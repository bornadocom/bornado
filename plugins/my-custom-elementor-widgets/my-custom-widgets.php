<?php
/**
 * Plugin Name: My Custom Elementor Widgets
 * Description: نگه‌داری سفارشی‌سازی‌های مرتبط با Style 3 لیست آگهی.
 * Version: 1.3.0
 * Author: Your Name
 * Text Domain: my-custom-widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج در صورت دسترسی مستقیم
}

define( 'MY_CEW_PLUGIN_FILE', __FILE__ );
define( 'MY_CEW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

$my_cew_search_core = dirname( __DIR__ ) . '/bornado-search-core/bornado-search-core.php';
if ( ! class_exists( 'Bornado_Search_Core' ) && file_exists( $my_cew_search_core ) ) {
	require_once $my_cew_search_core;
}

// فقط فایل مرتبط با Style 3
require_once plugin_dir_path( __FILE__ ) . 'includes/adforest-list-style-3.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/adforest-loading-mode-page-scroll.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/adforest-custom-recent-ads-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/adforest-search-by-location-v2.php';

/**
 * Enqueue frontend CSS required by Style 3.
 *
 * @return void
 */
function my_cew_enqueue_style3_assets() {
	if ( is_admin() ) {
		return;
	}
	$css_path = MY_CEW_PLUGIN_DIR . 'assets/css/mcew-bornado-list.css';
	$css_ver  = is_readable( $css_path ) ? (string) filemtime( $css_path ) : '1.0.0';
	wp_enqueue_style(
		'mcew-bornado-list',
		plugins_url( 'assets/css/mcew-bornado-list.css', MY_CEW_PLUGIN_FILE ),
		array(),
		$css_ver
	);

	$location_css_path = MY_CEW_PLUGIN_DIR . 'assets/css/mcew-location-search-v2.css';
	$location_css_ver  = is_readable( $location_css_path ) ? (string) filemtime( $location_css_path ) : '1.0.0';
	wp_enqueue_style(
		'mcew-location-search-v2',
		plugins_url( 'assets/css/mcew-location-search-v2.css', MY_CEW_PLUGIN_FILE ),
		array(),
		$location_css_ver
	);

	$location_js_path = MY_CEW_PLUGIN_DIR . 'assets/js/mcew-location-search-v2.js';
	$location_js_ver  = is_readable( $location_js_path ) ? (string) filemtime( $location_js_path ) : '1.0.0';
	wp_enqueue_script(
		'mcew-location-search-v2',
		plugins_url( 'assets/js/mcew-location-search-v2.js', MY_CEW_PLUGIN_FILE ),
		array( 'jquery', 'bornado-search-core' ),
		$location_js_ver,
		true
	);
	wp_localize_script(
		'mcew-location-search-v2',
		'MCEWLocationSearchV2',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'adforest_get_countries_nonce' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'my_cew_enqueue_style3_assets', 120 );