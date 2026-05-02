<?php
// Override the functions and classes of parent theme here.

if (!function_exists('bornado_pick_fallback_image_for_ad_category')) {
    /**
     * Pick one random fallback attachment ID based on ad category.
     */
    function bornado_pick_fallback_image_for_ad_category($post_id)
    {
        $category_image_map = array(
            341 => array(2693, 2694, 2695), // استخدام و کاریابی
            340 => array(2699, 2700, 2701), // املاک
            342 => array(2702, 2703, 2704), // خدمات
            345 => array(2696, 2697, 2698), // کالا و لوازم
            349 => array(2886,2887),             // رویدادها
            337 => array(2705, 2708),       // وسایل نقلیه
            344 => array(2882, 2883), // کسب و کارها
            346 => array(2884, 2885), // اجتماعی
        );

        /**
         * Let future customizations extend/replace category image mapping.
         * Format: [term_id => [attachment_id, ...], ...]
         */
        $category_image_map = apply_filters('bornado_ad_category_fallback_images', $category_image_map, $post_id);
        if (empty($category_image_map) || !is_array($category_image_map)) {
            return 0;
        }

        $terms = wp_get_post_terms((int) $post_id, 'ad_cats');
        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        // Try deeper terms first, then fall back to their ancestors.
        usort($terms, function ($a, $b) {
            $depth_a = count(get_ancestors((int) $a->term_id, 'ad_cats'));
            $depth_b = count(get_ancestors((int) $b->term_id, 'ad_cats'));
            return $depth_b <=> $depth_a;
        });

        foreach ($terms as $term) {
            $term_chain = array_merge(array((int) $term->term_id), array_map('intval', get_ancestors((int) $term->term_id, 'ad_cats')));

            foreach ($term_chain as $term_id) {
                if (empty($category_image_map[$term_id]) || !is_array($category_image_map[$term_id])) {
                    continue;
                }

                $candidate_ids = array_values(array_filter(array_map('intval', $category_image_map[$term_id])));
                if (empty($candidate_ids)) {
                    continue;
                }

                $random_attachment_id = $candidate_ids[array_rand($candidate_ids)];
                if ($random_attachment_id > 0 && wp_attachment_is_image($random_attachment_id)) {
                    return $random_attachment_id;
                }
            }
        }

        return 0;
    }
}

if (!function_exists('adforest_get_ad_images')) {
    /**
     * Child-theme override:
     * - Keep original behavior for real ad images.
     * - If no image exists, inject one random category fallback attachment.
     */
    function adforest_get_ad_images($pid)
    {
        $re_order = get_post_meta($pid, '_sb_photo_arrangement_', true);
        if ($re_order !== '') {
            return explode(',', $re_order);
        }

        $attached_media = get_attached_media('', $pid);
        if (!empty($attached_media)) {
            return $attached_media;
        }

        $fallback_attachment_id = bornado_pick_fallback_image_for_ad_category($pid);
        if ($fallback_attachment_id > 0) {
            return array($fallback_attachment_id);
        }

        // Returning an empty list keeps AdForest's default no-image fallback behavior.
        return array();
    }
}

/**
 * Load Search Core compatibility shims before the parent theme defines pluggable helpers.
 */
$bornado_search_compat_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-search-compat.php';
if (file_exists($bornado_search_compat_bootstrap)) {
    require_once $bornado_search_compat_bootstrap;
}

/**
 * Load semantic breadcrumb override before the parent theme defines its pluggable function.
 */
$bornado_breadcrumb_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-breadcrumbs.php';
if (file_exists($bornado_breadcrumb_bootstrap)) {
    require_once $bornado_breadcrumb_bootstrap;
}

/**
 * Load custom header clone integration from child theme directory.
 */
$bornado_header_clone_bootstrap = trailingslashit(get_stylesheet_directory()) . 'adforest-header-search-4-clone/adforest-header-search-4-clone.php';
if (file_exists($bornado_header_clone_bootstrap)) {
    require_once $bornado_header_clone_bootstrap;
}

/**
 * Keep the Ad Search page contained without overriding parent templates.
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_page_template('page-search.php')) {
        return;
    }

    $style_handle = 'adforest-main-responsive';

    $inline_css = '
    @media (min-width: 1200px) {
        body.page-template-page-search-php .adt-breadcrumb > .container,
        body.page-template-page-search-php .adt-top-tabs-header > .container,
        body.page-template-page-search-php .adt-ads-with-filters > .container,
        body.page-template-page-search-php .adt-ads-topbar-section > .container,
        body.page-template-page-search-php .adt-recommended-ads-section > .container {
            max-width: 1200px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
    }';

    wp_add_inline_style($style_handle, $inline_css);
}, 99);

/**
 * Load the same price slider dependency outside the default Ad Search page.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    wp_enqueue_style('rangeslider-css');
    wp_enqueue_script('rangeslider-min');
}, 100);

if (!function_exists('bornado_frontend_language_tag')) {
    /**
     * Keep frontend language markup explicit for SEO and accessibility.
     *
     * WordPress locale can stay on fa_IR for translations while frontend pages
     * emit a short BCP 47 language tag that matches the page content language.
     */
    function bornado_frontend_language_tag()
    {
        return apply_filters('bornado_frontend_language_tag', 'fa');
    }
}

if (!function_exists('bornado_schema_entity_has_type')) {
    /**
     * Check whether a schema graph node contains one of the expected types.
     *
     * @param mixed        $entity
     * @param array<int,string> $expected_types
     * @return bool
     */
    function bornado_schema_entity_has_type($entity, array $expected_types)
    {
        if (!is_array($entity) || empty($entity['@type'])) {
            return false;
        }

        $types = is_array($entity['@type']) ? $entity['@type'] : array($entity['@type']);
        foreach ($types as $type) {
            if (in_array($type, $expected_types, true)) {
                return true;
            }
        }

        return false;
    }
}

add_filter('language_attributes', function ($output, $doctype) {
    if (is_admin()) {
        return $output;
    }

    $language_tag = bornado_frontend_language_tag();
    if (!is_string($language_tag) || $language_tag === '') {
        return $output;
    }

    $attributes = 'dir="' . esc_attr(is_rtl() ? 'rtl' : 'ltr') . '" lang="' . esc_attr($language_tag) . '"';
    if ($doctype === 'xhtml') {
        $attributes .= ' xml:lang="' . esc_attr($language_tag) . '"';
    }

    return $attributes;
}, 20, 2);

add_filter('rank_math/json_ld', function ($data, $json_ld) {
    if (is_admin() || !is_array($data)) {
        return $data;
    }

    $language_tag = bornado_frontend_language_tag();
    $route_context = function_exists('bornado_seo_routing_get_context') ? bornado_seo_routing_get_context() : array();
    $country_term = !empty($route_context['country_term']) && $route_context['country_term'] instanceof WP_Term
        ? $route_context['country_term']
        : null;
    $city_term = !empty($route_context['city_term']) && $route_context['city_term'] instanceof WP_Term
        ? $route_context['city_term']
        : null;
    $country_data = $country_term instanceof WP_Term && function_exists('bornado_get_country_data')
        ? bornado_get_country_data($country_term)
        : array();

    foreach ($data as $key => $entity) {
        if (!is_array($entity)) {
            continue;
        }

        if (
            bornado_schema_entity_has_type($entity, array('WebSite', 'WebPage', 'CollectionPage', 'Article', 'BlogPosting', 'ItemPage'))
            && empty($entity['inLanguage'])
        ) {
            $data[$key]['inLanguage'] = $language_tag;
        }

        if (
            $country_term instanceof WP_Term
            && bornado_schema_entity_has_type($entity, array('WebPage', 'CollectionPage', 'Article', 'BlogPosting', 'ItemPage'))
            && empty($entity['contentLocation'])
        ) {
            $content_location = array(
                '@type' => 'Place',
                'name'  => $country_term->name,
            );

            if ($city_term instanceof WP_Term) {
                $content_location['name'] = $city_term->name . ', ' . $country_term->name;
                $content_location['address'] = array(
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $city_term->name,
                    'addressCountry'  => !empty($country_data['country_code']) ? $country_data['country_code'] : $country_term->name,
                );
            }

            $data[$key]['contentLocation'] = $content_location;
        }
    }

    return $data;
}, 20, 2);

if (!function_exists('bornado_is_public_seo_request')) {
    /**
     * Limit SEO surface changes to real frontend HTML requests.
     *
     * @return bool
     */
    function bornado_is_public_seo_request()
    {
        return !is_admin()
            && !wp_doing_ajax()
            && !wp_doing_cron()
            && !(defined('REST_REQUEST') && REST_REQUEST)
            && !wp_is_json_request();
    }
}

if (!function_exists('bornado_frontend_locale')) {
    /**
     * Canonical frontend locale for a Persian-first single-site install.
     *
     * @return string
     */
    function bornado_frontend_locale()
    {
        return apply_filters('bornado_frontend_locale', 'fa_IR');
    }
}

add_filter('locale', function ($locale) {
    if (!bornado_is_public_seo_request()) {
        return $locale;
    }

    if (is_string($locale) && preg_match('/^fa(?:_|$)/i', $locale)) {
        return $locale;
    }

    return bornado_frontend_locale();
}, 20);

add_filter('wp_robots', function ($robots) {
    if (!bornado_is_public_seo_request()) {
        return $robots;
    }

    if (!is_array($robots)) {
        $robots = array();
    }

    if (empty($robots['max-image-preview'])) {
        $robots['max-image-preview'] = 'large';
    }

    if (!isset($robots['max-snippet'])) {
        $robots['max-snippet'] = -1;
    }

    if (!isset($robots['max-video-preview'])) {
        $robots['max-video-preview'] = -1;
    }

    return $robots;
}, 20);

if (!function_exists('bornado_has_external_hreflang_provider')) {
    /**
     * Detect multilingual plugins that may already own hreflang output.
     *
     * @return bool
     */
    function bornado_has_external_hreflang_provider()
    {
        return defined('ICL_SITEPRESS_VERSION')
            || function_exists('pll_the_languages')
            || function_exists('trp_custom_language_switcher');
    }
}

if (!function_exists('bornado_get_current_canonical_url_for_hreflang')) {
    /**
     * Best-effort canonical URL resolver for hreflang output.
     *
     * @return string
     */
    function bornado_get_current_canonical_url_for_hreflang()
    {
        if (function_exists('bornado_seo_routing_get_context')) {
            $route_context = bornado_seo_routing_get_context();
            if (!empty($route_context['canonical_url']) && is_string($route_context['canonical_url'])) {
                return $route_context['canonical_url'];
            }
        }

        if (is_front_page()) {
            return home_url('/');
        }

        if (is_home()) {
            $posts_page_id = (int) get_option('page_for_posts');
            return $posts_page_id > 0 ? (string) get_permalink($posts_page_id) : home_url('/');
        }

        if (is_singular()) {
            return (string) get_permalink();
        }

        if (is_post_type_archive()) {
            $post_type = get_query_var('post_type');
            $post_type = is_array($post_type) ? reset($post_type) : $post_type;
            return is_string($post_type) ? (string) get_post_type_archive_link($post_type) : '';
        }

        if (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $term_link = get_term_link($term);
                return is_wp_error($term_link) ? '' : (string) $term_link;
            }
        }

        return '';
    }
}

if (!function_exists('bornado_print_hreflang_links')) {
    /**
     * Output conservative hreflang signals for the current single-language site.
     *
     * We emit only self-referencing `fa` plus `x-default` on the global home page.
     * We intentionally do not emit country-specific hreflang variants because
     * country routes are distinct pages, not alternate translations of one page.
     *
     * @return void
     */
    function bornado_print_hreflang_links()
    {
        if (!bornado_is_public_seo_request() || bornado_has_external_hreflang_provider()) {
            return;
        }

        $canonical_url = bornado_get_current_canonical_url_for_hreflang();
        if ($canonical_url === '') {
            return;
        }

        printf(
            "<link rel=\"alternate\" hreflang=\"fa\" href=\"%s\" />\n",
            esc_url($canonical_url)
        );

        if (untrailingslashit($canonical_url) === untrailingslashit(home_url('/'))) {
            printf(
                "<link rel=\"alternate\" hreflang=\"x-default\" href=\"%s\" />\n",
                esc_url(home_url('/'))
            );
        }
    }
}
add_action('wp_head', 'bornado_print_hreflang_links', 6);