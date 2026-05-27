<?php
function adforest_get_full_category_name($term_id, $taxonomy = 'ad_cats') {
    $names = [];
    $term = get_term($term_id, $taxonomy);

    if (is_wp_error($term) || !$term) {
        return '';
    }

    while ($term && !is_wp_error($term)) {
        array_unshift($names, $term->name);
        if ($term->parent == 0) {
            break;
        }
        $term = get_term($term->parent, $taxonomy);
    }

    return implode(' > ', $names);
}

$current_id = get_current_user_id();
$ad_alerts = sb_get_ad_alerts($current_id);
$alerts_html = "";
$count = 0;


if (is_array($ad_alerts) && !empty($ad_alerts)) {
    foreach ($ad_alerts as $key => $val) {
        $count++;
        $alert_name = isset($val['alert_name']) ? $val['alert_name'] : "";
        $cat_id = isset($val['alert_category']) ? $val['alert_category'] : "";
        $terms = get_term_by('id', $cat_id, 'ad_cats');
        $term_name = adforest_get_full_category_name($cat_id);
        $alerts_html .= '<tr id="alert_detail_table_row_' . $key . '">
                            <td id="' . $key . '">' . $count . '</td>
                                <td>
                                    <a class="text-dark" href="">' . $alert_name . '</a>
                                </td>
                                <td class="d-none d-lg-table-cell">' . $term_name . '</td>
                                <td>
                                   <a href="javascript:void(0)"  data-value =  ' . $key . '  class="del_save_alert"> <span><i class="lni lni-trash-can"></i></span>
                            </td>
                         </tr>';
    }

}
?>

<?php echo adforest_dashboard_breadcrumb(esc_html__("Ad Alerts", "adforest")); ?>

<div class="row">
    <!-- Ads Table -->
    <div class="col-12">
        <div class="card-style mb-30">
            <table class="table card-table table-responsive table-responsive-large" style="width:100%">
                <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo esc_html__('Alert name', 'adforest'); ?></th>
                    <th class="d-none d-lg-table-cell"><?php echo esc_html__('Category', 'adforest'); ?></th>
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