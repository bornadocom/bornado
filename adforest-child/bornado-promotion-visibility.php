<?php

if (!function_exists('bornado_theme_option_is_truthy')) {
    /**
     * Normalize Redux-style option values to booleans.
     *
     * @param mixed $value Raw option value.
     * @return bool
     */
    function bornado_theme_option_is_truthy($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), array('1', 'true', 'yes', 'on'), true);
        }

        return !empty($value);
    }
}

if (!function_exists('bornado_get_promotion_visibility_flags')) {
    /**
     * Resolve feature/bump visibility from the current theme settings.
     *
     * The parent theme exposes a direct switch for bump only via the paid bump
     * option, while featured ads also have a dedicated "allow on ad" switch.
     * These values become the child theme's master visibility toggles so the
     * UI automatically returns when the site owner re-enables them later.
     *
     * @return array<string,bool>
     */
    function bornado_get_promotion_visibility_flags()
    {
        global $adforest_theme;

        $flags = array(
            'feature' => bornado_theme_option_is_truthy($adforest_theme['allow_featured_on_ad'] ?? false)
                || bornado_theme_option_is_truthy($adforest_theme['make_feature_paid'] ?? false),
            'bump' => bornado_theme_option_is_truthy($adforest_theme['make_bump_up_paid'] ?? false),
        );

        return apply_filters('bornado_promotion_visibility_flags', $flags, $adforest_theme);
    }
}

if (!function_exists('bornado_is_promotion_enabled')) {
    /**
     * Check whether a promotion capability should be visible/usable.
     *
     * @param string $type Supported values: feature, bump, any.
     * @return bool
     */
    function bornado_is_promotion_enabled($type = 'any')
    {
        $flags = bornado_get_promotion_visibility_flags();

        switch ($type) {
            case 'feature':
                $enabled = !empty($flags['feature']);
                break;

            case 'bump':
                $enabled = !empty($flags['bump']);
                break;

            case 'any':
            default:
                $enabled = !empty($flags['feature']) || !empty($flags['bump']);
                break;
        }

        return (bool) apply_filters('bornado_is_promotion_enabled', $enabled, $type, $flags);
    }
}

if (!function_exists('bornado_get_promotion_disabled_message')) {
    /**
     * User-facing copy when a disabled promotion action is requested.
     *
     * @param string $type Promotion type.
     * @return string
     */
    function bornado_get_promotion_disabled_message($type)
    {
        if ($type === 'bump') {
            return esc_html__('Bump up is currently unavailable.', 'adforest');
        }

        if ($type === 'feature') {
            return esc_html__('Featured ads are currently unavailable.', 'adforest');
        }

        return esc_html__('This promotion option is currently unavailable.', 'adforest');
    }
}

if (!function_exists('bornado_get_dom_inner_html')) {
    /**
     * Return the inner HTML for a DOM node.
     *
     * @param DOMDocument $dom Parsed document.
     * @param DOMNode     $node Root node.
     * @return string
     */
    function bornado_get_dom_inner_html($dom, $node)
    {
        $html = '';

        foreach ($node->childNodes as $child_node) {
            $html .= $dom->saveHTML($child_node);
        }

        return $html;
    }
}

if (!function_exists('bornado_remove_nodes_by_xpath')) {
    /**
     * Remove all nodes matched by one or more XPath selectors.
     *
     * @param DOMXPath     $xpath   XPath helper.
     * @param array<int,string> $queries XPath expressions.
     * @return void
     */
    function bornado_remove_nodes_by_xpath($xpath, array $queries)
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (!$nodes) {
                continue;
            }

            $removals = array();
            foreach ($nodes as $node) {
                if ($node instanceof DOMNode) {
                    $removals[] = $node;
                }
            }

            foreach ($removals as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }
}

if (!function_exists('bornado_filter_dashboard_promotion_markup')) {
    /**
     * Remove disabled promotion controls from dashboard markup.
     *
     * @param string $html Raw dashboard HTML.
     * @return string
     */
    function bornado_filter_dashboard_promotion_markup($html)
    {
        if (!is_string($html) || $html === '' || !class_exists('DOMDocument')) {
            return $html;
        }

        $hide_feature = !bornado_is_promotion_enabled('feature');
        $hide_bump = !bornado_is_promotion_enabled('bump');

        if (!$hide_feature && !$hide_bump) {
            return $html;
        }

        $previous_libxml_state = libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="bornado-dashboard-promotion-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous_libxml_state);
            return $html;
        }

        $xpath = new DOMXPath($dom);

        if ($hide_feature) {
            bornado_remove_nodes_by_xpath($xpath, array(
                "//a[contains(concat(' ', normalize-space(@class), ' '), ' sb_make_feature_ad_new_pkg ')]",
                "//span[contains(concat(' ', normalize-space(@class), ' '), ' non-clickable-featured ')]",
                "//ul[@id='ddmenu_2']/li[a[contains(@href, 'page_type=feature_ads')]]",
            ));
        }

        if ($hide_bump) {
            bornado_remove_nodes_by_xpath($xpath, array(
                "//a[contains(concat(' ', normalize-space(@class), ' '), ' bump_it_up_new_pkg ')]",
                "//a[contains(concat(' ', normalize-space(@class), ' '), ' bump_it_up ')]",
            ));
        }

        $root = $dom->getElementById('bornado-dashboard-promotion-root');
        $result = $root instanceof DOMElement ? bornado_get_dom_inner_html($dom, $root) : $html;

        libxml_clear_errors();
        libxml_use_internal_errors($previous_libxml_state);

        return $result;
    }
}

if (!function_exists('bornado_filter_my_listings_promotion_markup')) {
    /**
     * Remove disabled promotion controls from the modern My Listings page.
     *
     * @param string $html Raw page HTML.
     * @return string
     */
    function bornado_filter_my_listings_promotion_markup($html)
    {
        if (!is_string($html) || $html === '') {
            return $html;
        }

        $hide_feature = !bornado_is_promotion_enabled('feature');
        $hide_bump = !bornado_is_promotion_enabled('bump');

        if (!$hide_feature && !$hide_bump) {
            return $html;
        }

        if ($hide_feature) {
            $html = preg_replace(
                '#<a\b[^>]*class="[^"]*\badforest-action-btn--promote\b[^"]*"[^>]*>.*?</a>#si',
                '',
                $html
            );
        }

        if ($hide_bump) {
            $html = preg_replace(
                '#<a\b[^>]*class="[^"]*\badforest-action-btn--bump\b[^"]*"[^>]*>.*?</a>#si',
                '',
                $html
            );
        }

        return preg_replace('#<div class="adforest-action-row">\s*</div>#si', '', $html);
    }
}

if (!function_exists('bornado_detect_promotion_type_from_modal_request')) {
    /**
     * Infer which promotion modal is being requested from the form id.
     *
     * @return string Empty string when unknown.
     */
    function bornado_detect_promotion_type_from_modal_request()
    {
        $form_id = isset($_POST['formId']) ? sanitize_text_field(wp_unslash($_POST['formId'])) : '';

        if ($form_id !== '' && stripos($form_id, 'bump') !== false) {
            return 'bump';
        }

        if ($form_id !== '' && (stripos($form_id, 'feartured') !== false || stripos($form_id, 'feature') !== false)) {
            return 'feature';
        }

        return '';
    }
}

if (!function_exists('bornado_render_disabled_promotion_modal')) {
    /**
     * Render a lightweight warning when a disabled promotion modal is requested.
     *
     * @param string $type Promotion type.
     * @return void
     */
    function bornado_render_disabled_promotion_modal($type)
    {
        echo '<div class="alert alert-warning mt-3 mb-3">' . esc_html(bornado_get_promotion_disabled_message($type)) . '</div>';
        wp_die();
    }
}

if (!function_exists('bornado_filter_dashboard_allowed_page_types')) {
    /**
     * Drop disabled promotion pages from the dashboard page registry.
     *
     * @param array<int,string> $page_types Allowed page types.
     * @return array<int,string>
     */
    function bornado_filter_dashboard_allowed_page_types($page_types)
    {
        if (!is_array($page_types)) {
            return $page_types;
        }

        if (!bornado_is_promotion_enabled('feature')) {
            $page_types = array_values(array_diff($page_types, array('feature_ads')));
        }

        return $page_types;
    }

    add_filter('adforest_dashboard_allowed_page_types', 'bornado_filter_dashboard_allowed_page_types', 30);
}

if (!function_exists('bornado_redirect_disabled_promotion_pages')) {
    /**
     * Redirect requests to dashboard promotion pages that are currently disabled.
     *
     * @return void
     */
    function bornado_redirect_disabled_promotion_pages()
    {
        if (is_admin() || !is_page_template('page-theme-dashboard.php')) {
            return;
        }

        $page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';
        if ($page_type !== 'feature_ads' || bornado_is_promotion_enabled('feature')) {
            return;
        }

        wp_safe_redirect(remove_query_arg('page_type'));
        exit;
    }

    add_action('template_redirect', 'bornado_redirect_disabled_promotion_pages', 30);
}

if (!function_exists('bornado_handle_make_featured_ajax')) {
    /**
     * Block featured-ad AJAX when the capability is disabled.
     *
     * @return void
     */
    function bornado_handle_make_featured_ajax()
    {
        if (!bornado_is_promotion_enabled('feature')) {
            wp_send_json_error(array('message' => bornado_get_promotion_disabled_message('feature')));
        }

        adforest_make_featured();
    }
}

if (!function_exists('bornado_handle_bump_it_up_ajax')) {
    /**
     * Block bump-up AJAX when the capability is disabled.
     *
     * @return void
     */
    function bornado_handle_bump_it_up_ajax()
    {
        if (!bornado_is_promotion_enabled('bump')) {
            wp_send_json_error(array('message' => bornado_get_promotion_disabled_message('bump')));
        }

        adforest_bump_it_up();
    }
}

if (!function_exists('bornado_handle_make_featured_detail_ajax')) {
    /**
     * Block single-ad featured AJAX when the capability is disabled.
     *
     * @return void
     */
    function bornado_handle_make_featured_detail_ajax()
    {
        if (!bornado_is_promotion_enabled('feature')) {
            wp_send_json_error(array('message' => bornado_get_promotion_disabled_message('feature')));
        }

        adforest_make_featured_detail();
    }
}

if (!function_exists('bornado_handle_feature_modal_ajax')) {
    /**
     * Block feature/bump package modal AJAX when the capability is disabled.
     *
     * @return void
     */
    function bornado_handle_feature_modal_ajax()
    {
        $type = bornado_detect_promotion_type_from_modal_request();

        if ($type !== '' && !bornado_is_promotion_enabled($type)) {
            bornado_render_disabled_promotion_modal($type);
        }

        if ($type === '' && !bornado_is_promotion_enabled('any')) {
            bornado_render_disabled_promotion_modal('any');
        }

        load_feature_ad_modal();
    }
}

if (!function_exists('bornado_swap_promotion_ajax_handlers')) {
    /**
     * Swap parent promotion handlers for guarded child wrappers.
     *
     * @return void
     */
    function bornado_swap_promotion_ajax_handlers()
    {
        if (function_exists('adforest_make_featured')) {
            remove_action('wp_ajax_sb_make_featured', 'adforest_make_featured');
            add_action('wp_ajax_sb_make_featured', 'bornado_handle_make_featured_ajax');
        }

        if (function_exists('adforest_bump_it_up')) {
            remove_action('wp_ajax_sb_bump_it_up', 'adforest_bump_it_up');
            add_action('wp_ajax_sb_bump_it_up', 'bornado_handle_bump_it_up_ajax');
        }

        if (function_exists('adforest_make_featured_detail')) {
            remove_action('wp_ajax_sb_make_featured_detail', 'adforest_make_featured_detail');
            add_action('wp_ajax_sb_make_featured_detail', 'bornado_handle_make_featured_detail_ajax');
        }

        if (function_exists('load_feature_ad_modal')) {
            remove_action('wp_ajax_load_feature_ad_modal', 'load_feature_ad_modal');
            remove_action('wp_ajax_nopriv_load_feature_ad_modal', 'load_feature_ad_modal');
            add_action('wp_ajax_load_feature_ad_modal', 'bornado_handle_feature_modal_ajax');
            add_action('wp_ajax_nopriv_load_feature_ad_modal', 'bornado_handle_feature_modal_ajax');
        }
    }

    add_action('after_setup_theme', 'bornado_swap_promotion_ajax_handlers', 100);
}

if (!function_exists('bornado_enqueue_promotion_visibility_styles')) {
    /**
     * CSS fallback so dynamically injected rows stay in sync with the helper.
     *
     * @return void
     */
    function bornado_enqueue_promotion_visibility_styles()
    {
        $css = '';
        $dashboard_needs_reflow = !bornado_is_promotion_enabled('feature') || !bornado_is_promotion_enabled('bump');

        if ($dashboard_needs_reflow) {
            $css .= '
                body.page-template-page-theme-dashboard .top-selling-table tbody td .action {
                    display: flex;
                    align-items: center;
                    justify-content: center !important;
                    gap: 12px;
                }
                body.page-template-page-theme-dashboard .top-selling-table tbody td .action .ad_action_container {
                    margin: 0 !important;
                    gap: 12px !important;
                }
                body.page-template-page-theme-dashboard .top-selling-table tbody td .action .ad_action_container:empty {
                    display: none !important;
                }
                body.page-template-page-theme-dashboard .top-selling-table tbody td .action .more-btn {
                    margin: 0 !important;
                }
            ';
        }

        if (!bornado_is_promotion_enabled('feature')) {
            $css .= '
                body.page-template-page-theme-dashboard .sb_make_feature_ad_new_pkg,
                body.page-template-page-theme-dashboard .non-clickable-featured,
                body.page-template-page-my-listings .adforest-action-btn--promote,
                .single-ad .make_feature_admin_unlimited,
                .single-ad #sb_feature_ad {
                    display: none !important;
                }
            ';
        }

        if (!bornado_is_promotion_enabled('bump')) {
            $css .= '
                body.page-template-page-theme-dashboard .bump_it_up_new_pkg,
                body.page-template-page-theme-dashboard .bump_it_up,
                body.page-template-page-my-listings .adforest-action-btn--bump {
                    display: none !important;
                }
            ';
        }

        if ($css === '') {
            return;
        }

        wp_register_style('bornado-promotion-visibility-inline', false);
        wp_enqueue_style('bornado-promotion-visibility-inline');
        wp_add_inline_style('bornado-promotion-visibility-inline', $css);
    }

    add_action('wp_enqueue_scripts', 'bornado_enqueue_promotion_visibility_styles', 225);
}
