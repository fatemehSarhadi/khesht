<?php
/**
 * Customer accounts + reviews ("تجربه مشتریان").
 *  - Customers register with name + phone; the password is generated and sent by SMS (never chosen by the customer).
 *  - Login returns a signed token (header X-Khesht-Token). Reviews need a logged-in customer and are moderated in WP admin.
 * Data lives in {prefix}khesht_customers and {prefix}khesht_reviews (a small CRM; see admin "تجربه مشتریان").
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ---------- helpers ---------- */

/** Mobile number -> 09xxxxxxxxx, or '' if invalid. */
function khesht_normalize_mobile($raw) {
    $raw = strtr((string) $raw, array('۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'));
    $phone = preg_replace('/[\s\-\(\)]+/', '', $raw);
    if (strpos($phone, '+98') === 0) {
        $phone = '0' . substr($phone, 3);
    } elseif (strpos($phone, '0098') === 0) {
        $phone = '0' . substr($phone, 4);
    } elseif (strpos($phone, '98') === 0 && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    } elseif (strlen($phone) === 10 && $phone[0] === '9') {
        $phone = '0' . $phone;
    }
    return preg_match('/^09\d{9}$/', $phone) ? $phone : '';
}

function khesht_generate_password() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no look-alike characters
    $out = '';
    for ($i = 0; $i < 6; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

/** "سعید نوروزی" -> "سعید ن." (reviews never show full names or phones) */
function khesht_public_name($name) {
    $parts = preg_split('/\s+/u', trim((string) $name));
    if (count($parts) < 2) {
        return $parts[0];
    }
    $last = end($parts);
    return $parts[0] . ' ' . mb_substr($last, 0, 1) . '.';
}

/** Sends an SMS through the configured adapter. In mock mode the message is kept in an admin-visible outbox. */
function khesht_send_sms($to, $message) {
    if (khesht_env('KHESHT_SMS_ADAPTER', 'mock') === 'mock') {
        $box = get_option('khesht_sms_outbox', array());
        if (!is_array($box)) {
            $box = array();
        }
        array_unshift($box, array('time' => gmdate('Y-m-d H:i:s'), 'to' => $to, 'message' => $message));
        update_option('khesht_sms_outbox', array_slice($box, 0, 20), false);
    }
    $result = khesht_sms_adapter()->send($to, $message);
    return !empty($result['ok']);
}

function khesht_customer_by_phone($phone) {
    global $wpdb;
    $table = khesht_customers_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE phone = %s", $phone), ARRAY_A);
}

function khesht_customer_by_id($id) {
    global $wpdb;
    $table = khesht_customers_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", (int) $id), ARRAY_A);
}

/** Creates a fresh password, stores its hash and SMSes it. Returns true when the SMS was accepted. */
function khesht_issue_password($customer) {
    global $wpdb;
    $password = khesht_generate_password();
    $wpdb->update(
        khesht_customers_table(),
        array('password_hash' => wp_hash_password($password), 'fail_count' => 0, 'locked_until' => null),
        array('id' => (int) $customer['id'])
    );
    $message = 'خشت ۱۲۳' . "\n" . 'رمز ورود شما: ' . $password;
    return khesht_send_sms($customer['phone'], $message);
}

/* ---------- token ---------- */

function khesht_token_issue($customer_id) {
    $payload = wp_json_encode(array('c' => (int) $customer_id, 'e' => time() + 30 * DAY_IN_SECONDS));
    $body = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    return $body . '.' . hash_hmac('sha256', $body, wp_salt('auth'));
}

/** @return array|null active customer row for the request's token */
function khesht_token_customer(WP_REST_Request $request) {
    $token = (string) $request->get_header('X-Khesht-Token');
    if ($token === '' || strpos($token, '.') === false) {
        return null;
    }
    list($body, $sig) = explode('.', $token, 2);
    if (!hash_equals(hash_hmac('sha256', $body, wp_salt('auth')), $sig)) {
        return null;
    }
    $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);
    if (!is_array($payload) || empty($payload['c']) || empty($payload['e']) || (int) $payload['e'] < time()) {
        return null;
    }
    $customer = khesht_customer_by_id($payload['c']);
    return ($customer && $customer['status'] === 'active') ? $customer : null;
}

/* ---------- REST ---------- */

add_action('rest_api_init', function () {
    $ns = 'khesht/v1';
    $routes = array(
        array('/customers/register', 'POST', 'khesht_rest_customer_register'),
        array('/customers/login', 'POST', 'khesht_rest_customer_login'),
        array('/customers/forgot', 'POST', 'khesht_rest_customer_forgot'),
        array('/customers/me', 'GET', 'khesht_rest_customer_me'),
        array('/reviews', 'GET', 'khesht_rest_reviews'),
        array('/reviews', 'POST', 'khesht_rest_review_create'),
    );
    foreach ($routes as $r) {
        register_rest_route($ns, $r[0], array(
            'methods' => $r[1],
            'permission_callback' => '__return_true',
            'callback' => $r[2],
        ));
    }
});

function khesht_rest_customer_register(WP_REST_Request $request) {
    if (!khesht_rate_limit('cust_register', 6, 3600)) {
        return new WP_Error('khesht_rate_limited', 'Too many requests', array('status' => 429));
    }
    if ((string) $request->get_param('website') !== '') {
        return new WP_REST_Response(array('ok' => true), 201); // honeypot
    }
    $name = trim(sanitize_text_field((string) $request->get_param('name')));
    $phone = khesht_normalize_mobile($request->get_param('phone'));
    if (mb_strlen($name) < 3) {
        return new WP_Error('khesht_validation', 'Name required', array('status' => 400));
    }
    if ($phone === '') {
        return new WP_Error('khesht_invalid_phone', 'Invalid phone', array('status' => 400));
    }
    if (khesht_customer_by_phone($phone)) {
        return new WP_Error('khesht_exists', 'Already registered', array('status' => 409));
    }
    global $wpdb;
    $ok = $wpdb->insert(
        khesht_customers_table(),
        array('name' => mb_substr($name, 0, 190), 'phone' => $phone, 'status' => 'active', 'created_at' => gmdate('Y-m-d H:i:s')),
        array('%s', '%s', '%s', '%s')
    );
    if (!$ok) {
        khesht_install_db();
        $ok = $wpdb->insert(
            khesht_customers_table(),
            array('name' => mb_substr($name, 0, 190), 'phone' => $phone, 'status' => 'active', 'created_at' => gmdate('Y-m-d H:i:s')),
            array('%s', '%s', '%s', '%s')
        );
    }
    if (!$ok) {
        return new WP_Error('khesht_db', 'Could not save', array('status' => 500));
    }
    $customer = khesht_customer_by_id($wpdb->insert_id);
    $sms = khesht_issue_password($customer);
    return new WP_REST_Response(array('ok' => true, 'sms_sent' => $sms), 201);
}

function khesht_rest_customer_forgot(WP_REST_Request $request) {
    if (!khesht_rate_limit('cust_forgot', 6, 3600)) {
        return new WP_Error('khesht_rate_limited', 'Too many requests', array('status' => 429));
    }
    $phone = khesht_normalize_mobile($request->get_param('phone'));
    if ($phone === '') {
        return new WP_Error('khesht_invalid_phone', 'Invalid phone', array('status' => 400));
    }
    $customer = khesht_customer_by_phone($phone);
    if ($customer && $customer['status'] === 'active') {
        $key = 'khesht_forgot_' . md5($phone);
        if (!get_transient($key)) {
            set_transient($key, 1, 60);
            khesht_issue_password($customer);
        }
    }
    // same answer whether or not the number is registered
    return array('ok' => true);
}

function khesht_rest_customer_login(WP_REST_Request $request) {
    global $wpdb;
    $phone = khesht_normalize_mobile($request->get_param('phone'));
    $password = trim((string) $request->get_param('password'));
    $fail = new WP_Error('khesht_bad_login', 'Wrong phone or password', array('status' => 401));
    if ($phone === '' || $password === '') {
        return $fail;
    }
    $customer = khesht_customer_by_phone($phone);
    if (!$customer || $customer['status'] !== 'active' || $customer['password_hash'] === '') {
        return $fail;
    }
    if (!empty($customer['locked_until']) && strtotime($customer['locked_until'] . ' UTC') > time()) {
        return new WP_Error('khesht_locked', 'Too many attempts', array('status' => 429));
    }
    if (!wp_check_password(strtoupper($password), $customer['password_hash'])) {
        $count = (int) $customer['fail_count'] + 1;
        $update = array('fail_count' => $count);
        if ($count >= 5) {
            $update['fail_count'] = 0;
            $update['locked_until'] = gmdate('Y-m-d H:i:s', time() + 15 * MINUTE_IN_SECONDS);
        }
        $wpdb->update(khesht_customers_table(), $update, array('id' => (int) $customer['id']));
        return $fail;
    }
    $wpdb->update(
        khesht_customers_table(),
        array('fail_count' => 0, 'locked_until' => null, 'last_login_at' => gmdate('Y-m-d H:i:s')),
        array('id' => (int) $customer['id'])
    );
    return array(
        'token' => khesht_token_issue($customer['id']),
        'customer' => array('name' => $customer['name']),
    );
}

function khesht_rest_customer_me(WP_REST_Request $request) {
    $customer = khesht_token_customer($request);
    if (!$customer) {
        return new WP_Error('khesht_unauthorized', 'Unauthorized', array('status' => 401));
    }
    return array('customer' => array('name' => $customer['name']));
}

function khesht_rest_reviews(WP_REST_Request $request) {
    global $wpdb;
    $reviews = khesht_reviews_table();
    $customers = khesht_customers_table();
    $per = (int) $request->get_param('per_page');
    $per = $per > 0 ? min(24, $per) : 6;
    $page = max(1, (int) $request->get_param('page'));
    $offset = ($page - 1) * $per;

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $reviews WHERE status = 'approved'");
    $avg = (float) $wpdb->get_var("SELECT AVG(rating) FROM $reviews WHERE status = 'approved'");
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.id, r.rating, r.body, r.admin_reply, r.created_at, c.name
             FROM $reviews r LEFT JOIN $customers c ON c.id = r.customer_id
             WHERE r.status = 'approved' ORDER BY r.created_at DESC LIMIT %d OFFSET %d",
            $per,
            $offset
        ),
        ARRAY_A
    );
    $items = array();
    foreach ($rows as $row) {
        $items[] = array(
            'id' => (int) $row['id'],
            'name' => khesht_public_name($row['name']),
            'rating' => (int) $row['rating'],
            'body' => $row['body'],
            'reply' => (string) $row['admin_reply'],
            'date' => gmdate('Y-m-d', strtotime($row['created_at'] . ' UTC')),
        );
    }
    return array(
        'items' => $items,
        'total' => $total,
        'average' => $total ? round($avg, 1) : 0,
        'total_pages' => max(1, (int) ceil($total / $per)),
        'page' => $page,
    );
}

function khesht_rest_review_create(WP_REST_Request $request) {
    global $wpdb;
    $customer = khesht_token_customer($request);
    if (!$customer) {
        return new WP_Error('khesht_unauthorized', 'Unauthorized', array('status' => 401));
    }
    $rating = (int) $request->get_param('rating');
    $body = trim(sanitize_textarea_field((string) $request->get_param('body')));
    if ($rating < 1 || $rating > 5 || mb_strlen($body) < 10) {
        return new WP_Error('khesht_validation', 'Rating and text (min 10 chars) required', array('status' => 400));
    }
    $body = mb_substr($body, 0, 1000);

    $table = khesht_reviews_table();
    $recent = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE customer_id = %d AND created_at > %s",
        (int) $customer['id'],
        gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)
    ));
    if ($recent >= 2) {
        return new WP_Error('khesht_rate_limited', 'Too many reviews', array('status' => 429));
    }
    $status = khesht_setting('reviews_auto_approve', '') === '1' ? 'approved' : 'pending';
    $ok = $wpdb->insert(
        $table,
        array('customer_id' => (int) $customer['id'], 'rating' => $rating, 'body' => $body, 'status' => $status, 'created_at' => gmdate('Y-m-d H:i:s')),
        array('%d', '%d', '%s', '%s', '%s')
    );
    if (!$ok) {
        return new WP_Error('khesht_db', 'Could not save', array('status' => 500));
    }
    return new WP_REST_Response(array('ok' => true, 'status' => $status), 201);
}
