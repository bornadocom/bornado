<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'adforest' ) ) );

    return;
}

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

    <form name="checkout" method="post" class="checkout woocommerce-checkout"
          action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
        <div class="row">
            <div class="col-xxl-9 col-xl-9 col-lg-8 col-md-12 col-sm-12 order-2 order-lg-1">
                <div class="adt-checkout-form adt-inner">
                    <h2 class="adt-form-heading"><?php esc_html_e( 'Billing & Shipping Address', 'adforest' ); ?></h2>
                    <div class="adt-form-inner">
                        <!-- Coupon Code Section -->
                        <?php if ( wc_coupons_enabled() ) : ?>
                            <div class="accordion accordion-flush adt-acc-coupon" id="adt-accordion">
                                <div class="accordion-item adt-acc-coupon-item">
                                    <h2 class="accordion-header adt-acc-header" id="flush-headingOne">
                                        <button class="accordion-button adt-acc-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#flush-collapseOne"
                                                aria-expanded="false" aria-controls="flush-collapseOne">
                                            <span><?php esc_html_e( 'Have A Coupon?', 'adforest' ); ?></span>
                                            <?php esc_html_e( 'Click here to enter your code', 'adforest' ); ?>
                                        </button>
                                    </h2>
                                    <div id="flush-collapseOne" class="accordion-collapse collapse"
                                         aria-labelledby="flush-headingOne" data-bs-parent="#adt-accordion">
                                        <div class="accordion-body adt-acc-inner">
                                            <div class="coupon-form">
                                                <span><?php esc_html_e( 'Coupon Code:', 'adforest' ); ?></span>
                                                <div class="coupon-input-wrapper">
                                                    <input type="text" name="coupon_code" class="input-text" id="coupon_code"
                                                           value="" placeholder="<?php esc_attr_e( 'Enter code', 'adforest' ); ?>"/>
                                                    <button type="button" class="button apply-coupon-btn" id="apply_coupon_btn"
                                                            data-loading-text="<?php esc_attr_e( 'Applying...', 'adforest' ); ?>">
                                                        <?php esc_html_e( 'Apply', 'adforest' ); ?>
                                                    </button>
                                                </div>
                                                <div class="coupon-messages" id="coupon-messages"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Billing Details -->
                    <div class="customer-info woocommerce-billing-fields">
                        <?php
                        $billing_fields = $checkout->get_checkout_fields( 'billing' );

                        if ( $billing_fields ) : ?>
                            <div class="row">
                                <p class="required-note"><?php esc_html_e( '* indicates required fields', 'adforest' ); ?></p>
                                <?php foreach ( $billing_fields as $key => $field ) :
                                    if ($key === 'billing_country') {
                                        $field['class'][] = 'country_to_state';
                                    }
                                    if ($key === 'billing_state') {
                                        $field['class'][] = 'state_select';
                                    }

                                    if (empty($field['placeholder'])) {
                                        $field['placeholder'] = $field['label'];
                                    }
                                    if (!empty($field['required'])) {
                                        $field['placeholder'] .= ' *';
                                    }
                                    $field['label'] = '';
                                    ?>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-12 col-sm-12">
                                        <div class="adt-customer">
                                            <?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Additional Fields -->
                        <div class="adt-customer-textarea">
                            <?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>
                            <?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>
                                <textarea name="order_comments" class="input-text"
                                          placeholder="<?php esc_attr_e( 'Order Notes (optional)', 'adforest' ); ?>"
                                          rows="2" cols="5"></textarea>
                            <?php endif; ?>
                            <?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
                        </div>
                    </div>

                    <div class="adt-payment-options">
                        <?php woocommerce_checkout_payment(); ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Summary -->
            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-12 col-sm-12 order-1 order-lg-2">
                <div class="pro-list-sec-right adt-inner">
                    <div class="order-summary" id="order-summary">
                        <span><?php esc_html_e( 'Order Summary', 'adforest' ); ?></span>
                        <div class="order-summary-list" id="order-summary-list">
                            <div class="adt-list">
                                <span class="headings"><?php esc_html_e( 'Subtotal', 'adforest' ); ?></span>
                                <span class="values"><?php echo wc_price( WC()->cart->get_subtotal() ); ?></span>
                            </div>

                            <!-- Display Applied Coupons -->
                            <?php if ( ! empty( WC()->cart->get_applied_coupons() ) ) : ?>
                                <?php foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) : ?>
                                    <?php $coupon = new WC_Coupon( $coupon_code ); ?>
                                    <div class="adt-list coupon-discount">
                                        <span class="headings">
                                            <?php echo esc_html( sprintf( __( 'Coupon: %s', 'adforest' ), $coupon_code ) ); ?>
                                            <button type="button" class="remove-coupon" data-coupon="<?php echo esc_attr( $coupon_code ); ?>" title="<?php esc_attr_e( 'Remove coupon', 'adforest' ); ?>">×</button>
                                        </span>
                                        <span class="values">-<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $coupon_code ) ); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="adt-list">
                                <span class="headings"><?php esc_html_e( 'Delivery', 'adforest' ); ?></span>
                                <span class="values">
                                    <?php
                                    $shipping_total = WC()->cart->get_shipping_total();
                                    if ( $shipping_total > 0 ) {
                                        echo wp_kses_post( wc_price( $shipping_total ) );
                                    } else {
                                        echo esc_html__( 'Free', 'adforest' );
                                    }
                                    ?>
                                </span>
                            </div>

                            <?php if ( WC()->customer->get_shipping_country() ) : ?>
                                <div class="adt-list">
                                    <span class="headings"><?php esc_html_e( 'Shipping to', 'adforest' ); ?> <?php echo WC()->customer->get_shipping_state(); ?>.</span>
                                    <span class="values"><i class="fas fa-paper-plane"
                                                            aria-hidden="true"></i> <?php echo WC()->customer->get_shipping_address(); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="adt-list total-row">
                                <span class="headings"><?php esc_html_e( 'Total', 'adforest' ); ?></span>
                                <span class="values">
                                    <?php echo wc_price( WC()->cart->get_total( 'edit' ) ); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="adt-continue">
                    <span><i class="fa fa-arrow-left" aria-hidden="true"></i></span>
                    <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                        <?php esc_html_e( 'Continue Shopping', 'adforest' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#apply_coupon_btn').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                var $input = $('#coupon_code');
                var couponCode = $input.val().trim();
                var $messages = $('#coupon-messages');

                if (!couponCode) {
                    $messages.html('<div class="woocommerce-error"><?php esc_html_e( "Please enter a coupon code.", "adforest" ); ?></div>');
                    return;
                }
                $button.prop('disabled', true).text($button.data('loading-text'));
                $messages.empty();
                $.ajax({
                    type: 'POST',
                    url: wc_checkout_params.ajax_url,
                    data: {
                        action: 'woocommerce_apply_coupon',
                        coupon_code: couponCode,
                        security: wc_checkout_params.apply_coupon_nonce
                    },
                    success: function(response) {
                        if (typeof response === 'object') {
                            if (response.result === 'success') {
                                $messages.html('<div class="woocommerce-message">' + (response.message || '<?php esc_html_e( "Coupon applied successfully!", "adforest" ); ?>') + '</div>');
                                $input.val('');

                                $('body').trigger('update_checkout');

                                updateOrderSummary();
                            } else {
                                $messages.html('<div class="woocommerce-error">' + (response.message || '<?php esc_html_e( "Invalid coupon code.", "adforest" ); ?>') + '</div>');
                            }
                        } else if (typeof response === 'string') {
                            var $responseHtml = $(response);
                            console.log(response)
                            if ($responseHtml.hasClass('woocommerce-message') || $responseHtml.find('.woocommerce-message').length > 0) {
                                $messages.html(response);
                                $input.val('');

                                $('body').trigger('update_checkout');

                                updateOrderSummary();

                            } else {
                                $messages.html(response);
                            }
                        } else {
                            $messages.html('<div class="woocommerce-error"><?php esc_html_e( "Invalid response format.", "adforest" ); ?></div>');
                        }
                    },
                    error: function() {
                        $messages.html('<div class="woocommerce-error"><?php esc_html_e( "Something went wrong. Please try again.", "adforest" ); ?></div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Apply", "adforest" ); ?>');
                    }
                });
            });

            $(document).on('click', '.remove-coupon', function(e) {
                e.preventDefault();
                $("#sb_loading").show();

                var $button = $(this);
                var couponCode = $button.data('coupon');

                $.ajax({
                    type: 'POST',
                    url: wc_checkout_params.ajax_url,
                    data: {
                        action: 'woocommerce_remove_coupon',
                        coupon: couponCode,
                        security: wc_checkout_params.remove_coupon_nonce
                    },
                    success: function(response) {
                        $("#sb_loading").hide();
                        if (typeof response === 'object') {
                            if (response.result === 'success') {
                                $('body').trigger('update_checkout');

                                updateOrderSummary();
                            }
                        } else if (typeof response === 'string') {
                            var $responseHtml = $(response);
                            if (!$responseHtml.hasClass('woocommerce-error') && !$responseHtml.find('.woocommerce-error').length) {
                                $('body').trigger('update_checkout');

                                updateOrderSummary();
                            }
                        }
                    }
                });
            });

            $('#coupon_code').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#apply_coupon_btn').click();
                }
            });

            function updateOrderSummary() {
                window.location.reload()
            }
        });
    </script>

    <style>
        .coupon-form {
            width: 100%;
        }

        .coupon-input-wrapper {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .coupon-input-wrapper input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .apply-coupon-btn {
            padding: 8px 16px;
            background-color: #0073aa;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }

        .apply-coupon-btn:hover {
            background-color: #005a87;
        }

        .apply-coupon-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .coupon-messages {
            margin-top: 10px;
        }

        .coupon-messages .woocommerce-error,
        .coupon-messages .woocommerce-message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .coupon-messages .woocommerce-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .coupon-messages .woocommerce-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .coupon-discount {
            color: #155724;
        }

        .remove-coupon {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            margin-left: 5px;
            padding: 0;
            line-height: 1;
        }

        .remove-coupon:hover {
            color: #c82333;
        }

        .total-row {
            border-top: 2px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
        }
        .woocommerce-error::before, .woocommerce-message::before {
            position: fixed;
        }
    </style>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>