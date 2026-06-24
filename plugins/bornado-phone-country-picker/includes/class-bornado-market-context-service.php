<?php
/**
 * Suggestive market/location context for frontend UX.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Market_Context_Service' ) ) {
	return;
}

final class Bornado_Market_Context_Service {
	/**
	 * Resolve a suggested phone-country payload for UX only.
	 *
	 * @return array<string,mixed>
	 */
	public static function resolve_suggested_phone_country() {
		$sources = array(
			self::resolve_from_location_picker(),
			self::resolve_from_search_context(),
			self::resolve_from_route_context(),
		);

		foreach ( $sources as $candidate ) {
			if ( empty( $candidate ) || ! is_array( $candidate ) ) {
				continue;
			}

			$country = Bornado_Phone_Country_Picker_Service::find_country( $candidate );
			if ( empty( $country ) ) {
				continue;
			}

			$country['source']     = (string) ( $candidate['source'] ?? 'context' );
			$country['confidence'] = (string) ( $candidate['confidence'] ?? 'medium' );

			return $country;
		}

		return array();
	}

	/**
	 * Resolve context from the location-picker service.
	 *
	 * @return array<string,mixed>
	 */
	private static function resolve_from_location_picker() {
		if ( ! class_exists( 'Bornado_Location_Picker_Service' ) || ! method_exists( 'Bornado_Location_Picker_Service', 'get_selected_location' ) ) {
			return array();
		}

		$selected = Bornado_Location_Picker_Service::get_selected_location( true );
		if ( ! is_array( $selected ) ) {
			return array();
		}

		$country = isset( $selected['country'] ) && is_array( $selected['country'] ) ? $selected['country'] : array();

		return array_filter(
			array(
				'termId'      => ! empty( $country['id'] ) ? absint( $country['id'] ) : 0,
				'countryCode' => ! empty( $selected['country_code'] ) ? (string) $selected['country_code'] : (string) ( $country['countryCode'] ?? '' ),
				'dialCode'    => '',
				'source'      => 'location_picker',
				'confidence'  => ! empty( $country['id'] ) || ! empty( $selected['country_code'] ) ? 'high' : '',
			),
			static function ( $value ) {
				return '' !== (string) $value && 0 !== $value;
			}
		);
	}

	/**
	 * Resolve context directly from the persisted/search context.
	 *
	 * @return array<string,mixed>
	 */
	private static function resolve_from_search_context() {
		if ( ! function_exists( 'bornado_search_get_selected_context' ) ) {
			return array();
		}

		$context    = bornado_search_get_selected_context( true );
		$country_id = is_array( $context ) && ! empty( $context['country'] ) ? absint( $context['country'] ) : 0;
		if ( $country_id < 1 ) {
			return array();
		}

		return array(
			'termId'      => $country_id,
			'countryCode' => '',
			'dialCode'    => '',
			'source'      => 'search_context',
			'confidence'  => 'medium',
		);
	}

	/**
	 * Resolve context directly from the current semantic route.
	 *
	 * @return array<string,mixed>
	 */
	private static function resolve_from_route_context() {
		if ( ! function_exists( 'bornado_seo_routing_get_context' ) ) {
			return array();
		}

		$context = bornado_seo_routing_get_context();
		if ( ! is_array( $context ) ) {
			return array();
		}

		$country_term = ! empty( $context['country_term'] ) && $context['country_term'] instanceof WP_Term
			? $context['country_term']
			: null;

		if ( ! $country_term instanceof WP_Term ) {
			return array();
		}

		return array(
			'termId'      => (int) $country_term->term_id,
			'countryCode' => '',
			'dialCode'    => '',
			'source'      => 'route',
			'confidence'  => 'high',
		);
	}
}
