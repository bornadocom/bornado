<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bornado_Geo_Catalog' ) ) {
	final class Bornado_Geo_Catalog {
		const VERSION                    = '1.0.0';
		const SCHEMA_VERSION_OPTION     = 'bornado_geo_catalog_schema_version';
		const COUNTRIES_TABLE_SUFFIX    = 'bornado_geo_countries';
		const CITIES_TABLE_SUFFIX       = 'bornado_geo_cities';
		const COUNTRY_SEARCH_AJAX       = 'bornado_geo_search_countries';
		const CITY_SEARCH_AJAX          = 'bornado_geo_search_cities';
		const AJAX_NONCE_ACTION         = 'bornado_geo_catalog';
		const TOOLS_PAGE_SLUG           = 'bornado-geo-catalog';

		/**
		 * @var string|null
		 */
		private static $countries_table = null;

		/**
		 * @var string|null
		 */
		private static $cities_table = null;

		/**
		 * @return void
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'maybe_install_schema' ), 5 );
			add_action( 'wp_ajax_' . self::COUNTRY_SEARCH_AJAX, array( __CLASS__, 'handle_country_search_ajax' ) );
			add_action( 'wp_ajax_' . self::CITY_SEARCH_AJAX, array( __CLASS__, 'handle_city_search_ajax' ) );

			if ( is_admin() ) {
				add_action( 'admin_menu', array( __CLASS__, 'register_tools_page' ) );
				add_action( 'admin_post_bornado_geo_seed_root_countries', array( __CLASS__, 'handle_seed_root_countries_request' ) );
			}
		}

		/**
		 * @return string
		 */
		public static function get_countries_table() {
			if ( null === self::$countries_table ) {
				global $wpdb;
				self::$countries_table = $wpdb->prefix . self::COUNTRIES_TABLE_SUFFIX;
			}

			return self::$countries_table;
		}

		/**
		 * @return string
		 */
		public static function get_cities_table() {
			if ( null === self::$cities_table ) {
				global $wpdb;
				self::$cities_table = $wpdb->prefix . self::CITIES_TABLE_SUFFIX;
			}

			return self::$cities_table;
		}

		/**
		 * @return void
		 */
		public static function maybe_install_schema() {
			$current_version = (string) get_option( self::SCHEMA_VERSION_OPTION, '' );
			if ( self::VERSION === $current_version ) {
				return;
			}

			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$charset_collate = $wpdb->get_charset_collate();
			$countries_table = self::get_countries_table();
			$cities_table    = self::get_cities_table();

			$sql = array();
			$sql[] = "CREATE TABLE {$countries_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				iso2 char(2) NOT NULL,
				geoname_id bigint(20) unsigned NOT NULL DEFAULT 0,
				name_fa varchar(190) NOT NULL DEFAULT '',
				name_en varchar(190) NOT NULL DEFAULT '',
				slug_candidate varchar(120) NOT NULL DEFAULT '',
				phone_dial_code varchar(16) NOT NULL DEFAULT '',
				currency_code varchar(8) NOT NULL DEFAULT '',
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY iso2 (iso2),
				UNIQUE KEY geoname_id (geoname_id),
				KEY name_fa (name_fa),
				KEY name_en (name_en)
			) {$charset_collate};";

			$sql[] = "CREATE TABLE {$cities_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				geoname_id bigint(20) unsigned NOT NULL DEFAULT 0,
				country_iso2 char(2) NOT NULL,
				name_fa varchar(190) NOT NULL DEFAULT '',
				name_en varchar(190) NOT NULL DEFAULT '',
				asciiname varchar(190) NOT NULL DEFAULT '',
				slug_candidate varchar(160) NOT NULL DEFAULT '',
				latitude decimal(10,7) NOT NULL DEFAULT 0,
				longitude decimal(10,7) NOT NULL DEFAULT 0,
				population bigint(20) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY geoname_id (geoname_id),
				KEY country_iso2 (country_iso2),
				KEY name_fa (name_fa),
				KEY name_en (name_en),
				KEY population (population)
			) {$charset_collate};";

			foreach ( $sql as $statement ) {
				dbDelta( $statement );
			}

			update_option( self::SCHEMA_VERSION_OPTION, self::VERSION, false );
		}

		/**
		 * @return void
		 */
		public static function register_tools_page() {
			add_management_page(
				'Bornado Geo Catalog',
				'Bornado Geo Catalog',
				'manage_options',
				self::TOOLS_PAGE_SLUG,
				array( __CLASS__, 'render_tools_page' )
			);
		}

		/**
		 * @return void
		 */
		public static function render_tools_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'adforest-child' ) );
			}

			$country_count = self::get_country_count();
			$city_count    = self::get_city_count();
			$root_count    = Bornado_Geo_Term_Manager::get_root_country_term_count();
			$nonce         = wp_create_nonce( 'bornado_geo_seed_root_countries' );
			?>
			<div class="wrap">
				<h1>Bornado Geo Catalog</h1>
				<p>GeoNames snapshots live in custom catalog tables. Core AdForest files stay untouched; countries can be seeded into <code>ad_country</code> from this child-theme layer.</p>
				<table class="widefat striped" style="max-width: 960px; margin-bottom: 24px;">
					<tbody>
						<tr>
							<td><strong>Catalog countries</strong></td>
							<td><?php echo esc_html( number_format_i18n( $country_count ) ); ?></td>
						</tr>
						<tr>
							<td><strong>Catalog cities</strong></td>
							<td><?php echo esc_html( number_format_i18n( $city_count ) ); ?></td>
						</tr>
						<tr>
							<td><strong>Root <code>ad_country</code> terms</strong></td>
							<td><?php echo esc_html( number_format_i18n( $root_count ) ); ?></td>
						</tr>
					</tbody>
				</table>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom: 24px;">
					<input type="hidden" name="action" value="bornado_geo_seed_root_countries" />
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
					<?php submit_button( 'Seed Root Countries Into ad_country', 'primary', 'submit', false ); ?>
				</form>

				<h2>WP-CLI</h2>
				<p>For large GeoNames files use WP-CLI so imports run outside web-request time limits.</p>
				<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:960px;overflow:auto;">wp bornado-geo import-countries "C:\path\countryInfo.txt"
wp bornado-geo import-cities "C:\path\cities1000.zip"
wp bornado-geo import-fa-names "C:\path\alternateNamesV2.zip"
wp bornado-geo seed-root-countries</pre>
			</div>
			<?php
		}

		/**
		 * @return void
		 */
		public static function handle_seed_root_countries_request() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'adforest-child' ) );
			}

			check_admin_referer( 'bornado_geo_seed_root_countries' );

			Bornado_Geo_Term_Manager::seed_all_root_countries();

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'   => self::TOOLS_PAGE_SLUG,
						'seeded' => '1',
					),
					admin_url( 'tools.php' )
				)
			);
			exit;
		}

		/**
		 * @param string $iso2
		 * @return array<string,mixed>|null
		 */
		public static function get_country_by_iso2( $iso2 ) {
			global $wpdb;

			$iso2 = self::normalize_iso2( $iso2 );
			if ( '' === $iso2 ) {
				return null;
			}

			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . self::get_countries_table() . ' WHERE iso2 = %s LIMIT 1',
					$iso2
				),
				ARRAY_A
			);

			return is_array( $row ) ? self::normalize_country_row( $row ) : null;
		}

		/**
		 * @param int $geoname_id
		 * @return array<string,mixed>|null
		 */
		public static function get_city_by_geoname_id( $geoname_id ) {
			global $wpdb;

			$geoname_id = absint( $geoname_id );
			if ( $geoname_id < 1 ) {
				return null;
			}

			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . self::get_cities_table() . ' WHERE geoname_id = %d LIMIT 1',
					$geoname_id
				),
				ARRAY_A
			);

			return is_array( $row ) ? self::normalize_city_row( $row ) : null;
		}

		/**
		 * @param string $country_iso2
		 * @param int    $geoname_id
		 * @return array<string,mixed>|null
		 */
		public static function get_city_by_country_and_geoname( $country_iso2, $geoname_id ) {
			$country_iso2 = self::normalize_iso2( $country_iso2 );
			$city         = self::get_city_by_geoname_id( $geoname_id );

			if ( empty( $city ) || $country_iso2 !== (string) $city['country_iso2'] ) {
				return null;
			}

			return $city;
		}

		/**
		 * @param string $query
		 * @param int    $limit
		 * @return array<int,array<string,mixed>>
		 */
		public static function search_countries( $query = '', $limit = 20 ) {
			global $wpdb;

			$limit = max( 1, min( 500, (int) $limit ) );
			$query = trim( (string) $query );
			$table = self::get_countries_table();

			if ( '' === $query ) {
				$sql  = $wpdb->prepare(
					'SELECT * FROM ' . $table . ' ORDER BY name_en ASC LIMIT %d',
					$limit
				);
				$rows = $wpdb->get_results( $sql, ARRAY_A );
			} else {
				$like = '%' . $wpdb->esc_like( $query ) . '%';
				$sql  = $wpdb->prepare(
					'SELECT * FROM ' . $table . ' WHERE iso2 = %s OR name_fa LIKE %s OR name_en LIKE %s ORDER BY CASE WHEN iso2 = %s THEN 0 ELSE 1 END, name_en ASC LIMIT %d',
					self::normalize_iso2( $query ),
					$like,
					$like,
					self::normalize_iso2( $query ),
					$limit
				);
				$rows = $wpdb->get_results( $sql, ARRAY_A );
			}

			$results = array();
			foreach ( (array) $rows as $row ) {
				if ( is_array( $row ) ) {
					$results[] = self::normalize_country_row( $row );
				}
			}

			return $results;
		}

		/**
		 * @param string $country_iso2
		 * @param string $query
		 * @param int    $limit
		 * @return array<int,array<string,mixed>>
		 */
		public static function search_cities( $country_iso2, $query = '', $limit = 25 ) {
			global $wpdb;

			$country_iso2 = self::normalize_iso2( $country_iso2 );
			if ( '' === $country_iso2 ) {
				return array();
			}

			$limit = max( 1, min( 50, (int) $limit ) );
			$query = trim( (string) $query );
			$table = self::get_cities_table();

			if ( '' === $query ) {
				$sql  = $wpdb->prepare(
					'SELECT * FROM ' . $table . ' WHERE country_iso2 = %s ORDER BY population DESC, name_fa ASC, name_en ASC LIMIT %d',
					$country_iso2,
					$limit
				);
				$rows = $wpdb->get_results( $sql, ARRAY_A );
			} else {
				$like = '%' . $wpdb->esc_like( $query ) . '%';
				$sql  = $wpdb->prepare(
					'SELECT * FROM ' . $table . ' WHERE country_iso2 = %s AND (name_fa LIKE %s OR name_en LIKE %s OR asciiname LIKE %s) ORDER BY population DESC, name_fa ASC, name_en ASC LIMIT %d',
					$country_iso2,
					$like,
					$like,
					$like,
					$limit
				);
				$rows = $wpdb->get_results( $sql, ARRAY_A );
			}

			$results = array();
			foreach ( (array) $rows as $row ) {
				if ( is_array( $row ) ) {
					$results[] = self::normalize_city_row( $row );
				}
			}

			return $results;
		}

		/**
		 * @return void
		 */
		public static function handle_country_search_ajax() {
			check_ajax_referer( self::AJAX_NONCE_ACTION, 'nonce' );

			$query = isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '';
			$limit = '' === trim( (string) $query ) ? 252 : 50;
			$items = self::get_country_search_items( (string) $query, $limit );

			wp_send_json_success(
				array(
					'items' => $items,
				)
			);
		}

		/**
		 * @return void
		 */
		public static function handle_city_search_ajax() {
			check_ajax_referer( self::AJAX_NONCE_ACTION, 'nonce' );

			$country_iso2 = isset( $_POST['country_iso2'] ) ? wp_unslash( $_POST['country_iso2'] ) : '';
			$query        = isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '';
			$items        = array();

			foreach ( self::search_cities( (string) $country_iso2, (string) $query ) as $city ) {
				$items[] = array(
					'geonameId'     => (int) $city['geoname_id'],
					'countryIso2'   => (string) $city['country_iso2'],
					'nameFa'        => (string) $city['name_fa'],
					'nameEn'        => (string) $city['name_en'],
					'asciiname'     => (string) $city['asciiname'],
					'slugCandidate' => (string) $city['slug_candidate'],
					'latitude'      => (string) $city['latitude'],
					'longitude'     => (string) $city['longitude'],
					'population'    => (int) $city['population'],
				);
			}

			wp_send_json_success(
				array(
					'items' => $items,
				)
			);
		}

		/**
		 * @param string $query
		 * @param int    $limit
		 * @return array<int,array<string,mixed>>
		 */
		public static function get_country_search_items( $query = '', $limit = 252 ) {
			static $empty_query_cache = null;

			$query = trim( (string) $query );
			$limit = max( 1, min( 252, (int) $limit ) );

			if ( '' === $query && is_array( $empty_query_cache ) ) {
				return array_slice( $empty_query_cache, 0, $limit );
			}

			$items = array();
			foreach ( self::search_countries( $query, '' === $query ? 252 : $limit ) as $country ) {
				$legacy_term_id = (int) Bornado_Geo_Term_Manager::get_root_country_term_id_by_iso2( (string) $country['iso2'] );
				if ( $legacy_term_id < 1 ) {
					continue;
				}

				if ( function_exists( 'bornado_is_tier_one_country' ) && ! bornado_is_tier_one_country( $legacy_term_id ) ) {
					continue;
				}

				$items[] = array(
					'iso2'          => (string) $country['iso2'],
					'nameFa'        => (string) $country['name_fa'],
					'nameEn'        => (string) $country['name_en'],
					'slugCandidate' => (string) $country['slug_candidate'],
					'phoneDialCode' => (string) $country['phone_dial_code'],
					'currencyCode'  => (string) $country['currency_code'],
					'geonameId'     => (int) $country['geoname_id'],
					'legacyTermId'  => $legacy_term_id,
				);
			}

			if ( '' === $query ) {
				$empty_query_cache = array_values( $items );
			}

			return array_slice( $items, 0, $limit );
		}

		/**
		 * @return int
		 */
		public static function get_country_count() {
			global $wpdb;
			return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_countries_table() );
		}

		/**
		 * @return int
		 */
		public static function get_city_count() {
			global $wpdb;
			return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_cities_table() );
		}

		/**
		 * @param array<string,mixed> $row
		 * @return array<string,mixed>
		 */
		private static function normalize_country_row( array $row ) {
			$normalized = array(
				'id'             => isset( $row['id'] ) ? (int) $row['id'] : 0,
				'iso2'           => self::normalize_iso2( isset( $row['iso2'] ) ? $row['iso2'] : '' ),
				'geoname_id'     => isset( $row['geoname_id'] ) ? (int) $row['geoname_id'] : 0,
				'name_fa'        => sanitize_text_field( isset( $row['name_fa'] ) ? (string) $row['name_fa'] : '' ),
				'name_en'        => sanitize_text_field( isset( $row['name_en'] ) ? (string) $row['name_en'] : '' ),
				'slug_candidate' => sanitize_title( isset( $row['slug_candidate'] ) ? (string) $row['slug_candidate'] : '' ),
				'phone_dial_code'=> sanitize_text_field( isset( $row['phone_dial_code'] ) ? (string) $row['phone_dial_code'] : '' ),
				'currency_code'  => strtoupper( sanitize_text_field( isset( $row['currency_code'] ) ? (string) $row['currency_code'] : '' ) ),
			);

			$name_overrides = self::get_known_country_name_overrides();
			$iso2           = (string) $normalized['iso2'];
			if ( isset( $name_overrides[ $iso2 ] ) ) {
				$normalized['name_fa'] = (string) $name_overrides[ $iso2 ];
			}

			$phone_overrides = self::get_known_country_phone_dial_overrides();
			if ( '' === (string) $normalized['phone_dial_code'] && isset( $phone_overrides[ $iso2 ] ) ) {
				$normalized['phone_dial_code'] = (string) $phone_overrides[ $iso2 ];
			}

			return $normalized;
		}

		/**
		 * @return array<string,string>
		 */
		private static function get_known_country_name_overrides() {
			return array(
				'AN' => 'آنتیل هلند',
				'CS' => 'صربستان و مونته نگرو',
				'DO' => 'جمهوری دومینیکن',
				'GB' => 'بریتانیا',
			);
		}

		/**
		 * Backfill a few known dialing-code gaps or multi-code territories that do
		 * not normalize cleanly from GeoNames `countryInfo.txt`.
		 *
		 * @return array<string,string>
		 */
		private static function get_known_country_phone_dial_overrides() {
			return array(
				'AX' => '+35818',
				'DO' => '+1809',
				'GG' => '+44',
				'GS' => '+500',
				'IM' => '+44',
				'JE' => '+44',
				'PR' => '+1787',
				'XK' => '+383',
			);
		}

		/**
		 * @param array<string,mixed> $row
		 * @return array<string,mixed>
		 */
		private static function normalize_city_row( array $row ) {
			return array(
				'id'             => isset( $row['id'] ) ? (int) $row['id'] : 0,
				'geoname_id'     => isset( $row['geoname_id'] ) ? (int) $row['geoname_id'] : 0,
				'country_iso2'   => self::normalize_iso2( isset( $row['country_iso2'] ) ? $row['country_iso2'] : '' ),
				'name_fa'        => sanitize_text_field( isset( $row['name_fa'] ) ? (string) $row['name_fa'] : '' ),
				'name_en'        => sanitize_text_field( isset( $row['name_en'] ) ? (string) $row['name_en'] : '' ),
				'asciiname'      => sanitize_text_field( isset( $row['asciiname'] ) ? (string) $row['asciiname'] : '' ),
				'slug_candidate' => sanitize_title( isset( $row['slug_candidate'] ) ? (string) $row['slug_candidate'] : '' ),
				'latitude'       => isset( $row['latitude'] ) ? (string) $row['latitude'] : '',
				'longitude'      => isset( $row['longitude'] ) ? (string) $row['longitude'] : '',
				'population'     => isset( $row['population'] ) ? (int) $row['population'] : 0,
			);
		}

		/**
		 * @param string $value
		 * @return string
		 */
		public static function normalize_iso2( $value ) {
			$value = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $value ) );
			return strlen( $value ) <= 2 ? $value : substr( $value, 0, 2 );
		}
	}
}

if ( ! class_exists( 'Bornado_Geo_Term_Manager' ) ) {
	final class Bornado_Geo_Term_Manager {
		const LOCATION_TAXONOMY            = 'ad_country';
		const CURRENCY_TAXONOMY            = 'ad_currency';
		const META_COUNTRY_CODE            = '_bornado_country_code';
		const META_PHONE_DIAL_CODE         = '_bornado_country_phone_dial_code';
		const META_DISPLAY_NAME_EN         = '_bornado_country_display_name_en';
		const META_DISPLAY_NAME_FA_OVERRIDE = '_bornado_country_display_name_fa_override';
		const META_CURRENCY_TERM_ID        = '_bornado_country_currency_term_id';
		const META_GEO_SOURCE              = '_bornado_geo_source';
		const META_GEO_SOURCE_ID           = '_bornado_geo_source_id';
		const META_GEO_NAME_EN             = '_bornado_geo_name_en';
		const META_GEO_COUNTRY_ISO2        = '_bornado_geo_country_iso2';
		const META_GEO_CURRENCY_CODE       = '_bornado_geo_currency_code';
		const GEO_SOURCE                   = 'geonames';

		/**
		 * @var array<string,string>|null
		 */
		private static $country_slug_overrides = null;

		/**
		 * @var array<string,int>|null
		 */
		private static $currency_cache = null;

		/**
		 * @var array<string,int>|null
		 */
		private static $root_country_term_id_by_iso2 = null;

		/**
		 * @param string $iso2
		 * @return int
		 */
		public static function ensure_root_country_term_by_iso2( $iso2 ) {
			$country = Bornado_Geo_Catalog::get_country_by_iso2( $iso2 );
			if ( empty( $country ) ) {
				return 0;
			}

			return self::ensure_root_country_term( $country );
		}

		/**
		 * @param array<string,mixed> $country
		 * @return int
		 */
		public static function ensure_root_country_term( array $country ) {
			$iso2 = Bornado_Geo_Catalog::normalize_iso2( isset( $country['iso2'] ) ? $country['iso2'] : '' );
			if ( '' === $iso2 ) {
				return 0;
			}

			$existing = self::find_root_country_term( $country );
			if ( $existing instanceof WP_Term ) {
				self::sync_root_country_term_meta( $existing, $country );
				return (int) $existing->term_id;
			}

			$name = ! empty( $country['name_fa'] ) ? (string) $country['name_fa'] : (string) $country['name_en'];
			$slug = self::build_root_country_slug( $country );

			$created = wp_insert_term(
				$name,
				self::LOCATION_TAXONOMY,
				array(
					'parent' => 0,
					'slug'   => $slug,
				)
			);

			if ( is_wp_error( $created ) ) {
				$term_id = absint( $created->get_error_data( 'term_exists' ) );
				if ( $term_id > 0 ) {
					$term = get_term( $term_id, self::LOCATION_TAXONOMY );
					if ( $term instanceof WP_Term ) {
						self::sync_root_country_term_meta( $term, $country );
						return (int) $term->term_id;
					}
				}

				return 0;
			}

			$term_id = isset( $created['term_id'] ) ? absint( $created['term_id'] ) : 0;
			if ( $term_id < 1 ) {
				return 0;
			}

			$term = get_term( $term_id, self::LOCATION_TAXONOMY );
			if ( $term instanceof WP_Term ) {
				self::sync_root_country_term_meta( $term, $country );
			}

			return $term_id;
		}

		/**
		 * @param array<string,mixed> $country
		 * @param array<string,mixed> $city
		 * @param int                 $country_term_id
		 * @return int
		 */
		public static function ensure_city_term( array $country, array $city, $country_term_id ) {
			$country_term_id = absint( $country_term_id );
			$geoname_id      = isset( $city['geoname_id'] ) ? absint( $city['geoname_id'] ) : 0;

			if ( $country_term_id < 1 || $geoname_id < 1 ) {
				return 0;
			}

			$existing = self::find_city_term_by_source_id( $geoname_id );
			if ( $existing instanceof WP_Term ) {
				self::sync_city_term_meta( $existing, $country, $city );
				if ( (int) $existing->parent !== $country_term_id ) {
					wp_update_term(
						(int) $existing->term_id,
						self::LOCATION_TAXONOMY,
						array(
							'parent' => $country_term_id,
						)
					);
				}

				return (int) $existing->term_id;
			}

			$name = ! empty( $city['name_fa'] ) ? (string) $city['name_fa'] : (string) $city['name_en'];
			$slug = self::build_city_slug( $city );

			$created = wp_insert_term(
				$name,
				self::LOCATION_TAXONOMY,
				array(
					'parent' => $country_term_id,
					'slug'   => $slug,
				)
			);

			if ( is_wp_error( $created ) ) {
				$retry_slug = $slug . '-' . $geoname_id;
				$created    = wp_insert_term(
					$name,
					self::LOCATION_TAXONOMY,
					array(
						'parent' => $country_term_id,
						'slug'   => $retry_slug,
					)
				);
			}

			if ( is_wp_error( $created ) ) {
				$term_id = absint( $created->get_error_data( 'term_exists' ) );
				if ( $term_id > 0 ) {
					$term = get_term( $term_id, self::LOCATION_TAXONOMY );
					if ( $term instanceof WP_Term ) {
						self::sync_city_term_meta( $term, $country, $city );
						return (int) $term->term_id;
					}
				}

				return 0;
			}

			$term_id = isset( $created['term_id'] ) ? absint( $created['term_id'] ) : 0;
			if ( $term_id < 1 ) {
				return 0;
			}

			$term = get_term( $term_id, self::LOCATION_TAXONOMY );
			if ( $term instanceof WP_Term ) {
				self::sync_city_term_meta( $term, $country, $city );
			}

			return $term_id;
		}

		/**
		 * @return int
		 */
		public static function seed_all_root_countries() {
			$count = 0;
			foreach ( Bornado_Geo_Catalog::search_countries( '', 500 ) as $country ) {
				$term_id = self::ensure_root_country_term( $country );
				if ( $term_id > 0 ) {
					$count++;
				}
			}

			if ( class_exists( 'Bornado_Location_Picker_Service' ) && method_exists( 'Bornado_Location_Picker_Service', 'flush_cache' ) ) {
				Bornado_Location_Picker_Service::flush_cache();
			}

			return $count;
		}

		/**
		 * @return int
		 */
		public static function get_root_country_term_count() {
			$terms = get_terms(
				array(
					'taxonomy'   => self::LOCATION_TAXONOMY,
					'hide_empty' => false,
					'parent'     => 0,
					'fields'     => 'ids',
					'number'     => 0,
				)
			);

			return is_wp_error( $terms ) ? 0 : count( (array) $terms );
		}

		/**
		 * @param string $iso2
		 * @return int
		 */
		public static function get_root_country_term_id_by_iso2( $iso2 ) {
			$iso2 = Bornado_Geo_Catalog::normalize_iso2( $iso2 );
			if ( '' === $iso2 ) {
				return 0;
			}

			self::prime_root_country_term_lookup();

			return isset( self::$root_country_term_id_by_iso2[ $iso2 ] )
				? (int) self::$root_country_term_id_by_iso2[ $iso2 ]
				: 0;
		}

		/**
		 * @return void
		 */
		private static function prime_root_country_term_lookup() {
			if ( null !== self::$root_country_term_id_by_iso2 ) {
				return;
			}

			self::$root_country_term_id_by_iso2 = array();
			$used_bypass = false;
			if ( function_exists( 'bornado_location_terms_bypass_push' ) ) {
				bornado_location_terms_bypass_push();
				$used_bypass = true;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => self::LOCATION_TAXONOMY,
					'hide_empty' => false,
					'parent'     => 0,
					'number'     => 0,
				)
			);

			if ( $used_bypass && function_exists( 'bornado_location_terms_bypass_pop' ) ) {
				bornado_location_terms_bypass_pop();
			}

			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				return;
			}

			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				$keys = array_unique(
					array_filter(
						array(
							Bornado_Geo_Catalog::normalize_iso2( get_term_meta( $term->term_id, self::META_COUNTRY_CODE, true ) ),
							Bornado_Geo_Catalog::normalize_iso2( get_term_meta( $term->term_id, self::META_GEO_COUNTRY_ISO2, true ) ),
						)
					)
				);

				foreach ( $keys as $key ) {
					if ( '' !== $key && ! isset( self::$root_country_term_id_by_iso2[ $key ] ) ) {
						self::$root_country_term_id_by_iso2[ $key ] = (int) $term->term_id;
					}
				}
			}
		}

		/**
		 * @return array<string,int>
		 */
		public static function repair_root_country_duplicates() {
			$canonical_count = 0;
			$merged_count    = 0;
			$deleted_count   = 0;

			foreach ( Bornado_Geo_Catalog::search_countries( '', 500 ) as $country ) {
				$canonical = self::find_root_country_term( $country );
				if ( ! ( $canonical instanceof WP_Term ) ) {
					continue;
				}

				$canonical_count++;
				self::sync_root_country_term_meta( $canonical, $country );

				$candidates = self::collect_root_country_term_candidates( $country );
				if ( count( $candidates ) < 2 ) {
					continue;
				}

				foreach ( $candidates as $term ) {
					if ( (int) $term->term_id === (int) $canonical->term_id ) {
						continue;
					}

					self::migrate_root_country_references( (int) $term->term_id, (int) $canonical->term_id );

					$deleted = wp_delete_term( (int) $term->term_id, self::LOCATION_TAXONOMY );
					if ( ! is_wp_error( $deleted ) && $deleted ) {
						$merged_count++;
						$deleted_count++;
					}
				}
			}

			if ( class_exists( 'Bornado_Location_Picker_Service' ) && method_exists( 'Bornado_Location_Picker_Service', 'flush_cache' ) ) {
				Bornado_Location_Picker_Service::flush_cache();
			}

			return array(
				'canonical' => $canonical_count,
				'merged'    => $merged_count,
				'deleted'   => $deleted_count,
			);
		}

		/**
		 * @param int $from_term_id
		 * @param int $to_term_id
		 * @return void
		 */
		private static function migrate_root_country_references( $from_term_id, $to_term_id ) {
			$from_term_id = absint( $from_term_id );
			$to_term_id   = absint( $to_term_id );

			if ( $from_term_id < 1 || $to_term_id < 1 || $from_term_id === $to_term_id ) {
				return;
			}

			$used_bypass = false;
			if ( function_exists( 'bornado_location_terms_bypass_push' ) ) {
				bornado_location_terms_bypass_push();
				$used_bypass = true;
			}

			$children = get_terms(
				array(
					'taxonomy'   => self::LOCATION_TAXONOMY,
					'hide_empty' => false,
					'parent'     => $from_term_id,
					'number'     => 0,
				)
			);

			if ( ! is_wp_error( $children ) ) {
				foreach ( (array) $children as $child ) {
					if ( $child instanceof WP_Term ) {
						wp_update_term(
							(int) $child->term_id,
							self::LOCATION_TAXONOMY,
							array(
								'parent' => $to_term_id,
							)
						);
					}
				}
			}

			if ( $used_bypass && function_exists( 'bornado_location_terms_bypass_pop' ) ) {
				bornado_location_terms_bypass_pop();
			}

			$object_ids = get_objects_in_term( $from_term_id, self::LOCATION_TAXONOMY );
			if ( is_wp_error( $object_ids ) || empty( $object_ids ) ) {
				return;
			}

			foreach ( array_unique( array_map( 'absint', (array) $object_ids ) ) as $object_id ) {
				if ( $object_id < 1 ) {
					continue;
				}

				$term_ids = wp_get_object_terms(
					$object_id,
					self::LOCATION_TAXONOMY,
					array(
						'fields' => 'ids',
					)
				);

				if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
					continue;
				}

				$new_term_ids = array_unique( array_map( 'absint', (array) $term_ids ) );
				$index        = array_search( $from_term_id, $new_term_ids, true );
				if ( false === $index ) {
					continue;
				}

				$new_term_ids[ $index ] = $to_term_id;
				$new_term_ids           = array_values( array_unique( array_filter( $new_term_ids ) ) );

				wp_set_object_terms( $object_id, $new_term_ids, self::LOCATION_TAXONOMY, false );
			}
		}

		/**
		 * @param string $iso2
		 * @return WP_Term|null
		 */
		public static function find_root_country_term_by_iso2( $iso2 ) {
			$iso2 = Bornado_Geo_Catalog::normalize_iso2( $iso2 );
			if ( '' === $iso2 ) {
				return null;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => self::LOCATION_TAXONOMY,
					'hide_empty' => false,
					'parent'     => 0,
					'number'     => 1,
					'meta_query' => array(
						array(
							'key'   => self::META_COUNTRY_CODE,
							'value' => $iso2,
						),
					),
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms[0] ) || ! ( $terms[0] instanceof WP_Term ) ) {
				return null;
			}

			return $terms[0];
		}

		/**
		 * @param array<string,mixed> $country
		 * @return WP_Term|null
		 */
		private static function find_root_country_term( array $country ) {
			$candidates = self::collect_root_country_term_candidates( $country );
			if ( empty( $candidates ) ) {
				return null;
			}

			$iso2           = Bornado_Geo_Catalog::normalize_iso2( isset( $country['iso2'] ) ? $country['iso2'] : '' );
			$preferred_slug = self::build_root_country_slug( $country );
			$legacy_slug    = sanitize_title( strtolower( $iso2 ) );
			$name_fa        = sanitize_text_field( isset( $country['name_fa'] ) ? (string) $country['name_fa'] : '' );
			$name_en        = sanitize_text_field( isset( $country['name_en'] ) ? (string) $country['name_en'] : '' );

			foreach ( $candidates as $term ) {
				if ( $iso2 !== '' && strtoupper( (string) get_term_meta( $term->term_id, self::META_COUNTRY_CODE, true ) ) === $iso2 ) {
					return $term;
				}
			}

			foreach ( $candidates as $term ) {
				if ( $iso2 !== '' && strtoupper( (string) get_term_meta( $term->term_id, self::META_GEO_COUNTRY_ISO2, true ) ) === $iso2 ) {
					return $term;
				}
			}

			foreach ( $candidates as $term ) {
				if ( $preferred_slug !== '' && (string) $term->slug === $preferred_slug ) {
					return $term;
				}
			}

			foreach ( $candidates as $term ) {
				if ( $legacy_slug !== '' && (string) $term->slug === $legacy_slug ) {
					return $term;
				}
			}

			foreach ( $candidates as $term ) {
				if ( $name_en !== '' && sanitize_text_field( (string) get_term_meta( $term->term_id, self::META_DISPLAY_NAME_EN, true ) ) === $name_en ) {
					return $term;
				}
			}

			foreach ( $candidates as $term ) {
				if ( $name_fa !== '' && sanitize_text_field( (string) $term->name ) === $name_fa ) {
					return $term;
				}
			}

			foreach ( $candidates as $term ) {
				if ( $name_en !== '' && sanitize_text_field( (string) $term->name ) === $name_en ) {
					return $term;
				}
			}

			return reset( $candidates ) ?: null;
		}

		/**
		 * @param array<string,mixed> $country
		 * @return array<int,WP_Term>
		 */
		private static function collect_root_country_term_candidates( array $country ) {
			$iso2 = Bornado_Geo_Catalog::normalize_iso2( isset( $country['iso2'] ) ? $country['iso2'] : '' );
			if ( '' === $iso2 ) {
				return array();
			}

			$candidates = array();
			$seen_ids   = array();
			$used_bypass = false;

			if ( function_exists( 'bornado_location_terms_bypass_push' ) ) {
				bornado_location_terms_bypass_push();
				$used_bypass = true;
			}

			self::append_root_country_term_candidates(
				$candidates,
				$seen_ids,
				get_terms(
					array(
						'taxonomy'   => self::LOCATION_TAXONOMY,
						'hide_empty' => false,
						'parent'     => 0,
						'number'     => 0,
						'meta_query' => array(
							array(
								'key'   => self::META_COUNTRY_CODE,
								'value' => $iso2,
							),
						),
					)
				)
			);

			self::append_root_country_term_candidates(
				$candidates,
				$seen_ids,
				get_terms(
					array(
						'taxonomy'   => self::LOCATION_TAXONOMY,
						'hide_empty' => false,
						'parent'     => 0,
						'number'     => 0,
						'meta_query' => array(
							array(
								'key'   => self::META_GEO_COUNTRY_ISO2,
								'value' => $iso2,
							),
						),
					)
				)
			);

			foreach ( array_unique( array_filter( array( self::build_root_country_slug( $country ), sanitize_title( strtolower( $iso2 ) ) ) ) ) as $slug ) {
				$term = get_term_by( 'slug', $slug, self::LOCATION_TAXONOMY );
				if ( $term instanceof WP_Term && 0 === (int) $term->parent && ! isset( $seen_ids[ $term->term_id ] ) ) {
					$seen_ids[ $term->term_id ] = true;
					$candidates[]               = $term;
				}
			}

			$names = array_unique(
				array_filter(
					array(
						sanitize_text_field( isset( $country['name_fa'] ) ? (string) $country['name_fa'] : '' ),
						sanitize_text_field( isset( $country['name_en'] ) ? (string) $country['name_en'] : '' ),
					)
				)
			);

			foreach ( $names as $name ) {
				self::append_root_country_term_candidates(
					$candidates,
					$seen_ids,
					get_terms(
						array(
							'taxonomy'   => self::LOCATION_TAXONOMY,
							'hide_empty' => false,
							'parent'     => 0,
							'number'     => 0,
							'name'       => $name,
						)
					)
				);
			}

			if ( $used_bypass && function_exists( 'bornado_location_terms_bypass_pop' ) ) {
				bornado_location_terms_bypass_pop();
			}

			return $candidates;
		}

		/**
		 * @param array<int,WP_Term>                    $candidates
		 * @param array<int,bool>                       $seen_ids
		 * @param array<int,WP_Term>|WP_Error|string[] $terms
		 * @return void
		 */
		private static function append_root_country_term_candidates( array &$candidates, array &$seen_ids, $terms ) {
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return;
			}

			foreach ( (array) $terms as $term ) {
				if ( ! ( $term instanceof WP_Term ) || 0 !== (int) $term->parent ) {
					continue;
				}

				if ( isset( $seen_ids[ $term->term_id ] ) ) {
					continue;
				}

				$seen_ids[ $term->term_id ] = true;
				$candidates[]               = $term;
			}
		}

		/**
		 * @param int $geoname_id
		 * @return WP_Term|null
		 */
		public static function find_city_term_by_source_id( $geoname_id ) {
			$geoname_id = absint( $geoname_id );
			if ( $geoname_id < 1 ) {
				return null;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => self::LOCATION_TAXONOMY,
					'hide_empty' => false,
					'number'     => 1,
					'meta_query' => array(
						array(
							'key'   => self::META_GEO_SOURCE_ID,
							'value' => (string) $geoname_id,
						),
					),
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms[0] ) || ! ( $terms[0] instanceof WP_Term ) ) {
				return null;
			}

			return $terms[0];
		}

		/**
		 * @param array<string,mixed> $country
		 * @return string
		 */
		public static function build_root_country_slug( array $country ) {
			$iso2      = Bornado_Geo_Catalog::normalize_iso2( isset( $country['iso2'] ) ? $country['iso2'] : '' );
			$overrides = self::get_country_slug_overrides();

			if ( isset( $overrides[ $iso2 ] ) ) {
				return sanitize_title( $overrides[ $iso2 ] );
			}

			return sanitize_title( strtolower( $iso2 ) );
		}

		/**
		 * @param array<string,mixed> $city
		 * @return string
		 */
		public static function build_city_slug( array $city ) {
			$slug = '';

			if ( ! empty( $city['slug_candidate'] ) ) {
				$slug = sanitize_title( (string) $city['slug_candidate'] );
			}

			if ( '' === $slug && ! empty( $city['asciiname'] ) ) {
				$slug = sanitize_title( (string) $city['asciiname'] );
			}

			if ( '' === $slug && ! empty( $city['name_en'] ) ) {
				$slug = sanitize_title( (string) $city['name_en'] );
			}

			if ( '' === $slug ) {
				$slug = 'city';
			}

			return $slug;
		}

		/**
		 * @param WP_Term             $term
		 * @param array<string,mixed> $country
		 * @return void
		 */
		private static function sync_root_country_term_meta( WP_Term $term, array $country ) {
			$currency_term_id = self::resolve_currency_term_id( isset( $country['currency_code'] ) ? (string) $country['currency_code'] : '' );
			$override_name     = sanitize_text_field( (string) get_term_meta( $term->term_id, self::META_DISPLAY_NAME_FA_OVERRIDE, true ) );
			$target_name       = '' !== $override_name
				? $override_name
				: ( ! empty( $country['name_fa'] ) ? (string) $country['name_fa'] : (string) $term->name );

			if ( '' !== $target_name && (string) $term->name !== $target_name ) {
				wp_update_term(
					(int) $term->term_id,
					self::LOCATION_TAXONOMY,
					array(
						'name' => $target_name,
						'slug' => self::build_root_country_slug( $country ),
					)
				);
			}

			update_term_meta( $term->term_id, self::META_COUNTRY_CODE, isset( $country['iso2'] ) ? (string) $country['iso2'] : '' );
			update_term_meta( $term->term_id, self::META_PHONE_DIAL_CODE, isset( $country['phone_dial_code'] ) ? (string) $country['phone_dial_code'] : '' );
			update_term_meta( $term->term_id, self::META_DISPLAY_NAME_EN, isset( $country['name_en'] ) ? (string) $country['name_en'] : '' );
			update_term_meta( $term->term_id, self::META_GEO_SOURCE, self::GEO_SOURCE );
			update_term_meta( $term->term_id, self::META_GEO_COUNTRY_ISO2, isset( $country['iso2'] ) ? (string) $country['iso2'] : '' );
			update_term_meta( $term->term_id, self::META_GEO_CURRENCY_CODE, isset( $country['currency_code'] ) ? (string) $country['currency_code'] : '' );

			if ( $currency_term_id > 0 ) {
				update_term_meta( $term->term_id, self::META_CURRENCY_TERM_ID, $currency_term_id );
			}
		}

		/**
		 * @param WP_Term             $term
		 * @param array<string,mixed> $country
		 * @param array<string,mixed> $city
		 * @return void
		 */
		private static function sync_city_term_meta( WP_Term $term, array $country, array $city ) {
			if ( ! empty( $city['name_fa'] ) && (string) $term->name !== (string) $city['name_fa'] ) {
				wp_update_term(
					(int) $term->term_id,
					self::LOCATION_TAXONOMY,
					array(
						'name' => (string) $city['name_fa'],
					)
				);
			}

			update_term_meta( $term->term_id, self::META_GEO_SOURCE, self::GEO_SOURCE );
			update_term_meta( $term->term_id, self::META_GEO_SOURCE_ID, isset( $city['geoname_id'] ) ? (string) $city['geoname_id'] : '' );
			update_term_meta( $term->term_id, self::META_GEO_NAME_EN, isset( $city['name_en'] ) ? (string) $city['name_en'] : '' );
			update_term_meta( $term->term_id, self::META_GEO_COUNTRY_ISO2, isset( $country['iso2'] ) ? (string) $country['iso2'] : '' );
		}

		/**
		 * @param string $currency_code
		 * @return int
		 */
		private static function resolve_currency_term_id( $currency_code ) {
			$currency_code = strtoupper( sanitize_text_field( (string) $currency_code ) );
			if ( '' === $currency_code ) {
				return 0;
			}

			self::prime_currency_cache();

			if ( isset( self::$currency_cache[ $currency_code ] ) ) {
				return (int) self::$currency_cache[ $currency_code ];
			}

			if ( ! apply_filters( 'bornado_geo_create_missing_currency_terms', true, $currency_code ) ) {
				return 0;
			}

			$created_term_id = self::create_currency_term( $currency_code );
			if ( $created_term_id > 0 ) {
				self::$currency_cache[ $currency_code ] = $created_term_id;
			}

			return $created_term_id;
		}

		/**
		 * @return void
		 */
		private static function prime_currency_cache() {
			if ( null !== self::$currency_cache ) {
				return;
			}

			self::$currency_cache = array();
			$terms                = get_terms(
				array(
					'taxonomy'   => self::CURRENCY_TAXONOMY,
					'hide_empty' => false,
					'number'     => 0,
				)
			);

			if ( ! is_wp_error( $terms ) ) {
				foreach ( (array) $terms as $term ) {
					if ( ! ( $term instanceof WP_Term ) ) {
						continue;
					}

					self::remember_currency_term( $term );
				}
			}

			$overrides = apply_filters( 'bornado_geo_currency_term_overrides', array() );
			if ( is_array( $overrides ) ) {
				foreach ( $overrides as $code => $target ) {
					$key = strtoupper( sanitize_text_field( (string) $code ) );
					if ( '' === $key ) {
						continue;
					}

					$resolved_term_id = self::resolve_currency_override_target( $target );
					if ( $resolved_term_id > 0 ) {
						self::$currency_cache[ $key ] = $resolved_term_id;
					}
				}
			}
		}

		/**
		 * @param WP_Term $term
		 * @return void
		 */
		private static function remember_currency_term( WP_Term $term ) {
			$key = strtoupper( sanitize_text_field( (string) $term->slug ) );
			if ( '' === $key ) {
				return;
			}

			self::$currency_cache[ $key ] = (int) $term->term_id;
		}

		/**
		 * @param string $currency_code
		 * @return int
		 */
		private static function create_currency_term( $currency_code ) {
			$currency_code = strtoupper( sanitize_text_field( (string) $currency_code ) );
			if ( '' === $currency_code ) {
				return 0;
			}

			$slug = sanitize_title( strtolower( $currency_code ) );
			if ( '' === $slug ) {
				return 0;
			}

			$existing = get_term_by( 'slug', $slug, self::CURRENCY_TAXONOMY );
			if ( $existing instanceof WP_Term ) {
				self::remember_currency_term( $existing );
				return (int) $existing->term_id;
			}

			$symbol = function_exists( 'bornado_geo_get_currency_symbol' ) ? bornado_geo_get_currency_symbol( $currency_code ) : $currency_code;
			$symbol = sanitize_text_field( (string) $symbol );
			if ( '' === $symbol ) {
				$symbol = $currency_code;
			}

			$created = wp_insert_term(
				$symbol,
				self::CURRENCY_TAXONOMY,
				array(
					'slug' => $slug,
				)
			);

			if ( is_wp_error( $created ) ) {
				$term_id = 0;
				if ( 'term_exists' === $created->get_error_code() ) {
					$term_id = absint( $created->get_error_data() );
				}

				if ( $term_id <= 0 ) {
					$existing = get_term_by( 'slug', $slug, self::CURRENCY_TAXONOMY );
					if ( $existing instanceof WP_Term ) {
						self::remember_currency_term( $existing );
						return (int) $existing->term_id;
					}
				}

				if ( $term_id > 0 ) {
					$term = get_term( $term_id, self::CURRENCY_TAXONOMY );
					if ( $term instanceof WP_Term ) {
						self::remember_currency_term( $term );
						return (int) $term->term_id;
					}
				}

				return 0;
			}

			$term = get_term( isset( $created['term_id'] ) ? (int) $created['term_id'] : 0, self::CURRENCY_TAXONOMY );
			if ( $term instanceof WP_Term ) {
				self::remember_currency_term( $term );
				return (int) $term->term_id;
			}

			return 0;
		}

		/**
		 * @param mixed $target
		 * @return int
		 */
		private static function resolve_currency_override_target( $target ) {
			if ( absint( $target ) > 0 ) {
				return absint( $target );
			}

			if ( is_string( $target ) && '' !== trim( $target ) ) {
				$term = get_term_by( 'slug', sanitize_title( $target ), self::CURRENCY_TAXONOMY );
				if ( $term instanceof WP_Term ) {
					return (int) $term->term_id;
				}

				$term = get_term_by( 'name', sanitize_text_field( $target ), self::CURRENCY_TAXONOMY );
				if ( $term instanceof WP_Term ) {
					return (int) $term->term_id;
				}
			}

			if ( is_array( $target ) ) {
				foreach ( $target as $candidate ) {
					$resolved = self::resolve_currency_override_target( $candidate );
					if ( $resolved > 0 ) {
						return $resolved;
					}
				}
			}

			return 0;
		}

		/**
		 * @return array<string,string>
		 */
		private static function get_country_slug_overrides() {
			if ( null === self::$country_slug_overrides ) {
				self::$country_slug_overrides = array(
					'GB' => 'uk',
				);
			}

			return self::$country_slug_overrides;
		}
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI && ! class_exists( 'Bornado_Geo_Catalog_CLI' ) ) {
	/**
	 * WP-CLI helpers for importing GeoNames data into the Bornado catalog tables.
	 */
	final class Bornado_Geo_Catalog_CLI {
		/**
		 * Import `countryInfo.txt`.
		 *
		 * ## OPTIONS
		 *
		 * <path>
		 * : Local path to `countryInfo.txt`
		 *
		 * @param array<int,string> $args
		 * @return void
		 */
		public function import_countries( $args ) {
			$path = isset( $args[0] ) ? (string) $args[0] : '';
			$fh   = $this->open_text_file( $path );
			if ( ! is_resource( $fh ) ) {
				WP_CLI::error( 'Could not open countryInfo source file.' );
			}

			global $wpdb;

			$table = Bornado_Geo_Catalog::get_countries_table();
			$count = 0;

			while ( false !== ( $line = fgets( $fh ) ) ) {
				$line = trim( (string) $line );
				if ( '' === $line || '#' === substr( $line, 0, 1 ) ) {
					continue;
				}

				$columns = explode( "\t", $line );
				$iso2    = Bornado_Geo_Catalog::normalize_iso2( $columns[0] ?? '' );
				$name_en = sanitize_text_field( (string) ( $columns[4] ?? '' ) );

				if ( '' === $iso2 || '' === $name_en ) {
					continue;
				}

				$row = array(
					'iso2'           => $iso2,
					'geoname_id'     => absint( $columns[16] ?? 0 ),
					'name_fa'        => $name_en,
					'name_en'        => $name_en,
					'slug_candidate' => sanitize_title( strtolower( $iso2 ) ),
					'phone_dial_code'=> $this->normalize_phone_dial_code( (string) ( $columns[12] ?? '' ) ),
					'currency_code'  => strtoupper( sanitize_text_field( (string) ( $columns[10] ?? '' ) ) ),
					'updated_at'     => current_time( 'mysql' ),
				);

				$wpdb->replace( $table, $row );
				$count++;
			}

			fclose( $fh );
			WP_CLI::success( sprintf( 'Imported %d country rows.', $count ) );
		}

		/**
		 * Import `cities1000.zip` / `cities500.zip` / plain text extracts.
		 *
		 * ## OPTIONS
		 *
		 * <path>
		 * : Local path to the GeoNames city export.
		 *
		 * [--min-population=<population>]
		 * : Optional population floor.
		 *
		 * @param array<int,string>        $args
		 * @param array<string,string|int> $assoc_args
		 * @return void
		 */
		public function import_cities( $args, $assoc_args ) {
			$path           = isset( $args[0] ) ? (string) $args[0] : '';
			$min_population = isset( $assoc_args['min-population'] ) ? max( 0, (int) $assoc_args['min-population'] ) : 0;
			$fh             = $this->open_text_file( $path );

			if ( ! is_resource( $fh ) ) {
				WP_CLI::error( 'Could not open city source file.' );
			}

			global $wpdb;

			$table = Bornado_Geo_Catalog::get_cities_table();
			$count = 0;

			while ( false !== ( $line = fgets( $fh ) ) ) {
				$line = trim( (string) $line );
				if ( '' === $line ) {
					continue;
				}

				$columns = explode( "\t", $line );
				if ( 'P' !== (string) ( $columns[6] ?? '' ) ) {
					continue;
				}

				$population = (int) ( $columns[14] ?? 0 );
				if ( $population < $min_population ) {
					continue;
				}

				$country_iso2 = Bornado_Geo_Catalog::normalize_iso2( $columns[8] ?? '' );
				$name_en      = sanitize_text_field( (string) ( $columns[1] ?? '' ) );
				$ascii_name   = sanitize_text_field( (string) ( $columns[2] ?? '' ) );

				if ( '' === $country_iso2 || '' === $name_en ) {
					continue;
				}

				$row = array(
					'geoname_id'     => absint( $columns[0] ?? 0 ),
					'country_iso2'   => $country_iso2,
					'name_fa'        => $name_en,
					'name_en'        => $name_en,
					'asciiname'      => $ascii_name,
					'slug_candidate' => sanitize_title( '' !== $ascii_name ? $ascii_name : $name_en ),
					'latitude'       => (float) ( $columns[4] ?? 0 ),
					'longitude'      => (float) ( $columns[5] ?? 0 ),
					'population'     => max( 0, $population ),
					'updated_at'     => current_time( 'mysql' ),
				);

				if ( empty( $row['geoname_id'] ) ) {
					continue;
				}

				$wpdb->replace( $table, $row );
				$count++;
			}

			fclose( $fh );
			WP_CLI::success( sprintf( 'Imported %d city rows.', $count ) );
		}

		/**
		 * Apply Persian names from `alternateNamesV2.zip`.
		 *
		 * ## OPTIONS
		 *
		 * <path>
		 * : Local path to the GeoNames alternate names export.
		 *
		 * @param array<int,string> $args
		 * @return void
		 */
		public function import_fa_names( $args ) {
			$path = isset( $args[0] ) ? (string) $args[0] : '';
			$fh   = $this->open_text_file( $path );

			if ( ! is_resource( $fh ) ) {
				WP_CLI::error( 'Could not open alternateNames source file.' );
			}

			global $wpdb;

			$countries_table = Bornado_Geo_Catalog::get_countries_table();
			$cities_table    = Bornado_Geo_Catalog::get_cities_table();
			$country_hits     = 0;
			$city_hits        = 0;
			$processed_ids    = array();

			while ( false !== ( $line = fgets( $fh ) ) ) {
				$line = trim( (string) $line );
				if ( '' === $line ) {
					continue;
				}

				$columns = explode( "\t", $line );
				$lang    = strtolower( (string) ( $columns[2] ?? '' ) );
				if ( ! preg_match( '/^fa(?:$|[-_])/i', $lang ) ) {
					continue;
				}

				$geoname_id = absint( $columns[1] ?? 0 );
				$name_fa    = sanitize_text_field( (string) ( $columns[3] ?? '' ) );

				if ( $geoname_id < 1 || '' === $name_fa || isset( $processed_ids[ $geoname_id ] ) ) {
					continue;
				}

				// GeoNames can include transliterated or auxiliary values under codes
				// like `faac` or repeated `fa-*` variants. Keep only labels that
				// actually contain Persian/Arabic script characters.
				if ( ! $this->contains_persian_script( $name_fa ) ) {
					continue;
				}

				$updated = $wpdb->update(
					$countries_table,
					array(
						'name_fa'    => $name_fa,
						'updated_at' => current_time( 'mysql' ),
					),
					array(
						'geoname_id' => $geoname_id,
					)
				);

				if ( false !== $updated && $updated > 0 ) {
					$country_hits += $updated;
				}

				$updated = $wpdb->update(
					$cities_table,
					array(
						'name_fa'    => $name_fa,
						'updated_at' => current_time( 'mysql' ),
					),
					array(
						'geoname_id' => $geoname_id,
					)
				);

				if ( false !== $updated && $updated > 0 ) {
					$city_hits += $updated;
				}

				$processed_ids[ $geoname_id ] = true;
			}

			fclose( $fh );
			WP_CLI::success( sprintf( 'Applied Persian names to %d countries and %d cities.', $country_hits, $city_hits ) );
		}

		/**
		 * Seed catalog countries into `ad_country`.
		 *
		 * @return void
		 */
		public function seed_root_countries() {
			$count = Bornado_Geo_Term_Manager::seed_all_root_countries();
			WP_CLI::success( sprintf( 'Ensured %d root country terms.', $count ) );
		}

		/**
		 * Repair duplicate root-country terms created by earlier seeding runs.
		 *
		 * @return void
		 */
		public function repair_root_countries() {
			$stats = Bornado_Geo_Term_Manager::repair_root_country_duplicates();
			WP_CLI::success(
				sprintf(
					'Repaired root countries. Canonical terms synced: %d, duplicates merged: %d, duplicate roots deleted: %d.',
					isset( $stats['canonical'] ) ? (int) $stats['canonical'] : 0,
					isset( $stats['merged'] ) ? (int) $stats['merged'] : 0,
					isset( $stats['deleted'] ) ? (int) $stats['deleted'] : 0
				)
			);
		}

		/**
		 * @param string $path
		 * @return resource|false
		 */
		private function open_text_file( $path ) {
			$path = trim( (string) $path );
			if ( '' === $path || ! file_exists( $path ) ) {
				return false;
			}

			if ( preg_match( '/\.zip$/i', $path ) ) {
				$zip = new ZipArchive();
				if ( true !== $zip->open( $path ) || $zip->numFiles < 1 ) {
					return false;
				}

				$tmp_dir = wp_tempnam( 'bornado-geo-catalog' );
				if ( ! $tmp_dir ) {
					$zip->close();
					return false;
				}

				@unlink( $tmp_dir );
				wp_mkdir_p( $tmp_dir );

				$entry_name = $this->select_best_zip_entry( $zip, $path );
				if ( ! $entry_name ) {
					$zip->close();
					return false;
				}

				$zip->extractTo( $tmp_dir, array( $entry_name ) );
				$zip->close();

				$full_path = trailingslashit( $tmp_dir ) . $entry_name;
				return file_exists( $full_path ) ? fopen( $full_path, 'r' ) : false;
			}

			return fopen( $path, 'r' );
		}

		/**
		 * Pick the most likely text payload from a GeoNames zip archive.
		 *
		 * Some GeoNames zips can contain helper/readme files before the real data
		 * file. Using the first entry blindly can therefore import the wrong file
		 * and yield zero matched rows for alternate names.
		 *
		 * @param ZipArchive $zip  Open archive.
		 * @param string     $path Source zip path.
		 * @return string
		 */
		private function select_best_zip_entry( ZipArchive $zip, $path ) {
			$best_entry     = '';
			$best_score     = -1;
			$archive_stem   = strtolower( preg_replace( '/\.zip$/i', '', basename( (string) $path ) ) );

			for ( $index = 0; $index < $zip->numFiles; $index++ ) {
				$stat = $zip->statIndex( $index );
				if ( ! is_array( $stat ) || empty( $stat['name'] ) ) {
					continue;
				}

				$name = (string) $stat['name'];
				if ( '/' === substr( $name, -1 ) ) {
					continue;
				}

				$base_name = strtolower( basename( $name ) );
				$size      = ! empty( $stat['size'] ) ? (int) $stat['size'] : 0;
				$score     = 0;

				if ( preg_match( '/\.(txt|csv|tsv)$/i', $base_name ) ) {
					$score += 100;
				}

				if ( false !== strpos( $base_name, $archive_stem ) ) {
					$score += 200;
				}

				if ( false !== strpos( $base_name, 'alternate' ) ) {
					$score += 80;
				}

				if ( false !== strpos( $base_name, 'cities' ) ) {
					$score += 80;
				}

				if ( false !== strpos( $base_name, 'readme' ) || false !== strpos( $base_name, 'license' ) ) {
					$score -= 400;
				}

				$score += min( 100, (int) floor( $size / 100000 ) );

				if ( $score > $best_score ) {
					$best_score = $score;
					$best_entry = $name;
				}
			}

			if ( '' !== $best_entry ) {
				return $best_entry;
			}

			$fallback_name = $zip->getNameIndex( 0 );
			return $fallback_name ? (string) $fallback_name : '';
		}

		/**
		 * @param string $value
		 * @return string
		 */
		private function normalize_phone_dial_code( $value ) {
			$value = trim( preg_replace( '/[^\d+]/', '', (string) $value ) );
			if ( '' === $value ) {
				return '';
			}

			if ( '+' !== substr( $value, 0, 1 ) ) {
				$value = '+' . ltrim( $value, '+' );
			}

			return $value;
		}

		/**
		 * Return whether a string contains Persian/Arabic script characters.
		 *
		 * @param string $value Candidate text.
		 * @return bool
		 */
		private function contains_persian_script( $value ) {
			return 1 === preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', (string) $value );
		}
	}

	WP_CLI::add_command( 'bornado-geo', 'Bornado_Geo_Catalog_CLI' );
}

Bornado_Geo_Catalog::init();
