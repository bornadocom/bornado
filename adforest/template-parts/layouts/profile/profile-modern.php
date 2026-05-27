<?php
global $adforest_theme;
$author_id = get_query_var('author');
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$posts_per_page = get_option('posts_per_page') ?? 8;

$args = [
    'post_type' => 'ad_post',
    'posts_per_page' => $posts_per_page,
    'author' => $author_id,
    'paged' => $paged,
    'post_status' => 'publish',
];

$ads_query = new WP_Query($args);

$vertical_ad = isset($adforest_theme['adforest_user_page_ad_vertical']) ? $adforest_theme['adforest_user_page_ad_vertical'] : "";
$horizontal_ad = isset($adforest_theme['adforest_user_page_ad_horizontal']) ? $adforest_theme['adforest_user_page_ad_horizontal'] : "";
?>

<section class="adt-seller-detail-section">
    <div class="container adt-container">
        <div class="row">
            <div class="col-lg-4 col-xl-3">
                <?php get_template_part('template-parts/layouts/profile/profile-header'); 
                if($vertical_ad) {
                    ?>
                    <div class="adt-vertical-ad-box">
                        <?php
                        if ( function_exists( 'adforest_render_theme_ad' ) ) {
                            adforest_render_theme_ad( 'adforest_user_page_ad_vertical' );
                        } else {
                            echo wp_kses_post( $vertical_ad );
                        }
                        ?>
                    </div>
                <?php } ?>
            </div>
            <div class="col-lg-8 col-xl-9">
                <div class="adt-horizontal-ad-box">
                    <?php if($horizontal_ad) {
                        if ( function_exists( 'adforest_render_theme_ad' ) ) {
                            adforest_render_theme_ad( 'adforest_user_page_ad_horizontal' );
                        } else {
                            echo wp_kses_post( $horizontal_ad );
                        }
                    } ?>
                </div>
                <?php
                $grid_cols = $adforest_theme['no_of_ad_in_search_page_row'];
                $plugin_file = 'adforest-framework/index.php';

                if (!function_exists('is_plugin_active_for_network')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                $is_plugin_active = in_array($plugin_file, apply_filters('active_plugins', get_option('active_plugins')))
                    || (is_multisite() && is_plugin_active_for_network($plugin_file));
                if ($ads_query->have_posts() && $is_plugin_active) {
                    // Match the search-page grid wrapper rules — list_view
                    // cards span full width and require the list-mode class
                    // on the grid container, otherwise the card image
                    // overflows the column with no styling applied.
                    $profile_layout_pre = isset($adforest_theme['adforest_grid_layout']) ? $adforest_theme['adforest_grid_layout'] : 'modern';
                    $grid_extra_class   = '';
                    $grid_cols_render   = $grid_cols;
                    if ($profile_layout_pre === 'list_view' && function_exists('adforest_load_search_card')) {
                        $grid_cols_render = '1';
                        $grid_extra_class = 'adt-search-ads-list-mode';
                    } ?>
                    <div class="adt-search-ads-grid <?php echo "adt-search-ads-col-" . esc_attr($grid_cols_render); ?> <?php echo esc_attr($grid_extra_class); ?>">
                        <?php
                        $search_page_adverts = $adforest_theme['search_page_grid_adverts'];
                        $ads = explode('|', $search_page_adverts);
                        $total_ads = count($ads);
                        $ad_index = 0;

                        $ad_threshold = rand(3, 4);
                        $listing_counter = 0;
                        while ($ads_query->have_posts()) : $ads_query->the_post();
                            // Remove the duplicate $ads_query->the_post(); line
                            $listing_counter++;
                            $ad_details = get_ad_post_details(get_the_ID());
                            $category_names = $ad_details['category_names'];
                            $first_img = $ad_details['img'];
                            $truncated_location = $ad_details['truncated_location'];
                            $truncated_title = truncate_string($ad_details['ad_title'], 40);
                            $price_html = $ad_details['price_html'];
                            $ad_permalink = $ad_details['ad_link'];
                            $heart_class = $ad_details['heart_class'];
                            $is_fav      = isset($ad_details['is_fav']) ? (bool) $ad_details['is_fav'] : false;
                            $fav_title   = $is_fav ? esc_html__('Click to remove from favourite', 'adforest') : esc_html__('Click to make it favourite', 'adforest');
                            $fav_extra   = $is_fav ? ' ad-favourited' : '';
                            $is_featured = $ad_details['is_featured'];
                            $all_ad_images = $ad_details['all_ad_images'];
                            $ad_poster_img = $ad_details['ad_poster_img'];
                            $ad_poster_name = $ad_details['ad_poster_name'];
                            $ad_title = truncate_string($ad_details['ad_title'], 40);
                            $featured_tag = $is_featured ? '<img style="transform: rotate(180deg);" src="' . get_template_directory_uri() . '/images/featured.png' . '" alt="featured-tag" class="featured-tag">' : '';
                            $ad_categories_post = $ad_details['categories'];
                            $ad_type = get_post_meta(get_the_ID(), '_adforest_ad_type', true);

                            $category_links = [];

                            foreach ($ad_categories_post as $category) {
                                $category_url = get_term_link($category);
                                if (!is_wp_error($category_url)) {
                                    $category_links[] = '<a class="ctg-tag" href="' . esc_url($category_url) . '">' . esc_html($category->name) . '</a>';
                                }
                            }

                            $category_links_string = implode(' > ', $category_links);
                            ?>
                            <?php
                            $profile_layout = isset($adforest_theme['adforest_grid_layout']) ? $adforest_theme['adforest_grid_layout'] : 'modern';
                            if ($profile_layout == 'simple') {
                                echo adforest_ad_grid_1($ad_permalink, $first_img, $is_featured, $ad_categories_post, $ad_details, $truncated_title, $truncated_location, $price_html, $heart_class);
                            } elseif ($profile_layout == 'with_labels') {
                                ?>
                                <div class="item search_with_labels_grid ">
                                    <?php
                                    echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class);
                                    ?>
                                </div>
                                <?php
                            } elseif ($profile_layout == 'modern') {
                                ?>
                                <div class="item search_with_labels_grid ">
                                    <?php
                                    echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                    ?>
                                </div>
                                <?php
                            } elseif (
                                function_exists('adforest_load_search_card')
                                && in_array($profile_layout, array('modern_card', 'compact_grid', 'list_view'), true)
                            ) {
                                // Search 2.0 card layouts — dispatch through the
                                // shared template loader so the profile page
                                // renders the same card the user picked for
                                // search results. Without this branch the
                                // ads grid silently rendered empty whenever
                                // the option was set to one of these slugs.
                                ?>
                                <div class="item adf-card-item adf-card-item--<?php echo esc_attr($profile_layout); ?>">
                                    <?php
                                    adforest_load_search_card($profile_layout, array(
                                        'ad_permalink'           => $ad_permalink,
                                        'first_img'              => $first_img,
                                        'all_ad_images'          => $all_ad_images,
                                        'is_featured'            => $is_featured,
                                        'heart_class'            => $heart_class,
                                        'is_fav'                 => $is_fav,
                                        'fav_title'              => $fav_title,
                                        'fav_extra'              => $fav_extra,
                                        'truncated_title'        => $truncated_title,
                                        'ad_title'               => $ad_title,
                                        'truncated_location'     => $truncated_location,
                                        'price_html'             => $price_html,
                                        'ad_categories_post'     => $ad_categories_post,
                                        'ad_poster_img'          => $ad_poster_img,
                                        'ad_poster_name'         => $ad_poster_name,
                                        'ad_type'                => $ad_type,
                                        'ad_details'             => $ad_details,
                                        'top_bar_specific_style' => '',
                                        'featured_tag'           => $featured_tag,
                                    ));
                                    ?>
                                </div>
                                <?php
                            } else {
                                // Unknown layout slug — fall back to the
                                // modern card so the grid never renders empty.
                                ?>
                                <div class="item search_with_labels_grid ">
                                    <?php
                                    echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type, $ad_title, $price_html, $truncated_location);
                                    ?>
                                </div>
                                <?php
                            }
                            if (isset($adforest_theme['turn_on_grid_adverts_search']) && $adforest_theme['turn_on_grid_adverts_search'] == '1') {
                                if ($listing_counter == $ad_threshold && $total_ads > 0) {
                                    echo wp_kses_post($ads[$ad_index]);

                                    $ad_index++;
                                    if ($ad_index >= $total_ads) {
                                        $ad_index = 0;
                                    }

                                    $listing_counter = 0;
                                    $ad_threshold = isset($adforest_theme['show_ads_after_a_no_of_listings']) ? intval($adforest_theme['show_ads_after_a_no_of_listings']) : 0;
                                }
                            }
                            ?>
                        <?php endwhile; ?>
                    </div>

                    <!-- Custom Pagination -->
                    <nav aria-label="pagination">
                        <ul class="pagination adt-custom-pagination">
                            <?php
                            $total_pages = $ads_query->max_num_pages;
                            if ($total_pages > 1) {
                                if ($paged > 1) {
                                    echo '<li class="page-item"><a class="page-link prv" href="' . esc_url(get_pagenum_link($paged - 1)) . '"><i class="fas fa-chevron-left"></i></a></li>';
                                }

                                for ($i = 1; $i <= $total_pages; $i++) {
                                    $active_class = ($i == $paged) ? ' active' : '';
                                    echo '<li class="page-item"><a class="page-link' . $active_class . '" href="' . esc_url(get_pagenum_link($i)) . '">' . str_pad($i, 2, '0', STR_PAD_LEFT) . '</a></li>';
                                }

                                if ($paged < $total_pages) {
                                    echo '<li class="page-item"><a class="page-link nxt" href="' . esc_url(get_pagenum_link($paged + 1)) . '"><i class="fas fa-chevron-right"></i></a></li>';
                                }
                            }
                            ?>
                        </ul>
                    </nav>

                    <?php wp_reset_postdata(); ?>
                <?php } else {
                    $nothing_found = get_template_directory_uri() . '/images/nothing-found.png';
                    ?>
                    <div class="adt-ad-not-found">
                        <img src="<?php echo esc_url($nothing_found); ?>" alt="">
                        <h3><?php esc_html_e('No Ads found.', 'adforest'); ?></h3>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>