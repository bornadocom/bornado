<?php
/**
 * Cart Page Template Override
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
global $adforest_theme;
$adt_container_class = "";
if ( isset( $adforest_theme['sb_header'] ) && ( $adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar" ) ) {
    $adt_container_class = "adt-container";
}

$page_id = get_queried_object_id();

$page_header_style = get_post_meta($page_id, '_page_header_style', true);

if(isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2")) {
    $adt_container_class = "adt-container";
}
?>

    <section class="adt-cart-section">
        <div class="container <?php echo esc_attr($adt_container_class); ?>">
            <div class="row">
                <!-- Product List Column -->
                <div class="col-xxl-9 col-xl-9 col-lg-8 col-md-12 col-sm-12 order-2 order-lg-1">
                    <div class="pro-list-sec-left adt-inner">
                        <h2 class="pro-list-heading"><?php esc_html_e('Product List', 'adforest'); ?></h2>

                        <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                            <div class="pro-list">
                                <?php do_action('woocommerce_before_cart_contents'); ?>

                                <?php
                                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                                        ?>
                                        <div class="pro-list-item">
                                            <div class="pro-list-left">
                                                <!-- Product Image -->
                                                <?php
                                                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                                                if (!$product_permalink) {
                                                    echo wp_kses_post($thumbnail);
                                                } else {
                                                    printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                                                }
                                                ?>

                                                <div class="pro-desc-left">
                                                    <!-- Product Title -->
                                                    <p class="title">
                                                        <?php
                                                        if (!$product_permalink) {
                                                            echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key));
                                                        } else {
                                                            echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
                                                        }
                                                        ?>
                                                    </p>

                                                    <div class="price-sec">
                                                        <!-- Product Price -->
                                                        <p class="price">
                                                            <?php
                                                            echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
                                                            ?>
                                                        </p>
                                                        <?php if ($_product->get_sku()): ?>
                                                            <p class="pk">(<?php echo esc_html($_product->get_sku()); ?>)</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="pro-list-right">
                                                <!-- Custom Quantity Controls -->
                                                <div class="adt-counter">
                                                    <button type="button" class="qty-decrease"><i class="fas fa-angle-left"></i></button>
                                                    <span class="adt-count"><?php echo str_pad($cart_item['quantity'], 2, '0', STR_PAD_LEFT); ?></span>
                                                    <button type="button" class="qty-increase"><i class="fas fa-angle-right"></i></button>
                                                    <!-- Hidden WooCommerce quantity input -->
                                                    <input
                                                            type="number"
                                                            class="qty"
                                                            name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]"
                                                            value="<?php echo esc_attr($cart_item['quantity']); ?>"
                                                            min="0"
                                                            max="<?php echo esc_attr($_product->get_max_purchase_quantity()); ?>"
                                                            style="display: none;"
                                                    />
                                                </div>

                                                <!-- Remove Button with hidden WooCommerce remove link -->
                                                <?php
                                                $remove_url = wc_get_cart_remove_url($cart_item_key);
                                                printf(
                                                    '<a href="%s" class="remove-item-link" style="display: none;" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                                                    esc_url($remove_url),
                                                    esc_attr(sprintf(esc_html__('Remove %s from cart', 'adforest'), wp_strip_all_tags($_product->get_name()))),
                                                    esc_attr($product_id),
                                                    esc_attr($_product->get_sku())
                                                );
                                                ?>
                                                <button type="button" class="adt-remove"><?php echo esc_html__("Remove", "adforest") ?></button>
                                            </div>
                                        </div>
                                        <div class="adt-line"></div>
                                        <?php
                                    }
                                }
                                ?>

                                <?php do_action('woocommerce_cart_contents'); ?>
                            </div>

                            <button type="submit" class="button" name="update_cart" value="<?php esc_attr_e('Update cart', 'adforest'); ?>" style="display: none;"><?php esc_html_e('Update cart', 'adforest'); ?></button>

                            <?php do_action('woocommerce_cart_actions'); ?>
                            <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                        </form>
                    </div>
                </div>

                <!-- Order Summary Column -->
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-12 col-sm-12 order-1 order-lg-2">
                    <div class="pro-list-sec-right adt-inner">
                        <div class="order-summary">
                            <span><?php esc_html_e( 'Order Summary', 'adforest' ); ?></span>

                            <?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

                            <div class="order-summary-list">
                                <div class="adt-list">
                                    <span class="headings"><?php esc_html_e( 'Subtotal', 'adforest' ); ?></span>
                                    <span class="values"><?php wc_cart_totals_subtotal_html(); ?></span>
                                </div>

                                <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
                                    <div class="adt-list">
                                        <span class="headings"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
                                        <span class="values"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
                                    </div>
                                <?php endforeach; ?>

                                <div class="adt-list">
                                    <span class="headings"><?php esc_html_e( 'Delivery', 'adforest' ); ?></span>
                                    <span class="values">
                                    <?php echo WC()->cart->get_shipping_total() > 0 ? wc_price( WC()->cart->get_shipping_total() ) : esc_html__( 'Free', 'adforest' ); ?>
                                </span>
                                </div>

                                <div class="adt-list">
                                    <span class="headings"><?php esc_html_e( 'Total', 'adforest' ); ?></span>
                                    <span class="values"><?php wc_cart_totals_order_total_html(); ?></span>
                                </div>
                            </div>

                            <?php if ( wc_coupons_enabled() ) : ?>
                                <!-- Coupon Form - Fixed Structure -->
                                <form class="woocommerce-cart-form woocommerce-coupon-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                                    <div class="accordion accordion-flush acc-coupon" id="adt-accordion">
                                        <div class="accordion-item acc-coupon-item">
                                            <h2 class="accordion-header acc-header" id="flush-headingOne">
                                                <button class="accordion-button acc-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#flush-collapseOne"
                                                        aria-expanded="true" aria-controls="flush-collapseOne">
                                                    <?php esc_html_e( 'Coupon Code:', 'adforest' ); ?>
                                                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                                                </button>
                                            </h2>
                                            <div id="flush-collapseOne" class="accordion-collapse collapse show"
                                                 aria-labelledby="flush-headingOne" data-bs-parent="#adt-accordion">
                                                <div class="accordion-body acc-inner">
                                                    <input type="text" name="coupon_code" id="coupon_code"
                                                           placeholder="<?php esc_attr_e( 'Enter code', 'adforest' ); ?>" value="" />
                                                    <button type="submit" name="apply_coupon" class="button adt-button-dark-1"
                                                            value="<?php esc_attr_e( 'Apply coupon', 'adforest' ); ?>">
                                                        <?php esc_html_e( 'Apply', 'adforest' ); ?>
                                                    </button>
                                                    <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
                                <button class="checkout">
                                    <?php esc_html_e( 'Proceed To Checkout', 'adforest' ); ?>
                                </button>
                            </a>
                        </div>
                    </div>

                    <div class="adt-continue">
                        <span><i class="fa fa-arrow-left" aria-hidden="true"></i></span>
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Continue Shopping', 'adforest' ); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        jQuery(document).ready(function($) {
            $('.woocommerce-remove-coupon').on('click', function(e) {
                e.preventDefault();

                var couponCode = $(this).data('coupon');
                var removeUrl = $(this).attr('href');

                $(this).html('<i class="fa fa-spinner fa-spin"></i>');

                window.location.href = removeUrl;
            });
        });
    </script>

<?php do_action( 'woocommerce_after_cart' ); ?>