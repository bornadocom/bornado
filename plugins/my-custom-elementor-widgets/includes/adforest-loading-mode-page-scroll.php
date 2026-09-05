<?php
/**
 * Adds a custom AdForest loading mode:
 * "Infinity Scroll (Page)" without editing theme core files.
 *
 * @package My_Custom_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MCEW_PAGE_SCROLL_MODE_FLAG_OPTION = 'mcew_adforest_loading_page_scroll_enabled';

/**
 * Allow integrations to intentionally disable the page-scroll runtime.
 *
 * Intentionally NOT tied to Bornado DOM windowing. An earlier mismatched filter
 * name (`bornado_windowed_infinite_scroll_enabled`) never fired; wiring it to
 * Bornado's real `*_is_enabled` filter would disable page loading while
 * windowing stayed on, breaking infinite scroll.
 *
 * @return bool
 */
function mcew_should_skip_page_scroll_runtime() {
	return (bool) apply_filters( 'mcew_disable_page_scroll_runtime', false );
}

/**
 * Whether the current request is an Ad Search / listing surface.
 *
 * Prefer Bornado's detector; fall back to search layout markers so Elementor
 * homepage widgets with a colliding load-more button ID are skipped.
 *
 * @return bool
 */
function mcew_is_ad_search_listing_view() {
	if ( function_exists( 'bornado_is_ad_search_view' ) ) {
		return (bool) bornado_is_ad_search_view();
	}

	if ( is_page_template( 'page-search.php' ) ) {
		return true;
	}

	return (bool) apply_filters( 'mcew_is_ad_search_listing_view', false );
}

/**
 * Preload distance in pixels before the results bottom.
 *
 * @return int
 */
function mcew_get_page_scroll_preload_threshold_px() {
	$threshold = (int) apply_filters( 'mcew_page_scroll_preload_threshold_px', 2500 );
	return max( 600, $threshold );
}

/**
 * Whether the dedicated page-scroll mode flag is active.
 *
 * @return bool
 */
function mcew_is_page_scroll_mode_flag_enabled() {
	$custom_flag = (string) get_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0' );
	if ( '1' === $custom_flag ) {
		return true;
	}

	$theme_opts = get_option( 'adforest_theme', array() );
	if ( is_array( $theme_opts ) && isset( $theme_opts['mcew_loading_mode_page_scroll_active'] ) && '1' === (string) $theme_opts['mcew_loading_mode_page_scroll_active'] ) {
		return true;
	}

	return false;
}

/**
 * Read current loading mode, honoring dedicated custom flag first.
 *
 * @return string
 */
function mcew_get_raw_loading_mode_value() {
	$custom_flag = (string) get_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0' );
	if ( '1' === $custom_flag ) {
		return 'infinity_scroll_page';
	}

	$theme_opts = get_option( 'adforest_theme', array() );
	if ( ! is_array( $theme_opts ) ) {
		return '';
	}

	if ( isset( $theme_opts['mcew_loading_mode_page_scroll_active'] ) && '1' === (string) $theme_opts['mcew_loading_mode_page_scroll_active'] ) {
		return 'infinity_scroll_page';
	}

	return isset( $theme_opts['loading_ads_mode'] ) ? (string) $theme_opts['loading_ads_mode'] : '';
}

/**
 * Parse Redux AJAX serialized data payload.
 *
 * @return array<string,mixed>
 */
function mcew_parse_redux_serialized_theme_data() {
	$out = array();
	if ( ! isset( $_POST['data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $out;
	}

	$raw = wp_unslash( $_POST['data'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! is_string( $raw ) || '' === $raw ) {
		return $out;
	}

	parse_str( $raw, $parsed );
	if ( is_array( $parsed ) ) {
		$out = $parsed;
	}

	return $out;
}

/**
 * Persist custom loading mode selection in dedicated option.
 *
 * @return void
 */
function mcew_capture_page_scroll_mode_from_theme_submit() {
	if ( ! is_admin() ) {
		return;
	}

	$posted_flag = isset( $_POST[ MCEW_PAGE_SCROLL_MODE_FLAG_OPTION ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? sanitize_text_field( wp_unslash( $_POST[ MCEW_PAGE_SCROLL_MODE_FLAG_OPTION ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: '';

	if ( '1' === $posted_flag ) {
		update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '1', false );
		return;
	}
	if ( '0' === $posted_flag ) {
		update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0', false );
		return;
	}

	$redux_payload = mcew_parse_redux_serialized_theme_data();
	if ( isset( $redux_payload[ MCEW_PAGE_SCROLL_MODE_FLAG_OPTION ] ) ) {
		$redux_flag = sanitize_text_field( (string) $redux_payload[ MCEW_PAGE_SCROLL_MODE_FLAG_OPTION ] );
		update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, ( '1' === $redux_flag ? '1' : '0' ), false );
		return;
	}

	$posted_mode = '';
	if ( isset( $_POST['adforest_theme'] ) && is_array( $_POST['adforest_theme'] ) && isset( $_POST['adforest_theme']['loading_ads_mode'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_mode = sanitize_text_field( wp_unslash( $_POST['adforest_theme']['loading_ads_mode'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['adforest_theme']['mcew_loading_mode_page_scroll_active'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted_inside = sanitize_text_field( wp_unslash( $_POST['adforest_theme']['mcew_loading_mode_page_scroll_active'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, ( '1' === $posted_inside ? '1' : '0' ), false );
			return;
		}
	} elseif ( isset( $_POST['loading_ads_mode'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_mode = sanitize_text_field( wp_unslash( $_POST['loading_ads_mode'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	} elseif ( isset( $redux_payload['adforest_theme'] ) && is_array( $redux_payload['adforest_theme'] ) && isset( $redux_payload['adforest_theme']['loading_ads_mode'] ) ) {
		$posted_mode = sanitize_text_field( (string) $redux_payload['adforest_theme']['loading_ads_mode'] );
		if ( isset( $redux_payload['adforest_theme']['mcew_loading_mode_page_scroll_active'] ) ) {
			$redux_inside = sanitize_text_field( (string) $redux_payload['adforest_theme']['mcew_loading_mode_page_scroll_active'] );
			update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, ( '1' === $redux_inside ? '1' : '0' ), false );
			return;
		}
	}

	if ( 'infinity_scroll_page' === $posted_mode ) {
		update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '1', false );
		return;
	}

	if ( in_array( $posted_mode, array( 'pagination', 'show_more', 'infinity_scroll' ), true ) ) {
		update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0', false );
	}
}
add_action( 'admin_init', 'mcew_capture_page_scroll_mode_from_theme_submit', 5 );
add_action( 'wp_ajax_redux_ajax_save', 'mcew_capture_page_scroll_mode_from_theme_submit', 1 );

/**
 * Keep flag synced on direct adforest_theme option updates.
 *
 * @param array<string,mixed> $old_value Previous value.
 * @param array<string,mixed> $value     New value.
 * @return void
 */
function mcew_sync_page_scroll_flag_on_adforest_theme_update( $old_value, $value ) {
	if ( ! is_array( $value ) || ! isset( $value['loading_ads_mode'] ) ) {
		return;
	}

	$mode = (string) $value['loading_ads_mode'];
	if ( 'infinity_scroll_page' === $mode ) {
		update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '1', false );
	} elseif ( in_array( $mode, array( 'pagination', 'show_more', 'infinity_scroll' ), true ) ) {
		update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0', false );
	}
}
add_action( 'update_option_adforest_theme', 'mcew_sync_page_scroll_flag_on_adforest_theme_update', 10, 2 );

/**
 * AJAX endpoint to persist custom loading mode flag.
 *
 * @return void
 */
function mcew_ajax_set_page_scroll_loading_mode_flag() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'mcew_page_scroll_mode_nonce', 'nonce' );

	$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$flag  = ( '1' === $value ) ? '1' : '0';
	update_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, $flag, false );

	wp_send_json_success( array( 'flag' => $flag ) );
}
add_action( 'wp_ajax_mcew_set_page_scroll_loading_mode_flag', 'mcew_ajax_set_page_scroll_loading_mode_flag' );

/**
 * Frontend bridge:
 * when custom mode is active, use show_more engine + auto-trigger via window scroll.
 *
 * @param mixed $option_value Option payload.
 * @return mixed
 */
function mcew_bridge_loading_mode_option_for_frontend( $option_value ) {
	if ( ! is_array( $option_value ) ) {
		return $option_value;
	}

	if ( mcew_should_skip_page_scroll_runtime() ) {
		return $option_value;
	}

	// Keep the option bridge scoped to listing views so Elementor homepage
	// widgets are not forced into show_more / page-scroll behavior.
	if ( ! is_admin() && ! wp_doing_ajax() && ! mcew_is_ad_search_listing_view() ) {
		return $option_value;
	}

	$array_flag  = isset( $option_value['mcew_loading_mode_page_scroll_active'] ) ? (string) $option_value['mcew_loading_mode_page_scroll_active'] : '0';
	$custom_flag = (string) get_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0' );

	if ( '1' !== $custom_flag && '1' !== $array_flag ) {
		$current_mode = isset( $option_value['loading_ads_mode'] ) ? (string) $option_value['loading_ads_mode'] : '';
		if ( 'infinity_scroll_page' !== $current_mode ) {
			return $option_value;
		}
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $option_value;
	}

	$option_value['loading_ads_mode']                       = 'show_more';
	$option_value['mcew_loading_mode_page_scroll_active']   = '1';
	$option_value['mcew_loading_mode_original_setting']     = 'infinity_scroll_page';

	return $option_value;
}
add_filter( 'option_adforest_theme', 'mcew_bridge_loading_mode_option_for_frontend', 25 );

/**
 * Add body class for safe scoped CSS and JS.
 *
 * @param string[] $classes Existing classes.
 * @return string[]
 */
function mcew_add_page_scroll_loading_body_class( $classes ) {
	if ( mcew_should_skip_page_scroll_runtime() ) {
		return $classes;
	}

	if ( ! mcew_is_page_scroll_mode_flag_enabled() ) {
		return $classes;
	}

	if ( ! mcew_is_ad_search_listing_view() ) {
		return $classes;
	}

	$classes[] = 'mcew-loading-mode-page-scroll';
	return $classes;
}
add_filter( 'body_class', 'mcew_add_page_scroll_loading_body_class' );

/**
 * Inject custom loading mode in Redux button_set UI.
 *
 * @return void
 */
function mcew_inject_loading_mode_page_scroll_in_options_ui() {
	if ( ! is_admin() ) {
		return;
	}
	?>
	<script>
	(function () {
		var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var ajaxNonce = <?php echo wp_json_encode( wp_create_nonce( 'mcew_page_scroll_mode_nonce' ) ); ?>;

		function persistFlag(enabled) {
			if (!ajaxUrl || !window.fetch) return;
			var params = new URLSearchParams();
			params.append('action', 'mcew_set_page_scroll_loading_mode_flag');
			params.append('nonce', ajaxNonce);
			params.append('value', enabled ? '1' : '0');
			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})["catch"](function(){});
		}

		function findFieldset() {
			return document.getElementById('adforest_theme-loading_ads_mode')
				|| document.querySelector('#adforest_theme-loading_ads_mode')
				|| document.querySelector('fieldset[id*="loading_ads_mode"]');
		}

		function injectOption() {
			var fieldset = findFieldset();
			if (!fieldset) return;

			var buttonset = fieldset.querySelector('.buttonset');
			if (!buttonset) return;

			var hiddenFlag = fieldset.querySelector('input[name="<?php echo esc_js( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION ); ?>"]');
			if (!hiddenFlag) {
				hiddenFlag = document.createElement('input');
				hiddenFlag.type = 'hidden';
				hiddenFlag.name = '<?php echo esc_js( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION ); ?>';
				hiddenFlag.value = '0';
				fieldset.appendChild(hiddenFlag);
			}

			var hiddenReduxFlag = fieldset.querySelector('input[name="adforest_theme[mcew_loading_mode_page_scroll_active]"]');
			if (!hiddenReduxFlag) {
				hiddenReduxFlag = document.createElement('input');
				hiddenReduxFlag.type = 'hidden';
				hiddenReduxFlag.name = 'adforest_theme[mcew_loading_mode_page_scroll_active]';
				hiddenReduxFlag.value = '0';
				fieldset.appendChild(hiddenReduxFlag);
			}

			if (fieldset.querySelector('#loading_ads_mode-buttonset-page-scroll')) {
				var existing = fieldset.querySelector('#loading_ads_mode-buttonset-page-scroll');
				if (existing && existing.checked) {
					hiddenFlag.value = '1';
					hiddenReduxFlag.value = '1';
				}
				return;
			}

			var input = document.createElement('input');
			input.type = 'radio';
			input.id = 'loading_ads_mode-buttonset-page-scroll';
			input.name = 'adforest_theme[loading_ads_mode]';
			input.value = 'infinity_scroll_page';
			input.className = 'buttonset-item ui-checkboxradio ui-helper-hidden-accessible';
			input.setAttribute('data-id', 'loading_ads_mode');

			var label = document.createElement('label');
			label.setAttribute('for', 'loading_ads_mode-buttonset-page-scroll');
			label.className = 'ui-button ui-widget ui-checkboxradio-radio-label ui-controlgroup-item ui-checkboxradio-label ui-corner-right';
			label.innerHTML = '<span class="ui-checkboxradio-icon ui-corner-all ui-icon ui-icon-background ui-icon-blank"></span><span class="ui-checkboxradio-icon-space"> </span>Infinity Scroll (Page)';

			var labels = buttonset.querySelectorAll('label');
			if (labels.length) {
				labels[labels.length - 1].classList.remove('ui-corner-right');
			}

			buttonset.appendChild(input);
			buttonset.appendChild(label);

			var saved = <?php echo wp_json_encode( mcew_get_raw_loading_mode_value() ); ?>;
			if (saved === 'infinity_scroll_page') {
				input.checked = true;
				hiddenFlag.value = '1';
				hiddenReduxFlag.value = '1';
				label.classList.add('ui-checkboxradio-checked', 'ui-state-active');
				var allInputs = buttonset.querySelectorAll('input[type="radio"][name="adforest_theme[loading_ads_mode]"]');
				allInputs.forEach(function (el) {
					if (el !== input) el.checked = false;
				});
				var allLabels = buttonset.querySelectorAll('label');
				allLabels.forEach(function (el) {
					if (el !== label) el.classList.remove('ui-checkboxradio-checked', 'ui-state-active');
				});
			}

			buttonset.addEventListener('change', function (event) {
				var target = event && event.target ? event.target : null;
				if (!target || target.name !== 'adforest_theme[loading_ads_mode]') return;
				var enabled = target.value === 'infinity_scroll_page';
				hiddenFlag.value = enabled ? '1' : '0';
				hiddenReduxFlag.value = enabled ? '1' : '0';
				persistFlag(enabled);
			});

			if (window.jQuery && window.jQuery.fn.checkboxradio) {
				window.jQuery(buttonset).find('input').checkboxradio('refresh');
			}
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', injectOption);
		} else {
			injectOption();
		}
	})();
	</script>
	<?php
}
add_action( 'admin_footer', 'mcew_inject_loading_mode_page_scroll_in_options_ui', 99 );

/**
 * Print frontend CSS/JS for page-based infinite loading behavior.
 *
 * @return void
 */
function mcew_page_scroll_loading_frontend_enhancer() {
	if ( is_admin() ) {
		return;
	}

	if ( mcew_should_skip_page_scroll_runtime() ) {
		return;
	}

	if ( ! mcew_is_page_scroll_mode_flag_enabled() ) {
		return;
	}

	if ( ! mcew_is_ad_search_listing_view() ) {
		return;
	}

	$preload_threshold_px = mcew_get_page_scroll_preload_threshold_px();
	?>
	<style>
		body.mcew-loading-mode-page-scroll .load-more-btn-box {
			display: none !important;
		}
		body.mcew-loading-mode-page-scroll #sb_loading,
		body.mcew-loading-mode-page-scroll .loading,
		body.mcew-loading-mode-page-scroll .loader,
		body.mcew-loading-mode-page-scroll .spinner,
		body.mcew-loading-mode-page-scroll .loading-spinner {
			opacity: 0 !important;
			visibility: hidden !important;
			pointer-events: none !important;
		}
		body.mcew-loading-mode-page-scroll #no_more_ads_p {
			opacity: 0 !important;
			visibility: hidden !important;
			pointer-events: none !important;
		}
		body.mcew-loading-mode-page-scroll .adt-map-search-section .map-search-wrapper .search-content-side.scroller {
			height: auto !important;
			max-height: none !important;
			overflow: visible !important;
		}
		body.mcew-loading-mode-page-scroll .adt-map-search-section .adt-search-ads-list,
		body.mcew-loading-mode-page-scroll .adt-map-search-section .adt-search-ads-grid {
			height: auto !important;
			max-height: none !important;
			overflow: visible !important;
		}
	</style>
	<script>
	(function () {
		var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var configuredThresholdPx = <?php echo (int) $preload_threshold_px; ?>;
		var cardSelectors = [
			'.adf-card-item',
			'.adt-category-ad-list',
			'.adt-car-dealer-card'
		];
		var triggerLock = false;
		var requestInFlight = false;
		var lastKnownItemsCount = 0;
		var itemsAtRequestStart = 0;
		var lastTriggerAt = 0;
		var requestGeneration = 0;
		var abortController = null;
		var debounceTimer = null;
		var ticking = false;
		var authFailed = false;

		function hideLoadingUi() {
			var ids = ['sb_loading', 'no_more_ads_p'];
			ids.forEach(function (id) {
				var el = document.getElementById(id);
				if (el) {
					el.style.visibility = 'hidden';
					el.style.opacity = '0';
					el.style.pointerEvents = 'none';
				}
			});
		}

		function getLoadButton() {
			var results = document.getElementById('adforest-ajax-results');
			if (results) {
				var scoped = results.querySelector('#load-more-ads-btn');
				if (scoped) {
					return scoped;
				}
			}
			var mapBox = document.querySelector('.search-ads-result-box, .adt-map-search-section');
			if (mapBox) {
				var mapBtn = mapBox.querySelector('#load-more-ads-btn') || document.getElementById('load-more-ads-btn');
				if (mapBtn && mapBtn.closest('.adt-map-search-section, .search-ads-result-box, .adt-ads-with-filters, .adt-ads-topbar-section')) {
					return mapBtn;
				}
			}
			var btn = document.getElementById('load-more-ads-btn');
			if (!btn) {
				return null;
			}
			if (btn.closest('.elementor-widget, .elementor-section') && !btn.closest('#adforest-ajax-results, .adt-ads-with-filters, .adt-ads-topbar-section, .adt-map-search-section')) {
				return null;
			}
			return btn;
		}

		function getNonceValue() {
			var nonceField = document.getElementById('sb_load_more_ads_nonce');
			return nonceField ? (nonceField.value || '') : '';
		}

		function parseIntAttr(el, name, fallback) {
			if (!el) {
				return fallback;
			}
			var raw = el.getAttribute(name);
			var n = parseInt(raw, 10);
			return isNaN(n) ? fallback : n;
		}

		function getButtonConfig(button) {
			if (!button) return null;
			var jq = window.jQuery ? window.jQuery(button) : null;
			var searchQuery = jq ? jq.data('search-query') : null;
			if (!searchQuery) {
				var rawSearchQuery = button.getAttribute('data-search-query') || '';
				if (rawSearchQuery) {
					try {
						searchQuery = JSON.parse(rawSearchQuery);
					} catch (e) {
						searchQuery = rawSearchQuery;
					}
				}
			}

			return {
				loadingMode: (button.getAttribute('data-loading-mode') || '').trim(),
				searchQuery: searchQuery,
				searchPageType: (button.getAttribute('data-search-page') || '').trim(),
				viewType: (button.getAttribute('data-view-type') || '').trim(),
				paged: parseInt(button.dataset.mcewCurrentPage || '2', 10) || 2,
				adCount: parseIntAttr(button, 'data-ad-count', 0),
				postsPerPage: parseIntAttr(button, 'data-posts-per-page', 0),
				nonce: getNonceValue(),
				searchAjaxNonce: window.adforestAjaxSearch && window.adforestAjaxSearch.nonce ? window.adforestAjaxSearch.nonce : '',
				searchAjaxUrl: window.adforestAjaxSearch && window.adforestAjaxSearch.ajaxUrl ? window.adforestAjaxSearch.ajaxUrl : ajaxUrl
			};
		}

		function getAppendTarget(config) {
			if (config && config.searchPageType === 'map') {
				return document.querySelector('.search-ads-result-box');
			}
			if (config && config.viewType === 'list') {
				return document.querySelector('.adt-search-ads-list') || document.querySelector('.adt-search-ads-grid');
			}
			return document.querySelector('.adt-search-ads-grid') || document.querySelector('.search-ads-result-box') || document.querySelector('.adt-search-ads-list');
		}

		function getResultsAnchor() {
			return document.getElementById('adforest-ajax-results')
				|| document.querySelector('.adt-search-ads-grid')
				|| document.querySelector('.adt-search-ads-list')
				|| document.querySelector('.search-ads-result-box');
		}

		function getPreloadThresholdPx() {
			return Math.max(configuredThresholdPx, Math.round(window.innerHeight * 2.5));
		}

		function isNearResultsBottom() {
			var anchor = getResultsAnchor();
			var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
			var viewportBottom = scrollTop + window.innerHeight;
			var threshold = getPreloadThresholdPx();
			var bottomEdge;

			if (anchor && typeof anchor.getBoundingClientRect === 'function') {
				var rect = anchor.getBoundingClientRect();
				bottomEdge = scrollTop + rect.bottom;
			} else {
				bottomEdge = Math.max(
					document.body.scrollHeight,
					document.documentElement.scrollHeight
				);
			}

			return viewportBottom >= (bottomEdge - threshold);
		}

		function setButtonIdle(button) {
			if (!button) return;
			button.disabled = false;
			button.textContent = 'Show More';
		}

		function setButtonLoading(button) {
			if (!button) return;
			button.disabled = true;
			button.textContent = 'Loading...';
		}

		function clearNoMoreAds() {
			var el = document.getElementById('no_more_ads_p');
			if (!el) return;
			el.innerHTML = '';
			el.style.visibility = 'hidden';
			el.style.opacity = '0';
			el.style.pointerEvents = 'none';
		}

		function showNoMoreAds() {
			var el = document.getElementById('no_more_ads_p');
			if (!el) return;
			el.innerHTML = '<div role="alert" class="alert alert-info alert-dismissible"><i class="fa fa-info-circle"></i> ' + 'آگهی بیشتری وجود ندارد.' + '</div>';
			el.style.visibility = 'visible';
			el.style.opacity = '1';
			el.style.pointerEvents = 'auto';
		}

		function showAuthFailureNotice() {
			var el = document.getElementById('no_more_ads_p');
			if (!el) return;
			el.innerHTML = '<div role="alert" class="alert alert-warning alert-dismissible"><i class="fa fa-exclamation-triangle"></i> جلسه منقضی شده است. صفحه را رفرش کنید.</div>';
			el.style.visibility = 'visible';
			el.style.opacity = '1';
			el.style.pointerEvents = 'auto';
		}

		function getSemanticRouteContext() {
			var coreConfig = window.BornadoSearchCoreConfig || {};
			var ctx = coreConfig.routeContext || {};
			return ctx && ctx.isSemanticRoute ? ctx : null;
		}

		function applySemanticStructuralContext(params) {
			var ctx = getSemanticRouteContext();
			if (!ctx) {
				return;
			}

			// Clean semantic URLs (e.g. /uk/property/, /uk/liverpool/) carry no
			// cat_id/country_id in the address bar, so the AJAX search would
			// return ads from every category/city. Backfill the structural
			// context from the resolved route so infinite scroll stays scoped
			// to the same category and location as the first server-rendered page.
			if (!params.has('country_id') && !params.has('ad_country')) {
				var locationId = Number(ctx.cityId || ctx.countryId || 0);
				if (locationId > 0) {
					params.set('country_id', String(locationId));
				}
			}

			if (!params.has('cat_id') && !params.has('ad_cats')) {
				var categoryId = Number(ctx.categoryId || 0);
				if (categoryId > 0) {
					params.set('cat_id', String(categoryId));
				}
			}
		}

		function buildFiltersRaw(config) {
			var currentQs = '';
			if (window.adforestAjaxSearchApi && typeof window.adforestAjaxSearchApi.collect === 'function') {
				try {
					currentQs = String(window.adforestAjaxSearchApi.collect() || '');
				} catch (e) {
					currentQs = '';
				}
			}
			if (!currentQs) {
				currentQs = String(window.location.search || '').replace(/^\?/, '');
			}
			var params = new URLSearchParams(currentQs);
			params.delete('paged');
			params.delete('page-number');
			applySemanticStructuralContext(params);
			if (config && config.viewType) {
				params.set('view-type', config.viewType);
			}
			if (config && config.paged) {
				params.set('page-number', String(config.paged));
			}
			return params.toString();
		}

		function isCardNode(node) {
			if (!node || node.nodeType !== 1) {
				return false;
			}
			if (node.id === 'load-more-ads-btn' || node.id === 'no_more_ads_p') {
				return false;
			}
			if (node.classList && (node.classList.contains('load-more-btn-box') || node.classList.contains('bornado-wis-spacer'))) {
				return false;
			}
			for (var i = 0; i < cardSelectors.length; i++) {
				if (node.matches && node.matches(cardSelectors[i])) {
					return true;
				}
			}
			return false;
		}

		function collectCardNodes(root) {
			var cards = [];
			if (!root) {
				return cards;
			}
			if (isCardNode(root)) {
				cards.push(root);
				return cards;
			}
			var nodes = root.querySelectorAll(cardSelectors.join(','));
			for (var i = 0; i < nodes.length; i++) {
				cards.push(nodes[i]);
			}
			return cards;
		}

		function extractCardNodesFromHtml(html, config) {
			var temp = document.createElement('div');
			temp.innerHTML = html || '';
			var selector = '.adt-search-ads-grid';
			if (config && config.viewType === 'list') {
				selector = '.adt-search-ads-list';
			} else if (config && config.searchPageType === 'map') {
				selector = '.search-ads-result-box';
			}
			var source = temp.querySelector(selector) || temp;
			return collectCardNodes(source);
		}

		function appendCardNodes(appendTarget, cards) {
			if (!appendTarget || !cards || !cards.length) {
				return 0;
			}
			var added = 0;
			for (var i = 0; i < cards.length; i++) {
				appendTarget.appendChild(cards[i]);
				added++;
			}
			return added;
		}

		function responseHasEmptyState(html) {
			var temp = document.createElement('div');
			temp.innerHTML = html || '';
			if (temp.querySelector('.no_ads_found, .adforest-ajax-empty')) {
				return true;
			}
			return false;
		}

		function disableLoadButton(button) {
			if (!button) return;
			button.disabled = true;
			button.textContent = 'No More Ads';
			button.style.display = 'none';
		}

		function getMobileFilterSidebar() {
			return document.getElementById('adforest-ajax-sidebar');
		}

		function getLegacySidebarWrapper() {
			var sidebar = getMobileFilterSidebar();
			return sidebar ? sidebar.closest('.adt-ads-filter-sidebar, .all-filters-sidebar') : null;
		}

		function getLegacyFilterToggleButtons() {
			return document.querySelectorAll('.mobile-filters-btn a, .search-all-filters, #adf-open-filters');
		}

		function isMobileViewport() {
			return window.innerWidth < 992;
		}

		function syncMobileFilterState(isOpen) {
			var sidebar = getMobileFilterSidebar();
			var wrapper = getLegacySidebarWrapper();
			if (!sidebar) return;
			if (isOpen) {
				document.body.classList.add('adf-mobile-filters-open');
				if (wrapper) {
					wrapper.classList.add('open');
				}
				if (sidebar.classList.contains('mobile-filters')) {
					sidebar.classList.add('active');
				}
			} else {
				document.body.classList.remove('adf-mobile-filters-open');
				if (wrapper) {
					wrapper.classList.remove('open');
				}
				if (sidebar.classList.contains('mobile-filters')) {
					sidebar.classList.remove('active');
				}
			}
			getLegacyFilterToggleButtons().forEach(function (button) {
				if (button.classList) {
					button.classList.toggle('active', !!isOpen);
				}
				if (button.getAttribute && button.getAttribute('aria-expanded') !== null) {
					button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				}
			});
		}

		function ensureMobileFilterCloseButton() {
			var sidebar = getMobileFilterSidebar();
			if (!sidebar) return;
			var heading = sidebar.querySelector('.mobile-filter-heading');
			if (!heading) return;
			if (heading.querySelector('.adf-mobile-filters-close')) return;
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'filter-close-btn adf-mobile-filters-close';
			button.setAttribute('aria-label', 'Close filters');
			button.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
			heading.appendChild(button);
		}

		function bindMobileFilterCompatibility() {
			ensureMobileFilterCloseButton();

			document.addEventListener('click', function (event) {
				var target = event.target;
				if (!target || !target.closest) return;
				var trigger = target.closest('.mobile-filters-btn a, .mobile-filters-btn, .search-all-filters, #adf-open-filters');
				if (trigger && isMobileViewport()) {
					event.preventDefault();
					var shouldOpen = !document.body.classList.contains('adf-mobile-filters-open');
					syncMobileFilterState(shouldOpen);
					return;
				}

				var closer = target.closest('.adf-mobile-filters-close, a.filter-close-btn, .adf-filters-backdrop, .close-sidebar');
				if (closer) {
					event.preventDefault();
					syncMobileFilterState(false);
				}
			});

			window.addEventListener('resize', function () {
				if (!isMobileViewport()) {
					syncMobileFilterState(false);
				}
			});
		}

		function reinitializeNewContent(root) {
			if (!root || !window.jQuery) return;
			var $root = window.jQuery(root);
			if (window.jQuery.fn.owlCarousel) {
				$root.find('.adt-car-ad-carousel').each(function () {
					if (window.jQuery(this).hasClass('owl-loaded')) return;
					window.jQuery(this).owlCarousel({
						loop: true,
						rtl: (typeof is_rtl !== 'undefined') ? is_rtl : false,
						margin: 0,
						nav: true,
						navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
						dots: false,
						responsive: { 0: { items: 1 } }
					});
				});
			}
			if (window.jQuery.fn.tooltip) {
				$root.find('[data-toggle="tooltip"]').tooltip();
			}
			if (typeof window.adfInitTooltips === 'function') {
				window.adfInitTooltips();
			}
		}

		function getRenderedItemsCount() {
			return document.querySelectorAll(
				'.adt-search-ads-list .adt-category-ad-list, ' +
				'.adt-search-ads-list .adt-car-dealer-card, ' +
				'.adt-search-ads-grid > .adf-card-item, ' +
				'.adt-search-ads-grid .adt-category-ad-list, ' +
				'.adt-search-ads-grid .adt-car-dealer-card, ' +
				'.search-ads-result-box .adt-category-ad-list, ' +
				'.search-ads-result-box .adt-car-dealer-card, ' +
				'.search-ads-result-box > .adf-card-item'
			).length;
		}

		function hasNoMoreAdsMessage() {
			var el = document.getElementById('no_more_ads_p');
			if (!el) return false;
			var text = (el.textContent || '').trim();
			return text !== '';
		}

		function abortInFlightRequest() {
			if (abortController) {
				try {
					abortController.abort();
				} catch (e) {}
				abortController = null;
			}
			requestInFlight = false;
		}

		function markTriggered(button) {
			if (!button) return;
			triggerLock = true;
			requestInFlight = true;
			lastTriggerAt = Date.now();
			itemsAtRequestStart = getRenderedItemsCount();
			lastKnownItemsCount = itemsAtRequestStart;
			button.dataset.mcewPageScrollLoading = '1';
			setButtonLoading(button);
		}

		function releaseTrigger(button) {
			triggerLock = false;
			requestInFlight = false;
			if (button && button.dataset) {
				delete button.dataset.mcewPageScrollLoading;
			}
			setButtonIdle(button);
		}

		function isShortFirstPage(button) {
			var config = getButtonConfig(button);
			if (!config) {
				return true;
			}
			if (config.postsPerPage > 0 && config.adCount > 0 && config.adCount < config.postsPerPage) {
				return true;
			}
			return false;
		}

		function canTrigger(button) {
			if (authFailed) return false;
			if (!button) return false;
			if (button.disabled) return false;
			if (triggerLock || requestInFlight) return false;
			if (button.dataset && button.dataset.mcewPageScrollLoading === '1') return false;
			if (hasNoMoreAdsMessage()) return false;
			if (isShortFirstPage(button)) return false;

			var text = (button.textContent || '').toLowerCase();
			if (text.indexOf('loading') !== -1) return false;
			if (text.indexOf('no more') !== -1) return false;
			return true;
		}

		function resetAfterFilterRender() {
			abortInFlightRequest();
			requestGeneration += 1;
			authFailed = false;
			var button = getLoadButton();
			if (button) {
				button.dataset.mcewCurrentPage = '2';
				delete button.dataset.mcewPageScrollLoading;
				setButtonIdle(button);
				button.style.display = '';
			}
			clearNoMoreAds();
			triggerLock = false;
			requestInFlight = false;
			lastKnownItemsCount = getRenderedItemsCount();
			itemsAtRequestStart = lastKnownItemsCount;
			hideLoadingUi();
			window.setTimeout(maybeLoadNextPage, 120);
		}

		function loadNextPage(button) {
			var config = getButtonConfig(button);
			var appendTarget = getAppendTarget(config);
			if (!config || !appendTarget) {
				releaseTrigger(button);
				return;
			}

			var filtersRaw = buildFiltersRaw(config);
			var useSearchAjax = !!(config.searchAjaxNonce && config.searchAjaxUrl && filtersRaw);
			var requestUrl = useSearchAjax ? config.searchAjaxUrl : ajaxUrl;
			var params = new URLSearchParams();
			var generation = requestGeneration;

			if (useSearchAjax) {
				params.append('action', 'adforest_ajax_search');
				params.append('security', config.searchAjaxNonce);
				params.append('filters_raw', filtersRaw);
			} else {
				if (!config.searchQuery || !config.nonce || !ajaxUrl) {
					releaseTrigger(button);
					return;
				}
				params.append('action', 'load_more_ads');
				params.append('search_query', JSON.stringify(config.searchQuery));
				params.append('paged', String(config.paged));
				params.append('view_type', config.viewType || 'grid');
				params.append('security', config.nonce);
			}

			abortInFlightRequest();
			abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
			requestInFlight = true;

			var fetchOpts = {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			};
			if (abortController) {
				fetchOpts.signal = abortController.signal;
			}

			fetch(requestUrl, fetchOpts).then(function (response) {
				if (generation !== requestGeneration) {
					throw new Error('stale_generation');
				}
				if (response.status === 403) {
					throw new Error('auth_failed');
				}
				if (!response.ok) {
					throw new Error('load_more_failed');
				}
				return useSearchAjax ? response.json() : response.text();
			}).then(function (payload) {
				if (generation !== requestGeneration) {
					return;
				}

				var html = '';
				var maxPages = 0;
				if (useSearchAjax) {
					if (!payload || !payload.success || !payload.data) {
						throw new Error('invalid_search_ajax_response');
					}
					html = payload.data.html || '';
					maxPages = parseInt(payload.data.max_num_pages || '0', 10) || 0;
				} else {
					html = payload || '';
				}

				var normalized = (html || '').trim();
				if (!normalized || normalized === '0' || normalized === '-1') {
					if (normalized === '-1') {
						authFailed = true;
						showAuthFailureNotice();
						releaseTrigger(button);
						return;
					}
					showNoMoreAds();
					disableLoadButton(button);
					releaseTrigger(button);
					return;
				}

				if (useSearchAjax && maxPages > 0 && config.paged > maxPages) {
					showNoMoreAds();
					disableLoadButton(button);
					releaseTrigger(button);
					return;
				}

				if (responseHasEmptyState(html)) {
					showNoMoreAds();
					disableLoadButton(button);
					releaseTrigger(button);
					return;
				}

				var cards;
				if (useSearchAjax) {
					cards = extractCardNodesFromHtml(html, config);
				} else {
					var temp = document.createElement('div');
					temp.innerHTML = html;
					cards = collectCardNodes(temp);
					if (!cards.length) {
						// Classic load_more_ads returns bare card markup fragments.
						var fragCards = [];
						while (temp.firstChild) {
							var child = temp.firstChild;
							temp.removeChild(child);
							if (child.nodeType === 1) {
								fragCards.push(child);
							}
						}
						cards = fragCards;
					}
				}

				if (!cards.length) {
					showNoMoreAds();
					disableLoadButton(button);
					releaseTrigger(button);
					return;
				}

				var addedItems = appendCardNodes(appendTarget, cards);
				if (!addedItems) {
					showNoMoreAds();
					disableLoadButton(button);
					releaseTrigger(button);
					return;
				}

				button.dataset.mcewCurrentPage = String(config.paged + 1);
				lastKnownItemsCount = getRenderedItemsCount();
				reinitializeNewContent(appendTarget);
				releaseTrigger(button);
				hideLoadingUi();

				if (window.jQuery) {
					window.jQuery(document).trigger('ajax_content_loaded');
				}

				if (useSearchAjax && maxPages > 0 && config.paged >= maxPages) {
					showNoMoreAds();
					disableLoadButton(button);
				} else {
					window.setTimeout(maybeLoadNextPage, 80);
				}
			}).catch(function (err) {
				if (generation !== requestGeneration) {
					return;
				}
				if (err && err.name === 'AbortError') {
					return;
				}
				if (err && err.message === 'stale_generation') {
					return;
				}
				if (err && err.message === 'auth_failed') {
					authFailed = true;
					showAuthFailureNotice();
				}
				releaseTrigger(button);
				hideLoadingUi();
			}).then(function () {
				if (generation === requestGeneration) {
					requestInFlight = false;
					abortController = null;
				}
			});
		}

		function maybeLoadNextPage() {
			if (ticking) return;
			ticking = true;
			window.requestAnimationFrame(function () {
				ticking = false;
				var button = getLoadButton();
				if (!canTrigger(button)) return;
				if (!isNearResultsBottom()) return;
				markTriggered(button);
				loadNextPage(button);
			});
		}

		function scheduleMaybeLoadNextPage() {
			if (debounceTimer) {
				window.clearTimeout(debounceTimer);
			}
			debounceTimer = window.setTimeout(function () {
				debounceTimer = null;
				maybeLoadNextPage();
			}, 100);
		}

		function mutationLooksLikeWindowing(mutations) {
			if (!mutations || !mutations.length) {
				return false;
			}
			for (var i = 0; i < mutations.length; i++) {
				var m = mutations[i];
				var nodes = [];
				if (m.addedNodes && m.addedNodes.length) {
					for (var a = 0; a < m.addedNodes.length; a++) nodes.push(m.addedNodes[a]);
				}
				if (m.removedNodes && m.removedNodes.length) {
					for (var r = 0; r < m.removedNodes.length; r++) nodes.push(m.removedNodes[r]);
				}
				for (var n = 0; n < nodes.length; n++) {
					var node = nodes[n];
					if (!node || node.nodeType !== 1) continue;
					if (
						(node.classList && node.classList.contains('bornado-wis-spacer')) ||
						(node.getAttribute && node.getAttribute('data-bornado-wis-spacer') === '1') ||
						(node.getAttribute && node.getAttribute('data-bornado-wis-id'))
					) {
						return true;
					}
				}
			}
			return false;
		}

		window.addEventListener('scroll', scheduleMaybeLoadNextPage, { passive: true });
		window.addEventListener('resize', scheduleMaybeLoadNextPage);
		window.addEventListener('pageshow', function (event) {
			if (event && event.persisted) {
				abortInFlightRequest();
				triggerLock = false;
				requestInFlight = false;
				authFailed = false;
				var button = getLoadButton();
				if (button && button.dataset) {
					delete button.dataset.mcewPageScrollLoading;
					setButtonIdle(button);
				}
				lastKnownItemsCount = getRenderedItemsCount();
				hideLoadingUi();
				scheduleMaybeLoadNextPage();
			}
		});

		if (window.jQuery) {
			window.jQuery(document).on('adforest:search:rendered', function () {
				resetAfterFilterRender();
			});
		} else {
			document.addEventListener('adforest:search:rendered', resetAfterFilterRender);
		}

		if (document.readyState !== 'loading') {
			bindMobileFilterCompatibility();
		}
		document.addEventListener('DOMContentLoaded', function () {
			hideLoadingUi();
			bindMobileFilterCompatibility();
			scheduleMaybeLoadNextPage();
		});
		window.setTimeout(scheduleMaybeLoadNextPage, 400);
		window.setTimeout(hideLoadingUi, 100);
		window.setTimeout(hideLoadingUi, 600);

		var observer = new MutationObserver(function (mutations) {
			var windowingOnly = mutationLooksLikeWindowing(mutations);
			var button = getLoadButton();
			var currentItemsCount = getRenderedItemsCount();

			if (requestInFlight && currentItemsCount > itemsAtRequestStart) {
				// Content arrived; lock will clear in the fetch path. Avoid false unlocks from remounts.
				lastKnownItemsCount = currentItemsCount;
			} else if (triggerLock && requestInFlight && Date.now() - lastTriggerAt > 4000) {
				releaseTrigger(button);
			}

			hideLoadingUi();
			if (!windowingOnly) {
				scheduleMaybeLoadNextPage();
			}
		});
		observer.observe(document.body, { childList: true, subtree: true });
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mcew_page_scroll_loading_frontend_enhancer', 99 );
