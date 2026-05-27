<?php
$flip_it = '';
$custom_field_dispaly = 'style=display:none;';
if (is_rtl()) {
    $flip_it = 'flip';
}
wp_enqueue_script("google-map-callback");

adforest_load_search_countries(1);
//pagination	
global $adforest_theme;
if (get_query_var('paged')) {
    $paged = get_query_var('paged');
} else if (get_query_var('page')) {
    $paged = get_query_var('page');
} else {
    $paged = 1;
}
//Listing Title
$event_title = '';
if (isset($_GET['by_title']) && $_GET['by_title'] != "") {
    $event_title = $_GET['by_title'];
}
//Categories
$cat_id = isset($_GET['event_cat']) && $_GET['event_cat'] != "" ? $_GET['event_cat'] : "";
if (is_tax('l_event_cat')) {
    $cat_id = isset($_GET['event_cat']) && $_GET['event_cat'] != "" ? $_GET['event_cat'] : get_queried_object_id();
}
$category = '';
if ($cat_id != "") {
    $category = array(
        array(
            'taxonomy' => 'l_event_cat',
            'field' => 'term_id',
            'terms' => $cat_id,
        ),
    );
}
$location_id = isset($_GET['event_loc']) && $_GET['event_loc'] != "" ? $_GET['event_loc'] : "";
if (is_tax('event_loc')) {
    $location_id = isset($_GET['event_loc']) && $_GET['event_loc'] != "" ? $_GET['event_loc'] : get_queried_object_id();
}
$location = '';
if ($location_id != "") {
    $location = array(
        array(
            'taxonomy' => 'event_loc',
            'field' => 'term_id',
            'terms' => $location_id,
        ),
    );
}
$sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_pro_event_page']);
//Listing Street Address
$venue = '';
if (isset($_GET['by_location']) && $_GET['by_location'] != "") {
    $venue = array(
        'key' => 'sb_pro_event_venue',
        'value' => trim($_GET['by_location']),
        'compare' => 'LIKE',
    );
}
//only active events
$active_events = array(
    'key' => 'sb_pro_event_status',
    'value' => '1',
    'compare' => '='
);
$order = 'desc';
$orderBy = 'date';
if (isset($_GET['sort_by']) && $_GET['sort_by'] != "") {
    $orde_arr = explode('-', $_GET['sort_by']);
    $order = isset($orde_arr[1]) ? $orde_arr[1] : 'desc';
    {
        $orderBy = isset($orde_arr[0]) ? $orde_arr[0] : 'ID';
    }
}
//query 
$args = array
(
    's' => $event_title,
    'post_type' => 'events',
    'post_status' => 'publish',
    'posts_per_page' => get_option('posts_per_page'),
    'meta_query' => array(
        $active_events,
        $venue,
    ),
    'tax_query' => array(
        $category,
        $location
    ),
    'order' => $order,
    'orderby' => $orderBy,
    'paged' => $paged,
);
$results = new WP_Query($args);

?>
<div class="black-bakground"></div>
<section class="search-bar-content event-form-container">
    <div class="container">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="main-search-bar">
                    <div class="row">
                        <form class="eventzform" id="d_events_filters" method="post">
                            <div class="row">
                                <div class="col-md-12 col-xs-12 col-sm-12">
                                    <div class="list-box-shadow">
                                        <div class="row">
                                            <?php
                                            $layout = isset($adforest_theme['events-filter-manager']['enabled']) ? $adforest_theme['events-filter-manager']['enabled'] : array();
                                            if ($layout):

                                                $count = 0;
                                                foreach ($layout as $key => $value) {

                                                    if ($count == 4) {
                                                        echo '<div class="col-md-12 col-xs-12 col-sm-12"><div class="hide-it"><a href="javascript:void(0);" class="adv-srch">' . esc_html__('Advance Search', 'sb_pro') . '</a></div></di>';
                                                        echo '</div><div class="hide_adv_search"><div class="row">';
                                                    }
                                                    switch ($key) {
                                                        case 'by_title':
                                                            wc_get_template2('event-search/search-filters/by-title.php');

                                                            break;
                                                        case 'by_category':
                                                            wc_get_template2('event-search/search-filters/by-category.php');

                                                            break;
                                                        case 'by_location':
                                                            wc_get_template2('event-search/search-filters/by-location.php');
                                                            break;
                                                        case 'by_date':
                                                            wc_get_template2('event-search/search-filters/by-date.php');

                                                            break;
                                                        case 'by_custom_location':
                                                            wc_get_template2('event-search/search-filters/by-locationcustom.php');

                                                            break;
                                                        case 'by_radius':
                                                            wc_get_template2('event-search/search-filters/by-slider.php');
                                                            break;
                                                    }
                                                    $count++;
                                                }

                                                if ($count == 4 || $count > 4) {
                                                    echo '</div></div>';;
                                                }
                                            endif;
                                            ?>

                                            <div class="col-sm-12 col-md-12 col-xs-12 additional-fields-search" <?php echo esc_attr($custom_field_dispaly) ?>>
                                                <div class="card-body">
                                                    <label id="toggle-additional-fields" style="cursor: pointer;">
                                                        <?php echo esc_html__('Additional fields', 'dwt-listing'); ?>
                                                        <span class="toggle-icon">&#9660;</span>
                                                    </label>
                                                    <div class="additional-fields-search-container"
                                                         id="additional-fields-container" style="display: none;">
                                                    </div>
                                                    <div style="margin: 0 15px 0 15px;" class="col-lg-6 col-md-6 col-sm-12 col-12 additional-fields-search">
                                                        <span class="form-control-clear glyphicon glyphicon-remove form-control-feedback hidden"></span>
                                                        <span class="input-group-btn">
                                                            <button id="additional_fields_filters"
                                                                    class="btn btn-event-search" type="button">
                                                                <span class="fa fa-search"></span>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-xs-12 text-right">
                                    <button type="submit" class="btn btn-event-search">
                                        <span class="fa fa-search"></span> Search Events
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="match-adforest-topbar event">
    <div class="container">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="found-adforest">
                    <div class="found-adforest-heading">
                        <h5><span
                                    id="event-count"><?php echo esc_html($results->found_posts); ?></span><?php echo "   " . __('Found Events', 'sb_pro') . ":"; ?>
                            <span><a class="filterAdType-count"
                                     href="<?php echo get_the_permalink($sb_search_page); ?>"><?php echo __('Reset Search', 'sb_pro'); ?></a></span>
                    </div>
                    <div class="found-adforest-sorting">
                        <ul class="found-sort-item">
                            <li>
                                <?php
                                $selectedOldest = $selectedLatest = $selectedTitleAsc = $selectedTitleDesc = $selectedPriceHigh = $selectedPriceLow = '';
                                if (isset($_GET['sort'])) {
                                    $selectedOldest = ($_GET['sort'] == 'id-asc') ? 'selected' : '';
                                    $selectedLatest = ($_GET['sort'] == 'id-desc') ? 'selected' : '';
                                    $selectedTitleAsc = ($_GET['sort'] == 'title-asc') ? 'selected' : '';
                                    $selectedTitleDesc = ($_GET['sort'] == 'title-desc') ? 'selected' : '';
                                    $selectedPriceHigh = ($_GET['sort'] == 'price-desc') ? 'selected' : '';
                                    $selectedPriceLow = ($_GET['sort'] == 'price-asc') ? 'selected' : '';
                                }
                                ?>
                                <form method="get">
                                    <select name="sort_by" class="default-select event_orer_by order_by">
                                        <option value="id-desc" <?php echo esc_attr($selectedLatest); ?>>
                                            <?php echo esc_html__('Newest To Oldest', 'sb_pro'); ?></option>
                                        <option value="id-asc" <?php echo esc_attr($selectedOldest); ?>>
                                            <?php echo esc_html__('Oldest To Newest', 'sb_pro'); ?></option>
                                    </select>
                                    <?php echo adforest_search_params('sort'); ?>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<input type="hidden" name="layout_type" value="">


</form>
<?php
$fetch_output = "";
$grid_type = isset($adforest_theme['event_grid_type']) ? $adforest_theme['event_grid_type'] : "3";
$event_grid_col = isset($adforest_theme['event_grid_col']) ? $adforest_theme['event_grid_col'] : '3';
while ($results->have_posts()) {
    $results->the_post();
    $event_id = get_the_ID();

    $function = "get_event_grid_type_$grid_type";
    $fetch_output .= $function($event_id, $event_grid_col);
}
wp_reset_postdata();
?>
<div class="event-search-content event-grids">
    <div class="container">
        <div class="row fd ad-event-grid-section" id="event-content">
            <?php echo $fetch_output; ?>
        </div>

        <div class="row event-pagination">
            <div class="col-12">
                <?php adforest_pagination_search($results); ?>
            </div>
        </div>
        <input type="hidden" id="current-page" value accept="1">

    </div>
</div>