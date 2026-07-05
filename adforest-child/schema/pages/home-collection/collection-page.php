<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_home_collection_page_entity')) {
    /**
     * CollectionPage schema for the global home collection page.
     *
     * @param array<string,mixed> $entity
     * @param string $page_type
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_home_collection_page_entity(array $entity, $page_type, array $route_context)
    {
        $entity = bornado_schema_manager_build_collection_page_entity_base($page_type, $route_context, $entity);

        return apply_filters('bornado_schema_manager_home_collection_entity', $entity, $route_context);
    }
}
