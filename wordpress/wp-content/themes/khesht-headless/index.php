<?php
if (!defined('ABSPATH')) {
    exit;
}
$origin = function_exists('khesht_frontend_origin')
    ? khesht_frontend_origin()
    : 'http://localhost:5173';
wp_safe_redirect($origin, 302);
exit;
