<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_category_vertical_configs')) {
    /**
     * Registry for category vertical-specific schema enrichments.
     *
     * Each vertical file can extend this via the filter so the core schema layer
     * stays clean while vertical-specific semantics remain discoverable.
     *
     * @return array<string,array<string,mixed>>
     */
    function bornado_schema_manager_get_category_vertical_configs()
    {
        return (array) apply_filters('bornado_schema_manager_category_vertical_configs', array());
    }
}

if (!function_exists('bornado_schema_manager_get_category_root_term')) {
    /**
     * Resolve the top-level category term for a given ad_cats term.
     *
     * @param WP_Term|null $term
     * @return WP_Term|null
     */
    function bornado_schema_manager_get_category_root_term($term)
    {
        if (!($term instanceof WP_Term)) {
            return null;
        }

        $ancestor_ids = array_reverse(array_map('intval', get_ancestors((int) $term->term_id, 'ad_cats', 'taxonomy')));
        if (empty($ancestor_ids)) {
            return $term;
        }

        $root_term = get_term((int) $ancestor_ids[0], 'ad_cats');

        return $root_term instanceof WP_Term ? $root_term : $term;
    }
}

if (!function_exists('bornado_schema_manager_get_category_context')) {
    /**
     * Resolve current category-page context across native taxonomy pages and
     * semantic routes such as /country/category/ and /country/city/category/.
     *
     * @param array<string,mixed>|null $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_get_category_context($route_context = null)
    {
        $route_context  = is_array($route_context) ? $route_context : bornado_schema_manager_get_route_context();
        $route_mode     = !empty($route_context['route_mode']) && is_string($route_context['route_mode'])
            ? (string) $route_context['route_mode']
            : '';
        $country_term   = !empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term
            ? $route_context['country_term']
            : null;
        $city_term      = !empty($route_context['city_term']) && $route_context['city_term'] instanceof WP_Term
            ? $route_context['city_term']
            : null;
        $category_terms = !empty($route_context['category_terms']) && is_array($route_context['category_terms'])
            ? array_values(array_filter($route_context['category_terms'], static function ($term) {
                return $term instanceof WP_Term;
            }))
            : array();
        $deepest_term   = !empty($route_context['deepest_term']) && $route_context['deepest_term'] instanceof WP_Term
            ? $route_context['deepest_term']
            : null;

        if (!$deepest_term instanceof WP_Term && !empty($category_terms)) {
            $candidate = end($category_terms);
            $deepest_term = $candidate instanceof WP_Term ? $candidate : null;
            reset($category_terms);
        }

        if (!$deepest_term instanceof WP_Term && is_tax('ad_cats')) {
            $queried_term = get_queried_object();
            if ($queried_term instanceof WP_Term && $queried_term->taxonomy === 'ad_cats') {
                $deepest_term = $queried_term;
                $chain_ids = array_reverse(array_map('intval', get_ancestors((int) $queried_term->term_id, 'ad_cats', 'taxonomy')));
                $chain_ids[] = (int) $queried_term->term_id;
                $category_terms = array_values(array_filter(array_map(static function ($term_id) {
                    $term = get_term((int) $term_id, 'ad_cats');
                    return $term instanceof WP_Term ? $term : null;
                }, $chain_ids)));
            }
        }

        if (!$deepest_term instanceof WP_Term && $route_mode === 'category_only' && is_tax('ad_cats')) {
            $queried_term = get_queried_object();
            if ($queried_term instanceof WP_Term && $queried_term->taxonomy === 'ad_cats') {
                $deepest_term = $queried_term;
            }
        }

        if (!$deepest_term instanceof WP_Term) {
            return array();
        }

        if (empty($category_terms)) {
            $category_terms = array($deepest_term);
        }

        $root_term = bornado_schema_manager_get_category_root_term($deepest_term);
        $vertical_configs = bornado_schema_manager_get_category_vertical_configs();
        $vertical_key = '';
        $vertical_config = array();

        if ($root_term instanceof WP_Term) {
            $root_id = (int) $root_term->term_id;
            foreach ($vertical_configs as $candidate_key => $config) {
                $term_ids = isset($config['term_ids']) && is_array($config['term_ids']) ? array_map('intval', $config['term_ids']) : array();
                $slugs    = isset($config['slugs']) && is_array($config['slugs']) ? array_map('sanitize_title', $config['slugs']) : array();

                if (in_array($root_id, $term_ids, true) || in_array(sanitize_title($root_term->slug), $slugs, true)) {
                    $vertical_key = (string) $candidate_key;
                    $vertical_config = is_array($config) ? $config : array();
                    break;
                }
            }
        }

        $shape = 'category_root_collection';
        if ($route_mode === 'country_city_category') {
            $shape = 'category_country_city_collection';
        } elseif ($route_mode === 'country_category') {
            $shape = 'category_country_collection';
        } elseif ($route_mode === 'category_only') {
            $shape = 'category_root_collection';
        } elseif ($country_term instanceof WP_Term && $city_term instanceof WP_Term) {
            $shape = 'category_country_city_collection';
        } elseif ($country_term instanceof WP_Term) {
            $shape = 'category_country_collection';
        }

        return array(
            'shape'           => $shape,
            'route_mode'      => $route_mode,
            'country_term'    => $country_term,
            'city_term'       => $city_term,
            'category_terms'  => $category_terms,
            'deepest_term'    => $deepest_term,
            'root_term'       => $root_term,
            'vertical_key'    => $vertical_key,
            'vertical_config' => $vertical_config,
        );
    }
}

if (!function_exists('bornado_schema_manager_is_category_shape')) {
    /**
     * Whether the given page type is one of the category matrix shapes.
     *
     * @param string $page_type
     * @return bool
     */
    function bornado_schema_manager_is_category_shape($page_type)
    {
        return in_array(
            (string) $page_type,
            array(
                'category_root_collection',
                'category_country_collection',
                'category_country_city_collection',
            ),
            true
        );
    }
}

if (!function_exists('bornado_schema_manager_apply_category_vertical_enrichment')) {
    /**
     * Apply vertical-specific metadata to category collection pages.
     *
     * @param array<string,mixed> $entity
     * @param array<string,mixed> $category_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_apply_category_vertical_enrichment(array $entity, array $category_context)
    {
        $vertical_key    = isset($category_context['vertical_key']) ? (string) $category_context['vertical_key'] : '';
        $vertical_config = !empty($category_context['vertical_config']) && is_array($category_context['vertical_config'])
            ? $category_context['vertical_config']
            : array();

        if ($vertical_key === '' || empty($vertical_config)) {
            return $entity;
        }

        $home_url = untrailingslashit(home_url('/'));
        $label_fa = isset($vertical_config['label_fa']) ? trim((string) $vertical_config['label_fa']) : '';
        $label_en = isset($vertical_config['label_en']) ? trim((string) $vertical_config['label_en']) : '';
        $keywords = isset($vertical_config['keywords']) && is_array($vertical_config['keywords'])
            ? array_values(array_filter(array_map(static function ($keyword) {
                return is_string($keyword) ? trim($keyword) : '';
            }, $vertical_config['keywords'])))
            : array();
        $about_terms = isset($vertical_config['about_terms']) && is_array($vertical_config['about_terms'])
            ? array_values($vertical_config['about_terms'])
            : array();

        $vertical_term = array(
            '@type'            => 'DefinedTerm',
            '@id'              => $home_url . '/#vertical-' . $vertical_key,
            'identifier'       => 'bornado:vertical:' . $vertical_key,
            'termCode'         => $vertical_key,
            'inDefinedTermSet' => array('@id' => $home_url . '/#vertical-taxonomy'),
        );

        if ($label_fa !== '') {
            $vertical_term['name'] = $label_fa;
        }

        if ($label_en !== '') {
            $vertical_term['alternateName'] = $label_en;
        }

        if (!empty($keywords)) {
            $vertical_term['description'] = implode(' | ', $keywords);
        }

        if (empty($entity['about']) || !is_array($entity['about'])) {
            $entity['about'] = array();
        }

        $entity['about'][] = $vertical_term;

        $existing_about_names = array();
        foreach ($entity['about'] as $about_entity) {
            if (!is_array($about_entity) || empty($about_entity['name']) || !is_string($about_entity['name'])) {
                continue;
            }

            $existing_about_names[sanitize_title((string) $about_entity['name'])] = true;
        }

        foreach ($about_terms as $about_term) {
            $about_name = '';
            $about_alternate_name = '';

            if (is_string($about_term)) {
                $about_name = trim($about_term);
            } elseif (is_array($about_term)) {
                $about_name = !empty($about_term['name']) && is_string($about_term['name'])
                    ? trim((string) $about_term['name'])
                    : '';
                $about_alternate_name = !empty($about_term['alternateName']) && is_string($about_term['alternateName'])
                    ? trim((string) $about_term['alternateName'])
                    : '';
            }

            if ($about_name === '') {
                continue;
            }

            $about_slug = sanitize_title($about_name);
            if ($about_slug !== '' && isset($existing_about_names[$about_slug])) {
                continue;
            }

            $about_entity = array(
                '@type' => 'Thing',
                'name'  => $about_name,
            );

            if ($about_alternate_name !== '') {
                $about_entity['alternateName'] = $about_alternate_name;
            }

            $entity['about'][] = $about_entity;

            if ($about_slug !== '') {
                $existing_about_names[$about_slug] = true;
            }
        }

        if (!empty($keywords)) {
            $entity['keywords'] = implode(', ', array_values(array_unique($keywords)));
        }

        if ($label_fa !== '') {
            $entity['genre'] = $label_fa;
        }

        return $entity;
    }
}

if (!function_exists('bornado_schema_manager_get_category_display_name')) {
    /**
     * Best human-facing category label for collection copy.
     *
     * @param array<string,mixed> $category_context
     * @return string
     */
    function bornado_schema_manager_get_category_display_name(array $category_context)
    {
        $deepest_term = !empty($category_context['deepest_term']) && $category_context['deepest_term'] instanceof WP_Term
            ? $category_context['deepest_term']
            : null;
        if ($deepest_term instanceof WP_Term) {
            return $deepest_term->name;
        }

        $root_term = !empty($category_context['root_term']) && $category_context['root_term'] instanceof WP_Term
            ? $category_context['root_term']
            : null;
        if ($root_term instanceof WP_Term) {
            return $root_term->name;
        }

        $vertical_config = !empty($category_context['vertical_config']) && is_array($category_context['vertical_config'])
            ? $category_context['vertical_config']
            : array();

        return isset($vertical_config['label_fa']) ? trim((string) $vertical_config['label_fa']) : '';
    }
}

if (!function_exists('bornado_schema_manager_cleanup_category_collection_entity')) {
    /**
     * Remove inherited page properties that are noisy or misleading for dynamic
     * category listing pages.
     *
     * @param array<string,mixed> $entity
     * @return array<string,mixed>
     */
    function bornado_schema_manager_cleanup_category_collection_entity(array $entity)
    {
        $remove_keys = array(
            'datePublished',
            'dateModified',
            'primaryImageOfPage',
            'thumbnailUrl',
            'image',
            'author',
            'mainEntityOfPage',
        );

        foreach ($remove_keys as $key) {
            if (array_key_exists($key, $entity)) {
                unset($entity[$key]);
            }
        }

        return $entity;
    }
}

if (!function_exists('bornado_schema_manager_build_category_collection_copy')) {
    /**
     * Build shape-aware SEO copy for category listing pages.
     *
     * @param array<string,mixed> $category_context
     * @return array<string,string>
     */
    function bornado_schema_manager_build_category_collection_copy(array $category_context)
    {
        $shape        = isset($category_context['shape']) ? (string) $category_context['shape'] : 'category_root_collection';
        $category_name = bornado_schema_manager_get_category_display_name($category_context);
        $country_term  = !empty($category_context['country_term']) && $category_context['country_term'] instanceof WP_Term
            ? $category_context['country_term']
            : null;
        $city_term     = !empty($category_context['city_term']) && $category_context['city_term'] instanceof WP_Term
            ? $category_context['city_term']
            : null;
        $site_name     = bornado_schema_manager_get_site_name();

        if ($category_name === '') {
            return array();
        }

        $headline = '';
        $description = '';

        if ($shape === 'category_country_city_collection' && $country_term instanceof WP_Term && $city_term instanceof WP_Term) {
            $headline = sprintf('آگهی رایگان %s ایرانیان %s', $category_name, $city_term->name);
            $description = sprintf(
                'مرجع درج آگهی رایگان %s برای ایرانیان %s، %s؛ مشاهده و درج آگهی‌های مرتبط با %s در برنادو.',
                $category_name,
                $city_term->name,
                $country_term->name,
                $category_name
            );
        } elseif ($shape === 'category_country_collection' && $country_term instanceof WP_Term) {
            $headline = sprintf('آگهی رایگان %s ایرانیان %s', $category_name, $country_term->name);
            $description = sprintf(
                'مرجع درج آگهی رایگان %s برای ایرانیان %s؛ مشاهده و درج آگهی‌های مرتبط با %s در برنادو.',
                $category_name,
                $country_term->name,
                $category_name
            );
        } else {
            $headline = sprintf('آگهی رایگان %s ایرانیان خارج کشور', $category_name);
            $description = sprintf(
                'مرجع درج آگهی رایگان %s برای ایرانیان خارج کشور؛ مشاهده و درج آگهی‌های مرتبط با %s در برنادو.',
                $category_name,
                $category_name
            );
        }

        return array(
            'name'        => $site_name !== '' ? $headline . ' - ' . $site_name : $headline,
            'headline'    => $headline,
            'description' => $description,
        );
    }
}

if (!function_exists('bornado_schema_manager_finalize_category_collection_entity')) {
    /**
     * Apply category-shape polish so all category listing pages behave consistently.
     *
     * @param array<string,mixed> $entity
     * @param array<string,mixed> $category_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_finalize_category_collection_entity(array $entity, array $category_context)
    {
        $entity = bornado_schema_manager_cleanup_category_collection_entity($entity);
        $copy   = bornado_schema_manager_build_category_collection_copy($category_context);

        if (!empty($copy['name'])) {
            $entity['name'] = $copy['name'];
        }

        if (!empty($copy['headline'])) {
            $entity['headline'] = $copy['headline'];
        }

        if (!empty($copy['description'])) {
            $entity['description'] = $copy['description'];
        }

        return bornado_schema_manager_apply_category_vertical_enrichment($entity, $category_context);
    }
}

if (!function_exists('bornado_schema_manager_build_category_item_list_name')) {
    /**
     * Human-readable label for ItemList nodes attached to category collections.
     *
     * @param array<string,mixed> $category_context
     * @return string
     */
    function bornado_schema_manager_build_category_item_list_name(array $category_context)
    {
        $copy = bornado_schema_manager_build_category_collection_copy($category_context);
        if (!empty($copy['headline'])) {
            return (string) $copy['headline'];
        }

        return bornado_schema_manager_get_category_display_name($category_context);
    }
}
