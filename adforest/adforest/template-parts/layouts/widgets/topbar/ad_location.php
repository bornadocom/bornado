<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Ad Location', 'adforest');
$expand = isset($_GET['location']) ? 'show' : "";

$location = '';
if (isset($_GET['location']) && $_GET['location'] != "") {
    $location = $_GET['location'];
}
?>

<div class="col-sm-6 col-md-4 col-lg-3">
    <?php
    global $wp;
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <form method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="form-field">
            <label for="sb_user_address" class="form-label"><?php echo esc_html($title); ?></label>
            <input type="text" class="form-control" id="sb_user_address_loc" name="location"
                   placeholder="<?php echo esc_attr__("Search", 'adforest'); ?>"
                   value="<?php echo esc_attr($location); ?>"
            >
            <button type="submit" class="search-btn-title"><i class="fas fa-search"></i></button>
        </div>
        <?php
        echo adforest_search_params('location');
        ?>
    </form>
    <?php adforest_load_search_countries(); ?>
    <?php adforest_widget_counter(); ?>
</div>
<?php adforest_advance_search_container(); ?>