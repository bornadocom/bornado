<?php
/**
 * AdForest AJAX Search System (Search 2.0).
 *
 * Provides AJAX-powered filtering for the ad search page while staying
 * fully backward compatible with the legacy GET-based flow. When JS is
 * disabled or the endpoint fails the standard form submission still works.
 *
 * Public surface:
 *   - wp_ajax_adforest_ajax_search / wp_ajax_nopriv_adforest_ajax_search
 *   - wp_ajax_adforest_ajax_cat_fields / wp_ajax_nopriv_adforest_ajax_cat_fields
 *   - adforest_build_search_query_args()  (reusable query builder)
 *   - adforest_tokenize_location()        (multi-keyword location helper)
 *
 * Filters:
 *   - adforest_ajax_search_should_enqueue (bool)
 *   - adforest_ajax_search_query_args     (array $args, array $params)
 *   - adforest_ajax_search_response       (array $payload, array $params)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Should a category search include child categories in its tax_query?
 *
 * Wired to the Redux option `search_show_sub_cats_with_parent` so admins
 * can toggle "Show Sub Categories with Parent" in Theme Options → Search
 * Settings. Child themes can override via `adforest_include_child_categories`.
 *
 * Default: true (matches the pre-option legacy behavior so upgrades stay
 * backward compatible).
 */
if ( ! function_exists( 'adforest_include_child_categories' ) ) {
	function adforest_include_child_categories() {
		global $adforest_theme;
		$enabled = isset( $adforest_theme['search_show_sub_cats_with_parent'] )
			? (bool) $adforest_theme['search_show_sub_cats_with_parent']
			: true;
		return (bool) apply_filters( 'adforest_include_child_categories', $enabled );
	}
}

/**
 * Should a location (ad_country) search include child locations?
 *
 * SCOPE — this helper is intentionally restricted to the taxonomy-based
 * country widget path. It is read exclusively inside the `ad_country`
 * tax_query branch of the search builder and the parallel legacy
 * templates. It MUST NOT be consulted from:
 *   - Radius search (geo BETWEEN on `_adforest_ad_map_lat/long`)
 *   - Keyword / tokenized `_adforest_ad_location` meta LIKE queries
 *   - Any non-`ad_country` tax_query
 *
 * Wired to the Redux option `sb_search_sub_locations`. Child themes can
 * override via `adforest_include_child_locations`. Default: true (so the
 * common "pick a country → get ads from all its cities" UX works out of
 * the box).
 */
if ( ! function_exists( 'adforest_include_child_locations' ) ) {
	function adforest_include_child_locations() {
		global $adforest_theme;
		$enabled = isset( $adforest_theme['sb_search_sub_locations'] )
			? (bool) $adforest_theme['sb_search_sub_locations']
			: true;
		return (bool) apply_filters( 'adforest_include_child_locations', $enabled );
	}
}

/**
 * The radius-search code path adds this filter to widen DECIMAL precision
 * before it runs the query. The legacy template defines the callback
 * inline, but AJAX requests don't load the template — so we provide a
 * canonical definition here so the filter always resolves.
 */
if ( ! function_exists( 'adforest_cast_decimal_precision' ) ) {
	function adforest_cast_decimal_precision( $array ) {
		if ( isset( $array['where'] ) ) {
			$array['where'] = str_replace( 'DECIMAL', 'DECIMAL(10,3)', $array['where'] );
		}
		return $array;
	}
}

/**
 * Whitelist + sanitize a raw filter bag coming from client JS or legacy GET.
 */
if ( ! function_exists( 'adforest_sanitize_search_params' ) ) {
	function adforest_sanitize_search_params( $raw ) {
		$params = array();
		if ( ! is_array( $raw ) ) {
			return $params;
		}

		$scalar_whitelist = array(
			'ad_title', 'cat_id', 'country_id', 'ad_currency', 'condition',
			'ad_type', 'adtype', 'warranty', 'ad', 'sort', 'c',
			'min_price', 'max_price', 'location', 'rd', 'lat', 'long',
			'view-type', 'page-number', 'paged',
		);
		// Child themes / extensions can extend the whitelist via filter.
		$scalar_whitelist = apply_filters( 'adforest_ajax_search_allowed_params', $scalar_whitelist, $raw );
		foreach ( $scalar_whitelist as $k ) {
			if ( isset( $raw[ $k ] ) && ! is_array( $raw[ $k ] ) ) {
				$params[ $k ] = sanitize_text_field( wp_unslash( (string) $raw[ $k ] ) );
			}
		}

		if ( isset( $raw['custom'] ) && is_array( $raw['custom'] ) ) {
			$params['custom'] = array();
			foreach ( $raw['custom'] as $k => $v ) {
				$key = sanitize_key( $k );
				if ( $key === '' ) {
					continue;
				}
				if ( is_array( $v ) ) {
					$clean = array();
					foreach ( $v as $vv ) {
						if ( ! is_array( $vv ) ) {
							$clean[] = sanitize_text_field( wp_unslash( (string) $vv ) );
						}
					}
					$params['custom'][ $key ] = $clean;
				} else {
					$params['custom'][ $key ] = sanitize_text_field( wp_unslash( (string) $v ) );
				}
			}
		}

		foreach ( array( 'min_custom', 'max_custom' ) as $ck ) {
			if ( isset( $raw[ $ck ] ) && is_array( $raw[ $ck ] ) ) {
				$params[ $ck ] = array();
				foreach ( $raw[ $ck ] as $k => $v ) {
					$key = sanitize_key( $k );
					if ( $key === '' || is_array( $v ) ) {
						continue;
					}
					$params[ $ck ][ $key ] = sanitize_text_field( wp_unslash( (string) $v ) );
				}
			}
		}

		// Coerce numeric-ish values into plain strings (already sanitized above).
		foreach ( array( 'cat_id', 'country_id', 'ad_currency', 'min_price', 'max_price', 'rd', 'paged', 'page-number' ) as $num_key ) {
			if ( isset( $params[ $num_key ] ) && ! is_numeric( $params[ $num_key ] ) ) {
				unset( $params[ $num_key ] );
			}
		}

		return $params;
	}
}

/**
 * Tokenize "Harlow, Essex, UK" -> ["Harlow","Essex","UK"] for multi-keyword match.
 */
if ( ! function_exists( 'adforest_tokenize_location' ) ) {
	function adforest_tokenize_location( $raw ) {
		$raw = trim( (string) $raw );
		if ( $raw === '' ) {
			return array();
		}
		$parts  = preg_split( '/[,;]+/', $raw );
		$tokens = array();
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( $part === '' ) {
				continue;
			}
			$tokens[] = $part;
		}
		return array_values( array_unique( $tokens ) );
	}
}

/**
 * Translate a sanitized param bag into WP_Query args.
 *
 * Mirrors the inline logic from template-parts/layouts/search/search-sidebar.php
 * (and the sibling topbar/map variants) so AJAX and legacy requests produce
 * identical result sets.
 */
if ( ! function_exists( 'adforest_build_search_query_args' ) ) {
	function adforest_build_search_query_args( $params ) {
		global $adforest_theme;

		$params = is_array( $params ) ? $params : array();

		$map_type = function_exists( 'adforest_mapType' ) ? adforest_mapType() : 'google_map';

		$allow_near_by      = ! empty( $params['location'] );
		$allow_rd           = ! empty( $params['rd'] );
		$lat_lng_meta_query = array();

		if ( $allow_near_by && $allow_rd ) {
			$latlng = array();
			if ( $map_type === 'leafletjs_map' ) {
				$map_lat  = isset( $params['lat'] ) ? $params['lat'] : '';
				$map_long = isset( $params['long'] ) ? $params['long'] : '';
				if ( $map_lat !== '' && $map_long !== '' ) {
					$latlng = array( 'latitude' => $map_lat, 'longitude' => $map_long );
				}
			} elseif ( $map_type === 'google_map' && function_exists( 'adforest_getLatLong' ) ) {
				$latlng = adforest_getLatLong( $params['location'] );
			}

			if ( is_array( $latlng ) && ! empty( $latlng ) && function_exists( 'adforest_determine_minMax_latLong' ) ) {
				$latitude  = isset( $latlng['latitude'] ) ? $latlng['latitude'] : '';
				$longitude = isset( $latlng['longitude'] ) ? $latlng['longitude'] : '';
				$distance  = isset( $params['rd'] ) ? $params['rd'] : '20';
				if ( $latitude !== '' && $longitude !== '' ) {
					$lats_longs = adforest_determine_minMax_latLong(
						array( 'latitude' => $latitude, 'longitude' => $longitude, 'distance' => $distance ),
						false
					);
					if ( is_array( $lats_longs ) && isset( $lats_longs['lat']['min'], $lats_longs['lat']['max'], $lats_longs['long']['min'], $lats_longs['long']['max'] ) ) {
						$lat_lng_meta_query[] = array(
							'key'     => '_adforest_ad_map_lat',
							'value'   => array( $lats_longs['lat']['min'], $lats_longs['lat']['max'] ),
							'compare' => 'BETWEEN',
							'type'    => 'DECIMAL',
						);
						$lat_lng_meta_query[] = array(
							'key'     => '_adforest_ad_map_long',
							'value'   => array( $lats_longs['long']['min'], $lats_longs['long']['max'] ),
							'compare' => 'BETWEEN',
							'type'    => 'DECIMAL',
						);
						if ( function_exists( 'adforest_cast_decimal_precision' ) ) {
							add_filter( 'get_meta_sql', 'adforest_cast_decimal_precision' );
						}
					}
				}
			}
		}

		$meta_query = array();

		if ( ! empty( $params['condition'] ) ) {
			$meta_query[] = array( 'key' => '_adforest_ad_condition', 'value' => $params['condition'], 'compare' => '=' );
		}
		if ( ! empty( $params['ad_type'] ) ) {
			$meta_query[] = array( 'key' => '_adforest_ad_type', 'value' => $params['ad_type'], 'compare' => '=' );
		} elseif ( ! empty( $params['adtype'] ) ) {
			$meta_query[] = array( 'key' => '_adforest_ad_type', 'value' => $params['adtype'], 'compare' => '=' );
		}
		if ( ! empty( $params['warranty'] ) ) {
			$meta_query[] = array( 'key' => '_adforest_ad_warranty', 'value' => $params['warranty'], 'compare' => '=' );
		}
		if ( ! empty( $params['ad'] ) ) {
			$meta_query[] = array( 'key' => '_adforest_is_feature', 'value' => $params['ad'], 'compare' => '=' );
		}
		if ( ! empty( $params['sort'] ) && $params['sort'] === 'featured' ) {
			$meta_query[] = array( 'key' => '_adforest_is_feature', 'value' => '1', 'compare' => '=' );
		}
		if ( ! empty( $params['c'] ) ) {
			$meta_query[] = array( 'key' => '_adforest_ad_currency', 'value' => $params['c'], 'compare' => '=' );
		}

		if ( isset( $params['min_price'] ) && $params['min_price'] !== '' ) {
			$max = ( isset( $params['max_price'] ) && $params['max_price'] !== '' ) ? $params['max_price'] : PHP_INT_MAX;
			$meta_query[] = array(
				'key'     => '_adforest_ad_price',
				'value'   => array( $params['min_price'], $max ),
				'type'    => 'numeric',
				'compare' => 'BETWEEN',
			);
		}

		// Location (multi-token OR when multiple tokens provided).
		if ( ! empty( $params['location'] ) && ! $allow_rd ) {
			$tokens = adforest_tokenize_location( $params['location'] );
			if ( count( $tokens ) > 1 ) {
				$loc_sub = array( 'relation' => 'OR' );
				foreach ( $tokens as $tok ) {
					$loc_sub[] = array( 'key' => '_adforest_ad_location', 'value' => $tok, 'compare' => 'LIKE' );
				}
				$meta_query[] = $loc_sub;
			} else {
				$meta_query[] = array( 'key' => '_adforest_ad_location', 'value' => trim( $params['location'] ), 'compare' => 'LIKE' );
			}
		}

		// Custom dynamic fields.
		$custom_search = array();
		if ( ! empty( $params['min_custom'] ) && is_array( $params['min_custom'] ) ) {
			foreach ( $params['min_custom'] as $key => $val ) {
				$min_val = $val;
				$max_val = isset( $params['max_custom'][ $key ] ) ? $params['max_custom'][ $key ] : '';
				if ( $min_val !== '' && $max_val !== '' ) {
					$meta_key = '_adforest_tpl_field_' . $key;
					if ( function_exists( 'adforest_validateDateFormat' )
						&& adforest_validateDateFormat( $min_val )
						&& adforest_validateDateFormat( $max_val ) ) {
						$custom_search[] = array(
							'key'     => $meta_key,
							'value'   => array( $min_val, $max_val ),
							'compare' => 'BETWEEN',
						);
					} else {
						$custom_search[] = array(
							'key'     => $meta_key,
							'value'   => array( $min_val, $max_val ),
							'type'    => 'numeric',
							'compare' => 'BETWEEN',
						);
					}
				}
			}
		}
		if ( ! empty( $params['custom'] ) && is_array( $params['custom'] ) ) {
			$template_cat_id = isset( $params['cat_id'] ) ? $params['cat_id'] : '';
			$cat_template    = function_exists( 'adforest_dynamic_field_type_template' )
				? adforest_dynamic_field_type_template( $template_cat_id )
				: array();

			foreach ( $params['custom'] as $key => $val ) {
				$meta_key = '_adforest_tpl_field_' . $key;
				if ( is_array( $val ) ) {
					foreach ( $val as $v ) {
						if ( $v === '' || $v === '0' ) {
							continue;
						}
						$custom_search[] = array( 'key' => $meta_key, 'value' => $v, 'compare' => 'LIKE' );
					}
				} else {
					$val_str = trim( (string) $val );
					if ( $val_str === '' || $val_str === '0' ) {
						continue;
					}
					$field_type = function_exists( 'adforest_dynamic_field_type' )
						? adforest_dynamic_field_type( $cat_template, $key )
						: '';
					$val_str    = stripslashes( $val_str );
					if ( $field_type === 'checkbox' ) {
						$custom_search[] = array( 'key' => $meta_key, 'value' => '"' . $val_str . '"', 'compare' => 'LIKE' );
					} elseif ( $field_type === 'select' ) {
						$custom_search[] = array( 'key' => $meta_key, 'value' => $val_str, 'compare' => 'REGEXP' );
					} else {
						$custom_search[] = array( 'key' => $meta_key, 'value' => $val_str, 'compare' => 'LIKE' );
					}
				}
			}
		}
		if ( ! empty( $custom_search ) ) {
			$custom_search['relation'] = 'AND';
			$meta_query[]              = $custom_search;
		}
		if ( ! empty( $lat_lng_meta_query ) ) {
			$meta_query = array_merge( $meta_query, $lat_lng_meta_query );
		}

		// Taxonomy filters. include_children toggles are driven by Theme
		// Options → Search Settings and can be overridden via filters for
		// per-request customization in child themes.
		$tax_query = array();
		if ( ! empty( $params['cat_id'] ) && is_numeric( $params['cat_id'] ) ) {
			$tax_query[] = array(
				'taxonomy'         => 'ad_cats',
				'field'            => 'term_id',
				'terms'            => (int) $params['cat_id'],
				'include_children' => adforest_include_child_categories() ? 1 : 0,
			);
		}
		// Taxonomy-based location widget only. include_children is gated
		// on a taxonomy term_id being present — radius search and the
		// `_adforest_ad_location` meta LIKE path never reach this branch.
		if ( ! empty( $params['country_id'] ) && is_numeric( $params['country_id'] ) ) {
			$tax_query[] = array(
				'taxonomy'         => 'ad_country',
				'field'            => 'term_id',
				'terms'            => (int) $params['country_id'],
				'include_children' => adforest_include_child_locations() ? 1 : 0,
			);
		}
		if ( ! empty( $params['ad_currency'] ) && is_numeric( $params['ad_currency'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'ad_currency',
				'field'    => 'term_id',
				'terms'    => (int) $params['ad_currency'],
			);
		}
		$tax_query = apply_filters( 'adforest_site_location_ads', $tax_query, 'search' );

		// Ordering.
		$order          = 'desc';
		$order_by       = 'date';
		$ordering_price = '';
		if ( ! empty( $params['sort'] ) ) {
			$parts = explode( '-', $params['sort'] );
			$order = isset( $parts[1] ) ? strtolower( $parts[1] ) : 'desc';
			if ( isset( $parts[0] ) && $parts[0] === 'price' ) {
				$order_by       = 'meta_value_num';
				$ordering_price = '_adforest_ad_price';
			} else {
				$order_by = isset( $parts[0] ) ? $parts[0] : 'date';
			}
		}
		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			$order = 'desc';
		}

		$paged = 1;
		if ( ! empty( $params['paged'] ) ) {
			$paged = max( 1, (int) $params['paged'] );
		} elseif ( ! empty( $params['page-number'] ) ) {
			$paged = max( 1, (int) $params['page-number'] );
		}

		$title = isset( $params['ad_title'] ) ? $params['ad_title'] : '';

		$args = array(
			's'              => $title,
			'post_type'      => 'ad_post',
			'post_status'    => 'publish',
			'posts_per_page' => (int) get_option( 'posts_per_page' ),
			'tax_query'      => $tax_query,
			'meta_query'     => $meta_query,
			'order'          => $order,
			'orderby'        => $order_by,
			'paged'          => $paged,
		);
		if ( $ordering_price !== '' ) {
			$args['meta_key'] = $ordering_price;
		}

		// Global Filters Mode scoping.
		// In global mode every category's custom fields are visible at once,
		// so a user can submit filters that don't apply to the category they
		// just picked. Without scoping, a `_adforest_tpl_field_property_size`
		// clause AND'd with `cat_id=Cars` returns zero results because no Car
		// ad has that meta key. Drop irrelevant custom-field clauses based on
		// the slug→[cat_ids] map. Built-in meta (price, condition, location,
		// etc.) and lat/long range clauses are never touched. Only runs when:
		//   - mode is `global` (legacy `category` is normalized away)
		//   - a numeric cat_id is present in the request
		//   - the field map is available (otherwise safe-fallback keeps all)
		$filter_mode = isset( $adforest_theme['sb_search_filter_mode'] )
			? $adforest_theme['sb_search_filter_mode']
			: 'category_based';
		if ( $filter_mode === 'category' ) {
			$filter_mode = 'category_based';
		}
		if ( $filter_mode === 'global'
			&& ! empty( $params['cat_id'] )
			&& is_numeric( $params['cat_id'] )
			&& ! empty( $args['meta_query'] ) ) {
			$field_map = adforest_get_global_field_category_map();
			if ( ! empty( $field_map ) ) {
				$args['meta_query'] = adforest_filter_meta_query_by_category(
					$args['meta_query'],
					(int) $params['cat_id'],
					$field_map
				);
			}
		}

		$args = apply_filters( 'adforest_wpml_show_all_posts', $args );
		$args = apply_filters( 'adforest_ajax_search_query_args', $args, $params );

		return $args;
	}
}

/**
 * AJAX: run a search request and return rendered listing HTML.
 *
 * Accepts a `filters` bag that mirrors the legacy $_GET structure so the
 * query builder can be shared across both code paths.
 */
if ( ! function_exists( 'adforest_ajax_search_callback' ) ) {
	function adforest_ajax_search_callback() {
		check_ajax_referer( 'adforest_ajax_search_nonce', 'security' );

		global $adforest_theme;

		// Accept either a pre-parsed `filters` array or a URL-encoded query
		// string (`filters_raw`). The query-string form keeps parity with
		// $_GET and avoids JS-side array reconstruction bugs.
		$raw = array();
		if ( isset( $_POST['filters_raw'] ) && is_string( $_POST['filters_raw'] ) ) {
			parse_str( wp_unslash( $_POST['filters_raw'] ), $raw );
		} elseif ( isset( $_POST['filters'] ) && is_array( $_POST['filters'] ) ) {
			$raw = $_POST['filters'];
		}
		$params = adforest_sanitize_search_params( $raw );

		// The legacy renderer reads a handful of values from $_GET
		// (view-type, page-number, sort, etc.). Spoof them for the request
		// lifetime so grid/list toggles and meta templates behave correctly.
		$get_backup = $_GET;
		$_GET       = array_merge( $_GET, $params );

		// Spoof REQUEST_URI so get_pagenum_link() builds pagination links
		// that point at the search page (with the current filters) rather
		// than admin-ajax.php. Without this, pagination breaks for users
		// with JS disabled and is unshareable.
		$uri_backup     = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		$search_page_id = isset( $adforest_theme['sb_search_page'] ) ? (int) $adforest_theme['sb_search_page'] : 0;
		$search_page_id = apply_filters( 'adforest_language_page_id', $search_page_id );
		if ( $search_page_id ) {
			$search_path  = wp_parse_url( get_permalink( $search_page_id ), PHP_URL_PATH );
			$filters_qs   = isset( $_POST['filters_raw'] ) ? wp_unslash( (string) $_POST['filters_raw'] ) : '';
			if ( $search_path ) {
				$_SERVER['REQUEST_URI'] = $filters_qs !== ''
					? $search_path . '?' . $filters_qs
					: $search_path;
			}
		}

		$loading_ads_mode = ! empty( $adforest_theme['loading_ads_mode'] ) ? $adforest_theme['loading_ads_mode'] : 'pagination';
		$style_infinity   = ( $loading_ads_mode === 'infinity_scroll' ) ? 'style = "height: 1000px; overflow: auto;"' : '';

		$args  = adforest_build_search_query_args( $params );
		$query = new WP_Query( $args );

		$ad_count = 0;
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$ad_count++;
			}
			$query->rewind_posts();
		}
		$paged = isset( $args['paged'] ) ? (int) $args['paged'] : 1;

		ob_start();
		if ( function_exists( 'adforest_render_ads_in_search' ) ) {
			echo adforest_render_ads_in_search( $query, $style_infinity, $loading_ads_mode, $paged, $args, $ad_count );
		}
		$html = ob_get_clean();

		if ( trim( $html ) === '' ) {
			$html = '<div class="adforest-ajax-empty text-center p-4">'
				. esc_html__( 'No results found for your filters.', 'adforest' )
				. '</div>';
		}

		wp_reset_postdata();
		$_GET                    = $get_backup;
		$_SERVER['REQUEST_URI']  = $uri_backup;

		$payload = array(
			'html'          => $html,
			'total'         => (int) $query->found_posts,
			'max_num_pages' => (int) $query->max_num_pages,
			'paged'         => $paged,
			'query_args'    => $args,
		);
		$payload = apply_filters( 'adforest_ajax_search_response', $payload, $params );

		wp_send_json_success( $payload );
	}
	add_action( 'wp_ajax_adforest_ajax_search', 'adforest_ajax_search_callback' );
	add_action( 'wp_ajax_nopriv_adforest_ajax_search', 'adforest_ajax_search_callback' );
}

/**
 * Global Filters Mode — aggregate every category's dynamic fields into a
 * single deduplicated list keyed by slug.
 *
 * Walks every `ad_cats` term, resolves each to its template via
 * `adforest_dynamic_templateID()`, fetches the template's
 * `_sb_dynamic_form_fields` term meta, parses it via `sb_dynamic_form_data()`,
 * and merges entries by slug. First-seen `types`/`titles` win; for choice
 * fields (select, checkbox, radio, color), the union of unique `values`
 * options is preserved so admins don't lose dropdown items present in one
 * category but not another.
 *
 * Result is cached in the `adforest_global_fields_cache` transient (1h)
 * and busted automatically when categories or template terms change.
 *
 * @return array List of normalized field rows compatible with sb_dynamic_form_data().
 */
if ( ! function_exists( 'adforest_get_all_dynamic_fields' ) ) {
	function adforest_get_all_dynamic_fields() {
		$fields_key = 'adforest_global_fields_cache';
		$map_key    = 'adforest_global_field_map_cache';

		// Both caches are written together as a side effect of the same
		// walk; only short-circuit when BOTH are present so an upgrade
		// from a pre-map build doesn't leave the map perpetually empty.
		$cached_fields = get_transient( $fields_key );
		$cached_map    = get_transient( $map_key );
		if ( is_array( $cached_fields ) && is_array( $cached_map ) ) {
			return apply_filters( 'adforest_global_dynamic_fields', $cached_fields );
		}

		$fields_by_slug = array();
		$map_by_slug    = array(); // slug => [cat_id, cat_id, ...]

		if ( ! function_exists( 'sb_dynamic_form_data' ) || ! function_exists( 'adforest_dynamic_templateID' ) ) {
			set_transient( $fields_key, array(), HOUR_IN_SECONDS );
			set_transient( $map_key,    array(), HOUR_IN_SECONDS );
			return apply_filters( 'adforest_global_dynamic_fields', array() );
		}

		// `hide_empty` is false so admins see all configured templates even
		// when a category currently has zero ads (matches admin expectation).
		$cats = get_terms( array(
			'taxonomy'   => 'ad_cats',
			'hide_empty' => false,
			'fields'     => 'ids',
		) );
		if ( is_wp_error( $cats ) || empty( $cats ) ) {
			set_transient( $fields_key, array(), HOUR_IN_SECONDS );
			set_transient( $map_key,    array(), HOUR_IN_SECONDS );
			return apply_filters( 'adforest_global_dynamic_fields', array() );
		}

		// Memoize parsed rows per template — multiple categories often share
		// a template. We still iterate every category (so the map records
		// every cat that inherits the template) but only parse once.
		$template_rows = array();

		foreach ( $cats as $cat_id ) {
			$cat_id      = (int) $cat_id;
			$template_id = adforest_dynamic_templateID( $cat_id );
			if ( ! $template_id ) {
				continue;
			}

			if ( ! isset( $template_rows[ $template_id ] ) ) {
				$raw  = get_term_meta( $template_id, '_sb_dynamic_form_fields', true );
				$rows = ! empty( $raw ) ? sb_dynamic_form_data( $raw ) : array();
				$template_rows[ $template_id ] = is_array( $rows ) ? $rows : array();
			}

			foreach ( $template_rows[ $template_id ] as $row ) {
				if ( empty( $row['types'] ) || empty( $row['slugs'] ) ) {
					continue;
				}
				if ( isset( $row['status'] ) && (int) $row['status'] === 0 ) {
					continue;
				}
				if ( ! isset( $row['in_search'] ) || $row['in_search'] !== 'yes' ) {
					continue;
				}
				// Type 5 is intentionally excluded by the per-category
				// renderer; mirror that decision here.
				if ( (int) $row['types'] === 5 ) {
					continue;
				}

				$slug = sanitize_key( $row['slugs'] );
				if ( $slug === '' ) {
					continue;
				}

				if ( ! isset( $fields_by_slug[ $slug ] ) ) {
					$fields_by_slug[ $slug ] = $row;
				} else {
					// Merge `values` for choice-type fields so dropdown options
					// from every category that uses this slug are available.
					$existing  = $fields_by_slug[ $slug ];
					$type      = (int) $existing['types'];
					$is_choice = in_array( $type, array( 2, 3, 7, 8, 9 ), true );

					if ( $is_choice && ! empty( $row['values'] ) ) {
						$existing_opts = isset( $existing['values'] ) && $existing['values'] !== ''
							? explode( '|', $existing['values'] )
							: array();
						$new_opts = explode( '|', $row['values'] );
						$merged   = array_values( array_unique( array_merge( $existing_opts, $new_opts ) ) );
						$fields_by_slug[ $slug ]['values'] = implode( '|', $merged );
					}
				}

				// Track every category that exposes this slug so the search
				// builder can drop irrelevant filters when one category is
				// selected in Global mode.
				if ( ! isset( $map_by_slug[ $slug ] ) ) {
					$map_by_slug[ $slug ] = array();
				}
				if ( ! in_array( $cat_id, $map_by_slug[ $slug ], true ) ) {
					$map_by_slug[ $slug ][] = $cat_id;
				}
			}
		}

		$fields = array_values( $fields_by_slug );

		set_transient( $fields_key, $fields,      HOUR_IN_SECONDS );
		set_transient( $map_key,    $map_by_slug, HOUR_IN_SECONDS );

		return apply_filters( 'adforest_global_dynamic_fields', $fields );
	}
}

/**
 * Return the slug → [ad_cats term IDs] map built by the global aggregator.
 *
 * The map is cached in `adforest_global_field_map_cache`. If missing (first
 * read after a cache bust, or upgrade from a pre-map version), the
 * aggregator is invoked to populate both transients as a side effect.
 *
 * Returns an empty array on failure — callers MUST treat empty as
 * "no information; do not scope" to satisfy the safe-fallback rule.
 */
if ( ! function_exists( 'adforest_get_global_field_category_map' ) ) {
	function adforest_get_global_field_category_map() {
		$map = get_transient( 'adforest_global_field_map_cache' );
		if ( ! is_array( $map ) ) {
			adforest_get_all_dynamic_fields();
			$map = get_transient( 'adforest_global_field_map_cache' );
			if ( ! is_array( $map ) ) {
				$map = array();
			}
		}
		return apply_filters( 'adforest_global_field_category_map', $map );
	}
}

/**
 * Drop custom-field meta_query clauses for slugs that don't belong to the
 * selected category. Only `_adforest_tpl_field_*` keys are eligible — the
 * built-in price / condition / type / location keys are left alone.
 *
 * Recurses into nested clause groups one level (the query builder bundles
 * dynamic fields into a single AND sub-array). Empty groups left after
 * pruning are removed so we don't leave stranded `relation` keys.
 *
 * Safe fallback: when a slug isn't present in the map at all, the clause
 * is preserved — never silently drop a filter we don't have data for.
 */
if ( ! function_exists( 'adforest_filter_meta_query_by_category' ) ) {
	function adforest_filter_meta_query_by_category( $meta_query, $cat_id, $field_map ) {
		if ( ! is_array( $meta_query ) || empty( $meta_query ) ) {
			return $meta_query;
		}
		$cat_id     = (int) $cat_id;
		$prefix     = '_adforest_tpl_field_';
		$prefix_len = strlen( $prefix );

		$out = array();
		foreach ( $meta_query as $key => $clause ) {
			if ( $key === 'relation' ) {
				$out[ $key ] = $clause;
				continue;
			}
			if ( ! is_array( $clause ) ) {
				$out[ $key ] = $clause;
				continue;
			}
			if ( isset( $clause['key'] ) && is_string( $clause['key'] ) ) {
				if ( strpos( $clause['key'], $prefix ) === 0 ) {
					$slug = substr( $clause['key'], $prefix_len );
					if ( isset( $field_map[ $slug ] ) && is_array( $field_map[ $slug ] ) && ! empty( $field_map[ $slug ] ) ) {
						if ( ! in_array( $cat_id, $field_map[ $slug ], true ) ) {
							// Slug exists in map but not for this cat — drop.
							continue;
						}
					}
					// Unknown slug → keep (safe fallback).
				}
				// Built-in (`_adforest_ad_*`) or unknown leaf — keep.
				$out[ $key ] = $clause;
				continue;
			}

			// Nested group — recurse, then drop if it became empty.
			$sub = adforest_filter_meta_query_by_category( $clause, $cat_id, $field_map );
			$has_real = false;
			foreach ( $sub as $sk => $sv ) {
				if ( $sk !== 'relation' ) { $has_real = true; break; }
			}
			if ( $has_real ) {
				$out[ $key ] = $sub;
			}
		}
		return $out;
	}
}

/**
 * Render the aggregated dynamic-field accordion for Global Filters Mode.
 *
 * Mirrors the per-category markup from
 * `template-parts/layouts/widgets/sidebar/custom.php` so styling, JS hooks
 * (slider init, color picker), and form submission stay identical between
 * the two modes. The shape of the HTML — accordion item per field, each
 * wrapped in its own `<form>` — is what the AJAX search controller already
 * understands, so no JS changes are needed beyond mode detection.
 */
if ( ! function_exists( 'adforest_render_global_dynamic_fields' ) ) {
	function adforest_render_global_dynamic_fields() {
		global $adforest_theme;

		$fields = adforest_get_all_dynamic_fields();
		if ( empty( $fields ) ) {
			return '';
		}

		$sb_search_page = isset( $adforest_theme['sb_search_page'] ) ? $adforest_theme['sb_search_page'] : 0;
		$sb_search_page = apply_filters( 'adforest_language_page_id', $sb_search_page );
		$action_url     = $sb_search_page ? get_the_permalink( $sb_search_page ) : 'javascript:void(0)';
		$action_url     = apply_filters( 'adforest_category_widget_form_action', $action_url );

		$widget_title = '';

		$html            = '';
		$custom_id_count = 0;

		foreach ( $fields as $r ) {
			if ( empty( $r['types'] ) || empty( $r['titles'] ) || empty( $r['slugs'] ) ) {
				continue;
			}
			$custom_id_count++;
			$custom_id  = 'customField' . $custom_id_count;
			$rand_ids   = wp_rand( 123, 1234567 );
			$open_collapse = ' show';
			$collapsed     = '';

			$field_name = 'custom[' . esc_attr( $r['slugs'] ) . ']';
			$field_val  = ( isset( $_GET['custom'] ) && isset( $_GET['custom'][ esc_attr( $r['slugs'] ) ] ) )
				? $_GET['custom'][ esc_attr( $r['slugs'] ) ]
				: '';

			$html .= '<div class="accordion-item">'
				. '<div class="accordion-header" role="tab" id="heading-' . $rand_ids . '">'
				. '<button class="accordion-button ' . $collapsed . '" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapse' . $custom_id . '" aria-expanded="true" aria-controls="panelsStayOpen-collapse' . $custom_id . '">'
				. esc_html( $widget_title ) . ' ' . esc_html( $r['titles'] )
				. '</button></div>'
				. '<form method="get" action="' . esc_url( $action_url ) . '" class="custom-search-form">'
				. '<div id="panelsStayOpen-collapse' . $custom_id . '" class="panel-collapse collapse' . $open_collapse . '" role="tabpanel" aria-labelledby="heading-' . $rand_ids . '" aria-expanded="true"><div style="padding:15px" class="panel-body"><div class="skin-minimal">';

			$type = (int) $r['types'];

			if ( $type === 1 ) {
				$html .= '<div class="search-widget"><input placeholder="' . esc_attr( $r['titles'] ) . '" name="' . $field_name . '" value="' . esc_attr( $field_val ) . '" type="text"><button type="submit"><i class="fa fa-search"></i></button></div>';
			} elseif ( $type === 2 ) {
				$opts = '';
				if ( ! empty( $r['values'] ) ) {
					$varArrs = explode( '|', $r['values'] );
					$opts   .= '<option value="0">' . esc_html__( 'Select Option', 'adforest' ) . '</option>';
					foreach ( $varArrs as $varArr ) {
						$selected = ( $field_val == $varArr ) ? 'selected="selected"' : '';
						$opts    .= '<option value="' . esc_attr( $varArr ) . '" ' . $selected . '>' . esc_html( $varArr ) . '</option>';
					}
				}
				$html .= '<select name="' . $field_name . '" class="custom-search-select default-select submit_on_select">' . $opts . '</select>';
			} elseif ( $type === 3 ) {
				$opts = '';
				if ( ! empty( $r['values'] ) ) {
					$varArrs = explode( '|', $r['values'] );
					$loop    = 1;
					foreach ( $varArrs as $val ) {
						$checked = '';
						if ( $field_val !== '' ) {
							if ( is_array( $field_val ) ) {
								$checked = in_array( $val, $field_val, true ) ? 'checked="checked"' : '';
							} else {
								$checked = ( $val == $field_val ) ? 'checked="checked"' : '';
							}
						}
						$opts .= '<li><input type="checkbox" class="submit_on_select" id="g-minimal-checkbox-' . $custom_id . '-' . $loop . '" value="' . esc_attr( $val ) . '" ' . $checked . ' name="' . $field_name . '"><label for="g-minimal-checkbox-' . $custom_id . '-' . $loop . '">' . esc_html( $val ) . '</label></li>';
						$loop++;
					}
				}
				$html .= '<div class="skin-minimal"><ul class="list">' . $opts . '</ul></div>';
			} elseif ( $type === 9 ) {
				$opts = '';
				if ( ! empty( $r['values'] ) ) {
					$varArrs = explode( '|', $r['values'] );
					$loop    = 1;
					foreach ( $varArrs as $val ) {
						$checked = '';
						if ( $field_val !== '' ) {
							if ( is_array( $field_val ) ) {
								$checked = in_array( $val, $field_val, true ) ? 'checked="checked"' : '';
							} else {
								$checked = ( $val == $field_val ) ? 'checked="checked"' : '';
							}
						}
						$opts .= '<li><input type="checkbox" id="g-minimal-checkbox-' . $custom_id . '-' . $loop . '" value="' . esc_attr( $val ) . '" ' . $checked . ' name="' . $field_name . '[]"><label for="g-minimal-checkbox-' . $custom_id . '-' . $loop . '">' . esc_html( $val ) . '</label></li>';
						$loop++;
					}
				}
				$html .= '<div class="skin-minimal"><ul class="list">' . $opts . '</ul></div>';
			} elseif ( $type === 4 ) {
				$min_val = ( isset( $_GET['min_custom'] ) && isset( $_GET['min_custom'][ esc_attr( $r['slugs'] ) ] ) )
					? $_GET['min_custom'][ esc_attr( $r['slugs'] ) ] : '';
				$max_val = ( isset( $_GET['max_custom'] ) && isset( $_GET['max_custom'][ esc_attr( $r['slugs'] ) ] ) )
					? $_GET['max_custom'][ esc_attr( $r['slugs'] ) ] : '';
				$btn_cls = 'btn btn-theme btn-sm btn-block';
				$html   .= '<div class="clearfix"></div>'
					. '<div class="search-widget col-md-12 no-padding"><input placeholder="' . esc_attr__( 'From', 'adforest' ) . '" name="min_' . $field_name . '" value="' . esc_attr( $min_val ) . '" type="text" class="dynamic-form-date-fields"><button type="submit" onclick="return false;"><i class="fa fa-calendar"></i></button></div>'
					. '<div class="search-widget col-md-12 no-padding"><input placeholder="' . esc_attr__( 'To', 'adforest' ) . '" name="max_' . $field_name . '" value="' . esc_attr( $max_val ) . '" type="text" class="dynamic-form-date-fields"><button type="submit" onclick="return false;"><i class="fa fa-calendar"></i></button></div>'
					. '<div class="col-md-12 no-padding"><button type="submit" class="' . esc_attr( $btn_cls ) . '"><i class="fa fa-search"></i></button></div>';
			} elseif ( $type === 6 ) {
				if ( function_exists( 'wp_enqueue_script' ) && ! wp_script_is( 'rangeslider-min', 'enqueued' ) ) {
					wp_enqueue_script( 'rangeslider-min' );
				}
				if ( function_exists( 'wp_enqueue_style' ) && ! wp_style_is( 'rangeslider-css', 'enqueued' ) ) {
					wp_enqueue_style( 'rangeslider-css' );
				}
				$varArrs   = explode( '|', $r['values'] );
				$hiddenMin = ( isset( $varArrs[0] ) && (int) $varArrs[0] ) ? $varArrs[0] : 0;
				$hiddenMax = ( isset( $varArrs[1] ) && (int) $varArrs[1] ) ? $varArrs[1] : 1000000;
				$hiddenStp = ( isset( $varArrs[2] ) && (int) $varArrs[2] ) ? $varArrs[2] : 1000;
				$min_val = ( isset( $_GET['min_custom'] ) && isset( $_GET['min_custom'][ esc_attr( $r['slugs'] ) ] ) )
					? $_GET['min_custom'][ esc_attr( $r['slugs'] ) ] : $hiddenMin;
				$max_val = ( isset( $_GET['max_custom'] ) && isset( $_GET['max_custom'][ esc_attr( $r['slugs'] ) ] ) )
					? $_GET['max_custom'][ esc_attr( $r['slugs'] ) ] : $hiddenMax;
				$slider_base = sanitize_html_class( $r['slugs'] ) . '_' . $rand_ids;
				$slider_id   = $slider_base . '_slider';
				$html       .= '<div class="adt-range-slider">'
					. '<span class="price-slider-value"><strong>' . esc_html__( 'Range', 'adforest' ) . ': </strong> '
					. '<span id="' . esc_attr( $slider_base ) . '_min_display">' . esc_html( $min_val ) . '</span> - <span id="' . esc_attr( $slider_base ) . '_max_display">' . esc_html( $max_val ) . '</span> </span>'
					. '<input type="text" class="adt-ads-range-slider" id="' . esc_attr( $slider_id ) . '" data-min="' . esc_attr( $hiddenMin ) . '" data-max="' . esc_attr( $hiddenMax ) . '" data-step="' . esc_attr( $hiddenStp ) . '" data-from="' . esc_attr( $min_val ) . '" data-to="' . esc_attr( $max_val ) . '" data-display-min="#' . esc_attr( $slider_base ) . '_min_display" data-display-max="#' . esc_attr( $slider_base ) . '_max_display" data-input-min="#' . esc_attr( $slider_base ) . '_min" data-input-max="#' . esc_attr( $slider_base ) . '_max" />'
					. '<div class="extra-controls margin-top-10">'
					. '<input type="text" class="form-control adt-range-input-min" name="min_' . $field_name . '" value="' . esc_attr( $min_val ) . '" placeholder="' . esc_attr__( 'min', 'adforest' ) . '" id="' . esc_attr( $slider_base ) . '_min" />'
					. '<div>&#9866;</div>'
					. '<input type="text" class="form-control adt-range-input-max" name="max_' . $field_name . '" value="' . esc_attr( $max_val ) . '" placeholder="' . esc_attr__( 'max', 'adforest' ) . '" id="' . esc_attr( $slider_base ) . '_max" />'
					. '</div>'
					. '<button type="submit" class="btn btn-theme btn-sm btn-block margin-top-10"><i class="fa fa-search"></i> ' . esc_html__( 'Search', 'adforest' ) . '</button>'
					. '</div>';
			} elseif ( $type === 7 ) {
				$opts      = '';
				$colorsCss = '';
				if ( ! empty( $r['values'] ) ) {
					$varArrs   = explode( '|', $r['values'] );
					$rand_name = wp_rand( 1111, 99999 );
					$loop      = 1;
					$loop_count = 1;
					$count_more = ( count( $varArrs ) > 6 ) ? ' more ' : ' no-more ';
					foreach ( $varArrs as $val ) {
						$colors = explode( ':', $val );
						$code   = isset( $colors[0] ) ? $colors[0] : '';
						$name   = isset( $colors[1] ) ? $colors[1] : '';
						if ( $code === '' || $name === '' ) {
							continue;
						}
						$is_checked = ( $field_val == $code ) ? 'checked="checked"' : '';
						$opts      .= '<div class="color-picker__item">'
							. '<input id="g-input-' . $loop_count . '-' . $rand_name . '" type="radio" class="color-picker__input" name="' . $field_name . '" value="' . esc_attr( $code ) . '" ' . $is_checked . ' onclick="this.form.submit()" />'
							. '<label for="g-input-' . $loop_count . '-' . $rand_name . '" class="color-picker__color color-picker__color--' . $loop_count . '-' . $rand_name . ' ' . $count_more . '"></label>'
							. '</div>';
						$colorsCss .= '.color-picker__color--' . $loop_count . '-' . $rand_name . ' { background: ' . $code . '; }';
						$loop_count++;
						$loop++;
					}
				}
				$html .= '<div class="skin-minimal theme-input-colors">' . $opts . '</div><style>' . $colorsCss . '</style>';
			} elseif ( $type === 8 ) {
				$opts = '';
				if ( ! empty( $r['values'] ) ) {
					$varArrs = explode( '|', $r['values'] );
					$loop    = 1;
					foreach ( $varArrs as $val ) {
						$checked = ( $field_val !== '' && $val == $field_val ) ? 'checked="checked"' : '';
						$opts   .= '<li><input type="radio" id="g-minimal-radio-' . $custom_id . '-' . $loop . '" value="' . esc_attr( $val ) . '" ' . $checked . ' name="' . $field_name . '"><label for="g-minimal-radio-' . $custom_id . '-' . $loop . '">' . esc_html( $val ) . '</label></li>';
						$loop++;
					}
				}
				$html .= '<div class="skin-minimal"><ul class="list">' . $opts . '</ul></div>';
			}

			if ( function_exists( 'adforest_search_params' ) ) {
				$html .= adforest_search_params( $field_name );
			}
			$html .= '</div></div></div></div></form>';
		}

		return $html;
	}
}

/**
 * AJAX: return the dynamic custom-field filter markup.
 *
 * Two modes:
 *   - `cat_id=X`               → render fields scoped to that category
 *     (legacy / category_based mode)
 *   - `mode=global`            → render the deduplicated all-categories
 *     accordion produced by `adforest_render_global_dynamic_fields()`
 *
 * The two branches share one endpoint to keep nonce + JS plumbing simple.
 */
if ( ! function_exists( 'adforest_ajax_category_fields_callback' ) ) {
	function adforest_ajax_category_fields_callback() {
		check_ajax_referer( 'adforest_ajax_search_nonce', 'security' );

		$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( (string) $_POST['mode'] ) ) : '';
		$cat_id = isset( $_POST['cat_id'] ) ? (int) $_POST['cat_id'] : 0;

		$payload = array( 'html' => '', 'cat_id' => $cat_id, 'mode' => $mode );

		if ( $mode === 'global' ) {
			$payload['html'] = adforest_render_global_dynamic_fields();
			$payload         = apply_filters( 'adforest_ajax_global_fields_response', $payload );
			wp_send_json_success( $payload );
			return;
		}

		if ( $cat_id > 0 ) {
			$get_backup     = $_GET;
			$_GET['cat_id'] = $cat_id;
			$instance       = array( 'open_widget' => 1, 'title' => '' );

			// locate_template prefers a child-theme override when present,
			// so custom themes can ship their own dynamic-field markup.
			$template_path = locate_template( 'template-parts/layouts/widgets/sidebar/custom.php' );
			if ( ! $template_path ) {
				$template_path = get_template_directory() . '/template-parts/layouts/widgets/sidebar/custom.php';
			}

			// The widget template builds its output into a `$customHTML`
			// string (and into `$customHTML` only — it never echoes).
			// The widget class that normally renders this template echoes
			// the string itself after the require; the AJAX path has to
			// mirror that contract or the response html is empty.
			// `$instance` and `$term_id` are also referenced inside the
			// template, so seed them with safe defaults before include.
			$customHTML = '';
			$term_id    = 0;
			ob_start();
			if ( file_exists( $template_path ) ) {
				include $template_path;
			}
			echo $customHTML;
			$payload['html'] = trim( ob_get_clean() );

			$_GET = $get_backup;
		}

		$payload = apply_filters( 'adforest_ajax_category_fields_response', $payload, $cat_id );
		wp_send_json_success( $payload );
	}
	add_action( 'wp_ajax_adforest_ajax_cat_fields', 'adforest_ajax_category_fields_callback' );
	add_action( 'wp_ajax_nopriv_adforest_ajax_cat_fields', 'adforest_ajax_category_fields_callback' );
}

/**
 * Bust the global fields cache whenever a category or any term meta we
 * read from changes. Cheap enough to wire to broad term hooks — the
 * regenerate cost is one query + a per-template walk, paid lazily on the
 * next read.
 */
if ( ! function_exists( 'adforest_clear_global_fields_cache' ) ) {
	function adforest_clear_global_fields_cache() {
		delete_transient( 'adforest_global_fields_cache' );
		delete_transient( 'adforest_global_field_map_cache' );
	}
	add_action( 'created_ad_cats', 'adforest_clear_global_fields_cache' );
	add_action( 'edited_ad_cats',  'adforest_clear_global_fields_cache' );
	add_action( 'delete_ad_cats',  'adforest_clear_global_fields_cache' );
	// term meta hooks fire for every taxonomy; filter by meta_key so we
	// only invalidate when the keys we depend on actually change.
	add_action( 'updated_term_meta', 'adforest_maybe_clear_global_fields_cache_on_meta', 10, 4 );
	add_action( 'added_term_meta',   'adforest_maybe_clear_global_fields_cache_on_meta', 10, 4 );
	add_action( 'deleted_term_meta', 'adforest_maybe_clear_global_fields_cache_on_meta', 10, 4 );
}
if ( ! function_exists( 'adforest_maybe_clear_global_fields_cache_on_meta' ) ) {
	function adforest_maybe_clear_global_fields_cache_on_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( $meta_key === '_sb_dynamic_form_fields' || $meta_key === '_sb_category_template' ) {
			delete_transient( 'adforest_global_fields_cache' );
			delete_transient( 'adforest_global_field_map_cache' );
		}
	}
}

/**
 * Enqueue the frontend AJAX search bundle on relevant pages.
 */
if ( ! function_exists( 'adforest_ajax_search_enqueue' ) ) {
	function adforest_ajax_search_enqueue() {
		global $adforest_theme;

		$search_page_id = isset( $adforest_theme['sb_search_page'] ) ? (int) $adforest_theme['sb_search_page'] : 0;
		$should         = is_page_template( 'page-search.php' )
			|| ( $search_page_id && is_page( $search_page_id ) )
			|| is_tax( 'ad_cats' )
			|| is_tax( 'ad_country' );

		// Author archive (user profile) renders the same Search 2.0 card
		// templates when the grid layout is set to one of the new slugs.
		// Without the card stylesheet the markup falls back to unstyled
		// images that overflow the column. Enqueue just the CSS — the
		// search-ajax/search-ux JS bundles need the search-page markup
		// to work and would no-op (or error) on the author page.
		if ( is_author() ) {
			wp_enqueue_style(
				'adforest-search-ui',
				trailingslashit( get_template_directory_uri() ) . 'assets/css/search-ui.css',
				array(),
				defined( 'ADFOREST_VERSION' ) ? ADFOREST_VERSION : '1.0.0'
			);
		}

		$should         = apply_filters( 'adforest_ajax_search_should_enqueue', $should );
		if ( ! $should ) {
			return;
		}

		$handle = 'adforest-search-ajax';
		wp_enqueue_script(
			$handle,
			trailingslashit( get_template_directory_uri() ) . 'assets/js/search-ajax.js',
			array( 'jquery' ),
			defined( 'ADFOREST_VERSION' ) ? ADFOREST_VERSION : '1.0.0',
			true
		);

		// Search 2.0 Part 3 — modern card styles. Loaded on every search
		// page (cheap, ~6kB) so admins can switch Grid Type from the
		// theme options without a separate CSS toggle.
		wp_enqueue_style(
			'adforest-search-ui',
			trailingslashit( get_template_directory_uri() ) . 'assets/css/search-ui.css',
			array(),
			defined( 'ADFOREST_VERSION' ) ? ADFOREST_VERSION : '1.0.0'
		);

		// Search 2.0 Part 9 — Filter UX layer (chips, clear-all, sidebar
		// visual feedback, mobile drawer). Depends on search-ajax.js
		// because it reads `adforestAjaxSearchApi` and listens for the
		// `adforest:search:rendered` event.
		wp_enqueue_script(
			'adforest-search-ux',
			trailingslashit( get_template_directory_uri() ) . 'assets/js/search-ux.js',
			array( 'jquery', $handle ),
			defined( 'ADFOREST_VERSION' ) ? ADFOREST_VERSION : '1.0.0',
			true
		);

		// Resolve the saved filter mode with defense-in-depth so JS always
		// receives an authoritative value even if the Redux global wasn't
		// populated yet at this point in the boot order. Read order:
		//   1. $adforest_theme global (Redux's runtime cache)
		//   2. get_option('adforest_theme') direct (DB source of truth)
		//   3. 'category_based' default
		$mode_raw = '';
		if ( isset( $adforest_theme['sb_search_filter_mode'] ) && $adforest_theme['sb_search_filter_mode'] !== '' ) {
			$mode_raw = (string) $adforest_theme['sb_search_filter_mode'];
		} else {
			$opts = get_option( 'adforest_theme' );
			if ( is_array( $opts ) && isset( $opts['sb_search_filter_mode'] ) && $opts['sb_search_filter_mode'] !== '' ) {
				$mode_raw = (string) $opts['sb_search_filter_mode'];
			}
		}
		$filter_mode = $mode_raw !== '' ? $mode_raw : 'category_based';
		// Legacy 'category' (pre-rename) → 'category_based'. Never coerces
		// 'global' or 'category_based'; only the bare legacy string.
		if ( $filter_mode === 'category' ) {
			$filter_mode = 'category_based';
		}
		// Final whitelist guard — if anything unexpected made it through,
		// fall back to the safe default rather than passing junk to JS.
		if ( ! in_array( $filter_mode, array( 'category_based', 'global' ), true ) ) {
			$filter_mode = 'category_based';
		}

		$debounce = isset( $adforest_theme['sb_search_debounce_ms'] ) ? (int) $adforest_theme['sb_search_debounce_ms'] : 400;

		// Debug toggle — only emits a console log on the frontend when
		// `?adforest_debug=1` is present, so production pages stay quiet.
		$debug = ! empty( $_GET['adforest_debug'] );

		wp_localize_script(
			$handle,
			'adforestAjaxSearch',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'adforest_ajax_search_nonce' ),
				'filterMode'    => $filter_mode,
				'rawMode'       => $mode_raw, // unfiltered value — debug only
				'debug'         => (bool) $debug,
				'debounce'      => max( 100, (int) $debounce ),
				'searchPageUrl' => $search_page_id ? get_permalink( $search_page_id ) : '',
				'i18n'          => array(
					'loading'   => esc_html__( 'Loading...', 'adforest' ),
					'noResults' => esc_html__( 'No results found.', 'adforest' ),
					'adsFound'  => esc_html__( 'Ad(s) Found:', 'adforest' ),
					'reset'     => esc_html__( 'Reset Filters', 'adforest' ),
					'error'     => esc_html__( 'Something went wrong while loading results. Please try again.', 'adforest' ),
					'retry'     => esc_html__( 'Retry', 'adforest' ),
					'reload'    => esc_html__( 'Reload page', 'adforest' ),
				),
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'adforest_ajax_search_enqueue', 20 );
}

/**
 * Helper: emit the loader + reset markup inside the sidebar. Templates call
 * this to get a consistent reset button + spinner without duplicating HTML.
 */
if ( ! function_exists( 'adforest_ajax_search_toolbar' ) ) {
	function adforest_ajax_search_toolbar() {
		?>
		<div class="adforest-ajax-toolbar" data-adforest-toolbar>
			<button type="button"
					class="adforest-ajax-reset btn btn-sm btn-outline-secondary"
					data-adforest-reset>
				<i class="fa fa-undo" aria-hidden="true"></i>
				<?php echo esc_html__( 'Reset Filters', 'adforest' ); ?>
			</button>
			<span class="adforest-ajax-spinner" data-adforest-spinner aria-hidden="true" style="display:none;">
				<i class="fa fa-circle-notch fa-spin"></i>
			</span>
		</div>
		<?php
	}
}

/**
 * Inject minimal CSS needed for the AJAX loader / sidebar disabled state.
 * Kept inline to avoid an extra stylesheet and to guarantee load order.
 */
if ( ! function_exists( 'adforest_ajax_search_inline_css' ) ) {
	function adforest_ajax_search_inline_css() {
		$css = '.adforest-ajax-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px;}'
			. '.adforest-ajax-spinner{display:inline-block;font-size:16px;}'
			. '.adforest-ajax-spinner.is-visible{display:inline-block!important;}'
			. '.adforest-ajax-loading{opacity:.6;pointer-events:none;transition:opacity .15s ease-in-out;}'
			. '#adforest-ajax-results.adforest-ajax-loading{position:relative;}'
			. '#adforest-ajax-results.adforest-ajax-loading::after{content:"";position:absolute;inset:0;background:rgba(255,255,255,0.35);z-index:2;}';
		wp_register_style( 'adforest-search-ajax-inline', false );
		wp_enqueue_style( 'adforest-search-ajax-inline' );
		wp_add_inline_style( 'adforest-search-ajax-inline', $css );
	}
	add_action( 'wp_enqueue_scripts', 'adforest_ajax_search_inline_css', 25 );
}
