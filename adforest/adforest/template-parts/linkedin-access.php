<?php
global $adforest_theme;
if(!function_exists('adforest_linkedin_login')){
function adforest_linkedin_login($email = '', $first_name = '', $last_name = '', $profile_img = '') {
    global $adforest_theme;
    $login_text = esc_html__('Login Successfully', 'adforest');
    if (!email_exists($email)) {
        $display_name = $first_name . ' ' . $last_name;
        $user_name = explode('@', $email);
        $u_name = adforest_check_user_name($user_name[0]);
        $uid = wp_create_user($u_name, wp_generate_password(), sanitize_email($email));
        wp_update_user(array('ID' => $uid, 'display_name' => sanitize_text_field($display_name)));
        update_user_meta($uid, '_sb_contact', '');
        update_user_meta($uid, '_sb_user_linkedin_pic', $profile_img);
        if ($adforest_theme['sb_allow_pkg_on_reg'] == '1') {
            $package_to_assign = $adforest_theme['sb_register_package'];
            if (isset($package_to_assign) && !empty($package_to_assign)) {
                if (function_exists('adforest_give_user_package_from_admin')) {
                    adforest_give_user_package_from_admin($package_to_assign, $uid);
                }
            }
        }
        // Email for new user
        if (function_exists('adforest_email_on_new_user')) {
            adforest_email_on_new_user($uid, '');
        }
        // check phone verification is on or not
        // check phone verification is on or not
        $sms_gateway = adforest_verify_sms_gateway();
        if ($sms_gateway != "") {
            update_user_meta($uid, '_sb_is_ph_verified', '0');
        }
        // login after register
        $user = get_user_by('email', $email);
        $user_id = $user->ID;
        if ($user) {
            wp_set_current_user($user_id, $user->user_login);
            wp_set_auth_cookie($user_id);
            $sb_profile_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_profile_page']);
            $after_login_redirect = get_the_permalink($sb_profile_page);
            adforest_redirect_with_msg($after_login_redirect, $login_text, 'success');
        }
    } else {
        if (email_exists($email)) {
            $user = get_user_by('email', $email);
            $user_id = $user->ID;
            if ($user) {
                wp_set_current_user($user_id, $user->user_login);
                wp_set_auth_cookie($user_id);
                $sb_profile_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_profile_page']);
                $after_login_redirect = get_the_permalink($sb_profile_page);
                adforest_redirect_with_msg($after_login_redirect, $login_text, 'success');
            }
        }
    }
}
}

// Linkedin handling
if(!function_exists('adforest_get_linkedin_data')){
function adforest_get_linkedin_data($code) {
    global $adforest_theme;
    $res = array();
    $server_output = '';

    $client_id = ($adforest_theme['adforest_linkedin_api_key']);
    $client_secret = ($adforest_theme['adforest_linkedin_api_secret']);
    $redirect_uri = ($adforest_theme['adforest_redirect_uri']);
    if ($code != "") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.linkedin.com/oauth/v2/accessToken");
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=authorization_code&code=" . $code . "&redirect_uri=$redirect_uri&client_id=$client_id&client_secret=$client_secret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
    }
    // notify me in case of error
    if (json_decode($server_output)->error) {
        adforest_redirect_with_msg(home_url('/'), json_decode($server_output)->error_description);
    }
    //For Email     
    $Url = "https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))";
    $token = json_decode($server_output)->access_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'X-Restli-Protocol-Version: 2.0.0',
        'Accept: application/json',
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    $response_body = json_decode($body, true);
    $userEmail = (isset($response_body['elements'][0]['handle~']['emailAddress'])) ? $response_body['elements'][0]['handle~']['emailAddress'] : '';
    // For Profile
    $Url = "https://api.linkedin.com/v2/me?projection=(id,firstName,lastName,profilePicture(displayImage~:playableStreams))";
    $token = json_decode($server_output)->access_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'X-Restli-Protocol-Version: 2.0.0',
        'Accept: application/json',
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    $response_body = json_decode($body, true);
    $fname = (isset($response_body['firstName']['localized']['en_US'])) ? $response_body['firstName']['localized']['en_US'] : '';
    $lname = (isset($response_body['lastName']['localized']['en_US'])) ? $response_body['lastName']['localized']['en_US'] : '';
    $profile_img = (isset($response_body['profilePicture']['displayImage~']['elements'][1]['identifiers'][0]['identifier'])) ? $response_body['profilePicture']['displayImage~']['elements'][1]['identifiers'][0]['identifier'] : '';
    $res[] = $fname;
    $res[] = $lname;
    $res[] = $userEmail;
    $res[] = $profile_img;
    return $res;
}}
$linkein_access = FALSE;
if ((isset($adforest_theme['adforest_linkedin_api_key'])) && $adforest_theme['adforest_linkedin_api_key'] != '' && (isset($adforest_theme['adforest_linkedin_api_secret'])) && $adforest_theme['adforest_linkedin_api_secret'] != '' && (isset($adforest_theme['adforest_redirect_uri'])) && $adforest_theme['adforest_redirect_uri'] != '') {
    $linkein_access = TRUE;
}
if (isset($_GET['code']) && $_GET['code'] != '' && $linkein_access) {
    $response = adforest_get_linkedin_data($_GET['code']);
    if (count($response) > 0) {
        $fname = isset($response[0]) ? $response[0] : '';
        $lname = isset($response[1]) ? $response[1] : '';
        $email = isset($response[2]) ? $response[2] : '';
        $profile_img = isset($response[3]) ? $response[3] : '';

        adforest_linkedin_login($email, $fname, $lname, $profile_img);
    }
}
