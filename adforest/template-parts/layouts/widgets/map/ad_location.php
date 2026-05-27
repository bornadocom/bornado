<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Ad Location', 'adforest');

$location = '';
if (isset($_GET['location']) && $_GET['location'] != "") {
    $location = $_GET['location'];
}
?>

<h3><?php echo esc_html($title); ?></h3>
<div>
    <?php
    global $wp;
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <form method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="adt-search-list-box">
            <div class="form-field">
                <input type="text" class="form-control" id="sb_user_address_loc" name="location"
                       placeholder="<?php echo esc_attr__("Search", 'adforest'); ?>"
                       value="<?php echo esc_attr($location); ?>">
                <button type="submit" class="search-btn-title"><i class="fas fa-search"></i>
                </button>
                <?php echo adforest_search_params('location'); ?>
            </div>
        </div>
    </form>
    <?php adforest_load_search_countries(); ?>
</div>