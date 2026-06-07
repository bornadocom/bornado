<?php
/**
 * Custom bornad-style single ad template.
 *
 * Keeps AdForest data sources and partials, but uses a dedicated layout
 * closer to the reference design for RTL / Persian storefronts.
 *
 * @package Bornado_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

global $adforest_theme;

if (!function_exists('bornado_extract_style6_field_items')) {
    /**
     * Convert AdForest style-6 custom field HTML into structured items.
     *
     * @param string $html Raw custom field HTML.
     * @return array<int, array<string, string>>
     */
    function bornado_extract_style6_field_items($html)
    {
        $items = array();
        if (!is_string($html) || '' === trim($html)) {
            return $items;
        }

        if (!preg_match_all('/<li>(.*?)<\/li>/si', $html, $matches)) {
            return $items;
        }

        foreach ($matches[1] as $raw_item) {
            $raw_item = trim((string) $raw_item);
            if ('' === $raw_item) {
                continue;
            }

            if (preg_match('/^(.*?)\s*:\s*<span>(.*)<\/span>\s*$/si', $raw_item, $parts)) {
                $label = trim(wp_strip_all_tags($parts[1]));
                $value = trim($parts[2]);
            } else {
                $label = trim(wp_strip_all_tags($raw_item));
                $value = '';
            }

            if ('' === $label) {
                continue;
            }

            $items[] = array(
                'label'      => $label,
                'value_html' => $value,
            );
        }

        return $items;
    }
}

if (!function_exists('bornado_build_summary_field_items')) {
    /**
     * Pick a compact summary set for the top information panel.
     *
     * @param array $custom_items Parsed custom field items.
     * @param array $fallbacks    Fallback items.
     * @param int   $max_items    Maximum item count.
     * @return array<int, array<string, string>>
     */
    function bornado_build_summary_field_items($custom_items, $fallbacks, $max_items = 4)
    {
        $summary = array();
        $seen    = array();

        foreach (array_merge((array) $custom_items, (array) $fallbacks) as $item) {
            $label = isset($item['label']) ? trim((string) $item['label']) : '';
            $value = isset($item['value_html']) ? trim((string) $item['value_html']) : '';

            if ('' === $label || '' === wp_strip_all_tags($value)) {
                continue;
            }

            $key = md5(strtolower($label . '|' . wp_strip_all_tags($value)));
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $summary[]  = array(
                'label'      => $label,
                'value_html' => $value,
            );

            if (count($summary) >= (int) $max_items) {
                break;
            }
        }

        return $summary;
    }
}

if (!function_exists('bornado_filter_remaining_field_items')) {
    /**
     * Remove summary items from the full detail list to avoid duplicates.
     *
     * @param array $all_items     Full field items.
     * @param array $summary_items Summary field items.
     * @return array<int, array<string, string>>
     */
    function bornado_filter_remaining_field_items($all_items, $summary_items)
    {
        $summary_keys = array();
        foreach ((array) $summary_items as $item) {
            $label = isset($item['label']) ? (string) $item['label'] : '';
            $value = isset($item['value_html']) ? (string) $item['value_html'] : '';
            $summary_keys[md5(strtolower($label . '|' . wp_strip_all_tags($value)))] = true;
        }

        $remaining = array();
        foreach ((array) $all_items as $item) {
            $label = isset($item['label']) ? (string) $item['label'] : '';
            $value = isset($item['value_html']) ? (string) $item['value_html'] : '';
            $item_key = md5(strtolower($label . '|' . wp_strip_all_tags($value)));
            if (isset($summary_keys[$item_key])) {
                continue;
            }
            $remaining[] = $item;
        }

        return $remaining;
    }
}

if (!function_exists('bornado_mask_email_address')) {
    /**
     * Obscure part of an email address for guest-facing previews.
     *
     * @param string $email Email address.
     * @return string
     */
    function bornado_mask_email_address($email)
    {
        $email = trim((string) $email);
        if ('' === $email || false === strpos($email, '@')) {
            return $email;
        }

        list($local_part, $domain_part) = explode('@', $email, 2);
        $local_part = trim((string) $local_part);
        if (strlen($local_part) <= 2) {
            $local_part = substr($local_part, 0, 1) . '*';
        } else {
            $local_part = substr($local_part, 0, 2) . str_repeat('*', max(strlen($local_part) - 2, 3));
        }

        return $local_part . '@' . $domain_part;
    }
}

if (!function_exists('bornado_render_contact_item')) {
    /**
     * Render a contact item for the bornad single-ad contact list.
     *
     * @param array<string, mixed> $args Contact item args.
     * @return string
     */
    function bornado_render_contact_item($args)
    {
        $defaults = array(
            'icon_image' => '',
            'icon_class' => '',
            'icon_alt' => '',
            'small_text' => '',
            'value_text' => '',
            'href' => 'javascript:void(0)',
            'reveal' => false,
            'post_id' => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $icon_html = '';
        if ('' !== $args['icon_image']) {
            $icon_html = '<img src="' . esc_url((string) $args['icon_image']) . '" alt="' . esc_attr((string) $args['icon_alt']) . '">';
        } elseif ('' !== $args['icon_class']) {
            $icon_html = '<i class="' . esc_attr((string) $args['icon_class']) . '" aria-hidden="true"></i>';
        }

        if (!empty($args['reveal'])) {
            return sprintf(
                '<div class="bornad-contact-item bornad-contact-item--reveal"><span class="bornad-contact-icon">%1$s</span><span class="bornad-contact-text"><small class="toggle-contact-number" style="cursor:pointer;" data-ad-id="%2$d">%3$s</small><a class="style_2_ph" href="javascript:void(0)">%4$s</a></span></div>',
                $icon_html,
                absint($args['post_id']),
                esc_html((string) $args['small_text']),
                esc_html((string) $args['value_text'])
            );
        }

        return sprintf(
            '<a class="bornad-contact-item" href="%1$s"><span class="bornad-contact-icon">%2$s</span><span class="bornad-contact-text"><small>%3$s</small><strong class="style_2_ph">%4$s</strong></span></a>',
            esc_url((string) $args['href']),
            $icon_html,
            esc_html((string) $args['small_text']),
            esc_html((string) $args['value_text'])
        );
    }
}

$original_layout_style = isset($adforest_theme['ad_layout_style']) ? $adforest_theme['ad_layout_style'] : null;
$original_layout_flag  = isset($adforest_theme['bornado_ad_layout_bornad_style_active']) ? $adforest_theme['bornado_ad_layout_bornad_style_active'] : null;

// Keep style-2-specific branches for shared partials like slider and watermark.
$adforest_theme['ad_layout_style'] = '2';
$adforest_theme['bornado_ad_layout_bornad_style_active'] = '1';

$pid           = get_the_ID();
$poster_id     = get_post_field('post_author', $pid);
$user_info     = get_userdata($poster_id);
$poster_name   = get_post_meta($pid, '_adforest_poster_name', true);
$user_pic      = adforest_get_user_dp($poster_id);
$user_type     = '';
$contact_num   = get_post_meta($pid, '_adforest_poster_contact', true);
$ad_status     = get_post_meta($pid, '_adforest_ad_status_', true);
$ad_website    = get_post_meta($pid, '_adforest_ad_website', true);
$ad_tagline    = get_post_meta($pid, '_adforest_ad_tagline', true) ?? '';
$ad_type_terms = wp_get_post_terms($pid, 'ad_type');
$ad_type       = (!is_wp_error($ad_type_terms) && !empty($ad_type_terms) && isset($ad_type_terms[0]->name))
    ? (string) $ad_type_terms[0]->name
    : (get_post_meta($pid, '_adforest_ad_type', true) ?? '');
$ad_location   = get_post_meta($pid, '_adforest_ad_location', true);
$show_ad_id    = isset($adforest_theme['sb_show_ad_id']) ? $adforest_theme['sb_show_ad_id'] : '';
$allow_whatsapp = $adforest_theme['sb_ad_whatsapp_chat'] ?? false;
$allow_sb_chat = $adforest_theme['sb_ad_sbchat_chat'] ?? false;
$horizontal_ad = $adforest_theme['style_ad_720_1'] ?? '';
$horizontal_ad_2 = $adforest_theme['style_ad_720_2'] ?? '';
$registration_date = ($user_info && isset($user_info->user_registered)) ? $user_info->user_registered : '';
$is_phone_verified = get_user_meta($poster_id, '_sb_is_ph_verified', true);
$verified_class = ('1' === (string) $is_phone_verified) ? 'verified' : '';
$cat_link_page = isset($adforest_theme['cat_and_location']) ? $adforest_theme['cat_and_location'] : 'search';

if ('Indiviual' === get_user_meta($poster_id, '_sb_user_type', true)) {
    $user_type = esc_html__('Individual', 'adforest');
} elseif ('Dealer' === get_user_meta($poster_id, '_sb_user_type', true)) {
    $user_type = esc_html__('Dealer', 'adforest');
}

if ('' === $poster_name && $user_info) {
    $poster_name = $user_info->display_name;
}

if ('' === $contact_num) {
    $contact_num = get_user_meta($poster_id, '_sb_contact', true);
}

$poster_email = ($user_info && isset($user_info->user_email)) ? strtolower(trim((string) $user_info->user_email)) : '';
$contact_method_statuses = function_exists('bornado_get_user_contact_method_statuses')
    ? (array) bornado_get_user_contact_method_statuses($poster_id)
    : array();
$has_custom_contact_methods = function_exists('bornado_has_ad_contact_methods')
    ? (bool) bornado_has_ad_contact_methods($pid)
    : false;
$selected_contact_methods = $has_custom_contact_methods && function_exists('bornado_get_ad_contact_methods')
    ? (array) bornado_get_ad_contact_methods($pid)
    : array();

$communication_mode    = $adforest_theme['communication_mode'] ?? 'both';
$adforest_post_ad_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page'] ?? '');
$ad_update_url         = '';
$ad_update_source      = 'legacy-post-page';
if (!empty($adforest_post_ad_page)) {
    $legacy_post_ad_page_url = get_permalink($adforest_post_ad_page);
    if (is_string($legacy_post_ad_page_url) && '' !== $legacy_post_ad_page_url) {
        $ad_update_url = add_query_arg('id', $pid, $legacy_post_ad_page_url);
    }
}
$modern_post_ad_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? (int) $adforest_theme['sb_modern_post_ad_page'] : 0;
if ($modern_post_ad_page_id > 0) {
    $translated_modern_post_ad_page_id = (int) apply_filters('adforest_language_page_id', $modern_post_ad_page_id);
    if ($translated_modern_post_ad_page_id > 0) {
        $modern_post_ad_page_id = $translated_modern_post_ad_page_id;
        $ad_update_source = 'modern-option-translated';
    } else {
        $ad_update_source = 'modern-option';
    }
}
if ($modern_post_ad_page_id < 1) {
    $bornado_modern_post_pages = get_posts(array(
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
    if (!empty($bornado_modern_post_pages)) {
        $modern_post_ad_page_id = (int) $bornado_modern_post_pages[0];
        $ad_update_source       = 'modern-template-fallback';
    }
}
if ($modern_post_ad_page_id > 0) {
    $modern_post_ad_page_url = get_permalink($modern_post_ad_page_id);
    if (is_string($modern_post_ad_page_url) && '' !== $modern_post_ad_page_url) {
        $ad_update_url = add_query_arg('id', $pid, $modern_post_ad_page_url);
        $ad_update_source = 'modern-url-active';
    }
}
$posted_time           = get_the_time('U', $pid);
$ad_posted_date        = adforest_get_ad_posted_date($posted_time);
$ad_views              = (int) get_post_meta($pid, 'adforest_ad_views', true);
$formatted_views       = ($ad_views >= 1000) ? number_format($ad_views / 1000, 1) . 'K' : $ad_views;
$is_price_enabled      = is_ad_price_enabled($pid);
$price_type            = get_post_meta($pid, '_adforest_ad_price_type', true);
$price_html            = '';
$price_amount_html     = '';
$price_type_html       = '';
$adf_bidding_gate_ok   = function_exists('adforest_should_show_bidding') ? adforest_should_show_bidding($pid) : true;
$is_bidable            = $adf_bidding_gate_ok ? get_post_meta($pid, '_adforest_ad_bidding', true) : 0;
$recently_viewed_html  = '';
$current_user_id       = get_current_user_id();
$claim_enabled         = !empty($adforest_theme['allow_claim']);
$claim_is_logged_in    = is_user_logged_in();
$claim_is_owner        = $claim_is_logged_in && ((int) $current_user_id === (int) $poster_id);
$claim_is_claimed      = function_exists('bornado_is_ad_claim_already_approved')
    ? bornado_is_ad_claim_already_approved($pid)
    : false;
$claim_existing_id     = ($claim_is_logged_in && function_exists('bornado_get_existing_claim_post_id'))
    ? bornado_get_existing_claim_post_id($pid, $current_user_id)
    : 0;
$claim_login_page      = apply_filters('adforest_language_page_id', $adforest_theme['sb_sign_in_page'] ?? '');
$claim_login_url       = '';
$current_page_url      = function_exists('adforest_get_current_url')
    ? (string) adforest_get_current_url()
    : (string) get_permalink($pid);
$guest_login_url       = bornado_get_safe_login_redirect_url($current_page_url);
$phone_login_required  = function_exists('adforest_showPhone_to_users')
    ? (bool) adforest_showPhone_to_users()
    : false;
$phone_icon_url        = trailingslashit(get_template_directory_uri()) . 'images/phone-icon.svg';
$whatsapp_icon_url     = trailingslashit(get_template_directory_uri()) . 'images/whatsapp-icon.svg';
$masked_num            = (strlen((string) $contact_num) > 5) ? substr((string) $contact_num, 0, -5) . str_repeat('x', 5) : (string) $contact_num;
$masked_email          = bornado_mask_email_address($poster_email);
$show_custom_phone     = $has_custom_contact_methods
    && in_array('phone', $selected_contact_methods, true)
    && !empty($contact_method_statuses['phone']['enabled'])
    && '' !== (string) $contact_num;
$show_custom_whatsapp  = $has_custom_contact_methods
    && in_array('whatsapp', $selected_contact_methods, true)
    && !empty($contact_method_statuses['whatsapp']['enabled'])
    && '' !== (string) $contact_num;
$show_custom_email     = $has_custom_contact_methods
    && in_array('email', $selected_contact_methods, true)
    && !empty($contact_method_statuses['email']['enabled'])
    && '' !== $poster_email;
$has_custom_contact_list = $show_custom_phone || $show_custom_whatsapp || $show_custom_email;
$sb_plugin_options     = get_option('sb_plugin_options', array());
$sb_chat_feature_active = class_exists('SB_Chat')
    && '1' === (string) $allow_sb_chat
    && isset($sb_plugin_options['sbChat-active'])
    && 1 == $sb_plugin_options['sbChat-active']
    && class_exists('SB_Chat_Setting_Page');
$show_toolbar_chat = $sb_chat_feature_active
    && $has_custom_contact_methods
    && in_array('site_message', $selected_contact_methods, true);
$claim_contact_value   = $claim_is_logged_in ? (string) get_user_meta($current_user_id, '_sb_contact', true) : '';
$smart_claim_context   = function_exists('bornado_get_ad_ownership_claim_context')
    ? (array) bornado_get_ad_ownership_claim_context($pid)
    : array();
$claim_uses_phone_flow = !empty($smart_claim_context['has_phone']);

if (!$claim_is_logged_in && !empty($claim_login_page)) {
    $claim_login_url = $guest_login_url;
}

if (isset($adforest_theme['sb_show_recently_viewed_on_ad_detail']) && 1 == $adforest_theme['sb_show_recently_viewed_on_ad_detail']) {
    $has_viewed_before = is_recently_viewed_ad_post($pid);
    add_recently_viewed_ad_post($pid);
    if ($has_viewed_before) {
        $recently_viewed_html = adforest_recently_viewed_ad($pid);
    }
} else {
    update_post_meta($pid, 'adforest_ad_views', $ad_views + 1);
}

if ($is_price_enabled) {
    $price_html = adforest_adPrice($pid, 'negotiable-single', '');

    if ('' !== $price_html) {
        if (preg_match('/<small>(.*)<\/small>/Us', $price_html, $price_type_match)) {
            $price_type_html = trim($price_type_match[1]);
        }
        $price_amount_html = trim((string) preg_replace('/<small>.*<\/small>/Us', '', $price_html));
        if ('' === $price_amount_html) {
            $price_amount_html = $price_html;
        }
    }

    if (function_exists('bornado_ad_has_numeric_price_value') && bornado_ad_has_numeric_price_value($pid) && '' !== $price_type && 'no_price' !== $price_type) {
        if ('Fixed' === $price_type) {
            $price_type_label = __('Fixed', 'adforest');
        } elseif ('Negotiable' === $price_type) {
            $price_type_label = __('Negotiable', 'adforest');
        } elseif ('auction' === $price_type) {
            $price_type_label = __('Auction', 'adforest');
        } else {
            $price_type_label = $price_type;
            if (!empty($adforest_theme['sb_price_types_more'])) {
                $price_type_label = str_replace('_', ' ', $price_type_label);
            }
        }

        $price_type_html = '<span class="negotiable-single">(' . esc_html($price_type_label) . ')</span>';
    }
}

$ad_selected_warranty = wp_get_post_terms($pid, 'ad_warranty');
$ad_warranty_name     = (!is_wp_error($ad_selected_warranty) && !empty($ad_selected_warranty) && isset($ad_selected_warranty[0]->name))
    ? $ad_selected_warranty[0]->name
    : '';

$ad_selected_condition = wp_get_post_terms($pid, 'ad_condition');
$ad_condition_name     = (!is_wp_error($ad_selected_condition) && !empty($ad_selected_condition) && isset($ad_selected_condition[0]->name))
    ? $ad_selected_condition[0]->name
    : '';

$ad_condition_val = wp_get_post_terms($pid, 'ad_condition');
$ad_condition_val = (!is_wp_error($ad_condition_val) && !empty($ad_condition_val) && isset($ad_condition_val[0]->name))
    ? $ad_condition_val[0]->name
    : get_post_meta($pid, '_adforest_ad_condition', true);

$ad_details            = get_ad_post_details($pid);
$ad_category_selected  = isset($ad_details['categories']) && is_array($ad_details['categories']) ? $ad_details['categories'] : array();
$ad_country_selected   = isset($ad_details['countries']) && is_array($ad_details['countries']) ? $ad_details['countries'] : array();
$category_links        = array();
$country_links         = array();
$country_summary_value = '';

foreach ($ad_category_selected as $category) {
    $category_url = adforest_cat_link_page($category->term_id, $cat_link_page, 'ad_cats');
    if (!is_wp_error($category_url)) {
        $category_links[] = '<a class="bornad-breadcrumb-link" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
    }
}

foreach ($ad_country_selected as $country) {
    $country_url = adforest_cat_link_page($country->term_id, $cat_link_page, 'ad_country');
    if (!is_wp_error($country_url)) {
        $country_links[] = '<a class="bornad-country-link" href="' . esc_url($country_url) . '">' . esc_html($country->name) . '</a>';
    }
}

if (!empty($ad_country_selected)) {
    $last_country = end($ad_country_selected);
    if ($last_country instanceof WP_Term && isset($last_country->name)) {
        $country_summary_value = (string) $last_country->name;
    } elseif (is_object($last_country) && isset($last_country->name)) {
        $country_summary_value = (string) $last_country->name;
    }
}

$category_links_string = implode('<span class="bornad-breadcrumb-sep">/</span>', $category_links);
$country_links_string  = implode('<span class="bornad-breadcrumb-sep">/</span>', $country_links);

$custom_fields_html  = function_exists('adforestCustomFieldsHTML') ? (string) adforestCustomFieldsHTML($pid, '', 'style-6') : '';
$custom_field_items  = bornado_extract_style6_field_items($custom_fields_html);
$fallback_summary    = array();

if ('' !== $ad_type) {
    $fallback_summary[] = array(
        'label'      => esc_html__('Type', 'adforest'),
        'value_html' => esc_html($ad_type),
    );
}
if ('' !== $ad_condition_name) {
    $fallback_summary[] = array(
        'label'      => esc_html__('Condition', 'adforest'),
        'value_html' => esc_html($ad_condition_name),
    );
}
if ('' !== $ad_warranty_name) {
    $fallback_summary[] = array(
        'label'      => esc_html__('Warranty', 'adforest'),
        'value_html' => esc_html($ad_warranty_name),
    );
}
if ('' !== $country_summary_value) {
    $fallback_summary[] = array(
        'label'      => esc_html__('Location', 'adforest'),
        'value_html' => esc_html($country_summary_value),
    );
}

$summary_items = bornado_build_summary_field_items($custom_field_items, $fallback_summary, 4);
$detail_field_items = bornado_filter_remaining_field_items($custom_field_items, $summary_items);

$adf_show_ad_720_1 = function_exists('adforest_has_visible_ad_content')
    ? adforest_has_visible_ad_content('style_ad_720_1')
    : ('' !== $horizontal_ad);

$adf_show_ad_720_2 = function_exists('adforest_has_visible_ad_content')
    ? adforest_has_visible_ad_content('style_ad_720_2')
    : ('' !== $horizontal_ad_2);
?>
<section class="bornad-ad-detail-section">
    <div class="container bornad-ad-detail-container">
        <div class="row bornad-detail-layout">
            <div class="col-lg-7 bornad-detail-main">
                <div class="bornad-card bornad-gallery-card" id="adt-ad-detail-top-box">
                    <div class="bornad-card-body">
                        <?php get_template_part('template-parts/layouts/ad-style/ad-img', 'carousel'); ?>
                        <?php get_template_part('template-parts/layouts/ad-style/status', 'watermark'); ?>
                    </div>
                </div>

                <div class="bornad-card bornad-seller-card bornad-seller-card--desktop" id="bornad-contact-panel">
                    <div class="bornad-card-header">
                        <h3>اطلاعات فروشنده</h3>
                    </div>
                    <div class="bornad-card-body">
                        <div class="bornad-seller-top">
                            <div class="bornad-seller-avatar">
                                <a href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')); ?>">
                                    <img src="<?php echo esc_attr($user_pic); ?>" alt="<?php echo esc_attr($poster_name); ?>">
                                </a>
                            </div>
                            <div class="bornad-seller-meta">
                                <?php if ('' !== $user_type) { ?>
                                    <span class="bornad-seller-type"><?php echo esc_html($user_type); ?></span>
                                <?php } ?>
                                <h4>
                                    <a href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')); ?>">
                                        <?php echo esc_html($poster_name); ?>
                                    </a>
                                    <?php if ('1' === (string) $is_phone_verified) { ?>
                                        <span class="bornad-verified <?php echo esc_attr($verified_class); ?>">
                                            <i class="fas fa-check" aria-hidden="true"></i>
                                        </span>
                                    <?php } ?>
                                </h4>
                                <?php if ('' !== $registration_date) { ?>
                                    <p><?php printf(esc_html__('Member Since %s', 'adforest'), esc_html(date_i18n(get_option('date_format'), strtotime($registration_date)))); ?></p>
                                <?php } ?>
                                <a class="bornad-seller-link" href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')); ?>">
                                    <?php echo esc_html__('View All Ads', 'adforest'); ?>
                                </a>
                            </div>
                        </div>

                        <?php if (!empty($ad_location)) { ?>
                            <div class="bornad-seller-location">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <span><?php echo esc_html($ad_location); ?></span>
                            </div>
                        <?php } ?>

                        <?php
                        if ($has_custom_contact_methods) {
                            if ($has_custom_contact_list) {
                                ?>
                                <div class="bornad-contact-list">
                                    <?php
                                    if ($show_custom_phone) {
                                        echo bornado_render_contact_item(array(
                                            'icon_image' => $phone_icon_url,
                                            'icon_alt' => 'phone',
                                            'small_text' => $phone_login_required ? __('Login to View', 'adforest') : __('Click To Show', 'adforest'),
                                            'value_text' => $masked_num,
                                            'href' => $phone_login_required ? $guest_login_url : 'javascript:void(0)',
                                            'reveal' => !$phone_login_required,
                                            'post_id' => $pid,
                                        ));
                                    }

                                    if ($show_custom_whatsapp) {
                                        echo bornado_render_contact_item(array(
                                            'icon_image' => $whatsapp_icon_url,
                                            'icon_alt' => 'whatsapp',
                                            'small_text' => $phone_login_required ? __('Login to View', 'adforest') : __('Click To Show', 'adforest'),
                                            'value_text' => $masked_num,
                                            'href' => $phone_login_required ? $guest_login_url : 'javascript:void(0)',
                                            'reveal' => !$phone_login_required,
                                            'post_id' => $pid,
                                        ));
                                    }

                                    if ($show_custom_email) {
                                        echo bornado_render_contact_item(array(
                                            'icon_class' => 'far fa-envelope',
                                            'small_text' => $phone_login_required ? __('Login to View', 'adforest') : 'ایمیل',
                                            'value_text' => $phone_login_required ? $masked_email : $poster_email,
                                            'href' => $phone_login_required ? $guest_login_url : 'mailto:' . $poster_email,
                                            'reveal' => false,
                                        ));
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                        } elseif (($communication_mode == 'both' || $communication_mode == 'phone') && '' !== $contact_num) {
                            $requires_login = $phone_login_required;
                            $call_now = '#';
                            if ($requires_login) {
                                $call_now = $guest_login_url;
                            }
                            ?>
                            <div class="bornad-contact-list">
                                <?php if ($requires_login) { ?>
                                    <a class="bornad-contact-item" href="<?php echo esc_url($call_now); ?>">
                                        <span class="bornad-contact-icon"><img src="<?php echo esc_url($phone_icon_url); ?>" alt="phone"></span>
                                        <span class="bornad-contact-text">
                                            <small><?php esc_html_e('Login to View', 'adforest'); ?></small>
                                            <strong class="style_2_ph"><?php echo esc_html($masked_num); ?></strong>
                                        </span>
                                    </a>
                                <?php } else { ?>
                                    <div class="bornad-contact-item bornad-contact-item--reveal">
                                        <span class="bornad-contact-icon"><img src="<?php echo esc_url($phone_icon_url); ?>" alt="phone"></span>
                                        <span class="bornad-contact-text">
                                            <small class="toggle-contact-number" style="cursor:pointer;" data-ad-id="<?php echo intval($pid); ?>"><?php esc_html_e('Click To Show', 'adforest'); ?></small>
                                            <a class="style_2_ph" href="javascript:void(0)"><?php echo esc_html($masked_num); ?></a>
                                        </span>
                                    </div>
                                <?php } ?>

                            </div>
                        <?php } ?>
                    </div>
                </div>

                <?php if (!empty($adforest_theme['tips_title']) && !empty($adforest_theme['tips_for_ad'])) { ?>
                    <div class="bornad-card bornad-extra-card bornad-tips-card">
                        <div class="bornad-card-header">
                            <h3><?php echo wp_kses_post($adforest_theme['tips_title']); ?></h3>
                        </div>
                        <div class="bornad-card-body">
                            <?php echo wp_kses_post($adforest_theme['tips_for_ad']); ?>
                        </div>
                    </div>
                <?php } ?>

                <?php if (isset($adforest_theme['allow_lat_lon']) && $adforest_theme['allow_lat_lon']) {
                    $map_lat = get_post_meta($pid, '_adforest_ad_map_lat', true);
                    $map_long = get_post_meta($pid, '_adforest_ad_map_long', true);
                    ?>
                    <div id="adt-ad-location-box" class="bornad-card bornad-map-card">
                        <div class="bornad-card-header">
                            <div>
                                <h3>موقعیت آگهی</h3>
                                <?php if (!empty($ad_location)) { ?>
                                    <p><?php echo esc_html($ad_location); ?></p>
                                <?php } ?>
                            </div>
                            <a href="#adt-ad-location-box" class="bornad-map-link">نمایش روی نقشه</a>
                        </div>
                        <div class="bornad-card-body">
                            <?php
                            if ('' !== $map_lat && '' !== $map_long) {
                                ?>
                                <div id="itemMap" style="width: 100%; height: 360px; margin-bottom: 0;"></div>
                                <input type="hidden" id="ad_lat" value="<?php echo esc_attr($map_lat); ?>" />
                                <input type="hidden" id="ad_lon" value="<?php echo esc_attr($map_long); ?>" />
                            <?php } else {
                                $res_arr = adforest_get_latlon($ad_location);
                                if (isset($res_arr) && count($res_arr) > 0) {
                                    ?>
                                    <div id="itemMap" style="width: 100%; height: 360px; margin-bottom: 0;"></div>
                                    <input type="hidden" id="ad_lat" value="<?php echo esc_attr($res_arr[0]); ?>" />
                                    <input type="hidden" id="ad_lon" value="<?php echo esc_attr($res_arr[1]); ?>" />
                                <?php }
                            }
                            ?>
                        </div>
                    </div>
                <?php } ?>

                <?php
                if (get_post_meta($pid, '_adforest_ad_yvideo', true) != '') {
                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', get_post_meta($pid, '_adforest_ad_yvideo', true), $match);
                    if (isset($match[1]) && '' !== $match[1]) {
                        $video_id = $match[1];
                        ?>
                        <div class="bornad-card bornad-video-card">
                            <div class="bornad-card-header">
                                <h3>ویدئوی آگهی</h3>
                            </div>
                            <div class="bornad-card-body">
                                <?php
                                $iframe = 'iframe';
                                echo '<' . $iframe . ' width="560" height="420" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></' . $iframe . '>';
                                ?>
                            </div>
                        </div>
                    <?php
                    }
                }
                ?>
            </div>

            <div class="col-lg-5 bornad-detail-sidebar">
                <div class="bornad-card bornad-summary-card">
                    <div class="bornad-card-body">
                        <?php if ('' !== $category_links_string) { ?>
                            <div class="bornad-breadcrumbs">
                                <?php echo wp_kses_post($category_links_string); ?>
                            </div>
                        <?php } ?>

                        <h1 class="bornad-ad-title"><?php echo esc_html(get_the_title()); ?></h1>

                        <?php if ('' !== $ad_tagline) { ?>
                            <p class="bornad-ad-tagline"><?php echo esc_html($ad_tagline); ?></p>
                        <?php } ?>

                        <div class="bornad-meta-line">
                            <?php if (!empty($ad_location)) { ?>
                                <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i><?php echo esc_html($ad_location); ?></span>
                            <?php } ?>
                            <span><i class="far fa-calendar-alt" aria-hidden="true"></i><?php echo esc_html($ad_posted_date); ?></span>
                            <span><i class="far fa-eye" aria-hidden="true"></i><?php echo esc_html($formatted_views); ?></span>
                        </div>

                        <?php if (isset($adforest_theme['sb_ad_rating']) && $adforest_theme['sb_ad_rating']) { ?>
                            <div class="bornad-rating-box">
                                <?php get_template_part('template-parts/layouts/ad-style/ad', 'rating'); ?>
                            </div>
                        <?php } ?>

                        <div class="bornad-toolbar">
                            <?php if ($claim_enabled) { ?>
                                <a
                                    class="bornad-toolbar-button bornad-toolbar-button--claim"
                                    data-bs-target=".bornad-claim-modal"
                                    data-bs-toggle="modal"
                                    href="javascript:void(0);"
                                    title="<?php echo esc_attr($claim_uses_phone_flow ? 'احراز مالکیت با شماره تاییدشده' : __('احراز مالکیت آگهی', 'adforest')); ?>"
                                >
                                    <i class="far fa-id-card" aria-hidden="true"></i>
                                    <span><?php echo esc_html($claim_uses_phone_flow ? 'احراز مالکیت با شماره' : 'احراز مالکیت آگهی'); ?></span>
                                </a>
                            <?php } ?>

                            <?php
                            if ($show_toolbar_chat) {
                                echo do_shortcode(
                                    '[sb_chat_shortcode_popup '
                                        . 'class="bornad-toolbar-button bornad-toolbar-chat" '
                                        . 'button_title="' . esc_attr__('گفتگو', 'adforest') . '" '
                                        . 'post_author_id="' . absint($poster_id) . '" '
                                        . 'post_id="' . absint($pid) . '" '
                                        . 'icon="far fa-comment-alt"'
                                        . ']'
                                );
                            }
                            ?>

                            <a class="bornad-toolbar-button" href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')); ?>" title="<?php echo esc_attr__('مشاهده همه آگهی‌های این کاربر', 'adforest'); ?>">
                                <i class="far fa-user" aria-hidden="true"></i>
                            </a>

                            <?php if (isset($adforest_theme['share_ads_on']) && $adforest_theme['share_ads_on']) { ?>
                                <a class="bornad-toolbar-button" data-bs-toggle="modal" data-bs-target=".share-ad" data-adid="<?php echo esc_attr(get_the_ID()); ?>" href="javascript:void(0);" title="<?php echo esc_attr__('اشتراک‌گذاری آگهی', 'adforest'); ?>">
                                    <i class="fas fa-share-alt" aria-hidden="true"></i>
                                </a>
                            <?php } ?>

                            <?php
                            $is_fav      = (get_user_meta(get_current_user_id(), '_sb_fav_id_' . $pid, true) == $pid);
                            $heart_class = $is_fav ? 'fas fa-heart text-danger' : 'far fa-heart';
                            $fav_title   = $is_fav ? esc_html__('Click to remove from favourite', 'adforest') : esc_html__('Click to make it favourite', 'adforest');
                            $fav_extra   = $is_fav ? ' ad-favourited' : '';
                            ?>
                            <a class="bornad-toolbar-button ad_to_fav<?php echo esc_attr($fav_extra); ?>" data-adid="<?php echo esc_attr(get_the_ID()); ?>" href="javascript:void(0);" title="<?php echo esc_attr($fav_title); ?>" aria-label="<?php echo esc_attr($fav_title); ?>">
                                <i class="<?php echo esc_attr($heart_class); ?>" aria-hidden="true"></i>
                            </a>

                            <a class="bornad-toolbar-button" data-bs-target=".report-quote" data-bs-toggle="modal" data-adid="<?php echo esc_attr(get_the_ID()); ?>" href="javascript:void(0);" title="<?php echo esc_attr__('گزارش آگهی', 'adforest'); ?>">
                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            </a>
                        </div>

                        <?php if ('' !== $price_amount_html || !empty($summary_items)) { ?>
                            <div class="bornad-summary-list">
                                <?php if ('' !== $price_amount_html) { ?>
                                    <div class="bornad-summary-item bornad-summary-item--price">
                                        <span class="bornad-summary-label">قیمت</span>
                                        <strong class="bornad-summary-value bornad-summary-value--price">
                                            <?php if ('' !== $price_type_html) { ?>
                                                <span class="bornad-price-type"><?php echo wp_kses_post($price_type_html); ?></span>
                                            <?php } ?>
                                            <span class="bornad-price-amount"><?php echo wp_kses_post($price_amount_html); ?></span>
                                        </strong>
                                    </div>
                                <?php } ?>
                                <?php foreach ($summary_items as $summary_item) { ?>
                                    <div class="bornad-summary-item">
                                        <span class="bornad-summary-label"><?php echo esc_html($summary_item['label']); ?></span>
                                        <strong class="bornad-summary-value"><?php echo wp_kses_post($summary_item['value_html']); ?></strong>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <?php if ($show_ad_id) { ?>
                            <div class="bornad-ad-id">کد آگهی: <?php echo esc_html(get_the_ID()); ?></div>
                        <?php } ?>

                        <?php if ((current_user_can('administrator') || get_current_user_id() == $poster_id) && '' !== $ad_update_url) { ?>
                            <a
                                class="bornad-edit-link"
                                href="<?php echo esc_url($ad_update_url); ?>"
                                data-bornado-edit-marker="bornado-edit-v2"
                                data-bornado-edit-source="<?php echo esc_attr($ad_update_source); ?>"
                                data-bornado-edit-ad-id="<?php echo esc_attr($pid); ?>"
                            >
                                <?php echo esc_html__('ویرایش آگهی', 'adforest'); ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <?php if (!empty($detail_field_items)) { ?>
                    <div class="bornad-card bornad-details-card">
                        <div class="bornad-card-header">
                            <h3>جزئیات آگهی</h3>
                        </div>
                        <div class="bornad-card-body">
                            <ul class="bornad-detail-list" id="adt-ad-general-info-box">
                                <?php foreach ($detail_field_items as $custom_field_item) { ?>
                                    <li>
                                        <span><?php echo esc_html($custom_field_item['label']); ?></span>
                                        <strong><?php echo wp_kses_post($custom_field_item['value_html']); ?></strong>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>

                <div class="bornad-card bornad-description-card" id="adt-ad-description-box">
                    <div class="bornad-card-header">
                        <h3>توضیحات</h3>
                    </div>
                    <div class="bornad-card-body">
                        <div class="bornad-description-content">
                            <?php echo wp_kses_post(get_the_content()); ?>
                        </div>
                        <?php do_action('adforest_owner_text'); ?>
                        <?php get_template_part('template-parts/layouts/ad-style/ad', 'tags'); ?>
                    </div>
                </div>

                <?php
                if (isset($adforest_theme['sb_enable_comments_offer']) && $adforest_theme['sb_enable_comments_offer'] && $adf_bidding_gate_ok) {
                    if (0 != $is_bidable && '0' !== $is_bidable) {
                        $bids_res = adforest_get_all_biddings_array($pid);
                        $total_bids = count($bids_res);
                        $bid_container_height = ($total_bids > 3) ? 'height: 400px;' : '';
                        $max = 0;
                        $min = 0;

                        if ($total_bids > 0) {
                            $max = max($bids_res);
                            $min = min($bids_res);
                        }

                        $thousands_sep = isset($adforest_theme['sb_price_separator']) && '' !== $adforest_theme['sb_price_separator'] ? $adforest_theme['sb_price_separator'] : ',';
                        $decimals = isset($adforest_theme['sb_price_decimals']) && '' !== $adforest_theme['sb_price_decimals'] ? $adforest_theme['sb_price_decimals'] : 0;
                        $decimals_separator = isset($adforest_theme['sb_price_decimals_separator']) && '' !== $adforest_theme['sb_price_decimals_separator'] ? $adforest_theme['sb_price_decimals_separator'] : '.';
                        $curreny = !empty(get_post_meta($pid, '_adforest_ad_currency', true)) ? get_post_meta($pid, '_adforest_ad_currency', true) : $adforest_theme['sb_currency'];

                        $max = number_format((int) $max, $decimals, $decimals_separator, $thousands_sep);
                        $min = number_format((int) $min, $decimals, $decimals_separator, $thousands_sep);

                        if (isset($adforest_theme['sb_price_direction']) && 'right' === $adforest_theme['sb_price_direction']) {
                            $max .= '<small>' . $curreny . '</small>';
                            $min .= '<small>' . $curreny . '</small>';
                        } elseif (isset($adforest_theme['sb_price_direction']) && 'right_with_space' === $adforest_theme['sb_price_direction']) {
                            $max .= ' <small>' . $curreny . '</small>';
                            $min .= ' <small>' . $curreny . '</small>';
                        } else {
                            $max = '<small>' . $curreny . '</small>' . $max;
                            $min = '<small>' . $curreny . '</small>' . $min;
                        }
                        ?>
                        <div class="bornad-card bornad-bids-card" id="adt-ad-biding-box">
                            <div class="bornad-card-header">
                                <h3><?php echo esc_html__('Bidding State', 'adforest'); ?></h3>
                            </div>
                            <div class="bornad-card-body">
                                <div class="bid-detail-wrapper">
                                    <div class="bid-detail-box purple">
                                        <small><?php echo esc_html__('Total Bids', 'adforest'); ?></small>
                                        <span><?php echo esc_html($total_bids); ?></span>
                                    </div>
                                    <div class="bid-detail-box green">
                                        <small><?php echo esc_html__('Highest bid', 'adforest'); ?></small>
                                        <span><?php echo wp_kses_post($max); ?></span>
                                    </div>
                                    <div class="bid-detail-box yellow">
                                        <small><?php echo esc_html__('Lowest bid', 'adforest'); ?></small>
                                        <span><?php echo wp_kses_post($min); ?></span>
                                    </div>
                                </div>
                                <div class="bids-list-wrapper" style="<?php echo esc_attr($bid_container_height); ?>">
                                    <ul class="bids-list">
                                        <?php
                                        arsort($bids_res);
                                        if ($total_bids > 0) {
                                            foreach ($bids_res as $key => $val) {
                                                $data        = explode('_', $key);
                                                $bidder_id   = $data[0] ?? '';
                                                $bid_date    = $data[1] ?? '';
                                                $bid_comment = $data[2] ?? '';
                                                $bidder_info = get_userdata($bidder_id);

                                                if (!isset($bidder_info->display_name) || '' === $bidder_info->display_name) {
                                                    continue;
                                                }

                                                $bidder_name = $bidder_info->display_name;
                                                $val         = substr($val, 0, 12);
                                                $bidder_pic  = adforest_get_user_dp($bidder_id, 'adforest-single-small');

                                                if (isset($adforest_theme['sb_price_direction']) && 'right' === $adforest_theme['sb_price_direction']) {
                                                    $offer = $val . '<small>' . $curreny . '</small>';
                                                } elseif (isset($adforest_theme['sb_price_direction']) && 'left' === $adforest_theme['sb_price_direction']) {
                                                    $offer = '<small>' . $curreny . '</small>' . $val;
                                                } elseif (isset($adforest_theme['sb_price_direction']) && 'right_with_space' === $adforest_theme['sb_price_direction']) {
                                                    $offer = $val . ' <small>' . $curreny . '</small>';
                                                } elseif (isset($adforest_theme['sb_price_direction']) && 'left_with_space' === $adforest_theme['sb_price_direction']) {
                                                    $offer = '<small>' . $curreny . '</small> ' . $val;
                                                } else {
                                                    $offer = '<small>' . $curreny . '</small>' . $val;
                                                }
                                                ?>
                                                <li>
                                                    <div class="bid-box">
                                                        <div class="user-img">
                                                            <a class="m-0" href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($bidder_id), 'type', 'ads')); ?>">
                                                                <img src="<?php echo esc_url($bidder_pic); ?>" alt="<?php echo esc_attr($bidder_name); ?>">
                                                            </a>
                                                        </div>
                                                        <div class="user-content">
                                                            <h4><?php echo esc_html($bidder_name); ?></h4>
                                                            <div class="price"><?php echo wp_kses_post($offer); ?></div>
                                                            <ul>
                                                                <li>
                                                                    <i class="fas fa-calendar-week" aria-hidden="true"></i><?php echo esc_html(adforest_timeago($bid_date)); ?>
                                                                </li>
                                                            </ul>
                                                            <?php if ('' !== $bid_comment) { ?>
                                                                <p><?php echo esc_html($bid_comment); ?></p>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php
                                            }
                                        } else {
                                            ?>
                                            <li class="bornad-empty-bids">
                                                <div class="alert alert-warning" role="alert">
                                                    <?php echo wp_kses_post(__('<strong>No bids yet!</strong> This ad has not received any bids.', 'adforest')); ?>
                                                </div>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>

                                <?php
                                $bid_date = get_post_meta($pid, '_adforest_ad_bidding_date', true);
                                if (!$bid_date) {
                                    $bid_date = date('Y-m-d', strtotime('+7 days', current_time('timestamp')));
                                }
                                ?>
                                <div id="adt-ad-create-bid" class="adt-ad-create-bid">
                                    <?php
                                    $bid_end_date = get_post_meta($pid, '_adforest_ad_bidding_date', true);
                                    if ($bid_end_date != '' && date('Y-m-d H:i:s') > $bid_end_date && isset($adforest_theme['bidding_timer']) && $adforest_theme['bidding_timer']) {
                                        echo '<em>' . esc_html__('Bidding has been closed.', 'adforest') . '</em>';
                                    } else {
                                        $adf_paid_bidding    = !empty($adforest_theme['sb_make_bidding_paid']);
                                        $adf_current_user_id = get_current_user_id();
                                        $adf_user_credits    = $adf_current_user_id ? get_user_meta($adf_current_user_id, '_sb_paid_biddings', true) : '';
                                        $adf_needs_package   = ($adf_paid_bidding && $adf_current_user_id && ($adf_user_credits === '' || (string) $adf_user_credits === '0'));
                                        $adf_package_url     = function_exists('adforest_get_bidding_package_url') ? adforest_get_bidding_package_url() : home_url('/');
                                        if ($adf_needs_package) {
                                            ?>
                                            <div class="adt-bidding-package-cta">
                                                <a href="<?php echo esc_url($adf_package_url); ?>" class="adt-button-dark adt-buy-bidding-package"><?php echo esc_html__('Buy Bidding Package', 'adforest'); ?></a>
                                            </div>
                                        <?php } else { ?>
                                            <form role="form" id="sb_bid_ad"
                                                data-paid-bidding="<?php echo $adf_paid_bidding ? '1' : '0'; ?>"
                                                data-user-credits="<?php echo esc_attr((string) $adf_user_credits); ?>"
                                                data-package-url="<?php echo esc_url($adf_package_url); ?>">
                                                <div class="input-field-box">
                                                    <input name="bid_amount"
                                                        placeholder="<?php echo esc_attr__('Bid', 'adforest'); ?>"
                                                        type="text" data-parsley-required="true"
                                                        data-parsley-pattern="/^[0-9]+\.?[0-9]*$/"
                                                        data-parsley-error-message="<?php echo esc_attr__('only numbers allowed.', 'adforest'); ?>"
                                                        autocomplete="off" maxlength="12" />
                                                    <div class="input-box">
                                                        <input type="text" name="bid_comment" id="bid_comment" placeholder="<?php echo esc_attr__('Comment', 'adforest'); ?>">
                                                        <small><?php echo esc_html__('*Your phone number will be visible to the post author', 'adforest'); ?></small>
                                                    </div>
                                                    <button class="adt-button-dark"><?php echo esc_html__('Send Now', 'adforest'); ?></button>
                                                    <input type="hidden" name="ad_id" value="<?php echo esc_attr($pid); ?>" />
                                                    <input type="hidden" id="sb-bidding-token" value="<?php echo esc_attr(wp_create_nonce('sb_bidding_secure')); ?>" />
                                                </div>
                                            </form>
                                        <?php }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                }
                ?>

                <?php if (isset($adforest_theme['sb_ad_rating']) && $adforest_theme['sb_ad_rating']) { ?>
                    <div class="bornad-card bornad-reviews-card" id="adt-ad-review-box">
                        <div class="bornad-card-header">
                            <h3>بررسی و دیدگاه‌ها</h3>
                        </div>
                        <div class="bornad-card-body">
                            <?php get_template_part('template-parts/layouts/ad-style/ad', 'reviews'); ?>
                        </div>
                    </div>
                <?php } ?>

                <?php
                if (get_post_meta($pid, 'sb_pro_is_hours_allow', true) == '1' && class_exists('SbPro')) {
                    ?>
                    <div class="bornad-card bornad-extra-card">
                        <div class="bornad-card-body">
                            <?php echo apply_filters('sb_show_business_hours', $pid); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                <?php } ?>

                <?php
                $is_ad_booking_allow = get_post_meta($pid, 'is_ad_booking_allow', true);
                if (isset($is_ad_booking_allow) && '' !== $is_ad_booking_allow && class_exists('SbPro')) {
                    ?>
                    <div class="bornad-card bornad-extra-card">
                        <div class="bornad-card-body">
                            <?php echo apply_filters('sb_show_booking_option', $pid); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                <?php } ?>

                <?php
                if (is_active_sidebar('adforest_ad_sidebar_bottom')) {
                    echo '<div class="bornad-sidebar-bottom">';
                    dynamic_sidebar('adforest_ad_sidebar_bottom');
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <div class="bornad-card bornad-seller-card bornad-seller-card--mobile">
            <div class="bornad-card-header">
                <h3>اطلاعات فروشنده</h3>
            </div>
            <div class="bornad-card-body">
                <div class="bornad-seller-top">
                    <div class="bornad-seller-avatar">
                        <a href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')); ?>">
                            <img src="<?php echo esc_attr($user_pic); ?>" alt="<?php echo esc_attr($poster_name); ?>">
                        </a>
                    </div>
                    <div class="bornad-seller-meta">
                        <?php if ('' !== $user_type) { ?>
                            <span class="bornad-seller-type"><?php echo esc_html($user_type); ?></span>
                        <?php } ?>
                        <h4>
                            <a href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')); ?>">
                                <?php echo esc_html($poster_name); ?>
                            </a>
                            <?php if ('1' === (string) $is_phone_verified) { ?>
                                <span class="bornad-verified <?php echo esc_attr($verified_class); ?>">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                </span>
                            <?php } ?>
                        </h4>
                        <?php if ('' !== $registration_date) { ?>
                            <p><?php printf(esc_html__('Member Since %s', 'adforest'), esc_html(date_i18n(get_option('date_format'), strtotime($registration_date)))); ?></p>
                        <?php } ?>
                        <a class="bornad-seller-link" href="<?php echo esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')); ?>">
                            <?php echo esc_html__('View All Ads', 'adforest'); ?>
                        </a>
                    </div>
                </div>

                <?php if (!empty($ad_location)) { ?>
                    <div class="bornad-seller-location">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <span><?php echo esc_html($ad_location); ?></span>
                    </div>
                <?php } ?>

                <?php
                if ($has_custom_contact_methods) {
                    if ($has_custom_contact_list) {
                        ?>
                        <div class="bornad-contact-list">
                            <?php
                            if ($show_custom_phone) {
                                echo bornado_render_contact_item(array(
                                    'icon_image' => $phone_icon_url,
                                    'icon_alt' => 'phone',
                                    'small_text' => $phone_login_required ? __('Login to View', 'adforest') : __('Click To Show', 'adforest'),
                                    'value_text' => $masked_num,
                                    'href' => $phone_login_required ? $guest_login_url : 'javascript:void(0)',
                                    'reveal' => !$phone_login_required,
                                    'post_id' => $pid,
                                ));
                            }

                            if ($show_custom_whatsapp) {
                                echo bornado_render_contact_item(array(
                                    'icon_image' => $whatsapp_icon_url,
                                    'icon_alt' => 'whatsapp',
                                    'small_text' => $phone_login_required ? __('Login to View', 'adforest') : __('Click To Show', 'adforest'),
                                    'value_text' => $masked_num,
                                    'href' => $phone_login_required ? $guest_login_url : 'javascript:void(0)',
                                    'reveal' => !$phone_login_required,
                                    'post_id' => $pid,
                                ));
                            }

                            if ($show_custom_email) {
                                echo bornado_render_contact_item(array(
                                    'icon_class' => 'far fa-envelope',
                                    'small_text' => $phone_login_required ? __('Login to View', 'adforest') : 'ایمیل',
                                    'value_text' => $phone_login_required ? $masked_email : $poster_email,
                                    'href' => $phone_login_required ? $guest_login_url : 'mailto:' . $poster_email,
                                    'reveal' => false,
                                ));
                            }
                            ?>
                        </div>
                        <?php
                    }
                } elseif (($communication_mode == 'both' || $communication_mode == 'phone') && '' !== $contact_num) {
                    $requires_login = $phone_login_required;
                    $call_now = '#';
                    if ($requires_login) {
                        $call_now = $guest_login_url;
                    }
                    ?>
                    <div class="bornad-contact-list">
                        <?php if ($requires_login) { ?>
                            <a class="bornad-contact-item" href="<?php echo esc_url($call_now); ?>">
                                <span class="bornad-contact-icon"><img src="<?php echo esc_url($phone_icon_url); ?>" alt="phone"></span>
                                <span class="bornad-contact-text">
                                    <small><?php esc_html_e('Login to View', 'adforest'); ?></small>
                                    <strong class="style_2_ph"><?php echo esc_html($masked_num); ?></strong>
                                </span>
                            </a>
                        <?php } else { ?>
                            <div class="bornad-contact-item bornad-contact-item--reveal">
                                <span class="bornad-contact-icon"><img src="<?php echo esc_url($phone_icon_url); ?>" alt="phone"></span>
                                <span class="bornad-contact-text">
                                    <small class="toggle-contact-number" style="cursor:pointer;" data-ad-id="<?php echo intval($pid); ?>"><?php esc_html_e('Click To Show', 'adforest'); ?></small>
                                    <a class="style_2_ph" href="javascript:void(0)"><?php echo esc_html($masked_num); ?></a>
                                </span>
                            </div>
                        <?php } ?>

                    </div>
                <?php } ?>
            </div>
        </div>

        <?php if (isset($adforest_theme['Related_ads_on']) && '1' === (string) $adforest_theme['Related_ads_on']) { ?>
            <div class="bornad-related-wrap">
                <?php get_template_part('template-parts/layouts/ad-style/related', 'ads'); ?>
            </div>
        <?php } ?>
    </div>
</section>

<?php
if ($claim_enabled) {
    ?>
    <div class="modal fade bornad-claim-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">احراز مالکیت آگهی</div>
                    <button type="button" class="btn close" data-bs-dismiss="modal"><span aria-hidden="true">&#10005;</span><span class="sr-only"><?php echo __('Close', 'adforest'); ?></span></button>
                </div>
                <div class="modal-body">
                    <div class="bornad-claim-feedback" style="display:none;"></div>
                    <?php if ($claim_uses_phone_flow) { ?>
                        <p class="bornad-claim-note">
                            <?php
                            echo wp_kses(
                                $smart_claim_context['note'] ?? 'برای احراز مالکیت این آگهی باید با شماره ثبت‌شده روی آگهی وارد شوید.',
                                array(
                                    'bdi' => array(
                                        'dir' => true,
                                        'class' => true,
                                    ),
                                )
                            );
                            ?>
                        </p>
                        <div class="form-group col-md-12 col-sm-12">
                            <label>شماره ثبت‌شده روی آگهی</label>
                            <input type="text" class="form-control" value="<?php echo esc_attr($smart_claim_context['display_phone'] ?? ''); ?>" readonly>
                        </div>
                        <?php if ('ajax-transfer' === ($smart_claim_context['action_type'] ?? '')) { ?>
                            <div class="col-md-12 col-sm-12 margin-bottom-20 margin-top-20">
                                <button
                                    type="button"
                                    class="adt-button-dark btn-block"
                                    id="bornad-smart-claim-transfer"
                                    data-adid="<?php echo esc_attr($pid); ?>"
                                    data-default-text="<?php echo esc_attr($smart_claim_context['action_label'] ?? 'انتقال آگهی‌ها'); ?>"
                                >
                                    <?php echo esc_html($smart_claim_context['action_label'] ?? 'انتقال آگهی‌ها'); ?>
                                </button>
                            </div>
                        <?php } elseif (!empty($smart_claim_context['action_url'])) {
                            $claim_action_url = (string) $smart_claim_context['action_url'];
                            $claim_continue_token = '';
                            $claim_action_query = wp_parse_url($claim_action_url, PHP_URL_QUERY);
                            if (is_string($claim_action_query) && '' !== $claim_action_query) {
                                $claim_action_params = array();
                                parse_str($claim_action_query, $claim_action_params);
                                if (!empty($claim_action_params['bornado_continue_token'])) {
                                    $claim_continue_token = (string) $claim_action_params['bornado_continue_token'];
                                }
                            }
                            ?>
                            <a
                                class="adt-button-dark btn-block bornad-claim-action-link"
                                href="<?php echo esc_url($claim_action_url); ?>"
                                data-action-url="<?php echo esc_url($claim_action_url); ?>"
                                <?php if ('' !== $claim_continue_token) { ?>
                                    data-bornado-auth-open="1"
                                    data-mode="login"
                                    data-method="phone"
                                    data-continue-token="<?php echo esc_attr($claim_continue_token); ?>"
                                <?php } ?>
                            >
                                <?php echo esc_html($smart_claim_context['action_label'] ?? 'ادامه'); ?>
                            </a>
                        <?php } ?>
                    <?php } elseif (!$claim_is_logged_in) { ?>
                        <p class="bornad-claim-note">برای ثبت درخواست احراز مالکیت ابتدا وارد حساب کاربری خود شوید.</p>
                        <?php if ('' !== $claim_login_url) { ?>
                            <a class="adt-button-dark btn-block" href="<?php echo esc_url($claim_login_url); ?>" data-bs-dismiss="modal">
                                ورود و ادامه
                            </a>
                        <?php } ?>
                    <?php } else { ?>
                        <form class="bornad-claim-form" onsubmit="return false;">
                            <div class="form-group col-md-12 col-sm-12">
                                <label for="bornad-claim-contact">شماره تماس</label>
                                <input id="bornad-claim-contact" type="text" class="form-control" value="<?php echo esc_attr($claim_contact_value); ?>" placeholder="شماره تماس شما">
                            </div>
                            <div class="form-group col-md-12 col-sm-12">
                                <label for="bornad-claim-details">جزئیات درخواست</label>
                                <textarea id="bornad-claim-details" rows="4" class="form-control" placeholder="مدارک یا توضیحات لازم برای احراز مالکیت را بنویسید."></textarea>
                            </div>
                            <div class="col-md-12 col-sm-12 margin-bottom-20 margin-top-20">
                                <button type="button" class="adt-button-dark btn-block" id="bornad-claim-submit" data-adid="<?php echo esc_attr($pid); ?>" data-default-text="ثبت درخواست">
                                    ثبت درخواست
                                </button>
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

get_template_part('template-parts/layouts/ad-style/report', 'ad');
if (isset($adforest_theme['share_ads_on']) && $adforest_theme['share_ads_on']) {
    get_template_part('template-parts/layouts/ad-style/share', 'ad');
}

if (null === $original_layout_style) {
    unset($adforest_theme['ad_layout_style']);
} else {
    $adforest_theme['ad_layout_style'] = $original_layout_style;
}

if (null === $original_layout_flag) {
    unset($adforest_theme['bornado_ad_layout_bornad_style_active']);
} else {
    $adforest_theme['bornado_ad_layout_bornad_style_active'] = $original_layout_flag;
}
