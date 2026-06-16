<?php
/**
 * Bornado reusable wheel picker bootstrap.
 *
 * Implementation notes and usage examples live in `docs/wheel-picker.md`.
 *
 * @package Bornado_Child
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Bornado_Wheel_Picker')) {
    final class Bornado_Wheel_Picker
    {
        const VERSION = '1.0.0';
        const STYLE_HANDLE = 'bornado-wheel-picker';
        const SCRIPT_HANDLE = 'bornado-wheel-picker';

        /**
         * Whether the front-end defaults have already been localized.
         *
         * @var bool
         */
        private static $localized = false;

        /**
         * Register module hooks.
         *
         * @return void
         */
        public static function init()
        {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'), 20);
        }

        /**
         * Register front-end assets.
         *
         * @return void
         */
        public static function register_assets()
        {
            if (is_admin() || (function_exists('wp_is_json_request') && wp_is_json_request())) {
                return;
            }

            $style_rel = '/assets/css/bornado-wheel-picker.css';
            $style_abs = get_stylesheet_directory() . $style_rel;
            $style_ver = file_exists($style_abs) ? (string) filemtime($style_abs) : self::VERSION;
            wp_register_style(
                self::STYLE_HANDLE,
                get_stylesheet_directory_uri() . $style_rel,
                array(),
                $style_ver
            );

            $script_rel = '/assets/js/bornado-wheel-picker.js';
            $script_abs = get_stylesheet_directory() . $script_rel;
            $script_ver = file_exists($script_abs) ? (string) filemtime($script_abs) : self::VERSION;
            wp_register_script(
                self::SCRIPT_HANDLE,
                get_stylesheet_directory_uri() . $script_rel,
                array(),
                $script_ver,
                true
            );
        }

        /**
         * Ensure picker assets are available on this request.
         *
         * @return void
         */
        public static function enqueue_assets()
        {
            if (is_admin() || (function_exists('wp_is_json_request') && wp_is_json_request())) {
                return;
            }

            self::register_assets();

            wp_enqueue_style(self::STYLE_HANDLE);
            wp_enqueue_script(self::SCRIPT_HANDLE);

            if (self::$localized) {
                return;
            }

            wp_localize_script(
                self::SCRIPT_HANDLE,
                'BornadoWheelPickerConfig',
                array(
                    'defaults' => self::get_default_frontend_config(),
                )
            );

            self::$localized = true;
        }

        /**
         * Render one picker root element.
         *
         * @param array<string,mixed> $args Picker options.
         * @return string
         */
        public static function render($args = array())
        {
            self::enqueue_assets();

            $defaults = array(
                'id' => '',
                'class_name' => '',
                'type' => 'date',
                'variant' => 'date-modal',
                'hidden' => true,
                'rtl' => is_rtl(),
                'title' => __('انتخاب تاریخ', 'adforest'),
                'eyebrow' => __('Wheel Picker', 'adforest'),
                'confirm_text' => __('تایید', 'adforest'),
                'cancel_text' => __('انصراف', 'adforest'),
                'show_output' => false,
                'preview_format' => 'YYYY-MM-DD',
                'output_format' => 'YYYY-MM-DD',
                'min_year' => 1930,
                'max_year' => (int) gmdate('Y') + 10,
                'column_order' => array('year', 'month', 'day'),
                'labels' => array(
                    'year' => __('سال', 'adforest'),
                    'month' => __('ماه', 'adforest'),
                    'day' => __('روز', 'adforest'),
                ),
                'months' => self::get_default_months(),
            );

            $config = wp_parse_args(is_array($args) ? $args : array(), $defaults);
            $root_id = self::sanitize_html_identifier($config['id']);
            if ('' === $root_id) {
                $root_id = wp_unique_id('bornado-wheel-picker-');
            }

            $class_tokens = array(
                'bornado-wheel-picker',
                'bornado-wheel-picker--' . sanitize_html_class((string) $config['variant']),
            );
            if (!empty($config['rtl'])) {
                $class_tokens[] = 'is-rtl';
            }

            $custom_classes = preg_split('/\s+/', (string) $config['class_name']);
            if (is_array($custom_classes)) {
                foreach ($custom_classes as $class_name) {
                    $class_name = sanitize_html_class($class_name);
                    if ('' !== $class_name) {
                        $class_tokens[] = $class_name;
                    }
                }
            }

            $is_hidden = !empty($config['hidden']);
            unset($config['id'], $config['class_name'], $config['hidden']);

            $frontend_config = array(
                'type' => (string) $config['type'],
                'variant' => (string) $config['variant'],
                'rtl' => !empty($config['rtl']),
                'title' => (string) $config['title'],
                'eyebrow' => (string) $config['eyebrow'],
                'confirmText' => (string) $config['confirm_text'],
                'cancelText' => (string) $config['cancel_text'],
                'closeText' => __('بستن', 'adforest'),
                'showOutput' => !empty($config['show_output']),
                'previewFormat' => (string) $config['preview_format'],
                'outputFormat' => (string) $config['output_format'],
                'minYear' => (int) $config['min_year'],
                'maxYear' => (int) $config['max_year'],
                'columnOrder' => is_array($config['column_order']) ? array_values($config['column_order']) : array('year', 'month', 'day'),
                'labels' => is_array($config['labels']) ? $config['labels'] : array(),
                'months' => is_array($config['months']) ? array_values($config['months']) : self::get_default_months(),
            );

            return sprintf(
                '<div id="%1$s" class="%2$s"%3$s dir="%4$s" data-bornado-wheel-picker-config="%5$s"></div>',
                esc_attr($root_id),
                esc_attr(implode(' ', array_unique($class_tokens))),
                $is_hidden ? ' hidden' : '',
                !empty($config['rtl']) ? 'rtl' : 'ltr',
                esc_attr(wp_json_encode($frontend_config))
            );
        }

        /**
         * Build the default front-end config localized into JS once.
         *
         * @return array<string,mixed>
         */
        private static function get_default_frontend_config()
        {
            return array(
                'rtl' => is_rtl(),
                'type' => 'date',
                'variant' => 'date-modal',
                'title' => __('انتخاب تاریخ', 'adforest'),
                'eyebrow' => __('Wheel Picker', 'adforest'),
                'confirmText' => __('تایید', 'adforest'),
                'cancelText' => __('انصراف', 'adforest'),
                'closeText' => __('بستن', 'adforest'),
                'showOutput' => false,
                'previewFormat' => 'YYYY-MM-DD',
                'outputFormat' => 'YYYY-MM-DD',
                'rowHeight' => 48,
                'visibleRows' => 5,
                'minYear' => 1930,
                'maxYear' => (int) gmdate('Y') + 10,
                'columnOrder' => array('year', 'month', 'day'),
                'labels' => array(
                    'year' => __('سال', 'adforest'),
                    'month' => __('ماه', 'adforest'),
                    'day' => __('روز', 'adforest'),
                ),
                'months' => self::get_default_months(),
            );
        }

        /**
         * Month labels used by the default date picker variant.
         *
         * @return array<int,array<string,string>>
         */
        private static function get_default_months()
        {
            return array(
                array('value' => '01', 'label' => __('January', 'adforest'), 'shortLabel' => __('Jan', 'adforest')),
                array('value' => '02', 'label' => __('February', 'adforest'), 'shortLabel' => __('Feb', 'adforest')),
                array('value' => '03', 'label' => __('March', 'adforest'), 'shortLabel' => __('Mar', 'adforest')),
                array('value' => '04', 'label' => __('April', 'adforest'), 'shortLabel' => __('Apr', 'adforest')),
                array('value' => '05', 'label' => __('May', 'adforest'), 'shortLabel' => __('May', 'adforest')),
                array('value' => '06', 'label' => __('June', 'adforest'), 'shortLabel' => __('Jun', 'adforest')),
                array('value' => '07', 'label' => __('July', 'adforest'), 'shortLabel' => __('Jul', 'adforest')),
                array('value' => '08', 'label' => __('August', 'adforest'), 'shortLabel' => __('Aug', 'adforest')),
                array('value' => '09', 'label' => __('September', 'adforest'), 'shortLabel' => __('Sep', 'adforest')),
                array('value' => '10', 'label' => __('October', 'adforest'), 'shortLabel' => __('Oct', 'adforest')),
                array('value' => '11', 'label' => __('November', 'adforest'), 'shortLabel' => __('Nov', 'adforest')),
                array('value' => '12', 'label' => __('December', 'adforest'), 'shortLabel' => __('Dec', 'adforest')),
            );
        }

        /**
         * Keep custom IDs safe for HTML output.
         *
         * @param mixed $value Raw identifier.
         * @return string
         */
        private static function sanitize_html_identifier($value)
        {
            $value = is_scalar($value) ? (string) $value : '';
            $value = preg_replace('/[^A-Za-z0-9\-_:.]+/', '-', $value);
            return is_string($value) ? trim($value, '-') : '';
        }
    }
}

if (!function_exists('bornado_wheel_picker_enqueue_assets')) {
    /**
     * Make picker assets available on the current request.
     *
     * @return void
     */
    function bornado_wheel_picker_enqueue_assets()
    {
        Bornado_Wheel_Picker::enqueue_assets();
    }
}

if (!function_exists('bornado_render_wheel_picker')) {
    /**
     * Return picker markup for theme templates.
     *
     * @param array<string,mixed> $args Picker options.
     * @return string
     */
    function bornado_render_wheel_picker($args = array())
    {
        return Bornado_Wheel_Picker::render(is_array($args) ? $args : array());
    }
}

if (!function_exists('bornado_wheel_picker')) {
    /**
     * Echo picker markup for theme templates.
     *
     * @param array<string,mixed> $args Picker options.
     * @return void
     */
    function bornado_wheel_picker($args = array())
    {
        echo bornado_render_wheel_picker($args); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

Bornado_Wheel_Picker::init();
