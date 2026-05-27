<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Categories', 'adforest');

// Get current query parameters and sanitize them
$current_query = array_map('sanitize_text_field', $_GET);

$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : floatval($instance['min_price']);
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : floatval($instance['max_price']);
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

<div class="adt-range-slider range-slider-woocommerce-shop">
    <h3><?php echo esc_html($title); ?></h3>
    <form action="" method="get">
        <span class="price-slider-value">
            <?php printf( esc_html__( 'Price (%s)', 'adforest' ), esc_html( $site_currency ) ); ?>
            <span id="min_price"><?php echo esc_html($min_price); ?></span> -
            <span id="max_price"><?php echo esc_html($max_price); ?></span>
        </span>
        <div class="range-slider">
            <input type="text" class="adt-ads-range-slider"/>
        </div>
        <div class="extra-controls">
            <input type="text" class="adt-ads-input-from form-control" name="min_price" id="min_selected"
                   value="<?php echo esc_attr($min_price); ?>">
            <div>&#9866;</div>
            <input type="text" class="adt-ads-input-to form-control" name="max_price" id="max_selected"
                   value="<?php echo esc_attr($max_price); ?>">
        </div>
        <?php foreach ($current_query as $key => $value): ?>
            <?php if ($key != 'min_price' && $key != 'max_price'): ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <button class="adt-button-dark"><?php echo esc_html__("Search Now", 'adforest'); ?></button>
    </form>
</div>