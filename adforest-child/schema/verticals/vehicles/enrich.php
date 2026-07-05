<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('bornado_schema_manager_category_vertical_configs', function ($configs) {
    $configs['vehicles'] = array(
        'term_ids'    => array(340),
        'slugs'       => array('vehicles', 'vehicle', 'cars', 'auto', 'khodro'),
        'label_fa'    => 'وسایل نقلیه',
        'label_en'    => 'Vehicles',
        'keywords'    => array('وسایل نقلیه', 'خودرو', 'Vehicles', 'Cars', 'خرید و فروش خودرو', 'Vehicle Marketplace'),
        'about_terms' => array(
            array('name' => 'خودرو', 'alternateName' => 'Cars'),
            array('name' => 'خرید و فروش خودرو', 'alternateName' => 'Vehicle Marketplace'),
        ),
    );

    return $configs;
});
