<?php
/**
 * Plugin Name: Bornado Auth Modal
 * Description: Reusable modal-based authentication for Bornado with Firebase phone auth and AdForest email/password flows.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-auth-modal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bornado_Auth_Modal' ) ) {
	final class Bornado_Auth_Modal {
		const VERSION = '1.0.0';

		/**
		 * @var Bornado_Auth_Modal|null
		 */
		private static $instance = null;

		/**
		 * @var bool
		 */
		private $modal_rendered = false;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {
			add_action( 'init', array( $this, 'register_shortcodes' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 210 );
			// Render the modal before footer scripts so the frontend controller can bind to it.
			add_action( 'wp_footer', array( $this, 'render_modal' ), 5 );

			add_action( 'wp_ajax_bornado_auth_phone_preflight', array( $this, 'ajax_phone_preflight' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_phone_preflight', array( $this, 'ajax_phone_preflight' ) );
			add_action( 'wp_ajax_bornado_auth_resolve_continue_token', array( $this, 'ajax_resolve_continue_token' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_resolve_continue_token', array( $this, 'ajax_resolve_continue_token' ) );
			add_action( 'wp_ajax_bornado_auth_firebase_login', array( $this, 'ajax_firebase_login' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_firebase_login', array( $this, 'ajax_firebase_login' ) );
			add_action( 'wp_ajax_bornado_auth_firebase_register', array( $this, 'ajax_firebase_register' ) );
			add_action( 'wp_ajax_nopriv_bornado_auth_firebase_register', array( $this, 'ajax_firebase_register' ) );
		}

		public function register_shortcodes() {
			add_shortcode( 'bornado_auth_modal', array( $this, 'render_trigger_shortcode' ) );
			add_shortcode( 'bornado_auth_trigger', array( $this, 'render_trigger_shortcode' ) );
		}

		public function enqueue_assets() {
			global $adforest_theme;

			if ( is_admin() || is_user_logged_in() ) {
				return;
			}

			$plugin_url  = plugin_dir_url( __FILE__ );
			$plugin_path = plugin_dir_path( __FILE__ );
			$css_path    = $plugin_path . 'assets/css/bornado-auth-modal.css';
			$js_path     = $plugin_path . 'assets/js/bornado-auth-modal.js';
			$script_deps = array( 'jquery' );

			$this->enqueue_google_recaptcha_assets();
			$this->enqueue_firebase_assets();

			if ( ! empty( $adforest_theme['sb_register_with_phone'] ) && ( wp_script_is( 'firebase-auth', 'registered' ) || wp_script_is( 'firebase-auth', 'enqueued' ) ) ) {
				$script_deps[] = 'firebase-auth';
			}

			wp_enqueue_style(
				'bornado-auth-modal',
				$plugin_url . 'assets/css/bornado-auth-modal.css',
				array(),
				file_exists( $css_path ) ? (string) filemtime( $css_path ) : self::VERSION
			);

			wp_enqueue_script(
				'bornado-auth-modal',
				$plugin_url . 'assets/js/bornado-auth-modal.js',
				$script_deps,
				file_exists( $js_path ) ? (string) filemtime( $js_path ) : self::VERSION,
				true
			);

			wp_localize_script(
				'bornado-auth-modal',
				'bornadoAuthModal',
				$this->build_frontend_config()
			);
		}

		private function enqueue_google_recaptcha_assets() {
			global $adforest_theme;

			$captcha_type = isset( $adforest_theme['google-recaptcha-type'] ) && ! empty( $adforest_theme['google-recaptcha-type'] )
				? (string) $adforest_theme['google-recaptcha-type']
				: 'v2';
			$site_key     = isset( $adforest_theme['google_api_key'] ) ? trim( (string) $adforest_theme['google_api_key'] ) : '';

			if ( '' === $site_key ) {
				return;
			}

			if ( 'v3' === $captcha_type ) {
				if ( ! wp_script_is( 'bornado-auth-google-recaptcha-v3', 'enqueued' ) ) {
					wp_enqueue_script(
						'bornado-auth-google-recaptcha-v3',
						'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $site_key ),
						array(),
						null,
						true
					);
				}
				return;
			}

			if ( ! wp_script_is( 'bornado-auth-google-recaptcha-v2', 'enqueued' ) ) {
				wp_enqueue_script(
					'bornado-auth-google-recaptcha-v2',
					'https://www.google.com/recaptcha/api.js',
					array(),
					null,
					true
				);
			}
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
			$privacy = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
			$sign_in = isset( $adforest_theme['sb_sign_in_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_in_page'] ) : 0;
			$sign_up = isset( $adforest_theme['sb_sign_up_page'] ) ? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_sign_up_page'] ) : 0;
			$profile = $this->get_profile_url();

			$config = array(
				'ajaxUrl'               => apply_filters( 'adforest_set_query_param', admin_url( 'admin-ajax.php' ) ),
				'afterLoginUrl'         => isset( $strings['sb_after_login_page'] ) ? (string) $strings['sb_after_login_page'] : home_url( '/' ),
				'profileUrl'            => $profile,
				'registerRedirectUrl'   => $profile,
				'captchaType'           => isset( $adforest_theme['google-recaptcha-type'] ) ? (string) $adforest_theme['google-recaptcha-type'] : 'v2',
				'captchaSiteKey'        => isset( $adforest_theme['google_api_key'] ) ? (string) $adforest_theme['google_api_key'] : '',
				'googleCaptchaNonce'    => wp_create_nonce( 'sb_google_captcha3_verification_nonce' ),
				'loginNonce'            => wp_create_nonce( 'sb_login_secure' ),
				'registerNonce'         => wp_create_nonce( 'sb_register_secure' ),
				'forgotNonce'           => wp_create_nonce( 'sb_forgot_pass_secure' ),
				'phonePreflightNonce'   => wp_create_nonce( 'bornado_auth_phone_preflight' ),
				'continueTokenNonce'    => wp_create_nonce( 'bornado_auth_resolve_continue_token' ),
				'firebaseLoginNonce'    => wp_create_nonce( 'bornado_auth_firebase_login' ),
				'firebaseRegisterNonce' => wp_create_nonce( 'bornado_auth_firebase_register' ),
				'phoneEnabled'          => ! empty( $adforest_theme['sb_register_with_phone'] ),
				'registerEnabled'       => (bool) get_option( 'users_can_register' ),
				'showRegisterPhone'     => ! empty( $adforest_theme['sb_user_phone_show_on_reg'] ),
				'signInUrl'             => $sign_in ? get_permalink( $sign_in ) : '',
				'signUpUrl'             => $sign_up ? get_permalink( $sign_up ) : '',
				'privacyUrl'            => $privacy ? $privacy : '',
				'isRtl'                 => is_rtl(),
				'firebase'              => array(
					'apiKey'            => isset( $adforest_theme['sb_firebase_apikey'] ) ? (string) $adforest_theme['sb_firebase_apikey'] : '',
					'projectId'         => isset( $adforest_theme['sb_firebase_projectId'] ) ? (string) $adforest_theme['sb_firebase_projectId'] : '',
					'messagingSenderId' => isset( $adforest_theme['sb_firebase_messagingSenderId'] ) ? (string) $adforest_theme['sb_firebase_messagingSenderId'] : '',
					'appId'             => isset( $adforest_theme['sb_firebase_appId'] ) ? (string) $adforest_theme['sb_firebase_appId'] : '',
				),
				'phoneCountries'        => function_exists( 'bornado_get_phone_country_options' ) ? bornado_get_phone_country_options() : array(),
				'defaultPhoneCountry'   => function_exists( 'bornado_get_default_phone_country_option' ) ? bornado_get_default_phone_country_option() : array(),
				'i18n'                  => array(
					'loginTitle'            => __( 'ورود به حساب کاربری', 'bornado-auth-modal' ),
					'registerTitle'         => __( 'عضویت در برنادو', 'bornado-auth-modal' ),
					'phoneMethod'           => __( 'موبایل', 'bornado-auth-modal' ),
					'emailMethod'           => __( 'ایمیل و رمز', 'bornado-auth-modal' ),
					'phoneLoginSuccess'     => __( 'ورود با موفقیت انجام شد.', 'bornado-auth-modal' ),
					'phoneRegisterSuccess'  => __( 'عضویت با موفقیت انجام شد.', 'bornado-auth-modal' ),
					'verificationSent'      => __( 'کد تایید برای شماره شما ارسال شد.', 'bornado-auth-modal' ),
					'resendCode'            => __( 'ارسال دوباره کد', 'bornado-auth-modal' ),
					'resendIn'              => __( 'ارسال دوباره تا', 'bornado-auth-modal' ),
					'seconds'               => __( 'ثانیه', 'bornado-auth-modal' ),
					'wrongOtp'              => __( 'کد واردشده صحیح نیست.', 'bornado-auth-modal' ),
					'genericError'          => __( 'خطایی رخ داد. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
					'networkError'          => __( 'ارتباط با سرور برقرار نشد. دوباره تلاش کنید.', 'bornado-auth-modal' ),
					'invalidPhone'          => __( 'شماره موبایل را با فرمت صحیح وارد کنید.', 'bornado-auth-modal' ),
					'invalidEmail'          => __( 'ایمیل معتبر وارد کنید.', 'bornado-auth-modal' ),
					'passwordMismatch'      => __( 'رمز عبور و تکرار آن یکسان نیست.', 'bornado-auth-modal' ),
					'termsRequired'         => __( 'برای ادامه باید قوانین را بپذیرید.', 'bornado-auth-modal' ),
					'otpRequired'           => __( 'کد تایید را وارد کنید.', 'bornado-auth-modal' ),
					'otpLength'             => __( 'کد تایید باید ۶ رقم باشد.', 'bornado-auth-modal' ),
					'nameRequired'          => __( 'نام را وارد کنید.', 'bornado-auth-modal' ),
					'emailRequired'         => __( 'ایمیل را وارد کنید.', 'bornado-auth-modal' ),
					'passwordRequired'      => __( 'رمز عبور را وارد کنید.', 'bornado-auth-modal' ),
					'forgotSuccess'         => __( 'لینک بازیابی رمز عبور به ایمیل شما ارسال شد.', 'bornado-auth-modal' ),
					'verifyRecaptcha'       => __( 'برای ادامه، تایید امنیتی را کامل کنید.', 'bornado-auth-modal' ),
					'loading'               => __( 'در حال پردازش...', 'bornado-auth-modal' ),
					'changeNumber'          => __( 'تغییر شماره', 'bornado-auth-modal' ),
					'backToLogin'           => __( 'بازگشت به ورود', 'bornado-auth-modal' ),
					'continueLabel'         => __( 'ادامه', 'bornado-auth-modal' ),
					'verifyLabel'           => __( 'تایید کد', 'bornado-auth-modal' ),
					'countryLabel'          => __( 'کد کشور', 'bornado-auth-modal' ),
					'close'                 => __( 'بستن', 'bornado-auth-modal' ),
					'rememberMe'            => __( 'مرا به خاطر بسپار', 'bornado-auth-modal' ),
					'forgotPassword'        => __( 'رمزت را فراموش کرده‌ای؟', 'bornado-auth-modal' ),
					'backToAuth'            => __( 'بازگشت', 'bornado-auth-modal' ),
				),
			);

			return $config;
		}

		public function render_trigger_shortcode( $atts = array() ) {
			if ( is_user_logged_in() ) {
				return '';
			}

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

			$mode      = 'register' === strtolower( $atts['mode'] ) ? 'register' : 'login';
			$method    = 'email' === strtolower( $atts['method'] ) ? 'email' : 'phone';
			$tag       = 'a' === strtolower( $atts['tag'] ) ? 'a' : 'button';
			$label     = trim( (string) $atts['label'] );
			$class     = trim( (string) $atts['class'] );
			$fallback  = 'register' === $mode ? $this->get_sign_up_url() : $this->get_sign_in_url();

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

		public function render_modal() {
			if ( is_admin() || is_user_logged_in() || $this->modal_rendered ) {
				return;
			}

			$this->modal_rendered = true;
			$privacy_url          = $this->get_privacy_url();
			?>
			<div class="modal fade bornado-auth-modal" id="bornado-auth-modal" tabindex="-1" aria-labelledby="bornado-auth-modal-title" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered modal-lg">
					<div class="modal-content">
						<div class="bornado-auth-modal__shell">
							<div class="modal-header bornado-auth-modal__header">
								<button type="button" class="btn-close bornado-auth-modal__close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'بستن', 'bornado-auth-modal' ); ?>"></button>
								<div class="bornado-auth-modal__topbar">
									<div class="bornado-auth-modal__mode-switch" role="tablist" aria-label="<?php esc_attr_e( 'نوع عملیات', 'bornado-auth-modal' ); ?>">
										<button type="button" class="bornado-auth-pill is-active" data-auth-mode="login"><?php esc_html_e( 'ورود', 'bornado-auth-modal' ); ?></button>
										<button type="button" class="bornado-auth-pill" data-auth-mode="register"><?php esc_html_e( 'عضویت', 'bornado-auth-modal' ); ?></button>
									</div>
								</div>
								<div class="bornado-auth-modal__hero">
									<h2 class="modal-title" id="bornado-auth-modal-title"><?php esc_html_e( 'ورود به حساب کاربری', 'bornado-auth-modal' ); ?></h2>
								</div>
							</div>

							<div class="bornado-auth-modal__body">
								<div class="bornado-auth-modal__notice" id="bornado-auth-notice" role="status" aria-live="polite" hidden></div>

								<div class="bornado-auth-modal__method-switch" role="tablist" aria-label="<?php esc_attr_e( 'روش ورود', 'bornado-auth-modal' ); ?>">
									<button type="button" class="bornado-auth-method is-active" data-auth-method="phone"><?php esc_html_e( 'موبایل', 'bornado-auth-modal' ); ?></button>
									<button type="button" class="bornado-auth-method" data-auth-method="email"><?php esc_html_e( 'ایمیل و رمز', 'bornado-auth-modal' ); ?></button>
								</div>

								<div class="bornado-auth-views">
									<section class="bornado-auth-view is-active" data-view="login-phone">
										<form id="bornado-auth-phone-login-form" novalidate>
											<div class="bornado-auth-field">
												<label for="bornado-auth-login-phone"><?php esc_html_e( 'شماره موبایل', 'bornado-auth-modal' ); ?></label>
												<input type="tel" id="bornado-auth-login-phone" name="phone_number" inputmode="tel" autocomplete="tel" placeholder="9121234567">
											</div>
											<label class="bornado-auth-check">
												<input type="checkbox" id="bornado-auth-login-remember" name="remember" value="1">
												<span><?php esc_html_e( 'مرا به خاطر بسپار', 'bornado-auth-modal' ); ?></span>
											</label>
											<div class="bornado-auth-actions">
												<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'ارسال کد', 'bornado-auth-modal' ); ?></button>
											</div>
										</form>
									</section>

									<section class="bornado-auth-view" data-view="register-phone">
										<form id="bornado-auth-phone-register-form" novalidate>
											<div class="bornado-auth-field">
												<label for="bornado-auth-register-name-phone"><?php esc_html_e( 'نام', 'bornado-auth-modal' ); ?></label>
												<input type="text" id="bornado-auth-register-name-phone" name="display_name" autocomplete="name" placeholder="<?php esc_attr_e( 'نام و نام خانوادگی', 'bornado-auth-modal' ); ?>">
											</div>
											<div class="bornado-auth-field">
												<label for="bornado-auth-register-phone"><?php esc_html_e( 'شماره موبایل', 'bornado-auth-modal' ); ?></label>
												<input type="tel" id="bornado-auth-register-phone" name="phone_number" inputmode="tel" autocomplete="tel" placeholder="9121234567">
											</div>
											<label class="bornado-auth-check">
												<input type="checkbox" id="bornado-auth-register-terms-phone" required>
												<span>
													<?php esc_html_e( 'با قوانین و حریم خصوصی موافقم', 'bornado-auth-modal' ); ?>
													<?php if ( $privacy_url ) : ?>
														<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'مشاهده', 'bornado-auth-modal' ); ?></a>
													<?php endif; ?>
												</span>
											</label>
											<div class="bornado-auth-actions">
												<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'ارسال کد', 'bornado-auth-modal' ); ?></button>
											</div>
										</form>
									</section>

									<section class="bornado-auth-view" data-view="otp">
										<form id="bornado-auth-otp-form" novalidate>
											<div class="bornado-auth-otp-intro">
												<h3><?php esc_html_e( 'تایید شماره موبایل', 'bornado-auth-modal' ); ?></h3>
												<p id="bornado-auth-otp-target"><?php esc_html_e( 'کد تایید برای شماره شما ارسال می‌شود.', 'bornado-auth-modal' ); ?></p>
											</div>
											<div class="bornado-auth-field">
												<label for="bornado-auth-otp-code"><?php esc_html_e( 'کد تایید', 'bornado-auth-modal' ); ?></label>
												<input type="text" id="bornado-auth-otp-code" name="otp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="123456">
											</div>
											<div id="bornado-auth-firebase-recaptcha" class="bornado-auth-recaptcha bornado-auth-recaptcha--firebase"></div>
											<div class="bornado-auth-otp-meta">
												<button type="button" class="bornado-auth-link" id="bornado-auth-change-number"><?php esc_html_e( 'تغییر شماره', 'bornado-auth-modal' ); ?></button>
												<button type="button" class="bornado-auth-link" id="bornado-auth-resend-code"><?php esc_html_e( 'ارسال دوباره کد', 'bornado-auth-modal' ); ?></button>
												<span id="bornado-auth-resend-timer" hidden></span>
											</div>
											<div class="bornado-auth-actions">
												<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'تایید و ادامه', 'bornado-auth-modal' ); ?></button>
											</div>
										</form>
									</section>

									<section class="bornado-auth-view" data-view="login-email">
										<form id="bornado-auth-email-login-form" novalidate>
											<input type="hidden" name="is_captcha" value="yes">
											<div class="bornado-auth-field">
												<label for="bornado-auth-login-email"><?php esc_html_e( 'ایمیل', 'bornado-auth-modal' ); ?></label>
												<input type="email" id="bornado-auth-login-email" name="sb_reg_email" autocomplete="email" placeholder="<?php esc_attr_e( 'ایمیل', 'bornado-auth-modal' ); ?>">
											</div>
											<div class="bornado-auth-field">
												<label for="bornado-auth-login-password"><?php esc_html_e( 'رمز عبور', 'bornado-auth-modal' ); ?></label>
												<input type="password" id="bornado-auth-login-password" name="sb_reg_password" autocomplete="current-password" placeholder="<?php esc_attr_e( 'رمز عبور', 'bornado-auth-modal' ); ?>">
											</div>
											<div class="bornado-auth-inline-row">
												<label class="bornado-auth-check">
													<input type="checkbox" name="is_remember" value="1">
													<span><?php esc_html_e( 'مرا به خاطر بسپار', 'bornado-auth-modal' ); ?></span>
												</label>
												<button type="button" class="bornado-auth-link" id="bornado-auth-open-forgot"><?php esc_html_e( 'فراموشی رمز', 'bornado-auth-modal' ); ?></button>
											</div>
											<div class="bornado-auth-recaptcha bornado-auth-recaptcha--google" id="bornado-auth-login-recaptcha"></div>
											<div class="bornado-auth-actions">
												<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'ورود', 'bornado-auth-modal' ); ?></button>
											</div>
										</form>
									</section>

									<section class="bornado-auth-view" data-view="register-email">
										<form id="bornado-auth-email-register-form" novalidate>
											<input type="hidden" name="is_captcha" value="yes">
											<div class="bornado-auth-grid">
												<div class="bornado-auth-field">
													<label for="bornado-auth-register-name-email"><?php esc_html_e( 'نام', 'bornado-auth-modal' ); ?></label>
													<input type="text" id="bornado-auth-register-name-email" name="sb_reg_name" autocomplete="name" placeholder="<?php esc_attr_e( 'نام و نام خانوادگی', 'bornado-auth-modal' ); ?>">
												</div>
												<div class="bornado-auth-field bornado-auth-field--optional" id="bornado-auth-register-phone-wrap">
													<label for="bornado-auth-register-contact-email"><?php esc_html_e( 'شماره تماس', 'bornado-auth-modal' ); ?></label>
													<input type="tel" id="bornado-auth-register-contact-email" name="sb_reg_contact" autocomplete="tel" placeholder="9121234567">
												</div>
											</div>
											<div class="bornado-auth-field">
												<label for="bornado-auth-register-email"><?php esc_html_e( 'ایمیل', 'bornado-auth-modal' ); ?></label>
												<input type="email" id="bornado-auth-register-email" name="sb_reg_email" autocomplete="email" placeholder="<?php esc_attr_e( 'ایمیل', 'bornado-auth-modal' ); ?>">
											</div>
											<div class="bornado-auth-grid">
												<div class="bornado-auth-field">
													<label for="bornado-auth-register-password"><?php esc_html_e( 'رمز عبور', 'bornado-auth-modal' ); ?></label>
													<input type="password" id="bornado-auth-register-password" name="sb_reg_password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'رمز عبور', 'bornado-auth-modal' ); ?>">
												</div>
												<div class="bornado-auth-field">
													<label for="bornado-auth-register-password-confirm"><?php esc_html_e( 'تکرار رمز عبور', 'bornado-auth-modal' ); ?></label>
													<input type="password" id="bornado-auth-register-password-confirm" name="sb_reg_password_confirm" autocomplete="new-password" placeholder="<?php esc_attr_e( 'تکرار رمز عبور', 'bornado-auth-modal' ); ?>">
												</div>
											</div>
											<label class="bornado-auth-check">
												<input type="checkbox" id="bornado-auth-register-terms-email" required>
												<span>
													<?php esc_html_e( 'با قوانین و حریم خصوصی موافقم', 'bornado-auth-modal' ); ?>
													<?php if ( $privacy_url ) : ?>
														<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'مشاهده', 'bornado-auth-modal' ); ?></a>
													<?php endif; ?>
												</span>
											</label>
											<div class="bornado-auth-recaptcha bornado-auth-recaptcha--google" id="bornado-auth-register-recaptcha"></div>
											<div class="bornado-auth-actions">
												<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'ثبت‌نام', 'bornado-auth-modal' ); ?></button>
											</div>
										</form>
									</section>

									<section class="bornado-auth-view" data-view="forgot">
										<form id="bornado-auth-forgot-form" novalidate>
											<div class="bornado-auth-otp-intro">
												<h3><?php esc_html_e( 'بازیابی رمز عبور', 'bornado-auth-modal' ); ?></h3>
												<p><?php esc_html_e( 'ایمیل حساب را وارد کن تا لینک بازیابی برایت ارسال شود.', 'bornado-auth-modal' ); ?></p>
											</div>
											<div class="bornado-auth-field">
												<label for="bornado-auth-forgot-email"><?php esc_html_e( 'ایمیل', 'bornado-auth-modal' ); ?></label>
												<input type="email" id="bornado-auth-forgot-email" name="sb_forgot_email" autocomplete="email" placeholder="<?php esc_attr_e( 'ایمیل', 'bornado-auth-modal' ); ?>">
											</div>
											<div class="bornado-auth-actions bornado-auth-actions--split">
												<button type="button" class="bornado-auth-link-button" id="bornado-auth-back-from-forgot"><?php esc_html_e( 'بازگشت', 'bornado-auth-modal' ); ?></button>
												<button type="submit" class="adt-button-dark bornado-auth-submit"><?php esc_html_e( 'ارسال لینک بازیابی', 'bornado-auth-modal' ); ?></button>
											</div>
										</form>
									</section>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public function ajax_phone_preflight() {
			$this->verify_ajax_nonce( 'bornado_auth_phone_preflight', 'security' );

			if ( function_exists( 'adforest_is_demo' ) && adforest_is_demo() ) {
				wp_send_json_error( array( 'message' => __( 'در حالت دمو این عملیات مجاز نیست.', 'bornado-auth-modal' ) ), 403 );
			}

			$mode            = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'login';
			$phone_dial_code = isset( $_POST['phone_dial_code'] ) ? wp_unslash( $_POST['phone_dial_code'] ) : '';
			$phone           = $this->normalize_phone_number( isset( $_POST['phone_number'] ) ? wp_unslash( $_POST['phone_number'] ) : '', $phone_dial_code );

			if ( ! $this->is_valid_phone_number( $phone ) ) {
				wp_send_json_error( array( 'message' => __( 'شماره موبایل معتبر نیست.', 'bornado-auth-modal' ) ), 422 );
			}

			$user_id = $this->find_user_id_by_phone( $phone );

			if ( 'register' === $mode ) {
				if ( ! get_option( 'users_can_register' ) ) {
					wp_send_json_error( array( 'message' => __( 'ثبت‌نام در حال حاضر غیرفعال است.', 'bornado-auth-modal' ) ), 403 );
				}
				if ( $user_id ) {
					wp_send_json_error( array( 'message' => __( 'این شماره قبلا ثبت شده است.', 'bornado-auth-modal' ) ), 409 );
				}
				wp_send_json_success(
					array(
						'phone_number' => $phone,
						'mode'         => 'register',
					)
				);
			}

			if ( ! $user_id ) {
				wp_send_json_error( array( 'message' => __( 'کاربری با این شماره پیدا نشد.', 'bornado-auth-modal' ) ), 404 );
			}

			wp_send_json_success(
				array(
					'phone_number' => $phone,
					'mode'         => 'login',
				)
			);
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

			$user_id = $this->find_user_id_by_phone( $phone );
			$mode    = 'login';

			if ( ! $user_id ) {
				if ( ! get_option( 'users_can_register' ) ) {
					wp_send_json_error( array( 'message' => __( 'برای این شماره حسابی پیدا نشد و ثبت‌نام نیز غیرفعال است.', 'bornado-auth-modal' ) ), 403 );
				}

				$mode = 'register';
			}

			$redirect_url = isset( $payload['redirect_url'] ) ? esc_url_raw( (string) $payload['redirect_url'] ) : '';
			if ( '' === $redirect_url ) {
				$redirect_url = $this->get_profile_url();
			}

			$display_name = isset( $payload['display_name'] ) ? sanitize_text_field( (string) $payload['display_name'] ) : '';
			if ( '' === $display_name ) {
				$display_name = __( 'کاربر برنادو', 'bornado-auth-modal' );
			}

			wp_send_json_success(
				array(
					'mode'         => $mode,
					'phone_number' => $phone,
					'redirect_url' => $redirect_url,
					'remember'     => '1',
					'display_name' => $display_name,
				)
			);
		}

		public function ajax_firebase_login() {
			$this->verify_ajax_nonce( 'bornado_auth_firebase_login', 'security' );

			if ( function_exists( 'adforest_is_demo' ) && adforest_is_demo() ) {
				wp_send_json_error( array( 'message' => __( 'در حالت دمو این عملیات مجاز نیست.', 'bornado-auth-modal' ) ), 403 );
			}

			$phone_dial_code = isset( $_POST['phone_dial_code'] ) ? wp_unslash( $_POST['phone_dial_code'] ) : '';
			$requested_phone = $this->normalize_phone_number( isset( $_POST['phone_number'] ) ? wp_unslash( $_POST['phone_number'] ) : '', $phone_dial_code );
			$remember        = ! empty( $_POST['remember'] ) && '1' === (string) wp_unslash( $_POST['remember'] );
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
			if ( ! $user ) {
				wp_send_json_error( array( 'message' => __( 'حساب کاربری معتبر نیست.', 'bornado-auth-modal' ) ), 404 );
			}

			update_user_meta( $user_id, '_sb_is_ph_verified', '1' );
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, $remember, is_ssl() );

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

			if ( function_exists( 'adforest_is_demo' ) && adforest_is_demo() ) {
				wp_send_json_error( array( 'message' => __( 'در حالت دمو این عملیات مجاز نیست.', 'bornado-auth-modal' ) ), 403 );
			}

			if ( ! get_option( 'users_can_register' ) ) {
				wp_send_json_error( array( 'message' => __( 'ثبت‌نام در حال حاضر غیرفعال است.', 'bornado-auth-modal' ) ), 403 );
			}

			$phone_dial_code = isset( $_POST['phone_dial_code'] ) ? wp_unslash( $_POST['phone_dial_code'] ) : '';
			$requested_phone = $this->normalize_phone_number( isset( $_POST['phone_number'] ) ? wp_unslash( $_POST['phone_number'] ) : '', $phone_dial_code );
			$display_name    = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
			$token_result    = $this->verify_firebase_identity_token( isset( $_POST['id_token'] ) ? wp_unslash( $_POST['id_token'] ) : '' );

			if ( '' === $display_name ) {
				wp_send_json_error( array( 'message' => __( 'نام را وارد کنید.', 'bornado-auth-modal' ) ), 422 );
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

			$phone_digits  = preg_replace( '/\D+/', '', $verified_phone );
			$phone_suffix  = $phone_digits ? substr( $phone_digits, -10 ) : (string) wp_rand( 100000, 999999 );
			$username_base = sanitize_user( remove_accents( $display_name ), true );
			if ( '' === $username_base ) {
				$username_base = 'bornado-' . $phone_suffix;
			}
			if ( function_exists( 'adforest_check_user_name' ) ) {
				$username_base = adforest_check_user_name( $username_base );
			}
			$username_base = sanitize_user( (string) $username_base, true );
			if ( '' === $username_base ) {
				$username_base = 'bornado-user-' . $phone_suffix;
			}

			$final_display_name = '' !== $display_name ? $display_name : $username_base;

			$user_data = array(
				'user_login'    => $username_base,
				'user_pass'     => wp_generate_password( 18, true, true ),
				'display_name'  => $final_display_name,
				'nickname'      => $final_display_name,
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
					'user_nicename' => sanitize_title( $username_base ),
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
			wp_set_auth_cookie( $user_id, true, is_ssl() );

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
				'Invalid email or password.'                     => __( 'ایمیل یا رمز عبور نادرست است.', 'bornado-auth-modal' ),
				'The email address is not correct.'              => __( 'آدرس ایمیل صحیح نیست.', 'bornado-auth-modal' ),
				'This username is already registered.'           => __( 'این نام کاربری قبلا ثبت شده است.', 'bornado-auth-modal' ),
				'This email address is already registered.'      => __( 'این ایمیل قبلا ثبت شده است.', 'bornado-auth-modal' ),
				'Cannot create a user with an empty nicename.'   => __( 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
				'Could not save password reset key to database.' => __( 'ارسال لینک بازیابی انجام نشد. دوباره تلاش کنید.', 'bornado-auth-modal' ),
			);

			if ( isset( $translations[ $message ] ) ) {
				return $translations[ $message ];
			}

			$contains_map = array(
				'nicename'            => __( 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
				'empty username'      => __( 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.', 'bornado-auth-modal' ),
				'captcha'             => __( 'تایید امنیتی ناموفق بود. دوباره تلاش کن.', 'bornado-auth-modal' ),
				'verification expired' => __( 'زمان تایید به پایان رسیده است. دوباره کد بگیر.', 'bornado-auth-modal' ),
				'expired'             => __( 'زمان این درخواست به پایان رسیده است. دوباره تلاش کن.', 'bornado-auth-modal' ),
				'phone number'        => __( 'شماره موبایل معتبر نیست یا قبلا ثبت شده است.', 'bornado-auth-modal' ),
				'email address'       => __( 'ایمیل واردشده معتبر نیست یا قبلا استفاده شده است.', 'bornado-auth-modal' ),
				'user name'           => __( 'این نام کاربری قبلا ثبت شده است.', 'bornado-auth-modal' ),
				'security'            => __( 'اعتبار امنیتی درخواست نامعتبر است. صفحه را تازه‌سازی کن و دوباره تلاش کن.', 'bornado-auth-modal' ),
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
