<?php
/**
 * Show Negotiable (توافقی) for ads without a numeric price.
 *
 * Child-theme only — does not modify parent theme or plugin core files.
 *
 * Search cards call get_ad_post_details(), which AdForest Elementor registers on
 * plugins_loaded priority 10. This file is bootstrapped earlier via the Bornado MU
 * loader (bornado_bootstrap_empty_ad_price_module) so the override wins there too.
 *
 * @package Bornado_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_get_negotiable_price_label')) {
    /**
     * Localized label for the Negotiable price type.
     *
     * @return string
     */
    function bornado_get_negotiable_price_label()
    {
        global $adforest_theme;

        if (isset($adforest_theme['sb_price_types_more']) && is_string($adforest_theme['sb_price_types_more']) && '' !== trim($adforest_theme['sb_price_types_more'])) {
            $custom_types = array_map('trim', explode('|', $adforest_theme['sb_price_types_more']));
            foreach ($custom_types as $custom_type) {
                if ('' === $custom_type) {
                    continue;
                }

                if (false !== mb_strpos($custom_type, 'توافق')) {
                    return $custom_type;
                }
            }
        }

        $label = __('Negotiable', 'adforest');
        if (is_string($label) && '' !== trim($label) && 'Negotiable' !== $label) {
            return $label;
        }

        return 'توافقی';
    }
}

if (!function_exists('bornado_normalize_price_type_label')) {
    /**
     * Map stored AdForest price type values to the label users should see.
     *
     * @param string $price_type Raw stored price type.
     * @return string
     */
    function bornado_normalize_price_type_label($price_type)
    {
        $price_type = trim((string) $price_type);
        if ('' === $price_type) {
            return '';
        }

        if ('Negotiable' === $price_type) {
            return bornado_get_negotiable_price_label();
        }

        if ('Fixed' === $price_type) {
            return __('Fixed', 'adforest');
        }

        if ('auction' === $price_type) {
            return __('Auction', 'adforest');
        }

        if ('on_call' === $price_type) {
            return __('Price On Call', 'adforest');
        }

        if ('free' === $price_type) {
            return __('Free', 'adforest');
        }

        return str_replace('_', ' ', $price_type);
    }
}

if (!function_exists('bornado_ad_has_numeric_price_value')) {
    /**
     * Determine whether an ad has a displayable numeric price value.
     *
     * @param int $post_id Ad post ID.
     * @return bool
     */
    function bornado_ad_has_numeric_price_value($post_id)
    {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return false;
        }

        $price_type = (string) get_post_meta($post_id, '_adforest_ad_price_type', true);

        if ('range' === $price_type) {
            $price_from = get_post_meta($post_id, '_adforest_ad_price_from', true);
            $price_to   = get_post_meta($post_id, '_adforest_ad_price_to', true);

            return ('' !== (string) $price_from || '' !== (string) $price_to);
        }

        $price = get_post_meta($post_id, '_adforest_ad_price', true);

        return ('' !== (string) $price);
    }
}

if (!function_exists('bornado_should_show_negotiable_for_empty_price')) {
    /**
     * Ads without a price should fall back to Negotiable unless they use a special type.
     *
     * @param int $post_id Ad post ID.
     * @return bool
     */
    function bornado_should_show_negotiable_for_empty_price($post_id)
    {
        if (bornado_ad_has_numeric_price_value($post_id)) {
            return false;
        }

        $price_type = (string) get_post_meta($post_id, '_adforest_ad_price_type', true);

        return !in_array($price_type, array('on_call', 'free', 'range'), true);
    }
}

if (!function_exists('bornado_render_negotiable_price_display')) {
    /**
     * Render Negotiable in the same shapes used by adforest_adPrice().
     *
     * @param string $class CSS class for the optional type wrapper.
     * @param string $tag   Wrapper tag; h3 keeps legacy markup.
     * @return string
     */
    function bornado_render_negotiable_price_display($class = 'negotiable', $tag = 'h3')
    {
        $label = bornado_get_negotiable_price_label();

        if ('h3' === $tag) {
            return '<h3>' . esc_html($label) . '</h3>';
        }

        return esc_html($label);
    }
}

if (!function_exists('bornado_build_empty_ad_price_html')) {
    /**
     * Strong-wrapped Negotiable label for card/list contexts.
     *
     * @return string
     */
    function bornado_build_empty_ad_price_html()
    {
        return '<strong>' . esc_html(bornado_get_negotiable_price_label()) . '</strong>';
    }
}

if (!function_exists('bornado_get_default_currency_code')) {
    /**
     * Resolve the storefront currency even when WooCommerce helpers are unavailable.
     *
     * @return string
     */
    function bornado_get_default_currency_code()
    {
        if (function_exists('get_woocommerce_currency')) {
            $currency = get_woocommerce_currency();
            if (is_string($currency) && '' !== $currency) {
                return $currency;
            }
        }

        return '';
    }
}

if (!function_exists('adforest_adPrice')) {
    /**
     * Child-theme override:
     * - Preserve AdForest price formatting for priced ads.
     * - Show Negotiable (توافقی) for ads without a price.
     */
    function adforest_adPrice($id = '', $class = 'negotiable', $tag = 'h3')
    {
        if (get_post_meta($id, '_adforest_ad_price_type', true) === 'range') {
            $price_from = get_post_meta($id, '_adforest_ad_price_from', true);
            $price_to   = get_post_meta($id, '_adforest_ad_price_to', true);

            if ($price_from === '' && $price_to === '') {
                if (bornado_should_show_negotiable_for_empty_price($id)) {
                    return bornado_render_negotiable_price_display($class, $tag);
                }

                return '';
            }

            global $adforest_theme;
            $remove_separator   = isset($adforest_theme['sb_price_separator_remove']) && '1' === (string) $adforest_theme['sb_price_separator_remove'];
            $thousands_sep      = $remove_separator ? '' : ',';
            $decimals           = isset($adforest_theme['sb_price_decimals']) ? (int) $adforest_theme['sb_price_decimals'] : 0;
            $decimals_separator = isset($adforest_theme['sb_price_decimals_separator']) ? $adforest_theme['sb_price_decimals_separator'] : '.';
            $curreny            = get_post_meta($id, '_adforest_ad_currency', true) ?: $adforest_theme['sb_currency'];

            if (!empty($adforest_theme['sb_price_separator']) && !$remove_separator) {
                $thousands_sep = $adforest_theme['sb_price_separator'];
            }

            $formatted_from = is_numeric($price_from) ? number_format($price_from, $decimals, $decimals_separator, $thousands_sep) : $price_from;
            $formatted_to   = is_numeric($price_to) ? number_format($price_to, $decimals, $decimals_separator, $thousands_sep) : $price_to;

            if (isset($adforest_theme['sb_price_direction'])) {
                switch ($adforest_theme['sb_price_direction']) {
                    case 'right':
                        $formatted_from .= $curreny;
                        $formatted_to   .= $curreny;
                        break;
                    case 'right_with_space':
                        $formatted_from .= ' ' . $curreny;
                        $formatted_to   .= ' ' . $curreny;
                        break;
                    case 'left':
                        $formatted_from = $curreny . $formatted_from;
                        $formatted_to   = $curreny . $formatted_to;
                        break;
                    case 'left_with_space':
                        $formatted_from = $curreny . ' ' . $formatted_from;
                        $formatted_to   = $curreny . ' ' . $formatted_to;
                        break;
                    default:
                        $formatted_from = $curreny . $formatted_from;
                        $formatted_to   = $curreny . $formatted_to;
                }
            }

            $price_range = $formatted_from . ' - ' . $formatted_to;

            if ($tag === 'h3') {
                return '<h3>' . $price_range . '</h3>';
            }

            return $price_range;
        }

        if (get_post_meta($id, '_adforest_ad_price', true) === '' && get_post_meta($id, '_adforest_ad_price_type', true) === 'on_call') {
            return __('Price On Call', 'adforest');
        }

        if (get_post_meta($id, '_adforest_ad_price', true) === '' && get_post_meta($id, '_adforest_ad_price_type', true) === 'free') {
            return __('Free', 'adforest');
        }

        if (bornado_should_show_negotiable_for_empty_price($id)) {
            return bornado_render_negotiable_price_display($class, $tag);
        }

        $price = 0;
        global $adforest_theme;
        $remove_separator = isset($adforest_theme['sb_price_separator_remove']) && '1' === (string) $adforest_theme['sb_price_separator_remove'];
        $thousands_sep = $remove_separator ? '' : ',';

        if (isset($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator'] !== '' && !$remove_separator) {
            $thousands_sep = $adforest_theme['sb_price_separator'];
        }

        $decimals           = 0;
        $decimals_separator = '.';

        if (isset($adforest_theme['sb_price_decimals']) && $adforest_theme['sb_price_decimals'] !== '') {
            $decimals = $adforest_theme['sb_price_decimals'];
        }

        if (isset($adforest_theme['sb_price_decimals_separator']) && $adforest_theme['sb_price_decimals_separator'] !== '') {
            $decimals_separator = $adforest_theme['sb_price_decimals_separator'];
        }

        $curreny = $adforest_theme['sb_currency'];

        if (get_post_meta($id, '_adforest_ad_currency', true) !== '') {
            $curreny = get_post_meta($id, '_adforest_ad_currency', true);
        }

        if ($id !== '') {
            if (is_numeric(get_post_meta($id, '_adforest_ad_price', true))) {
                $price = number_format(get_post_meta($id, '_adforest_ad_price', true), $decimals, $decimals_separator, $thousands_sep);
            }

            $price = (isset($price) && $price !== '') ? $price : 0;

            if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] === 'right') {
                $price = $price . $curreny;
            } elseif (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] === 'right_with_space') {
                $price = $price . ' ' . $curreny;
            } elseif (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] === 'left') {
                $price = $curreny . $price;
            } elseif (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] === 'left_with_space') {
                $price = $curreny . ' ' . $price;
            } else {
                $price = $curreny . $price;
            }
        }

        $price_type_html = '';

        if (get_post_meta($id, '_adforest_ad_price_type', true) !== '' && isset($adforest_theme['allow_price_type']) && $adforest_theme['allow_price_type']) {
            $price_type = '';

            $price_type = bornado_normalize_price_type_label((string) get_post_meta($id, '_adforest_ad_price_type', true));

            $price_type_html = '<span class="' . esc_attr($class) . '">&nbsp;(' . $price_type . ')</span>';
        }

        if ($tag === 'h3') {
            return '<h3>' . $price . ' </h3>' . $price_type_html;
        }

        return $price . '<small>' . $price_type_html . '</small>';
    }
}

if (!function_exists('get_ad_post_details')) {
    /**
     * Child-theme override for Elementor/list cards:
     * empty-price ads show Negotiable instead of "No Price".
     */
    function get_ad_post_details($post_id = null, $truncate_title = 0): array
    {
        global $adforest_theme;

        if ($post_id instanceof WP_Post) {
            $post_id = $post_id->ID;
        }

        $post_id = intval($post_id);

        if ($post_id === 0) {
            return array();
        }

        $ad_selected_cats = wp_get_post_terms(
            $post_id,
            'ad_cats',
            array(
                'orderby' => 'parent',
                'order'   => 'ASC',
            )
        );
        $ad_selected_countries = wp_get_post_terms(
            $post_id,
            'ad_country',
            array(
                'orderby' => 'parent',
                'order'   => 'ASC',
            )
        );
        if (is_wp_error($ad_selected_cats) || !is_array($ad_selected_cats)) {
            $ad_selected_cats = array();
        }

        if (is_wp_error($ad_selected_countries) || !is_array($ad_selected_countries)) {
            $ad_selected_countries = array();
        }

        $category_names = wp_list_pluck($ad_selected_cats, 'name');
        $category_ids   = wp_list_pluck($ad_selected_cats, 'term_id');

        $poster_id   = get_post_field('post_author', $post_id);
        $poster_name = get_post_meta($post_id, '_adforest_poster_name', true);

        if (empty($poster_name)) {
            $user_info   = get_userdata($poster_id);
            $poster_name = $user_info ? $user_info->display_name : '';
        }

        $user_pic             = function_exists('adforest_get_user_dp') ? adforest_get_user_dp($poster_id) : '';
        // Search/list cards never need the heavy single-post image size.
        $image_thumbnail_size = 'adforest-ad-list';
        $media                = function_exists('adforest_get_ad_images') ? adforest_get_ad_images($post_id) : array();
        $media_ids            = array();

        if (is_array($media)) {
            foreach ($media as $item) {
                if ($item instanceof WP_Post) {
                    $media_ids[] = (int) $item->ID;
                } elseif (is_numeric($item)) {
                    $media_ids[] = (int) $item;
                }
            }
        } elseif ($media instanceof WP_Post) {
            $media_ids[] = (int) $media->ID;
        } elseif (!empty($media) && is_numeric($media)) {
            $media_ids[] = (int) $media;
        }

        $media_ids = array_values(array_unique(array_filter($media_ids)));
        $img       = (!empty($media_ids)) ? wp_get_attachment_image_src($media_ids[0], $image_thumbnail_size) : null;
        $first_img = isset($img[0]) ? $img[0] : '';

        if (empty($first_img)) {
            $first_img = trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';
        }

        $all_ad_images = array();

        if (!empty($media_ids)) {
            foreach ($media_ids as $image_id) {
                $image_src = wp_get_attachment_image_src($image_id, $image_thumbnail_size);

                if (!empty($image_src)) {
                    $all_ad_images[] = $image_src[0];
                }
            }
        }

        if (empty($all_ad_images) && '' !== $first_img) {
            $all_ad_images[] = $first_img;
        }

        $ad_title             = get_the_title($post_id);
        $ad_location          = get_post_meta($post_id, '_adforest_ad_location', true);
        $truncated_location   = truncate_string($ad_location, 20);
        $truncated_title      = $truncate_title == 0 ? truncate_string(get_the_title($post_id), 18) : truncate_string(get_the_title($post_id), $truncate_title);
        $ad_price             = get_post_meta($post_id, '_adforest_ad_price', true);
        $ad_price_from        = get_post_meta($post_id, '_adforest_ad_price_from', true);
        $ad_price_to          = get_post_meta($post_id, '_adforest_ad_price_to', true);
        $ad_price_type        = get_post_meta($post_id, '_adforest_ad_price_type', true);
        $curreny              = get_post_meta($post_id, '_adforest_ad_currency', true) ?: $adforest_theme['sb_currency'];
        $default_currency     = bornado_get_default_currency_code();
        $currency_symbol      = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol($default_currency) : $default_currency;
        $default_currency     = isset($adforest_theme['sb_currency']) ? $adforest_theme['sb_currency'] : $currency_symbol;

        if (!$curreny) {
            $curreny = $default_currency;
        }

        $ad_link     = get_permalink($post_id);
        $is_fav      = (get_user_meta(get_current_user_id(), '_sb_fav_id_' . $post_id, true) == $post_id);
        $heart_class = $is_fav ? 'fas fa-heart text-danger' : 'far fa-heart';
        $price_html  = '';

        $remove_separator = isset($adforest_theme['sb_price_separator_remove']) && '1' === (string) $adforest_theme['sb_price_separator_remove'];
        $thousands_sep = $remove_separator ? '' : ',';

        if (!empty($adforest_theme['sb_price_separator']) && !$remove_separator) {
            $thousands_sep = $adforest_theme['sb_price_separator'];
        }

        $decimals           = isset($adforest_theme['sb_price_decimals']) ? (int) $adforest_theme['sb_price_decimals'] : 0;
        $decimals_separator = isset($adforest_theme['sb_price_decimals_separator']) ? $adforest_theme['sb_price_decimals_separator'] : '.';

        if (isset($ad_price) && $ad_price != '') {
            $formatted_price = is_numeric($ad_price) ? number_format($ad_price, $decimals, $decimals_separator, $thousands_sep) : $ad_price;
            $price_with_currency = '';

            if (isset($adforest_theme['sb_price_direction'])) {
                switch ($adforest_theme['sb_price_direction']) {
                    case 'right':
                        $price_with_currency = $formatted_price . $curreny;
                        break;
                    case 'right_with_space':
                        $price_with_currency = $formatted_price . ' ' . $curreny;
                        break;
                    case 'left':
                        $price_with_currency = $curreny . $formatted_price;
                        break;
                    case 'left_with_space':
                        $price_with_currency = $curreny . ' ' . $formatted_price;
                        break;
                    default:
                        $price_with_currency = $curreny . $formatted_price;
                }
            } else {
                $price_with_currency = $curreny . $formatted_price;
            }

            $price_html = '<strong>' . esc_html($price_with_currency);

            if (!empty($ad_price_type)) {
                $formatted_price_type = bornado_normalize_price_type_label((string) $ad_price_type);
                $price_html          .= ' <small>(' . esc_html($formatted_price_type) . ')</small>';
            }

            $price_html .= '</strong>';
        } elseif ($ad_price_type == 'free') {
            $price_html = '<strong>' . __('Free', 'adforest-elementor') . '</strong>';
        } elseif ($ad_price_type == 'on_call') {
            $price_html = '<strong>' . __('Price On Call', 'adforest-elementor') . '</strong>';
        } elseif ($ad_price_type == 'range') {
            $formatted_from = is_numeric($ad_price_from) ? number_format($ad_price_from, $decimals, $decimals_separator, $thousands_sep) : $ad_price_from;
            $formatted_to   = is_numeric($ad_price_to) ? number_format($ad_price_to, $decimals, $decimals_separator, $thousands_sep) : $ad_price_to;

            if (isset($adforest_theme['sb_price_direction'])) {
                switch ($adforest_theme['sb_price_direction']) {
                    case 'right':
                        $formatted_from .= $curreny;
                        $formatted_to   .= $curreny;
                        break;
                    case 'right_with_space':
                        $formatted_from .= ' ' . $curreny;
                        $formatted_to   .= ' ' . $curreny;
                        break;
                    case 'left':
                        $formatted_from = $curreny . $formatted_from;
                        $formatted_to   = $curreny . $formatted_to;
                        break;
                    case 'left_with_space':
                        $formatted_from = $curreny . ' ' . $formatted_from;
                        $formatted_to   = $curreny . ' ' . $formatted_to;
                        break;
                    default:
                        $formatted_from = $curreny . $formatted_from;
                        $formatted_to   = $curreny . $formatted_to;
                }
            }

            $price_range = $formatted_from . ' - ' . $formatted_to;
            $price_html  = '<strong>' . esc_html($price_range) . '</strong>';
        } elseif (bornado_should_show_negotiable_for_empty_price($post_id)) {
            $price_html = bornado_build_empty_ad_price_html();
        }

        $is_featured = get_post_meta($post_id, '_adforest_is_feature', true);

        return array(
            'category_names'     => $category_names,
            'img'                => $first_img,
            'first_image_id'     => !empty($media_ids) ? (int) $media_ids[0] : 0,
            'image_size'         => $image_thumbnail_size,
            'img_width'          => isset($img[1]) ? (int) $img[1] : 0,
            'img_height'         => isset($img[2]) ? (int) $img[2] : 0,
            'all_ad_images'      => $all_ad_images,
            'ad_title'           => $ad_title,
            'truncated_location' => $truncated_location,
            'truncated_title'    => $truncated_title,
            'price_html'         => $price_html,
            'ad_link'            => $ad_link,
            'heart_class'        => $heart_class,
            'is_fav'             => $is_fav,
            'is_featured'        => $is_featured,
            'location'           => $ad_location,
            'ad_poster_name'     => $poster_name,
            'ad_poster_img'      => $user_pic,
            'category_ids'       => $category_ids,
            'price'              => $ad_price,
            'categories'         => $ad_selected_cats,
            'countries'          => $ad_selected_countries,
        );
    }
}
