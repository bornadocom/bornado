<?php
/**
 * Hide AdForest search-sidebar widgets when the current category's synced
 * template marks matching default fields as Hidden (Show = 0).
 *
 * Covers:
 * - Native taxonomy archives (adforest_cat_search)
 * - BORNADO semantic category routes (page-search.php + adforest_search_sidebar)
 *
 * Child-theme only — does not modify parent theme files.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_category_search_sidebar_init')) {
    function bornado_category_search_sidebar_init()
    {
        add_filter('widget_display_callback', 'bornado_category_search_sidebar_filter_widget', 10, 3);
    }

    add_action('init', 'bornado_category_search_sidebar_init');
}

if (!function_exists('bornado_category_search_sidebar_allowed_sidebar_ids')) {
    /**
     * Sidebars that render AdForest listing filters.
     *
     * @return string[]
     */
    function bornado_category_search_sidebar_allowed_sidebar_ids()
    {
        $ids = array(
            'adforest_search_sidebar', // Theme label: "Ads Search" — used by page-search.php (BORNADO routes)
            'adforest_cat_search',     // Theme label: "Category Search - Sidebar" — taxonomy-ad_cats.php
        );

        return apply_filters('bornado_category_search_sidebar_ids', $ids);
    }
}

if (!function_exists('bornado_category_search_sidebar_filter_widget')) {
    /**
     * @param array|false $instance
     * @param WP_Widget   $widget
     * @param array       $args
     * @return array|false
     */
    function bornado_category_search_sidebar_filter_widget($instance, $widget, $args)
    {
        if (!bornado_category_search_sidebar_should_apply($args)) {
            return $instance;
        }

        $field_key = bornado_category_search_sidebar_resolve_field_key($widget);
        if ($field_key === '') {
            return $instance;
        }

        $show_flags = bornado_category_search_sidebar_get_show_flags();
        if ($show_flags === null) {
            return $instance;
        }

        if (!bornado_category_search_sidebar_is_field_shown($show_flags, $field_key)) {
            return false;
        }

        return $instance;
    }
}

if (!function_exists('bornado_category_search_sidebar_should_apply')) {
    function bornado_category_search_sidebar_should_apply($args)
    {
        if (bornado_category_search_sidebar_get_term_id() <= 0) {
            return false;
        }

        $sidebar_id = isset($args['id']) ? (string) $args['id'] : '';
        if ($sidebar_id !== '' && in_array($sidebar_id, bornado_category_search_sidebar_allowed_sidebar_ids(), true)) {
            return true;
        }

        return is_tax('ad_cats');
    }
}

if (!function_exists('bornado_category_search_sidebar_get_term_id')) {
    function bornado_category_search_sidebar_get_term_id()
    {
        static $term_id = null;

        if ($term_id !== null) {
            return (int) $term_id;
        }

        $term_id = 0;

        if (is_tax('ad_cats')) {
            $term_id = (int) get_queried_object_id();
            return $term_id;
        }

        if (isset($_GET['cat_id']) && is_numeric($_GET['cat_id']) && (int) $_GET['cat_id'] > 0) {
            $term_id = (int) $_GET['cat_id'];
            return $term_id;
        }

        if (function_exists('bornado_seo_routing_get_context')) {
            $context = bornado_seo_routing_get_context();
            if (is_array($context)) {
                if (!empty($context['deepest_term']) && $context['deepest_term'] instanceof WP_Term) {
                    $term_id = (int) $context['deepest_term']->term_id;
                    return $term_id;
                }

                if (!empty($context['cat_id']) && is_numeric($context['cat_id'])) {
                    $term_id = (int) $context['cat_id'];
                    return $term_id;
                }
            }
        }

        return 0;
    }
}

if (!function_exists('bornado_category_search_sidebar_get_show_flags')) {
    /**
     * @return array<string, mixed>|null Null when no template is available.
     */
    function bornado_category_search_sidebar_get_show_flags()
    {
        static $loaded = false;
        static $flags = null;

        if ($loaded) {
            return $flags;
        }

        $loaded = true;

        $term_id = bornado_category_search_sidebar_get_term_id();
        if ($term_id <= 0 || !function_exists('adforest_dynamic_templateID')) {
            return $flags;
        }

        $template_id = adforest_dynamic_templateID($term_id);
        if (empty($template_id) || !function_exists('sb_custom_form_data')) {
            return $flags;
        }

        $encoded = get_term_meta((int) $template_id, '_sb_dynamic_form_fields', true);
        if ($encoded === '' || $encoded === false) {
            return $flags;
        }

        $meta_keys = array(
            'price'      => '_sb_default_cat_price_show',
            'price_type' => '_sb_default_cat_price_type_show',
            'condition'  => '_sb_default_cat_condition_show',
            'warranty'   => '_sb_default_cat_warranty_show',
            'ad_type'    => '_sb_default_cat_ad_type_show',
        );

        $meta_keys = apply_filters('bornado_category_search_sidebar_meta_keys', $meta_keys);

        $flags = array();
        foreach ($meta_keys as $field => $meta_key) {
            $flags[$field] = sb_custom_form_data($encoded, $meta_key);
        }

        $flags = apply_filters('bornado_category_search_sidebar_show_flags', $flags, $term_id, (int) $template_id);

        return $flags;
    }
}

if (!function_exists('bornado_category_search_sidebar_is_field_shown')) {
    function bornado_category_search_sidebar_is_field_shown(array $show_flags, $field_key)
    {
        if (!array_key_exists($field_key, $show_flags)) {
            return true;
        }

        $value = $show_flags[$field_key];

        return !in_array((string) $value, array('0', 'false', 'no', 'hide'), true);
    }
}

if (!function_exists('bornado_category_search_sidebar_resolve_field_key')) {
    function bornado_category_search_sidebar_resolve_field_key($widget)
    {
        if (!is_object($widget)) {
            return '';
        }

        $map = array(
            'adforest_condition_search_widget' => 'condition',
            'adforest_search_condition'        => 'condition',

            'adforest_price_search_widget' => 'price',
            'adforest_search_ad_price'     => 'price',

            'adforest_warranty_search_widget' => 'warranty',
            'adforest_search_ad_warranty'     => 'warranty',

            'adforest_ad_type_search_widget' => 'ad_type',
            'adforest_search_ad_type'        => 'ad_type',
        );

        $map = apply_filters('bornado_category_search_sidebar_widget_map', $map);

        $id_base = isset($widget->id_base) ? (string) $widget->id_base : '';
        if ($id_base !== '' && isset($map[$id_base])) {
            return (string) $map[$id_base];
        }

        $class_name = strtolower(get_class($widget));
        if ($class_name !== '' && isset($map[$class_name])) {
            return (string) $map[$class_name];
        }

        $classname = '';
        if (isset($widget->widget_options['classname'])) {
            $classname = (string) $widget->widget_options['classname'];
        }

        $classname_map = array(
            'adforest_search_conidtion'   => 'condition',
            'adforest_search_ad_price'    => 'price',
            'adforest_search_ad_warranty' => 'warranty',
            'adforest_search_ad_type'     => 'ad_type',
        );

        $classname_map = apply_filters('bornado_category_search_sidebar_classname_map', $classname_map);

        if ($classname !== '' && isset($classname_map[$classname])) {
            return (string) $classname_map[$classname];
        }

        return '';
    }
}
