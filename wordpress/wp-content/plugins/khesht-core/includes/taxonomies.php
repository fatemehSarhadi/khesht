<?php

if (!defined('ABSPATH')) {
    exit;
}

function khesht_register_taxonomies() {
    $common = array(
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
    );

    register_taxonomy('khesht_property_type', array('khesht_property'), array_merge($common, array(
        'label' => 'نوع ملک',
        'rewrite' => array('slug' => 'property-type'),
    )));

    register_taxonomy('khesht_deal', array('khesht_property'), array_merge($common, array(
        'label' => 'نوع معامله',
        'hierarchical' => false,
    )));

    register_taxonomy('khesht_location', array('khesht_property', 'khesht_area_price'), array_merge($common, array(
        'label' => 'موقعیت',
        'rewrite' => array('slug' => 'location'),
    )));
}

function khesht_seed_terms() {
    $types = array(
        'residential' => 'مسکونی',
        'land' => 'زمین',
        'garden' => 'باغ و باغچه',
        'commercial' => 'تجاری و اداری',
        'industrial' => 'صنعتی',
        'agri' => 'کشاورزی و دامپروری',
        'economic' => 'فایل‌های اقتصادی',
    );
    $deals = array(
        'buy' => 'خرید',
        'rahn' => 'رهن',
        'rahn-rent' => 'رهن،اجاره',
        'presale' => 'پیش خرید',
        'partnership' => 'مشارکت در ساخت',
    );
    foreach ($types as $slug => $name) {
        if (!term_exists($slug, 'khesht_property_type')) {
            wp_insert_term($name, 'khesht_property_type', array('slug' => $slug));
        }
    }
    foreach ($deals as $slug => $name) {
        if (!term_exists($slug, 'khesht_deal')) {
            wp_insert_term($name, 'khesht_deal', array('slug' => $slug));
        }
    }
}

/** Magazine categories used by the frontend chips (education / construction / market / media). */
function khesht_seed_categories() {
    $cats = array(
        'education' => 'آموزش املاک',
        'construction' => 'ساخت‌وساز',
        'market' => 'تحلیل بازار',
        'media' => 'پادکست و رسانه',
        'ceo' => 'وبلاگ مهندس نوروزی',
    );
    foreach ($cats as $slug => $name) {
        if (!term_exists($slug, 'category')) {
            wp_insert_term($name, 'category', array('slug' => $slug));
        }
    }
}

/** Default services so the panel is not empty; editors can change them any time. */
function khesht_seed_services() {
    $existing = get_posts(array('post_type' => 'khesht_service', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids'));
    if ($existing) {
        return;
    }
    $services = array(
        array('خرید و فروش', 'scale', 'همراهی کامل از انتخاب و بررسی سند تا مذاکره و انتقال؛ برای سکونت یا سرمایه‌گذاری.'),
        array('رهن و اجاره', 'key', 'تنظیم رهن کامل یا رهن و اجاره با شفافیت شرایط و تعهدات هر دو طرف.'),
        array('کارشناسی و قیمت‌گذاری', 'ruler', 'برآورد کارشناسی بر پایه‌ی موقعیت، سند، ساخت و داده‌های واقعی بازار.'),
        array('مشاوره', 'chat', 'مشاوره‌ی تصمیم‌گیری برای خرید، فروش، مشارکت و سرمایه‌گذاری.'),
        array('ساخت و ساز و شهرک‌سازی', 'hammer', 'از طراحی تا اجرا؛ خشت روی خشت، همان چیزی را می‌سازیم که شما می‌خواهید.'),
        array('مشارکت', 'handshake', 'مشارکت در ساخت و پروژه‌های مشترک؛ سرمایه‌ی شما و تجربه‌ی اجرایی ما در کنار هم.'),
    );
    foreach ($services as $order => $svc) {
        $id = wp_insert_post(array(
            'post_type' => 'khesht_service',
            'post_status' => 'publish',
            'post_title' => $svc[0],
            'post_content' => $svc[2],
            'menu_order' => $order,
        ));
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, 'khesht_icon', $svc[1]);
        }
    }
}

/** Adds the "مشارکت" service on installs that were seeded with only five services. */
function khesht_seed_partnership_service() {
    $services = get_posts(array('post_type' => 'khesht_service', 'post_status' => 'any', 'numberposts' => -1));
    foreach ($services as $service) {
        if (trim($service->post_title) === 'مشارکت') {
            return;
        }
    }
    $id = wp_insert_post(array(
        'post_type' => 'khesht_service',
        'post_status' => 'publish',
        'post_title' => 'مشارکت',
        'post_content' => 'مشارکت در ساخت و پروژه‌های مشترک؛ سرمایه‌ی شما و تجربه‌ی اجرایی ما در کنار هم.',
        'menu_order' => 5,
    ));
    if ($id && !is_wp_error($id)) {
        update_post_meta($id, 'khesht_icon', 'handshake');
    }
}

/** One-time data upgrades for sites that were activated with an older plugin version. */
function khesht_run_seed_upgrades() {
    if ((int) get_option('khesht_seed_version', 0) >= 2) {
        return;
    }
    khesht_seed_terms();
    khesht_seed_categories();
    khesht_seed_partnership_service();
    update_option('khesht_seed_version', 2);
}
