<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_category_root_item_list_entity')) {
    /**
     * Build ItemList for root category landing pages.
     *
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_category_root_item_list_entity(array $route_context)
    {
        $canonical_url = bornado_schema_manager_get_current_canonical_url();
        if ($canonical_url === '') {
            return array();
        }

        $category_context = function_exists('bornado_schema_manager_get_category_context')
            ? bornado_schema_manager_get_category_context($route_context)
            : array();
        $item_list_name = function_exists('bornado_schema_manager_build_category_item_list_name')
            ? bornado_schema_manager_build_category_item_list_name($category_context)
            : '';

        return bornado_schema_manager_build_query_item_list_entity(
            bornado_schema_manager_get_item_list_id($canonical_url),
            array(
                'include_empty' => true,
                'url'           => $canonical_url,
                'name'          => $item_list_name,
            )
        );
    }
}

if (!function_exists('bornado_schema_manager_extend_category_root_with_item_list_graph')) {
    /**
     * Append ItemList to the root category collection graph.
     *
     * @param array<int|string,mixed> $data
     * @param array<string,mixed> $route_context
     * @param string $page_type
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_extend_category_root_with_item_list_graph(array $data, array $route_context, $page_type = 'category_root_collection')
    {
        $entity = bornado_schema_manager_build_category_root_item_list_entity($route_context);
        if (empty($entity)) {
            return $data;
        }

        $data['bornado_category_root_item_list'] = $entity;

        return $data;
    }
}
