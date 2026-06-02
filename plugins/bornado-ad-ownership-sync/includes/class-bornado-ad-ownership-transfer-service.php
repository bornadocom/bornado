<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Ad_Ownership_Transfer_Service {

	const LOG_POST_TYPE         = 'bornado_owner_log';
	const POST_TYPE             = 'ad_post';
	const POST_PHONE_META       = '_adforest_poster_contact';
	const POST_PHONE_CANON_META = '_bornado_owner_phone_canonical';
	const POSTER_NAME_META      = '_adforest_poster_name';
	const FLASH_TRANSIENT_KEY   = 'bornado_ad_transfer_flash_';
	const LOCK_OPTION_KEY       = 'bornado_ad_transfer_lock_';

	/**
	 * Bootstrap runtime hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_log_post_type' ) );
		add_action( 'bornado_auth_modal_firebase_login_success', array( __CLASS__, 'handle_verified_auth' ), 10, 3 );
		add_action( 'bornado_auth_modal_firebase_register_success', array( __CLASS__, 'handle_verified_auth' ), 10, 3 );
		add_action( 'wp_ajax_bornado_execute_phone_claim_transfer', array( __CLASS__, 'ajax_execute_phone_claim_transfer' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_flash_notice' ), 40 );
	}

	/**
	 * Register a private audit trail post type.
	 *
	 * @return void
	 */
	public static function register_log_post_type() {
		register_post_type(
			self::LOG_POST_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Bornado Ownership Logs',
					'singular_name' => 'Bornado Ownership Log',
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'supports'            => array( 'title', 'custom-fields' ),
			)
		);
	}

	/**
	 * Trigger the transfer engine after a verified Firebase auth event.
	 *
	 * @param int   $user_id User ID.
	 * @param string $verified_phone Canonical verified phone.
	 * @param array $context Optional auth context.
	 * @return void
	 */
	public static function handle_verified_auth( $user_id, $verified_phone, $context = array() ) {
		self::transfer_for_user(
			$user_id,
			$verified_phone,
			array(
				'source'      => ! empty( $context['event'] ) ? (string) $context['event'] : 'verified_auth',
				'store_flash' => true,
				'claim_ad_id' => ! empty( $context['claim_ad_id'] ) ? absint( $context['claim_ad_id'] ) : 0,
			)
		);
	}

	/**
	 * Transfer all eligible ads for one verified phone.
	 *
	 * @param int    $user_id Target user ID.
	 * @param string $verified_phone Verified phone.
	 * @param array  $args Execution context.
	 * @return array<string,mixed>
	 */
	public static function transfer_for_user( $user_id, $verified_phone, array $args = array() ) {
		$user_id         = absint( $user_id );
		$canonical_phone = Bornado_Ad_Ownership_Phone::normalize_user_phone( $verified_phone );
		$user            = $user_id > 0 ? get_userdata( $user_id ) : false;
		$claim_ad_id     = ! empty( $args['claim_ad_id'] ) ? absint( $args['claim_ad_id'] ) : 0;
		$store_flash     = ! empty( $args['store_flash'] );
		$source          = ! empty( $args['source'] ) ? sanitize_key( (string) $args['source'] ) : 'unknown';

		$summary = array(
			'source'              => $source,
			'user_id'             => $user_id,
			'claim_ad_id'         => $claim_ad_id,
			'canonical_phone'     => $canonical_phone,
			'matched_count'       => 0,
			'transferred_count'   => 0,
			'already_owned_count' => 0,
			'skipped_count'       => 0,
			'transferred_ids'     => array(),
			'transferred_items'   => array(),
			'already_owned_ids'   => array(),
			'skipped'             => array(),
			'message'             => '',
		);

		if ( $user_id <= 0 || ! ( $user instanceof WP_User ) ) {
			$summary['message'] = 'حساب کاربری مقصد معتبر نیست.';
			self::log_run( $summary );
			return $summary;
		}

		if ( '' === $canonical_phone ) {
			$summary['message'] = 'شماره تاییدشده معتبر نبود و هیچ انتقالی انجام نشد.';
			self::log_run( $summary );
			if ( $store_flash ) {
				self::store_flash_notice( $user_id, $summary );
			}
			return $summary;
		}

		$lock_key = self::LOCK_OPTION_KEY . md5( $user_id . '|' . $canonical_phone );
		if ( ! add_option( $lock_key, (string) time(), '', 'no' ) ) {
			$summary['message'] = 'یک عملیات هم‌زمان برای همین شماره در حال اجراست. چند لحظه دیگر دوباره تلاش کنید.';
			self::log_run( $summary );
			if ( $store_flash ) {
				self::store_flash_notice( $user_id, $summary );
			}
			return $summary;
		}

		try {
			$candidate_ids             = self::get_matching_listing_ids( $canonical_phone );
			$summary['matched_count']  = count( $candidate_ids );
			$display_name              = trim( (string) $user->display_name );

			foreach ( $candidate_ids as $post_id ) {
				$post_id = absint( $post_id );
				if ( $post_id <= 0 ) {
					continue;
				}

				$post = get_post( $post_id );
				if ( ! ( $post instanceof WP_Post ) || self::POST_TYPE !== $post->post_type ) {
					$summary['skipped'][] = array(
						'post_id' => $post_id,
						'reason'  => 'invalid_post',
					);
					continue;
				}

				if ( ! in_array( $post->post_status, self::eligible_statuses(), true ) ) {
					$summary['skipped'][] = array(
						'post_id' => $post_id,
						'reason'  => 'ineligible_status',
					);
					continue;
				}

				$current_raw_phone = (string) get_post_meta( $post_id, self::POST_PHONE_META, true );
				$current_phone     = Bornado_Ad_Ownership_Phone::normalize_listing_phone( $post_id, $current_raw_phone );
				if ( '' === $current_phone ) {
					$current_phone = Bornado_Ad_Ownership_Phone::normalize_user_phone( get_post_meta( $post_id, self::POST_PHONE_CANON_META, true ) );
				}

				if ( $current_phone !== $canonical_phone ) {
					$summary['skipped'][] = array(
						'post_id' => $post_id,
						'reason'  => 'phone_mismatch_after_normalization',
					);
					continue;
				}

				update_post_meta( $post_id, self::POST_PHONE_CANON_META, $canonical_phone );
				if ( $current_raw_phone !== $canonical_phone ) {
					update_post_meta( $post_id, self::POST_PHONE_META, $canonical_phone );
				}

				if ( (int) $post->post_author === $user_id ) {
					if ( '' !== $display_name ) {
						update_post_meta( $post_id, self::POSTER_NAME_META, $display_name );
					}
					$summary['already_owned_ids'][] = $post_id;
					continue;
				}

				$previous_owner_id   = (int) $post->post_author;
				$previous_owner      = $previous_owner_id > 0 ? get_userdata( $previous_owner_id ) : false;
				$previous_owner_name = $previous_owner instanceof WP_User
					? trim( (string) $previous_owner->display_name )
					: '';

				$update_result = wp_update_post(
					array(
						'ID'          => $post_id,
						'post_author' => $user_id,
					),
					true
				);

				if ( is_wp_error( $update_result ) ) {
					$summary['skipped'][] = array(
						'post_id' => $post_id,
						'reason'  => 'wp_update_failed:' . sanitize_key( $update_result->get_error_code() ),
					);
					continue;
				}

				if ( '' !== $display_name ) {
					update_post_meta( $post_id, self::POSTER_NAME_META, $display_name );
				}

				$summary['transferred_ids'][] = $post_id;
				$summary['transferred_items'][] = array(
					'post_id'              => $post_id,
					'from_user_id'         => $previous_owner_id,
					'from_user_name'       => '' !== $previous_owner_name ? $previous_owner_name : ( $previous_owner_id > 0 ? sprintf( 'کاربر #%s', $previous_owner_id ) : 'نامشخص' ),
					'to_user_id'           => $user_id,
					'to_user_name'         => '' !== $display_name ? $display_name : sprintf( 'کاربر #%s', $user_id ),
					'canonical_phone'      => $canonical_phone,
					'transferred_at_gmt'   => gmdate( 'Y-m-d H:i:s' ),
				);
			}
		} finally {
			delete_option( $lock_key );
		}

		$summary['transferred_count']   = count( $summary['transferred_ids'] );
		$summary['already_owned_count'] = count( $summary['already_owned_ids'] );
		$summary['skipped_count']       = count( $summary['skipped'] );
		$summary['message']             = self::build_summary_message( $summary );

		self::log_run( $summary );

		if ( $store_flash ) {
			self::store_flash_notice( $user_id, $summary );
		}

		return $summary;
	}

	/**
	 * AJAX endpoint for the immediate smart-claim flow.
	 *
	 * @return void
	 */
	public static function ajax_execute_phone_claim_transfer() {
		check_ajax_referer( 'bornado_phone_claim_transfer', 'security' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => 'برای ادامه ابتدا وارد حساب کاربری خود شوید.',
				),
				401
			);
		}

		$user_id     = get_current_user_id();
		$ad_id       = isset( $_POST['ad_id'] ) ? absint( $_POST['ad_id'] ) : 0;
		$user_phone  = (string) get_user_meta( $user_id, '_sb_contact', true );
		$is_verified = '1' === (string) get_user_meta( $user_id, '_sb_is_ph_verified', true );
		$ad_phone    = Bornado_Ad_Ownership_Claim_Bridge::get_listing_claim_phone( $ad_id );

		if ( $ad_id <= 0 || self::POST_TYPE !== get_post_type( $ad_id ) ) {
			wp_send_json_error(
				array(
					'message' => 'شناسه آگهی معتبر نیست.',
				),
				400
			);
		}

		if ( ! $is_verified ) {
			wp_send_json_error(
				array(
					'message' => 'برای انتقال این آگهی‌ها باید با شماره تاییدشده وارد شوید.',
				),
				403
			);
		}

		$user_phone = Bornado_Ad_Ownership_Phone::normalize_user_phone( $user_phone );
		if ( '' === $ad_phone || $user_phone !== $ad_phone ) {
			wp_send_json_error(
				array(
					'message' => 'شماره تاییدشده حساب فعلی با شماره ثبت‌شده روی این آگهی یکسان نیست.',
				),
				409
			);
		}

		$summary = self::transfer_for_user(
			$user_id,
			$user_phone,
			array(
				'source'      => 'claim_ajax',
				'store_flash' => true,
				'claim_ad_id' => $ad_id,
			)
		);

		wp_send_json_success(
			array(
				'message'           => $summary['message'],
				'transferred_count' => $summary['transferred_count'],
				'already_owned'     => $summary['already_owned_count'],
				'skipped'           => $summary['skipped_count'],
				'reload'            => true,
			)
		);
	}

	/**
	 * Print a one-time front-end notice after redirect.
	 *
	 * @return void
	 */
	public static function render_flash_notice() {
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$payload = get_transient( self::FLASH_TRANSIENT_KEY . $user_id );
		if ( ! is_array( $payload ) || empty( $payload['message'] ) ) {
			return;
		}

		delete_transient( self::FLASH_TRANSIENT_KEY . $user_id );
		$is_success = ! empty( $payload['transferred_count'] ) || ! empty( $payload['already_owned_count'] );
		?>
		<div class="bornado-ad-transfer-flash <?php echo $is_success ? 'is-success' : 'is-error'; ?>" dir="rtl">
			<button type="button" class="bornado-ad-transfer-flash__close" aria-label="<?php echo esc_attr( 'بستن' ); ?>">&times;</button>
			<div class="bornado-ad-transfer-flash__message"><?php echo esc_html( (string) $payload['message'] ); ?></div>
		</div>
		<style>
			.bornado-ad-transfer-flash{position:fixed;right:24px;bottom:24px;z-index:99999;max-width:420px;padding:14px 18px 14px 44px;border-radius:14px;color:#fff;box-shadow:0 12px 32px rgba(0,0,0,.18);font-size:14px;line-height:1.8}
			.bornado-ad-transfer-flash.is-success{background:#178f52}
			.bornado-ad-transfer-flash.is-error{background:#c0392b}
			.bornado-ad-transfer-flash__close{position:absolute;top:10px;left:12px;border:0;background:transparent;color:#fff;font-size:22px;line-height:1;cursor:pointer}
		</style>
		<script>
			(function(){
				var notice=document.querySelector('.bornado-ad-transfer-flash');
				if(!notice){return;}
				var closeButton=notice.querySelector('.bornado-ad-transfer-flash__close');
				var removeNotice=function(){ if(notice && notice.parentNode){ notice.parentNode.removeChild(notice); } };
				if(closeButton){ closeButton.addEventListener('click', removeNotice); }
				window.setTimeout(removeNotice, 9000);
			})();
		</script>
		<?php
	}

	/**
	 * Persist a short-lived flash summary for the next page load.
	 *
	 * @param int   $user_id User ID.
	 * @param array $summary Transfer summary.
	 * @return void
	 */
	private static function store_flash_notice( $user_id, array $summary ) {
		set_transient( self::FLASH_TRANSIENT_KEY . absint( $user_id ), $summary, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Find matching listing IDs for one canonical phone.
	 *
	 * @param string $canonical_phone Canonical phone.
	 * @return array<int,int>
	 */
	public static function find_listing_ids_for_phone( $canonical_phone ) {
		return self::get_matching_listing_ids( $canonical_phone );
	}

	/**
	 * Find phone-matched listings that fit one dashboard query.
	 *
	 * @param string $canonical_phone Canonical phone.
	 * @param array  $query_args Dashboard query args.
	 * @return array<int,int>
	 */
	public static function find_listing_ids_for_dashboard_query( $canonical_phone, array $query_args ) {
		$phone_ids = self::get_matching_listing_ids( $canonical_phone );
		if ( empty( $phone_ids ) ) {
			return array();
		}

		$post_status = isset( $query_args['post_status'] ) ? $query_args['post_status'] : 'publish';
		if ( is_string( $post_status ) ) {
			$post_status = array( $post_status );
		}

		$lookup_args = array(
			'post_type'      => self::POST_TYPE,
			'post__in'       => array_map( 'intval', $phone_ids ),
			'post_status'    => $post_status,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		if ( ! empty( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
			$lookup_args['meta_query'] = $query_args['meta_query'];
		}

		if ( ! empty( $query_args['meta_key'] ) ) {
			$lookup_args['meta_key'] = $query_args['meta_key'];
		}

		if ( isset( $query_args['meta_value'] ) ) {
			$lookup_args['meta_value'] = $query_args['meta_value'];
		}

		$matched_ids = get_posts( $lookup_args );

		return array_values(
			array_filter(
				array_map( 'intval', (array) $matched_ids )
			)
		);
	}

	/**
	 * Find matching listing IDs for one canonical phone.
	 *
	 * @param string $canonical_phone Canonical phone.
	 * @return array<int,int>
	 */
	private static function get_matching_listing_ids( $canonical_phone ) {
		global $wpdb;

		$canonical_phone = Bornado_Ad_Ownership_Phone::normalize_user_phone( $canonical_phone );
		if ( '' === $canonical_phone ) {
			return array();
		}

		$post_ids = array();

		$direct_ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => self::eligible_statuses(),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => self::POST_PHONE_CANON_META,
				'meta_value'     => $canonical_phone,
			)
		);

		foreach ( $direct_ids as $post_id ) {
			$post_ids[ absint( $post_id ) ] = absint( $post_id );
		}

		$raw_candidates       = Bornado_Ad_Ownership_Phone::build_raw_search_candidates( $canonical_phone );
		$sanitized_candidates = array();
		foreach ( $raw_candidates as $candidate ) {
			$sanitized = Bornado_Ad_Ownership_Phone::sanitize_for_comparison( $candidate );
			if ( '' !== $sanitized ) {
				$sanitized_candidates[] = $sanitized;
			}
		}
		$sanitized_candidates = array_values( array_unique( $sanitized_candidates ) );

		if ( empty( $raw_candidates ) || empty( $sanitized_candidates ) ) {
			return array_values( $post_ids );
		}

		$status_placeholders    = implode( ',', array_fill( 0, count( self::eligible_statuses() ), '%s' ) );
		$raw_placeholders       = implode( ',', array_fill( 0, count( $raw_candidates ), '%s' ) );
		$sanitized_placeholders = implode( ',', array_fill( 0, count( $sanitized_candidates ), '%s' ) );
		$sanitized_sql          = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(pm.meta_value, ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '\r', ''), '\n', ''), '\t', '')";

		$query_args = array_merge(
			array(
				self::POST_PHONE_META,
				self::POST_TYPE,
			),
			self::eligible_statuses(),
			$raw_candidates,
			$sanitized_candidates
		);

		$query = $wpdb->prepare(
			"SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm
				ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND p.post_status IN ($status_placeholders)
				AND (
					pm.meta_value IN ($raw_placeholders)
					OR $sanitized_sql IN ($sanitized_placeholders)
				)",
			$query_args
		);

		$matched_ids = $wpdb->get_col( $query );
		foreach ( (array) $matched_ids as $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id > 0 ) {
				$post_ids[ $post_id ] = $post_id;
			}
		}

		return array_values( $post_ids );
	}

	/**
	 * Build the end-user summary message.
	 *
	 * @param array $summary Transfer summary.
	 * @return string
	 */
	private static function build_summary_message( array $summary ) {
		$transferred   = ! empty( $summary['transferred_count'] ) ? absint( $summary['transferred_count'] ) : 0;
		$already_owned = ! empty( $summary['already_owned_count'] ) ? absint( $summary['already_owned_count'] ) : 0;
		$matched       = ! empty( $summary['matched_count'] ) ? absint( $summary['matched_count'] ) : 0;

		if ( $transferred > 0 ) {
			return sprintf(
				'انتقال مالکیت با موفقیت انجام شد. %1$s آگهی به حساب شما منتقل شد%2$s.',
				number_format_i18n( $transferred ),
				$already_owned > 0 ? sprintf( ' و %1$s آگهی از قبل روی حساب شما بود', number_format_i18n( $already_owned ) ) : ''
			);
		}

		if ( $already_owned > 0 && $matched === $already_owned ) {
			return '';
		}

		if ( $matched > 0 ) {
			return 'چند آگهی منطبق پیدا شد، اما هیچ انتقال معتبری انجام نشد. جزئیات برای بررسی ثبت شد.';
		}

		return 'هیچ آگهی منطبقی با این شماره پیدا نشد.';
	}

	/**
	 * Persist one audit record.
	 *
	 * @param array $summary Transfer summary.
	 * @return void
	 */
	private static function log_run( array $summary ) {
		$title = sprintf(
			'Ownership sync | user:%1$s | phone:%2$s | %3$s',
			! empty( $summary['user_id'] ) ? absint( $summary['user_id'] ) : 0,
			! empty( $summary['canonical_phone'] ) ? (string) $summary['canonical_phone'] : 'n/a',
			gmdate( 'Y-m-d H:i:s' )
		);

		$log_id = wp_insert_post(
			array(
				'post_type'   => self::LOG_POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $log_id ) || $log_id <= 0 ) {
			return;
		}

		update_post_meta( $log_id, 'bornado_transfer_summary', $summary );
	}

	/**
	 * Eligible statuses chosen for this rollout.
	 *
	 * @return array<int,string>
	 */
	private static function eligible_statuses() {
		return array( 'publish', 'pending', 'expired' );
	}
}
