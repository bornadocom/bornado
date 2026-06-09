<?php
/**
 * Independent listing API for search/list views.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Listing_API' ) ) {
	return;
}

final class Bornado_Listing_API {
	const REST_NAMESPACE = 'bornado/v1';
	const ROUTE_LISTINGS = '/listings';
	const ROUTE_LISTING  = '/listings/(?P<id>\d+)';
	const DEFAULT_PER_PAGE = 12;
	const MAX_PER_PAGE     = 48;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register public routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_LISTINGS,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_listings' ),
				'permission_callback' => '__return_true',
				'args'                => self::get_listing_collection_args(),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_LISTING,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_listing' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'description'       => 'Ad post ID.',
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $value ) {
							return (int) $value > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Collection endpoint definition.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_listing_collection_args() {
		return array(
			'page' => array(
				'description'       => 'Page number.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => static function ( $value ) {
					return (int) $value >= 1;
				},
			),
			'per_page' => array(
				'description'       => 'Results per page.',
				'type'              => 'integer',
				'default'           => self::DEFAULT_PER_PAGE,
				'sanitize_callback' => 'absint',
				'validate_callback' => static function ( $value ) {
					$value = (int) $value;
					return $value >= 1 && $value <= self::MAX_PER_PAGE;
				},
			),
			'search' => array(
				'description'       => 'Free-text keyword search.',
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country_id' => array(
				'description'       => 'Root country term ID.',
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'city_id' => array(
				'description'       => 'City term ID.',
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'cat_id' => array(
				'description'       => 'Deepest ad category term ID.',
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'min_price' => array(
				'description'       => 'Minimum numeric price filter.',
				'type'              => 'number',
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_decimal_param' ),
			),
			'max_price' => array(
				'description'       => 'Maximum numeric price filter.',
				'type'              => 'number',
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_decimal_param' ),
			),
			'sort' => array(
				'description'       => 'Sort mode.',
				'type'              => 'string',
				'default'           => 'newest',
				'sanitize_callback' => array( __CLASS__, 'sanitize_sort_param' ),
				'validate_callback' => static function ( $value ) {
					return isset( self::get_supported_sorts()[ (string) $value ] );
				},
			),
		);
	}

	/**
	 * Sanitize decimal request params without forcing zero.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_decimal_param( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		$value = preg_replace( '/[^0-9.]/', '', (string) $value );

		return '' === $value ? '' : (string) $value;
	}

	/**
	 * Sanitize sort mode.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_sort_param( $value ) {
		$value = sanitize_key( str_replace( '_', '-', (string) $value ) );
		return isset( self::get_supported_sorts()[ $value ] ) ? $value : 'newest';
	}

	/**
	 * GET /listings
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public static function get_listings( WP_REST_Request $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = (int) $request->get_param( 'per_page' );
		$per_page = $per_page > 0 ? min( $per_page, self::MAX_PER_PAGE ) : self::DEFAULT_PER_PAGE;

		$query_args = self::build_query_args( $request, $page, $per_page );
		$query      = new WP_Query( $query_args );

		$items = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				if ( $post instanceof WP_Post ) {
					$items[] = self::serialize_listing( $post );
				}
			}
		}

		$response = new WP_REST_Response(
			array(
				'items'            => $items,
				'pagination'       => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total_items' => (int) $query->found_posts,
					'total_pages' => (int) $query->max_num_pages,
					'has_next'    => $page < (int) $query->max_num_pages,
					'has_prev'    => $page > 1,
				),
				'links'            => self::build_collection_links( $request, $page, (int) $query->max_num_pages, $per_page ),
				'applied_filters'  => self::get_applied_filters( $request ),
				'supported_sorts'  => self::get_supported_sorts(),
				'contract_version' => '2026-06-09',
				'runtime'          => array(
					'provider'          => 'wordpress-adapter',
					'post_type'         => 'ad_post',
					'route_contract'    => 'semantic-path-plus-query',
					'search_engine'     => 'wp-query',
					'independent_layer' => 'plugin',
				),
			)
		);

		$response->header( 'X-WP-Total', (string) (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) (int) $query->max_num_pages );

		return $response;
	}

	/**
	 * GET /listings/{id}
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_listing( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post ) || 'ad_post' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error(
				'bornado_listing_not_found',
				'Listing not found.',
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			array(
				'item'             => self::serialize_listing( $post ),
				'links'            => array(
					'self'      => rest_url( trailingslashit( self::REST_NAMESPACE ) . 'listings/' . $post_id ),
					'canonical' => get_permalink( $post ),
				),
				'contract_version' => '2026-06-09',
				'runtime'          => array(
					'provider'          => 'wordpress-adapter',
					'post_type'         => 'ad_post',
					'independent_layer' => 'plugin',
				),
			)
		);
	}

	/**
	 * Translate API params into a WP_Query payload.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @param int             $page Page number.
	 * @param int             $per_page Results per page.
	 * @return array<string,mixed>
	 */
	private static function build_query_args( WP_REST_Request $request, $page, $per_page ) {
		$args = array(
			'post_type'           => 'ad_post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page'      => $per_page,
			'paged'               => $page,
		);

		$search = trim( (string) $request->get_param( 'search' ) );
		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$tax_query = array();
		$location_term_id = max( absint( $request->get_param( 'city_id' ) ), absint( $request->get_param( 'country_id' ) ) );
		if ( $location_term_id > 0 ) {
			$tax_query[] = array(
				'taxonomy'         => 'ad_country',
				'field'            => 'term_id',
				'terms'            => array( $location_term_id ),
				'include_children' => true,
			);
		}

		$cat_id = absint( $request->get_param( 'cat_id' ) );
		if ( $cat_id > 0 ) {
			$tax_query[] = array(
				'taxonomy'         => 'ad_cats',
				'field'            => 'term_id',
				'terms'            => array( $cat_id ),
				'include_children' => true,
			);
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_query;
		}

		$meta_query = array();
		$min_price  = $request->get_param( 'min_price' );
		$max_price  = $request->get_param( 'max_price' );
		if ( '' !== $min_price && '' !== $max_price ) {
			$meta_query[] = array(
				'key'     => '_adforest_ad_price',
				'value'   => array( (float) $min_price, (float) $max_price ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		} elseif ( '' !== $min_price ) {
			$meta_query[] = array(
				'key'     => '_adforest_ad_price',
				'value'   => (float) $min_price,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		} elseif ( '' !== $max_price ) {
			$meta_query[] = array(
				'key'     => '_adforest_ad_price',
				'value'   => (float) $max_price,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$args = array_merge( $args, self::get_sort_query_args( (string) $request->get_param( 'sort' ) ) );

		return apply_filters( 'bornado_listing_api_query_args', $args, $request );
	}

	/**
	 * Supported sort modes.
	 *
	 * @return array<string,string>
	 */
	private static function get_supported_sorts() {
		return array(
			'newest'    => 'Newest first',
			'oldest'    => 'Oldest first',
			'price-asc' => 'Price low to high',
			'price-desc'=> 'Price high to low',
			'popular'   => 'Most viewed',
		);
	}

	/**
	 * Convert public sort mode into WP_Query args.
	 *
	 * @param string $sort Sort key.
	 * @return array<string,mixed>
	 */
	private static function get_sort_query_args( $sort ) {
		switch ( $sort ) {
			case 'oldest':
				return array(
					'orderby' => 'date',
					'order'   => 'ASC',
				);

			case 'price-asc':
				return array(
					'meta_key' => '_adforest_ad_price',
					'orderby'  => 'meta_value_num',
					'order'    => 'ASC',
				);

			case 'price-desc':
				return array(
					'meta_key' => '_adforest_ad_price',
					'orderby'  => 'meta_value_num',
					'order'    => 'DESC',
				);

			case 'popular':
				return array(
					'meta_key' => 'sb_post_views_count',
					'orderby'  => 'meta_value_num',
					'order'    => 'DESC',
				);

			case 'newest':
			default:
				return array(
					'orderby' => 'date',
					'order'   => 'DESC',
				);
		}
	}

	/**
	 * Build a lightweight representation of one listing card.
	 *
	 * @param WP_Post $post Ad post.
	 * @return array<string,mixed>
	 */
	private static function serialize_listing( WP_Post $post ) {
		$post_id    = (int) $post->ID;
		$ad_details = function_exists( 'get_ad_post_details' ) ? get_ad_post_details( $post_id ) : array();
		$image_urls = array();

		if ( ! empty( $ad_details['all_ad_images'] ) && is_array( $ad_details['all_ad_images'] ) ) {
			$image_urls = array_values(
				array_filter(
					array_map(
						static function ( $url ) {
							return is_string( $url ) ? trim( $url ) : '';
						},
						$ad_details['all_ad_images']
					)
				)
			);
		}

		$primary_image = '';
		if ( ! empty( $ad_details['img'] ) && is_string( $ad_details['img'] ) ) {
			$primary_image = $ad_details['img'];
		} elseif ( ! empty( $image_urls[0] ) ) {
			$primary_image = $image_urls[0];
		}

		$author_id   = (int) $post->post_author;
		$author_name = $author_id > 0 ? get_the_author_meta( 'display_name', $author_id ) : '';
		$categories  = self::serialize_terms( $post_id, 'ad_cats' );
		$locations   = self::serialize_terms( $post_id, 'ad_country' );
		$price_raw   = get_post_meta( $post_id, '_adforest_ad_price', true );
		$view_count  = (int) get_post_meta( $post_id, 'sb_post_views_count', true );
		$is_urgent   = false;
		$ad_type     = get_post_meta( $post_id, '_adforest_ad_type', true );

		if ( is_string( $ad_type ) && false !== stripos( $ad_type, 'urgent' ) ) {
			$is_urgent = true;
		}

		return array(
			'id'             => $post_id,
			'title'          => get_the_title( $post ),
			'excerpt'        => wp_trim_words( wp_strip_all_tags( $post->post_content ), 28, '...' ),
			'permalink'      => get_permalink( $post ),
			'canonical_url'  => get_permalink( $post ),
			'posted_at'      => get_post_time( DATE_ATOM, true, $post ),
			'posted_label'   => function_exists( 'bornado_get_search_card_relative_posted_label' ) ? bornado_get_search_card_relative_posted_label( $post_id ) : '',
			'posted_location'=> function_exists( 'bornado_get_search_card_posted_location_text' ) ? bornado_get_search_card_posted_location_text( $post_id ) : '',
			'featured'       => ! empty( $ad_details['is_featured'] ),
			'urgent'         => $is_urgent,
			'verified'       => function_exists( 'adforest_is_verified_user' ) ? (bool) adforest_is_verified_user( $author_id ) : false,
			'views'          => $view_count,
			'price'          => array(
				'raw'  => is_numeric( $price_raw ) ? (float) $price_raw : null,
				'html' => ! empty( $ad_details['price_html'] ) ? (string) $ad_details['price_html'] : '',
				'type' => (string) get_post_meta( $post_id, '_adforest_ad_price_type', true ),
			),
			'author'         => array(
				'id'       => $author_id,
				'name'     => $author_name,
				'verified' => function_exists( 'adforest_is_verified_user' ) ? (bool) adforest_is_verified_user( $author_id ) : false,
			),
			'media'          => array(
				'primary_image' => $primary_image,
				'gallery'       => $image_urls,
			),
			'location'       => array(
				'label' => ! empty( $ad_details['location'] ) ? (string) $ad_details['location'] : '',
				'terms' => $locations,
			),
			'categories'     => $categories,
			'badges'         => array_values(
				array_filter(
					array(
						! empty( $ad_details['is_featured'] ) ? 'featured' : '',
						$is_urgent ? 'urgent' : '',
						( function_exists( 'adforest_is_verified_user' ) && adforest_is_verified_user( $author_id ) ) ? 'verified' : '',
					)
				)
			),
		);
	}

	/**
	 * Serialize taxonomy terms attached to one ad.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int,array<string,mixed>>
	 */
	private static function serialize_terms( $post_id, $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$items = array();
		foreach ( $terms as $term ) {
			if ( ! ( $term instanceof WP_Term ) ) {
				continue;
			}

			$link = get_term_link( $term );
			$items[] = array(
				'id'   => (int) $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'url'  => is_wp_error( $link ) ? '' : (string) $link,
			);
		}

		return $items;
	}

	/**
	 * Echo back normalized applied filters for the contract/documentation layer.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return array<string,mixed>
	 */
	private static function get_applied_filters( WP_REST_Request $request ) {
		return array(
			'search'    => trim( (string) $request->get_param( 'search' ) ),
			'country_id'=> absint( $request->get_param( 'country_id' ) ),
			'city_id'   => absint( $request->get_param( 'city_id' ) ),
			'cat_id'    => absint( $request->get_param( 'cat_id' ) ),
			'min_price' => self::sanitize_decimal_param( $request->get_param( 'min_price' ) ),
			'max_price' => self::sanitize_decimal_param( $request->get_param( 'max_price' ) ),
			'sort'      => self::sanitize_sort_param( $request->get_param( 'sort' ) ),
		);
	}

	/**
	 * Build pagination/navigation links for API consumers.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @param int             $page Current page.
	 * @param int             $total_pages Total pages.
	 * @param int             $per_page Results per page.
	 * @return array<string,string>
	 */
	private static function build_collection_links( WP_REST_Request $request, $page, $total_pages, $per_page ) {
		$base = rest_url( trailingslashit( self::REST_NAMESPACE ) . 'listings' );
		$args = self::get_applied_filters( $request );
		$args['per_page'] = $per_page;

		$links = array(
			'self' => add_query_arg(
				array_merge(
					$args,
					array(
						'page' => $page,
					)
				),
				$base
			),
		);

		if ( $page > 1 ) {
			$links['prev'] = add_query_arg(
				array_merge(
					$args,
					array(
						'page' => $page - 1,
					)
				),
				$base
			);
		}

		if ( $page < $total_pages ) {
			$links['next'] = add_query_arg(
				array_merge(
					$args,
					array(
						'page' => $page + 1,
					)
				),
				$base
			);
		}

		return $links;
	}
}
