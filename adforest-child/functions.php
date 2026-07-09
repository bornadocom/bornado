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
 * Re-apply semantic route country/category constraints directly to ad queries.
 */
$bornado_semantic_route_query_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-semantic-route-query-fix.php';
if (file_exists($bornado_semantic_route_query_fix_bootstrap)) {
    require_once $bornado_semantic_route_query_fix_bootstrap;
}

/**
 * Load semantic breadcrumb override before the parent theme defines its pluggable function.
 */
$bornado_breadcrumb_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-breadcrumbs.php';
if (file_exists($bornado_breadcrumb_bootstrap)) {
    require_once $bornado_breadcrumb_bootstrap;
}

/**
 * Load the schema module tree so page-specific schema logic stays organized.
 */
$bornado_schema_bootstrap = trailingslashit(get_stylesheet_directory()) . 'schema/bootstrap.php';
if (file_exists($bornado_schema_bootstrap)) {
    require_once $bornado_schema_bootstrap;
}

/**
 * Respect category-template Show/Hide flags in the category search sidebar.
 */
$bornado_category_search_sidebar_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-category-search-sidebar.php';
if (file_exists($bornado_category_search_sidebar_bootstrap)) {
    require_once $bornado_category_search_sidebar_bootstrap;
}

/**
 * Make category widget counts respect the active search location context.
 */
$bornado_contextual_category_counts_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-contextual-category-counts.php';
if (file_exists($bornado_contextual_category_counts_bootstrap)) {
    require_once $bornado_contextual_category_counts_bootstrap;
}

/**
 * Fix category-widget counts and active-state rendering on live search pages.
 */
$bornado_category_widget_context_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-category-widget-context-fix.php';
if (file_exists($bornado_category_widget_context_fix_bootstrap)) {
    require_once $bornado_category_widget_context_fix_bootstrap;
}

/**
 * Re-register category widgets so child-theme template overrides are used.
 */
$bornado_category_widget_loader_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-category-widget-loader.php';
if (file_exists($bornado_category_widget_loader_bootstrap)) {
    require_once $bornado_category_widget_loader_bootstrap;
}

/**
 * Remove duplicate category labels and mark active rows without touching plugin files.
 */
$bornado_category_widget_render_fix_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-category-widget-render-fix.php';
if (file_exists($bornado_category_widget_render_fix_bootstrap)) {
    require_once $bornado_category_widget_render_fix_bootstrap;
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
 * Self-hosted global geo catalog and lookup APIs for ad posting.
 */
$bornado_geo_catalog_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-geo-catalog.php';
if (file_exists($bornado_geo_catalog_bootstrap)) {
    require_once $bornado_geo_catalog_bootstrap;
}

/**
 * Public location UI should only show locations that currently have active listings.
 */
$bornado_public_location_visibility_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-public-location-visibility.php';
if (file_exists($bornado_public_location_visibility_bootstrap)) {
    require_once $bornado_public_location_visibility_bootstrap;
}

/**
 * Currency-code alias overrides for mapping GeoNames countries to ad_currency.
 */
$bornado_geo_currency_overrides_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-geo-currency-overrides.php';
if (file_exists($bornado_geo_currency_overrides_bootstrap)) {
    require_once $bornado_geo_currency_overrides_bootstrap;
}

/**
 * Supplemental Persian country-name overrides for Geo Catalog country labels.
 */
$bornado_geo_country_name_overrides_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-geo-country-name-overrides.php';
if (file_exists($bornado_geo_country_name_overrides_bootstrap)) {
    require_once $bornado_geo_country_name_overrides_bootstrap;
}

/**
 * Supplemental Persian city-name overrides for Geo Catalog gaps.
 */
$bornado_geo_city_name_overrides_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-geo-city-name-overrides.php';
if (file_exists($bornado_geo_city_name_overrides_bootstrap)) {
    require_once $bornado_geo_city_name_overrides_bootstrap;
}

/**
 * Global country/city ad-post UI plus publish-time sync into ad_country.
 */
$bornado_global_ad_location_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-global-ad-location.php';
if (file_exists($bornado_global_ad_location_bootstrap)) {
    require_once $bornado_global_ad_location_bootstrap;
}

/**
 * Keep dashboard profile phone UX aligned with the selected country dial code.
 */
$bornado_profile_phone_guard_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-profile-phone-guard.php';
if (file_exists($bornado_profile_phone_guard_bootstrap)) {
    require_once $bornado_profile_phone_guard_bootstrap;
}

/**
 * Manage profile avatar upload/remove UX from the child theme layer.
 */
$bornado_profile_avatar_manager_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-profile-avatar-manager.php';
if (file_exists($bornado_profile_avatar_manager_bootstrap)) {
    require_once $bornado_profile_avatar_manager_bootstrap;
}

/**
 * Hide self-contact UI and guard profile-contact submissions.
 */
$bornado_profile_contact_guard_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-profile-contact-guard.php';
if (file_exists($bornado_profile_contact_guard_bootstrap)) {
    require_once $bornado_profile_contact_guard_bootstrap;
}

/**
 * Rich verified-contact panel for public profile pages.
 */
$bornado_profile_public_contact_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-profile-public-contact.php';
if (file_exists($bornado_profile_public_contact_bootstrap)) {
    require_once $bornado_profile_public_contact_bootstrap;
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
 * Archive sold/expired ads and 301 them to their exact category/location branch.
 */
$bornado_ad_archive_redirects_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-archive-redirects.php';
if (file_exists($bornado_ad_archive_redirects_bootstrap)) {
    require_once $bornado_ad_archive_redirects_bootstrap;
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
 * Guard third-party tracking and noisy fallback integrations from the child theme layer.
 */
$bornado_tracking_guards_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-tracking-guards.php';
if (file_exists($bornado_tracking_guards_bootstrap)) {
    require_once $bornado_tracking_guards_bootstrap;
}

/**
 * Keep Rank Math sitemap rewrites self-healing after rewrite-affecting changes.
 */
$bornado_sitemap_self_heal_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-sitemap-self-heal.php';
if (file_exists($bornado_sitemap_self_heal_bootstrap)) {
    require_once $bornado_sitemap_self_heal_bootstrap;
}

/**
 * Remove global reCAPTCHA noise from pages that do not render matching forms.
 */
$bornado_recaptcha_guard_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-recaptcha-guard.php';
if (file_exists($bornado_recaptcha_guard_bootstrap)) {
    require_once $bornado_recaptcha_guard_bootstrap;
}

/**
 * Inject Google Tag Manager from the child theme layer.
 */
$bornado_google_tag_manager_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-google-tag-manager.php';
if (file_exists($bornado_google_tag_manager_bootstrap)) {
    require_once $bornado_google_tag_manager_bootstrap;
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

if (!function_exists('bornado_resolve_safe_redirect_url')) {
    /**
     * Resolve the safest available post-auth redirect target.
     *
     * Priority:
     * 1. Explicit function argument.
     * 2. `redirect_to` query arg.
     * 3. Legacy `u` query arg.
     * 4. Internal fallback URL.
     *
     * @param string $requested_url Explicit redirect target.
     * @param string $fallback_url Internal fallback target.
     * @return string
     */
    function bornado_resolve_safe_redirect_url($requested_url = '', $fallback_url = '')
    {
        $site_fallback = home_url('/');
        $fallback_url  = is_string($fallback_url) ? trim($fallback_url) : '';
        $fallback_url  = wp_validate_redirect($fallback_url, $site_fallback);
        $candidates    = array();

        if (is_string($requested_url)) {
            $requested_url = trim($requested_url);
            if ('' !== $requested_url) {
                $candidates[] = $requested_url;
            }
        }

        foreach (array('redirect_to', 'u') as $query_key) {
            if (!isset($_GET[$query_key])) {
                continue;
            }

            $candidate = wp_unslash($_GET[$query_key]);
            if (!is_scalar($candidate)) {
                continue;
            }

            $candidate = trim((string) $candidate);
            if ('' !== $candidate) {
                $candidates[] = $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            $validated = wp_validate_redirect($candidate, '');
            if ('' !== $validated) {
                return $validated;
            }
        }

        return $fallback_url;
    }
}

if (!function_exists('bornado_build_auth_redirect_url')) {
    /**
     * Append backward-compatible auth redirect params to an auth URL.
     *
     * @param string $base_url Auth page URL.
     * @param string $redirect_url Target URL after auth.
     * @param string $fallback_url Fallback redirect target.
     * @return string
     */
    function bornado_build_auth_redirect_url($base_url = '', $redirect_url = '', $fallback_url = '')
    {
        $base_url = is_string($base_url) ? trim($base_url) : '';
        if ('' === $base_url || '#' === $base_url) {
            return $base_url;
        }

        $safe_redirect = bornado_resolve_safe_redirect_url($redirect_url, $fallback_url);
        if ('' === $safe_redirect) {
            return $base_url;
        }

        return add_query_arg(
            array(
                'redirect_to' => $safe_redirect,
                'u'           => $safe_redirect,
            ),
            $base_url
        );
    }
}

if (!function_exists('bornado_get_auth_page_url')) {
    /**
     * Resolve the configured auth page URL from the child theme layer.
     *
     * @param string $mode Either `login` or `register`.
     * @return string
     */
    function bornado_get_auth_page_url($mode = 'login')
    {
        global $adforest_theme;

        $mode = 'register' === $mode ? 'register' : 'login';
        if (function_exists('bornado_auth_modal_fallback_url')) {
            $auth_url = (string) bornado_auth_modal_fallback_url($mode);
            if ('' !== $auth_url && '#' !== $auth_url) {
                return $auth_url;
            }
        }

        $page_key = 'register' === $mode ? 'sb_sign_up_page' : 'sb_sign_in_page';
        $page_id  = isset($adforest_theme[$page_key]) ? apply_filters('adforest_language_page_id', $adforest_theme[$page_key]) : 0;
        if ($page_id) {
            $auth_url = get_permalink($page_id);
            if ($auth_url) {
                return (string) $auth_url;
            }
        }

        return '';
    }
}

if (!function_exists('bornado_is_same_site_page_url')) {
    /**
     * Compare two same-site URLs by origin/path, ignoring trailing slashes.
     *
     * @param string $candidate_url URL to inspect.
     * @param string $reference_url Expected site URL.
     * @return bool
     */
    function bornado_is_same_site_page_url($candidate_url, $reference_url)
    {
        $candidate_url = is_string($candidate_url) ? trim($candidate_url) : '';
        $reference_url = is_string($reference_url) ? trim($reference_url) : '';
        if ('' === $candidate_url || '' === $reference_url) {
            return false;
        }

        $candidate_host = (string) wp_parse_url($candidate_url, PHP_URL_HOST);
        $reference_host = (string) wp_parse_url($reference_url, PHP_URL_HOST);
        if ('' !== $candidate_host && '' !== $reference_host && strtolower($candidate_host) !== strtolower($reference_host)) {
            return false;
        }

        $candidate_path = untrailingslashit((string) wp_parse_url($candidate_url, PHP_URL_PATH));
        $reference_path = untrailingslashit((string) wp_parse_url($reference_url, PHP_URL_PATH));

        return '' !== $candidate_path && $candidate_path === $reference_path;
    }
}

if (!function_exists('bornado_get_current_auth_redirect_url')) {
    /**
     * Resolve a current-page redirect target, but avoid looping back to auth pages.
     *
     * @param string $fallback_url Internal fallback target.
     * @return string
     */
    function bornado_get_current_auth_redirect_url($fallback_url = '')
    {
        $site_fallback = home_url('/');
        $fallback_url  = bornado_resolve_safe_redirect_url($fallback_url, $site_fallback);
        $current_url   = function_exists('adforest_get_current_url') ? (string) adforest_get_current_url() : '';
        $current_url   = bornado_resolve_safe_redirect_url($current_url, $fallback_url);
        $current_path  = untrailingslashit((string) wp_parse_url($current_url, PHP_URL_PATH));

        if ('' === $current_path) {
            return $fallback_url;
        }

        foreach (array('login', 'register') as $mode) {
            $auth_url = bornado_get_auth_page_url($mode);
            if ('' === $auth_url) {
                continue;
            }

            $auth_path = untrailingslashit((string) wp_parse_url($auth_url, PHP_URL_PATH));
            if ('' !== $auth_path && $current_path === $auth_path) {
                return $fallback_url;
            }
        }

        return $current_url;
    }
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

        if ('' === $redirect_url) {
            $redirect_url = function_exists('bornado_get_current_auth_redirect_url')
                ? bornado_get_current_auth_redirect_url($fallback_url)
                : $fallback_url;
        }

        $redirect_url = bornado_resolve_safe_redirect_url($redirect_url, $fallback_url);

        $login_url = bornado_get_auth_page_url('login');
        if ('' !== $login_url && '#' !== $login_url) {
            return bornado_build_auth_redirect_url($login_url, $redirect_url, $fallback_url);
        }

        return wp_login_url($redirect_url);
    }
}

if (!function_exists('bornado_filter_auth_redirect_page_urls')) {
    /**
     * Sanitize auth-page redirect query args without editing parent theme files.
     *
     * @param string $page_url URL being filtered by AdForest.
     * @return string
     */
    function bornado_filter_auth_redirect_page_urls($page_url = '')
    {
        $page_url = is_string($page_url) ? trim($page_url) : '';
        if ('' === $page_url) {
            return $page_url;
        }

        $is_auth_url = false;
        foreach (array('login', 'register') as $mode) {
            $auth_url = bornado_get_auth_page_url($mode);
            if ('' !== $auth_url && bornado_is_same_site_page_url($page_url, $auth_url)) {
                $is_auth_url = true;
                break;
            }
        }

        if (!$is_auth_url) {
            return $page_url;
        }

        $query_string = (string) wp_parse_url($page_url, PHP_URL_QUERY);
        if ('' === $query_string) {
            return $page_url;
        }

        parse_str($query_string, $query_args);
        $requested_redirect = '';
        if (!empty($query_args['redirect_to']) && is_scalar($query_args['redirect_to'])) {
            $requested_redirect = (string) $query_args['redirect_to'];
        } elseif (!empty($query_args['u']) && is_scalar($query_args['u'])) {
            $requested_redirect = (string) $query_args['u'];
        }

        if ('' === trim($requested_redirect)) {
            return $page_url;
        }

        $clean_url = remove_query_arg(array('redirect_to', 'u'), $page_url);
        return bornado_build_auth_redirect_url($clean_url, $requested_redirect, home_url('/'));
    }
}
add_filter('adforest_page_lang_url', 'bornado_filter_auth_redirect_page_urls', 120);

if (!function_exists('bornado_harden_legacy_auth_redirect_globals')) {
    /**
     * Normalize legacy auth globals before parent scripts read them.
     *
     * @return void
     */
    function bornado_harden_legacy_auth_redirect_globals()
    {
        if (is_admin()) {
            return;
        }

        $js = <<<'JS'
(function () {
    var siteUrl = %s;

    function safeUrl(url, fallback) {
        try {
            return new URL(url, fallback || window.location.origin);
        } catch (error) {
            return new URL(fallback || window.location.origin, window.location.origin);
        }
    }

    function getSiteOrigin() {
        return safeUrl(siteUrl, window.location.origin).origin;
    }

    function isSafeTarget(parsedUrl) {
        return Boolean(
            parsedUrl
            && /^(https?:)$/.test(parsedUrl.protocol)
            && parsedUrl.origin === getSiteOrigin()
        );
    }

    function normalizeFallback() {
        var candidates = [];
        if (window.sb_options && window.sb_options.sb_after_login_page) {
            candidates.push(window.sb_options.sb_after_login_page);
        }
        if (window.sb_options && window.sb_options.profile_page) {
            candidates.push(window.sb_options.profile_page);
        }
        candidates.push(siteUrl);
        candidates.push(window.location.origin);

        for (var index = 0; index < candidates.length; index += 1) {
            if (!candidates[index]) {
                continue;
            }

            var parsed = safeUrl(candidates[index], window.location.origin);
            if (isSafeTarget(parsed)) {
                return parsed.toString();
            }
        }

        return safeUrl(window.location.origin, window.location.origin).toString();
    }

    function resolveSafeTarget(candidateUrl, fallbackUrl) {
        var fallback = safeUrl(fallbackUrl || normalizeFallback(), window.location.origin);
        if (!candidateUrl) {
            return fallback.toString();
        }

        try {
            var parsed = new URL(candidateUrl, fallback.toString());
            if (!isSafeTarget(parsed)) {
                return fallback.toString();
            }

            return parsed.toString();
        } catch (error) {
            return fallback.toString();
        }
    }

    function getQueryRedirectTarget() {
        var current = safeUrl(window.location.href, window.location.href);
        return current.searchParams.get('redirect_to') || current.searchParams.get('u') || '';
    }

    if (!window.sb_options || typeof window.sb_options !== 'object') {
        return;
    }

    var fallback = normalizeFallback();
    var queryRedirect = getQueryRedirectTarget();
    window.sb_options.sb_after_login_page = resolveSafeTarget(queryRedirect || window.sb_options.sb_after_login_page, fallback);
})();
JS;

        $inline_js = sprintf($js, wp_json_encode(home_url('/')));
        foreach (array('adforest-custom', 'firebase-custom') as $handle) {
            if (wp_script_is($handle, 'registered') || wp_script_is($handle, 'enqueued')) {
                wp_add_inline_script($handle, $inline_js, 'before');
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'bornado_harden_legacy_auth_redirect_globals', 999);

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

if (!function_exists('bornado_get_ad_search_seo_heading_title')) {
    /**
     * Resolve the current Ad Search SEO title for use in a semantic H1.
     *
     * Prefer Rank Math's final frontend title so the heading mirrors the active
     * SEO title template, then shorten it into a cleaner H1 for listing pages.
     *
     * @return string
     */
    function bornado_get_ad_search_seo_heading_title()
    {
        $title = '';

        if (class_exists('\RankMath\Paper\Paper') && method_exists('\RankMath\Paper\Paper', 'get')) {
            $paper = \RankMath\Paper\Paper::get();
            if (is_object($paper) && method_exists($paper, 'get_title')) {
                $title = (string) $paper->get_title();
            }
        }

        if ($title === '') {
            $title = (string) wp_get_document_title();
        }

        if ($title === '') {
            $queried_object_id = get_queried_object_id();
            if ($queried_object_id > 0) {
                $title = (string) get_the_title($queried_object_id);
            }
        }

        $title = wp_specialchars_decode(wp_strip_all_tags($title), ENT_QUOTES);
        $title = trim(preg_replace('/\s+/u', ' ', $title));

        if ($title === '') {
            return '';
        }

        $site_name = wp_specialchars_decode(wp_strip_all_tags((string) get_bloginfo('name')), ENT_QUOTES);
        $site_name = trim(preg_replace('/\s+/u', ' ', $site_name));

        $segments = preg_split('/(?:\s*\|\s*|\s*»\s*|\s*–\s*|\s*—\s*|\s+-\s+)/u', $title);
        if (is_array($segments) && count($segments) > 1) {
            $segments = array_values(array_filter(array_map('trim', $segments), static function ($segment) use ($site_name) {
                if (!is_string($segment) || $segment === '') {
                    return false;
                }

                if ($site_name !== '' && function_exists('mb_stripos') && mb_stripos($segment, $site_name) !== false) {
                    return false;
                }

                return true;
            }));

            if (!empty($segments) && isset($segments[0]) && is_string($segments[0])) {
                $title = $segments[0];
            }
        }

        return trim($title);
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
     * emit a full BCP 47 language tag that matches the page content language.
     */
    function bornado_frontend_language_tag()
    {
        return apply_filters('bornado_frontend_language_tag', 'fa-IR');
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
            var queuedScope = document;
            var isEnhanceQueued = false;

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
                var currentStep = Number($slider.attr('data-step') || 0);
                var instanceStep = instance && instance.options ? Number(instance.options.step || 0) : 0;
                if (!instance || !instance.result) {
                    return;
                }

                if (
                    sliderNode.getAttribute('data-bornado-step-applied') === '1'
                    && currentStep === STEP
                    && instanceStep === STEP
                ) {
                    return;
                }

                instance.update({ step: STEP });
                $slider.attr('data-step', String(STEP)).data('step', STEP);
                sliderNode.setAttribute('data-bornado-step-applied', '1');
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

            function flushEnhanceQueue() {
                var scope = queuedScope || document;
                queuedScope = document;
                isEnhanceQueued = false;
                enhanceAllSliders(scope);
            }

            function queueEnhance(scope, delay) {
                if (delay && delay > 0) {
                    window.setTimeout(function () {
                        queueEnhance(scope, 0);
                    }, delay);
                    return;
                }

                queuedScope = scope || queuedScope || document;
                if (isEnhanceQueued) {
                    return;
                }

                isEnhanceQueued = true;
                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(flushEnhanceQueue);
                    return;
                }

                window.setTimeout(flushEnhanceQueue, 16);
            }

            queueEnhance(document, 0);
            queueEnhance(document, 400);

            var $ = getJQuery();
            if ($) {
                $(window).on('load', function () {
                    queueEnhance(document, 0);
                });

                $(document).on('adforest:search:rendered', function () {
                    queueEnhance(document, 0);
                });

                $(document).ajaxComplete(function () {
                    queueEnhance(document, 0);
                });
            }

            document.addEventListener('adforestCategoryTemplateLoaded', function () {
                queueEnhance(document, 0);
            });
        });
        JS;

        wp_register_script('bornado-price-slider-step', '', array('jquery'), null, true);
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

        $child_style_path = get_stylesheet_directory() . '/style.css';
        if (
            is_readable($child_style_path)
            && !wp_style_is('adforest-pro-style', 'enqueued')
            && !wp_style_is('adforest-pro-style', 'done')
        ) {
            wp_enqueue_style(
                'adforest-pro-style',
                get_stylesheet_uri(),
                array('dashboard-main'),
                (string) filemtime($child_style_path)
            );
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

if (!function_exists('bornado_enqueue_form_a11y_fixes')) {
    /**
     * Patch third-party and dynamic frontend form markup from the child theme layer.
     */
    function bornado_enqueue_form_a11y_fixes()
    {
        if (is_admin()) {
            return;
        }

        $script_path = get_stylesheet_directory() . '/assets/js/bornado-form-a11y-fixes.js';
        if (!file_exists($script_path)) {
            return;
        }

        wp_enqueue_script(
            'bornado-form-a11y-fixes',
            get_stylesheet_directory_uri() . '/assets/js/bornado-form-a11y-fixes.js',
            array(),
            (string) filemtime($script_path),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'bornado_enqueue_form_a11y_fixes', 245);

if (!function_exists('bornado_enqueue_message_poll_guard')) {
    /**
     * Keep AdForest's global unread-message poller from flooding admin-ajax.
     *
     * The parent script starts `sb_check_messages` with a raw interval value.
     * On admin-facing screens that poll is unnecessary, and on any slow page
     * overlapping requests can pile up until the browser runs out of resources.
     * Patch the behavior from the child theme without editing theme core files.
     */
    function bornado_enqueue_message_poll_guard()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $script_path = get_stylesheet_directory() . '/assets/js/bornado-message-poll-guard.js';
        if (!file_exists($script_path)) {
            return;
        }

        wp_enqueue_script(
            'bornado-message-poll-guard',
            get_stylesheet_directory_uri() . '/assets/js/bornado-message-poll-guard.js',
            array('jquery'),
            (string) filemtime($script_path),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'bornado_enqueue_message_poll_guard', 260);
add_action('admin_enqueue_scripts', 'bornado_enqueue_message_poll_guard', 260);

if (!function_exists('adforest_save_selected_category')) {
    /**
     * Safe child-theme override for the category-template AJAX callback.
     *
     * AdForest always returns a `more_options` payload, but the parent callback
     * only initializes its default-template flags inside the "no template
     * selected" branch. That leaves the variables undefined for categories that
     * do have a custom template, even though the frontend ignores those flags in
     * that path. We keep the original behavior, but initialize the flags
     * predictably so the response is valid in every branch.
     *
     * @return void
     */
    function adforest_save_selected_category()
    {
        if (
            !isset($_POST['security'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'save_selected_category_nonce')
        ) {
            wp_send_json_error(esc_html__('Security check failed.', 'adforest'));
        }

        global $adforest_theme;

        $ai_intent_in_category = false;
        if (function_exists('adforest_is_ai_ready') && function_exists('adforest_should_show_intent_tab')) {
            $ai_intent_in_category = adforest_is_ai_ready() && adforest_should_show_intent_tab();
        }

        if (!isset($_POST['category_id'], $_POST['category_name'])) {
            wp_send_json_error(esc_html__('Category ID or name not set.', 'adforest'));
        }

        $category_id = sanitize_text_field(wp_unslash($_POST['category_id']));
        $post_id = isset($_POST['post_id']) ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
        $category_name = sanitize_text_field(wp_unslash($_POST['category_name']));
        $category_template_id = get_term_meta($category_id, '_sb_category_template', true);

        $encoded_meta = '';
        if (isset($category_template_id)) {
            $category_template = get_term($category_template_id);
            if (isset($category_template->term_id)) {
                $encoded_meta = get_term_meta($category_template->term_id, '_sb_dynamic_form_fields', true);
            }
        }

        $decoded_meta = '';
        if ($encoded_meta !== '') {
            $decoded_meta = base64_decode($encoded_meta, true);
            if ($decoded_meta === false) {
                $decoded_meta = '';
            }
        }

        $template_meta_array = array();
        if ($decoded_meta !== '') {
            $template_meta_array = json_decode($decoded_meta, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $template_meta_array = array();
            }
        }

        $html = array();
        $cat_template_img_allowed = false;
        if (isset($template_meta_array)) {
            $html = adforest_load_ad_post_fields($template_meta_array, $post_id, $ai_intent_in_category);
            if (
                isset($template_meta_array['_sb_default_cat_image_show'])
                && $template_meta_array['_sb_default_cat_image_show'] == 1
            ) {
                $cat_template_img_allowed = true;
            }
        }

        $no_template_selected = '';
        if ((!is_array($html) || empty($html['html'])) && (!is_array($template_meta_array) || count($template_meta_array) === 0)) {
            $no_template_selected = 'no_template_selected';
        }

        $default_template_html = '';
        $default_template_warranty_and_condition = '';
        $default_template_image_container = '';
        $default_template_on = false;
        $default_intent_ad_type_html = '';
        $default_intent_condition_warranty_html = '';

        // These flags only matter for the fallback default-template branch, but
        // the response contract always includes them. Initialize them once so
        // categories with custom templates still return a clean payload.
        $video_link_on = 1;
        $ad_tags_on = 1;
        $ad_images_on = 1;
        $ad_condition_on = 1;
        $ad_warranty_on = 1;

        if ($no_template_selected == 'no_template_selected') {
            if (isset($adforest_theme['sb_default_adpost_template_on']) && $adforest_theme['sb_default_adpost_template_on'] == '1') {
                $price = '';
                $price_to = '';
                $price_from = '';
                $ad_price_type = '';
                $ad_type = '';
                $ad_yvideo = '';
                $tags = '';
                $ad_condition_value = '';
                $ad_warranty_value = '';
                $default_template_on = true;
                $tags_allowed = true;
                $video_allowed = true;

                if ($post_id !== '') {
                    $price = get_post_meta($post_id, '_adforest_ad_price', true);
                    $price_to = get_post_meta($post_id, '_adforest_ad_price_to', true);
                    $price_from = get_post_meta($post_id, '_adforest_ad_price_from', true);
                    $ad_price_type = get_post_meta($post_id, '_adforest_ad_price_type', true);
                    $ad_type = get_post_meta($post_id, '_adforest_ad_type', true);
                    $ad_yvideo = get_post_meta($post_id, '_adforest_ad_yvideo', true);
                    $tags_array = wp_get_object_terms($post_id, 'ad_tags', array('fields' => 'names'));
                    $tags = implode(',', $tags_array);
                    $ad_condition_value = get_post_meta($post_id, '_adforest_ad_condition', true);
                    $ad_warranty_value = get_post_meta($post_id, '_adforest_ad_warranty', true);
                    $ad_post_package = get_post_meta($post_id, '_adforest_ad_post_package', true);
                    $user_packages = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
                    $user_packages_default = prepare_default_packages();

                    if (is_array($user_packages) && is_array($user_packages_default)) {
                        $user_packages = $user_packages_default + $user_packages;
                    } else {
                        $user_packages = $user_packages_default;
                    }

                    if (isset($user_packages[$ad_post_package])) {
                        $tags_pkg = isset($user_packages[$ad_post_package]['allow_tags']) ? $user_packages[$ad_post_package]['allow_tags'] : '';
                        $video = isset($user_packages[$ad_post_package]['video_links']) ? $user_packages[$ad_post_package]['video_links'] : '';

                        $tags_allowed = ($tags_pkg === 'yes');
                        $video_allowed = ($video === 'yes');
                    }
                }

                if (isset($adforest_theme['sb_default_adpost_template_ad_type']) && $adforest_theme['sb_default_adpost_template_ad_type'] == '1') {
                    $ad_type_terms = adforest_get_ad_taxonomy_callback('ad_type');
                    $is_required = isset($template_meta_array['_sb_default_cat_ad_type_required']) && $template_meta_array['_sb_default_cat_ad_type_required'] == 1 ? 'true' : 'false';

                    $ad_type_options = '';
                    if (!empty($ad_type_terms) && !is_wp_error($ad_type_terms) && is_array($ad_type_terms)) {
                        foreach ($ad_type_terms as $term) {
                            $ad_type_val = $term->term_id . '|' . $term->name;
                            $is_checked = ($ad_type === $term->name) ? 'checked' : '';
                            $ad_type_options .= '<li>
                                                        <input type="radio" name="ad_type" id="' . esc_attr($term->slug) . '" value="' . esc_attr($ad_type_val) . '" data-parsley-required="' . esc_attr($is_required) . '" data-parsley-error-message="' . __('Please Select the ad type.', 'adforest') . '" ' . $is_checked . ' />
                                                        <label class="dont_change_color" for="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</label>
                                                    </li>';
                        }
                    } else {
                        $ad_type_options .= '<p>' . __('No ad types available', 'adforest') . '</p>';
                    }

                    $ad_type_field_html = '<div class="col-lg-12">
                                                <div class="field-box">
                                                    <label for="ad_type" class="form-label">' . __("Ad Type", "adforest") . '</label>
                                                    <div id="ad_type">
                                                        <ul class="select-user-type ad_type_list_adpost">
                                                            ' . $ad_type_options . '
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>';

                    if ($ai_intent_in_category) {
                        $default_intent_ad_type_html = $ad_type_field_html;
                    } else {
                        $default_template_html .= $ad_type_field_html;
                    }
                }

                if (isset($adforest_theme['sb_default_adpost_template_price_type']) && $adforest_theme['sb_default_adpost_template_price_type'] == '1') {
                    $sb_price_types_strings = array('Fixed' => __('Fixed', 'adforest'), 'Negotiable' => __('Negotiable', 'adforest'), 'on_call' => __('Price on call', 'adforest'), 'auction' => __('Auction', 'adforest'), 'free' => __('Free', 'adforest'), 'no_price' => __('No price', 'adforest'), 'range' => __('Range', 'adforest'));
                    $is_required = isset($template_meta_array['_sb_default_cat_price_type_required']) && $template_meta_array['_sb_default_cat_price_type_required'] == 1 ? 'true' : 'false';

                    $new_types_array = array();
                    if (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) > 0) {
                        $sb_price_types = $adforest_theme['sb_price_types'];
                    } elseif (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) == 0 && isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] == '') {
                        $sb_price_types = array('Fixed', 'Negotiable', 'on_call', 'auction', 'free', 'no_price', 'range');
                    } else {
                        $sb_price_types = array();
                    }

                    if (is_array($sb_price_types) && count($sb_price_types) > 0) {
                        foreach ($sb_price_types as $p_val) {
                            $new_types_array[$p_val] = $sb_price_types_strings[$p_val];
                        }
                    }

                    if (isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] != '') {
                        $sb_price_types_more_array = explode('|', $adforest_theme['sb_price_types_more']);
                        if (isset($sb_price_types_more_array) && is_array($sb_price_types_more_array) && count($sb_price_types_more_array)) {
                            foreach ($sb_price_types_more_array as $p_type_more) {
                                $new_types_array[str_replace(' ', '_', $p_type_more)] = $p_type_more;
                            }
                        }
                    }

                    $price_type_options = '';
                    if (is_array($new_types_array) && count($new_types_array) > 0) {
                        foreach ($new_types_array as $key => $value) {
                            $selected = $key === $ad_price_type ? ' selected' : '';
                            $price_type_options .= '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($value) . '</option>';
                        }
                    }

                    $default_template_html .= '<div class="row" style="display: flex; flex-wrap: wrap;"><div class="col-6" style="padding-right: 10px;">
                                                <div class="field-box">
                                                    <label for="ad_post_price_type" class="form-label">' . __("Price Type", "adforest") . '</label>
                                                    <select id="ad_post_price_type" name="ad_post_price_type" class="default-select" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required . ', 'adforest') . '">
                                                        <option value="">' . __("Select Option", 'adforest') . '</option>
                                                        ' . $price_type_options . '
                                                    </select>
                                                </div>
                                            </div>';
                }

                if (isset($adforest_theme['sb_default_adpost_template_price']) && $adforest_theme['sb_default_adpost_template_price'] == '1') {
                    $default_template_html .= '<div class="col-6" style="padding-left: 10px;">
                                                <div class="field-box ad_price_container">
                                                    <label for="ad_price" class="form-label"> ' . __("Price", "adforest") . '</label>
                                                    <input type="text" class="form-control" name="ad_price" id="ad_price" value="' . $price . '"
                                                        placeholder="' . esc_attr__("Enter Your Price", "adforest") . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required . ', 'adforest') . '">
                                                </div>
                                                <div class="field-box price_range_container" style="display: none;">
                                                    <label class="form-label">' . __("Price Range", "adforest") . '</label>
                                                    <div style="display: flex; gap: 10px;">
                                                        <input type="text" class="form-control" name="ad_price_from" id="ad_price_from" value="' . $price_from . '" placeholder="' . esc_attr("From") . '">
                                                        <input type="text" class="form-control" name="ad_price_to" id="ad_price_to" value="' . $price_to . '" placeholder="' . esc_attr("To") . '">
                                                    </div>
                                                </div>
                                            </div></div>';
                }

                if (isset($adforest_theme['sb_default_adpost_template_videoURL']) && $adforest_theme['sb_default_adpost_template_videoURL'] == '1' && $video_allowed) {
                    $default_template_html .= '<div class="col-lg-6 ad_yvideo_container">
                                                <div class="field-box">
                                                    <label for="ad_yvideo" class="form-label">' . __("Video Link", "adforest") . '</label>
                                                    <input class="form-control" name="ad_yvideo"
                                                           type="text"
                                                           id="ad_yvideo"
                                                           value="' . esc_attr($ad_yvideo) . '"
                                                           data-parsley-error-message="' . __("Should be valid youtube video url.", "adforest") . '"
                                                           data-parsley-pattern="/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/">
                                                </div>
                                            </div>';
                }

                if (isset($adforest_theme['sb_default_adpost_template_tags']) && $adforest_theme['sb_default_adpost_template_tags'] == '1' && $tags_allowed) {
                    $default_template_html .= '<div class="col-lg-12 ad_tags_container">
                                                <div class="field-box">
                                                    <div class="tags">
                                                        <label for="tags" class="control-label">
                                                            ' . __("Tags", "adforest") . '
                                                            <small>' . __("Comma separated", "adforest") . '</small>
                                                        </label>
                                                        <input class="form-control" name="tags" id="tags" value="' . esc_attr($tags) . '">
                                                    </div>
                                                </div>
                                            </div>';
                }

                if (isset($adforest_theme['sb_default_adpost_template_images']) && $adforest_theme['sb_default_adpost_template_images'] == '1') {
                    $img_size_arr = explode('-', $adforest_theme['sb_upload_size']);
                    $display_size = $img_size_arr[1];
                    ob_start();
                    ?>
                    <label for="img_dropzone"
                           class="form-label"><?php echo esc_html__("Ad Images", 'adforest'); ?></label>
                    <div id="img_dropzone" class="dropzone"></div>
                    <small><?php echo esc_html(sprintf(__('Maximum file size: %s', 'adforest'), $display_size)); ?></small>
                    <?php
                    $default_template_image_container .= ob_get_clean();
                }

                if (isset($adforest_theme['sb_default_adpost_template_condition']) && $adforest_theme['sb_default_adpost_template_condition'] == '1') {
                    $ad_condition_taxonomies = adforest_get_ad_taxonomy_callback('ad_condition');
                    ob_start();
                    ?>
                    <div class="col-lg-6 ad_condition_container">
                        <div class="field-box">
                            <label for="condition"
                                   class="form-label"><?php echo __("Condition", 'adforest'); ?></label>
                            <div class="switch-btns-box" id="condition">
                                <ul class="select-user-type ad_type_list_adpost">
                                    <?php if (!empty($ad_condition_taxonomies) && !is_wp_error($ad_condition_taxonomies) && is_array($ad_condition_taxonomies)): ?>
                                        <?php
                                        $selected_condition_value = $ad_condition_value;
                                        foreach ($ad_condition_taxonomies as $condition):
                                            $ad_condition_val = $condition->term_id . '|' . $condition->name;
                                            $is_checked = ($selected_condition_value === $condition->name);
                                            ?>
                                            <li>
                                                <input type="radio" name="ad_condition"
                                                       id="check-condition-<?php echo esc_attr($condition->slug); ?>"
                                                       value="<?php echo esc_attr($ad_condition_val); ?>"
                                                       <?php echo $is_checked ? 'checked="checked"' : ''; ?>
                                                       data-parsley-error-message="<?php echo __('This field is required . ', 'adforest'); ?>"/>
                                                <label class="dont_change_color"
                                                       for="check-condition-<?php echo esc_attr($condition->slug); ?>">
                                                    <?php echo esc_html($condition->name); ?>
                                                </label>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                    $condition_field_html = ob_get_clean();

                    if ($ai_intent_in_category) {
                        $default_intent_condition_warranty_html .= $condition_field_html;
                    } else {
                        $default_template_warranty_and_condition .= $condition_field_html;
                    }
                }

                if (isset($adforest_theme['sb_default_adpost_template_warranty']) && $adforest_theme['sb_default_adpost_template_warranty'] == '1') {
                    $ad_warranty_taxonomies = adforest_get_ad_taxonomy_callback('ad_warranty');
                    ob_start();
                    ?>
                    <div class="col-lg-6 ad_warranty_container">
                        <div class="field-box">
                            <label for="warranty"
                                   class="form-label"><?php echo __("Warranty", 'adforest'); ?></label>
                            <div class="switch-btns-box" id="warranty">
                                <ul class="select-user-type ad_type_list_adpost">
                                    <?php if (!empty($ad_warranty_taxonomies) && !is_wp_error($ad_warranty_taxonomies) && is_array($ad_warranty_taxonomies)): ?>
                                        <?php
                                        $selected_warranty_value = $ad_warranty_value;
                                        foreach ($ad_warranty_taxonomies as $warranty):
                                            $ad_warranty_val = $warranty->term_id . '|' . $warranty->name;
                                            $is_checked = ($selected_warranty_value === $warranty->name);
                                            ?>
                                            <li>
                                                <input type="radio" name="ad_warranty"
                                                       id="check-warranty-<?php echo esc_attr($warranty->slug); ?>"
                                                       value="<?php echo esc_attr($ad_warranty_val); ?>"
                                                       <?php echo $is_checked ? 'checked="checked"' : ''; ?>
                                                       data-parsley-error-message="<?php echo __('This field is required . ', 'adforest'); ?>"/>
                                                <label class="dont_change_color"
                                                       for="check-warranty-<?php echo esc_attr($warranty->slug); ?>">
                                                    <?php echo esc_html($warranty->name); ?>
                                                </label>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                    $warranty_field_html = ob_get_clean();

                    if ($ai_intent_in_category) {
                        $default_intent_condition_warranty_html .= $warranty_field_html;
                    } else {
                        $default_template_warranty_and_condition .= $warranty_field_html;
                    }
                }
            }
        }

        $intent_ad_type_html = isset($html['intent_ad_type_html']) ? $html['intent_ad_type_html'] : '';
        $intent_condition_warranty_html = isset($html['intent_condition_warranty_html']) ? $html['intent_condition_warranty_html'] : '';

        if ($default_template_on && isset($default_intent_ad_type_html)) {
            $intent_ad_type_html = $default_intent_ad_type_html;
        }
        if ($default_template_on && isset($default_intent_condition_warranty_html)) {
            $intent_condition_warranty_html = $default_intent_condition_warranty_html;
        }

        wp_send_json_success(array(
            'id' => $category_id,
            'name' => $category_name,
            'category_template_html' => isset($html['html']) ? $html['html'] : '',
            'custom_fields_html' => isset($html['custom_fields']) ? $html['custom_fields'] : '',
            'condition_and_value_fields' => isset($html['condition_and_value_fields']) ? $html['condition_and_value_fields'] : '',
            'tags_and_video_fields' => isset($html['tags_and_video_fields']) ? $html['tags_and_video_fields'] : '',
            'image_field' => isset($html['image_field']) ? $html['image_field'] : '',
            'no_template_selected' => $no_template_selected,
            'default_template_html' => $default_template_html,
            'default_template_on' => $default_template_on,
            'more_options' => array(
                'video_link_on' => $video_link_on,
                'ad_tags_on' => $ad_tags_on,
                'ad_images_on' => $ad_images_on,
                'ad_condition_on' => $ad_condition_on,
                'ad_warranty_on' => $ad_warranty_on,
                'default_template_warranty_and_condition' => $default_template_warranty_and_condition,
                'default_template_image_container' => $default_template_image_container,
            ),
            'cat_template_img_allowed' => $cat_template_img_allowed,
            'ai_intent_in_category' => $ai_intent_in_category,
            'intent_ad_type_html' => $intent_ad_type_html,
            'intent_condition_warranty_html' => $intent_condition_warranty_html,
        ));
    }
}

if (!function_exists('bornado_log_missing_message_poll_payload')) {
    /**
     * Log missing message-poll payloads with throttling so real issues stay visible
     * without flooding the debug log.
     *
     * @param int $user_id Current user ID.
     * @return void
     */
    function bornado_log_missing_message_poll_payload($user_id)
    {
        $user_id = (int) $user_id;
        $cache_key = 'bornado_missing_new_msgs_' . $user_id;

        if (get_transient($cache_key)) {
            return;
        }

        set_transient($cache_key, 1, 15 * MINUTE_IN_SECONDS);

        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $referer = wp_get_referer();

        error_log(
            sprintf(
                '[Bornado Message Poll] Missing `new_msgs` payload; defaulted safely to 0. user_id=%d request_uri=%s referer=%s',
                $user_id,
                $request_uri !== '' ? $request_uri : '(unknown)',
                $referer ? $referer : '(unknown)'
            )
        );
    }
}

if (!function_exists('bornado_override_adforest_message_check_ajax')) {
    /**
     * Replace the parent AJAX callback with a warning-safe version.
     *
     * AdForest reads `$_POST['new_msgs']` without checking it exists, but the
     * current implementation never uses that value afterwards. We keep the same
     * response contract and harden the entrypoint from the child theme layer.
     *
     * @return void
     */
    function bornado_override_adforest_message_check_ajax()
    {
        if (!function_exists('adforest_check_messages')) {
            return;
        }

        remove_action('wp_ajax_sb_check_messages', 'adforest_check_messages');
        add_action('wp_ajax_sb_check_messages', 'bornado_check_messages_safe');
    }
}
add_action('after_setup_theme', 'bornado_override_adforest_message_check_ajax', 20);

if (!function_exists('bornado_check_messages_safe')) {
    /**
     * Safe drop-in replacement for AdForest's unread-message poll endpoint.
     *
     * @return void
     */
    function bornado_check_messages_safe()
    {
        check_ajax_referer('adforest_check_messages_nonce', 'security');

        if (adforest_is_demo()) {
            echo esc_html__('Not allowed in demo mode', 'adforest');
            die();
        }

        adforest_authenticate_check();

        global $wpdb, $adforest_theme;

        $current_user_id = get_current_user_id();
        if (!isset($_POST['new_msgs'])) {
            bornado_log_missing_message_poll_payload($current_user_id);
        }

        $unread_msgs = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sb_chat_messages WHERE receiver_id = %d AND read_status = 0",
                $current_user_id
            )
        );

        if ($unread_msgs > 0) {
            $message_template = isset($adforest_theme['msg_notification_text'])
                ? (string) $adforest_theme['msg_notification_text']
                : '%count%';

            echo '1|' . str_replace('%count%', (string) $unread_msgs, $message_template) . '|' . $unread_msgs;
        }

        die();
    }
}

if (!function_exists('bornado_dequeue_rank_math_editor_assets_on_widgets_screen')) {
    /**
     * Prevent editor-only Rank Math assets from colliding with the block widgets UI.
     *
     * The block-based widgets screen already boots its own interface data store.
     * When SEO/editor bundles meant for post editing are loaded there as well,
     * WordPress can log `Store "core/interface" is already registered`.
     * Keep the workaround scoped to `widgets.php` so normal Rank Math editors
     * remain untouched.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     * @return void
     */
    function bornado_dequeue_rank_math_editor_assets_on_widgets_screen($hook_suffix)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_widgets_screen = ($hook_suffix === 'widgets.php')
            || ($screen && isset($screen->base) && (string) $screen->base === 'widgets');

        if (!$is_widgets_screen) {
            return;
        }

        $script_handles = array(
            'rank-math-editor',
            'rank-math-formats',
            'rank-math-primary-term',
            'rank-math-schema',
            'rank-math-schema-pro',
            'rank-math-pro-schema',
            'rank-math-pro-schema-filters',
            'rank-math-content-ai',
            'rank-math-content-ai-media',
        );

        foreach ($script_handles as $handle) {
            wp_dequeue_script($handle);
        }

        $style_handles = array(
            'rank-math-editor',
            'rank-math-schema',
            'rank-math-schema-pro',
            'rank-math-content-ai-page',
        );

        foreach ($style_handles as $handle) {
            wp_dequeue_style($handle);
        }
    }
}
add_action('admin_enqueue_scripts', 'bornado_dequeue_rank_math_editor_assets_on_widgets_screen', 999);

if (!function_exists('bornado_is_non_production_host')) {
    /**
     * Treat any host other than the production domain as non-production.
     *
     * @return bool
     */
    function bornado_is_non_production_host()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower(trim((string) wp_unslash($_SERVER['HTTP_HOST']))) : '';
        $host = preg_replace('/:\d+$/', '', $host);

        return !in_array($host, array('bornado.com', 'www.bornado.com'), true);
    }
}

if (!function_exists('bornado_cookieyes_markup_strip_patterns')) {
    /**
     * Regex patterns matching CookieYes script embeds that must never run inside
     * the WordPress admin or block-editor widget previews.
     *
     * @return array<int,string>
     */
    function bornado_cookieyes_markup_strip_patterns()
    {
        return array(
            // Remote CookieYes cloud loader (cdn-cookieyes.com/client_data/.../script.js).
            '#<script\b[^>]*\bsrc=(["\'])[^"\']*cookieyes[^"\']*\1[^>]*>\s*</script>#i',
            // CookieYes loader referenced by its fixed id attribute.
            '#<script\b[^>]*\bid=(["\'])cookieyes\1[^>]*>\s*</script>#i',
            // Google Consent Mode helper shipped alongside the loader.
            '#<script\b[^>]*\bid=(["\'])cookie-law-info-gcm[^"\']*\1[^>]*>.*?</script>#is',
        );
    }
}

if (!function_exists('bornado_strip_cookieyes_markup')) {
    /**
     * Remove CookieYes script embeds from an HTML fragment.
     *
     * @param string $html HTML fragment.
     * @return string
     */
    function bornado_strip_cookieyes_markup($html)
    {
        if (!is_string($html) || $html === '' || stripos($html, 'cookieyes') === false) {
            return $html;
        }

        return (string) preg_replace(bornado_cookieyes_markup_strip_patterns(), '', $html);
    }
}

if (!function_exists('bornado_strip_cookieyes_scripts_from_widgets_admin')) {
    /**
     * Remove CookieYes embeds from the block widgets admin page output.
     *
     * Acts as a defensive net for any CookieYes markup that reaches the static
     * widgets.php HTML. The remote loader has no purpose inside the admin and,
     * when it runs there, throws a noisy domain-mismatch error.
     *
     * @param string $html Full admin page HTML.
     * @return string
     */
    function bornado_strip_cookieyes_scripts_from_widgets_admin($html)
    {
        return bornado_strip_cookieyes_markup($html);
    }
}

if (!function_exists('bornado_buffer_widgets_admin_without_cookieyes')) {
    /**
     * Start a one-page output buffer for the widgets admin (all hosts).
     *
     * @return void
     */
    function bornado_buffer_widgets_admin_without_cookieyes()
    {
        if (!is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !isset($screen->base) || (string) $screen->base !== 'widgets') {
            return;
        }

        ob_start('bornado_strip_cookieyes_scripts_from_widgets_admin');
    }
}
add_action('current_screen', 'bornado_buffer_widgets_admin_without_cookieyes', 20);

if (!function_exists('bornado_remove_cookieyes_frontend_hooks')) {
    /**
     * Detach every CookieYes front-end output callback from the current request.
     *
     * The CookieYes plugin registers its loader on the front-end `wp_head`,
     * `wp_footer` and `wp_enqueue_scripts` actions. WordPress reuses those same
     * front-end actions when rendering a Legacy Widget preview inside an iframe,
     * so the loader leaks into the block widgets editor. Removing the callbacks
     * by class namespace keeps the change targeted and reversible per request.
     *
     * @return void
     */
    function bornado_remove_cookieyes_frontend_hooks()
    {
        global $wp_filter;

        $hooks = array(
            'wp_head',
            'wp_footer',
            'wp_enqueue_scripts',
            'wp_print_styles',
            'wp_print_footer_scripts',
        );

        foreach ($hooks as $hook) {
            if (empty($wp_filter[$hook]) || !($wp_filter[$hook] instanceof WP_Hook)) {
                continue;
            }

            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $function = isset($callback['function']) ? $callback['function'] : null;
                    if (
                        is_array($function)
                        && isset($function[0])
                        && is_object($function[0])
                        && strpos(get_class($function[0]), 'CookieYes') !== false
                    ) {
                        remove_action($hook, $function, $priority);
                    }
                }
            }
        }
    }
}

if (!function_exists('bornado_block_cookieyes_in_iframe_preview')) {
    /**
     * Suppress CookieYes output during Legacy Widget preview iframe renders.
     *
     * WordPress defines `IFRAME_REQUEST` while building the Legacy Widget
     * preview document (see `render_legacy_widget_preview_iframe()` and
     * `handle_legacy_widget_preview_iframe()`), which is the only front-end
     * `wp_head`/`wp_footer` render that fires from the widgets editor. Running
     * at priority 0 lets us detach the loader before it prints.
     *
     * @return void
     */
    function bornado_block_cookieyes_in_iframe_preview()
    {
        if (!defined('IFRAME_REQUEST') || !IFRAME_REQUEST) {
            return;
        }

        bornado_remove_cookieyes_frontend_hooks();
    }
}
add_action('wp_enqueue_scripts', 'bornado_block_cookieyes_in_iframe_preview', 0);
add_action('wp_head', 'bornado_block_cookieyes_in_iframe_preview', 0);

if (!function_exists('bornado_reduce_block_editor_assets_on_widgets_screen')) {
    /**
     * Drop post-editor-only block assets that bloat (and double-register stores
     * on) the block widgets screen.
     *
     * Third-party editor bundles hook `enqueue_block_editor_assets`, which also
     * fires on `widgets.php`. Several of them ship their own copy of WordPress
     * packages, producing `Store "core/interface" is already registered` and
     * needless load. These blocks are not usable inside widget areas, so the
     * removal is scoped to the widgets screen only.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     * @return void
     */
    function bornado_reduce_block_editor_assets_on_widgets_screen($hook_suffix)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_widgets_screen = ($hook_suffix === 'widgets.php')
            || ($screen && isset($screen->base) && (string) $screen->base === 'widgets');

        if (!$is_widgets_screen) {
            return;
        }

        $script_handles = array(
            'contact-form-7-contact-form-selector-editor-script',
            'wpforms-gutenberg-form-selector',
            'elementor-ai-gutenberg',
            'rank-math-command-editor-script',
            'rank-math-faq-block-editor-script-2',
            'rank-math-howto-block-editor-script-2',
            'rank-math-howto-block',
            'rank-math-toc-block-editor-script',
            'rank-math-rich-snippet-editor-script',
            'rank-math-related-posts-editor-script',
        );

        foreach ($script_handles as $handle) {
            wp_dequeue_script($handle);
        }
    }
}
add_action('admin_enqueue_scripts', 'bornado_reduce_block_editor_assets_on_widgets_screen', 1000);

/*
 * -------------------------------------------------------------------------
 * Front-end performance: safe, additive-only optimizations.
 *
 * These helpers never remove functionality or alter behavior. They only add
 * network resource hints and a single `fetchpriority` attribute to the
 * largest-contentful-paint image, so they cannot break the site.
 *
 * NOTE: The dominant performance issue measured by Lighthouse is the server
 * response time (TTFB ~9s). That is a hosting/database/page-cache concern and
 * cannot be fixed from the theme. The hints below help the rendering pipeline
 * around it but the server response itself must be addressed separately.
 * -------------------------------------------------------------------------
 */

if (!function_exists('bornado_add_perf_resource_hints')) {
    /**
     * Warm up connections to the third-party origins used on the front end.
     *
     * Purely additive: `preconnect`/`dns-prefetch` only open sockets earlier and
     * never change which assets load or how they execute.
     *
     * @param array  $hints         Resource hint URLs for the relation type.
     * @param string $relation_type Current relation type (preconnect, etc.).
     * @return array
     */
    function bornado_add_perf_resource_hints($hints, $relation_type)
    {
        if (is_admin()) {
            return $hints;
        }

        if ($relation_type === 'preconnect') {
            $hints[] = array('href' => 'https://www.gstatic.com', 'crossorigin' => 'anonymous');
            $hints[] = array('href' => 'https://cdnjs.cloudflare.com', 'crossorigin' => 'anonymous');
            $hints[] = 'https://www.google.com';
        }

        if ($relation_type === 'dns-prefetch') {
            $hints[] = 'https://www.googletagmanager.com';
            $hints[] = 'https://connect.facebook.net';
            $hints[] = 'https://fonts.gstatic.com';
            $hints[] = 'https://secure.gravatar.com';
        }

        return $hints;
    }
}
add_filter('wp_resource_hints', 'bornado_add_perf_resource_hints', 10, 2);

if (!function_exists('bornado_inject_lcp_fetchpriority')) {
    /**
     * Flag the first ad-gallery image as the high-priority LCP candidate.
     *
     * The single-ad gallery prints raw `<img>` markup (parent template
     * `ad-img-carousel.php`), so WordPress cannot auto-apply
     * `fetchpriority="high"`. We add it to the first lightbox image only.
     * Fail-safe: any miss or error returns the original HTML untouched.
     *
     * @param string $html Buffered page HTML.
     * @return string
     */
    function bornado_inject_lcp_fetchpriority($html)
    {
        if (!is_string($html) || $html === '' || stripos($html, 'lightbox') === false) {
            return $html;
        }

        try {
            $pattern = '/(<a\b[^>]*\bclass="[^"]*\blightbox\b[^"]*"[^>]*>\s*<img\b)([^>]*?)(\/?>)/i';
            $count   = 0;

            $result = preg_replace_callback(
                $pattern,
                function ($matches) {
                    if (stripos($matches[0], 'fetchpriority') !== false) {
                        return $matches[0];
                    }

                    $img_attributes = $matches[2];
                    if (stripos($img_attributes, 'loading=') !== false) {
                        $img_attributes = preg_replace('/\bloading\s*=\s*"[^"]*"/i', 'loading="eager"', $img_attributes);
                    }

                    $extra = ' fetchpriority="high"';
                    if (stripos($matches[0], 'decoding=') === false) {
                        $extra .= ' decoding="async"';
                    }

                    return $matches[1] . $img_attributes . $extra . $matches[3];
                },
                $html,
                1,
                $count
            );

            if (is_string($result) && $count > 0) {
                return $result;
            }
        } catch (\Throwable $e) {
            return $html;
        }

        return $html;
    }
}

if (!function_exists('bornado_buffer_single_ad_for_lcp')) {
    /**
     * Buffer single ad pages so the LCP image can be prioritized.
     *
     * @return void
     */
    function bornado_buffer_single_ad_for_lcp()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || is_feed()
            || !is_singular('ad_post')
        ) {
            return;
        }

        ob_start('bornado_inject_lcp_fetchpriority');
    }
}
add_action('template_redirect', 'bornado_buffer_single_ad_for_lcp', 0);
