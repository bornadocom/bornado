<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Currency', 'adforest');
$cur_type = '';
$perm_name = (is_home() || is_front_page()) ? 'ad_currency' : 'ad_currency';
if (isset($_GET["$perm_name"]) && $_GET["$perm_name"] != "") {
    $cur_type = $_GET["$perm_name"];
}

$ad_currencys = adforest_get_ad_taxonomy_callback('ad_currency');
$perm_name = (is_home() || is_front_page()) ? 'ad_currency' : 'ad_currency';
?>
<h3><?php echo esc_html($title); ?></h3>
<div class="col-sm-6 col-md-4 col-lg-3">
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
                                   value="<?php echo esc_attr($currency_name); ?>">
                            <span class="checkmark"></span>
                        </label>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <?php echo adforest_search_params('ad_currency'); ?>
    </form>
    <?php adforest_widget_counter(); ?>
</div>
<?php adforest_advance_search_container(); ?>