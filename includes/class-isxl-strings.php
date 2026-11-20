<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Text that lives outside posts: site title, tagline, date formats, widget
 * titles, theme texts (theme mods picked in settings) and anything code
 * registers with isxl_register_string().
 *
 * The source text is the default language; translations are stored per
 * language under md5(source) in the `isxl_strings` option, so a filter only
 * needs the raw value to find its translation.
 */
class ISXL_Strings {

    const OPT = 'isxl_strings';

    /** @var array<string,array{string:string,name:string,group:string,multiline:bool}> */
    private static $reg = [];
    /** @var array<string,array<string,string>>|null */
    private static $tr = null;

    public static function init() {
        if ( ! ISXL_Model::has_languages() ) return;
        add_action( 'init', [ __CLASS__, 'hook_filters' ], 20 );
    }

    public static function hook_filters() {
        foreach ( [ 'blogname', 'blogdescription', 'date_format', 'time_format' ] as $opt ) {
            add_filter( 'option_' . $opt, [ __CLASS__, 'filter' ], 20 );
        }
        add_filter( 'widget_title', [ __CLASS__, 'filter' ], 20 );
        foreach ( (array) ISXL_Model::settings()['theme_mods'] as $mod ) {
            add_filter( 'theme_mod_' . sanitize_key( $mod ), [ __CLASS__, 'filter' ], 20 );
        }
    }

    public static function register( $string, $name = '', $group = 'Custom', $multiline = false ) {
        $string = (string) $string;
        if ( trim( $string ) === '' ) return;
        $key = md5( $string );
        if ( isset( self::$reg[ $key ] ) ) return;
        self::$reg[ $key ] = [
            'string'    => $string,
            'name'      => $name !== '' ? (string) $name : wp_trim_words( $string, 6 ),
            'group'     => (string) $group,
            'multiline' => (bool) $multiline || strpos( $string, "\n" ) !== false,
        ];
    }

    /** @return array<string,array<string,string>> */
    private static function translations() {
        if ( self::$tr === null ) {
            $v        = get_option( self::OPT, [] );
            self::$tr = is_array( $v ) ? $v : [];
        }
        return self::$tr;
    }

    public static function translate( $string, $lang = null ) {
        if ( ! is_string( $string ) || $string === '' ) return $string;
        $lang = $lang ?? ISXL_Model::current();
        if ( ISXL_Model::is_default( $lang ) ) return $string;
        $t = self::translations()[ $lang ][ md5( $string ) ] ?? '';
        return $t !== '' ? $t : $string;
    }

    /** Filter callback: translate only for visitors. */
    public static function filter( $value ) {
        if ( ! is_string( $value ) || ! ISXL_Router::is_frontend_context() ) return $value;
        return self::translate( $value );
    }

    /**
     * Everything translatable, for the Strings tab. Built-ins are read raw
     * here (admin context, so the filters above leave them alone).
     *
     * @return array<string,array{string:string,name:string,group:string,multiline:bool}>
     */
    public static function all() {
        self::register( get_option( 'blogname' ), __( 'Site Title' ), 'WordPress' );
        self::register( get_option( 'blogdescription' ), __( 'Tagline' ), 'WordPress' );
        self::register( get_option( 'date_format' ), __( 'Date Format' ), 'WordPress' );
        self::register( get_option( 'time_format' ), __( 'Time Format' ), 'WordPress' );

        global $wp_registered_widgets;
        foreach ( (array) $wp_registered_widgets as $w ) {
            $cb = $w['callback'][0] ?? null;
            if ( ! $cb instanceof WP_Widget ) continue;
            $settings = $cb->get_settings();
            $inst     = $settings[ $w['params'][0]['number'] ?? 0 ] ?? [];
            if ( ! empty( $inst['title'] ) ) self::register( $inst['title'], $cb->name, __( 'Widgets', 'insightx-lang' ) );
        }

        foreach ( (array) ISXL_Model::settings()['theme_mods'] as $mod ) {
            $v = get_theme_mod( $mod );
            if ( is_string( $v ) ) self::register( $v, $mod, __( 'Theme', 'insightx-lang' ) );
        }

        do_action( 'isxl_register_strings' );
        return self::$reg;
    }

    /**
     * Theme mods that look like text a visitor reads — candidates for the
     * "Theme texts" picker. Skips URLs, colors, numbers and ids.
     *
     * @return array<string,string> name => value
     */
    public static function theme_text_mods() {
        $out = [];
        foreach ( (array) get_theme_mods() as $k => $v ) {
            if ( ! is_string( $v ) || strlen( $v ) < 2 || strlen( $v ) > 5000 ) continue;
            if ( preg_match( '#^(https?:)?//#i', $v ) || preg_match( '/^#?[0-9a-f]{3,8}$/i', $v ) || is_numeric( $v ) ) continue;
            if ( ! preg_match( '/\p{L}{2}/u', wp_strip_all_tags( $v ) ) ) continue;
            if ( in_array( $v, [ 'on', 'off', 'yes', 'no', 'true', 'false', 'left', 'right', 'center' ], true ) ) continue;
            $out[ (string) $k ] = $v;
        }
        return $out;
    }

    /** @param array<string,array<string,string>> $posted lang => [md5 => text] */
    public static function save( array $posted ) {
        $tr = self::translations();
        foreach ( $posted as $lang => $rows ) {
            $lang = sanitize_key( $lang );
            if ( ! ISXL_Model::is_language( $lang ) || ISXL_Model::is_default( $lang ) || ! is_array( $rows ) ) continue;
            foreach ( $rows as $key => $text ) {
                if ( ! preg_match( '/^[a-f0-9]{32}$/', (string) $key ) ) continue;
                $text = wp_kses_post( wp_unslash( (string) $text ) );
                if ( $text === '' ) {
                    unset( $tr[ $lang ][ $key ] );
                } else {
                    $tr[ $lang ][ $key ] = $text;
                }
            }
        }
        update_option( self::OPT, $tr );
        self::$tr = $tr;
    }

    public static function get( $key, $lang ) {
        return self::translations()[ $lang ][ $key ] ?? '';
    }
}
