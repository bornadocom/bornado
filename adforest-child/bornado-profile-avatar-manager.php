<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_enqueue_profile_avatar_manager_assets' ) ) {
	/**
	 * Enqueue child-theme avatar removal UX for frontend profile screens.
	 *
	 * @return void
	 */
	function bornado_enqueue_profile_avatar_manager_assets() {
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		$script_path = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-profile-avatar-manager.js';
		if ( ! file_exists( $script_path ) ) {
			return;
		}

		$user_id = get_current_user_id();

		wp_enqueue_script(
			'bornado-profile-avatar-manager',
			trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-profile-avatar-manager.js',
			array( 'jquery' ),
			(string) filemtime( $script_path ),
			true
		);

		wp_localize_script(
			'bornado-profile-avatar-manager',
			'bornadoProfileAvatarManager',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'deleteNonce'      => wp_create_nonce( 'bornado_delete_user_profile_picture' ),
				'defaultAvatarUrl' => function_exists( 'adforest_get_user_dp' ) ? adforest_get_user_dp( $user_id ) : '',
				'i18n'             => array(
					'deleteFailed' => __( 'حذف عکس پروفایل انجام نشد. دوباره تلاش کنید.', 'adforest-child' ),
					'deleteDone'   => __( 'عکس پروفایل حذف شد.', 'adforest-child' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_profile_avatar_manager_assets', 134 );

if ( ! function_exists( 'bornado_delete_user_profile_picture' ) ) {
	/**
	 * Remove the current user's custom profile picture without touching theme core.
	 *
	 * @return void
	 */
	function bornado_delete_user_profile_picture() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => __( 'برای ادامه باید وارد حساب کاربری خود شوید.', 'adforest-child' ),
				),
				403
			);
		}

		check_ajax_referer( 'bornado_delete_user_profile_picture', 'security' );

		if ( function_exists( 'adforest_is_demo' ) && adforest_is_demo() ) {
			wp_send_json_error(
				array(
					'message' => __( 'در حالت دمو این عملیات مجاز نیست.', 'adforest-child' ),
				)
			);
		}

		$user_id = get_current_user_id();

		delete_user_meta( $user_id, '_sb_user_pic' );
		delete_user_meta( $user_id, '_sb_user_linkedin_pic' );

		wp_send_json_success(
			array(
				'avatarUrl' => function_exists( 'adforest_get_user_dp' ) ? adforest_get_user_dp( $user_id ) : '',
				'message'   => __( 'عکس پروفایل حذف شد.', 'adforest-child' ),
			)
		);
	}
}
add_action( 'wp_ajax_bornado_delete_user_profile_picture', 'bornado_delete_user_profile_picture' );
