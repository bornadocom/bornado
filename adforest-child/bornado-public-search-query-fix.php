<?php
/**
 * Child-theme fixes for full-refresh public search queries.
 *
 * Search 2.0 (AJAX) treats a lone `min_price` as "from X upwards" by using a
 * very high max bound. The legacy PHP templates still build a BETWEEN query
 * with an empty upper value on full refresh, which collapses valid result sets
 * to zero. Normalize that meta query so refresh and AJAX return the same ads.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_public_search_query_fix_is_relevant_request')) {
    /**
     * Only run on frontend public ad-search requests.
     *
     * @return bool
     */
    function bornado_public_search_query_fix_is_relevant_request()
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

        if (empty($_GET['min_price']) || !empty($_GET['max_price'])) {
            return false;
        }

        if (function_exists('bornado_is_query_only_ad_search_bridge_active') && bornado_is_query_only_ad_search_bridge_active()) {
            return true;
        }

        if (function_exists('bornado_get_search_page_id')) {
            $search_page_id = (int) bornado_get_search_page_id();
            if ($search_page_id > 0 && is_page($search_page_id)) {
                return true;
            }
        }

        return is_page_template('page-search.php') || is_tax(array('ad_cats', 'ad_country'));
    }
}

if (!function_exists('bornado_public_search_query_fix_normalize_meta_query')) {
    /**
     * Recursively repair malformed price BETWEEN clauses.
     *
     * @param mixed $meta_query
     * @return mixed
     */
    function bornado_public_search_query_fix_normalize_meta_query($meta_query)
    {
        if (!is_array($meta_query)) {
            return $meta_query;
        }

        foreach ($meta_query as $index => $clause) {
            if (!is_array($clause)) {
                continue;
            }

            $is_price_between =
                isset($clause['key'], $clause['compare'])
                && $clause['key'] === '_adforest_ad_price'
                && strtoupper((string) $clause['compare']) === 'BETWEEN';

            if ($is_price_between) {
                $value = isset($clause['value']) && is_array($clause['value']) ? array_values($clause['value']) : array();
                $min   = isset($value[0]) ? trim((string) $value[0]) : '';
                $max   = isset($value[1]) ? trim((string) $value[1]) : '';

                if ($min !== '' && $max === '') {
                    $clause['value'][1] = (string) PHP_INT_MAX;
                    $meta_query[$index] = $clause;
                }

                continue;
            }

            $meta_query[$index] = bornado_public_search_query_fix_normalize_meta_query($clause);
        }

        return $meta_query;
    }
}

if (!function_exists('bornado_fix_full_refresh_public_search_query_args')) {
    /**
     * Align full-refresh price filtering with Search 2.0 AJAX behavior.
     *
     * @param mixed $args
     * @return mixed
     */
    function bornado_fix_full_refresh_public_search_query_args($args)
    {
        if (!is_array($args) || !bornado_public_search_query_fix_is_relevant_request()) {
            return $args;
        }

        $post_type = isset($args['post_type']) ? $args['post_type'] : '';
        if ($post_type !== 'ad_post') {
            return $args;
        }

        if (empty($args['meta_query']) || !is_array($args['meta_query'])) {
            return $args;
        }

        $args['meta_query'] = bornado_public_search_query_fix_normalize_meta_query($args['meta_query']);

        return $args;
    }

    add_filter('adforest_wpml_show_all_posts', 'bornado_fix_full_refresh_public_search_query_args', 20);
}
