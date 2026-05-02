<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
defined('ABSPATH') || exit;

/* Galery template */
global $adforest_theme;
$event_id = get_the_ID();
$pid = $event_id;
$poster_id = get_post_field('post_author', $pid);
$venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
$sb_pro_event_status = get_post_meta($event_id, 'sb_pro_event_status', true);
$sb_pro_event_contact = get_post_meta($event_id, 'sb_pro_event_contact', true);
$sb_pro_event_email = get_post_meta($event_id, 'sb_pro_event_email', true);
$sb_pro_event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
$sb_pro_event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
$sb_pro_event_lat = get_post_meta($event_id, 'sb_pro_event_lat', true);
$sb_pro_event_long = get_post_meta($event_id, 'sb_pro_event_long', true);
$sb_pro_event_listing_id = get_post_meta($event_id, 'sb_pro_event_listing_id', true);
sb_expire_the_event($event_id);    // expire the event if date is expired

$event_questions = get_post_meta($event_id, 'event_question', true);
$event_schedules = get_post_meta($event_id, 'event_schedules', true);
$sb_profile_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_profile_page']);

$user_id = get_current_user_id();
$all_attendees = get_post_meta($event_id, 'attending_users', true);
$going = "yes";

$going_text = esc_html__('Going', 'sb_pro');
if (is_array($all_attendees) && ($key = array_search($user_id, $all_attendees)) !== false) {
    $going = 'no';
    $going_text = esc_html__('Not Going', 'sb_pro');
}
$breadcrumb = isset($adforest_theme['event_breadcrumb']['url']) ? $adforest_theme['event_breadcrumb']['url'] : "";

$breadcrumb_image_style = "";
if ($breadcrumb != "") {
    $breadcrumb_image_style = 'style= "background-image: url(' . esc_url($breadcrumb) . ')"';
}

$user_type = __('Dealer', 'sb_pro');
if (get_user_meta($poster_id, '_sb_user_type', true) == 'Indiviual') {
    $user_type = __('Individual', 'sb_pro');
} else if (get_user_meta($poster_id, '_sb_user_type', true) == 'Dealer') {
    $user_type = __('Dealer', 'sb_pro');
}

$poster_name = get_post_meta($pid, '_adforest_poster_name', true);

if ($poster_name == "") {
    $user_info = get_userdata($poster_id);
    $poster_name = $user_info->display_name;
}
$registration_date = $user_info->user_registered;
$user_pic = adforest_get_user_dp($poster_id);

$ad_location = get_post_meta(get_the_ID(), 'sb_pro_event_venue', true);
$truncated_location = truncate_string($ad_location, 25);
?>

    <section class="adt-event-title-section" <?php echo $breadcrumb_image_style; ?>>
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="content-box">
                        <div>
                            <h3><?php echo esc_html__(get_the_title()) ?></h3>
                            <ul>
                                <input type="hidden" name="event_id" id="ad_id" value="<?php echo $event_id; ?>">
                                <li>
                                    <i class="far fa-calendar-alt"></i><?php echo date(get_option('date_format') . "  " . get_option('time_format'), strtotime($sb_pro_event_start_date)); ?>
                                </li>
                                <li>
                                    <i class="far fa-clock"></i><?php echo date(get_option('date_format') . "  " . get_option('time_format'), strtotime($sb_pro_event_end_date)); ?>
                                </li>
                                <?php if (get_post_field('post_author', $pid) == get_current_user_id() || is_super_admin(get_current_user_id())) { ?>
                                    <li>
                                        <i class="fa fa-edit"></i>
                                        <span> <a style="color: #6D6D6D;"
                                                  href="<?php echo get_the_permalink($sb_profile_page) . '?page_type=events&id=' . get_the_ID() . '' ?>"><?php echo esc_html__('Edit', 'sb_pro'); ?></a>
                                    </span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <div>
                            <?php $already_favourite = get_user_meta($poster_id, '_sb_fav_event_' . $event_id, true); ?>
                            <a href="javascript:void(0)" id="ad-to-fav-event"
                               class='<?php echo $already_favourite == $event_id ? 'ad-favourited' : "" ?> social-links'
                               data-id="<?php echo adforest_return_echo($event_id) ?>">
                                <img src="<?php echo esc_url(SB_DIR_URL . "/assets/images/heart-icon.svg"); ?>"
                                     alt="action-icon">
                            </a>
                            <?php if (isset($adforest_theme['event_share_allow']) && $adforest_theme['event_share_allow']) { ?>
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target=".share-events"
                                   id="share-event" data-id="<?php echo adforest_return_echo($event_id) ?>"
                                   class="social-links">
                                    <img src="<?php echo esc_url(SB_DIR_URL . "/assets/images/share-icon.svg"); ?>"
                                         alt="action-icon">
                                </a>
                            <?php } ?>
                            <?php if ($sb_pro_event_contact != "") { ?>
                                <a href="https://wa.me/<?php echo esc_attr($sb_pro_event_contact) ?>"
                                   class="social-links" id="whatsapp-event"
                                   data-id="<?php echo adforest_return_echo($event_id) ?>"><img
                                            src="<?php echo esc_url(SB_DIR_URL . "/assets/images/whatsapp-icon.svg"); ?>"
                                            alt="action-icon"></a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="adt-ad-detail-section ad-advanced-detail-section adt-event-detail-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-ad-detail-content-wrapper">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="ad-detail-middle-content">
                                    <?php
                                    if ((get_post_field('post_author', $pid) == $user_id || current_user_can('administrator'))) {
                                        ?>
                                        <div role="alert" class="alert alert-info alert-dismissible alert-outline">
                                            <i class="fa fa-info-circle"></i>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                    aria-label="Close"></button>
                                            <a style="cursor: pointer" data-bs-toggle="modal"
                                               data-bs-target=".sortable-images"><?php echo __('Rearrange the event photos.', 'sb_pro'); ?></a>
                                        </div>
                                        <?php
                                    }


                                    if (get_post_field('post_author', $pid) == $user_id && get_post_status($event_id) == 'pending') {
                                        ?>
                                        <div role="alert" class="alert alert-warning alert-dismissible alert-outline">
                                            <i class="fa fa-info-circle"></i>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                    aria-label="Close"></button>
                                            <a data-bs-toggle="modal"
                                               data-bs-target=".sortable-images"><?php echo __('Waiting for admin approval', 'sb_pro'); ?></a>
                                        </div>
                                        <?php
                                    }

                                    wc_get_template2('single-event/event-gallery.php');
                                    ?>
                                    <?php
                                    if (class_exists('ACF')) {
                                        get_template_part('template-parts/acfcustom', 'fields');
                                    }
                                    ?>
                                    <div id="adt-ad-description-box" class="adt-ad-description">
                                        <h4><?php echo esc_html__("Description:"); ?></h4>
                                        <?php echo get_the_content(" ", " ", $event_id); ?>
                                    </div>
                                    <?php wc_get_template2('single-event/event-map.php'); ?>
                                    <?php
                                    if ($event_questions && !empty($event_questions) || $event_schedules && !empty($event_schedules)) { ?>
                                        <div class="adt-ad-description adforest-event-faq-and-schedule">
                                            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                                <?php if ($event_questions && !empty($event_questions)) { ?>
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link active" id="pills-home-tab"
                                                                data-bs-toggle="pill"
                                                                data-bs-target="#pills-home" type="button" role="tab"
                                                                aria-controls="pills-home"
                                                                aria-selected="true"><?php echo esc_html__('FAQ', 'sb_pro'); ?></button>
                                                    </li>

                                                <?php } ?>

                                                <?php if ($event_schedules && !empty($event_schedules)) { ?>
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link" id="pills-profile-tab"
                                                                data-bs-toggle="pill"
                                                                data-bs-target="#pills-profile" type="button" role="tab"
                                                                aria-controls="pills-profile"
                                                                aria-selected="false"><?php echo esc_html__('Schedule', 'sb_pro'); ?></button>
                                                    </li>

                                                <?php } ?>
                                            </ul>
                                            <div class="tab-content" id="pills-tabContent">
                                                <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                                     aria-labelledby="pills-home-tab">
                                                    <div class="accordion" id="accordionExample">
                                                        <?php
                                                        if ($event_questions && !empty($event_questions)) {
                                                            $count = 0;
                                                            foreach ($event_questions as $que) {
                                                                $count++;
                                                                ?>
                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header" id="headingOne">
                                                                        <button class="accordion-button <?php echo is_rtl() ? "event-question-accordion-adf" : ""; ?>" type="button"
                                                                                data-bs-toggle="collapse"
                                                                                data-bs-target="#faqs_<?php echo esc_attr($count); ?>"
                                                                                aria-expanded="true"
                                                                                aria-controls="collapseOne">
                                                                            <?php echo esc_html__($que['question']) ?>
                                                                        </button>
                                                                    </h2>
                                                                    <div id="faqs_<?php echo esc_attr($count); ?>"
                                                                         class="accordion-collapse collapse <?php echo $count == 1 ? 'show' : ""; ?>"
                                                                         aria-labelledby="headingOne"
                                                                         data-bs-parent="#accordionExample">
                                                                        <div class="accordion-body default-text-color-adf">
                                                                            <?php echo esc_html__($que['answer']) ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                                     aria-labelledby="pills-profile-tab">
                                                    <div class="accordion" id="accordionExample2">
                                                        <?php
                                                        if ($event_schedules && !empty($event_schedules)) {
                                                            $count = 0;
                                                            foreach ($event_schedules as $que) {
                                                                $count++;
                                                                ?>
                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header" id="headingOne">
                                                                        <button class="accordion-button <?php echo is_rtl() ? "event-question-accordion-adf" : ""; ?>" type="button"
                                                                                data-bs-toggle="collapse"
                                                                                data-bs-target="#schedule_<?php echo esc_attr($count); ?>"
                                                                                aria-expanded="true"
                                                                                aria-controls="collapseOne">
                                                                            <?php echo esc_html__($que['day']) ?>
                                                                        </button>
                                                                    </h2>
                                                                    <div id="schedule_<?php echo esc_attr($count); ?>"
                                                                         class="accordion-collapse collapse <?php echo $count == 1 ? 'show' : ""; ?>"
                                                                         aria-labelledby="headingOne"
                                                                         data-bs-parent="#accordionExample2">
                                                                        <div class="accordion-body default-text-color-adf">
                                                                            <?php echo($que['day_val']) ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <?php
                                    if (isset($adforest_theme['event_review_allowed']) && $adforest_theme['event_review_allowed']) {
                                        wc_get_template2('single-event/event-review.php');
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="adt-product-author-detail-box">
                                    <?php
                                    $event_start_date = date('Y-m-d H:i:s', strtotime($sb_pro_event_start_date));
                                    ?>
                                    <ul class="adt-listing-bidding" data-event-date="<?php echo $event_start_date; ?>">
                                        <li>
                                            <span class="days">00</span>
                                            <small><?php echo esc_html__("days", "sb_pro"); ?></small>
                                        </li>
                                        <li>
                                            <span class="hours">00</span>
                                            <small><?php echo esc_html__("hours", "sb_pro"); ?></small>
                                        </li>
                                        <li>
                                            <span class="minutes">00</span>
                                            <small><?php echo esc_html__("minutes", "sb_pro"); ?></small>
                                        </li>
                                        <li>
                                            <span class="seconds">00</span>
                                            <small><?php echo esc_html__("seconds", "sb_pro"); ?></small>
                                        </li>
                                    </ul>
                                    <div class="going-btn">
                                        <a href="javascript:void()" class="btn-theme-secondary"
                                           data-status="<?php echo esc_attr($going) ?>" id="going_to_event"
                                           data-id="<?php echo $event_id ?>"
                                           data-going="<?php echo esc_html__('Going', 'sb_pro'); ?>"
                                           data-going="<?php echo esc_html__('Not Going', 'sb_pro'); ?>"><?php echo esc_html($going_text); ?></a>
                                        </span>
                                    </div>
                                    <div class="author-box">
                                        <?php
                                        $is_phone_verified = get_user_meta(get_current_user_id(), '_sb_is_ph_verified', true);
                                        $verified_class = '';
                                        if ($is_phone_verified == '1') {
                                            $verified_class = 'verified';
                                        }
                                        ?>
                                        <span style="margin: 10px 10px 0 0"
                                              class="designation"><?php echo adforest_return_echo($user_type); ?></span>
                                        <div class="author-img">
                                            <img src="<?php echo esc_attr($user_pic); ?>" alt="author-img">
                                        </div>
                                        <div class="author-meta">
                                            <h4><?php echo esc_html($poster_name . " "); ?><?php if ($is_phone_verified == '1') {
                                                    ?><span class="verified">
                                                    <i class="fas fa-check"></i>
                                            </span>
                                                <?php } ?>
                                            </h4>
                                            <p><?php echo __("Member Since " . date("F j, Y", strtotime($registration_date)), "sb_pro"); ?></p>
                                            <a href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads'); ?>">
                                                <?php echo __("View All Events", "sb_pro"); ?>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="location-box">
                                        <a href="#event-location-container"
                                           class="map"><?php echo __("see map", "sb_pro"); ?></a>
                                        <img src="<?php echo trailingslashit(get_template_directory_uri()) . 'images/location-icon.svg' ?>"
                                             alt="location"><?php echo __(" " . $truncated_location, 'sb_pro'); ?>
                                    </div>
                                    <?php
                                    if ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'phone') {
                                        $call_now = 'javascript:void(0);';
                                        if (wp_is_mobile())
                                            $call_now = 'tel:' . adforest_get_CallAbleNumber(get_post_meta($pid, '_adforest_poster_contact', true));

                                        $contact_num = get_post_meta($pid, '_adforest_poster_contact', true);
                                        $batch_text = '';
                                        if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification']) {
                                            $contact_num = get_user_meta($poster_id, '_sb_contact', true);
                                            if ($contact_num != "") {
                                                if (get_user_meta($poster_id, '_sb_is_ph_verified', true) == '1') {
                                                    $batch_text = __('Verified', 'sb_pro');
                                                } else {
                                                    $batch_text = __('Not verified', 'sb_pro');
                                                }
                                            } else {
                                                $contact_num = get_post_meta($pid, '_adforest_poster_contact', true);
                                                $batch_text = __('Not verified', 'sb_pro');
                                            }
                                        }
                                        if ($contact_num != "") {
                                            if (adforest_showPhone_to_users()) {
                                                $call_now = "javascript:void(0)";
                                                $adforest_login_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : '';
                                                $adforest_login_page = apply_filters('adforest_language_page_id', $adforest_login_page);
                                                if ($adforest_login_page != '') {
                                                    $redirect_url = adforest_login_with_redirect_url_param(adforest_get_current_url());
                                                    $call_now = $redirect_url;
                                                }
                                                ?>
                                                <div class="details-click-view phone">
                                                    <a data-ad-id="<?php echo intval($pid); ?>"
                                                       href="<?php echo adforest_return_echo($call_now); ?>"
                                                       class="sb-click-num2"
                                                       id="show_ph_div"><span class="info-heading"><i
                                                                    class="fa fa-phone"></i><?php echo esc_html__('Phone :', 'sb_pro') ?></span><span
                                                                class="sb-phonenumber"><?php echo esc_html__('Login to view', 'sb_pro'); ?></span><?php if ($batch_text != "") { ?>
                                                            <small
                                                                    class="sb-small">
                                                            -<?php echo adforest_return_echo($batch_text); ?></small><?php } ?>
                                                    </a>
                                                </div>
                                            <?php } else { ?>
                                                <div class="contact-box-main">
                                                    <div class="contact-box whatsapp">
                                                        <div class="icon"><img
                                                                    src="<?php echo trailingslashit(get_template_directory_uri()) . 'images/whatsapp-icon.svg' ?>"
                                                                    alt="icon"></div>
                                                        <div class="meta">
                                                            <small style="cursor: pointer;" class="click-to-toggle"
                                                                   data-ad-id="<?php echo intval($pid); ?>"
                                                                   data-contact-num="<?php echo esc_attr($contact_num); ?>"><?php echo __("Click To Show", "sb_pro"); ?>
                                                            </small>
                                                            <a class="style_2_ph" href="javascript:void(0)">
                                                                <?php
                                                                $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                                echo esc_html($masked_num); ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="contact-box phone-2">
                                                        <div class="icon"><img
                                                                    src="<?php echo trailingslashit(get_template_directory_uri()) . 'images/phone-icon.svg' ?>"
                                                                    alt="icon"></div>
                                                        <div class="meta">
                                                            <small style="cursor: pointer;" class="click-to-toggle"
                                                                   data-ad-id="<?php echo intval($pid); ?>"
                                                                   data-contact-num="<?php echo esc_attr($contact_num); ?>"><?php echo __("Click To Show", "sb_pro"); ?>
                                                            </small>
                                                            <a class="style_2_ph" href="javascript:void(0)">
                                                                <?php
                                                                $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                                echo esc_html($masked_num); ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php }
                                    } ?>
                                </div>
                                <?php
                                $attendee_count = 0;
                                if (is_array($all_attendees)) {
                                    $attendee_count = count($all_attendees);
                                }

                                $attendee_style = "";
                                if (isset($attendee_count) && $attendee_count > 5) {
                                    $attendee_style = 'style="height: 450px; overflow-y: scroll"';
                                }
                                ?>
                                <div class="adt-list-separator-box">
                                    <div class="separator-header">
                                        <h4><?php echo __("Attendees (" . $attendee_count . ")"); ?></h4>
                                    </div>
                                    <div class="separator-body" <?php echo $attendee_style; ?>>
                                        <ul>
                                            <?php
                                            if (is_array($all_attendees) && !empty($all_attendees)) {
                                                $attendee_html = "";

                                                $count = 1;
                                                foreach ($all_attendees as $user) {

                                                    $count++;

                                                    if ($count == 8) {

                                                        break;
                                                    }

                                                    $user_info = get_userdata($user);
                                                    $poster_name = $user_info->display_name;
                                                    $user_pic = adforest_get_user_dp($poster_id);
                                                    $user_address = get_user_meta($poster_id, '_sb_address', true);
                                                    $user_role = get_user_meta($poster_id, '_sb_user_type', true);

                                                    $user_role = $user_role == "Dealer" ? "Dealer" : "Individual";
                                                    ?>
                                                    <li>
                                                        <div class="user">
                                                            <img src="<?php echo esc_url($user_pic, "sb_pro"); ?>"
                                                                 alt="attendie-img">
                                                            <h5><?php echo __($poster_name); ?></h5>
                                                        </div>
                                                        <small><?php echo __($user_role); ?></small>
                                                    </li>
                                                <?php }
                                            } ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="adt-list-separator-box">
                                    <div class="adt-seller-detail-sidebar">
                                        <div class="bottom-meta">
                                            <?php
                                            if (isset($adforest_theme['user_contact_form_event']) && $adforest_theme['user_contact_form_event']) {

                                                $captcha_type = isset($adforest_theme['google-recaptcha-type']) && !empty($adforest_theme['google-recaptcha-type']) ? $adforest_theme['google-recaptcha-type'] : 'v2';
                                                $site_key = isset($adforest_theme['google_api_key']) && !empty($adforest_theme['google_api_key']) ? $adforest_theme['google_api_key'] : '';
                                                $contact_form_recaptcha = isset($adforest_theme['contact_form_recaptcha_event']) && !empty($adforest_theme['contact_form_recaptcha_event']) ? $adforest_theme['contact_form_recaptcha_event'] : '';
                                                ?>
                                                <h4 class="main-title text-left"><?php echo __('Contact Us', 'sb_pro'); ?></h4>
                                                <form class="send-message-to-author">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control" name="userName"
                                                               aria-describedby="nameHelp"
                                                               placeholder="<?php echo __('Name', 'sb_pro'); ?>"
                                                               data-parsley-required="true"
                                                               data-parsley-error-message="<?php echo __('This field is required.', 'sb_pro'); ?>">
                                                        <small id="nameHelp" class="form-text text-muted"></small>
                                                    </div>
                                                    <div class="form-group">
                                                        <input type="email" class="form-control" name="emailAddress"
                                                               aria-describedby="emailHelp"
                                                               placeholder="<?php echo __('Email', 'sb_pro'); ?>"
                                                               data-parsley-required="true"
                                                               data-parsley-error-message="<?php echo __('This field is required.', 'sb_pro'); ?>">
                                                        <small id="emailHelp" class="form-text text-muted"></small>
                                                    </div>
                                                    <div class="form-group">
                                                        <input type="text" class="form-control" name="phoneNumber"
                                                               aria-describedby="phonelHelp"
                                                               placeholder="<?php echo __('Phone Number', 'sb_pro'); ?>"
                                                               data-parsley-required="true"
                                                               data-parsley-error-message="<?php echo __('Format should be +CountrycodePhonenumber.', 'sb_pro'); ?>"
                                                               data-parsley-pattern="/\+[0-9]+$/">
                                                        <small id="phonelHelp" class="form-text text-muted"></small>
                                                    </div>
                                                    <div class="form-group">
                                                        <textarea class="form-control" name="message" rows="4"
                                                                  placeholder="<?php echo __('Message', 'sb_pro'); ?>"
                                                                  data-parsley-required="true"
                                                                  data-parsley-error-message="<?php echo __('This field is required.', 'sb_pro'); ?>"></textarea>
                                                    </div>
                                                    <?php
                                                    $captcha = '<input type="hidden" value="no" name="is_captcha" />';

                                                    if (isset($contact_form_recaptcha) && $contact_form_recaptcha) {
                                                        if ($captcha_type == 'v2') {
                                                            if ($site_key != "") {
                                                                $captcha = '<div class="form-group"><div class="g-recaptcha" data-sitekey="' . $site_key . '"></div></div><input type="hidden" value="yes" name="is_captcha" />';
                                                            }
                                                        } else {
                                                            $captcha = '<input type="hidden" value="yes" name="is_captcha" />';
                                                        }
                                                    }
                                                    echo adforest_return_echo($captcha);
                                                    ?>
                                                    <div class="sellers-button-group">
                                                        <button
                                                                class="adt-button-dark"><?php echo __('Send Message', 'sb_pro'); ?></button>
                                                    </div>
                                                    <input type="hidden" name="ad_id" value="<?php echo $event_id; ?>">
                                                </form>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    </section>


<?php
if (isset($adforest_theme['event_share_allow']) && $adforest_theme['event_share_allow']) {
    wc_get_template2('single-event/share-event.php');
}
/* review reply form modal box */
$flip_it = 'text-left';
if (is_rtl()) {
    $flip_it = 'text-right';
}
?>

    <div class="modal fade event_reply_rating" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <form id="event_rating_reply_form">
                <div class="modal-content <?php echo esc_attr($flip_it); ?>">
                    <div class="modal-header">
                        <button type="button" class="close" data-bs-dismiss="modal"><span
                                    aria-hidden="true">&#10005;</span><span class="sr-only"></span></button>
                        <div class="modal-title"><?php echo __('Reply to', 'sb_pro'); ?> <span
                                    id="reply_to_rating"></span>
                        </div>
                    </div>
                    <div class="modal-body <?php echo esc_attr($flip_it); ?>">
                        <div class="form-group  col-md-12 col-sm-12">
                            <label></label>
                            <textarea placeholder="<?php echo __('Write your reply...', 'sb_pro'); ?>" rows="3"
                                      class="form-control" name="reply_comments" data-parsley-required="true"
                                      data-parsley-error-message="<?php echo __('This field is required.', 'sb_pro'); ?>"></textarea>
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-12 col-sm-12 margin-bottom-20 margin-top-20">
                            <input type="hidden" id="sb-review-reply-token"
                                   value="<?php echo wp_create_nonce('sb_review_reply_secure'); ?>"/>
                            <input type="hidden" id="parent_comment_id" value="0" name="parent_comment_id"/>
                            <input type="hidden" value="<?php echo adforest_return_echo($pid); ?>" name="ad_id"/>
                            <input type="hidden" value="<?php echo adforest_return_echo($poster_id); ?>"
                                   name="ad_owner"/>
                            <button type="submit" class="btn btn-theme btn-block">
                                <?php echo __('Submit', 'sb_pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php wc_get_template2('single-event/share-map.php'); ?>
<?php wc_get_template2('single-event/rearrange-notification.php'); ?>