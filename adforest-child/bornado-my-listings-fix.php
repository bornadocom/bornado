<?php

if (!function_exists('bornado_is_my_listings_active_context')) {
    /**
     * Detect profile views that list published, non-expired ads and should
     * treat missing AdForest status meta as "active" for legacy records.
     *
     * @return bool
     */
    function bornado_is_my_listings_active_context()
    {
        if (is_admin() && !wp_doing_ajax()) {
            return false;
        }

        if (is_page()) {
            $page_id = (int) get_queried_object_id();
            $template_slug = $page_id > 0 ? get_page_template_slug($page_id) : '';
            if (in_array($template_slug, array('page-my-listings.php', 'page-favorites.php'), true)) {
                return true;
            }
        }

        if (is_page_template('page-theme-dashboard.php')) {
            $page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';

            return in_array($page_type, array('my_ads', 'feature_ads', 'fav_ads'), true);
        }

        return false;
    }
}

if (!function_exists('bornado_is_legacy_my_listings_active_meta_query')) {
    /**
     * Match the parent template's original "active" meta query.
     *
     * @param mixed $meta_query Raw WP_Query meta_query value.
     * @return bool
     */
    function bornado_is_legacy_my_listings_active_meta_query($meta_query)
    {
        if (!is_array($meta_query) || empty($meta_query)) {
            return false;
        }

        foreach ($meta_query as $clause) {
            if (!is_array($clause)) {
                continue;
            }

            $key     = isset($clause['key']) ? (string) $clause['key'] : '';
            $compare = isset($clause['compare']) ? strtoupper((string) $clause['compare']) : '';
            $value   = isset($clause['value']) ? $clause['value'] : array();

            if ($key !== '_adforest_ad_status_' || $compare !== 'NOT IN') {
                continue;
            }

            $values = is_array($value) ? array_map('strval', $value) : array((string) $value);

            return in_array('expired', $values, true) && in_array('sold', $values, true);
        }

        return false;
    }
}

if (!function_exists('bornado_get_modern_active_ads_meta_query')) {
    /**
     * Treat missing ad-status meta as active for legacy ads that are still published.
     *
     * @return array<int|string, mixed>
     */
    function bornado_get_modern_active_ads_meta_query()
    {
        return array(
            'relation' => 'OR',
            array(
                'key'     => '_adforest_ad_status_',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_adforest_ad_status_',
                'value'   => array('expired', 'sold'),
                'compare' => 'NOT IN',
            ),
        );
    }
}

if (!function_exists('bornado_is_dashboard_published_ads_ajax_context')) {
    /**
     * Detect the dashboard "load more" AJAX actions that should use the same
     * published/non-expired filters as the initial page render.
     *
     * @return bool
     */
    function bornado_is_dashboard_published_ads_ajax_context()
    {
        if (!wp_doing_ajax()) {
            return false;
        }

        $action  = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        $ad_type = isset($_POST['ad_type']) ? sanitize_key(wp_unslash($_POST['ad_type'])) : '';

        if ($action !== 'load_more_dashboard_ads') {
            return false;
        }

        return in_array($ad_type, array('my_ads', 'featured_ads', 'fav_ads'), true);
    }
}

if (!function_exists('bornado_fix_modern_my_listings_active_queries')) {
    /**
     * Repair profile listing queries so published ads without
     * `_adforest_ad_status_` still appear under active/favorite/featured views.
     *
     * @param WP_Query $query Query instance about to run.
     * @return void
     */
    function bornado_fix_modern_my_listings_active_queries($query)
    {
        if (!($query instanceof WP_Query)) {
            return;
        }

        $is_profile_context = bornado_is_my_listings_active_context();
        $is_ajax_context    = bornado_is_dashboard_published_ads_ajax_context();
        if (!$is_profile_context && !$is_ajax_context) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== 'ad_post' && (!is_array($post_type) || !in_array('ad_post', $post_type, true))) {
            return;
        }

        $post_status = $query->get('post_status');
        $is_publish  = ($post_status === 'publish')
            || (is_array($post_status) && count($post_status) === 1 && in_array('publish', $post_status, true));

        if (!$is_publish) {
            return;
        }

        $author_id    = (int) $query->get('author');
        $favorite_ids = $query->get('post__in');
        $is_author_query = $author_id > 0 && $author_id === (int) get_current_user_id();
        $is_favorites_query = is_array($favorite_ids) && !empty($favorite_ids);

        if (!$is_author_query && !$is_favorites_query) {
            return;
        }

        $meta_query = $query->get('meta_query');

        if ($is_ajax_context && empty($meta_query)) {
            $query->set('meta_query', bornado_get_modern_active_ads_meta_query());
            return;
        }

        if (!bornado_is_legacy_my_listings_active_meta_query($meta_query)) {
            return;
        }

        $query->set('meta_query', bornado_get_modern_active_ads_meta_query());
    }

    add_action('pre_get_posts', 'bornado_fix_modern_my_listings_active_queries', 20);
}
