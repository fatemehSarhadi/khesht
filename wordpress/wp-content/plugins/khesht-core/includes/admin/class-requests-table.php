<?php

if (!defined('ABSPATH')) {
    exit;
}

class Khesht_Requests_List_Table extends WP_List_Table {
    private $type;

    public function __construct($type = '') {
        parent::__construct(array('singular' => 'request', 'plural' => 'requests', 'ajax' => false));
        $this->type = isset(khesht_request_types()[$type]) ? $type : '';
    }

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'created_at' => 'تاریخ (شمسی)',
            'type' => 'نوع',
            'name' => 'نام',
            'phone' => 'تلفن',
            'subject' => 'موضوع و توضیح',
            'status' => 'وضعیت',
            'sms_status' => 'پیامک',
        );
    }

    protected function get_bulk_actions() {
        $actions = array();
        foreach (khesht_request_statuses() as $key => $label) {
            $actions[$key] = 'علامت‌گذاری: ' . $label;
        }
        $actions['resend_sms'] = 'ارسال مجدد پیامک';
        $actions['delete'] = 'حذف';
        return $actions;
    }

    // The default bulk <select name="action"> would clash with the admin-post "action" field, so render our own ("do").
    protected function bulk_actions($which = '') {
        if ($which !== 'top') {
            return;
        }
        echo '<label class="screen-reader-text" for="khesht-bulk">عملیات گروهی</label>';
        echo '<select name="do" id="khesht-bulk"><option value="">عملیات گروهی</option>';
        foreach ($this->get_bulk_actions() as $key => $label) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '</select> <button type="submit" class="button action">اعمال</button>';
    }

    public function prepare_items() {
        global $wpdb;
        $table = khesht_requests_table();
        $per = 20;
        $page = $this->get_pagenum();
        $where = array('1=1');
        $args = array();

        if ($this->type) {
            $where[] = 'type = %s';
            $args[] = $this->type;
        }
        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
        if ($status && isset(khesht_request_statuses()[$status])) {
            $where[] = 'status = %s';
            $args[] = $status;
        }
        $s = isset($_REQUEST['s']) ? trim(sanitize_text_field(wp_unslash($_REQUEST['s']))) : '';
        if ($s !== '') {
            $like = '%' . $wpdb->esc_like($s) . '%';
            $where[] = '(name LIKE %s OR phone LIKE %s OR subject LIKE %s OR description LIKE %s)';
            array_push($args, $like, $like, $like, $like);
        }
        $where_sql = implode(' AND ', $where);

        $count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        $total = (int) ($args ? $wpdb->get_var($wpdb->prepare($count_sql, $args)) : $wpdb->get_var($count_sql));

        $offset = ($page - 1) * $per;
        $rows_sql = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT %d OFFSET %d";
        $rows_args = array_merge($args, array($per, $offset));
        $this->items = $wpdb->get_results($wpdb->prepare($rows_sql, $rows_args), ARRAY_A);

        $this->_column_headers = array($this->get_columns(), array(), array());
        $this->set_pagination_args(array('total_items' => $total, 'per_page' => $per));
    }

    protected function column_cb($item) {
        return '<input type="checkbox" name="ids[]" value="' . (int) $item['id'] . '" />';
    }

    protected function column_created_at($item) {
        $new = $item['status'] === 'new' ? ' <strong style="color:#b32d2e">●</strong>' : '';
        return esc_html(khesht_format_jalali($item['created_at'])) . $new;
    }

    protected function column_type($item) {
        $types = khesht_request_types();
        return esc_html(isset($types[$item['type']]) ? $types[$item['type']] : $item['type']);
    }

    protected function column_name($item) {
        $links = array();
        foreach (array('in_progress' => 'در حال پیگیری', 'done' => 'انجام شد', 'spam' => 'اسپم') as $key => $label) {
            $links[] = '<a href="' . esc_url($this->action_url($item['id'], $key)) . '">' . esc_html($label) . '</a>';
        }
        $links[] = '<a href="' . esc_url($this->action_url($item['id'], 'resend_sms')) . '">ارسال مجدد پیامک</a>';
        $links[] = '<a href="' . esc_url($this->action_url($item['id'], 'delete')) . '" onclick="return confirm(\'حذف شود؟\')" style="color:#b32d2e">حذف</a>';
        return '<strong>' . esc_html($item['name']) . '</strong><div class="row-actions" style="position:static;left:0">' . implode(' | ', $links) . '</div>';
    }

    protected function column_phone($item) {
        $p = esc_html($item['phone']);
        return '<a href="tel:' . esc_attr($item['phone']) . '" dir="ltr">' . $p . '</a>';
    }

    protected function column_subject($item) {
        $out = '<strong>' . esc_html($item['subject']) . '</strong>';
        $out .= '<details style="margin-top:4px"><summary>مشاهده توضیح و یادداشت</summary>';
        $out .= '<p style="white-space:pre-wrap">' . esc_html($item['description']) . '</p>';
        $out .= '<textarea name="notes[' . (int) $item['id'] . ']" rows="2" style="width:100%" placeholder="یادداشت داخلی">' . esc_textarea((string) $item['note']) . '</textarea>';
        $out .= '<button class="button button-small" type="submit" name="khesht_save_note" value="' . (int) $item['id'] . '">ذخیره یادداشت</button></details>';
        return $out;
    }

    protected function column_status($item) {
        $st = khesht_request_statuses();
        return esc_html(isset($st[$item['status']]) ? $st[$item['status']] : $item['status']);
    }

    protected function column_sms_status($item) {
        $map = array('sent' => 'ارسال شد', 'failed' => 'ناموفق', 'skipped' => 'تنظیم نشده', 'pending' => 'در انتظار');
        $label = isset($map[$item['sms_status']]) ? $map[$item['sms_status']] : $item['sms_status'];
        $color = $item['sms_status'] === 'failed' ? '#b32d2e' : ($item['sms_status'] === 'sent' ? '#1a7f37' : '#666');
        return '<span style="color:' . $color . '">' . esc_html($label) . '</span>';
    }

    private function action_url($id, $do) {
        return wp_nonce_url(
            admin_url('admin-post.php?action=khesht_request_action&do=' . rawurlencode($do) . '&id=' . (int) $id),
            'khesht_request_action'
        );
    }

    public function no_items() {
        echo 'درخواستی ثبت نشده است.';
    }
}
