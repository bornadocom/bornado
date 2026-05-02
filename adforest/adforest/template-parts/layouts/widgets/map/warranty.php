<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Warranty', 'adforest');
?>

<h3><?php echo esc_html($title); ?></h3>

<?php
global $wp;
$adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
$adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
$adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
?>
<form id="ad_warranty_form" method="get"
      action="<?php echo adforest_return_echo($adforest_search_page); ?>">
    <div class="adt-switch-btns-box">
        <ul class="select-user-type">
            <?php
            $ad_warranty = adforest_get_ad_taxonomy_callback('ad_warranty');
            if (is_array($ad_warranty) && count($ad_warranty) > 0) {
                foreach ($ad_warranty as $type) {
                    $ad_warranty_details = get_taxonomy_details($type);
                    $name = $ad_warranty_details['name'];
                    $slug = $ad_warranty_details['slug'];
                    ?>
                    <li>
                        <input type="radio" name="warranty" class="submit-on-warranty-change"
                               id="check-warranty-<?php echo esc_attr($slug); ?>"
                               value="<?php echo esc_attr($type->name); ?>"/>
                        <label for="check-warranty-<?php echo esc_attr($slug); ?>">
                            <?php echo esc_html($name); ?>
                        </label>
                    </li>
                <?php }
            } ?>
        </ul>
    </div>
    <?php echo adforest_search_params('warranty'); ?>
</form>
