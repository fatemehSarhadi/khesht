<?php

if (!defined('ABSPATH')) {
    exit;
}

abstract class Khesht_Market_Adapter {
    abstract public function quotes();
}

function khesht_market_adapter() {
    $name = khesht_env('KHESHT_MARKET_ADAPTER', 'mock');
    if ($name === 'navasan' && class_exists('Khesht_Navasan_Market_Adapter')) {
        return new Khesht_Navasan_Market_Adapter();
    }
    $adapter = apply_filters('khesht_market_adapter', null, $name);
    if ($adapter instanceof Khesht_Market_Adapter) {
        return $adapter;
    }
    return new Khesht_Mock_Market_Adapter();
}
