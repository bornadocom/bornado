<?php
global $adforest_theme, $template;
$sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
$sb_search_page = isset($sb_search_page) && $sb_search_page != '' ? get_the_permalink($sb_search_page) : 'javascript:void(0)';

$sb_search_page = apply_filters('adforest_category_widget_form_action', $sb_search_page);
$sb_cat_desc_title = isset($adforest_theme['sb_cat_desc_title']) ? $adforest_theme['sb_cat_desc_title'] : '';
$page_template = basename($template);
if ($page_template == 'taxonomy-ad_cats.php') {
    $term_id = get_queried_object_id();
}
$sidebar_position = isset($adforest_theme['cat_sidebar_position']) ? $adforest_theme['cat_sidebar_position'] : 'left';
$adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);

$loading_ads_mode = isset($adforest_theme['loading_ads_mode']) ? $adforest_theme['loading_ads_mode'] : 'pagination';

$cat_term_desc = term_description($term_id);

$ad_count = 0;
while ($results->have_posts()) {
    $results->the_post();
    $ad_count++;
}


$style_for_infinity_scroll = '';
if ($loading_ads_mode == 'infinity_scroll' && ($ad_count > get_option('posts_per_page'))) {
    $style_for_infinity_scroll = 'style = "height: 1000px; overflow: auto;"';
}
?>
<?php if ($search_cat_page) { ?>
    <!-- adt-ads-with-filters-start -->
    <section class="adt-ads-with-filters">
        <div class="container adt-container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-ads-filter-content-wrapper">
                        <?php if ($sidebar_position == 'left') { ?>
                            <div class="adt-ads-filter-sidebar">
                                <div class="accordion" id="accordionPanelsStayOpenExample">
                                    <?php dynamic_sidebar('adforest_cat_search'); ?>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="adt-ads-filter-content">
                            <div class="adt-ads-sort-box">
                                <h3>
                                    <?php echo esc_html($results->found_posts) . ' ' . esc_html__('Ad(s) Found:', 'adforest'); ?>
                                    <?php
                                    $param = $_SERVER['QUERY_STRING'];
                                    if ($param != "") {
                                        ?>
                                        <span><a class="filterAdType-count"
                                                 href="<?php echo get_the_permalink($adforest_search_page); ?>"><?php echo __('Reset Search', 'adforest'); ?></a></span>
                                    <?php } ?>
                                </h3>
                                <?php
                                $selectedOldest = $selectedLatest = $selectedTitleAsc = $selectedTitleDesc = $selectedPriceHigh = $selectedPriceLow = $selectedFeatured = '';
                                if (isset($_GET['sort'])) {
                                    $selectedOldest = ($_GET['sort'] == 'id-asc') ? 'selected' : '';
                                    $selectedLatest = ($_GET['sort'] == 'id-desc') ? 'selected' : '';
                                    $selectedTitleAsc = ($_GET['sort'] == 'title-asc') ? 'selected' : '';
                                    $selectedFeatured = ($_GET['sort'] == 'featured') ? 'selected' : '';
                                    $selectedTitleDesc = ($_GET['sort'] == 'title-desc') ? 'selected' : '';
                                    $selectedPriceHigh = ($_GET['sort'] == 'price-desc') ? 'selected' : '';
                                    $selectedPriceLow = ($_GET['sort'] == 'price-asc') ? 'selected' : '';
                                } elseif (isset($_GET['ad'])) {
                                    $selectedFeatured = ($_GET['ad'] == '1') ? 'selected' : '';
                                }
                                ?>
                                <div class="adt-sort-filters">
                                    <form id="sort-form" method="get">
                                        <select name="sort" class="default-select order_by" id="select-sort">
                                            <option value="id-desc" <?php echo esc_attr($selectedLatest); ?>>
                                                <?php echo esc_html__('Newest To Oldest', 'adforest'); ?>
                                            </option>
                                            <option value="id-asc" <?php echo esc_attr($selectedOldest); ?>>
                                                <?php echo esc_html__('Oldest To Newest', 'adforest'); ?>
                                            </option>
                                            <option value="featured" <?php echo esc_attr($selectedFeatured); ?>>
                                                <?php echo esc_html__('Featured', 'adforest'); ?>
                                            </option>
                                            <option value="price-desc" <?php echo esc_attr($selectedPriceHigh); ?>>
                                                <?php echo esc_html__('Price: High to Low', 'adforest'); ?>
                                            </option>
                                            <option value="price-asc" <?php echo esc_attr($selectedPriceLow); ?>>
                                                <?php echo esc_html__('Price: Low to High', 'adforest'); ?>
                                            </option>
                                        </select>
                                        <?php echo adforest_search_params('sort'); ?>
                                    </form>
                                    <div class="adt-change-view-box">
                                        <?php
                                        $grid_view = adforest_custom_remove_url_query('view-type', 'grid');
                                        $list_view = adforest_custom_remove_url_query('view-type', 'list');
                                        $grid_active = $list_active = '';
                                        if ((isset($_GET['view-type']) && $_GET['view-type'] == 'grid') || !isset($_GET['view-type'])) {
                                            $grid_active = 'active';
                                        } elseif (isset($_GET['view-type']) && $_GET['view-type'] == 'list') {
                                            $list_active = 'active';
                                        }
                                        if (isset($adforest_theme['search_layout_types']) && $adforest_theme['search_layout_types'] == true) {
                                            ?>
                                            <a href="<?php echo esc_url($grid_view); ?>"
                                               class="icon-box grid <?php echo esc_attr($grid_active); ?>">
                                                <i class="fas fa-th-large"></i>
                                            </a>
                                            <a href="<?php echo esc_url($list_view); ?>"
                                               class="icon-box list <?php echo esc_attr($list_active); ?>">
                                                <i class="fas fa-bars"></i>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <?php
                            if (isset($adforest_theme['sb_allow_cats_above_filters']) && $adforest_theme['sb_allow_cats_above_filters']) {
                                if (isset($term_id) && $term_id != "") {
                                    $cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : "";
                                    if (isset($cat_id) && $cat_id != "") {
                                        $cat_id = $_GET['cat_id'];
                                    } else {
                                        $cat_id = $term_id;
                                    }
                                    $ad_cats = adforest_get_cats('ad_cats', $cat_id);
                                    
                                    // Filter out sub-categories with zero ads if the option is enabled
                                    if (isset($adforest_theme['search_popup_cat_disable']) && $adforest_theme['search_popup_cat_disable'] == true) {
                                        $ad_cats = array_filter($ad_cats, function($category) {
                                            return isset($category->count) && $category->count > 0;
                                        });
                                    }
                                    
                                    $res = '';
                                    $rows_count = 1;
                                    $max_rows = $adforest_theme['sb_max_sub_cats'];
                                    $show = true;
                                    if (count($ad_cats) > 0) {
                                        parse_str($_SERVER['QUERY_STRING'], $search_params);
                                        unset($search_params['cat_id']);
                                        $new_params = http_build_query($search_params);
                                        $cat_params = '';
                                        $cls = '';
                                        $res .= '<ul class="city-select-city" >';
                                        foreach ($ad_cats as $ad_cat) {
                                            if ($new_params != "") {
                                                $cat_params = '?' . $new_params . '&cat_id=' . $ad_cat->term_id;
                                                $cat_link = get_the_permalink($adforest_search_page) . $cat_params;
                                            } else {
                                                $cat_params = '?cat_id=' . $ad_cat->term_id;
                                                $cat_link = get_the_permalink($adforest_search_page) . $cat_params;
                                            }

                                            $li_col = '3';
                                            if (isset($adforest_theme['sb_li_cols']) && $adforest_theme['sb_li_cols'] != "") {
                                                $li_col = $adforest_theme['sb_li_cols'];
                                            }

                                            $count = ($ad_cat->count);
                                            if ($rows_count > $max_rows && $show) {
                                                $show = false;
                                                $res .= '<li class="col-md-12 col-sm-12 col-xs-12 hide_cats text-center margin-top-20"><a href="javascript:void(0);"  class="tax-show-more">' . __('Show more', 'adforest') . '</a></li>';
                                                $cls = 'no-display show_it';
                                            }
                                            $res .= '<li class="col-md-' . esc_attr($li_col) . ' col-sm-6 col-xs-12 ' . esc_attr($cls) . '"><a href="' . $cat_link . '" >' . $ad_cat->name . ' <span>(' . $count . ')</span> </a></li>';
                                            $rows_count++;
                                        }
                                        $res .= '</ul>';
                                        ?>
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <div class="expand-collapse adforest-new-filter">
                                                <h3><a role="button" data-bs-toggle="collapse"
                                                       data-parent="#accordion" href="#collapseOnez"
                                                       aria-expanded="true" aria-controls="collapseOnez">
                                                        <i class="more-less fa"></i>
                                                        <?php
                                                        $title = adforest_get_taxonomy_parents($cat_id, 'ad_cats', false);
                                                        $find = '&raquo;';
                                                        $replace = '';
                                                        $result = preg_replace("/$find/", $replace, $title, 1);
                                                        echo adforest_return_echo($result);
                                                        ?> </a>
                                                </h3>
                                                <form>
                                                    <div id="collapseOnez" class="panel-collapse collapse in show"
                                                         role="tabpanel"
                                                         aria-labelledby="headingOnez">
                                                        <div class="panel-body">
                                                            <?php $display_cat_desc = isset($adforest_theme['display_cat_desc']) ? $adforest_theme['display_cat_desc'] : "";
                                                            if ($display_cat_desc && isset($term_id) && $term_id != '' && $cat_term_desc != '') {
                                                                echo '<div id="cat-full-text" style="display: none;">' . $cat_term_desc . ' <a href="javascript:void(0)" id="show_less">' . esc_html__('Read Less', 'adforest') . '</a></div>';
                                                                echo '<div id="cat-half-text">' . wp_trim_words($cat_term_desc, 15) . '<a href="javascript:void(0)" id ="show_more"> ' . esc_html__('Read More', 'adforest') . '</a></div>';
                                                            }
                                                            ?>
                                                            <div class="search-modal">
                                                                <div class="search-block"><?php echo adforest_return_echo($res); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                        <?php
                                    }
                                }
                            }
                            ?>
                            <div class="col-md-12 col-xs-12 col-sm-12 col-lg-12">
                                <?php get_template_part('template-parts/layouts/search/search', 'tags'); ?>
                            </div>
                            <?php if ( isset( $adforest_theme['featured_first'] ) && $adforest_theme['featured_first'] == '1' ) {
                                echo adforest_featured_grids_on_search();
                            } ?>
                            <?php
                            if (isset($adforest_theme['search_ad_720_1']) && $adforest_theme['search_ad_720_1'] != "" && $results->have_posts()) {
                                ?>

                                <div class="col-md-12">
                                    <div class="margin-bottom-30 margin-top-10 text-center">
                                        <?php echo "" . $adforest_theme['search_ad_720_1']; ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                            <?php echo adforest_render_ads_in_search($results, $style_for_infinity_scroll, $loading_ads_mode, $paged, $args, $ad_count); ?>
                        </div>
                        <?php if ($sidebar_position == 'right') { ?>
                            <div class="adt-ads-filter-sidebar">
                                <div class="accordion" id="accordionPanelsStayOpenExample">
                                    <?php dynamic_sidebar('adforest_cat_search'); ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- adt-ads-with-filters-end -->
<?php } ?>