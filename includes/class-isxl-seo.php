<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * hreflang alternates. <html lang> already follows the language through the
 * `locale` filter, and core sitemaps list every language because their
 * queries run with lang=all and each permalink carries its own prefix.
 */
class ISXL_SEO {

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_action( 'wp_head', [ __CLASS__, 'hreflang' ], 1 );
    }

    public static function hreflang() {
        if ( is_404() || is_search() || is_paged() ) return;
        $links = [];
        foreach ( ISXL_Model::languages() as $l ) {
            $t = ISXL_Links::translation_url( $l['slug'] );
            if ( ! $t['exists'] ) continue;
            $links[ str_replace( '_', '-', $l['locale'] ) ] = $t['url'];
            if ( $l['is_default'] ) $links['x-default'] = $t['url'];
        }
        if ( count( $links ) < 3 ) return; // one language + x-default: nothing to point at
        foreach ( $links as $hl => $url ) {
            printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $hl ), esc_url( $url ) );
        }
    }
}
