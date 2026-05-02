<?php
global $adforest_theme;
$adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
$adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
$adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
$ad_title = '';
if (isset($_GET['ad_title']) && $_GET['ad_title'] != "") {
    $ad_title = $_GET['ad_title'];
}
?>
<div class="col-sm-6 col-md-4 col-lg-3">
    <form method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="form-field">
            <label for="ad_title" class="form-label"><?php echo __("Search", 'adforest'); ?></label>
            <input type="text" class="form-control" id="ad_title" name="ad_title"
                   placeholder="<?php echo esc_attr__("Search", 'adforest'); ?>"
                   value="<?php echo esc_attr($ad_title); ?>"
            >
            <button type="submit" class="search-btn-title"><i class="fas fa-search"></i></button>
        </div>
        <?php
        echo adforest_search_params('ad_title');
        ?>
    </form>
    <?php
    adforest_widget_counter();
    ?>
</div>
<?php adforest_advance_search_container(); ?>