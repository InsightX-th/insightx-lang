<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Show a widget — or any block — in one language only.
 *
 * - Legacy widgets: a "Language" select in the widget form.
 * - Blocks (block widgets, posts, templates): an `isxlLang` attribute set from
 *   the block sidebar (assets/js/editor.js), applied in render_block.
 */
class ISXL_Widgets {

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_action( 'in_widget_form', [ __CLASS__, 'form' ], 10, 3 );
        add_filter( 'widget_update_callback', [ __CLASS__, 'update' ], 10, 2 );
        add_filter( 'widget_display_callback', [ __CLASS__, 'display' ], 10, 1 );
        add_filter( 'register_block_type_args', [ __CLASS__, 'block_attribute' ] );
        add_filter( 'render_block', [ __CLASS__, 'render_block' ], 10, 2 );
    }

    /** @param WP_Widget $widget */
    public static function form( $widget, $return, $instance ) {
        $cur = $instance['isxl_lang'] ?? '';
        $id  = $widget->get_field_id( 'isxl_lang' );
        echo '<p><label for="' . esc_attr( $id ) . '">' . esc_html__( 'Show in language:', 'insightx-lang' ) . '</label> ';
        echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $widget->get_field_name( 'isxl_lang' ) ) . '">';
        echo '<option value="">' . esc_html__( 'All languages', 'insightx-lang' ) . '</option>';
        foreach ( ISXL_Model::languages() as $l ) {
            echo '<option value="' . esc_attr( $l['slug'] ) . '"' . selected( $cur, $l['slug'], false ) . '>' . esc_html( $l['name'] ) . '</option>';
        }
        echo '</select></p>';
    }

    public static function update( $instance, $new ) {
        $l = sanitize_key( $new['isxl_lang'] ?? '' );
        if ( ISXL_Model::is_language( $l ) ) {
            $instance['isxl_lang'] = $l;
        } else {
            unset( $instance['isxl_lang'] );
        }
        return $instance;
    }

    public static function display( $instance ) {
        if ( is_array( $instance ) && ! empty( $instance['isxl_lang'] ) && ! is_admin() && $instance['isxl_lang'] !== ISXL_Model::current() ) {
            return false;
        }
        return $instance;
    }

    /** Server-rendered blocks validate attributes; declare ours on every block. */
    public static function block_attribute( $args ) {
        $args['attributes']             = $args['attributes'] ?? [];
        $args['attributes']['isxlLang'] = [ 'type' => 'string', 'default' => '' ];
        return $args;
    }

    public static function render_block( $html, $block ) {
        $l = $block['attrs']['isxlLang'] ?? '';
        if ( $l !== '' && ! is_admin() && ! ISXL_Router::is_rest() && $l !== ISXL_Model::current() ) return '';
        return $html;
    }
}
