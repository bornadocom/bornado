<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Countries', 'adforest');
$ad_countries = adforest_get_ad_taxonomy_callback('ad_country');

// Filter out countries with zero ads if the option is enabled
if (isset($adforest_theme['search_popup_loc_disable']) && $adforest_theme['search_popup_loc_disable'] == true) {
    $ad_countries = array_filter($ad_countries, function($country) {
        $country_details = get_taxonomy_details($country);
        return isset($country_details['ad_count']) && $country_details['ad_count'] > 0;
    });
}

$selected_country_id = "";
$expand = '';
if (isset($_GET['country_id'])) {
    $selected_country_id = $_GET['country_id'];
    $expand = 'show';
}
?>

<h3><?php echo esc_html($title); ?></h3>
<div class="map_search_countries">
    <?php
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page, 'cat_page');
    ?>

    <form method="get" id="search_countries"
          action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="form-field">
            <select class="default-select" id="topbar_countries">
                <option value=""><?php echo __('Select an Option', 'adforest'); ?></option>
                <?php
                if (is_array($ad_countries) && count($ad_countries) > 0) {
                    foreach ($ad_countries as $country) {
                        $country_details = get_taxonomy_details($country);
                        $name = $country_details['name'];
                        $country_id = $country->term_id;
                        $selected = ($country_id == $selected_country_id) ? 'selected' : '';
                        ?>
                        <option value="<?php echo esc_attr($country_id); ?>"
                                data-country-id="<?php echo esc_attr($country_id); ?>" <?php echo ($selected ? 'selected="selected"' : ''); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php }
                } ?>
            </select>
        </div>
        <input type="hidden" name="country_id" id="country_id" value=""/>
        <?php echo adforest_search_params('country_id'); ?>
        <?php apply_filters('adforest_form_lang_field', true); ?>
    </form>
</div>