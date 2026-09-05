<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_geo_guide_breadcrumb_entity')) {
    /**
     * BreadcrumbList from the geo-guide parent tree.
     *
     * @param array<string,mixed>  $settings
     * @param array<string,string> $ids
     * @param string               $canonical_url
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_geo_guide_breadcrumb_entity(array $settings, array $ids, $canonical_url)
    {
        $post = !empty($settings['post']) && $settings['post'] instanceof WP_Post ? $settings['post'] : get_queried_object();
        if (!($post instanceof WP_Post) || $canonical_url === '') {
            return array();
        }

        $items = array();
        $position = 1;
        $home_name = function_exists('bornado_schema_manager_normalize_schema_text')
            ? bornado_schema_manager_normalize_schema_text(__('Home', 'adforest'))
            : __('Home', 'adforest');

        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $home_name,
            'item'     => home_url('/'),
        );

        $ancestors = array_reverse(array_map('intval', get_post_ancestors($post)));
        foreach ($ancestors as $ancestor_id) {
            $title = trim((string) get_the_title($ancestor_id));
            $url   = get_permalink($ancestor_id);
            if ($title === '' || !is_string($url) || $url === '') {
                continue;
            }

            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $title,
                'item'     => $url,
            );
        }

        $current_name = trim((string) get_the_title($post));
        if ($current_name === '') {
            $current_name = $canonical_url;
        }

        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => $current_name,
            'item'     => $canonical_url,
        );

        if (count($items) < 2) {
            return array();
        }

        $breadcrumb = array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        );

        if (!empty($ids['breadcrumb'])) {
            $breadcrumb['@id'] = (string) $ids['breadcrumb'];
        }

        return $breadcrumb;
    }
}

if (!function_exists('bornado_schema_manager_build_geo_guide_howto_entity')) {
    /**
     * HowTo node from the guide's practical steps.
     *
     * @param array<string,mixed>  $settings
     * @param array<string,string> $ids
     * @param string               $canonical_url
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_geo_guide_howto_entity(array $settings, array $ids, $canonical_url)
    {
        $steps = !empty($settings['how_to_steps']) && is_array($settings['how_to_steps'])
            ? array_values(array_filter(array_map('strval', $settings['how_to_steps'])))
            : array();

        if (count($steps) < 2) {
            return array();
        }

        $location = !empty($settings['location']) ? (string) $settings['location'] : '';
        $how_to_steps = array();
        $position = 1;

        foreach ($steps as $step_text) {
            $text = trim(wp_strip_all_tags($step_text));
            if ($text === '') {
                continue;
            }

            $how_to_steps[] = array(
                '@type'    => 'HowToStep',
                'position' => $position,
                'name'     => sprintf('گام %s', number_format_i18n($position)),
                'text'     => $text,
            );
            $position++;
        }

        if (count($how_to_steps) < 2) {
            return array();
        }

        $howto = array(
            '@type'           => 'HowTo',
            'name'            => $location !== ''
                ? sprintf('چطور از Bornado در %s استفاده کنیم', $location)
                : 'چطور از Bornado استفاده کنیم',
            'inLanguage'      => function_exists('bornado_schema_manager_get_site_language_tag')
                ? bornado_schema_manager_get_site_language_tag()
                : 'fa-IR',
            'step'            => $how_to_steps,
        );

        if (!empty($ids['howto'])) {
            $howto['@id'] = (string) $ids['howto'];
        }

        if ($canonical_url !== '') {
            $howto['url'] = $canonical_url . '#bornado-guide-how-to';
        }

        return $howto;
    }
}

if (!function_exists('bornado_schema_manager_build_geo_guide_faq_entity')) {
    /**
     * FAQPage node from city-specific questions.
     *
     * @param array<string,mixed>  $settings
     * @param array<string,string> $ids
     * @param string               $canonical_url
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_geo_guide_faq_entity(array $settings, array $ids, $canonical_url)
    {
        $faq_items = !empty($settings['faq_items']) && is_array($settings['faq_items'])
            ? $settings['faq_items']
            : array();

        $main_entity = array();
        foreach ($faq_items as $item) {
            if (!is_array($item) || empty($item['question']) || empty($item['answer'])) {
                continue;
            }

            $question = trim(wp_strip_all_tags((string) $item['question']));
            $answer   = trim(wp_strip_all_tags((string) $item['answer']));
            if ($question === '' || $answer === '') {
                continue;
            }

            $main_entity[] = array(
                '@type'          => 'Question',
                'name'           => $question,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => $answer,
                ),
            );
        }

        if (empty($main_entity)) {
            return array();
        }

        $location = !empty($settings['location']) ? (string) $settings['location'] : '';
        $faq = array(
            '@type'      => 'FAQPage',
            'name'       => $location !== ''
                ? sprintf('پرسش‌های رایج درباره نیازمندی‌های %s', $location)
                : 'پرسش‌های رایج',
            'inLanguage' => function_exists('bornado_schema_manager_get_site_language_tag')
                ? bornado_schema_manager_get_site_language_tag()
                : 'fa-IR',
            'mainEntity' => $main_entity,
        );

        if (!empty($ids['faq'])) {
            $faq['@id'] = (string) $ids['faq'];
        }

        if ($canonical_url !== '') {
            $faq['url'] = $canonical_url . '#bornado-guide-faq';
        }

        return $faq;
    }
}

if (!function_exists('bornado_schema_manager_build_geo_guide_category_list_entity')) {
    /**
     * ItemList of category listing routes, not live ads.
     *
     * @param array<string,mixed>  $settings
     * @param array<string,string> $ids
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_geo_guide_category_list_entity(array $settings, array $ids)
    {
        $featured = !empty($settings['featured_categories']) && is_array($settings['featured_categories'])
            ? $settings['featured_categories']
            : array();

        $elements = array();
        $position = 1;
        foreach ($featured as $item) {
            if (!is_array($item) || empty($item['name']) || empty($item['url'])) {
                continue;
            }

            $elements[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => (string) $item['name'],
                'url'      => (string) $item['url'],
            );
        }

        if (empty($elements)) {
            return array();
        }

        $location = !empty($settings['location']) ? (string) $settings['location'] : '';
        $list = array(
            '@type'           => 'ItemList',
            'name'            => $location !== ''
                ? sprintf('دسته‌های اصلی نیازمندی در %s', $location)
                : 'دسته‌های اصلی نیازمندی',
            'numberOfItems'   => count($elements),
            'itemListElement' => $elements,
        );

        if (!empty($ids['categories'])) {
            $list['@id'] = (string) $ids['categories'];
        }

        return $list;
    }
}

if (!function_exists('bornado_schema_manager_extend_geo_guide_graph')) {
    /**
     * Append geo-guide topic, place, breadcrumb, HowTo, FAQ and category list.
     *
     * @param array<int|string,mixed> $data
     * @param array<string,mixed>     $route_context
     * @param string                  $page_type
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_extend_geo_guide_graph(array $data, array $route_context, $page_type)
    {
        unset($route_context, $page_type);

        $settings = bornado_schema_manager_get_geo_guide_settings();
        $canonical_url = bornado_schema_manager_get_geo_guide_canonical_url($settings);
        $ids = bornado_schema_manager_get_geo_guide_graph_ids($canonical_url);

        $place = bornado_schema_manager_build_geo_guide_place_entity($settings, $ids);
        if (!empty($place)) {
            $data['bornado_geo_place'] = $place;
        }

        $community = bornado_schema_manager_build_geo_guide_community_entity($settings, $ids, $canonical_url);
        if (!empty($community)) {
            $data['bornado_geo_community'] = $community;
        }

        $breadcrumb = bornado_schema_manager_build_geo_guide_breadcrumb_entity($settings, $ids, $canonical_url);
        if (!empty($breadcrumb)) {
            $data['bornado_geo_breadcrumb'] = $breadcrumb;
        }

        $howto = bornado_schema_manager_build_geo_guide_howto_entity($settings, $ids, $canonical_url);
        if (!empty($howto)) {
            $data['bornado_geo_howto'] = $howto;
        }

        $faq = bornado_schema_manager_build_geo_guide_faq_entity($settings, $ids, $canonical_url);
        if (!empty($faq)) {
            $data['bornado_geo_faq'] = $faq;
        }

        $categories = bornado_schema_manager_build_geo_guide_category_list_entity($settings, $ids);
        if (!empty($categories)) {
            $data['bornado_geo_categories'] = $categories;
        }

        return $data;
    }
}
