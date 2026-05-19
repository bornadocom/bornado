<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Country_Currency_Service {

	const LOCATION_TAXONOMY  = 'ad_country';
	const CURRENCY_TAXONOMY  = 'ad_currency';
	const POST_TYPE          = 'ad_post';
	const COUNTRY_CURRENCY_META = '_bornado_country_currency_term_id';
	const POST_CURRENCY_META = '_adforest_ad_currency';

	/**
	 * Resolve the expected country/currency payload for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	public static function get_currency_payload_for_post( $post_id ) {
		$post_id       = (int) $post_id;
		$location_term = self::get_deepest_location_term_for_post( $post_id );

		if ( ! $location_term instanceof WP_Term ) {
			return self::get_empty_payload(
				array(
					'post_id' => $post_id,
					'reason'  => 'missing_location',
				)
			);
		}

		$payload            = self::get_currency_payload_for_location( $location_term );
		$payload['post_id'] = $post_id;

		return $payload;
	}

	/**
	 * Resolve the expected country/currency payload for a location term.
	 *
	 * @param WP_Term|int $location_term Location term or ID.
	 * @return array<string,mixed>
	 */
	public static function get_currency_payload_for_location( $location_term ) {
		$location_term = get_term( $location_term, self::LOCATION_TAXONOMY );
		if ( ! $location_term instanceof WP_Term ) {
			return self::get_empty_payload(
				array(
					'reason' => 'missing_location',
				)
			);
		}

		$country_term = self::get_root_country_term( $location_term );
		if ( ! $country_term instanceof WP_Term ) {
			return self::get_empty_payload(
				array(
					'location_term' => $location_term,
					'reason'        => 'missing_country',
				)
			);
		}

		$currency_term = self::get_country_currency_term( $country_term );
		if ( ! $currency_term instanceof WP_Term ) {
			return self::get_empty_payload(
				array(
					'location_term' => $location_term,
					'country_term'  => $country_term,
					'reason'        => 'missing_country_currency',
				)
			);
		}

		return array(
			'is_valid'          => true,
			'reason'            => '',
			'post_id'           => 0,
			'location_term'     => $location_term,
			'country_term'      => $country_term,
			'currency_term'     => $currency_term,
			'currency_term_id'  => (int) $currency_term->term_id,
			'currency_name'     => (string) $currency_term->name,
			'currency_slug'     => (string) $currency_term->slug,
			'currency_meta'     => (string) $currency_term->name,
		);
	}

	/**
	 * Resolve the deepest assigned ad_country term for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return WP_Term|null
	 */
	public static function get_deepest_location_term_for_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 ) {
			return null;
		}

		$terms = wp_get_post_terms(
			$post_id,
			self::LOCATION_TAXONOMY,
			array(
				'orderby' => 'none',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		usort(
			$terms,
			function ( $left, $right ) {
				$left_depth  = self::get_term_depth( $left );
				$right_depth = self::get_term_depth( $right );

				if ( $left_depth === $right_depth ) {
					return (int) $right->term_id <=> (int) $left->term_id;
				}

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
		if ( class_exists( 'Bornado_Country_Model' ) ) {
			$root_country = Bornado_Country_Model::get_root_country_term( $location_term );
			return $root_country instanceof WP_Term ? $root_country : null;
		}

		$location_term = get_term( $location_term, self::LOCATION_TAXONOMY );
		if ( ! $location_term instanceof WP_Term ) {
			return null;
		}

		if ( 0 === (int) $location_term->parent ) {
			return $location_term;
		}

		$ancestors = array_reverse( array_map( 'intval', get_ancestors( (int) $location_term->term_id, self::LOCATION_TAXONOMY, 'taxonomy' ) ) );
		if ( empty( $ancestors ) ) {
			return null;
		}

		$root_country = get_term( (int) $ancestors[0], self::LOCATION_TAXONOMY );

		return $root_country instanceof WP_Term ? $root_country : null;
	}

	/**
	 * Resolve the configured currency term for a country term.
	 *
	 * @param WP_Term|int $country_term Country term or ID.
	 * @return WP_Term|null
	 */
	public static function get_country_currency_term( $country_term ) {
		$country_term = get_term( $country_term, self::LOCATION_TAXONOMY );
		if ( ! $country_term instanceof WP_Term ) {
			return null;
		}

		$currency_term_id = 0;

		if ( class_exists( 'Bornado_Country_Model' ) ) {
			$country_data = Bornado_Country_Model::get_country_data( $country_term );
			if ( ! empty( $country_data['currency_term_id'] ) ) {
				$currency_term_id = (int) $country_data['currency_term_id'];
			}
		}

		if ( $currency_term_id < 1 ) {
			$currency_term_id = (int) get_term_meta( $country_term->term_id, self::COUNTRY_CURRENCY_META, true );
		}

		if ( $currency_term_id < 1 ) {
			return null;
		}

		$currency_term = get_term( $currency_term_id, self::CURRENCY_TAXONOMY );

		return $currency_term instanceof WP_Term ? $currency_term : null;
	}

	/**
	 * Return the expected currency term for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return WP_Term|null
	 */
	public static function get_currency_term_for_post( $post_id ) {
		$payload = self::get_currency_payload_for_post( $post_id );

		return ! empty( $payload['currency_term'] ) && $payload['currency_term'] instanceof WP_Term
			? $payload['currency_term']
			: null;
	}

	/**
	 * Calculate taxonomy depth for a location term.
	 *
	 * @param WP_Term $term Term object.
	 * @return int
	 */
	private static function get_term_depth( $term ) {
		return $term instanceof WP_Term
			? count( get_ancestors( (int) $term->term_id, self::LOCATION_TAXONOMY, 'taxonomy' ) )
			: 0;
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
				'location_term'    => null,
				'country_term'     => null,
				'currency_term'    => null,
				'currency_term_id' => 0,
				'currency_name'    => '',
				'currency_slug'    => '',
				'currency_meta'    => '',
			),
			$overrides
		);
	}
}

if ( ! function_exists( 'bornado_get_country_currency_payload_for_post' ) ) {
	/**
	 * Public helper returning the expected currency payload for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	function bornado_get_country_currency_payload_for_post( $post_id ) {
		return class_exists( 'Bornado_Country_Currency_Service' )
			? Bornado_Country_Currency_Service::get_currency_payload_for_post( $post_id )
			: array();
	}
}
