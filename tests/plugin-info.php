<?php
/** WordPress-free smoke test for the plugin details modal metadata. */
define( 'ABSPATH', __DIR__ );
define( 'ISXL_DIR', dirname( __DIR__ ) . '/' );
define( 'ISXL_URL', 'https://example.test/wp-content/plugins/insightx-lang/' );
$plugin_file = file_get_contents( ISXL_DIR . 'insightx-lang.php' );
if ( $plugin_file === false || ! preg_match( '/^ \* Version:\s*([0-9.]+)/m', $plugin_file, $version ) ) exit( 1 );
define( 'ISXL_VERSION', $version[1] );

function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { return true; }
function determine_locale() { return 'th_TH'; }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }

require ISXL_DIR . 'includes/class-isxl-plugin-info.php';

function ensure( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); }
}

$args = (object) [ 'slug' => 'insightx-lang' ];
$info = ISXL_Plugin_Info::info( false, 'plugin_information', $args );
ensure( is_object( $info ), 'plugin_information returns plugin info' );
ensure( isset( $info->sections['description'], $info->sections['installation'], $info->sections['faq'], $info->sections['changelog'] ), 'all modal tabs are present' );
ensure( strpos( $info->sections['installation'], 'อัปโหลด' ) !== false, 'Thai installation instructions' );
ensure( strpos( $info->banners['low'], 'banner.svg?ver=' ) !== false, 'versioned banner URL' );
ensure( strpos( $info->icons['1x'], 'icon-128x128.png?ver=' ) !== false, 'versioned icon URL' );
ensure( ISXL_Plugin_Info::info( false, 'plugin_information', (object) [ 'slug' => 'another-plugin' ] ) === false, 'other plugins are untouched' );

$remote = (object) [ 'version' => '9.9.9', 'sections' => [ 'changelog' => '<p>Remote release</p>' ] ];
$decorated = ISXL_Plugin_Info::decorate( $remote );
ensure( $decorated->version === '9.9.9', 'remote release version is retained' );
ensure( $decorated->sections['changelog'] === '<p>Remote release</p>', 'remote changelog is retained' );
ensure( isset( $decorated->sections['installation'], $decorated->sections['faq'] ), 'missing sections are added to remote info' );

$update = ISXL_Plugin_Info::decorate_update( (object) [] );
ensure( isset( $update->icons['1x'], $update->icons['2x'] ), 'update row gets the new icon' );
echo "Plugin info OK\n";
