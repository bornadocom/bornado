<?php
/**
 * Public API for shared search behavior.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Search_Core' ) ) {
	return;
}

final class Bornado_Search_Core {
	const VERSION       = '1.0.0';
	const SCRIPT_HANDLE = 'bornado-search-core';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 6 );
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

		$script_path = BORNADO_SEARCH_CORE_DIR . 'assets/js/bornado-search-core.js';
		$script_ver  = is_readable( $script_path ) ? (string) filemtime( $script_path ) : self::VERSION;

		wp_register_script(
			self::SCRIPT_HANDLE,
			BORNADO_SEARCH_CORE_URL . 'assets/js/bornado-search-core.js',
			array(),
			$script_ver,
			false
		);
	}

	/**
	 * Ensure the helper is available before inline search scripts run.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Return contextual action URLs.
	 *
	 * @param array<string,mixed> $args Optional behavior flags.
	 * @return array<string,string>
	 */
	public static function get_search_actions( $args = array() ) {
		return Bornado_Search_Context::get_search_actions( $args );
	}

	/**
	 * Return normalized query context values used by custom search UIs.
	 *
	 * @return array<string,string>
	 */
	public static function get_selected_context() {
		$route_context = function_exists( 'bornado_seo_routing_get_context' ) ? bornado_seo_routing_get_context() : array();
		$route_country = ! empty( $route_context['country_term'] ) && $route_context['country_term'] instanceof WP_Term ? (string) $route_context['country_term']->term_id : '';
		$route_city    = ! empty( $route_context['city_term'] ) && $route_context['city_term'] instanceof WP_Term ? (string) $route_context['city_term']->term_id : '';
		$route_cat     = ! empty( $route_context['deepest_term'] ) && $route_context['deepest_term'] instanceof WP_Term ? (string) $route_context['deepest_term']->term_id : '';

		return array(
			'ad_title' => self::get_query_value( array( 'ad_title', 'title' ) ),
			'country'  => '' !== $route_country ? $route_country : self::get_query_value( array( 'bornado_country', 'country_id', 'ad_country', 'location' ) ),
			'city'     => '' !== $route_city ? $route_city : self::get_query_value( array( 'bornado_city', 'city_id' ) ),
			'category' => '' !== $route_cat ? $route_cat : self::get_query_value( array( 'cat_id', 'ad_cats' ) ),
			'ad_type'  => self::get_query_value( array( 'ad_type', 'type' ) ),
		);
	}

	/**
	 * Normalize an array of query args and remove empty values.
	 *
	 * @param mixed                  $source       Query source.
	 * @param array<int,string>|null $allowed_keys Optional whitelist.
	 * @return array<string,mixed>
	 */
	public static function build_clean_query_args( $source, $allowed_keys = null ) {
		if ( ! is_array( $source ) ) {
			return array();
		}

		$clean = array();
		foreach ( $source as $key => $value ) {
			$key = is_string( $key ) ? trim( $key ) : '';
			if ( '' === $key ) {
				continue;
			}

			if ( is_array( $allowed_keys ) && ! in_array( $key, $allowed_keys, true ) ) {
				continue;
			}

			$normalized = self::normalize_query_value( $value );
			if ( self::is_empty_query_value( $normalized ) ) {
				continue;
			}

			$clean[ $key ] = $normalized;
		}

		return $clean;
	}

	/**
	 * Return the current request query args after removing empty values.
	 *
	 * @param array<int,string>      $excluded_keys Keys to exclude from the result.
	 * @param array<int,string>|null $allowed_keys  Optional whitelist.
	 * @return array<string,mixed>
	 */
	public static function get_current_query_args( $excluded_keys = array(), $allowed_keys = null ) {
		$clean = self::build_clean_query_args( wp_unslash( $_GET ), $allowed_keys );

		if ( empty( $excluded_keys ) ) {
			return $clean;
		}

		foreach ( $excluded_keys as $excluded_key ) {
			if ( is_string( $excluded_key ) && '' !== $excluded_key ) {
				unset( $clean[ $excluded_key ] );
			}
		}

		return $clean;
	}

	/**
	 * Render hidden inputs from a query array after removing empty values.
	 *
	 * @param mixed                  $source        Source query array.
	 * @param array<int,string>      $excluded_keys Keys to exclude.
	 * @param array<int,string>|null $allowed_keys  Optional whitelist.
	 * @return string
	 */
	public static function render_hidden_fields( $source, $excluded_keys = array(), $allowed_keys = null ) {
		$clean = self::build_clean_query_args( $source, $allowed_keys );
		if ( ! empty( $excluded_keys ) ) {
			foreach ( $excluded_keys as $excluded_key ) {
				if ( is_string( $excluded_key ) && '' !== $excluded_key ) {
					unset( $clean[ $excluded_key ] );
				}
			}
		}

		return self::render_hidden_inputs_recursive( $clean );
	}

	/**
	 * Return the first available query value from a list of keys.
	 *
	 * @param array<int,string> $keys Candidate query var names.
	 * @return string
	 */
	private static function get_query_value( $keys ) {
		foreach ( $keys as $key ) {
			if ( ! isset( $_GET[ $key ] ) ) {
				continue;
			}

			$value = wp_unslash( $_GET[ $key ] );
			if ( is_array( $value ) ) {
				continue;
			}

			return sanitize_text_field( $value );
		}

		return '';
	}

	/**
	 * Normalize a query value recursively.
	 *
	 * @param mixed $value Raw query value.
	 * @return mixed
	 */
	private static function normalize_query_value( $value ) {
		if ( is_array( $value ) ) {
			$normalized = array();
			foreach ( $value as $child_key => $child_value ) {
				$child_value = self::normalize_query_value( $child_value );
				if ( self::is_empty_query_value( $child_value ) ) {
					continue;
				}
				$normalized[ $child_key ] = $child_value;
			}

			return $normalized;
		}

		if ( is_bool( $value ) || is_numeric( $value ) ) {
			return $value;
		}

		return trim( sanitize_text_field( (string) $value ) );
	}

	/**
	 * Return whether a normalized query value is empty.
	 *
	 * @param mixed $value Normalized value.
	 * @return bool
	 */
	private static function is_empty_query_value( $value ) {
		if ( is_array( $value ) ) {
			return empty( $value );
		}

		if ( is_bool( $value ) || is_numeric( $value ) ) {
			return false;
		}

		return '' === (string) $value;
	}

	/**
	 * Render hidden inputs recursively for normalized query data.
	 *
	 * @param array<string|int,mixed> $values Query values.
	 * @param string                  $prefix Current field name prefix.
	 * @return string
	 */
	private static function render_hidden_inputs_recursive( $values, $prefix = '' ) {
		if ( ! is_array( $values ) || empty( $values ) ) {
			return '';
		}

		$output = '';
		foreach ( $values as $key => $value ) {
			$field_name = '' === $prefix ? (string) $key : $prefix . '[' . $key . ']';

			if ( is_array( $value ) ) {
				$output .= self::render_hidden_inputs_recursive( $value, $field_name );
				continue;
			}

			$output .= sprintf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $field_name ),
				esc_attr( (string) $value )
			);
		}

		return $output;
	}
}
