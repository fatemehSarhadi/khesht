<?php

if (!defined('ABSPATH')) {
    exit;
}

abstract class Khesht_Sms_Adapter {
    abstract public function send($to, $message);
}

function khesht_sms_adapter() {
    $name = khesht_env('KHESHT_SMS_ADAPTER', 'mock');
    if ($name === 'kavenegar' && class_exists('Khesht_Kavenegar_Sms_Adapter')) {
        return new Khesht_Kavenegar_Sms_Adapter();
    }
    $adapter = apply_filters('khesht_sms_adapter', null, $name);
    if ($adapter instanceof Khesht_Sms_Adapter) {
        return $adapter;
    }
    return new Khesht_Mock_Sms_Adapter();
}
