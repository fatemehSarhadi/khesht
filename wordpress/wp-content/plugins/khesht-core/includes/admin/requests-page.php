<?php
/**
 * Admin screen: "درخواست‌ها" — list, filter, change status, re-send SMS, add notes, delete, export CSV.
 */

if (!defined('ABSPATH')) {
    exit;
}

function khesht_requests_cap() {
    return 'edit_others_posts'; // editors and administrators
}

add_action('admin_menu', function () {
    $new = khesht_requests_new_count();
    $bubble = $new > 0 ? ' <span class="awaiting-mod">' . (int) $new . '</span>' : '';
    add_menu_page(
        'درخواست‌ها',
        'درخواست‌ها' . $bubble,
        khesht_requests_cap(),
        'khesht-requests',
        'khesht_render_requests_page',
        'dashicons-email-alt',
        4
    );
});

function khesht_requests_url($args = array()) {
    return add_query_arg($args, admin_url('admin.php?page=khesht-requests'));
}

/* ---------- actions (admin-post) ---------- */

add_action('admin_post_khesht_request_action', function () {
    if (!current_user_can(khesht_requests_cap())) {
        wp_die('دسترسی غیرمجاز');
    }
    check_admin_referer('khesht_request_action');

    // Save note button (textarea named notes[ID], button name khesht_save_note=ID)
    if (!empty($_POST['khesht_save_note'])) {
        $note_id = (int) $_POST['khesht_save_note'];
        $notes = isset($_POST['notes']) && is_array($_POST['notes']) ? $_POST['notes'] : array();
        $note = isset($notes[$note_id]) ? sanitize_textarea_field(wp_unslash($notes[$note_id])) : '';
        khesht_request_update($note_id, array('note' => $note));
        wp_safe_redirect(add_query_arg('khesht_done', '1', wp_get_referer() ? wp_get_referer() : khesht_requests_url()));
        exit;
    }

    $act = isset($_REQUEST['do']) ? sanitize_key($_REQUEST['do']) : '';
    $ids = array();
    if (!empty($_REQUEST['ids']) && is_array($_REQUEST['ids'])) {
        $ids = array_map('intval', $_REQUEST['ids']);
    } elseif (!empty($_REQUEST['id'])) {
        $ids = array((int) $_REQUEST['id']);
    }
    $statuses = khesht_request_statuses();

    foreach ($ids as $id) {
        if ($id <= 0) {
            continue;
        }
        if (isset($statuses[$act])) {
            khesht_request_update($id, array('status' => $act));
        } elseif ($act === 'delete') {
            khesht_request_delete($id);
        } elseif ($act === 'resend_sms') {
            khesht_send_request_sms($id);
        }
    }

    $back = wp_get_referer() ? wp_get_referer() : khesht_requests_url();
    wp_safe_redirect(add_query_arg('khesht_done', '1', $back));
    exit;
});

add_action('admin_post_khesht_export_requests', function () {
    if (!current_user_can(khesht_requests_cap())) {
        wp_die('دسترسی غیرمجاز');
    }
    check_admin_referer('khesht_export_requests');
    global $wpdb;
    $table = khesht_requests_table();
    $type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
    if ($type && isset(khesht_request_types()[$type])) {
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE type = %s ORDER BY id DESC", $type), ARRAY_A);
    } else {
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
    }

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="khesht-requests-' . gmdate('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads Persian correctly
    fputcsv($out, array('id', 'type', 'jalali_date', 'name', 'phone', 'subject', 'description', 'status', 'sms_status', 'note'));
    foreach ($rows as $r) {
        fputcsv($out, array(
            $r['id'], $r['type'], khesht_format_jalali($r['created_at']), $r['name'], $r['phone'],
            $r['subject'], $r['description'], $r['status'], $r['sms_status'], $r['note'],
        ));
    }
    fclose($out);
    exit;
});

/* ---------- list table ---------- */

function khesht_render_requests_page() {
    if (!current_user_can(khesht_requests_cap())) {
        return;
    }
    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }
    require_once KHESHT_CORE_DIR . 'includes/admin/class-requests-table.php';

    $type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
    $types = khesht_request_types();
    $table = new Khesht_Requests_List_Table($type);
    $table->prepare_items();
    ?>
    <div class="wrap" dir="rtl">
      <h1 class="wp-heading-inline">درخواست‌ها</h1>
      <a class="page-title-action" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=khesht_export_requests' . ($type ? '&type=' . $type : '')), 'khesht_export_requests')); ?>">خروجی CSV</a>
      <hr class="wp-header-end">
      <?php if (isset($_GET['khesht_done'])) : ?>
        <div class="notice notice-success is-dismissible"><p>انجام شد.</p></div>
      <?php endif; ?>
      <ul class="subsubsub">
        <li><a href="<?php echo esc_url(khesht_requests_url()); ?>" class="<?php echo $type === '' ? 'current' : ''; ?>">همه</a> |</li>
        <?php $i = 0; foreach ($types as $key => $label) : $i++; ?>
          <li><a href="<?php echo esc_url(khesht_requests_url(array('type' => $key))); ?>" class="<?php echo $type === $key ? 'current' : ''; ?>"><?php echo esc_html($label); ?> <span class="count">(<?php echo (int) khesht_requests_new_count($key); ?> جدید)</span></a><?php echo $i < count($types) ? ' |' : ''; ?></li>
        <?php endforeach; ?>
      </ul>
      <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="khesht-requests">
        <?php if ($type) : ?><input type="hidden" name="type" value="<?php echo esc_attr($type); ?>"><?php endif; ?>
        <?php $table->search_box('جستجو (نام، شماره، موضوع)', 'khesht-req'); ?>
      </form>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="khesht_request_action">
        <?php wp_nonce_field('khesht_request_action'); ?>
        <?php $table->display(); ?>
      </form>
    </div>
    <?php
}
