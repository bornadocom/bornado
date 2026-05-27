<?php
//add_filter( 'woocommerce_payment_complete_order_status', 'adforest_after_payment', 10, 2 );
add_action('woocommerce_order_status_completed', 'adforest_after_payment');

if (!function_exists('adforest_after_payment')) {
    function adforest_after_payment($order_id)
    {
        global $adforest_theme;
        $order = new WC_Order($order_id);
        $uid = get_post_meta($order_id, '_customer_user', true);
        if ($uid == "") {
            $uid = $order->get_user_id();
        }
        $items = $order->get_items();
        foreach ($items as $item) {
            $product_id = $item['product_id'];
            $product_type = wc_get_product($product_id);
            $allowed_product_type = array('adforest_classified_pkgs', 'subscription', 'variable-subscription', 'sb_category_package');
            if (isset($adforest_theme['shop-turn-on']) && $adforest_theme['shop-turn-on'] && !in_array($product_type->get_type(), $allowed_product_type)) {
                continue;
            }
            $ads = get_post_meta($product_id, 'package_free_ads', true);
            $featured_ads = get_post_meta($product_id, 'package_featured_ads', true);
            $bump_ads = get_post_meta($product_id, 'package_bump_ads', true);
            $days = get_post_meta($product_id, 'package_expiry_days', true);

            $package_ad_expiry_days = get_post_meta($product_id, 'package_ad_expiry_days', true);
            $package_adFeatured_expiry_days = get_post_meta($product_id, 'package_adFeatured_expiry_days', true);
            /*
             * new features added start
             */
            $paid_biddings = get_post_meta($product_id, 'package_make_bidding_paid', true);
            $number_of_events = get_post_meta($product_id, 'number_of_events', true);
            $package_video_links = get_post_meta($product_id, 'package_video_links', true);
            $num_of_images = get_post_meta($product_id, 'package_num_of_images', true);
            $package_allow_tags = get_post_meta($product_id, 'package_allow_tags', true);
            $package_allow_bidding = get_post_meta($product_id, 'package_allow_bidding', true);
            $package_allow_categories = get_post_meta($product_id, 'package_allow_categories', true);


            // Compute package expiry by rolling over remaining days (if any)
            $package_data = get_user_meta($uid, 'adforest_ads_package_details', true);
            $pkg_expiry_value = $days;
            if ($days == '-1') {
                $pkg_expiry_value = '-1';
            } else if (is_numeric($days) && (int)$days > 0) {
                $existing_expiry = '';
                if (is_array($package_data)
                    && isset($package_data[$product_id])
                    && isset($package_data[$product_id]['pkg_expiry_days'])
                    && $package_data[$product_id]['pkg_expiry_days'] !== ''
                    && $package_data[$product_id]['pkg_expiry_days'] !== '-1') {
                    $existing_expiry = $package_data[$product_id]['pkg_expiry_days'];
                }
                $today_str = current_time('Y-m-d');
                $base_date = $today_str;
                if ($existing_expiry) {
                    $e_date = strtotime($existing_expiry);
                    $today_ts = strtotime($today_str);
                    $base_date = ($today_ts > $e_date) ? $today_str : $existing_expiry;
                }
                $pkg_expiry_value = date('Y-m-d', strtotime($base_date . " +$days days"));
            }

            if (isset($product_id) && $product_id != "") {

                $naw_pkg_details = array();
                $naw_pkg_details[$product_id] = array(
                    'free_ads' => $ads,
                    'featured_ads' => $featured_ads,
                    'bump_ads' => $bump_ads,
                    'pkg_expiry_days' => $pkg_expiry_value,
                    'ad_expiry_days' => $package_ad_expiry_days,
                    'featured_expiry_days' => $package_adFeatured_expiry_days,
                    'video_links' => $package_video_links,
                    'num_of_images' => $num_of_images,
                    'allow_tags' => $package_allow_tags,
                    'allow_bidding' => $package_allow_bidding,
                    'number_of_events' => $number_of_events,
                    'paid_biddings' => $paid_biddings,
                    'allow_cate' => $package_allow_categories,
                );

                $package_data = get_user_meta($uid, 'adforest_ads_package_details', true);

                if (isset($package_data) && !empty($package_data) && is_array($package_data)) {
                    foreach ($package_data as $key => $val) {
                        if (isset($naw_pkg_details) && count($naw_pkg_details) > 0) {
                            foreach ($naw_pkg_details as $new_key => $new_pkgs) {
                                if ($key == $new_key) {
                                    $update_package = array();

                                    if ($naw_pkg_details[$key]['free_ads'] == "-1") {
                                        $update_package[$new_key]['free_ads'] = $naw_pkg_details[$key]['free_ads'];
                                    } else {
                                        $update_package[$new_key]['free_ads'] = intval($package_data[$key]['free_ads']) + intval($naw_pkg_details[$key]['free_ads']);
                                    }

                                    if ($naw_pkg_details[$key]['ad_expiry_days'] == "-1") {
                                        $update_package[$new_key]['ad_expiry_days'] = $naw_pkg_details[$key]['ad_expiry_days'];
                                    } else {
                                        $update_package[$new_key]['ad_expiry_days'] = intval($naw_pkg_details[$key]['ad_expiry_days']);
                                    }

                                    if ($naw_pkg_details[$key]['featured_ads'] == "-1") {
                                        $update_package[$new_key]['featured_ads'] = $naw_pkg_details[$key]['featured_ads'];
                                    } else {
                                        if (isset($package_data[$key]['featured_ads']) && !empty($package_data[$key]['featured_ads']) && isset($naw_pkg_details[$key]['featured_ads']) && !empty($naw_pkg_details[$key]['featured_ads'])) {
                                            $update_package[$new_key]['featured_ads'] = intval($package_data[$key]['featured_ads']) + intval($naw_pkg_details[$key]['featured_ads']);
                                        }
                                    }

                                    if ($naw_pkg_details[$key]['bump_ads'] == "-1") {
                                        $update_package[$new_key]['bump_ads'] = $naw_pkg_details[$key]['bump_ads'];
                                    } else {
                                        if (isset($package_data[$key]['bump_ads']) && !empty($package_data[$key]['bump_ads']) && isset($naw_pkg_details[$key]['bump_ads']) && !empty($naw_pkg_details[$key]['bump_ads'])) {
                                            $update_package[$new_key]['bump_ads'] = intval($package_data[$key]['bump_ads']) + intval($naw_pkg_details[$key]['bump_ads']);
                                        }
                                    }

                                    if ($naw_pkg_details[$key]['featured_expiry_days'] == "-1") {
                                        $update_package[$new_key]['featured_expiry_days'] = $naw_pkg_details[$key]['featured_expiry_days'];
                                    } else {
                                        if (isset($package_data[$key]['featured_expiry_days']) && !empty($package_data[$key]['featured_expiry_days']) && isset($naw_pkg_details[$key]['featured_expiry_days']) && !empty($naw_pkg_details[$key]['featured_expiry_days'])) {
                                            $update_package[$new_key]['featured_expiry_days'] = intval($naw_pkg_details[$key]['featured_expiry_days']);
                                        }
                                    }

                                    if ($naw_pkg_details[$key]['pkg_expiry_days'] == "-1") {
                                        $update_package[$new_key]['pkg_expiry_days'] = $naw_pkg_details[$key]['pkg_expiry_days'];
                                    } else {
                                        if (isset($package_data[$key]['pkg_expiry_days']) && !empty($package_data[$key]['pkg_expiry_days']) && isset($naw_pkg_details[$key]['pkg_expiry_days']) && !empty($naw_pkg_details[$key]['pkg_expiry_days'])) {
                                            $update_package[$new_key]['pkg_expiry_days'] = $naw_pkg_details[$key]['pkg_expiry_days'];
                                        }
                                    }

                                    $update_package[$new_key]['video_links'] = $naw_pkg_details[$key]['video_links'];

                                    if ($naw_pkg_details[$key]['num_of_images'] == "-1") {
                                        $update_package[$new_key]['num_of_images'] = $naw_pkg_details[$key]['num_of_images'];
                                    } else {
                                        if (isset($naw_pkg_details[$key]['num_of_images']) && !empty($naw_pkg_details[$key]['num_of_images'])) {
                                            $update_package[$new_key]['num_of_images'] = intval($naw_pkg_details[$key]['num_of_images']);
                                        }
                                    }

                                    $update_package[$new_key]['allow_tags'] = $naw_pkg_details[$key]['allow_tags'];

                                    if ($naw_pkg_details[$key]['allow_bidding'] == "-1") {
                                        $update_package[$new_key]['allow_bidding'] = $naw_pkg_details[$key]['allow_bidding'];
                                    } else {
                                        if (isset($package_data[$key]['allow_bidding']) && !empty($package_data[$key]['allow_bidding']) && isset($naw_pkg_details[$key]['allow_bidding']) && !empty($naw_pkg_details[$key]['allow_bidding'])) {
                                            $update_package[$new_key]['allow_bidding'] = intval($package_data[$key]['allow_bidding']) + intval($naw_pkg_details[$key]['allow_bidding']);
                                        }
                                    }

                                    if ($naw_pkg_details[$key]['number_of_events'] == "-1") {
                                        $update_package[$new_key]['number_of_events'] = $naw_pkg_details[$key]['number_of_events'];
                                    } else {
                                        if (isset($package_data[$key]['number_of_events']) && !empty($package_data[$key]['number_of_events']) && isset($naw_pkg_details[$key]['number_of_events']) && !empty($naw_pkg_details[$key]['number_of_events'])) {
                                            $update_package[$new_key]['number_of_events'] = intval($package_data[$key]['number_of_events']) + intval($naw_pkg_details[$key]['number_of_events']);
                                        }
                                    }

                                    if ($naw_pkg_details[$key]['number_of_events'] == "-1") {
                                        $update_package[$new_key]['paid_biddings'] = $naw_pkg_details[$key]['paid_biddings'];
                                    } else {
                                        if (isset($package_data[$key]['paid_biddings']) && !empty($package_data[$key]['paid_biddings']) && isset($naw_pkg_details[$key]['paid_biddings']) && !empty($naw_pkg_details[$key]['paid_biddings'])) {
                                            $update_package[$new_key]['paid_biddings'] = intval($package_data[$key]['paid_biddings']) + intval($naw_pkg_details[$key]['paid_biddings']);
                                        }
                                    }

                                    if ($naw_pkg_details[$key]['allow_cate'] == 'all') {
                                        $update_package[$new_key]['allow_cate'] = 'all';
                                    } else {
                                        $update_package[$new_key]['allow_cate'] = $naw_pkg_details[$key]['allow_cate'];
                                    }

                                    unset($package_data[$key]);
                                    $package_data = $package_data + $update_package;
                                    update_user_meta($uid, 'adforest_ads_package_details', $package_data);
                                } else {
                                    $package_data = $package_data + $naw_pkg_details;
                                    update_user_meta($uid, 'adforest_ads_package_details', $package_data);
                                }
                            }
                        }
                    }
                } else {
                    update_user_meta($uid, 'adforest_ads_package_details', $naw_pkg_details);
                }
            }
            //else {

            //     // ///////
            //     // $current_categories = explode(",", $package_allow_categories);

            //     // $current_categories[] = $current_categories_pri;

            //     // $current_categories_unique = array_unique($current_categories);

            //     // $current_categories_unique = array_values($current_categories_unique);

            //     // $cat_base_pakg = implode(",", $current_categories_unique);

            //     $sb_claim_ads = get_post_meta($product_id, '_sb_claim_ads', true);

            //     update_user_meta($uid, '_sb_video_links', $package_video_links);
            //     update_user_meta($uid, '_sb_allow_tags', $package_allow_tags);
            //     update_user_meta($uid, 'package_ad_expiry_days', $package_ad_expiry_days);
            //     update_user_meta($uid, 'package_adFeatured_expiry_days', $package_adFeatured_expiry_days);

            //     if ($num_of_images == '-1') {
            //         update_user_meta($uid, '_sb_num_of_images', $num_of_images);
            //     } else if (is_numeric($num_of_images) && $num_of_images != 0) {
            //         update_user_meta($uid, '_sb_num_of_images', $num_of_images);
            //     }
            //     if ($package_allow_bidding == '-1') {
            //         update_user_meta($uid, '_sb_allow_bidding', $package_allow_bidding);
            //     } else if (is_numeric($package_allow_bidding) && $package_allow_bidding != 0) {
            //         $already_stored_biddings = get_user_meta($uid, '_sb_allow_bidding', true);
            //         if ($already_stored_biddings != '-1') {
            //             $new_bidding_count = (int) $package_allow_bidding + (int) $already_stored_biddings;
            //             update_user_meta($uid, '_sb_allow_bidding', $new_bidding_count);
            //         } else if ($already_stored_biddings == '-1') {
            //             update_user_meta($uid, '_sb_allow_bidding', $package_allow_bidding);
            //         }
            //     }
            //     if ($sb_claim_ads == '-1') {
            //         update_user_meta($uid, '_sb_claim_ads', $package_allow_bidding);
            //     } else if (is_numeric($sb_claim_ads) && $sb_claim_ads != 0) {
            //         $already_sb_claim_ads = get_user_meta($uid, '_sb_claim_ads', true);
            //         if ($already_sb_claim_ads != '-1') {
            //             $new_sb_claim_ads = (int) $sb_claim_ads + (int) $already_sb_claim_ads;
            //             update_user_meta($uid, '_sb_claim_ads', $new_sb_claim_ads);
            //         } else if ($already_sb_claim_ads == '-1') {
            //             update_user_meta($uid, '_sb_claim_ads', $sb_claim_ads);
            //         }
            //     }
            //     /*
            //      * new features added end
            //      */
            //     $sb_pkg_type = get_user_meta($uid, '_sb_pkg_type', true);
            //     $current_pkg_type = explode("+", get_the_title($product_id));
            //     $current_pkg_type[] = $sb_pkg_type;
            //     $current_current_pkg_type = array_unique($current_pkg_type);
            //     $current_current_pkg_type = array_values($current_current_pkg_type);
            //     $current_base_pakg = implode("+", $current_current_pkg_type);
            //     update_user_meta($uid, '_sb_pkg_type', $current_base_pakg);
            //     if ($ads == '-1') {
            //         update_user_meta($uid, '_sb_simple_ads', '-1');
            //     } else if (is_numeric($ads) && $ads != 0) {
            //         $simple_ads = get_user_meta($uid, '_sb_simple_ads', true);
            //         if ($simple_ads != '-1') {
            //             $simple_ads = (int)$simple_ads;
            //             $new_ads = $ads + $simple_ads;
            //             update_user_meta($uid, '_sb_simple_ads', $new_ads);
            //         } else if ($simple_ads == '-1') {
            //             update_user_meta($uid, '_sb_simple_ads', $ads);
            //         }
            //     }
            //     if ($featured_ads == '-1') {
            //         update_user_meta($uid, '_sb_featured_ads', '-1');
            //     } else if (is_numeric($featured_ads) && $featured_ads != 0) {
            //         $f_ads = get_user_meta($uid, '_sb_featured_ads', true);
            //         if ($f_ads != '-1') {
            //             $f_ads = (int) $f_ads;
            //             $new_f_fads = $featured_ads + $f_ads;
            //             update_user_meta($uid, '_sb_featured_ads', $new_f_fads);
            //         } else if ($f_ads == '-1') {
            //             update_user_meta($uid, '_sb_featured_ads', $featured_ads);
            //         }
            //     }

            //     if ($bump_ads == '-1') {
            //         update_user_meta($uid, '_sb_bump_ads', '-1');
            //     } else if (is_numeric($bump_ads) && $bump_ads != 0) {
            //         $b_ads = get_user_meta($uid, '_sb_bump_ads', true);
            //         if ($b_ads != '-1') {
            //             $b_ads = (int) $b_ads;
            //             $new_b_fads = $bump_ads + $b_ads;
            //             update_user_meta($uid, '_sb_bump_ads', $new_b_fads);
            //         } else if ($b_ads == '-1') {
            //             update_user_meta($uid, '_sb_bump_ads', $bump_ads);
            //         }
            //     }

            //     if ($days == '-1') {
            //         update_user_meta($uid, '_sb_expire_ads', '-1');
            //     } else if($days > 0){
            //         $expiry_date = get_user_meta($uid, '_sb_expire_ads', true);
            //         $e_date = strtotime($expiry_date);
            //         $today = strtotime(date('Y-m-d'));
            //         if ($today > $e_date) {
            //             $new_expiry = date('Y-m-d', strtotime("+$days days"));
            //         } else {
            //             $date = date_create($expiry_date);
            //             date_add($date, date_interval_create_from_date_string("$days days"));
            //             $new_expiry = date_format($date, "Y-m-d");
            //         }
            //         update_user_meta($uid, '_sb_expire_ads', $new_expiry);
            //     }


            //        $paid_biddings   =     get_post_meta($product_id, 'package_make_bidding_paid', true);
            //     if ($paid_biddings == '-1') {
            //         update_user_meta($uid, '_sb_paid_biddings', '-1');
            //     } else if (is_numeric($paid_biddings) && $paid_biddings != 0) {
            //         $user_paid_biddings = get_user_meta($uid, '_sb_paid_biddings', true);
            //         if ($user_paid_biddings != '-1') {
            //             $user_paid_biddings = $user_paid_biddings;
            //             $new_biddings = (int) $paid_biddings + (int) $user_paid_biddings;
            //             update_user_meta($uid, '_sb_paid_biddings', $new_biddings);
            //         } else if ($simple_ads == '-1') {
            //             update_user_meta($uid, '_sb_paid_biddings', $paid_biddings);
            //         }
            //     }


            //        $number_of_events   =     get_post_meta($product_id, 'number_of_events', true);
            //     if ($number_of_events == '-1') {
            //         update_user_meta($uid, 'number_of_events', '-1');
            //     } else if (is_numeric($number_of_events) && $number_of_events != 0) {
            //         $user_number_of_events = get_user_meta($uid, 'number_of_events', true);
            //         if ($user_number_of_events != '-1') {
            //             $user_number_of_events = $user_number_of_events;
            //             $new_number_of_events = (int) $number_of_events + (int) $user_number_of_events;
            //             update_user_meta($uid, 'number_of_events', $new_number_of_events);
            //         } else if ($simple_ads == '-1') {
            //             update_user_meta($uid, 'number_of_events', $number_of_events);
            //         }
            //     }
            // }

            //            $data_arr  =  array();
            //            $data_arr['_sb_pkg_type']              =   get_the_title($product_id);
            //            $data_arr['package_id']                =   $product_id;
            //            $data_arr['_sb_video_links']           =   $package_video_links;
            //            $data_arr['package_allow_categories']  =   $package_allow_categories;
            //            $data_arr['package_ad_expiry_days']    =   $package_ad_expiry_days;
            //            $data_arr['package_adFeatured_expiry_days'] =   $package_adFeatured_expiry_days;
            //            $data_arr['_sb_allow_bidding'] =   $package_allow_bidding;
            //            $data_arr['_sb_simple_ads']    =   $ads;
            //            $data_arr['_sb_featured_ads']  =   $featured_ads;
            //            $data_arr['_sb_bump_ads']      =   $bump_ads;
            //            $data_arr['_sb_expire_ads']    =   $days;
            //            $data_arr['_sb_paid_biddings'] =   $paid_biddings;
            //
            //            update_user_meta($uid , "_sb_user_packge_$uid"."_".$product_id_ , $data_arr);

        }
    }
}


add_action('woocommerce_scheduled_subscription_trial_end', 'registration_trial_expired', 100);

if (!function_exists('registration_trial_expired')) {
    function registration_trial_expired($subscription_id)
    {

        $subscription_obj = wcs_get_subscription($subscription_id);
        $items = $subscription_obj->get_items();

        if (is_array($items) && count($items) > 0) {
            foreach ($items as $item) {
                $product_id = $item['product_id'];
                if ($product_id != 0 && $product_id != "") {
                    $order_id = $item['order_id'];
                    $uid = get_post_meta($order_id, '_customer_user', true);
                    $product_type = wc_get_product($product_id);

                    $allowed_product_type = array('adforest_classified_pkgs', 'subscription', 'variable-subscription');

                    if (isset($adforest_theme['shop-turn-on']) && $adforest_theme['shop-turn-on'] && !in_array($product_type->get_type(), $allowed_product_type)) {
                        continue;
                    }

                    $ads = get_post_meta($product_id, 'package_free_ads', true);
                    $featured_ads = get_post_meta($product_id, 'package_featured_ads', true);
                    $bump_ads = get_post_meta($product_id, 'package_bump_ads', true);
                    $days = get_post_meta($product_id, 'package_expiry_days', true);

                    $package_ad_expiry_days = get_post_meta($product_id, 'package_ad_expiry_days', true);
                    $package_adFeatured_expiry_days = get_post_meta($product_id, 'package_adFeatured_expiry_days', true);
                    /*
                     * new features added start
                     */
                    $package_video_links = get_post_meta($product_id, 'package_video_links', true);
                    $num_of_images = get_post_meta($product_id, 'package_num_of_images', true);
                    $package_allow_tags = get_post_meta($product_id, 'package_allow_tags', true);
                    $package_allow_bidding = get_post_meta($product_id, 'package_allow_bidding', true);
                    $package_allow_categories = get_post_meta($product_id, 'package_allow_categories', true);

                    //update_user_meta($uid, '_sb_video_links', $package_video_links);
                    //update_user_meta($uid, '_sb_allow_tags', $package_allow_tags);
                    //update_user_meta($uid, 'package_allow_categories', $package_allow_categories);


                    $simple_ad_expiry = get_user_meta($uid, 'package_ad_expiry_days', true);
                    if ($simple_ad_expiry != "" && $simple_ad_expiry > 0 && $package_ad_expiry_days > 0) {

                        $package_ad_expiry_days = (int)$simple_ad_expiry - (int)$package_ad_expiry_days;

                        if ($package_ad_expiry_days < 0) {

                            $package_ad_expiry_days = 0;
                        }

                        update_user_meta($uid, 'package_ad_expiry_days', $package_ad_expiry_days);
                    }

                    $feature_ad_expiry = get_user_meta($uid, 'package_adFeatured_expiry_days', true);
                    if ($feature_ad_expiry != "" && $feature_ad_expiry > 0 && $package_adFeatured_expiry_days > 0) {

                        $package_adFeatured_expiry_days = (int)$feature_ad_expiry - (int)$package_adFeatured_expiry_days;

                        if ($package_adFeatured_expiry_days < 0) {

                            $package_adFeatured_expiry_days = 0;
                        }
                        update_user_meta($uid, 'package_adFeatured_expiry_days', $package_adFeatured_expiry_days);
                    }


                    $user_images = get_user_meta($uid, '_sb_num_of_images', true);

                    if ($num_of_images == '-1') {
                        update_user_meta($uid, '_sb_num_of_images', $num_of_images);
                    } else if (is_numeric($num_of_images) && $num_of_images > 0 && $user_images > 0) {

                        $num_of_images = $user_images - $num_of_images;

                        if ($num_of_images < 0) {

                            $num_of_images = 0;
                        }
                        update_user_meta($uid, '_sb_num_of_images', $num_of_images);
                    }

                    $user_bidding = get_user_meta($uid, '_sb_allow_bidding', true);
                    if ($package_allow_bidding == '-1') {
                        update_user_meta($uid, '_sb_allow_bidding', $package_allow_bidding);
                    } else if (is_numeric($package_allow_bidding) && $package_allow_bidding != 0 && (int)$user_bidding > 0) {
                        $already_stored_biddings = get_user_meta($uid, '_sb_allow_bidding', true);
                        if ($already_stored_biddings != '-1') {
                            $new_bidding_count = $already_stored_biddings - $package_allow_bidding;
                            if ($new_bidding_count < 0) {

                                $new_bidding_count = 0;
                            }
                            update_user_meta($uid, '_sb_allow_bidding', $new_bidding_count);
                        } else if ($already_stored_biddings == '-1') {
                            update_user_meta($uid, '_sb_allow_bidding', $package_allow_bidding);
                        }
                    }

                    /*
                     * new features added end
                     */

                    update_user_meta($uid, '_sb_pkg_type', get_the_title($product_id));

                    if ($ads == '-1') {
                        update_user_meta($uid, '_sb_simple_ads', '-1');
                    } else if (is_numeric($ads) && $ads != 0) {
                        $simple_ads = get_user_meta($uid, '_sb_simple_ads', true);
                        if ($simple_ads != '-1') {
                            $simple_ads = $simple_ads;
                            $new_ads = (int)$simple_ads - (int)$ads;

                            if ($new_ads < 0) {

                                $new_ads = 0;
                            }
                            update_user_meta($uid, '_sb_simple_ads', $new_ads);
                        } else if ($simple_ads == '-1') {
                            update_user_meta($uid, '_sb_simple_ads', $ads);
                        }
                    }
                    if ($featured_ads == '-1') {
                        update_user_meta($uid, '_sb_featured_ads', '-1');
                    } else if (is_numeric($featured_ads) && $featured_ads != 0) {
                        $f_ads = get_user_meta($uid, '_sb_featured_ads', true);
                        if ($f_ads != '-1') {
                            $f_ads = (int)$f_ads;
                            $new_f_fads = $f_ads - $featured_ads;

                            if ($new_f_fads < 0) {

                                $new_f_fads = 0;
                            }

                            update_user_meta($uid, '_sb_featured_ads', $new_f_fads);
                        } else if ($f_ads == '-1') {
                            update_user_meta($uid, '_sb_featured_ads', $featured_ads);
                        }
                    }

                    if ($bump_ads == '-1') {
                        update_user_meta($uid, '_sb_bump_ads', '-1');
                    } else if (is_numeric($bump_ads) && $bump_ads != 0) {
                        $b_ads = get_user_meta($uid, '_sb_bump_ads', true);
                        if ($b_ads != '-1') {
                            $b_ads = (int)$b_ads;
                            $new_b_fads = $b_ads - $bump_ads;
                            if ($new_b_fads < 0) {

                                $new_b_fads = 0;
                            }
                            update_user_meta($uid, '_sb_bump_ads', $new_b_fads);
                        } else if ($b_ads == '-1') {
                            update_user_meta($uid, '_sb_bump_ads', $bump_ads);
                        }
                    }

                    if ($days == '-1') {
                        update_user_meta($uid, '_sb_expire_ads', '-1');
                    } else {
                        $expiry_date = get_user_meta($uid, '_sb_expire_ads', true);
                        $e_date = strtotime($expiry_date);
                        $today = strtotime(current_time('Y-m-d'));
                        if ($today > $e_date) {
                            $new_expiry = current_time('Y-m-d');
                        } else {
                            $date = date_create($expiry_date);
                            date_add($date, date_interval_create_from_date_string("-$days days"));
                            $new_expiry = date_format($date, "Y-m-d");
                        }
                        update_user_meta($uid, '_sb_expire_ads', $new_expiry);
                    }
                }
            }
        }
    }
}

if (!function_exists('adforest_after_payment_test')) {
    function adforest_after_payment_test($product_id)
    {
        $uid = get_current_user_id();
        $ads = get_post_meta($product_id, 'package_free_ads', true);
        $featured_ads = get_post_meta($product_id, 'package_featured_ads', true);
        $days = get_post_meta($product_id, 'package_expiry_days', true);
        update_user_meta($uid, '_sb_pkg_type', get_the_title($product_id));
        if ($ads == '-1') {
            update_user_meta($uid, '_sb_simple_ads', '-1');
        } else if (is_numeric($ads) && $ads != 0) {
            $simple_ads = get_user_meta($uid, '_sb_simple_ads', true);
            $new_ads = $ads + $simple_ads;
            update_user_meta($uid, '_sb_simple_ads', $new_ads);
        }
        if ($featured_ads == '-1') {
            update_user_meta($uid, '_sb_featured_ads', '-1');
        } else if (is_numeric($featured_ads) && $featured_ads != 0) {
            $f_ads = get_user_meta($uid, '_sb_featured_ads', true);
            $f_ads = (int)$f_ads;
            $new_f_fads = $featured_ads + $f_ads;
            update_user_meta($uid, '_sb_featured_ads', $new_f_fads);
        }

        if ($days == '-1') {
            update_user_meta($uid, '_sb_expire_ads', '-1');
        } else {
            $expiry_date = get_user_meta($uid, '_sb_expire_ads', true);
            $e_date = strtotime($expiry_date);
            $today = strtotime(current_time('Y-m-d'));
            if ($today > $e_date) {
                $new_expiry = date('Y-m-d', strtotime("+$days days", $today));
            } else {
                $date = date_create($expiry_date);
                date_add($date, date_interval_create_from_date_string("$days days"));
                $new_expiry = date_format($date, "Y-m-d");
            }
            update_user_meta($uid, '_sb_expire_ads', $new_expiry);
        }
    }
}

if (!function_exists('adforest_hide_package_quantity')) {
    function adforest_hide_package_quantity($return, $product)
    {
        global $adforest_theme;
        if (isset($adforest_theme['shop-turn-on']) && $adforest_theme['shop-turn-on']) {
            return false;
        } else {
            return true;
        }
    }
}
add_filter('woocommerce_is_sold_individually', 'adforest_hide_package_quantity', 10, 2);

if (!function_exists('adforest_woo_price')) {
    function adforest_woo_price($currency = '', $price = 0)
    {
        global $adforest_theme;
        $thousands_sep = wc_get_price_thousand_separator();
        $decimals = wc_get_price_decimals();;
        $decimals_separator = wc_get_price_decimal_separator();

        $price = number_format((int)$price, $decimals, $decimals_separator, $thousands_sep);
        $price = (isset($price) && $price != "") ? $price : 0;

        if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right') {
            $price = $price . $currency;
        } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left') {
            $price = $currency . $price;
        } else {
            $price = $currency . $price;
        }

        return $price;
    }
}

/**
 * Auto Complete all WooCommerce orders.
 */
add_action('woocommerce_thankyou', 'adforest_custom_woocommerce_auto_complete_order', 10, 1);
if (!function_exists('adforest_custom_woocommerce_auto_complete_order')) {
    function adforest_custom_woocommerce_auto_complete_order($order_id)
    {
        if (!$order_id) {
            return;
        }

        global $adforest_theme;
        $adforest_theme = get_option('adforest_theme');

        if (isset($adforest_theme['sb_order_auto_approve']) && $adforest_theme['sb_order_auto_approve']) {
            $disable_auto_approve = isset($adforest_theme['sb_order_auto_approve_disable']) && !empty($adforest_theme['sb_order_auto_approve_disable']) ? $adforest_theme['sb_order_auto_approve_disable'] : array();
            $order_paid_method = get_post_meta($order_id, '_payment_method', true);
            $order_paid_method = isset($order_paid_method) && !empty($order_paid_method) ? $order_paid_method : '';
            if (isset($disable_auto_approve) && !empty($disable_auto_approve) && is_array($disable_auto_approve)) {
                if ($order_paid_method !== '' && in_array($order_paid_method, $disable_auto_approve)) {
                    return;
                }
            }
            $order = wc_get_order($order_id);
            $order->update_status('completed');
        }
    }
}

if (!function_exists('adforest_product_img_url')) {
    function adforest_product_img_url($size = 'shop_catalog')
    {
        global $post;
        $image_size = apply_filters('single_product_archive_thumbnail_size', $size);
        return get_the_post_thumbnail_url($post->ID, $image_size);
    }
}

if (!function_exists('adforest_product_price')) {
    function adforest_product_price($product, $view = 'default', $price_class = '')
    {
        global $product, $adforest_theme;

        if (empty($product)) {

            die();
        }

        $sb_regular_price = $product->get_regular_price();
        $sb_sale_price = $product->get_sale_price();

        $thousands_sep = ",";
        if (isset($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator'] != '') {
            $thousands_sep = $adforest_theme['sb_price_separator'];
        }
        $decimals = 0;
        if (isset($adforest_theme['sb_price_decimals']) && $adforest_theme['sb_price_decimals'] != '') {
            $decimals = $adforest_theme['sb_price_decimals'];
        }
        $decimals_separator = ".";
        if (isset($adforest_theme['sb_price_decimals_separator']) && $adforest_theme['sb_price_decimals_separator'] != '') {
            $decimals_separator = $adforest_theme['sb_price_decimals_separator'];
        }
        // Price format
        $sb_regular_price = number_format((float)$sb_regular_price, $decimals, $decimals_separator, $thousands_sep);
        $sb_sale_price = number_format((float)$sb_sale_price, $decimals, $decimals_separator, $thousands_sep);

        if ($view == 'modern') {
            $sb_prod_price = sb_product_currency($sb_regular_price, 'span', 'dollartext');
            if ($product->is_on_sale()) {
                $sb_prod_price = sb_product_currency($sb_sale_price, 'span', 'dollartext') . '<small class="sale-value"><del>' . sb_product_currency($sb_regular_price) . '</del></small>';
            }
        } else if ($view == 'fancy') {
            $sb_prod_price = ' <h5>' . sb_product_currency($sb_regular_price, 'span') . '</h5>';
            if ($product->is_on_sale()) {
                $sb_prod_price = ' <h5>' . sb_product_currency($sb_sale_price, 'span') . '<small class="sale-value"><del>' . sb_product_currency($sb_regular_price) . '</del></small></h5>';
            }
        } else {
            $sb_prod_price = '<span class="price">' . sb_product_currency($sb_regular_price) . '</span>';
            if ($product->is_on_sale()) {
                $sb_prod_price = '<span class="price">' . sb_product_currency($sb_sale_price) . '</span><small class="sale-value"><del>' . sb_product_currency($sb_regular_price) . '</del></small>';
            }
        }
        return $sb_prod_price;
    }
}

if (!function_exists('sb_product_currency')) {
    function sb_product_currency($price, $tag = '', $class = '')
    {
        global $adforest_theme;
        $html = '';

        if ($price == '') {
            return $html;
        }

        $currency_sym = get_woocommerce_currency_symbol();
        $sb_price_direction = $adforest_theme['sb_price_direction'];
        $class = isset($class) && $class != '' ? ' class="' . $class . '" ' : '';
        $currency_html = '';

        if (isset($sb_price_direction) && ($sb_price_direction == 'right_with_space' || $sb_price_direction == 'right')) {
            if ($sb_price_direction == 'right_with_space') {
                $currency_html = ' ' . $currency_sym;
            }
            if ($sb_price_direction == 'right') {
                $currency_html = $currency_sym;
            }

            if (isset($tag) && $tag != '') {
                $html .= $price . '<' . $tag . $class . '>' . $currency_html . '</' . $tag . '>';
            } else {
                $html .= $price . $currency_html;
            }
        } else if (isset($sb_price_direction) && ($sb_price_direction == 'left_with_space' || $sb_price_direction == 'left')) {
            if ($sb_price_direction == 'left_with_space') {
                $currency_html = $currency_sym . ' ';
            }
            if ($sb_price_direction == 'left') {
                $currency_html = $currency_sym;
            }
            if (isset($tag) && $tag != '') {
                $html .= '<' . $tag . $class . '>' . $currency_html . '</' . $tag . '>' . $price;
            } else {
                $html .= $currency_html . $price;
            }
        }

        return $html;
    }
}

if (!function_exists('adforest_sale_html')) {
    function adforest_sale_html($product, $view = 'default')
    {

        $sale_html = '';
        if ($view == 'modern') {
            if ($product->is_on_sale()) {
                if ($product->is_on_sale()) {
                    $sale_html = '<div class="sale-pricing">
                               <span>' . esc_html__('Sale', 'adforest') . '</span>
                           </div>';
                }
            }
        } else if ($view == 'old') {
            if ($product->is_on_sale()) {
                $sale_html = '<div class="sb-modern-sale">
                            <img src="' . get_template_directory_uri() . '/images/sale2.png" alt="sale image"/>
                                <div class="sale-text">' . __('Sale', 'adforest') . '</div>
                           </div>';
            }
        } else if ($view == 'modern2') {
            if ($product->is_on_sale()) {
                if ($product->is_on_sale()) {
                    $sale_html = '<div class="pricing_sale"><div class="percentsaving">' . __('Sale', 'adforest') . '<div class="fold"></div> </div></div>';
                }
            }
        } else {
            if ($product->is_on_sale()) {
                $sale_html = '<div class="sale-div">
                                <img src="' . get_template_directory_uri() . '/images/sale.png" alt="sale image"/>
                                <div class="sale-text">' . __('Sale', 'adforest') . '</div>
                           </div>';
            }
        }
        return $sale_html;
    }
}