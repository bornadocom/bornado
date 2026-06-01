<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Ad_Ownership_Phone {

	/**
	 * Normalize a verified user phone into one canonical value.
	 *
	 * @param string $raw_phone Raw phone number.
	 * @param string $dial_code Optional dial code.
	 * @return string
	 */
	public static function normalize_user_phone( $raw_phone, $dial_code = '' ) {
		$raw_phone = self::normalize_unicode_digits( $raw_phone );
		$dial_code = self::normalize_unicode_digits( $dial_code );

		if ( function_exists( 'bornado_normalize_phone_with_dial_code' ) ) {
			$normalized = bornado_normalize_phone_with_dial_code( $raw_phone, $dial_code );
			if ( '' !== $normalized ) {
				return (string) $normalized;
			}
		}

		if ( class_exists( 'Bornado_Country_Phone_Service' ) ) {
			$normalized = (string) Bornado_Country_Phone_Service::normalize_global_phone( $raw_phone );
			if ( '' !== $normalized ) {
				return $normalized;
			}
		}

		return self::normalize_global_fallback( $raw_phone );
	}

	/**
	 * Normalize one listing phone using the listing location when possible.
	 *
	 * @param int         $post_id Listing ID.
	 * @param string|null $raw_phone Optional phone override.
	 * @return string
	 */
	public static function normalize_listing_phone( $post_id, $raw_phone = null ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return '';
		}

		if ( null === $raw_phone ) {
			$raw_phone = get_post_meta( $post_id, '_adforest_poster_contact', true );
		}

		$raw_phone = self::normalize_unicode_digits( $raw_phone );
		if ( '' === trim( (string) $raw_phone ) ) {
			return '';
		}

		if ( class_exists( 'Bornado_Country_Phone_Service' ) ) {
			$payload = Bornado_Country_Phone_Service::get_phone_payload_for_post( $post_id, $raw_phone );
			if ( ! empty( $payload['is_valid'] ) && ! empty( $payload['normalized_phone'] ) ) {
				return (string) $payload['normalized_phone'];
			}
		}

		return self::normalize_user_phone( $raw_phone );
	}

	/**
	 * Convert Arabic/Persian numerals to ASCII digits.
	 *
	 * @param string $value Raw string.
	 * @return string
	 */
	public static function normalize_unicode_digits( $value ) {
		$value = (string) $value;

		$map = array(
			'۰' => '0',
			'۱' => '1',
			'۲' => '2',
			'۳' => '3',
			'۴' => '4',
			'۵' => '5',
			'۶' => '6',
			'۷' => '7',
			'۸' => '8',
			'۹' => '9',
			'٠' => '0',
			'١' => '1',
			'٢' => '2',
			'٣' => '3',
			'٤' => '4',
			'٥' => '5',
			'٦' => '6',
			'٧' => '7',
			'٨' => '8',
			'٩' => '9',
		);

		return strtr( $value, $map );
	}

	/**
	 * Remove common formatting noise for SQL and PHP comparisons.
	 *
	 * @param string $phone Raw phone value.
	 * @return string
	 */
	public static function sanitize_for_comparison( $phone ) {
		$phone = self::normalize_unicode_digits( $phone );
		$phone = preg_replace( '/[^\d+]/', '', (string) $phone );

		return is_string( $phone ) ? $phone : '';
	}

	/**
	 * Build a conservative set of raw search variants.
	 *
	 * @param string $canonical_phone Canonical phone.
	 * @return array<int,string>
	 */
	public static function build_raw_search_candidates( $canonical_phone ) {
		$canonical_phone = self::normalize_user_phone( $canonical_phone );
		if ( '' === $canonical_phone ) {
			return array();
		}

		$digits     = self::digits_only( $canonical_phone );
		$candidates = array(
			$canonical_phone,
			$digits,
			'00' . $digits,
		);

		if ( 0 === strpos( $canonical_phone, '+98' ) ) {
			$local_digits = substr( $digits, 2 );
			if ( false !== $local_digits && '' !== $local_digits ) {
				$candidates[] = '0' . $local_digits;
				$candidates[] = $local_digits;
			}
		}

		$candidates = array_map( array( __CLASS__, 'normalize_unicode_digits' ), $candidates );
		$candidates = array_map( 'trim', $candidates );
		$candidates = array_filter(
			array_unique( $candidates ),
			static function ( $value ) {
				return '' !== (string) $value;
			}
		);

		return array_values( $candidates );
	}

	/**
	 * Return digits only from a canonical phone.
	 *
	 * @param string $phone Raw phone value.
	 * @return string
	 */
	public static function digits_only( $phone ) {
		$digits = preg_replace( '/[^\d]/', '', self::normalize_unicode_digits( $phone ) );

		return is_string( $digits ) ? $digits : '';
	}

	/**
	 * Normalize one phone number without relying on project helpers.
	 *
	 * @param string $raw_phone Raw phone.
	 * @return string
	 */
	private static function normalize_global_fallback( $raw_phone ) {
		$raw_phone = trim( self::normalize_unicode_digits( $raw_phone ) );
		$raw_phone = preg_replace( '/[^\d+]/', '', $raw_phone );

		if ( ! is_string( $raw_phone ) || '' === $raw_phone ) {
			return '';
		}

		if ( 0 === strpos( $raw_phone, '00' ) ) {
			$raw_phone = '+' . substr( $raw_phone, 2 );
		} elseif ( '+' !== substr( $raw_phone, 0, 1 ) ) {
			$raw_phone = '+' . ltrim( $raw_phone, '+' );
		}

		$digits = self::digits_only( $raw_phone );
		if ( '' === $digits ) {
			return '';
		}

		$normalized = '+' . $digits;

		return preg_match( '/^\+\d{8,16}$/', $normalized ) ? $normalized : '';
	}
}
