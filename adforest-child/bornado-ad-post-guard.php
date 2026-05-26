<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_enqueue_ad_post_guard_assets' ) ) {
	/**
	 * Add a child-theme safety layer around the AdForest ad-post form without
	 * touching parent theme files.
	 *
	 * @return void
	 */
	function bornado_enqueue_ad_post_guard_assets() {
		if ( is_admin() ) {
			return;
		}

		$handle    = 'bornado-ad-post-guard';
		$asset_uri = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-ad-post-guard.js';
		$asset_path = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-ad-post-guard.js';

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
			'bornadoAdPostGuard',
			array(
				'storageKey' => sprintf(
					'bornado:ad-post-draft:%d:%d',
					(int) get_current_user_id(),
					(int) get_queried_object_id()
				),
				'phoneCountries' => function_exists( 'bornado_get_phone_country_options' ) ? bornado_get_phone_country_options() : array(),
				'defaultPhoneCountry' => function_exists( 'bornado_get_default_phone_country_option' ) ? bornado_get_default_phone_country_option() : array(),
				'i18n' => array(
					'phoneExample' => __( 'نمونه نهایی', 'adforest-child' ),
					'localPhoneExample' => __( 'نمونه شماره بدون کد کشور', 'adforest-child' ),
					'selectCountry' => __( 'ابتدا کشور را انتخاب کنید تا کد تلفن همان کشور اعمال شود.', 'adforest-child' ),
					'countryApplied' => __( 'کد کشور از کشور انتخابی آگهی گرفته می‌شود.', 'adforest-child' ),
					'countryCodeLabel' => __( 'کد کشور', 'adforest-child' ),
					'invalidPhone' => __( 'شماره واردشده هنوز قابل تبدیل به فرمت بین‌المللی معتبر نیست.', 'adforest-child' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_ad_post_guard_assets', 132 );
