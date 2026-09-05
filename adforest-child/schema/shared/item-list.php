<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_item_page_type')) {
    /**
     * The listing results point to single listing pages, so use the more
     * specific ItemPage type instead of a generic WebPage.
     *
     * @return string
     */
    function bornado_schema_manager_get_item_page_type()
    {
        return 'ItemPage';
    }
}

if (!function_exists('bornado_schema_manager_get_item_list_query_paged')) {
    /**
     * Resolve the current collection page number for schema queries.
     *
     * @return int
     */
    function bornado_schema_manager_get_item_list_query_paged()
    {
        $paged = (int) get_query_var('paged');
        if ($paged > 0) {
            return $paged;
        }

        $page = (int) get_query_var('page');
        if ($page > 0) {
            return $page;
        }

        return 1;
    }
}

if (!function_exists('bornado_schema_manager_get_query_item_list_sort_value')) {
    /**
     * Resolve the active sort mode for collection-page ItemList metadata.
     *
     * @return string
     */
    function bornado_schema_manager_get_query_item_list_sort_value()
    {
        if (function_exists('bornado_sort_filters_get_selected_value')) {
            return (string) bornado_sort_filters_get_selected_value();
        }

        if (isset($_GET['sort'])) {
            return sanitize_text_field(wp_unslash((string) $_GET['sort']));
        }

        if (isset($_GET['ad']) && '1' === (string) wp_unslash($_GET['ad'])) {
            return 'featured';
        }

        return 'id-desc';
    }
}

if (!function_exists('bornado_schema_manager_get_query_item_list_sort_args')) {
    /**
     * Map the current UI sort mode to WP_Query arguments.
     *
     * @return array<string,string>
     */
    function bornado_schema_manager_get_query_item_list_sort_args()
    {
        $sort_value = bornado_schema_manager_get_query_item_list_sort_value();
        $sort_args  = array(
            'order'   => 'DESC',
            'orderby' => 'date',
            'meta_key' => '',
        );

        if ($sort_value === '') {
            return $sort_args;
        }

        $parts = explode('-', $sort_value);
        $sort_args['order'] = !empty($parts[1]) ? strtoupper((string) $parts[1]) : 'DESC';
        if (!in_array($sort_args['order'], array('ASC', 'DESC'), true)) {
            $sort_args['order'] = 'DESC';
        }

        if (!empty($parts[0]) && $parts[0] === 'price') {
            $sort_args['orderby'] = 'meta_value_num';
            $sort_args['meta_key'] = '_adforest_ad_price';
        } elseif (!empty($parts[0])) {
            $sort_args['orderby'] = (string) $parts[0];
        }

        return $sort_args;
    }
}

if (!function_exists('bornado_schema_manager_get_query_item_list_order')) {
    /**
     * Map the current UI sort mode to Schema.org ItemList ordering.
     *
     * @return string
     */
    function bornado_schema_manager_get_query_item_list_order()
    {
        $sort_value = bornado_schema_manager_get_query_item_list_sort_value();

        if (in_array($sort_value, array('id-asc', 'price-asc', 'title-asc'), true)) {
            return 'https://schema.org/ItemListOrderAscending';
        }

        if (in_array($sort_value, array('id-desc', 'price-desc', 'title-desc'), true)) {
            return 'https://schema.org/ItemListOrderDescending';
        }

        if ($sort_value === 'featured') {
            return 'https://schema.org/ItemListUnordered';
        }

        return '';
    }
}

if (!function_exists('bornado_schema_manager_build_live_ad_search_query_args')) {
    /**
     * Build a route-aware ad query for schema ItemList generation.
     *
     * This intentionally mirrors the public AdForest search flow at a lighter
     * level so schema can reflect the real result set even when the main query
     * has been bridged to the search page object.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_live_ad_search_query_args()
    {
        $sort_args      = bornado_schema_manager_get_query_item_list_sort_args();
        $posts_per_page = (int) get_option('posts_per_page');
        $posts_per_page = $posts_per_page > 0 ? $posts_per_page : 10;
        $meta_query     = array();
        $tax_query      = array();
        $route_context  = function_exists('bornado_schema_manager_get_route_context')
            ? bornado_schema_manager_get_route_context()
            : array();

        $meta_query[] = array(
            'key'     => '_adforest_ad_status_',
            'value'   => 'active',
            'compare' => '=',
        );

        if (!empty($_GET['condition'])) {
            $meta_query[] = array(
                'key'     => '_adforest_ad_condition',
                'value'   => sanitize_text_field(wp_unslash((string) $_GET['condition'])),
                'compare' => '=',
            );
        }

        $ad_type = '';
        if (!empty($_GET['ad_type'])) {
            $ad_type = sanitize_text_field(wp_unslash((string) $_GET['ad_type']));
        } elseif (!empty($_GET['adtype'])) {
            $ad_type = sanitize_text_field(wp_unslash((string) $_GET['adtype']));
        }
        if ($ad_type !== '') {
            $meta_query[] = array(
                'key'     => '_adforest_ad_type',
                'value'   => $ad_type,
                'compare' => '=',
            );
        }

        if (!empty($_GET['warranty'])) {
            $meta_query[] = array(
                'key'     => '_adforest_ad_warranty',
                'value'   => sanitize_text_field(wp_unslash((string) $_GET['warranty'])),
                'compare' => '=',
            );
        }

        if (!empty($_GET['ad'])) {
            $meta_query[] = array(
                'key'     => '_adforest_is_feature',
                'value'   => sanitize_text_field(wp_unslash((string) $_GET['ad'])),
                'compare' => '=',
            );
        } elseif (bornado_schema_manager_get_query_item_list_sort_value() === 'featured') {
            $meta_query[] = array(
                'key'     => '_adforest_is_feature',
                'value'   => '1',
                'compare' => '=',
            );
        }

        if (!empty($_GET['c'])) {
            $meta_query[] = array(
                'key'     => '_adforest_ad_currency',
                'value'   => sanitize_text_field(wp_unslash((string) $_GET['c'])),
                'compare' => '=',
            );
        }

        if (!empty($_GET['min_price'])) {
            $max_price = isset($_GET['max_price']) ? trim((string) wp_unslash($_GET['max_price'])) : '';
            $price_clause = array(
                'key'     => '_adforest_ad_price',
                'value'   => array(
                    trim((string) wp_unslash($_GET['min_price'])),
                    $max_price !== '' ? $max_price : (string) PHP_INT_MAX,
                ),
                'type'    => 'numeric',
                'compare' => 'BETWEEN',
            );
            $meta_query[] = $price_clause;
        }

        if (!empty($_GET['location']) && empty($_GET['rd'])) {
            $meta_query[] = array(
                'key'     => '_adforest_ad_location',
                'value'   => sanitize_text_field(wp_unslash((string) $_GET['location'])),
                'compare' => 'LIKE',
            );
        }

        if (!empty($_GET['country_id'])) {
            $tax_query[] = array(
                'taxonomy'         => 'ad_country',
                'field'            => 'term_id',
                'terms'            => array_map('intval', (array) wp_unslash($_GET['country_id'])),
                'include_children' => function_exists('adforest_include_child_locations') && adforest_include_child_locations() ? 1 : 0,
            );
        }

        if (!empty($_GET['ad_currency'])) {
            $tax_query[] = array(
                'taxonomy' => 'ad_currency',
                'field'    => 'term_id',
                'terms'    => array_map('intval', (array) wp_unslash($_GET['ad_currency'])),
            );
        }

        if (!empty($_GET['cat_id'])) {
            $tax_query[] = array(
                'taxonomy'         => 'ad_cats',
                'field'            => 'term_id',
                'terms'            => array_map('intval', (array) wp_unslash($_GET['cat_id'])),
                'include_children' => true,
            );
        }

        $countries_location = !empty($tax_query) ? $tax_query[0] : array();
        if (!empty($countries_location) && function_exists('apply_filters')) {
            $countries_location = apply_filters('adforest_site_location_ads', array($countries_location), 'search');
            if (is_array($countries_location) && count($countries_location) === 1 && isset($countries_location[0]) && is_array($countries_location[0])) {
                $tax_query[0] = $countries_location[0];
            }
        }

        $args = array(
            'post_type'              => 'ad_post',
            'post_status'            => 'publish',
            'posts_per_page'         => min(10, $posts_per_page),
            'paged'                  => bornado_schema_manager_get_item_list_query_paged(),
            'order'                  => $sort_args['order'],
            'orderby'                => $sort_args['orderby'],
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if (!empty($sort_args['meta_key'])) {
            $args['meta_key'] = $sort_args['meta_key'];
        }

        if (!empty($_GET['ad_title'])) {
            $args['s'] = sanitize_text_field(wp_unslash((string) $_GET['ad_title']));
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        if (
            function_exists('bornado_semantic_route_query_fix_build_tax_query')
            && !empty($route_context['is_seo_route'])
            && !empty($route_context['is_valid'])
        ) {
            $route_params = array(
                'country_id' => !empty($_GET['country_id']) ? (int) wp_unslash($_GET['country_id']) : 0,
                'cat_id'     => !empty($_GET['cat_id']) ? (int) wp_unslash($_GET['cat_id']) : 0,
            );

            $args['tax_query'] = bornado_semantic_route_query_fix_build_tax_query($args, $route_context, $route_params);
        }

        $args = apply_filters('adforest_wpml_show_all_posts', $args);

        return is_array($args) ? $args : array();
    }
}

if (!function_exists('bornado_schema_manager_get_live_ad_search_query')) {
    /**
     * Resolve the actual ad-results query behind the current search/category page.
     *
     * @return WP_Query|null
     */
    function bornado_schema_manager_get_live_ad_search_query()
    {
        static $query = null;
        static $resolved = false;

        if ($resolved) {
            return $query instanceof WP_Query ? $query : null;
        }

        $resolved = true;
        $args = bornado_schema_manager_build_live_ad_search_query_args();
        if (empty($args)) {
            return null;
        }

        $query = new WP_Query($args);

        return $query instanceof WP_Query ? $query : null;
    }
}

if (!function_exists('bornado_schema_manager_get_item_list_source_query')) {
    /**
     * Pick the best query source for schema ItemList generation.
     *
     * @return WP_Query|null
     */
    function bornado_schema_manager_get_item_list_source_query()
    {
        global $wp_query;

        $has_ad_posts = false;
        if ($wp_query instanceof WP_Query && !empty($wp_query->posts) && is_array($wp_query->posts)) {
            foreach ($wp_query->posts as $post) {
                if ($post instanceof WP_Post && $post->post_type === 'ad_post') {
                    $has_ad_posts = true;
                    break;
                }
            }
        }

        if ($has_ad_posts) {
            return $wp_query;
        }

        if (function_exists('bornado_is_ad_search_view') && bornado_is_ad_search_view()) {
            if (doing_action('wp_head') || doing_action('rank_math/json_ld') || doing_action('rank_math/head')) {
                return $wp_query instanceof WP_Query ? $wp_query : null;
            }

            return bornado_schema_manager_get_live_ad_search_query();
        }

        return $wp_query instanceof WP_Query ? $wp_query : null;
    }
}

if (!function_exists('bornado_schema_manager_build_query_item_list_entity')) {
    /**
     * Build an ItemList from the currently rendered ad query.
     *
     * This shared helper keeps country/city/category collection pages aligned so
     * their list schema stays consistent as we extend the module.
     *
     * @param string $item_list_id
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_query_item_list_entity($item_list_id, array $args = array())
    {
        $source_query = bornado_schema_manager_get_item_list_source_query();
        if (!($source_query instanceof WP_Query)) {
            return array();
        }

        $item_list_id = (string) $item_list_id;
        if ($item_list_id === '') {
            return array();
        }

        $include_empty = !empty($args['include_empty']);
        $canonical_url = !empty($args['url']) && is_string($args['url'])
            ? (string) $args['url']
            : bornado_schema_manager_get_current_canonical_url();
        $item_list_name = !empty($args['name']) && is_string($args['name'])
            ? trim((string) $args['name'])
            : '';
        $posts = isset($source_query->posts) && is_array($source_query->posts) ? $source_query->posts : array();
        $posts = array_values(array_filter($posts, static function ($post) {
            return $post instanceof WP_Post && $post->post_type === 'ad_post';
        }));
        if (empty($posts) && !$include_empty) {
            return array();
        }

        $paged          = max(1, (int) get_query_var('paged', 1));
        $posts_per_page = (int) $source_query->get('posts_per_page');
        $posts_per_page = $posts_per_page > 0 ? $posts_per_page : count($posts);
        $offset_base    = max(0, ($paged - 1) * $posts_per_page);
        $item_type      = bornado_schema_manager_get_item_page_type();
        $item_list      = array();
        $seen_urls      = array();

        foreach ($posts as $post) {
            $title = trim(wp_strip_all_tags(get_the_title($post)));
            $url   = (string) get_permalink($post);

            if ($title === '' || $url === '') {
                continue;
            }

            $normalized_url = untrailingslashit(strtolower($url));
            if (isset($seen_urls[$normalized_url])) {
                continue;
            }
            $seen_urls[$normalized_url] = true;

            $list_item = array(
                '@type'    => 'ListItem',
                'position' => $offset_base + count($item_list) + 1,
                'item'     => array(
                    '@type' => $item_type,
                    '@id'   => $url,
                    'url'   => $url,
                    'name'  => $title,
                ),
            );

            $excerpt = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) get_the_excerpt($post))));
            if ($excerpt !== '') {
                $list_item['item']['description'] = $excerpt;
            }

            $thumbnail_url = (string) get_the_post_thumbnail_url($post, 'full');
            if ($thumbnail_url !== '') {
                $list_item['item']['image'] = $thumbnail_url;
            }

            $item_list[] = $list_item;
        }

        if (empty($item_list) && !$include_empty) {
            return array();
        }

        $entity = array(
            '@type'           => 'ItemList',
            '@id'             => $item_list_id,
            'numberOfItems'   => max(count($item_list), (int) $source_query->found_posts),
            'itemListElement' => $item_list,
        );

        if ($canonical_url !== '') {
            $entity['url'] = $canonical_url;
            $entity['mainEntityOfPage'] = $canonical_url;
        }

        if ($item_list_name !== '') {
            $entity['name'] = $item_list_name;
        }

        $item_list_order = bornado_schema_manager_get_query_item_list_order();
        if ($item_list_order !== '') {
            $entity['itemListOrder'] = $item_list_order;
        }

        return $entity;
    }
}
