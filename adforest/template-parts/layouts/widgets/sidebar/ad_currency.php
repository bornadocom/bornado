<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Currency', 'adforest');
$cur_type = '';
$expand = "";
$perm_name = (is_home() || is_front_page()) ? 'ad_currency' : 'ad_currency';
if (isset($_GET["$perm_name"]) && $_GET["$perm_name"] != "") {
    $expand = "show";
    $cur_type = $_GET["$perm_name"];
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
                    data-bs-target="#panelsStayOpen-collapseEight" aria-expanded="false"
                    aria-controls="panelsStayOpen-collapseEight">
                <?php echo esc_html($title); ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseEight" class="accordion-collapse collapse <?php echo esc_attr($expand); ?>">
            <div class="accordion-body">
                <?php
                global $wp;
                $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
                $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
                $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
                ?>
                <form id="ad_currency_form" method="get"
                      action="<?php echo adforest_return_echo($adforest_search_page); ?>">
                    <div class="adt-switch-btns-box">
                        <ul class="adt-type-filter-box">
                            <?php
                            $ad_currencys = adforest_get_ad_taxonomy_callback('ad_currency');
                            $perm_name = (is_home() || is_front_page()) ? 'ad_currency' : 'ad_currency';
                            foreach ($ad_currencys as $currency) {
                                $ad_type_details = get_taxonomy_details($currency);
                                $currency_name = $ad_type_details['name'];
                                ?>
                                <li>
                                    <label class="container"><?php echo esc_html($currency_name); ?>
                                        <input tabindex="7" type="radio"
                                               class="submit-on-currency-change"
                                               id="minimal-radio-<?php echo esc_attr($currency->term_id); ?>"
                                               name="<?php echo esc_attr($perm_name); ?>"
                                               value="<?php echo esc_attr($currency->term_id); ?>">
                                        <span class="checkmark"></span>
                                    </label>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php echo adforest_search_params('ad_currency'); ?>
                </form>
            </div>
        </div>
    </div>