<?php
global $adforest_theme;
$expand = '';
$title = !empty($instance['title']) ? $instance['title'] : __('Ad Type', 'adforest');
if (isset($_GET['ad_type'])) {
    $expand = 'show';
}

$collapsed = 'collapsed';
if(isset($instance['open_widget']) && $instance['open_widget'] == '1') {
	$expand = 'show';
    $collapsed = '';
}
?>
<div class="accordion-item">
    <h2 class="accordion-header">
        <button class="accordion-button <?php echo esc_attr($collapsed); ?>" type="button" data-bs-toggle="collapse"
                data-bs-target="#panelsStayOpen-collapseFour" aria-expanded="false"
                aria-controls="panelsStayOpen-collapseFour">
            <?php echo esc_html($title); ?>
        </button>
    </h2>
    <?php
    global $wp;
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <div id="panelsStayOpen-collapseFour" class="accordion-collapse collapse <?php echo esc_attr($expand) ?>">
        <div class="accordion-body">
            <form id="ad_type_form" method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
                <ul class="adt-type-filter-box">
                    <?php
                    $ad_types = adforest_get_ad_taxonomy_callback('ad_type');
                    $perm_name = (is_home() || is_front_page()) ? 'adtype' : 'ad_type';
                    foreach ($ad_types as $type) {
                        $ad_type_details = get_taxonomy_details($type);
                        $type_name = $ad_type_details['name'];
                        ?>
                        <li>
                            <label class="container"><?php echo esc_html($type_name); ?>
                                <input tabindex="7" type="radio"
                                       class="submit-on-change"
                                       id="minimal-radio-<?php echo esc_attr($type->term_id); ?>"
                                       name="<?php echo esc_attr($perm_name); ?>"
                                       value="<?php echo esc_attr($type_name); ?>">
                                <span class="checkmark"></span>
                            </label>
                        </li>
                    <?php } ?>
                </ul>
                <?php echo adforest_search_params($perm_name); ?>
            </form>
        </div>
    </div>
</div>