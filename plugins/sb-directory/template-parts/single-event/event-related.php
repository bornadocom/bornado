<?php
global $adforest_theme;
$pid = get_the_ID();
?>
<?php 
if (isset($adforest_theme['sb_pro_related_events']) && $adforest_theme['sb_pro_related_events']) {
    $cats = wp_get_post_terms($pid, 'l_event_cat');
    $categories = array();
    foreach ($cats as $cat) {
        $categories[] = $cat->term_id;
    }
    $args = array(
        'post_type' => 'events',
        'post_status' => 'publish',
        'posts_per_page' => isset($adforest_theme['sb_pro_related_events_count']) ? $adforest_theme['sb_pro_related_events_count'] : 3,
        'order' => 'DESC',
        'post__not_in' => array($pid),
        'tax_query' => array(
            array(
                'taxonomy' => 'l_event_cat',
                'field' => 'id',
                'terms' => $categories,
                'operator' => 'IN',
                'include_children' => 0,
            )
        ),
    );
    $events_html = "";
    $results = new WP_Query($args);
    if ($results->have_posts()) {   
        echo  '<div class="related-event"><h4>'.esc_html__('Related events','sb_pro').'</h4>';
        while ($results->have_posts()) {
            $results->the_post();
            $pid = get_the_ID();
            $media = sb_pro_fetch_event_gallery($pid);
            $full_image[0] = "";
            $img = "";
            if (count($media) > 0) {
                foreach ($media as $m) {
                    $mid = '';
                    if (isset($m->ID))
                        $mid = $m->ID;
                    else
                        $mid = $m;

                    $image = wp_get_attachment_image_src($mid, 'adforest-ad-related');
                    $img = isset($image[0]) && $image[0] != "" ? $image[0] : adforest_get_ad_default_image_url();

                    $full_image = wp_get_attachment_image_src($mid, 'full');
                    $full_image[0] = isset($full_image[0]) ? $full_image[0] : "";
                    break;
                }
            }
            $venue = get_post_meta($pid, 'sb_pro_event_venue', true);
            $sb_pro_event_start_date = get_post_meta($pid, 'sb_pro_event_start_date', true);
            $sb_pro_event_end_date = get_post_meta($pid, 'sb_pro_event_end_date', true);
            $sb_pro_event_start_date = date_i18n(get_option('date_format'), strtotime($sb_pro_event_start_date));
            $cats = wp_get_post_terms($pid, 'l_event_cat');
            $categories = "";
            $title = get_the_title();
            foreach ($cats as $cat) {
                $categories = '<small>' . $cat->name . '</small>';
            }
            ?>
            <div class="recent-section-content">
                <div class="row">
                    <div class="col-sm-5">
                        <div class="img-recent-1">
                            <a href="<?php echo get_the_permalink($pid); ?>">   <img class="img-fluid" alt="<?php echo get_the_title(); ?>" src="<?php echo esc_url($img); ?>"></a>
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <div class="recent-ads-list-content">
                            <div class="recent-ads-list-title"><a href="<?php echo get_the_permalink(); ?>"><?php echo adforest_words_count(get_the_title(), 25); ?> </a></div>
                            <ul class="recent-ads-list-location"><li><?php echo adforest_words_count($venue, 30); ?></li></ul>
                            <div class="recent-ads-list-price"><?php echo $sb_pro_event_start_date; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
        echo '</div>';
    }
}
