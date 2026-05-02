<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Condition', 'adforest');
$selected_condition = isset($_GET['condition']) ? $_GET['condition'] : "";
?>
<div class="col-sm-6 col-md-4 col-lg-3">
    <?php
    global $wp;
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <form id="ad_condition_form" method="get"
          action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="form-field">
            <label for="ad-condition-topbar" class="form-label"><?php echo esc_html($title); ?></label>
            <select class="default-select" id="ad-condition-topbar" name="condition">
                <option value=""><?php echo __("Select an Option", "adforest"); ?></option>
                <?php
                $ad_condition = adforest_get_ad_taxonomy_callback('ad_condition');
                foreach ($ad_condition as $type) {
                    $ad_condition_details = get_taxonomy_details($type);
                    $name = $ad_condition_details['name'];
                    $slug = $ad_condition_details['slug'];
                    $selected = ($name == $selected_condition) ? 'selected' : '';
                    ?>
                    <option value="<?php echo esc_attr($name); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($name); ?></option>
                <?php } ?>
            </select>
        </div>
        <?php echo adforest_search_params('condition'); ?>
    </form>
    <?php adforest_widget_counter(); ?>
</div>
<?php adforest_advance_search_container(); ?>