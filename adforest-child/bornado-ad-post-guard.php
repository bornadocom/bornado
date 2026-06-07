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

		$handle       = 'bornado-ad-post-guard';
		$style_handle = 'bornado-ad-post-contact-methods';
		$asset_uri    = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-ad-post-guard.js';
		$asset_path   = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-ad-post-guard.js';
		$style_uri    = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/css/bornado-ad-post-contact-methods.css';
		$style_path   = trailingslashit( get_stylesheet_directory() ) . 'assets/css/bornado-ad-post-contact-methods.css';
		$editing_ad_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				$style_handle,
				$style_uri,
				array(),
				(string) filemtime( $style_path )
			);
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
					'bornado:ad-post-draft:%d:%d:%d',
					(int) get_current_user_id(),
					(int) get_queried_object_id(),
					$editing_ad_id
				),
				'phoneCountries' => function_exists( 'bornado_get_phone_country_options' ) ? bornado_get_phone_country_options() : array(),
				'defaultPhoneCountry' => function_exists( 'bornado_get_default_phone_country_option' ) ? bornado_get_default_phone_country_option() : array(),
				'contactMethods' => function_exists( 'bornado_get_ad_post_contact_methods_context' ) ? bornado_get_ad_post_contact_methods_context( get_current_user_id() ) : array(),
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

if ( ! function_exists( 'bornado_enqueue_ad_post_checkbox_fix_styles' ) ) {
	/**
	 * Fix the terms checkbox hit area/alignment on the frontend ad-post page.
	 *
	 * Parent AdForest renders this checkbox with pretty-checkbox markup, but the
	 * classic ad-post page does not consistently apply the page-local override
	 * CSS that the modern wrapper uses. Keep the repair in the child theme and
	 * scope it to the post-ad form only.
	 *
	 * @return void
	 */
	function bornado_enqueue_ad_post_checkbox_fix_styles() {
		if ( is_admin() || ! function_exists( 'bornado_is_ad_post_page' ) || ! bornado_is_ad_post_page() ) {
			return;
		}

		$deps = function_exists( 'bornado_get_theme_style_handles' )
			? bornado_get_theme_style_handles()
			: array();

		foreach ( array( 'pretty-checkbox', 'adforest-style', 'adforest-main' ) as $candidate ) {
			if ( wp_style_is( $candidate, 'registered' ) || wp_style_is( $candidate, 'enqueued' ) ) {
				$deps[] = $candidate;
			}
		}

		$deps   = array_values( array_unique( $deps ) );
		$handle = 'bornado-ad-post-checkbox-fix';
		$css    = <<<'CSS'
#adforest-ad-post-form .skin-minimal.check-detail {
	margin: 18px 0 6px;
}

#adforest-ad-post-form .skin-minimal.check-detail ul.list {
	list-style: none;
	margin: 0;
	padding: 0;
}

#adforest-ad-post-form .skin-minimal.check-detail ul.list > li {
	margin: 0;
	padding: 0;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty {
	display: inline-flex;
	align-items: flex-start;
	position: relative;
	margin: 0;
	padding: 0;
	line-height: 1.4;
	white-space: normal;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty input#minimal-checkbox-1 {
	position: absolute !important;
	top: 0 !important;
	left: 0 !important;
	width: 18px !important;
	height: 18px !important;
	min-width: 18px !important;
	margin: 0 !important;
	padding: 0 !important;
	opacity: 0 !important;
	transform: none !important;
	cursor: pointer !important;
	z-index: 2 !important;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty .state {
	display: inline-block;
	margin: 0;
	position: relative;
	padding-left: 28px;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty .state::before {
	content: "";
	position: absolute;
	top: 1px;
	left: 0;
	width: 18px;
	height: 18px;
	border: 1.5px solid rgba(15, 23, 42, 0.3);
	border-radius: 4px;
	background: #fff;
	transition: border-color 0.15s ease, background 0.15s ease;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty:hover .state::before,
#adforest-ad-post-form .skin-minimal.check-detail .pretty input#minimal-checkbox-1:focus + .state::before {
	border-color: #ffcc00;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty input#minimal-checkbox-1:checked + .state::before,
#adforest-ad-post-form .skin-minimal.check-detail .pretty input#minimal-checkbox-1:checked ~ .state::before {
	background: #ffcc00;
	border-color: #ffcc00;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty .state::after {
	content: "";
	position: absolute;
	top: 7px;
	left: 5px;
	width: 8px;
	height: 5px;
	border: 2px solid #fff;
	border-top: 0;
	border-right: 0;
	transform: rotate(-45deg);
	opacity: 0;
	pointer-events: none;
	transition: opacity 0.15s ease;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty input#minimal-checkbox-1:checked + .state::after,
#adforest-ad-post-form .skin-minimal.check-detail .pretty input#minimal-checkbox-1:checked ~ .state::after {
	opacity: 1;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty .state label {
	margin: 0;
	min-width: 0;
	padding: 0;
	text-indent: 0;
	line-height: 1.5;
	cursor: pointer;
}

#adforest-ad-post-form .skin-minimal.check-detail .pretty .state label::before,
#adforest-ad-post-form .skin-minimal.check-detail .pretty .state label::after {
	content: none !important;
	display: none !important;
}

body.rtl #adforest-ad-post-form .skin-minimal.check-detail .pretty {
	margin-right: 0;
}

body.rtl #adforest-ad-post-form .skin-minimal.check-detail .pretty input#minimal-checkbox-1 {
	left: auto !important;
	right: 0 !important;
}

body.rtl #adforest-ad-post-form .skin-minimal.check-detail .pretty .state {
	padding-left: 0;
	padding-right: 28px;
}

body.rtl #adforest-ad-post-form .skin-minimal.check-detail .pretty .state::before {
	left: auto;
	right: 0;
}

body.rtl #adforest-ad-post-form .skin-minimal.check-detail .pretty .state::after {
	left: auto;
	right: 5px;
	transform: rotate(-45deg);
	border-top: 0;
	border-right: 0;
}

body.rtl #adforest-ad-post-form .skin-minimal.check-detail .pretty .state label {
	text-align: right;
}
CSS;

		wp_register_style( $handle, false, $deps, '1.0.0' );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $css );
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_ad_post_checkbox_fix_styles', 205 );
