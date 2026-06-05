<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Ad_Ownership_Claim_Bridge {

	/**
	 * Bootstrap the claim bridge.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 215 );
	}

	/**
	 * Enqueue assets for the smart-claim CTA.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		global $adforest_theme;

		if ( is_admin() || ! is_singular( 'ad_post' ) || empty( $adforest_theme['allow_claim'] ) ) {
			return;
		}

		$ad_id   = get_queried_object_id();
		$context = self::get_claim_context( $ad_id );
		if ( empty( $context['has_phone'] ) ) {
			return;
		}

		$script_path = BORNADO_AD_OWNERSHIP_SYNC_PATH . 'assets/js/bornado-ad-ownership-sync.js';
		wp_enqueue_script(
			'bornado-ad-ownership-sync',
			BORNADO_AD_OWNERSHIP_SYNC_URL . 'assets/js/bornado-ad-ownership-sync.js',
			array( 'jquery' ),
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : BORNADO_AD_OWNERSHIP_SYNC_VERSION,
			true
		);

		wp_localize_script(
			'bornado-ad-ownership-sync',
			'bornadoOwnershipClaim',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'bornado_phone_claim_transfer' ),
				'loadingText'  => 'در حال بررسی و انتقال...',
				'genericError' => 'خطایی رخ داد. لطفاً دوباره تلاش کنید.',
				'reloadDelay'  => 1400,
			)
		);
	}

	/**
	 * Build all smart-claim UI data for one listing.
	 *
	 * @param int $ad_id Listing ID.
	 * @return array<string,mixed>
	 */
	public static function get_claim_context( $ad_id ) {
		$ad_id            = absint( $ad_id );
		$canonical_phone  = self::get_listing_claim_phone( $ad_id );
		$is_logged_in     = is_user_logged_in();
		$current_user_id  = get_current_user_id();
		$current_owner_id = (int) get_post_field( 'post_author', $ad_id );
		$current_phone    = '';
		$is_verified      = false;

		$context = array(
			'has_phone'      => '' !== $canonical_phone,
			'canonical_phone'=> $canonical_phone,
			'display_phone'  => $canonical_phone,
			'mode'           => 'legacy',
			'note'           => '',
			'action_label'   => '',
			'action_url'     => '',
			'action_type'    => '',
			'ad_id'          => $ad_id,
		);

		if ( '' === $canonical_phone ) {
			return $context;
		}

		$claim_entry_url = self::build_claim_entry_url( $ad_id, $canonical_phone );
		$logout_entry    = $claim_entry_url ? wp_logout_url( $claim_entry_url ) : '';

		if ( ! $is_logged_in ) {
			$context['mode']         = 'smart_login';
			$context['note']         = sprintf(
				'برای احراز مالکیت این آگهی با شماره %s وارد شوید. پس از تایید شماره، همه آگهی‌های ثبت‌شده با همین شماره به حساب شما منتقل می‌شود.',
				self::get_phone_display_markup( $canonical_phone )
			);
			$context['action_label'] = 'ورود با این شماره و احراز مالکیت';
			$context['action_url']   = $claim_entry_url;
			$context['action_type']  = 'link';
			return $context;
		}

		$current_phone = Bornado_Ad_Ownership_Phone::normalize_user_phone( get_user_meta( $current_user_id, '_sb_contact', true ) );
		$is_verified   = '1' === (string) get_user_meta( $current_user_id, '_sb_is_ph_verified', true );

		if ( $current_owner_id === $current_user_id ) {
			$context['mode']        = 'already_owner';
			$context['note']        = 'این آگهی همین حالا روی حساب کاربری شما قرار دارد.';
			$context['action_type'] = 'none';
			return $context;
		}

		if ( $is_verified && '' !== $current_phone && $current_phone === $canonical_phone ) {
			$context['mode']         = 'smart_transfer';
			$context['note']         = sprintf(
				'شماره تاییدشده حساب شما با شماره این آگهی (%s) یکسان است. با تایید زیر، همه آگهی‌های ثبت‌شده با این شماره فوراً به حساب شما منتقل می‌شود.',
				self::get_phone_display_markup( $canonical_phone )
			);
			$context['action_label'] = 'انتقال همه آگهی‌های این شماره';
			$context['action_type']  = 'ajax-transfer';
			return $context;
		}

		$context['mode']         = 'switch_account';
		$context['note']         = sprintf(
			'این آگهی با شماره %s ثبت شده است. برای احراز مالکیت باید با همین شماره وارد شوید.',
			self::get_phone_display_markup( $canonical_phone )
		);
		$context['action_label'] = 'خروج و ورود با این شماره';
		$context['action_url']   = $logout_entry ? $logout_entry : $claim_entry_url;
		$context['action_type']  = 'link';

		return $context;
	}

	/**
	 * Resolve a canonical phone for one listing.
	 *
	 * @param int $ad_id Listing ID.
	 * @return string
	 */
	public static function get_listing_claim_phone( $ad_id ) {
		$ad_id = absint( $ad_id );
		if ( $ad_id <= 0 || 'ad_post' !== get_post_type( $ad_id ) ) {
			return '';
		}

		$raw_phone   = (string) get_post_meta( $ad_id, '_adforest_poster_contact', true );
		$normalized  = Bornado_Ad_Ownership_Phone::normalize_listing_phone( $ad_id, $raw_phone );
		if ( '' !== $normalized ) {
			return $normalized;
		}

		$stored = Bornado_Ad_Ownership_Phone::normalize_user_phone( get_post_meta( $ad_id, '_bornado_owner_phone_canonical', true ) );
		if ( '' !== $stored ) {
			return $stored;
		}

		return '';
	}

	/**
	 * Build the signed continue-token URL for the auth modal flow.
	 *
	 * @param int    $ad_id Listing ID.
	 * @param string $canonical_phone Canonical phone.
	 * @return string
	 */
	private static function build_claim_entry_url( $ad_id, $canonical_phone ) {
		$ad_id           = absint( $ad_id );
		$canonical_phone = Bornado_Ad_Ownership_Phone::normalize_user_phone( $canonical_phone );
		$redirect_url    = function_exists( 'bornado_auth_modal_profile_url' )
			? (string) bornado_auth_modal_profile_url()
			: home_url( '/profile/' );

		if ( '' === $canonical_phone || '' === $redirect_url ) {
			return '';
		}

		$secret = self::get_shared_secret();
		if ( '' === $secret ) {
			if ( function_exists( 'bornado_get_safe_login_redirect_url' ) ) {
				return (string) bornado_get_safe_login_redirect_url( $redirect_url );
			}

			return $redirect_url;
		}

		$payload = array(
			'purpose'      => 'listing_manage_continue',
			'flow_source'  => 'claim',
			'exp'          => time() + ( 15 * MINUTE_IN_SECONDS ),
			'phone'        => $canonical_phone,
			'redirect_url' => $redirect_url,
			'claim_ad_id'  => $ad_id,
		);

		$encoded_payload = self::base64_url_encode( wp_json_encode( $payload ) );
		$signature       = hash_hmac( 'sha256', $encoded_payload, $secret );
		$token           = $encoded_payload . '.' . $signature;

		return add_query_arg(
			array(
				'bornado_continue_token' => $token,
			),
			$redirect_url
		);
	}

	/**
	 * Read the shared secret used by the auth modal.
	 *
	 * @return string
	 */
	private static function get_shared_secret() {
		if ( defined( 'BORNADO_NOTIFICATION_SHARED_SECRET' ) ) {
			return trim( (string) BORNADO_NOTIFICATION_SHARED_SECRET );
		}

		if ( defined( 'BORNADO_NOTIFICATION_BRIDGE_DIR' ) ) {
			$config_path = trailingslashit( BORNADO_NOTIFICATION_BRIDGE_DIR ) . 'config/bornado-notification-bridge-config.php';
			if ( file_exists( $config_path ) ) {
				require_once $config_path;
				if ( defined( 'BORNADO_NOTIFICATION_SHARED_SECRET' ) ) {
					return trim( (string) BORNADO_NOTIFICATION_SHARED_SECRET );
				}
			}
		}

		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			$config_path = trailingslashit( WP_PLUGIN_DIR ) . 'bornado-notification-bridge/config/bornado-notification-bridge-config.php';
			if ( file_exists( $config_path ) ) {
				require_once $config_path;
				if ( defined( 'BORNADO_NOTIFICATION_SHARED_SECRET' ) ) {
					return trim( (string) BORNADO_NOTIFICATION_SHARED_SECRET );
				}
			}
		}

		return '';
	}

	/**
	 * Wrap an international phone so it stays visually correct in RTL text.
	 *
	 * @param string $phone Canonical phone number.
	 * @return string
	 */
	private static function get_phone_display_markup( $phone ) {
		$phone = Bornado_Ad_Ownership_Phone::normalize_user_phone( $phone );
		if ( '' === $phone ) {
			return '';
		}

		return sprintf(
			'<bdi dir="ltr" class="bornado-inline-phone">%s</bdi>',
			esc_html( $phone )
		);
	}

	/**
	 * Base64 URL-safe encoding helper.
	 *
	 * @param string $value Raw string.
	 * @return string
	 */
	private static function base64_url_encode( $value ) {
		return rtrim( strtr( base64_encode( (string) $value ), '+/', '-_' ), '=' );
	}
}
