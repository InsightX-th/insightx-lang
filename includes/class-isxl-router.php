<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Works out the current language and hides the /{lang}/ prefix from WP's
 * request parser.
 *
 * The prefix is stripped from REQUEST_URI only while WP::parse_request()
 * runs, then put back — so rewrite rules stay untouched, and
 * redirect_canonical() still compares the real URL with the (prefixed)
 * permalink.
 */
class ISXL_Router {

    const COOKIE = 'isxl_lang';

    /** @var string|null */
    private static $orig_uri = null;
    /** @var bool Language came from an explicit source (prefix or ?lang=). */
    private static $explicit = false;

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        self::detect();
        add_filter( 'locale', [ __CLASS__, 'locale' ], 1 );
        add_filter( 'do_parse_request', [ __CLASS__, 'strip_prefix' ], 1 );
        add_action( 'parse_request', [ __CLASS__, 'restore_uri' ], 1 );
        add_action( 'template_redirect', [ __CLASS__, 'redirect_to_object_language' ], 1 );
        add_action( 'wp', [ __CLASS__, 'remember' ] );
    }

    /** Non-default language slugs — the ones that carry a URL prefix. */
    public static function prefixed() {
        $def = ISXL_Model::default_slug();
        return array_values( array_filter( ISXL_Model::slugs(), function ( $s ) use ( $def ) { return $s !== $def; } ) );
    }

    public static function home_path() {
        return (string) wp_parse_url( get_option( 'home' ), PHP_URL_PATH );
    }

    public static function is_rest() {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return true;
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        return strpos( $uri, '/' . rest_get_url_prefix() . '/' ) !== false || strpos( $uri, 'rest_route=' ) !== false;
    }

    /** WooCommerce Store API (block cart and checkout). */
    public static function is_store_api() {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        return strpos( $uri, '/wc/store/' ) !== false || strpos( $uri, 'wc%2Fstore' ) !== false || strpos( $uri, 'wc/store/' ) !== false;
    }

    /** admin-ajax.php called from a front-end page (cart fragments, filters…). */
    public static function is_frontend_ajax() {
        if ( ! wp_doing_ajax() ) return false;
        $ref = wp_get_raw_referer();
        return $ref && strpos( $ref, admin_url() ) !== 0;
    }

    /** True where the visitor-facing language applies (not wp-admin screens). */
    public static function is_frontend_context() {
        if ( is_admin() && ! self::is_frontend_ajax() ) return false;
        if ( self::is_rest() && ! self::$explicit ) return false;
        return true;
    }

    public static function explicit() {
        return self::$explicit;
    }

    private static function detect() {
        // phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        list( $lang ) = ISXL_Url::parse( $uri, self::home_path(), self::prefixed() );

        if ( $lang === '' && isset( $_GET['lang'] ) && ISXL_Model::is_language( sanitize_key( $_GET['lang'] ) ) ) {
            $lang = sanitize_key( $_GET['lang'] );
        }
        if ( $lang !== '' ) {
            self::$explicit = true;
        } elseif ( isset( $_COOKIE[ self::COOKIE ] ) && ( self::is_frontend_ajax() || self::is_store_api() ) ) {
            $lang = sanitize_key( $_COOKIE[ self::COOKIE ] );
            // Store API (block cart/checkout) acts for the visitor: names, prices and the order language follow them.
            if ( self::is_store_api() && ISXL_Model::is_language( $lang ) ) self::$explicit = true;
        }
        // phpcs:enable
        if ( $lang === '' && ! is_admin() && ! self::is_rest() ) {
            $lang = ISXL_Model::default_slug();
            self::$explicit = true; // an unprefixed front-end URL *is* the default language
        }
        if ( $lang !== '' ) ISXL_Model::set_current( $lang );
    }

    public static function locale( $locale ) {
        if ( ! self::is_frontend_context() ) return $locale;
        $l = ISXL_Model::get_language( ISXL_Model::current() );
        return $l ? $l['locale'] : $locale;
    }

    public static function strip_prefix( $do ) {
        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) return $do;
        $uri = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        list( $lang, $rest ) = ISXL_Url::parse( $uri, self::home_path(), self::prefixed() );
        if ( $lang !== '' ) {
            self::$orig_uri         = $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            $_SERVER['REQUEST_URI'] = $rest;
        }
        return $do;
    }

    public static function restore_uri() {
        if ( self::$orig_uri !== null ) {
            $_SERVER['REQUEST_URI'] = self::$orig_uri;
            self::$orig_uri         = null;
        }
    }

    /**
     * A post or term opened under another language's prefix (e.g. a Thai post
     * at /en/slug/) goes to its own URL, so one piece of content has one URL.
     */
    public static function redirect_to_object_language() {
        if ( is_preview() || is_feed() || ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== $_SERVER['REQUEST_METHOD'] ) ) return;
        $obj  = get_queried_object();
        $lang = '';
        $url  = '';
        if ( $obj instanceof WP_Post && is_singular() && ISXL_Model::is_translated_post_type( $obj->post_type ) ) {
            $lang = ISXL_Model::get_post_lang( $obj->ID );
            $url  = get_permalink( $obj );
        } elseif ( $obj instanceof WP_Term && ( is_category() || is_tag() || is_tax() ) && ISXL_Model::is_translated_taxonomy( $obj->taxonomy ) ) {
            $lang = ISXL_Model::get_term_lang( $obj->term_id );
            $url  = get_term_link( $obj );
        }
        if ( $lang !== '' && $lang !== ISXL_Model::current() && is_string( $url ) && $url !== '' ) {
            wp_safe_redirect( $url, 301 );
            exit;
        }
    }

    /** Remember the visitor's language for later AJAX calls. */
    public static function remember() {
        if ( is_admin() || headers_sent() ) return;
        $cur = ISXL_Model::current();
        if ( ( $_COOKIE[ self::COOKIE ] ?? '' ) !== $cur ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            setcookie( self::COOKIE, $cur, [
                'expires'  => time() + YEAR_IN_SECONDS,
                'path'     => COOKIEPATH ?: '/',
                'domain'   => COOKIE_DOMAIN ?: '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ] );
        }
    }
}
