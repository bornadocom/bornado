<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('bornado_schema_manager_category_vertical_configs', function ($configs) {
    $configs['items'] = array(
        'term_ids'    => array(342),
        'slugs'       => array('items', 'goods', 'products', 'kala'),
        'label_fa'    => 'کالا و لوازم',
        'label_en'    => 'Items',
        'keywords'    => array('کالا', 'لوازم', 'Items', 'Goods', 'خرید و فروش کالا', 'Marketplace Items'),
        'about_terms' => array(
            array('name' => 'کالا و لوازم', 'alternateName' => 'Goods and Items'),
            array('name' => 'خرید و فروش کالا', 'alternateName' => 'Marketplace Items'),
        ),
    );

    return $configs;
});
