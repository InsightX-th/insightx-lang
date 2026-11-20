<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Keeps lists in one language: post queries get a tax_query on the language
 * taxonomy, term queries a meta_query on the term language.
 *
 * Language used, first match wins:
 *  1. explicit `lang` query arg ('all' = no filter) — also accepted on REST
 *  2. REST without `lang` → no filter (block editor sends `lang` itself)
 *  3. wp-admin → the admin bar language filter ('' = all)
 *  4. front end → current language
 *
 * Single-object lookups (p, page_id, name, pagename) are never filtered;
 * ISXL_Router redirects those to their own language instead.
 */
class ISXL_Query {

    public static function init() {
        add_action( 'pre_get_posts', [ __CLASS__, 'posts' ], 5 );
        add_filter( 'get_terms_args', [ __CLASS__, 'terms' ], 10, 2 );
        add_filter( 'wp_sitemaps_posts_query_args', [ __CLASS__, 'all_languages' ] );
        add_filter( 'wp_sitemaps_taxonomies_query_args', [ __CLASS__, 'all_languages' ] );
        add_action( 'rest_api_init', [ __CLASS__, 'rest_hooks' ] );
        // Adjacent post links (prev/next) stay in the post's language.
        foreach ( [ 'previous', 'next' ] as $dir ) {
            add_filter( "get_{$dir}_post_join", [ __CLASS__, 'adjacent_join' ], 10, 5 );
            add_filter( "get_{$dir}_post_where", [ __CLASS__, 'adjacent_where' ], 10, 5 );
        }
    }

    public static function all_languages( $args ) {
        $args['lang'] = 'all';
        return $args;
    }

    /** Language to filter by, or '' for none. */
    private static function lang_for( $explicit ) {
        if ( is_string( $explicit ) && $explicit !== '' ) {
            return ISXL_Model::is_language( $explicit ) ? $explicit : '';
        }
        if ( ISXL_Router::is_rest() ) return '';
        if ( is_admin() && ! ISXL_Router::is_frontend_ajax() ) return ISXL_Model::admin_lang();
        return ISXL_Model::current();
    }

    /** @param WP_Query $q */
    public static function posts( $q ) {
        if ( ! ISXL_Model::has_languages() ) return;
        $lang = self::lang_for( $q->get( 'lang' ) );
        if ( $lang === '' ) return;

        foreach ( [ 'p', 'page_id', 'name', 'pagename', 'attachment', 'attachment_id' ] as $single ) {
            if ( $q->get( $single ) ) return;
        }
        if ( $q->get( 'post__in' ) && ! $q->get( 's' ) ) return; // explicit id lists (related products, sliders) are already chosen

        if ( ! self::all_translated( $q ) ) return;

        $tax   = (array) $q->get( 'tax_query' );
        $tax[] = [ 'taxonomy' => ISXL_Model::TAX, 'field' => 'slug', 'terms' => $lang ];
        $q->set( 'tax_query', $tax );
    }

    /** Every post type the query can return is translated. */
    private static function all_translated( WP_Query $q ) {
        $pt = $q->get( 'post_type' );
        if ( $pt === 'any' ) {
            $pt = get_post_types( [ 'exclude_from_search' => false ] );
        } elseif ( empty( $pt ) ) {
            if ( $q->is_attachment() ) return false;
            if ( $q->is_tax() || $q->is_category() || $q->is_tag() ) {
                // Archive of a taxonomy: types are the ones it is attached to.
                $pt = [];
                foreach ( (array) $q->tax_query->queried_terms as $tax => $_ ) {
                    $t = get_taxonomy( $tax );
                    if ( $t ) $pt = array_merge( $pt, $t->object_type );
                }
                if ( ! $pt ) $pt = [ 'post' ];
            } elseif ( $q->is_search() ) {
                $pt = get_post_types( [ 'exclude_from_search' => false ] );
            } else {
                $pt = [ 'post' ];
            }
        }
        $pt = array_diff( (array) $pt, [ 'attachment' ] );
        if ( ! $pt ) return false;
        foreach ( $pt as $type ) {
            if ( ! ISXL_Model::is_translated_post_type( $type ) ) return false;
        }
        return true;
    }

    /**
     * @param array    $args
     * @param string[] $taxonomies
     */
    public static function terms( $args, $taxonomies ) {
        if ( ! ISXL_Model::has_languages() || empty( $taxonomies ) ) return $args;
        // Lookups for a specific object or term are not lists.
        foreach ( [ 'object_ids', 'include', 'slug', 'name', 'term_taxonomy_id', 'get' ] as $k ) {
            if ( ! empty( $args[ $k ] ) && ( $k !== 'get' || $args[ $k ] === 'all' ) ) return $args;
        }
        foreach ( (array) $taxonomies as $tax ) {
            if ( ! ISXL_Model::is_translated_taxonomy( $tax ) ) return $args;
        }
        $lang = self::lang_for( $args['lang'] ?? '' );
        if ( $lang === '' ) return $args;

        $mq   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : [];
        $mq[] = [ 'key' => ISXL_Model::TERM_LANG, 'value' => $lang ];
        $args['meta_query'] = $mq;
        return $args;
    }

    /** REST: honour ?lang= on post and term collections. */
    public static function rest_hooks() {
        foreach ( ISXL_Model::post_types() as $pt ) {
            add_filter( "rest_{$pt}_query", [ __CLASS__, 'rest_args' ], 10, 2 );
        }
        foreach ( ISXL_Model::taxonomies() as $tax ) {
            add_filter( "rest_{$tax}_query", [ __CLASS__, 'rest_args' ], 10, 2 );
        }
    }

    /**
     * @param array           $args
     * @param WP_REST_Request $req
     */
    public static function rest_args( $args, $req ) {
        $lang = sanitize_key( (string) $req->get_param( 'lang' ) );
        if ( $lang !== '' ) $args['lang'] = $lang;
        return $args;
    }

    public static function adjacent_join( $join, $in_same_term, $excluded, $taxonomy, $post ) {
        global $wpdb;
        if ( ! $post || ! ISXL_Model::is_translated_post_type( $post->post_type ) ) return $join;
        return $join . " INNER JOIN {$wpdb->term_relationships} AS isxl_tr ON p.ID = isxl_tr.object_id";
    }

    public static function adjacent_where( $where, $in_same_term, $excluded, $taxonomy, $post ) {
        global $wpdb;
        if ( ! $post || ! ISXL_Model::is_translated_post_type( $post->post_type ) ) return $where;
        $term = get_term_by( 'slug', ISXL_Model::get_post_lang( $post->ID ), ISXL_Model::TAX );
        if ( ! $term ) return $where;
        return $where . $wpdb->prepare( ' AND isxl_tr.term_taxonomy_id = %d', $term->term_taxonomy_id );
    }
}
