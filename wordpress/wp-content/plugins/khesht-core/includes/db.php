<?php
/**
 * Database layer: custom table for consultation + collaboration requests.
 * Table: {prefix}khesht_requests (created with dbDelta on activation / upgrade).
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KHESHT_DB_VERSION', '2');

function khesht_requests_table() {
    global $wpdb;
    return $wpdb->prefix . 'khesht_requests';
}

function khesht_install_db() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = khesht_requests_table();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(20) NOT NULL,
  name varchar(190) NOT NULL,
  phone varchar(20) NOT NULL,
  subject varchar(255) NOT NULL DEFAULT '',
  description text NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'new',
  note text NULL,
  sms_status varchar(20) NOT NULL DEFAULT 'pending',
  sms_response text NULL,
  ip varchar(64) NOT NULL DEFAULT '',
  user_agent varchar(255) NOT NULL DEFAULT '',
  source_url varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY type_status (type,status),
  KEY created_at (created_at)
) $charset;";

    dbDelta($sql);

    $customers = $wpdb->prefix . 'khesht_customers';
    dbDelta("CREATE TABLE $customers (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(190) NOT NULL,
  phone varchar(20) NOT NULL,
  password_hash varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'active',
  note text NULL,
  fail_count smallint(5) unsigned NOT NULL DEFAULT 0,
  locked_until datetime NULL,
  last_login_at datetime NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY phone (phone),
  KEY status (status)
) $charset;");

    $reviews = $wpdb->prefix . 'khesht_reviews';
    dbDelta("CREATE TABLE $reviews (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id bigint(20) unsigned NOT NULL,
  rating tinyint(3) unsigned NOT NULL DEFAULT 5,
  body text NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  admin_reply text NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status_created (status,created_at),
  KEY customer (customer_id)
) $charset;");

    update_option('khesht_db_version', KHESHT_DB_VERSION);
}

function khesht_maybe_upgrade_db() {
    if (get_option('khesht_db_version') !== KHESHT_DB_VERSION) {
        khesht_install_db();
    }
}

function khesht_request_statuses() {
    return array(
        'new' => 'جدید',
        'in_progress' => 'در حال پیگیری',
        'done' => 'انجام شد',
        'spam' => 'اسپم',
    );
}

function khesht_request_types() {
    return array(
        'consult' => 'درخواست مشاوره',
        'collab' => 'درخواست همکاری',
    );
}

/** @return int|false inserted id */
function khesht_request_insert($type, $data) {
    global $wpdb;
    $row = array(
        'type' => $type,
        'name' => $data['name'],
        'phone' => $data['phone'],
        'subject' => $data['subject'],
        'description' => $data['description'],
        'status' => 'new',
        'sms_status' => 'pending',
        'ip' => isset($data['ip']) ? $data['ip'] : '',
        'user_agent' => isset($data['user_agent']) ? $data['user_agent'] : '',
        'source_url' => isset($data['source_url']) ? $data['source_url'] : '',
        'created_at' => gmdate('Y-m-d H:i:s'),
    );
    $formats = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

    $ok = $wpdb->insert(khesht_requests_table(), $row, $formats);
    if (!$ok) {
        // Table may be missing (e.g. plugin files updated after activation): create it and retry once.
        error_log('[khesht] request insert failed: ' . $wpdb->last_error);
        khesht_install_db();
        $ok = $wpdb->insert(khesht_requests_table(), $row, $formats);
        if (!$ok) {
            error_log('[khesht] request insert failed again: ' . $wpdb->last_error);
        }
    }
    return $ok ? (int) $wpdb->insert_id : false;
}

function khesht_request_get($id) {
    global $wpdb;
    $table = khesht_requests_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", (int) $id), ARRAY_A);
}

function khesht_request_update($id, $fields) {
    global $wpdb;
    $allowed = array('status', 'note', 'sms_status', 'sms_response');
    $clean = array();
    foreach ($allowed as $key) {
        if (array_key_exists($key, $fields)) {
            $clean[$key] = $fields[$key];
        }
    }
    if (!$clean) {
        return false;
    }
    return $wpdb->update(khesht_requests_table(), $clean, array('id' => (int) $id));
}

function khesht_request_delete($id) {
    global $wpdb;
    return $wpdb->delete(khesht_requests_table(), array('id' => (int) $id), array('%d'));
}

function khesht_requests_new_count($type = '') {
    global $wpdb;
    $table = khesht_requests_table();
    if ($type) {
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = 'new' AND type = %s", $type));
    }
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'new'");
}

/** Gregorian -> Jalali (Solar Hijri) for admin display. */
function khesht_gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + (int) (($gy2 + 3) / 4) - (int) (($gy2 + 99) / 100) + (int) (($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * (int) ($days / 12053));
    $days %= 12053;
    $jy += 4 * (int) ($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += (int) (($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int) ($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int) (($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return array($jy, $jm, $jd);
}

/** "1405/07/21 14:35" in Asia/Tehran from a UTC mysql datetime. */
function khesht_format_jalali($mysql_utc) {
    $ts = strtotime($mysql_utc . ' UTC');
    if (!$ts) {
        return '';
    }
    $dt = new DateTime('@' . $ts);
    $dt->setTimezone(new DateTimeZone('Asia/Tehran'));
    list($jy, $jm, $jd) = khesht_gregorian_to_jalali((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'));
    return sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, $dt->format('H:i'));
}

function khesht_customers_table() {
    global $wpdb;
    return $wpdb->prefix . 'khesht_customers';
}

function khesht_reviews_table() {
    global $wpdb;
    return $wpdb->prefix . 'khesht_reviews';
}
