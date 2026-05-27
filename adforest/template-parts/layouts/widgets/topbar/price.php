<?php
global $adforest_theme;

$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : $instance['min_price'];
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : $instance['max_price'];
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
?>
<div class="col-sm-6 col-md-4 col-lg-3">
    <?php
    $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
    $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
    $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page);
    ?>
    <form class="" method="get"
          action="<?php echo adforest_return_echo($adforest_search_page); ?>">
        <div class="form-field">
            <span class="price-slider-value">
                <?php printf( esc_html__( 'Price (%s)', 'adforest' ), esc_html( $site_currency ) ); ?>
                <span id="min_price"><?php echo esc_html($min_price); ?></span> -
                <span id="max_price"><?php echo esc_html($max_price); ?></span>
            </span>
            <div class="range-slider">
                <input type="text" class="adt-ads-range-slider" name="" value=""/>
            </div>
            <div class="extra-controls">
                <input type="hidden" class="adt-ads-input-from form-control" name="min_price" id="min_selected"
                       value="<?php echo esc_attr($min_price); ?>">
                <div>&#9866;</div>
                <input type="hidden" class="adt-ads-input-to form-control" name="max_price" id="max_selected"
                       value="<?php echo esc_attr($max_price); ?>">
            </div>
            <input type="hidden" id="min_price" value="<?php echo esc_attr($instance['min_price']); ?>"/>
            <input type="hidden" id="max_price" value="<?php echo esc_attr($instance['max_price']); ?>"/>
            <button type="submit"
                    class="search-btn-title price_search_btn"><i class="fa fa-search"></i></button>
            <?php echo adforest_search_params('min_price', 'max_price', 'c'); ?>
        </div>
    </form>
    <?php adforest_widget_counter(); ?>
</div>
<?php adforest_advance_search_container(); ?>
