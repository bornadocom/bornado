<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Ad_Hash_Service' ) ) {
	return;
}

final class Bornado_Ad_Hash_Service {
	/**
	 * @var Bornado_Ad_Hash_Service|null
	 */
	private static $instance = null;

	/**
	 * @var \Hashids\Hashids|null
	 */
	private $hashids = null;

	/**
	 * @var string
	 */
	private $fallbackAlphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

	private function __construct() {
		try {
			$this->hashids = new \Hashids\Hashids(
				$this->get_salt(),
				Bornado_Ad_Permalinks::HASH_MIN_LENGTH
			);
		} catch ( \Throwable $throwable ) {
			$this->hashids = null;
		}
	}

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param int $post_id
	 * @return string
	 */
	public function encode_id( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return '';
		}

		if ( $this->hashids instanceof \Hashids\Hashids ) {
			$hash = $this->hashids->encode( $post_id );
			if ( '' !== $hash ) {
				return $hash;
			}
		}

		return $this->fallback_encode( $post_id );
	}

	/**
	 * @param string $hash
	 * @return int
	 */
	public function decode_id( $hash ) {
		$hash = is_string( $hash ) ? trim( $hash ) : '';
		if ( '' === $hash ) {
			return 0;
		}

		if ( $this->hashids instanceof \Hashids\Hashids ) {
			$decoded = $this->hashids->decode( $hash );
			if ( 1 === count( $decoded ) ) {
				return (int) $decoded[0];
			}
		}

		return $this->fallback_decode( $hash );
	}

	/**
	 * @return string
	 */
	private function get_salt() {
		if ( defined( 'BORNADO_AD_HASHIDS_SALT' ) && BORNADO_AD_HASHIDS_SALT ) {
			return (string) BORNADO_AD_HASHIDS_SALT;
		}

		return wp_salt( 'auth' ) . '|' . network_home_url( '/' );
	}

	/**
	 * Deterministic fallback used only when the runtime lacks bcmath/gmp.
	 *
	 * @param int $number
	 * @return string
	 */
	private function fallback_encode( $number ) {
		$alphabet = $this->get_fallback_alphabet();
		$base     = strlen( $alphabet );
		$value    = (int) $number;
		$raw      = '';

		while ( $value > 0 ) {
			$raw   = $alphabet[ $value % $base ] . $raw;
			$value = (int) floor( $value / $base );
		}

		if ( '' === $raw ) {
			$raw = $alphabet[0];
		}

		$lengthMarker = $alphabet[ min( strlen( $raw ), $base - 1 ) ];
		$checksum     = $alphabet[ hexdec( substr( md5( $this->get_salt() . '|' . $raw ), 0, 2 ) ) % $base ];
		$fillerLength = max( 0, Bornado_Ad_Permalinks::HASH_MIN_LENGTH - 2 - strlen( $raw ) );
		$filler       = '';

		if ( $fillerLength > 0 ) {
			$fillerSeed = hash( 'sha256', $this->get_salt() . '|' . $raw );
			for ( $i = 0; $i < $fillerLength; $i++ ) {
				$filler .= $alphabet[ hexdec( substr( $fillerSeed, ( $i * 2 ) % 62, 2 ) ) % $base ];
			}
		}

		return $checksum . $lengthMarker . $filler . $raw;
	}

	/**
	 * @param string $hash
	 * @return int
	 */
	private function fallback_decode( $hash ) {
		$alphabet = $this->get_fallback_alphabet();
		$base     = strlen( $alphabet );
		$hash     = preg_replace( '/[^A-Za-z0-9]/', '', $hash );

		if ( ! is_string( $hash ) || strlen( $hash ) < 3 ) {
			return 0;
		}

		$lengthIndex = strpos( $alphabet, $hash[1] );
		if ( false === $lengthIndex || $lengthIndex <= 0 ) {
			return 0;
		}

		$raw = substr( $hash, -$lengthIndex );
		if ( '' === $raw ) {
			return 0;
		}

		$expectedChecksum = $alphabet[ hexdec( substr( md5( $this->get_salt() . '|' . $raw ), 0, 2 ) ) % $base ];
		if ( $hash[0] !== $expectedChecksum ) {
			return 0;
		}

		$value = 0;
		foreach ( str_split( $raw ) as $char ) {
			$position = strpos( $alphabet, $char );
			if ( false === $position ) {
				return 0;
			}

			$value = ( $value * $base ) + $position;
		}

		return (int) $value;
	}

	/**
	 * @return string
	 */
	private function get_fallback_alphabet() {
		$seed     = substr( hash( 'sha256', $this->get_salt() ), 0, 32 );
		$alphabet = str_split( $this->fallbackAlphabet );
		$length   = count( $alphabet );
		$cursor   = 0;

		for ( $i = $length - 1; $i > 0; $i-- ) {
			$chunk = substr( $seed, $cursor, 2 );
			if ( '' === $chunk ) {
				$seed   = hash( 'sha256', $seed . $this->get_salt() );
				$chunk  = substr( $seed, 0, 2 );
				$cursor = 0;
			}

			$j = hexdec( $chunk ) % ( $i + 1 );

			$temp          = $alphabet[ $i ];
			$alphabet[ $i ] = $alphabet[ $j ];
			$alphabet[ $j ] = $temp;
			$cursor        += 2;
		}

		return implode( '', $alphabet );
	}
}
