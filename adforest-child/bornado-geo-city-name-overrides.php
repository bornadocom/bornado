<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_geo_default_city_name_overrides' ) ) {
	/**
	 * Project-level fallback map for Persian city names when GeoNames alt names
	 * are missing, weak, or inconsistent.
	 *
	 * Key format:
	 * - integer GeoName ID => Persian display name
	 *
	 * Example:
	 * return array(
	 *     6173331 => 'ونکوور',
	 *     6167865 => 'تورنتو',
	 * );
	 *
	 * @return array<int|string,string>
	 */
	function bornado_geo_default_city_name_overrides() {
		return array();
	}
}
