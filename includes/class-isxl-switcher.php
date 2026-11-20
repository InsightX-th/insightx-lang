<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Language switcher, three ways in:
 * - shortcode [isxl_switcher style="list|dropdown" flags="1" names="1" hide_current="0"]
 * - block isxl/switcher (registered in assets/js/editor.js, rendered here)
 * - menu: a Custom Link with URL "#isxl-switcher" expands into one item per language
 */
class ISXL_Switcher {

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_shortcode( 'isxl_switcher', [ __CLASS__, 'render' ] );
        add_action( 'init', [ __CLASS__, 'register_block' ] );
        add_filter( 'wp_nav_menu_objects', [ __CLASS__, 'menu_items' ], 10 );
        add_action( 'wp_head', [ __CLASS__, 'style' ] );
    }

    /** @return array<int,array{slug:string,name:string,locale:string,flag:string,url:string,current:bool,exists:bool}> */
    public static function items() {
        $out = [];
        $cur = ISXL_Model::current();
        foreach ( ISXL_Model::languages() as $l ) {
            $t     = ISXL_Links::translation_url( $l['slug'] );
            $out[] = [
                'slug'    => $l['slug'],
                'name'    => $l['name'],
                'locale'  => $l['locale'],
                'flag'    => ISXL_Model::flag_emoji( $l['flag'] ),
                'url'     => $l['slug'] === $cur ? ( $t['exists'] ? $t['url'] : ISXL_Links::lang_home( $cur ) ) : $t['url'],
                'current' => $l['slug'] === $cur,
                'exists'  => $t['exists'],
            ];
        }
        return $out;
    }

    /** @param array<string,string>|string $atts */
    public static function render( $atts = [] ) {
        $a = shortcode_atts( [ 'style' => 'list', 'flags' => '1', 'names' => '1', 'hide_current' => '0' ], $atts, 'isxl_switcher' );
        $items = self::items();
        if ( $a['hide_current'] === '1' ) $items = array_filter( $items, function ( $i ) { return ! $i['current']; } );
        $label = function ( $i ) use ( $a ) {
            $parts = [];
            if ( $a['flags'] === '1' && $i['flag'] !== '' ) $parts[] = '<span class="isxl-flag" aria-hidden="true">' . $i['flag'] . '</span>';
            if ( $a['names'] === '1' || $i['flag'] === '' ) $parts[] = '<span class="isxl-name">' . esc_html( $i['name'] ) . '</span>';
            return implode( ' ', $parts );
        };

        if ( $a['style'] === 'dropdown' ) {
            $h = '<select class="isxl-switcher isxl-switcher-dropdown" aria-label="' . esc_attr__( 'Language', 'insightx-lang' ) . '" onchange="if(this.value)location.href=this.value">';
            foreach ( $items as $i ) {
                $h .= '<option value="' . esc_url( $i['url'] ) . '" lang="' . esc_attr( str_replace( '_', '-', $i['locale'] ) ) . '"' . selected( $i['current'], true, false ) . '>' . esc_html( trim( $i['flag'] . ' ' . $i['name'] ) ) . '</option>';
            }
            return $h . '</select>';
        }

        $h = '<ul class="isxl-switcher">';
        foreach ( $items as $i ) {
            $h .= '<li class="isxl-lang-' . esc_attr( $i['slug'] ) . ( $i['current'] ? ' is-current' : '' ) . '">'
                . '<a href="' . esc_url( $i['url'] ) . '" hreflang="' . esc_attr( str_replace( '_', '-', $i['locale'] ) ) . '" lang="' . esc_attr( str_replace( '_', '-', $i['locale'] ) ) . '"'
                . ( $i['current'] ? ' aria-current="true"' : '' ) . '>' . $label( $i ) . '</a></li>';
        }
        return $h . '</ul>';
    }

    public static function register_block() {
        register_block_type( 'isxl/switcher', [
            'api_version'     => 3,
            'attributes'      => [
                'style'       => [ 'type' => 'string', 'default' => 'list' ],
                'flags'       => [ 'type' => 'boolean', 'default' => true ],
                'names'       => [ 'type' => 'boolean', 'default' => true ],
                'hideCurrent' => [ 'type' => 'boolean', 'default' => false ],
            ],
            'render_callback' => function ( $attrs ) {
                return self::render( [
                    'style'        => $attrs['style'] ?? 'list',
                    'flags'        => ! empty( $attrs['flags'] ) ? '1' : '0',
                    'names'        => ! empty( $attrs['names'] ) ? '1' : '0',
                    'hide_current' => ! empty( $attrs['hideCurrent'] ) ? '1' : '0',
                ] );
            },
        ] );
    }

    /**
     * Expand "#isxl-switcher" custom links into language items.
     *
     * @param WP_Post[] $items
     */
    public static function menu_items( $items ) {
        $out = [];
        foreach ( $items as $item ) {
            if ( ( $item->url ?? '' ) !== '#isxl-switcher' ) {
                $out[] = $item;
                continue;
            }
            foreach ( self::items() as $n => $l ) {
                $clone          = clone $item;
                $clone->ID      = $item->ID . '-' . $l['slug'];
                $clone->db_id   = 0;
                $clone->title   = trim( $l['flag'] . ' ' . $l['name'] );
                $clone->url     = $l['url'];
                $clone->classes = array_merge( (array) $item->classes, [ 'isxl-lang-item', 'isxl-lang-' . $l['slug'] ], $l['current'] ? [ 'current-lang' ] : [] );
                $clone->xfn     = 'alternate';
                $clone->menu_order = $item->menu_order + $n / 10;
                $out[] = $clone;
            }
        }
        return $out;
    }

    public static function style() {
        echo "<style id=\"isxl-switcher-css\">.isxl-switcher{list-style:none;margin:0;padding:0;display:flex;gap:.75em;flex-wrap:wrap}.isxl-switcher a{text-decoration:none}.isxl-switcher .is-current a{font-weight:700}</style>\n";
    }
}
