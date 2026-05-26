<?php
/**
 * Shared request-context helpers for search modules.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Search_Context' ) ) {
	return;
}

final class Bornado_Search_Context {
	/**
	 * Resolve the contextual AdForest search page URL.
	 *
	 * @param string $widget_action Optional AdForest widget action context.
	 * @return string
	 */
	public static function get_search_page_url( $widget_action = '' ) {
		global $adforest_theme;

		$search_page_id = isset( $adforest_theme['sb_search_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_search_page'] ) : 0;
		$url            = ! empty( $search_page_id ) ? get_the_permalink( $search_page_id ) : '';
		if ( empty( $url ) ) {
			return home_url( '/' );
		}

		return apply_filters( 'adforest_category_widget_form_action', $url, $widget_action );
	}

	/**
	 * Build all contextual actions for the current request.
	 *
	 * @param array<string,mixed> $args Optional behavior flags.
	 * @return array<string,string>
	 */
	public static function get_search_actions( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'widget_action' => '',
			)
		);

		$default_action = self::get_search_page_url( (string) $args['widget_action'] );
		if ( ! self::supports_contextual_filter_actions( $default_action ) ) {
			return array(
				'default_action'        => $default_action,
				'all_countries_action'  => $default_action,
				'all_cities_action'     => $default_action,
				'all_categories_action' => $default_action,
				'all_filters_action'    => $default_action,
			);
		}

		$segments       = self::get_request_segments();
		if ( ! empty( $segments ) ) {
			$segments = self::strip_search_page_prefix( $segments, $default_action );
		}

		return array(
			'default_action'        => $default_action,
			'all_countries_action'  => self::build_url_from_segments( self::strip_leading_country( $segments ) ),
			'all_cities_action'     => self::build_url_from_segments( self::strip_leading_city( $segments ) ),
			'all_categories_action' => self::build_url_from_segments( self::strip_leading_category( $segments ) ),
			'all_filters_action'    => self::build_url_from_segments( self::strip_all_filters( $segments ) ),
		);
	}

	/**
	 * Return the current request path split into segments.
	 *
	 * @return array<int,string>
	 */
	public static function get_request_segments() {
		global $wp;

		if ( ! isset( $wp ) || ! isset( $wp->request ) ) {
			return array();
		}

		$request_path = trim( (string) $wp->request, '/' );
		if ( '' === $request_path ) {
			return array();
		}

		return array_values( array_filter( explode( '/', $request_path ) ) );
	}

	/**
	 * Strip the search page path prefix from request segments.
	 *
	 * @param array<int,string> $segments   Request path segments.
	 * @param string            $search_url Search page URL.
	 * @return array<int,string>
	 */
	public static function strip_search_page_prefix( $segments, $search_url ) {
		$search_page_segments = self::get_relative_path_segments( $search_url );
		if ( empty( $search_page_segments ) ) {
			return $segments;
		}

		if ( self::segments_start_with( $segments, $search_page_segments ) ) {
			return array_slice( $segments, count( $search_page_segments ) );
		}

		return $segments;
	}

	/**
	 * Convert a URL to path segments relative to home_url().
	 *
	 * @param string $url URL to normalize.
	 * @return array<int,string>
	 */
	public static function get_relative_path_segments( $url ) {
		$path      = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

		if ( '' === $path ) {
			return array();
		}

		$segments = array_values( array_filter( explode( '/', $path ) ) );
		if ( '' === $home_path ) {
			return $segments;
		}

		$home_segments = array_values( array_filter( explode( '/', $home_path ) ) );
		if ( empty( $home_segments ) ) {
			return $segments;
		}

		if ( self::segments_start_with( $segments, $home_segments ) ) {
			return array_slice( $segments, count( $home_segments ) );
		}

		return $segments;
	}

	/**
	 * Build a URL from normalized path segments.
	 *
	 * @param array<int,string> $segments Path segments.
	 * @return string
	 */
	public static function build_url_from_segments( $segments ) {
		if ( empty( $segments ) ) {
			return home_url( '/' );
		}

		return home_url( user_trailingslashit( implode( '/', $segments ) ) );
	}

	/**
	 * Limit contextual "remove filter" actions to real search/archive routes.
	 *
	 * On single ads or unrelated pages, deriving actions from the current path
	 * would point back to that page and make header searches appear as refreshes.
	 *
	 * @param string $default_action Search page URL.
	 * @return bool
	 */
	private static function supports_contextual_filter_actions( $default_action ) {
		$route_context = function_exists( 'bornado_seo_routing_get_context' ) ? bornado_seo_routing_get_context() : array();
		if ( ! empty( $route_context['is_seo_route'] ) && ! empty( $route_context['is_valid'] ) ) {
			return true;
		}

		if ( is_tax( 'ad_country' ) || is_tax( 'ad_cats' ) ) {
			return true;
		}

		$request_segments     = self::get_request_segments();
		$search_page_segments = self::get_relative_path_segments( $default_action );
		if ( empty( $request_segments ) || empty( $search_page_segments ) ) {
			return false;
		}

		return self::segments_start_with( $request_segments, $search_page_segments );
	}

	/**
	 * Remove the leading country segment when present.
	 *
	 * @param array<int,string> $segments Path segments.
	 * @return array<int,string>
	 */
	public static function strip_leading_country( $segments ) {
		if ( empty( $segments ) ) {
			return array();
		}

		$first_segment = sanitize_title( $segments[0] );
		$country_term  = self::resolve_country_term_by_slug( $first_segment );
		if ( $country_term instanceof WP_Term ) {
			array_shift( $segments );
		}

		return array_values( $segments );
	}

	/**
	 * Remove the leading city segment when present while keeping the country base.
	 *
	 * @param array<int,string> $segments Path segments.
	 * @return array<int,string>
	 */
	public static function strip_leading_city( $segments ) {
		if ( empty( $segments ) ) {
			return array();
		}

		$country_term = self::resolve_country_term_by_slug( sanitize_title( $segments[0] ) );
		if ( $country_term instanceof WP_Term && isset( $segments[1] ) ) {
			$city_term = self::resolve_city_term_for_country( sanitize_title( $segments[1] ), $country_term );
			if ( $city_term instanceof WP_Term ) {
				unset( $segments[1] );
				return array_values( $segments );
			}
		}

		$legacy_city = get_term_by( 'slug', sanitize_title( $segments[0] ), 'ad_country' );
		if ( $legacy_city && ! is_wp_error( $legacy_city ) ) {
			array_shift( $segments );
		}

		return array_values( $segments );
	}

	/**
	 * Remove all leading category segments after the country/city context.
	 *
	 * @param array<int,string> $segments Path segments.
	 * @return array<int,string>
	 */
	public static function strip_leading_category( $segments ) {
		$segments = array_values( $segments );
		if ( empty( $segments ) ) {
			return array();
		}

		$prefix = self::extract_route_prefix( $segments );
		$base   = array_slice( $segments, 0, $prefix );
		$tail   = array_slice( $segments, $prefix );

		foreach ( $tail as $segment ) {
			$category_term = get_term_by( 'slug', sanitize_title( $segment ), 'ad_cats' );
			if ( ! $category_term || is_wp_error( $category_term ) ) {
				return array_values( array_merge( $base, $tail ) );
			}
		}

		return array_values( $base );
	}

	/**
	 * Remove all leading city/category path filters.
	 *
	 * @param array<int,string> $segments Path segments.
	 * @return array<int,string>
	 */
	public static function strip_all_filters( $segments ) {
		$segments = array_values( $segments );
		if ( empty( $segments ) ) {
			return array();
		}

		$country_term = self::resolve_country_term_by_slug( sanitize_title( $segments[0] ) );
		if ( $country_term instanceof WP_Term ) {
			return array( $segments[0] );
		}

		return array();
	}

	/**
	 * Return whether a path starts with a given prefix.
	 *
	 * @param array<int,string> $segments Path segments.
	 * @param array<int,string> $prefix   Prefix segments.
	 * @return bool
	 */
	public static function segments_start_with( $segments, $prefix ) {
		if ( empty( $prefix ) ) {
			return true;
		}

		if ( count( $prefix ) > count( $segments ) ) {
			return false;
		}

		return array_slice( $segments, 0, count( $prefix ) ) === array_values( $prefix );
	}

	/**
	 * Determine the length of the stable country/city prefix.
	 *
	 * @param array<int,string> $segments Path segments.
	 * @return int
	 */
	private static function extract_route_prefix( $segments ) {
		if ( empty( $segments ) ) {
			return 0;
		}

		$country_term = self::resolve_country_term_by_slug( sanitize_title( $segments[0] ) );
		if ( ! ( $country_term instanceof WP_Term ) ) {
			return 0;
		}

		if ( isset( $segments[1] ) ) {
			$city_term = self::resolve_city_term_for_country( sanitize_title( $segments[1] ), $country_term );
			if ( $city_term instanceof WP_Term ) {
				return 2;
			}
		}

		return 1;
	}

	/**
	 * Resolve a root-level country term by slug.
	 *
	 * @param string $slug Country slug.
	 * @return WP_Term|null
	 */
	private static function resolve_country_term_by_slug( $slug ) {
		$term = get_term_by( 'slug', sanitize_title( (string) $slug ), 'ad_country' );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		return 0 === (int) $term->parent ? $term : null;
	}

	/**
	 * Resolve a city term within a root country term.
	 *
	 * @param string  $slug Country child slug.
	 * @param WP_Term $country_term Country term.
	 * @return WP_Term|null
	 */
	private static function resolve_city_term_for_country( $slug, $country_term ) {
		$term = get_term_by( 'slug', sanitize_title( (string) $slug ), 'ad_country' );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		if ( (int) $term->term_id === (int) $country_term->term_id ) {
			return null;
		}

		return in_array( (int) $country_term->term_id, array_map( 'intval', get_ancestors( (int) $term->term_id, 'ad_country', 'taxonomy' ) ), true ) ? $term : null;
	}
}
