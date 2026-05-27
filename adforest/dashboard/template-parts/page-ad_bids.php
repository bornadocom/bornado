<?php echo adforest_dashboard_breadcrumb(esc_html__("Ad Bids", "adforest")); ?>

<?php
if (!is_user_logged_in()) {
    echo '<div class="alert alert-warning">' . esc_html__("Please login to view your ad bids.", "adforest") . '</div>';
    return;
}

$current_user_id = get_current_user_id();

update_user_meta($current_user_id, 'ad_bids_last_seen_time', current_time('mysql'));

// Pagination
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Collect all of the current user's ad IDs
$args_ads = array(
    'post_type'      => 'ad_post',
    'author'         => $current_user_id,
    'posts_per_page' => -1,
    'post_status'    => array('publish', 'pending', 'draft', 'private'),
    'fields'         => 'ids',
);
$user_ad_ids = get_posts($args_ads);

$all_bids = array();

if (is_array($user_ad_ids) && count($user_ad_ids) > 0) {
    foreach ($user_ad_ids as $ad_id) {
        // Use the theme helper that reads bids from postmeta
        if (function_exists('adforest_get_all_biddings_array')) {
            $bids_map = adforest_get_all_biddings_array($ad_id);
        } else {
            $bids_map = array();
        }

        // $bids_map key format: bidderId_date_comment => price
        if (is_array($bids_map) && count($bids_map) > 0) {
            foreach ($bids_map as $key => $price) {
                $parts = explode('_', $key, 3);
                $bidder_id = isset($parts[0]) ? intval($parts[0]) : 0;
                $bid_date = isset($parts[1]) ? $parts[1] : '';
                $bid_comment = isset($parts[2]) ? $parts[2] : '';

                $all_bids[] = array(
                    'ad_id'       => $ad_id,
                    'bidder_id'   => $bidder_id,
                    'date'        => $bid_date,
                    'comment'     => $bid_comment,
                    'price_raw'   => $price,
                );
            }
        }
    }
}

// Sort bids by newest date (string date format stored in meta)
usort($all_bids, function ($a, $b) {
    $ta = strtotime($a['date']);
    $tb = strtotime($b['date']);
    if ($ta === $tb) {
        return 0;
    }
    return ($ta > $tb) ? -1 : 1; // descending
});

$total_bids = count($all_bids);
$paged_bids = array_slice($all_bids, $offset, $per_page);
$total_pages = ($per_page > 0) ? ceil($total_bids / $per_page) : 1;

?>
<div class="card-style">
    <div class="table-wrapper table-responsive">
        <table class="table ad-bids-table">
            <thead>
                <tr>
                    <th class="col-ad"><?php echo esc_html__("Ad", "adforest"); ?></th>
                    <th class="col-bidder"><?php echo esc_html__("Bidder", "adforest"); ?></th>
                    <th class="col-date"><?php echo esc_html__("Date", "adforest"); ?></th>
                    <th class="col-price text-right"><?php echo esc_html__("Bid Amount", "adforest"); ?></th>
                    <th class="col-action text-right"><?php echo esc_html__("Action", "adforest"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($paged_bids) === 0) { ?>
                    <tr>
                        <td colspan="5">
                            <div class="alert alert-info mb-0"><?php echo esc_html__("No bids found on your ads.", "adforest"); ?></div>
                        </td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($paged_bids as $bid) {
                        $ad_title = get_the_title($bid['ad_id']);
                        $ad_link = get_permalink($bid['ad_id']);
                        $bidder = get_userdata($bid['bidder_id']);
                        $bidder_name = $bidder && isset($bidder->display_name) ? $bidder->display_name : esc_html__("Unknown", "adforest");
                        $bidder_profile = adforest_set_url_param(get_author_posts_url($bid['bidder_id']), 'type', 'ads');
                        // Bid Amount with currency formatting (mirrors theme price settings)
                        $amount_raw = is_numeric($bid['price_raw']) ? (float)$bid['price_raw'] : 0;
                        $thousands_sep = isset($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator'] !== '' ? $adforest_theme['sb_price_separator'] : ',';
                        $decimals = isset($adforest_theme['sb_price_decimals']) && $adforest_theme['sb_price_decimals'] !== '' ? (int)$adforest_theme['sb_price_decimals'] : 0;
                        $decimals_separator = isset($adforest_theme['sb_price_decimals_separator']) && $adforest_theme['sb_price_decimals_separator'] !== '' ? $adforest_theme['sb_price_decimals_separator'] : '.';
                        $currency = isset($adforest_theme['sb_currency']) ? $adforest_theme['sb_currency'] : '';
                        if (get_post_meta($bid['ad_id'], '_adforest_ad_currency', true) != '') {
                            $currency = get_post_meta($bid['ad_id'], '_adforest_ad_currency', true);
                        }
                        $amount_str = number_format((float)$amount_raw, $decimals, $decimals_separator, $thousands_sep);
                        $direction = isset($adforest_theme['sb_price_direction']) ? $adforest_theme['sb_price_direction'] : 'left';
                        if ($direction === 'right') {
                            $price_display = $amount_str . '<small>' . $currency . '</small>';
                        } else if ($direction === 'right_with_space') {
                            $price_display = $amount_str . ' <small>' . $currency . '</small>';
                        } else if ($direction === 'left_with_space') {
                            $price_display = '<small>' . $currency . '</small> ' . $amount_str;
                        } else { // left (default)
                            $price_display = '<small>' . $currency . '</small>' . $amount_str;
                        }

                        $date_display = '';
                        if (!empty($bid['date'])) {
                            $date_display = date_i18n(get_option('date_format'), strtotime($bid['date']));
                        }

                        $comment_display = wp_kses_post($bid['comment']);
                    ?>
                        <tr>
                            <td class="col-ad" data-label="<?php echo esc_attr__("Ad", "adforest"); ?>">
                                <a href="<?php echo esc_url($ad_link); ?>" target="_blank"><?php echo esc_html($ad_title); ?></a>
                            </td>
                            <td class="col-bidder" data-label="<?php echo esc_attr__("Bidder", "adforest"); ?>">
                                <a href="<?php echo esc_url($bidder_profile); ?>" target="_blank"><?php echo esc_html($bidder_name); ?></a>
                                <div class="ad-bids-meta">ID: <?php echo esc_html($bid['bidder_id']); ?></div>
                            </td>
                            <td class="col-date" data-label="<?php echo esc_attr__("Date", "adforest"); ?>">
                                <span><?php echo esc_html($date_display); ?></span>
                            </td>
                            <td class="col-price text-right" data-label="<?php echo esc_attr__("Bid Amount", "adforest"); ?>">
                                <span><?php echo wp_kses_post($price_display); ?></span>
                            </td>
                            <td class="col-action text-right" data-label="<?php echo esc_attr__("Action", "adforest"); ?>">
                                <button type="button"
                                    class="btn dark-btn ad-bid-view-detail"
                                    data-bs-toggle="modal"
                                    data-bs-target="#ad-bid-detail-modal"
                                    data-ad-title="<?php echo esc_attr($ad_title); ?>"
                                    data-ad-link="<?php echo esc_url($ad_link); ?>"
                                    data-bidder-name="<?php echo esc_attr($bidder_name); ?>"
                                    data-bidder-profile="<?php echo esc_url($bidder_profile); ?>"
                                    data-date="<?php echo esc_attr($date_display); ?>"
                                        data-amount="<?php echo esc_attr(wp_strip_all_tags($price_display)); ?>"
                                    data-comment="<?php echo esc_attr(wp_strip_all_tags($comment_display)); ?>">
                                    <?php echo esc_html__("View Details", "adforest"); ?>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1) { ?>
        <nav aria-label="bids-pagination">
            <ul class="pagination">
                <?php
                $base_url = remove_query_arg('paged');
                for ($i = 1; $i <= $total_pages; $i++) {
                    $active = ($i === $page) ? 'active' : '';
                    $url = add_query_arg('paged', $i, $base_url);
                    echo '<li class="page-item ' . esc_attr($active) . '"><a class="page-link" href="' . esc_url($url) . '">' . esc_html($i) . '</a></li>';
                }
                ?>
            </ul>
        </nav>
    <?php } ?>

    <!-- Bid Detail Modal -->
    <div class="modal fade" id="ad-bid-detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo esc_html__("Bid Details", "adforest"); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><strong><?php echo esc_html__("Ad", "adforest"); ?>:</strong> <a id="bid-detail-ad-link" href="#" target="_blank"><span id="bid-detail-ad-title"></span></a></div>
                    <div class="mb-2"><strong><?php echo esc_html__("Bidder", "adforest"); ?>:</strong> <a id="bid-detail-bidder-link" href="#" target="_blank"><span id="bid-detail-bidder-name"></span></a></div>
                    <div class="mb-2"><strong><?php echo esc_html__("Date", "adforest"); ?>:</strong> <span id="bid-detail-date"></span></div>
                    <div class="mb-2"><strong><?php echo esc_html__("Bid Amount", "adforest"); ?>:</strong> <span id="bid-detail-amount"></span></div>
                    <div class="mb-2"><strong><?php echo esc_html__("Comment", "adforest"); ?>:</strong>
                        <div id="bid-detail-comment" style="white-space: pre-wrap;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo esc_html__("Close", "adforest"); ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.ad-bid-view-detail');
                if (!btn) return;
                var adTitle = btn.getAttribute('data-ad-title') || '';
                var adLink = btn.getAttribute('data-ad-link') || '#';
                var bidderName = btn.getAttribute('data-bidder-name') || '';
                var bidderProfile = btn.getAttribute('data-bidder-profile') || '#';
                var date = btn.getAttribute('data-date') || '';
                var amount = btn.getAttribute('data-amount') || '';
                var comment = btn.getAttribute('data-comment') || '';

                var t = document.getElementById('bid-detail-ad-title');
                if (t) t.textContent = adTitle;
                var al = document.getElementById('bid-detail-ad-link');
                if (al) al.setAttribute('href', adLink);
                var bn = document.getElementById('bid-detail-bidder-name');
                if (bn) bn.textContent = bidderName;
                var bl = document.getElementById('bid-detail-bidder-link');
                if (bl) bl.setAttribute('href', bidderProfile);
                var dd = document.getElementById('bid-detail-date');
                if (dd) dd.textContent = date;
                var ba = document.getElementById('bid-detail-amount');
                if (ba) ba.textContent = amount;
                var bc = document.getElementById('bid-detail-comment');
                if (bc) bc.textContent = comment;
            });
        })();
    </script>
</div>