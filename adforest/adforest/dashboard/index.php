<?php
global $adforest_theme;

if (!function_exists('adforest_safe_svg_output')) {
    function adforest_safe_svg_output($svg_filename) {
        $svg_path = get_template_directory() . '/dashboard/icons/' . $svg_filename;
        
        // Validate the file exists and is actually in the icons directory
        $real_path = realpath($svg_path);
        $icons_dir = realpath(get_template_directory() . '/dashboard/icons/');
        
        if ($real_path && 
            $icons_dir && 
            strpos($real_path, $icons_dir) === 0 && 
            file_exists($real_path) && 
            pathinfo($real_path, PATHINFO_EXTENSION) === 'svg') {
            echo file_get_contents($real_path);
        }
    }
}

// Remove Expired Packages //
adforest_remove_expired_packages();
// Remove Expired Packages //

$selected_categories = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);

if (!is_array($selected_categories)) {
    $selected_categories = [];
}

$assigned_on_registration_packages = array_filter($selected_categories, function ($package) {
    return is_array($package) &&
        isset($package['assigned_on_registration']) &&
        $package['assigned_on_registration'] == 1;
});

// Remove packages assigned on registration
$selected_categories = array_filter($selected_categories, function ($package) {
    return !(is_array($package) &&
        isset($package['assigned_on_registration']) &&
        $package['assigned_on_registration'] == 1);
});

$sb_sign_in_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_sign_in_page']);
$user_info = get_userdata(get_current_user_id());
//$user_id = $user_info->ID;
$user_id = get_current_user_id();
$user_pic = adforest_get_user_dp($user_info->ID);
$sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
$logo = isset($adforest_theme['sb_dashboard_logo']['url']) ? $adforest_theme['sb_dashboard_logo']['url'] : ADFOREST_IMAGE_PATH . "/logo.png";
$sb_post_ad_page = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : "";
$sb_post_ad_page = apply_filters('adforest_ad_post_verified_id', $sb_post_ad_page);
$pay_per_post_check = isset($adforest_theme['sb_pay_per_post_option']) ? $adforest_theme['sb_pay_per_post_option'] : false;

$paid_html = "";
$pkg_type_str = '';
$profile_td_pkg = get_user_meta($user_id, '_sb_pkg_type', true);
$profile_td_pkg_meta = get_user_meta($user_id);
if (isset($profile_td_pkg) && $profile_td_pkg == 'free') {
    $pkg_type_str = esc_html__('Free', 'adforest');
} else {
    $pkg_type_str = $profile_td_pkg;
}

if (class_exists('WooCommerce')) {
    $paid_html = '';

    foreach ($assigned_on_registration_packages as $package_name => $package_data) {
        $free_ads = !empty($package_data['free_ads']) ? $package_data['free_ads'] : esc_html__('N/A', 'adforest');
        $featured_ads = !empty($package_data['featured_ads']) ? $package_data['featured_ads'] : esc_html__('N/A', 'adforest');
        $bump_ads = !empty($package_data['bump_ads']) ? $package_data['bump_ads'] : esc_html__('N/A', 'adforest');
        $pkg_expiry_days = !empty($package_data['pkg_expiry_days']) ? $package_data['pkg_expiry_days'] : esc_html__('N/A', 'adforest');
        $ad_expiry_days = !empty($package_data['ad_expiry_days']) ? $package_data['ad_expiry_days'] : esc_html__('N/A', 'adforest');
        $featured_expiry_days = !empty($package_data['featured_expiry_days']) ? $package_data['featured_expiry_days'] : esc_html__('N/A', 'adforest');
        $video_links = !empty($package_data['video_links']) ? $package_data['video_links'] : esc_html__('N/A', 'adforest');
        $num_of_images = !empty($package_data['num_of_images']) ? $package_data['num_of_images'] : esc_html__('N/A', 'adforest');
        $allow_tags = !empty($package_data['allow_tags']) ? $package_data['allow_tags'] : esc_html__('N/A', 'adforest');
        $allow_bidding = !empty($package_data['allow_bidding']) ? $package_data['allow_bidding'] : esc_html__('N/A', 'adforest');
        $number_of_events = !empty($package_data['number_of_events']) ? $package_data['number_of_events'] : esc_html__('N/A', 'adforest');
        $paid_biddings = !empty($package_data['paid_biddings']) ? $package_data['paid_biddings'] : esc_html__('N/A', 'adforest');
        $allow_cate = !empty($package_data['allow_cate']) ? $package_data['allow_cate'] : esc_html__('N/A', 'adforest');

        $pretty_free_ads = ($free_ads == '-1') ? __("Unlimited", 'adforest') : (is_numeric($free_ads) ? $free_ads : esc_html__('N/A', 'adforest'));
        $pretty_featured_ads = ($featured_ads == '-1') ? __("Unlimited", 'adforest') : (is_numeric($featured_ads) ? $featured_ads : esc_html__('N/A', 'adforest'));
        $pretty_bump_ads = ($bump_ads == '-1') ? __("Unlimited", 'adforest') : (is_numeric($bump_ads) ? $bump_ads : esc_html__('N/A', 'adforest'));
        $pretty_ad_expiry_days = ($ad_expiry_days == '-1') ? __("Unlimited", 'adforest') : (is_numeric($ad_expiry_days) ? $ad_expiry_days : esc_html__('N/A', 'adforest'));
        $pretty_featured_expiry_days = ($featured_expiry_days == '-1') ? __("Unlimited", 'adforest') : (is_numeric($featured_expiry_days) ? $featured_expiry_days : esc_html__('N/A', 'adforest'));

        if ($pkg_expiry_days == '-1') {
            $pretty_pkg_expiry_days = __("Unlimited", 'adforest');
        } elseif (!empty($pkg_expiry_days) && strtotime($pkg_expiry_days)) {
            $pretty_pkg_expiry_days = date_i18n(get_option('date_format'), strtotime($pkg_expiry_days));
        } else {
            $pretty_pkg_expiry_days = esc_html__('N/A', 'adforest');
        }

        $product_title = esc_html__('N/A', 'adforest');
        $product = wc_get_product($package_name);
        if ($product) {
            $product_title = $product->get_title();
        }

        $paid_html .= '
                        <tr><td class="text-dark">' . esc_html__('Package Name', 'adforest') . '</td>
                            <td class="text-right">' . adforest_return_echo($product_title) . '</td>
                        </tr>
                        <tr><td class="text-dark">' . esc_html__('Package Expiry', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($pretty_pkg_expiry_days) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Free Ads Remaining', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($pretty_free_ads) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Simple Ads Expiry Days', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($pretty_ad_expiry_days) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Featured Ads Remaining', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($pretty_featured_ads) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Featured Ads Expiry Days', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($pretty_featured_expiry_days) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Bump-up Ads Remaining', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($pretty_bump_ads) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Video Links Allowed', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($video_links) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Number of Images Allowed', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($num_of_images) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Tags Allowed', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($allow_tags) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Bidding Allowed', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($allow_bidding) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Number of Events', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($number_of_events) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Paid Biddings', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($paid_biddings) . '</td>
                        </tr>
                        <tr><td>' . esc_html__('Allowed Categories', 'adforest') . '</td>
                            <td class="text-right">' . esc_html($allow_cate) . '</td>
                        </tr>';
    }
}
$activeAds = '';
$expandAds = "";
if (isset($_GET['page_type']) && ($_GET['page_type'] == 'my_ads' || $_GET['page_type'] == 'feature_ads' || $_GET['page_type'] == 'rej_ads' || $_GET['page_type'] == 'inactive_ads' || $_GET['page_type'] == 'fav_ads' || $_GET['page_type'] == 'expire_ads')) {
    $expandAds = 'show';
    $activeAds = 'active';
}
?>

<!-- ======== sidebar-nav start =========== -->
<aside class="sidebar-nav-wrapper">
    <div class="navbar-logo" style="display: inline-block">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <img src="<?php echo esc_url($logo, "adforest") ?>" alt="logo" />
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php
            $active = "";
            if (!isset($_GET['page_type'])) {
                $active = 'active';
            }
            ?>
            <li class="nav-item <?php echo esc_attr($active) ?>">
                <a href="<?php echo get_the_permalink(); ?>">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('dashboard.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("Dashboard", "adforest"); ?></span>
                </a>
            </li>
            <?php
            $active_profile = "";
            if (isset($_GET['page_type']) && $_GET['page_type'] == 'my_profile') {
                $active_profile = 'active';
            }
            ?>
            <li class="nav-item <?php echo esc_attr($active_profile); ?>">
                <a href="<?php echo get_the_permalink() . "?page_type=my_profile"; ?>">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('edit_profile.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("Edit Profile", "adforest"); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo adforest_set_url_param(get_author_posts_url($user_id), 'type', 'ads') ?>">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('view_profile.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("View Profile", "adforest"); ?></span>
                </a>
            </li>
            <li class="nav-item nav-item-has-children <?php echo esc_attr($activeAds); ?>">
                <a
                    href="#"
                    class="collapsed"
                    data-bs-toggle="collapse"
                    data-bs-target="#ddmenu_2"
                    aria-controls="ddmenu_2"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('manage_ads.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("Manage Ads", "adforest"); ?></span>
                </a>
                <ul id="ddmenu_2" class="collapse dropdown-nav <?php echo esc_attr($expandAds); ?>">
                    <?php
                    $page_types = [
                        'my_ads' => esc_html__("Active Ads", "adforest"),
                        'feature_ads' => esc_html__("Featured Ads", "adforest"),
                        'rej_ads' => esc_html__("Rejected Ads", "adforest"),
                        'inactive_ads' => esc_html__("Inactive Ads", "adforest"),
                        'fav_ads' => esc_html__("Favourite Ads", "adforest"),
                        'expire_ads' => esc_html__("Expired/Sold Ads", "adforest"),
                    ];

                    $current_page_type = $_GET['page_type'] ?? '';

                    foreach ($page_types as $type => $label) {
                        $active_class = ($current_page_type === $type) ? 'active' : '';
                    ?>
                        <li>
                            <a class="<?php echo esc_attr($active_class); ?>"
                                href="<?php echo get_the_permalink() . "?page_type=$type"; ?>"><?php echo esc_html($label); ?></a>
                        </li>
                    <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
            $active_messages = "";
            if (isset($_GET['page_type']) && $_GET['page_type'] == 'msg') {
                $active_messages = 'active';
            }
            $sb_plugin_options = get_option('sb_plugin_options', array());
            if (isset($sb_plugin_options['sbChat-active']) && $sb_plugin_options['sbChat-active'] == 1 && class_exists('SB_Chat_Setting_Page')) {
            ?>
                <li class="nav-item <?php echo esc_attr($active_messages); ?>">
                    <a href="<?php echo get_the_permalink() . "?page_type=msg"; ?>">
                        <span class="icon">
                            <?php
                            adforest_safe_svg_output('message.svg');
                            ?>
                        </span>
                        <span class="text"><?php echo esc_html__("My Messages", "adforest"); ?></span>
                    </a>
                </li>
            <?php }
            if (class_exists('SbPro')) {
                echo apply_filters('sb_get_anchor', '', 'page_type', 'events');
            } ?>

            <?php if (class_exists('SbPro')) {
                echo apply_filters('sb_get_booking_anchor', '', 'page_type', 'bookings');
            } ?>
            <?php
            $ad_alert_active = "";
            if (isset($_GET['page_type']) && $_GET['page_type'] == 'alerts') {
                $ad_alert_active = 'active';
            }
            ?>
            <li class="nav-item <?php echo esc_attr($ad_alert_active) ?>">
                <a href="<?php echo get_the_permalink() . "?page_type=alerts"; ?>">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('ad_alerts.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("Ad Alerts", "adforest"); ?></span>
                </a>
            </li>
            <?php
            $ad_bids_active = "";
            if (isset($_GET['page_type']) && $_GET['page_type'] == 'ad_bids') {
                $ad_bids_active = 'active';
            }
            // Compute new bids count since last seen
            $new_bids_count = 0;
            if (is_user_logged_in()) {
                $last_seen_time = get_user_meta(get_current_user_id(), 'ad_bids_last_seen_time', true);
                if (!empty($last_seen_time)) {
                    global $wpdb;
                    $user_ad_ids = get_posts(array(
                        'post_type'      => 'ad_post',
                        'author'         => get_current_user_id(),
                        'posts_per_page' => -1,
                        'post_status'    => array('publish', 'pending', 'draft', 'private'),
                        'fields'         => 'ids',
                    ));
                    if (is_array($user_ad_ids) && count($user_ad_ids) > 0) {
                        $threshold = strtotime($last_seen_time);
                        foreach ($user_ad_ids as $ad_id) {
                            $rows = $wpdb->get_results($wpdb->prepare(
                                "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE '_adforest_bid_%' ORDER BY meta_id DESC",
                                $ad_id
                            ));
                            if (is_array($rows) && count($rows) > 0) {
                                foreach ($rows as $r) {
                                    $parts = explode('_separator_', $r->meta_value);
                                    $bid_date = isset($parts[0]) ? $parts[0] : '';
                                    if (!empty($bid_date) && strtotime($bid_date) > $threshold) {
                                        $new_bids_count++;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // If never seen, do not flood with total count; leave as 0 until page is opened
                    $new_bids_count = 0;
                }
            }
            ?>
            <li class="nav-item <?php echo esc_attr($ad_bids_active) ?>">
                <a href="<?php echo get_the_permalink() . "?page_type=ad_bids"; ?>">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('ad_alerts.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("Ad Bids", "adforest"); ?></span>
                    <?php if (!empty($new_bids_count)) { ?>
                        <span class="badge" style="margin-left:8px; background:#e91e63; color:#fff; border-radius:12px; padding:2px 8px; font-size:12px; line-height:1; display:inline-block;">
                            <?php echo esc_html($new_bids_count); ?>
                        </span>
                    <?php } ?>
                </a>
            </li>
            <?php
            $my_pkgs_active = "";
            if (isset($_GET['page_type']) && $_GET['page_type'] == 'my_packages') {
                $my_pkgs_active = 'active';
            }
            ?>
            <li class="nav-item <?php echo esc_attr($my_pkgs_active) ?>">
                <a href="<?php echo get_the_permalink() . "?page_type=my_packages"; ?>">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('packages.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("My Packages", "adforest"); ?></span>
                </a>
            </li>
            <?php
            $invoices_active = "";
            if (isset($_GET['page_type']) && $_GET['page_type'] == 'invoices') {
                $invoices_active = 'active';
            }
            ?>
            <li class="nav-item <?php echo esc_attr($invoices_active) ?>">
                <a href="<?php echo get_the_permalink() . "?page_type=invoices"; ?>">
                    <span class="icon">
                        <?php
                        adforest_safe_svg_output('packages.svg');
                        ?>
                    </span>
                    <span class="text"><?php echo esc_html__("Invoices", "adforest"); ?></span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="promo-box">
        <a
            href="<?php echo esc_url(get_permalink($sb_post_ad_page), "adforest"); ?>"
            target="_blank"
            rel="nofollow"
            class="btn dark-btn btn-block">
            <?php echo esc_html__("Post Ad", "adforest") ?>
        </a>
    </div>
</aside>
<div class="overlay"></div>
<!-- ======== sidebar-nav end =========== -->

<!-- ======== main-wrapper start =========== -->
<main class="main-wrapper">
    <!-- ========== header start ========== -->
    <header class="header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-5 col-md-5 col-9">
                    <div class="header-left d-flex align-items-center">
                        <div class="menu-toggler">
                            <button id="menu-toggle" class="btn menu-toggle-btn"><i
                                    class="lni lni-chevron-left"></i> <?php echo esc_html__("Menu", "adforest"); ?>
                            </button>
                        </div>
                        <div class="vendor_button">
                            <?php print_r(apply_filters('adforest_vendor_role_assign_button', '', $user_id)); ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 col-md-7 col-3">
                    <div class="header-right">
                        <!-- notification start -->
                        <?php
                        global $wpdb;
                        $current_user_id = get_current_user_id();

                        // Fetch unread messages count
                        $unread_msgs_query = $wpdb->get_results($wpdb->prepare("
                                                                                SELECT *
                                                                                FROM {$wpdb->prefix}sb_chat_messages
                                                                                WHERE receiver_id = %d AND read_status = 0
                                                                                ORDER BY created DESC
                                                                                LIMIT 30
                                                                            ", $current_user_id));

                        $unread_msgs = is_array($unread_msgs_query) ? count($unread_msgs_query) : 0;

                        $theme_notifications = function_exists('adforest_get_user_theme_notifications')
                            ? adforest_get_user_theme_notifications(
                                $current_user_id,
                                array(
                                    'limit'        => 30,
                                    'include_read' => false,
                                )
                            )
                            : array();

                        $theme_notifications = is_array($theme_notifications) ? $theme_notifications : array();
                        $unread_theme_count  = count($theme_notifications);
                        $total_unread        = $unread_msgs + $unread_theme_count;

                        $combined_notifications = array();

                        if (!empty($unread_msgs_query)) {
                            foreach ($unread_msgs_query as $msg) {
                                $combined_notifications[] = array(
                                    'type'      => 'chat',
                                    'timestamp' => strtotime($msg->created) ?: 0,
                                    'data'      => $msg,
                                );
                            }
                        }

                        if (!empty($theme_notifications)) {
                            foreach ($theme_notifications as $notification_item) {
                                $combined_notifications[] = array(
                                    'type'      => 'theme',
                                    'timestamp' => strtotime($notification_item->created_at) ?: 0,
                                    'data'      => $notification_item,
                                );
                            }
                        }

                        if (!empty($combined_notifications)) {
                            usort(
                                $combined_notifications,
                                function ($a, $b) {
                                    $a_time = isset($a['timestamp']) ? (int) $a['timestamp'] : 0;
                                    $b_time = isset($b['timestamp']) ? (int) $b['timestamp'] : 0;

                                    if ($a_time === $b_time) {
                                        return 0;
                                    }

                                    return ($a_time < $b_time) ? 1 : -1;
                                }
                            );
                        }
                        ?>
                        <div class="notification-box ml-15 d-none d-md-flex">
                            <button
                                class="dropdown-toggle"
                                type="button"
                                id="notification"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="lni lni-alarm"></i>
                                <span><?php echo esc_html($total_unread); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notification">
                                <?php
                                if ($total_unread > 0 && !empty($combined_notifications)) {
                                    $count = 0;
                                    foreach ($combined_notifications as $notification_item) {
                                        if ($count >= 5) {
                                            break;
                                        }

                                        if ($notification_item['type'] === 'chat') {
                                            $msg = $notification_item['data'];
                                            $sender = get_userdata($msg->sender_id);
                                            $sender_name = isset($sender->display_name) ? esc_html($sender->display_name) : esc_html__('Unknown User', 'adforest');
                                            $parts = explode('|', $msg->message);
                                            $message_for_preview = isset($parts[1]) ? trim($parts[1]) : trim($msg->message);
                                            $message_preview = wp_trim_words(wp_strip_all_tags($message_for_preview), 10, '...');
                                            $conversation_id = isset($msg->conversation_id) ? $msg->conversation_id : "";
                                            $timestamp = isset($notification_item['timestamp']) ? (int) $notification_item['timestamp'] : time();
                                        ?>
                                            <li>
                                                <a href="<?php echo esc_url(get_the_permalink() . "?page_type=msg&conversation_id=" . $conversation_id); ?>"
                                                    class="notification-item-dash"
                                                    data-notification-type="chat"
                                                    data-message-id="<?php echo esc_attr($msg->id); ?>">
                                                    <div class="image">
                                                        <img src="<?php echo esc_url(get_avatar_url($msg->sender_id)); ?>"
                                                            alt="<?php echo esc_attr($sender_name); ?>" />
                                                    </div>
                                                    <div class="content">
                                                        <h6><?php echo esc_html($sender_name) . __(" (New Message)", "adforest"); ?></h6>
                                                        <p><?php echo esc_html($message_preview); ?></p>
                                                        <?php
                                                        echo esc_html(
                                                            sprintf(
                                                                __('%1$s at %2$s', 'adforest'),
                                                                wp_date(get_option('date_format'), $timestamp),
                                                                wp_date(get_option('time_format'), $timestamp)
                                                            )
                                                        );
                                                        ?>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php
                                        } else {
                                            $notification = $notification_item['data'];
                                            $timestamp = isset($notification_item['timestamp']) ? (int) $notification_item['timestamp'] : time();
                                            $title = !empty($notification->title) ? $notification->title : __('New notification', 'adforest');
                                            $message = !empty($notification->message) ? wp_strip_all_tags($notification->message) : '';
                                            $message_preview = wp_trim_words($message, 16, '...');
                                            $meta_link = '';
                                            if (is_array($notification->meta) && isset($notification->meta['link'])) {
                                                $meta_link = $notification->meta['link'];
                                            }
                                            if (empty($meta_link)) {
                                                $meta_link = add_query_arg('page_type', 'invoices', get_the_permalink());
                                            }
                                            $icon_map = array(
                                                'package_order' => 'lni lni-package',
                                                'ad_post'       => 'lni lni-world-alt',
                                            );
                                            $icon_class = isset($icon_map[$notification->type]) ? $icon_map[$notification->type] : 'lni lni-bell';
                                        ?>
                                            <li>
                                                <a href="<?php echo esc_url($meta_link); ?>"
                                                    class="notification-item-dash"
                                                    data-notification-type="theme"
                                                    data-notification-id="<?php echo esc_attr($notification->id); ?>">
                                                    <div class="image notification-icon">
                                                        <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                                    </div>
                                                    <div class="content">
                                                        <h6><?php echo esc_html($title); ?></h6>
                                                        <?php if (!empty($message_preview)) : ?>
                                                            <p><?php echo esc_html($message_preview); ?></p>
                                                        <?php endif; ?>
                                                        <?php
                                                        echo esc_html(
                                                            sprintf(
                                                                __('%1$s at %2$s', 'adforest'),
                                                                wp_date(get_option('date_format'), $timestamp),
                                                                wp_date(get_option('time_format'), $timestamp)
                                                            )
                                                        );
                                                        ?>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php
                                        }

                                        $count++;
                                    }

                                    $notification_page = isset($adforest_theme['sb_notification_page']) ? $adforest_theme['sb_notification_page'] : "";
                                    $notification_page_link = get_the_permalink($notification_page);
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url($notification_page_link); ?>">
                                            <span><?php echo esc_html__("View All Notifications", "adforest"); ?></span>
                                        </a>
                                    </li>
                                <?php
                                } else {
                                ?>
                                    <li>
                                        <?php esc_html_e('You Have 0 New Notifications', 'adforest'); ?>
                                    </li>
                                <?php
                                }
                                ?>
                            </ul>
                        </div>
                        <!-- notification end -->
                        <!-- profile start -->
                        <div class="profile-box ml-15">
                            <button
                                class="dropdown-toggle bg-transparent border-0"
                                type="button"
                                id="profile"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <div class="profile-info">
                                    <div class="info">
                                        <h6><?php echo esc_html($user_info->display_name) ?></h6>
                                        <div class="image">
                                            <img
                                                src="<?php echo esc_url($user_pic) ?>"
                                                alt="" />
                                            <span class="status"></span>
                                        </div>
                                    </div>
                                </div>
                                <i class="lni lni-chevron-down"></i>
                            </button>
                            <ul
                                class="dropdown-menu dropdown-menu-end"
                                aria-labelledby="profile">
                                <li>
                                    <a href="<?php echo adforest_set_url_param(get_author_posts_url(get_current_user_id()), 'type', 'ads') ?>">
                                        <i class="lni lni-user"></i> <?php echo esc_html__("View Profile", "adforest"); ?>
                                    </a>
                                </li>
                                <?php echo apply_filters('adforest_vendor_dashboard_profile', '', $user_id); ?>
                                <li>
                                    <a href="<?php echo get_the_permalink() . "?page_type=msg"; ?>"> <i
                                            class="lni lni-inbox"></i> <?php echo esc_html__("Messages", "adforest"); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo wp_logout_url(get_the_permalink($sb_sign_in_page)); ?>"> <i
                                            class="lni lni-exit"></i> <?php echo esc_html__("Sign Out", "adforest"); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- profile end -->
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- ========== header end ========== -->
    <input type="hidden" value="<?php echo esc_attr__('Confirm', 'adforest'); ?>" id="confirm_btn">
    <input type="hidden" value="<?php echo esc_attr__('Cancel', 'adforest'); ?>" id="cancel_btn">
    <input type="hidden" value="<?php echo esc_attr__('Are you sure ?', 'adforest'); ?>" id="confirm_text">
    <input type="hidden" value="<?php echo esc_attr__('Are you sure you want to purchase ?', 'adforest'); ?>"
        id="confirm_profile">
    <input type="hidden" value="<?php echo wp_is_mobile(); ?>" id="is_mobile">
    <input type="hidden" id="ad_info_text" value="<?php echo esc_attr__('Ad info', 'adforest'); ?>">
    <!-- ========== section start ========== -->
    <section class="section adforest-dashboard-section">
        <div class="container-fluid">
            <?php
            $is_email_verification_enabled = isset($adforest_theme['sb_new_user_email_verification']) && $adforest_theme['sb_new_user_email_verification'];
            $email_verification_status = get_user_meta($user_id, 'sb_user_email_verification_status', true);
            $show_resend_verification_user_dashboard = isset($adforest_theme['show_resend_verification_user_dashboard']) ? $adforest_theme['show_resend_verification_user_dashboard'] : 0;

            if ($is_email_verification_enabled && $email_verification_status != 'verified' && $show_resend_verification_user_dashboard) :
            ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-warning alert-dismissible fade show adforest-email-verification-alert" role="alert">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div class="d-flex align-items-start">
                                    <span class="me-3 lni lni-envelope alert-icon"></span>
                                    <div>
                                        <h6 class="mb-1"><?php esc_html_e('Verify your email address', 'adforest'); ?></h6>
                                        <p class="mb-0">
                                            <?php esc_html_e('Please verify your email address. Check your inbox for the verification link.', 'adforest'); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary adforest-email-verification-resend"
                                        data-loading-text="<?php echo esc_attr__('Sending…', 'adforest'); ?>"
                                        data-success-text="<?php echo esc_attr__('Email sent!', 'adforest'); ?>"
                                        data-security="<?php echo wp_create_nonce('email_verification_resend_nonce'); ?>">
                                        <?php esc_html_e('Click here to resend the verification email', 'adforest'); ?>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php esc_attr_e('Close', 'adforest'); ?>"></button>
                        </div>
                    </div>
                </div>
            <?php
            endif;
            ?>
            <?php
            if (isset($_GET['page_type']) && $_GET['page_type'] != "") {
                $allowed_page_types = array(
                    'my_profile',
                    'my_ads',
                    'feature_ads',
                    'rej_ads',
                    'inactive_ads',
                    'fav_ads',
                    'expire_ads',
                    'msg',
                    'alerts',
                    'ad_bids',
                    'my_packages',
                    'invoices',
                    'events',
                    'bookings'
                );
                
                /**
                 * Filter: Allow child themes or plugins to add custom page types
                 * 
                 * @param array $allowed_page_types Array of allowed page type slugs
                 * @return array Modified array of allowed page types
                 * 
                 * Usage in child theme:
                 * add_filter('adforest_dashboard_allowed_page_types', function($types) {
                 *     $types[] = 'my_custom_page';
                 *     $types[] = 'another_custom_page';
                 *     return $types;
                 * });
                 */
                $allowed_page_types = apply_filters('adforest_dashboard_allowed_page_types', $allowed_page_types);
                
                $page_type = sanitize_key($_GET['page_type']);
                
                if (in_array($page_type, $allowed_page_types, true)) {
                    $rating = "";
                    get_template_part('dashboard/template-parts/page', $page_type);
                } else {
                    echo '<div class="alert alert-danger">' . esc_html__('Invalid page type.', 'adforest') . '</div>';
                }
            } else { ?>
                <div class="title-wrapper">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <?php if (is_user_logged_in()) :
                                $current_user = wp_get_current_user();
                            ?>
                                <h2>
                                    <?php
                                    echo sprintf(
                                        esc_html__('Welcome, %s', 'adforest'),
                                        esc_html($current_user->display_name)
                                    );
                                    ?>
                                </h2>
                            <?php else : ?>
                                <h2><?php esc_html_e('Welcome', 'adforest'); ?></h2>
                            <?php endif; ?>
                        </div>
                        <!-- end col -->
                        <div class="col-md-6">
                            <div class="breadcrumb-wrapper">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="<?php echo get_the_permalink() ?>"><?php echo esc_html__("Dashboard", "adforest"); ?></a>
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                </div>

                <div class="row">
                    <?php
                    $ppp_enabled = isset($adforest_theme['sb_pay_per_post_option']) ? (bool)$adforest_theme['sb_pay_per_post_option'] : false;
                    $ppp_free_toggle = isset($adforest_theme['enable_free_ads_with_ppp']) ? (bool)$adforest_theme['enable_free_ads_with_ppp'] : false;
                    if ($ppp_enabled && $ppp_free_toggle) {
                        $configured_total = isset($adforest_theme['ppp_free_ads_quota']) ? intval($adforest_theme['ppp_free_ads_quota']) : 0;
                        $user_remaining = get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true);
                        if ($user_remaining === '' || $user_remaining === null) {
                            $user_remaining = max(0, $configured_total);
                            update_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', $user_remaining);
                        }
                    ?>
                        <!-- <div class="col-xl-3 col-lg-4 col-sm-6 col-6">
                            <div class="icon-card">
                                <div class="icon info">
                                    <i class="lni lni-gift"></i>
                                </div>
                                <div class="content">
                                    <h6 class=""><?php echo esc_html__('PPP Free Ads', 'adforest'); ?></h6>
                                    <h3 class="text-semi-bold">
                                        <?php echo esc_html(intval($user_remaining) . ' / ' . intval($configured_total)); ?>
                                    </h3>
                                </div>
                            </div>
                        </div> -->
                    <?php
                    }
                    ?>
                    <div class="col-xl-3 col-lg-4 col-sm-6 col-6">
                        <div class="icon-card">
                            <div class="icon purple">
                                <i class="lni lni-cart-full"></i>
                            </div>
                            <div class="content">
                                <h6 class=""><?php echo esc_html__('Ad Sold', 'adforest'); ?></h6>
                                <h3 class="text-semi-bold"><?php echo adforest_get_sold_ads(get_current_user_id()); ?></h3>
                            </div>
                        </div>
                        <!-- End Icon Cart -->
                    </div>
                    <!-- End Col -->
                    <div class="col-xl-3 col-lg-4 col-sm-6 col-6">
                        <div class="icon-card">
                            <div class="icon success">
                                <i class="lni lni-dollar"></i>
                            </div>
                            <div class="content">
                                <h6 class=""><?php echo esc_html__('Total Listings', 'adforest'); ?></h6>
                                <?php
                                $total_listing_published = adforest_get_all_ads(get_current_user_id()); ?>
                                <h3 class="text-semi-bold"><?php echo esc_html($total_listing_published); ?></h3>
                            </div>
                        </div>
                        <!-- End Icon Cart -->
                    </div>
                    <!-- End Col -->
                    <div class="col-xl-3 col-lg-4 col-sm-6 col-6">
                        <div class="icon-card">
                            <div class="icon primary">
                                <i class="lni lni-credit-cards"></i>
                            </div>
                            <div class="content">
                                <h6><?php echo esc_html__('Inactive Ads', 'adforest'); ?></h6>
                                <h3 class="text-semi-bold"><?php echo adforest_get_disbale_ads(get_current_user_id()); ?></h3>
                            </div>
                        </div>
                        <!-- End Icon Cart -->
                    </div>
                    <!-- End Col -->
                    <div class="col-xl-3 col-lg-4 col-sm-6 col-6">
                        <div class="icon-card">
                            <div class="icon orange">
                                <i class="lni lni-user"></i>
                            </div>
                            <div class="content">
                                <h6 class=""><?php echo esc_html__('Total Ratings', 'adforest'); ?></h6>
                                <h3 class="text-semi-bold"><?php echo count(adforest_get_all_ratings(get_current_user_id())); ?></h3>
                            </div>
                        </div>
                        <!-- End Icon Cart -->
                    </div>
                    <!-- End Col -->
                </div>
                <?php if (isset($adforest_theme['sb_show_profile_stat']) && $adforest_theme['sb_show_profile_stat'] == '1') { ?>
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card-style">
                                <div class="title d-flex flex-wrap justify-content-between">
                                    <div class="left">
                                        <h6 class="text-medium">
                                            <?php echo esc_html__("Ad Views", "adforest"); ?>
                                        </h6>
                                    </div>
                                    <div class="right">
                                        <div class="select-style-1">
                                            <div class="select-position select-sm">
                                                <select id="posted_ads_graph_select" class="light-bg" data-security-nonce="<?php echo wp_create_nonce('dashboard_graph_nonce'); ?>">
                                                    <option value="weekly"><?php echo esc_html__("Weekly", "adforest"); ?></option>
                                                    <option value="yearly"><?php echo esc_html__("Yearly", "adforest"); ?></option>
                                                    <option value="monthly"><?php echo esc_html__("Monthly", "adforest"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart">
                                    <div id="chart-loading-dash"
                                        class="loading-indicator"
                                        style="border-top:8px solid #242424;">
                                    </div>
                                    <canvas id="Chart1"
                                        style="width:100%;height:400px;"></canvas>
                                </div>
                            </div>
                        </div>
                        <!-- End Col -->
                        <?php
                        $author_id = get_current_user_id();
                        $daily_views = get_user_meta($author_id, 'daily_profile_views', true);
                        if (!is_array($daily_views)) {
                            $daily_views = [];
                        }
                        ?>
                        <div class="col-lg-5">
                            <div class="card-style mb-30">
                                <div class="title d-flex flex-wrap align-items-center justify-content-between">
                                    <div class="left">
                                        <h6 class="text-medium mb-30"><?php echo esc_html__("Profile Views", "adforest"); ?></h6>
                                    </div>
                                    <div class="right">
                                        <div class="select-style-1">
                                            <div class="select-position select-sm">
                                                <select id="sold_ads_graph_select" class="light-bg">
                                                    <option value="weekly"><?php echo esc_html__("Weekly", "adforest"); ?></option>
                                                    <option value="yearly"><?php echo esc_html__("Yearly", "adforest"); ?></option>
                                                    <option value="monthly"><?php echo esc_html__("Monthly", "adforest"); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart"
                                    id="profile-views-chart-container"
                                    data-views='<?php echo esc_attr(wp_json_encode($daily_views)); ?>'>
                                    <div id="chart-loading-profile-views"
                                        style="border-top: 8px solid #242424;"
                                        class="loading-indicator"></div>
                                    <canvas id="ProfileViewsChart"
                                        style="width:100%;height:400px;"></canvas>
                                </div>
                            </div>
                        </div>
                        <!-- End Col -->
                    </div>
                <?php } ?>
                <div class="row">
                    <!-- Recently Viewed Table -->
                    <div class="col-lg-7">
                        <div class="card-style mb-30">
                            <div class="title d-flex flex-wrap justify-content-between">
                                <h6 class="text-medium"><?php echo esc_html__('Recently Viewed Ads', 'adforest') ?></h6>
                            </div>
                            <div class="row">
                                <?php echo display_recently_viewed_ad_posts(); ?>
                            </div>
                            <div class="mt-3"></div>
                        </div>
                    </div>
                    <!-- Recently Viewed Table -->
                    <div class="col-lg-5">
                        <?php
                        if ($adforest_theme['sb_allow_pkg_on_reg'] == '1') {
                            if (is_array($assigned_on_registration_packages) && count($assigned_on_registration_packages) > 0) {
                        ?>
                                <div class="card-style mb-30">
                                    <div class="title d-flex flex-wrap justify-content-between">
                                        <h6 class="text-medium"><?php esc_html_e('Default Package Assigned on Registration', 'adforest') ?></h6>
                                    </div>
                                    <div class="card-body py-0 package-details" data-simplebar="">
                                        <table class="table ">
                                            <tbody>
                                                <?php echo adforest_return_echo($paid_html) ?>
                                            </tbody>
                                        </table>

                                    </div>
                                </div>
                        <?php }
                        } ?>
                    </div>
                </div>

                <?php if (is_array($selected_categories) && count($selected_categories) > 0) { ?>
                    <div class="all-packages">
                        <h3><?php echo esc_html__("Purchased Packages", 'adforest') ?></h3>
                        <div class="row">
                            <?php
                            foreach ($selected_categories as $package_id => $details) :
                                $product_title = '';
                                $product = wc_get_product($package_id);
                                if ($product) {
                                    $product_title = $product->get_title();
                                }

                                if (!function_exists('display_package_value_enhanced')) {
                                    function display_package_value_enhanced($details, $key)
                                    {
                                        if (!isset($details[$key]) || $details[$key] === '' || $details[$key] === null) {
                                            return false;
                                        }

                                        $value = $details[$key];

                                        if ($value === '-1' || $value === -1) {
                                            return esc_html__("Unlimited", "adforest");
                                        }

                                        if (strtolower($value) === 'yes') {
                                            return esc_html__("Yes", "adforest");
                                        }
                                        if (strtolower($value) === 'no') {
                                            return esc_html__("No", "adforest");
                                        }

                                        return esc_html($value);
                                    }
                                }

                                if (!function_exists('display_expiry_date_enhanced')) {
                                    function display_expiry_date_enhanced($details, $key)
                                    {
                                        if (!isset($details[$key]) || $details[$key] === '' || $details[$key] === null) {
                                            return false;
                                        }

                                        $value = $details[$key];

                                        if ($value === '-1' || $value === -1) {
                                            return esc_html__("Unlimited", "adforest");
                                        }

                                        if (strtotime($value)) {
                                            return esc_html(date_i18n(get_option('date_format'), strtotime($value)));
                                        }

                                        return false;
                                    }
                                }

                                if (!function_exists('should_display_package_field')) {
                                    function should_display_package_field($details, $key)
                                    {
                                        return isset($details[$key]) && $details[$key] !== '' && $details[$key] !== null;
                                    }
                                }

                            ?>
                                <div class="col-xl-6 col-xxl-4">
                                    <div class="card-style mb-30">
                                        <div class="title d-flex flex-wrap justify-content-between">
                                            <h6 class="text-medium"><?php echo $product_title ? esc_html($product_title) : esc_html__("Unknown Package", "adforest"); ?></h6>
                                        </div>
                                        <ul>
                                            <?php if (should_display_package_field($details, 'free_ads')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Free Ads:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'free_ads'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'featured_ads')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Featured Ads:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'featured_ads'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'bump_ads')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Bump Ads:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'bump_ads'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php
                                            $expiry_date = display_expiry_date_enhanced($details, 'pkg_expiry_days');
                                            if ($expiry_date !== false):
                                            ?>
                                                <li>
                                                    <span><?php echo esc_html__("Package Expiry Date:", "adforest"); ?></span>
                                                    <span><?php echo $expiry_date; ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'ad_expiry_days')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Ad Expiry Days:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'ad_expiry_days'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'featured_expiry_days')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Featured Expiry Days:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'featured_expiry_days'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'video_links')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Video Links:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'video_links'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'num_of_images')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Number of Images:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'num_of_images'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'allow_tags')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Allow Tags:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'allow_tags'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'allow_bidding')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Allow Bidding:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'allow_bidding'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'number_of_events')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Number of Events:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'number_of_events'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php if (should_display_package_field($details, 'paid_biddings')): ?>
                                                <li>
                                                    <span><?php echo esc_html__("Paid Biddings:", "adforest"); ?></span>
                                                    <span><?php echo display_package_value_enhanced($details, 'paid_biddings'); ?></span>
                                                </li>
                                            <?php endif; ?>

                                            <?php
                                            $category_ids = isset($details['allow_cate']) && $details['allow_cate'] !== '' ? explode(',', $details['allow_cate']) : [];
                                            if (is_array($category_ids) && count($category_ids) > 0):
                                            ?>
                                                <li>
                                                    <span><?php echo esc_html__("Allowed Categories:", "adforest"); ?></span>
                                                    <span>
                                                        <?php
                                                        if ($category_ids[0] === 'all') {
                                                            echo esc_html__("All", "adforest");
                                                        } else {
                                                            $category_names = [];
                                                            foreach ($category_ids as $category_id) {
                                                                $term = get_term($category_id, 'ad_cats');
                                                                if ($term && !is_wp_error($term)) {
                                                                    $category_names[] = $term->name;
                                                                }
                                                            }
                                                            if (!empty($category_names)) {
                                                                echo esc_html(implode(', ', $category_names));
                                                            }
                                                        }
                                                        ?>
                                                    </span>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
            <?php
                }
            } ?>
        </div>
    </section>
    <!-- ========== section end ========== -->

    <!-- ========== footer start =========== -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 order-last order-md-first">
                    <div class="copyright text-center text-md-start">
                        <?php $footer_text = isset($adforest_theme['sb_dashbboard_footer']) ? $adforest_theme['sb_dashbboard_footer'] : ""; ?>
                        <?php echo wp_kses($footer_text, ADFOREST_ALLOWED_FORM_HTML) ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</main>
