<?php
/**
 * Admin screen "گالری": pick many images, drag to reorder, remove. Shown on the About page.
 * Captions come from the image caption/title in the Media Library.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page('گالری خشت', 'گالری', 'upload_files', 'khesht-gallery', 'khesht_render_gallery_page', 'dashicons-format-gallery', 6);
});

add_action('admin_init', function () {
    register_setting('khesht_gallery_group', 'khesht_gallery_ids', array(
        'type' => 'string',
        'sanitize_callback' => function ($value) {
            return implode(',', array_filter(array_map('intval', explode(',', (string) $value))));
        },
        'default' => '',
    ));
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_khesht-gallery') {
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
    }
});

function khesht_render_gallery_page() {
    if (!current_user_can('upload_files')) {
        return;
    }
    $value = (string) get_option('khesht_gallery_ids', '');
    $ids = array_filter(array_map('intval', explode(',', $value)));
    ?>
    <div class="wrap" dir="rtl">
      <h1>گالری</h1>
      <p>تصاویری که این‌جا انتخاب می‌کنید در بخش «گالری» صفحه‌ی «درباره ما» نمایش داده می‌شوند. ترتیب را با کشیدن و رها کردن تغییر دهید.</p>
      <form method="post" action="options.php">
        <?php settings_fields('khesht_gallery_group'); ?>
        <input type="hidden" id="khesht-gallery-value" name="khesht_gallery_ids" value="<?php echo esc_attr($value); ?>">
        <ul id="khesht-gallery-list" style="display:flex;flex-wrap:wrap;gap:12px;list-style:none;padding:0;margin:16px 0">
          <?php foreach ($ids as $id) : ?>
            <li data-id="<?php echo (int) $id; ?>" style="position:relative;cursor:move;border:1px solid #c3c4c7;background:#fff;padding:4px">
              <?php echo wp_get_attachment_image($id, array(120, 120), false, array('style' => 'display:block;object-fit:cover;width:120px;height:120px')); ?>
              <button type="button" class="khesht-remove" aria-label="حذف" style="position:absolute;top:2px;left:2px;border:0;background:#b32d2e;color:#fff;border-radius:50%;width:22px;height:22px;cursor:pointer">×</button>
            </li>
          <?php endforeach; ?>
        </ul>
        <p>
          <button type="button" class="button" id="khesht-gallery-add">افزودن تصویر</button>
          <?php submit_button('ذخیره گالری', 'primary', 'submit', false); ?>
        </p>
      </form>
    </div>
    <script>
    jQuery(function ($) {
      var $list = $('#khesht-gallery-list');
      var $input = $('#khesht-gallery-value');
      function sync() {
        var ids = [];
        $list.children('li').each(function () { ids.push($(this).data('id')); });
        $input.val(ids.join(','));
      }
      $list.sortable({ update: sync });
      $list.on('click', '.khesht-remove', function () { $(this).closest('li').remove(); sync(); });
      $('#khesht-gallery-add').on('click', function () {
        var frame = wp.media({ title: 'انتخاب تصاویر گالری', multiple: 'add', library: { type: 'image' } });
        frame.on('select', function () {
          frame.state().get('selection').each(function (a) {
            if ($list.find('li[data-id="' + a.id + '"]').length) { return; }
            var url = (a.attributes.sizes && a.attributes.sizes.thumbnail) ? a.attributes.sizes.thumbnail.url : a.attributes.url;
            var $li = $('<li style="position:relative;cursor:move;border:1px solid #c3c4c7;background:#fff;padding:4px"></li>').attr('data-id', a.id).data('id', a.id);
            $li.append($('<img>').attr('src', url).css({ display: 'block', objectFit: 'cover', width: 120, height: 120 }));
            $li.append('<button type="button" class="khesht-remove" aria-label="حذف" style="position:absolute;top:2px;left:2px;border:0;background:#b32d2e;color:#fff;border-radius:50%;width:22px;height:22px;cursor:pointer">×</button>');
            $list.append($li);
          });
          sync();
        });
        frame.open();
      });
    });
    </script>
    <?php
}
