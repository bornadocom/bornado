<?php
/**
 * Plugin Name: Bornado Location Picker
 * Description: Reusable country/city picker for Bornado semantic search flows.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-location-picker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BORNADO_LOCATION_PICKER_FILE' ) ) {
	define( 'BORNADO_LOCATION_PICKER_FILE', __FILE__ );
}

if ( ! defined( 'BORNADO_LOCATION_PICKER_DIR' ) ) {
	define( 'BORNADO_LOCATION_PICKER_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BORNADO_LOCATION_PICKER_URL' ) ) {
	define( 'BORNADO_LOCATION_PICKER_URL', plugin_dir_url( __FILE__ ) );
}

$bornado_location_picker_search_core = dirname( __DIR__ ) . '/bornado-search-core/bornado-search-core.php';
if ( ! class_exists( 'Bornado_Search_Core' ) && file_exists( $bornado_location_picker_search_core ) ) {
	require_once $bornado_location_picker_search_core;
}

require_once BORNADO_LOCATION_PICKER_DIR . 'includes/class-bornado-location-picker-service.php';
require_once BORNADO_LOCATION_PICKER_DIR . 'includes/class-bornado-location-picker-renderer.php';
require_once BORNADO_LOCATION_PICKER_DIR . 'includes/class-bornado-location-picker-widget.php';

final class Bornado_Location_Picker_Plugin {
	const VERSION       = '1.0.0';
	const SCRIPT_HANDLE = 'bornado-location-picker';
	const STYLE_HANDLE  = 'bornado-location-picker';
	const AJAX_ACTION   = 'bornado_location_picker_children';
	const NONCE_ACTION  = 'bornado_location_picker';

	/**
	 * Whether the front-end config has already been localized.
	 *
	 * @var bool
	 */
	private static $localized = false;

	/**
	 * Register plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_widget' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 20 );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'handle_children_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'handle_children_ajax' ) );
		add_action( 'created_ad_country', array( __CLASS__, 'flush_term_caches' ) );
		add_action( 'edited_ad_country', array( __CLASS__, 'flush_term_caches' ) );
		add_action( 'delete_ad_country', array( __CLASS__, 'flush_term_caches' ) );
	}

	/**
	 * Register front-end assets.
	 *
	 * @return void
	 */
	public static function register_assets() {
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		$style_path = BORNADO_LOCATION_PICKER_DIR . 'assets/css/bornado-location-picker.css';
		$style_ver  = is_readable( $style_path ) ? (string) filemtime( $style_path ) : self::VERSION;
		wp_register_style(
			self::STYLE_HANDLE,
			BORNADO_LOCATION_PICKER_URL . 'assets/css/bornado-location-picker.css',
			array(),
			$style_ver
		);

		$script_path = BORNADO_LOCATION_PICKER_DIR . 'assets/js/bornado-location-picker.js';
		$script_ver  = is_readable( $script_path ) ? (string) filemtime( $script_path ) : self::VERSION;
		wp_register_script(
			self::SCRIPT_HANDLE,
			BORNADO_LOCATION_PICKER_URL . 'assets/js/bornado-location-picker.js',
			array(),
			$script_ver,
			true
		);
	}

	/**
	 * Ensure picker assets are available on the current request.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		self::register_assets();

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );

		if ( self::$localized ) {
			return;
		}

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'BornadoLocationPicker',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'action'  => self::AJAX_ACTION,
				'strings' => array(
					'loading'       => __( 'در حال بارگذاری شهرها...', 'bornado-location-picker' ),
					'noCities'      => __( 'برای این کشور هنوز شهری ثبت نشده است.', 'bornado-location-picker' ),
					'chooseCountry' => __( 'ابتدا کشور را انتخاب کنید.', 'bornado-location-picker' ),
					'citySearch'    => __( 'جستجو در شهرها', 'bornado-location-picker' ),
				),
			)
		);

		self::$localized = true;
	}

	/**
	 * Register shortcode API.
	 *
	 * @return void
	 */
	public static function register_shortcode() {
		add_shortcode( 'bornado_location_picker', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Register classic widget API.
	 *
	 * @return void
	 */
	public static function register_widget() {
		if ( class_exists( 'WP_Widget' ) && class_exists( 'Bornado_Location_Picker_Widget' ) ) {
			register_widget( 'Bornado_Location_Picker_Widget' );
		}
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		return self::render( is_array( $atts ) ? $atts : array() );
	}

	/**
	 * Render a picker instance.
	 *
	 * @param array<string,mixed> $args Render options.
	 * @return string
	 */
	public static function render( $args = array() ) {
		self::enqueue_assets();
		return Bornado_Location_Picker_Renderer::render( is_array( $args ) ? $args : array() );
	}

	/**
	 * AJAX endpoint returning cities for a country.
	 *
	 * @return void
	 */
	public static function handle_children_ajax() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$country_id = isset( $_POST['country_id'] ) ? absint( wp_unslash( $_POST['country_id'] ) ) : 0;
		if ( $country_id < 1 ) {
			wp_send_json_error(
				array(
					'message' => __( 'شناسه کشور معتبر نیست.', 'bornado-location-picker' ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'items' => Bornado_Location_Picker_Service::get_city_options( $country_id ),
			)
		);
	}

	/**
	 * Reset cached location trees after term writes.
	 *
	 * @return void
	 */
	public static function flush_term_caches() {
		Bornado_Location_Picker_Service::flush_cache();
	}
}

if ( ! function_exists( 'bornado_render_location_picker' ) ) {
	/**
	 * Return picker markup for theme templates.
	 *
	 * @param array<string,mixed> $args Render options.
	 * @return string
	 */
	function bornado_render_location_picker( $args = array() ) {
		return Bornado_Location_Picker_Plugin::render( is_array( $args ) ? $args : array() );
	}
}

if ( ! function_exists( 'bornado_location_picker' ) ) {
	/**
	 * Echo picker markup for theme templates.
	 *
	 * @param array<string,mixed> $args Render options.
	 * @return void
	 */
	function bornado_location_picker( $args = array() ) {
		echo bornado_render_location_picker( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

Bornado_Location_Picker_Plugin::init();
