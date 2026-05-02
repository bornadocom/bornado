<?php
global $adforest_theme, $template, $wpdb;
$page_template = basename($template);

get_template_part('template-parts/headers/html', 'head');

$shop_request = function_exists('adforestAPI_getSpecific_headerVal') ? adforestAPI_getSpecific_headerVal('Adforest-Shop-Request') : '';
if ($shop_request === 'body') {
    return;
}

if (isset($adforest_theme['sb_comming_soon_mode']) && $adforest_theme['sb_comming_soon_mode'] == true && !current_user_can('administrator')) {
    return;
}

if ($page_template == 'page-theme-dashboard.php') {
    return;
}

$user_id = get_current_user_id();

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
                $shop_header_style = $adforest_theme['adforest_shop_select_header_style'];
                if (isset($shop_header_style) && $shop_header_style == 'vendor-1') {
                    $page_header_style = 'vendor-1';
                } elseif (isset($shop_header_style) && $shop_header_style == 'vendor-2') {
                    $page_header_style = 'vendor-2';
                }
            }
        }

        if ($page_header_style == 'white') {
            get_template_part('template-parts/headers/header', '1');
        } elseif ($page_header_style == 'header_w_topbar') {
            get_template_part('template-parts/headers/header', '2');
        } elseif ($page_header_style == 'vendor-1') {
            get_template_part('template-parts/headers/header', '3');
        } elseif ($page_header_style == 'vendor-2') {
            get_template_part('template-parts/headers/header', 'cybersale');
        } elseif ($page_header_style == 'transparent') {
            get_template_part('template-parts/headers/header', 'transparent');
        } elseif ($page_header_style == 'search') {
            get_template_part('template-parts/headers/header', '4');
        } elseif ($page_header_style == 'elementor-pro' && in_array('elementor-pro/elementor-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            elementor_theme_do_location('header');
        }
    }
}

// Default header style (if no page-specific header is set)
$header_type = isset($adforest_theme['sb_header']) ? $adforest_theme['sb_header'] : '1';

if ($header_type == 'elementor-pro' && in_array('elementor-pro/elementor-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    elementor_theme_do_location('header');
} else {
    add_action('adforest_action_header_content', 'adforest_header_content_html');
    do_action('adforest_action_header_content');
}