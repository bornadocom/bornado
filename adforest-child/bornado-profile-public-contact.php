<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_is_public_profile_page' ) ) {
	/**
	 * True when the current request is rendering a public author profile.
	 *
	 * @return bool
	 */
	function bornado_is_public_profile_page() {
		return (int) get_query_var( 'author' ) > 0;
	}
}

if ( ! function_exists( 'bornado_get_profile_verified_contact_methods' ) ) {
	/**
	 * Return verified contact methods for one user profile.
	 *
	 * @param int $user_id User ID.
	 * @return array<int,array<string,string>>
	 */
	function bornado_get_profile_verified_contact_methods( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return array();
		}

		$methods = array();
		$status_map = array();

		if ( class_exists( 'Bornado_Ad_Contact_Methods' ) && method_exists( 'Bornado_Ad_Contact_Methods', 'get_user_contact_method_statuses' ) ) {
			$status_map = (array) Bornado_Ad_Contact_Methods::get_user_contact_method_statuses( $user_id );
		}

		foreach ( array( 'phone', 'whatsapp', 'email' ) as $key ) {
			if ( empty( $status_map[ $key ]['enabled'] ) || empty( $status_map[ $key ]['value'] ) ) {
				continue;
			}

			$value = trim( (string) $status_map[ $key ]['value'] );
			if ( '' === $value ) {
				continue;
			}

			$methods[] = array(
				'key'   => $key,
				'label' => isset( $status_map[ $key ]['label'] ) ? (string) $status_map[ $key ]['label'] : $key,
				'value' => $value,
				'icon'  => 'phone' === $key ? 'fas fa-phone-alt' : ( 'whatsapp' === $key ? 'fab fa-whatsapp' : 'fas fa-envelope' ),
			);
		}

		return $methods;
	}
}

if ( ! function_exists( 'bornado_get_profile_contact_value_html' ) ) {
	/**
	 * Build a clickable HTML value for one contact method.
	 *
	 * @param string $key   Method key.
	 * @param string $value Raw contact value.
	 * @return string
	 */
	function bornado_get_profile_contact_value_html( $key, $value ) {
		$key   = (string) $key;
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( 'email' === $key ) {
			$display = antispambot( $value );
			$href    = 'mailto:' . antispambot( $value );

			return sprintf(
				'<a href="%1$s" class="bornado-profile-contact__link" dir="ltr">%2$s</a>',
				esc_url( $href ),
				esc_html( $display )
			);
		}

		$callable = function_exists( 'adforest_get_CallAbleNumber' ) ? adforest_get_CallAbleNumber( $value ) : preg_replace( '/[^0-9+]/', '', $value );
		$href     = 'whatsapp' === $key ? 'https://wa.me/' . ltrim( preg_replace( '/[^0-9]/', '', $callable ), '0' ) : 'tel:' . $callable;

		return sprintf(
			'<a href="%1$s" class="bornado-profile-contact__link" dir="ltr">%2$s</a>',
			esc_url( $href ),
			esc_html( $value )
		);
	}
}

if ( ! function_exists( 'bornado_render_profile_contact_panel' ) ) {
	/**
	 * Render the verified-contact panel for public profile pages.
	 *
	 * @param int  $user_id         Profile owner ID.
	 * @param bool $is_own_profile  Whether the viewer owns the profile.
	 * @return string
	 */
	function bornado_render_profile_contact_panel( $user_id, $is_own_profile = false ) {
		$user_id        = (int) $user_id;
		$is_own_profile = (bool) $is_own_profile;
		$methods        = bornado_get_profile_verified_contact_methods( $user_id );

		if ( empty( $methods ) && ! $is_own_profile ) {
			return '';
		}

		$login_required = function_exists( 'adforest_showPhone_to_users' ) ? adforest_showPhone_to_users() : false;
		$login_url      = function_exists( 'bornado_get_safe_login_redirect_url' )
			? bornado_get_safe_login_redirect_url()
			: wp_login_url( home_url( '/' ) );

		ob_start();
		?>
		<div class="bornado-profile-contact-panel<?php echo $is_own_profile ? ' is-owner-view' : ''; ?>"
		     data-bornado-profile-contact
		     data-user-id="<?php echo esc_attr( $user_id ); ?>">
			<div class="bornado-profile-contact-panel__header">
				<h4><?php echo esc_html__( 'اطلاعات تماس تاییدشده', 'adforest-child' ); ?></h4>
				<p><?php echo esc_html__( 'روش های ارتباطی :', 'adforest-child' ); ?></p>
			</div>

			<?php if ( empty( $methods ) ) : ?>
				<div class="bornado-profile-contact-panel__empty">
					<?php echo esc_html__( 'هنوز هیچ راه ارتباطی تاییدشده‌ای برای این پروفایل ثبت نشده است.', 'adforest-child' ); ?>
				</div>
			<?php else : ?>
				<ul class="bornado-profile-contact-list">
					<?php foreach ( $methods as $method ) : ?>
						<li class="bornado-profile-contact-list__item" data-contact-key="<?php echo esc_attr( $method['key'] ); ?>">
							<div class="bornado-profile-contact-list__meta">
								<div class="bornado-profile-contact-list__icon"><i class="<?php echo esc_attr( $method['icon'] ); ?>"></i></div>
								<div class="bornado-profile-contact-list__copy">
									<div class="bornado-profile-contact-list__label-row">
										<span class="bornado-profile-contact-list__label"><?php echo esc_html( $method['label'] ); ?></span>
										<span class="bornado-profile-contact-list__badge"><?php echo esc_html__( 'تایید شده', 'adforest-child' ); ?></span>
									</div>
									<div class="bornado-profile-contact-list__value<?php echo $is_own_profile ? ' is-filled' : ' is-hidden'; ?>" data-contact-value>
										<?php
										if ( $is_own_profile ) {
											echo wp_kses_post( bornado_get_profile_contact_value_html( $method['key'], $method['value'] ) );
										} elseif ( $login_required && ! is_user_logged_in() ) {
											?>
											<a href="<?php echo esc_url( $login_url ); ?>" class="bornado-profile-contact-list__toggle bornado-profile-contact-list__toggle--link bornado-profile-contact-list__toggle--full">
												<?php echo esc_html__( 'ورود برای مشاهده', 'adforest-child' ); ?>
											</a>
											<?php
										} else {
											?>
											<button type="button"
											        class="bornado-profile-contact-list__toggle bornado-profile-contact-list__toggle--full"
											        data-bornado-reveal-contact
											        data-method-key="<?php echo esc_attr( $method['key'] ); ?>">
												<?php echo esc_html__( 'نمایش', 'adforest-child' ); ?>
											</button>
											<?php
										}
										?>
									</div>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'bornado_enqueue_profile_public_contact_assets' ) ) {
	/**
	 * Enqueue contact-panel assets on public profile pages.
	 *
	 * @return void
	 */
	function bornado_enqueue_profile_public_contact_assets() {
		if ( is_admin() || ! bornado_is_public_profile_page() ) {
			return;
		}

		$style_path = trailingslashit( get_stylesheet_directory() ) . 'assets/css/bornado-profile-public-contact.css';
		$script_path = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-profile-public-contact.js';
		$style_deps = function_exists( 'bornado_get_theme_style_handles' ) ? bornado_get_theme_style_handles() : array();

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'bornado-profile-public-contact',
				trailingslashit( get_stylesheet_directory_uri() ) . 'assets/css/bornado-profile-public-contact.css',
				$style_deps,
				(string) filemtime( $style_path )
			);
		}

		if ( ! file_exists( $script_path ) ) {
			return;
		}

		wp_enqueue_script(
			'bornado-profile-public-contact',
			trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-profile-public-contact.js',
			array( 'jquery' ),
			(string) filemtime( $script_path ),
			true
		);

		wp_localize_script(
			'bornado-profile-public-contact',
			'bornadoProfilePublicContact',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bornado_profile_public_contact_reveal' ),
				'i18n'    => array(
					'genericError' => __( 'نمایش اطلاعات تماس انجام نشد. دوباره تلاش کنید.', 'adforest-child' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_profile_public_contact_assets', 140 );

if ( ! function_exists( 'bornado_ajax_reveal_profile_contacts' ) ) {
	/**
	 * Reveal verified profile contacts for click-to-show UI.
	 *
	 * @return void
	 */
	function bornado_ajax_reveal_profile_contacts() {
		check_ajax_referer( 'bornado_profile_public_contact_reveal', 'security' );

		if ( function_exists( 'adforest_showPhone_to_users' ) && adforest_showPhone_to_users() && ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => __( 'برای مشاهده اطلاعات تماس باید وارد حساب کاربری خود شوید.', 'adforest-child' ),
				),
				403
			);
		}

		$user_id     = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$method_key  = isset( $_POST['method_key'] ) ? sanitize_key( wp_unslash( $_POST['method_key'] ) ) : '';
		$methods     = bornado_get_profile_verified_contact_methods( $user_id );
		$target_item = array();

		if ( $user_id < 1 ) {
			wp_send_json_error(
				array(
					'message' => __( 'پروفایل معتبر نیست.', 'adforest-child' ),
				),
				400
			);
		}

		if ( empty( $methods ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'اطلاعات تماس تاییدشده‌ای برای این پروفایل ثبت نشده است.', 'adforest-child' ),
				),
				404
			);
		}

		if ( '' === $method_key ) {
			wp_send_json_error(
				array(
					'message' => __( 'روش ارتباطی معتبر نیست.', 'adforest-child' ),
				),
				400
			);
		}

		foreach ( $methods as $method ) {
			if ( $method['key'] === $method_key ) {
				$target_item = $method;
				break;
			}
		}

		if ( empty( $target_item ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'این روش ارتباطی برای این پروفایل در دسترس نیست.', 'adforest-child' ),
				),
				404
			);
		}

		wp_send_json_success(
			array(
				'key'  => $target_item['key'],
				'html' => bornado_get_profile_contact_value_html( $target_item['key'], $target_item['value'] ),
			)
		);
	}
}
add_action( 'wp_ajax_bornado_reveal_profile_contacts', 'bornado_ajax_reveal_profile_contacts' );
add_action( 'wp_ajax_nopriv_bornado_reveal_profile_contacts', 'bornado_ajax_reveal_profile_contacts' );
