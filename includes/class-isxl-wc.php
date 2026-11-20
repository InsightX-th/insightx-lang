<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WooCommerce: one product per language, one stock.
 *
 * - Product data in the enabled sync groups is copied to every translation
 *   on save (price, stock, SKU, shipping, tax, type, linked products,
 *   attributes, images, visibility).
 * - Variations are created for translations and kept in sync the same way.
 * - Stock changes from orders reach every translation.
 * - Shop / cart / checkout / account / terms pages follow the language; the
 *   cart swaps products to the visitor's language.
 * - Orders remember their language; customer emails are sent in it.
 */
class ISXL_WC {

    /** @var bool Re-entry guard while copying data between translations. */
    private static $syncing = false;
    /** @var array<int,bool> Emails whose locale we switched (spl_object_id => true). */
    private static $switched = [];
    /** @var string */
    private static $lang_before_email = '';

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;

        // Product sync
        add_action( 'woocommerce_after_product_object_save', [ __CLASS__, 'after_save' ], 20 );
        add_action( 'woocommerce_product_set_stock', [ __CLASS__, 'sync_stock' ] );
        add_action( 'woocommerce_variation_set_stock', [ __CLASS__, 'sync_stock' ] );
        add_action( 'isxl_post_duplicated', [ __CLASS__, 'on_duplicated' ], 10, 3 );
        add_filter( 'wc_product_has_unique_sku', [ __CLASS__, 'unique_sku' ], 10, 3 );
        add_filter( 'isxl_duplicate_skip_meta', [ __CLASS__, 'skip_meta' ] );

        // Front end
        foreach ( [ 'shop', 'cart', 'checkout', 'myaccount', 'terms' ] as $page ) {
            add_filter( "woocommerce_get_{$page}_page_id", [ __CLASS__, 'page_id' ] );
        }
        add_filter( 'page_link', [ __CLASS__, 'shop_page_link' ], 30, 2 );
        add_filter( 'request', [ __CLASS__, 'shop_page_request' ] );
        add_filter( 'woocommerce_get_cart_item_from_session', [ __CLASS__, 'cart_item' ], 20, 2 );
        add_filter( 'woocommerce_product_related_posts_query', [ __CLASS__, 'related_query' ], 10, 2 );

        // Orders and emails
        add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'order_lang' ] );
        add_action( 'woocommerce_store_api_checkout_update_order_meta', [ __CLASS__, 'order_lang' ] );
        add_action( 'woocommerce_init', [ __CLASS__, 'email_hooks' ] );
        add_filter( 'woocommerce_allow_restoring_email_locale', [ __CLASS__, 'email_restore' ], 1, 2 );

        // Strings
        add_action( 'isxl_register_strings', [ __CLASS__, 'register_strings' ] );
        add_filter( 'woocommerce_gateway_title', [ 'ISXL_Strings', 'filter' ], 20 );
        add_filter( 'woocommerce_gateway_description', [ 'ISXL_Strings', 'filter' ], 20 );
    }

    /* ------------------------------------------------------------------
     * Sync groups
     * ------------------------------------------------------------------ */

    /**
     * group => [label, meta keys, untranslated taxonomies copied as is]
     *
     * @return array<string,array{0:string,1:string[],2:string[]}>
     */
    public static function sync_groups() {
        return [
            'price'      => [ __( 'Price & sale dates', 'insightx-lang' ), [ '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to', '_price' ], [] ],
            'stock'      => [ __( 'Stock', 'insightx-lang' ), [ '_manage_stock', '_stock', '_stock_status', '_backorders', '_low_stock_amount', '_sold_individually' ], [] ],
            'sku'        => [ 'SKU / GTIN', [ '_sku', '_global_unique_id' ], [] ],
            'shipping'   => [ __( 'Weight, size & shipping class', 'insightx-lang' ), [ '_weight', '_length', '_width', '_height' ], [ 'product_shipping_class' ] ],
            'tax'        => [ __( 'Tax', 'insightx-lang' ), [ '_tax_status', '_tax_class' ], [] ],
            'type'       => [ __( 'Product type, virtual & downloads', 'insightx-lang' ), [ '_virtual', '_downloadable', '_downloadable_files', '_download_limit', '_download_expiry' ], [ 'product_type' ] ],
            'linked'     => [ __( 'Upsells, cross-sells & grouped', 'insightx-lang' ), [ '_upsell_ids', '_crosssell_ids', '_children' ], [] ],
            'attributes' => [ __( 'Attributes & default variation', 'insightx-lang' ), [ '_product_attributes', '_default_attributes' ], [] ],
            'images'     => [ __( 'Image & gallery', 'insightx-lang' ), [ '_thumbnail_id', '_product_image_gallery' ], [] ],
            'visibility' => [ __( 'Featured & catalog visibility', 'insightx-lang' ), [], [ 'product_visibility' ] ],
        ];
    }

    /** @return array<string,string> group => label (settings form) */
    public static function sync_groups_flat() {
        return array_map( function ( $g ) { return $g[0]; }, self::sync_groups() );
    }

    /** @return string[] enabled group keys */
    private static function enabled_groups() {
        $off = (array) ISXL_Model::settings()['wc_sync'];
        return array_values( array_diff( array_keys( self::sync_groups() ), $off ) );
    }

    /* ------------------------------------------------------------------
     * Product sync
     * ------------------------------------------------------------------ */

    /** @param WC_Product $product */
    public static function after_save( $product ) {
        if ( self::$syncing || ! $product instanceof WC_Product ) return;
        $id = $product->get_id();
        $trs = ISXL_Model::translations( 'post', $id );
        unset( $trs[ ISXL_Model::get_post_lang( $id ) ] );
        if ( ! $trs ) return;

        self::$syncing = true;
        try {
            foreach ( $trs as $lang => $tr_id ) {
                self::copy_product_data( $id, $tr_id, $lang, $product->is_type( 'variation' ) );
                if ( $product->is_type( 'variable' ) ) self::sync_variations( $id, $tr_id, $lang );
                self::refresh( $tr_id );
            }
        } finally {
            self::$syncing = false;
        }
    }

    /** Copy enabled sync groups from one product (or variation) to its translation. */
    private static function copy_product_data( $from, $to, $lang, $is_variation ) {
        $groups = self::sync_groups();
        foreach ( self::enabled_groups() as $g ) {
            list( , $keys, $taxes ) = $groups[ $g ];
            foreach ( $keys as $key ) {
                if ( ! metadata_exists( 'post', $from, $key ) ) {
                    delete_post_meta( $to, $key );
                    continue;
                }
                $v = get_post_meta( $from, $key, true );
                switch ( $key ) {
                    case '_upsell_ids':
                    case '_crosssell_ids':
                    case '_children':
                        $v = array_map( function ( $pid ) use ( $lang ) { return ISXL_Model::get_translation( 'post', (int) $pid, $lang ) ?: (int) $pid; }, (array) $v );
                        break;
                    case '_product_attributes':
                        $v = self::merge_attributes( (array) $v, (array) get_post_meta( $to, $key, true ) );
                        break;
                    case '_default_attributes':
                        $v = self::map_attribute_values( (array) $v, $lang );
                        break;
                }
                update_post_meta( $to, $key, wp_slash( $v ) );
            }
            if ( ! $is_variation ) {
                foreach ( $taxes as $tax ) {
                    $ids = wp_get_object_terms( $from, $tax, [ 'fields' => 'ids' ] );
                    if ( ! is_wp_error( $ids ) ) wp_set_object_terms( $to, array_map( 'intval', $ids ), $tax );
                }
                if ( $g === 'attributes' ) self::copy_attribute_terms( $from, $to, $lang );
            }
        }
        if ( $is_variation ) {
            // Variation attribute values (attribute_pa_color = slug) follow the term translation.
            foreach ( get_post_meta( $from ) as $key => $vals ) {
                if ( strpos( $key, 'attribute_' ) !== 0 ) continue;
                $mapped = self::map_attribute_values( [ substr( $key, 10 ) => $vals[0] ], $lang );
                update_post_meta( $to, $key, wp_slash( (string) reset( $mapped ) ) );
            }
        }
    }

    /**
     * Taxonomy attributes come from the source; custom (text) attributes keep
     * the translation's own wording when it has the same attribute.
     */
    private static function merge_attributes( array $src, array $dst ) {
        foreach ( $src as $k => $a ) {
            if ( empty( $a['is_taxonomy'] ) && isset( $dst[ $k ]['value'] ) && $dst[ $k ]['value'] !== '' ) {
                $src[ $k ]['value'] = $dst[ $k ]['value'];
            }
        }
        return $src;
    }

    /** [taxonomy => term slug] → slugs of the translated terms. */
    private static function map_attribute_values( array $pairs, $lang ) {
        foreach ( $pairs as $tax => $slug ) {
            if ( ! taxonomy_exists( $tax ) || ! ISXL_Model::is_translated_taxonomy( $tax ) || $slug === '' ) continue;
            $t = get_term_by( 'slug', $slug, $tax );
            if ( ! $t ) continue;
            $tr = ISXL_Model::get_translation( 'term', $t->term_id, $lang );
            $tt = $tr ? get_term( $tr, $tax ) : null;
            if ( $tt && ! is_wp_error( $tt ) ) $pairs[ $tax ] = $tt->slug;
        }
        return $pairs;
    }

    private static function copy_attribute_terms( $from, $to, $lang ) {
        foreach ( (array) get_post_meta( $from, '_product_attributes', true ) as $tax => $a ) {
            if ( empty( $a['is_taxonomy'] ) || ! taxonomy_exists( $tax ) ) continue;
            $ids = wp_get_object_terms( $from, $tax, [ 'fields' => 'ids' ] );
            if ( is_wp_error( $ids ) ) continue;
            if ( ISXL_Model::is_translated_taxonomy( $tax ) ) {
                $ids = array_values( array_filter( array_map( function ( $t ) use ( $lang ) { return ISXL_Model::get_translation( 'term', $t, $lang ) ?: $t; }, $ids ) ) );
            }
            wp_set_object_terms( $to, array_map( 'intval', $ids ), $tax );
        }
    }

    /** Every variation of the source has a linked twin under the translated parent. */
    private static function sync_variations( $parent, $tr_parent, $lang ) {
        foreach ( get_children( [ 'post_parent' => $parent, 'post_type' => 'product_variation', 'post_status' => 'any', 'fields' => 'ids' ] ) as $var_id ) {
            $twin = ISXL_Model::get_translation( 'post', $var_id, $lang );
            if ( ! $twin ) $twin = self::create_variation( $var_id, $tr_parent, $lang );
            if ( $twin ) {
                self::copy_product_data( $var_id, $twin, $lang, true );
                if ( get_post_status( $twin ) !== get_post_status( $var_id ) ) {
                    wp_update_post( [ 'ID' => $twin, 'post_status' => get_post_status( $var_id ) ] );
                }
            }
        }
        WC_Product_Variable::sync( $tr_parent );
    }

    private static function create_variation( $var_id, $tr_parent, $lang ) {
        $src = get_post( $var_id );
        $new = wp_insert_post( [
            'post_type'    => 'product_variation',
            'post_parent'  => $tr_parent,
            'post_status'  => $src->post_status,
            'post_title'   => get_the_title( $tr_parent ),
            'post_excerpt' => $src->post_excerpt, // variation description
            'menu_order'   => $src->menu_order,
            'post_author'  => $src->post_author,
        ], true );
        if ( is_wp_error( $new ) ) return 0;
        ISXL_Duplicator::copy_meta( $var_id, $new );
        ISXL_Model::set_post_lang( $var_id, ISXL_Model::get_post_lang( $src->post_parent ) ?: ISXL_Model::default_slug() );
        ISXL_Model::set_post_lang( $new, $lang );
        ISXL_Model::link( 'post', $var_id, $new );
        return $new;
    }

    /** New translation draft of a variable product: create its variations now. */
    public static function on_duplicated( $src, $new, $lang ) {
        if ( get_post_type( $src ) !== 'product' ) return;
        $p = wc_get_product( $src );
        if ( ! $p ) return;
        self::$syncing = true;
        try {
            if ( $p->is_type( 'variable' ) ) self::sync_variations( $src, $new, $lang );
            self::copy_attribute_terms( $src, $new, $lang );
            update_post_meta( $new, '_default_attributes', self::map_attribute_values( (array) get_post_meta( $src, '_default_attributes', true ), $lang ) );
            foreach ( [ '_upsell_ids', '_crosssell_ids', '_children' ] as $k ) {
                $ids = (array) get_post_meta( $src, $k, true );
                if ( $ids ) update_post_meta( $new, $k, array_map( function ( $pid ) use ( $lang ) { return ISXL_Model::get_translation( 'post', (int) $pid, $lang ) ?: (int) $pid; }, $ids ) );
            }
            self::refresh( $new );
        } finally {
            self::$syncing = false;
        }
    }

    /** Stock changed by an order or wc_update_product_stock(): mirror it. */
    public static function sync_stock( $product ) {
        if ( self::$syncing || ! $product instanceof WC_Product || ! in_array( 'stock', self::enabled_groups(), true ) ) return;
        $id  = $product->get_id();
        $trs = ISXL_Model::translations( 'post', $id );
        unset( $trs[ ISXL_Model::get_post_lang( $id ) ] );
        if ( ! $trs ) return;
        self::$syncing = true;
        try {
            foreach ( $trs as $tr_id ) {
                // Not "updating": WooCommerce saves the product, so stock status and caches follow.
                wc_update_product_stock( $tr_id, $product->get_stock_quantity(), 'set', false );
            }
        } finally {
            self::$syncing = false;
        }
    }

    /**
     * Re-save through the CRUD layer after raw meta copies, so WooCommerce
     * rebuilds its lookup tables, stock status and transients. Runs inside the
     * $syncing guard, so the save does not sync back.
     */
    private static function refresh( $id ) {
        clean_post_cache( $id );
        $p = wc_get_product( $id );
        if ( $p ) $p->save();
        wc_delete_product_transients( $id );
    }

    /** The same SKU is allowed inside one translation group. */
    public static function unique_sku( $found, $product_id, $sku ) {
        if ( ! $found || $sku === '' ) return $found;
        global $wpdb;
        $others = $wpdb->get_col( $wpdb->prepare(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_sku' AND pm.meta_value = %s AND pm.post_id <> %d AND p.post_status <> 'trash'",
            $sku,
            $product_id
        ) );
        $group = array_map( 'intval', ISXL_Model::translations( 'post', $product_id ) );
        foreach ( $others as $o ) {
            if ( ! in_array( (int) $o, $group, true ) ) return $found;
        }
        return false;
    }

    public static function skip_meta( $skip ) {
        // Recomputed by WooCommerce for the new product.
        return array_merge( $skip, [ 'total_sales', '_wc_average_rating', '_wc_rating_count', '_wc_review_count', '_product_version' ] );
    }

    /* ------------------------------------------------------------------
     * Front end
     * ------------------------------------------------------------------ */

    public static function page_id( $id ) {
        if ( $id <= 0 || ! ISXL_Router::is_frontend_context() ) return $id;
        return ISXL_Model::get_translation( 'post', (int) $id, ISXL_Model::current() ) ?: $id;
    }

    /** Raw shop page id (unmapped). */
    private static function raw_shop_id() {
        return (int) get_option( 'woocommerce_shop_page_id' );
    }

    /**
     * A translated shop page links to the product archive in its language —
     * the archive path comes from the default shop page's slug.
     */
    public static function shop_page_link( $link, $post_id ) {
        $shop = self::raw_shop_id();
        if ( ! $shop || (int) $post_id === $shop ) return $link;
        $trs = ISXL_Model::translations( 'post', $shop );
        if ( ! in_array( (int) $post_id, $trs, true ) ) return $link;
        $archive = get_post_type_archive_link( 'product' );
        return $archive ? ISXL_Links::to_lang( $archive, ISXL_Model::get_post_lang( $post_id ) ) : $link;
    }

    /** Opening a translated shop page by its own slug shows the product archive. */
    public static function shop_page_request( $vars ) {
        if ( empty( $vars['pagename'] ) ) return $vars;
        $page = get_page_by_path( $vars['pagename'] );
        $shop = self::raw_shop_id();
        if ( $page && $shop && $page->ID !== $shop && in_array( (int) $page->ID, ISXL_Model::translations( 'post', $shop ), true ) ) {
            $new = [ 'post_type' => 'product' ];
            if ( ! empty( $vars['paged'] ) ) $new['paged'] = $vars['paged'];
            return $new;
        }
        return $vars;
    }

    /** Cart items follow the visitor's language. */
    public static function cart_item( $item, $values ) {
        if ( ! ISXL_Router::is_frontend_context() ) return $item;
        $lang = ISXL_Model::current();
        $pid  = ISXL_Model::get_translation( 'post', (int) $item['product_id'], $lang );
        $vid  = ! empty( $item['variation_id'] ) ? ISXL_Model::get_translation( 'post', (int) $item['variation_id'], $lang ) : 0;
        if ( ( ! $pid || $pid === (int) $item['product_id'] ) && ( ! $vid || $vid === (int) $item['variation_id'] ) ) return $item;
        if ( $pid ) $item['product_id'] = $pid;
        if ( $vid ) {
            $item['variation_id'] = $vid;
            if ( ! empty( $item['variation'] ) ) {
                $mapped = [];
                foreach ( $item['variation'] as $k => $v ) {
                    $m             = self::map_attribute_values( [ preg_replace( '/^attribute_/', '', $k ) => $v ], $lang );
                    $mapped[ $k ]  = (string) reset( $m );
                }
                $item['variation'] = $mapped;
            }
        }
        $p = wc_get_product( $vid ?: $pid );
        if ( $p ) $item['data'] = $p;
        return $item;
    }

    /** Related products: same language as the product shown. */
    public static function related_query( $query, $product_id ) {
        global $wpdb;
        $term = get_term_by( 'slug', ISXL_Model::get_post_lang( $product_id ), ISXL_Model::TAX );
        if ( ! $term ) return $query;
        $query['join']  .= " INNER JOIN {$wpdb->term_relationships} isxl_tr ON isxl_tr.object_id = p.ID";
        $query['where'] .= $wpdb->prepare( ' AND isxl_tr.term_taxonomy_id = %d', $term->term_taxonomy_id );
        return $query;
    }

    /* ------------------------------------------------------------------
     * Orders and emails
     * ------------------------------------------------------------------ */

    /** @param WC_Order $order */
    public static function order_lang( $order ) {
        $order->update_meta_data( '_isxl_lang', ISXL_Model::current() );
        if ( current_action() === 'woocommerce_store_api_checkout_update_order_meta' ) $order->save_meta_data();
    }

    public static function order_language( $order ) {
        $l = $order instanceof WC_Order ? (string) $order->get_meta( '_isxl_lang' ) : '';
        return ISXL_Model::is_language( $l ) ? $l : ISXL_Model::default_slug();
    }

    public static function email_hooks() {
        if ( ! function_exists( 'WC' ) || ! WC()->mailer() ) return;
        foreach ( WC()->mailer()->get_emails() as $email ) {
            if ( ! $email->is_customer_email() ) continue; // store-owner emails stay in the site language
            add_filter( 'woocommerce_email_recipient_' . $email->id, [ __CLASS__, 'email_switch' ], 1, 3 );
            foreach ( [ 'subject', 'heading', 'additional_content' ] as $f ) {
                add_filter( "woocommerce_email_{$f}_{$email->id}", function ( $text, $obj, $mail ) use ( $f ) {
                    return self::email_text( $text, $mail, $f );
                }, 20, 3 );
            }
        }
    }

    /**
     * Fires inside WC_Email::send_notification(), after the order is attached
     * and before anything is rendered — the place to switch language.
     */
    public static function email_switch( $recipient, $object, $email ) {
        if ( ! $object instanceof WC_Order || ! $email instanceof WC_Email ) return $recipient;
        $key = spl_object_id( $email );
        if ( isset( self::$switched[ $key ] ) ) return $recipient;
        $lang = self::order_language( $object );
        $l    = ISXL_Model::get_language( $lang );
        if ( ! $l ) return $recipient;
        self::$lang_before_email = ISXL_Model::current();
        ISXL_Model::set_current( $lang );
        switch_to_locale( $l['locale'] );
        self::$switched[ $key ] = true;
        return $recipient;
    }

    public static function email_restore( $allow, $email ) {
        $key = is_object( $email ) ? spl_object_id( $email ) : 0;
        if ( isset( self::$switched[ $key ] ) ) {
            unset( self::$switched[ $key ] );
            restore_previous_locale();
            if ( self::$lang_before_email !== '' ) ISXL_Model::set_current( self::$lang_before_email );
        }
        return $allow;
    }

    /** Subject/heading/additional content typed in WooCommerce → Emails, translated in Strings. */
    private static function email_text( $text, $email, $field ) {
        if ( ! $email instanceof WC_Email ) return $text;
        $raw = (string) $email->get_option( $field );
        if ( $raw === '' ) return $text; // default text — already translated by the locale switch
        $tr = ISXL_Strings::translate( $raw );
        return $tr === $raw ? $text : $email->format_string( $tr );
    }

    public static function register_strings() {
        $g = 'WooCommerce';
        foreach ( WC()->payment_gateways()->payment_gateways() as $gw ) {
            if ( 'yes' !== $gw->enabled ) continue;
            ISXL_Strings::register( $gw->get_option( 'title' ), $gw->get_method_title() . ' — ' . __( 'Title' ), $g );
            ISXL_Strings::register( $gw->get_option( 'description' ), $gw->get_method_title() . ' — ' . __( 'Description' ), $g, true );
        }
        foreach ( WC()->mailer()->get_emails() as $email ) {
            if ( ! $email->is_customer_email() || ! $email->is_enabled() ) continue;
            foreach ( [ 'subject', 'heading', 'additional_content' ] as $f ) {
                ISXL_Strings::register( (string) $email->get_option( $f ), $email->get_title() . ' — ' . $f, __( 'WooCommerce emails', 'insightx-lang' ), $f === 'additional_content' );
            }
        }
        ISXL_Strings::register( (string) get_option( 'woocommerce_email_footer_text' ), __( 'Email footer', 'insightx-lang' ), __( 'WooCommerce emails', 'insightx-lang' ), true );
    }

    /* ------------------------------------------------------------------
     * Settings tab
     * ------------------------------------------------------------------ */

    public static function render_tab() {
        $on = self::enabled_groups();
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="isxl_save"><input type="hidden" name="section" value="wc">';
        wp_nonce_field( 'isxl_save' );
        echo '<div class="isxl-card"><div class="isxl-card-head"><div class="isxl-card-head-title"><div><h2>' . esc_html__( 'Keep identical across translations', 'insightx-lang' ) . '</h2><p class="isxl-card-sub">'
            . esc_html__( 'Ticked data is copied from the product you save to all its translations. Title, description and slug are always per language.', 'insightx-lang' ) . '</p></div></div></div>';
        echo '<div class="isxl-card-body isxl-checklist">';
        foreach ( self::sync_groups() as $k => $g ) {
            echo '<label><input type="checkbox" name="wc_sync[]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $on, true ), true, false ) . '> ' . esc_html( $g[0] );
            if ( $g[1] ) echo ' <span class="isxl-muted">' . esc_html( implode( ', ', $g[1] ) ) . '</span>';
            echo '</label>';
        }
        echo '</div><div class="isxl-card-foot"><button type="submit" class="isxl-btn isxl-btn-primary">' . esc_html__( 'Save', 'insightx-lang' ) . '</button></div></div></form>';

        echo '<div class="isxl-card"><div class="isxl-card-body isxl-note"><section><h3>' . esc_html__( 'Pages', 'insightx-lang' ) . '</h3><p>'
            . esc_html__( 'Translate the Shop, Cart, Checkout, My account and Terms pages like any page (Pages → + in the language column). WooCommerce then uses the right one for each language.', 'insightx-lang' )
            . '</p></section></div></div>';
    }
}
