<?php
/**
 * Plugin Name: AdForest Mobile Bottom Navigation
 * Description: Dynamic mobile bottom navigation for AdForest + Elementor without Elementor Pro.
 * Version: 1.2.0
 * Author: Bornado
 * Text Domain: adf-mobile-bottom-nav
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$adf_mobile_search_core = dirname( __DIR__ ) . '/bornado-search-core/bornado-search-core.php';
if ( ! class_exists( 'Bornado_Search_Core' ) && file_exists( $adf_mobile_search_core ) ) {
	require_once $adf_mobile_search_core;
}

final class ADF_Mobile_Bottom_Nav {
	const OPTION_KEY = 'adf_mbn_settings';

	private static $instance = null;
	private $shortcode_rendered = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_body_open', array( $this, 'render_top_search_bar' ) );
		add_action( 'wp_footer', array( $this, 'render_global_nav' ) );

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_shortcode() {
		add_shortcode( 'adf_mobile_bottom_nav', array( $this, 'shortcode' ) );
	}

	public function enqueue_assets() {
		$settings = $this->get_settings();
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		$plugin_url = plugin_dir_url( __FILE__ );
		wp_enqueue_style(
			'adf-mobile-bottom-nav',
			$plugin_url . 'assets/css/adf-mobile-bottom-nav.css',
			array(),
			'1.2.0'
		);

		wp_enqueue_script(
			'adf-mobile-bottom-nav',
			$plugin_url . 'assets/js/adf-mobile-bottom-nav.js',
			array( 'bornado-search-core' ),
			'1.2.0',
			true
		);

		$vars = array(
			'bg'           => sanitize_hex_color( $settings['background_color'] ) ?: '#ffffff',
			'active'       => sanitize_hex_color( $settings['active_color'] ) ?: '#1f6fff',
			'icon'         => sanitize_hex_color( $settings['icon_color'] ) ?: '#6f7785',
			'text'         => sanitize_hex_color( $settings['text_color'] ) ?: '#6f7785',
			'height'       => $settings['enabled'] ? absint( $settings['bar_height'] ) : 0,
			'topHeight'    => $settings['top_search_enabled'] ? absint( $settings['top_search_height'] ) : 0,
			'topBg'        => sanitize_hex_color( $settings['top_search_bg'] ) ?: '#ffffff',
			'topBorder'    => sanitize_hex_color( $settings['top_search_border'] ) ?: '#dfe3eb',
			'topText'      => sanitize_hex_color( $settings['top_search_text'] ) ?: '#6f7785',
			'topIcon'      => sanitize_hex_color( $settings['top_search_icon'] ) ?: '#1f6fff',
			'topOffset'    => $settings['top_search_enabled'] ? 'max(' . absint( $settings['top_search_height'] ) . 'px, 88px)' : '0px',
			'hideOnScroll' => (bool) $settings['hide_on_scroll'],
		);

		wp_add_inline_style(
			'adf-mobile-bottom-nav',
			':root{--adf-mbn-bg:' . esc_attr( $vars['bg'] ) . ';--adf-mbn-active:' . esc_attr( $vars['active'] ) . ';--adf-mbn-icon:' . esc_attr( $vars['icon'] ) . ';--adf-mbn-text:' . esc_attr( $vars['text'] ) . ';--adf-mbn-height:' . esc_attr( $vars['height'] ) . 'px;--adf-mbn-top-height:' . esc_attr( $vars['topHeight'] ) . 'px;--adf-mbn-top-offset:' . esc_attr( $vars['topOffset'] ) . ';--adf-mbn-top-bg:' . esc_attr( $vars['topBg'] ) . ';--adf-mbn-top-border:' . esc_attr( $vars['topBorder'] ) . ';--adf-mbn-top-text:' . esc_attr( $vars['topText'] ) . ';--adf-mbn-top-icon:' . esc_attr( $vars['topIcon'] ) . ';}'
		);

		wp_localize_script(
			'adf-mobile-bottom-nav',
			'ADFMobileBottomNav',
			array(
				'hideOnScroll'  => $vars['hideOnScroll'],
				'cities'        => $this->get_city_options( $settings ),
				'selectedCity'  => $this->get_selected_city( $settings ),
				'categories'    => $this->get_category_options(),
				'selectedCategory' => $this->get_selected_category(),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'countriesNonce' => wp_create_nonce( 'adforest_get_countries_nonce' ),
			)
		);
	}

	public function render_top_search_bar() {
		if ( is_admin() || is_feed() || wp_is_json_request() ) {
			return;
		}
		$settings = $this->get_settings();
		if ( ! $settings['top_search_enabled'] ) {
			return;
		}

		echo $this->get_top_search_markup( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function render_global_nav() {
		if ( is_admin() || is_feed() || wp_is_json_request() ) {
			return;
		}
		$settings = $this->get_settings();
		if ( ! $settings['enabled'] || empty( $settings['items'] ) || $this->shortcode_rendered ) {
			return;
		}

		echo $this->get_nav_markup( $settings['items'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function shortcode() {
		$settings = $this->get_settings();
		if ( empty( $settings['items'] ) ) {
			return '';
		}
		$this->shortcode_rendered = true;
		return $this->get_nav_markup( $settings['items'], true );
	}

	private function get_nav_markup( $items, $from_shortcode = false ) {
		$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
		$rendered    = array();

		foreach ( $items as $item ) {
			$url = $this->resolve_item_link( $item );
			if ( empty( $url ) ) {
				continue;
			}

			$is_active = $this->urls_match( $url, $current_url );
			$classes   = 'adf-mbn__item' . ( $is_active ? ' is-active' : '' );

			$rendered[] = sprintf(
				'<li class="%1$s"><a class="adf-mbn__link" href="%2$s" aria-label="%3$s">%4$s<span class="adf-mbn__label">%5$s</span>%6$s</a></li>',
				esc_attr( $classes ),
				esc_url( $url ),
				esc_attr( $item['label'] ),
				$this->get_icon_svg( $item['icon'], $item['label'] ),
				esc_html( $item['label'] ),
				! empty( $item['badge'] ) ? '<span class="adf-mbn__badge" aria-hidden="true">' . esc_html( $item['badge'] ) . '</span>' : ''
			);
		}

		if ( empty( $rendered ) ) {
			return '';
		}

		$classes = 'adf-mobile-bottom-nav';
		if ( $from_shortcode ) {
			$classes .= ' adf-mobile-bottom-nav--shortcode';
		}

		return '<nav class="' . esc_attr( $classes ) . '" role="navigation" aria-label="' . esc_attr__( 'Mobile bottom navigation', 'adf-mobile-bottom-nav' ) . '"><ul class="adf-mbn__list">' . implode( '', $rendered ) . '</ul></nav>';
	}

	private function urls_match( $left, $right ) {
		$left_parts  = wp_parse_url( $left );
		$right_parts = wp_parse_url( $right );

		$left_host  = strtolower( (string) ( $left_parts['host'] ?? '' ) );
		$right_host = strtolower( (string) ( $right_parts['host'] ?? '' ) );
		if ( $left_host && $right_host && $left_host !== $right_host ) {
			return false;
		}

		$left_path  = untrailingslashit( (string) ( $left_parts['path'] ?? '' ) );
		$right_path = untrailingslashit( (string) ( $right_parts['path'] ?? '' ) );

		if ( '' === $left_path ) {
			$left_path = '/';
		}
		if ( '' === $right_path ) {
			$right_path = '/';
		}

		return $left_path === $right_path;
	}

	private function resolve_item_link( $item ) {
		if ( ! empty( $item['dynamic'] ) ) {
			return $this->get_dynamic_link( $item['dynamic'] );
		}
		return ! empty( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
	}

	private function get_top_search_markup( $settings ) {
		$search_actions  = function_exists( 'bornado_search_get_actions' ) ? bornado_search_get_actions() : array(
			'default_action'    => home_url( '/' ),
			'all_cities_action' => home_url( '/' ),
			'all_categories_action' => home_url( '/' ),
			'all_filters_action' => home_url( '/' ),
		);
		$search_page_url = $search_actions['default_action'];
		$all_cities_url  = $search_actions['all_cities_action'];
		$all_categories_url = $search_actions['all_categories_action'];
		$all_filters_url = $search_actions['all_filters_action'];
		$ad_title        = isset( $_GET['ad_title'] ) ? sanitize_text_field( wp_unslash( $_GET['ad_title'] ) ) : '';
		$placeholder     = $this->get_dynamic_placeholder( $settings );
		$selected_city   = $this->get_selected_city( $settings );
		$selected_category = $this->get_selected_category();

		ob_start();
		?>
		<div class="adf-mobile-top-search" role="search" aria-label="<?php esc_attr_e( 'Mobile ad title search', 'adf-mobile-bottom-nav' ); ?>">
			<form
				method="get"
				action="<?php echo esc_url( $search_page_url ); ?>"
				class="adf-mobile-top-search__form"
				data-default-action="<?php echo esc_url( $search_page_url ); ?>"
				data-all-cities-action="<?php echo esc_url( $all_cities_url ); ?>"
				data-all-categories-action="<?php echo esc_url( $all_categories_url ); ?>"
				data-all-filters-action="<?php echo esc_url( $all_filters_url ); ?>"
			>
				<label for="adf-mobile-top-search-title" class="screen-reader-text"><?php esc_html_e( 'Search ads with title', 'adf-mobile-bottom-nav' ); ?></label>
				<span class="adf-mobile-top-search__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24"><path d="M10.5 3a7.5 7.5 0 015.96 12.05l4.24 4.24-1.41 1.41-4.24-4.24A7.5 7.5 0 1110.5 3zm0 2a5.5 5.5 0 100 11 5.5 5.5 0 000-11z"/></svg>
				</span>
				<input
					type="text"
					class="adf-mobile-top-search__input"
					id="adf-mobile-top-search-title"
					name="ad_title"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					value="<?php echo esc_attr( $ad_title ); ?>"
				/>
				<input type="hidden" name="country_id" class="adf-mobile-top-search__city-input" value="<?php echo esc_attr( $selected_city['value'] ); ?>">
				<input type="hidden" name="cat_id" class="adf-mobile-top-search__category-input" value="<?php echo esc_attr( $selected_category['value'] ); ?>">
				<?php echo $this->render_hidden_search_inputs( array( 'ad_title', 'country_id', 'ad_country', 'cat_id', 'ad_cats' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</form>
			<div class="adf-mobile-top-search__filters">
				<button type="button" class="adf-mobile-top-search__filter-strip adf-mobile-top-search__city-strip" aria-haspopup="dialog" aria-expanded="false">
					<span class="adf-mobile-top-search__filter-left">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 00-7 7c0 5.14 7 13 7 13s7-7.86 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/></svg>
						<span class="adf-mobile-top-search__filter-text"><strong class="adf-mobile-top-search__city-label"><?php echo esc_html( $selected_city['label'] ); ?></strong></span>
					</span>
					<svg class="adf-mobile-top-search__filter-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5 5-5z"/></svg>
				</button>
				<button type="button" class="adf-mobile-top-search__filter-strip adf-mobile-top-search__category-strip" aria-haspopup="dialog" aria-expanded="false">
					<span class="adf-mobile-top-search__filter-left">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3V3zm10 0h8v5h-8V3zM3 13h5v8H3v-8zm7 0h11v8H10v-8z"/></svg>
						<span class="adf-mobile-top-search__filter-text"><strong class="adf-mobile-top-search__category-label"><?php echo esc_html( $selected_category['label'] ); ?></strong></span>
					</span>
					<svg class="adf-mobile-top-search__filter-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5 5-5z"/></svg>
				</button>
			</div>
		</div>
		<div class="adf-mobile-city-sheet" aria-hidden="true">
			<div class="adf-mobile-city-sheet__backdrop" data-filter-close></div>
			<div class="adf-mobile-city-sheet__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Select city', 'adf-mobile-bottom-nav' ); ?>">
				<div class="adf-mobile-city-sheet__handle"></div>
				<h4 class="adf-mobile-city-sheet__title"><?php esc_html_e( 'انتخاب شهر', 'adf-mobile-bottom-nav' ); ?></h4>
				<input type="text" class="adf-mobile-city-sheet__search" placeholder="<?php esc_attr_e( 'جستجوی شهر...', 'adf-mobile-bottom-nav' ); ?>">
				<ul class="adf-mobile-city-sheet__list"></ul>
			</div>
		</div>
		<div class="adf-mobile-category-sheet" aria-hidden="true">
			<div class="adf-mobile-category-sheet__backdrop" data-filter-close></div>
			<div class="adf-mobile-category-sheet__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Select category', 'adf-mobile-bottom-nav' ); ?>">
				<div class="adf-mobile-category-sheet__handle"></div>
				<h4 class="adf-mobile-category-sheet__title"><?php esc_html_e( 'انتخاب دسته‌بندی', 'adf-mobile-bottom-nav' ); ?></h4>
				<input type="text" class="adf-mobile-category-sheet__search" placeholder="<?php esc_attr_e( 'جستجوی دسته‌بندی...', 'adf-mobile-bottom-nav' ); ?>">
				<ul class="adf-mobile-category-sheet__list"></ul>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function get_dynamic_placeholder( $settings ) {
		$default = ! empty( $settings['top_search_placeholder'] ) ? $settings['top_search_placeholder'] : __( 'جستجو در همه آگهی ها', 'adf-mobile-bottom-nav' );
		$cat_name = $this->get_current_category_name();
		if ( '' !== $cat_name ) {
			return sprintf( __( 'جستجو در %s', 'adf-mobile-bottom-nav' ), $cat_name );
		}
		return $default;
	}

	private function get_current_category_name() {
		$queried = get_queried_object();
		if ( is_object( $queried ) && isset( $queried->taxonomy ) && in_array( $queried->taxonomy, array( 'ad_cats', 'category' ), true ) && ! empty( $queried->name ) ) {
			return sanitize_text_field( $queried->name );
		}

		$cat_id_keys = array( 'cat_id', 'ad_cats' );
		foreach ( $cat_id_keys as $key ) {
			if ( isset( $_GET[ $key ] ) && is_numeric( $_GET[ $key ] ) ) {
				$term = get_term_by( 'id', absint( $_GET[ $key ] ), 'ad_cats' );
				if ( $term && ! is_wp_error( $term ) ) {
					return sanitize_text_field( $term->name );
				}
			}
		}

		if ( isset( $_GET['ad_cats'] ) && ! is_numeric( $_GET['ad_cats'] ) ) {
			$term = get_term_by( 'slug', sanitize_title( wp_unslash( $_GET['ad_cats'] ) ), 'ad_cats' );
			if ( $term && ! is_wp_error( $term ) ) {
				return sanitize_text_field( $term->name );
			}
		}
		return '';
	}

	private function format_city_term( $term ) {
		if ( ! $term || is_wp_error( $term ) ) {
			return array(
				'value'  => '',
				'label'  => '',
				'url'    => '',
				'parent' => '0',
			);
		}

		$term_link = get_term_link( $term );

		return array(
			'value'  => (string) $term->term_id,
			'label'  => $term->name,
			'url'    => ! is_wp_error( $term_link ) ? $term_link : '',
			'parent' => (string) $term->parent,
		);
	}

	private function format_category_term( $term, $depth = 0 ) {
		if ( ! $term || is_wp_error( $term ) ) {
			return array(
				'value'  => '',
				'label'  => '',
				'url'    => '',
				'parent' => '0',
			);
		}

		$term_link = get_term_link( $term );
		$prefix    = $depth > 0 ? str_repeat( ' - ', $depth ) : '';

		return array(
			'value'  => (string) $term->term_id,
			'label'  => $prefix . $term->name,
			'url'    => ! is_wp_error( $term_link ) ? $term_link : '',
			'parent' => (string) $term->parent,
		);
	}

	private function normalize_filter_label( $label ) {
		return trim( preg_replace( '/^(?:\s*-\s*)+/', '', (string) $label ) );
	}

	private function get_all_cities_option() {
		$search_actions = function_exists( 'bornado_search_get_actions' ) ? bornado_search_get_actions() : array(
			'all_cities_action' => home_url( '/' ),
		);

		return array(
			'value'  => '',
			'label'  => __( 'تمام شهرها', 'adf-mobile-bottom-nav' ),
			'url'    => $search_actions['all_cities_action'],
			'parent' => '0',
		);
	}

	private function get_all_categories_option() {
		$search_actions = function_exists( 'bornado_search_get_actions' ) ? bornado_search_get_actions() : array(
			'all_categories_action' => home_url( '/' ),
		);

		return array(
			'value'  => '',
			'label'  => __( 'تمام دسته‌بندی‌ها', 'adf-mobile-bottom-nav' ),
			'url'    => $search_actions['all_categories_action'],
			'parent' => '0',
		);
	}

	private function get_city_options( $settings ) {
		$cities = array(
			$this->get_all_cities_option(),
		);
		if ( taxonomy_exists( 'ad_country' ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'ad_country',
					'hide_empty' => false,
					'parent'     => 0,
					'number'     => 0,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$cities[] = $this->format_city_term( $term );
				}
			}
		}

		return $cities;
	}

	private function get_category_options() {
		$categories = array(
			$this->get_all_categories_option(),
		);

		if ( taxonomy_exists( 'ad_cats' ) ) {
			$categories = array_merge( $categories, $this->get_category_branch( 0, 0 ) );
		}

		return $categories;
	}

	private function get_category_branch( $parent = 0, $depth = 0 ) {
		$branch = array();
		$terms  = get_terms(
			array(
				'taxonomy'   => 'ad_cats',
				'hide_empty' => false,
				'parent'     => (int) $parent,
				'number'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return $branch;
		}

		foreach ( $terms as $term ) {
			$branch[] = $this->format_category_term( $term, $depth );
			$branch   = array_merge( $branch, $this->get_category_branch( $term->term_id, $depth + 1 ) );
		}

		return $branch;
	}

	private function get_selected_city( $settings ) {
		$requested     = '';
		$has_requested = false;
		if ( isset( $_GET['country_id'] ) ) {
			$requested     = sanitize_text_field( wp_unslash( $_GET['country_id'] ) );
			$has_requested = true;
		} elseif ( isset( $_GET['ad_country'] ) ) {
			$requested     = sanitize_text_field( wp_unslash( $_GET['ad_country'] ) );
			$has_requested = true;
		}
		$default  = sanitize_text_field( $settings['top_search_default_city'] ?? '' );
		$cities   = $this->get_city_options( $settings );
		$selected = $has_requested ? $requested : $default;

		if ( '' === $selected || __( 'تمام شهرها', 'adf-mobile-bottom-nav' ) === $selected ) {
			return $this->get_all_cities_option();
		}

		if ( taxonomy_exists( 'ad_country' ) ) {
			$term = null;
			if ( is_numeric( $selected ) ) {
				$term = get_term_by( 'id', absint( $selected ), 'ad_country' );
			}

			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'slug', sanitize_title( $selected ), 'ad_country' );
			}

			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'name', $selected, 'ad_country' );
			}

			if ( $term && ! is_wp_error( $term ) ) {
				return $this->format_city_term( $term );
			}
		}

		return isset( $cities[0] ) ? $cities[0] : $this->get_all_cities_option();
	}

	private function get_selected_category() {
		$categories    = $this->get_category_options();
		$requested     = '';
		$requested_id  = '';
		$queried       = get_queried_object();

		if ( isset( $_GET['cat_id'] ) ) {
			$requested    = sanitize_text_field( wp_unslash( $_GET['cat_id'] ) );
			$requested_id = $requested;
		} elseif ( isset( $_GET['ad_cats'] ) ) {
			$requested = sanitize_text_field( wp_unslash( $_GET['ad_cats'] ) );
		} elseif ( is_object( $queried ) && isset( $queried->taxonomy ) && 'ad_cats' === $queried->taxonomy && ! empty( $queried->term_id ) ) {
			$requested_id = (string) absint( $queried->term_id );
			$requested    = ! empty( $queried->slug ) ? (string) $queried->slug : '';
		}

		if ( '' === $requested && '' === $requested_id ) {
			return $this->get_all_categories_option();
		}

		foreach ( $categories as $category ) {
			if ( '' !== $requested_id && $category['value'] === $requested_id ) {
				$category['label'] = $this->normalize_filter_label( $category['label'] );
				return $category;
			}
		}

		if ( taxonomy_exists( 'ad_cats' ) ) {
			$term = null;
			if ( '' !== $requested_id && is_numeric( $requested_id ) ) {
				$term = get_term_by( 'id', absint( $requested_id ), 'ad_cats' );
			}

			if ( ( ! $term || is_wp_error( $term ) ) && '' !== $requested ) {
				$term = get_term_by( 'slug', sanitize_title( $requested ), 'ad_cats' );
			}

			if ( ( ! $term || is_wp_error( $term ) ) && '' !== $requested ) {
				$term = get_term_by( 'name', $requested, 'ad_cats' );
			}

			if ( $term && ! is_wp_error( $term ) ) {
				$category          = $this->format_category_term( $term, count( get_ancestors( (int) $term->term_id, 'ad_cats' ) ) );
				$category['label'] = $this->normalize_filter_label( $category['label'] );
				return $category;
			}
		}

		return isset( $categories[0] ) ? $categories[0] : $this->get_all_categories_option();
	}

	private function render_hidden_search_inputs( $excluded_keys = array() ) {
		$query_args = function_exists( 'bornado_search_build_clean_query_args' ) ? bornado_search_build_clean_query_args( $_GET ) : array();
		if ( empty( $query_args ) ) {
			return '';
		}

		foreach ( $excluded_keys as $excluded_key ) {
			unset( $query_args[ $excluded_key ] );
		}

		return $this->render_hidden_input_fields( $query_args );
	}

	private function render_hidden_input_fields( $values, $parent_key = '' ) {
		if ( ! is_array( $values ) ) {
			return '';
		}

		$output = '';
		foreach ( $values as $key => $value ) {
			$key = (string) $key;
			if ( '' === $key ) {
				continue;
			}

			$field_name = '' === $parent_key ? $key : $parent_key . '[' . $key . ']';
			if ( is_array( $value ) ) {
				$output .= $this->render_hidden_input_fields( $value, $field_name );
				continue;
			}

			$output .= sprintf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $field_name ),
				esc_attr( (string) $value )
			);
		}

		return $output;
	}

	private function get_dynamic_link( $dynamic ) {
		switch ( $dynamic ) {
			case 'home':
				return home_url( '/' );
			case 'dashboard':
				$page_id = (int) get_option( 'adforest_profile_page' );
				return $page_id ? get_permalink( $page_id ) : home_url( '/dashboard/' );
			case 'favorites':
				$page_id = (int) get_option( 'adforest_fav_page' );
				return $page_id ? get_permalink( $page_id ) : home_url( '/favorites/' );
			case 'post-ad':
				$page_id = (int) get_option( 'adforest_ad_post_page' );
				return $page_id ? get_permalink( $page_id ) : home_url( '/post-ad/' );
			default:
				return '';
		}
	}

	private function get_icon_svg( $icon, $label ) {
		$icons = array(
			'home'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l9 8h-3v10h-5v-6H11v6H6V11H3l9-8z"/></svg>',
			'search'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 015.96 12.05l4.24 4.24-1.41 1.41-4.24-4.24A7.5 7.5 0 1110.5 3zm0 2a5.5 5.5 0 100 11 5.5 5.5 0 000-11z"/></svg>',
			'heart'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21l-1.35-1.23C5.4 15 2 11.92 2 8.16 2 5.08 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09A5.98 5.98 0 0116.5 3C19.58 3 22 5.08 22 8.16c0 3.76-3.4 6.84-8.65 11.61L12 21z"/></svg>',
			'plus'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5z"/></svg>',
			'user'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v2h16v-2c0-2.76-3.58-5-8-5z"/></svg>',
			'category'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3V3zm10 0h8v5h-8V3zM3 13h5v8H3v-8zm7 0h11v8H10v-8z"/></svg>',
			'chat'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16a2 2 0 012 2v9a2 2 0 01-2 2H8l-5 4v-4H4a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>',
			'custom-svg'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>',
		);

		$selected = isset( $icons[ $icon ] ) ? $icons[ $icon ] : $icons['home'];
		return '<span class="adf-mbn__icon" role="img" aria-label="' . esc_attr( $label ) . '">' . $selected . '</span>';
	}

	public function add_settings_page() {
		add_menu_page(
			__( 'Mobile Bottom Nav', 'adf-mobile-bottom-nav' ),
			__( 'Mobile Bottom Nav', 'adf-mobile-bottom-nav' ),
			'manage_options',
			'adf-mobile-bottom-nav',
			array( $this, 'render_settings_page' ),
			'dashicons-smartphone',
			58
		);
	}

	public function register_settings() {
		register_setting(
			'adf_mbn_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults(),
			)
		);
	}

	public function sanitize_settings( $input ) {
		$defaults = $this->defaults();
		$output   = $defaults;

		$output['enabled']          = ! empty( $input['enabled'] ) ? 1 : 0;
		$output['hide_on_scroll']   = ! empty( $input['hide_on_scroll'] ) ? 1 : 0;
		$output['background_color'] = sanitize_hex_color( $input['background_color'] ?? $defaults['background_color'] ) ?: $defaults['background_color'];
		$output['active_color']     = sanitize_hex_color( $input['active_color'] ?? $defaults['active_color'] ) ?: $defaults['active_color'];
		$output['icon_color']       = sanitize_hex_color( $input['icon_color'] ?? $defaults['icon_color'] ) ?: $defaults['icon_color'];
		$output['text_color']       = sanitize_hex_color( $input['text_color'] ?? $defaults['text_color'] ) ?: $defaults['text_color'];
		$output['bar_height']       = max( 56, min( 90, absint( $input['bar_height'] ?? $defaults['bar_height'] ) ) );
		$output['top_search_enabled']     = ! empty( $input['top_search_enabled'] ) ? 1 : 0;
		$output['top_search_height']      = max( 48, min( 78, absint( $input['top_search_height'] ?? $defaults['top_search_height'] ) ) );
		$output['top_search_bg']          = sanitize_hex_color( $input['top_search_bg'] ?? $defaults['top_search_bg'] ) ?: $defaults['top_search_bg'];
		$output['top_search_border']      = sanitize_hex_color( $input['top_search_border'] ?? $defaults['top_search_border'] ) ?: $defaults['top_search_border'];
		$output['top_search_text']        = sanitize_hex_color( $input['top_search_text'] ?? $defaults['top_search_text'] ) ?: $defaults['top_search_text'];
		$output['top_search_icon']        = sanitize_hex_color( $input['top_search_icon'] ?? $defaults['top_search_icon'] ) ?: $defaults['top_search_icon'];
		$output['top_search_placeholder'] = sanitize_text_field( $input['top_search_placeholder'] ?? $defaults['top_search_placeholder'] );
		$output['top_search_default_city'] = sanitize_text_field( $input['top_search_default_city'] ?? $defaults['top_search_default_city'] );

		$items = isset( $input['items'] ) && is_array( $input['items'] ) ? $input['items'] : array();
		$items = array_slice( $items, 0, 5 );
		$clean = array();

		foreach ( $items as $item ) {
			$label = sanitize_text_field( $item['label'] ?? '' );
			if ( '' === $label ) {
				continue;
			}

			$clean[] = array(
				'label'   => $label,
				'icon'    => sanitize_key( $item['icon'] ?? 'home' ),
				'url'     => esc_url_raw( $item['url'] ?? '' ),
				'dynamic' => sanitize_key( $item['dynamic'] ?? '' ),
				'badge'   => sanitize_text_field( $item['badge'] ?? '' ),
			);
		}

		$output['items'] = ! empty( $clean ) ? $clean : $defaults['items'];
		return $output;
	}

	private function defaults() {
		return array(
			'enabled'          => 1,
			'hide_on_scroll'   => 0,
			'background_color' => '#ffffff',
			'active_color'     => '#1f6fff',
			'icon_color'       => '#6f7785',
			'text_color'       => '#6f7785',
			'bar_height'       => 64,
			'top_search_enabled'     => 1,
			'top_search_height'      => 62,
			'top_search_bg'          => '#ffffff',
			'top_search_border'      => '#dfe3eb',
			'top_search_text'        => '#6f7785',
			'top_search_icon'        => '#1f6fff',
			'top_search_placeholder' => 'جستجو در همه آگهی ها',
			'top_search_default_city' => '',
			'items'            => array(
				array(
					'label'   => 'خانه',
					'icon'    => 'home',
					'url'     => home_url( '/' ),
					'dynamic' => 'home',
					'badge'   => '',
				),
				array(
					'label'   => 'جستجو',
					'icon'    => 'search',
					'url'     => home_url( '/search/' ),
					'dynamic' => '',
					'badge'   => '',
				),
				array(
					'label'   => 'ثبت آگهی',
					'icon'    => 'plus',
					'url'     => home_url( '/post-ad/' ),
					'dynamic' => 'post-ad',
					'badge'   => '',
				),
				array(
					'label'   => 'علاقه‌مندی',
					'icon'    => 'heart',
					'url'     => home_url( '/favorites/' ),
					'dynamic' => 'favorites',
					'badge'   => '2',
				),
			),
		);
	}

	private function get_settings() {
		$options = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $options, $this->defaults() );
	}

	public function render_settings_page() {
		$settings = $this->get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AdForest Mobile Bottom Navigation', 'adf-mobile-bottom-nav' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'adf_mbn_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Navigation', 'adf-mobile-bottom-nav' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="1" <?php checked( 1, $settings['enabled'] ); ?>> <?php esc_html_e( 'Show on all frontend pages (mobile only).', 'adf-mobile-bottom-nav' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hide on Scroll', 'adf-mobile-bottom-nav' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[hide_on_scroll]" value="1" <?php checked( 1, $settings['hide_on_scroll'] ); ?>> <?php esc_html_e( 'Hide while scrolling down and show on scroll up.', 'adf-mobile-bottom-nav' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Bar Height', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="number" min="56" max="90" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[bar_height]" value="<?php echo esc_attr( $settings['bar_height'] ); ?>"> px</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Background Color', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[background_color]" value="<?php echo esc_attr( $settings['background_color'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Active Item Color', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[active_color]" value="<?php echo esc_attr( $settings['active_color'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Icon Color', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[icon_color]" value="<?php echo esc_attr( $settings['icon_color'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Text Color', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>"></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Mobile Top Search Bar', 'adf-mobile-bottom-nav' ); ?></h2>
				<p><?php esc_html_e( 'This section uses AdForest ad title search behavior (ad_title).', 'adf-mobile-bottom-nav' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Top Search', 'adf-mobile-bottom-nav' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_enabled]" value="1" <?php checked( 1, $settings['top_search_enabled'] ); ?>> <?php esc_html_e( 'Show fixed search bar at top on mobile.', 'adf-mobile-bottom-nav' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Search Bar Height', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="number" min="48" max="78" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_height]" value="<?php echo esc_attr( $settings['top_search_height'] ); ?>"> px</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Placeholder', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_placeholder]" value="<?php echo esc_attr( $settings['top_search_placeholder'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default City', 'adf-mobile-bottom-nav' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_default_city]" value="<?php echo esc_attr( $settings['top_search_default_city'] ); ?>">
							<p class="description"><?php esc_html_e( 'اگر خالی بماند، گزینه "تمام شهرها" به‌صورت پیش‌فرض انتخاب می‌شود.', 'adf-mobile-bottom-nav' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Top Bar Background', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_bg]" value="<?php echo esc_attr( $settings['top_search_bg'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Input Border Color', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_border]" value="<?php echo esc_attr( $settings['top_search_border'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Text Color', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_text]" value="<?php echo esc_attr( $settings['top_search_text'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Icon Color', 'adf-mobile-bottom-nav' ); ?></th>
						<td><input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[top_search_icon]" value="<?php echo esc_attr( $settings['top_search_icon'] ); ?>"></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Menu Items (max 5)', 'adf-mobile-bottom-nav' ); ?></h2>
				<p><?php esc_html_e( 'Use Dynamic Link for AdForest pages, otherwise set custom URL.', 'adf-mobile-bottom-nav' ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Label', 'adf-mobile-bottom-nav' ); ?></th>
							<th><?php esc_html_e( 'Icon', 'adf-mobile-bottom-nav' ); ?></th>
							<th><?php esc_html_e( 'Dynamic Link', 'adf-mobile-bottom-nav' ); ?></th>
							<th><?php esc_html_e( 'Custom URL', 'adf-mobile-bottom-nav' ); ?></th>
							<th><?php esc_html_e( 'Badge', 'adf-mobile-bottom-nav' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $settings['items'] as $index => $item ) : ?>
						<tr>
							<td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[items][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $item['label'] ); ?>" class="regular-text"></td>
							<td>
								<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[items][<?php echo esc_attr( $index ); ?>][icon]">
									<?php foreach ( array( 'home', 'search', 'heart', 'plus', 'user', 'category', 'chat' ) as $icon ) : ?>
										<option value="<?php echo esc_attr( $icon ); ?>" <?php selected( $icon, $item['icon'] ); ?>><?php echo esc_html( ucfirst( $icon ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[items][<?php echo esc_attr( $index ); ?>][dynamic]">
									<option value="" <?php selected( '', $item['dynamic'] ); ?>><?php esc_html_e( 'None', 'adf-mobile-bottom-nav' ); ?></option>
									<option value="home" <?php selected( 'home', $item['dynamic'] ); ?>><?php esc_html_e( 'Home', 'adf-mobile-bottom-nav' ); ?></option>
									<option value="dashboard" <?php selected( 'dashboard', $item['dynamic'] ); ?>><?php esc_html_e( 'Dashboard', 'adf-mobile-bottom-nav' ); ?></option>
									<option value="favorites" <?php selected( 'favorites', $item['dynamic'] ); ?>><?php esc_html_e( 'Favorites', 'adf-mobile-bottom-nav' ); ?></option>
									<option value="post-ad" <?php selected( 'post-ad', $item['dynamic'] ); ?>><?php esc_html_e( 'Post Ad', 'adf-mobile-bottom-nav' ); ?></option>
								</select>
							</td>
							<td><input type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[items][<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $item['url'] ); ?>" class="regular-text"></td>
							<td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[items][<?php echo esc_attr( $index ); ?>][badge]" value="<?php echo esc_attr( $item['badge'] ); ?>" class="small-text"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p class="description"><?php esc_html_e( 'For Elementor usage (optional), use shortcode: [adf_mobile_bottom_nav]', 'adf-mobile-bottom-nav' ); ?></p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

ADF_Mobile_Bottom_Nav::instance();
