<?php
get_header();
$style = isset($adforest_theme['ad_layout_style']) ? $adforest_theme['ad_layout_style'] : 1;
$page_class = "";
if ($style == 2) {
    $page_class = esc_attr("adt-white-breadcrumb");
}
adforest_custom_breadcrumbs($page_class);
global $adforest_theme;

wp_enqueue_style('star-rating', trailingslashit(get_template_directory_uri()) . 'assets/css/star-rating.css');
wp_enqueue_script('adforest-fancybox');
wp_enqueue_script('nicescroll');
wp_enqueue_style('adforest-fancybox');

if (isset($adforest_theme['allow_lat_lon']) && $adforest_theme['allow_lat_lon'] && isset($adforest_theme['gmap_api_key']) && !empty($adforest_theme['gmap_api_key'])) {
    $map_lang = 'en';
    if (isset($adforest_theme['gmap_lang']) && $adforest_theme['gmap_lang'] != "") {
        $map_lang = $adforest_theme['gmap_lang'];
    }
    $map_lang = apply_filters('adforest_languages_code', $map_lang);
    
    wp_enqueue_script('google-map-callback');
}

if (have_posts()) {
    while (have_posts()) {
        the_post();
        $aid = get_the_ID();
        $expiry_days = '-1';
        $package_ad_expiry_days = get_post_meta($aid, 'package_ad_expiry_days', true);
        if (isset($package_ad_expiry_days) && $package_ad_expiry_days != '') {
            $expiry_days = $package_ad_expiry_days;
        } else if (isset($adforest_theme['simple_ad_removal']) && $adforest_theme['simple_ad_removal'] != '') {
            $expiry_days = $adforest_theme['simple_ad_removal'];
        } else {
        }
        if ($expiry_days != '-1') {
            $now = time();
            $simple_date = strtotime(get_the_date('Y-m-d'));
            $simple_days = adforest_days_diff($now, $simple_date);
            $after_expired_ads = isset($adforest_theme['after_expired_ads']) && !empty($adforest_theme['after_expired_ads']) ? $adforest_theme['after_expired_ads'] : 'trashed';
            if ($after_expired_ads == 'expired') {
                if ($simple_days >= $expiry_days) {
                    update_post_meta($aid, '_adforest_ad_status_', 'expired');
                    $my_post = array(
                        'ID' => $aid,
                        'post_status' => 'draft',
                        'post_type' => 'ad_post',
                    );
                    wp_update_post($my_post);
                }
            } else if ($after_expired_ads == 'trashed') {
                if ($simple_days >= $expiry_days) {
                    wp_trash_post($aid);
                }
            } else {
                if ($simple_days >= $expiry_days) {
                    update_post_meta($aid, '_adforest_ad_status_', 'expired');
                    $my_post = array(
                        'ID' => $aid,
                        'post_status' => 'publish',
                        'post_type' => 'ad_post',
                    );
                    wp_update_post($my_post);
                }
            }

			if (current_user_can('manage_options') && isset($_GET["show_ad_expiry"])) {
				$expiry_timestamp = $simple_date + ((int) $expiry_days * DAY_IN_SECONDS);
				$days_remaining = (int) ceil(($expiry_timestamp - $now) / DAY_IN_SECONDS);
				$expiry_date_str = date_i18n(get_option('date_format'), $expiry_timestamp);
				$debug_message = $days_remaining > 0
					? sprintf(__('Ad expires on %1$s (%2$d days remaining)', 'adforest'), esc_html($expiry_date_str), $days_remaining)
					: sprintf(__('Ad expired on %1$s (%2$d days ago)', 'adforest'), esc_html($expiry_date_str), abs($days_remaining));
				echo '<div style="margin:10px 0;padding:10px;border-left:4px solid #cc0000;background:#fff4f4;color:#333;font-size:13px;">' . esc_html($debug_message) . '</div>';
			}
        } else {
            if (current_user_can('manage_options') && isset($_GET["show_ad_expiry"])) {
				$expiry_timestamp = $simple_date + ((int) $expiry_days * DAY_IN_SECONDS);
				$days_remaining = (int) ceil(($expiry_timestamp - $now) / DAY_IN_SECONDS);
				$expiry_date_str = date_i18n(get_option('date_format'), $expiry_timestamp);
				$debug_message = "Expiry Days: " . $expiry_days;
				echo '<div style="margin:10px 0;padding:10px;border-left:4px solid #cc0000;background:#fff4f4;color:#333;font-size:13px;">' . esc_html($debug_message) . '</div>';
			}
        }

        $featured_expiry_days = '-1';
        $package_adFeatured_expiry_days = get_post_meta($aid, 'package_adFeatured_expiry_days', true);
        if (isset($package_adFeatured_expiry_days) && $package_adFeatured_expiry_days != '') {
            $featured_expiry_days = $package_adFeatured_expiry_days;
        } else if (isset($adforest_theme['featured_expiry']) && $adforest_theme['featured_expiry'] != '') {
            $featured_expiry_days = $adforest_theme['featured_expiry'];
        }

        if (get_post_meta($aid, '_adforest_is_feature', true) == '1' && $featured_expiry_days != '-1') {
            if (isset($featured_expiry_days) && $featured_expiry_days != '-1') {
                $now = time();
                $featured_date = strtotime(get_post_meta($aid, '_adforest_is_feature_date', true));

                $featured_days = adforest_days_diff($now, $featured_date);
                if ($featured_days > $featured_expiry_days) {
                    update_post_meta($aid, '_adforest_is_feature', 0);
                }
            }
        }
        if (function_exists('pvc_get_post_views')) {
            $job_views = call_user_func('pvc_get_post_views', get_the_ID());
        } else {
            adforest_setPostViews($aid);
        }
    }

    $style = isset($adforest_theme['ad_layout_style']) ? $adforest_theme['ad_layout_style'] : 1;
    get_template_part('template-parts/layouts/ad-style/style', $style);
} else {
    get_template_part('template-parts/content', 'none');
}
get_footer();
?>