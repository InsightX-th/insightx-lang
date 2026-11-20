<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * "Add translation": copies a post (or term) into another language as a
 * draft and links the two, so the translator edits real content instead of
 * starting from a blank page. Block markup and Elementor data are copied
 * verbatim with the rest of the meta.
 */
class ISXL_Duplicator {

    /** Meta that belongs to one post only. */
    const SKIP_META = [ '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_isxl_group', '_wp_trash_meta_status', '_wp_trash_meta_time', '_elementor_css', '_encloseme', '_pingme' ];

    public static function init() {
        add_action( 'admin_post_isxl_translate', [ __CLASS__, 'handle_post' ] );
        add_action( 'admin_post_isxl_translate_term', [ __CLASS__, 'handle_term' ] );
    }

    public static function url( $post_id, $lang ) {
        return wp_nonce_url( admin_url( 'admin-post.php?action=isxl_translate&post=' . (int) $post_id . '&lang=' . rawurlencode( $lang ) ), 'isxl_translate_' . (int) $post_id );
    }

    public static function term_url( $term_id, $taxonomy, $lang ) {
        return wp_nonce_url( admin_url( 'admin-post.php?action=isxl_translate_term&term=' . (int) $term_id . '&taxonomy=' . rawurlencode( $taxonomy ) . '&lang=' . rawurlencode( $lang ) ), 'isxl_translate_term_' . (int) $term_id );
    }

    public static function handle_post() {
        $id   = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
        $lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';
        check_admin_referer( 'isxl_translate_' . $id );
        if ( ! current_user_can( 'edit_post', $id ) ) wp_die( esc_html__( 'You cannot edit this item.', 'insightx-lang' ), 403 );
        $new = self::duplicate_post( $id, $lang );
        if ( is_wp_error( $new ) ) wp_die( esc_html( $new->get_error_message() ) );
        wp_safe_redirect( admin_url( 'post.php?post=' . $new . '&action=edit' ) );
        exit;
    }

    public static function handle_term() {
        $id   = isset( $_GET['term'] ) ? absint( $_GET['term'] ) : 0;
        $tax  = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
        $lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';
        check_admin_referer( 'isxl_translate_term_' . $id );
        $t = get_taxonomy( $tax );
        if ( ! $t || ! current_user_can( $t->cap->edit_terms ) ) wp_die( esc_html__( 'You cannot edit this item.', 'insightx-lang' ), 403 );
        $new = self::duplicate_term( $id, $tax, $lang );
        if ( is_wp_error( $new ) ) wp_die( esc_html( $new->get_error_message() ) );
        wp_safe_redirect( get_edit_term_link( $new, $tax ) );
        exit;
    }

    /**
     * @return int|WP_Error New post id (an existing translation is returned as is).
     */
    public static function duplicate_post( $id, $lang ) {
        $src = get_post( $id );
        if ( ! $src || ! ISXL_Model::is_translated_post_type( $src->post_type ) ) return new WP_Error( 'isxl', __( 'This content type is not translated.', 'insightx-lang' ) );
        if ( ! ISXL_Model::is_language( $lang ) ) return new WP_Error( 'isxl', __( 'Unknown language.', 'insightx-lang' ) );
        $existing = ISXL_Model::get_translation( 'post', $id, $lang );
        if ( $existing ) return $existing;

        $parent = $src->post_parent ? ( ISXL_Model::get_translation( 'post', $src->post_parent, $lang ) ?: 0 ) : 0;
        $new = wp_insert_post( wp_slash( [
            'post_type'      => $src->post_type,
            'post_status'    => 'draft',
            'post_title'     => $src->post_title,
            'post_content'   => $src->post_content,
            'post_excerpt'   => $src->post_excerpt,
            'post_parent'    => $parent,
            'menu_order'     => $src->menu_order,
            'comment_status' => $src->comment_status,
            'ping_status'    => $src->ping_status,
            'post_password'  => $src->post_password,
            'post_author'    => get_current_user_id() ?: $src->post_author,
        ] ), true );
        if ( is_wp_error( $new ) ) return $new;

        ISXL_Model::set_post_lang( $new, $lang );
        ISXL_Model::link( 'post', $id, $new );
        self::copy_meta( $id, $new );
        self::copy_terms( $id, $new, $lang );

        /**
         * After a translation draft is created (WooCommerce copies variations here).
         */
        do_action( 'isxl_post_duplicated', $id, $new, $lang );
        return $new;
    }

    public static function copy_meta( $from, $to, array $only = [] ) {
        $skip = apply_filters( 'isxl_duplicate_skip_meta', self::SKIP_META, $from, $to );
        foreach ( get_post_meta( $from ) as $key => $values ) {
            if ( in_array( $key, $skip, true ) || ( $only && ! in_array( $key, $only, true ) ) ) continue;
            delete_post_meta( $to, $key );
            foreach ( $values as $v ) {
                // get_post_meta() returns serialized strings; add_post_meta() would serialize twice.
                add_post_meta( $to, $key, wp_slash( maybe_unserialize( $v ) ) );
            }
        }
    }

    /** Terms of translated taxonomies are swapped for their translations. */
    public static function copy_terms( $from, $to, $lang ) {
        foreach ( get_object_taxonomies( get_post_type( $from ) ) as $tax ) {
            if ( $tax === ISXL_Model::TAX ) continue;
            $ids = wp_get_object_terms( $from, $tax, [ 'fields' => 'ids', 'lang' => 'all' ] );
            if ( is_wp_error( $ids ) ) continue;
            if ( ISXL_Model::is_translated_taxonomy( $tax ) ) {
                $ids = array_values( array_filter( array_map( function ( $t ) use ( $lang ) {
                    return ISXL_Model::get_translation( 'term', $t, $lang );
                }, $ids ) ) );
            }
            wp_set_object_terms( $to, array_map( 'intval', $ids ), $tax );
        }
    }

    /**
     * @return int|WP_Error New term id.
     */
    public static function duplicate_term( $id, $tax, $lang ) {
        $src = get_term( $id, $tax );
        if ( ! $src || is_wp_error( $src ) || ! ISXL_Model::is_translated_taxonomy( $tax ) ) return new WP_Error( 'isxl', __( 'This taxonomy is not translated.', 'insightx-lang' ) );
        if ( ! ISXL_Model::is_language( $lang ) ) return new WP_Error( 'isxl', __( 'Unknown language.', 'insightx-lang' ) );
        $existing = ISXL_Model::get_translation( 'term', $id, $lang );
        if ( $existing ) return $existing;

        $parent = $src->parent ? ISXL_Model::get_translation( 'term', $src->parent, $lang ) : 0;
        // Core refuses two terms with the same name under one parent, so the copy is marked with the language.
        $res = wp_insert_term( $src->name . ' (' . strtoupper( $lang ) . ')', $tax, [
            'slug'        => $src->slug . '-' . $lang,
            'description' => $src->description,
            'parent'      => $parent,
        ] );
        if ( is_wp_error( $res ) ) return $res;
        $new = (int) $res['term_id'];
        ISXL_Model::set_term_lang( $new, $lang );
        ISXL_Model::link( 'term', $id, $new );
        foreach ( get_term_meta( $id ) as $key => $values ) {
            if ( in_array( $key, [ ISXL_Model::GROUP, ISXL_Model::TERM_LANG ], true ) ) continue;
            foreach ( $values as $v ) add_term_meta( $new, $key, wp_slash( maybe_unserialize( $v ) ) );
        }
        do_action( 'isxl_term_duplicated', $id, $new, $lang, $tax );
        return $new;
    }
}
