<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Settings page (InsightX AllHub look): Languages · Strings · Content ·
 * WooCommerce, the user guide page, plus the admin bar language filter.
 */
class ISXL_Admin {

    const SLUG       = 'insightx-lang';
    const GUIDE_SLUG = 'insightx-lang-guide';

    /** Common languages for the "Add language" picker: slug, name, locale, flag country. */
    const PRESETS = [
        [ 'th', 'ไทย', 'th', 'th' ],
        [ 'en', 'English', 'en_US', 'gb' ],
        [ 'zh', '中文 (简体)', 'zh_CN', 'cn' ],
        [ 'zh-tw', '中文 (繁體)', 'zh_TW', 'tw' ],
        [ 'ja', '日本語', 'ja', 'jp' ],
        [ 'ko', '한국어', 'ko_KR', 'kr' ],
        [ 'vi', 'Tiếng Việt', 'vi', 'vn' ],
        [ 'lo', 'ພາສາລາວ', 'lo', 'la' ],
        [ 'my', 'မြန်မာ', 'my_MM', 'mm' ],
        [ 'km', 'ខ្មែរ', 'km', 'kh' ],
        [ 'ms', 'Bahasa Melayu', 'ms_MY', 'my' ],
        [ 'id', 'Bahasa Indonesia', 'id_ID', 'id' ],
        [ 'fr', 'Français', 'fr_FR', 'fr' ],
        [ 'de', 'Deutsch', 'de_DE', 'de' ],
        [ 'es', 'Español', 'es_ES', 'es' ],
        [ 'ru', 'Русский', 'ru_RU', 'ru' ],
        [ 'ar', 'العربية', 'ar', 'sa' ],
        [ 'hi', 'हिन्दी', 'hi_IN', 'in' ],
    ];

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_post_isxl_save', [ __CLASS__, 'save' ] );
        add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar' ], 90 );
        add_action( 'admin_init', [ __CLASS__, 'set_admin_lang' ] );
        add_action( 'admin_notices', [ __CLASS__, 'setup_notice' ] );
    }

    public static function page_url( $tab = '' ) {
        return admin_url( 'admin.php?page=' . self::SLUG . ( $tab ? '&tab=' . $tab : '' ) );
    }

    public static function menu() {
        $hook = add_menu_page( 'InsightX Lang', 'InsightX Lang', 'manage_options', self::SLUG, [ __CLASS__, 'render' ], 'dashicons-translation', 81 );
        add_submenu_page( self::SLUG, 'InsightX Lang', __( 'Settings', 'insightx-lang' ), 'manage_options', self::SLUG, [ __CLASS__, 'render' ] );
        $guide = add_submenu_page( self::SLUG, __( 'User Guide', 'insightx-lang' ), __( 'User Guide', 'insightx-lang' ), 'manage_options', self::GUIDE_SLUG, [ __CLASS__, 'render_guide' ] );
        add_action( 'admin_print_styles-' . $hook, [ __CLASS__, 'assets' ] );
        add_action( 'admin_print_styles-' . $guide, [ __CLASS__, 'guide_assets' ] );
    }

    public static function guide_url( $anchor = '' ) {
        return admin_url( 'admin.php?page=' . self::GUIDE_SLUG ) . ( $anchor ? '#isxl-guide-' . $anchor : '' );
    }

    public static function guide_assets() {
        wp_enqueue_style( 'isxl-admin', ISXL_URL . 'assets/css/admin.css', [], (string) filemtime( ISXL_DIR . 'assets/css/admin.css' ) );
        wp_enqueue_style( 'isxl-user-guide', ISXL_URL . 'assets/css/user-guide.css', [ 'isxl-admin' ], (string) filemtime( ISXL_DIR . 'assets/css/user-guide.css' ) );
        wp_enqueue_script( 'isxl-user-guide', ISXL_URL . 'assets/js/user-guide.js', [], (string) filemtime( ISXL_DIR . 'assets/js/user-guide.js' ), true );
    }

    public static function render_guide() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        require ISXL_DIR . 'includes/views/user-guide.php';
    }

    public static function assets() {
        wp_enqueue_style( 'isxl-admin', ISXL_URL . 'assets/css/admin.css', [], (string) filemtime( ISXL_DIR . 'assets/css/admin.css' ) );
        wp_enqueue_script( 'isxl-admin', ISXL_URL . 'assets/js/admin.js', [ 'jquery', 'jquery-ui-sortable' ], (string) filemtime( ISXL_DIR . 'assets/js/admin.js' ), true );
        wp_localize_script( 'isxl-admin', 'isxlAdmin', [
            /* translators: %s: language name */
            'confirmDelete' => __( 'Delete "%s"? Its content stays, but it will have no language until you add this language again.', 'insightx-lang' ),
        ] );
    }

    public static function setup_notice() {
        if ( ISXL_Model::has_languages() || ! current_user_can( 'manage_options' ) ) return;
        $screen = get_current_screen();
        if ( $screen && strpos( (string) $screen->id, self::SLUG ) !== false ) return;
        echo '<div class="notice notice-info"><p><strong>InsightX Lang:</strong> '
            . esc_html__( 'Add your languages to start translating.', 'insightx-lang' )
            . ' <a href="' . esc_url( self::page_url() ) . '">' . esc_html__( 'Open settings', 'insightx-lang' ) . '</a></p></div>';
    }

    /* ------------------------------------------------------------------
     * Admin bar language filter
     * ------------------------------------------------------------------ */

    public static function admin_bar( WP_Admin_Bar $bar ) {
        if ( ! is_admin() || ! ISXL_Model::has_languages() || ! current_user_can( 'edit_posts' ) ) return;
        $cur   = ISXL_Model::admin_lang();
        $l     = $cur ? ISXL_Model::get_language( $cur ) : null;
        $title = $l ? trim( ISXL_Model::flag_emoji( $l['flag'] ) . ' ' . $l['name'] ) : __( 'All languages', 'insightx-lang' );
        $bar->add_node( [ 'id' => 'isxl-lang', 'title' => '<span class="ab-icon dashicons dashicons-translation" style="top:2px"></span>' . esc_html( $title ), 'href' => '#' ] );
        $opts = [ '' => __( 'All languages', 'insightx-lang' ) ];
        foreach ( ISXL_Model::languages() as $lang ) $opts[ $lang['slug'] ] = trim( ISXL_Model::flag_emoji( $lang['flag'] ) . ' ' . $lang['name'] );
        foreach ( $opts as $slug => $name ) {
            $bar->add_node( [
                'parent' => 'isxl-lang',
                'id'     => 'isxl-lang-' . ( $slug ?: 'all' ),
                'title'  => ( $slug === $cur ? '✓ ' : '' ) . esc_html( $name ),
                'href'   => wp_nonce_url( add_query_arg( 'isxl_admin_lang', $slug ?: 'all' ), 'isxl_admin_lang' ),
            ] );
        }
    }

    public static function set_admin_lang() {
        if ( ! isset( $_GET['isxl_admin_lang'] ) ) return;
        check_admin_referer( 'isxl_admin_lang' );
        $l = sanitize_key( wp_unslash( $_GET['isxl_admin_lang'] ) );
        update_user_meta( get_current_user_id(), 'isxl_admin_lang', ISXL_Model::is_language( $l ) ? $l : '' );
        wp_safe_redirect( remove_query_arg( [ 'isxl_admin_lang', '_wpnonce' ] ) );
        exit;
    }

    /* ------------------------------------------------------------------
     * Save
     * ------------------------------------------------------------------ */

    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '', 403 );
        check_admin_referer( 'isxl_save' );
        $section = isset( $_POST['section'] ) ? sanitize_key( $_POST['section'] ) : '';
        $msg     = 'saved';

        switch ( $section ) {
            case 'languages':
                $rows = isset( $_POST['langs'] ) && is_array( $_POST['langs'] ) ? wp_unslash( $_POST['langs'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in save_languages()
                $def  = isset( $_POST['default_lang'] ) ? sanitize_key( wp_unslash( $_POST['default_lang'] ) ) : '';
                $keep = [];
                foreach ( $rows as $r ) {
                    if ( ! is_array( $r ) || ! empty( $r['remove'] ) ) continue;
                    $r['is_default'] = ( sanitize_key( $r['slug'] ?? '' ) === $def );
                    $keep[] = $r;
                }
                $was_empty = ! ISXL_Model::has_languages();
                ISXL_Model::save_languages( $keep );
                self::install_language_packs();
                if ( $was_empty && ISXL_Model::has_languages() ) self::assign_default();
                break;

            case 'assign':
                $n   = self::assign_default();
                $msg = 'assigned-' . $n;
                break;

            case 'content':
                $s               = ISXL_Model::settings();
                $s['post_types'] = array_map( 'sanitize_key', (array) wp_unslash( $_POST['post_types'] ?? [] ) );
                $s['taxonomies'] = array_map( 'sanitize_key', (array) wp_unslash( $_POST['taxonomies'] ?? [] ) );
                $s['attributes'] = empty( $_POST['attributes'] ) ? 0 : 1;
                ISXL_Model::save_settings( $s );
                update_option( 'isxl_flush_rewrite', 1 );
                break;

            case 'strings':
                $tr = isset( $_POST['tr'] ) && is_array( $_POST['tr'] ) ? $_POST['tr'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in ISXL_Strings::save()
                ISXL_Strings::save( $tr );
                break;

            case 'theme_mods':
                $s               = ISXL_Model::settings();
                $s['theme_mods'] = array_values( array_map( 'sanitize_key', (array) wp_unslash( $_POST['theme_mods'] ?? [] ) ) );
                ISXL_Model::save_settings( $s );
                break;

            case 'wc':
                $s   = ISXL_Model::settings();
                $on  = array_map( 'sanitize_key', (array) wp_unslash( $_POST['wc_sync'] ?? [] ) );
                $all = class_exists( 'ISXL_WC' ) ? array_keys( ISXL_WC::sync_groups_flat() ) : [];
                $s['wc_sync'] = array_values( array_diff( $all, $on ) ); // stored as the switched-off list
                ISXL_Model::save_settings( $s );
                break;
        }

        $tab = $section === 'assign' ? 'languages' : ( $section === 'theme_mods' ? 'strings' : $section );
        wp_safe_redirect( add_query_arg( [ 'msg' => $msg ], self::page_url( $tab ) ) );
        exit;
    }

    /** Download WordPress core translations for newly added locales (best effort). */
    private static function install_language_packs() {
        if ( ! current_user_can( 'install_languages' ) ) return;
        require_once ABSPATH . 'wp-admin/includes/translation-install.php';
        if ( ! wp_can_install_language_pack() ) return;
        $installed = get_available_languages();
        foreach ( ISXL_Model::languages() as $l ) {
            if ( $l['locale'] !== 'en_US' && ! in_array( $l['locale'], $installed, true ) ) {
                wp_download_language_pack( $l['locale'] );
            }
        }
    }

    /**
     * Give every post and term of a translated type that has no language the
     * default language. Returns how many objects were updated.
     */
    public static function assign_default() {
        global $wpdb;
        $def = ISXL_Model::default_slug();
        if ( $def === '' ) return 0;
        $term = get_term_by( 'slug', $def, ISXL_Model::TAX );
        if ( ! $term ) return 0;
        $n   = 0;
        $pts = ISXL_Model::post_types();
        if ( $pts ) {
            $in  = implode( ',', array_fill( 0, count( $pts ), '%s' ) );
            $ids = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type IN ($in) AND p.post_status NOT IN ('auto-draft','trash','inherit')
                 AND NOT EXISTS ( SELECT 1 FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                                  WHERE tr.object_id = p.ID AND tt.taxonomy = %s )",
                array_merge( $pts, [ ISXL_Model::TAX ] )
            ) );
            foreach ( $ids as $id ) {
                wp_set_object_terms( (int) $id, $def, ISXL_Model::TAX );
                $n++;
            }
        }
        foreach ( ISXL_Model::taxonomies() as $tax ) {
            $terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids', 'lang' => 'all', 'meta_query' => [ [ 'key' => ISXL_Model::TERM_LANG, 'compare' => 'NOT EXISTS' ] ] ] );
            if ( is_wp_error( $terms ) ) continue;
            foreach ( $terms as $tid ) {
                update_term_meta( (int) $tid, ISXL_Model::TERM_LANG, $def );
                $n++;
            }
        }
        return $n;
    }

    /** How many posts/terms of translated types still lack a language. */
    private static function count_unassigned() {
        global $wpdb;
        $n   = 0;
        $pts = ISXL_Model::post_types();
        if ( $pts ) {
            $in = implode( ',', array_fill( 0, count( $pts ), '%s' ) );
            $n += (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type IN ($in) AND p.post_status NOT IN ('auto-draft','trash','inherit')
                 AND NOT EXISTS ( SELECT 1 FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                                  WHERE tr.object_id = p.ID AND tt.taxonomy = %s )",
                array_merge( $pts, [ ISXL_Model::TAX ] )
            ) );
        }
        foreach ( ISXL_Model::taxonomies() as $tax ) {
            $c = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'count', 'lang' => 'all', 'meta_query' => [ [ 'key' => ISXL_Model::TERM_LANG, 'compare' => 'NOT EXISTS' ] ] ] );
            if ( ! is_wp_error( $c ) ) $n += (int) $c;
        }
        return $n;
    }

    /* ------------------------------------------------------------------
     * Render
     * ------------------------------------------------------------------ */

    private static function form_open( $section ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="isxl_save"><input type="hidden" name="section" value="' . esc_attr( $section ) . '">';
        wp_nonce_field( 'isxl_save' );
    }

    private static function card_head( $title, $sub = '', $actions = '' ) {
        echo '<div class="isxl-card-head"><div class="isxl-card-head-title"><div><h2>' . esc_html( $title ) . '</h2>';
        if ( $sub !== '' ) echo '<p class="isxl-card-sub">' . esc_html( $sub ) . '</p>';
        echo '</div></div>';
        if ( $actions !== '' ) echo '<div class="isxl-card-head-actions">' . $actions . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts by callers
        echo '</div>';
    }

    public static function render() {
        // phpcs:ignore WordPress.Security.NonceVerification
        $tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'languages';
        $tabs = [
            'languages' => __( 'Languages', 'insightx-lang' ),
            'strings'   => __( 'Strings', 'insightx-lang' ),
            'content'   => __( 'Content', 'insightx-lang' ),
        ];
        if ( class_exists( 'ISXL_WC' ) ) $tabs['wc'] = 'WooCommerce';
        $tabs['guide'] = __( 'User Guide', 'insightx-lang' ); // links to the guide page
        if ( ! isset( $tabs[ $tab ] ) || $tab === 'guide' ) $tab = 'languages';
        if ( ! ISXL_Model::has_languages() ) $tab = 'languages';
        // phpcs:ignore WordPress.Security.NonceVerification
        $msg = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : '';
        ?>
        <div class="isxl-wrap" id="isxl-app">
            <header class="isxl-header">
                <div class="isxl-header-top">
                    <div class="isxl-header-brand">
                        <div class="isxl-logo" aria-hidden="true">
                            <img src="<?php echo esc_url( ISXL_URL . 'assets/icon-128x128.png?v=' . filemtime( ISXL_DIR . 'assets/icon-128x128.png' ) ); ?>" alt="" width="46" height="46">
                        </div>
                        <div>
                            <h1>InsightX Lang</h1>
                            <p class="isxl-tagline"><?php esc_html_e( 'Multilingual posts, pages, products, menus, widgets and strings', 'insightx-lang' ); ?></p>
                        </div>
                    </div>
                </div>
                <nav class="isxl-nav" role="tablist">
                    <?php foreach ( $tabs as $k => $label ) : ?>
                        <a class="isxl-nav-item<?php echo $k === $tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( $k === 'guide' ? self::guide_url() : self::page_url( $k ) ); ?>" role="tab"<?php echo $k === $tab ? ' aria-selected="true"' : ''; ?>><?php echo esc_html( $label ); ?></a>
                    <?php endforeach; ?>
                </nav>
            </header>

            <main class="isxl-main">
                <?php
                switch ( $tab ) {
                    case 'strings': self::tab_strings(); break;
                    case 'content': self::tab_content(); break;
                    case 'wc':      ISXL_WC::render_tab(); break;
                    default:        self::tab_languages();
                }
                ?>
            </main>
            <div class="isxl-toast" id="isxl-toast" role="status" aria-live="polite"
                <?php
                if ( $msg === 'saved' ) {
                    echo ' data-msg="' . esc_attr__( 'Saved', 'insightx-lang' ) . '"';
                } elseif ( strpos( $msg, 'assigned-' ) === 0 ) {
                    /* translators: %d: number of items */
                    echo ' data-msg="' . esc_attr( sprintf( __( 'Language set on %d items', 'insightx-lang' ), (int) substr( $msg, 9 ) ) ) . '"';
                }
                ?>
            ></div>
        </div>
        <?php
    }

    private static function tab_languages() {
        $langs = ISXL_Model::languages();
        ?>
        <div class="isxl-card">
            <?php self::form_open( 'languages' ); ?>
            <?php
            $picker = '<select id="isxl-preset" class="isxl-input" aria-label="' . esc_attr__( 'Choose a language…', 'insightx-lang' ) . '"><option value="">' . esc_html__( 'Choose a language…', 'insightx-lang' ) . '</option>';
            foreach ( self::PRESETS as $p ) {
                if ( isset( $langs[ $p[0] ] ) ) continue;
                $picker .= '<option value="' . esc_attr( wp_json_encode( $p ) ) . '">' . esc_html( ISXL_Model::flag_emoji( $p[3] ) . ' ' . $p[1] . ' (' . $p[2] . ')' ) . '</option>';
            }
            $picker .= '<option value="custom">' . esc_html__( 'Custom…', 'insightx-lang' ) . '</option></select>'
                . '<button type="button" class="isxl-btn isxl-btn-ghost" id="isxl-add-lang">' . esc_html__( 'Add language', 'insightx-lang' ) . '</button>';
            self::card_head( __( 'Languages', 'insightx-lang' ), __( 'Drag to reorder. The default language has no prefix in URLs; the others use /slug/.', 'insightx-lang' ), $picker );
            ?>
            <div class="isxl-card-body">
                <table class="isxl-table isxl-lang-table">
                    <thead><tr>
                        <th class="isxl-col-drag" aria-label="<?php esc_attr_e( 'Order', 'insightx-lang' ); ?>"></th>
                        <th><?php esc_html_e( 'Default', 'insightx-lang' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'insightx-lang' ); ?></th>
                        <th><?php esc_html_e( 'URL slug', 'insightx-lang' ); ?></th>
                        <th><?php esc_html_e( 'Locale', 'insightx-lang' ); ?></th>
                        <th><?php esc_html_e( 'Flag', 'insightx-lang' ); ?></th>
                        <th class="isxl-col-del"><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'insightx-lang' ); ?></span></th>
                    </tr></thead>
                    <tbody id="isxl-lang-rows">
                        <?php
                        $i = 0;
                        foreach ( $langs as $l ) {
                            self::lang_row( $i++, $l );
                        }
                        ?>
                    </tbody>
                </table>
                <template id="isxl-lang-row-tpl"><?php self::lang_row( '__i__', [ 'slug' => '', 'name' => '', 'locale' => '', 'flag' => '', 'is_default' => false ] ); ?></template>

                <p class="isxl-hint isxl-hint-below"><?php esc_html_e( 'Removing a language keeps its content; the content simply has no language until you add it again.', 'insightx-lang' ); ?></p>
            </div>
            <div class="isxl-card-foot"><button type="submit" class="isxl-btn isxl-btn-primary"><?php esc_html_e( 'Save languages', 'insightx-lang' ); ?></button></div>
            </form>
        </div>

        <?php if ( ISXL_Model::has_languages() ) : $n = self::count_unassigned(); ?>
        <div class="isxl-card">
            <?php self::form_open( 'assign' ); ?>
            <?php self::card_head( __( 'Content without a language', 'insightx-lang' ), __( 'Items without a language are hidden from the front end.', 'insightx-lang' ) ); ?>
            <div class="isxl-card-body isxl-row">
                <p class="isxl-big-num"><strong><?php echo esc_html( number_format_i18n( $n ) ); ?></strong> <?php esc_html_e( 'items', 'insightx-lang' ); ?></p>
                <button type="submit" class="isxl-btn isxl-btn-ghost"<?php disabled( $n, 0 ); ?>>
                    <?php
                    $d = ISXL_Model::get_language( ISXL_Model::default_slug() );
                    /* translators: %s: language name */
                    echo esc_html( sprintf( __( 'Set them to %s', 'insightx-lang' ), $d ? $d['name'] : '' ) );
                    ?>
                </button>
            </div>
            </form>
        </div>
        <?php endif;
    }

    /** @param int|string $i */
    private static function lang_row( $i, array $l ) {
        $n = 'langs[' . $i . ']';
        ?>
        <tr class="isxl-lang-row"<?php echo $l['slug'] === '' ? ' data-new="1"' : ''; ?>>
            <td class="isxl-col-drag"><span class="isxl-drag" title="<?php esc_attr_e( 'Drag to reorder', 'insightx-lang' ); ?>">⋮⋮</span></td>
            <td><input type="radio" name="default_lang" value="<?php echo esc_attr( $l['slug'] ); ?>" class="isxl-default-radio" <?php checked( ! empty( $l['is_default'] ) ); ?> aria-label="<?php esc_attr_e( 'Default language', 'insightx-lang' ); ?>"></td>
            <td><input type="text" name="<?php echo esc_attr( $n ); ?>[name]" value="<?php echo esc_attr( $l['name'] ); ?>" required class="isxl-input" data-f="name"></td>
            <td><input type="text" name="<?php echo esc_attr( $n ); ?>[slug]" value="<?php echo esc_attr( $l['slug'] ); ?>" required pattern="[a-z0-9-]+" class="isxl-input isxl-input-sm isxl-slug" data-f="slug"></td>
            <td><input type="text" name="<?php echo esc_attr( $n ); ?>[locale]" value="<?php echo esc_attr( $l['locale'] ); ?>" required pattern="[A-Za-z0-9_]+" class="isxl-input isxl-input-sm" data-f="locale"></td>
            <td><input type="text" name="<?php echo esc_attr( $n ); ?>[flag]" value="<?php echo esc_attr( $l['flag'] ); ?>" maxlength="2" class="isxl-input isxl-input-xs" data-f="flag" placeholder="th"> <span class="isxl-flag-preview"><?php echo esc_html( ISXL_Model::flag_emoji( $l['flag'] ) ); ?></span></td>
            <td class="isxl-col-del">
                <button type="button" class="isxl-del" title="<?php esc_attr_e( 'Remove', 'insightx-lang' ); ?>" aria-label="<?php esc_attr_e( 'Remove', 'insightx-lang' ); ?>"<?php disabled( ! empty( $l['is_default'] ) ); ?>>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                </button>
            </td>
        </tr>
        <?php
    }

    private static function tab_content() {
        $s    = ISXL_Model::settings();
        $pts  = array_filter( get_post_types( [ 'show_ui' => true ], 'objects' ), function ( $o ) { return ISXL_Model::translatable_post_type( $o->name ); } );
        $taxs = array_filter( get_taxonomies( [ 'show_ui' => true ], 'objects' ), function ( $o ) { return ISXL_Model::translatable_taxonomy( $o->name ); } );
        unset( $pts['attachment'], $pts['wp_navigation'], $pts['shop_order'], $pts['shop_coupon'], $pts['product_variation'], $pts['shop_order_refund'] );
        unset( $taxs['nav_menu'], $taxs['link_category'], $taxs['post_format'], $taxs['product_type'], $taxs['product_visibility'], $taxs['product_shipping_class'] );
        foreach ( array_keys( $taxs ) as $k ) if ( strpos( $k, 'pa_' ) === 0 ) unset( $taxs[ $k ] );
        ?>
        <?php self::form_open( 'content' ); ?>
        <div class="isxl-grid-2">
            <div class="isxl-card">
                <?php self::card_head( __( 'Translated content types', 'insightx-lang' ), __( 'Each item belongs to one language and can have one translation per language.', 'insightx-lang' ) ); ?>
                <div class="isxl-card-body isxl-checklist">
                    <?php foreach ( $pts as $pt ) : ?>
                        <label><input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, (array) $s['post_types'], true ) ); ?>> <?php echo esc_html( $pt->labels->name ); ?> <code><?php echo esc_html( $pt->name ); ?></code></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="isxl-card">
                <?php self::card_head( __( 'Translated taxonomies', 'insightx-lang' ), __( 'Categories, tags and product attributes per language.', 'insightx-lang' ) ); ?>
                <div class="isxl-card-body isxl-checklist">
                    <?php foreach ( $taxs as $t ) : ?>
                        <label><input type="checkbox" name="taxonomies[]" value="<?php echo esc_attr( $t->name ); ?>" <?php checked( in_array( $t->name, (array) $s['taxonomies'], true ) ); ?>> <?php echo esc_html( $t->labels->name ); ?> <code><?php echo esc_html( $t->name ); ?></code></label>
                    <?php endforeach; ?>
                    <?php if ( function_exists( 'wc_get_attribute_taxonomy_names' ) ) : ?>
                        <label><input type="checkbox" name="attributes" value="1" <?php checked( ! empty( $s['attributes'] ) ); ?>> <?php esc_html_e( 'All WooCommerce product attributes', 'insightx-lang' ); ?> <code>pa_*</code></label>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="isxl-actions"><button type="submit" class="isxl-btn isxl-btn-primary"><?php esc_html_e( 'Save', 'insightx-lang' ); ?></button></div>
        </form>
        <?php
    }

    private static function tab_strings() {
        $strings = ISXL_Strings::all();
        $others  = array_filter( ISXL_Model::languages(), function ( $l ) { return ! $l['is_default']; } );
        $groups  = array_unique( array_column( $strings, 'group' ) );
        sort( $groups );
        ?>
        <div class="isxl-card">
            <?php self::form_open( 'strings' ); ?>
            <?php
            $tools = '<input type="search" class="isxl-input" id="isxl-str-search" placeholder="' . esc_attr__( 'Search…', 'insightx-lang' ) . '">'
                . '<select id="isxl-str-group" class="isxl-input"><option value="">' . esc_html__( 'All groups', 'insightx-lang' ) . '</option>';
            foreach ( $groups as $g ) $tools .= '<option>' . esc_html( $g ) . '</option>';
            $tools .= '</select>';
            self::card_head( __( 'String translations', 'insightx-lang' ), __( 'Leave a field empty to use the original text.', 'insightx-lang' ), $tools );
            ?>
            <div class="isxl-card-body isxl-flush">
                <?php if ( ! $others ) : ?>
                    <p class="isxl-empty"><?php esc_html_e( 'Add a second language first.', 'insightx-lang' ); ?></p>
                <?php else : ?>
                <table class="isxl-table isxl-str-table">
                    <thead><tr>
                        <th><?php esc_html_e( 'Original', 'insightx-lang' ); ?></th>
                        <?php foreach ( $others as $l ) : ?><th><?php echo esc_html( trim( ISXL_Model::flag_emoji( $l['flag'] ) . ' ' . $l['name'] ) ); ?></th><?php endforeach; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $strings as $key => $s ) : ?>
                        <tr data-group="<?php echo esc_attr( $s['group'] ); ?>" data-search="<?php echo esc_attr( strtolower( $s['name'] . ' ' . $s['string'] ) ); ?>">
                            <td class="isxl-str-orig"><span class="isxl-str-meta"><?php echo esc_html( $s['group'] . ' · ' . $s['name'] ); ?></span><?php echo esc_html( $s['string'] ); ?></td>
                            <?php foreach ( $others as $l ) : $v = ISXL_Strings::get( $key, $l['slug'] ); $name = 'tr[' . $l['slug'] . '][' . $key . ']'; ?>
                                <td>
                                    <?php if ( $s['multiline'] || strlen( $s['string'] ) > 80 ) : ?>
                                        <textarea class="isxl-input" rows="3" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $v ); ?></textarea>
                                    <?php else : ?>
                                        <input type="text" class="isxl-input" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $v ); ?>">
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php if ( $others ) : ?><div class="isxl-card-foot"><button type="submit" class="isxl-btn isxl-btn-primary"><?php esc_html_e( 'Save translations', 'insightx-lang' ); ?></button></div><?php endif; ?>
            </form>
        </div>

        <div class="isxl-card">
            <?php self::form_open( 'theme_mods' ); ?>
            <?php self::card_head( __( 'Theme texts', 'insightx-lang' ), __( 'Texts from the Customizer (header, footer, buttons). Ticked ones appear in the list above.', 'insightx-lang' ) ); ?>
            <div class="isxl-card-body isxl-checklist">
                <?php
                $mods   = ISXL_Strings::theme_text_mods();
                $chosen = (array) ISXL_Model::settings()['theme_mods'];
                if ( ! $mods ) echo '<p class="isxl-empty">' . esc_html__( 'The active theme has no text settings.', 'insightx-lang' ) . '</p>';
                foreach ( $mods as $k => $v ) :
                    ?>
                    <label><input type="checkbox" name="theme_mods[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $chosen, true ) ); ?>> <code><?php echo esc_html( $k ); ?></code> <span class="isxl-muted"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $v ), 12 ) ); ?></span></label>
                <?php endforeach; ?>
            </div>
            <?php if ( $mods ) : ?><div class="isxl-card-foot"><button type="submit" class="isxl-btn isxl-btn-ghost"><?php esc_html_e( 'Save theme texts', 'insightx-lang' ); ?></button></div><?php endif; ?>
            </form>
        </div>
        <?php
    }
}
