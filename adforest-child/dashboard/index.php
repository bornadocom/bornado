<?php
/**
 * Child-theme dashboard override.
 *
 * Keeps the parent dashboard template intact, but customizes the four
 * homepage shortcut cards at the top of the dashboard.
 */

if (!function_exists('bornado_dashboard_card_xpath_query')) {
    /**
     * Build an XPath selector for a dashboard card by icon class.
     *
     * @param string $icon_class Icon class fragment to match.
     * @return string
     */
    function bornado_dashboard_card_xpath_query($icon_class)
    {
        return sprintf(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' icon-card ')][.//i[contains(@class, '%s')]]",
            $icon_class
        );
    }
}

if (!function_exists('bornado_dashboard_dom_update_card')) {
    /**
     * Keep the original card markup, but update a specific card in place.
     *
     * @param DOMXPath    $xpath XPath helper.
     * @param DOMElement  $card  Card node.
     * @param array       $config Card config.
     * @return void
     */
    function bornado_dashboard_dom_update_card($xpath, $card, array $config)
    {
        if (!$card instanceof DOMElement || $card->parentNode === null) {
            return;
        }

        $icon_node = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' icon ')]/i", $card);
        if ($icon_node && $icon_node->length > 0 && !empty($config['icon_class'])) {
            $icon_node->item(0)->setAttribute('class', $config['icon_class']);
        }

        $title_node = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' content ')]/h6", $card);
        if ($title_node && $title_node->length > 0 && array_key_exists('title', $config) && $config['title'] !== null) {
            $title_node->item(0)->nodeValue = $config['title'];
        }

        $value_node = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' content ')]/h3", $card);
        if ($value_node && $value_node->length > 0 && array_key_exists('value', $config) && $config['value'] !== null) {
            $value_node->item(0)->nodeValue = $config['value'];
        }

        $href = esc_url($config['href']);
        $card->setAttribute('role', 'link');
        $card->setAttribute('tabindex', '0');
        $card->setAttribute('aria-label', wp_strip_all_tags((string) ($config['title'] ?? 'Dashboard shortcut')));
        $card->setAttribute('data-bornado-href', $href);
        $card->setAttribute('onclick', "window.location.href='" . esc_js($href) . "';");
        $card->setAttribute(
            'onkeydown',
            "if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='" . esc_js($href) . "';}"
        );

        $existing_style = trim((string) $card->getAttribute('style'));
        $card->setAttribute('style', trim($existing_style . ';cursor:pointer;'));
    }
}

if (!function_exists('bornado_dashboard_dom_get_inner_html')) {
    /**
     * Return all child markup of a DOM node.
     *
     * @param DOMDocument $dom  Parsed dashboard document.
     * @param DOMNode     $node Root wrapper node.
     * @return string
     */
    function bornado_dashboard_dom_get_inner_html($dom, $node)
    {
        $html = '';

        foreach ($node->childNodes as $child_node) {
            $html .= $dom->saveHTML($child_node);
        }

        return $html;
    }
}

if (!function_exists('bornado_customize_dashboard_home_cards')) {
    /**
     * Replace the four dashboard stat cards with clickable shortcuts.
     *
     * @param string $html Original dashboard markup.
     * @return string
     */
    function bornado_customize_dashboard_home_cards($html)
    {
        if (!class_exists('DOMDocument')) {
            return $html;
        }

        $dashboard_url = get_permalink();
        $user_id = get_current_user_id();

        $cards = array(
            'lni-cart-full' => array(
                'href'       => add_query_arg('page_type', 'my_profile', $dashboard_url),
                'title'      => 'ویرایش پروفایل من',
                'value'      => 'ویرایش',
                'icon_class' => 'lni lni-user',
            ),
            'lni-dollar' => array(
                'href'       => add_query_arg('page_type', 'my_ads', $dashboard_url),
                'title'      => 'کل آگهی‌ها',
                'value'      => null,
                'icon_class' => 'lni lni-list',
            ),
            'lni-credit-cards' => array(
                'href'       => add_query_arg('page_type', 'msg', $dashboard_url),
                'title'      => 'پیام‌های من',
                'value'      => 'مشاهده',
                'icon_class' => 'lni lni-envelope',
            ),
            'lni-user' => array(
                'href'       => adforest_set_url_param(get_author_posts_url($user_id), 'type', 1),
                'title'      => null,
                'value'      => null,
                'icon_class' => 'lni lni-star-filled',
            ),
        );

        $previous_libxml_state = libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="bornado-dashboard-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous_libxml_state);
            return $html;
        }

        $xpath = new DOMXPath($dom);

        $target_cards = array();

        foreach (array_keys($cards) as $icon_class) {
            $matched_cards = $xpath->query(bornado_dashboard_card_xpath_query($icon_class));

            if ($matched_cards && $matched_cards->length > 0 && $matched_cards->item(0) instanceof DOMElement) {
                $target_cards[$icon_class] = $matched_cards->item(0);
            }
        }

        foreach ($cards as $icon_class => $config) {
            if (!isset($target_cards[$icon_class])) {
                continue;
            }

            bornado_dashboard_dom_update_card(
                $xpath,
                $target_cards[$icon_class],
                $config
            );
        }

        $root = $dom->getElementById('bornado-dashboard-root');
        $result = $root instanceof DOMElement ? bornado_dashboard_dom_get_inner_html($dom, $root) : $html;

        libxml_clear_errors();
        libxml_use_internal_errors($previous_libxml_state);

        return $result;
    }
}

$bornado_dashboard_page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';

ob_start();
require get_template_directory() . '/dashboard/index.php';
$bornado_dashboard_output = ob_get_clean();

if ($bornado_dashboard_page_type === '') {
    $bornado_dashboard_output = bornado_customize_dashboard_home_cards($bornado_dashboard_output);
}

if (function_exists('bornado_filter_dashboard_promotion_markup')) {
    $bornado_dashboard_output = bornado_filter_dashboard_promotion_markup($bornado_dashboard_output);
}

echo $bornado_dashboard_output;
