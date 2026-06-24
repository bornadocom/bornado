<?php
/**
 * Dashboard-profile integration metadata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Phone_Country_Picker_Profile_Integration' ) ) {
	return;
}

final class Bornado_Phone_Country_Picker_Profile_Integration {
	/**
	 * Return frontend mount metadata.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_frontend_config() {
		return array(
			'id'                 => 'profile',
			'selectName'         => 'bornado_phone_dial_code',
			'phoneInputSelector' => '#sb_user_contact',
			'rootSelector'       => '#sb_update_profile .input-style-1, #sb_update_profile .form-group',
			'helperSelector'     => '#sb_update_profile small',
		);
	}
}
