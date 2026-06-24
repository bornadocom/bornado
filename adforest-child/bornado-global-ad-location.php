<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bornado_Global_Ad_Location' ) ) {
	final class Bornado_Global_Ad_Location {
		const SCRIPT_HANDLE          = 'bornado-global-ad-location';
		const STYLE_HANDLE           = 'bornado-global-ad-location';
		const META_PENDING_SELECTION = '_bornado_geo_pending_selection';
		const META_PENDING_HASH      = '_bornado_geo_pending_hash';
		const META_SYNCED_HASH       = '_bornado_geo_synced_hash';
		const META_SYNCED_AT         = '_bornado_geo_synced_at';

		/**
		 * @var array<string,mixed>|null
		 */
		private static $request_selection = null;

		/**
		 * @var array<int,bool>
		 */
		private static $shutdown_candidates = array();

		/**
		 * @var bool
		 */
		private static $shutdown_registered = false;

		/**
		 * @var array<int,bool>
		 */
		private static $processing_posts = array();

		/**
		 * @return void
		 */
		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 145 );
			add_action( 'wp_ajax_sb_ad_posting', array( __CLASS__, 'prefilter_ajax_request' ), -20 );
			add_action( 'adforest_directory_fields_saving', array( __CLASS__, 'persist_pending_selection' ), 20, 2 );
			add_action( 'transition_post_status', array( __CLASS__, 'handle_transition_post_status' ), 35, 3 );
		}

		/**
		 * @return void
		 */
		public static function enqueue_assets() {
			$is_ad_post_page = function_exists( 'bornado_is_ad_post_page' ) && bornado_is_ad_post_page();
			$is_inline_edit  = function_exists( 'bornado_inline_edit_is_active' ) && bornado_inline_edit_is_active();

			if ( is_admin() || ( ! $is_ad_post_page && ! $is_inline_edit ) ) {
				return;
			}

			$script_path = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-global-ad-location.js';
			$style_path  = trailingslashit( get_stylesheet_directory() ) . 'assets/css/bornado-global-ad-location.css';

			if ( file_exists( $style_path ) ) {
				wp_enqueue_style(
					self::STYLE_HANDLE,
					trailingslashit( get_stylesheet_directory_uri() ) . 'assets/css/bornado-global-ad-location.css',
					array(),
					(string) filemtime( $style_path )
				);
			}

			if ( ! file_exists( $script_path ) ) {
				return;
			}

			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-global-ad-location.js',
				array(),
				(string) filemtime( $script_path ),
				true
			);

			wp_localize_script(
				self::SCRIPT_HANDLE,
				'BornadoGlobalAdLocation',
				array(
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( Bornado_Geo_Catalog::AJAX_NONCE_ACTION ),
					'actions'   => array(
						'countrySearch' => Bornado_Geo_Catalog::COUNTRY_SEARCH_AJAX,
						'citySearch'    => Bornado_Geo_Catalog::CITY_SEARCH_AJAX,
					),
					'countries' => Bornado_Geo_Catalog::get_country_search_items( '', 252 ),
					'selection' => self::get_current_selection_payload(),
					'i18n'      => array(
						'title'             => __( 'موقعیت جهانی آگهی', 'adforest-child' ),
						'countryLabel'      => __( 'کشور', 'adforest-child' ),
						'countryPlaceholder'=> __( 'نام کشور را جستجو کنید', 'adforest-child' ),
						'cityLabel'         => __( 'شهر', 'adforest-child' ),
						'cityPlaceholder'   => __( 'نام شهر را جستجو کنید', 'adforest-child' ),
						'cityDisabled'      => __( 'ابتدا کشور را انتخاب کنید', 'adforest-child' ),
						'loading'           => __( 'در حال جستجو...', 'adforest-child' ),
						'noResults'         => __( 'موردی پیدا نشد.', 'adforest-child' ),
						'selectedCountry'   => __( 'کشور انتخاب‌شده', 'adforest-child' ),
						'selectedCity'      => __( 'شهر انتخاب‌شده', 'adforest-child' ),
						'changeCountry'     => __( 'تغییر کشور', 'adforest-child' ),
						'changeCity'        => __( 'تغییر شهر', 'adforest-child' ),
						'optionalCity'      => __( 'اختیاری', 'adforest-child' ),
					),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function prefilter_ajax_request() {
			self::$request_selection = null;

			if ( empty( $_POST['sb_data'] ) ) {
				return;
			}

			$raw_data = wp_unslash( $_POST['sb_data'] );
			if ( ! is_string( $raw_data ) || '' === $raw_data ) {
				return;
			}

			parse_str( $raw_data, $params );
			if ( ! is_array( $params ) ) {
				return;
			}

			$selection = self::normalize_selection_from_source( $params );
			if ( empty( $selection ) ) {
				return;
			}

			self::$request_selection = $selection;

			$params['ad_country']        = (string) (int) $selection['root_term_id'];
			$params['ad_country_states'] = '';
			$params['ad_country_cities'] = '';
			$params['ad_country_towns']  = '';
			$params['ad_country_id']     = (string) (int) $selection['root_term_id'];

			if ( empty( $params['ad_map_lat'] ) && ! empty( $selection['city_latitude'] ) ) {
				$params['ad_map_lat'] = (string) $selection['city_latitude'];
			}

			if ( empty( $params['ad_map_long'] ) && ! empty( $selection['city_longitude'] ) ) {
				$params['ad_map_long'] = (string) $selection['city_longitude'];
			}

			if ( empty( $params['ad_address'] ) ) {
				$params['ad_address'] = self::build_location_label( $selection );
			}

			self::inject_selection_into_params( $params, $selection );

			$_POST['sb_data'] = wp_slash( http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) );
		}

		/**
		 * @param int                 $post_id
		 * @param array<string,mixed> $params
		 * @return void
		 */
		public static function persist_pending_selection( $post_id, $params ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 || ! is_array( $params ) ) {
				return;
			}

			$selection = ! empty( self::$request_selection ) && is_array( self::$request_selection )
				? self::$request_selection
				: self::normalize_selection_from_source( $params );

			if ( empty( $selection ) ) {
				return;
			}

			update_post_meta( $post_id, self::META_PENDING_SELECTION, $selection );
			update_post_meta( $post_id, self::META_PENDING_HASH, self::get_selection_hash( $selection ) );

			if ( 'publish' === get_post_status( $post_id ) ) {
				self::queue_post_for_shutdown_sync( $post_id );
			}
		}

		/**
		 * @param string  $new_status
		 * @param string  $old_status
		 * @param WP_Post $post
		 * @return void
		 */
		public static function handle_transition_post_status( $new_status, $old_status, $post ) {
			if ( ! ( $post instanceof WP_Post ) || 'ad_post' !== $post->post_type ) {
				return;
			}

			if ( 'publish' !== $new_status || 'publish' === $old_status ) {
				return;
			}

			self::queue_post_for_shutdown_sync( (int) $post->ID );
		}

		/**
		 * @return void
		 */
		public static function dispatch_deferred_syncs() {
			if ( empty( self::$shutdown_candidates ) ) {
				return;
			}

			foreach ( array_keys( self::$shutdown_candidates ) as $post_id ) {
				$post_id = absint( $post_id );
				if ( $post_id < 1 || 'publish' !== get_post_status( $post_id ) ) {
					continue;
				}

				self::sync_post_geo_terms( $post_id );
			}

			self::$shutdown_candidates = array();
			self::$shutdown_registered = false;
		}

		/**
		 * @param int $post_id
		 * @return void
		 */
		private static function queue_post_for_shutdown_sync( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return;
			}

			self::$shutdown_candidates[ $post_id ] = true;
			if ( ! self::$shutdown_registered ) {
				self::$shutdown_registered = true;
				add_action( 'shutdown', array( __CLASS__, 'dispatch_deferred_syncs' ) );
			}
		}

		/**
		 * @param int $post_id
		 * @return void
		 */
		private static function sync_post_geo_terms( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 || isset( self::$processing_posts[ $post_id ] ) ) {
				return;
			}

			$selection = get_post_meta( $post_id, self::META_PENDING_SELECTION, true );
			if ( ! is_array( $selection ) || empty( $selection['country_iso2'] ) ) {
				return;
			}

			$selection_hash = self::get_selection_hash( $selection );
			if ( $selection_hash === (string) get_post_meta( $post_id, self::META_SYNCED_HASH, true ) ) {
				return;
			}

			$country = Bornado_Geo_Catalog::get_country_by_iso2( (string) $selection['country_iso2'] );
			if ( empty( $country ) ) {
				return;
			}

			self::$processing_posts[ $post_id ] = true;

			try {
				$country_term_id = Bornado_Geo_Term_Manager::ensure_root_country_term( $country );
				if ( $country_term_id < 1 ) {
					return;
				}

				$term_ids = array( $country_term_id );
				if ( ! empty( $selection['city_geoname_id'] ) ) {
					$city = Bornado_Geo_Catalog::get_city_by_country_and_geoname(
						(string) $selection['country_iso2'],
						(int) $selection['city_geoname_id']
					);

					if ( ! empty( $city ) ) {
						$city_term_id = Bornado_Geo_Term_Manager::ensure_city_term( $country, $city, $country_term_id );
						if ( $city_term_id > 0 ) {
							$term_ids[] = $city_term_id;
						}

						if ( '' === (string) get_post_meta( $post_id, '_adforest_ad_map_lat', true ) && ! empty( $city['latitude'] ) ) {
							update_post_meta( $post_id, '_adforest_ad_map_lat', (string) $city['latitude'] );
						}

						if ( '' === (string) get_post_meta( $post_id, '_adforest_ad_map_long', true ) && ! empty( $city['longitude'] ) ) {
							update_post_meta( $post_id, '_adforest_ad_map_long', (string) $city['longitude'] );
						}
					}
				}

				$term_ids = array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );
				if ( ! empty( $term_ids ) ) {
					wp_set_object_terms( $post_id, $term_ids, 'ad_country', false );
				}

				if ( '' === trim( (string) get_post_meta( $post_id, '_adforest_ad_location', true ) ) ) {
					update_post_meta( $post_id, '_adforest_ad_location', self::build_location_label( $selection ) );
				}

				update_post_meta( $post_id, self::META_SYNCED_HASH, $selection_hash );
				update_post_meta( $post_id, self::META_SYNCED_AT, current_time( 'mysql' ) );

				if ( class_exists( 'Bornado_Location_Picker_Service' ) && method_exists( 'Bornado_Location_Picker_Service', 'flush_cache' ) ) {
					Bornado_Location_Picker_Service::flush_cache();
				}
			} finally {
				unset( self::$processing_posts[ $post_id ] );
			}
		}

		/**
		 * @return array<string,mixed>
		 */
		private static function get_current_selection_payload() {
			$post_id = self::get_current_editing_post_id();
			if ( $post_id < 1 ) {
				return array();
			}

			$selection = get_post_meta( $post_id, self::META_PENDING_SELECTION, true );
			if ( is_array( $selection ) && ! empty( $selection['country_iso2'] ) ) {
				return $selection;
			}

			$terms = wp_get_post_terms( $post_id, 'ad_country' );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return array();
			}

			$root = null;
			$deep = null;
			foreach ( $terms as $term ) {
				if ( ! ( $term instanceof WP_Term ) ) {
					continue;
				}

				if ( 0 === (int) $term->parent && ! $root ) {
					$root = $term;
				}

				if ( ! $deep || count( get_ancestors( (int) $term->term_id, 'ad_country', 'taxonomy' ) ) > count( get_ancestors( (int) $deep->term_id, 'ad_country', 'taxonomy' ) ) ) {
					$deep = $term;
				}
			}

			if ( ! ( $root instanceof WP_Term ) ) {
				return array();
			}

			$country_iso2 = (string) get_term_meta( $root->term_id, Bornado_Geo_Term_Manager::META_COUNTRY_CODE, true );
			$country      = Bornado_Geo_Catalog::get_country_by_iso2( $country_iso2 );

			$payload = array(
				'country_iso2'           => $country_iso2,
				'country_geoname_id'     => ! empty( $country['geoname_id'] ) ? (int) $country['geoname_id'] : 0,
				'country_name_fa'        => ! empty( $country['name_fa'] ) ? (string) $country['name_fa'] : (string) $root->name,
				'country_name_en'        => ! empty( $country['name_en'] ) ? (string) $country['name_en'] : (string) get_term_meta( $root->term_id, Bornado_Geo_Term_Manager::META_DISPLAY_NAME_EN, true ),
				'country_slug_candidate' => ! empty( $country['slug_candidate'] ) ? (string) $country['slug_candidate'] : (string) $root->slug,
				'country_phone_dial_code'=> ! empty( $country['phone_dial_code'] ) ? (string) $country['phone_dial_code'] : (string) get_term_meta( $root->term_id, Bornado_Geo_Term_Manager::META_PHONE_DIAL_CODE, true ),
				'country_currency_code'  => ! empty( $country['currency_code'] ) ? (string) $country['currency_code'] : (string) get_term_meta( $root->term_id, Bornado_Geo_Term_Manager::META_GEO_CURRENCY_CODE, true ),
				'root_term_id'           => (int) $root->term_id,
				'city_geoname_id'        => 0,
				'city_name_fa'           => '',
				'city_name_en'           => '',
				'city_slug_candidate'    => '',
				'city_latitude'          => '',
				'city_longitude'         => '',
			);

			if ( $deep instanceof WP_Term && (int) $deep->term_id !== (int) $root->term_id ) {
				$payload['city_geoname_id'] = absint( get_term_meta( $deep->term_id, Bornado_Geo_Term_Manager::META_GEO_SOURCE_ID, true ) );
				$payload['city_name_fa']    = (string) $deep->name;
				$payload['city_name_en']    = (string) get_term_meta( $deep->term_id, Bornado_Geo_Term_Manager::META_GEO_NAME_EN, true );
				$payload['city_slug_candidate'] = (string) $deep->slug;

				if ( $payload['city_geoname_id'] > 0 ) {
					$city = Bornado_Geo_Catalog::get_city_by_geoname_id( (int) $payload['city_geoname_id'] );
					if ( ! empty( $city ) ) {
						$payload['city_name_fa']        = (string) $city['name_fa'];
						$payload['city_name_en']        = (string) $city['name_en'];
						$payload['city_slug_candidate'] = (string) $city['slug_candidate'];
						$payload['city_latitude']       = (string) $city['latitude'];
						$payload['city_longitude']      = (string) $city['longitude'];
					}
				}
			}

			return $payload;
		}

		/**
		 * @param mixed $source
		 * @return array<string,mixed>
		 */
		private static function normalize_selection_from_source( $source ) {
			if ( ! is_array( $source ) ) {
				return array();
			}

			$country_iso2 = Bornado_Geo_Catalog::normalize_iso2( isset( $source['bornado_geo_country_iso2'] ) ? $source['bornado_geo_country_iso2'] : '' );
			if ( '' === $country_iso2 ) {
				return array();
			}

			$country = Bornado_Geo_Catalog::get_country_by_iso2( $country_iso2 );
			if ( empty( $country ) ) {
				return array();
			}

			$root_term_id = Bornado_Geo_Term_Manager::ensure_root_country_term( $country );
			if ( $root_term_id < 1 ) {
				return array();
			}

			if ( function_exists( 'bornado_is_tier_one_country' ) && ! bornado_is_tier_one_country( $root_term_id ) ) {
				return array();
			}

			$selection = array(
				'country_iso2'            => (string) $country['iso2'],
				'country_geoname_id'      => (int) $country['geoname_id'],
				'country_name_fa'         => (string) $country['name_fa'],
				'country_name_en'         => (string) $country['name_en'],
				'country_slug_candidate'  => (string) $country['slug_candidate'],
				'country_phone_dial_code' => (string) $country['phone_dial_code'],
				'country_currency_code'   => (string) $country['currency_code'],
				'root_term_id'            => $root_term_id,
				'city_geoname_id'         => 0,
				'city_name_fa'            => '',
				'city_name_en'            => '',
				'city_slug_candidate'     => '',
				'city_latitude'           => '',
				'city_longitude'          => '',
			);

			$city_geoname_id = isset( $source['bornado_geo_city_geoname_id'] ) ? absint( $source['bornado_geo_city_geoname_id'] ) : 0;
			if ( $city_geoname_id > 0 ) {
				$city = Bornado_Geo_Catalog::get_city_by_country_and_geoname( $country_iso2, $city_geoname_id );
				if ( ! empty( $city ) ) {
					$selection['city_geoname_id']     = (int) $city['geoname_id'];
					$selection['city_name_fa']        = (string) $city['name_fa'];
					$selection['city_name_en']        = (string) $city['name_en'];
					$selection['city_slug_candidate'] = (string) $city['slug_candidate'];
					$selection['city_latitude']       = (string) $city['latitude'];
					$selection['city_longitude']      = (string) $city['longitude'];
				}
			}

			return $selection;
		}

		/**
		 * @param array<string,mixed> $params
		 * @param array<string,mixed> $selection
		 * @return void
		 */
		private static function inject_selection_into_params( array &$params, array $selection ) {
			$params['bornado_geo_country_iso2']            = (string) $selection['country_iso2'];
			$params['bornado_geo_country_geoname_id']      = (string) (int) $selection['country_geoname_id'];
			$params['bornado_geo_country_name_fa']         = (string) $selection['country_name_fa'];
			$params['bornado_geo_country_name_en']         = (string) $selection['country_name_en'];
			$params['bornado_geo_country_slug_candidate']  = (string) $selection['country_slug_candidate'];
			$params['bornado_geo_country_phone_dial_code'] = (string) $selection['country_phone_dial_code'];
			$params['bornado_geo_country_currency_code']   = (string) $selection['country_currency_code'];
			$params['bornado_geo_root_term_id']            = (string) (int) $selection['root_term_id'];
			$params['bornado_geo_city_geoname_id']         = (string) (int) $selection['city_geoname_id'];
			$params['bornado_geo_city_name_fa']            = (string) $selection['city_name_fa'];
			$params['bornado_geo_city_name_en']            = (string) $selection['city_name_en'];
			$params['bornado_geo_city_slug_candidate']     = (string) $selection['city_slug_candidate'];
			$params['bornado_geo_city_latitude']           = (string) $selection['city_latitude'];
			$params['bornado_geo_city_longitude']          = (string) $selection['city_longitude'];
		}

		/**
		 * @param array<string,mixed> $selection
		 * @return string
		 */
		private static function build_location_label( array $selection ) {
			$country = trim( (string) ( $selection['country_name_fa'] ?? '' ) );
			$city    = trim( (string) ( $selection['city_name_fa'] ?? '' ) );

			if ( '' !== $country && '' !== $city ) {
				return $city . '، ' . $country;
			}

			return '' !== $city ? $city : $country;
		}

		/**
		 * @param array<string,mixed> $selection
		 * @return string
		 */
		private static function get_selection_hash( array $selection ) {
			return md5( wp_json_encode( $selection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}

		/**
		 * @return int
		 */
		private static function get_current_editing_post_id() {
			$is_inline_edit = function_exists( 'bornado_inline_edit_is_active' ) && bornado_inline_edit_is_active();
			if ( $is_inline_edit && function_exists( 'bornado_inline_edit_current_ad_id' ) ) {
				return absint( bornado_inline_edit_current_ad_id() );
			}

			return isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		}
	}
}

Bornado_Global_Ad_Location::init();
