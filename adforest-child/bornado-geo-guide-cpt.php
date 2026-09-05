<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_geo_guide_post_type')) {
    /**
     * Custom post type for Iranians geo guides.
     *
     * @return string
     */
    function bornado_geo_guide_post_type()
    {
        return 'bornado_geo_guide';
    }
}

if (!function_exists('bornado_geo_guide_rewrite_slug')) {
    /**
     * Public URL prefix. Final city URL: /iranians/{country}/{city}/
     *
     * @return string
     */
    function bornado_geo_guide_rewrite_slug()
    {
        return 'iranians';
    }
}

if (!function_exists('bornado_geo_guide_rewrite_version')) {
    /**
     * Bump to flush permalinks after CPT/rewrite changes.
     *
     * @return string
     */
    function bornado_geo_guide_rewrite_version()
    {
        return '1.0.0';
    }
}

if (!function_exists('bornado_geo_guide_is_guide_post')) {
    /**
     * Whether a post is a geo-guide CPT entry.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    function bornado_geo_guide_is_guide_post($post_id = 0)
    {
        $post = get_post((int) $post_id);
        return $post instanceof WP_Post && $post->post_type === bornado_geo_guide_post_type();
    }
}

if (!function_exists('bornado_geo_guide_is_city_guide')) {
    /**
     * City guides have a city term. Country hubs do not.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    function bornado_geo_guide_is_city_guide($post_id = 0)
    {
        if (!function_exists('bornado_geo_guide_get_meta')) {
            return false;
        }

        return (int) bornado_geo_guide_get_meta((int) $post_id, 'city_term_id', 0) > 0;
    }
}

if (!function_exists('bornado_geo_guide_is_indexable')) {
    /**
     * Only city guides are indexed until a country hub is intentionally filled later.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    function bornado_geo_guide_is_indexable($post_id = 0)
    {
        $post_id = (int) $post_id;
        $post    = get_post($post_id);
        if (!($post instanceof WP_Post) || $post->post_status !== 'publish') {
            return false;
        }

        if (!bornado_geo_guide_is_guide_post($post_id)) {
            return true;
        }

        return bornado_geo_guide_is_city_guide($post_id);
    }
}

if (!function_exists('bornado_geo_guide_register_post_type')) {
    /**
     * Register the dedicated Iranians guide content type.
     *
     * @return void
     */
    function bornado_geo_guide_register_post_type()
    {
        $post_type = bornado_geo_guide_post_type();
        $slug      = bornado_geo_guide_rewrite_slug();

        register_post_type(
            $post_type,
            array(
                'labels' => array(
                    'name'               => 'راهنمای ایرانیان',
                    'singular_name'      => 'راهنمای ایرانیان',
                    'add_new'            => 'افزودن راهنما',
                    'add_new_item'       => 'افزودن راهنمای جدید',
                    'edit_item'          => 'ویرایش راهنما',
                    'new_item'           => 'راهنمای جدید',
                    'view_item'          => 'مشاهده راهنما',
                    'view_items'         => 'مشاهده راهنماها',
                    'search_items'       => 'جستجوی راهنما',
                    'not_found'          => 'راهنمایی پیدا نشد.',
                    'not_found_in_trash' => 'در زباله‌دان راهنمایی نیست.',
                    'parent_item_colon'  => 'کشور والد:',
                    'all_items'          => 'همه راهنماها',
                    'menu_name'          => 'راهنمای ایرانیان',
                    'attributes'         => 'ساختار URL',
                ),
                'description'         => 'صفحات جامعه و راهنمای ایرانیان در کشورها و شهرها، جدا از لیست آگهی‌ها.',
                'public'              => true,
                'publicly_queryable'  => true,
                'exclude_from_search' => false,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_nav_menus'   => true,
                'show_in_admin_bar'   => true,
                'show_in_rest'        => true,
                'menu_position'       => 27,
                'menu_icon'           => 'dashicons-groups',
                'capability_type'     => 'page',
                'map_meta_cap'        => true,
                'hierarchical'        => true,
                'has_archive'         => false,
                'rewrite'             => array(
                    'slug'       => $slug,
                    'with_front' => false,
                    'pages'      => false,
                    'feeds'      => false,
                ),
                'query_var'           => true,
                'delete_with_user'    => false,
                'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes'),
            )
        );
    }
}
add_action('init', 'bornado_geo_guide_register_post_type', 5);

if (!function_exists('bornado_geo_guide_maybe_flush_rewrites')) {
    /**
     * Flush permalinks once after CPT registration.
     *
     * @return void
     */
    function bornado_geo_guide_maybe_flush_rewrites()
    {
        if (wp_installing()) {
            return;
        }

        $option  = 'bornado_geo_guide_rewrite_version';
        $version = bornado_geo_guide_rewrite_version();
        if (get_option($option) === $version) {
            return;
        }

        bornado_geo_guide_register_post_type();
        flush_rewrite_rules(false);
        update_option($option, $version, false);
    }
}
add_action('init', 'bornado_geo_guide_maybe_flush_rewrites', 20);

if (!function_exists('bornado_geo_guide_flush_rewrites_on_switch')) {
    /**
     * Flush permalinks when the child theme is activated.
     *
     * @return void
     */
    function bornado_geo_guide_flush_rewrites_on_switch()
    {
        delete_option('bornado_geo_guide_rewrite_version');
    }
}
add_action('after_switch_theme', 'bornado_geo_guide_flush_rewrites_on_switch');

if (!function_exists('bornado_geo_guide_reserve_routing_prefix')) {
    /**
     * Keep listing routes from claiming /iranians/.
     *
     * @param array<int,string> $prefixes Reserved first-path prefixes.
     * @return array<int,string>
     */
    function bornado_geo_guide_reserve_routing_prefix($prefixes)
    {
        $prefixes   = is_array($prefixes) ? $prefixes : array();
        $prefixes[] = bornado_geo_guide_rewrite_slug();

        return array_values(array_unique(array_filter(array_map('sanitize_title', $prefixes))));
    }
}
add_filter('bornado_seo_routing_reserved_prefixes', 'bornado_geo_guide_reserve_routing_prefix');

if (!function_exists('bornado_geo_guide_hide_legacy_page_template')) {
    /**
     * Stop creating new guides as ordinary Pages.
     *
     * @param array<string,string> $templates Page templates.
     * @return array<string,string>
     */
    function bornado_geo_guide_hide_legacy_page_template($templates)
    {
        unset($templates['page-geo-guide.php']);

        return $templates;
    }
}
add_filter('theme_page_templates', 'bornado_geo_guide_hide_legacy_page_template');

if (!function_exists('bornado_geo_guide_filter_wp_robots')) {
    /**
     * Keep country URL parents out of the index.
     *
     * @param array<string,mixed> $robots Robots directives.
     * @return array<string,mixed>
     */
    function bornado_geo_guide_filter_wp_robots($robots)
    {
        if (!is_singular(bornado_geo_guide_post_type())) {
            return is_array($robots) ? $robots : array();
        }

        if (bornado_geo_guide_is_indexable(get_queried_object_id())) {
            return is_array($robots) ? $robots : array();
        }

        $robots = is_array($robots) ? $robots : array();
        $robots['noindex'] = true;
        $robots['follow']  = true;
        unset($robots['index']);

        return $robots;
    }
}
add_filter('wp_robots', 'bornado_geo_guide_filter_wp_robots', 30);

if (!function_exists('bornado_geo_guide_filter_rank_math_robots')) {
    /**
     * Mirror noindex into Rank Math output.
     *
     * @param array<string,string> $robots Rank Math robots.
     * @return array<string,string>
     */
    function bornado_geo_guide_filter_rank_math_robots($robots)
    {
        if (!is_singular(bornado_geo_guide_post_type())) {
            return $robots;
        }

        if (bornado_geo_guide_is_indexable(get_queried_object_id())) {
            return $robots;
        }

        $robots          = is_array($robots) ? $robots : array();
        $robots['index'] = 'noindex';
        $robots['follow'] = 'follow';

        return $robots;
    }
}
add_filter('rank_math/frontend/robots', 'bornado_geo_guide_filter_rank_math_robots');

if (!function_exists('bornado_geo_guide_filter_sitemap_entry')) {
    /**
     * Drop country hubs from Rank Math sitemaps.
     *
     * @param array<string,mixed>|false $url    Sitemap entry.
     * @param string                    $type   Object type.
     * @param WP_Post|object            $object Object.
     * @return array<string,mixed>|false
     */
    function bornado_geo_guide_filter_sitemap_entry($url, $type, $object)
    {
        if ($type !== 'post' || !($object instanceof WP_Post) || $object->post_type !== bornado_geo_guide_post_type()) {
            return $url;
        }

        return bornado_geo_guide_is_indexable((int) $object->ID) ? $url : false;
    }
}
add_filter('rank_math/sitemap/entry', 'bornado_geo_guide_filter_sitemap_entry', 10, 3);

if (!function_exists('bornado_geo_guide_filter_rank_math_sitemap_count')) {
    /**
     * Count only city guides in the Rank Math sitemap index.
     *
     * @param string       $where     SQL where clause.
     * @param string|array $post_type Post type.
     * @return string
     */
    function bornado_geo_guide_filter_rank_math_sitemap_count($where, $post_type)
    {
        if ((is_string($post_type) ? $post_type : '') !== bornado_geo_guide_post_type()) {
            return $where;
        }

        global $wpdb;

        return $where . $wpdb->prepare(
            " AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} AS bornado_city
                WHERE bornado_city.post_id = p.ID
                AND bornado_city.meta_key = %s
                AND CAST(bornado_city.meta_value AS UNSIGNED) > 0
            )",
            '_bornado_geo_guide_city_term_id'
        );
    }
}
add_filter('rank_math/sitemap/post_count/where', 'bornado_geo_guide_filter_rank_math_sitemap_count', 10, 2);

if (!function_exists('bornado_geo_guide_sync_rank_math_robots')) {
    /**
     * Keep Rank Math post robots aligned with city vs country-hub rules.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    function bornado_geo_guide_sync_rank_math_robots($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1 || wp_is_post_revision($post_id) || !bornado_geo_guide_is_guide_post($post_id)) {
            return;
        }

        if (bornado_geo_guide_is_city_guide($post_id)) {
            $robots = get_post_meta($post_id, 'rank_math_robots', true);
            if (!is_array($robots) || in_array('noindex', $robots, true)) {
                update_post_meta($post_id, 'rank_math_robots', array('index', 'follow'));
            }
        } else {
            update_post_meta($post_id, 'rank_math_robots', array('noindex', 'follow'));
        }
    }
}
add_action('save_post_' . 'bornado_geo_guide', 'bornado_geo_guide_sync_rank_math_robots', 20);

if (!function_exists('bornado_geo_guide_invalidate_rank_math_sitemap')) {
    /**
     * Drop Rank Math's cached sitemap index after a guide change.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    function bornado_geo_guide_invalidate_rank_math_sitemap($post_id)
    {
        if (!bornado_geo_guide_is_guide_post((int) $post_id)) {
            return;
        }

        if (class_exists('\RankMath\Sitemap\Cache_Watcher')) {
            \RankMath\Sitemap\Cache_Watcher::invalidate(bornado_geo_guide_post_type());
        }
    }
}
add_action('save_post_' . 'bornado_geo_guide', 'bornado_geo_guide_invalidate_rank_math_sitemap', 30);

if (!function_exists('bornado_geo_guide_sync_existing_rank_math_robots')) {
    /**
     * One-time sync so already-published city guides become sitemap-eligible.
     *
     * @return void
     */
    function bornado_geo_guide_sync_existing_rank_math_robots()
    {
        if (wp_installing() || get_option('bornado_geo_guide_rank_math_robots_sync') === '1') {
            return;
        }

        $posts = get_posts(array(
            'post_type'      => bornado_geo_guide_post_type(),
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));

        foreach ($posts as $post_id) {
            bornado_geo_guide_sync_rank_math_robots((int) $post_id);
        }

        if (class_exists('\RankMath\Sitemap\Cache_Watcher')) {
            \RankMath\Sitemap\Cache_Watcher::invalidate(bornado_geo_guide_post_type());
        }

        update_option('bornado_geo_guide_rank_math_robots_sync', '1', false);
    }
}
add_action('init', 'bornado_geo_guide_sync_existing_rank_math_robots', 30);

if (!function_exists('bornado_geo_guide_filter_core_sitemap_query')) {
    /**
     * Drop country hubs from the core sitemap.
     *
     * @param array<string,mixed> $args      Query args.
     * @param string              $post_type Post type.
     * @return array<string,mixed>
     */
    function bornado_geo_guide_filter_core_sitemap_query($args, $post_type)
    {
        if ($post_type !== bornado_geo_guide_post_type()) {
            return $args;
        }

        $meta_query = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : array();
        $meta_query[] = array(
            'key'     => '_bornado_geo_guide_city_term_id',
            'value'   => 0,
            'compare' => '>',
            'type'    => 'NUMERIC',
        );
        $args['meta_query'] = $meta_query;

        return $args;
    }
}
add_filter('wp_sitemaps_posts_query_args', 'bornado_geo_guide_filter_core_sitemap_query', 10, 2);

if (!function_exists('bornado_geo_guide_admin_columns')) {
    /**
     * Extra list-table columns.
     *
     * @param array<string,string> $columns Columns.
     * @return array<string,string>
     */
    function bornado_geo_guide_admin_columns($columns)
    {
        $ordered = array();
        foreach ($columns as $key => $label) {
            $ordered[$key] = $label;
            if ($key === 'title') {
                $ordered['bornado_guide_kind']     = 'نوع';
                $ordered['bornado_guide_location'] = 'بازار';
                $ordered['bornado_guide_index']    = 'ایندکس';
            }
        }

        return $ordered;
    }
}
add_filter('manage_' . 'bornado_geo_guide' . '_posts_columns', 'bornado_geo_guide_admin_columns');

if (!function_exists('bornado_geo_guide_render_admin_columns')) {
    /**
     * Render extra list-table columns.
     *
     * @param string $column  Column key.
     * @param int    $post_id Post ID.
     * @return void
     */
    function bornado_geo_guide_render_admin_columns($column, $post_id)
    {
        $post_id = (int) $post_id;
        if (!function_exists('bornado_geo_guide_get_meta')) {
            echo '—';
            return;
        }

        $country_id = (int) bornado_geo_guide_get_meta($post_id, 'country_term_id', 0);
        $city_id    = (int) bornado_geo_guide_get_meta($post_id, 'city_term_id', 0);
        $country    = $country_id > 0 ? get_term($country_id, 'ad_country') : null;
        $city       = $city_id > 0 ? get_term($city_id, 'ad_country') : null;

        if ($column === 'bornado_guide_kind') {
            echo $city_id > 0 ? 'شهر' : 'کشور / اسکلت URL';
            return;
        }

        if ($column === 'bornado_guide_location') {
            $parts = array();
            if ($country instanceof WP_Term) {
                $parts[] = $country->slug;
            }
            if ($city instanceof WP_Term) {
                $parts[] = $city->slug;
            }
            echo $parts ? esc_html(implode(' / ', $parts)) : '—';
            return;
        }

        if ($column === 'bornado_guide_index') {
            echo bornado_geo_guide_is_indexable($post_id) ? 'index' : 'noindex';
        }
    }
}
add_action('manage_' . 'bornado_geo_guide' . '_posts_custom_column', 'bornado_geo_guide_render_admin_columns', 10, 2);

if (!function_exists('bornado_geo_guide_admin_notice')) {
    /**
     * Short setup reminder on the CPT list screen.
     *
     * @return void
     */
    function bornado_geo_guide_admin_notice()
    {
        $screen = get_current_screen();
        if (!($screen instanceof WP_Screen) || $screen->id !== 'edit-' . bornado_geo_guide_post_type()) {
            return;
        }
        ?>
        <div class="notice notice-info">
            <p>
                <strong>ساختار URL:</strong>
                <code>/iranians/{کشور}/{شهر}/</code>
                — اول یک راهنمای کشور با اسلاگ <code>uk</code> بسازید (بدون شهر، noindex می‌ماند). بعد راهنمای شهر را با والد همان کشور و اسلاگ <code>london</code> بسازید.
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'bornado_geo_guide_admin_notice');

if (!function_exists('bornado_geo_guide_parent_permalink_notice')) {
    /**
     * Warn when a city guide is missing its country parent.
     *
     * @return void
     */
    function bornado_geo_guide_parent_permalink_notice()
    {
        $screen = get_current_screen();
        if (!($screen instanceof WP_Screen) || $screen->id !== bornado_geo_guide_post_type()) {
            return;
        }

        $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        if ($post_id < 1 || !bornado_geo_guide_is_city_guide($post_id)) {
            return;
        }

        $post = get_post($post_id);
        if (!($post instanceof WP_Post) || (int) $post->post_parent > 0) {
            return;
        }
        ?>
        <div class="notice notice-warning">
            <p>
                این راهنمای شهر والد کشور ندارد. در جعبه «ساختار URL» کشور والد را انتخاب کنید تا آدرس
                <code>/iranians/uk/london/</code> ساخته شود، نه <code>/iranians/london/</code>.
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'bornado_geo_guide_parent_permalink_notice');

if (!function_exists('bornado_geo_guide_get_editor_post_id')) {
    /**
     * Current guide ID on the frontend or Rank Math editor screen.
     *
     * @return int
     */
    function bornado_geo_guide_get_editor_post_id()
    {
        $post_id = get_queried_object_id();
        if ($post_id > 0) {
            return (int) $post_id;
        }

        $post_id = get_the_ID();
        if ($post_id > 0) {
            return (int) $post_id;
        }

        return isset($_GET['post']) ? (int) $_GET['post'] : 0;
    }
}

if (!function_exists('bornado_geo_guide_get_location_term_name')) {
    /**
     * Public location label stored on the guide (city or country term name).
     *
     * @param string $field country_term_id or city_term_id.
     * @return string
     */
    function bornado_geo_guide_get_location_term_name($field)
    {
        if (!function_exists('bornado_geo_guide_get_meta')) {
            return '';
        }

        $post_id = bornado_geo_guide_get_editor_post_id();
        if ($post_id < 1 || !bornado_geo_guide_is_guide_post($post_id)) {
            return '';
        }

        $term_id = (int) bornado_geo_guide_get_meta($post_id, $field, 0);
        if ($term_id < 1) {
            return '';
        }

        $term = get_term($term_id, 'ad_country');

        return $term instanceof WP_Term ? (string) $term->name : '';
    }
}

if (!function_exists('bornado_geo_guide_get_rank_math_city')) {
    /**
     * Rank Math replacement for %geo_city%.
     *
     * @return string
     */
    function bornado_geo_guide_get_rank_math_city()
    {
        return bornado_geo_guide_get_location_term_name('city_term_id');
    }
}

if (!function_exists('bornado_geo_guide_get_rank_math_country')) {
    /**
     * Rank Math replacement for %geo_country%.
     *
     * @return string
     */
    function bornado_geo_guide_get_rank_math_country()
    {
        return bornado_geo_guide_get_location_term_name('country_term_id');
    }
}

if (!function_exists('bornado_geo_guide_register_rank_math_vars')) {
    /**
     * Expose city/country names as Rank Math title variables.
     *
     * @return void
     */
    function bornado_geo_guide_register_rank_math_vars()
    {
        if (!function_exists('rank_math_register_var_replacement')) {
            return;
        }

        rank_math_register_var_replacement(
            'geo_city',
            array(
                'name'        => 'نام شهر راهنما',
                'description' => 'نام شهر انتخاب‌شده در راهنمای ایرانیان',
                'variable'    => 'geo_city',
                'example'     => 'لندن',
            ),
            'bornado_geo_guide_get_rank_math_city'
        );

        rank_math_register_var_replacement(
            'geo_country',
            array(
                'name'        => 'نام کشور راهنما',
                'description' => 'نام کشور انتخاب‌شده در راهنمای ایرانیان',
                'variable'    => 'geo_country',
                'example'     => 'بریتانیا',
            ),
            'bornado_geo_guide_get_rank_math_country'
        );
    }
}
add_action('rank_math/vars/register_extra_replacements', 'bornado_geo_guide_register_rank_math_vars');
