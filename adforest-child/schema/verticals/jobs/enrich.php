<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('bornado_schema_manager_category_vertical_configs', function ($configs) {
    $configs['jobs'] = array(
        'term_ids'    => array(339),
        'slugs'       => array('jobs', 'job', 'careers', 'employment', 'estekhdam'),
        'label_fa'    => 'استخدام و کاریابی',
        'label_en'    => 'Jobs',
        'keywords'    => array('استخدام', 'کاریابی', 'Jobs', 'Employment', 'فرصت شغلی', 'Recruitment'),
        'about_terms' => array(
            array('name' => 'فرصت شغلی', 'alternateName' => 'Job Opportunities'),
            array('name' => 'استخدام', 'alternateName' => 'Recruitment'),
        ),
    );

    return $configs;
});
