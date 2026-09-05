<?php
/**
 * DOM windowing controller for AdForest listing pages.
 *
 * Keeps the theme's native infinite-scroll / AJAX loading behavior intact
 * while bounding retained DOM from the child theme layer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_should_enable_windowed_infinite_scroll' ) ) {
	/**
	 * Enable the windowed listing controller on public Ad search views.
	 *
	 * @return bool
	 */
	function bornado_should_enable_windowed_infinite_scroll() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_json_request() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( ! function_exists( 'bornado_is_ad_search_view' ) || ! bornado_is_ad_search_view() ) {
			return false;
		}

		return (bool) apply_filters( 'bornado_windowed_infinite_scroll_is_enabled', true );
	}
}

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( bornado_should_enable_windowed_infinite_scroll() ) {
			$classes[] = 'bornado-windowed-infinite-scroll';
		}

		return $classes;
	},
	30
);

if ( ! function_exists( 'bornado_enqueue_windowed_infinite_scroll_assets' ) ) {
	/**
	 * Enqueue the child-theme DOM windowing controller and expose guardrails.
	 *
	 * @return void
	 */
	function bornado_enqueue_windowed_infinite_scroll_assets() {
		if ( ! bornado_should_enable_windowed_infinite_scroll() ) {
			return;
		}

		$asset_path = trailingslashit( get_stylesheet_directory() ) . 'assets/js/bornado-search-dom-windowing.js';
		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$dependencies = array( 'jquery' );
		foreach ( array( 'adforest-search-ajax', 'adforest-search-ux', 'adforest-custom', 'bornado-search-core' ) as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) ) {
				$dependencies[] = $handle;
			}
		}

		wp_enqueue_script(
			'bornado-search-windowed-infinite-scroll',
			trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bornado-search-dom-windowing.js',
			array_values( array_unique( $dependencies ) ),
			(string) filemtime( $asset_path ),
			true
		);

		wp_localize_script(
			'bornado-search-windowed-infinite-scroll',
			'BornadoWindowedInfiniteScrollConfig',
			array(
				'enabled'             => true,
				'debug'               => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'resultsSelector'     => '#adforest-ajax-results',
				'containerSelectors'  => array(
					'.adt-search-ads-grid',
					'.adt-search-ads-list',
					'.search-ads-result-box',
				),
				'paginationSelectors' => array(
					'.pagination.adt-custom-pagination',
					'.pagination.pagination-lg',
					'.pagination.pagination-large',
					'.pagination',
				),
				'cardSelectors'       => array(
					'.adf-card-item',
					'.adt-category-ad-list',
					'.adt-car-dealer-card',
				),
				'windowing'           => array(
					'keepBeforeCards' => 24,
					'keepAfterCards'  => 24,
					'preloadThresholdPx' => 2500,
				),
				'strings'             => array(
					'loading' => __( 'Loading more results...', 'adforest' ),
					'end'     => __( 'All available result pages have been loaded.', 'adforest' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_windowed_infinite_scroll_assets', 215 );
