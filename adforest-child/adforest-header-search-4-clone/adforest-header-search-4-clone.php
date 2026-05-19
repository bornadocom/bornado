<?php
/**
 * AdForest - Header Search (4) dedicated clone integration.
 *
 * This file is loaded from the child theme to avoid editing parent theme files.
 */

if (!defined('ABSPATH')) {
    exit;
}

$bornado_header_search_core_paths = array(
    dirname(__DIR__) . '/bornado-search-core/bornado-search-core.php',
    dirname(dirname(__DIR__)) . '/My-Customization/bornado-search-core/bornado-search-core.php',
);
if (!class_exists('Bornado_Search_Core')) {
    foreach ($bornado_header_search_core_paths as $bornado_header_search_core) {
        if (file_exists($bornado_header_search_core)) {
            require_once $bornado_header_search_core;
            break;
        }
    }
}

$bornado_header_location_picker_paths = array(
    dirname(__DIR__) . '/bornado-location-picker/bornado-location-picker.php',
    dirname(dirname(__DIR__)) . '/My-Customization/bornado-location-picker/bornado-location-picker.php',
);
if (!class_exists('Bornado_Location_Picker_Plugin')) {
    foreach ($bornado_header_location_picker_paths as $bornado_header_location_picker) {
        if (file_exists($bornado_header_location_picker)) {
            require_once $bornado_header_location_picker;
            break;
        }
    }
}

if (!defined('BORNADO_HEADER_SEARCH_4_CLONE_KEY')) {
    define('BORNADO_HEADER_SEARCH_4_CLONE_KEY', 'search_4_clone');
}

if (!defined('BORNADO_HEADER_SEARCH_4_CLONE_LABEL')) {
    define('BORNADO_HEADER_SEARCH_4_CLONE_LABEL', 'Header Search (4) - Bornado');
}

if (!defined('BORNADO_HEADER_SEARCH_4_CLONE_TEMPLATE')) {
    define(
        'BORNADO_HEADER_SEARCH_4_CLONE_TEMPLATE',
        dirname(__FILE__) . '/templates/header-search-4-clone.php'
    );
}

if (!function_exists('adforest_header_content_html')) {
    function adforest_header_content_html()
    {
        global $adforest_theme;

        $page_header_style = get_post_meta(get_the_ID(), '_page_header_style', true);
        if (empty($page_header_style)) {
            $page_header_style = $adforest_theme['sb_header'] ?? 'white';
        }

        $change_woo_header = $adforest_theme['shop_change_header'] ?? false;
        if (class_exists('woocommerce') && $change_woo_header) {
            if (is_product() || is_product_category() || is_shop()) {
                $shop_header_style = $adforest_theme['adforest_shop_select_header_style'] ?? '';
                if ($shop_header_style === 'vendor-1') {
                    $page_header_style = 'vendor-1';
                } elseif ($shop_header_style === 'vendor-2') {
                    $page_header_style = 'vendor-2';
                }
            }
        }

        if ($page_header_style === 'white') {
            get_template_part('template-parts/headers/header', '1');
        } elseif ($page_header_style === 'header_w_topbar') {
            get_template_part('template-parts/headers/header', '2');
        } elseif ($page_header_style === 'vendor-1') {
            get_template_part('template-parts/headers/header', '3');
        } elseif ($page_header_style === 'vendor-2') {
            get_template_part('template-parts/headers/header', 'cybersale');
        } elseif ($page_header_style === 'transparent') {
            get_template_part('template-parts/headers/header', 'transparent');
        } elseif ($page_header_style === 'search') {
            get_template_part('template-parts/headers/header', '4');
        } elseif ($page_header_style === BORNADO_HEADER_SEARCH_4_CLONE_KEY) {
            if (file_exists(BORNADO_HEADER_SEARCH_4_CLONE_TEMPLATE)) {
                include BORNADO_HEADER_SEARCH_4_CLONE_TEMPLATE;
            } else {
                get_template_part('template-parts/headers/header', '4');
            }
        } elseif (
            $page_header_style === 'elementor-pro' &&
            in_array('elementor-pro/elementor-pro.php', apply_filters('active_plugins', get_option('active_plugins')), true)
        ) {
            elementor_theme_do_location('header');
        }
    }
}

/**
 * Add cloned style into Header Style image selector in theme options.
 */
add_filter('redux/options/adforest_theme/field/sb_header', function ($field) {
    if (!is_array($field)) {
        return $field;
    }

    if (!isset($field['options']) || !is_array($field['options'])) {
        $field['options'] = array();
    }

    if (!isset($field['options'][BORNADO_HEADER_SEARCH_4_CLONE_KEY])) {
        $field['options'][BORNADO_HEADER_SEARCH_4_CLONE_KEY] = array(
            'alt' => esc_html__(BORNADO_HEADER_SEARCH_4_CLONE_LABEL, 'adforest'),
            'img' => trailingslashit(get_template_directory_uri()) . 'images/headers/header-4.png',
        );
    }

    return $field;
});

/**
 * Show Header Search fields when either original or clone header is selected.
 */
add_filter('redux/options/adforest_theme/field/adforest_header_ad_cats_selection', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_enabled_fields', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_keyword_label', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_keyword_placeholder', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_ad_type_label', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_ad_type_placeholder', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_location_label', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_location_placeholder', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_category_label', 'bornado_header_clone_expand_required_rule');
add_filter('redux/options/adforest_theme/field/header_search_category_placeholder', 'bornado_header_clone_expand_required_rule');

if (!function_exists('bornado_header_clone_expand_required_rule')) {
    function bornado_header_clone_expand_required_rule($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        $field['required'] = array(
            'sb_header',
            '=',
            array('search', BORNADO_HEADER_SEARCH_4_CLONE_KEY),
        );

        return $field;
    }
}

/**
 * Extend per-page header style dropdown with the new clone.
 */
add_action('add_meta_boxes', function () {
    remove_meta_box('header_style_meta_box', 'page', 'side');

    add_meta_box(
        'header_style_meta_box',
        __('Header Style', 'adforest'),
        'bornado_header_style_meta_box_callback',
        'page',
        'side',
        'default'
    );
}, 100);

if (!function_exists('bornado_header_style_meta_box_callback')) {
    function bornado_header_style_meta_box_callback($post)
    {
        $header_style = get_post_meta($post->ID, '_page_header_style', true);
        wp_nonce_field('save_page_header_style', 'page_header_style_nonce');
        ?>
        <label for="page_header_style"><?php echo __('Select Header Style', 'adforest'); ?></label>
        <select name="page_header_style" id="page_header_style">
            <option value="" <?php selected($header_style, ''); ?>><?php echo esc_html__('Default', 'adforest'); ?></option>
            <option value="white" <?php selected($header_style, 'white'); ?>><?php echo esc_html__('Header White', 'adforest'); ?></option>
            <option value="header_w_topbar" <?php selected($header_style, 'header_w_topbar'); ?>><?php echo esc_html__('Header With Top Bar', 'adforest'); ?></option>
            <option value="vendor-1" <?php selected($header_style, 'vendor-1'); ?>><?php echo esc_html__('Header Vendor 1', 'adforest'); ?></option>
            <option value="vendor-2" <?php selected($header_style, 'vendor-2'); ?>><?php echo esc_html__('Header Vendor 2', 'adforest'); ?></option>
            <option value="transparent" <?php selected($header_style, 'transparent'); ?>><?php echo esc_html__('Transparent', 'adforest'); ?></option>
            <option value="search" <?php selected($header_style, 'search'); ?>><?php echo esc_html__('Search', 'adforest'); ?></option>
            <option value="<?php echo esc_attr(BORNADO_HEADER_SEARCH_4_CLONE_KEY); ?>" <?php selected($header_style, BORNADO_HEADER_SEARCH_4_CLONE_KEY); ?>>
                <?php echo esc_html__(BORNADO_HEADER_SEARCH_4_CLONE_LABEL, 'adforest'); ?>
            </option>
        }

        $original = $options['adforest_header_ad_cats_selection'];
        $sanitized = bornado_header_clone_sanitize_selected_cats($original);

        if ($sanitized !== $original) {
            $options['adforest_header_ad_cats_selection'] = $sanitized;
            update_option('adforest_theme', $options);
        }
    }
}

add_action('init', 'bornado_header_clone_cleanup_saved_categories', 1);

add_filter('redux/options/adforest_theme/validate/adforest_header_ad_cats_selection', function ($field, $value, $existing_value) {
    $value = bornado_header_clone_sanitize_selected_cats($value);
    return array(
        'value' => $value,
    );
}, 10, 3);

/**
 * Extend per-page header style dropdown with the new clone.
 */
add_action('add_meta_boxes', function () {
    remove_meta_box('header_style_meta_box', 'page', 'side');

    add_meta_box(
        'header_style_meta_box',
        __('Header Style', 'adforest'),
        'bornado_header_style_meta_box_callback',
        'page',
        'side',
        'default'
    );
}, 100);

if (!function_exists('bornado_header_style_meta_box_callback')) {
    function bornado_header_style_meta_box_callback($post)
    {
        $header_style = get_post_meta($post->ID, '_page_header_style', true);
        wp_nonce_field('save_page_header_style', 'page_header_style_nonce');
        ?>
        <label for="page_header_style"><?php echo __('Select Header Style', 'adforest'); ?></label>
        <select name="page_header_style" id="page_header_style">
            <option value="" <?php selected($header_style, ''); ?>><?php echo esc_html__('Default', 'adforest'); ?></option>
            <option value="white" <?php selected($header_style, 'white'); ?>><?php echo esc_html__('Header White', 'adforest'); ?></option>
            <option value="header_w_topbar" <?php selected($header_style, 'header_w_topbar'); ?>><?php echo esc_html__('Header With Top Bar', 'adforest'); ?></option>
            <option value="vendor-1" <?php selected($header_style, 'vendor-1'); ?>><?php echo esc_html__('Header Vendor 1', 'adforest'); ?></option>
            <option value="vendor-2" <?php selected($header_style, 'vendor-2'); ?>><?php echo esc_html__('Header Vendor 2', 'adforest'); ?></option>
            <option value="transparent" <?php selected($header_style, 'transparent'); ?>><?php echo esc_html__('Transparent', 'adforest'); ?></option>
            <option value="search" <?php selected($header_style, 'search'); ?>><?php echo esc_html__('Search', 'adforest'); ?></option>
            <option value="<?php echo esc_attr(BORNADO_HEADER_SEARCH_4_CLONE_KEY); ?>" <?php selected($header_style, BORNADO_HEADER_SEARCH_4_CLONE_KEY); ?>>
                <?php echo esc_html__(BORNADO_HEADER_SEARCH_4_CLONE_LABEL, 'adforest'); ?>
            </option>
            <option value="elementor-pro" <?php selected($header_style, 'elementor-pro'); ?>><?php echo esc_html__('Header Elementor Pro', 'adforest'); ?></option>
        </select>
        <?php
    }
}
