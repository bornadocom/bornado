<?php
/**
 * Plugin Name: Sb Directory Plugin
 * Version: 2.0.14
 * Description: this Adforest plugin , allow you to create events and directory options.
 * Author: Scripts Bundle
 * Author URI: https://themeforest.net/user/scriptsbundle/
 * License: GPL2
 * Text Domain: sb_pro
 */

define('SB_DIR_PATH', plugin_dir_path(__FILE__));
define('SB_DIR_URL', plugin_dir_url(__FILE__));
define('ELEMENTOR_EVENT', __FILE__);

if (!class_exists('SbPro')) {

    class SbPro
    {
        /**
         * Constructor
         */
        public function __construct()
        {
            $this->setup_actions();
            $this->require_plugin_files();
            add_action('init', array($this, 'sb_pro_plugin_textdomain'), 0);
        }

        public function plugin_path()
        {
            return untrailingslashit(plugin_dir_path(__FILE__));
        }

        public function plugin_url()
        {
            return untrailingslashit(plugins_url('/', __FILE__));
        }

        public function sb_pro_plugin_textdomain()
        {
            $locale = apply_filters('plugin_locale', determine_locale(), 'sb_pro');

            // Load from the global WP_LANG_DIR if available (optional)
            $global_mo = WP_LANG_DIR . '/plugins/sb_pro-' . $locale . '.mo';
            if (file_exists($global_mo)) {
                load_textdomain('sb_pro', $global_mo);
            }

            
            // Load from local /languages folder (inside plugin)
            load_plugin_textdomain('sb_pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        /**
         * Setting up Hooks
         */
        public function setup_actions()
        {
            //Main plugin hooks
            register_activation_hook(SB_DIR_PATH, array('SbPro', 'activate_sb_pro'));
            register_deactivation_hook(SB_DIR_PATH, array('SbPro', 'deactivate_sb_pro'));
            add_action('wp_enqueue_scripts', array($this, 'sb_enqueue_scripts'));
        }

        public function sb_enqueue_scripts()
        {
            global $template;
            $page_template = basename($template);
            wp_enqueue_style('sb-custom-style', plugin_dir_url(__FILE__) . 'assets/css/sb_custom.css');
            wp_enqueue_style('sb-custom-responsive', plugin_dir_url(__FILE__) . 'assets/css/responsive.css');
            wp_enqueue_style('adforest-dt', plugin_dir_url(__FILE__) . 'assets/css/datepicker.min.css');

            if (is_singular('events')) {
                wp_enqueue_script('jquery-ui-all');
            }
        }

        /**
         * Activate callback
         */
        public static function activate_sb_pro()
        {
            //Activation code in here
        }

        /**
         * Deactivate callback
         */
        public static function deactivate_sb_pro()
        {
            //Deactivation code in here
        }

        private function require_plugin_files()
        {
            //Files to require
            require_once SB_DIR_PATH . '/inc/helper.php';
            require_once SB_DIR_PATH . '/inc/ajax_actions.php';
            require_once SB_DIR_PATH . '/inc/sb_actions.php';
            require_once SB_DIR_PATH . '/inc/common-functions.php';
            require_once SB_DIR_PATH . '/inc/template-loader.php';
            require SB_DIR_PATH . '/inc/elementor/elementor-main.php';
        }
    }

    // instantiate the plugin class
    $wp_plugin_template = new SbPro();
}

function example_update_default_image_size($old_theme_name, $old_theme = false)
{

    $adforest_theme = wp_get_theme();
    if ($adforest_theme->get('Name') != 'adforest' && $adforest_theme->get('Name') != 'adforest child') {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

add_action('init', 'example_update_default_image_size', 10, 2);

/* For Custom Fields in Events */
if (class_exists('ACF')) {
    require SB_DIR_PATH . 'custom-fields/ad_custom_fields.php';
}
