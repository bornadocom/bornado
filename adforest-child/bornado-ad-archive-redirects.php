<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BORNADO_AD_ARCHIVE_REDIRECT_OPTION')) {
    define('BORNADO_AD_ARCHIVE_REDIRECT_OPTION', 'redirect_archive');
}

if (!defined('BORNADO_AD_ARCHIVE_REDIRECT_TARGET_META')) {
    define('BORNADO_AD_ARCHIVE_REDIRECT_TARGET_META', '_bornado_archive_redirect_target');
}

if (!defined('BORNADO_AD_ARCHIVE_REDIRECT_SOURCE_META')) {
    define('BORNADO_AD_ARCHIVE_REDIRECT_SOURCE_META', '_bornado_archive_redirect_source');
}

if (!defined('BORNADO_AD_ARCHIVE_REDIRECT_STATUS_META')) {
    define('BORNADO_AD_ARCHIVE_REDIRECT_STATUS_META', '_bornado_archive_redirect_status');
}

if (!defined('BORNADO_AFTER_EXPIRED_REDIRECT_FLAG_OPTION')) {
    define('BORNADO_AFTER_EXPIRED_REDIRECT_FLAG_OPTION', 'bornado_after_expired_redirect_archive');
}

if (!defined('BORNADO_AFTER_SOLD_REDIRECT_FLAG_OPTION')) {
    define('BORNADO_AFTER_SOLD_REDIRECT_FLAG_OPTION', 'bornado_after_sold_redirect_archive');
}

if (!function_exists('bornado_get_ad_lifecycle_option')) {
    /**
     * Read one AdForest theme option without depending on parent globals only.
     *
     * @param string $key     Option key.
     * @param mixed  $default Fallback value.
     * @return mixed
     */
    function bornado_get_ad_lifecycle_option($key, $default = '')
    {
        global $adforest_theme;

        if (isset($adforest_theme[$key])) {
            return $adforest_theme[$key];
        }

        $options = get_option('adforest_theme', array());
        if (is_array($options) && array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }
}

if (!function_exists('bornado_get_raw_ad_lifecycle_option')) {
    /**
     * Read one AdForest option exactly as stored in the database.
     *
     * @param string $key     Option key.
     * @param mixed  $default Fallback value.
     * @return mixed
     */
    function bornado_get_raw_ad_lifecycle_option($key, $default = '')
    {
        $options = get_option('adforest_theme', array());
        if (is_array($options) && array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }
}

if (!function_exists('bornado_get_archive_redirect_flag_option_name')) {
    /**
     * Resolve the standalone option name used for one lifecycle redirect flag.
     *
     * @param string $status Ad lifecycle status.
     * @return string
     */
    function bornado_get_archive_redirect_flag_option_name($status)
    {
        $status = sanitize_key($status);

        if ($status === 'expired') {
            return BORNADO_AFTER_EXPIRED_REDIRECT_FLAG_OPTION;
        }

        if ($status === 'sold') {
            return BORNADO_AFTER_SOLD_REDIRECT_FLAG_OPTION;
        }

        return '';
    }
}

if (!function_exists('bornado_is_archive_redirect_flag_enabled')) {
    /**
     * Whether the companion redirect flag is enabled for one lifecycle mode.
     *
     * @param string $status Ad lifecycle status.
     * @return bool
     */
    function bornado_is_archive_redirect_flag_enabled($status)
    {
        $option_name = bornado_get_archive_redirect_flag_option_name($status);
        if ($option_name === '') {
            return false;
        }

        return (string) get_option($option_name, '0') === '1';
    }
}

if (!function_exists('bornado_is_archive_redirect_mode_enabled')) {
    /**
     * Whether one lifecycle mode should use the child-theme archive redirect.
     *
     * @param string $status Ad lifecycle status.
     * @return bool
     */
    function bornado_is_archive_redirect_mode_enabled($status)
    {
        $status = sanitize_key($status);
        if (!in_array($status, array('expired', 'sold'), true)) {
            return false;
        }

        $option_key = $status === 'sold' ? 'after_sold_ads' : 'after_expired_ads';

        return bornado_is_archive_redirect_flag_enabled($status)
            || bornado_get_raw_ad_lifecycle_option($option_key) === BORNADO_AD_ARCHIVE_REDIRECT_OPTION;
    }
}

if (!function_exists('bornado_normalize_ad_lifecycle_options_for_runtime')) {
    /**
     * Keep parent theme runtime on known values while custom redirect logic
     * remains controlled from the child theme.
     *
     * AdForest's single-ad expiry handler treats any unknown
     * `after_expired_ads` value as the fallback branch that leaves the ad
     * published. When our custom `redirect_archive` option is selected, convert
     * it to a safe built-in runtime value so parent code never republishes the
     * expired listing before our redirect layer runs.
     *
     * @param array<string,mixed> $options Theme options array.
     * @return array<string,mixed>
     */
    function bornado_normalize_ad_lifecycle_options_for_runtime($options)
    {
        if (!is_array($options)) {
            return $options;
        }

        if (
            isset($options['after_expired_ads'])
            && $options['after_expired_ads'] === BORNADO_AD_ARCHIVE_REDIRECT_OPTION
        ) {
            $options['after_expired_ads'] = 'expired';
        }

        if (
            isset($options['after_sold_ads'])
            && $options['after_sold_ads'] === BORNADO_AD_ARCHIVE_REDIRECT_OPTION
        ) {
            $options['after_sold_ads'] = 'expired';
        }

        return $options;
    }
}

if (!function_exists('bornado_sync_runtime_ad_lifecycle_globals')) {
    /**
     * Mirror normalized lifecycle options into the parent theme global.
     *
     * @return void
     */
    function bornado_sync_runtime_ad_lifecycle_globals()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
        ) {
            return;
        }

        global $adforest_theme;

        if (!is_array($adforest_theme)) {
            return;
        }

        $adforest_theme = bornado_normalize_ad_lifecycle_options_for_runtime($adforest_theme);
    }
}
add_action('wp', 'bornado_sync_runtime_ad_lifecycle_globals', 1);

if (!function_exists('bornado_should_archive_redirect_ad_status')) {
    /**
     * Whether the selected sold/expired action should redirect to the archive.
     *
     * @param string $status Ad lifecycle status.
     * @return bool
     */
    function bornado_should_archive_redirect_ad_status($status)
    {
        $status = sanitize_key($status);
        return bornado_is_archive_redirect_mode_enabled($status);
    }
}

if (!function_exists('bornado_normalize_public_path')) {
    /**
     * Normalize a request path for stable redirect lookups.
     *
     * @param string $path_or_url Relative path or absolute URL.
     * @return string
     */
    function bornado_normalize_public_path($path_or_url)
    {
        $path_or_url = is_string($path_or_url) ? trim($path_or_url) : '';
        if ($path_or_url === '') {
            return '/';
        }

        $path = wp_parse_url($path_or_url, PHP_URL_PATH);
        $path = is_string($path) ? $path : $path_or_url;
        $path = rawurldecode($path);
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path);

        if ($path !== '/') {
            $path = untrailingslashit($path);
        }

        return $path;
    }
}

if (!function_exists('bornado_get_current_request_public_path')) {
    /**
     * Get the normalized path for the current frontend request.
     *
     * @return string
     */
    function bornado_get_current_request_public_path()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
        $path        = is_string($request_uri) ? wp_parse_url($request_uri, PHP_URL_PATH) : '';

        return bornado_normalize_public_path((string) $path);
    }
}

if (!function_exists('bornado_get_historical_public_ad_slug')) {
    /**
     * Recover the public slug that an ad used before being moved to trash.
     *
     * @param WP_Post $post Ad post object.
     * @return string
     */
    function bornado_get_historical_public_ad_slug($post)
    {
        if (!$post instanceof WP_Post) {
            return '';
        }

        $desired_slug = trim((string) get_post_meta((int) $post->ID, '_wp_desired_post_slug', true));
        if ($desired_slug !== '') {
            return sanitize_title($desired_slug);
        }

        $post_slug = trim((string) $post->post_name);
        if ($post_slug === '') {
            return sanitize_title((string) $post->post_title);
        }

        return sanitize_title(preg_replace('/__trashed$/', '', $post_slug));
    }
}

if (!function_exists('bornado_get_public_ad_permalink')) {
    /**
     * Resolve the stable public permalink for one ad, even after it becomes draft.
     *
     * @param int $post_id Ad post ID.
     * @return string
     */
    function bornado_get_public_ad_permalink($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return '';
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post || $post->post_type !== 'ad_post') {
            return '';
        }

        if ($post->post_status === 'trash') {
            $historical_slug = bornado_get_historical_public_ad_slug($post);
            if ($historical_slug !== '' && class_exists('Bornado_Ad_Hash_Service')) {
                $hash = (string) Bornado_Ad_Hash_Service::instance()->encode_id($post->ID);
                if ($hash !== '') {
                    return home_url(user_trailingslashit('ad/' . $hash . '/' . $historical_slug));
                }
            }
        }

        if (class_exists('Bornado_Ad_Permalinks') && method_exists('Bornado_Ad_Permalinks', 'get_canonical_permalink')) {
            $canonical_url = (string) Bornado_Ad_Permalinks::get_canonical_permalink($post);
            if ($canonical_url !== '') {
                return $canonical_url;
            }
        }

        $removed_preview_filter = false;
        if (function_exists('bornado_filter_unpublished_ad_post_permalink')) {
            $removed_preview_filter = remove_filter('post_type_link', 'bornado_filter_unpublished_ad_post_permalink', 20);
        }

        $public_url = get_permalink($post_id);

        if ($removed_preview_filter) {
            add_filter('post_type_link', 'bornado_filter_unpublished_ad_post_permalink', 20, 4);
        }

        return is_string($public_url) ? $public_url : '';
    }
}

if (!function_exists('bornado_get_single_ad_archive_redirect_fallback_url')) {
    /**
     * Rebuild the last single-ad breadcrumb archive URL from assigned terms.
     *
     * @param int $post_id Ad post ID.
     * @return string
     */
    function bornado_get_single_ad_archive_redirect_fallback_url($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return '';
        }

        if (
            !function_exists('bornado_semantic_breadcrumb_get_post_term_chain')
            || !function_exists('bornado_semantic_breadcrumb_get_semantic_archive_url')
        ) {
            return '';
        }

        $location_chain = (array) bornado_semantic_breadcrumb_get_post_term_chain($post_id, 'ad_country');
        $category_chain = (array) bornado_semantic_breadcrumb_get_post_term_chain($post_id, 'ad_cats');
        $country_term   = !empty($location_chain[0]) && $location_chain[0] instanceof WP_Term ? $location_chain[0] : null;
        $city_term      = !empty($location_chain) ? end($location_chain) : null;
        $deepest_cat    = !empty($category_chain) ? end($category_chain) : null;

        if (!$city_term instanceof WP_Term) {
            $city_term = null;
        }

        if ($city_term instanceof WP_Term && $country_term instanceof WP_Term && (int) $city_term->term_id === (int) $country_term->term_id) {
            $city_term = null;
        }

        $country_id  = $country_term instanceof WP_Term ? (int) $country_term->term_id : 0;
        $city_id     = $city_term instanceof WP_Term ? (int) $city_term->term_id : 0;
        $category_id = $deepest_cat instanceof WP_Term ? (int) $deepest_cat->term_id : 0;

        if ($category_id > 0) {
            return (string) bornado_semantic_breadcrumb_get_semantic_archive_url($country_id, $city_id, $category_id);
        }

        if ($city_id > 0) {
            return (string) bornado_semantic_breadcrumb_get_semantic_archive_url($country_id, $city_id, 0);
        }

        if ($country_id > 0) {
            return (string) bornado_semantic_breadcrumb_get_semantic_archive_url($country_id, 0, 0);
        }

        return '';
    }
}

if (!function_exists('bornado_get_single_ad_archive_redirect_url')) {
    /**
     * Resolve the exact archive URL represented by the last breadcrumb branch.
     *
     * @param int $post_id Ad post ID.
     * @return string
     */
    function bornado_get_single_ad_archive_redirect_url($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return '';
        }

        if (function_exists('bornado_semantic_breadcrumb_get_single_ad_items')) {
            $items = (array) bornado_semantic_breadcrumb_get_single_ad_items($post_id, false);

            for ($index = count($items) - 1; $index >= 0; $index--) {
                $item_url = !empty($items[$index]['url']) ? (string) $items[$index]['url'] : '';
                if ($item_url !== '') {
                    return $item_url;
                }
            }
        }

        return trim((string) bornado_get_single_ad_archive_redirect_fallback_url($post_id));
    }
}

if (!function_exists('bornado_clear_ad_archive_redirect_meta')) {
    /**
     * Remove redirect bookkeeping once an ad becomes public again.
     *
     * @param int $post_id Ad post ID.
     * @return void
     */
    function bornado_clear_ad_archive_redirect_meta($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return;
        }

        delete_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_TARGET_META);
        delete_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_SOURCE_META);
        delete_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_STATUS_META);
    }
}

if (!function_exists('bornado_transition_ad_to_archive_redirect_state')) {
    /**
     * Convert an ad into a non-public state while preserving an SEO redirect.
     *
     * @param int    $post_id Ad post ID.
     * @param string $status  Lifecycle status to persist.
     * @return string Redirect target URL.
     */
    function bornado_transition_ad_to_archive_redirect_state($post_id, $status)
    {
        $post_id = (int) $post_id;
        $status  = sanitize_key($status);

        if ($post_id < 1 || !in_array($status, array('sold', 'expired'), true)) {
            return '';
        }

        $target_url = trim((string) bornado_get_single_ad_archive_redirect_url($post_id));
        if ($target_url === '') {
            return '';
        }

        $source_url  = bornado_get_public_ad_permalink($post_id);
        $source_path = bornado_normalize_public_path((string) $source_url);

        if ($source_path === '/' && is_singular('ad_post') && get_queried_object_id() === $post_id) {
            $source_path = bornado_get_current_request_public_path();
        }

        if ($source_path === '/' || $source_path === '') {
            return '';
        }

        update_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_TARGET_META, esc_url_raw($target_url));
        update_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_SOURCE_META, $source_path);
        update_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_STATUS_META, $status);
        update_post_meta($post_id, '_adforest_ad_status_', $status);

        if (get_post_status($post_id) !== 'draft') {
            wp_update_post(array(
                'ID'          => $post_id,
                'post_status' => 'draft',
                'post_type'   => 'ad_post',
            ));
        }

        return $target_url;
    }
}

if (!function_exists('bornado_get_ad_archive_redirect_candidate_status')) {
    /**
     * Resolve whether one ad should currently live in the archive redirect state.
     *
     * @param int $post_id Ad post ID.
     * @return string Either `expired`, `sold`, or an empty string.
     */
    function bornado_get_ad_archive_redirect_candidate_status($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return '';
        }

        $stored_status = sanitize_key((string) get_post_meta($post_id, '_adforest_ad_status_', true));
        if ($stored_status === 'sold' && bornado_should_archive_redirect_ad_status('sold')) {
            return 'sold';
        }

        if ($stored_status === 'expired' && bornado_should_archive_redirect_ad_status('expired')) {
            return 'expired';
        }

        if (bornado_should_archive_redirect_ad_status('expired') && bornado_should_force_archive_expired_ad($post_id)) {
            return 'expired';
        }

        return '';
    }
}

if (!function_exists('bornado_is_ad_past_expiry_window')) {
    /**
     * Reproduce AdForest expiry timing before the parent template runs.
     *
     * @param int $post_id Ad post ID.
     * @return bool
     */
    function bornado_is_ad_past_expiry_window($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return false;
        }

        $expiry_days = get_post_meta($post_id, 'package_ad_expiry_days', true);
        if ($expiry_days === '' || $expiry_days === null) {
            $expiry_days = bornado_get_ad_lifecycle_option('simple_ad_removal', '-1');
        }

        if ($expiry_days === '' || $expiry_days === null || (string) $expiry_days === '-1') {
            return false;
        }

        $expiry_days = (int) $expiry_days;
        if ($expiry_days < 0) {
            return false;
        }

        $simple_date = strtotime((string) get_the_date('Y-m-d', $post_id));
        if (!$simple_date) {
            return false;
        }

        $simple_days = function_exists('adforest_days_diff')
            ? (int) adforest_days_diff(time(), $simple_date)
            : (int) floor(abs(time() - $simple_date) / DAY_IN_SECONDS);

        return $simple_days >= $expiry_days;
    }
}

if (!function_exists('bornado_is_ad_marked_expired')) {
    /**
     * Whether AdForest already flagged this ad as expired.
     *
     * @param int $post_id Ad post ID.
     * @return bool
     */
    function bornado_is_ad_marked_expired($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return false;
        }

        return (string) get_post_meta($post_id, '_adforest_ad_status_', true) === 'expired';
    }
}

if (!function_exists('bornado_should_force_archive_expired_ad')) {
    /**
     * Decide whether one ad must immediately leave the public published state.
     *
     * @param int $post_id Ad post ID.
     * @return bool
     */
    function bornado_should_force_archive_expired_ad($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return false;
        }

        return bornado_is_ad_marked_expired($post_id) || bornado_is_ad_past_expiry_window($post_id);
    }
}

if (!function_exists('bornado_maybe_redirect_expired_single_ad')) {
    /**
     * On first public hit after expiry, archive the ad and 301 to its exact archive.
     *
     * @return void
     */
    function bornado_maybe_redirect_expired_single_ad()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || !is_singular('ad_post')
            || !bornado_should_archive_redirect_ad_status('expired')
        ) {
            return;
        }

        $post_id = (int) get_queried_object_id();
        if ($post_id < 1) {
            return;
        }

        if (!bornado_should_force_archive_expired_ad($post_id)) {
            return;
        }

        $target_url = bornado_transition_ad_to_archive_redirect_state($post_id, 'expired');
        if ($target_url === '') {
            return;
        }

        wp_safe_redirect($target_url, (int) apply_filters('bornado_ad_archive_redirect_status_code', 301, $post_id, $target_url, 'expired'));
        exit;
    }
}
add_action('template_redirect', 'bornado_maybe_redirect_expired_single_ad', 0);

if (!function_exists('bornado_swap_simple_ads_removal_handler')) {
    /**
     * Route AdForest automatic expiry through the child redirect layer.
     *
     * @return void
     */
    function bornado_swap_simple_ads_removal_handler()
    {
        if (!has_action('adforest_simple_ads_removal', 'adforest_simple_ads_removal_callback')) {
            return;
        }

        remove_action('adforest_simple_ads_removal', 'adforest_simple_ads_removal_callback');
        add_action('adforest_simple_ads_removal', 'bornado_handle_simple_ads_removal', 10);
    }
}
add_action('after_setup_theme', 'bornado_swap_simple_ads_removal_handler', 50);

if (!function_exists('bornado_handle_simple_ads_removal')) {
    /**
     * Keep automatic expiry aligned with Redirect Archive mode.
     *
     * @return void
     */
    function bornado_handle_simple_ads_removal()
    {
        if (!bornado_should_archive_redirect_ad_status('expired')) {
            if (function_exists('adforest_simple_ads_removal_callback')) {
                adforest_simple_ads_removal_callback();
            }

            return;
        }

        $candidate_ids = get_posts(array(
            'post_type'              => 'ad_post',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'orderby'                => 'date',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));

        foreach ($candidate_ids as $candidate_id) {
            $candidate_id = (int) $candidate_id;
            if ($candidate_id < 1 || !bornado_should_force_archive_expired_ad($candidate_id)) {
                continue;
            }

            $previous_status = sanitize_key((string) get_post_meta($candidate_id, '_adforest_ad_status_', true));
            if ($previous_status !== 'expired' && function_exists('adforest_add_ad_post_notification')) {
                adforest_add_ad_post_notification($candidate_id, 'expired');
            }

            bornado_transition_ad_to_archive_redirect_state($candidate_id, 'expired');
        }

    }
}

if (!function_exists('bornado_maybe_redirect_archived_ad_404')) {
    /**
     * Redirect removed ad URLs to their preserved archive target.
     *
     * @return void
     */
    function bornado_maybe_redirect_archived_ad_404()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || !is_404()
        ) {
            return;
        }

        $request_path = bornado_get_current_request_public_path();
        if ($request_path === '' || $request_path === '/') {
            return;
        }

        $redirected_posts = get_posts(array(
            'post_type'              => 'ad_post',
            'post_status'            => array('draft', 'trash', 'private'),
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'   => BORNADO_AD_ARCHIVE_REDIRECT_SOURCE_META,
                    'value' => $request_path,
                ),
            ),
        ));

        $post_id = !empty($redirected_posts[0]) ? (int) $redirected_posts[0] : 0;
        if ($post_id < 1) {
            return;
        }

        $target_url = trim((string) get_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_TARGET_META, true));
        if ($target_url === '') {
            return;
        }

        $status = (string) get_post_meta($post_id, BORNADO_AD_ARCHIVE_REDIRECT_STATUS_META, true);
        $refreshed_status = in_array($status, array('expired', 'sold'), true)
            ? $status
            : bornado_get_ad_archive_redirect_candidate_status($post_id);
        if ($refreshed_status !== '' && bornado_should_archive_redirect_ad_status($refreshed_status)) {
            $refreshed_target = bornado_transition_ad_to_archive_redirect_state($post_id, $refreshed_status);
            if ($refreshed_target !== '') {
                $target_url = $refreshed_target;
                $status     = $refreshed_status;
            }
        }

        wp_safe_redirect($target_url, (int) apply_filters('bornado_ad_archive_redirect_status_code', 301, $post_id, $target_url, $status));
        exit;
    }
}
add_action('template_redirect', 'bornado_maybe_redirect_archived_ad_404', 1);

if (!function_exists('bornado_filter_ad_lifecycle_redirect_field')) {
    /**
     * Add the archive-redirect option as a real Redux button-set choice.
     *
     * @param array $field Redux field config.
     * @return array
     */
    function bornado_filter_ad_lifecycle_redirect_field($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        if (empty($field['options']) || !is_array($field['options'])) {
            $field['options'] = array();
        }

        $field['options'][BORNADO_AD_ARCHIVE_REDIRECT_OPTION] = __('Redirect Archive', 'adforest-child');

        return $field;
    }
}
add_filter('redux/options/adforest_theme/field/after_expired_ads', 'bornado_filter_ad_lifecycle_redirect_field');
add_filter('redux/options/adforest_theme/field/after_sold_ads', 'bornado_filter_ad_lifecycle_redirect_field');

if (!function_exists('bornado_validate_ad_lifecycle_redirect_option')) {
    /**
     * Map the custom option back onto AdForest's native values on save.
     *
     * The UI may offer `redirect_archive`, but the parent theme should still
     * persist a native lifecycle mode such as `expired`.
     *
     * @param array  $field    Redux field config.
     * @param mixed  $value    Submitted value.
     * @param mixed  $existing Existing saved value.
     * @return array<string,mixed>
     */
    function bornado_validate_ad_lifecycle_redirect_option($field, $value, $existing = null)
    {
        unset($existing);

        $field_id = !empty($field['id']) ? sanitize_key((string) $field['id']) : '';
        $status   = $field_id === 'after_sold_ads' ? 'sold' : 'expired';
        $value    = sanitize_key((string) $value);

        $allowed_values = array('published', 'trashed', 'expired', BORNADO_AD_ARCHIVE_REDIRECT_OPTION);
        if (!in_array($value, $allowed_values, true)) {
            $value = isset($field['default']) ? sanitize_key((string) $field['default']) : 'trashed';
        }

        $is_redirect_archive = ($value === BORNADO_AD_ARCHIVE_REDIRECT_OPTION);
        $flag_option_name    = bornado_get_archive_redirect_flag_option_name($status);
        if ($flag_option_name !== '') {
            update_option($flag_option_name, $is_redirect_archive ? '1' : '0', false);
        }

        return array(
            'value' => $is_redirect_archive ? 'expired' : $value,
        );
    }
}
add_filter('redux/options/adforest_theme/validate/after_expired_ads', 'bornado_validate_ad_lifecycle_redirect_option', 10, 3);
add_filter('redux/options/adforest_theme/validate/after_sold_ads', 'bornado_validate_ad_lifecycle_redirect_option', 10, 3);

if (!function_exists('bornado_is_adforest_theme_options_page')) {
    /**
     * Best-effort detection for the AdForest Redux admin screen.
     *
     * @return bool
     */
    function bornado_is_adforest_theme_options_page()
    {
        if (!is_admin()) {
            return false;
        }

        if (!empty($_POST['adforest_theme']) && is_array($_POST['adforest_theme'])) {
            return true;
        }

        $page = isset($_REQUEST['page']) ? sanitize_key(wp_unslash($_REQUEST['page'])) : '';
        if ($page !== '' && strpos($page, 'adforest') !== false) {
            return true;
        }

        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && !empty($screen->id) && strpos((string) $screen->id, 'adforest') !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_force_persist_archive_redirect_admin_selection')) {
    /**
     * Persist Redirect Archive admin selections even if Redux validation misses them.
     *
     * @return void
     */
    function bornado_force_persist_archive_redirect_admin_selection()
    {
        if (
            !is_admin()
            || empty($_POST['adforest_theme'])
            || !is_array($_POST['adforest_theme'])
            || !bornado_is_adforest_theme_options_page()
            || !current_user_can('manage_options')
        ) {
            return;
        }

        $posted_options = wp_unslash($_POST['adforest_theme']);
        $tracked_fields = array(
            'after_expired_ads' => 'expired',
            'after_sold_ads'    => 'sold',
        );

        $saved_options = get_option('adforest_theme', array());
        if (!is_array($saved_options)) {
            $saved_options = array();
        }

        $changes = array();
        foreach ($tracked_fields as $field_key => $status) {
            if (!array_key_exists($field_key, $posted_options)) {
                continue;
            }

            $posted_value = sanitize_key((string) $posted_options[$field_key]);
            $flag_name    = bornado_get_archive_redirect_flag_option_name($status);
            if ($flag_name === '') {
                continue;
            }

            if ($posted_value === BORNADO_AD_ARCHIVE_REDIRECT_OPTION) {
                $saved_options[$field_key] = BORNADO_AD_ARCHIVE_REDIRECT_OPTION;
                update_option($flag_name, '1', false);
                $changes[$field_key] = 'redirect_archive';
                continue;
            }

            update_option($flag_name, '0', false);
            if ($posted_value !== '') {
                $saved_options[$field_key] = $posted_value;
                $changes[$field_key] = $posted_value;
            }
        }

        if (!empty($changes)) {
            update_option('adforest_theme', $saved_options, false);
        }
    }
}
add_action('shutdown', 'bornado_force_persist_archive_redirect_admin_selection', 999);

if (!function_exists('bornado_enqueue_archive_redirect_option_ui')) {
    /**
     * When the redirect flag is enabled, keep the custom radio visibly selected
     * even though Redux persists the underlying native value (`expired`).
     *
     * @return void
     */
    function bornado_enqueue_archive_redirect_option_ui()
    {
        if (!bornado_is_adforest_theme_options_page()) {
            return;
        }

        $config = array(
            'fields' => array(
                array(
                    'field'   => 'after_expired_ads',
                    'enabled' => bornado_is_archive_redirect_flag_enabled('expired')
                        || bornado_get_raw_ad_lifecycle_option('after_expired_ads') === BORNADO_AD_ARCHIVE_REDIRECT_OPTION,
                ),
                array(
                    'field'   => 'after_sold_ads',
                    'enabled' => bornado_is_archive_redirect_flag_enabled('sold')
                        || bornado_get_raw_ad_lifecycle_option('after_sold_ads') === BORNADO_AD_ARCHIVE_REDIRECT_OPTION,
                ),
            ),
        );

        wp_register_script('bornado-archive-redirect-admin-ui', '', array(), null, true);
        wp_enqueue_script('bornado-archive-redirect-admin-ui');
        wp_add_inline_script(
            'bornado-archive-redirect-admin-ui',
            'window.BornadoArchiveRedirectOption=' . wp_json_encode($config) . ';'
            . <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
    var config = window.BornadoArchiveRedirectOption || {};
    var fields = Array.isArray(config.fields) ? config.fields : [];
    if (!fields.length) {
        return;
    }

    function getLabel(radio) {
        if (!radio || !radio.id) {
            return null;
        }
        return document.querySelector('label[for="' + radio.id + '"]');
    }

    function syncField(item) {
        var field = String(item.field || '');
        if (!field) {
            return;
        }

        var radios = Array.prototype.slice.call(document.querySelectorAll('input[name="adforest_theme[' + field + ']"]'));
        if (!radios.length) {
            return;
        }

        var redirectRadio = radios.find(function (radio) {
            return String(radio.value) === 'redirect_archive';
        });
        var expiredRadio = radios.find(function (radio) {
            return String(radio.value) === 'expired';
        });
        if (!redirectRadio || !expiredRadio) {
            return;
        }

        if (item.enabled) {
            redirectRadio.checked = true;
            expiredRadio.checked = false;
        }

        radios.forEach(function (radio) {
            var label = getLabel(radio);
            if (!label) {
                return;
            }

            label.classList.toggle('ui-state-active', !!radio.checked);
            label.classList.toggle('ui-button-active', !!radio.checked);
            label.setAttribute('aria-pressed', radio.checked ? 'true' : 'false');
        });

        redirectRadio.addEventListener('change', function () {
            item.enabled = redirectRadio.checked;
        });

        radios.forEach(function (radio) {
            if (radio === redirectRadio) {
                return;
            }

            radio.addEventListener('change', function () {
                if (radio.checked) {
                    item.enabled = false;
                }
            });
        });
    }

    fields.forEach(syncField);
});
JS
        );
    }
}
add_action('admin_enqueue_scripts', 'bornado_enqueue_archive_redirect_option_ui');

if (!function_exists('bornado_is_expired_dashboard_view_context')) {
    /**
     * Detect front-end dashboard views that should list expired/sold ads only.
     *
     * @return bool
     */
    function bornado_is_expired_dashboard_view_context()
    {
        if (is_admin() && !wp_doing_ajax()) {
            return false;
        }

        if (!is_page_template('page-theme-dashboard.php')) {
            return false;
        }

        $page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';

        return $page_type === 'expire_ads';
    }
}

if (!function_exists('bornado_is_expired_dashboard_ajax_context')) {
    /**
     * Detect the dashboard load-more action for expired/sold ads.
     *
     * @return bool
     */
    function bornado_is_expired_dashboard_ajax_context()
    {
        if (!wp_doing_ajax()) {
            return false;
        }

        $action  = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        $ad_type = isset($_POST['ad_type']) ? sanitize_key(wp_unslash($_POST['ad_type'])) : '';

        return $action === 'load_more_dashboard_ads' && $ad_type === 'expired_ads';
    }
}

if (!function_exists('bornado_get_expired_dashboard_meta_query')) {
    /**
     * Query only ads whose lifecycle state is truly expired or sold.
     *
     * @return array<int|string,mixed>
     */
    function bornado_get_expired_dashboard_meta_query()
    {
        return array(
            'relation' => 'OR',
            array(
                'key'     => '_adforest_ad_status_',
                'value'   => 'expired',
                'compare' => '=',
            ),
            array(
                'key'     => '_adforest_ad_status_',
                'value'   => 'sold',
                'compare' => '=',
            ),
        );
    }
}

if (!function_exists('bornado_fix_expired_dashboard_queries')) {
    /**
     * Keep expired/sold dashboard views from mixing ordinary drafts into the list.
     *
     * @param WP_Query $query Query instance about to run.
     * @return void
     */
    function bornado_fix_expired_dashboard_queries($query)
    {
        if (!($query instanceof WP_Query)) {
            return;
        }

        if (!bornado_is_expired_dashboard_view_context() && !bornado_is_expired_dashboard_ajax_context()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== 'ad_post' && (!is_array($post_type) || !in_array('ad_post', $post_type, true))) {
            return;
        }

        $author_id = (int) $query->get('author');
        if ($author_id > 0 && $author_id !== (int) get_current_user_id()) {
            return;
        }

        $post_status = $query->get('post_status');
        $allowed_statuses = array('draft', 'publish');

        if (is_string($post_status) && !in_array($post_status, $allowed_statuses, true)) {
            return;
        }

        if (is_array($post_status) && empty(array_intersect($allowed_statuses, array_map('strval', $post_status)))) {
            return;
        }

        $query->set('meta_query', bornado_get_expired_dashboard_meta_query());
    }
}
add_action('pre_get_posts', 'bornado_fix_expired_dashboard_queries', 25);

if (!function_exists('bornado_swap_ad_status_ajax_handler')) {
    /**
     * Route dashboard sold/expired requests through the child-theme guard first.
     *
     * @return void
     */
    function bornado_swap_ad_status_ajax_handler()
    {
        remove_action('wp_ajax_sb_update_ad_status', 'adforest_sb_update_ad_status');
        add_action('wp_ajax_sb_update_ad_status', 'bornado_handle_ad_status_update');
    }
}
add_action('after_setup_theme', 'bornado_swap_ad_status_ajax_handler', 50);

if (!function_exists('bornado_handle_ad_status_update')) {
    /**
     * Preserve parent behavior except for the new archive-redirect lifecycle mode.
     *
     * @return void
     */
    function bornado_handle_ad_status_update()
    {
        $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';

        if (!in_array($status, array('sold', 'expired'), true) || !bornado_should_archive_redirect_ad_status($status)) {
            adforest_sb_update_ad_status();
            return;
        }

        adforest_authenticate_check();

        $security = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';
        if (empty($security) || !wp_verify_nonce($security, 'sb_update_ad_status_nonce')) {
            echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
            die();
        }

        if (function_exists('adforest_is_demo') && adforest_is_demo()) {
            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }

        $ad_id = isset($_POST['ad_id']) ? absint($_POST['ad_id']) : 0;
        if ($ad_id < 1) {
            echo '0|' . esc_html__("Invalid ad data received.", 'adforest');
            die();
        }

        $previous_status = (string) get_post_meta($ad_id, '_adforest_ad_status_', true);
        if ($previous_status === $status) {
            echo '0| ' . esc_html__("Already ", 'adforest') . $previous_status;
            die();
        }

        $post = get_post($ad_id);
        if (!$post instanceof WP_Post || $post->post_type !== 'ad_post') {
            echo '0|' . esc_html__("Invalid ad data received.", 'adforest');
            die();
        }

        if (!current_user_can('edit_post', $ad_id)) {
            echo '0|' . esc_html__("You are not allowed to update this ad.", 'adforest');
            die();
        }

        $target_url = bornado_transition_ad_to_archive_redirect_state($ad_id, $status);
        if ($target_url === '') {
            echo '0|' . esc_html__("Unable to update this ad right now.", 'adforest');
            die();
        }

        echo '1|' . esc_html__("Updated successfully.", 'adforest');
        die();
    }
}

add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if (
        !($post instanceof WP_Post)
        || $post->post_type !== 'ad_post'
        || $new_status !== 'publish'
        || $old_status === 'publish'
    ) {
        return;
    }

    bornado_clear_ad_archive_redirect_meta((int) $post->ID);
}, 20, 3);
