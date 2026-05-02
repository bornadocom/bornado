<?php
if (!function_exists('adforest_user_not_logged_in')) {

    function adforest_user_not_logged_in()
    {
        global $adforest_theme;
        if (get_current_user_id() == 0) {
            $redirect_url = adforest_login_with_redirect_url_param(adforest_get_current_url());
            echo adforest_redirect($redirect_url);
            exit;
        }
    }
}

// Ajax handler for Login User
add_action('wp_ajax_sb_login_user', 'adforest_login_user');
add_action('wp_ajax_nopriv_sb_login_user', 'adforest_login_user');

if (!function_exists('adforest_login_user')) {

    function adforest_login_user()
    {
        global $adforest_theme;

        $params = array();
        parse_str(wp_unslash($_POST['sb_data']), $params);
        check_ajax_referer('sb_login_secure', 'security');
        $remember = false;
        if (isset($params['is_remember']) && $params['is_remember']) {
            $remember = true;
        }

        $google_captcha_auth = adforest_recaptcha_verify($adforest_theme['google_api_secret'], $params['g-recaptcha-response'], $_SERVER['REMOTE_ADDR'], $params['is_captcha']);
        $captcha_type = isset($adforest_theme['google-recaptcha-type']) && !empty($adforest_theme['google-recaptcha-type']) ? $adforest_theme['google-recaptcha-type'] : 'v2';
        if($google_captcha_auth) {
        } else {
            if ($captcha_type == 'v3') {
                echo __('You are spammer ! Get out.', 'adforest');
            } else {
                echo __('please verify captcha code', 'adforest');
            }
            die();
        }

        $user = get_user_by('email', $params['sb_reg_email']);
        error_log("[LOGIN DEBUG] USER: " . print_r($user, true));

        if ($user) {
            if (empty($user->roles) || count($user->roles) == 0) {
                echo __('Your account is not verified yet.', 'adforest');
                die();
            }

            $creds = array(
                'user_login'    => $user->user_login,
                'user_password' => $params['sb_reg_password'],
                'remember'      => $remember
            );

            $user = wp_signon($creds, false);

            if (!is_wp_error($user)) {
                $res = adforest_auto_login($user->user_login, $params['sb_reg_password'], $remember);
                if ($res == 1) {
                    echo "1";
                }
            } else {
                echo adforest_return_echo($user->get_error_message());
                die();
            }
        } else {
            echo __('Invalid email or password.', 'adforest');
        }
        die();
    }
}


if (!function_exists('adforest_auto_login')) {

    function adforest_auto_login($username, $password, $remember)
    {
        $creds = array();
        $creds['user_login'] = $username;
        $creds['user_password'] = $password;
        $creds['remember'] = $remember;

        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            return false;
        } else {
            //global $adforest_theme;
            //if( isset( $adforest_theme['sb_new_user_email_verification'] ) && $adforest_theme['sb_new_user_email_verification'] )
            //{
            if (count($user->roles) > 0) {
                /* ======= This code is add when we face issue vendor upload image ====== */
                $user_id = $user->ID;
                wp_set_current_user($user_id, $user->user_login);
                wp_set_auth_cookie($user_id, $remember);
                return true;
            } else {
                return 2;
            }
            //}
        }
    }
}

add_action('wp_ajax_sb_register_check_user', 'sb_register_check_user_fun');
add_action('wp_ajax_nopriv_sb_register_check_user', 'sb_register_check_user_fun');
if (!function_exists('sb_register_check_user_fun')) {

    function sb_register_check_user_fun()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_register_check_user_nonce')) {
            wp_send_json_error(esc_html__('Security check failed.', 'adforest'));
        }

        global $wpdb;

        $is_demo = adforest_is_demo();
        if ($is_demo) {
            wp_send_json_error(array("message" => esc_html__('Not allowed in demo mode', 'adforest')));
            die();
        }

        $form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : "";
        $param = array();
        parse_str($form_data, $param);
        $user_contact = isset($param['adforest_reg_number']) ? sanitize_text_field($param['adforest_reg_number']) : "";

        $query = $wpdb->prepare(
            "SELECT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = %s 
            AND meta_value = %s",
            '_sb_contact',
            $user_contact
        );

        $result = $wpdb->get_results($query);

        if (isset($result) && !empty($result)) {
            wp_send_json_error(array("message" => esc_html__('Phone Number already registered', 'adforest')));
            die();
        }

        wp_send_json_success(array("message" => esc_html__('No such user name exist', 'adforest')));
        die();
    }
}

add_action('wp_ajax_sb_google_captcha3_verification', 'adforest_google_captcha3_verification_callback');
add_action('wp_ajax_nopriv_sb_google_captcha3_verification', 'adforest_google_captcha3_verification_callback');

if (!function_exists('adforest_google_captcha3_verification_callback')) {

    function adforest_google_captcha3_verification_callback()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_google_captcha3_verification_nonce')) {
            $data_resp['success'] = false;
            $data_resp['msg'] = esc_html__('Security check failed.', 'adforest');
            echo json_encode($data_resp);
            wp_die();
        }
        global $adforest_theme;
        $google_api_secret = isset($adforest_theme['google_api_secret']) && !empty($adforest_theme['google_api_secret']) ? $adforest_theme['google_api_secret'] : '';
        $captcha = "";
        if (isset($_POST['token'])) {
            $captcha = $_POST['token'];
        }
        $secretKey = $google_api_secret;
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) . '&response=' . urlencode($captcha);
        $responseData = wp_remote_get($url);
        $data_resp = array();
        if (is_wp_error($responseData)) {
            $error_message = $responseData->get_error_message();
            $data_resp['success'] = false;
            $data_resp['msg'] = $error_message;
            $data_resp['responseData'] = $responseData;
            echo json_encode($data_resp);
            wp_die();
        } else {
            $res = json_decode($responseData['body'], true);
            if ($res["success"]) {
                $data_resp['success'] = true;
            } else {
                $data_resp['success'] = false;
                $data_resp['responseData'] = $responseData;
                $data_resp['msg'] = __('You are a spammer! Get out of here.', 'adforest');
            }
            echo json_encode($data_resp);
            wp_die();
        }
    }
}

add_action('wp_ajax_sb_register_user', 'adforest_register_user');
add_action('wp_ajax_nopriv_sb_register_user', 'adforest_register_user');
if (!function_exists('adforest_register_user')) {

    function adforest_register_user()
    {
        global $adforest_theme;
        global $wpdb;
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo esc_html__('Not allowed in demo mode', 'adforest');
            die();
        }
        // Getting values
        $params = array();
        parse_str(wp_unslash($_POST['sb_data']), $params);
        check_ajax_referer('sb_register_secure', 'security');
        if (email_exists($params['sb_reg_email']) == false) {
            if (isset($params['sb_reg_contact']) && $params['sb_reg_contact'] != "") {
                $user_contact = isset($params['sb_reg_contact']) ? sanitize_text_field($params['sb_reg_contact']) : '';

                $query_user = $wpdb->prepare(
                    "SELECT user_id 
                    FROM {$wpdb->usermeta} 
                    WHERE meta_key = %s 
                    AND meta_value = %s",
                    '_sb_contact',
                    $user_contact
                );

                $result = $wpdb->get_results($query_user);
                if (isset($result) && !empty($result)) {
                    echo __('Phone Number already registered', 'adforest');
                    die();
                }
            }
            $is_captcha = $params['is_captcha'];

            $google_captcha_auth = false;

            //$grecaptchta_rsp = isset($params['g-recaptcha-response']) && $params['g-recaptcha-response'] != '' ? $params['g-recaptcha-response'] : '';
            //if($grecaptchta_rsp != ''){
            $google_captcha_auth = adforest_recaptcha_verify($adforest_theme['google_api_secret'], $params['g-recaptcha-response'], $_SERVER['REMOTE_ADDR'], $params['is_captcha']);
            //}

            $captcha_type = isset($adforest_theme['google-recaptcha-type']) && !empty($adforest_theme['google-recaptcha-type']) ? $adforest_theme['google-recaptcha-type'] : 'v2';

            if ($google_captcha_auth) {

                $user_name = explode('@', $params['sb_reg_email']);

                $other_errors = adforest_before_register_new_user($user_name[0], $params['sb_reg_email']);

                if ($other_errors) {
                    echo adforest_return_echo($other_errors);
                    die();
                }
                $u_name = adforest_check_user_name($user_name[0]);
                $uid = wp_create_user($u_name, $params['sb_reg_password'], sanitize_email($params['sb_reg_email']));

                if (isset($adforest_theme['subscriber_checkbox_on_register']) && $adforest_theme['subscriber_checkbox_on_register'] == true) {
                    if (isset($params['minimal-subscriber-1'])) {
                        do_action('adforest_subscribe_newsletter_on_regisster', $adforest_theme, $uid);
                    }
                } else {
                    do_action('adforest_subscribe_newsletter_on_regisster', $adforest_theme, $uid);
                }
                $display_name = isset($params['sb_reg_name']) ? sanitize_text_field($params['sb_reg_name']) : $u_name;

                wp_update_user(array('ID' => $uid, 'display_name' => $display_name));
                $contact_number = isset($params['sb_reg_contact']) ? sanitize_text_field($params['sb_reg_contact']) : "";
                update_user_meta($uid, '_sb_contact', $params['sb_reg_contact']);

                if ($adforest_theme['sb_allow_pkg_on_reg'] == '1') {
                    $sb_allow_pkg_on_reg = true;
                    $package_to_assign = $adforest_theme['sb_register_package'];
                    if (isset($package_to_assign) && !empty($package_to_assign)) {
                        if (function_exists('adforest_give_user_package_from_admin')) {
                            adforest_give_user_package_from_admin($package_to_assign, $uid, $sb_allow_pkg_on_reg);
                        }
                    }
                }
                // Email for new user
                if (function_exists('adforest_email_on_new_user')) {
                    adforest_email_on_new_user($uid, '');
                }
                // check phone verification is on or not
                $sms_gateway = adforest_verify_sms_gateway();
                if ($sms_gateway != "") {
                    update_user_meta($uid, '_sb_is_ph_verified', '0');
                }

                $user_role = $adforest_theme['sb_user_role_on_registeration'] ?? 'none';
                if ($user_role == 'dealer') {
                    update_user_meta($uid, '_sb_user_type', 'Dealer');
                } else if($user_role == 'individual') {
                    update_user_meta($uid, '_sb_user_type', 'Indiviual');
                }

                if (isset($adforest_theme['sb_new_user_email_verification']) && $adforest_theme['sb_new_user_email_verification']) {
                    $user = new WP_User($uid);
                    foreach ($user->roles as $role) {
                        $user->remove_role($role);
                    }
                    echo 2;
                    die();
                } else if ($adforest_theme['sb_admin_approve_user'] && $adforest_theme['sb_admin_approve_user']) {

                    $user = new WP_User($uid);
                    // Remove all user roles after registration
                    foreach ($user->roles as $role) {
                        $user->remove_role($role);
                    }
                    echo 3;
                    die();
                } else {
                    adforest_auto_login($params['sb_reg_email'], $params['sb_reg_password'], true);
                    echo 1;
                    die();
                }
            } else {
                if ($captcha_type == 'v3') {
                    echo __('You are spammer ! Get out.', 'adforest');
                } else {
                    echo __('please verify captcha code', 'adforest');
                }
                die();
            }
        } else {
            echo __('Email already exist, please try other one.', 'adforest');
            die();
        }
        die();
    }
}

if (!function_exists('adforest_before_register_new_user')) {
    function adforest_before_register_new_user($user_login, $user_email)
    {
        $errors = new WP_Error();
        $sanitized_user_login = sanitize_user($user_login);
        $user_email = apply_filters('user_registration_email', $user_email);
        do_action('register_post', $sanitized_user_login, $user_email, $errors);
        $errors = apply_filters('registration_errors', $errors, $sanitized_user_login, $user_email);
        if ($errors->has_errors()) {
            return $errors->get_error_message();
        }
    }
}

if (!function_exists('adforest_check_user_name')) {

    function adforest_check_user_name($username = '')
    {
        if (username_exists($username)) {
            $random = mt_rand();
            $username = $username . '-' . $random;
            adforest_check_user_name($username);
        }
        return $username;
    }
}

add_action('wp_ajax_upload_ad_images', 'adforest_upload_ad_images');
if (!function_exists('adforest_upload_ad_images')) {
    function adforest_upload_ad_images()
    {
        global $adforest_theme;

        check_ajax_referer('sb_upload_ad_images_nonce', 'nonce');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }

        adforest_authenticate_check();

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        if (!isset($_FILES['my_file_upload']) || $_FILES['my_file_upload']['error'] !== UPLOAD_ERR_OK) {
            echo '0|' . __("File upload failed. Please try again.", 'adforest');
            die();
        }

        $file = $_FILES['my_file_upload'];
        $file_tmp_path = $file['tmp_name'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $file_parts = explode('.', $file_name);
        $file_extension = strtolower(end($file_parts));
        
        if (!in_array($file_extension, $allowed_extensions, true)) {
            echo '0|' . __("Sorry, only JPG, JPEG, PNG & GIF files are allowed.", 'adforest');
            die();
        }

        $allowed_mime_types = array(
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        );
        
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_mime = finfo_file($finfo, $file_tmp_path);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $detected_mime = mime_content_type($file_tmp_path);
        } else {
            $detected_mime = '';
        }

        if ($detected_mime && !in_array($detected_mime, $allowed_mime_types, true)) {
            echo '0|' . __("Invalid file type detected. Only image files are allowed.", 'adforest');
            die();
        }

        $image_info = @getimagesize($file_tmp_path);
        
        if ($image_info === false) {
            echo '0|' . __("The uploaded file is not a valid image.", 'adforest');
            die();
        }
        
        $image_mime = $image_info['mime'];
        if (!in_array($image_mime, $allowed_mime_types, true)) {
            echo '0|' . __("Image format validation failed.", 'adforest');
            die();
        }

        if (isset($adforest_theme['sb_standard_images_size']) && $adforest_theme['sb_standard_images_size']) {
            $width = $image_info[0];
            $height = $image_info[1];
            
            if ($width < 760 || $height < 410) {
                echo '0|' . __("Minimum image dimension should be 760x410", 'adforest');
                die();
            }
        }

        $size_arr = explode('-', $adforest_theme['sb_upload_size']);
        $display_size = $size_arr[1];
        $actual_size = $size_arr[0];

        if ($file_size > $actual_size) {
            echo '0|' . __("Max allowed image size is", 'adforest') . " " . $display_size;
            die();
        }

        $safe_filename = sanitize_file_name($file_name);
        $_FILES['my_file_upload']['name'] = $safe_filename;

        if (isset($_GET['is_update']) && $_GET['is_update'] != "") {
            $ad_id = absint($_GET['is_update']);
        } else {
            $ad_id = get_user_meta(get_current_user_id(), 'ad_in_progress', true);
        }

        if (empty($ad_id) || !is_numeric($ad_id)) {
            echo '0|' . __("Please enter title first in order to create ad.", 'adforest');
            die();
        }

        $ad_author = get_post_field('post_author', $ad_id);
        if ($ad_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
            echo '0|' . __("You don't have permission to upload images to this ad.", 'adforest');
            die();
        }

        $user_packages_images = get_user_meta(get_current_user_id(), '_sb_num_of_images', true);

        $user_upload_max_images = '';
        if (isset($user_packages_images) && $user_packages_images == '-1') {
            $user_upload_max_images = '';
        } elseif (isset($user_packages_images) && $user_packages_images > 0) {
            $user_upload_max_images = $user_packages_images;
        } else {
            $user_upload_max_images = isset($adforest_theme['sb_upload_limit']) ? $adforest_theme['sb_upload_limit'] : 10;
        }

        $media = get_attached_media('image', $ad_id);
        if ($user_upload_max_images != '' && count($media) >= $user_upload_max_images) {
            echo '0|' . __("You can not upload more than ", 'adforest') . " " . $user_upload_max_images;
            die();
        }
        
        add_filter('wp_check_filetype_and_ext', 'adforest_strict_image_upload_check', 10, 4);
        
        $attachment_id = media_handle_upload('my_file_upload', $ad_id);
        
        remove_filter('wp_check_filetype_and_ext', 'adforest_strict_image_upload_check', 10);

        if (is_wp_error($attachment_id)) {
            $error_string = $attachment_id->get_error_message();
            echo '0|' . $error_string;
            die();
        }

        $images = get_post_meta($ad_id, '_sb_photo_arrangement_', true);
        if (!empty($images)) {
            $existing_images = array_map('absint', explode(',', $images));
            $existing_images[] = $attachment_id;
            $images = implode(',', $existing_images);
        } else {
            $images = $attachment_id;
        }
        
        update_post_meta($ad_id, '_sb_photo_arrangement_', sanitize_text_field($images));

        echo adforest_return_echo($attachment_id);
        die();
    }
}

/**
 * Additional security filter for image uploads
 */
if (!function_exists('adforest_strict_image_upload_check')) {
    function adforest_strict_image_upload_check($data, $file, $filename, $mimes) {
        $wp_filetype = wp_check_filetype($filename, $mimes);
        $ext = $wp_filetype['ext'];
        $type = $wp_filetype['type'];
        
        // Only allow image types
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        
        if (!in_array($type, $allowed_types)) {
            $data['ext'] = false;
            $data['type'] = false;
        }
        
        return $data;
    }
}

//Create Ad ID for Title
add_action('wp_ajax_create_ad_id_for_title', 'adforest_create_ad_id_for_title');
if (!function_exists('adforest_create_ad_id_for_title')) {

    function adforest_create_ad_id_for_title()
    {
        check_ajax_referer('sb_create_ad_id_for_title_nonce', 'nonce');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }

        if ($_POST['is_update'] != "") {
            wp_die();
        }
        $title = sanitize_text_field($_POST['title']);
        if (get_current_user_id() == "") {
            wp_die();
        }
        if (!isset($title)) {
            wp_die();
        }
        $ad_id = get_user_meta(get_current_user_id(), 'ad_in_progress', true);
        if (get_post_status($ad_id) && $ad_id != "" && get_post_status($ad_id) != 'publish') {
            $my_post = array(
                'ID' => get_user_meta(get_current_user_id(), 'ad_in_progress', true),
                'post_title' => $title,
                'post_status' => 'private',
                'post_type' => 'ad_post',
                'post_author' => get_current_user_id(),
            );
            wp_update_post($my_post);
            wp_die();
        }
        // Gather post data.
        $my_post = array(
            'post_title' => sanitize_text_field($title),
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
            'post_type' => 'ad_post'
        );
        // Insert the post into the database.
        $id = wp_insert_post($my_post);
        if ($id) {
            update_user_meta(get_current_user_id(), 'ad_in_progress', $id);
        }
        wp_die();
    }
}

// Ajax handler for add to cart
add_action('wp_ajax_sb_add_cart', 'adforest_add_to_cart');
add_action('wp_ajax_nopriv_sb_add_cart', 'adforest_add_to_cart');
if (!function_exists('adforest_add_to_cart')) {
    function adforest_add_to_cart()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_add_cart_nonce')) {
            echo '0|' . __("Security check failed.", 'adforest');
            die();
        }
        global $adforest_theme;

        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }

        $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
        $redirect_url = adforest_login_with_redirect_url_param(get_the_permalink($sb_packages_page));
        if (get_current_user_id() == "") {
            echo '0|' . __("You need to be logged in.", 'adforest') . '|' . $redirect_url;
            die();
        }

        $product_id = (int)$_POST['product_id'];
        $qty = (int)$_POST['qty'];

        if (!isset($_POST['product_id']) || !isset($_POST['qty'])) {
            echo '0|' . __("Invalid request.", 'adforest');
            die();
        }

        if ($qty < 1) {
            echo '0|' . __("Invalid quantity.", 'adforest');
            die();
        }

        global $woocommerce;

        if ($woocommerce->cart->add_to_cart($product_id, $qty)) {
            echo '1|' . __("Added to cart.", 'adforest') . '|' . wc_get_cart_url();
        } else {
            echo '0|' . __("Already in your cart.", 'adforest') . '|' . wc_get_cart_url();
        }
        die();
    }
}

// Ad Posting...
add_action('wp_ajax_sb_ad_posting', 'adforest_ad_posting');
if (!function_exists('adforest_ad_posting')) {
    function adforest_ad_posting()
    {
        global $adforest_theme;
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo "2";
            die();
        }

        check_ajax_referer('sb_post_secure', 'security');
        adforest_set_date_timezone();
        if (get_current_user_id() == "") {
            echo "0";
            die();
        }

        // Getting values
        $params = array();
        parse_str(wp_unslash($_POST['sb_data']), $params);
        $cats = array();

        // Extract categories
        $selected_categories = array_filter(array(
            $params['ad_post_category_select'] ?? null,
            $params['ad_post_child_category_select_1'] ?? null,
            $params['ad_post_child_category_select_2'] ?? null,
            $params['ad_post_child_category_select_3'] ?? null,
            $params['ad_post_child_category_select_4'] ?? null,
            $params['ad_post_child_category_select_5'] ?? null,
            $params['ad_post_child_category_select_6'] ?? null,
        ));

        /*  Package Details Starts  */
        $pay_per_post = isset($adforest_theme['sb_pay_per_post_option']) ? $adforest_theme['sb_pay_per_post_option'] : false;
        $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
        $package_single = array();
        
        if (isset($pay_per_post) && $pay_per_post != 1) {
            if (!isset($params['ads_package']) || $params['ads_package'] == "") {
                if (!isset($_POST['is_update'])) {
                    echo "10";
                    die();
                }
            }
            
            if (isset($params['ads_package']) && $params['ads_package'] != "" && $params['ads_package'] != 0) {
                $params_ads_package = $params['ads_package'];
            } else {
                // Check if user has any packages with available ads
                $has_available_ads = false;
                if (is_array($packageDetails) && !empty($packageDetails)) {
                    foreach ($packageDetails as $package_id => $package_data) {
                        if (isset($package_data['free_ads']) && ($package_data['free_ads'] > 0 || $package_data['free_ads'] == '-1')) {
                            $has_available_ads = true;
                            break;
                        }
                    }
                }
                
                if (!$has_available_ads) {
                    if (!isset($_POST['is_update'])) {
                        echo "1";
                        die();
                    }
                }
            }

            if (isset($params['ads_package']) && $params['ads_package'] != "" && $params['ads_package'] != 0) {
                $params_ads_package = $params['ads_package'];
                if (isset($packageDetails[$params_ads_package])) {
                    $package_single = $packageDetails[$params_ads_package];
                } else {
                    // Check if user has any packages with available ads
                    $has_available_ads = false;
                    if (is_array($packageDetails) && !empty($packageDetails)) {
                        foreach ($packageDetails as $package_id => $package_data) {
                            if (isset($package_data['free_ads']) && ($package_data['free_ads'] > 0 || $package_data['free_ads'] == '-1')) {
                                $has_available_ads = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$has_available_ads) {
                        if (!isset($_POST['is_update'])) {
                            echo "1";
                            die();
                        }
                    }
                }
            }

            if ($params['ads_package'] == 0 && $_POST['is_update'] == "") {
                // Check if user has any packages with available ads
                $has_available_ads = false;
                if (is_array($packageDetails) && !empty($packageDetails)) {
                    foreach ($packageDetails as $package_id => $package_data) {
                        if (isset($package_data['free_ads']) && ($package_data['free_ads'] > 0 || $package_data['free_ads'] == '-1')) {
                            $has_available_ads = true;
                            break;
                        }
                    }
                }
                
                if(isset($adforest_theme['adforest_ad_posting_mode']) && $adforest_theme['adforest_ad_posting_mode'] == 'paid_post') {
                    if (!$has_available_ads) {
                        echo 'no_ads';
                        die();
                    }
                }
            }

            $simple_ads = isset($package_single['free_ads']) ? $package_single['free_ads'] : '';
            $featured_ad = isset($package_single['featured_ads']) ? $package_single['featured_ads'] : '';
            $bump_ads = isset($package_single['bump_ads']) ? $package_single['bump_ads'] : '';
            $expiry = isset($package_single['pkg_expiry_days']) ? $package_single['pkg_expiry_days'] : '';
            $_sb_allow_bidding = isset($package_single['allow_bidding']) ? $package_single['allow_bidding'] : '';
            $ad_expiry_days = isset($package_single['ad_expiry_days']) ? $package_single['ad_expiry_days'] : '';
            $featured_expiry_days = isset($package_single['featured_expiry_days']) ? $package_single['featured_expiry_days'] : '';
            $video_links = isset($package_single['video_links']) ? $package_single['video_links'] : '';
            $num_of_images = isset($package_single['num_of_images']) ? $package_single['num_of_images'] : '';
            $allow_tags = isset($package_single['allow_tags']) ? $package_single['allow_tags'] : '';
            $number_of_events = isset($package_single['number_of_events']) ? $package_single['number_of_events'] : '';
            $paid_biddings = isset($package_single['paid_biddings']) ? $package_single['paid_biddings'] : '';
        }
        /*  Package Details Ends  */

        if (!is_super_admin(get_current_user_id()) && $_POST['is_update'] == "") {

            if ($pay_per_post != 1 && !empty($package_single)) {
                $simple_ads = isset($package_single['free_ads']) ? $package_single['free_ads'] : '';
                $expiry = isset($package_single['pkg_expiry_days']) ? $package_single['pkg_expiry_days'] : '';
                
                if ($simple_ads == -1) {
                    // Unlimited ads
                } else if ($simple_ads <= 0) {
                    echo "1";
                    die();
                }

                if ($expiry != '-1') {
                    if ($expiry < current_time('Y-m-d')) {
                        echo "1";
                        die();
                    }
                }
            }
        }

        $selected_parent_cat = $params['ad_post_category_select'] ?? null;
        $product_ids = get_product_ids_by_meta_query($selected_parent_cat);

        // PPP Free quota computation
        $ppp_enabled = isset($adforest_theme['sb_pay_per_post_option']) ? (bool)$adforest_theme['sb_pay_per_post_option'] : false;
        $ppp_free_toggle = isset($adforest_theme['enable_free_ads_with_ppp']) ? (bool)$adforest_theme['enable_free_ads_with_ppp'] : false;
        $user_free_remaining = (int)get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true);
        if ($ppp_enabled && $ppp_free_toggle) {
            if ($user_free_remaining === 0 && get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true) === '') {
                $initial_quota = isset($adforest_theme['ppp_free_ads_quota']) ? intval($adforest_theme['ppp_free_ads_quota']) : 0;
                update_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', max(0, $initial_quota));
                $user_free_remaining = (int)get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true);
            }
        }
        $can_use_ppp_free = ($ppp_enabled && $ppp_free_toggle && empty($_POST['is_update']) && $user_free_remaining > 0);

        if (isset($params['ad_contact_email']) && $params['ad_contact_email'] != "") {
            $user_provided_email = $params['ad_contact_email'];
            $user_data = wp_update_user(array('ID' => get_current_user_id(), 'user_email' => $user_provided_email));
        }

        $admin_img_required = isset($adforest_theme['sb_default_img_required']) ? $adforest_theme['sb_default_img_required'] : false;

        $ad_status = 'publish';
        if (isset($pay_per_post) && $pay_per_post == 1 && !empty($product_ids) && $_POST['is_update'] == '' && !$can_use_ppp_free) {
            $ad_status = 'pending';
        }
        $sb_form_is_custom = isset($params['sb_form_is_custom']) && $params['sb_form_is_custom'] == 'no' ? true : FALSE; // get ad post template type
        $sb_default_img_required = isset($adforest_theme['sb_default_img_required']) ? $adforest_theme['sb_default_img_required'] : false; // get image req or not in default template ad post

        if ($_POST['is_update'] != "") {
            $pid = $_POST['is_update'];
            if ($adforest_theme['sb_update_approval'] == 'manual') {
                $ad_status = 'pending';
            } else if (get_post_status($pid) == 'pending' || get_post_status($pid) == 'rejected') {
                $ad_status = 'pending';
            }
            $stored_ad_status = get_post_meta($pid, '_adforest_ad_status_', true);

            if (get_post_status($pid) == 'draft' || $stored_ad_status == 'sold' || $stored_ad_status == 'expired') {
                $ad_status = 'draft';
            }

            if (!$sb_form_is_custom) {
                $is_imageallow = adforestCustomFieldsVals($pid, $cats);
            }

            $media = get_attached_media('image', $pid);
            if ($sb_default_img_required && $sb_form_is_custom) {
                $is_imageallow = 1;
            }

            if ($is_imageallow == 1 && count($media) == 0) {
                echo "img_req";
                wp_die();
            }

            if ($ad_status == 'pending') {
                adforest_get_notify_on_ad_post($pid, true);
            }
        } else {
            if ($adforest_theme['sb_ad_approval'] == 'manual') {
                $ad_status = 'pending';
            }
            $pid = get_user_meta(get_current_user_id(), 'ad_in_progress', true);

            $is_imageallow = false;
            if (!$sb_form_is_custom) {
                $is_imageallow = adforestCustomFieldsVals($pid, $cats);
            }
            $media = get_attached_media('image', $pid);
            if ($sb_default_img_required && $sb_form_is_custom) {
                $is_imageallow = 1;
            }
            if ($is_imageallow == 1 && count($media) == 0) {
                echo "img_req";
                wp_die();
            }

            // Now user can post new ad

            // exit;
            delete_user_meta(get_current_user_id(), 'ad_in_progress');
            if ($pay_per_post != true && !empty($package_single) && $_POST['is_update'] == "") {
                $simple_ads = isset($package_single['free_ads']) ? $package_single['free_ads'] : '';
                if ($simple_ads > 0 && !is_super_admin(get_current_user_id())) {
                    $params_ads_package = $params['ads_package'];
                    $simple_ads = $simple_ads - 1;
                    $package_single['free_ads'] = $simple_ads;
                    $packageDetails[$params_ads_package] = $package_single;
                    update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                }
                
                $_sb_allow_bidding = isset($package_single['allow_bidding']) ? $package_single['allow_bidding'] : '';
                if (isset($_sb_allow_bidding) && $_sb_allow_bidding > 0 && !is_super_admin(get_current_user_id()) && $params['ad_bidding'] == 1) {
                    $params_ads_package = $params['ads_package'];
                    $_sb_allow_bidding = $_sb_allow_bidding - 1;
                    $package_single['allow_bidding'] = $_sb_allow_bidding;
                    $packageDetails[$params_ads_package] = $package_single;
                    update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                }
            }

            // Consume PPP free quota on new ad if eligible
            if ($can_use_ppp_free) {
                $remaining = max(0, ((int)get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true)) - 1);
                update_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', $remaining);
            }

            update_post_meta($pid, '_adforest_ad_status_', 'active');
            update_post_meta($pid, '_adforest_is_feature', '0');
            adforest_get_notify_on_ad_post($pid);
        }

        global $wpdb;

        $qry = $wpdb->prepare(
            "UPDATE {$wpdb->postmeta}
            SET meta_value = ''
            WHERE post_id = %d
            AND meta_key LIKE %s",
            $pid,
            '_adforest_tpl_field_%'
        );

        $wpdb->query($qry);
        $words = explode(',', $adforest_theme['bad_words_filter']);
        $replace = $adforest_theme['bad_words_replace'];
        $desc = adforest_badwords_filter($words, $params['ad_description'], $replace);
        $title = adforest_badwords_filter($words, $params['ad_title'], $replace);

        $desc = wp_kses_post($desc);
        $desc = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $desc);

        $desc = preg_replace('/<img[^>]*>/', '', $desc);

        $sb_trusted_user = get_user_meta(get_current_user_id(), '_sb_trusted_user', true);
        $ad_status = ($sb_trusted_user == 1) ? 'publish' : $ad_status;

        if ($_POST['is_update'] != "") {
            $my_post = array(
                'ID' => $pid,
                'post_title' => sanitize_text_field($title),
                'post_status' => $ad_status,
                'post_content' => $desc,
                'post_name' => sanitize_text_field($title),
                'post_type' => 'ad_post'
            );
        } else {
            $my_post = array(
                'ID' => $pid,
                'post_title' => sanitize_text_field($title),
                'post_status' => $ad_status,
                'post_content' => $desc,
                'post_name' => sanitize_text_field($title),
                'post_date' => current_time('mysql'),
                'post_type' => 'ad_post',
                'post_author' => get_current_user_id()
            );
        }

        $ad_post_id = wp_update_post($my_post);

        if (!is_wp_error($ad_post_id) && empty($_POST['is_update']) && function_exists('adforest_add_ad_post_notification')) {
            adforest_add_ad_post_notification($ad_post_id, 'submitted');
        }

        wp_set_post_terms($pid, $selected_categories, 'ad_cats');

        /* countries */
        $countries = array();
        if (isset($params['ad_country']) && $params['ad_country'] != "") {
            $countries[] = $params['ad_country'];
        }
        if (isset($params['ad_country_states']) && $params['ad_country_states'] != "") {
            $countries[] = $params['ad_country_states'];
        }
        if (isset($params['ad_country_cities']) && $params['ad_country_cities'] != "") {
            $countries[] = $params['ad_country_cities'];
        }
        if (isset($params['ad_country_towns']) && $params['ad_country_towns'] != "") {
            $countries[] = $params['ad_country_towns'];
        }

        wp_set_post_terms($pid, $countries, 'ad_country');

        // setting taxonomoies selected
        $type = '';
        if (isset($params['ad_type']) && $params['ad_type'] != "") {
            $type_arr = explode('|', $params['ad_type']);
            wp_set_post_terms($pid, $type_arr[0], 'ad_type');
            $type = $type_arr[1];
        }
        $condition = '';
        if (isset($params['ad_condition']) && $params['ad_condition'] != "") {
            $condition_arr = explode('|', $params['ad_condition']);
            wp_set_post_terms($pid, $condition_arr[0], 'ad_condition');
            $condition = $condition_arr[1];
        }
        $warranty = '';
        if (isset($params['ad_warranty']) && $params['ad_warranty'] != "") {
            $warranty_arr = explode('|', $params['ad_warranty']);
            wp_set_post_terms($pid, $warranty_arr[0], 'ad_warranty');
            $warranty = $warranty_arr[1];
        }

        $currency = '';
        if (isset($params['ad_currency']) && $params['ad_currency'] != "") {
            $currency_arr = explode('|', $params['ad_currency']);
            wp_set_post_terms($pid, $currency_arr[0], 'ad_currency');
            $currency = $currency_arr[1];
            update_post_meta($pid, '_adforest_ad_currency', sanitize_text_field($currency));
        }

        $tags = explode(',', $params['tags']);
        wp_set_object_terms($pid, $tags, 'ad_tags');

        // Update post meta
        $theme_ad_bidding_date = (isset($params['ad_bidding']) && $params['ad_bidding'] == 1) ? $params['ad_bidding_date'] : '';
        $sb_user_name = isset($params['sb_user_name']) && $params['sb_user_name'] != '' ? $params['sb_user_name'] : '';
        $ad_contact_number = isset($params['ad_contact_number']) && $params['ad_contact_number'] != '' ? $params['ad_contact_number'] : '';
        $ad_address = isset($params['ad_address']) && $params['ad_address'] != '' ? $params['ad_address'] : '';
        $ad_tagline = isset($params['ad_tagline']) && $params['ad_tagline'] != '' ? $params['ad_tagline'] : '';
        $ad_price = isset($params['ad_price']) && $params['ad_price'] != '' ? $params['ad_price'] : '';
        $ad_website = isset($params['ad_website']) && $params['ad_website'] != '' ? $params['ad_website'] : '';
        $ad_map_lat = isset($params['ad_map_lat']) && $params['ad_map_lat'] != '' ? $params['ad_map_lat'] : '';
        $ad_map_long = isset($params['ad_map_long']) && $params['ad_map_long'] != '' ? $params['ad_map_long'] : '';
        $ad_bidding = isset($params['ad_bidding']) && $params['ad_bidding'] != '' ? $params['ad_bidding'] : '';
        $ad_price_from = isset($params['ad_price_from']) && $params['ad_price_from'] != '' ? $params['ad_price_from'] : '';
        $ad_price_to = isset($params['ad_price_to']) && $params['ad_price_to'] != '' ? $params['ad_price_to'] : '';

        $ad_post_price_type = isset($params['ad_post_price_type']) && $params['ad_post_price_type'] != '' ? $params['ad_post_price_type'] : '';

        update_post_meta($pid, '_adforest_poster_name', sanitize_text_field($sb_user_name));
        update_post_meta($pid, '_adforest_poster_contact', sanitize_text_field($ad_contact_number));
        update_post_meta($pid, '_adforest_ad_location', sanitize_text_field($ad_address));
        update_post_meta($pid, '_adforest_ad_tagline', sanitize_text_field($ad_tagline));
        update_post_meta($pid, '_adforest_ad_type', sanitize_text_field($type));
        update_post_meta($pid, '_adforest_ad_condition', sanitize_text_field($condition));
        update_post_meta($pid, '_adforest_ad_warranty', sanitize_text_field($warranty));
        update_post_meta($pid, '_adforest_ad_price', sanitize_text_field($ad_price));
        update_post_meta($pid, '_adforest_ad_price_from', sanitize_text_field($ad_price_from));
        update_post_meta($pid, '_adforest_ad_price_to', sanitize_text_field($ad_price_to));
        update_post_meta($pid, '_adforest_ad_map_lat', sanitize_text_field($ad_map_lat));
        update_post_meta($pid, '_adforest_ad_map_long', sanitize_text_field($ad_map_long));
        update_post_meta($pid, '_adforest_ad_bidding', sanitize_text_field($ad_bidding));
        update_post_meta($pid, '_adforest_ad_price_type', sanitize_text_field($ad_post_price_type));
        update_post_meta($pid, '_adforest_ad_bidding_date', sanitize_text_field($theme_ad_bidding_date));
        update_post_meta($pid, '_adforest_ad_website', sanitize_text_field($ad_website));
        update_post_meta($pid, '_adforest_ad_post_package', $params['ads_package']);

        if (isset($params['ad_yvideo']) && $params['ad_yvideo'] != "") {
            update_post_meta($pid, '_adforest_ad_yvideo', sanitize_text_field($params['ad_yvideo']));
        } else {
            update_post_meta($pid, '_adforest_ad_yvideo', '');
        }

        $featured_marked = false;
        if (isset($params['sb_make_it_feature']) && $params['sb_make_it_feature']) {
            $featured_ad = isset($package_single['featured_ads']) ? $package_single['featured_ads'] : '';
            if ($featured_ad > 0 || $featured_ad == '-1') {
                update_post_meta($pid, '_adforest_is_feature', '1');
                update_post_meta($pid, '_adforest_is_feature_date', current_time('Y-m-d'));
                $old_featured_count = $featured_ad;
                if ($old_featured_count == '-1') {
                    $new_featured_count = '-1';
                } elseif ($old_featured_count > 0) {
                    $params_ads_package = $params['ads_package'];
                    $package_single['featured_ads'] = $old_featured_count - 1;
                    $packageDetails[$params_ads_package] = $package_single;
                    update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                }
                $package_adFeatured_expiry_days = get_user_meta(get_current_user_id(), 'package_adFeatured_expiry_days', true);
                if ($package_adFeatured_expiry_days) {
                    update_post_meta($pid, 'package_adFeatured_expiry_days', $package_adFeatured_expiry_days);
                }
                $featured_marked = true;
            } else {
                // Check if user has any packages with featured ads
                $has_featured_ads = false;
                if (is_array($packageDetails) && !empty($packageDetails)) {
                    foreach ($packageDetails as $package_id => $package_data) {
                        if (isset($package_data['featured_ads']) && ($package_data['featured_ads'] > 0 || $package_data['featured_ads'] == '-1')) {
                            $has_featured_ads = true;
                            $package_single = $package_data;
                            $params_ads_package = $package_id;
                            break;
                        }
                    }
                }
                
                if ($has_featured_ads) {
                    update_post_meta($pid, '_adforest_is_feature', '1');
                    update_post_meta($pid, '_adforest_is_feature_date', current_time('Y-m-d'));
                    $old_featured_count = $package_single['featured_ads'];
                    if ($old_featured_count == '-1') {
                        $new_featured_count = '-1';
                    } elseif ($old_featured_count > 0) {
                        $package_single['featured_ads'] = $old_featured_count - 1;
                        $packageDetails[$params_ads_package] = $package_single;
                        update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                    }
                    $package_adFeatured_expiry_days = get_user_meta(get_current_user_id(), 'package_adFeatured_expiry_days', true);
                    if ($package_adFeatured_expiry_days) {
                        update_post_meta($pid, 'package_adFeatured_expiry_days', $package_adFeatured_expiry_days);
                    }
                }
                if ($has_featured_ads) {
                    $featured_marked = true;
                }
            }

            if (isset($params['ads_package']) && $params['ads_package'] != 0) {
                $selectedPackage_Id = $params['ads_package'];
                $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
                $selectedPackage = $packageDetails[$selectedPackage_Id];
                $selectedPackageFeaturedExpiry = $selectedPackage['featured_expiry_days'];
                if (isset($selectedPackageFeaturedExpiry)) {
                    update_post_meta($pid, 'package_adFeatured_expiry_days', $selectedPackageFeaturedExpiry);
                }
            }
        }

        if ($featured_marked && function_exists('adforest_add_ad_post_notification')) {
            adforest_add_ad_post_notification($pid, 'featured');
        }

        // Bumping up ads
        $bump_performed = false;
        if (isset($params['sb_bump_up']) && $params['sb_bump_up']) {
            // Check if user has bump ads in the selected package
            $bump_ads = isset($package_single['bump_ads']) ? $package_single['bump_ads'] : 0;
            
            if ($bump_ads <= 0 || $bump_ads == "") {
                wp_update_post(
                    array(
                        'ID' => $pid,
                        'post_date' => current_time('mysql'),
                        'post_type' => 'ad_post',
                        'post_author' => get_current_user_id(),
                    )
                );
                do_action('adforest_wpml_bumpup_ads', $pid);
                if (!$adforest_theme['sb_allow_free_bump_up'] && $bump_ads != '-1') {
                    $package_single['bump_ads'] = $bump_ads - 1;
                    $packageDetails[$params_ads_package] = $package_single;
                    update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                }
                $bump_performed = true;
            } else {
                if ($bump_ads > 0 || $bump_ads == '-1' || (isset($adforest_theme['sb_allow_free_bump_up']) && $adforest_theme['sb_allow_free_bump_up'])) {
                    wp_update_post(
                        array(
                            'ID' => $pid,
                            'post_date' => current_time('mysql'),
                            'post_type' => 'ad_post',
                            'post_author' => get_current_user_id(),
                        )
                    );
                    do_action('adforest_wpml_bumpup_ads', $pid);
                    if (!$adforest_theme['sb_allow_free_bump_up'] && $bump_ads != '-1') {
                        $bump_ads = $bump_ads - 1;
                        $package_single['bump_ads'] = $bump_ads;
                        $packageDetails[$params_ads_package] = $package_single;
                        update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                    }
                    $bump_performed = true;
                }
            }
        }

        if ($bump_performed && function_exists('adforest_add_ad_post_notification')) {
            adforest_add_ad_post_notification(
                $pid,
                'bump',
                array(
                    'bump_date' => current_time('mysql'),
                    'unique'    => uniqid( 'bump_', true ),
                )
            );
        }

        //Add Dynamic Fields
        if (isset($params['cat_template_field']) && count($params['cat_template_field']) > 0) {
            foreach ($params['cat_template_field'] as $key => $data) {
                if (is_array($data)) {
                    $dataArr = array();
                    foreach ($data as $k)
                        $dataArr[] = $k;
                    $data = stripslashes(json_encode($dataArr, JSON_UNESCAPED_UNICODE));
                }
                update_post_meta($pid, $key, sanitize_text_field($data));
            }
        }

        /* Making Location DB
          explode address */
        if ($params['ad_map_lat'] == "" && $params['ad_map_long']) {
            $address = explode(',', $params['sb_user_address']);
            if (count($address) == 3) {
                $city = trim($address[0]);
                $state = trim($address[1]);
                $country = trim($address[2]);
                adforest_add_location($country, $state, $city);
            } else if (count($address) == 2) {
                $city = trim($address[0]);
                $country = trim($address[1]);
                $state = '';
                adforest_add_location($country, $state, $city);
            }
        }
        do_action('adforest_directory_fields_saving', $pid, $params); /* save directory data */
        $ad_posting_mode = isset($adforest_theme['adforest_ad_posting_mode']) ? $adforest_theme['adforest_ad_posting_mode'] : 'paid_post';

        if ($ad_posting_mode == 'free') {
            if (empty($_POST['is_update'])) {
                $free_ads_expiry_days = isset($adforest_theme['adforest_free_ad_posting_simple_ads_exipry']) ? intval($adforest_theme['adforest_free_ad_posting_simple_ads_exipry']) : 30;
                
                update_post_meta($pid, 'package_ad_expiry_days', $free_ads_expiry_days);
            }
        } else {
            if (empty($_POST['is_update'])) { 
                $pay_per_post = $adforest_theme['sb_pay_per_post_option'];
                if ($can_use_ppp_free) {
                    // Set expiry from PPP free settings
                    $ppp_free_expiry_days = isset($adforest_theme['ppp_free_ads_expiry_days']) ? intval($adforest_theme['ppp_free_ads_expiry_days']) : 30;
                    update_post_meta($pid, 'package_ad_expiry_days', $ppp_free_expiry_days);
                    do_action('adforest_duplicate_posts_lang', $pid);
                } else if (isset($pay_per_post) && empty($product_ids)) {
                    $package_ad_expiry_days = isset($package_single['pkg_expiry_days']) ? $package_single['pkg_expiry_days'] : '';
                    if ($package_ad_expiry_days) {
                        update_post_meta($pid, 'package_ad_expiry_days', $package_ad_expiry_days);
                    }
                    do_action('adforest_duplicate_posts_lang', $pid);
                }
            }

            if (isset($params['ads_package']) && $params['ads_package'] != 0) {
                $selectedPackage_Id = $params['ads_package'];
                $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
                $selectedPackage = $packageDetails[$selectedPackage_Id];
                $selectedPackageAdExpiry = $selectedPackage['ad_expiry_days'];
                if (isset($selectedPackageAdExpiry)) {
                    update_post_meta($pid, 'package_ad_expiry_days', $selectedPackageAdExpiry);
                }
            } else {
                $user_meta_simple_ad_days = get_user_meta(get_current_user_id(), 'package_ad_expiry_days', true);
                update_post_meta($pid, 'package_ad_expiry_days', $user_meta_simple_ad_days);
            }
        }

        /**
         * 0 = N/A
         * 1 = open 24/7
         * 2 = selective hours
         */
        $listing_is_open = isset($params['hours_type']) ? ($params['hours_type']) : "";
        $listing_timezone = isset($params['listing_timezome']) ? ($params['listing_timezome']) : "";
        $listing_brandname = isset($params['listing_brandname']) ? ($params['listing_brandname']) : "";
        /* checkbox for closed/not */
        $is_closed = isset($params['is_closed']) ? $params['is_closed'] : array();
        $start_from = isset($params['to']) ? $params['to'] : array();
        $end_from = isset($params['from']) ? $params['from'] : array();
        //get break hours data again input name
        $break_from = isset($params['breakfrom']) ? $params['breakfrom'] : array();
        $break_to = isset($params['breakto']) ? $params['breakto'] : array();
        $break_click = isset($params['is_break']) ? $params['is_break'] : array();

        if ($listing_is_open == '1') {
            update_post_meta($pid, 'sb_pro_is_hours_allow', '1');
            update_post_meta($pid, 'sb_pro_business_hours', $listing_is_open);
        } /* listing business hours */ else if ($listing_is_open == '2') {
            $custom_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            for ($a = 0; $a <= 6; $a++) {
                $to = '';
                $from = '';
                $days = '';
                $break_time_from = '';
                $break_time_to = '';
                //get days
                $days = lcfirst($custom_days[$a]);
                if (!in_array($a, $is_closed)) {
                    $from = date("H:i:s", strtotime(str_replace(" : ", ":", $end_from[$a])));
                    $to = date("H:i:s", strtotime(str_replace(" : ", ":", $start_from[$a])));
                    //day status open or not
                    update_post_meta($pid, '_timingz_' . $days . '_open', '1');
                    //day hours from
                    update_post_meta($pid, '_timingz_' . $days . '_from', $from);
                    update_post_meta($pid, '_timingz_' . $days . '_to', $to);
                    //break hours
                    if (in_array($a, $break_click)) {
                        $break_time_from = isset($break_from[$a]) && $break_from[$a] != "" ? date("H:i:s", strtotime(str_replace(" : ", ":", $break_from[$a]))) : "";
                        $break_time_to = isset($break_to[$a]) && $break_to[$a] != "" ? date("H:i:s", strtotime(str_replace(" : ", ":", $break_to[$a]))) : "";

                        update_post_meta($pid, '_timingz_break_' . $days . '_open', '1');
                        update_post_meta($pid, '_timingz_break_' . $days . '_breakfrom', $break_time_from);
                        update_post_meta($pid, '_timingz_break_' . $days . '_breakto', $break_time_to);
                    } else {
                        update_post_meta($pid, '_timingz_break_' . $days . '_open', '0');
                        update_post_meta($pid, '_timingz_break_' . $days . '_breakfrom', '');
                        update_post_meta($pid, '_timingz_break_' . $days . '_breakto', '');
                    }
                } else {
                    update_post_meta($pid, '_timingz_' . $days . '_open', '0');
                }
            }
            update_post_meta($pid, 'sb_pro_business_hours', 0);
            update_post_meta($pid, 'sb_pro_user_timezone', $listing_timezone);
            update_post_meta($pid, 'sb_pro_is_hours_allow', '1');
        } else {
            update_post_meta($pid, 'sb_pro_is_hours_allow', '0');
            /* add this code on 26-aug-2020(because n/a not show if user choose N/A) */
            update_post_meta($pid, 'sb_pro_business_hours', '');
        }

        if ($_POST['is_update'] != "") {
            $my_post = array(
                'ID' => $pid,
                'post_title' => sanitize_text_field($title),
                'post_status' => $ad_status,
                'post_content' => $desc,
                'post_name' => sanitize_text_field($title),
                'post_type' => 'ad_post'
            );
            wp_update_post($my_post);
        }

        // Remove the deprecated simple ads deduction logic since we're now using package details
        // The deduction is already handled in the package management section above

        if (isset($adforest_theme['sb_pay_per_post_option']) && $adforest_theme['sb_pay_per_post_option'] == 1 && $_POST['is_update'] == "" && !$can_use_ppp_free) {
            if (!empty($product_ids)) {
                $url = get_the_permalink($adforest_theme['sb_pay_per_post_template_page']);
                $redirect_url = $url . "?pid=" . $pid;
            } else {
                echo 'no_product_pay_per_post';
                die();
            }
        } else {
            $redirect_url = get_the_permalink($ad_post_id);
        }
        echo urldecode($redirect_url);
        die();
    }
}

if (!function_exists('adforestCustomFieldsVals')) {

    function adforestCustomFieldsVals($post_id = '', $terms = array())
    {
        if ($post_id == "")
            return null;
        /* $terms = wp_get_post_terms($post_id, 'ad_cats'); */
        $is_show = '';
        if (count($terms) > 0) {
            foreach ($terms as $term) {
                $term_id = $term;
                $t = adforest_dynamic_templateID($term_id);
                if ($t)
                    break;
            }
            $templateID = adforest_dynamic_templateID($term_id);
            $result = get_term_meta($templateID, '_sb_dynamic_form_fields', true);

            $is_show = '';
            $html = '';

            if (isset($result) && $result != "") {
                $is_show = sb_custom_form_data($result, '_sb_default_cat_image_required');
            }
        }
        return ($is_show == 1) ? 1 : 0;
    }
}

/* verify recaptcha */
// Goog re-capthca verification
if (!function_exists('adforest_recaptcha_verify')) {

    function adforest_recaptcha_verify($api_secret, $code, $ip, $is_captcha)
    {

        global $adforest_theme;
        $captcha_status = false;
        $captcha_type = isset($adforest_theme['google-recaptcha-type']) && !empty($adforest_theme['google-recaptcha-type']) ? $adforest_theme['google-recaptcha-type'] : 'v2';
        if ($is_captcha == 'no') {
            return true;
        }
        if ($captcha_type == 'v3') {
            return true;
        } else {
            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $api_secret . '&response=' . $code . '&remoteip=' . $ip;
            $responseData = wp_remote_get($url);
            $res = json_decode($responseData['body'], true);
            if ($res["success"] === true) {
                $captcha_status = true;
            } else {
                $captcha_status = false;
            }
        }
        return $captcha_status;
    }
}

add_action('wp_ajax_sb_register_user_with_otp', 'sb_register_user_with_otp_fun');
add_action('wp_ajax_nopriv_sb_register_user_with_otp', 'sb_register_user_with_otp_fun');

if (!function_exists('sb_register_user_with_otp_fun')) {

    function sb_register_user_with_otp_fun()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_register_user_with_otp_nonce')) {
            wp_send_json_error(array("message" => esc_html__('Security check failed.', 'adforest')));
            die();
        }
        global $wpdp;
        global $adforest_theme;

        $is_demo = adforest_is_demo();

        if ($is_demo) {
            wp_send_json_error(array("message" => esc_html__('Not allowed in demo mode', 'adforest')));
            die();
        }

        // Check if user registration is enabled
        if (!get_option('users_can_register')) {
            wp_send_json_error(array("message" => esc_html__('User registration is currently disabled.', 'adforest')));
            die();
        }

        $form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : "";
        $param = array();
        parse_str($form_data, $param);

        $random = mt_rand(0, 1000);
        // $user_name = isset($adforest_theme['sb_register_user_txt']) ? $adforest_theme['sb_register_user_txt'] : "user";
        // $user_name = $user_name . '-' . $random;

        $user_name = isset($param['adforest_reg_name']) ? $param['adforest_reg_name'] : "";

        $user_name = adforest_check_user_name($user_name);

        $contact_number = isset($param['adforest_reg_number']) ? $param['adforest_reg_number'] : "";
        if ($user_name == "") {
            wp_send_json_error(array("message" => esc_html__('Please Enter user name', 'adforest')));
            die();
        }
        if (username_exists($user_name)) {
            wp_send_json_error(array("message" => esc_html__('User Name already exist', 'adforest')));
            die();
        }
        $info = array();
        $info['user_login'] = $user_name;
        $info['user_nicename'] = $user_name;
        $info['user_pass'] = wp_generate_password(12);
        $user_id = wp_insert_user($info);
        if (is_wp_error($user_id)) {
            wp_send_json_error(array("message" => $user_id->get_error_message()));
            die();
        }


        $saved_num = update_user_meta($user_id, '_sb_contact', $contact_number);
        if (function_exists('adforest_email_on_new_user')) {
            adforest_email_on_new_user($user_id, '', false);
        }


        global $adforest_theme;
        if ($adforest_theme['sb_allow_pkg_on_reg'] == '1') {
            $sb_allow_pkg_on_reg = true;
            $package_to_assign = $adforest_theme['sb_register_package'];
            if (isset($package_to_assign) && !empty($package_to_assign)) {
                if (function_exists('adforest_give_user_package_from_admin')) {
                    adforest_give_user_package_from_admin($package_to_assign, $user_id, $sb_allow_pkg_on_reg);
                }
            }
        }

        update_user_meta($user_id, '_sb_is_ph_verified', '1');

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        $user_role = $adforest_theme['sb_user_role_on_registeration'] ?? 'none';
        if ($user_role == 'dealer') {
            update_user_meta($user_id, '_sb_user_type', 'Dealer');
        } else if($user_role == 'individual') {
            update_user_meta($user_id, '_sb_user_type', 'Indiviual');
        }
        wp_send_json_success(array("message" => esc_html__('User Registered Succesfully', 'adforest')));
    }
}

/* check if user name exist or number belong to that user */
add_action('wp_ajax_nopriv_sb_login_check_user', 'sb_login_check_user_func');

if (!function_exists('sb_login_check_user_func')) {
    function sb_login_check_user_func()
    {
        global $wpdb;

        if (adforest_is_demo()) {
            wp_send_json_error(['message' => esc_html__('Not allowed in demo mode', 'adforest')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sb_phone_login_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed', 'adforest')]);
        }

        // Sanitize IP address
        $ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ?: '0.0.0.0';
        $rate_limit_key = 'otp_request_' . md5($ip_address);
        $attempts = (int) get_transient($rate_limit_key) ?: 0;

        if ($attempts >= 5) {
            wp_send_json_error(['message' => esc_html__('Too many attempts. Please try again later.', 'adforest')]);
        }

        // Properly unslash and parse form data
        $form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : '';
        parse_str($form_data, $param);
        $user_contact = sanitize_text_field($param['sb_reg_phone'] ?? '');

        if (empty($user_contact)) {
            wp_send_json_error(['message' => esc_html__('Please enter a valid phone number.', 'adforest')]);
        }

        $query = $wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_sb_contact' AND meta_value = %s",
            $user_contact
        );
        $result = $wpdb->get_results($query);

        if (!empty($result)) {
            $user_id = (int) $result[0]->user_id;

            // Generate a 6-digit numeric OTP for user-friendly input
            $otp_code = adforest_generate_otp_code(6);

            // Hash the OTP for secure storage (prevents exposure if transient is compromised)
            $otp_hash = wp_hash($otp_code);

            // Generate unique session token for additional security
            $session_token = adforest_generate_secure_token(16);

            $token_data = array(
                'user_id'       => $user_id,
                'otp_hash'      => $otp_hash,           // Hashed OTP for secure comparison
                'session_token' => $session_token,      // Binds OTP to specific request
                'phone'         => $user_contact,
                'ip_address'    => $ip_address,         // Bind to IP for extra security
                'created_at'    => time(),
                'expires'       => time() + 300,        // 5 minutes expiration (reduced for security)
                'attempts'      => 0,                   // Track verification attempts
            );

            // Use cryptographically secure key generation
            $token_key = 'phone_login_token_' . wp_hash($user_contact . $session_token);
            set_transient($token_key, $token_data, 300); // 5 minutes

            update_user_meta($user_id, 'phone_login_token_key', $token_key);

            delete_transient($rate_limit_key);

            // Log OTP generation for security auditing (OTP itself is NOT logged)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[AdForest OTP] Generated for user_id: %d, phone: %s, ip: %s',
                    $user_id,
                    substr($user_contact, 0, -4) . '****', // Mask phone in logs
                    $ip_address
                ));
            }

            /**
             * Action hook to send OTP via SMS/WhatsApp
             *
             * @param string $user_contact Phone number
             * @param string $otp_code     The OTP code to send (plaintext)
             * @param int    $user_id      User ID
             */
            do_action('adforest_send_otp_code', $user_contact, $otp_code, $user_id);

            // Return session token to frontend (required for verification)
            wp_send_json_success([
                'message'       => esc_html__('OTP sent successfully.', 'adforest'),
                'session_token' => $session_token, // Frontend must send this back
            ]);
        } else {
            set_transient($rate_limit_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('Phone number not registered.', 'adforest')]);
        }
    }
}

if (!function_exists('adforest_generate_secure_token')) {
    function adforest_generate_secure_token($length = 32)
    {
        $bytes = openssl_random_pseudo_bytes($length);
        return bin2hex($bytes);
    }
}

/**
 * Generate a numeric OTP code for phone verification
 *
 * @param int $length Length of OTP (default 6 digits)
 * @return string Numeric OTP code
 */
if (!function_exists('adforest_generate_otp_code')) {
    function adforest_generate_otp_code($length = 6)
    {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= random_int(0, 9);
        }
        return $otp;
    }
}

/* Login user on the otp verification / by user phone number */
add_action('wp_ajax_nopriv_sb_login_user_with_otp', 'sb_login_user_with_otp_fun');

if (!function_exists('sb_login_user_with_otp_fun')) {
    /**
     * Verify OTP and log user in
     *
     * SECURITY: This function validates the OTP code against the stored hash.
     * The OTP must match exactly - no bypass is possible.
     *
     * Required POST parameters:
     * - nonce: WordPress nonce for CSRF protection
     * - phone_number: User's registered phone number
     * - otp_code: The 6-digit OTP entered by user
     * - session_token: Session token returned when OTP was generated
     */
    function sb_login_user_with_otp_fun()
    {
        global $wpdb;

        // Block demo mode
        if (adforest_is_demo()) {
            wp_send_json_error(['message' => esc_html__('Not allowed in demo mode', 'adforest')]);
        }

        // Verify WordPress nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sb_otp_nonce_phone_login')) {
            wp_send_json_error(['message' => esc_html__('Security check failed', 'adforest')]);
        }

        // Rate limiting by IP (with validation)
        $ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ?: '0.0.0.0';
        $rate_limit_key = 'otp_verify_attempts_' . md5($ip_address);
        $ip_attempts = (int) get_transient($rate_limit_key) ?: 0;

        if ($ip_attempts >= 10) {
            // Log potential brute force attempt
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[AdForest OTP SECURITY] IP blocked due to excessive attempts: %s', $ip_address));
            }
            wp_send_json_error(['message' => esc_html__('Too many attempts. Please try again in 30 minutes.', 'adforest')]);
        }

        // Sanitize and validate required inputs
        $phone_number  = sanitize_text_field($_POST['phone_number'] ?? '');
        $otp_code      = sanitize_text_field($_POST['otp_code'] ?? '');
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');

        // CRITICAL: All three parameters are required
        if (empty($phone_number)) {
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('Phone number is required.', 'adforest')]);
        }

        if (empty($otp_code)) {
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('OTP code is required.', 'adforest')]);
        }

        if (empty($session_token)) {
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('Invalid session. Please request a new OTP.', 'adforest')]);
        }

        // Validate OTP format (must be 6 digits)
        if (!preg_match('/^\d{6}$/', $otp_code)) {
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('Invalid OTP format. Please enter 6 digits.', 'adforest')]);
        }

        // Find user by phone number
        $query = $wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_sb_contact' AND meta_value = %s",
            $phone_number
        );
        $result = $wpdb->get_results($query);

        if (empty($result)) {
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('User not found.', 'adforest')]);
        }

        $user_id = (int) $result[0]->user_id;

        // Get stored token key
        $token_key = get_user_meta($user_id, 'phone_login_token_key', true);

        if (empty($token_key)) {
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('No pending verification found. Please request a new OTP.', 'adforest')]);
        }

        // Get token data from transient
        $token_data = get_transient($token_key);

        if (empty($token_data)) {
            delete_user_meta($user_id, 'phone_login_token_key');
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('Verification expired. Please request a new OTP.', 'adforest')]);
        }

        // Check expiration
        if (!isset($token_data['expires']) || $token_data['expires'] < time()) {
            delete_user_meta($user_id, 'phone_login_token_key');
            delete_transient($token_key);
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('OTP has expired. Please request a new one.', 'adforest')]);
        }

        // Rate limit per OTP token (max 3 attempts per OTP)
        $token_attempts = isset($token_data['attempts']) ? (int) $token_data['attempts'] : 0;
        if ($token_attempts >= 3) {
            delete_user_meta($user_id, 'phone_login_token_key');
            delete_transient($token_key);
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[AdForest OTP SECURITY] OTP locked after 3 failed attempts. user_id: %d, ip: %s', $user_id, $ip_address));
            }
            wp_send_json_error(['message' => esc_html__('Too many incorrect attempts. Please request a new OTP.', 'adforest')]);
        }

        // CRITICAL VALIDATION 1: Verify session token matches
        if (!isset($token_data['session_token']) || !hash_equals($token_data['session_token'], $session_token)) {
            $token_data['attempts'] = $token_attempts + 1;
            set_transient($token_key, $token_data, $token_data['expires'] - time());
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('Invalid session. Please request a new OTP.', 'adforest')]);
        }

        // CRITICAL VALIDATION 2: Verify phone number matches stored data (timing-safe)
        if (!isset($token_data['phone']) || !hash_equals($token_data['phone'], $phone_number)) {
            $token_data['attempts'] = $token_attempts + 1;
            set_transient($token_key, $token_data, $token_data['expires'] - time());
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('Phone number mismatch.', 'adforest')]);
        }

        // CRITICAL VALIDATION 3: Verify user ID matches stored data
        if (!isset($token_data['user_id']) || (int) $token_data['user_id'] !== $user_id) {
            $token_data['attempts'] = $token_attempts + 1;
            set_transient($token_key, $token_data, $token_data['expires'] - time());
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);
            wp_send_json_error(['message' => esc_html__('User verification failed.', 'adforest')]);
        }

        // CRITICAL VALIDATION 4: Verify OTP code using timing-safe comparison
        if (!isset($token_data['otp_hash'])) {
            delete_user_meta($user_id, 'phone_login_token_key');
            delete_transient($token_key);
            wp_send_json_error(['message' => esc_html__('Invalid token data. Please request a new OTP.', 'adforest')]);
        }

        // Hash the provided OTP and compare with stored hash
        $provided_otp_hash = wp_hash($otp_code);
        if (!hash_equals($token_data['otp_hash'], $provided_otp_hash)) {
            // Increment attempt counter
            $token_data['attempts'] = $token_attempts + 1;
            set_transient($token_key, $token_data, $token_data['expires'] - time());
            set_transient($rate_limit_key, $ip_attempts + 1, 30 * MINUTE_IN_SECONDS);

            // Log failed attempt
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[AdForest OTP] Failed verification attempt %d/3. user_id: %d, ip: %s',
                    $token_data['attempts'],
                    $user_id,
                    $ip_address
                ));
            }

            $remaining_attempts = 3 - $token_data['attempts'];
            if ($remaining_attempts > 0) {
                wp_send_json_error([
                    'message' => sprintf(
                        esc_html__('Incorrect OTP. %d attempts remaining.', 'adforest'),
                        $remaining_attempts
                    )
                ]);
            } else {
                wp_send_json_error(['message' => esc_html__('Incorrect OTP. Please request a new one.', 'adforest')]);
            }
        }

        // OTP VERIFIED SUCCESSFULLY - Proceed with login

        // Verify user exists
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            delete_user_meta($user_id, 'phone_login_token_key');
            delete_transient($token_key);
            wp_send_json_error(['message' => esc_html__('User not found.', 'adforest')]);
        }

        // Clean up all verification data
        delete_user_meta($user_id, 'phone_login_token_key');
        delete_transient($token_key);
        delete_transient($rate_limit_key);

        // Log successful authentication
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[AdForest OTP] Successful login. user_id: %d, phone: %s, ip: %s',
                $user_id,
                substr($phone_number, 0, -4) . '****',
                $ip_address
            ));
        }

        // Set authentication
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, false, is_ssl());

        /**
         * Action hook fired after successful OTP login
         *
         * @param int    $user_id      User ID
         * @param string $phone_number Phone number used for login
         */
        do_action('adforest_otp_login_success', $user_id, $phone_number);

        wp_send_json_success(['message' => esc_html__('You are successfully logged in', 'adforest')]);
    }
}

/**
 * Enqueue Phone OTP Login Scripts
 *
 * Loads the JavaScript required for secure OTP-based phone authentication.
 * Only loads on pages where phone login is available.
 */
add_action('wp_enqueue_scripts', 'adforest_enqueue_phone_otp_scripts');
if (!function_exists('adforest_enqueue_phone_otp_scripts')) {
    function adforest_enqueue_phone_otp_scripts()
    {
        global $adforest_theme;

        // Only load if phone verification is enabled
        if (empty($adforest_theme['sb_phone_verification'])) {
            return;
        }

        // Register and enqueue the phone OTP login script
        wp_register_script(
            'adforest-phone-otp-login',
            trailingslashit(get_template_directory_uri()) . 'assets/js/phone-otp-login.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script with AJAX URL and security nonces
        wp_localize_script('adforest-phone-otp-login', 'adforest_phone_otp', array(
            'ajaxurl'                  => admin_url('admin-ajax.php'),
            'sb_phone_login_nonce'     => wp_create_nonce('sb_phone_login_nonce'),
            'sb_otp_nonce_phone_login' => wp_create_nonce('sb_otp_nonce_phone_login'),
            'strings'                  => array(
                'sending_otp'          => esc_html__('Sending OTP...', 'adforest'),
                'send_otp'             => esc_html__('Send OTP', 'adforest'),
                'verifying'            => esc_html__('Verifying...', 'adforest'),
                'verify_login'         => esc_html__('Verify & Login', 'adforest'),
                'invalid_phone'        => esc_html__('Please enter a valid phone number.', 'adforest'),
                'invalid_otp'          => esc_html__('Please enter a valid 6-digit OTP code.', 'adforest'),
                'session_expired'      => esc_html__('Session expired. Please request a new OTP.', 'adforest'),
                'network_error'        => esc_html__('Network error. Please try again.', 'adforest'),
            ),
        ));

        wp_enqueue_script('adforest-phone-otp-login');
    }
}

add_action('wp_ajax_sb_display_phone_num', 'sb_display_phone_num_callback');
add_action('wp_ajax_nopriv_sb_display_phone_num', 'sb_display_phone_num_callback');
if (!function_exists('sb_display_phone_num_callback')) {
    function sb_display_phone_num_callback()
    {
        check_ajax_referer('sb_display_phone_num_secure', 'security');
        global $adforest_theme;

        $pid = isset($_POST['ad_id']) && $_POST['ad_id'] != '' ? $_POST['ad_id'] : 0;
        if ($pid != 0) {
            $contact_num = '';
            if ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'phone') {
                $contact_num = get_post_meta($pid, '_adforest_poster_contact', true);
                if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification']) {
                    //$contact_num = get_user_meta($poster_id, '_sb_contact', true);
                    $contact_num = get_user_meta(get_current_user_id(), '_sb_contact', true);
                    $contact_num = isset($contact_num) && $contact_num != '' ? $contact_num : '';
                    if ($contact_num == "") {
                        $contact_num = get_post_meta($pid, '_adforest_poster_contact', true);
                    }
                }
            }
            if ($contact_num != '') {
                echo '1|' . $contact_num;
                wp_die();
            } else {
                echo '0|' . __('There is no added phone number.', 'adforest');
                wp_die();
            }
        } else {
            echo '0|' . __('There is no ad id.', 'adforest');
            wp_die();
        }
    }
}

add_action('wp_ajax_sb_display_phone_num_user', 'sb_display_phone_num_user_callback');
add_action('wp_ajax_nopriv_sb_display_phone_num_user', 'sb_display_phone_num_user_callback');

/* get user number */
if (!function_exists('sb_display_phone_num_user_callback')) {

    function sb_display_phone_num_user_callback()
    {
        check_ajax_referer('sb_display_phone_num_secure', 'security');
        global $adforest_theme;
//        $is_demo = adforest_is_demo();
//        if ($is_demo) {
//            echo '0|' . __('Not allowed in demo mode', 'adforest');
//            die();
//        }

        $pid = isset($_POST['ad_id']) && $_POST['ad_id'] != '' ? $_POST['ad_id'] : 0;

        if ($pid != 0) {
            $contact_num = '';

            $contact_num = get_post_meta($pid, '_adforest_poster_contact', true);

            if ($contact_num == '') {
                $poster_id = get_post_field('post_author', $pid);
                $contact_num = get_user_meta($poster_id, '_sb_contact', true);
            }

            if ($contact_num != '') {
                echo '1|' . $contact_num;
                wp_die();
            } else {
                echo '0|' . __('There is no added phone number.', 'adforest');
                wp_die();
            }
        } else {
            echo '0|' . __('There is no Post id.', 'adforest');
            wp_die();
        }
    }
}

add_action('wp_ajax_sb_display_phone_num_profile', 'sb_display_phone_num_profile_callback');

/* get profile number */
if (!function_exists('sb_display_phone_num_profile_callback')) {
    function sb_display_phone_num_profile_callback()
    {
        if (!is_user_logged_in()) {
            echo '0|' . __('You need to login to view the phone number', 'adforest');
            wp_die();
        }
        global $adforest_theme;
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sb_phone_nonce')) {
            echo '0|' . __('Security check failed', 'adforest');
            wp_die();
        }

        $current_user_id = get_current_user_id();
        if(!$current_user_id || $current_user_id == 0) {
            echo '0|' . __('You need to login to view the phone number', 'adforest');
            wp_die();
        }

        $user_id = isset($_POST['user_id']) && $_POST['user_id'] != '' ? $_POST['user_id'] : 0;

        if ($user_id != 0) {
            $contact_num = get_user_meta($user_id, '_sb_contact', true);


            if (isset($contact_num) && $contact_num != '') {
                echo '1|' . $contact_num;
                wp_die();
            } else {
                echo '0|' . __('There is no added phone number.', 'adforest');
                wp_die();
            }
        } else {
            echo '0|' . __('Something Went Wrong', 'adforest');
            wp_die();
        }
    }
}

/* Submit bid */
add_action('wp_ajax_sb_submit_bid', 'adforest_submit_bid');
add_action('wp_ajax_nopriv_sb_submit_bid', 'adforest_submit_bid');
if (!function_exists('adforest_submit_bid')) {

    function adforest_submit_bid()
    {
        adforest_authenticate_check();
        global $adforest_theme;

        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }

        $params = array();
        parse_str(wp_unslash($_POST['sb_data']), $params);
        $ad_id = apply_filters('adforest_language_page_id', $params['ad_id'], 'ad_post');
        check_ajax_referer('sb_bidding_secure', 'security');
        adforest_set_date_timezone();
        $bid_end_date = get_post_meta($ad_id, '_adforest_ad_bidding_date', true); // '2018-03-16 14:59:00';
        if ($bid_end_date != "" && current_time('mysql') > $bid_end_date && isset($adforest_theme['bidding_timer']) && $adforest_theme['bidding_timer']) {
            echo '0|' . __('Bidding has been closed.', 'adforest');
            die();
        }

        $comments = sanitize_text_field($params['bid_comment']);
        $offer = sanitize_text_field($params['bid_amount']);
        //$ad_id = $params['ad_id'];
        $offer_by = get_current_user_id();
        $ad_author = get_post_field('post_author', $ad_id);
        if ($offer_by == $ad_author) {
            echo '0|' . __("Ad author can't post bid.", 'adforest');
            die();
        }


        $is_bidding_paid = isset($adforest_theme['sb_make_bidding_paid']) ? $adforest_theme['sb_make_bidding_paid'] : false;
        $user_paid_biddings = get_user_meta($offer_by, '_sb_paid_biddings', true);
        if ($is_bidding_paid) {
            if ($user_paid_biddings == '' || $user_paid_biddings == 0) {
                echo '0|' . __("Buy a package to post bidding against this ad", 'adforest');
                die();
            } else {
                if ($user_paid_biddings != "-1") {
                    $user_paid_biddings = $user_paid_biddings;
                    $remaining_bids = (int)$user_paid_biddings - 1;
                    update_user_meta($offer_by, '_sb_paid_biddings', $remaining_bids);
                }
            }
        }

        $bid = '';
        if ($offer == "") {
            $bid = current_time('mysql') . "_separator_" . $comments . "_separator_" . $offer_by;
        } else {
            $bid = current_time('mysql') . "_separator_" . $comments . "_separator_" . $offer_by . "_separator_" . $offer;
        }
        if (isset($adforest_theme['sb_email_on_new_bid_on']) && $adforest_theme['sb_email_on_new_bid_on']) {
            adforest_send_email_new_bid($offer_by, $ad_author, $offer, $comments, $ad_id);
        }
        $is_exist = get_post_meta($ad_id, "_adforest_bid_" . $offer_by, true);
        if ($is_exist != "") {
            update_post_meta($ad_id, "_adforest_bid_" . $offer_by, $bid);
            do_action('adforest_wpml_bid_translation', $ad_id, $offer_by, $bid);
            if (function_exists('adforest_add_ad_post_notification')) {
                adforest_add_ad_post_notification(
                    $ad_id,
                    'bid',
                    array(
                        'bidder_id' => $offer_by,
                        'amount'    => $offer,
                    )
                );
            }
            echo '1|' . __("Updated successfully.", 'adforest');
        } else {
            update_post_meta($ad_id, "_adforest_bid_" . $offer_by, $bid);
            do_action('adforest_wpml_bid_translation', $ad_id, $offer_by, $bid);
            if (function_exists('adforest_add_ad_post_notification')) {
                adforest_add_ad_post_notification(
                    $ad_id,
                    'bid',
                    array(
                        'bidder_id' => $offer_by,
                        'amount'    => $offer,
                    )
                );
            }
            echo '1|' . __("Posted successfully.", 'adforest');
        }
        die();
    }
}

/* Ad rating With Image */
function handle_ad_rating_form_submission()
{
    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_ad_rating_nonce')) {
        echo '0|' . __("Security check failed.", 'adforest');
        die();
    }
    $is_demo = adforest_is_demo();
    if ($is_demo) {
        echo '0|' . esc_html__('Not allowed in demo mode', 'adforest');
        die();
    }
    global $adforest_theme;
    adforest_set_date_timezone();
    $sb_update_rating = isset($adforest_theme['sb_update_rating']) && $adforest_theme['sb_update_rating'] ? TRUE : FALSE;
    $sender_id = get_current_user_id();

    if (get_current_user_id() == "" || get_current_user_id() == 0) {
        echo '0|' . __("You are not logged in.", 'adforest');
        die();
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'sb_ad_rating') {
            $params = array();
            parse_str(wp_unslash($_POST['formdata']), $params);
            $rated_id = get_user_meta($sender_id, 'ad_ratting_' . $sender_id . "_" . $params['ad_id'], true);
            $rated_already_flag = FALSE;
            if ($params['ad_id'] != '' && $params['ad_id'] == $rated_id) {
                $rated_already_flag = TRUE;
            }

            $sender = get_userdata($sender_id);

            if ($sender_id == $params['ad_owner']) {
                echo '0|' . __("Ad author can't post rating.", 'adforest');
                die();
            }

            if ($rated_already_flag && !$sb_update_rating) {
                echo '0|' . __("You've posted rating already.", 'adforest');
                die();
            }
            $review_enable_gallery = isset($adforest_theme['dwt_listing_review_enable_gallery']) ? $adforest_theme['dwt_listing_review_enable_gallery'] : true;
            if ($review_enable_gallery == '1') {
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    // The uploaded file is in $_FILES['file']
                    $uploadedFile = $_FILES['file'];

                    $fileName = $uploadedFile['name'];

                    $fileType = $uploadedFile['type'];
                    $allowedTypes = array('image/jpeg', 'image/png', 'image/jpg');
                    if (!in_array($uploadedFile['type'], $allowedTypes)) {
                        echo '0|' . __("Please upload a JPEG, JPG or PNG image.", 'adforest');
                        die();
                    }

                    $fileSize = $uploadedFile['size'];
                    $image_rating_size = isset($adforest_theme['adforest_review_images_size']) ? $adforest_theme['adforest_review_images_size'] : "";

                    if ($fileSize > $image_rating_size) {
                        echo '0|' . __("File size exceeds the limit. Please upload a smaller file.", 'adforest');
                        die();
                    }
                    $themeDirectory = get_template_directory();
                    $targetDirectory = $themeDirectory . '/images/';
                    $uniqueFilename = wp_unique_filename($targetDirectory, $uploadedFile['name']);
                    $targetFilePath = $targetDirectory . $uniqueFilename;

                    $uploadResult = wp_upload_bits($targetFilePath, null, file_get_contents($uploadedFile['tmp_name']));
                    $attachmentId = media_handle_upload('file', 0);
                }
            }
            if ($sb_update_rating) {
                $args = array(
                    'type__in' => array('ad_post_rating'),
                    'post_id' => $params['ad_id'],
                    'user_id' => $sender_id,
                    'number' => 1,
                    'parent' => 0,
                );
                $comment_exist = get_comments($args);
                if (count($comment_exist) > 0) {
                    $comment = array();
                    $comment['comment_ID'] = $comment_exist[0]->comment_ID;
                    $comment['comment_content'] = sanitize_textarea_field($params['rating_comments']);
                    wp_update_comment($comment);

                    if (isset($params['rating'])) {
                        update_comment_meta($comment_exist[0]->comment_ID, 'review_stars', $params['rating']);
                    }
                    if ($review_enable_gallery == '1') {
                        if (isset($_FILES['file'])) {
                            update_comment_meta($comment_exist[0]->comment_ID, "review_images_attachmentId", $attachmentId);
                        }
                    }
                    if (isset($adforest_theme['sb_rating_email_author']) && $adforest_theme['sb_rating_email_author']) {
                        adforest_email_ad_rating($params['ad_id'], $sender_id, $params['rating'], $params['rating_comments']);
                    }
                    echo '1|' . __("Your rating has been updated.", 'adforest');
                    die();
                }
            }

            $time = current_time('mysql');
            $data = array(
                'comment_post_ID' => $params['ad_id'],
                'comment_author' => $sender->display_name,
                'comment_author_email' => $sender->user_email,
                'comment_author_url' => '',
                'comment_content' => sanitize_textarea_field($params['rating_comments']),
                'comment_type' => 'ad_post_rating',
                'user_id' => $sender_id,
                'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                'comment_date' => $time,
                'comment_approved' => 1,
            );

            $comment_id = wp_insert_comment($data);
            if ($comment_id) {
                if (isset($params['rating'])) {
                    update_comment_meta($comment_id, 'review_stars', $params['rating']);
                }
                if ($review_enable_gallery == '1') {
                    if (isset($_FILES['file'])) {
                        update_comment_meta($comment_id, "review_images_attachmentId", $attachmentId);
                    }
                }
                update_user_meta($sender_id, 'ad_ratting_' . $sender_id . '_' . $params['ad_id'], $params['ad_id']);


                if (isset($adforest_theme['sb_rating_email_author']) && $adforest_theme['sb_rating_email_author']) {
                    adforest_email_ad_rating($params['ad_id'], $sender_id, $params['rating'], $params['rating_comments']);
                }
                //do_action('adforest_wpml_comment_meta_updation', $comment_id, $params);
                echo '1|' . __("Your rating has been posted.", 'adforest');
                die();
            }
        }
    }
}

add_action('wp_ajax_sb_ad_rating', 'handle_ad_rating_form_submission');
add_action('wp_ajax_nopriv_sb_ad_rating', 'handle_ad_rating_form_submission');


/* Ad rating Reply */
add_action('wp_ajax_sb_ad_rating_reply', 'adforest_ad_rating_reply');
add_action('wp_ajax_nopriv_sb_ad_rating_reply', 'adforest_ad_rating_reply');
if (!function_exists('adforest_ad_rating_reply')) {

    function adforest_ad_rating_reply()
    {

        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }

        check_ajax_referer('sb_review_reply_secure', 'security');
        adforest_set_date_timezone();
        if (!get_current_user_id()) {
            echo '0|' . __("You are not logged in.", 'adforest');
            die();
        } else {
            global $adforest_theme;
            $params = array();
            parse_str(wp_unslash($_POST['sb_data']), $params);

            $sender_id = get_current_user_id();
            $sender = get_userdata($sender_id);

            if ($sender_id != $params['ad_owner']) {
                echo '0|' . __("Only Ad owner can reply the rating.", 'adforest');
                die();
            }

            $args = array(
                'type__in' => array('ad_post_rating'),
                'post_id' => $params['ad_id'],
                'user_id' => $sender_id,
                'number' => 1,
                'parent' => $params['parent_comment_id'],
            );
            $comment_exist = get_comments($args);
            if (count($comment_exist) > 0) {
                $comment = array();
                $comment['comment_ID'] = $comment_exist[0]->comment_ID;
                $comment['comment_content'] = $params['reply_comments'];
                wp_update_comment($comment);

                if (isset($adforest_theme['sb_rating_reply_email']) && $adforest_theme['sb_rating_reply_email']) {
                    $comment_data = get_comment($params['parent_comment_id']);
                    $rating = get_comment_meta($params['parent_comment_id'], 'review_stars', true);
                    adforest_email_ad_rating_reply($params['ad_id'], $comment_data->user_id, $params['reply_comments'], $rating, $comment_data->comment_content);
                }
                echo '1|' . __("Your reply has been updated.", 'adforest');
                die();
            }

            $time = current_time('mysql');

            $data = array(
                'comment_post_ID' => $params['ad_id'],
                'comment_author' => $sender->display_name,
                'comment_author_email' => $sender->user_email,
                'comment_author_url' => '',
                'comment_content' => $params['reply_comments'],
                'comment_type' => 'ad_post_rating',
                'user_id' => $sender_id,
                'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                'comment_date' => $time,
                'comment_parent' => $params['parent_comment_id'],
                'comment_approved' => 1
            );

            $comment_id = wp_insert_comment($data);
            if ($comment_id) {
                if (isset($adforest_theme['sb_rating_reply_email']) && $adforest_theme['sb_rating_reply_email']) {
                    $comment_data = get_comment($params['parent_comment_id']);
                    $rating = get_comment_meta($params['parent_comment_id'], 'review_stars', true);
                    adforest_email_ad_rating_reply($params['ad_id'], $comment_data->user_id, $params['reply_comments'], $rating, $comment_data->comment_content);
                }
                echo '1|' . __("Your reply has been posted.", 'adforest');
                die();
            }
        }
    }
}

// Add to favourites
add_action('wp_ajax_sb_fav_ad', 'adforest_sb_fav_ad');
add_action('wp_ajax_nopriv_sb_fav_ad', 'adforest_sb_fav_ad');
if (!function_exists('adforest_sb_fav_ad')) {
    function adforest_sb_fav_ad()
    {
        check_ajax_referer('sb_fav_ad_nonce', 'security');
        adforest_authenticate_check();
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }

        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        if ($ad_id <= 0) {
            echo '0|' . __("Invalid ad.", 'adforest');
            die();
        }

        $uid      = get_current_user_id();
        $meta_key = '_sb_fav_id_' . $ad_id;
        $existing = get_user_meta($uid, $meta_key, true);

        if ($existing == $ad_id) {
            // TOGGLE: already favourited → remove
            delete_user_meta($uid, $meta_key);
            do_action('adforest_wpml_fav_ads_remove', $ad_id);
            echo '2|' . __("Removed from your favourites.", 'adforest');
        } else {
            // TOGGLE: not favourited → add
            update_user_meta($uid, $meta_key, $ad_id);
            do_action('adforest_wpml_fav_ads', $ad_id);
            echo '1|' . __("Added to your favourites.", 'adforest');
        }
        die();
    }
}

/* Get States */
add_action('wp_ajax_sb_get_sub_states', 'adforest_get_sub_states');
add_action('wp_ajax_nopriv_sb_get_sub_states_search', 'adforest_get_sub_states_search');
if (!function_exists('adforest_get_sub_states')) {
    function adforest_get_sub_states()
    {
        check_ajax_referer('sb_get_sub_states_nonce', 'security');
        $country_id = $_POST['country_id'];
        $ad_country = adforest_get_cats('ad_country', $country_id, 0, 'post_ad');
        if (count($ad_country) > 0) {
            $cats_html = '<select class="category form-control">';
            $cats_html .= '<option label="' . esc_html__('Select Option', 'adforest') . '"></option>';
            foreach ($ad_country as $ad_cat) {
                $cats_html .= '<option value="' . $ad_cat->term_id . '">' . $ad_cat->name . '</option>';
            }
            $cats_html .= '</select>';
            echo adforest_return_echo($cats_html);
            die();
        } else {
            echo "";
            die();
        }
    }
}

add_action('wp_ajax_fetch_sellers', 'fetch_sellers_callback');
add_action('wp_ajax_nopriv_fetch_sellers', 'fetch_sellers_callback');
function fetch_sellers_callback()
{
    check_ajax_referer('sb_fetch_sellers_nonce', 'security');
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $users_per_page = 10;

    $args = [
        'number' => $users_per_page,
        'paged' => $paged,
        'search' => "*{$search}*",
        'meta_query' => [
            'relation' => 'AND',
        ]
    ];

    if (!empty($rating)) {
        $args['meta_query'][] = [
            'key' => '_adforest_rating_avg',
            'value' => $rating,
            'compare' => '>=',
            'type' => 'NUMERIC'
        ];
    }

    $user_query = new WP_User_Query($args);
    $total_users = $user_query->get_total();
    $total_pages = ceil($total_users / $users_per_page);

    ob_start();

    if (!empty($user_query->get_results())) {
        foreach ($user_query->get_results() as $user) {
            $author_id = $user->ID;
            $user_pic = adforest_get_user_dp($author_id, 'adforest-user-profile');
            $address = get_user_meta($author_id, '_sb_address', true);
            $user_role = (get_user_meta($author_id, '_sb_user_type', true) == 'Indiviual') ? __('Individual', 'adforest') : __('Dealer', 'adforest');
            ?>
            <div class="adt-seller-card">
                <div class="top-content">
                    <img src="<?php echo esc_url($user_pic); ?>" alt="seller-img">
                    <h4><?php echo esc_html($user->display_name); ?></h4>
                    <span><?php echo ucfirst($user_role); ?></span>
                </div>
                <div class="location">
                    <i class="fas fa-map-marker-alt"></i><?php echo esc_html($address); ?>
                </div>
                <div class="bottom-content">
                    <div class="rating">
                        <span><?php printf(esc_html__('%s Ads', 'adforest'), esc_html(count_user_posts($author_id, 'ad_post'))); ?></span>
                        <?php
                        $got = get_user_meta($author_id, "_adforest_rating_avg", true);
                        if ($got == "") $got = 0;
                        for ($i = 1; $i <= 5; $i++) {
                            $icon = ($i <= round($got)) ? '<i class="fa fa-star"></i>' : '<i class="far fa-star"></i>';
                            echo esc_html($icon);
                        }
                        ?>
                    </div>
                    <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>" class="seller-prf-btn">View
                        Profile</a>
                </div>
            </div>
            <?php
        }
    } else {
        $nothing_found = esc_url(get_template_directory_uri()) . '/images/nothing-found.png';
        echo '<div class="no_ads_found">
                <img src="' . esc_url($nothing_found) . '" alt="">
                <h3>' . __("No sellers found.", "adforest") . '</h3>
              </div>';
    }

    $html = ob_get_clean();

    // Generate pagination links
    ob_start();
    if ($total_pages > 1) {
        if ($paged > 1) {
            echo '<li class="page-item"><a class="page-link prv" href="#" data-page="' . ($paged - 1) . '"><i class="fas fa-chevron-left"></i></a></li>';
        }

        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $paged) ? ' active' : '';
            echo '<li class="page-item"><a class="page-link' . $active_class . '" href="#" data-page="' . $i . '">' . str_pad($i, 2, '0', STR_PAD_LEFT) . '</a></li>';
        }

        if ($paged < $total_pages) {
            echo '<li class="page-item"><a class="page-link nxt" href="#" data-page="' . ($paged + 1) . '"><i class="fas fa-chevron-right"></i></a></li>';
        }
    }
    $pagination = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'total_users' => $total_users,
        'pagination' => $pagination,
    ]);

    wp_die();
}

add_action('wp_ajax_sb_get_sub_cat_search', 'adforest_get_sub_cats_search');
add_action('wp_ajax_nopriv_sb_get_sub_cat_search', 'adforest_get_sub_cats_search');
if (!function_exists('adforest_get_sub_cats_search')) {
    function adforest_get_sub_cats_search()
    {
        check_ajax_referer('sb_get_sub_cat_search_nonce', 'security');
        global $adforest_theme;
        $adpost_cat_style = isset($adforest_theme['adpost_cat_style']) && $adforest_theme['adpost_cat_style'] == 'grid' ? TRUE : FALSE;
        $search_popup_cat_disable = isset($adforest_theme['search_popup_cat_disable']) ? $adforest_theme['search_popup_cat_disable'] : false;
        $search_show_sub_cats_with_parent = isset($adforest_theme['search_show_sub_cats_with_parent']) ? $adforest_theme['search_show_sub_cats_with_parent'] : false;
        $cat_id = $_POST['cat_id'];
        $load_type = isset($_POST['type']) && $_POST['type'] != '' ? $_POST['type'] : '';
        
        // If the feature is enabled, directly submit the form
        if ($search_show_sub_cats_with_parent && $load_type != 'ad_post') {
            // Check if the category has sub-categories
            $sub_cats = adforest_get_cats('ad_cats', $cat_id);
            if (count($sub_cats) > 0) {
                // Has sub-categories, submit the form
                echo "submit";
            } else {
                // No sub-categories, show empty result
                $selected_cats = adforest_get_taxonomy_parents($cat_id, 'ad_cats', false);
                $find = '&raquo;';
                $replace = '';
                $selected_cats = preg_replace("/$find/", $replace, $selected_cats, 1);
                $res = '<label>' . $selected_cats . '</label>';
                $res .= '<div class="alert alert-info">' . __('No sub-categories found for this category.', 'adforest') . '</div>';
                echo adforest_return_echo($res);
            }
            die();
        }
        
        if ($load_type == 'ad_post') {
            if ($adpost_cat_style) {
                $ad_cats = adforest_get_cats('ad_cats', $cat_id, 0, 'post_ad');
                echo adforest_check_sub_cats_paid($cat_id);
            } else {
                $ad_cats = adforest_get_cats('ad_cats', 0, 0, 'post_ad');
            }
        } else {
            $ad_cats = adforest_get_cats('ad_cats', $cat_id);
        }
        $res = '';
        if (count($ad_cats) > 0) {
            $selected_cats = adforest_get_taxonomy_parents($cat_id, 'ad_cats', false);
            $find = '&raquo;';
            $replace = '';
            $selected_cats = preg_replace("/$find/", $replace, $selected_cats, 1);
            $res = '<label>' . $selected_cats . '</label>';
            $res .= '<ul class="city-select-city" >';

            foreach ($ad_cats as $ad_cat) {
                $id = 'ajax_cat';
                $count_p = ($ad_cat->count);
                $term_level = adforest_get_taxonomy_depth($ad_cat->term_id, 'ad_cats');

                $count_style = ' (' . $count_p . ')';
                if ($load_type == 'ad_post') {
                    $count_style = '';
                }


                $res .= '
                        <li class="col-sm-4 col-xs-6 margin-top-15">
                            <a class="category_click_link" href="javascript:void(0);" data-term-level="' . $term_level . '" data-cat-id="' . esc_attr($ad_cat->term_id) . '" id="' . $id . '">'
                    . $ad_cat->name . $count_style . '
                            </a>
                        </li>';
            }
            $res .= '</ul>';
            echo adforest_return_echo($res);
        } else {
            echo "submit";
        }
        die();
    }
}

// check permission for ad posting
if (!function_exists('adforest_check_validity')) {

    function adforest_check_validity($free_ads = '', $pkg_expiry_days = ''): void
    {
        global $adforest_theme;
        $uid = get_current_user_id();
        $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
        if (get_user_meta($uid, '_sb_simple_ads', true) == 0 || get_user_meta($uid, '_sb_simple_ads', true) == "") {
            if ($free_ads < 1) {
                adforest_redirect_with_msg(get_the_permalink($sb_packages_page), __('Please subscribe to a package to post an ad.', 'adforest'));
            } else {
                if (get_user_meta($uid, '_sb_expire_ads', true) != '-1') {
                    if (get_user_meta($uid, '_sb_expire_ads', true) < current_time('Y-m-d')) {
                        update_user_meta($uid, '_sb_simple_ads', 0);
                        update_user_meta($uid, '_sb_featured_ads', 0);
                        if ($pkg_expiry_days !== '-1') {
                            if ($pkg_expiry_days < current_time('Y-m-d')) {
                                adforest_redirect_with_msg(get_the_permalink($sb_packages_page), __("Your package has been expired.", 'adforest'));
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }
}

/* Make ad featured  */
add_action('wp_ajax_sb_make_featured_detail', 'adforest_make_featured_detail');
if (!function_exists('adforest_make_featured_detail')) {
    function adforest_make_featured_detail()
    {
        check_ajax_referer('sb_make_featured_detail_nonce', 'security');
        global $adforest_theme;
        $ad_id = $_POST['ad_id'];
        $ads_package = $_POST['ads_package'];

        $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
        $params_ads_package = $ads_package;

        if (isset($packageDetails[$params_ads_package])) {
            $package_single = $packageDetails[$params_ads_package];
        }

        $featured_ad_new = isset($package_single['featured_ads']) ? $package_single['featured_ads'] : '';
        $pkg_expiry_date = isset($package_single['pkg_expiry_days']) ? $package_single['pkg_expiry_days'] : '';

        $user_id = get_current_user_id();
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }
        if (get_post_field('post_author', $ad_id) == $user_id) {
            if (get_post_meta($ad_id, '_adforest_is_feature', true) == '1') {
                wp_send_json_error(array("message" => __("This ad is featured already.", 'adforest')));
                die();
            }

            $featured_ads = get_user_meta($user_id, '_sb_featured_ads', true);
            if (isset($adforest_theme['make_feature_paid']) && $adforest_theme['make_feature_paid'] == true && get_post_meta($ad_id, '_adforest_is_feature', true) != "1") {
                $url = get_the_permalink($adforest_theme['sb_feature_template_page']);
                // ITHY
                $redirect_url = $url . "?pid=" . $ad_id;
                wp_send_json_success(array("message" => __("Redirecting....", 'adforest'), 'url' => $redirect_url));
                die();
            } else {
                if ($featured_ads != 0 && $featured_ads != "") {

                    if (get_user_meta($user_id, '_sb_expire_ads', true) != '-1') {
                        if (get_user_meta($user_id, '_sb_expire_ads', true) < current_time('Y-m-d')) {
                            wp_send_json_error(array("message" => __("Your package has expired", 'adforest')));
                            die();
                        }
                    }
                    $feature_ads = get_user_meta($user_id, '_sb_featured_ads', true);
                    $feature_ads = (int)$feature_ads - 1;
                    update_user_meta($user_id, '_sb_featured_ads', $feature_ads);
                    update_post_meta($ad_id, '_adforest_is_feature', '1');
                    update_post_meta($ad_id, '_adforest_is_feature_date', current_time('Y-m-d'));

                    $package_adFeatured_expiry_days = get_user_meta($user_id, 'package_adFeatured_expiry_days', true);
                    if ($package_adFeatured_expiry_days) {
                        update_post_meta($ad_id, 'package_adFeatured_expiry_days', $package_adFeatured_expiry_days);
                    }

                    do_action('adforest_wpml_make_featured', $ad_id);
                    $ad_meta = get_post_meta($ad_id);

                    if ($ad_meta['_adforest_is_feature'][0] != 0) {
                        wp_send_json_success(array("message" => __("This ad has been featured successfully.", 'adforest')));
                    } else {
                        wp_send_json_error(array("message" => __("Something Went Wrong", 'adforest')));
                    }
                } elseif ($featured_ad_new != 0 && $featured_ad_new != "") {

                    if ($pkg_expiry_date != '-1') {
                        if ($pkg_expiry_date < current_time('Y-m-d')) {
                            wp_send_json_error(array("message" => __("Your package has expired", 'adforest')));
                            die();
                        }
                    }
                    update_post_meta($ad_id, '_adforest_is_feature', '1');
                    update_post_meta($ad_id, '_adforest_is_feature_date', current_time('Y-m-d'));
                    $ad_meta = get_post_meta($ad_id);

                    if ($ad_meta['_adforest_is_feature'][0] != 0) {
                        $featured_ad_new = $featured_ad_new - 1;
                        $package_single['featured_ads'] = $featured_ad_new;
                        $packageDetails[$params_ads_package] = $package_single;
                        update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                        wp_send_json_success(array("message" => __("This ad has been featured successfullly.", 'adforest')));
                    } else {
                        wp_send_json_error(array("message" => __("Something Went Wrong", 'adforest')));
                    }
                } else {
                    wp_send_json_error(array("message" => __("Get package in order to make it featured", 'adforest')));
                }
            }
        } else {
            wp_send_json_error(array("message" => __("You must be the Ad owner to make it featured.", 'adforest')));
        }
        die();
    }
}

add_action('wp_ajax_load_more_ads', 'load_more_ads');
add_action('wp_ajax_nopriv_load_more_ads', 'load_more_ads');
function load_more_ads()
{
    check_ajax_referer('sb_load_more_ads_nonce', 'security');
    global $adforest_theme;
    
    $query_args = json_decode(stripslashes($_POST['search_query']), true);
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $query_args['paged'] = $paged;
    $view_type = $_POST['view_type'];

    $query = new WP_Query($query_args);
    if ($query->have_posts()) {
        ob_start();

        if ($view_type == 'list') {
            // Handle list view with proper list layout
        while ($query->have_posts()) {
            $query->the_post();
            $pid = get_the_ID();
            $ad_details = get_ad_post_details($pid);
            $first_img = $ad_details['img'];
                $truncated_location = $ad_details['location'];
                $truncated_title = $ad_details['ad_title'];
            $price_html = $ad_details['price_html'];
            $ad_permalink = $ad_details['ad_link'];
            $heart_class = $ad_details['heart_class'];
            $is_featured = $ad_details['is_featured'];
                $all_ad_images = $ad_details['all_ad_images'];
                $ad_poster_img = $ad_details['ad_poster_img'];
                $ad_poster_name = $ad_details['ad_poster_name'];
                $ad_title = $ad_details['ad_title'];
                $location = $ad_details['location'];
                $content = get_post_field('post_content', $pid);
                $clean_content = wp_strip_all_tags($content);
                $truncated_content = truncate_string($clean_content, 120);
                $ad_categories_post = $ad_details['categories'];
                
                // Get comments for rating
                $limit = $adforest_theme['sb_rating_max'] ?? 0;
                $offset = ($paged * $limit) - $limit;
                $args = array(
                    'type__in' => array('ad_post_rating'),
                    'number' => $limit,
                    'offset' => $offset,
                    'parent' => 0,
                    'post_id' => $pid,
                );
                $comments = get_comments($args);
                $get_percentage = adforest_fetch_reviews_average($pid);

                if (isset($adforest_theme['adforest_list_layout']) && $adforest_theme['adforest_list_layout'] == '1') {
                    // List style 1
                    ?>
                <div class="adt-category-ad-list">
                    <div class="category-img-box">
                        <a href="<?php echo esc_url($ad_permalink, 'adforest'); ?>">
                            <img class="img-fluid"
                                 src="<?php echo esc_url($first_img, "adforest"); ?>"
                                     alt="<?php echo esc_html($ad_title); ?>">

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
                            <?php
                            $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                            $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                            $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                            ?>
                            <a href="javascript:void(0);"
                               class="favourite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>"
                               data-adid="<?php echo $pid; ?>"
                               data-toggle="tooltip"
                               data-placement="top"
                               title="<?php echo esc_attr( $fav_title_local ); ?>"
                               aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
                                <i class="<?php echo esc_attr($heart_class); ?>"></i>
                            </a>
                            <div class="adt-ad-cats">
                                <?php echo wp_kses($category_links_string, ADFOREST_ALLOWED_FORM_HTML); ?>
                            </div>
                            <a href="<?php echo esc_url($ad_permalink); ?>">
                            <h5><?php echo esc_html($truncated_title); ?></h5></a>
                        <p>
                            <i class="fas fa-map-marker-alt"></i><?php echo esc_html($truncated_location); ?>
                        </p>
                        <div class="price-box">
                            <?php echo wp_kses($price_html, ADFOREST_ALLOWED_FORM_HTML); ?>
                                <a href="<?php echo esc_url($ad_permalink); ?>"
                               class="detail-btn"><?php echo __("Detail", "adforest"); ?></a>
                        </div>
                    </div>
                </div>
                    <?php
                } elseif (isset($adforest_theme['adforest_list_layout']) && $adforest_theme['adforest_list_layout'] == '2') {
                    // List style 2
                    ?>
                    <div class="adt-car-dealer-card">
                        <div class="adt-car-ad-carousel owl-carousel owl-theme">
                            <?php foreach ($all_ad_images as $image_url) { ?>
                                <div class="item"><img src="<?php echo esc_url($image_url); ?>"
                                                       alt="<?php echo esc_html($ad_title); ?>">
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
                                    <li><i class="far fa-calendar-alt"></i><?php echo get_the_date('F j, Y', $pid); ?></li>
                                    <li>
                                        <img src="<?php echo esc_url($ad_poster_img, "adforest"); ?>"
                                             alt="author"><?php echo esc_html($ad_poster_name); ?>
                                    </li>
                                </ul>
                                <p><?php echo esc_html($truncated_content); ?></p>
                            </div>
                            <div class="adt-car-price-meta">
                                <div class="price-box">
                                    <?php echo wp_kses($price_html, ADFOREST_ALLOWED_FORM_HTML); ?>
                                </div>
                                <div class="detail-btn-box">
                                    <?php
                                    $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                                    $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                                    $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                                    ?>
                                    <span class="favorite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>"
                                          data-adid="<?php echo $pid; ?>"
                                          data-toggle="tooltip"
                                          data-placement="top"
                                          title="<?php echo esc_attr( $fav_title_local ); ?>"
                                          aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
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
            }
        } else {
            // Handle grid view with proper grid layout
            while ($query->have_posts()) {
                $query->the_post();
                $pid = get_the_ID();
                $ad_details = get_ad_post_details($pid);
                $first_img = $ad_details['img'];
                $truncated_location = $ad_details['location'];
                $truncated_title = $ad_details['ad_title'];
                $price_html = $ad_details['price_html'];
                $ad_permalink = $ad_details['ad_link'];
                $heart_class = $ad_details['heart_class'];
                $is_featured = $ad_details['is_featured'];
                $all_ad_images = $ad_details['all_ad_images'];
                $ad_poster_img = $ad_details['ad_poster_img'];
                $ad_poster_name = $ad_details['ad_poster_name'];
                $ad_title = $ad_details['ad_title'];
                $ad_categories_post = $ad_details['categories'];
                $ad_type = get_post_meta($pid, '_adforest_ad_type', true);
                $featured_tag = $is_featured ? '<img style="transform: rotate(180deg);" src="' . esc_url(get_template_directory_uri()) . '/images/featured.png' . '" alt="featured-tag" class="featured-tag">' : '';
                
                $top_bar_specific_style = '';
                if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'topbar') {
                    $top_bar_specific_style = 'top_bar_specific_style';
                }

                if (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'simple') {
                    // Grid style 1
                    echo adforest_ad_grid_1($ad_permalink, $first_img, $is_featured, $ad_categories_post, $ad_details, $truncated_title, $truncated_location, $price_html, $heart_class);
                } elseif (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'with_labels') {
                    // Grid style 2
                    ?>
                    <div class="item search_with_labels_grid <?php echo esc_attr($top_bar_specific_style); ?>">
                        <?php echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class, $pid); ?>
                    </div>
                    <?php
                } elseif (isset($adforest_theme['adforest_grid_layout']) && $adforest_theme['adforest_grid_layout'] == 'modern') {
                    // Grid style 3
                    ?>
                    <div class="item search_with_labels_grid <?php echo esc_attr($top_bar_specific_style); ?>">
                        <?php echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location, $pid); ?>
                    </div>
                    <?php
                }
            }
        }

        $response = ob_get_clean();
        echo wp_kses_post($response);
    } else {
        echo '0';
    }
    wp_die();
}


function adforest_handle_product_review_submission()
{
    check_ajax_referer('sb_product_review_nonce', 'security');
    if (empty($_POST['product_id']) || empty($_POST['name']) || empty($_POST['email']) || empty($_POST['description'])) {
        wp_send_json_error(['message' => __('Please fill in all required fields.', 'adforest')]);
    }

    $product_id = intval($_POST['product_id']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $rating = intval($_POST['rating']);

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => __('Invalid product.', 'adforest')]);
    }

    if ($rating < 0 || $rating > 5) {
        wp_send_json_error(['message' => __('Invalid rating value.', 'adforest')]);
    }

    if (!is_user_logged_in()) {
        if (email_exists($email)) {
            wp_send_json_error(['message' => __('You must be logged in to review this product.', 'adforest')]);
        }
    } else {
        $user_id = get_current_user_id();
        $existing_review = get_comments(array(
            'post_id' => $product_id,
            'user_id' => $user_id,
            'count' => true,
        ));

        if ($existing_review > 0) {
            wp_send_json_error(['message' => __('You have already reviewed this product.', 'adforest')]);
        }
    }

    $existing_review_by_email = get_comments(array(
        'post_id' => $product_id,
        'author_email' => $email,
        'count' => true,
    ));
    if ($existing_review_by_email > 0) {
        wp_send_json_error(['message' => __('You have already reviewed this product using this email.', 'adforest')]);
    }

    $comment_data = array(
        'comment_post_ID' => $product_id,
        'comment_author' => $name,
        'comment_author_email' => $email,
        'comment_content' => $description,
        'comment_type' => 'review',
        'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
        'comment_approved' => 0,
    );

    $comment_id = wp_insert_comment($comment_data);

    if ($comment_id) {
        if ($rating) {
            update_comment_meta($comment_id, 'rating', $rating);
        }
        if ($title) {
            update_comment_meta($comment_id, 'review_title', $title);
        }

        wp_send_json_success(['message' => __('Thank you! Your review has been submitted for moderation.', 'adforest')]);
    }

    wp_send_json_error(['message' => __('An error occurred while submitting your review. Please try again.', 'adforest')]);
}

add_action('wp_ajax_submit_product_review', 'adforest_handle_product_review_submission');
add_action('wp_ajax_nopriv_submit_product_review', 'adforest_handle_product_review_submission');

//========================== SELLER RATING ==========================//
add_action('wp_ajax_sb_post_user_rating', 'adforest_post_user_rating');
add_action('wp_ajax_nopriv_sb_post_user_rating', 'adforest_post_user_rating');
if (!function_exists('adforest_post_user_rating')) {
    function adforest_post_user_rating()
    {

        global $adforest_theme;
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }
        adforest_authenticate_check();
        // Getting values
        $params = array();
        parse_str(wp_unslash($_POST['sb_data']), $params);
        check_ajax_referer('sb_user_rating_secure', 'security');
        $ratting = $params['rating'];
        $comments = sanitize_text_field($params['sb_rate_user_comments']);
        $author = $params['author'];
        $rator = get_current_user_id();

        if ($author == $rator) {
            echo '0|' . __("You can't rate yourself.", 'adforest');
            die();
        }
        if (get_user_meta($author, "_user_" . $rator, true) == "") {
            update_user_meta($author, "_user_" . $rator, $ratting . "_separator_" . $comments . "_separator_" . current_time('Y-m-d'));

            $ratings = adforest_get_all_ratings($author);
            $all_rattings = 0;
            $got = 0;

            if (count($ratings) > 0) {
                foreach ($ratings as $rating) {
                    $data = explode('_separator_', $rating->meta_value);

                    if (isset($data[0]) && is_numeric($data[0])) {
                        $got += floatval($data[0]);
                        $all_rattings++;
                    } else {
                        echo '0|' . __("Error processing rating data.", 'adforest');
                        exit;
                    }
                }

                if ($all_rattings > 0) {
                    $avg = $got / $all_rattings;
                } else {
                    $avg = $ratting;
                }
            } else {
                $avg = $ratting;
            }


            $rating_updated = update_user_meta($author, "_adforest_rating_avg", $avg);
            if (!$rating_updated) {
                echo '0|' . __("Failed to update the average rating.", 'adforest');
                exit;
            }

            $total = get_user_meta($author, "_adforest_rating_count", true);
            if ($total == "") $total = 0;
            $total += 1;

            $count_updated = update_user_meta($author, "_adforest_rating_count", $total);
            print_r($count_updated);
            if (!$count_updated) {
                echo '0|' . __("Failed to update the rating count.", 'adforest');
                exit;
            }

            global $adforest_theme;
            if (isset($adforest_theme['email_to_user_on_rating']) && $adforest_theme['email_to_user_on_rating']) {
                adforest_send_email_new_rating($rator, $author, $ratting, $comments);
            }

            echo '1|' . __("You've rated this user.", 'adforest');
        } else {

            if (isset($adforest_theme['sb_rewiew_edit']) && $adforest_theme['sb_rewiew_edit']) {

                update_user_meta($author, "_user_" . $rator, $ratting . "_separator_" . $comments . "_separator_" . current_time('Y-m-d'));

                $ratings = adforest_get_all_ratings($author);
                $all_rattings = 0;
                $got = 0;
                if (count($ratings) > 0) {
                    foreach ($ratings as $rating) {
                        $data = explode('_separator_', $rating->meta_value);
                        $got = $got + $data[0];
                        $all_rattings++;
                    }
                    $avg = $got / $all_rattings;
                } else {
                    $avg = $ratting;
                }


                update_user_meta($author, "_adforest_rating_avg", $avg);
                echo '1|' . __("Your rating has been updated.", 'adforest');
                die();
            }
            echo '0|' . __("You already rated this user.", 'adforest');
        }
        die();
    }
}
//========================== SELLER RATING ==========================//


/* ========================= */
/*  deal with video upload   */
/* ========================= */
add_action('wp_ajax_upload_ad_videos', 'upload_ad_videos_callback');
if (!function_exists('upload_ad_videos_callback')) {
    function upload_ad_videos_callback()
    {
        check_ajax_referer('sb_upload_ad_videos_nonce', 'security');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }
        global $adforest_theme;
        adforest_authenticate_check();
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        /* get files information */
        $vid_file_name = $_FILES['my_single_video_upload']['name'];
        $vid_file_size = $_FILES['my_single_video_upload']['size'];
        $vid_convert_to_mb = ($vid_file_size / 1000000);
        $vid_file_format = end(explode('.', $vid_file_name));

        /* max upload size in MB */
        $vid_size_arr = explode('-', $adforest_theme['sb_upload_video_mb_limit']);
        $vid_display_size = $vid_size_arr[1];
        $vid_actual_size = $vid_size_arr[0];

        /* Check file size */
        if ($vid_convert_to_mb > $vid_actual_size) {
            echo '0|' . __("Max allowd video size in MB is", 'adforest') . " " . $vid_actual_size;
            die();
        }

        /* check ad is updating */
        if ($_GET['is_update'] != "") {
            $ad_id = absint($_GET['is_update']);
        } else {
            $ad_id = get_user_meta(get_current_user_id(), 'ad_in_progress', true);
        }

        if ($ad_id == "") {
            echo '0|' . __("Please enter title first in order to create ad.", 'adforest');
            die();
        }
        /* get already attachment ids */
        $store_vid_ids = '';
        $store_vid_ids_arr = array();
        $store_vid_ids = get_post_meta($ad_id, 'adforest_video_uploaded_attachment_', true);
        if ($store_vid_ids != '') {
            $store_vid_ids_arr = explode(',', $store_vid_ids);
        }
        // Check max file limit
        if (count($store_vid_ids_arr) > 0) {
            if (count($store_vid_ids_arr) >= $adforest_theme['sb_upload_video_limit']) {
                echo '0|' . esc_html__("You can not upload more than ", 'adforest') . " " . $adforest_theme['sb_upload_video_limit'];
                die();
            }
        }
        $attachment_id = media_handle_upload('my_single_video_upload', $ad_id);

        if (!is_wp_error($attachment_id)) {
            $video_attachment_id = get_post_meta($ad_id, 'adforest_video_uploaded_attachment_', true);
            if ($video_attachment_id != "") {
                $video_attachment_id = $video_attachment_id . ',' . $attachment_id;
                update_post_meta($ad_id, 'adforest_video_uploaded_attachment_', $video_attachment_id);
            } else {
                update_post_meta($ad_id, 'adforest_video_uploaded_attachment_', $attachment_id);
            }
            echo '' . $attachment_id;
            die();
        } else {
            echo '0|' . __("Something went wrong please try later", 'adforest');
            die();
        }
    }
}

/*====== Fetch uploaded video to display after upload ... ======*/
add_action('wp_ajax_get_uploaded_video', 'adforest_get_uploaded_video');
if (!function_exists('adforest_get_uploaded_video')) {

    function adforest_get_uploaded_video()
    {
        check_ajax_referer('sb_get_uploaded_video_nonce', 'security');
        if ($_POST['is_update'] != "") {
            $ad_id = $_POST['is_update'];
        } else {
            $ad_id = get_user_meta(get_current_user_id(), 'ad_in_progress', true);
        }

        /* get record from db */
        $video_attachment_id = get_post_meta($ad_id, 'adforest_video_uploaded_attachment_', true);
        $video_ids_ = (explode(",", $video_attachment_id));
        $result = array();
        if (count($video_ids_) > 0 && is_array($video_ids_) && $video_ids_[0] != "-1" && $video_ids_[0] != '') {
            $mid = '';
            //if (isset($video_attachment_id) && !empty($video_attachment_id) && $video_attachment_id[0] != "-1") {
            //$image = wp_get_attachment_image_src($video_attachment_id, 'adforest-ad-thumb');
            for ($i = 0; $i < count($video_ids_); $i++) {
                $mid = $video_ids_[$i];
                $attach_video_details = wp_get_attachment_metadata($mid);
                $video_url = wp_get_attachment_url($mid);
                $obj = array();
                $obj['video_name'] = basename(get_attached_file($mid));
                $obj['video_url'] = $video_url;
                $obj['video_size'] = filesize(get_attached_file($mid));
                $obj['video_id'] = (int)$mid;
                $result[] = $obj;
            }
        }
        header('Content-type: text/json');
        header('Content-type: application/json');
        if ($result != '') {
            echo json_encode($result);
        }
        die();
    }
}
/*====== Fetch uploaded video to display after upload ... ======*/

/*========================== delete video ==========================*/
add_action('wp_ajax_delete_upload_video', 'adforest_delete_upload_video');
if (!function_exists('adforest_delete_upload_video')) {

    function adforest_delete_upload_video()
    {
        check_ajax_referer('sb_delete_upload_video_nonce', 'security');
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

        $attachment_id_ = $_POST['video'];
        if ($attachment_id_) {
            $save_db = '';
            $ids = get_post_meta($ad_id, 'adforest_video_uploaded_attachment_', true);
            $ids_arr = explode(',', $ids);
            if (in_array($attachment_id_, $ids_arr)) {
                unset($ids_arr[array_search($attachment_id_, $ids_arr)]);
            }
            if (!empty($ids_arr) && $ids_arr[0] != '') {
                $ids_arr = array_values($ids_arr);
                $save_db = implode(',', $ids_arr);
            } else {
                $save_db = "";
            }
            $update_output = update_post_meta($ad_id, 'adforest_video_uploaded_attachment_', $save_db);
            if ($update_output != false) {
                wp_delete_attachment($attachment_id_, true);
            }
            echo '1';
            die();
        } else {
            echo '0|' . __("File not Deleted", 'adforest');
            die();
        }
    }
}
/*========================== delete video ==========================*/

add_action('wp_ajax_sb_verification_code', 'adforest_verification_code');
if (!function_exists('adforest_verification_code')) {

    function adforest_verification_code()
    {
        check_ajax_referer('sb_verification_code_nonce', 'security');
        $code = $_POST['sb_code'];
        $user_id = get_current_user_id();
        $saved = get_user_meta($user_id, '_ph_code_', true);
        if ($saved == "") {
            echo '0|' . __("Code not found in DB", 'adforest');
        }

        if ($code == $saved) {
            update_user_meta($user_id, '_ph_code_', '');
            update_user_meta($user_id, '_sb_is_ph_verified', '1');
            update_user_meta($user_id, '_ph_code_date_', '');
            echo '1|' . __("Phone number has been verified", 'adforest');
        } else {
            echo '0|' . __("Invalid code that you entered", 'adforest');
        }

        die();
    }
}

/* * ***************************************** */
/* Ajax handler for job alerts subscription */
/* * **************************************** */
add_action('wp_ajax_nopriv_job_alert_subscription_check', 'sb_job_alert_subscription_check');
add_action('wp_ajax_job_alert_subscription_check', 'sb_job_alert_subscription_check');
if (!function_exists('sb_job_alert_subscription_check')) {
    function sb_job_alert_subscription_check()
    {
        check_ajax_referer('sb_job_alert_subscription_check_nonce', 'security');
        global $adforest_theme;
        $user_id = get_current_user_id();

        if ($user_id == "" || $user_id == 0) {

            echo '0|' . __("Please login first", 'adforest');
            die();
        }

        echo '1|' . __("Proceed", 'adforest');
    }
}

/* * ***************************************** */
/* Ajax handler for job alerts subscription */
/* * **************************************** */
add_action('wp_ajax_nopriv_job_alert_subscription', 'sb_job_alert_subscription');
add_action('wp_ajax_job_alert_subscription', 'sb_job_alert_subscription');
if (!function_exists('sb_job_alert_subscription')) {
    function sb_job_alert_subscription()
    {
        check_ajax_referer('sb_job_alert_subscription_nonce', 'security');
        global $adforest_theme;
        $user_id = get_current_user_id();

        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }

        if ($user_id == "" || $user_id == 0) {
            echo '0|' . __("Please login first", 'adforest');
            die();
        }

        // Get values from form data
        $params = array();
        parse_str(wp_unslash($_POST['submit_alert_data']), $params);

        // Sanitize inputs to ensure no malicious code is processed
        $alert_name = sanitize_text_field(wp_unslash($_POST['alert_name']));
        $alert_email = sanitize_email($_POST['alert_email']);
        $alert_frequency = sanitize_text_field($_POST['alert_frequency']);
        $alert_category = sanitize_text_field($_POST['alert_category']);

        $random_string = adforest_randomString(5);

        $cand_alert = array();
        if (!empty($alert_name)) {
            $cand_alert['alert_name'] = esc_html($alert_name);
        }
        if (!empty($alert_email)) {
            $cand_alert['alert_email'] = esc_html($alert_email);
        }
        if (!empty($alert_category)) {
            $cand_alert['alert_category'] = $alert_category;
        }

        $encoded_alert = json_encode($cand_alert, JSON_UNESCAPED_UNICODE);
        update_user_meta($user_id, '_cand_alerts_' . $user_id . $random_string, $encoded_alert);

        if (get_user_meta($user_id, '_cand_alerts_en', true) == '') {
            update_user_meta($user_id, '_cand_alerts_en', 1);
        }

        echo '1|' . __("Successfully subscribed", 'adforest');
        die();
    }
}

add_action('wp_ajax_handle_like_dislike', 'handle_like_dislike');
add_action('wp_ajax_nopriv_handle_like_dislike', 'handle_like_dislike');

function handle_like_dislike()
{
    check_ajax_referer('handle_like_dislike_nonce', 'security');
    if (isset($_POST['comment_id']) && isset($_POST['type'])) {
        $comment_id = intval($_POST['comment_id']);
        $type = sanitize_text_field($_POST['type']);
        $user_id = get_current_user_id();

        $already_liked = get_comment_meta($comment_id, 'liked_' . $user_id, true);
        $already_disliked = get_comment_meta($comment_id, 'disliked_' . $user_id, true);

        if ($type == 'like') {
            if ($already_liked) {
                wp_send_json_error(["message" => __("Message already Liked.", "adforest")]);
                die();
            }

            if ($already_disliked) {
                $dislikes = get_comment_meta($comment_id, 'dislikes_count', true);
                $dislikes = $dislikes ? $dislikes - 1 : 0;
                update_comment_meta($comment_id, 'dislikes_count', $dislikes);
                delete_comment_meta($comment_id, 'disliked_' . $user_id);
            }

            $likes = get_comment_meta($comment_id, 'likes_count', true);
            $likes = $likes ? $likes + 1 : 1;
            update_comment_meta($comment_id, 'likes_count', $likes);

            update_comment_meta($comment_id, 'liked_' . $user_id, true);

        } elseif ($type == 'dislike') {
            if ($already_disliked) {
                wp_send_json_error(["message" => __("Message already Disliked.", "adforest")]);
                die();
            }

            if ($already_liked) {
                $likes = get_comment_meta($comment_id, 'likes_count', true);
                $likes = $likes ? $likes - 1 : 0;
                update_comment_meta($comment_id, 'likes_count', $likes);
                delete_comment_meta($comment_id, 'liked_' . $user_id);
            }

            $dislikes = get_comment_meta($comment_id, 'dislikes_count', true);
            $dislikes = $dislikes ? $dislikes + 1 : 1;
            update_comment_meta($comment_id, 'dislikes_count', $dislikes);

            update_comment_meta($comment_id, 'disliked_' . $user_id, true);
        }

        wp_send_json_success(array(
            'likes' => get_comment_meta($comment_id, 'likes_count', true),
            'dislikes' => get_comment_meta($comment_id, 'dislikes_count', true),
        ));
    } else {
        wp_send_json_error(["message" => __("Something Went Wrong.", "adforest")]);
    }
}

// Get sub cats
// Register AJAX actions for getting subcategories and Category based Pacakges
add_action('wp_ajax_nopriv_sb_get_sub_cat', 'adforest_get_sub_cats');
add_action('wp_ajax_sb_get_sub_cat', 'adforest_get_sub_cats');

if (!function_exists('adforest_get_sub_cats')) {
    function adforest_get_sub_cats()
    {
        check_ajax_referer('sb_get_sub_cat_nonce', 'security');
        global $adforest_theme;

        $cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
        $is_update = isset($_POST['is_update']) ? intval($_POST['is_update']) : "";

        if ($adforest_theme['sb_pay_per_post_option']) {
            $result = adforest_check_category_pay_per_post_package($cat_id);

            if ($result['found'] == 0) {
                $response = [];

                if ($result['reason'] == 'cat_not_found') {
                    $response = ['title' => esc_html__("Not Found", "adforest"), 'message' => __("Category not found in Pay Per Post Packages", "adforest")];
                } else {
                    $response = ['title' => esc_html__("Not Found", "adforest"), 'message' => __("No Package found for Pay Per Post", "adforest")];
                }
                wp_send_json_error($response);
            }
        } else {

        }

        $ad_cats = adforest_get_cats('ad_cats', $cat_id, 0, 'post_ad');

        $cat_pkg_type = $adforest_theme['cat_pkg_type'] ?? 'parent';
        $parent_child_pkg_flag = ($cat_pkg_type === 'child') || !adforest_category_has_parent($cat_id);

        $category_package_flag = false;
        if ($parent_child_pkg_flag) {
            $paid_category = get_term_meta($cat_id, 'adforest_make_cat_paid', true) === 'yes';
            $selected_categories = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true) ?: [];
            $all_cates = $selected_categories['allow_cate'] ?? '';

            if ($paid_category) {
                if ($all_cates === '') {
                    $category_package_flag = true;
                } elseif ($all_cates !== 'all' && !in_array($cat_id, explode(",", $all_cates))) {
                    $category_package_flag = true;
                }
            }
        }

        $Ads_packages = '';
        $all_packages = '';
        $pay_per_post = $adforest_theme['sb_pay_per_post_option'] ?? false;

        $ppp_free_toggle = isset($adforest_theme['enable_free_ads_with_ppp']) ? (bool)$adforest_theme['enable_free_ads_with_ppp'] : false;
        $user_free_remaining = (int)get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true);
        if ($ppp_free_toggle && $pay_per_post) {
            if ($user_free_remaining === 0 && get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true) === '') {
                $initial_quota = isset($adforest_theme['ppp_free_ads_quota']) ? intval($adforest_theme['ppp_free_ads_quota']) : 0;
                update_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', max(0, $initial_quota));
                $user_free_remaining = (int)get_user_meta(get_current_user_id(), 'ppp_free_ads_remaining', true);
            }
        }
        if ($parent_child_pkg_flag && $category_package_flag && $paid_category && $is_update == 0) {
            if (!$pay_per_post) {
                $Ads_packages = prepare_category_packages($cat_id, $selected_categories);
                if($Ads_packages == '') {
                    $packages_page_id = $adforest_theme['sb_packages_page'] ?? "";
                    $package_page_link = "";
                    if($packages_page_id != "") {
                        $package_page_link = get_permalink($packages_page_id);
                    }

                    wp_send_json_error([
                            'title'=>esc_html__("Category Paid!", "adforest"),
                            'message' => __("Please Purchase a Package for Posting in this Paid Category.", "adforest"),
                            'redirect' => esc_url($package_page_link)
                    ]);
                }
            }
        }

        if (!$paid_category && !$pay_per_post) {
            $selected_packages = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);

            $filtered_packages = [];
            if (is_array($selected_packages)){
                $filtered_packages = array_filter($selected_packages, function ($package) use ($cat_id) {
                    $allowed = isset($package['allow_cate']) ? $package['allow_cate'] : '';

                    return $allowed === '' || $allowed === 'all' || (string)$allowed === (string)$cat_id;
                });
            }

            $default_packages = prepare_default_packages();

            if (is_array($filtered_packages) && is_array($default_packages)) {
                $selected_packages = $default_packages + $filtered_packages;
            } else {
                $selected_packages = $default_packages;
            }

            if (is_array($selected_packages) && count($selected_packages) > 0 && $is_update == 0) {
                foreach ($selected_packages as $key => $values) {
                    $pkg_expiry_date = isset($values['pkg_expiry_days']) ? $values['pkg_expiry_days'] : '';

                    $is_expired = ($pkg_expiry_date !== '-1' && $pkg_expiry_date < current_time('Y-m-d'));
                    if ($is_expired) {
                        continue;
                    }

                    $json_data = esc_attr(wp_json_encode($values));

                    $Pkg_radio = '<input class="form-check-input ads-package-radio" '
                        . 'type="radio" '
                        . 'name="ads_package" '
                        . 'id="ads_package_' . esc_attr($key) . '" '
                        . 'value="' . esc_attr($key) . '" '
                        . 'data-package="' . $json_data . '" '
                        . 'required>';

                    $product_title = $key == 0 ? __("Default", "adforest") : (wc_get_product($key) ? wc_get_product($key)->get_title() : '');

                    $all_packages .= '<div class="package-card">
                        <label for="ads_package_' . $key . '">
                            <div class="card-content">
                                <div class="r-meta">
                                    ' . $Pkg_radio . '
                                    <span class="product-title">' . $product_title . '</span>
                                </div>
                                <ul class="package-details">';
                    foreach ($values as $detailName => $detailValue) {
                        if (in_array($detailName, ['free_ads', 'featured_ads', 'pkg_expiry_days', 'allow_bidding', 'allow_tags', 'video_links']) && !empty($detailValue)) {
                            $custom_titles = [
                                'free_ads' => __('Free Ads', 'adforest'),
                                'featured_ads' => __('Featured Ads', "adforest"),
                                'pkg_expiry_days' => __('Package Expiry Days', 'adforest'),
                                'allow_bidding' => __('Bidding Allowed', 'adforest'),
                                'allow_tags' => __('Tags Allowed', 'adforest'),
                                'video_links' => __('Video Allowed', 'adforest'),
                            ];
                            if ($detailName === 'pkg_expiry_days' && strtotime($detailValue)) {
                                $pretty_value = date_i18n(get_option('date_format'), strtotime($detailValue));
                            } else {
                                $pretty_value = ($detailValue == "-1") ? __("Unlimited", "adforest") : $detailValue;
                            }

                            if($pretty_value == 'no') {
                                $pretty_value = __("No", "adforest");
                            }
                            
                            if($pretty_value == 'yes') {
                                $pretty_value = __("Yes", "adforest");
                            }

                            $custom_title = $custom_titles[$detailName] ?? $detailName;
                            $all_packages .= '<li>' . $custom_title . ': ' . $pretty_value . '</li>';
                        }
                    }
                    $all_packages .= '</ul>
                        </div>
                    </label>
                </div>';
                }
            }
        }

        if(isset($adforest_theme['adforest_ad_posting_mode']) && $adforest_theme['adforest_ad_posting_mode'] == 'free') {
            wp_send_json_success([]);
        } else {
            if ($Ads_packages != "") {
                $response_data = [
                    'selected_packages' => (isset($_POST['is_update']) && !empty($_POST['is_update'])) ? "" : $Ads_packages,
                    'ppp_free_available' => ($pay_per_post && $ppp_free_toggle && $user_free_remaining > 0 && empty($is_update)),
                    'ppp_free_remaining' => ($pay_per_post && $ppp_free_toggle) ? $user_free_remaining : 0,
                ];
                wp_send_json_success($response_data);
            } else {
                $response_data = [
                    'selected_packages' => (isset($_POST['is_update']) && !empty($_POST['is_update'])) ? "" : $all_packages,
                    'ppp_free_available' => ($pay_per_post && $ppp_free_toggle && $user_free_remaining > 0 && empty($is_update)),
                    'ppp_free_remaining' => ($pay_per_post && $ppp_free_toggle) ? $user_free_remaining : 0,
                ];
                wp_send_json_success($response_data);
            }
        }
    }
}

/**
 * Prepare default package details.
 */

if (!function_exists('prepare_default_packages')) {
    function prepare_default_packages()
    {
        $sb_simple_ads = get_user_meta(get_current_user_id(), '_sb_simple_ads', true);
        $sb_featured_ads = get_user_meta(get_current_user_id(), '_sb_featured_ads', true);
        $sb_expire_ads = get_user_meta(get_current_user_id(), '_sb_expire_ads', true);
        $package_ad_expiry_days = get_user_meta(get_current_user_id(), 'package_ad_expiry_days', true);
        $package_adFeatured_expiry_days = get_user_meta(get_current_user_id(), 'package_adFeatured_expiry_days', true);
        $sb_bump_ads = get_user_meta(get_current_user_id(), '_sb_bump_ads', true);
        $num_of_images = get_user_meta(get_current_user_id(), '_sb_num_of_images', true);
        $video_links = get_user_meta(get_current_user_id(), '_sb_video_links', true);
        $allow_tags = get_user_meta(get_current_user_id(), '_sb_allow_tags', true);
        $allow_bidding = get_user_meta(get_current_user_id(), '_sb_allow_bidding', true);

        if ($sb_expire_ads === '-1' || $sb_expire_ads > current_time('Y-m-d')) {
            return [
                '0' => [
                    'free_ads' => $sb_simple_ads,
                    'featured_ads' => $sb_featured_ads,
                    'pkg_expiry_days' => $sb_expire_ads,
                    'ad_expiry_days' => $package_ad_expiry_days,
                    'featured_expiry_days' => $package_adFeatured_expiry_days,
                    'bump_ads' => $sb_bump_ads,
                    'num_of_images' => $num_of_images,
                    'video_links' => $video_links,
                    'allow_tags' => $allow_tags,
                    'allow_bidding' => $allow_bidding,
                ]
            ];
        }
        return [];
    }
}

/**
 * Prepare package details for the selected category.
 */
if (!function_exists('prepare_category_packages')) {
    function prepare_category_packages($cat_id, $selected_categories)
    {
        $selected_packages = checkCategoryAndSavePackages($selected_categories, $cat_id);
        $default_packages = prepare_default_packages();

        if (is_array($selected_packages) && is_array($default_packages)) {
            $selected_packages = $default_packages + $selected_packages;
        } else {
            $selected_packages = $default_packages;
        }

        $Ads_packages = '';
        foreach ($selected_packages as $key => $values) {
            $pkg_expiry_date = isset($values['pkg_expiry_days']) ? $values['pkg_expiry_days'] : '';
            $is_expired = ($pkg_expiry_date !== '-1' && $pkg_expiry_date < current_time('Y-m-d'));
            if ($is_expired) {
                continue;
            }

            $product_title = ($key == 0)
                ? __("Default", "adforest")
                : wc_get_product($key)->get_title();

            $json_data = esc_attr(wp_json_encode($values));

            $Pkg_radio = '<input class="form-check-input ads-package-radio" '
                . 'type="radio" '
                . 'name="ads_package" '
                . 'id="ads_package_' . esc_attr($key) . '" '
                . 'value="' . esc_attr($key) . '" '
                . 'data-package="' . $json_data . '" '
                . 'required>';

            $Ads_packages .= '<div class="package-card">
                        <label for="ads_package_' . $key . '">
                            <div class="card-content">
                                <div class="r-meta">
                                    ' . $Pkg_radio . '
                                    <span class="product-title">' . $product_title . '</span>
                                </div>
                                <ul class="package-details">';
            foreach ($values as $detailName => $detailValue) {
                if (in_array($detailName, ['free_ads', 'featured_ads', 'pkg_expiry_days', 'video_links', 'allow_bidding', 'allow_tags']) && !empty($detailValue)) {
                    $custom_titles = [
                        'free_ads' => __('Free Ads', "adforest"),
                        'featured_ads' => __('Featured Ads', "adforest"),
                        'pkg_expiry_days' => __('Package Expiry Days', "adforest"),
                        'video_links' => __('Video Allowed', "adforest"),
                        'allow_bidding' => __('Bidding Allowed', "adforest"),
                        'allow_tags' => __('Tags Allowed', "adforest"),
                    ];
                    $custom_title = $custom_titles[$detailName] ?? $detailName;
                    if ($detailName === 'pkg_expiry_days' && strtotime($detailValue)) {
                        $pretty_value = date_i18n(get_option('date_format'), strtotime($detailValue));
                    } else {
                        $pretty_value = ($detailValue == "-1") ? __("Unlimited", "adforest") : $detailValue;
                    }

                    if($pretty_value == 'no') {
                        $pretty_value = __("No", "adforest");
                    }
                    
                    if($pretty_value == 'yes') {
                        $pretty_value = __("Yes", "adforest");
                    }

                    $Ads_packages .= '<li>' . esc_html($custom_title) . ': ' . esc_html($pretty_value) . '</li>';
                }
            }
            $Ads_packages .= '</ul>
                            </div>
                        </label>
                    </div>';
        }

        return $Ads_packages;
    }
}

if (!function_exists('adforest_category_has_parent')) {
    function adforest_category_has_parent($catid)
    {
        $category = get_term($catid);
        if ($category->parent > 0) {
            return true;
        }
        return false;
    }
}

if (!function_exists('checkCategoryAndSavePackages')) {
    function checkCategoryAndSavePackages($selected_categories, $cat_id)
    {
        $packages = array();
        foreach ($selected_categories as $key => $value) {
            if ((isset($value['allow_cate']) && in_array($cat_id, explode(',', $value['allow_cate'])))) {
                $packages[$key] = $value;
            }
        }

        return $packages;
    }
}

if (!function_exists('adforest_check_category_pay_per_post_package')) {
    function adforest_check_category_pay_per_post_package($cat_id): array
    {
        $category_pkg_args = array(
            'post_type' => 'product',
            'fields' => 'ids',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'adforest_pay_per_post_pkgs',
                ),
            ),
            'posts_per_page' => -1,
        );

        $package_ids = get_posts($category_pkg_args);

        if (empty($package_ids)) {
            return ['found' => 0, 'reason' => 'pkg_not_found'];
        }

        foreach ($package_ids as $package_id) {
            $package_cats = get_post_meta($package_id, 'adforest_package_cats', true);

            if (!empty($package_cats)) {
                $package_cats = maybe_unserialize($package_cats);

                if (is_array($package_cats) && in_array($cat_id, $package_cats)) {
                    return ['found' => 1];
                }
            }
        }

        return ['found' => 0, 'reason' => 'cat_not_found'];
    }
}

// ADS rating delete
add_action('wp_ajax_ads_rating_delete', 'adforest_ads_rating_delete');
add_action('wp_ajax_nopriv_ads_rating_delete', 'adforest_ads_rating_delete');
if (!function_exists('adforest_ads_rating_delete')) {
    function adforest_ads_rating_delete()
    {
        check_ajax_referer('adforest_ads_rating_delete_nonce', 'security');
        $ad_id = $_POST['ad_id'];
        $comment_id = $_POST['comment_id'];
        $sender_id = get_current_user_id();
        $check_comment = get_user_meta($sender_id, 'ad_ratting_' . $sender_id . '_' . $ad_id);
        if ($check_comment != "") {

            wp_delete_comment($comment_id);
            delete_user_meta($sender_id, 'ad_ratting_' . $sender_id . '_' . $ad_id);

            echo '1|' . __("Ads Rating delete Successfully", 'adforest');
            die();
        } else {
            echo '0|' . __("You are not allowed to delete this", 'adforest');
        }
    }
}

add_action('wp_ajax_sb_reset_password', 'adforest_reset_password');
add_action('wp_ajax_nopriv_sb_reset_password', 'adforest_reset_password');
// Reset Password
if (!function_exists('adforest_reset_password')) {
    function adforest_reset_password()
    {
        global $adforest_theme;

        $params = [];
        parse_str(wp_unslash($_POST['sb_data']), $params);

        check_ajax_referer('sb_reset_pass_secure', 'security');

        $token = $params['token'];
        $token_arr = explode('-sb-uid-', $token);
        $key = $token_arr[0];
        $uid = intval($token_arr[1]);

        $saved_token = get_user_meta($uid, 'sb_password_forget_token', true);

        if ($saved_token != $key) {
            echo '0|' . __("Unauthorized access.", 'adforest');
            die();
        }

        $token_db = get_user_meta($uid, 'sb_password_forget_token', true);
        $expiry = get_user_meta($uid, 'sb_password_forget_token_expiry', true);
        if (empty($token_db) || !hash_equals($token_db, $key) || current_time('timestamp') > $expiry) {
            echo '0|' . __("Invalid or expired security token.", 'adforest');
            die();
        }

        $new_password = $params['sb_new_password'];
        wp_set_password($new_password, $uid);
        delete_user_meta($uid, 'sb_password_forget_token');
        delete_user_meta($uid, 'sb_password_forget_token_expiry');

        echo '1|' . __("Password Changed successfully.", 'adforest');
        die();
    }
}

// Check Notification
add_action('wp_ajax_sb_check_messages', 'adforest_check_messages');
if (!function_exists('adforest_check_messages')) {
    function adforest_check_messages()
    {
        check_ajax_referer('adforest_check_messages_nonce', 'security');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }
        adforest_authenticate_check();

        $current_msgs = isset($_POST['new_msgs']) ? intval(wp_unslash($_POST['new_msgs'])) : 0;
        global $wpdb;

        $current_user_id = get_current_user_id();
        $unread_msgs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sb_chat_messages WHERE receiver_id = %d AND read_status = 0", $current_user_id));

        if ($unread_msgs > 0) {
            global $adforest_theme;
            echo '1|' . str_replace('%count%', $unread_msgs, $adforest_theme['msg_notification_text']) . '|' . $unread_msgs;
        }
        die();
    }
}

/* Rearrange images */
add_action('wp_ajax_sb_sort_images', 'adforest_sort_images');
if (!function_exists('adforest_sort_images')) {

    function adforest_sort_images()
    {
        check_ajax_referer('adforest_sort_images_nonce', 'security');
        update_post_meta($_POST['ad_id'], '_sb_photo_arrangement_', $_POST['ids']);
        die();
    }
}

function adforest_get_child_terms()
{
    check_ajax_referer('adforest_get_child_terms_nonce', 'security');
    $parent = isset($_POST['parent']) ? intval($_POST['parent']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';

    if (empty($taxonomy)) {
        wp_send_json_error();
    }

    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'parent' => $parent,
        'hide_empty' => false,
    ));

    if (!is_wp_error($terms)) {
        wp_send_json_success($terms);
    }
    wp_send_json_error();
}

add_action('wp_ajax_get_child_terms', 'adforest_get_child_terms');
add_action('wp_ajax_nopriv_get_child_terms', 'adforest_get_child_terms');

add_action('wp_ajax_feature_ad_posting', 'adforest_make_featured_bump_func');
add_action('wp_ajax_bumup_ad_posting', 'adforest_make_featured_bump_func');
add_action('wp_ajax_pay_per_post_ad_posting', 'adforest_make_featured_bump_func');

if (!function_exists('adforest_make_featured_bump_func')) {
    function adforest_make_featured_bump_func()
    {

        //wp_conoce
        $package_id = isset($_POST['package_id']) ? $_POST['package_id'] : "";

        $featured_id = (isset($_POST['featured_id']) && $_POST['featured_id'] != "") ? (int)$_POST['featured_id'] : "";
        $bump_up_id = (isset($_POST['bump_up_id']) && $_POST['bump_up_id'] != "") ? (int)$_POST['bump_up_id'] : "";
        $pay_per_post_id = (isset($_POST['pay_per_post_id']) && $_POST['pay_per_post_id'] != "") ? (int)$_POST['pay_per_post_id'] : "";

        if ($package_id != "" && $featured_id != "" || $bump_up_id != "" || $pay_per_post_id != "") {
            $custom_data = array();

            if ($featured_id != "") {
                $custom_data['sb_package_featured_post_id'] = $featured_id;
                check_ajax_referer('sb_post_feature_secure', 'security');
                $adforest_pay_per_post_data['adforest_make_featured_bump'] = $custom_data;
            }

            if ($bump_up_id != "") {
                $custom_data['sb_package_bump_post_id'] = $bump_up_id;
                check_ajax_referer('sb_post_bumpup_secure', 'security');
                $adforest_pay_per_post_data['adforest_make_featured_bump'] = $custom_data;
            }

            if ($pay_per_post_id != "") {
                $custom_data['sb_pay_per_post_id'] = $pay_per_post_id;
                check_ajax_referer('sb_post_ppp_secure', 'security');
                $adforest_pay_per_post_data['adforest_pay_per_post_data'] = $custom_data;
            }

            WC()->cart->add_to_cart($package_id, 1, 0, array(), $adforest_pay_per_post_data);
            $redirect_url = wc_get_cart_url();
            if ($redirect_url) {
                wp_send_json_success(array("message" => __("Added to cart.", 'adforest'), 'url' => $redirect_url));
            } else {
                wp_send_json_success(array("message" => __("Something went wrong.", 'adforest'), 'url' => ''));
            }
        } else {
            wp_send_json_success(array("message" => __("Something went wrong.", 'adforest'), 'url' => ''));
        }
    }
}

if (!function_exists('adforest_pay_per_post_oreder_data_func')) {
    function adforest_pay_per_post_oreder_data_func($item, $cart_item_key, $values, $order)
    {
        if (isset($values['adforest_pay_per_post_data'])) {
            $item->add_meta_data('sb_pay_per_post_id', $values['adforest_pay_per_post_data']['sb_pay_per_post_id']);
        }
        if (isset($values['adforest_make_featured_bump']) && $values['adforest_make_featured_bump']['sb_package_featured_post_id'] != "") {
            $item->add_meta_data('sb_package_featured_post_id', $values['adforest_make_featured_bump']['sb_package_featured_post_id']);
        }
        if (isset($values['adforest_make_featured_bump']) && $values['adforest_make_featured_bump']['sb_package_bump_post_id'] != "") {
            $item->add_meta_data('sb_package_bump_post_id', $values['adforest_make_featured_bump']['sb_package_bump_post_id']);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'adforest_pay_per_post_oreder_data_func', 10, 4);

if (!function_exists('sb_packages_product_data_updating_on_completion')) {
    function sb_packages_product_data_updating_on_completion($order_id)
    {

        $post_id = '';
        $post_bump_id = '';
        $pay_post_id = '';
        $cat_id = '';
        $product_id = '';
        $order = new WC_Order($order_id);
        $items = $order->get_items();
        if (count((array)$items) > 0) {
            foreach ($items as $key => $item) {

                $product_id = $item->get_product_id();
                $pay_post_id = $item->get_meta('sb_pay_per_post_id');
                $featured_post_id = $item->get_meta('sb_package_featured_post_id');
                $post_bump_id = $item->get_meta('sb_package_bump_post_id');

                if ($featured_post_id != "") {
                    update_post_meta($featured_post_id, '_adforest_is_feature', '1');
                    update_post_meta($featured_post_id, '_adforest_is_feature_date', current_time('Y-m-d'));
                    $package_adFeatured_expiry_days = get_post_meta($product_id, 'package_adFeatured_expiry_days', true);
                    if ($package_adFeatured_expiry_days != "") {

                        update_post_meta($featured_post_id, 'package_adFeatured_expiry_days', $package_adFeatured_expiry_days);
                    }
                } else {
                    update_post_meta($featured_post_id, '_adforest_is_feature', 0);
                }

                if ($post_bump_id != "") {
                    wp_update_post(
                        array(
                            'ID' => $post_bump_id,
                            'post_date' => current_time('mysql'),
                            'post_type' => 'ad_post',
                            'post_status' => 'publish',
                        )
                    );
                }

                if ($pay_post_id != "") {
                    $pay_post_ad_expiry_days = get_post_meta($product_id, "pay_post_ad_expiry_days", true);
                    update_post_meta($pay_post_id, 'package_ad_expiry_days', $pay_post_ad_expiry_days);
                    wp_update_post(
                        array(
                            'ID' => $pay_post_id, // ID of the post to update
                            'post_date' => current_time('mysql'),
                            'post_type' => 'ad_post',
                            'post_status' => 'publish',
                        )
                    );
                }
            }
        }
    }
}
add_action('woocommerce_order_status_completed', 'sb_packages_product_data_updating_on_completion');


add_action('wp_ajax_adforest_term_autocomplete', 'adforest_term_autocomplete_callback');
if (!function_exists('adforest_term_autocomplete_callback')) {
    function adforest_term_autocomplete_callback()
    {
        $result = array();
        $args = array('hide_empty' => 0);
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $terms = get_terms('ad_cats', $args);
        $cats_html = '';
        $cats_html .= '<ul class="sb-admin-dropdown">';
        if (count($terms) > 0) {
            foreach ($terms as $term) {
                $total_posts = get_term_meta($term->term_id, '_adforest_term_count', true);
                if ($total_posts == 0) {
                    $total_posts = isset($term->count) ? $term->count : 0;
                }
                $count = isset($total_posts) && $total_posts > 0 ? $total_posts : 0;
                $cats_html .= '<li class="sb-select-term" data-sb-term-value="' . $term->term_id . '|' . $term->name . '">' . $term->name . ' (' . urldecode_deep($term->slug) . ')' . ' (' . $count . ') </li>';
            }
        }
        $cats_html .= '</ul>';
        echo json_encode($cats_html);
        wp_die();
    }
}

add_action('wp_ajax_make_ad_featured_admin', 'adforest_make_ad_featured_admin');

if (!function_exists('adforest_make_ad_featured_admin')) {
    function adforest_make_ad_featured_admin() {
        check_ajax_referer('adforest_make_ad_featured_admin_nonce', 'security');
        if (!current_user_can('administrator')) {
            wp_send_json_error(['message' => __('Unauthorized', 'adforest')]);
        }

        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;

        if (!$ad_id) {
            wp_send_json_error(['message' => __('Invalid Ad ID', 'adforest')]);
        }

        global $adforest_theme;

        update_post_meta($ad_id, '_adforest_is_feature', 1);

        $ad_expiry_for_feature = isset($adforest_theme['featured_expiry']) ? intval($adforest_theme['featured_expiry']) : 0;

        if ($ad_expiry_for_feature > 0) {
            $expiry_date = date('Y-m-d H:i:s', strtotime("+{$ad_expiry_for_feature} days", current_time('timestamp')));
            update_post_meta($ad_id, '_adforest_is_feature_date', $expiry_date);
        }

        wp_send_json_success(['message' => __('Ad marked as featured successfully.', 'adforest')]);
    }
}

// Ajax handler for Social login
add_action('wp_ajax_sb_social_login', 'adforest_check_social_user');
add_action('wp_ajax_nopriv_sb_social_login', 'adforest_check_social_user');

if (!function_exists('adforest_check_social_user')) {
    function adforest_check_social_user() {
        global $adforest_theme;
        $is_demo = adforest_is_demo();

        if ($is_demo) {
            echo '0|error|Invalid request|' . __("Not allowed in demo mode", 'adforest');
            die();
        }

        check_ajax_referer('sb_social_login_nonce', 'security');
        $network = (isset($_POST['sb_network'])) ? $_POST['sb_network'] : '';
        $response_response = false;
        $user_name = "";


        if ($network == 'facebook') {
            $access_token = (isset($_POST['access_token'])) ? $_POST['access_token'] : '';
            $token_verify = wp_remote_get("https://graph.facebook.com/me?fields=name,email&access_token=$access_token");

            if (isset($token_verify['response']['code']) && $token_verify['response']['code'] == '200') {
                $info = json_decode($token_verify['body']);
                if (isset($_POST['email']) && isset($info->email) && $info->email == $_POST['email']) {
                    $user_name = $info->email;
                    $response_response = true;
                }
            }
        } else if ($network == 'google') {
            $access_token = (isset($_POST['access_token'])) ? $_POST['access_token'] : '';
            $token_verify = wp_remote_get("https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=$access_token");

            if (isset($token_verify['response']['code']) && $token_verify['response']['code'] == '200') {
                $info = json_decode($token_verify['body']);
                if (isset($_POST['email']) && isset($info->email) && $info->email == $_POST['email']) {
                    $user_name = $info->email;
                    $response_response = true;
                }
            }
        }

        if ($response_response == false) {
            echo '0|error|Invalid request|' . __("Authentication Failed.", 'adforest');
            die();
        }

        unset($_SESSION['sb_nonce']);
        $_SESSION['sb_nonce'] = time();

        if ($user_name == "") {
            echo '1|' . $_SESSION['sb_nonce'] . '|0|' . __("We are unable to get your email.", 'adforest');
            die();
        }

        if (email_exists($user_name)) {
            $user = get_user_by('email', $user_name);
            $user_id = $user->ID;

            if ($user) {
                if (count($user->roles) == 0) {
                    echo '1|' . $_SESSION['sb_nonce'] . '|0|' . __("Your account is not verified yet", 'adforest');
                    die();
                }

                wp_clear_auth_cookie();
                wp_set_current_user($user_id, $user->user_login);
                wp_set_auth_cookie($user_id);

                echo '1|' . $_SESSION['sb_nonce'] . '|1|' . __("You're logged in successfully", 'adforest');
            }
        } else {
            $user_username = explode('@', $user_name);
            $other_errors = adforest_before_register_new_user($user_username[0], $user_name);

            if ($other_errors) {
                echo '0|error|Invalid request|' . $other_errors;
                die();
            }

            // Register new user
            $password = mt_rand(1000, 10000);
            $uid = adforest_do_register($user_name, $password);

            if (filter_var($uid, FILTER_VALIDATE_INT) === false) {
                echo '0|error|Invalid request|' . __("Something went wrong.", 'adforest');
            } else {
                global $adforest;


                if (function_exists('adforest_email_on_new_social_user')) {
                    adforest_email_on_new_social_user($uid, $password);
                }

                $user_role = $adforest_theme['sb_user_role_on_registeration'] ?? 'none';
                if ($user_role == 'dealer') {
                    update_user_meta($uid, '_sb_user_type', 'Dealer');
                } else if ($user_role == 'individual') {
                    update_user_meta($uid, '_sb_user_type', 'Individual');
                }

                if (isset($adforest_theme['sb_allow_pkg_on_reg']) && $adforest_theme['sb_allow_pkg_on_reg'] == '1') {
                    $sb_allow_pkg_on_reg = true;
                    $package_to_assign = $adforest_theme['sb_register_package'] ?? '';
                    if (!empty($package_to_assign) && function_exists('adforest_give_user_package_from_admin')) {
                        adforest_give_user_package_from_admin($package_to_assign, $uid, $sb_allow_pkg_on_reg);
                    }
                }

                echo '1|' . $_SESSION['sb_nonce'] . '|1|' . __("You're registered and logged in successfully.", 'adforest');
            }
        }

        die();
    }
}

if (!function_exists('adforest_do_register')) {

    function adforest_do_register($email = '', $password = '')
    {
        global $adforest_theme;
        if (email_exists($email) == false) {
            $user_name = explode('@', $email);
            $u_name = adforest_check_user_name($user_name[0]);
            $uid = wp_create_user($u_name, $password, $email);

            if (is_wp_error($uid)) {
                return $uid->get_error_message(); // for invalid user
            }

            do_action('adforest_subscribe_newsletter_on_regisster', $adforest_theme, $uid);
            wp_update_user(array('ID' => $uid, 'display_name' => $u_name));
            adforest_auto_login($email, $password, true);

            if ($adforest_theme['sb_allow_ads']) {


                update_user_meta($uid, '_sb_simple_ads', $adforest_theme['sb_free_ads_limit']);

                if ($adforest_theme['sb_allow_featured_ads']) {
                    update_user_meta($uid, '_sb_featured_ads', $adforest_theme['sb_featured_ads_limit']);
                }


                if ($adforest_theme['sb_allow_bump_ads']) {
                    update_user_meta($uid, '_sb_bump_ads', $adforest_theme['sb_bump_ads_limit']);
                }

                if ($adforest_theme['simple_ad_removal'] != '') {
                    update_user_meta($uid, 'package_ad_expiry_days', $adforest_theme['simple_ad_removal']);
                }

                if ($adforest_theme['featured_expiry'] != '') {
                    update_user_meta($uid, 'package_adFeatured_expiry_days', $adforest_theme['featured_expiry']);
                }


                if ($adforest_theme['sb_package_validity'] == '-1') {
                    update_user_meta($uid, '_sb_expire_ads', $adforest_theme['sb_package_validity']);
                } else {
                    $days = $adforest_theme['sb_package_validity'];
                    $expiry_date = date('Y-m-d', strtotime("+$days days", current_time('timestamp')));
                    update_user_meta($uid, '_sb_expire_ads', $expiry_date);
                }

                if (isset($adforest_theme['sb_free_events_limit']) && $adforest_theme['sb_free_events_limit'] != "") {
                    update_user_meta($uid, 'number_of_events', $adforest_theme['sb_free_events_limit']);
                }
            } else {
                update_user_meta($uid, '_sb_simple_ads', 0);
                update_user_meta($uid, '_sb_featured_ads', 0);
                update_user_meta($uid, '_sb_bump_ads', 0);
                update_user_meta($uid, '_sb_expire_ads', current_time('Y-m-d'));
            }
            update_user_meta($uid, '_sb_pkg_type', 'free');
            return $uid;
        }
    }
}

// AJAX handler to save package data to user meta
add_action('wp_ajax_save_package_data_to_user_meta', 'adforest_save_package_data_to_user_meta');
if (!function_exists('adforest_save_package_data_to_user_meta')) {
    function adforest_save_package_data_to_user_meta() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'adforest_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $user_id = get_current_user_id();
        $package_data = $_POST['package_data'];
        
        if (isset($package_data['num_of_images'])) {
            update_user_meta($user_id, '_sb_num_of_images', $package_data['num_of_images']);
        }
        
        wp_send_json_success('Package data saved successfully');
    }
}

// AJAX handler to clear package data from user meta
add_action('wp_ajax_clear_package_data_from_user_meta', 'adforest_clear_package_data_from_user_meta');
if (!function_exists('adforest_clear_package_data_from_user_meta')) {
    function adforest_clear_package_data_from_user_meta() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['security'], 'adforest_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Clear package data from user meta
        delete_user_meta($user_id, '_sb_num_of_images');
        delete_user_meta($user_id, '_sb_simple_ads');
        delete_user_meta($user_id, '_sb_featured_ads');
        delete_user_meta($user_id, '_sb_bump_ads');
        delete_user_meta($user_id, '_sb_expire_ads');
        delete_user_meta($user_id, '_sb_video_links');
        delete_user_meta($user_id, '_sb_allow_tags');
        delete_user_meta($user_id, '_sb_allow_bidding');
                
        wp_send_json_success('Package data cleared successfully');
    }
}

// User rating delete
add_action('wp_ajax_sb_delete_user_rating_frontend', 'adforest_sb_rating_delete');
if (!function_exists('adforest_sb_rating_delete')) {
    function adforest_sb_rating_delete()
    {
        check_ajax_referer('adforest_delete_user_rating_frontend_nonce', 'security');
        $author_id = $_POST['user_id'];
        $rator = get_current_user_id();
        $meta_key = "_user_" . $rator;
        $rating = get_user_meta($author_id, $meta_key, true);


        if ($rating != "" || is_super_admin($rator)) {
            delete_user_meta($author_id, $meta_key);
            $ratings = adforest_get_all_ratings($author_id);
            $all_rattings = 0;
            $got = 0;
            if (count($ratings) > 0) {
                foreach ($ratings as $rating) {
                    $data = explode('_separator_', $rating->meta_value);
                    $got = $got + $data[0];
                    $all_rattings++;
                }
                $avg = $got / $all_rattings;
            } else {
                $avg = $rating;
            }
            update_user_meta($author_id, "_adforest_rating_avg", $avg);
            echo '1|' . __("rating deleted  Successfully", 'adforest');
            die();
        } else {
            echo '0|' . __("You can not delete this", 'adforest');
            die();
        }
    }
}

if (!function_exists('adforest_get_header_user_menu_markup')) {
    /**
     * Returns the HTML markup for the header user menu block.
     *
     * @param array $args Optional overrides to adjust page IDs when the helper is reused.
     *
     * @return string
     */
    function adforest_get_header_user_menu_markup($args = array())
    {
        global $adforest_theme;

        $defaults = array(
            'sign_in_page'  => isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : '',
            'sign_up_page'  => isset($adforest_theme['sb_sign_up_page']) ? $adforest_theme['sb_sign_up_page'] : '',
            'profile_page'  => isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '',
            'user_id'       => get_current_user_id(),
        );

        $args = wp_parse_args($args, $defaults);
        $user_id = (int) $args['user_id'];
        $sb_sign_in_page = $args['sign_in_page'];
        $sb_sign_up_page = $args['sign_up_page'];
        $sb_profile_page = $args['profile_page'];

        ob_start();
        if (!is_user_logged_in()) {
            ?>
            <a href="<?php echo esc_url(get_the_permalink($sb_sign_in_page), 'adforest'); ?>"
               class="sign-in"><i class="fas fa-user"></i><?php echo esc_html__("Sign in", "adforest"); ?></a>
            <span class="divider"><?php echo esc_html__("Or", "adforest"); ?></span>
            <a href="<?php echo esc_url(get_the_permalink($sb_sign_up_page), 'adforest'); ?>"
               class="sign-up"><?php echo esc_html__("Register", "adforest"); ?></a>
            <?php
        } else {
            $dp = '';
            if (function_exists('adforest_get_user_dp')) {
                $dp = adforest_get_user_dp($user_id);
            }
            ?>
            <div class="adt-user-avatar">
                <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                   class="login-user">
                    <img class="img-circle" src="<?php echo esc_url($dp); ?>"
                         alt="<?php echo esc_attr__('user profile picture', 'adforest'); ?>" width="32"
                         height="32">
                </a>

                <ul class="dropdown-user-login">
                    <li><a class="user_login_dropdown_text"
                           href="<?php echo get_the_permalink($sb_profile_page); ?>"><i
                                    class="fa fa-user"></i> <?php echo esc_html__("Profile", "adforest"); ?></a>
                    </li>
                    <?php echo apply_filters('adforest_vendor_dashboard_profile', '', $user_id); ?>
                    <?php
                    if (isset($adforest_theme['communication_mode']) && ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'message')) {
                        ?>
                        <li><a class="user_login_dropdown_text"
                               href="<?php echo adforest_set_url_param(trailingslashit(get_the_permalink($sb_profile_page)), 'page_type', 'msg'); ?>"><i
                                            class="fa fa-envelope"></i> <?php echo esc_html__('Messages', 'adforest'); ?>
                                <span class="badge bg-danger"><?php echo esc_html(adforest_get_message_count()); ?></span></a>
                        </li>
                        <?php
                    }
                    if (isset($adforest_theme['sb_cart_in_menu']) && $adforest_theme['sb_cart_in_menu']) {
                        global $woocommerce;
                        ?>
                        <li><a class="user_login_dropdown_text" href="<?php echo wc_get_cart_url(); ?>"><i
                                        class="fa fa-shopping-cart"></i> <?php echo esc_html__('Cart', 'adforest'); ?>
                                <span class="badge bg-danger"><?php echo adforest_return_echo($woocommerce->cart->cart_contents_count); ?></span></a>
                        </li>
                        <?php
                    }
                    ?>

                    <li><a class="user_login_dropdown_text"
                           href="<?php echo wp_logout_url(get_the_permalink($sb_sign_in_page)); ?>"><i
                                    class="fa fa-power-off"></i> <?php echo esc_html__("Logout", "adforest"); ?>
                        </a></li>
                </ul>
            </div>
            <?php
        }

        return ob_get_clean();
    }
}

if (!function_exists('adforest_header_user_menu_shortcode')) {
    /**
     * Shortcode handler for rendering the header user menu inside Elementor or other builders.
     *
     * @return string
     */
    function adforest_header_user_menu_shortcode($atts = array())
    {
        $atts = shortcode_atts(array(
            'sign_in_page' => '',
            'sign_up_page' => '',
            'profile_page' => '',
            'user_id'      => '',
        ), $atts, 'adforest_header_user_menu');

        $args = array_filter(array(
            'sign_in_page' => !empty($atts['sign_in_page']) ? (int) $atts['sign_in_page'] : null,
            'sign_up_page' => !empty($atts['sign_up_page']) ? (int) $atts['sign_up_page'] : null,
            'profile_page' => !empty($atts['profile_page']) ? (int) $atts['profile_page'] : null,
            'user_id'      => !empty($atts['user_id']) ? (int) $atts['user_id'] : null,
        ), function($value) {
            return !is_null($value);
        });

        return adforest_get_header_user_menu_markup($args);
    }
}
add_shortcode('adforest_header_user_menu', 'adforest_header_user_menu_shortcode');
