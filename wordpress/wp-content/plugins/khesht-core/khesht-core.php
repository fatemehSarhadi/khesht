<?php
/**
 * Plugin Name: Khesht Core
 * Description: Headless CMS model, REST API, request inbox (DB), SMS/market adapters, and rebuild webhook for Khesht.
 * Version: 0.2.0
 * Text Domain: khesht
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KHESHT_CORE_DIR', plugin_dir_path(__FILE__));
define('KHESHT_CORE_VERSION', '0.2.0');

require_once KHESHT_CORE_DIR . 'includes/helpers.php';
require_once KHESHT_CORE_DIR . 'includes/db.php';
require_once KHESHT_CORE_DIR . 'includes/post-types.php';
require_once KHESHT_CORE_DIR . 'includes/taxonomies.php';
require_once KHESHT_CORE_DIR . 'includes/meta.php';
require_once KHESHT_CORE_DIR . 'includes/cors.php';
require_once KHESHT_CORE_DIR . 'includes/sms/class-sms-adapter.php';
require_once KHESHT_CORE_DIR . 'includes/sms/class-mock-sms-adapter.php';
require_once KHESHT_CORE_DIR . 'includes/sms/class-kavenegar-sms-adapter.php';
require_once KHESHT_CORE_DIR . 'includes/market/class-market-adapter.php';
require_once KHESHT_CORE_DIR . 'includes/market/class-mock-market-adapter.php';
require_once KHESHT_CORE_DIR . 'includes/market/class-navasan-market-adapter.php';
require_once KHESHT_CORE_DIR . 'includes/forms.php';
require_once KHESHT_CORE_DIR . 'includes/customers.php';
require_once KHESHT_CORE_DIR . 'includes/rest.php';
require_once KHESHT_CORE_DIR . 'includes/webhook.php';
require_once KHESHT_CORE_DIR . 'includes/settings.php';

if (is_admin()) {
    require_once KHESHT_CORE_DIR . 'includes/admin/meta-boxes.php';
    require_once KHESHT_CORE_DIR . 'includes/admin/requests-page.php';
    require_once KHESHT_CORE_DIR . 'includes/admin/gallery-page.php';
    require_once KHESHT_CORE_DIR . 'includes/admin/customers-page.php';
}

add_action('init', 'khesht_register_post_types');
add_action('init', 'khesht_register_taxonomies');
add_action('init', 'khesht_register_meta');
add_action('plugins_loaded', 'khesht_maybe_upgrade_db');
add_action('init', 'khesht_run_seed_upgrades', 30);
add_action('rest_api_init', 'khesht_register_rest_routes');
add_action('rest_api_init', 'khesht_register_cors');
add_action('save_post', 'khesht_maybe_fire_rebuild_webhook', 20, 3);

register_activation_hook(__FILE__, 'khesht_activate');

function khesht_activate() {
    khesht_register_post_types();
    khesht_register_taxonomies();
    khesht_install_db();
    khesht_seed_terms();
    khesht_seed_categories();
    khesht_seed_services();
    if (get_option('khesht_settings') === false) {
        add_option('khesht_settings', khesht_default_settings());
    }
    flush_rewrite_rules();
}
