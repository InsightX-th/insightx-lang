<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Term screens: language field on add/edit, translations row, and one
 * column per language in the term list.
 */
class ISXL_Admin_Terms {

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_action( 'admin_init', [ __CLASS__, 'hooks' ] );
    }

    public static function hooks() {
        foreach ( ISXL_Model::taxonomies() as $tax ) {
            add_action( "{$tax}_add_form_fields", [ __CLASS__, 'add_field' ] );
            add_action( "{$tax}_edit_form_fields", [ __CLASS__, 'edit_field' ], 10, 2 );
            add_action( "edited_{$tax}", [ __CLASS__, 'save' ] );
            add_filter( "manage_edit-{$tax}_columns", [ __CLASS__, 'add_columns' ] );
            add_filter( "manage_{$tax}_custom_column", [ __CLASS__, 'column' ], 10, 3 );
        }
    }

    private static function select( $current ) {
        $h = '<select name="isxl_lang" id="isxl_lang">';
        foreach ( ISXL_Model::languages() as $l ) {
            $h .= '<option value="' . esc_attr( $l['slug'] ) . '"' . selected( $current, $l['slug'], false ) . '>' . esc_html( trim( ISXL_Model::flag_emoji( $l['flag'] ) . ' ' . $l['name'] ) ) . '</option>';
        }
        return $h . '</select>';
    }

    public static function add_field() {
        echo '<div class="form-field"><label for="isxl_lang">' . esc_html__( 'Language', 'insightx-lang' ) . '</label>'
            . self::select( ISXL_Model::admin_lang() ?: ISXL_Model::default_slug() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /** @param WP_Term $term */
    public static function edit_field( $term, $tax ) {
        $own = ISXL_Model::get_term_lang( $term->term_id );
        wp_nonce_field( 'isxl_term_lang', 'isxl_term_lang_nonce' );
        echo '<tr class="form-field"><th scope="row"><label for="isxl_lang">' . esc_html__( 'Language', 'insightx-lang' ) . '</label></th><td>' . self::select( $own ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
        echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Translations', 'insightx-lang' ) . '</th><td>';
        foreach ( ISXL_Model::languages() as $l ) {
            if ( $l['slug'] === $own ) continue;
            echo '<p>' . esc_html( trim( ISXL_Model::flag_emoji( $l['flag'] ) . ' ' . $l['name'] ) ) . ': ' . self::cell( $term->term_id, $tax, $l['slug'], true ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        echo '</td></tr>';
    }

    public static function save( $term_id ) {
        if ( ! isset( $_POST['isxl_term_lang_nonce'], $_POST['isxl_lang'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_key( $_POST['isxl_term_lang_nonce'] ), 'isxl_term_lang' ) ) return;
        $lang  = sanitize_key( wp_unslash( $_POST['isxl_lang'] ) );
        $taken = ISXL_Model::get_translation( 'term', $term_id, $lang );
        if ( ISXL_Model::is_language( $lang ) && ( ! $taken || $taken === (int) $term_id ) ) {
            ISXL_Model::set_term_lang( $term_id, $lang );
        }
    }

    public static function add_columns( $cols ) {
        $out = [];
        foreach ( $cols as $k => $v ) {
            $out[ $k ] = $v;
            if ( $k === 'name' ) {
                foreach ( ISXL_Model::languages() as $l ) {
                    $out[ 'isxl_' . $l['slug'] ] = esc_html( ISXL_Model::flag_emoji( $l['flag'] ) ?: strtoupper( $l['slug'] ) );
                }
            }
        }
        return $out;
    }

    public static function column( $out, $col, $term_id ) {
        if ( strpos( $col, 'isxl_' ) !== 0 ) return $out;
        $tax = isset( $_REQUEST['taxonomy'] ) ? sanitize_key( wp_unslash( $_REQUEST['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        return self::cell( $term_id, $tax, substr( $col, 5 ), false );
    }

    private static function cell( $term_id, $tax, $lang, $verbose ) {
        $own = ISXL_Model::get_term_lang( $term_id );
        if ( $own === $lang ) return '<span class="isxl-cell is-self">✓</span>';
        $tr = ISXL_Model::get_translation( 'term', $term_id, $lang );
        if ( $tr ) {
            $t = get_term( $tr, $tax );
            return '<a class="isxl-cell is-tr" href="' . esc_url( (string) get_edit_term_link( $tr, $tax ) ) . '">' . ( $verbose && $t && ! is_wp_error( $t ) ? esc_html( $t->name ) : '✎' ) . '</a>';
        }
        if ( $own === '' ) return '—';
        return '<a class="isxl-cell is-add" href="' . esc_url( ISXL_Duplicator::term_url( $term_id, $tax, $lang ) ) . '">' . ( $verbose ? esc_html__( '+ Add', 'insightx-lang' ) : '+' ) . '</a>';
    }
}
