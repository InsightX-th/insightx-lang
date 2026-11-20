<?php
if ( ! defined( 'ABSPATH' ) && ! defined( 'ISXL_TESTING' ) ) exit;

/**
 * Pure URL helpers for the /{lang}/ directory prefix — no WordPress calls,
 * so tests/run.php can exercise them directly.
 */
class ISXL_Url {

    /**
     * Read the language prefix off a request URI.
     *
     * @param string   $uri       Request URI, e.g. "/sub/en/shop/?x=1".
     * @param string   $home_path Path of the site root, e.g. "/sub" or "".
     * @param string[] $prefixed  Slugs that use a prefix (non-default languages).
     * @return array{0:string,1:string} [lang slug or '', URI without the prefix]
     */
    public static function parse( $uri, $home_path, array $prefixed ) {
        $home_path = rtrim( (string) $home_path, '/' );
        $path      = (string) $uri;
        if ( $home_path !== '' ) {
            if ( strpos( $path, $home_path . '/' ) !== 0 && $path !== $home_path ) return [ '', $uri ];
            $path = substr( $path, strlen( $home_path ) );
        }
        foreach ( $prefixed as $slug ) {
            $p = '/' . $slug;
            if ( $path === $p || strpos( $path, $p . '/' ) === 0 || strpos( $path, $p . '?' ) === 0 ) {
                $rest = substr( $path, strlen( $p ) );
                if ( $rest === '' || $rest[0] === '?' ) $rest = '/' . $rest;
                return [ $slug, $home_path . $rest ];
            }
        }
        return [ '', $uri ];
    }

    /**
     * Point a site URL at a language: drop any language prefix, then add the
     * target one (none for the default language). Off-site URLs are returned as is.
     *
     * @param string   $url
     * @param string   $home     Raw site URL without trailing slash, e.g. "https://x.test/sub".
     * @param string[] $prefixed Slugs that use a prefix.
     * @param string   $lang     Target slug; '' or a slug not in $prefixed = no prefix.
     */
    public static function with_lang( $url, $home, array $prefixed, $lang ) {
        $home = rtrim( (string) $home, '/' );
        // Compare scheme-less so http/https variants of home both match.
        $strip = function ( $u ) { return preg_replace( '#^https?:#i', '', (string) $u ); };
        $u = $strip( $url );
        $h = $strip( $home );
        if ( $h === '' || stripos( $u, $h ) !== 0 ) return $url;
        $after = substr( $u, strlen( $h ) );
        if ( $after !== '' && ! in_array( $after[0], [ '/', '?', '#' ], true ) ) return $url; // e.g. x.test.evil

        $scheme = substr( (string) $url, 0, strlen( (string) $url ) - strlen( $u ) );
        foreach ( $prefixed as $slug ) {
            $p = '/' . $slug;
            if ( $after === $p || strpos( $after, $p . '/' ) === 0 || strpos( $after, $p . '?' ) === 0 || strpos( $after, $p . '#' ) === 0 ) {
                $after = substr( $after, strlen( $p ) );
                break;
            }
        }
        if ( in_array( $lang, $prefixed, true ) ) {
            $after = '/' . $lang . ( $after === '' || $after[0] !== '/' ? '/' . ltrim( $after, '/' ) : $after );
            if ( substr( $after, -2 ) === '//' ) $after = substr( $after, 0, -1 );
        }
        return $scheme . $h . $after;
    }
}
