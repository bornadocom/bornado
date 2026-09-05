<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_item_page_id')) {
    /**
     * Stable @id for a single-ad ItemPage node.
     *
     * @param string $canonical_url
     * @return string
     */
    function bornado_schema_manager_get_item_page_id($canonical_url)
    {
        $canonical_url = (string) $canonical_url;

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#webpage';
    }
}

if (!function_exists('bornado_schema_manager_get_ad_entity_id')) {
    /**
     * Stable @id for the primary classified entity.
     *
     * @param string $canonical_url
     * @return string
     */
    function bornado_schema_manager_get_ad_entity_id($canonical_url)
    {
        $canonical_url = (string) $canonical_url;

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#ad';
    }
}

if (!function_exists('bornado_schema_manager_get_ad_offer_id')) {
    /**
     * Stable @id for the Offer node of a single ad.
     *
     * @param string $canonical_url
     * @return string
     */
    function bornado_schema_manager_get_ad_offer_id($canonical_url)
    {
        $canonical_url = (string) $canonical_url;

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#offer';
    }
}

if (!function_exists('bornado_schema_manager_get_ad_place_id')) {
    /**
     * Stable @id for the Place node of a single ad.
     *
     * @param string $canonical_url
     * @return string
     */
    function bornado_schema_manager_get_ad_place_id($canonical_url)
    {
        $canonical_url = (string) $canonical_url;

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#place';
    }
}

if (!function_exists('bornado_schema_manager_get_ad_image_id')) {
    /**
     * Stable @id for a gallery ImageObject on a single ad.
     *
     * @param string $canonical_url
     * @param int    $index
     * @return string
     */
    function bornado_schema_manager_get_ad_image_id($canonical_url, $index)
    {
        $canonical_url = (string) $canonical_url;
        $index         = max(1, (int) $index);

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#image-' . $index;
    }
}

if (!function_exists('bornado_schema_manager_normalize_schema_text')) {
    /**
     * Collapse whitespace and strip tags for schema text fields.
     *
     * @param mixed $value
     * @return string
     */
    function bornado_schema_manager_normalize_schema_text($value)
    {
        $text = trim(preg_replace('/\s+/u', ' ', wp_specialchars_decode(wp_strip_all_tags((string) $value), ENT_QUOTES)));

        return is_string($text) ? $text : '';
    }
}

if (!function_exists('bornado_schema_manager_parse_schema_number')) {
    /**
     * Parse a numeric price-like value from mixed ad meta.
     *
     * @param mixed $value
     * @return float|null
     */
    function bornado_schema_manager_parse_schema_number($value)
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d.,\-]/u', '', $raw);
        if (!is_string($normalized) || $normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return null;
        }

        if (strpos($normalized, ',') !== false && strpos($normalized, '.') !== false) {
            $normalized = str_replace(',', '', $normalized);
        } elseif (strpos($normalized, ',') !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}

if (!function_exists('bornado_schema_manager_get_post_term_chain')) {
    /**
     * Deepest assigned term plus ancestors for a taxonomy.
     *
     * @param int    $post_id
     * @param string $taxonomy
     * @return array<int,WP_Term>
     */
    function bornado_schema_manager_get_post_term_chain($post_id, $taxonomy)
    {
        $post_id  = (int) $post_id;
        $taxonomy = (string) $taxonomy;

        if ($post_id < 1 || $taxonomy === '') {
            return array();
        }

        if (function_exists('bornado_semantic_breadcrumb_get_post_term_chain')) {
            $chain = bornado_semantic_breadcrumb_get_post_term_chain($post_id, $taxonomy);
            if (is_array($chain)) {
                return array_values(array_filter($chain, static function ($term) {
                    return $term instanceof WP_Term;
                }));
            }
        }

        $terms = wp_get_post_terms($post_id, $taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }

        $deepest = null;
        $deepest_depth = -1;
        foreach ($terms as $term) {
            if (!($term instanceof WP_Term)) {
                continue;
            }
            $depth = count(get_ancestors((int) $term->term_id, $taxonomy, 'taxonomy'));
            if ($depth > $deepest_depth) {
                $deepest_depth = $depth;
                $deepest = $term;
            }
        }

        if (!($deepest instanceof WP_Term)) {
            return array();
        }

        $ids = array_reverse(array_map('intval', get_ancestors((int) $deepest->term_id, $taxonomy, 'taxonomy')));
        $ids[] = (int) $deepest->term_id;
        $chain = array();
        foreach ($ids as $term_id) {
            $term = get_term($term_id, $taxonomy);
            if ($term instanceof WP_Term) {
                $chain[] = $term;
            }
        }

        return $chain;
    }
}

if (!function_exists('bornado_schema_manager_resolve_ad_location_terms')) {
    /**
     * Resolve country/city terms for a single ad from ad_country assignments.
     *
     * @param int $post_id
     * @return array{country_term:?WP_Term,city_term:?WP_Term}
     */
    function bornado_schema_manager_resolve_ad_location_terms($post_id)
    {
        $chain = bornado_schema_manager_get_post_term_chain((int) $post_id, 'ad_country');
        $country_term = !empty($chain[0]) && $chain[0] instanceof WP_Term ? $chain[0] : null;
        $city_term = null;

        foreach ($chain as $term) {
            if (!($term instanceof WP_Term)) {
                continue;
            }
            if (count(get_ancestors((int) $term->term_id, 'ad_country', 'taxonomy')) === 2) {
                $city_term = $term;
                break;
            }
        }

        if (!($city_term instanceof WP_Term) && !empty($chain)) {
            $last = end($chain);
            if ($last instanceof WP_Term && (!($country_term instanceof WP_Term) || (int) $last->term_id !== (int) $country_term->term_id)) {
                $city_term = $last;
            }
        }

        return array(
            'country_term' => $country_term,
            'city_term'    => $city_term,
        );
    }
}

if (!function_exists('bornado_schema_manager_resolve_iso_currency_code')) {
    /**
     * Resolve a 3-letter ISO currency code when confidently available.
     *
     * @param int           $post_id
     * @param WP_Term|null  $country_term
     * @return string
     */
    function bornado_schema_manager_resolve_iso_currency_code($post_id, $country_term = null)
    {
        $candidates = array();

        $meta = trim((string) get_post_meta((int) $post_id, '_adforest_ad_currency', true));
        if ($meta !== '') {
            $candidates[] = $meta;
        }

        $currency_terms = wp_get_post_terms((int) $post_id, 'ad_currency');
        if (!is_wp_error($currency_terms) && !empty($currency_terms)) {
            foreach ($currency_terms as $term) {
                if ($term instanceof WP_Term) {
                    $candidates[] = (string) $term->slug;
                    $candidates[] = (string) $term->name;
                    $geo_code = (string) get_term_meta((int) $term->term_id, '_bornado_geo_currency_code', true);
                    if ($geo_code !== '') {
                        $candidates[] = $geo_code;
                    }
                }
            }
        }

        if ($country_term instanceof WP_Term) {
            $geo_code = (string) get_term_meta((int) $country_term->term_id, '_bornado_geo_currency_code', true);
            if ($geo_code !== '') {
                $candidates[] = $geo_code;
            }

            if (function_exists('bornado_get_country_data')) {
                $country_data = (array) bornado_get_country_data($country_term);
                if (!empty($country_data['currency_code'])) {
                    $candidates[] = (string) $country_data['currency_code'];
                }
            }
        }

        $symbol_map = array(
            '£' => 'GBP',
            '$' => 'USD',
            '€' => 'EUR',
            '¥' => 'JPY',
            '₹' => 'INR',
            '₽' => 'RUB',
            '₺' => 'TRY',
            '₩' => 'KRW',
            'د.إ' => 'AED',
            'ر.س' => 'SAR',
        );

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            if (isset($symbol_map[$candidate])) {
                return $symbol_map[$candidate];
            }

            $upper = strtoupper($candidate);
            if (preg_match('/^[A-Z]{3}$/', $upper)) {
                return $upper;
            }

            if (preg_match('/\b([A-Z]{3})\b/', $upper, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }
}

if (!function_exists('bornado_schema_manager_map_item_condition')) {
    /**
     * Map ad condition labels/slugs to schema.org itemCondition URLs.
     *
     * @param string $condition
     * @return string
     */
    function bornado_schema_manager_map_item_condition($condition)
    {
        $value = strtolower(trim((string) $condition));
        if ($value === '') {
            return '';
        }

        $new_tokens = array('new', 'نو', 'جدید', 'brand-new', 'brand_new');
        $used_tokens = array('used', 'کارکرده', 'دست دوم', 'second-hand', 'second_hand', 'pre-owned', 'preowned');
        $refurb_tokens = array('refurbished', 'بازسازی', 'تعمیرشده', 'reconditioned');

        foreach ($new_tokens as $token) {
            if ($value === $token || strpos($value, $token) !== false) {
                return 'https://schema.org/NewCondition';
            }
        }
        foreach ($used_tokens as $token) {
            if ($value === $token || strpos($value, $token) !== false) {
                return 'https://schema.org/UsedCondition';
            }
        }
        foreach ($refurb_tokens as $token) {
            if ($value === $token || strpos($value, $token) !== false) {
                return 'https://schema.org/RefurbishedCondition';
            }
        }

        return '';
    }
}

if (!function_exists('bornado_schema_manager_map_availability')) {
    /**
     * Map Bornado ad status to schema.org availability.
     *
     * @param string $status
     * @return string
     */
    function bornado_schema_manager_map_availability($status)
    {
        $status = strtolower(trim((string) $status));

        if ($status === 'sold') {
            return 'https://schema.org/SoldOut';
        }
        if ($status === 'expired') {
            return 'https://schema.org/OutOfStock';
        }

        return 'https://schema.org/InStock';
    }
}

if (!function_exists('bornado_schema_manager_collect_ad_image_urls')) {
    /**
     * Collect absolute image URLs for an ad gallery.
     *
     * @param int $post_id
     * @return array<int,string>
     */
    function bornado_schema_manager_collect_ad_image_urls($post_id)
    {
        $post_id = (int) $post_id;
        $urls    = array();

        if (function_exists('adforest_get_ad_images')) {
            $media = adforest_get_ad_images($post_id);
            if (is_array($media)) {
                foreach ($media as $item) {
                    $attachment_id = 0;
                    if (is_object($item) && isset($item->ID)) {
                        $attachment_id = (int) $item->ID;
                    } elseif (is_array($item) && isset($item['id'])) {
                        $attachment_id = (int) $item['id'];
                    } elseif (is_numeric($item)) {
                        $attachment_id = (int) $item;
                    }

                    if ($attachment_id > 0) {
                        $url = (string) wp_get_attachment_image_url($attachment_id, 'full');
                        if ($url !== '') {
                            $urls[] = $url;
                        }
                    }
                }
            }
        }

        if (empty($urls)) {
            $thumb = (string) get_the_post_thumbnail_url($post_id, 'full');
            if ($thumb !== '') {
                $urls[] = $thumb;
            }
        }

        $unique = array();
        foreach ($urls as $url) {
            $url = esc_url_raw($url);
            if ($url === '') {
                continue;
            }
            $key = untrailingslashit(strtolower($url));
            if (!isset($unique[$key])) {
                $unique[$key] = $url;
            }
        }

        return array_values($unique);
    }
}

if (!function_exists('bornado_schema_manager_get_single_ad_context')) {
    /**
     * Build a normalized context payload for the current (or given) single ad.
     *
     * @param int|null $post_id
     * @return array<string,mixed>
     */
    function bornado_schema_manager_get_single_ad_context($post_id = null)
    {
        static $cache = array();

        $post_id = $post_id === null ? (int) get_queried_object_id() : (int) $post_id;
        if ($post_id < 1) {
            return array();
        }

        if (isset($cache[$post_id])) {
            return $cache[$post_id];
        }

        $post = get_post($post_id);
        if (!($post instanceof WP_Post) || $post->post_type !== 'ad_post') {
            $cache[$post_id] = array();
            return array();
        }

        $canonical_url = (string) get_permalink($post);
        if (function_exists('bornado_schema_manager_get_current_canonical_url') && is_singular('ad_post') && (int) get_queried_object_id() === $post_id) {
            $resolved = bornado_schema_manager_get_current_canonical_url();
            if ($resolved !== '') {
                $canonical_url = $resolved;
            }
        }

        $category_chain = bornado_schema_manager_get_post_term_chain($post_id, 'ad_cats');
        $deepest_category = !empty($category_chain) ? end($category_chain) : null;
        if (!($deepest_category instanceof WP_Term)) {
            $deepest_category = null;
        }
        $root_category = $deepest_category instanceof WP_Term && function_exists('bornado_schema_manager_get_category_root_term')
            ? bornado_schema_manager_get_category_root_term($deepest_category)
            : null;

        $vertical_key = '';
        $vertical_config = array();
        if ($root_category instanceof WP_Term && function_exists('bornado_schema_manager_get_category_vertical_configs')) {
            foreach (bornado_schema_manager_get_category_vertical_configs() as $candidate_key => $config) {
                $term_ids = isset($config['term_ids']) && is_array($config['term_ids']) ? array_map('intval', $config['term_ids']) : array();
                $slugs    = isset($config['slugs']) && is_array($config['slugs']) ? array_map('sanitize_title', $config['slugs']) : array();
                if (in_array((int) $root_category->term_id, $term_ids, true) || in_array(sanitize_title($root_category->slug), $slugs, true)) {
                    $vertical_key = (string) $candidate_key;
                    $vertical_config = is_array($config) ? $config : array();
                    break;
                }
            }
        }

        $location = bornado_schema_manager_resolve_ad_location_terms($post_id);
        $country_term = $location['country_term'];
        $city_term = $location['city_term'];

        $price_type = (string) get_post_meta($post_id, '_adforest_ad_price_type', true);
        $price = bornado_schema_manager_parse_schema_number(get_post_meta($post_id, '_adforest_ad_price', true));
        $price_from = bornado_schema_manager_parse_schema_number(get_post_meta($post_id, '_adforest_ad_price_from', true));
        $price_to = bornado_schema_manager_parse_schema_number(get_post_meta($post_id, '_adforest_ad_price_to', true));
        $currency = bornado_schema_manager_resolve_iso_currency_code($post_id, $country_term);

        $condition_meta = (string) get_post_meta($post_id, '_adforest_ad_condition', true);
        $condition_terms = wp_get_post_terms($post_id, 'ad_condition');
        $condition_label = $condition_meta;
        if ((is_wp_error($condition_terms) || empty($condition_terms)) === false && $condition_terms[0] instanceof WP_Term) {
            $condition_label = (string) $condition_terms[0]->name;
            if ($condition_meta === '') {
                $condition_meta = (string) $condition_terms[0]->slug;
            }
        }

        $ad_type_meta = (string) get_post_meta($post_id, '_adforest_ad_type', true);
        $ad_type_terms = wp_get_post_terms($post_id, 'ad_type');
        $ad_type_label = $ad_type_meta;
        if (!is_wp_error($ad_type_terms) && !empty($ad_type_terms) && $ad_type_terms[0] instanceof WP_Term) {
            $ad_type_label = (string) $ad_type_terms[0]->name;
        }

        $status = (string) get_post_meta($post_id, '_adforest_ad_status_', true);
        if ($status === '') {
            $status = 'active';
        }

        $title = bornado_schema_manager_normalize_schema_text(get_the_title($post));
        $description = '';
        if (function_exists('bornado_schema_manager_get_current_meta_description') && is_singular('ad_post') && (int) get_queried_object_id() === $post_id) {
            $description = bornado_schema_manager_get_current_meta_description();
        }
        if ($description === '') {
            $description = bornado_schema_manager_normalize_schema_text(get_the_excerpt($post));
        }
        if ($description === '') {
            $description = bornado_schema_manager_normalize_schema_text(wp_trim_words((string) $post->post_content, 60, ''));
        }

        $poster_name = bornado_schema_manager_normalize_schema_text(get_post_meta($post_id, '_adforest_poster_name', true));
        if ($poster_name === '') {
            $author = get_user_by('id', (int) $post->post_author);
            if ($author instanceof WP_User) {
                $poster_name = bornado_schema_manager_normalize_schema_text($author->display_name);
            }
        }

        // Match UI privacy policy: imported/admin-owned ads hide seller identity.
        if (
            $poster_name !== ''
            && function_exists('bornado_should_hide_seller_identity')
            && bornado_should_hide_seller_identity($post_id)
        ) {
            $poster_name = '';
        }

        $lat = bornado_schema_manager_parse_schema_number(get_post_meta($post_id, '_adforest_ad_map_lat', true));
        $lng = bornado_schema_manager_parse_schema_number(get_post_meta($post_id, '_adforest_ad_map_long', true));
        $location_label = bornado_schema_manager_normalize_schema_text(get_post_meta($post_id, '_adforest_ad_location', true));

        $language_tag = function_exists('bornado_schema_manager_get_site_language_tag')
            ? (string) bornado_schema_manager_get_site_language_tag()
            : 'fa-IR';

        $context = array(
            'post'              => $post,
            'post_id'           => $post_id,
            'canonical_url'     => $canonical_url,
            'title'             => $title,
            'description'       => $description,
            'date_published'    => get_post_time('c', true, $post),
            'date_modified'     => get_post_modified_time('c', true, $post),
            'in_language'       => $language_tag !== '' ? $language_tag : 'fa-IR',
            'category_chain'    => $category_chain,
            'deepest_category'  => $deepest_category,
            'root_category'     => $root_category,
            'vertical_key'      => $vertical_key,
            'vertical_config'   => $vertical_config,
            'country_term'      => $country_term,
            'city_term'         => $city_term,
            'location_label'    => $location_label,
            'latitude'          => $lat,
            'longitude'         => $lng,
            'price_type'        => $price_type,
            'price'             => $price,
            'price_from'        => $price_from,
            'price_to'          => $price_to,
            'currency'          => $currency,
            'condition_label'   => bornado_schema_manager_normalize_schema_text($condition_label),
            'condition_schema'  => bornado_schema_manager_map_item_condition($condition_label !== '' ? $condition_label : $condition_meta),
            'ad_type_label'     => bornado_schema_manager_normalize_schema_text($ad_type_label),
            'ad_status'         => $status,
            'availability'      => bornado_schema_manager_map_availability($status),
            'poster_name'       => $poster_name,
            'image_urls'        => bornado_schema_manager_collect_ad_image_urls($post_id),
            'ids'               => array(
                'webpage' => bornado_schema_manager_get_item_page_id($canonical_url),
                'ad'      => bornado_schema_manager_get_ad_entity_id($canonical_url),
                'offer'   => bornado_schema_manager_get_ad_offer_id($canonical_url),
                'place'   => bornado_schema_manager_get_ad_place_id($canonical_url),
                'breadcrumb' => bornado_schema_manager_get_breadcrumb_id($canonical_url),
            ),
        );

        $cache[$post_id] = $context;

        return $context;
    }
}
