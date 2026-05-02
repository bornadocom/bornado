<?php
global $adforest_theme;
$layout_style = $adforest_theme['ad_layout_style'] ?? '1';
$image_thumbnail_size = 'adforest-single-post';
if ($layout_style == '2') {
    $image_thumbnail_size = 'adforest_single_product';
}
$is_thumbnail = $adforest_theme['ad_slider_type'] ?? "1";
$class_no_arrows = ($is_thumbnail != '1') ? "arrows-on-carousel" : "";
$ad_id = get_the_ID();
$media = adforest_get_ad_images(get_the_ID());
$disable_optimize_img = isset($adforest_theme['sb_optimize_img_switch']) && $adforest_theme['sb_optimize_img_switch'] ? TRUE : FALSE;
$title = get_the_title();

$video_urls = array();
$video_attachment_ids = get_post_meta($ad_id, 'adforest_video_uploaded_attachment_', true);

if (isset($adforest_theme['sb_allow_upload_video']) && $adforest_theme['sb_allow_upload_video'] == true && !empty($video_attachment_ids)) {
    if (is_string($video_attachment_ids)) {
        $video_ids_array = explode(',', $video_attachment_ids);
    } else if (is_array($video_attachment_ids)) {
        $video_ids_array = $video_attachment_ids;
    } else {
        $video_ids_array = array($video_attachment_ids);
    }

    foreach ($video_ids_array as $video_id) {
        $video_id = trim($video_id);
        if (!empty($video_id) && is_numeric($video_id)) {
            $video_url = wp_get_attachment_url($video_id);
            if ($video_url) {
                $video_urls[] = array(
                    'id' => $video_id,
                    'url' => $video_url
                );
            }
        }
    }
}
?>
    <div id="sync1" class="owl-carousel owl-theme adt-ads-detail-carousel <?php echo esc_attr($class_no_arrows) ?>">
        <?php
        if (!empty($video_urls)) {
            foreach ($video_urls as $video_data) { ?>
                <div class="item">
                    <div class="video-box">
                        <video controls>
                            <source src="<?php echo esc_url($video_data['url']); ?>" type="video/mp4">
                            <?php echo esc_html__("Your browser does not support the video tag.", "adforest"); ?>
                        </video>
                    </div>
                </div>
            <?php }
        }

        if (count($media) > 0) {
            foreach ($media as $m) {
                $mid = $m->ID ?? $m;
                $img = wp_get_attachment_image_src($mid, $image_thumbnail_size);
                $full_img = wp_get_attachment_image_src($mid, 'full');
                if (!is_array($img) || !is_array($full_img)) {
                    continue;
                }
                if (isset($img[0]) && $img[0] == '') {
                    continue;
                }
                $slider_img = $img[0] ?? "";
                if ($disable_optimize_img) {
                    $slider_img = $full_img[0];
                }
                if (isset($full_img[0])) { ?>
                    <div class="item">
                        <div class="img-box">
                            <a href="<?php echo esc_url($full_img[0]); ?>" data-fancybox="gallery" class="lightbox">
                                <img src="<?php echo esc_attr($slider_img); ?>" alt="<?php echo esc_attr($title); ?>">
                            </a>
                        </div>
                    </div>
                <?php }
            }
        } else {
            if (isset($adforest_theme['sb_default_detail_img']) && $adforest_theme['sb_default_detail_img']) {
                $img = adforest_get_ad_default_image_url($image_thumbnail_size); ?>
                <div class="item">
                    <div class="img-box">
                        <a href="<?php echo esc_url($img); ?>" data-fancybox="gallery" class="lightbox">
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>">
                        </a>
                    </div>
                </div>
            <?php }
        } ?>
    </div>

<?php if ($is_thumbnail == '1') { ?>
    <div id="sync2" class="owl-carousel owl-theme">
        <?php
        if (!empty($video_urls)) {
            foreach ($video_urls as $index => $video_data) { ?>
                <div class="item">
                    <div class="img-box">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/images/video-logo.jpg'); ?>"
                             alt="<?php echo esc_attr(sprintf(__('Video Thumbnail %d', 'adforest'), ($index + 1))); ?>">
                    </div>
                </div>
            <?php }
        }

        if (count($media) > 0) {
            foreach ($media as $m) {
                $mid = '';
                $mid = $m->ID ?? $m;
                $img = wp_get_attachment_image_src($mid, $image_thumbnail_size);
                $full_img = wp_get_attachment_image_src($mid, 'full');
                if (!is_array($img) || !is_array($full_img)) {
                    continue;
                }
                if (isset($img[0]) && $img[0] == '') {
                    continue;
                }
                $slider_img = $img[0] ?? "";
                if ($disable_optimize_img) {
                    $slider_img = $full_img[0];
                }
                if (isset($full_img[0])) { ?>
                    <div class="item">
                        <div class="img-box">
                            <img src="<?php echo esc_attr($slider_img); ?>"
                                 alt="<?php echo esc_attr($title); ?>">
                        </div>
                    </div>
                <?php }
            }
        } else {
            if (isset($adforest_theme['sb_default_detail_img']) && $adforest_theme['sb_default_detail_img']) {
                $img = adforest_get_ad_default_image_url($image_thumbnail_size); ?>
                <div class="item">
                    <div class="img-box">
                        <img src="<?php echo esc_url($img); ?>"
                             alt="<?php echo esc_attr($title); ?>">
                    </div>
                </div>
            <?php }
        } ?>
    </div>
<?php } ?>