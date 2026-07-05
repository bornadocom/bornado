<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_page_uses_shared_breadcrumb')) {
    /**
     * Whether the active page type should use the shared breadcrumb schema module.
     *
     * @param string $page_type
     * @return bool
     */
    function bornado_schema_manager_page_uses_shared_breadcrumb($page_type)
    {
        return in_array((string) $page_type, array('home_collection', 'country_collection', 'city_collection'), true)
            || (
                function_exists('bornado_schema_manager_is_category_shape')
                && bornado_schema_manager_is_category_shape($page_type)
            );
    }
}

if (!function_exists('bornado_schema_manager_build_shared_breadcrumb_entity')) {
    /**
     * Build a shared BreadcrumbList entity for schema-managed collection pages.
     *
     * @param string $page_type
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_shared_breadcrumb_entity($page_type, array $route_context)
    {
        if (!bornado_schema_manager_page_uses_shared_breadcrumb($page_type)) {
            return array();
        }

        $canonical_url = bornado_schema_manager_get_current_canonical_url();
        if ($canonical_url === '') {
            return array();
        }

        $items = function_exists('bornado_semantic_breadcrumb_get_items')
            ? (array) bornado_semantic_breadcrumb_get_items()
            : array();

        if (empty($items) && function_exists('bornado_schema_manager_is_category_shape') && bornado_schema_manager_is_category_shape($page_type)) {
            $category_context = function_exists('bornado_schema_manager_get_category_context')
                ? bornado_schema_manager_get_category_context($route_context)
                : array();
            $category_terms = !empty($category_context['category_terms']) && is_array($category_context['category_terms'])
                ? $category_context['category_terms']
                : array();

            foreach ($category_terms as $term) {
                if (!($term instanceof WP_Term)) {
                    continue;
                }

                $term_link = get_term_link($term);
                $items[] = array(
                    'label' => $term->name,
                    'url'   => is_wp_error($term_link) ? '' : (string) $term_link,
                );
            }
        }

        if (empty($items) && $page_type !== 'home_collection') {
            $country_term = !empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term
                ? $route_context['country_term']
                : null;

            if ($country_term instanceof WP_Term) {
                $items = array(
                    array(
                        'label' => $country_term->name,
                        'url'   => '',
                    ),
                );
            }
        }

        $schema_items = array(
            array(
                '@type'    => 'ListItem',
                'position' => 1,
                'item'     => array(
                    '@type' => 'WebPage',
                    '@id'   => home_url('/'),
                    'url'   => home_url('/'),
                    'name'  => wp_strip_all_tags(esc_html__('Home', 'adforest')),
                ),
            ),
        );

        $position = 2;
        foreach ($items as $item) {
            $label = isset($item['label']) ? wp_strip_all_tags((string) $item['label']) : '';
            if ($label === '') {
                continue;
            }

            $item_url = isset($item['url']) ? (string) $item['url'] : '';
            if ($item_url === '') {
                $item_url = $canonical_url;
            }

            $schema_items[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'item'     => array(
                    '@type' => 'WebPage',
                    '@id'   => $item_url,
                    'url'   => $item_url,
                    'name'  => $label,
                ),
            );
            $position++;
        }

        if (count($schema_items) < 2) {
            return array();
        }

        return array(
            '@type'           => 'BreadcrumbList',
            '@id'             => bornado_schema_manager_get_breadcrumb_id($canonical_url),
            'itemListElement' => $schema_items,
        );
    }
}

if (!function_exists('bornado_schema_manager_extend_graph_with_shared_breadcrumb')) {
    /**
     * Append the shared breadcrumb schema to the page graph.
     *
     * @param array<int|string,mixed> $data
     * @param array<string,mixed> $route_context
     * @param string $page_type
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_extend_graph_with_shared_breadcrumb(array $data, array $route_context, $page_type)
    {
        $entity = bornado_schema_manager_build_shared_breadcrumb_entity($page_type, $route_context);
        if (empty($entity)) {
            return $data;
        }

        $data['bornado_shared_breadcrumb'] = $entity;

        return $data;
    }
}

if (!function_exists('bornado_schema_manager_should_skip_legacy_breadcrumb_schema')) {
    /**
     * Tell the legacy breadcrumb printer when the new schema module owns output.
     *
     * @return bool
     */
    function bornado_schema_manager_should_skip_legacy_breadcrumb_schema()
    {
        return bornado_schema_manager_page_uses_shared_breadcrumb(bornado_schema_manager_get_page_type());
    }
}
