<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('bornado_schema_manager_category_vertical_configs', function ($configs) {
    $configs['community'] = array(
        'term_ids'    => array(343),
        'slugs'       => array('community', 'social', 'ejtemaei'),
        'label_fa'    => 'اجتماعی',
        'label_en'    => 'Community',
        'keywords'    => array('اجتماعی', 'Community', 'Social', 'رویداد و اجتماع', 'شبکه اجتماعی ایرانیان', 'Community Events'),
        'about_terms' => array(
            array('name' => 'رویداد و اجتماع', 'alternateName' => 'Community Events'),
            array('name' => 'شبکه اجتماعی ایرانیان', 'alternateName' => 'Iranian Community'),
        ),
    );

    return $configs;
});
