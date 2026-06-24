<?php
/**
 * Make AdForest category-search widgets child-theme aware.
 *
 * The parent theme hardcodes get_template_directory() when loading widget
 * templates, which bypasses child-theme overrides. Re-register only the
 * category widgets with subclasses that resolve templates via locate_template().
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_category_widget_locate_template')) {
    /**
     * Locate a widget template with child-theme fallback.
     *
     * @param string $relative_path Relative template path from the theme root.
     * @return string
     */
    function bornado_category_widget_locate_template($relative_path)
    {
        $relative_path = ltrim((string) $relative_path, '/\\');
        $located = locate_template(array($relative_path), false, false);

        if (is_string($located) && $located !== '') {
            return $located;
        }

        return trailingslashit(get_template_directory()) . $relative_path;
    }
}

if (class_exists('Adforest_Category_Search_Widget') && !class_exists('Bornado_Category_Search_Widget')) {
    class Bornado_Category_Search_Widget extends Adforest_Category_Search_Widget
    {
        public function widget($args, $instance)
        {
            echo wp_kses_post($args['before_widget']);
            $widget_layout = adforest_search_layout();
            $template_path = bornado_category_widget_locate_template('template-parts/layouts/widgets/' . $widget_layout . '/categories.php');

            require $template_path;

            echo wp_kses_post($args['after_widget']);
        }
    }
}

if (class_exists('Adforest_Category_Select_Search_Widget') && !class_exists('Bornado_Category_Select_Search_Widget')) {
    class Bornado_Category_Select_Search_Widget extends Adforest_Category_Select_Search_Widget
    {
        public function widget($args, $instance)
        {
            echo wp_kses_post($args['before_widget']);
            $widget_layout = adforest_search_layout();
            $template_path = bornado_category_widget_locate_template('template-parts/layouts/widgets/' . $widget_layout . '/categories-select.php');

            require $template_path;

            echo wp_kses_post($args['after_widget']);
        }
    }
}

if (!function_exists('bornado_register_child_category_widgets')) {
    /**
     * Replace the parent widget registrations with child-aware subclasses.
     *
     * @return void
     */
    function bornado_register_child_category_widgets()
    {
        if (!class_exists('Bornado_Category_Search_Widget') || !class_exists('Bornado_Category_Select_Search_Widget')) {
            return;
        }

        unregister_widget('Adforest_Category_Search_Widget');
        unregister_widget('Adforest_Category_Select_Search_Widget');

        register_widget('Bornado_Category_Search_Widget');
        register_widget('Bornado_Category_Select_Search_Widget');
    }
}
add_action('widgets_init', 'bornado_register_child_category_widgets', 100);
