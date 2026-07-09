<?php
/**
 * Shared country data for the phone-country picker.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Phone_Country_Picker_Service' ) ) {
	return;
}

final class Bornado_Phone_Country_Picker_Service {
	/**
	 * Runtime cache for normalized countries.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private static $countries = null;

	/**
	 * Geo-catalog lookup keyed by legacy term id.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private static $geo_country_lookup = null;

	/**
	 * Return normalized phone-country options.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_country_options() {
		if ( null !== self::$countries ) {
			return self::$countries;
		}

		$base_items = self::get_base_phone_countries();
		$countries  = array();

		foreach ( $base_items as $item ) {
			$term_id      = isset( $item['termId'] ) ? absint( $item['termId'] ) : 0;
			$dial_code    = self::sanitize_dial_code( isset( $item['dialCode'] ) ? $item['dialCode'] : '' );
			$country_code = strtoupper( sanitize_text_field( isset( $item['countryCode'] ) ? (string) $item['countryCode'] : '' ) );
			$name_fa      = trim( (string) ( $item['name'] ?? '' ) );
			$country_data = self::get_country_data( $term_id );
			$name_en      = trim( (string) ( $country_data['display_name_en'] ?? '' ) );
			$market_status = trim( (string) ( $country_data['market_status'] ?? '' ) );
			$geo_country   = self::get_geo_country_by_term_id( $term_id );

			if ( '' === $dial_code || '' === $name_fa ) {
				continue;
			}

			if ( '' === $country_code && ! empty( $country_data['country_code'] ) ) {
				$country_code = strtoupper( sanitize_text_field( (string) $country_data['country_code'] ) );
			}

			if ( '' === $country_code && ! empty( $geo_country['iso2'] ) ) {
				$country_code = strtoupper( sanitize_text_field( (string) $geo_country['iso2'] ) );
			}

			if ( '' === $name_en && ! empty( $geo_country['nameEn'] ) ) {
				$name_en = trim( (string) $geo_country['nameEn'] );
			}

			$countries[] = array(
				'termId'         => $term_id,
				'name'           => $name_fa,
				'displayNameFa'  => $name_fa,
				'displayNameEn'  => $name_en,
				'dialCode'       => $dial_code,
				'countryCode'    => $country_code,
				'flagEmoji'      => self::country_code_to_flag_emoji( $country_code ),
				'flagUrl'        => self::build_flag_url( $country_code ),
				'marketStatus'   => $market_status,
				'isTierOne'      => 'tier1' === $market_status,
				'isPinned'       => 'tier1' === $market_status,
				'searchTokens'   => self::build_search_tokens( $name_fa, $name_en, $country_code, $dial_code ),
			);
		}

		self::$countries = array_values( self::unique_countries_by_dial_code( $countries ) );

		return self::$countries;
	}

	/**
	 * Return the legacy default option used by existing forms.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_legacy_default_country() {
		if ( function_exists( 'bornado_get_default_phone_country_option' ) ) {
			$default = bornado_get_default_phone_country_option();
			if ( ! empty( $default ) && is_array( $default ) ) {
				return $default;
			}
		}

		$countries = self::get_country_options();

		return ! empty( $countries[0] ) && is_array( $countries[0] ) ? $countries[0] : array();
	}

	/**
	 * Find a country by available identifiers.
	 *
	 * @param array<string,mixed> $lookup Lookup arguments.
	 * @return array<string,mixed>
	 */
	public static function find_country( array $lookup ) {
		$term_id      = isset( $lookup['termId'] ) ? absint( $lookup['termId'] ) : 0;
		$country_code = strtoupper( sanitize_text_field( (string) ( $lookup['countryCode'] ?? '' ) ) );
		$dial_code    = self::sanitize_dial_code( $lookup['dialCode'] ?? '' );

		foreach ( self::get_country_options() as $country ) {
			if ( $term_id > 0 && $term_id === (int) $country['termId'] ) {
				return $country;
			}

			if ( '' !== $country_code && $country_code === (string) $country['countryCode'] ) {
				return $country;
			}

			if ( '' !== $dial_code && $dial_code === (string) $country['dialCode'] ) {
				return $country;
			}
		}

		return array();
	}

	/**
	 * Return the base phone-country payload from existing runtime data.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function get_base_phone_countries() {
		if ( function_exists( 'bornado_get_phone_country_options' ) ) {
			$options = bornado_get_phone_country_options();
			return is_array( $options ) ? $options : array();
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

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$country_data = self::get_country_data( (int) $term->term_id );
			$dial_code    = self::sanitize_dial_code( $country_data['phone_dial_code'] ?? '' );
			$country_code = strtoupper( sanitize_text_field( (string) ( $country_data['country_code'] ?? '' ) ) );

			if ( '' === $dial_code ) {
				continue;
			}

			$options[] = array(
				'termId'      => (int) $term->term_id,
				'name'        => (string) $term->name,
				'dialCode'    => $dial_code,
				'countryCode' => $country_code,
			);
		}

		return $options;
	}

	/**
	 * Read normalized country meta from the routing model when available.
	 *
	 * @param int $term_id Root-country term id.
	 * @return array<string,mixed>
	 */
	private static function get_country_data( $term_id ) {
		$term_id = absint( $term_id );
		if ( $term_id < 1 ) {
			return array();
		}

		if ( function_exists( 'bornado_get_country_data' ) ) {
			$data = bornado_get_country_data( $term_id );
			return is_array( $data ) ? $data : array();
		}

		return array();
	}

	/**
	 * Read geo-catalog data keyed by legacy term id.
	 *
	 * @param int $term_id Root-country term id.
	 * @return array<string,mixed>
	 */
	private static function get_geo_country_by_term_id( $term_id ) {
		$term_id = absint( $term_id );
		if ( $term_id < 1 ) {
			return array();
		}

		if ( null === self::$geo_country_lookup ) {
			self::$geo_country_lookup = array();

			if ( class_exists( 'Bornado_Geo_Catalog' ) && method_exists( 'Bornado_Geo_Catalog', 'get_country_search_items' ) ) {
				$items = Bornado_Geo_Catalog::get_country_search_items( '', 252 );
				if ( is_array( $items ) ) {
					foreach ( $items as $item ) {
						if ( ! is_array( $item ) || empty( $item['legacyTermId'] ) ) {
							continue;
						}

						self::$geo_country_lookup[ (int) $item['legacyTermId'] ] = $item;
					}
				}
			}
		}

		return isset( self::$geo_country_lookup[ $term_id ] ) && is_array( self::$geo_country_lookup[ $term_id ] )
			? self::$geo_country_lookup[ $term_id ]
			: array();
	}

	/**
	 * Convert an ISO2 code into a flag emoji.
	 *
	 * @param string $country_code ISO 3166-1 alpha-2.
	 * @return string
	 */
	private static function country_code_to_flag_emoji( $country_code ) {
		$country_code = strtoupper( preg_replace( '/[^A-Z]/', '', (string) $country_code ) );
		if ( 2 !== strlen( $country_code ) || ! function_exists( 'mb_chr' ) ) {
			return '';
		}

		$offset = 127397;
		$chars  = preg_split( '//u', $country_code, -1, PREG_SPLIT_NO_EMPTY );
		$flag   = '';

		foreach ( $chars as $char ) {
			$flag .= mb_chr( $offset + ord( $char ), 'UTF-8' );
		}

		return $flag;
	}

	/**
	 * Build a stable flag image URL for the given ISO2 code.
	 *
	 * @param string $country_code ISO 3166-1 alpha-2.
	 * @return string
	 */
	private static function build_flag_url( $country_code ) {
		$country_code = strtolower( preg_replace( '/[^a-z]/i', '', (string) $country_code ) );
		if ( 2 !== strlen( $country_code ) ) {
			return '';
		}

		return 'https://flagcdn.com/w40/' . $country_code . '.png';
	}

	/**
	 * Generate a compact search token string for JS filtering.
	 *
	 * @param string $name_fa Localized country name.
	 * @param string $name_en English country name.
	 * @param string $country_code ISO2 code.
	 * @param string $dial_code International dial code.
	 * @return string
	 */
	private static function build_search_tokens( $name_fa, $name_en, $country_code, $dial_code ) {
		$tokens = array_filter(
			array(
				trim( (string) $name_fa ),
				trim( (string) $name_en ),
				trim( (string) $country_code ),
				trim( (string) $dial_code ),
				ltrim( trim( (string) $dial_code ), '+' ),
			)
		);

		return implode( ' ', $tokens );
	}

	/**
	 * Deduplicate countries by normalized dial code.
	 *
	 * @param array<int,array<string,mixed>> $countries Countries.
	 * @return array<string,array<string,mixed>>
	 */
	private static function unique_countries_by_dial_code( array $countries ) {
		$unique = array();

		foreach ( $countries as $country ) {
			$dial_code = self::sanitize_dial_code( $country['dialCode'] ?? '' );
			if ( '' === $dial_code ) {
				continue;
			}

			if ( ! isset( $unique[ $dial_code ] ) ) {
				$unique[ $dial_code ] = $country;
			}
		}

		return $unique;
	}

	/**
	 * Normalize a dial-code string.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	private static function sanitize_dial_code( $value ) {
		if ( class_exists( 'Bornado_Country_Phone_Service' ) && method_exists( 'Bornado_Country_Phone_Service', 'sanitize_phone_dial_code' ) ) {
			return (string) Bornado_Country_Phone_Service::sanitize_phone_dial_code( $value );
		}

		$cleaned = trim( (string) $value );
		$cleaned = preg_replace( '/[^\d+]/', '', $cleaned ) ?? '';
		if ( '' === $cleaned ) {
			return '';
		}

		if ( 0 === strpos( $cleaned, '00' ) ) {
			$cleaned = '+' . substr( $cleaned, 2 );
		} elseif ( '+' !== substr( $cleaned, 0, 1 ) ) {
			$cleaned = '+' . ltrim( $cleaned, '+' );
		}

		$digits = preg_replace( '/[^\d]/', '', $cleaned ) ?? '';
		return preg_match( '/^\+\d{1,4}$/', '+' . $digits ) ? '+' . $digits : '';
	}
}
