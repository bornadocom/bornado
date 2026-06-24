<?php
/**
 * Bridge a third single-ad layout into AdForest without touching theme core.
 *
 * @package Bornado_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION')) {
    define('BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION', 'bornado_single_ad_bornad_style_enabled');
}

if (!defined('BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE')) {
    define('BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE', 'bornad-style');
}

if (!function_exists('bornado_get_raw_single_ad_layout_value')) {
    /**
     * Read the admin-selected single-ad layout before frontend bridging.
     *
     * @return string
     */
    function bornado_get_raw_single_ad_layout_value()
    {
        $style_flag = get_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0');
        if ('1' === (string) $style_flag) {
            return BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE;
        }

        $theme_opts = get_option('adforest_theme', array());
        if (!is_array($theme_opts)) {
            return '';
        }

        if (isset($theme_opts['bornado_ad_layout_bornad_style_active']) && '1' === (string) $theme_opts['bornado_ad_layout_bornad_style_active']) {
            return BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE;
        }

        return isset($theme_opts['ad_layout_style']) ? (string) $theme_opts['ad_layout_style'] : '';
    }
}

if (!function_exists('bornado_parse_redux_serialized_post_data')) {
    /**
     * Parse the serialized Redux AJAX payload.
     *
     * @return array
     */
    function bornado_parse_redux_serialized_post_data()
    {
        $out = array();
        if (!isset($_POST['data'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return $out;
        }

        $raw = wp_unslash($_POST['data']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!is_string($raw) || '' === $raw) {
            return $out;
        }

        parse_str($raw, $parsed);
        if (is_array($parsed)) {
            $out = $parsed;
        }

        return $out;
    }
}

if (!function_exists('bornado_capture_single_ad_style_selection_from_submit')) {
    /**
     * Persist bornad-style selection outside Redux sanitization.
     *
     * @return void
     */
    function bornado_capture_single_ad_style_selection_from_submit()
    {
        if (!is_admin()) {
            return;
        }

        $posted_flag = isset($_POST[BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION]) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ? sanitize_text_field(wp_unslash($_POST[BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ('1' === $posted_flag) {
            update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '1', false);
            return;
        }

        if ('0' === $posted_flag) {
            update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0', false);
            return;
        }

        $redux_payload = bornado_parse_redux_serialized_post_data();
        if (isset($redux_payload[BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION])) {
            $redux_flag = sanitize_text_field((string) $redux_payload[BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION]);
            if ('1' === $redux_flag) {
                update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '1', false);
                return;
            }
            if ('0' === $redux_flag) {
                update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0', false);
                return;
            }
        }

        $posted_layout = '';
        if (isset($_POST['adforest_theme']) && is_array($_POST['adforest_theme']) && isset($_POST['adforest_theme']['ad_layout_style'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $posted_layout = sanitize_text_field(wp_unslash($_POST['adforest_theme']['ad_layout_style'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (isset($_POST['adforest_theme']['bornado_ad_layout_bornad_style_active'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $posted_inside = sanitize_text_field(wp_unslash($_POST['adforest_theme']['bornado_ad_layout_bornad_style_active'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, ('1' === $posted_inside ? '1' : '0'), false);
                return;
            }
        } elseif (isset($_POST['ad_layout_style'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $posted_layout = sanitize_text_field(wp_unslash($_POST['ad_layout_style'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        } elseif (isset($redux_payload['adforest_theme']) && is_array($redux_payload['adforest_theme']) && isset($redux_payload['adforest_theme']['ad_layout_style'])) {
            $posted_layout = sanitize_text_field((string) $redux_payload['adforest_theme']['ad_layout_style']);
            if (isset($redux_payload['adforest_theme']['bornado_ad_layout_bornad_style_active'])) {
                $redux_inside = sanitize_text_field((string) $redux_payload['adforest_theme']['bornado_ad_layout_bornad_style_active']);
                update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, ('1' === $redux_inside ? '1' : '0'), false);
                return;
            }
        }

        if (BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE === $posted_layout) {
            update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '1', false);
            return;
        }

        if (in_array($posted_layout, array('1', '2'), true)) {
            update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0', false);
        }
    }

    add_action('admin_init', 'bornado_capture_single_ad_style_selection_from_submit', 5);
    add_action('wp_ajax_redux_ajax_save', 'bornado_capture_single_ad_style_selection_from_submit', 1);
}

if (!function_exists('bornado_sync_single_ad_style_flag_on_theme_option_update')) {
    /**
     * Keep the bornad-style flag in sync when adforest_theme updates.
     *
     * @param mixed $old_value Previous option.
     * @param mixed $value     New option.
     * @return void
     */
    function bornado_sync_single_ad_style_flag_on_theme_option_update($old_value, $value)
    {
        unset($old_value);

        if (!is_array($value) || !isset($value['ad_layout_style'])) {
            return;
        }

        $layout       = (string) $value['ad_layout_style'];
        $array_flag   = isset($value['bornado_ad_layout_bornad_style_active'])
            ? (string) $value['bornado_ad_layout_bornad_style_active']
            : '0';
        $stored_flag  = (string) get_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0');

        if ('1' === $array_flag || '1' === $stored_flag || BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE === $layout) {
            update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '1', false);
        } elseif (in_array($layout, array('1', '2'), true)) {
            update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0', false);
        }
    }

    add_action('update_option_adforest_theme', 'bornado_sync_single_ad_style_flag_on_theme_option_update', 10, 2);
}

if (!function_exists('bornado_ajax_set_single_ad_style_flag')) {
    /**
     * Persist the bornad-style flag from the injected admin control.
     *
     * @return void
     */
    function bornado_ajax_set_single_ad_style_flag()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }

        check_ajax_referer('bornado_single_ad_style_nonce', 'nonce');

        $value = isset($_POST['value']) ? sanitize_text_field(wp_unslash($_POST['value'])) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $flag  = ('1' === $value) ? '1' : '0';

        update_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, $flag, false);

        wp_send_json_success(array('flag' => $flag));
    }

    add_action('wp_ajax_bornado_set_single_ad_style_flag', 'bornado_ajax_set_single_ad_style_flag');
}

if (!function_exists('bornado_is_bornad_single_ad_style_enabled')) {
    /**
     * Determine whether bornad-style should be active.
     *
     * @return bool
     */
    function bornado_is_bornad_single_ad_style_enabled()
    {
        $style_flag = (string) get_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0');
        $theme_opts  = get_option('adforest_theme', array());
        $redux_flag  = (is_array($theme_opts) && isset($theme_opts['bornado_ad_layout_bornad_style_active']))
            ? (string) $theme_opts['bornado_ad_layout_bornad_style_active']
            : '0';

        return ('1' === $style_flag || '1' === $redux_flag);
    }
}

if (!function_exists('bornado_get_readable_permalink')) {
    /**
     * Return a human-readable permalink where only the path portion is decoded.
     *
     * This keeps sharing text readable in Persian while preserving the canonical
     * permalink structure generated by WordPress.
     *
     * @param int $post_id Post ID.
     * @return string
     */
    function bornado_get_readable_permalink($post_id = 0)
    {
        $post_id = absint($post_id);
        $url = $post_id > 0 ? get_permalink($post_id) : get_permalink();
        if (!is_string($url) || '' === $url) {
            return '';
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['path'])) {
            return $url;
        }

        $rebuilt = '';
        if (!empty($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }

        if (!empty($parts['host'])) {
            $rebuilt .= $parts['host'];
        }

        if (!empty($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $path_segments = explode('/', (string) $parts['path']);
        $decoded_segments = array_map(
            static function ($segment) {
                return rawurldecode((string) $segment);
            },
            $path_segments
        );
        $rebuilt .= implode('/', $decoded_segments);

        if (isset($parts['query']) && '' !== (string) $parts['query']) {
            $rebuilt .= '?' . $parts['query'];
        }

        if (isset($parts['fragment']) && '' !== (string) $parts['fragment']) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }
}

if (!function_exists('bornado_should_bridge_single_ad_layout')) {
    /**
     * Limit bornad-style bridge to the single ad page.
     *
     * @return bool
     */
    function bornado_should_bridge_single_ad_layout()
    {
        if (is_admin() && !wp_doing_ajax()) {
            return false;
        }

        return is_singular('ad_post');
    }
}

if (!function_exists('bornado_should_hide_seller_identity')) {
    /**
     * Hide seller identity while an imported ad still belongs to an admin account.
     *
     * The ownership transfer flow updates post_author, so this check automatically
     * flips back to visible once the ad is reassigned to a regular user.
     *
     * @param int $ad_id Listing ID.
     * @return bool
     */
    function bornado_should_hide_seller_identity($ad_id)
    {
        $ad_id = absint($ad_id);
        if ($ad_id <= 0 || 'ad_post' !== get_post_type($ad_id)) {
            return false;
        }

        $owner_id = (int) get_post_field('post_author', $ad_id);
        if ($owner_id <= 0) {
            return false;
        }

        $should_hide = user_can($owner_id, 'manage_options');

        return (bool) apply_filters('bornado_should_hide_seller_identity', $should_hide, $ad_id, $owner_id);
    }
}

if (!function_exists('bornado_bridge_single_ad_layout_option_for_frontend')) {
    /**
     * Replace the theme layout option only for single ad rendering.
     *
     * @param mixed $option_value Option payload.
     * @return mixed
     */
    function bornado_bridge_single_ad_layout_option_for_frontend($option_value)
    {
        if (!is_array($option_value)) {
            return $option_value;
        }

        if (!bornado_should_bridge_single_ad_layout()) {
            return $option_value;
        }

        $array_flag = isset($option_value['bornado_ad_layout_bornad_style_active'])
            ? (string) $option_value['bornado_ad_layout_bornad_style_active']
            : '0';
        $style_flag = get_option(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION, '0');
        $layout     = isset($option_value['ad_layout_style']) ? (string) $option_value['ad_layout_style'] : '';

        if ('1' !== (string) $style_flag && '1' !== $array_flag && BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE !== $layout) {
            return $option_value;
        }

        $option_value['ad_layout_style']                       = BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE;
        $option_value['bornado_ad_layout_bornad_style_active'] = '1';
        $option_value['bornado_ad_layout_original_setting']     = BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE;

        return $option_value;
    }

    add_filter('option_adforest_theme', 'bornado_bridge_single_ad_layout_option_for_frontend', 20);
}

if (!function_exists('bornado_add_single_ad_style_body_class')) {
    /**
     * Add a dedicated body class for bornad-style.
     *
     * @param array $classes Existing body classes.
     * @return array
     */
    function bornado_add_single_ad_style_body_class($classes)
    {
        if (!is_singular('ad_post') || !bornado_is_bornad_single_ad_style_enabled()) {
            return $classes;
        }

        $classes[] = 'bornado-single-ad-style-active';
        $classes[] = 'bornado-single-ad-style-bornad';

        return $classes;
    }

    add_filter('body_class', 'bornado_add_single_ad_style_body_class');
}

if (!function_exists('bornado_get_existing_claim_post_id')) {
    /**
     * Check whether the current user already claimed an ad.
     *
     * @return void
     */
    function bornado_get_existing_claim_post_id($ad_id, $user_id)
    {
        $ad_id   = absint($ad_id);
        $user_id = absint($user_id);

        if ($ad_id <= 0 || $user_id <= 0) {
            return 0;
        }

        $claim_query = new WP_Query(
            array(
                'author'         => $user_id,
                'fields'         => 'ids',
                'meta_key'       => 'd_listing_original_id',
                'meta_value'     => $ad_id,
                'post_status'    => array('publish', 'pending', 'draft', 'private'),
                'post_type'      => 'ad_claims',
                'posts_per_page' => 10,
                'no_found_rows'  => true,
            )
        );

        if (empty($claim_query->posts)) {
            return 0;
        }

        foreach ($claim_query->posts as $claim_post_id) {
            $claim_post_id = absint($claim_post_id);
            if ($claim_post_id <= 0) {
                continue;
            }

            $claim_status = (string) get_post_meta($claim_post_id, 'd_listing_claim_status', true);

            // Allow users to submit a fresh request after an admin decline.
            if ('decline' === $claim_status) {
                continue;
            }

            return $claim_post_id;
        }

        return 0;
    }
}

if (!function_exists('bornado_is_ad_claim_already_approved')) {
    /**
     * Determine whether an ad already has an approved ownership claim.
     *
     * @param int $ad_id Ad ID.
     * @return bool
     */
    function bornado_is_ad_claim_already_approved($ad_id)
    {
        $ad_id = absint($ad_id);
        if ($ad_id <= 0) {
            return false;
        }

        $current_owner_id = (int) get_post_field('post_author', $ad_id);
        $approved_claim = new WP_Query(
            array(
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => 'd_listing_original_id',
                        'value' => $ad_id,
                    ),
                    array(
                        'key'   => 'd_listing_claim_status',
                        'value' => 'approved',
                    ),
                ),
                'post_status'    => array('publish', 'pending', 'draft', 'private'),
                'post_type'      => 'ad_claims',
                'posts_per_page' => 1,
                'no_found_rows'  => true,
            )
        );

        if (empty($approved_claim->posts)) {
            return false;
        }

        foreach ($approved_claim->posts as $claim_post_id) {
            $claim_post_id = absint($claim_post_id);
            if ($claim_post_id <= 0) {
                continue;
            }

            $claimer_id = (int) get_post_meta($claim_post_id, 'd_listing_claimer_id', true);

            if ($claimer_id > 0 && $claimer_id === $current_owner_id) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_send_claim_admin_email')) {
    /**
     * Notify the site admin about a new claim.
     *
     * @param int    $ad_id       Original ad ID.
     * @param int    $claimer_id  Current user ID.
     * @param string $contact     Contact number.
     * @param string $details     Claim details.
     * @return void
     */
    function bornado_send_claim_admin_email($ad_id, $claimer_id, $contact, $details)
    {
        global $adforest_theme;

        if (empty($adforest_theme['sb_listing_is_admin_email'])) {
            return;
        }

        $ad_id      = absint($ad_id);
        $claimer_id = absint($claimer_id);
        $claimer    = get_userdata($claimer_id);
        $owner_id   = (int) get_post_field('post_author', $ad_id);
        $owner      = get_userdata($owner_id);

        $subject = isset($adforest_theme['sb_listing_subject']) && '' !== $adforest_theme['sb_listing_subject']
            ? $adforest_theme['sb_listing_subject']
            : 'Listing Claim - Adforest Listing';
        $body    = isset($adforest_theme['sb_listing_claim_message']) && '' !== $adforest_theme['sb_listing_claim_message']
            ? $adforest_theme['sb_listing_claim_message']
            : '<p>' . esc_html__('Below listing is claimed.', 'adforest') . '</p>';
        $from    = isset($adforest_theme['sb_listing_claim_from']) && '' !== $adforest_theme['sb_listing_claim_from']
            ? $adforest_theme['sb_listing_claim_from']
            : 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>';

        $replacements = array(
            '%site_name%'       => get_bloginfo('name'),
            '%ad_owner%'        => $owner && isset($owner->display_name) ? $owner->display_name : '',
            '%ad_title%'        => get_the_title($ad_id),
            '%ad_link%'         => get_the_permalink($ad_id),
            '%claimed_by%'      => $claimer && isset($claimer->display_name) ? $claimer->display_name : '',
            '%claimer_email%'   => $claimer && isset($claimer->user_email) ? $claimer->user_email : '',
            '%claimer_contact%' => $contact,
            '%claim_details%'   => $details,
        );

        $subject = strtr($subject, $replacements);
        $body    = stripcslashes(strtr($body, $replacements));

        wp_mail(
            get_option('admin_email'),
            $subject,
            $body,
            array('Content-Type: text/html; charset=UTF-8', $from)
        );
    }
}

if (!function_exists('bornado_submit_ad_claim')) {
    /**
     * Persist a claim request from the single ad modal.
     *
     * @return void
     */
    function bornado_submit_ad_claim()
    {
        global $adforest_theme;

        check_ajax_referer('bornado_ad_claim_nonce', 'security');

        if (empty($adforest_theme['allow_claim'])) {
            wp_send_json_error(array('message' => 'امکان احراز مالکیت آگهی در حال حاضر غیرفعال است.'), 400);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'برای ثبت درخواست باید وارد حساب کاربری خود شوید.'), 401);
        }

        $ad_id      = isset($_POST['ad_id']) ? absint($_POST['ad_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $claimer_id = get_current_user_id();
        $contact    = isset($_POST['contact']) ? sanitize_text_field(wp_unslash($_POST['contact'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $details    = isset($_POST['details']) ? sanitize_textarea_field(wp_unslash($_POST['details'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ($ad_id <= 0 || 'ad_post' !== get_post_type($ad_id)) {
            wp_send_json_error(array('message' => 'شناسه آگهی معتبر نیست.'), 400);
        }

        if ((int) get_post_field('post_author', $ad_id) === (int) $claimer_id) {
            wp_send_json_error(array('message' => 'شما نمی‌توانید برای آگهی خودتان درخواست احراز مالکیت ثبت کنید.'), 400);
        }

        if (bornado_is_ad_claim_already_approved($ad_id)) {
            wp_send_json_error(array('message' => 'مالکیت این آگهی قبلاً تایید شده است.'), 400);
        }

        if ('' === $contact) {
            $contact = (string) get_user_meta($claimer_id, '_sb_contact', true);
            $contact = sanitize_text_field($contact);
        }

        if ('' === $contact) {
            wp_send_json_error(array('message' => 'لطفاً شماره تماس خود را وارد کنید.'), 400);
        }

        if ('' === $details) {
            wp_send_json_error(array('message' => 'لطفاً توضیحات یا مدارک لازم برای احراز مالکیت را وارد کنید.'), 400);
        }

        if (!empty($adforest_theme['is_claim_paid'])) {
            $remaining_claims = (string) get_user_meta($claimer_id, '_sb_claim_ads', true);

            $existing_claim_id = bornado_get_existing_claim_post_id($ad_id, $claimer_id);

            if (0 === $existing_claim_id && ('' === $remaining_claims || '0' === $remaining_claims)) {
                wp_send_json_error(array('message' => 'پکیج شما امکان ثبت درخواست احراز مالکیت ندارد.'), 400);
            }

            if (0 === $existing_claim_id && '-1' !== $remaining_claims && is_numeric($remaining_claims)) {
                update_user_meta($claimer_id, '_sb_claim_ads', max(0, ((int) $remaining_claims) - 1));
            }
        }

        $existing_claim_id = bornado_get_existing_claim_post_id($ad_id, $claimer_id);
        if ($existing_claim_id > 0) {
            wp_send_json_error(array('message' => 'شما قبلاً برای این آگهی درخواست احراز مالکیت ثبت کرده‌اید.'), 400);
        }

        $claim_post_id = wp_insert_post(
            array(
                'post_author' => $claimer_id,
                'post_parent' => $ad_id,
                'post_status' => 'publish',
                'post_title'  => sprintf(__('Claim for %s', 'adforest'), get_the_title($ad_id)),
                'post_type'   => 'ad_claims',
            ),
            true
        );

        if (is_wp_error($claim_post_id) || !$claim_post_id) {
            wp_send_json_error(array('message' => 'در حال حاضر امکان ثبت درخواست وجود ندارد. لطفاً دوباره تلاش کنید.'), 500);
        }

        update_post_meta($claim_post_id, 'd_listing_original_id', $ad_id);
        update_post_meta($claim_post_id, 'd_listing_claimer_id', $claimer_id);
        update_post_meta($claim_post_id, 'd_listing_claimer_contact', $contact);
        update_post_meta($claim_post_id, 'd_listing_claimer_msg', $details);
        update_post_meta($claim_post_id, 'd_listing_claim_status', 'pending');
        update_user_meta($claimer_id, 'sb_listing_claimed_listing_id' . $ad_id, $ad_id);

        bornado_send_claim_admin_email($ad_id, $claimer_id, $contact, $details);

        wp_send_json_success(array('message' => 'درخواست احراز مالکیت آگهی با موفقیت ثبت شد.'));
    }

    add_action('wp_ajax_bornado_submit_ad_claim', 'bornado_submit_ad_claim');
    add_action('wp_ajax_nopriv_bornado_submit_ad_claim', 'bornado_submit_ad_claim');
}

if (!function_exists('bornado_enqueue_single_ad_style_assets')) {
    /**
     * Load child-theme styling for bornad-style.
     *
     * @return void
     */
    function bornado_enqueue_single_ad_style_assets()
    {
        if (is_admin() || !is_singular('ad_post') || !bornado_is_bornad_single_ad_style_enabled()) {
            return;
        }

        $style_path = get_stylesheet_directory() . '/assets/css/bornad-single-ad-layout.css';
        if (!file_exists($style_path)) {
            return;
        }

        $deps = function_exists('bornado_get_theme_style_handles')
            ? bornado_get_theme_style_handles()
            : array();

        wp_enqueue_style(
            'bornad-single-ad-layout',
            get_stylesheet_directory_uri() . '/assets/css/bornad-single-ad-layout.css',
            $deps,
            (string) filemtime($style_path)
        );

        wp_enqueue_script('jquery');
        wp_localize_script(
            'jquery',
            'bornadoClaimData',
            array(
                'ajaxUrl'           => admin_url('admin-ajax.php'),
                'genericError'      => 'خطایی رخ داد. لطفاً دوباره تلاش کنید.',
                'loadingText'       => 'در حال ارسال...',
                'nonce'             => wp_create_nonce('bornado_ad_claim_nonce'),
                'displayPhoneNonce' => wp_create_nonce('sb_display_phone_num_secure'),
            )
        );
        wp_add_inline_script(
            'jquery',
            "(function($){
                function bornadoShowClaimFeedback(\$modal, message, isSuccess) {
                    var \$box = \$modal.find('.bornad-claim-feedback');
                    \$box.removeClass('is-success is-error').addClass(isSuccess ? 'is-success' : 'is-error').text(message).show();
                }

                $(document).on('click', '#bornad-claim-submit', function(event){
                    event.preventDefault();

                    var \$button = $(this);
                    var \$modal = \$button.closest('.bornad-claim-modal');
                    var defaultText = \$button.attr('data-default-text') || \$button.text();

                    if (\$button.prop('disabled')) {
                        return;
                    }

                    \$modal.find('.bornad-claim-feedback').hide().text('');
                    \$button.prop('disabled', true).addClass('is-loading').text(bornadoClaimData.loadingText);

                    $.post(bornadoClaimData.ajaxUrl, {
                        action: 'bornado_submit_ad_claim',
                        ad_id: \$button.data('adid'),
                        contact: $.trim(\$modal.find('#bornad-claim-contact').val()),
                        details: $.trim(\$modal.find('#bornad-claim-details').val()),
                        security: bornadoClaimData.nonce
                    }).done(function(response){
                        if (response && response.success) {
                            bornadoShowClaimFeedback(\$modal, response.data.message, true);
                            \$modal.find('#bornad-claim-details').val('');

                            window.setTimeout(function(){
                                if (window.bootstrap && window.bootstrap.Modal) {
                                    var modalElement = \$modal.get(0);
                                    var modalInstance = window.bootstrap.Modal.getInstance(modalElement);
                                    if (modalInstance) {
                                        modalInstance.hide();
                                    }
                                }
                            }, 1200);
                        } else {
                            bornadoShowClaimFeedback(\$modal, response && response.data && response.data.message ? response.data.message : bornadoClaimData.genericError, false);
                        }
                    }).fail(function(xhr){
                        var message = bornadoClaimData.genericError;
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            message = xhr.responseJSON.data.message;
                        }
                        bornadoShowClaimFeedback(\$modal, message, false);
                    }).always(function(){
                        \$button.prop('disabled', false).removeClass('is-loading').text(defaultText);
                    });
                });

                $(document).on('click', '.bornad-claim-action-link', function(event){
                    var actionUrl = $(this).attr('data-action-url') || $(this).attr('href') || '';
                    var usesAuthModal = $(this).is('[data-bornado-auth-open]') || !!$(this).attr('data-continue-token');

                    if (!actionUrl || usesAuthModal) {
                        return;
                    }

                    event.preventDefault();
                    window.location.assign(actionUrl);
                });

                $(document).on('click', '.bornado-contact-reveal-trigger', function(event){
                    event.preventDefault();

                    var \$trigger = $(this);
                    var \$value = \$trigger.find('.style_2_ph');
                    var \$label = \$trigger.find('.bornado-contact-reveal-label');
                    var adId = \$trigger.data('ad-id');
                    var phoneNonce = (window.sb_options && sb_options.sb_display_phone_num_secure)
                        ? sb_options.sb_display_phone_num_secure
                        : bornadoClaimData.displayPhoneNonce;

                    if (!adId || \$trigger.hasClass('is-loading') || \$trigger.hasClass('is-revealed')) {
                        return;
                    }

                    \$trigger.addClass('is-loading');

                    $.post(bornadoClaimData.ajaxUrl, {
                        action: 'sb_display_phone_num_user',
                        ad_id: adId,
                        security: phoneNonce
                    }).done(function(response){
                        response = $.trim(response || '');
                        var res = response.split('|');

                        if (res[0] === '1' && res[1]) {
                            \$value.html(res[1]);
                            \$label.text('شماره تماس');
                            \$trigger.addClass('is-revealed');
                            return;
                        }

                        if (window.toastr && res[1]) {
                            toastr.error(res[1], '', {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: 'toast-top-right'
                            });
                        }
                    }).fail(function(){
                        if (window.toastr) {
                            toastr.error(bornadoClaimData.genericError, '', {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: 'toast-top-right'
                            });
                        }
                    }).always(function(){
                        \$trigger.removeClass('is-loading');
                    });
                });
            })(jQuery);",
            'after'
        );
    }

    add_action('wp_enqueue_scripts', 'bornado_enqueue_single_ad_style_assets', 210);
}

if (!function_exists('bornado_inject_single_ad_style_option_in_theme_ui')) {
    /**
     * Inject a third radio option into the Redux button set.
     *
     * @return void
     */
    function bornado_inject_single_ad_style_option_in_theme_ui()
    {
        if (!is_admin()) {
            return;
        }
        ?>
        <script>
        (function () {
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var ajaxNonce = <?php echo wp_json_encode(wp_create_nonce('bornado_single_ad_style_nonce')); ?>;

            function persistStyleFlag(isBornadStyle) {
                if (!ajaxUrl || !window.fetch) {
                    return;
                }

                var params = new URLSearchParams();
                params.append('action', 'bornado_set_single_ad_style_flag');
                params.append('nonce', ajaxNonce);
                params.append('value', isBornadStyle ? '1' : '0');

                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: params.toString()
                })["catch"](function () {});
            }

            function injectBornadStyleOption() {
                var fieldset = document.getElementById('adforest_theme-ad_layout_style');
                if (!fieldset) {
                    return;
                }

                var buttonset = fieldset.querySelector('.buttonset');
                if (!buttonset) {
                    return;
                }

                var hiddenFlag = fieldset.querySelector('input[name="<?php echo esc_js(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION); ?>"]');
                if (!hiddenFlag) {
                    hiddenFlag = document.createElement('input');
                    hiddenFlag.type = 'hidden';
                    hiddenFlag.name = '<?php echo esc_js(BORNADO_SINGLE_AD_BORNAD_FLAG_OPTION); ?>';
                    hiddenFlag.value = '0';
                    fieldset.appendChild(hiddenFlag);
                }

                var hiddenReduxFlag = fieldset.querySelector('input[name="adforest_theme[bornado_ad_layout_bornad_style_active]"]');
                if (!hiddenReduxFlag) {
                    hiddenReduxFlag = document.createElement('input');
                    hiddenReduxFlag.type = 'hidden';
                    hiddenReduxFlag.name = 'adforest_theme[bornado_ad_layout_bornad_style_active]';
                    hiddenReduxFlag.value = '0';
                    fieldset.appendChild(hiddenReduxFlag);
                }

                if (fieldset.querySelector('#ad_layout_style-buttonset-bornad-style')) {
                    var existingInput = fieldset.querySelector('#ad_layout_style-buttonset-bornad-style');
                    if (existingInput && existingInput.checked) {
                        hiddenFlag.value = '1';
                        hiddenReduxFlag.value = '1';
                    }
                    return;
                }

                var input = document.createElement('input');
                input.type = 'radio';
                input.id = 'ad_layout_style-buttonset-bornad-style';
                input.name = 'adforest_theme[ad_layout_style]';
                input.value = '<?php echo esc_js(BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE); ?>';
                input.className = 'buttonset-item ui-checkboxradio ui-helper-hidden-accessible';
                input.setAttribute('data-id', 'ad_layout_style');

                var label = document.createElement('label');
                label.setAttribute('for', 'ad_layout_style-buttonset-bornad-style');
                label.className = 'ui-button ui-widget ui-checkboxradio-radio-label ui-controlgroup-item ui-checkboxradio-label ui-corner-right';
                label.innerHTML = '<span class="ui-checkboxradio-icon ui-corner-all ui-icon ui-icon-background ui-icon-blank"></span><span class="ui-checkboxradio-icon-space"> </span>bornad-style';

                var labels = buttonset.querySelectorAll('label');
                if (labels.length) {
                    labels[labels.length - 1].classList.remove('ui-corner-right');
                }

                buttonset.appendChild(input);
                buttonset.appendChild(label);

                var saved = <?php echo wp_json_encode(bornado_get_raw_single_ad_layout_value()); ?>;
                if (saved === '<?php echo esc_js(BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE); ?>') {
                    input.checked = true;
                    hiddenFlag.value = '1';
                    hiddenReduxFlag.value = '1';
                    label.classList.add('ui-checkboxradio-checked', 'ui-state-active');

                    var allInputs = buttonset.querySelectorAll('input[type="radio"][name="adforest_theme[ad_layout_style]"]');
                    allInputs.forEach(function (el) {
                        if (el !== input) {
                            el.checked = false;
                        }
                    });

                    var allLabels = buttonset.querySelectorAll('label');
                    allLabels.forEach(function (el) {
                        if (el !== label) {
                            el.classList.remove('ui-checkboxradio-checked', 'ui-state-active');
                        }
                    });
                }

                buttonset.addEventListener('change', function (event) {
                    var target = event && event.target ? event.target : null;
                    if (!target || target.name !== 'adforest_theme[ad_layout_style]') {
                        return;
                    }

                    var isBornadStyle = target.value === '<?php echo esc_js(BORNADO_SINGLE_AD_BORNAD_LAYOUT_VALUE); ?>';
                    hiddenFlag.value = isBornadStyle ? '1' : '0';
                    hiddenReduxFlag.value = isBornadStyle ? '1' : '0';
                    persistStyleFlag(isBornadStyle);
                });

                if (window.jQuery && window.jQuery.fn.checkboxradio) {
                    window.jQuery(buttonset).find('input').checkboxradio('refresh');
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', injectBornadStyleOption);
            } else {
                injectBornadStyleOption();
            }
        })();
        </script>
        <?php
    }

    add_action('admin_footer', 'bornado_inject_single_ad_style_option_in_theme_ui', 99);
}
