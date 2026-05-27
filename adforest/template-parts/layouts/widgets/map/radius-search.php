<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Search Filter', 'adforest');
$adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
$adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
$adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
$location = '';

if (isset($_GET['location']) && $_GET['location'] != "") {
    $location = $_GET['location'];
}
if (isset($instance['open_widget']) && $instance['open_widget'] == '1') {
    $expand = 'show';
}
$default_radius = isset($instance['default_value']) ? $instance['default_value'] : "";

$read_only = "";
$read_only = isset($instance['edit_able']) ? $instance['edit_able'] : "yes";
if ($read_only == "no" && $default_radius != "") {
    $read_only = ' readonly="true"';

}

$show_hide = isset($instance['show_hide']) ? $instance['show_hide'] : "";
$display_style = '';
$btn_search = '<button class="search-btn-title"  type="submit" id="btn-submit"><i class="fa fa-search"></i></button>';
$btn_other = '<button class="crosshair_btn" type="button" id="you_current_location_text" data-place="text_field"><i class="fa fa-crosshairs"></i></button>';
$btn_search1 = '';
if ($show_hide == 'hide') {
    $display_style = 'hideSearchInput';
} else {
    $btn_search1 = '<button class="search-btn" type="submit" id="btn-submit"><i class="fa fa-search"></i></button>';
    $btn_search = '';
}

$radius = '';
$area = isset($_GET['location']) && $_GET['location'] != '' ? $_GET['location'] : '';

if (isset($_GET['location']) && $_GET['location'] != "" && isset($_GET['rd']) && $_GET['rd'] != "") {
    $radius = $_GET['rd'];
    $area = $_GET['location'];
} else {
    $radius = $default_radius;
}
$radius_placeholder = __('Radius in km', 'adforest');
$search_radius_type = isset($adforest_theme['search_radius_type']) ? $adforest_theme['search_radius_type'] : 'km';
if ($search_radius_type == 'mile') {
    $radius_placeholder = __('Radius in Miles', 'adforest');
}

?>
<div class="adt-search-list-box">
    <form id="sb-radius-form" class="for-radius">
        <div class="form-field">
            <?php
            $mapType = adforest_mapType();
            $attr_leaflet = "";
            $placeHolder = __('Type Location...', 'adforest');
            if ($mapType == 'leafletjs_map') {
                $map_lat = (isset($_GET['lat']) && $_GET['lat']) ? $_GET['lat'] : '';
                $map_long = (isset($_GET['long']) && $_GET['long']) ? $_GET['long'] : '';
                echo '
                    <input type="hidden" name="lat" id="sb_user_address_lat" value="' . esc_attr($map_lat) . '">
                    <input type="hidden" name="long" id="sb_user_address_long" value="' . esc_attr($map_long) . '">
                  ';

                $attr_leaflet = ' readonly="readonly"';
                $placeHolder = __('Get Location...', 'adforest');
            } else {
                $btn_other = '<button class="crosshair_btn" type="button" id="get-location" data-place="text_field"><i class="fa fa-crosshairs"></i></button>';
            }
            $location_input_id = ($mapType == 'leafletjs_map') ? 'sb_user_address_leaflet' : 'sb_user_address';
            ?>
            <label for="radius-search"
                   class="form-label"><?php echo esc_html__('Radius Search', 'adforest'); ?></label>
            <input type="text" class="form-control" id="<?php echo esc_attr($location_input_id); ?>" name="location"
                   placeholder="<?php echo esc_attr($placeHolder); ?>"
                   value="<?php echo esc_attr($location); ?>">
            <div>
                <?php
                echo wp_kses_post($btn_search);
                echo wp_kses_post($btn_other);
                ?>
            </div>
        </div>
        <div id="suggestions-box" class="suggestions-box"></div>
        <div class="form-field">
            <input type="number" class="form-control <?php echo esc_attr($display_style) ?>" id="rd" name="rd"
                   value="<?php echo esc_attr($radius); ?>"
                   placeholder="<?php echo esc_attr($radius_placeholder); ?>" data-parsley-required="true"
                   data-parsley-error-message="" <?php echo esc_attr($read_only); ?>>
            <?php echo wp_kses_post($btn_search1); ?>
        </div>
        <?php echo adforest_search_params('location', 'rd', 'country_id'); ?>
    </form>
</div>

<style>
    .suggestions-box {
        position: absolute;
        background: #fff;
        z-index: 9999;
        width: 250px;
        max-height: 200px;
        overflow-y: auto;
    }
    .suggestion-item {
        padding: 8px;
        cursor: pointer;
    }
    .suggestion-item:hover {
        background: #f0f0f0;
    }
</style>