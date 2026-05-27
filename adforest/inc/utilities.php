<?php
/* static string and localization */

add_action('wp_enqueue_scripts', 'adforest_static_strings', 100);
if (!function_exists('adforest_static_strings')) {
    function adforest_static_strings()
    {
        $string_array = apply_filters('adforest_get_static_string', []);
        wp_localize_script(
            'adforest-custom',
            'get_strings',
            $string_array
        );
        wp_localize_script(
            'firebase-custom',
            'get_strings',
            $string_array
        );
    }
}

/* make description link in theme options */
if (!function_exists('adforest_make_link')) {

    function adforest_make_link($url, $text)
    {
        return wp_kses("<a href='" . esc_url($url) . "' target='_blank'>", adforest_required_tags()) . $text . wp_kses('</a>', adforest_required_tags());
    }

}

/* Required tag */
if (!function_exists('adforest_required_tags')) {
    function adforest_required_tags()
    {
        return $allowed_tags = array(
            'div' => adforest_required_attributes(),
            'span' => adforest_required_attributes(),
            'p' => adforest_required_attributes(),
            'a' => array_merge(adforest_required_attributes(), array(
                'href' => array(),
                'rel' => array(),
                'target' => array('_blank', '_top'),
            )),
            'u' => adforest_required_attributes(),
            'br' => adforest_required_attributes(),
            'i' => adforest_required_attributes(),
            'q' => adforest_required_attributes(),
            'b' => adforest_required_attributes(),
            'ul' => adforest_required_attributes(),
            'ol' => adforest_required_attributes(),
            'li' => adforest_required_attributes(),
            'br' => adforest_required_attributes(),
            'hr' => adforest_required_attributes(),
            'strong' => adforest_required_attributes(),
            'blockquote' => adforest_required_attributes(),
            'del' => adforest_required_attributes(),
            'strike' => adforest_required_attributes(),
            'em' => adforest_required_attributes(),
            'code' => adforest_required_attributes(),
            'style' => adforest_required_attributes(),
            'script' => adforest_required_attributes(),
            'img' => adforest_required_attributes(),
        );
    }

}
/* Required attributes */
if (!function_exists('adforest_required_attributes')) {
    function adforest_required_attributes()
    {
        return $default_attribs = array(
            'id' => array(),
            'src' => array(),
            'href' => array(),
            'target' => array(),
            'class' => array(),
            'title' => array(),
            'type' => array(),
            'style' => array(),
            'data' => array(),
            'role' => array(),
            'aria-haspopup' => array(),
            'aria-expanded' => array(),
            'data-toggle' => array(),
            'data-hover' => array(),
            'data-animations' => array(),
            'data-mce-id' => array(),
            'data-mce-style' => array(),
            'data-mce-bogus' => array(),
            'data-href' => array(),
            'data-tabs' => array(),
            'data-small-header' => array(),
            'data-adapt-container-width' => array(),
            'data-height' => array(),
            'data-hide-cover' => array(),
            'data-show-facepile' => array(),
            'alt' => array(),
        );
    }

}

// Get user profile PIC
if (!function_exists('adforest_get_user_dp')) {
    function adforest_get_user_dp($user_id, $size = 'adforest-single-small')
    {
        global $adforest_theme;
        $user_pic = trailingslashit(esc_url(get_template_directory_uri())) . 'images/9.jpg';
        if (isset($adforest_theme['sb_user_dp']['url']) && $adforest_theme['sb_user_dp']['url'] != "") {
            $user_pic = $adforest_theme['sb_user_dp']['url'];
        }

        if (get_user_meta($user_id, '_sb_user_linkedin_pic', true) != "") {
            $user_pic = get_user_meta($user_id, '_sb_user_linkedin_pic', true);

            return $user_pic;
        }

        $image_link = array();
        if (get_user_meta($user_id, '_sb_user_pic', true) != "") {
            $attach_id = get_user_meta($user_id, '_sb_user_pic', true);
            $image_link = wp_get_attachment_image_src($attach_id, $size);
        }
        if (isset($image_link) && !empty($image_link) && is_array($image_link) && count($image_link) > 0) {
            if ($image_link[0] != "") {
                $headers = @get_headers($image_link[0]);
                if (!str_contains($headers[0], '404')) {
                    return $image_link[0];
                } else {
                    return $user_pic;
                }
            } else {
                return $user_pic;
            }
        } else {
            return $user_pic;
        }
    }

}

/* Select map type */
if (!function_exists('adforest_mapType')) {
    function adforest_mapType()
    {
        global $adforest_theme;
        $mapType = 'google_map';
        if (isset($adforest_theme['map-setings-map-type']) && $adforest_theme['map-setings-map-type'] != '') {
            $mapType = $adforest_theme['map-setings-map-type'];
        }
        if ($mapType === 'maplibre_map') {
            $mapType = 'leafletjs_map';
        }

        return $mapType;
    }

}

add_filter('adforest_get_static_string', 'adforest_get_static_string_fun', 10, 3);
if (!function_exists('adforest_get_static_string_fun')) {
    function adforest_get_static_string_fun()
    {
        if (!class_exists('Redux')) {
            return;
        }
        global $adforest_theme;
        $ajax_url = apply_filters('adforest_set_query_param', admin_url('admin-ajax.php'));
        $mapType = adforest_mapType();

        $user_id = get_current_user_id();

        $is_logged_in = 0;
        if (is_user_logged_in()) {
            $is_logged_in = 1;
        }
        $sb_packages_page = '';
        // $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
        $sb_profile_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_profile_page'] ?? "");


        $sb_after_login_page = isset($adforest_theme['sb_after_login_page']) && $adforest_theme['sb_after_login_page'] != '' ? $adforest_theme['sb_after_login_page'] : $sb_profile_page;

        /* ----------------------------------------------------------
         * Modern User Menu — redirect-after-login swap.
         *
         * When the admin enables "Modern User Menu" but the
         * After-Login page is still set to the classic Profile
         * page, sending users back there feels broken — the rest of
         * the site is using the modern pages. We resolve a sensible
         * modern landing page so users don't bounce to the old UI:
         *
         *   1. If `sb_modern_after_login_page` is explicitly set,
         *      always use it (full admin control).
         *   2. Else, if the resolved page is the classic Profile
         *      page, auto-swap to `sb_modern_settings_page`
         *      (closest 1:1 mapping for "Profile"), falling back to
         *      `sb_modern_my_listings_page` if Settings isn't set.
         *
         * Classic behavior is untouched when Modern User Menu is
         * disabled.
         * -------------------------------------------------------- */
        $modern_menu_on = !empty($adforest_theme['sb_header_user_menu_style']);
        if ($modern_menu_on) {
            $modern_override = isset($adforest_theme['sb_modern_after_login_page']) ? $adforest_theme['sb_modern_after_login_page'] : '';
            if (!empty($modern_override)) {
                $sb_after_login_page = $modern_override;
            } elseif (!empty($sb_profile_page) && $sb_after_login_page == $sb_profile_page) {
                // Auto-swap classic Profile → Modern Settings → Modern My Listings
                $modern_settings = isset($adforest_theme['sb_modern_settings_page']) ? $adforest_theme['sb_modern_settings_page'] : '';
                $modern_listings = isset($adforest_theme['sb_modern_my_listings_page']) ? $adforest_theme['sb_modern_my_listings_page'] : '';
                if (!empty($modern_settings)) {
                    $sb_after_login_page = $modern_settings;
                } elseif (!empty($modern_listings)) {
                    $sb_after_login_page = $modern_listings;
                }
            }
        }

        $sb_after_login_page = apply_filters('adforest_language_page_id', $sb_after_login_page);

        $sb_profile_page = get_the_permalink($sb_profile_page);


        $sb_after_login_page = get_the_permalink($sb_after_login_page);
        if (isset($_GET['u']) && $_GET['u'] != "") {
            $sb_after_login_page = $_GET['u'];
        }
        $sb_2column = isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true;

        $tags_limit_val = !empty($adforest_theme['ad_post_tags_limit']) && $adforest_theme['ad_post_tags_limit'] > 0 ? $adforest_theme['ad_post_tags_limit'] : 10;

        $sb_upload_limit_admin = !empty($adforest_theme['sb_upload_limit']) && $adforest_theme['sb_upload_limit'] > 0 ? $adforest_theme['sb_upload_limit'] : 0;

        $user_packages_images = get_user_meta(get_current_user_id(), '_sb_num_of_images', true);
        //$user_upload_max_images = isset($user_packages_images) && !empty($user_packages_images) ? $user_packages_images : $sb_upload_limit_admin;

        if (isset($user_packages_images) && $user_packages_images == '-1') {
            $user_upload_max_images = 'null';
        } elseif (isset($user_packages_images) && $user_packages_images > 0) {
            $user_upload_max_images = $user_packages_images;
        } else {
            $user_upload_max_images = $sb_upload_limit_admin;
        }

        $auto_slide = 1000;
        if (isset($adforest_theme['sb_auto_slide_time']) && $adforest_theme['sb_auto_slide_time'] != "") {
            $auto_slide = $adforest_theme['sb_auto_slide_time'];
        }

        $yes = 0;
        $not_time = '';
        $unread_msgs = 0;

        if (isset($adforest_theme['msg_notification_on']) && isset($adforest_theme['communication_mode']) && ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'message')) {
            $yes = $adforest_theme['msg_notification_on'];
            $not_time = $adforest_theme['msg_notification_time'];
        }
        $rtl = (bool)is_rtl();
        $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
        $packages_page_link = isset($sb_packages_page) ? esc_url(get_the_permalink($sb_packages_page)) : "";
        $pay_per_post_option = isset($adforest_theme['sb_pay_per_post_option']) ? $adforest_theme['sb_pay_per_post_option'] : false;

        return array(
            'ajax_url' => $ajax_url,
            'adforest_map_type' => $mapType,
            'cat_pkg_error' => sprintf("%s %s %s", '' . esc_html__("Whoops! you are not allowed to ad post in this category.Please buy another package.", "adforest") . '', '<a href =  "' . get_the_permalink($sb_packages_page) . '"> ' . esc_html__('Click here ', 'adforest') . ' </a>', esc_html__("to visit Packages page", "adforest")),
            'google_recaptcha_type' => isset($adforest_theme['google-recaptcha-type']) ? $adforest_theme['google-recaptcha-type'] : "",
            'google_recaptcha_site_key' => isset($adforest_theme['google_api_key']) ? $adforest_theme['google_api_key'] : "",
            'profile_page' => $sb_profile_page,
            'sb_after_login_page' => $sb_after_login_page,
            'facebook_key' => isset($adforest_theme['fb_api_key']) ? $adforest_theme['fb_api_key'] : "",
            'google_key' => isset($adforest_theme['gmail_api_key']) ? $adforest_theme['gmail_api_key'] : "",
            'redirect_uri' => isset($adforest_theme['redirect_uri']) ? $adforest_theme['redirect_uri'] : "",
            'sb_2_column' => $sb_2column,
            'max_upload_images' => sprintf(__('No more images please.you can only upload %d', 'adforest'), $user_upload_max_images),
            'zero' => __('Zero Star', 'adforest'),
            'one' => __('One Star', 'adforest'),
            'two' => __('Two Stars', 'adforest'),
            'three' => __('Three Stars', 'adforest'),
            'four' => __('Four Stars', 'adforest'),
            'five' => __('Five Stars', 'adforest'),
            'Sunday' => __('Sunday', 'adforest'),
            'Monday' => __('Monday', 'adforest'),
            'Tuesday' => __('Tuesday', 'adforest'),
            'Wednesday' => __('Wednesday', 'adforest'),
            'Thursday' => __('Thursday', 'adforest'),
            'Friday' => __('Friday', 'adforest'),
            'Saturday' => __('Saturday', 'adforest'),
            'Sun' => __('Sun', 'adforest'),
            'Mon' => __('Mon', 'adforest'),
            'Tue' => __('Tue', 'adforest'),
            'Wed' => __('Wed', 'adforest'),
            'Thu' => __('Thu', 'adforest'),
            'Fri' => __('Fri', 'adforest'),
            'Sat' => __('Sat', 'adforest'),
            'Su' => __('Su', 'adforest'),
            'Mo' => __('Mo', 'adforest'),
            'Tu' => __('Tu', 'adforest'),
            'We' => __('We', 'adforest'),
            'Th' => __('Th', 'adforest'),
            'Fr' => __('Fr', 'adforest'),
            'Sa' => __('Sa', 'adforest'),
            'January' => __('January', 'adforest'),
            'February' => __('February', 'adforest'),
            'March' => __('March', 'adforest'),
            'April' => __('April', 'adforest'),
            'May' => __('May', 'adforest'),
            'June' => __('June', 'adforest'),
            'July' => __('July', 'adforest'),
            'August' => __('August', 'adforest'),
            'September' => __('September', 'adforest'),
            'October' => __('October', 'adforest'),
            'November' => __('November', 'adforest'),
            'December' => __('December', 'adforest'),
            'Jan' => __('Jan', 'adforest'),
            'Feb' => __('Feb', 'adforest'),
            'Mar' => __('Mar', 'adforest'),
            'Apr' => __('Apr', 'adforest'),
            'May' => __('May', 'adforest'),
            'Jun' => __('Jun', 'adforest'),
            'Jul' => __('July', 'adforest'),
            'Aug' => __('Aug', 'adforest'),
            'Sep' => __('Sep', 'adforest'),
            'Oct' => __('Oct', 'adforest'),
            'Nov' => __('Nov', 'adforest'),
            'Dec' => __('Dec', 'adforest'),
            'Today' => __('Today', 'adforest'),
            'Clear' => __('Clear', 'adforest'),
            'dateFormat' => __('dateFormat', 'adforest'),
            'timeFormat' => __('timeFormat', 'adforest'),
            'required_images' => __('Images are required.', 'adforest'),
            'auto_slide_time' => $auto_slide,
            'msg_notification_on' => esc_attr($yes),
            'msg_notification_time' => esc_attr($not_time),
            'is_logged_in' => $is_logged_in,
            'select_place_holder' => __('Select an option', 'adforest'),
            'adforest_tags_limit_val' => $tags_limit_val,
            'adforest_tags_limit' => __('Oops ! you have exceeded your tags limit.', 'adforest'),
            'is_rtl' => $rtl,
            'sub_cat_option_select' => $adforest_theme['is_sub_cat_required'] ?? "",
            'confirm' => __('Are you sure?', 'adforest'),
            'not_logged_in' => __("Please login first to post an Ad.", "adforest"),
            'select_package' => __("Please Select your listing package.", "adforest"),
            'demo_mode' => __("Not allowed in demo mode", "adforest"),
            'val_not_found' => __('Value does not exist in the array', 'adforest'),
            'ad_update_success' => __("Ad updated successfully.", "adforest"),
            'ad_posted' => __("Ad Posted successfully.", "adforest"),
            '_nonce_error' => __("There is something wrong with the security please check the admin panel.", "adforest"),
            'verification_notice' => __("Verification code has been sent to ", "adforest"),
            'invalid_phone' => __('Invalid format , Valid format is +16505551234', 'adforest'),
            'images_required_on_ad_post' => isset($adforest_theme['sb_default_img_required']) ? $adforest_theme['sb_default_img_required'] : '',
            'images_required_error' => __("At least one image is required", "adforest"),
            'main_btn_color_text' => isset($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : "",
            'main_btn_color' => $adforest_theme['opt-theme-btn-color']['regular'] ? $adforest_theme['opt-theme-btn-color']['regular'] : "",
            'main_btn_hover_color_text' => isset($adforest_theme['opt-theme-btn-text-color']['hover']) ? $adforest_theme['opt-theme-btn-text-color']['hover'] : "",
            'no_more_ads' => esc_html__("No More Ads Found!", "adforest"),
            'show_more_btn_text' => esc_html__("Show More", "adforest"),
            'verify_account_msg' => esc_html__("Verification Email has been sent. Please verify your account.", "adforest"),
            "event_started" => esc_html__("Event Started", "adforest"),
            "not_sub_cat_text" => esc_html__("Please select a child category to continue", "adforest"),
            "additional_fields_text" => esc_html__("Additional Fields", "adforest"),
            "sb_default_adpost_template_on" => isset($adforest_theme['sb_default_adpost_template_on']) ? $adforest_theme['sb_default_adpost_template_on'] : "",
            "sb_default_adpost_template_images" => $adforest_theme['sb_default_adpost_template_images'],
            "remaining_featured_ads" => __("Remaining Featured Ads: ", "adforest"),
            "unlimited_string" => __("Unlimited: ", "adforest"),
            "no_featured_ads" => __("No featured ads in selected package, please select a different package or buy a new one", "adforest"),
            "here" => __("here", "adforest"),
            "packages_page_link" => $packages_page_link,
            "pay_per_post_option" => $pay_per_post_option,
            "pay_per_post_option_no_products" => __("No Packages for Selected Category", "adforest"),
            "success" => esc_html__("Success", "adforest"),
            "pkg_success" => esc_html__("Package selected successfully", "adforest"),
            "pkg_required" => esc_html__("Required Fields", "adforest"),
            "pkg_error" => esc_html__("Please select required fields", "adforest"),
            "multiple_validation_errors" => esc_html__("Multiple Validation Errors", "adforest"),
            "check_following_tabs" => esc_html__("Please check the following tabs", "adforest"),
            "fill_all_fields" => esc_html__("Please fill in all required fields", "adforest"),
            "form_validation_error" => esc_html__("Form Validation Error", "adforest"),
            "error_in" => esc_html__("Error in", "adforest"),
            "fields_need_attention" => esc_html__("fields need attention", "adforest"),
            "admin_allow_unlimited_ads" => $adforest_theme['admin_allow_unlimited_ads'],
            "is_current_user_admin" => current_user_can('administrator') ? 'admin' : 'not_admin',
            "select_option" => esc_html__("Select Option", "adforest"),
            "enter_otp" => esc_html__("Please enter the verification code.", "adforest"),
            "sb_display_phone_num_secure" => wp_create_nonce('sb_display_phone_num_secure'),
            "ad_posting_mode" => isset($adforest_theme['adforest_ad_posting_mode']) ? $adforest_theme['adforest_ad_posting_mode'] : 'paid_post',
            "nonce" => wp_create_nonce('adforest_nonce'),
            "max_upload_images_allowed_pkg" => __("Maximum number of images allowed in your package is", "adforest"),
            "image_removed_successfully" => __("Image Removed Successfully.", "adforest"),
            "redirecting" => __("Redirecting", "adforest"),
            "marking_all_read" => __("Marking all as read", "adforest"),
            "all_read" => __("All read", "adforest"),
        );
    }
}

if (!function_exists('adforest_set_url_param')) {
    function adforest_set_url_param($adforest_url = '', $key = '', $value = '')
    {
        if ($adforest_url != '') {
            $adforest_url = add_query_arg(array($key => $value), $adforest_url);
            $adforest_url = apply_filters('adforest_page_lang_url', $adforest_url);
        }

        return $adforest_url;
    }

}

/**/
if (!function_exists('adforest_return_echo')) {
    function adforest_return_echo($html = '')
    {
        return $html;
    }

}

/* get feature image */
if (!function_exists('adforest_get_feature_image')) {
    function adforest_get_feature_image($post_id, $image_size)
    {
        return wp_get_attachment_image_src(get_post_thumbnail_id(esc_html($post_id)), $image_size);
    }
}

if (!function_exists('adforest_get_date')) {
    function adforest_get_date($PID)
    {
        if (!$PID) {
            return;
        }
        echo get_the_date(get_option('date_format'), $PID);
    }
}

if (!function_exists('adforest_social_share')) {
    function adforest_social_share()
    {
        if (in_array('add-to-any/add-to-any.php', apply_filters('active_plugins', get_option('active_plugins')))) {

            return do_shortcode('[addtoany]');
        }
        $sbURL = esc_url(get_permalink());
        $sbTitle = str_replace(' ', '%20', esc_html(get_the_title()));
        $sbThumbnail = wp_get_attachment_image_src(get_post_thumbnail_id(esc_html(get_the_ID())), 'sb-single-blog-featured');
        $twitterURL = 'https://twitter.com/intent/tweet?text=' . $sbTitle . '&amp;url=' . $sbURL;
        $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u=' . $sbURL;
        $googleURL = 'https://plus.google.com/share?url=' . $sbURL;
        $bufferURL = 'https://bufferapp.com/add?url=' . $sbURL . '&amp;text=' . $sbTitle;
        $sbThumbnail[0] = isset($sbThumbnail[0]) ? $sbThumbnail[0] : "";

        $pinterestURL = 'https://pinterest.com/pin/create/button/?url=' . $sbURL . '&amp;media=' . $sbThumbnail[0] . '&amp;description=' . $sbTitle;

        return '<a href="' . esc_url($facebookURL) . '" class="btn btn-fb btn-md" target="_blank"><i class="fab fa-facebook-f"></i></a>
        <a href="' . esc_url($twitterURL) . '" class="btn btn-twitter btn-md" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="' . esc_url($googleURL) . '" class="btn btn-twitter btn-md" target="_blank"><i class="fab fa-google"></i></a>
        <a href="' . esc_url($pinterestURL) . '" class="btn btn-twitter btn-md" target="_blank"><i class="fab fa-pinterest"></i></a>
        <a href="' . esc_url($bufferURL) . '" class="btn btn-twitter btn-md" target="_blank"><i class="fa fa-plus"></i></a>
        ';
    }

}

// Adforest Comments List
if (!function_exists('adforest_comments_list')) {
    function adforest_comments_list($comment, $args, $depth)
    {
        $GLOBALS['comment'] = $comment;
        $img = '';
        if (get_avatar_url($comment, 44) != "") {
            $img = '<img class="pull-left hidden-xs img-circle" alt="' . esc_attr__('Avatar', 'adforest') . '" src="' . esc_url(get_avatar_url($comment, 44)) . '" />';
        }
        ?>
        <li class="comment" id="comment-<?php esc_attr(comment_ID()); ?>">
        <div class="comment-info">
            <?php echo "" . $img; ?>
        </div>
        <div class="author-desc">
            <div class="author-title">
                <strong><?php comment_author(); ?></strong>
                <ul class="list-inline pull-right">
                    <li>
                        <a href="javascript:void(0);"><?php echo esc_html(get_comment_date()) . " " . esc_html(get_comment_time()); ?></a>
                    </li>
                    <?php
                    $myclass = ' active-color';
                    $reply_link = preg_replace('/comment-reply-link/', 'comment-reply-link ' . $myclass, get_comment_reply_link(array_merge($args, array(
                        'reply_text' => esc_attr__('Reply', 'adforest'),
                        'depth' => $depth,
                        'max_depth' => $args['max_depth']
                    ))), 1);
                    ?>
                    <?php if ($reply_link != "") { ?>
                    <li><?php echo wp_kses($reply_link, adforest_required_tags()); ?>
                        <?php } ?>
                    </li>
                </ul>
            </div>
            <?php comment_text(); ?>
        </div>
        <?php
        //  if ($args['has_children'] == "") {
        echo '</li>';
        //}
        ?>
        <?php
    }
}

if (!trait_exists('adforest_reuse_functions')) {
    trait adforest_reuse_functions
    {
        function adforect_widget_open($instance)
        {
            global $adforest_theme;
            if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'sidebar' || $adforest_theme['search_design'] == "map") {
                $open_widget = 0;
                if (isset($instance['open_widget'])) {
                    $open_widget = $instance['open_widget'];
                }

                $open_selected = $close_selected = '';
                if ($open_widget == '1') {
                    $open_selected = 'selected="selected"';
                } else {
                    $close_selected = 'selected="selected"';
                }

                $open_html = '<p><label for="' . esc_attr($this->get_field_id('open_widget')) . '" > ' . esc_html__('Widget behaviour:', 'adforest') . '</label> <select  class="widefat" id="' . esc_attr($this->get_field_id('open_widget')) . '" name="' . esc_attr($this->get_field_name('open_widget')) . '"><option value="1"' . esc_attr($open_selected) . '>' . __('Open', 'adforest') . '</option><option value="0"' . esc_attr($close_selected) . '>' . __('Close', 'adforest') . '</option></select></p>';
                echo adforest_return_echo($open_html);
            }
        }

        function adforect_enable_showmore_on_cats($instance)
        {
            global $adforest_theme;
            if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'sidebar' || $adforest_theme['search_design'] == "map") {
                $open_widget = 0;
                if (isset($instance['show_more_cate'])) {
                    $open_widget = $instance['show_more_cate'];
                }

                $open_selected = $close_selected = '';
                if ($open_widget == '1') {
                    $open_selected = 'selected="selected"';
                } else {
                    $close_selected = 'selected="selected"';
                }

                $open_html = '<p><label for="' . esc_attr($this->get_field_id('show_more_cate')) . '" > ' . esc_html__('Enable Show More Categories?', 'adforest') . '</label> <select  class="widefat" id="' . esc_attr($this->get_field_id('show_more_cate')) . '" name="' . esc_attr($this->get_field_name('show_more_cate')) . '"><option value="1"' . esc_attr($open_selected) . '>' . __('Yes', 'adforest') . '</option><option value="0"' . esc_attr($close_selected) . '>' . __('No', 'adforest') . '</option></select></p>';
                echo adforest_return_echo($open_html);
            }
        }

    }

}

/* Translation */
if (!function_exists('adforest_translate')) {
    function adforest_translate($index)
    {
        $strings = array(
            'variation_not_available' => __('This product is currently out of stock/unavailable.', 'adforest'),
            'adding_to_cart' => __('Adding...', 'adforest'),
            'add_to_cart' => __('add to cart', 'adforest'),
            'view_cart' => __('View Cart', 'adforest'),
            'cart_success_msg' => __('Product Added successfully.', 'adforest'),
            'cart_success' => __('Success', 'adforest'),
            'cart_error_msg' => __('Something went wrong, please try again.', 'adforest'),
            'cart_error' => __('Error', 'adforest'),
            'email_error_msg' => __('Please enter a valid email.', 'adforest'),
            'mc_success_msg' => __('Thank you, we will get back to you.', 'adforest'),
            'mc_error_msg' => __('There is some error, please check your API-KEY and LIST-ID.', 'adforest'),
        );

        return $strings[$index];
    }

}

/* check is user login or not */
if (!function_exists('adforest_user_logged_in')) {
    function adforest_user_logged_in()
    {
        if (get_current_user_id() != 0) {
            echo adforest_redirect(home_url('/'));
            exit;
        }
    }
}

/* adforest redirect */
if (!function_exists('adforest_redirect')) {
    function adforest_redirect($url = '')
    {
        return "<script> var red_url = decodeURI('{$url}'); window.location = red_url;console.log(red_url);</script>";
    }
}

// check page build with elementor /
if (!function_exists('sb_is_elementor')) {
    function sb_is_elementor($page_id)
    {
        if (class_exists('Elementor\Plugin')) {
            // return \Elementor\Plugin::$instance->db->is_built_with_elementor($page_id);

            return \Elementor\Plugin::$instance->documents->get($page_id)->is_built_with_elementor($page_id);
        } else {
            return false;
        }
    }
}

if (!function_exists('adforest_returnImgSrc')) {
    function adforest_returnImgSrc($id, $size = 'full', $showHtml = false, $class = '', $alt = '')
    {
        $img = '';
        if (isset($id) && $id != "") {
            if ($showHtml == false) {
                $img1 = wp_get_attachment_image_src($id, $size);
                $img = (isset($img1[0])) ? $img1[0] : '';
            } else {
                $class = ($class != "") ? 'class="' . esc_attr($class) . '"' : '';
                $alt = ($alt != "") ? 'alt="' . esc_attr($alt) . '"' : '';
                $img1 = wp_get_attachment_image_src($id, $size);
                $img = '<img src="' . esc_url($img1[0]) . '" ' . $class . ' ' . $alt . '>';
            }
        }

        return $img;
    }
}

if (!function_exists('adforest_verify_sms_gateway')) {
    function adforest_verify_sms_gateway()
    {
        global $adforest_theme;
        $gateway = '';
        if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification'] && class_exists('WP_Twilio_Core')) {
            $gateway = 'twilio';
        } else if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification'] && in_array('wp-iletimerkezi-sms/core.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $gateway = 'iletimerkezi-sms';
        }

        return $gateway;
    }
}

if (!function_exists('adforest_clean_shortcode')) {
    function adforest_clean_shortcode($string)
    {
        $replace = str_replace("`{`", "[", $string);
        $replace = str_replace("`}`", "]", $replace);
        $replace = str_replace("``", '"', $replace);

        return $replace;
    }
}

/* get ads category */
if (!function_exists('adforest_get_cats')) {
    function adforest_get_cats($taxonomy = 'category', $parent_of = 0, $child_of = 0, $type = 'general')
    {
        global $adforest_theme;
        $search_popup_cat_disable = isset($adforest_theme['search_popup_cat_disable']) ? $adforest_theme['search_popup_cat_disable'] : false;
        $search_popup_loc_disable = isset($adforest_theme['search_popup_loc_disable']) ? $adforest_theme['search_popup_loc_disable'] : false;

        $show_all_terms = false;
        if ($search_popup_cat_disable && $taxonomy == 'ad_cats') {
            $show_all_terms = true;
        }
        if ($search_popup_loc_disable && $taxonomy == 'ad_country') {
            $show_all_terms = true;
        }

        if ($type == 'post_ad') {
            $show_all_terms = false;
        }

        $defaults = array(
            'taxonomy' => $taxonomy,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'exclude' => array(),
            'exclude_tree' => array(),
            'number' => '',
            'offset' => '',
            'fields' => 'all',
            'name' => '',
            'slug' => '',
            'hierarchical' => true,
            'search' => '',
            'name__like' => '',
            'description__like' => '',
            'pad_counts' => false,
            'get' => '',
            'child_of' => $child_of,
            'parent' => $parent_of,
            'childless' => false,
            'cache_domain' => 'core',
            'update_term_meta_cache' => true,
            'meta_query' => ''
        );
        $defaults = apply_filters('adforest_wpml_show_all_posts', $defaults);

        if (taxonomy_exists($taxonomy)) {
            return get_terms($defaults);
        } else {
            return array();
        }
    }
}

add_action('adforest_validate_phone_verification', 'adforest_validate_phone_verification');
if (!function_exists('adforest_validate_phone_verification')) {
    function adforest_validate_phone_verification()
    {
        global $adforest_theme;
        $page_url = home_url('/');
        $sb_profile_page = isset($adforest_theme['sb_profile_page']) && $adforest_theme['sb_profile_page'] != '' ? $adforest_theme['sb_profile_page'] : get_option('page_on_front');

        $sb_profile_page = apply_filters('adforest_language_page_id', $sb_profile_page);

        if (is_user_logged_in()) {
            $enable_phone_verification = isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification'] ? true : false;
            $ad_post_with_phone_verification = isset($adforest_theme['ad_post_restriction']) && $adforest_theme['ad_post_restriction'] == 'phn_verify' ? true : false;
            if ($enable_phone_verification && $ad_post_with_phone_verification) {
                $user_id = get_current_user_id();
                if (get_user_meta($user_id, '_sb_is_ph_verified', true) != '1') {
                    $page_url = adforest_set_url_param(get_permalink($sb_profile_page), 'page_type', 'my_profile');
                    $msg = esc_html__('Please verify your phone number first', 'adforest');
                    echo '<script type="text/javascript" src="' . trailingslashit(esc_url(get_template_directory_uri())) . 'assets/js/toastr.min.js"></script><script type="text/javascript">toastr.error("' . $msg . '", "", {timeOut: 2500,"closeButton": true, "positionClass": "toast-top-right"});window.location =   "' . $page_url . '";</script>';
                }
            }
        }
    }
}

if (!function_exists('adforest_login_with_redirect_url_param')) {
    function adforest_login_with_redirect_url_param($redirect_url = '')
    {
        global $adforest_theme;
        $final_redi_url = '';
        $red_url = '';
        $sb_sign_in_page = isset($adforest_theme['sb_sign_in_page']) ? apply_filters('adforest_language_page_id', $adforest_theme['sb_sign_in_page']) : "#";
        $login_page_url = isset($adforest_theme['sb_sign_in_page']) && !empty($adforest_theme['sb_sign_in_page']) ? get_the_permalink($sb_sign_in_page) : home_url('/');
        if ($redirect_url != '') {
            $query_url = parse_url($login_page_url, PHP_URL_QUERY);
            if ($query_url) {
                $red_url = '&u=' . $redirect_url;
            } else {
                $red_url = '?u=' . $redirect_url;
            }
        }
        $final_redi_url = $login_page_url . $red_url;
        $final_redi_url = apply_filters('adforest_page_lang_url', $final_redi_url);

        return $final_redi_url;
    }
}

/* get current page url */
if (!function_exists('adforest_get_current_url')) {
    function adforest_get_current_url()
    {
        $site_url = site_url();
        $findme = 'https';
        if (strpos($site_url, $findme) !== false) {
            return $actual_link = "https://" . "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        } else {
            return $actual_link = "http://" . "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }
    }
}

if (!function_exists('adforest_get_all_countries')) {
    function adforest_get_all_countries()
    {
        $res = array();
        if (!is_admin()) {
            return $res;
        }
        $args = array(
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_type' => '_sb_country',
            'post_status' => 'publish',
        );
        $countries = get_posts($args);
        foreach ($countries as $country) {
            $stripped = trim(preg_replace('/\s+/', ' ', $country->post_excerpt));
            $res[$stripped] = $country->post_title;
        }

        return $res;
    }
}

if (!function_exists('adforest_get_products_theme_options')) {
    function adforest_get_products_theme_options()
    {
        $packages_arr = array('' => __('Select a package', 'adforest'));
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'fields' => 'ids',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'orderby' => 'ID',
            );
            $the_query = new WP_Query($args);

            // The Loop
            if ($the_query->have_posts()):
                while ($the_query->have_posts()):
                    $the_query->the_post();
                    global $post;
                    $packages_arr[$post] = get_the_title($post);
                endwhile;
            endif;

            // Reset Post Data
            wp_reset_postdata();

            return $packages_arr;
        }
    }

}

/* Return adforest ad statuses */
if (!function_exists('adforest_is_demo')) {
    function adforest_is_demo()
    {
        global $adforest_theme;

        $restrict_phone_show = (isset($adforest_theme['is_demo'])) ? $adforest_theme['is_demo'] : false;

        return $restrict_phone_show;
    }

}

// add_action('admin_notices', 'adforest_sample_admin_notice_activate');
if (!function_exists('adforest_randomString')) {
    function adforest_randomString($length = 50)
    {
        $str = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        return $str;
    }

}

if (!function_exists('adforest_get_static_form')) {
    function adforest_get_static_form($term_id = '', $post_id = '')
    {
        $html = '';
        $display_size = '';
        $price = '';
        $required = '';
        global $adforest_theme;
        $size_arr = explode('-', $adforest_theme['sb_upload_size']);
        $display_size = $size_arr[1];
        $actual_size = $size_arr[0];

        $adforest_ad_type_strings = array(
            'Buy' => __('Buy', 'adforest'),
            'Exchange' => __('Exchange', 'adforest'),
            'lost_and_found' => __('Lost And Found', 'adforest'),
            'sell' => __('Sell', 'adforest')
        );

        $adforest_ad_type_array = array();
        if (isset($adforest_theme['sb_ad_types']) && count($adforest_theme['sb_ad_types']) > 0) {
            $sb_ad_types = $adforest_theme['sb_ad_types'];
        } else if (isset($adforest_theme['sb_ad_types']) && count($adforest_theme['sb_ad_types']) == 0 && isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] == "") {
            $sb_ad_types = array(
                'Buy' => __('Buy', 'adforest'),
                'Exchange' => __('Exchange', 'adforest'),
                'lost_and_found' => __('Lost And Found', 'adforest'),
                'sell' => __('Sell', 'adforest')
            );
        } else {
            $sb_ad_types = array();
        }

        foreach ($sb_ad_types as $p_val) {
            if (isset($adforest_ad_type_strings[$p_val])) {
                $adforest_ad_type_array[$p_val] = $adforest_ad_type_strings[$p_val];
            } else {
                $adforest_ad_type_array[$p_val] = __('Unknown', 'adforest');
            }
        }

        $_sb_video_links = get_user_meta(get_current_user_id(), '_sb_video_links', true);
        $_sb_allow_tags = get_user_meta(get_current_user_id(), '_sb_allow_tags', true);

        if (!apply_filters('adforest_directory_enabled', false)) {
            // Get Price Field ,
            $vals[] = array(
                'type' => 'select_custom',
                'post_meta' => '_adforest_ad_type',
                'is_show' => '_sb_default_cat_ad_type_show',
                'is_req' => '_sb_default_cat_ad_type_required',
                'main_title' => __('Type of Ad', 'adforest'),
                'sub_title' => '',
                'field_name' => 'buy_sell',
                'field_id' => 'buy_sell',
                'field_value' => $adforest_ad_type_array,
                'field_req' => 1,
                'cat_name' => 'ad_type',
                'field_class' => ' category ',
                'columns' => '12',
                'data-parsley-type' => '',
                'data-parsley-message' => __('This field is required.', 'adforest'),
            );
        }

        $currency_msg = $adforest_theme['sb_currency'] ?? "" . " " . __('only', 'adforest');
        $currenies = adforest_get_cats('ad_currency', 0);
        if (count($currenies) > 0) {
            $currency_msg = '';
        }

        $sb_price_types_strings = array(
            'Fixed' => __('Fixed', 'adforest'),
            'Negotiable' => __('Negotiable', 'adforest'),
            'on_call' => __('Price on call', 'adforest'),
            'auction' => __('Auction', 'adforest'),
            'free' => __('Free', 'adforest'),
            'no_price' => __('No price', 'adforest')
        );

        $new_types_array = array();
        if (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) > 0) {
            $sb_price_types = $adforest_theme['sb_price_types'];
        } else if (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) == 0 && isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] == "") {
            $sb_price_types = array('Fixed', 'Negotiable', 'on_call', 'auction', 'free', 'no_price');
        } else {
            $sb_price_types = array();
        }

        $sb_price_types_html = '';
        foreach ($sb_price_types as $p_val) {
            $new_types_array[$p_val] = $sb_price_types_strings[$p_val];
        }
        if (isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] != "") {
            $sb_price_types_more_array = explode('|', $adforest_theme['sb_price_types_more']);
            foreach ($sb_price_types_more_array as $p_type_more) {
                $new_types_array[str_replace(' ', '_', $p_type_more)] = $p_type_more;
            }
        }


        $vals[] = array(
            'type' => 'select_custom',
            'post_meta' => '_adforest_ad_price_type',
            'is_show' => '_sb_default_cat_price_type_show',
            'is_req' => '_sb_default_cat_price_type_required',
            'main_title' => __('Price Type', 'adforest'),
            'sub_title' => '',
            'field_name' => 'ad_price_type',
            'field_id' => 'ad_price_type',
            'field_value' => $new_types_array,
            'field_req' => $required,
            'cat_name' => '',
            'field_class' => ' category ',
            'columns' => '12',
            'data-parsley-type' => '',
            'data-parsley-message' => __('This field is required.', 'adforest'),
        );

        $currenies = adforest_get_cats('ad_currency', 0);
        if (isset($currenies) && count($currenies) > 0) {
            $vals[] = array(
                'type' => 'select',
                'post_meta' => '_adforest_ad_currency',
                'is_show' => '_sb_default_cat_price_show',
                'is_req' => '_sb_default_cat_price_required',
                'main_title' => __('Currency', 'adforest'),
                'sub_title' => '',
                'field_name' => 'ad_currency',
                'field_id' => 'ad_currency',
                'field_value' => '',
                'field_req' => $required,
                'cat_name' => 'ad_currency',
                'field_class' => ' category curreny_class',
                'columns' => '12',
                'data-parsley-type' => '',
                'data-parsley-message' => __('This field is required.', 'adforest'),
            );
        }

        if (!apply_filters('adforest_directory_enabled', false)) {
            $vals[] = array(
                'type' => 'textfield',
                'post_meta' => '_adforest_ad_price',
                'is_show' => '_sb_default_cat_price_show',
                'is_req' => '_sb_default_cat_price_required',
                'main_title' => __('Price', 'adforest'),
                'sub_title' => $currency_msg,
                'field_name' => 'ad_price',
                'field_id' => 'ad_price',
                'field_value' => $price,
                'field_req' => $required,
                'cat_name' => '',
                'field_class' => '',
                'columns' => '12',
                'data-parsley-type' => 'digits',
                'data-parsley-message' => __('Can\'t be empty and only integers allowed.', 'adforest'),
            );
        } else {
            $vals = apply_filters('adforest_directory_template_ad_post_price', $vals);
        }

        if (isset($_sb_video_links) && !empty($_sb_video_links) && $_sb_video_links == 'no') {

        } else {

            if ($required) {
                $valid_text = __('This field is required and should be valid youtube video url.', 'adforest');
            } else {
                $valid_text = __('Should be valid youtube video url.', 'adforest');
            }
            $vals[] = array(
                'type' => 'textfield',
                'post_meta' => '_adforest_ad_yvideo',
                'is_show' => '_sb_default_cat_video_show',
                'is_req' => '_sb_default_cat_video_required',
                'main_title' => __('Youtube Video Link', 'adforest'),
                'sub_title' => '',
                'field_name' => 'ad_yvideo',
                'field_id' => 'ad_yvideo',
                'field_value' => '',
                'field_req' => $required,
                'cat_name' => '',
                'field_class' => '',
                'columns' => '12',
                'data-parsley-type' => 'url',
                'data-parsley-pattern' => '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',
                'data-parsley-message' => $valid_text,
            );
        }
        if (!apply_filters('adforest_directory_enabled', false)) {
            $vals[] = array(
                'type' => 'select',
                'post_meta' => '_adforest_ad_condition',
                'is_show' => '_sb_default_cat_condition_show',
                'is_req' => '_sb_default_cat_condition_required',
                'main_title' => __('Item Condition', 'adforest'),
                'sub_title' => '',
                'field_name' => 'condition',
                'field_id' => 'condition',
                'field_value' => '',
                'field_req' => $required,
                'cat_name' => 'ad_condition',
                'field_class' => ' category ',
                'columns' => '12',
                'data-parsley-type' => '',
                'data-parsley-message' => __('This field is required.', 'adforest'),
            );
            $vals[] = array(
                'type' => 'select',
                'post_meta' => '_adforest_ad_warranty',
                'is_show' => '_sb_default_cat_warranty_show',
                'is_req' => '_sb_default_cat_warranty_required',
                'main_title' => __('Item Warranty', 'adforest'),
                'sub_title' => '',
                'field_name' => 'ad_warranty',
                'field_id' => 'warranty',
                'field_value' => '',
                'field_req' => $required,
                'cat_name' => 'ad_warranty',
                'field_class' => ' category ',
                'columns' => '12',
                'data-parsley-type' => '',
                'data-parsley-message' => __('This field is required.', 'adforest'),
            );
        }

        $vals[] = array(
            'type' => 'image',
            'post_meta' => '',
            'is_show' => '_sb_default_cat_image_show',
            'is_req' => '_sb_default_cat_image_required',
            'main_title' => __('Click the box below to ad photos!', 'adforest'),
            'sub_title' => __('upload only jpg, png and jpeg files with a max file size of ', 'adforest') . $display_size,
            'field_name' => 'dropzone',
            'field_id' => 'dropzone',
            'field_value' => '',
            'field_req' => $required,
            'cat_name' => '',
            'field_class' => ' dropzone ',
            'columns' => '12',
            'data-parsley-type' => '',
            'data-parsley-message' => __('This field is required.', 'adforest'),
        );

        $is_video_allowed = isset($adforest_theme['sb_allow_upload_video']) ? $adforest_theme['sb_allow_upload_video'] : false;
        $max_upload_vid_size = isset($adforest_theme['sb_upload_video_mb_limit']) ? $adforest_theme['sb_upload_video_mb_limit'] : 2;
        if ($is_video_allowed) {
            $vals[] = array(
                'type' => 'video',
                'post_meta' => '',
                'is_show' => '_sb_default_cat_video_show',
                'is_req' => '_sb_default_cat_video_required',
                'main_title' => __('Click the box below to ad Videos', 'adforest'),
                'sub_title' => __('upload only videos (mp4, ogg, webm) files with a max file size of', 'adforest') . " " . $max_upload_vid_size,
                'field_name' => 'dropzone',
                'field_id' => 'dropzone_video',
                'field_value' => '',
                'field_req' => '',
                'cat_name' => '',
                'field_class' => ' dropzone ',
                'columns' => '12',
                'data-parsley-type' => '',
                'data-parsley-message' => __('This field is required.', 'adforest'),
            );
        }
        if (isset($_sb_allow_tags) && !empty($_sb_allow_tags) && $_sb_allow_tags == 'no') {

        } else {
            $vals[] = array(
                'type' => 'textfield',
                'post_meta' => '',
                'is_show' => '_sb_default_cat_tags_show',
                'is_req' => '_sb_default_cat_tags_required',
                'main_title' => __('Tags', 'adforest'),
                'sub_title' => __('Comma separated', 'adforest'),
                'field_name' => 'tags',
                'field_id' => 'tags',
                'field_value' => '',
                'field_req' => $required,
                'cat_name' => 'ad_tags',
                'field_class' => '',
                'columns' => '12',
                'data-parsley-type' => '',
                'data-parsley-message' => __('This field is required.', 'adforest'),
            );
        }
        foreach ($vals as $val) {
            $type = $val['type'];
            $html .= adforest_return_input($type, $post_id, $term_id, $val);
        }

        return $html;
    }

}

/* authentication check */
if (!function_exists('adforest_authenticate_check')) {
    function adforest_authenticate_check()
    {
        if (get_current_user_id() == "" || get_current_user_id() == 0) {
            echo '0|' . __("You are not logged in.", 'adforest');
            die();
        }
    }

}

add_action('wp_ajax_get_uploaded_ad_images', 'adforest_get_uploaded_ad_images');
if (!function_exists('adforest_get_uploaded_ad_images')) {
    function adforest_get_uploaded_ad_images()
    {
        check_ajax_referer('adforest_get_uploaded_ad_images_nonce', 'security');
        if ($_POST['is_update'] != "") {
            $ad_id = $_POST['is_update'];
        } else {
            $ad_id = get_user_meta(get_current_user_id(), 'ad_in_progress', true);
            if (get_post_status($ad_id) && $ad_id != "" && get_post_status($ad_id) != 'publish') {

            } else {
                return '';
                die();
            }
        }
        $media = adforest_get_ad_images($ad_id);
        $result = array();
        foreach ($media as $m) {
            $mid = '';
            $guid = '';
            if (isset($m->ID)) {
                $mid = $m->ID;
                //$guid	=	get_the_guid( $mid );
                $source = wp_get_attachment_image_src($mid, 'adforest-user-profile');
                $guid = $source[0];
            } else {
                $mid = $m;
                //$guid	=	get_the_guid( $mid );
                $source = wp_get_attachment_image_src($mid, 'adforest-user-profile');
                $guid = $source[0];
            }
            $obj = array();
            $obj['dispaly_name'] = basename(get_attached_file($mid));;
            $obj['name'] = $guid;
            $obj['size'] = filesize(get_attached_file($mid));
            $obj['id'] = $mid;
            $result[] = $obj;
        }
        header('Content-type: text/json');
        header('Content-type: application/json');
        echo json_encode($result);
        die();
    }

}

if (!function_exists('adforest_get_ad_images')) {
    function adforest_get_ad_images($pid)
    {
        global $adforest_theme;
        $re_order = get_post_meta($pid, '_sb_photo_arrangement_', true);
        if ($re_order != "") {
            return explode(',', $re_order);
        } else {
            return $attach_media = get_attached_media('', $pid);
        }
    }
}

add_action('wp_ajax_delete_ad_image', 'adforest_delete_ad_image');
if (!function_exists('adforest_delete_ad_image')) {
    function adforest_delete_ad_image()
    {
        check_ajax_referer('adforest_delete_ad_image_nonce', 'security');
        if (get_current_user_id() == "") {
            die();
        }
        if ($_POST['is_update'] != "") {
            $ad_id = $_POST['is_update'];
        } else {
            $ad_id = get_user_meta(get_current_user_id(), 'ad_in_progress', true);
        }
        if (!is_super_admin(get_current_user_id()) && get_post_field('post_author', $ad_id) != get_current_user_id()) {
            die();
        }

        $attachmentid = $_POST['img'];
        wp_delete_attachment($attachmentid, true);
        if (get_post_meta($ad_id, '_sb_photo_arrangement_', true) != "") {
            $ids = get_post_meta($ad_id, '_sb_photo_arrangement_', true);
            $res = str_replace($attachmentid, "", $ids);
            $res = str_replace(',,', ",", $res);
            $img_ids = trim($res, ',');
            update_post_meta($ad_id, '_sb_photo_arrangement_', $img_ids);
        }
        echo "1";
        die();
    }
}

if (!function_exists('adforest_set_date_timezone')) {
    function adforest_set_date_timezone()
    {
        global $adforest_theme;
        $time_zones_val = isset($adforest_theme['bid_timezone']) && $adforest_theme['bid_timezone'] != '' ? $adforest_theme['bid_timezone'] : 'Etc/UTC';
        if (function_exists('adforest_timezone_list') && isset($adforest_theme['bid_timezone']) && $adforest_theme['bid_timezone'] != '') {
            $time_zones_val = adforest_timezone_list('', $adforest_theme['bid_timezone']);
            if (!is_admin()) {
                date_default_timezone_set($time_zones_val);
            }
        } else {
            $time_zones_val = 'Etc/UTC';
            date_default_timezone_set($time_zones_val);
        }
    }
}
/* Bad word filter */
if (!function_exists('adforest_badwords_filter')) {
    function adforest_badwords_filter($words = array(), $string = "", $replacement = "")
    {
        foreach ($words as $word) {
            $string = preg_replace('/\b' . $word . '\b/iu', $replacement, $string);
        }

        return $string;
    }

}

/* Time difference n days */
if (!function_exists('adforest_days_diff')) {
    function adforest_days_diff($now, $from): float
    {
        $datediff = $now - $from;

        return floor($datediff / (60 * 60 * 24));
    }

}

if (!function_exists('adforest_setPostViews')) {
    function adforest_setPostViews($postID): void
    {
        $postID = esc_html($postID);
        $count_key = 'sb_post_views_count';
        $count = get_post_meta($postID, $count_key, true);
        if ($count == '') {
            $count = 0;
            delete_post_meta($postID, $count_key);
            add_post_meta($postID, $count_key, '0');
        } else {
            $count++;
            update_post_meta($postID, $count_key, $count);
        }
    }
}

if (!function_exists('adforest_fetch_reviews_average')) {
    function adforest_fetch_reviews_average($listing_id)
    {
        $comments = '';
        $get_rating_avrage = '';
        $one_star = '';
        $two_star = '';
        $three_star = '';
        $four_star = '';
        $five_star = '';
        $star1 = $star2 = $star3 = $star4 = $star5 = 0;
        $args = array(
            'type__in' => array('ad_post_rating'),
            'parent' => 0, // only parents
            'post_id' => $listing_id, // use post_id, not post_ID
        );
        $comments = get_comments($args);
        if (count($comments) > 0) {

            $sum_of_rated = 0;
            $no_of_times_rated = 0;
            foreach ($comments as $comment) {
                $rated = get_comment_meta($comment->comment_ID, 'review_stars', true);
                if ($rated != "" && $rated > 0) {
                    $sum_of_rated += $rated;
                    $no_of_times_rated++;
                    if ($rated == 1) {
                        $star1++;
                    }
                    if ($rated == 2) {
                        $star2++;
                    }
                    if ($rated == 3) {
                        $star3++;
                    }
                    if ($rated == 4) {
                        $star4++;
                    }
                    if ($rated == 5) {
                        $star5++;
                    }
                }
            }
            //loop end get avrage value
            if ($no_of_times_rated === 0) {
                return array(
                    'total_stars'    => '<i class="fa fa-star-o" aria-hidden="true"></i><i class="fa fa-star-o" aria-hidden="true"></i><i class="fa fa-star-o" aria-hidden="true"></i><i class="fa fa-star-o" aria-hidden="true"></i><i class="fa fa-star-o" aria-hidden="true"></i>',
                    'average'        => '0.0',
                    'rated_no_of_times' => 0,
                    'ratings'        => array('1_star' => 0, '2_star' => 0, '3_star' => 0, '4_star' => 0, '5_star' => 0),
                );
            }
            $get_rating_avrage = round($sum_of_rated / $no_of_times_rated, 2);
            $get_rating_avrage1 = round($sum_of_rated / $no_of_times_rated, 1);
            $one_star = round(($star1 / $no_of_times_rated) * 100);
            $two_star = round(($star2 / $no_of_times_rated) * 100);
            $three_star = round(($star3 / $no_of_times_rated) * 100);
            $four_star = round(($star4 / $no_of_times_rated) * 100);
            $five_star = round(($star5 / $no_of_times_rated) * 100);

            $total_stars = explode(".", $get_rating_avrage1);

            $stars_html = '';
            $first_part = (isset($total_stars[0]) && $total_stars[0] > 0 && $total_stars[0] != "") ? $total_stars[0] : 0;
            $second_part = (isset($total_stars[1]) && $total_stars[1] > 0 && $total_stars[1] != "") ? $total_stars[1] : 0;
            for ($stars = 1; $stars <= 5; $stars++) {
                if ($stars <= $first_part && $first_part > 0) {
                    $stars_html .= '<i class="fa fa-star color" aria-hidden="true"></i>';
                } else if ($stars == $first_part + 1 && $second_part <= 5 && $second_part > 0) {
                    $stars_html .= '<i class="fa fa-star-half-o color" aria-hidden="true"></i>';
                } else if ($stars == $first_part + 1 && $second_part > 5 && $second_part > 0) {
                    $stars_html .= '<i class="fa fa-star color" aria-hidden="true"></i>';
                } else {
                    $stars_html .= '<i class="fa fa-star-o" aria-hidden="true"></i>';
                }
            }
            if (strpos($get_rating_avrage, ".") !== false) {
            } else {
                $get_rating_avrage = $get_rating_avrage . '.0';
            }

            $array = array();
            $array['total_stars'] = $stars_html;
            $array['average'] = $get_rating_avrage;
            $array['rated_no_of_times'] = $no_of_times_rated;
            $array['ratings'] = array(
                '1_star' => $one_star,
                '2_star' => $two_star,
                '3_star' => $three_star,
                '4_star' => $four_star,
                '5_star' => $five_star
            );

            return $array;
        }
    }
}

if (!function_exists('add_recently_viewed_ad_post')) {
    /**
     * Track “recently viewed” ad_posts per user in user_meta,
     * keeping the most-recently viewed at index 0 and no duplicates.
     *
     * @param int $post_id The ad_post ID being viewed.
     * @param int $max Maximum number of entries to keep (default 10).
     */
    function add_recently_viewed_ad_post($post_id, $max = 10)
    {
        if (get_post_type($post_id) !== 'ad_post') {
            return;
        }

        $views = (int)get_post_meta($post_id, 'adforest_ad_views', true);
        update_post_meta($post_id, 'adforest_ad_views', $views + 1);

        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $meta_key = 'recently_viewed_ad_posts';

        $recent = get_user_meta($user_id, $meta_key, true);
        if (!is_array($recent)) {
            $recent = [];
        }

        if (false !== ($idx = array_search($post_id, $recent, true))) {
            unset($recent[$idx]);
        }

        array_unshift($recent, $post_id);

        $recent = array_slice($recent, 0, (int)$max);

        $recent = array_values($recent);

        update_user_meta($user_id, $meta_key, $recent);
    }
}

// Get lat lon by location
if (!function_exists('adforest_get_latlon')) {

    function adforest_get_latlon($location)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adforest_locations';
        // Explode location
        $address = explode(',', $location);
        if (count($address) == 1) {
            return array();
        }
        if (count($address) == 3) {
            $country = trim($address[2]);
            $state = trim($address[1]);
            $city = trim($address[0]);
        }
        if (count($address) == 4) {
            $country = trim($address[3]);
            $state = trim($address[2]);
            $city = trim($address[1]);
        } else if (count($address) == 2) {
            $country = trim($address[1]);
            $city = trim($address[0]);
        }

        $country = isset($country) ? sanitize_text_field($country) : '';
        $city = isset($city) ? sanitize_text_field($city) : '';

        $country_like = '%' . $wpdb->esc_like($country) . '%';
        $country_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_title LIKE %s",
                '_sb_country',
                $country_like
            )
        );
        if (count((array)$country_data) == 0) {
            return array();
        }
        $country_id = $country_data->ID;
        $arr = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT latitude,longitude FROM $table_name WHERE country_id = %d AND location_type = %s AND name = %s",
                (int)$country_id,
                'city',
                $city
            )
        );
        if (count((array)$arr) > 0) {
            if ($arr->latitude != "" && $arr->longitude != "") {
                return array($arr->latitude, $arr->longitude);
            }
        }

        return array();
    }

}

if (!function_exists('adforest_adPrice')) {
    function adforest_adPrice($id = '', $class = 'negotiable', $tag = 'h3')
    {
        if (get_post_meta($id, '_adforest_ad_price_type', true) == "range") {
            $price_from = get_post_meta($id, '_adforest_ad_price_from', true);
            $price_to = get_post_meta($id, '_adforest_ad_price_to', true);

            if ($price_from === "" && $price_to === "") {
                return '';
            }

            global $adforest_theme;
            $thousands_sep = isset($adforest_theme['sb_price_separator_remove']) && $adforest_theme['sb_price_separator_remove'] == '1' ? "" : ",";
            if (!empty($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator_remove'] != '1') {
                $thousands_sep = $adforest_theme['sb_price_separator'];
            }
            $decimals = isset($adforest_theme['sb_price_decimals']) ? (int)$adforest_theme['sb_price_decimals'] : 0;
            $decimals_separator = isset($adforest_theme['sb_price_decimals_separator']) ? $adforest_theme['sb_price_decimals_separator'] : '.';
            $curreny = get_post_meta($id, '_adforest_ad_currency', true) ?: $adforest_theme['sb_currency'];

            $formatted_from = is_numeric($price_from) ? number_format($price_from, $decimals, $decimals_separator, $thousands_sep) : $price_from;
            $formatted_to = is_numeric($price_to) ? number_format($price_to, $decimals, $decimals_separator, $thousands_sep) : $price_to;

            // Add currency direction
            if (isset($adforest_theme['sb_price_direction'])) {
                switch ($adforest_theme['sb_price_direction']) {
                    case 'right':
                        $formatted_from .= $curreny;
                        $formatted_to .= $curreny;
                        break;
                    case 'right_with_space':
                        $formatted_from .= " " . $curreny;
                        $formatted_to .= " " . $curreny;
                        break;
                    case 'left':
                        $formatted_from = $curreny . $formatted_from;
                        $formatted_to = $curreny . $formatted_to;
                        break;
                    case 'left_with_space':
                        $formatted_from = $curreny . " " . $formatted_from;
                        $formatted_to = $curreny . " " . $formatted_to;
                        break;
                    default:
                        $formatted_from = $curreny . $formatted_from;
                        $formatted_to = $curreny . $formatted_to;
                }
            }

            $price_range = $formatted_from . ' - ' . $formatted_to;

            if ($tag == 'h3') {
                return '<h3>' . $price_range . '</h3>';
            } else {
                return $price_range;
            }
        }

        if (get_post_meta($id, '_adforest_ad_price', true) == "" && get_post_meta($id, '_adforest_ad_price_type', true) == "on_call") {
            return __("Price On Call", 'adforest');
        }
        if (get_post_meta($id, '_adforest_ad_price', true) == "" && get_post_meta($id, '_adforest_ad_price_type', true) == "free") {
            return __("Free", 'adforest');
        }

        if (get_post_meta($id, '_adforest_ad_price', true) == "" || get_post_meta($id, '_adforest_ad_price_type', true) == "no_price") {
            return '';
        }

        $price = 0;
        global $adforest_theme;
        $thousands_sep = isset($adforest_theme['sb_price_separator_remove']) && $adforest_theme['sb_price_separator_remove'] == '1' ? "" : ",";

        if (isset($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator'] != "" && $adforest_theme['sb_price_separator_remove'] != '1') {
            $thousands_sep = $adforest_theme['sb_price_separator'];
        }
        $decimals = 0;
        if (isset($adforest_theme['sb_price_decimals']) && $adforest_theme['sb_price_decimals'] != "") {
            $decimals = $adforest_theme['sb_price_decimals'];
        }
        $decimals_separator = ".";
        if (isset($adforest_theme['sb_price_decimals_separator']) && $adforest_theme['sb_price_decimals_separator'] != "") {
            $decimals_separator = $adforest_theme['sb_price_decimals_separator'];
        }
        $curreny = $adforest_theme['sb_currency'];
        if (get_post_meta($id, '_adforest_ad_currency', true) != "") {
            $curreny = get_post_meta($id, '_adforest_ad_currency', true);
        }

        if ($id != "") {
            if (is_numeric(get_post_meta($id, '_adforest_ad_price', true))) {
                $price = number_format(get_post_meta($id, '_adforest_ad_price', true), $decimals, $decimals_separator, $thousands_sep);
            }

            $price = (isset($price) && $price != "") ? $price : 0;

            if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right') {
                $price = $price . $curreny;
            } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right_with_space') {
                $price = $price . " " . $curreny;
            } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left') {
                $price = $curreny . $price;
            } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left_with_space') {
                $price = $curreny . " " . $price;
            } else {
                $price = $curreny . $price;
            }
        }
        // Price type fixed or ...
        $price_type_html = '';
        if (get_post_meta($id, '_adforest_ad_price_type', true) != "" && isset($adforest_theme['allow_price_type']) && $adforest_theme['allow_price_type']) {
            $price_type = '';
            if (get_post_meta($id, '_adforest_ad_price_type', true) == 'Fixed') {
                $price_type = __('Fixed', 'adforest');
            } else if (get_post_meta($id, '_adforest_ad_price_type', true) == 'Negotiable') {
                $price_type = __('Negotiable', 'adforest');
            } else if (get_post_meta($id, '_adforest_ad_price_type', true) == 'auction') {
                $price_type = __('Auction', 'adforest');
            } else {
                $price_type = get_post_meta($id, '_adforest_ad_price_type', true);
                if (isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] != '') {
                    $price_type = str_replace('_', ' ', $price_type);
                }
            }


            $price_type_html = '<span class="' . esc_attr($class) . '">&nbsp;(' . $price_type . ')</span>';
        }
        if ($tag == 'h3') {
            return '<h3>' . $price . ' </h3>' . $price_type_html . '';
        } else {
            return $price . "<small>" . $price_type_html . "</small>";
        }
    }

}

/* Show phone number to user check */
if (!function_exists('adforest_showPhone_to_users')) {

    function adforest_showPhone_to_users()
    {
        global $adforest_theme;

        $restrict_phone_show = (isset($adforest_theme['restrict_phone_show'])) ? $adforest_theme['restrict_phone_show'] : 'all';
        $is_show_phone = false;
        if ($restrict_phone_show == "login_only") {
            $is_show_phone = true;
            if (is_user_logged_in()) {
                $is_show_phone = false;
            }
        }

        return $is_show_phone;
    }
}

/* making number callable */
if (!function_exists('adforest_get_CallAbleNumber')) {
    function adforest_get_CallAbleNumber($phone_number = '')
    {
        return preg_replace("/[^0-9+]/", "", $phone_number);
    }
}

if (!function_exists('adforest_get_all_biddings_array')) {

    function adforest_get_all_biddings_array($ad_id)
    {
        global $wpdb;
        $ad_id = (int)$ad_id;
        $bid_like = $wpdb->esc_like('_adforest_bid_') . '%';
        $biddings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND  meta_key like %s ORDER BY meta_id DESC",
                $ad_id,
                $bid_like
            ),
            OBJECT
        );
        $bid_array = array();
        if (count($biddings) > 0) {
            foreach ($biddings as $bid) {
                // date - comment - user - offer
                $data_array = explode('_separator_', $bid->meta_value);
                $bid_array[$data_array[2] . '_' . $data_array[0] . '_' . $data_array[1]] = $data_array[3];
            }
        }

        return $bid_array;
    }
}

/* Time Ago */
if (!function_exists('adforest_timeago')) {

    function adforest_timeago($date)
    {
        adforest_set_date_timezone();
        $timestamp = strtotime($date);

        $strTime = array(
            __('second', 'adforest'),
            __('minute', 'adforest'),
            __('hour', 'adforest'),
            __('day', 'adforest'),
            __('month', 'adforest'),
            __('year', 'adforest')
        );
        $length = array("60", "60", "24", "30", "12", "10");
        //$currentTime = time();
        $currentTime = current_time('mysql', 1);
        //$currentTime = date('Y-m-d H:i:s');
        $currentTime = strtotime($currentTime);
        if ($currentTime >= $timestamp) {
            $diff = $currentTime - $timestamp;
            for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
                $diff = $diff / $length[$i];
            }
            $diff = round($diff);

            return $diff . " " . $strTime[$i] . __('(s) ago', 'adforest');
        }
    }
}

if (!function_exists('adforest_comments_pagination2')) {

    function adforest_comments_pagination2($total_records, $current_page)
    {
        // Check if a records is set.
        if (!isset($total_records)) {
            return;
        }
        if (!isset($current_page)) {
            return;
        }
        $args = array(
            'base' => add_query_arg('page-number', '%#%'),
            'format' => '?page-number=%#%',
            'total' => $total_records,
            'current' => $current_page,
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'prev_next' => true,
            'prev_text' => '<i class="fa fa-chevron-left" aria-hidden="true"></i>',
            'next_text' => '<i class="fa fa-chevron-right" aria-hidden="true"></i>',
            'type' => 'array'
        );
        $pagination = paginate_links($args);
        $pagination_html = '';
        if (count((array)$pagination) > 0) {
            $pagination_html = '<ul class="pagination pagination-lg">';
            foreach ($pagination as $key => $page_link) {
                $link = $page_link;
                $class = '';
                if (strpos($page_link, 'current') !== false) {
                    $link = '<a href="javascript:void(0);">' . $current_page . '</a>';
                    $class = 'active';
                }
                $pagination_html .= '<li class="' . $class . '">' . $link . '</li>';
            }
            $pagination_html .= '</ul>';
        }

        return $pagination_html;
    }

}

if (!function_exists('adforest_get_ad_default_image_url')) {
    function adforest_get_ad_default_image_url($ad_img_size = '')
    {
        global $adforest_theme;
        $image_url = $adforest_theme['default_related_image']['url'] ?? '';
        if (!empty($adforest_theme['default_related_image']['id'])) {
            $image_url = wp_get_attachment_image_src($adforest_theme['default_related_image']['id'], $ad_img_size);
            $image_url = !empty($image_url[0]) ? $image_url[0] : $adforest_theme['default_related_image']['url'];
        }

        return $image_url;
    }

}

/* get post description as per need. */
if (!function_exists('adforest_words_count')) {
    function adforest_words_count($context = '', $limit = 180)
    {
        $string = '';
        $contents = strip_tags(strip_shortcodes($context));
        $contents = adforest_removeURL($contents);
        $removeSpaces = str_replace(" ", "", $contents);
        $contents = preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', html_entity_decode($contents, ENT_QUOTES));
        if (strlen($removeSpaces) > $limit) {
            return mb_substr(str_replace("&nbsp;", "", $contents), 0, $limit) . '...';
        } else {
            return str_replace("&nbsp;", "", $contents);
        }
    }
}

/* remove url from excerpt */
if (!function_exists('adforest_removeURL')) {
    function adforest_removeURL($string)
    {
        return preg_replace("/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i", '', $string);
    }
}

if (!function_exists('adforest_get_ad_cats')) {

    function adforest_get_ad_cats($id, $by = 'name', $for_country = false, $event_tax = "")
    {
        $taxonomy = 'ad_cats'; //Put your custom taxonomy term here

        if ($for_country) {
            $taxonomy = 'ad_country';
        } else {
            $taxonomy = 'ad_cats'; //Put your custom taxonomy term here
        }

        if ($event_tax != "") {
            $taxonomy = $event_tax;
        }

        $terms = wp_get_post_terms($id, $taxonomy);
        $cats = array();
        $myparentID = '';
        foreach ($terms as $term) {
            if ($term->parent == 0) {
                $myparent = $term;
                $myparentID = $myparent->term_id;
                $cats[] = array('name' => $myparent->name, 'id' => $myparent->term_id);
                break;
            }
        }
        $child_term_id = "";
        if ($myparentID != "") {
            $mychildID = '';
            // Right, the parent is set, now let's get the children
            foreach ($terms as $term) {
                if ($term->parent == $myparentID) { // this ignores the parent of the current post taxonomy
                    $child_term = $term; // this gets the children of the current post taxonomy
                    $mychildID = $child_term->term_id;
                    $cats[] = array('name' => $child_term->name, 'id' => $child_term->term_id);
                    break;
                }
            }
            if ($mychildID != "") {
                $mychildchildID = '';
                // Right, the parent is set, now let's get the children
                foreach ($terms as $term) {
                    if ($term->parent == $mychildID) { // this ignores the parent of the current post taxonomy
                        $child_term = $term; // this gets the children of the current post taxonomy
                        $mychildchildID = $child_term->term_id;
                        $cats[] = array('name' => $child_term->name, 'id' => $child_term->term_id);
                        break;
                    }
                }
                if ($mychildchildID != "") {
                    $child_term_id = "";
                    // Right, the parent is set, now let's get the children
                    foreach ($terms as $term) {
                        if ($term->parent == $mychildchildID) { // this ignores the parent of the current post taxonomy
                            $child_term = $term; // this gets the children of the current post taxonomy
                            $child_term_id = $child_term->term_id;
                            $cats[] = array('name' => $child_term->name, 'id' => $child_term->term_id);
                            break;
                        }
                    }
                }
                if ($child_term_id != "") {
                    // Right, the parent is set, now let's get the children
                    foreach ($terms as $term) {
                        if ($term->parent == $child_term_id) { // this ignores the parent of the current post taxonomy
                            $child_term = $term; // this gets the children of the current post taxonomy
                            $cats[] = array('name' => $child_term->name, 'id' => $child_term->term_id);
                            break;
                        }
                    }
                }
            }
        }

        return $cats;
    }
}

if (!function_exists('truncate_string')) {
    function truncate_string($string, $limit = 20): string
    {
        $string = trim($string);

        if (empty($string)) {
            return '';
        }

        $decoded = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

        if (mb_strlen($decoded, 'UTF-8') <= $limit) {
            return $string;
        }

        if (is_rtl()) {
            $truncated_decoded = mb_substr($decoded, 0, $limit, 'UTF-8');

            if (mb_substr($decoded, $limit, 1, 'UTF-8') !== ' ') {
                $lastSpace = mb_strrpos($truncated_decoded, ' ', 0, 'UTF-8');
                if ($lastSpace !== false && $lastSpace > 0) {
                    $truncated_decoded = mb_substr($truncated_decoded, 0, $lastSpace, 'UTF-8');
                }
            }

            $encoded_length = 0;
            $decoded_length = 0;
            $target_length = mb_strlen($truncated_decoded, 'UTF-8');

            while ($decoded_length < $target_length && $encoded_length < strlen($string)) {
                $char = mb_substr($string, $encoded_length, 1, 'UTF-8');
                $decoded_char = html_entity_decode($char, ENT_QUOTES, 'UTF-8');

                if (strlen($char) > strlen($decoded_char)) {
                    $entity_end = strpos($string, ';', $encoded_length);
                    if ($entity_end !== false) {
                        $encoded_length = $entity_end + 1;
                    } else {
                        $encoded_length += strlen($char);
                    }
                } else {
                    $encoded_length += strlen($char);
                }

                $decoded_length++;
            }

            return mb_substr($string, 0, $encoded_length, 'UTF-8') . '...';
        }

        $truncated_decoded = mb_substr($decoded, 0, $limit, 'UTF-8');

        $encoded_length = 0;
        $decoded_pos = 0;
        $target_length = mb_strlen($truncated_decoded, 'UTF-8');

        while ($decoded_pos < $target_length && $encoded_length < strlen($string)) {
            $remaining = substr($string, $encoded_length);

            if ($remaining[0] === '&') {
                $entity_end = strpos($remaining, ';');
                if ($entity_end !== false) {
                    $encoded_length += $entity_end + 1;
                } else {
                    $encoded_length++;
                }
            } else {
                $char = mb_substr($string, $encoded_length, 1, 'UTF-8');
                $encoded_length += strlen($char);
            }

            $decoded_pos++;
        }

        return mb_substr($string, 0, $encoded_length, 'UTF-8') . '...';
    }
}

// Last login time
if (!function_exists('adforest_get_last_login')) {
    function adforest_get_last_login($uid)
    {
        $from = get_user_meta($uid, '_sb_last_login', true);
        if ($from == "") {
            update_user_meta($uid, '_sb_last_login', current_time('timestamp'));
            $from = get_user_meta($uid, '_sb_last_login', true);
        }

        return adforest_human_time_diff($from, current_time('timestamp'));
    }
}

/* ------------------------------------------------ */
/* Pagination */
/* ------------------------------------------------ */
if (!function_exists('adforest_pagination')) {

    function adforest_pagination($w_query = array())
    {
        if (is_singular()) {
            return;
        }

        global $wp_query;
        if (isset($w_query) && !empty($w_query)) {
            $wp_query = $w_query;
        }
        /** Stop execution if there's only 1 page */
        if ($wp_query->max_num_pages <= 1) {
            return;
        }

        $paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;
        $max = intval($wp_query->max_num_pages);

        /**     Add current page to the array */
        if ($paged >= 1) {
            $links[] = $paged;
        }

        /**     Add the pages around the current page to the array */
        if ($paged >= 3) {
            $links[] = $paged - 1;
            $links[] = $paged - 2;
        }

        if (($paged + 2) <= $max) {
            $links[] = $paged + 2;
            $links[] = $paged + 1;
        }

        echo '<ul class="pagination pagination-large">' . "\n";

        if (get_previous_posts_link()) {
            echo '<li><a class="page-link" href="' . previous_posts(false) . '" aria-label="Previous"><i class="fa fa-angle-left"></i></a></li>';
        }


        /**     Link to first page, plus ellipses if necessary */
        if (!in_array(1, $links)) {
            $class = 1 == $paged ? ' class="active"' : '';

            printf('<li%s><a href="%s" class="page-link">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link(1)), '1');

            if (!in_array(2, $links)) {
                echo '<li><a href="javascript:void(0);" class="page-link">...</a></li>';
            }
        }

        /**     Link to current page, plus 2 pages in either direction if necessary */
        sort($links);
        foreach ((array)$links as $link) {
            $class = $paged == $link ? ' class="active"' : '';
            printf('<li%s><a href="%s" class="page-link">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link($link)), $link);
        }

        /**     Link to last page, plus ellipses if necessary */
        if (!in_array($max, $links)) {
            if (!in_array($max - 1, $links)) {
                echo '<li><a href="javascript:void(0);" class="page-link">...</a></li>' . "\n";
            }
            $class = $paged == $max ? ' class="active"' : '';
            printf('<li%s><a href="%s" class="page-link">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link($max)), $max);
        }

        if (get_next_posts_link()) {
            echo '<li><a class="page-link" href= "' . next_posts($max, false) . '" aria-label="next"><i class="fa fa-angle-right"></i></a></li>';
        }
        echo '</ul>' . "\n";
    }

}

/**
 * Check whether the ad-post "Address Required" theme option is enabled.
 *
 * Uses the existing Redux option id `sb_default_ad_addres_required` (note the
 * intentional typo "addres" — that's the established key in options-init.php
 * and demo JSON configs; do NOT rename it).
 *
 * Defaults to TRUE when the option is unset, preserving legacy behavior.
 *
 * @return bool True if admin wants the address field required; false otherwise.
 */
if (!function_exists('adforest_is_address_required')) {
    function adforest_is_address_required() {
        global $adforest_theme;

        // If the field itself is disabled via sb_allow_address, required is moot
        if (isset($adforest_theme['sb_allow_address']) && !$adforest_theme['sb_allow_address']) {
            return false;
        }

        // Default to true if option is unset (legacy default)
        if (!isset($adforest_theme['sb_default_ad_addres_required'])) {
            return true;
        }

        $val = $adforest_theme['sb_default_ad_addres_required'];
        return ($val === true || $val === 1 || $val === '1');
    }
}

/**
 * Check whether the current (or specified) ad is in the current user's favourites.
 *
 * Storage: AdForest stores each favourite as an individual user_meta row with
 * key "_sb_fav_id_<ad_id>" and value "<ad_id>" (confirmed via the toggle AJAX
 * handler in authentication.php and the remove handler in dashboard/functions.php).
 * This helper reads that exact pattern — do not change storage unilaterally.
 *
 * @param int $ad_id Optional. Defaults to the current post ID.
 * @return bool True if the ad is in the user's favourites.
 */
if (!function_exists('adforest_is_ad_favourited')) {
    function adforest_is_ad_favourited($ad_id = 0) {
        if (!$ad_id) {
            $ad_id = get_the_ID();
        }
        if (!$ad_id || !is_user_logged_in()) {
            return false;
        }

        $saved = get_user_meta(get_current_user_id(), '_sb_fav_id_' . intval($ad_id), true);
        return ($saved == $ad_id);
    }
}

/**
 * Get the state-aware tooltip text for a favourite heart button.
 *
 * Centralizes the "Click to make/remove favourite" string so every template,
 * Elementor widget, and AJAX re-render uses the same source of truth. Both
 * strings are translatable; the return value can be further filtered via
 * `adforest_fav_tooltip_text`.
 *
 * @param int $ad_id Optional. Defaults to the current post ID.
 * @return string Translated tooltip text.
 */
if (!function_exists('adforest_get_fav_tooltip')) {
    function adforest_get_fav_tooltip($ad_id = 0) {
        $is_fav = adforest_is_ad_favourited($ad_id);
        $text   = $is_fav
            ? __('Click to remove from favourite', 'adforest')
            : __('Click to make it favourite', 'adforest');

        return apply_filters('adforest_fav_tooltip_text', $text, $ad_id, $is_fav);
    }
}

if (!function_exists('adforest_human_time_diff')) {
    function adforest_human_time_diff($from, $to = '')
    {
        adforest_set_date_timezone();
        if (empty($to)) {
            $to = current_time('timestamp');
        }

        $diff = (int)abs($to - $from);

        if ($diff < HOUR_IN_SECONDS) {
            $mins = round($diff / MINUTE_IN_SECONDS);
            if ($mins <= 1) {
                $mins = 1;
            }

            $since = sprintf(_n('%s min', '%s mins', $mins, 'adforest'), $mins);
        } elseif ($diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS) {
            $hours = round($diff / HOUR_IN_SECONDS);
            if ($hours <= 1) {
                $hours = 1;
            }

            $since = sprintf(_n('%s hour', '%s hours', $hours, 'adforest'), $hours);
        } elseif ($diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS) {
            $days = round($diff / DAY_IN_SECONDS);
            if ($days <= 1) {
                $days = 1;
            }

            $since = sprintf(_n('%s day', '%s days', $days, 'adforest'), $days);
        } elseif ($diff < MONTH_IN_SECONDS && $diff >= WEEK_IN_SECONDS) {
            $weeks = round($diff / WEEK_IN_SECONDS);
            if ($weeks <= 1) {
                $weeks = 1;
            }

            $since = sprintf(_n('%s week', '%s weeks', $weeks, 'adforest'), $weeks);
        } elseif ($diff < YEAR_IN_SECONDS && $diff >= MONTH_IN_SECONDS) {
            $months = round($diff / MONTH_IN_SECONDS);
            if ($months <= 1) {
                $months = 1;
            }

            $since = sprintf(_n('%s month', '%s months', $months, 'adforest'), $months);
        } elseif ($diff >= YEAR_IN_SECONDS) {
            $years = round($diff / YEAR_IN_SECONDS);
            if ($years <= 1) {
                $years = 1;
            }

            $since = sprintf(_n('%s year', '%s years', $years, 'adforest'), $years);
        }

        return apply_filters('human_time_diff', $since, $diff, $from, $to);
    }
}

if (!function_exists('adforest_get_sold_ads')) {
    function adforest_get_sold_ads($user_id)
    {
        global $wpdb;
        $user_id = (int)$user_id;
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total FROM $wpdb->posts WHERE post_type = %s AND post_author = %d AND post_status = %s",
                'ad_post',
                $user_id,
                'publich'
            )
        );

        $args = array(
            'post_type' => 'ad_post',
            'author__in' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'draft',
            'meta_key' => '_adforest_ad_status_',
            'meta_value' => 'sold',
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $args = apply_filters('adforest_site_location_ads', $args, 'ads');
        $query = new WP_Query($args);

        return $query->post_count;
        wp_reset_postdata();
    }
}

if (!function_exists('adforest_get_all_ads')) {
    function adforest_get_all_ads($user_id)
    {
        global $wpdb;
        $user_id = (int)$user_id;
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total FROM  $wpdb->posts WHERE post_type = %s AND post_status = %s AND post_author = %d",
                'ad_post',
                'publish',
                $user_id
            )
        );
        $total = apply_filters('adforest_get_lang_posts_by_author', $total, $user_id);

        return $total;
    }
}

if (!function_exists('adforest_getLatLong')) {
    function adforest_getLatLong($address = '')
    {
        global $adforest_theme;

        $gmap_api_key = isset($adforest_theme['gmap_api_key']) && !empty($adforest_theme['gmap_api_key']) ? $adforest_theme['gmap_api_key'] : '';
        $google_map_key_type = isset($adforest_theme['g-map-key-type']) && !empty($adforest_theme['g-map-key-type']) ? $adforest_theme['g-map-key-type'] : 'g_key_open';
        $gmap_restricted_api_key = isset($adforest_theme['gmap_restricted_api_key']) && !empty($adforest_theme['gmap_restricted_api_key']) ? $adforest_theme['gmap_restricted_api_key'] : '';

        if (isset($gmap_restricted_api_key) && !empty($gmap_restricted_api_key) && $google_map_key_type == 'g_key_restricted') {
            $gmap_api_key = $gmap_restricted_api_key;
        }
        if ($address) {
            $formattedAddr = str_replace(' ', '+', $address);
            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );
            //Send request and receive json data by address
            $geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?key=' . $gmap_api_key . '&address=' . $formattedAddr . '&language=' . get_bloginfo('language') . '&sensor=false', false, stream_context_create($arrContextOptions));
            $output = json_decode($geocodeFromAddr);
            //Get latitude and longitute from json data
            if (isset($output->results[0]->geometry->location->lat) && isset($output->results[0]->geometry->location->lng)) {
                $data['latitude'] = $output->results[0]->geometry->location->lat;
                $data['longitude'] = $output->results[0]->geometry->location->lng;
            } else {
                return array();
            }
            //Return latitude and longitude of the given address
            if (!empty($data)) {
                return $data;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (!function_exists('adforest_validate_date_format')) {
    function adforest_validate_date_format($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
}

if (!function_exists('adforest_determine_min_max_lat_long')) {
    function adforest_determine_min_max_lat_long($data_arr = array(), $check_db = true)
    {

        global $adforest_theme;

        /* $data_array = array("latitude" => '21212121212', "longitude" => '212121212121', "distance" => '100' ); */
        $data = array();
        $user_id = get_current_user_id();
        $success = false;
        $search_radius_type = isset($adforest_theme['search_radius_type']) ? $adforest_theme['search_radius_type'] : 'km';

        if (isset($data_arr) && !empty($data_arr)) {
            $nearby_data = $data_arr;
        } else if ($user_id && $check_db) {
            $nearby_data = get_user_meta($user_id, '_sb_user_nearby_data', true);
        }

        if (isset($nearby_data) && $nearby_data != "") {
            //array("latitude" => $latitude, "longitude" => $longitude, "distance" => $distance );
            $original_lat = $nearby_data['latitude'];
            $original_long = $nearby_data['longitude'];
            $distance = intval($nearby_data['distance']);

            if ($search_radius_type == 'mile' && $distance > 0) {
                $distance = $distance * 1.609344;  // convert kilometer to miles
            }

            $lat = $original_lat; //latitude
            $lon = $original_long; //longitude
            $distance = ($distance); //your distance in KM
            $R = 6371; //constant earth radius. You can add precision here if you wish

            $maxLat = $lat + rad2deg($distance / $R);
            $minLat = $lat - rad2deg($distance / $R);

            $maxLon = $lon + rad2deg(asin($distance / $R) / @abs(@cos(deg2rad($lat))));
            $minLon = $lon - rad2deg(asin($distance / $R) / @abs(@cos(deg2rad($lat))));

            $data['radius'] = $R;
            $data['distance'] = $distance;
            $data['lat']['original'] = $original_lat;
            $data['long']['original'] = $original_long;

            $data['lat']['min'] = $minLat;
            $data['lat']['max'] = $maxLat;

            $data['long']['min'] = $minLon;
            $data['long']['max'] = $maxLon;
        }

        return $data;
    }
}

if (!function_exists('adforest_search_params')) {
    function adforest_search_params($index, $second = '', $third = '', $search_url = false)
    {
        global $adforest_theme;
        $param = $_SERVER['QUERY_STRING'];
        $res = '';
        //if (isset($param) && $index != 'cat_id' && $index != 'country_id') {
        if (isset($param)) {
            parse_str($_SERVER['QUERY_STRING'], $vars);
            foreach ($vars as $key => $val) {
                if ($key == $index) {
                    continue;
                }

                if ($second != "") {
                    if ($key == $second) {
                        continue;
                    }
                }
                if ($third != "") {
                    if ($key == $third) {
                        continue;
                    }
                }

                if ($key == 'ad_cat_sub' || $key == 'ad_cat_sub_sub' || $key == 'ad_cat_sub_sub_sub' || $key == 'ad_cat_sub_sub_sub_sub') {
                    continue;
                }

                if (isset($vars['custom']) && count($vars['custom']) > 0 && 'custom' == $key) {


                    if (is_array($val)) {
                        if (isset($val) && count($val) > 0) {
                            foreach ($val as $ckey => $cval) {
                                $name = "custom[$ckey]";
                                if ($name == $index) {
                                    continue;
                                }
                                if (isset($cval) && is_array($cval)) {
                                    foreach ($cval as $v) {
                                        $res .= '<input type="hidden" name="' . esc_attr($name) . '[]" value="' . esc_attr($v) . '" />';
                                    }
                                } else {
                                    $res .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($cval) . '" />';
                                }
                            }
                        }
                    } else {

                        foreach ($vars['custom'] as $ckey => $cval) {
                            $name = "custom[$ckey]";
                            if ($name == $index) {
                                continue;
                            }
                            $res .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($cval) . '" />';
                        }
                    }
                } else if (isset($vars['min_custom']) && count((array)$vars['min_custom']) > 0 && 'min_custom' == $key) {
                    foreach ($vars['min_custom'] as $ckey => $cval) {
                        $name = "min_custom[$ckey]";
                        if ($name == "min_" . $index) {
                            continue;
                        }
                        if ($name == $index) {
                            continue;
                        }
                        $res .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($cval) . '" />';
                    }
                } else if (isset($vars['max_custom']) && count((array)$vars['max_custom']) > 0 && 'max_custom' == $key) {
                    foreach ($vars['max_custom'] as $ckey => $cval) {
                        $name = "max_custom[$ckey]";
                        if ($name == "max_" . $index) {
                            continue;
                        }
                        if ($name == $second) {
                            continue;
                        }
                        $res .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($cval) . '" />';
                    }
                } else {
                    $res .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" />';
                }
            }
        } else if ($search_url) {
            $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
            $res = get_the_permalink($sb_search_page);
        }

        return $res;
    }
}

/* Get parents of custom taxonomy */
if (!function_exists('adforest_get_taxonomy_parents')) {
    function adforest_get_taxonomy_parents($id, $taxonomy, $link = true, $separator = ' &raquo; ', $nicename = false, $visited = array())
    {
        $chain = '';
        $parent = get_term($id, $taxonomy);
        if (is_wp_error($parent)) {
            echo "fail";

            return $parent;
        }

        if (!isset($parent) || empty($parent)) {
            return;
        }

        if ($nicename) {
            $name = $parent->slug;
        } else {
            $name = $parent->name;
        }

        if ($parent->parent && ($parent->parent != $parent->term_id) && !in_array($parent->parent, $visited)) {
            $visited[] = $parent->parent;
            $chain .= adforest_get_taxonomy_parents($parent->parent, $taxonomy, $link, $separator, $nicename, $visited);
        }

        if ($link) {
            $chain .= '<a href="' . esc_url(get_term_link((int)$parent->term_id, $taxonomy)) . '" title="' . esc_attr(sprintf(__("View all posts in %s", 'adforest'), $parent->name)) . '">' . $name . '</a>' . $separator;
        } else {
            $chain .= $separator . $name;
        }

        return $chain;
    }
}

/* Redirect */
if (!function_exists('adforest_redirect_with_msg')) {
    function adforest_redirect_with_msg($url, $msg = '', $message_type = 'error')
    {
        if ($message_type == 'success') {
            echo '<script type="text/javascript" src="' . trailingslashit(esc_url(get_template_directory_uri())) . 'assets/js/toastr.min.js"></script><script type="text/javascript"> toastr.success("' . $msg . '", "", {timeOut: 2500,"closeButton": true, "positionClass": "toast-top-right"}); window.location =   "' . $url . '";</script>';
        } else {
            echo '<script type="text/javascript" src="' . trailingslashit(esc_url(get_template_directory_uri())) . 'assets/js/toastr.min.js"></script><script type="text/javascript">toastr.error("' . $msg . '", "", {timeOut: 2500,"closeButton": true, "positionClass": "toast-top-right"});window.location =   "' . $url . '";</script>';
        }
        exit;
    }
}

// get the depth level of any taxonomy //
if (!function_exists('adforest_get_taxonomy_depth')) {
    function adforest_get_taxonomy_depth($term_id = 0, $taxonomy = 'ad_cats'): int
    {
        if ($term_id != 0) {
            $ancestors = get_ancestors($term_id, $taxonomy);

            return count($ancestors) + 1;
        }

        return 0;
    }
}

// Search Layout //
if (!function_exists('adforest_search_layout')) {
    function adforest_search_layout()
    {
        global $adforest_theme, $template;
        $widget_layout = 'sidebar';
        if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'topbar') {
            $widget_layout = 'topbar';
        } else if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'map') {
            $widget_layout = 'map';
        }

        $page_template = basename($template);
        if ($page_template == 'taxonomy-ad_cats.php' || $page_template == 'taxonomy-ad_country.php') {
            $widget_layout = 'sidebar';
        }


        return $widget_layout;
    }
}

if (!function_exists('adforest_load_search_countries')) {
    function adforest_load_search_countries($action_on_complete = '')
    {
        global $adforest_theme;
        if($adforest_theme['map-setings-map-type'] == 'google_map') {
            $stricts = '';
            if (isset($adforest_theme['sb_location_allowed']) && !$adforest_theme['sb_location_allowed'] && isset($adforest_theme['sb_list_allowed_country'])) {
                $stricts = "componentRestrictions: {country: " . json_encode($adforest_theme['sb_list_allowed_country']) . "}";
            }
            $types = "'(cities)'";
            if (isset($adforest_theme['sb_location_type']) && $adforest_theme['sb_location_type'] != "") {
                if ($adforest_theme['sb_location_type'] == 'regions') {
                    $types = "";
                } else {
                    $types = "'(cities)'";
                }
            }
            echo "<script>document.addEventListener('DOMContentLoaded', (event) => {
                    // Phase A compatibility shim — feature-detect the legacy
                    // google.maps.places.Autocomplete constructor before use.
                    // New Google Cloud projects (post-March 1, 2025) no longer
                    // expose this constructor; google.maps.places is still
                    // truthy but .Autocomplete is undefined. Without this
                    // guard the page throws:
                    //   Cannot read properties of undefined (reading 'Autocomplete')
                    function _adfGPlacesAvailable() {
                        return !!(window.google
                            && google.maps
                            && google.maps.places
                            && typeof google.maps.places.Autocomplete === 'function');
                    }
                    function _adfGPlacesWarnOnce() {
                        if (window.__adfGPlacesWarned) { return; }
                        window.__adfGPlacesWarned = true;
                        if (window.console && console.warn) {
                            console.warn('AdForest: Google Places Autocomplete unavailable. Falling back to manual address input.');
                        }
                    }
                    function adforest_location() {
                        var input = document.getElementById('sb_user_address');
                        if (!input) { return; }
                        if (!_adfGPlacesAvailable()) { _adfGPlacesWarnOnce(); return; }
                        var options = {types: [$types], $stricts};
                        var action_on_complete = '$action_on_complete';
                        var autocomplete = new google.maps.places.Autocomplete(input, options);

                        if (action_on_complete) {
                            new google.maps.event.addListener(autocomplete, 'place_changed', function() {
                                var place = autocomplete.getPlace();
                                document.getElementById('ad_map_lat').value = place.geometry.location.lat();
                                document.getElementById('ad_map_long').value = place.geometry.location.lng();
                                var markers = [{
                                    'title': '',
                                    'lat': place.geometry.location.lat(),
                                    'lng': place.geometry.location.lng(),
                                }];
                                my_g_map(markers);
                            });
                        }
                    }
                    // Expose for dashboard-custom.js which calls adforest_location()
                    // on #sb_user_address focus. Without this it lives only in
                    // the DOMContentLoaded arrow-function scope.
                    window.adforest_location = adforest_location;
                    window._adfGPlacesAvailable = _adfGPlacesAvailable;
                    window._adfGPlacesWarnOnce = _adfGPlacesWarnOnce;
                    // Google Maps JS is enqueued with loading=async so it may
                    // not be ready on DOMContentLoaded — poll briefly until
                    // the Autocomplete CONSTRUCTOR is available, then init.
                    // Bails out after ~15s (150 × 100ms) so a missing key,
                    // blocked CDN, or a new-API-key project without the
                    // legacy constructor fails quietly with a single warn.
                    var _adfGmapTries = 0;
                    (function adfGmapWait() {
                        if (_adfGPlacesAvailable()) {
                            adforest_location();
                            return;
                        }
                        if (++_adfGmapTries > 150) {
                            _adfGPlacesWarnOnce();
                            return;
                        }
                        setTimeout(adfGmapWait, 100);
                    })();
                    });
                </script>";
        }
    }
}

if (!function_exists('adforest_determine_minMax_latLong')) {
    function adforest_determine_minMax_latLong($data_arr = array(), $check_db = true)
    {
        global $adforest_theme;
        /* $data_array = array("latitude" => '21212121212', "longitude" => '212121212121', "distance" => '100' ); */
        $data = array();
        $user_id = get_current_user_id();
        $success = false;
        $search_radius_type = isset($adforest_theme['search_radius_type']) ? $adforest_theme['search_radius_type'] : 'km';

        if (isset($data_arr) && !empty($data_arr)) {
            $nearby_data = $data_arr;
        } else if ($user_id && $check_db) {
            $nearby_data = get_user_meta($user_id, '_sb_user_nearby_data', true);
        }

        if (isset($nearby_data) && $nearby_data != "") {
            //array("latitude" => $latitude, "longitude" => $longitude, "distance" => $distance );
            $original_lat = $nearby_data['latitude'];
            $original_long = $nearby_data['longitude'];
            $distance = intval($nearby_data['distance']);

            if ($search_radius_type == 'mile' && $distance > 0) {
                $distance = $distance * 1.609344;  // convert kilometer to miles
            }

            $lat = $original_lat; //latitude
            $lon = $original_long; //longitude
            $distance = ($distance); //your distance in KM
            $R = 6371; //constant earth radius. You can add precision here if you wish

            $maxLat = $lat + rad2deg($distance / $R);
            $minLat = $lat - rad2deg($distance / $R);

            $maxLon = $lon + rad2deg(asin($distance / $R) / @abs(@cos(deg2rad($lat))));
            $minLon = $lon - rad2deg(asin($distance / $R) / @abs(@cos(deg2rad($lat))));

            $data['radius'] = $R;
            $data['distance'] = $distance;
            $data['lat']['original'] = $original_lat;
            $data['long']['original'] = $original_long;

            $data['lat']['min'] = $minLat;
            $data['lat']['max'] = $maxLat;

            $data['long']['min'] = $minLon;
            $data['long']['max'] = $maxLon;
        }

        return $data;
    }
}

// Load Ad Packages //
add_action('wp_ajax_load_feature_ad_modal', 'load_feature_ad_modal');
add_action('wp_ajax_nopriv_load_feature_ad_modal', 'load_feature_ad_modal');
if (!function_exists('load_feature_ad_modal')) {
    function load_feature_ad_modal()
    {
        check_ajax_referer('adforest_load_feature_ad_modal_nonce', 'security');
        global $adforest_theme;
        $adId = isset($_POST['adID']) ? $_POST['adID'] : "";
        $formId = isset($_POST['formId']) ? $_POST['formId'] : "";
        $selected_categories = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
        $default_package = prepare_default_packages();

        if (is_array($selected_categories) && is_array($default_package)) {
            $selected_categories = $default_package + $selected_categories;
        } else {
            $selected_categories = $default_package;
        }

        $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
        $link_package_page = get_the_permalink($sb_packages_page);
        if (empty($selected_categories) || !is_array($selected_categories)) {
            $no_pkg_html = '<div class="alert alert-warning mt-3 mb-3">' .
                esc_html__('No package found. Please ', 'adforest') .
                '<a href="' . esc_url($link_package_page) . '" class="btn dark-btn btn-block">' .
                esc_html__('purchase a package', 'adforest') .
                '</a>' .
                '</div>';

            echo wp_kses($no_pkg_html, ADFOREST_ALLOWED_FORM_HTML);
            wp_die();
        }
        if (is_array($selected_categories)) {
            $Ads_packages = '<form id="' . $formId . '" class="d-flex flex-column" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';

            foreach ($selected_categories as $product_id => $packageDetails) {
                $Pkg_radio = '<input class="form-check-input" type="radio" name="ads_package" id="ads_package_' . $product_id . '" value="' . $product_id . '" required>';
                $product = wc_get_product($product_id);
                if ($product) {
                    $product_title = $product->get_title();
                }
                $product_title = $product_id == 0 ? __("Default", "adforest") : $product_title;
                $Ads_packages .= '<div class="featured-duration" id="featured-duration">
                                        <ul class="featured-duration-list" id="cate_package">
                                            <li>
                                                <label for="ads_package_' . $product_id . '">
                                                    <div class="type-box">
                                                    <input type="hidden" value="' . $adId . '" name="ad_id" />
                                                        <div class="r-meta">
                                                            ' . $Pkg_radio . '
                                                            <span>' . $product_title . '</span>
                                                        </div>
                                                    </div>
                                                </label>
                                            </li>';

                foreach ($packageDetails as $detailName => $detailValue) {
                    if (in_array($detailName, [
                        'free_ads',
                        'featured_ads',
                        'pkg_expiry_days',
                        'ad_expiry_days',
                        'featured_expiry_days',
                        'bump_ads'
                    ])) {
                        $custom_titles = [
                            'free_ads' => 'Free Ads',
                            'featured_ads' => 'Featured Ads',
                            'pkg_expiry_days' => 'Package Expiry Days',
                            'ad_expiry_days' => 'Simple Expiry Days',
                            'featured_expiry_days' => 'Featured Expiry Days',
                            'bump_ads' => 'Bump Up Ads'
                        ];

                        $title = isset($custom_titles[$detailName]) ? $custom_titles[$detailName] : $detailName;
                        if ($detailName === 'pkg_expiry_days' && strtotime($detailValue)) {
                            $pretty_value = date_i18n(get_option('date_format'), strtotime($detailValue));
                        } else {
                            $pretty_value = ($detailValue == "-1") ? __("Unlimited", "adforest") : $detailValue;
                        }
                        $Ads_packages .= '<li>' . $title . ': ' . $pretty_value . '</li>';
                    }
                }
                $Ads_packages .= '</ul></div>';
            }
            $Ads_packages .= '<button type="submit" class="btn btn-primary m-4 feature_modal_btn">' . esc_html__("Submit", "adforest") . '</button>';
            $Ads_packages .= '</form>';

            echo wp_kses($Ads_packages, ADFOREST_ALLOWED_FORM_HTML);
        }
        wp_die();
    }
}

// Load Ad Packages //

if (!function_exists('adforest_get_feature_text')) {
    function adforest_get_feature_text($pid)
    {
        ?>
        <div role="alert" class="alert alert-info alert-dismissible">
            <i class="fa fa-info-circle"></i>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            <?php echo __('Mark as featured Ad,', 'adforest'); ?>
            <a href="javascript:void(0);" class="sb_anchor" data-btn-ok-label="<?php echo __('Yes', 'adforest'); ?>"
               data-btn-cancel-label="<?php echo __('No', 'adforest'); ?>" data-bs-toggle="confirmation"
               data-singleton="true"
               data-title="<?php echo __('Are you sure?', 'adforest'); ?>" data-content="" id="sb_feature_ad"
               aaa_id="<?php echo esc_attr($pid); ?>">
                <?php echo __('Click Here.', 'adforest'); ?>
            </a>
        </div>
        <?php
    }
}

if (!function_exists('adforest_get_feature_text_new_pkg')) {
    function adforest_get_feature_text_new_pkg($pid)
    {
        ?>
        <div role="alert" class="alert alert-info alert-dismissible">
            <i class="fa fa-info-circle"></i>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            <?php echo __('Mark as featured Ad,', 'adforest'); ?>
            <a class="mb-1  mr-2 sb_anchor sb_make_feature_ad_detail_page" href="javascript:void(0);"
               data-aaa-id="<?php echo esc_attr($pid); ?>"><?php echo __('Click Here.', 'adforest'); ?></a>
        </div>
        <?php
    }
}

if (!function_exists('adforest_cat_link_page')) {
    function adforest_cat_link_page($category_id, $type = '', $tax = 'cat_id')
    {
        global $adforest_theme;
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $link = adforest_set_url_param(get_the_permalink($sb_search_page), $tax, $category_id);
        if ($type == 'category') {
            $link = get_term_link((int)$category_id);
        }

        return $link;
    }
}

if (!function_exists('adforest_get_grid_layout')) {
    function adforest_get_grid_layout()
    {
        global $adforest_theme;
        $search_ad_layout_for_sidebar = '';
        if (isset($adforest_theme['search_layout_types']) && $adforest_theme['search_layout_types'] == true) {
            if (isset($_GET['view-type']) && $_GET['view-type'] != "") {
                if ($_GET['view-type'] == 'grid') {
                    $search_ad_layout_for_sidebar = isset($adforest_theme['search_layout_types_grid']) ?
                        $adforest_theme['search_layout_types_grid'] : "grid_1";
                }
                if ($_GET['view-type'] == 'list') {
                    $search_ad_layout_for_sidebar = isset($adforest_theme['search_layout_types_list']) ? $adforest_theme['search_layout_types_list'] : 'list_1';
                }
            }
        }

        return $search_ad_layout_for_sidebar;
    }
}

if (!function_exists('get_total_active_ad_posts')) {
    function get_total_active_ad_posts()
    {
        $args = array(
            'post_type' => 'ad_post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        $query = new WP_Query($args);

        return $query->found_posts;
    }
}

if (!function_exists('adforest_widget_counter')) {

    function adforest_widget_counter($return = false)
    {
        global $adforest_theme;
        @$GLOBALS['widget_counter'] += 1;
        if ($GLOBALS['widget_counter'] == $adforest_theme['search_widget_limit']) {
            if ($return) {
                return '<a href="javascript:void(0);" class="adv-srch">' . esc_html__('Advance Search', 'adforest') . '</a>';
            } else {
                echo '<a href="javascript:void(0);" class="adv-srch">' . esc_html__('Advance Search', 'adforest') . '</a>';
            }
        }
    }
}

if (!function_exists('adforest_advance_search_container')) {

    function adforest_advance_search_container($return = false)
    {
        global $adforest_theme;
        if ($GLOBALS['widget_counter'] == $adforest_theme['search_widget_limit']) {
            if ($return) {
                return '</div><div class="hide_adv_search"><div class="row">';
            } else {
                echo '</div><div class="hide_adv_search"><div class="row">';
            }
        }
    }

}

if (!function_exists(('adforest_render_ads_in_search'))) {
    function adforest_render_ads_in_search($query, $style_for_infinity_scroll, $loading_ads_mode, $paged, $args, $ad_count)
    {
        global $adforest_theme;
        $view_type = (isset($_GET['view-type']) && $_GET['view-type'] !== '') ? $_GET['view-type'] : (isset($adforest_theme['search_ad_layout_for_search']) ? $adforest_theme['search_ad_layout_for_search'] : 'grid');

        // Search 2.0 Part 5 — unify the legacy "List" search layout with the
        // new card system. When the user picks List view (via the on-page
        // toggle or the Search Layout option) AND the new dispatcher is
        // loaded, force the grid pipeline to render the `list_view` card
        // instead of the legacy `adforest_list_layout` templates. Old list
        // templates remain as a safe fallback when the dispatcher is
        // unavailable (e.g., during partial upgrades).
        //
        // Also unify when the admin's Grid Type is set to `list_view`. Without
        // this branch, leaving the default Search Layout on Grid while picking
        // List View as the card design would feed each list-row card into a
        // 4-column grid container — squashing wide rows into narrow portrait
        // columns from ad #10 onward (the first 9 are the featured-first list
        // rows, which use a separate full-width container).
        $adforest_grid_layout_is_list_view = ( isset( $adforest_theme['adforest_grid_layout'] ) && $adforest_theme['adforest_grid_layout'] === 'list_view' );
        $adforest_unified_list = ( ( $view_type == 'list' || $adforest_grid_layout_is_list_view ) && function_exists('adforest_load_search_card') );
        $adforest_grid_layout_snapshot = null;
        if ($adforest_unified_list) {
            $adforest_grid_layout_snapshot = isset($adforest_theme['adforest_grid_layout']) ? $adforest_theme['adforest_grid_layout'] : null;
            $adforest_theme['adforest_grid_layout'] = 'list_view';
            $view_type = 'grid';
        }

        if ($query->have_posts() && ($view_type == 'list')) { ?>
            <div class="adt-search-ads-list" <?php echo ($style_for_infinity_scroll); ?>>
                <?php
                $site_currency = isset($adforest_theme['sb_currency']) && !empty($adforest_theme['sb_currency']) ? $adforest_theme['sb_currency'] : get_woocommerce_currency_symbol();
                $search_page_list_adverts = $adforest_theme['search_page_list_adverts'];
                $ads = explode('|', $search_page_list_adverts);
                $total_ads = count($ads);
                $ad_index = 0;

                $ad_threshold = rand(3, 4);
                $listing_counter = 0;
                while ($query->have_posts()) : $query->the_post();
                    $listing_counter++;
                    $ad_details = get_ad_post_details(get_the_ID());
                    $first_img = $ad_details['img'];
                    $truncated_location = $ad_details['location'];
                    $truncated_title = $ad_details['ad_title'];
                    $price_html = $ad_details['price_html'];
                    $ad_permalink = $ad_details['ad_link'];
                    $heart_class = $ad_details['heart_class'];
                    $is_fav      = isset($ad_details['is_fav']) ? (bool) $ad_details['is_fav'] : false;
                    $fav_title   = $is_fav ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                    $fav_extra   = $is_fav ? ' ad-favourited' : '';
                    $is_featured = $ad_details['is_featured'];
                    $all_ad_images = $ad_details['all_ad_images'];
                    $ad_poster_img = $ad_details['ad_poster_img'];
                    $ad_poster_name = $ad_details['ad_poster_name'];
                    $ad_title = get_the_title();
                    $location = $ad_details['location'];
                    $content = get_the_content();
                    $clean_content = wp_strip_all_tags($content);
                    $truncated_content = truncate_string($clean_content, 120);
                    $ad_categories_post = $ad_details['categories'];
                    if (function_exists('adforest_comments_pagination2')) {
                        $page = (isset($_GET['page-number'])) ? $_GET['page-number'] : 1;
                    } else {
                        $page = (get_query_var('page')) ? get_query_var('page') : 1;
                    }

                    $limit = $adforest_theme['sb_rating_max'] ?? 0;
                    $offset = ($page * $limit) - $limit;
                    $comment_args = array(
                        'type__in' => array('ad_post_rating'),
                        'number' => $limit,
                        'offset' => $offset,
                        'parent' => 0,
                        'post_id' => get_the_ID(),
                    );

                    $comments = get_comments($comment_args);
                    $get_percentage = adforest_fetch_reviews_average(get_the_ID());

                    if (isset($adforest_theme['adforest_list_layout']) && $adforest_theme['adforest_list_layout'] == '1') {
                        ?>
                        <div class="adt-category-ad-list">
                            <div class="category-img-box">
                                <a href="<?php echo esc_url($ad_permalink, 'adforest'); ?>">
                                    <img class="img-fluid"
                                         src="<?php echo esc_url($first_img, "adforest"); ?>"
                                         alt="<?php echo esc_html(get_the_title()); ?>">

                                    <?php if ($is_featured): ?>
                                        <span class="featured-label"><?php echo __('Featured', 'adforest'); ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <?php
                            $category_links = [];

                            foreach ($ad_categories_post as $category) {
                                $category_url = get_term_link($category);
                                if (!is_wp_error($category_url)) {
                                    $category_links[] = '<a class="ctg-tag" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
                                }
                            }

                            $category_links_string = implode(' > ', $category_links);
                            ?>
                            <div class="category-content-box">
                                <a href="javascript:void(0);"
                                   class="favourite ad_to_fav<?php echo esc_attr( $fav_extra ); ?>"
                                   data-adid="<?php echo get_the_ID(); ?>"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   title="<?php echo esc_attr( $fav_title ); ?>"
                                   aria-label="<?php echo esc_attr( $fav_title ); ?>">
                                    <i class="<?php echo esc_attr($heart_class); ?>"></i>
                                </a>
                                <div class="adt-ad-cats">
                                    <?php echo wp_kses($category_links_string, ADFOREST_ALLOWED_FORM_HTML); ?>
                                </div>
                                <a href="<?php the_permalink(); ?>">
                                    <h5><?php echo esc_html($truncated_title); ?></h5></a>
                                <p>
                                    <i class="fas fa-map-marker-alt"></i><?php echo esc_html($truncated_location); ?>
                                </p>
                                <div class="price-box">
                                    <?php echo wp_kses($price_html, ADFOREST_ALLOWED_FORM_HTML); ?>
                                    <a href="<?php the_permalink(); ?>"
                                       class="detail-btn"><?php echo __("Detail", "adforest"); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php
                    } elseif (isset($adforest_theme['adforest_list_layout']) && $adforest_theme['adforest_list_layout'] == '2') {
                        ?>
                        <div class="adt-car-dealer-card">
                            <div class="adt-car-ad-carousel owl-carousel owl-theme">
                                <?php foreach ($all_ad_images as $image_url) { ?>
                                    <div class="item"><img src="<?php echo esc_url($image_url); ?>"
                                                           alt="<?php echo esc_html(get_the_title()) ?>">
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="adt-car-content-box">
                                <div class="adt-car-meta-box">
                                    <div class="rating-box">
                                        <span class="rating"><?php echo esc_html(isset($get_percentage['average']) ? $get_percentage['average'] : "0.0"); ?></span>
                                        <?php
                                        if (isset($get_percentage) && count($get_percentage['ratings']) > 0) {
                                            echo adforest_return_echo($get_percentage['total_stars']);
                                        } else {
                                            echo str_repeat('<i class="far fa-star" aria-hidden="true"></i>', 5);
                                        }
                                        ?>
                                        <span class="reviews"><?php echo is_array($comments) ? count($comments) : 0 . __(" Reviews", 'adforest'); ?></span>
                                    </div>
                                    <a href="<?php echo esc_url($ad_permalink, "adforest"); ?>">
                                        <h3><?php echo esc_html($ad_title); ?></h3></a>
                                    <ul>
                                        <li>
                                            <i class="fas fa-location-arrow"></i><?php echo esc_html($location); ?>
                                        </li>
                                        <li><i class="far fa-calendar-alt"></i><?php echo get_the_date(); ?></li>
                                        <li>
                                            <img src="<?php echo esc_url($ad_poster_img, "adforest"); ?>"
                                                 alt="author"><?php echo esc_html($ad_poster_name); ?>
                                        </li>
                                    </ul>
                                    <p><?php echo esc_html($truncated_content); ?></p>
                                </div>
                                <div class="adt-car-price-meta">
                                    <div class="price-box">
                                        <?php if (isset($formatted_price)) { ?>
                                            <span><?php echo esc_html($site_currency) . $formatted_price; ?></span>
                                        <?Php } ?>
                                        <?php if (isset($ad_price_type) && !empty($ad_price_type)) { ?>
                                            <small>(<?php echo esc_html($ad_price_type); ?>)</small>
                                        <?php } ?>
                                    </div>
                                    <div class="detail-btn-box">
                                            <span class="favorite ad_to_fav<?php echo esc_attr( $fav_extra ); ?>"
                                                  data-adid="<?php echo get_the_ID(); ?>"
                                                  data-toggle="tooltip"
                                                  data-placement="top"
                                                  title="<?php echo esc_attr( $fav_title ); ?>"
                                                  aria-label="<?php echo esc_attr( $fav_title ); ?>">
                                                <i class="<?php echo esc_attr($heart_class); ?>"></i>
                                            </span>
                                        <a href="<?php echo esc_url($ad_permalink, "adforest"); ?>"
                                           class="adt-button-dark-1"><?php echo __("Details", "adforest"); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }

                    if (isset($adforest_theme['turn_on_list_adverts_search']) && $adforest_theme['turn_on_list_adverts_search'] == '1') {
                        if ($listing_counter == $ad_threshold && $total_ads > 0) {
                            echo '<div class="margin-tb-30">';
                            if ( function_exists( 'adforest_render_ad' ) ) {
                                $list_ad_type = function_exists( 'adforest_get_ad_type' ) ? adforest_get_ad_type( 'search_page_list_adverts' ) : 'image';
                                adforest_render_ad( $list_ad_type, $ads[$ad_index] );
                            } else {
                                echo wp_kses( $ads[$ad_index], ADFOREST_ALLOWED_FORM_HTML );
                            }
                            echo '</div>';

                            $ad_index++;
                            if ($ad_index >= $total_ads) {
                                $ad_index = 0;
                            }

                            $listing_counter = 0;
                            $ad_threshold = isset($adforest_theme['show_list_ads_after_a_no_of_listings']) ? intval($adforest_theme['show_list_ads_after_a_no_of_listings']) : 0;
                        }
                    }

                endwhile; ?>
            </div>
            <?php
            if ($query->have_posts()) {
                if ($loading_ads_mode == 'show_more' || $loading_ads_mode == 'infinity_scroll') {
                    ?>
                    <div class="d-flex justify-content-center">
                        <button data-search-query='<?php echo json_encode($args); ?>'
                                data-loading-mode="<?php echo esc_attr($loading_ads_mode); ?>"
                                data-ad-count="<?php echo esc_attr($ad_count); ?>"
                                data-posts-per-page="<?php echo get_option('posts_per_page'); ?>"
                                data-view-type="<?php echo esc_attr($view_type); ?>"
                                class="adt-button-dark"
                                id="load-more-ads-btn"><?php echo esc_html('Show More', 'adforest'); ?></button>
                    </div>
                <?php }
            } ?>
            <div class="m-2" id="no_more_ads_p"></div>
            <?php if ($loading_ads_mode == 'pagination') {
                $total_pages = $query->max_num_pages;
                if ($total_pages > 1) {
                    ?>
                    <nav aria-label="pagination">
                        <ul class="pagination adt-custom-pagination">
                            <?php
                            if ($paged > 1) {
                                echo '<li class="page-item"><a class="page-link prv" href="' . esc_url(get_pagenum_link($paged - 1)) . '"><i class="fas fa-chevron-left"></i></a></li>';
                            }
                            for ($i = 1; $i <= $total_pages; $i++) {
                                $active_class = ($i == $paged) ? ' active' : '';
                                echo '<li class="page-item"><a class="page-link' . $active_class . '" href="' . esc_url(get_pagenum_link($i)) . '">' . str_pad($i, 2, '0', STR_PAD_LEFT) . '</a></li>';
                            }
                            if ($paged < $total_pages) {
                                echo '<li class="page-item"><a class="page-link nxt" href="' . esc_url(get_pagenum_link($paged + 1)) . '"><i class="fas fa-chevron-right"></i></a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                <?php }
            } ?>
            <?php
        } elseif ($query->have_posts()) {
            $grid_cols = $adforest_theme['no_of_ad_in_search_page_row'] ?? '';
            $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == false) ? "one-column-mobile-layout" : "";
            // List_view cards span full width — collapse the column count
            // so the grid container becomes single-column when unified.
            $grid_extra_class = '';
            if ($adforest_unified_list) {
                $grid_cols = '1';
                $grid_extra_class = 'adt-search-ads-list-mode';
            }

            // Section heading: when the page renders Featured Ads above the
            // main results (featured_first + feature_on_search both on) AND
            // we're in list mode, the two stacks look identical without a
            // label. Print a "Search Results" heading on page 1 only — for
            // paginated AJAX loads the heading would otherwise inject in
            // the middle of the appended results.
            $featured_first_on = ( isset( $adforest_theme['featured_first'] ) && $adforest_theme['featured_first'] == '1' );
            $featured_on_search = ! empty( $adforest_theme['feature_on_search'] );
            if ( $adforest_unified_list && $paged <= 1 && $featured_first_on && $featured_on_search ) {
                ?>
                <div class="adt-ads-top-box adt-ads-results-heading">
                    <h2><?php esc_html_e( 'Search Results', 'adforest' ); ?></h2>
                </div>
                <?php
            }
            ?>
            <div class="adt-search-ads-grid <?php echo "adt-search-ads-col-" . esc_attr($grid_cols); ?> <?php echo esc_attr($sb_2column) ?> <?php echo esc_attr($grid_extra_class); ?>" <?php echo ($style_for_infinity_scroll); ?>>
                <?php
                $search_page_adverts = (isset($adforest_theme['search_page_grid_adverts']) ? $adforest_theme['search_page_grid_adverts'] : '');
                $ads = explode('|', $search_page_adverts);
                $total_ads = count($ads);
                $ad_index = 0;
                $title_limit = 40;
                $location_limit = 40;
                if (isset($adforest_theme['sb_ad_title_limit_on']) && $adforest_theme['sb_ad_title_limit_on'] == '1') {
                    $title_limit = isset($adforest_theme['sb_ad_title_limit']) ? $adforest_theme['sb_ad_title_limit'] : 40;
                }

                if (isset($adforest_theme['sb_ad_location_limit_on']) && $adforest_theme['sb_ad_location_limit_on'] == '1') {
                    $location_limit = isset($adforest_theme['sb_ad_location_limit']) ? $adforest_theme['sb_ad_location_limit'] : 40;
                }

                $ad_threshold = rand(3, 4);
                $listing_counter = 0;
                while ($query->have_posts()) {
                    $query->the_post();
                    $listing_counter++;
                    $ad_details = get_ad_post_details(get_the_ID());
                    $first_img = $ad_details['img'];
                    $truncated_location = truncate_string($ad_details['location'], $location_limit);
                    $truncated_title = truncate_string($ad_details['ad_title'], $title_limit);
                    $price_html = $ad_details['price_html'];
                    $ad_permalink = $ad_details['ad_link'];
                    $heart_class = $ad_details['heart_class'];
                    $is_fav      = isset($ad_details['is_fav']) ? (bool) $ad_details['is_fav'] : false;
                    $fav_title   = $is_fav ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                    $fav_extra   = $is_fav ? ' ad-favourited' : '';
                    $is_featured = $ad_details['is_featured'];
                    $all_ad_images = $ad_details['all_ad_images'];
                    $ad_poster_img = $ad_details['ad_poster_img'];
                    $ad_poster_name = $ad_details['ad_poster_name'];
                    $ad_title = truncate_string($ad_details['ad_title'], $title_limit);
                    $featured_tag = $is_featured ? '<img style="transform: rotate(180deg);" src="' . esc_url(get_template_directory_uri()) . '/images/featured.png' . '" alt="featured-tag" class="featured-tag">' : '';
                    $ad_categories_post = $ad_details['categories'];
                    $top_bar_specific_style = '';
                    if ($adforest_theme['search_design'] == 'topbar') {
                        $top_bar_specific_style = 'top_bar_specific_style';
                    }
                    $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);
                    if (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'simple') {
                        echo adforest_ad_grid_1($ad_permalink, $first_img, $is_featured, $ad_categories_post, $ad_details, $truncated_title, $truncated_location, $price_html, $heart_class);
                    } elseif (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'with_labels') {
                        ?>
                        <div class="item search_with_labels_grid <?php echo esc_attr($top_bar_specific_style); ?>">
                            <?php echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class); ?>
                        </div>
                        <?php
                    } elseif (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'modern') {
                        ?>
                        <div class="item search_with_labels_grid <?php echo esc_attr($top_bar_specific_style); ?>">
                            <?php echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location); ?>
                        </div>
                        <?php
                    } elseif (
                        function_exists('adforest_load_search_card')
                        && isset($adforest_theme['adforest_grid_layout'])
                        && in_array($adforest_theme['adforest_grid_layout'], array('modern_card', 'compact_grid', 'list_view'), true)
                    ) {
                        // Search 2.0 Part 3: dispatch to one of the new
                        // template-based card layouts. Existing classic
                        // layouts above are unaffected — this branch only
                        // fires for the three opt-in slugs.
                        ?>
                        <div class="item adf-card-item adf-card-item--<?php echo esc_attr($adforest_theme['adforest_grid_layout']); ?> <?php echo esc_attr($top_bar_specific_style); ?>">
                            <?php
                            adforest_load_search_card($adforest_theme['adforest_grid_layout'], array(
                                'ad_permalink'           => $ad_permalink,
                                'first_img'              => $first_img,
                                'all_ad_images'          => $all_ad_images,
                                'is_featured'            => $is_featured,
                                'heart_class'            => $heart_class,
                                'is_fav'                 => $is_fav,
                                'fav_title'              => $fav_title,
                                'fav_extra'              => $fav_extra,
                                'truncated_title'        => $truncated_title,
                                'ad_title'               => $ad_title,
                                'truncated_location'     => $truncated_location,
                                'price_html'             => $price_html,
                                'ad_categories_post'     => $ad_categories_post,
                                'ad_poster_img'          => $ad_poster_img,
                                'ad_poster_name'         => $ad_poster_name,
                                'ad_type'                => $ad_type,
                                'ad_details'             => $ad_details,
                                'top_bar_specific_style' => $top_bar_specific_style,
                                'featured_tag'           => $featured_tag,
                            ));
                            ?>
                        </div>
                        <?php
                    }

                    if (isset($adforest_theme['turn_on_grid_adverts_search']) && $adforest_theme['turn_on_grid_adverts_search'] == '1') {
                        if ($listing_counter == $ad_threshold && $total_ads > 0) {
                            $search_page_adverts = (isset($adforest_theme['search_page_grid_adverts']) ? $adforest_theme['search_page_grid_adverts'] : '');

                            if (strpos($search_page_adverts, '<!--ADSEPARATOR-->') !== false) {
                                $ads = explode('<!--ADSEPARATOR-->', $search_page_adverts);
                            } elseif (strpos($search_page_adverts, '||SEPARATOR||') !== false) {
                                $ads = explode('||SEPARATOR||', $search_page_adverts);
                            } elseif (strpos($search_page_adverts, '###') !== false) {
                                $ads = explode('###', $search_page_adverts);
                            } else {
                                $ads = array($search_page_adverts);
                            }

                            $total_ads = count($ads);
                            $current_ad = trim($ads[$ad_index]);

                            if (!empty($current_ad)) {
                                if ( function_exists( 'adforest_render_ad' ) ) {
                                    $grid_ad_type = function_exists( 'adforest_get_ad_type' ) ? adforest_get_ad_type( 'search_page_grid_adverts' ) : 'image';
                                    adforest_render_ad( $grid_ad_type, $current_ad );
                                } else {
                                    echo wp_kses_post( $current_ad );
                                }
                            }

                            $ad_index++;
                            if ($ad_index >= $total_ads) {
                                $ad_index = 0;
                            }

                            $listing_counter = 0;
                            $ad_threshold = isset($adforest_theme['show_ads_after_a_no_of_listings']) ? intval($adforest_theme['show_ads_after_a_no_of_listings']) : 0;
                        }
                    }
                }
                ?>
            </div>
            <?php
            if ($query->have_posts()) {
                if ($loading_ads_mode == 'show_more' || $loading_ads_mode == 'infinity_scroll') {
                    ?>
                    <div class="d-flex justify-content-center">
                        <button data-search-query='<?php echo json_encode($args); ?>'
                                data-loading-mode="<?php echo esc_attr($loading_ads_mode); ?>"
                                data-ad-count="<?php echo esc_attr($ad_count); ?>"
                                data-posts-per-page="<?php echo get_option('posts_per_page'); ?>"
                                data-view-type="<?php echo esc_attr($view_type); ?>"
                                class="adt-button-dark"
                                id="load-more-ads-btn"><?php echo esc_html('Show More', 'adforest'); ?></button>
                    </div>
                <?php }
            } ?>
            <div class="m-2" id="no_more_ads_p"></div>
            <?php if ($loading_ads_mode == 'pagination') {
                $total_pages = $query->max_num_pages;
                if ($total_pages > 1) {
                    $range = 2; // Number of pages to show on each side of current page
                    $showitems = ($range * 2) + 1; // Total items to show
                    ?>
                    <nav aria-label="pagination">
                        <ul class="pagination adt-custom-pagination">
                            <?php
                            // Previous button
                            if ($paged > 1) {
                                echo '<li class="page-item"><a class="page-link prv" href="' . esc_url(get_pagenum_link($paged - 1)) . '"><i class="fas fa-chevron-left"></i></a></li>';
                            }

                            // First page
                            if ($paged > ($range + 1) && $showitems < $total_pages) {
                                echo '<li class="page-item"><a class="page-link" href="' . esc_url(get_pagenum_link(1)) . '">01</a></li>';
                            }

                            // First ellipsis
                            if ($paged > ($range + 2) && $showitems < $total_pages) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }

                            // Page numbers around current page
                            for ($i = 1; $i <= $total_pages; $i++) {
                                if (1 != $total_pages && !($i >= $paged + $range + 1 || $i <= $paged - $range - 1) || $total_pages <= $showitems) {
                                    $active_class = ($i == $paged) ? ' active' : '';
                                    echo '<li class="page-item"><a class="page-link' . $active_class . '" href="' . esc_url(get_pagenum_link($i)) . '">' . str_pad($i, 2, '0', STR_PAD_LEFT) . '</a></li>';
                                }
                            }

                            // Last ellipsis
                            if ($paged < $total_pages - $range - 1 && $showitems < $total_pages) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }

                            // Last page
                            if ($paged < $total_pages - $range && $showitems < $total_pages) {
                                echo '<li class="page-item"><a class="page-link" href="' . esc_url(get_pagenum_link($total_pages)) . '">' . str_pad($total_pages, 2, '0', STR_PAD_LEFT) . '</a></li>';
                            }

                            // Next button
                            if ($paged < $total_pages) {
                                echo '<li class="page-item"><a class="page-link nxt" href="' . esc_url(get_pagenum_link($paged + 1)) . '"><i class="fas fa-chevron-right"></i></a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                <?php }
            } ?>
        <?php } else {
            $nothing_found = esc_url(get_template_directory_uri()) . '/images/nothing-found.png';
            echo '<div class="no_ads_found">
                    <img src="' . esc_url($nothing_found) . '" alt="">
                    <h3>' . __("No Ads found.", "adforest") . '</h3>
                  </div>';
        } ?>
        <?php
        if (isset($adforest_theme['search_ad_720_2']) && $adforest_theme['search_ad_720_2'] != "" && $query->have_posts()) {
            ?>
            <div class="col-md-12">
                <div class="margin-bottom-30 margin-top-10 text-center">
                    <?php echo wp_kses_post($adforest_theme['search_ad_720_2']); ?>
                </div>
            </div>
            <?php
        }
        ?>
        <?php
        // Restore the original Grid Type so any code that runs AFTER this
        // function on the same request (e.g., load-more AJAX path) sees the
        // admin's actual selection, not our list-mode override.
        if ($adforest_unified_list) {
            if ($adforest_grid_layout_snapshot === null) {
                unset($adforest_theme['adforest_grid_layout']);
            } else {
                $adforest_theme['adforest_grid_layout'] = $adforest_grid_layout_snapshot;
            }
        }
    }
}

/* adforest search params */
if (!function_exists('adforest_custom_remove_url_query')) {
    function adforest_custom_remove_url_query($key = '', $value = '')
    {
        $url = adforest_curPageURL();
        $param = "?" . $_SERVER['QUERY_STRING'];
        $url = preg_replace('/(?:&|(\?))' . $key . '=[^&]*(?(1)&|)?/i', "$1", $param);
        $url = rtrim($url, '?');
        $url = rtrim($url, '&');
        $final_url = ($url != "") ? $url . "&$key=$value" : "?$key=$value";

        return $final_url;
    }
}

if (!function_exists('adforest_curPageURL')) {
    function adforest_curPageURL()
    {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"])) {
            if ($_SERVER["HTTPS"] == "on") {
                $pageURL .= "s";
            }
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }

        return $pageURL;
    }
}

// AJAX: fetch child categories for cascading selects
add_action('wp_ajax_adforest_get_child_categories', 'adforest_get_child_categories');
add_action('wp_ajax_nopriv_adforest_get_child_categories', 'adforest_get_child_categories');
if (!function_exists('adforest_get_child_categories')) {
    function adforest_get_child_categories()
    {
        check_ajax_referer('adforest_get_child_categories_nonce', 'security');
        $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
        $taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field($_GET['taxonomy']) : 'ad_cats';
        $response = array();
        if ($parent_id > 0) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => $parent_id,
            ));
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $response[] = array(
                        'id' => $term->term_id,
                        'text' => $term->name,
                        'has_children' => (int) $term->count > 0 || get_term_children($term->term_id, $taxonomy) ? true : false,
                    );
                }
            }
        }
        wp_send_json_success($response);
    }
}

if (!function_exists('adforest_title_limit')) {
    function adforest_title_limit($ad_title = '')
    {
        global $adforest_theme;
        if (isset($adforest_theme['sb_ad_title_limit_on']) && $adforest_theme['sb_ad_title_limit_on'] && isset($adforest_theme['sb_ad_title_limit']) && $adforest_theme['sb_ad_title_limit'] != "") {
            return adforest_words_count($ad_title, $adforest_theme['sb_ad_title_limit']);
        } else {
            return $ad_title;
        }
    }
}

/* Get States Search */
add_action('wp_ajax_get_related_cities', 'adforest_get_countries');
add_action('wp_ajax_nopriv_get_related_cities', 'adforest_get_countries');
if (!function_exists('adforest_get_countries')) {

    function adforest_get_countries()
    {
        check_ajax_referer('adforest_get_countries_nonce', 'security');
        global $adforest_theme;

        $cat_id = $_POST['country_id'];
        $ad_cats = adforest_get_cats('ad_country', $cat_id);
        $res = '';
        if (count($ad_cats) > 0) {
            $selected_cats = adforest_get_taxonomy_parents($cat_id, 'ad_country', false);
            $find = '&raquo;';
            $replace = '';
            $selected_cats = preg_replace("/$find/", $replace, $selected_cats, 1);
            $res = '<label>' . $selected_cats . '</label>';
            //$res = '<label>'.adforest_get_taxonomy_parents( $cat_id, 'ad_country', false).'</label>';
            $res .= '<ul class="city-select-city" >';
            foreach ($ad_cats as $ad_cat) {
                $location_count = get_term($ad_cat->term_id);
                $count = $location_count->count;

                $id = 'ajax_states';
                $res .= '<li class="col-sm-4 col-md-4 col-xs-6"><a href="javascript:void(0);" data-country-id="' . esc_attr($ad_cat->term_id) . '" id="' . $id . '">' . $ad_cat->name . ' <span>(' . esc_html($count) . ')</span></a></li>';
            }
            $res .= '</ul>';
            echo adforest_return_echo($res);
        } else {
            echo "submit";
        }
        die();
    }
}

if (!function_exists('adforest_get_disbale_ads')) {
    function adforest_get_disbale_ads($user_id)
    {
        global $wpdb;
        $user_id = absint($user_id);
        if (!$user_id) {
            return 0;
        }
        $query = $wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_status = %s AND post_type = %s",
            $user_id,
            'pending',
            'ad_post'
        );

        $rows = $wpdb->get_results($query);

        return count($rows);
    }
}

// Recently Viewed posts//
if (!function_exists('display_recently_viewed_ad_posts')) {
    function display_recently_viewed_ad_posts($paged = 1)
    {
        if (!is_user_logged_in()) {
            return '<div class="table-responsive"><p>'
                . esc_html__('No Recently Viewed Ads.', 'adforest')
                . '</p></div>';
        }

        $user_id = get_current_user_id();
        $recently_viewed = get_user_meta($user_id, 'recently_viewed_ad_posts', true);
        $recently_viewed = is_array($recently_viewed) ? array_filter($recently_viewed) : [];

        if (empty($recently_viewed)) {
            if ($paged === 1) {
                return '<div class="table-responsive"><p>'
                    . esc_html__('No Recently Viewed Ads.', 'adforest')
                    . '</p></div>';
            } else {
                return '';
            }
        }

        $args = [
            'post_type' => 'ad_post',
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => 5,
            'paged' => $paged,
            'post__in' => $recently_viewed,
            'orderby' => 'post__in',
            'ignore_sticky_posts' => true,
        ];
        $query = new WP_Query($args);

        ob_start(); ?>

        <div class="table-responsive">
            <table class="table top-selling-table">
                <thead>
                <tr>
                    <th class="min-width"><h6
                                class="text-sm text-medium"><?php esc_html_e("Ad Title", "adforest"); ?></h6></th>
                    <th class="min-width"><h6 class="text-sm text-medium"><?php esc_html_e("Price", "adforest"); ?></h6>
                    </th>
                    <th class="min-width"><h6 class="text-sm text-medium"><?php esc_html_e("Views", "adforest"); ?></h6>
                    </th>
                    <th class="min-width"><h6
                                class="text-sm text-medium"><?php esc_html_e("Status", "adforest"); ?></h6></th>
                </tr>
                </thead>
                <tbody>
                <?php if ($query->have_posts()) : ?>
                    <?php while ($query->have_posts()) : $query->the_post();
                        $ad = get_ad_post_details(get_the_ID());
                        $ad_views = get_post_meta(get_the_ID(), 'adforest_ad_views', true);
                        ?>
                        <tr>
                            <td>
                                <div class="product">
                                    <div class="image">
                                        <img src="<?php echo esc_url($ad['img']); ?>"
                                             alt="<?php echo esc_attr($ad['ad_title']); ?>"/>
                                    </div>
                                    <a href="<?php echo esc_url($ad['ad_link']); ?>">
                                        <p class="text-sm"><?php echo esc_html($ad['ad_title']); ?></p>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <p class="text-sm">
                                    <?php
                                    $price = !empty($ad['price']) ? $ad['price'] : esc_html__('N/A', 'adforest');
                                    echo esc_html($price);
                                    ?>
                                </p>
                            </td>
                            <td><p class="text-sm"><?php echo esc_html($ad_views); ?></p></td>
                            <td>
                                <?php
                                $status = get_post_status();

                                $status_labels = array(
                                    'publish' => __( 'Published', 'adforest' ),
                                    'pending' => __( 'Pending Review', 'adforest' ),
                                    'draft'   => __( 'Draft', 'adforest' ),
                                    'trash'   => __( 'Trash', 'adforest' ),
                                    'private' => __( 'Private', 'adforest' ),
                                    'future'  => __( 'Scheduled', 'adforest' ),
                                );

                                $label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );

                                printf(
                                    '<span class="status-btn %1$s-btn">%2$s</span>',
                                    esc_attr( $status ),
                                    esc_html( $label )
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endwhile;
                    wp_reset_postdata(); ?>
                <?php else: ?>
                    <?php if ($paged === 1) : ?>
                        <tr>
                            <td colspan="3">
                                <?php esc_html_e('No Recently Viewed Ads.', 'adforest'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        // show Load More only if more pages exist
        if ($query->max_num_pages > $paged) : ?>
            <div class="d-flex justify-content-center align-items-center">
                <button
                        id="load-more-ads"
                        class="btn dark-btn adt-button-dark-1"
                        data-next-page="<?php echo esc_attr($paged + 1); ?>"
                        data-security="<?php echo wp_create_nonce('load_more_ads_nonce') ?>"
                >
                    <?php esc_html_e('Load More', 'adforest'); ?>
                </button>
            </div>
        <?php
        endif;

        return ob_get_clean();
    }
}
// Recently Viewed posts//

if (!function_exists('adforest_social_profiles')) {
    function adforest_social_profiles()
    {
        global $adforest_theme;
        if (isset($adforest_theme['sb_enable_social_links']) && $adforest_theme['sb_enable_social_links']) {
            $social_netwroks = array(
                'facebook' => esc_html__('Facebook', 'adforest'),
                'twitter' => esc_html__('Twitter', 'adforest'),
                'linkedin' => esc_html__('Linkedin', 'adforest'),
                'instagram' => esc_html__('Instagram', 'adforest'),
                //'google' => __('Google', 'adforest')
            );
        } else {
            $social_netwroks = array();
        }

        return $social_netwroks;
    }
}

if (!function_exists('adforest_check_if_phoneVerified')) {
    function adforest_check_if_phoneVerified($user_id = 0)
    {
        global $adforest_theme;
        $verifed_phone_number = false;
        if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification']) {
            if (isset($adforest_theme['sb_new_user_sms_verified_can']) && $adforest_theme['sb_new_user_sms_verified_can'] == true) {
                $user_id = ($user_id) ? $user_id : get_current_user_id();
                if (get_user_meta($user_id, '_sb_is_ph_verified', true) != '1') {
                    //get_user_meta($user_id, '_sb_is_ph_verified', true);
                    $verifed_phone_number = true;
                }
            }
        }

        return $verifed_phone_number;
    }
}

/* ============================== */
/* Getting Ad alerts */
/* =============================== */
if (!function_exists('sb_get_ad_alerts')) {
    function sb_get_ad_alerts($user_id = '')
    {
        global $wpdb;

        $user_id = absint($user_id);
        if (!$user_id) {
            return array();
        }

        $like_pattern = $wpdb->esc_like("_cand_alerts_{$user_id}") . '%';
        $query = $wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
            $user_id,
            $like_pattern
        );

        $resumes = $wpdb->get_results($query);
        $data = array();

        foreach ($resumes as $resume) {
            $value = json_decode($resume->meta_value, true);
            $data[$resume->meta_key] = $value;
        }

        return $data;
    }
}

if (!function_exists('get_next_posts_link_custom')) {
    function get_next_posts_link_custom($wp_query, $label = null, $max_page = 0)
    {
        global $paged;

        if (!$max_page) {
            $max_page = $wp_query->max_num_pages;
        }

        if (!$paged) {
            $paged = 1;
        }

        $nextpage = intval($paged) + 1;

        if (null === $label) {
            $label = __('Next Page &raquo;', 'adforest');
        }

        if ($nextpage <= $max_page) {
            /**
             * Filters the anchor tag attributes for the next posts page link.
             *
             * @param string $attributes Attributes for the anchor tag.
             *
             * @since 2.7.0
             *
             */
            $attr = apply_filters('next_posts_link_attributes', '');

            return '<a href="' . next_posts($max_page, false) . "\" $attr>" . preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label) . '</a>';
        }
    }
}

if (!function_exists('adforest_display_cats')) {
    function adforest_display_cats($pid, $class = "")
    {
        global $adforest_theme;
        $post_categories = wp_get_object_terms($pid, array('ad_cats'), array('orderby' => 'parent'));
        $cats_html = '';
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';

        foreach ($post_categories as $c) {
            $cat = get_term($c);
            $cats_html .= '<span class="padding_cats"><a href="' . adforest_cat_link_page($cat->term_id, $cat_link_page, 'cat_id') . '" class="' . $class . '">' . esc_html($cat->name) . '</a></span>';
        }

        return $cats_html;
    }
}

add_filter('adforest_make_bid_categ', 'adforest_make_bid_categ_callback', 11, 1);
if (!function_exists('adforest_make_bid_categ_callback')) {
    function adforest_make_bid_categ_callback($bid_categories = true)
    {
        global $adforest_theme;
        $_sb_allow_bidding = get_user_meta(get_current_user_id(), '_sb_allow_bidding', true);
        $sb_enable_comments_offer = isset($adforest_theme['sb_enable_comments_offer']) ? $adforest_theme['sb_enable_comments_offer'] : false;
        if (!$sb_enable_comments_offer) { /// bidding master switch off
            return false;
        }

        $sb_make_bid_categorised = isset($adforest_theme['sb_make_bid_categorised']) ? $adforest_theme['sb_make_bid_categorised'] : true;
        $bid_categorised_type    = isset($adforest_theme['bid_categorised_type']) ? $adforest_theme['bid_categorised_type'] : 'all';
        $is_selective            = ( $sb_make_bid_categorised && $bid_categorised_type === 'selective' );

        $ad_id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;

        // NEW-AD FLOW (no ?id= in URL).
        // Previously this returned true unconditionally — causing bidding fields to appear
        // for EVERY category regardless of the selective setting. In selective mode we now
        // default to hidden; the JS listener on the category dropdown (AJAX endpoint
        // `adforest_check_bidding_cat`) will reveal it when a bid-enabled category is picked.
        if ($ad_id == 0) {
            return $is_selective ? false : true;
        }

        // EDIT-AD FLOW (ad_id known) — original logic preserved below.
        $bid_flag = false;
        if ($_sb_allow_bidding <= 0) {
            if ($_sb_allow_bidding == -1) {
                $bid_flag = true;
            }
        }
        if (isset($_sb_allow_bidding) && $_sb_allow_bidding != '' && $bid_flag) {
            $bid_categories = false;
        } else {
            //$ad_id = get_user_meta(get_current_user_id(), 'ad_in_progress', true);
            if ($is_selective && $ad_id != 0) {
                $cat_id = get_post_meta($ad_id, 'adforest_latest_bid_cat_id', true);
                $cat_id = isset($cat_id) && !empty($cat_id) ? $cat_id : 0;
                $bid_cat_base = get_term_meta($cat_id, 'adforest_make_bid_cat_base', true);
                if (isset($bid_cat_base) && $bid_cat_base == 'yes') {
                    $bid_categories = true;
                } else {
                    $bid_categories = false;
                }
                update_user_meta($ad_id, 'adforest_latest_bid_cat_id', $cat_id);
            } else {
                $bid_categories = true;
            }
        }

        return $bid_categories;
    }
}

/**
 * AJAX: Check whether a given category has bidding enabled under the
 * "Selective" bidding category type. Used by the ad posting form's
 * category dropdown to show/hide the bidding section dynamically.
 *
 * Response:
 *   "1" — bidding allowed for this category
 *   "0" — bidding not allowed for this category
 *
 * Honours the global "Selective" setting — if selective mode is OFF
 * (i.e. "all" mode), always returns "1" when bidding is globally enabled.
 */
add_action('wp_ajax_adforest_check_bidding_cat', 'adforest_check_bidding_cat_callback');
add_action('wp_ajax_nopriv_adforest_check_bidding_cat', 'adforest_check_bidding_cat_callback');
if (!function_exists('adforest_check_bidding_cat_callback')) {
    function adforest_check_bidding_cat_callback()
    {
        global $adforest_theme;

        $cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;

        // Master bidding switch
        $bidding_on = isset($adforest_theme['sb_enable_comments_offer']) ? (bool) $adforest_theme['sb_enable_comments_offer'] : false;
        if (!$bidding_on) {
            echo '0';
            wp_die();
        }

        $sb_make_bid_categorised = isset($adforest_theme['sb_make_bid_categorised']) ? $adforest_theme['sb_make_bid_categorised'] : true;
        $bid_categorised_type    = isset($adforest_theme['bid_categorised_type']) ? $adforest_theme['bid_categorised_type'] : 'all';
        $is_selective            = ($sb_make_bid_categorised && $bid_categorised_type === 'selective');

        // "All" mode — bidding is allowed in every category
        if (!$is_selective) {
            echo '1';
            wp_die();
        }

        // Selective mode — check the category's per-term meta
        if ($cat_id <= 0) {
            echo '0';
            wp_die();
        }

        $bid_cat_base = get_term_meta($cat_id, 'adforest_make_bid_cat_base', true);
        echo ($bid_cat_base === 'yes') ? '1' : '0';
        wp_die();
    }
}

if (!function_exists('adforest_comments_pagination')) {

    function adforest_comments_pagination($total_records, $current_page)
    {
        // Check if a records is set.
        if (!isset($total_records)) {
            return;
        }
        if (!isset($current_page)) {
            return;
        }
        $args = array(
            'base' => add_query_arg('page', '%#%'),
            'format' => '?page=%#%',
            'total' => $total_records,
            'current' => $current_page,
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'prev_next' => true,
            'prev_text' => '<i class="fa fa-chevron-left" aria-hidden="true"></i>',
            'next_text' => '<i class="fa fa-chevron-right" aria-hidden="true"></i>',
            'type' => 'array'
        );
        $pagination = paginate_links($args);
        $pagination_html = '';
        if (count((array)$pagination) > 0) {
            $pagination_html = '<ul class="pagination">';
            foreach ($pagination as $key => $page_link) {
                $link = $page_link;
                $class = '';
                if (strpos($page_link, 'current') !== false) {
                    $link = '<a href="javascript:void(0);">' . $current_page . '</a>';
                    $class = 'active';
                }
                $pagination_html .= '<li class="' . $class . '">' . $link . '</li>';
            }
            $pagination_html .= '</ul>';
        }

        return $pagination_html;
    }

}

if (!function_exists('adforest_ad_locations_limit')) {
    function adforest_ad_locations_limit($ad_location = '')
    {
        global $adforest_theme;
        if (isset($adforest_theme['sb_ad_location_limit_on']) && $adforest_theme['sb_ad_location_limit_on'] && isset($adforest_theme['sb_ad_location_limit']) && $adforest_theme['sb_ad_location_limit'] != "") {
            return adforest_words_count($ad_location, $adforest_theme['sb_ad_location_limit']);
        } else {
            return $ad_location;
        }
    }
}

/* Get and Set Post Views */
if (!function_exists('adforest_getPostViews')) {
    function adforest_getPostViews($postID)
    {
        $postID = esc_html($postID);
        $count_key = 'sb_post_views_count';
        $count = get_post_meta($postID, $count_key, true);
        if ($count == '') {
            delete_post_meta($postID, $count_key);
            add_post_meta($postID, $count_key, '0');

            return "0";
        }

        return $count;
    }
}

if (!function_exists('adforest_pagination_search')) {

    function adforest_pagination_search($wp_query)
    {
        //  if (is_singular())
        //return;


        /** Stop execution if there's only 1 page */
        if ($wp_query->max_num_pages <= 1) {
            return;
        }

        if (get_query_var('paged')) {
            $paged = get_query_var('paged');
        } elseif (get_query_var('page')) {
            $paged = get_query_var('page');
        } else {
            $paged = 1;
        }

        $max = intval($wp_query->max_num_pages);
        /**     Add current page to the array */
        if ($paged >= 1) {
            $links[] = $paged;
        }

        /**     Add the pages around the current page to the array */
        if ($paged >= 3) {
            $links[] = $paged - 1;
            $links[] = $paged - 2;
        }

        if (($paged + 2) <= $max) {
            $links[] = $paged + 2;
            $links[] = $paged + 1;
        }

        echo '<ul class="pagination pagination-lg">' . "\n";

        if (get_previous_posts_link()) {
            printf('<li>%s</li>' . "\n", get_previous_posts_link());
        }

        /**     Link to first page, plus ellipses if necessary */
        if (!in_array(1, $links)) {
            $class = 1 == $paged ? ' class="active"' : '';

            printf('<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link(1)), '1');

            if (!in_array(2, $links)) {
                echo '<li><a href="javascript:void(0);">...</a></li>';
            }
        }
        /**     Link to current page, plus 2 pages in either direction if necessary */
        sort($links);
        foreach ((array)$links as $link) {

            $class = $paged == $link ? ' class="active"' : '';
            printf('<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link($link)), $link);
        }
        /**     Link to last page, plus ellipses if necessary */
        if (!in_array($max, $links)) {
            if (!in_array($max - 1, $links)) {
                echo '<li><a href="javascript:void(0);">...</a></li>' . "\n";
            }
            $class = $paged == $max ? ' class="active"' : '';
            printf('<li%s><a href="%s">%s</a></li>' . "\n", $class, esc_url(get_pagenum_link($max)), $max);
        }

        if (get_next_posts_link_custom($wp_query)) {
            printf('<li>%s</li>' . "\n", get_next_posts_link_custom($wp_query));
        }

        echo '</ul>' . "\n";
    }

}

if (!function_exists('adforest_dynamic_field_type_template')) {
    function adforest_dynamic_field_type_template($term_id = '')
    {
        $template_id = adforest_dynamic_templateID($term_id);
        $result = get_term_meta($template_id, '_sb_dynamic_form_fields', true);
        $template_array = sb_dynamic_form_data($result);

        return $template_array;
    }

}

if (!function_exists('adforest_dynamic_field_type')) {
    function adforest_dynamic_field_type($template_array = [], $slug = '')
    {
        $field_type = '';
        if (isset($template_array) && count($template_array) > 0) {
            foreach ($template_array as $ct) {
                if ($ct['slugs'] == $slug) {
                    if ($ct['types'] == 1) {
                        $field_type = 'input';
                    } else if ($ct['types'] == 2) {
                        $field_type = 'select';
                    } else if ($ct['types'] == 3 || $ct['types'] == 9) {
                        $field_type = 'checkbox';
                    } else if ($ct['types'] == 4) {
                        $field_type = 'date';
                    } else if ($ct['types'] == 5) {
                        $field_type = 'url';
                    } else if ($ct['types'] == 6) {
                        $field_type = 'number';
                    } else if ($ct['types'] == 7) {
                        $field_type = 'radio';
                    }
                }
            }
        }

        return $field_type;
    }

}

/* getting social icon array */
if (!function_exists('adforest_social_icons')) {
    function adforest_social_icons($social_network)
    {
        $social_icons = array(
            'Facebook' => 'fab fa-facebook',
            'X' => 'fab fa-x-twitter',
            'Linkedin' => 'fab fa-linkedin ',
            'Google' => 'fab fa-google',
            'YouTube' => 'fab fa-youtube',
            'Vimeo' => 'fab fa-vimeo ',
            'Pinterest' => 'fab fa-pinterest ',
            'Tumblr' => 'fab fa-tumblr ',
            'Reddit' => 'fab fa-reddit ',
            'Flickr' => 'fab fa-flickr ',
            'StumbleUpon' => 'fab fa-stumbleupon',
            'Delicious' => 'fab fa-delicious ',
            'dribble' => 'fab fa-dribbble ',
            'behance' => 'fab fa-behance',
            'DeviantART' => 'fab fa-deviantart',
            'Instagram' => 'fab fa-instagram',
        );

        return $social_icons[$social_network];
    }
}

if (!function_exists('adforest_validateDateFormat')) {
    function adforest_validateDateFormat($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
}

if (!function_exists('adforest_show_toastr_and_redirect')) {
    function adforest_show_toastr_and_redirect()
    {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const toastrCss = document.createElement('link');
                toastrCss.rel = 'stylesheet';
                toastrCss.href = 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css';
                document.head.appendChild(toastrCss);

                const toastrJs = document.createElement('script');
                toastrJs.src = 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js';
                toastrJs.onload = function () {
                    toastr.error("<?php echo esc_js(__('Only Ad owner can edit this Ad', 'adforest')); ?>");

                    setTimeout(function () {
                        window.location.href = "<?php echo esc_url(home_url('/')); ?>";
                    }, 1000);
                };
                document.body.appendChild(toastrJs);
            });
        </script>
        <?php
        exit;
    }
}

add_filter('register_post_type_args', 'adforest_register_post_type_args', 10, 2);
if (!function_exists('adforest_register_post_type_args')) {
    function adforest_register_post_type_args($args, $post_type)
    {
        $adforest_theme_values = get_option('adforest_theme');
        if (isset($adforest_theme_values['sb_url_rewriting_enable']) && $adforest_theme_values['sb_url_rewriting_enable'] && isset($adforest_theme_values['sb_ad_slug']) && $adforest_theme_values['sb_ad_slug'] != "") {
            if ('ad_post' === $post_type) {
                $old_slug = 'ad';
                if (get_option('sb_ad_old_slug') != "") {
                    $old_slug = get_option('sb_ad_old_slug');
                }
                $args['rewrite']['slug'] = $adforest_theme_values['sb_ad_slug'];
                update_option('sb_ad_old_slug', $adforest_theme_values['sb_ad_slug']);
                if (($current_rules = get_option('rewrite_rules'))) {
                    foreach ($current_rules as $key => $val) {
                        if (strpos($key, $old_slug) !== false) {
                            add_rewrite_rule(str_ireplace($old_slug, $adforest_theme_values['sb_ad_slug'], $key), $val, 'top');
                        }
                    }
                    flush_rewrite_rules();
                }
            }
        }

        return $args;
    }
}

if (!function_exists('adforest_change_taxonomies_slug')) {
    function adforest_change_taxonomies_slug($args, $taxonomy)
    {
        /* item category */
        $adforest_theme_values = get_option('adforest_theme');
        if (isset($adforest_theme_values['sb_url_rewriting_enable_cat']) && $adforest_theme_values['sb_url_rewriting_enable_cat'] && isset($adforest_theme_values['sb_cat_slug']) && $adforest_theme_values['sb_cat_slug'] != "") {
            if ('ad_cats' === $taxonomy) {
                $args['rewrite']['slug'] = $adforest_theme_values['sb_cat_slug'];
            }
        }
        if (isset($adforest_theme_values['sb_url_rewriting_enable_location']) && $adforest_theme_values['sb_url_rewriting_enable_location'] && isset($adforest_theme_values['sb_ad_location_slug']) && $adforest_theme_values['sb_ad_location_slug'] != "") {
            if ('ad_country' === $taxonomy) {
                $args['rewrite']['slug'] = $adforest_theme_values['sb_ad_location_slug'];
            }
        }
        if (isset($adforest_theme_values['sb_url_rewriting_enable_ad_tags']) && $adforest_theme_values['sb_url_rewriting_enable_ad_tags'] && isset($adforest_theme_values['sb_ad_tags_slug']) && $adforest_theme_values['sb_ad_tags_slug'] != "") {
            if ('ad_tags' === $taxonomy) {
                $args['rewrite']['slug'] = $adforest_theme_values['sb_ad_tags_slug'];
            }
        }

        return $args;
    }

}
add_filter('register_taxonomy_args', 'adforest_change_taxonomies_slug', 10, 2);

if (!function_exists('adforest_featured_grids_on_search')) {
    function adforest_featured_grids_on_search()
    {
        global $adforest_theme;
        $allow_featured_ads = $adforest_theme['feature_on_search'] ?? false;
        $max_ads_feature = $adforest_theme['max_ads_feature'] ?? 5;
        $title_feature = $adforest_theme['feature_ads_title'] ?? '';
        if ($allow_featured_ads) {

            // Search 2.0 Part 6 — match the main loop's view mode. When the
            // user is in List view AND the new card dispatcher is loaded,
            // render each featured ad as a vertical list_view card and
            // skip the owl-carousel entirely (a horizontal carousel of
            // wide list cards renders broken). Grid mode keeps the
            // existing carousel + admin's chosen card design intact.
            $featured_view_type = ( isset( $_GET['view-type'] ) && $_GET['view-type'] !== '' )
                ? $_GET['view-type']
                : ( isset( $adforest_theme['search_ad_layout_for_search'] ) ? $adforest_theme['search_ad_layout_for_search'] : 'grid' );
            // Also honor the admin's "Grid Type" = List View choice. Without
            // this, picking List View as the card design while leaving the
            // default search layout on Grid would render list cards inside
            // owl-carousel slides — squeezing wide list rows into narrow
            // portrait columns (the visible breakage on the Top Bar layout).
            $featured_grid_layout_is_list = ( isset( $adforest_theme['adforest_grid_layout'] ) && $adforest_theme['adforest_grid_layout'] === 'list_view' );
            $featured_is_list   = ( ( $featured_view_type === 'list' || $featured_grid_layout_is_list ) && function_exists( 'adforest_load_search_card' ) );
        ?>
        <div class="margin-t-30">
            <div class="row">
                <div class="col-lg-12">

                    <div class="adt-ads-top-box">
                        <h2><?php echo __($title_feature, "adforest"); ?></h2>
                    </div>
                    <?php
                    $args = [
                        'post_type' => 'ad_post',
                        'posts_per_page' => $max_ads_feature,
                        'meta_key' => '_adforest_is_feature',
                        'meta_value' => '1',
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ];
                    $featured_ads = new WP_Query($args);
                    $title_limit = 40;
                    $location_limit = 40;
                    if (isset($adforest_theme['sb_ad_title_limit_on']) && $adforest_theme['sb_ad_title_limit_on'] == '1') {
                        $title_limit = isset($adforest_theme['sb_ad_title_limit']) ? $adforest_theme['sb_ad_title_limit'] : 40;
                    }

                    if (isset($adforest_theme['sb_ad_location_limit_on']) && $adforest_theme['sb_ad_location_limit_on'] == '1') {
                        $location_limit = isset($adforest_theme['sb_ad_location_limit']) ? $adforest_theme['sb_ad_location_limit'] : 40;
                    }
                    ?>
                    <div class="<?php echo $featured_is_list ? 'ad-featured-list-mode' : 'adt-ads-carousel owl-carousel owl-theme'; ?>">
                        <?php
                        while ($featured_ads->have_posts()) {
                            $featured_ads->the_post();
                            $ad_details = get_ad_post_details(get_the_ID());
                            $first_img = $ad_details['img'];
                            $truncated_location = truncate_string($ad_details['location'], $location_limit);
                            $truncated_title = truncate_string($ad_details['ad_title'], $title_limit);
                            $price_html = $ad_details['price_html'];
                            $ad_permalink = $ad_details['ad_link'];
                            $heart_class = $ad_details['heart_class'];
                            $is_featured = $ad_details['is_featured'];
                            $all_ad_images = $ad_details['all_ad_images'];
                            $ad_poster_img = $ad_details['ad_poster_img'];
                            $ad_poster_name = $ad_details['ad_poster_name'];
                            $ad_title = truncate_string($ad_details['ad_title'], $title_limit);
                            $featured_tag = $is_featured ? '<img style="transform: rotate(180deg);" src="' . esc_url(get_template_directory_uri()) . '/images/featured.png' . '" alt="featured-tag" class="featured-tag">' : '';
                            $ad_categories_post = $ad_details['categories'];
                            $top_bar_specific_style = '';
                            if ($adforest_theme['search_design'] == 'topbar') {
                                $top_bar_specific_style = 'top_bar_specific_style';
                            }
                            $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);
                            ?>
                            <?php
                            // Recompute favourite metadata so the new-layout
                            // cards can render the heart with the correct
                            // tooltip + active state without the main loop.
                            $is_fav_featured    = isset( $ad_details['is_fav'] ) ? (bool) $ad_details['is_fav'] : false;
                            $fav_title_featured = $is_fav_featured ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                            $fav_extra_featured = $is_fav_featured ? ' ad-favourited' : '';

                            $card_data = array(
                                'ad_permalink'           => $ad_permalink,
                                'first_img'              => $first_img,
                                'all_ad_images'          => $all_ad_images,
                                'is_featured'            => $is_featured,
                                'heart_class'            => $heart_class,
                                'is_fav'                 => $is_fav_featured,
                                'fav_title'              => $fav_title_featured,
                                'fav_extra'              => $fav_extra_featured,
                                'truncated_title'        => $truncated_title,
                                'ad_title'               => $ad_title,
                                'truncated_location'     => $truncated_location,
                                'price_html'             => $price_html,
                                'ad_categories_post'     => $ad_categories_post,
                                'ad_poster_img'          => $ad_poster_img,
                                'ad_poster_name'         => $ad_poster_name,
                                'ad_type'                => $ad_type,
                                'ad_details'             => $ad_details,
                                'top_bar_specific_style' => $top_bar_specific_style,
                                'featured_tag'           => $featured_tag,
                            );

                            if ( $featured_is_list ) {
                                // List mode: render the list_view card
                                // directly inside the vertical container.
                                // Skip the .item wrapper (that was an owl-
                                // carousel slide convention) — the list
                                // container styles spacing per-card.
                                adforest_load_search_card( 'list_view', $card_data );
                            } else {
                                ?>
                                <div class="item">
                                    <?php if (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'simple') {
                                        echo adforest_ad_grid_1($ad_permalink, $first_img, $is_featured, $ad_categories_post, $ad_details, $truncated_title, $truncated_location, $price_html, $heart_class);
                                    } elseif (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'with_labels') {
                                        echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class);
                                    } elseif (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'modern') {
                                        echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                    } elseif (
                                        function_exists('adforest_load_search_card')
                                        && isset($adforest_theme['adforest_grid_layout'])
                                        && in_array($adforest_theme['adforest_grid_layout'], array('modern_card', 'compact_grid', 'list_view'), true)
                                    ) {
                                        // Search 2.0 Part 3: reuse the same card
                                        // templates as the main loop so Featured
                                        // First renders with the admin's chosen
                                        // card design.
                                        adforest_load_search_card($adforest_theme['adforest_grid_layout'], $card_data);
                                    } ?>
                                </div>
                                <?php
                            }
                            ?>
                            <?php
                        }
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
    }
}

/**
 * Get WooCommerce product category details by ID.
 *
 * @param int $cat_id The product category ID.
 * @return array|false An array of category details or false if not found.
 */
if (!function_exists('adforest_get_woocommerce_category_details')) {
    function adforest_get_woocommerce_category_details($cat_id)
    {
        $term = get_term($cat_id, 'product_cat');

        if (is_wp_error($term) || !$term) {
            return false;
        }

        return array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'count' => $term->count,
            'parent' => $term->parent,
            'link' => get_term_link($term),
        );
    }
}

if (!function_exists('adforest_ad_grid_1')) {
    function adforest_ad_grid_1($ad_permalink, $first_img, $is_featured, $ad_categories_post, $ad_details, $truncated_title, $truncated_location, $price_html, $heart_class)
    {
        $posted_time = get_the_time('U', get_the_ID());

        ob_start(); ?>
        <div class="adt-category-ad-card">
            <div class="category-img-box">
                <a href="<?php echo esc_url($ad_permalink, 'adforest'); ?>">
                    <img class="img-fluid" src="<?php echo esc_url($first_img, 'adforest'); ?>"
                         alt="<?php echo esc_html(get_the_title()); ?>">
                </a>
                <?php if ($is_featured) : ?>
                    <img class="featured-tag"
                         src="<?php echo esc_url(get_template_directory_uri()) . '/images/featured.png'; ?>"
                         alt="featured-tag">
                <?php endif; ?>
                <div class="video_icon_container"><?php echo adforest_video_icon() ?></div>
            </div>
            <div class="category-content-box">
                <?php
                $category_links = [];
                foreach ((array) $ad_categories_post as $category) {
                    if (!is_object($category)) {
                        continue;
                    }
                    $category_url = get_term_link($category);
                    if (!is_wp_error($category_url)) {
                        $category_links[] = '<a class="ctg-tag" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
                    }
                }
                //                $category_links_string = implode(' > ', $category_links);
                $selected_categories = $ad_details['categories'] ?? '';
                $category_link = '';
                $category_name = '';
                // $selected_categories = '';
                if (!empty($selected_categories) && is_array($selected_categories) && isset($selected_categories[0]) && is_object($selected_categories[0]) && isset($selected_categories[0]->name)) {
                    $category_link = isset($selected_categories[0]->term_id) ? get_term_link($selected_categories[0]->term_id) : "";
                    $category_name = $selected_categories[0]->name;
                }
                ?>
                <div class="adt-ad-cats">
                    <a class="ctg-tag" href="<?php echo esc_url($category_link) ?>">
                        <?php echo esc_html($category_name); ?>
                    </a>
                </div>
                <a href="<?php echo esc_url($ad_permalink, 'adforest'); ?>">
                    <h5><?php echo esc_html($truncated_title); ?></h5>
                </a>
                <p>
                    <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($truncated_location); ?>
                </p>
                <p class="ad_grid_date_posted">
                    <?php echo adforest_get_ad_posted_date($posted_time); ?>
                </p>
                <div class="price-box">
                    <?php echo wp_kses($price_html, ADFOREST_ALLOWED_FORM_HTML); ?>
                    <?php
                    $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                    $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                    $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                    ?>
                    <a href="javascript:void(0);" class="favourite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>" data-adid="<?php echo get_the_ID(); ?>"
                       data-toggle="tooltip" data-placement="top"
                       title="<?php echo esc_attr( $fav_title_local ); ?>"
                       aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
                        <i class="<?php echo esc_attr($heart_class); ?>"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}

if (!function_exists('adforest_ad_grid_2')) {
    function adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class, $post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();
        $ad_type = get_post_meta($post_id, '_adforest_ad_type', true);
        $author_id = get_post_field('post_author', $post_id);
        $author_link = get_author_posts_url($author_id);
        ob_start(); ?>
        <div class="adt-property-ad-card">
            <div class="adt-property-img-box">
                <div class="adt-property-img-carousel owl-carousel owl-theme">
                    <?php
                    if (!empty($all_ad_images)) {
                        foreach ($all_ad_images as $ad_image) {
                            echo '<div class="item"><a href="' . esc_url($ad_permalink) . '"><img src="' . esc_url($ad_image) . '" alt="' . esc_html(get_the_title()) . '"></a></div>';
                        }
                    } else {
                        $default_image_url = esc_url(get_template_directory_uri()) . '/images/no-image.jpg';
                        echo '<div class="item"><a href="' . esc_url($ad_permalink) . '"><img src="' . esc_url($default_image_url) . '" alt="' . esc_html(get_the_title()) . '"></a></div>';
                    }
                    ?>
                </div>
                <div class="video_icon_container"><?php echo adforest_video_icon() ?></div>
                <div class="tags-box">
                    <?php
                    if ($is_featured) {
                        echo '<span class="featured">' . esc_html__("Featured", "adforest") . '</span>';
                    }

                    if ($ad_type) {
                        echo '<span class="urgent">' . esc_html($ad_type) . '</span>';
                    }
                    ?>
                </div>
                <div class="author-box">
                    <a href="<?php echo esc_url($author_link); ?>"><img
                                src="<?php echo esc_url($ad_poster_img, "adforest"); ?>" alt="author-img"></a>
                    <a href="<?php echo esc_url($author_link); ?>"><span><?php echo esc_html($ad_poster_name); ?></span></a>
                </div>
            </div>
            <div class="adt-property-content-box">
                <span><i class="far fa-calendar-minus"></i> <?php echo get_the_date('F j, Y'); ?></span>
                <a href="<?php echo esc_url($ad_permalink); ?>">
                    <h3 dir="auto"><?php echo esc_html($ad_title); ?></h3>
                </a>
            </div>
            <div class="adt-property-location-box">
                <i class="fas fa-location-arrow"></i> <?php echo esc_html($truncated_location); ?>
            </div>
            <div class="adt-property-price-box">
                <h4><?php echo wp_kses($price_html, ADFOREST_ALLOWED_FORM_HTML); ?></h4>
                <?php
                $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                ?>
                <a href="javascript:void(0);" class="favorite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>" data-adid="<?php echo get_the_ID(); ?>"
                   data-toggle="tooltip" data-placement="top"
                   title="<?php echo esc_attr( $fav_title_local ); ?>"
                   aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
                    <i class="<?php echo esc_attr($heart_class); ?>"></i>
                </a>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}

if (!function_exists('adforest_ad_grid_3')) {
    function adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location, $post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();
        $author_id = get_post_field('post_author', $post_id);
        $author_link = get_author_posts_url($author_id);
        ob_start(); ?>
        <div class="adt-property-ad-card adt-car-ad-card">
            <div class="adt-property-img-box">
                <div class="adt-property-img-carousel owl-carousel owl-theme">
                    <?php
                    if (!empty($all_ad_images)) {
                        foreach ($all_ad_images as $ad_image) {
                            echo '<div class="item"><a href="' . esc_url($ad_permalink) . '"><img src="' . esc_url($ad_image) . '" alt="' . esc_html(get_the_title()) . '"></a></div>';
                        }
                    } else {
                        $default_image_url = esc_url(get_template_directory_uri()) . '/images/no-image.jpg';
                        echo '<div class="item"><a href="' . esc_url($ad_permalink) . '"><img src="' . esc_url($default_image_url) . '" alt="' . esc_html(get_the_title()) . '"></a></div>';
                    }
                    ?>
                </div>
                <div class="video_icon_container"><?php echo adforest_video_icon() ?></div>
                <div class="favorite-box">
                    <?php
                    $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                    $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                    $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                    ?>
                    <a href="javascript:void(0);" class="favorite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>" data-adid="<?php echo get_the_ID(); ?>"
                       data-toggle="tooltip" data-placement="top"
                       title="<?php echo esc_attr( $fav_title_local ); ?>"
                       aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
                        <i class="<?php echo esc_attr($heart_class); ?>"></i>
                    </a>
                </div>
                <?php echo ($featured_tag);  ?>
                <div class="author-box">
                    <a href="<?php echo esc_url($author_link) ?>"><img src="<?php echo esc_url($ad_poster_img); ?>"
                                                                       alt="author-img"></a>
                    <a href="<?php echo esc_url($author_link) ?>"><span><?php echo esc_html($ad_poster_name); ?></span></a>
                </div>
            </div>
            <div class="adt-property-content-box">
                <?php if (!empty($ad_type)) { ?>
                    <span class="ctg-tag used"><?php echo esc_html($ad_type); ?></span>
                <?php } ?>
                <a href="<?php echo esc_url($ad_permalink); ?>">
                    <h3><?php echo esc_html($ad_title); ?></h3>
                </a>
                <span class="price"><?php echo wp_kses($price_html, ADFOREST_ALLOWED_FORM_HTML); ?></span>
                <span class="publish-date"><i class="far fa-calendar-minus"></i><?php echo get_the_date(); ?></span>
            </div>
            <div class="adt-property-location-box">
                <i class="fas fa-location-arrow"></i><?php echo esc_html($truncated_location); ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}

if (!function_exists('adforest_ad_grid_4')) {
    function adforest_ad_grid_4($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location)
    {
        $author_id = get_post_field('post_author', get_the_ID());
        $author_link = get_author_posts_url($author_id);
        ob_start(); ?>
        <div class="adt-property-ad-card adt-car-ad-card">
            <div class="adt-property-img-box">
                <div class="adt-property-img-carousel owl-carousel owl-theme">
                    <?php
                    if (!empty($all_ad_images)) {
                        foreach ($all_ad_images as $ad_image) {
                            echo '<div class="item"><a href="' . esc_url($ad_permalink) . '"><img src="' . esc_url($ad_image) . '" alt="' . esc_html(get_the_title()) . '"></a></div>';
                        }
                    } else {
                        $default_image_url = esc_url(get_template_directory_uri()) . '/images/no-image.jpg';
                        echo '<div class="item"><a href="' . esc_url($ad_permalink) . '"><img src="' . esc_url($default_image_url) . '" alt="' . esc_html(get_the_title()) . '"></a></div>';
                    }
                    ?>
                </div>
                <div class="video_icon_container"><?php echo adforest_video_icon() ?></div>
                <div class="favorite-box">
                    <?php
                    $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                    $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                    $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                    ?>
                    <a href="javascript:void(0);" class="favorite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>" data-adid="<?php echo get_the_ID(); ?>"
                       data-toggle="tooltip" data-placement="top"
                       title="<?php echo esc_attr( $fav_title_local ); ?>"
                       aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
                        <i class="<?php echo esc_attr($heart_class); ?>"></i>
                    </a>
                </div>
                <?php echo wp_kses($featured_tag, ADFOREST_ALLOWED_FORM_HTML); ?>
                <div class="author-box">
                    <a href="<?php echo esc_url($author_link) ?>"><img src="<?php echo esc_url($ad_poster_img); ?>"
                                                                       alt="author-img"></a>
                    <a href="<?php echo esc_url($author_link) ?>"><span><?php echo esc_html($ad_poster_name); ?></span></a>
                </div>
            </div>
            <div class="adt-property-content-box">
                <?php if (!empty($ad_type)) { ?>
                    <span class="ctg-tag used"><?php echo esc_html($ad_type); ?></span>
                <?php } ?>
                <a href="<?php echo esc_url($ad_permalink); ?>">
                    <h3><?php echo esc_html($ad_title); ?></h3>
                </a>
                <span class="publish-date"><i class="far fa-calendar-minus"></i><?php echo get_the_date(); ?></span>
                <?php
                global $adforest_theme;
                $pid = get_the_ID();
                if (function_exists('adforest_comments_pagination2')) {
                    $page = (isset($_GET['page-number'])) ? $_GET['page-number'] : 1;
                } else {
                    $page = (get_query_var('page')) ? get_query_var('page') : 1;
                }

                $limit = $adforest_theme['sb_rating_max'];
                $offset = ($page * $limit) - $limit;
                $args = array(
                    'type__in' => array('ad_post_rating'),
                    'number' => $limit,
                    'offset' => $offset,
                    'parent' => 0,
                    'post_id' => $pid,
                );

                $comments = get_comments($args);
                $get_percentage = adforest_fetch_reviews_average($pid);
                ?>
                <div class="rating">
                    <span><?php echo esc_html(isset($get_percentage['average']) ? $get_percentage['average'] : 0); ?></span>
                    <small><?php echo is_array($comments) ? count($comments) : 0 . __(" Reviews", 'adforest'); ?></small>
                    <?php
                    if (isset($get_percentage) && count($get_percentage['ratings']) > 0) {
                        echo adforest_return_echo($get_percentage['total_stars']);
                    } else {
                        echo '<i class="far fa-star" aria-hidden="true"></i>
                                  <i class="far fa-star" aria-hidden="true"></i>
                                  <i class="far fa-star" aria-hidden="true"></i>
                                  <i class="far fa-star" aria-hidden="true"></i>
                                  <i class="far fa-star" aria-hidden="true"></i>';
                    }
                    ?>
                </div>
            </div>
            <div class="adt-property-location-box">
                <i class="fas fa-location-arrow"></i><?php echo esc_html($truncated_location); ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}

if (!function_exists("adforest_remove_expired_packages")) {
    function adforest_remove_expired_packages($user_id = "")
    {
        if($user_id == "") {
            $user_id = get_current_user_id();
        }
        $selected_categories = get_user_meta($user_id, 'adforest_ads_package_details', true);

        if (is_array($selected_categories) && count($selected_categories) > 0) {
            foreach ($selected_categories as $package_id => $details) {
                if (isset($details['pkg_expiry_days']) && $details['pkg_expiry_days'] != '-1' && strtotime($details['pkg_expiry_days']) < current_time('timestamp')) {
                    unset($selected_categories[$package_id]);
                }
            }

            update_user_meta($user_id, 'adforest_ads_package_details', $selected_categories);
        }
    }
}

if (!function_exists('is_recently_viewed_ad_post')) {
    function is_recently_viewed_ad_post($post_id)
    {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $recently_viewed = get_user_meta($user_id, 'recently_viewed_ad_posts', true);

            if ($recently_viewed && is_array($recently_viewed)) {
                return in_array($post_id, $recently_viewed);
            }
        }
        return false;
    }
}

if (!function_exists('adforest_recently_viewed_ad')) {
    function adforest_recently_viewed_ad($pid)
    { ?>
       
        <div role="alert" class="alert alert-info alert-dismissible">
			 <i class="fa fa-info-circle"></i>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            <?php echo __('Ad Recently Viewed', 'adforest'); ?>
        </div>
    <?php }
}

if (!function_exists('adforest_get_ad_posted_date')) {
    function adforest_get_ad_posted_date($posted_at_timestamp)
    {
        $current_timestamp = current_time('timestamp');
        $time_diff = $current_timestamp - $posted_at_timestamp;

        $days_diff = floor($time_diff / (60 * 60 * 24));

        if ($days_diff > 15) {
            return sprintf(
                __('Posted on %s', 'adforest'),
                date_i18n('d F Y', $posted_at_timestamp)
            );
        }

        if ($days_diff >= 1) {
            return sprintf(
                __('Posted %s ago', 'adforest'),
                sprintf(
                    _n('%d day', '%d days', $days_diff, 'adforest'),
                    $days_diff
                )
            );
        }

        $hours_diff = floor($time_diff / (60 * 60));
        if ($hours_diff >= 1) {
            return sprintf(
                __('Posted %s ago', 'adforest'),
                sprintf(
                    _n('%d hour', '%d hours', $hours_diff, 'adforest'),
                    $hours_diff
                )
            );
        }

        $minutes_diff = floor($time_diff / 60);
        if ($minutes_diff >= 1) {
            return sprintf(
                __('Posted %s ago', 'adforest'),
                sprintf(
                    _n('%d minute', '%d minutes', $minutes_diff, 'adforest'),
                    $minutes_diff
                )
            );
        }

        return __('Posted just now', 'adforest');
    }
}

if (!function_exists('adforest_owner_text_callback')) {
    function adforest_owner_text_callback($phone_number = '')
    {
        global $adforest_theme;
        $owner_deal_text = isset($adforest_theme['owner_deal_text']) && !empty($adforest_theme['owner_deal_text']) ? $adforest_theme['owner_deal_text'] : '';

        if (!empty($owner_deal_text)) {
            echo '<div class="adforest-owner-text">' . adforest_return_echo($owner_deal_text) . '</div>';
        }
    }

    add_action('adforest_owner_text', 'adforest_owner_text_callback');
}

add_action('template_redirect', 'adforest_record_profile_view');
if (!function_exists('adforest_record_profile_view')) {
    function adforest_record_profile_view()
    {
        if (!is_author()) {
            return;
        }

        $author_id = get_queried_object_id();
        $today = current_time('Y-m-d');

        $views = get_user_meta($author_id, 'daily_profile_views', true);
        if (!is_array($views)) {
            $views = [];
        }

        if (isset($views[$today])) {
            $views[$today]++;
        } else {
            $views[$today] = 1;
        }

        update_user_meta($author_id, 'daily_profile_views', $views);
    }
}

add_action('template_redirect', function () {
    if (!is_singular('ad_post')) {
        return;
    }
    $post_id = get_queried_object_id();
    $today = current_time('Y-m-d');

    $views = get_post_meta($post_id, 'daily_ad_post_views', true);
    if (!is_array($views)) {
        $views = [];
    }

    if (isset($views[$today])) {
        $views[$today]++;
    } else {
        $views[$today] = 1;
    }

    update_post_meta($post_id, 'daily_ad_post_views', $views);
});

if (!function_exists('adforest_get_message_count')) {
    function adforest_get_message_count()
    {
        global $wpdb;
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return 0;
        }

        // Count only unread CHAT messages — the badge sits on the
        // "Messages" menu item, so it must reflect actual unread
        // conversations. Theme notifications (ad-approved, favorite-added,
        // etc.) belong to a separate counter and were inflating this one.
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sb_chat_messages
             WHERE receiver_id = %d AND read_status = 0",
            $current_user_id
        ));
    }
}

if (!function_exists('adforest_video_icon')) {
    function adforest_video_icon($class = 'play-video', $icon_class = 'fa fa-play-circle')
    {
        global $adforest_theme;
        if (isset($adforest_theme['sb_video_icon']) && $adforest_theme['sb_video_icon'] && get_post_meta(get_the_ID(), '_adforest_ad_yvideo', true)) {
            return '<a href="' . get_post_meta(get_the_ID(), '_adforest_ad_yvideo', true) . '" class="' . esc_attr($class) . '" target="_blank"><i class="' . $icon_class . '"></i></a>';
        } else {
            return "";
        }
    }
}

if (!function_exists('adforest_handle_mark_all_read')) {
    function adforest_handle_mark_all_read()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'adforest_mark_all_read')) {
            wp_send_json_error(esc_html__('Invalid nonce', 'adforest'));
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(esc_html__('Not logged in', 'adforest'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sb_chat_messages';

        $updated = $wpdb->update(
            $table,
            array('read_status' => 1),
            array('receiver_id' => $user_id,
                'read_status' => 0),
            array('%d'),
            array('%d', '%d')
        );

        if ($updated === false) {
            wp_send_json_error(esc_html('DB error', 'adforest'));
        }

        $theme_updated = 0;
        if (function_exists('adforest_mark_all_theme_notifications_read')) {
            $theme_updated = adforest_mark_all_theme_notifications_read($user_id);
        }

        wp_send_json_success(array(
            'updated'        => intval($updated),
            'theme_updated'  => intval($theme_updated),
        ));
    }
}

add_action('wp_ajax_adforest_mark_all_read', 'adforest_handle_mark_all_read');

add_action('wp_ajax_search_taxonomy_terms', 'search_taxonomy_terms_callback');
add_action('wp_ajax_nopriv_search_taxonomy_terms', 'search_taxonomy_terms_callback');

function search_taxonomy_terms_callback() {
    check_ajax_referer('adforest_search_taxonomy_terms_nonce', 'security');
    $taxonomy = sanitize_text_field($_GET['taxonomy'] ?? '');
    $search   = sanitize_text_field($_GET['q'] ?? '');

    if (!$taxonomy || strlen($search) < 3) {
        wp_send_json([]);
    }

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'search'     => $search,
    ]);

    $results = [];
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            // Build full hierarchy label (Parent > Child > Subchild)
            $name = $term->name;
            $parent_id = $term->parent;
            while ($parent_id) {
                $parent = get_term($parent_id, $taxonomy);
                if ($parent && !is_wp_error($parent)) {
                    $name = $parent->name . " > " . $name;
                    $parent_id = $parent->parent;
                } else {
                    $parent_id = 0;
                }
            }
            $results[] = [
                'id'   => $term->term_id,
                'text' => $name,
            ];
        }
    }

    wp_send_json($results);
}

/* -------------------------------------------------------------------------- */
/* Location selector helpers                                                  */
/* -------------------------------------------------------------------------- */

if (!function_exists('adforest_get_location_default_image_url')) {
    /**
     * Returns the fallback flag/image used for the location selector.
     *
     * @return string
     */
    function adforest_get_location_default_image_url()
    {
        $default = trailingslashit(get_template_directory_uri()) . 'images/global.png';

        return esc_url_raw(apply_filters('adforest_location_default_image', $default));
    }
}

if (!function_exists('adforest_get_location_image_url')) {
    /**
     * Fetches the dedicated image for a location term or falls back to the default.
     *
     * @param int $term_id
     *
     * @return string
     */
    function adforest_get_location_image_url($term_id)
    {
        $term_id   = absint($term_id);

        if ($term_id) {
            $image_url = '';

            if (function_exists('adforest_taxonomy_image_url')) {
                $image_url = adforest_taxonomy_image_url($term_id, 'adforest-single-small-50', false);
            }

            if (empty($image_url)) {
                $image_id = get_term_meta($term_id, 'taxonomy_image', true);
                if ($image_id) {
                    if (is_numeric($image_id)) {
                        $image_src = wp_get_attachment_image_src((int) $image_id, 'adforest-single-small-50');
                        if (is_array($image_src) && !empty($image_src[0])) {
                            $image_url = $image_src[0];
                        }
                    } elseif (filter_var($image_id, FILTER_VALIDATE_URL)) {
                        $image_url = $image_id;
                    }
                }
            }

            if (!empty($image_url)) {
                return esc_url_raw($image_url);
            }
        }

        return adforest_get_location_default_image_url();
    }
}

if (!function_exists('adforest_get_location_cookie_value')) {
    /**
     * Retrieves the raw cookie value that stores the selected site location.
     *
     * @return string
     */
    function adforest_get_location_cookie_value()
    {
        global $adforest_theme;

        $enabled = isset($adforest_theme['sb_top_location']) && $adforest_theme['sb_top_location'] === '1';
        if (!$enabled) {
            if (isset($_COOKIE['adforest_site_location_id'])) {
                setcookie('adforest_site_location_id', '', time() - HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false);
                unset($_COOKIE['adforest_site_location_id']);
            }
            return '';
        }

        if (!isset($_COOKIE['adforest_site_location_id'])) {
            return '';
        }

        $raw_value = wp_unslash($_COOKIE['adforest_site_location_id']);

        return sanitize_text_field($raw_value);
    }
}

if (!function_exists('adforest_get_selected_site_location_id')) {
    /**
     * Returns the sanitized and validated term ID of the selected location.
     *
     * @return int
     */
    function adforest_get_selected_site_location_id()
    {
        global $adforest_theme;

        $feature_enabled = isset($adforest_theme['sb_top_location']) && $adforest_theme['sb_top_location'] === '1';
        if (!$feature_enabled) {
            return 0;
        }

        $default_location_id = 0;
        if (isset($adforest_theme['sb_top_location_default']) && $adforest_theme['sb_top_location_default'] !== '') {
            $default_location_id = absint($adforest_theme['sb_top_location_default']);
        }

        $raw_value = adforest_get_location_cookie_value();

        if ($raw_value === '') {
            if ($default_location_id) {
                $default_term = get_term($default_location_id, 'ad_country');
                if ($default_term && !is_wp_error($default_term)) {
                    return (int) $default_term->term_id;
                }
            }

            return 0;
        }

        if ('all' === $raw_value) {
            return 0;
        }

        $term_id = absint($raw_value);

        if (!$term_id) {
            if ($default_location_id) {
                $default_term = get_term($default_location_id, 'ad_country');
                if ($default_term && !is_wp_error($default_term)) {
                    return (int) $default_term->term_id;
                }
            }

            return 0;
        }

        $term = get_term($term_id, 'ad_country');
        if (!$term || is_wp_error($term)) {
            if ($default_location_id) {
                $default_term = get_term($default_location_id, 'ad_country');
                if ($default_term && !is_wp_error($default_term)) {
                    return (int) $default_term->term_id;
                }
            }

            return 0;
        }

        return (int) $term->term_id;
    }
}

if (!function_exists('adforest_get_location_selector_data')) {
    /**
     * Builds the data structure needed to render the location selector UI.
     *
     * @return array
     */
    function adforest_get_location_selector_data()
    {
        global $adforest_theme;

        $enabled  = isset($adforest_theme['sb_top_location']) && $adforest_theme['sb_top_location'] === '1';
        $list     = $adforest_theme['sb_top_location_list'] ?? [];
        $default_location_id = 0;
        if (isset($adforest_theme['sb_top_location_default']) && $adforest_theme['sb_top_location_default'] !== '') {
            $default_location_id = absint($adforest_theme['sb_top_location_default']);
        }

        if (!$enabled) {
            return [];
        }

        if (!is_array($list)) {
            $list = [];
        }

        $list = array_filter(array_map('absint', $list));
        if ($default_location_id) {
            $list[] = $default_location_id;
        }

        $list = array_values(array_unique($list));

        if (empty($list)) {
            return [];
        }

        $default_image   = adforest_get_location_default_image_url();
        $raw_value       = adforest_get_location_cookie_value();
        $is_cookie_empty = ($raw_value === '');
        $is_cookie_all   = ('all' === $raw_value);

        $preferred_term_id = 0;
        if (!$is_cookie_empty && !$is_cookie_all) {
            $preferred_term_id = absint($raw_value);
        }

        $selected = [
            'id'       => 'all',
            'term_id'  => 0,
            'slug'     => '',
            'name'     => esc_html__('All Locations', 'adforest'),
            'image'    => $default_image,
            'count'    => 0,
            'is_all'   => true,
        ];

        $selected_term = null;
        if ($preferred_term_id) {
            $candidate = get_term($preferred_term_id, 'ad_country');
            if ($candidate && !is_wp_error($candidate)) {
                $selected_term = $candidate;
            } elseif ($default_location_id) {
                $fallback = get_term($default_location_id, 'ad_country');
                if ($fallback && !is_wp_error($fallback)) {
                    $selected_term = $fallback;
                }
            }
        } elseif (!$is_cookie_all && $default_location_id) {
            $fallback = get_term($default_location_id, 'ad_country');
            if ($fallback && !is_wp_error($fallback)) {
                $selected_term = $fallback;
            }
        }

        if ($selected_term) {
            $selected = [
                'id'       => (string) $selected_term->term_id,
                'term_id'  => (int) $selected_term->term_id,
                'slug'     => $selected_term->slug,
                'name'     => $selected_term->name,
                'image'    => adforest_get_location_image_url($selected_term->term_id),
                'count'    => (int) $selected_term->count,
                'is_all'   => false,
            ];
            $raw_value = (string) $selected_term->term_id;
        } else {
            $raw_value = 'all';
        }

        $items   = [];
        $items[] = [
            'id'       => 'all',
            'term_id'  => 0,
            'slug'     => '',
            'name'     => esc_html__('All Locations', 'adforest'),
            'image'    => $default_image,
            'count'    => 0,
            'active'   => ('all' === $raw_value),
            'is_all'   => true,
        ];

        foreach ($list as $term_id) {
            $term = get_term(absint($term_id), 'ad_country');
            if (!$term || is_wp_error($term)) {
                continue;
            }

            $items[] = [
                'id'       => (string) $term->term_id,
                'term_id'  => (int) $term->term_id,
                'slug'     => $term->slug,
                'name'     => $term->name,
                'image'    => adforest_get_location_image_url($term->term_id),
                'count'    => (int) $term->count,
                'active'   => ((string) $term->term_id === $raw_value),
                'is_all'   => false,
            ];
        }

        $data = [
            'selected'  => $selected,
            'raw_value' => $raw_value,
            'items'     => $items,
        ];

        return apply_filters('adforest_location_selector_data', $data);
    }
}

if (!function_exists('adforest_filter_ads_by_selected_location')) {
    /**
     * Applies the selected top-bar location to the main ad queries.
     *
     * @param WP_Query $query
     *
     * @return void
     */
    function adforest_filter_ads_by_selected_location($query)
    {
        if (!($query instanceof \WP_Query)) {
            return;
        }

        if (is_admin()) {
            return;
        }

        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if ($query->get('adforest_disable_location_filter')) {
            return;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        $location_id = adforest_get_selected_site_location_id();
        if (!$location_id) {
            return;
        }

        if (isset($_GET['country_id']) && $_GET['country_id'] !== '') {
            return;
        }

        $post_type = $query->get('post_type');
        $should_filter = false;

        if (empty($post_type)) {
            if ($query->is_main_query() && ($query->is_post_type_archive('ad_post') || $query->is_tax(['ad_country', 'ad_cats', 'ad_tags']) || $query->is_search())) {
                $should_filter = true;
            }
        } elseif (is_array($post_type)) {
            if (in_array('ad_post', $post_type, true)) {
                $should_filter = true;
            }
        } else {
            if ('ad_post' === $post_type) {
                $should_filter = true;
            }
        }

        if (!$should_filter) {
            return;
        }

        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = [];
        }

        if (!empty($tax_query) && !isset($tax_query['relation'])) {
            $tax_query['relation'] = 'AND';
        }

        $already_applied = false;
        foreach ($tax_query as $tax_condition) {
            if (!is_array($tax_condition)) {
                continue;
            }

            if (isset($tax_condition['taxonomy']) && 'ad_country' === $tax_condition['taxonomy']) {
                $already_applied = true;
                break;
            }
        }

        if (!$already_applied) {
            $tax_query[] = [
                'taxonomy' => 'ad_country',
                'field'    => 'term_id',
                'terms'    => [$location_id],
            ];
        }

        $query->set('tax_query', $tax_query);
    }
}
add_action('pre_get_posts', 'adforest_filter_ads_by_selected_location', 20);

/**
 * Render one of the new modern listing-card layouts.
 *
 * Used by `adforest_render_ads_in_search()` (Search 2.0 Part 3). Picks the
 * card template by Grid Type slug, prefers a child-theme override via
 * `locate_template()`, and exposes the data bag to the template via
 * `extract()` — templates do NOT run their own queries.
 *
 * Adding new layouts is a one-line change to the `$map` here plus a new
 * file in `template-parts/layouts/search/cards/`. Existing classic layouts
 * (`simple` / `with_labels` / `modern`) are not affected.
 *
 * @param string $layout_key One of `modern_card`, `compact_grid`, `list_view`.
 * @param array  $data       Per-listing variables (ad_permalink, first_img, etc.)
 */
if ( ! function_exists( 'adforest_load_search_card' ) ) {
    function adforest_load_search_card( $layout_key, $data ) {
        $map = apply_filters( 'adforest_search_card_map', array(
            'modern_card'  => 'card-modern.php',
            'compact_grid' => 'card-compact.php',
            'list_view'    => 'card-list.php',
        ) );
        if ( ! isset( $map[ $layout_key ] ) ) {
            return;
        }

        $rel_path = 'template-parts/layouts/search/cards/' . $map[ $layout_key ];
        $template = locate_template( $rel_path );
        if ( ! $template ) {
            $template = trailingslashit( get_template_directory() ) . $rel_path;
        }
        if ( ! file_exists( $template ) ) {
            return;
        }

        // EXTR_SKIP — never overwrite an already-defined symbol in this scope.
        if ( is_array( $data ) ) {
            extract( $data, EXTR_SKIP );
        }
        include $template;
    }
}
