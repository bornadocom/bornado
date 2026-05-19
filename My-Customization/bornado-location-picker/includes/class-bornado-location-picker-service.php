<?php
/**
 * Data preparation for the Bornado location picker.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Location_Picker_Service' ) ) {
	return;
}

final class Bornado_Location_Picker_Service {
	const TAXONOMY = 'ad_country';
	const CACHE_GROUP = 'bornado_location_picker';

	/**
	 * Runtime cache for root countries.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private static $root_countries = null;

	/**
	 * Runtime cache for city collections keyed by country id.
	 *
	 * @var array<int,array<int,array<string,mixed>>>
	 */
	private static $cities_by_country = array();

	/**
	 * Build the view model used by the renderer.
	 *
	 * @param array<string,mixed> $args Render options.
	 * @return array<string,mixed>
	 */
	public static function get_component_data( $args = array() ) {
		$defaults = array(
				'mode'           => 'compact',
				'title'          => '',
				'class_name'     => '',
				'button_label'   => __( 'انتخاب کشور و شهر', 'bornado-location-picker' ),
				'submit_label'   => __( 'اعمال موقعیت', 'bornado-location-picker' ),
				'reset_label'    => __( 'همه کشورها', 'bornado-location-picker' ),
				'search_label'   => __( 'جستجو در کشورها', 'bornado-location-picker' ),
				'city_label'     => __( 'جستجو در شهرها', 'bornado-location-picker' ),
				'widget_action'  => '',
				'auto_submit'    => false,
				'submit_on_apply'=> true,
				'show_title'     => false,
				'external_form_selector'  => '',
				'external_input_selector' => '',
				'render_hidden_input'     => true,
				'input_name'              => 'country_id',
				'input_id'                => '',
				'input_data_role'         => '',
				'panel_heading'  => __( 'انتخاب موقعیت', 'bornado-location-picker' ),
				'country_heading'=> __( 'کشورها', 'bornado-location-picker' ),
				'city_heading'   => __( 'شهرها', 'bornado-location-picker' ),
		);

		$args = is_array( $args ) ? $args : array();
		$args = wp_parse_args( $args, $defaults );

		$selected       = self::normalize_selected_location( self::get_selected_location() );
		$country        = self::normalize_term_payload( isset( $selected['country'] ) ? $selected['country'] : array() );
		$city           = self::normalize_term_payload( isset( $selected['city'] ) ? $selected['city'] : array() );
		$country_id     = ! empty( $country['id'] ) ? absint( $country['id'] ) : 0;
		$city_id        = ! empty( $city['id'] ) ? absint( $city['id'] ) : 0;
		$deepest_term_id = ! empty( $selected['deepest_term_id'] ) ? absint( $selected['deepest_term_id'] ) : ( $city_id ? $city_id : $country_id );
		$search_actions = self::normalize_search_actions( self::get_search_actions( isset( $args['widget_action'] ) ? (string) $args['widget_action'] : '' ) );
		$countries     = self::get_root_country_options();
		$cities        = $country_id > 0 ? self::get_city_options( $country_id ) : array();
		$panel_id      = wp_unique_id( 'bornado-location-picker-' );
		$form_action   = ! empty( $city['url'] ) ? $city['url'] : ( ! empty( $country['url'] ) ? $country['url'] : $search_actions['all_countries_action'] );

		$selected['country']         = $country;
		$selected['city']            = $city;
		$selected['deepest_term_id'] = $deepest_term_id;
		$selected['root_country_id'] = $country_id;
		$selected['country_code']    = ! empty( $country['countryCode'] ) ? (string) $country['countryCode'] : '';
		$default_summary_fallback    = self::build_summary_text(
			array(
				'country' => null,
				'city'    => null,
			)
		);
		$summary_fallback            = isset( $args['summary_fallback'] ) && '' !== trim( (string) $args['summary_fallback'] )
			? (string) $args['summary_fallback']
			: $default_summary_fallback;
		$current_summary             = self::build_summary_text( $selected );

		if ( 0 === $country_id && 0 === $city_id ) {
			$current_summary = $summary_fallback;
		}

		return array(
			'id'             => $panel_id,
			'args'           => $args,
			'mode'           => self::normalize_mode( (string) $args['mode'] ),
			'countries'      => $countries,
			'cities'         => $cities,
			'selected'       => $selected,
			'summary'        => $current_summary,
			'search_actions' => $search_actions,
			'form_action'    => $form_action,
			'hidden_fields'  => self::get_hidden_query_fields_html(),
			'config'         => array(
				'panelId'           => $panel_id,
				'mode'              => self::normalize_mode( (string) $args['mode'] ),
				'autoSubmit'        => ! empty( $args['auto_submit'] ),
				'submitOnApply'     => isset( $args['submit_on_apply'] ) ? (bool) $args['submit_on_apply'] : true,
				'externalFormSelector'  => (string) $args['external_form_selector'],
				'externalInputSelector' => (string) $args['external_input_selector'],
				'renderHiddenInput'     => ! empty( $args['render_hidden_input'] ),
				'inputName'             => sanitize_key( (string) $args['input_name'] ),
				'selected'          => array(
					'countryId'     => $country_id,
					'cityId'        => $city_id,
					'deepestTermId' => $deepest_term_id,
				),
				'actions'           => $search_actions,
				'countries'         => $countries,
				'initialCities'     => $cities,
				'summaryFallback'   => $summary_fallback,
				'strings'           => array(
					'panelHeading'      => (string) $args['panel_heading'],
					'countryHeading'    => (string) $args['country_heading'],
					'cityHeading'       => (string) $args['city_heading'],
					'searchCountries'   => (string) $args['search_label'],
					'searchCities'      => (string) $args['city_label'],
					'resetLabel'        => (string) $args['reset_label'],
					'submitLabel'       => (string) $args['submit_label'],
					'allCitiesLabel'    => __( 'همه شهرهای این کشور', 'bornado-location-picker' ),
					'chooseCountryHelp' => __( 'ابتدا کشور را انتخاب کنید تا شهرهای آن نمایش داده شود.', 'bornado-location-picker' ),
				),
			),
		);
	}

	/**
	 * Return the root countries list.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_root_country_options() {
		if ( null !== self::$root_countries ) {
			return self::$root_countries;
		}

		$cached = wp_cache_get( 'root_countries_' . self::get_cache_version(), self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			self::$root_countries = $cached;
			return self::$root_countries;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'parent'     => 0,
				'number'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			self::$root_countries = array();
			return self::$root_countries;
		}

		self::$root_countries = array_values(
			array_filter(
				array_map(
					array( __CLASS__, 'map_term' ),
					$terms
				)
			)
		);

		wp_cache_set( 'root_countries_' . self::get_cache_version(), self::$root_countries, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return self::$root_countries;
	}

	/**
	 * Return the city options for a root country.
	 *
	 * @param int $country_id Country term id.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_city_options( $country_id ) {
		$country_id = absint( $country_id );
		if ( $country_id < 1 ) {
			return array();
		}

		if ( isset( self::$cities_by_country[ $country_id ] ) ) {
			return self::$cities_by_country[ $country_id ];
		}

		$cache_key = 'cities_' . $country_id . '_' . self::get_cache_version();
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			self::$cities_by_country[ $country_id ] = $cached;
			return self::$cities_by_country[ $country_id ];
		}

		$country_term = self::get_root_country_term( $country_id );
		if ( ! $country_term instanceof WP_Term ) {
			self::$cities_by_country[ $country_id ] = array();
			return self::$cities_by_country[ $country_id ];
		}

		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'parent'     => (int) $country_term->term_id,
				'number'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$options = array(
			array(
				'id'         => 0,
				'label'      => sprintf( __( 'همه شهرهای %s', 'bornado-location-picker' ), $country_term->name ),
				'url'        => self::get_term_url( $country_term ),
				'parentId'   => (int) $country_term->term_id,
				'countryCode'=> self::get_country_code( $country_term ),
				'kind'       => 'country-base',
			),
		);

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$mapped = self::map_term( $term );
				if ( ! empty( $mapped ) ) {
					$options[] = $mapped;
				}
			}
		}

		self::$cities_by_country[ $country_id ] = $options;
		wp_cache_set( $cache_key, $options, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return self::$cities_by_country[ $country_id ];
	}

	/**
	 * Clear runtime and object-cache entries after location changes.
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$root_countries  = null;
		self::$cities_by_country = array();
		self::bump_cache_version();
	}

	/**
	 * Return a lightweight cache version token.
	 *
	 * @return string
	 */
	private static function get_cache_version() {
		$version = get_option( 'bornado_location_picker_cache_version', '' );
		if ( '' === $version ) {
			$version = (string) time();
			update_option( 'bornado_location_picker_cache_version', $version, false );
		}

		return (string) $version;
	}

	/**
	 * Rotate the cache version after term writes.
	 *
	 * @return void
	 */
	private static function bump_cache_version() {
		update_option( 'bornado_location_picker_cache_version', (string) time(), false );
	}

	/**
	 * Resolve the current selection from route context or query vars.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_selected_location() {
		$route_context = function_exists( 'bornado_seo_routing_get_context' ) ? bornado_seo_routing_get_context() : array();
		$country_term  = ! empty( $route_context['country_term'] ) && $route_context['country_term'] instanceof WP_Term ? $route_context['country_term'] : null;
		$city_term     = ! empty( $route_context['city_term'] ) && $route_context['city_term'] instanceof WP_Term ? $route_context['city_term'] : null;

		if ( ! $country_term instanceof WP_Term ) {
			$context      = function_exists( 'bornado_search_get_selected_context' ) ? bornado_search_get_selected_context() : array();
			$location_ids = array();

			if ( ! empty( $context['city'] ) ) {
				$location_ids[] = absint( $context['city'] );
			}
			if ( ! empty( $context['country'] ) ) {
				$location_ids[] = absint( $context['country'] );
			}

			foreach ( $location_ids as $location_id ) {
				$term = get_term( $location_id, self::TAXONOMY );
				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				if ( 0 === (int) $term->parent ) {
					$country_term = $term;
					break;
				}

				$city_term    = $term;
				$country_term = self::get_root_country_term( $term );
				break;
			}
		}

		$country = $country_term instanceof WP_Term ? self::map_term( $country_term ) : null;
		$city    = $city_term instanceof WP_Term ? self::map_term( $city_term ) : null;

		return array(
			'country'         => $country,
			'city'            => $city,
			'area'            => null,
			'deepest_term_id' => ! empty( $city['id'] ) ? (int) $city['id'] : ( ! empty( $country['id'] ) ? (int) $country['id'] : 0 ),
			'root_country_id' => ! empty( $country['id'] ) ? (int) $country['id'] : 0,
			'country_code'    => ! empty( $country['countryCode'] ) ? (string) $country['countryCode'] : '',
		);
	}

	/**
	 * Return hidden inputs that should travel with the picker.
	 *
	 * @return string
	 */
	public static function get_hidden_query_fields_html() {
		if ( function_exists( 'bornado_search_render_hidden_query_fields' ) ) {
			return bornado_search_render_hidden_query_fields(
				null,
				array(
					'country_id',
					'ad_country',
					'location',
					'city_id',
					'bornado_country',
					'bornado_city',
					'page',
					'paged',
				)
			);
		}

		return '';
	}

	/**
	 * Build a compact human summary for the current selection.
	 *
	 * @param array<string,mixed> $selected Current selection.
	 * @return string
	 */
	public static function build_summary_text( $selected ) {
		$country_label = ! empty( $selected['country']['label'] ) ? (string) $selected['country']['label'] : '';
		$city_label    = ! empty( $selected['city']['label'] ) ? (string) $selected['city']['label'] : '';

		if ( '' !== $country_label && '' !== $city_label ) {
			return $country_label . '، ' . $city_label;
		}

		if ( '' !== $country_label ) {
			return $country_label;
		}

		return __( 'همه کشورها', 'bornado-location-picker' );
	}

	/**
	 * Return contextual search URLs from search-core when available.
	 *
	 * @param string $widget_action Action context.
	 * @return array<string,string>
	 */
	private static function get_search_actions( $widget_action ) {
		if ( function_exists( 'bornado_search_get_actions' ) ) {
			return bornado_search_get_actions(
				array(
					'widget_action' => $widget_action,
				)
			);
		}

		$home = home_url( '/' );

		return array(
			'default_action'        => $home,
			'all_countries_action'  => $home,
			'all_cities_action'     => $home,
			'all_categories_action' => $home,
			'all_filters_action'    => $home,
		);
	}

	/**
	 * Normalize the selected location payload to predictable arrays.
	 *
	 * @param mixed $selected Raw selected payload.
	 * @return array<string,mixed>
	 */
	private static function normalize_selected_location( $selected ) {
		if ( ! is_array( $selected ) ) {
			$selected = array();
		}

		return array(
			'country'         => self::normalize_term_payload( isset( $selected['country'] ) ? $selected['country'] : array() ),
			'city'            => self::normalize_term_payload( isset( $selected['city'] ) ? $selected['city'] : array() ),
			'area'            => self::normalize_term_payload( isset( $selected['area'] ) ? $selected['area'] : array() ),
			'deepest_term_id' => isset( $selected['deepest_term_id'] ) ? absint( $selected['deepest_term_id'] ) : 0,
			'root_country_id' => isset( $selected['root_country_id'] ) ? absint( $selected['root_country_id'] ) : 0,
			'country_code'    => isset( $selected['country_code'] ) ? sanitize_text_field( (string) $selected['country_code'] ) : '',
		);
	}

	/**
	 * Normalize a country/city payload into scalar-safe keys.
	 *
	 * @param mixed $payload Raw term payload.
	 * @return array<string,mixed>
	 */
	private static function normalize_term_payload( $payload ) {
		if ( ! is_array( $payload ) ) {
			return array();
		}

		return array(
			'id'          => isset( $payload['id'] ) ? absint( $payload['id'] ) : 0,
			'label'       => isset( $payload['label'] ) ? sanitize_text_field( (string) $payload['label'] ) : '',
			'slug'        => isset( $payload['slug'] ) ? sanitize_title( (string) $payload['slug'] ) : '',
			'url'         => isset( $payload['url'] ) ? esc_url_raw( (string) $payload['url'] ) : '',
			'parentId'    => isset( $payload['parentId'] ) ? absint( $payload['parentId'] ) : 0,
			'countryCode' => isset( $payload['countryCode'] ) ? sanitize_text_field( (string) $payload['countryCode'] ) : '',
			'kind'        => isset( $payload['kind'] ) ? sanitize_key( (string) $payload['kind'] ) : '',
		);
	}

	/**
	 * Normalize action URLs so renderer code always sees string keys.
	 *
	 * @param mixed $actions Raw action payload.
	 * @return array<string,string>
	 */
	private static function normalize_search_actions( $actions ) {
		$home = home_url( '/' );
		if ( ! is_array( $actions ) ) {
			$actions = array();
		}

		return array(
			'default_action'        => ! empty( $actions['default_action'] ) ? esc_url_raw( (string) $actions['default_action'] ) : $home,
			'all_countries_action'  => ! empty( $actions['all_countries_action'] ) ? esc_url_raw( (string) $actions['all_countries_action'] ) : $home,
			'all_cities_action'     => ! empty( $actions['all_cities_action'] ) ? esc_url_raw( (string) $actions['all_cities_action'] ) : $home,
			'all_categories_action' => ! empty( $actions['all_categories_action'] ) ? esc_url_raw( (string) $actions['all_categories_action'] ) : $home,
			'all_filters_action'    => ! empty( $actions['all_filters_action'] ) ? esc_url_raw( (string) $actions['all_filters_action'] ) : $home,
		);
	}

	/**
	 * Map a term into the lightweight picker contract.
	 *
	 * @param WP_Term $term Term instance.
	 * @return array<string,mixed>
	 */
	private static function map_term( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		return array(
			'id'          => (int) $term->term_id,
			'label'       => (string) $term->name,
			'slug'        => (string) $term->slug,
			'url'         => self::get_term_url( $term ),
			'parentId'    => (int) $term->parent,
			'countryCode' => self::get_country_code( $term ),
			'kind'        => 0 === (int) $term->parent ? 'country' : 'city',
		);
	}

	/**
	 * Return a semantic-friendly term URL with safe fallbacks.
	 *
	 * @param WP_Term $term Term instance.
	 * @return string
	 */
	private static function get_term_url( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return home_url( '/' );
		}

		$term_url = get_term_link( $term );
		if ( ! is_wp_error( $term_url ) && is_string( $term_url ) && '' !== $term_url ) {
			return $term_url;
		}

		if ( class_exists( 'Bornado_SEO_Routing' ) && method_exists( 'Bornado_SEO_Routing', 'get_semantic_url_preview' ) ) {
			$root_country = self::get_root_country_term( $term );
			$country_id   = $root_country instanceof WP_Term ? (int) $root_country->term_id : (int) $term->term_id;
			$city_id      = 0 === (int) $term->parent ? 0 : (int) $term->term_id;
			return (string) Bornado_SEO_Routing::get_semantic_url_preview( $country_id, $city_id, 0 );
		}

		return home_url( '/' );
	}

	/**
	 * Resolve the root country term for any location term.
	 *
	 * @param WP_Term|int $term Term instance or id.
	 * @return WP_Term|null
	 */
	private static function get_root_country_term( $term ) {
		$term = get_term( $term, self::TAXONOMY );
		if ( ! $term instanceof WP_Term ) {
			return null;
		}

		if ( function_exists( 'bornado_get_country_data' ) ) {
			$data = bornado_get_country_data( $term );
			if ( ! empty( $data['root_country_id'] ) ) {
				$root_term = get_term( (int) $data['root_country_id'], self::TAXONOMY );
				if ( $root_term instanceof WP_Term ) {
					return $root_term;
				}
			}
		}

		if ( 0 === (int) $term->parent ) {
			return $term;
		}

		$ancestors = array_reverse( array_map( 'intval', get_ancestors( (int) $term->term_id, self::TAXONOMY, 'taxonomy' ) ) );
		if ( empty( $ancestors ) ) {
			return null;
		}

		$root_term = get_term( (int) $ancestors[0], self::TAXONOMY );

		return $root_term instanceof WP_Term ? $root_term : null;
	}

	/**
	 * Read the normalized country code for a location.
	 *
	 * @param WP_Term $term Term instance.
	 * @return string
	 */
	private static function get_country_code( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return '';
		}

		if ( function_exists( 'bornado_get_country_data' ) ) {
			$data = bornado_get_country_data( $term );
			if ( ! empty( $data['country_code'] ) ) {
				return (string) $data['country_code'];
			}
		}

		return '';
	}

	/**
	 * Normalize mode names to the supported set.
	 *
	 * @param string $mode Raw mode.
	 * @return string
	 */
	private static function normalize_mode( $mode ) {
		$mode = sanitize_key( $mode );
		if ( in_array( $mode, array( 'inline', 'header', 'mobile-sheet' ), true ) ) {
			return $mode;
		}

		return 'compact';
	}
}
