<?php

if (!function_exists('bornado_get_modern_post_ad_page_id')) {
    /**
     * Resolve the configured modern Post Ad page for the active language.
     *
     * @return int
     */
    function bornado_get_modern_post_ad_page_id()
    {
        global $adforest_theme;

        $candidate_ids = array();

        $configured_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? (int) $adforest_theme['sb_modern_post_ad_page'] : 0;
        if ($configured_page_id > 0) {
            $candidate_ids[] = $configured_page_id;

            $translated_page_id = (int) apply_filters('adforest_language_page_id', $configured_page_id);
            if ($translated_page_id > 0) {
                $candidate_ids[] = $translated_page_id;
            }
        }

        foreach (array_unique(array_map('intval', $candidate_ids)) as $candidate_id) {
            if ($candidate_id > 0 && 'page' === get_post_type($candidate_id)) {
                return $candidate_id;
            }
        }

        $template_pages = get_posts(array(
            'post_type'              => 'page',
            'post_status'            => array('publish', 'private'),
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'meta_key'               => '_wp_page_template',
            'meta_value'             => 'page-add-new.php',
            'orderby'                => 'menu_order title',
            'order'                  => 'ASC',
            'suppress_filters'       => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'no_found_rows'          => true,
        ));

        if (!empty($template_pages)) {
            return (int) $template_pages[0];
        }

        return 0;
    }
}

if (!function_exists('bornado_get_edit_ad_url')) {
    /**
     * Build the correct frontend edit URL for a given ad.
     *
     * @param int         $ad_id        Ad post ID.
     * @param string|null $fallback_url Legacy/fallback URL.
     * @return string
     */
    function bornado_get_edit_ad_url($ad_id, $fallback_url = '')
    {
        $ad_id = absint($ad_id);
        if ($ad_id < 1 || 'ad_post' !== get_post_type($ad_id)) {
            return is_string($fallback_url) ? $fallback_url : '';
        }

        $modern_page_id = bornado_get_modern_post_ad_page_id();
        if ($modern_page_id > 0) {
            $modern_page_url = get_permalink($modern_page_id);
            if (is_string($modern_page_url) && '' !== $modern_page_url) {
                return add_query_arg('id', $ad_id, $modern_page_url);
            }
        }

        return is_string($fallback_url) ? $fallback_url : '';
    }
}

if (!function_exists('bornado_rewrite_edit_ad_url_to_modern_page')) {
    /**
     * Force legacy AdForest edit-ad links onto the modern Add New page.
     *
     * AdForest builds edit links via `adforest_set_url_param(..., 'id', $ad_id)`.
     * Later, Bornado's semantic-routing filter can reinterpret those query URLs
     * as search/archive links on location-based pages. When the `id` belongs to
     * an `ad_post`, treat it as an edit action and rebuild the URL on the
     * dedicated modern page instead of leaving it on the legacy/classic base URL.
     *
     * @param string $url Candidate frontend URL.
     * @return string
     */
    function bornado_rewrite_edit_ad_url_to_modern_page($url)
    {
        if (!is_string($url) || '' === trim($url)) {
            return $url;
        }

        $parsed_url = wp_parse_url($url);
        if (!is_array($parsed_url) || empty($parsed_url['query'])) {
            return $url;
        }

        $query_args = array();
        parse_str((string) $parsed_url['query'], $query_args);

        if (!isset($query_args['id'])) {
            return $url;
        }

        $ad_id = absint($query_args['id']);
        if ($ad_id < 1 || 'ad_post' !== get_post_type($ad_id)) {
            return $url;
        }

        $rewritten_url = bornado_get_edit_ad_url($ad_id, '');
        if (!is_string($rewritten_url) || '' === $rewritten_url) {
            return $url;
        }

        if (!empty($parsed_url['fragment'])) {
            $rewritten_url .= '#' . ltrim((string) $parsed_url['fragment'], '#');
        }

        return $rewritten_url;
    }

    add_filter('adforest_page_lang_url', 'bornado_rewrite_edit_ad_url_to_modern_page', 99);
}
