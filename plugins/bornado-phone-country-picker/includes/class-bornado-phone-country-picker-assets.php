<?php
/**
 * Frontend assets for the phone-country picker.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Phone_Country_Picker_Assets' ) ) {
	return;
}

final class Bornado_Phone_Country_Picker_Assets {
	const STYLE_HANDLE  = 'bornado-phone-country-picker';
	const SCRIPT_HANDLE = 'bornado-phone-country-picker';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 220 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 221 );
	}

	/**
	 * Register frontend assets.
	 *
	 * @return void
	 */
	public static function register_assets() {
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		$style_path = BORNADO_PHONE_COUNTRY_PICKER_DIR . 'assets/css/bornado-phone-country-picker.css';
		$script_path = BORNADO_PHONE_COUNTRY_PICKER_DIR . 'assets/js/bornado-phone-country-picker.js';

		wp_register_style(
			self::STYLE_HANDLE,
			BORNADO_PHONE_COUNTRY_PICKER_URL . 'assets/css/bornado-phone-country-picker.css',
			array(),
			is_readable( $style_path ) ? (string) filemtime( $style_path ) : BORNADO_PHONE_COUNTRY_PICKER_VERSION
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			BORNADO_PHONE_COUNTRY_PICKER_URL . 'assets/js/bornado-phone-country-picker.js',
			self::get_script_dependencies(),
			is_readable( $script_path ) ? (string) filemtime( $script_path ) : BORNADO_PHONE_COUNTRY_PICKER_VERSION,
			true
		);
	}

	/**
	 * Enqueue frontend assets with runtime config.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		$countries = Bornado_Phone_Country_Picker_Service::get_country_options();
		if ( empty( $countries ) ) {
			return;
		}

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );
		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.BornadoPhoneCountryPicker = ' . wp_json_encode( self::build_frontend_config() ) . ';',
			'before'
		);
	}

	/**
	 * Build runtime config for the frontend picker.
	 *
	 * @return array<string,mixed>
	 */
	private static function build_frontend_config() {
		return array(
			'countries'            => Bornado_Phone_Country_Picker_Service::get_country_options(),
			'suggestedCountry'     => Bornado_Market_Context_Service::resolve_suggested_phone_country(),
			'legacyDefaultCountry' => Bornado_Phone_Country_Picker_Service::get_legacy_default_country(),
			'integrations'         => array(
				Bornado_Phone_Country_Picker_Auth_Modal_Integration::get_frontend_config(),
				Bornado_Phone_Country_Picker_Profile_Integration::get_frontend_config(),
			),
			'i18n'                 => array(
				'searchPlaceholder' => __( 'جستجو در کشورها یا کد تماس', 'bornado-phone-country-picker' ),
				'emptySearch'       => __( 'کشوری با این جستجو پیدا نشد.', 'bornado-phone-country-picker' ),
				'suggestedLabel'    => __( 'پیشنهادی', 'bornado-phone-country-picker' ),
				'selectedLabel'     => __( 'کشور انتخاب‌شده', 'bornado-phone-country-picker' ),
				'currentDialCode'   => __( 'پیش‌شماره انتخابی', 'bornado-phone-country-picker' ),
				'placeholderLabel'  => __( 'کد کشور', 'bornado-phone-country-picker' ),
			),
		);
	}

	/**
	 * Detect script dependencies exposed by existing integrations.
	 *
	 * @return array<int,string>
	 */
	private static function get_script_dependencies() {
		$deps = array( 'jquery' );

		foreach ( array( 'bornado-auth-modal', 'bornado-profile-phone-guard' ) as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) ) {
				$deps[] = $handle;
			}
		}

		return array_values( array_unique( $deps ) );
	}
}
