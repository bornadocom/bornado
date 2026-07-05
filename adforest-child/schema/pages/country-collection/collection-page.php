<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_country_collection_page_entity')) {
    /**
     * CollectionPage schema for country archive routes.
     *
     * @param array<string,mixed> $entity
     * @param string $page_type
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_country_collection_page_entity(array $entity, $page_type, array $route_context)
    {
        $entity = bornado_schema_manager_build_collection_page_entity_base($page_type, $route_context, $entity);
        $canonical_url = bornado_schema_manager_get_current_canonical_url();

        if ($canonical_url !== '') {
            $entity['mainEntity'] = bornado_schema_manager_get_ref(
                bornado_schema_manager_get_item_list_id($canonical_url)
            );
        }

        return apply_filters('bornado_schema_manager_country_collection_entity', $entity, $route_context);
    }
}
