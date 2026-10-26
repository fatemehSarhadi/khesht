<?php

if (!defined('ABSPATH')) {
    exit;
}

function khesht_maybe_fire_rebuild_webhook($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
        return;
    }
    $types = array(
        'khesht_property',
        'khesht_team',
        'khesht_service',
        'khesht_partner',
        'khesht_area_price',
        'khesht_project',
        'post',
        'page',
    );
    if (!in_array($post->post_type, $types, true)) {
        return;
    }
    if (!in_array($post->post_status, array('publish', 'private', 'draft'), true)) {
        return;
    }
    khesht_fire_rebuild_webhook($post_id, $post->post_type);
}

function khesht_fire_rebuild_webhook($post_id, $post_type) {
    $url = khesht_env('KHESHT_FRONTEND_REBUILD_URL', '');
    $secret = khesht_env('KHESHT_WEBHOOK_SECRET', '');
    if (!$url || !$secret) {
        return;
    }
    $payload = wp_json_encode(array(
        'id' => $post_id,
        'type' => $post_type,
        'time' => time(),
    ));
    $sig = hash_hmac('sha256', $payload, $secret);
    wp_remote_post($url, array(
        'timeout' => 8,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Khesht-Signature' => $sig,
        ),
        'body' => $payload,
        'blocking' => false,
    ));
}
