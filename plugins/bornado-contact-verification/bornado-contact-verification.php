<?php
/**
 * Plugin Name: Bornado Contact Verification
 * Description: Independent email and WhatsApp verification flows for the Bornado profile page.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-contact-verification
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BORNADO_CONTACT_VERIFICATION_FILE')) {
    define('BORNADO_CONTACT_VERIFICATION_FILE', __FILE__);
}

if (!defined('BORNADO_CONTACT_VERIFICATION_DIR')) {
    define('BORNADO_CONTACT_VERIFICATION_DIR', plugin_dir_path(__FILE__));
}

require_once BORNADO_CONTACT_VERIFICATION_DIR . 'includes/class-bornado-contact-verification.php';

Bornado_Contact_Verification::instance();

if (!function_exists('bornado_contact_verification_get_email_status')) {
    /**
     * @param int $user_id
     * @return array<string,mixed>
     */
    function bornado_contact_verification_get_email_status($user_id = 0)
    {
        return Bornado_Contact_Verification::instance()->get_email_status_data((int) $user_id);
    }
}

if (!function_exists('bornado_contact_verification_get_whatsapp_status')) {
    /**
     * @param int $user_id
     * @return array<string,mixed>
     */
    function bornado_contact_verification_get_whatsapp_status($user_id = 0)
    {
        return Bornado_Contact_Verification::instance()->get_whatsapp_status_data((int) $user_id);
    }
}

if (!function_exists('bornado_contact_verification_get_notice')) {
    /**
     * @return array<string,string>
     */
    function bornado_contact_verification_get_notice()
    {
        return Bornado_Contact_Verification::instance()->get_notice_from_request();
    }
}
