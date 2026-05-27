<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Condition', 'adforest');
$expand = isset($_GET['condition']) ? 'show' : "";
$collapsed = 'collapsed';
if(isset($instance['open_widget']) && $instance['open_widget'] == '1') {
	$expand = 'show';
    $collapsed = '';
}
?>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button <?php echo esc_attr($collapsed); ?>" type="button" data-bs-toggle="collapse"
                    data-bs-target="#panelsStayOpen-collapseSix" aria-expanded="false"
                    aria-controls="panelsStayOpen-collapseSix">
                <?php echo esc_html($title); ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseSix" class="accordion-collapse collapse <?php echo esc_attr($expand); ?>">
            <div class="accordion-body">
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
                            <?php } ?>
                        </ul>
                    </div>
                    <?php echo adforest_search_params('condition'); ?>
                </form>
            </div>
        </div>
    </div>