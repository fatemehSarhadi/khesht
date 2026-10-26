<?php

if (!defined('ABSPATH')) {
    exit;
}

function khesht_register_rest_routes() {
    $ns = 'khesht/v1';

    register_rest_route($ns, '/site', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_site',
    ));
    register_rest_route($ns, '/team', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_team',
    ));
    register_rest_route($ns, '/services', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_services',
    ));
    register_rest_route($ns, '/partners', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_partners',
    ));
    register_rest_route($ns, '/area-prices', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_area_prices',
    ));
    register_rest_route($ns, '/market-quotes', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_market_quotes',
    ));
    register_rest_route($ns, '/market-insights', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_insights',
    ));
    register_rest_route($ns, '/taxonomies', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_taxonomies',
    ));
    register_rest_route($ns, '/properties', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_properties',
    ));
    register_rest_route($ns, '/properties/(?P<slug>[^/]+)', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_property',
    ));
    register_rest_route($ns, '/magazine', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_magazine',
    ));
    register_rest_route($ns, '/magazine/(?P<slug>[^/]+)', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_article',
    ));
    register_rest_route($ns, '/projects', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_projects',
    ));
    register_rest_route($ns, '/projects/(?P<slug>[^/]+)', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_project',
    ));
    register_rest_route($ns, '/gallery', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_gallery',
    ));
    register_rest_route($ns, '/search', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'khesht_rest_search',
    ));
    register_rest_route($ns, '/consultation', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            return khesht_handle_form_request($request, 'consult');
        },
    ));
    register_rest_route($ns, '/collaboration', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            return khesht_handle_form_request($request, 'collab');
        },
    ));
}

function khesht_rest_site() {
    $st = khesht_settings();
    $heroes = array();
    foreach (khesht_hero_slots() as $slot => $label) {
        if (!empty($st['heroes'][$slot])) {
            $heroes[$slot] = $st['heroes'][$slot];
        }
    }
    return array(
        'site' => array(
            'name' => get_bloginfo('name'),
            'slogan' => get_bloginfo('description'),
            'url' => khesht_frontend_origin(),
            'phone' => $st['phone'],
            'address' => $st['address'],
            'social' => array(
                'instagram' => $st['instagram'],
                'telegram' => $st['telegram'],
                'bale' => $st['bale'],
            ),
            'copyright' => $st['copyright'],
        ),
        'heroes' => (object) $heroes,
        'ceo' => array(
            'name' => $st['ceo_name'],
            'title' => $st['ceo_title'],
            'photo' => $st['ceo_photo'],
            'bio' => wpautop(wp_kses_post($st['ceo_bio'])),
        ),
    );
}

function khesht_rest_team() {
    $q = new WP_Query(array(
        'post_type' => 'khesht_team',
        'posts_per_page' => 50,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));
    $items = array();
    foreach ($q->posts as $post) {
        $items[] = array(
            'id' => $post->ID,
            'slug' => $post->post_name,
            'name' => get_the_title($post),
            'role' => get_post_meta($post->ID, 'khesht_role', true),
            'bio' => wp_strip_all_tags($post->post_content),
            'photo' => khesht_image_url($post->ID),
        );
    }
    return array('items' => $items);
}

function khesht_rest_services() {
    $q = new WP_Query(array(
        'post_type' => 'khesht_service',
        'posts_per_page' => 20,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));
    $items = array();
    foreach ($q->posts as $post) {
        $items[] = array(
            'id' => $post->ID,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'description' => wp_strip_all_tags($post->post_content),
            'icon' => get_post_meta($post->ID, 'khesht_icon', true) ?: 'building',
        );
    }
    return array('items' => $items);
}

function khesht_rest_partners() {
    $q = new WP_Query(array(
        'post_type' => 'khesht_partner',
        'posts_per_page' => 50,
        'post_status' => 'publish',
    ));
    $items = array();
    foreach ($q->posts as $post) {
        $items[] = array(
            'id' => $post->ID,
            'name' => get_the_title($post),
            'description' => wp_strip_all_tags($post->post_content),
            'logo' => khesht_image_url($post->ID),
            'url' => get_post_meta($post->ID, 'khesht_url', true),
        );
    }
    return array('items' => $items);
}

function khesht_rest_area_prices() {
    $q = new WP_Query(array(
        'post_type' => 'khesht_area_price',
        'posts_per_page' => 100,
        'post_status' => 'publish',
    ));
    $items = array();
    foreach ($q->posts as $post) {
        $items[] = array(
            'id' => $post->ID,
            'title' => get_the_title($post),
            'summary' => wp_strip_all_tags($post->post_content),
            'range' => get_post_meta($post->ID, 'khesht_price_range', true),
            'updated_at' => get_post_modified_time('Y-m-d', true, $post),
        );
    }
    return array('items' => $items);
}

function khesht_rest_market_quotes() {
    $cached = get_transient('khesht_market_quotes');
    if ($cached) {
        return $cached;
    }
    $data = khesht_market_adapter()->quotes();
    $source = isset($data['source']) ? $data['source'] : '';
    if ($source === 'mock') {
        return $data; // not connected yet: never cache, so a newly added key works immediately
    }
    // live data: 5 minutes; failed upstream call: retry after 1 minute
    set_transient('khesht_market_quotes', $data, $source === 'error' ? MINUTE_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
    return $data;
}

function khesht_rest_insights() {
    $q = new WP_Query(array(
        'post_type' => 'post',
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'category_name' => 'market',
    ));
    return array('items' => khesht_map_articles($q->posts, false));
}

function khesht_rest_taxonomies() {
    return array(
        'property_type' => khesht_map_terms('khesht_property_type'),
        'transaction_type' => khesht_map_terms('khesht_deal'),
        'location' => khesht_map_terms('khesht_location'),
    );
}

function khesht_map_terms($taxonomy) {
    $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
    if (is_wp_error($terms)) {
        return array();
    }
    $out = array();
    foreach ($terms as $term) {
        $parent = $term->parent ? get_term($term->parent, $taxonomy) : null;
        $out[] = array(
            'slug' => $term->slug,
            'name' => $term->name,
            'parent' => ($parent && !is_wp_error($parent)) ? $parent->slug : null,
        );
    }
    return $out;
}

function khesht_rest_properties(WP_REST_Request $request) {
    $page = max(1, (int) $request->get_param('page'));
    $per = min(24, max(1, (int) ($request->get_param('per_page') ?: 12)));
    $args = array(
        'post_type' => 'khesht_property',
        'post_status' => 'publish',
        'paged' => $page,
        'posts_per_page' => $per,
    );

    $tax = array();
    $type = sanitize_title($request->get_param('type'));
    if ($type) {
        $term = get_term_by('slug', $type, 'khesht_property_type');
        $tax[] = array(
            'taxonomy' => 'khesht_property_type',
            'field' => 'slug',
            'terms' => $type,
            'include_children' => true,
        );
        unset($term);
    }
    $deal = sanitize_title($request->get_param('deal'));
    if ($deal) {
        $tax[] = array(
            'taxonomy' => 'khesht_deal',
            'field' => 'slug',
            'terms' => $deal,
        );
    }
    if ($tax) {
        $args['tax_query'] = $tax;
    }

    $meta = array();
    foreach (array('city' => 'khesht_city', 'region' => 'khesht_region', 'neighborhood' => 'khesht_neighborhood') as $param => $key) {
        $value = sanitize_title($request->get_param($param));
        if ($value) {
            $meta[] = array('key' => $key, 'value' => $value);
        }
    }
    if ($request->get_param('featured') === '1') {
        $meta[] = array('key' => 'khesht_featured', 'value' => '1');
    }
    $pmin = $request->get_param('price_min');
    $pmax = $request->get_param('price_max');
    if ($pmin !== null && $pmin !== '') {
        $meta[] = array('key' => 'khesht_price_amount', 'value' => (float) $pmin, 'compare' => '>=', 'type' => 'NUMERIC');
    }
    if ($pmax !== null && $pmax !== '') {
        $meta[] = array('key' => 'khesht_price_amount', 'value' => (float) $pmax, 'compare' => '<=', 'type' => 'NUMERIC');
    }
    $amin = $request->get_param('area_min');
    $amax = $request->get_param('area_max');
    if ($amin !== null && $amin !== '') {
        $meta[] = array('key' => 'khesht_area_m2', 'value' => (float) $amin, 'compare' => '>=', 'type' => 'NUMERIC');
    }
    if ($amax !== null && $amax !== '') {
        $meta[] = array('key' => 'khesht_area_m2', 'value' => (float) $amax, 'compare' => '<=', 'type' => 'NUMERIC');
    }
    if ($meta) {
        $args['meta_query'] = $meta;
    }

    $q = new WP_Query($args);
    $items = array();
    foreach ($q->posts as $post) {
        $items[] = khesht_property_card($post);
    }
    return array(
        'items' => $items,
        'total' => (int) $q->found_posts,
        'page' => $page,
        'per_page' => $per,
        'total_pages' => (int) $q->max_num_pages,
    );
}

function khesht_property_card($post) {
    $approx = (bool) get_post_meta($post->ID, 'khesht_price_is_approximate', true);
    return array(
        'id' => $post->ID,
        'slug' => $post->post_name,
        'title' => get_the_title($post),
        'image' => khesht_image_url($post->ID),
        'location' => khesht_location_label($post->ID),
        'area_m2' => (float) get_post_meta($post->ID, 'khesht_area_m2', true),
        'price_amount' => (float) get_post_meta($post->ID, 'khesht_price_amount', true),
        'price_is_approximate' => $approx,
        'currency' => get_post_meta($post->ID, 'khesht_currency', true) ?: 'toman',
        'featured' => (bool) get_post_meta($post->ID, 'khesht_featured', true),
        'code' => get_post_meta($post->ID, 'khesht_code', true),
        'url' => '/amlak/property/?id=' . $post->ID,
    );
}

function khesht_find_post($key, $post_type) {
    $key = (string) $key;
    if (ctype_digit($key)) {
        $post = get_post((int) $key);
        if ($post && $post->post_type === $post_type && $post->post_status === 'publish') {
            return $post;
        }
        return null;
    }
    $posts = get_posts(array(
        'name' => sanitize_title($key),
        'post_type' => $post_type,
        'post_status' => 'publish',
        'numberposts' => 1,
    ));
    return $posts ? $posts[0] : null;
}

/** Phone shown on a property page: file-specific number, else the category number, else the main number. */
function khesht_property_phone($post_id) {
    $own = trim((string) get_post_meta($post_id, 'khesht_contact_phone', true));
    if ($own !== '') {
        return $own;
    }
    $map = khesht_setting('type_phones', array());
    if (is_array($map)) {
        foreach (khesht_term_slugs($post_id, 'khesht_property_type') as $slug) {
            if (!empty($map[$slug])) {
                return $map[$slug];
            }
        }
    }
    return (string) khesht_setting('phone', '');
}

function khesht_lines($text) {
    $parts = preg_split('/\r\n|\r|\n/', (string) $text);
    return array_values(array_filter(array_map('trim', $parts), 'strlen'));
}

function khesht_rest_property(WP_REST_Request $request) {
    $post = khesht_find_post($request['slug'], 'khesht_property');
    if (!$post) {
        return new WP_Error('not_found', 'Not found', array('status' => 404));
    }
    $id = $post->ID;
    $card = khesht_property_card($post);
    $card['excerpt'] = $post->post_excerpt;
    $card['body'] = apply_filters('the_content', $post->post_content);
    $card['gallery'] = khesht_gallery_urls($id);
    $card['property_type'] = khesht_term_slugs($id, 'khesht_property_type');
    $card['transaction_type'] = khesht_term_slugs($id, 'khesht_deal');
    $card['property_type_labels'] = wp_get_post_terms($id, 'khesht_property_type', array('fields' => 'names'));
    $card['transaction_labels'] = wp_get_post_terms($id, 'khesht_deal', array('fields' => 'names'));
    $card['city'] = get_post_meta($id, 'khesht_city', true);
    $card['region'] = get_post_meta($id, 'khesht_region', true);
    $card['neighborhood'] = get_post_meta($id, 'khesht_neighborhood', true);
    $card['location'] = array(
        'label' => khesht_location_label($id),
        'city' => $card['city'],
        'region' => $card['region'],
        'neighborhood' => $card['neighborhood'],
    );
    $card['video_url'] = get_post_meta($id, 'khesht_video_url', true);
    $card['negotiable'] = (bool) get_post_meta($id, 'khesht_negotiable', true);
    $card['contact_phone'] = khesht_property_phone($id);
    $card['lat'] = get_post_meta($id, 'khesht_lat', true);
    $card['lng'] = get_post_meta($id, 'khesht_lng', true);
    $card['features'] = khesht_lines(get_post_meta($id, 'khesht_features', true));
    $card['specs'] = array(
        'bedrooms' => get_post_meta($id, 'khesht_bedrooms', true),
        'build_area' => get_post_meta($id, 'khesht_build_area', true),
        'year_built' => get_post_meta($id, 'khesht_year_built', true),
        'build_style' => get_post_meta($id, 'khesht_build_style', true),
        'landscaping' => get_post_meta($id, 'khesht_landscaping', true),
        'deed' => get_post_meta($id, 'khesht_deed', true),
        'floor' => get_post_meta($id, 'khesht_floor', true),
        'parking' => (bool) get_post_meta($id, 'khesht_parking', true),
        'storage' => (bool) get_post_meta($id, 'khesht_storage', true),
        'elevator' => (bool) get_post_meta($id, 'khesht_elevator', true),
    );
    return $card;
}

function khesht_map_articles($posts, $full) {
    $items = array();
    foreach ($posts as $post) {
        $cats = get_the_category($post->ID);
        $cat = $cats ? array('slug' => $cats[0]->slug, 'name' => $cats[0]->name) : null;
        $item = array(
            'id' => $post->ID,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'excerpt' => $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 24),
            'date' => get_the_date('Y-m-d', $post),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'category' => $cat,
            'image' => khesht_image_url($post->ID),
            'url' => '/mag/article/?id=' . $post->ID,
            'reading_minutes' => max(1, (int) ceil(str_word_count(wp_strip_all_tags($post->post_content)) / 180)),
            'media_url' => get_post_meta($post->ID, 'khesht_media_url', true),
        );
        if ($full) {
            $item['body'] = apply_filters('the_content', $post->post_content);
        }
        $items[] = $item;
    }
    return $items;
}

function khesht_rest_magazine(WP_REST_Request $request) {
    $per = (int) $request->get_param('per_page');
    $per = $per > 0 ? min(24, $per) : 9;
    $page = max(1, (int) $request->get_param('page'));
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => $per,
        'paged' => $page,
        'post_status' => 'publish',
    );
    $cat = sanitize_title((string) $request->get_param('category'));
    if ($cat) {
        $args['category_name'] = $cat;
    } else {
        // the CEO blog has its own page; keep it out of the general magazine
        $ceo = get_category_by_slug('ceo');
        if ($ceo) {
            $args['category__not_in'] = array((int) $ceo->term_id);
        }
    }
    $search = trim(sanitize_text_field((string) $request->get_param('s')));
    if ($search !== '') {
        $args['s'] = khesht_normalize_fa($search);
    }
    $q = new WP_Query($args);
    return array(
        'items' => khesht_map_articles($q->posts, false),
        'total' => (int) $q->found_posts,
        'total_pages' => (int) $q->max_num_pages,
        'page' => $page,
    );
}

function khesht_rest_article(WP_REST_Request $request) {
    $post = khesht_find_post($request['slug'], 'post');
    if (!$post) {
        return new WP_Error('not_found', 'Not found', array('status' => 404));
    }
    $items = khesht_map_articles(array($post), true);
    return $items[0];
}

function khesht_gallery_urls($post_id) {
    $ids = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, 'khesht_gallery_ids', true))));
    $urls = array();
    foreach ($ids as $id) {
        $src = wp_get_attachment_image_src($id, 'large');
        if ($src) {
            $urls[] = $src[0];
        }
    }
    return $urls;
}

function khesht_project_item($post, $full = false) {
    $id = $post->ID;
    $item = array(
        'id' => $id,
        'slug' => $post->post_name,
        'title' => get_the_title($post),
        'excerpt' => $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 24),
        'image' => khesht_image_url($id),
        'group' => get_post_meta($id, 'khesht_project_group', true),
        'status' => get_post_meta($id, 'khesht_project_status', true),
        'location' => get_post_meta($id, 'khesht_project_location', true),
        'area' => get_post_meta($id, 'khesht_project_area', true),
        'year' => get_post_meta($id, 'khesht_project_year', true),
        'url' => '/project/?id=' . $id,
    );
    if ($full) {
        $item['body'] = apply_filters('the_content', $post->post_content);
        $item['gallery'] = khesht_gallery_urls($id);
        $item['duration'] = get_post_meta($id, 'khesht_project_duration', true);
        $item['units'] = get_post_meta($id, 'khesht_project_units', true);
        $item['client'] = get_post_meta($id, 'khesht_project_client', true);
        $item['video_url'] = get_post_meta($id, 'khesht_project_video', true);
        $item['features'] = khesht_lines(get_post_meta($id, 'khesht_project_features', true));
    }
    return $item;
}

function khesht_rest_projects(WP_REST_Request $request) {
    $limit = (int) $request->get_param('limit');
    $args = array(
        'post_type' => 'khesht_project',
        'post_status' => 'publish',
        'posts_per_page' => $limit > 0 ? min(60, $limit) : 60,
        'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
    );
    $meta = array();
    $group = sanitize_key((string) $request->get_param('group'));
    if ($group) {
        $meta[] = array('key' => 'khesht_project_group', 'value' => $group);
    }
    $status = sanitize_key((string) $request->get_param('status'));
    if ($status) {
        $meta[] = array('key' => 'khesht_project_status', 'value' => $status);
    }
    if ($meta) {
        $args['meta_query'] = $meta;
    }
    $q = new WP_Query($args);
    $items = array();
    foreach ($q->posts as $post) {
        $items[] = khesht_project_item($post, false);
    }
    return array('items' => $items, 'total' => (int) $q->found_posts);
}

function khesht_rest_project(WP_REST_Request $request) {
    $post = khesht_find_post($request['slug'], 'khesht_project');
    if (!$post) {
        return new WP_Error('not_found', 'Not found', array('status' => 404));
    }
    return khesht_project_item($post, true);
}

function khesht_rest_gallery() {
    $ids = array_filter(array_map('intval', explode(',', (string) get_option('khesht_gallery_ids', ''))));
    $items = array();
    foreach ($ids as $id) {
        $full = wp_get_attachment_image_src($id, 'large');
        if (!$full) {
            continue;
        }
        $thumb = wp_get_attachment_image_src($id, 'medium_large');
        $caption = wp_get_attachment_caption($id);
        $items[] = array(
            'id' => $id,
            'url' => $full[0],
            'thumb' => $thumb ? $thumb[0] : $full[0],
            'caption' => $caption ? $caption : get_the_title($id),
        );
    }
    return array('items' => $items);
}

function khesht_normalize_fa($text) {
    return str_replace(array('ي', 'ك'), array('ی', 'ک'), (string) $text);
}

function khesht_rest_search(WP_REST_Request $request) {
    $term = trim(khesht_normalize_fa(sanitize_text_field((string) $request->get_param('q'))));
    $empty = array('properties' => array(), 'articles' => array(), 'projects' => array());
    if (mb_strlen($term) < 2) {
        return $empty;
    }
    if (!khesht_rate_limit('search', 60, 60)) {
        return new WP_Error('khesht_rate_limited', 'Too many requests', array('status' => 429));
    }

    $run = function ($post_type) use ($term) {
        $q = new WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            's' => $term,
            'posts_per_page' => 6,
            'no_found_rows' => true,
        ));
        return $q->posts;
    };

    $out = $empty;
    foreach ($run('khesht_property') as $post) {
        $card = khesht_property_card($post);
        $out['properties'][] = $card;
    }
    foreach ($run('post') as $post) {
        $cats = get_the_category($post->ID);
        $out['articles'][] = array(
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 18),
            'url' => '/mag/article/?id=' . $post->ID,
            'category' => $cats ? array('slug' => $cats[0]->slug, 'name' => $cats[0]->name) : null,
        );
    }
    foreach ($run('khesht_project') as $post) {
        $group = get_post_meta($post->ID, 'khesht_project_group', true);
        $out['projects'][] = array(
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 18),
            'url' => '/project/?id=' . $post->ID,
            'location' => get_post_meta($post->ID, 'khesht_project_location', true),
        );
    }
    return $out;
}
