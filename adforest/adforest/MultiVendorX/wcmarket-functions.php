<?php


add_filter('adforest_vendor_dashboard_profile', 'adforest_vendor_dashboard_profile_callback', 10, 2);
if (!function_exists('adforest_vendor_dashboard_profile_callback')) {
    function adforest_vendor_dashboard_profile_callback($html = '', $user_id = '')
    {
        if (is_plugin_active('dc-woocommerce-multi-vendor/dc_product_vendor.php') && $user_id != '') {
            $user = get_userdata($user_id);
            if ($user->roles[0] == 'dc_vendor') {
                global $adforest_theme;
                $vendor_dashboard = isset($adforest_theme['sb_vendor_dashboard_page'][0]) ? $adforest_theme['sb_vendor_dashboard_page'][0] : '';
                if (isset($vendor_dashboard) && $vendor_dashboard != '') {
                    $page_link = esc_url(get_permalink($vendor_dashboard));
                    $html = '<li><a href="' . $page_link . '"><i class="fa fa-user"></i>' . __("Vendor Dashboard", "adforest") . '</a></li>';
                }
            }
            return $html;
        }
    }
}

/* Send Email to Vendor/store Owner */
add_action('wp_ajax_sb_send_email_to_store_vendor', 'adforest_sb_send_email_to_store_vendor_func');
add_action('wp_ajax_nopriv_sb_send_email_to_store_vendor', 'adforest_sb_send_email_to_store_vendor_func');
if (!function_exists('adforest_sb_send_email_to_store_vendor_func')) {

    function adforest_sb_send_email_to_store_vendor_func() {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_vendor_email_nonce')) {
            echo '0|' . __("Security check failed.", "adforest");
            die();
        }

        global $get_mvx_global_settings, $adforest_theme;
        $params = array();
        parse_str(sanitize_text_field($_POST['sb_data']), $params);
        $u_name = sanitize_text_field($params['u_name']);
        $u_email = sanitize_email($params['u_email']);
        $u_subject = sanitize_text_field($params['u_subject']);
        $u_mesage = sanitize_textarea_field($params['u_mesage']);
        $vendor_id = absint($_POST['vendor_id']);
        if ($vendor_id == '' || $vendor_id == 0) {
            echo '0|' . __("Something went wrong.", "adforest");
            die();
        }

        if (isset($adforest_theme['sb_email_template_to_vendor_desc']) && $adforest_theme['sb_email_template_to_vendor_desc'] != "" && isset($adforest_theme['sb_email_template_to_vendor_from']) && $adforest_theme['sb_email_template_to_vendor_from'] != "") {
            $vendor = get_mvx_vendor($vendor_id);
            $store_title = apply_filters('wcmp_vendor_lists_single_button_text', $vendor->page_title);
            $store_link = esc_url($vendor->get_permalink());
            $vendor_email = isset($vendor->user_data->user_email) ? $vendor->user_data->user_email : '';
            $vendor_info = get_userdata($vendor_id);
            $store_owner = $vendor_info->display_name;
            $to = $vendor_email;
            /* $subject = $adforest_theme['sb_email_template_seller_widget_subject']; */
            $from = $adforest_theme['sb_email_template_to_vendor_from'];
            $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");

            $msg_keywords = array(
                '%sender_name%',
                '%sender_email%',
                '%sender_subject%',
                '%sender_message%',
                '%store_title%',
                '%store_link%',
                '%store_owner%'
            );
            $msg_replaces = array(
                $u_name,
                $u_email,
                $u_subject,
                $u_mesage,
                $store_title,
                $store_link,
                $store_owner
            );
            $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_email_template_to_vendor_desc']);
            $subject_keywords = array('%site_name%', '%store_title%', '%store_owner%');
            $subject_replaces = array(get_bloginfo('name'), $store_title, $store_owner);

            $subject = str_replace($subject_keywords, $subject_replaces, $adforest_theme['sb_email_template_to_vendor_subject']);
            $res = wp_mail($to, $subject, $body, $headers);

            if ($res) {
                echo '1|' . __("Message has been sent.", "adforest");
            } else {
                echo '0|' . __("Message not sent, please try later.", "adforest");
            }
            die();
        }
    }

}


/*
 * add link under profile for
 * vendor dashboard.
 */
add_filter('adforest_vendor_dashboard_profile', 'adforest_vendor_dashboard_profile_callback', 10, 2);
if (!function_exists('adforest_vendor_dashboard_profile_callback')) {
    function adforest_vendor_dashboard_profile_callback($html = '', $user_id = '')
    {
        if (is_plugin_active('dc-woocommerce-multi-vendor/dc_product_vendor.php') && $user_id != '') {            
            $user = get_userdata($user_id);
            if (isset($user->roles[0]) && $user->roles[0] == 'dc_vendor') {
                global $adforest_theme;
                $vendor_dashboard = isset($adforest_theme['sb_vendor_dashboard_page'][0]) ? $adforest_theme['sb_vendor_dashboard_page'][0] : '';
                if (isset($vendor_dashboard) && $vendor_dashboard != '') {
                    $page_link = esc_url(get_permalink($vendor_dashboard));
                    $html = '<li><a href="' . $page_link . '">' . __("Vendor Dashboard", "adforest") . '</a></li>';
                }
            }
            return $html;
        }
    }
}
/*========================
 * ajax vendor
 * favourites/un-favourites
 =========================*/
add_action('wp_ajax_vendor_fav_ad', 'adforest_vendor_fav_ad');
add_action('wp_ajax_nopriv_vendor_fav_ad', 'adforest_vendor_fav_ad');
if (!function_exists('adforest_vendor_fav_ad')) {

    function adforest_vendor_fav_ad()
    {
        adforest_authenticate_check();

        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_vendor_fav_nonce')) {
            echo '0|' . __("Security check failed.", "adforest");
            die();
        }

        $vendor_id = absint($_POST['vendor_id']);
        $status_code = sanitize_text_field($_POST['status_code']);

        if ($status_code == "true") {
            update_user_meta(get_current_user_id(), '_vendor_fav_id_' . $vendor_id, $vendor_id);
            echo '1|' . __("Vendor Added to your favourites.", 'adforest');
        } else {
            if (delete_user_meta(get_current_user_id(), '_vendor_fav_id_' . $vendor_id)) {
                echo '0|' . __("Vendor removed to your favourites.", 'adforest');
            }
        }
        die();
    }
}
/*
 * add button for applying as a vendor
 */
add_filter('adforest_vendor_role_assign_button', 'adforest_vendor_role_assign', 10, 2);
if (!function_exists('adforest_vendor_role_assign')) {
    function adforest_vendor_role_assign($html = '', $user_id = '')
    {
        $role_user = wp_get_current_user();
        $vendor_btn_class = '';
        $vendor_btn_text = __('Request as Vendor', 'adforest');

        // MVX (dc-woocommerce-multi-vendor) provides get_mvx_global_settings().
        // When the plugin is inactive the theme still runs this filter via the
        // dashboard, so guard the call to avoid a fatal "Call to undefined
        // function" error on the user profile page.
        if (!function_exists('get_mvx_global_settings') || !function_exists('is_plugin_active') || !is_plugin_active('dc-woocommerce-multi-vendor/dc_product_vendor.php')) {
            return $html;
        }

        if ($role_user != '') {
            global $get_mvx_global_settings;
            //print_r($get_mvx_global_settings);

            $is_approve_manually = get_mvx_global_settings('approve_vendor_manually');
            //$is_approve_manually = $WCMp->vendor_caps->vendor_general_settings('approve_vendor_manually');

            if (in_array('dc_pending_vendor', (array)$role_user->roles)) {
                $vendor_btn_text = __('Pending Vendor', 'adforest');
                $html = '<button type="button" id="" class="btn btn-primary" disabled="">' . $vendor_btn_text . '</button>';
            } else {
                $html = '<button type="button" id="role_as_vendor" data-user_id="' . $user_id . '" data-vendor_approve="' . $is_approve_manually . '" class="btn btn-primary">' . $vendor_btn_text . '</button>';
            }
        }
        if ($user_id != '' && $role_user->roles[0] != 'dc_vendor' && !current_user_can('administrator')) {
            return $html;
        }
    }
}
/*
 * assign new role to user
 * as a vendor
 */
add_action('wp_ajax_sb_change_role_user_to_vendor', 'sb_change_role_user_to_vendor');
if (!function_exists('sb_change_role_user_to_vendor')) {
    function sb_change_role_user_to_vendor()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_vendor_role_nonce')) {
            echo __('Security check failed.', 'adforest');
            die();
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo __('You must be logged in.', 'adforest');
            die();
        }

        global $MVX;
        //print_r($MVX);
        $user_id = absint($_POST['user_id']);
        $vendor_approve = sanitize_text_field($_POST['vendor_approve']);

        // Verify the user can only change their own role (not admin)
        if ($user_id != get_current_user_id()) {
            echo __('Unauthorized action.', 'adforest');
            die();
        }
        if ($user_id != '') {

            if (!function_exists('get_mvx_global_settings')) {
                echo __('Vendor feature is not available right now.', 'adforest');
                die();
            }
            $is_approve_manually  =  get_mvx_global_settings('approve_vendor');
            $user = new WP_User($user_id);
            if ($is_approve_manually == "manually") {
                $user = new WP_User(absint($user_id));
                // $user->set_role('dc_pending_vendor');
                $user->set_role('dc_pending_vendor');
                $user->add_role('subscriber');  
                if(function_exists('sb_send_email_to_pending_vendor_callback')){
                     $email_pending_send = sb_send_email_to_pending_vendor_callback($user);
                }
                if(function_exists('sb_send_email_on_new_vendor_register')){
                    $email_admin_send = sb_send_email_on_new_vendor_register($user);
                }
                echo __('Currently your request is in pending.', 'adforest');
            } else {
                //$user->set_role('dc_vendor');
                $user->set_role('dc_vendor');
                $user->add_role('subscriber');
                 if(function_exists('sb_send_email_on_new_vendor_register')){
                    $email_admin_send = sb_send_email_on_new_vendor_register($user);
                }
                echo __('Now your role is vendor.', 'adforest');
            }
        } else {
            echo __('Something went wrong.', 'adforest');
        }
        die();
    }
}
/*
 * Email send to vendor
 * when status is pending
 */
if (!function_exists('sb_send_email_to_pending_vendor_callback')) {
    function sb_send_email_to_pending_vendor_callback($vendor_obj = '')
    {
        if ($vendor_obj != '') {
            global $get_mvx_global_settings, $adforest_theme;
            if (isset($adforest_theme['sb_vendor_pending_email_message']) && $adforest_theme['sb_vendor_pending_email_message'] != "" && isset($adforest_theme['sb_vendor_pending_email_from']) && $adforest_theme['sb_vendor_pending_email_from'] != "") {

                $subject = $adforest_theme['sb_vendor_pending_email_subject'];
                $to = $vendor_obj->user_email;
                /* $subject = $adforest_theme['sb_email_template_seller_widget_subject']; */
                $from = isset($adforest_theme['sb_vendor_pending_email_from']) ? $adforest_theme['sb_vendor_pending_email_from'] : get_option('admin_email');
                $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from", "Reply-To: <$to>");
                $msg_keywords = array('%site_name%', '%vendor_name%', '%vendor_email%');
                $msg_replaces = array(get_bloginfo('name'), $vendor_obj->display_name, $vendor_obj->user_email);
                $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_vendor_pending_email_message']);
                wp_mail($to, $subject, $body, $headers);
            }
        }
    }
}
/*
 * Email send to admin on
 * register as a vendor
 */
if (!function_exists('sb_send_email_on_new_vendor_callback')) {
    function sb_send_email_on_new_vendor_register($vendor_obj = '')
    {
        if ($vendor_obj != '') {
            global $get_mvx_global_settings, $adforest_theme;
            if (isset($adforest_theme['sb_new_vendor_admin_message']) && $adforest_theme['sb_new_vendor_admin_message'] != "" && isset($adforest_theme['sb_new_vendor_admin_message_from']) && $adforest_theme['sb_new_vendor_admin_message_from'] != "") {

                $subject = $adforest_theme['sb_new_vendor_admin_message_subject'];
                $to = get_option('admin_email');
                /* $subject = $adforest_theme['sb_email_template_seller_widget_subject']; */
                $from = $vendor_obj->user_email;
                $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from", "Reply-To: <$to>");

                $msg_keywords = array('%site_name%', '%vendor_name%', '%vendor_email%');
                $msg_replaces = array(get_bloginfo('name'), $vendor_obj->display_name, $vendor_obj->user_email);

                $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_new_vendor_admin_message']);
                wp_mail($to, $subject, $body, $headers);
            }
        }
    }
}

/**==============================
 * Hide vendor dashboard menu for
 * acces to wp-admin
 * ==============================*/
add_action('init', 'blockusers_access_wpadmin');
function blockusers_access_wpadmin()
{
     $user = wp_get_current_user();
     $allowed_roles = array( 'editor', 'administrator', 'author' ,'contributor' ); 

    if ( !array_intersect( $allowed_roles, $user->roles ) && ! defined( 'DOING_AJAX' ) ) {
            add_action( 'admin_init',  'sb_dashboard_redirect');

        } else {
            return; // Bail
        }
}
    function sb_dashboard_redirect() {
        /* @global string $pagenow */
        global $pagenow;    
            wp_redirect(home_url());
            exit;
       
    }