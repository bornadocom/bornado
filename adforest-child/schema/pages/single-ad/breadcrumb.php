<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_single_ad_breadcrumb_entity')) {
    /**
     * Build BreadcrumbList for a singular ad page.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_single_ad_breadcrumb_entity(array $context)
    {
        $canonical_url = isset($context['canonical_url']) ? (string) $context['canonical_url'] : '';
        if ($canonical_url === '') {
            return array();
        }

        $items = array();
        if (function_exists('bornado_semantic_breadcrumb_get_single_ad_items')) {
            $post_id = isset($context['post_id']) ? (int) $context['post_id'] : 0;
            $items = bornado_semantic_breadcrumb_get_single_ad_items($post_id > 0 ? $post_id : null, true);
        }

        if (!is_array($items) || empty($items)) {
            return array();
        }

        $schema_items = array(
            array(
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => bornado_schema_manager_normalize_schema_text(__('Home', 'adforest')),
                'item'     => array(
                    '@type' => 'WebPage',
                    '@id'   => home_url('/'),
                    'name'  => bornado_schema_manager_normalize_schema_text(__('Home', 'adforest')),
                ),
            ),
        );

        $position = 2;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = isset($item['label']) ? bornado_schema_manager_normalize_schema_text($item['label']) : '';
            if ($name === '') {
                continue;
            }

            $url = isset($item['url']) ? (string) $item['url'] : '';
            $list_item = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $name,
            );

            if ($url !== '') {
                $list_item['item'] = array(
                    '@type' => 'WebPage',
                    '@id'   => $url,
                    'name'  => $name,
                );
            } else {
                $list_item['item'] = array(
                    '@type' => 'WebPage',
                    '@id'   => $canonical_url,
                    'name'  => $name,
                );
            }

            $schema_items[] = $list_item;
            $position++;
        }

        if (count($schema_items) < 2) {
            return array();
        }

        return array(
            '@type'           => 'BreadcrumbList',
            '@id'             => !empty($context['ids']['breadcrumb'])
                ? (string) $context['ids']['breadcrumb']
                : bornado_schema_manager_get_breadcrumb_id($canonical_url),
            'itemListElement' => $schema_items,
        );
    }
}

if (!function_exists('bornado_schema_manager_extend_single_ad_with_breadcrumb_graph')) {
    /**
     * Append the single-ad BreadcrumbList to the graph.
     *
     * @param array<int|string,mixed> $data
     * @param array<string,mixed>     $route_context
     * @param string                  $page_type
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_extend_single_ad_with_breadcrumb_graph(array $data, array $route_context, $page_type = 'single_ad')
    {
        unset($route_context, $page_type);

        $context = bornado_schema_manager_get_single_ad_context();
        if (empty($context)) {
            return $data;
        }

        $entity = bornado_schema_manager_build_single_ad_breadcrumb_entity($context);
        if (!empty($entity)) {
            $data['bornado_single_ad_breadcrumb'] = $entity;
        }

        return $data;
    }
}
