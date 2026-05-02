<?php
$current_id = get_current_user_id();
$ad_alerts = sb_get_ad_alerts($current_id);
$alerts_html = "";
$count = 0;
$author_id = get_current_user_id();
$args = array(
    'author' => $author_id,
    'post_type' => 'ad_claims',
);
$author_posts = new WP_Query($args);
if ($author_posts->have_posts()) {
    while ($author_posts->have_posts()) {
        $author_posts->the_post();
        $claim_title = get_the_title();
        $count++;
        $post_id = get_the_ID();
        $status = get_post_meta($post_id, 'd_listing_claim_status', true);
        $class_btn = 'badge-danger';
        if ($status == 'pending') {
            $class_btn = 'badge-warning';
        } else if ($status == 'approved') {
            $class_btn = 'badge-success';
        } else if ($status == 'decline') {
            $class_btn = 'badge-danger';
        }
        $alerts_html .= '<td >' . $count . '</td>
    <td>
        <a class="text-dark" href="' . get_the_permalink($post_id) . '">' . $claim_title . '</a>
    </td>
    <td class="d-none d-lg-table-cell">' . get_the_date() . '</td>
    <td>
      <span class="badge ' . $class_btn . '">' . $status . '</span>
    </td>
</tr>';
    }
    wp_reset_postdata();
}
?>
<?php echo adforest_dashboard_breadcrumb(esc_html__("Claims", "adforest")); ?>

<div class="content-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-12">
                <!-- Recent Order Table -->
                <div class="card-style mb-30 recent-orders" id="recent-orders">
                    <div class="card-body pt-0 pb-5">
                        <table class="table card-table table-responsive table-responsive-large" style="width:100%">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo esc_html__('Alert name', 'adforest'); ?></th>
                                <th class="d-none d-lg-table-cell"><?php echo esc_html__('Claim date', 'adforest'); ?></th>
                                <th class="d-none d-lg-table-cell"><?php echo esc_html__('Action', 'adforest'); ?></th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php echo adforest_return_echo($alerts_html); ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- End Content -->
</div>