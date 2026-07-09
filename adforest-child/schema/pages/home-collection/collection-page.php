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
        $canonical_url = bornado_schema_manager_get_current_canonical_url();
        $home_topic_name = trim((string) apply_filters(
            'bornado_schema_manager_home_collection_topic_name',
            'نیازمندی‌های ایرانیان خارج از کشور'
        ));
        $home_audience_name = trim((string) apply_filters(
            'bornado_schema_manager_home_collection_audience_name',
            'ایرانیان خارج از کشور'
        ));

        if ($canonical_url !== '') {
            $entity['mainEntity'] = bornado_schema_manager_get_ref(
                bornado_schema_manager_get_item_list_id($canonical_url)
            );
        }

        if ($home_topic_name !== '') {
            $entity['about'] = array(
                array(
                    '@type' => 'Thing',
                    'name'  => $home_topic_name,
                ),
            );
        }

        if ($home_audience_name !== '') {
            $entity['audience'] = array(
                '@type' => 'Audience',
                'audienceType' => $home_audience_name,
            );
        }

        return apply_filters('bornado_schema_manager_home_collection_entity', $entity, $route_context);
    }
}
