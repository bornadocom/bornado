<?php
global $adforest_theme;

$pid = get_queried_object_id();
if (!$pid) {
    $pid = get_the_ID();
}


if ($adforest_theme['Related_ads_on'] && $pid) {
    $cats = wp_get_post_terms($pid, 'ad_cats');
    if (empty($cats) || is_wp_error($cats)) {
        return;
    }

    $categories = array();
    foreach ($cats as $cat) {
        $categories[] = $cat->term_id;
    }

    $is_active = array(
        'key' => '_adforest_ad_status_',
        'value' => 'active',
        'compare' => '=',
    );

    $args = array(
        'post_type' => 'ad_post',
        'post_status' => 'publish',
        'posts_per_page' => $adforest_theme['max_ads'],
        'order' => 'DESC',
        'orderby' => 'date',
        'post__not_in' => array($pid),
        'tax_query' => array(
            array(
                'taxonomy' => 'ad_cats',
                'field' => 'id',
                'terms' => $categories,
                'operator' => 'IN',
                'include_children' => 0,
            )
        ),
        'meta_query' => array(
            $is_active,
        ),
    );

    $related_style = isset($adforest_theme['related_ad_style']) ? $adforest_theme['related_ad_style'] : "1";
    $sb_related_ads_title = isset($adforest_theme['sb_related_ads_title']) ? $adforest_theme['sb_related_ads_title'] : "Related Ads";
    $sb_related_ads_title_limit = isset($adforest_theme['sb_related_ads_title_limit']) ? $adforest_theme['sb_related_ads_title_limit'] : 1;
    $ad_related_cols = isset($adforest_theme['ads_related_cols']) ? $adforest_theme['ads_related_cols'] : 1;

    $results = new WP_Query($args);

    if ($results->have_posts()) {
        ?>
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
            <div class="related-ads-container" id="related-ads-container">
                <div class="adt-ads-top-box">
                    <h4><?php echo esc_html($sb_related_ads_title); ?></h4>
                </div>
                <?php
                if ($related_style == "1") {
                    ?>
                    <div class="adt-ads-carousel owl-carousel owl-theme" data-columns="<?php echo esc_attr($ad_related_cols); ?>">
                        <?php
                        while ($results->have_posts()) {
                            $results->the_post();

                            if (get_the_ID() == $pid) {
                                continue;
                            }

                            $ad_details = get_ad_post_details(get_the_ID());
                            $category_names = $ad_details['category_names'];
                            $img = $ad_details['img'];
                            $truncated_location = $ad_details['truncated_location'];
                            $ad_title = truncate_string($ad_details['ad_title'], $sb_related_ads_title_limit);
                            $price_html = $ad_details['price_html'];
                            $ad_permalink = $ad_details['ad_link'];
                            $heart_class = $ad_details['heart_class'];
                            $is_featured = $ad_details['is_featured'];
                            $ad_categories_post = $ad_details['categories'];
                            $category_links = [];

                            foreach ($ad_categories_post as $category) {
                                $category_url = get_term_link($category);
                                if (!is_wp_error($category_url)) {
                                    $category_links[] = '<a class="ctg-tag" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
                                }
                            }

                            $category_links_string = implode(' > ', $category_links);
                            $selected_categories = $ad_details['categories'];
                            $category_link = '';
                            $category_name = '';
                            if (!empty($selected_categories) && is_array($selected_categories) && isset($selected_categories[0]) && is_object($selected_categories[0]) && isset($selected_categories[0]->term_id)) {
                                $category_link = get_term_link($selected_categories[0]->term_id);
                                if (is_wp_error($category_link)) {
                                    $category_link = '';
                                }
                                $category_name = isset($selected_categories[0]->name) ? $selected_categories[0]->name : '';
                            }
                            ?>
                            <div class="item">
                                <div class="adt-category-ad-card">
                                    <div class="category-img-box">
                                        <a href="<?php echo esc_url($ad_permalink); ?>">
                                            <img class="img-fluid"
                                                 src="<?php echo esc_url($img); ?>"
                                                 alt="<?php echo esc_attr($ad_title); ?>">
                                        </a>
                                        <?php if ($is_featured) : ?>
                                            <img class="featured-tag"
                                                 src="<?php echo trailingslashit(get_template_directory_uri()) . 'images/featured.png'; ?>"
                                                 alt="featured-tag">
                                        <?php endif; ?>
                                    </div>
                                    <div class="category-content-box">
                                        <div class="adt-ad-cats">
                                            <a class="ctg-tag"
                                               href="<?php echo esc_url($category_link) ?>"><?php echo esc_html($category_name); ?></a>
                                        </div>
                                        <a href="<?php echo esc_url($ad_permalink); ?>">
                                            <h5><?php echo esc_html($ad_title); ?></h5>
                                        </a>
                                        <p>
                                            <i class="fas fa-map-marker-alt"></i><?php echo esc_html($truncated_location); ?>
                                        </p>
                                        <div class="price-box">
                                            <?php echo wp_kses_post($price_html); ?>
                                            <?php
                                            $is_fav_local    = ( strpos( (string) $heart_class, 'fas ' ) !== false );
                                            $fav_title_local = $is_fav_local ? esc_html__( 'Click to remove from favourite', 'adforest' ) : esc_html__( 'Click to make it favourite', 'adforest' );
                                            $fav_extra_local = $is_fav_local ? ' ad-favourited' : '';
                                            ?>
                                            <a href="javascript:void(0);"
                                               data-adid="<?php echo get_the_ID(); ?>"
                                               class="favourite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               title="<?php echo esc_attr( $fav_title_local ); ?>"
                                               aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
                                                <i class="<?php echo esc_attr($heart_class); ?>"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        wp_reset_postdata();
                        ?>
                    </div>
                    <?php
                } else {
                    while ($results->have_posts()) {
                        $results->the_post();

                        if (get_the_ID() == $pid) {
                            continue;
                        }

                        $ad_details = get_ad_post_details(get_the_ID());
                        $category_names = $ad_details['category_names'];
                        $first_img = $ad_details['img'];
                        $truncated_location = $ad_details['location'];
                        $truncated_title = $ad_details['ad_title'];
                        $price_html = $ad_details['price_html'];
                        $ad_permalink = $ad_details['ad_link'];
                        $heart_class = $ad_details['heart_class'];
                        $is_featured = $ad_details['is_featured'];
                        $ad_categories_post = $ad_details['categories'];
                        ?>
                        <div class="adt-category-ad-list">
                            <div class="category-img-box">
                                <a href="<?php echo esc_url($ad_permalink); ?>">
                                    <img class="img-fluid"
                                         src="<?php echo esc_url($first_img); ?>"
                                         alt="<?php echo esc_html(get_the_title()); ?>">

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
                                <a href="javascript:void(0);"
                                   class="favourite ad_to_fav<?php echo esc_attr( $fav_extra_local ); ?>"
                                   data-adid="<?php echo get_the_ID(); ?>"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   title="<?php echo esc_attr( $fav_title_local ); ?>"
                                   aria-label="<?php echo esc_attr( $fav_title_local ); ?>">
                                    <i class="<?php echo esc_attr($heart_class); ?>"></i>
                                </a>
                                <?php
                                $category_links = [];

                                foreach ($ad_categories_post as $category) {
                                    $category_url = get_term_link($category);
                                    if (!is_wp_error($category_url)) {
                                        $category_links[] = '<a class="ctg-tag" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
                                    }
                                }

                                $category_links_string = implode(' > ', $category_links);
                                ?>
                                <div class="adt-ad-cats">
                                    <?php echo wp_kses_post($category_links_string); ?>
                                </div>
                                <a href="<?php the_permalink(); ?>">
                                    <h5><?php echo esc_html($truncated_title); ?></h5></a>
                                <p>
                                    <i class="fas fa-map-marker-alt"></i><?php echo esc_html($truncated_location); ?>
                                </p>
                                <div class="price-box">
                                    <?php echo wp_kses_post($price_html) ?>
                                    <a href="<?php esc_url(the_permalink()); ?>"
                                       class="detail-btn"><?php echo esc_html__("Detail", "adforest"); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
}
?>