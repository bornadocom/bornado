<?php
/**
 * The template for displaying archive vendor info
 *
 * Override this template by copying it to yourtheme/MultiVendorX/archive-vendor-info.php
 *
 * @author      MultiVendorX
 * @package MultiVendorX/Templates
 * @version     3.7
 */
global $MVX,$adforest_theme;;

$vendor = get_mvx_vendor($vendor_id);
$vendor_hide_address = apply_filters('mvx_vendor_store_header_hide_store_address', get_user_meta($vendor_id, '_vendor_hide_address', true), $vendor->id);
$vendor_hide_phone = apply_filters('mvx_vendor_store_header_hide_store_phone', get_user_meta($vendor_id, '_vendor_hide_phone', true), $vendor->id);
$vendor_hide_email = apply_filters('mvx_vendor_store_header_hide_store_email', get_user_meta($vendor_id, '_vendor_hide_email', true), $vendor->id);
$template_class = get_mvx_vendor_settings('mvx_vendor_shop_template', 'store', 'template1');
$template_class = apply_filters('can_vendor_edit_shop_template', false) && get_user_meta($vendor_id, '_shop_template', true) ? get_user_meta($vendor_id, '_shop_template', true) : $template_class;
$vendor_hide_description = apply_filters('mvx_vendor_store_header_hide_description', get_user_meta($vendor_id, '_vendor_hide_description', true), $vendor->id);

$vendor_fb_profile = get_user_meta($vendor_id, '_vendor_fb_profile', true);
$vendor_twitter_profile = get_user_meta($vendor_id, '_vendor_twitter_profile', true);
$vendor_linkdin_profile = get_user_meta($vendor_id, '_vendor_linkdin_profile', true);
$vendor_google_plus_profile = get_user_meta($vendor_id, '_vendor_google_plus_profile', true);
$vendor_youtube = get_user_meta($vendor_id, '_vendor_youtube', true);
$vendor_instagram = get_user_meta($vendor_id, '_vendor_instagram', true);
$vendor_pinterest_profile = get_user_meta($vendor_id, '_vendor_pinterest_profile', true);

// Follow code
$mvx_customer_follow_vendor = get_user_meta( get_current_user_id(), 'mvx_customer_follow_vendor', true ) ? get_user_meta( get_current_user_id(), 'mvx_customer_follow_vendor', true ) : array();
$vendor_lists = !empty($mvx_customer_follow_vendor) ? wp_list_pluck( $mvx_customer_follow_vendor, 'user_id' ) : array();
$follow_status = in_array($vendor_id, $vendor_lists) ? __( 'Unfollow', 'adforest' ) : __( 'Follow', 'adforest' );
$follow_status_key = in_array($vendor_id, $vendor_lists) ? 'Unfollow' : 'Follow';



/*custom template hooks*/

$vendor_hide_description = '';
$vendor_hide_description = apply_filters('mvx_vendor_store_header_hide_description', get_user_meta($vendor_id, '_vendor_hide_description', true), $vendor->id);
/* contact detail in sidebar */
$vendor_telefone = get_user_meta($vendor_id, '_vendor_phone', true) ? get_user_meta($vendor_id, '_vendor_phone', true) : '';
$vendor_email = isset($vendor->user_data->user_email) ? $vendor->user_data->user_email : '';
$register_date = isset($vendor->user_data->user_registered) ? $vendor->user_data->user_registered : '';
$vendor_products = ( $vendor->get_products() ) ? $vendor->get_products() : '';

/* Template for Vendor Single Page */
$vendor_temp_custom = isset($adforest_theme['sb_vendor_templates0']) ? $adforest_theme['sb_vendor_templates0'] : '';

$banner_img = ( isset($banner) && $banner != '' ) ? $banner : get_template_directory_uri() . '/images/banner_placeholder.png';

$vendor_image = $vendor->get_image('image', 'adforest_vendor_img') ? $vendor->get_image('image', 'adforest_vendor_img') : get_template_directory_uri() . '/images/qa.png';
$store_name = apply_filters('wcmp_vendor_lists_single_button_text', $vendor->page_title);
/* badge for approved vendor */
$verified_icon = get_template_directory_uri() . '/images/d-tick.png';

$baner_img = isset($adforest_theme['sb_vendor_detail_baner_img']['url']) ? $adforest_theme['sb_vendor_detail_baner_img']['url'] : '';

$fav_v_class = '';

$favourited_text    =  esc_html__('Follow','adforest');
if (get_user_meta(get_current_user_id(), '_vendor_fav_id_' . $vendor_id, true) == $vendor_id) {
    $fav_v_class = 'favourited_v';
    $favourited_text  = esc_html__('Followed','adforest');
}


if(isset($_GET['dashboard_type']) && $_GET['dashboard_type'] != ""){
    $template_class   = 'template'.$_GET['dashboard_type'];
    $vendor_temp_custom = 2;
}


/* here we load our custom template from theme option */
if ($vendor_temp_custom == 1) {
    ?>
    <section class="detail-page-speedcross">
        <div class="container">
            <div class="row">
                <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-12 col-sm-12 col-12">
                    <div class="detail-product-search vendor-sidebar">
                        <div class="heading-product-search">
                            <h2><?php echo esc_html__('Contact Us', 'adforest') ?></h2>
                        </div>
                        <form id="vendro-owner-contact" class="vendro-owner-contact"
                              name="vendro-owner-contact" method="post">
                            <div class="form-group">
                                <input type="text" class="form-control input-plh"
                                       placeholder="<?php echo esc_attr__("Your Name", 'adforest'); ?>"
                                       name="u_name" id="u_name" data-parsley-required="true"
                                       data-parsley-error-message="<?php echo __('This field is required.', 'adforest'); ?>">
                            </div>

                            <div class="form-group">
                                <input type="email" class="form-control input-plh"
                                       placeholder="<?php echo esc_attr__("Email Address", 'adforest'); ?>"
                                       name="u_email" id="u_email" data-parsley-required="true"
                                       data-parsley-error-message="<?php echo __('This field is required.', 'adforest'); ?>">
                            </div>

                            <div class="form-group">
                                <input type="text" class="form-control input-plh"
                                       placeholder="<?php echo esc_attr__("Subject", 'adforest'); ?>"
                                       name="u_subject" id="u_subject">
                            </div>

                            <div class="form-group">
                                <textarea class="form-control input-plh" rows="3" id="u_mesage"
                                          name="u_mesage"
                                          placeholder="<?php echo esc_attr__("Your Message", 'adforest'); ?>"
                                          data-parsley-required="true"
                                          data-parsley-error-message="<?php echo __('This field is required.', 'adforest'); ?>"></textarea>
                            </div>

                            <div class="form-group">
                                <input type="hidden" id="vendor_id" name="vendor_id"
                                       value="<?php echo esc_attr($vendor_id); ?>"/>
                                <button type="submit"
                                        vendor-contact-<?php echo esc_attr($vendor_id); ?> class="btn
                                        btn-theme"><?php echo esc_html__("Contact Now", 'adforest'); ?></button>
                            </div>
                        </form>
                    </div>
                    <?php
                    $args = array(
                        'post_type' => 'product',
                        'posts_per_page' => 4,
                        'post_status' => 'publish'
                    );

                    $recent_products = new WP_Query($args);
                    if ($recent_products->have_posts()) {
                        ?>
                        <div class="sale-product-shop">
                            <div class="recent-prodcut">
                                <div class="recent-prodcut-heading">
                                    <h2><?php echo esc_html__('Recent Product', 'adforest') ?></h2>
                                </div>
                                <?php
                                while ($recent_products->have_posts()) : $recent_products->the_post();

                                    global $product;
                                    echo adforest_get_recent_products_list($product);

                                endwhile;
                                wp_reset_query();
                                ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                    <div class="listing-detail-page">
                        <div class="show-detail-img">
                            <img src="<?php echo esc_url($banner_img); ?>" alt="" class="img-fluid">
                        </div>
                        <?php if (!$vendor_hide_description && !empty($description) && $description != '') { ?>
                            <div class="persoanl-description">
                                <h2><?php echo esc_html__('About us', 'adforest'); ?></h2>
                                <?php echo htmlspecialchars_decode(wpautop($description), ENT_QUOTES); ?>
                            </div>
                        <?php } ?>
                    </div>

                    <?php
                    if ($adforest_theme['sb_vendor_show_shop_prod'] == true) {
                        echo ' <div class="row">';

                        $num_vendor_prod_show = isset($adforest_theme['num_vendor_prod_show']) ? $adforest_theme['num_vendor_prod_show'] : 6;
                        if ($vendor_products != '' && count($vendor_products) > 0) {

                            $args = array(
                                'post_type' => 'product',
                                'post_status' => 'publish',
                                'posts_per_page' => $num_vendor_prod_show,
                                'author' => $vendor_id, //change your vendor id here
                            );
                            $products = new WP_Query($args);
                            if ($products->have_posts()) {
                                while ($products->have_posts()) {
                                    $products->the_post();
                                    $product_id = get_the_ID();
                                    global $product;
                                    ?>

                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <?php echo adforest_get_product_details($product); ?>
                                    </div>
                                    <?php
                                }
                            }
                        } echo '</div>';
                    }
                    ?>
                </div>
                <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-12 col-sm-12 col-12">
                    <div class="all-personal-details">
                        <div class="personal-details">
                            <img src="<?php echo esc_url($vendor_image); ?>" class="thumbnail-img">
                            <h2><?php echo esc_html($store_name); ?></h2>
                            <p><?php echo date("M d ,Y", strtotime($register_date)); ?></p>


                            <a href="Javascript:void()" data-vendorid ="<?php echo esc_html($vendor_id) ?>" class="btn follow-now-btn vendor_to_fav    <?php echo esc_html($fav_v_class) ?>"><?php echo esc_html($favourited_text) ?></a>
                        </div>
                        <div class="personal-information">
                            <h2><?php echo esc_html__('Personal Details', 'adforest') ?></h2>

                            <?php if ($vendor_email != "") { ?>
                                <div class="personal-mail">
                                    <i class="fa fa-envelope"></i>
                                    <ul class="personal-email">
                                        <li><a href="#" class="descript-mail"><?php echo esc_html__('Email', 'adforest') ?></a></li>
                                        <li><a href="#" class="detail-mail"><?php echo esc_html($vendor_email) ?></a></li>
                                    </ul>
                                </div>
                            <?php } if ($vendor_telefone != "") { ?>
                                <div class="personal-phone">
                                    <i class="fa fa-phone"></i>
                                    <ul class="personal-number">
                                        <li><a href="#" class="descript-phone"><?php echo esc_html__('Phone', 'adforest') ?></a></li>
                                        <li><a href="#" class="detail-phone"><?php echo esc_html($vendor_telefone) ?></a></li>
                                    </ul>
                                </div>

                            <?php } if ($vendor->get_formatted_address() && $vendor_hide_address != 'Enable') { ?>
                                <div class="personal-addres">
                                    <i class="fa fa-map-marker"></i>
                                    <ul class="personal-address">
                                        <li><a href="#" class="descript-address"><?php echo esc_html__('Address', 'adforest') ?></a></li>
                                        <li><a href="#" class="detail-address"><?php echo esc_html($vendor->get_formatted_address()); ?></a></li>
                                    </ul>
                                </div>
                            <?php } ?>
                        </div>

                        <?php if (apply_filters('wcmp_vendor_store_header_show_social_links', true, $vendor->id)) : ?>

                        <?php
                        $vendor_fb_profile = get_user_meta($vendor_id, '_vendor_fb_profile', true);
                        $vendor_twitter_profile = get_user_meta($vendor_id, '_vendor_twitter_profile', true);
                        $vendor_linkdin_profile = get_user_meta($vendor_id, '_vendor_linkdin_profile', true);
                        $vendor_google_plus_profile = get_user_meta($vendor_id, '_vendor_google_plus_profile', true);
                        $vendor_youtube = get_user_meta($vendor_id, '_vendor_youtube', true);
                        $vendor_instagram = get_user_meta($vendor_id, '_vendor_instagram', true);
                        ?>
                        <div class="personal-social-icons">
                            <h2><?php echo esc_html__('Follow us:', 'adforest') ?></h2>
                            <ul class="socials-links">
                                <?php if ($vendor_fb_profile) { ?>  <li><a href="<?php echo esc_url($vendor_fb_profile) ?>"><i class="fa fa-facebook"></i></a></li><?php } ?>
                                <?php if ($vendor_twitter_profile) { ?>  <li><a href="<?php echo esc_url($vendor_twitter_profile) ?>"><i class="fa fa-twitter"></i></a></li><?php } ?>
                                <?php if ($vendor_linkdin_profile) { ?>  <li><a href="<?php echo esc_url($vendor_linkdin_profile) ?>"><i class="fa fa-linkedin"></i></a></li><?php } ?>
                                <?php if ($vendor_youtube) { ?> <li><a href="<?php echo esc_url($vendor_youtube) ?>"><i class="fa fa-youtube"></i></a></li><?php } ?>
                                <?php if ($vendor_instagram) { ?>  <li><a href="<?php echo esc_url($vendor_instagram) ?>"><i class="fa fa-instagram" 0=""></i></a></li><?php } ?>
                                <?php do_action('mvx_vendor_store_header_social_link', $vendor_id); ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                    endif;
                    if ($baner_img != "") {

                        $baner_img_link = isset($adforest_theme['sb_vendor_detail_baner_link']) ? $adforest_theme['sb_vendor_detail_baner_link'] : '#';
                        ?>
                        <div class="discpont-img">
                            <a href="<?php echo esc_url($baner_img_link) ?>">  <img src="<?php echo esc_url($baner_img) ?>" class="img-fluid" alt="<?php echo esc_attr__('Banner image', 'adforest') ?>"></a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

else {


    if ( $template_class == 'template3') { ?>
        <div class='mvx_bannersec_start mvx-theme01'>
            <div class="mvx-banner-wrap">
                <?php if($banner != '') { ?>
                    <div class='banner-img-cls'>
                        <img src="<?php echo esc_url($banner); ?>" class="mvx-imgcls"/>
                    </div>
                <?php } else { ?>
                    <img src="<?php echo esc_url($MVX->plugin_url . 'assets/images/banner_placeholder.jpg'); ?>" class="mvx-imgcls"/>
                <?php } ?>

                <div class='mvx-banner-area'>
                    <div class='mvx-bannerright'>
                        <div class="socialicn-area">
                            <div class="mvx_social_profile">
                                <?php if ($vendor_fb_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_fb_profile); ?>"><i class="mvx-font ico-facebook-icon"></i></a><?php } ?>
                                <?php if ($vendor_twitter_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_twitter_profile); ?>"><i class="mvx-font ico-twitter-icon"></i></a><?php } ?>
                                <?php if ($vendor_linkdin_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_linkdin_profile); ?>"><i class="mvx-font ico-linkedin-icon"></i></a><?php } ?>
                                <?php if ($vendor_google_plus_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_google_plus_profile); ?>"><i class="mvx-font ico-google-plus-icon"></i></a><?php } ?>
                                <?php if ($vendor_youtube) { ?> <a target="_blank" href="<?php echo esc_url($vendor_youtube); ?>"><i class="mvx-font ico-youtube-icon"></i></a><?php } ?>
                                <?php if ($vendor_instagram) { ?> <a target="_blank" href="<?php echo esc_url($vendor_instagram); ?>"><i class="mvx-font ico-instagram-icon"></i></a><?php } ?>
                                <?php if ($vendor_pinterest_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_pinterest_profile); ?>"><i class="mvx-font icon-pinterest"></i></a><?php } ?>
                                <?php do_action( 'mvx_vendor_store_header_social_link', $vendor_id ); ?>
                            </div>
                        </div>
                        <div class='mvx-butn-area'>
                            <?php do_action( 'mvx_additional_button_at_banner' ); ?>
                        </div>
                    </div>
                </div>

                <div class='mvx-banner-below'>
                    <div class='mvx-profile-area'>
                        <img src='<?php echo esc_attr($profile); ?>' class='mvx-profile-imgcls' />
                    </div>
                    <div>
                        <div class="mvx-banner-middle">
                            <div class="mvx-heading"><?php echo esc_html($vendor->page_title) ?></div>
                            <!-- Follow button will be added here -->
                            <?php if (mvx_is_module_active('follow-store')) { ?>
                                <button type="button" class="mvx-butn <?php echo is_user_logged_in() ? 'mvx-stroke-butn' : ''; ?>" data-vendor_id=<?php echo esc_attr($vendor_id); ?> data-status=<?php echo esc_attr($follow_status_key); ?> ><span></span><?php echo is_user_logged_in() ? esc_attr($follow_status) : esc_html_e('You must logged in to follow', 'adforest'); ?></button>
                            <?php } ?>
                        </div>
                        <div class="mvx-contact-deatil">

                            <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="mvx-address"><span><i class="mvx-font ico-location-icon"></i></span><?php echo esc_html($location); ?></p><?php } ?>

                            <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="mvx-address"><span><i class="mvx-font ico-call-icon"></i></span><?php echo apply_filters('vendor_shop_page_contact', $mobile, $vendor_id); ?></p><?php } ?>

                            <?php if (!empty($email) && $vendor_hide_email != 'Enable') { ?>
                                <p class="mvx-address"><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="mvx_vendor_detail"><i class="mvx-font ico-mail-icon"></i><?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?></a></p><?php } ?>

                            <?php
                            if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                                $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                                $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                                if (empty($external_store_label))
                                    $external_store_label = __('External Store URL', 'adforest');
                                if (isset($external_store_url) && !empty($external_store_url)) {
                                    ?><p class="external_store_url"><label><a target="_blank" href="<?php echo apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id); ?>"><?php echo esc_html($external_store_label); ?></a></label></p><?php
                                }
                            }
                            ?>
                            <?php do_action('mvx_after_vendor_information',$vendor_id);?>
                        </div>

                        <?php if (!$vendor_hide_description && !empty($description)) { ?>
                            <div class="description_data">
                                <?php echo wp_kses_post(htmlspecialchars_decode( wpautop( $description ), ENT_QUOTES )); ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="mvx_vendor_rating">
                        <?php
                        if (mvx_is_module_active('store-review') && get_mvx_vendor_settings('is_sellerreview', 'review_management')) {
                            if (mvx_is_store_page()) {
                                $vendor_term_id = get_user_meta( mvx_find_shop_page_vendor(), '_vendor_term_id', true );
                                $rating_val_array = mvx_get_vendor_review_info($vendor_term_id);
                                $MVX->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                            }
                        }
                        ?>
                    </div>

                </div>

            </div>
        </div>
    <?php } elseif ( $template_class == 'template1' ) {
        ?>
        <div class='mvx_bannersec_start mvx-theme02'>

            <div class="mvx-banner-wrap">
                <?php if($banner != '') { ?>
                    <div class='banner-img-cls'>
                        <img src="<?php echo esc_url($banner); ?>" class="mvx-imgcls"/>
                    </div>
                <?php } else { ?>
                    <img src="<?php echo esc_url($MVX->plugin_url . 'assets/images/banner_placeholder.jpg'); ?>" class="mvx-imgcls"/>
                <?php } ?>
                <div class='mvx-banner-area'>
                    <div class='mvx-bannerleft'>
                        <div class='mvx-profile-area'>
                            <img src='<?php echo esc_attr($profile); ?>' class='mvx-profile-imgcls' />
                        </div>
                        <div class="mvx-heading"><?php echo esc_html($vendor->page_title); ?></div>

                        <div class="mvx_vendor_rating">
                            <?php
                            if (mvx_is_module_active('store-review') && get_mvx_vendor_settings('is_sellerreview', 'review_management')) {
                                if (mvx_is_store_page()) {
                                    $vendor_term_id = get_user_meta( mvx_find_shop_page_vendor(), '_vendor_term_id', true );
                                    $rating_val_array = mvx_get_vendor_review_info($vendor_term_id);
                                    $MVX->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                                }
                            }
                            ?>
                        </div>
                        <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="mvx-address"><span><i class="mvx-font ico-location-icon"></i></span><?php echo esc_html($location); ?></p><?php } ?>

                        <div class="mvx-contact-deatil">

                            <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="mvx-address"><span><i class="mvx-font ico-call-icon"></i></span><?php echo esc_html(apply_filters('vendor_shop_page_contact', $mobile, $vendor_id)); ?></p><?php } ?>

                            <?php if (!empty($email) && $vendor_hide_email != 'Enable') { ?>
                                <p class="mvx-address"><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="mvx_vendor_detail"><i class="mvx-font ico-mail-icon"></i><?php echo esc_html(apply_filters('vendor_shop_page_email', $email, $vendor_id)); ?></a></p><?php } ?>
                            <?php
                            if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                                $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                                $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                                if (empty($external_store_label))
                                    $external_store_label = __('External Store URL', 'adforest');
                                if (isset($external_store_url) && !empty($external_store_url)) {
                                    ?><p class="external_store_url"><label><a target="_blank" href="<?php echo esc_attr(apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id)); ?>"><?php echo esc_html($external_store_label); ?></a></label></p><?php
                                }
                            }
                            ?>
                            <?php do_action('mvx_after_vendor_information',$vendor_id);?>
                        </div>
                    </div>
                    <div class='mvx-bannerright'>
                        <div class="socialicn-area">
                            <div class="mvx_social_profile">
                                <?php if ($vendor_fb_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_fb_profile); ?>"><i class="mvx-font ico-facebook-icon"></i></a><?php } ?>
                                <?php if ($vendor_twitter_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_twitter_profile); ?>"><i class="mvx-font ico-twitter-icon"></i></a><?php } ?>
                                <?php if ($vendor_linkdin_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_linkdin_profile); ?>"><i class="mvx-font ico-linkedin-icon"></i></a><?php } ?>
                                <?php if ($vendor_google_plus_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_google_plus_profile); ?>"><i class="mvx-font ico-google-plus-icon"></i></a><?php } ?>
                                <?php if ($vendor_youtube) { ?> <a target="_blank" href="<?php echo esc_url($vendor_youtube); ?>"><i class="mvx-font ico-youtube-icon"></i></a><?php } ?>
                                <?php if ($vendor_instagram) { ?> <a target="_blank" href="<?php echo esc_url($vendor_instagram); ?>"><i class="mvx-font ico-instagram-icon"></i></a><?php } ?>
                                <?php if ($vendor_pinterest_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_pinterest_profile); ?>"><i class="mvx-font icon-pinterest"></i></a><?php } ?>
                                <?php do_action( 'mvx_vendor_store_header_social_link', $vendor_id ); ?>
                            </div>
                        </div>
                        <div class='mvx-butn-area'>
                            <!-- Follow button will be added here -->
                            <?php if (mvx_is_module_active('follow-store')) { ?>
                                <button type="button" class="mvx-butn <?php echo is_user_logged_in() ? 'mvx-stroke-butn' : ''; ?>" data-vendor_id=<?php echo esc_attr($vendor_id); ?> data-status=<?php echo esc_attr($follow_status_key); ?> ><span></span><?php echo is_user_logged_in() ? esc_attr($follow_status) : esc_html_e('You must logged in to follow', 'adforest'); ?></button>
                            <?php } ?>
                            <?php do_action( 'mvx_additional_button_at_banner' ); ?>
                        </div>
                    </div>

                </div>
            </div>
            <?php if (!$vendor_hide_description && !empty($description)) { ?>
                <div class="description_data">
                    <?php echo wp_kses_post(htmlspecialchars_decode( wpautop( $description ), ENT_QUOTES )); ?>
                </div>
            <?php } ?>
        </div>
    <?php } elseif ( $template_class == 'template2' ) {
        ?>
        <div class='mvx_bannersec_start mvx-theme03'>
            <div class="mvx-banner-wrap">
                <?php if($banner != '') { ?>
                    <div class='banner-img-cls'>
                        <img src="<?php echo esc_url($banner); ?>" class="mvx-imgcls"/>
                    </div>
                <?php } else { ?>
                    <img src="<?php echo esc_url($MVX->plugin_url . 'assets/images/banner_placeholder.jpg'); ?>" class="mvx-imgcls"/>
                <?php } ?>
                <div class='mvx-banner-area'>
                    <div class='mvx-bannerright'>
                        <div class="socialicn-area">
                            <div class="mvx_social_profile">
                                <?php if ($vendor_fb_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_fb_profile); ?>"><i class="mvx-font ico-facebook-icon"></i></a><?php } ?>
                                <?php if ($vendor_twitter_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_twitter_profile); ?>"><i class="mvx-font ico-twitter-icon"></i></a><?php } ?>
                                <?php if ($vendor_linkdin_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_linkdin_profile); ?>"><i class="mvx-font ico-linkedin-icon"></i></a><?php } ?>
                                <?php if ($vendor_google_plus_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_google_plus_profile); ?>"><i class="mvx-font ico-google-plus-icon"></i></a><?php } ?>
                                <?php if ($vendor_youtube) { ?> <a target="_blank" href="<?php echo esc_url($vendor_youtube); ?>"><i class="mvx-font ico-youtube-icon"></i></a><?php } ?>
                                <?php if ($vendor_instagram) { ?> <a target="_blank" href="<?php echo esc_url($vendor_instagram); ?>"><i class="mvx-font ico-instagram-icon"></i></a><?php } ?>
                                <?php if ($vendor_pinterest_profile) { ?> <a target="_blank" href="<?php echo esc_url($vendor_pinterest_profile); ?>"><i class="mvx-font icon-pinterest"></i></a><?php } ?>
                                <?php do_action( 'mvx_vendor_store_header_social_link', $vendor_id ); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='mvx-banner-below'>
                    <div class='mvx-profile-area'>
                        <img src='<?php echo esc_attr($profile); ?>' class='mvx-profile-imgcls' />
                    </div>
                    <div class="mvx-heading"><?php echo esc_html($vendor->page_title) ?></div>

                    <div class="mvx_vendor_rating">
                        <?php
                        if (mvx_is_module_active('store-review') && get_mvx_vendor_settings('is_sellerreview', 'review_management')) {
                            if (mvx_is_store_page()) {
                                $vendor_term_id = get_user_meta( mvx_find_shop_page_vendor(), '_vendor_term_id', true );
                                $rating_val_array = mvx_get_vendor_review_info($vendor_term_id);
                                $MVX->template->get_template('review/rating.php', array('rating_val_array' => $rating_val_array));
                            }
                        }
                        ?>
                    </div>

                    <div class="mvx-contact-deatil">

                        <?php if (!empty($location) && $vendor_hide_address != 'Enable') { ?><p class="mvx-address"><span><i class="mvx-font ico-location-icon"></i></span><?php echo esc_html($location); ?></p><?php } ?>

                        <?php if (!empty($mobile) && $vendor_hide_phone != 'Enable') { ?><p class="mvx-address"><span><i class="mvx-font ico-call-icon"></i></span><?php echo apply_filters('vendor_shop_page_contact', $mobile, $vendor_id); ?></p><?php } ?>

                        <?php if (!empty($email) && $vendor_hide_email != 'Enable') { ?>
                            <p class="mvx-address"><a href="mailto:<?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?>" class="mvx_vendor_detail"><i class="mvx-font ico-mail-icon"></i><?php echo apply_filters('vendor_shop_page_email', $email, $vendor_id); ?></a></p><?php } ?>

                        <?php
                        if (apply_filters('is_vendor_add_external_url_field', true, $vendor->id)) {
                            $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
                            $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
                            if (empty($external_store_label))
                                $external_store_label = __('External Store URL', 'adforest');
                            if (isset($external_store_url) && !empty($external_store_url)) {
                                ?><p class="external_store_url"><label><a target="_blank" href="<?php echo apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id); ?>"><?php echo esc_html($external_store_label); ?></a></label></p><?php
                            }
                        }
                        ?>
                        <?php do_action('mvx_after_vendor_information',$vendor_id);?>
                    </div>

                    <?php if (!$vendor_hide_description && !empty($description)) { ?>
                        <div class="description_data">
                            <?php echo wp_kses_post(htmlspecialchars_decode( wpautop( $description ), ENT_QUOTES )); ?>
                        </div>
                    <?php } ?>

                    <div class='mvx-butn-area'>
                        <!-- Follow button will be added here -->
                        <?php if (mvx_is_module_active('follow-store')) { ?>
                            <button type="button" class="mvx-butn <?php echo is_user_logged_in() ? 'mvx-stroke-butn' : ''; ?>" data-vendor_id=<?php echo esc_attr($vendor_id); ?> data-status=<?php echo esc_attr($follow_status_key); ?> ><span></span><?php echo is_user_logged_in() ? esc_attr($follow_status) : esc_html_e('You must logged in to follow', 'adforest'); ?></button>
                        <?php } ?>
                        <?php do_action( 'mvx_additional_button_at_banner' ); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
// Additional hook after archive description ended
    do_action('mvx_after_vendor_description', $vendor_id);
}
