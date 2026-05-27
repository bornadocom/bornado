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
	const CONTEXT_COOKIE = 'bornado_search_context';
	const CONTEXT_COOKIE_TTL = 2592000;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'sync_persisted_context' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 6 );
		add_action( 'wp_ajax_bornado_semantic_category_targets', array( __CLASS__, 'ajax_semantic_category_targets' ) );
		add_action( 'wp_ajax_nopriv_bornado_semantic_category_targets', array( __CLASS__, 'ajax_semantic_category_targets' ) );
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

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.BornadoSearchCoreConfig = ' . wp_json_encode(
				array(
					'cookieName'   => self::CONTEXT_COOKIE,
					'cookieTtl'    => self::CONTEXT_COOKIE_TTL,
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'routeContext' => self::get_frontend_route_context_payload(),
				)
			) . ';',
			'before'
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
	 * Return the brand-home destination for the current visitor.
	 *
	 * Home stays global until a location context exists; once a country/city is
	 * selected, the logo should lead back to that location landing page.
	 *
	 * @return string
	 */
	public static function get_brand_home_url() {
		$resolved = self::resolve_selected_location_terms();
		$country_term = $resolved['country_term'];
		$city_term    = $resolved['city_term'];

		if ( class_exists( 'Bornado_SEO_Routing' ) && method_exists( 'Bornado_SEO_Routing', 'get_semantic_url_preview' ) ) {
			$url = (string) Bornado_SEO_Routing::get_semantic_url_preview(
				$country_term instanceof WP_Term ? (int) $country_term->term_id : 0,
				$city_term instanceof WP_Term ? (int) $city_term->term_id : 0,
				0
			);
			if ( '' !== $url ) {
				return $url;
			}
		}

		if ( $city_term instanceof WP_Term ) {
			$term_link = get_term_link( $city_term, 'ad_country' );
			if ( ! is_wp_error( $term_link ) ) {
				return (string) $term_link;
			}
		}

		if ( $country_term instanceof WP_Term ) {
			$term_link = get_term_link( $country_term, 'ad_country' );
			if ( ! is_wp_error( $term_link ) ) {
				return (string) $term_link;
			}
		}

		return home_url( '/' );
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
		$persisted     = self::get_persisted_context();

		return array(
			'ad_title' => self::get_query_value( array( 'ad_title', 'title' ) ),
			'country'  => '' !== $route_country ? $route_country : self::get_context_value( array( 'bornado_country', 'country_id', 'ad_country', 'location' ), $persisted ),
			'city'     => '' !== $route_city ? $route_city : self::get_context_value( array( 'bornado_city', 'city_id' ), $persisted ),
			'category' => '' !== $route_cat ? $route_cat : self::get_query_value( array( 'cat_id', 'ad_cats' ) ),
			'ad_type'  => self::get_query_value( array( 'ad_type', 'type' ) ),
		);
	}

	/**
	 * Persist current search context for subsequent non-search page views.
	 *
	 * @return void
	 */
	public static function sync_persisted_context() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_json_request() ) {
			return;
		}

		$context = self::build_persistable_context();
		if ( empty( $context ) ) {
			return;
		}

		$current = self::get_persisted_context();
		$next    = array_merge( $current, $context );
		if ( wp_json_encode( $current ) === wp_json_encode( $next ) ) {
			return;
		}

		self::write_persisted_context( $next );
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
		$source = function_exists( 'bornado_seo_routing_get_public_query_args' )
			? bornado_seo_routing_get_public_query_args()
			: wp_unslash( $_GET );

		$clean = self::build_clean_query_args( $source, $allowed_keys );

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
	 * Resolve contextual semantic URLs for category widgets without touching theme core files.
	 *
	 * @return void
	 */
	public static function ajax_semantic_category_targets() {
		$cat_ids = isset( $_REQUEST['cat_ids'] ) ? (array) wp_unslash( $_REQUEST['cat_ids'] ) : array();
		$cat_ids = array_values( array_unique( array_filter( array_map( 'intval', $cat_ids ) ) ) );

		$country_id = isset( $_REQUEST['country_id'] ) ? max( 0, (int) wp_unslash( $_REQUEST['country_id'] ) ) : 0;
		$city_id    = isset( $_REQUEST['city_id'] ) ? max( 0, (int) wp_unslash( $_REQUEST['city_id'] ) ) : 0;
		$query_args = isset( $_REQUEST['query_args'] ) && is_array( $_REQUEST['query_args'] )
			? self::build_clean_query_args( wp_unslash( $_REQUEST['query_args'] ) )
			: array();

		$category_urls = array();
		if ( function_exists( 'bornado_seo_routing_get_contextual_url' ) ) {
			foreach ( $cat_ids as $cat_id ) {
				$url = bornado_seo_routing_get_contextual_url(
					array(
						'country_id'            => $country_id,
						'city_id'               => $city_id,
						'cat_id'                => (int) $cat_id,
						'query_args'            => $query_args,
						'preserve_public_query' => false,
					)
				);

				if ( is_string( $url ) && '' !== $url ) {
					$category_urls[ (string) $cat_id ] = $url;
				}
			}

			$all_url = bornado_seo_routing_get_contextual_url(
				array(
					'country_id'            => $country_id,
					'city_id'               => $city_id,
					'cat_id'                => 0,
					'query_args'            => $query_args,
					'preserve_public_query' => false,
				)
			);
		} else {
			$all_url = '';
		}

		wp_send_json_success(
			array(
				'categoryUrls' => $category_urls,
				'allUrl'       => is_string( $all_url ) ? $all_url : '',
			)
		);
	}

	/**
	 * Expose the current route context to frontend helpers.
	 *
	 * @return array<string,mixed>
	 */
	private static function get_frontend_route_context_payload() {
		$route_context = function_exists( 'bornado_seo_routing_get_context' ) ? bornado_seo_routing_get_context() : array();
		$country_term  = ! empty( $route_context['country_term'] ) && $route_context['country_term'] instanceof WP_Term ? $route_context['country_term'] : null;
		$city_term     = ! empty( $route_context['city_term'] ) && $route_context['city_term'] instanceof WP_Term ? $route_context['city_term'] : null;
		$deepest_term  = ! empty( $route_context['deepest_term'] ) && $route_context['deepest_term'] instanceof WP_Term ? $route_context['deepest_term'] : null;
		$public_query  = function_exists( 'bornado_seo_routing_get_public_query_args' ) ? bornado_seo_routing_get_public_query_args() : array();

		return array(
			'isSemanticRoute' => ! empty( $route_context['is_valid'] ),
			'canonicalUrl'    => ! empty( $route_context['canonical_url'] ) ? (string) $route_context['canonical_url'] : '',
			'countryId'       => $country_term instanceof WP_Term ? (int) $country_term->term_id : 0,
			'cityId'          => $city_term instanceof WP_Term ? (int) $city_term->term_id : 0,
			'categoryId'      => $deepest_term instanceof WP_Term ? (int) $deepest_term->term_id : 0,
			'publicQuery'     => self::build_clean_query_args( $public_query ),
		);
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
	 * Return the first available context value from the request or persisted state.
	 *
	 * @param array<int,string>      $keys      Candidate query var names.
	 * @param array<string,string>|null $persisted Persisted values.
	 * @return string
	 */
	private static function get_context_value( $keys, $persisted = null ) {
		$query_value = self::get_query_value( $keys );
		if ( '' !== $query_value ) {
			return $query_value;
		}

		$persisted = is_array( $persisted ) ? $persisted : self::get_persisted_context();
		foreach ( $keys as $key ) {
			if ( ! isset( $persisted[ $key ] ) ) {
				continue;
			}

			$value = $persisted[ $key ];
			if ( is_array( $value ) ) {
				continue;
			}

			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				return $value;
			}
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

	/**
	 * Build the subset of current request data that should survive navigation.
	 *
	 * @return array<string,mixed>
	 */
	private static function build_persistable_context() {
		$context = array();
		$route_context = function_exists( 'bornado_seo_routing_get_context' ) ? bornado_seo_routing_get_context() : array();

		if ( ! empty( $route_context['is_valid'] ) ) {
			$country_term = ! empty( $route_context['country_term'] ) && $route_context['country_term'] instanceof WP_Term ? $route_context['country_term'] : null;
			$city_term    = ! empty( $route_context['city_term'] ) && $route_context['city_term'] instanceof WP_Term ? $route_context['city_term'] : null;

			if ( $city_term instanceof WP_Term ) {
				$context['country_id'] = (int) $city_term->term_id;
			} elseif ( $country_term instanceof WP_Term ) {
				$context['country_id'] = (int) $country_term->term_id;
			}
		}

		$query_context = self::get_current_query_args( array(), self::get_persistable_context_keys() );
		if ( ! empty( $query_context ) ) {
			$context = array_merge( $context, $query_context );
		}

		return self::build_clean_query_args( $context, self::get_persistable_context_keys() );
	}

	/**
	 * Return query keys that are safe to persist across requests.
	 *
	 * @return array<int,string>
	 */
	private static function get_persistable_context_keys() {
		return array(
			'country_id',
			'ad_country',
			'location',
			'city_id',
			'bornado_country',
			'bornado_city',
		);
	}

	/**
	 * Read persisted context from the first-party cookie.
	 *
	 * @return array<string,string>
	 */
	private static function get_persisted_context() {
		if ( empty( $_COOKIE[ self::CONTEXT_COOKIE ] ) ) {
			return array();
		}

		$raw = wp_unslash( $_COOKIE[ self::CONTEXT_COOKIE ] );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		return self::build_clean_query_args( $data, self::get_persistable_context_keys() );
	}

	/**
	 * Store persisted context in a first-party cookie that JS can also update.
	 *
	 * @param array<string,mixed> $context Context payload.
	 * @return void
	 */
	private static function write_persisted_context( $context ) {
		if ( headers_sent() ) {
			return;
		}

		$context = self::build_clean_query_args( $context, self::get_persistable_context_keys() );
		$value   = empty( $context ) ? '' : wp_json_encode( $context );
		$expires = empty( $context ) ? time() - HOUR_IN_SECONDS : time() + self::CONTEXT_COOKIE_TTL;
		$path    = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$domain  = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

		setcookie(
			self::CONTEXT_COOKIE,
			(string) $value,
			$expires,
			$path,
			$domain,
			is_ssl(),
			false
		);

		if ( '' === $value ) {
			unset( $_COOKIE[ self::CONTEXT_COOKIE ] );
			return;
		}

		$_COOKIE[ self::CONTEXT_COOKIE ] = (string) $value;
	}

	/**
	 * Resolve the currently selected location into country/city terms.
	 *
	 * @return array{country_term:WP_Term|null,city_term:WP_Term|null}
	 */
	private static function resolve_selected_location_terms() {
		if ( class_exists( 'Bornado_Location_Picker_Service' ) && method_exists( 'Bornado_Location_Picker_Service', 'get_selected_location' ) ) {
			$selected = Bornado_Location_Picker_Service::get_selected_location();
			$country_id = ! empty( $selected['country']['id'] ) ? (int) $selected['country']['id'] : 0;
			$city_id    = ! empty( $selected['city']['id'] ) ? (int) $selected['city']['id'] : 0;

			return array(
				'country_term' => $country_id > 0 ? get_term( $country_id, 'ad_country' ) : null,
				'city_term'    => $city_id > 0 ? get_term( $city_id, 'ad_country' ) : null,
			);
		}

		$route_context = function_exists( 'bornado_seo_routing_get_context' ) ? bornado_seo_routing_get_context() : array();
		$country_term  = ! empty( $route_context['country_term'] ) && $route_context['country_term'] instanceof WP_Term ? $route_context['country_term'] : null;
		$city_term     = ! empty( $route_context['city_term'] ) && $route_context['city_term'] instanceof WP_Term ? $route_context['city_term'] : null;

		if ( $country_term instanceof WP_Term || $city_term instanceof WP_Term ) {
			return array(
				'country_term' => $country_term,
				'city_term'    => $city_term,
			);
		}

		$selected_context = self::get_selected_context();
		$term_ids         = array();
		if ( ! empty( $selected_context['city'] ) ) {
			$term_ids[] = absint( $selected_context['city'] );
		}
		if ( ! empty( $selected_context['country'] ) ) {
			$term_ids[] = absint( $selected_context['country'] );
		}

		foreach ( $term_ids as $term_id ) {
			if ( $term_id < 1 ) {
				continue;
			}

			$term = get_term( $term_id, 'ad_country' );
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			if ( 0 === (int) $term->parent ) {
				return array(
					'country_term' => $term,
					'city_term'    => null,
				);
			}

			return array(
				'country_term' => self::get_root_country_term( $term ),
				'city_term'    => $term,
			);
		}

		return array(
			'country_term' => null,
			'city_term'    => null,
		);
	}

	/**
	 * Resolve the root country ancestor for a nested ad_country term.
	 *
	 * @param WP_Term $term Location term.
	 * @return WP_Term|null
	 */
	private static function get_root_country_term( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return null;
		}

		if ( 0 === (int) $term->parent ) {
			return $term;
		}

		$ancestor_ids = array_reverse( array_map( 'intval', get_ancestors( (int) $term->term_id, 'ad_country', 'taxonomy' ) ) );
		if ( empty( $ancestor_ids ) ) {
			return null;
		}

		$root = get_term( (int) $ancestor_ids[0], 'ad_country' );
		return $root instanceof WP_Term ? $root : null;
	}
}
