<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_breadcrumb_show_current_page_title')) {
    /**
     * Whether the visible breadcrumb should repeat the current page title.
     *
     * Default false: the page H1 already states the current page; breadcrumbs show the path only.
     *
     * @return bool
     */
    function bornado_breadcrumb_show_current_page_title()
    {
        return (bool) apply_filters('bornado_breadcrumb_show_current_page_title', false);
    }
}

if (!function_exists('bornado_semantic_breadcrumb_get_route_context')) {
    /**
     * Return the current semantic route context when available.
     *
     * @return array<string,mixed>
     */
    function bornado_semantic_breadcrumb_get_route_context()
    {
        if (!function_exists('bornado_seo_routing_get_context')) {
            return array();
        }

        $context = bornado_seo_routing_get_context();
        if (empty($context['is_seo_route']) || empty($context['is_valid'])) {
            return array();
        }

        return is_array($context) ? $context : array();
    }
}

if (!function_exists('bornado_semantic_breadcrumb_get_items')) {
    /**
     * Build semantic breadcrumb items for the current route.
     *
     * @return array<int,array<string,string|bool>>
     */
    function bornado_semantic_breadcrumb_get_items()
    {
        $context = bornado_semantic_breadcrumb_get_route_context();
        if (empty($context)) {
            return array();
        }

        $items          = array();
        $paged          = !empty($context['paged']) ? max(1, (int) $context['paged']) : 1;
        $is_archive_native = !empty($context['is_archive_native']);
        $country_term   = !empty($context['country_term']) && $context['country_term'] instanceof WP_Term ? $context['country_term'] : null;
        $city_term      = !empty($context['city_term']) && $context['city_term'] instanceof WP_Term ? $context['city_term'] : null;
        $category_terms = !empty($context['category_terms']) && is_array($context['category_terms']) ? $context['category_terms'] : array();
        $category_terms = array_values(array_filter($category_terms, function ($term) {
            return $term instanceof WP_Term;
        }));

        if ($country_term instanceof WP_Term) {
            $country_is_current = $is_archive_native && !($city_term instanceof WP_Term) && empty($category_terms) && $paged < 2;
            $items[] = array(
                'label'  => $country_term->name,
                'url'    => $country_is_current ? '' : bornado_semantic_breadcrumb_get_semantic_archive_url((int) $country_term->term_id, 0, 0),
                'active' => $country_is_current,
            );
        }

        if ($city_term instanceof WP_Term) {
            $city_is_current = $is_archive_native && empty($category_terms) && $paged < 2;
            $items[] = array(
                'label'  => $city_term->name,
                'url'    => $city_is_current ? '' : bornado_semantic_breadcrumb_get_semantic_archive_url(
                    $country_term instanceof WP_Term ? (int) $country_term->term_id : 0,
                    (int) $city_term->term_id,
                    0
                ),
                'active' => $city_is_current,
            );
        }

        foreach ($category_terms as $term) {
            $term_url = get_term_link($term);

            $items[] = array(
                'label'  => $term->name,
                'url'    => is_wp_error($term_url) ? '' : (string) $term_url,
                'active' => false,
            );
        }

        if ($paged > 1) {
            $items[] = array(
                'label'  => esc_html__('Page', 'adforest') . ' ' . $paged,
                'url'    => '',
                'active' => true,
            );
        }

        return $items;
    }
}

if (!function_exists('bornado_semantic_breadcrumb_output_current_route_items')) {
    /**
     * Echo semantic breadcrumb items if the current route is handled by bornado-routing.
     *
     * @return bool
     */
    function bornado_semantic_breadcrumb_output_current_route_items()
    {
        $items = bornado_semantic_breadcrumb_get_items();
        if (empty($items)) {
            return false;
        }

        foreach ($items as $item) {
            $label = isset($item['label']) ? (string) $item['label'] : '';
            $url   = isset($item['url']) ? (string) $item['url'] : '';

            if ('' !== $url) {
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url($url),
                    esc_html($label)
                );
                continue;
            }

            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($label)
            );
        }

        return true;
    }
}

if (!function_exists('bornado_semantic_breadcrumb_get_post_term_chain')) {
    /**
     * Resolve a post term chain from the top-level term to the deepest assigned child.
     *
     * @param int    $post_id  Post ID.
     * @param string $taxonomy Taxonomy name.
     * @return array<int,WP_Term>
     */
    function bornado_semantic_breadcrumb_get_post_term_chain($post_id, $taxonomy)
    {
        $post_id  = (int) $post_id;
        $taxonomy = is_string($taxonomy) ? $taxonomy : '';
        if ($post_id < 1 || $taxonomy === '') {
            return array();
        }

        $raw_terms = wp_get_post_terms($post_id, $taxonomy);
        if (is_wp_error($raw_terms) || empty($raw_terms)) {
            return array();
        }

        $terms_by_id = array();
        foreach ($raw_terms as $term) {
            if ($term instanceof WP_Term) {
                $terms_by_id[(int) $term->term_id] = $term;
            }
        }

        if (empty($terms_by_id)) {
            return array();
        }

        $deepest_term = null;
        $deepest_depth = -1;
        foreach ($terms_by_id as $term) {
            $depth = count(get_ancestors((int) $term->term_id, $taxonomy, 'taxonomy'));
            if ($depth > $deepest_depth) {
                $deepest_term  = $term;
                $deepest_depth = $depth;
            }
        }

        if (!$deepest_term instanceof WP_Term) {
            return array_values($terms_by_id);
        }

        $chain_ids   = array_reverse(array_map('intval', get_ancestors((int) $deepest_term->term_id, $taxonomy, 'taxonomy')));
        $chain_ids[] = (int) $deepest_term->term_id;
        $chain       = array();

        foreach ($chain_ids as $term_id) {
            if (isset($terms_by_id[$term_id])) {
                $chain[] = $terms_by_id[$term_id];
                continue;
            }

            $term = get_term($term_id, $taxonomy);
            if ($term instanceof WP_Term) {
                $chain[] = $term;
            }
        }

        return $chain;
    }
}

if (!function_exists('bornado_semantic_breadcrumb_get_semantic_archive_url')) {
    /**
     * Build a semantic archive URL with optional city context.
     *
     * @param int $country_id  Country term ID.
     * @param int $city_id     City term ID.
     * @param int $category_id Category term ID.
     * @return string
     */
    function bornado_semantic_breadcrumb_get_semantic_archive_url($country_id = 0, $city_id = 0, $category_id = 0)
    {
        $country_id  = (int) $country_id;
        $city_id     = (int) $city_id;
        $category_id = (int) $category_id;

        if (class_exists('Bornado_SEO_Routing') && method_exists('Bornado_SEO_Routing', 'get_semantic_url_preview')) {
            $url = (string) Bornado_SEO_Routing::get_semantic_url_preview($country_id, $city_id, $category_id);
            if ($url !== '') {
                return $url;
            }
        }

        if ($category_id > 0) {
            $term_link = get_term_link($category_id, 'ad_cats');
            return is_wp_error($term_link) ? '' : (string) $term_link;
        }

        if ($city_id > 0) {
            $term_link = get_term_link($city_id, 'ad_country');
            return is_wp_error($term_link) ? '' : (string) $term_link;
        }

        if ($country_id > 0) {
            $term_link = get_term_link($country_id, 'ad_country');
            return is_wp_error($term_link) ? '' : (string) $term_link;
        }

        return '';
    }
}

if (!function_exists('bornado_semantic_breadcrumb_get_single_ad_items')) {
    /**
     * Build a semantic breadcrumb for single ad pages.
     *
     * @param int|null $post_id Optional post ID.
     * @return array<int,array<string,string|bool>>
     */
    function bornado_semantic_breadcrumb_get_single_ad_items($post_id = null, $for_schema = false)
    {
        $post = $post_id ? get_post((int) $post_id) : get_queried_object();
        $show_current_title = $for_schema || bornado_breadcrumb_show_current_page_title();
        if (!$post instanceof WP_Post || $post->post_type !== 'ad_post') {
            return array();
        }

        $items          = array();
        $city_chain     = bornado_semantic_breadcrumb_get_post_term_chain((int) $post->ID, 'ad_country');
        $category_chain = bornado_semantic_breadcrumb_get_post_term_chain((int) $post->ID, 'ad_cats');
        $country_term   = null;
        $city_term      = null;

        if (!empty($city_chain)) {
            $first_term = reset($city_chain);
            if ($first_term instanceof WP_Term) {
                $country_term = $first_term;
            }
        }

        foreach ($city_chain as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }

            // In AdForest location trees, depth 2 most commonly maps to the city level.
            if (count(get_ancestors((int) $term->term_id, 'ad_country', 'taxonomy')) === 2) {
                $city_term = $term;
                break;
            }
        }

        if (!($city_term instanceof WP_Term) && !empty($city_chain)) {
            $city_term = end($city_chain);
        }

        $country_id     = $country_term instanceof WP_Term ? (int) $country_term->term_id : 0;
        $city_id        = $city_term instanceof WP_Term ? (int) $city_term->term_id : 0;

        if ($country_term instanceof WP_Term) {
            $items[] = array(
                'label'  => $country_term->name,
                'url'    => bornado_semantic_breadcrumb_get_semantic_archive_url($country_id, 0, 0),
                'active' => false,
            );
        }

        if ($city_term instanceof WP_Term) {
            $items[] = array(
                'label'  => $city_term->name,
                'url'    => bornado_semantic_breadcrumb_get_semantic_archive_url($country_id, $city_id, 0),
                'active' => false,
            );
        }

        foreach ($category_chain as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }

            $items[] = array(
                'label'  => $term->name,
                'url'    => bornado_semantic_breadcrumb_get_semantic_archive_url($country_id, $city_id, (int) $term->term_id),
                'active' => false,
            );
        }

        if ($show_current_title) {
            $items[] = array(
                'label'  => get_the_title($post),
                'url'    => '',
                'active' => true,
            );
        }

        return $items;
    }
}

if (!function_exists('bornado_semantic_breadcrumb_output_single_ad_items')) {
    /**
     * Echo semantic breadcrumb items for single ad pages.
     *
     * @return bool
     */
    function bornado_semantic_breadcrumb_output_single_ad_items()
    {
        $items = bornado_semantic_breadcrumb_get_single_ad_items();
        if (empty($items)) {
            return false;
        }

        foreach ($items as $item) {
            $label  = isset($item['label']) ? (string) $item['label'] : '';
            $url    = isset($item['url']) ? (string) $item['url'] : '';

            if ('' !== $url) {
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url($url),
                    esc_html($label)
                );
                continue;
            }

            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($label)
            );
        }

        return true;
    }
}

if (!function_exists('bornado_semantic_breadcrumb_has_external_schema_provider')) {
    /**
     * Detect whether an SEO plugin likely outputs its own breadcrumb schema.
     *
     * @return bool
     */
    function bornado_semantic_breadcrumb_has_external_schema_provider()
    {
        if (function_exists('bornado_seo_routing_has_external_seo_provider')) {
            return (bool) bornado_seo_routing_has_external_seo_provider();
        }

        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) {
            return true;
        }

        if (class_exists('RankMath') || defined('RANK_MATH_VERSION')) {
            return true;
        }

        if (defined('AIOSEO_VERSION') || function_exists('aioseo')) {
            return true;
        }

        return false;
    }
}

if (!function_exists('bornado_print_semantic_breadcrumb_schema')) {
    /**
     * Print BreadcrumbList schema for semantic routes when no SEO plugin owns it.
     *
     * @return void
     */
    function bornado_print_semantic_breadcrumb_schema()
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (
            function_exists('bornado_schema_manager_should_skip_legacy_breadcrumb_schema')
            && bornado_schema_manager_should_skip_legacy_breadcrumb_schema()
        ) {
            return;
        }

        if (bornado_semantic_breadcrumb_has_external_schema_provider()) {
            return;
        }

        $context       = bornado_semantic_breadcrumb_get_route_context();
        $items         = array();
        $canonical_url = '';

        if (!empty($context)) {
            $items         = bornado_semantic_breadcrumb_get_items();
            $canonical_url = !empty($context['canonical_url']) ? (string) $context['canonical_url'] : '';
        } elseif (is_singular('ad_post')) {
            $items         = bornado_semantic_breadcrumb_get_single_ad_items(null, true);
            $canonical_url = (string) get_permalink(get_queried_object_id());
        }

        if (empty($items) || $canonical_url === '') {
            return;
        }

        $schema_items = array(
            array(
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => wp_strip_all_tags(esc_html__('Home', 'adforest')),
                'item'     => home_url('/'),
            ),
        );

        $position      = 2;
        foreach ($items as $item) {
            $item_url = isset($item['url']) ? (string) $item['url'] : '';
            if ('' === $item_url) {
                $item_url = $canonical_url;
            }

            if ('' === $item_url) {
                continue;
            }

            $schema_items[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => wp_strip_all_tags(isset($item['label']) ? (string) $item['label'] : ''),
                'item'     => $item_url,
            );
            $position++;
        }

        if (count($schema_items) < 2) {
            return;
        }

        $schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $schema_items,
        );

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>\n";
    }
}
add_action('wp_head', 'bornado_print_semantic_breadcrumb_schema', 25);

if (!function_exists('bornado_is_ad_post_page')) {
    /**
     * Whether the current request is the AdForest post-ad page.
     *
     * @return bool
     */
    function bornado_is_ad_post_page()
    {
        if (is_page_template('page-add-new.php')) {
            return true;
        }

        global $adforest_theme;

        $page_id = isset($adforest_theme['sb_post_ad_page'])
            ? (int) apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page'])
            : 0;

        if ($page_id > 0) {
            $page_id = (int) apply_filters('adforest_ad_post_verified_id', $page_id);
        }

        if ($page_id > 0 && is_page($page_id)) {
            return true;
        }

        return is_page('ad-post');
    }
}

if (!function_exists('bornado_should_hide_breadcrumb_on_real_front_page')) {
    /**
     * Hide breadcrumbs only on the actual front page request.
     *
     * Semantic search routes can still be bound to the front-page object internally,
     * so `is_front_page()` alone is too broad for Bornado search URLs.
     *
     * @return bool
     */
    function bornado_should_hide_breadcrumb_on_real_front_page()
    {
        $homepage_id = (int) get_option('page_on_front');
        if ($homepage_id < 1 || !is_front_page()) {
            return false;
        }

        $route_context = function_exists('bornado_seo_routing_get_context')
            ? (array) bornado_seo_routing_get_context()
            : array();
        if (!empty($route_context['is_seo_route'])) {
            return false;
        }

        $public_query_args = function_exists('bornado_seo_routing_get_public_query_args')
            ? (array) bornado_seo_routing_get_public_query_args()
            : array();
        if (!empty($public_query_args)) {
            return false;
        }

        $request_uri  = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_path = is_string($request_uri) ? (string) wp_parse_url($request_uri, PHP_URL_PATH) : '';
        $home_path    = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);

        return untrailingslashit($request_path) === untrailingslashit($home_path);
    }
}

if (!function_exists('adforest_custom_breadcrumbs')) {
    function adforest_custom_breadcrumbs($page_class = '')
    {
        global $adforest_theme, $post, $author;

        if (bornado_is_ad_post_page()) {
            return;
        }

        if (bornado_should_hide_breadcrumb_on_real_front_page()) {
            return;
        }

        $adt_container_class = '';
        if (!is_array($adforest_theme)) {
            $adt_container_class = 'adt-container';
        }
        if (isset($adforest_theme)) {
            if (empty($adforest_theme['ad_forest_show_breadcrumb'])) {
                return;
            }

            if (!empty($adforest_theme['sb_header']) &&
                in_array($adforest_theme['sb_header'], array('white', 'header_w_topbar'), true)) {
                $adt_container_class = 'adt-container';
            }
        }

        $home_title = esc_html__('Home', 'adforest');
        $home_url = esc_url(home_url('/'));

        echo '<!-- adt-breadcrumb-start -->';
        echo '<div class="adt-breadcrumb ' . esc_attr($page_class) . '">';
        echo '<div class="container ' . esc_attr($adt_container_class) . '">';
        echo '<div class="row">';
        echo '<div class="col-lg-12">';
        echo '<nav aria-label="' . esc_attr__('Breadcrumb', 'adforest') . '">';
        echo '<ol class="breadcrumb">';

        printf(
            '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
            $home_url,
            $home_title
        );

        if (is_home()) {
            $blog_id = get_option('page_for_posts');
            $blog_title = $blog_id
                ? get_the_title($blog_id)
                : esc_html__('Blog', 'adforest');
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($blog_title)
            );
        } elseif (is_author()) {
            $userdata = get_userdata($author);
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($userdata->display_name)
            );
        } elseif (bornado_semantic_breadcrumb_output_current_route_items()) {
        } elseif (is_tax('ad_cats') || is_tax('ad_country')) {
            $term = get_queried_object();
            if (!empty($term->parent)) {
                $parent = get_term($term->parent, $term->taxonomy);
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url(get_term_link($parent)),
                    esc_html($parent->name)
                );
            }
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($term->name)
            );
        } elseif (is_single()) {
            $post_type = get_post_type();
            $single_title_rendered = false;

            $not_product = !function_exists('is_product') || !is_product();
            if ($post_type === 'ad_post' && bornado_semantic_breadcrumb_output_single_ad_items()) {
                $single_title_rendered = true;
            } elseif ($post_type !== 'post' && $not_product) {
                $search_page_id = '';

                if (isset($adforest_theme) && is_array($adforest_theme) && isset($adforest_theme['sb_search_page']) && $adforest_theme['sb_search_page'] !== '') {
                    $search_page_id = apply_filters(
                        'adforest_language_page_id',
                        $adforest_theme['sb_search_page']
                    );
                }

                $search_url = $search_page_id
                    ? get_permalink($search_page_id)
                    : 'javascript:void(0)';
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url($search_url),
                    esc_html__('Classified Ads', 'adforest')
                );
            } elseif (function_exists('is_product') && is_product()) {
                $shop_url = wc_get_page_permalink('shop');
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url($shop_url),
                    esc_html__('Shop', 'adforest')
                );
            } else {
                $blog_id = get_option('page_for_posts');
                $blog_title = $blog_id
                    ? get_the_title($blog_id)
                    : esc_html__('Blog', 'adforest');
                $blog_url = $blog_id
                    ? get_permalink($blog_id)
                    : home_url('/');
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url($blog_url),
                    esc_html($blog_title)
                );
            }

            if (!$single_title_rendered && bornado_breadcrumb_show_current_page_title()) {
                printf(
                    '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                    esc_html(get_the_title())
                );
            }
        } elseif (is_category()) {
            $cat = get_category(get_query_var('cat'));
            if (!empty($cat->parent)) {
                $parent = get_category($cat->parent);
                echo get_category_parents(
                    $parent,
                    true,
                    '<li class="breadcrumb-item">',
                    false
                );
            }
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(single_cat_title('', false))
            );
        } elseif (is_page()) {
            if ($post->post_parent) {
                $ancestors = array_reverse(get_post_ancestors($post->ID));
                foreach ($ancestors as $ancestor) {
                    printf(
                        '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                        esc_url(get_permalink($ancestor)),
                        esc_html(get_the_title($ancestor))
                    );
                }
            }

            if (bornado_breadcrumb_show_current_page_title()) {
                printf(
                    '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                    esc_html(get_the_title())
                );
            }
        } elseif (is_tag()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(single_tag_title('', false))
            );
        } elseif (is_day()) {
            printf(
                '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                esc_url(get_year_link(get_the_time('Y'))),
                esc_html(get_the_time('Y'))
            );
            printf(
                '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                esc_url(get_month_link(get_the_time('Y'), get_the_time('m'))),
                esc_html(get_the_time('F'))
            );
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_time('j'))
            );
        } elseif (is_month()) {
            printf(
                '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                esc_url(get_year_link(get_the_time('Y'))),
                esc_html(get_the_time('Y'))
            );
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_time('F'))
            );
        } elseif (is_year()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_time('Y'))
            );
        } elseif (get_query_var('paged')) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html__('Page ', 'adforest') . intval(get_query_var('paged'))
            );
        } elseif (is_search()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html__('Search results for: ', 'adforest') . esc_html(get_search_query())
            );
        } elseif (is_404()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html__('404, Not Found!', 'adforest')
            );
        }

        echo '</ol>';
        echo '</nav>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<!-- adt-breadcrumb-end -->';
    }
}