<?php
/**
 * Vendor List Map
 *
 * This template can be overridden by copying it to yourtheme/MultiVendorX/shortcode/vendor-list/content-vendor.php
 *
 * @package MultiVendorX/Templates
 * @version 3.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $MVX, $vendor_list;
$vendor = get_mvx_vendor($vendor_id);
$image = $vendor->get_image() ? $vendor->get_image('image', array(125, 125)) : $MVX->plugin_url . 'assets/images/WP-stdavatar.png';
$rating_info = mvx_get_vendor_review_info($vendor->term_id);
$rating = round($rating_info['avg_rating'], 2);
$review_count = empty(intval($rating_info['total_rating'])) ? '' : intval($rating_info['total_rating']);
$vendor_phone = $vendor->phone ? $vendor->phone : __('No number yet', 'adforest');
$vendor_hide_address = get_user_meta($vendor_id, '_vendor_hide_address', true) ? get_user_meta($vendor_id, '_vendor_hide_address', true) : '';
$vendor_hide_phone = get_user_meta($vendor_id, '_vendor_hide_phone', true) ? get_user_meta($vendor_id, '_vendor_hide_phone', true) : '';
$hide_vendor_details = get_mvx_vendor_settings('mvx_hide_vendor_details', 'store');
$should_hide = false;
if ($hide_vendor_details == 'All User') {
    $should_hide = true;
} elseif ($hide_vendor_details == 'Non Logged-in user' && !is_user_logged_in()) {
    $should_hide = true;
}
?>

<div class="adt-seller-card">
    <div class="top-content">
        <img src="<?php echo esc_url($image); ?>" alt="seller-img">
        <a href="<?php echo esc_url($vendor->get_permalink()); ?>" class="store-name">
            <h4><?php echo esc_html($vendor->page_title); ?></h4></a>
    </div>
    <div class="location">
        <i class="fas fa-map-marker-alt"></i><?php echo esc_html($vendor->get_formatted_address()) ? $vendor->get_formatted_address() : __('No Address found', 'adforest'); ?>
    </div>
    <div class="bottom-content">
        <div class="rating">
            <?php if ($review_count) : ?>
                <span><?php echo esc_html($review_count); ?><?php esc_html_e('Reviews', 'adforest'); ?></span>
            <?php else : ?>
                <span><?php esc_html_e('No Reviews', 'adforest'); ?></span>
            <?php endif; ?>

            <?php
            // Display dynamic stars based on average rating
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= floor($rating)) {
                    echo '<i class="fas fa-star"></i>';
                } elseif ($i - $rating < 1) {
                    echo '<i class="fas fa-star-half-alt"></i>';
                } else {
                    echo '<i class="far fa-star"></i>';
                }
            }
            ?>
        </div>
        <a href="<?php echo esc_url($vendor->get_permalink()); ?>"
           class="seller-prf-btn"><?php echo __("View Profile", "adforest") ?></a>
    </div>
</div>
