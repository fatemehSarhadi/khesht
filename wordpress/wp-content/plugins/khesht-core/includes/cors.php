<?php

if (!defined('ABSPATH')) {
    exit;
}

function khesht_register_cors() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        $origin = get_http_origin();
        $allowed = array(
            khesht_frontend_origin(),
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        );
        $extra = khesht_env('KHESHT_EXTRA_ORIGINS', '');
        if ($extra) {
            $allowed = array_merge($allowed, array_map('trim', explode(',', $extra)));
        }
        if ($origin && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-Khesht-Token');
        }
        return $value;
    });
}
