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
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_ad_post_guard_assets', 132 );
