<?php global $adforest_theme;

while ($results->have_posts()) {
    ?>
    <div class="search-ads-result-box">
        <?php
        $results->the_post();
        $ad_details = get_ad_post_details(get_the_ID());
        $category_names = $ad_details['category_names'];
        $first_img = $ad_details['img'];
        $truncated_location = $ad_details['location'];
        $truncated_title = $ad_details['ad_title'];
        $price_html = $ad_details['price_html'];
        $ad_permalink = $ad_details['ad_link'];
        $heart_class = $ad_details['heart_class'];
        $is_featured = $ad_details['is_featured'];
        ?>
        <div class="adt-category-ad-list">
            <div class="category-img-box">
                <a href="<?php echo esc_url($ad_permalink, 'adforest'); ?>">
                    <img class="img-fluid"
                         src="<?php echo esc_url($first_img, "adforest"); ?>"
                         alt="ad-img">

                    <?php if ($is_featured): ?>
                        <span class="featured-label"><?php echo esc_html__('Featured', 'adforest'); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="category-content-box">
                <?php
                $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                ?>
                <a href="javascript:void(0);" class="favourite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>"
                   data-adid="<?php echo get_the_ID(); ?>"
                   data-toggle="tooltip"
                   data-placement="top"
                   title="<?php echo esc_attr( $fav_title_local ); ?>"
                   aria-label="<?php echo esc_attr( $fav_title_local ); ?>"><i
                            class="<?php echo esc_attr($heart_class); ?>"></i></a>
                <a class="ctg-tag"
                   href="#"><?php echo esc_html($category_names[count($category_names) - 1]); ?></a>
                <a href="<?php the_permalink(); ?>">
                    <h5><?php echo esc_html($truncated_title); ?></h5></a>
                <p>
                    <i class="fas fa-map-marker-alt"></i><?php echo esc_html($truncated_location); ?>
                </p>
                <div class="price-box">
                    <?php echo wp_kses_post($price_html); ?>
                    <a href="<?php the_permalink(); ?>"
                       class="detail-btn"><?php echo esc_html__("Detail", "adforest"); ?></a>
                </div>
            </div>
        </div>
    </div>
<?php }