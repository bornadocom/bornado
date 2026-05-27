<?php
/**
 * Plugin Name: Bornado Notification Bridge
 * Description: Emits canonical Bornado notification events from WordPress without embedding delivery logic in the CMS.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-notification-bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BORNADO_NOTIFICATION_BRIDGE_FILE')) {
    define('BORNADO_NOTIFICATION_BRIDGE_FILE', __FILE__);
}

if (!defined('BORNADO_NOTIFICATION_BRIDGE_DIR')) {
    define('BORNADO_NOTIFICATION_BRIDGE_DIR', plugin_dir_path(__FILE__));
}

if (file_exists(BORNADO_NOTIFICATION_BRIDGE_DIR . 'config/bornado-notification-bridge-config.php')) {
    require_once BORNADO_NOTIFICATION_BRIDGE_DIR . 'config/bornado-notification-bridge-config.php';
}

require_once BORNADO_NOTIFICATION_BRIDGE_DIR . 'includes/class-bornado-notification-bridge.php';

Bornado_Notification_Bridge::instance();
