<?php
/**
 * Plugin Name: Bornado Auth Modal
 * Description: Unified phone-first authentication modal for Bornado.
 * Version: 1.1.0
 * Author: Bornado
 * Text Domain: bornado-auth-modal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bornado_Auth_Modal' ) ) {
	final class Bornado_Auth_Modal {
		const VERSION = '1.1.0';

		/**
		 * @var Bornado_Auth_Modal|null
		 */
		private static $instance = null;

		/**
		 * @var bool
		 */
		private $modal_rendered = false;

		/**
		 * @var bool
		 */
		private $inline_rendered = false;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {
			add_action( 'init', array( $this, 'register_shortcodes' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 210 );
			add_action( 'wp_footer', array( $this, 'render_modal' ), 5 );

			add_action( 'wp_ajax_bornado_auth_phone_preflight', array( $this, 'ajax_phone_preflight' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_phone_preflight', array( $this, 'ajax_phone_preflight' ) );
			add_action( 'wp_ajax_bornado_auth_resolve_continue_token', array( $this, 'ajax_resolve_continue_token' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_resolve_continue_token', array( $this, 'ajax_resolve_continue_token' ) );
			add_action( 'wp_ajax_bornado_auth_phone_password_login', array( $this, 'ajax_phone_password_login' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_phone_password_login', array( $this, 'ajax_phone_password_login' ) );
			add_action( 'wp_ajax_bornado_auth_firebase_login', array( $this, 'ajax_firebase_login' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_firebase_login', array( $this, 'ajax_firebase_login' ) );
			add_action( 'wp_ajax_bornado_auth_firebase_register', array( $this, 'ajax_firebase_register' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_firebase_register', array( $this, 'ajax_firebase_register' ) );
		}

		public function register_shortcodes() {
			add_shortcode( 'bornado_auth_modal', array( $this, 'render_trigger_shortcode' ) );
			add_shortcode( 'bornado_auth_trigger', array( $this, 'render_trigger_shortcode' ) );
			add_shortcode( 'bornado_auth_inline', array( $this, 'render_inline_shortcode' ) );
		}

		public function enqueue_assets() {
			global $adforest_theme;

			if ( is_admin() ) {
				return;
			}

			$plugin_url  = plugin_dir_url( __FILE__ );
			$plugin_path = plugin_dir_path( __FILE__ );
			$css_path    = $plugin_path . 'assets/css/bornado-auth-modal.css';
			$js_path     = $plugin_path . 'assets/js/bornado-auth-modal.js';
			$script_deps = array( 'jquery' );

			wp_enqueue_style(
				'bornado-auth-modal',
				$plugin_url . 'assets/css/bornado-auth-modal.css',
				array(),
				file_exists( $css_path ) ? (string) filemtime( $css_path ) : self::VERSION
			);

			if ( is_user_logged_in() ) {
				return;
			}

			$this->enqueue_firebase_assets();

			if ( ! empty( $adforest_theme['sb_register_with_phone'] ) && ( wp_script_is( 'firebase-auth', 'registered' ) || wp_script_is( 'firebase-auth', 'enqueued' ) ) ) {
				$script_deps[] = 'firebase-auth';
			}

			wp_enqueue_script(
				'bornado-auth-modal',
				$plugin_url . 'assets/js/bornado-auth-modal.js',
				$script_deps,
				file_exists( $js_path ) ? (string) filemtime( $js_path ) : self::VERSION,
				true
			);

			wp_localize_script( 'bornado-auth-modal', 'bornadoAuthModal', $this->build_frontend_config() );
			$this->cleanup_legacy_guest_auth_assets();
		}

		private function enqueue_firebase_assets() {
			global $adforest_theme;

			if ( empty( $adforest_theme['sb_register_with_phone'] ) ) {
				return;
			}

			if ( ! wp_script_is( 'firebase-app', 'registered' ) && ! wp_script_is( 'firebase-app', 'enqueued' ) ) {
				wp_register_script( 'firebase-app', 'https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js', array(), null, true );
			}
			if ( ! wp_script_is( 'firebase-analytics', 'registered' ) && ! wp_script_is( 'firebase-analytics', 'enqueued' ) ) {
				wp_register_script( 'firebase-analytics', 'https://www.gstatic.com/firebasejs/8.3.2/firebase-analytics.js', array( 'firebase-app' ), null, true );
			}
			if ( ! wp_script_is( 'firebase-auth', 'registered' ) && ! wp_script_is( 'firebase-auth', 'enqueued' ) ) {
				wp_register_script( 'firebase-auth', 'https://www.gstatic.com/firebasejs/8.3.2/firebase-auth.js', array( 'firebase-app' ), null, true );
			}

			wp_enqueue_script( 'firebase-app' );
			wp_enqueue_script( 'firebase-analytics' );
			wp_enqueue_script( 'firebase-auth' );
		}

		private function build_frontend_config() {
			global $adforest_theme;

			$strings = function_exists( 'adforest_get_static_string_fun' ) ? adforest_get_static_string_fun() : array();
			$profile = $this->get_profile_url();
			$sign_in = isset( $adforest_theme['sb_sign_in_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_in_page'] ) : 0;
			$sign_up = isset( $adforest_theme['sb_sign_up_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_up_page'] ) : 0;

			return array(
				'ajaxUrl'                => apply_filters( 'adforest_set_query_param', admin_url( 'admin-ajax.php' ) ),
				'afterLoginUrl'          => isset( $strings['sb_after_login_page'] ) ? (string) $strings['sb_after_login_page'] : home_url( '/' ),
				'profileUrl'             => $profile,
				'registerRedirectUrl'    => $profile,
				'phonePreflightNonce'    => wp_create_nonce( 'bornado_auth_phone_preflight' ),
				'continueTokenNonce'     => wp_create_nonce( 'bornado_auth_resolve_continue_token' ),
				'phonePasswordLoginNonce'=> wp_create_nonce( 'bornado_auth_phone_password_login' ),
				'firebaseLoginNonce'     => wp_create_nonce( 'bornado_auth_firebase_login' ),
				'firebaseRegisterNonce'  => wp_create_nonce( 'bornado_auth_firebase_register' ),
				'phoneEnabled'           => ! empty( $adforest_theme['sb_register_with_phone'] ),
				'registerEnabled'        => (bool) get_option( 'users_can_register' ),
				'signInUrl'              => $sign_in ? get_permalink( $sign_in ) : '',
				'signUpUrl'              => $sign_up ? get_permalink( $sign_up ) : '',
				'privacyUrl'             => $this->get_privacy_url(),
				'isRtl'                  => is_rtl(),
				'firebase'               => array(
					'apiKey'            => isset( $adforest_theme['sb_firebase_apikey'] ) ? (string) $adforest_theme['sb_firebase_apikey'] : '',
					'projectId'         => isset( $adforest_theme['sb_firebase_projectId'] ) ? (string) $adforest_theme['sb_firebase_projectId'] : '',
					'messagingSenderId' => isset( $adforest_theme['sb_firebase_messagingSenderId'] ) ? (string) $adforest_theme['sb_firebase_messagingSenderId'] : '',
					'appId'             => isset( $adforest_theme['sb_firebase_appId'] ) ? (string) $adforest_theme['sb_firebase_appId'] : '',
				),
				'phoneCountries'         => function_exists( 'bornado_get_phone_country_options' ) ? bornado_get_phone_country_options() : array(),
				'defaultPhoneCountry'    => function_exists( 'bornado_get_default_phone_country_option' ) ? bornado_get_default_phone_country_option() : array(),
				'i18n'                   => array(
					'loading'               => __( 'در حال پردازش...', 'bornado-auth-modal' ),
					'genericError'          => __( 'خطایی رخ داد. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
					'networkError'          => __( 'ارتباط با سرور برقرار نشد. دوباره تلاش کنید.', 'bornado-auth-modal' ),
					'invalidPhone'          => __( 'شماره موبایل را با فرمت صحیح وارد کنید.', 'bornado-auth-modal' ),
					'passwordRequired'      => __( 'رمز عبور را وارد کنید.', 'bornado-auth-modal' ),
					'passwordMismatch'      => __( 'رمز عبور و تکرار آن یکسان نیست.', 'bornado-auth-modal' ),
					'passwordTooShort'      => __( 'رمز عبور باید حداقل ۶ کاراکتر باشد.', 'bornado-auth-modal' ),
					'termsRequired'         => __( 'برای ادامه باید قوانین را بپذیرید.', 'bornado-auth-modal' ),
					'otpRequired'           => __( 'کد تایید را وارد کنید.', 'bornado-auth-modal' ),
					'otpLength'             => __( 'کد تایید باید ۶ رقم باشد.', 'bornado-auth-modal' ),
					'verifyRecaptcha'       => __( 'برای ادامه، تایید امنیتی را کامل کنید.', 'bornado-auth-modal' ),
					'wrongOtp'              => __( 'کد واردشده صحیح نیست.', 'bornado-auth-modal' ),
					'verificationSent'      => __( 'کد تایید برای شماره شما ارسال شد.', 'bornado-auth-modal' ),
					'phoneLoginSuccess'     => __( 'ورود با موفقیت انجام شد.', 'bornado-auth-modal' ),
					'phoneRegisterSuccess'  => __( 'عضویت با موفقیت انجام شد.', 'bornado-auth-modal' ),
					'phoneVerified'         => __( 'شماره موبایل تایید شد. حالا رمز عبور حساب را مشخص کنید.', 'bornado-auth-modal' ),
					'resendCode'            => __( 'ارسال دوباره کد', 'bornado-auth-modal' ),
					'resendIn'              => __( 'ارسال دوباره تا', 'bornado-auth-modal' ),
					'seconds'               => __( 'ثانیه', 'bornado-auth-modal' ),
					'changeNumber'          => __( 'تغییر شماره', 'bornado-auth-modal' ),
					'backToPassword'        => __( 'بازگشت به رمز عبور', 'bornado-auth-modal' ),
					'continueLabel'         => __( 'ادامه', 'bornado-auth-modal' ),
					'loginLabel'            => __( 'ورود', 'bornado-auth-modal' ),
					'verifyLabel'           => __( 'تایید کد', 'bornado-auth-modal' ),
					'completeSignupLabel'   => __( 'تکمیل عضویت', 'bornado-auth-modal' ),
					'otpFallbackLabel'      => __( 'ورود با کد یکبار مصرف', 'bornado-auth-modal' ),
					'passwordErrorWithOtp'  => __( 'رمز عبور صحیح نیست.', 'bornado-auth-modal' ),
					'countryLabel'          => __( 'کد کشور', 'bornado-auth-modal' ),
					'rememberMe'            => __( 'مرا به خاطر بسپار', 'bornado-auth-modal' ),
					'defaultTitle'          => __( 'ورود یا عضویت', 'bornado-auth-modal' ),
					'defaultSubtitle'       => __( 'شماره موبایل خود را وارد کنید', 'bornado-auth-modal' ),
					'passwordTitle'         => __( 'ورود با رمز عبور', 'bornado-auth-modal' ),
					'passwordSubtitle'      => '',
					'otpTitle'              => __( 'کد تایید', 'bornado-auth-modal' ),
					'otpSubtitle'           => '',
					'setupTitle'            => __( 'تکمیل عضویت', 'bornado-auth-modal' ),
					'setupSubtitle'         => '',
					'claimLoginSubtitle'    => __( 'این آگهی با شماره %s ثبت شده است. باید با همین شماره وارد شوید تا احراز مالکیت ادامه پیدا کند.', 'bornado-auth-modal' ),
					'claimRegisterSubtitle' => __( 'این آگهی با شماره %s ثبت شده است. با تایید همین شماره، احراز مالکیت ادامه پیدا می‌کند.', 'bornado-auth-modal' ),
					'nameOptional'          => __( 'نام و نام خانوادگی (اختیاری)', 'bornado-auth-modal' ),
					'nameOptionalPlaceholder'=> __( 'نام و نام خانوادگی', 'bornado-auth-modal' ),
				),
			);
		}

		public function render_trigger_shortcode( $atts = array() ) {
			$atts = shortcode_atts(
				array(
					'label'  => '',
					'mode'   => 'login',
					'method' => 'phone',
					'tag'    => 'button',
					'class'  => '',
				),
				$atts,
				'bornado_auth_modal'
			);

			if ( is_user_logged_in() ) {
				return $this->render_logged_in_shortcode_state( $atts );
			}

			$mode     = 'register' === strtolower( $atts['mode'] ) ? 'register' : 'login';
			$method   = 'email' === strtolower( $atts['method'] ) ? 'email' : 'phone';
			$tag      = 'a' === strtolower( $atts['tag'] ) ? 'a' : 'button';
			$label    = trim( (string) $atts['label'] );
			$class    = trim( (string) $atts['class'] );
			$fallback = 'register' === $mode ? $this->get_sign_up_url() : $this->get_sign_in_url();

			if ( '' === $label ) {
				$label = 'register' === $mode ? __( 'عضویت', 'bornado-auth-modal' ) : __( 'ورود', 'bornado-auth-modal' );
			}

			$attr = sprintf(
				'data-bornado-auth-open="1" data-mode="%1$s" data-method="%2$s"',
				esc_attr( $mode ),
				esc_attr( $method )
			);

			if ( 'a' === $tag ) {
				return sprintf(
					'<a href="%1$s" class="%2$s" %3$s>%4$s</a>',
					esc_url( $fallback ? $fallback : '#' ),
					esc_attr( trim( 'bornado-auth-trigger ' . $class ) ),
					$attr,
					esc_html( $label )
				);
			}

			return sprintf(
				'<button type="button" class="%1$s" %2$s>%3$s</button>',
				esc_attr( trim( 'bornado-auth-trigger adt-button-dark ' . $class ) ),
				$attr,
				esc_html( $label )
			);
		}

		private function render_logged_in_shortcode_state( $atts = array() ) {
			$user = wp_get_current_user();
			if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
				return '';
			}

			$display_name = trim( (string) $user->display_name );
			if ( '' === $display_name ) {
				$display_name = trim( (string) $user->user_login );
			}

			$card_class = trim( 'bornado-auth-member-card ' . (string) $atts['class'] );
			$links      = $this->get_logged_in_shortcode_links();

			ob_start();
			?>
			<div class="<?php echo esc_attr( $card_class ); ?>">
				<div class="bornado-auth-member-card__hero">
					<a class="bornado-auth-member-card__avatar" href="<?php echo esc_url( $links['dashboard']['url'] ); ?>" aria-label="<?php echo esc_attr__( 'رفتن به حساب کاربری', 'bornado-auth-modal' ); ?>">
						<?php echo $this->get_user_avatar_markup( $user->ID, 72, $display_name ); ?>
					</a>
					<div class="bornado-auth-member-card__copy">
						<span class="bornado-auth-member-card__eyebrow"><?php esc_html_e( 'حساب کاربری فعال', 'bornado-auth-modal' ); ?></span>
						<h3><?php echo esc_html( sprintf( __( 'سلام %s', 'bornado-auth-modal' ), $display_name ) ); ?></h3>
						<p><?php esc_html_e( 'شما همین حالا وارد حساب کاربری خود هستید. از اینجا می‌توانید سریع به بخش‌های مهم حساب، آگهی‌ها و پیام‌ها دسترسی داشته باشید.', 'bornado-auth-modal' ); ?></p>
					</div>
				</div>

				<div class="bornado-auth-member-card__actions">
					<?php if ( ! empty( $links['dashboard']['url'] ) ) : ?>
						<a class="bornado-auth-member-card__button bornado-auth-member-card__button--primary" href="<?php echo esc_url( $links['dashboard']['url'] ); ?>">
							<?php echo esc_html( $links['dashboard']['label'] ); ?>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $links['post_ad']['url'] ) ) : ?>
						<a class="bornado-auth-member-card__button bornado-auth-member-card__button--secondary" href="<?php echo esc_url( $links['post_ad']['url'] ); ?>">
							<?php echo esc_html( $links['post_ad']['label'] ); ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="bornado-auth-member-card__grid">
					<?php foreach ( $links as $key => $link ) : ?>
						<?php if ( in_array( $key, array( 'dashboard', 'post_ad' ), true ) || empty( $link['url'] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<a class="bornado-auth-member-card__tile" href="<?php echo esc_url( $link['url'] ); ?>">
							<strong><?php echo esc_html( $link['label'] ); ?></strong>
							<span><?php echo esc_html( $link['description'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php

			return (string) ob_get_clean();
		}

		public function render_modal() {
			if ( is_admin() || is_user_logged_in() || $this->modal_rendered || $this->inline_rendered ) {
				return;
			}

			$this->modal_rendered = true;
			$this->render_auth_surface(
				array(
					'display' => 'modal',
					'mode'    => 'login',
					'class'   => '',
				)
			);
		}

		public function render_inline_shortcode( $atts = array() ) {
			$atts = shortcode_atts(
				array(
					'mode'  => 'login',
					'class' => '',
				),
				$atts,
				'bornado_auth_inline'
			);

			if ( is_user_logged_in() ) {
				return $this->render_logged_in_shortcode_state( $atts );
			}

			if ( $this->inline_rendered ) {
				return '';
			}

			$this->inline_rendered = true;

			ob_start();
			$this->render_auth_surface(
				array(
					'display' => 'inline',
					'mode'    => 'register' === strtolower( (string) $atts['mode'] ) ? 'register' : 'login',
					'class'   => trim( (string) $atts['class'] ),
				)
			);

			return (string) ob_get_clean();
		}

		private function render_auth_surface( $args = array() ) {
			$display     = ( isset( $args['display'] ) && 'inline' === $args['display'] ) ? 'inline' : 'modal';
			$default_mode = ( isset( $args['mode'] ) && 'register' === $args['mode'] ) ? 'register' : 'login';
			$class       = isset( $args['class'] ) ? trim( (string) $args['class'] ) : '';
			$privacy_url = $this->get_privacy_url();
			$root_id     = 'inline' === $display ? 'bornado-auth-inline' : 'bornado-auth-modal';
			$root_class  = 'inline' === $display
				? trim( 'bornado-auth-inline ' . $class )
				: trim( 'modal fade bornado-auth-modal ' . $class );
			?>
			<div class="<?php echo esc_attr( $root_class ); ?>" id="<?php echo esc_attr( $root_id ); ?>" data-default-mode="<?php echo esc_attr( $default_mode ); ?>" <?php echo 'modal' === $display ? 'tabindex="-1" aria-labelledby="bornado-auth-modal-title" aria-hidden="true"' : ''; ?>>
				<?php if ( 'modal' === $display ) : ?>
					<div class="modal-dialog modal-dialog-centered modal-lg">
						<div class="modal-content">
				<?php endif; ?>
				<div class="bornado-auth-modal__shell">
					<div class="modal-header bornado-auth-modal__header">
						<?php if ( 'modal' === $display ) : ?>
							<button type="button" class="btn-close bornado-auth-modal__close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'بستن', 'bornado-auth-modal' ); ?>"></button>
						<?php endif; ?>
						<div class="bornado-auth-modal__hero">
							<h2 class="modal-title" id="bornado-auth-modal-title"><?php esc_html_e( 'ورود یا عضویت', 'bornado-auth-modal' ); ?></h2>
							<p class="bornado-auth-modal__subtitle" id="bornado-auth-modal-subtitle"><?php esc_html_e( 'شماره موبایل خود را وارد کنید', 'bornado-auth-modal' ); ?></p>
						</div>
					</div>

					<div class="bornado-auth-modal__body">
						<div class="bornado-auth-modal__notice" id="bornado-auth-notice" role="status" aria-live="polite" hidden></div>

						<div class="bornado-auth-views">
							<section class="bornado-auth-view is-active" data-view="phone-entry">
								<form id="bornado-auth-phone-entry-form" novalidate>
									<div class="bornado-auth-field">
										<label for="bornado-auth-phone-entry"><?php esc_html_e( 'شماره موبایل', 'bornado-auth-modal' ); ?></label>
										<input type="tel" id="bornado-auth-phone-entry" name="phone_number" inputmode="tel" autocomplete="tel" placeholder="9121234567">
									</div>
									<div class="bornado-auth-actions">
										<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'ادامه', 'bornado-auth-modal' ); ?></button>
									</div>
								</form>
							</section>

							<section class="bornado-auth-view" data-view="password-login">
								<form id="bornado-auth-password-login-form" novalidate>
									<div class="bornado-auth-field">
										<label for="bornado-auth-password-login-input"><?php esc_html_e( 'رمز عبور', 'bornado-auth-modal' ); ?></label>
										<input type="password" id="bornado-auth-password-login-input" name="password" autocomplete="current-password" placeholder="<?php esc_attr_e( 'رمز عبور', 'bornado-auth-modal' ); ?>">
									</div>
									<label class="bornado-auth-check">
										<input type="checkbox" id="bornado-auth-password-login-remember" name="remember" value="1" checked>
										<span><?php esc_html_e( 'مرا به خاطر بسپار', 'bornado-auth-modal' ); ?></span>
									</label>
									<div class="bornado-auth-actions">
										<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'ورود', 'bornado-auth-modal' ); ?></button>
									</div>
								</form>
								<div class="bornado-auth-secondary-links">
									<button type="button" class="bornado-auth-link" id="bornado-auth-login-with-otp"><?php esc_html_e( 'ورود با کد یکبار مصرف', 'bornado-auth-modal' ); ?></button>
									<button type="button" class="bornado-auth-link bornado-auth-link--muted" data-auth-reset="1"><?php esc_html_e( 'تغییر شماره', 'bornado-auth-modal' ); ?></button>
								</div>
							</section>

							<section class="bornado-auth-view" data-view="otp">
								<form id="bornado-auth-otp-form" novalidate>
									<div class="bornado-auth-field">
										<label for="bornado-auth-otp-code"><?php esc_html_e( 'کد تایید', 'bornado-auth-modal' ); ?></label>
										<input type="text" id="bornado-auth-otp-code" name="otp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="123456">
									</div>
									<div id="bornado-auth-firebase-recaptcha" class="bornado-auth-recaptcha bornado-auth-recaptcha--firebase"></div>
									<div class="bornado-auth-otp-meta">
										<button type="button" class="bornado-auth-link" id="bornado-auth-back-to-password" hidden><?php esc_html_e( 'بازگشت به رمز عبور', 'bornado-auth-modal' ); ?></button>
										<button type="button" class="bornado-auth-link" data-auth-reset="1"><?php esc_html_e( 'تغییر شماره', 'bornado-auth-modal' ); ?></button>
										<button type="button" class="bornado-auth-link" id="bornado-auth-resend-code"><?php esc_html_e( 'ارسال دوباره کد', 'bornado-auth-modal' ); ?></button>
										<span id="bornado-auth-resend-timer" hidden></span>
									</div>
									<div class="bornado-auth-actions">
										<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'تایید کد', 'bornado-auth-modal' ); ?></button>
									</div>
								</form>
							</section>

							<section class="bornado-auth-view" data-view="setup-account">
								<form id="bornado-auth-setup-form" novalidate>
									<div class="bornado-auth-field">
										<label for="bornado-auth-setup-name"><?php esc_html_e( 'نام و نام خانوادگی (اختیاری)', 'bornado-auth-modal' ); ?></label>
										<input type="text" id="bornado-auth-setup-name" name="display_name" autocomplete="name" placeholder="<?php esc_attr_e( 'نام و نام خانوادگی', 'bornado-auth-modal' ); ?>">
									</div>
									<div class="bornado-auth-grid">
										<div class="bornado-auth-field">
											<label for="bornado-auth-setup-password"><?php esc_html_e( 'رمز عبور', 'bornado-auth-modal' ); ?></label>
											<input type="password" id="bornado-auth-setup-password" name="password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'رمز عبور', 'bornado-auth-modal' ); ?>">
										</div>
										<div class="bornado-auth-field">
											<label for="bornado-auth-setup-password-confirm"><?php esc_html_e( 'تکرار رمز عبور', 'bornado-auth-modal' ); ?></label>
											<input type="password" id="bornado-auth-setup-password-confirm" name="password_confirm" autocomplete="new-password" placeholder="<?php esc_attr_e( 'تکرار رمز عبور', 'bornado-auth-modal' ); ?>">
										</div>
									</div>
									<label class="bornado-auth-check">
										<input type="checkbox" id="bornado-auth-setup-terms" required>
										<span>
											<?php esc_html_e( 'با قوانین و حریم خصوصی موافقم', 'bornado-auth-modal' ); ?>
											<?php if ( $privacy_url ) : ?>
												<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'مشاهده', 'bornado-auth-modal' ); ?></a>
											<?php endif; ?>
										</span>
									</label>
									<div class="bornado-auth-actions">
										<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'تکمیل عضویت', 'bornado-auth-modal' ); ?></button>
									</div>
								</form>
								<div class="bornado-auth-secondary-links">
									<button type="button" class="bornado-auth-link bornado-auth-link--muted" data-auth-reset="1"><?php esc_html_e( 'تغییر شماره', 'bornado-auth-modal' ); ?></button>
								</div>
							</section>
						</div>
					</div>
				</div>
				<?php if ( 'modal' === $display ) : ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		public function ajax_phone_preflight() {
			$this->verify_ajax_nonce( 'bornado_auth_phone_preflight', 'security' );
			$this->guard_demo_mode();

			$phone_dial_code = isset( $_POST['phone_dial_code'] ) ? wp_unslash( $_POST['phone_dial_code'] ) : '';
			$phone           = $this->normalize_phone_number( isset( $_POST['phone_number'] ) ? wp_unslash( $_POST['phone_number'] ) : '', $phone_dial_code );

			if ( ! $this->is_valid_phone_number( $phone ) ) {
				wp_send_json_error( array( 'message' => __( 'شماره موبایل معتبر نیست.', 'bornado-auth-modal' ) ), 422 );
			}

			$resolution = $this->resolve_phone_auth_flow( $phone );
			if ( is_wp_error( $resolution ) ) {
				wp_send_json_error( array( 'message' => $resolution->get_error_message() ), 403 );
			}

			wp_send_json_success( $resolution );
		}

		public function ajax_resolve_continue_token() {
			$this->verify_ajax_nonce( 'bornado_auth_resolve_continue_token', 'security' );

			$token   = isset( $_POST['continue_token'] ) ? wp_unslash( $_POST['continue_token'] ) : '';
			$payload = $this->parse_continue_token( $token );

			if ( is_wp_error( $payload ) ) {
				wp_send_json_error( array( 'message' => $payload->get_error_message() ), 422 );
			}

			$phone = $this->normalize_phone_number( isset( $payload['phone'] ) ? (string) $payload['phone'] : '' );
			if ( ! $this->is_valid_phone_number( $phone ) ) {
				wp_send_json_error( array( 'message' => __( 'شماره موبایل معتبر نیست.', 'bornado-auth-modal' ) ), 422 );
			}

			$resolution = $this->resolve_phone_auth_flow( $phone );
			if ( is_wp_error( $resolution ) ) {
				wp_send_json_error( array( 'message' => $resolution->get_error_message() ), 403 );
			}

			$redirect_url = isset( $payload['redirect_url'] ) ? esc_url_raw( (string) $payload['redirect_url'] ) : '';
			if ( '' === $redirect_url ) {
				$redirect_url = $this->get_profile_url();
			}

			wp_send_json_success(
				array(
					'mode'          => isset( $resolution['mode'] ) ? (string) $resolution['mode'] : 'login',
					'next_step'     => isset( $resolution['next_step'] ) ? (string) $resolution['next_step'] : '',
					'existing_user' => ! empty( $resolution['existing_user'] ),
					'phone_number'  => isset( $resolution['phone_number'] ) ? (string) $resolution['phone_number'] : $phone,
					'redirect_url'  => $redirect_url,
					'claim_ad_id'   => ! empty( $payload['claim_ad_id'] ) ? absint( $payload['claim_ad_id'] ) : 0,
					'remember'      => '1',
				)
			);
		}

		public function ajax_phone_password_login() {
			$this->verify_ajax_nonce( 'bornado_auth_phone_password_login', 'security' );
			$this->guard_demo_mode();

			$phone_dial_code = isset( $_POST['phone_dial_code'] ) ? wp_unslash( $_POST['phone_dial_code'] ) : '';
			$phone           = $this->normalize_phone_number( isset( $_POST['phone_number'] ) ? wp_unslash( $_POST['phone_number'] ) : '', $phone_dial_code );
			$password        = (string) wp_unslash( $_POST['password'] ?? '' );
			$remember        = ! empty( $_POST['remember'] ) && '1' === (string) wp_unslash( $_POST['remember'] );

			if ( ! $this->is_valid_phone_number( $phone ) ) {
				wp_send_json_error( array( 'message' => __( 'شماره موبایل معتبر نیست.', 'bornado-auth-modal' ) ), 422 );
			}

			if ( '' === trim( $password ) ) {
				wp_send_json_error( array( 'message' => __( 'رمز عبور را وارد کنید.', 'bornado-auth-modal' ) ), 422 );
			}

			$user_id = $this->find_user_id_by_phone( $phone );
			if ( ! $user_id ) {
				wp_send_json_error( array( 'message' => __( 'کاربری با این شماره پیدا نشد.', 'bornado-auth-modal' ) ), 404 );
			}

			$user = get_user_by( 'ID', $user_id );
			if ( ! $user instanceof WP_User ) {
				wp_send_json_error( array( 'message' => __( 'حساب کاربری معتبر نیست.', 'bornado-auth-modal' ) ), 404 );
			}

			if ( empty( $user->roles ) || count( $user->roles ) === 0 ) {
				wp_send_json_error( array( 'message' => __( 'حساب شما هنوز تایید نشده است.', 'bornado-auth-modal' ) ), 403 );
			}

			if ( ! wp_check_password( $password, $user->user_pass, $user_id ) ) {
				wp_send_json_error( array( 'message' => __( 'رمز عبور صحیح نیست.', 'bornado-auth-modal' ) ), 422 );
			}

			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, $remember, is_ssl() );

			do_action( 'bornado_auth_modal_phone_login_success', $user_id, $phone );

			wp_send_json_success(
				array(
					'message'      => __( 'ورود با موفقیت انجام شد.', 'bornado-auth-modal' ),
					'phone_number' => $phone,
				)
			);
		}

		public function ajax_firebase_login() {
			$this->verify_ajax_nonce( 'bornado_auth_firebase_login', 'security' );
			$this->guard_demo_mode();

			$phone_dial_code = isset( $_POST['phone_dial_code'] ) ? wp_unslash( $_POST['phone_dial_code'] ) : '';
			$requested_phone = $this->normalize_phone_number( isset( $_POST['phone_number'] ) ? wp_unslash( $_POST['phone_number'] ) : '', $phone_dial_code );
			$remember        = ! empty( $_POST['remember'] ) && '1' === (string) wp_unslash( $_POST['remember'] );
			$claim_ad_id     = ! empty( $_POST['claim_ad_id'] ) ? absint( wp_unslash( $_POST['claim_ad_id'] ) ) : 0;
			$token_result    = $this->verify_firebase_identity_token( isset( $_POST['id_token'] ) ? wp_unslash( $_POST['id_token'] ) : '' );

			if ( is_wp_error( $token_result ) ) {
				wp_send_json_error( array( 'message' => $this->translate_auth_error_message( $token_result->get_error_message() ) ), 422 );
			}

			$verified_phone = $this->normalize_phone_number( $token_result['phone_number'] );
			if ( $requested_phone && $verified_phone !== $requested_phone ) {
				wp_send_json_error( array( 'message' => __( 'شماره تاییدشده با شماره درخواستی یکسان نیست.', 'bornado-auth-modal' ) ), 409 );
			}

			$user_id = $this->find_user_id_by_phone( $verified_phone );
			if ( ! $user_id ) {
				wp_send_json_error( array( 'message' => __( 'کاربری با این شماره پیدا نشد.', 'bornado-auth-modal' ) ), 404 );
			}

			$user = get_user_by( 'ID', $user_id );
			if ( ! $user instanceof WP_User ) {
				wp_send_json_error( array( 'message' => __( 'حساب کاربری معتبر نیست.', 'bornado-auth-modal' ) ), 404 );
			}

			update_user_meta( $user_id, '_sb_is_ph_verified', '1' );
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, $remember, is_ssl() );

			do_action(
				'bornado_auth_modal_firebase_login_success',
				$user_id,
				$verified_phone,
				array(
					'event'        => 'firebase_login',
					'firebase_uid' => isset( $token_result['firebase_uid'] ) ? (string) $token_result['firebase_uid'] : '',
					'claim_ad_id'  => $claim_ad_id,
				)
			);
			do_action( 'bornado_auth_modal_phone_login_success', $user_id, $verified_phone );

			wp_send_json_success(
				array(
					'message'      => __( 'ورود با موفقیت انجام شد.', 'bornado-auth-modal' ),
					'phone_number' => $verified_phone,
				)
			);
		}

		public function ajax_firebase_register() {
			global $adforest_theme;

			$this->verify_ajax_nonce( 'bornado_auth_firebase_register', 'security' );
			$this->guard_demo_mode();

			if ( ! get_option( 'users_can_register' ) ) {
				wp_send_json_error( array( 'message' => __( 'ثبت‌نام در حال حاضر غیرفعال است.', 'bornado-auth-modal' ) ), 403 );
			}

			$phone_dial_code = isset( $_POST['phone_dial_code'] ) ? wp_unslash( $_POST['phone_dial_code'] ) : '';
			$requested_phone = $this->normalize_phone_number( isset( $_POST['phone_number'] ) ? wp_unslash( $_POST['phone_number'] ) : '', $phone_dial_code );
			$display_name    = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
			$password        = (string) wp_unslash( $_POST['password'] ?? '' );
			$remember        = ! empty( $_POST['remember'] ) && '1' === (string) wp_unslash( $_POST['remember'] );
			$claim_ad_id     = ! empty( $_POST['claim_ad_id'] ) ? absint( wp_unslash( $_POST['claim_ad_id'] ) ) : 0;
			$token_result    = $this->verify_firebase_identity_token( isset( $_POST['id_token'] ) ? wp_unslash( $_POST['id_token'] ) : '' );

			if ( '' === trim( $password ) ) {
				wp_send_json_error( array( 'message' => __( 'رمز عبور را وارد کنید.', 'bornado-auth-modal' ) ), 422 );
			}

			if ( strlen( $password ) < 6 ) {
				wp_send_json_error( array( 'message' => __( 'رمز عبور باید حداقل ۶ کاراکتر باشد.', 'bornado-auth-modal' ) ), 422 );
			}

			if ( is_wp_error( $token_result ) ) {
				wp_send_json_error( array( 'message' => $this->translate_auth_error_message( $token_result->get_error_message() ) ), 422 );
			}

			$verified_phone = $this->normalize_phone_number( $token_result['phone_number'] );
			if ( $requested_phone && $verified_phone !== $requested_phone ) {
				wp_send_json_error( array( 'message' => __( 'شماره تاییدشده با شماره درخواستی یکسان نیست.', 'bornado-auth-modal' ) ), 409 );
			}

			if ( $this->find_user_id_by_phone( $verified_phone ) ) {
				wp_send_json_error( array( 'message' => __( 'این شماره قبلا ثبت شده است.', 'bornado-auth-modal' ) ), 409 );
			}

			$phone_digits       = preg_replace( '/\D+/', '', $verified_phone );
			$phone_suffix       = $phone_digits ? substr( $phone_digits, -10 ) : (string) wp_rand( 100000, 999999 );
			$username           = $this->build_unique_username( $display_name, $phone_suffix );
			$final_display_name = '' !== $display_name ? $display_name : $this->build_default_display_name( $verified_phone );

			$user_data = array(
				'user_login'   => $username,
				'user_pass'    => $password,
				'display_name' => $final_display_name,
				'nickname'     => $final_display_name,
			);

			$user_id = wp_insert_user( $user_data );
			if ( is_wp_error( $user_id ) ) {
				wp_send_json_error( array( 'message' => $this->translate_auth_error_message( $user_id->get_error_message() ) ), 422 );
			}

			wp_update_user(
				array(
					'ID'            => $user_id,
					'display_name'  => $final_display_name,
					'nickname'      => $final_display_name,
					'user_nicename' => sanitize_title( $username ),
				)
			);

			update_user_meta( $user_id, '_sb_contact', $verified_phone );
			update_user_meta( $user_id, '_sb_is_ph_verified', '1' );

			if ( function_exists( 'adforest_email_on_new_user' ) ) {
				adforest_email_on_new_user( $user_id, '', false );
			}

			if ( isset( $adforest_theme['sb_allow_pkg_on_reg'] ) && '1' === (string) $adforest_theme['sb_allow_pkg_on_reg'] ) {
				$package_to_assign = isset( $adforest_theme['sb_register_package'] ) ? $adforest_theme['sb_register_package'] : '';
				if ( $package_to_assign && function_exists( 'adforest_give_user_package_from_admin' ) ) {
					adforest_give_user_package_from_admin( $package_to_assign, $user_id, true );
				}
			}

			$user_role = $adforest_theme['sb_user_role_on_registeration'] ?? 'none';
			if ( 'dealer' === $user_role ) {
				update_user_meta( $user_id, '_sb_user_type', 'Dealer' );
			} elseif ( 'individual' === $user_role ) {
				update_user_meta( $user_id, '_sb_user_type', 'Indiviual' );
			}

			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, $remember, is_ssl() );

			do_action(
				'bornado_auth_modal_firebase_register_success',
				$user_id,
				$verified_phone,
				array(
					'event'        => 'firebase_register',
					'firebase_uid' => isset( $token_result['firebase_uid'] ) ? (string) $token_result['firebase_uid'] : '',
					'claim_ad_id'  => $claim_ad_id,
				)
			);
			do_action( 'bornado_auth_modal_phone_register_success', $user_id, $verified_phone );

			wp_send_json_success(
				array(
					'message'      => __( 'عضویت با موفقیت انجام شد.', 'bornado-auth-modal' ),
					'phone_number' => $verified_phone,
				)
			);
		}

		private function verify_ajax_nonce( $action, $key ) {
			$nonce = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error( array( 'message' => __( 'اعتبار امنیتی درخواست نامعتبر است.', 'bornado-auth-modal' ) ), 403 );
			}
		}

		private function guard_demo_mode() {
			if ( function_exists( 'adforest_is_demo' ) && adforest_is_demo() ) {
				wp_send_json_error( array( 'message' => __( 'در حالت دمو این عملیات مجاز نیست.', 'bornado-auth-modal' ) ), 403 );
			}
		}

		private function verify_firebase_identity_token( $id_token ) {
			global $adforest_theme;

			$id_token = trim( (string) $id_token );
			$api_key  = isset( $adforest_theme['sb_firebase_apikey'] ) ? trim( (string) $adforest_theme['sb_firebase_apikey'] ) : '';

			if ( '' === $id_token ) {
				return new WP_Error( 'missing_token', __( 'توکن تایید فایربیس دریافت نشد.', 'bornado-auth-modal' ) );
			}

			if ( '' === $api_key ) {
				return new WP_Error( 'missing_api_key', __( 'تنظیمات فایربیس کامل نیست.', 'bornado-auth-modal' ) );
			}

			$response = wp_remote_post(
				'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . rawurlencode( $api_key ),
				array(
					'timeout' => 20,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'idToken' => $id_token,
						)
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'firebase_lookup_failed', __( 'اعتبارسنجی فایربیس انجام نشد. دوباره تلاش کنید.', 'bornado-auth-modal' ) );
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $code || empty( $body['users'][0]['phoneNumber'] ) ) {
				$message = __( 'تایید شماره موبایل انجام نشد. دوباره تلاش کنید.', 'bornado-auth-modal' );
				if ( ! empty( $body['error']['message'] ) ) {
					$message = $this->translate_auth_error_message( sanitize_text_field( $body['error']['message'] ) );
				}
				return new WP_Error( 'invalid_firebase_token', $message );
			}

			return array(
				'phone_number' => (string) $body['users'][0]['phoneNumber'],
				'firebase_uid' => isset( $body['users'][0]['localId'] ) ? (string) $body['users'][0]['localId'] : '',
			);
		}

		private function find_user_id_by_phone( $phone_number ) {
			global $wpdb;

			$phone_number = $this->normalize_phone_number( $phone_number );
			if ( '' === $phone_number ) {
				return 0;
			}

			$candidates = array_unique(
				array_filter(
					array(
						$phone_number,
						ltrim( $phone_number, '+' ),
						'00' . ltrim( $phone_number, '+' ),
						str_replace( ' ', '', $phone_number ),
					)
				)
			);

			if ( empty( $candidates ) ) {
				return 0;
			}

			$placeholders = implode( ',', array_fill( 0, count( $candidates ), '%s' ) );
			$query        = $wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_sb_contact' AND meta_value IN ($placeholders) LIMIT 1",
				$candidates
			);
			$user_id      = $wpdb->get_var( $query );

			return $user_id ? (int) $user_id : 0;
		}

		private function normalize_phone_number( $phone_number, $phone_dial_code = '' ) {
			if ( function_exists( 'bornado_normalize_phone_with_dial_code' ) ) {
				$normalized = bornado_normalize_phone_with_dial_code( $phone_number, $phone_dial_code );
				if ( '' !== $normalized ) {
					return $normalized;
				}
			}

			$phone_number = trim( (string) $phone_number );
			$phone_number = preg_replace( '/[^\d+]/', '', $phone_number );

			if ( '' === $phone_number ) {
				return '';
			}

			if ( 0 === strpos( $phone_number, '00' ) ) {
				$phone_number = '+' . substr( $phone_number, 2 );
			} elseif ( '+' !== substr( $phone_number, 0, 1 ) ) {
				$phone_number = '+' . ltrim( $phone_number, '+' );
			}

			$phone_number = '+' . preg_replace( '/[^\d]/', '', $phone_number );

			return $phone_number;
		}

		private function is_valid_phone_number( $phone_number ) {
			return (bool) preg_match( '/^\+\d{8,16}$/', $phone_number );
		}

		/**
		 * Return the minimal auth resolution for one normalized phone number.
		 *
		 * @param string $phone Normalized phone number.
		 * @return array<string,mixed>|WP_Error
		 */
		private function resolve_phone_auth_flow( $phone ) {
			$phone = $this->normalize_phone_number( $phone );
			if ( ! $this->is_valid_phone_number( $phone ) ) {
				return new WP_Error( 'invalid_phone', __( 'شماره موبایل معتبر نیست.', 'bornado-auth-modal' ) );
			}

			$user_id = $this->find_user_id_by_phone( $phone );
			if ( $user_id > 0 ) {
				return array(
					'phone_number'    => $phone,
					'existing_user'   => true,
					'next_step'       => 'password',
					'mode'            => 'login',
					'allow_otp_login' => true,
				);
			}

			if ( ! get_option( 'users_can_register' ) ) {
				return new WP_Error(
					'registration_disabled',
					__( 'برای این شماره حسابی پیدا نشد و ثبت‌نام نیز غیرفعال است.', 'bornado-auth-modal' )
				);
			}

			return array(
				'phone_number'    => $phone,
				'existing_user'   => false,
				'next_step'       => 'otp',
				'mode'            => 'register',
				'allow_otp_login' => false,
			);
		}

		private function build_unique_username( $display_name, $phone_suffix ) {
			$display_name = trim( (string) $display_name );
			$phone_suffix = preg_replace( '/\D+/', '', (string) $phone_suffix );

			$username = sanitize_user( remove_accents( $display_name ), true );
			if ( function_exists( 'adforest_check_user_name' ) ) {
				$username = adforest_check_user_name( $username );
			}
			$username = sanitize_user( (string) $username, true );

			if ( '' === $username ) {
				$username = 'bornado-' . ( $phone_suffix ? $phone_suffix : wp_rand( 100000, 999999 ) );
			}

			$base_username = $username;
			$suffix        = 1;
			while ( username_exists( $username ) ) {
				$username = sanitize_user( $base_username . '-' . $suffix, true );
				++$suffix;
			}

			return $username;
		}

		private function build_default_display_name( $phone_number ) {
			$digits = preg_replace( '/\D+/', '', (string) $phone_number );
			$tail   = '' !== $digits ? substr( $digits, -4 ) : '';

			if ( '' === $tail ) {
				return __( 'کاربر برنادو', 'bornado-auth-modal' );
			}

			return sprintf( __( 'کاربر %s', 'bornado-auth-modal' ), $tail );
		}

		private function get_sign_in_url() {
			global $adforest_theme;

			$page_id = isset( $adforest_theme['sb_sign_in_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_in_page'] ) : 0;

			return $page_id ? (string) get_permalink( $page_id ) : '';
		}

		private function get_sign_up_url() {
			global $adforest_theme;

			$page_id = isset( $adforest_theme['sb_sign_up_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_up_page'] ) : 0;

			return $page_id ? (string) get_permalink( $page_id ) : '';
		}

		private function get_logged_in_shortcode_links() {
			return array(
				'dashboard' => array(
					'label'       => __( 'داشبورد من', 'bornado-auth-modal' ),
					'description' => __( 'مدیریت حساب و آگهی‌ها', 'bornado-auth-modal' ),
					'url'         => $this->get_profile_url(),
				),
				'post_ad'   => array(
					'label'       => __( 'ثبت آگهی جدید', 'bornado-auth-modal' ),
					'description' => __( 'آگهی تازه ثبت کنید', 'bornado-auth-modal' ),
					'url'         => $this->get_post_ad_url(),
				),
				'messages'  => array(
					'label'       => __( 'پیام‌ها', 'bornado-auth-modal' ),
					'description' => __( 'گفتگوها و پیام‌های دریافتی', 'bornado-auth-modal' ),
					'url'         => $this->get_dashboard_page_url( 'sb_modern_messages_page', 'msg' ),
				),
				'favorites' => array(
					'label'       => __( 'علاقه‌مندی‌ها', 'bornado-auth-modal' ),
					'description' => __( 'آگهی‌های ذخیره‌شده شما', 'bornado-auth-modal' ),
					'url'         => $this->get_dashboard_page_url( 'sb_modern_favorites_page', 'fav_ads' ),
				),
				'settings'  => array(
					'label'       => __( 'تنظیمات حساب', 'bornado-auth-modal' ),
					'description' => __( 'ویرایش پروفایل و اطلاعات شخصی', 'bornado-auth-modal' ),
					'url'         => $this->get_dashboard_page_url( 'sb_modern_settings_page', 'my_profile' ),
				),
			);
		}

		private function get_dashboard_page_url( $page_option_key, $fallback_page_type = '' ) {
			global $adforest_theme;

			$page_id = isset( $adforest_theme[ $page_option_key ] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme[ $page_option_key ] ) : 0;
			if ( $page_id ) {
				$url = get_permalink( $page_id );
				if ( $url ) {
					return (string) $url;
				}
			}

			$profile_url = $this->get_profile_url();
			if ( '' !== $profile_url && '' !== $fallback_page_type ) {
				return (string) add_query_arg( 'page_type', $fallback_page_type, $profile_url );
			}

			return $profile_url;
		}

		private function get_post_ad_url() {
			global $adforest_theme;

			$modern_page_id = isset( $adforest_theme['sb_modern_post_ad_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_modern_post_ad_page'] ) : 0;
			if ( $modern_page_id ) {
				$url = get_permalink( $modern_page_id );
				if ( $url ) {
					return (string) $url;
				}
			}

			$page_id = isset( $adforest_theme['sb_post_ad_page'] ) ? $adforest_theme['sb_post_ad_page'] : 0;
			$page_id = apply_filters( 'adforest_ad_post_verified_id', $page_id );
			$page_id = apply_filters( 'adforest_language_page_id', $page_id );

			if ( $page_id ) {
				$url = get_permalink( $page_id );
				if ( $url ) {
					return (string) $url;
				}
			}

			return home_url( '/' );
		}

		private function get_user_avatar_markup( $user_id, $size = 72, $alt = '' ) {
			$user_id = absint( $user_id );
			$size    = max( 24, absint( $size ) );
			$alt     = trim( (string) $alt );

			if ( $user_id <= 0 ) {
				return get_avatar( 0, $size, '', $alt );
			}

			if ( function_exists( 'sbchat_get_user_avatar' ) ) {
				return wp_kses(
					sbchat_get_user_avatar( $user_id, $size ),
					array(
						'img' => array(
							'alt'    => true,
							'src'    => true,
							'class'  => true,
							'height' => true,
							'width'  => true,
							'loading'=> true,
							'decoding' => true,
							'srcset' => true,
							'sizes'  => true,
						),
					)
				);
			}

			$custom_avatar_url = $this->get_user_custom_avatar_url( $user_id, $size );
			if ( '' !== $custom_avatar_url ) {
				return sprintf(
					'<img alt="%1$s" src="%2$s" class="avatar avatar-%3$d photo" height="%3$d" width="%3$d" />',
					esc_attr( $alt ),
					esc_url( $custom_avatar_url ),
					(int) $size
				);
			}

			return get_avatar( $user_id, $size, '', $alt );
		}

		private function get_user_custom_avatar_url( $user_id, $size = 72 ) {
			global $adforest_theme;

			$attach_id = absint( get_user_meta( $user_id, '_sb_user_pic', true ) );
			if ( $attach_id > 0 ) {
				$image_link = wp_get_attachment_image_src( $attach_id, array( $size, $size ) );
				if ( is_array( $image_link ) && ! empty( $image_link[0] ) ) {
					return (string) $image_link[0];
				}
			}

			if ( isset( $adforest_theme['sb_user_dp']['url'] ) && '' !== trim( (string) $adforest_theme['sb_user_dp']['url'] ) ) {
				return (string) $adforest_theme['sb_user_dp']['url'];
			}

			return trailingslashit( get_template_directory_uri() ) . 'images/users/9.jpg';
		}

		/**
		 * Remove legacy guest-auth assets when the modal owns auth UX.
		 *
		 * Keep the old assets only on the dedicated fallback sign-in/sign-up pages.
		 *
		 * @return void
		 */
		private function cleanup_legacy_guest_auth_assets() {
			if ( is_admin() || is_user_logged_in() || $this->is_legacy_auth_page() ) {
				return;
			}

			if ( wp_script_is( 'adforest-phone-otp-login', 'enqueued' ) ) {
				wp_dequeue_script( 'adforest-phone-otp-login' );
			}

			if ( wp_script_is( 'firebase-custom', 'enqueued' ) ) {
				wp_dequeue_script( 'firebase-custom' );
			}
		}

		/**
		 * True only on the theme's dedicated sign-in/sign-up pages.
		 *
		 * @return bool
		 */
		private function is_legacy_auth_page() {
			global $adforest_theme;

			$sign_in_page_id = isset( $adforest_theme['sb_sign_in_page'] ) ? (int) apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_in_page'] ) : 0;
			$sign_up_page_id = isset( $adforest_theme['sb_sign_up_page'] ) ? (int) apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_up_page'] ) : 0;
			$current_page_id = (int) get_queried_object_id();

			if ( $current_page_id <= 0 ) {
				return false;
			}

			return in_array( $current_page_id, array_filter( array( $sign_in_page_id, $sign_up_page_id ) ), true );
		}

		private function get_privacy_url() {
			$url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';

			return is_string( $url ) ? $url : '';
		}

		private function get_profile_url() {
			global $adforest_theme;

			$page_id = isset( $adforest_theme['sb_profile_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_profile_page'] ) : 0;
			if ( $page_id ) {
				$url = get_permalink( $page_id );
				if ( $url ) {
					return (string) $url;
				}
			}

			return home_url( '/profile/' );
		}

		private function parse_continue_token( $token ) {
			$token = trim( (string) $token );
			if ( '' === $token || false === strpos( $token, '.' ) ) {
				return new WP_Error( 'invalid_token', __( 'لینک ورود معتبر نیست.', 'bornado-auth-modal' ) );
			}

			list( $encoded_payload, $provided_signature ) = explode( '.', $token, 2 );
			$secret = $this->get_notification_shared_secret();
			if ( '' === $secret ) {
				return new WP_Error( 'missing_secret', __( 'تنظیمات امنیتی کامل نیست.', 'bornado-auth-modal' ) );
			}

			$expected_signature = hash_hmac( 'sha256', $encoded_payload, $secret );
			if ( ! hash_equals( $expected_signature, (string) $provided_signature ) ) {
				return new WP_Error( 'invalid_signature', __( 'لینک ورود معتبر نیست.', 'bornado-auth-modal' ) );
			}

			$decoded_payload = $this->base64_url_decode( $encoded_payload );
			$payload         = json_decode( $decoded_payload, true );

			if ( ! is_array( $payload ) ) {
				return new WP_Error( 'invalid_payload', __( 'اطلاعات لینک معتبر نیست.', 'bornado-auth-modal' ) );
			}

			if ( empty( $payload['purpose'] ) || 'listing_manage_continue' !== $payload['purpose'] ) {
				return new WP_Error( 'invalid_purpose', __( 'این لینک برای این عملیات معتبر نیست.', 'bornado-auth-modal' ) );
			}

			if ( empty( $payload['exp'] ) || (int) $payload['exp'] < time() ) {
				return new WP_Error( 'expired_link', __( 'زمان استفاده از این لینک به پایان رسیده است.', 'bornado-auth-modal' ) );
			}

			return $payload;
		}

		private function get_notification_shared_secret() {
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

		private function base64_url_decode( $value ) {
			$value = strtr( (string) $value, '-_', '+/' );
			$pad   = strlen( $value ) % 4;

			if ( $pad > 0 ) {
				$value .= str_repeat( '=', 4 - $pad );
			}

			$decoded = base64_decode( $value, true );

			return false !== $decoded ? $decoded : '';
		}

		private function translate_auth_error_message( $message ) {
			$message = trim( wp_strip_all_tags( (string) $message ) );

			if ( '' === $message ) {
				return __( 'خطایی رخ داد. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' );
			}

			$translations = array(
				'INVALID_ID_TOKEN'                               => __( 'اعتبار نشست تایید نامعتبر است. دوباره کد بگیر.', 'bornado-auth-modal' ),
				'TOKEN_EXPIRED'                                  => __( 'زمان تایید به پایان رسیده است. دوباره کد بگیر.', 'bornado-auth-modal' ),
				'USER_DISABLED'                                  => __( 'این حساب غیرفعال شده است.', 'bornado-auth-modal' ),
				'TOO_MANY_ATTEMPTS_TRY_LATER'                    => __( 'تلاش‌های زیادی انجام شده. کمی بعد دوباره امتحان کن.', 'bornado-auth-modal' ),
				'PHONE_NUMBER_EXISTS'                            => __( 'این شماره قبلا ثبت شده است.', 'bornado-auth-modal' ),
				'INVALID_PHONE_NUMBER'                           => __( 'شماره موبایل معتبر نیست.', 'bornado-auth-modal' ),
				'Incorrect password.'                            => __( 'رمز عبور صحیح نیست.', 'bornado-auth-modal' ),
				'Cannot create a user with an empty nicename.'   => __( 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
				'Could not save password reset key to database.' => __( 'ارسال لینک بازیابی انجام نشد. دوباره تلاش کنید.', 'bornado-auth-modal' ),
			);

			if ( isset( $translations[ $message ] ) ) {
				return $translations[ $message ];
			}

			$contains_map = array(
				'nicename'             => __( 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
				'empty username'       => __( 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
				'verification expired' => __( 'زمان تایید به پایان رسیده است. دوباره کد بگیر.', 'bornado-auth-modal' ),
				'expired'              => __( 'زمان این درخواست به پایان رسیده است. دوباره تلاش کن.', 'bornado-auth-modal' ),
				'phone number'         => __( 'شماره موبایل معتبر نیست یا قبلا ثبت شده است.', 'bornado-auth-modal' ),
				'password'             => __( 'رمز عبور صحیح نیست.', 'bornado-auth-modal' ),
				'security'             => __( 'اعتبار امنیتی درخواست نامعتبر است. صفحه را تازه‌سازی کن و دوباره تلاش کن.', 'bornado-auth-modal' ),
			);

			$lower_message = strtolower( $message );
			foreach ( $contains_map as $needle => $translation ) {
				if ( false !== strpos( $lower_message, $needle ) ) {
					return $translation;
				}
			}

			return $message;
		}
	}

	Bornado_Auth_Modal::instance();
}

if ( ! function_exists( 'bornado_auth_modal_fallback_url' ) ) {
	function bornado_auth_modal_fallback_url( $mode = 'login' ) {
		global $adforest_theme;

		$page_key = 'register' === $mode ? 'sb_sign_up_page' : 'sb_sign_in_page';
		$page_id  = isset( $adforest_theme[ $page_key ] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme[ $page_key ] ) : 0;

		return $page_id ? (string) get_permalink( $page_id ) : '#';
	}
}

if ( ! function_exists( 'bornado_auth_modal_profile_url' ) ) {
	function bornado_auth_modal_profile_url() {
		global $adforest_theme;

		$page_id = isset( $adforest_theme['sb_profile_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_profile_page'] ) : 0;
		if ( $page_id ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return (string) $url;
			}
		}

		return home_url( '/profile/' );
	}
}

if ( ! function_exists( 'bornado_auth_modal_trigger_attrs' ) ) {
	function bornado_auth_modal_trigger_attrs( $mode = 'login', $method = 'phone' ) {
		$mode   = 'register' === $mode ? 'register' : 'login';
		$method = 'email' === $method ? 'email' : 'phone';

		return sprintf(
			'data-bornado-auth-open="1" data-mode="%1$s" data-method="%2$s"',
			esc_attr( $mode ),
			esc_attr( $method )
		);
	}
}
