<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Language prefix on generated URLs.
 *
 * - home_url() on the front end carries the *current* language, so archive,
 *   search, cart and theme links stay in the language being browsed.
 * - Permalinks of posts and terms carry the language of *that object*,
 *   wherever they are printed (admin "View" links included).
 * - The static front page / posts page map to the translation in the current
 *   language.
 */
class ISXL_Links {

    /** @var int|null page_on_front before mapping */
    private static $raw_front = null;

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_filter( 'home_url', [ __CLASS__, 'home_url' ], 10, 2 );
        add_filter( 'post_link', [ __CLASS__, 'post_link' ], 20, 2 );
        add_filter( 'page_link', [ __CLASS__, 'page_link' ], 20, 2 );
        add_filter( 'post_type_link', [ __CLASS__, 'post_link' ], 20, 2 );
        add_filter( 'term_link', [ __CLASS__, 'term_link' ], 20, 3 );
        add_filter( 'option_page_on_front', [ __CLASS__, 'front_page' ] );
        add_filter( 'option_page_for_posts', [ __CLASS__, 'posts_page' ] );
    }

    /** Raw site URL, never prefixed. */
    public static function raw_home() {
        return untrailingslashit( (string) get_option( 'home' ) );
    }

    /** Point any site URL at $lang. */
    public static function to_lang( $url, $lang ) {
        return ISXL_Url::with_lang( $url, self::raw_home(), ISXL_Router::prefixed(), $lang );
    }

    /** Home page URL of a language. */
    public static function lang_home( $lang ) {
        return self::to_lang( self::raw_home() . '/', $lang );
    }

    public static function home_url( $url, $path ) {
        if ( ! ISXL_Router::is_frontend_context() || wp_doing_cron() ) return $url;
        if ( ISXL_Router::is_rest() ) return $url;
        $p = ltrim( (string) $path, '/' );
        if ( $p !== '' && preg_match( '#^(wp-json|wp-sitemap|wp-content|wp-includes|wp-admin|xmlrpc\.php|wp-login\.php|robots\.txt|favicon\.ico)#', $p ) ) return $url;
        $cur = ISXL_Model::current();
        return ISXL_Model::is_default( $cur ) ? $url : self::to_lang( $url, $cur );
    }

    /** @param WP_Post|int $post */
    public static function post_link( $link, $post ) {
        $post = get_post( $post );
        if ( ! $post || ! ISXL_Model::is_translated_post_type( $post->post_type ) ) return $link;
        $lang = ISXL_Model::get_post_lang( $post->ID );
        return $lang === '' ? $link : self::to_lang( $link, $lang );
    }

    public static function page_link( $link, $post_id ) {
        $lang = ISXL_Model::get_post_lang( $post_id );
        if ( $lang === '' ) return $link;
        // Any translation of the static front page links to its language's home.
        if ( 'page' === get_option( 'show_on_front' ) ) {
            get_option( 'page_on_front' ); // fills $raw_front
            if ( self::$raw_front && in_array( (int) $post_id, ISXL_Model::translations( 'post', self::$raw_front ), true ) ) {
                return self::lang_home( $lang );
            }
        }
        return self::to_lang( $link, $lang );
    }

    public static function term_link( $link, $term, $taxonomy ) {
        if ( ! ISXL_Model::is_translated_taxonomy( $taxonomy ) ) return $link;
        $lang = ISXL_Model::get_term_lang( $term->term_id );
        return $lang === '' ? $link : self::to_lang( $link, $lang );
    }

    public static function front_page( $id ) {
        self::$raw_front = (int) $id;
        return self::map_page( $id );
    }

    public static function posts_page( $id ) {
        return self::map_page( $id );
    }

    private static function map_page( $id ) {
        if ( ! $id || ! ISXL_Router::is_frontend_context() ) return $id;
        $tr = ISXL_Model::get_translation( 'post', (int) $id, ISXL_Model::current() );
        return $tr ?: $id;
    }

    /**
     * URL of the current page in another language: the translation of the
     * queried object, else that language's home page.
     *
     * @return array{url:string,exists:bool}
     */
    public static function translation_url( $lang ) {
        $obj = get_queried_object();
        if ( is_front_page() || is_home() && ! is_paged() && 'posts' === get_option( 'show_on_front' ) ) {
            return [ 'url' => self::lang_home( $lang ), 'exists' => true ];
        }
        if ( $obj instanceof WP_Post && ISXL_Model::is_translated_post_type( $obj->post_type ) ) {
            $tr = ISXL_Model::get_translation( 'post', $obj->ID, $lang );
            if ( $tr && 'publish' === get_post_status( $tr ) ) return [ 'url' => get_permalink( $tr ), 'exists' => true ];
            return [ 'url' => self::lang_home( $lang ), 'exists' => false ];
        }
        if ( $obj instanceof WP_Term && ISXL_Model::is_translated_taxonomy( $obj->taxonomy ) ) {
            $tr = ISXL_Model::get_translation( 'term', $obj->term_id, $lang );
            if ( $tr ) {
                $u = get_term_link( (int) $tr, $obj->taxonomy );
                if ( is_string( $u ) ) return [ 'url' => $u, 'exists' => true ];
            }
            return [ 'url' => self::lang_home( $lang ), 'exists' => false ];
        }
        if ( is_post_type_archive() || is_search() || is_date() || is_author() ) {
            // Language-neutral lists: same path, other prefix.
            $scheme = is_ssl() ? 'https://' : 'http://';
            $host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
            $uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
            return [ 'url' => self::to_lang( $scheme . $host . $uri, $lang ), 'exists' => true ];
        }
        return [ 'url' => self::lang_home( $lang ), 'exists' => false ];
    }
}
