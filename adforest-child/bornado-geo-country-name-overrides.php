<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_geo_default_country_name_overrides' ) ) {
	/**
	 * Project-level fallback map for Persian country names when the imported
	 * catalog values are weak, outdated, or undesirable for UI display.
	 *
	 * Key format:
	 * - ISO2 country code => Persian display name
	 *
	 * @return array<string,string>
	 */
	function bornado_geo_default_country_name_overrides() {
		return array(
			'IR' => 'ایران',
		);
	}
}
