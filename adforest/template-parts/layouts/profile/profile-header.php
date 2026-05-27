<?php
global $adforest_theme;
$author_id = get_query_var('author');
$author = get_user_by('ID', $author_id);
$user_pic = adforest_get_user_dp($author_id, 'adforest-user-profile');
$contact_num = get_user_meta($author->ID, '_sb_contact', true);
$address = get_user_meta($author->ID, '_sb_address', true);
$author_intro = get_user_meta($author_id, '_sb_user_intro', true);

if (get_user_meta($author->ID, '_sb_user_type', true) == 'Indiviual') {
    $user_type = esc_html__('Individual', 'adforest');
} else if (get_user_meta($author->ID, '_sb_user_type', true) == 'Dealer') {
    $user_type = esc_html__('Dealer', 'adforest');
}
$social_icons = "";
$profiles = adforest_social_profiles();
foreach ($profiles as $key => $value) {
    if (get_user_meta($author->ID, '_sb_profile_' . $key, true) != "") {
        $icon = isset($key) && $key == 'linkedin' ? 'lni lni-' . $key . '-original' : 'lni lni-' . $key . '-filled';
        if($key == 'facebook') {
            $icon = 'fa-brands fa-facebook-f';
        } elseif($key == 'twitter') {
            $icon = 'fa-brands fa-x-twitter';
        } elseif($key == 'linkedin') {
            $icon = 'fa-brands fa-linkedin';
        } elseif($key == 'instagram') {
            $icon = 'fa-brands fa-instagram';
        }
        $social_icons .= '<a href="' . esc_url(get_user_meta($author->ID, '_sb_profile_' . $key, true)) . '" target="_blank" class="mb-1 btn social-icons-outline-dash btn-' . esc_attr($key) . '"><i class="' . $icon . '"></i></a>';
    }
}
?>
<div class="adt-seller-detail-sidebar">
    <div class="top-meta">
        <?php if (isset($user_type) && $user_type != "") { ?>
            <span class="status"><?php echo esc_html($user_type); ?></span>
        <?php } ?>
        <img src="<?php echo esc_attr($user_pic); ?>" id="user_dp"
             alt="<?php echo esc_attr__('Profile Picture', 'adforest'); ?>">
        <h4><?php echo esc_html($author->display_name); ?></h4>
        <?php if (isset($adforest_theme['sb_enable_user_ratting']) && $adforest_theme['sb_enable_user_ratting']) {
            $ratings = adforest_get_all_ratings($author_id);
            ?>
            <div class="rating profile-header-rating">
                <a style="text-decoration: none"
                   href="<?php echo adforest_set_url_param(get_author_posts_url($author->ID), 'type', 1); ?>">
                    <span><?php printf( esc_html__( '%s Reviews', 'adforest' ), esc_html( count( $ratings ) ) ); ?></span>
                    <?php
                    $got = get_user_meta($author->ID, "_adforest_rating_avg", true);
                    $got_safe = floatval($got);

                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= round($got_safe)) {
                            echo '<i class="fa fa-star"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                </a>
            </div>
        <?php } ?>
    </div>
    <div class="last-active">
        <i class="fas fa-calendar-week"></i><span><small><?php echo esc_html__('Last Active:', 'adforest') ?></small><?php printf(_x('%s Ago', 'Last login time', 'adforest'), adforest_get_last_login($author->ID)); ?></span>
    </div>
    <div class="bottom-meta">
        <div class="ad-sold-wrapper">
            <div class="ad-sold-box purple">
                <span><?php echo adforest_get_sold_ads($author->ID); ?></span>
                <small><?php echo esc_html__('Ad Sold', 'adforest'); ?></small>
            </div>
            <div class="ad-sold-box green">
                <span><?php echo adforest_get_all_ads($author->ID); ?></span>
                <small><?php echo esc_html__('Total Ads', 'adforest'); ?></small>
            </div>
        </div>
        <ul>
            <?php if ($address != "") { ?>
                <li>
                    <i class="fas fa-map-marker-alt"></i><span><?php echo esc_html__('Address: ', 'adforest'); ?></span><?php echo esc_html($address); ?>
                </li>
            <?php } ?>
            <?php if ($contact_num != "") {
                if (adforest_showPhone_to_users()) {
                    $adforest_login_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : '';
                    $adforest_login_page = apply_filters('adforest_language_page_id', $adforest_login_page);
                    if ($adforest_login_page != '') {
                        $redirect_url = adforest_login_with_redirect_url_param(adforest_get_current_url());
                    }
                    ?>
                    <li><i class="fas fa-phone-alt"></i><span><?php echo esc_html__('Phone: ', 'adforest'); ?></span>
                        <a href="<?php echo esc_url($redirect_url); ?>">
                            <span class="sb-phonenumber"><?php echo esc_html__('Login To View', 'adforest') ?></span>
                        </a>
                    </li>
                <?php } else { ?>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <small style="cursor: pointer;" class="click-to-view_ph"
                               data-user_id="<?php echo esc_attr($author->ID); ?>"
                               data-nonce="<?php echo wp_create_nonce('sb_phone_nonce'); ?>">
                            <?php echo esc_html__("Click To Show", "adforest") ?>
                        </small>
                        <span class="phone-number" style="display: none;"></span>
                    </li>
                <?php }
            } ?>
        </ul>
        <?php if ($author_intro != "") { ?>
            <h4><?php echo esc_html__( 'Introduction', 'adforest' ); ?></h4>
            <p><?php echo esc_html($author_intro); ?></p>
        <?php }
        if ($social_icons != "") { ?>
            <h4><?php echo esc_html__( 'Social Profile', 'adforest' ); ?></h4>
            <p><?php echo adforest_return_echo($social_icons); ?></p>
        <?php }
        require trailingslashit(get_template_directory()) . 'template-parts/layouts/profile/contact-form.php';
        ?>
    </div>
</div>