<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_sanitize_single_ad_rank_math_nodes')) {
    /**
     * Remove article-like Rank Math nodes that are inappropriate for classified ads.
     *
     * @param array<int|string,mixed> $data
     * @param array<string,mixed>     $context
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_sanitize_single_ad_rank_math_nodes(array $data, array $context)
    {
        $canonical_url = isset($context['canonical_url']) ? untrailingslashit((string) $context['canonical_url']) : '';

        foreach ($data as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }

            $is_article = bornado_schema_entity_has_type($entity, array('Article', 'NewsArticle', 'BlogPosting'));
            if (!$is_article) {
                continue;
            }

            $entity_url = '';
            if (!empty($entity['url'])) {
                $entity_url = untrailingslashit((string) $entity['url']);
            } elseif (!empty($entity['mainEntityOfPage']) && is_string($entity['mainEntityOfPage'])) {
                $entity_url = untrailingslashit((string) $entity['mainEntityOfPage']);
            } elseif (!empty($entity['@id']) && is_string($entity['@id'])) {
                $entity_url = untrailingslashit(preg_replace('/#.*$/', '', (string) $entity['@id']));
            }

            if ($canonical_url === '' || $entity_url === '' || $entity_url === $canonical_url) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}

if (!function_exists('bornado_schema_manager_build_single_ad_item_page_entity')) {
    /**
     * Primary ItemPage handler for singular ad_post pages.
     *
     * @param array<string,mixed> $entity
     * @param string              $page_type
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_single_ad_item_page_entity(array $entity, $page_type, array $route_context)
    {
        unset($page_type, $route_context);

        $context = bornado_schema_manager_get_single_ad_context();
        if (empty($context)) {
            return $entity;
        }

        $graph_refs = bornado_schema_manager_get_base_graph_refs();
        $canonical_url = (string) $context['canonical_url'];
        $webpage_id = (string) $context['ids']['webpage'];
        $ad_id = (string) $context['ids']['ad'];
        $breadcrumb_id = (string) $context['ids']['breadcrumb'];

        $entity['@type'] = 'ItemPage';
        if ($webpage_id !== '') {
            $entity['@id'] = $webpage_id;
        }
        if ($canonical_url !== '') {
            $entity['url'] = $canonical_url;
        }
        if (!empty($context['title'])) {
            $entity['name'] = (string) $context['title'];
            $entity['headline'] = (string) $context['title'];
        }
        if (!empty($context['description'])) {
            $entity['description'] = (string) $context['description'];
        }
        if (!empty($context['date_published'])) {
            $entity['datePublished'] = (string) $context['date_published'];
        }
        if (!empty($context['date_modified'])) {
            $entity['dateModified'] = (string) $context['date_modified'];
        }
        if (!empty($context['in_language'])) {
            $entity['inLanguage'] = (string) $context['in_language'];
        }

        $entity['isPartOf'] = bornado_schema_manager_get_ref($graph_refs['website']['@id']);
        $entity['publisher'] = bornado_schema_manager_get_ref($graph_refs['publisher']['@id']);

        if ($breadcrumb_id !== '') {
            $entity['breadcrumb'] = bornado_schema_manager_get_ref($breadcrumb_id);
        }
        if ($ad_id !== '') {
            $entity['mainEntity'] = bornado_schema_manager_get_ref($ad_id);
        }

        if (!empty($context['image_urls'][0])) {
            $entity['primaryImageOfPage'] = bornado_schema_manager_get_ref(
                bornado_schema_manager_get_ad_image_id($canonical_url, 1)
            );
            $entity['image'] = bornado_schema_manager_get_ref(
                bornado_schema_manager_get_ad_image_id($canonical_url, 1)
            );
        }

        $about = array();
        if (!empty($context['country_term']) && $context['country_term'] instanceof WP_Term) {
            $about[] = array('@type' => 'Place', 'name' => $context['country_term']->name);
        }
        if (!empty($context['city_term']) && $context['city_term'] instanceof WP_Term) {
            $about[] = array('@type' => 'Place', 'name' => $context['city_term']->name);
        }
        if (!empty($context['deepest_category']) && $context['deepest_category'] instanceof WP_Term) {
            $about[] = array('@type' => 'Thing', 'name' => $context['deepest_category']->name);
        }
        if (!empty($about)) {
            $entity['about'] = $about;
        }

        if (!empty($context['ids']['place'])) {
            $entity['contentLocation'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
        }

        return apply_filters('bornado_schema_manager_single_ad_item_page_entity', $entity, $context);
    }
}
