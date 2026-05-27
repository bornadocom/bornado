<?php
global $adforest_theme;

// Get the min and max price from URL parameters or instance settings
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : $instance['min_price'];
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : $instance['max_price'];
$expand = isset($_GET['min_price']) || isset($_GET['max_price']) ? 'show' : '';

$title = !empty($instance['title']) ? $instance['title'] : __('Price', 'adforest');
$site_currency = $adforest_theme['sb_currency'] ?? get_woocommerce_currency_symbol();

wp_localize_script(
    'adforest-custom',
    'price_widget',
    [
        'min_price' => $instance['min_price'],
        'max_price' => $instance['max_price']
    ]
);

$collapsed = 'collapsed';
if(isset($instance['open_widget']) && $instance['open_widget'] == '1') {
	$expand = 'show';
    $collapsed = '';
}
?>
<div class="accordion-item">
    <h2 class="accordion-header">
        <button class="accordion-button <?php echo esc_attr($collapsed); ?>" type="button" data-bs-toggle="collapse"
                data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false"
                aria-controls="panelsStayOpen-collapseThree">
            <?php echo esc_html($title); ?>
        </button>
    </h2>
    <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse <?php echo esc_attr($expand); ?>">
        <div class="accordion-body adt-range-slider">
            <?php
            $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
            $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
            $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
            ?>
            <form method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>"
                  data-adforest-default-min="<?php echo esc_attr($instance['min_price']); ?>"
                  data-adforest-default-max="<?php echo esc_attr($instance['max_price']); ?>">
                <span class="price-slider-value">
                    <?php printf( esc_html__( 'Price (%s)', 'adforest' ), esc_html( $site_currency ) ); ?>
                    <span id="min_price"><?php echo esc_html($min_price); ?></span> -
                    <span id="max_price"><?php echo esc_html($max_price); ?></span>
                </span>
                <div class="range-slider">
                    <input type="text" class="adt-ads-range-slider" name="" value=""/>
                </div>
                <div class="extra-controls">
                    <input type="text" class="adt-ads-input-from form-control" name="min_price" id="min_selected"
                           data-adforest-default="<?php echo esc_attr($instance['min_price']); ?>"
                           value="<?php echo esc_attr($min_price); ?>">
                    <div>&#9866;</div>
                    <input type="text" class="adt-ads-input-to form-control" name="max_price" id="max_selected"
                           data-adforest-default="<?php echo esc_attr($instance['max_price']); ?>"
                           value="<?php echo esc_attr($max_price); ?>">
                </div>
                <input type="hidden" id="min_price" value="<?php echo esc_attr($instance['min_price']); ?>"/>
                <input type="hidden" id="max_price" value="<?php echo esc_attr($instance['max_price']); ?>"/>
                <button type="submit" class="adt-button-dark"><?php echo __("Search Now", 'adforest'); ?></button>
                <?php echo adforest_search_params('min_price', 'max_price', 'c'); ?>
            </form>
        </div>
    </div>
</div>
