<?php
global $adforest_theme;
$pid = get_the_ID();
$type = $adforest_theme['cat_and_location'];
$cat_link_page = isset($adforest_theme['cat_and_location']) ? ($adforest_theme['cat_and_location']) : 'search';
$poster_id = get_post_field('post_author', $pid);
$show_ad_id = isset($adforest_theme['sb_show_ad_id']) ? $adforest_theme['sb_show_ad_id'] : "";
$user_info = get_userdata($poster_id);
$registration_date = $user_info->user_registered;
$user_type = "";
if (get_user_meta($poster_id, '_sb_user_type', true) == 'Indiviual') {
    $user_type = esc_html__('Individual', 'adforest');
} else if (get_user_meta($poster_id, '_sb_user_type', true) == 'Dealer') {
    $user_type = esc_html__('Dealer', 'adforest');
}
$poster_name = get_post_meta($pid, '_adforest_poster_name', true);

if ($poster_name == "") {
    $user_info = get_userdata($poster_id);
    $poster_name = $user_info->display_name;
}

$user_pic = adforest_get_user_dp($poster_id);
$allow_whatsapp = $adforest_theme['sb_ad_whatsapp_chat'] ?? false;
$allow_sb_chat = $adforest_theme['sb_ad_sbchat_chat'] ?? false;
$horizontal_ad = $adforest_theme['style_ad_720_1'] ?? "";
$horizontal_ad_2 = $adforest_theme['style_ad_720_2'] ?? "";

$ad_type = get_post_meta($pid, "_adforest_ad_type", true) ?? "";
$ad_tagline = get_post_meta($pid, "_adforest_ad_tagline", true) ?? "";
$ad_condition_val = wp_get_post_terms($pid, 'ad_condition');
$ad_condition_val = (!is_wp_error($ad_condition_val) && !empty($ad_condition_val) && isset($ad_condition_val[0]->name)) ? $ad_condition_val[0]->name : get_post_meta($pid, '_adforest_ad_condition', true);

$posted_at = get_the_date();
$posted_time = get_the_time('U', get_the_ID());
$ad_posted_date = adforest_get_ad_posted_date($posted_time);

$ad_location = get_post_meta(get_the_ID(), '_adforest_ad_location', true);
$truncated_location = truncate_string($ad_location, 15);

$contact_num = get_post_meta($pid, '_adforest_poster_contact', true);
if ($contact_num == "") {
    $contact_num = get_user_meta($poster_id, '_sb_contact', true);
}
$ad_status = get_post_meta($pid, '_adforest_ad_status_', true);
$adforest_post_ad_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page']);
$ad_update_url = adforest_set_url_param(get_the_permalink($adforest_post_ad_page), 'id', $pid);
$adt_container_class = "";
if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
    $adt_container_class = "adt-container";
}
$show_side_menu = isset($adforest_theme['sb_style_1_side_menu']) ? $adforest_theme['sb_style_1_side_menu'] : "";

$is_price_enabled = is_ad_price_enabled($pid);
$ad_selected_warranty = wp_get_post_terms($pid, 'ad_warranty');
$ad_warranty_name = (!is_wp_error($ad_selected_warranty) && !empty($ad_selected_warranty) && isset($ad_selected_warranty[0]->name))
    ? $ad_selected_warranty[0]->name
    : '';

$ad_selected_condition = wp_get_post_terms($pid, 'ad_condition');
$ad_condition_name = (!is_wp_error($ad_selected_condition) && !empty($ad_selected_condition) && isset($ad_selected_condition[0]->name))
    ? $ad_selected_condition[0]->name
    : '';
$ad_website = get_post_meta($pid, '_adforest_ad_website', true);
$adf_bidding_gate_ok = function_exists('adforest_should_show_bidding') ? adforest_should_show_bidding($pid) : true;
?>
    <section class="adt-ad-detail-section ABCCSS">
        <div class="container <?php echo esc_attr($adt_container_class); ?>">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-ad-detail-content-wrapper content-center">
                        <?php if ($show_side_menu == 1) { ?>
                            <div class="left-side-bar">
                                <div class="adt-detail-content-list sticky">
                                    <ul>
                                        <li>
                                            <a class="ad-content-item active"
                                               href="#adt-ad-detail-top-box"><?php echo esc_html__("Top", "adforest"); ?></a>
                                        </li>
                                        <li>
                                            <a class="ad-content-item"
                                               href="#adt-ad-general-info-box"><?php echo esc_html__("General Info", "adforest"); ?></a>
                                        </li>
                                        <li>
                                            <a class="ad-content-item"
                                               href="#adt-ad-description-box"><?php echo esc_html__("Description", "adforest"); ?></a>
                                        </li>
                                        <li>
                                            <a class="ad-content-item"
                                               href="#adt-ad-location-box"><?php echo esc_html__("Location", "adforest"); ?></a>
                                        </li>
                                        <?php
                                        $bidding_enabled = $adf_bidding_gate_ok ? get_post_meta($pid, '_adforest_ad_bidding', true) : 0;
                                        if ($bidding_enabled == 1) {
                                            ?>
                                            <li>
                                                <a class="ad-content-item"
                                                   href="#adt-ad-biding-box"><?php echo esc_html__("Bids", "adforest"); ?></a>
                                            </li>
                                        <?php }
                                        if (isset($adforest_theme['sb_ad_rating']) && $adforest_theme['sb_ad_rating']) {
                                            ?>
                                            <li>
                                                <a class="ad-content-item"
                                                   href="#adt-ad-review-box"><?php echo esc_html__("Write A Review", "adforest"); ?></a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                                <?php if (is_active_sidebar('adforest_vertical_advert')) : ?>
                                    <div class="adt-vertical-ad-box">
                                        <?php dynamic_sidebar('adforest_vertical_advert'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php } ?>
                        <div class="ad-detail-middle-content">
                            <?php get_template_part('template-parts/layouts/ad-style/rearrange', 'notification'); ?>
                            <?php
                            if (isset($adforest_theme['sb_show_recently_viewed_on_ad_detail']) && $adforest_theme['sb_show_recently_viewed_on_ad_detail'] == 1) {
                                $has_viewed_before = is_recently_viewed_ad_post($pid);

                                add_recently_viewed_ad_post($pid);

                                if (isset($adforest_theme['sb_show_recently_viewed_on_ad_detail']) && $adforest_theme['sb_show_recently_viewed_on_ad_detail'] == 1) {
                                    if ($has_viewed_before) {
                                        echo adforest_recently_viewed_ad($pid);
                                    }
                                }
                            } else {
                                $views = (int)get_post_meta($pid, 'adforest_ad_views', true);
                                update_post_meta($pid, 'adforest_ad_views', $views + 1);
                            }
                            ?>
                            <?php if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && get_current_user_id() != "" && get_post_meta($pid, '_adforest_is_feature', true) == '0' && get_post_meta($pid, '_adforest_ad_status_', true) == 'active') { ?>
                                <span>
                                    <?php get_template_part('template-parts/layouts/ad-style/feature', 'notification'); ?>
                                </span>
                            <?php } ?>
                            <div id="adt-ad-detail-top-box" class="ad-detail-top-box">
                                <?php get_template_part('template-parts/layouts/ad-style/ad-img', 'carousel'); ?>
                                <?php get_template_part('template-parts/layouts/ad-style/status', 'watermark'); ?>
                            </div>
                            <div class="ad-about-box">
                                <div class="top-rating-box">
                                    <?php
                                    if (isset($adforest_theme['sb_ad_rating']) && $adforest_theme['sb_ad_rating']) {
                                        get_template_part('template-parts/layouts/ad-style/ad', 'rating');
                                    }
                                    ?>
                                    <div class="adt-ad-views-box">
                                        <?php
                                        if (current_user_can('administrator') || get_current_user_id() == $poster_id) { ?>
                                            <a class="post-edit-button" href="<?php echo esc_url($ad_update_url); ?>">
                                                <?php echo esc_html__("Edit", "adforest"); ?>
                                            </a>
                                        <?php } ?>
                                        <div class="views">
                                            <?php
                                            $ad_views = get_post_meta($pid, 'adforest_ad_views', true);
                                            if ($ad_views >= 1000) {
                                                $formatted_views = number_format($ad_views / 1000, 1) . 'K';
                                            } else {
                                                $formatted_views = $ad_views;
                                            }
                                            echo esc_html__("Views: ", 'adforest');
                                            ?>
                                            <span><?php echo esc_html($formatted_views); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <h3><?php echo esc_html(get_the_title()) ?></h3>
                                <?php if ($ad_tagline != "") { ?>
                                    <p class="ad-tagline"><?php echo esc_html($ad_tagline); ?></p>
                                <?php } ?>
                                <?php
                                $ad_details = get_ad_post_details($pid);
                                $ad_category_selected = $ad_details['categories'];
                                $category_links = [];

                                foreach ($ad_category_selected as $category) {
                                    $category_url = adforest_cat_link_page($category->term_id, $cat_link_page, 'ad_cats');
                                    if (!is_wp_error($category_url)) {
                                        $category_links[] = '<a style="color:#777777" class="ctg-tag" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
                                    }
                                }

                                $category_links_string = implode(' > ', $category_links);
                                ?>
                                <p><?php echo wp_kses_post($category_links_string); ?></p>
                                <?php
                                $ad_details = get_ad_post_details($pid);
                                $ad_country_selected = $ad_details['countries'];
                                $category_links = [];
                                if (is_array($ad_country_selected) && count($ad_country_selected) > 0) {
                                    foreach ($ad_country_selected as $country) {
                                        $country_url = adforest_cat_link_page($country->term_id, $cat_link_page, 'ad_country');
                                        if (!is_wp_error($country_url)) {
                                            $country_links[] = '<a style="color:#777777" class="ctg-tag" href="' . esc_url($country_url) . '">' . esc_html($country->name) . '</a>';
                                        }
                                    }

                                    $country_links_string = implode(' > ', $country_links);
                                    ?>
                                    <div class="adt-ad-info-box">
                                        <p>
                                            <strong><?php echo esc_html__("Country: ", "adforest") ?></strong><?php echo wp_kses_post($country_links_string); ?>
                                        </p>
                                    </div>
                                <?php } ?>
                                <div class="row">
                                    <?php if ($ad_warranty_name != "") { ?>
                                        <div class="adt-ad-info-box col-md-6">
                                            <p>
                                                <strong><?php echo esc_html__("Warranty", "adforest") ?></strong><?php echo " " . esc_html($ad_warranty_name); ?>
                                            </p>
                                        </div>
                                    <?php }
                                    if ($ad_condition_name) { ?>
                                        <div class="adt-ad-info-box col-md-6">
                                            <p>
                                                <strong><?php echo esc_html__("Condition:", "adforest") ?></strong><?php echo " " . esc_html($ad_condition_name); ?>
                                            </p>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="more-detail-box">
                                    <ul>
                                        <li>
                                        <span><i class="fas fa-map-marker-alt"></i><?php echo esc_html($truncated_location); ?><a
                                                    href="#adt-ad-location-box"><?php echo esc_html__("See map", "adforest"); ?></a></span>
                                        </li>
                                        <li>
                                            <span><i class="fas fa-calendar-week"></i><?php echo esc_html($ad_posted_date); ?></span>
                                        </li>
                                    </ul>
                                    <ul class="social-link">
                                        <li>
                                            <a href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads'); ?>"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               title="<?php echo esc_attr__("Click to View Ad Owner", "adforest") ?>">
                                                <i class="far fa-user"></i>
                                            </a>
                                        </li>
                                        <?php if (isset($adforest_theme['share_ads_on']) && $adforest_theme['share_ads_on']) { ?>
                                            <li>
                                                <a data-bs-toggle="modal" data-bs-target=".share-ad"
                                                   data-adid="<?php echo get_the_ID(); ?>"
                                                   href="javascript:void(0);"
                                                   data-toggle="tooltip"
                                                   data-placement="top"
                                                   title="<?php echo esc_attr__("Click to Share Ad", "adforest") ?>">
                                                    <i class="fas fa-share-alt"></i>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <?php
                                        $is_fav      = ( get_user_meta( get_current_user_id(), '_sb_fav_id_' . $pid, true ) == $pid );
                                        $heart_class = $is_fav ? 'fas fa-heart text-danger' : 'far fa-heart';
                                        $fav_title   = $is_fav ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                                        $fav_extra   = $is_fav ? ' ad-favourited' : '';
                                        ?>
                                        <li>
                                            <a class="ad_to_fav<?php echo esc_attr( $fav_extra ); ?>" data-adid="<?php echo get_the_ID(); ?>"
                                               href="javascript:void(0);"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               title="<?php echo esc_attr( $fav_title ); ?>"
                                               aria-label="<?php echo esc_attr( $fav_title ); ?>">
                                                <i class="<?php echo esc_attr($heart_class); ?>"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a data-bs-target=".report-quote" data-bs-toggle="modal"
                                               data-adid="<?php echo get_the_ID(); ?>"
                                               href="javascript:void(0);"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               title="<?php echo esc_attr__("Click to Report Ad", "adforest") ?>">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <?php if ($show_ad_id) { ?>
                                    <div class="adt-ad-id">
                                        <p><?php echo esc_html__("ID: ", "adforest") . get_the_ID(); ?></p>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="adt-product-or-seller-mobile-box">
                                <?php if ($is_price_enabled) { ?>
                                    <div class="ad-price-box">
                                        <i class="fas fa-tag"></i>
                                        <?php
                                        $price_type = get_post_meta($pid, '_adforest_ad_price_type', true);
                                        if ($price_type == "no_price") { ?>
                                            <h3><?php echo esc_html__("No Price", "adforest"); ?></h3>
                                        <?php } else {
                                            echo '<h3>' . adforest_adPrice($pid, 'negotiable-single', '') . '</h3>';
                                        }
                                        ?>
                                        <i class="fas fa-tag sm-tag"></i>
                                    </div>

                                <?php } ?>
                                <?php
                                if (isset($adforest_theme['sb_enable_comments_offer']) && $adforest_theme['sb_enable_comments_offer'] && $adf_bidding_gate_ok) {
                                    $bid_date = get_post_meta($pid, '_adforest_ad_bidding_date', true);
                                    if (!$bid_date) {
                                        $bid_date = date('Y-m-d', strtotime('+7 days', current_time('timestamp')));
                                    }
                                    $bidding_enabled = $adf_bidding_gate_ok ? get_post_meta($pid, '_adforest_ad_bidding', true) : 0;
                                    if (isset($adforest_theme['bidding_timer']) && $adforest_theme['bidding_timer'] == '1') {
                                        if (($bidding_enabled == 1 || $bidding_enabled == "1") && strtotime($bid_date) >= current_time('timestamp')) { ?>
                                            <div class="ad-bidding-timer-box">
                                                <div class="ad-bidding-timer-box-inner">
                                                    <h5><?php echo esc_html__("Bidding Ends In", "adforest"); ?></h5>
                                                    <!-- Added data-biddate attribute -->
                                                    <div class="adt-deal-countdown-desc-clock-mobile"
                                                         data-biddate="<?php echo esc_attr($bid_date); ?>">
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                                <span class="adt-countdown-clock-title"
                                                                      id="days">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Days", "adforest"); ?></span>
                                                        </div>
                                                        <div class="adt-coundown-svg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                        </div>
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                                <span class="adt-countdown-clock-title"
                                                                      id="hours">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Hours", "adforest"); ?></span>
                                                        </div>
                                                        <div class="adt-coundown-svg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                        </div>
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                            <span class="adt-countdown-clock-title"
                                                                  id="minutes">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Minutes", "adforest"); ?></span>
                                                        </div>
                                                        <div class="adt-coundown-svg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                        </div>
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                            <span class="adt-countdown-clock-title"
                                                                  id="seconds">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Seconds", "adforest"); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php }
                                    }
                                } ?>
                                <div class="ad-owner-detail-box">
                                    <?php
                                    $is_phone_verified = get_user_meta($poster_id, '_sb_is_ph_verified', true);
                                    $verified_class = '';
                                    if ($is_phone_verified == '1') {
                                        $verified_class = 'verified';
                                    }
                                    ?>
                                    <span class="<?php echo esc_attr($verified_class); ?>">
                                         <?php if ($is_phone_verified == '1') {
                                             ?>
                                             <i class="fas fa-check"></i>
                                             <?php
                                         }
                                         ?>
                                    </span>
                                    <?php if ($user_type != "") { ?>
                                        <span class="owner-status"><?php echo adforest_return_echo($user_type); ?></span>
                                    <?php } ?>
                                    <a class="m-0"
                                       href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads'); ?>">
                                        <img src="<?php echo esc_attr($user_pic); ?>" alt="owner-img">
                                    </a>
                                    <h4><?php echo esc_html($poster_name); ?></h4>
                                    <p>
                                        <?php
                                        printf(
                                            esc_html__('Member Since %s', 'adforest'),
                                            esc_html(date_i18n(get_option('date_format'), strtotime($registration_date)))
                                        );
                                        ?>
                                    </p>
                                    <a class="view-all-ads-text"
                                       href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads'); ?>">
                                        <?php echo esc_html__("View All Ads", "adforest"); ?>
                                    </a>
                                    <?php
                                    if ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'phone') {
                                        $call_now = 'javascript:void(0);';
                                        if (wp_is_mobile()) {
                                            $call_now = 'tel:' . adforest_get_CallAbleNumber(get_post_meta($pid, '_adforest_poster_contact', true));
                                        }

                                        $batch_text = '';
                                        if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification']) {
                                            if ($contact_num != "") {
                                                if (get_user_meta($poster_id, '_sb_is_ph_verified', true) == '1') {
                                                    $batch_text = esc_html__('Verified', 'adforest');
                                                } else {
                                                    $batch_text = esc_html__('Not verified', 'adforest');
                                                }
                                            } else {
                                                $batch_text = esc_html__('Not verified', 'adforest');
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

                                                $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                ?>

                                                <!-- Phone Box -->
                                                <div class="contact-detail-box">
                                                    <div class="icon-box">
                                                        <i class="fas fa-phone"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <a href="<?php echo esc_url($call_now); ?>"
                                                           style="text-decoration: none;">
                                                            <small style="cursor: pointer;"
                                                                   class="toggle-contact-number">
                                                                <?php echo esc_html__("Login to View", "adforest"); ?>
                                                            </small>
                                                            <span class="phone-number">
                                                                <?php echo esc_html($masked_num); ?>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>

                                                <!-- WhatsApp Box -->
                                                <div class="contact-detail-box green">
                                                    <div class="icon-box">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <a href="<?php echo esc_url($call_now); ?>"
                                                           style="text-decoration: none;">
                                                            <small style="cursor: pointer;"
                                                                   class="toggle-contact-number">
                                                                <?php echo esc_html__("Login to View", "adforest"); ?>
                                                            </small>
                                                            <span class="phone-number">
                                                                <?php echo esc_html($masked_num); ?>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>

                                            <?php } else { ?>
                                                <div class="contact-detail-box">
                                                    <div class="icon-box">
                                                        <i class="fas fa-phone"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <small style="cursor: pointer;" class="toggle-contact-number"
                                                               data-ad-id="<?php echo intval($pid); ?>">
                                                            <?php echo esc_html__("Click To Show", "adforest"); ?>
                                                        </small>
                                                        <span class="phone-number">
                                                        <?php
                                                        $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                        echo esc_html($masked_num);
                                                        ?>
                                                    </span>
                                                    </div>
                                                </div>
                                                <div class="contact-detail-box green">
                                                    <div class="icon-box">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <small style="cursor: pointer;" class="toggle-contact-number"
                                                               data-ad-id="<?php echo intval($pid); ?>"
                                                        ><?php echo esc_html__("Click To Show", "adforest"); ?>
                                                        </small>
                                                        <span class="phone-number">
                                                        <?php
                                                        // Mask the last 5 digits with 'x'
                                                        $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                        echo esc_html($masked_num);
                                                        ?>
                                                    </span>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php }
                                    } ?>
                                    <?php if ($allow_whatsapp && $contact_num != "") {
                                        $contact_num = preg_replace('/[^\dxX]/', '', $contact_num);
                                        $redirect_url = '';
                                        if (isset($adforest_theme['sb_show_whatsapp_intro']) && $adforest_theme['sb_show_whatsapp_intro'] == '1') {
                                            $post_link = get_permalink($pid);
                                            $whatsapp_intro = get_user_meta(get_current_user_id(), '_sb_user_whatsapp_intro', true);
                                            $ad_link_text = "Ad Link: " . $post_link;
                                            $redirect_url = "https://api.whatsapp.com/send?phone=$contact_num&text=" . urlencode($ad_link_text) . "%0a" . urlencode($whatsapp_intro);
                                        } else {
                                            $redirect_url = "https://api.whatsapp.com/send?phone=$contact_num";
                                        }

                                        $whatsapp_text = esc_html__("Message on WhatsApp", 'adforest');
                                        if (adforest_showPhone_to_users()) {
                                            $adforest_login_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : '';
                                            $adforest_login_page = apply_filters('adforest_language_page_id', $adforest_login_page);
                                            if ($adforest_login_page != '') {
                                                $redirect_url = adforest_login_with_redirect_url_param(adforest_get_current_url());
                                                $whatsapp_text = esc_html__("Login to Chat", 'adforest');
                                            }
                                        }
                                        ?>
                                        <a style="text-decoration: none" href="<?php echo esc_url($redirect_url); ?>"
                                           class="btn btn-whatsap btn-block"
                                           target="_blank">
                                            <i class="fab fa-whatsapp"></i>
                                            <span class=""><?php echo esc_html($whatsapp_text); ?></span>
                                        </a>
                                    <?php } ?>
                                    <?php
                                    if (($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'message') && $ad_status == 'active') {
                                        if (class_exists('SB_Chat') && $allow_sb_chat == "1") {
                                            $sb_plugin_options = get_option('sb_plugin_options', array());
                                            if (isset($sb_plugin_options['sbChat-active']) && $sb_plugin_options['sbChat-active'] == 1 && class_exists('SB_Chat_Setting_Page')) {
                                                $classes = 'adt-button-dark text-decoration-none';
                                                $button_text = esc_html__('Start Chat', 'adforest');
                                                echo do_shortcode(
                                                    '[sb_chat_shortcode_popup '
                                                    . 'class="' . esc_attr($classes) . '" '
                                                    . 'button_title="' . esc_attr($button_text) . '" '
                                                    . 'post_author_id="' . absint($poster_id) . '" '
                                                    . 'post_id="' . absint($pid) . '" '
                                                    . ']'
                                                );
                                            }
                                        }
                                    } ?>
                                    <?php if ( isset( $ad_website ) && ! empty( $ad_website ) ) { ?>
                                        <div class="adt-website_link">
                                            <a href="<?php echo esc_url( $ad_website ); ?>" target="_blank" rel="noopener noreferrer">
                                                <i class="fas fa-globe"></i>
                                                <?php echo esc_html( preg_replace( '#^https?://#', '', $ad_website ) ); ?>
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php
                            $adf_show_ad_720_1 = function_exists( 'adforest_has_visible_ad_content' )
                                ? adforest_has_visible_ad_content( 'style_ad_720_1' )
                                : ( $horizontal_ad != "" );
                            if ( $adf_show_ad_720_1 ) { ?>
                                <div class="adt-horizontal-ad-box">
                                    <?php
                                    if ( function_exists( 'adforest_render_theme_ad' ) ) {
                                        adforest_render_theme_ad( 'style_ad_720_1' );
                                    } else {
                                        echo wp_kses_post( $horizontal_ad );
                                    }
                                    ?>
                                </div>
                            <?php } ?>
                            <div id="adt-ad-general-info-box" class="adt-ad-general-info">
                                <ul>
                                    <?php if (!empty($ad_type)) { ?>
                                        <li>
                                            <span><?php echo esc_html__("Type:", "adforest"); ?></span>
                                            <small><?php echo esc_html($ad_type); ?></small>
                                        </li>
                                    <?php } ?>
                                    <?php
                                    if ($ad_condition_val != "" && isset($adforest_theme['allow_tax_condition']) && $adforest_theme['allow_tax_condition']) { ?>
                                        <li>
                                            <span><?php echo esc_html__("Condition:", "adforest"); ?></span>
                                            <small><?php echo esc_html($ad_condition_val); ?></small>
                                        </li>
                                    <?php } ?>
                                    <!--Displaying Custom fields-->
                                    <?php
                                    if (function_exists('adforestCustomFieldsHTML')) {
                                        print_r(adforestCustomFieldsHTML($pid, '', 'style-6'));

                                    }
                                    ?>
                                    <!--Displaying Custom Fields-->
                                </ul>
                            </div>
                            <div id="adt-ad-description-box" class="adt-ad-description">
                                <h4><?php echo esc_html__("Description:", "adforest"); ?></h4>
                                <p><?php echo wp_kses_post(get_the_content()); ?></p>
                                <?php do_action('adforest_owner_text'); ?>
                                <?php get_template_part('template-parts/layouts/ad-style/ad', 'tags'); ?>
                            </div>
                            <?php
                            $adf_show_ad_720_2 = function_exists( 'adforest_has_visible_ad_content' )
                                ? adforest_has_visible_ad_content( 'style_ad_720_2' )
                                : ( $horizontal_ad_2 != "" );
                            if ( $adf_show_ad_720_2 ) { ?>
                                <div class="adt-horizontal-ad-box">
                                    <?php
                                    if ( function_exists( 'adforest_render_theme_ad' ) ) {
                                        adforest_render_theme_ad( 'style_ad_720_2' );
                                    } else {
                                        echo wp_kses_post( $horizontal_ad_2 );
                                    }
                                    ?>
                                </div>
                            <?php } ?>
                            <?php
                            if (get_post_meta($pid, '_adforest_ad_yvideo', true) != "") {
                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', get_post_meta($pid, '_adforest_ad_yvideo', true), $match);

                                if (isset($match[1]) && $match[1] != "") {
                                    $video_id = $match[1];
                                    ?>
                                    <div class="ad-detail-video">
                                        <div id="video">
                                            <h4><?php echo esc_html__("Video:", "adforest"); ?></h4>
                                            <?php
                                            $iframe = 'iframe';
                                            echo '<' . $iframe . ' width="560" height="450" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></' . $iframe . '>';
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                            <?php if (isset($adforest_theme['allow_lat_lon']) && $adforest_theme['allow_lat_lon']) {
                                $map_lat = get_post_meta($pid, '_adforest_ad_map_lat', true);
                                $map_long = get_post_meta($pid, '_adforest_ad_map_long', true); ?>

                                <div id="adt-ad-location-box" class="adt-ad-location-box">
                                    <div class="top-box">
                                        <h4><?php echo esc_html__("Location:", "adforest") ?></h4>
                                        <p>
                                            <i class="fas fa-location-arrow"></i><?php echo esc_html($ad_location) ?>
                                        </p>
                                    </div>
                                    <?php
                                    if ($map_lat != "" && $map_long != "") {
                                        ?>
                                        <div id="itemMap" style="width: 100%; height: 370px; margin-bottom:5px;"></div>
                                        <input type="hidden" id="ad_lat" value="<?php echo esc_attr($map_lat); ?>"/>
                                        <input type="hidden" id="ad_lon" value="<?php echo esc_attr($map_long); ?>"/>
                                        <?php
                                    } else {
                                        $res_arr = adforest_get_latlon($ad_location);
                                        if (isset($res_arr) && count($res_arr) > 0) {
                                            ?>
                                            <div id="itemMap"
                                                 style="width: 100%; height: 370px; margin-bottom:5px;"></div>
                                            <input type="hidden" id="ad_lat"
                                                   value="<?php echo esc_attr($res_arr[0]); ?>"/>
                                            <input type="hidden" id="ad_lon"
                                                   value="<?php echo esc_attr($res_arr[1]); ?>"/>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            <?php } ?>
                            <?php
                            if (isset($adforest_theme['sb_enable_comments_offer']) && $adforest_theme['sb_enable_comments_offer'] && $adf_bidding_gate_ok) {
                                $bids_res = adforest_get_all_biddings_array($pid);
                                $total_bids = count($bids_res);

                                $bid_container_height = '';
                                if ($total_bids > 3) {
                                    $bid_container_height = 'height: 400px;';
                                }

                                $max = 0;
                                $min = 0;
                                if ($total_bids > 0) {
                                    $max = max($bids_res);
                                    $min = min($bids_res);
                                }

                                $thousands_sep = ",";
                                if (isset($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator'] != '') {
                                    $thousands_sep = $adforest_theme['sb_price_separator'];
                                }
                                $decimals = 0;
                                if (isset($adforest_theme['sb_price_decimals']) && $adforest_theme['sb_price_decimals'] != '') {
                                    $decimals = $adforest_theme['sb_price_decimals'];
                                }
                                $decimals_separator = ".";
                                if (isset($adforest_theme['sb_price_decimals_separator']) && $adforest_theme['sb_price_decimals_separator'] != '') {
                                    $decimals_separator = $adforest_theme['sb_price_decimals_separator'];
                                }

                                $curreny = $adforest_theme['sb_currency'];
                                if (get_post_meta($pid, '_adforest_ad_currency', true) != "") {
                                    $curreny = get_post_meta($pid, '_adforest_ad_currency', true);
                                }

                                // Price format
                                $max = number_format((int)$max, $decimals, $decimals_separator, $thousands_sep);
                                $min = number_format((int)$min, $decimals, $decimals_separator, $thousands_sep);

                                $min = substr($min, 0, 12);
                                $max = substr($max, 0, 12);

                                if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right') {
                                    $max = $max . '<small>' . $curreny . '</small>';
                                    $min = $min . '<small>' . $curreny . '</small>';
                                } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left') {
                                    $max = '<small>' . $curreny . '</small>' . $max;
                                    $min = '<small>' . $curreny . '</small>' . $min;
                                } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right_with_space') {
                                    $max = $max . ' <small>' . $curreny . '</small>';
                                    $min = $min . ' <small>' . $curreny . '</small>';
                                } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left_with_space') {
                                    $max = '<small>' . $curreny . '</small> ' . $max;
                                    $min = '<small>' . $curreny . '</small> ' . $min;
                                } else {
                                    $max = '<small>' . $curreny . '</small>' . $max;
                                    $min = '<small>' . $curreny . '</small>' . $min;
                                }
                                $bid_end_date = get_post_meta($pid, '_adforest_ad_bidding_date', true);
                                $bidding_enabled = $adf_bidding_gate_ok ? get_post_meta($pid, '_adforest_ad_bidding', true) : 0;
                                if ($bidding_enabled == 1 || $bidding_enabled == "1") {
                                    ?>
                                    <div id="adt-ad-biding-box" class="adt-ad-biding-box">
                                        <h4><?php echo esc_html__("Bidding State", "adforest"); ?></h4>
                                        <div class="bid-detail-wrapper">
                                            <div class="bid-detail-box purple">
                                                <small><?php echo esc_html__("Total Bids", "adforest"); ?></small>
                                                <span><?php echo esc_html($total_bids); ?></span>
                                            </div>
                                            <div class="bid-detail-box green">
                                                <small><?php echo esc_html__("Highest bid", "adforest"); ?></small>
                                                <span><?php echo wp_kses_post($max); ?></span>
                                            </div>
                                            <div class="bid-detail-box yellow">
                                                <small><?php echo esc_html__("Lowest bid", "adforest"); ?></small>
                                                <span><?php echo wp_kses_post($min); ?></span>
                                            </div>
                                        </div>
                                        <div class="bids-list-wrapper"
                                             style="<?php echo esc_attr($bid_container_height); ?>">
                                            <ul class="bids-list">
                                                <?php
                                                arsort($bids_res);
                                                $count = 1;
                                                if ($total_bids > 0) {
                                                    foreach ($bids_res as $key => $val) {
                                                        $data = explode('_', $key);
                                                        $bidder_id = $data[0] ?? "";
                                                        $bid_date = $data[1] ?? "";
                                                        $bid_comment = $data[2] ?? "";
                                                        $user_info = get_userdata($bidder_id);
                                                        $bidder_name = 'demo';
                                                        $user_profile = 'javascript:void(0);';
                                                        if (isset($user_info->display_name) && $user_info->display_name != "") {
                                                            $bidder_name = $user_info->display_name;
                                                            $user_profile = adforest_set_url_param(get_author_posts_url($bidder_id), 'type', 'ads');
                                                        } else {
                                                            continue;
                                                        }

                                                        $current_user = get_current_user_id();
                                                        $ad_owner = get_post_field('post_author', $pid);
                                                        $cls = '';
                                                        $admin_html = '';
                                                        if ($current_user == $ad_owner && get_post_meta($pid, '_adforest_poster_contact', true) != "") {
                                                            $admin_html = '<time class="date">' . get_user_meta($bidder_id, '_sb_contact', true) . '</time>';
                                                        }
                                                        $val = substr($val, 0, 12);
                                                        $user_pic = adforest_get_user_dp($bidder_id, 'adforest-single-small');
                                                        if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right') {
                                                            $offer = $val . '<small>' . $curreny . '</small>';
                                                        } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left') {
                                                            $offer = '<small>' . $curreny . '</small>' . $val;
                                                        } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right_with_space') {
                                                            $offer = $val . ' <small>' . $curreny . '</small>';
                                                        } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left_with_space') {
                                                            $offer = '<small>' . $curreny . '</small> ' . $val;
                                                        } else {
                                                            $offer = '<small>' . $curreny . '</small>' . $val;
                                                        } ?>

                                                        <li>
                                                            <div class="bid-box">
                                                                <div class="user-img">
                                                                    <img src="<?php echo esc_url($user_pic); ?>"
                                                                         alt="<?php echo esc_attr($bidder_name); ?>">
                                                                </div>

                                                                <div class="user-content">
                                                                    <h4><?php echo esc_html($bidder_name); ?></h4>
                                                                    <div class="price"><?php echo wp_kses_post($offer); ?></div>
                                                                    <ul>
                                                                        <li>
                                                                            <i class="fas fa-calendar-week"></i><?php echo adforest_timeago($bid_date); ?>
                                                                        </li>
                                                                    </ul>
                                                                    <p><?php echo esc_html($bid_comment); ?></p>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php }
                                                } else { ?>
                                                    <div class="alert alert-warning" role="alert">
                                                        <?php echo wp_kses_post(__(" <strong>No bids yet!</strong> This ad has not received any bids.", "adforest")); ?>
                                                    </div>
                                                <?php }
                                                ?>

                                            </ul>
                                        </div>
                                    </div>
                                    <div id="adt-ad-create-bid" class="adt-ad-create-bid">
                                        <?php
                                        $bid_end_date = get_post_meta($pid, '_adforest_ad_bidding_date', true);
                                        if ($bid_end_date != "" && date('Y-m-d H:i:s') > $bid_end_date && isset($adforest_theme['bidding_timer']) && $adforest_theme['bidding_timer']) {
                                            echo '<h4>' . esc_html__("Bids:", 'adforest') . '</h4>';
                                            echo '<em>' . esc_html__('Bidding has been closed.', 'adforest') . '</em>';
                                        } else {
                                            $adf_paid_bidding    = !empty($adforest_theme['sb_make_bidding_paid']);
                                            $adf_current_user_id = get_current_user_id();
                                            $adf_user_credits    = $adf_current_user_id ? get_user_meta($adf_current_user_id, '_sb_paid_biddings', true) : '';
                                            $adf_needs_package   = ($adf_paid_bidding && $adf_current_user_id && ($adf_user_credits === '' || (string) $adf_user_credits === '0'));
                                            $adf_package_url     = function_exists('adforest_get_bidding_package_url') ? adforest_get_bidding_package_url() : home_url('/');
                                            ?>
                                            <h4><?php echo esc_html__("Bids:", "adforest"); ?></h4>
                                            <?php if ($adf_needs_package) : ?>
                                                <div class="adt-bidding-package-cta">
                                                    <a href="<?php echo esc_url($adf_package_url); ?>"
                                                       class="adt-button-dark adt-buy-bidding-package"><?php echo esc_html__('Buy Bidding Package', 'adforest'); ?></a>
                                                </div>
                                            <?php else : ?>
                                                <form role="form" id="sb_bid_ad"
                                                      data-paid-bidding="<?php echo $adf_paid_bidding ? '1' : '0'; ?>"
                                                      data-user-credits="<?php echo esc_attr((string) $adf_user_credits); ?>"
                                                      data-package-url="<?php echo esc_url($adf_package_url); ?>">
                                                    <div class="input-field-box">
                                                        <div class="label">
                                                            <input name="bid_amount"
                                                                   placeholder="<?php echo esc_attr__('Bid', 'adforest'); ?>"
                                                                   type="text" data-parsley-required="true"
                                                                   data-parsley-pattern="/^[0-9]+\.?[0-9]*$/"
                                                                   data-parsley-error-message="<?php echo esc_attr__('only numbers allowed.', 'adforest'); ?>"
                                                                   autocomplete="off" maxlength="12"/>
                                                        </div>
                                                        <div class="input-box">
                                                            <input type="text" name="bid_comment" id="bid_comment"
                                                                   placeholder="<?php echo esc_attr__("Comment", "adforest"); ?>">
                                                            <small><?php echo esc_html__("*Your phone number will be shown to post author", "adforest"); ?></small>
                                                        </div>
                                                        <button class="adt-button-dark"><?php echo esc_html__("Send Now", "adforest"); ?></button>
                                                        <input type="hidden" name="ad_id"
                                                               value="<?php echo esc_attr($pid) ?>"/>
                                                        <input type="hidden" id="sb-bidding-token"
                                                               value="<?php echo wp_create_nonce('sb_bidding_secure'); ?>"/>
                                                    </div>
                                                </form>
                                            <?php endif; ?>
                                        <?php } ?>
                                    </div>
                                <?php }
                            } ?>
                            <div id="adt-ad-review-box">
                                <?php get_template_part('template-parts/layouts/ad-style/ad', 'reviews'); ?>
                            </div>
                        </div>
                        <div class="ad-right-sidebar">
                            <div class="adt-product-or-seller-web-box">
                                <?php if ($is_price_enabled) { ?>

                                    <div class="ad-price-box">
                                        <i class="fas fa-tag"></i>
                                        <?php
                                        $price_type = get_post_meta($pid, '_adforest_ad_price_type', true);
                                        if ($price_type == "no_price") { ?>
                                            <h3><?php echo esc_html__("No Price", "adforest"); ?></h3>
                                        <?php } else { ?>
                                            <h3><?php echo adforest_adPrice($pid, 'negotiable-single', ''); ?></h3>
                                        <?php } ?>
                                        <i class="fas fa-tag sm-tag"></i>
                                    </div>

                                <?php } ?>
                                <?php
                                if (isset($adforest_theme['sb_enable_comments_offer']) && $adforest_theme['sb_enable_comments_offer'] && $adf_bidding_gate_ok) {
                                    $bid_date = get_post_meta($pid, '_adforest_ad_bidding_date', true);
                                    if (!$bid_date) {
                                        $bid_date = date('Y-m-d', strtotime('+7 days', current_time('timestamp')));
                                    }
                                    $bidding_enabled = $adf_bidding_gate_ok ? get_post_meta($pid, '_adforest_ad_bidding', true) : 0;
                                    if (isset($adforest_theme['bidding_timer']) && $adforest_theme['bidding_timer'] == '1') {
                                        if (($bidding_enabled == 1 || $bidding_enabled == "1") && strtotime($bid_date) >= current_time('timestamp')) { ?>
                                            <div class="ad-bidding-timer-box">
                                                <div class="ad-bidding-timer-box-inner">
                                                    <h5><?php echo esc_html__("Bidding Ends In", "adforest"); ?></h5>
                                                    <!-- Added data-biddate attribute -->
                                                    <div class="adt-deal-countdown-desc-clock"
                                                         data-biddate="<?php echo esc_attr($bid_date); ?>">
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                                <span class="adt-countdown-clock-title"
                                                                      id="days">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Days", "adforest"); ?></span>
                                                        </div>
                                                        <div class="adt-coundown-svg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                        </div>
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                                <span class="adt-countdown-clock-title"
                                                                      id="hours">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Hours", "adforest"); ?></span>
                                                        </div>
                                                        <div class="adt-coundown-svg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                        </div>
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                            <span class="adt-countdown-clock-title"
                                                                  id="minutes">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Minutes", "adforest"); ?></span>
                                                        </div>
                                                        <div class="adt-coundown-svg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                                 viewBox="0 0 5 5" fill="none">
                                                                <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                            </svg>
                                                        </div>
                                                        <div class="adt-countdown-clock-div">
                                                            <div class="adt-countdown-clock-inner">
                                                            <span class="adt-countdown-clock-title"
                                                                  id="seconds">00</span>
                                                            </div>
                                                            <span class="adt-countdown-clock-heading"><?php echo esc_html__("Seconds", "adforest"); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php }
                                    }
                                } ?>
                                <div class="ad-owner-detail-box">
                                    <?php
                                    $is_phone_verified = get_user_meta($poster_id, '_sb_is_ph_verified', true);
                                    $verified_class = '';
                                    if ($is_phone_verified == '1') {
                                        $verified_class = 'verified';
                                    }
                                    ?>
                                    <span class="<?php echo esc_attr($verified_class); ?>">
                                         <?php if ($is_phone_verified == '1') {
                                             ?>
                                             <i class="fas fa-check"></i>
                                             <?php
                                         }
                                         ?>
                                    </span>
                                    <?php if ($user_type != "") { ?>
                                        <span class="owner-status"><?php echo adforest_return_echo($user_type); ?></span>
                                    <?php } ?>
                                    <a class="m-0"
                                       href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads'); ?>">
                                        <img src="<?php echo esc_attr($user_pic); ?>" alt="owner-img">
                                    </a>
                                    <h4><?php echo esc_html($poster_name); ?></h4>
                                    <p>
                                        <?php
                                        echo esc_html__(
                                                'Member Since',
                                                'adforest'
                                            ) . ' ' . esc_html(
                                                date_i18n(get_option('date_format'), strtotime($registration_date))
                                            );
                                        ?>
                                    </p>
                                    <a class="view-all-ads-text"
                                       href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads'); ?>">
                                        <?php echo esc_html__("View All Ads", "adforest"); ?>
                                    </a>
                                    <?php
                                    if ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'phone') {
                                        $call_now = 'javascript:void(0);';
                                        if (wp_is_mobile()) {
                                            $call_now = 'tel:' . adforest_get_CallAbleNumber(get_post_meta($pid, '_adforest_poster_contact', true));
                                        }

                                        $batch_text = '';
                                        if (isset($adforest_theme['sb_phone_verification']) && $adforest_theme['sb_phone_verification']) {
                                            if ($contact_num != "") {
                                                if (get_user_meta($poster_id, '_sb_is_ph_verified', true) == '1') {
                                                    $batch_text = esc_html__('Verified', 'adforest');
                                                } else {
                                                    $batch_text = esc_html__('Not verified', 'adforest');
                                                }
                                            } else {
                                                $batch_text = esc_html__('Not verified', 'adforest');
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

                                                $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                ?>

                                                <!-- Phone Box -->
                                                <div class="contact-detail-box">
                                                    <div class="icon-box">
                                                        <i class="fas fa-phone"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <a href="<?php echo esc_url($call_now); ?>"
                                                           style="text-decoration: none;">
                                                            <small style="cursor: pointer;"
                                                                   class="toggle-contact-number">
                                                                <?php echo esc_html__("Login to View", "adforest"); ?>
                                                            </small>
                                                            <span class="phone-number">
                                                                <?php echo esc_html($masked_num); ?>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>

                                                <!-- WhatsApp Box -->
                                                <div class="contact-detail-box green">
                                                    <div class="icon-box">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <a href="<?php echo esc_url($call_now); ?>"
                                                           style="text-decoration: none;">
                                                            <small style="cursor: pointer;"
                                                                   class="toggle-contact-number">
                                                                <?php echo esc_html__("Login to View", "adforest"); ?>
                                                            </small>
                                                            <span class="phone-number">
                                                                <?php echo esc_html($masked_num); ?>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>

                                            <?php } else { ?>
                                                <div class="contact-detail-box">
                                                    <div class="icon-box">
                                                        <i class="fas fa-phone"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <small style="cursor: pointer;" class="toggle-contact-number"
                                                               data-ad-id="<?php echo intval($pid); ?>">
                                                            <?php echo esc_html__("Click To Show", "adforest"); ?>
                                                        </small>
                                                        <span class="phone-number">
                                                        <?php
                                                        $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                        echo esc_html($masked_num);
                                                        ?>
                                                    </span>
                                                    </div>
                                                </div>
                                                <div class="contact-detail-box green">
                                                    <div class="icon-box">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </div>
                                                    <div class="meta-box">
                                                        <small style="cursor: pointer;" class="toggle-contact-number"
                                                               data-ad-id="<?php echo intval($pid); ?>"
                                                        ><?php echo esc_html__("Click To Show", "adforest"); ?>
                                                        </small>
                                                        <span class="phone-number">
                                                        <?php
                                                        // Mask the last 5 digits with 'x'
                                                        $masked_num = substr($contact_num, 0, -5) . str_repeat('x', 5);
                                                        echo esc_html($masked_num);
                                                        ?>
                                                    </span>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php }
                                    } ?>
                                    <?php if ($allow_whatsapp && $contact_num != "") {
                                        $contact_num = preg_replace('/[^\dxX]/', '', $contact_num);
                                        $redirect_url = '';
                                        if (isset($adforest_theme['sb_show_whatsapp_intro']) && $adforest_theme['sb_show_whatsapp_intro'] == '1') {
                                            $post_link = get_permalink($pid);
                                            $whatsapp_intro = get_user_meta(get_current_user_id(), '_sb_user_whatsapp_intro', true);
                                            $ad_link_text = esc_html__("Ad Link: ", "adforest") . $post_link;
                                            $redirect_url = "https://api.whatsapp.com/send?phone=$contact_num&text=" . urlencode($ad_link_text) . "%0a" . urlencode($whatsapp_intro);
                                        } else {
                                            $redirect_url = "https://api.whatsapp.com/send?phone=$contact_num";
                                        }
                                        $whatsapp_text = esc_html__("Message on WhatsApp", 'adforest');
                                        if (adforest_showPhone_to_users()) {
                                            $adforest_login_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : '';
                                            $adforest_login_page = apply_filters('adforest_language_page_id', $adforest_login_page);
                                            if ($adforest_login_page != '') {
                                                $redirect_url = adforest_login_with_redirect_url_param(adforest_get_current_url());
                                                $whatsapp_text = esc_html__("Login to Chat", "adforest");
                                            }
                                        }
                                        ?>
                                        <a style="text-decoration: none" href="<?php echo esc_url($redirect_url); ?>"
                                           class="btn btn-whatsap btn-block"
                                           target="_blank">
                                            <i class="fab fa-whatsapp"></i>
                                            <span class=""><?php echo esc_html($whatsapp_text); ?></span>
                                        </a>
                                    <?php } ?>
                                    <?php
                                    if (($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'message') && $ad_status == 'active') {
                                        if (class_exists('SB_Chat') && $allow_sb_chat == "1") {
                                            $sb_plugin_options = get_option('sb_plugin_options', array());
                                            if (isset($sb_plugin_options['sbChat-active']) && $sb_plugin_options['sbChat-active'] == 1 && class_exists('SB_Chat_Setting_Page')) {
                                                $classes = 'adt-button-dark text-decoration-none';
                                                $button_text = esc_html__('Start Chat', 'adforest');
                                                echo do_shortcode(
                                                    '[sb_chat_shortcode_popup '
                                                    . 'class="' . esc_attr($classes) . '" '
                                                    . 'button_title="' . esc_attr($button_text) . '" '
                                                    . 'post_author_id="' . absint($poster_id) . '" '
                                                    . 'post_id="' . absint($pid) . '" '
                                                    . ']'
                                                );
                                            }
                                        }
                                    } ?>
                                    <?php if ( isset( $ad_website ) && ! empty( $ad_website ) ) { ?>
                                        <div class="adt-website_link">
                                            <a href="<?php echo esc_url( $ad_website ); ?>" target="_blank" rel="noopener noreferrer">
                                                <i class="fas fa-globe"></i>
                                                <?php echo esc_html( preg_replace( '#^https?://#', '', $ad_website ) ); ?>
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php
                            if (class_exists('SbPro')) {
                                if (get_post_meta($pid, 'sb_pro_is_hours_allow', true) == '1') { ?>
                                    <div class="adt-detail-box-booking">
                                        <?php echo apply_filters('sb_show_business_hours', $pid); ?>
                                        <?php echo apply_filters('sb_show_booking_option', $pid); ?>
                                    </div>
                                <?php }
                            }
                            if ($adforest_theme['tips_title'] != '' && $adforest_theme['tips_for_ad'] != "") {
                                ?>
                                <div class="ad-detail-safety-tips-box">
                                    <div style="padding: 20px;" class="widget-heading">
                                        <div class="panel-title">
                                            <span class="tips_widget_title"><?php echo adforest_return_echo($adforest_theme['tips_title']); ?></span>
                                        </div>
                                    </div>
                                    <div class="widget-content saftey tips_widget_body">
                                        <?php echo adforest_return_echo($adforest_theme['tips_for_ad']); ?>
                                    </div>
                                </div>

                                <?php
                            } ?>
                            <?php
                            if (is_active_sidebar('adforest_ad_sidebar_bottom')) {
                                echo '<div class = "ad-bottom-sidebar">';
                                dynamic_sidebar('adforest_ad_sidebar_bottom');
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php if (isset($adforest_theme['Related_ads_on']) && $adforest_theme['Related_ads_on'] == "1") { ?>
                    <?php get_template_part('template-parts/layouts/ad-style/related', 'ads'); ?>
                <?php } ?>
            </div>
        </div>
    </section>
<?php
get_template_part('template-parts/layouts/ad-style/report', 'ad');
if (isset($adforest_theme['share_ads_on']) && $adforest_theme['share_ads_on']) {
    get_template_part('template-parts/layouts/ad-style/share', 'ad');
}
