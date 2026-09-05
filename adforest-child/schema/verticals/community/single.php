<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_community_single_ad_entity')) {
    /**
     * CreativeWork schema for community listings.
     *
     * ClassifiedAd is avoided because many validators/Google subsets do not accept it.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_community_single_ad_entity(array $context)
    {
        $entity = bornado_schema_manager_build_single_ad_base_entity($context, 'CreativeWork');

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
