<?php echo adforest_dashboard_breadcrumb(esc_html__("Invoices", "adforest")); ?>

<?php
$args = array(
    'customer_id' => get_current_user_id(),
);
$history_html = "";
$orders = wc_get_orders($args);

if ( count( (array) $orders ) > 0 ) {
    foreach ( $orders as $order ) {
        $items = $order->get_items();
        $product_name = '';
        foreach ( $items as $item ) {
            $product_name .= $item->get_name() . ', ';
        }
        $product_name = rtrim( $product_name, ', ' );

        $history_html .= '<tr class="row-content">
            <td></td>
            <td>' . esc_html( $order->get_id() ) . '</td>
            <td>' . esc_html( $product_name ) . '</td>
            <td><span class="label label-default">' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span></td>
            <td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->get_date_created() ) ) ) . '</td>
            <td>' . wp_kses_post( class_exists( 'WooCommerce' ) && function_exists( 'wc_price' ) ? wc_price( $order->get_total() ) : $order->get_total() ) . '</td>
            <td>
                <button class="btn btn-sm btn-primary download-invoice-btn" data-order-id="' . esc_attr( $order->get_id() ) . '">
                    <i class="fa fa-download"></i> ' . esc_html__( 'View Invoice', 'adforest' ) . '
                </button>
            </td>
        </tr>';
    }
} else {
    $history_html .= '<tr><td colspan="7">' . esc_html__( 'There is no order history found.', 'adforest' ) . '</td></tr>';
}
?>

<div class="main">
    <main class="content my_ads">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12">
                    <div class="card-style mb-30">
                        <div class="table-responsive custom-tabel-label">
                            <table class="table top-selling-table">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th><?php echo esc_html__('Order #', 'adforest') ?></th>
                                    <th><?php echo esc_html__('Packages', 'adforest') ?></th>
                                    <th><?php echo esc_html__('Status', 'adforest') ?></th>
                                    <th><?php echo esc_html__('Date', 'adforest') ?></th>
                                    <th><?php echo esc_html__('Order total', 'adforest') ?></th>
                                    <th><?php echo esc_html__('Invoice', 'adforest') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php echo wp_kses_post($history_html); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Invoice Print View Modal -->
<div id="invoice-print-view" style="display: none;">
    <div id="invoice-content"></div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #invoice-print-view,
        #invoice-print-view * {
            visibility: visible;
        }
        #invoice-print-view {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
    }
    
    .invoice-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px;
        background: white;
        font-family: Arial, sans-serif;
    }
    
    .invoice-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #333;
        padding-bottom: 20px;
    }
    
    .invoice-header h1 {
        margin: 0;
        font-size: 32px;
        color: #333;
    }
    
    .invoice-details {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    
    .invoice-details div {
        flex: 1;
    }
    
    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    .invoice-table th,
    .invoice-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .invoice-table th {
        background-color: #f4f4f4;
        font-weight: bold;
    }
    
    .invoice-total {
        text-align: right;
        margin-top: 20px;
        font-size: 18px;
        font-weight: bold;
    }
    
    .print-button {
        text-align: center;
        margin: 30px 0;
    }
    
    .btn-print {
        background-color: #007bff;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin-right: 10px;
    }
    
    .btn-close {
        background-color: #6c757d;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }
    
    .btn-print:hover {
        background-color: #0056b3;
    }
    
    .btn-close:hover {
        background-color: #545b62;
    }
</style>

<script>
jQuery(document).ready(function($) {
    $('.download-invoice-btn').on('click', function() {
        var orderId = $(this).data('order-id');
        
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'get_invoice_html',
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce('get_invoice_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('.main').hide();
                    
                    $('#invoice-content').html(response.data.html);
                    $('#invoice-print-view').show();
                    
                    window.scrollTo(0, 0);
                } else {
                    alert('Error loading invoice');
                }
                
                $('.download-invoice-btn').prop('disabled', false).html('<i class="fa fa-download"></i> <?php echo esc_js(__('View Invoice', 'adforest')); ?>');
            },
            error: function() {
                alert('Error loading invoice');
                $('.download-invoice-btn').prop('disabled', false).html('<i class="fa fa-download"></i> <?php echo esc_js(__('View Invoice', 'adforest')); ?>');
            }
        });
    });
    
    $(document).on('click', '.btn-print', function() {
        window.print();
    });
    
    $(document).on('click', '.btn-close', function() {
        $('#invoice-print-view').hide();
        $('.main').show();
    });
});
</script>