<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('bornado_schema_manager_category_vertical_configs', function ($configs) {
    $configs['services'] = array(
        'term_ids'    => array(341),
        'slugs'       => array('services', 'service', 'khadamat'),
        'label_fa'    => 'خدمات',
        'label_en'    => 'Services',
        'keywords'    => array('خدمات', 'Services', 'Service Providers', 'آگهی خدمات', 'خدمات محلی', 'Local Services'),
        'about_terms' => array(
            array('name' => 'ارائه دهندگان خدمات', 'alternateName' => 'Service Providers'),
            array('name' => 'خدمات محلی', 'alternateName' => 'Local Services'),
        ),
    );

    return $configs;
});
