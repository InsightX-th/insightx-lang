<?php
/**
 * WP-free checks for the pure helpers. Run: php tests/run.php
 */
define( 'ISXL_TESTING', true );
require __DIR__ . '/../includes/class-isxl-url.php';

$fail = 0;
function check( $name, $got, $want ) {
    global $fail;
    if ( $got === $want ) { echo "  ok  $name\n"; return; }
    $fail++;
    echo "FAIL  $name\n      got:  " . var_export( $got, true ) . "\n      want: " . var_export( $want, true ) . "\n";
}

$p = [ 'en', 'zh-cn' ];

echo "ISXL_Url::parse\n";
check( 'root default', ISXL_Url::parse( '/', '', $p ), [ '', '/' ] );
check( 'en root', ISXL_Url::parse( '/en/', '', $p ), [ 'en', '/' ] );
check( 'en no slash', ISXL_Url::parse( '/en', '', $p ), [ 'en', '/' ] );
check( 'en page', ISXL_Url::parse( '/en/shop/?a=1', '', $p ), [ 'en', '/shop/?a=1' ] );
check( 'en query only', ISXL_Url::parse( '/en?wc-ajax=x', '', $p ), [ 'en', '/?wc-ajax=x' ] );
check( 'slug starting with en', ISXL_Url::parse( '/engine/', '', $p ), [ '', '/engine/' ] );
check( 'subdir', ISXL_Url::parse( '/sub/en/a/', '/sub', $p ), [ 'en', '/sub/a/' ] );
check( 'subdir default', ISXL_Url::parse( '/sub/a/', '/sub', $p ), [ '', '/sub/a/' ] );
check( 'outside home', ISXL_Url::parse( '/other/en/', '/sub', $p ), [ '', '/other/en/' ] );
check( 'dashed slug', ISXL_Url::parse( '/zh-cn/x', '', $p ), [ 'zh-cn', '/x' ] );

echo "ISXL_Url::with_lang\n";
$h = 'https://ptn.local';
check( 'add en', ISXL_Url::with_lang( 'https://ptn.local/shop/', $h, $p, 'en' ), 'https://ptn.local/en/shop/' );
check( 'add en home', ISXL_Url::with_lang( 'https://ptn.local/', $h, $p, 'en' ), 'https://ptn.local/en/' );
check( 'add en bare home', ISXL_Url::with_lang( 'https://ptn.local', $h, $p, 'en' ), 'https://ptn.local/en/' );
check( 'add en query', ISXL_Url::with_lang( 'https://ptn.local/?s=a', $h, $p, 'en' ), 'https://ptn.local/en/?s=a' );
check( 'swap en->default', ISXL_Url::with_lang( 'https://ptn.local/en/shop/', $h, $p, 'th' ), 'https://ptn.local/shop/' );
check( 'swap en->zh', ISXL_Url::with_lang( 'https://ptn.local/en/shop/', $h, $p, 'zh-cn' ), 'https://ptn.local/zh-cn/shop/' );
check( 'idempotent', ISXL_Url::with_lang( 'https://ptn.local/en/shop/', $h, $p, 'en' ), 'https://ptn.local/en/shop/' );
check( 'http variant', ISXL_Url::with_lang( 'http://ptn.local/a/', $h, $p, 'en' ), 'http://ptn.local/en/a/' );
check( 'offsite untouched', ISXL_Url::with_lang( 'https://example.com/a/', $h, $p, 'en' ), 'https://example.com/a/' );
check( 'lookalike host untouched', ISXL_Url::with_lang( 'https://ptn.local.evil/a', $h, $p, 'en' ), 'https://ptn.local.evil/a' );
check( 'slug-like page kept', ISXL_Url::with_lang( 'https://ptn.local/engine/', $h, $p, 'th' ), 'https://ptn.local/engine/' );
check( 'subdir', ISXL_Url::with_lang( 'https://x.test/sub/a/', 'https://x.test/sub', $p, 'en' ), 'https://x.test/sub/en/a/' );

echo $fail ? "\n$fail failed\n" : "\nall passed\n";
exit( $fail ? 1 : 0 );
