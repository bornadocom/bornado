<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_get_phone_country_geo_lookup' ) ) {
	/**
	 * Build a lookup of Geo Catalog countries by root term id.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	function bornado_get_phone_country_geo_lookup() {
		static $lookup = null;

		if ( null !== $lookup ) {
			return $lookup;
		}

		$lookup = array();

		if ( class_exists( 'Bornado_Geo_Catalog' ) && method_exists( 'Bornado_Geo_Catalog', 'get_country_search_items' ) ) {
			$items = Bornado_Geo_Catalog::get_country_search_items( '', 252 );
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					if ( ! is_array( $item ) || empty( $item['legacyTermId'] ) ) {
						continue;
					}

					$lookup[ (int) $item['legacyTermId'] ] = $item;
				}
			}
		}

		return $lookup;
	}
}

if ( ! function_exists( 'bornado_build_phone_country_search_tokens' ) ) {
	/**
	 * Build a compact search token string for client-side filtering.
	 *
	 * @param string $name_fa Localized country name.
	 * @param string $name_en English country name.
	 * @param string $country_code ISO2 code.
	 * @param string $dial_code International dial code.
	 * @return string
	 */
	function bornado_build_phone_country_search_tokens( $name_fa, $name_en, $country_code, $dial_code ) {
		$tokens = array_filter(
			array(
				trim( (string) $name_fa ),
				trim( (string) $name_en ),
				strtoupper( trim( (string) $country_code ) ),
				trim( (string) $dial_code ),
				ltrim( trim( (string) $dial_code ), '+' ),
			)
		);

		return implode( ' ', $tokens );
	}
}

if ( ! function_exists( 'bornado_match_phone_country_option' ) ) {
	/**
	 * Match a phone-country option by the available identifiers.
	 *
	 * @param array<int,array<string,mixed>> $options Available options.
	 * @param array<string,mixed>            $lookup  Lookup payload.
	 * @return array<string,mixed>
	 */
	function bornado_match_phone_country_option( array $options, array $lookup ) {
		$term_id      = isset( $lookup['termId'] ) ? absint( $lookup['termId'] ) : 0;
		$dial_code    = isset( $lookup['dialCode'] ) ? trim( (string) $lookup['dialCode'] ) : '';
		$country_code = isset( $lookup['countryCode'] ) ? strtoupper( trim( (string) $lookup['countryCode'] ) ) : '';

		if ( $term_id > 0 ) {
			foreach ( $options as $option ) {
				if ( (int) ( $option['termId'] ?? 0 ) === $term_id ) {
					return $option;
				}
			}
		}

		if ( '' !== $dial_code ) {
			foreach ( $options as $option ) {
				if ( trim( (string) ( $option['dialCode'] ?? '' ) ) === $dial_code ) {
					return $option;
				}
			}
		}

		if ( '' !== $country_code ) {
			foreach ( $options as $option ) {
				if ( strtoupper( trim( (string) ( $option['countryCode'] ?? '' ) ) ) === $country_code ) {
					return $option;
				}
			}
		}

		return array();
	}
}

if ( ! function_exists( 'bornado_get_phone_country_locale_candidates' ) ) {
	/**
	 * Build locale-based fallback country candidates.
	 *
	 * @return array<int,array<string,string>>
	 */
	function bornado_get_phone_country_locale_candidates() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale = strtolower( str_replace( '_', '-', (string) $locale ) );
		$parts  = array_values( array_filter( explode( '-', $locale ) ) );
		$items  = array();

		if ( ! empty( $parts[1] ) && preg_match( '/^[a-z]{2}$/', (string) $parts[1] ) ) {
			$items[] = array(
				'countryCode' => strtoupper( (string) $parts[1] ),
			);
		}

		if ( ! empty( $parts[0] ) ) {
			$language_map = array(
				'fa' => 'IR',
				'ar' => 'AE',
				'en' => 'GB',
			);

			if ( isset( $language_map[ $parts[0] ] ) ) {
				$items[] = array(
					'countryCode' => (string) $language_map[ $parts[0] ],
				);
			}
		}

		return $items;
	}
}

if ( ! function_exists( 'bornado_resolve_default_phone_country_option' ) ) {
	/**
	 * Resolve the default phone-country option using runtime context first.
	 *
	 * @param array<int,array<string,mixed>>|null $options Option list.
	 * @return array<string,mixed>
	 */
	function bornado_resolve_default_phone_country_option( $options = null ) {
		$options = is_array( $options ) ? $options : bornado_get_phone_country_options();

		if ( empty( $options ) ) {
			return array();
		}

		if ( class_exists( 'Bornado_Market_Context_Service' ) && method_exists( 'Bornado_Market_Context_Service', 'resolve_suggested_phone_country' ) ) {
			$context_match = bornado_match_phone_country_option(
				$options,
				(array) Bornado_Market_Context_Service::resolve_suggested_phone_country()
			);

			if ( ! empty( $context_match ) ) {
				return $context_match;
			}
		}

		foreach ( bornado_get_phone_country_locale_candidates() as $candidate ) {
			$locale_match = bornado_match_phone_country_option( $options, $candidate );
			if ( ! empty( $locale_match ) ) {
				return $locale_match;
			}
		}

		return ! empty( $options[0] ) && is_array( $options[0] ) ? $options[0] : array();
	}
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

		$geo_lookup = bornado_get_phone_country_geo_lookup();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			if ( function_exists( 'bornado_is_tier_one_country' ) && ! bornado_is_tier_one_country( $term ) ) {
				continue;
			}

			$country_data = function_exists( 'bornado_get_country_data' ) ? bornado_get_country_data( $term ) : array();
			$geo_item     = isset( $geo_lookup[ (int) $term->term_id ] ) && is_array( $geo_lookup[ (int) $term->term_id ] )
				? $geo_lookup[ (int) $term->term_id ]
				: array();
			$dial_code    = '';
			$name_fa      = trim( (string) $term->name );
			$name_en      = ! empty( $country_data['display_name_en'] ) ? trim( (string) $country_data['display_name_en'] ) : '';
			$country_code = ! empty( $country_data['country_code'] ) ? strtoupper( trim( (string) $country_data['country_code'] ) ) : '';

			if ( class_exists( 'Bornado_Country_Phone_Service' ) ) {
				$dial_code = Bornado_Country_Phone_Service::get_country_phone_dial_code( $term );
			} elseif ( ! empty( $country_data['phone_dial_code'] ) ) {
				$dial_code = (string) $country_data['phone_dial_code'];
			}

			if ( '' === $dial_code ) {
				continue;
			}

			if ( '' === $name_en && ! empty( $geo_item['nameEn'] ) ) {
				$name_en = trim( (string) $geo_item['nameEn'] );
			}

			if ( '' === $country_code && ! empty( $geo_item['iso2'] ) ) {
				$country_code = strtoupper( trim( (string) $geo_item['iso2'] ) );
			}

			$options[] = array(
				'termId'        => (int) $term->term_id,
				'name'          => $name_fa,
				'displayNameFa' => $name_fa,
				'displayNameEn' => $name_en,
				'dialCode'      => (string) $dial_code,
				'countryCode'   => $country_code,
				'searchTokens'  => bornado_build_phone_country_search_tokens( $name_fa, $name_en, $country_code, (string) $dial_code ),
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

		return bornado_resolve_default_phone_country_option( $options );
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
