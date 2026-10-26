<?php

if (!defined('ABSPATH')) {
    exit;
}

function khesht_env($key, $default = '') {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    if (defined($key)) {
        return constant($key);
    }
    return $default;
}

function khesht_frontend_origin() {
    return rtrim(khesht_env('KHESHT_FRONTEND_ORIGIN', 'http://localhost:5173'), '/');
}

function khesht_term_slugs($post_id, $taxonomy) {
    $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'slugs'));
    return is_wp_error($terms) ? array() : array_values($terms);
}

function khesht_term_name($post_id, $taxonomy) {
    $terms = wp_get_post_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }
    return $terms[0]->name;
}

function khesht_image_url($post_id) {
    $id = get_post_thumbnail_id($post_id);
    if (!$id) {
        return '';
    }
    $src = wp_get_attachment_image_src($id, 'large');
    return $src ? $src[0] : '';
}

function khesht_location_label($post_id) {
    $parts = array();
    foreach (array('city', 'region', 'neighborhood') as $field) {
        $slug = get_post_meta($post_id, 'khesht_' . $field, true);
        if ($slug) {
            $term = get_term_by('slug', $slug, 'khesht_location');
            if ($term && !is_wp_error($term)) {
                $parts[] = $term->name;
            }
        }
    }
    if ($parts) {
        return implode('، ', $parts);
    }
    return khesht_term_name($post_id, 'khesht_location');
}

function khesht_rate_limit($bucket, $limit = 8, $window = 600) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0';
    $key = 'khesht_rl_' . md5($bucket . $ip);
    $count = (int) get_transient($key);
    if ($count >= $limit) {
        return false;
    }
    set_transient($key, $count + 1, $window);
    return true;
}
