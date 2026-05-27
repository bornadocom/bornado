<?php
global $adforest_theme;
$adforest_theme = get_option('adforest_theme');

load_theme_textdomain('adforest', trailingslashit(get_template_directory()) . 'languages/');
add_theme_support('woocommerce');

// Add default posts and comments RSS feed links to head.
add_theme_support('automatic-feed-links');
add_theme_support('wc-product-gallery-zoom');
add_theme_support('wc-product-gallery-lightbox');
add_theme_support('wc-product-gallery-slider');
add_theme_support('post-thumbnails', array('post', 'project'));

$crop_ad_images = isset($adforest_theme['crop_ad_images']) && $adforest_theme['crop_ad_images'] == false ? false : true;
if(in_array('dc-woocommerce-multi-vendor/dc_product_vendor.php', apply_filters('active_plugins', get_option('active_plugins')))){


    $crop_ad_images = isset($adforest_theme['crop_ad_images']) && $adforest_theme['crop_ad_images'] == false ? false : true;
    add_image_size('adforest_vendor_store_front_grid', 276, 140, $crop_ad_images);

}
add_image_size('adforest_single_product', 540, 400, $crop_ad_images);
add_image_size('adforest-ad-related', 300, 224, $crop_ad_images);
add_image_size('adforest-ad-list', 350, 220, $crop_ad_images);
add_image_size('adforest-ad-thumb', 120, 63, $crop_ad_images);
add_image_size('adforest-single-post', 760, 410, $crop_ad_images);
add_image_size('adforest-single-small', 80, 80, $crop_ad_images);
add_image_size('adforest-shop-home', 265, 350, $crop_ad_images);

/*
 * Let WordPress manage the document title.
 * By adding theme support, we declare that this theme does not use a
 * hard-coded <title> tag in the document head, and expect WordPress to
 * provide it for us.
 */
add_theme_support('title-tag');
// Theme editor style
add_editor_style('editor.css');

if(isset($adforest_theme['sb_block_widget']) &&  !$adforest_theme['sb_block_widget']){
    remove_theme_support( 'widgets-block-editor');
}

/*
 * Enable support for Post Thumbnails on posts and pages.
 *
 * @link https://developer.wordpress.org/themes/functionality/featured-SB_TAMEER_IMAGES-post-thumbnails/
 */

/* This theme uses wp_nav_menu() in one location. */
register_nav_menus(array('main_menu' => esc_html__('Adforest Primary Menu', 'adforest'),));
register_nav_menus(array('footer_main_menu' => esc_html__('Adforest footer-6 , footer-7 Menu', 'adforest'),));
register_nav_menus(array('wc_menu' => esc_html__('Adforest Multivendor Menu', 'adforest'),));

register_nav_menus(array('footer_1' => esc_html__('adforest Footer 1', 'adforest'),));
register_nav_menus(array('footer_2' => esc_html__('adforest Footer 2', 'adforest'),));
register_nav_menus(array('footer_3' => esc_html__('adforest Footer 3', 'adforest'),));
register_nav_menus(array('footer_4' => esc_html__('adforest Footer 4', 'adforest'),));

/* Registrering all sidebars for  themes */
add_action('widgets_init', 'adforest_themes_sidebar_widgets_init');
if (!function_exists('adforest_themes_sidebar_widgets_init')) {

    function adforest_themes_sidebar_widgets_init()
    {
        register_sidebar(
            array(
                'name' => esc_html__('adforest Sidebar', 'adforest'),
                'id' => 'adforest_theme_sidebar',
                'before_widget' => '<div class="widget widget-content"><div id="%1$s">',
                'after_widget' => '</div></div>',
                'before_title' => '<div class="widget-heading"><h4 class="panel-title"><span>',
                'after_title' => '</span></h4></div>'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('Adforest Vertical Advert', 'adforest'),
                'id' => 'adforest_vertical_advert',
                'before_widget' => '<div class="widget widget-content"><div id="%1$s">',
                'after_widget' => '</div></div>',
                'before_title' => '<div class="widget-heading"><h4 class="panel-title"><span>',
                'after_title' => '</span></h4></div>'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('AdForest Woo-Commerce Sidebar', 'adforest'),
                'id' => 'adforest_woocommerce_widget',
                'before_widget' => '<div class="widget %2$s"><div class="widget-content saftey">',
                'after_widget' => '</div></div>',
                'before_title' => '<div class="widget-heading"><div class="panel-title"><a>',
                'after_title' => '</a></div></div>'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('AdForest Woo-Commerce Detail page', 'adforest'),
                'id' => 'adforest_woocommerce_detail_widget',
                'before_widget' => '<div class="widget %2$s">',
                'after_widget' => '</div></div>',
                'before_title' => '<div class="widget-heading"><div class="panel-title"><a>',
                'after_title' => '</a></div></div><div class="widget-content saftey">'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('adforest Grid Sidebar', 'adforest'),
                'id' => 'sb_themes_grid_sidebar',
                'before_widget' => '<div class="widget widget-content"><div id="%1$s">',
                'after_widget' => '</div></div>',
                'before_title' => '<div class="widget-heading"><h4 class="panel-title"><span>',
                'after_title' => '</span></h4></div>'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('Single Ad Bottom/ Profile Page', 'adforest'),
                'id' => 'adforest_ad_sidebar_bottom',
                'before_widget' => '<div class="widget">',
                'after_widget' => '</div></div>',
                'before_title' => '<div class="widget-heading"><div class="panel-title"><span>',
                'after_title' => '</span></div></div><div class="widget-content saftey">'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('Ads Search', 'adforest'),
                'id' => 'adforest_search_sidebar',
                'before_widget' => '',
                'after_widget' => '',
                'before_title' => '<div class="panel-heading"><h4 class="panel-title">',
                'after_title' => '</h4></div>'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('Category Search - Sidebar', 'adforest'),
                'id' => 'adforest_cat_search',
                'before_widget' => '<div class="panel panel-default sb-default-widget">',
                'after_widget' => '</div>',
                'before_title' => '<div class="panel-heading"><h4 class="panel-title">',
                'after_title' => '</h4></div>'
            )
        );

        register_sidebar(
            array(
                'name' => esc_html__('Location Search - Sidebar', 'adforest'),
                'id' => 'adforest_location_search',
                'before_widget' => '<div class="panel panel-default sb-default-widget">',
                'after_widget' => '</div>',
                'before_title' => '<div class="panel-heading"><h4 class="panel-title">',
                'after_title' => '</h4></div>'
            )
        );
        register_sidebar(
            array(
                'name' => esc_html__('Users  Sidebar', 'adforest'),
                'id' => 'sb_themes_user_sidebar',
                'before_widget' => '<div class="widget widget-content"><div id="%1$s">',
                'after_widget' => '</div></div>',
                'before_title' => '<div class="widget-heading"><h4 class="panel-title"><span>',
                'after_title' => '</span></h4></div>'
            )
        );
    }
}