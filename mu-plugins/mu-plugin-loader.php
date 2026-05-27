<?php
/**
 * Plugin Name: Bornado Search Core Loader
 * Description: MU loader for Bornado Search Core and Bornado SEO Routing plugins.
 * Version: 1.0.0
 * Author: Bornado
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_require_first_existing_file' ) ) {
	/**
	 * Require the first file that exists in a candidate list.
	 *
	 * @param array<int,string> $paths Candidate file paths.
	 * @return bool
	 */
	function bornado_require_first_existing_file( $paths ) {
		foreach ( (array) $paths as $path ) {
			if ( is_string( $path ) && '' !== $path && file_exists( $path ) ) {
				require_once $path;
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'bornado_get_local_customization_root' ) ) {
	/**
	 * Guess the project customization root for local repo-based development.
	 *
	 * @return string
	 */
	function bornado_get_local_customization_root() {
		$base_dir = dirname( __DIR__ );
		if ( is_dir( $base_dir ) ) {
			return $base_dir;
		}

		return '';
	}
}

if ( ! function_exists( 'bornado_bootstrap_empty_ad_price_module' ) ) {
	/**
	 * Load child-theme price helpers before AdForest Elementor defines get_ad_post_details().
	 *
	 * Search cards read price_html from get_ad_post_details(), which the Elementor
	 * plugin registers on plugins_loaded priority 10. Child theme functions.php loads
	 * later on after_setup_theme, so the override must bootstrap earlier.
	 */
	function bornado_bootstrap_empty_ad_price_module() {
		if ( function_exists( 'bornado_get_negotiable_price_label' ) ) {
			return;
		}

		$stylesheet = get_option( 'stylesheet' );
		$module_paths = array();
		if ( is_string( $stylesheet ) && '' !== $stylesheet ) {
			$module_paths[] = WP_CONTENT_DIR . '/themes/' . $stylesheet . '/bornado-empty-ad-price.php';
		}

		$local_customization_root = bornado_get_local_customization_root();
		if ( '' !== $local_customization_root ) {
			$module_paths[] = dirname( $local_customization_root ) . '/adforest-child/bornado-empty-ad-price.php';
		}

		bornado_require_first_existing_file( array_values( array_unique( $module_paths ) ) );
	}

	add_action( 'plugins_loaded', 'bornado_bootstrap_empty_ad_price_module', 5 );
}

$local_customization_root = bornado_get_local_customization_root();
$plugin_roots = array(
	WP_PLUGIN_DIR,
	defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : '',
	$local_customization_root,
);

$bornado_plugin_map = array(
	'bornado-search-core' => 'bornado-search-core.php',
	'bornado-routing' => 'bornado-routing.php',
	'bornado-ad-permalinks' => 'bornado-ad-permalinks.php',
	'bornado-auth-modal' => 'bornado-auth-modal.php',
	'bornado-notification-bridge' => 'bornado-notification-bridge.php',
);

foreach ( $bornado_plugin_map as $plugin_dir => $plugin_file ) {
	$candidate_paths = array();
	foreach ( $plugin_roots as $root ) {
		if ( ! is_string( $root ) || '' === $root ) {
			continue;
		}

		$candidate_paths[] = rtrim( $root, '/\\' ) . '/' . $plugin_dir . '/' . $plugin_file;
	}

	bornado_require_first_existing_file( array_values( array_unique( $candidate_paths ) ) );
}
