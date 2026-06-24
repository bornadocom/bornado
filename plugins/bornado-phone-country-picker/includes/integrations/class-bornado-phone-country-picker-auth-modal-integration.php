<?php
/**
 * Auth-modal integration metadata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Phone_Country_Picker_Auth_Modal_Integration' ) ) {
	return;
}

final class Bornado_Phone_Country_Picker_Auth_Modal_Integration {
	/**
	 * Return frontend mount metadata.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_frontend_config() {
		return array(
			'id'                 => 'auth-modal',
			'selectName'         => 'phone_dial_code',
			'phoneInputSelector' => 'input[name="phone_number"]',
			'rootSelector'       => '.bornado-auth-field, .bornado-auth-phone-row',
			'helperSelector'     => '.bpcp__helper',
		);
	}
}
