<?php

if (!defined('ABSPATH')) {
    exit;
}

function khesht_register_post_types() {
    $public_rest = array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
        'menu_position' => 5,
    );

    register_post_type('khesht_property', array_merge($public_rest, array(
        'label' => 'Properties',
        'labels' => array(
            'name' => 'املاک',
            'singular_name' => 'ملک',
        ),
        'menu_icon' => 'dashicons-admin-home',
        'rewrite' => array('slug' => 'amlak'),
    )));

    register_post_type('khesht_team', array_merge($public_rest, array(
        'label' => 'Team',
        'labels' => array('name' => 'تیم', 'singular_name' => 'عضو تیم'),
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
    )));

    register_post_type('khesht_service', array_merge($public_rest, array(
        'label' => 'Services',
        'labels' => array('name' => 'خدمات', 'singular_name' => 'خدمت'),
        'menu_icon' => 'dashicons-hammer',
    )));

    register_post_type('khesht_area_price', array_merge($public_rest, array(
        'label' => 'Area prices',
        'labels' => array('name' => 'برآورد مناطق', 'singular_name' => 'برآورد'),
        'menu_icon' => 'dashicons-chart-area',
        'supports' => array('title', 'editor'),
    )));

    register_post_type('khesht_partner', array_merge($public_rest, array(
        'label' => 'Partners',
        'labels' => array('name' => 'همکاران', 'singular_name' => 'همکار'),
        'menu_icon' => 'dashicons-businessman',
        'supports' => array('title', 'editor', 'thumbnail'),
    )));

    register_post_type('khesht_project', array_merge($public_rest, array(
        'label' => 'Projects',
        'labels' => array('name' => 'پروژه‌ها و نمونه‌کارها', 'singular_name' => 'پروژه'),
        'menu_icon' => 'dashicons-building',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
    )));
}
