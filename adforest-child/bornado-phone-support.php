<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_get_phone_country_options' ) ) {
	/**
	 * Return root-country phone dial code options for UX helpers.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function bornado_get_phone_country_options() {
		static $options = null;

		if ( null !== $options ) {
			return $options;
		}

		$options = array();
		if ( function_exists( 'bornado_location_terms_bypass_push' ) ) {
			bornado_location_terms_bypass_push();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'ad_country',
				'parent'     => 0,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( function_exists( 'bornado_location_terms_bypass_pop' ) ) {
			bornado_location_terms_bypass_pop();
		}

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			if ( function_exists( 'bornado_is_tier_one_country' ) && ! bornado_is_tier_one_country( $term ) ) {
				continue;
			}

			$country_data = function_exists( 'bornado_get_country_data' ) ? bornado_get_country_data( $term ) : array();
			$dial_code    = '';

			if ( class_exists( 'Bornado_Country_Phone_Service' ) ) {
				$dial_code = Bornado_Country_Phone_Service::get_country_phone_dial_code( $term );
			} elseif ( ! empty( $country_data['phone_dial_code'] ) ) {
				$dial_code = (string) $country_data['phone_dial_code'];
			}

			if ( '' === $dial_code ) {
				continue;
			}

			$options[] = array(
				'termId'      => (int) $term->term_id,
				'name'        => (string) $term->name,
				'dialCode'    => (string) $dial_code,
				'countryCode' => ! empty( $country_data['country_code'] ) ? (string) $country_data['country_code'] : '',
			);
		}

		return $options;
	}
}

if ( ! function_exists( 'bornado_get_default_phone_country_option' ) ) {
	/**
	 * Return the default phone-country option for UI helpers.
	 *
	 * @return array<string,mixed>
	 */
	function bornado_get_default_phone_country_option() {
		$options = bornado_get_phone_country_options();

		return ! empty( $options[0] ) && is_array( $options[0] ) ? $options[0] : array();
	}
}

if ( ! function_exists( 'bornado_normalize_phone_with_dial_code' ) ) {
	/**
	 * Normalize a raw phone number using a selected phone dial code.
	 *
	 * @param string $raw_phone Raw phone input.
	 * @param string $dial_code Selected dial code.
	 * @return string
	 */
	function bornado_normalize_phone_with_dial_code( $raw_phone, $dial_code = '' ) {
		$raw_phone = trim( (string) $raw_phone );
		$dial_code = trim( (string) $dial_code );

		if ( '' === $raw_phone ) {
			return '';
		}

		if ( '' !== $dial_code && class_exists( 'Bornado_Country_Phone_Service' ) ) {
			$payload = Bornado_Country_Phone_Service::normalize_phone_for_country( $raw_phone, $dial_code );
			if ( ! empty( $payload['is_valid'] ) && ! empty( $payload['normalized_phone'] ) ) {
				return (string) $payload['normalized_phone'];
			}
		}

		if ( class_exists( 'Bornado_Country_Phone_Service' ) ) {
			return (string) Bornado_Country_Phone_Service::normalize_global_phone( $raw_phone );
		}

		$raw_phone = preg_replace( '/[^\d+]/', '', $raw_phone ) ?? '';
		if ( '' === $raw_phone ) {
			return '';
		}

		if ( 0 === strpos( $raw_phone, '00' ) ) {
			$raw_phone = '+' . substr( $raw_phone, 2 );
		} elseif ( '+' !== substr( $raw_phone, 0, 1 ) ) {
			$raw_phone = '+' . ltrim( $raw_phone, '+' );
		}

		$digits = preg_replace( '/[^\d]/', '', $raw_phone ) ?? '';

		return '' !== $digits ? '+' . $digits : '';
	}
}
