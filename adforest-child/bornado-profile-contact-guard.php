<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_guard_profile_contact_submission' ) ) {
	/**
	 * Prevent users from sending the profile contact form to themselves.
	 *
	 * @return void
	 */
	function bornado_guard_profile_contact_submission() {
		$receiver_id = isset( $_POST['receiver_id'] ) ? absint( wp_unslash( $_POST['receiver_id'] ) ) : 0;
		$current_user_id = get_current_user_id();

		if ( $receiver_id > 0 && $current_user_id > 0 && $receiver_id === $current_user_id ) {
			check_ajax_referer( 'sb_user_contact_form_nonce', 'security' );
			echo '0|' . __( 'شما نمی‌توانید از فرم پروفایل به خودتان پیام بفرستید.', 'adforest-child' );
			wp_die();
		}

		if ( function_exists( 'adforest_user_contact_form' ) ) {
			adforest_user_contact_form();
			wp_die();
		}

		echo '0|' . __( 'فرم تماس پروفایل در دسترس نیست.', 'adforest-child' );
		wp_die();
	}
}

add_action(
	'init',
	static function () {
		remove_action( 'wp_ajax_sb_user_contact_form', 'adforest_user_contact_form' );
		remove_action( 'wp_ajax_nopriv_sb_user_contact_form', 'adforest_user_contact_form' );

		add_action( 'wp_ajax_sb_user_contact_form', 'bornado_guard_profile_contact_submission' );
		add_action( 'wp_ajax_nopriv_sb_user_contact_form', 'bornado_guard_profile_contact_submission' );
	},
	20
);
