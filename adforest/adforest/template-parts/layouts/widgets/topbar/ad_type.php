<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Ad Type', 'adforest');
$selected_type_id = "";
if (isset($_GET['ad_type'])) {
    $selected_type_id = $_GET['ad_type'];
}
?>
<div class="col-sm-6 col-md-4 col-lg-3">
    <?php
    global $wp;
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <form id="ad_type_form" method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="form-field">
            <label for="ad-type" class="form-label"><?php echo esc_html($title); ?></label>
            <select class="default-select" name="ad_type" id="topbar_ad_type">
                <option value=""><?php echo __("Select an Option", "adforest"); ?></option>
                <?php
                $ad_types = adforest_get_ad_taxonomy_callback('ad_type');
                $perm_name = (is_home() || is_front_page()) ? 'adtype' : 'ad_type';
                foreach ($ad_types as $type) {
                $ad_type_details = get_taxonomy_details($type);
                $type_name = $ad_type_details['name'];
                $type_id = $type->term_id;
                $selected = ($type_name == $selected_type_id) ? 'selected' : '';
                ?>
                <option value="<?php echo esc_attr($type_name); ?>" <?php echo esc_attr($selected); ?>>
                    <?php echo esc_html($type_name); ?>
                </option>
                <?php } ?>
            </select>
        </div>
        <?php echo adforest_search_params($perm_name); ?>
    </form>
    <?php adforest_widget_counter(); ?>
</div>
<?php adforest_advance_search_container(); ?>
