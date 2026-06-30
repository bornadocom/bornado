<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_location_terms_bypass_push' ) ) {
	/**
	 * Increment a runtime bypass counter for public location-term filtering.
	 *
	 * @return void
	 */
	function bornado_location_terms_bypass_push() {
		$GLOBALS['bornado_location_terms_bypass_depth'] = isset( $GLOBALS['bornado_location_terms_bypass_depth'] )
			? (int) $GLOBALS['bornado_location_terms_bypass_depth'] + 1
			: 1;
	}
}

if ( ! function_exists( 'bornado_location_terms_bypass_pop' ) ) {
	/**
	 * Decrement the runtime bypass counter for public location-term filtering.
	 *
	 * @return void
	 */
	function bornado_location_terms_bypass_pop() {
		if ( empty( $GLOBALS['bornado_location_terms_bypass_depth'] ) ) {
			return;
		}

		$GLOBALS['bornado_location_terms_bypass_depth'] = max( 0, (int) $GLOBALS['bornado_location_terms_bypass_depth'] - 1 );
	}
}

if ( ! function_exists( 'bornado_location_terms_bypass_is_active' ) ) {
	/**
	 * Return whether public location-term filtering is temporarily bypassed.
	 *
	 * @return bool
	 */
	function bornado_location_terms_bypass_is_active() {
		return ! empty( $GLOBALS['bornado_location_terms_bypass_depth'] );
	}
}

if ( ! class_exists( 'Bornado_Public_Location_Visibility' ) ) {
	final class Bornado_Public_Location_Visibility {
		const TAXONOMY               = 'ad_country';
		const POST_TYPE              = 'ad_post';
		const STATUS_META_KEY        = '_adforest_ad_status_';
		const CACHE_GROUP            = 'bornado_public_location_visibility';
		const CACHE_VERSION_OPTION   = 'bornado_public_location_visibility_version';

		/**
		 * @var array<string,mixed>|null
		 */
		private static $active_tree = null;

		/**
		 * @var array<int,int>
		 */
		private static $root_term_cache = array();

		/**
		 * @var array<int,bool>
		 */
		private static $tier_one_root_cache = array();

		/**
		 * @return void
		 */
		public static function init() {
			add_filter( 'get_terms', array( __CLASS__, 'filter_public_location_terms' ), 20, 4 );

			add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'bump_cache_version' ), 40, 3 );
			add_action( 'transition_post_status', array( __CLASS__, 'handle_transition_post_status' ), 40, 3 );
			add_action( 'set_object_terms', array( __CLASS__, 'handle_set_object_terms' ), 40, 6 );
			add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'bump_cache_version' ) );
			add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'bump_cache_version' ) );
			add_action( 'delete_' . self::TAXONOMY, array( __CLASS__, 'bump_cache_version' ) );
		}

		/**
		 * Filter public ad_country term lists down to locations that currently have
		 * at least one publish + active listing.
		 *
		 * @param array<int,WP_Term>         $terms      Terms returned by get_terms().
		 * @param array<string>|string       $taxonomies Requested taxonomies.
		 * @param array<string,mixed>        $args       Query args.
		 * @param WP_Term_Query              $term_query Query object.
		 * @return array<int,WP_Term>
		 */
		public static function filter_public_location_terms( $terms, $taxonomies, $args, $term_query ) {
			unset( $term_query );

			if ( empty( $terms ) || ! is_array( $terms ) ) {
				return $terms;
			}

			if ( ! self::should_filter_public_terms( $taxonomies ) ) {
				return $terms;
			}

			$tree = self::get_active_location_tree();
			if ( empty( $tree['root_ids'] ) ) {
				return array();
			}

			$parent = isset( $args['parent'] ) ? (int) $args['parent'] : null;
			$fields = isset( $args['fields'] ) ? (string) $args['fields'] : 'all';
			$filtered = array();

			if ( 'ids' === $fields ) {
				foreach ( $terms as $term_id ) {
					$term = get_term( $term_id, self::TAXONOMY );
					if ( ! $term instanceof WP_Term ) {
						continue;
					}

					if ( self::is_term_allowed_in_public_results( $term, $parent, $tree ) ) {
						$filtered[] = (int) $term->term_id;
					}
				}

				return array_values( array_unique( array_map( 'intval', $filtered ) ) );
			}

			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term || self::TAXONOMY !== $term->taxonomy ) {
					continue;
				}

				if ( self::is_term_allowed_in_public_results( $term, $parent, $tree ) ) {
					$filtered[] = $term;
				}
			}

			return array_values( $filtered );
		}

		/**
		 * Determine whether a location term should remain visible in public term queries.
		 *
		 * @param WP_Term                   $term
		 * @param int|null                  $parent
		 * @param array<string,mixed>       $tree
		 * @return bool
		 */
		private static function is_term_allowed_in_public_results( $term, $parent, array $tree ) {
			if ( ! $term instanceof WP_Term || self::TAXONOMY !== $term->taxonomy ) {
				return false;
			}

			if ( 0 === (int) $term->parent ) {
				return self::is_root_country_allowed( (int) $term->term_id )
					&& in_array( (int) $term->term_id, $tree['root_ids'], true );
			}

			if ( null !== $parent && $parent > 0 ) {
				if ( ! self::is_root_country_allowed( $parent ) ) {
					return false;
				}

				$allowed_children = isset( $tree['children_by_root'][ $parent ] ) ? (array) $tree['children_by_root'][ $parent ] : array();
				return in_array( (int) $term->term_id, $allowed_children, true );
			}

			$root_id = self::get_root_country_id_for_term( $term );
			if ( $root_id < 1 || ! self::is_root_country_allowed( $root_id ) ) {
				return false;
			}

			$allowed_children = isset( $tree['children_by_root'][ $root_id ] ) ? (array) $tree['children_by_root'][ $root_id ] : array();
			return in_array( (int) $term->term_id, $allowed_children, true );
		}

		/**
		 * @param mixed $taxonomies
		 * @return bool
		 */
		private static function should_filter_public_terms( $taxonomies ) {
			$taxonomies = is_array( $taxonomies ) ? $taxonomies : array( $taxonomies );
			if ( ! in_array( self::TAXONOMY, $taxonomies, true ) ) {
				return false;
			}

			if ( is_admin() ) {
				return false;
			}

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return false;
			}

			if ( bornado_location_terms_bypass_is_active() ) {
				return false;
			}

			if ( function_exists( 'bornado_is_ad_post_page' ) && bornado_is_ad_post_page() ) {
				return false;
			}

			// Keep JSON/REST/system requests untouched.
			if ( wp_is_json_request() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				return false;
			}

			return true;
		}

		/**
		 * @return array{root_ids:array<int>,children_by_root:array<int,array<int>>}
		 */
		private static function get_active_location_tree() {
			if ( null !== self::$active_tree ) {
				return self::$active_tree;
			}

			$cache_key = 'tree_' . self::get_cache_version();
			$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
			if ( is_array( $cached ) && isset( $cached['root_ids'], $cached['children_by_root'] ) ) {
				self::$active_tree = $cached;
				return self::$active_tree;
			}

			global $wpdb;

			$sql = $wpdb->prepare(
				"SELECT DISTINCT tt.term_id
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->term_relationships} tr
					ON tr.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} tt
					ON tt.term_taxonomy_id = tr.term_taxonomy_id
				LEFT JOIN {$wpdb->postmeta} pm
					ON pm.post_id = p.ID
					AND pm.meta_key = %s
				WHERE p.post_type = %s
					AND p.post_status = 'publish'
					AND tt.taxonomy = %s
					AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = 'active')",
				self::STATUS_META_KEY,
				self::POST_TYPE,
				self::TAXONOMY
			);

			$term_ids = $wpdb->get_col( $sql );
			$term_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $term_ids ) ) ) );

			$root_ids          = array();
			$children_by_root  = array();

			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id, self::TAXONOMY );
				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				$root_id = self::get_root_country_id_for_term( $term );
				if ( $root_id < 1 || ! self::is_root_country_allowed( $root_id ) ) {
					continue;
				}

				$root_ids[ $root_id ] = $root_id;

				if ( (int) $term->parent > 0 ) {
					if ( ! isset( $children_by_root[ $root_id ] ) ) {
						$children_by_root[ $root_id ] = array();
					}

					$children_by_root[ $root_id ][ $term_id ] = $term_id;
				}
			}

			foreach ( $children_by_root as $root_id => $children ) {
				$children_by_root[ $root_id ] = array_values( array_unique( array_map( 'intval', (array) $children ) ) );
			}

			self::$active_tree = array(
				'root_ids'         => array_values( array_unique( array_map( 'intval', array_values( $root_ids ) ) ) ),
				'children_by_root' => $children_by_root,
			);

			wp_cache_set( $cache_key, self::$active_tree, self::CACHE_GROUP, HOUR_IN_SECONDS );

			return self::$active_tree;
		}

		/**
		 * @param WP_Term|int $term
		 * @return int
		 */
		private static function get_root_country_id_for_term( $term ) {
			$term = get_term( $term, self::TAXONOMY );
			if ( ! $term instanceof WP_Term ) {
				return 0;
			}

			$term_id = (int) $term->term_id;
			if ( isset( self::$root_term_cache[ $term_id ] ) ) {
				return (int) self::$root_term_cache[ $term_id ];
			}

			if ( 0 === (int) $term->parent ) {
				self::$root_term_cache[ $term_id ] = $term_id;
				return $term_id;
			}

			$parent_term = get_term( (int) $term->parent, self::TAXONOMY );
			if ( $parent_term instanceof WP_Term && 0 === (int) $parent_term->parent ) {
				self::$root_term_cache[ $term_id ] = (int) $parent_term->term_id;
				return (int) $parent_term->term_id;
			}

			if ( function_exists( 'bornado_get_country_data' ) ) {
				$data = bornado_get_country_data( $term );
				if ( ! empty( $data['root_country_id'] ) ) {
					self::$root_term_cache[ $term_id ] = (int) $data['root_country_id'];
					return (int) $data['root_country_id'];
				}
			}

			$ancestors = array_reverse( array_map( 'intval', get_ancestors( (int) $term->term_id, self::TAXONOMY, 'taxonomy' ) ) );
			self::$root_term_cache[ $term_id ] = ! empty( $ancestors[0] ) ? (int) $ancestors[0] : 0;
			return (int) self::$root_term_cache[ $term_id ];
		}

		/**
		 * @param int $root_term_id
		 * @return bool
		 */
		private static function is_root_country_allowed( $root_term_id ) {
			$root_term_id = absint( $root_term_id );
			if ( $root_term_id < 1 ) {
				return false;
			}

			if ( isset( self::$tier_one_root_cache[ $root_term_id ] ) ) {
				return (bool) self::$tier_one_root_cache[ $root_term_id ];
			}

			$term = get_term( $root_term_id, self::TAXONOMY );
			self::$tier_one_root_cache[ $root_term_id ] = $term instanceof WP_Term
				? ( function_exists( 'bornado_is_tier_one_country' ) ? (bool) bornado_is_tier_one_country( $term ) : true )
				: false;

			return (bool) self::$tier_one_root_cache[ $root_term_id ];
		}

		/**
		 * @return string
		 */
		private static function get_cache_version() {
			$version = get_option( self::CACHE_VERSION_OPTION, '' );
			if ( '' === $version ) {
				$version = (string) time();
				update_option( self::CACHE_VERSION_OPTION, $version, false );
			}

			return (string) $version;
		}

		/**
		 * @return void
		 */
		public static function bump_cache_version() {
			self::$active_tree = null;
			self::$root_term_cache = array();
			self::$tier_one_root_cache = array();
			update_option( self::CACHE_VERSION_OPTION, (string) time(), false );
		}

		/**
		 * @param int     $post_id
		 * @param WP_Post $post
		 * @param bool    $update
		 * @return void
		 */
		public static function handle_save_post( $post_id, $post, $update ) {
			unset( $update );

			if ( $post instanceof WP_Post && self::POST_TYPE === $post->post_type ) {
				self::bump_cache_version();
			}
		}

		/**
		 * @param string  $new_status
		 * @param string  $old_status
		 * @param WP_Post $post
		 * @return void
		 */
		public static function handle_transition_post_status( $new_status, $old_status, $post ) {
			unset( $new_status, $old_status );

			if ( $post instanceof WP_Post && self::POST_TYPE === $post->post_type ) {
				self::bump_cache_version();
			}
		}

		/**
		 * @param int          $object_id
		 * @param array<mixed> $terms
		 * @param array<mixed> $tt_ids
		 * @param string       $taxonomy
		 * @param bool         $append
		 * @param array<mixed> $old_tt_ids
		 * @return void
		 */
		public static function handle_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
			unset( $terms, $tt_ids, $append, $old_tt_ids );

			if ( self::TAXONOMY !== $taxonomy || self::POST_TYPE !== get_post_type( (int) $object_id ) ) {
				return;
			}

			self::bump_cache_version();
		}
	}
}

Bornado_Public_Location_Visibility::init();
