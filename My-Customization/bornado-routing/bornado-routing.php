<?php
/**
 * Plugin Name: Bornado SEO Routing
 * Description: Semantic SEO routing and canonical handling for Bornado on AdForest multisite installs.
 * Version: 1.1.0
 * Author: Bornado
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-seo-landing-manager.php';
require_once __DIR__ . '/includes/class-country-model.php';

final class Bornado_SEO_Routing {

	const OPTION_REWRITE_VERSION = 'bornado_seo_routing_rewrite_version';
	const REWRITE_VERSION        = '2.1.0';
	const QUERY_ROUTE           = 'bornado_seo_route';
	const QUERY_ROUTE_PATH      = 'bornado_seo_path';

	/**
	 * Runtime route context.
	 *
	 * @var array<string,mixed>
	 */
	private static $context = array();

	/**
	 * Cache for resolve_semantic_route() results keyed by route path.
	 * Prevents the same DB queries running 2-3 times per request.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private static $route_cache = array();

	/**
	 * Cache for get_adforest_search_page_id().
	 *
	 * @var int|null
	 */
	private static $search_page_id_cache = null;

	/**
	 * Bootstrap hooks.
	 *
	 * @return void
	 */
	public static function init() {
		if ( class_exists( 'Bornado_SEO_Landing_Manager' ) ) {
			Bornado_SEO_Landing_Manager::init();
		}

		if ( class_exists( 'Bornado_Country_Model' ) ) {
			Bornado_Country_Model::init();
		}

		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 20 );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_filter( 'request', array( __CLASS__, 'filter_request_vars' ), 1 );
		add_action( 'parse_request', array( __CLASS__, 'capture_route_context' ) );
		add_filter( 'pre_handle_404', array( __CLASS__, 'filter_pre_handle_404' ), 10, 2 );
		add_action( 'send_headers', array( __CLASS__, 'maybe_send_debug_headers' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_noncanonical_request' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'inject_internal_search_state' ), 5 );
		add_filter( 'template_include', array( __CLASS__, 'filter_template_include' ), 99 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'filter_redirect_canonical' ), 10, 2 );
		add_filter( 'term_link', array( __CLASS__, 'filter_term_link' ), 10, 3 );
		add_filter( 'adforest_page_lang_url', array( __CLASS__, 'filter_adforest_page_lang_url' ) );
		add_filter( 'adforest_category_widget_form_action', array( __CLASS__, 'filter_search_form_action' ), 10, 2 );
		add_filter( 'adforest_filter_taxonomy_popup_actions', array( __CLASS__, 'filter_taxonomy_popup_action' ), 10, 3 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ) );
		add_filter( 'document_title_parts', array( __CLASS__, 'filter_document_title_parts' ) );
		add_filter( 'wpseo_canonical', array( __CLASS__, 'filter_external_canonical' ) );
		add_filter( 'wpseo_robots', array( __CLASS__, 'filter_wpseo_robots' ) );
		add_filter( 'rank_math/frontend/canonical', array( __CLASS__, 'filter_external_canonical' ) );
		add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'filter_rank_math_robots' ) );
		add_filter( 'aioseo_canonical_url', array( __CLASS__, 'filter_external_canonical' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_canonical_tag' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'print_tag_search_form_fix' ), 100 );
	}

	/**
	 * Flush rules on activation for regular plugin installs.
	 *
	 * @return void
	 */
	public static function activate() {
		self::register_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( self::OPTION_REWRITE_VERSION, self::REWRITE_VERSION, false );
	}

	/**
	 * Register custom query vars.
	 *
	 * @param string[] $vars Query vars.
	 * @return string[]
	 */
	public static function register_query_vars( $vars ) {
		$vars[] = self::QUERY_ROUTE;
		$vars[] = self::QUERY_ROUTE_PATH;

		return $vars;
	}

	/**
	 * Hijack semantic routes before WordPress resolves them to attachments, pages, or 404s.
	 *
	 * @param array<string,mixed> $query_vars Parsed request vars.
	 * @return array<string,mixed>
	 */
	public static function filter_request_vars( $query_vars ) {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $query_vars;
		}

		$route_path = self::get_current_request_path();
		if ( '' === $route_path ) {
			return $query_vars;
		}

		$resolved = self::resolve_semantic_route( $route_path );
		if ( empty( $resolved['is_valid'] ) ) {
			return $query_vars;
		}

		$resolved = self::hydrate_route_context( $resolved );

		// Always persist context for valid routes so that filter_pre_handle_404,
		// maybe_send_debug_headers, and other downstream hooks know this is a
		// recognised SEO route — even when get_bound_query_vars returns empty
		// (e.g. AdForest search page ID is not configured yet).
		self::$context = $resolved;

		$bound_query_vars = self::get_bound_query_vars( $resolved );
		if ( empty( $bound_query_vars ) ) {
			return $query_vars;
		}

		// Prevent WordPress from falling back to an attachment post whose slug
		// happens to match a category or country segment in this route.  Without
		// this, uploading e.g. vehicles.svg creates an attachment with
		// slug="vehicles" that WordPress resolves before our semantic route can
		// take over, causing a 301 redirect to the media-file URL instead of
		// showing the category archive.
		unset(
			$query_vars['attachment'],
			$query_vars['attachment_id'],
			$query_vars['name'],
			$query_vars['pagename'],
			$query_vars['error']
		);

		return $bound_query_vars;
	}

	/**
	 * Add a bottom-priority catch-all route for semantic archives.
	 *
	 * Existing pages/posts/taxonomies keep precedence because these rules are added at the bottom.
	 *
	 * @return void
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule(
			'^(.+?)/page/([0-9]{1,})/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_ROUTE_PATH . '=$matches[1]&paged=$matches[2]',
			'bottom'
		);
		add_rewrite_rule(
			'^(.+?)/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_ROUTE_PATH . '=$matches[1]',
			'bottom'
		);
	}

	/**
	 * One-time rewrite flush for MU-style deployments.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( wp_installing() ) {
			return;
		}

		if ( self::REWRITE_VERSION === get_option( self::OPTION_REWRITE_VERSION ) ) {
			return;
		}

		self::register_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( self::OPTION_REWRITE_VERSION, self::REWRITE_VERSION, false );
	}

	/**
	 * Resolve the requested semantic route into concrete terms.
	 *
	 * @param WP $wp WordPress request object.
	 * @return void
	 */
	public static function capture_route_context( $wp ) {
		$route_path = '';
		if ( ! empty( $wp->query_vars[ self::QUERY_ROUTE ] ) ) {
			$route_path = isset( $wp->query_vars[ self::QUERY_ROUTE_PATH ] ) ? self::normalize_route_path( (string) $wp->query_vars[ self::QUERY_ROUTE_PATH ] ) : '';
		} elseif ( self::should_try_fallback_route( $wp ) ) {
			$route_path = self::normalize_route_path( (string) $wp->request );
		} else {
			return;
		}

		if ( '' === $route_path ) {
			self::$context = array(
				'is_seo_route' => true,
				'is_valid'     => false,
			);
			return;
		}

		$resolved = self::resolve_semantic_route( $route_path );
		if ( empty( $resolved['is_valid'] ) ) {
			self::$context = $resolved;
			return;
		}

		$resolved = self::hydrate_route_context( $resolved );

		if ( empty( $wp->query_vars[ self::QUERY_ROUTE ] ) ) {
			$wp->query_vars[ self::QUERY_ROUTE ]      = 1;
			$wp->query_vars[ self::QUERY_ROUTE_PATH ] = $route_path;
			unset( $wp->query_vars['error'], $wp->query_vars['pagename'], $wp->query_vars['name'], $wp->query_vars['attachment'], $wp->query_vars['attachment_id'] );
		}

		$bound_query_vars = self::get_bound_query_vars( $resolved );
		if ( ! empty( $bound_query_vars ) ) {
			$wp->query_vars = array_merge( $wp->query_vars, $bound_query_vars );
			unset( $wp->query_vars['error'], $wp->query_vars['pagename'], $wp->query_vars['name'] );
		}

		self::$context = $resolved;
	}

	/**
	 * Prevent WordPress from forcing a 404 for valid semantic routes.
	 *
	 * @param bool     $preempt  Pre-handle value.
	 * @param WP_Query $wp_query Main query instance.
	 * @return bool
	 */
	public static function filter_pre_handle_404( $preempt, $wp_query ) {
		if ( ! ( $wp_query instanceof WP_Query ) ) {
			return $preempt;
		}

		// Case 1: route was not recognised at request-parse time — try a late recovery.
		if ( ! self::is_current_seo_route() && $wp_query->is_404() ) {
			self::attempt_late_route_recovery( $wp_query );
		}

		if ( ! self::is_current_seo_route() || empty( self::$context['is_valid'] ) ) {
			return $preempt;
		}

		// Case 2: context is valid but get_bound_query_vars returned empty earlier
		// (most likely because get_adforest_search_page_id() returned 0 at request
		// time).  Try once more now that all plugins are fully loaded.
		$bound_post = self::get_bound_post( self::$context );
		if ( ! $bound_post instanceof WP_Post ) {
			// Still no bound post — nothing we can do, let WordPress 404.
			return $preempt;
		}

		// Wire the bound post into the query so the theme can render it.
		$wp_query->queried_object_id = $bound_post->ID;
		$wp_query->queried_object    = $bound_post;
		$wp_query->posts             = array( $bound_post );
		$wp_query->post              = $bound_post;
		$wp_query->found_posts       = 1;
		$wp_query->post_count        = 1;
		$wp_query->max_num_pages     = 1;

		if ( ! empty( self::$context['landing_post'] ) && self::$context['landing_post'] instanceof WP_Post ) {
			$wp_query->query_vars['post_type'] = self::$context['landing_post']->post_type;
			$wp_query->query_vars['p']         = self::$context['landing_post']->ID;
		} else {
			$wp_query->query_vars['page_id'] = $bound_post->ID;
		}

		$wp_query->is_404      = false;
		$wp_query->is_singular = true;
		if ( ! empty( self::$context['landing_post'] ) && self::$context['landing_post'] instanceof WP_Post ) {
			$wp_query->is_single = true;
			$wp_query->is_page   = false;
		} else {
			$wp_query->is_page   = true;
			$wp_query->is_single = false;
		}
		status_header( 200 );

		return true;
	}

	/**
	 * Emit lightweight debug headers when explicitly requested.
	 *
	 * Usage: append `?bornado_debug_route=1` to a URL and inspect the response headers.
	 *
	 * @return void
	 */
	public static function maybe_send_debug_headers() {
		if ( empty( $_GET['bornado_debug_route'] ) || '1' !== $_GET['bornado_debug_route'] ) {
			return;
		}

		$status = 'none';
		if ( self::is_current_seo_route() ) {
			$status = ! empty( self::$context['is_valid'] ) ? 'valid' : 'invalid';
		}

		header( 'X-Bornado-Route-Status: ' . $status );
		header( 'X-Bornado-Route-Mode: ' . ( ! empty( self::$context['route_mode'] ) ? self::$context['route_mode'] : 'none' ) );
		header( 'X-Bornado-Route-Path: ' . ( ! empty( self::$context['segments'] ) ? implode( '/', (array) self::$context['segments'] ) : 'none' ) );
		header( 'X-Bornado-Route-City: ' . ( ! empty( self::$context['city_term'] ) && self::$context['city_term'] instanceof WP_Term ? self::$context['city_term']->slug : 'none' ) );
		header( 'X-Bornado-Route-Category: ' . ( ! empty( self::$context['deepest_term'] ) && self::$context['deepest_term'] instanceof WP_Term ? self::$context['deepest_term']->slug : 'none' ) );
		header( 'X-Bornado-Landing-Id: ' . ( ! empty( self::$context['landing_post'] ) && self::$context['landing_post'] instanceof WP_Post ? (string) self::$context['landing_post']->ID : 'none' ) );
	}

	/**
	 * Redirect legacy or query-polluted URLs to a single canonical route.
	 *
	 * @return void
	 */
	public static function maybe_redirect_noncanonical_request() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! self::is_current_seo_route() && class_exists( 'Bornado_SEO_Landing_Manager' ) && is_singular( Bornado_SEO_Landing_Manager::POST_TYPE ) && ! is_preview() ) {
			$native_target = Bornado_SEO_Landing_Manager::get_public_route_url( get_queried_object_id() );
			if ( $native_target && ! self::urls_match( self::current_request_url(), $native_target ) ) {
				wp_safe_redirect( $native_target, 301 );
				exit;
			}
		}

		$target = '';

		if ( self::is_current_seo_route() ) {
			$target = self::build_clean_current_route_target();
		} else {
			$target = self::build_legacy_redirect_target();
		}

		if ( ! $target ) {
			return;
		}

		if ( self::urls_match( self::current_request_url(), $target ) ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Inject AdForest-compatible state so the theme can keep using its current query builder.
	 *
	 * @return void
	 */
	public static function inject_internal_search_state() {
		if ( ! self::is_current_seo_route() ) {
			return;
		}

		if ( empty( self::$context['is_valid'] ) ) {
			global $wp_query;
			if ( $wp_query instanceof WP_Query ) {
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();
			}
			return;
		}

		$state = self::get_request_query_args();

		if ( ! empty( self::$context['city_term'] ) && self::$context['city_term'] instanceof WP_Term ) {
			$state['country_id'] = (int) self::$context['city_term']->term_id;
		} elseif ( ! empty( self::$context['country_term'] ) && self::$context['country_term'] instanceof WP_Term ) {
			$state['country_id'] = (int) self::$context['country_term']->term_id;
		} else {
			unset( $state['country_id'] );
		}

		if ( ! empty( self::$context['deepest_term'] ) ) {
			$state['cat_id'] = (int) self::$context['deepest_term']->term_id;
		} else {
			unset( $state['cat_id'] );
		}

		foreach ( $state as $key => $value ) {
			$_GET[ $key ]     = $value;
			$_REQUEST[ $key ] = $value;
		}

		// AdForest re-creates hidden fields from the raw query string.
		// Keep non-structural filters there, but avoid exposing route-derived city/category
		// as removable tags because they already live in the semantic path itself.
		$public_state                   = self::get_public_query_state( $state );
		$_SERVER['QUERY_STRING']        = http_build_query( $public_state, '', '&', PHP_QUERY_RFC3986 );
		self::$context['state_query']   = $state;
		self::$context['public_query']  = $public_state;
	}

	/**
	 * Serve the theme search template for valid semantic routes and 404 template for invalid ones.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public static function filter_template_include( $template ) {
		if ( ! self::is_current_seo_route() ) {
			return $template;
		}

		if ( empty( self::$context['is_valid'] ) ) {
			$not_found_template = get_query_template( '404' );
			return $not_found_template ? $not_found_template : $template;
		}

		if ( ! empty( self::$context['landing_post'] ) && self::$context['landing_post'] instanceof WP_Post ) {
			$landing_template = __DIR__ . '/templates/seo-landing.php';
			if ( file_exists( $landing_template ) ) {
				return $landing_template;
			}
		}

		$search_template = locate_template( array( 'page-search.php' ) );
		return $search_template ? $search_template : $template;
	}

	/**
	 * Prevent WordPress canonical logic from fighting custom semantic routes.
	 *
	 * @param string|false $redirect_url Redirect target.
	 * @param string       $requested_url Requested URL.
	 * @return string|false
	 */
	public static function filter_redirect_canonical( $redirect_url, $requested_url ) {
		if ( self::is_current_seo_route() ) {
			return false;
		}

		if ( self::build_legacy_redirect_target() ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Convert taxonomy term links into semantic URLs when enough context exists.
	 *
	 * @param string  $url      Existing URL.
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy name.
	 * @return string
	 */
	public static function filter_term_link( $url, $term, $taxonomy ) {
		if ( ! $term instanceof WP_Term ) {
			return $url;
		}

		if ( 'ad_country' === $taxonomy ) {
			$route_terms  = self::split_location_term_for_route( $term );
			$semantic_url = self::build_semantic_url(
				$route_terms['country_term'] instanceof WP_Term ? (int) $route_terms['country_term']->term_id : 0,
				$route_terms['city_term'] instanceof WP_Term ? (int) $route_terms['city_term']->term_id : 0
			);
			return $semantic_url ? $semantic_url : $url;
		}

		if ( 'ad_cats' !== $taxonomy ) {
			return $url;
		}

		$active_country = self::get_active_country_term();
		$active_city    = self::get_active_city_term();
		$semantic_url   = self::build_semantic_url(
			$active_country ? (int) $active_country->term_id : 0,
			$active_city ? (int) $active_city->term_id : 0,
			(int) $term->term_id
		);
		return $semantic_url ? $semantic_url : $url;
	}

	/**
	 * Convert AdForest-generated query links to the semantic form when possible.
	 *
	 * @param string $url Existing URL.
	 * @return string
	 */
	public static function filter_adforest_page_lang_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}

		$parsed_url = wp_parse_url( $url );
		if ( empty( $parsed_url['query'] ) ) {
			return $url;
		}

		parse_str( $parsed_url['query'], $args );

		$location_id = isset( $args['country_id'] ) ? (int) $args['country_id'] : 0;
		$cat_id      = self::get_selected_category_id_from_args( $args );
		$paged      = 1;

		if ( isset( $args['paged'] ) && (int) $args['paged'] > 1 ) {
			$paged = (int) $args['paged'];
		} elseif ( isset( $args['page'] ) && (int) $args['page'] > 1 ) {
			$paged = (int) $args['page'];
		}

		$route_terms = self::get_route_terms_from_location_id( $location_id );
		if ( ! ( $route_terms['country_term'] instanceof WP_Term ) ) {
			$route_terms['country_term'] = self::get_active_country_term();
		}
		if ( ! ( $route_terms['city_term'] instanceof WP_Term ) ) {
			$route_terms['city_term'] = self::get_active_city_term();
		}

		if ( ! ( $route_terms['country_term'] instanceof WP_Term ) && ! $cat_id ) {
			return $url;
		}

		unset(
			$args['country_id'],
			$args['cat_id'],
			$args['paged'],
			$args['page'],
			$args['ad_cat_sub'],
			$args['ad_cat_sub_sub'],
			$args['ad_cat_sub_sub_sub'],
			$args['ad_cat_sub_sub_sub_sub']
		);

		$semantic_url = self::build_semantic_url(
			$route_terms['country_term'] instanceof WP_Term ? (int) $route_terms['country_term']->term_id : 0,
			$route_terms['city_term'] instanceof WP_Term ? (int) $route_terms['city_term']->term_id : 0,
			$cat_id,
			$paged,
			$args
		);

		return $semantic_url ? $semantic_url : $url;
	}

	/**
	 * Keep all AdForest filter forms anchored to the semantic route base when available.
	 *
	 * @param string $url Existing action URL.
	 * @param string $context Optional form context.
	 * @return string
	 */
	public static function filter_search_form_action( $url, $context = '' ) {
		if ( ! self::is_current_seo_route() || empty( self::$context['is_valid'] ) ) {
			return $url;
		}

		$canonical = self::build_semantic_url(
			! empty( self::$context['country_term'] ) && self::$context['country_term'] instanceof WP_Term ? (int) self::$context['country_term']->term_id : 0,
			! empty( self::$context['city_term'] ) && self::$context['city_term'] instanceof WP_Term ? (int) self::$context['city_term']->term_id : 0,
			! empty( self::$context['deepest_term'] ) && self::$context['deepest_term'] instanceof WP_Term ? (int) self::$context['deepest_term']->term_id : 0
		);

		return $canonical ? $canonical : $url;
	}

	/**
	 * Provide direct semantic links for AdForest's category popup when city context is known.
	 *
	 * @param string $url Existing URL.
	 * @param int    $term_id Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	public static function filter_taxonomy_popup_action( $url, $term_id, $taxonomy ) {
		if ( 'ad_cats' !== $taxonomy ) {
			return $url;
		}

		$active_country = self::get_active_country_term();
		$active_city    = self::get_active_city_term();
		$semantic_url   = self::build_semantic_url(
			$active_country ? (int) $active_country->term_id : 0,
			$active_city ? (int) $active_city->term_id : 0,
			(int) $term_id
		);
		return $semantic_url ? $semantic_url : $url;
	}

	/**
	 * Mark non-canonical filter states as noindex while keeping them crawlable.
	 *
	 * @param array<string,mixed> $robots Robots directives.
	 * @return array<string,mixed>
	 */
	public static function filter_wp_robots( $robots ) {
		if ( self::should_noindex_request() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
		}

		return $robots;
	}

	/**
	 * Make the HTML title reflect the semantic route.
	 *
	 * @param array<string,string> $parts Title parts.
	 * @return array<string,string>
	 */
	public static function filter_document_title_parts( $parts ) {
		if ( ! self::is_current_seo_route() || empty( self::$context['is_valid'] ) ) {
			return $parts;
		}

		if ( ! empty( self::$context['landing_post'] ) && self::$context['landing_post'] instanceof WP_Post ) {
			return $parts;
		}

		$title_parts   = array();
		if ( ! empty( self::$context['country_term'] ) && self::$context['country_term'] instanceof WP_Term ) {
			$title_parts[] = self::$context['country_term']->name;
		}
		if ( ! empty( self::$context['city_term'] ) ) {
			$title_parts[] = self::$context['city_term']->name;
		}
		$category_path = ! empty( self::$context['category_terms'] ) ? self::$context['category_terms'] : array();

		foreach ( $category_path as $term ) {
			if ( $term instanceof WP_Term ) {
				$title_parts[] = $term->name;
			}
		}

		$parts['title'] = implode( ' | ', array_reverse( $title_parts ) );

		return $parts;
	}

	/**
	 * Return the canonical URL for SEO plugins when available.
	 *
	 * @param string $canonical Existing canonical URL.
	 * @return string
	 */
	public static function filter_external_canonical( $canonical ) {
		$route_canonical = self::get_canonical_url_for_current_request();
		return $route_canonical ? $route_canonical : $canonical;
	}

	/**
	 * Keep Yoast robots aligned with semantic route indexing policy.
	 *
	 * @param string $robots Existing robots directives.
	 * @return string
	 */
	public static function filter_wpseo_robots( $robots ) {
		if ( self::should_noindex_request() ) {
			return 'noindex,follow';
		}

		return $robots;
	}

	/**
	 * Keep Rank Math robots aligned with semantic route indexing policy.
	 *
	 * @param array<string,string> $robots Existing robots directives.
	 * @return array<string,string>
	 */
	public static function filter_rank_math_robots( $robots ) {
		if ( self::should_noindex_request() ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'follow';
		}

		return $robots;
	}

	/**
	 * Print a canonical tag when the site is not using an SEO plugin filter.
	 *
	 * @return void
	 */
	public static function print_canonical_tag() {
		if ( self::has_external_canonical_provider() ) {
			return;
		}

		$canonical = self::get_canonical_url_for_current_request();
		if ( ! $canonical ) {
			return;
		}

		printf(
			"<link rel=\"canonical\" href=\"%s\" />\n",
			esc_url( $canonical )
		);
	}

	/**
	 * Prevent AdForest tag-removal forms from submitting an empty GET query like `?`.
	 *
	 * The theme uses GET forms for removable search tags. When the last tag is removed,
	 * the browser submits the form with no controls and appends a dangling `?` to the URL.
	 * Fix it here without touching theme files by redirecting the empty submission directly
	 * to the clean form action on the client side.
	 *
	 * @return void
	 */
	public static function print_tag_search_form_fix() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		?>
		<script>
		(function($){
			if (!$ || typeof $.fn === 'undefined') {
				return;
			}

			$(document).on('submit', '.tag-search form[method="get"]', function(e){
				var form = this;
				var $form = $(form);
				var hasPayload = false;

				$form.find('input, select, textarea').each(function(){
					var field = this;
					var type = String(field.type || '').toLowerCase();
					var value;

					if (field.disabled || !field.name) {
						return;
					}

					if ((type === 'checkbox' || type === 'radio') && !field.checked) {
						return;
					}

					value = $(field).val();

					if (Array.isArray(value) && value.length) {
						hasPayload = true;
						return false;
					}

					if (value !== null && String(value) !== '') {
						hasPayload = true;
						return false;
					}
				});

				if (hasPayload) {
					return;
				}

				e.preventDefault();
				window.location.assign(String(form.getAttribute('action') || window.location.href).replace(/\?+$/, ''));
			});
		})(window.jQuery);
		</script>
		<?php
	}

	/**
	 * Whether the current request is a semantic archive handled by this plugin.
	 *
	 * @return bool
	 */
	private static function is_current_seo_route() {
		return ! empty( self::$context['is_seo_route'] );
	}

	/**
	 * Public wrapper used by admin UI to preview semantic routes.
	 *
	 * @param int $country_id Country term ID.
	 * @param int $city_id City term ID.
	 * @param int $cat_id Category term ID.
	 * @return string
	 */
	public static function get_semantic_url_preview( $country_id, $city_id = 0, $cat_id = 0 ) {
		return self::build_semantic_url( (int) $country_id, (int) $city_id, (int) $cat_id );
	}

	/**
	 * Whether the current frontend request is a valid semantic route.
	 *
	 * @return bool
	 */
	public static function is_valid_semantic_route() {
		return self::is_current_seo_route() && ! empty( self::$context['is_valid'] );
	}

	/**
	 * Return a readonly snapshot of the current resolved route.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_public_route_context() {
		$context = array(
			'is_seo_route'   => self::is_current_seo_route(),
			'is_valid'       => ! empty( self::$context['is_valid'] ),
			'route_mode'     => '',
			'paged'          => 1,
			'canonical_url'  => '',
			'country_term'   => null,
			'city_term'      => null,
			'category_terms' => array(),
			'deepest_term'   => null,
			'landing_post'   => null,
		);

		if ( empty( self::$context ) ) {
			return $context;
		}

		$context['route_mode']    = ! empty( self::$context['route_mode'] ) ? (string) self::$context['route_mode'] : '';
		$context['paged']         = ! empty( self::$context['paged'] ) ? max( 1, (int) self::$context['paged'] ) : 1;
		$context['canonical_url'] = ! empty( self::$context['canonical_url'] ) ? (string) self::$context['canonical_url'] : '';

		if ( ! empty( self::$context['country_term'] ) && self::$context['country_term'] instanceof WP_Term ) {
			$context['country_term'] = self::$context['country_term'];
		}

		if ( ! empty( self::$context['city_term'] ) && self::$context['city_term'] instanceof WP_Term ) {
			$context['city_term'] = self::$context['city_term'];
		}

		if ( ! empty( self::$context['category_terms'] ) && is_array( self::$context['category_terms'] ) ) {
			$context['category_terms'] = array_values(
				array_filter(
					self::$context['category_terms'],
					function ( $term ) {
						return $term instanceof WP_Term;
					}
				)
			);
		}

		if ( ! empty( self::$context['deepest_term'] ) && self::$context['deepest_term'] instanceof WP_Term ) {
			$context['deepest_term'] = self::$context['deepest_term'];
		}

		if ( ! empty( self::$context['landing_post'] ) && self::$context['landing_post'] instanceof WP_Post ) {
			$context['landing_post'] = self::$context['landing_post'];
		}

		return $context;
	}

	/**
	 * Whether an SEO plugin likely emits its own schema/canonical graph.
	 *
	 * @return bool
	 */
	public static function has_external_seo_provider() {
		return self::has_external_canonical_provider();
	}

	/**
	 * Build a clean target for a semantic route currently polluted by query-string state.
	 *
	 * @return string
	 */
	private static function build_clean_current_route_target() {
		if ( empty( self::$context['is_valid'] ) ) {
			return '';
		}

		$current_args = self::get_request_query_args();
		$selected_cat = self::get_selected_category_id_from_args( $current_args );
		$has_seo_args = isset( $current_args['country_id'] ) || isset( $current_args['cat_id'] ) || isset( $current_args['ad_cat_sub'] ) || isset( $current_args['ad_cat_sub_sub'] ) || isset( $current_args['ad_cat_sub_sub_sub'] ) || isset( $current_args['ad_cat_sub_sub_sub_sub'] );
		$paged        = ! empty( self::$context['paged'] ) ? (int) self::$context['paged'] : 1;

		$location_id = isset( $current_args['country_id'] ) ? (int) $current_args['country_id'] : 0;
		$cat_id      = $selected_cat ? $selected_cat : ( ! empty( self::$context['deepest_term'] ) ? (int) self::$context['deepest_term']->term_id : 0 );
		$route_terms = self::get_route_terms_from_location_id( $location_id );

		if ( ! ( $route_terms['country_term'] instanceof WP_Term ) && ! empty( self::$context['country_term'] ) && self::$context['country_term'] instanceof WP_Term ) {
			$route_terms['country_term'] = self::$context['country_term'];
		}

		if ( ! ( $route_terms['city_term'] instanceof WP_Term ) && ! empty( self::$context['city_term'] ) && self::$context['city_term'] instanceof WP_Term ) {
			$route_terms['city_term'] = self::$context['city_term'];
		}

		$remaining_args = $current_args;
		unset(
			$remaining_args['country_id'],
			$remaining_args['cat_id'],
			$remaining_args['paged'],
			$remaining_args['page'],
			$remaining_args['ad_cat_sub'],
			$remaining_args['ad_cat_sub_sub'],
			$remaining_args['ad_cat_sub_sub_sub'],
			$remaining_args['ad_cat_sub_sub_sub_sub']
		);

		$target = self::build_semantic_url(
			$route_terms['country_term'] instanceof WP_Term ? (int) $route_terms['country_term']->term_id : 0,
			$route_terms['city_term'] instanceof WP_Term ? (int) $route_terms['city_term']->term_id : 0,
			$cat_id,
			$paged,
			$remaining_args
		);
		if ( ! $target ) {
			return '';
		}

		$current_url = self::current_request_url();
		if ( $has_seo_args || ! self::urls_match( $current_url, $target ) ) {
			return $target;
		}

		return '';
	}

	/**
	 * Build a canonical redirect target for legacy AdForest routes.
	 *
	 * @return string
	 */
	private static function build_legacy_redirect_target() {
		$country_id = 0;
		$cat_id     = self::get_selected_category_id_from_args( self::get_request_query_args() );
		$paged      = max( 1, (int) get_query_var( 'paged' ) );
		if ( isset( $_GET['paged'] ) && (int) $_GET['paged'] > 1 ) {
			$paged = (int) $_GET['paged'];
		} elseif ( isset( $_GET['page'] ) && (int) $_GET['page'] > 1 ) {
			$paged = (int) $_GET['page'];
		}

		if ( isset( $_GET['country_id'] ) && $_GET['country_id'] !== '' ) {
			$country_id = (int) wp_unslash( $_GET['country_id'] );
		} elseif ( is_tax( 'ad_country' ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$country_id = (int) $term->term_id;
			}
		}

		if ( ! $cat_id && is_tax( 'ad_cats' ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$cat_id = (int) $term->term_id;
			}
		}

		$route_terms = self::get_route_terms_from_location_id( $country_id );

		if ( ! ( $route_terms['country_term'] instanceof WP_Term ) && ! $cat_id ) {
			return '';
		}

		$query_args = self::get_request_query_args();
		unset(
			$query_args['country_id'],
			$query_args['cat_id'],
			$query_args['paged'],
			$query_args['page'],
			$query_args['ad_cat_sub'],
			$query_args['ad_cat_sub_sub'],
			$query_args['ad_cat_sub_sub_sub'],
			$query_args['ad_cat_sub_sub_sub_sub']
		);

		return self::build_semantic_url(
			$route_terms['country_term'] instanceof WP_Term ? (int) $route_terms['country_term']->term_id : 0,
			$route_terms['city_term'] instanceof WP_Term ? (int) $route_terms['city_term']->term_id : 0,
			$cat_id,
			$paged,
			$query_args
		);
	}

	/**
	 * Resolve a category chain from sequential slugs.
	 *
	 * @param string[] $segments Category segments after the city.
	 * @return WP_Term[]|false
	 */
	private static function resolve_category_chain( array $segments ) {
		if ( empty( $segments ) ) {
			return array();
		}

		$terms     = array();
		$parent_id = 0;

		foreach ( $segments as $slug ) {
			$term = get_term_by( 'slug', $slug, 'ad_cats' );
			if ( ! ( $term instanceof WP_Term ) ) {
				return false;
			}

			if ( (int) $term->parent !== $parent_id ) {
				return false;
			}

			$terms[]   = $term;
			$parent_id = (int) $term->term_id;
		}

		return $terms;
	}

	/**
	 * Build the public semantic URL.
	 *
	 * @param int                        $country_id Country term ID.
	 * @param int                        $city_id City term ID.
	 * @param int                        $cat_id Deepest category term ID.
	 * @param int                        $paged Page number.
	 * @param array<string,mixed>|string $query_args Optional extra query args.
	 * @return string
	 */
	private static function build_semantic_url( $country_id, $city_id = 0, $cat_id = 0, $paged = 1, $query_args = array() ) {
		$segments    = array();
		$country_id  = (int) $country_id;
		$city_id     = (int) $city_id;
		$category_id = (int) $cat_id;

		if ( $country_id > 0 ) {
			$country_term = get_term( $country_id, 'ad_country' );
			if ( ! ( $country_term instanceof WP_Term ) ) {
				return '';
			}
			$segments[] = $country_term->slug;
		}

		if ( $city_id > 0 ) {
			$city_term = get_term( $city_id, 'ad_country' );
			if ( ! ( $city_term instanceof WP_Term ) ) {
				return '';
			}

			if ( ! self::is_term_within_country( $city_term, $country_id ) ) {
				return '';
			}

			$segments[] = $city_term->slug;
		}

		if ( $category_id > 0 ) {
			$category_term = get_term( $category_id, 'ad_cats' );
			if ( ! $category_term instanceof WP_Term ) {
				return '';
			}

			foreach ( self::get_category_path_terms( $category_term ) as $term ) {
				$segments[] = $term->slug;
			}
		}

		if ( empty( $segments ) ) {
			return '';
		}

		if ( (int) $paged > 1 ) {
			$segments[] = 'page';
			$segments[] = (string) (int) $paged;
		}

		$path = implode( '/', array_map( 'rawurlencode', $segments ) );
		$url  = home_url( user_trailingslashit( $path ) );

		if ( ! empty( $query_args ) && is_array( $query_args ) ) {
			$url = add_query_arg( $query_args, $url );
		}

		return $url;
	}

	/**
	 * Get the category path from top-level parent to the current term.
	 *
	 * @param WP_Term $term Category term.
	 * @return WP_Term[]
	 */
	private static function get_category_path_terms( WP_Term $term ) {
		$terms     = array( $term );
		$ancestors = get_ancestors( $term->term_id, 'ad_cats', 'taxonomy' );
		$ancestors = array_reverse( array_map( 'intval', $ancestors ) );

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor_term = get_term( $ancestor_id, 'ad_cats' );
			if ( $ancestor_term instanceof WP_Term ) {
				array_unshift( $terms, $ancestor_term );
			}
		}

		return $terms;
	}

	/**
	 * Return the current route country term when available.
	 *
	 * @return WP_Term|null
	 */
	private static function get_active_country_term() {
		if ( self::is_current_seo_route() && ! empty( self::$context['country_term'] ) ) {
			return self::$context['country_term'];
		}

		if ( isset( $_GET['country_id'] ) && $_GET['country_id'] !== '' ) {
			$route_terms = self::get_route_terms_from_location_id( (int) wp_unslash( $_GET['country_id'] ) );
			return $route_terms['country_term'];
		}

		if ( is_tax( 'ad_country' ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$route_terms = self::split_location_term_for_route( $term );
				return $route_terms['country_term'];
			}
		}

		return null;
	}

	/**
	 * Return the current route city term when available.
	 *
	 * @return WP_Term|null
	 */
	private static function get_active_city_term() {
		if ( self::is_current_seo_route() && ! empty( self::$context['city_term'] ) ) {
			return self::$context['city_term'];
		}

		if ( isset( $_GET['country_id'] ) && $_GET['country_id'] !== '' ) {
			$route_terms = self::get_route_terms_from_location_id( (int) wp_unslash( $_GET['country_id'] ) );
			return $route_terms['city_term'];
		}

		if ( is_tax( 'ad_country' ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$route_terms = self::split_location_term_for_route( $term );
				return $route_terms['city_term'];
			}
		}

		return null;
	}

	/**
	 * Resolve the configured AdForest search page for the current site.
	 *
	 * @return int
	 */
	private static function get_adforest_search_page_id() {
		if ( null !== self::$search_page_id_cache ) {
			return self::$search_page_id_cache;
		}

		global $adforest_theme;

		if ( empty( $adforest_theme['sb_search_page'] ) ) {
			self::$search_page_id_cache = 0;
			return 0;
		}

		$page_id = apply_filters( 'adforest_language_page_id', $adforest_theme['sb_search_page'] );
		self::$search_page_id_cache = max( 0, (int) $page_id );

		return self::$search_page_id_cache;
	}

	/**
	 * Recover semantic routes even if WordPress reached the 404 handler first.
	 *
	 * @param WP_Query $wp_query Main query instance.
	 * @return void
	 */
	private static function attempt_late_route_recovery( $wp_query ) {
		$route_path = self::get_current_request_path();
		if ( '' === $route_path ) {
			return;
		}

		$resolved = self::resolve_semantic_route( $route_path );
		if ( empty( $resolved['is_valid'] ) ) {
			return;
		}

		$resolved       = self::hydrate_route_context( $resolved );
		self::$context  = $resolved;
		$bound_post     = self::get_bound_post( $resolved );
		if ( ! $bound_post instanceof WP_Post ) {
			return;
		}

		$wp_query->queried_object_id      = $bound_post->ID;
		$wp_query->queried_object         = $bound_post;
		$wp_query->posts                  = array( $bound_post );
		$wp_query->post                   = $bound_post;
		$wp_query->found_posts            = 1;
		$wp_query->post_count             = 1;
		$wp_query->max_num_pages          = 1;

		if ( ! empty( $resolved['landing_post'] ) && $resolved['landing_post'] instanceof WP_Post ) {
			$wp_query->query_vars['post_type'] = $resolved['landing_post']->post_type;
			$wp_query->query_vars['p']         = $resolved['landing_post']->ID;
		} else {
			$wp_query->query_vars['page_id'] = $bound_post->ID;
		}
	}

	/**
	 * Try to resolve semantic routes even when rewrite rules were not flushed yet.
	 *
	 * @param WP $wp WordPress request object.
	 * @return bool
	 */
	private static function should_try_fallback_route( $wp ) {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}

		if ( empty( $wp->request ) ) {
			return false;
		}

		return empty( $wp->matched_rule );
	}

	/**
	 * Normalize a requested route path relative to the current site's home path.
	 *
	 * This is important on subdirectory multisite installs where the incoming request
	 * may still contain the site prefix (for example `uk/london` instead of `london`).
	 *
	 * @param string $route_path Raw route path.
	 * @return string
	 */
	private static function normalize_route_path( $route_path ) {
		$route_path = trim( (string) $route_path, '/' );
		if ( '' === $route_path ) {
			return '';
		}

		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = trim( (string) $home_path, '/' );

		if ( '' !== $home_path ) {
			if ( $route_path === $home_path ) {
				return '';
			}

			$prefix = $home_path . '/';
			if ( 0 === strpos( $route_path, $prefix ) ) {
				$route_path = substr( $route_path, strlen( $prefix ) );
			}
		}

		return trim( (string) $route_path, '/' );
	}

	/**
	 * Get current frontend request path relative to the current site's home path.
	 *
	 * @return string
	 */
	private static function get_current_request_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( '' === $request_uri ) {
			return '';
		}

		$path = wp_parse_url( $request_uri, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return '';
		}

		return self::normalize_route_path( $path );
	}

	/**
	 * Resolve country-first routes plus no-country category UX hubs.
	 *
	 * @param string $route_path Requested semantic path.
	 * @return array<string,mixed>
	 */
	private static function resolve_semantic_route( $route_path ) {
		$cache_key = (string) $route_path;
		if ( isset( self::$route_cache[ $cache_key ] ) ) {
			return self::$route_cache[ $cache_key ];
		}

		$result = self::do_resolve_semantic_route( $route_path );
		self::$route_cache[ $cache_key ] = $result;
		return $result;
	}

	/**
	 * Internal uncached implementation of route resolution.
	 *
	 * @param string $route_path Requested semantic path.
	 * @return array<string,mixed>
	 */
	private static function do_resolve_semantic_route( $route_path ) {
		$segments = array_values( array_filter( array_map( 'sanitize_title', explode( '/', trim( (string) $route_path, '/' ) ) ) ) );
		if ( empty( $segments ) ) {
			return array(
				'is_seo_route' => true,
				'is_valid'     => false,
			);
		}

		$paged = 1;
		$page_segments_count = count( $segments );
		if ( $page_segments_count >= 2 && 'page' === $segments[ $page_segments_count - 2 ] ) {
			$page_number = (int) $segments[ $page_segments_count - 1 ];
			if ( $page_number > 1 ) {
				$paged    = $page_number;
				$segments = array_slice( $segments, 0, -2 );
			}
		}

		if ( empty( $segments ) ) {
			return array(
				'is_seo_route' => true,
				'is_valid'     => false,
			);
		}

		$country_term   = self::resolve_country_term_by_slug( $segments[0] );
		$city_term      = null;
		$category_terms = false;

		if ( $country_term instanceof WP_Term ) {
			$remaining_segments = array_slice( $segments, 1 );
			if ( ! empty( $remaining_segments ) ) {
				$possible_city = self::resolve_city_term_for_country( $remaining_segments[0], $country_term );
				if ( $possible_city instanceof WP_Term ) {
					$city_term          = $possible_city;
					$remaining_segments = array_slice( $remaining_segments, 1 );
				}
			}

			$category_terms = self::resolve_category_chain( $remaining_segments );
		} else {
			$category_terms = self::resolve_category_chain( $segments );
		}

		if ( false === $category_terms ) {
			return array(
				'is_seo_route' => true,
				'is_valid'     => false,
				'segments'     => $segments,
				'country_term' => $country_term,
				'city_term'    => $city_term,
			);
		}

		if ( $paged < 2 ) {
			$paged = max( 1, (int) get_query_var( 'paged' ) );
		}
		$deepest_term = ! empty( $category_terms ) ? end( $category_terms ) : null;
		$route_mode   = 'none';
		if ( $country_term instanceof WP_Term && $city_term instanceof WP_Term && $deepest_term instanceof WP_Term ) {
			$route_mode = 'country_city_category';
		} elseif ( $country_term instanceof WP_Term && $city_term instanceof WP_Term ) {
			$route_mode = 'country_city';
		} elseif ( $country_term instanceof WP_Term && $deepest_term instanceof WP_Term ) {
			$route_mode = 'country_category';
		} elseif ( $country_term instanceof WP_Term ) {
			$route_mode = 'country_only';
		} elseif ( $deepest_term instanceof WP_Term ) {
			$route_mode = 'category_only';
		}

		return array(
			'is_seo_route'   => true,
			'is_valid'       => true,
			'segments'       => $segments,
			'country_term'   => $country_term,
			'city_term'      => $city_term,
			'category_terms' => $category_terms,
			'deepest_term'   => $deepest_term instanceof WP_Term ? $deepest_term : null,
			'route_mode'     => $route_mode,
			'paged'          => $paged,
			'canonical_url'  => self::build_semantic_url(
				$country_term instanceof WP_Term ? (int) $country_term->term_id : 0,
				$city_term instanceof WP_Term ? (int) $city_term->term_id : 0,
				$deepest_term instanceof WP_Term ? (int) $deepest_term->term_id : 0,
				$paged
			),
		);
	}

	/**
	 * Build a stable canonical URL for the current request.
	 *
	 * @return string
	 */
	private static function get_canonical_url_for_current_request() {
		if ( self::is_current_seo_route() && ! empty( self::$context['is_valid'] ) ) {
			return (string) self::$context['canonical_url'];
		}

		$legacy_target = self::build_legacy_redirect_target();
		return $legacy_target ? $legacy_target : '';
	}

	/**
	 * Detect common SEO plugins that already emit canonical tags.
	 *
	 * @return bool
	 */
	private static function has_external_canonical_provider() {
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Frontend' ) ) {
			return true;
		}

		if ( class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return true;
		}

		if ( defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determine whether the current request should be noindexed.
	 *
	 * @return bool
	 */
	private static function should_noindex_request() {
		if ( self::is_current_seo_route() ) {
			$extra_args = self::get_request_query_args();
			$route_mode = ! empty( self::$context['route_mode'] ) ? (string) self::$context['route_mode'] : '';
			unset(
				$extra_args['country_id'],
				$extra_args['cat_id'],
				$extra_args['ad_cat_sub'],
				$extra_args['ad_cat_sub_sub'],
				$extra_args['ad_cat_sub_sub_sub'],
				$extra_args['ad_cat_sub_sub_sub_sub'],
				$extra_args['bornado_debug_route']
			);

			// If a landing post exists and is marked indexable, only noindex
			// when there are additional filter args that make the URL non-canonical.
			if ( ! empty( self::$context['landing_post'] )
				&& self::$context['landing_post'] instanceof WP_Post
				&& Bornado_SEO_Landing_Manager::is_indexable( self::$context['landing_post'] )
			) {
				return ! empty( $extra_args );
			}

			// Category-only hubs should only become indexable when they are backed
			// by an explicit, indexable SEO landing. Otherwise keep the clean route
			// crawlable for UX, but out of the index.
			if ( 'category_only' === $route_mode ) {
				return true;
			}

			// For plain search-page routes (no landing), noindex only when
			// extra filter args are present. A clean /country/category/ URL
			// with no extra args is canonical and should be indexable.
			return ! empty( $extra_args );
		}

		return is_tax( 'ad_country' ) || is_tax( 'ad_cats' );
	}

	/**
	 * Get the current request query args as unslashed values.
	 *
	 * @return array<string,mixed>
	 */
	private static function get_request_query_args() {
		return wp_unslash( $_GET );
	}

	/**
	 * Build the public query-string state for semantic routes.
	 *
	 * Route-defining city/category values should remain in $_GET for theme logic, but not in
	 * the visible query string because the semantic path already carries them. This prevents
	 * AdForest from rendering them as removable tags that cannot actually be removed.
	 *
	 * @param array<string,mixed> $state Full request state.
	 * @return array<string,mixed>
	 */
	private static function get_public_query_state( array $state ) {
		if ( ! self::is_current_seo_route() || empty( self::$context['is_valid'] ) ) {
			return $state;
		}

		unset(
			$state['country_id'],
			$state['cat_id'],
			$state['ad_cat_sub'],
			$state['ad_cat_sub_sub'],
			$state['ad_cat_sub_sub_sub'],
			$state['ad_cat_sub_sub_sub_sub']
		);

		return $state;
	}

	/**
	 * Attach landing post information to a resolved route.
	 *
	 * @param array<string,mixed> $resolved Route context.
	 * @return array<string,mixed>
	 */
	private static function hydrate_route_context( array $resolved ) {
		if ( empty( $resolved['is_valid'] ) || ! class_exists( 'Bornado_SEO_Landing_Manager' ) ) {
			return $resolved;
		}

		$resolved['landing_post'] = Bornado_SEO_Landing_Manager::find_matching_landing( $resolved );

		return $resolved;
	}

	/**
	 * Resolve a root-level country term by slug.
	 *
	 * @param string $slug Country slug.
	 * @return WP_Term|null
	 */
	private static function resolve_country_term_by_slug( $slug ) {
		$term = get_term_by( 'slug', sanitize_title( (string) $slug ), 'ad_country' );
		if ( ! $term instanceof WP_Term ) {
			return null;
		}

		return 0 === (int) $term->parent ? $term : null;
	}

	/**
	 * Resolve a child location inside a country route.
	 *
	 * @param string  $slug Country child slug.
	 * @param WP_Term $country_term Country term.
	 * @return WP_Term|null
	 */
	private static function resolve_city_term_for_country( $slug, WP_Term $country_term ) {
		$term = get_term_by( 'slug', sanitize_title( (string) $slug ), 'ad_country' );
		if ( ! $term instanceof WP_Term ) {
			return null;
		}

		if ( (int) $term->term_id === (int) $country_term->term_id ) {
			return null;
		}

		return self::is_term_within_country( $term, (int) $country_term->term_id ) ? $term : null;
	}

	/**
	 * Split a location term into country and city route components.
	 *
	 * @param WP_Term|null $term Location term.
	 * @return array{country_term:?WP_Term,city_term:?WP_Term}
	 */
	private static function split_location_term_for_route( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return array(
				'country_term' => null,
				'city_term'    => null,
			);
		}

		if ( 0 === (int) $term->parent ) {
			return array(
				'country_term' => $term,
				'city_term'    => null,
			);
		}

		$ancestor_ids = array_reverse( array_map( 'intval', get_ancestors( (int) $term->term_id, 'ad_country', 'taxonomy' ) ) );
		$country_term = null;
		if ( ! empty( $ancestor_ids ) ) {
			$country_candidate = get_term( (int) $ancestor_ids[0], 'ad_country' );
			if ( $country_candidate instanceof WP_Term ) {
				$country_term = $country_candidate;
			}
		}

		return array(
			'country_term' => $country_term,
			'city_term'    => $term,
		);
	}

	/**
	 * Resolve route terms from a selected location term id.
	 *
	 * @param int $location_id Location term ID.
	 * @return array{country_term:?WP_Term,city_term:?WP_Term}
	 */
	private static function get_route_terms_from_location_id( $location_id ) {
		$location_id = (int) $location_id;
		if ( $location_id < 1 ) {
			return array(
				'country_term' => null,
				'city_term'    => null,
			);
		}

		$term = get_term( $location_id, 'ad_country' );

		return self::split_location_term_for_route( $term instanceof WP_Term ? $term : null );
	}

	/**
	 * Return whether a location term belongs to a specific country term.
	 *
	 * @param WP_Term $term Location term.
	 * @param int     $country_id Country term ID.
	 * @return bool
	 */
	private static function is_term_within_country( WP_Term $term, $country_id ) {
		$country_id = (int) $country_id;
		if ( $country_id < 1 ) {
			return false;
		}

		if ( (int) $term->term_id === $country_id ) {
			return true;
		}

		return in_array( $country_id, array_map( 'intval', get_ancestors( (int) $term->term_id, 'ad_country', 'taxonomy' ) ), true );
	}

	/**
	 * Build WP query vars for the current route target.
	 *
	 * @param array<string,mixed> $resolved Route context.
	 * @return array<string,mixed>
	 */
	private static function get_bound_query_vars( array $resolved ) {
		$bound_post = self::get_bound_post( $resolved );
		if ( ! $bound_post instanceof WP_Post ) {
			return array();
		}

		$query_vars = array(
			self::QUERY_ROUTE      => 1,
			self::QUERY_ROUTE_PATH => ! empty( $resolved['segments'] ) ? implode( '/', (array) $resolved['segments'] ) : '',
		);

		if ( ! empty( $resolved['landing_post'] ) && $resolved['landing_post'] instanceof WP_Post ) {
			$query_vars['post_type'] = $resolved['landing_post']->post_type;
			$query_vars['p']         = $resolved['landing_post']->ID;
		} else {
			$query_vars['page_id'] = $bound_post->ID;
		}

		if ( ! empty( $resolved['paged'] ) && (int) $resolved['paged'] > 1 ) {
			$query_vars['paged'] = (int) $resolved['paged'];
		}

		return $query_vars;
	}

	/**
	 * Resolve which WP post should act as the main queried object.
	 *
	 * @param array<string,mixed> $resolved Route context.
	 * @return WP_Post|null
	 */
	private static function get_bound_post( array $resolved ) {
		if ( ! empty( $resolved['landing_post'] ) && $resolved['landing_post'] instanceof WP_Post ) {
			return $resolved['landing_post'];
		}

		$search_page_id = self::get_adforest_search_page_id();
		if ( $search_page_id <= 0 ) {
			return null;
		}

		$page = get_post( $search_page_id );
		return $page instanceof WP_Post ? $page : null;
	}

	/**
	 * Resolve the deepest selected category from AdForest's category fields.
	 *
	 * @return int
	 */
	private static function get_selected_category_id_from_args( array $args ) {
		$keys = array(
			'cat_id',
			'ad_cat_sub',
			'ad_cat_sub_sub',
			'ad_cat_sub_sub_sub',
			'ad_cat_sub_sub_sub_sub',
		);

		$selected = 0;
		foreach ( $keys as $key ) {
			if ( isset( $args[ $key ] ) && '' !== $args[ $key ] ) {
				$selected = (int) $args[ $key ];
			}
		}

		return $selected;
	}

	/**
	 * Build the absolute current request URL.
	 *
	 * @return string
	 */
	private static function current_request_url() {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

		return $scheme . $host . $uri;
	}

	/**
	 * Compare URLs after normalizing query ordering and trailing slashes.
	 *
	 * @param string $left First URL.
	 * @param string $right Second URL.
	 * @return bool
	 */
	private static function urls_match( $left, $right ) {
		return self::normalize_url( $left ) === self::normalize_url( $right );
	}

	/**
	 * Normalize a URL for equality comparisons.
	 *
	 * @param string $url Input URL.
	 * @return string
	 */
	private static function normalize_url( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return untrailingslashit( $url );
		}

		$query = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			ksort( $query );
		}

		$normalized  = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
		$normalized .= isset( $parts['host'] ) ? $parts['host'] : '';
		$normalized .= isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';

		if ( ! empty( $query ) ) {
			$normalized .= '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		}

		return $normalized;
	}
}

if ( ! function_exists( 'bornado_seo_routing_get_context' ) ) {
	/**
	 * Public helper returning the current semantic route context.
	 *
	 * @return array<string,mixed>
	 */
	function bornado_seo_routing_get_context() {
		if ( ! class_exists( 'Bornado_SEO_Routing' ) ) {
			return array(
				'is_seo_route'   => false,
				'is_valid'       => false,
				'route_mode'     => '',
				'paged'          => 1,
				'canonical_url'  => '',
				'country_term'   => null,
				'city_term'      => null,
				'category_terms' => array(),
				'deepest_term'   => null,
				'landing_post'   => null,
			);
		}

		return Bornado_SEO_Routing::get_public_route_context();
	}
}

if ( ! function_exists( 'bornado_seo_routing_has_external_seo_provider' ) ) {
	/**
	 * Whether a third-party SEO plugin is active for the current request.
	 *
	 * @return bool
	 */
	function bornado_seo_routing_has_external_seo_provider() {
		return class_exists( 'Bornado_SEO_Routing' ) && Bornado_SEO_Routing::has_external_seo_provider();
	}
}

Bornado_SEO_Routing::init();
register_activation_hook( __FILE__, array( 'Bornado_SEO_Routing', 'activate' ) );
