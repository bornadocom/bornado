<?php
echo adforest_dashboard_breadcrumb(esc_html__("Purchased Packages", "adforest"));
$selected_categories = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
// Remove packages assigned on registration
if (is_array($selected_categories) && count($selected_categories) > 0) {
    $selected_categories = array_filter($selected_categories, function ($package) {
        return !(is_array($package) &&
            isset($package['assigned_on_registration']) &&
            $package['assigned_on_registration'] == 1);
    });
}
if (is_array($selected_categories) && count($selected_categories) > 0) { ?>
    <div class="all-packages">
        <div class="row">
            <?php
            foreach ($selected_categories as $package_id => $details) :
                $product_title = '';
                $product = wc_get_product($package_id);
                if ($product) {
                    $product_title = $product->get_title();
                }

                if (!function_exists('display_package_value_enhanced')) {
                    function display_package_value_enhanced($details, $key)
                    {
                        if (!isset($details[$key]) || $details[$key] === '' || $details[$key] === null) {
                            return false;
                        }

                        $value = $details[$key];

                        if ($value === '-1' || $value === -1) {
                            return esc_html__("Unlimited", "adforest");
                        }

                        if (strtolower($value) === 'yes') {
                            return esc_html__("Yes", "adforest");
                        }
                        if (strtolower($value) === 'no') {
                            return esc_html__("No", "adforest");
                        }

                        return esc_html($value);
                    }
                }

                if (!function_exists('display_expiry_date_enhanced')) {
                    function display_expiry_date_enhanced($details, $key)
                    {
                        if (!isset($details[$key]) || $details[$key] === '' || $details[$key] === null) {
                            return false;
                        }

                        $value = $details[$key];

                        if ($value === '-1' || $value === -1) {
                            return esc_html__("Unlimited", "adforest");
                        }

                        if (strtotime($value)) {
                            return esc_html(date_i18n(get_option('date_format'), strtotime($value)));
                        }

                        return false;
                    }
                }

                if (!function_exists('should_display_package_field')) {
                    function should_display_package_field($details, $key)
                    {
                        return isset($details[$key]) && $details[$key] !== '' && $details[$key] !== null;
                    }
                }

                ?>
                <div class="col-xl-6 col-xxl-4">
                    <div class="card-style mb-30">
                        <div class="title d-flex flex-wrap justify-content-between">
                            <h6 class="text-medium mb-10"><?php echo $product_title ? esc_html($product_title) : esc_html__("Unknown Package", "adforest"); ?></h6>
                        </div>
                        <ul>
                            <?php if (should_display_package_field($details, 'free_ads')): ?>
                                <li>
                                    <span><?php echo esc_html__("Free Ads:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'free_ads'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'featured_ads')): ?>
                                <li>
                                    <span><?php echo esc_html__("Featured Ads:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'featured_ads'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'bump_ads')): ?>
                                <li>
                                    <span><?php echo esc_html__("Bump Ads:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'bump_ads'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php
                            $expiry_date = display_expiry_date_enhanced($details, 'pkg_expiry_days');
                            if ($expiry_date !== false):
                                ?>
                                <li>
                                    <span><?php echo esc_html__("Package Expiry Date:", "adforest"); ?></span>
                                    <span><?php echo $expiry_date; ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'ad_expiry_days')): ?>
                                <li>
                                    <span><?php echo esc_html__("Ad Expiry Days:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'ad_expiry_days'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'featured_expiry_days')): ?>
                                <li>
                                    <span><?php echo esc_html__("Featured Expiry Days:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'featured_expiry_days'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'video_links')): ?>
                                <li>
                                    <span><?php echo esc_html__("Video Links:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'video_links'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'num_of_images')): ?>
                                <li>
                                    <span><?php echo esc_html__("Number of Images:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'num_of_images'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'allow_tags')): ?>
                                <li>
                                    <span><?php echo esc_html__("Allow Tags:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'allow_tags'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'allow_bidding')): ?>
                                <li>
                                    <span><?php echo esc_html__("Allow Bidding:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'allow_bidding'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'number_of_events')): ?>
                                <li>
                                    <span><?php echo esc_html__("Number of Events:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'number_of_events'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php if (should_display_package_field($details, 'paid_biddings')): ?>
                                <li>
                                    <span><?php echo esc_html__("Paid Biddings:", "adforest"); ?></span>
                                    <span><?php echo display_package_value_enhanced($details, 'paid_biddings'); ?></span>
                                </li>
                            <?php endif; ?>

                            <?php
                            $category_ids = isset($details['allow_cate']) && $details['allow_cate'] !== '' ? explode(',', $details['allow_cate']) : [];
                            if (is_array($category_ids) && count($category_ids) > 0):
                                ?>
                                <li>
                                    <span><?php echo esc_html__("Allowed Categories:", "adforest"); ?></span>
                                    <span>
                                        <?php
                                        if ($category_ids[0] === 'all') {
                                            echo esc_html__("All", "adforest");
                                        } else {
                                            $category_names = [];
                                            foreach ($category_ids as $category_id) {
                                                $term = get_term($category_id, 'ad_cats');
                                                if ($term && !is_wp_error($term)) {
                                                    $category_names[] = $term->name;
                                                }
                                            }
                                            if (!empty($category_names)) {
                                                echo esc_html(implode(', ', $category_names));
                                            }
                                        }
                                        ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="alert alert-warning">
        <?php echo esc_html__("You have not purchased any packages yet.", "adforest"); ?>
    </div>
    <?php
}