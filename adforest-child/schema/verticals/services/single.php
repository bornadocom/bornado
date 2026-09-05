<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_services_single_ad_entity')) {
    /**
     * Service schema for service marketplace ads.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_services_single_ad_entity(array $context)
    {
        $entity = bornado_schema_manager_build_single_ad_base_entity($context, 'Service');

        $provider = bornado_schema_manager_build_single_ad_seller_entity($context);
        if (!empty($provider)) {
            $entity['provider'] = $provider;
        }

        if (!empty($context['ids']['place'])) {
            $entity['areaServed'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
        }

        return $entity;
    }
}
