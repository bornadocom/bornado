<?php get_header(); ?>
<?php adforest_custom_breadcrumbs(); ?>
<?php global $adforest_theme; ?>

    <section class="search-vendor-page">
        <div class="container">
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-xxl-10 col-xl-9 col-lg-9 col-md-12 col-sm-12 col-12 col-pm">
                    <div class="search-found-list">
                        <div class="row">
                            <?php
                            $countries_location = '';
                            $countries_location = apply_filters('adforest_site_location_ads', $countries_location, 'search');

                            if (get_query_var('paged')) {
                                $paged = get_query_var('paged');
                            } else if (get_query_var('page')) {
                                // This will occur if on front page.
                                $paged = get_query_var('page');
                            } else {
                                $paged = 1;
                            }
                            $category = array(
                                array(
                                    'taxonomy' => 'ad_tags',
                                    'field' => 'term_id',
                                    'terms' => get_queried_object_id(),
                                ),
                            );

                            $args = array(
                                'post_type' => 'ad_post',
                                'post_status' => 'publish',
                                'posts_per_page' => get_option('posts_per_page'),
                                'tax_query' => array(
                                    $category,
                                    $countries_location,
                                ),
                                'meta_query' => array(
                                    array(
                                        'key' => '_adforest_ad_status_',
                                        'value' => 'active',
                                        'compare' => '=',
                                    ),
                                ),
                                'orderby' => 'date',
                                'order' => 'DESC',
                                'fields' => 'ids',
                                'paged' => $paged,
                            );
                            $results = new WP_Query($args);
                            $col = 3;
                            if ($results->have_posts()) {
                                require trailingslashit(get_template_directory()) . "template-parts/layouts/ad-style/search-layout-list.php";
                            } else {
                                $nothing_found = get_template_directory_uri() . '/images/nothing-found.png';
                                echo '<div class="no_ads_found">
                                        <img src="' . esc_url($nothing_found) . '" alt="">
                                        <h3>' . __("No Ads found.", "adforest") . '</h3>
                                      </div>';
                            }
                            ?>
                        </div>

                        <div class="pagination-item">
                            <?php adforest_pagination_search($results); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php get_footer(); ?>