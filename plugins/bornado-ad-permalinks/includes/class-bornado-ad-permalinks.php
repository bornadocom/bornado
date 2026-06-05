<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Ad_Permalinks' ) ) {
	return;
}

final class Bornado_Ad_Permalinks {
	const VERSION                = '1.0.0';
	const REWRITE_VERSION        = '1.1.0';
	const OPTION_REWRITE_VERSION = 'bornado_ad_permalinks_rewrite_version';
	const POST_TYPE              = 'ad_post';
	const PREFIX                 = 'ad';
	const QUERY_ROUTE            = 'bornado_ad_route';
	const QUERY_HASH             = 'bornado_ad_hash';
	const QUERY_SLUG             = 'bornado_ad_slug';
	const HASH_MIN_LENGTH        = 5;
	const TITLE_MIN_LENGTH       = 20;
	const TITLE_MAX_LENGTH       = 80;
	const AJAX_TITLE_ERROR_CODE  = 'ad_title_invalid';

	/**
	 * @var bool
	 */
	private static $is_hash_route_request = false;

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ), 0 );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 20 );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_action( 'parse_request', array( __CLASS__, 'maybe_parse_hash_route' ), 0 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_post_type_link' ), 10, 4 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_to_canonical' ), 0 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'filter_redirect_canonical' ), 10, 2 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'filter_core_canonical_url' ), 10, 2 );
		add_filter( 'wpseo_canonical', array( __CLASS__, 'filter_external_canonical_url' ) );
		add_filter( 'rank_math/frontend/canonical', array( __CLASS__, 'filter_external_canonical_url' ) );
		add_filter( 'rank_math/opengraph/url', array( __CLASS__, 'filter_external_canonical_url' ) );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'filter_rank_math_json_ld' ), 99, 2 );
		add_filter( 'rank_math/sitemap/entry', array( __CLASS__, 'filter_rank_math_sitemap_entry' ), 10, 3 );
		add_filter( 'aioseo_canonical_url', array( __CLASS__, 'filter_external_canonical_url' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_canonical_tag' ), 1 );
		add_filter( 'option_adforest_theme', array( __CLASS__, 'filter_adforest_theme_options' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_title_guard_assets' ), 50 );
		add_action( 'wp_ajax_sb_ad_posting', array( __CLASS__, 'validate_ajax_ad_title' ), 1 );
	}

	/**
	 * @return void
	 */
	public static function activate() {
		self::register_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( self::OPTION_REWRITE_VERSION, self::REWRITE_VERSION, false );
	}

	/**
	 * @param array<int,string> $vars
	 * @return array<int,string>
	 */
	public static function register_query_vars( $vars ) {
		$vars[] = self::QUERY_ROUTE;
		$vars[] = self::QUERY_HASH;
		$vars[] = self::QUERY_SLUG;

		return $vars;
	}

	/**
	 * @return void
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule(
			'^' . preg_quote( self::PREFIX, '/' ) . '/([A-Za-z0-9]+)/([^/]+)/feed/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_HASH . '=$matches[1]&' . self::QUERY_SLUG . '=$matches[2]&feed=rss2',
			'top'
		);

		add_rewrite_rule(
			'^' . preg_quote( self::PREFIX, '/' ) . '/([A-Za-z0-9]+)/([^/]+)/(feed|rdf|rss|rss2|atom)/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_HASH . '=$matches[1]&' . self::QUERY_SLUG . '=$matches[2]&feed=$matches[3]',
			'top'
		);

		add_rewrite_rule(
			'^' . preg_quote( self::PREFIX, '/' ) . '/([A-Za-z0-9]+)/([^/]+)/comment-page-([0-9]{1,})/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_HASH . '=$matches[1]&' . self::QUERY_SLUG . '=$matches[2]&cpage=$matches[3]',
			'top'
		);

		add_rewrite_rule(
			'^' . preg_quote( self::PREFIX, '/' ) . '/([A-Za-z0-9]+)/([^/]+)/([0-9]{1,})/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_HASH . '=$matches[1]&' . self::QUERY_SLUG . '=$matches[2]&page=$matches[3]',
			'top'
		);

		add_rewrite_rule(
			'^' . preg_quote( self::PREFIX, '/' ) . '/([A-Za-z0-9]+)/([^/]+)/embed/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_HASH . '=$matches[1]&' . self::QUERY_SLUG . '=$matches[2]&embed=true',
			'top'
		);

		add_rewrite_rule(
			'^' . preg_quote( self::PREFIX, '/' ) . '/([A-Za-z0-9]+)/([^/]+)/?$',
			'index.php?' . self::QUERY_ROUTE . '=1&' . self::QUERY_HASH . '=$matches[1]&' . self::QUERY_SLUG . '=$matches[2]',
			'top'
		);
	}

	/**
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
	 * @param WP $wp
	 * @return void
	 */
	public static function maybe_parse_hash_route( $wp ) {
		if ( empty( $wp->query_vars[ self::QUERY_ROUTE ] ) ) {
			return;
		}

		self::$is_hash_route_request = true;

		$hash    = isset( $wp->query_vars[ self::QUERY_HASH ] ) ? sanitize_text_field( (string) $wp->query_vars[ self::QUERY_HASH ] ) : '';
		$post_id = Bornado_Ad_Hash_Service::instance()->decode_id( $hash );
		$post    = $post_id > 0 ? get_post( $post_id ) : null;

		if ( ! self::is_readable_ad_post( $post ) ) {
			$wp->query_vars['error'] = '404';
			return;
		}

		$wp->query_vars['post_type'] = self::POST_TYPE;
		$wp->query_vars['p']         = (int) $post->ID;

		unset(
			$wp->query_vars['error'],
			$wp->query_vars['name'],
			$wp->query_vars['pagename'],
			$wp->query_vars['attachment'],
			$wp->query_vars['attachment_id']
		);
	}

	/**
	 * @param string   $post_link
	 * @param WP_Post  $post
	 * @param bool     $leavename
	 * @param bool     $sample
	 * @return string
	 */
	public static function filter_post_type_link( $post_link, $post, $leavename, $sample ) {
		unset( $leavename );

		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return $post_link;
		}

		$permalink = self::build_ad_permalink( $post, (bool) $sample );
		return '' !== $permalink ? $permalink : $post_link;
	}

	/**
	 * @return void
	 */
	public static function maybe_redirect_to_canonical() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! is_singular( self::POST_TYPE ) || is_preview() || is_feed() || is_embed() || is_trackback() ) {
			return;
		}

		if ( (int) get_query_var( 'page' ) > 1 || (int) get_query_var( 'cpage' ) > 0 ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$target = self::get_contextual_canonical_url( $post );
		if ( '' === $target ) {
			return;
		}

		if ( self::normalize_url( self::current_request_url() ) === self::normalize_url( $target ) ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * @param string|false $redirect_url
	 * @param string       $requested_url
	 * @return string|false
	 */
	public static function filter_redirect_canonical( $redirect_url, $requested_url ) {
		unset( $requested_url );

		if ( self::$is_hash_route_request || is_singular( self::POST_TYPE ) ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * @param string|false $canonical_url
	 * @param WP_Post      $post
	 * @return string|false
	 */
	public static function filter_core_canonical_url( $canonical_url, $post ) {
		if ( $post instanceof WP_Post && self::POST_TYPE === $post->post_type ) {
			$canonical = self::get_contextual_canonical_url( $post );
			return '' !== $canonical ? $canonical : $canonical_url;
		}

		return $canonical_url;
	}

	/**
	 * @param string $canonical_url
	 * @return string
	 */
	public static function filter_external_canonical_url( $canonical_url ) {
		if ( ! is_singular( self::POST_TYPE ) ) {
			return $canonical_url;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return $canonical_url;
		}

		$canonical = self::get_contextual_canonical_url( $post );
		return '' !== $canonical ? $canonical : $canonical_url;
	}

	/**
	 * @param array<mixed>|mixed $data
	 * @param mixed              $jsonld
	 * @return array<mixed>|mixed
	 */
	public static function filter_rank_math_json_ld( $data, $jsonld ) {
		unset( $jsonld );

		if ( ! is_array( $data ) || ! is_singular( self::POST_TYPE ) ) {
			return $data;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return $data;
		}

		$canonical       = self::get_contextual_canonical_url( $post );
		$placeholder_url = self::get_placeholder_permalink( $post );
		if ( '' === $canonical || '' === $placeholder_url ) {
			return $data;
		}

		return self::replace_schema_placeholder_urls( $data, $placeholder_url, $canonical );
	}

	/**
	 * @param array<string,mixed>|mixed $url
	 * @param string                    $type
	 * @param mixed                     $object
	 * @return array<string,mixed>|mixed
	 */
	public static function filter_rank_math_sitemap_entry( $url, $type, $object ) {
		if ( 'post' !== $type || ! is_array( $url ) ) {
			return $url;
		}

		$post = self::resolve_post_object( $object );
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return $url;
		}

		$canonical = self::get_canonical_permalink( $post );
		if ( '' !== $canonical ) {
			$url['loc'] = $canonical;
		}

		return $url;
	}

	/**
	 * @return void
	 */
	public static function print_canonical_tag() {
		if ( ! is_singular( self::POST_TYPE ) || self::has_external_canonical_provider() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$canonical = self::get_contextual_canonical_url( $post );
		if ( '' === $canonical ) {
			return;
		}

		printf(
			"<link rel=\"canonical\" href=\"%s\" />\n",
			esc_url( $canonical )
		);
	}

	/**
	 * @param mixed $options
	 * @return mixed
	 */
	public static function filter_adforest_theme_options( $options ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}

		$current_limit = isset( $options['ad_post_title_limit'] ) ? (int) $options['ad_post_title_limit'] : 0;
		if ( $current_limit < self::TITLE_MIN_LENGTH || $current_limit > self::TITLE_MAX_LENGTH ) {
			$options['ad_post_title_limit'] = self::TITLE_MAX_LENGTH;
		}

		return $options;
	}

	/**
	 * @return void
	 */
	public static function enqueue_title_guard_assets() {
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		$script_path = BORNADO_AD_PERMALINKS_DIR . 'assets/js/bornado-ad-title-guard.js';
		$script_ver  = is_readable( $script_path ) ? (string) filemtime( $script_path ) : self::VERSION;

		wp_enqueue_script(
			'bornado-ad-title-guard',
			BORNADO_AD_PERMALINKS_URL . 'assets/js/bornado-ad-title-guard.js',
			array(),
			$script_ver,
			true
		);

		wp_localize_script(
			'bornado-ad-title-guard',
			'bornadoAdTitleGuard',
			array(
				'minLength'    => self::TITLE_MIN_LENGTH,
				'maxLength'    => self::TITLE_MAX_LENGTH,
				'errorCode'    => self::AJAX_TITLE_ERROR_CODE,
				'message'      => sprintf(
					/* translators: 1: minimum characters, 2: maximum characters. */
					__( 'عنوان آگهی باید بین %1$d تا %2$d کاراکتر باشد.', 'bornado-ad-permalinks' ),
					self::TITLE_MIN_LENGTH,
					self::TITLE_MAX_LENGTH
				),
				'placeholder'  => sprintf(
					/* translators: 1: maximum characters. */
					__( 'عنوان آگهی را در حداکثر %1$d کاراکتر وارد کنید.', 'bornado-ad-permalinks' ),
					self::TITLE_MAX_LENGTH
				),
			)
		);
	}

	/**
	 * @return void
	 */
	public static function validate_ajax_ad_title() {
		$params = array();
		if ( ! empty( $_POST['sb_data'] ) ) {
			parse_str( wp_unslash( $_POST['sb_data'] ), $params );
		}

		$title = isset( $params['ad_title'] ) ? sanitize_text_field( $params['ad_title'] ) : '';
		if ( self::is_title_length_valid( $title ) ) {
			return;
		}

		wp_die( self::AJAX_TITLE_ERROR_CODE );
	}

	/**
	 * @param WP_Post $post
	 * @return string
	 */
	public static function get_canonical_permalink( $post ) {
		return self::build_ad_permalink( $post, false );
	}

	/**
	 * @param WP_Post $post
	 * @return string
	 */
	private static function get_contextual_canonical_url( $post ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return '';
		}

		$canonical = self::build_ad_permalink( $post, false );
		if ( '' === $canonical ) {
			return '';
		}

		$page_number = max( 1, (int) get_query_var( 'page' ) );
		if ( $page_number > 1 ) {
			return trailingslashit( $canonical ) . user_trailingslashit( (string) $page_number, 'single_paged' );
		}

		return $canonical;
	}

	/**
	 * @param WP_Post $post
	 * @return string
	 */
	private static function get_placeholder_permalink( $post ) {
		return self::build_ad_permalink( $post, true );
	}

	/**
	 * @param WP_Post $post
	 * @param bool    $placeholder_slug
	 * @return string
	 */
	private static function build_ad_permalink( $post, $placeholder_slug ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return '';
		}

		$slug = self::get_post_slug( $post, $placeholder_slug );
		$hash = Bornado_Ad_Hash_Service::instance()->encode_id( $post->ID );

		if ( '' === $slug || '' === $hash ) {
			return '';
		}

		return home_url( user_trailingslashit( self::PREFIX . '/' . $hash . '/' . $slug ) );
	}

	/**
	 * @param WP_Post|null $post
	 * @return bool
	 */
	private static function is_readable_ad_post( $post ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
			return false;
		}

		if ( 'publish' === $post->post_status ) {
			return true;
		}

		// Allow authors/admins to open hash-based preview URLs for unpublished ads.
		$preview_flag = isset( $_GET['preview'] ) ? strtolower( trim( (string) wp_unslash( $_GET['preview'] ) ) ) : '';
		if ( in_array( $preview_flag, array( '1', 'true', 'yes' ), true ) && current_user_can( 'edit_post', $post->ID ) ) {
			return true;
		}

		return current_user_can( 'read_post', $post->ID );
	}

	/**
	 * @param WP_Post $post
	 * @param bool    $placeholder_slug
	 * @return string
	 */
	private static function get_post_slug( $post, $placeholder_slug ) {
		if ( $placeholder_slug ) {
			return '%postname%';
		}

		if ( ! empty( $post->post_name ) ) {
			return $post->post_name;
		}

		$fallback_slug = sanitize_title( $post->post_title );
		return '' !== $fallback_slug ? $fallback_slug : 'ad';
	}

	/**
	 * @param mixed $candidate
	 * @return WP_Post|null
	 */
	private static function resolve_post_object( $candidate ) {
		if ( $candidate instanceof WP_Post ) {
			return $candidate;
		}

		if ( is_object( $candidate ) && isset( $candidate->ID ) ) {
			$post = get_post( (int) $candidate->ID );
			return $post instanceof WP_Post ? $post : null;
		}

		if ( is_numeric( $candidate ) ) {
			$post = get_post( (int) $candidate );
			return $post instanceof WP_Post ? $post : null;
		}

		return null;
	}

	/**
	 * @param mixed  $value
	 * @param string $placeholder_url
	 * @param string $canonical_url
	 * @return mixed
	 */
	private static function replace_schema_placeholder_urls( $value, $placeholder_url, $canonical_url ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $nested_value ) {
				$value[ $key ] = self::replace_schema_placeholder_urls( $nested_value, $placeholder_url, $canonical_url );
			}

			return $value;
		}

		if ( is_string( $value ) && 0 === strpos( $value, $placeholder_url ) ) {
			return $canonical_url . substr( $value, strlen( $placeholder_url ) );
		}

		return $value;
	}

	/**
	 * @param string $title
	 * @return bool
	 */
	private static function is_title_length_valid( $title ) {
		$title  = trim( (string) $title );
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );

		return $length >= self::TITLE_MIN_LENGTH && $length <= self::TITLE_MAX_LENGTH;
	}

	/**
	 * @return string
	 */
	private static function current_request_url() {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

		return $scheme . '://' . $host . $uri;
	}

	/**
	 * @param string $url
	 * @return string
	 */
	private static function normalize_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return rtrim( $url );
		}

		$query = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			ksort( $query );
		}

		$normalized  = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) . '://' : '';
		$normalized .= isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';

		if ( isset( $parts['port'] ) ) {
			$normalized .= ':' . (int) $parts['port'];
		}

		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
		if ( '' === $path ) {
			$path = '/';
		}

		// Treat Unicode and percent-encoded path variants as the same URL, but still
		// preserve trailing-slash differences for canonical enforcement.
		$path = rawurldecode( $path );

		// Keep non-root trailing slashes intact so `/ad/hash/slug` redirects to `/ad/hash/slug/`.
		$normalized .= '/' === $path ? '/' : preg_replace( '#/+#', '/', $path );

		if ( ! empty( $query ) ) {
			$normalized .= '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		}

		return $normalized;
	}

	/**
	 * @return bool
	 */
	private static function has_external_canonical_provider() {
		return defined( 'WPSEO_VERSION' )
			|| class_exists( 'WPSEO_Frontend' )
			|| class_exists( 'RankMath' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| class_exists( 'AIOSEO\\Plugin\\AIOSEO' );
	}
}
