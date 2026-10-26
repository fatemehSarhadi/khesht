<?php
/**
 * Site settings (Settings -> Khesht): contact info, social links, SMS destination, hero images.
 * Secrets (API keys) stay in environment variables / wp-config.php, never in this screen.
 */

if (!defined('ABSPATH')) {
    exit;
}

function khesht_default_settings() {
    return array(
        'phone' => '09122615643',
        'address' => 'البرز، جاده قدیم کرج قزوین، سهیلیه، کیلومتر ۸، بلوار اصلی، نبش کوچه نسترن شرقی ۴',
        'instagram' => 'https://www.instagram.com/Khesht.123',
        'telegram' => 'https://t.me/Khesht.123',
        'bale' => 'https://ble.ir/Khesht.123',
        'sms_to' => '09122615643',
        'copyright' => '© All rights of this website are reserved for Khesht.',
        'heroes' => array(),
        'type_phones' => array(),
        'ceo_name' => 'مهندس سعید نوروزی',
        'ceo_title' => 'مدیر عامل مجموعه خشت',
        'ceo_photo' => '',
        'ceo_bio' => '',
        'reviews_auto_approve' => '',
    );
}

function khesht_hero_slots() {
    return array(
        'home1' => 'صفحه اصلی — اسلاید ۱',
        'home2' => 'صفحه اصلی — اسلاید ۲',
        'home3' => 'صفحه اصلی — اسلاید ۳',
        'amlak' => 'املاک',
        'sakht' => 'ساخت‌وساز',
        'shahrak' => 'شهرک‌سازی و پروژه‌ها',
        'mag' => 'مجله',
        'bazar' => 'بازار و تحلیل',
        'about' => 'درباره ما',
        'card' => 'تماس با ما',
        'hamkari' => 'همکاری',
    );
}

function khesht_settings() {
    $saved = get_option('khesht_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    return array_merge(khesht_default_settings(), $saved);
}

function khesht_setting($key, $default = '') {
    $all = khesht_settings();
    return isset($all[$key]) ? $all[$key] : $default;
}

/** Environment variable wins, then the admin setting. */
function khesht_setting_value($key, $env_key) {
    $env = khesht_env($env_key, '');
    if ($env !== '') {
        return $env;
    }
    return (string) khesht_setting($key, '');
}

add_action('admin_menu', function () {
    add_options_page('تنظیمات خشت', 'Khesht (خشت)', 'manage_options', 'khesht-settings', 'khesht_render_settings');
});

add_action('admin_init', function () {
    register_setting('khesht_settings_group', 'khesht_settings', array(
        'type' => 'array',
        'sanitize_callback' => 'khesht_sanitize_settings',
        'default' => khesht_default_settings(),
    ));
});

function khesht_sanitize_settings($input) {
    $input = is_array($input) ? $input : array();
    $out = khesht_default_settings();
    foreach (array('phone', 'sms_to', 'copyright') as $key) {
        $out[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : '';
    }
    $out['address'] = isset($input['address']) ? sanitize_textarea_field($input['address']) : '';
    foreach (array('instagram', 'telegram', 'bale') as $key) {
        $out[$key] = isset($input[$key]) ? esc_url_raw($input[$key]) : '';
    }
    foreach (array('ceo_name', 'ceo_title') as $key) {
        $out[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : '';
    }
    $out['ceo_photo'] = isset($input['ceo_photo']) ? esc_url_raw($input['ceo_photo']) : '';
    $out['ceo_bio'] = isset($input['ceo_bio']) ? wp_kses_post($input['ceo_bio']) : '';
    $out['reviews_auto_approve'] = !empty($input['reviews_auto_approve']) ? '1' : '';
    $out['type_phones'] = array();
    if (!empty($input['type_phones']) && is_array($input['type_phones'])) {
        foreach ($input['type_phones'] as $slug => $phone) {
            $phone = sanitize_text_field($phone);
            if ($phone !== '') {
                $out['type_phones'][sanitize_key($slug)] = $phone;
            }
        }
    }
    $out['heroes'] = array();
    foreach (array_keys(khesht_hero_slots()) as $slot) {
        if (!empty($input['heroes'][$slot])) {
            $out['heroes'][$slot] = esc_url_raw($input['heroes'][$slot]);
        }
    }
    return $out;
}

function khesht_render_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }
    wp_enqueue_media();
    $s = khesht_settings();
    $name = 'khesht_settings';
    ?>
    <div class="wrap" dir="rtl">
      <h1>تنظیمات خشت</h1>
      <form method="post" action="options.php">
        <?php settings_fields('khesht_settings_group'); ?>

        <h2>اطلاعات تماس</h2>
        <table class="form-table" role="presentation">
          <tr><th><label for="k-phone">تلفن</label></th><td><input id="k-phone" class="regular-text" dir="ltr" name="<?php echo esc_attr($name); ?>[phone]" value="<?php echo esc_attr($s['phone']); ?>"></td></tr>
          <tr><th><label for="k-address">نشانی</label></th><td><textarea id="k-address" class="large-text" rows="2" name="<?php echo esc_attr($name); ?>[address]"><?php echo esc_textarea($s['address']); ?></textarea></td></tr>
          <tr><th><label for="k-instagram">اینستاگرام</label></th><td><input id="k-instagram" class="regular-text" dir="ltr" name="<?php echo esc_attr($name); ?>[instagram]" value="<?php echo esc_attr($s['instagram']); ?>"></td></tr>
          <tr><th><label for="k-telegram">تلگرام</label></th><td><input id="k-telegram" class="regular-text" dir="ltr" name="<?php echo esc_attr($name); ?>[telegram]" value="<?php echo esc_attr($s['telegram']); ?>"></td></tr>
          <tr><th><label for="k-bale">بله</label></th><td><input id="k-bale" class="regular-text" dir="ltr" name="<?php echo esc_attr($name); ?>[bale]" value="<?php echo esc_attr($s['bale']); ?>"></td></tr>
          <tr><th><label for="k-copy">متن کپی‌رایت (انگلیسی)</label></th><td><input id="k-copy" class="large-text" dir="ltr" name="<?php echo esc_attr($name); ?>[copyright]" value="<?php echo esc_attr($s['copyright']); ?>"></td></tr>
        </table>

        <h2>پیامک</h2>
        <table class="form-table" role="presentation">
          <tr>
            <th><label for="k-sms">شماره‌ی دریافت پیامک درخواست‌ها</label></th>
            <td>
              <input id="k-sms" class="regular-text" dir="ltr" name="<?php echo esc_attr($name); ?>[sms_to]" value="<?php echo esc_attr($s['sms_to']); ?>">
              <p class="description">فرم‌های مشاوره و همکاری به این شماره پیامک می‌شوند. اگر متغیر محیطی <code>KHESHT_SMS_TO</code> تنظیم شده باشد، همان اولویت دارد.</p>
              <p class="description">سرویس پیامک: <code><?php echo esc_html(khesht_env('KHESHT_SMS_ADAPTER', 'mock')); ?></code> (کلید API فقط در wp-config.php یا متغیر محیطی تنظیم می‌شود).</p>
            </td>
          </tr>
        </table>

        <h2>شماره‌ی تماس هر دسته‌ی ملک</h2>
        <p class="description">در صفحه‌ی هر ملک، دکمه‌ی تماس با شماره‌ی دسته‌ی همان ملک ساخته می‌شود. اگر خالی باشد شماره‌ی اصلی (بالا) استفاده می‌شود. اگر در خود فایل ملک شماره‌ی اختصاصی وارد شود، آن اولویت دارد.</p>
        <table class="form-table" role="presentation">
          <?php
          $type_terms = get_terms(array('taxonomy' => 'khesht_property_type', 'hide_empty' => false));
          if (!is_wp_error($type_terms)) :
              foreach ($type_terms as $term) :
                  $tp = isset($s['type_phones'][$term->slug]) ? $s['type_phones'][$term->slug] : ''; ?>
            <tr><th><label for="tp-<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></label></th>
              <td><input id="tp-<?php echo esc_attr($term->slug); ?>" class="regular-text" dir="ltr" name="<?php echo esc_attr($name); ?>[type_phones][<?php echo esc_attr($term->slug); ?>]" value="<?php echo esc_attr($tp); ?>"></td></tr>
          <?php endforeach; endif; ?>
        </table>

        <h2>مهندس نوروزی (صفحه‌ی معرفی)</h2>
        <table class="form-table" role="presentation">
          <tr><th><label for="k-ceo-name">نام</label></th><td><input id="k-ceo-name" class="regular-text" name="<?php echo esc_attr($name); ?>[ceo_name]" value="<?php echo esc_attr($s['ceo_name']); ?>"></td></tr>
          <tr><th><label for="k-ceo-title">عنوان</label></th><td><input id="k-ceo-title" class="regular-text" name="<?php echo esc_attr($name); ?>[ceo_title]" value="<?php echo esc_attr($s['ceo_title']); ?>"></td></tr>
          <tr><th>عکس</th><td>
            <input type="url" class="regular-text khesht-hero-url" dir="ltr" name="<?php echo esc_attr($name); ?>[ceo_photo]" value="<?php echo esc_attr($s['ceo_photo']); ?>">
            <button type="button" class="button khesht-pick-image">انتخاب از کتابخانه</button>
          </td></tr>
          <tr><th>متن معرفی</th><td><?php wp_editor($s['ceo_bio'], 'khesht_ceo_bio', array('textarea_name' => $name . '[ceo_bio]', 'textarea_rows' => 10, 'media_buttons' => false)); ?></td></tr>
        </table>
        <p class="description">مطالب وبلاگ مهندس نوروزی: نوشته‌ی جدید بسازید و دسته‌ی «وبلاگ مهندس نوروزی» را انتخاب کنید.</p>

        <h2>تجربه مشتریان</h2>
        <table class="form-table" role="presentation">
          <tr><th>انتشار نظرها</th><td><label><input type="checkbox" name="<?php echo esc_attr($name); ?>[reviews_auto_approve]" value="1" <?php checked($s['reviews_auto_approve'], '1'); ?>> نظرهای جدید بدون بررسی منتشر شوند (پیشنهاد: خاموش، تا هر نظر را قبل از انتشار تأیید کنید)</label></td></tr>
        </table>

        <h2>تصاویر هیرو (بالای صفحه‌ها)</h2>
        <p class="description">اگر خالی باشد، تصویر پیش‌فرض سایت نمایش داده می‌شود. پیشنهاد: عرض حداقل ۱۹۲۰ پیکسل.</p>
        <table class="form-table" role="presentation">
          <?php foreach (khesht_hero_slots() as $slot => $label) :
              $url = isset($s['heroes'][$slot]) ? $s['heroes'][$slot] : ''; ?>
            <tr>
              <th><?php echo esc_html($label); ?></th>
              <td>
                <input type="url" class="regular-text khesht-hero-url" dir="ltr" name="<?php echo esc_attr($name); ?>[heroes][<?php echo esc_attr($slot); ?>]" value="<?php echo esc_attr($url); ?>">
                <button type="button" class="button khesht-pick-image">انتخاب از کتابخانه</button>
                <?php if ($url) : ?><img src="<?php echo esc_url($url); ?>" alt="" style="max-height:48px;vertical-align:middle;margin-inline-start:10px;"><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>

        <?php submit_button('ذخیره تنظیمات'); ?>
      </form>

      <h2>متغیرهای محیطی (کلیدها و اتصال‌ها)</h2>
      <ul style="list-style:disc;margin-inline-start:20px">
        <li><code>KHESHT_FRONTEND_ORIGIN</code>, <code>KHESHT_FRONTEND_REBUILD_URL</code>, <code>KHESHT_WEBHOOK_SECRET</code></li>
        <li><code>KHESHT_SMS_ADAPTER</code> (mock | kavenegar), <code>KHESHT_SMS_API_KEY</code>, <code>KHESHT_SMS_SENDER</code>, <code>KHESHT_SMS_TO</code></li>
        <li><code>KHESHT_MARKET_ADAPTER</code> (mock | navasan), <code>KHESHT_MARKET_API_KEY</code></li>
      </ul>
    </div>
    <script>
    (function () {
      document.querySelectorAll('.khesht-pick-image').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var input = btn.parentNode.querySelector('.khesht-hero-url');
          var frame = wp.media({ title: 'انتخاب تصویر', multiple: false, library: { type: 'image' } });
          frame.on('select', function () {
            var att = frame.state().get('selection').first().toJSON();
            input.value = att.url;
          });
          frame.open();
        });
      });
    })();
    </script>
    <?php
}
