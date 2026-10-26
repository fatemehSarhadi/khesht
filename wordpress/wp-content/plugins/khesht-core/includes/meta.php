<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Field definitions shared by registration, meta boxes and REST. */
function khesht_meta_defs() {
    return array(
        'khesht_property' => array(
            'title' => 'مشخصات ملک',
            'fields' => array(
                array('key' => 'khesht_area_m2', 'label' => 'متراژ (متر مربع)', 'type' => 'number'),
                array('key' => 'khesht_price_amount', 'label' => 'قیمت (تومان)', 'type' => 'number'),
                array('key' => 'khesht_price_is_approximate', 'label' => 'قیمت حدودی است', 'type' => 'checkbox'),
                array('key' => 'khesht_featured', 'label' => 'فایل ویژه', 'type' => 'checkbox'),
                array('key' => 'khesht_city', 'label' => 'شهر', 'type' => 'term'),
                array('key' => 'khesht_region', 'label' => 'منطقه', 'type' => 'term'),
                array('key' => 'khesht_neighborhood', 'label' => 'محله', 'type' => 'term'),
                array('key' => 'khesht_lat', 'label' => 'عرض جغرافیایی (lat)', 'type' => 'text'),
                array('key' => 'khesht_lng', 'label' => 'طول جغرافیایی (lng)', 'type' => 'text'),
                array('key' => 'khesht_gallery_ids', 'label' => 'گالری تصاویر (تصویر شاخص = عکس کارت)', 'type' => 'gallery'),
                array('key' => 'khesht_negotiable', 'label' => 'قیمت قابل مذاکره است', 'type' => 'checkbox'),
                array('key' => 'khesht_code', 'label' => 'کد فایل', 'type' => 'text'),
                array('key' => 'khesht_video_url', 'label' => 'لینک بازدید ویدئویی (آپارات، یوتیوب یا ...)', 'type' => 'url'),
                array('key' => 'khesht_bedrooms', 'label' => 'تعداد خواب', 'type' => 'number'),
                array('key' => 'khesht_build_area', 'label' => 'متراژ بنا (متر مربع)', 'type' => 'number'),
                array('key' => 'khesht_year_built', 'label' => 'سال ساخت', 'type' => 'text'),
                array('key' => 'khesht_build_style', 'label' => 'سبک ساخت', 'type' => 'text'),
                array('key' => 'khesht_landscaping', 'label' => 'محوطه‌سازی', 'type' => 'text'),
                array('key' => 'khesht_deed', 'label' => 'نوع سند', 'type' => 'text'),
                array('key' => 'khesht_floor', 'label' => 'طبقه', 'type' => 'text'),
                array('key' => 'khesht_parking', 'label' => 'پارکینگ دارد', 'type' => 'checkbox'),
                array('key' => 'khesht_storage', 'label' => 'انباری دارد', 'type' => 'checkbox'),
                array('key' => 'khesht_elevator', 'label' => 'آسانسور دارد', 'type' => 'checkbox'),
                array('key' => 'khesht_features', 'label' => 'امکانات (هر مورد در یک خط)', 'type' => 'textarea'),
                array('key' => 'khesht_contact_phone', 'label' => 'شماره‌ی تماس اختصاصی این فایل (اختیاری)', 'type' => 'text'),
            ),
        ),
        'khesht_project' => array(
            'title' => 'مشخصات پروژه',
            'fields' => array(
                array('key' => 'khesht_project_group', 'label' => 'دسته', 'type' => 'select', 'options' => array('sakht' => 'ساخت‌وساز / نمونه‌کار', 'shahrak' => 'شهرک‌سازی')),
                array('key' => 'khesht_project_status', 'label' => 'وضعیت', 'type' => 'select', 'options' => array('done' => 'اجراشده', 'ongoing' => 'در حال اجرا')),
                array('key' => 'khesht_project_location', 'label' => 'موقعیت (متن)', 'type' => 'text'),
                array('key' => 'khesht_project_area', 'label' => 'متراژ / تعداد واحد (متن)', 'type' => 'text'),
                array('key' => 'khesht_project_year', 'label' => 'سال', 'type' => 'text'),
                array('key' => 'khesht_project_duration', 'label' => 'مدت اجرا', 'type' => 'text'),
                array('key' => 'khesht_project_units', 'label' => 'تعداد واحد', 'type' => 'text'),
                array('key' => 'khesht_project_client', 'label' => 'کارفرما / مالک', 'type' => 'text'),
                array('key' => 'khesht_project_features', 'label' => 'ویژگی‌ها (هر مورد در یک خط)', 'type' => 'textarea'),
                array('key' => 'khesht_project_video', 'label' => 'لینک ویدئو (اختیاری)', 'type' => 'url'),
                array('key' => 'khesht_gallery_ids', 'label' => 'گالری تصاویر', 'type' => 'gallery'),
            ),
        ),
        'khesht_team' => array(
            'title' => 'مشخصات عضو',
            'fields' => array(
                array('key' => 'khesht_role', 'label' => 'سمت در مجموعه', 'type' => 'text'),
            ),
        ),
        'khesht_service' => array(
            'title' => 'نمایش خدمت',
            'fields' => array(
                array('key' => 'khesht_icon', 'label' => 'آیکون', 'type' => 'select', 'options' => array(
                    'scale' => 'ترازو (خرید و فروش)', 'key' => 'کلید (رهن و اجاره)', 'ruler' => 'خط‌کش (کارشناسی)',
                    'chat' => 'گفت‌وگو (مشاوره)', 'hammer' => 'چکش (ساخت‌وساز)', 'handshake' => 'دست دادن (مشارکت)', 'city' => 'شهر', 'building' => 'ساختمان',
                )),
            ),
        ),
        'khesht_area_price' => array(
            'title' => 'برآورد قیمت منطقه',
            'fields' => array(
                array('key' => 'khesht_price_range', 'label' => 'بازه‌ی قیمت (متن، مثلاً «۳۰ تا ۴۵ میلیون تومان به ازای هر متر»)', 'type' => 'text'),
                array('key' => 'khesht_city', 'label' => 'محدوده', 'type' => 'term'),
            ),
        ),
        'khesht_partner' => array(
            'title' => 'مشخصات همکار',
            'fields' => array(
                array('key' => 'khesht_url', 'label' => 'وب‌سایت / لینک', 'type' => 'url'),
            ),
        ),
        'post' => array(
            'title' => 'پادکست / رسانه',
            'fields' => array(
                array('key' => 'khesht_media_url', 'label' => 'لینک فایل صوتی یا ویدیو (برای دسته‌ی «پادکست و رسانه»)', 'type' => 'url'),
            ),
        ),
    );
}

function khesht_register_meta() {
    foreach (khesht_meta_defs() as $post_type => $def) {
        foreach ($def['fields'] as $field) {
            switch ($field['type']) {
                case 'number':
                    $type = 'number';
                    break;
                case 'checkbox':
                    $type = 'boolean';
                    break;
                default:
                    $type = 'string';
            }
            register_post_meta($post_type, $field['key'], array(
                'type' => $type,
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ));
        }
    }
}
