<?php
global $adforest_theme;
$vertical_ad = isset($adforest_theme['adforest_user_page_ad_vertical']) ? $adforest_theme['adforest_user_page_ad_vertical'] : "";
$horizontal_ad = isset($adforest_theme['adforest_user_page_ad_horizontal']) ? $adforest_theme['adforest_user_page_ad_horizontal'] : "";

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$users_per_page = get_option("posts_per_page");

$title = isset($_GET['title']) ? sanitize_text_field($_GET['title']) : '';
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : '';
$sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'new_to_old';

$args = [
    'number' => $users_per_page,
    'paged' => $paged,
    'meta_query' => [],
    'orderby' => 'registered',
    'order' => ($sort === 'old_to_new') ? 'ASC' : 'DESC'
];

if (!empty($title)) {
    $args['search'] = '*' . esc_attr($title) . '*';
    $args['search_columns'] = ['display_name', 'user_email'];
}

if (!empty($rating)) {
    $args['meta_query'][] = [
        'key' => '_adforest_rating_avg',
        'value' => $rating,
        'compare' => '>=',
        'type' => 'NUMERIC'
    ];
}

$user_query = new WP_User_Query($args);

$total_users = $user_query->get_total();
$total_pages = ceil($total_users / $users_per_page);
?>

<section class="adt-seller-search-section">
    <div class="container adt-container">
        <div class="row">
            <div class="col-lg-3">
                <div class="adt-ads-filter-sidebar">
                    <?php require trailingslashit(get_template_directory()) . 'template-parts/layouts/seller-search/search-form.php'; ?>
                    <?php if($vertical_ad) { ?>
                        <div class="adt-vertical-ad-box" style="margin-top: 35px;">
                            <?php echo wp_kses_post($vertical_ad); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="adt-ads-sort-box">
                    <h3 class="sellers_found_heading"><?php echo esc_html($total_users) . __(" Sellers Found", "adforest"); ?>
                        :</h3>
                    <select class="default-select" id="sort_sellers">
                        <option value="new_to_old" <?php selected($sort, 'new_to_old'); ?>>
                            <?php echo esc_html__("Newest To Oldest", "adforest"); ?>
                        </option>
                        <option value="old_to_new" <?php selected($sort, 'old_to_new'); ?>>
                            <?php echo esc_html__("Oldest To Newest", "adforest"); ?>
                        </option>
                    </select>
                </div>
                <?php if($horizontal_ad) { ?>
                    <div class="adt-horizontal-ad-box">
                        <?php
                        if ( function_exists( 'adforest_render_theme_ad' ) ) {
                            adforest_render_theme_ad( 'adforest_user_page_ad_horizontal' );
                        } else {
                            echo wp_kses_post( $horizontal_ad );
                        }
                        ?>
                    </div>
                <?php } ?>
                <?php if (!empty($user_query->get_results())) { ?>
                    <div class="adt-seller-cards-grid">
                        <?php
                        foreach ($user_query->get_results() as $user) {
                            $author_id = $user->ID;
                            $user_pic = adforest_get_user_dp($author_id, 'adforest-user-profile');
                            $contact_num = get_user_meta($author_id, '_sb_contact', true);
                            $address = get_user_meta($author_id, '_sb_address', true);
                            $author_intro = get_user_meta($author_id, '_sb_user_intro', true);
                            $user_role = '';
                            if (get_user_meta($author_id, '_sb_user_type', true) == 'Indiviual') {
                                $user_role = __('Individual', 'adforest');
                            } else if (get_user_meta($author_id, '_sb_user_type', true) == 'Dealer') {
                                $user_role = __('Dealer', 'adforest');
                            }
                            ?>
                            <div class="adt-seller-card">
                                <div class="top-content">
                                    <img src="<?php echo esc_url($user_pic); ?>" alt="seller-img">
                                    <h4><?php echo esc_html($user->display_name); ?></h4>
                                    <?php if ($user_role != "") { ?>
                                        <span><?php echo ucfirst($user_role); ?></span>
                                    <?php } ?>
                                </div>
                                <div class="location">
                                    <i class="fas fa-map-marker-alt"></i><?php echo esc_html($address); ?>
                                </div>
                                <div class="bottom-content">
                                    <div class="rating">
                                        <span><?php printf(esc_html__('%s Ads', 'adforest'), esc_html(count_user_posts($author_id, 'ad_post'))); ?></span>
                                        <?php
                                        $got = get_user_meta($author_id, "_adforest_rating_avg", true);
                                        $total = 0;
                                        if ($got == "")
                                            $got = 0;
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= round($got))
                                                echo '<i class="fa fa-star"></i>';
                                            else
                                                echo '<i class="far fa-star"></i>';

                                            $total++;
                                        }
                                        ?>
                                    </div>
                                    <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"
                                       class="seller-prf-btn"><?php echo esc_html__("View Profile", "adforest"); ?></a>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                } else {
                    $nothing_found = get_template_directory_uri() . '/images/nothing-found.png';
                    ?>
                    <div class="adt-ad-not-found">
                        <img src="<?php echo esc_url($nothing_found); ?>" alt="">
                        <h3><?php _e('No Sellers found.', 'adforest'); ?></h3>
                    </div>
                <?php }
                ?>

                <!-- Pagination -->
                <nav aria-label="Page navigation example">
                    <ul class="pagination adt-custom-pagination">
                        <?php
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
            </div>
        </div>
    </div>
</section>