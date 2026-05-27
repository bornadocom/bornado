<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce/Templates
 * @version     1.6.4
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
global $adforest_theme;
wp_enqueue_script('adforest-fancybox');
wp_enqueue_style('adforest-fancybox');
function adforest_increment_product_view_count()
{
    if (is_product()) {
        global $post;
        if (isset($post->ID)) {
            $product_id = $post->ID;
            $view_count = get_post_meta($product_id, '_product_view_count', true);
            if (empty($view_count)) {
                $view_count = 0;
            }
            $view_count++;
            update_post_meta($product_id, '_product_view_count', $view_count);
        }
    }
}

if (isset($adforest_theme['shop-turn-on']) && $adforest_theme['shop-turn-on']) {
    wp_enqueue_script('star-rating');
    wp_enqueue_style('star-rating', trailingslashit(get_template_directory_uri()) . 'assets/css/star-rating.css');
    $banner_img = isset($adforest_theme['single-product-banner']['url']) ? $adforest_theme['single-product-banner']['url'] : "#";
    get_header('shop');
    /**
     * woocommerce_before_main_content hook.
     *
     * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
     * @hooked woocommerce_breadcrumb - 20
     */
    ?>
    <?php
    $adt_container_class = "";
    if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
        $adt_container_class = "adt-container";
    }

    $page_id = get_queried_object_id();

    $page_header_style = get_post_meta($page_id, '_page_header_style', true);

    if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2")) {
        $adt_container_class = "adt-container";
    }
    $shop_sidebar_single_on = isset($adforest_theme['shop-sidebar-single-on']) ? $adforest_theme['shop-sidebar-single-on'] : '';
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
    adforest_custom_breadcrumbs();
    ?>
    <section class="listing-list-salesman">
        <div class="container">
            <div class="first-heading">
                <?php
                do_action('woocommerce_before_main_content');
                do_action('woocommerce_after_main_content');
                ?>
            </div>
        </div>
    </section>

    <!-- adt-multivendor-product-detail-section-start -->
    <section class="adt-multivendor-product-detail-section">
        <div class="container <?php echo esc_attr($adt_container_class); ?>">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-multivendor-product-detail-wrapper">
                        <?php if ($shop_sidebar_single_on == '1') { ?>
                            <div class="adt-multivendor-product-sidebar">
                                <div class="adt-detail-content-list sticky">
                                    <ul>
                                        <li>
                                            <a class="ad-content-item active"
                                               href="#adt-product-detail-box"><?php echo esc_html__("Top", "adforest"); ?></a>
                                        </li>
                                        <li>
                                            <a class="ad-content-item"
                                               href="#adt-product-description-box"><?php echo esc_html__("Description", "adforest"); ?></a>
                                        </li>
                                        <li>
                                            <a class="ad-content-item"
                                               href="#adt-product-add-review-box"><?php echo esc_html__("Write a Review", "adforest"); ?></a>
                                        </li>
                                        <li>
                                            <a class="ad-content-item"
                                               href="#adt-product-rating-box"><?php echo esc_html__("Reviews", "adforest"); ?></a>
                                        </li>
                                        <?php if (isset($adforest_theme['shop-related-single-on']) && $adforest_theme['shop-related-single-on']) { ?>
                                            <li>
                                                <a class="ad-content-item"
                                                   href="#adt-product-qna-box"><?php echo esc_html__("Related Products", "adforest"); ?></a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                                <div class="adt-vertical-ad-box">
                                    <?php
                                    if (is_active_sidebar('adforest_woocommerce_detail_widget')) :
                                        dynamic_sidebar('adforest_woocommerce_detail_widget');
                                    endif;
                                    ?>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="adt-multivendor-product-content">
                            <div class="product-detail-box" id="adt-product-detail-box">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <?php
                                        global $product;
                                        if (empty($product) || !$product instanceof WC_Product) {
                                            $product = wc_get_product(get_the_ID());
                                        }
                                        if (!$product) {
                                            return;
                                        }

                                        $product_id = $product->get_id();
                                        adforest_increment_product_view_count();
                                        $featured_image = get_the_post_thumbnail_url($product->get_id(), 'full');
                                        $gallery_image_ids = $product->get_gallery_image_ids();
                                        ?>

                                        <div class="ad-detail-top-box">
                                            <!-- Main Carousel (Large Images) -->
                                            <div id="sync3" class="owl-carousel owl-theme adt-ads-detail-carousel">
                                                <?php if ($featured_image) : ?>
                                                    <div class="item">
                                                        <div class="img-box d-flex justify-content-center align-items-center">
                                                            <a href="<?php echo esc_url($featured_image); ?>"
                                                               data-fancybox="gallery" class="lightbox">
                                                                <img src="<?php echo esc_url($featured_image); ?>"
                                                                     alt="<?php echo esc_attr(get_the_title()); ?>">
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($gallery_image_ids) : ?>
                                                    <?php foreach ($gallery_image_ids as $image_id) : ?>
                                                        <?php $image_url = wp_get_attachment_url($image_id); ?>
                                                        <div class="item">
                                                            <div class="img-box d-flex justify-content-center align-items-center">
                                                                <a href="<?php echo esc_url($image_url); ?>"
                                                                   data-fancybox="gallery" class="lightbox">
                                                                    <img src="<?php echo esc_url($image_url); ?>"
                                                                         alt="<?php echo esc_attr(get_the_title()); ?>">
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Thumbnail Carousel (Smaller Images) -->
                                            <div id="sync4" class="owl-carousel owl-theme">
                                                <?php if ($featured_image) : ?>
                                                    <div class="item">
                                                        <div class="img-box">
                                                            <img src="<?php echo esc_url($featured_image); ?>"
                                                                 alt="<?php echo esc_attr(get_the_title()); ?>">
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($gallery_image_ids) : ?>
                                                    <?php foreach ($gallery_image_ids as $image_id) : ?>
                                                        <?php $image_url = wp_get_attachment_url($image_id); ?>
                                                        <div class="item">
                                                            <div class="img-box">
                                                                <img src="<?php echo esc_url($image_url); ?>"
                                                                     alt="<?php echo esc_attr(get_the_title()); ?>">
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="product-content-box">
                                            <div class="product-top-meta">
                                                <div class="views">
                                                    <?php
                                                    $ad_views = get_post_meta($product_id, '_product_view_count', true);
                                                    if ($ad_views >= 1000) {
                                                        $formatted_views = number_format($ad_views / 1000, 1) . 'K';
                                                    } else {
                                                        $formatted_views = $ad_views;
                                                    }
                                                    echo esc_html__("Views: ", 'adforest');
                                                    ?>
                                                    <span><?php echo esc_html($formatted_views); ?></span>
                                                </div>
                                                <?php
                                                // Display Seller/Vendor Info
                                                if (function_exists('get_mvx_product_vendors')) {
                                                    $vendor = get_mvx_product_vendors($product_id);
                                                    if ($vendor && !empty($vendor)) {
                                                        $vendor_id = $vendor->id;
                                                        $vendor_name = $vendor->page_title ? $vendor->page_title : $vendor->user_data->display_name;
                                                        $vendor_link = $vendor->get_permalink();
                                                        // Get vendor store image, fallback to profile image, then to default
                                                        $vendor_image = $vendor->get_image('image', array(40, 40));
                                                        if (empty($vendor_image)) {
                                                            $vendor_image = $vendor->get_image('profile_image', array(40, 40));
                                                        }
                                                        if (empty($vendor_image)) {
                                                            $vendor_image = get_avatar_url($vendor_id, array('size' => 40));
                                                        }
                                                        ?>
                                                        <div class="product-seller-info">
                                                            <a href="<?php echo esc_url($vendor_link); ?>" class="seller-profile-link">
                                                                <img src="<?php echo esc_url($vendor_image); ?>" alt="<?php echo esc_attr($vendor_name); ?>" class="seller-avatar">
                                                                <span class="seller-name"><?php echo esc_html__("Sold by: ", "adforest"); ?><strong><?php echo esc_html($vendor_name); ?></strong></span>
                                                            </a>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <?php
                                            $average_rating = $product->get_average_rating();
                                            $review_count = $product->get_review_count();


                                            $product_title = get_the_title();
                                            $product_description = get_the_content();
                                            $short_description = $product->get_short_description();

                                            $default_quantity = 1;
                                            ?>
                                            <div class="rating">
                                                <span><?php echo esc_html($average_rating); ?></span>
                                                <small><?php echo esc_html($review_count); ?> Reviews</small>
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
                                            <h3><?php echo esc_html($product_title); ?></h3>
                                            <?php if ($short_description) : ?>
                                                <?php echo wp_kses_post($short_description); ?>
                                            <?php endif; ?>
                                            <ul class="product-quantity-detail">
                                                <li>
                                                    <div class="quantity-box">
                                                        <button data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="<?php echo esc_attr__("Remove One", "adforest"); ?>"
                                                                id="decrease-quantity"><i
                                                                    class="fas fa-chevron-left"></i></button>
                                                        <span id="product-quantity"
                                                              data-default-qty="<?php echo esc_html($default_quantity); ?>">
                                                            <?php echo esc_html($default_quantity); ?>
                                                        </span>
                                                        <button data-toggle="tooltip"
                                                                data-placement="top"
                                                                title="<?php echo esc_attr__("Add One", "adforest"); ?>"
                                                                id="increase-quantity"><i
                                                                    class="fas fa-chevron-right"></i></button>
                                                    </div>
                                                </li>
                                                <li>
                                                    <a data-bs-toggle="modal" data-bs-target=".share-product"
                                                       class="extra-btn" data-adid="<?php echo get_the_ID(); ?>"
                                                       href="javascript:void(0);" data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="<?php echo esc_attr__("Share Product", "adforest"); ?>">
                                                        <i class="fas fa-share-alt"></i>
                                                    </a>
                                                </li>
                                                <?php
                                                $heart_filled = 'fa-heart';
                                                $fav_class = "";
                                                $fav_tooltip_text = esc_html__("Add to Wishlist", "adforest");
                                                if (get_user_meta(get_current_user_id(), '_product_fav_id_' . $product_id, true) == $product_id) {
                                                    $fav_class = 'favourited';
                                                    $heart_filled = 'fa-heart';
                                                    $fav_tooltip_text = esc_html__("Remove from Wishlist", "adforest");
                                                }
                                                ?>
                                                <li>
                                                    <a href="javascript:void(0);"
                                                       class="extra-btn product_to_fav <?php echo esc_attr($fav_class); ?>"
                                                       data-productId="<?php echo esc_attr($product_id); ?>"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="<?php echo esc_attr($fav_tooltip_text); ?>">
                                                        <i class="fa <?php echo esc_attr($heart_filled); ?>"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);"
                                                       class="adt-theme-button-2 custom_add_to_cart_button"
                                                       data-product-id="<?php echo esc_attr($product_id); ?>">
                                                        <?php echo esc_html__("Add To Cart", "adforest"); ?>
                                                    </a>
                                                </li>

                                            </ul>
                                            <ul class="vendor-services-list">
                                                <?php
                                                if (isset($adforest_theme['services_boxes']) && !empty($adforest_theme['services_boxes'])) {
                                                    $services = $adforest_theme['services_boxes'];
                                                    usort($services, function ($a, $b) {
                                                        return $a['sort'] <=> $b['sort'];
                                                    });
                                                    foreach ($services as $service) { ?>
                                                        <li>
                                                            <i class="<?php echo esc_attr($service['url']); ?>"></i><?php echo esc_html($service['title']); ?>
                                                        </li>
                                                    <?php }
                                                } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <?php if (isset($adforest_theme['shop_advertisement_top']['url'])) { ?>
                                        <div class="adt-horizontal-ad-box">
                                            <img class="img-fluid"
                                                 src="<?php echo esc_url($adforest_theme['shop_advertisement_top']['url'], 'adforest'); ?>"
                                                 alt="horizontal-ad">
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="product-description-box" id="adt-product-description-box">
                                <h4><?php echo esc_html__("Description:", "adforest"); ?></h4>
                                <?php if ($product_description) : ?>
                                    <?php echo wp_kses_post($product_description); ?>
                                <?php endif; ?>
                            </div>
                            <div class="product-add-review-box" id="adt-product-add-review-box">
                                <h4><?php echo esc_html__("Add A Review", "adforest"); ?></h4>
                                <form id="product-review-form" method="post">
                                    <label for="title"
                                           class="form-label"><?php echo esc_html__("Your rating", "adforest"); ?></label>

                                    <div class="col-md-12 col-sm-12 margin-bottom-30">
                                        <div class="form-group">
                                            <div dir="ltr">
                                                <input id="input-21b" name="rating" value="1" type="hidden"
                                                       data-show-clear="false" <?php if (is_rtl()) { ?> dir="rtl"
                                                       <?php } ?>class="rating" data-min="0" data-max="5" data-step="1"
                                                       data-size="xs" required title="required">
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="input-field">
                                        <label for="title"
                                               class="form-label"><?php echo esc_html__("Title", "adforest"); ?></label>
                                        <input type="text" class="form-control" id="title"
                                               placeholder="<?php echo esc_attr__("Enter Your Title here", "adforest"); ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="input-field">
                                                <label for="name"
                                                       class="form-label"><?php echo esc_html__("Name", "adforest"); ?></label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                       placeholder="<?php echo esc_attr__("Enter Your Name here", "adforest"); ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="input-field">
                                                <label for="email"
                                                       class="form-label"><?php echo esc_html__("Email", "adforest"); ?></label>
                                                <input type="text" class="form-control" id="email" name="email"
                                                       placeholder="<?php echo esc_attr__("Enter Your Email here", "adforest"); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="input-field">
                                        <label for="description"
                                               class="form-label"><?php echo esc_html__("Review Description", "adforest"); ?></label>
                                        <textarea class="form-control" id="description" name="description"
                                                  placeholder="<?php echo esc_attr__("Enter Your Message here", "adforest"); ?>"></textarea>
                                    </div>
                                    <div class="submit-btn-box">
                                        <!-- <div class="remember">
                                            <label class="container">
                                                <?php echo esc_html__("Save my name, email, and website in this browser
                                                for the next time I comment.", "adforest"); ?>
                                                <input type="checkbox" checked="checked" name="remember_me">
                                                <span class="checkmark"></span>
                                            </label>
                                        </div> -->
                                        <button type="submit" class="adt-button-dark">
                                            <?php echo esc_html__("Submit Review", "adforest"); ?>
                                        </button>
                                    </div>
                                    <input type="hidden" name="product_id" value="<?php echo get_the_ID(); ?>"/>
                                    <input type="hidden" name="action" value="submit_product_review"/>
                                    <input type="hidden" name="security" value="<?php echo wp_create_nonce('sb_product_review_nonce'); ?>"/>
                                </form>
                            </div>
                            <?php
                            $user_info = get_userdata(get_current_user_id());
                            $vendor_desc = get_user_meta($user_info->ID, '_sb_user_intro', true);
                            if (isset($vendor_desc) && $vendor_desc != "") {
                                ?>
                                <div class="product-description-box" id="adt-product-vendor-box">
                                    <p>
                                        <?php
                                        echo esc_html__($vendor_desc);
                                        ?>
                                    </p>
                                </div>
                            <?php } ?>
                            <div class="product-rating-box" id="adt-product-rating-box">
                                <h4><?php echo esc_html__("Rating & Review", "adforest"); ?></h4>
                                <?php
                                $reviews = get_comments(array(
                                    'post_id' => $product_id,
                                    'status' => 'approve',
                                    'type' => 'review',
                                ));

                                if ($reviews) {
                                    ?>
                                    <div class="review-list-wrapper">
                                        <ul class="review-list">
                                            <?php
                                            foreach ($reviews as $review) :
                                                $rating = get_comment_meta($review->comment_ID, 'rating', true);
                                                $review_title = get_comment_meta($review->comment_ID, 'review_title', true);
                                                $review_date = date_i18n('F d, Y', strtotime($review->comment_date));
                                                $review_author = $review->comment_author;
                                                $review_content = $review->comment_content;
                                                $avatar = get_avatar($review->comment_author_email, 64);
                                                if (!$avatar) {
                                                    $avatar = '<img src="' . get_template_directory_uri() . '/images/no-image.png" alt="No Image" />';
                                                }
                                                $stars = '';
                                                if ($rating) {
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            $stars .= '<i class="fas fa-star"></i>';
                                                        } else {
                                                            $stars .= '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                }
                                                ?>
                                                <li>
                                                    <div class="review-box">
                                                        <div class="user-img">
                                                            <?php echo wp_kses_post($avatar); ?>
                                                        </div>
                                                        <div class="user-content">
                                                            <h4><?php echo esc_html($review_author); ?></h4>
                                                            <ul>
                                                                <li>
                                                                    <i class="fas fa-calendar-week"></i> <?php echo esc_html($review_date); ?>
                                                                </li>
                                                                <li>
                                                                    <span><?php echo esc_html__($review_title); ?></span>
                                                                    <?php echo wp_kses_post($stars); ?>
                                                                </li>
                                                            </ul>
                                                            <p><?php echo esc_html__($review_content); ?></p>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php
                                            endforeach;

                                            ?>
                                        </ul>
                                    </div>
                                    <?php
                                } else {
                                    echo '<p>' . esc_html__("No reviews found.", "adforest") . '</p>';
                                }
                                ?>
                            </div>

                            <?php require trailingslashit(get_template_directory()) . 'woocommerce/single-product/related.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- adt-multivendor-product-detail-section-end -->

    <!-- adt-multivendor-services-section-start -->
    <section class="adt-multivendor-services-section">
        <div class="container">
            <div class="row">
                <?php
                if (isset($adforest_theme['services_boxes']) && !empty($adforest_theme['services_boxes'])) {
                    $services = $adforest_theme['services_boxes'];
                    usort($services, function ($a, $b) {
                        return $a['sort'] <=> $b['sort'];
                    });
                    foreach ($services as $service) {
                        echo '<div class="col-6 col-lg-3">';
                        echo '    <div class="adt-multivendor-services-box">';
                        echo '        <i class="' . esc_attr($service['url']) . '"></i>';
                        echo '        <h6>' . esc_html($service['title']) . '</h6>';
                        echo '        <p>' . esc_html($service['description']) . '</p>';
                        echo '    </div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>
    <!-- adt-multivendor-services-section-end -->
    <?php
    get_footer();
} else {
    $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
    wp_redirect(get_the_permalink($sb_packages_page));
    exit;
}
?>

<div class="modal fade share-product" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content <?php echo esc_attr($flip_it); ?>">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo esc_html__('Share', 'adforest'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body <?php echo esc_attr($flip_it); ?>">
                <div class="recent-products">
                    <div class="recent-product-list">
                        <div class="recent-product-container">
                            <div class="recent-product-list-image">
                                <?php
                                global $product;
                                $product_id = $product->get_id();

                                if (has_post_thumbnail($product_id)) {
                                    $image_id = get_post_thumbnail_id($product_id);
                                    $image_url = wp_get_attachment_image_src($image_id, 'woocommerce_thumbnail');
                                    $img = $image_url[0];
                                } else {
                                    $img = wc_placeholder_img_src('woocommerce_thumbnail');
                                }
                                ?>
                                <a href="<?php echo get_the_permalink($product_id); ?>"
                                   class="recent-product-list-image-inner">
                                    <img src="<?php echo esc_url($img); ?>"
                                         alt="<?php echo get_the_title($product_id); ?>">
                                </a>
                            </div>
                            <div class="recent-product-list-content">
                                <h3 class="recent-product-list-title">
                                    <a href="<?php echo get_the_permalink($product_id); ?>">
                                        <?php echo get_the_title($product_id); ?>
                                    </a>
                                </h3>
                                <div class="recent-product-list-price">
                                    <?php echo wp_kses_post($product->get_price_html()); ?>
                                </div>
                                <p><?php echo wp_trim_words($product->get_short_description(), 25, '...'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="share-link"><?php echo esc_html__('Link', 'adforest'); ?></div>
                <p>
                    <a href="<?php echo get_the_permalink($product_id); ?>"><?php echo get_the_permalink($product_id); ?></a>
                </p>
            </div>
            <div class="modal-footer">
                <?php
                if (function_exists('woocommerce_social_share')) {
                    // echo woocommerce_social_share();
                } else {
                    $share_url = get_the_permalink($product_id);
                    $share_title = get_the_title($product_id);
                    ?>
                    <ul class="wc-social-share">
                        <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($share_url); ?>"
                               target="_blank" class="facebook"><i class="fab fa-facebook-f"></i></a></li>
                        <li>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($share_url); ?>&text=<?php echo urlencode($share_title); ?>"
                               target="_blank" class="twitter"><i class="fab fa-twitter"></i></a></li>
                        <li>
                            <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode($share_url); ?>&media=<?php echo urlencode($img); ?>&description=<?php echo urlencode($share_title); ?>"
                               target="_blank" class="pinterest"><i class="fab fa-pinterest-p"></i></a></li>
                        <li>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($share_url); ?>&title=<?php echo urlencode($share_title); ?>"
                               target="_blank" class="linkedin"><i class="fab fa-linkedin-in"></i></a></li>
                        <li>
                            <a href="mailto:?subject=<?php echo urlencode($share_title); ?>&body=<?php echo urlencode($share_url); ?>"
                               class="email"><i class="fas fa-envelope"></i></a></li>
                    </ul>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>