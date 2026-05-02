<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_search_compat_build_query_args')) {
    /**
     * Build sanitized query args for search forms when Search Core is available.
     *
     * @param array<int,string> $excluded_keys Keys to exclude.
     * @return array<string,mixed>
     */
    function bornado_search_compat_build_query_args($excluded_keys = array())
    {
        if (function_exists('bornado_search_get_current_query_args')) {
            return bornado_search_get_current_query_args($excluded_keys);
        }

        $source = isset($_GET) ? wp_unslash($_GET) : array();
        if (!is_array($source)) {
            return array();
        }

        $clean = array();
        foreach ($source as $key => $value) {
            $key = is_string($key) ? trim($key) : '';
            if ($key === '' || in_array($key, $excluded_keys, true)) {
                continue;
            }

            if (in_array($key, array('ad_cat_sub', 'ad_cat_sub_sub', 'ad_cat_sub_sub_sub', 'ad_cat_sub_sub_sub_sub'), true)) {
                continue;
            }

            if (is_array($value)) {
                $normalized = array();
                foreach ($value as $child_key => $child_value) {
                    if (is_array($child_value)) {
                        $child_value = array_map('sanitize_text_field', array_map('strval', $child_value));
                        $child_value = array_values(array_filter($child_value, function ($item) {
                            return trim((string) $item) !== '';
                        }));
                    } else {
                        $child_value = trim(sanitize_text_field((string) $child_value));
                    }

                    if ($child_value === '' || $child_value === array()) {
                        continue;
                    }

                    $normalized[$child_key] = $child_value;
                }

                if (!empty($normalized)) {
                    $clean[$key] = $normalized;
                }
                continue;
            }

            if (is_bool($value) || is_numeric($value)) {
                $clean[$key] = $value;
                continue;
            }

            $value = trim(sanitize_text_field((string) $value));
            if ($value !== '') {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}

if (!function_exists('bornado_search_compat_render_hidden_query_fields')) {
    /**
     * Render sanitized hidden query inputs.
     *
     * @param array<string,mixed> $query_args Query args.
     * @return string
     */
    function bornado_search_compat_render_hidden_query_fields($query_args)
    {
        if (function_exists('bornado_search_render_hidden_query_fields')) {
            return bornado_search_render_hidden_query_fields($query_args);
        }

        $output = '';
        $walker = function ($values, $prefix = '') use (&$walker, &$output) {
            if (!is_array($values)) {
                return;
            }

            foreach ($values as $key => $value) {
                $field_name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
                if (is_array($value)) {
                    $walker($value, $field_name);
                    continue;
                }

                $output .= sprintf(
                    '<input type="hidden" name="%1$s" value="%2$s" />',
                    esc_attr($field_name),
                    esc_attr((string) $value)
                );
            }
        };

        $walker($query_args);
        return $output;
    }
}

if (!function_exists('adforest_search_params')) {
    /**
     * Child-theme override that keeps AdForest hidden search params aligned with Search Core.
     *
     * @param string   $index      Primary key to exclude.
     * @param string   $second     Optional second key to exclude.
     * @param string   $third      Optional third key to exclude.
     * @param bool     $search_url Whether to return the search URL fallback.
     * @return string
     */
    function adforest_search_params($index, $second = '', $third = '', $search_url = false)
    {
        global $adforest_theme;

        $excluded_keys = array_filter(
            array(
                $index,
                $second,
                $third,
                'ad_cat_sub',
                'ad_cat_sub_sub',
                'ad_cat_sub_sub_sub',
                'ad_cat_sub_sub_sub_sub',
            )
        );

        $query_args = bornado_search_compat_build_query_args($excluded_keys);
        if (!empty($query_args)) {
            return bornado_search_compat_render_hidden_query_fields($query_args);
        }

        if ($search_url && !empty($adforest_theme['sb_search_page'])) {
            $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
            return (string) get_the_permalink($sb_search_page);
        }

        return '';
    }
}
