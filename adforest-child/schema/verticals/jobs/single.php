<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_jobs_single_ad_entity')) {
    /**
     * JobPosting when minimum valid employer-job fields exist; otherwise CreativeWork.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_jobs_single_ad_entity(array $context)
    {
        $title = isset($context['title']) ? (string) $context['title'] : '';
        $description = isset($context['description']) ? (string) $context['description'] : '';
        $date_posted = isset($context['date_published']) ? (string) $context['date_published'] : '';
        $has_location = (!empty($context['city_term']) && $context['city_term'] instanceof WP_Term)
            || (!empty($context['country_term']) && $context['country_term'] instanceof WP_Term)
            || !empty($context['ids']['place']);
        $hiring_name = isset($context['poster_name']) ? bornado_schema_manager_normalize_schema_text($context['poster_name']) : '';

        $can_emit_job = ($title !== '' && $description !== '' && $date_posted !== '' && $has_location && $hiring_name !== '');

        if (!$can_emit_job) {
            return bornado_schema_manager_build_single_ad_base_entity($context, 'CreativeWork');
        }

        $canonical_url = isset($context['canonical_url']) ? (string) $context['canonical_url'] : '';
        $entity = array(
            '@type'       => 'JobPosting',
            '@id'         => !empty($context['ids']['ad']) ? (string) $context['ids']['ad'] : bornado_schema_manager_get_ad_entity_id($canonical_url),
            'title'       => $title,
            'description' => $description,
            'datePosted'  => $date_posted,
            'url'         => $canonical_url,
            'hiringOrganization' => array(
                '@type' => 'Organization',
                'name'  => $hiring_name,
            ),
        );

        if (!empty($context['ids']['webpage'])) {
            $entity['mainEntityOfPage'] = bornado_schema_manager_get_ref((string) $context['ids']['webpage']);
        }
        if (!empty($context['deepest_category']) && $context['deepest_category'] instanceof WP_Term) {
            $entity['occupationalCategory'] = $context['deepest_category']->name;
        }
        if (!empty($context['ids']['place'])) {
            $entity['jobLocation'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
        }

        if (!empty($context['ad_status']) && in_array((string) $context['ad_status'], array('sold', 'expired'), true)) {
            $entity['validThrough'] = !empty($context['date_modified'])
                ? (string) $context['date_modified']
                : (string) $date_posted;
        }

        return $entity;
    }
}
