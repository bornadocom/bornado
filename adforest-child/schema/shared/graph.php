<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_page_handlers')) {
    /**
     * Registry of page-type specific schema handlers.
     *
     * @return array<string,string>
     */
    function bornado_schema_manager_get_page_handlers()
    {
        $handlers = array(
            'home_collection'     => 'bornado_schema_manager_build_home_collection_page_entity',
            'country_collection'  => 'bornado_schema_manager_build_country_collection_page_entity',
            'city_collection'     => 'bornado_schema_manager_build_city_collection_page_entity',
            'category_root_collection' => 'bornado_schema_manager_build_category_root_collection_page_entity',
            'category_country_collection' => 'bornado_schema_manager_build_category_country_collection_page_entity',
            'category_country_city_collection' => 'bornado_schema_manager_build_category_country_city_collection_page_entity',
        );

        return (array) apply_filters('bornado_schema_manager_page_handlers', $handlers);
    }
}

if (!function_exists('bornado_schema_manager_get_graph_extenders')) {
    /**
     * Registry of page-specific graph extenders.
     *
     * Extenders may append extra nodes such as BreadcrumbList or ItemList after
     * the primary page entity has been prepared.
     *
     * @return array<string,array<int,string>>
     */
    function bornado_schema_manager_get_graph_extenders()
    {
        $extenders = array(
            'home_collection' => array(
                'bornado_schema_manager_extend_graph_with_shared_breadcrumb',
            ),
            'country_collection' => array(
                'bornado_schema_manager_extend_graph_with_shared_breadcrumb',
                'bornado_schema_manager_extend_country_collection_with_item_list_graph',
            ),
            'city_collection' => array(
                'bornado_schema_manager_extend_graph_with_shared_breadcrumb',
                'bornado_schema_manager_extend_city_collection_with_item_list_graph',
            ),
            'category_root_collection' => array(
                'bornado_schema_manager_extend_graph_with_shared_breadcrumb',
                'bornado_schema_manager_extend_category_root_with_item_list_graph',
            ),
            'category_country_collection' => array(
                'bornado_schema_manager_extend_graph_with_shared_breadcrumb',
                'bornado_schema_manager_extend_category_country_with_item_list_graph',
            ),
            'category_country_city_collection' => array(
                'bornado_schema_manager_extend_graph_with_shared_breadcrumb',
                'bornado_schema_manager_extend_category_country_city_with_item_list_graph',
            ),
        );

        return (array) apply_filters('bornado_schema_manager_graph_extenders', $extenders);
    }
}

if (!function_exists('bornado_schema_manager_collect_compact_graph_refs')) {
    /**
     * Gather compact @id-only references used across the graph.
     *
     * @param mixed $value
     * @param array<string,bool> $references
     * @return void
     */
    function bornado_schema_manager_collect_compact_graph_refs($value, array &$references)
    {
        if (!is_array($value)) {
            return;
        }

        if (
            isset($value['@id'])
            && is_string($value['@id'])
            && !isset($value['@type'])
            && count($value) === 1
        ) {
            $references[(string) $value['@id']] = true;
            return;
        }

        foreach ($value as $child) {
            bornado_schema_manager_collect_compact_graph_refs($child, $references);
        }
    }
}

if (!function_exists('bornado_schema_manager_prune_orphan_image_nodes')) {
    /**
     * Remove standalone ImageObject nodes that are no longer referenced after
     * category-page cleanup stripped inherited page-image properties.
     *
     * @param array<int|string,mixed> $data
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_prune_orphan_image_nodes(array $data)
    {
        $references = array();
        bornado_schema_manager_collect_compact_graph_refs($data, $references);

        foreach ($data as $key => $entity) {
            if (!is_array($entity) || !bornado_schema_entity_has_type($entity, array('ImageObject'))) {
                continue;
            }

            $entity_id = !empty($entity['@id']) && is_string($entity['@id'])
                ? (string) $entity['@id']
                : '';

            if ($entity_id !== '' && empty($references[$entity_id])) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}

if (!function_exists('bornado_schema_manager_enrich_graph')) {
    /**
     * Apply shared and page-specific schema mutations to Rank Math's graph.
     *
     * @param array<int|string,mixed> $data
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_enrich_graph(array $data)
    {
        $route_context     = bornado_schema_manager_get_route_context();
        $page_type         = bornado_schema_manager_get_page_type();
        $handlers          = bornado_schema_manager_get_page_handlers();
        $graph_extenders   = bornado_schema_manager_get_graph_extenders();
        $language_tag      = function_exists('bornado_frontend_language_tag')
            ? (string) bornado_frontend_language_tag()
            : '';
        $content_location  = bornado_schema_manager_build_content_location($route_context);

        foreach ($data as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }

            if (
                $language_tag !== ''
                && bornado_schema_entity_has_type($entity, array('WebSite', 'WebPage', 'CollectionPage', 'Article', 'BlogPosting', 'ItemPage'))
                && empty($entity['inLanguage'])
            ) {
                $data[$key]['inLanguage'] = $language_tag;
            }

            if (
                !empty($content_location)
                && bornado_schema_entity_has_type($entity, array('WebPage', 'CollectionPage', 'Article', 'BlogPosting', 'ItemPage'))
                && empty($entity['contentLocation'])
            ) {
                $data[$key]['contentLocation'] = $content_location;
            }
        }

        if (!isset($handlers[$page_type]) || !is_string($handlers[$page_type]) || !function_exists($handlers[$page_type])) {
            return $data;
        }

        $primary_key = bornado_schema_manager_find_primary_page_entity_key($data);
        $entity      = $primary_key !== null && isset($data[$primary_key]) && is_array($data[$primary_key])
            ? $data[$primary_key]
            : array();

        $entity = $handlers[$page_type]($entity, $page_type, $route_context);

        if ($primary_key === null) {
            $data['bornado_collection_page'] = $entity;
        } else {
            $data[$primary_key] = $entity;
        }

        if (!empty($graph_extenders[$page_type]) && is_array($graph_extenders[$page_type])) {
            foreach ($graph_extenders[$page_type] as $extender) {
                if (is_string($extender) && function_exists($extender)) {
                    $data = $extender($data, $route_context, $page_type);
                }
            }
        }

        if (function_exists('bornado_schema_manager_is_category_shape') && bornado_schema_manager_is_category_shape($page_type)) {
            $data = bornado_schema_manager_prune_orphan_image_nodes($data);
        }

        return $data;
    }
}

add_filter('rank_math/json_ld', function ($data, $json_ld) {
    if (is_admin() || !is_array($data)) {
        return $data;
    }

    return bornado_schema_manager_enrich_graph($data);
}, 20, 2);
