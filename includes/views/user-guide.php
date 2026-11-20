<?php
/** Thai user guide (same layout as the InsightX AllHub guide). */
if ( ! defined( 'ABSPATH' ) ) exit;

$guide_topics = [
    'start'       => 'เริ่มต้นใช้งาน',
    'languages'   => 'เพิ่มและจัดการภาษา',
    'content'     => 'เลือกเนื้อหาที่จะแปล',
    'translate'   => 'แปลเรื่อง หน้า และสินค้า',
    'terms'       => 'แปลหมวดหมู่ แท็ก และคุณสมบัติ',
    'menus'       => 'เมนูแยกตามภาษา',
    'switcher'    => 'ตัวสลับภาษา',
    'widgets'     => 'วิดเจ็ตและบล็อกตามภาษา',
    'strings'     => 'แปลข้อความของเว็บ',
    'woocommerce' => 'WooCommerce',
    'seo'         => 'URL และ SEO',
    'developers'  => 'สำหรับนักพัฒนา',
    'troubleshooting' => 'แก้ปัญหาที่พบบ่อย',
];

// Screenshots of the installed plugin UI.
$guide_figure = static function ( $file, $alt, $caption, $width, $height ) {
    $url = ISXL_URL . 'assets/images/guide/' . $file;
    ?>
    <figure class="isxl-guide-figure">
        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( 'เปิดภาพขนาดเต็มในแท็บใหม่: ' . $caption ); ?>">
            <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" loading="lazy" decoding="async">
        </a>
        <figcaption><?php echo esc_html( $caption ); ?><span>คลิกภาพเพื่อดูขนาดเต็มในแท็บใหม่</span></figcaption>
    </figure>
    <?php
};
$settings_url = ISXL_Admin::page_url();
?>
<div class="isxl-wrap isxl-guide" lang="th">
    <header class="isxl-header isxl-guide-header" id="isxl-guide-top">
        <div class="isxl-header-brand">
            <div class="isxl-logo" aria-hidden="true">
                <img src="<?php echo esc_url( ISXL_URL . 'assets/icon-128x128.png?v=' . filemtime( ISXL_DIR . 'assets/icon-128x128.png' ) ); ?>" alt="" width="46" height="46">
            </div>
            <div>
                <h1>คู่มือการใช้งาน InsightX Lang</h1>
                <p class="isxl-tagline">ทำเว็บหลายภาษา ทั้งเรื่อง หน้า สินค้า WooCommerce เมนู วิดเจ็ต และข้อความของเว็บ</p>
            </div>
        </div>
        <a class="isxl-btn isxl-btn-ghost" href="<?php echo esc_url( $settings_url ); ?>">เปิดหน้าตั้งค่า InsightX Lang <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
    </header>

    <div class="isxl-guide-layout">
        <nav class="isxl-guide-toc" aria-label="สารบัญคู่มือ">
            <h2 id="isxl-guide-topics-label">หัวข้อในคู่มือ</h2>
            <select class="isxl-guide-topic-select" aria-labelledby="isxl-guide-topics-label">
                <?php foreach ( $guide_topics as $guide_id => $guide_title ) : ?>
                    <option value="isxl-guide-<?php echo esc_attr( $guide_id ); ?>"><?php echo esc_html( $guide_title ); ?></option>
                <?php endforeach; ?>
            </select>
            <ul>
                <?php foreach ( $guide_topics as $guide_id => $guide_title ) : ?>
                    <li><a href="#isxl-guide-<?php echo esc_attr( $guide_id ); ?>"><?php echo esc_html( $guide_title ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <article class="isxl-guide-content">
            <section id="isxl-guide-start" aria-labelledby="isxl-guide-start-title">
                <h2 id="isxl-guide-start-title">เริ่มต้นใช้งาน</h2>
                <p>InsightX Lang ทำให้เนื้อหาแต่ละชิ้นมีภาษาของตัวเอง และผูกฉบับแปลของแต่ละภาษาเข้าเป็นกลุ่มเดียวกัน ภาษาหลักไม่มีคำนำหน้าใน URL เช่น <code>/about/</code> ส่วนภาษาอื่นขึ้นต้นด้วย slug ของภาษา เช่น <code>/en/about/</code></p>
                <ol>
                    <li><strong>ปิด Polylang และ Hyyan WooCommerce Polylang Integration ก่อน</strong> ถ้ายังเปิดอยู่ InsightX Lang จะไม่ทำงานและขึ้นแจ้งเตือนบอก</li>
                    <li>เปิด <strong>InsightX Lang → ตั้งค่า (Settings)</strong> แท็บ <strong>ภาษา (Languages)</strong> แล้วเพิ่มภาษาที่ต้องการ เลือกภาษาหลัก แล้วกด <strong>บันทึกภาษา (Save languages)</strong></li>
                    <li>บันทึกครั้งแรก ระบบจะตั้งเนื้อหาเดิมทั้งหมดเป็นภาษาหลักให้เอง</li>
                    <li>แปลหน้าแรก และหน้าที่ใช้บ่อย (เกี่ยวกับเรา ติดต่อเรา ร้านค้า ตะกร้า ชำระเงิน) เป็นภาษาอื่น</li>
                    <li>ตั้งเมนูของแต่ละภาษา และใส่ตัวสลับภาษาใน header</li>
                </ol>
                <p class="isxl-guide-note"><strong>หน้าแรกของภาษาอื่น:</strong> ถ้ายังไม่ได้แปลหน้าแรก (หน้าที่ตั้งไว้ใน ตั้งค่า → การอ่าน) การเปิด <code>/en/</code> จะพากลับไปหน้าแรกภาษาหลัก แปลหน้าแรกก่อนเพื่อให้ <code>/en/</code> ใช้งานได้</p>
            </section>

            <section id="isxl-guide-languages" aria-labelledby="isxl-guide-languages-title">
                <h2 id="isxl-guide-languages-title">เพิ่มและจัดการภาษา</h2>
                <p>เปิดแท็บ <strong>ภาษา (Languages)</strong> ในหน้าตั้งค่า</p>
                <?php $guide_figure( 'languages-real.png', 'แท็บภาษา มีตารางภาษาไทยและอังกฤษ ช่องเลือกภาษาพร้อมปุ่มเพิ่มภาษาด้านขวาบน และปุ่มถังขยะท้ายแถว', 'หน้าจอจริง: แท็บภาษา (Languages)', 1176, 393 ); ?>
                <ol>
                    <li>เลือกภาษาจากช่อง <strong>เลือกภาษา… (Choose a language…)</strong> ขวาบนของการ์ด แล้วกด <strong>เพิ่มภาษา (Add language)</strong> ถ้าไม่มีในรายการ ให้เลือก <strong>กำหนดเอง… (Custom…)</strong> แล้วกรอกเอง</li>
                    <li>ตรวจค่าในแถวใหม่ แล้วกด <strong>บันทึกภาษา (Save languages)</strong></li>
                </ol>
                <dl>
                    <dt>ภาษาหลัก (Default)</dt><dd>ภาษาที่ไม่มีคำนำหน้าใน URL เลือกได้ภาษาเดียว และเป็นภาษาต้นฉบับของแท็บข้อความ (Strings)</dd>
                    <dt>ชื่อ (Name)</dt><dd>ชื่อที่ผู้ชมเห็นในตัวสลับภาษา ควรเขียนเป็นภาษานั้นเอง เช่น ไทย, English, 中文</dd>
                    <dt>Slug ใน URL</dt><dd>คำนำหน้าใน URL เช่น <code>en</code> → <code>/en/</code> ใช้ได้เฉพาะตัวพิมพ์เล็ก ตัวเลข และขีด ถ้าเปลี่ยนภายหลัง ลิงก์เดิมที่แชร์ไปแล้วจะเปิดไม่ได้</dd>
                    <dt>Locale</dt><dd>รหัสภาษาของ WordPress เช่น <code>th</code>, <code>en_US</code>, <code>zh_CN</code> ใช้เลือกไฟล์แปลของธีม ปลั๊กอิน และ WordPress ระบบจะดาวน์โหลดไฟล์แปลของ WordPress ให้เองเมื่อบันทึก</dd>
                    <dt>ธง (Flag)</dt><dd>รหัสประเทศ 2 ตัว เช่น <code>th</code>, <code>gb</code>, <code>cn</code> ใช้แสดงธงในตัวสลับภาษาและในหน้า admin</dd>
                </dl>
                <h3>เรียงลำดับและลบภาษา</h3>
                <ul>
                    <li>ลากจุด ⋮⋮ หน้าแถวเพื่อเรียงลำดับ ลำดับนี้ใช้ในตัวสลับภาษา แล้วกดบันทึก</li>
                    <li>กดไอคอน <strong>ถังขยะ</strong> ท้ายแถวเพื่อลบภาษา ระบบจะถามยืนยันแล้วบันทึกทันที ภาษาหลักลบไม่ได้ ให้เปลี่ยนภาษาหลักก่อน</li>
                </ul>
                <p class="isxl-guide-note"><strong>ลบภาษาแล้วเนื้อหายังอยู่:</strong> เรื่อง หน้า และสินค้าของภาษานั้นไม่ถูกลบ แต่จะไม่มีภาษากำกับและไม่แสดงหน้าเว็บ จนกว่าจะเพิ่มภาษาเดิมกลับมา (ใช้ slug เดิม)</p>
                <p>การ์ด <strong>เนื้อหาที่ยังไม่มีภาษา</strong> ด้านล่างบอกจำนวนรายการที่ยังไม่มีภาษา ซึ่งจะไม่แสดงหน้าเว็บ กดปุ่มในการ์ดเพื่อตั้งทั้งหมดเป็นภาษาหลัก</p>
            </section>

            <section id="isxl-guide-content" aria-labelledby="isxl-guide-content-title">
                <h2 id="isxl-guide-content-title">เลือกเนื้อหาที่จะแปล</h2>
                <p>แท็บ <strong>เนื้อหา (Content)</strong> ใช้เลือกว่าประเภทเนื้อหาและหมวดหมู่ไหนมีภาษาแยก ค่าเริ่มต้นคือเรื่อง หน้า Patterns สินค้า หมวดหมู่ แท็ก และคุณสมบัติสินค้า</p>
                <?php $guide_figure( 'content-real.png', 'แท็บเนื้อหา มีรายการประเภทเนื้อหาและหมวดหมู่ที่ติ๊กเลือกให้แปลได้', 'หน้าจอจริง: แท็บเนื้อหา (Content)', 1176, 263 ); ?>
                <ul>
                    <li><strong>ติ๊กไว้:</strong> แต่ละรายการมีภาษาของตัวเอง หน้าเว็บแสดงเฉพาะรายการในภาษาที่ผู้ชมเปิดอยู่</li>
                    <li><strong>ไม่ติ๊ก:</strong> ใช้รายการเดียวกันทุกภาษา เช่น หมวดหมู่ที่ไม่ต้องแปล</li>
                    <li>รายการนี้แสดงเฉพาะประเภทที่ผู้ชมเห็นได้ ประเภทภายในของปลั๊กอิน (เช่นที่ ACF ใช้เก็บการตั้งค่า) จะไม่อยู่ในรายการ เพราะถ้าแยกภาษาแล้วจะทำให้ปลั๊กอินนั้นโหลดค่าไม่ครบ</li>
                </ul>
                <p>เปิดแปลประเภทใหม่แล้ว ให้กลับไปแท็บภาษา แล้วกดตั้งเนื้อหาที่ยังไม่มีภาษาเป็นภาษาหลัก</p>
            </section>

            <section id="isxl-guide-translate" aria-labelledby="isxl-guide-translate-title">
                <h2 id="isxl-guide-translate-title">แปลเรื่อง หน้า และสินค้า</h2>
                <p>ในหน้ารายการ (เรื่อง หน้า สินค้า) มีคอลัมน์ธงของแต่ละภาษาต่อจากชื่อ</p>
                <?php $guide_figure( 'post-list-real.png', 'หน้ารายการหน้า มีคอลัมน์ธงไทยและอังกฤษ แสดงเครื่องหมายถูกและดินสอ', 'หน้าจอจริง: คอลัมน์ภาษาในหน้ารายการ', 1176, 282 ); ?>
                <dl>
                    <dt>✓ (สีเขียว)</dt><dd>รายการนี้อยู่ในภาษานี้</dd>
                    <dt>✎ (ดินสอ)</dt><dd>มีฉบับแปลภาษานี้แล้ว กดเพื่อแก้ไขฉบับแปล</dd>
                    <dt>+ (บวก)</dt><dd>ยังไม่มีฉบับแปล กดเพื่อสร้าง</dd>
                </dl>
                <h3>สร้างฉบับแปลจาก editor</h3>
                <?php $guide_figure( 'editor-panel-real.png', 'แผงภาษาใน sidebar ของ Gutenberg แสดงว่าเนื้อหาเป็นภาษาไทย และมีลิงก์แก้ไขฉบับภาษาอังกฤษ', 'หน้าจอจริง: แผงภาษา (Language) ใน Gutenberg', 658, 382 ); ?>
                <ol>
                    <li>เปิดเรื่อง หน้า หรือสินค้าใน editor แล้วดูแผง <strong>ภาษา (Language)</strong> ในแท็บ Page/Post ทางขวา</li>
                    <li>ถ้าแก้อะไรอยู่ ให้กด <strong>บันทึก</strong> ก่อน ปุ่มเพิ่มฉบับแปลจะกดไม่ได้จนกว่าจะบันทึก</li>
                    <li>กด <strong>+ เพิ่ม (+ Add)</strong> ข้างภาษาที่ต้องการ ระบบจะสร้าง <strong>ฉบับร่าง</strong> ที่คัดลอกเนื้อหา บล็อก รูปหน้าปก ฟิลด์เพิ่มเติม และข้อมูล Elementor มาให้ แล้วเปิด editor ของฉบับร่างนั้น</li>
                    <li>แปลชื่อ เนื้อหา และ slug แล้วกด <strong>เผยแพร่</strong></li>
                </ol>
                <ul>
                    <li>หมวดหมู่และแท็กของฉบับแปลจะใช้หมวดหมู่ภาษาเดียวกันที่แปลไว้แล้วให้เอง ถ้าหมวดหมู่ไหนยังไม่ได้แปล ฉบับแปลจะไม่มีหมวดหมู่นั้น</li>
                    <li>หน้าลูก (Parent) จะผูกกับหน้าแม่ภาษาเดียวกันให้ ถ้าแปลหน้าแม่ไว้แล้ว</li>
                    <li>ช่อง <strong>เนื้อหานี้เป็นภาษา</strong> ใช้ย้ายเนื้อหาไปอีกภาษา จะเปลี่ยนไม่ได้ถ้าภาษานั้นมีฉบับแปลอยู่แล้ว</li>
                    <li>ใน editor แบบคลาสสิก จะเห็นกล่อง <strong>ภาษา</strong> ด้านขวาแทน ใช้งานแบบเดียวกัน</li>
                </ul>
                <p class="isxl-guide-note"><strong>รายการใน admin:</strong> เมนู <strong>ทุกภาษา (All languages)</strong> บนแถบ admin ใช้กรองหน้ารายการให้เหลือภาษาเดียว เนื้อหาใหม่ที่สร้างตอนเลือกภาษาไว้จะเป็นภาษานั้น</p>
            </section>

            <section id="isxl-guide-terms" aria-labelledby="isxl-guide-terms-title">
                <h2 id="isxl-guide-terms-title">แปลหมวดหมู่ แท็ก และคุณสมบัติ</h2>
                <p>แปลหมวดหมู่ก่อนแปลเนื้อหา เพื่อให้ฉบับแปลผูกหมวดหมู่ภาษาเดียวกันได้ทันที</p>
                <?php $guide_figure( 'term-real.png', 'หน้าแก้ไขหมวดหมู่สินค้า มีช่องภาษาเลือกไทย และแถวฉบับแปลลิงก์ไปหมวดหมู่ภาษาอังกฤษ', 'หน้าจอจริง: ภาษาและฉบับแปลในหน้าแก้ไขหมวดหมู่', 1532, 296 ); ?>
                <ol>
                    <li>เปิดหน้าแก้ไขหมวดหมู่ แท็ก หรือคุณสมบัติสินค้า (เช่น สินค้า → คุณสมบัติ → ตั้งค่า terms)</li>
                    <li>ที่แถว <strong>ฉบับแปล (Translations)</strong> กด <strong>+ เพิ่ม</strong> ข้างภาษาที่ต้องการ</li>
                    <li>ระบบจะสร้างหมวดหมู่ใหม่ชื่อ <code>ชื่อเดิม (EN)</code> และ slug <code>slug-en</code> เพราะ WordPress ไม่ยอมให้ชื่อซ้ำ ให้แก้ชื่อและ slug เป็นภาษานั้นแล้วบันทึก</li>
                </ol>
                <p>ในหน้ารายการหมวดหมู่ก็มีคอลัมน์ธง ✓ ✎ + แบบเดียวกับหน้ารายการเนื้อหา ส่วนหมวดหมู่ที่สร้างใหม่ในหน้ารายการจะมีช่องเลือกภาษาให้</p>
            </section>

            <section id="isxl-guide-menus" aria-labelledby="isxl-guide-menus-title">
                <h2 id="isxl-guide-menus-title">เมนูแยกตามภาษา</h2>
                <p>แต่ละตำแหน่งเมนูของธีมจะมีอีกช่องสำหรับแต่ละภาษา เช่น <strong>Primary Menu (English)</strong></p>
                <?php $guide_figure( 'menus-real.png', 'รายการตำแหน่งเมนู มี Primary Menu และ Primary Menu (English) รวมถึงตำแหน่งอื่นที่มีภาษาอังกฤษต่อท้าย', 'หน้าจอจริง: ตำแหน่งเมนูแยกตามภาษาในหน้าเมนู', 1568, 386 ); ?>
                <ol>
                    <li>เปิด <strong>หน้าตา → เมนู</strong> แล้วสร้างเมนูของภาษาหลักตามปกติ ติ๊กตำแหน่ง เช่น Primary Menu</li>
                    <li>สร้างเมนูใหม่อีกอันสำหรับภาษาอังกฤษ ใส่หน้าฉบับภาษาอังกฤษ แล้วติ๊กตำแหน่ง <strong>Primary Menu (English)</strong></li>
                    <li>เปิด <code>/en/</code> เพื่อตรวจว่าเมนูเปลี่ยนตามภาษา</li>
                </ol>
                <p>ถ้าตำแหน่งของภาษาไหนเว้นว่างไว้ ภาษานั้นจะใช้เมนูของภาษาหลักแทน</p>
            </section>

            <section id="isxl-guide-switcher" aria-labelledby="isxl-guide-switcher-title">
                <h2 id="isxl-guide-switcher-title">ตัวสลับภาษา</h2>
                <p>ตัวสลับภาษาลิงก์ไปหน้าเดียวกันในภาษาอื่น ถ้าหน้านั้นยังไม่มีฉบับแปล จะลิงก์ไปหน้าแรกของภาษานั้นแทน มีให้เลือกใส่ 3 แบบ:</p>
                <dl>
                    <dt>ในเมนู</dt><dd>หน้าตา → เมนู → <strong>ลิงก์กำหนดเอง (Custom Links)</strong> ใส่ URL เป็น <code>#isxl-switcher</code> ชื่ออะไรก็ได้ แล้วเพิ่มเข้าเมนู หน้าเว็บจะแสดงเป็นรายการภาษาทั้งหมดแทน</dd>
                    <dt>บล็อก</dt><dd>เพิ่มบล็อก <strong>ตัวสลับภาษา (Language switcher)</strong> ในหน้า เทมเพลต หรือวิดเจ็ต ตั้งค่าได้ว่าจะแสดงแบบรายการหรือดรอปดาวน์ แสดงธงหรือชื่อ และซ่อนภาษาปัจจุบันไหม</dd>
                    <dt>Shortcode</dt><dd><code>[isxl_switcher]</code> หรือ <code>[isxl_switcher style="dropdown" flags="1" names="1" hide_current="0"]</code> ใช้ได้ในบล็อก Shortcode, Elementor หรือในธีม</dd>
                </dl>
            </section>

            <section id="isxl-guide-widgets" aria-labelledby="isxl-guide-widgets-title">
                <h2 id="isxl-guide-widgets-title">วิดเจ็ตและบล็อกตามภาษา</h2>
                <p>ทุกบล็อกในหน้า เทมเพลต และวิดเจ็ต ตั้งให้แสดงเฉพาะภาษาได้ เหมาะกับแบนเนอร์หรือข้อความที่ต่างกันแต่ละภาษาในพื้นที่ที่ใช้ร่วมกัน เช่น sidebar หรือ footer</p>
                <?php $guide_figure( 'block-visibility-real.png', 'แผงแสดงตามภาษาในแท็บ Block ของ Gutenberg มีช่องเลือกทุกภาษา', 'หน้าจอจริง: แผงแสดงตามภาษา (Language visibility)', 305, 156 ); ?>
                <ol>
                    <li>เลือกบล็อก แล้วเปิดแท็บ <strong>Block</strong> ทางขวา</li>
                    <li>ที่แผง <strong>แสดงตามภาษา (Language visibility)</strong> เลือกภาษาที่จะให้แสดงบล็อกนี้ ค่าเริ่มต้นคือทุกภาษา</li>
                </ol>
                <p>วิดเจ็ตแบบคลาสสิกมีช่อง <strong>แสดงในภาษา (Show in language)</strong> ท้ายฟอร์มวิดเจ็ต ใช้งานแบบเดียวกัน ใน editor ยังเห็นบล็อกครบทุกภาษา การซ่อนมีผลเฉพาะหน้าเว็บ</p>
            </section>

            <section id="isxl-guide-strings" aria-labelledby="isxl-guide-strings-title">
                <h2 id="isxl-guide-strings-title">แปลข้อความของเว็บ</h2>
                <p>แท็บ <strong>ข้อความ (Strings)</strong> ใช้แปลข้อความที่ไม่ได้อยู่ในเรื่องหรือหน้า เช่น ชื่อเว็บ คำโปรย รูปแบบวันที่ หัววิดเจ็ต ชื่อช่องทางชำระเงิน และข้อความอีเมลของ WooCommerce</p>
                <?php $guide_figure( 'strings-real.png', 'แท็บข้อความ มีตารางต้นฉบับและช่องแปลภาษาอังกฤษ ชื่อเว็บแปลเป็น PTN English', 'หน้าจอจริง: แท็บข้อความ (Strings)', 1176, 341 ); ?>
                <ol>
                    <li>ค้นหาข้อความ หรือเลือกกลุ่มจากช่องขวาบน</li>
                    <li>พิมพ์คำแปลในคอลัมน์ของแต่ละภาษา แล้วกด <strong>บันทึกคำแปล (Save translations)</strong></li>
                    <li>เว้นว่างไว้เพื่อใช้ข้อความต้นฉบับ</li>
                </ol>
                <h3>ข้อความจากธีม (Customizer)</h3>
                <p>การ์ด <strong>ข้อความของธีม (Theme texts)</strong> ด้านล่างแสดงข้อความที่ตั้งไว้ใน Customizer เช่น ข้อความ header, footer และปุ่ม ติ๊กรายการที่ต้องการแปลแล้วกดบันทึก รายการจะไปโผล่ในตารางด้านบนให้แปล</p>
                <p class="isxl-guide-note"><strong>ข้อความในตัวธีมและปลั๊กอิน:</strong> เช่น "Add to cart" หรือ "Read more" ไม่ต้องแปลที่นี่ ข้อความเหล่านี้เปลี่ยนตาม Locale ของภาษาจากไฟล์ .mo ของธีมหรือปลั๊กอินให้เอง ถ้ายังไม่เปลี่ยน แปลว่าธีมหรือปลั๊กอินนั้นไม่มีไฟล์แปลของภาษานั้น</p>
            </section>

            <section id="isxl-guide-woocommerce" aria-labelledby="isxl-guide-woocommerce-title">
                <h2 id="isxl-guide-woocommerce-title">WooCommerce</h2>
                <p>สินค้าแต่ละภาษาเป็นสินค้าแยกกัน แต่ใช้ข้อมูลร้านค้าชุดเดียวกัน เลือกว่าจะให้ข้อมูลไหนเหมือนกันทุกภาษาได้ในแท็บ <strong>WooCommerce</strong></p>
                <?php $guide_figure( 'woocommerce-real.png', 'แท็บ WooCommerce มีรายการกลุ่มข้อมูลที่ sync ทุกฉบับแปล เช่น ราคา สต็อก SKU น้ำหนัก ภาษี', 'หน้าจอจริง: เลือกข้อมูลที่ให้เหมือนกันทุกฉบับแปล', 1176, 363 ); ?>
                <ul>
                    <li><strong>ข้อมูลที่ติ๊กไว้</strong> จะคัดลอกจากสินค้าที่บันทึกไปยังทุกฉบับแปล แก้ฝั่งไหนก็ได้ อีกฝั่งจะเปลี่ยนตาม ส่วนชื่อ รายละเอียด และ slug แยกตามภาษาเสมอ</li>
                    <li><strong>สต็อก:</strong> มีคนสั่งซื้อจากภาษาไหน สต็อกทุกฉบับแปลลดตาม (ต้องเปิดการจัดการสต็อกใน WooCommerce → ตั้งค่า → สินค้า → คลังสินค้า)</li>
                    <li><strong>SKU:</strong> ฉบับแปลใช้ SKU เดียวกันได้</li>
                    <li><strong>สินค้ามีตัวเลือก (Variable):</strong> สร้างฉบับแปลแล้ว variation จะถูกสร้างตามให้เอง และใช้คุณสมบัติภาษาเดียวกัน แปลค่าคุณสมบัติ (เช่น ขนาด สี) ก่อน เพื่อให้ตัวเลือกแสดงเป็นภาษานั้น</li>
                    <li><strong>สินค้าแนะนำ (Upsell / Cross-sell):</strong> เปลี่ยนเป็นสินค้าภาษาเดียวกันให้เอง ถ้าแปลไว้แล้ว</li>
                </ul>
                <h3>หน้าร้านค้า ตะกร้า และชำระเงิน</h3>
                <ol>
                    <li>แปลหน้า <strong>ร้านค้า ตะกร้า ชำระเงิน บัญชีของฉัน และข้อกำหนด</strong> เหมือนหน้าทั่วไป</li>
                    <li>WooCommerce จะเลือกหน้าที่ตรงภาษาให้เอง ไม่ต้องตั้งค่าเพิ่ม</li>
                </ol>
                <ul>
                    <li>เมื่อผู้ชมสลับภาษา สินค้าในตะกร้าจะเปลี่ยนเป็นฉบับภาษานั้น</li>
                    <li>คำสั่งซื้อจะจำภาษาที่ลูกค้าใช้ตอนสั่ง อีเมลถึงลูกค้า (ยืนยันคำสั่งซื้อ จัดส่ง ใบแจ้งหนี้) ส่งเป็นภาษานั้น ส่วนอีเมลถึงร้านค้ายังเป็นภาษาของเว็บ</li>
                    <li>หัวเรื่อง หัวข้อ และข้อความเพิ่มเติมที่ตั้งเองใน WooCommerce → ตั้งค่า → อีเมล แปลได้ในแท็บข้อความ กลุ่ม <strong>อีเมล WooCommerce</strong></li>
                </ul>
            </section>

            <section id="isxl-guide-seo" aria-labelledby="isxl-guide-seo-title">
                <h2 id="isxl-guide-seo-title">URL และ SEO</h2>
                <ul>
                    <li><strong>URL:</strong> ภาษาหลักไม่มีคำนำหน้า ภาษาอื่นเป็น <code>/slug/</code> เช่น <code>/en/product/cement/</code> ถ้าเปิดเนื้อหาด้วยคำนำหน้าผิดภาษา ระบบจะพาไป URL ที่ถูกต้อง (301)</li>
                    <li><strong>hreflang:</strong> ทุกหน้าที่มีฉบับแปลจะมี <code>&lt;link rel="alternate" hreflang&gt;</code> ของทุกภาษา และ <code>x-default</code> ชี้ไปภาษาหลัก</li>
                    <li><strong>ภาษาของหน้า:</strong> <code>&lt;html lang&gt;</code> เปลี่ยนตาม Locale ของภาษา</li>
                    <li><strong>Sitemap:</strong> <code>/wp-sitemap.xml</code> มี URL ครบทุกภาษา</li>
                </ul>
                <p>หลังเพิ่มภาษาหรือเปลี่ยน slug ระบบจะสร้างกฎ URL ใหม่ให้เอง ถ้า <code>/en/</code> เปิดไม่ได้ ให้ไปที่ ตั้งค่า → ลิงก์ถาวร แล้วกดบันทึก 1 ครั้ง</p>
            </section>

            <section id="isxl-guide-developers" aria-labelledby="isxl-guide-developers-title">
                <h2 id="isxl-guide-developers-title">สำหรับนักพัฒนา</h2>
                <dl>
                    <dt><code>isxl_current_lang()</code></dt><dd>slug ของภาษาปัจจุบัน เช่น <code>th</code></dd>
                    <dt><code>isxl_get_post( $id, $lang )</code></dt><dd>ID ของฉบับแปล หรือ 0 ถ้ายังไม่มี (<code>isxl_get_term()</code> ใช้กับหมวดหมู่)</dd>
                    <dt><code>isxl_home_url( $lang )</code></dt><dd>URL หน้าแรกของภาษา</dd>
                    <dt><code>isxl_register_string( $text, $name, $group )</code> + <code>isxl__( $text )</code></dt><dd>ลงทะเบียนข้อความในธีมให้แปลได้ในแท็บข้อความ แล้วใช้ <code>isxl__()</code> หรือ <code>isxl_e()</code> แสดงคำแปล เรียก register ทุก request เช่นใน <code>init</code></dd>
                    <dt><code>isxl_the_languages()</code></dt><dd>ข้อมูลสำหรับทำตัวสลับภาษาเอง: slug, name, flag, url, current, exists</dd>
                    <dt>Hooks</dt><dd><code>isxl_post_types</code>, <code>isxl_taxonomies</code>, <code>isxl_duplicate_skip_meta</code>, <code>isxl_post_duplicated</code>, <code>isxl_term_duplicated</code>, <code>isxl_register_strings</code></dd>
                </dl>
            </section>

            <section id="isxl-guide-troubleshooting" aria-labelledby="isxl-guide-troubleshooting-title">
                <h2 id="isxl-guide-troubleshooting-title">แก้ปัญหาที่พบบ่อย</h2>
                <details open><summary>เปิด /en/ แล้วกลับไปหน้าแรกภาษาไทย</summary><p>หน้าแรก (ตั้งค่า → การอ่าน) ยังไม่มีฉบับภาษาอังกฤษ หรือฉบับแปลยังเป็นฉบับร่าง แปลหน้าแรกแล้วกดเผยแพร่</p></details>
                <details><summary>เนื้อหาเดิมหายจากหน้าเว็บหลังเปิดปลั๊กอิน</summary><p>เนื้อหานั้นยังไม่มีภาษา ไปแท็บภาษา แล้วกดปุ่มในการ์ด <strong>เนื้อหาที่ยังไม่มีภาษา</strong> เพื่อตั้งเป็นภาษาหลัก</p></details>
                <details><summary>ปุ่ม + เพิ่ม ใน editor กดไม่ได้</summary><p>มีการแก้ไขที่ยังไม่ได้บันทึก กดบันทึกก่อน ฉบับแปลจะคัดลอกจากเนื้อหาที่บันทึกล่าสุด</p></details>
                <details><summary>ฉบับแปลของสินค้าไม่มีหมวดหมู่</summary><p>หมวดหมู่นั้นยังไม่ได้แปลตอนสร้างฉบับแปล แปลหมวดหมู่ก่อน แล้วเลือกหมวดหมู่ภาษาเดียวกันในฉบับแปลเอง</p></details>
                <details><summary>สต็อกไม่ลดพร้อมกันทุกภาษา</summary><p>ตรวจว่าเปิดการจัดการสต็อกของร้าน (WooCommerce → ตั้งค่า → สินค้า → คลังสินค้า) และติ๊ก <strong>สต็อก</strong> ไว้ในแท็บ WooCommerce ของปลั๊กอินนี้</p></details>
                <details><summary>ข้อความของธีมไม่เปลี่ยนภาษา</summary><p>ถ้าเป็นข้อความที่ตั้งใน Customizer ให้ติ๊กในการ์ดข้อความของธีม แล้วแปลในแท็บข้อความ ถ้าเป็นข้อความในโค้ดธีม ธีมต้องมีไฟล์แปล (.mo) ของ Locale นั้น หรือให้นักพัฒนาใช้ <code>isxl_register_string()</code></p></details>
                <details><summary>ต้องการกลับไปใช้ Polylang</summary><p>ปิด InsightX Lang แล้วเปิด Polylang ข้อมูลภาษาของสองปลั๊กอินเก็บแยกกัน ไม่ทับกัน แต่ฉบับแปลที่สร้างใน InsightX Lang จะไม่ผูกกันใน Polylang</p></details>
            </section>
            <a class="isxl-guide-back-top" href="#isxl-guide-top">กลับไปด้านบน</a>
        </article>
    </div>
</div>
