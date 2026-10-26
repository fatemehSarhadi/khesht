<?php
/**
 * Classic meta boxes so editors can fill property / project / team / area-price fields from the WP panel.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', function () {
    foreach (khesht_meta_defs() as $post_type => $def) {
        add_meta_box(
            'khesht_fields',
            $def['title'],
            'khesht_render_meta_box',
            $post_type,
            'normal',
            'high',
            array('fields' => $def['fields'])
        );
    }
});

function khesht_location_options() {
    $terms = get_terms(array('taxonomy' => 'khesht_location', 'hide_empty' => false));
    $out = array();
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $out[$term->slug] = $term->name;
        }
    }
    return $out;
}

function khesht_render_meta_box($post, $box) {
    wp_nonce_field('khesht_save_meta', 'khesht_meta_nonce');
    echo '<table class="form-table" role="presentation" dir="rtl">';
    foreach ($box['args']['fields'] as $field) {
        $key = $field['key'];
        $value = get_post_meta($post->ID, $key, true);
        $name = 'khesht_meta[' . esc_attr($key) . ']';
        echo '<tr><th><label for="' . esc_attr($key) . '">' . esc_html($field['label']) . '</label></th><td>';
        switch ($field['type']) {
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr($key) . '" name="' . $name . '" value="1"' . checked((bool) $value, true, false) . '>';
                break;
            case 'select':
                echo '<select id="' . esc_attr($key) . '" name="' . $name . '"><option value="">—</option>';
                foreach ($field['options'] as $v => $label) {
                    echo '<option value="' . esc_attr($v) . '"' . selected($value, $v, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                break;
            case 'term':
                echo '<select id="' . esc_attr($key) . '" name="' . $name . '"><option value="">—</option>';
                foreach (khesht_location_options() as $v => $label) {
                    echo '<option value="' . esc_attr($v) . '"' . selected($value, $v, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select><p class="description">گزینه‌ها از «موقعیت» (منوی طبقه‌بندی‌ها) می‌آیند.</p>';
                break;
            case 'gallery':
                echo '<input type="hidden" class="khesht-gallery-ids" id="' . esc_attr($key) . '" name="' . $name . '" value="' . esc_attr($value) . '">';
                echo '<div class="khesht-gallery-preview" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px">';
                foreach (array_filter(array_map('intval', explode(',', (string) $value))) as $att_id) {
                    echo wp_get_attachment_image($att_id, array(70, 70));
                }
                echo '</div><button type="button" class="button khesht-gallery-pick">انتخاب تصاویر گالری</button>';
                break;
            case 'textarea':
                echo '<textarea id="' . esc_attr($key) . '" name="' . $name . '" rows="5" class="large-text">' . esc_textarea((string) $value) . '</textarea>';
                break;
            case 'number':
                echo '<input type="number" step="any" id="' . esc_attr($key) . '" name="' . $name . '" value="' . esc_attr($value) . '" class="regular-text" dir="ltr">';
                break;
            case 'url':
                echo '<input type="url" id="' . esc_attr($key) . '" name="' . $name . '" value="' . esc_attr($value) . '" class="regular-text" dir="ltr">';
                break;
            default:
                echo '<input type="text" id="' . esc_attr($key) . '" name="' . $name . '" value="' . esc_attr($value) . '" class="regular-text">';
        }
        echo '</td></tr>';
    }
    echo '</table>';
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['khesht_meta_nonce']) || !wp_verify_nonce($_POST['khesht_meta_nonce'], 'khesht_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $defs = khesht_meta_defs();
    $type = get_post_type($post_id);
    if (!isset($defs[$type])) {
        return;
    }
    $input = isset($_POST['khesht_meta']) && is_array($_POST['khesht_meta']) ? wp_unslash($_POST['khesht_meta']) : array();

    foreach ($defs[$type]['fields'] as $field) {
        $key = $field['key'];
        $raw = isset($input[$key]) ? $input[$key] : '';
        switch ($field['type']) {
            case 'checkbox':
                $value = $raw === '1' ? '1' : '';
                break;
            case 'number':
                $value = $raw === '' ? '' : (string) (float) $raw;
                break;
            case 'url':
                $value = esc_url_raw($raw);
                break;
            case 'textarea':
                $value = sanitize_textarea_field($raw);
                break;
            case 'select':
                $value = isset($field['options'][$raw]) ? $raw : '';
                break;
            case 'term':
                $value = sanitize_title($raw);
                break;
            case 'gallery':
                $value = implode(',', array_filter(array_map('intval', explode(',', (string) $raw))));
                break;
            default:
                $value = sanitize_text_field($raw);
        }
        if ($value === '') {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }
}, 10, 1);

add_action('admin_footer', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'post') {
        return;
    }
    wp_enqueue_media();
    ?>
    <script>
    (function () {
      document.querySelectorAll('.khesht-gallery-pick').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var cell = btn.parentNode;
          var input = cell.querySelector('.khesht-gallery-ids');
          var preview = cell.querySelector('.khesht-gallery-preview');
          var frame = wp.media({ title: 'گالری تصاویر', multiple: 'add', library: { type: 'image' } });
          frame.on('open', function () {
            var ids = (input.value || '').split(',').filter(Boolean);
            var sel = frame.state().get('selection');
            ids.forEach(function (id) { var a = wp.media.attachment(id); a.fetch(); sel.add(a); });
          });
          frame.on('select', function () {
            var ids = [];
            preview.innerHTML = '';
            frame.state().get('selection').each(function (a) {
              ids.push(a.id);
              var url = (a.attributes.sizes && a.attributes.sizes.thumbnail) ? a.attributes.sizes.thumbnail.url : a.attributes.url;
              var img = document.createElement('img');
              img.src = url; img.width = 70; img.height = 70; img.style.objectFit = 'cover';
              preview.appendChild(img);
            });
            input.value = ids.join(',');
          });
          frame.open();
        });
      });
    })();
    </script>
    <?php
});
