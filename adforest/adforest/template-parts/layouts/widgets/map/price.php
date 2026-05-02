<?php
global $adforest_theme;

// Get the min and max price from URL parameters or instance settings
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : $instance['min_price'];
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : $instance['max_price'];
$expand = isset($_GET['min_price']) || isset($_GET['max_price']) ? 'show' : '';

$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Price', 'adforest');
$site_currency = isset($adforest_theme['sb_currency']) && !empty($adforest_theme['sb_currency']) ? $adforest_theme['sb_currency'] : get_woocommerce_currency_symbol();

wp_localize_script(
    'adforest-custom',
    'price_widget',
    [
        'min_price' => $instance['min_price'],
        'max_price' => $instance['max_price']
    ]
);

?>

<h3><?php echo esc_html($title); ?></h3>
<div class="margin-bottom-30">
    <?php
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <form method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="adt-range-slider">
        <span class="price-slider-value">
            <?php echo sprintf( esc_html__('Price (%s)', 'adforest'), esc_html($site_currency) ); ?>
            <span id="min_price"><?php echo esc_html($min_price); ?></span> -
            <span id="max_price"><?php echo esc_html($max_price); ?></span>
        </span>
            <div class="range-slider">
                <input type="text" class="adt-ads-range-slider" name="" value=""/>
            </div>
            <div class="extra-controls">
                <input type="text" class="adt-ads-input-from form-control" name="min_price" id="min_selected"
                       value="<?php echo esc_attr($min_price); ?>">
                <div>&#9866;</div>
                <input type="text" class="adt-ads-input-to form-control" name="max_price" id="max_selected"
                       value="<?php echo esc_attr($max_price); ?>">
            </div>
            <input type="hidden" id="min_price" value="<?php echo esc_attr($instance['min_price']); ?>"/>
            <input type="hidden" id="max_price" value="<?php echo esc_attr($instance['max_price']); ?>"/>
            <button type="submit" class="adt-button-dark"><?php echo esc_html__("Search Now", 'adforest'); ?></button>
            <?php echo adforest_search_params('min_price', 'max_price', 'c'); ?>
        </div>
    </form>
</div>