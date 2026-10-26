<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the consultation and collaboration forms:
 * validate -> store in {prefix}khesht_requests -> SMS the company number -> log SMS result.
 *
 * @param string $type 'consult' | 'collab'
 */
function khesht_handle_form_request(WP_REST_Request $request, $type) {
    $types = khesht_request_types();
    if (!isset($types[$type])) {
        return new WP_Error('khesht_bad_type', 'Bad type', array('status' => 400));
    }
    if (!khesht_rate_limit('form_' . $type)) {
        return new WP_Error('khesht_rate_limited', 'Too many requests', array('status' => 429));
    }

    // Honeypot: silently accept bots, store nothing.
    $website = sanitize_text_field((string) $request->get_param('website'));
    if ($website !== '') {
        return new WP_REST_Response(array('ok' => true, 'sms' => 'skipped'), 201);
    }

    $name = sanitize_text_field((string) $request->get_param('name'));
    $phone = sanitize_text_field((string) $request->get_param('phone'));
    $subject = sanitize_text_field((string) $request->get_param('subject'));
    $description = sanitize_textarea_field((string) $request->get_param('description'));

    $missing = array();
    foreach (array('name' => $name, 'phone' => $phone, 'subject' => $subject, 'description' => $description) as $key => $value) {
        if ($value === '') {
            $missing[] = $key;
        }
    }
    if ($missing) {
        return new WP_Error('khesht_validation', 'Missing fields', array('status' => 400, 'missing' => $missing));
    }

    // Accept Persian/Arabic digits.
    $phone = strtr($phone, array('۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'));
    $phone_compact = preg_replace('/[\s\-\(\)]+/', '', $phone);
    // +98912..., 0098912..., 98912... -> 0912...
    if (strpos($phone_compact, '+98') === 0) {
        $phone_compact = '0' . substr($phone_compact, 3);
    } elseif (strpos($phone_compact, '0098') === 0) {
        $phone_compact = '0' . substr($phone_compact, 4);
    } elseif (strpos($phone_compact, '98') === 0 && strlen($phone_compact) === 12) {
        $phone_compact = '0' . substr($phone_compact, 2);
    }
    if (!preg_match('/^0?9\d{9}$/', $phone_compact) && !preg_match('/^0\d{10}$/', $phone_compact)) {
        return new WP_Error('khesht_invalid_phone', 'Invalid phone', array('status' => 400));
    }

    $name = mb_substr($name, 0, 190);
    $subject = mb_substr($subject, 0, 255);
    $description = mb_substr($description, 0, 2000);

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 255) : '';
    $source = isset($_SERVER['HTTP_REFERER']) ? mb_substr(esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])), 0, 255) : '';

    $id = khesht_request_insert($type, array(
        'name' => $name,
        'phone' => $phone_compact,
        'subject' => $subject,
        'description' => $description,
        'ip' => $ip,
        'user_agent' => $ua,
        'source_url' => $source,
    ));
    if (!$id) {
        global $wpdb;
        $detail = (defined('WP_DEBUG') && WP_DEBUG) ? $wpdb->last_error : '';
        return new WP_Error('khesht_db', 'Could not save request', array('status' => 500, 'detail' => $detail));
    }

    $sms = khesht_send_request_sms($id);

    return new WP_REST_Response(array(
        'ok' => true,
        'id' => $id,
        'sms' => $sms['provider'],
        'message' => 'از ثبت درخواست شما بسیار متشکریم، کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.',
    ), 201);
}

/** Sends (or re-sends) the notification SMS for a stored request and logs the result. */
function khesht_send_request_sms($id) {
    $row = khesht_request_get($id);
    if (!$row) {
        return array('ok' => false, 'provider' => 'none');
    }
    $types = khesht_request_types();
    $to = khesht_setting_value('sms_to', 'KHESHT_SMS_TO');
    if ($to === '') {
        khesht_request_update($id, array('sms_status' => 'skipped', 'sms_response' => 'sms_to is not configured'));
        return array('ok' => false, 'provider' => 'none');
    }
    $label = isset($types[$row['type']]) ? $types[$row['type']] : $row['type'];
    $message = $label . "\n" . $row['name'] . "\n" . $row['phone'] . "\n" . $row['subject'];

    $result = khesht_sms_adapter()->send($to, $message);
    $ok = !empty($result['ok']);
    khesht_request_update($id, array(
        'sms_status' => $ok ? 'sent' : 'failed',
        'sms_response' => wp_json_encode($result),
    ));
    if (!isset($result['provider'])) {
        $result['provider'] = 'unknown';
    }
    return $result;
}
