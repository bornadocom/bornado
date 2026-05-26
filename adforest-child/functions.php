<?php
// Override the functions and classes of parent theme here.

if (!function_exists('bornado_pick_fallback_image_for_ad_category')) {
    /**
     * Pick one random fallback attachment ID based on ad category.
     */
    function bornado_pick_fallback_image_for_ad_category($post_id)
    {
        $category_image_map = array(
            339 => array(2322, 2323), // استخدام و کاریابی
            338 => array(2324, 2325), // املاک
            341 => array(2326, 2327), // خدمات
            342 => array(2320, 2321), // کالا و لوازم
            340 => array(2328, 2329),       // وسایل نقلیه
            343 => array(2318, 2319), // اجتماعی
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
            $ordered_ids = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $re_order)))));
            if (!empty($ordered_ids)) {
                return $ordered_ids;
            }
        }

        $attached_media = get_attached_media('', $pid);
        if (!empty($attached_media)) {
            // Numeric keys so callers like Recent Ads Widget ($media[0]) work.
            return array_values(array_map('intval', array_keys($attached_media)));
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
 * Respect category-template Show/Hide flags in the category search sidebar.
 */
$bornado_category_search_sidebar_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-category-search-sidebar.php';
if (file_exists($bornado_category_search_sidebar_bootstrap)) {
    require_once $bornado_category_search_sidebar_bootstrap;
}

/**
 * Keep ad currency aligned with the selected country/city without touching theme core files.
 */
$bornado_ad_currency_sync_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-currency-sync.php';
if (file_exists($bornado_ad_currency_sync_bootstrap)) {
    require_once $bornado_ad_currency_sync_bootstrap;
}

/**
 * Shared phone-country helpers for ad post, auth modal, and profile UX.
 */
$bornado_phone_support_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-phone-support.php';
if (file_exists($bornado_phone_support_bootstrap)) {
    require_once $bornado_phone_support_bootstrap;
}

/**
 * Keep ad phone numbers aligned with the selected country/city without touching theme core files.
 */
$bornado_ad_phone_sync_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-phone-sync.php';
if (file_exists($bornado_ad_phone_sync_bootstrap)) {
    require_once $bornado_ad_phone_sync_bootstrap;
}

/**
 * Load custom header clone integration from child theme directory.
 */
$bornado_header_clone_bootstrap = trailingslashit(get_stylesheet_directory()) . 'adforest-header-search-4-clone/adforest-header-search-4-clone.php';
if (file_exists($bornado_header_clone_bootstrap)) {
    require_once $bornado_header_clone_bootstrap;
}

/**
 * Protect the ad-post form UX from the child theme layer.
 */
$bornado_ad_post_guard_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-ad-post-guard.php';
if (file_exists($bornado_ad_post_guard_bootstrap)) {
    require_once $bornado_ad_post_guard_bootstrap;
}

/**
 * Keep dashboard profile phone UX aligned with the selected country dial code.
 */
$bornado_profile_phone_guard_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornado-profile-phone-guard.php';
if (file_exists($bornado_profile_phone_guard_bootstrap)) {
    require_once $bornado_profile_phone_guard_bootstrap;
}

/**
 * Add a third single-ad layout from the child theme layer.
 */
$bornado_single_ad_style_bootstrap = trailingslashit(get_stylesheet_directory()) . 'bornad-single-ad-style.php';
if (file_exists($bornado_single_ad_style_bootstrap)) {
    require_once $bornado_single_ad_style_bootstrap;
}

if (!function_exists('bornado_flag_ad_search_template')) {
    /**
     * Remember the resolved front-end template (set on template_include after Bornado routing).
     *
     * @param string $template Absolute template path.
     * @return string
     */
    function bornado_flag_ad_search_template($template)
    {
        $GLOBALS['bornado_active_template'] = is_string($template) ? $template : '';

        return $template;
    }

    add_filter('template_include', 'bornado_flag_ad_search_template', 100);
}

if (!function_exists('bornado_is_ad_search_view')) {
    /**
     * True when the Ad Search listing UI is actually being rendered.
     */
    function bornado_is_ad_search_view()
    {
        $active_template = isset($GLOBALS['bornado_active_template']) ? (string) $GLOBALS['bornado_active_template'] : '';

        if ($active_template !== '') {
            if (false !== strpos($active_template, 'page-search.php')) {
                return true;
            }
            if (false !== strpos($active_template, 'seo-landing.php')) {
                return true;
            }
        }

        if (is_page_template('page-search.php')) {
            return true;
        }

        if (class_exists('Bornado_SEO_Routing') && method_exists('Bornado_SEO_Routing', 'is_valid_semantic_route')) {
            if (Bornado_SEO_Routing::is_valid_semantic_route()) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_get_theme_style_handles')) {
    /**
     * Parent style handles to load child overrides after (whichever is registered).
     *
     * @return string[]
     */
    function bornado_get_theme_style_handles()
    {
        $handles = array('adforest-main-responsive', 'adforest-main', 'adforest-style', 'adforest-pro-style');
        $deps    = array();

        foreach ($handles as $handle) {
            if (wp_style_is($handle, 'registered') || wp_style_is($handle, 'enqueued')) {
                $deps[] = $handle;
            }
        }

        return $deps;
    }
}

/**
 * Mark Ad Search views so layout CSS applies on semantic URLs too (not only page-template body class).
 */
add_filter('body_class', function ($classes) {
    if (bornado_is_ad_search_view()) {
        $classes[] = 'bornado-ad-search-view';
    }

    return $classes;
});

/**
 * Header + Ad Search layout CSS (late priority so parent/Redux styles load first).
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    $deps = bornado_get_theme_style_handles();

    $header_css = get_stylesheet_directory() . '/assets/css/bornado-header-layout.css';
    if (file_exists($header_css)) {
        wp_enqueue_style(
            'bornado-header-layout',
            get_stylesheet_directory_uri() . '/assets/css/bornado-header-layout.css',
            $deps,
            (string) filemtime($header_css)
        );
        $deps = array('bornado-header-layout');
    }

    if (!bornado_is_ad_search_view()) {
        return;
    }

    $search_css = get_stylesheet_directory() . '/assets/css/bornado-ad-search-layout.css';
    if (!file_exists($search_css)) {
        return;
    }

    wp_enqueue_style(
        'bornado-ad-search-layout',
        get_stylesheet_directory_uri() . '/assets/css/bornado-ad-search-layout.css',
        $deps,
        (string) filemtime($search_css)
    );
}, 200);

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

/**
 * Convert AdForest sort field to a compact icon trigger.
 *
 * Keep parent templates untouched by handling it with child theme assets only.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    $css = '
    .adt-ads-sort-box .adt-sort-filters {
        position: relative;
    }
    .adt-ads-sort-box .adt-sort-filters form {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        inset-inline-end: 0;
        z-index: 20;
        min-width: 210px;
        padding: 8px;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 10px;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
    }
    .adt-ads-sort-box .adt-sort-filters.bornado-sort-open form {
        display: block;
    }
    .adt-ads-sort-box .adt-sort-filters .bornado-sort-toggle,
    .adt-ads-sort-box .adt-sort-filters .adt-sort-toggle,
    .adt-ads-sort-box .adt-sort-filters .bornado-sort-trigger {
        width: 38px;
        height: 38px;
        border: 1px solid rgba(0, 0, 0, 0.14);
        border-radius: 10px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .adt-ads-sort-box .adt-sort-filters .bornado-sort-toggle:hover,
    .adt-ads-sort-box .adt-sort-filters .adt-sort-toggle:hover,
    .adt-ads-sort-box .adt-sort-filters .bornado-sort-trigger:hover,
    .adt-ads-sort-box .adt-sort-filters .bornado-sort-toggle:focus-visible,
    .adt-ads-sort-box .adt-sort-filters .adt-sort-toggle:focus-visible,
    .adt-ads-sort-box .adt-sort-filters .bornado-sort-trigger:focus-visible {
        border-color: #1479f6;
        color: #1479f6;
        box-shadow: 0 0 0 3px rgba(20, 121, 246, 0.14);
        outline: none;
    }
    .adt-ads-sort-box .adt-sort-filters .bornado-sort-toggle-icon {
        font-size: 18px;
        line-height: 1;
    }
    @media (max-width: 991.98px) {
        .adt-ads-sort-box {
            flex-direction: row !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            gap: 10px !important;
        }
        .adt-ads-sort-box h3 {
            margin: 0;
            flex: 1 1 auto;
            min-width: 0;
        }
        .adt-ads-sort-box .adt-sort-filters {
            width: auto !important;
            margin-inline-start: auto;
            flex: 0 0 auto;
        }
    }';

    wp_register_style('bornado-sort-icon-toggle', false);
    wp_enqueue_style('bornado-sort-icon-toggle');
    wp_add_inline_style('bornado-sort-icon-toggle', $css);

    $js = "
    document.addEventListener('DOMContentLoaded', function () {
        var wrappers = document.querySelectorAll('.adt-sort-filters');
        if (!wrappers.length) {
            return;
        }

        wrappers.forEach(function (wrapper) {
            if (wrapper.classList.contains('bornado-sort-ready')) {
                return;
            }

            var form = wrapper.querySelector('form');
            if (!form) {
                return;
            }

            wrapper.classList.add('bornado-sort-ready');

            var button = wrapper.querySelector('.bornado-sort-toggle, .adt-sort-toggle, .bornado-sort-trigger');
            if (!button) {
                button = document.createElement('button');
                button.type = 'button';
                button.className = 'bornado-sort-toggle bornado-sort-trigger';
                button.setAttribute('aria-label', 'مرتب سازی');
                button.innerHTML = '<span class=\"bornado-sort-toggle-icon\" aria-hidden=\"true\">⇅</span>';
                wrapper.insertBefore(button, form);
            } else {
                button.classList.add('bornado-sort-trigger');
                if (!button.getAttribute('aria-label')) {
                    button.setAttribute('aria-label', 'مرتب سازی');
                }
            }
            button.setAttribute('aria-expanded', 'false');

            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var isOpen = wrapper.classList.toggle('bornado-sort-open');
                button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            form.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.adt-sort-filters.bornado-sort-open').forEach(function (wrapper) {
                wrapper.classList.remove('bornado-sort-open');
                var toggle = wrapper.querySelector('.bornado-sort-toggle, .adt-sort-toggle, .bornado-sort-trigger');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
    ";

    wp_register_script('bornado-sort-icon-toggle', '', array(), null, true);
    wp_enqueue_script('bornado-sort-icon-toggle');
    wp_add_inline_script('bornado-sort-icon-toggle', $js);
}, 120);

add_action('init', function() {
    $taxonomies = ['ad_cats', 'ad_country', 'ad_condition', 'ad_type', 'ad_warranty', 'ad_currency'];
    
    foreach ($taxonomies as $taxonomy) {
        global $wp_taxonomies;
        if (isset($wp_taxonomies[$taxonomy])) {
            $wp_taxonomies[$taxonomy]->show_in_rest = true;
            $wp_taxonomies[$taxonomy]->rest_base = $taxonomy;
        }
    }
}, 999);

if (!function_exists('bornado_hide_manual_currency_field_when_disabled')) {
    /**
     * Hide the frontend ad-post currency selector when the theme option is off.
     *
     * The theme renders currency from two separate paths in AdPostModern:
     * the Elementor block itself and the category-template HTML injected later.
     * Keep backend auto-assignment active, but remove both manual UI entry points.
     */
    function bornado_hide_manual_currency_field_when_disabled()
    {
        if (is_admin()) {
            return;
        }

        global $adforest_theme;

        $currency_option_enabled = $adforest_theme['sb_currency_option_ad_post'] ?? false;
        if (!empty($currency_option_enabled)) {
            return;
        }

        $css = '
        .bornado-hide-ad-currency {
            display: none !important;
        }';

        wp_register_style('bornado-hide-ad-currency', false);
        wp_enqueue_style('bornado-hide-ad-currency');
        wp_add_inline_style('bornado-hide-ad-currency', $css);

        $js = "
        document.addEventListener('DOMContentLoaded', function () {
            function hideCurrencyField(root) {
                var scope = root && root.querySelectorAll ? root : document;

                scope.querySelectorAll('select[name=\"ad_currency\"]').forEach(function (select) {
                    var wrapper = null;

                    if (select.closest('#cat_template_html')) {
                        wrapper = select.closest('.row');
                    } else {
                        wrapper = select.closest('.field-box.location-box') || select.closest('.field-box') || select.closest('.row');
                    }

                    if (wrapper) {
                        wrapper.classList.add('bornado-hide-ad-currency');
                    }

                    select.required = false;
                    select.removeAttribute('required');
                    select.removeAttribute('data-parsley-required');
                });
            }

            hideCurrencyField(document);

            var categoryTemplate = document.getElementById('cat_template_html');
            if (!categoryTemplate || typeof MutationObserver === 'undefined') {
                return;
            }

            var observer = new MutationObserver(function () {
                hideCurrencyField(categoryTemplate);
            });

            observer.observe(categoryTemplate, {
                childList: true,
                subtree: true
            });
        });
        ";

        wp_register_script('bornado-hide-ad-currency', '', array(), null, true);
        wp_enqueue_script('bornado-hide-ad-currency');
        wp_add_inline_script('bornado-hide-ad-currency', $js);
    }
}
add_action('wp_enqueue_scripts', 'bornado_hide_manual_currency_field_when_disabled', 130);

if (!function_exists('bornado_sync_price_requirement_with_category_template')) {
    /**
     * Keep Ad Post Modern price validation aligned with the selected category template.
     *
     * AdForest's frontend JS re-applies `required` on price fields after each
     * price-type change, even when the selected `sb_dynamic_form_templates`
     * marks the default Price field as not required. Respect the template rule
     * here without editing core theme assets.
     */
    function bornado_sync_price_requirement_with_category_template()
    {
        if (is_admin()) {
            return;
        }

        $js = "
        document.addEventListener('DOMContentLoaded', function () {
            function getPriceField() {
                return document.querySelector('#cat_template_html #ad_price, #ad_price');
            }

            function getRangeFields() {
                return {
                    from: document.getElementById('ad_price_from'),
                    to: document.getElementById('ad_price_to')
                };
            }

            function getTemplateRequiredState() {
                var priceField = getPriceField();
                if (!priceField) {
                    return null;
                }

                var templateState = priceField.getAttribute('data-bornado-template-price-required');
                if (templateState === 'true' || templateState === 'false') {
                    return templateState === 'true';
                }

                var fieldBox = priceField.closest('.field-box') || priceField.parentElement;
                var label = fieldBox ? fieldBox.querySelector('label[for=\"ad_price\"], label') : null;
                var hasRequiredMarker = !!(label && label.querySelector('.required'));
                var parsleyState = String(priceField.getAttribute('data-parsley-required') || '').toLowerCase();
                var requiredState = priceField.hasAttribute('required');
                var isRequired = hasRequiredMarker || parsleyState === 'true' || requiredState;

                if (label) {
                    isRequired = hasRequiredMarker;
                }

                priceField.setAttribute('data-bornado-template-price-required', isRequired ? 'true' : 'false');
                return isRequired;
            }

            function setFieldRequired(field, isRequired) {
                if (!field) {
                    return;
                }

                if (isRequired) {
                    field.setAttribute('required', 'required');
                    field.setAttribute('data-parsley-required', 'true');
                } else {
                    field.removeAttribute('required');
                    field.setAttribute('data-parsley-required', 'false');
                }
            }

            function syncPriceRequirement() {
                var templateRequiresPrice = getTemplateRequiredState();
                if (templateRequiresPrice === null) {
                    return;
                }

                var priceTypeField = document.getElementById('ad_post_price_type');
                var priceType = priceTypeField ? String(priceTypeField.value || '') : '';
                var priceField = getPriceField();
                var rangeFields = getRangeFields();

                if (priceType === 'free' || priceType === 'no_price' || priceType === 'on_call') {
                    setFieldRequired(priceField, false);
                    setFieldRequired(rangeFields.from, false);
                    setFieldRequired(rangeFields.to, false);
                    return;
                }

                if (priceType === 'range') {
                    setFieldRequired(priceField, false);
                    setFieldRequired(rangeFields.from, templateRequiresPrice);
                    setFieldRequired(rangeFields.to, templateRequiresPrice);
                    return;
                }

                setFieldRequired(priceField, templateRequiresPrice);
                setFieldRequired(rangeFields.from, false);
                setFieldRequired(rangeFields.to, false);
            }

            function afterThemeHandlers(callback) {
                window.setTimeout(callback, 0);
            }

            syncPriceRequirement();

            document.addEventListener('change', function (event) {
                if (event.target && event.target.id === 'ad_post_price_type') {
                    afterThemeHandlers(syncPriceRequirement);
                }
            }, true);

            var categoryTemplate = document.getElementById('cat_template_html');
            if (categoryTemplate && typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function () {
                    afterThemeHandlers(syncPriceRequirement);
                });

                observer.observe(categoryTemplate, {
                    childList: true,
                    subtree: true
                });
            }

            document.addEventListener('adforestCategoryTemplateLoaded', function () {
                afterThemeHandlers(syncPriceRequirement);
            });
        });
        ";

        wp_register_script('bornado-sync-price-required', '', array(), null, true);
        wp_enqueue_script('bornado-sync-price-required');
        wp_add_inline_script('bornado-sync-price-required', $js);
    }
}
add_action('wp_enqueue_scripts', 'bornado_sync_price_requirement_with_category_template', 131);