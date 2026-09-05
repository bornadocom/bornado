<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_property_single_ad_entity')) {
    /**
     * Accommodation/House schema for property ads.
     *
     * RealEstateListing is a WebPage type and must not carry Accommodation fields
     * like numberOfRooms; those belong on Accommodation subtypes.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_property_single_ad_entity(array $context)
    {
        $entity = bornado_schema_manager_build_single_ad_base_entity($context, 'Accommodation');

        if (!empty($context['ad_type_label'])) {
            $entity = bornado_schema_manager_append_additional_property(
                $entity,
                __('Ad type', 'adforest'),
                (string) $context['ad_type_label']
            );
        }

        if (!empty($context['ids']['place'])) {
            $entity['containedInPlace'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
        }

        return $entity;
    }
}
