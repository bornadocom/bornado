<?php get_header(); ?>
<?php
global $adforest_theme;
adforest_custom_breadcrumbs();
adforest_load_search_countries();
/* Only need on this page so inluded here don't want to increase page size for optimizaion by adding extra scripts in all the web */

$mapType = adforest_mapType();

if ($mapType == 'leafletjs_map') {
} else if ($mapType == 'google_map') {
    wp_enqueue_script('google-map-callback');
}
wp_enqueue_script('adforest-search');
wp_enqueue_style('datepicker', trailingslashit(get_template_directory_uri()) . 'css/datepicker.min.css');


/* For Near By Ads */
$allow_near_by = (isset($_GET['location']) && $_GET['location']) ? true : false;
$lat_lng_meta_query = array();
if ($allow_near_by) {
    $latlng = array();
    if ($mapType == 'leafletjs_map') {
        $map_lat = (isset($_GET['lat']) && $_GET['lat']) ? $_GET['lat'] : '';
        $map_long = (isset($_GET['long']) && $_GET['long']) ? $_GET['long'] : '';
        if ($map_lat != "" && $map_long != "") {
            $latlng = array("latitude" => $map_lat, "longitude" => $map_long);
        }
    } else if ($mapType == 'google_map') {
        $latlng = adforest_getLatLong($_GET['location']);
    }

    if (count($latlng) > 0) {
        $latitude = (isset($latlng['latitude'])) ? $latlng['latitude'] : '';
        $longitude = (isset($latlng['longitude'])) ? $latlng['longitude'] : '';
        $distance = (isset($_GET['rd'])) ? $_GET['rd'] : '20';
        $data_array = array("latitude" => $latitude, "longitude" => $longitude, "distance" => $distance);

        if ($latitude != "" && $longitude != "") {
            $type_lat = "'DECIMAL'";
            $type_lon = "'DECIMAL'";
            $lats_longs = adforest_determine_minMax_latLong($data_array, false);
            if (isset($lats_longs) && count($lats_longs) > 0) {
                //$lat_lng_meta_query['relation'] = 'AND';
                $lat_lng_meta_query[] = array(
                    'key' => '_adforest_ad_map_lat',
                    'value' => array($lats_longs['lat']['min'], $lats_longs['lat']['max']),
                    'compare' => 'BETWEEN',
                    'type' => 'DECIMAL',
                );

                $lat_lng_meta_query[] = array(
                    'key' => '_adforest_ad_map_long',
                    'value' => array($lats_longs['long']['min'], $lats_longs['long']['max']),
                    'compare' => 'BETWEEN',
                    'type' => 'DECIMAL',
                );

                add_filter('get_meta_sql', 'adforest_cast_decimal_precision');
                if (!function_exists('adforest_cast_decimal_precision')) {

                    function adforest_cast_decimal_precision($array)
                    {
                        $array['where'] = str_replace('DECIMAL', 'DECIMAL(10,3)', $array['where']);
                        return $array;
                    }
                }
            }
        }
    }
}


$meta = array(
    'key' => 'post_id',
    'value' => '0',
    'compare' => '!=',
);

// only active ads
$is_active = array(
    'key' => '_adforest_ad_status_',
    'value' => 'active',
    'compare' => '=',
);


$condition = '';
if (isset($_GET['condition']) && $_GET['condition'] != "") {
    $condition = array(
        'key' => '_adforest_ad_condition',
        'value' => $_GET['condition'],
        'compare' => '=',
    );
}

$ad_type = '';
if (isset($_GET['ad_type']) && $_GET['ad_type'] != "") {
    $ad_type = array(
        'key' => '_adforest_ad_type',
        'value' => $_GET['ad_type'],
        'compare' => '=',
    );
} else if (isset($_GET['adtype']) && $_GET['adtype'] != "") {
    $ad_type = array(
        'key' => '_adforest_ad_type',
        'value' => $_GET['adtype'],
        'compare' => '=',
    );
}

$warranty = '';
if (isset($_GET['warranty']) && $_GET['warranty'] != "") {
    $warranty = array(
        'key' => '_adforest_ad_warranty',
        'value' => $_GET['warranty'],
        'compare' => '=',
    );
}
$feature_or_simple = '';
if (isset($_GET['ad']) && $_GET['ad'] != "") {
    $feature_or_simple = array(
        'key' => '_adforest_is_feature',
        'value' => $_GET['ad'],
        'compare' => '=',
    );
}
$currency = '';
if (isset($_GET['c']) && $_GET['c'] != "") {
    $currency = array(
        'key' => '_adforest_ad_currency',
        'value' => $_GET['c'],
        'compare' => '=',
    );
}

$price = '';
if (isset($_GET['min_price']) && $_GET['min_price'] != "") {
    $price = array(
        'key' => '_adforest_ad_price',
        'value' => array($_GET['min_price'], $_GET['max_price']),
        'type' => 'numeric',
        'compare' => 'BETWEEN',
    );
}
$location = '';
if (isset($_GET['location']) && $_GET['location'] != "") {
    $location = array(
        'key' => '_adforest_ad_location',
        'value' => trim($_GET['location']),
        'compare' => 'LIKE',
    );
}

//Location
$countries_location = '';

$coun_id = isset($_GET['country_id']) ? $_GET['country_id'] : get_queried_object_id();
//if (isset($_GET['country_id']) && $_GET['country_id'] != "") {
$countries_location = array(
    array(
        'taxonomy' => 'ad_country',
        'field' => 'term_id',
        'terms' => $coun_id,
    ),
);
//}

$countries_location = apply_filters('adforest_site_location_ads', $countries_location, 'search');

$order = 'desc';
$orderBy = 'date';
if (isset($_GET['sort']) && $_GET['sort'] != "") {
    $orde_arr = explode('-', $_GET['sort']);
    $order = isset($orde_arr[1]) ? $orde_arr[1] : 'desc';
    if (isset($orde_arr[0]) && $orde_arr[0] == 'price') {
        $orderBy = 'meta_value_num';
    } else {
        $orderBy = isset($orde_arr[0]) ? $orde_arr[0] : 'date';
    }
}


$category = '';
if (isset($_GET['cat_id']) && $_GET['cat_id'] != "") {
    $category = array(
        array(
            'taxonomy' => 'ad_cats',
            'field' => 'term_id',
            'terms' => $_GET['cat_id'],
            'include_children' => 0,
        ),
    );
}

global $pass_term_id;
$pass_term_id = get_queried_object_id();


$title = '';
if (isset($_GET['ad_title']) && $_GET['ad_title'] != "") {
    $title = $_GET['ad_title'];
}

$custom_search = array();

if (isset($_GET['min_custom'])) {
    foreach ($_GET['min_custom'] as $key => $val) {
        $get_minVal = $val;
        $get_maxVal = (isset($_GET['max_custom']["$key"]) && $_GET['max_custom']["$key"] != "") ? $_GET['max_custom']["$key"] : '';
        if ($get_minVal != "" && $get_maxVal != "") {
            $metaKey = '_adforest_tpl_field_' . $key;

            if (adforest_validateDateFormat($get_minVal) && adforest_validateDateFormat($get_maxVal)) {
                $custom_search[] = array(
                    'key' => $metaKey,
                    'value' => array($get_minVal, $get_maxVal),
                    'compare' => 'BETWEEN',
                );
            } else {

                $custom_search[] = array(
                    'key' => $metaKey,
                    'value' => array($get_minVal, $get_maxVal),
                    'type' => 'numeric',
                    'compare' => 'BETWEEN',
                );
            }
        }
    }
}

if (isset($_GET['custom'])) {
    foreach ($_GET['custom'] as $key => $val) {
        if (is_array($val)) {
            $arr = array();
            $metaKey = '_adforest_tpl_field_' . $key;

            foreach ($val as $v) {

                $custom_search[] = array(
                    'key' => $metaKey,
                    'value' => $v,
                    'compare' => 'LIKE',
                );
            }
        } else {
            if (trim($val) == "0") {
                continue;
            }

            $val = stripslashes_deep($val);

            $metaKey = '_adforest_tpl_field_' . $key;
            $custom_search[] = array(
                'key' => $metaKey,
                'value' => $val,
                'compare' => 'LIKE',
            );
        }
    }
}

if (get_query_var('paged')) {
    $paged = get_query_var('paged');
} else if (get_query_var('page')) {
    // This will occur if on front page.
    $paged = get_query_var('page');
} else {
    $paged = 1;
}
$args = array(
    's' => $title,
    'post_type' => 'ad_post',
    'post_status' => 'publish',
    'posts_per_page' => get_option('posts_per_page'),
    'tax_query' => array(
        $category,
        $countries_location,
    ),
    'meta_key' => '_adforest_ad_price',
    'meta_query' => array(
        $is_active,
        $condition,
        $ad_type,
        $warranty,
        $feature_or_simple,
        $price,
        $currency,
        $location,
        $custom_search,
        $lat_lng_meta_query,
    ),
    'order' => $order,
    'orderby' => $orderBy,
    'paged' => $paged,
);
$args = apply_filters('adforest_wpml_show_all_posts', $args);
$results = new WP_Query($args);
$search_cat_page = isset($adforest_theme['search_location_page']) && $adforest_theme['search_location_page'] ? TRUE : FALSE;
$GLOBALS['widget_counter'] = 0;
require trailingslashit(get_template_directory()) . 'template-parts/layouts/search/location/search-sidebar.php';
?>

    <div class="panel panel-default my_panel">
        <div class="search-modal modal fade cats_model" id="cat_modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">

                        <h3 class="modal-title text-center" id="lineModalLabel"><i
                                    class="fa fa-cogs"></i> <?php echo __('Select Any Category', 'adforest'); ?>
                            <button type="button" class="close" data-bs-dismiss="modal"><span aria-hidden="true">&#10005;</span><span
                                        class="sr-only">Close</span></button>
                        </h3>
                    </div>
                    <div class="modal-body">
                        <div class="search-block">
                            <div class="row"></div>
                            <div class="row">
                                <div class="col-md-12 col-xs-12 col-sm-12 popular-search" id="cats_response"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="ad-search-btn"
                                class="btn btn-theme btn-lg btn-block"><?php echo __('Submit', 'adforest'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php get_footer(); ?>