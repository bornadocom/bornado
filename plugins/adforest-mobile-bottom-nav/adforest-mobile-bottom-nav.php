<?php
/**
 * Plugin Name: AdForest Mobile Bottom Navigation
 * Description: Dynamic mobile bottom navigation for AdForest + Elementor without Elementor Pro.
 * Version: 1.3.5
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

$adf_mobile_location_picker = dirname( __DIR__ ) . '/bornado-location-picker/bornado-location-picker.php';
if ( ! class_exists( 'Bornado_Location_Picker_Plugin' ) && file_exists( $adf_mobile_location_picker ) ) {
	require_once $adf_mobile_location_picker;
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
		add_action( 'wp_ajax_adf_mobile_filter_count', array( $this, 'handle_mobile_filter_count_ajax' ) );
		add_action( 'wp_ajax_nopriv_adf_mobile_filter_count', array( $this, 'handle_mobile_filter_count_ajax' ) );

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

		$hide_top_search = $this->should_hide_top_search_bar();

		$plugin_url = plugin_dir_url( __FILE__ );
		$plugin_dir = plugin_dir_path( __FILE__ );
		$style_ver  = file_exists( $plugin_dir . 'assets/css/adf-mobile-bottom-nav.css' ) ? (string) filemtime( $plugin_dir . 'assets/css/adf-mobile-bottom-nav.css' ) : '1.3.5';
		$script_ver = file_exists( $plugin_dir . 'assets/js/adf-mobile-bottom-nav.js' ) ? (string) filemtime( $plugin_dir . 'assets/js/adf-mobile-bottom-nav.js' ) : '1.3.5';
		wp_enqueue_style(
			'adf-mobile-bottom-nav',
			$plugin_url . 'assets/css/adf-mobile-bottom-nav.css',
			array(),
			$style_ver
		);

		wp_enqueue_script(
			'adf-mobile-bottom-nav',
			$plugin_url . 'assets/js/adf-mobile-bottom-nav.js',
			array( 'bornado-search-core' ),
			$script_ver,
			true
		);

		$vars = array(
			'bg'           => sanitize_hex_color( $settings['background_color'] ) ?: '#ffffff',
			'active'       => sanitize_hex_color( $settings['active_color'] ) ?: '#1f6fff',
			'icon'         => sanitize_hex_color( $settings['icon_color'] ) ?: '#6f7785',
			'text'         => sanitize_hex_color( $settings['text_color'] ) ?: '#6f7785',
			'height'       => $settings['enabled'] ? absint( $settings['bar_height'] ) : 0,
			'topHeight'    => ( $settings['top_search_enabled'] && ! $hide_top_search ) ? absint( $settings['top_search_height'] ) : 0,
			'topBg'        => sanitize_hex_color( $settings['top_search_bg'] ) ?: '#ffffff',
			'topBorder'    => sanitize_hex_color( $settings['top_search_border'] ) ?: '#dfe3eb',
			'topText'      => sanitize_hex_color( $settings['top_search_text'] ) ?: '#6f7785',
			'topIcon'      => sanitize_hex_color( $settings['top_search_icon'] ) ?: '#1f6fff',
			'topOffset'    => ( $settings['top_search_enabled'] && ! $hide_top_search ) ? 'max(' . absint( $settings['top_search_height'] ) . 'px, 88px)' : '0px',
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
				'hideOnScroll'     => $vars['hideOnScroll'],
				'cities'           => $this->get_city_options( $settings ),
				'selectedCity'     => $this->get_selected_city( $settings ),
				'categories'       => $this->get_category_options(),
				'selectedCategory' => $this->get_selected_category(),
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'countriesNonce'   => wp_create_nonce( 'adforest_get_countries_nonce' ),
				'mobileCountNonce' => wp_create_nonce( 'adf_mobile_filter_count' ),
				'isLoggedIn'       => is_user_logged_in(),
				'favoritesUrl'     => is_user_logged_in() ? $this->get_favorites_url() : '',
				'favoritesLoginMessage' => __( 'برای مشاهده علاقه‌مندی‌ها ابتدا وارد حساب کاربری شوید.', 'adf-mobile-bottom-nav' ),
				'mobileFiltersApplyTemplate' => __( 'نمایش {{count}} آگهی', 'adf-mobile-bottom-nav' ),
				'mobileFiltersApplyZero'     => __( 'آگهی‌ای یافت نشد', 'adf-mobile-bottom-nav' ),
				'mobileFiltersApplyLoading'  => __( 'در حال محاسبه...', 'adf-mobile-bottom-nav' ),
				'mobileFiltersApplyError'    => __( 'خطا در محاسبه آگهی‌ها', 'adf-mobile-bottom-nav' ),
				'mobileFiltersApplyHint'     => __( 'فیلترهای دلخواه را انتخاب کنید و در پایان نتایج را نمایش دهید.', 'adf-mobile-bottom-nav' ),
				'mobileFiltersCountDebounce' => 260,
			)
		);
	}

	public function handle_mobile_filter_count_ajax() {
		check_ajax_referer( 'adf_mobile_filter_count', 'security' );

		if ( ! function_exists( 'adforest_sanitize_search_params' ) || ! function_exists( 'adforest_build_search_query_args' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Search filters are unavailable.', 'adf-mobile-bottom-nav' ),
				),
				500
			);
		}

		$raw = array();
		if ( isset( $_POST['filters_raw'] ) && is_string( $_POST['filters_raw'] ) ) {
			parse_str( wp_unslash( $_POST['filters_raw'] ), $raw );
		} elseif ( isset( $_POST['filters'] ) && is_array( $_POST['filters'] ) ) {
			$raw = wp_unslash( $_POST['filters'] );
		}

		$params = adforest_sanitize_search_params( $raw );
		$args   = adforest_build_search_query_args( $params );

		$args['posts_per_page']         = 1;
		$args['fields']                 = 'ids';
		$args['no_found_rows']          = false;
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;
		$args['cache_results']          = false;

		$query = new WP_Query( $args );

		wp_send_json_success(
			array(
				'total'         => (int) $query->found_posts,
				'max_num_pages' => (int) $query->max_num_pages,
			)
		);
	}

	public function render_top_search_bar() {
		if ( is_admin() || is_feed() || wp_is_json_request() ) {
			return;
		}
		$settings = $this->get_settings();
		if ( ! $settings['top_search_enabled'] || $this->should_hide_top_search_bar() ) {
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

	private function is_single_ad_context() {
		return is_singular( 'ad_post' );
	}

	public function render_single_ad_contact_nav( $post_id = 0, $args = array() ) {
		return $this->get_single_ad_contact_nav_markup( false, $post_id, $args );
	}

	private function get_single_ad_contact_nav_markup( $from_shortcode = false, $post_id = 0, $args = array() ) {
		$post_id = $post_id > 0 ? (int) $post_id : get_queried_object_id();
		if ( $post_id < 1 || 'ad_post' !== get_post_type( $post_id ) ) {
			return '';
		}

		$args = wp_parse_args(
			(array) $args,
			array(
				'nav_classes'   => '',
				'exclude_types' => array(),
				'phone_reveal'  => false,
			)
		);

		$actions = $this->get_single_ad_contact_actions( $post_id );
		if ( ! empty( $args['exclude_types'] ) ) {
			$excluded_types = array_map( 'sanitize_key', (array) $args['exclude_types'] );
			$actions        = array_values(
				array_filter(
					$actions,
					static function ( $action ) use ( $excluded_types ) {
						$type = isset( $action['type'] ) ? sanitize_key( (string) $action['type'] ) : '';
						return '' !== $type && ! in_array( $type, $excluded_types, true );
					}
				)
			);
		}

		if ( empty( $actions ) ) {
			return '';
		}

		$rendered = array();
		foreach ( $actions as $action ) {
			$classes    = 'adf-mbn__item adf-mbn__item--contact adf-mbn__item--contact-' . sanitize_html_class( $action['type'] );
			$link_class = 'adf-mbn__link adf-mbn__link--contact';
			if ( ! empty( $action['class'] ) ) {
				$link_class .= ' ' . trim( (string) $action['class'] );
			}

			if ( ! empty( $args['phone_reveal'] ) && 'phone' === $action['type'] && empty( $action['requires_login'] ) ) {
				$rendered[] = sprintf(
					'<li class="%1$s"><a class="%2$s bornado-contact-reveal-trigger" href="javascript:void(0)" aria-label="%3$s" data-ad-id="%4$d">%5$s<span class="adf-mbn__label bornado-contact-reveal-label">%6$s</span><span class="adf-mbn__meta style_2_ph"></span></a></li>',
					esc_attr( $classes ),
					esc_attr( $link_class ),
					esc_attr__( 'شماره تماس', 'adf-mobile-bottom-nav' ),
					absint( $post_id ),
					$this->get_icon_svg( $action['icon'], $action['label'] ),
					esc_html__( 'شماره تماس', 'adf-mobile-bottom-nav' )
				);
				continue;
			}

			$target_rel = '';
			if ( ! empty( $action['new_tab'] ) ) {
				$target_rel = ' target="_blank" rel="noopener noreferrer"';
			}

			$rendered[] = sprintf(
				'<li class="%1$s"><a class="%2$s" href="%3$s" aria-label="%4$s" %5$s%6$s>%7$s<span class="adf-mbn__label">%8$s</span></a></li>',
				esc_attr( $classes ),
				esc_attr( $link_class ),
				esc_url( $action['url'] ),
				esc_attr( $action['label'] ),
				$this->implode_html_attributes( $action['attrs'] ),
				$target_rel,
				$this->get_icon_svg( $action['icon'], $action['label'] ),
				esc_html( $action['label'] )
			);
		}

		$classes = 'adf-mobile-bottom-nav adf-mobile-bottom-nav--contact';
		if ( $from_shortcode ) {
			$classes .= ' adf-mobile-bottom-nav--shortcode';
		}
		if ( ! empty( $args['nav_classes'] ) ) {
			$classes .= ' ' . trim( (string) $args['nav_classes'] );
		}

		return sprintf(
			'<nav class="%1$s" role="navigation" aria-label="%2$s"><ul class="adf-mbn__list adf-mbn__list--contact" style="%3$s">%4$s</ul></nav>',
			esc_attr( $classes ),
			esc_attr__( 'روش های ارتباطی آگهی', 'adf-mobile-bottom-nav' ),
			esc_attr( 'grid-template-columns:repeat(' . count( $actions ) . ', minmax(0, 1fr));' ),
			implode( '', $rendered )
		);
	}

	private function get_single_ad_contact_actions( $post_id ) {
		global $adforest_theme;

		$post_id    = (int) $post_id;
		$poster_id  = (int) get_post_field( 'post_author', $post_id );
		$user       = $poster_id > 0 ? get_userdata( $poster_id ) : false;
		$contact_no = (string) get_post_meta( $post_id, '_adforest_poster_contact', true );

		if ( '' === $contact_no && $poster_id > 0 ) {
			$contact_no = (string) get_user_meta( $poster_id, '_sb_contact', true );
		}

		$poster_email = $user instanceof WP_User ? strtolower( trim( (string) $user->user_email ) ) : '';
		$current_url  = function_exists( 'adforest_get_current_url' )
			? (string) adforest_get_current_url()
			: (string) get_permalink( $post_id );
		$guest_login_url = function_exists( 'bornado_get_safe_login_redirect_url' )
			? (string) bornado_get_safe_login_redirect_url( $current_url )
			: (string) wp_login_url( $current_url );
		$phone_login_required = function_exists( 'adforest_showPhone_to_users' )
			? (bool) adforest_showPhone_to_users()
			: false;
		$allow_sb_chat  = ! empty( $adforest_theme['sb_ad_sbchat_chat'] );
		$communication_mode = isset( $adforest_theme['communication_mode'] ) ? (string) $adforest_theme['communication_mode'] : 'both';
		$has_custom_contact_methods = function_exists( 'bornado_has_ad_contact_methods' )
			? (bool) bornado_has_ad_contact_methods( $post_id )
			: false;
		$selected_contact_methods = $has_custom_contact_methods && function_exists( 'bornado_get_ad_contact_methods' )
			? (array) bornado_get_ad_contact_methods( $post_id )
			: array();
		$contact_method_statuses = function_exists( 'bornado_get_user_contact_method_statuses' )
			? (array) bornado_get_user_contact_method_statuses( $poster_id )
			: array();
		$sb_plugin_options = get_option( 'sb_plugin_options', array() );
		$sb_chat_feature_active = class_exists( 'SB_Chat' )
			&& $allow_sb_chat
			&& isset( $sb_plugin_options['sbChat-active'] )
			&& 1 == $sb_plugin_options['sbChat-active'] // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			&& class_exists( 'SB_Chat_Setting_Page' );
		$actions         = array();
		$current_user_id = get_current_user_id();
		$is_owner        = $current_user_id > 0 && $current_user_id === $poster_id;

		$show_custom_phone = $has_custom_contact_methods
			&& in_array( 'phone', $selected_contact_methods, true )
			&& ! empty( $contact_method_statuses['phone']['enabled'] )
			&& '' !== $contact_no;
		$show_custom_whatsapp = $has_custom_contact_methods
			&& in_array( 'whatsapp', $selected_contact_methods, true )
			&& ! empty( $contact_method_statuses['whatsapp']['enabled'] )
			&& '' !== $contact_no;
		$show_custom_email = $has_custom_contact_methods
			&& in_array( 'email', $selected_contact_methods, true )
			&& ! empty( $contact_method_statuses['email']['enabled'] )
			&& '' !== $poster_email;
		$show_custom_site_message = $has_custom_contact_methods
			&& in_array( 'site_message', $selected_contact_methods, true )
			&& $sb_chat_feature_active
			&& ! $is_owner;

		if ( $show_custom_phone ) {
			$actions[] = array(
				'type'           => 'phone',
				'label'          => 'تماس',
				'icon'           => 'phone',
				'url'            => $phone_login_required ? $guest_login_url : 'tel:' . preg_replace( '/[^\d+]/', '', $contact_no ),
				'attrs'          => array(),
				'new_tab'        => false,
				'class'          => '',
				'requires_login' => $phone_login_required,
			);
		}

		if ( $show_custom_whatsapp ) {
			$actions[] = array(
				'type'           => 'whatsapp',
				'label'          => 'واتس اپ',
				'icon'           => 'whatsapp',
				'url'            => $phone_login_required ? $guest_login_url : $this->get_single_ad_whatsapp_url( $post_id, $contact_no ),
				'attrs'          => array(),
				'new_tab'        => ! $phone_login_required,
				'class'          => '',
				'requires_login' => $phone_login_required,
			);
		}

		if ( $show_custom_email ) {
			$actions[] = array(
				'type'           => 'email',
				'label'          => 'ایمیل',
				'icon'           => 'email',
				'url'            => $phone_login_required ? $guest_login_url : 'mailto:' . sanitize_email( $poster_email ),
				'attrs'          => array(),
				'new_tab'        => false,
				'class'          => '',
				'requires_login' => $phone_login_required,
			);
		}

		if ( $show_custom_site_message ) {
			$actions[] = array(
				'type'           => 'site-message',
				'label'          => 'گفتگو',
				'icon'           => 'chat',
				'url'            => '#',
				'attrs'          => array(
					'data-user_id' => (string) $poster_id,
					'data-post_id' => (string) $post_id,
				),
				'new_tab'        => false,
				'class'          => 'scroll chat_toggler_popup sbchat-myBtn',
				'requires_login' => false,
			);
		}

		if ( $has_custom_contact_methods ) {
			return $actions;
		}

		if ( '' !== $contact_no && in_array( $communication_mode, array( 'both', 'phone' ), true ) ) {
			$actions[] = array(
				'type'           => 'phone',
				'label'          => 'تماس',
				'icon'           => 'phone',
				'url'            => $phone_login_required ? $guest_login_url : 'tel:' . preg_replace( '/[^\d+]/', '', $contact_no ),
				'attrs'          => array(),
				'new_tab'        => false,
				'class'          => '',
				'requires_login' => $phone_login_required,
			);
		}

		return array_values( $actions );
	}

	private function get_single_ad_whatsapp_url( $post_id, $contact_no ) {
		$post_id          = (int) $post_id;
		$whatsapp_number  = preg_replace( '/[^\d]/', '', (string) $contact_no );
		$post_link        = function_exists( 'bornado_get_readable_permalink' )
			? (string) bornado_get_readable_permalink( $post_id )
			: (string) get_permalink( $post_id );
		$post_title       = get_the_title( $post_id );
		$whatsapp_message = trim( $post_title . ' - ' . $post_link );

		if ( '' === $whatsapp_number ) {
			return $post_link ? (string) $post_link : home_url( '/' );
		}

		return 'https://api.whatsapp.com/send?phone=' . rawurlencode( $whatsapp_number ) . '&text=' . rawurlencode( $whatsapp_message );
	}

	private function implode_html_attributes( $attributes ) {
		if ( ! is_array( $attributes ) || empty( $attributes ) ) {
			return '';
		}

		$compiled = array();
		foreach ( $attributes as $name => $value ) {
			$name = trim( (string) $name );
			if ( '' === $name || null === $value || '' === $value ) {
				continue;
			}

			$compiled[] = sprintf( '%1$s="%2$s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}

		return empty( $compiled ) ? '' : implode( ' ', $compiled ) . ' ';
	}

	private function get_nav_markup( $items, $from_shortcode = false ) {
		if ( $this->is_single_ad_context() ) {
			$single_ad_markup = $this->get_single_ad_contact_nav_markup( $from_shortcode );
			if ( '' !== $single_ad_markup ) {
				return $single_ad_markup;
			}
		}

		$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
		$rendered    = array();
		$items       = $this->sort_nav_items(
			$this->maybe_swap_profile_nav_items(
				$this->maybe_append_filters_item(
					$this->filter_nav_items(
						$this->maybe_append_auth_item( $items )
					)
				)
			)
		);

		foreach ( $items as $item ) {
			$url = $this->resolve_item_link( $item );
			if ( empty( $url ) ) {
				continue;
			}

			$item_label = $this->get_item_label( $item );
			$is_active = $this->is_item_active( $item, $url, $current_url );
			$classes   = 'adf-mbn__item' . ( $is_active ? ' is-active' : '' );
			$link_attrs = $this->get_item_link_attrs( $item, $url );

			$rendered[] = sprintf(
				'<li class="%1$s"><a class="adf-mbn__link" href="%2$s" aria-label="%3$s" %4$s>%5$s<span class="adf-mbn__label">%6$s</span>%7$s</a></li>',
				esc_attr( $classes ),
				esc_url( $url ),
				esc_attr( $item_label ),
				$link_attrs,
				$this->get_icon_svg( $item['icon'], $item_label ),
				esc_html( $item_label ),
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

	private function is_item_active( $item, $url, $current_url ) {
		if ( ! empty( $item['dynamic'] ) && 'filters' === $item['dynamic'] ) {
			return false;
		}

		if ( ! empty( $item['dynamic'] ) && 'auth' === $item['dynamic'] && ! is_user_logged_in() ) {
			return false;
		}

		if ( ! empty( $item['dynamic'] ) && 'my_ads' === $item['dynamic'] ) {
			return $this->is_profile_page()
				&& isset( $_GET['page_type'] )
				&& 'my_ads' === sanitize_key( wp_unslash( $_GET['page_type'] ) );
		}

		if ( ! empty( $item['dynamic'] ) && 'messages' === $item['dynamic'] ) {
			return $this->is_profile_page()
				&& isset( $_GET['page_type'] )
				&& 'msg' === sanitize_key( wp_unslash( $_GET['page_type'] ) );
		}

		if ( ! empty( $item['dynamic'] ) && 'favorites' === $item['dynamic'] ) {
			return is_user_logged_in()
				&& isset( $_GET['page_type'] )
				&& 'fav_ads' === sanitize_key( wp_unslash( $_GET['page_type'] ) );
		}

		if ( ! empty( $item['dynamic'] ) && in_array( $item['dynamic'], array( 'auth', 'dashboard' ), true ) && is_user_logged_in() ) {
			if ( $this->is_profile_page() ) {
				$page_type = isset( $_GET['page_type'] ) ? sanitize_key( wp_unslash( $_GET['page_type'] ) ) : '';

				return '' === $page_type || 'my_profile' === $page_type;
			}
		}

		if ( ! empty( $item['dynamic'] ) && 'post-ad' === $item['dynamic'] ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			return $this->is_post_ad_page();
		}

		return $this->urls_match( $url, $current_url );
	}

	private function get_item_link_attrs( $item, $url ) {
		if ( ! empty( $item['dynamic'] ) && 'filters' === $item['dynamic'] ) {
			return 'data-adf-mbn-filters="1" role="button" aria-expanded="false"';
		}

		if ( ! empty( $item['dynamic'] ) && 'favorites' === $item['dynamic'] && ! is_user_logged_in() ) {
			return 'data-adf-mbn-favorites-guest="1" role="button"';
		}

		if ( ! empty( $item['dynamic'] ) && 'post-ad' === $item['dynamic'] && ! is_user_logged_in() ) {
			if ( function_exists( 'bornado_auth_modal_trigger_attrs' ) ) {
				return bornado_auth_modal_trigger_attrs( 'login', 'phone' ) . ' role="button"';
			}
			return 'role="button"';
		}

		if ( empty( $item['dynamic'] ) || 'auth' !== $item['dynamic'] || is_user_logged_in() ) {
			return '';
		}

		if ( function_exists( 'bornado_auth_modal_trigger_attrs' ) ) {
			return bornado_auth_modal_trigger_attrs( 'login', 'phone' );
		}

		return '';
	}

	private function get_favorites_url() {
		return add_query_arg( 'page_type', 'fav_ads', $this->get_profile_page_url() );
	}

	private function get_profile_page_url() {
		return function_exists( 'bornado_auth_modal_profile_url' )
			? bornado_auth_modal_profile_url()
			: home_url( '/profile/' );
	}

	private function get_my_ads_url() {
		return add_query_arg( 'page_type', 'my_ads', $this->get_profile_page_url() );
	}

	private function get_messages_url() {
		return add_query_arg( 'page_type', 'msg', $this->get_profile_page_url() );
	}

	private function is_profile_page() {
		global $adforest_theme;

		$page_id = isset( $adforest_theme['sb_profile_page'] )
			? (int) apply_filters( 'adforest_language_page_id', $adforest_theme['sb_profile_page'] )
			: 0;

		if ( $page_id && is_page( $page_id ) ) {
			return true;
		}

		$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );

		return $this->urls_match( $this->get_profile_page_url(), $current_url );
	}

	private function is_post_ad_page() {
		global $adforest_theme;

		$page_id = isset( $adforest_theme['sb_post_ad_page'] )
			? (int) apply_filters( 'adforest_language_page_id', $adforest_theme['sb_post_ad_page'] )
			: 0;

		if ( $page_id && is_page( $page_id ) ) {
			return true;
		}

		$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );

		return $this->urls_match( $this->get_post_ad_url(), $current_url );
	}

	private function is_profile_nav_context() {
		return $this->is_profile_page() || $this->is_post_ad_page();
	}

	private function should_hide_top_search_bar() {
		return $this->is_profile_nav_context();
	}

	private function get_post_ad_url() {
		global $adforest_theme;

		$page_id = isset( $adforest_theme['sb_post_ad_page'] )
			? apply_filters( 'adforest_language_page_id', $adforest_theme['sb_post_ad_page'] )
			: 0;

		if ( $page_id ) {
			$url = apply_filters( 'adforest_ad_post_verified_link', get_permalink( $page_id ) );
			if ( $url ) {
				return (string) $url;
			}
		}

		return home_url( '/ad-post/' );
	}

	private function get_post_ad_guest_href() {
		return add_query_arg( 'u', $this->get_post_ad_url(), home_url( '/' ) );
	}

	private function is_search_nav_item( $item ) {
		$dynamic = isset( $item['dynamic'] ) ? (string) $item['dynamic'] : '';
		if ( '' !== $dynamic ) {
			return false;
		}

		$icon  = isset( $item['icon'] ) ? (string) $item['icon'] : '';
		$label = isset( $item['label'] ) ? (string) $item['label'] : '';

		return 'search' === $icon || 'جستجو' === $label;
	}

	private function filter_nav_items( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$filtered = array();
		foreach ( $items as $item ) {
			if ( $this->is_search_nav_item( $item ) ) {
				continue;
			}
			$filtered[] = $item;
		}

		return $filtered;
	}

	private function maybe_append_auth_item( $items ) {
		if ( is_user_logged_in() ) {
			return $items;
		}

		foreach ( $items as $item ) {
			if ( ! empty( $item['dynamic'] ) && 'auth' === $item['dynamic'] ) {
				return $items;
			}
		}

		$items[] = array(
			'label'   => 'ورود',
			'icon'    => 'user',
			'url'     => function_exists( 'bornado_auth_modal_fallback_url' ) ? bornado_auth_modal_fallback_url( 'login' ) : home_url( '/' ),
			'dynamic' => 'auth',
			'badge'   => '',
		);

		return $items;
	}

	private function maybe_append_filters_item( $items ) {
		if ( ! $this->is_mobile_filters_available() ) {
			return $items;
		}

		$filter_index = null;
		foreach ( $items as $index => $item ) {
			if ( ! empty( $item['dynamic'] ) && 'filters' === $item['dynamic'] ) {
				$filter_index = (int) $index;
				break;
			}
		}

		$items = array_values(
			array_filter(
				(array) $items,
				static function ( $item ) {
					return empty( $item['dynamic'] ) || 'filters' !== $item['dynamic'];
				}
			)
		);

		$filter_item = array(
			'label'   => 'فیلتر',
			'icon'    => 'filters',
			'url'     => 'javascript:void(0);',
			'dynamic' => 'filters',
			'badge'   => '',
		);

		$insert_at = null !== $filter_index ? $filter_index : 1;
		array_splice( $items, min( $insert_at, count( $items ) ), 0, array( $filter_item ) );

		return $items;
	}

	private function maybe_swap_profile_nav_items( $items ) {
		if ( ! $this->is_profile_nav_context() || ! is_array( $items ) ) {
			return $items;
		}

		$swapped = array();
		foreach ( $items as $item ) {
			$dynamic = ! empty( $item['dynamic'] ) ? (string) $item['dynamic'] : '';

			if ( 'filters' === $dynamic ) {
				$item = array(
					'label'   => 'آگهی های من',
					'icon'    => 'category',
					'url'     => $this->get_my_ads_url(),
					'dynamic' => 'my_ads',
					'badge'   => '',
				);
			} elseif ( 'favorites' === $dynamic ) {
				$item = array(
					'label'   => 'پیام های من',
					'icon'    => 'chat',
					'url'     => $this->get_messages_url(),
					'dynamic' => 'messages',
					'badge'   => '',
				);
			}

			$swapped[] = $item;
		}

		return $swapped;
	}

	private function get_nav_item_order_weight( $item ) {
		$dynamic = ! empty( $item['dynamic'] ) ? (string) $item['dynamic'] : '';
		$weights = array(
			'home'      => 10,
			'filters'   => 20,
			'my_ads'    => 20,
			'post-ad'   => 30,
			'favorites' => 40,
			'messages'  => 40,
			'auth'      => 50,
			'dashboard' => 50,
		);

		if ( isset( $weights[ $dynamic ] ) ) {
			return $weights[ $dynamic ];
		}

		$icon = isset( $item['icon'] ) ? (string) $item['icon'] : '';
		$icon_weights = array(
			'home'     => 10,
			'filters'  => 20,
			'category' => 20,
			'plus'     => 30,
			'heart'    => 40,
			'chat'     => 40,
			'user'     => 50,
		);

		return isset( $icon_weights[ $icon ] ) ? $icon_weights[ $icon ] : 999;
	}

	private function sort_nav_items( $items ) {
		if ( ! is_array( $items ) || count( $items ) < 2 ) {
			return is_array( $items ) ? $items : array();
		}

		$indexed = array_values( $items );
		usort(
			$indexed,
			function ( $left, $right ) {
				$left_weight  = $this->get_nav_item_order_weight( $left );
				$right_weight = $this->get_nav_item_order_weight( $right );

				if ( $left_weight === $right_weight ) {
					return 0;
				}

				return $left_weight < $right_weight ? -1 : 1;
			}
		);

		return $indexed;
	}

	private function get_item_label( $item ) {
		if ( ! empty( $item['dynamic'] ) && 'auth' === $item['dynamic'] && is_user_logged_in() ) {
			return 'حساب من';
		}

		return isset( $item['label'] ) ? (string) $item['label'] : '';
	}

	private function get_top_search_markup( $settings ) {
		global $adforest_theme;

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

		$logo_url = '';
		if ( isset( $adforest_theme['sb_site_logo_mobile']['url'] ) && ! empty( $adforest_theme['sb_site_logo_mobile']['url'] ) ) {
			$logo_url = $adforest_theme['sb_site_logo_mobile']['url'];
		} elseif ( isset( $adforest_theme['sb_site_logo']['url'] ) && ! empty( $adforest_theme['sb_site_logo']['url'] ) ) {
			$logo_url = $adforest_theme['sb_site_logo']['url'];
		} elseif ( defined( 'ADFOREST_IMAGE_PATH' ) ) {
			$logo_url = ADFOREST_IMAGE_PATH . '/adt-logo.png';
		}
		$logo_alt = get_bloginfo( 'name' );
		$brand_home_url = function_exists( 'bornado_search_get_brand_home_url' )
			? bornado_search_get_brand_home_url()
			: home_url( '/' );

		ob_start();
		?>
		<div class="adf-mobile-top-search" role="search" aria-label="<?php esc_attr_e( 'Mobile ad title search', 'adf-mobile-bottom-nav' ); ?>">
			<div class="adf-mobile-top-search__row">
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
				<?php if ( ! empty( $logo_url ) ) : ?>
				<a class="adf-mobile-top-search__brand" href="<?php echo esc_url( $brand_home_url ); ?>" aria-label="<?php echo esc_attr( $logo_alt ); ?>">
					<img class="adf-mobile-top-search__brand-img" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" loading="eager" decoding="async" />
				</a>
				<?php endif; ?>
			</div>
			<div class="adf-mobile-top-search__filters">
				<?php
				if ( function_exists( 'bornado_render_location_picker' ) ) {
					echo bornado_render_location_picker(
						array(
							'mode'                   => 'compact',
							'class_name'             => 'adf-mobile-top-search__location-picker',
							'button_label'           => __( 'کشور و شهر', 'adf-mobile-bottom-nav' ),
							'submit_label'           => __( 'اعمال', 'adf-mobile-bottom-nav' ),
							'reset_label'            => __( 'همه کشورها', 'adf-mobile-bottom-nav' ),
							'panel_heading'          => __( 'انتخاب کشور و شهر', 'adf-mobile-bottom-nav' ),
							'country_heading'        => __( 'کشورها', 'adf-mobile-bottom-nav' ),
							'city_heading'           => __( 'شهرها', 'adf-mobile-bottom-nav' ),
							'search_label'           => __( 'جستجو در کشورها', 'adf-mobile-bottom-nav' ),
							'city_label'             => __( 'جستجو در شهرها', 'adf-mobile-bottom-nav' ),
							'auto_submit'            => true,
							'external_form_selector' => '.adf-mobile-top-search__form',
							'external_input_selector'=> '.adf-mobile-top-search__city-input',
							'render_hidden_input'    => false,
							'submit_on_apply'        => true,
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<button type="button" class="adf-mobile-top-search__filter-strip adf-mobile-top-search__category-strip bornado-mobile-choice__trigger bornado-mobile-choice__trigger--compact" aria-haspopup="dialog" aria-expanded="false">
					<span class="bornado-mobile-choice__trigger-copy bornado-mobile-choice__trigger-copy--compact">
						<span class="bornado-mobile-choice__trigger-label"><?php esc_html_e( 'دسته‌بندی', 'adf-mobile-bottom-nav' ); ?></span>
						<span class="bornado-mobile-choice__summary adf-mobile-top-search__category-label"><?php echo esc_html( $selected_category['label'] ); ?></span>
					</span>
					<span class="bornado-mobile-choice__trigger-icon bornado-mobile-choice__trigger-icon--compact" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v5h-8V3zM3 13h5v8H3v-8zm7 0h11v8H10v-8z"/></svg>
					</span>
				</button>
			</div>
		</div>
		<div class="adf-mobile-category-sheet bornado-mobile-choice__sheet" aria-hidden="true">
			<button type="button" class="adf-mobile-category-sheet__backdrop bornado-mobile-choice__backdrop" data-filter-close aria-label="<?php esc_attr_e( 'Close category picker', 'adf-mobile-bottom-nav' ); ?>"></button>
			<div class="adf-mobile-category-sheet__panel bornado-mobile-choice__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Select category', 'adf-mobile-bottom-nav' ); ?>">
				<div class="adf-mobile-category-sheet__handle bornado-mobile-choice__handle"></div>
				<div class="adf-mobile-category-sheet__panel-head bornado-mobile-choice__panel-head">
					<h4 class="adf-mobile-category-sheet__title bornado-mobile-choice__panel-title"><?php esc_html_e( 'انتخاب دسته‌بندی', 'adf-mobile-bottom-nav' ); ?></h4>
					<button type="button" class="adf-mobile-category-sheet__close bornado-mobile-choice__close" data-filter-close aria-label="<?php esc_attr_e( 'بستن', 'adf-mobile-bottom-nav' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.4 5l.7.7L12 10.59l4.9-4.89.7-.7 1.4 1.41-.7.7L13.41 12l4.89 4.9.7.7-1.41 1.4-.7-.7L12 13.41l-4.9 4.89-.7.7-1.4-1.41.7-.7L10.59 12 5.7 7.1l-.7-.7L6.4 5z"/></svg>
					</button>
				</div>
				<input type="text" class="adf-mobile-category-sheet__search bornado-mobile-choice__search" placeholder="<?php esc_attr_e( 'جستجوی دسته‌بندی...', 'adf-mobile-bottom-nav' ); ?>">
				<ul class="adf-mobile-category-sheet__list bornado-mobile-choice__list"></ul>
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
		$selected_location = class_exists( 'Bornado_Location_Picker_Service' ) && method_exists( 'Bornado_Location_Picker_Service', 'get_selected_location' )
			? Bornado_Location_Picker_Service::get_selected_location()
			: array();
		if ( ! empty( $selected_location['city']['id'] ) ) {
			$requested     = (string) absint( $selected_location['city']['id'] );
			$has_requested = true;
		} elseif ( ! empty( $selected_location['country']['id'] ) ) {
			$requested     = (string) absint( $selected_location['country']['id'] );
			$has_requested = true;
		} elseif ( isset( $_GET['country_id'] ) ) {
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
				return function_exists( 'bornado_search_get_brand_home_url' )
					? bornado_search_get_brand_home_url()
					: home_url( '/' );
			case 'dashboard':
				return function_exists( 'bornado_auth_modal_profile_url' ) ? bornado_auth_modal_profile_url() : home_url( '/profile/' );
			case 'auth':
				if ( is_user_logged_in() ) {
					return function_exists( 'bornado_auth_modal_profile_url' ) ? bornado_auth_modal_profile_url() : home_url( '/profile/' );
				}
				return function_exists( 'bornado_auth_modal_fallback_url' ) ? bornado_auth_modal_fallback_url( 'login' ) : home_url( '/' );
			case 'favorites':
				if ( ! is_user_logged_in() ) {
					return '#';
				}
				return $this->get_favorites_url();
			case 'my_ads':
				return $this->get_my_ads_url();
			case 'messages':
				return $this->get_messages_url();
			case 'post-ad':
				if ( ! is_user_logged_in() ) {
					return $this->get_post_ad_guest_href();
				}
				return $this->get_post_ad_url();
			case 'filters':
				return 'javascript:void(0);';
			default:
				return '';
		}
	}

	private function get_icon_svg( $icon, $label ) {
		$icons = array(
			'home'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l9 8h-3v10h-5v-6H11v6H6V11H3l9-8z"/></svg>',
			'search'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 015.96 12.05l4.24 4.24-1.41 1.41-4.24-4.24A7.5 7.5 0 1110.5 3zm0 2a5.5 5.5 0 100 11 5.5 5.5 0 000-11z"/></svg>',
			'filters'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v2H4V7zm3 5h10v2H7v-2zm3 5h4v2h-4v-2z"/></svg>',
			'heart'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21l-1.35-1.23C5.4 15 2 11.92 2 8.16 2 5.08 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09A5.98 5.98 0 0116.5 3C19.58 3 22 5.08 22 8.16c0 3.76-3.4 6.84-8.65 11.61L12 21z"/></svg>',
			'plus'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5z"/></svg>',
			'user'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v2h16v-2c0-2.76-3.58-5-8-5z"/></svg>',
			'category'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3V3zm10 0h8v5h-8V3zM3 13h5v8H3v-8zm7 0h11v8H10v-8z"/></svg>',
			'chat'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16a2 2 0 012 2v9a2 2 0 01-2 2H8l-5 4v-4H4a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>',
			'phone'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 01.99-.24c1.08.36 2.24.56 3.43.56a1 1 0 011 1V20a1 1 0 01-1 1C10.3 21 3 13.7 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.19.19 2.35.56 3.43a1 1 0 01-.25 1z"/></svg>',
			'email'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6zm2 .5v.38l7 5.25 7-5.25V6.5H5zm14 2.88l-6.4 4.8a1 1 0 01-1.2 0L5 9.38V18h14V9.38z"/></svg>',
			'whatsapp'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.05 4.94A9.9 9.9 0 0012.02 2a10 10 0 00-8.66 15l-1.3 4.75 4.87-1.28A10 10 0 1019.05 4.94zm-7.03 15.37a8.3 8.3 0 01-4.22-1.15l-.3-.18-2.88.76.77-2.81-.2-.3a8.3 8.3 0 1110.83 2.37 8.23 8.23 0 01-4 1.31zm4.54-6.2c-.25-.13-1.47-.72-1.7-.8-.23-.08-.39-.13-.56.13-.17.25-.64.8-.79.97-.15.17-.29.19-.54.06a6.7 6.7 0 01-1.97-1.21 7.4 7.4 0 01-1.36-1.7c-.14-.25-.02-.38.1-.51.11-.11.25-.29.37-.43.12-.14.16-.24.25-.4.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.76 2.68 4.27 3.76.6.26 1.08.42 1.45.53.61.2 1.16.17 1.59.1.49-.07 1.47-.6 1.67-1.18.21-.58.21-1.08.14-1.18-.06-.1-.23-.16-.48-.29z"/></svg>',
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
			if ( $this->is_search_nav_item( $item ) ) {
				continue;
			}

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
					'label'   => 'فیلتر',
					'icon'    => 'filters',
					'url'     => '#',
					'dynamic' => 'filters',
					'badge'   => '',
				),
				array(
					'label'   => 'ثبت آگهی',
					'icon'    => 'plus',
					'url'     => $this->get_post_ad_url(),
					'dynamic' => 'post-ad',
					'badge'   => '',
				),
				array(
					'label'   => 'علاقه‌مندی‌ها',
					'icon'    => 'heart',
					'url'     => $this->get_favorites_url(),
					'dynamic' => 'favorites',
					'badge'   => '',
				),
				array(
					'label'   => 'ورود',
					'icon'    => 'user',
					'url'     => function_exists( 'bornado_auth_modal_fallback_url' ) ? bornado_auth_modal_fallback_url( 'login' ) : home_url( '/' ),
					'dynamic' => 'auth',
					'badge'   => '',
				),
			),
		);
	}

	private function is_mobile_filters_available() {
		if ( is_admin() || is_feed() || wp_is_json_request() ) {
			return false;
		}

		global $adforest_theme;

		$search_design = isset( $adforest_theme['search_design'] ) ? (string) $adforest_theme['search_design'] : '';
		$mobile_filters_enabled = ! empty( $adforest_theme['search_design_sidebar_mob_filter'] );
		if ( 'sidebar' !== $search_design || ! $mobile_filters_enabled ) {
			return false;
		}

		if ( function_exists( 'bornado_is_ad_search_view' ) ) {
			return (bool) bornado_is_ad_search_view();
		}

		$search_page_id = isset( $adforest_theme['sb_search_page'] ) ? (int) apply_filters( 'adforest_language_page_id', $adforest_theme['sb_search_page'] ) : 0;

		return is_page_template( 'page-search.php' )
			|| ( $search_page_id && is_page( $search_page_id ) )
			|| is_tax( 'ad_cats' )
			|| is_tax( 'ad_country' );
	}

	private function get_settings() {
		$options = wp_parse_args( get_option( self::OPTION_KEY, array() ), $this->defaults() );
		$options['items'] = $this->filter_nav_items( $options['items'] ?? array() );

		return $options;
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
									<?php foreach ( array( 'home', 'search', 'filters', 'heart', 'plus', 'user', 'category', 'chat' ) as $icon ) : ?>
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
									<option value="filters" <?php selected( 'filters', $item['dynamic'] ); ?>><?php esc_html_e( 'Filters', 'adf-mobile-bottom-nav' ); ?></option>
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

if ( ! function_exists( 'bornado_render_shared_ad_contact_methods' ) ) {
	function bornado_render_shared_ad_contact_methods( $post_id = 0, $args = array() ) {
		if ( ! class_exists( 'ADF_Mobile_Bottom_Nav' ) ) {
			return '';
		}

		return ADF_Mobile_Bottom_Nav::instance()->render_single_ad_contact_nav( (int) $post_id, (array) $args );
	}
}
