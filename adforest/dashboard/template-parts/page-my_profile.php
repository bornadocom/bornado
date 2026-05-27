<?php
global $adforest_theme;
$sb_sign_in_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_sign_in_page']);
$user_info = get_userdata(get_current_user_id());
$user_id = $user_info->ID;
$user_pic = adforest_get_user_dp($user_info->ID);
/* user type html */
$user_type = get_user_meta($user_info->ID, '_sb_user_type', true);
$is_indiviual = '';
$user_type_txt = '';
$is_dealer = '';
if ($user_type == 'Dealer') {
    $is_dealer = 'selected="selected"';
    $user_type_txt = esc_html__('Dealer', 'adforest');
}
if ($user_type == 'Indiviual') {
    $is_indiviual = 'selected="selected"';
    $user_type_txt = esc_html__('Individual', 'adforest');
}
$user_type_html = '<option value="">' . esc_html__('Select option', 'adforest') . '</option>
                    <option value="Indiviual"  ' . $is_indiviual . '>' . esc_html__('Individual', 'adforest') . '</option>
					 <option value="Dealer" ' . $is_dealer . '>' . esc_html__('Dealer', 'adforest') . '</option>';

$sb_disable_linkedin_edit = isset($adforest_theme['sb_disable_linkedin_edit']) && $adforest_theme['sb_disable_linkedin_edit'] ? TRUE : FALSE;

/* User social links */
$social_html = '';
if (isset($adforest_theme['sb_enable_social_links']) && $adforest_theme['sb_enable_social_links']) {

    $profiles = adforest_social_profiles();
    foreach ($profiles as $key => $value) {

        $disabled_field = '';
        if ($key == 'linkedin' && $sb_disable_linkedin_edit) {
            $disabled_field = ' disabled="disabled" ';
        }
        $social_html .= '<div class="col-md-12 col-sm-12 col-xs-12 col-lg-6">
                            <div class="input-style-1">
                              <label>' . $value . '</label>
                              <input ' . $disabled_field . 'type="text" value="' . esc_attr(get_user_meta($user_id, '_sb_profile_' . $key, true)) . '" name="_sb_profile_' . $key . '">
                            </div>
                         </div>';
    }
}


/* Phone number verification */

$is_verified = '';
$is_firebase = $adforest_theme['sb_phone_verification_firebase'] ? $adforest_theme['sb_phone_verification_firebase'] : false;
$sms_gateway = adforest_verify_sms_gateway();
$is_number_verified = get_user_meta($user_id, '_sb_is_ph_verified', true);
$user_desc = get_user_meta($user_info->ID, '_sb_user_intro', true);
$user_phone_number = get_user_meta($user_id, '_sb_contact', true) ?? "";

if ($sms_gateway != "" && !$is_firebase) {
    if ($is_number_verified == '1') {
        $is_verified = '<span class="mb-2 mr-2 verified_user_text sb_user_type">' . esc_html__('Verified', 'adforest') . '</span>';
    } else if ($user_phone_number != '') {
        $is_verified = '&nbsp;
				<a data-target="#verification_modal" data-toggle="modal" class="small_text" id="sb-verify-phone">' . esc_html__('Verify now', 'adforest') . '</a>';
    }
} else if ($is_firebase) {
    if ($is_number_verified == '1') {
        $is_verified = '<span class="mb-2 mr-2 verified_user_text sb_user_type">' . esc_html__('Verified', 'adforest') . '</span>';
    } else if ($user_phone_number != '') {
        $app_key = isset($adforest_theme['sb_firebase_apikey']) && $adforest_theme['sb_firebase_apikey'] != "" ? $adforest_theme['sb_firebase_apikey'] : "";
        $project_id = isset($adforest_theme['sb_firebase_projectId']) && $adforest_theme['sb_firebase_projectId'] != "" ? $adforest_theme['sb_firebase_projectId'] : "";
        $sender_id = isset($adforest_theme['sb_firebase_messagingSenderId']) && $adforest_theme['sb_firebase_messagingSenderId'] != "" ? $adforest_theme['sb_firebase_messagingSenderId'] : "";
        $app_id = isset($adforest_theme['sb_firebase_appId']) && $adforest_theme['sb_firebase_appId'] != "" ? $adforest_theme['sb_firebase_appId'] : "";
        wp_enqueue_script('firebase-app', "https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js", false, false, true);
        wp_enqueue_script('firebase-analytics', "https://www.gstatic.com/firebasejs/8.3.2/firebase-analytics.js", false, false, true);
        wp_enqueue_script('firebase-auth', "https://www.gstatic.com/firebasejs/8.3.2/firebase-auth.js", false, false, false);
        wp_enqueue_script('firebase-custom', trailingslashit(get_template_directory_uri()) . 'assests/js/firebase-custom.js', array(), false, false);

        $is_verified = '&nbsp;         
	 <a   class="small_text" id="sb-verify-phone-firebase">' . esc_html__('Verify now', 'adforest') . '</a><div id="firebase-recaptcha" class="firebase-recaptcha"></div><input type="hidden" id="user-otp-num" value="' . get_user_meta($user_id, '_sb_contact', true) . '"> <input type="hidden"   id="sb-fb-apikey" value= "' . $app_key . '"> 
                 <input type="hidden"   id="sb-fb-projectid"   value= "' . $project_id . '"> 
                 <input type="hidden"   id="sb-fb-senderid"   value= "' . $sender_id . '"> 
                 <input type="hidden"   id="sb-fb-appid"    value= "' . $app_id . '"> ';
    }
}

$readonly = "";
$email_name = "name=user_email";
if (isset($user_info->user_email) && $user_info->user_email != "") {
    $readonly = "readonly";
    $email_name = "";
}
$ph_placeholder = esc_html__('+CountrycodePhonenumber', 'adforest');
$sms_gateway = adforest_verify_sms_gateway();
if ($sms_gateway != "") {
    $ph_placeholder = esc_html__('+CountrycodePhonenumber', 'adforest');
}

$delete_account_html = '';
if (isset($adforest_theme['sb_new_user_delete_option']) && $adforest_theme['sb_new_user_delete_option']) {
    $data_title = esc_html__("Are you sure you want to delete this account?", "adforest");
    $delete_account_html = '<a class="remove_user_profile delete_site_user main-btn-danger my-4" href="javascript:void(0);" data-btn-ok-label="' . esc_html__("Yes", "adforest") . '" data-btn-cancel-label="' . esc_html__("No", "adforest") . '" data-toggle="confirmation" data-singleton="true" data-title="' . $data_title . '" data-content="" data-user-id="' . $user_info->ID . '" title="' . esc_html__("Delete Account?", "adforest") . '" aria-describedby="confirmation151400">' . esc_html__("Delete Account?", "adforest") . '</a>';
}


$badge = '';
if (get_user_meta($user_id, '_sb_badge_type', true) != "" && get_user_meta($user_id, '_sb_badge_text', true) != "") {
    $badge = ' <span class="label ' . get_user_meta($user_id, '_sb_badge_type', true) . '">
	' . get_user_meta($user_id, '_sb_badge_text', true) . '</span>';
}


$package_type_html = '';
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    $package_type = get_user_meta($user_id, '_sb_pkg_type', true);
    if (get_user_meta($user_id, '_sb_pkg_type', true) != 'free') {
        $package_type = esc_html__('Paid', 'adforest');
    } else {
        $package_type = esc_html__('Free', 'adforest');
    }
    $package_type_html = '<span class="label label-warning">' . $package_type . '</span>';
}

$rating = '';
if (isset($adforest_theme['sb_enable_user_ratting']) && $adforest_theme['sb_enable_user_ratting']) {

    $rating = '<a href="' . adforest_set_url_param(get_author_posts_url($user_id), 'type', 1) . '">
			<div class="rating">';
    $got = get_user_meta($user_id, "_adforest_rating_avg", true);
    if ($got == "")
        $got = 0;
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= round($got))
            $rating .= '<i class="lni lni-star-filled"></i>';
        else
            $rating .= '<i class="lni lni-star"></i>';
    }
    $rating .= '<span class="rating-count">
			   (' . count(adforest_get_all_ratings($user_id)) . ')
			   </span>
			</div>
			</a>';
}
$social_icons = "";
$profiles = adforest_social_profiles();
foreach ($profiles as $key => $value) {
    if (get_user_meta($user_id, '_sb_profile_' . $key, true) != "") {
        $icon = isset($key) && $key == 'linkedin' ? 'lni lni-' . $key . '-original' : 'lni lni-' . $key . '-filled';
        $social_icons .= '<a href="' . esc_url(get_user_meta($user_id, '_sb_profile_' . $key, true)) . '" target="_blank" class="mb-1 btn social-icons-outline-dash btn-' . esc_attr($key) . '"><i class="' . $icon . '"></i></a>';
    }
}
?>

<?php echo adforest_dashboard_breadcrumb(esc_html__("Edit Profile", "adforest")); ?>
    <div class="form-elements-wrapper">
        <div class="row">
            <div class="col-lg-4">
                <div class="card-style">
                    <div class="card text-center widget-profile px-0 border-0">
                        <div class="user-dp-container"
                             style="position: relative; display: inline-block; cursor: pointer;">
                            <img src="<?php echo esc_url($user_pic); ?>"
                                 alt="<?php echo esc_html__('img', 'adforest'); ?>" id="img-upload">
                            <div class="edit-dp">
                                <i class="lni lni-camera" aria-hidden="true" id="upload_user_dp"></i>
                            </div>
                            <input type="file" id="imgInp" name="my_file_upload[]" accept="image/*"
                                   class="sb_files-data form-control" style="display: none;" data-security="<?php echo wp_create_nonce("upload_user_image_nonce") ?>">
                        </div>
                    </div>
                    <div class="card-body profile-main-body">
                        <div class="dashboard-user-info">
                            <h4 class="text-dark"><?php echo esc_html($user_info->display_name) ?></h4>
                            <p><?php echo esc_html($user_info->user_email) ?></p>
                            <?php
                            echo adforest_return_echo($package_type_html);
                            if (get_user_meta($user_id, '_sb_user_type', true) != "") {
                                $user_type_meta = get_user_meta($user_id, '_sb_user_type', true);
                                $user_type = "";
                                if ($user_type_meta == 'Indiviual') {
                                    $user_type = esc_html__('Indiviual', 'adforest');
                                } else if ($user_type_meta == 'Dealer') {
                                    $user_type = esc_html__('Dealer', 'adforest');
                                }
                                echo '<span class="label label-success sb_user_type">' . $user_type . '</span>';

                            }
                            echo adforest_return_echo($badge);
                            ?>
                        </div>
                        <div class="dashboard-user-rating">
                            <?php echo wp_kses_post($rating); ?>
                        </div>
                    </div>

                    <hr class="w-100">
                    <div class="contact-info">
                        <h5><?php echo esc_html__('Contact Information', 'adforest'); ?></h5>
                        <ul>
                            <li>
                                <p><i class="lni lni-phone"></i><?php echo esc_html__('Phone number', 'adforest') ?></p>
                                <p><?php echo esc_html(get_user_meta($user_id, '_sb_contact', true)) . $is_verified; ?> </p>
                            </li>
                            <li>
                                <p class=""><i
                                            class="lni lni-users"></i><?php echo esc_html__('Social Profile', 'adforest') ?>
                                </p>
                                <p class="social-button"><?php echo adforest_return_echo($social_icons); ?></p>
                            </li>
                        </ul>


                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card-style">
                    <?php wp_enqueue_script('google-map-callback'); ?>
                    <?php adforest_load_search_countries(); ?>
                    <form id="sb_update_profile">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-style-1">
                                    <label for="sb_user_name"><?php echo esc_html__("Your Name", "adforest"); ?></label>
                                    <input value="<?php echo esc_attr($user_info->display_name) ?>"
                                           name="sb_user_name" type="text"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-style-1">
                                    <label><?php echo esc_html__("Email Address", "adforest"); ?><span
                                                class="color-red">*</span></label>
                                    <input <?php echo esc_attr($email_name); ?>
                                            value="<?php echo esc_attr($user_info->user_email) ?>"
                                            type="email" <?php echo esc_attr($readonly) ?>/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-style-1">
                                    <label><?php echo esc_html__("Contact Number", "adforest"); ?><span
                                                class="color-red">*</span></label>
                                    <input type="text" name="sb_user_contact" id="sb_user_contact"
                                           value="<?php echo esc_attr(get_user_meta($user_info->ID, '_sb_contact', true)) ?>"
                                           placeholder="<?php echo esc_attr($ph_placeholder) ?>"/>
                                    <small></small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="select-style-1">
                                    <label><?php echo esc_html__("I AM", "adforest"); ?><span
                                                class="color-red">*</span></label>
                                    <div class="select-position">
                                        <select name="sb_user_type">
                                            <?php echo adforest_return_echo($user_type_html) ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php echo adforest_return_echo($social_html); ?>
                            <?php
                            $mapType = adforest_mapType();
                            $location_input_id = ($mapType == 'leafletjs_map') ? 'sb_user_address_leaflet' : 'sb_user_address';
                            ?>
                            <div class="col-lg-12">
                                <div class="input-style-1">
                                    <label><?php echo esc_html__("Location", "adforest"); ?></label>
                                    <input type="text"
                                           placeholder="<?php echo esc_attr__('Enter a location', 'adforest'); ?>"
                                           name="sb_user_address" id="<?php echo esc_attr($location_input_id); ?>"
                                           autocomplete="on"
                                           value="<?php echo esc_attr(get_user_meta($user_info->ID, '_sb_address', true)); ?>"/>
                                </div>
                                <div id="suggestions-box" class="suggestions-box"></div>
                            </div>
                            <div class="col-lg-12">
                                <div class="input-style-1">
                                    <label><?php echo esc_html__('Introduction', 'adforest'); ?></label>
                                    <textarea name="sb_user_intro" placeholder="<?php echo esc_html__('Message', 'adforest'); ?>"
                                              rows="6"><?php echo isset($user_desc) ? esc_html($user_desc) : ""; ?></textarea>
                                </div>
                            </div>
                            <?php
                            if ($adforest_theme['sb_show_whatsapp_intro'] == '1') {
                                echo '<div class="col-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="input-style-1">
                                            <label>' . esc_html__('Whatsapp Intro', 'adforest') . ' <span class="color-red"></span></label>
                                            <textarea name="sb_user_whatsapp_intro" rows="6">' . esc_attr(get_user_meta($user_info->ID, '_sb_user_whatsapp_intro', true)) . '</textarea>
                                        </div>
                                    </div>';
                            }
                            ?>
                            <input type="hidden" id="adforest_profile_msg"
                                   value="<?php echo esc_attr__('Profile saved successfully.', 'adforest'); ?>"/>
                            <input type="hidden" id="sb-profile-token"
                                   value="<?php echo wp_create_nonce('sb_profile_secure'); ?>"/>
                            <div class="col-lg-12">
                                <div class="button-container">
                                    <a data-target="#changePasswordModal" data-toggle="modal"
                                       class="dark-btn changePasswordDashboard adt-button-dark"><?php echo esc_html__("Change Password", "adforest"); ?></a>
                                    <button type="submit"
                                            class="main-btn "><?php echo esc_html__("Update My Info", "adforest"); ?></button>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="button-container">
                                    <?php echo $delete_account_html; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php
                    echo '<div id="changePasswordModal" class="modal fade change-pass-modal" role="dialog">
                            <div class="modal-dialog">
                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header rte">
                                        <h3 class="modal-title">' . esc_html__('Password Change', 'adforest') . '</h3>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form id="sb-change-password">
                                        <div class="modal-body">
                                            <div class="input-style-1">
                                                <label>' . esc_html__('Current Password', 'adforest') . '</label>
                                                <input placeholder="' . esc_html__('Current Password', 'adforest') . '" type="password"  name="current_pass" id="current_pass">
                                            </div>
                                            <div class="input-style-1">
                                                <label>' . esc_html__('New Password', 'adforest') . '</label>
                                                <input placeholder="' . esc_html__('New Password', 'adforest') . '" type="password" name="new_pass" id="new_pass">
                                            </div>
                                            <div class="input-style-1">
                                                <label>' . esc_html__('Confirm New Password', 'adforest') . '</label>
                                                <input placeholder="' . esc_html__('Confirm Password', 'adforest') . '" type="password" name="con_new_pass" id="con_new_pass">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <input type="hidden" id="sb-profile-reset-pass-token" value="' . wp_create_nonce('sb_profile_reset_pass_secure') . '" />
                                            <button class="dark-btn" type="button" id="change_pwd">' . esc_html__('Reset My Account', 'adforest') . '</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>';
                    ?>
                </div>
            </div>
        </div>
        <div class="row">

        </div>
    </div>

<?php
$sb_user_phone_num = get_user_meta($user_id, '_sb_contact', true);
$phone_verified_html = "";
if (isset($sb_user_phone_num) && $sb_user_phone_num != '' && !$is_firebase) {
    $nonce = wp_create_nonce('verify_phone_nonce');
    $phone_verified_html = '<form id="sb-ph-verification">
                                        <div class="modal-body">
                                           <div class="input-style-1 sb_ver_ph_div">
                                             <label>' . esc_html__('Phone number', 'adforest') . '</label>
                                             <input value="' . esc_html($sb_user_phone_num) . '" type="text" name="sb_ph_number" id="sb_ph_number" readonly>
                                           </div>
                                           <div class="input-style-1 sb_ver_ph_code_div no-display">
                                             <label>' . esc_html__('Enter code', 'adforest') . '</label>
                                             <input type="text" name="sb_ph_number_code" id="sb_ph_number_code" required>
                                               <small class="pull-right">' . esc_html__('Did not get code?', 'adforest') . ' <a href="javascript:void(0);" class="small_text" id="resend_now" data-security="'. $nonce .'">' . esc_html__('Resend now', 'adforest') . '</a></small>
                                           </div>
                                        </div>
                                        <div class="modal-footer">
                                              <button class="main-btn danger-btn" type="button" id="sb_verification_ph" data-security="'. $nonce .'">' . esc_html__('Verify now', 'adforest') . '</button>
                                              <button class="dark-btn no-display" type="button" id="sb_verification_ph_back">' . esc_html__('Processing ...', 'adforest') . '</button>
                                              <button class="dark-btn no-display" type="button" id="sb_verification_ph_code">' . esc_html__('Verify now', 'adforest') . '</button>
                                        </div>
                                 </form>';
} else if ($is_firebase) {
    $phone_verified_html .= '<form id="sb-ph-verification">
                                <div class="modal-body">           
                                    <div class="input-style-1 sb_ver_ph_code_div ">
                                        <label>' . esc_html__('Enter code', 'adforest') . '</label>
                                        <input type="text" name="sb_ph_number_code" id="sb_ph_number_code">                                              
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button class="dark-btn test" type="button" id="sb_verify_otp">' . esc_html__('Verify now', 'adforest') . '</button>
                                    <button class="dark-btn no-display" type="button" id="sb_verification_ph_back">' . esc_html__('Processing ...', 'adforest') . '</button>
                                    <button class="dark-btn no-display" type="button" id="sb_verification_ph_code">' . esc_html__('Verify now', 'adforest') . '</button>
                                </div>
                            </form>';
}
echo '<div class="custom-modal">
        <div id="verification_modal"
             class="sb-verify-modal modal fade"
             role="dialog"
             data-bs-backdrop="static"
             data-bs-keyboard="false">
           <div class="modal-dialog">
              <!-- Modal content-->
              <div class="modal-content">
                 <div class="modal-header">
                    <span class="modal-title">' . esc_html__('Verify phone number', 'adforest') . '</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                  ' . $phone_verified_html . '
              </div>
           </div>
        </div>
   </div>';
?>
    <style>
        .suggestions-box {
            position: absolute;
            background: #fff;
            z-index: 9999;
            width: 500px;
            max-height: 200px;
            overflow-y: auto;
        }

        .suggestion-item {
            padding: 8px;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background: #f0f0f0;
        }
    </style>
<?php
