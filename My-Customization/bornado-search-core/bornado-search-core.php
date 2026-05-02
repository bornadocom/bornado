<?php
/**
 * Plugin Name: Bornado Search Core
 * Description: Shared AdForest/Bornado search URL and query logic for custom modules.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-search-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BORNADO_SEARCH_CORE_FILE' ) ) {
	define( 'BORNADO_SEARCH_CORE_FILE', __FILE__ );
}

if ( ! defined( 'BORNADO_SEARCH_CORE_DIR' ) ) {
	define( 'BORNADO_SEARCH_CORE_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BORNADO_SEARCH_CORE_URL' ) ) {
	define( 'BORNADO_SEARCH_CORE_URL', plugin_dir_url( __FILE__ ) );
}

require_once BORNADO_SEARCH_CORE_DIR . 'includes/class-bornado-search-context.php';
require_once BORNADO_SEARCH_CORE_DIR . 'includes/class-bornado-search-core.php';

Bornado_Search_Core::init();

if ( ! function_exists( 'bornado_search_get_actions' ) ) {
	/**
	 * Return contextual search action URLs for current request.
	 *
	 * @param array<string,mixed> $args Optional behavior flags.
	 * @return array<string,string>
	 */
	function bornado_search_get_actions( $args = array() ) {
		return Bornado_Search_Core::get_search_actions( $args );
	}
}

if ( ! function_exists( 'bornado_search_get_selected_context' ) ) {
	/**
	 * Return normalized search context from query vars.
	 *
	 * @return array<string,string>
	 */
	function bornado_search_get_selected_context() {
		return Bornado_Search_Core::get_selected_context();
	}
}

if ( ! function_exists( 'bornado_search_build_clean_query_args' ) ) {
	/**
	 * Normalize a source array and drop empty values.
	 *
	 * @param mixed                $source       Array-like query source.
	 * @param array<int,string>|null $allowed_keys Optional whitelist.
	 * @return array<string,mixed>
	 */
	function bornado_search_build_clean_query_args( $source, $allowed_keys = null ) {
		return Bornado_Search_Core::build_clean_query_args( $source, $allowed_keys );
	}
}

if ( ! function_exists( 'bornado_search_get_current_query_args' ) ) {
	/**
	 * Return the current request query args after removing empty values.
	 *
	 * @param array<int,string>      $excluded_keys Keys to exclude from the result.
	 * @param array<int,string>|null $allowed_keys  Optional whitelist.
	 * @return array<string,mixed>
	 */
	function bornado_search_get_current_query_args( $excluded_keys = array(), $allowed_keys = null ) {
		return Bornado_Search_Core::get_current_query_args( $excluded_keys, $allowed_keys );
	}
}

if ( ! function_exists( 'bornado_search_render_hidden_query_fields' ) ) {
	/**
	 * Render hidden inputs from query data after removing empty values.
	 *
	 * @param mixed                  $source        Query source. Uses current request when null.
	 * @param array<int,string>      $excluded_keys Keys to exclude from the output.
	 * @param array<int,string>|null $allowed_keys  Optional whitelist.
	 * @return string
	 */
	function bornado_search_render_hidden_query_fields( $source = null, $excluded_keys = array(), $allowed_keys = null ) {
		if ( null === $source ) {
			$source = bornado_search_get_current_query_args( $excluded_keys, $allowed_keys );
			return Bornado_Search_Core::render_hidden_fields( $source );
		}

		return Bornado_Search_Core::render_hidden_fields( $source, $excluded_keys, $allowed_keys );
	}
}
