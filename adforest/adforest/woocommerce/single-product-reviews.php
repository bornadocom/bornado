<?php
/**
 * Display single product reviews (comments)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product-reviews.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     9.7.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $product;
if (!comments_open()) {
    return;
}
?>
<div id="reviews" class="woocommerce-Reviews">
    <div id="comments">
        <h2 class="woocommerce-Reviews-title">
            <?php echo esc_html__('Reviews', 'adforest'); ?></h2>
        <?php if (have_comments()) :
            ?>
            <ol class="commentlist">
                <?php wp_list_comments(apply_filters('woocommerce_product_review_list_args', array('callback' => 'woocommerce_comments'))); ?>
            </ol>
            <?php
            if (get_comment_pages_count() > 1 && get_option('page_comments')) :
                echo '<nav class="woocommerce-pagination">';
                paginate_comments_links(apply_filters('woocommerce_comment_pagination_args', array(
                    'prev_text' => '&larr;',
                    'next_text' => '&rarr;',
                    'type' => 'list',
                )));
                echo '</nav>';
            endif;
            ?>
        <?php else : ?>
            <p class="woocommerce-noreviews"><?php echo esc_html__('There are no reviews yet.', 'adforest'); ?></p>
        <?php endif; ?>
    </div>
    <div class="clearfix"></div>
</div>