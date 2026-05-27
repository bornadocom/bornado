<?php
add_action('wp_ajax_sb_fav_remove_ad', 'adforest_sb_fav_remove_ad');
if (!function_exists('adforest_sb_fav_remove_ad')) {
    function adforest_sb_fav_remove_ad()
    {
        adforest_authenticate_check();
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_fav_remove_ad_nonce')) {
            echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
            die();
        }

        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        if ($ad_id <= 0) {
            echo '0|' . esc_html__("Invalid ad.", 'adforest');
            die();
        }

        if (delete_user_meta(get_current_user_id(), '_sb_fav_id_' . $ad_id)) {
            do_action('adforest_wpml_fav_ads_remove', $ad_id);
            echo '1|' . esc_html__("Ad removed successfully.", 'adforest');
        } else {
            echo '0|' . esc_html__("There is some problem, please try again later.", 'adforest');
        }
        die();
    }
}

add_action('wp_ajax_sb_change_password', 'adforest_change_password');
if (!function_exists('adforest_change_password')) {
    function adforest_change_password()
    {
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_profile_reset_pass_secure')) {
            echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
            die();
        }
        adforest_authenticate_check();
        global $adforest_theme;
        // Getting values

        $is_demo = (isset($adforest_theme['is_demo'])) ? $adforest_theme['is_demo'] : false;

        if ($is_demo) {
            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }


        $params = array();
        parse_str(wp_unslash($_POST['sb_data']), $params);
        check_ajax_referer('sb_profile_reset_pass_secure', 'security');
        $current_pass = $params['current_pass'];
        $new_pass = $params['new_pass'];
        $con_new_pass = $params['con_new_pass'];
        if ($current_pass == "" || $new_pass == "" || $con_new_pass == "") {
            echo '0|' . esc_html__("All fields are required.", 'adforest');
            die();
        }
        if ($new_pass != $con_new_pass) {
            echo '0|' . esc_html__("New password not matched.", 'adforest');
            die();
        }
        $user = get_user_by('ID', get_current_user_id());
        if ($user && wp_check_password($current_pass, $user->data->user_pass, $user->ID)) {
            wp_set_password($new_pass, $user->ID);
            echo '1|' . esc_html__("Password changed successfully.", 'adforest');
        } else {
            echo '0|' . esc_html__("Current password not matched.", 'adforest');
        }

        die();
    }
}

add_action('wp_ajax_get_ad_counts', 'get_ad_counts');
add_action('wp_ajax_nopriv_get_ad_counts', 'get_ad_counts');
function get_ad_counts()
{
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'dashboard_graph_nonce')) {
        wp_send_json([
            'success' => false,
            'data' => ["message" =>__("Security Check Failed!", "adforest")]
        ]);
        die();
    }
    $user_id = get_current_user_id();
    $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'yearly';

    $posts = get_posts([
        'author' => $user_id,
        'post_type' => 'ad_post',
        'posts_per_page' => -1
    ]);

    $all_views = [];
    foreach ($posts as $p) {
        $meta = get_post_meta($p->ID, 'daily_ad_post_views', true);
        if (is_array($meta)) {
            foreach ($meta as $date => $cnt) {
                if (!isset($all_views[$date])) {
                    $all_views[$date] = 0;
                }
                $all_views[$date] += intval($cnt);
            }
        }
    }

    $data = [];
    $today = current_time('Y-m-d');
    $ts_today = strtotime($today);

    if ($period === 'yearly') {
        $data = array_fill(0, 12, 0);
        foreach ($all_views as $date => $cnt) {
            $ts = strtotime($date);
            if (date('Y', $ts) === date('Y', $ts_today)) {
                $month = intval(date('n', $ts)) - 1;
                $data[$month] += $cnt;
            }
        }
    } elseif ($period === 'monthly') {
        $days_in_month = intval(date('t', $ts_today));
        $data = array_fill(0, $days_in_month, 0);
        foreach ($all_views as $date => $cnt) {
            $ts = strtotime($date);
            if (date('Y-m', $ts) === date('Y-m', $ts_today)) {
                $day = intval(date('j', $ts)) - 1;
                $data[$day] += $cnt;
            }
        }
    } else {
        $weekday_index = intval(date('N', $ts_today)) - 1;
        $week_start_ts = $ts_today - $weekday_index * DAY_IN_SECONDS;
        $data = array_fill(0, 7, 0);
        foreach ($all_views as $date => $cnt) {
            $ts = strtotime($date);
            if ($ts >= $week_start_ts && $ts < $week_start_ts + 7 * DAY_IN_SECONDS) {
                $offset = intval(floor(($ts - $week_start_ts) / DAY_IN_SECONDS));
                $data[$offset] += $cnt;
            }
        }
    }

    wp_send_json([
        'success' => true,
        'data' => $data
    ]);
}

if (!function_exists('adforest_get_sold_ads_periodically')) {
    function adforest_get_sold_ads_periodically($user_id, $period = 'yearly')
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_date FROM $wpdb->posts 
                 INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
                 WHERE post_type = 'ad_post' 
                   AND post_author = %d 
                   AND post_status = 'draft' 
                   AND meta_key = '_adforest_ad_status_' 
                   AND meta_value = 'sold'",
                $user_id
            )
        );


        $data = [];
        $currentDate = strtotime(current_time('Y-m-d'));

        foreach ($results as $result) {
            $date = strtotime($result->post_date);

            if ($period === 'yearly') {
                $month = date('n', $date);
                $data[$month] = isset($data[$month]) ? $data[$month] + 1 : 1;
            } elseif ($period === 'monthly' && date('Y-m', $date) === date('Y-m', $currentDate)) {
                $day = date('j', $date);
                $data[$day] = isset($data[$day]) ? $data[$day] + 1 : 1;
            } elseif ($period === 'weekly' && date('W', $date) === date('W', $currentDate)) {
                $weekday = date('N', $date);
                $data[$weekday] = isset($data[$weekday]) ? $data[$weekday] + 1 : 1;
            }
        }

        $totalPeriods = ($period === 'yearly') ? 12 : (($period === 'monthly') ? 31 : 7);
        for ($i = 1; $i <= $totalPeriods; $i++) {
            $data[$i] = isset($data[$i]) ? $data[$i] : 0;
        }

        ksort($data);

        return array_values($data);
    }
}

add_action('wp_ajax_upload_user_pic', 'adforest_user_profile_pic');
if (!function_exists('adforest_user_profile_pic')) {

    function adforest_user_profile_pic()
    {
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'upload_user_image_nonce')) {
            echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
            die();
        }
        /* img upload */

        $is_demo = adforest_is_demo();
        if ($is_demo) {

            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }
        $condition_img = 7;
        $img_count = 1;
        if (!empty($_FILES["my_file_upload"])) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            $files = $_FILES["my_file_upload"];
            $attachment_ids = array();
            $attachment_idss = '';

            if ($img_count >= 1) {
                $imgcount = $img_count;
            } else {
                $imgcount = 1;
            }
            $ul_con = '';
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = array(
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key]
                    );

                    $_FILES = array("my_file_upload" => $file);

                    // Allow certain file formats
                    $imageFileType = strtolower(end(explode('.', $file['name'])));
                    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                        echo '0|' . esc_html__("Sorry, only JPG, JPEG, PNG & GIF files are allowed.", 'adforest');
                        die();
                    }

                    // Check file size
                    if ($file['size'] > 2097152) {
                        echo '0|' . esc_html__("Max allowd image size is 2MB", 'adforest');
                        die();
                    }


                    foreach ($_FILES as $file => $array) {

                        if ($imgcount >= $condition_img) {
                            break;
                        }
                        $attach_id = media_handle_upload($file, $post_id);
                        $attachment_ids[] = $attach_id;

                        $image_link = wp_get_attachment_image_src($attach_id, 'adforest-user-profile');
                    }
                    if ($imgcount > $condition_img) {
                        break;
                    }
                    $imgcount++;
                }
            }
        }
        /* img upload */
        $attachment_idss = array_filter($attachment_ids);
        $attachment_idss = implode(',', $attachment_idss);

        $arr = array();
        $arr['attachment_idss'] = $attachment_idss;
        $arr['ul_con'] = $ul_con;

        $uid = get_current_user_id();
        update_user_meta($uid, '_sb_user_pic', $attach_id);
        update_user_meta($uid, '_sb_user_linkedin_pic', '');
        echo '1|' . $image_link[0];
        die();
    }

}

add_action('wp_ajax_adforest_resend_email_verification', 'adforest_resend_email_verification');
if (!function_exists('adforest_resend_email_verification')) {
    function adforest_resend_email_verification()
    {
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'email_verification_resend_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed. Reload the page and try again.', 'adforest')), 401);
        }
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You need to be logged in to continue.', 'adforest')), 403);
        }

        if (adforest_is_demo()) {
            wp_send_json_error(array('message' => esc_html__('Not allowed in demo mode', 'adforest')));
        }

        global $adforest_theme;
        $email_verification_enabled = isset($adforest_theme['sb_new_user_email_verification']) && $adforest_theme['sb_new_user_email_verification'];

        if (!$email_verification_enabled) {
            wp_send_json_error(array('message' => esc_html__('Email verification is currently disabled.', 'adforest')));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user || empty($user->user_email)) {
            wp_send_json_error(array('message' => esc_html__('No email address found for your account.', 'adforest')));
        }

        $verification_status = get_user_meta($user_id, 'sb_user_email_verification_status', true);
        if ($verification_status === 'verified') {
            wp_send_json_success(array('message' => esc_html__('Your email address is already verified.', 'adforest')));
        }

        if (!function_exists('adforest_randomString') || !function_exists('adforest_email_on_new_user')) {
            wp_send_json_error(array('message' => esc_html__('Verification email service is unavailable.', 'adforest')));
        }

        $token = get_user_meta($user_id, 'sb_email_verification_token', true);
        if (empty($token)) {
            $token = adforest_randomString(50);
            update_user_meta($user_id, 'sb_email_verification_token', $token);
        }

        $verification_link = esc_url(home_url()) . '?verification_key=' . $token . '-sb-uid-' . $user_id;

        update_user_meta($user_id, 'sb_user_email_verification_status', 'pending');

        $user_info = get_userdata($user_id);

        $to = $user_info->user_email;
        $subject = $adforest_theme['sb_new_user_message_subject'];
        $from = $adforest_theme['sb_new_user_message_from'];
        $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");
        $user_name = $user_info->user_email;

        $msg_keywords = array('%site_name%', '%user_name%', '%display_name%', '%verification_link%');
        $msg_replaces = array(
            get_bloginfo('name'),
            $user_name,
            $user_info->display_name,
            $verification_link
        );
        $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_new_user_message']);
        wp_mail($to, $subject, $body, $headers);

        wp_send_json_success(array('message' => esc_html__('Verification email has been sent. Please check your inbox.', 'adforest')));
    }
}

add_action('wp_ajax_sb_verification_system', 'adforest_verification_system');
if (!function_exists('adforest_verification_system')) {
    function adforest_verification_system()
    {
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'verify_phone_nonce')) {
            echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
            die();
        }
        global $adforest_theme;
        $ph = sanitize_text_field( wp_unslash( $_POST['sb_phone_numer'] ) );
        if (!preg_match("/\+[0-9]+$/", $ph)) {
            echo '0|' . esc_html__('Please update valid phone number +CountrycodePhonenumber in profile.', 'adforest');
            die();
        }

        $user_id = get_current_user_id();

        if (isset($adforest_theme['sb_resend_code']) && $adforest_theme['sb_resend_code'] != "" && get_user_meta($user_id, '_ph_code_', true) != "") {
            $timeFirst = strtotime(get_user_meta($user_id, '_ph_code_date_', true));
            $timeSecond = current_time('timestamp');
            $differenceInSeconds = $timeSecond - $timeFirst;
            $adforest_theme['sb_resend_code'] . "<" . $differenceInSeconds;
            if ($adforest_theme['sb_resend_code'] > $differenceInSeconds) {
                $after_seconds = $adforest_theme['sb_resend_code'] - $differenceInSeconds;
                echo '0|' . esc_html__("You can't resend the verification code before", 'adforest') . " " . $after_seconds . '-' . esc_html__("seconds.", 'adforest');
                die();
            }
        }

        $code = mt_rand(100000, 500000);
        $res = adforest_send_sms($ph, $code);

        $gateway = adforest_verify_sms_gateway();
        $sms_sent = false;
        if ($gateway == "iletimerkezi-sms" && $res == true) {
            $sms_sent = true;
        }
        if ($gateway == "twilio" && $res->sid) {
            $sms_sent = true;
        }

        if ($sms_sent) {
            //if( true )
            update_user_meta($user_id, '_ph_code_', $code);
            echo esc_html($code);
            update_user_meta($user_id, '_sb_is_ph_verified', '0');
            update_user_meta($user_id, '_ph_code_date_', current_time('mysql'));
            echo '1|' . esc_html__("Verification code has been sent.", 'adforest');
        } else {
            echo '0|' . esc_html__("Server not responding.", 'adforest');
            update_user_meta($user_id, '_sb_is_ph_verified', '0');
        }
        die();
    }
}

if (!function_exists('adforest_send_sms')) {
    function adforest_send_sms($receiver_ph, $code)
    {
        global $adforest_theme;
        $message = esc_html__('Your verification code is', 'adforest') . " " . $code;
        $gateway = adforest_verify_sms_gateway();

        if ($gateway == "iletimerkezi-sms") {
            $ilt_data = get_option('ilt_option');

            $options = ilt_get_options();
            $options['number_to'] = $receiver_ph;
            $options['message']   = $message;

            $args = wp_parse_args($args, $options);
            $is_args_valid = ilt_validate_sms_args($args);

            if (!$is_args_valid) {

                $message     = $args['message']      ?? '';
                $public_key  = $args['public_key']   ?? '';
                $private_key = $args['private_key']  ?? '';
                $sender      = $args['sender']       ?? '';

                $message = apply_filters('ilt_sms_message', $message, $args);

                try {
                    $client = Emarka\Sms\Client::createClient([
                        'api_key' => $public_key,
                        'secret'  => $private_key,
                        'sender'  => $sender,
                    ]);

                    $response = $client->send($receiver_ph, $message);

                    if (!$response) {
                        $is_args_valid = ilt_log_entry_format(
                            esc_html__('[Api Error] Connection error', 'adforest'),
                            $args
                        );
                        $return = false;

                    } else {
                        $is_args_valid = ilt_log_entry_format(
                            sprintf(esc_html__('Success! Message ID: %s', 'adforest'), $response),
                            $args
                        );
                        $return = true;
                    }

                } catch (\Exception $e) {

                    $is_args_valid = ilt_log_entry_format(
                        sprintf(esc_html__('[Api Error] %s ', 'adforest'), $e->getMessage()),
                        $args
                    );
                    $return = false;
                }

            } else {
                $return = false;
            }

            ilt_update_logs($is_args_valid, $args['logging']);

            return $return;
        }

        if ($gateway == "twilio") {
            $twl_data = get_option('twl_option');

            $account_sid = $twl_data['account_sid'];
            $auth_token = $twl_data['auth_token'];
            $twilio_phone_number = $twl_data['number_from'];
            $twilio_whatsapp_number = $adforest_theme['sb_twilio_whatsapp_number'];

            $client = new Twilio\Rest\Client($account_sid, $auth_token);
            try {
                if (isset($adforest_theme['sb_verify_whatsapp']) && $adforest_theme['sb_verify_whatsapp']) {
                    $response = $client->messages->create(
                        "whatsapp:" . $receiver_ph,
                        array(
                            "from" => "whatsapp:" . $twilio_whatsapp_number,
                            "body" => "Your verification code is:" . $code,
                        )
                    );
                } else {
                    $response = $client->messages->create($receiver_ph, array("from" => $twilio_phone_number, "body" => $message));
                }

                if ($response->sid) {
                    return $response;
                } else {
                    echo '0|' . esc_html__('Message not sent. Please check your Twilio configuration.', 'adforest');
                    die();
                }
            } catch (\Exception $e) {
                echo '0|' . $e->getMessage();
                die();
            }

        }
    }

}

// Ajax hander for update profile processing
add_action('wp_ajax_sb_update_profile', 'adforest_profile_update_ajax_processed');
if (!function_exists('adforest_profile_update_ajax_processed')) {
    function adforest_profile_update_ajax_processed()
    {
        // Getting values

        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo esc_html__('Not allowed in demo mode', 'adforest');
            die();
        }


        $params = array();
        parse_str(wp_unslash($_POST['sb_data']), $params);
        check_ajax_referer('sb_profile_secure', 'security');

        $uid = get_current_user_id();
        $email = (isset($params['user_email']) && $params['user_email'] != "") ? $params['user_email'] : "";
        global $adforest_theme;
        $sms_gateway = adforest_verify_sms_gateway();
        if ($sms_gateway != "") {

        }
        $ph_num = sanitize_text_field($params['sb_user_contact']);
        if (!preg_match("/\+[0-9]+$/", $ph_num)) {
            echo esc_html__('Please enter valid phone number +CountrycodePhonenumber', 'adforest');
            die();
        }
        $saved_ph = get_user_meta($uid, '_sb_contact', true);
        if ($saved_ph != $ph_num) {
            update_user_meta($uid, '_sb_is_ph_verified', '0');
        }
        /* if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification'] && in_array('wp-twilio-core/core.php', apply_filters('active_plugins', get_option('active_plugins')))) {
          $ph_num = sanitize_text_field($params['sb_user_contact']);
          if (!preg_match("/\+[0-9]+$/", $ph_num)) {
          echo esc_html__('Please enter valid phone number +CountrycodePhonenumber', 'adforest');
          die();
          }

          $saved_ph = get_user_meta($uid, '_sb_contact', true);
          if ($saved_ph != $ph_num) {
          update_user_meta($uid, '_sb_is_ph_verified', '0');
          }
          } */
        wp_update_user(array('ID' => $uid, 'display_name' => sanitize_text_field($params['sb_user_name'])));
        update_user_meta($uid, '_sb_address', sanitize_text_field($params['sb_user_address']));
        update_user_meta($uid, '_sb_user_type', sanitize_text_field($params['sb_user_type']));
        update_user_meta($uid, '_sb_user_intro', sanitize_textarea_field($params['sb_user_intro']));
        update_user_meta($uid, '_sb_user_whatsapp_intro', sanitize_textarea_field($params['sb_user_whatsapp_intro']));
        $sb_disable_linkedin_edit = isset($adforest_theme['sb_disable_linkedin_edit']) && $adforest_theme['sb_disable_linkedin_edit'] ? TRUE : FALSE;
        $profiles = adforest_social_profiles();
        foreach ($profiles as $key => $value) {
            if ($key == 'linkedin' && $sb_disable_linkedin_edit) {
                continue;
            }
            update_user_meta($uid, '_sb_profile_' . $key, sanitize_textarea_field($params['_sb_profile_' . $key]));
        }
        do_action('adforest_directory_update_profile_opening_hours', $uid, $params);
        if ($email != "") {
            $args = array(
                'ID' => $uid,
                'user_email' => $email,
            );
            $update = wp_update_user($args);
            if (is_wp_error($update)) {
                echo adforest_return_echo($update->get_error_message());
                die();
            } else {
                echo '1';
                die();
            }
        }


        if (isset($params['sb_user_contact']) && $params['sb_user_contact'] != "") {
            global $wpdb;
            $user_contact = sanitize_text_field($params['sb_user_contact']);
            $query_user = $wpdb->prepare(
                "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s",
                '_sb_contact',
                $user_contact
            );
            $result = $wpdb->get_results($query_user);

            if (is_array($result) && isset($result[0]->user_id) && $result[0]->user_id != $uid) {
                echo esc_html__('Phone Number already registered', 'adforest');
                die();
            }


            update_user_meta($uid, '_sb_contact', $user_contact);

        }
        echo '1';
        die();
    }
}

function load_more_ads_ajax_handler()
{
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'load_more_ads_nonce')) {
        echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
        die();
    }
    $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $output = display_recently_viewed_ad_posts($paged);

    if ($paged == 1) {
        echo wp_kses_post($output);
    } else {
        preg_match('/<tbody>(.*?)<\/tbody>/s', $output, $matches);
        echo wp_kses_post($matches[1]);
    }

    wp_die();
}

add_action('wp_ajax_load_more_ads_dashboard_table', 'load_more_ads_ajax_handler');
add_action('wp_ajax_nopriv_load_more_ads_dashboard_table', 'load_more_ads_ajax_handler');
function adforest_load_more_dashboard_ads()
{
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'load_more_ads_nonce')) {
        echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
        die();
    }
    $user_id = get_current_user_id();
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $ad_type = isset($_POST['ad_type']) ? $_POST['ad_type'] : "";

    $args = [];
    if ($ad_type != "" && $ad_type == 'my_ads') {
        $args = array(
            'post_type' => 'ad_post',
            'author' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged,
            'order' => 'DESC',
            'orderby' => 'date'
        );
    } elseif ($ad_type != "" && $ad_type == 'featured_ads') {
        $args = array(
            'post_type' => 'ad_post',
            'author' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'meta_key' => '_adforest_is_feature',
            'meta_value' => '1',
            'paged' => $paged,
            'order' => 'DESC',
            'orderby' => 'ID'
        );
    } elseif ($ad_type != "" && $ad_type == 'rejected_ads') {
        $args = array(
            'post_type' => 'ad_post',
            'author' => $user_id,
            'post_status' => 'rejected',
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged,
            'order' => 'DESC',
            'orderby' => 'ID'
        );
    } elseif ($ad_type != "" && $ad_type == 'fav_ads') {
        global $wpdb;
        $uid = get_current_user_id();
        $fav_like = $wpdb->esc_like('_sb_fav_id_') . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
                $uid,
                $fav_like
            )
        );
        $pids = array(0);
        foreach ($rows as $row) {
            $pids[] = $row->meta_value;
        }
        $args = array(
            'post_type' => 'ad_post',
            'post__in' => $pids,
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged,
            'order' => 'DESC',
            'orderby' => 'date'
        );
    } elseif ($ad_type != "" && $ad_type == 'inactive_ads') {
        $args = array(
            'post_type' => 'ad_post',
            'author' => $user_id,
            'post_status' => array('pending'),
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged,
            'order' => 'DESC',
            'orderby' => 'ID'
        );
    } elseif ($ad_type != "" && $ad_type == 'expired_ads') {
        global $adforest_theme;
        $user_id = get_current_user_id();
        $args = array(
            'post_type' => 'ad_post',
            'author' => $user_id,
            'post_status' => array('draft'),
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged,
            'order' => 'DESC',
            'orderby' => 'ID'
        );

        $after_expired_ads = isset($adforest_theme['after_expired_ads']) ? $adforest_theme['after_expired_ads'] : "";
        if ($after_expired_ads == "published") {
            $args = array(
                'post_type' => 'ad_post',
                'author' => $user_id,
                'post_status' => array('draft', 'publish'),
                'posts_per_page' => get_option('posts_per_page'),
                'paged' => $paged,
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_adforest_ad_status_',
                        'value' => 'expired',
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_adforest_ad_status_',
                        'value' => 'sold',
                        'compare' => '=',
                    ),
                )
            );
        }
    }

    $query = new WP_Query($args);

    if ($query->have_posts()):
        while ($query->have_posts()):
            $query->the_post();
            $ad_details = get_ad_post_details(get_the_ID());
            $first_img = $ad_details['img'];
            $title = $ad_details['ad_title'];
            $price = $ad_details['price'];
            $ad_permalink = $ad_details['ad_link'];
            $post_status = get_post_status(get_the_ID());
            $status_label = ucfirst($post_status);
            ?>
            <tr>
                <td>
                    <div class="product">
                        <div class="image">
                            <img src="<?php echo esc_url($first_img); ?>" alt="<?php echo esc_attr($title); ?>" />
                        </div>
                        <p class="text-sm"><?php echo esc_html($title); ?></p>
                    </div>
                </td>
                <td>
                    <div class="table-price-dash">
                        <?php echo adforest_adPrice(get_the_ID(), 'negotiable', 'p'); ?>
                    </div>
                </td>
                <td>
                    <?php
                    $status_labels = array(
                        'publish' => __('Published', 'adforest'),
                        'pending' => __('Pending Review', 'adforest'),
                        'draft' => __('Draft', 'adforest'),
                        'trash' => __('Trash', 'adforest'),
                        'private' => __('Private', 'adforest'),
                        'future' => __('Scheduled', 'adforest'),
                    );

                    if (isset($status_labels[$post_status])) {
                        $status_label = $status_labels[$post_status];
                    } elseif ($obj = get_post_status_object($post_status)) {
                        $status_label = $obj->label;
                    } else {
                        $status_label = ucfirst($post_status);
                    }
                    ?>
                    <span class="status-btn <?php echo esc_attr($post_status); ?>-btn">
                        <?php echo esc_html($status_label); ?>
                    </span>
                </td>
                <td>
                    <div class="action justify-content-end">
                        <?php
                        global $adforest_theme;
                        $sb_post_ad_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page']);
                        $ad_update_url = adforest_set_url_param(get_the_permalink($sb_post_ad_page), 'id', get_the_ID());
                        $bump_ads = get_user_meta(get_current_user_id(), '_sb_bump_ads', true);
                        $bump_up_ads_class = '';
                        if ($bump_ads > 0 || $bump_ads == '-1' || (isset($adforest_theme['sb_allow_free_bump_up']) && $adforest_theme['sb_allow_free_bump_up'])) {
                            $bump_up_ads_class = 'bump_it_up_new_pkg';
                        } else {
                            $bump_up_ads_class = 'bump_it_up_new_pkg';
                        }

                        $featured_ads = get_user_meta(get_current_user_id(), '_sb_featured_ads', true);
                        $sb_expire_ads = get_user_meta(get_current_user_id(), '_sb_expire_ads', true);
                        if ($featured_ads != 0 && $featured_ads != "" && ($sb_expire_ads != '-1' || $sb_expire_ads < current_time('Y-m-d'))) {
                            $ad_featured = 'sb_make_feature_ad_new_pkg';
                        } else {
                            $ad_featured = 'sb_make_feature_ad_new_pkg';
                        }
                        $is_featured = get_post_meta(get_the_ID(), '_adforest_is_feature', true) == '1';
                        $star_class = $is_featured ? 'lni lni-star-filled' : 'lni lni-star';
                        $link_class = $ad_featured;
                        $inline_style = $is_featured ? 'style="pointer-events: none;"' : '';
                        ?>
                        <div class="ad_action_container d-flex justify-content-center align-items-center gap-4">
                            <a href="javascript:void(0)" class="<?php echo esc_attr($ad_featured); ?>" title="Make Featured"
                                data-aaa-id="<?php echo esc_attr(get_the_ID()); ?>" <?php echo esc_attr($inline_style); ?>>
                                <i class="<?php echo esc_attr($star_class) ?>"></i>
                            </a>
                            <a href="javascript:void(0)" class="<?php echo esc_attr($bump_up_ads_class); ?>" title="Bump Up Ad"
                                data-aaa-id="<?php echo esc_attr(get_the_ID()); ?>">
                                <i class="lni lni-arrow-up-circle"></i>
                            </a>
                            <a href="<?php echo esc_url($ad_update_url); ?>" class="edit" title="Edit Ad">
                                <i class="lni lni-pencil"></i>
                            </a>
                        </div>
                        <button class="more-btn ml-10 dropdown-toggle" id="moreAction<?php echo get_the_ID(); ?>"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="lni lni-more-alt"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="moreAction<?php echo get_the_ID(); ?>">
                            <li class="dropdown-item">
                                <a href="javascript:void(0)" class="text-gray ad_package_info" data-nonce="<?php echo wp_create_nonce('adforest_ad_package_info'); ?>"
                                    data-adid='<?php echo get_the_ID(); ?>'><?php echo esc_html__("Info", "adforest"); ?></a>
                            </li>
                            <li class="dropdown-item">
                                <a href="javascript:void(0)" class="text-gray ad_status" data-adid='<?php echo get_the_ID(); ?>'
                                    data-value="active" data-security="<?php echo esc_attr(wp_create_nonce('sb_update_ad_status_nonce')); ?>"><?php echo esc_html__("Active", "adforest"); ?></a>
                            </li>
                            <li class="dropdown-item">
                                <a href="javascript:void(0)" class="text-gray ad_status" data-adid='<?php echo get_the_ID(); ?>'
                                    data-value="expired" data-security="<?php echo esc_attr(wp_create_nonce('sb_update_ad_status_nonce')); ?>"><?php echo esc_html__("Expire", "adforest"); ?></a>
                            </li>
                            <li class="dropdown-item">
                                <a href="javascript:void(0)" class="text-gray ad_status" data-adid='<?php echo get_the_ID(); ?>'
                                    data-value="sold" data-security="<?php echo esc_attr(wp_create_nonce('sb_update_ad_status_nonce')); ?>"><?php echo esc_html__("Sold", "adforest"); ?></a>
                            </li>
                            <li class="dropdown-item">
                                <a href="javascript:void(0)" class="text-gray remove_ad" data-adid='<?php echo get_the_ID(); ?>'
                                    data-value="expired"><?php echo esc_html__("Delete", "adforest"); ?></a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php
        endwhile;
    else:
        echo 'no_more_posts';
    endif;

    wp_die();
}

add_action('wp_ajax_load_more_dashboard_ads', 'adforest_load_more_dashboard_ads');
add_action('wp_ajax_nopriv_load_more_dashboard_ads', 'adforest_load_more_dashboard_ads');

// Get Ad Info Dashboard
add_action('wp_ajax_sb_get_ad_package_info', 'sb_get_ad_package_info_callback');
if (!function_exists('sb_get_ad_package_info_callback')) {
    function sb_get_ad_package_info_callback()
    {
        global $adforest_theme;

        check_ajax_referer('adforest_ad_package_info', 'nonce');

        $aid = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        if (!$aid) {
            wp_die();
        }

        if (get_post_field('post_author', $aid) != get_current_user_id()) {
            echo esc_html__('Only ad author can view these details', 'adforest');
            wp_die();
        }

        $ad_status = get_post_meta($aid, '_adforest_ad_status_', true);
        if ('expired' === $ad_status) {
            echo esc_html__('This ad is expired', 'adforest');
            wp_die();
        }

        $expiry_days = get_post_meta($aid, 'package_ad_expiry_days', true);
        if ($expiry_days === '') {
            $expiry_days = !empty($adforest_theme['simple_ad_removal'])
                ? $adforest_theme['simple_ad_removal']
                : '-1';
        }

        $orig_date = get_post_meta($aid, '_adforest_original_post_date', true);
        if (!$orig_date) {
            $current_post_date = get_post_field('post_date', $aid);
            update_post_meta($aid, '_adforest_original_post_date', $current_post_date);
            $orig_date = $current_post_date;
        }
        $posted_date = date('Y-m-d', strtotime($orig_date));

        if ($expiry_days === '-1') {
            $expiry_date = esc_html__('Unlimited days', 'adforest');
        } else {
            $expiry_date = date_i18n(get_option('date_format'), strtotime("$posted_date +{$expiry_days} days"));
        }

        $featured_expiry_days = get_post_meta($aid, 'package_adFeatured_expiry_days', true);
        if ($featured_expiry_days === '') {
            $featured_expiry_days = !empty($adforest_theme['featured_expiry'])
                ? $adforest_theme['featured_expiry']
                : '-1';
        }

        if (get_post_meta($aid, '_adforest_is_feature', true) == '1') {
            $featured_date_raw = get_post_meta($aid, '_adforest_is_feature_date', true);
            $featured_date = date_i18n(get_option('date_format'), strtotime($featured_date_raw));
            if ($featured_expiry_days === '-1') {
                $feature_expiry_date = esc_html__('Unlimited days', 'adforest');
            } else {
                $feature_expiry_date = date_i18n(get_option('date_format'), strtotime("$featured_date_raw +{$featured_expiry_days} days"));
            }
        } else {
            $featured_date = esc_html__('Not featured ad', 'adforest');
            $feature_expiry_date = esc_html__('Not featured ad', 'adforest');
        }
        if (!empty($posted_date) && strtotime($posted_date)) {
            $readable = date_i18n(get_option('date_format'), strtotime($posted_date));
        } else {
            $readable = esc_html__('N/A', 'adforest');
        }

        $response = '<ul>';
        $response .= '<li><label>' . esc_html__('Posted date:', 'adforest') . ' </label><span>' . esc_html($readable) . '</span></li>';
        $response .= '<li><label>' . esc_html__('Expiry date:', 'adforest') . ' </label><span>' . esc_html($expiry_date) . '</span></li>';
        $response .= '<li><label>' . esc_html__('Featured date:', 'adforest') . ' </label><span>' . esc_html($featured_date) . '</span></li>';
        $response .= '<li><label>' . esc_html__('Featured expiry date:', 'adforest') . ' </label><span>' . esc_html($feature_expiry_date) . '</span></li>';
        $response .= '</ul>';

        echo $response;
        wp_die();
    }
}

// Update Ad Status
add_action('wp_ajax_sb_update_ad_status', 'adforest_sb_update_ad_status');
if (!function_exists('adforest_sb_update_ad_status')) {
    function adforest_sb_update_ad_status()
    {
        adforest_authenticate_check();
        $security = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';
        if (empty($security) || !wp_verify_nonce($security, 'sb_update_ad_status_nonce')) {
            echo '0|' . esc_html__("Security check failed. Reload the page and try again.", 'adforest');
            die();
        }
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }

        global $adforest_theme;
        $ad_id = isset($_POST['ad_id']) ? absint($_POST['ad_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';
        if (!$ad_id || empty($status)) {
            echo '0|' . esc_html__("Invalid ad data received.", 'adforest');
            die();
        }
        $previous_staus = get_post_meta($ad_id, '_adforest_ad_status_', true);
        if ($previous_staus == $status) {
            $message = esc_html__("Already ", 'adforest') . $previous_staus;
            echo '0| ' . $message;
            die();
        }

        /*if activating from inactive to active bump it up automatically*/

        if ($status == 'active' && $previous_staus != 'active') {

            $user_id = get_current_user_id();

            // Safe integer cast — prevents weird string comparisons when meta stored as text
            $simple_ads = intval(get_user_meta($user_id, '_sb_simple_ads', true));
            $expiry     = get_user_meta($user_id, '_sb_expire_ads', true);

            // --- Package-based reactivation fallback (detection only; consumption below) ---
            // If the global counters say "no package" BUT the user actually has a valid
            // package in adforest_ads_package_details, allow the reactivation. This covers
            // data drift where `_sb_simple_ads` / `_sb_expire_ads` are stale or unset
            // while a real purchased package is still active.
            $has_valid_package = false;
            $valid_pkg_index   = null; // remember which entry to debit if reactivation succeeds
            $pkg_details       = get_user_meta($user_id, 'adforest_ads_package_details', true);
            if (is_array($pkg_details) && !empty($pkg_details)) {
                foreach ($pkg_details as $index => $pkg) {
                    if (!is_array($pkg)) continue;
                    $pkg_expiry    = isset($pkg['pkg_expiry_days']) ? $pkg['pkg_expiry_days'] : '';
                    $pkg_free_ads  = isset($pkg['free_ads'])        ? intval($pkg['free_ads']) : 0;
                    $pkg_unlimited = ($pkg_expiry === '-1' || $pkg_free_ads === -1);
                    $pkg_active    = $pkg_unlimited
                        || ($pkg_expiry && strtotime($pkg_expiry) >= current_time('timestamp'));
                    $pkg_has_ads   = ($pkg_free_ads === -1 || $pkg_free_ads > 0 || $pkg_unlimited);

                    if ($pkg_active && $pkg_has_ads) {
                        $has_valid_package = true;
                        $valid_pkg_index   = $index;
                        break;
                    }
                }
            }

            // Primary check — bypassed when a valid package is detected via fallback
            if ($simple_ads == -1) {
                // unlimited — allowed
            } else if ($simple_ads <= 0 && !$has_valid_package) {
                echo '0|' . esc_html__("Please buy package first to reactivate.", 'adforest');
                die();
            }
            // Fix: strtotime-based expiry comparison (no more string lex compare)
            if ($expiry != '-1' && $expiry !== '' && !$has_valid_package) {
                if (strtotime($expiry) < current_time('timestamp')) {
                    echo '0|' . esc_html__("Please buy package first reactivate.", 'adforest');
                    die();
                }
            }

            wp_update_post(
                array(
                    'ID' => $ad_id, // ID of the post to update
                    'post_date' => current_time('mysql'),
                    'post_type' => 'ad_post',
                    'post_status' => 'publish',
                    'post_date_gmt' => get_gmt_from_date(current_time('mysql'))
                )
            );

            $package_ad_expiry_days = get_user_meta($user_id, 'package_ad_expiry_days', true);
            if ($package_ad_expiry_days != "") {
                update_post_meta($ad_id, 'package_ad_expiry_days', $package_ad_expiry_days);
                if ($simple_ads > 0) {
                    update_user_meta($user_id, '_sb_simple_ads', $simple_ads - 1);
                }
            }

            // --- Package consumption from authoritative store ---
            // If the reactivation used the package_details fallback, debit the specific
            // package entry that authorised it. Unlimited entries (free_ads = -1) are
            // never debited. Limited entries are only debited when free_ads > 0 so the
            // counter can never go negative.
            if ($valid_pkg_index !== null && is_array($pkg_details) && isset($pkg_details[$valid_pkg_index])) {
                $entry_free = isset($pkg_details[$valid_pkg_index]['free_ads'])
                    ? intval($pkg_details[$valid_pkg_index]['free_ads'])
                    : 0;
                if ($entry_free > 0) {
                    $pkg_details[$valid_pkg_index]['free_ads'] = $entry_free - 1;
                    update_user_meta($user_id, 'adforest_ads_package_details', $pkg_details);
                }
            }
        }
        $after_expired_ads = isset($adforest_theme['after_expired_ads']) ? $adforest_theme['after_expired_ads'] : "";
        $after_sold_ads = isset($adforest_theme['after_sold_ads']) ? $adforest_theme['after_sold_ads'] : "";
        $expired = "draft";
        if ($after_expired_ads == 'published') {
            $expired = "publish";
        }
        $sold = "draft";
        if ($after_sold_ads == 'published') {
            $sold = "publish";
        }

        $sb_status_array = array(
            'expired' => $expired,
            'sold' => $sold,
            'active' => 'publish',
        );
        if (!array_key_exists($status, $sb_status_array)) {
            echo '0|' . esc_html__("Invalid status selected.", 'adforest');
            die();
        }
        update_post_meta($ad_id, '_adforest_ad_status_', $status);
        $my_post = array(
            'ID' => $ad_id,
            'post_status' => $sb_status_array[$status],
            'post_type' => 'ad_post',
        );
        wp_update_post($my_post);


        echo '1|' . esc_html__("Updated successfully.", 'adforest');
        die();
    }
}

// Bump it up
add_action('wp_ajax_sb_bump_it_up', 'adforest_bump_it_up');
if (!function_exists('adforest_bump_it_up')) {
    function adforest_bump_it_up()
    {
        check_ajax_referer('sb_bump_it_up_nonce', 'nonce');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            wp_send_json_error(array("message" => esc_html__('Not allowed in demo mode', 'adforest')));
        }
        $ad_id = $_POST['ad_id'];
        $user_id = get_current_user_id();
        $ads_package = $_POST['ads_package'];

        $packageDetails = get_user_meta($user_id, 'adforest_ads_package_details', true);
        $params_ads_package = $ads_package;

        if (isset($packageDetails[$params_ads_package]) && $packageDetails[$params_ads_package] != 0) {
            $package_single = $packageDetails[$params_ads_package];
        }

        $bump_ads_new = isset($package_single['bump_ads']) ? $package_single['bump_ads'] : '';
        $pkg_expiry_date = isset($package_single['pkg_expiry_days']) ? $package_single['pkg_expiry_days'] : '';

        adforest_set_date_timezone();
        if (get_post_field('post_author', $ad_id) == $user_id) {
            global $adforest_theme;
            if (isset($adforest_theme['make_bump_up_paid']) && $adforest_theme['make_bump_up_paid'] == true) {
                $url = get_the_permalink($adforest_theme['sb_bump_up_template_page']);
                $redirect_url = $url . "?pid=" . $ad_id;
                wp_send_json_success(array("message" => esc_html__("Post Bumped up Submit.", 'adforest'), 'url' => $redirect_url));
            } else {
                if ($bump_ads_new > 0 || $bump_ads_new == '-1') {
                    if ($pkg_expiry_date != '-1') {
                        if ($pkg_expiry_date < current_time('Y-m-d')) {
                            wp_send_json_error(array("message" => esc_html__('Your package has expired', 'adforest')));
                        }
                        wp_update_post(
                            array(
                                'ID' => $ad_id, // ID of the post to update
                                'post_date' => current_time('mysql'),
                                'post_type' => 'ad_post',
                                'post_date_gmt' => get_gmt_from_date(current_time('mysql'))
                            )
                        );
                        do_action('adforest_wpml_bumpup_ads', $ad_id);

                        if ($bump_ads_new != '-1') {
                            $bump_ads_new = $bump_ads_new - 1;
                            $package_single['bump_ads'] = $bump_ads_new;
                            $packageDetails[$params_ads_package] = $package_single;
                            update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                            if (function_exists('adforest_add_ad_post_notification')) {
                                adforest_add_ad_post_notification($ad_id, 'bump');
                            }
                            wp_send_json_success(array("message" => esc_html__('Bumped up successfully.', 'adforest')));
                            die();
                        } elseif ($bump_ads_new == '-1') {
                            if (function_exists('adforest_add_ad_post_notification')) {
                                adforest_add_ad_post_notification($ad_id, 'bump');
                            }
                            wp_send_json_success(array("message" => esc_html__('Bumped up successfully.', 'adforest')));
                        }
                    } else {
                        if ($bump_ads_new != '-1') {
                            $bump_ads_new = $bump_ads_new - 1;
                            $package_single['bump_ads'] = $bump_ads_new;
                            $packageDetails[$params_ads_package] = $package_single;
                            update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                            if (function_exists('adforest_add_ad_post_notification')) {
                                adforest_add_ad_post_notification($ad_id, 'bump');
                            }
                            wp_send_json_success(array("message" => esc_html__('Bumped up successfully.', 'adforest')));
                            die();
                        } elseif($bump_ads_new == '-1') {
                            if (function_exists('adforest_add_ad_post_notification')) {
                                adforest_add_ad_post_notification($ad_id, 'bump');
                            }
                            wp_send_json_success(array("message" => esc_html__('Bumped up successfully.', 'adforest')));
                        }
                    }


                } else {
                    wp_send_json_error(array("message" => esc_html__('Buy package to make it bump.', 'adforest')));
                    die();
                }

            }
        } else {
            wp_send_json_error(array("message" => esc_html__('You must be the Ad owner to make it featured.', 'adforest')));
        }

        die();
    }

}

/* Make ad featured  */
add_action('wp_ajax_sb_make_featured', 'adforest_make_featured');
if (!function_exists('adforest_make_featured')) {
    function adforest_make_featured()
    {
        check_ajax_referer('sb_feature_ad_nonce', 'nonce');
        global $adforest_theme;
        $ad_id = $_POST['ad_id'];
        $ads_package = $_POST['ads_package'];

        $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
        $params_ads_package = $ads_package;

        if (isset($packageDetails[$params_ads_package]) && $packageDetails[$params_ads_package] != 0) {
            $package_single = $packageDetails[$params_ads_package];
        }

        $featured_ad_new = isset($package_single['featured_ads']) ? $package_single['featured_ads'] : '';
        $pkg_expiry_date = isset($package_single['pkg_expiry_days']) ? $package_single['pkg_expiry_days'] : '';

        $user_id = get_current_user_id();
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }
        if (get_post_field('post_author', $ad_id) == $user_id) {
            if (get_post_meta($ad_id, '_adforest_is_feature', true) == '1') {
                wp_send_json_error(array("message" => esc_html__("This ad is featured already.", 'adforest')));
            }

            $featured_ads = get_user_meta($user_id, '_sb_featured_ads', true);
            if (isset($adforest_theme['make_feature_paid']) && $adforest_theme['make_feature_paid'] == true && get_post_meta($ad_id, '_adforest_is_feature', true) != "1") {
                $url = get_the_permalink($adforest_theme['sb_feature_template_page']);
                // ITHY
                $redirect_url = $url . "?pid=" . $ad_id;
                wp_send_json_success(array("message" => esc_html__("Redirecting....", 'adforest'), 'url' => $redirect_url));
                die();
            } else {
                if (!isset($packageDetails[$params_ads_package]) || $packageDetails[$params_ads_package] == 0) {
                    if ($featured_ads != 0 && $featured_ads != "") {

                        if (get_user_meta($user_id, '_sb_expire_ads', true) != '-1') {
                            if (get_user_meta($user_id, '_sb_expire_ads', true) < current_time('Y-m-d')) {
                                wp_send_json_error(array("message" => esc_html__("Your package has expired", 'adforest')));
                                // echo '0|' . esc_html__("Your package has expired", 'adforest');
                                die();
                            }
                        }
                        $feature_ads = get_user_meta($user_id, '_sb_featured_ads', true);
                        $feature_ads = (int) $feature_ads - 1;
                        update_user_meta($user_id, '_sb_featured_ads', $feature_ads);
                        update_post_meta($ad_id, '_adforest_is_feature', '1');
                        update_post_meta($ad_id, '_adforest_is_feature_date', current_time('Y-m-d'));

                        $package_adFeatured_expiry_days = get_user_meta($user_id, 'package_adFeatured_expiry_days', true);
                        if ($package_adFeatured_expiry_days) {
                            update_post_meta($ad_id, 'package_adFeatured_expiry_days', $package_adFeatured_expiry_days);
                        }

                        if (function_exists('adforest_add_ad_post_notification')) {
                            adforest_add_ad_post_notification($ad_id, 'featured');
                        }

                        do_action('adforest_wpml_make_featured', $ad_id);
                        $ad_meta = get_post_meta($ad_id);

                        if ($ad_meta['_adforest_is_feature'][0] != 0) {
                            // echo '1|' . esc_html__("This ad has been featured successfullly", 'adforest');
                            wp_send_json_success(array("message" => esc_html__("This ad has been featured successfully.", 'adforest')));
                        } else {
                            wp_send_json_error(array("message" => esc_html__("Something Went Wrong", 'adforest')));
                        }
                    } else {
                        wp_send_json_error(array("message" => esc_html__("No Featured Ads in Selected Package", 'adforest')));
                    }
                } elseif ($featured_ad_new != 0 && $featured_ad_new != "") {

                    if ($pkg_expiry_date != '-1') {
                        if ($pkg_expiry_date < current_time('Y-m-d')) {
                            wp_send_json_error(array("message" => esc_html__("Your package has expired", 'adforest')));
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
                        if (function_exists('adforest_add_ad_post_notification')) {
                            adforest_add_ad_post_notification($ad_id, 'featured');
                        }
                        wp_send_json_success(array("message" => esc_html__("This ad has been featured successfully.", 'adforest')));
                    } else {
                        wp_send_json_error(array("message" => esc_html__("Something Went Wrong", 'adforest')));
                    }
                } else {
                    wp_send_json_error(array("message" => esc_html__("No Featured Ads in Selected Package", 'adforest')));
                }
            }
        } else {
            wp_send_json_error(array("message" => esc_html__("You must be the Ad owner to make it featured.", 'adforest')));
        }
        die();
    }
}

add_action('wp_ajax_del_job_alerts', 'adforest_del_job_alerts');
if (!function_exists('adforest_del_job_alerts')) {
    function adforest_del_job_alerts()
    {
        check_ajax_referer('sb_delete_ad_alert_nonce', 'nonce');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }
        global $nokri;
        $user_id = get_current_user_id();
        $alert_id = $_POST['alert_id'];
        /* demo check */
        if ($alert_id != "") {
            if (delete_user_meta($user_id, $alert_id)) {
                echo '1|' . esc_html__("Deleted successfully.", 'adforest');
                die();
            } else {
                echo '0|' . esc_html__("Unable to delete", 'adforest');
                die();
            }
        }
        echo '0|' . esc_html__("Unable to delete", 'adforest');
        die();
    }
}

// Remove Ad
add_action('wp_ajax_sb_remove_ad', 'adforest_sb_remove_ad');
if (!function_exists('adforest_sb_remove_ad')) {
    function adforest_sb_remove_ad()
    {
        check_ajax_referer('sb_remove_ad_nonce', 'nonce');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . esc_html__("Not allowed in demo mode", 'adforest');
            die();
        }

        adforest_authenticate_check();

        $ad_id = $_POST['ad_id'];
        $stored_status = get_post_meta($ad_id, '_adforest_ad_status_', true);
        if (wp_trash_post($ad_id)) {
            echo '1|' . esc_html__("Ad removed successfully.", 'adforest');
        } else {
            echo '0|' . esc_html__("There's some problem, please try again later.", 'adforest');
        }

        die();
    }
}

if (!function_exists('adforest_pagination_ads')) {
    function adforest_pagination_ads($wp_query)
    {
        if (is_singular())
            //return;

            if ($wp_query->max_num_pages <= 1)
                return;

        if (get_query_var('paged')) {
            $paged = get_query_var('paged');
        } elseif (get_query_var('page')) {
            $paged = get_query_var('page');
        } else {
            $paged = 1;
        }

        $max = intval($wp_query->max_num_pages);
        if ($paged >= 1)
            $links[] = $paged;

        if ($paged >= 3) {
            $links[] = $paged - 1;
            $links[] = $paged - 2;
        }

        if (($paged + 2) <= $max) {
            $links[] = $paged + 2;
            $links[] = $paged + 1;
        }

        $pagination = "";

        $pagination .= '<ul class="pagination pagination-lg">' . "\n";

        if (get_previous_posts_link())
            $pagination .= '<li>' . get_previous_posts_link() . '</li>' . "\n";

        if (!in_array(1, $links)) {
            $class = 1 == $paged ? ' class="active"' : '';

            $pagination .= '<li  ' . $class . '><a href="' . esc_url(get_pagenum_link(1)) . '">1</a></li>' . "\n";

            if (!in_array(2, $links))
                $pagination .= '<li><a href="javascript:void(0);">...</a></li>';
        }
        sort($links);
        foreach ((array) $links as $link) {

            $class = $paged == $link ? ' class="active"' : '';
            $pagination .= '<li ' . $class . '><a href="' . esc_url(get_pagenum_link($link)) . '">' . $link . '</a></li>' . "\n";
        }
        if (!in_array($max, $links)) {
            if (!in_array($max - 1, $links))
                $pagination .= '<li><a href="javascript:void(0);">...</a></li>' . "\n";
            $class = $paged == $max ? ' class="active"' : '';
            $pagination .= '<li ' . $class . '><a href="' . esc_url(get_pagenum_link($max)) . '">' . $max . '</a></li>' . "\n";
        }

        if (get_next_posts_link_custom($wp_query))
            $pagination .= '<li>' . get_next_posts_link_custom($wp_query) . '</li>' . "\n";

        return $pagination .= '</ul>' . "\n";
    }
}

add_action('wp_ajax_adforest_mark_notification_message_as_read', 'adforest_mark_notification_message_as_read');
function adforest_mark_notification_message_as_read()
{
    check_ajax_referer('mark_all_notifications_read_nonce', 'nonce');
    global $wpdb;

    if (isset($_POST['notification_type']) && 'theme' === sanitize_key(wp_unslash($_POST['notification_type'] ?? ''))) {
        if (!isset($_POST['notification_id']) || !is_numeric($_POST['notification_id'])) {
            wp_send_json_error(esc_html__('Invalid request.', 'adforest'));
            wp_die();
        }

        $notification_id = absint($_POST['notification_id']);

        if (!$notification_id) {
            wp_send_json_error(esc_html__('Invalid request.', 'adforest'));
            wp_die();
        }

        $updated = adforest_mark_theme_notification_as_read($notification_id, get_current_user_id());

        if ($updated) {
            wp_send_json_success(esc_html__('Notification marked as read.', 'adforest'));
        } else {
            wp_send_json_error(esc_html__('Failed to update notification.', 'adforest'));
        }

        wp_die();
    }

    if (!isset($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
        wp_send_json_error(esc_html__('Invalid request.', 'adforest'));
        wp_die();
    }

    $message_id = intval($_POST['message_id']);

    $updated = $wpdb->update(
        "{$wpdb->prefix}sb_chat_messages",
        array('read_status' => 1),
        array('id' => $message_id)
    );

    if ($updated !== false) {
        wp_send_json_success(esc_html__('Message marked as read.', 'adforest'));
    } else {
        wp_send_json_error(esc_html__('Failed to update message.', 'adforest'));
    }

    wp_die();
}

if (!function_exists("adforest_dashboard_breadcrumb")) {
    function adforest_dashboard_breadcrumb($page_title)
    {
        $breadcrumb = '
                        <div class="title-wrapper">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="title">
                                        <h2>' . esc_html($page_title) . '</h2>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="breadcrumb-wrapper">
                                        <nav aria-label="breadcrumb">
                                            <ol class="breadcrumb">
                                                <li class="breadcrumb-item">
                                                    <a href="' . get_the_permalink() . '">' . esc_html__("Dashboard", "adforest") . '</a>
                                                </li>
                                                <li class="breadcrumb-item active" aria-current="page">
                                                    ' . esc_html($page_title) . '
                                                </li>
                                            </ol>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
        ';
        return $breadcrumb;
    }
}

/**
 * Ad Dashboard Templates
 *
 * A modular approach to display different types of ads in user dashboard
 */

/**
 * Get query arguments based on ad type
 *
 * @param string $ad_type Type of ads to display
 * @return array Query arguments for WP_Query
 */
function adforest_get_ads_query_args($ad_type)
{
    global $adforest_theme;
    $user_id = get_current_user_id();
    $paged = get_query_var('paged', 1);
    $posts_per_page = get_option('posts_per_page');

    $args = array(
        'post_type' => 'ad_post',
        'author' => $user_id,
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'order' => 'DESC',
    );

    switch ($ad_type) {
        case 'my_ads':
            $args['post_status'] = 'publish';
            $args['orderby'] = 'date';
            $args['meta_query'] = array(
                array(
                    'key' => '_adforest_ad_status_',
                    'value' => array('expired', 'sold'),
                    'compare' => 'NOT IN',
                ),
            );
            break;

        case 'featured_ads':
            $args['post_status'] = 'publish';
            $args['meta_key'] = '_adforest_is_feature';
            $args['meta_value'] = '1';
            $args['orderby'] = 'ID';
            $args['meta_query'] = array(
                array(
                    'key' => '_adforest_ad_status_',
                    'value' => array('expired', 'sold'),
                    'compare' => 'NOT IN',
                ),
            );
            break;

        case 'expired_ads':
            $after_expired_ads = isset($adforest_theme['after_expired_ads']) ? $adforest_theme['after_expired_ads'] : "";
            if ($after_expired_ads == "published") {
                $args['post_status'] = array('draft', 'publish');
                $args['orderby'] = 'ID';
                $args['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_adforest_ad_status_',
                        'value' => 'expired',
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_adforest_ad_status_',
                        'value' => 'sold',
                        'compare' => '=',
                    ),
                );
            } else {
                $args['post_status'] = array('draft');
                $args['orderby'] = 'ID';
            }
            break;

        case 'inactive_ads':
            $args['post_status'] = array('pending');
            $args['orderby'] = 'ID';
            break;

        case 'rejected_ads':
            $args['post_status'] = 'rejected';
            $args['orderby'] = 'ID';
            break;

        case 'fav_ads':
            global $wpdb;
            $uid = get_current_user_id();
            $fav_like = $wpdb->esc_like('_sb_fav_id_') . '%';
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
                    $uid,
                    $fav_like
                )
            );
            $pids = array(0);
            foreach ($rows as $row) {
                $pids[] = $row->meta_value;
            }
            $args['post__in'] = $pids;
            $args['post_status'] = 'publish';
            $args['orderby'] = 'date';
            unset($args['author']);
            $args['meta_query'] = array(
                array(
                    'key' => '_adforest_ad_status_',
                    'value' => array('expired', 'sold'),
                    'compare' => 'NOT IN',
                ),
            );
            break;
    }

    return $args;
}

/**
 * Get page title based on ad type
 *
 * @param string $ad_type Type of ads
 * @return string Page title
 */
function adforest_get_page_title($ad_type)
{
    switch ($ad_type) {
        case 'my_ads':
            return esc_html__("My Ads", "adforest");
        case 'featured_ads':
            return esc_html__("Featured Ads", "adforest");
        case 'expired_ads':
            return esc_html__("Expired/Sold Ads", "adforest");
        case 'inactive_ads':
            return esc_html__("Inactive Ads", "adforest");
        case 'rejected_ads':
            return esc_html__("Rejected Ads", "adforest");
        case 'fav_ads':
            return esc_html__("Favourite Ads", "adforest");
        default:
            return esc_html__("My Ads", "adforest");
    }
}

/**
 * Get the actions/buttons to display for each ad based on type
 *
 * @param int $ad_id Post ID
 * @param string $ad_type Type of ad
 * @return string HTML for action buttons
 */
function adforest_get_ad_actions($ad_id, $ad_type)
{
    global $adforest_theme;
    $sb_post_ad_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page']);
    $ad_update_url = adforest_set_url_param(get_the_permalink($sb_post_ad_page), 'id', $ad_id);
    $html = '';

    $bump_ads = get_user_meta(get_current_user_id(), '_sb_bump_ads', true);
    $bump_up_ads_class = '';
    if ($bump_ads > 0 || $bump_ads == '-1' || (isset($adforest_theme['sb_allow_free_bump_up']) && $adforest_theme['sb_allow_free_bump_up'])) {
        $bump_up_ads_class = 'bump_it_up_new_pkg';
    } else {
        $bump_up_ads_class = 'bump_it_up_new_pkg';
    }

    $make_bump_up_paid_url = "#";
    if (
        isset($adforest_theme['make_bump_up_paid']) && $adforest_theme['make_bump_up_paid'] &&
        isset($adforest_theme['sb_bump_up_template_page']) && $adforest_theme['sb_bump_up_template_page'] != 0
    ) {
        $bump_up_ads_class = '';
        $make_bump_up_paid_url = (isset($adforest_theme['sb_bump_up_template_page']) && $adforest_theme['sb_bump_up_template_page'] != "")
            ? get_the_permalink($adforest_theme['sb_bump_up_template_page']) . '?pid=' . $ad_id
            : "javascript:void(0)";
    }

    $featured_ads = get_user_meta(get_current_user_id(), '_sb_featured_ads', true);
    $sb_expire_ads = get_user_meta(get_current_user_id(), '_sb_expire_ads', true);

    if ($featured_ads != 0 && $featured_ads != "" && ($sb_expire_ads != '-1' || $sb_expire_ads < current_time('Y-m-d'))) {
        $ad_featured = 'sb_make_feature_ad_new_pkg';
    } else {
        $ad_featured = 'sb_make_feature_ad_new_pkg';
    }

    $make_feature_paid_url = "#";
    if (
        isset($adforest_theme['make_feature_paid']) && $adforest_theme['make_feature_paid'] &&
        isset($adforest_theme['sb_feature_template_page']) && $adforest_theme['sb_feature_template_page'] != 0
    ) {
        $ad_featured = '';
        $make_feature_paid_url = (isset($adforest_theme['sb_feature_template_page']) && $adforest_theme['sb_feature_template_page'] != "")
            ? get_the_permalink($adforest_theme['sb_feature_template_page']) . '?pid=' . $ad_id
            : "javascript:void(0)";
    }

    $html .= '<div class="action justify-content-end">';

    $html .= '<div class="ad_action_container d-flex justify-content-center align-items-center gap-4">';

    // Add Pay for Ad button for inactive ads when pay-per-post is enabled
    if ($ad_type == 'inactive_ads' && isset($adforest_theme['sb_pay_per_post_option']) && $adforest_theme['sb_pay_per_post_option'] == 1) {
        $pay_per_post_page = isset($adforest_theme['sb_pay_per_post_template_page']) ? $adforest_theme['sb_pay_per_post_template_page'] : '';
        if ($pay_per_post_page) {
            $pay_for_ad_url = get_the_permalink($pay_per_post_page) . '?pid=' . $ad_id;
            $html .= '<a href="' . esc_url($pay_for_ad_url) . '" class="pay-for-ad-btn" 
                   data-bs-toggle="tooltip" data-bs-placement="top"
                   title="' . esc_attr__("Pay for Ad", "adforest") . '" data-aaa-id="' . esc_attr($ad_id) . '">
                   <i class="lni lni-credit-cards"></i>
                </a>';
        }
    }

    $is_featured = get_post_meta(get_the_ID(), '_adforest_is_feature', true) == '1';
    $star_class = $is_featured ? 'lni lni-star-filled' : 'lni lni-star';
    $inline_style = $is_featured ? 'pointer-events: none; cursor: default;' : '';
    $link_class = $is_featured ? '' : $ad_featured;

    if ($ad_type == 'my_ads') {
        if ($is_featured) {
            // Non-clickable span with tooltip "Already Featured"
            $html .= '<span class="non-clickable-featured" 
                   data-bs-toggle="tooltip" data-bs-placement="top" 
                   title="' . esc_html__("Already Featured", "adforest") . '" 
                   style="cursor: default;">
                   <i class="' . esc_attr($star_class) . '"></i>
              </span>';
        } else {
            // Clickable link to make featured
            $html .= '<a href="' . esc_url($make_feature_paid_url) . '" class="' . esc_attr($link_class) . '" 
               data-bs-toggle="tooltip" data-bs-placement="top"
               title="' . esc_html__("Make Featured", "adforest") . '" data-aaa-id="' . esc_attr(get_the_ID()) . '">
               <i class="' . esc_attr($star_class) . '"></i>
              </a>';
        }
        $html .= '<a href="' . esc_url($make_bump_up_paid_url, "adforest") . '" class="' . esc_attr($bump_up_ads_class) . '" 
                   data-bs-toggle="tooltip" data-bs-placement="top"
                   title="' . esc_html__("Bump up Ad", "adforest") . '" data-aaa-id="' . esc_attr($ad_id) . '">
                   <i class="lni lni-arrow-up-circle"></i>
                </a>';
    } elseif ($ad_type == 'featured_ads') {
        $html .= '<a href="javascript:void(0)" class="' . $bump_up_ads_class . '" 
                   data-bs-toggle="tooltip" data-bs-placement="top"
                   title="' . esc_html__("Bump Up Ad", "adforest") . '" data-aaa-id="' . esc_attr($ad_id) . '">
                   <i class="lni lni-arrow-up-circle"></i>
                </a>';
    } elseif ($ad_type == 'fav_ads') {
        $nonce = wp_create_nonce('sb_fav_remove_ad_nonce');

        $html .= '<a href="javascript:void(0)" class="remove_fav_ad" 
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="' . esc_html__("Remove Favourite", "adforest") . '"
                data-aaa-id="' . esc_attr($ad_id) . '"
                data-nonce="' . $nonce . '">
                <i class="mdi mdi-tag-remove-outline"></i>
                </a>';
    }

    if ($ad_type != 'expired_ads' && $ad_type != 'inactive_ads' && $ad_type != 'rejected_ads') {
        $html .= '<a href="' . esc_url($ad_update_url) . '" class="edit" data-bs-toggle="tooltip" data-bs-placement="top"
                   title="' . esc_html__("Edit Ad", "adforest") . '">
                   <i class="lni lni-pencil"></i>
                </a>';
    } else {
        $html .= '<a href="' . esc_url($ad_update_url) . '" class="edit" data-bs-toggle="tooltip" data-bs-placement="top"
                   title="' . esc_html__("Edit Ad", "adforest") . '">
                   <i class="lni lni-pencil"></i>
                </a>';
    }

    $html .= '</div>';

    $html .= '<button class="more-btn ml-10 dropdown-toggle" 
                    id="moreAction' . $ad_id . '" data-bs-toggle="dropdown" 
                    aria-expanded="false">
                <i class="lni lni-more-alt"></i>
            </button>';

    $html .= '<ul class="dropdown-menu dropdown-menu-end" 
                aria-labelledby="moreAction' . $ad_id . '">';

    $html .= '<li class="dropdown-item">
                <a href="javascript:void(0)" class="text-gray ad_package_info"
                   data-adid="' . $ad_id . '" data-nonce="' . wp_create_nonce('adforest_ad_package_info') . '">' . esc_html__("Info", "adforest") . '</a>
            </li>';

    if ($ad_type != 'my_ads' && $ad_type != 'inactive_ads') {
        $html .= '<li class="dropdown-item">
                    <a href="javascript:void(0)" class="text-gray ad_status" 
                       data-adid="' . $ad_id . '" data-value="active" data-security="' . esc_attr(wp_create_nonce('sb_update_ad_status_nonce')) . '">' . esc_html__("Active", "adforest") . '</a>
                </li>';
    }

    if ($ad_type != 'expired_ads' && $ad_type != 'inactive_ads') {
        $html .= '<li class="dropdown-item">
                    <a href="javascript:void(0)" class="text-gray ad_status" 
                       data-adid="' . $ad_id . '" data-value="expired" data-security="' . esc_attr(wp_create_nonce('sb_update_ad_status_nonce')) . '">' . esc_html__("Expire", "adforest") . '</a>
                </li>';
    }

    if ($ad_type != 'inactive_ads') {
        $html .= '<li class="dropdown-item">
                <a href="javascript:void(0)" class="text-gray ad_status" 
                   data-adid="' . $ad_id . '" data-value="sold" data-security="' . esc_attr(wp_create_nonce('sb_update_ad_status_nonce')) . '">' . esc_html__("Sold", "adforest") . '</a>
            </li>';
    }

    $html .= '<li class="dropdown-item">
                <a href="javascript:void(0)" class="text-gray remove_ad" 
                   data-adid="' . $ad_id . '" data-value="expired">' . esc_html__("Delete", "adforest") . '</a>
            </li>';

    $html .= '</ul></div>';

    return $html;
}

/**
 * Display the ads table
 *
 * @param string $ad_type Type of ads to display
 */
function adforest_display_ads_table($ad_type, $table_class)
{
    $args = adforest_get_ads_query_args($ad_type);
    $query = new WP_Query($args);

    $page_title = adforest_get_page_title($ad_type);
    echo adforest_dashboard_breadcrumb($page_title);

    ?>
    <div class="row">
        <!-- Ads Table -->
        <div class="col-12">
            <div class="card-style mb-30">
                <div class="table-responsive">
                    <table class="table top-selling-table dashboard-<?php echo esc_attr($table_class); ?>">
                        <thead>
                            <tr>
                                <th class="min-width">
                                    <h6 class="text-sm text-medium"><?php echo esc_html__("Ad Title", "adforest"); ?></h6>
                                </th>
                                <th class="min-width">
                                    <h6 class="text-sm text-medium"><?php echo esc_html__("Price", "adforest"); ?></h6>
                                </th>
                                <th class="min-width">
                                    <h6 class="text-sm text-medium"><?php echo esc_html__("Status", "adforest"); ?></h6>
                                </th>
                                <th>
                                    <h6 class="text-sm text-medium text-end">
                                        <?php echo esc_html__("Actions", "adforest"); ?></h6>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query->have_posts()): ?>
                                <?php while ($query->have_posts()):
                                    $query->the_post(); ?>
                                    <?php
                                    $ad_details = get_ad_post_details(get_the_ID());
                                    $first_img = $ad_details['img'];
                                    $title = $ad_details['ad_title'];
                                    $price = $ad_details['price'];
                                    $ad_permalink = $ad_details['ad_link'];
                                    $post_status = get_post_status(get_the_ID());
                                    $status_label = ucfirst($post_status);

                                    $tr_class = ($ad_type == 'fav_ads') ? 'class="holder-' . get_the_ID() . '"' : '';
                                    $posted_time = get_the_time('U', get_the_ID());
                                    ?>
                                    <tr <?php if ($tr_class)
                                        echo 'class="' . esc_attr($tr_class) . '"'; ?>>
                                        <td>
                                            <div class="product">
                                                <div class="image">
                                                    <img src="<?php echo esc_url($first_img); ?>"
                                                        alt="<?php echo esc_attr($title); ?>" />
                                                </div>
                                                <a href="<?php echo get_permalink(get_the_ID()); ?>">
                                                    <p class="text-sm"><?php echo esc_html($title); ?></p>
                                                    <p class="ad-date"><?php echo adforest_get_ad_posted_date($posted_time); ?></p>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-price-dash">
                                                <?php
                                                $price_output = adforest_adPrice(get_the_ID(), 'negotiable', 'p');
                                                if (!empty($price_output)) {
                                                    echo $price_output;
                                                } else {
                                                    echo esc_html__('No Price', 'adforest');
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $status_labels = array(
                                                'publish' => __('Published', 'adforest'),
                                                'pending' => __('Pending Review', 'adforest'),
                                                'draft' => __('Draft', 'adforest'),
                                                'trash' => __('Trash', 'adforest'),
                                                'private' => __('Private', 'adforest'),
                                                'future' => __('Scheduled', 'adforest'),
                                            );

                                            if (isset($status_labels[$post_status])) {
                                                $status_label = $status_labels[$post_status];
                                            } elseif ($obj = get_post_status_object($post_status)) {
                                                $status_label = $obj->label;
                                            } else {
                                                $status_label = ucfirst($post_status);
                                            }
                                            ?>
                                            <span class="status-btn <?php echo esc_attr($post_status); ?>-btn">
                                                <?php echo esc_html($status_label); ?>
                                            </span>
                                        </td>
                                        <?php if ($ad_type == 'fav_ads') { ?>
                                            <td>
                                                <a href="javascript:void(0)" class="remove_fav_ad" data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="<?php echo esc_attr__("Remove Favorite", "adforest"); ?>"
                                                    data-aaa-id="<?php echo esc_attr(get_the_ID()) ?>"
                                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'sb_fav_remove_ad_nonce' ) ); ?>">
                                                    <i class="mdi mdi-tag-remove-outline"></i>
                                                </a>
                                            </td>
                                        <?php } else { ?>
                                            <td>
                                                <?php echo adforest_get_ad_actions(get_the_ID(), $ad_type); ?>
                                            </td>
                                        <?php } ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <p><?php echo esc_html__("No ads found.", "adforest"); ?></p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php wp_reset_postdata(); ?>
                        </tbody>
                    </table>
                    <?php
                    $posts_per_page = get_option('posts_per_page');
                    if ($query->have_posts()) {
                        if ($query->found_posts > $posts_per_page) { ?>
                            <div class="d-flex justify-content-center align-items-center">
                                <button id="load-more-myads"
                                    class="btn dark-btn <?php echo (esc_attr($ad_type) == 'my_ads') ? 'adt-button-dark-1' : ''; ?>"
                                    data-security="<?php echo wp_create_nonce('load_more_ads_nonce') ?>"
                                    data-ad-type="<?php echo esc_attr($ad_type); ?>">
                                    <?php echo esc_html__('Load More', 'adforest'); ?>
                                </button>
                            </div>
                        <?php }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

add_action('wp_ajax_sb_verify_firebase_otp', 'sb_verify_firebase_otp_fun');
if (!function_exists('sb_verify_firebase_otp_fun')) {

    function sb_verify_firebase_otp_fun()
    {
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_login_otp_nonce')) {
            wp_send_json_error(array("message" => esc_html__('Invalid security token', 'adforest')));
            die();
        }

        $is_demo = adforest_is_demo();
        if ($is_demo) {
            wp_send_json_error(array("message" => esc_html__('Not allowed in demo mode', 'adforest')));
            die();
        }

        $user_id = get_current_user_id();
        $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : "";
        $saved_num = get_user_meta($user_id, '_sb_contact', true);
        if ($phone_number == "") {
            wp_send_json_error(array("message" => esc_html__('Phone Number not exist', 'adforest')));
        } else if ($phone_number != $saved_num) {
            wp_send_json_error(array("message" => esc_html__('Phone Number not match', 'adforest')));
        } else {
            update_user_meta($user_id, '_sb_is_ph_verified', "1");
            wp_send_json_success(array("message" => esc_html__('Verified succesfully', 'adforest')));
        }
    }
}

/* Delete USER */
add_action('wp_ajax_delete_site_user_func', 'adforest_delete_site_user_func');
if (!function_exists('adforest_delete_site_user_func')) {
    function adforest_delete_site_user_func()
    {
        check_ajax_referer('sb_delete_site_user_nonce', 'nonce');
        $del_user_id = $_POST['del_user_id'];
        $current_user_id = get_current_user_id();
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $success = 0;
        $message = __("Something went wrong.", "adforest");
        $if_user_exists = adforest_user_id_exists($del_user_id);
        if ($current_user_id == $del_user_id && $if_user_exists) {
            if (current_user_can('administrator')) {

                $success = 0;
                $message = __("Admin can not delete his account from here.", "adforest");
            } else {
                adforestTheme_delete_userComments($current_user_id);
                $user_delete = wp_delete_user($current_user_id);
                if ($user_delete) {

                    $success = 1;
                    $message = __("Your account has been deleted successfully.", "adforest");
                    wp_logout();
                }
            }
        }
        echo adforest_return_echo($success . '|' . $message);
        die();
    }
}

if (!function_exists('adforestTheme_delete_userComments')) {
    function adforestTheme_delete_userComments($user_id)
    {
        $user = get_user_by('id', $user_id);

        $comments = get_comments('author_email=' . $user->user_email);
        foreach ($comments as $comment):
            wp_delete_comment($comment->$comment_id, true);
        endforeach;

        $comments = get_comments('user_id=' . $user_id);
        foreach ($comments as $comment):
            wp_delete_comment($comment->$comment_id, true);
        endforeach;
    }
}

if (!function_exists('adforest_user_id_exists')) {
    function adforest_user_id_exists($user)
    {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user));

        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'users.php') {
        wp_register_style('adforest-admin-inline', false);
        wp_enqueue_style('adforest-admin-inline');

        $css = '
            .wp-list-table.users th.column-assigned_package,
            .wp-list-table.users td.column-assigned_package,
            .wp-list-table.users th.column-display_name,
            .wp-list-table.users td.column-display_name {
                width: 100px !important;
                max-width: 100px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .wp-list-table.users th.column-role,
            .wp-list-table.users td.column-role {
                width: 100px !important;
                max-width: 100px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
        ';

        wp_add_inline_style('adforest-admin-inline', $css);
    }
});
