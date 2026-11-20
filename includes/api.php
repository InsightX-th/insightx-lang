<?php
/**
 * Public API for themes and plugins.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Current language slug, e.g. "th". */
function isxl_current_lang() {
    return ISXL_Model::current();
}

/** Default language slug. */
function isxl_default_lang() {
    return ISXL_Model::default_slug();
}

/** @return array<string,array<string,mixed>> All languages keyed by slug. */
function isxl_languages() {
    return ISXL_Model::languages();
}

/** Language of a post ('' when none). */
function isxl_get_post_language( $post_id ) {
    return ISXL_Model::get_post_lang( $post_id );
}

/** Translation of a post in $lang (current language by default); 0 when none. */
function isxl_get_post( $post_id, $lang = null ) {
    return ISXL_Model::get_translation( 'post', $post_id, $lang ?? ISXL_Model::current() );
}

/** Translation of a term in $lang (current language by default); 0 when none. */
function isxl_get_term( $term_id, $lang = null ) {
    return ISXL_Model::get_translation( 'term', $term_id, $lang ?? ISXL_Model::current() );
}

/** @return array<string,int> lang => post id */
function isxl_get_post_translations( $post_id ) {
    return ISXL_Model::translations( 'post', $post_id );
}

/** Home URL of a language (current by default). */
function isxl_home_url( $lang = null ) {
    return ISXL_Links::lang_home( $lang ?? ISXL_Model::current() );
}

/**
 * Make a string translatable in InsightX Lang → Strings. Call on every
 * request (e.g. on `init`), with the default-language text.
 */
function isxl_register_string( $string, $name = '', $group = 'Custom', $multiline = false ) {
    ISXL_Strings::register( $string, $name, $group, $multiline );
}

/** Translated string for the current language (the original when none). */
function isxl__( $string ) {
    return ISXL_Strings::translate( $string );
}

/** Echo isxl__() escaped for HTML. */
function isxl_e( $string ) {
    echo esc_html( ISXL_Strings::translate( $string ) );
}

/**
 * Language switcher data.
 *
 * @return array<int,array{slug:string,name:string,locale:string,flag:string,url:string,current:bool,exists:bool}>
 */
function isxl_the_languages() {
    return ISXL_Switcher::items();
}
