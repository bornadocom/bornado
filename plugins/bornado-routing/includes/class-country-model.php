<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Country_Model {

	const TAXONOMY             = 'ad_country';
	const CURRENCY_TAXONOMY    = 'ad_currency';
	const META_COUNTRY_CODE    = '_bornado_country_code';
	const META_PHONE_DIAL_CODE = '_bornado_country_phone_dial_code';
	const META_DISPLAY_NAME_EN = '_bornado_country_display_name_en';
	const META_MARKET_STATUS   = '_bornado_country_market_status';
	const META_CURRENCY_TERM_ID = '_bornado_country_currency_term_id';
	const NONCE_ACTION         = 'bornado_country_model_save';
	const NONCE_NAME           = 'bornado_country_model_nonce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_term_meta' ) );
		add_action( self::TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_edit_fields' ), 10, 2 );
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'save_term_meta' ), 10, 2 );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_term_meta' ), 10, 2 );
		add_filter( 'manage_edit-' . self::TAXONOMY . '_columns', array( __CLASS__, 'filter_admin_columns' ) );
		add_filter( 'manage_' . self::TAXONOMY . '_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 3 );
	}

	/**
	 * Register structured term meta for REST and admin usage.
	 *
	 * @return void
	 */
	public static function register_term_meta() {
		register_term_meta(
			self::TAXONOMY,
			self::META_COUNTRY_CODE,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_country_code' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'can_manage_terms' ),
			)
		);

		register_term_meta(
			self::TAXONOMY,
			self::META_DISPLAY_NAME_EN,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'can_manage_terms' ),
			)
		);

		register_term_meta(
			self::TAXONOMY,
			self::META_PHONE_DIAL_CODE,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_phone_dial_code' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'can_manage_terms' ),
			)
		);

		register_term_meta(
			self::TAXONOMY,
			self::META_MARKET_STATUS,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_market_status' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'can_manage_terms' ),
			)
		);

		register_term_meta(
			self::TAXONOMY,
			self::META_CURRENCY_TERM_ID,
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_currency_term_id' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( __CLASS__, 'can_manage_terms' ),
			)
		);
	}

	/**
	 * Render add-term fields.
	 *
	 * @return void
	 */
	public static function render_add_fields() {
		$currency_options = self::get_currency_options();
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="form-field term-bornado-country-code-wrap">
			<label for="bornado-country-code"><?php esc_html_e( 'Country Code', 'bornado-routing' ); ?></label>
			<input type="text" name="bornado_country_code" id="bornado-country-code" value="" maxlength="2" />
			<p><?php esc_html_e( 'For root market terms only. Use ISO 3166-1 alpha-2, for example GB, CA, US, DE.', 'bornado-routing' ); ?></p>
		</div>
		<div class="form-field term-bornado-country-name-en-wrap">
			<label for="bornado-country-display-name-en"><?php esc_html_e( 'English Display Name', 'bornado-routing' ); ?></label>
			<input type="text" name="bornado_country_display_name_en" id="bornado-country-display-name-en" value="" />
			<p><?php esc_html_e( 'Optional canonical English market label, for example United Kingdom.', 'bornado-routing' ); ?></p>
		</div>
		<div class="form-field term-bornado-country-phone-dial-code-wrap">
			<label for="bornado-country-phone-dial-code"><?php esc_html_e( 'Phone Dial Code', 'bornado-routing' ); ?></label>
			<input type="text" name="bornado_country_phone_dial_code" id="bornado-country-phone-dial-code" value="" maxlength="5" placeholder="+44" />
			<p><?php esc_html_e( 'For root market terms only. Use the international calling code, for example +44, +1, +98, +971.', 'bornado-routing' ); ?></p>
		</div>
		<div class="form-field term-bornado-country-status-wrap">
			<label for="bornado-country-market-status"><?php esc_html_e( 'Market Status', 'bornado-routing' ); ?></label>
			<select name="bornado_country_market_status" id="bornado-country-market-status">
				<?php foreach ( self::get_market_status_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p><?php esc_html_e( 'Recommended only for root market terms. Child city terms will not keep these values.', 'bornado-routing' ); ?></p>
		</div>
		<div class="form-field term-bornado-country-currency-wrap">
			<label for="bornado-country-currency"><?php esc_html_e( 'Currency', 'bornado-routing' ); ?></label>
			<select name="bornado_country_currency_term_id" id="bornado-country-currency">
				<option value="0"><?php esc_html_e( 'None', 'bornado-routing' ); ?></option>
				<?php foreach ( $currency_options as $term_id => $label ) : ?>
					<option value="<?php echo esc_attr( $term_id ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p>
				<?php
				echo ! empty( $currency_options )
					? esc_html__( 'Choose one of the currencies defined in the Ad Currency taxonomy. Child city terms inherit this from the root country.', 'bornado-routing' )
					: esc_html__( 'No currencies found in the Ad Currency taxonomy yet. Create them first, then assign one here.', 'bornado-routing' );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render edit-term fields.
	 *
	 * @param WP_Term $term Current term.
	 * @return void
	 */
	public static function render_edit_fields( $term, $taxonomy = '' ) {
		unset( $taxonomy );

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$data             = self::get_country_data( $term );
		$currency_options = self::get_currency_options();
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<tr class="form-field term-bornado-country-code-wrap">
			<th scope="row"><label for="bornado-country-code"><?php esc_html_e( 'Country Code', 'bornado-routing' ); ?></label></th>
			<td>
				<input type="text" name="bornado_country_code" id="bornado-country-code" value="<?php echo esc_attr( $data['country_code'] ); ?>" maxlength="2" />
				<p class="description"><?php esc_html_e( 'For root market terms only. Use ISO 3166-1 alpha-2, for example GB, CA, US, DE.', 'bornado-routing' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-bornado-country-name-en-wrap">
			<th scope="row"><label for="bornado-country-display-name-en"><?php esc_html_e( 'English Display Name', 'bornado-routing' ); ?></label></th>
			<td>
				<input type="text" name="bornado_country_display_name_en" id="bornado-country-display-name-en" value="<?php echo esc_attr( $data['display_name_en'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Optional canonical English market label, for example United Kingdom.', 'bornado-routing' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-bornado-country-phone-dial-code-wrap">
			<th scope="row"><label for="bornado-country-phone-dial-code"><?php esc_html_e( 'Phone Dial Code', 'bornado-routing' ); ?></label></th>
			<td>
				<input type="text" name="bornado_country_phone_dial_code" id="bornado-country-phone-dial-code" value="<?php echo esc_attr( $data['phone_dial_code'] ); ?>" maxlength="5" placeholder="+44" />
				<p class="description"><?php esc_html_e( 'For root market terms only. Use the international calling code, for example +44, +1, +98, +971.', 'bornado-routing' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-bornado-country-status-wrap">
			<th scope="row"><label for="bornado-country-market-status"><?php esc_html_e( 'Market Status', 'bornado-routing' ); ?></label></th>
			<td>
				<select name="bornado_country_market_status" id="bornado-country-market-status">
					<?php foreach ( self::get_market_status_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $data['market_status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Recommended only for root market terms. Child city terms will not keep these values.', 'bornado-routing' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-bornado-country-currency-wrap">
			<th scope="row"><label for="bornado-country-currency"><?php esc_html_e( 'Currency', 'bornado-routing' ); ?></label></th>
			<td>
				<select name="bornado_country_currency_term_id" id="bornado-country-currency">
					<option value="0"><?php esc_html_e( 'None', 'bornado-routing' ); ?></option>
					<?php foreach ( $currency_options as $term_id => $label ) : ?>
						<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( (int) $data['currency_term_id'], (int) $term_id ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php
					echo ! empty( $currency_options )
						? esc_html__( 'Choose one of the currencies defined in the Ad Currency taxonomy. Child city terms inherit this from the root country.', 'bornado-routing' )
						: esc_html__( 'No currencies found in the Ad Currency taxonomy yet. Create them first, then assign one here.', 'bornado-routing' );
					?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Persist term meta for root market terms.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public static function save_term_meta( $term_id, $tt_id = 0 ) {
		unset( $tt_id );

		$term_id = (int) $term_id;
		if ( $term_id < 1 || ! self::can_manage_terms() ) {
			return;
		}

		if ( empty( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		$term = get_term( $term_id, self::TAXONOMY );
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		if ( ! self::is_root_country_term( $term ) ) {
			delete_term_meta( $term_id, self::META_COUNTRY_CODE );
			delete_term_meta( $term_id, self::META_PHONE_DIAL_CODE );
			delete_term_meta( $term_id, self::META_DISPLAY_NAME_EN );
			delete_term_meta( $term_id, self::META_MARKET_STATUS );
			delete_term_meta( $term_id, self::META_CURRENCY_TERM_ID );
			return;
		}

		$country_code    = isset( $_POST['bornado_country_code'] ) ? self::sanitize_country_code( wp_unslash( $_POST['bornado_country_code'] ) ) : '';
		$phone_dial_code = isset( $_POST['bornado_country_phone_dial_code'] ) ? self::sanitize_phone_dial_code( wp_unslash( $_POST['bornado_country_phone_dial_code'] ) ) : '';
		$display_name_en = isset( $_POST['bornado_country_display_name_en'] ) ? sanitize_text_field( wp_unslash( $_POST['bornado_country_display_name_en'] ) ) : '';
		$market_status   = isset( $_POST['bornado_country_market_status'] ) ? self::sanitize_market_status( wp_unslash( $_POST['bornado_country_market_status'] ) ) : '';
		$currency_term_id = isset( $_POST['bornado_country_currency_term_id'] ) ? self::sanitize_currency_term_id( wp_unslash( $_POST['bornado_country_currency_term_id'] ) ) : 0;

		self::update_or_delete_term_meta( $term_id, self::META_COUNTRY_CODE, $country_code );
		self::update_or_delete_term_meta( $term_id, self::META_PHONE_DIAL_CODE, $phone_dial_code );
		self::update_or_delete_term_meta( $term_id, self::META_DISPLAY_NAME_EN, $display_name_en );
		self::update_or_delete_term_meta( $term_id, self::META_MARKET_STATUS, $market_status );
		self::update_or_delete_term_meta( $term_id, self::META_CURRENCY_TERM_ID, $currency_term_id > 0 ? $currency_term_id : '' );
	}

	/**
	 * Add quick-inspection columns to the taxonomy table.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function filter_admin_columns( $columns ) {
		$columns['bornado_country_code']  = __( 'Country Code', 'bornado-routing' );
		$columns['bornado_phone_dial_code'] = __( 'Phone Dial Code', 'bornado-routing' );
		$columns['bornado_market_status'] = __( 'Market Status', 'bornado-routing' );
		$columns['bornado_currency']      = __( 'Currency', 'bornado-routing' );

		return $columns;
	}

	/**
	 * Render custom admin column values.
	 *
	 * @param string $content Existing content.
	 * @param string $column_name Column slug.
	 * @param int    $term_id Term ID.
	 * @return string
	 */
	public static function render_admin_column( $content, $column_name, $term_id ) {
		$data = self::get_country_data( $term_id );
		if ( 'bornado_country_code' === $column_name ) {
			return $data['is_root_country'] ? $data['country_code'] : '—';
		}

		if ( 'bornado_market_status' === $column_name ) {
			if ( ! $data['is_root_country'] ) {
				return '—';
			}

			$options = self::get_market_status_options();
			return isset( $options[ $data['market_status'] ] ) ? $options[ $data['market_status'] ] : '—';
		}

		if ( 'bornado_phone_dial_code' === $column_name ) {
			return $data['is_root_country'] && '' !== $data['phone_dial_code'] ? $data['phone_dial_code'] : '—';
		}

		if ( 'bornado_currency' === $column_name ) {
			return $data['is_root_country'] && '' !== $data['currency_name'] ? $data['currency_name'] : '—';
		}

		return $content;
	}

	/**
	 * Return normalized market data for a root country term.
	 *
	 * @param WP_Term|int $term Term object or ID.
	 * @return array<string,mixed>
	 */
	public static function get_country_data( $term ) {
		$term = get_term( $term, self::TAXONOMY );
		if ( ! $term instanceof WP_Term ) {
			return self::get_empty_country_data();
		}

		$root_country     = self::get_root_country_term( $term );
		$is_root          = $root_country instanceof WP_Term && (int) $root_country->term_id === (int) $term->term_id;
		$currency_term_id = $root_country instanceof WP_Term ? (int) get_term_meta( $root_country->term_id, self::META_CURRENCY_TERM_ID, true ) : 0;
		$currency_term    = self::get_currency_term_by_id( $currency_term_id );

		return array(
			'term_id'          => $root_country instanceof WP_Term ? (int) $root_country->term_id : 0,
			'market_slug'      => $root_country instanceof WP_Term ? (string) $root_country->slug : '',
			'display_name_fa'  => $root_country instanceof WP_Term ? (string) $root_country->name : '',
			'display_name_en'  => $root_country instanceof WP_Term ? (string) get_term_meta( $root_country->term_id, self::META_DISPLAY_NAME_EN, true ) : '',
			'country_code'     => $root_country instanceof WP_Term ? (string) get_term_meta( $root_country->term_id, self::META_COUNTRY_CODE, true ) : '',
			'phone_dial_code'  => $root_country instanceof WP_Term ? (string) get_term_meta( $root_country->term_id, self::META_PHONE_DIAL_CODE, true ) : '',
			'market_status'    => $root_country instanceof WP_Term ? (string) get_term_meta( $root_country->term_id, self::META_MARKET_STATUS, true ) : '',
			'currency_term_id' => $currency_term instanceof WP_Term ? (int) $currency_term->term_id : 0,
			'currency_name'    => $currency_term instanceof WP_Term ? (string) $currency_term->name : '',
			'currency_slug'    => $currency_term instanceof WP_Term ? (string) $currency_term->slug : '',
			'is_root_country'  => $is_root,
			'root_country_id'  => $root_country instanceof WP_Term ? (int) $root_country->term_id : 0,
		);
	}

	/**
	 * Resolve the root country term for any location term in ad_country.
	 *
	 * @param WP_Term|int $term Term object or ID.
	 * @return WP_Term|null
	 */
	public static function get_root_country_term( $term ) {
		$term = get_term( $term, self::TAXONOMY );
		if ( ! $term instanceof WP_Term ) {
			return null;
		}

		if ( self::is_root_country_term( $term ) ) {
			return $term;
		}

		$ancestors = array_reverse( array_map( 'intval', get_ancestors( (int) $term->term_id, self::TAXONOMY, 'taxonomy' ) ) );
		if ( empty( $ancestors ) ) {
			return null;
		}

		$root_country = get_term( (int) $ancestors[0], self::TAXONOMY );

		return $root_country instanceof WP_Term ? $root_country : null;
	}

	/**
	 * Return whether a term is a root market term.
	 *
	 * @param WP_Term $term Term object.
	 * @return bool
	 */
	public static function is_root_country_term( $term ) {
		return $term instanceof WP_Term && self::TAXONOMY === $term->taxonomy && 0 === (int) $term->parent;
	}

	/**
	 * Capability gate for term meta changes.
	 *
	 * @return bool
	 */
	public static function can_manage_terms( ...$args ) {
		unset( $args );

		return current_user_can( 'manage_categories' );
	}

	/**
	 * Sanitize ISO alpha-2 country code.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string
	 */
	public static function sanitize_country_code( $value, ...$args ) {
		unset( $args );

		$value = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $value ) );

		return strlen( $value ) <= 2 ? $value : substr( $value, 0, 2 );
	}

	/**
	 * Sanitize an international phone dial code.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string
	 */
	public static function sanitize_phone_dial_code( $value, ...$args ) {
		unset( $args );

		$value = trim( (string) $value );
		$value = preg_replace( '/[^\d+]/', '', $value );

		if ( '' === $value ) {
			return '';
		}

		if ( 0 === strpos( $value, '00' ) ) {
			$value = '+' . substr( $value, 2 );
		} elseif ( '+' !== substr( $value, 0, 1 ) ) {
			$value = '+' . ltrim( $value, '+' );
		}

		$digits = preg_replace( '/[^\d]/', '', $value );
		if ( '' === $digits ) {
			return '';
		}

		$normalized = '+' . $digits;

		return preg_match( '/^\+\d{1,4}$/', $normalized ) ? $normalized : '';
	}

	/**
	 * Sanitize market status.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string
	 */
	public static function sanitize_market_status( $value, ...$args ) {
		unset( $args );

		$value   = sanitize_key( (string) $value );
		$options = self::get_market_status_options();

		return isset( $options[ $value ] ) ? $value : '';
	}

	/**
	 * Sanitize a selected currency term ID.
	 *
	 * @param mixed $value Raw meta value.
	 * @return int
	 */
	public static function sanitize_currency_term_id( $value, ...$args ) {
		unset( $args );

		$term_id = absint( $value );
		$term    = self::get_currency_term_by_id( $term_id );

		return $term instanceof WP_Term ? (int) $term->term_id : 0;
	}

	/**
	 * Available market status options.
	 *
	 * @return array<string,string>
	 */
	private static function get_market_status_options() {
		return array(
			''       => __( 'None', 'bornado-routing' ),
			'tier1'  => __( 'Tier 1', 'bornado-routing' ),
			'tier2'  => __( 'Tier 2', 'bornado-routing' ),
			'tier3'  => __( 'Tier 3', 'bornado-routing' ),
			'pilot'  => __( 'Pilot', 'bornado-routing' ),
			'hold'   => __( 'Hold', 'bornado-routing' ),
		);
	}

	/**
	 * Return available ad currency terms for admin dropdowns.
	 *
	 * @return array<int,string>
	 */
	private static function get_currency_options() {
		$options = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => self::CURRENCY_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$options[ (int) $term->term_id ] = (string) $term->name;
			}
		}

		return $options;
	}

	/**
	 * Resolve a currency term by ID.
	 *
	 * @param int $term_id Currency term ID.
	 * @return WP_Term|null
	 */
	private static function get_currency_term_by_id( $term_id ) {
		$term_id = absint( $term_id );
		if ( $term_id < 1 ) {
			return null;
		}

		$term = get_term( $term_id, self::CURRENCY_TAXONOMY );

		return $term instanceof WP_Term ? $term : null;
	}

	/**
	 * Update term meta or delete it when empty.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $meta_key Meta key.
	 * @param string|int $value Meta value.
	 * @return void
	 */
	private static function update_or_delete_term_meta( $term_id, $meta_key, $value ) {
		if ( '' === (string) $value ) {
			delete_term_meta( $term_id, $meta_key );
			return;
		}

		update_term_meta( $term_id, $meta_key, $value );
	}

	/**
	 * Empty market data payload.
	 *
	 * @return array<string,mixed>
	 */
	private static function get_empty_country_data() {
		return array(
			'term_id'         => 0,
			'market_slug'     => '',
			'display_name_fa' => '',
			'display_name_en' => '',
			'country_code'    => '',
			'phone_dial_code' => '',
			'market_status'   => '',
			'currency_term_id' => 0,
			'currency_name'   => '',
			'currency_slug'   => '',
			'is_root_country' => false,
			'root_country_id' => 0,
		);
	}
}

if ( ! function_exists( 'bornado_get_country_data' ) ) {
	/**
	 * Public helper for reading normalized market country data.
	 *
	 * @param WP_Term|int $term Term object or ID.
	 * @return array<string,mixed>
	 */
	function bornado_get_country_data( $term ) {
		if ( ! class_exists( 'Bornado_Country_Model' ) ) {
			return array();
		}

		return Bornado_Country_Model::get_country_data( $term );
	}
}
