<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Country_Phone_Service {

	const LOCATION_TAXONOMY       = 'ad_country';
	const POST_TYPE               = 'ad_post';
	const COUNTRY_DIAL_CODE_META  = '_bornado_country_phone_dial_code';
	const POST_PHONE_META         = '_adforest_poster_contact';

	/**
	 * Resolve the expected phone payload for a post.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $raw_phone Optional raw phone override.
	 * @return array<string,mixed>
	 */
	public static function get_phone_payload_for_post( $post_id, $raw_phone = null ) {
		$post_id       = (int) $post_id;
		$location_term = self::get_deepest_location_term_for_post( $post_id );

		if ( ! $location_term instanceof WP_Term ) {
			return self::get_empty_payload(
				array(
					'post_id'   => $post_id,
					'raw_phone' => self::resolve_raw_phone( $post_id, $raw_phone ),
					'reason'    => 'missing_location',
				)
			);
		}

		$payload            = self::get_phone_payload_for_location( $location_term, self::resolve_raw_phone( $post_id, $raw_phone ) );
		$payload['post_id'] = $post_id;

		return $payload;
	}

	/**
	 * Resolve the expected phone payload for a location term.
	 *
	 * @param WP_Term|int $location_term Location term or ID.
	 * @param string      $raw_phone Raw phone input.
	 * @return array<string,mixed>
	 */
	public static function get_phone_payload_for_location( $location_term, $raw_phone = '' ) {
		$location_term = get_term( $location_term, self::LOCATION_TAXONOMY );
		$raw_phone     = trim( (string) $raw_phone );

		if ( ! $location_term instanceof WP_Term ) {
			return self::get_empty_payload(
				array(
					'raw_phone' => $raw_phone,
					'reason'    => 'missing_location',
				)
			);
		}

		$country_term = self::get_root_country_term( $location_term );
		if ( ! $country_term instanceof WP_Term ) {
			return self::get_empty_payload(
				array(
					'raw_phone'     => $raw_phone,
					'location_term' => $location_term,
					'reason'        => 'missing_country',
				)
			);
		}

		$phone_dial_code = self::get_country_phone_dial_code( $country_term );
		if ( '' === $phone_dial_code ) {
			return self::get_empty_payload(
				array(
					'raw_phone'       => $raw_phone,
					'location_term'   => $location_term,
					'country_term'    => $country_term,
					'phone_dial_code' => '',
					'reason'          => 'missing_country_phone_dial_code',
				)
			);
		}

		$normalized_payload = self::normalize_phone_for_country( $raw_phone, $phone_dial_code );
		if ( empty( $normalized_payload['is_valid'] ) ) {
			return self::get_empty_payload(
				array(
					'raw_phone'       => $raw_phone,
					'location_term'   => $location_term,
					'country_term'    => $country_term,
					'phone_dial_code' => $phone_dial_code,
					'reason'          => ! empty( $normalized_payload['reason'] ) ? (string) $normalized_payload['reason'] : 'invalid_phone_format',
				)
			);
		}

		return array(
			'is_valid'         => true,
			'reason'           => '',
			'post_id'          => 0,
			'raw_phone'        => $raw_phone,
			'location_term'    => $location_term,
			'country_term'     => $country_term,
			'phone_dial_code'  => $phone_dial_code,
			'normalized_phone' => (string) $normalized_payload['normalized_phone'],
		);
	}

	/**
	 * Normalize a phone number using the dial code of the selected country.
	 *
	 * @param string $raw_phone Raw phone number.
	 * @param string $phone_dial_code Country phone dial code.
	 * @return array<string,mixed>
	 */
	public static function normalize_phone_for_country( $raw_phone, $phone_dial_code ) {
		$raw_phone       = trim( (string) $raw_phone );
		$phone_dial_code = self::sanitize_phone_dial_code( $phone_dial_code );

		if ( '' === $raw_phone ) {
			return array(
				'is_valid'         => false,
				'reason'           => 'missing_phone',
				'normalized_phone' => '',
			);
		}

		if ( '' === $phone_dial_code ) {
			return array(
				'is_valid'         => false,
				'reason'           => 'missing_country_phone_dial_code',
				'normalized_phone' => '',
			);
		}

		$cleaned = preg_replace( '/[^\d+]/', '', $raw_phone );
		if ( '' === $cleaned ) {
			return array(
				'is_valid'         => false,
				'reason'           => 'invalid_phone_format',
				'normalized_phone' => '',
			);
		}

		if ( 0 === strpos( $cleaned, '00' ) ) {
			$cleaned = '+' . substr( $cleaned, 2 );
		}

		$dial_digits = preg_replace( '/[^\d]/', '', $phone_dial_code );
		if ( '' === $dial_digits ) {
			return array(
				'is_valid'         => false,
				'reason'           => 'missing_country_phone_dial_code',
				'normalized_phone' => '',
			);
		}

		if ( '+' === substr( $cleaned, 0, 1 ) ) {
			$normalized_phone = self::normalize_global_phone( $cleaned );
			if ( '' === $normalized_phone ) {
				return array(
					'is_valid'         => false,
					'reason'           => 'invalid_phone_format',
					'normalized_phone' => '',
				);
			}

			if ( 0 !== strpos( $normalized_phone, $phone_dial_code ) ) {
				return array(
					'is_valid'         => false,
					'reason'           => 'phone_country_mismatch',
					'normalized_phone' => '',
				);
			}

			return array(
				'is_valid'         => true,
				'reason'           => '',
				'normalized_phone' => $normalized_phone,
			);
		}

		$digits_only = preg_replace( '/[^\d]/', '', $cleaned );
		if ( '' === $digits_only ) {
			return array(
				'is_valid'         => false,
				'reason'           => 'invalid_phone_format',
				'normalized_phone' => '',
			);
		}

		if ( 0 === strpos( $digits_only, '00' . $dial_digits ) ) {
			$normalized_phone = self::normalize_global_phone( '+' . substr( $digits_only, 2 ) );
		} elseif ( 0 === strpos( $digits_only, $dial_digits ) ) {
			$normalized_phone = self::normalize_global_phone( '+' . $digits_only );
		} else {
			$local_digits     = ltrim( $digits_only, '0' );
			$normalized_phone = self::normalize_global_phone( '+' . $dial_digits . $local_digits );
		}

		if ( '' === $normalized_phone ) {
			return array(
				'is_valid'         => false,
				'reason'           => 'invalid_phone_format',
				'normalized_phone' => '',
			);
		}

		if ( 0 !== strpos( $normalized_phone, $phone_dial_code ) ) {
			return array(
				'is_valid'         => false,
				'reason'           => 'phone_country_mismatch',
				'normalized_phone' => '',
			);
		}

		return array(
			'is_valid'         => true,
			'reason'           => '',
			'normalized_phone' => $normalized_phone,
		);
	}

	/**
	 * Resolve the dial code configured on a root country term.
	 *
	 * @param WP_Term|int $country_term Country term or ID.
	 * @return string
	 */
	public static function get_country_phone_dial_code( $country_term ) {
		$country_term = get_term( $country_term, self::LOCATION_TAXONOMY );
		if ( ! $country_term instanceof WP_Term ) {
			return '';
		}

		if ( function_exists( 'bornado_get_country_data' ) ) {
			$country_data = bornado_get_country_data( $country_term );
			if ( ! empty( $country_data['phone_dial_code'] ) ) {
				return self::sanitize_phone_dial_code( $country_data['phone_dial_code'] );
			}
		}

		return self::sanitize_phone_dial_code( (string) get_term_meta( $country_term->term_id, self::COUNTRY_DIAL_CODE_META, true ) );
	}

	/**
	 * Resolve the deepest assigned location term for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return WP_Term|null
	 */
	public static function get_deepest_location_term_for_post( $post_id ) {
		if ( class_exists( 'Bornado_Country_Currency_Service' ) ) {
			$term = Bornado_Country_Currency_Service::get_deepest_location_term_for_post( $post_id );
			return $term instanceof WP_Term ? $term : null;
		}

		$post_id = (int) $post_id;
		if ( $post_id < 1 ) {
			return null;
		}

		$terms = wp_get_post_terms( $post_id, self::LOCATION_TAXONOMY );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		usort(
			$terms,
			function ( $left, $right ) {
				$left_depth  = count( get_ancestors( (int) $left->term_id, self::LOCATION_TAXONOMY, 'taxonomy' ) );
				$right_depth = count( get_ancestors( (int) $right->term_id, self::LOCATION_TAXONOMY, 'taxonomy' ) );

				return $right_depth <=> $left_depth;
			}
		);

		return $terms[0] instanceof WP_Term ? $terms[0] : null;
	}

	/**
	 * Resolve the root country term for a location term.
	 *
	 * @param WP_Term|int $location_term Location term or ID.
	 * @return WP_Term|null
	 */
	public static function get_root_country_term( $location_term ) {
		if ( class_exists( 'Bornado_Country_Currency_Service' ) ) {
			$term = Bornado_Country_Currency_Service::get_root_country_term( $location_term );
			return $term instanceof WP_Term ? $term : null;
		}

		if ( class_exists( 'Bornado_Country_Model' ) ) {
			$term = Bornado_Country_Model::get_root_country_term( $location_term );
			return $term instanceof WP_Term ? $term : null;
		}

		return null;
	}

	/**
	 * Sanitize a stored phone dial code.
	 *
	 * @param string $phone_dial_code Dial code candidate.
	 * @return string
	 */
	public static function sanitize_phone_dial_code( $phone_dial_code ) {
		if ( class_exists( 'Bornado_Country_Model' ) && method_exists( 'Bornado_Country_Model', 'sanitize_phone_dial_code' ) ) {
			return (string) Bornado_Country_Model::sanitize_phone_dial_code( $phone_dial_code );
		}

		$phone_dial_code = trim( (string) $phone_dial_code );
		$phone_dial_code = preg_replace( '/[^\d+]/', '', $phone_dial_code );

		if ( '' === $phone_dial_code ) {
			return '';
		}

		if ( 0 === strpos( $phone_dial_code, '00' ) ) {
			$phone_dial_code = '+' . substr( $phone_dial_code, 2 );
		} elseif ( '+' !== substr( $phone_dial_code, 0, 1 ) ) {
			$phone_dial_code = '+' . ltrim( $phone_dial_code, '+' );
		}

		$digits = preg_replace( '/[^\d]/', '', $phone_dial_code );
		$normalized = '+' . $digits;

		return preg_match( '/^\+\d{1,4}$/', $normalized ) ? $normalized : '';
	}

	/**
	 * Normalize a global phone number to a canonical E.164-like value.
	 *
	 * @param string $phone Raw phone.
	 * @return string
	 */
	public static function normalize_global_phone( $phone ) {
		$phone = trim( (string) $phone );
		$phone = preg_replace( '/[^\d+]/', '', $phone ) ?? '';

		if ( '' === $phone ) {
			return '';
		}

		if ( 0 === strpos( $phone, '00' ) ) {
			$phone = '+' . substr( $phone, 2 );
		} elseif ( '+' !== substr( $phone, 0, 1 ) ) {
			$phone = '+' . ltrim( $phone, '+' );
		}

		$digits = preg_replace( '/[^\d]/', '', $phone ) ?? '';
		if ( '' === $digits ) {
			return '';
		}

		$normalized = '+' . $digits;

		return preg_match( '/^\+\d{8,16}$/', $normalized ) ? $normalized : '';
	}

	/**
	 * Resolve the raw phone value to normalize.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $raw_phone Optional raw override.
	 * @return string
	 */
	private static function resolve_raw_phone( $post_id, $raw_phone ) {
		if ( null !== $raw_phone ) {
			return trim( (string) $raw_phone );
		}

		return trim( (string) get_post_meta( (int) $post_id, self::POST_PHONE_META, true ) );
	}

	/**
	 * Build a normalized empty payload.
	 *
	 * @param array<string,mixed> $overrides Field overrides.
	 * @return array<string,mixed>
	 */
	private static function get_empty_payload( array $overrides = array() ) {
		return array_merge(
			array(
				'is_valid'         => false,
				'reason'           => '',
				'post_id'          => 0,
				'raw_phone'        => '',
				'location_term'    => null,
				'country_term'     => null,
				'phone_dial_code'  => '',
				'normalized_phone' => '',
			),
			$overrides
		);
	}
}

if ( ! function_exists( 'bornado_get_country_phone_payload_for_post' ) ) {
	/**
	 * Public helper returning the normalized phone payload for a post.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $raw_phone Optional raw phone override.
	 * @return array<string,mixed>
	 */
	function bornado_get_country_phone_payload_for_post( $post_id, $raw_phone = null ) {
		return class_exists( 'Bornado_Country_Phone_Service' )
			? Bornado_Country_Phone_Service::get_phone_payload_for_post( $post_id, $raw_phone )
			: array();
	}
}
