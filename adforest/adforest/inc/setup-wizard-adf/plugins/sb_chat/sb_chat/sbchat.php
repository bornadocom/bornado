<?php
/**
 * Plugin Name: SB Chat
 * Plugin URI: https://themeforest.net/user/scriptsbundle/
 * Description: SB Chat is a WordPress plugin based on Ajax based Chat system where buyers and seller can communicate with each other.
 * Version: 2.0.13
 * Text Domain: sb_chat
 * Author: Scripts Bundle
 * Author URI: https://themeforest.net/user/scriptsbundle/
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    die('-1');
}

global $wpdb;
define( 'SBCHAT_ASSETS_DIR_URL', plugin_dir_url( __FILE__ ) . 'assets/' );
define( 'SBCHAT_ADMIN_DIR_URL', plugin_dir_url( __FILE__ ) . 'admin/' );
define( 'SBCHAT_INC_DIR_URL', plugin_dir_url( __FILE__ ) . 'inc/' );
define( 'SBCHAT_LIB_DIR_URL', plugin_dir_url( __FILE__ ) . 'lib/' );
define( 'SBCHAT_ASSETS_DIR_PATH', plugin_dir_path( __FILE__ ) . 'assets' );
define( 'SBCHAT_ADMIN_DIR_PATH', plugin_dir_path( __FILE__ ) . 'admin' );
define( 'SBCHAT_INC_DIR_PATH', plugin_dir_path( __FILE__ ) . 'inc' );
define( 'SBCHAT_LIB_DIR_PATH', plugin_dir_path( __FILE__ ) . 'lib' );
define( 'SBCHAT_TABLE_CONVERSATIONS', $wpdb->prefix . 'sb_chat_conversation' );
define( 'SBCHAT_TABLE_MESSAGES', $wpdb->prefix . 'sb_chat_messages' );
define( 'SBCHAT_UPLOAD_DIR_URL', plugin_dir_url( __FILE__ ) . 'uploads' );
define( 'SBCHAT_UPLOAD_DIR_PATH', plugin_dir_path( __FILE__ ) . 'uploads' );

if (!class_exists('SB_Chat')) {

    class SB_Chat {

        public $plugin_url;
        public $sb_plugin_options;
        public $plugin_dir;
        private static $instance = null;

        public static function get_instance() {
            if (!self::$instance)
                self::$instance = new self;
            return self::$instance;
        }

        public function __construct() {

            global $sb_plugin_options;
            $sb_plugin_options = get_option('sb_plugin_options');
            $this->sb_chat_define_table();
            $this->sb_chat_files_inclusion();
            $this->sb_chat_define_constants();
            sb_chat_db_tables::sb_chat_create_db_tables();
            $this->db_activation_hook();

            add_action('wp_enqueue_scripts', array($this, 'SB_Chat_plugin_scripts'));
            add_action( 'admin_enqueue_scripts', array( $this, 'register_plugin_admin_scripts_styles' ) );
            add_action( 'plugins_loaded', array( $this, 'sb_chat_load_textdomain' ) );

        }

        public function  sb_chat_load_textdomain() {
            load_plugin_textdomain( 'sb_chat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        public function SB_Chat_plugin_scripts() {

            global $sb_plugin_options;
            wp_enqueue_style('sb-chat-style', $this->plugin_url . 'assets/css/sb-style.css');
            wp_enqueue_style( 'sb-chat-style-rtl', $this->plugin_url . 'assets/css/sb-style-rtl.css' );
            wp_register_script('sb-chat-admin-script', $this->plugin_url . 'assets/js/admin-custom.js', array('jquery'), false, true);
            wp_enqueue_script('sb-chat-admin-script');
            $sb_notifications_time = isset($sb_plugin_options['sb_notifications_time']) ? $sb_plugin_options['sb_notifications_time'] : 1;

            if($sb_notifications_time != ""){
                $sb_notifications_time =  (int)$sb_notifications_time * 1000;
            }

            if($sb_notifications_time < 10000 && $sb_notifications_time != '')
            {
                $sb_notifications_time = 10000;
            }
            wp_localize_script(
                'sb-chat-admin-script',
                'localize_vars',
                array(
                    'sbAjaxurl' => admin_url('admin-ajax.php'),
                    'site_url' => get_bloginfo('url'),
                    'sb_notification' => isset($sb_plugin_options['sb_notifications']) ? $sb_plugin_options['sb_notifications'] : '',
                    'notification_time' => $sb_notifications_time,
                    'image_dir' => $this->plugin_url . 'assets/images/',
                    'nonces' => array(
                        'reload_incoming_messages' => wp_create_nonce('sbchat_reload_incoming_messages_nonce'),
                        'load_conversations_list'  => wp_create_nonce('sbchat_load_conversations_list_nonce'),
                    ),
                )
            );

            // Provide translated labels for frontend usage
            wp_localize_script( 'sb-chat-admin-script', 'sbchatLabels', array(
                'related_post_label' => __( 'Related post', 'sb_chat' ),
            ) );

            // Inline fix for legacy messages missing data-label
            $inline_set_label = 'document.addEventListener("DOMContentLoaded",function(){try{var nodes=document.querySelectorAll(".message-post-card:not([data-label])");if(window.sbchatLabels&&sbchatLabels.related_post_label){nodes.forEach(function(n){n.setAttribute("data-label",sbchatLabels.related_post_label);});}}catch(e){}});';
            wp_add_inline_script( 'sb-chat-admin-script', $inline_set_label, 'after' );

            // Add dropzonejs support
            wp_register_style( 'dropzone-style', $this->plugin_url . 'lib/dropzone/dropzone.min.css' );
            wp_register_style( 'custom-dropzone-style', $this->plugin_url . 'assets/css/custom-dropzone.css' );
            wp_register_script( 'dropzone-script', $this->plugin_url . 'lib/dropzone/dropzone.min.js', array( 'jquery' ), false, true );
            wp_register_script( 'fslightbox-script', $this->plugin_url . 'assets/js/fslightbox.js', array( 'jquery' ), false, true );
//            wp_enqueue_style( 'dropzone-style' );
//            wp_enqueue_style( 'custom-dropzone-style' );
//            wp_enqueue_script( 'dropzone-scri pt' );
            wp_enqueue_script( 'fslightbox-script' );

        }

        public function register_plugin_admin_scripts_styles( $hook ) {

            wp_enqueue_script( 'sbchat', $this->plugin_url . 'assets/js/sbchat.js', array( 'jquery' ), false, true );
            // Localize and inline for admin context as well
            wp_localize_script( 'sbchat', 'sbchatLabels', array(
                'related_post_label' => __( 'Related post', 'sb_chat' ),
            ) );
            wp_add_inline_script( 'sbchat', 'document.addEventListener("DOMContentLoaded",function(){try{var nodes=document.querySelectorAll(".message-post-card:not([data-label])");if(window.sbchatLabels&&sbchatLabels.related_post_label){nodes.forEach(function(n){n.setAttribute("data-label",sbchatLabels.related_post_label);});}}catch(e){}});', 'after' );

            if ( $hook !== 'sbchat_page_sbchat_conversations' )
                return false;

            if ( has_filter( 'admin_footer_text' ) )
                add_filter( 'admin_footer_text', '__return_empty_string', 11 );

            if ( has_filter( 'update_footer' ) )
                remove_filter( 'update_footer', 'core_update_footer', 10 );

            wp_enqueue_style( 'sbchat', $this->plugin_url . 'assets/css/sb-style.css' );
            wp_enqueue_style( 'sbchat-rtl', $this->plugin_url . 'assets/css/sb-style-rtl.css' );
            wp_enqueue_style( 'bootstrap', $this->plugin_url . 'assets/css/bootstrap.min.css' );
            wp_enqueue_script( 'sbchat', $this->plugin_url . 'assets/js/sbchat.js', array( 'jquery' ), false, true );
            wp_enqueue_script( 'fslightbox', $this->plugin_url . 'assets/js/fslightbox.js', array( 'jquery' ), false, true );
            wp_enqueue_style( 'select2', $this->plugin_url . 'lib/select2/select2.min.css' );
            wp_enqueue_script( 'select2', $this->plugin_url . 'lib/select2/select2.min.js', array( 'jquery' ), false, true );

            $nonce = wp_create_nonce( 'my-ajax-nonce' );
            wp_localize_script( 'sbchat', 'sbchat', array( 'xhr' => admin_url( 'admin-ajax.php' ) , 'ajax_nonce' => $nonce ) );
        }

        public function sb_chat_define_constants() {
            global $wpdb;
            global $sb_chat_tblname_chat_message;
            global $sb_chat_conversation_tbl;
            $sb_chat_tblname_chat_message = $wpdb->prefix . "sb_chat_message";
            $sb_chat_conversation_tbl = $wpdb->prefix . "sb_chat_conversation";
            $this->plugin_url           = plugin_dir_url(__FILE__);
            $this->plugin_dir           = plugin_dir_path(__FILE__);
        }
        public function sb_chat_files_inclusion() {
            global $sb_plugin_options;

            $sb_plugin_options = get_option('sb_plugin_options');
            require_once 'includes/sb-chat-temp-class.php';
            require_once 'includes/utilities.php';
            require_once 'includes/settings.php';
            require_once 'includes/sb-chat-db.php';
            require_once 'models/conversations.php';
            require_once 'models/messages.php';
            require_once 'models/users.php';
            require_once 'models/crons.php';
            require_once 'helpers/date_time.php';
            require_once 'api/api_functions.php';
            require_once 'api/chat-api.php';
            require_once 'api/sb_chat_basic_auth.php';
        }
        public function sb_chat_define_table(){
            global $sb_plugin_options, $wpdb,  $sb_chat_message_tbl,$sb_chat_conversation_tbl;
            $sb_chat_message_tbl = $wpdb->prefix . "sb_chat_messages";
            $sb_chat_conversation_tbl = $wpdb->prefix . "sb_chat_conversation";
            $this->sb_plugin_options    = $sb_plugin_options;
        }
        public function db_activation_hook() {

            register_activation_hook(__FILE__, array( 'sb_chat_db_tables' ,'sb_chat_create_db_tables' ));
        }

        public static function get_plugin_options( $option_key = '' ) {

            $plugin_options  = array();
            if ( get_option( 'sb_plugin_options' ) !== false )
                $plugin_options = get_option( 'sb_plugin_options' );

            if ( is_array( $plugin_options ) && count( $plugin_options ) > 0 ) {

                if ( ! empty( $option_key ) && array_key_exists( $option_key, $plugin_options ) )
                    return $plugin_options[$option_key];

                return $plugin_options;
            }
        }

        public static function get_server_base_path() {

            $server_base_path = explode( basename( __FILE__, '.php' ), __FILE__ );
            $server_base_path = $server_base_path[0];

            return $server_base_path;
        }

        public static function get_plugin_installation_status() {

            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $sbchat_plugin_uri = 'sb-chat/sbchat.php';

            $active_wp_plugins = get_option( 'active_plugins' );
            $all_wp_plugins = array_keys( get_plugins() );

            $plugin_installation_status = null;
            if ( ( is_array( $all_wp_plugins ) && count( $all_wp_plugins ) == 0 ) || empty( $all_wp_plugins ) ) {}
            $plugin_installation_status = array(
                'status' => false,
                'message' => __( 'No plugins found for the current installation.', 'sb_chat' )
            );

            if ( $active_wp_plugins === false )
                $plugin_installation_status = array(
                    'status' => false,
                    'message' => __( 'No active plugins found for the current installation.', 'sb_chat' )
                );

            $sbchat_plugin_installed = ( in_array( $sbchat_plugin_uri, $active_wp_plugins, true ) && in_array( $sbchat_plugin_uri, $all_wp_plugins, true ) ) ? true : false;

            if ( $sbchat_plugin_installed )
                $plugin_installation_status = array(
                    'status' => true,
                    'message' => __( 'SBChat plugin is installed and active.', 'sb_chat' )
                );

            if ( $sbchat_plugin_installed === false )
                $plugin_installation_status = array(
                    'status' => false,
                    'message' => __( 'SBChat plugin is not installed or inactive on the current installation. Kindly, install or activate it to procees further.', 'sb_chat' )
                );

            return $plugin_installation_status;
        }
    }
}
SB_Chat::get_instance();


function disallow_blocked_users_login( $user_login, $user ) {
    $is_blocked = get_user_meta( $user->ID, 'sb_is_user_blocked', true );
    if ( $is_blocked == 'true' || $is_blocked == '1' ) {
        // The user is blocked, so prevent them from logging in
        wp_logout(); // Log the user out to prevent them from accessing the site
        echo '0|' . __("Your account has been blocked by  admin. Please contact site admin for assistance.", 'sb_chat');
    }
}
//add_action( 'wp_login', 'disallow_blocked_users_login', 10, 2 );

function add_custom_user_profile_fields($user) {
    ?>
    <h3><?php _e('Block User', 'sb_is_user_blocked'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="sb_is_user_blocked"><?php _e('Is User Blocked? This will block the user to send message', 'sb_is_user_blocked'); ?></label></th>
            <td>
                <select name="sb_is_user_blocked" id="sb_is_user_blocked">
                    <option value="0" <?php selected(get_user_meta($user->ID, 'sb_is_user_blocked', true), 0); ?>><?php _e('No', 'sb_is_user_blocked'); ?></option>
                    <option value="1" <?php selected(get_user_meta($user->ID, 'sb_is_user_blocked', true), 1); ?>><?php _e('Yes', 'sb_is_user_blocked'); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_custom_user_profile_fields');
add_action('edit_user_profile', 'add_custom_user_profile_fields');

function save_custom_user_profile_fields($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'sb_is_user_blocked', $_POST['sb_is_user_blocked']);
    }
}
add_action('personal_options_update', 'save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'save_custom_user_profile_fields');

add_action( 'wp_footer', function() {
    echo   '<div id="sbchatModal" class="sbchat-modal">

 </div>';
});
