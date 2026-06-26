<?php
/**
 * Enforce semantic route location/category constraints on ad-search queries.
 *
 * Full-page semantic routes already inject `country_id` / `cat_id` into `$_GET`
 * before the AdForest templates build their query. The missing piece is the
 * Search 2.0 AJAX endpoint: it only receives public query params such as
 * `adtype`, so route-defining location/category scope can be lost unless we
 * reconstruct it server-side from the current semantic page URL.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_semantic_route_query_fix_is_ad_query_args')) {
    /**
     * Return whether the supplied args belong to a public ad search query.
     *
     * @param mixed $args
     * @return bool
     */
    function bornado_semantic_route_query_fix_is_ad_query_args($args)
    {
        if (!is_array($args)) {
            return false;
        }

        return isset($args['post_type']) && $args['post_type'] === 'ad_post';
    }
}

if (!function_exists('bornado_semantic_route_query_fix_is_frontend_request')) {
    /**
     * Whether the current request is a public frontend request.
     *
     * @return bool
     */
    function bornado_semantic_route_query_fix_is_frontend_request()
    {
        return !(
            is_admin()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || wp_is_json_request()
        );
    }
}

if (!function_exists('bornado_semantic_route_query_fix_is_public_ajax_search_request')) {
    /**
     * Whether the current request is the public AdForest AJAX search endpoint.
     *
     * @return bool
     */
    function bornado_semantic_route_query_fix_is_public_ajax_search_request()
    {
        if (!wp_doing_ajax()) {
            return false;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash((string) $_REQUEST['action'])) : '';

        return $action === 'adforest_ajax_search';
    }
}

if (!function_exists('bornado_semantic_route_query_fix_get_home_relative_segments')) {
    /**
     * Split a URL path into segments relative to the site's home path.
     *
     * @param string $url
     * @return array<int,string>
     */
    function bornado_semantic_route_query_fix_get_home_relative_segments($url)
    {
        $path = trim((string) wp_parse_url((string) $url, PHP_URL_PATH), '/');
        if ($path === '') {
            return array();
        }

        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        if ($home_path === '') {
            return $segments;
        }

        $home_segments = array_values(array_filter(explode('/', $home_path), 'strlen'));
        if (empty($home_segments)) {
            return $segments;
        }

        $prefix = array_slice($segments, 0, count($home_segments));
        if ($prefix === $home_segments) {
            return array_slice($segments, count($home_segments));
        }

        return $segments;
    }
}

if (!function_exists('bornado_semantic_route_query_fix_resolve_root_country')) {
    /**
     * Resolve a root country term by slug.
     *
     * @param string $slug
     * @return WP_Term|null
     */
    function bornado_semantic_route_query_fix_resolve_root_country($slug)
    {
        $term = get_term_by('slug', sanitize_title((string) $slug), 'ad_country');
        if (!($term instanceof WP_Term) || (int) $term->parent !== 0) {
            return null;
        }

        return $term;
    }
}

if (!function_exists('bornado_semantic_route_query_fix_resolve_city_for_country')) {
    /**
     * Resolve a city term that belongs to the supplied root country.
     *
     * @param string  $slug
     * @param WP_Term $country_term
     * @return WP_Term|null
     */
    function bornado_semantic_route_query_fix_resolve_city_for_country($slug, WP_Term $country_term)
    {
        $term = get_term_by('slug', sanitize_title((string) $slug), 'ad_country');
        if (!($term instanceof WP_Term) || (int) $term->term_id === (int) $country_term->term_id) {
            return null;
        }

        return in_array((int) $country_term->term_id, array_map('intval', get_ancestors((int) $term->term_id, 'ad_country', 'taxonomy')), true)
            ? $term
            : null;
    }
}

if (!function_exists('bornado_semantic_route_query_fix_resolve_category_chain')) {
    /**
     * Resolve a semantic category chain from path segments.
     *
     * @param array<int,string> $segments
     * @return array<int,WP_Term>|false
     */
    function bornado_semantic_route_query_fix_resolve_category_chain(array $segments)
    {
        if (empty($segments)) {
            return array();
        }

        $chain = array();
        $previous = null;

        foreach ($segments as $segment) {
            $term = get_term_by('slug', sanitize_title((string) $segment), 'ad_cats');
            if (!($term instanceof WP_Term)) {
                return false;
            }

            if ($previous instanceof WP_Term) {
                $ancestors = array_map('intval', get_ancestors((int) $term->term_id, 'ad_cats', 'taxonomy'));
                if (!in_array((int) $previous->term_id, $ancestors, true)) {
                    return false;
                }
            }

            $chain[] = $term;
            $previous = $term;
        }

        return $chain;
    }
}

if (!function_exists('bornado_semantic_route_query_fix_resolve_context_from_url')) {
    /**
     * Best-effort semantic route resolver used for AJAX fallbacks.
     *
     * @param string $url
     * @return array<string,mixed>
     */
    function bornado_semantic_route_query_fix_resolve_context_from_url($url)
    {
        $segments = bornado_semantic_route_query_fix_get_home_relative_segments($url);
        if (empty($segments)) {
            return array();
        }

        $country_term = bornado_semantic_route_query_fix_resolve_root_country($segments[0]);
        $city_term = null;
        $remaining_segments = $segments;

        if ($country_term instanceof WP_Term) {
            $remaining_segments = array_slice($segments, 1);
            if (!empty($remaining_segments)) {
                $possible_city = bornado_semantic_route_query_fix_resolve_city_for_country($remaining_segments[0], $country_term);
                if ($possible_city instanceof WP_Term) {
                    $city_term = $possible_city;
                    $remaining_segments = array_slice($remaining_segments, 1);
                }
            }
        }

        $category_terms = $country_term instanceof WP_Term
            ? bornado_semantic_route_query_fix_resolve_category_chain($remaining_segments)
            : bornado_semantic_route_query_fix_resolve_category_chain($segments);

        if ($category_terms === false) {
            return array();
        }

        $deepest_term = !empty($category_terms) ? end($category_terms) : null;
        if (
            !($country_term instanceof WP_Term)
            && !($deepest_term instanceof WP_Term)
        ) {
            return array();
        }

        return array(
            'is_seo_route'   => true,
            'is_valid'       => true,
            'country_term'   => $country_term instanceof WP_Term ? $country_term : null,
            'city_term'      => $city_term instanceof WP_Term ? $city_term : null,
            'category_terms' => is_array($category_terms) ? $category_terms : array(),
            'deepest_term'   => $deepest_term instanceof WP_Term ? $deepest_term : null,
        );
    }
}

if (!function_exists('bornado_semantic_route_query_fix_get_current_route_context')) {
    /**
     * Resolve the active semantic route context for frontend or AJAX requests.
     *
     * @return array<string,mixed>
     */
    function bornado_semantic_route_query_fix_get_current_route_context()
    {
        if (!function_exists('bornado_seo_routing_get_context')) {
            return array();
        }

        if (!wp_doing_ajax()) {
            $route_context = bornado_seo_routing_get_context();
            return is_array($route_context) ? $route_context : array();
        }

        $referer = isset($_SERVER['HTTP_REFERER']) ? wp_unslash((string) $_SERVER['HTTP_REFERER']) : '';
        if ($referer === '') {
            return array();
        }

        return bornado_semantic_route_query_fix_resolve_context_from_url($referer);
    }
}

if (!function_exists('bornado_semantic_route_query_fix_extract_tax_clauses')) {
    /**
     * Recursively flatten taxonomy clauses from AdForest's mixed tax_query shape.
     *
     * @param mixed $node
     * @param array<int,array<string,mixed>> $clauses
     * @return void
     */
    function bornado_semantic_route_query_fix_extract_tax_clauses($node, array &$clauses)
    {
        if (!is_array($node)) {
            return;
        }

        if (isset($node['taxonomy']) && is_string($node['taxonomy']) && $node['taxonomy'] !== '') {
            $clauses[] = $node;
            return;
        }

        foreach ($node as $key => $value) {
            if ($key === 'relation') {
                continue;
            }

            if (is_array($value)) {
                bornado_semantic_route_query_fix_extract_tax_clauses($value, $clauses);
            }
        }
    }
}

if (!function_exists('bornado_semantic_route_query_fix_build_tax_query')) {
    /**
     * Rebuild tax_query with the current semantic route location/category enforced.
     *
     * @param array<string,mixed> $args
     * @param array<string,mixed> $route_context
     * @param array<string,mixed> $params
     * @return array<int|string,mixed>
     */
    function bornado_semantic_route_query_fix_build_tax_query(array $args, array $route_context, array $params = array())
    {
        $existing_clauses = array();
        $tax_query = isset($args['tax_query']) && is_array($args['tax_query']) ? $args['tax_query'] : array();

        bornado_semantic_route_query_fix_extract_tax_clauses($tax_query, $existing_clauses);

        $normalized = array();
        foreach ($existing_clauses as $clause) {
            $taxonomy = isset($clause['taxonomy']) ? (string) $clause['taxonomy'] : '';
            if ($taxonomy === 'ad_country' || $taxonomy === 'ad_cats') {
                continue;
            }
            $normalized[] = $clause;
        }

        $location_term_id = !empty($params['country_id']) && is_numeric($params['country_id'])
            ? (int) $params['country_id']
            : 0;

        if ($location_term_id < 1 && !empty($route_context['city_term']) && $route_context['city_term'] instanceof WP_Term) {
            $location_term_id = (int) $route_context['city_term']->term_id;
        } elseif ($location_term_id < 1 && !empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term) {
            $location_term_id = (int) $route_context['country_term']->term_id;
        }

        if ($location_term_id > 0) {
            $normalized[] = array(
                'taxonomy'         => 'ad_country',
                'field'            => 'term_id',
                'terms'            => array($location_term_id),
                'include_children' => (function_exists('adforest_include_child_locations') && adforest_include_child_locations()) ? 1 : 0,
            );
        }

        $category_term_id = !empty($params['cat_id']) && is_numeric($params['cat_id'])
            ? (int) $params['cat_id']
            : 0;

        if ($category_term_id < 1 && !empty($route_context['deepest_term']) && $route_context['deepest_term'] instanceof WP_Term) {
            $category_term_id = (int) $route_context['deepest_term']->term_id;
        }

        if ($category_term_id > 0) {
            $normalized[] = array(
                'taxonomy'         => 'ad_cats',
                'field'            => 'term_id',
                'terms'            => array($category_term_id),
                'include_children' => (function_exists('adforest_include_child_categories') && adforest_include_child_categories()) ? 1 : 0,
            );
        }

        if (count($normalized) > 1) {
            $normalized['relation'] = 'AND';
        }

        return $normalized;
    }
}

if (!function_exists('bornado_enforce_semantic_route_tax_query_on_ad_search')) {
    /**
     * Guarantee ad-search queries stay scoped to the semantic route.
     *
     * @param mixed $args
     * @return mixed
     */
    function bornado_enforce_semantic_route_tax_query_on_ad_search($args)
    {
        if (
            !bornado_semantic_route_query_fix_is_frontend_request()
            || wp_doing_ajax()
            || !bornado_semantic_route_query_fix_is_ad_query_args($args)
            || !function_exists('bornado_is_ad_search_view')
            || !bornado_is_ad_search_view()
        ) {
            return $args;
        }

        $route_context = bornado_semantic_route_query_fix_get_current_route_context();
        if (!is_array($route_context)) {
            return $args;
        }

        if (empty($route_context['is_seo_route']) || empty($route_context['is_valid'])) {
            return $args;
        }

        $args['tax_query'] = bornado_semantic_route_query_fix_build_tax_query($args, $route_context);

        return $args;
    }

    add_filter('adforest_wpml_show_all_posts', 'bornado_enforce_semantic_route_tax_query_on_ad_search', 25);
}

if (!function_exists('bornado_enforce_semantic_route_tax_query_on_ajax_search')) {
    /**
     * Guarantee AJAX search requests inherit the semantic route's scope when
     * structural filters are omitted from the public query string.
     *
     * @param mixed                $args
     * @param array<string,mixed>  $params
     * @return mixed
     */
    function bornado_enforce_semantic_route_tax_query_on_ajax_search($args, $params)
    {
        if (
            !bornado_semantic_route_query_fix_is_public_ajax_search_request()
            || !bornado_semantic_route_query_fix_is_ad_query_args($args)
        ) {
            return $args;
        }

        $route_context = bornado_semantic_route_query_fix_get_current_route_context();
        if (
            !is_array($route_context)
            || empty($route_context['is_seo_route'])
            || empty($route_context['is_valid'])
        ) {
            return $args;
        }

        $args['tax_query'] = bornado_semantic_route_query_fix_build_tax_query(
            $args,
            $route_context,
            is_array($params) ? $params : array()
        );

        return $args;
    }

    add_filter('adforest_ajax_search_query_args', 'bornado_enforce_semantic_route_tax_query_on_ajax_search', 25, 2);
}
