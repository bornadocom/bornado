<?php
/**
 * Front-end performance optimizations applied from the child theme layer.
 *
 * The goal is to reduce mobile Lighthouse regressions without modifying the
 * AdForest parent theme files directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_disable_frontend_emoji_assets' ) ) {
	/**
	 * Remove WordPress emoji assets from the public site.
	 *
	 * @return void
	 */
	function bornado_disable_frontend_emoji_assets() {
		if ( is_admin() ) {
			return;
		}

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter(
			'tiny_mce_plugins',
			static function ( $plugins ) {
				if ( ! is_array( $plugins ) ) {
					return array();
				}

				return array_diff( $plugins, array( 'wpemoji' ) );
			}
		);
		add_filter( 'emoji_svg_url', '__return_false' );
	}
}
add_action( 'init', 'bornado_disable_frontend_emoji_assets', 1 );

if ( ! function_exists( 'bornado_is_header_search_clone_active' ) ) {
	/**
	 * Whether the current page is using the child-theme header search clone.
	 *
	 * @return bool
	 */
	function bornado_is_header_search_clone_active() {
		if ( ! defined( 'BORNADO_HEADER_SEARCH_4_CLONE_KEY' ) || ! function_exists( 'bornado_header_clone_get_page_header_style' ) ) {
			return false;
		}

		return (string) bornado_header_clone_get_page_header_style() === (string) BORNADO_HEADER_SEARCH_4_CLONE_KEY;
	}
}

if ( ! function_exists( 'bornado_should_keep_location_widget_assets' ) ) {
	/**
	 * Keep location/search widget assets only where the UI can actually appear.
	 *
	 * @return bool
	 */
	function bornado_should_keep_location_widget_assets() {
		return ( function_exists( 'bornado_is_ad_search_view' ) && bornado_is_ad_search_view() )
			|| bornado_is_header_search_clone_active();
	}
}

if ( ! function_exists( 'bornado_search_layout_uses_map' ) ) {
	/**
	 * Whether the active search layout is the dedicated map layout.
	 *
	 * @return bool
	 */
	function bornado_search_layout_uses_map() {
		global $adforest_theme;

		$search_design = isset( $adforest_theme['search_design'] ) ? (string) $adforest_theme['search_design'] : '';

		return 'map' === $search_design;
	}
}

if ( ! function_exists( 'bornado_dequeue_map_assets_when_not_needed' ) ) {
	/**
	 * Remove Google Maps assets from non-map search views.
	 *
	 * @return void
	 */
	function bornado_dequeue_map_assets_when_not_needed() {
		if ( ! function_exists( 'bornado_is_ad_search_view' ) || ! bornado_is_ad_search_view() || bornado_search_layout_uses_map() ) {
			return;
		}

		foreach ( array( 'google-map-callback', 'google-map', 'marker-clusterer' ) as $handle ) {
			if ( wp_script_is( $handle, 'enqueued' ) ) {
				wp_dequeue_script( $handle );
			}
		}
	}
}

if ( ! function_exists( 'bornado_apply_frontend_performance_overrides' ) ) {
	/**
	 * Narrow broad asset loading from the child/theme/plugin layer.
	 *
	 * @return void
	 */
	function bornado_apply_frontend_performance_overrides() {
		global $adforest_theme;

		if ( is_admin() || is_feed() || wp_is_json_request() ) {
			return;
		}

		foreach ( array( 'bornado-search-core', 'adf-mobile-bottom-nav', 'mcew-location-search-v2' ) as $defer_handle ) {
			if ( wp_script_is( $defer_handle, 'registered' ) || wp_script_is( $defer_handle, 'enqueued' ) ) {
				wp_script_add_data( $defer_handle, 'strategy', 'defer' );
			}
		}

		if ( wp_script_is( 'adfelementor-jquery', 'registered' ) || wp_script_is( 'adfelementor-jquery', 'enqueued' ) ) {
			wp_dequeue_script( 'adfelementor-jquery' );
			wp_deregister_script( 'adfelementor-jquery' );
		}

		if ( wp_style_is( 'load-fa-latest', 'enqueued' ) && ( wp_style_is( 'adforest-pro-font-awesome', 'registered' ) || wp_style_is( 'adforest-pro-font-awesome', 'enqueued' ) ) ) {
			wp_dequeue_style( 'adforest-pro-font-awesome' );
		}

		if ( function_exists( 'bornado_is_ad_search_view' ) && bornado_is_ad_search_view() ) {
			/*
			 * AdForest enqueues its own jQuery bundle even though the rest of the
			 * page already relies on WordPress' core `jquery` handle. Keeping both
			 * versions active increases parse/execute cost on mobile.
			 */
			if ( wp_script_is( 'adforest-jquery', 'enqueued' ) ) {
				wp_dequeue_script( 'adforest-jquery' );
			}

			if ( wp_script_is( 'adforest-jquery', 'registered' ) ) {
				wp_deregister_script( 'adforest-jquery' );
			}

			wp_enqueue_script( 'jquery' );

			$jquery_ui_loader = sprintf(
				<<<'JS'
(function(w,$){if(!$||!$.fn){return;}var selector='.dynamic-form-date-fields, #ad_bidding_date';var scriptSrc='%1$s';var styleHref='%2$s';var queue=[];var loading=false;var loaded=(typeof $.fn.datepicker==='function'&&!$.fn.datepicker.__bornadoLazy);function pageHasTargets(){return !!document.querySelector(selector);}function ensureStyle(){if(document.querySelector('link[data-bornado-jqui]')){return;}var link=document.createElement('link');link.rel='stylesheet';link.href=styleHref;link.media='all';link.setAttribute('data-bornado-jqui','1');document.head.appendChild(link);}function replay(){if(typeof $.fn.datepicker!=='function'||$.fn.datepicker.__bornadoLazy){return;}while(queue.length){var item=queue.shift();item.elements.forEach(function(node){if(node&&node.nodeType===1){$.fn.datepicker.apply($(node),item.args);}});}}function ensureAssets(){if(loaded||loading){return;}loading=true;ensureStyle();var script=document.createElement('script');script.src=scriptSrc;script.async=true;script.onload=function(){loaded=true;loading=false;replay();};script.onerror=function(){loading=false;};document.head.appendChild(script);}if(typeof $.fn.datepicker!=='function'){var stub=function(){queue.push({elements:this.toArray(),args:Array.prototype.slice.call(arguments)});if(this.filter(selector).length||pageHasTargets()){ensureAssets();}return this;};stub.__bornadoLazy=true;$.fn.datepicker=stub;}$(function(){if(pageHasTargets()){ensureAssets();}});})(window,window.jQuery);
JS
				,
				esc_js( trailingslashit( get_template_directory_uri() ) . 'assets/js/jquery/jquery.ui.min.js' ),
				esc_js( 'https://code.jquery.com/ui/1.13.3/themes/smoothness/jquery-ui.css' )
			);
			wp_add_inline_script( 'jquery', $jquery_ui_loader, 'after' );

			if ( wp_script_is( 'jquery-ui-min', 'enqueued' ) ) {
				wp_dequeue_script( 'jquery-ui-min' );
			}

			if ( wp_style_is( 'adforest-jquery-ui-css', 'enqueued' ) ) {
				wp_dequeue_style( 'adforest-jquery-ui-css' );
			}

			$has_social_login = ! empty( $adforest_theme['fb_api_key'] ) || ! empty( $adforest_theme['gmail_api_key'] );
			if ( ! $has_social_login && wp_script_is( 'hello', 'enqueued' ) ) {
				wp_dequeue_script( 'hello' );
			}
		}

		if ( ! bornado_should_keep_location_widget_assets() ) {
			foreach ( array( 'mcew-bornado-list', 'mcew-location-search-v2' ) as $style_handle ) {
				if ( wp_style_is( $style_handle, 'enqueued' ) ) {
					wp_dequeue_style( $style_handle );
				}
			}

			if ( wp_script_is( 'mcew-location-search-v2', 'enqueued' ) ) {
				wp_dequeue_script( 'mcew-location-search-v2' );
			}
		}

		if ( function_exists( 'bornado_is_ad_search_view' ) && bornado_is_ad_search_view() ) {
			/*
			 * Search pages do not use the blog skin or pretty-checkbox assets.
			 * Owl styles are only needed for the dedicated map layout mini-carousel.
			 */
			$search_css_to_remove = array(
				'adforest-theme-blog',
				'pretty-checkbox',
				'bootstrap-rtl',
			);

			if ( ! bornado_search_layout_uses_map() ) {
				$search_css_to_remove[] = 'owl-carousel-carousel';
				$search_css_to_remove[] = 'owl-theme';
			}

			foreach ( $search_css_to_remove as $style_handle ) {
				if ( wp_style_is( $style_handle, 'enqueued' ) ) {
					wp_dequeue_style( $style_handle );
				}
			}
		}

		$is_ad_post_page = function_exists( 'bornado_is_ad_post_page' ) && bornado_is_ad_post_page();
		$is_inline_edit  = function_exists( 'bornado_inline_edit_is_active' ) && bornado_inline_edit_is_active();

		/*
		 * The inline single-ad editor renders AdForest's ad-post form on a normal
		 * single-ad request, so it still needs the editor/date/tags assets even
		 * though it is not the dedicated post-ad page template.
		 */
		if ( ! $is_ad_post_page && ! $is_inline_edit ) {
			foreach ( array( 'jquery-tagsinput', 'jquery-te', 'adforest-dt' ) as $style_handle ) {
				if ( wp_style_is( $style_handle, 'enqueued' ) ) {
					wp_dequeue_style( $style_handle );
				}
			}

			foreach ( array( 'tagsinput', 'jquery-te', 'adforest-dt' ) as $script_handle ) {
				if ( wp_script_is( $script_handle, 'enqueued' ) ) {
					wp_dequeue_script( $script_handle );
				}
			}
		}

		bornado_dequeue_map_assets_when_not_needed();
	}
}

if ( ! function_exists( 'bornado_should_defer_search_stylesheet' ) ) {
	/**
	 * Decide whether a stylesheet can be loaded asynchronously on search pages.
	 *
	 * @param string $handle Stylesheet handle.
	 * @param string $href   Stylesheet source URL.
	 * @return bool
	 */
	function bornado_should_defer_search_stylesheet( $handle, $href ) {
		$deferred_handles = array(
			'google-fonts-poppins',
			'adforest-google_fonts',
			'load-fa-latest',
			'adforest-jquery-ui-css',
		);

		if ( in_array( $handle, $deferred_handles, true ) ) {
			return true;
		}

		return false !== strpos( $href, 'fonts.googleapis.com/' );
	}
}

if ( ! function_exists( 'bornado_defer_search_noncritical_styles' ) ) {
	/**
	 * Let search pages paint before non-critical remote stylesheets finish loading.
	 *
	 * @param string $html   Stylesheet HTML.
	 * @param string $handle Stylesheet handle.
	 * @param string $href   Stylesheet source URL.
	 * @param string $media  Intended media attribute.
	 * @return string
	 */
	function bornado_defer_search_noncritical_styles( $html, $handle, $href, $media ) {
		if ( is_admin() || ! function_exists( 'bornado_is_ad_search_view' ) || ! bornado_is_ad_search_view() ) {
			return $html;
		}

		if ( ! bornado_should_defer_search_stylesheet( $handle, $href ) ) {
			return $html;
		}

		$media = is_string( $media ) && '' !== $media ? $media : 'all';

		return sprintf(
			'<link rel="stylesheet" id="%1$s-css" href="%2$s" media="print" onload="this.media=\'%3$s\'" /><noscript><link rel="stylesheet" id="%1$s-css-noscript" href="%2$s" media="%3$s" /></noscript>',
			esc_attr( $handle ),
			esc_url( $href ),
			esc_attr( $media )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_apply_frontend_performance_overrides', 220 );
add_action( 'wp_print_footer_scripts', 'bornado_dequeue_map_assets_when_not_needed', 1 );
add_filter( 'style_loader_tag', 'bornado_defer_search_noncritical_styles', 20, 4 );
