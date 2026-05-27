<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Condition', 'adforest');
?>

<h3><?php echo esc_html($title); ?></h3>
<div>
    <?php
    global $wp;
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <form id="ad_condition_form" method="get"
          action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="adt-switch-btns-box">
            <ul class="select-user-type">
                <?php
                $ad_condition = adforest_get_ad_taxonomy_callback('ad_condition');
                if (is_array($ad_condition) && count($ad_condition) > 0) {
                    foreach ($ad_condition as $type) {
                        $ad_condition_details = get_taxonomy_details($type);
                        $name = $ad_condition_details['name'];
                        $slug = $ad_condition_details['slug'];
                        ?>
                        <li>
                            <input type="radio" name="condition" class="submit-on-condition-change"
                                   id="check-condition-<?php echo esc_attr($slug); ?>"
                                   value="<?php echo esc_attr($type->name); ?>"/>
                            <label for="check-condition-<?php echo esc_attr($slug); ?>">
                                <?php echo esc_html($name); ?>
                            </label>
                        </li>
                    <?php }
                } ?>
            </ul>
        </div>
        <?php echo adforest_search_params('condition'); ?>
    </form>
</div>