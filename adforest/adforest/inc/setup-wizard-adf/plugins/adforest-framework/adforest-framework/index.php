<?php
/**
 * Plugin Name: Adforest Framework
 * Plugin URI: https://themeforest.net/user/scriptsbundle/
 * Description: This plugin is essential for the proper theme funcationality.
 * Version: 5.0.14
 * Author: Scripts Bundle
 * Author URI: https://themeforest.net/user/scriptsbundle/
 * License: GPL2
 * Text Domain: redux-framework 
*/

$my_theme = wp_get_theme();
$my_theme->get('Name');

define('SB_PLUGIN_FRAMEWORK_PATH', plugin_dir_path(__FILE__));

define('SB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SB_THEMEURL_PLUGIN', get_template_directory_uri() . '/');
define('SB_IMAGES_PLUGIN', SB_THEMEURL_PLUGIN . 'images/');
define('SB_CSS_PLUGIN', SB_THEMEURL_PLUGIN . 'css/');

/* For Redux Framework */
require SB_PLUGIN_FRAMEWORK_PATH . '/admin-init.php';

/* For Add to Cart */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    require SB_PLUGIN_PATH . 'cart/cart-js.php';
}

/* For Metaboxes */
require SB_PLUGIN_PATH . 'cpt/index.php';
require SB_PLUGIN_PATH . 'ad-post/index.php';
/* For Metaboxes */

/* For Metaboxes  User profile */
require SB_PLUGIN_PATH . 'user_profile/index.php';
/* For Metaboxes  User profile */

/* Category Templates */
require SB_PLUGIN_PATH . 'ad-post/custom_category_templates.php';
/* Category Templates */

/* For Newsletter Contact Us */
if ($my_theme->get('Name') == 'adforest' || $my_theme->get('Name') == 'adforest child' || $my_theme->get('Name') == 'adforest pro') {
    require SB_PLUGIN_PATH . 'js/newsletter_email.php';
}
/* For Newsletter Email */

/* Theme functions */
require SB_PLUGIN_PATH . 'functions.php';
require SB_PLUGIN_PATH . 'inc/class-commom-email-templates.php';
require SB_PLUGIN_PATH . 'inc/adforest-common-functions.php';
/* Theme functions */

///* For Custom Fields in Events */
//var_dump(class_exists('ACF'));
//if (class_exists('ACF')) {
//    echo "In Custom field init";
//    require SB_PLUGIN_PATH . 'custom-fields/ad_custom_fields.php';
//}
/* For Custom Fields in Events */

/* For Timezone */
require SB_PLUGIN_PATH . 'adforest_timezones.php';
/* For Timezone */


add_action('wp_enqueue_scripts', 'adforest_theme_scripts');

if (!function_exists('adforest_theme_scripts')) {
    function adforest_theme_scripts()
    {
        wp_register_script('adforest-theme-js', plugin_dir_url(__FILE__) . 'js/theme.js', false, false, true);
        wp_register_script('adforest-jquery-ui', plugin_dir_url(__FILE__) . 'js/jquery-ui.js', false, false, true);

        wp_enqueue_script('adforest-theme-js');
    }
}

add_action('admin_enqueue_scripts', 'sb_framework_scripts', 0);

if (!function_exists('sb_framework_scripts')) {
    function sb_framework_scripts()
    {
        wp_enqueue_style('sb-frm-plugin-css', plugin_dir_url(__FILE__) . 'css/plugin.css');
        wp_enqueue_script('sb-frm-plugin-js', plugin_dir_url(__FILE__) . 'js/plugin.js', false, false, true);
        wp_localize_script(
            'sb-frm-plugin-js',
            'adforestFramework',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonces' => array(
                    'deleteUserRating' => wp_create_nonce('sb_delete_user_rating_nonce'),
                    'deleteUserBid' => wp_create_nonce('sb_delete_user_bid_nonce'),
                ),
                'i18n' => array(
                    'genericError' => esc_html__('Request failed. Please reload the page and try again.', 'redux-framework'),
                ),
            )
        );
    }
}
if (class_exists("Redux")) {
    add_action('wp', 'remove_admin_bar');

    if (!function_exists('remove_admin_bar')) {
        function remove_admin_bar()
        {
            global $adforest_theme;
            if ($adforest_theme['admin_bar']) {
                if (is_user_logged_in()) {
                    show_admin_bar(true);
                }
            } else {
                show_admin_bar(false);
            }

            if (is_user_logged_in() && !is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
                $user = wp_get_current_user();
                if (in_array('subscriber', $user->roles) || in_array('dc_vendor', $user->roles)) {
                    show_admin_bar(false);
                }
            }
        }
    }
}

/*Load text domain*/
add_action('plugins_loaded', 'adforest_framework_load_plugin_textdomain');

if (!function_exists('adforest_framework_load_plugin_textdomain')) {
    function adforest_framework_load_plugin_textdomain() {
        load_plugin_textdomain('redux-framework', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

/* Theme Options */
require SB_PLUGIN_PATH . 'theme_options_fmw.php';
/* Theme Options */

/*On Plugin Activation.*/
register_activation_hook(__FILE__, 'adforest_framework_activate');
if (!function_exists('adforest_framework_activate')) {
    function adforest_framework_activate()
    {
        // creating location table
        global $wpdb;

        $table_name = $wpdb->prefix . 'adforest_locations';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
    		  lid int NOT NULL AUTO_INCREMENT,
    		  name varchar(100) NOT NULL,
    		  latitude varchar(200) NOT NULL,
    		  longitude varchar(200) NOT NULL,
    		  country_id int NOT NULL,
    		  state_id int NOT NULL,
    		  location_type varchar(20) NOT NULL,
    		  PRIMARY KEY  (lid)
    		) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            $wpdb->query("insert into $table_name (`lid`, `name`, `latitude`, `longitude`, `country_id`, `state_id`, `location_type`) values 
    		(1, 'Punjab', '31.1704063', '72.7097161', 23, 0, 'state'),
                    (2, 'Lahore', '31.5546061', '74.3571581', 23, 1, 'city'),
                    (9, 'AL', '32.3182314', '-86.902298', 56, 0, 'state'),
                    (10, 'Birmingham', '33.5206608', '-86.80249', 56, 9, 'city'),
                    (13, 'IL', '', '', 56, 0, 'state'),
                    (14, 'Chicago', '41.8781136', '-87.6297982', 56, 13, 'city'),
                    (15, 'NC', '35.7595731', '-79.0192997', 56, 0, 'state'),
                    (16, 'Charlotte', '35.2270869', '-80.8431267', 56, 15, 'city'),
                    (17, 'Urbana', '40.1105875', '-88.2072697', 56, 13, 'city'),
                    (18, 'FL', '27.6648274', '-81.5157535', 56, 0, 'state'),
                    (19, 'Sydney', '27.9633563', '-82.2073118', 56, 18, 'city'),
                    (20, 'AL', '32.3182314', '-86.902298', 0, 0, 'state'),
                    (21, 'Birmingham', '33.5206608', '-86.80249', 0, 20, 'city'),
                    (22, 'Multan', '30.1983807', '71.4687028', 23, 1, 'city'),
                    (23, 'CA', '36.778261', '-119.4179324', 56, 0, 'state'),
                    (24, 'Los Angeles', '34.0522342', '-118.2436849', 56, 23, 'city'),
                    (25, '', '', '', 131, 0, 'state'),
                    (26, 'aajsaksjas', '', '', 131, 25, 'city'),
                    (27, 'NY', '43.2994285', '-74.2179326', 56, 0, 'state'),
                    (28, 'New York', '43.2994285', '-74.2179326', 56, 27, 'city'),
                    (29, 'NJ', '40.0583238', '-74.4056612', 56, 0, 'state'),
                    (30, 'Jersey City', '40.7281575', '-74.0776417', 56, 29, 'city'),
                    (31, 'Newark', '40.735657', '-74.1723667', 56, 29, 'city'),
                    (32, 'WA', '47.7510741', '-120.7401386', 56, 0, 'state'),
                    (33, 'Central Park', '44.938014', '-93.078205', 56, 32, 'city'),
                    (34, 'Cheektowaga', '42.9026136', '-78.744572', 56, 27, 'city'),
                    (35, 'Nyack', '41.0906519', '-73.9179146', 56, 27, 'city'),
                    (36, 'Albany', '42.6525793', '-73.7562317', 56, 27, 'city'),
                    (37, 'OR', '43.8041334', '-120.5542012', 56, 0, 'state'),
                    (38, 'Nyssa', '43.8768289', '-116.9948804', 56, 37, 'city'),
                    (39, 'Dover', '39.158168', '-75.5243682', 56, 29, 'city'),
                    (40, 'DC', '38.9071923', '-77.0368707', 56, 0, 'state'),
                    (41, 'Washington', '47.7510741', '-120.7401386', 56, 40, 'city'),
                    (42, 'Washington Township', '39.7561387', '-75.0727956', 56, 29, 'city'),
                    (43, 'San Francisco', '37.7749295', '-122.4194155', 56, 23, 'city'),
                    (44, 'ID', '37.09024', '-95.712891', 56, 0, 'state'),
                    (45, 'Idaho City', '43.8285046', '-115.8345537', 56, 44, 'city'),
                    (46, 'OH', '40.4172871', '-82.907123', 56, 0, 'state'),
                    (47, 'Idaho', '44.0682019', '-114.7420408', 56, 46, 'city'),
                    (48, 'MI', '37.09024', '-95.712891', 56, 0, 'state'),
                    (49, 'Wyoming', '43.0759678', '-107.2902839', 56, 48, 'city'),
                    (50, 'TX', '31.9685988', '-99.9018131', 56, 0, 'state'),
                    (51, 'El Paso', '31.7618778', '-106.4850217', 56, 50, 'city'),
                    (52, 'AR', '35.20105', '-91.8318334', 56, 0, 'state'),
                    (53, 'CO', '39.5500507', '-105.7820674', 56, 0, 'state'),
                    (54, 'Colorado City', '32.3881745', '-100.8645576', 56, 53, 'city'),
                    (55, 'NE', '37.09024', '-95.712891', 56, 0, 'state'),
                    (56, 'Nebraska City', '40.6765745', '-95.8593616', 56, 55, 'city'),
                    (57, 'IN', '37.09024', '-95.712891', 56, 0, 'state'),
                    (58, 'Nebraska', '41.4925374', '-99.9018131', 56, 57, 'city'),
                    (59, 'MO', '37.9642529', '-91.8318334', 56, 0, 'state'),
                    (60, 'Kansas City', '39.0997265', '-94.5785667', 56, 59, 'city'),
                    (61, 'Denver', '39.7616189', '-104.9622498', 56, 53, 'city'),
                    (62, 'Oregon City', '45.3573429', '-122.6067583', 56, 37, 'city'),
                    (63, 'Oregon', '43.8041334', '-120.5542012', 56, 46, 'city'),
                    (64, 'Dallas', '32.7766642', '-96.7969879', 56, 50, 'city'),
                    (65, 'Nevada', '38.8026097', '-116.419389', 56, 59, 'city'),
                    (66, 'SD', '43.9695148', '-99.9018131', 56, 0, 'state'),
                    (67, 'South Dakota Park', '43.7266181', '-103.4168231', 56, 66, 'city'),
                    (68, 'NV', '38.8026097', '-116.419389', 56, 0, 'state'),
                    (69, 'Las Vegas', '36.1699412', '-115.1398296', 56, 68, 'city')
    		");
        }
        $url = esc_url("http://authenticate.scriptsbundle.com/adforest/activated.php") . "?purchase_code=" . get_option('_sb_purchase_code');
        $res = wp_remote_get($url);
    }
}

/*On Uninstall P;ugin*/
register_uninstall_hook(__FILE__, 'sb_framework_uninstall');
if (!function_exists('sb_framework_uninstall')) {
    function sb_framework_uninstall()
    {
        delete_option('_sb_purchase_code');
    }
}

/*Keep admin section in english*/
$get_val = get_option('adforest_theme');
if (
    isset($get_val['sb_admin_translate']) &&
    $get_val['sb_admin_translate'] == 0 &&
    (
        $my_theme->get('Name') == 'adforest' ||
        $my_theme->get('Name') == 'adforest child'
    )
) {
    add_filter('locale', 'adforest_admin_in_english_locale');
}

if (!function_exists('adforest_admin_in_english_locale')) {
    function adforest_admin_in_english_locale($locale)
    {
        if (adforest_admin_in_english_should_use_english()) {
            $locale = 'en_US';
        }
        return $locale;
    }
}

if (!function_exists('adforest_admin_in_english_should_use_english')) {
    function adforest_admin_in_english_should_use_english()
    {
        /*frontend AJAX calls are mistakend for admin calls, because the endpoint is wp-admin/admin-ajax.php*/
        return adforest_admin_in_english_is_admin() && !adforest_admin_in_english_is_frontend_ajax();
    }
}

if (!function_exists('adforest_admin_in_english_is_admin')) {
    function adforest_admin_in_english_is_admin()
    {
        return
            is_admin() || adforest_admin_in_english_is_tiny_mce() || adforest_admin_in_english_is_login_page();
    }
}

if (!function_exists('adforest_admin_in_english_is_frontend_ajax')) {
    function adforest_admin_in_english_is_frontend_ajax()
    {
        return defined('DOING_AJAX') && DOING_AJAX && false === strpos(wp_get_referer(), '/wp-admin/');
    }
}

if (!function_exists('adforest_admin_in_english_is_tiny_mce')) {
    function adforest_admin_in_english_is_tiny_mce()
    {
        return false !== strpos($_SERVER['REQUEST_URI'], '/wp-includes/js/tinymce/');
    }
}

if (!function_exists('adforest_admin_in_english_is_login_page')) {
    function adforest_admin_in_english_is_login_page()
    {
        return false !== strpos($_SERVER['REQUEST_URI'], '/wp-login.php');
    }
}

function get_adf_framework_plugin_url()
{
    return untrailingslashit(plugin_dir_path(__FILE__));
}
