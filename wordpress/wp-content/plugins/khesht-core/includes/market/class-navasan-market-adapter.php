<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Live gold / coin / USD / EUR quotes from Navasan (https://www.navasan.tech/webserviceguide/).
 *
 * wp-config.php or host env:
 *   KHESHT_MARKET_ADAPTER=navasan
 *   KHESHT_MARKET_API_KEY=<your navasan api key>
 * Optional:
 *   KHESHT_NAVASAN_BASE=http://api.navasan.tech
 *   KHESHT_NAVASAN_ITEMS={"gold":{"key":"18ayar","label":"..."}}  (override item keys)
 *
 * The REST layer caches the response for 10 minutes (see khesht_rest_market_quotes).
 * The key never reaches the browser.
 */
class Khesht_Navasan_Market_Adapter extends Khesht_Market_Adapter {
    private function items() {
        $items = array(
            'gold' => array('key' => '18ayar', 'label' => 'طلای ۱۸ عیار (هر گرم)'),
            'coin' => array('key' => 'sekkeh', 'label' => 'سکه امامی'),
            'usd'  => array('key' => 'usd_sell', 'label' => 'دلار'),
            'eur'  => array('key' => 'eur', 'label' => 'یورو'),
        );
        $override = khesht_env('KHESHT_NAVASAN_ITEMS', '');
        if ($override) {
            $decoded = json_decode($override, true);
            if (is_array($decoded)) {
                $items = array_merge($items, $decoded);
            }
        }
        return $items;
    }

    private function fallback($source) {
        $out = array();
        foreach ($this->items() as $id => $def) {
            $out[] = array('id' => $id, 'label' => $def['label'], 'value' => null, 'unit' => 'تومان', 'change' => 0);
        }
        return array('updated_at' => gmdate('c'), 'source' => $source, 'items' => $out);
    }

    public function quotes() {
        $key = khesht_env('KHESHT_MARKET_API_KEY', '');
        if ($key === '') {
            return $this->fallback('mock');
        }
        $base = rtrim(khesht_env('KHESHT_NAVASAN_BASE', 'http://api.navasan.tech'), '/');
        $res = wp_remote_get($base . '/latest/?api_key=' . rawurlencode($key), array('timeout' => 8));
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
            return $this->fallback('error');
        }
        $raw = json_decode(wp_remote_retrieve_body($res), true);
        if (!is_array($raw)) {
            return $this->fallback('error');
        }

        $newest = 0;
        $out = array();
        foreach ($this->items() as $id => $def) {
            $row = isset($raw[$def['key']]) ? $raw[$def['key']] : null;
            if ($row && !empty($row['timestamp'])) {
                $newest = max($newest, (int) $row['timestamp']);
            }
            $out[] = array(
                'id' => $id,
                'label' => $def['label'],
                'value' => $row ? (float) str_replace(',', '', (string) $row['value']) : null,
                'unit' => 'تومان',
                'change' => $row && isset($row['change']) ? (float) $row['change'] : 0,
            );
        }
        return array(
            'updated_at' => $newest ? gmdate('c', $newest) : gmdate('c'),
            'source' => 'navasan.tech',
            'items' => $out,
        );
    }
}
