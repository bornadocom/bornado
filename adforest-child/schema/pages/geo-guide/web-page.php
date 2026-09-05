<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_geo_guide_settings')) {
    /**
     * Guide payload used by geo schema builders.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_get_geo_guide_settings()
    {
        $post_id = get_queried_object_id();
        if ($post_id < 1 || !function_exists('bornado_geo_guide_get_settings')) {
            return array();
        }

        $settings = bornado_geo_guide_get_settings($post_id);

        return is_array($settings) ? $settings : array();
    }
}

if (!function_exists('bornado_schema_manager_get_geo_guide_canonical_url')) {
    /**
     * Canonical URL for the active geo guide.
     *
     * @param array<string,mixed> $settings
     * @return string
     */
    function bornado_schema_manager_get_geo_guide_canonical_url(array $settings = array())
    {
        $canonical_url = function_exists('bornado_schema_manager_get_current_canonical_url')
            ? (string) bornado_schema_manager_get_current_canonical_url()
            : '';

        if ($canonical_url !== '') {
            return $canonical_url;
        }

        $post = !empty($settings['post']) && $settings['post'] instanceof WP_Post
            ? $settings['post']
            : get_queried_object();

        if ($post instanceof WP_Post) {
            $permalink = get_permalink($post);
            if (is_string($permalink) && $permalink !== '') {
                return $permalink;
            }
        }

        return '';
    }
}

if (!function_exists('bornado_schema_manager_get_geo_guide_graph_ids')) {
    /**
     * Stable @id values for geo-guide graph nodes.
     *
     * @param string $canonical_url
     * @return array<string,string>
     */
    function bornado_schema_manager_get_geo_guide_graph_ids($canonical_url)
    {
        $base = untrailingslashit((string) $canonical_url);
        if ($base === '') {
            return array();
        }

        return array(
            'webpage'    => $base . '/#webpage',
            'community'  => $base . '/#community',
            'place'      => $base . '/#place',
            'breadcrumb' => $base . '/#breadcrumb',
            'howto'      => $base . '/#howto',
            'faq'        => $base . '/#faq',
            'categories' => $base . '/#categories',
        );
    }
}

if (!function_exists('bornado_schema_manager_sanitize_geo_guide_rank_math_nodes')) {
    /**
     * Drop Rank Math nodes that would duplicate or mislabel a geo guide.
     *
     * @param array<int|string,mixed> $data
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_sanitize_geo_guide_rank_math_nodes(array $data)
    {
        foreach ($data as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }

            if (bornado_schema_entity_has_type($entity, array(
                'Article',
                'NewsArticle',
                'BlogPosting',
                'CollectionPage',
                'FAQPage',
                'HowTo',
                'ItemList',
                'BreadcrumbList',
            ))) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}

if (!function_exists('bornado_schema_manager_build_geo_guide_place_entity')) {
    /**
     * Place node for the guide's city or country.
     *
     * @param array<string,mixed> $settings
     * @param array<string,string> $ids
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_geo_guide_place_entity(array $settings, array $ids)
    {
        $country_name = !empty($settings['country_name']) ? (string) $settings['country_name'] : '';
        $city_name    = !empty($settings['city_name']) ? (string) $settings['city_name'] : '';

        if ($country_name === '' && $city_name === '') {
            return array();
        }

        $place = array(
            '@type' => $city_name !== '' ? 'Place' : 'Country',
        );

        if (!empty($ids['place'])) {
            $place['@id'] = (string) $ids['place'];
        }

        if ($city_name !== '' && $country_name !== '') {
            $place['name'] = $city_name . '، ' . $country_name;
            $place['address'] = array(
                '@type'           => 'PostalAddress',
                'addressLocality' => $city_name,
                'addressCountry'  => array(
                    '@type' => 'Country',
                    'name'  => $country_name,
                ),
            );
        } else {
            $place['name'] = $city_name !== '' ? $city_name : $country_name;
        }

        return $place;
    }
}

if (!function_exists('bornado_schema_manager_build_geo_guide_community_entity')) {
    /**
     * Topic entity for «ایرانیان {مکان}».
     *
     * @param array<string,mixed> $settings
     * @param array<string,string> $ids
     * @param string               $canonical_url
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_geo_guide_community_entity(array $settings, array $ids, $canonical_url)
    {
        $post  = !empty($settings['post']) && $settings['post'] instanceof WP_Post ? $settings['post'] : get_queried_object();
        $name  = $post instanceof WP_Post ? trim((string) get_the_title($post)) : '';
        $about = !empty($settings['hero_intro'])
            ? trim(wp_strip_all_tags((string) $settings['hero_intro']))
            : '';

        if ($name === '') {
            return array();
        }

        $community = array(
            '@type' => 'Thing',
            'name'  => $name,
        );

        if (!empty($ids['community'])) {
            $community['@id'] = (string) $ids['community'];
        }

        if ($canonical_url !== '') {
            $community['url'] = (string) $canonical_url;
        }

        if ($about !== '') {
            $community['description'] = $about;
        }

        if (!empty($ids['place']) && (!empty($settings['country_name']) || !empty($settings['city_name']))) {
            $community['areaServed'] = bornado_schema_manager_get_ref((string) $ids['place']);
        }

        return $community;
    }
}

if (!function_exists('bornado_schema_manager_build_geo_guide_web_page_entity')) {
    /**
     * Primary WebPage handler for Iranians geo guides.
     *
     * @param array<string,mixed> $entity
     * @param string              $page_type
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_geo_guide_web_page_entity(array $entity, $page_type, array $route_context)
    {
        unset($page_type, $route_context);

        $settings = bornado_schema_manager_get_geo_guide_settings();
        $canonical_url = bornado_schema_manager_get_geo_guide_canonical_url($settings);
        $ids = bornado_schema_manager_get_geo_guide_graph_ids($canonical_url);
        $graph_refs = bornado_schema_manager_get_base_graph_refs();
        $post = !empty($settings['post']) && $settings['post'] instanceof WP_Post ? $settings['post'] : get_queried_object();

        $title = function_exists('bornado_schema_manager_get_current_seo_title')
            ? bornado_schema_manager_get_current_seo_title()
            : '';
        if ($title === '' && $post instanceof WP_Post) {
            $title = trim((string) get_the_title($post));
        }

        $description = function_exists('bornado_schema_manager_get_current_meta_description')
            ? bornado_schema_manager_get_current_meta_description()
            : '';
        if ($description === '' && !empty($settings['hero_intro'])) {
            $description = trim(wp_strip_all_tags((string) $settings['hero_intro']));
        }

        $entity['@type'] = 'WebPage';

        if (!empty($ids['webpage'])) {
            $entity['@id'] = (string) $ids['webpage'];
        }

        if ($canonical_url !== '') {
            $entity['url'] = $canonical_url;
        }

        if ($title !== '') {
            $entity['name'] = $title;
        }

        if ($description !== '') {
            $entity['description'] = $description;
        }

        $language_tag = function_exists('bornado_schema_manager_get_site_language_tag')
            ? (string) bornado_schema_manager_get_site_language_tag()
            : 'fa-IR';
        if ($language_tag !== '') {
            $entity['inLanguage'] = $language_tag;
        }

        if ($post instanceof WP_Post) {
            $published = get_the_date('c', $post);
            $modified  = get_the_modified_date('c', $post);
            if (is_string($published) && $published !== '') {
                $entity['datePublished'] = $published;
            }
            if (is_string($modified) && $modified !== '') {
                $entity['dateModified'] = $modified;
            }

            $image_id = get_post_thumbnail_id($post);
            if ($image_id > 0) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
                if (is_string($image_url) && $image_url !== '') {
                    $entity['primaryImageOfPage'] = array(
                        '@type' => 'ImageObject',
                        'url'   => $image_url,
                    );
                }
            }
        }

        $has_place = (!empty($settings['country_name']) || !empty($settings['city_name'])) && !empty($ids['place']);

        $about = array();
        if (!empty($ids['community'])) {
            $about[] = bornado_schema_manager_get_ref((string) $ids['community']);
        }
        if ($has_place) {
            $about[] = bornado_schema_manager_get_ref((string) $ids['place']);
        }
        if (!empty($about)) {
            $entity['about'] = $about;
        }

        if ($has_place) {
            $entity['contentLocation'] = bornado_schema_manager_get_ref((string) $ids['place']);
        }

        if (!empty($ids['community'])) {
            $entity['mainEntity'] = bornado_schema_manager_get_ref((string) $ids['community']);
            $audience = array(
                '@type' => 'PeopleAudience',
                'name'  => $post instanceof WP_Post ? trim((string) get_the_title($post)) : $title,
            );
            if ($has_place) {
                $audience['geographicArea'] = bornado_schema_manager_get_ref((string) $ids['place']);
            }
            $entity['audience'] = $audience;
        }

        $has_part = array();
        if (!empty($ids['howto']) && !empty($settings['how_to_steps']) && is_array($settings['how_to_steps'])) {
            $has_part[] = bornado_schema_manager_get_ref((string) $ids['howto']);
        }
        if (!empty($ids['faq']) && !empty($settings['faq_items']) && is_array($settings['faq_items'])) {
            $has_part[] = bornado_schema_manager_get_ref((string) $ids['faq']);
        }
        if (!empty($ids['categories']) && !empty($settings['featured_categories']) && is_array($settings['featured_categories'])) {
            $has_part[] = bornado_schema_manager_get_ref((string) $ids['categories']);
        }
        if (!empty($has_part)) {
            $entity['hasPart'] = $has_part;
        }

        if (!empty($ids['breadcrumb'])) {
            $entity['breadcrumb'] = bornado_schema_manager_get_ref((string) $ids['breadcrumb']);
        }

        $significant = array();
        if (!empty($settings['city_listing_url'])) {
            $significant[] = (string) $settings['city_listing_url'];
        } elseif (!empty($settings['country_listing_url'])) {
            $significant[] = (string) $settings['country_listing_url'];
        }
        if (!empty($settings['secondary_cta_url'])) {
            $significant[] = (string) $settings['secondary_cta_url'];
        }
        $significant = array_values(array_unique(array_filter($significant)));
        if (!empty($significant)) {
            $entity['significantLink'] = $significant;
        }

        $related = array();
        if (!empty($settings['featured_categories']) && is_array($settings['featured_categories'])) {
            foreach ($settings['featured_categories'] as $item) {
                if (!empty($item['url'])) {
                    $related[] = (string) $item['url'];
                }
            }
        }
        $related = array_values(array_unique(array_filter($related)));
        if (!empty($related)) {
            $entity['relatedLink'] = $related;
        }

        if (!empty($graph_refs['publisher']['@id'])) {
            $entity['publisher'] = bornado_schema_manager_get_ref((string) $graph_refs['publisher']['@id']);
        }
        if (!empty($graph_refs['website']['@id'])) {
            $entity['isPartOf'] = bornado_schema_manager_get_ref((string) $graph_refs['website']['@id']);
        }

        return $entity;
    }
}
