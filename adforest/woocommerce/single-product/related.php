<?php

/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     10.3.0
 */

global $adforest_theme;

if (isset($adforest_theme['shop-related-single-on']) && $adforest_theme['shop-related-single-on']) {
    $cats = wp_get_post_terms(get_the_ID(), 'product_cat');
    $categories = array();
    foreach ($cats as $cat) {
        $categories[] = $cat->term_id;
    }
    $countRelated = (isset($adforest_theme['shop-number-of-related-products-single'])) ? $adforest_theme['shop-number-of-related-products-single'] : 4;
    $relatedTitle = (isset($adforest_theme['shop-related-single-title'])) ? $adforest_theme['shop-related-single-title'] : esc_html__("Related Products", "adforest");
    $loop_args = array(
        'post_type' => 'product',
        'posts_per_page' => $countRelated,
        'order' => 'DESC',
        'post__not_in' => array(get_the_ID()),
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'id',
                'terms' => $categories
            )
        )
    );
//    $loop_args = apply_filters('adforest_wpml_show_all_posts', $loop_args);
    $related_products = new WP_Query($loop_args);
    ?>
    <div class="product-qna-box" id="adt-product-qna-box">
        <?php
        if ($related_products->have_posts()) {
            ?>
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4><?php echo esc_html($relatedTitle); ?></h4>
                        <ul class="day-item">
                            <li>
                                <a style="color: #0c0c0c"
                                   href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>"><?php echo esc_html__('View All Product', 'adforest') ?></a>
                            </li>
                        </ul>
                    </div>
                    <div class="owl-carousel carousel-team-style-1 staff-list">
                        <?php
                        while ($related_products->have_posts()) {
                            $related_products->the_post();
                            global $product;
                            $heart_filled = 'fa-heart';
                            $fav_class = "";
                            if (get_user_meta(get_current_user_id(), '_product_fav_id_' . get_the_ID(), true) == get_the_ID()) {
                                $fav_class = 'favourited';
                                $heart_filled = 'fa-heart';
                            }
                            ?>
                            <div class="adt-multivendor-category-ad-card">
                                <div class="category-img-box">
                                    <?php
                                    $image_id = $product->get_image_id();
                                    $image_url = wp_get_attachment_image_url($image_id, 'full');

                                    $default_image = trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';
                                    $img_src = !empty($image_url) ? $image_url : $default_image;
                                    ?>
                                    <a href="<?php the_permalink(); ?>"><img class="img-fluid"
                                                                             src="<?php echo esc_url($img_src); ?>"
                                                                             alt="ad-img"></a>
                                </div>
                                <div class="category-content-box">
                                    <div class="rating">
                                        <span><?php echo esc_html($product->get_average_rating()); ?></span>
                                        <small><?php echo esc_html($product->get_rating_count()) . ' ' . esc_html__("Reviews", "adforest"); ?></small>
                                    </div>
                                    <a href="<?php the_permalink(); ?>">
                                        <h5><?php echo esc_html(wp_trim_words(get_the_title(), 5, '...')); ?></h5>
                                    </a>
                                    <?php
                                    $regular_price = $product->get_regular_price();
                                    $sale_price = $product->get_sale_price();
                                    ?>

                                    <strong class="price">
                                        <?php if (!empty($sale_price)) : ?>
                                            <del><?php echo wc_price($regular_price); ?></del>
                                            <ins><?php echo wc_price($sale_price); ?></ins>
                                        <?php else : ?>
                                            <?php echo wc_price($regular_price); ?>
                                        <?php endif; ?>
                                    </strong>
                                    <div class="detail-btn-box">
                                        <a href="<?php the_permalink(); ?>"
                                           class="detail-btn"><?php echo esc_html__("Detail Now", "adforest"); ?></a>
                                        <a href="javascript:void(0);"
                                           class="favourite product_to_fav  <?php echo esc_attr($fav_class); ?>"
                                           data-productId="<?php echo get_the_ID(); ?>">
                                            <i class="fa <?php echo esc_attr($heart_filled); ?>"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php
        } else {
            echo esc_html__("No Related Products Found!", "adforest");
        }
        ?>
    </div>
    <?php
}