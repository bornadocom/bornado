<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Route-aware H1, document title, and meta description for Ad Search listing pages.
 *
 * Listing URLs share the same WordPress page (Ad Search), so Rank Math would
 * otherwise repeat the homepage title. This module is the single source of
 * truth for collection copy used by the visible H1, Rank Math, and schema.
 */

if (!function_exists('bornado_listing_seo_normalize_text')) {
    /**
     * Collapse whitespace and strip tags from SEO copy.
     *
     * @param string $text
     * @return string
     */
    function bornado_listing_seo_normalize_text($text)
    {
        $text = wp_specialchars_decode(wp_strip_all_tags((string) $text), ENT_QUOTES);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}

if (!function_exists('bornado_listing_seo_term_name')) {
    /**
     * @param mixed $term
     * @return string
     */
    function bornado_listing_seo_term_name($term)
    {
        if (!($term instanceof WP_Term)) {
            return '';
        }

        return bornado_listing_seo_normalize_text($term->name);
    }
}

if (!function_exists('bornado_listing_seo_site_name')) {
    /**
     * @return string
     */
    function bornado_listing_seo_site_name()
    {
        $site_name = bornado_listing_seo_normalize_text((string) get_bloginfo('name'));

        return $site_name !== '' ? $site_name : 'Bornado';
    }
}

if (!function_exists('bornado_listing_seo_with_brand')) {
    /**
     * Append the site name once, using a pipe separator.
     *
     * @param string $title
     * @return string
     */
    function bornado_listing_seo_with_brand($title)
    {
        $title     = bornado_listing_seo_normalize_text($title);
        $site_name = bornado_listing_seo_site_name();

        if ($title === '') {
            return $site_name;
        }

        if ($site_name !== '' && function_exists('mb_stripos') && mb_stripos($title, $site_name) !== false) {
            return $title;
        }

        return $title . ' | ' . $site_name;
    }
}

if (!function_exists('bornado_listing_seo_should_apply')) {
    /**
     * True on Ad Search listing views that are not editorial landings or guides.
     *
     * @return bool
     */
    function bornado_listing_seo_should_apply()
    {
        static $cached = null;
        if (is_bool($cached)) {
            return $cached;
        }

        if (is_admin() || wp_doing_ajax() || is_feed() || is_robots() || is_trackback()) {
            return false;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        if (is_singular('ad_post')) {
            return false;
        }

        if (function_exists('bornado_geo_guide_is_template') && bornado_geo_guide_is_template()) {
            return false;
        }

        $is_search_view = function_exists('bornado_is_ad_search_view') && bornado_is_ad_search_view();

        if (!$is_search_view && function_exists('bornado_seo_routing_get_context')) {
            $route = bornado_seo_routing_get_context();
            $is_search_view = !empty($route['is_valid']);
        }

        if (!$is_search_view && function_exists('bornado_get_search_page_id')) {
            $search_page_id = bornado_get_search_page_id();
            $queried_id     = (int) get_queried_object_id();
            $front_id       = (int) get_option('page_on_front');

            if ($search_page_id > 0 && ($queried_id === $search_page_id || ($front_id === $search_page_id && is_front_page()))) {
                $is_search_view = true;
            }
        }

        if (!$is_search_view) {
            if (did_action('template_include')) {
                $cached = false;
            }

            return false;
        }

        if (function_exists('bornado_seo_routing_get_context')) {
            $route = bornado_seo_routing_get_context();
            if (!empty($route['landing_post']) && $route['landing_post'] instanceof WP_Post) {
                $cached = false;
                return false;
            }
        }

        $cached = true;

        return true;
    }
}

if (!function_exists('bornado_listing_seo_get_route_terms')) {
    /**
     * @return array{country:string,city:string,category:string,paged:int,route_mode:string}
     */
    function bornado_listing_seo_get_route_terms()
    {
        $route = function_exists('bornado_seo_routing_get_context')
            ? bornado_seo_routing_get_context()
            : array();

        $country = !empty($route['country_term']) ? bornado_listing_seo_term_name($route['country_term']) : '';
        $city    = !empty($route['city_term']) ? bornado_listing_seo_term_name($route['city_term']) : '';
        $category = '';

        if (!empty($route['deepest_term'])) {
            $category = bornado_listing_seo_term_name($route['deepest_term']);
        } elseif (!empty($route['category_terms']) && is_array($route['category_terms'])) {
            $terms = array_reverse(array_values(array_filter($route['category_terms'], static function ($term) {
                return $term instanceof WP_Term;
            })));
            if (!empty($terms[0])) {
                $category = bornado_listing_seo_term_name($terms[0]);
            }
        }

        $paged = !empty($route['paged']) ? max(1, (int) $route['paged']) : 1;
        if ($paged < 2) {
            $paged = max(1, (int) get_query_var('paged'));
        }

        return array(
            'country'    => $country,
            'city'       => $city,
            'category'   => $category,
            'paged'      => $paged,
            'route_mode' => !empty($route['route_mode']) ? (string) $route['route_mode'] : '',
        );
    }
}

if (!function_exists('bornado_listing_seo_detect_page_type')) {
    /**
     * @param array{country:string,city:string,category:string,route_mode:string} $terms
     * @return string
     */
    function bornado_listing_seo_detect_page_type(array $terms)
    {
        $mode_map = array(
            'category_only'           => 'category_root_collection',
            'country_only'            => 'country_collection',
            'country_city'            => 'city_collection',
            'country_category'        => 'category_country_collection',
            'country_city_category'   => 'category_country_city_collection',
        );

        $mode = isset($terms['route_mode']) ? (string) $terms['route_mode'] : '';
        if (isset($mode_map[$mode])) {
            return $mode_map[$mode];
        }

        $has_country  = !empty($terms['country']);
        $has_city     = !empty($terms['city']);
        $has_category = !empty($terms['category']);

        if ($has_category && $has_country && $has_city) {
            return 'category_country_city_collection';
        }
        if ($has_category && $has_country) {
            return 'category_country_collection';
        }
        if ($has_category) {
            return 'category_root_collection';
        }
        if ($has_country && $has_city) {
            return 'city_collection';
        }
        if ($has_country) {
            return 'country_collection';
        }

        return 'home_collection';
    }
}

if (!function_exists('bornado_listing_seo_meta_for_term')) {
    /**
     * Shared listing meta description. Action language stays in meta, not in H1.
     *
     * @param string $term
     * @return string
     */
    function bornado_listing_seo_meta_for_term($term)
    {
        $term = bornado_listing_seo_normalize_text($term);
        if ($term === '') {
            $term = 'ایرانیان خارج از کشور';
        }

        return sprintf(
            'آگهی‌های نیازمندی %s را در این صفحه ببینید، جستجو کنید و فیلتر کنید یا آگهی خود را رایگان و بدون واسطه درج کنید.',
            $term
        );
    }
}

if (!function_exists('bornado_listing_seo_get_copy')) {
    /**
     * @return array{h1:string,title:string,description:string,page_type:string}
     */
    function bornado_listing_seo_get_copy()
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $empty = array(
            'h1'          => '',
            'title'       => '',
            'description' => '',
            'page_type'   => '',
        );

        if (!bornado_listing_seo_should_apply()) {
            if (did_action('template_include')) {
                $cached = $empty;
            }

            return $empty;
        }

        $terms     = bornado_listing_seo_get_route_terms();
        $page_type = bornado_listing_seo_detect_page_type($terms);
        $h1        = '';
        $qualifier = '';
        $meta_term = '';

        switch ($page_type) {
            case 'city_collection':
                $h1        = $terms['city'] !== '' ? sprintf('آگهی‌های %s', $terms['city']) : '';
                $qualifier = 'نیازمندی‌های فعال همین شهر';
                $meta_term = $terms['city'];
                break;

            case 'country_collection':
                $h1        = $terms['country'] !== '' ? sprintf('آگهی‌های %s', $terms['country']) : '';
                $qualifier = 'نیازمندی‌های فعال شهرهای این کشور';
                $meta_term = $terms['country'];
                break;

            case 'category_country_city_collection':
                if ($terms['category'] !== '' && $terms['city'] !== '') {
                    $h1        = sprintf('آگهی‌های %s در %s', $terms['category'], $terms['city']);
                    $qualifier = 'نیازمندی‌های فعال همین شهر';
                    $meta_term = $terms['category'] . ' در ' . $terms['city'];
                }
                break;

            case 'category_country_collection':
                if ($terms['category'] !== '' && $terms['country'] !== '') {
                    $h1        = sprintf('آگهی‌های %s در %s', $terms['category'], $terms['country']);
                    $qualifier = 'نیازمندی‌های فعال شهرهای این کشور';
                    $meta_term = $terms['category'] . ' در ' . $terms['country'];
                }
                break;

            case 'category_root_collection':
                if ($terms['category'] !== '') {
                    $h1        = sprintf('آگهی‌های %s', $terms['category']);
                    $qualifier = 'نیازمندی‌های ایرانیان خارج از کشور';
                    $meta_term = $terms['category'];
                }
                break;

            case 'home_collection':
            default:
                $h1        = 'نیازمندی‌های ایرانیان خارج از کشور';
                $qualifier = '';
                $meta_term = 'ایرانیان خارج از کشور';
                break;
        }

        $h1 = bornado_listing_seo_normalize_text($h1);
        if ($h1 === '') {
            $h1        = 'نیازمندی‌های ایرانیان خارج از کشور';
            $qualifier = '';
            $meta_term = 'ایرانیان خارج از کشور';
            $page_type = 'home_collection';
        }

        $title = $qualifier !== '' ? $h1 . ' | ' . $qualifier : $h1;
        $title = bornado_listing_seo_with_brand($title);

        if ($terms['paged'] > 1) {
            $title .= ' | صفحه ' . number_format_i18n($terms['paged']);
        }

        $copy = array(
            'h1'          => $h1,
            'title'       => $title,
            'description' => bornado_listing_seo_meta_for_term($meta_term),
            'page_type'   => $page_type,
        );

        /**
         * Filter listing SEO copy used by H1, Rank Math, and schema.
         *
         * @param array{h1:string,title:string,description:string,page_type:string} $copy
         * @param array{country:string,city:string,category:string,paged:int,route_mode:string} $terms
         */
        $copy = apply_filters('bornado_listing_seo_copy', $copy, $terms);

        $cached = array(
            'h1'          => bornado_listing_seo_normalize_text(isset($copy['h1']) ? $copy['h1'] : $h1),
            'title'       => bornado_listing_seo_normalize_text(isset($copy['title']) ? $copy['title'] : $title),
            'description' => bornado_listing_seo_normalize_text(isset($copy['description']) ? $copy['description'] : ''),
            'page_type'   => isset($copy['page_type']) ? (string) $copy['page_type'] : $page_type,
        );

        return $cached;
    }
}

if (!function_exists('bornado_listing_seo_filter_rank_math_title')) {
    /**
     * @param string $title
     * @return string
     */
    function bornado_listing_seo_filter_rank_math_title($title)
    {
        $copy = bornado_listing_seo_get_copy();

        return $copy['title'] !== '' ? $copy['title'] : $title;
    }
}

if (!function_exists('bornado_listing_seo_filter_rank_math_description')) {
    /**
     * @param string $description
     * @return string
     */
    function bornado_listing_seo_filter_rank_math_description($description)
    {
        $copy = bornado_listing_seo_get_copy();

        return $copy['description'] !== '' ? $copy['description'] : $description;
    }
}

if (!function_exists('bornado_listing_seo_filter_document_title')) {
    /**
     * @param string $title
     * @return string
     */
    function bornado_listing_seo_filter_document_title($title)
    {
        $copy = bornado_listing_seo_get_copy();

        return $copy['title'] !== '' ? $copy['title'] : $title;
    }
}

add_filter('rank_math/frontend/title', 'bornado_listing_seo_filter_rank_math_title', 20);
add_filter('rank_math/frontend/description', 'bornado_listing_seo_filter_rank_math_description', 20);
add_filter('pre_get_document_title', 'bornado_listing_seo_filter_document_title', 20);
