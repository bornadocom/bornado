<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_route_context')) {
    /**
     * Resolve the current SEO route context when available.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_get_route_context()
    {
        if (!function_exists('bornado_seo_routing_get_context')) {
            return array();
        }

        $route_context = bornado_seo_routing_get_context();

        return is_array($route_context) ? $route_context : array();
    }
}

if (!function_exists('bornado_schema_manager_get_page_type')) {
    /**
     * Classify the active request into a schema-management bucket.
     *
     * @return string
     */
    function bornado_schema_manager_get_page_type()
    {
        $route_context  = bornado_schema_manager_get_route_context();
        $category_context = function_exists('bornado_schema_manager_get_category_context')
            ? bornado_schema_manager_get_category_context($route_context)
            : array();
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

        if ($route_mode === 'category_only') {
            return 'category_root_collection';
        }

        if ($route_mode === 'country_only') {
            return 'country_collection';
        }

        if ($route_mode === 'country_city') {
            return 'city_collection';
        }

        if ($route_mode === 'country_category') {
            return 'category_country_collection';
        }

        if ($route_mode === 'country_city_category') {
            return 'category_country_city_collection';
        }

        if (!empty($category_context['shape']) && is_string($category_context['shape'])) {
            return $category_context['shape'];
        }

        if (is_front_page() && function_exists('bornado_is_ad_search_view') && bornado_is_ad_search_view()) {
            return 'home_collection';
        }

        if (function_exists('bornado_is_ad_search_view') && bornado_is_ad_search_view()) {
            if ($country_term instanceof WP_Term && !($city_term instanceof WP_Term) && empty($category_terms)) {
                return 'country_collection';
            }

            if ($country_term instanceof WP_Term && $city_term instanceof WP_Term && empty($category_terms)) {
                return 'city_collection';
            }
        }

        if (is_singular('ad_post')) {
            return 'single_ad';
        }

        return 'generic';
    }
}

if (!function_exists('bornado_schema_manager_build_content_location')) {
    /**
     * Build a location object from the semantic route context.
     *
     * @param array<string,mixed> $route_context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_content_location(array $route_context)
    {
        $country_term = !empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term
            ? $route_context['country_term']
            : null;
        $city_term    = !empty($route_context['city_term']) && $route_context['city_term'] instanceof WP_Term
            ? $route_context['city_term']
            : null;

        if (!($country_term instanceof WP_Term)) {
            return array();
        }

        $content_location = array(
            '@type' => 'Place',
            'name'  => $country_term->name,
        );

        if ($city_term instanceof WP_Term) {
            $country_data = function_exists('bornado_get_country_data')
                ? (array) bornado_get_country_data($country_term)
                : array();
            $country_code = !empty($country_data['country_code']) ? (string) $country_data['country_code'] : '';
            $address_country = array(
                '@type' => 'Country',
                'name'  => $country_term->name,
            );

            if ($country_code !== '') {
                $address_country['alternateName'] = $country_code;
                $address_country['identifier'] = $country_code;
            }

            $content_location['name'] = $city_term->name . ', ' . $country_term->name;
            $content_location['address'] = array(
                '@type'           => 'PostalAddress',
                'addressLocality' => $city_term->name,
                'addressCountry'  => $address_country,
            );
        }

        return $content_location;
    }
}

if (!function_exists('bornado_schema_manager_build_about_entities')) {
    /**
     * Build semantic "about" entities for collection pages.
     *
     * @param string $page_type
     * @param array<string,mixed> $route_context
     * @return array<int,array<string,mixed>>
     */
    function bornado_schema_manager_build_about_entities($page_type, array $route_context)
    {
        $category_context = function_exists('bornado_schema_manager_get_category_context')
            ? bornado_schema_manager_get_category_context($route_context)
            : array();
        $entities     = array();
        $country_term = !empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term
            ? $route_context['country_term']
            : null;
        $city_term    = !empty($route_context['city_term']) && $route_context['city_term'] instanceof WP_Term
            ? $route_context['city_term']
            : null;
        $deepest_term = !empty($route_context['deepest_term']) && $route_context['deepest_term'] instanceof WP_Term
            ? $route_context['deepest_term']
            : null;
        if (!$deepest_term instanceof WP_Term && !empty($category_context['deepest_term']) && $category_context['deepest_term'] instanceof WP_Term) {
            $deepest_term = $category_context['deepest_term'];
        }

        if ($country_term instanceof WP_Term) {
            $entities[] = array(
                '@type' => 'Place',
                'name'  => $country_term->name,
            );
        }

        if ($city_term instanceof WP_Term) {
            $entities[] = array(
                '@type' => 'Place',
                'name'  => $city_term->name,
            );
        }

        if ($deepest_term instanceof WP_Term) {
            $entities[] = array(
                '@type' => 'Thing',
                'name'  => $deepest_term->name,
            );
        }

        if (empty($entities) && $page_type === 'home_collection') {
            $headline = bornado_schema_manager_get_collection_headline();
            if ($headline !== '') {
                $entities[] = array(
                    '@type' => 'Thing',
                    'name'  => $headline,
                );
            }
        }

        return $entities;
    }
}

if (!function_exists('bornado_schema_manager_find_primary_page_entity_key')) {
    /**
     * Find the main page-like node inside a Rank Math graph.
     *
     * @param array<int|string,mixed> $data
     * @return int|string|null
     */
    function bornado_schema_manager_find_primary_page_entity_key(array $data)
    {
        foreach ($data as $key => $entity) {
            if (bornado_schema_entity_has_type($entity, array('WebPage', 'CollectionPage', 'SearchResultsPage'))) {
                return $key;
            }
        }

        return null;
    }
}
