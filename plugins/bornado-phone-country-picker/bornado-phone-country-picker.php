<?php
/**
 * Plugin Name: Bornado Phone Country Picker
 * Description: Independent phone-country picker UX for Bornado forms.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-phone-country-picker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BORNADO_PHONE_COUNTRY_PICKER_VERSION', '1.0.0' );
define( 'BORNADO_PHONE_COUNTRY_PICKER_FILE', __FILE__ );
define( 'BORNADO_PHONE_COUNTRY_PICKER_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'BORNADO_PHONE_COUNTRY_PICKER_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

require_once BORNADO_PHONE_COUNTRY_PICKER_DIR . 'includes/class-bornado-phone-country-picker-service.php';
require_once BORNADO_PHONE_COUNTRY_PICKER_DIR . 'includes/class-bornado-market-context-service.php';
require_once BORNADO_PHONE_COUNTRY_PICKER_DIR . 'includes/integrations/class-bornado-phone-country-picker-auth-modal-integration.php';
require_once BORNADO_PHONE_COUNTRY_PICKER_DIR . 'includes/integrations/class-bornado-phone-country-picker-profile-integration.php';
require_once BORNADO_PHONE_COUNTRY_PICKER_DIR . 'includes/class-bornado-phone-country-picker-assets.php';

if ( ! class_exists( 'Bornado_Phone_Country_Picker' ) ) {
	final class Bornado_Phone_Country_Picker {
		/**
		 * Register hooks.
		 *
		 * @return void
		 */
		public static function init() {
			Bornado_Phone_Country_Picker_Assets::init();
		}
	}
}

Bornado_Phone_Country_Picker::init();
