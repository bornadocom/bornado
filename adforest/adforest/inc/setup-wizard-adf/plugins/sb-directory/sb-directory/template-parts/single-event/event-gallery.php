<?php
wp_enqueue_style('adforest-fancybox', trailingslashit(get_template_directory_uri()) . 'assets/css/jquery.fancybox.min.css');
wp_enqueue_script('adforest-fancybox', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery.fancybox.min.js');

$event_id = get_the_ID();
$media = sb_pro_fetch_event_gallery($event_id);
$title = get_the_title();
$image_thumbnail_size = 'adforest-single-post';
$disable_optimize_img = isset($adforest_theme['sb_optimize_img_switch']) && $adforest_theme['sb_optimize_img_switch'];
?>
<div id="adt-ad-detail-top-box" class="ad-detail-top-box">
    <div id="sync1" class="owl-carousel owl-theme adt-ads-detail-carousel">
        <?php if (!empty($media)): ?>
            <?php foreach ($media as $m):
                $mid = isset($m->ID) ? $m->ID : $m;
                $img = wp_get_attachment_image_src($mid, $image_thumbnail_size);
                $full_img = wp_get_attachment_image_src($mid, 'full');
                $slider_img = $disable_optimize_img ? $full_img[0] : $img[0];
                ?>
                <div class="item">
                    <div class="img-box">
                        <a href="<?php echo esc_url($full_img[0]); ?>" data-caption="<?php echo esc_attr($title); ?>" data-fancybox="gallery">
                            <img src="<?php echo esc_url($slider_img); ?>" alt="<?php echo esc_attr($title); ?>">
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php
            $default_img = adforest_get_ad_default_image_url($image_thumbnail_size);
            ?>
            <div class="item">
                <div class="img-box">
                    <a href="<?php echo esc_url($default_img); ?>" data-caption="<?php echo esc_attr($title); ?>" data-fancybox="gallery">
                        <img src="<?php echo esc_url($default_img); ?>" alt="<?php echo esc_attr($title); ?>">
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="sync2" class="owl-carousel owl-theme">
        <?php if (!empty($media)): ?>
            <?php foreach ($media as $m):
                $mid = isset($m->ID) ? $m->ID : $m;
                $thumb_img = wp_get_attachment_image_src($mid, 'thumbnail');
                ?>
                <div class="item">
                    <div class="img-box">
                        <img src="<?php echo esc_url($thumb_img[0]); ?>" alt="<?php echo esc_attr($title); ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>