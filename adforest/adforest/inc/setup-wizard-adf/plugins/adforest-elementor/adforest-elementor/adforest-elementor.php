<?php
/**
 * Plugin Name: Adforest Elementor
 * Plugin URI: https://themeforest.net/user/scriptsbundle/
 * Description: This plugin is used to add shortcodes by Elementor Pagebuilder.
 * Version: 3.0.14
 * Author: Scripts Bundle
 * Author URI: https://themeforest.net/user/scriptsbundle/
 * License: GPL2
 * Text Domain: adforest-elementor
 */

if (!defined('ABSPATH'))
    exit;

final class Adforest_Elementor
{
    const VERSION = '1.0.0';
    const MINIMUM_ELEMENTOR_VERSION = '2.0.0';
    const MINIMUM_PHP_VERSION = '7.0';

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'i18n'));
        add_action('plugins_loaded', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'register_scripts_styles'));
    }

    public function i18n()
    {
        load_plugin_textdomain('adforest-elementor', FALSE, basename(dirname(__FILE__)) . '/languages/');
    }

    public function init()
    {

        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'admin_notice_missing_main_plugin'));
            return;
        }
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_elementor_version'));
            return;
        }
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_php_version'));
            return;
        }
        require_once('plugin.php');
    }

    public function register_scripts_styles()
    {
        global $adforest_theme, $template;
        $page_template = $template != "" ? basename($template) : "";
        wp_enqueue_script('adfelementor-jquery', plugin_dir_url(__FILE__) . 'assets/jquery-3.7.1.min.js', array(), false, true);
        wp_enqueue_script('adforest-elementor-js', plugin_dir_url(__FILE__) . 'assets/widgets.js', array('jquery'), self::VERSION, true);
        if ($page_template !== 'page-theme-dashboard.php') {
            wp_enqueue_script('adfelementor-bootstrap', plugin_dir_url(__FILE__) . 'assets/bootstrap.min.js', array(), false, true);
        }
        wp_enqueue_style('adforest-elementor-css', plugin_dir_url(__FILE__) . 'assets/widgets.css', array(), self::VERSION);
        wp_register_style('adforest-real-estate-hero-bg', plugin_dir_url(__FILE__) . 'assets/css/real-estate-hero-bg.css', array(), self::VERSION);
        wp_enqueue_style('jquery-tagsinput', plugin_dir_url(__FILE__) . 'assets/jquery.tagsinput.min.css', array(), self::VERSION);
        wp_enqueue_style('jquery-te', plugin_dir_url(__FILE__) . 'assets/jquery-te.css', array(), self::VERSION);
        wp_register_style('adforest-dropzone-css', plugin_dir_url(__FILE__) . 'assets/dropzone.css', array(), self::VERSION);
        wp_enqueue_style('adforest-dt', plugin_dir_url(__FILE__) . 'assets/datepicker.min.css', array(), self::VERSION);

        $sb_register_with_phone = isset($adforest_theme['sb_register_with_phone']) ? $adforest_theme['sb_register_with_phone'] : false;
        if ($sb_register_with_phone) {
            wp_enqueue_script('firebase-app', "https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js", false, false, true);
            wp_enqueue_script('firebase-analytics', "https://www.gstatic.com/firebasejs/8.3.2/firebase-analytics.js", false, false, true);
            wp_enqueue_script('firebase-auth', "https://www.gstatic.com/firebasejs/8.3.2/firebase-auth.js", false, false, true);
            wp_enqueue_script('adforest-elementor-parsley', plugin_dir_url(__FILE__) . 'assets/parsley.min.js', array(), false, true);
            wp_enqueue_script('firebase-custom', plugin_dir_url(__FILE__) . 'assets/firebase-custom.js', array(), false, true);
        }

        $mapType = adforest_mapType();
        if ($mapType == 'google_map') {
            wp_enqueue_script('google-map-callback');
        }

        wp_localize_script(
            'adforest-elementor-js',
            'adforest_custom_data',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'site_url' => site_url(),
            ]
        );
    }

    public function admin_notice_missing_main_plugin()
    {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'adforest-elementor'),
            '<strong>' . esc_html__('Adforest Elementor Widgets ', 'adforest-elementor') . '</strong>',
            '<strong>' . esc_html__('Elementor ', 'adforest-elementor') . '</strong>'
        );
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_elementor_version()
    {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'adforest-elementor'),
            '<strong>' . esc_html__('Adforest Elementor Widgets ', 'adforest-elementor') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'adforest-elementor') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_php_version()
    {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'adforest-elementor'),
            '<strong>' . esc_html__('Elementor ', 'adforest-elementor') . '</strong>',
            '<strong>' . esc_html__('PHP', 'adforest-elementor') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

new Adforest_Elementor;