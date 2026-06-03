<?php
/**
 * Reusable AdForest sort dropdown widget and shortcode.
 *
 * Keeps parent theme files untouched while exposing the existing sort logic
 * as a classic widget and a shortcode-friendly component.
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_sort_filters_get_options')) {
    /**
     * Return supported AdForest sort options.
     *
     * @return array<string,string>
     */
    function bornado_sort_filters_get_options()
    {
        return array(
            'id-desc'    => __('Newest To Oldest', 'adforest'),
            'id-asc'     => __('Oldest To Newest', 'adforest'),
            'featured'   => __('Featured', 'adforest'),
            'price-desc' => __('Price: High to Low', 'adforest'),
            'price-asc'  => __('Price: Low to High', 'adforest'),
        );
    }
}

if (!function_exists('bornado_sort_filters_get_selected_value')) {
    /**
     * Resolve the current sort selection from the query string.
     *
     * @return string
     */
    function bornado_sort_filters_get_selected_value()
    {
        $options = bornado_sort_filters_get_options();

        if (isset($_GET['sort'])) {
            $selected = sanitize_text_field(wp_unslash((string) $_GET['sort']));
            if (isset($options[$selected])) {
                return $selected;
            }
        }

        if (isset($_GET['ad']) && '1' === (string) wp_unslash($_GET['ad'])) {
            return 'featured';
        }

        return 'id-desc';
    }
}

if (!function_exists('bornado_sort_filters_get_action_url')) {
    /**
     * Resolve the AdForest search page URL used by the sort form.
     *
     * @return string
     */
    function bornado_sort_filters_get_action_url()
    {
        global $adforest_theme;

        $page_id = function_exists('bornado_get_search_page_id') ? bornado_get_search_page_id() : 0;
        if ($page_id < 1 && isset($adforest_theme['sb_search_page'])) {
            $page_id = (int) $adforest_theme['sb_search_page'];
        }

        if ($page_id > 0) {
            $page_id = (int) apply_filters('adforest_language_page_id', $page_id);
            $url = get_the_permalink($page_id);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return home_url('/');
    }
}

if (!function_exists('bornado_sort_filters_get_lang_field')) {
    /**
     * Return the optional multilingual hidden field for search forms.
     *
     * @return string
     */
    function bornado_sort_filters_get_lang_field()
    {
        $lang_field = apply_filters('adforest_form_lang_field', false);
        return is_string($lang_field) ? $lang_field : '';
    }
}

if (!function_exists('bornado_sort_filters_enqueue_assets')) {
    /**
     * Enqueue widget assets once per request.
     *
     * @return void
     */
    function bornado_sort_filters_enqueue_assets()
    {
        static $enqueued = false;

        if ($enqueued || is_admin() || wp_is_json_request()) {
            return;
        }

        $enqueued = true;

        $css = '
        .bornado-sort-widget__form {
            margin: 0;
        }
        .bornado-sort-widget__select,
        .bornado-sort-widget .select2-container {
            width: 100% !important;
        }
        .bornado-sort-widget__noscript {
            margin-top: 10px;
        }
        .bornado-sort-widget--accordion .accordion-body .form-group:last-child {
            margin-bottom: 0;
        }
        @media (max-width: 991.98px) {
            .bornado-sort-widget--accordion .accordion-item {
                border: 0;
                border-radius: 20px;
                background: #ffffff;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
                overflow: hidden;
            }
            .bornado-sort-widget--accordion .accordion-button {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                width: 100%;
                padding: 16px 18px;
                border: 0;
                background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
                font-size: 14px;
                font-weight: 800;
                line-height: 1.5;
                color: #101828;
                box-shadow: none;
            }
            .bornado-sort-widget--accordion .accordion-button:not(.collapsed) {
                color: #0f172a;
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
                box-shadow: inset 0 -1px 0 #edf1f6;
            }
            .bornado-sort-widget--accordion .accordion-body {
                padding: 14px 16px 16px !important;
                background: #ffffff;
            }
            .bornado-sort-widget--accordion .bornado-sort-widget__select {
                min-height: 52px;
                border: 1px solid #dbe3ef;
                border-radius: 16px;
                background: #f8fafc;
                color: #101828;
                font-size: 14px;
                box-shadow: none;
            }
        }';

        wp_register_style('bornado-sort-filters-widget', false);
        wp_enqueue_style('bornado-sort-filters-widget');
        wp_add_inline_style('bornado-sort-filters-widget', $css);

        $js = "
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[data-bornado-sort-widget=\"1\"] select[name=\"sort\"]').forEach(function (select) {
                select.addEventListener('change', function () {
                    if (select.form) {
                        select.form.submit();
                    }
                });
            });

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery('form[data-bornado-sort-widget=\"1\"] select[name=\"sort\"]').each(function () {
                    var $select = window.jQuery(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        return;
                    }

                    $select.select2({
                        width: '100%'
                    });

                    $select.on('select2:select', function () {
                        if (this.form) {
                            this.form.submit();
                        }
                    });
                });
            }
        });
        ";

        wp_register_script('bornado-sort-filters-widget', '', array(), null, true);
        wp_enqueue_script('bornado-sort-filters-widget');
        wp_add_inline_script('bornado-sort-filters-widget', $js);
    }
}

if (!function_exists('bornado_render_sort_filters')) {
    /**
     * Render the reusable sort dropdown.
     *
     * @param array<string,mixed> $args Render options.
     * @return string
     */
    function bornado_render_sort_filters($args = array())
    {
        $defaults = array(
            'class_name'  => '',
            'select_id'   => '',
            'title'       => __('Sort Ads', 'adforest'),
            'open_widget' => false,
            'accordion'   => false,
        );

        $args = wp_parse_args(is_array($args) ? $args : array(), $defaults);

        bornado_sort_filters_enqueue_assets();

        $options       = bornado_sort_filters_get_options();
        $selected      = bornado_sort_filters_get_selected_value();
        $select_id     = is_string($args['select_id']) && $args['select_id'] !== '' ? $args['select_id'] : wp_unique_id('bornado-sort-filters-');
        $collapse_id   = wp_unique_id('bornado-sort-collapse-');
        $action_url    = bornado_sort_filters_get_action_url();
        $hidden_fields = function_exists('adforest_search_params') ? adforest_search_params('sort') : '';
        $lang_field    = bornado_sort_filters_get_lang_field();
        $accordion     = !empty($args['accordion']);
        $title         = is_string($args['title']) ? $args['title'] : '';
        $is_active     = isset($_GET['sort']) || (isset($_GET['ad']) && '1' === (string) wp_unslash($_GET['ad']));
        $expand        = (!empty($args['open_widget']) || $is_active) ? 'show' : '';
        $collapsed     = (!empty($args['open_widget']) || $is_active) ? '' : 'collapsed';
        $class_name    = trim(
            'bornado-sort-widget ' .
            ($accordion ? 'bornado-sort-widget--accordion ' : '') .
            (is_string($args['class_name']) ? $args['class_name'] : '')
        );

        ob_start();
        ?>
        <div class="<?php echo esc_attr($class_name); ?>">
            <?php if ($accordion) : ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button
                            class="accordion-button <?php echo esc_attr($collapsed); ?>"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?php echo esc_attr($collapse_id); ?>"
                            aria-expanded="<?php echo $collapsed === '' ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr($collapse_id); ?>"
                        >
                            <?php echo esc_html($title); ?>
                        </button>
                    </h2>
                    <div id="<?php echo esc_attr($collapse_id); ?>" class="accordion-collapse collapse <?php echo esc_attr($expand); ?>">
                        <div class="accordion-body">
            <?php endif; ?>

            <form class="bornado-sort-widget__form" method="get" action="<?php echo esc_url($action_url); ?>" data-bornado-sort-widget="1">
                <div class="form-group">
                    <select id="<?php echo esc_attr($select_id); ?>" name="sort" class="form-control default-select bornado-sort-widget__select">
                        <?php foreach ($options as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($selected, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php echo $hidden_fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $lang_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <noscript>
                    <button type="submit" class="adt-button-dark bornado-sort-widget__noscript">
                        <?php esc_html_e('Apply', 'adforest'); ?>
                    </button>
                </noscript>
            </form>

            <?php if ($accordion) : ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}

if (!function_exists('bornado_sort_filters_shortcode')) {
    /**
     * Shortcode wrapper for the sort dropdown.
     *
     * @param array<string,mixed> $atts Shortcode attributes.
     * @return string
     */
    function bornado_sort_filters_shortcode($atts = array())
    {
        $atts = shortcode_atts(
            array(
                'class' => '',
                'id'    => '',
            ),
            is_array($atts) ? $atts : array(),
            'bornado_sort_filters'
        );

        return bornado_render_sort_filters(
            array(
                'class_name' => (string) $atts['class'],
                'select_id'  => (string) $atts['id'],
            )
        );
    }
}

if (!class_exists('Bornado_Sort_Filters_Widget') && class_exists('WP_Widget')) {
    /**
     * Classic widget exposing the reusable sort dropdown.
     */
    class Bornado_Sort_Filters_Widget extends WP_Widget
    {
        /**
         * Register widget metadata.
         */
        public function __construct()
        {
            parent::__construct(
                'bornado_sort_filters_widget',
                __('Bornado Sort Filters', 'adforest'),
                array(
                    'classname'   => 'bornado_sort_filters_widget',
                    'description' => __('Reusable AdForest sort dropdown for widget areas.', 'adforest'),
                )
            );
        }

        /**
         * Render the widget.
         *
         * @param array<string,mixed> $args Widget wrapper args.
         * @param array<string,mixed> $instance Saved settings.
         * @return void
         */
        public function widget($args, $instance)
        {
            $title = isset($instance['title']) ? (string) $instance['title'] : '';
            $open_widget = !empty($instance['open_widget']) && '1' === (string) $instance['open_widget'];

            echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo bornado_render_sort_filters(
                array(
                    'title'       => $title !== '' ? apply_filters('widget_title', $title) : __('Sort Ads', 'adforest'),
                    'open_widget' => $open_widget,
                    'accordion'   => true,
                )
            ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        /**
         * Render admin form.
         *
         * @param array<string,mixed> $instance Saved settings.
         * @return void
         */
        public function form($instance)
        {
            $title = isset($instance['title']) ? (string) $instance['title'] : __('مرتب سازی آگهی‌ها', 'adforest');
            $open_widget = !empty($instance['open_widget']) ? (string) $instance['open_widget'] : '';
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                    <?php esc_html_e('عنوان:', 'adforest'); ?>
                </label>
                <input
                    class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                    type="text"
                    value="<?php echo esc_attr($title); ?>"
                >
            </p>
            <p>
                <input
                    class="checkbox"
                    type="checkbox"
                    <?php checked($open_widget, '1'); ?>
                    id="<?php echo esc_attr($this->get_field_id('open_widget')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('open_widget')); ?>"
                    value="1"
                />
                <label for="<?php echo esc_attr($this->get_field_id('open_widget')); ?>">
                    <?php esc_html_e('Open Widget', 'adforest'); ?>
                </label>
            </p>
            <?php
        }

        /**
         * Sanitize widget settings.
         *
         * @param array<string,mixed> $new_instance New values.
         * @param array<string,mixed> $old_instance Previous values.
         * @return array<string,string>
         */
        public function update($new_instance, $old_instance)
        {
            unset($old_instance);

            return array(
                'title' => !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '',
                'open_widget' => !empty($new_instance['open_widget']) ? '1' : '',
            );
        }
    }
}

if (!function_exists('bornado_register_sort_filters_shortcode')) {
    /**
     * Register the shortcode API.
     *
     * @return void
     */
    function bornado_register_sort_filters_shortcode()
    {
        add_shortcode('bornado_sort_filters', 'bornado_sort_filters_shortcode');
    }
}

if (!function_exists('bornado_register_sort_filters_widget')) {
    /**
     * Register the classic widget API.
     *
     * @return void
     */
    function bornado_register_sort_filters_widget()
    {
        if (class_exists('WP_Widget') && class_exists('Bornado_Sort_Filters_Widget')) {
            register_widget('Bornado_Sort_Filters_Widget');
        }
    }
}

add_action('init', 'bornado_register_sort_filters_shortcode', 20);
add_action('widgets_init', 'bornado_register_sort_filters_widget', 20);
