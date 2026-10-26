<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kavenegar (kavenegar.com) SMS adapter.
 *   KHESHT_SMS_ADAPTER=kavenegar
 *   KHESHT_SMS_API_KEY=<api key from the Kavenegar panel>
 *   KHESHT_SMS_SENDER=<sender line, optional – default line is used if empty>
 * Endpoint: POST https://api.kavenegar.com/v1/{API-KEY}/sms/send.json  (receptor, message, sender)
 * Verify the endpoint/sender rules against your Kavenegar panel before going live.
 */
class Khesht_Kavenegar_Sms_Adapter extends Khesht_Sms_Adapter {
    public function send($to, $message) {
        $key = khesht_env('KHESHT_SMS_API_KEY', '');
        if ($key === '') {
            return array('ok' => false, 'provider' => 'kavenegar', 'error' => 'missing_api_key');
        }
        $body = array(
            'receptor' => $to,
            'message' => $message,
        );
        $sender = khesht_env('KHESHT_SMS_SENDER', '');
        if ($sender !== '') {
            $body['sender'] = $sender;
        }
        $res = wp_remote_post(
            'https://api.kavenegar.com/v1/' . rawurlencode($key) . '/sms/send.json',
            array('timeout' => 10, 'body' => $body)
        );
        if (is_wp_error($res)) {
            return array('ok' => false, 'provider' => 'kavenegar', 'error' => $res->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        $json = json_decode(wp_remote_retrieve_body($res), true);
        $status = isset($json['return']['status']) ? (int) $json['return']['status'] : $code;
        return array(
            'ok' => $status === 200,
            'provider' => 'kavenegar',
            'status' => $status,
            'raw' => isset($json['return']['message']) ? $json['return']['message'] : '',
        );
    }
}
