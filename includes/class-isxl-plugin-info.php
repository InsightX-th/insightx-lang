<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** WordPress plugin details modal and update-row artwork. */
class ISXL_Plugin_Info {

    const SLUG = 'insightx-lang';

    public static function register_hooks() {
        add_filter( 'plugins_api', [ __CLASS__, 'info' ], 10, 3 );
    }

    private static function asset_url( $path ) {
        $file = ISXL_DIR . $path;
        return ISXL_URL . $path . '?ver=' . (string) filemtime( $file );
    }

    public static function info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || ! is_object( $args ) || ! isset( $args->slug ) || $args->slug !== self::SLUG ) return $result;

        return (object) [
            'name'         => 'InsightX Lang',
            'slug'         => self::SLUG,
            'version'      => ISXL_VERSION,
            'author'       => '<a href="https://www.insightx.in.th">InsightX</a>',
            'homepage'     => 'https://insightx.in.th/',
            'requires'     => '6.5',
            'requires_php' => '7.4',
            'sections'     => self::sections(),
            'banners'      => [ 'low' => self::asset_url( 'assets/branding/banner.svg' ), 'high' => self::asset_url( 'assets/branding/banner.svg' ) ],
            'icons'        => self::icons(),
            'external'     => true,
        ];
    }

    public static function decorate( $info ) {
        if ( ! is_object( $info ) ) return $info;
        $ours = self::info( false, 'plugin_information', (object) [ 'slug' => self::SLUG ] );
        $sections = $ours->sections;
        if ( ! empty( $info->sections['changelog'] ) ) $sections['changelog'] = $info->sections['changelog'];
        $info->sections = $sections;
        $info->banners  = $ours->banners;
        $info->icons    = $ours->icons;
        return $info;
    }

    public static function decorate_update( $update ) {
        if ( is_object( $update ) ) $update->icons = self::icons();
        return $update;
    }

    private static function icons() {
        return [
            '1x'      => self::asset_url( 'assets/icon-128x128.png' ),
            '2x'      => self::asset_url( 'assets/icon-256x256.png' ),
            'default' => self::asset_url( 'assets/icon-128x128.png' ),
        ];
    }

    private static function sections() {
        if ( strpos( determine_locale(), 'th' ) === 0 ) {
            return [
                'description'  => '<p><strong>InsightX Lang</strong> ช่วยทำเว็บไซต์หลายภาษาบน WordPress และ WooCommerce แปลเรื่อง หน้า สินค้า หมวดหมู่ เมนู วิดเจ็ต และข้อความของเว็บได้</p><p>ภาษาหลักใช้ URL ปกติ ส่วนภาษาอื่นใช้คำนำหน้า เช่น <code>/en/</code> พร้อมตัวสลับภาษาและแท็ก hreflang</p>',
                'installation' => '<ol><li>อัปโหลดไฟล์ zip ของ InsightX Lang ผ่าน <strong>ปลั๊กอิน → เพิ่มปลั๊กอิน → อัปโหลดปลั๊กอิน</strong> แล้วเปิดใช้งาน</li><li>ปิด Polylang และ Hyyan WooCommerce Polylang Integration หากยังเปิดอยู่</li><li>เปิด <strong>InsightX Lang → Settings → Languages</strong> เพิ่มภาษา เลือกภาษาหลัก แล้วบันทึก</li><li>เลือกประเภทเนื้อหาในแท็บ Content จากนั้นสร้างฉบับแปลและตั้งเมนูแต่ละภาษา</li></ol><p>คู่มือพร้อมภาพหน้าจอจริงอยู่ที่ <strong>InsightX Lang → User Guide</strong></p>',
                'faq'          => '<h4>ทำไมเปิด /en/ แล้วย้อนกลับไปภาษาไทย?</h4><p>หน้าแรกภาษาอังกฤษยังไม่มีหรือยังไม่เผยแพร่ ให้แปลหน้าที่ตั้งเป็นหน้าแรกแล้วเผยแพร่ก่อน</p><h4>เนื้อหาเดิมไม่แสดงหลังเปิดปลั๊กอิน?</h4><p>ตรวจว่าเนื้อหานั้นถูกกำหนดเป็นภาษาหลักแล้ว ใช้เครื่องมือจัดการเนื้อหาที่ไม่มีภาษาในแท็บ Languages</p><h4>ใช้ร่วมกับ Polylang ได้ไหม?</h4><p>ไม่ได้ ให้ปิด Polylang ก่อนเปิดใช้ InsightX Lang เพื่อไม่ให้ระบบ URL และภาษาแย่งกันทำงาน</p><h4>แปลสินค้า WooCommerce ได้ไหม?</h4><p>ได้ สินค้าแต่ละภาษาเป็นคนละรายการ และเลือกซิงก์ราคา สต็อก SKU และข้อมูลอื่นในแท็บ WooCommerce ได้</p>',
                'changelog'    => self::changelog(),
            ];
        }
        return [
            'description'  => '<p><strong>InsightX Lang</strong> adds multilingual content to WordPress and WooCommerce: posts, pages, products, terms, menus, widgets and site strings.</p><p>The default language keeps its normal URLs; other languages use a prefix such as <code>/en/</code>. Language switchers and hreflang tags are included.</p>',
            'installation' => '<ol><li>Upload the InsightX Lang zip under <strong>Plugins → Add New → Upload Plugin</strong>, then activate it.</li><li>Deactivate Polylang and Hyyan WooCommerce Polylang Integration if either is active.</li><li>Open <strong>InsightX Lang → Settings → Languages</strong>, add languages, choose a default and save.</li><li>Select translatable content in the Content tab, then create translations and menus for each language.</li></ol><p>See <strong>InsightX Lang → User Guide</strong> for the illustrated guide.</p>',
            'faq'          => '<h4>Why does /en/ return to the default homepage?</h4><p>The translated homepage is missing or unpublished. Translate and publish the page selected as the homepage.</p><h4>Why is existing content missing?</h4><p>Check that it has a language. Assign unlabelled content to the default language in the Languages tab.</p><h4>Can I run it alongside Polylang?</h4><p>No. Deactivate Polylang before using InsightX Lang so their language routing does not conflict.</p><h4>Can I translate WooCommerce products?</h4><p>Yes. Products are separate per language, while price, stock, SKU and other fields can be synced in the WooCommerce tab.</p>',
            'changelog'    => self::changelog(),
        ];
    }

    private static function changelog() {
        $text = file_get_contents( ISXL_DIR . 'CHANGELOG.md' );
        if ( $text === false ) return '';
        $html = '';
        foreach ( preg_split( '/\R/', $text ) as $line ) {
            if ( preg_match( '/^\*\*\[([0-9.]+)\] - ([0-9-]+)\*\*$/', $line, $m ) ) {
                if ( $html !== '' ) $html .= '</ul>';
                $html .= '<h4>' . esc_html( $m[1] ) . ' — ' . esc_html( $m[2] ) . '</h4><ul>';
            } elseif ( strpos( $line, '- ' ) === 0 && $html !== '' ) {
                $html .= '<li>' . esc_html( substr( $line, 2 ) ) . '</li>';
            }
        }
        return $html === '' ? '' : $html . '</ul>';
    }
}
