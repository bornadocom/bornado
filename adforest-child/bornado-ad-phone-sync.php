<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Ad_Phone_Sync {

	const POST_TYPE       = 'ad_post';
	const LOCATION_TAXONOMY = 'ad_country';
	const POST_PHONE_META = '_adforest_poster_contact';
	const ISSUE_META      = '_bornado_phone_sync_issue';

	/**
	 * Guard against recursion during internal updates.
	 *
	 * @var array<int,bool>
	 */
	private static $processing_posts = array();

	/**
	 * Bootstrap hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_sb_ad_posting', array( __CLASS__, 'prefilter_ajax_phone_number' ), 0 );
		add_action( 'set_object_terms', array( __CLASS__, 'handle_set_object_terms' ), 20, 6 );
		add_action( 'added_post_meta', array( __CLASS__, 'handle_phone_meta_change' ), 20, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'handle_phone_meta_change' ), 20, 4 );
		add_action( 'rest_after_insert_' . self::POST_TYPE, array( __CLASS__, 'handle_rest_insert' ), 20, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	/**
	 * Normalize the posted phone number before AdForest saves it.
	 *
	 * @return void
	 */
	public static function prefilter_ajax_phone_number() {
		if ( empty( $_POST['sb_data'] ) || ! function_exists( 'bornado_get_country_phone_payload_for_post' ) ) {
			return;
		}

		parse_str( wp_unslash( $_POST['sb_data'] ), $params );
		if ( ! is_array( $params ) ) {
			return;
		}

		$raw_phone = isset( $params['ad_contact_number'] ) ? trim( (string) $params['ad_contact_number'] ) : '';
		if ( '' === $raw_phone ) {
			return;
		}

		$posted_dial_code = isset( $params['bornado_phone_dial_code'] ) ? trim( (string) $params['bornado_phone_dial_code'] ) : '';
		if ( '' !== $posted_dial_code && class_exists( 'Bornado_Country_Phone_Service' ) ) {
			$payload = Bornado_Country_Phone_Service::normalize_phone_for_country( $raw_phone, $posted_dial_code );
			if ( ! empty( $payload['is_valid'] ) && ! empty( $payload['normalized_phone'] ) ) {
				$params['ad_contact_number'] = (string) $payload['normalized_phone'];
				$_POST['sb_data']            = wp_slash( http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) );

				return;
			}
		}

		$location_term_id = self::get_location_term_id_from_params( $params );
		if ( $location_term_id < 1 ) {
			return;
		}

		$payload = Bornado_Country_Phone_Service::get_phone_payload_for_location( $location_term_id, $raw_phone );
		if ( empty( $payload['is_valid'] ) || empty( $payload['normalized_phone'] ) ) {
			return;
		}

		$params['ad_contact_number'] = (string) $payload['normalized_phone'];
		$_POST['sb_data']            = wp_slash( http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) );
	}

	/**
	 * Re-check the phone whenever location terms are changed.
	 *
	 * @param int          $object_id Object ID.
	 * @param array<mixed> $terms Assigned terms.
	 * @param array<mixed> $tt_ids Term taxonomy IDs.
	 * @param string       $taxonomy Taxonomy slug.
	 * @param bool         $append Append flag.
	 * @param array<mixed> $old_tt_ids Previous term taxonomy IDs.
	 * @return void
	 */
	public static function handle_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		unset( $terms, $tt_ids, $append, $old_tt_ids );

		if ( self::LOCATION_TAXONOMY !== $taxonomy ) {
			return;
		}

		$post_id = (int) $object_id;
		if ( ! self::should_process_post( $post_id ) ) {
			return;
		}

		self::sync_post_phone( $post_id );
	}

	/**
	 * Re-sync the phone after direct meta writes.
	 *
	 * @param int    $meta_id Meta ID.
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public static function handle_phone_meta_change( $meta_id, $post_id, $meta_key, $meta_value ) {
		unset( $meta_id, $meta_value );

		if ( self::POST_PHONE_META !== $meta_key ) {
			return;
		}

		$post_id = (int) $post_id;
		if ( ! self::should_process_post( $post_id ) ) {
			return;
		}

		self::sync_post_phone( $post_id );
	}

	/**
	 * Final REST safeguard after the post, terms, and meta have been inserted.
	 *
	 * @param WP_Post         $post Inserted post.
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating Create flag.
	 * @return void
	 */
	public static function handle_rest_insert( $post, $request, $creating ) {
		unset( $request, $creating );

		if ( ! ( $post instanceof WP_Post ) || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( ! self::should_process_post( (int) $post->ID ) ) {
			return;
		}

		self::sync_post_phone( (int) $post->ID );
	}

	/**
	 * Show an admin notice when the phone cannot be normalized.
	 *
	 * @return void
	 */
	public static function render_admin_notice() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		if ( $post_id < 1 ) {
			return;
		}

		$issue = get_post_meta( $post_id, self::ISSUE_META, true );
		if ( ! is_string( $issue ) || '' === $issue ) {
			return;
		}

		$message = self::get_issue_message( $issue );
		if ( '' === $message ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Normalize and enforce the expected phone number for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private static function sync_post_phone( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 || isset( self::$processing_posts[ $post_id ] ) || ! function_exists( 'bornado_get_country_phone_payload_for_post' ) ) {
			return;
		}

		self::$processing_posts[ $post_id ] = true;

		try {
			$payload = bornado_get_country_phone_payload_for_post( $post_id );

			if ( empty( $payload['is_valid'] ) || empty( $payload['normalized_phone'] ) ) {
				self::store_issue( $post_id, ! empty( $payload['reason'] ) ? (string) $payload['reason'] : 'invalid_phone_format' );
				self::maybe_force_pending( $post_id );
				return;
			}

			$normalized_phone = (string) $payload['normalized_phone'];
			if ( (string) get_post_meta( $post_id, self::POST_PHONE_META, true ) !== $normalized_phone ) {
				update_post_meta( $post_id, self::POST_PHONE_META, $normalized_phone );
			}

			delete_post_meta( $post_id, self::ISSUE_META );
		} finally {
			unset( self::$processing_posts[ $post_id ] );
		}
	}

	/**
	 * Move invalid posts to pending review.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private static function maybe_force_pending( $post_id ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( in_array( $post->post_status, array( 'auto-draft', 'inherit', 'trash', 'pending' ), true ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'          => (int) $post_id,
				'post_status' => 'pending',
			)
		);
	}

	/**
	 * Persist the latest phone-validation issue.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $issue Issue code.
	 * @return void
	 */
	private static function store_issue( $post_id, $issue ) {
		update_post_meta( $post_id, self::ISSUE_META, sanitize_key( (string) $issue ) );
	}

	/**
	 * Determine whether a post can be processed by the sync layer.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function should_process_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 || isset( self::$processing_posts[ $post_id ] ) ) {
			return false;
		}

		return self::POST_TYPE === get_post_type( $post_id );
	}

	/**
	 * Resolve the deepest selected location term from AJAX params.
	 *
	 * @param array<string,mixed> $params Submitted params.
	 * @return int
	 */
	private static function get_location_term_id_from_params( array $params ) {
		$keys = array(
			'ad_country_towns',
			'ad_country_cities',
			'ad_country_states',
			'ad_country',
		);

		foreach ( $keys as $key ) {
			if ( ! empty( $params[ $key ] ) ) {
				return absint( $params[ $key ] );
			}
		}

		return 0;
	}

	/**
	 * Convert an issue code into a human-readable admin notice.
	 *
	 * @param string $issue Issue code.
	 * @return string
	 */
	private static function get_issue_message( $issue ) {
		switch ( (string) $issue ) {
			case 'missing_location':
				return 'این آگهی به حالت در انتظار بازبینی رفت چون بدون کشور یا شهر معتبر، نرمال‌سازی شماره تماس ممکن نبود.';
			case 'missing_country':
				return 'این آگهی به حالت در انتظار بازبینی رفت چون کشور ریشه برای لوکیشن انتخاب‌شده قابل تشخیص نبود و در نتیجه شماره تماس نهایی نشد.';
			case 'missing_country_phone_dial_code':
				return 'این آگهی به حالت در انتظار بازبینی رفت چون برای کشور انتخاب‌شده هنوز Phone Dial Code تعریف نشده است.';
			case 'missing_phone':
				return 'این آگهی به حالت در انتظار بازبینی رفت چون شماره تماس آگهی خالی است و بدون شماره معتبر ثبت نهایی انجام نشد.';
			case 'phone_country_mismatch':
				return 'این آگهی به حالت در انتظار بازبینی رفت چون شماره تماس واردشده با کد تلفن کشور انتخاب‌شده هم‌خوان نیست.';
			case 'invalid_phone_format':
			default:
				return 'این آگهی به حالت در انتظار بازبینی رفت چون شماره تماس واردشده قابل نرمال‌سازی به فرمت بین‌المللی معتبر نبود.';
		}
	}
}

Bornado_Ad_Phone_Sync::init();
