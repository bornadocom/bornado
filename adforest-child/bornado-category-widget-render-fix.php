<?php
/**
 * Inline CSS fix for the sidebar category-search widget.
 *
 * We avoid touching plugin/core files and also avoid JS flicker by printing a
 * small dynamic style block in the document head. The CSS hides the duplicate
 * selected-category label and highlights the row that matches the current
 * `cat_id` query param.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_category_widget_render_fix_should_run')) {
    function bornado_category_widget_render_fix_should_run()
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST) || wp_is_json_request()) {
            return false;
        }

        return isset($_GET['cat_id']) && absint($_GET['cat_id']) > 0;
    }
}

if (!function_exists('bornado_print_category_widget_render_fix_css')) {
    function bornado_print_category_widget_render_fix_css()
    {
        if (!bornado_category_widget_render_fix_should_run()) {
            return;
        }

        $current_cat_id = isset($_GET['cat_id']) ? absint($_GET['cat_id']) : 0;
        if ($current_cat_id < 1) {
            return;
        }
        ?>
        <style id="bornado-category-widget-render-fix">
            .adt-ads-filter-sidebar .adt-category-list-sidebar {
                font-size: 0 !important;
                line-height: 0 !important;
            }

            .adt-ads-filter-sidebar .adt-category-list-sidebar > * {
                font-size: initial !important;
                line-height: normal !important;
            }

            .adt-ads-filter-sidebar .adt-category-list-sidebar > span:first-child,
            .adt-ads-filter-sidebar .adt-category-list-sidebar > label:first-child {
                display: none !important;
            }

            .adt-ads-filter-sidebar .adt-category-list-sidebar li.hidden-category:has(a.category_click_link[data-cat-id="<?php echo esc_attr($current_cat_id); ?>"]) {
                display: inline-block !important;
            }

            .adt-ads-filter-sidebar .adt-category-list-sidebar li:has(a.category_click_link[data-cat-id="<?php echo esc_attr($current_cat_id); ?>"]) > .adt-category-box {
                padding: 10px 12px !important;
                margin: -10px -12px !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            .adt-ads-filter-sidebar .adt-category-list-sidebar li:has(a.category_click_link[data-cat-id="<?php echo esc_attr($current_cat_id); ?>"]) .category-meta a,
            .adt-ads-filter-sidebar .adt-category-list-sidebar li:has(a.category_click_link[data-cat-id="<?php echo esc_attr($current_cat_id); ?>"]) .listing-count {
                color: #ff002e !important;
            }

            .adt-ads-filter-sidebar .adt-category-list-sidebar li:has(a.category_click_link[data-cat-id="<?php echo esc_attr($current_cat_id); ?>"]) .category-meta .img-box {
                background-color: #fff !important;
                border-color: currentColor !important;
            }

            .adt-ads-filter-sidebar .adt-category-list-sidebar li:has(a.category_click_link[data-cat-id="<?php echo esc_attr($current_cat_id); ?>"]) .listing-count {
                background-color: #fff !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'bornado_print_category_widget_render_fix_css', 999);

if (!function_exists('bornado_category_widget_render_fix_strip_leading_label')) {
    function bornado_category_widget_render_fix_strip_leading_label($html)
    {
        if (!is_string($html) || $html === '' || strpos($html, 'adt-category-list-sidebar') === false) {
            return $html;
        }

        $pattern = '~(<div\b[^>]*class="[^"]*\badt-category-list-sidebar\b[^"]*"[^>]*>)(.*?)(<ul\b)~isu';

        return preg_replace_callback($pattern, static function ($matches) {
            $between = isset($matches[2]) ? (string) $matches[2] : '';

            // Remove only plain text/comment content that sits directly above the list.
            if (preg_match('~^\s*(?:<!--.*?-->\s*)*[^<]*$~su', $between)) {
                return $matches[1] . $matches[3];
            }

            return $matches[0];
        }, $html);
    }
}

if (!function_exists('bornado_start_category_widget_render_fix_buffer')) {
    function bornado_start_category_widget_render_fix_buffer()
    {
        if (!bornado_category_widget_render_fix_should_run()) {
            return;
        }

        ob_start('bornado_category_widget_render_fix_strip_leading_label');
    }
}
add_action('template_redirect', 'bornado_start_category_widget_render_fix_buffer', 0);
