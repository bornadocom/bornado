<?php
/**
 * Context-aware category counts for AdForest search widgets.
 *
 * This module keeps the logic out of parent theme files and uses a single
 * cached aggregate query per widget render instead of one count query per term.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_category_widget_context_fix_config')) {
    /**
     * Return shared configuration for contextual category counts.
     *
     * @return array<string,mixed>
     */
    function bornado_category_widget_context_fix_config()
    {
        return array(
            'cache_group' => 'bornado_category_widget',
            'cache_ttl'   => (int) apply_filters('bornado_category_widget_context_cache_ttl', 10 * MINUTE_IN_SECONDS),
        );
    }
}

if (!function_exists('bornado_category_widget_should_use_contextual_counts')) {
    /**
     * Only use contextual counts on frontend search/location/category views.
     *
     * @return bool
     */
    function bornado_category_widget_should_use_contextual_counts()
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

        if (function_exists('bornado_is_ad_search_view') && bornado_is_ad_search_view()) {
            return true;
        }

        return is_tax(array('ad_country', 'ad_cats'));
    }
}

if (!function_exists('bornado_category_widget_get_cache_version')) {
    /**
     * Read the current cache version token.
     *
     * @return string
     */
    function bornado_category_widget_get_cache_version()
    {
        $version = get_option('bornado_category_widget_context_cache_version', '1');

        return is_scalar($version) ? (string) $version : '1';
    }
}

if (!function_exists('bornado_category_widget_bump_cache_version')) {
    /**
     * Invalidate all contextual count caches by rotating the version token.
     *
     * @return void
     */
    function bornado_category_widget_bump_cache_version()
    {
        static $bumped = false;

        if ($bumped) {
            return;
        }

        $bumped = true;
        update_option('bornado_category_widget_context_cache_version', (string) microtime(true));
    }
}

if (!function_exists('bornado_category_widget_maybe_bump_cache_for_post')) {
    /**
     * Invalidate caches when an ad changes in a way that affects taxonomy counts.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    function bornado_category_widget_maybe_bump_cache_for_post($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1 || get_post_type($post_id) !== 'ad_post') {
            return;
        }

        bornado_category_widget_bump_cache_version();
    }
}
add_action('save_post_ad_post', 'bornado_category_widget_maybe_bump_cache_for_post', 20);
add_action('trashed_post', 'bornado_category_widget_maybe_bump_cache_for_post', 20);
add_action('untrashed_post', 'bornado_category_widget_maybe_bump_cache_for_post', 20);
add_action('deleted_post', 'bornado_category_widget_maybe_bump_cache_for_post', 20);

if (!function_exists('bornado_category_widget_maybe_bump_cache_for_terms')) {
    /**
     * Invalidate caches when ad category/location assignments change.
     *
     * @param int          $object_id   Object ID.
     * @param array        $terms       Assigned terms.
     * @param array        $tt_ids      Assigned term taxonomy IDs.
     * @param string       $taxonomy    Taxonomy name.
     * @param bool         $append      Whether terms were appended.
     * @param array|string $old_tt_ids  Previous term taxonomy IDs.
     * @return void
     */
    function bornado_category_widget_maybe_bump_cache_for_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids)
    {
        unset($terms, $tt_ids, $append, $old_tt_ids);

        if (!in_array($taxonomy, array('ad_cats', 'ad_country'), true)) {
            return;
        }

        if ((int) $object_id < 1 || get_post_type((int) $object_id) !== 'ad_post') {
            return;
        }

        bornado_category_widget_bump_cache_version();
    }
}
add_action('set_object_terms', 'bornado_category_widget_maybe_bump_cache_for_terms', 20, 6);

if (!function_exists('bornado_category_widget_get_location_context')) {
    /**
     * Resolve the currently active location context into a stable ID pair.
     *
     * Posts are saved with their full ad_country chain, so filtering by the
     * deepest active term is both accurate and fast.
     *
     * @return array<string,int>
     */
    function bornado_category_widget_get_location_context()
    {
        static $context = null;

        if (is_array($context)) {
            return $context;
        }

        $context = array(
            'country_term_id'  => 0,
            'city_term_id'     => 0,
            'location_term_id' => 0,
        );

        if (class_exists('Bornado_Location_Picker_Service') && method_exists('Bornado_Location_Picker_Service', 'get_selected_location')) {
            $selected = (array) Bornado_Location_Picker_Service::get_selected_location(true);
            $context['country_term_id'] = !empty($selected['country']['id']) ? (int) $selected['country']['id'] : 0;
            $context['city_term_id'] = !empty($selected['city']['id']) ? (int) $selected['city']['id'] : 0;
        }

        if ($context['country_term_id'] < 1 && function_exists('bornado_seo_routing_get_context')) {
            $route_context = (array) bornado_seo_routing_get_context();
            if (!empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term) {
                $context['country_term_id'] = (int) $route_context['country_term']->term_id;
            }
            if (!empty($route_context['city_term']) && $route_context['city_term'] instanceof WP_Term) {
                $context['city_term_id'] = (int) $route_context['city_term']->term_id;
            }
        }

        if ($context['country_term_id'] < 1 && isset($_GET['country_id']) && $_GET['country_id'] !== '') {
            $location_term = get_term((int) wp_unslash($_GET['country_id']), 'ad_country');
            if ($location_term instanceof WP_Term) {
                if ((int) $location_term->parent > 0) {
                    $context['city_term_id'] = (int) $location_term->term_id;
                } else {
                    $context['country_term_id'] = (int) $location_term->term_id;
                }
            }
        }

        $context['location_term_id'] = $context['city_term_id'] > 0
            ? $context['city_term_id']
            : $context['country_term_id'];

        return $context;
    }
}

if (!function_exists('bornado_category_widget_get_term_ids')) {
    /**
     * Normalize a term list into a unique list of positive term IDs.
     *
     * @param array<int,mixed> $terms Terms or term IDs.
     * @return array<int,int>
     */
    function bornado_category_widget_get_term_ids($terms)
    {
        $term_ids = array();

        if (!is_array($terms)) {
            return $term_ids;
        }

        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                $term_ids[] = (int) $term->term_id;
                continue;
            }

            if (is_numeric($term)) {
                $term_ids[] = (int) $term;
            }
        }

        $term_ids = array_values(array_unique(array_filter($term_ids)));
        sort($term_ids, SORT_NUMERIC);

        return $term_ids;
    }
}

if (!function_exists('bornado_category_widget_get_contextual_counts')) {
    /**
     * Return contextual counts for a set of visible category terms.
     *
     * @param array<int,mixed> $terms Visible terms or term IDs.
     * @return array<int,int>
     */
    function bornado_category_widget_get_contextual_counts($terms)
    {
        global $wpdb;

        $term_ids = bornado_category_widget_get_term_ids($terms);
        if (empty($term_ids) || !bornado_category_widget_should_use_contextual_counts()) {
            return array();
        }

        $location_context = bornado_category_widget_get_location_context();
        $location_term_id = !empty($location_context['location_term_id']) ? (int) $location_context['location_term_id'] : 0;
        if ($location_term_id < 1) {
            return array();
        }

        $config = bornado_category_widget_context_fix_config();
        $cache_payload = array(
            'version'  => bornado_category_widget_get_cache_version(),
            'location' => $location_term_id,
            'terms'    => $term_ids,
            'locale'   => determine_locale(),
        );
        $cache_hash = md5(wp_json_encode($cache_payload));
        $cache_key = 'counts_' . $cache_hash;
        $cache_group = isset($config['cache_group']) ? (string) $config['cache_group'] : 'bornado_category_widget';

        $cached = wp_cache_get($cache_key, $cache_group);
        if (is_array($cached)) {
            return $cached;
        }

        $transient_key = 'bornado_ctx_cat_' . $cache_hash;
        $cached = get_transient($transient_key);
        if (is_array($cached)) {
            wp_cache_set($cache_key, $cached, $cache_group, (int) $config['cache_ttl']);
            return $cached;
        }

        $counts = array_fill_keys($term_ids, 0);
        $placeholders = implode(', ', array_fill(0, count($term_ids), '%d'));
        $sql = "
            SELECT cat_tt.term_id AS category_term_id, COUNT(DISTINCT p.ID) AS ad_count
            FROM {$wpdb->posts} AS p
            INNER JOIN {$wpdb->term_relationships} AS cat_rel
                ON cat_rel.object_id = p.ID
            INNER JOIN {$wpdb->term_taxonomy} AS cat_tt
                ON cat_tt.term_taxonomy_id = cat_rel.term_taxonomy_id
            INNER JOIN {$wpdb->term_relationships} AS loc_rel
                ON loc_rel.object_id = p.ID
            INNER JOIN {$wpdb->term_taxonomy} AS loc_tt
                ON loc_tt.term_taxonomy_id = loc_rel.term_taxonomy_id
            WHERE p.post_type = %s
                AND p.post_status = %s
                AND cat_tt.taxonomy = %s
                AND loc_tt.taxonomy = %s
                AND loc_tt.term_id = %d
                AND cat_tt.term_id IN ($placeholders)
            GROUP BY cat_tt.term_id
        ";

        $params = array_merge(
            array('ad_post', 'publish', 'ad_cats', 'ad_country', $location_term_id),
            $term_ids
        );

        $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        if (is_array($results)) {
            foreach ($results as $row) {
                $term_id = isset($row['category_term_id']) ? (int) $row['category_term_id'] : 0;
                if ($term_id > 0 && isset($counts[$term_id])) {
                    $counts[$term_id] = isset($row['ad_count']) ? (int) $row['ad_count'] : 0;
                }
            }
        }

        wp_cache_set($cache_key, $counts, $cache_group, (int) $config['cache_ttl']);
        set_transient($transient_key, $counts, (int) $config['cache_ttl']);

        return $counts;
    }
}

if (!function_exists('bornado_category_widget_get_contextual_ad_count')) {
    /**
     * Return a single contextual category count.
     *
     * @param int $term_id Category term ID.
     * @return int|null
     */
    function bornado_category_widget_get_contextual_ad_count($term_id)
    {
        $term_id = (int) $term_id;
        if ($term_id < 1) {
            return null;
        }

        $counts = bornado_category_widget_get_contextual_counts(array($term_id));
        if (!array_key_exists($term_id, $counts)) {
            return null;
        }

        return (int) $counts[$term_id];
    }
}

if (!function_exists('bornado_category_widget_get_term_details')) {
    /**
     * Build taxonomy details while reusing contextual counts when available.
     *
     * @param mixed              $term              Term object.
     * @param array<int,int>|null $contextual_counts Optional preloaded contextual counts.
     * @return array<string,mixed>
     */
    function bornado_category_widget_get_term_details($term, $contextual_counts = null)
    {
        if (!($term instanceof WP_Term)) {
            return array();
        }

        $term_id = (int) $term->term_id;
        if (!is_array($contextual_counts) || !array_key_exists($term_id, $contextual_counts)) {
            return get_taxonomy_details($term);
        }

        $taxonomy_image = get_option('adforest_taxonomy_image' . $term_id);
        if (!$taxonomy_image) {
            $taxonomy_image = plugins_url('adforest-elementor/assets/images/no-image.jpg');
        }

        $taxonomy_icon = '';
        if ($term->taxonomy === 'ad_cats') {
            $term_meta = get_option('taxonomy_term_' . $term_id);
            $taxonomy_icon = isset($term_meta['ad_cat_icon']) ? (string) $term_meta['ad_cat_icon'] : '';
        }

        $display_mode = 'image';
        if (!empty($taxonomy_icon) && $taxonomy_image === plugins_url('adforest-elementor/assets/images/no-image.jpg')) {
            $display_mode = 'icon';
        }

        return array(
            'name'         => $term->name,
            'ad_count'     => (int) $contextual_counts[$term_id],
            'image'        => $taxonomy_image,
            'icon'         => $taxonomy_icon,
            'display_mode' => $display_mode,
            'link'         => get_term_link($term),
            'slug'         => $term->slug,
        );
    }
}

if (!function_exists('bornado_category_widget_is_parent_count_query')) {
    /**
     * Detect the lightweight taxonomy count queries emitted by the parent theme.
     *
     * AdForest's `get_active_ad_count()` builds a small `WP_Query` with:
     * - post_type = ad_post
     * - post_status = publish
     * - fields = ids
     * - one tax_query clause for the counted taxonomy
     *
     * @param WP_Query $query Query instance.
     * @return bool
     */
    function bornado_category_widget_is_parent_count_query($query)
    {
        if (!($query instanceof WP_Query) || $query->is_main_query()) {
            return false;
        }

        if ($query->get('post_type') !== 'ad_post' || $query->get('post_status') !== 'publish') {
            return false;
        }

        if ($query->get('fields') !== 'ids') {
            return false;
        }

        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query) || empty($tax_query)) {
            return false;
        }

        $taxonomy_clauses = array();
        foreach ($tax_query as $clause) {
            if (!is_array($clause) || empty($clause['taxonomy'])) {
                continue;
            }

            $taxonomy_clauses[] = $clause;
        }

        if (count($taxonomy_clauses) !== 1) {
            return false;
        }

        $clause = $taxonomy_clauses[0];

        return !empty($clause['taxonomy']) && $clause['taxonomy'] === 'ad_cats';
    }
}

if (!function_exists('bornado_category_widget_inject_location_into_parent_count_query')) {
    /**
     * Make the parent theme's per-category count query respect active location.
     *
     * This fixes hardcoded parent templates such as `search-map.php` that still
     * call `get_taxonomy_details()` directly and therefore bypass child widgets.
     *
     * @param WP_Query $query Query instance.
     * @return void
     */
    function bornado_category_widget_inject_location_into_parent_count_query($query)
    {
        if (!bornado_category_widget_should_use_contextual_counts()) {
            return;
        }

        if (!bornado_category_widget_is_parent_count_query($query)) {
            return;
        }

        $location_context = bornado_category_widget_get_location_context();
        $location_term_id = !empty($location_context['location_term_id']) ? (int) $location_context['location_term_id'] : 0;
        if ($location_term_id < 1) {
            return;
        }

        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = array();
        }

        foreach ($tax_query as $clause) {
            if (is_array($clause) && !empty($clause['taxonomy']) && $clause['taxonomy'] === 'ad_country') {
                return;
            }
        }

        $tax_query[] = array(
            'taxonomy'         => 'ad_country',
            'field'            => 'term_id',
            'terms'            => array($location_term_id),
            'include_children' => (function_exists('adforest_include_child_locations') && adforest_include_child_locations()) ? 1 : 0,
        );

        if (count($tax_query) > 1 && !isset($tax_query['relation'])) {
            $tax_query['relation'] = 'AND';
        }

        $query->set('tax_query', $tax_query);
    }
}
add_action('pre_get_posts', 'bornado_category_widget_inject_location_into_parent_count_query', 20);
