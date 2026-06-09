<?php
/**
 * Child-theme search filter UX fixes.
 *
 * Allow selected radio-like search filters to be cleared with a second click
 * without editing AdForest parent theme assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_enqueue_search_filter_toggle_fix_assets' ) ) {
	/**
	 * Enqueue the frontend search-filter toggle fix script.
	 *
	 * @return void
	 */
	function bornado_enqueue_search_filter_toggle_fix_assets() {
		if ( is_admin() || ! function_exists( 'bornado_is_ad_search_view' ) || ! bornado_is_ad_search_view() ) {
			return;
		}

		$asset_path = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-search-filter-toggle-fix.js';
		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$dependencies = array( 'jquery' );
		foreach ( array( 'adforest-custom', 'adforest-search-ajax', 'adforest-search-ux' ) as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				$dependencies[] = $handle;
			}
		}

		wp_enqueue_script(
			'bornado-search-filter-toggle-fix',
			trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-search-filter-toggle-fix.js',
			array_values( array_unique( $dependencies ) ),
			(string) filemtime( $asset_path ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_search_filter_toggle_fix_assets', 134 );
