<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="adt-checkout-section">
	<div class="container">
		<?php if ( $order ) : ?>
			<div class="row">
				<div class="col-lg-12">
					<div class="adt-checkout-form adt-inner">
						<h2 class="adt-form-heading"><?php esc_html_e( 'Thanks For your Order', 'adforest' ); ?></h2>
						<div class="adt-thanks">
							<span><?php esc_html_e( 'Your order was completed successfully', 'adforest' ); ?></span>
						</div>
						<div class="adt-order">
							<div class="adt-order-no">
								<span class="adt-order-no-heading"><?php esc_html_e( 'Order Number:', 'adforest' ); ?></span>
								<span class="adt-order-no-values"><?php echo esc_html( $order->get_order_number() ); ?></span>
							</div>
							<div class="adt-order-no">
								<span class="adt-order-no-heading"><?php esc_html_e( 'Date:', 'adforest' ); ?></span>
								<span class="adt-order-no-values"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
							</div>
							<?php if ( $order->get_billing_email() ) : ?>
								<div class="adt-order-no">
									<span class="adt-order-no-heading"><?php esc_html_e( 'Email Address:', 'adforest' ); ?></span>
									<span class="adt-order-no-values"><?php echo esc_html( $order->get_billing_email() ); ?></span>
								</div>
							<?php endif; ?>
							<div class="adt-order-no">
								<span class="adt-order-no-heading"><?php esc_html_e( 'Total:', 'adforest' ); ?></span>
								<span class="adt-order-no-values"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
							</div>
							<?php if ( $order->get_payment_method_title() ) : ?>
								<div class="adt-order-no">
									<span class="adt-order-no-heading"><?php esc_html_e( 'Payment Method:', 'adforest' ); ?></span>
									<span class="adt-order-no-values"><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<h2 class="adt-form-heading"><?php esc_html_e( 'Order Details', 'adforest' ); ?></h2>
						<div class="table-responsive adt-scroll-margin">
							<table class="t-order">
								<thead>
								<tr>
									<th><?php esc_html_e( 'Product', 'adforest' ); ?></th>
									<th><?php esc_html_e( 'Total', 'adforest' ); ?></th>
								</tr>
								</thead>
								<tbody>
								<?php foreach ( $order->get_items() as $item_id => $item ) :
									$product = $item->get_product();
									?>
									<tr class="o-border">
										<td class="adt-order-heading">
											<?php
											echo esc_html( $item->get_name() );
											if ( $item->get_quantity() > 1 ) {
												echo ' x' . esc_html( $item->get_quantity() );
											}
											?>
										</td>
										<td class="adt-order-heading">
											<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
										</td>
									</tr>
								<?php endforeach; ?>

								<?php if ( $order->get_shipping_method() ) : ?>
									<tr class="o-border">
										<td class="adt-order-heading"><?php esc_html_e( 'Shipping', 'adforest' ); ?></td>
										<td class="adt-order-heading"><?php echo wp_kses_post( $order->get_shipping_method() ); ?></td>
									</tr>
								<?php endif; ?>

								<?php if ( $order->get_total_tax() > 0 ) : ?>
									<tr class="o-border">
										<td class="adt-order-heading"><?php esc_html_e( 'Tax', 'adforest' ); ?></td>
										<td class="adt-order-heading"><?php echo wc_price( $order->get_total_tax() ); ?></td>
									</tr>
								<?php endif; ?>

								<tr class="o-border">
									<td class="adt-order-heading"><?php esc_html_e( 'Total', 'adforest' ); ?></td>
									<td class="adt-order-heading"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
								</tr>
								</tbody>
							</table>
						</div>

						<h2 class="adt-form-heading"><?php esc_html_e( 'Billing Address', 'adforest' ); ?></h2>
						<div class="table-responsive">
							<table class="t-bill">
								<tbody>
								<?php
								$billing = $order->get_address( 'billing' );
								if ( ! empty( $billing ) ) :
									if ( ! empty( $billing['first_name'] ) || ! empty( $billing['last_name'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading adt-bill-col1-width"><?php esc_html_e( 'Name', 'adforest' ); ?></td>
											<td class="adt-bill-heading adt-bill-col2-width">
												<?php echo esc_html( trim( $billing['first_name'] . ' ' . $billing['last_name'] ) ); ?>
											</td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['address_1'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'Address Line 01', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['address_1'] ); ?></td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['address_2'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'Address Line 02', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['address_2'] ); ?></td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['city'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'City', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['city'] ); ?></td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['country'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'Country', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['country'] ); ?></td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['state'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'State/Province', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['state'] ); ?></td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['postcode'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'Zip/Postal Code', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['postcode'] ); ?></td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['phone'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'Phone Number', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['phone'] ); ?></td>
										</tr>
									<?php endif;
									if ( ! empty( $billing['email'] ) ) : ?>
										<tr class="tborder">
											<td class="adt-bill-heading"><?php esc_html_e( 'Email Address', 'adforest' ); ?></td>
											<td class="adt-bill-heading"><?php echo esc_html( $billing['email'] ); ?></td>
										</tr>
									<?php endif;
								endif;
								?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="row">
				<div class="col-lg-12">
					<div class="adt-checkout-form adt-inner">
						<h2 class="adt-form-heading"><?php esc_html_e( 'Order Received', 'adforest' ); ?></h2>
						<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>