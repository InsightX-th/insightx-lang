<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Block editor:
 * - editor-blocks.js (every block editor: posts, widgets, site editor):
 *   "Language visibility" on every block + the Language switcher block.
 * - editor-panel.js (post editor only): the "Language" sidebar panel and a
 *   fetch middleware that adds ?lang= so Query Loop / Latest Posts previews
 *   show the post's own language.
 */
class ISXL_Editor {

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'assets' ] );
        add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
    }

    private static function languages_for_js() {
        $out = [];
        foreach ( ISXL_Model::languages() as $l ) {
            $out[] = [ 'slug' => $l['slug'], 'name' => $l['name'], 'flag' => ISXL_Model::flag_emoji( $l['flag'] ), 'isDefault' => $l['is_default'] ];
        }
        return $out;
    }

    public static function assets() {
        $deps = [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-hooks', 'wp-compose', 'wp-i18n', 'wp-server-side-render' ];
        wp_enqueue_script( 'isxl-editor-blocks', ISXL_URL . 'assets/js/editor-blocks.js', $deps, (string) filemtime( ISXL_DIR . 'assets/js/editor-blocks.js' ), true );
        wp_localize_script( 'isxl-editor-blocks', 'isxlBlocks', [ 'languages' => self::languages_for_js() ] );
        wp_set_script_translations( 'isxl-editor-blocks', 'insightx-lang', ISXL_DIR . 'languages' );

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->base !== 'post' || ! ISXL_Model::is_translated_post_type( $screen->post_type ) ) return;
        global $post;
        if ( ! $post instanceof WP_Post ) return;

        $own = ISXL_Model::get_post_lang( $post->ID );
        if ( $own === '' ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $own = isset( $_GET['new_lang'] ) ? sanitize_key( $_GET['new_lang'] ) : ( ISXL_Model::admin_lang() ?: ISXL_Model::default_slug() );
        }
        $translations = [];
        foreach ( ISXL_Model::languages() as $l ) {
            if ( $l['slug'] === $own ) continue;
            $tr = ISXL_Model::get_translation( 'post', $post->ID, $l['slug'] );
            $translations[ $l['slug'] ] = $tr ? [
                'id'     => $tr,
                'title'  => get_the_title( $tr ),
                'status' => get_post_status( $tr ),
                'edit'   => get_edit_post_link( $tr, 'raw' ),
            ] : [ 'add' => ISXL_Duplicator::url( $post->ID, $l['slug'] ) ];
        }

        $rest_bases = [];
        foreach ( ISXL_Model::post_types() as $pt ) {
            $o = get_post_type_object( $pt );
            if ( $o && $o->show_in_rest ) $rest_bases[] = $o->rest_base ?: $pt;
        }
        foreach ( ISXL_Model::taxonomies() as $tax ) {
            $o = get_taxonomy( $tax );
            if ( $o && $o->show_in_rest ) $rest_bases[] = $o->rest_base ?: $tax;
        }

        wp_enqueue_script( 'isxl-editor-panel', ISXL_URL . 'assets/js/editor-panel.js', [ 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n' ], (string) filemtime( ISXL_DIR . 'assets/js/editor-panel.js' ), true );
        wp_localize_script( 'isxl-editor-panel', 'isxlPanel', [
            'postId'       => $post->ID,
            'lang'         => $own,
            'languages'    => self::languages_for_js(),
            'translations' => (object) $translations,
            'restBases'    => array_values( array_unique( $rest_bases ) ),
            'isNew'        => 'auto-draft' === $post->post_status,
        ] );
        wp_set_script_translations( 'isxl-editor-panel', 'insightx-lang', ISXL_DIR . 'languages' );
    }

    public static function routes() {
        register_rest_route( 'isxl/v1', '/post-lang', [
            'methods'             => 'POST',
            'permission_callback' => function ( WP_REST_Request $r ) { return current_user_can( 'edit_post', (int) $r['id'] ); },
            'args'                => [
                'id'   => [ 'required' => true, 'type' => 'integer' ],
                'lang' => [ 'required' => true, 'type' => 'string' ],
            ],
            'callback'            => function ( WP_REST_Request $r ) {
                if ( ! class_exists( 'ISXL_Admin_Posts' ) ) require_once ISXL_DIR . 'includes/class-isxl-admin-posts.php';
                $res = ISXL_Admin_Posts::change_language( (int) $r['id'], sanitize_key( $r['lang'] ) );
                if ( is_wp_error( $res ) ) return new WP_Error( 'isxl_lang_taken', $res->get_error_message(), [ 'status' => 409 ] );
                return [ 'lang' => ISXL_Model::get_post_lang( (int) $r['id'] ) ];
            },
        ] );
    }
}
