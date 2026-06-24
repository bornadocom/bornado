<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Categories', 'adforest');

// Align logic with map widget: use sub-categories when feature is enabled and a parent is selected
$search_show_sub_cats_with_parent = isset($adforest_theme['search_show_sub_cats_with_parent']) ? $adforest_theme['search_show_sub_cats_with_parent'] : false;
$current_cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

if ($search_show_sub_cats_with_parent && $current_cat_id > 0) {
    $ad_categories = adforest_get_cats('ad_cats', $current_cat_id);
} else {
    $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
}

$contextual_counts = function_exists('bornado_category_widget_get_contextual_counts')
    ? bornado_category_widget_get_contextual_counts($ad_categories)
    : array();

// Filter out categories with zero ads if the option is enabled
if (isset($adforest_theme['search_popup_cat_disable']) && $adforest_theme['search_popup_cat_disable'] == true) {
    $ad_categories = array_filter($ad_categories, function ($category) use ($contextual_counts) {
        $category_details = function_exists('bornado_category_widget_get_term_details')
            ? bornado_category_widget_get_term_details($category, $contextual_counts)
            : get_taxonomy_details($category);

        return isset($category_details['ad_count']) && (int) $category_details['ad_count'] > 0;
    });
}

$selected_cat_id = "";
if (isset($_GET['cat_id'])) {
    $selected_cat_id = $_GET['cat_id'];
}
?>

    <div class="col-sm-6 col-md-4 col-lg-3">
        <?php
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
        $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page, 'cat_page');
        ?>
        <form method="get" id="search_cats_w" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
            <div class="form-field">
                <label for="topbar_categories" class="form-label"><?php echo esc_html($title); ?></label>
                <select class="default-select" id="topbar_categories">
                    <option value=""><?php echo esc_html__('Select an Option', 'adforest'); ?></option>
                    <?php
                    if (is_array($ad_categories) && count($ad_categories) > 0) {
                        foreach ($ad_categories as $category) {
                            $category_details = function_exists('bornado_category_widget_get_term_details')
                                ? bornado_category_widget_get_term_details($category, $contextual_counts)
                                : get_taxonomy_details($category);
                            $name = $category_details['name'] ?? '';
                            $category_id = $category->term_id;
                            $selected = ($category_id == $selected_cat_id) ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr($category_id); ?>"
                                    data-cat-id="<?php echo esc_attr($category_id); ?>" <?php echo ($selected ? 'selected="selected"' : ''); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php }
                    } ?>
                </select>
            </div>
            <input type="hidden" name="cat_id" id="cat_id" value=""/>
            <?php echo adforest_search_params('cat_id'); ?>
            <?php apply_filters('adforest_form_lang_field', true); ?>
        </form>
        <?php adforest_widget_counter(); ?>
    </div>
<?php adforest_advance_search_container(); ?>
