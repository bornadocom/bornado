<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_enqueue_profile_phone_guard_assets' ) ) {
	/**
	 * Add a child-theme phone UX helper to the profile dashboard form.
	 *
	 * @return void
	 */
	function bornado_enqueue_profile_phone_guard_assets() {
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		$handle     = 'bornado-profile-phone-guard';
		$asset_uri  = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-profile-phone-guard.js';
		$asset_path = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-profile-phone-guard.js';

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		wp_enqueue_script(
			$handle,
			$asset_uri,
			array( 'jquery' ),
			(string) filemtime( $asset_path ),
			true
		);

		wp_localize_script(
			$handle,
			'bornadoProfilePhoneGuard',
			array(
				'phoneCountries'      => function_exists( 'bornado_get_phone_country_options' ) ? bornado_get_phone_country_options() : array(),
				'defaultPhoneCountry' => function_exists( 'bornado_get_default_phone_country_option' ) ? bornado_get_default_phone_country_option() : array(),
				'i18n'                => array(
					'countryLabel'  => __( 'Country Code', 'adforest-child' ),
					'phoneExample'  => __( 'نمونه نهایی', 'adforest-child' ),
					'invalidPhone'  => __( 'شماره واردشده هنوز قابل تبدیل به فرمت بین‌المللی معتبر نیست.', 'adforest-child' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_profile_phone_guard_assets', 133 );

if ( ! function_exists( 'bornado_prefilter_profile_phone_number' ) ) {
	/**
	 * Normalize the dashboard profile phone before AdForest validates and saves it.
	 *
	 * @return void
	 */
	function bornado_prefilter_profile_phone_number() {
		if ( empty( $_POST['sb_data'] ) ) {
			return;
		}

		parse_str( wp_unslash( $_POST['sb_data'] ), $params );
		if ( ! is_array( $params ) || empty( $params['sb_user_contact'] ) ) {
			return;
		}

		$dial_code = isset( $params['bornado_phone_dial_code'] ) ? (string) $params['bornado_phone_dial_code'] : '';
		$normalized_phone = function_exists( 'bornado_normalize_phone_with_dial_code' )
			? bornado_normalize_phone_with_dial_code( (string) $params['sb_user_contact'], $dial_code )
			: '';

		if ( '' !== $normalized_phone ) {
			$params['sb_user_contact'] = $normalized_phone;
			$_POST['sb_data']          = wp_slash( http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) );
		}
	}
}
add_action( 'wp_ajax_sb_update_profile', 'bornado_prefilter_profile_phone_number', 0 );
