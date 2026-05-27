<?php
/**
 * Bridge for AdForest search list style 3.
 *
 * Adds a third option in admin UI without editing theme core files,
 * then safely maps layout "3" to theme list style "1" on frontend logic
 * while exposing a CSS hook class for custom visual styling.
 *
 * @package My_Custom_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dedicated flag option key for custom style 3.
 */
const MCEW_STYLE3_FLAG_OPTION = 'mcew_adforest_list_style3_enabled';

/**
 * Raw setting helper from theme option.
 *
 * @return string
 */
function mcew_get_raw_adforest_list_layout_value() {
	$style3_flag = get_option( MCEW_STYLE3_FLAG_OPTION, '0' );
	if ( '1' === (string) $style3_flag ) {
		return '3';
	}

	$theme_opts = get_option( 'adforest_theme', array() );
	if ( ! is_array( $theme_opts ) ) {
		return '';
	}
	if ( isset( $theme_opts['mcew_list_layout_style3_active'] ) && '1' === (string) $theme_opts['mcew_list_layout_style3_active'] ) {
		return '3';
	}
	return isset( $theme_opts['adforest_list_layout'] ) ? (string) $theme_opts['adforest_list_layout'] : '';
}

/**
 * Parse serialized payload posted by Redux ajax save.
 *
 * @return array<string,string>
 */
function mcew_parse_redux_serialized_post_data() {
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
 * Persist custom style-3 selection in a dedicated option.
 *
 * Redux may sanitize unknown button_set values, so we keep our own flag.
 *
 * @return void
 */
function mcew_capture_style3_selection_from_theme_options_submit() {
	if ( ! is_admin() ) {
		return;
	}

	// First priority: explicit hidden flag posted by our injected admin UI script.
	$posted_flag = isset( $_POST[ MCEW_STYLE3_FLAG_OPTION ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? sanitize_text_field( wp_unslash( $_POST[ MCEW_STYLE3_FLAG_OPTION ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: '';

	if ( '1' === $posted_flag ) {
		update_option( MCEW_STYLE3_FLAG_OPTION, '1', false );
		return;
	}
	if ( '0' === $posted_flag ) {
		update_option( MCEW_STYLE3_FLAG_OPTION, '0', false );
		return;
	}

	// Redux ajax save often sends serialized form data in $_POST['data'].
	$redux_payload = mcew_parse_redux_serialized_post_data();
	if ( isset( $redux_payload[ MCEW_STYLE3_FLAG_OPTION ] ) ) {
		$redux_flag = sanitize_text_field( (string) $redux_payload[ MCEW_STYLE3_FLAG_OPTION ] );
		if ( '1' === $redux_flag ) {
			update_option( MCEW_STYLE3_FLAG_OPTION, '1', false );
			return;
		}
		if ( '0' === $redux_flag ) {
			update_option( MCEW_STYLE3_FLAG_OPTION, '0', false );
			return;
		}
	}

	$posted_layout = '';
	if ( isset( $_POST['adforest_theme'] ) && is_array( $_POST['adforest_theme'] ) && isset( $_POST['adforest_theme']['adforest_list_layout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_layout = sanitize_text_field( wp_unslash( $_POST['adforest_theme']['adforest_list_layout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['adforest_theme']['mcew_list_layout_style3_active'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted_inside = sanitize_text_field( wp_unslash( $_POST['adforest_theme']['mcew_list_layout_style3_active'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_option( MCEW_STYLE3_FLAG_OPTION, ( '1' === $posted_inside ? '1' : '0' ), false );
			return;
		}
	} elseif ( isset( $_POST['adforest_list_layout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_layout = sanitize_text_field( wp_unslash( $_POST['adforest_list_layout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	} elseif ( isset( $redux_payload['adforest_theme'] ) && is_array( $redux_payload['adforest_theme'] ) && isset( $redux_payload['adforest_theme']['adforest_list_layout'] ) ) {
		$posted_layout = sanitize_text_field( (string) $redux_payload['adforest_theme']['adforest_list_layout'] );
		if ( isset( $redux_payload['adforest_theme']['mcew_list_layout_style3_active'] ) ) {
			$redux_inside = sanitize_text_field( (string) $redux_payload['adforest_theme']['mcew_list_layout_style3_active'] );
			update_option( MCEW_STYLE3_FLAG_OPTION, ( '1' === $redux_inside ? '1' : '0' ), false );
			return;
		}
	}

	if ( '3' === $posted_layout ) {
		update_option( MCEW_STYLE3_FLAG_OPTION, '1', false );
		return;
	}

	// Any explicit non-3 choice disables style-3 override.
	if ( in_array( $posted_layout, array( '1', '2' ), true ) ) {
		update_option( MCEW_STYLE3_FLAG_OPTION, '0', false );
	}
}
add_action( 'admin_init', 'mcew_capture_style3_selection_from_theme_options_submit', 5 );
add_action( 'wp_ajax_redux_ajax_save', 'mcew_capture_style3_selection_from_theme_options_submit', 1 );

/**
 * Keep style-3 flag in sync when adforest_theme option changes directly.
 *
 * @param array $old_value Previous adforest_theme option.
 * @param array $value     New adforest_theme option.
 * @return void
 */
function mcew_sync_style3_flag_on_adforest_option_update( $old_value, $value ) {
	if ( ! is_array( $value ) || ! isset( $value['adforest_list_layout'] ) ) {
		return;
	}
	$layout = (string) $value['adforest_list_layout'];
	if ( '3' === $layout ) {
		update_option( MCEW_STYLE3_FLAG_OPTION, '1', false );
	} elseif ( in_array( $layout, array( '1', '2' ), true ) ) {
		update_option( MCEW_STYLE3_FLAG_OPTION, '0', false );
	}
}
add_action( 'update_option_adforest_theme', 'mcew_sync_style3_flag_on_adforest_option_update', 10, 2 );

/**
 * Ajax endpoint to persist style3 flag independent from Redux sanitization.
 *
 * @return void
 */
function mcew_ajax_set_style3_flag() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'mcew_style3_flag_nonce', 'nonce' );

	$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$flag  = ( '1' === $value ) ? '1' : '0';
	update_option( MCEW_STYLE3_FLAG_OPTION, $flag, false );

	wp_send_json_success( array( 'flag' => $flag ) );
}
add_action( 'wp_ajax_mcew_set_style3_flag', 'mcew_ajax_set_style3_flag' );

/**
 * Check whether style 3 is currently active on frontend.
 *
 * @return bool
 */
function mcew_is_style3_enabled() {
	$style3_flag = (string) get_option( MCEW_STYLE3_FLAG_OPTION, '0' );
	$theme_opts  = get_option( 'adforest_theme', array() );
	$redux_flag  = ( is_array( $theme_opts ) && isset( $theme_opts['mcew_list_layout_style3_active'] ) ) ? (string) $theme_opts['mcew_list_layout_style3_active'] : '0';

	return ( '1' === $style3_flag || '1' === $redux_flag );
}

/**
 * Build a fully localized relative posted-time label for style3 cards.
 *
 * @param int $ad_id Ad post ID.
 * @return string
 */
function mcew_get_relative_posted_label( $ad_id ) {
	$from = (int) get_post_time( 'U', true, $ad_id );
	if ( $from <= 0 ) {
		return '';
	}

	$to   = (int) current_time( 'timestamp' );
	$diff = abs( $to - $from );

	if ( $diff >= ( 15 * DAY_IN_SECONDS ) ) {
		return date_i18n( 'j F Y', $from );
	}

	if ( $diff < HOUR_IN_SECONDS ) {
		$mins = max( 1, (int) round( $diff / MINUTE_IN_SECONDS ) );
		return sprintf(
			_n( '%s دقیقه پیش', '%s دقیقه پیش', $mins, 'my-custom-widgets' ),
			number_format_i18n( $mins )
		);
	}

	if ( $diff < DAY_IN_SECONDS ) {
		$hours = max( 1, (int) round( $diff / HOUR_IN_SECONDS ) );
		return sprintf(
			_n( '%s ساعت پیش', '%s ساعت پیش', $hours, 'my-custom-widgets' ),
			number_format_i18n( $hours )
		);
	}

	if ( $diff < WEEK_IN_SECONDS ) {
		$days = max( 1, (int) round( $diff / DAY_IN_SECONDS ) );
		return sprintf(
			_n( '%s روز پیش', '%s روز پیش', $days, 'my-custom-widgets' ),
			number_format_i18n( $days )
		);
	}

	if ( $diff < MONTH_IN_SECONDS ) {
		$weeks = max( 1, (int) round( $diff / WEEK_IN_SECONDS ) );
		return sprintf(
			_n( '%s هفته پیش', '%s هفته پیش', $weeks, 'my-custom-widgets' ),
			number_format_i18n( $weeks )
		);
	}

	if ( $diff < YEAR_IN_SECONDS ) {
		$months = max( 1, (int) round( $diff / MONTH_IN_SECONDS ) );
		return sprintf(
			_n( '%s ماه پیش', '%s ماه پیش', $months, 'my-custom-widgets' ),
			number_format_i18n( $months )
		);
	}

	$years = max( 1, (int) round( $diff / YEAR_IN_SECONDS ) );
	return sprintf(
		_n( '%s سال پیش', '%s سال پیش', $years, 'my-custom-widgets' ),
		number_format_i18n( $years )
	);
}

/**
 * Build and cache style3 meta text for one ad.
 *
 * @param int $ad_id Ad post ID.
 * @return string
 */
function mcew_get_style3_meta_text( $ad_id ) {
	$ad_id = (int) $ad_id;
	if ( $ad_id <= 0 || 'ad_post' !== get_post_type( $ad_id ) ) {
		return '';
	}

	$cache_key = 'mcew_style3_meta_v2_' . $ad_id;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached && is_string( $cached ) ) {
		return $cached;
	}

	$posted = mcew_get_relative_posted_label( $ad_id );

	$city      = '';
	$countries = wp_get_post_terms(
		$ad_id,
		'ad_country',
		array(
			'orderby' => 'parent',
			'order'   => 'ASC',
		)
	);

	if ( ! is_wp_error( $countries ) && ! empty( $countries ) ) {
		$term = end( $countries );
		if ( $term instanceof WP_Term && isset( $term->name ) ) {
			$city = (string) $term->name;
		}
	}

	$text = $posted;
	if ( '' !== $city ) {
		$text .= ' ' . __( 'در', 'my-custom-widgets' ) . ' ' . $city;
	}

	set_transient( $cache_key, $text, 12 * HOUR_IN_SECONDS );

	return $text;
}

/**
 * Clear cached style3 meta when an ad changes.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function mcew_clear_style3_meta_cache( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return;
	}

	delete_transient( 'mcew_style3_meta_' . $post_id );
	delete_transient( 'mcew_style3_meta_v2_' . $post_id );
}
add_action( 'save_post_ad_post', 'mcew_clear_style3_meta_cache' );
add_action( 'deleted_post', 'mcew_clear_style3_meta_cache' );

/**
 * Clear cached meta when country terms change.
 *
 * @param int    $object_id Object ID.
 * @param array  $terms     Terms.
 * @param array  $tt_ids    Term taxonomy IDs.
 * @param string $taxonomy  Taxonomy name.
 * @return void
 */
function mcew_clear_style3_meta_cache_on_term_change( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( 'ad_country' !== $taxonomy ) {
		return;
	}

	mcew_clear_style3_meta_cache( $object_id );
}
add_action( 'set_object_terms', 'mcew_clear_style3_meta_cache_on_term_change', 10, 4 );

/**
 * Normalize incoming ad IDs for style3 meta requests.
 *
 * @return int[]
 */
function mcew_collect_requested_style3_meta_ids() {
	$ids = array();

	if ( isset( $_POST['ad_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ids[] = (int) wp_unslash( $_POST['ad_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	if ( isset( $_POST['ad_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_ids = wp_unslash( $_POST['ad_ids'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( is_array( $raw_ids ) ) {
			foreach ( $raw_ids as $raw_id ) {
				$ids[] = (int) $raw_id;
			}
		} elseif ( is_string( $raw_ids ) && '' !== $raw_ids ) {
			foreach ( explode( ',', $raw_ids ) as $raw_id ) {
				$ids[] = (int) trim( $raw_id );
			}
		}
	}

	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	return $ids;
}

/**
 * Fetch posted time + city label for style3 cards.
 *
 * @return void
 */
function mcew_ajax_get_style3_meta() {
	if ( ! mcew_is_style3_enabled() ) {
		wp_send_json_error( array( 'message' => 'style3 not active' ), 403 );
	}

	$ad_ids = mcew_collect_requested_style3_meta_ids();
	if ( empty( $ad_ids ) ) {
		wp_send_json_error( array( 'message' => 'invalid ad id' ), 400 );
	}

	$items = array();
	foreach ( $ad_ids as $ad_id ) {
		$text = mcew_get_style3_meta_text( $ad_id );
		if ( '' !== $text ) {
			$items[ (string) $ad_id ] = $text;
		}
	}

	if ( empty( $items ) ) {
		wp_send_json_error( array( 'message' => 'no valid ad ids' ), 400 );
	}

	$first_id   = (string) reset( $ad_ids );
	$first_text = isset( $items[ $first_id ] ) ? $items[ $first_id ] : reset( $items );

	wp_send_json_success(
		array(
			'meta_text' => $first_text,
			'items'     => $items,
		)
	);
}
add_action( 'wp_ajax_mcew_get_style3_meta', 'mcew_ajax_get_style3_meta' );
add_action( 'wp_ajax_nopriv_mcew_get_style3_meta', 'mcew_ajax_get_style3_meta' );

/**
 * Inject frontend enhancer for style3 cards.
 *
 * @return void
 */
function mcew_style3_frontend_enhancer() {
	if ( is_admin() ) {
		return;
	}
	if ( ! mcew_is_style3_enabled() ) {
		return;
	}
	?>
	<script>
	(function () {
		var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var metaCache = Object.create(null);
		var pendingIds = Object.create(null);
		var flushTimer = null;
		var scanTimer = null;
		var inflight = false;

		function addPriceBoxClass(card) {
			var priceBox = card.querySelector('.price-box');
			if (priceBox) {
				priceBox.classList.add('mcew-style3-price-box');
			}
		}

		function renderMeta(card, metaText) {
			if (!card || !metaText) return;
			var content = card.querySelector('.category-content-box');
			if (!content) return;
			var old = content.querySelector('.mcew-style3-posted');
			if (old) old.remove();
			var line = document.createElement('div');
			line.className = 'mcew-style3-posted';
			line.innerHTML = '<i class="far fa-clock" aria-hidden="true"></i><span class="mcew-style3-posted__text"></span>';
			line.querySelector('.mcew-style3-posted__text').textContent = metaText;
			var price = content.querySelector('.price-box');
			if (price) {
				price.insertAdjacentElement('afterend', line);
			} else {
				content.appendChild(line);
			}
			delete card.dataset.mcewStyle3Queued;
			card.dataset.mcewStyle3Done = '1';
		}

		function getCardAdId(card) {
			if (!card) return '';
			if (card.dataset && card.dataset.postId) return card.dataset.postId || '';
			var fav = card.querySelector('.favourite[data-adid], .favorite[data-adid]');
			if (!fav) fav = card.querySelector('.ad_to_fav[data-adid]');
			if (!fav) return '';
			return fav.getAttribute('data-adid') || '';
		}

		function processCard(card) {
			if (!card) return;
			addPriceBoxClass(card);
			var adId = getCardAdId(card);
			if (!adId) return;
			if (metaCache[adId]) {
				renderMeta(card, metaCache[adId]);
				return;
			}
			if (card.dataset.mcewStyle3Queued === '1') return;
			card.dataset.mcewStyle3Queued = '1';
			pendingIds[adId] = true;
			scheduleFlush();
		}

		function processWithin(root) {
			if (!root) return;
			if (root.nodeType === 1 && root.matches && root.matches('.mcew-theme-list-style-3 .adt-category-ad-list')) {
				processCard(root);
			}
			if (root.querySelectorAll) {
				root.querySelectorAll('.mcew-theme-list-style-3 .adt-category-ad-list').forEach(processCard);
			}
		}

		function applyMetaToExistingCards(adId, metaText) {
			if (!adId || !metaText) return;
			document.querySelectorAll('.mcew-theme-list-style-3 .adt-category-ad-list').forEach(function (card) {
				if (getCardAdId(card) === adId) {
					renderMeta(card, metaText);
				}
			});
		}

		function clearQueuedStateForAd(adId) {
			if (!adId) return;
			document.querySelectorAll('.mcew-theme-list-style-3 .adt-category-ad-list').forEach(function (card) {
				if (getCardAdId(card) === adId && card.dataset.mcewStyle3Done !== '1') {
					delete card.dataset.mcewStyle3Queued;
				}
			});
		}

		function flushPendingIds() {
			if (inflight) return;
			var adIds = Object.keys(pendingIds);
			if (!adIds.length) return;
			inflight = true;
			pendingIds = Object.create(null);

			var params = new URLSearchParams();
			params.append('action', 'mcew_get_style3_meta');
			adIds.forEach(function (adId) {
				params.append('ad_ids[]', adId);
			});
			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			}).then(function (r) { return r.json(); }).then(function (res) {
				if (!res || !res.success || !res.data || !res.data.items) return;
				Object.keys(res.data.items).forEach(function (adId) {
					metaCache[adId] = res.data.items[adId];
					applyMetaToExistingCards(adId, res.data.items[adId]);
				});
			})["catch"](function(){})
				["finally"](function () {
					adIds.forEach(function (adId) {
						if (!metaCache[adId]) {
							clearQueuedStateForAd(adId);
						}
					});
					inflight = false;
					if (Object.keys(pendingIds).length) {
						scheduleFlush();
					}
				});
		}

		function scheduleFlush() {
			if (flushTimer) return;
			flushTimer = window.setTimeout(function () {
				flushTimer = null;
				flushPendingIds();
			}, 80);
		}

		function scheduleScan(root) {
			if (scanTimer) window.clearTimeout(scanTimer);
			scanTimer = window.setTimeout(function () {
				scanTimer = null;
				processWithin(root || document);
			}, 30);
		}

		function run() {
			processWithin(document);
		}
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
		var obs = new MutationObserver(function (mutations) {
			var shouldScan = false;
			mutations.forEach(function (mutation) {
				if (shouldScan || !mutation.addedNodes || !mutation.addedNodes.length) return;
				for (var i = 0; i < mutation.addedNodes.length; i++) {
					if (mutation.addedNodes[i] && mutation.addedNodes[i].nodeType === 1) {
						shouldScan = true;
						break;
					}
				}
			});
			if (shouldScan) scheduleScan(document);
		});
		if (document.body) {
			obs.observe(document.body, { childList: true, subtree: true });
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mcew_style3_frontend_enhancer', 98 );

/**
 * Frontend-only option bridge:
 * if admin saved style 3, force core rendering branch to style 1
 * and add a marker key consumed by body_class/css.
 *
 * @param mixed $option_value Option payload.
 * @return mixed
 */
function mcew_bridge_adforest_list_layout_option_for_frontend( $option_value ) {
	if ( ! is_array( $option_value ) ) {
		return $option_value;
	}

	$array_flag = isset( $option_value['mcew_list_layout_style3_active'] ) ? (string) $option_value['mcew_list_layout_style3_active'] : '0';
	$style3_flag = get_option( MCEW_STYLE3_FLAG_OPTION, '0' );
	if ( '1' !== (string) $style3_flag && '1' !== $array_flag ) {
		$layout = isset( $option_value['adforest_list_layout'] ) ? (string) $option_value['adforest_list_layout'] : '';
		if ( '3' !== $layout ) {
			return $option_value;
		}
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $option_value;
	}

	$option_value['adforest_list_layout']              = '1';
	$option_value['mcew_list_layout_style3_active']    = '1';
	$option_value['mcew_list_layout_original_setting'] = '3';

	return $option_value;
}
add_filter( 'option_adforest_theme', 'mcew_bridge_adforest_list_layout_option_for_frontend', 20 );

/**
 * Add a body class to scope style 3 CSS safely.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function mcew_add_style3_body_class( $classes ) {
	$style3_flag = get_option( MCEW_STYLE3_FLAG_OPTION, '0' );
	$theme_opts  = get_option( 'adforest_theme', array() );
	$array_flag  = ( is_array( $theme_opts ) && isset( $theme_opts['mcew_list_layout_style3_active'] ) ) ? (string) $theme_opts['mcew_list_layout_style3_active'] : '0';
	if ( '1' !== (string) $style3_flag && '1' !== $array_flag ) {
		return $classes;
	}
	$classes[] = 'mcew-theme-list-style-3';

	return $classes;
}
add_filter( 'body_class', 'mcew_add_style3_body_class' );

/**
 * Inject admin-side third button for Redux fieldset.
 *
 * No core theme edit required: purely DOM extension in options page.
 */
function mcew_inject_style3_in_adforest_options_ui() {
	if ( ! is_admin() ) {
		return;
	}
	?>
	<script>
	(function () {
		var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var ajaxNonce = <?php echo wp_json_encode( wp_create_nonce( 'mcew_style3_flag_nonce' ) ); ?>;

		function persistStyle3Flag(isStyle3) {
			if (!ajaxUrl || !window.fetch) {
				return;
			}
			var params = new URLSearchParams();
			params.append('action', 'mcew_set_style3_flag');
			params.append('nonce', ajaxNonce);
			params.append('value', isStyle3 ? '1' : '0');
			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})["catch"](function () {});
		}

		function injectStyle3Option() {
			var fieldset = document.getElementById('adforest_theme-adforest_list_layout');
			if (!fieldset) {
				return;
			}

			var buttonset = fieldset.querySelector('.buttonset');
			if (!buttonset) {
				return;
			}

			var hiddenFlag = fieldset.querySelector('input[name="<?php echo esc_js( MCEW_STYLE3_FLAG_OPTION ); ?>"]');
			if (!hiddenFlag) {
				hiddenFlag = document.createElement('input');
				hiddenFlag.type = 'hidden';
				hiddenFlag.name = '<?php echo esc_js( MCEW_STYLE3_FLAG_OPTION ); ?>';
				hiddenFlag.value = '0';
				fieldset.appendChild(hiddenFlag);
			}
			var hiddenReduxFlag = fieldset.querySelector('input[name="adforest_theme[mcew_list_layout_style3_active]"]');
			if (!hiddenReduxFlag) {
				hiddenReduxFlag = document.createElement('input');
				hiddenReduxFlag.type = 'hidden';
				hiddenReduxFlag.name = 'adforest_theme[mcew_list_layout_style3_active]';
				hiddenReduxFlag.value = '0';
				fieldset.appendChild(hiddenReduxFlag);
			}

			if (fieldset.querySelector('#adforest_list_layout-buttonset3')) {
				var existingStyle3 = fieldset.querySelector('#adforest_list_layout-buttonset3');
				if (existingStyle3 && existingStyle3.checked) {
					hiddenFlag.value = '1';
					hiddenReduxFlag.value = '1';
				}
				return;
			}

			var input = document.createElement('input');
			input.type = 'radio';
			input.id = 'adforest_list_layout-buttonset3';
			input.name = 'adforest_theme[adforest_list_layout]';
			input.value = '3';
			input.className = 'buttonset-item ui-checkboxradio ui-helper-hidden-accessible';
			input.setAttribute('data-id', 'adforest_list_layout');

			var label = document.createElement('label');
			label.setAttribute('for', 'adforest_list_layout-buttonset3');
			label.className = 'ui-button ui-widget ui-checkboxradio-radio-label ui-controlgroup-item ui-checkboxradio-label ui-corner-right';
			label.innerHTML = '<span class="ui-checkboxradio-icon ui-corner-all ui-icon ui-icon-background ui-icon-blank"></span><span class="ui-checkboxradio-icon-space"> </span>Style 3';

			var labels = buttonset.querySelectorAll('label');
			if (labels.length) {
				labels[labels.length - 1].classList.remove('ui-corner-right');
			}

			buttonset.appendChild(input);
			buttonset.appendChild(label);

			var saved = <?php echo wp_json_encode( mcew_get_raw_adforest_list_layout_value() ); ?>;
			if (saved === '3') {
				input.checked = true;
				hiddenFlag.value = '1';
				hiddenReduxFlag.value = '1';
				label.classList.add('ui-checkboxradio-checked', 'ui-state-active');
				var allInputs = buttonset.querySelectorAll('input[type="radio"][name="adforest_theme[adforest_list_layout]"]');
				allInputs.forEach(function (el) {
					if (el !== input) {
						el.checked = false;
					}
				});
				var allLabels = buttonset.querySelectorAll('label');
				allLabels.forEach(function (el) {
					if (el !== label) {
						el.classList.remove('ui-checkboxradio-checked', 'ui-state-active');
					}
				});
			}

			buttonset.addEventListener('change', function (event) {
				var target = event && event.target ? event.target : null;
				if (!target || target.name !== 'adforest_theme[adforest_list_layout]') {
					return;
				}
				var isStyle3 = target.value === '3';
				hiddenFlag.value = isStyle3 ? '1' : '0';
				hiddenReduxFlag.value = isStyle3 ? '1' : '0';
				persistStyle3Flag(isStyle3);
			});

			if (window.jQuery && window.jQuery.fn.checkboxradio) {
				window.jQuery(buttonset).find('input').checkboxradio('refresh');
			}
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', injectStyle3Option);
		} else {
			injectStyle3Option();
		}
	})();
	</script>
	<?php
}
add_action( 'admin_footer', 'mcew_inject_style3_in_adforest_options_ui', 99 );
