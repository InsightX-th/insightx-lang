# InsightX Lang (`insightx-lang`)

ปลั๊กอินหลายภาษาแบบเบาสำหรับ WordPress + WooCommerce ใช้แทน Polylang + Hyyan WooPoly ได้ในตัวเดียว หน้า admin หน้าตาเหมือน InsightX AllHub

คู่มือพร้อมภาพหน้าจอจริง: [อ่านคู่มือการใช้งาน](docs/user-guide.md) หรือเปิด **InsightX Lang → User Guide** ใน WordPress Admin

## ทำอะไรได้บ้าง

- **URL แบบโฟลเดอร์:** ภาษาหลักไม่มี prefix (`/about/`) ภาษาอื่นเป็น `/en/about/`
- **แปลเนื้อหา:** เรื่อง หน้า สินค้า Reusable block หมวดหมู่ แท็ก และคุณสมบัติสินค้า (`pa_*`) กด "+ เพิ่ม" แล้วได้ฉบับร่างที่คัดลอกเนื้อหามาให้แปลต่อ (Gutenberg และ Elementor ก็คัดลอกได้)
- **Gutenberg:** แผง "ภาษา" ใน sidebar ทุกบล็อกมี "แสดงตามภาษา" มีบล็อก "ตัวสลับภาษา" และ Query Loop ใน editor แสดงเฉพาะภาษาของหน้านั้น
- **เมนูและวิดเจ็ต:** ตำแหน่งเมนูแยกตามภาษา (`primary___en`) และตั้งวิดเจ็ตให้แสดงเฉพาะภาษาได้
- **ข้อความ:** ชื่อเว็บ คำโปรย รูปแบบวันที่ หัววิดเจ็ต ข้อความ Customizer และข้อความอีเมลหรือช่องทางชำระเงินของ WooCommerce ส่วนธีมและปลั๊กอินที่มี `.mo` จะเปลี่ยนตาม locale ให้เอง
- **SEO:** มี `hreflang` และ `x-default` ส่วน `<html lang>` และ `wp-sitemap.xml` มีครบทุกภาษา
- **WooCommerce:**
  - sync ราคา สต็อก SKU น้ำหนักและขนาด ภาษี ประเภทสินค้า upsell คุณสมบัติ และรูป ให้ทุกฉบับแปล (เลือกกลุ่มที่ต้องการได้)
  - สร้าง variation ให้ฉบับแปลอัตโนมัติ
  - เมื่อสต็อกลดจากออเดอร์ ทุกฉบับแปลลดตาม
  - หน้า Shop, Cart, Checkout และ My account เปลี่ยนตามภาษา
  - ตะกร้าเปลี่ยนสินค้าเป็นภาษาที่ผู้ชมใช้อยู่
  - ออเดอร์จำภาษาไว้ และส่งอีเมลลูกค้าเป็นภาษานั้น
  - รองรับ HPOS และ block checkout

## ติดตั้ง

1. **ปิด Polylang และ Hyyan WooPoly ก่อน** (ถ้ายังเปิดอยู่ ปลั๊กอินนี้จะไม่ทำงานและขึ้นแจ้งเตือน)
2. เปิดใช้ InsightX Lang → เมนู **InsightX Lang** → เพิ่มภาษาแล้วบันทึก ครั้งแรกระบบจะตั้งเนื้อหาเดิมทั้งหมดเป็นภาษาหลักให้
3. ไปที่ตั้งค่า → ลิงก์ถาวร แล้วกดบันทึก 1 ครั้ง (ปกติระบบ flush ให้เอง)

## API สำหรับธีม

```php
isxl_current_lang();                     // 'th'
isxl_get_post( $id, 'en' );              // ID ของฉบับแปล หรือ 0
isxl_home_url( 'en' );                   // https://site/en/
isxl_register_string( 'โทรหาเรา', 'Call CTA', 'Header' );
echo esc_html( isxl__( 'โทรหาเรา' ) );
foreach ( isxl_the_languages() as $l ) { /* slug, name, flag, url, current, exists */ }
```

Hooks: `isxl_post_types`, `isxl_taxonomies`, `isxl_duplicate_skip_meta`, `isxl_post_duplicated`, `isxl_term_duplicated`, `isxl_register_strings`

## ข้อมูลที่เก็บ

| อะไร | ที่เก็บ |
|---|---|
| รายการภาษา | option `isxl_languages` |
| ภาษาของ post | taxonomy `isxl_language` |
| ภาษาของ term | term meta `_isxl_lang` |
| กลุ่มคำแปล | meta `_isxl_group` (UUID เดียวกันทั้งกลุ่ม) |
| คำแปลข้อความ | option `isxl_strings` |
| ภาษาของออเดอร์ | order meta `_isxl_lang` |

## ยังไม่รองรับ

แปลไฟล์สื่อ, แปล slug ของ CPT/taxonomy base และ endpoint ของ Woo, URL แบบ subdomain/domain, แปลอัตโนมัติ, import จาก Polylang

## พัฒนา

```bash
php tests/run.php                       # test URL helper (ไม่ต้องใช้ WP)
wp i18n make-json languages/insightx-lang-th.po --no-purge   # หลังแก้ .po
msgfmt -o languages/insightx-lang-th.mo languages/insightx-lang-th.po
```

## ออกเวอร์ชันใหม่

แก้เลขเวอร์ชันใน header และ `ISXL_VERSION` ของ `insightx-lang.php` ให้ตรงกับหัวข้อบนสุดของ `CHANGELOG.md` แล้ว commit และสร้าง tag `vX.Y.Z` เมื่อ push tag workflow จะรันทดสอบ สร้าง zip ที่ติดตั้งได้ และแนบ zip กับ GitHub Release เพื่อให้ตัวตรวจอัปเดตของปลั๊กอินใช้งาน

## License

GPL-3.0-or-later
