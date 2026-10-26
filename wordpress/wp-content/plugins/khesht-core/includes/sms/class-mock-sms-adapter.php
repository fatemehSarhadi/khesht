<?php

if (!defined('ABSPATH')) {
    exit;
}

class Khesht_Mock_Sms_Adapter extends Khesht_Sms_Adapter {
    public function send($to, $message) {
        error_log('[khesht-sms-mock] to=' . $to . ' body=' . $message);
        return array(
            'ok' => true,
            'provider' => 'mock',
            'to' => $to,
        );
    }
}
