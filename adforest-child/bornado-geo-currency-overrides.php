<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_geo_default_currency_overrides' ) ) {
	/**
	 * Keep explicit remaps available for rare live environments whose existing
	 * `ad_currency` slugs do not follow the standard ISO-code rule.
	 *
	 * The normal path is now:
	 * - reuse existing term with `slug = strtolower( ISO 4217 code )`
	 * - create the term automatically when it does not exist
	 *
	 * @return array<string,mixed>
	 */
	function bornado_geo_default_currency_overrides() {
		return array();
	}
}

if ( ! function_exists( 'bornado_geo_default_currency_symbols' ) ) {
	/**
	 * Canonical display symbols used when creating missing `ad_currency` terms.
	 *
	 * Symbols come from a CLDR-backed ISO 4217 dataset. Some currencies
	 * intentionally fall back to their ISO code where no distinct short symbol is
	 * commonly used in international UIs.
	 *
	 * @return array<string,string>
	 */
	function bornado_geo_default_currency_symbols() {
		return array(
			'AED' => 'د.إ.',
			'AFN' => '؋',
			'ALL' => 'ALL',
			'AMD' => '֏',
			'ANG' => 'ANG',
			'AOA' => 'Kz',
			'ARS' => '$',
			'AUD' => '$',
			'AWG' => 'AWG',
			'AZN' => '₼',
			'BAM' => 'KM',
			'BBD' => '$',
			'BDT' => '৳',
			'BHD' => 'د.ب.',
			'BIF' => 'BIF',
			'BMD' => '$',
			'BND' => '$',
			'BOB' => 'Bs',
			'BOV' => 'BOV',
			'BRL' => 'R$',
			'BSD' => '$',
			'BTN' => 'BTN',
			'BWP' => 'P',
			'BYN' => 'BYN',
			'BZD' => '$',
			'CAD' => '$',
			'CDF' => 'CDF',
			'CHE' => 'CHE',
			'CHF' => 'CHF',
			'CHW' => 'CHW',
			'CLF' => 'CLF',
			'CLP' => '$',
			'CNY' => '¥',
			'COP' => '$',
			'COU' => 'COU',
			'CRC' => '₡',
			'CUP' => '$',
			'CVE' => 'CVE',
			'CZK' => 'Kč',
			'DJF' => 'DJF',
			'DKK' => 'kr',
			'DOP' => '$',
			'DZD' => 'د.ج.',
			'EGP' => 'E£',
			'ERN' => 'ERN',
			'ETB' => 'ETB',
			'EUR' => '€',
			'FJD' => '$',
			'FKP' => '£',
			'GBP' => '£',
			'GEL' => '₾',
			'GHS' => 'GH₵',
			'GIP' => '£',
			'GMD' => 'GMD',
			'GNF' => 'FG',
			'GTQ' => 'Q',
			'GYD' => '$',
			'HKD' => '$',
			'HNL' => 'L',
			'HTG' => 'HTG',
			'HUF' => 'Ft',
			'IDR' => 'Rp',
			'ILS' => '₪',
			'INR' => '₹',
			'IQD' => 'د.ع.',
			'IRR' => '﷼',
			'ISK' => 'kr',
			'JMD' => '$',
			'JOD' => 'د.أ.',
			'JPY' => '¥',
			'KES' => 'KES',
			'KGS' => '⃀',
			'KHR' => '៛',
			'KMF' => 'CF',
			'KPW' => '₩',
			'KRW' => '₩',
			'KWD' => 'د.ك.',
			'KYD' => '$',
			'KZT' => '₸',
			'LAK' => '₭',
			'LBP' => 'L£',
			'LKR' => 'Rs',
			'LRD' => '$',
			'LSL' => 'LSL',
			'LYD' => 'د.ل.',
			'MAD' => 'MAD',
			'MDL' => 'MDL',
			'MGA' => 'Ar',
			'MKD' => 'MKD',
			'MMK' => 'K',
			'MNT' => '₮',
			'MOP' => 'MOP',
			'MRU' => 'أ.م.',
			'MUR' => 'Rs',
			'MVR' => 'MVR',
			'MWK' => 'MWK',
			'MXN' => '$',
			'MXV' => 'MXV',
			'MYR' => 'RM',
			'MZN' => 'MZN',
			'NAD' => '$',
			'NGN' => '₦',
			'NIO' => 'C$',
			'NOK' => 'kr',
			'NPR' => 'Rs',
			'NZD' => '$',
			'OMR' => 'ر.ع.',
			'PAB' => 'PAB',
			'PEN' => 'PEN',
			'PGK' => 'PGK',
			'PHP' => '₱',
			'PKR' => 'Rs',
			'PLN' => 'zł',
			'PYG' => '₲',
			'QAR' => 'ر.ق.',
			'RON' => 'lei',
			'RSD' => 'RSD',
			'RUB' => '₽',
			'RWF' => 'RF',
			'SAR' => '﷼',
			'SBD' => '$',
			'SCR' => 'SCR',
			'SDG' => 'ج.س.',
			'SEK' => 'kr',
			'SGD' => '$',
			'SHP' => '£',
			'SLE' => 'SLE',
			'SLL' => 'SLL',
			'SOS' => 'SOS',
			'SRD' => '$',
			'SSP' => '£',
			'STN' => 'Db',
			'SVC' => 'SVC',
			'SYP' => '£',
			'SZL' => 'SZL',
			'THB' => '฿',
			'TJS' => 'TJS',
			'TMT' => 'TMT',
			'TND' => 'د.ت.',
			'TOP' => 'T$',
			'TRY' => '₺',
			'TTD' => '$',
			'TWD' => '$',
			'TZS' => 'TZS',
			'UAH' => '₴',
			'UGX' => 'UGX',
			'USD' => '$',
			'USN' => 'USN',
			'USS' => 'USS',
			'UYI' => 'UYI',
			'UYU' => '$',
			'UYW' => 'UYW',
			'UZS' => 'UZS',
			'VED' => 'VED',
			'VES' => 'VES',
			'VND' => '₫',
			'VUV' => 'VUV',
			'WST' => 'WST',
			'XAD' => 'XAD',
			'XAF' => 'FCFA',
			'XAG' => 'XAG',
			'XAU' => 'XAU',
			'XBA' => 'XBA',
			'XBB' => 'XBB',
			'XBC' => 'XBC',
			'XBD' => 'XBD',
			'XCD' => '$',
			'XCG' => 'Cg',
			'XDR' => 'XDR',
			'XOF' => 'F CFA',
			'XPD' => 'XPD',
			'XPF' => 'CFPF',
			'XPT' => 'XPT',
			'XSU' => 'XSU',
			'XTS' => 'XTS',
			'XUA' => 'XUA',
			'XXX' => '¤',
			'YER' => 'ر.ي.',
			'ZAR' => 'R',
			'ZMW' => 'ZK',
			'ZWG' => 'ZWG',
		);
	}
}

if ( ! function_exists( 'bornado_geo_get_currency_symbol' ) ) {
	/**
	 * @param string $currency_code
	 * @return string
	 */
	function bornado_geo_get_currency_symbol( $currency_code ) {
		$currency_code = strtoupper( sanitize_text_field( (string) $currency_code ) );
		if ( '' === $currency_code ) {
			return '';
		}

		$symbols = apply_filters( 'bornado_geo_currency_symbols', bornado_geo_default_currency_symbols() );
		if ( isset( $symbols[ $currency_code ] ) && '' !== trim( (string) $symbols[ $currency_code ] ) ) {
			return (string) $symbols[ $currency_code ];
		}

		return $currency_code;
	}
}

add_filter(
	'bornado_geo_currency_term_overrides',
	function ( $overrides ) {
		$overrides = is_array( $overrides ) ? $overrides : array();
		return $overrides + bornado_geo_default_currency_overrides();
	},
	10,
	1
);

add_filter(
	'bornado_geo_currency_symbols',
	function ( $symbols ) {
		$symbols = is_array( $symbols ) ? $symbols : array();
		return $symbols + bornado_geo_default_currency_symbols();
	},
	10,
	1
);
