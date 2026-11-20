<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * One menu per language: every theme location gets a twin per extra
 * language ("primary___en"); on the front end the twin replaces the original.
 * A location left empty for a language falls back to the default menu.
 */
class ISXL_Menus {

    const SEP = '___';

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_action( 'after_setup_theme', [ __CLASS__, 'register_locations' ], 999 );
        add_filter( 'theme_mod_nav_menu_locations', [ __CLASS__, 'swap_locations' ], 20 );
    }

    public static function register_locations() {
        $extra = [];
        foreach ( get_registered_nav_menus() as $loc => $label ) {
            if ( strpos( $loc, self::SEP ) !== false ) continue;
            foreach ( ISXL_Router::prefixed() as $slug ) {
                $l = ISXL_Model::get_language( $slug );
                $extra[ $loc . self::SEP . $slug ] = $label . ' (' . $l['name'] . ')';
            }
        }
        if ( $extra ) register_nav_menus( $extra );
    }

    /** @param array<string,int>|false $locs */
    public static function swap_locations( $locs ) {
        if ( ! is_array( $locs ) || is_admin() || ! ISXL_Router::is_frontend_context() ) return $locs;
        $cur = ISXL_Model::current();
        if ( ISXL_Model::is_default( $cur ) ) return $locs;
        foreach ( $locs as $loc => $menu ) {
            if ( strpos( $loc, self::SEP ) !== false ) continue;
            if ( ! empty( $locs[ $loc . self::SEP . $cur ] ) ) $locs[ $loc ] = $locs[ $loc . self::SEP . $cur ];
        }
        return $locs;
    }
}
