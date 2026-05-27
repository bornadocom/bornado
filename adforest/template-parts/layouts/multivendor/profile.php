<?php
global $adforest_theme;
$author_id = get_current_user_id();
$author = get_user_by('ID', $author_id);
$user_pic = adforest_get_user_dp($author_id, 'adforest-user-profile');
$contact_num = get_user_meta($author->ID, '_sb_contact', true);
$address = get_user_meta($author->ID, '_sb_address', true);
$author_intro = get_user_meta($author_id, '_sb_user_intro', true);

$background_img = isset($adforest_theme['adforest_profile_background_img']['url']) ? $adforest_theme['adforest_profile_background_img']['url'] : trailingslashit(get_template_directory_uri()) . 'images/multivendor-detail-banner.png';
$user_profile_fb = get_user_meta(get_current_user_id(), '_sb_profile_facebook', true);
$user_profile_instagram = get_user_meta(get_current_user_id(), '_sb_profile_instagram', true);
$user_profile_linkedin = get_user_meta(get_current_user_id(), '_sb_profile_linkedin', true);
$user_profile_twitter = get_user_meta(get_current_user_id(), '_sb_profile_twitter', true);
?>
<section class="adt-multivendor-detail-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="multivendor-detail-banner">
                    <img class="img-fluid" src="<?php echo esc_url($background_img, "adforest"); ?>" alt="banner-img">
                    <div class="follow-us-box">
                        <h4><?php echo esc_html__("Follow Us", "adforest"); ?></h4>
                        <ul class="social-links">
                            <li>
                                <a href="<?php echo esc_url(isset($user_profile_fb) ? $user_profile_fb : "", "adforest"); ?>"><i
                                            class="fab fa-facebook-f"></i></a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(isset($user_profile_twitter) ? $user_profile_twitter : "", "adforest"); ?>"><i
                                            class="fab fa-twitter"></i></a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(isset($user_profile_linkedin) ? $user_profile_linkedin : "", "adforest"); ?>"><i
                                            class="fab fa-linkedin-in"></i></a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(isset($user_profile_instagram) ? $user_profile_instagram : "", "adforest"); ?>"><i
                                            class="fab fa-instagram"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="multivendor-detail-wrapper">
                    <div class="multivendor-profile-box-sidebar">
                        <div class="multivendor-profile-box">
                            <div class="favourite-box"><i class="fas fa-heart"></i></div>
                            <div class="img-box">
                                <img style="width: auto" src="<?php echo esc_attr($user_pic); ?>" id="user_dp"
                                     alt="<?php echo esc_attr__('Profile Picture', 'adforest'); ?>">
                            </div>
                            <h3><?php echo esc_html($author->display_name); ?></h3>
                            <?php
                            $ratings = adforest_get_all_ratings($author_id);
                            ?>
                            <div class="rating">
                                <a style="text-decoration: none"
                                   href="<?php echo adforest_set_url_param(get_author_posts_url($author->ID), 'type', 1); ?>">
                                    <span><?php printf( esc_html__( '%s Reviews', 'adforest' ), esc_html( count( $ratings ) ) ); ?></span>
                                    <?php
                                    $got = get_user_meta($author->ID, "_adforest_rating_avg", true);
                                    $total = 0;
                                    if ($got == "")
                                        $got = 0;
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= round($got))
                                            echo '<i class="fa fa-star"></i>';
                                        else
                                            echo '<i class="far fa-star"></i>';

                                        $total++;
                                    }
                                    ?>
                                </a>
                            </div>
                            <div class="active-box">
                                <i class="fas fa-calendar-week"></i><span><small><?php echo esc_html__('Last Active:', 'adforest') ?></small><?php printf(_x('%s Ago', 'Last login time', 'adforest'), adforest_get_last_login($author->ID)); ?></span>
                            </div>
                            <ul>
                                <li>
                                    <i class="fas fa-map-marker-alt"></i><span><?php echo esc_html__('Address: ', 'adforest'); ?></span><small><?php echo esc_html($address); ?></small>
                                </li>
                                <?php if ($contact_num != "") {
                                    if (adforest_showPhone_to_users()) {
                                        $adforest_login_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : '';
                                        $adforest_login_page = apply_filters('adforest_language_page_id', $adforest_login_page);
                                        if ($adforest_login_page != '') {
                                            $redirect_url = adforest_login_with_redirect_url_param(adforest_get_current_url());
                                        }
                                        ?>
                                        <li>
                                            <i class="fas fa-phone-alt"></i><span><?php echo esc_html__('Phone: ', 'adforest'); ?></span>
                                            <a href="<?php echo esc_url($redirect_url); ?>">
                                                <span class="sb-phonenumber"><?php echo esc_html__('Login To View', 'adforest') ?></span>
                                            </a>
                                        </li>
                                    <?php } else { ?>
                                        <li>
                                            <i class="fas fa-phone-alt"></i><span><?php echo esc_html__('Phone: ', 'adforest'); ?></span>
                                            <small style="cursor: pointer;" class="click-to-view_ph"
                                                   data-user_id="<?php echo esc_attr($author->ID); ?>">
                                                <?php echo esc_html__("Click To Show", "adforest") ?>
                                            </small>
                                            <span class="phone-number" style="display: none;">
                                                <?php echo esc_html($contact_num); ?>
                                            </span>
                                        </li>
                                    <?php }
                                } ?>
                            </ul>
                            <div class="follow-box">
                                <a href="#" class="adt-button-dark"><?php echo esc_html__("Follow Now", "adforest"); ?></a>
                            </div>
                        </div>
                        <div class="multivendor-contact-box">
                            <h3><?php echo esc_html__("Contact Us", "adforest"); ?></h3>
                            <input type="text" placeholder="<?php echo esc_attr__("Name", "adforest"); ?>">
                            <input type="email" placeholder="<?php echo esc_attr__("Email", "adforest"); ?>">
                            <input type="text" placeholder="<?php echo esc_attr__("Subject", "adforest"); ?>">
                            <textarea name="Message" id="" placeholder="<?php echo esc_attr__("Message", "adforest"); ?>"></textarea>
                            <button type="submit"
                                    class="adt-button-dark"><?php echo esc_html__("Send Message", "adforest"); ?></button>
                        </div>
                    </div>
                    <div class="multivendor-main-content">
                        <h2><?php echo esc_html__("About us", "adforest"); ?></h2>
                        <p><?php echo esc_html($author_intro); ?></p>
                        <div class="row">
                            <?php
                            $args = array(
                                'post_type' => 'product',
                                'posts_per_page' => 8,
                                'author' => $author_id,
                                'post_status' => 'publish',
                            );

                            $products = new WP_Query($args);

                            if ($products->have_posts()) {
                                while ($products->have_posts()) {
                                    $products->the_post();
                                    $product = wc_get_product(get_the_ID());

                                    $product_link = get_permalink();
                                    $product_title = get_the_title();
                                    $product_price = $product->get_price_html();
                                    $product_image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                                    $product_reviews_count = $product->get_review_count();
                                    $product_average_rating = $product->get_average_rating();
                                    $heart_filled = 'fa-heart';
                                    $fav_class = "";
                                    if (get_user_meta(get_current_user_id(), '_product_fav_id_' . get_the_ID(), true) == get_the_ID()) {
                                        $fav_class = 'favourited';
                                        $heart_filled = 'fa-heart';
                                    }

                                    if (!$product_image_url) {
                                        $product_image_url = wc_placeholder_img_src();
                                    }

                                    echo '<div class="col-sm-6 col-md-4 col-xl-6">';
                                    echo '    <div class="adt-multivendor-category-ad-card">';
                                    echo '        <div class="category-img-box">';
                                    echo '            <a href="' . esc_url($product_link) . '">';
                                    echo '                <img class="img-fluid" src="' . esc_url($product_image_url) . '" alt="' . esc_attr($product_title) . '">';
                                    echo '            </a>';
                                    echo '        </div>';
                                    echo '        <div class="category-content-box">';
                                    echo '            <div class="rating">';
                                    echo '                <span>' . esc_html($product_average_rating) . '</span>';
                                    echo '                <small>' . sprintf( esc_html__( '%s Reviews', 'adforest' ), esc_html( $product_reviews_count ) ) . '</small>';
                                    echo '            </div>';
                                    echo '            <a href="' . esc_url($product_link) . '">';
                                    echo '                <h5>' . esc_html(wp_trim_words($product_title, 5, '...')) . '</h5>';
                                    echo '            </a>';
                                    echo '            <strong class="price">' . $product_price . '</strong>';
                                    echo '            <div class="detail-btn-box">';
                                    echo '                <a href="' . esc_url($product_link) . '" class="detail-btn">' . esc_html__("Detail Now", "adforest") . '</a>';
                                    ?>
                                    <a href="javascript:void(0);"
                                       class="favourite product_to_fav  <?php echo esc_attr($fav_class); ?>"
                                       data-productId="<?php echo get_the_ID(); ?>">
                                        <i class="fa <?php echo esc_attr($heart_filled); ?>"></i>
                                    </a>
                                    <?php
                                    echo '            </div>';
                                    echo '        </div>';
                                    echo '    </div>';
                                    echo '</div>';
                                }
                                wp_reset_postdata();
                            } else {
                                echo '<p>' . esc_html__("No products found", "adforest") . '</p>';
                            }
                            ?>

                        </div>
                    </div>
                    <div class="multivendor-recent-ads-sidebar">
                        <?php
                        if (is_active_sidebar('adforest_ad_sidebar_bottom')) {
                            echo '<div class = "ad-bottom-sidebar">';
                            dynamic_sidebar('adforest_ad_sidebar_bottom');
//                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            $services = $adforest_theme['services_boxes'];
            if (!empty($services)) {
                foreach ($services as $service) {
                    $icon = !empty($service['url']) ? esc_attr($service['url']) : '';
                    $title = !empty($service['title']) ? esc_html($service['title']) : '';
                    $description = !empty($service['description']) ? esc_html($service['description']) : '';

                    echo '<div class="col-6 col-lg-3">';
                    echo '  <div class="adt-multivendor-services-box">';
                    echo '      <i class="' . $icon . '"></i>';
                    echo '      <h6>' . $title . '</h6>';
                    echo '      <p>' . $description . '</p>';
                    echo '  </div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</section>