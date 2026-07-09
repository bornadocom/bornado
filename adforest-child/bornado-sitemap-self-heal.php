<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_get_sitemap_self_heal_request_path')) {
    /**
     * Normalize the current request path relative to the site's home path.
     *
     * @return string
     */
    function bornado_get_sitemap_self_heal_request_path()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_uri = is_string($request_uri) ? $request_uri : '';
        if ($request_uri === '') {
            return '';
        }

        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';
        $path = trim($path, '/');

        $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
        $home_path = trim(is_string($home_path) ? $home_path : '', '/');

        if ($home_path !== '') {
            if ($path === $home_path) {
                return '';
            }

            $prefix = $home_path . '/';
            if (strpos($path, $prefix) === 0) {
                $path = substr($path, strlen($prefix));
            }
        }

        return trim((string) $path, '/');
    }
}

if (!function_exists('bornado_is_rank_math_sitemap_request_path')) {
    /**
     * Check whether a relative request path targets a Rank Math sitemap asset.
     *
     * @param string $path Relative request path.
     * @return bool
     */
    function bornado_is_rank_math_sitemap_request_path($path)
    {
        $path = trim((string) $path, '/');
        if ($path === '') {
            return false;
        }

        return (bool) preg_match(
            '#^(?:sitemap(?:_index)?\.xml|[^/]+?-sitemap(?:[0-9]+)?\.xml|(?:[a-z]+-)?sitemap\.xsl)$#i',
            $path
        );
    }
}

if (!function_exists('bornado_is_sitemap_self_heal_request')) {
    /**
     * True when the current request is a front-end sitemap request we can heal.
     *
     * @return bool
     */
    function bornado_is_sitemap_self_heal_request()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || wp_is_json_request()
        ) {
            return false;
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) wp_unslash($_SERVER['REQUEST_METHOD'])) : 'GET';
        if (!in_array($method, array('GET', 'HEAD'), true)) {
            return false;
        }

        return bornado_is_rank_math_sitemap_request_path(bornado_get_sitemap_self_heal_request_path());
    }
}

if (!function_exists('bornado_has_rank_math_sitemap_rewrite_rules')) {
    /**
     * Detect whether the stored rewrite rules still include Rank Math sitemap routes.
     *
     * @return bool
     */
    function bornado_has_rank_math_sitemap_rewrite_rules()
    {
        $rules = get_option('rewrite_rules');
        if (!is_array($rules) || empty($rules)) {
            return false;
        }

        $has_index_rule = false;
        $has_xml_rule = false;
        $has_xsl_rule = false;

        foreach (array_keys($rules) as $rule) {
            $rule = (string) $rule;
            if ($rule === '' || stripos($rule, 'sitemap') === false) {
                continue;
            }

            if (!$has_index_rule && preg_match('/sitemap_index\\\\\.xml\$$/i', $rule)) {
                $has_index_rule = true;
            }

            if (!$has_xml_rule && preg_match('/-sitemap(?:\(.+\))?\\\\\.xml\$$/i', $rule)) {
                $has_xml_rule = true;
            }

            if (!$has_xsl_rule && preg_match('/sitemap\\\\\.xsl\$$/i', $rule)) {
                $has_xsl_rule = true;
            }

            if ($has_index_rule && $has_xml_rule && $has_xsl_rule) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_can_attempt_sitemap_rewrite_heal')) {
    /**
     * Gate sitemap rewrite healing so it only runs when the setup supports it.
     *
     * @return bool
     */
    function bornado_can_attempt_sitemap_rewrite_heal()
    {
        if (!class_exists('\RankMath\Sitemap\Router')) {
            return false;
        }

        return (string) get_option('permalink_structure') !== '';
    }
}

if (!function_exists('bornado_try_heal_sitemap_rewrite_rules')) {
    /**
     * Flush sitemap rewrite rules at a throttled cadence.
     *
     * @param string $reason Short reason label.
     * @return bool True when a flush was attempted.
     */
    function bornado_try_heal_sitemap_rewrite_rules($reason)
    {
        if (!bornado_can_attempt_sitemap_rewrite_heal()) {
            return false;
        }

        $reason = sanitize_key((string) $reason);
        $now = time();
        $last_attempt = (int) get_option('bornado_sitemap_self_heal_last_attempt', 0);

        if ($last_attempt > 0 && ($now - $last_attempt) < (10 * MINUTE_IN_SECONDS)) {
            return false;
        }

        update_option('bornado_sitemap_self_heal_last_attempt', $now, false);
        update_option('bornado_sitemap_self_heal_last_reason', $reason, false);

        // Soft flush is enough here because the failure pattern is missing WP rewrite rules,
        // not a missing web-server rewrite block for the whole site.
        flush_rewrite_rules(false);

        return true;
    }
}

if (!function_exists('bornado_maybe_proactively_heal_sitemap_rewrite_rules')) {
    /**
     * Periodically verify that sitemap rewrites still exist and rebuild them if not.
     *
     * @return void
     */
    function bornado_maybe_proactively_heal_sitemap_rewrite_rules()
    {
        if (
            !bornado_can_attempt_sitemap_rewrite_heal()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || wp_is_json_request()
        ) {
            return;
        }

        $now = time();
        $last_check = (int) get_option('bornado_sitemap_self_heal_last_check', 0);
        if ($last_check > 0 && ($now - $last_check) < (6 * HOUR_IN_SECONDS)) {
            return;
        }

        update_option('bornado_sitemap_self_heal_last_check', $now, false);

        if (!bornado_has_rank_math_sitemap_rewrite_rules()) {
            bornado_try_heal_sitemap_rewrite_rules('missing_rules');
        }
    }
}
add_action('init', 'bornado_maybe_proactively_heal_sitemap_rewrite_rules', 50);

if (!function_exists('bornado_maybe_self_heal_current_sitemap_request')) {
    /**
     * If a sitemap URL resolves to 404, rebuild the rewrites and replay the request.
     *
     * @return void
     */
    function bornado_maybe_self_heal_current_sitemap_request()
    {
        if (!bornado_is_sitemap_self_heal_request()) {
            return;
        }

        global $wp_query;

        if ($wp_query instanceof WP_Query && !$wp_query->is_404) {
            return;
        }

        if (!bornado_try_heal_sitemap_rewrite_rules('404_request')) {
            return;
        }

        $path = bornado_get_sitemap_self_heal_request_path();
        if ($path === '') {
            return;
        }

        wp_safe_redirect(home_url('/' . ltrim($path, '/')), 302, 'Bornado Sitemap Self Heal');
        exit;
    }
}
add_action('template_redirect', 'bornado_maybe_self_heal_current_sitemap_request', -20);
