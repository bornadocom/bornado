<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('bornado_schema_manager_category_vertical_configs', function ($configs) {
    $configs['property'] = array(
        'term_ids'    => array(338),
        'slugs'       => array('property', 'properties', 'amlak'),
        'label_fa'    => 'املاک',
        'label_en'    => 'Property',
        'keywords'    => array('املاک', 'Property', 'Real Estate', 'آگهی املاک', 'خرید و فروش ملک', 'اجاره مسکن'),
        'about_terms' => array(
            array('name' => 'خرید و فروش ملک', 'alternateName' => 'Real Estate Listings'),
            array('name' => 'اجاره مسکن', 'alternateName' => 'Rentals'),
        ),
    );

    return $configs;
});
