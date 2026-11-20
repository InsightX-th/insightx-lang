<?php
/**
 * Plugin Name:       InsightX Lang
 * Plugin URI:        https://insightx.in.th/
 * Version:           0.1.1
 * Author:            InsightX
 * Author URI:        https://www.insightx.in.th
 * Text Domain:       insightx-lang
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * WC requires at least: 8.0
 * Description:       Lean multilingual for WordPress: posts, pages, terms, menus, widgets, Gutenberg blocks, strings, hreflang and WooCommerce products — /en/ directory URLs.
 * License:           GPLv3 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ISXL_VERSION', '0.1.1' );
define( 'ISXL_FILE', __FILE__ );
define( 'ISXL_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'ISXL_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/*
 * Two multilingual plugins on the same request would both rewrite the URL,
 * filter every query and fight over links. Stand aside while Polylang or
 * Hyyan WooPoly is active and say so.
 */
function isxl_conflicting_plugins() {
    $found = [];
    if ( defined( 'POLYLANG_VERSION' ) || function_exists( 'pll_current_language' ) ) $found[] = 'Polylang';
    if ( class_exists( 'Hyyan\\WPI\\Plugin' ) ) $found[] = 'Hyyan WooCommerce Polylang Integration';
    return $found;
}

add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'insightx-lang', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    $conflicts = isxl_conflicting_plugins();
    if ( $conflicts ) {
        add_action( 'admin_notices', function () use ( $conflicts ) {
            echo '<div class="notice notice-warning"><p><strong>InsightX Lang:</strong> '
                . esc_html( sprintf(
                    /* translators: %s: plugin names */
                    __( 'Deactivate %s first — InsightX Lang stays off while another multilingual plugin is active.', 'insightx-lang' ),
                    implode( ', ', $conflicts )
                ) ) . '</p></div>';
        } );
        return;
    }

    require_once ISXL_DIR . 'includes/class-isxl-url.php';
    require_once ISXL_DIR . 'includes/class-isxl-model.php';
    require_once ISXL_DIR . 'includes/api.php';
    require_once ISXL_DIR . 'includes/class-isxl-router.php';
    require_once ISXL_DIR . 'includes/class-isxl-query.php';
    require_once ISXL_DIR . 'includes/class-isxl-links.php';
    require_once ISXL_DIR . 'includes/class-isxl-duplicator.php';
    require_once ISXL_DIR . 'includes/class-isxl-menus.php';
    require_once ISXL_DIR . 'includes/class-isxl-widgets.php';
    require_once ISXL_DIR . 'includes/class-isxl-strings.php';
    require_once ISXL_DIR . 'includes/class-isxl-switcher.php';
    require_once ISXL_DIR . 'includes/class-isxl-seo.php';

    ISXL_Model::init();
    ISXL_Router::init();
    ISXL_Query::init();
    ISXL_Links::init();
    ISXL_Duplicator::init();
    ISXL_Menus::init();
    ISXL_Widgets::init();
    ISXL_Strings::init();
    ISXL_Switcher::init();
    ISXL_SEO::init();

    if ( is_admin() ) {
        require_once ISXL_DIR . 'includes/class-isxl-admin.php';
        require_once ISXL_DIR . 'includes/class-isxl-admin-posts.php';
        require_once ISXL_DIR . 'includes/class-isxl-admin-terms.php';
        ISXL_Admin::init();
        ISXL_Admin_Posts::init();
        ISXL_Admin_Terms::init();
    }
    require_once ISXL_DIR . 'includes/class-isxl-editor.php';
    ISXL_Editor::init();

    if ( class_exists( 'WooCommerce' ) ) {
        require_once ISXL_DIR . 'includes/class-isxl-wc.php';
        ISXL_WC::init();
    }
}, 1 );

// HPOS + block checkout: the plugin only reads/writes orders through WC_Order.
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

register_activation_hook( __FILE__, function () {
    // Rewrite rules depend on the language list; rebuild on the next request.
    update_option( 'isxl_flush_rewrite', 1 );
} );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/*
 * GitHub update checker — same setup as InsightX AllHub: release assets
 * built by the release workflow, public repo, no auth.
 */
if ( file_exists( ISXL_DIR . 'libs/plugin-update-checker/plugin-update-checker.php' ) ) {
    require_once ISXL_DIR . 'libs/plugin-update-checker/plugin-update-checker.php';
    $isxl_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/InsightX-th/insightx-lang',
        __FILE__,
        'insightx-lang'
    );
    $isxl_update_checker->getVcsApi()->enableReleaseAssets();
}
