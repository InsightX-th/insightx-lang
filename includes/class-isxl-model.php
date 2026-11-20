<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Languages, settings, object languages and translation groups.
 *
 * - Post language: taxonomy `isxl_language` (one term per language slug), so
 *   front-end filtering is a plain tax_query.
 * - Term language: term meta `_isxl_lang`.
 * - Translation group: meta `_isxl_group` holding one shared id on every
 *   member (posts and terms each have their own meta table).
 */
class ISXL_Model {

    const TAX       = 'isxl_language';
    const GROUP     = '_isxl_group';
    const TERM_LANG = '_isxl_lang';
    const OPT_LANGS = 'isxl_languages';
    const OPT_SET   = 'isxl_settings';

    /** @var string|null Current front-end language slug. */
    private static $current = null;
    /** @var array<string,array<string,mixed>>|null */
    private static $langs = null;
    /** @var array<string,array<string,int>> Translation map cache, keyed "type:id". */
    private static $tr_cache = [];

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ], 1 );
        add_action( 'init', [ __CLASS__, 'maybe_flush' ], 99 );
        add_action( 'wp_insert_post', [ __CLASS__, 'on_insert_post' ], 5, 3 );
        add_action( 'created_term', [ __CLASS__, 'on_created_term' ], 5, 3 );
        add_action( 'before_delete_post', [ __CLASS__, 'forget_post' ] );
    }

    public static function register_taxonomy() {
        register_taxonomy( self::TAX, [], [
            'public'             => false,
            'show_ui'            => false,
            'show_in_rest'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'hierarchical'       => false,
            'show_in_nav_menus'  => false,
            'label'              => 'Language',
        ] );
    }

    public static function maybe_flush() {
        if ( get_option( 'isxl_flush_rewrite' ) ) {
            delete_option( 'isxl_flush_rewrite' );
            flush_rewrite_rules( false );
        }
    }

    /* ------------------------------------------------------------------
     * Languages
     * ------------------------------------------------------------------ */

    /** @return array<string,array{slug:string,locale:string,name:string,flag:string,is_default:bool,order:int}> */
    public static function languages() {
        if ( self::$langs !== null ) return self::$langs;
        $raw = get_option( self::OPT_LANGS, [] );
        self::$langs = [];
        foreach ( (array) $raw as $l ) {
            if ( empty( $l['slug'] ) ) continue;
            self::$langs[ $l['slug'] ] = [
                'slug'       => (string) $l['slug'],
                'locale'     => (string) ( $l['locale'] ?? 'en_US' ),
                'name'       => (string) ( $l['name'] ?? $l['slug'] ),
                'flag'       => (string) ( $l['flag'] ?? '' ),
                'is_default' => ! empty( $l['is_default'] ),
                'order'      => (int) ( $l['order'] ?? 0 ),
            ];
        }
        uasort( self::$langs, function ( $a, $b ) { return $a['order'] <=> $b['order']; } );
        return self::$langs;
    }

    public static function slugs() {
        return array_keys( self::languages() );
    }

    public static function has_languages() {
        return count( self::languages() ) > 0;
    }

    public static function default_slug() {
        foreach ( self::languages() as $l ) {
            if ( $l['is_default'] ) return $l['slug'];
        }
        $first = array_key_first( self::languages() );
        return $first === null ? '' : $first;
    }

    public static function get_language( $slug ) {
        $langs = self::languages();
        return $langs[ $slug ] ?? null;
    }

    public static function is_language( $slug ) {
        return is_string( $slug ) && $slug !== '' && isset( self::languages()[ $slug ] );
    }

    /**
     * Sanitize and store the language list. Exactly one default; slugs are
     * lowercase [a-z0-9-]; each slug gets its term in the language taxonomy.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    public static function save_languages( array $rows ) {
        $clean = [];
        $order = 0;
        foreach ( $rows as $r ) {
            $slug = sanitize_key( $r['slug'] ?? '' );
            if ( $slug === '' || isset( $clean[ $slug ] ) || in_array( $slug, [ 'wp-admin', 'wp-json', 'all' ], true ) ) continue;
            $clean[ $slug ] = [
                'slug'       => $slug,
                'locale'     => preg_replace( '/[^A-Za-z0-9_]/', '', (string) ( $r['locale'] ?? 'en_US' ) ),
                'name'       => sanitize_text_field( $r['name'] ?? $slug ),
                'flag'       => strtolower( preg_replace( '/[^A-Za-z]/', '', (string) ( $r['flag'] ?? '' ) ) ),
                'is_default' => ! empty( $r['is_default'] ),
                'order'      => $order++,
            ];
        }
        $defaults = array_keys( array_filter( $clean, function ( $l ) { return $l['is_default']; } ) );
        if ( $clean && count( $defaults ) !== 1 ) {
            $keep = $defaults[0] ?? array_key_first( $clean );
            foreach ( $clean as $k => $l ) $clean[ $k ]['is_default'] = ( $k === $keep );
        }
        update_option( self::OPT_LANGS, array_values( $clean ) );
        self::$langs = null;

        foreach ( $clean as $l ) {
            if ( ! term_exists( $l['slug'], self::TAX ) ) {
                wp_insert_term( $l['slug'], self::TAX, [ 'slug' => $l['slug'] ] );
            }
        }
        update_option( 'isxl_flush_rewrite', 1 );
    }

    /* ------------------------------------------------------------------
     * Current language
     * ------------------------------------------------------------------ */

    public static function current() {
        return self::$current ?? self::default_slug();
    }

    public static function set_current( $slug ) {
        if ( self::is_language( $slug ) ) self::$current = $slug;
    }

    public static function is_default( $slug ) {
        return $slug === self::default_slug();
    }

    /* ------------------------------------------------------------------
     * Settings
     * ------------------------------------------------------------------ */

    /** @return array<string,mixed> */
    public static function settings() {
        $s = get_option( self::OPT_SET, [] );
        return wp_parse_args( is_array( $s ) ? $s : [], [
            'post_types' => [ 'post', 'page', 'wp_block', 'product' ],
            'taxonomies' => [ 'category', 'post_tag', 'product_cat', 'product_tag' ],
            'attributes' => 1,   // translate every pa_* taxonomy
            'theme_mods' => [],  // theme mod names whose text is translatable
            'wc_sync'    => [],  // meta keys switched OFF from sync (empty = sync all)
        ] );
    }

    public static function save_settings( array $s ) {
        update_option( self::OPT_SET, $s );
    }

    /**
     * Post types visitors can see (plus reusable blocks). Internal types such
     * as ACF's acf-post-type are never translated: filtering their queries by
     * language would hide the definitions plugins load on every request.
     */
    public static function translatable_post_type( $pt ) {
        if ( $pt === 'wp_block' ) return true;
        $o = get_post_type_object( $pt );
        return $o && ( $o->public || $o->publicly_queryable );
    }

    public static function translatable_taxonomy( $tax ) {
        if ( $tax === 'wp_pattern_category' ) return true;
        $o = get_taxonomy( $tax );
        return $o && ( $o->public || $o->publicly_queryable );
    }

    /** @return string[] */
    public static function post_types() {
        $pts = array_values( array_filter( (array) self::settings()['post_types'], [ __CLASS__, 'translatable_post_type' ] ) );
        return apply_filters( 'isxl_post_types', $pts );
    }

    /** @return string[] */
    public static function taxonomies() {
        $s    = self::settings();
        $taxs = array_values( array_filter( (array) $s['taxonomies'], [ __CLASS__, 'translatable_taxonomy' ] ) );
        if ( ! empty( $s['attributes'] ) && function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
            $taxs = array_merge( $taxs, wc_get_attribute_taxonomy_names() );
        }
        return apply_filters( 'isxl_taxonomies', array_values( array_unique( $taxs ) ) );
    }

    public static function is_translated_post_type( $pt ) {
        return is_string( $pt ) && in_array( $pt, self::post_types(), true );
    }

    public static function is_translated_taxonomy( $tax ) {
        return is_string( $tax ) && in_array( $tax, self::taxonomies(), true );
    }

    /* ------------------------------------------------------------------
     * Object languages
     * ------------------------------------------------------------------ */

    public static function get_post_lang( $post_id ) {
        $terms = get_the_terms( (int) $post_id, self::TAX );
        if ( ! $terms || is_wp_error( $terms ) ) return '';
        return $terms[0]->slug;
    }

    public static function set_post_lang( $post_id, $slug ) {
        if ( ! self::is_language( $slug ) ) return;
        wp_set_object_terms( (int) $post_id, $slug, self::TAX, false );
        self::$tr_cache = [];
    }

    public static function get_term_lang( $term_id ) {
        return (string) get_term_meta( (int) $term_id, self::TERM_LANG, true );
    }

    public static function set_term_lang( $term_id, $slug ) {
        if ( ! self::is_language( $slug ) ) return;
        update_term_meta( (int) $term_id, self::TERM_LANG, $slug );
        self::$tr_cache = [];
    }

    /**
     * New posts of a translated type get a language straight away: an explicit
     * request value, then the admin list filter, then the default language.
     */
    public static function on_insert_post( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || ! self::is_translated_post_type( $post->post_type ) ) return;
        if ( self::get_post_lang( $post_id ) !== '' ) return;
        $lang = '';
        // phpcs:ignore WordPress.Security.NonceVerification -- only picks a default; the save itself is nonce-checked by core.
        foreach ( [ 'isxl_lang', 'new_lang' ] as $k ) {
            if ( isset( $_REQUEST[ $k ] ) && self::is_language( sanitize_key( wp_unslash( $_REQUEST[ $k ] ) ) ) ) {
                $lang = sanitize_key( wp_unslash( $_REQUEST[ $k ] ) );
                break;
            }
        }
        if ( $lang === '' && is_admin() ) {
            $admin = self::admin_lang();
            if ( $admin !== '' ) $lang = $admin;
        }
        if ( $lang === '' ) $lang = is_admin() ? self::default_slug() : self::current();
        self::set_post_lang( $post_id, $lang );
    }

    public static function on_created_term( $term_id, $tt_id, $taxonomy ) {
        if ( ! self::is_translated_taxonomy( $taxonomy ) || self::get_term_lang( $term_id ) !== '' ) return;
        // phpcs:ignore WordPress.Security.NonceVerification
        $req = isset( $_REQUEST['isxl_lang'] ) ? sanitize_key( wp_unslash( $_REQUEST['isxl_lang'] ) ) : '';
        $lang = self::is_language( $req ) ? $req : ( is_admin() ? ( self::admin_lang() ?: self::default_slug() ) : self::current() );
        self::set_term_lang( $term_id, $lang );
    }

    /** Language chosen in the admin bar filter ('' = all languages). */
    public static function admin_lang() {
        $l = (string) get_user_meta( get_current_user_id(), 'isxl_admin_lang', true );
        return self::is_language( $l ) ? $l : '';
    }

    /* ------------------------------------------------------------------
     * Translation groups ($type is 'post' or 'term')
     * ------------------------------------------------------------------ */

    private static function meta_get( $type, $id ) {
        return (string) ( $type === 'term' ? get_term_meta( $id, self::GROUP, true ) : get_post_meta( $id, self::GROUP, true ) );
    }

    private static function meta_set( $type, $id, $group ) {
        $type === 'term' ? update_term_meta( $id, self::GROUP, $group ) : update_post_meta( $id, self::GROUP, $group );
    }

    private static function lang_of( $type, $id ) {
        return $type === 'term' ? self::get_term_lang( $id ) : self::get_post_lang( $id );
    }

    /**
     * All translations of an object, the object itself included.
     *
     * @return array<string,int> lang slug => object id
     */
    public static function translations( $type, $id ) {
        $id  = (int) $id;
        $key = $type . ':' . $id;
        if ( isset( self::$tr_cache[ $key ] ) ) return self::$tr_cache[ $key ];

        $out   = [];
        $own   = self::lang_of( $type, $id );
        $group = self::meta_get( $type, $id );
        if ( $group !== '' ) {
            global $wpdb;
            $table = $type === 'term' ? $wpdb->termmeta : $wpdb->postmeta;
            $col   = $type === 'term' ? 'term_id' : 'post_id';
            // ponytail: meta_value lookup per group; move groups to a taxonomy if sites reach ~50k posts.
            $ids = $wpdb->get_col( $wpdb->prepare( "SELECT {$col} FROM {$table} WHERE meta_key = %s AND meta_value = %s", self::GROUP, $group ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            foreach ( $ids as $other ) {
                $other = (int) $other;
                if ( $type === 'post' && ! get_post_status( $other ) ) continue;
                $l = self::lang_of( $type, $other );
                if ( $l !== '' && self::is_language( $l ) && ! isset( $out[ $l ] ) ) $out[ $l ] = $other;
            }
        }
        if ( $own !== '' ) $out[ $own ] = $id;
        return self::$tr_cache[ $key ] = $out;
    }

    public static function get_translation( $type, $id, $lang ) {
        return self::translations( $type, $id )[ $lang ] ?? 0;
    }

    /** Put $other into $id's group (creating the group if needed). */
    public static function link( $type, $id, $other ) {
        $group = self::meta_get( $type, $id );
        if ( $group === '' ) {
            $group = wp_generate_uuid4();
            self::meta_set( $type, $id, $group );
        }
        self::meta_set( $type, $other, $group );
        self::$tr_cache = [];
    }

    public static function unlink( $type, $id ) {
        $type === 'term' ? delete_term_meta( $id, self::GROUP ) : delete_post_meta( $id, self::GROUP );
        self::$tr_cache = [];
    }

    public static function forget_post() {
        self::$tr_cache = [];
    }

    /** Flag emoji from a 2-letter country code ('' when none). */
    public static function flag_emoji( $code ) {
        $code = strtoupper( (string) $code );
        if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) return '';
        $out = '';
        foreach ( str_split( $code ) as $ch ) {
            $out .= mb_chr( 0x1F1E6 + ord( $ch ) - 65, 'UTF-8' );
        }
        return $out;
    }
}
