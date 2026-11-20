<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Post lists: one column per language (✓ this item · ✎ translation · + add).
 * Classic editor: a "Language" meta box. (The block editor uses the sidebar
 * panel from ISXL_Editor.)
 */
class ISXL_Admin_Posts {

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_action( 'admin_init', [ __CLASS__, 'columns' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'meta_box' ], 10, 2 );
        add_action( 'save_post', [ __CLASS__, 'save' ], 10, 2 );
        add_action( 'admin_head-edit.php', [ __CLASS__, 'column_css' ] );
        add_action( 'admin_head-edit-tags.php', [ __CLASS__, 'column_css' ] );
    }

    public static function columns() {
        foreach ( ISXL_Model::post_types() as $pt ) {
            add_filter( "manage_{$pt}_posts_columns", [ __CLASS__, 'add_columns' ], 100 );
            add_action( "manage_{$pt}_posts_custom_column", [ __CLASS__, 'column' ], 10, 2 );
        }
    }

    public static function add_columns( $cols ) {
        $out = [];
        foreach ( $cols as $k => $v ) {
            $out[ $k ] = $v;
            if ( $k === 'title' || $k === 'name' ) {
                foreach ( ISXL_Model::languages() as $l ) {
                    $label = ISXL_Model::flag_emoji( $l['flag'] ) ?: strtoupper( $l['slug'] );
                    $out[ 'isxl_' . $l['slug'] ] = '<span title="' . esc_attr( $l['name'] ) . '">' . esc_html( $label ) . '</span>';
                }
            }
        }
        return $out;
    }

    public static function column( $col, $post_id ) {
        if ( strpos( $col, 'isxl_' ) !== 0 ) return;
        $lang = substr( $col, 5 );
        echo self::cell( $post_id, $lang ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in cell()
    }

    /** ✓ / ✎ / + for one language of one post. */
    public static function cell( $post_id, $lang ) {
        $own = ISXL_Model::get_post_lang( $post_id );
        $l   = ISXL_Model::get_language( $lang );
        if ( $own === $lang ) {
            return '<span class="isxl-cell is-self" title="' . esc_attr( $l['name'] ) . '">✓</span>';
        }
        $tr = ISXL_Model::get_translation( 'post', $post_id, $lang );
        if ( $tr ) {
            $link = get_edit_post_link( $tr );
            /* translators: 1: language, 2: post title */
            $t = sprintf( __( 'Edit the %1$s translation: %2$s', 'insightx-lang' ), $l['name'], get_the_title( $tr ) );
            return $link ? '<a class="isxl-cell is-tr" href="' . esc_url( $link ) . '" title="' . esc_attr( $t ) . '">✎</a>' : '✎';
        }
        if ( $own === '' || ! current_user_can( 'edit_post', $post_id ) ) return '<span class="isxl-cell">—</span>';
        /* translators: %s: language */
        $t = sprintf( __( 'Add a %s translation', 'insightx-lang' ), $l['name'] );
        return '<a class="isxl-cell is-add" href="' . esc_url( ISXL_Duplicator::url( $post_id, $lang ) ) . '" title="' . esc_attr( $t ) . '">+</a>';
    }

    public static function column_css() {
        echo '<style>.column-' . implode( ',.column-', array_map( function ( $s ) { return 'isxl_' . esc_attr( $s ); }, ISXL_Model::slugs() ) )
            . '{width:2.4em;text-align:center!important}.isxl-cell{display:inline-block;min-width:1.6em;font-weight:600;text-decoration:none}.isxl-cell.is-self{color:#10b981}.isxl-cell.is-add{color:#0079ff;font-size:16px}</style>';
    }

    public static function meta_box( $post_type, $post ) {
        if ( ! ISXL_Model::is_translated_post_type( $post_type ) ) return;
        if ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) return;
        add_meta_box( 'isxl-lang', __( 'Language', 'insightx-lang' ), [ __CLASS__, 'render_box' ], $post_type, 'side', 'high' );
    }

    /** @param WP_Post $post */
    public static function render_box( $post ) {
        wp_nonce_field( 'isxl_post_lang', 'isxl_post_lang_nonce' );
        $own = ISXL_Model::get_post_lang( $post->ID );
        if ( $own === '' ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $own = isset( $_GET['new_lang'] ) ? sanitize_key( $_GET['new_lang'] ) : ( ISXL_Model::admin_lang() ?: ISXL_Model::default_slug() );
        }
        echo '<p><select name="isxl_lang" style="width:100%">';
        foreach ( ISXL_Model::languages() as $l ) {
            echo '<option value="' . esc_attr( $l['slug'] ) . '"' . selected( $own, $l['slug'], false ) . '>' . esc_html( trim( ISXL_Model::flag_emoji( $l['flag'] ) . ' ' . $l['name'] ) ) . '</option>';
        }
        echo '</select></p>';
        if ( 'auto-draft' === $post->post_status ) return;
        echo '<table style="width:100%">';
        foreach ( ISXL_Model::languages() as $l ) {
            if ( $l['slug'] === $own ) continue;
            echo '<tr><td>' . esc_html( trim( ISXL_Model::flag_emoji( $l['flag'] ) . ' ' . $l['name'] ) ) . '</td><td style="text-align:right">' . self::cell( $post->ID, $l['slug'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        echo '</table>';
    }

    public static function save( $post_id, $post ) {
        if ( ! isset( $_POST['isxl_post_lang_nonce'], $_POST['isxl_lang'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_key( $_POST['isxl_post_lang_nonce'] ), 'isxl_post_lang' ) ) return;
        if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) return;
        self::change_language( $post_id, sanitize_key( wp_unslash( $_POST['isxl_lang'] ) ) );
    }

    /**
     * Move a post to another language. Refused when a translation in that
     * language already exists — the group would hold two of one language.
     *
     * @return true|WP_Error
     */
    public static function change_language( $post_id, $lang ) {
        if ( ! ISXL_Model::is_language( $lang ) ) return new WP_Error( 'isxl', __( 'Unknown language.', 'insightx-lang' ) );
        if ( ISXL_Model::get_post_lang( $post_id ) === $lang ) return true;
        $taken = ISXL_Model::get_translation( 'post', $post_id, $lang );
        if ( $taken && $taken !== (int) $post_id ) {
            return new WP_Error( 'isxl', __( 'Another translation already uses this language.', 'insightx-lang' ) );
        }
        ISXL_Model::set_post_lang( $post_id, $lang );
        return true;
    }
}
