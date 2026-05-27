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

if (!function_exists('bornado_header_clone_get_page_header_style')) {
    /**
     * Resolve the effective header style while preserving parent theme fallbacks.
     *
     * @return string
     */
    function bornado_header_clone_get_page_header_style()
    {
        global $adforest_theme;

        $page_header_style = get_post_meta(get_the_ID(), '_page_header_style', true);
        if (empty($page_header_style)) {
            $page_header_style = $adforest_theme['sb_header'] ?? 'white';
        }

        $change_woo_header = $adforest_theme['shop_change_header'] ?? false;
        if (class_exists('woocommerce') && $change_woo_header && (is_product() || is_product_category() || is_shop())) {
            $shop_header_style = $adforest_theme['adforest_shop_select_header_style'] ?? '';
            if (in_array($shop_header_style, array('vendor-1', 'vendor-2'), true)) {
                $page_header_style = $shop_header_style;
            }
        }

        return (string) $page_header_style;
    }
}

if (!function_exists('bornado_header_clone_render_parent_header')) {
    /**
     * Render a parent header variant by style slug.
     *
     * @param string $page_header_style Header style slug.
     * @return bool True when a parent header or Elementor location was rendered.
     */
    function bornado_header_clone_render_parent_header($page_header_style)
    {
        $header_map = array(
            'white' => '1',
            'header_w_topbar' => '2',
            'vendor-1' => '3',
            'vendor-2' => 'cybersale',
            'transparent' => 'transparent',
            'search' => '4',
            'home_modern' => 'home-modern',
        );

        if (isset($header_map[$page_header_style])) {
            get_template_part('template-parts/headers/header', $header_map[$page_header_style]);
            return true;
        }

        if (
            $page_header_style === 'elementor-pro' &&
            in_array('elementor-pro/elementor-pro.php', apply_filters('active_plugins', get_option('active_plugins')), true)
        ) {
            elementor_theme_do_location('header');
            return true;
        }

        return false;
    }
}

if (!function_exists('adforest_header_content_html')) {
    function adforest_header_content_html()
    {
        $page_header_style = bornado_header_clone_get_page_header_style();

        if ($page_header_style === BORNADO_HEADER_SEARCH_4_CLONE_KEY) {
            if (file_exists(BORNADO_HEADER_SEARCH_4_CLONE_TEMPLATE)) {
                include BORNADO_HEADER_SEARCH_4_CLONE_TEMPLATE;
                return;
            }

            $page_header_style = 'search';
        }

        if (!bornado_header_clone_render_parent_header($page_header_style)) {
            bornado_header_clone_render_parent_header('white');
        }
    }
}

if (!function_exists('bornado_header_clone_sanitize_selected_cats')) {
    /**
     * Normalize the saved header category ids.
     *
     * @param mixed $value Raw Redux field value.
     * @return array<int,int|string>
     */
    function bornado_header_clone_sanitize_selected_cats($value)
    {
        if (!is_array($value)) {
            return array();
        }

        $sanitized = array();
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $term_id = absint($item);
                if ($term_id > 0) {
                    $sanitized[] = $term_id;
                }
            }
        }

        return array_values(array_unique($sanitized));
    }
}

if (!function_exists('bornado_header_clone_cleanup_saved_categories')) {
    /**
     * Keep the stored category selection clean after parent theme updates.
     *
     * @return void
     */
    function bornado_header_clone_cleanup_saved_categories()
    {
        $options = get_option('adforest_theme', array());
        if (!is_array($options) || !isset($options['adforest_header_ad_cats_selection'])) {
            return;
        }

        $original = $options['adforest_header_ad_cats_selection'];
        $sanitized = bornado_header_clone_sanitize_selected_cats($original);
        if ($sanitized === $original) {
            return;
        }

        $options['adforest_header_ad_cats_selection'] = $sanitized;
        update_option('adforest_theme', $options, false);
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

add_action('init', 'bornado_header_clone_cleanup_saved_categories', 1);

add_filter('redux/options/adforest_theme/validate/adforest_header_ad_cats_selection', function ($field, $value) {
    return array(
        'value' => bornado_header_clone_sanitize_selected_cats($value),
    );
}, 10, 2);

/**
 * Replace the parent page header metabox so the clone stays selectable.
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
        $transparent_header = get_post_meta($post->ID, '_adf_transparent_header', true);

        wp_nonce_field('save_page_header_style', 'page_header_style_nonce');
        ?>
        <label for="page_header_style"><?php echo esc_html__('Select Header Style', 'adforest'); ?></label>
        <select name="page_header_style" id="page_header_style">
            <option value="" <?php selected($header_style, ''); ?>><?php echo esc_html__('Default', 'adforest'); ?></option>
            <option value="white" <?php selected($header_style, 'white'); ?>><?php echo esc_html__('Header White', 'adforest'); ?></option>
            <option value="header_w_topbar" <?php selected($header_style, 'header_w_topbar'); ?>><?php echo esc_html__('Header With Top Bar', 'adforest'); ?></option>
            <option value="vendor-1" <?php selected($header_style, 'vendor-1'); ?>><?php echo esc_html__('Header Vendor 1', 'adforest'); ?></option>
            <option value="vendor-2" <?php selected($header_style, 'vendor-2'); ?>><?php echo esc_html__('Header Vendor 2', 'adforest'); ?></option>
            <option value="transparent" <?php selected($header_style, 'transparent'); ?>><?php echo esc_html__('Transparent', 'adforest'); ?></option>
            <option value="search" <?php selected($header_style, 'search'); ?>><?php echo esc_html__('Search', 'adforest'); ?></option>
            <option value="home_modern" <?php selected($header_style, 'home_modern'); ?>><?php echo esc_html__('Header Modern', 'adforest'); ?></option>
            <option value="<?php echo esc_attr(BORNADO_HEADER_SEARCH_4_CLONE_KEY); ?>" <?php selected($header_style, BORNADO_HEADER_SEARCH_4_CLONE_KEY); ?>>
                <?php echo esc_html__(BORNADO_HEADER_SEARCH_4_CLONE_LABEL, 'adforest'); ?>
            </option>
            <option value="elementor-pro" <?php selected($header_style, 'elementor-pro'); ?>><?php echo esc_html__('Header Elementor Pro', 'adforest'); ?></option>
        </select>

        <p style="margin-top:14px;">
            <label for="adf_transparent_header" style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;">
                <input type="checkbox"
                       name="adf_transparent_header"
                       id="adf_transparent_header"
                       value="1"
                       style="margin-top:3px;"
                       <?php checked($transparent_header, '1'); ?>>
                <span>
                    <strong><?php echo esc_html__('Transparent Modern Header', 'adforest'); ?></strong><br>
                    <span style="color:#666;font-size:12px;line-height:1.4;display:block;margin-top:2px;">
                        <?php echo esc_html__('Remove the top spacing so the page content flows under the fixed Modern header. Use this on pages with a custom hero/banner that should sit behind the header.', 'adforest'); ?>
                    </span>
                </span>
            </label>
        </p>
        <?php
    }
}
