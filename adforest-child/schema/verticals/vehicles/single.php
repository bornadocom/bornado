<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_vehicles_single_ad_entity')) {
    /**
     * Vehicle schema for vehicle marketplace ads.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_vehicles_single_ad_entity(array $context)
    {
        $entity = bornado_schema_manager_build_single_ad_base_entity($context, 'Vehicle');

        if (!empty($context['ad_type_label'])) {
            $entity = bornado_schema_manager_append_additional_property(
                $entity,
                __('Ad type', 'adforest'),
                (string) $context['ad_type_label']
            );
        }

        return $entity;
    }
}
