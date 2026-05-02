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
	$custom_flag = (string) get_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0' );
	$theme_opts  = get_option( 'adforest_theme', array() );
	$array_flag  = ( is_array( $theme_opts ) && isset( $theme_opts['mcew_loading_mode_page_scroll_active'] ) ) ? (string) $theme_opts['mcew_loading_mode_page_scroll_active'] : '0';
	if ( '1' !== $custom_flag && '1' !== $array_flag ) {
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

	$custom_flag = (string) get_option( MCEW_PAGE_SCROLL_MODE_FLAG_OPTION, '0' );
	$theme_opts  = get_option( 'adforest_theme', array() );
	$array_flag  = ( is_array( $theme_opts ) && isset( $theme_opts['mcew_loading_mode_page_scroll_active'] ) ) ? (string) $theme_opts['mcew_loading_mode_page_scroll_active'] : '0';
	if ( '1' !== $custom_flag && '1' !== $array_flag ) {
		return;
	}
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
			return document.getElementById('load-more-ads-btn');
		}

		function canTrigger(button) {
			if (!button) return false;
			if (button.disabled) return false;
			var text = (button.textContent || '').toLowerCase();
			if (text.indexOf('loading') !== -1) return false;
			if (text.indexOf('no more') !== -1) return false;
			return true;
		}

		var ticking = false;
		function maybeLoadNextPage() {
			if (ticking) return;
			ticking = true;
			window.requestAnimationFrame(function () {
				ticking = false;
				var button = getLoadButton();
				if (!canTrigger(button)) return;
				var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
				var viewportBottom = scrollTop + window.innerHeight;
				var docHeight = Math.max(
					document.body.scrollHeight,
					document.documentElement.scrollHeight
				);
				if (viewportBottom >= docHeight - 900) {
					button.click();
				}
			});
		}

		window.addEventListener('scroll', maybeLoadNextPage, { passive: true });
		window.addEventListener('resize', maybeLoadNextPage);
		document.addEventListener('DOMContentLoaded', function () {
			hideLoadingUi();
			maybeLoadNextPage();
		});
		setTimeout(maybeLoadNextPage, 400);
		setTimeout(hideLoadingUi, 100);
		setTimeout(hideLoadingUi, 600);

		var observer = new MutationObserver(function () {
			hideLoadingUi();
			maybeLoadNextPage();
		});
		observer.observe(document.body, { childList: true, subtree: true });
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mcew_page_scroll_loading_frontend_enhancer', 99 );
