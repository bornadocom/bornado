<?php
// ============= ShortCode for sign up ============= //
if (!function_exists('register_user_elementor')) {
    function register_user_elementor($params)
    {
        global $adforest_theme;
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $section_title = $description = $section_title_2 = $description_2 = $button_title = $button_link = $main_link = $bg_img = $is_captcha = $terms_title = $terms_link = '';

        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $description = isset($params['description']) ? $params['description'] : "";
        $section_title_2 = isset($params['section_title_2']) ? $params['section_title_2'] : "";
        $button_title = isset($params['button_title']) ? $params['button_title'] : "";
        $button_link = isset($params['button_link']) ? $params['button_link'] : "";
        $main_link = isset($params['main_link']) ? $params['main_link'] : "";
        $sec_img = isset($params['bg_img']) ? $params['bg_img'] : "";
        $is_captcha = isset($params['is_captcha']) ? $params['is_captcha'] : "";
        $terms_title = isset($params['terms_title']) ? $params['terms_title'] : "";
        $terms_link = isset($params['terms_link']) ? $params['terms_link'] : "";
        $description_2 = isset($params['description_2']) ? $params['description_2'] : "";
        $sb_sign_in_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_sign_in_page']);

        $captcha_type = isset($adforest_theme['google-recaptcha-type']) && !empty($adforest_theme['google-recaptcha-type']) ? $adforest_theme['google-recaptcha-type'] : 'v2';
        $captcha = '<input type="hidden" value="no" name="is_captcha" />';
        if ($captcha_type == 'v2') {
            if ($is_captcha == 'with' && $adforest_theme['google_api_key'] != "") {
                $captcha = '<div class="form-group"><div class="g-recaptcha" data-sitekey="' . $adforest_theme['google_api_key'] . '"></div></div><input type="hidden" value="yes" name="is_captcha" />';
            }
        } else {
            $captcha = '<input type="hidden" value="yes" name="is_captcha" />';
        }

        if (!adforest_vc_forntend_edit() && !is_admin()) {
            adforest_user_logged_in();
        }

        $contact_number_html = '<input class="form-control" id="adforest_contact_number" name="sb_reg_contact" data-parsley-type="integer" data-parsley-required="true" data-parsley-error-message="' . __('This field is required.Should be a valid integer value', 'adforest-elementor') . '" placeholder="' . __('Your Contact Number', 'adforest-elementor') . '" type="text">';
        if (isset($adforest_theme['sb_user_phone_required']) && !$adforest_theme['sb_user_phone_required']) {
            $contact_number_html = '<input id="adforest_contact_number" placeholder="' . __('Your Contact Number', 'adforest-elementor') . '" class="form-control" type="text" name="sb_reg_contact">';
        }

        $sms_gateway = adforest_verify_sms_gateway();
        if ($sms_gateway != "") {
        }
        $contact_number_html = '<input placeholder="' . __('+CountrycodePhonenumber', 'adforest-elementor') . '" class="form-control" type="text" name="sb_reg_contact" data-parsley-required="true" data-parsley-pattern="/\+[0-9]+$/" data-parsley-error-message="' . __('Format should be +CountrycodePhonenumber', 'adforest-elementor') . '">';

        $sb_register_with_phone = isset($adforest_theme['sb_register_with_phone']) ? $adforest_theme['sb_register_with_phone'] : false;
        if ($sb_register_with_phone) {
            $app_key = isset($adforest_theme['sb_firebase_apikey']) && $adforest_theme['sb_firebase_apikey'] != "" ? $adforest_theme['sb_firebase_apikey'] : "";
            $project_id = isset($adforest_theme['sb_firebase_projectId']) && $adforest_theme['sb_firebase_projectId'] != "" ? $adforest_theme['sb_firebase_projectId'] : "";
            $sender_id = isset($adforest_theme['sb_firebase_messagingSenderId']) && $adforest_theme['sb_firebase_messagingSenderId'] != "" ? $adforest_theme['sb_firebase_messagingSenderId'] : "";
            $app_id = isset($adforest_theme['sb_firebase_appId']) && $adforest_theme['sb_firebase_appId'] != "" ? $adforest_theme['sb_firebase_appId'] : "";
            $ajax_url = apply_filters('adforest_set_query_param', admin_url('admin-ajax.php'));
        }
        $default_registration_form = isset($adforest_theme['sb_default_registration_form']) ? $adforest_theme['sb_default_registration_form'] : "email";
        $register_with_phone_button = "";

        if (isset($_GET['reg_type']) && $_GET['reg_type'] != "") {
            $default_registration_form = sanitize_text_field($_GET['reg_type']);
        } else {
            $default_registration_form = 'email';
        }

        if ($default_registration_form == "email" && $sb_register_with_phone) {
            $redirect_url = get_the_permalink() . "?reg_type=phone";
            $register_with_phone_button = '<a class="adt-button-dark btn-block text-center text-sm" href="' . $redirect_url . '">' . esc_html__('Register with Phone Number', 'adforest-elementor') . '</a>';
        } else if ($default_registration_form == "phone" && $sb_register_with_phone) {
            $redirect_url = get_the_permalink() . "?reg_type=email";
            $register_with_phone_button = '<a class="adt-button-dark btn-block text-center text-sm" href="' . $redirect_url . '">' . esc_html__('Register with Email', 'adforest-elementor') . '</a>';
            $contact_number_html = '<input placeholder="' . __('+CountrycodePhonenumber', 'adforest-elementor') . '" class="form-control" type="text" name="adforest_reg_number" id="adforest_reg_number" data-parsley-required="true" data-parsley-pattern="/\+[0-9]+$/" data-parsley-error-message="' . __('Format should be +CountrycodePhonenumber', 'adforest-elementor') . '">';
        }

        if ($sb_register_with_phone && $default_registration_form == "phone") {
            $modal_html = adforest_phone_verification_modal();
        }

        $social_login = '';
        $social_linked = (isset($social_linked) && $social_linked != "") ? $social_linked : __("Signin With LinkedIn", 'adforest-elementor');
        $linkedin_api_key = '';
        if ((isset($adforest_theme['adforest_linkedin_api_key'])) && $adforest_theme['adforest_linkedin_api_key'] != '' && (isset($adforest_theme['adforest_linkedin_api_secret'])) && $adforest_theme['adforest_linkedin_api_secret'] != '' && (isset($adforest_theme['adforest_redirect_uri'])) && $adforest_theme['adforest_redirect_uri'] != '') {
            $linkedin_api_key = ($adforest_theme['adforest_linkedin_api_key']);
            $linkedin_secret_key = ($adforest_theme['adforest_linkedin_api_secret']);
            $redirect_uri = ($adforest_theme['adforest_redirect_uri']);
            $linkedin_url = 'https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=' . $linkedin_api_key . '&redirect_uri=' . $redirect_uri . '&state=popup&scope=r_liteprofile r_emailaddress';
            $social_login .= '<li>
                               <a href="' . esc_url($linkedin_url) . '" class="btn-social btn-linkedIn socials-links-items">
                                <img src="' . get_template_directory_uri() . '/images/linkedin.png"  alt="' . esc_html__('facebook logo', 'adforest-elementor') . '" />
                                </a>
                                </li>';
        }

        if ($adforest_theme['fb_api_key'] != "") {
            $social_login .= '<li><a class="btn socials-links-items btn-social btn-facebook" onclick="hello(\'facebook\').login(' . "{
                                    scope : 'email',
                                    }" . ')">
                          <img src="' . get_template_directory_uri() . '/images/fb.png"  alt="' . esc_html__('facebook logo', 'adforest-elementor') . '" />
                          </a></li>';
        }
        if ($adforest_theme['gmail_api_key'] != "") {
            $social_login .= '<li><a class="btn socials-links-items btn-social btn-google" onclick="hello(\'google\').login(' . "{scope : 'email'}" . ')">
                                <img src="' . get_template_directory_uri() . '/images/btn_google.png"  alt="' . esc_html__('google logo', 'adforest-elementor') . '" />
                              </a></li>';
        }


        if ($social_login != '') {
            $social_login .= '<input type="hidden" id="sb-social-login-nonce" value="' . wp_create_nonce('sb_social_login_nonce') . '" />';
        }
        $username_col = (isset($adforest_theme['sb_user_phone_show_on_reg']) && $adforest_theme['sb_user_phone_show_on_reg']) ? 6 : 12;
        ?>

        <section class="adt-sign-up-section test">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-7 left-content">
                        <div class="adt-signup-left-content">
                            <img src="<?php echo $sec_img; ?>" alt="login-img">
                            <h3><?php echo esc_html($section_title_2); ?></h3>
                            <p><?php echo esc_html($description_2); ?></p>
                            <a href="<?php echo esc_url($button_link); ?>"
                               class="adt-theme-button-2"><?php echo esc_html($button_title); ?></a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="adt-signup-right-content">
                            <h4><?php echo esc_html($section_title); ?></h4>
                            <p><?php echo esc_html($description); ?></p>
                            <?php if (!isset($_GET['reg_type']) || sanitize_text_field($_GET['reg_type']) == 'email') { ?>
                                <form id="adforest-signup-form">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-<?php echo $username_col ?>">
                                            <div class="field-box">
                                                <label for="sb_reg_name"><?php echo __("Name", 'adforest-elementor'); ?></label>
                                                <input type="text" name="sb_reg_name" id="sb_reg_name"
                                                       placeholder="<?php echo esc_attr__("Enter Your Name", "adforest-elementor"); ?>"
                                                       data-parsley-required="true"
                                                       data-parsley-error-message="<?php echo esc_attr__('Please enter your name.', 'adforest-elementor'); ?>">
                                            </div>
                                        </div>
                                        <?php if (isset($adforest_theme['sb_user_phone_show_on_reg']) && $adforest_theme['sb_user_phone_show_on_reg']) { ?>
                                            <div class="col-md-6 col-lg-6">
                                                <div class="field-box">
                                                    <label
                                                            for="sb_reg_contact"><?php echo __("Contact Number", 'adforest-elementor'); ?></label>
                                                    <?php echo $contact_number_html; ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <label for="sb_reg_email"><?php echo __("Email", 'adforest-elementor'); ?></label>
                                                <input type="text" name="sb_reg_email" id="sb_reg_email"
                                                       placeholder="<?php echo esc_attr__("Enter Your Email Address", 'adforest-elementor') ?>"
                                                       data-parsley-required="true"
                                                       data-parsley-type="email"
                                                       data-parsley-error-message="<?php echo esc_attr__('Please enter a valid email address.', 'adforest-elementor'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-6">
                                            <div class="field-box">
                                                <label for="sb_reg_password"><?php echo __("Password", 'adforest-elementor'); ?></label>
                                                <span class="sb_show_pass"><i class="fa fa-eye" aria-hidden="true"></i></span><input
                                                        type="password" name="sb_reg_password" id="sb_reg_password"
                                                        placeholder="<?php echo esc_attr__("Your Password", 'adforest-elementor') ?>"
                                                        data-parsley-required="true"
                                                        data-parsley-error-message="<?php echo __('Please enter your password', 'adforest-elementor'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-6">
                                            <div class="field-box">
                                                <label
                                                        for="sb_reg_password_confirm"><?php echo __("Confirm Password", 'adforest-elementor'); ?></label>
                                                <span class="sb_show_pass2"><i class="fa fa-eye" aria-hidden="true"></i></span><input
                                                        type="password" name="sb_reg_password_confirm"
                                                        id="sb_reg_password_confirm"
                                                        placeholder="<?php echo esc_attr__("Confirm Password", 'adforest-elementor') ?>"
                                                        data-parsley-required="true"
                                                        data-parsley-error-message="<?php echo __('Password does not match', 'adforest-elementor'); ?>"
                                                        data-parsley-equalto="#sb_reg_password">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="pretty p-default p-curve" style="margin-right: 0 !important">
                                                <input type="checkbox" id="minimal-checkbox-1"
                                                       name="minimal-checkbox-1" data-parsley-required="true"
                                                       data-parsley-error-message="<?php echo __('Please accept terms and conditions.', 'adforest-elementor'); ?>"/>
                                                <div class="state">
                                                    <label for="minimal-checkbox-1">
                                                        <?php echo __('I agree to', 'adforest-elementor'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <a href="<?php echo esc_url($terms_link); ?>"
                                               target="_blank" rel="noopener noreferrer" class="terms-link">
                                                <?php echo esc_html($terms_title); ?>
                                            </a>
                                        </div>
                                        <?php echo $captcha; ?>
                                        <div id="firebase-recaptcha"></div>
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <button id="sb_register_submit" type="submit"
                                                        class="adt-button-dark"><?php echo __("Register", "adforest-elementor") ?></button>
                                                <button type="button" id="sb_register_msg" style="display: none;"
                                                        class="adt-button-dark"><?php echo __("Processing...", "adforest-elementor") ?></button>
                                                <button type="button" id="sb_register_redirect" style="display: none;"
                                                        class="adt-button-dark"><?php echo __("Redirecting...", "adforest-elementor") ?></button>
                                            </div>
                                            <div class="col-lg-12">
                                                <?php if ($register_with_phone_button || $social_login != "") { ?>
                                                    <div class="option-social">
                                                        <span><?php echo __("or", "adforest-elementor"); ?></span>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($register_with_phone_button) { ?>
                                                    <div class="field-box">
                                                        <?php echo $register_with_phone_button; ?>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($social_login != "") { ?>
                                                    <div class="social-list">
                                                        <ul class="social-item">
                                                            <?php
                                                            echo $social_login;
                                                            ?>
                                                        </ul>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="botm-question-text">
                                                <span><?php echo esc_html__("Already have an account? ", "adforest-elementor"); ?>
                                                    <a href="<?php echo esc_url(get_permalink($sb_sign_in_page)); ?>">
                                                        <?php echo esc_html__("Sign In", "adforest-elementor") ?>
                                                    </a>
                                                </span>
                                            </div>
                                            <input type="hidden" id="sb-register-token"
                                                   value="<?php echo wp_create_nonce('sb_register_secure') ?>"/>
                                            <input type="hidden" id="get_action"
                                                   value="register"/>
                                        </div>
                                    </div>
                                </form>
                            <?php } else if (isset($_GET['reg_type']) && sanitize_text_field($_GET['reg_type']) == 'phone') { ?>
                                <form id="adforest-number-sign-up">
                                    <input type="hidden" id="sb-fb-apikey" value="<?php echo $app_key ?>">
                                    <input type="hidden" id="sb-fb-projectid" value="<?php echo $project_id ?>">
                                    <input type="hidden" id="sb-fb-senderid" value="<?php echo $sender_id ?>">
                                    <input type="hidden" id="sb-fb-appid" value="<?php echo $app_id ?>">
                                    <input type="hidden" value="<?php echo $ajax_url ?>" id="ajax_url">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <label for="adforest_reg_name"><?php echo __("Name", 'adforest-elementor'); ?></label>
                                                <input type="text" name="adforest_reg_name" id="adforest_reg_name"
                                                       placeholder="Enter Your Name" data-parsley-required="true"
                                                       data-parsley-error-message="<?php echo __("Name is required.", "adforest-elementor") ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <label
                                                        for="adforest_reg_number"><?php echo __("Contact Number", 'adforest-elementor'); ?></label>
                                                <?php echo $contact_number_html; ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="pretty p-default p-curve">
                                                <input type="checkbox" id="minimal-checkbox-1"
                                                       name="minimal-checkbox-1" data-parsley-required="true"
                                                       data-parsley-error-message="<?php echo __('Please accept terms and conditions.', 'adforest-elementor'); ?>"/>
                                                <div class="state">
                                                    <label><?php echo __('I agree to ', 'adforest-elementor') ?>
                                                        <a href="<?php echo esc_url($terms_link); ?>">
                                                            <?php echo esc_html($terms_title); ?>
                                                        </a>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php echo $captcha; ?>
                                        <div id="firebase-recaptcha"></div>
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <button
                                                        class="adt-button-dark"><?php echo __("Register Account", "adforest-elementor") ?></button>
                                            </div>
                                            <div class="col-lg-12">
                                                <?php if ($register_with_phone_button || $social_login != "") { ?>
                                                    <div class="option-social">
                                                        <span><?php echo __("or", "adforest-elementor"); ?></span>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($register_with_phone_button) { ?>
                                                    <div class="field-box">
                                                        <?php echo $register_with_phone_button; ?>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($social_login != "") { ?>
                                                    <div class="social-list">
                                                        <ul class="social-item">
                                                            <?php
                                                            echo $social_login;
                                                            ?>
                                                        </ul>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="botm-question-text">
                                                    <span><?php echo esc_html__("Already have an account? "); ?>
                                                        <a href="<?php echo esc_url(get_permalink($sb_sign_in_page)); ?>">
                                                            <?php echo esc_html__("Sign In") ?>
                                                        </a>
                                                    </span>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <input type="hidden" id="verify_account_msg"
                                       value="<?php echo __('Verification email has been sent to your email.', 'adforest-elementor') ?>"/>
                                <input type="hidden" id="verify_recaptcha"
                                       value="<?php echo __('Verify Recaptcha to process', 'adforest-elementor') ?>"/>
                                <input type="hidden" id="admin_verify_account"
                                       value="<?php echo __('Admin will verify your account and let you know shortly.', 'adforest-elementor') ?>"/>
                                <input type="hidden" id="get_action"
                                       value="register"/>
                                <?php echo $modal_html; ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php
    }
}

// ============= ShortCode for sign in ============= //
if (!function_exists('login_user_elementor')) {
    function login_user_elementor($params)
    {
        global $adforest_theme;
        $description = isset($params['description']) ? $params['description'] : "";
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $button_link = isset($params['button_link']['url']) ? $params['button_link']['url'] : "";
        $sec_img = isset($params['bg_img']) ? $params['bg_img'] : "";
        $description_2 = isset($params['description_2']) ? $params['description_2'] : "";
        $button_title = isset($params['button_title']) ? $params['button_title'] : "";
        $section_title_2 = isset($params['section_title_2']) ? $params['section_title_2'] : "";
        $is_captcha = isset($params['is_captcha']) ? $params['is_captcha'] : "";
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $sb_sign_up_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_sign_up_page']);

        $sb_register_with_phone = isset($adforest_theme['sb_register_with_phone']) ? $adforest_theme['sb_register_with_phone'] : false;
        $app_key = $app_id = $sender_id = $project_id = "";

        if ($sb_register_with_phone) {
            $app_key = isset($adforest_theme['sb_firebase_apikey']) && $adforest_theme['sb_firebase_apikey'] != "" ? $adforest_theme['sb_firebase_apikey'] : "";
            $project_id = isset($adforest_theme['sb_firebase_projectId']) && $adforest_theme['sb_firebase_projectId'] != "" ? $adforest_theme['sb_firebase_projectId'] : "";
            $sender_id = isset($adforest_theme['sb_firebase_messagingSenderId']) && $adforest_theme['sb_firebase_messagingSenderId'] != "" ? $adforest_theme['sb_firebase_messagingSenderId'] : "";
            $app_id = isset($adforest_theme['sb_firebase_appId']) && $adforest_theme['sb_firebase_appId'] != "" ? $adforest_theme['sb_firebase_appId'] : "";
        }

        $sb_login_with_phone = isset($adforest_theme['sb_register_with_phone']) ? $adforest_theme['sb_register_with_phone'] : false;
        $default_login_form = isset($adforest_theme['sb_default_registration_form']) ? $adforest_theme['sb_default_registration_form'] : "email";
        $contact_number_html = '<input class="form-control" id="adforest_contact_number" name="sb_reg_contact" data-parsley-type="integer" data-parsley-required="true" data-parsley-error-message="' . __('This field is required.Should be a valid integer value', 'adforest-elementor') . '" placeholder="' . __('Your Contact Number', 'adforest-elementor') . '" type="text">';
        if (isset($adforest_theme['sb_user_phone_required']) && !$adforest_theme['sb_user_phone_required']) {
            $contact_number_html = '<input id="adforest_contact_number" placeholder="' . __('Your Contact Number', 'adforest-elementor') . '" class="form-control" type="text" name="sb_reg_contact">';
        }
        $sms_gateway = adforest_verify_sms_gateway();
        if ($sms_gateway != "") {
        }
        $contact_number_html = '<input placeholder="' . __('+CountrycodePhonenumber', 'adforest-elementor') . '" class="form-control" type="text" name="sb_reg_contact" data-parsley-required="true" data-parsley-pattern="/\+[0-9]+$/" data-parsley-error-message="' . __('Format should be +CountrycodePhonenumber', 'adforest-elementor') . '">';

        $login_with_phone_button = "";
        if (isset($_GET['log_type']) && $_GET['log_type'] != "") {
            $default_login_form = sanitize_text_field($_GET['log_type']);
        } else {
            $default_login_form = 'email';
        }

        if ($default_login_form == "email" && $sb_register_with_phone) {
            $redirect_url = get_the_permalink() . "?log_type=phone";
            $login_with_phone_button = '<a class="adt-button-dark btn-block text-center text-sm"  href="' . $redirect_url . '">' . esc_html__('Login with Phone Number', 'adforest-elementor') . '</a>';
        } else if ($default_login_form == "phone" && $sb_login_with_phone) {
            $redirect_url = get_the_permalink() . "?log_type=email";
            $login_with_phone_button = '<a class="adt-button-dark btn-block text-center text-sm"  href="' . $redirect_url . '">' . esc_html__('Login with Email', 'adforest-elementor') . '</a>';
            $contact_number_html = '<input placeholder="' . __('+CountrycodePhonenumber', 'adforest-elementor') . '" class="form-control" type="text" name="sb_reg_phone" id="sb_reg_phone" data-parsley-required="true" data-parsley-pattern="/\+[0-9]+$/" data-parsley-error-message="' . __('Format should be +CountrycodePhonenumber', 'adforest-elementor') . '">';
        }
        $captcha_type = isset($adforest_theme['google-recaptcha-type']) && !empty($adforest_theme['google-recaptcha-type']) ? $adforest_theme['google-recaptcha-type'] : 'v2';
        $key = isset($adforest_theme['google_api_key']) && !empty($adforest_theme['google_api_key']) ? $adforest_theme['google_api_key'] : '';
        $captcha = '<input type="hidden" value="no" name="is_captcha" />';
        if ($captcha_type == 'v2') {
            if ($is_captcha == 'with' && $key != "") {
                $captcha = '<div class="form-group"><div class="g-recaptcha" data-sitekey="' . $key . '"></div></div><input type="hidden" value="yes" name="is_captcha" />';
            }
        } else {
            $captcha = '<input type="hidden" value="yes" name="is_captcha" id="is_captcha" />';
        }

        if (!adforest_vc_forntend_edit() && !is_admin()) {
            adforest_user_logged_in();
        }

        $social_login = '';
        $social_linked = (isset($social_linked) && $social_linked != "") ? $social_linked : __("Signin With LinkedIn", 'adforest-elementor');
        $linkedin_api_key = '';
        if ((isset($adforest_theme['adforest_linkedin_api_key'])) && $adforest_theme['adforest_linkedin_api_key'] != '' && (isset($adforest_theme['adforest_linkedin_api_secret'])) && $adforest_theme['adforest_linkedin_api_secret'] != '' && (isset($adforest_theme['adforest_redirect_uri'])) && $adforest_theme['adforest_redirect_uri'] != '') {
            $linkedin_api_key = ($adforest_theme['adforest_linkedin_api_key']);
            $linkedin_secret_key = ($adforest_theme['adforest_linkedin_api_secret']);
            $redirect_uri = ($adforest_theme['adforest_redirect_uri']);
            $linkedin_url = 'https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=' . $linkedin_api_key . '&redirect_uri=' . $redirect_uri . '&state=popup&scope=r_liteprofile r_emailaddress';
            $social_login .= '<li>
                               <a href="' . esc_url($linkedin_url) . '" class="btn-social btn-linkedIn socials-links-items">
                                <img src="' . get_template_directory_uri() . '/images/linkedin.png"  alt="' . esc_html__('facebook logo', 'adforest-elementor') . '" />
                                </a>
                                </li>';
        }

        if ($adforest_theme['fb_api_key'] != "") {
            $social_login .= '<li><a class="btn socials-links-items btn-social btn-facebook" onclick="hello(\'facebook\').login(' . "{
                                    scope : 'email',
                                    }" . ')">
                          <img src="' . get_template_directory_uri() . '/images/fb.png"  alt="' . esc_html__('facebook logo', 'adforest-elementor') . '" />
                          </a></li>';
        }
        if ($adforest_theme['gmail_api_key'] != "") {
            $social_login .= '<li><a class="btn socials-links-items btn-social btn-google" onclick="hello(\'google\').login(' . "{scope : 'email'}" . ')">
                                <img src="' . get_template_directory_uri() . '/images/btn_google.png"  alt="' . esc_html__('google logo', 'adforest-elementor') . '" />
                              </a></li>';
        }


        if ($social_login != '') {
            $social_login .= '<input type="hidden" id="sb-social-login-nonce" value="' . wp_create_nonce('sb_social_login_nonce') . '" />';
        }
        ?>

        <section class="adt-sign-up-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-7 left-content">
                        <div class="adt-signup-left-content">
                            <img src="<?php echo $sec_img; ?>" alt="login-img">
                            <h3><?php echo esc_html($section_title_2); ?></h3>
                            <p><?php echo esc_html($description_2); ?></p>
                            <a href="<?php echo esc_url($button_link); ?>"
                               class="adt-theme-button-2"><?php echo esc_html($button_title); ?></a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="adt-signup-right-content">
                            <h4><?php echo esc_html($section_title); ?></h4>
                            <p><?php echo esc_html($description); ?></p>
                            <?php if (!isset($_GET['log_type']) || sanitize_text_field($_GET['log_type']) == 'email') { ?>
                                <form id="adforest_login_user" data-parsley-validate>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <label for="sb_reg_email"><?php echo __("Email", "adforest-elementor"); ?></label>
                                                <input type="email" name="sb_reg_email" id="sb_reg_email"
                                                       placeholder="<?php echo esc_attr__("Enter Your Email Address", "adforest-elementor") ?>"
                                                       required
                                                       data-parsley-type="email">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <label for="sb_reg_password"><?php echo __("Password", "adforest-elementor"); ?></label>
                                                <span class="sb_show_pass"><i class="fa fa-eye" aria-hidden="true"></i></span>
                                                <input type="password" name="sb_reg_password" id="sb_reg_password"
                                                       placeholder="<?php echo esc_attr__("Your Password", "adforest-elementor") ?>"
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-check">
                                                <div class="pretty p-default p-curve">
                                                    <input type="checkbox" name="is_remember"
                                                           id="is_remember"/>
                                                    <div class="state">
                                                        <label><?php echo __('Remember Me', 'adforest-elementor') ?></label>
                                                    </div>
                                                </div>
                                                <div class="field-box">
                                                    <div class="forget-password">
                                                        <a href="#" data-bs-toggle="modal"
                                                           data-bs-target="#forgot_password_modal">
                                                            <?php echo __('Forget Password', 'adforest-elementor') ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php echo $captcha; ?>
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <button type="submit" id="adforest_login_button"
                                                        class="adt-button-dark">
                                                    <?php echo __("Sign In", "adforest-elementor") ?>
                                                    <i id='login-spinner-icon' class="fa fa-circle-o-notch fa-spin"
                                                       style="display: none; color: #fff;"></i>
                                                </button>
                                                <input type="hidden" id="sb-login-token"
                                                       value="<?php echo wp_create_nonce('sb_login_secure') ?>"/>
                                            </div>
                                            <div class="col-lg-12">
                                                <?php if ($login_with_phone_button || $social_login != "") { ?>
                                                    <div class="option-social">
                                                        <span><?php echo __("or", "adforest-elementor"); ?></span>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($login_with_phone_button) { ?>
                                                    <div class="field-box">
                                                        <?php echo $login_with_phone_button; ?>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($social_login != "") { ?>
                                                    <div class="social-list">
                                                        <ul class="social-item">
                                                            <?php
                                                            echo $social_login;
                                                            ?>
                                                        </ul>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="botm-question-text">
                                            <span><?php echo esc_html__("Don't have an account? ", "adforest-elementor"); ?>
                                                <a href="<?php echo esc_url(get_permalink($sb_sign_up_page)); ?>"
                                                ><?php echo esc_html__("Sign Up", "adforest-elementor") ?></a>
                                            </span>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="get_action"
                                           value="login"/>
                                </form>
                            <?php } else if (isset($_GET['log_type']) && sanitize_text_field($_GET['log_type']) == 'phone') { ?>
                                <form id="login_with_phone">
                                    <input type="hidden" id="sb-fb-apikey" value="<?php echo $app_key ?>">
                                    <input type="hidden" id="sb-fb-projectid" value="<?php echo $project_id ?>">
                                    <input type="hidden" id="sb-fb-senderid" value="<?php echo $sender_id ?>">
                                    <input type="hidden" id="sb-fb-appid" value="<?php echo $app_id ?>">
                                    <div class="col-lg-12">
                                        <div class="field-box">
                                            <label
                                                    for="sb_reg_phone"><?php echo __("Contact Number", 'adforest-elementor'); ?></label>
                                            <?php echo $contact_number_html; ?>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-check">
                                                <div class="pretty p-default p-curve">
                                                    <input type="checkbox" name="is_remember"
                                                           id="is_remember"/>
                                                    <div class="state">
                                                        <label><?php echo __('Remember Me', 'adforest-elementor') ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php echo $captcha; ?>
                                        <div id="firebase-recaptcha"></div>
                                        <div class="col-lg-12">
                                            <div class="field-box">
                                                <button id="login_with_phone_btn"
                                                        class="adt-button-dark"><?php echo __("Login", "adforest-elementor") ?>
                                                    <i id='login_with_ph_spinner' class="fa fa-circle-o-notch fa-spin"
                                                       style="display: none; color: #fff;"></i>
                                                </button>
                                            </div>
                                            <div class="col-lg-12">
                                                <?php if ($login_with_phone_button || $social_login != "") { ?>
                                                    <div class="option-social">
                                                        <span><?php echo __("or", "adforest-elementor"); ?></span>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($login_with_phone_button) { ?>
                                                    <div class="field-box">
                                                        <?php echo $login_with_phone_button; ?>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($social_login != "") { ?>
                                                    <div class="social-list">
                                                        <ul class="social-item">
                                                            <?php
                                                            echo $social_login;
                                                            ?>
                                                        </ul>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="botm-question-text">
                                                    <span><?php echo esc_html__("Don't have an account? "); ?>
                                                        <a href="<?php echo esc_url(get_permalink($sb_sign_up_page)); ?>"
                                                        ><?php echo esc_html__("Sign Up") ?></a>
                                                    </span>
                                            </div>
                                            <input type="hidden" id="verify_recaptcha"
                                                   value="<?php echo __('Verify Recaptcha to process', 'adforest-elementor') ?>"/>
                                            <input type="hidden" id="get_action"
                                                   value="login"/>
                                            <input type="hidden" id="phone_login_nonce"
                                                   value="<?php echo wp_create_nonce('sb_phone_login_nonce'); ?>"/>
                                        </div>
                                    </div>
                                </form>
                                <?php echo adforest_phone_verification_modal(); ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php

    }
}
// ============= ShortCode for sign in ============= //

// ============= Contact Us ShortCode  ============= //
if (!function_exists("contact_us_elementor")) {
    function contact_us_elementor($params)
    {
        global $adforest_theme;
        $right_title = $right_description = $address = $phone = $email = $contact_short_code = $left_title_1 = $left_desc_1 = $left_title_2 = $left_desc_2 = $bg_img = "";

        $right_title = isset($params['right_title']) ? $params['right_title'] : '';
        $right_description = isset($params['right_description']) ? $params['right_description'] : '';
        $left_title_1 = isset($params['left_title_1']) ? $params['left_title_1'] : '';
        $left_title_2 = isset($params['left_title_2']) ? $params['left_title_2'] : '';
        $left_desc_1 = isset($params['left_desc_1']) ? $params['left_desc_1'] : '';
        $left_desc_2 = isset($params['left_desc_2']) ? $params['left_desc_2'] : '';
        $address = isset($params['address']) ? $params['address'] : '';
        $phone = isset($params['phone']) ? $params['phone'] : '';
        $sec_img = isset($params['bg_img']) ? $params['bg_img'] : '';
        $email = isset($params['email']) ? $params['email'] : '';
        $contact_short_code = isset($params['contact_short_code']) ? $params['contact_short_code'] : '';
        $social_icons_list = isset($params['social_icons_list']) ? $params['social_icons_list'] : [];
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>

        <section class="adt-contact-us-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-6 col-xl-7">
                        <div class="adt-contact-left-content">
                            <h3><?php echo esc_html($left_title_1) ?></h3>
                            <p><?php echo esc_html($left_desc_1) ?></p>
                            <ul class="social-links">
                                <?php
                                if (is_array($social_icons_list) && count($social_icons_list) > 0) {
                                    foreach ($social_icons_list as $icon) { ?>
                                        <li>
                                            <a href="<?php echo esc_url($icon['link']); ?>">
                                                <i class="<?php echo $icon['icon']['value']; ?>"></i>
                                            </a>
                                        </li>
                                    <?php }
                                } ?>
                            </ul>
                            <div class="img-box">
                                <img src="<?php echo $sec_img; ?>" alt="contact-us-img">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xl-5">
                        <div class="adt-contact-right-content">
                            <h4><?php echo esc_html($right_title) ?></h4>
                            <p><?php echo esc_html($right_description) ?></p>
                            <?php echo do_shortcode(adforest_clean_shortcode($contact_short_code)); ?>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="contact-info-heading">
                            <h3><?php echo esc_html($left_title_2) ?></h3>
                            <p><?php echo esc_html($left_desc_2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="contact-info-box">
                            <div class="icon-box">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="meta-box">
                                <h5><?php echo esc_html__("Address", 'adforest-elementor'); ?></h5>
                                <?php echo wp_kses_post($address); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="contact-info-box">
                            <div class="icon-box">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="meta-box">
                                <h5><?php echo esc_html__("Phone", 'adforest-elementor'); ?></h5>
                                <?php echo wp_kses_post($phone); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="contact-info-box">
                            <div class="icon-box">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="meta-box">
                                <h5><?php echo esc_html__("Email Address", 'adforest-elementor'); ?></h5>
                                <?php echo wp_kses_post($email); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php
    }
}
// ============= Contact Us ShortCode  ============= //

// ============= Packages ShortCode  ============= //
if (!function_exists('price_modern3_short_base_func')) {
    function price_modern3_short_base_func($params)
    {
        global $adforest_theme;
        $section_design = $params['section_design'];
        $section_title = $params['section_title'];
        $section_description = $params['section_description'];
        $packages = isset($params['packages']) ? $params['packages'] : [];
        $cols_option = $params['cols_option'];
        $cols = '4';
        if ($cols_option == '1') {
            $cols = '12';
        } elseif ($cols_option == '2') {
            $cols = '6';
        } elseif ($cols_option == '3') {
            $cols = '4';
        } elseif ($cols_option == '4') {
            $cols = '3';
        }
        $design_type = "";
        $product_card_design = "";
        $individual_class = [];
        $class_count = 0;
        if ($section_design == 'modern') {
            $design_type = "adt-pricing-plan-section adt-pricing-plan-section-advanced";
            $product_card_design = "adt-pricing-plan-card";
        } elseif ($section_design == 'fancy') {
            $design_type = "adt-pricing-plan-section adt-estate-pricing-section";
            $product_card_design = "adt-pricing-plan-card adt-classified-card";
            $individual_class = ['individual', 'bussiness', 'premium'];
            $class_count = count($individual_class);
        }
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="<?php echo $design_type; ?>">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="heading-content">
                            <?php if ($section_design == 'modern') { ?>
                                <div class="label"><?php echo esc_html($section_title) ?></div>
                                <h2><?php echo esc_html($section_description) ?></h2>
                            <?php } else { ?>
                                <h2><?php echo esc_html($section_title) ?></h2>
                                <p><?php echo esc_html($section_description) ?></p>
                            <?php } ?>
                        </div>
                    </div>

                    <?php
                    if (is_array($packages) && count($packages) > 0) {
                        foreach ($packages as $index => $item) {
                            $product_id = intval($item['product_id']);
                            if (!$product_id) {
                                continue;
                            }
                            $product_meta = get_post_meta($product_id);
                            $product_title = get_the_title($product_id);
                            $currency_symbol = get_woocommerce_currency_symbol();
                            $sale_price = isset($product_meta['_sale_price'][0]) ? $product_meta['_sale_price'][0] : "";
                            $regular_price = $product_meta['_regular_price'][0] ?? '';
                            $package_expiry_days = $product_meta['package_expiry_days'][0] ?? '';
                            $simple_ad_expiry_days = $product_meta['package_ad_expiry_days'][0] ?? '';
                            $package_free_ads = $product_meta['package_free_ads'][0] ?? '';
                            $package_featured_ads = $product_meta['package_featured_ads'][0] ?? '';
                            $featured_expiry_days = $product_meta['package_adFeatured_expiry_days'][0] ?? '';
                            $package_bump_ads = $product_meta['package_bump_ads'][0] ?? '';
                            $package_num_of_images = $product_meta['package_num_of_images'][0] ?? '';
                            $package_allow_bidding = $product_meta['package_allow_bidding'][0] ?? '';
                            $package_video_links = $product_meta['package_video_links'][0] ?? '';
                            $package_allow_tags = $product_meta['package_allow_tags'][0] ?? '';
                            $product_categories = isset($product_meta['package_allow_categories']) ? $product_meta['package_allow_categories'][0] : "";
                            $product_events = isset($product_meta['number_of_events']) ? $product_meta['number_of_events'][0] : "";

                            $li = "";
                            $cat_list_ = "";

                            if (isset($product_categories) && $product_categories != "") {
                                $li = '<li id="toggle_categories" style="cursor: pointer;"><i class="fas fa-check-circle"></i>' . __("Categories", "adforest-elementor") . ': ' . __("Show All", "adforest-elementor") . '<div><i class="fa fa-question-circle"></i></div></li>';
                                $selected_categories_arr = explode(",", $product_categories);
                                $cat_list_ = '';
                                foreach ($selected_categories_arr as $single_cat_id) {
                                    $category = get_term($single_cat_id);
                                    if ($category && !is_wp_error($category) && $category->parent == 0) {
                                        $cat_list_ .= '<li><i class="fa fa-check-circle"></i> ' . esc_html($category->name) . '</li>';
                                    }
                                }
                            } else {
                                $li = '<li><i class="fas fa-check-circle"></i>' . __("Categories", "adforest-elementor") . ': ' . __("All", "adforest-elementor") . '</li>';
                            }

                            if ($section_design == 'fancy') {
                                $current_class = $individual_class[$index % $class_count];
                            }
                            ?>
                            <div class="col-sm-6 col-md-6 col-lg-4 col-xl-<?php echo esc_attr($cols); ?>">
                                <div class="<?php echo $product_card_design; ?>">
                                    <div class="price-box <?php echo $section_design == 'fancy' ? $current_class : ""; ?>">
                                        <i class="fas fa-bolt"></i>
                                        <h4><?php echo esc_html($product_title); ?></h4>
                                        <h3>
                                            <?php
                                            if (!empty($sale_price)) {
                                                echo esc_html($currency_symbol . $sale_price) . ' ';
                                                echo '<del>' . esc_html($currency_symbol . $regular_price) . '</del>';
                                            } else {
                                                echo esc_html($currency_symbol . $regular_price);
                                            }
                                            ?>
                                        </h3>

                                    </div>
                                    <ul>
                                        <?php if (isset($package_expiry_days) && $package_expiry_days !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Validity (Days):", "adforest-elementor"); ?>
                                                <?php echo ($package_expiry_days == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($package_expiry_days); ?>
                                            </li>
                                        <?php } ?>

                                        <?php if (isset($package_free_ads) && $package_free_ads !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Ads:", "adforest-elementor"); ?>
                                                <?php echo ($package_free_ads == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($package_free_ads); ?>
                                            </li>
                                        <?php } ?>

                                        <?php if (isset($simple_ad_expiry_days) && $simple_ad_expiry_days !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Ads Expiry (Days):", "adforest-elementor"); ?>
                                                <?php echo ($simple_ad_expiry_days == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($simple_ad_expiry_days); ?>
                                            </li>
                                        <?php } ?>

                                        <?php if (isset($package_featured_ads) && $package_featured_ads !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Featured Ads:", "adforest-elementor"); ?>
                                                <?php echo ($package_featured_ads == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($package_featured_ads); ?>
                                            </li>
                                        <?php } ?>

                                        <?php if (isset($featured_expiry_days) && $featured_expiry_days !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Featured Ads Expiry:", "adforest-elementor"); ?>
                                                <?php echo ($featured_expiry_days == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($featured_expiry_days); ?>
                                            </li>
                                        <?php } ?>

                                        <?php if (isset($package_bump_ads) && $package_bump_ads !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Bump-up Ads:", "adforest-elementor"); ?>
                                                <?php echo ($package_bump_ads == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($package_bump_ads); ?>
                                            </li>
                                        <?php } ?>

                                        <?php if (isset($package_num_of_images) && $package_num_of_images !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("No Of Images :", "adforest-elementor"); ?>
                                                <?php echo ($package_num_of_images == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($package_num_of_images); ?>
                                            </li>
                                        <?php } ?>

                                        <?php
                                        if (isset($package_allow_bidding) && $package_allow_bidding !== "") {
                                            ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Allow Bidding :", "adforest-elementor"); ?>
                                                <?php echo ($package_allow_bidding == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($package_allow_bidding); ?>
                                            </li>
                                            <?php
                                        } else {
                                            ?>
                                            <li>
                                                <i class="fas fa-times-circle"
                                                   style="color:red;"></i><?php echo esc_html__("Allow Bidding :", "adforest-elementor"); ?>
                                                <?php echo esc_html(isset($package_allow_bidding) && $package_allow_bidding !== "" ? $package_allow_bidding : __('No', 'adforest-elementor')); ?>
                                            </li>
                                            <?php
                                        }
                                        ?>

                                        <?php
                                        if (isset($package_video_links) && $package_video_links !== "no" && $package_video_links != "") {
                                            ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Video URL :", "adforest-elementor"); ?>
                                                <?php
                                                if ( strtolower($package_video_links) === 'yes' ) {
                                                    echo esc_html__( 'Yes', 'adforest' );
                                                }
                                                ?>
                                            </li>
                                            <?php
                                        } else {
                                            ?>
                                            <li>
                                                <i class="fas fa-times-circle"
                                                   style="color:red;"></i><?php echo esc_html__("Video URL :", "adforest-elementor"); ?>
                                                   <?php
                                                    if ( strtolower($package_video_links) === 'no' ) {
                                                        echo esc_html__( 'No', 'adforest' );
                                                    }
                                                    ?>
                                            </li>
                                            <?php
                                        }
                                        ?>

                                        <?php
                                        if (isset($package_allow_tags) && $package_allow_tags !== "no" && $package_allow_tags != '') {
                                            ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("Allow Tags :", "adforest-elementor"); ?>
                                                <?php
                                                if ( strtolower($package_allow_tags) === 'yes' ) {
                                                    echo esc_html__( 'Yes', 'adforest' );
                                                }
                                                ?>
                                            </li>
                                            <?php
                                        } else {
                                            ?>
                                            <li>
                                                <i class="fas fa-times-circle"
                                                   style="color:red;"></i><?php echo esc_html__("Allow Tags :", "adforest-elementor"); ?>
                                                <?php
                                                if ( strtolower($package_allow_tags) === 'no' ) {
                                                    echo esc_html__( 'No', 'adforest' );
                                                }
                                                ?>
                                            </li>
                                            <?php
                                        }
                                        ?>

                                        <?php if (isset($product_events) && $product_events !== "") { ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i><?php echo esc_html__("No Of Events :", "adforest-elementor"); ?>
                                                <?php echo ($product_events == "-1") ? __("Unlimited", "adforest-elementor") : esc_html($product_events); ?>
                                            </li>
                                        <?php } ?>

                                        <?php
                                        if (isset($product_categories) && $product_categories == 'all') {
                                            echo '<li>
                                                       <i class="fas fa-check-circle"></i>' . __("Allowed Categories", "adforest-elementor") . ' : ' . __(" All", "adforest-elementor") . '
                                                </li>';
                                        } elseif (isset($cat_list_) && trim($cat_list_) !== "") { ?>
                                            <li id="toggle_categories" style="cursor: pointer;">
                                                <i class="fas fa-check-circle"></i><?php echo __("Allowed Categories", "adforest-elementor"); ?>
                                                <div class="toggle_categories_list_box">
                                                    <i class="fa fa-question-circle"></i>
                                                    <ul id="categories_list_packages_widget">
                                                        <h5 class="categories_list_header">
                                                            <?php echo __("Allowed Categories", "adforest-elementor"); ?>
                                                        </h5>
                                                        <?php echo $cat_list_; ?>
                                                    </ul>
                                                </div>
                                            </li>
                                        <?php } else {
                                            echo '<li>
                                                       <i class="fas fa-times-circle" style="color: red"></i>' . __("Allowed Categories", "adforest-elementor") . ' : ' . __(" None", "adforest-elementor") . '
                                                </li>';
                                        } ?>
                                    </ul>
                                    <a href="javascript:void(0)"
                                       class="<?php echo $section_design == "fancy" ? "adt-button-dark-1" : "adt-button-dark-1"; ?> adforest_add_cart"
                                       data-product-id="<?php echo esc_attr($product_id); ?>"
                                       data-product-qty="1"><?php echo __("Buy Now", "adforest-elementor") ?></a>
                                </div>
                            </div>
                        <?php }
                    } ?>
                </div>
            </div>
            <?php if ($section_design == 'fancy') { ?>
                <img class="shape-1" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-1.png' ?>"
                     alt="shape">
                <img class="shape-2" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-2.png' ?>"
                     alt="shape">
                <img class="shape-3" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-1.png' ?>"
                     alt="shape">
            <?php } ?>
        </section>
        <?php
    }
}
// ============= Packages ShortCode  ============= //

// ============= About Us ShortCode  ============= //
if (!function_exists('about_us_short_base_func')) {
    function about_us_short_base_func($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $img_1 = isset($params['img_1']) ? $params['img_1']['url'] : "";
        $bg_img = isset($params['bg_img']) ? $params['bg_img']['url'] : "";
        $img_2 = isset($params['img_2']) ? $params['img_2']['url'] : "";
        $exp_head = isset($params['exp_head']) ? $params['exp_head'] : "";
        $exp_desc = isset($params['exp_desc']) ? $params['exp_desc'] : "";
        $video_link = isset($params['video_link']) ? $params['video_link'] : "";
        $features = isset($params['features']) ? $params['features'] : "";

        $youtube_id = '';

        if (preg_match('/[?&]v=([^&]+)/', $video_link, $matches)) {
            $youtube_id = $matches[1];
        }

        $embed_url = 'https://www.youtube.com/embed/' . $youtube_id;

        $style = '';
        $extra_class = '';
        if (!empty($bg_img)) {
            $style = 'style="background-image: url(' . esc_url($bg_img) . ')"';
            $extra_class = "adt-estate-about-section";
        }
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>

        <section class="adt-about-us-section <?php echo $extra_class; ?>" <?php echo $style; ?> >
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="left-img-box">
                            <?php if (!empty($img_1)) { ?>
                                <img class="about-img-1" src="<?php echo esc_url($img_1); ?>" alt="about-img1">
                            <?php } ?>
                            <?php if (!empty($img_2)) { ?>
                                <img class="about-img-2" src="<?php echo esc_url($img_2); ?>" alt="about-img2">
                            <?php } ?>
                            <img class="dots-img"
                                 src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/dots-img.png' ?>"
                                 alt="dots-img">
                            <?php if (!empty($exp_head) || !empty($exp_desc)) { ?>
                                <div class="exp-box">
                                    <?php if (!empty($exp_head)) { ?><strong><?php echo esc_html($exp_head) ?></strong><?php } ?>
                                    <?php if (!empty($exp_desc)) { ?><small><?php echo esc_html($exp_desc) ?></small><?php } ?>
                                </div>
                            <?php } ?>
                            <?php if (!empty($youtube_id)) { ?>
                                <a href="javascript:void(0);" class="play-btn" data-bs-toggle="modal"
                                   data-bs-target="#videoModal">
                                    <i class="fa fa-play"></i>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="right-cont">
                            <div class="sub-cont">
                                <?php if (!empty($section_title)) { ?><small class="label"><?php echo esc_html($section_title) ?></small><?php } ?>
                                <?php if (!empty($section_tagline)) { ?><h2 class="heading"><?php echo esc_html($section_tagline) ?></h2><?php } ?>
                                <?php if (!empty($section_description)) { ?><p><?php echo esc_html($section_description) ?></p><?php } ?>
                            </div>
                            <div class="simple-steps">
                                <ul>
                                    <?php
                                    if (is_array($features) && count($features) > 0) {
                                        foreach ($features as $feature) {
                                            $feature_title = $feature['small_title'] ?? '';
                                            $feature_description = $feature['small_desc'] ?? '';
                                            if ($feature_title === '' && $feature_description === '' && empty($feature['icon'])) {
                                                continue;
                                            }
                                            ?>
                                            <li>
                                                <div class="step-box">
                                                    <div class="left-icon">
                                                        <?php if (!empty($feature['icon'])) { ?>
                                                            <?php if (($feature['icon']['library'] ?? '') == 'svg') { ?>
                                                                <img src="<?php echo esc_url($feature['icon']['value']['url']); ?>" alt="step">
                                                            <?php } else if (!empty($feature['icon']['value'])) { ?>
                                                                <i class="<?php echo esc_attr($feature['icon']['value']); ?>"></i>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </div>
                                                    <div class="right-meta">
                                                        <?php if ($feature_title !== '') { ?><h3><?php echo esc_html($feature_title) ?></h3><?php } ?>
                                                        <?php if ($feature_description !== '') { ?><p><?php echo esc_html($feature_description) ?></p><?php } ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php }
                                    } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img class="vector-group"
                 src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/about-vector-img.png' ?>"
                 alt="vector-group-img">
            <?php if (!empty($bg_img)) { ?>
                <img class="shape-1" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-1.png' ?>"
                     alt="shape">
                <img class="shape-2" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-2.png' ?>"
                     alt="shape">
                <img class="shape-3" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-1.png' ?>"
                     alt="shape">
            <?php } ?>
            <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content bg-dark">
                        <div class="modal-body p-0 position-relative">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                                    data-bs-dismiss="modal" aria-label="Close"></button>
                            <div class="ratio ratio-16x9">
                                <iframe id="youtubeVideo" src="" title="YouTube video" allowfullscreen
                                        allow="autoplay"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var videoModal = document.getElementById('videoModal');
                    var videoIframe = document.getElementById('youtubeVideo');
                    var playBtn = document.querySelector('.play-btn');
                    var videoURL = "<?php echo esc_url($embed_url); ?>";

                    videoModal.addEventListener('show.bs.modal', function () {
                        videoIframe.src = videoURL + "?autoplay=1";
                    });

                    videoModal.addEventListener('hide.bs.modal', function () {
                        videoIframe.src = "";
                    });
                });
            </script>
        </section>

        <?php
    }
}
// ============= About Us ShortCode  ============= //

// ============= Workflow ShortCode  ============= //
if (!function_exists('workflow_short_base_func')) {
    function workflow_short_base_func($params)
    {
        global $adforest_theme;
        $section_title = $params['section_title'] ?? "";
        $section_tagline = $params['section_tagline'] ?? "";
        $icon_1 = $params['icon_1'] ?? "";
        $workflow_title_1 = $params['workflow_title_1'] ?? "";
        $workflow_tagline_1 = $params['workflow_tagline_1'] ?? "";
        $icon_2 = $params['icon_2'] ?? "";
        $workflow_title_2 = $params['workflow_title_2'] ?? "";
        $workflow_tagline_2 = $params['workflow_tagline_2'] ?? "";
        $icon_3 = $params['icon_3'] ?? "";
        $workflow_title_3 = $params['workflow_title_3'] ?? "";
        $workflow_tagline_3 = $params['workflow_tagline_3'] ?? "";
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-work-flow-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="heading-content">
                            <div class="label"><?php echo esc_html($section_title); ?></div>
                            <h2><?php echo esc_html($section_tagline); ?></h2>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="work-process-box">
                            <div class="work-type-outline">
                                <div class="type-outer">
                                    <?php if (isset($icon_1['library']) && $icon_1['library'] === "svg") { ?>
                                        <?php if (isset($icon_1['value']['url']) && !empty($icon_1['value']['url'])) { ?>
                                            <img src="<?php echo esc_url($icon_1['value']['url']); ?>" alt=""/>
                                        <?php } ?>
                                    <?php } elseif (isset($icon_1['value']) && !empty($icon_1['value'])) { ?>
                                        <i class="<?php echo esc_attr($icon_1['value']); ?>"></i>
                                    <?php } ?>
                                </div>
                                <div class="count-outline">
                                    <div class="count">
                                        <span>1</span>
                                    </div>
                                </div>
                            </div>
                            <small class="title"><?php echo esc_html($workflow_title_1); ?></small>
                            <h3 class="heading"><?php echo esc_html($workflow_tagline_1); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="work-process-box">
                            <div class="work-type-outline">
                                <div class="type-outer">
                                    <?php if (isset($icon_2['library']) && $icon_2['library'] === "svg") { ?>
                                        <?php if (isset($icon_2['value']['url']) && !empty($icon_2['value']['url'])) { ?>
                                            <img src="<?php echo esc_url($icon_2['value']['url']); ?>" alt=""/>
                                        <?php } ?>
                                    <?php } elseif (isset($icon_2['value']) && !empty($icon_2['value'])) { ?>
                                        <i class="<?php echo esc_attr($icon_2['value']); ?>"></i>
                                    <?php } ?>
                                </div>
                                <div class="count-outline">
                                    <div class="count">
                                        <span>2</span>
                                    </div>
                                </div>
                            </div>
                            <small class="title"><?php echo esc_html($workflow_title_2); ?></small>
                            <h3 class="heading"><?php echo esc_html($workflow_tagline_2); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="work-process-box">
                            <div class="work-type-outline">
                                <div class="type-outer">
                                    <?php if (isset($icon_3['library']) && $icon_3['library'] === "svg") { ?>
                                        <?php if (isset($icon_3['value']['url']) && !empty($icon_3['value']['url'])) { ?>
                                            <img src="<?php echo esc_url($icon_3['value']['url']); ?>" alt=""/>
                                        <?php } ?>
                                    <?php } elseif (isset($icon_3['value']) && !empty($icon_3['value'])) { ?>
                                        <i class="<?php echo esc_attr($icon_3['value']); ?>"></i>
                                    <?php } ?>
                                </div>
                                <div class="count-outline">
                                    <div class="count">
                                        <span>3</span>
                                    </div>
                                </div>
                            </div>
                            <small class="title"><?php echo esc_html($workflow_title_3); ?></small>
                            <h3 class="heading"><?php echo esc_html($workflow_tagline_3); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= Workflow ShortCode  ============= //

// ============= FAQs ShortCode  ============= //
if (!function_exists('faqs_short_base_func')) {
    function faqs_short_base_func($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $img_1 = isset($params['img_1']) ? $params['img_1']['url'] : "";
        $img_2 = isset($params['img_2']) ? $params['img_2']['url'] : "";
        $faqs = isset($params['faqs']) ? $params['faqs'] : "";
        $faq_count = 0;
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-advanced-faqs">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row align-items-center">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6">
                        <div class="left-main-content">
                            <h6 class="label"><?php echo esc_html($section_title); ?></h6>
                            <h3 class="main-heading"><?php echo esc_html($section_tagline); ?></h3>
                            <p><?php echo esc_html($section_description); ?></p>
                            <div id="accordion" class="accordion-style">
                                <?php
                                if (is_array($faqs) && count($faqs) > 0) {
                                    foreach ($faqs as $index => $faq) {
                                        $faq_count++;
                                        $question = isset($faq['question']) ? $faq['question'] : "";
                                        $answer = isset($faq['answer']) ? $faq['answer'] : "";
                                        $unique_id = 'faq' . $faq_count;

                                        // Determine if this is the first FAQ
                                        $is_first = ($index === 0);
                                        $button_classes = 'btn btn-link' . ($is_first ? '' : ' collapsed');
                                        $collapse_classes = 'collapse' . ($is_first ? ' show' : '');
                                        $aria_expanded = $is_first ? 'true' : 'false';
                                        ?>
                                        <div class="card" id="<?php echo $unique_id; ?>">
                                            <div class="card-header" id="heading<?php echo $unique_id; ?>">
                                                <h5 class="mb-0">
                                                    <button class="<?php echo $button_classes; ?>"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#collapse<?php echo $unique_id; ?>"
                                                            aria-expanded="<?php echo $aria_expanded; ?>"
                                                            aria-controls="collapse<?php echo $unique_id; ?>">
                                                        <span class="text-theme-secondary"><?php echo esc_html($faq_count); ?></span>
                                                        <span class="heading-txt"><?php echo esc_html($question); ?></span>
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="collapse<?php echo $unique_id; ?>"
                                                 class="<?php echo $collapse_classes; ?>"
                                                 aria-labelledby="heading<?php echo $unique_id; ?>"
                                                 data-bs-parent="#accordion">
                                                <div class="card-body">
                                                    <?php echo esc_html($answer); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6">
                        <div class="main-img-box">
                            <img src="<?php echo esc_url($img_1); ?>" alt="faq-img">
                            <img src="<?php echo esc_url($img_2); ?>" alt="faq-img">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= FAQs ShortCode  ============= //

// ============= Partners Slider ShortCode  ============= //
if (!function_exists('partners_sliders_short_base_func')) {
    function partners_sliders_short_base_func($params)
    {
        global $adforest_theme;
        $clients = isset($params['clients']) ? $params['clients'] : "";
        $section_bg = isset($params['section_bg_color']) ? $params['section_bg_color'] : "";

        $loop_slider = isset($params['loop_slider']) && $params['loop_slider'] === 'yes' ? 'true' : 'false';
        $autoplay_slider = isset($params['autoplay_slider']) && $params['autoplay_slider'] === 'yes' ? 'true' : 'false';
        $slider_speed = isset($params['slider_speed']) ? (int) $params['slider_speed'] : 3000;

        $slider_data_atts = "data-loop='{$loop_slider}' data-autoplay='{$autoplay_slider}' data-autoplay-timeout='{$slider_speed}'";

        $style = 'style= "background-color: rgba(0, 0, 0, 0.9);"';
        if ($section_bg == 'light') {
            $style = 'style= "background-color: #ffffff;"';
        }
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-brand-carousel-section" <?php echo $style; ?>>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row" style="width: 100%">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 col-xxl-12">
                        <div class="brand-carousel owl-carousel owl-theme" <?php echo $slider_data_atts; ?>>
                            <?php
                            if (is_array($clients) && count($clients) > 0) {
                                foreach ($clients as $client) {
                                    $client_logo = $client['logo']['url'];
                                    ?>
                                    <div class="item"><img class="img-fluid" src="<?php echo $client_logo ?>"
                                                           alt="brand-logo"></div>
                                    <?php
                                }
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= Partners Slider ShortCode  ============= //

// ============= ShortCode for Main Hero 1 ============= //
if (!function_exists('main_hero_1_shortcode')) {
    function main_hero_1_shortcode($params): void
    {
        global $adforest_theme;
        $section_title = $params['section_title'] ?? "";
        $section_tagline = $params['section_tagline'] ?? "";
        $section_description = $params['section_description'] ?? "";
        $category_sidebar_list = $params['mini_categories_repeater'] ?? "";
        $category_main_repeater = $params['category_main_repeater'] ?? "";
        $popular_categories = $params['popular_categories'] ?? "";
        $bg_img = $params['bg_img'] ?? "";
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';

        if (isset($bg_img['url'])) {
            $bgImageURL = $bg_img['url'];
        } else {
            $bgImageURL = adforest_returnImgSrc($bg_img);
        }
        $style = ($bgImageURL != "") ? ' style="background-image: url(' . $bgImageURL . ');"' : "";

        $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
        $category_count = count($ad_categories);
        if ($category_count > 45) {
            $category_count = "45+";
        }
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-classified-listing-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-4 col-xl-3">
                        <div class="adt-category-list-sidebar">
                            <ul>
                                <?php
                                if (is_array($category_sidebar_list) && count($category_sidebar_list) > 0) {
                                    foreach ($category_sidebar_list as $category) {
                                        $category_id = $category['mini_category'];
                                        $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                        $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';
                                        $ad_count = isset($cat_details['ad_count']) ? intval($cat_details['ad_count']) : 0;

                                        $category_image = '';
                                        if (isset($category['category_image']) && is_array($category['category_image']) && !empty($category['category_image']['url'])) {
                                            $category_image = esc_url($category['category_image']['url']);
                                        }

                                        $link = '';
                                        if (isset($cat_details['id']) && function_exists('adforest_cat_link_page')) {
                                            // Assuming $cat_link_page is defined and safe before this snippet
                                            $link = esc_url(adforest_cat_link_page($cat_details['id'], $cat_link_page, 'cat_id'));
                                        }
                                        ?>
                                        <li>
                                            <div class="adt-category-box test">
                                                <div class="category-meta">
                                                    <?php if (!is_wp_error($link) && !is_wp_error($category_image)) : ?>
                                                        <a href="<?php echo esc_url($link); ?>" class="img-box">
                                                            <img class="img-fluid"
                                                                 src="<?php echo esc_url($category_image); ?>"
                                                                 alt="<?php echo esc_attr($category_name); ?>">
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!is_wp_error($link) && !is_wp_error($category_image)) : ?>
                                                        <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($category_name); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="listing-count"><?php echo esc_html($ad_count) . " ads"; ?></span>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-8 col-xl-9">
                        <div class="adt-classified-listing-top-box" <?php echo $style; ?>>
                            <h4><?php echo esc_html($section_title); ?></h4>
                            <h1><?php echo esc_html($section_tagline); ?></h1>
                            <h5><?php echo esc_html($section_description); ?></h5>
                            <?php
                            if (!empty($popular_categories)) : ?>
                                <ul>
                                    <?php
                                    if (is_array($popular_categories) && count($popular_categories) > 0) {
                                        foreach ($popular_categories as $category) {
                                            $cat_details = adforest_get_taxonomy_details_by_id($category, 'ad_cats');
                                            $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                            $link = '';
                                            if (isset($cat_details['id']) && function_exists('adforest_cat_link_page')) {
                                                $link = esc_url(adforest_cat_link_page($cat_details['id'], $cat_link_page, 'cat_id'));
                                            }
                                            ?>
                                            <li>
                                                <a href="<?php echo esc_url($link); ?>">
                                                    <?php echo esc_html($category_name); ?>
                                                </a>
                                            </li>
                                        <?php }
                                    } ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="adt-explore-categories-content">
                            <div class="explore-categories-top-box">
                                <h2><?php echo __("Explore Categories.", "adforest-elementor"); ?></h2>
                                <div>
                                    <span><?php echo __("Browse All Different ", "adforest-elementor"); ?><strong><?php echo sprintf(__('%s Categories', 'adforest-elementor'), $category_count); ?></strong></span>
                                </div>
                            </div>
                            <div class="explore-category-grid">
                                <?php
                                if (is_array($category_main_repeater) && count($category_main_repeater) > 0) {
                                    foreach ($category_main_repeater as $category) {
                                        $category_id = $category['main_category'];
                                        $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                        $link = isset($cat_details['id']) && function_exists('adforest_cat_link_page')
                                            ? esc_url(adforest_cat_link_page($cat_details['id'], $cat_link_page, 'cat_id'))
                                            : '';

                                        $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                        $cat_img = '';
                                        if (isset($category['category_image']) && is_array($category['category_image']) && !empty($category['category_image']['url'])) {
                                            $cat_img = esc_url($category['category_image']['url']);
                                        }
                                        ?>
                                        <div class="explore-category-box">
                                            <a href="<?php echo esc_url($link); ?>">
                                                <img class="img-fluid" src="<?php echo esc_url($cat_img); ?>"
                                                     alt="<?php echo esc_attr($category_name); ?>">
                                                <span><?php echo esc_html($category_name); ?></span>
                                            </a>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php }
}
// ============= ShortCode for Main Hero 1 ============= //

// ============= ShortCode for Featured Ads ============= //
if (!function_exists("featured_ads_shortcode")) {
    function featured_ads_shortcode($params)
    {
        global $adforest_theme;
        $section_title = $params['section_title'] ?? "";
        $section_bottom_btn_text = $params['section_bottom_btn_text'] ?? "";
        $section_bottom_btn_link = $params['section_bottom_btn_link'] ?? "";
        $section_top_btn_link = $params['section_top_btn_link'] ?? "";
        $section_top_btn_text = $params['section_top_btn_text'] ?? "";
        $section_ad_type = $params['section_ad_type'] ?? "";
        $show_advert = $params['show_advert'] ?? "";
        $advert_img = $params['advert_horizontal'] ?? "";
        $left_sec_ad_type = $params['left_sec_ad_type'] ?? "";
        $left_section_ppp = $params['left_section_ppp'] ?? "";
        $left_ad_img = $params['left_ad'] ?? "";;
        $show_left_ad = $params['show_left_ad'] ?? "";;
        $section_grid_style = $params['section_grid_style'] ?? "";;
        $left_sec_ads_title = $params['left_sec_ads_title'] ?? "";;
        $show_left_sec_ad_type = $params['show_left_sec_ad_type'] ?? "";;
        $number_of_feat_ads_to_show = $params['number_of_feat_ads_to_show'] ?? "";;
        $left_sec_title_limit = $params['left_sec_title_limit'] ?? "";;
        $section_title_limit = $params['section_title_limit'] ?? 0;;
        $grid_columns = $params['grid_columns'] ?? 0;;
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '1');
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == false) ? "one-column-mobile-layout" : "";
        ?>
        <section class="adt-classified-listing-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-4 col-xl-3">
                        <?php echo adforest_display_1_ads_sidebar_section($left_sec_ad_type, $left_section_ppp, $left_ad_img, $show_left_ad, $left_sec_ads_title, $show_left_sec_ad_type, $left_sec_title_limit); ?>
                    </div>
                    <div class="col-lg-8 col-xl-9">
                        <?php if (isset($show_advert) && $show_advert == 'yes' && !empty($advert_img)) { ?>
                            <div class="adt-horizontal-ad-box">
                                <?php echo wp_kses_post($advert_img); ?>
                            </div>
                        <?php } ?>
                        <div class="adt-featured-ads-content">
                            <div class="adt-ads-top-box">
                                <h2><?php echo esc_html($section_title); ?></h2>
                                <a href="<?php echo esc_attr($section_top_btn_link); ?>">
                                    <button class="adt-button-1"><?php echo esc_html($section_top_btn_text); ?></button>
                                </a>
                            </div>
                        </div>
                        <div class="adt-search-ads-grid adt-search-ads-col-<?php echo $grid_columns; ?> <?php echo $sb_2column ?>">
                            <?php
                            $args = [
                                'post_type' => 'ad_post',
                                'posts_per_page' => !empty($number_of_feat_ads_to_show) ? $number_of_feat_ads_to_show : '5',
                                'post_status' => 'publish',
                            ];

                            if ($section_ad_type === 'recent') {
                                $args['meta_key'] = '_adforest_is_feature';
                                $args['meta_value'] = '0';
                                $args['orderby'] = 'date';
                                $args['order'] = 'DESC';
                            } elseif ($section_ad_type === 'featured') {
                                $args['meta_key'] = '_adforest_is_feature';
                                $args['meta_value'] = '1';
                                $args['orderby'] = 'date';
                                $args['order'] = 'DESC';
                            } elseif ($section_ad_type === 'all') {
                                $args['orderby'] = 'date';
                                $args['order'] = 'DESC';
                            }

                            $featured_ads = new WP_Query($args);

                            if ($featured_ads->have_posts()) {
                                while ($featured_ads->have_posts()) {
                                    $featured_ads->the_post();
                                    $ad_details = get_ad_post_details(get_the_ID());
                                    $img = $ad_details['img'];
                                    $truncated_location = $ad_details['truncated_location'];
                                    $price_html = $ad_details['price_html'];
                                    $heart_class = $ad_details['heart_class'];
                                    $is_featured = $ad_details['is_featured'];
                                    $ad_categories_post = $ad_details['categories'];
                                    $ad_poster_img = $ad_details['ad_poster_img'];
                                    $ad_poster_name = $ad_details['ad_poster_name'];
                                    $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);
                                    $ad_permalink = $ad_details['ad_link'];
                                    $ad_title = truncate_string($ad_details['ad_title'], $section_title_limit);
                                    $all_ad_images = $ad_details['all_ad_images'];
                                    $featured_tag = $is_featured ? '<img style="transform: rotate(180deg);" src="' . get_template_directory_uri() . '/images/featured.png' . '" alt="featured-tag" class="featured-tag">' : '';
                                    $selected_categories = $ad_details['categories'];
                                    ?>
                                    <?php if (isset($section_grid_style) && $section_grid_style == '1') { ?>
                                        <?php echo adforest_ad_grid_1($ad_permalink, $img, $is_featured, $ad_categories_post, $ad_details, $ad_title, $truncated_location, $price_html, $heart_class); ?>
                                    <?php } else if (isset($section_grid_style) && $section_grid_style == '2') { ?>
                                        <div class="adt-property-ad-card-outer">
                                            <?php echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class); ?>
                                        </div>
                                    <?php } elseif (isset($section_grid_style) && $section_grid_style == '3') { ?>
                                        <div class="adt-property-ad-card-outer">
                                            <?php echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location); ?>
                                        </div>
                                    <?php } ?>
                                    <?php
                                }
                                wp_reset_postdata();
                            }
                            ?>
                        </div>
                        <div class="col-lg-12">
                            <div class="adt-view-all-box">
                                <a href="<?php echo esc_url($section_bottom_btn_link); ?>">
                                    <button class="adt-button-dark-1"><?php echo esc_html($section_bottom_btn_text); ?></button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php }
}
// ============= ShortCode for Featured Ads ============= //

// ============= ShortCode for Main Hero 2 ============= //
if (!function_exists('main_hero_2_shortcode')) {
    function main_hero_2_shortcode($params)
    {
        global $adforest_theme;
        $section_title = $params['section_title'] ?? "";
        $cat_section_title = $params['cat_section_title'] ?? "";
        $section_tagline = $params['section_tagline'] ?? "";
        $cat_section_tagline = $params['cat_section_tagline'] ?? "";
        $badge_text_top = $params['badge_text_top'] ?? "";
        $badge_text_middle = $params['badge_text_middle'] ?? "";
        $badge_text_btm = $params['badge_text_btm'] ?? "";
        $bg_img = $params['bg_img'] ?? "";
        $bg_img_2 = $params['bg_img_2'] ?? "";
        $bg_img_3 = $params['bg_img_3'] ?? "";
        $bg_img_4 = $params['bg_img_4'] ?? "";
        $arrow_img = $params['arrow_img'] ?? "";
        $mini_categories_repeater = $params['mini_categories_repeater'] ?? "";
        $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';

        if (isset($bg_img['url'])) {
            $bgImageURL = $bg_img['url'];
        } else {
            $bgImageURL = adforest_returnImgSrc($bg_img);
        }
        $style = ($bgImageURL != "") ? ' style="background: rgba(0, 0, 0, 0) url(' . $bgImageURL . ') center top no-repeat; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;"' : "";
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-find-pet-hero" <?php echo $style; ?>>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-find-pet-content">
                            <h4><?php echo esc_html($section_title); ?></h4>
                            <h2><?php echo esc_html($section_tagline); ?></h2>
                            <div class="adt-badge">
                                <strong><?php echo esc_html($badge_text_top); ?></strong>
                                <h6><?php echo esc_html($badge_text_middle); ?></h6>
                                <span><?php echo esc_html($badge_text_btm); ?></span>
                            </div>
                            <img class="arrow-vector"
                                 src="<?php echo esc_url($arrow_img['url']); ?>" alt="arrow">
                        </div>
                        <div class="find-pet-carousel-area">
                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="sub-content">
                                        <small><?php echo esc_html($cat_section_title); ?></small>
                                        <h6><?php echo esc_html($cat_section_tagline); ?></h6>
                                    </div>
                                </div>
                                <div class="col-lg-10">
                                    <div class="pet-category-carousel owl-carousel owl-theme">
                                        <?php
                                        if (is_array($mini_categories_repeater) && count($mini_categories_repeater) > 0) {
                                            foreach ($mini_categories_repeater as $category) {
                                                $category_id = $category['mini_category'];
                                                $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                                $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';
                                                $ad_count = isset($cat_details['ad_count']) ? intval($cat_details['ad_count']) : 0;

                                                $category_image = '';
                                                if (isset($category['category_image']) && is_array($category['category_image']) && !empty($category['category_image']['url'])) {
                                                    $category_image = esc_url($category['category_image']['url']);
                                                }

                                                $link = '';
                                                if (isset($cat_details['id']) && function_exists('adforest_cat_link_page')) {
                                                    $link = esc_url(adforest_cat_link_page($cat_details['id'], $cat_link_page, 'cat_id'));
                                                }
                                                ?>
                                                <div class="item">
                                                    <a href="<?php echo esc_url($link); ?>"
                                                       class="pet-category-box">
                                                        <img src="<?php echo esc_url($category_image); ?>"
                                                             alt="<?php echo esc_attr($category_name); ?>">
                                                        <span><?php echo esc_html($category_name); ?><small><?php echo esc_html__("(" . $ad_count . ")", "adforest-elementor"); ?></small></span>
                                                    </a>
                                                </div>
                                            <?php }
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img class="cloud-pattern" src="<?php echo esc_url($bg_img_2['url']); ?>"
                 alt="cloud-pattern">
            <img class="pet-sub-img-1" src="<?php echo esc_url($bg_img_3['url']); ?>"
                 alt="pet-sub-img">
            <img class="pet-sub-img-2" src="<?php echo esc_url($bg_img_4['url']); ?>"
                 alt="pet-sub-hero">
        </section>
        <?php
    }
}
// ============= ShortCode for Main Hero 2 ============= //

// ============= ShortCode for Category Based Ads ============= //
if (!function_exists("adforest_category_based_ads")) {
    function adforest_category_based_ads($params): void
    {
        global $adforest_theme;
        $ad_cats_select = $params['ad_cats_select'] ?? "";
        $posts_per_page = $params['posts_per_page'] ?? "";
        $section_title = $params['section_title'] ?? "";
        $button_title = $params['button_title'] ?? "";
        $button_link = $params['button_link'] ?? "";
        $carousel_ad_type = $params['carousel_ad_type'] ?? "";
        $ad_grid_style = $params['ad_grid_style'] ?? "";
        $ad_title_limit = $params['ad_title_limit'] ?? "";
        $carousel_grid_columns = $params['carousel_grid_columns'] ?? "";
        $mobile_grid_columns = 1;
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        if ($sb_2column) {
            $mobile_grid_columns = 2;
        }


        $carousel_class = 'adt-ads-carousel-widgets';
        if ($ad_grid_style != '1') {
            $carousel_class = 'adt-property-ads-carousel-widgets';
        }

        $loop_slider = isset($params['loop_slider']) && $params['loop_slider'] === 'yes' ? 'true' : 'false';
        $autoplay_slider = isset($params['autoplay_slider']) && $params['autoplay_slider'] === 'yes' ? 'true' : 'false';
        $pause_on_hover_slider = isset($params['pause_on_hover_slider']) && $params['pause_on_hover_slider'] === 'yes' ? 'true' : 'false';
        $slider_speed = isset($params['slider_speed']) ? (int) $params['slider_speed'] : 3000;

        $slider_data_atts = "data-loop='{$loop_slider}' data-autoplay='{$autoplay_slider}' data-autoplay-timeout='{$slider_speed}' data-pause-on-hover='{$pause_on_hover_slider}'";

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        if (!empty($ad_cats_select)) {
            $args = [
                'post_type' => 'ad_post',
                'posts_per_page' => $posts_per_page,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_status' => 'publish',
            ];

            $args['tax_query'] = [
                [
                    'taxonomy' => 'ad_cats',
                    'field' => 'term_id',
                    'terms' => $ad_cats_select,
                    'operator' => 'IN',
                ],
            ];

            if ($carousel_ad_type === 'recent') {
                $args['meta_key'] = '_adforest_is_feature';
                $args['meta_value'] = '0';
            } elseif ($carousel_ad_type === 'featured') {
                $args['meta_key'] = '_adforest_is_feature';
                $args['meta_value'] = '1';
            }

            $ads_query = new WP_Query($args);

            if ($ads_query->have_posts()) {
                ?>
                <section>
                    <div class="container <?php echo esc_attr($adt_container_class); ?>">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="adt-ads-top-box">
                                    <h2><?php echo esc_html($section_title); ?></h2>
                                    <a href="<?php echo esc_url($button_link); ?>">
                                        <button class="adt-button-1">
                                            <?php echo esc_html($button_title); ?>
                                        </button>
                                    </a>
                                </div>

                                <div class="<?php echo esc_attr($carousel_class); ?> owl-carousel owl-theme"
                                     data-columns="<?php echo esc_attr($carousel_grid_columns); ?>"
                                     data-mobile-columns="<?php echo esc_attr($mobile_grid_columns); ?>" <?php echo $slider_data_atts ?>>
                                    <?php
                                    while ($ads_query->have_posts()) {
                                        $ads_query->the_post();
                                        $ad_details = get_ad_post_details(get_the_ID());

                                        $img = $ad_details['img'];
                                        $truncated_location = $ad_details['truncated_location'];
                                        $price_html = $ad_details['price_html'];
                                        $heart_class = $ad_details['heart_class'];
                                        $is_featured = $ad_details['is_featured'];
                                        $ad_categories_post = $ad_details['categories'];
                                        $ad_poster_img = $ad_details['ad_poster_img'];
                                        $ad_poster_name = $ad_details['ad_poster_name'];
                                        $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);
                                        $ad_permalink = $ad_details['ad_link'];
                                        $ad_title = truncate_string($ad_details['ad_title'], $ad_title_limit);
                                        $all_ad_images = $ad_details['all_ad_images'];
                                        $featured_tag = $is_featured ? '<img style="transform: rotate(180deg);" src="' . get_template_directory_uri() . '/images/featured.png' . '" alt="featured-tag" class="featured-tag">' : '';
                                        ?>
                                        <div class="item">
                                            <?php
                                            if ($ad_grid_style == 1) {
                                                echo adforest_ad_grid_1($ad_permalink, $img, $is_featured, $ad_categories_post, $ad_details, $ad_title, $truncated_location, $price_html, $heart_class);
                                            } elseif ($ad_grid_style == 2) {
                                                echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class);
                                            } elseif ($ad_grid_style == 3) {
                                                echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                            }
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    wp_reset_postdata();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php
            }
        }
    }
}
// ============= ShortCode for Category Based Ads ============= //

// ============= ShortCode for Recent Ads ============= //
if (!function_exists("recent_ads_shortcode")) {
    function recent_ads_shortcode($params)
    {
        global $adforest_theme;
        $main_sec_ad_type = $params['main_sec_ad_type'] ?? "";
        $main_section_ppp = $params['main_section_ppp'] ?? "";
        $left_sec_ad_type = $params['left_sec_ad_type'] ?? "";
        $left_section_ppp = $params['left_section_ppp'] ?? "";
        $left_ad_img = $params['left_ad'] ?? "";
        $show_left_sec_ad_type = $params['show_left_sec_ad_type'] ?? "";
        $show_left_ad = $params['show_left_ad'] ?? "";
        $left_sec_ads_title = $params['left_sec_ads_title'] ?? "";
        $left_ad_link = $params['left_ad_link']['url'] ?? "";
        $show_right_ad_1 = $params['show_right_ad_1'] ?? "";
        $show_right_ad_2 = $params['show_right_ad_2'] ?? "";
        $ad_1 = $params['advert_1'] ?? "";
        $section_title = $params['section_title'] ?? "";
        $btn_title = $params['btn_title'] ?? "";
        $btn_link = $params['btn_link'] ?? "";
        $ad_title_limit_side = $params['ad_title_limit_side'] ?? "";
        $ad_title_limit_main = $params['ad_title_limit_main'] ?? "";

        $ad_2 = $params['advert_2'] ?? "";
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = ($sb_2column == false) ? "one-column-mobile-layout" : "";
        ?>
        <section class="adt-estate-ads-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-6 col-xl-3">
                        <?php echo adforest_display_1_ads_sidebar_section($left_sec_ad_type, $left_section_ppp, $left_ad_img, $show_left_ad, $left_sec_ads_title, $show_left_sec_ad_type, $ad_title_limit_side); ?>
                    </div>
                    <div class="col-xl-6 middle-content <?php echo esc_attr($mobile_two_columns) ?>">
                        <?php
                        $args = [
                            'post_type' => 'ad_post',
                            'posts_per_page' => $main_section_ppp,
                            'post_status' => 'publish',
                        ];

                        $ad = "";
                        if ($main_sec_ad_type === 'recent') {
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                        } elseif ($main_sec_ad_type === 'featured') {
                            $args['meta_key'] = '_adforest_is_feature';
                            $args['meta_value'] = '1';
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                            $ad = "1";
                        }
                        $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', $ad);

                        $ads_query = new WP_Query($args);
                        if ($ads_query->have_posts()) {
                        ?>
                        <div class="adt-ads-top-box">
                            <h2><?php echo esc_html($section_title); ?></h2>
                            <a href="<?php echo esc_url($btn_link); ?>">
                                <button class="adt-button-1"><?php echo esc_html($btn_title); ?></button>
                            </a>
                        </div>
                        <?php
                        while ($ads_query->have_posts()) : $ads_query->the_post();
                            $ad_details = get_ad_post_details(get_the_ID());
                            $category_names = $ad_details['category_names'];
                            $first_img = $ad_details['img'];
                            $truncated_location = $ad_details['truncated_location'];
                            $title = $ad_details['ad_title'];
                            $truncated_title = truncate_string($title, $ad_title_limit_main);
                            $price_html = $ad_details['price_html'];
                            $ad_permalink = $ad_details['ad_link'];
                            $heart_class = $ad_details['heart_class'];
                            $is_featured = $ad_details['is_featured'];
                            $ad_categories_post = $ad_details['categories'];
                            $category_links = [];

                            foreach ($ad_categories_post as $category) {
                                $category_url = get_term_link($category);
                                if (!is_wp_error($category_url)) {
                                    $category_links[] = '<a class="ctg-tag" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
                                }
                            }

                            $category_links_string = implode(' > ', $category_links);
                            $selected_categories = $ad_details['categories'];
                            $category_link = '';
                            $category_name = '';
                            if (!empty($selected_categories) && is_array($selected_categories) && isset($selected_categories[0]) && is_object($selected_categories[0]) && isset($selected_categories[0]->term_id)) {
                                $category_link = get_term_link($selected_categories[0]->term_id);
                                if (is_wp_error($category_link)) {
                                    $category_link = '';
                                }
                                $category_name = isset($selected_categories[0]->name) ? $selected_categories[0]->name : '';
                            }
                            ?>
                            <div class="adt-category-ad-list">
                                <div class="category-img-box">
                                    <a href="<?php echo esc_url($ad_permalink); ?>">
                                        <img class="img-fluid"
                                             src="<?php echo esc_url($first_img); ?>"
                                             alt="<?php echo esc_html__(get_the_title(), "adforest-elementor"); ?>">

                                        <?php if ($is_featured): ?>
                                            <span class="featured-label"><?php echo __('Featured', 'adforest-elementor'); ?></span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="category-content-box">
                                    <a href="javascript:void(0);"
                                       data-adid="<?php echo get_the_ID(); ?>"
                                       class="favourite ad_to_fav"
                                       data-toggle="tooltip"
                                       data-placement="top"
                                       title="Click to add to favorites">
                                        <i class="<?php echo $heart_class; ?>"></i>
                                    </a>
                                    <div class="adt-ad-cats">
                                        <a class="ctg-tag" href="<?php echo esc_url($category_link) ?>">
                                            <?php echo esc_html($category_name); ?>
                                        </a>
                                    </div>
                                    <a href="<?php echo esc_url(the_permalink()); ?>">
                                        <h5><?php echo esc_html($truncated_title); ?></h5></a>
                                    <p>
                                        <i class="fas fa-map-marker-alt"></i><?php echo esc_html($truncated_location); ?>
                                    </p>
                                    <div class="price-box">
                                        <?php echo $price_html; ?>
                                        <a href="<?php echo esc_url(the_permalink()); ?>"
                                           class="detail-btn"><?php echo __("Detail", 'adforest-elementor'); ?></a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php wp_reset_postdata(); ?>
                    <?php } ?>
                    <?php if (!empty($ad_1) || !empty($ad_2)) { ?>
                        <div class="col-lg-6 col-xl-3 right-sidebar">
                            <?php
                            if ($show_right_ad_1 == 'yes' && !empty($ad_1)) {
                                ?>
                                <div class="adt-vertical-ad-box">
                                    <?php echo wp_kses_post($ad_1); ?>
                                </div>
                            <?php }
                            if ($show_right_ad_2 == 'yes' && !empty($ad_2)) {
                                ?>
                                <div class="adt-vertical-ad-box">
                                    <?php echo wp_kses_post($ad_2); ?>
                                </div>
                            <?php }
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Recent Ads ============= //

// ============= ShortCode for Horizontal Ad ============= //
if (!function_exists("adforest_horizontal_ad")) {
    function adforest_horizontal_ad($params)
    {
        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $ad_cats_ad = $params['ad_cats_ad'] ?? "";
        $ad_type    = $params['ad_type'] ?? "image";
        $allowed    = array( 'image', 'custom_html', 'adsense' );
        $ad_type    = in_array( $ad_type, $allowed, true ) ? $ad_type : 'image';

        if (isset($ad_cats_ad) && !empty($ad_cats_ad)) {
            ?>
            <section class="adt-horizontal-advert-section">
                <div class="container <?php echo esc_attr( $adt_container_class ); ?>">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="adt-horizontal-ad-box">
                                <?php
                                if ( function_exists( 'adforest_render_ad' ) ) {
                                    adforest_render_ad( $ad_type, $ad_cats_ad );
                                } else {
                                    echo wp_kses_post( $ad_cats_ad );
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
        }
    }
}
// ============= ShortCode for Horizontal Ad ============= //

// ============= ShortCode for Main Hero 3 ============= //
if (!function_exists('main_hero_3_shortcode')) {
    function main_hero_3_shortcode($params)
    {
        global $adforest_theme;
        $section_title = $params['section_title'] ?? "";
        $section_tagline = $params['section_tagline'] ?? "";
        $bg_img = $params['bg_img'] ?? "";
        $bg_color = $params['bg_color'] ?? "";
        $search_section_title = $params['search_section_title'] ?? "";
        $classified_search_fields = $params['classified_search_fields'] ?? "";
        $classified_search_ad_types = $params['classified_search_ad_types'] ?? "";
        $secondary_text_1 = $params['secondary_text_1'] ?? "";
        $secondary_text_2 = $params['secondary_text_2'] ?? "";
        $secondary_text_3 = $params['secondary_text_3'] ?? "";
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);

        if (isset($bg_img['url'])) {
            $bgImageURL = $bg_img['url'];
        } else {
            $bgImageURL = adforest_returnImgSrc($bg_img);
        }
        $style = ($bgImageURL != "") ? ' style="background: ' . $bg_color . ' url(' . $bgImageURL . ') no-repeat bottom/auto; margin-bottom: 60px"' : "";
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-classified-marketplace-hero" <?php echo $style; ?>>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-marketplace-hero-content">
                            <h4><?php echo esc_html($section_title); ?></h4>
                            <h2><?php echo esc_html($section_tagline); ?></h2>
                            <div class="adt-badge">
                                <strong><?php echo esc_html($secondary_text_1); ?></strong>
                                <h6><?php echo esc_html($secondary_text_2); ?></h6>
                                <span><?php echo esc_html($secondary_text_3); ?></span>
                            </div>
                        </div>
                        <div class="adt-classified-search-box">
                            <form method="GET"
                                  action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                <div class="title-box">
                                    <h4><?php echo esc_html($search_section_title); ?></h4>
                                    <div class="switch-btns-box">
                                        <ul id="search_hero_3_type_list" class="select-user-type">
                                            <?php foreach ($classified_search_ad_types as $ad_type):
                                                $term = get_term($ad_type['classified_ad_type']);
                                                if ($term) : ?>
                                                    <li>
                                                        <input type="radio" name="ad_type"
                                                               value="<?php echo esc_attr($term->name); ?>"
                                                               id="check-<?php echo esc_attr($term->slug); ?>-user"/>
                                                        <label for="check-<?php echo esc_attr($term->slug); ?>-user">
                                                            <?php echo esc_html($term->name); ?>
                                                        </label>
                                                    </li>
                                                <?php endif;
                                            endforeach; ?>
                                        </ul>
                                    </div>
                                </div>

                                <div class="row">
                                    <?php foreach ($classified_search_fields as $field) { ?>
                                        <div class="col-md-6">
                                            <div class="form-field">
                                                <?php if ($field['classified_field_type'] === 'title') { ?>
                                                    <label for="search"
                                                           class="form-label"><?php echo esc_html__('Title', 'adforest-elementor'); ?></label>
                                                    <input type="text" class="form-control" name="ad_title"
                                                           id="ad_title"
                                                           placeholder="<?php echo esc_attr__('Enter Keywords', 'adforest-elementor'); ?>">
                                                <?php } elseif ($field['classified_field_type'] === 'location') { ?>
                                                    <label for="location"
                                                           class="form-label"><?php echo esc_html__('Location', 'adforest-elementor'); ?></label>
                                                    <input type="text" class="form-control" name="location"
                                                           id="location"
                                                           placeholder="<?php echo esc_attr__('Enter Location', 'adforest-elementor'); ?>">
                                                    <button type="button" class="search-location"><i
                                                                class="fas fa-paper-plane"></i></button>
                                                <?php } elseif ($field['classified_field_type'] === 'category') {
                                                    $categories = adforest_get_ad_taxonomy_callback('ad_cats');
                                                    ?>
                                                    <div class="label-box">
                                                        <label for="category"
                                                               class="form-label"><?php echo esc_html__('Category', 'adforest-elementor'); ?></label>
                                                    </div>
                                                    <select class="default-select post-type-change" name="cat_id">
                                                        <option value=""><?php echo __("Select Category", 'adforest-elementor'); ?></option>
                                                        <?php foreach ($categories as $category) {
                                                            $cat_details = get_taxonomy_details($category);
                                                            $category_name = $cat_details['name'] ?? '';
                                                            $category_id = $category->term_id;
                                                            ?>
                                                            <option value="<?php echo esc_attr($category_id); ?>"><?php echo esc_html($category_name); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="search-btns-wrapper">
                                    <div>
                                        <button type="reset" class="reset-search"><i
                                                    class="fas fa-undo"></i><?php echo __("Reset Search", 'adforest-elementor'); ?>
                                        </button>
                                    </div>
                                    <div>
                                        <a href="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>"
                                           class="advanced-search"><?php echo __("Advanced Search", 'adforest-elementor'); ?></a>
                                        <button type="submit" class="search-btn adt-button-dark"><i
                                                    class="fas fa-search"></i><?php echo __("Search Now", 'adforest-elementor'); ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Main Hero 3 ============= //

// ============= ShortCode for Mini Category Slider ============= //
if (!function_exists("mini_category_slider")) {
    function mini_category_slider($params)
    {
        global $adforest_theme;
        $mini_categories = $params['mini_categories_repeater'] ?? "";

        $loop_slider = isset($params['loop_slider']) && $params['loop_slider'] === 'yes' ? 'true' : 'false';
        $autoplay_slider = isset($params['autoplay_slider']) && $params['autoplay_slider'] === 'yes' ? 'true' : 'false';
        $pause_on_hover_slider = isset($params['pause_on_hover_slider']) && $params['pause_on_hover_slider'] === 'yes' ? 'true' : 'false';
        $slider_speed = isset($params['slider_speed']) ? (int) $params['slider_speed'] : 3000;

        $slider_data_atts = "data-loop='{$loop_slider}' data-autoplay='{$autoplay_slider}' data-autoplay-timeout='{$slider_speed}' data-pause-on-hover='{$pause_on_hover_slider}'";

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <?php
        if (is_array($mini_categories) && count($mini_categories) > 0) { ?>
            <section class="adt-category-mini-section">
                <div class="container <?php echo $adt_container_class; ?>">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="category-mini-carousel owl-carousel owl-theme" <?php echo $slider_data_atts; ?>>
                                <?php
                                $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';

                                foreach ($mini_categories as $category) :
                                    $category_id = $category['mini_category'];
                                    $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');

                                    $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                    $link = '';
                                    if (!empty($cat_details['id']) && function_exists('adforest_cat_link_page')) {
                                        $link = esc_url(adforest_cat_link_page($cat_details['id'], $cat_link_page, 'cat_id'));
                                    }

                                    ?>
                                    <div class="item">
                                        <div class="category-mini-box">
                                            <a href="<?php echo esc_url($link); ?>"
                                               class="d-flex align-items-center">
                                                <img class="img-fluid"
                                                     src="<?php echo $category['category_image']['url']; ?>"
                                                     alt="<?php echo esc_attr($category_name); ?>"
                                                     style="margin-right: 8px;">
                                                <span><?php echo esc_html($category_name); ?></span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php } ?>
        <?php
    }
}
// ============= ShortCode for Mini Category Slider ============= //

// ============= ShortCode for Locations ============= //
if (!function_exists('ad_locations_shortcode')) {
    function ad_locations_shortcode($params)
    {
        global $adforest_theme;
        $section_bg_color = $params['section_bg_color'] ?? "";
        $section_title = $params['section_title'] ?? "";
        $section_left_btn_text = $params['section_left_btn_text'] ?? "";
        $ad_img = $params['advert_img'] ?? "";
        $show_ad = $params['show_ad'] ?? "";
        $popular_locations = $params['locations_repeater'] ?? "";
        $section_btn_link = $params['section_btn_link'] ?? "";
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';

        $style = '';
        if ($section_bg_color != '') {
            $style = 'style= "background-color: ' . $section_bg_color . ';"';
        }
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_grids = $sb_2column ? "col-6" : '';

        if (!empty($popular_locations) && !is_wp_error($popular_locations)) {
            $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'country_id', '');
            ?>
            <section class="adt-popular-location-section" <?php echo $style; ?>>
                <div class="container <?php echo $adt_container_class; ?>">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="top-heading-box">
                                <h4><?php echo esc_html($section_title); ?></h4>
                                <a href="<?php echo esc_url($section_btn_link); ?>"><?php echo esc_html($section_left_btn_text); ?></a>
                            </div>
                        </div>
                        <div class="col-lg-9">
                            <div class="row">
                                <?php foreach ($popular_locations as $location) :
                                    $location_id = $location['location'];
                                    $location_details = adforest_get_taxonomy_details_by_id($location_id, 'ad_country');
                                    if (empty($location_details)) {
                                        continue;
                                    }
                                    $location_name = isset($location_details['name']) ? sanitize_text_field($location_details['name']) : '';
                                    $ad_count = isset($location_details['ad_count']) ? intval($location_details['ad_count']) : 0;

                                    $location_image = '';
                                    if (isset($location['location_image']) && is_array($location['location_image']) && !empty($location['location_image']['url'])) {
                                        $location_image = esc_url($location['location_image']['url']);
                                    }

                                    $link = '';
                                    if (!empty($location_details['id']) && function_exists('adforest_cat_link_page')) {
                                        $link = esc_url(adforest_cat_link_page($location_details['id'], $cat_link_page, 'country_id'));
                                    }

                                    ?>
                                    <div class="<?php echo $mobile_two_grids ?> col-sm-6 col-lg-4">
                                        <a href="<?php echo esc_url($link); ?>"
                                           class="adt-location-box">
                                            <div class="location-img-box">
                                                <img src="<?php echo esc_url($location_image); ?>"
                                                     alt="location">
                                            </div>
                                            <div class="location-meta-box">
                                                <span class="location-name"><?php echo esc_html($location_name); ?></span>
                                                <span class="ads-count"><?php echo esc_html($ad_count . " ads"); ?></span>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <?php if ($show_ad == 'yes' && !empty($ad_img)) { ?>
                                <div class="adt-vertical-ad-box">
                                    <?php echo wp_kses_post($ad_img); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php }
    }
}
// ============= ShortCode for Locations ============= //

// ============= ShortCode for Main Hero 4 ============= //
if (!function_exists('main_hero_4_shortcode')) {
    function main_hero_4_shortcode($params)
    {
        global $adforest_theme;
        $show_main_ad = $params['show_main_ad'] ?? "";
        $show_right_sec_ad_type = $params['show_right_sec_ad_type'] ?? "";
        $ads_list = $params['ads_lists'] ?? "";
        $show_main_ad_2 = $params['show_main_ad_2'] ?? "";
        $main_ad_2 = $params['main_ads_2'] ?? "";
        $featured_ads_title = $params['featured_ads_title'] ?? "";
        $section_ad_type = $params['section_ad_type'] ?? "";
        $slider_ad_type = $params['slider_ad_type'] ?? "";
        $featured_ads_ppp = $params['featured_ads_ppp'] ?? "";
        $featured_ads_title_limit = $params['featured_ads_title_limit'] ?? 0;
        $regular_ads_title_limit = $params['regular_ads_title_limit'] ?? 0;
        $right_section_title_limit = $params['right_section_title_limit'] ?? 0;
        $regular_ads_ppp = $params['regular_ads_ppp'] ?? "";
        $featured_ads_btn_text = $params['featured_ads_btn_text'] ?? "";
        $featured_ads_btn_link = $params['featured_ads_btn_link'] ?? "";
        $show_featured_ads_carousel = $params['show_featured_ads_carousel'] ?? "";
        $regular_ads_title = $params['regular_ads_title'] ?? "";
        $regular_ads_btn_text = $params['regular_ads_btn_text'] ?? "";
        $regular_ads_btn_link = $params['regular_ads_btn_link'] ?? "";
        $show_regular_ads_carousel = $params['show_regular_ads_carousel'] ?? "";
        $show_search = $params['show_search'] ?? "";
        $search_title = $params['search_title'] ?? "";
        $search_btn_text = $params['search_btn_text'] ?? "";
        $fields = $params['fields'] ?? "";
        $show_categories = $params['show_categories'] ?? "";
        $cat_title = $params['cat_title'] ?? "";
        $show_left_ad = $params['show_left_ad'] ?? "";
        $left_ad_img = $params['left_ad'] ?? "";
        $show_right_ad_1 = $params['show_right_ad_1'] ?? "";
        $right_ad_img_1 = $params['right_ad_1'] ?? "";
        $right_sec_ad_type = $params['right_sec_ad_type'] ?? "";
        $right_section_ppp = $params['right_section_ppp'] ?? "";
        $show_right_ad_2 = $params['show_right_ad_2'] ?? "";
        $right_ad_img_2 = $params['right_ad_2'] ?? "";
        $slider_grid_columns = $params['slider_grid_columns'] ?? "";
        $slider_grid_type = $params['slider_grid_type'] ?? "";
        $regular_grid_type = $params['regular_grid_type'] ?? "";
        $regular_grid_columns = $params['regular_grid_columns'] ?? "";
        $sidebar_categories = $params['sidebar_locations_repeater'] ?? "";
        $hero_loop = isset($params['loop_slider']) && $params['loop_slider'] === 'yes' ? 'true' : 'false';
        $hero_autoplay = isset($params['autoplay_slider']) && $params['autoplay_slider'] === 'yes' ? 'true' : 'false';
        $hero_pause_on_hover = isset($params['pause_on_hover_slider']) && $params['pause_on_hover_slider'] === 'yes' ? 'true' : 'false';
        $hero_speed = isset($params['slider_speed']) ? (int) $params['slider_speed'] : 3000;

        $featured_loop = isset($params['featured_loop_slider']) && $params['featured_loop_slider'] === 'yes' ? 'true' : 'false';
        $featured_autoplay = isset($params['featured_autoplay_slider']) && $params['featured_autoplay_slider'] === 'yes' ? 'true' : 'false';
        $featured_pause_on_hover = isset($params['featured_pause_on_hover_slider']) && $params['featured_pause_on_hover_slider'] === 'yes' ? 'true' : 'false';
        $featured_speed = isset($params['featured_slider_speed']) ? (int) $params['featured_slider_speed'] : 3000;

        $hero_slider_data_atts = "data-loop='{$hero_loop}' data-autoplay='{$hero_autoplay}' data-autoplay-timeout='{$hero_speed}' data-pause-on-hover='{$hero_pause_on_hover}'";
        $featured_slider_data_atts = "data-loop='{$featured_loop}' data-autoplay='{$featured_autoplay}' data-autoplay-timeout='{$featured_speed}' data-pause-on-hover='{$featured_pause_on_hover}'";
        $categories = adforest_get_ad_taxonomy_callback('ad_cats');
        $locations = adforest_get_ad_taxonomy_callback('ad_country');
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = $sb_2column ? 2 : 1;
        ?>
        <section class="adt-smart-ads-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-smart-ads-content-wrapper">
                            <div class="left-sidebar">
                                <?php if ($show_search == 'yes') { ?>
                                    <div class="adt-search-list-box">
                                        <h4><?php echo esc_html($search_title); ?></h4>
                                        <form method="GET"
                                              action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                            <?php foreach ($fields as $field) : ?>
                                                <?php if ($field['field_type'] === 'title') : ?>
                                                    <div class="form-field">
                                                        <label for="search"
                                                               class="form-label"><?php echo esc_attr($field['field_name']); ?></label>
                                                        <input type="text" class="form-control" id="search"
                                                               name="ad_title"
                                                               placeholder="<?php echo esc_attr($field['placeholder_text']); ?>">
                                                    </div>
                                                <?php elseif ($field['field_type'] === 'address') : ?>
                                                    <div class="form-field">
                                                        <label for="sb_user_address" class="form-label">
                                                            <?php echo esc_html($field['field_name']); ?>
                                                        </label>
                                                        <input type="text" class="form-control" id="sb_user_address" name="location"
                                                               placeholder="<?php echo esc_attr($field['placeholder_text']); ?>">
                                                        <!-- <input type="hidden" id="sb_user_address_lat" name="latitude" value="">
                                                        <input type="hidden" id="sb_user_address_long" name="longitude" value=""> -->
                                                        <?php
                                                        if (function_exists('adforest_mapType')) {
                                                            $mapType = adforest_mapType();
                                                            if ($mapType !== 'leafletjs_map') {
                                                                wp_enqueue_script('google-map-callback');
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                <?php elseif ($field['field_type'] === 'category') :
                                                    $field_mode = $field['field_mode'] ?? "default"; ?>
                                                    <div class="form-field">
                                                        <label for="category" class="form-label">
                                                            <?php echo esc_html($field['field_name']); ?>
                                                        </label>

                                                        <?php if ($field_mode === 'default') : ?>
                                                            <select class="default-select post-type-change" name="cat_id">
                                                                <option value=""><?php echo esc_html($field['placeholder_text']); ?></option>
                                                                <?php
                                                                if (!empty($categories)) {
                                                                    foreach ($categories as $category) {
                                                                        $cat_details = get_taxonomy_details($category);
                                                                        $category_name = $cat_details['name'] ?? '';
                                                                        $category_id = $category->term_id;
                                                                        ?>
                                                                        <option value="<?php echo esc_attr($category_id); ?>">
                                                                            <?php echo esc_html($category_name); ?>
                                                                        </option>
                                                                    <?php }
                                                                } ?>
                                                            </select>
                                                        <?php else : // ajax mode ?>
                                                            <select class="ajax-taxonomy-select" 
                                                                    name="cat_id"
                                                                    data-taxonomy="ad_cats"
                                                                    data-placeholder="<?php echo esc_attr($field['placeholder_text']); ?>">
                                                            </select>
                                                        <?php endif; ?>
                                                    </div>

                                                <?php elseif ($field['field_type'] === 'location') :
                                                    $field_mode = $field['field_mode'] ?? "default"; ?>
                                                    <div class="form-field">
                                                        <label for="location" class="form-label">
                                                            <?php echo esc_html($field['field_name']); ?>
                                                        </label>

                                                        <?php if ($field_mode === 'default') : ?>
                                                            <select class="default-select post-type-change" name="country_id">
                                                                <option value=""><?php echo esc_html($field['placeholder_text']); ?></option>
                                                                <?php
                                                                if (!empty($locations)) {
                                                                    foreach ($locations as $location) {
                                                                        $location_details = get_taxonomy_details($location);
                                                                        $location_name = $location_details['name'] ?? '';
                                                                        $location_id = $location->term_id;
                                                                        ?>
                                                                        <option value="<?php echo esc_attr($location_id); ?>">
                                                                            <?php echo esc_html($location_name); ?>
                                                                        </option>
                                                                    <?php }
                                                                } ?>
                                                            </select>
                                                        <?php else : // ajax mode ?>
                                                            <select class="ajax-taxonomy-select" 
                                                                    name="country_id"
                                                                    data-taxonomy="ad_country"
                                                                    data-placeholder="<?php echo esc_attr($field['placeholder_text']); ?>">
                                                            </select>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <button style="width:100%" type="submit" class="search-btn adt-button-dark">
                                                <i class="fas fa-search"></i><?php echo esc_html($search_btn_text); ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php } ?>
                                <?php if ($show_categories == 'yes') { ?>
                                    <div class="adt-category-round-list-sidebar">
                                        <h4><?php echo esc_html($cat_title); ?></h4>
                                        <ul>
                                            <?php
                                            if (is_array($sidebar_categories) && count($sidebar_categories) > 0) {
                                                foreach ($sidebar_categories as $category) {
                                                    $category_id = $category['location'];
                                                    $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_country');
                                                    if (empty($cat_details)) {
                                                        continue;
                                                    }
                                                    $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                                    $category_link = isset($cat_details['link']) ? esc_url($cat_details['link']) : '';

                                                    $category_image = '';
                                                    if (isset($category['location_image']) && is_array($category['location_image']) && !empty($category['location_image']['url'])) {
                                                        $category_image = esc_url($category['location_image']['url']);
                                                    }

                                                    $ad_count = isset($cat_details['ad_count']) ? intval($cat_details['ad_count']) : 0;

                                                    ?>
                                                    <li id="<?php echo $category_id; ?>">
                                                        <div class="adt-category-box">
                                                            <div class="category-meta">
                                                                <a href="<?php echo esc_url($category_link); ?>"
                                                                   class="img-box">
                                                                    <img class="img-fluid"
                                                                         src="<?php echo esc_url($category_image); ?>"
                                                                         alt="category">
                                                                </a>
                                                                <a href="<?php echo esc_url($category_link); ?>"><?php echo esc_html($category_name); ?></a>
                                                            </div>
                                                            <span class="listing-count"><?php echo esc_html(sprintf(__('%d ads', 'adforest-elementor'), $ad_count)); ?></span>
                                                        </div>
                                                    </li>
                                                <?php }
                                            } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                                <?php if ($show_left_ad == 'yes' && !empty($left_ad_img)) { ?>
                                    <div class="adt-vertical-ad-box">
                                        <?php echo wp_kses_post($left_ad_img); ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="middle-content">
                                <?php if (isset($show_main_ad) && $show_main_ad == 'yes') { ?>
                                    <div class="adt-horizontal-ad-box top-margin-0">
                                        <div class="owl-carousel owl-theme owl-carousel-main-hero-4 ad-carousel" <?php echo $hero_slider_data_atts; ?>>
                                            <?php foreach ($ads_list as $ad) {
                                                $ad_img = $ad['ad_image'];
                                                if (!empty($ad_img)) {
                                                    ?>
                                                    <div class="item">
                                                        <?php echo wp_kses_post($ad_img); ?>
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php
                                $args = [
                                    'post_type' => 'ad_post',
                                    'posts_per_page' => $featured_ads_ppp ?? 5,
                                    'post_status' => 'publish',
                                ];

                                if ($slider_ad_type === 'recent') {
                                    $args['meta_key'] = '_adforest_is_feature';
                                    $args['meta_value'] = '0';
                                    $args['orderby'] = 'date';
                                    $args['order'] = 'DESC';
                                } elseif ($slider_ad_type === 'featured') {
                                    $args['meta_key'] = '_adforest_is_feature';
                                    $args['meta_value'] = '1';
                                    $args['orderby'] = 'date';
                                    $args['order'] = 'DESC';
                                } elseif ($slider_ad_type == 'both') {
                                    $args['orderby'] = 'date';
                                    $args['order'] = 'DESC';
                                }

                                $ads_query = new WP_Query($args);
                                if ($show_featured_ads_carousel == 'yes') {
                                    if ($ads_query->have_posts()) { ?>
                                        <div class="adt-ads-top-box">
                                            <h2><?php echo esc_html($featured_ads_title); ?></h2>
                                            <a href="<?php echo esc_url($featured_ads_btn_link) ?>">
                                                <button class="adt-button-1"><?php echo esc_html($featured_ads_btn_text); ?></button>
                                            </a>
                                        </div>
                                        <div class="adt-ads-sub-carousel owl-carousel owl-theme"
                                             data-columns="<?php echo esc_attr($slider_grid_columns); ?>"
                                             data-mobile-columns="<?php echo esc_attr($mobile_two_columns); ?>"
                                             <?php echo $featured_slider_data_atts; ?>>
                                            <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                                                $current_post_id = get_the_ID(); // Get the ID once
                                                $ad_details = get_ad_post_details($current_post_id);
                                                $img = $ad_details['img'];
                                                $truncated_location = $ad_details['truncated_location'];
                                                $price_html = $ad_details['price_html'];
                                                $heart_class = $ad_details['heart_class'];
                                                $is_featured = $ad_details['is_featured'];
                                                $ad_categories_post = $ad_details['categories'];
                                                $ad_poster_img = $ad_details['ad_poster_img'];
                                                $ad_poster_name = $ad_details['ad_poster_name'];
                                                $ad_type = get_post_meta($current_post_id, '_adforest_ad_type', true);
                                                $ad_permalink = $ad_details['ad_link'];
                                                $ad_title = truncate_string($ad_details['ad_title'], $featured_ads_title_limit);
                                                $all_ad_images = $ad_details['all_ad_images'];
                                                $featured_tag = $is_featured ? '<img src="' . plugin_dir_url(__FILE__) . 'assets/images/featured-tag.png' . '" alt="featured-tag" class="featured-tag">' : '';
                                                ?>
                                                <div class="item">
                                                    <?php
                                                    if ($slider_grid_type == '1') {
                                                        echo adforest_ad_grid_1($ad_permalink, $img, $is_featured, $ad_categories_post, $ad_details, $ad_title, $truncated_location, $price_html, $heart_class);
                                                    } elseif ($slider_grid_type == '2') {
                                                        echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class);
                                                    } elseif ($slider_grid_type == "3") {
                                                        echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                                    }
                                                    ?>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                        <?php wp_reset_postdata(); ?>
                                    <?php }
                                } ?>
                                <?php if ($show_main_ad_2 == "yes" && !empty($main_ad_2)) { ?>
                                    <div class="adt-horizontal-ad-box">
                                        <?php echo wp_kses_post($main_ad_2); ?>
                                    </div>
                                <?php } ?>
                                <?php
                                $args = [
                                    'post_type' => 'ad_post',
                                    'posts_per_page' => $regular_ads_ppp ?? 5,
                                    'post_status' => 'publish',
                                ];

                                if ($section_ad_type === 'recent') {
                                    $args['meta_key'] = '_adforest_is_feature';
                                    $args['meta_value'] = '0';
                                    $args['orderby'] = 'date';
                                    $args['order'] = 'DESC';
                                } elseif ($section_ad_type === 'featured') {
                                    $args['meta_key'] = '_adforest_is_feature';
                                    $args['meta_value'] = '1';
                                    $args['orderby'] = 'date';
                                    $args['order'] = 'DESC';
                                } elseif ($slider_ad_type == 'both') {
                                    $args['orderby'] = 'date';
                                    $args['order'] = 'DESC';
                                }

                                $ads_query = new WP_Query($args);
                                $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == false) ? "one-column-mobile-layout" : "";
                                if ($show_regular_ads_carousel == 'yes') {
                                    if ($ads_query->have_posts()) { ?>
                                        <div class="adt-ads-top-box">
                                            <h2><?php echo esc_html($regular_ads_title); ?></h2>
                                            <a href="<?php echo esc_url($regular_ads_btn_link); ?>">
                                                <button class="adt-button-1"><?php echo esc_html($regular_ads_btn_text); ?></button>
                                            </a>
                                        </div>
                                        <div class="adt-search-ads-grid <?php echo "adt-search-ads-col-" . $regular_grid_columns; ?> <?php echo $sb_2column ?>">
                                            <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                                                $current_post_id = get_the_ID(); // Get the ID once
                                                $ad_details = get_ad_post_details($current_post_id);
                                                $img = $ad_details['img'];
                                                $truncated_location = $ad_details['truncated_location'];
                                                $price_html = $ad_details['price_html'];
                                                $heart_class = $ad_details['heart_class'];
                                                $is_featured = $ad_details['is_featured'];
                                                $ad_categories_post = $ad_details['categories'];
                                                $ad_poster_img = $ad_details['ad_poster_img'];
                                                $ad_poster_name = $ad_details['ad_poster_name'];
                                                $ad_type = get_post_meta($current_post_id, '_adforest_ad_type', true);
                                                $ad_permalink = $ad_details['ad_link'];
                                                $ad_title = truncate_string($ad_details['ad_title'], $regular_ads_title_limit);
                                                $all_ad_images = $ad_details['all_ad_images'];
                                                $featured_tag = $is_featured ? '<img src="' . plugin_dir_url(__FILE__) . 'assets/images/featured-tag.png' . '" alt="featured-tag" class="featured-tag">' : '';

                                                if ($regular_grid_type == '1') {
                                                    echo adforest_ad_grid_1($ad_permalink, $img, $is_featured, $ad_categories_post, $ad_details, $ad_title, $truncated_location, $price_html, $heart_class);
                                                } elseif ($regular_grid_type == '2') {
                                                    ?>
                                                    <div class="adt-property-ad-card-outer">
                                                        <?php echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class); ?>
                                                    </div>
                                                    <?php
                                                } elseif ($regular_grid_type == "3") {
                                                    ?>
                                                    <div class="adt-property-ad-card-outer">
                                                        <?php echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location); ?>
                                                    </div>
                                                    <?php
                                                }
                                            endwhile; ?>
                                        </div>
                                        <?php wp_reset_postdata(); ?>
                                    <?php }
                                } ?>
                            </div>
                            <div class="right-sidebar">
                                <?php if ($show_right_ad_1 == 'yes' && !empty($right_ad_img_1)) { ?>
                                    <div class="adt-vertical-ad-box">
                                        <?php echo wp_kses_post($right_ad_img_1); ?>
                                    </div>
                                <?php }
                                if ($show_right_sec_ad_type == 'yes') {
                                    ?>
                                    <div class="adt-recent-ads-sidebar">
                                        <?php
                                        $args = [
                                            'post_type' => 'ad_post',
                                            'posts_per_page' => $right_section_ppp ?? 5,
                                            'post_status' => 'publish',
                                        ];

                                        if ($right_sec_ad_type === 'recent') {
                                            $args['orderby'] = 'date';
                                            $args['order'] = 'DESC';
                                        } elseif ($right_sec_ad_type === 'featured') {
                                            $args['meta_key'] = '_adforest_is_feature';
                                            $args['meta_value'] = '1';
                                            $args['orderby'] = 'date';
                                            $args['order'] = 'DESC';
                                        }

                                        $ads_query = new WP_Query($args);
                                        if ($ads_query->have_posts()) { ?>
                                            <h4><?php echo ($right_sec_ad_type === 'recent') ? __('Recent Ads', 'adforest-elementor') : __('Featured Ads', 'adforest-elementor'); ?></h4>
                                            <ul>
                                                <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                                                    $current_post_id = get_the_ID(); // Get the ID once
                                                    $ad_details = get_ad_post_details($current_post_id);
                                                    $first_img = $ad_details['img'];
                                                    $price_html = $ad_details['price_html'];
                                                    $truncated_title = truncate_string(get_the_title(), $right_section_title_limit);
                                                    ?>
                                                    <li>
                                                        <div class="adt-recent-ad-box">
                                                            <a href="<?php echo esc_url($first_img); ?>"
                                                               class="recent-img-box">
                                                                <img class="img-fluid"
                                                                     src="<?php echo esc_url($first_img); ?>"
                                                                     alt="<?php echo esc_html__(get_the_title(), 'adforest-elementor'); ?>">
                                                            </a>
                                                            <div class="recent-img-meta">
                                                                <a href="<?php echo esc_url(get_the_permalink()); ?>">
                                                                    <h6><?php echo esc_html($truncated_title); ?></h6>
                                                                </a>
                                                                <?php echo $price_html; ?>
                                                            </div>
                                                        </div>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                            <?php wp_reset_postdata(); ?>
                                            <?php
                                        } ?>
                                    </div>
                                <?php } ?>
                                <?php if ($show_right_ad_2 == 'yes' && !empty($right_ad_img_2)) { ?>
                                    <div class="adt-vertical-ad-box">
                                        <?php echo wp_kses_post($right_ad_img_2); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Main Hero 4 ============= //

// ============= ShortCode for Main Hero 5 ============= //
if (!function_exists("main_hero_5_shortcode")) {
    function main_hero_5_shortcode($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_btn_text = isset($params['section_btn_text']) ? $params['section_btn_text'] : "";
        $section_btn_link = isset($params['section_btn_link']) ? $params['section_btn_link'] : "";
        $section_bg_img = isset($params['section_bg_img']['url']) ? $params['section_bg_img']['url'] : "";
        $section_bg_color = isset($params['section_bg_color']) ? $params['section_bg_color'] : "";

        $style = '';
        if ($section_bg_color) {
            $style = 'style="background-color: ' . $section_bg_color . ' !important;"';
        }

        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>

        <section class="adt-explore-things-hero" <?php echo $style; ?>>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="explore-hero-content">
                            <h4><?php echo esc_html($section_title); ?></h4>
                            <h2><?php echo esc_html($section_tagline); ?></h2>
                            <a href="<?php echo esc_url($section_btn_link); ?>"
                               class="adt-button-dark"><?php echo esc_html($section_btn_text); ?></a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="explore-hero-img-box">
                            <img src="<?php echo esc_url($section_bg_img); ?>"
                                 alt="explore-things">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php
    }
}
// ============= ShortCode for Main Hero 5 ============= //

// ============= ShortCode for mini Ad and Location ============= //
if (!function_exists("adforest_mini_ad_and_location")) {
    function adforest_mini_ad_and_location($params)
    {
        global $adforest_theme;
        $ad_cats_advert = isset($params['ad_cat_advert']) ? $params['ad_cat_advert'] : "";
        $main_sec_ad_type_1 = isset($params['main_sec_ad_type_1']) ? $params['main_sec_ad_type_1'] : "";
        $main_sec_ad_type_1_btn_text = isset($params['main_sec_ad_type_1_btn_text']) ? $params['main_sec_ad_type_1_btn_text'] : "";
        $main_section_ppp_1 = isset($params['main_section_ppp_1']) ? $params['main_section_ppp_1'] : "";
        $main_sec_ad_type_2 = isset($params['main_sec_ad_type_2']) ? $params['main_sec_ad_type_2'] : "";
        $main_sec_ad_type_2_btn_text = isset($params['main_sec_ad_type_2_btn_text']) ? $params['main_sec_ad_type_2_btn_text'] : "";
        $main_section_ppp_2 = isset($params['main_section_ppp_2']) ? $params['main_section_ppp_2'] : "";
        $location_section_title = isset($params['location_section_title']) ? $params['location_section_title'] : "";
        $location_section_btn_text = isset($params['location_section_btn_text']) ? $params['location_section_btn_text'] : "";
        $right_sec_ad_type = isset($params['right_sec_ad_type']) ? $params['right_sec_ad_type'] : "";
        $right_section_ppp = isset($params['right_section_ppp']) ? $params['right_section_ppp'] : "";
        $right_ad_img = isset($params['right_advert_img']) ? $params['right_advert_img'] : "";
        $right_ad_img_2 = isset($params['right_advert_img_2']) ? $params['right_advert_img_2'] : "";
        $main_sec_ad_type_1_title = isset($params['main_sec_ad_type_1_title']) ? $params['main_sec_ad_type_1_title'] : "";
        $main_sec_ad_type_2_title = isset($params['main_sec_ad_type_2_title']) ? $params['main_sec_ad_type_2_title'] : "";
        $show_main_sec_ad_type_1 = isset($params['show_main_sec_ad_type_1']) ? $params['show_main_sec_ad_type_1'] : "";
        $main_sec_ad_type_1_btn_link = isset($params['main_sec_ad_type_1_btn_link']) ? $params['main_sec_ad_type_1_btn_link'] : "";
        $popular_locations = isset($params['locations_repeater']) ? $params['locations_repeater'] : "";
        $show_right_sec_ad_type = isset($params['show_right_sec_ad_type']) ? $params['show_right_sec_ad_type'] : "";
        $right_sec_ad_type_title = isset($params['right_sec_ad_type_title']) ? $params['right_sec_ad_type_title'] : "";
        $show_right_ad_img = isset($params['show_right_ad_img']) ? $params['show_right_ad_img'] : "";
        $show_right_ad_img_2 = isset($params['show_right_ad_img_2']) ? $params['show_right_ad_img_2'] : "";
        $show_ad_cats_advert = isset($params['show_ad_cats_advert']) ? $params['show_ad_cats_advert'] : "";
        $main_sec_ad_type_2_btn_link = isset($params['main_sec_ad_type_2_btn_link']) ? $params['main_sec_ad_type_2_btn_link'] : "";
        $location_section_btn_link = isset($params['location_section_btn_link']) ? $params['location_section_btn_link'] : "";
        $hide_right_sidebar = isset($params['hide_right_sidebar']) ? $params['hide_right_sidebar'] : '';
        $main_column_class = $hide_right_sidebar === 'yes' ? 'col-lg-12' : 'col-lg-9';
        $sidebar_column_class = 'col-lg-3';
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $loop_slider = isset($params['loop_slider']) && $params['loop_slider'] === 'yes' ? 'true' : 'false';
        $autoplay_slider = isset($params['autoplay_slider']) && $params['autoplay_slider'] === 'yes' ? 'true' : 'false';
        $pause_on_hover_slider = isset($params['pause_on_hover_slider']) && $params['pause_on_hover_slider'] === 'yes' ? 'true' : 'false';
        $slider_speed = isset($params['slider_speed']) ? (int) $params['slider_speed'] : 3000;

        $slider_data_atts = "data-loop='{$loop_slider}' data-autoplay='{$autoplay_slider}' data-autoplay-timeout='{$slider_speed}' data-pause-on-hover='{$pause_on_hover_slider}'";
        ?>

        <section class="adt-mini-ads-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="<?php echo esc_attr($main_column_class); ?>">
                        <?php
                        $args = [
                            'post_type' => 'ad_post',
                            'posts_per_page' => $main_section_ppp_1,
                            'post_status' => 'publish',
                        ];
                        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
                        $link = '';

                        if ($main_sec_ad_type_1 === 'recent') {
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '');
                        } elseif ($main_sec_ad_type_1 === 'featured') {
                            $args['meta_key'] = '_adforest_is_feature';
                            $args['meta_value'] = '1';
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '1');
                        }
                        $ads_query = new WP_Query($args);
                        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
                        $mobile_two_columns = $sb_2column ? 2 : 1;
                        if ($ads_query->have_posts() && isset($show_main_sec_ad_type_1) && $show_main_sec_ad_type_1 == 'yes') {
                            ?>
                            <div class="adt-ads-top-box">
                                <h2><?php echo esc_html($main_sec_ad_type_1_title); ?></h2>
                                <a href="<?php echo esc_url($main_sec_ad_type_1_btn_link); ?>">
                                    <button class="adt-button-1"><?php echo esc_html($main_sec_ad_type_1_btn_text); ?></button>
                                </a>
                            </div>
                            <div class="adt-mini-ads-carousel owl-carousel owl-theme"
                                 data-mobile-columns="<?php echo esc_attr($mobile_two_columns) ?>" <?php echo $slider_data_atts; ?>>
                                <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                                    $truncate_title = 15;
                                    $ad_details = get_ad_post_details(get_the_ID(), $truncate_title);
                                    $first_img = $ad_details['img'];
                                    $price_html = $ad_details['price_html'];
                                    $price_html = str_replace(['<strong>', '</strong>'], [
                                        '<h5>',
                                        '</h5>'
                                    ], $price_html);
                                    $ad_permalink = $ad_details['ad_link'];
                                    $is_featured = $ad_details['is_featured'];
                                    $ad_title = $ad_details['truncated_title'];
                                    ?>
                                    <div class="item">
                                        <div class="adt-mini-ad-box">
                                            <div class="ad-img-box">
                                                <a href="<?php echo esc_url($ad_permalink); ?>"><img
                                                            src="<?php echo esc_url($first_img); ?>"
                                                            alt="<?php echo esc_html__(get_the_title(), "adforest-elementor"); ?>"></a>
                                                <?php if ($is_featured) : ?>
                                                    <img class="featured-tag"
                                                         src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/featured.png' ?>"
                                                         alt="featured-tag">
                                                <?php endif; ?>
                                            </div>
                                            <div class="ad-meta-box">
                                                <a href="<?php echo esc_url($ad_permalink); ?>">
                                                    <h6>
                                                        <?php echo esc_html($ad_title); ?>
                                                    </h6></a>
                                                <?php echo $price_html; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <?php wp_reset_postdata(); ?>
                        <?php } ?>

                        <?php
                        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
                        $link_2 = '';
                        $args = [
                            'post_type' => 'ad_post',
                            'posts_per_page' => $main_section_ppp_2,
                            'post_status' => 'publish',
                        ];

                        if ($main_sec_ad_type_2 === 'recent') {
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                            $link_2 = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '');
                        } elseif ($main_sec_ad_type_2 === 'featured') {
                            $args['meta_key'] = '_adforest_is_feature';
                            $args['meta_value'] = '1';
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                            $link_2 = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '1');
                        }
                        $ads_query = new WP_Query($args);
                        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
                        $mobile_two_columns = ($sb_2column == false) ? "one-column-mobile-layout" : "";
                        if ($ads_query->have_posts()) {
                            ?>
                            <div class="adt-ads-top-box">
                                <h2><?php echo esc_html($main_sec_ad_type_2_title); ?></h2>
                                <a href="<?php echo esc_url($main_sec_ad_type_2_btn_link); ?>">
                                    <button class="adt-button-1"><?php echo esc_html($main_sec_ad_type_2_btn_text); ?></button>
                                </a>
                            </div>
                            <div class="adt-mini-ads-grid <?php echo esc_attr($mobile_two_columns) ?>">
                                <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                                    $truncate_title = 15;
                                    $ad_details = get_ad_post_details(get_the_ID(), $truncate_title);
                                    $first_img = $ad_details['img'];
                                    $price_html = $ad_details['price_html'];
                                    $price_html = str_replace(['<strong>', '</strong>'], [
                                        '<h5>',
                                        '</h5>'
                                    ], $price_html);
                                    $ad_permalink = $ad_details['ad_link'];
                                    $is_featured = $ad_details['is_featured'];
                                    $ad_title = $ad_details['truncated_title'];
                                    ?>
                                    <div class="adt-mini-ad-box">
                                        <div class="ad-img-box">
                                            <a href="<?php echo esc_url($ad_permalink); ?>"><img
                                                        src="<?php echo esc_url($first_img); ?>"
                                                        alt="<?php echo esc_html__(get_the_title(), "adforest-elementor"); ?>"></a>
                                        </div>
                                        <div class="ad-meta-box">
                                            <a href="<?php echo esc_url($ad_permalink); ?>">
                                                <h6><?php echo esc_html($ad_title); ?></h6></a>
                                            <?php echo $price_html; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <?php wp_reset_postdata(); ?>
                        <?php } ?>
                        <?php if (isset($show_ad_cats_advert) && $show_ad_cats_advert == 'yes' && $ad_cats_advert != "") { ?>
                            <div class="adt-horizontal-ad-box">
                                <?php echo wp_kses_post($ad_cats_advert); ?>
                            </div>
                        <?php } ?>
                        <?php
                        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
                        $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'country_id', '');
                        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
                        $mobile_two_columns = ($sb_2column == false) ? "one-column-mobile-layout" : "";
                        ?>
                        <div class="adt-ads-top-box">
                            <h2><?php echo esc_html($location_section_title); ?></h2>
                            <a href="<?php echo esc_url($location_section_btn_link); ?>">
                                <button class="adt-button-1"><?php echo esc_html($location_section_btn_text); ?></button>
                            </a>
                        </div>
                        <div class="adt-popular-locations-grid <?php echo esc_attr($mobile_two_columns) ?>">
                            <?php
                            $locations = adforest_get_ad_taxonomy_callback('ad_country');
                            foreach ($popular_locations as $location) :
                                $location_id = $location['location'];
                                $location_details = adforest_get_taxonomy_details_by_id($location_id, 'ad_country');
                                if (empty($location_details)) {
                                    continue;
                                }
                                $location_name = isset($location_details['name']) ? sanitize_text_field($location_details['name']) : '';
                                $loc_id = $location_details['id'] ?? '';

                                $location_image = '';
                                if (isset($location['location_image']) && is_array($location['location_image']) && !empty($location['location_image']['url'])) {
                                    $location_image = esc_url($location['location_image']['url']);
                                }

                                $ad_count = isset($location_details['ad_count']) ? intval($location_details['ad_count']) : 0;

                                ?>
                                <?php if (!empty($location_id) && function_exists('adforest_cat_link_page')) : ?>
                                <a href="<?php echo esc_url(adforest_cat_link_page($loc_id, '', 'country_id')); ?>" class="adt-mini-location-box">
                            <?php endif; ?>

                                <div class="location-img-box">
                                    <img src="<?php echo esc_url($location_image); ?>"
                                         alt="location">
                                    <span class="ads-count"><?php echo esc_html($ad_count . " ads"); ?></span>
                                </div>
                                <div class="location-meta-box">
                                    <span class="location-name"><?php echo esc_html($location_name); ?></span>
                                </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($hide_right_sidebar !== 'yes') : ?>
                    <div class="<?php echo esc_attr($sidebar_column_class); ?>">
                        <?php adforest_display_2_ads_sidebar_section($show_right_sec_ad_type, $right_sec_ad_type_title, $right_sec_ad_type, $right_section_ppp, $right_ad_img, $right_ad_img_2, $show_right_ad_img, $show_right_ad_img_2); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for mini Ad and Location ============= //

// ============= ShortCode for Ads Modern Bg ============= //
if (!function_exists('ads_modern_bg_shortcode')) {
    function ads_modern_bg_shortcode($params)
    {
        global $adforest_theme;
        $advert = isset($params['advert_horizontal']) ? $params['advert_horizontal'] : "";
        $show_advert = isset($params['show_advert']) ? $params['show_advert'] : "";
        $main_sec_title = isset($params['main_sec_title']) ? $params['main_sec_title'] : "";
        $main_sec_ad_type = isset($params['main_sec_ad_type']) ? $params['main_sec_ad_type'] : "";
        $main_sec_btn_text = isset($params['main_sec_btn_text']) ? $params['main_sec_btn_text'] : "";
        $main_sec_btn_link = isset($params['main_sec_btn_link']) ? $params['main_sec_btn_link'] : "";
        $main_section_ppp = isset($params['main_section_ppp']) ? $params['main_section_ppp'] : "";
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $link = '';
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-recent-mini-ads-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <?php
                        $args = [
                            'post_type' => 'ad_post',
                            'posts_per_page' => $main_section_ppp,
                            'post_status' => 'publish',
                        ];

                        if ($main_sec_ad_type === 'recent') {
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '');
                        } elseif ($main_sec_ad_type === 'featured') {
                            $args['meta_key'] = '_adforest_is_feature';
                            $args['meta_value'] = '1';
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '1');
                        } elseif ($main_sec_ad_type === 'all') {
                            $args['orderby'] = 'date';
                            $args['order'] = 'DESC';
                        }

                        $ads_query = new WP_Query($args);
                        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
                        $mobile_two_columns = ($sb_2column == false) ? "one-column-mobile-layout" : "";
                        if ($ads_query->have_posts()) {
                            ?>
                            <div class="adt-ads-top-box">
                                <h2><?php echo esc_html($main_sec_title); ?></h2>
                                <a href="<?php echo esc_url($main_sec_btn_link); ?>">

                                    <button class="adt-button-1">
                                        <?php echo esc_html($main_sec_btn_text); ?>
                                    </button>
                                </a>
                            </div>
                            <div class="adt-mini-ads-grid <?php echo esc_attr($mobile_two_columns) ?>">
                                <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                                    $truncate_title = 15;
                                    $ad_details = get_ad_post_details(get_the_ID(), $truncate_title);
                                    $first_img = $ad_details['img'];
                                    $price_html = $ad_details['price_html'];
                                    $price_html = str_replace(['<strong>', '</strong>'], [
                                        '<h5>',
                                        '</h5>'
                                    ], $price_html);
                                    $ad_permalink = $ad_details['ad_link'];
                                    $is_featured = $ad_details['is_featured'];
                                    $ad_title = $ad_details['truncated_title'];
                                    ?>
                                    <div class="adt-mini-ad-box">
                                        <div class="ad-img-box">
                                            <a href="<?php echo esc_url($ad_permalink); ?>"><img
                                                        src="<?php echo esc_url($first_img); ?>"
                                                        alt="<?php echo esc_html__(get_the_title(), "adforest-elementor"); ?>"></a>
                                            <?php if ($is_featured) : ?>
                                                <img class="featured-tag"
                                                     src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/featured.png' ?>"
                                                     alt="featured-tag">
                                            <?php endif; ?>
                                        </div>
                                        <div class="ad-meta-box">
                                            <a href="<?php echo esc_url($ad_permalink); ?>">
                                                <h6><?php echo esc_html($ad_title); ?></h6></a>
                                            <?php echo $price_html; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <?php wp_reset_postdata(); ?>
                        <?php } ?>
                        <?php if ($show_advert == 'yes' && $advert != "") { ?>
                            <div class="adt-horizontal-ad-box">
                                <?php echo wp_kses_post($advert); ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Ads Modern Bg ============= //

// ============= ShortCode for Multivendor Hero ============= //
if (!function_exists("adforest_multivendor_hero_shortcode")) {
    function adforest_multivendor_hero_shortcode($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_btn_text = isset($params['section_btn_text']) ? $params['section_btn_text'] : "";
        $section_btn_link = isset($params['section_btn_link']) ? $params['section_btn_link'] : "";
        $section_bg_img = isset($params['section_bg_img']['url']) ? $params['section_bg_img']['url'] : "";
        global $adforest_theme;

        $adt_container_class = "adt-container";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-multivendor-home-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="multivendor-hero-banner">
                            <h3><?php echo esc_html($section_title); ?></h3>
                            <h1><?php echo esc_html($section_tagline); ?></h1>
                            <a href="<?php echo esc_url($section_btn_link); ?>"
                               class="adt-theme-button-2"><?php echo esc_html($section_btn_text); ?></a>
                            <img class="main-img" src="<?php echo esc_url($section_bg_img); ?>"
                                 alt="banner-img">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Multivendor Hero ============= //

// ============= ShortCode for Multivendor Product Carousel ============= //
if (!function_exists("adforest_multivendor_product_carousel_shortcode")) {
    function adforest_multivendor_product_carousel_shortcode($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $grid_style = isset($params['grid_style']) ? $params['grid_style'] : "";
        $btn_title = isset($params['btn_title']) ? $params['btn_title'] : "";
        $btn_link = isset($params['btn_link']) ? $params['btn_link'] : "";
        $product_title_limit = isset($params['product_title_limit']) ? $params['product_title_limit'] : "";
        $grid_cols = isset($params['grid_cols']) ? $params['grid_cols'] : "";
        $no_of_prd_to_show = isset($params['no_of_prd_to_show']) ? $params['no_of_prd_to_show'] : '';
        $product_categories = isset($params['product_categories']) ? $params['product_categories'] : [];

        $args = array(
            'limit' => $no_of_prd_to_show,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            'type' => 'simple',
        );

        if (!empty($product_categories) && is_array($product_categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $product_categories,
                    'operator' => 'IN',
                ),
            );
        }

        $query = new WC_Product_Query($args);
        $latest_products = $query->get_products();

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2")) {
            $adt_container_class = "adt-container";
        }
        if ($grid_style == 'modern') {
            ?>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-ads-top-box">
                            <h2><?php echo esc_html($section_title); ?></h2>
                            <a href="<?php echo esc_url($btn_link); ?>">
                                <button class="adt-button-1"><?php echo esc_html($btn_title); ?></button>
                            </a>
                        </div>
                        <?php
                        if (!empty($latest_products)) { ?>
                            <div class="adt-multivendor-ads-carousel owl-carousel owl-theme"
                                 data-colums="<?php echo $grid_cols ?>">
                                <?php
                                foreach ($latest_products as $product_id) {
                                    $product = wc_get_product($product_id);
                                    $product_url = get_permalink($product_id);
                                    $thumbnail_id = get_post_thumbnail_id($product_id);
                                    $image_url = wp_get_attachment_image_src($thumbnail_id, 'full');
                                    if ($image_url) {
                                        $image_url = $image_url[0];
                                    } else {
                                        $image_url = wc_placeholder_img_src();
                                    }
                                    $title = $product->get_name();
                                    $price = $product->get_price_html();
                                    $rating = $product->get_average_rating();
                                    $review_count = $product->get_review_count();
                                    /* check already favourite or not */
                                    $heart_filled = 'fa-heart';
                                    $fav_class = "";
                                    $tooltip_title = esc_html__("Add to Wishlist", "adforest-elementor");
                                    if (get_user_meta(get_current_user_id(), '_product_fav_id_' . $product_id, true) == $product_id) {
                                        $fav_class = 'favourited';
                                        $heart_filled = 'fa-heart';
                                        $tooltip_title = esc_html__("Remove From Wishlist", "adforest-elementor");
                                    }
                                    $title = truncate_string($title, $product_title_limit);
                                    ?>

                                    <div class="item">
                                        <div class="adt-multivendor-category-ad-card">
                                            <div class="category-img-box">
                                                <a href="<?php echo esc_url($product_url); ?>">
                                                    <img class="img-fluid" src="<?php echo esc_url($image_url); ?>"
                                                         alt="<?php echo esc_attr($title); ?>">
                                                </a>
                                            </div>
                                            <div class="category-content-box">
                                                <div class="rating">
                                                    <span><?php echo esc_html($rating); ?></span>
                                                    <small><?php echo esc_html($review_count . " Reviews"); ?></small>
                                                </div>
                                                <a href="<?php echo esc_url($product_url); ?>">
                                                    <h5><?php echo esc_html(wp_trim_words($title, 6, '...')); ?></h5>
                                                </a>
                                                <strong class="price"><?php echo $price; ?></strong>
                                                <div class="detail-btn-box">
                                                    <a href="<?php echo esc_url($product_url); ?>" class="detail-btn">
                                                        <?php echo esc_html__("Detail Now", "adforest-elementor"); ?>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                       class="favourite product_to_fav  <?php echo $fav_class; ?>"
                                                       data-productId="<?php echo $product_id; ?>"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="<?php echo esc_attr($tooltip_title); ?>">
                                                        <i class="fa <?php echo $heart_filled; ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                        } ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php
        if (!empty($latest_products)) {
            if ($grid_style == 'fancy') { ?>
                <section class="adt-cyber-sale-products-section">
                    <div class="container <?php echo $adt_container_class; ?>">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="adt-ads-top-box">
                                    <h2><?php echo esc_html($section_title); ?></h2>
                                    <a href="<?php echo esc_url($btn_link); ?>">
                                        <button class="adt-button-1"><?php echo esc_html($btn_title); ?></button>
                                    </a>
                                </div>
                                <div class="cyber-sale-products-carousel owl-carousel owl-theme">
                                    <?php
                                    foreach ($latest_products as $product_id) {
                                        $product = wc_get_product($product_id);
                                        $product_url = get_permalink($product_id);
                                        $thumbnail_id = get_post_thumbnail_id($product_id);
                                        $image_url = wp_get_attachment_image_src($thumbnail_id, 'full');
                                        if ($image_url) {
                                            $image_url = $image_url[0];
                                        } else {
                                            $image_url = wc_placeholder_img_src();
                                        }
                                        $title = $product->get_name();
                                        $price = $product->get_price_html();
                                        $rating = $product->get_average_rating();
                                        $review_count = $product->get_review_count();
                                        /* check already favourite or not */
                                        $heart_filled = 'fa-heart';
                                        $fav_class = "";
                                        if (get_user_meta(get_current_user_id(), '_product_fav_id_' . $product_id, true) == $product_id) {
                                            $fav_class = 'favourited';
                                            $heart_filled = 'fa-heart';
                                        }
                                        if (isset($adforest_theme['shop-title-limit'])) {
                                            $shop_title_limit = isset($adforest_theme['shop-title-limit']) ? $adforest_theme['shop-title-limit'] : "";
                                            $title = truncate_string($title, $shop_title_limit);
                                        }
                                        ?>
                                        <div class="item">
                                            <div class="adt-cyber-sale-product-card">
                                                <div class="cyber-sale-product-img-box">
                                                    <a href="<?php echo esc_url($product_url); ?>"><img
                                                                src="<?php echo esc_url($image_url); ?>"
                                                                alt="product"></a>
                                                </div>
                                                <div class="cyber-sale-product-content">
                                                    <?php
                                                    $regular_price = $product->get_regular_price();
                                                    $sale_price = $product->get_sale_price();
                                                    ?>
                                                    <span>
                                                        <?php if (!empty($sale_price)) : ?>
                                                            <?php echo wc_price($sale_price); ?>
                                                            <del><?php echo wc_price($regular_price); ?></del>
                                                        <?php else : ?>
                                                            <?php echo wc_price($regular_price); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <a href="<?php echo esc_url($product_url); ?>">
                                                        <h4><?php echo esc_html(wp_trim_words(get_the_title(), 5, '...')); ?></h4>
                                                    </a>
                                                    <div class="rating">
                                                        <span><?php echo esc_html($product->get_rating_count()) . ' ' . __("Reviews ", "adforest-elementor"); ?></span>
                                                        <?php
                                                        $average_rating = $product->get_average_rating();

                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $average_rating) {
                                                                echo '<i class="fas fa-star"></i>';
                                                            } else {
                                                                echo '<i class="far fa-star"></i>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                    <a type="button" class="cart-btn custom_add_to_cart_button"
                                                       data-product-id="<?php echo esc_attr($product_id); ?>">
                                                        <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/cart.svg'; ?>"
                                                             alt="cart">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php }
        }
    }
}
// ============= ShortCode for Multivendor Product Carousel ============= //

// ============= ShortCode for Multivendor Product Categories ============= //
if (!function_exists('adforest_multivendor_product_categories')) {
    function adforest_multivendor_product_categories($params)
    {
        $section_title = $params['section_title'] ?? '';
        $btn_title = $params['btn_title'] ?? '';
        $btn_link = $params['btn_link'] ?? '';
        $source = $params['category_source'] ?? 'product'; // new
        $prod_items = $params['product_categories_repeater'] ?? [];
        $class_items = $params['classified_categories_repeater'] ?? [];
        $items = ($source === 'classified') ? $class_items : $prod_items;

        global $adforest_theme;
        $adt_container_class = 'adt-container';
        if (isset($adforest_theme['sb_header']) &&
            in_array($adforest_theme['sb_header'], ['white', 'header_w_topbar'], true)) {
            $adt_container_class = 'adt-container';
        }
        $page_id = get_queried_object_id();
        $page_header_style = get_post_meta($page_id, '_page_header_style', true);
        if (in_array($page_header_style, ['white', 'header_w_topbar', 'vendor-2'], true)) {
            $adt_container_class = 'adt-container';
        }

        $shop_url = wc_get_page_permalink('shop');
        ?>
        <div class="container <?php echo esc_attr($adt_container_class); ?>">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-ads-top-box">
                        <h2><?php echo esc_html($section_title); ?></h2>
                        <?php if ($btn_link) : ?>
                            <a href="<?php echo esc_url($btn_link); ?>">
                                <button class="adt-button-1"><?php echo esc_html($btn_title); ?></button>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($items) && is_array($items)) : ?>
                        <div class="adt-multivendor-mini-ctg-carousel owl-carousel owl-theme">
                            <?php foreach ($items as $category) :
                                $category_id = $category['main_category'];
                                $image_url = $category['category_image']['url'];

                                if ($source === 'classified') {
                                    $term = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                    $name = isset($term['name']) ? sanitize_text_field($term['name']) : '';

                                    $count = isset($term['ad_count']) ? intval($term['ad_count']) : 0;

                                    $cat_id = isset($term['id']) ? $term['id'] : '';

                                    $category_link = (!empty($cat_id) && function_exists('adforest_cat_link_page'))
                                        ? esc_url(adforest_cat_link_page($cat_id, 'cat_id'))
                                        : '';
                                } else {
                                    $cat_details = adforest_get_woocommerce_category_details($category_id);
                                    $name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                    $count = isset($cat_details['count']) ? intval($cat_details['count']) : 0;

                                    $category_id = isset($category_id) ? urlencode($category_id) : ''; // Ensure it's URL-safe
                                    $shop_url = isset($shop_url) ? esc_url($shop_url) : '';

                                    $category_link = !empty($category_id) && !empty($shop_url)
                                        ? esc_url($shop_url . '?category_id=' . $category_id)
                                        : '';
                                }
                                ?>
                                <div class="item">
                                    <div class="adt-multivendor-mini-ctg-box">
                                        <div class="img-box">
                                            <a href="<?php echo esc_url($category_link); ?>">
                                                <img src="<?php echo esc_url($image_url); ?>"
                                                     alt="<?php echo esc_attr($name); ?>">
                                            </a>
                                        </div>
                                        <a href="<?php echo esc_url($category_link); ?>">
                                            <h5><?php echo esc_html($name); ?></h5>
                                        </a>
                                        <span>
                                            <?php
                                            echo esc_html($count);
                                            echo $source === 'classified'
                                                ? ' Ads'
                                                : ' Products';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
// ============= ShortCode for Multivendor Product Categories ============= //

// ============= ShortCode for Multivendor Product With Sidebar ============= //
if (!function_exists('adforest_multivendor_product_w_sidebar')) {
    function adforest_multivendor_product_w_sidebar($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $sidebar_position = isset($params['sidebar_position']) ? $params['sidebar_position'] : "";
        $sidebar_title = isset($params['sidebar_title']) ? $params['sidebar_title'] : "";
        $product_category = isset($params['product_category']) ? $params['product_category'] : "";
        $ad_img = isset($params['advert_img']) ? $params['advert_img'] : "";
        $ad_position = isset($params['ad_position']) ? $params['ad_position'] : "";
        $button_text = isset($params['button_text']) ? $params['button_text'] : "";
        $btn_link = isset($params['btn_link']) ? $params['btn_link'] : "";
        $ad_limit = isset($params['ad_limit']) ? $params['ad_limit'] : "";
        $ad_limit_side = isset($params['ad_limit_side']) ? $params['ad_limit_side'] : "";
        $product_title_limit_sidebar = isset($params['product_title_limit_side']) ? $params['product_title_limit_side'] : "";
        $product_title_limit = isset($params['product_title_limit']) ? $params['product_title_limit'] : "";
        $product_categories_main = isset($params['product_categories_main']) ? $params['product_categories_main'] : "";
        $grid_style = isset($params['grid_style']) ? $params['grid_style'] : "";
        $grid_cols = isset($params['grid_cols']) ? $params['grid_cols'] : "";

        global $adforest_theme;

        $adt_container_class = "adt-container";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        ?>
        <div class="container <?php echo $adt_container_class; ?>">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-multivendor-product-sidebar-wrapper">
                        <?php if ($sidebar_position == 'left') { ?>
                            <div class="multivendor-product-sidebar">
                                <?php if ($ad_position == 'top') { ?>
                                    <?php if ($ad_img) { ?>
                                        <div class="adt-vertical-ad-box">
                                            <?php echo wp_kses_post($ad_img); ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                                <div class="adt-recent-ads-sidebar">
                                    <h4><?php echo esc_html($sidebar_title); ?></h4>
                                    <ul>
                                        <?php
                                        $args = array(
                                            'post_type' => 'product',
                                            'posts_per_page' => $ad_limit_side,
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => 'product_cat',
                                                    'field' => 'term_id',
                                                    'terms' => $product_category,
                                                ),
                                            ),
                                        );
                                        $products = new WP_Query($args);
                                        if ($products->have_posts()) {
                                            while ($products->have_posts()) {
                                                $products->the_post();
                                                global $product;
                                                $truncated_title = truncate_string(get_the_title(), $product_title_limit_sidebar);
                                                ?>
                                                <li>
                                                    <div class="adt-recent-ad-box">
                                                        <a href="<?php the_permalink(); ?>" class="recent-img-box">
                                                            <?php echo woocommerce_get_product_thumbnail('thumbnail'); ?>
                                                        </a>
                                                        <div class="recent-img-meta">
                                                            <a href="<?php the_permalink(); ?>">
                                                                <h6><?php echo $truncated_title ?></h6></a>
                                                            <strong><?php echo $product->get_price_html(); ?></strong>
                                                        </div>
                                                    </div>
                                                </li>
                                                <?php
                                            }
                                            wp_reset_postdata();
                                        } else {
                                            echo '<li>' . __('No products found.', 'adforest-elementor') . '</li>';
                                        } ?>
                                    </ul>
                                </div>
                                <?php if ($ad_position == 'btm') { ?>
                                    <?php if ($ad_img) { ?>
                                        <div class="adt-vertical-ad-box">
                                            <?php echo wp_kses_post($ad_img); ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <div class="adt-multivendor-product-wrapper">
                            <div class="adt-ads-top-box">
                                <h2><?php echo esc_html($section_title); ?></h2>
                                <a href="<?php echo esc_url($btn_link); ?>">
                                    <button class="adt-button-1"><?php echo esc_html($button_text); ?></button>
                                </a>
                            </div>
                            <div class="adt-multivendor-product-grid <?php echo "adt-multivendor-product-col-" . $grid_cols; ?>">
                                <?php
                                $args = array(
                                    'limit' => $ad_limit,
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                    'return' => 'ids',
                                    'type' => 'simple',
                                );
                                if (!empty($product_categories_main) && is_array($product_categories_main)) {
                                    $args['tax_query'] = array(
                                        array(
                                            'taxonomy' => 'product_cat',
                                            'field' => 'term_id',
                                            'terms' => $product_categories_main,
                                            'operator' => 'IN',
                                        ),
                                    );
                                }

                                $query = new WC_Product_Query($args);
                                $latest_products = $query->get_products();
                                foreach ($latest_products as $product_id) {
                                    $product = wc_get_product($product_id);
                                    $product_url = get_permalink($product_id);
                                    $thumbnail_id = get_post_thumbnail_id($product_id);
                                    $image_url = wp_get_attachment_image_src($thumbnail_id, 'full');
                                    if ($image_url) {
                                        $image_url = $image_url[0];
                                    } else {
                                        $image_url = wc_placeholder_img_src();
                                    }
                                    $title = $product->get_name();
                                    $price = $product->get_price_html();
                                    $rating = $product->get_average_rating();
                                    $review_count = $product->get_review_count();
                                    $heart_filled = 'fa-heart';
                                    $fav_class = "";
                                    $tooltip_title = esc_html__("Add to Wishlist", "adforest-elementor");
                                    if (get_user_meta(get_current_user_id(), '_product_fav_id_' . $product_id, true) == $product_id) {
                                        $fav_class = 'favourited';
                                        $heart_filled = 'fa-heart';
                                        $tooltip_title = esc_html__("Remove From Wishlist", "adforest-elementor");
                                    }
                                    if (isset($adforest_theme['shop-title-limit'])) {
                                        $shop_title_limit = isset($adforest_theme['shop-title-limit']) ? $adforest_theme['shop-title-limit'] : "";
                                        $title = truncate_string($title, $shop_title_limit);
                                    }
                                    ?>
                                    <?php if ($grid_style == 'modern') { ?>
                                        <div class="adt-multivendor-category-ad-card">
                                            <div class="category-img-box">
                                                <a href="<?php echo esc_url($product_url); ?>">
                                                    <img class="img-fluid"
                                                         src="<?php echo esc_url($image_url); ?>"
                                                         alt="<?php echo esc_html__(get_the_title(), "adforest-elementor"); ?>">
                                                </a>
                                            </div>
                                            <div class="category-content-box">
                                                <div class="rating">
                                                    <span><?php echo esc_html($rating); ?></span>
                                                    <small><?php echo esc_html($review_count . " Reviews"); ?></small>
                                                </div>
                                                <a href="<?php echo esc_url($product_url); ?>">
                                                    <h5><?php echo esc_html(truncate_string($title, $product_title_limit)); ?></h5>
                                                </a>
                                                <strong class="price"><?php echo $price; ?></strong>
                                                <div class="detail-btn-box">
                                                    <a href="<?php echo esc_url($product_url); ?>" class="detail-btn">
                                                        <?php echo esc_html__("Detail Now", 'adforest-elementor'); ?>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                       class="favourite product_to_fav  <?php echo $fav_class; ?>"
                                                       data-productId="<?php echo $product_id; ?>"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="<?php echo esc_attr($tooltip_title); ?>">
                                                        <i class="fa <?php echo $heart_filled; ?>"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="adt-cyber-sale-product-card">
                                            <div class="cyber-sale-product-img-box">
                                                <a href="<?php echo esc_url($product_url); ?>"><img
                                                            src="<?php echo esc_url($image_url); ?>"
                                                            alt="product"></a>
                                            </div>
                                            <div class="cyber-sale-product-content">
                                                <?php
                                                $regular_price = $product->get_regular_price();
                                                $sale_price = $product->get_sale_price();
                                                ?>
                                                <span>
                                                        <?php if (!empty($sale_price)) : ?>
                                                            <?php echo wc_price($sale_price); ?>
                                                            <del><?php echo wc_price($regular_price); ?></del>
                                                        <?php else : ?>
                                                            <?php echo wc_price($regular_price); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <a href="<?php echo esc_url($product_url); ?>">
                                                    <h4><?php echo esc_html__(truncate_string($title, $product_title_limit)); ?></h4>
                                                </a>
                                                <div class="rating">
                                                    <span><?php echo esc_html($product->get_rating_count()) . ' ' . __("Reviews ", "adforest-elementor"); ?></span>
                                                    <?php
                                                    $average_rating = $product->get_average_rating();

                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $average_rating) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <a type="button" class="cart-btn custom_add_to_cart_button"
                                                   data-product-id="<?php echo esc_attr($product_id); ?>">
                                                    <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/cart.svg'; ?>"
                                                         alt="cart">
                                                </a>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                        <?php if ($sidebar_position == 'right') { ?>
                            <div class="multivendor-product-sidebar">
                                <?php if ($ad_position == 'top') { ?>
                                    <?php if ($ad_img) { ?>
                                        <div class="adt-vertical-ad-box">
                                            <?php echo wp_kses_post($ad_img); ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                                <div class="adt-recent-ads-sidebar">
                                    <h4><?php echo esc_html($sidebar_title); ?></h4>
                                    <ul>
                                        <?php
                                        $args = array(
                                            'post_type' => 'product',
                                            'posts_per_page' => 5,
                                            'tax_query' => array(
                                                array(
                                                    'taxonomy' => 'product_cat',
                                                    'field' => 'term_id',
                                                    'terms' => $product_category,
                                                ),
                                            ),
                                        );
                                        $products = new WP_Query($args);
                                        if ($products->have_posts()) {
                                            while ($products->have_posts()) {
                                                $products->the_post();
                                                global $product;
                                                $truncated_title = truncate_string(get_the_title(), 15);
                                                ?>
                                                <li>
                                                    <div class="adt-recent-ad-box">
                                                        <a href="<?php the_permalink(); ?>" class="recent-img-box">
                                                            <?php echo woocommerce_get_product_thumbnail('thumbnail'); ?>
                                                        </a>
                                                        <div class="recent-img-meta">
                                                            <a href="<?php the_permalink(); ?>">
                                                                <h6><?php echo $truncated_title; ?></h6></a>
                                                            <strong><?php echo $product->get_price_html(); ?></strong>
                                                        </div>
                                                    </div>
                                                </li>
                                                <?php
                                            }
                                            wp_reset_postdata();
                                        } else {
                                            echo '<li>' . __('No products found.', 'adforest-elementor') . '</li>';
                                        } ?>
                                    </ul>
                                </div>
                                <?php if ($ad_position == 'btm') { ?>
                                    <?php if ($ad_img) { ?>
                                        <div class="adt-vertical-ad-box">
                                            <?php echo wp_kses_post($ad_img); ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
// ============= ShortCode for Multivendor Product With Sidebar ============= //

// ============= ShortCode for Multivendor Services ============= //
if (!function_exists('adforest_multivendor_services_shortcode')) {
    function adforest_multivendor_services_shortcode($params)
    {
        $services_list = isset($params['services_list']) ? $params['services_list'] : "";
        global $adforest_theme;

        $adt_container_class = "adt-container";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        if (!empty($services_list)) {
            $services = $services_list;
            ?>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <?php
                    foreach ($services as $service) {
                        echo '<div class="col-6 col-lg-3">';
                        echo '    <div class="adt-multivendor-services-box">';
                        echo '        <i class="' . esc_attr($service['service_icon']) . '"></i>';
                        echo '        <h6>' . esc_html($service['service_title']) . '</h6>';
                        echo '        <p>' . esc_html($service['service_description']) . '</p>';
                        echo '    </div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
    }
}
// ============= ShortCode for Multivendor Services ============= //

// ============= ShortCode for Real Estate Hero ============= //
if (!function_exists('adforest_real_estate_hero_shortcode')) {
    function adforest_real_estate_hero_shortcode($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";
        $section_bg_img = isset($params['section_bg_img']['url']) ? $params['section_bg_img']['url'] : "";
        $classified_search_ad_types = isset($params['classified_search_ad_types']) ? $params['classified_search_ad_types'] : "";
        $classified_search_fields = isset($params['classified_search_fields']) ? $params['classified_search_fields'] : "";
        $popular_ad_categories = isset($params['mini_categories_repeater']) ? $params['mini_categories_repeater'] : "";
        $carousel_categories_repeater = isset($params['carousel_categories_repeater']) ? $params['carousel_categories_repeater'] : "";
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-estate-hero-section-start -->
        <section class="adt-estate-hero-section<?php if (!empty($section_bg_img)) : ?> has-bg-image<?php endif; ?>" <?php if (!empty($section_bg_img)) : ?>style="background-image: url('<?php echo esc_url($section_bg_img); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;"<?php endif; ?>>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-10 col-xl-8">
                        <div class="content-box">
                            <span class="sub-title"><?php echo esc_html($section_title); ?></span>
                            <h1><?php echo esc_html($section_tagline); ?></h1>
                            <p><?php echo esc_html($section_description); ?></p>
                            <div class="adt-hero-search-tabs">
                                <nav>
                                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                        <?php
                                        $first = true;
                                        foreach ($classified_search_ad_types as $ad_type):
                                            $term = get_term($ad_type['classified_ad_type']);
                                            if ($term) : ?>
                                                <button class="classified-nav-link nav-link <?php echo $first ? 'active' : ''; ?>"
                                                        id="nav-<?php echo $term->term_id; ?>">
                                                    <?php echo esc_html($term->name); ?>
                                                </button>
                                                <?php
                                                $first = false;
                                            endif;
                                        endforeach;
                                        ?>
                                    </div>
                                </nav>
                                <div class="tab-content" id="nav-tabContent">
                                    <form method="GET"
                                          action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                        <div class="search-filters-bar">
                                            <?php foreach ($classified_search_fields as $field) { ?>
                                                <?php if ($field['classified_field_type'] === 'title') { ?>
                                                    <div class="filter-box">
                                                        <label for="ad_title"><?php echo __("Search", "adforest-elementor"); ?></label>
                                                        <input type="text" name="ad_title" id="ad_title"
                                                               placeholder="<?php echo esc_attr__('Enter Keywords', 'adforest-elementor'); ?>">
                                                    </div>
                                                <?php } elseif ($field['classified_field_type'] === 'category') {
                                                    $categories = adforest_get_ad_taxonomy_callback('ad_cats');
                                                    ?>
                                                    <div class="filter-box type-box">
                                                        <label for="search"><?php echo esc_html__('Category', 'adforest-elementor'); ?></label>
                                                        <select class="default-select" name="cat_id">
                                                            <option value=""><?php echo __("Select Category", 'adforest-elementor'); ?></option>
                                                            <?php foreach ($categories as $category) {
                                                                $cat_details = get_taxonomy_details($category);
                                                                $category_name = $cat_details['name'] ?? '';
                                                                $category_id = $category->term_id;
                                                                ?>
                                                                <option value="<?php echo esc_attr($category_id); ?>"><?php echo esc_html($category_name); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                <?php } elseif ($field['classified_field_type'] === 'location') {
                                                    $countries = adforest_get_ad_taxonomy_callback('ad_country');
                                                    ?>
                                                    <div class="filter-box location-box">
                                                        <label for="search"><?php echo esc_html__('Location', 'adforest-elementor'); ?></label>
                                                        <select class="default-select" name="country_id">
                                                            <option value=""><?php echo esc_html__('Select Location', 'adforest-elementor'); ?></option>
                                                            <?php foreach ($countries as $country) {
                                                                $country_details = get_taxonomy_details($country);
                                                                $country_name = $country_details['name'] ?? '';
                                                                $country_id = $country->term_id;
                                                                ?>
                                                                <option value="<?php echo esc_attr($country_id); ?>"><?php echo esc_html($country_name); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                            <button class="adt-button-dark-1 search-button" type="submit">
                                                <i class="fas fa-search"></i><?php echo __("Search", "adforest-elementor"); ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php
                            if (is_array($popular_ad_categories) && count($popular_ad_categories) > 0) :
                                ?>
                                <ul class="popular-keywords">
                                    <li><strong><?php echo __("Popular:", 'adforest-elementor'); ?></strong></li>
                                    <?php foreach ($popular_ad_categories as $category) :
                                        $category_id = $category['mini_category'];
                                        $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                        $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                        $cat_id = isset($cat_details['id']) ? $cat_details['id'] : '';

                                        $link = (!empty($cat_id) && function_exists('adforest_cat_link_page'))
                                            ? esc_url(adforest_cat_link_page($cat_id, 'cat_id'))
                                            : '#';

                                        ?>
                                        <li>
                                            <a href="<?php echo esc_url($link); ?>">
                                                <?php if (!empty($category['category_image']['url'])) : ?>
                                                    <img src="<?php echo $category['category_image']['url']; ?>"
                                                         alt="vector">
                                                <?php endif; ?>
                                                <?php echo esc_html($category_name); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-2 col-xl-4">
                        <div class="main-img-box">
                            <img src="<?php echo esc_url($bg_img); ?>" alt="main-img">
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="categories-carousel-box">
                            <h3><?php echo __("Find by Categories", "adforest-revamp"); ?></h3>
                            <div class="owl-carousel owl-theme adt-find-by-categories-carousel">
                                <?php foreach ($carousel_categories_repeater as $category) {
                                    $category_id = $category['carousel_category'];
                                    $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                    $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                    $category_ad_count = isset($cat_details['ad_count']) ? intval($cat_details['ad_count']) : 0;

                                    $link = '';
                                    if (!empty($cat_details['id']) && function_exists('adforest_cat_link_page')) {
                                        $link = esc_url(adforest_cat_link_page($cat_details['id'], $cat_link_page, 'cat_id'));
                                    }

                                    ?>
                                    <div class="item">
                                        <a href="<?php echo (isset($link) && !is_wp_error($link)) ? esc_url($link) : ''; ?>">
                                            <div class="category-box-main">
                                                <img src="<?php echo esc_url($category['carousel_category_image']['url']); ?>"
                                                     alt="house-img">
                                                <span><?php echo esc_html($category_name); ?></span>
                                                <small><?php echo esc_html($category_ad_count . " Ads"); ?></small>
                                            </div>
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-estate-hero-section-end -->
        <?php
    }
}
// ============= ShortCode for Real Estate Hero ============= //

// ============= ShortCode for Ad Grid Carousel ============= //
if (!function_exists('adforest_ad_grid_carousel_modern_shortcode')) {
    function adforest_ad_grid_carousel_modern_shortcode($params)
    {
        ob_start();

        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();
        $page_header_style = get_post_meta($page_id, '_page_header_style', true);
        if (in_array($page_header_style, ["white", "header_w_topbar", "vendor-2", "transparent"])) {
            $adt_container_class = "adt-container";
        }

        $title = $params['title'] ?? "";
        $desc = $params['desc'] ?? "";
        $ad_cats_select = $params['ad_cats_select'] ?? "";
        $ad_select = $params['ad_select'] ?? "";
        $no_of_ads = $params['no_of_ads'] ?? 5;
        $ad_title_limit = $params['ad_title_limit'] ?? 25;
        $button_text = $params['button_text'] ?? "";
        $button_link = $params['button_link'] ?? "";
        $carousel_grid_columns = $params['carousel_grid_columns'] ?? "4";
        $grid_style = $params['grid_style'] ?? "1";

        $ads_query = null;
        $args = [
            'post_type' => 'ad_post',
            'posts_per_page' => $no_of_ads,
            'post_status' => 'publish',
        ];

        if (!empty($ad_cats_select)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'ad_cats',
                    'field' => 'term_id',
                    'terms' => $ad_cats_select,
                    'operator' => 'IN',
                ],
            ];
        }

        switch ($ad_select) {
            case 'simple':
                $args['meta_query'] = [
                    'relation' => 'OR',
                    ['key' => '_adforest_is_feature', 'value' => '0', 'compare' => '='],
                    ['key' => '_adforest_is_feature', 'compare' => 'NOT EXISTS'],
                ];
                break;

            case 'featured':
                $args['meta_query'] = [
                    ['key' => '_adforest_is_feature', 'value' => '1', 'compare' => '='],
                ];
                break;

            case 'both':
                $args['meta_query'] = [
                    'relation' => 'OR',
                    ['key' => '_adforest_is_feature', 'value' => '1', 'compare' => '='],
                    ['key' => '_adforest_is_feature', 'value' => '0', 'compare' => '='],
                    ['key' => '_adforest_is_feature', 'compare' => 'NOT EXISTS'],
                ];
                break;
        }

        $ads_query = new WP_Query($args);

        // Mobile layout config
        $sb_2column = !empty($adforest_theme['sb_2column_mobile_layout']);
        $mobile_two_columns = $sb_2column ? 2 : 1;

        ?>
        <section class="adt-property-ads-section">
            <div class="container <?php echo esc_attr($adt_container_class); ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-ads-top-box">
                            <div class="ad-content-box">
                                <h2><?php echo esc_html($title); ?></h2>
                                <?php if (!empty($desc)) : ?>
                                    <p class="ad-desc"><?php echo esc_html($desc); ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo esc_url($button_link); ?>">
                                <button class="adt-button-1"><?php echo esc_html($button_text); ?></button>
                            </a>
                        </div>

                        <div class="adt-property-ads-carousel-widgets owl-carousel owl-theme"
                             data-columns="<?php echo esc_attr($carousel_grid_columns); ?>"
                             data-mobile-columns="<?php echo esc_attr($mobile_two_columns); ?>">
                            <?php
                            if ($ads_query && $ads_query->have_posts()) {
                                while ($ads_query->have_posts()) {
                                    $ads_query->the_post();
                                    $ad_details = get_ad_post_details(get_the_ID());

                                    $img = $ad_details['img'];
                                    $truncated_location = $ad_details['truncated_location'];
                                    $price_html = $ad_details['price_html'];
                                    $heart_class = $ad_details['heart_class'];
                                    $is_featured = $ad_details['is_featured'];
                                    $ad_categories_post = $ad_details['categories'];
                                    $ad_poster_img = $ad_details['ad_poster_img'];
                                    $ad_poster_name = $ad_details['ad_poster_name'];
                                    $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);
                                    $ad_permalink = $ad_details['ad_link'];
                                    $ad_title = truncate_string($ad_details['ad_title'], $ad_title_limit);
                                    $all_ad_images = $ad_details['all_ad_images'];
                                    $featured_tag = $is_featured ? '<img src="' . plugin_dir_url(__FILE__) . 'assets/images/featured-tag.png' . '" alt="featured-tag" class="featured-tag">' : '';

                                    echo '<div class="item">';
                                    switch ($grid_style) {
                                        case '1':
                                            echo adforest_ad_grid_1($ad_permalink, $img, $is_featured, $ad_categories_post, $ad_details, $ad_title, $truncated_location, $price_html, $heart_class);
                                            break;
                                        case '2':
                                            echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class);
                                            break;
                                        case '3':
                                            echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                            break;
                                        case '4':
                                            echo adforest_ad_grid_4($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                            break;
                                    }
                                    echo '</div>';
                                }
                                wp_reset_postdata();
                            } elseif (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                                echo '<div class="item"><div class="placeholder">Carousel placeholder content shown in Elementor edit mode.</div></div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php

        return ob_get_clean(); // Return the buffered output
    }
}
// ============= ShortCode for Ad Grid Carousel ============= //

// ============= ShortCode for How it Works Modern ============= //
if (!function_exists('how_it_works_modern_shortcode')) {
    function how_it_works_modern_shortcode($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $bg_img = isset($params['bg_img']) ? $params['bg_img'] : "";
        $icon_1 = isset($params['icon_1']) ? $params['icon_1'] : "";
        $workflow_title_1 = isset($params['workflow_title_1']) ? $params['workflow_title_1'] : "";
        $workflow_tagline_1 = isset($params['workflow_tagline_1']) ? $params['workflow_tagline_1'] : "";
        $icon_2 = isset($params['icon_2']) ? $params['icon_2'] : "";
        $workflow_title_2 = isset($params['workflow_title_2']) ? $params['workflow_title_2'] : "";
        $workflow_tagline_2 = isset($params['workflow_tagline_2']) ? $params['workflow_tagline_2'] : "";
        $icon_3 = isset($params['icon_3']) ? $params['icon_3'] : "";
        $workflow_title_3 = isset($params['workflow_title_3']) ? $params['workflow_title_3'] : "";
        $workflow_tagline_3 = isset($params['workflow_tagline_3']) ? $params['workflow_tagline_3'] : "";
        $box_title_1 = isset($params['box_title_1']) ? $params['box_title_1'] : "";
        $box_tagline_1 = isset($params['box_tagline_1']) ? $params['box_tagline_1'] : "";
        $box_btn_text_1 = isset($params['box_btn_text_1']) ? $params['box_btn_text_1'] : "";
        $box_btn_link_1 = isset($params['box_btn_link_1']) ? $params['box_btn_link_1'] : "";
        $box_bg_img_1 = isset($params['box_bg_img_1']) ? $params['box_bg_img_1'] : "";
        $box_title_2 = isset($params['box_title_2']) ? $params['box_title_2'] : "";
        $box_tagline_2 = isset($params['box_tagline_2']) ? $params['box_tagline_2'] : "";
        $box_btn_text_2 = isset($params['box_btn_text_2']) ? $params['box_btn_text_2'] : "";
        $box_btn_link_2 = isset($params['box_btn_link_2']) ? $params['box_btn_link_2'] : "";
        $box_bg_img_2 = isset($params['box_bg_img_2']) ? $params['box_bg_img_2'] : "";
        $box_bg_1 = isset($params['box_bg_1']) ? $params['box_bg_1'] : "";
        $box_bg_2 = isset($params['box_bg_2']) ? $params['box_bg_2'] : "";
        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }

        $style = "";
        if ($bg_img !== "") {
            $style = 'style="background-image:url(' . esc_url($bg_img) . ') !important;"';
        }

        ?>
        <!-- adt-update-work-flow-section-start -->
        <section class="adt-work-flow-section adt-update-work-flow-section" <?php echo $style ?> >
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="heading-content">
                            <h2><?php echo esc_html($section_title); ?></h2>
                            <p><?php echo esc_html($section_tagline); ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="work-process-box">
                            <div class="work-type-outline">
                                <div class="type-outer">
                                    <?php if (isset($icon_1['library']) && $icon_1['library'] === "svg") { ?>
                                        <?php if (isset($icon_1['value']['url']) && !empty($icon_1['value']['url'])) { ?>
                                            <img src="<?php echo esc_url($icon_1['value']['url']); ?>" alt=""/>
                                        <?php } ?>
                                    <?php } elseif (isset($icon_1['value']) && !empty($icon_1['value'])) { ?>
                                        <i class="<?php echo esc_attr($icon_1['value']); ?>"></i>
                                    <?php } ?>
                                </div>
                                <div class="count-outline">
                                    <div class="count">
                                        <span>1</span>
                                    </div>
                                </div>
                            </div>
                            <small class="title"><?php echo esc_html($workflow_title_1); ?></small>
                            <h3 class="heading"><?php echo esc_html($workflow_tagline_1); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="work-process-box">
                            <div class="work-type-outline">
                                <div class="type-outer">
                                    <?php if (isset($icon_2['library']) && $icon_2['library'] === "svg") { ?>
                                        <?php if (isset($icon_2['value']['url']) && !empty($icon_2['value']['url'])) { ?>
                                            <img src="<?php echo esc_url($icon_2['value']['url']); ?>" alt=""/>
                                        <?php } ?>
                                    <?php } elseif (isset($icon_2['value']) && !empty($icon_2['value'])) { ?>
                                        <i class="<?php echo esc_attr($icon_2['value']); ?>"></i>
                                    <?php } ?>
                                </div>
                                <div class="count-outline">
                                    <div class="count">
                                        <span>2</span>
                                    </div>
                                </div>
                            </div>
                            <small class="title"><?php echo esc_html($workflow_title_2); ?></small>
                            <h3 class="heading"><?php echo esc_html($workflow_tagline_2); ?></h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="work-process-box">
                            <div class="work-type-outline">
                                <div class="type-outer">
                                    <?php if (isset($icon_3['library']) && $icon_3['library'] === "svg") { ?>
                                        <?php if (isset($icon_3['value']['url']) && !empty($icon_3['value']['url'])) { ?>
                                            <img src="<?php echo esc_url($icon_3['value']['url']); ?>" alt=""/>
                                        <?php } ?>
                                    <?php } elseif (isset($icon_3['value']) && !empty($icon_3['value'])) { ?>
                                        <i class="<?php echo esc_attr($icon_3['value']); ?>"></i>
                                    <?php } ?>
                                </div>
                                <div class="count-outline">
                                    <div class="count">
                                        <span>3</span>
                                    </div>
                                </div>
                            </div>
                            <small class="title"><?php echo esc_html($workflow_title_3); ?></small>
                            <h3 class="heading"><?php echo esc_html($workflow_tagline_3); ?></h3>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="adt-work-process-card" style="background-color: <?php echo $box_bg_1; ?>">
                            <div class="left-content">
                                <h4><?php echo esc_html($box_title_1); ?></h4>
                                <p><?php echo esc_html($box_tagline_1); ?></p>
                                <a href="<?php echo esc_url($box_btn_link_1); ?>"
                                   class="adt-button-dark-1"><?php echo esc_html($box_btn_text_1); ?></a>
                            </div>
                            <div class="right-img-box">
                                <img src="<?php echo esc_url($box_bg_img_1); ?>"
                                     alt="post-ad-img">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="adt-work-process-card" style="background-color: <?php echo $box_bg_1; ?>">
                            <div class="left-content">
                                <h4><?php echo esc_html($box_title_2); ?></h4>
                                <p><?php echo esc_html($box_tagline_2); ?></p>
                                <a href="<?php echo esc_url($box_btn_link_2); ?>"
                                   class="adt-button-dark-1"><?php echo esc_html($box_btn_text_2); ?></a>
                            </div>
                            <div class="right-img-box">
                                <img src="<?php echo esc_url($box_bg_img_2); ?>"
                                     alt="create-account-img">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img class="shape-1" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-1.png' ?>"
                 alt="shape">
            <img class="shape-2" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-2.png' ?>"
                 alt="shape">
            <img class="shape-3" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-1.png' ?>"
                 alt="shape">
        </section>
        <!-- adt-update-work-flow-section-end -->
        <?php
    }
}
// ============= ShortCode for How it Works Modern ============= //

// ============= ShortCode for Locations Modern ============= //
if (!function_exists('locations_modern_shortcode')) {
    function locations_modern_shortcode($params)
    {
        $section_style = isset($params['section_style']) ? $params['section_style'] : "";
        $main_sec_title = isset($params['main_sec_title']) ? $params['main_sec_title'] : "";
        $main_sec_btn_text = isset($params['main_sec_btn_text']) ? $params['main_sec_btn_text'] : "";
        $main_sec_btn_link = isset($params['main_sec_btn_link']) ? $params['main_sec_btn_link'] : "";
        $locations = isset($params['location_repeater']) ? $params['location_repeater'] : "";
        $button_style = isset($params['button_style']) ? $params['button_style'] : "";
        $top_text = isset($params['top_text']) ? $params['top_text'] : "";
        $location_bg = isset($params['location_bg']['url']) ? $params['location_bg']['url'] : "";
        global $adforest_theme;

        $button_style_class = $button_style == "dark" ? "adt-button-dark-1" : "adt-button-dark";

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }

        $bg_style = "";
        if ($location_bg) {
            $bg_style = 'style="background: url(' . $location_bg . ')"';
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = $sb_2column ? "col-6" : "";
        ?>
        <!-- adt-popular-location-section-start -->
        <section class="adt-popular-location-section adt-estate-location-section" <?php echo $bg_style; ?>>
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="top-heading-box">
                            <h4><?php echo esc_html($main_sec_title); ?></h4>
                            <?php if ($section_style == '1') { ?>
                                <a href="<?php echo esc_url($main_sec_btn_link); ?>"
                                   class="adt-button-dark-1"><?php echo esc_html($main_sec_btn_text); ?></a>
                            <?php } ?>
                            <?php if ($section_style == '2') { ?>
                                <p class="bold_text"><?php echo wp_kses_post($top_text); ?></p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="row" id="masonry-grid">
                    <?php
                    if (!empty($locations)) {
                        $item_count = 0;

                        foreach ($locations as $location) {
                            $item_count++;
                            $location_id = $location['locations'];
                            $location_details = adforest_get_taxonomy_details_by_id($location_id, 'ad_country');
                            if (empty($location_details)) {
                                continue;
                            }
                            $location_name = isset($location_details['name']) ? sanitize_text_field($location_details['name']) : '';

                            $ad_count = isset($location_details['ad_count']) ? intval($location_details['ad_count']) : 0;

                            $link = isset($location_details['link']) ? esc_url($location_details['link']) : '#';


                            $large_class = '';
                            if ($section_style == '1') {
                                if ($item_count == 4 || $item_count == 6) {
                                    $large_class = 'large-img-box';
                                } elseif ($item_count > 10 && $item_count % 3 == 0) {
                                    $large_class = 'large-img-box';
                                }
                            }
                            ?>
                            <div class="<?php echo esc_attr($mobile_two_columns) ?> col-sm-6 col-lg-4 col-xl-3 masonry-item">
                                <a href="<?php echo esc_url($link); ?>"
                                   class="adt-location-box">
                                    <div class="location-img-box <?php echo esc_attr($large_class); ?>">
                                        <img src="<?php echo esc_url($location['location_image']['url']); ?>"
                                             alt="<?php echo esc_attr($location_name); ?>">
                                    </div>
                                    <div class="location-meta-box">
                                        <span class="location-name"><?php echo esc_html($location_name); ?></span>
                                        <span class="ads-count"><?php echo esc_html($ad_count . ' ads'); ?></span>
                                    </div>
                                </a>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p>' . __("No locations available", "adforest-revamp") . '</p>';
                    }
                    ?>
                </div>
                <?php if ($section_style == '2') { ?>
                    <div class="d-flex justify-content-center">
                        <a href="<?php echo esc_url($main_sec_btn_link); ?>"
                           class="<?php echo $button_style_class; ?>"><?php echo esc_html($main_sec_btn_text); ?></a>
                    </div>
                <?php } ?>
            </div>
        </section>
        <!-- adt-popular-location-section-end -->
        <?php
    }
}
// ============= ShortCode for Locations Modern ============= //

// ============= ShortCode for Our Articles ============= //
if (!function_exists('our_articles_shortcode')) {
    function our_articles_shortcode($params)
    {
        $section_title = isset($params['main_sec_title']) ? $params['main_sec_title'] : "";
        $main_sec_description = isset($params['main_sec_description']) ? $params['main_sec_description'] : "";
        $number_of_posts = isset($params['number_of_posts']) ? $params['number_of_posts'] : "";
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => $number_of_posts ?? 8,
            'post_status' => 'publish',
        );

        $the_query = new WP_Query($args);
        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_column = $sb_2column ? 'col-6' : '';
        ?>
        <section class="adt-home-blog-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="col-lg-12">
                            <div class="top-heading-box">
                                <h2><?php echo esc_html($section_title); ?></h2>
                                <p><?php echo esc_html($main_sec_description); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php
                    if ($the_query->have_posts()) :
                        while ($the_query->have_posts()) : $the_query->the_post();
                            $post_id = get_the_ID();
                            $post_title = get_the_title();
                            $post_url = get_permalink();
                            $post_date = get_the_date('F j, Y');
                            $post_image_url = get_the_post_thumbnail_url($post_id, 'medium');
                            if (!$post_image_url) {
                                $post_image_url = plugin_dir_url(__FILE__) . 'assets/images/no-image.jpg';
                            }
                            ?>
                            <div class="<?php echo esc_attr($mobile_two_column) ?> col-sm-6 col-lg-4 col-xl-3">
                                <div class="adt-blog-card">
                                    <div class="blog-img-box">
                                        <a href="<?php echo esc_url($post_url); ?>">
                                            <img src="<?php echo esc_url($post_image_url); ?>"
                                                 alt="<?php echo esc_attr($post_title); ?>">
                                        </a>
                                    </div>
                                    <div class="blog-meta-box">
                                        <div class="date"><i
                                                    class="far fa-calendar-alt"></i><?php echo esc_html($post_date); ?>
                                        </div>
                                        <a href="<?php echo esc_url($post_url); ?>">
                                            <h5><?php echo esc_html($post_title); ?></h5>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p>' . __("No posts found", "adforest-elementor") . '</p>';
                    endif;
                    ?>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Our Articles ============= //

// ============= ShortCode for Car Dealer Hero ============= //
if (!function_exists('adforest_car_dealer_hero_shortcode')) {
    function adforest_car_dealer_hero_shortcode($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";
        $classified_search_ad_types = isset($params['classified_search_ad_types']) ? $params['classified_search_ad_types'] : "";
        $ad_post_taxonomy = isset($params['ad_post_taxonomy']) ? $params['ad_post_taxonomy'] : "";
        $search_title_field_label = isset($params['search_title_field_label']) ? $params['search_title_field_label'] : "";
        $search_title_field_switch = isset($params['search_title_field_switch']) ? $params['search_title_field_switch'] : "";
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $make_terms = [];
        if (!empty($ad_post_taxonomy)) {
            $make_terms = get_terms(array(
                'taxonomy' => $ad_post_taxonomy,
                'parent' => 0,
                'hide_empty' => false,
            ));
        }
        ?>
        <!-- adt-car-dealer-hero-start -->
        <section class="adt-car-dealer-hero">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="content-box">
                            <span class="sub-title"><?php echo esc_html($section_title); ?></span>
                            <h1><?php echo esc_html($section_tagline); ?></h1>
                            <p><?php echo esc_html($section_description); ?></p>
                            <div class="adt-hero-search-tabs">
                                <nav>
                                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                        <?php foreach ($classified_search_ad_types as $index => $ad_type):
                                            $term = get_term($ad_type['classified_ad_type']);
                                            if ($term) :
                                                $active_class = $index === 0 ? 'active' : ''; ?>
                                                <button class="classified-nav-link nav-link <?php echo $active_class; ?>"
                                                        id="nav-<?php echo $term->term_id; ?>"
                                                        data-ad-type="<?php echo esc_attr($term->name); ?>">
                                                    <?php echo esc_html($term->name); ?>
                                                </button>
                                            <?php endif;
                                        endforeach; ?>
                                    </div>
                                </nav>
                                <div class="tab-content" id="nav-tabContent">
                                    <form id="car-dealer-hero-form" method="GET"
                                          action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                        <div class="search-filters-bar">
                                            <?php if (isset($search_title_field_switch) && $search_title_field_switch == 'yes') { ?>
                                                <div class="filter-box">
                                                    <label for="ad_title"><?php echo esc_html($search_title_field_label); ?></label>
                                                    <input type="text" name="ad_title" id="ad_title"
                                                           placeholder="<?php echo esc_attr__('Enter Keywords', 'adforest-elementor'); ?>">
                                                </div>
                                            <?php } ?>

                                            <!-- Make Select -->
                                            <div class="filter-box type-box">
                                                <label for="make"><?php echo esc_html__('Make', 'adforest-elementor'); ?></label>
                                                <select class="default-select" id="make">
                                                    <option value=""><?php echo __("Select Make", 'adforest-elementor'); ?></option>
                                                    <?php
                                                    if (!empty($make_terms) && !is_wp_error($make_terms)) {
                                                        foreach ($make_terms as $term) { ?>
                                                            <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
                                                        <?php }
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- Model Select -->
                                            <div class="filter-box type-box">
                                                <label for="model"><?php echo esc_html__('Model', 'adforest-elementor'); ?></label>
                                                <select class="default-select" id="model" disabled>
                                                    <option value=""><?php echo __("Select Model", 'adforest-elementor'); ?></option>
                                                </select>
                                            </div>

                                            <!-- Variant Select -->
                                            <div class="filter-box type-box">
                                                <label for="variant"><?php echo esc_html__('Variant', 'adforest-elementor'); ?></label>
                                                <select class="default-select" id="variant" disabled>
                                                    <option value=""><?php echo __("Select Variant", 'adforest-elementor'); ?></option>
                                                </select>
                                            </div>

                                            <button class="adt-button-dark-1 search-button" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>

                                        <!-- Hidden fields -->
                                        <input type="hidden" name="cat_id" id="cat_id_hidden" value="">
                                        <input type="hidden" name="ad_type" id="selected-ad-type" value="">

                                        <!-- Data for AJAX -->
                                        <div id="taxonomy-data"
                                             data-taxonomy="<?php echo esc_attr($ad_post_taxonomy); ?>"
                                             data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                                             data-select-model-text="<?php echo esc_attr__('Select Model', 'adforest-elementor'); ?>"
                                             data-select-variant-text="<?php echo esc_attr__('Select Variant', 'adforest-elementor'); ?>">
                                        </div>
                                    </form>

                                    <script type="text/javascript">
                                        jQuery(document).ready(function ($) {

                                            // Set the default selected ad_type (first one)
                                            var firstTab = $('.classified-nav-link').first();
                                            $('#selected-ad-type').val(firstTab.data('ad-type'));

                                            // Handle tab clicks
                                            $('.classified-nav-link').on('click', function (e) {
                                                e.preventDefault();
                                                $('.classified-nav-link').removeClass('active');
                                                $(this).addClass('active');
                                                $('#selected-ad-type').val($(this).data('ad-type'));
                                            });

                                            // Make -> Model AJAX
                                            $('#make').change(function () {
                                                var parent_id = $(this).val();
                                                var taxonomy = '<?php echo esc_js($ad_post_taxonomy); ?>';
                                                if (parent_id) {
                                                    $.ajax({
                                                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                                        type: 'POST',
                                                        dataType: 'json',
                                                        data: {
                                                            action: 'get_child_terms',
                                                            parent: parent_id,
                                                            taxonomy: taxonomy,
                                                            security: $('#adforest_get_child_terms_nonce').val(),
                                                        },
                                                        success: function (response) {
                                                            if (response.success) {
                                                                var options = '<option value=""><?php echo __("Select Model", "adforest-elementor"); ?></option>';
                                                                $.each(response.data, function (index, term) {
                                                                    options += '<option value="' + term.term_id + '">' + term.name + '</option>';
                                                                });
                                                                $('#model').html(options).prop('disabled', false);
                                                                $('#variant').html('<option value=""><?php echo __("Select Variant", "adforest-elementor"); ?></option>').prop('disabled', true);
                                                            }
                                                        }
                                                    });
                                                } else {
                                                    $('#model').html('<option value=""><?php echo __("Select Model", "adforest-elementor"); ?></option>').prop('disabled', true);
                                                    $('#variant').html('<option value=""><?php echo __("Select Variant", "adforest-elementor"); ?></option>').prop('disabled', true);
                                                }
                                            });

                                            // Model -> Variant AJAX
                                            $('#model').change(function () {
                                                var parent_id = $(this).val();
                                                var taxonomy = '<?php echo esc_js($ad_post_taxonomy); ?>';
                                                if (parent_id) {
                                                    $.ajax({
                                                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                                        type: 'POST',
                                                        dataType: 'json',
                                                        data: {
                                                            action: 'get_child_terms',
                                                            parent: parent_id,
                                                            taxonomy: taxonomy,
                                                            security: $('#adforest_get_child_terms_nonce').val(),
                                                        },
                                                        success: function (response) {
                                                            if (response.success) {
                                                                var options = '<option value=""><?php echo __("Select Variant", "adforest-elementor"); ?></option>';
                                                                $.each(response.data, function (index, term) {
                                                                    options += '<option value="' + term.term_id + '">' + term.name + '</option>';
                                                                });
                                                                $('#variant').html(options).prop('disabled', false);
                                                            }
                                                        }
                                                    });
                                                } else {
                                                    $('#variant').html('<option value=""><?php echo __("Select Variant", "adforest-elementor"); ?></option>').prop('disabled', true);
                                                }
                                            });

                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img class="car-dealer-main-img" src="<?php echo esc_url($bg_img); ?>" alt="car-dealer-img">
        </section>
        <!-- adt-car-dealer-hero-end -->
        <?php
    }
}
// ============= ShortCode for Car Dealer Hero ============= //

// ============= ShortCode for Category Carousel ============= //
if (!function_exists('category_carousel_shortcode')) {
    function category_carousel_shortcode($params)
    {
        global $adforest_theme;
        $title = isset($params['title']) ? $params['title'] : "";
        $categories = isset($params['carousel_categories_repeater']) ? $params['carousel_categories_repeater'] : [];
        $cat_link_page = 'search';

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = $sb_2column ? 2 : 1;
        ?>
        <section class="adt-car-explore-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-ads-top-box">
                            <h2><?php echo esc_html($title); ?></h2>
                        </div>
                        <div class="adt-car-category-carousel owl-carousel owl-theme"
                             data-mobile-columns="<?php echo esc_attr($mobile_two_columns) ?>">
                            <?php
                            foreach ($categories as $category) {
                                $category_id = $category['carousel_category'];
                                $category_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_type');
                                $category_link = '';
                                if (!empty($category_details['id']) && function_exists('adforest_cat_link_page')) {
                                    $category_link = esc_url(adforest_cat_link_page($category_details['name'], $cat_link_page, 'ad_type'));
                                }

                                $category_name = isset($category_details['name']) ? sanitize_text_field($category_details['name']) : '';

                                $category_count = isset($category_details['ad_count']) ? intval($category_details['ad_count']) : 0;

                                $category_image_url = '';
                                if (isset($category['carousel_category_image']) && is_array($category['carousel_category_image']) && !empty($category['carousel_category_image']['url'])) {
                                    $category_image_url = esc_url($category['carousel_category_image']['url']);
                                }

                                ?>
                                <div class="item">
                                    <a href="<?php echo esc_url($category_link); ?>">
                                        <div class="adt-car-category-box">
                                            <div class="img-box">
                                                <img class="img-fluid"
                                                     src="<?php echo esc_url($category_image_url); ?>"
                                                     alt="<?php echo $category_name; ?>">
                                            </div>
                                            <span><?php echo $category_name; ?></span>
                                            <small><?php echo $category_count . __(" Ads", "adforest-elementor"); ?></small>
                                        </div>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Category Carousel ============= //

// ============= ShortCode for Adforest Ad Grd Fancy ============= //
if (!function_exists('adforest_ad_grid_carousel_fancy_shortcode')) {
    function adforest_ad_grid_carousel_fancy_shortcode($params)
    {
        global $adforest_theme;
        $title = isset($params['title']) ? $params['title'] : "";
        $desc = isset($params['desc']) ? $params['desc'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";
        $ad_select = isset($params['ad_select']) ? $params['ad_select'] : "";
        $btn_title = isset($params['btn_title']) ? $params['btn_title'] : "";
        $btn_link = isset($params['btn_link']) ? $params['btn_link'] : "";
        $ad_title_limit = isset($params['ad_title_limit']) ? $params['ad_title_limit'] : "";
        $ad_cats_select = isset($params['ad_cats_select']) ? $params['ad_cats_select'] : "";
        $no_of_ads = isset($params['no_of_ads']) ? $params['no_of_ads'] : "";
        $carousel_grid_columns = isset($params['carousel_grid_columns']) ? $params['carousel_grid_columns'] : "";
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $link = '';
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }

        $args = [
            'post_type' => 'ad_post',
            'posts_per_page' => $no_of_ads,
            'post_status' => 'publish',
        ];

        if (!empty($ad_cats_select)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'ad_cats',
                    'field' => 'term_id',
                    'terms' => $ad_cats_select,
                    'operator' => 'IN',
                ],
            ];
        }

        switch ($ad_select) {
            case 'simple':
                $args['meta_query'] = [
                    'relation' => 'OR',
                    ['key' => '_adforest_is_feature', 'value' => '0', 'compare' => '='],
                    ['key' => '_adforest_is_feature', 'compare' => 'NOT EXISTS'],
                ];
                break;

            case 'featured':
                $args['meta_query'] = [
                    ['key' => '_adforest_is_feature', 'value' => '1', 'compare' => '='],
                ];
                break;

            case 'both':
                $args['meta_query'] = [
                    'relation' => 'OR',
                    ['key' => '_adforest_is_feature', 'value' => '1', 'compare' => '='],
                    ['key' => '_adforest_is_feature', 'value' => '0', 'compare' => '='],
                    ['key' => '_adforest_is_feature', 'compare' => 'NOT EXISTS'],
                ];
                break;
        }

        $ads_query = new WP_Query($args);

        $style = "";
        if ($bg_img !== "") {
            $style = 'style="background-image:url(' . esc_url($bg_img) . ') !important;"';
        }

        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = $sb_2column ? 2 : 1;
        if ($ads_query->have_posts()) {
            ?>
            <section class="adt-car-explore-section adt-trending-cars-section" <?php echo $style; ?>>
                <div class="container <?php echo $adt_container_class; ?>">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="adt-ads-top-box">
                                <h2><?php echo esc_html($title); ?></h2>
                                <a href="<?php echo esc_url($btn_link); ?>">
                                    <button class="adt-button-1"><?php echo esc_html($btn_title); ?></button>
                                </a>
                            </div>
                            <?php if (isset($desc) && !empty($desc)) { ?>
                                <p><?php echo esc_html($desc); ?></p>
                            <?php } ?>
                            <div class="adt-property-ads-carousel-widgets owl-carousel owl-theme"
                                 data-columns="<?php echo esc_attr($carousel_grid_columns); ?>"
                                 data-mobile-columns="<?php echo esc_attr($mobile_two_columns); ?>">
                                <?php
                                while ($ads_query->have_posts()) {
                                    $ads_query->the_post();
                                    $ad_details = get_ad_post_details(get_the_ID());
                                    $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);

                                    $truncated_location = $ad_details['truncated_location'];
                                    $ad_title = truncate_string($ad_details['ad_title'], $ad_title_limit);
                                    $price_html = $ad_details['price_html'];
                                    $ad_permalink = $ad_details['ad_link'];
                                    $heart_class = $ad_details['heart_class'];
                                    $is_featured = $ad_details['is_featured'];
                                    $all_ad_images = $ad_details['all_ad_images'];
                                    $ad_poster_img = $ad_details['ad_poster_img'];
                                    $ad_poster_name = $ad_details['ad_poster_name'];

                                    $featured_tag = $is_featured ? '<img src="' . plugin_dir_url(__FILE__) . 'assets/images/featured-tag.png' . '" alt="featured-tag" class="featured-tag">' : '';

                                    ?>
                                    <div class="item">
                                        <?php echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location); ?>
                                    </div>
                                    <?php
                                }
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
        }
    }
}
// ============= ShortCode for Adforest Ad Grd Fancy ============= //

// ============= ShortCode for About Us Modern ============= //
if (!function_exists('about_us_modern_short_base')) {
    function about_us_modern_short_base($params)
    {
        $title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $img_1 = isset($params['img_1']) ? $params['img_1'] : "";
        $exp_head = isset($params['exp_head']) ? $params['exp_head'] : "";
        $exp_desc = isset($params['exp_desc']) ? $params['exp_desc'] : "";
        $video_link = isset($params['video_link']) ? $params['video_link'] : "";
        $features = isset($params['features']) ? $params['features'] : "";
        $btn_text = isset($params['btn_text']) ? $params['btn_text'] : "";
        $btn_link = isset($params['btn_link']) ? $params['btn_link'] : "";
        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-about-experience-section-start -->
        <section class="adt-about-experience-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="left-img-box">
                            <img src="<?php echo esc_url($img_1['url']); ?>"
                                 alt="adt-experience-img">
                            <div class="experience-box">
                                <strong><?php echo esc_html($exp_head); ?></strong>
                                <span><?php echo esc_html($exp_desc); ?></span>
                            </div>
                            <a href="javascript:void(0);" class="play-btn" data-bs-toggle="modal"
                               data-bs-target="#videoModal">
                                <i class="fa fa-play"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="content-box">
                            <span><?php echo esc_html($title); ?></span>
                            <h2><?php echo esc_html($section_tagline); ?></h2>
                            <p><?php echo esc_html($section_description); ?></p>
                            <ul>
                                <?php if (is_array($features)) {
                                    foreach ($features as $feature) {
                                        ?>
                                        <li>
                                            <i class="fas fa-check-double"></i><?php echo esc_html(isset($feature['title']) ? $feature['title'] : ""); ?>
                                        </li>
                                    <?php }
                                } ?>
                            </ul>
                            <a href="<?php echo esc_url($btn_link); ?>"
                               class="adt-button-dark-1"><?php echo esc_html($btn_text); ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content bg-dark">
                        <div class="modal-body p-0 position-relative">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                                    data-bs-dismiss="modal" aria-label="Close"></button>
                            <div class="ratio ratio-16x9">
                                <iframe id="youtubeVideo" src="" title="YouTube video" allowfullscreen
                                        allow="autoplay"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var videoModal = document.getElementById('videoModal');
                    var videoIframe = document.getElementById('youtubeVideo');
                    var playBtn = document.querySelector('.play-btn');
                    var videoURL = "<?php echo esc_url($video_link); ?>";

                    videoModal.addEventListener('show.bs.modal', function () {
                        videoIframe.src = videoURL + "?autoplay=1";
                    });

                    videoModal.addEventListener('hide.bs.modal', function () {
                        videoIframe.src = "";
                    });
                });
            </script>
        </section>
        <!-- adt-about-experience-section-end -->
        <?php
    }
}
// ============= ShortCode for About Us Modern ============= //

// ============= ShortCode for Brand Carousel ============= //
if (!function_exists('brands_carousel_short_base')) {
    function brands_carousel_short_base($params)
    {
        $title = isset($params['title']) ? $params['title'] : "";
        $carousel_categories_repeater = isset($params['carousel_categories_repeater']) ? $params['carousel_categories_repeater'] : "";
        global $adforest_theme;
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = $sb_2column ? 2 : 1;
        ?>
        <!-- adt-car-explore-section-start -->
        <section class="adt-car-explore-section adt-car-logo-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-ads-top-box">
                            <h2><?php echo esc_html($title); ?></h2>
                        </div>
                        <div class="adt-car-category-carousel owl-carousel owl-theme"
                             data-mobile-columns="<?php echo esc_attr($mobile_two_columns) ?>">
                            <?php if (is_array($carousel_categories_repeater)) {
                                foreach ($carousel_categories_repeater as $category) {
                                    $category_id = $category['carousel_category'];
                                    $category_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');

                                    $category_link = '';
                                    if (isset($category_details['id']) && function_exists('adforest_cat_link_page')) {
                                        $category_link = esc_url(adforest_cat_link_page($category_details['id'], $cat_link_page, 'cat_id'));
                                    }

                                    $category_name = isset($category_details['name']) ? sanitize_text_field($category_details['name']) : '';

                                    $category_image_url = '';
                                    if (
                                        isset($category['carousel_category_image']) &&
                                        is_array($category['carousel_category_image']) &&
                                        !empty($category['carousel_category_image']['url'])
                                    ) {
                                        $category_image_url = esc_url($category['carousel_category_image']['url']);
                                    }

                                    ?>
                                    <div class="item">
                                        <a href="<?php echo esc_url($category_link) ?>">
                                            <div class="adt-car-category-box">
                                                <div class="img-box">
                                                    <img class="img-fluid"
                                                         src="<?php echo esc_url($category_image_url); ?>"
                                                         alt="car-logo">
                                                </div>
                                                <span><?php echo esc_html($category_name); ?></span>
                                            </div>
                                        </a>
                                    </div>
                                <?php }
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-car-explore-section-end -->
        <?php
    }
}
// ============= ShortCode for Brand Carousel ============= //

// ============= ShortCode for Adt Call to Action ============= //
if (!function_exists('adt_call_to_action_shortcode')) {
    function adt_call_to_action_shortcode($params)
    {
        $right_sec_bg = isset($params['right_sec_bg']) ? $params['right_sec_bg'] : "";
        $right_img = isset($params['right_img']['url']) ? $params['right_img']['url'] : "";
        $right_title = isset($params['right_title']) ? $params['right_title'] : "";
        $right_description = isset($params['right_description']) ? $params['right_description'] : "";
        $right_btn_text = isset($params['right_btn_text']) ? $params['right_btn_text'] : "";
        $right_btn_link = isset($params['right_btn_link']) ? $params['right_btn_link'] : "";
        $left_sec_bg = isset($params['left_sec_bg']) ? $params['left_sec_bg'] : "";
        $left_img = isset($params['left_img']['url']) ? $params['left_img']['url'] : "";
        $left_title = isset($params['left_title']) ? $params['left_title'] : "";
        $left_description = isset($params['left_description']) ? $params['left_description'] : "";
        $left_btn_text = isset($params['left_btn_text']) ? $params['left_btn_text'] : "";
        $left_btn_link = isset($params['left_btn_link']) ? $params['left_btn_link'] : "";
        $style_right = 'style="background-color: ' . $right_sec_bg . '; background-image: url(' . $right_img . ')"';
        $style_left = 'style="background-color: ' . $left_sec_bg . '; background-image: url(' . $left_img . ')"';
        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-car-explore-section-start -->
        <section class="adt-car-description-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="adt-car-advertise-card adv-primary" <?php echo $style_right; ?> >
                            <h3><?php echo esc_html($right_title); ?></h3>
                            <p><?php echo esc_html($right_description); ?></p>
                            <a style="margin-top: 10px;"
                               href="<?php echo esc_url($right_btn_link); ?>"
                               class="adt-button-dark-1"><?php echo esc_html($right_btn_text); ?></a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="adt-car-advertise-card adv-secondary" <?php echo $style_left; ?> >
                            <h3><?php echo esc_html($left_title); ?></h3>
                            <p><?php echo esc_html($left_description); ?></p>
                            <a style="margin-top: 10px;"
                               href="<?php echo esc_url($left_btn_link); ?>"
                               class="adt-button-dark-1"><?php echo esc_html($left_btn_text); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-car-explore-section-end -->
        <?php
    }
}
// ============= ShortCode for Adt Call to Action ============= //

// ============= ShortCode for Adt List Modern ============= //
if (!function_exists('ads_list_modern_shortcode')) {
    function ads_list_modern_shortcode($params)
    {
        global $adforest_theme;
        $title = isset($params['title']) ? $params['title'] : "";
        $description = isset($params['description']) ? $params['description'] : "";
        $number_of_ads = isset($params['number_of_ads']) ? $params['number_of_ads'] : "";
        $type_of_ads = isset($params['type_of_ads']) ? $params['type_of_ads'] : "";
//        $site_currency = isset($adforest_theme['sb_currency']) && !empty($adforest_theme['sb_currency']) ? $adforest_theme['sb_currency'] : get_woocommerce_currency_symbol();
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);

        $args = [
            'post_type' => 'ad_post',
            'posts_per_page' => $number_of_ads,
            'post_status' => 'publish',
        ];

        if ($type_of_ads == 'simple') {
            $args['meta_key'] = '_adforest_is_feature';
            $args['meta_value'] = '0';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '');
        } elseif ($type_of_ads == 'featured') {
            $args['meta_key'] = '_adforest_is_feature';
            $args['meta_value'] = '1';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '1');
        } elseif ($type_of_ads == 'both') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_adforest_is_feature',
                    'value' => '1',
                    'compare' => '='
                ],
                [
                    'key' => '_adforest_is_feature',
                    'compare' => 'NOT EXISTS'
                ]
            ];
            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', 'both');
        }

        $query = new WP_Query($args);
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section class="adt-car-dealer-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="col-lg-12">
                    <div class="top-heading-box">
                        <h2><?php echo esc_html($title); ?></h2>
                        <p><?php echo esc_html($description); ?></p>
                    </div>
                    <?php
                    if ($query->have_posts()) { ?>
                        <?php while ($query->have_posts()) : $query->the_post();
                            $ad_details = get_ad_post_details(get_the_ID());
                            $location = $ad_details['truncated_location'];
                            $ad_title = $ad_details['ad_title'];
                            $price_html = $ad_details['price_html'];
                            $ad_price = get_post_meta(get_the_ID(), '_adforest_ad_price', true);
                            $site_currency = get_post_meta(get_the_ID(), '_adforest_ad_currency', true) ?: $adforest_theme['sb_currency'];
                            if (empty($site_currency)) {
                                if (function_exists('get_woocommerce_currency_symbol')) {
                                    $site_currency = get_woocommerce_currency_symbol();
                                }
                            }
                            $formatted_price = 0;
                            if (isset($ad_price)) {
                                if (!empty($ad_price)) {
                                    $formatted_price = number_format((float)$ad_price, 0, '.', ',');
                                }
                            }
                            $ad_price_type = get_post_meta(get_the_ID(), '_adforest_ad_price_type', true);
                            $ad_permalink = $ad_details['ad_link'];
                            $heart_class = $ad_details['heart_class'];
                            $all_ad_images = $ad_details['all_ad_images'];
                            $ad_poster_img = $ad_details['ad_poster_img'];
                            $ad_poster_name = $ad_details['ad_poster_name'];
                            $content = get_the_content();
                            $clean_content = wp_strip_all_tags($content);
                            $truncated_content = truncate_string($clean_content, 120);

                            if (function_exists('adforest_comments_pagination2')) {
                                $page = (isset($_GET['page-number'])) ? absint($_GET['page-number']) : 1;
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
                                'post_id' => get_the_ID(),
                            );

                            $comments = get_comments($args);
                            $get_percentage = adforest_fetch_reviews_average(get_the_ID());
                            ?>
                            <div class="adt-car-dealer-card">
                                <div class="adt-car-ad-carousel owl-carousel owl-theme">
                                    <?php foreach ($all_ad_images as $image_url) { ?>
                                        <div class="item"><img src="<?php echo esc_url($image_url); ?>"
                                                               alt="featured-car-img">
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
                                            <span class="reviews"><?php echo __(count($comments) . " Reviews", 'adforest-elementor'); ?></span>
                                        </div>
                                        <a href="<?php echo esc_url($ad_permalink); ?>">
                                            <h3><?php echo esc_html($ad_title); ?></h3></a>
                                        <ul>
                                            <li>
                                                <i class="fas fa-location-arrow"></i><?php echo esc_html($location); ?>
                                            </li>
                                            <li><i class="far fa-calendar-alt"></i><?php echo get_the_date(); ?></li>
                                            <li>
                                                <img src="<?php echo esc_url($ad_poster_img); ?>"
                                                     alt="author"><?php echo esc_html($ad_poster_name); ?>
                                            </li>
                                        </ul>
                                        <p><?php echo esc_html($truncated_content); ?></p>
                                    </div>
                                    <div class="adt-car-price-meta">
                                        <div class="price-box">
                                            <?php
                                            if (isset($ad_price_type) && $ad_price_type == 'on_call') {
                                                echo '<span>' . __("Price on Call", "adforest-elementor") . '</span>';
                                            } elseif (isset($ad_price_type) && $ad_price_type == 'free') {
                                                echo '<span>' . __("Free", "adforest-elementor") . '</span>';
                                            } else {
                                                if (isset($formatted_price)) { ?>
                                                    <span><?php echo $site_currency . $formatted_price; ?></span>
                                                    <?php
                                                }
                                                if (isset($ad_price_type) && !empty($ad_price_type)) { ?>
                                                    <small>(<?php echo esc_html($ad_price_type); ?>)</small>
                                                <?php }
                                            } ?>
                                        </div>
                                        <div class="detail-btn-box">
                                            <span class="favorite ad_to_fav" data-adid="<?php echo get_the_ID(); ?>"><i
                                                        class="<?php echo $heart_class; ?>"></i></span>
                                            <a href="<?php echo esc_url($ad_permalink); ?>"
                                               class="adt-button-dark-1"><?php echo __("Details", "adforest-elementor"); ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php } ?>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Adt List Modern ============= //

// ============= ShortCode for Adt Info ============= //
if (!function_exists('adt_info_shortcode')) {
    function adt_info_shortcode($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $section_btn_link = isset($params['section_btn_link']) ? $params['section_btn_link'] : "";
        $section_btn_text = isset($params['section_btn_text']) ? $params['section_btn_text'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";

        $style = '';
        if (isset($bg_img)) {
            $style = 'style="background-image: url(' . esc_url($bg_img) . ')"';
        }
        global $adforest_theme;

        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        ?>
        <section class="adt-vehicles-search-section" <?php echo $style; ?> >
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <h2><?php echo wp_kses_post($section_title); ?></h2>
                        <p><?php echo esc_html($section_description); ?></p>
                        <a href="<?php echo esc_html($section_btn_link); ?>"
                           class="adt-button-dark-1"
                           style="margin-top: 20px"><?php echo esc_html($section_btn_text); ?></a>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Adt Info ============= //

// ============= ShortCode for Classic Home ============= //
if (!function_exists('classic_home_widget_base_shortcode')) {
    function classic_home_widget_base_shortcode($params)
    {
        global $adforest_theme;
        $filter_text = isset($params['filter_text']) ? $params['filter_text'] : "";
        $items = isset($params['items']) ? $params['items'] : "";
        $mini_categories_repeater = isset($params['mini_categories_repeater']) ? $params['mini_categories_repeater'] : [];
        $ads_title_limit = isset($params['ads_title_limit']) ? $params['ads_title_limit'] : [];
        $section_grid_style = isset($params['section_grid_style']) ? $params['section_grid_style'] : [];
        $number_of_ads = isset($params['number_of_ads']) ? $params['number_of_ads'] : [];
        $section_grid_cols = isset($params['section_grid_cols']) ? $params['section_grid_cols'] : 4;
        $current_query = $_GET;
        $loading_ads_mode = isset($adforest_theme['loading_ads_mode']) ? $adforest_theme['loading_ads_mode'] : '';
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';

        if (get_query_var('paged')) {
            $paged = get_query_var('paged');
        } elseif (get_query_var('page')) {
            $paged = get_query_var('page');
        } else {
            $paged = 1;
        }

        $args = array(
            'post_type' => 'ad_post',
            'post_status' => 'publish',
            'posts_per_page' => $number_of_ads,
            'paged' => $paged,
        );

        if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
            $args['meta_query'][] = array(
                'key' => 'price',
                'value' => array(absint($_GET['min_price']), absint($_GET['max_price'])),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }

        if (isset($_GET['warranty']) && !empty($_GET['warranty'])) {
            $args['meta_query'][] = array(
                'key' => '_adforest_ad_warranty',
                'value' => sanitize_text_field($_GET['warranty']),
                'compare' => '=',
            );
        }

        if (isset($_GET['condition']) && !empty($_GET['condition'])) {
            $args['meta_query'][] = array(
                'key' => '_adforest_ad_condition',
                'value' => sanitize_text_field($_GET['condition']),
                'compare' => '=',
            );
        }

        if (isset($_GET['cat_id']) && !empty($_GET['cat_id'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'ad_cats',
                'field' => 'term_id',
                'terms' => absint($_GET['cat_id']),
            );
        }

        if (isset($_GET['title']) && !empty($_GET['title'])) {
            $args['s'] = sanitize_text_field($_GET['title']);
        }

        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $args['meta_query'][] = array(
                'key' => '_adforest_ad_type',
                'value' => sanitize_text_field($_GET['type']),
                'compare' => '=',
            );
        }

        if (isset($_GET['location']) && !empty($_GET['location'])) {
            $args['meta_query'][] = array(
                'key' => '_adforest_ad_location',
                'value' => sanitize_text_field($_GET['location']),
                'compare' => '=',
            );
        }

        $the_query = new WP_Query($args);
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == false) ? "one-column-mobile-layout" : "";
        ?>
        <!-- adt-classic-ads-section-start -->
        <section class="adt-classic-ads-section">
            <div class="container adt-container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="all-filters-sidebar adt-ads-filter-sidebar">
                            <i class="fas fa-times close-sidebar"></i>

                            <?php foreach ($items as $item) : ?>
                                <?php if ($item['field_type'] === 'ad_categories' && !empty($item['ad_categories_title'])) :
                                    $enable_show_more_cats = !empty($item['enable_show_more_on_cats']) ? $item['enable_show_more_on_cats'] : 0;
                                    $no_of_cats_before_show_more = !empty($item['no_of_cats_before_show_more']) ? $item['no_of_cats_before_show_more'] : 0;
                                    ?>
                                    <!-- Condition Filter -->
                                    <h3><?php echo esc_html($item['ad_categories_title']); ?></h3>
                                    <div class="adt-switch-btns-box">
                                        <form method="get" id="search_cats_w">
                                            <?php
                                            $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
                                            if (count($ad_categories) > 0) {
                                                if ($enable_show_more_cats == "yes" && count($ad_categories) > $no_of_cats_before_show_more) {
                                                    $first_categories = array_slice($ad_categories, 0, $no_of_cats_before_show_more);
                                                    $rest_categories = array_slice($ad_categories, $no_of_cats_before_show_more);
                                                } else {
                                                    $first_categories = $ad_categories;
                                                    $rest_categories = [];
                                                }
                                                ?>
                                                <div class="adt-category-list-sidebar" style="padding: 0">
                                                    <?php
                                                    if (isset($_GET['cat_id']) && $_GET['cat_id'] != "") {
                                                        $selected_cats = adforest_get_taxonomy_parents(absint($_GET['cat_id']), 'ad_cats', false);
                                                        $find = '&raquo;';
                                                        $replace = '';
                                                        $selected_cats = preg_replace("/$find/", $replace, $selected_cats, 1);
                                                        echo "<span>" . adforest_return_echo($selected_cats) . "</span>";
                                                    }
                                                    ?>
                                                    <ul>
                                                        <!-- Display first set of categories -->
                                                        <?php foreach ($first_categories as $category) {
                                                            $category_details = get_taxonomy_details($category);
                                                            $name = $category_details['name'] ?? '';
                                                            $ad_count = $category_details['ad_count'] ?? '';
                                                            $image = $category_details['image'] ?? '';
                                                            $link = $category_details['link'] ?? '';
                                                            $category_search_page = apply_filters('adforest_filter_taxonomy_popup_actions', 'javascript:void(0);', $category->term_id, 'ad_cats');
                                                            ?>
                                                            <li>
                                                                <div class="adt-category-box">
                                                                    <div class="category-meta">
                                                                        <a href="<?php echo esc_url($category_search_page); ?>"
                                                                           class="img-box category_click_link"
                                                                           data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                                            <img class="img-fluid"
                                                                                 src="<?php echo esc_url($image); ?>"
                                                                                 alt="category">
                                                                        </a>
                                                                        <a href="<?php echo esc_url($category_search_page); ?>"
                                                                           class="category_click_link"
                                                                           data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                                            <?php echo esc_html($name); ?>
                                                                        </a>
                                                                    </div>
                                                                    <span class="listing-count"><?php echo esc_html($ad_count . " ads"); ?></span>
                                                                </div>
                                                            </li>
                                                        <?php } ?>

                                                        <!-- If there are extra categories, add Show More / Show Less links -->
                                                        <?php if (!empty($rest_categories)) { ?>
                                                            <?php foreach ($rest_categories as $category) {
                                                                $category_details = get_taxonomy_details($category);
                                                                $name = $category_details['name'] ?? '';
                                                                $ad_count = $category_details['ad_count'] ?? '';
                                                                $image = $category_details['image'] ?? '';
                                                                $link = $category_details['link'] ?? '';
                                                                $category_search_page = apply_filters('adforest_filter_taxonomy_popup_actions', 'javascript:void(0);', $category->term_id, 'ad_cats');
                                                                ?>
                                                                <li class="hidden-category" style="display: none;">
                                                                    <div class="adt-category-box">
                                                                        <div class="category-meta">
                                                                            <a href="<?php echo esc_url($category_search_page); ?>"
                                                                               class="img-box category_click_link"
                                                                               data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                                                <img class="img-fluid"
                                                                                     src="<?php echo esc_url($image); ?>"
                                                                                     alt="category">
                                                                            </a>
                                                                            <a href="<?php echo esc_url($category_search_page); ?>"
                                                                               class="category_click_link"
                                                                               data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                                                <?php echo esc_html($name); ?>
                                                                            </a>
                                                                        </div>
                                                                        <span class="listing-count"><?php echo esc_html($ad_count . " ads"); ?></span>
                                                                    </div>
                                                                </li>
                                                            <?php } ?>
                                                            <!-- Wrapper for the toggle links -->
                                                            <li class="toggle-wrapper">
                                                                <a href="javascript:void(0);"
                                                                   id="showMoreCategories"><?php _e('Show More', 'adforest-elementor'); ?></a>
                                                                <a href="javascript:void(0);" id="showLessCategories"
                                                                   style="display: none;"><?php _e('Show Less', 'adforest-elementor'); ?></a>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                            <input type="hidden" name="cat_id" id="cat_id" value=""/>
                                            <?php echo adforest_search_params('cat_id'); ?>
                                            <?php apply_filters('adforest_form_lang_field', true); ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'ad_title' && !empty($item['ad_title_title'])) :
                                    $ad_title = isset($_GET['title']) ? sanitize_text_field($_GET['title']) : "";
                                    ?>
                                    <!-- Condition Filter -->
                                    <h3><?php echo esc_html($item['ad_title_title']); ?></h3>
                                    <div class="adt-search-list-box">
                                        <form method="get">
                                            <div class="form-field">
                                                <label for="ad_title"
                                                       class="form-label"><?php echo __("Search", 'adforest-elementor'); ?></label>
                                                <input type="text" class="form-control" id="ad_title" name="ad_title"
                                                       placeholder="<?php echo esc_attr("Search"); ?>"
                                                       value="<?php echo esc_attr($ad_title); ?>">
                                                <button type="submit" class="search-btn-title" id="searchBtnTitle">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                            <?php
                                            echo adforest_search_params('ad_title');
                                            ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'radius_search' && !empty($item['radius_search_title'])) :
                                    global $adforest_theme;
                                    $title = __('Search Filter', 'adforest-elementor');
                                    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
                                    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
                                    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
                                    $location = '';

                                    if (isset($_GET['location']) && $_GET['location'] != "") {
                                        $location = sanitize_text_field($_GET['location']);
                                    }
                                    if (isset($instance['open_widget']) && $instance['open_widget'] == '1') {
                                        $expand = 'show';
                                    }
                                    $default_radius = isset($instance['default_value']) ? $instance['default_value'] : "";

                                    $read_only = "";
                                    // $read_only = isset($instance['edit_able']) ? $instance['edit_able'] : "yes";
                                    if ($read_only == "no" && $default_radius != "") {
                                        $read_only = ' readonly="true"';

                                    }

                                    $show_hide = "";
                                    $display_style = '';
                                    $btn_search = '<button class="search-btn-title"  type="submit" id="btn-submit"><i class="fa fa-search"></i></button>';
                                    $btn_other = '<button class="crosshair_btn" type="button" id="you_current_location_text" data-place="text_field"><i class="fa fa-crosshairs"></i></button>';
                                    $btn_search1 = '';
                                    if ($show_hide == 'hide') {
                                        $display_style = 'hideSearchInput';
                                    } else {
                                        $btn_search1 = '<button class="search-btn" type="submit" id="btn-submit"><i class="fa fa-search"></i></button>';
                                        $btn_search = '';
                                    }

                                    $radius = '';
                                    $area = isset($_GET['location']) && $_GET['location'] != '' ? sanitize_text_field($_GET['location']) : '';

                                    if (isset($_GET['location']) && $_GET['location'] != "" && isset($_GET['rd']) && $_GET['rd'] != "") {
                                        $radius = absint($_GET['rd']);
                                        $area = sanitize_text_field($_GET['location']);
                                    } else {
                                        $radius = $default_radius;
                                    }
                                    $radius_placeholder = __('Radius in km', 'adforest-elementor');
                                    $search_radius_type = isset($adforest_theme['search_radius_type']) ? $adforest_theme['search_radius_type'] : 'km';
                                    if ($search_radius_type == 'mile') {
                                        $radius_placeholder = __('Radius in Miles', 'adforest-elementor');
                                    }
                                    ?>
                                    <!-- Condition Filter -->
                                    <h3><?php echo esc_html($item['radius_search_title']); ?></h3>
                                    <div class="adt-search-list-box">
                                        <form id="sb-radius-form" class="for-radius">
                                            <div class="form-field">
                                                <?php
                                                $mapType = adforest_mapType();
                                                $attr_leaflet = "";
                                                $placeHolder = __('Type Location...', 'adforest-elementor');
                                                if ($mapType == 'leafletjs_map') {
                                                    $map_lat = (isset($_GET['lat']) && $_GET['lat']) ? sanitize_text_field($_GET['lat']) : '';
                                                    $map_long = (isset($_GET['long']) && $_GET['long']) ? sanitize_text_field($_GET['long']) : '';
                                                    echo '
                                                        <input type="hidden" name="lat" id="sb_user_address_lat" value="' . esc_attr($map_lat) . '">
                                                        <input type="hidden" name="long" id="sb_user_address_long" value="' . esc_attr($map_long) . '">
                                                      ';

                                                    $attr_leaflet = ' readonly="readonly"';
                                                    $placeHolder = __('Get Location...', 'adforest-elementor');
                                                } else {
                                                    $btn_other = '<button class="crosshair_btn" type="button" id="get-location" data-place="text_field"><i class="fa fa-crosshairs"></i></button>';
                                                }
                                                $location_input_id = ($mapType == 'leafletjs_map') ? 'sb_user_address_leaflet' : 'sb_user_address';
                                                ?>
                                                <label for="radius-search"
                                                       class="form-label"><?php echo __("Radius Search", 'adforest-elementor'); ?></label>
                                                <input type="text" class="form-control"
                                                       id="<?php echo $location_input_id; ?>" name="location"
                                                       placeholder="<?php echo esc_attr($placeHolder); ?>"
                                                       value="<?php echo esc_attr($location); ?>">
                                                <div>
                                                    <?php
                                                    echo $btn_search;
                                                    echo $btn_other;
                                                    ?>
                                                </div>
                                            </div>
                                            <div id="suggestions-box" class="suggestions-box"></div>
                                            <div class="form-field">
                                                <input type="number" class="form-control <?php echo $display_style ?>"
                                                       id="rd" name="rd"
                                                       value="<?php echo esc_attr($radius); ?>"
                                                       placeholder="<?php echo esc_attr($radius_placeholder); ?>"
                                                       data-parsley-required="true"
                                                       data-parsley-error-message="" <?php echo $read_only; ?>>
                                                <?php echo $btn_search1; ?>
                                            </div>
                                            <?php echo adforest_search_params('location', 'rd', 'country_id'); ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'condition' && !empty($item['condition_title'])) : ?>
                                    <!-- Condition Filter -->
                                    <h3><?php echo esc_html($item['condition_title']); ?></h3>
                                    <div class="adt-switch-btns-box">
                                        <form id="ad_condition_form" method="get">
                                            <ul class="select-user-type">
                                                <?php
                                                $ad_condition = adforest_get_ad_taxonomy_callback('ad_condition');
                                                foreach ($ad_condition as $type) {
                                                    $ad_condition_details = get_taxonomy_details($type);
                                                    $name = $ad_condition_details['name'] ?? '';
                                                    $slug = $ad_condition_details['slug'] ?? '';
                                                    ?>
                                                    <li>
                                                        <input type="radio" name="condition"
                                                               class="submit-on-condition-change"
                                                               id="check-condition-<?php echo esc_attr($slug); ?>"
                                                               value="<?php echo esc_attr($type->name); ?>"/>
                                                        <label for="check-condition-<?php echo esc_attr($slug); ?>">
                                                            <?php echo esc_html($name); ?>
                                                        </label>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                            <?php echo adforest_search_params('condition'); ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'ad_locations' && !empty($item['ad_locations_title'])) :
                                    $ad_countries = adforest_get_ad_taxonomy_callback('ad_country');

                                    $selected_country_id = "";
                                    if (isset($_GET['country_id'])) {
                                        $selected_country_id = absint($_GET['country_id']);
                                    }
                                    ?>
                                    <!-- Condition Filter -->
                                    <h3><?php echo esc_html($item['ad_locations_title']); ?></h3>
                                    <div class="adt-switch-btns-box">
                                        <form method="get" id="search_countries">
                                            <div class="form-field">
                                                <div>
                                                    <label for="topbar_countries"
                                                           class="form-label"><?php echo esc_html__("Locations", "adforest-elementor"); ?></label>
                                                </div>
                                                <select class="default-select" id="topbar_countries">
                                                    <option value=""><?php echo __("Select an Option", 'adforest-elementor'); ?></option>
                                                    <?php
                                                    foreach ($ad_countries as $country) {
                                                        $country_details = get_taxonomy_details($country);
                                                        $name = $country_details['name'] ?? '';
                                                        $country_id = $country->term_id;
                                                        $selected = ($country_id == $selected_country_id) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php echo $country_id; ?>"
                                                                data-country-id="<?php echo $country_id; ?>" <?php echo $selected; ?>>
                                                            <?php echo esc_html($name); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <input type="hidden" name="country_id" id="country_id" value=""/>
                                            <?php echo adforest_search_params('country_id'); ?>
                                            <?php apply_filters('adforest_form_lang_field', true); ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'ad_currency' && !empty($item['ad_currency_title'])) :
                                    $title = __('Currency', 'adforest-elementor');
                                    $cur_type = '';
                                    $perm_name = (is_home() || is_front_page()) ? 'ad_currency' : 'ad_currency';
                                    if (isset($_GET["$perm_name"]) && $_GET["$perm_name"] != "") {
                                        $cur_type = sanitize_text_field($_GET["$perm_name"]);
                                    }

                                    $ad_currencys = adforest_get_ad_taxonomy_callback('ad_currency');
                                    $perm_name = (is_home() || is_front_page()) ? 'ad_currency' : 'ad_currency';
                                    ?>
                                    <!-- Condition Filter -->
                                    <h3><?php echo esc_html($item['ad_currency_title']); ?></h3>
                                    <div class="adt-switch-btns-box">
                                        <form id="ad_currency_form" method="get">
                                            <div class="adt-switch-btns-box">
                                                <ul class="adt-type-filter-box">
                                                    <?php
                                                    $ad_currencys = adforest_get_ad_taxonomy_callback('ad_currency');
                                                    $perm_name = (is_home() || is_front_page()) ? 'ad_currency' : 'ad_currency';
                                                    foreach ($ad_currencys as $currency) {
                                                        $ad_type_details = get_taxonomy_details($currency);
                                                        $currency_name = $ad_type_details['name'] ?? '';
                                                        ?>
                                                        <li>
                                                            <label class="container"><?php echo esc_html($currency_name); ?>
                                                                <input tabindex="7" type="radio"
                                                                       class="submit-on-currency-change"
                                                                       id="minimal-radio-<?php echo esc_attr($currency->term_id); ?>"
                                                                       name="<?php echo esc_attr($perm_name); ?>"
                                                                       value="<?php echo esc_attr($currency_name); ?>">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                            <?php echo adforest_search_params('ad_currency'); ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'warranty' && !empty($item['warranty_title'])) : ?>
                                    <!-- Warranty Filter -->
                                    <div class="adt-switch-btns-box">
                                        <h3><?php echo esc_html($item['warranty_title']); ?></h3>
                                        <form id="ad_warranty_form" method="get">

                                            <ul class="select-user-type">
                                                <?php
                                                $ad_warranty = adforest_get_ad_taxonomy_callback('ad_warranty');

                                                foreach ($ad_warranty as $type) {
                                                    $ad_warranty_details = get_taxonomy_details($type);
                                                    $name = $ad_warranty_details['name'] ?? '';
                                                    $slug = $ad_warranty_details['slug'] ?? '';
                                                    ?>
                                                    <li>
                                                        <input type="radio" name="warranty"
                                                               class="submit-on-warranty-change"
                                                               id="check-warranty-<?php echo esc_attr($slug); ?>"
                                                               value="<?php echo esc_attr($type->name); ?>"/>
                                                        <label for="check-warranty-<?php echo esc_attr($slug); ?>">
                                                            <?php echo esc_html($name); ?>
                                                        </label>
                                                    </li>
                                                <?php } ?>
                                            </ul>

                                            <?php echo adforest_search_params('warranty'); ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'price') :
                                    wp_enqueue_style('rangeslider-css', trailingslashit(get_template_directory_uri()) . 'assets/css/rangeslider.min.css');
                                    wp_enqueue_script('rangeslider-min');
                                    $site_currency = $adforest_theme['sb_currency'] ?? get_woocommerce_currency_symbol();

                                    wp_localize_script(
                                        'adforest-custom',
                                        'price_widget',
                                        [
                                            'min_price' => $item['min_price'],
                                            'max_price' => $item['max_price']
                                        ]
                                    );
                                    ?>
                                    <!-- Price Filter -->
                                    <div class="adt-switch-btns-box">
                                        <h3><?php echo __("Ads Price", "adforest-elementor"); ?></h3>
                                        <form action="" method="get">
                                            <div class="adt-range-slider">
                                        <span class="price-slider-value">
                                            <?php echo __("Price (" . $site_currency . ")", 'adforest-elementor'); ?>
                                            <span id="min_price"><?php echo $item['min_price']; ?></span> -
                                            <span id="max_price"><?php echo $item['max_price']; ?></span>
                                        </span>
                                                <div class="range-slider">
                                                    <input type="text" class="adt-ads-range-slider"/>
                                                </div>
                                                <div class="extra-controls">
                                                    <input type="text" class="adt-ads-input-from form-control"
                                                           value="<?php echo esc_attr($item['min_price']); ?>"
                                                           name="min_price" id="min_selected">
                                                    <div>&#9866;</div>
                                                    <input type="text" class="adt-ads-input-to form-control"
                                                           value="<?php echo esc_attr($item['max_price']); ?>"
                                                           name="max_price" id="max_selected">
                                                </div>
                                                <?php foreach ($current_query as $key => $value): ?>
                                                    <?php if ($key != 'min_price' && $key != 'max_price'): ?>
                                                        <input type="hidden" name="<?php echo esc_attr($key); ?>"
                                                               value="<?php echo esc_attr($value); ?>">
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <button class="adt-button-dark"><?php echo __("Search Now", 'adforest-elementor'); ?></button>
                                            </div>
                                            <?php echo adforest_search_params('min_price', 'max_price'); ?>
                                        </form>
                                    </div>

                                <?php elseif ($item['field_type'] === 'ad' && !empty($item['adv_image'])) : ?>
                                    <!-- Ad Image -->
                                    <div class="adt-vertical-ad-box">
                                        <?php echo wp_kses_post($item['adv_image']); ?>
                                    </div>

                                <?php endif; ?>
                            <?php endforeach; ?>

                        </div>
                        <div class="adt-category-type-carousel-box">
                            <button class="search-all-filters"><i
                                        class="fas fa-filter"></i><?php echo esc_html($filter_text); ?>
                            </button>
                            <div class="adt-category-types-carousel owl-carousel owl-theme">
                                <?php
                                $ad_cats = adforest_get_ad_taxonomy_callback('ad_cats');
                                if (is_array($ad_cats)) {
                                    foreach ($mini_categories_repeater as $cat) {
                                        $category_id = $cat['mini_category'];
                                        $cat_detail = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                        $name = isset($cat_detail['name']) ? sanitize_text_field($cat_detail['name']) : '';

                                        $image = '';
                                        if (isset($cat['category_image']) && is_array($cat['category_image']) && !empty($cat['category_image']['url'])) {
                                            $image = esc_url($cat['category_image']['url']);
                                        }

                                        $link = '';
                                        if (isset($cat_detail['id']) && function_exists('adforest_cat_link_page')) {
                                            $link = esc_url(adforest_cat_link_page($cat_detail['id'], $cat_link_page, 'cat_id'));
                                        }

                                        ?>
                                        <div class="item">
                                            <a href="<?php echo esc_url($link); ?>">
                                                <div class="category-type-box">
                                                    <img src="<?php echo esc_url($image); ?>"
                                                         alt="ctg-img">
                                                    <span><?php echo esc_html($name); ?></span>
                                                </div>
                                            </a>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                        <?php
                        if ($the_query->have_posts()) { ?>
                            <div class="adt-search-ads-grid adt-search-ads-col-<?php echo $section_grid_cols; ?> <?php echo $sb_2column ?>">
                                <?php
                                while ($the_query->have_posts()) : $the_query->the_post();
                                    $ad_details = get_ad_post_details(get_the_ID());
                                    $img = $ad_details['img'];
                                    $truncated_location = $ad_details['truncated_location'];
                                    $price_html = $ad_details['price_html'];
                                    $heart_class = $ad_details['heart_class'];
                                    $is_featured = $ad_details['is_featured'];
                                    $ad_categories_post = $ad_details['categories'];
                                    $ad_poster_img = $ad_details['ad_poster_img'];
                                    $ad_poster_name = $ad_details['ad_poster_name'];
                                    $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);
                                    $ad_permalink = $ad_details['ad_link'];
                                    $ad_title = truncate_string($ad_details['ad_title'], $ads_title_limit);
                                    $all_ad_images = $ad_details['all_ad_images'];
                                    $featured_tag = $is_featured ? '<img style="transform: rotate(180deg);" src="' . get_template_directory_uri() . '/images/featured.png' . '" alt="featured-tag" class="featured-tag">' : '';
                                    $selected_categories = $ad_details['categories'];

                                    if ($section_grid_style == '1') {
                                        echo adforest_ad_grid_1($ad_permalink, $img, $is_featured, $ad_categories_post, $ad_details, $ad_title, $truncated_location, $price_html, $heart_class);
                                    } else if ($section_grid_style == '2') {
                                        echo '<div class="adt-property-ad-card-outer">' . adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class) . '</div>';
                                    } else if ($section_grid_style == '3') {
                                        echo '<div class="adt-property-ad-card-outer">' . adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location) . '</div>';
                                    }

                                endwhile; ?>
                            </div>
                        <?php } else {
                            $nothing_found = get_template_directory_uri() . '/images/nothing-found.png';
                            echo '<div class="no_ads_found">
                                <img src="' . esc_url($nothing_found) . '" alt="">
                                <h3>' . __("No Ads found.", 'adforest-elementor') . '</h3>
                            </div>';
                        }

                        wp_reset_postdata();
                        ?>
                        <div class="m-2" id="no_more_ads_p"></div>
                        <?php
                        if ($the_query->max_num_pages > 1) {
                            if ($loading_ads_mode == 'pagination') {
                                if (get_query_var('paged')) {
                                    $paged = get_query_var('paged');
                                } elseif (get_query_var('page')) {
                                    $paged = get_query_var('page');
                                } else {
                                    $paged = 1;
                                }
                                ?>
                                <nav aria-label="Page navigation example">
                                    <ul class="pagination adt-custom-pagination">
                                        <li class="page-item <?php echo $paged <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link prv"
                                               href="<?php echo $paged <= 1 ? '#' : get_pagenum_link($paged - 1); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        <?php
                                        $max_pages = $the_query->max_num_pages;
                                        $current_page = $paged;
                                        $pages_to_show = 5;
                                        $start_page = max(1, $current_page - floor($pages_to_show / 2));
                                        $end_page = min($max_pages, $start_page + $pages_to_show - 1);

                                        if ($end_page - $start_page < $pages_to_show - 1) {
                                            $start_page = max(1, $end_page - $pages_to_show + 1);
                                        }

                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="' . get_pagenum_link(1) . '">01</a></li>';
                                            if ($start_page > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }

                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active = ($current_page == $i) ? 'active' : '';
                                            echo '<li class="page-item"><a class="page-link ' . $active . '" href="' . get_pagenum_link($i) . '">' . str_pad($i, 2, '0', STR_PAD_LEFT) . '</a></li>';
                                        }

                                        if ($end_page < $max_pages) {
                                            if ($end_page < $max_pages - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="' . get_pagenum_link($max_pages) . '">' . str_pad($max_pages, 2, '0', STR_PAD_LEFT) . '</a></li>';
                                        }
                                        ?>
                                        <li class="page-item <?php echo $paged >= $the_query->max_num_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link nxt"
                                               href="<?php echo $paged >= $the_query->max_num_pages ? '#' : get_pagenum_link($paged + 1); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php } elseif ($loading_ads_mode == 'show_more' || $loading_ads_mode == 'infinity_scroll') {
                                ?>
                                <div class="d-flex justify-content-center">
                                    <button data-search-query='<?php echo json_encode($args); ?>'
                                            data-loading-mode="<?php echo esc_attr($loading_ads_mode); ?>"
                                            data-ad-count="<?php echo $ad_count; ?>"
                                            data-posts-per-page="<?php echo get_option('posts_per_page'); ?>"
                                            data-view-type="<?php echo esc_attr(isset($_GET['view-type']) ? sanitize_text_field($_GET['view-type']) : "") ?>"
                                            class="adt-button-dark"
                                            id="load-more-ads-btn"><?php echo esc_html__('Show More', 'adforest-elementor'); ?></button>
                                </div>
                            <?php }
                        } ?>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-classic-ads-section-end -->
        <?php
    }
}
// ============= ShortCode for Classic Home ============= //

// ============= ShortCode for Directory Hero ============= //
if (!function_exists('adforest_directory_hero')) {
    function adforest_directory_hero($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $video_link = isset($params['video_link']) ? $params['video_link'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";
        $section_bg_image = isset($params['section_bg_image']['url']) ? $params['section_bg_image']['url'] : "";
        $classified_search_fields = isset($params['classified_search_fields']) ? $params['classified_search_fields'] : "";
        $mini_categories_repeater = isset($params['mini_categories_repeater']) ? $params['mini_categories_repeater'] : "";
        $carousel_categories_repeater = isset($params['carousel_categories_repeater']) ? $params['carousel_categories_repeater'] : "";
        $carousel_cat_title = isset($params['carousel_cat_title']) ? $params['carousel_cat_title'] : "";
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);

        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-hero-directory-section-start -->
        <section class="adt-hero-directory-section" style="background: url(<?php echo esc_url($section_bg_image) ?>)">
            <div class="container <?php echo $adt_container_class; ?>"
            ">
            <div class="row">
                <div class="col-lg-6">
                    <div class="content-box">
                        <span class="sub-title"><?php echo esc_html($section_title); ?></span>
                        <h1><?php echo esc_html($section_tagline); ?></h1>
                        <p><?php echo esc_html($section_description); ?></p>
                        <div class="adt-hero-search-tabs">
                            <form method="GET"
                                  action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                <div class="search-filters-bar">
                                    <?php foreach ($classified_search_fields as $field) { ?>
                                        <?php if ($field['classified_field_type'] === 'title') { ?>
                                            <div class="filter-box">
                                                <label for="ad_title"><?php echo __("Search", "adforest-elementor"); ?></label>
                                                <input type="text" name="ad_title" id="ad_title"
                                                       placeholder="<?php echo esc_attr__('Enter Keywords', 'adforest-elementor'); ?>">
                                            </div>
                                        <?php } elseif ($field['classified_field_type'] === 'category') {
                                            $categories = adforest_get_ad_taxonomy_callback('ad_cats');
                                            ?>
                                            <div class="filter-box type-box">
                                                <label for="search"><?php echo esc_html__('Category', 'adforest-elementor'); ?></label>
                                                <select class="default-select" name="cat_id">
                                                    <option value=""><?php echo __("Select Category", 'adforest-elementor'); ?></option>
                                                    <?php foreach ($categories as $category) {
                                                        $cat_details = get_taxonomy_details($category);
                                                        $category_name = $cat_details['name'] ?? '';
                                                        $category_id = $category->term_id ?? '';
                                                        ?>
                                                        <option value="<?php echo esc_attr($category_id); ?>"><?php echo esc_html($category_name); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php } elseif ($field['classified_field_type'] === 'location') {
                                            $countries = adforest_get_ad_taxonomy_callback('ad_country');
                                            ?>
                                            <div class="filter-box location-box">
                                                <label for="search"><?php echo esc_html__('Location', 'adforest-elementor'); ?></label>
                                                <select class="default-select" name="country_id">
                                                    <option value=""><?php echo esc_html__('Select Location', 'adforest-elementor'); ?></option>
                                                    <?php foreach ($countries as $country) {
                                                        $country_details = get_taxonomy_details($country);
                                                        $country_name = $country_details['name'] ?? '';
                                                        $country_id = $country->term_id;
                                                        ?>
                                                        <option value="<?php echo esc_attr($country_id); ?>"><?php echo esc_html($country_name); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                    <button class="adt-button-dark-1 search-button" type="submit"><i
                                                class="fas fa-search"></i><?php echo __("Search", "adforest-elementor"); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php
                        if (!empty($mini_categories_repeater)) :
                            ?>
                            <ul class="popular-keywords">
                                <li><strong><?php echo __("Popular:", 'adforest-elementor'); ?></strong></li>
                                <?php foreach ($mini_categories_repeater as $category) :
                                    $cat_id = $category['mini_category'];
                                    $cat_details = adforest_get_taxonomy_details_by_id($cat_id, 'ad_cats');
                                    $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                    $category_id = isset($cat_details['id']) ? $cat_details['id'] : '';

                                    $category_image = '';
                                    if (isset($category['category_image']) && is_array($category['category_image']) && !empty($category['category_image']['url'])) {
                                        $category_image = esc_url($category['category_image']['url']);
                                    }

                                    $link = '';
                                    if (!empty($category_id) && function_exists('adforest_cat_link_page')) {
                                        $link = esc_url(adforest_cat_link_page($category_id, 'cat_id'));
                                    } else {
                                        $link = '#';
                                    }

                                    ?>
                                    <li><a href="<?php echo esc_url($link); ?>"><img
                                                    style="width: 20px; height: 20px;"
                                                    src="<?php echo esc_url($category_image); ?>"
                                                    alt="vector"><?php echo esc_html($category_name); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="main-img-box">
                        <img class="img-fluid main-img"
                             src="<?php echo esc_url($bg_img); ?>"
                             alt="main-img">
                        <img class="shape-2"
                             src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-2.png' ?>"
                             alt="shape">
                        <img class="shape-1"
                             src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/shape-1.png' ?>"
                             alt="shape">
                        <img class="red-lines"
                             src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/red-lines.png' ?>"
                             alt="shape">
                        <a href="<?php echo esc_url($video_link); ?>" class="play-btn"><i
                                    class="fa fa-play"></i></a>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="categories-carousel-box">
                        <h3><?php echo esc_html($carousel_cat_title); ?></h3>
                        <div class="owl-carousel owl-theme adt-find-by-categories-carousel">
                            <?php if (is_array($carousel_categories_repeater) && count($carousel_categories_repeater) > 0) {
                                foreach ($carousel_categories_repeater as $category) {
                                    $cat_id = $category['category'];
                                    $cat_details = adforest_get_taxonomy_details_by_id($cat_id, 'ad_cats');
                                    $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                    $category_id = isset($cat_details['id']) ? $cat_details['id'] : '';

                                    $category_ad_count = isset($cat_details['ad_count']) ? intval($cat_details['ad_count']) : 0;

                                    $category_image = '';
                                    if (isset($category['image']) && is_array($category['image']) && !empty($category['image']['url'])) {
                                        $category_image = esc_url($category['image']['url']);
                                    }

                                    $link = '';
                                    if (!empty($category_id) && function_exists('adforest_cat_link_page')) {
                                        $link = esc_url(adforest_cat_link_page($category_id, $cat_link_page, 'cat_id'));
                                    }
                                    ?>
                                    <div class="item">
                                        <a href="<?php echo esc_url($link); ?>">
                                            <div class="category-box-main">
                                                <img src="<?php echo esc_url($category_image); ?>" alt="house-img">
                                                <span><?php echo esc_html($category_name); ?></span>
                                                <small><?php echo esc_html($category_ad_count . " Ads"); ?></small>
                                            </div>
                                        </a>
                                    </div>
                                <?php }
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </section>
        <!-- adt-hero-directory-section-end -->
        <?php
    }
}
// ============= ShortCode for Directory Hero ============= //

// ============= ShortCode for Main Hero 6 ============= //
if (!function_exists('adforest_main_hero_6')) {
    function adforest_main_hero_6($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $section_bg_image = isset($params['section_bg_image']['url']) ? $params['section_bg_image']['url'] : "";
        $classified_search_fields = isset($params['classified_search_fields']) ? $params['classified_search_fields'] : "";
        $categories_repeater = isset($params['category_main_repeater']) ? $params['category_main_repeater'] : [];
        $cat_section_title = isset($params['cat_section_title']) ? $params['cat_section_title'] : "";
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }


        $args = [
            'post_type' => 'ad_post',
            'posts_per_page' => get_option('posts_per_page'),
            'post_status' => 'publish',
        ];

        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        $ads_query = new WP_Query($args);
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';
        ?>
        <!-- adt-hero-city-section-start -->
        <section class="adt-hero-city-section" style="background: url(<?php echo esc_url($section_bg_image) ?>)">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="content-box">
                            <span class="sub-title"><?php echo esc_html($section_title); ?></span>
                            <h1><?php echo esc_html($section_tagline); ?></h1>
                            <p><?php echo esc_html($section_description); ?></p>
                            <div class="adt-hero-search-tabs">
                                <form method="GET"
                                      action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                    <div class="search-filters-bar">
                                        <?php foreach ($classified_search_fields as $field) { ?>
                                            <?php if ($field['classified_field_type'] === 'title') { ?>
                                                <div class="filter-box">
                                                    <label for="ad_title"><?php echo __("Search", "adforest-elementor"); ?></label>
                                                    <input type="text" name="ad_title" id="ad_title"
                                                           placeholder="<?php echo esc_attr__('Enter Keywords', 'adforest-elementor'); ?>">
                                                </div>
                                            <?php } elseif ($field['classified_field_type'] === 'category') {
                                                $categories = adforest_get_ad_taxonomy_callback('ad_cats');
                                                ?>
                                                <div class="filter-box type-box">
                                                    <label for="search"><?php echo esc_html__('Category', 'adforest-elementor'); ?></label>
                                                    <select class="default-select" name="cat_id">
                                                        <option value=""><?php echo __("Select Category", 'adforest-elementor'); ?></option>
                                                        <?php foreach ($categories as $category) {
                                                            $cat_details = get_taxonomy_details($category);
                                                            $category_name = $cat_details['name'] ?? '';
                                                            $category_id = $category->term_id;
                                                            ?>
                                                            <option value="<?php echo esc_attr($category_id); ?>"><?php echo esc_html($category_name); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            <?php } elseif ($field['classified_field_type'] === 'location') {
                                                $countries = adforest_get_ad_taxonomy_callback('ad_country');
                                                ?>
                                                <div class="filter-box location-box">
                                                    <label for="search"><?php echo esc_html__('Location', 'adforest-elementor'); ?></label>
                                                    <select class="default-select" name="country_id">
                                                        <option value=""><?php echo esc_html__('Select Location', 'adforest-elementor'); ?></option>
                                                        <?php foreach ($countries as $country) {
                                                            $country_details = get_taxonomy_details($country);
                                                            $country_name = $country_details['name'] ?? '';
                                                            $country_id = $country->term_id;
                                                            ?>
                                                            <option value="<?php echo esc_attr($country_id); ?>"><?php echo esc_html($country_name); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                        <button class="adt-button-dark-1 search-button" type="submit"><i
                                                    class="fas fa-search"></i><?php echo __("Search", "adforest-elementor"); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <?php
                        if ($ads_query->have_posts()) {
                            ?>
                            <div class="owl-carousel owl-theme adt-place-detail-carousel">
                                <?php
                                while ($ads_query->have_posts()) : $ads_query->the_post();
                                    $ad_details = get_ad_post_details(get_the_ID());
                                    $category_names = $ad_details['category_names'];
                                    $first_img = $ad_details['img'];
                                    $location = $ad_details['location'];
                                    $ad_link = $ad_details['ad_link'];
                                    $first_cat = isset($category_names[0]) ? $category_names[0] : "";
                                    $ad_selected_cats = wp_get_post_terms(get_the_ID(), 'ad_cats');
                                    $main_selected_cat = isset($ad_selected_cats[0]) ? $ad_selected_cats[0] : "";

                                    $cat_link = "";
                                    if ($main_selected_cat != "") {
                                        $cat_link = adforest_cat_link_page($main_selected_cat->term_id, $cat_link_page, 'cat_id');
                                    }
                                    ?>
                                    <div class="item">
                                        <div class="adt-place-detail-box">
                                            <img class="place-img"
                                                 src="<?php echo esc_url($first_img); ?>"
                                                 alt="place-img">
                                            <div class="place-content-box">
                                                <span class="tag"><a style="text-decoration: none; color: #000;"
                                                                     href="<?php echo esc_url($cat_link); ?>"><?php echo esc_html($first_cat); ?></a></span>
                                                <h5><a style="text-decoration: none; color: #fff;"
                                                       href="<?php echo esc_url($ad_link); ?>"><?php echo esc_html(get_the_title()); ?></a>
                                                </h5>
                                                <p>
                                                    <i class="fas fa-location-arrow"></i> <?php echo esc_html($location); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="col-lg-12">
                        <div class="categories-carousel-box">
                            <h3><?php echo esc_html($cat_section_title); ?></h3>
                            <?php if (is_array($categories_repeater) && count($categories_repeater) > 0) { ?>
                                <div class="owl-carousel owl-theme adt-find-by-categories-carousel">
                                    <?php foreach ($categories_repeater as $category) {
                                        $category_id = $category['main_category'];
                                        $cat_details = adforest_get_taxonomy_details_by_id($category_id, 'ad_cats');
                                        $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                        $category_ad_count = isset($cat_details['ad_count']) ? intval($cat_details['ad_count']) : 0;

                                        $category_image = '';
                                        if (isset($category['category_image']) && is_array($category['category_image']) && !empty($category['category_image']['url'])) {
                                            $category_image = esc_url($category['category_image']['url']);
                                        }

                                        $cat_id = isset($cat_details['id']) ? $cat_details['id'] : '';

                                        $link = '';
                                        if (!empty($cat_id) && function_exists('adforest_cat_link_page')) {
                                            $link = esc_url(adforest_cat_link_page($cat_id, $cat_link_page, 'cat_id'));
                                        }

                                        ?>
                                        <a href="<?php echo esc_url($link); ?>">
                                            <div class="item">
                                                <div class="category-box-main">
                                                    <img src="<?php echo esc_url($category_image); ?>" alt="house-img">
                                                    <span><?php echo esc_html($category_name); ?></span>
                                                    <small><?php echo esc_html($category_ad_count . " Ads"); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-hero-city-section-end -->
        <?php
    }
}
// ============= ShortCode for Main Hero 6 ============= //

// ============= ShortCode for Ad Grid Fancy ============= //
if (!function_exists('adforest_ad_grid_fancy_shortcode')) {
    function adforest_ad_grid_fancy_shortcode($params)
    {

        global $adforest_theme;
        $title = isset($params['title']) ? $params['title'] : "";
        $ads_limit = isset($params['ads_limit']) ? $params['ads_limit'] : "";
        $ads_title_limit = isset($params['ads_title_limit']) ? $params['ads_title_limit'] : "";
        $desc = isset($params['desc']) ? $params['desc'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";
        $ad_select = isset($params['ad_select']) ? $params['ad_select'] : "";
        $grid_columns = isset($params['grid_columns']) ? $params['grid_columns'] : "";
        $grid_style = isset($params['grid_style']) ? $params['grid_style'] : "";
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $link = '';
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }
        $style = "";
        if (!empty($bg_img)) {
            $style = 'style="background-image: url(' . esc_url($bg_img) . ')"';
        }

        $args = [
            'post_type' => 'ad_post',
            'posts_per_page' => $ads_limit,
            'post_status' => 'publish',
        ];

        // Adjust the query based on the selected type of ads
        if ($ad_select === 'simple') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '');
        } elseif ($ad_select === 'featured') {
            $args['meta_key'] = '_adforest_is_feature';
            $args['meta_value'] = '1';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            $link = adforest_set_url_param(get_the_permalink($sb_search_page), 'ad', '1');
        }

        $ads_query = new WP_Query($args);
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = ($sb_2column == false) ? "one-column-mobile-layout" : "";

        if ($ads_query->have_posts()) {
            ?>
            <!-- adt-directory-listing-section-start -->
            <section
                    class="adt-directory-listing-section adt-place-listing-section adt-classic-ads-section" <?php echo $style; ?>>
                <div class="container <?php echo $adt_container_class; ?>">
                    <div class="row">
                        <?php if (!empty($title) && !empty($desc)) {
                            ?>
                            <div class="col-lg-12">
                                <div class="heading-content">
                                    <?php if (!empty($title)) {
                                        ?>
                                        <h2><?php echo esc_html($title); ?></h2>
                                        <?php
                                    } ?>
                                    <?php if (!empty($desc)) {
                                        ?>
                                        <p><?php echo esc_html($desc); ?></p>
                                        <?php
                                    } ?>
                                </div>
                            </div>
                        <?php }
                        ?>
                    </div>
                    <div class="adt-search-ads-grid adt-search-ads-col-<?php echo $grid_columns; ?> <?php echo $mobile_two_columns; ?>">
                        <?php
                        while ($ads_query->have_posts()) {
                            $ads_query->the_post();
                            $ad_details = get_ad_post_details(get_the_ID());

                            $img = $ad_details['img'];
                            $truncated_location = $ad_details['truncated_location'];
                            $price_html = $ad_details['price_html'];
                            $heart_class = $ad_details['heart_class'];
                            $is_featured = $ad_details['is_featured'];
                            $ad_categories_post = $ad_details['categories'];
                            $ad_poster_img = $ad_details['ad_poster_img'];
                            $ad_poster_name = $ad_details['ad_poster_name'];
                            $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);
                            $ad_permalink = $ad_details['ad_link'];
                            $ad_title = truncate_string($ad_details['ad_title'], $ads_title_limit);
                            $all_ad_images = $ad_details['all_ad_images'];
                            $featured_tag = $is_featured ? '<img src="' . plugin_dir_url(__FILE__) . 'assets/images/featured-tag.png' . '" alt="featured-tag" class="featured-tag">' : '';
                            ?>
                            <div class="adt-property-ad-card-outer">
                                <?php
                                if ($grid_style == 1) {
                                    echo adforest_ad_grid_1($ad_permalink, $img, $is_featured, $ad_categories_post, $ad_details, $ad_title, $truncated_location, $price_html, $heart_class);
                                } elseif ($grid_style == 2) {
                                    echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class);
                                } elseif ($grid_style == 3) {
                                    echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                } elseif ($grid_style == 4) {
                                    echo adforest_ad_grid_4($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                }
                                ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </section>
            <!-- adt-directory-listing-section-end -->
            <?php
        }
    }
}
// ============= ShortCode for Ad Grid Fancy ============= //

// ============= ShortCode for Locations Fancy ============= //
if (!function_exists('locations_fancy_shortcode')) {
    function locations_fancy_shortcode($params)
    {
        global $adforest_theme;
        $main_sec_title = isset($params['main_sec_title']) ? $params['main_sec_title'] : "";
        $main_sec_desc = isset($params['main_sec_desc']) ? $params['main_sec_desc'] : "";
        $carousel_locations_repeater = isset($params['carousel_locations_repeater']) ? $params['carousel_locations_repeater'] : "";
        $cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        $sb_2column = (isset($adforest_theme['sb_2column_mobile_layout']) && $adforest_theme['sb_2column_mobile_layout'] == true) ? true : false;
        $mobile_two_columns = $sb_2column ? 2 : 1;
        ?>
        <!-- adt-popular-location-section-start -->
        <section class="adt-popular-location-section adt-place-popular-location-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="top-heading-box">
                            <h4><?php echo esc_html($main_sec_title); ?></h4>
                            <p><?php echo esc_html($main_sec_desc); ?></p>
                        </div>
                    </div>
                    <?php
                    if (!empty($carousel_locations_repeater)) { ?>
                        <div class="adt-popular-place-carousel owl-carousel owl-theme"
                             data-mobile-column="<?php echo esc_attr($mobile_two_columns) ?>">
                            <?php
                            foreach ($carousel_locations_repeater as $location) {
                                $location_id = $location['carousel_location'];
                                $location_details = adforest_get_taxonomy_details_by_id($location_id, 'ad_country');
                                if (empty($location_details)) {
                                    continue;
                                }
                                $location_name = isset($location_details['name']) ? sanitize_text_field($location_details['name']) : '';

                                $location_image = '';
                                if (isset($location['carousel_location_image']) && is_array($location['carousel_location_image']) && !empty($location['carousel_location_image']['url'])) {
                                    $location_image = esc_url($location['carousel_location_image']['url']);
                                }

                                $ad_count = isset($location_details['ad_count']) ? intval($location_details['ad_count']) : 0;

                                $loc_id = isset($location_details['id']) ? $location_details['id'] : '';

                                $cat_link = '';
                                if (!empty($loc_id) && function_exists('adforest_cat_link_page')) {
                                    $cat_link = esc_url(adforest_cat_link_page($loc_id, $cat_link_page, 'country_id'));
                                }

                                ?>
                                <div class="item">
                                    <a href="<?php echo esc_url($cat_link); ?>" class="adt-location-box">
                                        <div class="location-img-box large-img-box">
                                            <img src="<?php echo esc_url($location_image); ?>" alt="location">
                                        </div>
                                        <div class="location-meta-box">
                                            <span class="location-name"><?php echo esc_html($location_name); ?></span>
                                            <span class="ads-count"><?php echo esc_html($ad_count . " ads"); ?></span>
                                        </div>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
        <!-- adt-popular-location-section-end -->
        <?php
    }
}
// ============= ShortCode for Locations Fancy ============= //

// ============= ShortCode for Event Home Hero ============= //
if (!function_exists('adforest_event_home_hero')) {
    function adforest_event_home_hero($params)
    {
        global $adforest_theme;
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $video_link = isset($params['video_link']) ? $params['video_link'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";
        $section_bg_image = isset($params['section_bg_image']['url']) ? $params['section_bg_image']['url'] : "";
        $classified_search_fields = isset($params['classified_search_fields']) ? $params['classified_search_fields'] : "";
        $popular_event_categories = isset($params['popular_categories']) ? $params['popular_categories'] : "";
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_pro_event_page']);
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-event-hero-section-start -->
        <section class="adt-event-hero-section" style="background: url(<?php echo esc_url($section_bg_image) ?>)">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="content-box">
                            <span class="sub-title"><?php echo esc_html($section_title); ?></span>
                            <h1><?php echo esc_html($section_tagline); ?></h1>
                            <p><?php echo esc_html($section_description); ?></p>
                            <div class="adt-hero-search-tabs">
                                <form method="GET"
                                      action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                    <div class="search-filters-bar">
                                        <?php foreach ($classified_search_fields as $field) { ?>
                                            <?php if ($field['classified_field_type'] === 'title') { ?>
                                                <div class="filter-box">
                                                    <label for="ad_title"><?php echo __("Search", "adforest-elementor"); ?></label>
                                                    <input type="text" name="ad_title" id="ad_title"
                                                           placeholder="<?php echo esc_attr__('Enter Keywords', 'adforest-elementor'); ?>">
                                                </div>
                                            <?php } elseif ($field['classified_field_type'] === 'category') {
                                                $categories = adforest_get_ad_taxonomy_callback('l_event_cat');
                                                ?>
                                                <div class="filter-box type-box">
                                                    <label for="search"><?php echo esc_html__('Category', 'adforest-elementor'); ?></label>
                                                    <select class="default-select" name="cat_id">
                                                        <option value=""><?php echo __("Select Category", 'adforest-elementor'); ?></option>
                                                        <?php foreach ($categories as $category) {
                                                            $cat_details = get_taxonomy_details($category);
                                                            $category_name = $cat_details['name'] ?? '';
                                                            $category_id = $category->term_id;
                                                            ?>
                                                            <option value="<?php echo esc_attr($category_id); ?>"><?php echo esc_html($category_name); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            <?php } elseif ($field['classified_field_type'] === 'location') {
                                                $countries = adforest_get_ad_taxonomy_callback('event_loc');
                                                ?>
                                                <div class="filter-box location-box">
                                                    <label for="search"><?php echo esc_html__('Location', 'adforest-elementor'); ?></label>
                                                    <select class="default-select" name="country_id">
                                                        <option value=""><?php echo esc_html__('Select Location', 'adforest-elementor'); ?></option>
                                                        <?php foreach ($countries as $country) {
                                                            $country_details = get_taxonomy_details($country);
                                                            $country_name = $country_details['name'] ?? '';
                                                            $country_id = $country->term_id;
                                                            ?>
                                                            <option value="<?php echo esc_attr($country_id); ?>"><?php echo esc_html($country_name); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                        <button class="adt-button-dark-1 search-button" type="submit"><i
                                                    class="fas fa-search"></i><?php echo __("Search", "adforest-elementor"); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php
                            if (!empty($popular_event_categories)) :
                                ?>
                                <ul class="popular-keywords">
                                    <li><strong><?php echo __("Popular:", 'adforest-elementor'); ?></strong></li>
                                    <?php foreach ($popular_event_categories as $category) :
                                        $cat_details = adforest_get_taxonomy_details_by_id($category, 'l_event_cat');
                                        $category_name = isset($cat_details['name']) ? sanitize_text_field($cat_details['name']) : '';

                                        $cat_id = isset($cat_details['id']) ? intval($cat_details['id']) : 0;

                                        $event_search_page_link = isset($adforest_search_page) ? get_permalink($adforest_search_page) : '';

                                        if (!empty($cat_id) && !empty($event_search_page_link)) {
                                            $link = esc_url(add_query_arg('cat_id', $cat_id, $event_search_page_link));
                                        } else {
                                            $link = '#';
                                        }

                                        ?>
                                        <li><a href="<?php echo esc_url($link); ?>">
                                                <?php echo esc_html($category_name); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main-img-box">
                <img src="<?php echo esc_url($bg_img); ?>" alt="main-img">
                <a href="<?php echo esc_url($video_link); ?>" class="play-btn"><i
                            class="fa fa-play"></i></a>
            </div>
        </section>
        <!-- adt-event-hero-section-end -->
        <?php
    }
}
// ============= ShortCode for Event Home Hero ============= //

// ============= ShortCode for Event Grid Carousel ============= //
if (!function_exists('adforest_event_grid_carousel')) {
    function adforest_event_grid_carousel($params)
    {
        $user_id = get_current_user_id();
        $title = isset($params['section_title']) ? $params['section_title'] : "";
        $no_of_events = isset($params['no_of_events']) ? $params['no_of_events'] : "";
        $event_title_limit = isset($params['event_title_limit']) ? $params['event_title_limit'] : "";
        $event_cats_select = isset($params['event_cats_select']) ? $params['event_cats_select'] : "";
        $args = array(
            'post_type' => 'events',
            'post_status' => 'publish',
            'posts_per_page' => $no_of_events,
        );

        if (!empty($event_cats_select) && is_array($event_cats_select)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'l_event_cat',
                    'field' => 'term_id',
                    'terms' => $event_cats_select,
                    'operator' => 'IN'
                )
            );
        }

        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $query = new \WP_Query($args);

        global $adforest_theme;
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-event-list-section-start -->
        <section class="adt-event-list-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-ads-top-box">
                            <h2><?php echo esc_html($title); ?></h2>
                        </div>
                        <?php if ($query->have_posts()) { ?>
                            <div class="adt-event-list-carousel owl-carousel owl-theme">
                                <?php while ($query->have_posts()) {
                                    $query->the_post();
                                    $event_id = get_the_ID();
                                    $media = sb_pro_fetch_event_gallery($event_id);
                                    $event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
                                    $event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
                                    $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
                                    $event_start_date_timer = date('Y-m-d H:i:s', strtotime($event_start_date));
                                    $event_timer_html = '';
                                    if ($event_start_date_timer > date('Y-m-d H:i:s')) {
                                        $event_timer_html = '
                                                            <div class="event-bid-time" data-event-date="' . esc_attr($event_start_date_timer) . '">
                                                                <span class="days">00</span>
                                                                <span class="hours">00</span>
                                                                <span class="minutes">00</span>
                                                                <span class="seconds">00</span>
                                                            </div>
                                                            ';
                                    }
                                    $poster_id = get_post_field('post_author', $event_id);
                                    $user_info = get_userdata($poster_id);
                                    $poster_name = $user_info->display_name;
                                    $get_user_dp = adforest_get_user_dp($poster_id);
                                    $event_title = truncate_string(get_the_title(), $event_title_limit);
                                    ?>
                                    <div class="item">
                                        <div class="adt-event-list-card">
                                            <a href="<?php echo esc_url(get_the_permalink()); ?>>">
                                                <img class="main_event_image"
                                                     src="<?php echo sb_return_event_image_id($media, 'adforest_single_product'); ?>"
                                                     alt="event-img">
                                            </a>
                                            <?php echo $event_timer_html; ?>
                                            <div class="event-list-content">
                                                <span><i class="fas fa-calendar-alt"></i><?php echo esc_html($event_end_date); ?></span>
                                                <h6><a style="color: #fff;"
                                                       href="<?php echo esc_url(get_the_permalink()); ?>"><?php echo esc_html($event_title); ?></a>
                                                </h6>
                                                <div class="meta">
                                                    <img src="<?php echo esc_url($get_user_dp); ?>"
                                                         alt="author">
                                                    <span><?php echo esc_html($poster_name); ?></span>
                                                    <i class="far fa-eye"></i>
                                                    <span><?php echo esc_html(adforest_getPostViews($event_id) . " Views"); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-event-list-section-end -->
        <?php
    }
}
// ============= ShortCode for Event Grid Carousel ============= //

// ============= ShortCode for Locations Fancy 2 ============= //
if (!function_exists('locations_fancy_2_shortcode')) {
    function locations_fancy_2_shortcode($params)
    {
        $main_sec_title = isset($params['main_sec_title']) ? $params['main_sec_title'] : "";
        $location_repeater = isset($params['location_repeater']) ? $params['location_repeater'] : "";
        global $adforest_theme;
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <section style="padding: 0 0 70px 0;" class="adt-event-list-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-ads-top-box">
                            <h2><?php echo esc_html($main_sec_title); ?></h2>
                        </div>
                        <?php
                        if (!empty($location_repeater)) { ?>
                            <div class="adt-popular-place-carousel owl-carousel owl-theme">
                                <?php
                                foreach ($location_repeater as $location) {
                                    $location_id = $location['locations'];
                                    $location_details = adforest_get_taxonomy_details_by_id($location_id, 'event_loc');
                                    $location_name = isset($location_details['name']) ? sanitize_text_field($location_details['name']) : '';

                                    $location_image = '';
                                    if (isset($location['location_image']) && is_array($location['location_image']) && !empty($location['location_image']['url'])) {
                                        $location_image = esc_url($location['location_image']['url']);
                                    }

                                    $ad_count = isset($location_details['ad_count']) ? intval($location_details['ad_count']) : 0;

                                    $loc_id = isset($location_details['id']) ? intval($location_details['id']) : 0;

                                    $event_search_page_link = isset($event_search_page_link) ? $event_search_page_link : '';

                                    if (!empty($loc_id) && !empty($event_search_page_link)) {
                                        $cat_link = esc_url(add_query_arg('event_custom_loc', $loc_id, $event_search_page_link));
                                    } else {
                                        $cat_link = '#';
                                    }
                                    ?>
                                    <div class="item">
                                        <a href="<?php echo esc_url($cat_link); ?>"
                                           class="adt-location-box">
                                            <div class="location-img-box large-img-box">
                                                <img src="<?php echo esc_url($location_image); ?>"
                                                     alt="location">
                                            </div>
                                            <div class="location-meta-box">
                                                <span class="location-name"><?php echo esc_html($location_name); ?></span>
                                                <span class="ads-count"><?php echo esc_html($ad_count) . __(" Events", "adforest-elementor"); ?></span>
                                            </div>
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Locations Fancy 2 ============= //

// ============= ShortCode for Event Exp ============= //
if (!function_exists('about_us_events_exp_short_base')) {
    function about_us_events_exp_short_base($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_description = isset($params['section_description']) ? $params['section_description'] : "";
        $bg_img = isset($params['bg_img']['url']) ? $params['bg_img']['url'] : "";
        $img_1 = isset($params['img_1']['url']) ? $params['img_1']['url'] : "";
        $img_2 = isset($params['img_2']['url']) ? $params['img_2']['url'] : "";
        $exp_head = isset($params['exp_head']) ? $params['exp_head'] : "";
        $exp_desc = isset($params['exp_desc']) ? $params['exp_desc'] : "";
        $video_link = isset($params['video_link']) ? $params['video_link'] : "";
        $features = isset($params['features']) ? $params['features'] : "";
        $btn_text = isset($params['btn_text']) ? $params['btn_text'] : "";
        $btn_link = isset($params['btn_link']) ? $params['btn_link'] : "";

        global $adforest_theme;
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-about-experience-section-start -->
        <section class="adt-about-experience-section adt-event-experience-section"
                 style="background: url(<?php echo esc_url($bg_img) ?>)">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="left-img-box">
                            <img class="event-exp-1" src="<?php echo esc_url($img_1); ?>"
                                 alt="event-experience">
                            <img class="event-exp-2" src="<?php echo esc_url($img_2); ?>"
                                 alt="event-experience">
                            <div class="experience-box">
                                <strong><?php echo esc_html($exp_head); ?></strong>
                                <span><?php echo esc_html($exp_desc); ?></span>
                            </div>
                            <a href="<?php echo esc_url($video_link); ?>" class="play-btn"><i
                                        class="fa fa-play"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="content-box">
                            <span><?php echo esc_html($section_title); ?></span>
                            <h2><?php echo esc_html($section_tagline); ?></h2>
                            <p><?php echo esc_html($section_description); ?></p>
                            <ul>
                                <?php if (is_array($features)) {
                                    foreach ($features as $feature) {
                                        ?>
                                        <li>
                                            <i class="fas fa-check-double"></i><?php echo esc_html(isset($feature['title']) ? $feature['title'] : ""); ?>
                                        </li>
                                    <?php }
                                } ?>
                            </ul>
                            <a href="<?php echo esc_url($btn_link); ?>"
                               class="adt-button-dark-event"><?php echo esc_html($btn_text); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-about-experience-section-end -->
        <?php
    }
}
// ============= ShortCode for Event Exp ============= //

// ============= ShortCode for Event List ============= //
if (!function_exists('adforest_event_list_shortcode')) {
    function adforest_event_list_shortcode($params)
    {
        $main_sec_title = isset($params['main_sec_title']) ? $params['main_sec_title'] : "";
        $main_sec_description = isset($params['main_sec_description']) ? $params['main_sec_description'] : "";
        $main_sec_btn_text = isset($params['main_sec_btn_text']) ? $params['main_sec_btn_text'] : "";
        $main_sec_btn_link = isset($params['main_sec_btn_link']) ? $params['main_sec_btn_link'] : "";
        $main_section_ppp = isset($params['main_section_ppp']) ? $params['main_section_ppp'] : "";
        $title_limit = isset($params['title_limit']) ? $params['title_limit'] : "";

        $user_id = get_current_user_id();
        $args = array(
            'post_type' => 'events',
            'post_status' => 'publish',
            'posts_per_page' => $main_section_ppp,
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $query = new \WP_Query($args);

        global $adforest_theme;
        $adt_container_class = "";
        if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
            $adt_container_class = "adt-container";
        }

        $page_id = get_queried_object_id();

        $page_header_style = get_post_meta($page_id, '_page_header_style', true);

        if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
            $adt_container_class = "adt-container";
        }
        ?>
        <!-- adt-events-list-section-start -->
        <section class="adt-events-list-section">
            <div class="container <?php echo $adt_container_class; ?>">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="top-heading-box">
                            <div>
                                <h2><?php echo esc_html($main_sec_title); ?></h2>
                                <p><?php echo esc_html($main_sec_description); ?></p>
                            </div>
                            <a href="<?php echo esc_url($main_sec_btn_link); ?>"
                               class="adt-button-dark"><?php echo esc_html($main_sec_btn_text); ?></a>
                        </div>
                        <?php if ($query->have_posts()) { ?>
                            <?php while ($query->have_posts()) {
                                $query->the_post();
                                $event_id = get_the_ID();
                                $media = sb_pro_fetch_event_gallery($event_id);
                                $event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
                                $event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
                                $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
                                $event_start_date_timer = date('Y-m-d H:i:s', strtotime($event_start_date));
                                $event_title = truncate_string(get_the_title(), $title_limit);
                                $event_timer_html = '';
                                if ($event_start_date_timer > date('Y-m-d H:i:s')) {
                                    $event_timer_html = '
                                                            <div class="event-bid-time" data-event-date="' . esc_attr($event_start_date_timer) . '">
                                                                <span class="days">00</span>
                                                                <span class="hours">00</span>
                                                                <span class="minutes">00</span>
                                                                <span class="seconds">00</span>
                                                            </div>
                                                            ';
                                }
                                $poster_id = get_post_field('post_author', $event_id);
                                $user_info = get_userdata($poster_id);
                                $poster_name = $user_info->display_name;
                                $get_user_dp = adforest_get_user_dp($poster_id);

                                $clean_content = wp_strip_all_tags(get_the_content());
                                ?>
                                <div class="adt-event-list-box">
                                    <div class="event-img-box">
                                        <a href="<?php echo esc_url(get_the_permalink()); ?>"><img
                                                    src="<?php echo sb_return_event_image_id($media, 'adforest_single_product'); ?>"
                                                    alt="event-list-img"></a>
                                        <?php echo $event_timer_html; ?>
                                    </div>

                                    <div class="event-detail-box">
                                        <a href="<?php echo esc_url(get_the_permalink()); ?>">
                                            <h3><?php echo esc_html($event_title); ?></h3></a>
                                        <ul>
                                            <li><i class="fas fa-location-arrow"></i><?php echo esc_html($event_venue); ?>
                                            </li>
                                            <li><i class="far fa-calendar-alt"></i><?php echo esc_html($event_end_date); ?>
                                            </li>
                                            <li><img src="<?php echo esc_url($get_user_dp); ?>"
                                                     alt="author"><?php echo esc_html($poster_name); ?></li>
                                        </ul>
                                        <p><?php echo esc_html($clean_content); ?></p>
                                        <a href="<?php echo esc_url(get_the_permalink()); ?>"
                                           class="event-external-link"><img
                                                    src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/arrow-icon.svg' ?>"
                                                    alt="arrow-icon"></a>
                                    </div>
                                </div>
                            <?php }
                        } ?>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-events-list-section-end -->
        <?php
    }
}
// ============= ShortCode for Event List ============= //

// ============= ShortCode for Cyber sale hero ============= //
if (!function_exists('adforest_cyber_sale_hero_shortcode')) {
    function adforest_cyber_sale_hero_shortcode($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_tagline = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $section_sale_text = isset($params['section_sale_text']) ? $params['section_sale_text'] : "";
        $sale_end_date = isset($params['sale_end_date']) ? $params['sale_end_date'] : "";
        $section_bg_img = isset($params['section_bg_img']['url']) ? $params['section_bg_img']['url'] : "";
        $section_main_img = isset($params['section_main_img']['url']) ? $params['section_main_img']['url'] : "";
        $section_second_img = isset($params['section_second_img']['url']) ? $params['section_second_img']['url'] : "";
        $section_discount_img = isset($params['section_discount_img']['url']) ? $params['section_discount_img']['url'] : "";

        $query_args = array(
            'post_type' => 'product',
            'posts_per_page' => 2,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'simple',
                ),
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $products = new WP_Query($query_args);
        ?>
        <!-- adt-cyber-sale-hero-section-start -->
        <section class="adt-cyber-sale-hero-section" style="background: url(<?php echo esc_url($section_bg_img) ?>)">
            <div class="container adt-container">
                <div class="row">
                    <div class="col-lg-7 col-xl-8">
                        <div class="left-content-box">
                            <span><?php echo esc_html($section_title); ?></span>
                            <h1><?php echo esc_html($section_tagline); ?></h1>
                            <ul id="countdown-timer" data-sale-end-date="<?php echo esc_attr($sale_end_date); ?>">
                                <li><strong><?php echo esc_html($section_sale_text); ?></strong></li>
                                <li><span id="days">0</span></li>
                                <li><span id="hours">0</span></li>
                                <li><span id="minutes">0</span></li>
                                <li><span id="seconds">0</span></li>
                            </ul>
                            <div class="row">
                                <?php
                                if ($products->have_posts()) {
                                    while ($products->have_posts()) {
                                        $products->the_post();
                                        global $product;

                                        $image_id = $product->get_image_id();
                                        $image_url = wp_get_attachment_image_url($image_id, 'full');

                                        $default_image = trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';
                                        $img_src = !empty($image_url) ? $image_url : $default_image;
                                        ?>
                                        <div class="col-lg-12 col-xl-6">
                                            <div class="adt-cyber-product-list-box">
                                                <div class="cyber-product-img-box">
                                                    <a href="<?php the_permalink(); ?>"><img
                                                                src="<?php echo esc_url($img_src); ?>"
                                                                alt="product-list-img"></a>
                                                </div>
                                                <div class="cyber-product-content-box">
                                                    <?php
                                                    $regular_price = $product->get_regular_price();
                                                    $sale_price = $product->get_sale_price();
                                                    ?>
                                                    <span>
                                                        <?php if (!empty($sale_price)) : ?>
                                                            <del><?php echo wc_price($regular_price); ?></del>
                                                            <?php echo wc_price($sale_price); ?>
                                                        <?php else : ?>
                                                            <?php echo wc_price($regular_price); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <a href="<?php the_permalink(); ?>">
                                                        <h4><?php echo esc_html(wp_trim_words(get_the_title(), 5, '...')); ?></h4>
                                                    </a>
                                                    <?php
                                                    $average_rating = $product->get_average_rating();
                                                    ?>
                                                    <div class="rating">
                                                        <span><?php echo esc_html($product->get_rating_count()) . ' ' . __("Reviews ", "adforest-elementor"); ?></span>
                                                        <?php
                                                        // Display stars based on the rating
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $average_rating) {
                                                                echo '<i class="fas fa-star"></i>';
                                                            } else {
                                                                echo '<i class="far fa-star"></i>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-xl-4">
                        <div class="cyber-sale-img-box">
                            <img class="img-fluid main-img"
                                 src="<?php echo esc_url($section_main_img); ?>" alt="main-img">
                            <img class="discount-img"
                                 src="<?php echo esc_url($section_discount_img); ?>"
                                 alt="discount-img">
                            <img class="text-img"
                                 src="<?php echo esc_url($section_second_img); ?>"
                                 alt="text-img">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- adt-cyber-sale-hero-section-end -->
        <?php
    }
}
// ============= ShortCode for Cyber sale hero ============= //

// ============= ShortCode for Multivendor Mini Category Slider ============= //
if (!function_exists('adforest_multivendor_mini_category_slider')) {
    function adforest_multivendor_mini_category_slider($params)
    {
        $product_categories_repeater = $params['product_categories_repeater'] ?? [];
        $product_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        $shop_url = wc_get_page_permalink('shop');
        if (!empty($product_categories_repeater) && !is_wp_error($product_categories_repeater)) {
            ?>
            <!-- adt-cyber-categories-topbar-start -->
            <section class="adt-cyber-categories-topbar">
                <div class="container adt-container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="adt-category-type-carousel-box">
                                <div class="adt-category-types-carousel-2 owl-carousel owl-theme">
                                    <?php
                                    $shop_url = wc_get_page_permalink('shop');
                                    foreach ($product_categories_repeater as $category) {
                                        $category_id = $category['main_category'];
                                        $cat_details = adforest_get_woocommerce_category_details($category_id);
                                        $category_link = !empty($category_id) && !empty($shop_url)
                                            ? esc_url($shop_url . '?category_id=' . $category_id)
                                            : '';
                                        ?>
                                        <div class="item">
                                            <div class="category-type-box">
                                                <a href="<?php echo esc_url($category_link); ?>">
                                                    <span><?php echo esc_html($cat_details['name'] ?? ''); ?></span>
                                                </a>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <form id="shop-categories-select" method="get"
                                      action="<?php echo esc_url($shop_url); ?>">
                                    <div class="dropdown category-dropdown">
                                        <button class="btn btn-secondary dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/grid-icon.svg' ?>"
                                                 alt="grid-icon"><?php echo __("All Categories", 'adforest-elementor') ?>
                                        </button>
                                        <ul class="dropdown-menu categories-list">
                                            <?php
                                            foreach ($product_categories as $category) {
                                                $category_id = $category->term_id;
                                                $category_link = !empty($category_id) && !empty($shop_url)
                                                    ? esc_url($shop_url . '?category_id=' . $category_id)
                                                    : '';
                                                echo '<li><a class="category-link" href="#" data-category-id="' . esc_attr($category->term_id) . '"><i
                                                            class="fas fa-atom"></i>' . esc_html($category->name) . '</a></li>';
                                            } ?>
                                        </ul>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- adt-cyber-categories-topbar-end -->
            <?php
        }
    }
}
// ============= ShortCode for Multivendor Mini Category Slider ============= //

// ============= ShortCode for Multivendor Product Carousel ============= //
if (!function_exists('adforest_multivendor_product_carousel_w_ads_shortcode')) {
    function adforest_multivendor_product_carousel_w_ads_shortcode($params)
    {
        $section_title = isset($params['section_title']) ? $params['section_title'] : "";
        $section_header_image = isset($params['section_header_image']['url']) ? $params['section_header_image']['url'] : "";
        $section_btn_title = isset($params['section_btn_title']) ? $params['section_btn_title'] : "";
        $section_btn_link = isset($params['section_btn_link']) ? $params['section_btn_link'] : "";
        $section_left_ad = isset($params['section_left_advert']) ? $params['section_left_advert'] : "";
        $section_right_ad = isset($params['section_right_advert']) ? $params['section_right_advert'] : "";
        $product_limit = isset($params['product_limit']) ? $params['product_limit'] : "";
        $product_title_limit = isset($params['product_title_limit']) ? $params['product_title_limit'] : "";

        $query_args = array(
            'post_type' => 'product',
            'posts_per_page' => $product_limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'simple',
                ),
            ),
        );

        $products = new WP_Query($query_args);
        ?>
        <section class="adt-cyber-sale-products-section" style="padding-bottom: 0;">
            <div class="container adt-container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-cyber-product-best-deals-container">
                            <div class="advertise-img-box left-advertise">
                                <?php echo wp_kses_post($section_left_ad); ?>
                            </div>
                            <div class="cyber-best-deals-box">
                                <div class="header-box">
                                    <h3><?php echo esc_html($section_title); ?> <img
                                                src="<?php echo esc_url($section_header_image); ?>"
                                                alt="gif">
                                    </h3>
                                    <a href="<?php echo esc_url($section_btn_link); ?>">
                                        <button class="adt-button-dark-1"><?php echo esc_html($section_btn_title); ?></button>
                                    </a>
                                </div>
                                <div class="best-deals-body-box">
                                    <div class="cyber-products-mini-carousel owl-carousel owl-theme">
                                        <?php
                                        if ($products->have_posts()) {
                                            while ($products->have_posts()) {
                                                $products->the_post();
                                                global $product;

                                                $image_id = $product->get_image_id();
                                                $image_url = wp_get_attachment_image_url($image_id, 'full');
                                                $product_title = truncate_string(get_the_title(), $product_title_limit);

                                                $default_image = trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';
                                                $img_src = !empty($image_url) ? $image_url : $default_image;
                                                ?>
                                                <div class="item">
                                                    <div class="adt-cyber-product-mini-card">
                                                        <div class="product-img-box">
                                                            <a href="<?php the_permalink(); ?>"><img
                                                                        src="<?php echo esc_url($img_src); ?>"
                                                                        alt="product"></a>
                                                        </div>
                                                        <div class="product-content-box">
                                                            <?php
                                                            $regular_price = $product->get_regular_price();
                                                            $sale_price = $product->get_sale_price();
                                                            ?>
                                                            <span>
                                                <?php if (!empty($sale_price)) : ?>
                                                    <?php echo wc_price($sale_price); ?>
                                                    <del><?php echo wc_price($regular_price); ?></del>
                                                <?php else : ?>
                                                    <?php echo wc_price($regular_price); ?>
                                                <?php endif; ?>
                                            </span>
                                                            <a href="<?php the_permalink(); ?>">
                                                                <h4><?php echo esc_html($product_title); ?></h4>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php }
                                        } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="advertise-img-box right-advertise">
                                <?php echo wp_kses_post($section_right_ad); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for Multivendor Product Carousel ============= //

// ============= ShortCode for adforest flash sale ============= //
if (!function_exists('adforest_flash_sale_shortcode')) {
    function adforest_flash_sale_shortcode($params)
    {
        $bg_color = isset($params['bg_color']) ? $params['bg_color'] : "";
        $no_of_products = isset($params['no_of_products']) ? $params['no_of_products'] : "";
        $header_image = isset($params['header_image']['url']) ? $params['header_image']['url'] : "";
        $left_img = isset($params['left_img_ad']) ? $params['left_img_ad'] : "";
        $right_img = isset($params['right_img_ad']) ? $params['right_img_ad'] : "";

        $query_args = array(
            'post_type' => 'product',
            'posts_per_page' => $no_of_products == "" ? 6 : $no_of_products,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'simple',
                ),
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $products = new WP_Query($query_args);
        ?>
        <section class="adt-cyber-sale-products-section">
            <div class="container adt-container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-cybersale-product-mini-container"
                             style="background-color: <?php echo $bg_color; ?>">
                            <img class="vector-img" src="<?php echo esc_url($header_image); ?>"
                                 alt="vector">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="adt-horizontal-adversite">
                                        <?php echo wp_kses_post($left_img); ?>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="adt-horizontal-adversite">
                                        <?php echo wp_kses_post($right_img); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="adt-cyberSale-product-mini-grid">
                                <?php
                                if ($products->have_posts()) {
                                    while ($products->have_posts()) {
                                        $products->the_post();
                                        global $product;

                                        $image_id = $product->get_image_id();
                                        $image_url = wp_get_attachment_image_url($image_id, 'full');

                                        $default_image = trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';
                                        $img_src = !empty($image_url) ? $image_url : $default_image;
                                        ?>
                                        <div class="adt-cyber-product-mini-card">
                                            <div class="product-img-box">
                                                <a href="<?php the_permalink(); ?>"><img
                                                            src="<?php echo esc_url($img_src); ?>"
                                                            alt="product"></a>
                                            </div>
                                            <div class="product-content-box">
                                                <?php
                                                $regular_price = $product->get_regular_price();
                                                $sale_price = $product->get_sale_price();
                                                ?>
                                                <span>
                                                    <?php if (!empty($sale_price)) : ?>
                                                        <?php echo wc_price($sale_price); ?>
                                                        <del><?php echo wc_price($regular_price); ?></del>
                                                    <?php else : ?>
                                                        <?php echo wc_price($regular_price); ?>
                                                    <?php endif; ?>
                                                </span>
                                                <a href="<?php the_permalink(); ?>">
                                                    <h4><?php echo esc_html(wp_trim_words(get_the_title(), 5, '...')); ?></h4>
                                                </a>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
// ============= ShortCode for adforest flash sale ============= //
