<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_category_root_collection_page_entity')) {
    /**
     * CollectionPage schema for root category landing pages like /property/.
     *
     * @param array<string,mixed> $entity
     * @param string $page_type
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_category_root_collection_page_entity(array $entity, $page_type, array $route_context)
    {
        $category_context = bornado_schema_manager_get_category_context($route_context);
        $entity = bornado_schema_manager_build_collection_page_entity_base($page_type, $route_context, $entity);
        $canonical_url = bornado_schema_manager_get_current_canonical_url();

        if ($canonical_url !== '') {
            $entity['mainEntity'] = bornado_schema_manager_get_ref(
                bornado_schema_manager_get_item_list_id($canonical_url)
            );
        }

        $entity = bornado_schema_manager_finalize_category_collection_entity($entity, $category_context);

        return apply_filters('bornado_schema_manager_category_root_collection_entity', $entity, $route_context, $category_context);
    }
}
