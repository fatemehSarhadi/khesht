<?php
/**
 * Admin: "تجربه مشتریان" — review moderation and the customers list (CRM).
 */

if (!defined('ABSPATH')) {
    exit;
}

function khesht_crm_cap() {
    return 'edit_others_posts';
}

function khesht_reviews_pending_count() {
    global $wpdb;
    $t = khesht_reviews_table();
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE status = 'pending'");
}

add_action('admin_menu', function () {
    $pending = khesht_reviews_pending_count();
    $bubble = $pending > 0 ? ' <span class="awaiting-mod">' . (int) $pending . '</span>' : '';
    add_menu_page('تجربه مشتریان', 'تجربه مشتریان' . $bubble, khesht_crm_cap(), 'khesht-reviews', 'khesht_render_reviews_page', 'dashicons-star-filled', 5);
    add_submenu_page('khesht-reviews', 'نظرات مشتریان', 'نظرات', khesht_crm_cap(), 'khesht-reviews', 'khesht_render_reviews_page');
    add_submenu_page('khesht-reviews', 'مشتریان (CRM)', 'مشتریان (CRM)', khesht_crm_cap(), 'khesht-customers', 'khesht_render_customers_page');
});

function khesht_crm_url($page, $args = array()) {
    return add_query_arg($args, admin_url('admin.php?page=' . $page));
}

function khesht_crm_action_url($do, $id, $back_page) {
    return wp_nonce_url(
        admin_url('admin-post.php?action=khesht_crm_action&do=' . rawurlencode($do) . '&id=' . (int) $id . '&back=' . rawurlencode($back_page)),
        'khesht_crm_action'
    );
}

/* ---------- actions ---------- */

add_action('admin_post_khesht_crm_action', function () {
    if (!current_user_can(khesht_crm_cap())) {
        wp_die('دسترسی غیرمجاز');
    }
    check_admin_referer('khesht_crm_action');
    global $wpdb;
    $reviews = khesht_reviews_table();
    $customers = khesht_customers_table();
    $back = isset($_REQUEST['back']) ? sanitize_key($_REQUEST['back']) : 'khesht-reviews';
    $back_url = wp_get_referer() ? wp_get_referer() : khesht_crm_url($back);

    if (!empty($_POST['khesht_save_reply'])) {
        $rid = (int) $_POST['khesht_save_reply'];
        $replies = isset($_POST['replies']) && is_array($_POST['replies']) ? $_POST['replies'] : array();
        $text = isset($replies[$rid]) ? sanitize_textarea_field(wp_unslash($replies[$rid])) : '';
        $wpdb->update($reviews, array('admin_reply' => $text), array('id' => $rid));
        wp_safe_redirect(add_query_arg('khesht_done', '1', $back_url));
        exit;
    }
    if (!empty($_POST['khesht_save_note'])) {
        $cid = (int) $_POST['khesht_save_note'];
        $notes = isset($_POST['notes']) && is_array($_POST['notes']) ? $_POST['notes'] : array();
        $text = isset($notes[$cid]) ? sanitize_textarea_field(wp_unslash($notes[$cid])) : '';
        $wpdb->update($customers, array('note' => $text), array('id' => $cid));
        wp_safe_redirect(add_query_arg('khesht_done', '1', $back_url));
        exit;
    }

    $do = isset($_REQUEST['do']) ? sanitize_key($_REQUEST['do']) : '';
    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    if ($id > 0) {
        switch ($do) {
            case 'approve':
                $wpdb->update($reviews, array('status' => 'approved'), array('id' => $id));
                break;
            case 'reject':
                $wpdb->update($reviews, array('status' => 'rejected'), array('id' => $id));
                break;
            case 'delete_review':
                $wpdb->delete($reviews, array('id' => $id), array('%d'));
                break;
            case 'block':
                $wpdb->update($customers, array('status' => 'blocked'), array('id' => $id));
                break;
            case 'unblock':
                $wpdb->update($customers, array('status' => 'active', 'fail_count' => 0, 'locked_until' => null), array('id' => $id));
                break;
            case 'resend_password':
                $c = khesht_customer_by_id($id);
                if ($c) {
                    khesht_issue_password($c);
                }
                break;
            case 'delete_customer':
                $wpdb->delete($reviews, array('customer_id' => $id), array('%d'));
                $wpdb->delete($customers, array('id' => $id), array('%d'));
                break;
        }
    }
    wp_safe_redirect(add_query_arg('khesht_done', '1', $back_url));
    exit;
});

add_action('admin_post_khesht_export_customers', function () {
    if (!current_user_can(khesht_crm_cap())) {
        wp_die('دسترسی غیرمجاز');
    }
    check_admin_referer('khesht_export_customers');
    global $wpdb;
    $c = khesht_customers_table();
    $r = khesht_reviews_table();
    $q = khesht_requests_table();
    $rows = $wpdb->get_results(
        "SELECT c.*, (SELECT COUNT(*) FROM $q x WHERE x.phone = c.phone) AS req_count,
                (SELECT COUNT(*) FROM $r v WHERE v.customer_id = c.id) AS rev_count
         FROM $c c ORDER BY c.id DESC",
        ARRAY_A
    );
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="khesht-customers-' . gmdate('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('id', 'name', 'phone', 'status', 'registered_jalali', 'last_login_jalali', 'requests', 'reviews', 'note'));
    foreach ($rows as $row) {
        fputcsv($out, array(
            $row['id'], $row['name'], $row['phone'], $row['status'],
            khesht_format_jalali($row['created_at']),
            $row['last_login_at'] ? khesht_format_jalali($row['last_login_at']) : '',
            $row['req_count'], $row['rev_count'], $row['note'],
        ));
    }
    fclose($out);
    exit;
});

/* ---------- pages ---------- */

function khesht_crm_notice() {
    if (isset($_GET['khesht_done'])) {
        echo '<div class="notice notice-success is-dismissible"><p>انجام شد.</p></div>';
    }
}

function khesht_render_reviews_page() {
    if (!current_user_can(khesht_crm_cap())) {
        return;
    }
    global $wpdb;
    $reviews = khesht_reviews_table();
    $customers = khesht_customers_table();
    $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
    $allowed = array('pending' => 'در انتظار تأیید', 'approved' => 'منتشرشده', 'rejected' => 'ردشده');
    $per = 20;
    $paged = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);

    $where = isset($allowed[$status]) ? $wpdb->prepare('WHERE r.status = %s', $status) : '';
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $reviews r $where");
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.*, c.name, c.phone FROM $reviews r LEFT JOIN $customers c ON c.id = r.customer_id $where ORDER BY r.id DESC LIMIT %d OFFSET %d",
            $per,
            ($paged - 1) * $per
        ),
        ARRAY_A
    );
    ?>
    <div class="wrap" dir="rtl">
      <h1>نظرات مشتریان</h1>
      <?php khesht_crm_notice(); ?>
      <ul class="subsubsub">
        <li><a href="<?php echo esc_url(khesht_crm_url('khesht-reviews')); ?>" class="<?php echo $status === '' ? 'current' : ''; ?>">همه</a> |</li>
        <?php $i = 0; foreach ($allowed as $key => $label) : $i++; ?>
          <li><a href="<?php echo esc_url(khesht_crm_url('khesht-reviews', array('status' => $key))); ?>" class="<?php echo $status === $key ? 'current' : ''; ?>"><?php echo esc_html($label); ?></a><?php echo $i < count($allowed) ? ' |' : ''; ?></li>
        <?php endforeach; ?>
      </ul>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="khesht_crm_action">
        <input type="hidden" name="back" value="khesht-reviews">
        <?php wp_nonce_field('khesht_crm_action'); ?>
        <table class="widefat striped" style="margin-top:12px">
          <thead><tr><th>تاریخ</th><th>مشتری</th><th>امتیاز</th><th>نظر و پاسخ</th><th>وضعیت</th><th>عملیات</th></tr></thead>
          <tbody>
          <?php if (!$rows) : ?>
            <tr><td colspan="6">نظری ثبت نشده است.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row) : ?>
            <tr>
              <td><?php echo esc_html(khesht_format_jalali($row['created_at'])); ?></td>
              <td><?php echo esc_html($row['name']); ?><br><span dir="ltr"><?php echo esc_html($row['phone']); ?></span></td>
              <td><?php echo str_repeat('★', (int) $row['rating']) . str_repeat('☆', 5 - (int) $row['rating']); ?></td>
              <td style="max-width:420px">
                <div style="white-space:pre-wrap"><?php echo esc_html($row['body']); ?></div>
                <textarea name="replies[<?php echo (int) $row['id']; ?>]" rows="2" style="width:100%;margin-top:6px" placeholder="پاسخ خشت (اختیاری، زیر نظر نمایش داده می‌شود)"><?php echo esc_textarea((string) $row['admin_reply']); ?></textarea>
                <button class="button button-small" type="submit" name="khesht_save_reply" value="<?php echo (int) $row['id']; ?>">ذخیره پاسخ</button>
              </td>
              <td><?php echo esc_html(isset($allowed[$row['status']]) ? $allowed[$row['status']] : $row['status']); ?></td>
              <td>
                <?php if ($row['status'] !== 'approved') : ?><a href="<?php echo esc_url(khesht_crm_action_url('approve', $row['id'], 'khesht-reviews')); ?>">تأیید و انتشار</a><br><?php endif; ?>
                <?php if ($row['status'] !== 'rejected') : ?><a href="<?php echo esc_url(khesht_crm_action_url('reject', $row['id'], 'khesht-reviews')); ?>">رد</a><br><?php endif; ?>
                <a href="<?php echo esc_url(khesht_crm_action_url('delete_review', $row['id'], 'khesht-reviews')); ?>" style="color:#b32d2e" onclick="return confirm('حذف شود؟')">حذف</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </form>
      <?php
      $pages = (int) ceil($total / $per);
      if ($pages > 1) {
          echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(paginate_links(array(
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'current' => $paged,
              'total' => $pages,
          ))) . '</div></div>';
      }
      ?>
    </div>
    <?php
}

function khesht_render_customers_page() {
    if (!current_user_can(khesht_crm_cap())) {
        return;
    }
    global $wpdb;
    $c = khesht_customers_table();
    $r = khesht_reviews_table();
    $q = khesht_requests_table();
    $per = 30;
    $paged = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);
    $s = isset($_GET['s']) ? trim(sanitize_text_field(wp_unslash($_GET['s']))) : '';

    $where = '';
    if ($s !== '') {
        $like = '%' . $wpdb->esc_like($s) . '%';
        $where = $wpdb->prepare('WHERE c.name LIKE %s OR c.phone LIKE %s', $like, $like);
    }
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $c c $where");
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT c.*, (SELECT COUNT(*) FROM $q x WHERE x.phone = c.phone) AS req_count,
                    (SELECT COUNT(*) FROM $r v WHERE v.customer_id = c.id) AS rev_count
             FROM $c c $where ORDER BY c.id DESC LIMIT %d OFFSET %d",
            $per,
            ($paged - 1) * $per
        ),
        ARRAY_A
    );
    $outbox = khesht_env('KHESHT_SMS_ADAPTER', 'mock') === 'mock' ? get_option('khesht_sms_outbox', array()) : array();
    ?>
    <div class="wrap" dir="rtl">
      <h1 class="wp-heading-inline">مشتریان (CRM)</h1>
      <a class="page-title-action" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=khesht_export_customers'), 'khesht_export_customers')); ?>">خروجی CSV</a>
      <hr class="wp-header-end">
      <?php khesht_crm_notice(); ?>
      <p>مشتریانی که در سایت ثبت‌نام کرده‌اند. رمز ورود را سیستم می‌سازد و پیامک می‌کند؛ مشتری رمز انتخاب نمی‌کند. درخواست‌های مشاوره/همکاری با همین شماره تلفن به مشتری وصل می‌شوند.</p>

      <?php if ($outbox) : ?>
        <div class="notice notice-warning inline">
          <p><strong>حالت آزمایشی پیامک (mock):</strong> پیامکی ارسال نمی‌شود و پیام‌ها این‌جا نمایش داده می‌شوند. بعد از اتصال پنل پیامک (<code>KHESHT_SMS_ADAPTER</code>) این بخش دیگر دیده نمی‌شود.</p>
          <table class="widefat striped"><thead><tr><th>زمان (UTC)</th><th>به</th><th>متن</th></tr></thead><tbody>
            <?php foreach (array_slice($outbox, 0, 8) as $m) : ?>
              <tr><td><?php echo esc_html($m['time']); ?></td><td dir="ltr"><?php echo esc_html($m['to']); ?></td><td style="white-space:pre-wrap"><?php echo esc_html($m['message']); ?></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      <?php endif; ?>

      <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="khesht-customers">
        <p class="search-box"><input type="search" name="s" value="<?php echo esc_attr($s); ?>" placeholder="نام یا شماره"> <button class="button">جستجو</button></p>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="khesht_crm_action">
        <input type="hidden" name="back" value="khesht-customers">
        <?php wp_nonce_field('khesht_crm_action'); ?>
        <table class="widefat striped">
          <thead><tr><th>نام</th><th>تلفن</th><th>ثبت‌نام</th><th>آخرین ورود</th><th>درخواست‌ها</th><th>نظرها</th><th>وضعیت</th><th>یادداشت</th><th>عملیات</th></tr></thead>
          <tbody>
          <?php if (!$rows) : ?><tr><td colspan="9">مشتری ثبت‌نام نکرده است.</td></tr><?php endif; ?>
          <?php foreach ($rows as $row) : ?>
            <tr>
              <td><strong><?php echo esc_html($row['name']); ?></strong></td>
              <td dir="ltr"><a href="tel:<?php echo esc_attr($row['phone']); ?>"><?php echo esc_html($row['phone']); ?></a></td>
              <td><?php echo esc_html(khesht_format_jalali($row['created_at'])); ?></td>
              <td><?php echo $row['last_login_at'] ? esc_html(khesht_format_jalali($row['last_login_at'])) : '—'; ?></td>
              <td><?php echo (int) $row['req_count'] > 0 ? '<a href="' . esc_url(khesht_requests_url(array('s' => $row['phone']))) . '">' . (int) $row['req_count'] . '</a>' : '0'; ?></td>
              <td><?php echo (int) $row['rev_count']; ?></td>
              <td><?php echo $row['status'] === 'blocked' ? '<span style="color:#b32d2e">مسدود</span>' : 'فعال'; ?></td>
              <td style="min-width:200px">
                <textarea name="notes[<?php echo (int) $row['id']; ?>]" rows="2" style="width:100%" placeholder="یادداشت داخلی"><?php echo esc_textarea((string) $row['note']); ?></textarea>
                <button class="button button-small" type="submit" name="khesht_save_note" value="<?php echo (int) $row['id']; ?>">ذخیره</button>
              </td>
              <td>
                <a href="<?php echo esc_url(khesht_crm_action_url('resend_password', $row['id'], 'khesht-customers')); ?>" onclick="return confirm('رمز جدید ساخته و پیامک شود؟')">ارسال رمز جدید</a><br>
                <?php if ($row['status'] === 'blocked') : ?>
                  <a href="<?php echo esc_url(khesht_crm_action_url('unblock', $row['id'], 'khesht-customers')); ?>">رفع مسدودی</a><br>
                <?php else : ?>
                  <a href="<?php echo esc_url(khesht_crm_action_url('block', $row['id'], 'khesht-customers')); ?>">مسدود کردن</a><br>
                <?php endif; ?>
                <a href="<?php echo esc_url(khesht_crm_action_url('delete_customer', $row['id'], 'khesht-customers')); ?>" style="color:#b32d2e" onclick="return confirm('مشتری و نظرهایش حذف شود؟')">حذف</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </form>
      <?php
      $pages = (int) ceil($total / $per);
      if ($pages > 1) {
          echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(paginate_links(array(
              'base' => add_query_arg('paged', '%#%'),
              'format' => '',
              'current' => $paged,
              'total' => $pages,
          ))) . '</div></div>';
      }
      ?>
    </div>
    <?php
}
