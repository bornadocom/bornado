<?php
// Override the functions and classes of parent theme here.

if (!function_exists('bornado_pick_fallback_image_for_ad_category')) {
    /**
     * Pick one random fallback attachment ID based on ad category.
     */
    function bornado_pick_fallback_image_for_ad_category($post_id)
    {
        $category_image_map = array(
            339 => array(2322, 2323), // استخدام و کاریابی
            338 => array(2324, 2325), // املاک
            341 => array(2326, 2327), // خدمات
            342 => array(2320, 2321), // کالا و لوازم
            340 => array(2328, 2329),       // وسایل نقلیه
            343 => array(2318, 2319), // اجتماعی
        );

        /**
         * Let future customizations extend/replace category image mapping.
         * Format: [term_id => [attachment_id, ...], ...]
         */
        $category_image_map = apply_filters('bornado_ad_category_fallback_images', $category_image_map, $post_id);
        if (empty($category_image_map) || !is_array($category_image_map)) {
            return 0;
        }

        $terms = wp_get_post_terms((int) $post_id, 'ad_cats');
        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        // Try deeper terms first, then fall back to their ancestors.
        usort($terms, function ($a, $b) {
            $depth_a = count(get_ancestors((int) $a->term_id, 'ad_cats'));
            $depth_b = count(get_ancestors((int) $b->term_id, 'ad_cats'));
            return $depth_b <=> $depth_a;
        });

        foreach ($terms as $term) {
            $term_chain = array_merge(array((int) $term->term_id), array_map('intval', get_ancestors((int) $term->term_id, 'ad_cats')));

            foreach ($term_chain as $term_id) {
                if (empty($category_image_map[$term_id]) || !is_array($category_image_map[$term_id])) {
                    continue;
                }

                $candidate_ids = array_values(array_filter(array_map('intval', $category_image_map[$term_id])));
                if (empty($candidate_ids)) {
                    continue;
                }

                $random_attachment_id = $candidate_ids[array_rand($candidate_ids)];
                if ($random_attachment_id > 0 && wp_attachment_is_image($random_attachment_id)) {
                    return $random_attachment_id;
                }
            }
        }

        return 0;
    }
}

if (!function_exists('adforest_get_ad_images')) {
    if (!function_exists('bornado_inline_edit_debug_log')) {
        /**
         * Write inline-edit image debugging entries to uploads.
         *
         * @param string $event   Short event label.
         * @param array  $payload Structured diagnostic data.
         * @return void
         */
        function bornado_inline_edit_debug_log($event, array $payload = array())
        {
            $uploads = wp_upload_dir();
            $base_dir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
            if ($base_dir === '') {
                return;
            }

            $log_path = trailingslashit($base_dir) . 'bornado-inline-edit-debug.log';
            $entry = array(
                'time'    => current_time('mysql'),
                'event'   => (string) $event,
                'request' => isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '',
                'payload' => $payload,
            );

            $line = wp_json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($line) && $line !== '') {
                @file_put_contents($log_path, $line . PHP_EOL, FILE_APPEND);
            }
        }
    }

    if (!function_exists('bornado_inline_edit_use_live_gallery_source')) {
        /**
         * During inline-edit AJAX, always read the live gallery state instead of
         * the cached `_bornado_inline_gallery_ids` snapshot.
         *
         * The snapshot is correct for normal display after save, but while the
         * owner is actively editing, AdForest's upload/delete endpoints mutate
         * `_sb_photo_arrangement_` immediately. If helper AJAX keeps reading the
         * snapshot, it can hand stale ids back to the editor and resurrect a
         * deleted image on the final save.
         *
         * @return bool
         */
        function bornado_inline_edit_use_live_gallery_source()
        {
            if (!wp_doing_ajax()) {
                return false;
            }

            $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '';

            return in_array($action, array('get_uploaded_ad_images', 'delete_ad_image', 'bornado_sync_ad_images'), true);
        }
    }

    if (!function_exists('bornado_inline_edit_get_live_gallery_ids')) {
        /**
         * Resolve the current gallery ids directly from the live AdForest source.
         *
         * Order comes from `_sb_photo_arrangement_` when available; otherwise we
         * fall back to the currently attached media.
         *
         * @param int $pid Ad post ID.
         * @return array<int>
         */
        function bornado_inline_edit_get_live_gallery_ids($pid)
        {
            $pid = (int) $pid;
            if ($pid < 1) {
                return array();
            }

            $re_order = get_post_meta($pid, '_sb_photo_arrangement_', true);
            if ($re_order !== '') {
                $ordered_ids = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $re_order)))));
                if (!empty($ordered_ids)) {
                    return $ordered_ids;
                }
            }

            $attached_media = get_attached_media('', $pid);
            if (!empty($attached_media)) {
                return array_values(array_map('intval', array_keys($attached_media)));
            }

            return array();
        }
    }

    /**
     * Child-theme override:
     * - Keep original behavior for real ad images.
     * - If no image exists, inject one random category fallback attachment.
     */
    function adforest_get_ad_images($pid)
    {
        $use_live_source = bornado_inline_edit_use_live_gallery_source();
        $explicitly_empty = ('1' === (string) get_post_meta($pid, '_bornado_gallery_explicitly_empty', true));

        if (!$use_live_source && metadata_exists('post', $pid, '_bornado_inline_gallery_ids')) {
            $bornado_inline_order = (string) get_post_meta($pid, '_bornado_inline_gallery_ids', true);
            if ($bornado_inline_order !== '') {
                $bornado_inline_ids = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $bornado_inline_order)))));
                if (!empty($bornado_inline_ids)) {
                    return $bornado_inline_ids;
                }
            }
        }

        $live_ids = bornado_inline_edit_get_live_gallery_ids($pid);
        if ($use_live_source) {
            bornado_inline_edit_debug_log(
                'ad_images_live_source',
                array(
                    'ad_id'               => (int) $pid,
                    'live_ids'            => $live_ids,
                    'inline_cached_ids'   => (string) get_post_meta($pid, '_bornado_inline_gallery_ids', true),
                    'arrangement_meta'    => (string) get_post_meta($pid, '_sb_photo_arrangement_', true),
                    'explicitly_empty'    => (string) get_post_meta($pid, '_bornado_gallery_explicitly_empty', true),
                    'attached_media_keys' => array_values(array_map('intval', array_keys((array) get_attached_media('', $pid)))),
                )
            );
        }
        if (!empty($live_ids)) {
            return $live_ids;
        }

        // Important distinction:
        // - During inline-edit AJAX, an explicitly empty gallery must stay empty
        //   so the editor does not treat the category fallback as a real image.
        // - During normal front-end display, "no real images" should still fall
        //   back to the category-specific placeholder selected in the child theme.
        if ($use_live_source && $explicitly_empty) {
            return array();
        }

        $fallback_attachment_id = bornado_pick_fallback_image_for_ad_category($pid);
        if ($fallback_attachment_id > 0) {
            return array($fallback_attachment_id);
        }

        // Returning an empty list keeps AdForest's default no-image fallback behavior.
        return array();
    }
}

/**
 * Show Negotiable (توافقی) for ads without a price before parent helpers load.
 * Search cards also need the MU bootstrap in bornado-search-core/mu-plugin-loader.php
 * because get_ad_post_details() is defined by Elementor before this file runs.
 */
$bornado_empty_ad_price_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-empty-ad-price.php';
if (file_exists($bornado_empty_ad_price_bootstrap) && !function_exists('bornado_get_negotiable_price_label')) {
    require_once $bornado_empty_ad_price_bootstrap;
}

/**
 * Load Search Core compatibility shims before the parent theme defines pluggable helpers.
 */
$bornado_search_compat_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-search-compat.php';
if (file_exists($bornado_search_compat_bootstrap)) {
    require_once $bornado_search_compat_bootstrap;
}

/**
 * Keep full page refresh search queries in sync with Search 2.0 defaults.
 */
$bornado_public_search_query_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-public-search-query-fix.php';
if (file_exists($bornado_public_search_query_fix_bootstrap)) {
    require_once $bornado_public_search_query_fix_bootstrap;
}

/**
 * Load semantic breadcrumb override before the parent theme defines its pluggable function.
 */
$bornado_breadcrumb_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-breadcrumbs.php';
if (file_exists($bornado_breadcrumb_bootstrap)) {
    require_once $bornado_breadcrumb_bootstrap;
}

/**
 * Respect category-template Show/Hide flags in the category search sidebar.
 */
$bornado_category_search_sidebar_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-category-search-sidebar.php';
if (file_exists($bornado_category_search_sidebar_bootstrap)) {
    require_once $bornado_category_search_sidebar_bootstrap;
}

/**
 * Show full titles in AdForest Recent Ads sidebar widget.
 */
$bornado_recent_ads_sidebar_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-recent-ads-sidebar.php';
if (file_exists($bornado_recent_ads_sidebar_bootstrap)) {
    require_once $bornado_recent_ads_sidebar_bootstrap;
}

/**
 * Register a reusable AdForest sort dropdown widget without touching parent files.
 */
$bornado_sort_filters_widget_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-sort-filters-widget.php';
if (file_exists($bornado_sort_filters_widget_bootstrap)) {
    require_once $bornado_sort_filters_widget_bootstrap;
}

/**
 * Keep ad currency aligned with the selected country/city without touching theme core files.
 */
$bornado_ad_currency_sync_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-currency-sync.php';
if (file_exists($bornado_ad_currency_sync_bootstrap)) {
    require_once $bornado_ad_currency_sync_bootstrap;
}

/**
 * Shared phone-country helpers for ad post, auth modal, and profile UX.
 */
$bornado_phone_support_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-phone-support.php';
if (file_exists($bornado_phone_support_bootstrap)) {
    require_once $bornado_phone_support_bootstrap;
}

/**
 * Keep ad phone numbers aligned with the selected country/city without touching theme core files.
 */
$bornado_ad_phone_sync_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-phone-sync.php';
if (file_exists($bornado_ad_phone_sync_bootstrap)) {
    require_once $bornado_ad_phone_sync_bootstrap;
}

/**
 * Keep legacy AdForest taxonomy-backed meta aligned for API/admin edits.
 */
$bornado_ad_taxonomy_meta_sync_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-taxonomy-meta-sync.php';
if (file_exists($bornado_ad_taxonomy_meta_sync_bootstrap)) {
    require_once $bornado_ad_taxonomy_meta_sync_bootstrap;
}

/**
 * Load custom header clone integration from child theme directory.
 */
$bornado_header_clone_bootstrap = trailingslashit(get_stylesheet_directory()) . 'adforest-header-search-4-clone/adforest-header-search-4-clone.php';
if (file_exists($bornado_header_clone_bootstrap)) {
    require_once $bornado_header_clone_bootstrap;
}

/**
 * Manage per-ad contact method selections from the child theme layer.
 */
$bornado_ad_contact_methods_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-contact-methods.php';
if (file_exists($bornado_ad_contact_methods_bootstrap)) {
    require_once $bornado_ad_contact_methods_bootstrap;
}

/**
 * Protect the ad-post form UX from the child theme layer.
 */
$bornado_ad_post_guard_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-post-guard.php';
if (file_exists($bornado_ad_post_guard_bootstrap)) {
    require_once $bornado_ad_post_guard_bootstrap;
}

/**
 * Keep dashboard profile phone UX aligned with the selected country dial code.
 */
$bornado_profile_phone_guard_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-profile-phone-guard.php';
if (file_exists($bornado_profile_phone_guard_bootstrap)) {
    require_once $bornado_profile_phone_guard_bootstrap;
}

/**
 * Allow search filters to be cleared with a second click from the child theme.
 */
$bornado_search_filter_toggle_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-search-filter-toggle-fix.php';
if (file_exists($bornado_search_filter_toggle_fix_bootstrap)) {
    require_once $bornado_search_filter_toggle_fix_bootstrap;
}

/**
 * Centralize feature/bump visibility and guard the related actions.
 */
$bornado_promotion_visibility_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-promotion-visibility.php';
if (file_exists($bornado_promotion_visibility_bootstrap)) {
    require_once $bornado_promotion_visibility_bootstrap;
}

/**
 * Fix Modern My Listings "Active" filter without touching parent theme files.
 */
$bornado_my_listings_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-my-listings-fix.php';
if (file_exists($bornado_my_listings_fix_bootstrap)) {
    require_once $bornado_my_listings_fix_bootstrap;
}

/**
 * Keep RTL phone numbers visually stable across frontend views.
 */
$bornado_phone_display_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-phone-display-fix.php';
if (file_exists($bornado_phone_display_fix_bootstrap)) {
    require_once $bornado_phone_display_fix_bootstrap;
}

/**
 * Normalize Persian/Arabic numerals for numeric-like user input.
 */
$bornado_numeric_normalization_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-numeric-normalization.php';
if (file_exists($bornado_numeric_normalization_bootstrap)) {
    require_once $bornado_numeric_normalization_bootstrap;
}

/**
 * Keep performance-oriented asset overrides in the child theme layer.
 */
$bornado_performance_optimizations_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-performance-optimizations.php';
if (file_exists($bornado_performance_optimizations_bootstrap)) {
    require_once $bornado_performance_optimizations_bootstrap;
}

/**
 * Keep listing infinite scroll DOM-bounded without touching parent theme files.
 */
$bornado_windowed_infinite_scroll_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-search-windowed-infinite-scroll.php';
if (file_exists($bornado_windowed_infinite_scroll_bootstrap)) {
    require_once $bornado_windowed_infinite_scroll_bootstrap;
}

/**
 * Force legacy edit-ad links onto the modern Add New page.
 */
$bornado_edit_ad_link_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-edit-ad-link-fix.php';
if (file_exists($bornado_edit_ad_link_fix_bootstrap)) {
    require_once $bornado_edit_ad_link_fix_bootstrap;
}

/**
 * Add a third single-ad layout from the child theme layer.
 */
$bornado_single_ad_style_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornad-single-ad-style.php';
if (file_exists($bornado_single_ad_style_bootstrap)) {
    require_once $bornado_single_ad_style_bootstrap;
}

/**
 * Reusable wheel picker infrastructure shared across Bornado flows.
 */
$bornado_wheel_picker_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-wheel-picker.php';
if (file_exists($bornado_wheel_picker_bootstrap)) {
    require_once $bornado_wheel_picker_bootstrap;
}

/**
 * In-place ad editor: edit an ad directly on its single-ad page.
 */
$bornado_inline_ad_edit_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-inline-ad-edit.php';
if (file_exists($bornado_inline_ad_edit_bootstrap)) {
    require_once $bornado_inline_ad_edit_bootstrap;
}

/**
 * Load SB Chat message bubble/timestamp customizations from the child theme.
 */
$bornado_sb_chat_profile_bubbles_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-sb-chat-profile-bubbles.php';
if (file_exists($bornado_sb_chat_profile_bubbles_bootstrap)) {
    require_once $bornado_sb_chat_profile_bubbles_bootstrap;
}

if (!function_exists('bornado_filter_unpublished_ad_post_permalink')) {
    /**
     * Serve a real WordPress preview URL for unpublished ads when the current
     * user is allowed to edit them.
     *
     * AdForest frequently calls get_permalink() for dashboard cards, pending
     * listings, and post-submit redirects. For draft/pending ads that produces
     * the public single URL, which naturally 404s. Swapping only those links to
     * preview URLs keeps published ads unchanged while restoring private author
     * previews everywhere the theme reuses get_permalink().
     *
     * @param string      $post_link Generated permalink.
     * @param WP_Post     $post      Post object being linked.
     * @param bool        $leavename Whether to keep the post name.
     * @param bool        $sample    Whether this is a sample permalink.
     * @return string
     */
    function bornado_filter_unpublished_ad_post_permalink($post_link, $post, $leavename = false, $sample = false)
    {
        static $preview_link_in_progress = false;

        if ($preview_link_in_progress || $sample || !($post instanceof WP_Post)) {
            return $post_link;
        }

        if ('ad_post' !== $post->post_type) {
            return $post_link;
        }

        if (is_admin() && !wp_doing_ajax()) {
            return $post_link;
        }

        if ('publish' === $post->post_status || 'trash' === $post->post_status || 'auto-draft' === $post->post_status) {
            return $post_link;
        }

        if (!current_user_can('edit_post', $post->ID)) {
            return $post_link;
        }

        $preview_link_in_progress = true;
        $preview_link             = get_preview_post_link($post);
        $preview_link_in_progress = false;

        if (is_string($preview_link) && $preview_link !== '') {
            return $preview_link;
        }

        return $post_link;
    }

    add_filter('post_type_link', 'bornado_filter_unpublished_ad_post_permalink', 20, 4);
}

if (!function_exists('bornado_get_safe_login_redirect_url')) {
    /**
     * Build a resilient login URL with a safe post-login redirect target.
     *
     * Some single-ad templates call this helper directly for guests. Keep the
     * function available in the child theme so public ad pages never fatally
     * error when an auxiliary auth helper is unavailable.
     *
     * @param string $redirect_url Target URL after login.
     * @return string
     */
    function bornado_get_safe_login_redirect_url($redirect_url = '')
    {
        $fallback_url = home_url('/');
        $redirect_url = is_string($redirect_url) ? trim($redirect_url) : '';

        if ('' === $redirect_url && function_exists('adforest_get_current_url')) {
            $redirect_url = (string) adforest_get_current_url();
        }

        $redirect_url = wp_validate_redirect($redirect_url, $fallback_url);

        if (function_exists('adforest_login_with_redirect_url_param')) {
            $login_url = (string) adforest_login_with_redirect_url_param(rawurlencode($redirect_url));
            if ('' !== $login_url) {
                return $login_url;
            }
        }

        if (function_exists('bornado_auth_modal_fallback_url')) {
            $login_url = (string) bornado_auth_modal_fallback_url('login');
            if ('' !== $login_url && '#' !== $login_url) {
                return add_query_arg('redirect_to', $redirect_url, $login_url);
            }
        }

        return wp_login_url($redirect_url);
    }
}

if (!function_exists('bornado_allow_unpublished_ad_preview_queries')) {
    /**
     * Extend single-ad preview queries so draft/private ads can load for users
     * who are allowed to edit the requested ad.
     *
     * The parent theme narrows single `ad_post` queries to `publish|pending`
     * only. That still leaves WordPress previews of draft/private ads resolving
     * to 404 even when the hash route is correct. Keep the expansion tightly
     * scoped to explicit preview requests for the targeted ad ID.
     *
     * @param WP_Query $query Query instance.
     * @return void
     */
    function bornado_allow_unpublished_ad_preview_queries($query)
    {
        if (!($query instanceof WP_Query) || is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== 'ad_post' && (!is_array($post_type) || !in_array('ad_post', $post_type, true))) {
            return;
        }

        $post_id = (int) $query->get('p');
        if ($post_id < 1) {
            return;
        }

        $preview_flag = isset($_GET['preview']) ? strtolower(trim((string) wp_unslash($_GET['preview']))) : '';
        if (!in_array($preview_flag, array('1', 'true', 'yes'), true)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $query->set('post_status', array('publish', 'pending', 'draft', 'private'));
        $query->set('perm', 'readable');
    }

    add_action('pre_get_posts', 'bornado_allow_unpublished_ad_preview_queries', 30);
}

if (!function_exists('bornado_flag_ad_search_template')) {
    /**
     * Remember the resolved front-end template (set on template_include after Bornado routing).
     *
     * @param string $template Absolute template path.
     * @return string
     */
    function bornado_flag_ad_search_template($template)
    {
        $GLOBALS['bornado_active_template'] = is_string($template) ? $template : '';

        return $template;
    }

    add_filter('template_include', 'bornado_flag_ad_search_template', 100);
}

if (!function_exists('bornado_is_ad_search_view')) {
    /**
     * True when the Ad Search listing UI is actually being rendered.
     */
    function bornado_is_ad_search_view()
    {
        $active_template = isset($GLOBALS['bornado_active_template']) ? (string) $GLOBALS['bornado_active_template'] : '';

        if ($active_template !== '') {
            if (false !== strpos($active_template, 'page-search.php')) {
                return true;
            }
            if (false !== strpos($active_template, 'seo-landing.php')) {
                return true;
            }
        }

        if (is_page_template('page-search.php')) {
            return true;
        }

        if (class_exists('Bornado_SEO_Routing') && method_exists('Bornado_SEO_Routing', 'is_valid_semantic_route')) {
            if (Bornado_SEO_Routing::is_valid_semantic_route()) {
                return true;
            }
        }

        if (function_exists('bornado_is_query_only_ad_search_bridge_active') && bornado_is_query_only_ad_search_bridge_active()) {
            return true;
        }

        return false;
    }
}

if (!function_exists('bornado_get_search_page_id')) {
    /**
     * Resolve the configured AdForest search page ID.
     *
     * @return int
     */
    function bornado_get_search_page_id()
    {
        global $adforest_theme;

        $page_id = isset($adforest_theme['sb_search_page']) ? (int) $adforest_theme['sb_search_page'] : 0;
        if ($page_id < 1) {
            $theme_opts = get_option('adforest_theme', array());
            if (is_array($theme_opts) && !empty($theme_opts['sb_search_page'])) {
                $page_id = (int) $theme_opts['sb_search_page'];
            }
        }

        return max(0, $page_id);
    }
}

if (!function_exists('bornado_get_public_ad_search_filter_keys')) {
    /**
     * Query-string keys that should activate the root-URL search bridge.
     *
     * @return array<int,string>
     */
    function bornado_get_public_ad_search_filter_keys()
    {
        $keys = array(
            'ad_title',
            'cat_id',
            'country_id',
            'ad_currency',
            'condition',
            'ad_type',
            'adtype',
            'warranty',
            'ad',
            'sort',
            'c',
            'min_price',
            'max_price',
            'location',
            'rd',
            'lat',
            'long',
            'view-type',
            'page-number',
            'paged',
            'custom',
            'min_custom',
            'max_custom',
        );

        return apply_filters('bornado_public_ad_search_filter_keys', $keys);
    }
}

if (!function_exists('bornado_has_non_empty_query_value')) {
    /**
     * Determine whether a query value contains meaningful filter data.
     *
     * @param mixed $value Raw query value.
     * @return bool
     */
    function bornado_has_non_empty_query_value($value)
    {
        if (is_array($value)) {
            foreach ($value as $child) {
                if (bornado_has_non_empty_query_value($child)) {
                    return true;
                }
            }

            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (null === $value) {
            return false;
        }

        return trim((string) $value) !== '';
    }
}

if (!function_exists('bornado_normalize_request_path_for_search_bridge')) {
    /**
     * Normalize a request path relative to the site's home path.
     *
     * @param string $path Absolute request path.
     * @return string
     */
    function bornado_normalize_request_path_for_search_bridge($path)
    {
        $path = trim((string) $path, '/');

        $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
        $home_path = trim((string) $home_path, '/');

        if ($home_path !== '') {
            if ($path === $home_path) {
                return '';
            }

            $prefix = $home_path . '/';
            if (strpos($path, $prefix) === 0) {
                $path = substr($path, strlen($prefix));
            }
        }

        return trim((string) $path, '/');
    }
}

if (!function_exists('bornado_is_query_only_ad_search_request')) {
    /**
     * True when a root URL like `/?max_price=...` should render the Ad Search page.
     *
     * @return bool
     */
    function bornado_is_query_only_ad_search_request()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || wp_is_json_request()
        ) {
            return false;
        }

        if (empty($_GET) || !is_array($_GET)) {
            return false;
        }

        if (bornado_get_search_page_id() < 1) {
            return false;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_path = is_string($request_uri) ? wp_parse_url($request_uri, PHP_URL_PATH) : '';
        $request_path = bornado_normalize_request_path_for_search_bridge((string) $request_path);
        if ($request_path !== '') {
            return false;
        }

        $search_keys = bornado_get_public_ad_search_filter_keys();
        foreach ($search_keys as $key) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            if (isset($_GET[$key]) && bornado_has_non_empty_query_value(wp_unslash($_GET[$key]))) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_set_query_only_ad_search_bridge')) {
    /**
     * Store bridge state for the current request.
     *
     * @param int $page_id Search page ID.
     * @return void
     */
    function bornado_set_query_only_ad_search_bridge($page_id)
    {
        $page_id = (int) $page_id;
        if ($page_id < 1) {
            return;
        }

        $GLOBALS['bornado_query_only_ad_search_bridge'] = array(
            'active'  => true,
            'page_id' => $page_id,
        );
    }
}

if (!function_exists('bornado_is_query_only_ad_search_bridge_active')) {
    /**
     * Whether the current request is using the root-query search bridge.
     *
     * @return bool
     */
    function bornado_is_query_only_ad_search_bridge_active()
    {
        return !empty($GLOBALS['bornado_query_only_ad_search_bridge']['active']);
    }
}

add_filter('request', function ($query_vars) {
    if (!bornado_is_query_only_ad_search_request()) {
        return $query_vars;
    }

    $search_page_id = bornado_get_search_page_id();
    if ($search_page_id < 1) {
        return $query_vars;
    }

    $search_page = get_post($search_page_id);
    if (!($search_page instanceof WP_Post)) {
        return $query_vars;
    }

    bornado_set_query_only_ad_search_bridge($search_page_id);

    $query_vars['page_id'] = $search_page_id;
    unset(
        $query_vars['error'],
        $query_vars['pagename'],
        $query_vars['name'],
        $query_vars['attachment'],
        $query_vars['attachment_id']
    );

    return $query_vars;
}, 20);

add_action('template_redirect', function () {
    if (!bornado_is_query_only_ad_search_bridge_active()) {
        return;
    }

    $search_page_id = !empty($GLOBALS['bornado_query_only_ad_search_bridge']['page_id'])
        ? (int) $GLOBALS['bornado_query_only_ad_search_bridge']['page_id']
        : bornado_get_search_page_id();

    $search_page = get_post($search_page_id);
    if (!($search_page instanceof WP_Post)) {
        return;
    }

    global $wp_query;

    if (!($wp_query instanceof WP_Query)) {
        return;
    }

    $wp_query->queried_object_id = $search_page->ID;
    $wp_query->queried_object = $search_page;
    $wp_query->posts = array($search_page);
    $wp_query->post = $search_page;
    $wp_query->found_posts = 1;
    $wp_query->post_count = 1;
    $wp_query->max_num_pages = 1;
    $wp_query->is_404 = false;
    $wp_query->is_home = false;
    $wp_query->is_front_page = false;
    $wp_query->is_page = true;
    $wp_query->is_singular = true;
    $wp_query->is_single = false;
    $wp_query->is_archive = false;
    $wp_query->is_search = false;

    status_header(200);
}, 0);

add_filter('template_include', function ($template) {
    if (!bornado_is_query_only_ad_search_bridge_active()) {
        return $template;
    }

    $search_template = locate_template(array('page-search.php'));
    return $search_template ? $search_template : $template;
}, 95);

add_filter('adforest_ajax_search_should_enqueue', function ($should) {
    return $should || bornado_is_query_only_ad_search_bridge_active();
});

if (!function_exists('bornado_get_search_card_deepest_location_name')) {
    /**
     * Resolve the deepest assigned AdForest location term for one ad card.
     *
     * @param int $post_id Ad post ID.
     * @return string
     */
    function bornado_get_search_card_deepest_location_name($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return '';
        }

        $terms = wp_get_post_terms($post_id, 'ad_country');
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        usort($terms, static function ($left, $right) {
            $left_depth  = count(get_ancestors((int) $left->term_id, 'ad_country', 'taxonomy'));
            $right_depth = count(get_ancestors((int) $right->term_id, 'ad_country', 'taxonomy'));

            return $right_depth <=> $left_depth;
        });

        $term = reset($terms);

        if ($term instanceof WP_Term && !empty($term->name)) {
            return (string) $term->name;
        }

        $location_meta = trim((string) get_post_meta($post_id, '_adforest_ad_location', true));

        return $location_meta;
    }
}

if (!function_exists('bornado_get_search_card_relative_posted_label')) {
    /**
     * Build a short Persian relative posted-time label for search cards.
     *
     * @param int $post_id Ad post ID.
     * @return string
     */
    function bornado_get_search_card_relative_posted_label($post_id)
    {
        if (function_exists('mcew_get_relative_posted_label')) {
            return (string) mcew_get_relative_posted_label($post_id);
        }

        $from = (int) get_post_time('U', true, $post_id);
        if ($from <= 0) {
            return '';
        }

        $to   = (int) current_time('timestamp');
        $diff = abs($to - $from);

        if ($diff >= (15 * DAY_IN_SECONDS)) {
            return date_i18n('j F Y', $from);
        }

        if ($diff < HOUR_IN_SECONDS) {
            $mins = max(1, (int) round($diff / MINUTE_IN_SECONDS));

            return sprintf('%s دقیقه پیش', number_format_i18n($mins));
        }

        if ($diff < DAY_IN_SECONDS) {
            $hours = max(1, (int) round($diff / HOUR_IN_SECONDS));

            return sprintf('%s ساعت پیش', number_format_i18n($hours));
        }

        if ($diff < YEAR_IN_SECONDS) {
            $days = max(1, (int) round($diff / DAY_IN_SECONDS));

            return sprintf('%s روز پیش', number_format_i18n($days));
        }

        $years = max(1, (int) round($diff / YEAR_IN_SECONDS));

        return sprintf('%s سال پیش', number_format_i18n($years));
    }
}

if (!function_exists('bornado_get_search_card_posted_location_text')) {
    /**
     * Build the combined "time ago in city" label used under search-card prices.
     *
     * @param int $post_id Ad post ID.
     * @return string
     */
    function bornado_get_search_card_posted_location_text($post_id)
    {
        $posted_label = trim((string) bornado_get_search_card_relative_posted_label($post_id));
        $city_label   = trim((string) bornado_get_search_card_deepest_location_name($post_id));

        if ('' === $posted_label) {
            return $city_label;
        }

        if ('' === $city_label) {
            return $posted_label;
        }

        return $posted_label . ' در ' . $city_label;
    }
}

if (!function_exists('bornado_get_theme_style_handles')) {
    /**
     * Parent style handles to load child overrides after (whichever is registered).
     *
     * @return string[]
     */
    function bornado_get_theme_style_handles()
    {
        $handles = array('adforest-main-responsive', 'adforest-main', 'adforest-style', 'adforest-pro-style');
        $deps    = array();

        foreach ($handles as $handle) {
            if (wp_style_is($handle, 'registered') || wp_style_is($handle, 'enqueued')) {
                $deps[] = $handle;
            }
        }

        return $deps;
    }
}

/**
 * Mark Ad Search views so layout CSS applies on semantic URLs too (not only page-template body class).
 */
add_filter('body_class', function ($classes) {
    if (bornado_is_ad_search_view()) {
        $classes[] = 'bornado-ad-search-view';
    }

    return $classes;
});

/**
 * Header + Ad Search layout CSS (late priority so parent/Redux styles load first).
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    $deps = bornado_get_theme_style_handles();
    $current_header_style = function_exists('bornado_header_clone_get_page_header_style')
        ? (string) bornado_header_clone_get_page_header_style()
        : '';
    $needs_mobile_choice_ui = bornado_is_ad_search_view()
        || (
            defined('BORNADO_HEADER_SEARCH_4_CLONE_KEY')
            && $current_header_style === (string) BORNADO_HEADER_SEARCH_4_CLONE_KEY
        );

    $header_css = get_stylesheet_directory() . '/assets/css/bornado-header-layout.css';
    if (file_exists($header_css)) {
        wp_enqueue_style(
            'bornado-header-layout',
            get_stylesheet_directory_uri() . '/assets/css/bornado-header-layout.css',
            $deps,
            (string) filemtime($header_css)
        );
        $deps = array('bornado-header-layout');
    }

    $mobile_choice_ui_css = get_stylesheet_directory() . '/assets/css/bornado-mobile-choice-ui.css';
    if ($needs_mobile_choice_ui && file_exists($mobile_choice_ui_css)) {
        wp_enqueue_style(
            'bornado-mobile-choice-ui',
            get_stylesheet_directory_uri() . '/assets/css/bornado-mobile-choice-ui.css',
            $deps,
            (string) filemtime($mobile_choice_ui_css)
        );
        $deps = array('bornado-mobile-choice-ui');
    }

    if (!bornado_is_ad_search_view()) {
        return;
    }

    $search_css = get_stylesheet_directory() . '/assets/css/bornado-ad-search-layout.css';
    if (!file_exists($search_css)) {
        return;
    }

    wp_enqueue_style(
        'bornado-ad-search-layout',
        get_stylesheet_directory_uri() . '/assets/css/bornado-ad-search-layout.css',
        $deps,
        (string) filemtime($search_css)
    );

    $search_chip_labels_js = get_stylesheet_directory() . '/assets/js/bornado-search-chip-labels.js';
    if (file_exists($search_chip_labels_js)) {
        wp_enqueue_script(
            'bornado-search-chip-labels',
            get_stylesheet_directory_uri() . '/assets/js/bornado-search-chip-labels.js',
            array('adforest-search-ux'),
            (string) filemtime($search_chip_labels_js),
            true
        );
    }

}, 200);

/**
 * Bridge AdForest's legacy mobile filters drawer with Search 2.0's new drawer state.
 *
 * Parent theme currently has two parallel mobile-filter systems:
 * - legacy sidebar mode toggles `.mobile-filters.active`
 * - Search 2.0 toggles `body.adf-mobile-filters-open`
 *
 * On sidebar search pages with mobile filters enabled, keep those two states
 * in sync from the child theme layer so the new Filters button actually opens
 * the existing mobile drawer, without editing theme core files.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin() || !bornado_is_ad_search_view()) {
        return;
    }

    global $adforest_theme;

    $search_design = isset($adforest_theme['search_design']) ? (string) $adforest_theme['search_design'] : '';
    $mobile_filters_enabled = !empty($adforest_theme['search_design_sidebar_mob_filter']);

    if ($search_design !== 'sidebar' || !$mobile_filters_enabled) {
        return;
    }

    $css = '@media (max-width: 991px) {'
        . 'body.adf-mobile-filters-open #adforest-ajax-sidebar.mobile-filters {'
        . 'display: block !important;'
        . '}'
        . '}';

    wp_register_style('bornado-mobile-search-filter-bridge', false);
    wp_enqueue_style('bornado-mobile-search-filter-bridge');
    wp_add_inline_style('bornado-mobile-search-filter-bridge', $css);

    $js = "
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.body;
        var sidebar = document.getElementById('adforest-ajax-sidebar');
        if (!body || !sidebar || !sidebar.classList.contains('mobile-filters')) {
            return;
        }

        var syncing = false;
        var mobileBreakpoint = 992;

        function isMobileViewport() {
            return window.innerWidth < mobileBreakpoint;
        }

        function setOpenState(isOpen) {
            body.classList.toggle('adf-mobile-filters-open', !!isOpen);
            sidebar.classList.toggle('active', !!isOpen);
        }

        function syncFromBody() {
            if (syncing) return;
            syncing = true;
            sidebar.classList.toggle('active', body.classList.contains('adf-mobile-filters-open'));
            syncing = false;
        }

        function syncFromSidebar() {
            if (syncing) return;
            syncing = true;
            body.classList.toggle('adf-mobile-filters-open', sidebar.classList.contains('active'));
            syncing = false;
        }

        syncFromBody();

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || !target.closest) {
                return;
            }

            var openTrigger = target.closest('#adf-open-filters, .mobile-filters-btn a');
            if (openTrigger && isMobileViewport()) {
                event.preventDefault();
                setOpenState(!body.classList.contains('adf-mobile-filters-open'));
                return;
            }

            var closeTrigger = target.closest('.adf-filters-backdrop, .filter-close-btn, .close-sidebar');
            if (closeTrigger) {
                event.preventDefault();
                setOpenState(false);
            }
        });

        window.addEventListener('resize', function () {
            if (!isMobileViewport()) {
                setOpenState(false);
            }
        });

        if (typeof MutationObserver === 'undefined') {
            return;
        }

        new MutationObserver(syncFromBody).observe(body, {
            attributes: true,
            attributeFilter: ['class']
        });

        new MutationObserver(syncFromSidebar).observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });
    });
    ";

    wp_register_script('bornado-mobile-search-filter-bridge', '', array(), null, true);
    wp_enqueue_script('bornado-mobile-search-filter-bridge');
    wp_add_inline_script('bornado-mobile-search-filter-bridge', $js);
}, 210);

/**
 * Load the same price slider dependency outside the default Ad Search page.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    $should_load_rangeslider = bornado_is_ad_search_view()
        || ( function_exists( 'is_shop' ) && is_shop() );

    if ( ! $should_load_rangeslider ) {
        return;
    }

    wp_enqueue_style('rangeslider-css');
    wp_enqueue_script('rangeslider-min');
}, 100);

if (!function_exists('bornado_frontend_language_tag')) {
    /**
     * Keep frontend language markup explicit for SEO and accessibility.
     *
     * WordPress locale can stay on fa_IR for translations while frontend pages
     * emit a short BCP 47 language tag that matches the page content language.
     */
    function bornado_frontend_language_tag()
    {
        return apply_filters('bornado_frontend_language_tag', 'fa');
    }
}

if (!function_exists('bornado_schema_entity_has_type')) {
    /**
     * Check whether a schema graph node contains one of the expected types.
     *
     * @param mixed        $entity
     * @param array<int,string> $expected_types
     * @return bool
     */
    function bornado_schema_entity_has_type($entity, array $expected_types)
    {
        if (!is_array($entity) || empty($entity['@type'])) {
            return false;
        }

        $types = is_array($entity['@type']) ? $entity['@type'] : array($entity['@type']);
        foreach ($types as $type) {
            if (in_array($type, $expected_types, true)) {
                return true;
            }
        }

        return false;
    }
}

add_filter('language_attributes', function ($output, $doctype) {
    if (is_admin()) {
        return $output;
    }

    $language_tag = bornado_frontend_language_tag();
    if (!is_string($language_tag) || $language_tag === '') {
        return $output;
    }

    $attributes = 'dir="' . esc_attr(is_rtl() ? 'rtl' : 'ltr') . '" lang="' . esc_attr($language_tag) . '"';
    if ($doctype === 'xhtml') {
        $attributes .= ' xml:lang="' . esc_attr($language_tag) . '"';
    }

    return $attributes;
}, 20, 2);

add_filter('rank_math/json_ld', function ($data, $json_ld) {
    if (is_admin() || !is_array($data)) {
        return $data;
    }

    $language_tag = bornado_frontend_language_tag();
    $route_context = function_exists('bornado_seo_routing_get_context') ? bornado_seo_routing_get_context() : array();
    $country_term = !empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term
        ? $route_context['country_term']
        : null;
    $city_term = !empty($route_context['city_term']) && $route_context['city_term'] instanceof WP_Term
        ? $route_context['city_term']
        : null;
    $country_data = $country_term instanceof WP_Term && function_exists('bornado_get_country_data')
        ? bornado_get_country_data($country_term)
        : array();

    foreach ($data as $key => $entity) {
        if (!is_array($entity)) {
            continue;
        }

        if (
            bornado_schema_entity_has_type($entity, array('WebSite', 'WebPage', 'CollectionPage', 'Article', 'BlogPosting', 'ItemPage'))
            && empty($entity['inLanguage'])
        ) {
            $data[$key]['inLanguage'] = $language_tag;
        }

        if (
            $country_term instanceof WP_Term
            && bornado_schema_entity_has_type($entity, array('WebPage', 'CollectionPage', 'Article', 'BlogPosting', 'ItemPage'))
            && empty($entity['contentLocation'])
        ) {
            $content_location = array(
                '@type' => 'Place',
                'name'  => $country_term->name,
            );

            if ($city_term instanceof WP_Term) {
                $content_location['name'] = $city_term->name . ', ' . $country_term->name;
                $content_location['address'] = array(
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $city_term->name,
                    'addressCountry'  => !empty($country_data['country_code']) ? $country_data['country_code'] : $country_term->name,
                );
            }

            $data[$key]['contentLocation'] = $content_location;
        }
    }

    return $data;
}, 20, 2);

if (!function_exists('bornado_is_public_seo_request')) {
    /**
     * Limit SEO surface changes to real frontend HTML requests.
     *
     * @return bool
     */
    function bornado_is_public_seo_request()
    {
        return !is_admin()
            && !wp_doing_ajax()
            && !wp_doing_cron()
            && !(defined('REST_REQUEST') && REST_REQUEST)
            && !wp_is_json_request();
    }
}

if (!function_exists('bornado_frontend_locale')) {
    /**
     * Canonical frontend locale for a Persian-first single-site install.
     *
     * @return string
     */
    function bornado_frontend_locale()
    {
        return apply_filters('bornado_frontend_locale', 'fa_IR');
    }
}

add_filter('locale', function ($locale) {
    if (!bornado_is_public_seo_request()) {
        return $locale;
    }

    if (is_string($locale) && preg_match('/^fa(?:_|$)/i', $locale)) {
        return $locale;
    }

    return bornado_frontend_locale();
}, 20);

if (!function_exists('bornado_override_adforest_edit_labels')) {
    /**
     * Force a small set of AdForest edit-form labels into Persian from the child
     * theme, even when the loaded theme .mo does not resolve them as expected.
     *
     * The source strings live in `adforest/inc/adforest_ad_post.php` and are
     * normally translated through the `adforest` textdomain. We keep the fix
     * local to the child theme so AdForest core files remain untouched.
     *
     * @param string $translated Already translated text.
     * @param string $text       Original source string.
     * @param string $domain     Text domain.
     * @return string
     */
    function bornado_override_adforest_edit_labels($translated, $text, $domain)
    {
        if ($domain !== 'adforest' || !is_string($text) || $text === '') {
            return $translated;
        }

        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $target_locale = function_exists('bornado_frontend_locale') ? bornado_frontend_locale() : 'fa_IR';

        if (
            !preg_match('/^fa(?:_|$)/i', (string) $locale)
            && !preg_match('/^fa(?:_|$)/i', (string) $target_locale)
        ) {
            return $translated;
        }

        static $overrides = array(
            'Ad Type'            => 'نوع آگهی',
            'Condition'          => 'وضعیت کالا',
            'Warranty'           => 'گارانتی / ضمانت',
            'Select Option'      => 'انتخاب گزینه',
            '-- Select Option --' => 'انتخاب گزینه',
        );

        return array_key_exists($text, $overrides) ? $overrides[$text] : $translated;
    }
}

add_filter('gettext', 'bornado_override_adforest_edit_labels', 20, 3);

add_filter('wp_robots', function ($robots) {
    if (!bornado_is_public_seo_request()) {
        return $robots;
    }

    if (!is_array($robots)) {
        $robots = array();
    }

    if (empty($robots['max-image-preview'])) {
        $robots['max-image-preview'] = 'large';
    }

    if (!isset($robots['max-snippet'])) {
        $robots['max-snippet'] = -1;
    }

    if (!isset($robots['max-video-preview'])) {
        $robots['max-video-preview'] = -1;
    }

    return $robots;
}, 20);

if (!function_exists('bornado_has_external_hreflang_provider')) {
    /**
     * Detect multilingual plugins that may already own hreflang output.
     *
     * @return bool
     */
    function bornado_has_external_hreflang_provider()
    {
        return defined('ICL_SITEPRESS_VERSION')
            || function_exists('pll_the_languages')
            || function_exists('trp_custom_language_switcher');
    }
}

if (!function_exists('bornado_get_current_canonical_url_for_hreflang')) {
    /**
     * Best-effort canonical URL resolver for hreflang output.
     *
     * @return string
     */
    function bornado_get_current_canonical_url_for_hreflang()
    {
        if (function_exists('bornado_seo_routing_get_context')) {
            $route_context = bornado_seo_routing_get_context();
            if (!empty($route_context['canonical_url']) && is_string($route_context['canonical_url'])) {
                return $route_context['canonical_url'];
            }
        }

        if (is_front_page()) {
            return home_url('/');
        }

        if (is_home()) {
            $posts_page_id = (int) get_option('page_for_posts');
            return $posts_page_id > 0 ? (string) get_permalink($posts_page_id) : home_url('/');
        }

        if (is_singular()) {
            return (string) get_permalink();
        }

        if (is_post_type_archive()) {
            $post_type = get_query_var('post_type');
            $post_type = is_array($post_type) ? reset($post_type) : $post_type;
            return is_string($post_type) ? (string) get_post_type_archive_link($post_type) : '';
        }

        if (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $term_link = get_term_link($term);
                return is_wp_error($term_link) ? '' : (string) $term_link;
            }
        }

        return '';
    }
}

if (!function_exists('bornado_print_hreflang_links')) {
    /**
     * Output conservative hreflang signals for the current single-language site.
     *
     * We emit only self-referencing `fa` plus `x-default` on the global home page.
     * We intentionally do not emit country-specific hreflang variants because
     * country routes are distinct pages, not alternate translations of one page.
     *
     * @return void
     */
    function bornado_print_hreflang_links()
    {
        if (!bornado_is_public_seo_request() || bornado_has_external_hreflang_provider()) {
            return;
        }

        $canonical_url = bornado_get_current_canonical_url_for_hreflang();
        if ($canonical_url === '') {
            return;
        }

        printf(
            "<link rel=\"alternate\" hreflang=\"fa\" href=\"%s\" />\n",
            esc_url($canonical_url)
        );

        if (untrailingslashit($canonical_url) === untrailingslashit(home_url('/'))) {
            printf(
                "<link rel=\"alternate\" hreflang=\"x-default\" href=\"%s\" />\n",
                esc_url(home_url('/'))
            );
        }
    }
}
add_action('wp_head', 'bornado_print_hreflang_links', 6);

add_action('init', function() {
    $taxonomies = ['ad_cats', 'ad_country', 'ad_condition', 'ad_type', 'ad_warranty', 'ad_currency'];
    
    foreach ($taxonomies as $taxonomy) {
        global $wp_taxonomies;
        if (isset($wp_taxonomies[$taxonomy])) {
            $wp_taxonomies[$taxonomy]->show_in_rest = true;
            $wp_taxonomies[$taxonomy]->rest_base = $taxonomy;
        }
    }
}, 999);

if (!function_exists('bornado_hide_manual_currency_field_when_disabled')) {
    /**
     * Hide the frontend ad-post currency selector when the theme option is off.
     *
     * The theme renders currency from two separate paths in AdPostModern:
     * the Elementor block itself and the category-template HTML injected later.
     * Keep backend auto-assignment active, but remove both manual UI entry points.
     */
    function bornado_hide_manual_currency_field_when_disabled()
    {
        if (is_admin()) {
            return;
        }

        global $adforest_theme;

        $currency_option_enabled = $adforest_theme['sb_currency_option_ad_post'] ?? false;
        if (!empty($currency_option_enabled)) {
            return;
        }

        $css = '
        .bornado-hide-ad-currency {
            display: none !important;
        }';

        wp_register_style('bornado-hide-ad-currency', false);
        wp_enqueue_style('bornado-hide-ad-currency');
        wp_add_inline_style('bornado-hide-ad-currency', $css);

        $js = "
        document.addEventListener('DOMContentLoaded', function () {
            function hideCurrencyField(root) {
                var scope = root && root.querySelectorAll ? root : document;

                scope.querySelectorAll('select[name=\"ad_currency\"]').forEach(function (select) {
                    var wrapper = null;

                    if (select.closest('#cat_template_html')) {
                        wrapper = select.closest('.row');
                    } else {
                        wrapper = select.closest('.field-box.location-box') || select.closest('.field-box') || select.closest('.row');
                    }

                    if (wrapper) {
                        wrapper.classList.add('bornado-hide-ad-currency');
                    }

                    select.required = false;
                    select.removeAttribute('required');
                    select.removeAttribute('data-parsley-required');
                });
            }

            hideCurrencyField(document);

            var categoryTemplate = document.getElementById('cat_template_html');
            if (!categoryTemplate || typeof MutationObserver === 'undefined') {
                return;
            }

            var observer = new MutationObserver(function () {
                hideCurrencyField(categoryTemplate);
            });

            observer.observe(categoryTemplate, {
                childList: true,
                subtree: true
            });
        });
        ";

        wp_register_script('bornado-hide-ad-currency', '', array(), null, true);
        wp_enqueue_script('bornado-hide-ad-currency');
        wp_add_inline_script('bornado-hide-ad-currency', $js);
    }
}
add_action('wp_enqueue_scripts', 'bornado_hide_manual_currency_field_when_disabled', 130);

if (!function_exists('bornado_sync_price_requirement_with_category_template')) {
    /**
     * Keep Ad Post Modern price validation aligned with the selected category template.
     *
     * AdForest's frontend JS re-applies `required` on price fields after each
     * price-type change, even when the selected `sb_dynamic_form_templates`
     * marks the default Price field as not required. Respect the template rule
     * here without editing core theme assets.
     */
    function bornado_sync_price_requirement_with_category_template()
    {
        if (is_admin()) {
            return;
        }

        $js = "
        document.addEventListener('DOMContentLoaded', function () {
            function getPriceField() {
                return document.querySelector('#cat_template_html #ad_price, #ad_price');
            }

            function getRangeFields() {
                return {
                    from: document.getElementById('ad_price_from'),
                    to: document.getElementById('ad_price_to')
                };
            }

            function getTemplateRequiredState() {
                var priceField = getPriceField();
                if (!priceField) {
                    return null;
                }

                var templateState = priceField.getAttribute('data-bornado-template-price-required');
                if (templateState === 'true' || templateState === 'false') {
                    return templateState === 'true';
                }

                var fieldBox = priceField.closest('.field-box') || priceField.parentElement;
                var label = fieldBox ? fieldBox.querySelector('label[for=\"ad_price\"], label') : null;
                var hasRequiredMarker = !!(label && label.querySelector('.required'));
                var parsleyState = String(priceField.getAttribute('data-parsley-required') || '').toLowerCase();
                var requiredState = priceField.hasAttribute('required');
                var isRequired = hasRequiredMarker || parsleyState === 'true' || requiredState;

                if (label) {
                    isRequired = hasRequiredMarker;
                }

                priceField.setAttribute('data-bornado-template-price-required', isRequired ? 'true' : 'false');
                return isRequired;
            }

            function setFieldRequired(field, isRequired) {
                if (!field) {
                    return;
                }

                if (isRequired) {
                    field.setAttribute('required', 'required');
                    field.setAttribute('data-parsley-required', 'true');
                } else {
                    field.removeAttribute('required');
                    field.setAttribute('data-parsley-required', 'false');
                }
            }

            function syncPriceRequirement() {
                var templateRequiresPrice = getTemplateRequiredState();
                if (templateRequiresPrice === null) {
                    return;
                }

                var priceTypeField = document.getElementById('ad_post_price_type');
                var priceType = priceTypeField ? String(priceTypeField.value || '') : '';
                var priceField = getPriceField();
                var rangeFields = getRangeFields();

                if (priceType === 'free' || priceType === 'no_price' || priceType === 'on_call') {
                    setFieldRequired(priceField, false);
                    setFieldRequired(rangeFields.from, false);
                    setFieldRequired(rangeFields.to, false);
                    return;
                }

                if (priceType === 'range') {
                    setFieldRequired(priceField, false);
                    setFieldRequired(rangeFields.from, templateRequiresPrice);
                    setFieldRequired(rangeFields.to, templateRequiresPrice);
                    return;
                }

                setFieldRequired(priceField, templateRequiresPrice);
                setFieldRequired(rangeFields.from, false);
                setFieldRequired(rangeFields.to, false);
            }

            function afterThemeHandlers(callback) {
                window.setTimeout(callback, 0);
            }

            syncPriceRequirement();

            document.addEventListener('change', function (event) {
                if (event.target && event.target.id === 'ad_post_price_type') {
                    afterThemeHandlers(syncPriceRequirement);
                }
            }, true);

            var categoryTemplate = document.getElementById('cat_template_html');
            if (categoryTemplate && typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function () {
                    afterThemeHandlers(syncPriceRequirement);
                });

                observer.observe(categoryTemplate, {
                    childList: true,
                    subtree: true
                });
            }

            document.addEventListener('adforestCategoryTemplateLoaded', function () {
                afterThemeHandlers(syncPriceRequirement);
            });
        });
        ";

        wp_register_script('bornado-sync-price-required', '', array(), null, true);
        wp_enqueue_script('bornado-sync-price-required');
        wp_add_inline_script('bornado-sync-price-required', $js);
    }
}
add_action('wp_enqueue_scripts', 'bornado_sync_price_requirement_with_category_template', 131);

if (!function_exists('bornado_enforce_price_slider_step')) {
    /**
     * Force AdForest price/range sliders to move in 10-unit steps from the child theme.
     */
    function bornado_enforce_price_slider_step()
    {
        if (is_admin()) {
            return;
        }

        $js = <<<'JS'
        document.addEventListener('DOMContentLoaded', function () {
            var STEP = 10;

            function getJQuery() {
                return window.jQuery || null;
            }

            function enhanceSlider(sliderNode) {
                var $ = getJQuery();
                if (!$ || !sliderNode) {
                    return;
                }

                var $slider = $(sliderNode);
                var instance = $slider.data('ionRangeSlider');
                if (!instance || !instance.result) {
                    return;
                }

                instance.update({ step: STEP });
                $slider.attr('data-step', String(STEP)).data('step', STEP);
            }

            function enhanceAllSliders(scope) {
                var $ = getJQuery();
                if (!$) {
                    return;
                }

                var $scope = scope ? $(scope) : $(document);
                var $sliders = $scope.find('.adt-ads-range-slider, #price-slider-topbar-search');
                if ($scope.is('.adt-ads-range-slider, #price-slider-topbar-search')) {
                    $sliders = $sliders.add($scope);
                }

                $sliders.each(function () {
                    enhanceSlider(this);
                });
            }

            function scheduleEnhance(scope, delay) {
                window.setTimeout(function () {
                    enhanceAllSliders(scope || document);
                }, delay || 0);
            }

            scheduleEnhance(document, 0);
            scheduleEnhance(document, 300);
            scheduleEnhance(document, 1000);

            var $ = getJQuery();
            if ($) {
                $(window).on('load', function () {
                    scheduleEnhance(document, 0);
                });

                $(document).on('adforest:search:rendered', function () {
                    scheduleEnhance(document, 0);
                });

                $(document).ajaxComplete(function () {
                    scheduleEnhance(document, 0);
                });
            }

            document.addEventListener('adforestCategoryTemplateLoaded', function () {
                scheduleEnhance(document, 0);
            });
        });
        JS;

        wp_register_script('bornado-price-slider-step', '', array('adforest-custom'), null, true);
        wp_enqueue_script('bornado-price-slider-step');
        wp_add_inline_script('bornado-price-slider-step', $js);
    }
}
add_action('wp_enqueue_scripts', 'bornado_enforce_price_slider_step', 132);

if (!function_exists('bornado_enqueue_dashboard_mobile_menu_assets')) {
    /**
     * Turn the dashboard mobile menus into app-like bottom sheets.
     */
    function bornado_enqueue_dashboard_mobile_menu_assets()
    {
        if (is_admin() || !is_page_template('page-theme-dashboard.php')) {
            return;
        }

        $style_path = get_stylesheet_directory() . '/assets/css/bornado-dashboard-mobile-menu.css';
        $script_path = get_stylesheet_directory() . '/assets/js/bornado-dashboard-mobile-menu.js';

        if (file_exists($style_path)) {
            wp_enqueue_style(
                'bornado-dashboard-mobile-menu',
                get_stylesheet_directory_uri() . '/assets/css/bornado-dashboard-mobile-menu.css',
                array('dashboard-main', 'dashboard-dash', 'dashboard-dash-rtl'),
                (string) filemtime($style_path)
            );
        }

        if (file_exists($script_path)) {
            wp_enqueue_script(
                'bornado-dashboard-mobile-menu',
                get_stylesheet_directory_uri() . '/assets/js/bornado-dashboard-mobile-menu.js',
                array('dashboard-bootstrap-bundle', 'dashboard-mainjs'),
                (string) filemtime($script_path),
                true
            );

            wp_localize_script(
                'bornado-dashboard-mobile-menu',
                'BornadoDashboardMenu',
                array(
                    'breakpoint'   => 991,
                    'closeLabel'   => __('بستن', 'adforest'),
                    'sidebarTitle' => __('منوی پروفایل', 'adforest'),
                    'profileTitle' => __('حساب کاربری', 'adforest'),
                )
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'bornado_enqueue_dashboard_mobile_menu_assets', 220);

if (!function_exists('bornado_get_temporarily_disabled_dashboard_page_types')) {
    /**
     * Dashboard sections temporarily disabled for all devices.
     *
     * @return array<int,string>
     */
    function bornado_get_temporarily_disabled_dashboard_page_types()
    {
        return array(
            'ad_bids',
            'my_packages',
            'invoices',
        );
    }
}

add_filter('adforest_dashboard_allowed_page_types', function ($page_types) {
    if (!is_array($page_types)) {
        return $page_types;
    }

    return array_values(array_diff($page_types, bornado_get_temporarily_disabled_dashboard_page_types()));
}, 20);

add_action('template_redirect', function () {
    if (is_admin() || !is_page_template('page-theme-dashboard.php')) {
        return;
    }

    $page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';
    if ($page_type === '' || !in_array($page_type, bornado_get_temporarily_disabled_dashboard_page_types(), true)) {
        return;
    }

    wp_safe_redirect(remove_query_arg('page_type'));
    exit;
}, 20);

add_action('wp_enqueue_scripts', function () {
    if (is_admin() || !is_page_template('page-theme-dashboard.php')) {
        return;
    }

    $disabled_types = bornado_get_temporarily_disabled_dashboard_page_types();
    if (empty($disabled_types)) {
        return;
    }

    $selectors = array();
    foreach ($disabled_types as $type) {
        $selectors[] = '.sidebar-nav-wrapper .sidebar-nav a[href*="page_type=' . esc_attr($type) . '"]';
    }

    $js = <<<'JS'
    document.addEventListener('DOMContentLoaded', function () {
        var selectors = %s;
        selectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (link) {
                var item = link.closest('.nav-item');
                if (item) {
                    item.style.display = 'none';
                    return;
                }

                var listItem = link.closest('li');
                if (listItem) {
                    listItem.style.display = 'none';
                    return;
                }

                link.style.display = 'none';
            });
        });
    });
    JS;

    wp_register_script('bornado-dashboard-hidden-menu-items', '', array(), null, true);
    wp_enqueue_script('bornado-dashboard-hidden-menu-items');
    wp_add_inline_script(
        'bornado-dashboard-hidden-menu-items',
        sprintf($js, wp_json_encode($selectors))
    );
}, 230);

if (!function_exists('bornado_hide_adt_ads_sort_box_everywhere')) {
    /**
     * Hide AdForest sort box globally from the child theme layer.
     */
    function bornado_hide_adt_ads_sort_box_everywhere()
    {
        if (is_admin()) {
            return;
        }

        $css = <<<'CSS'
        .adt-ads-sort-box {
            display: none !important;
        }
        CSS;

        wp_register_style('bornado-hide-adt-ads-sort-box', false, array(), null);
        wp_enqueue_style('bornado-hide-adt-ads-sort-box');
        wp_add_inline_style('bornado-hide-adt-ads-sort-box', $css);
    }
}
add_action('wp_enqueue_scripts', 'bornado_hide_adt_ads_sort_box_everywhere', 240);