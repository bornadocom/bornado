<?php
$pid = get_the_ID();
global $adforest_theme;
$style = isset($adforest_theme['ad_layout_style']) ? $adforest_theme['ad_layout_style'] : 1;

$watermark_class_sold = "";
$watermark_class_expired = "";
if ($style == 1) {
    $watermark_class_sold = "ad-closed-s1";
    $watermark_class_expired = "ad-expired-s1";
} else {
    $watermark_class_sold = "ad-closed-s2";
    $watermark_class_expired = "ad-expired-s2";
}
?>
<?php if (get_post_meta($pid, '_adforest_ad_status_', true) == 'sold') { ?>
    <div class="<?php echo esc_attr($watermark_class_sold); ?>"><img class="img-fluid" src="<?php echo esc_url($adforest_theme['sb_ad_sold']['url']); ?>"
                                alt="<?php echo esc_attr__('sold out', 'adforest'); ?>"></div>
<?php } ?>
<?php if (get_post_meta($pid, '_adforest_ad_status_', true) == 'expired') { ?>
    <div class="<?php echo esc_attr($watermark_class_expired); ?>"><img class="img-fluid" src="<?php echo esc_url($adforest_theme['sb_ad_expired']['url']); ?>"
                                 alt="<?php echo esc_attr__('expired', 'adforest'); ?>"></div>
<?php } ?>