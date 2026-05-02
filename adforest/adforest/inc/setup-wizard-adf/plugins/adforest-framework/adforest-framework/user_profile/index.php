<?php
add_action('show_user_profile', 'sb_show_extra_profile_fields');
add_action('edit_user_profile', 'sb_show_extra_profile_fields');
if (!function_exists('adforest_check_if_package_expired')) {

    function adforest_check_if_package_expired($user_id = 0)
    {
        $expiry = get_user_meta($user_id, '_sb_expire_ads', true);
        $is_expired = false;
        if ($expiry != '-1') {
            if ($expiry < date('Y-m-d')) {
                $is_expired = true;
            }
        }
        return $is_expired;
    }

}
if (!function_exists('sb_show_extra_profile_fields')) {

    function sb_show_extra_profile_fields($user)
    {
        ?>
        <h3><?php echo __('Adforest User Profile', 'redux-framework'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="_sb_badge_type"><?php echo __('Badge Color', 'redux-framework'); ?></label></th>
                <td>
                    <select name="_sb_badge_type" id="_sb_badge_type">
                        <option value=""><?php echo __('Select Type', 'redux-framework'); ?></option>
                        <option value="label-success" <?php if (get_the_author_meta('_sb_badge_type', $user->ID) == "label-success")
                            echo "selected"; ?>><?php echo __('Green', 'redux-framework'); ?>
                        </option>
                        <option value="label-warning" <?php if (get_the_author_meta('_sb_badge_type', $user->ID) == "label-warning")
                            echo "selected"; ?>><?php echo __('Orange', 'redux-framework'); ?>
                        </option>
                        <option value="label-info" <?php if (get_the_author_meta('_sb_badge_type', $user->ID) == "label-info")
                            echo "selected"; ?>><?php echo __('Blue', 'redux-framework'); ?></option>
                        <option value="label-danger" <?php if (get_the_author_meta('_sb_badge_type', $user->ID) == "label-danger")
                            echo "selected"; ?>><?php echo __('Red', 'redux-framework'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="_sb_badge_text"><?php echo __('Badge Text', 'redux-framework'); ?></label></th>
                <td>
                    <input type="text" name="_sb_badge_text" id="_sb_badge_text"
                           value="<?php echo esc_attr(get_the_author_meta('_sb_badge_text', $user->ID)); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr>
                <th><label for="_sb_ph_num_"><?php echo __('Phone Number', 'redux-framework'); ?></label></th>
                <td>
                    <input type="text" name="_sb_ph_num_" id="_sb_ph_num_"
                           value="<?php echo esc_attr(get_the_author_meta('_sb_contact', $user->ID)); ?>"
                           class="regular-text"/>
                    <small><?php echo __('+CountrycodeMobilenumber', 'redux-framework'); ?></small>
                </td>
            </tr>
            <tr>
                <th><label for="_sb_ph_verified_"><?php echo __('Phone no. verified', 'redux-framework'); ?></label>
                </th>
                <?php
                $ph_is_verified_ = get_the_author_meta('_sb_is_ph_verified', $user->ID);
                if ($ph_is_verified_ == "") {
                    $ph_is_verified_ = 0;
                }
                ?>
                <td>
                    <select name="_sb_ph_verified_" id="_sb_ph_verified_">
                        <option value=""><?php echo __('Select option', 'redux-framework'); ?></option>
                        <option value="0" <?php if ($ph_is_verified_ == '0')
                            echo "selected"; ?>>
                            <?php echo __('Not verified', 'redux-framework'); ?></option>
                        <option value="1" <?php if ($ph_is_verified_ == '1')
                            echo "selected"; ?>>
                            <?php echo __('Verified', 'redux-framework'); ?></option>
                    </select>
                </td>
                </td>
            </tr>
            <tr>
                <th><label for="_sb_user_type_"><?php echo __('User Type', 'redux-framework'); ?></label></th>
                <?php
                $user_type = get_user_meta($user->ID, '_sb_user_type', true);
                ?>
                <td>
                    <select name="_sb_user_type_" id="_sb_user_type_">
                        <option value=""><?php echo __('Select option', 'redux-framework'); ?></option>
                        <option value="Indiviual" <?php if ($user_type == 'Indiviual')
                            echo "selected"; ?>>
                            <?php echo __('Individual', 'redux-framework'); ?></option>
                        <option value="Dealer" <?php if ($user_type == 'Dealer')
                            echo "selected"; ?>>
                            <?php echo __('Dealer', 'redux-framework'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="_sb_user_location_"><?php echo __('User Location', 'redux-framework'); ?></label></th>
                <?php
                $user_location_ = get_the_author_meta('_sb_address', $user->ID);
                ?>
                <td>
                    <input type="text" name="_sb_user_location_" id="_sb_user_location_"
                           value="<?php echo esc_attr($user_location_); ?>" class="regular-text"/>
                </td>
            </tr>
            <tr>
                <th><label for="_sb_user_intro_"><?php echo __('User Introduction', 'redux-framework'); ?></label></th>
                <?php
                $_sb_user_intro_ = get_the_author_meta('_sb_user_intro', $user->ID);
                ?>
                <td>
                    <textarea rows="5" cols="30" name="_sb_user_intro_"
                              id="_sb_user_intro_"><?php echo esc_attr($_sb_user_intro_); ?></textarea>
                </td>
            </tr>
            <?php
            if (function_exists('adforest_social_profiles')) {
                $profiles = adforest_social_profiles();
                foreach ($profiles as $key => $value) {
                    $each_val = get_user_meta($user->ID, '_sb_profile_' . $key, true);
                    $each_val = isset($each_val) && $each_val != '' ? $each_val : '';
                    ?>
                    <tr>
                        <th><label for="<?php echo '_sb_profile_' . $key; ?>"><?php echo($value); ?></label></th>
                        <td>
                            <input type="text" name="<?php echo '_sb_profile_' . $key; ?>"
                                   id="<?php echo '_sb_profile_' . $key; ?>"
                                   value="<?php echo($each_val); ?>" class="regular-text"/>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr>
                <th><label for="_sb_trusted_user"><?php echo __('Trusted User', 'redux-framework'); ?></label></th>
                <?php
                $_sb_trusted_user = get_the_author_meta('_sb_trusted_user', $user->ID);
                $sb_trusted_user_checked = ($_sb_trusted_user == 1) ? 'checked="checked"' : '';
                ?>
                <td>
                    <input type="checkbox" name="_sb_trusted_user"
                           class="sb_trusted_user" <?php echo $sb_trusted_user_checked; ?> value="1">
                    <br/>
                    <p><?php esc_html_e("By making the user trusted. User's ads will be approved even (Admin Approval) is turn on in Theme Options. ", "redux-framework") ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="_sb_block_individual_messaging"><?php echo __('Block Messaging?', 'redux-framework'); ?></label>
                </th>
                <?php
                $sb_block_individual_messaging = get_the_author_meta('_sb_block_individual_messaging', $user->ID);
                $sb_block_individual_messaging_checked = ($sb_block_individual_messaging == 1) ? 'checked="checked"' : '';
                ?>
                <td>
                    <input type="checkbox" name="_sb_block_individual_messaging"
                           class="_sb_block_individual_messaging" <?php echo $sb_block_individual_messaging_checked; ?>
                           value="1">
                    <br/>
                    <p><?php esc_html_e("Block this user to send messging against ads", "redux-framework") ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="_sb_block_individual_messaging"><?php echo __('Send User email?', 'redux-framework'); ?></label>
                </th>
                <td>
                    <button class="button-primary" id="send_user_confirmation_email"
                            data-user_id="<?php echo esc_attr($user->ID) ?>">
                        <?php echo esc_html__('Send mail', 'redux-framework'); ?></button>
                    <input type="hidden" id="sb_send_user_account_confirmation_mail_nonce" value="<?php echo wp_create_nonce("adforest_send_user_account_confirmation_mail_nonce") ?>">
                    <br/>
                    <p><?php esc_html_e("Click on button to send email to user to let him know about his account verification", "redux-framework") ?>
                    </p>
                </td>
            </tr>
        </table>
        <br/>
        <div>
            <h2><?php echo __('Give User Packages', 'redux-framework'); ?></h2>

            <select name="give_user_package" id="give_user_package">
                <?php
                $packages = apply_filters('adforest_elementor_get_packages', []);

                echo '<option value="">' . esc_html__("Select a Package", "redux-framework") . '</option>';
                if ($packages) {
                    foreach ($packages as $pkg => $value) {
                        echo '<option value="' . esc_attr($pkg) . '">' . esc_html($value) . '</option>';
                    }
                } else {
                    echo '<option value="">' . esc_html__('No packages found', 'redux-framework') . '</option>';
                }
                ?>
            </select>
        </div>
        <br/>
        <div>
            <?php
            $selected_categories = get_user_meta($user->ID, 'adforest_ads_package_details', true);
            ?>
            <h2><?php echo __('User Packages/Subscriptions', 'redux-framework'); ?></h2>
            <div class="package-container">
                <?php
                if (is_array($selected_categories) && count($selected_categories) > 0) {
                    foreach ($selected_categories as $package_id => $details) : ?>
                        <div class="package adt_user_packages">
                            <div class="package-header">
                                <?php
                                $product = wc_get_product($package_id);
                                if ($product) {
                                    $product_title = $product->get_title();
                                }
                                ?>
                                <h3 class="product-title"> <?php echo $product_title; ?></h3>
                            </div>
                            <div class="package-body">
                                <ul>
                                    <li><span>Free Ads:</span> <span><?php echo ($details['free_ads'] == '-1' || $details['free_ads'] == -1) ? 'Unlimited' : (empty($details['free_ads']) ? '0' : $details['free_ads']); ?></span></li>
                                    <li><span>Featured Ads:</span> <span><?php echo ($details['featured_ads'] == '-1' || $details['featured_ads'] == -1) ? 'Unlimited' : (empty($details['featured_ads']) ? '0' : $details['featured_ads']); ?></span></li>
                                    <li><span>Bump Ads:</span> <span><?php echo ($details['bump_ads'] == '-1' || $details['bump_ads'] == -1) ? 'Unlimited' : (empty($details['bump_ads']) ? '0' : $details['bump_ads']); ?></span></li>
                                    <li><span>Package Expiry Date:</span>
                                        <span><?php echo ($details['pkg_expiry_days'] == '-1' || $details['pkg_expiry_days'] == -1) ? 'Never Expires' : (empty($details['pkg_expiry_days']) ? 'Not Set' : $details['pkg_expiry_days']); ?></span></li>
                                    <li><span>Ad Expiry Days:</span>
                                        <span><?php 
                                            $ad_expiry = isset($details['ad_expiry_days']) ? $details['ad_expiry_days'] : '';
                                            echo ($ad_expiry == '-1' || $ad_expiry == -1) ? 'Never Expires' : (empty($ad_expiry) ? 'Not Set' : $ad_expiry);
                                        ?></span></li>
                                    <li><span>Featured Expiry Days:</span>
                                        <span><?php echo ($details['featured_expiry_days'] == '-1' || $details['featured_expiry_days'] == -1) ? 'Never Expires' : (empty($details['featured_expiry_days']) ? 'Not Set' : $details['featured_expiry_days']); ?></span></li>
                                    <li><span>Video Links:</span> <span><?php echo ($details['video_links'] == '-1' || $details['video_links'] == -1) ? 'Unlimited' : (empty($details['video_links']) ? '0' : $details['video_links']); ?></span></li>
                                    <li><span>Number of Images:</span>
                                        <span><?php echo ($details['num_of_images'] == '-1' || $details['num_of_images'] == -1) ? 'Unlimited' : (empty($details['num_of_images']) ? '0' : $details['num_of_images']); ?></span></li>
                                    <li><span>Allow Tags:</span> <span><?php echo (strtolower($details['allow_tags']) == 'yes') ? 'Yes' : ((strtolower($details['allow_tags']) == 'no') ? 'No' : (empty($details['allow_tags']) ? 'Not Set' : $details['allow_tags'])); ?></span></li>
                                    <li><span>Allow Bidding:</span>
                                        <span><?php 
                                            $allow_bidding = isset($details['allow_bidding']) ? $details['allow_bidding'] : '';
                                            echo (strtolower($allow_bidding) == 'yes') ? 'Yes' : ((strtolower($allow_bidding) == 'no') ? 'No' : (empty($allow_bidding) ? 'Not Set' : $allow_bidding));
                                        ?></span></li>
                                    <li><span>Number of Events:</span>
                                        <span><?php 
                                            $num_events = isset($details['number_of_events']) ? $details['number_of_events'] : '';
                                            echo ($num_events == '-1' || $num_events == -1) ? 'Unlimited' : (empty($num_events) ? '0' : $num_events);
                                        ?></span></li>
                                    <li><span>Paid Biddings:</span>
                                        <span><?php 
                                            $paid_biddings = isset($details['paid_biddings']) ? $details['paid_biddings'] : '';
                                            echo ($paid_biddings == '-1' || $paid_biddings == -1) ? 'Unlimited' : (empty($paid_biddings) ? '0' : $paid_biddings);
                                        ?></span></li>
                                    <li><span>Allowed Categories:</span>
                                        <span>
                                            <?php
                                            $category_ids = isset($details['allow_cate']) ? explode(',', $details['allow_cate']) : array();
                                            if (empty($category_ids) || (count($category_ids) == 1 && empty($category_ids[0]))) {
                                                echo 'All Categories';
                                            } else {
                                                $category_names = array();
                                                foreach ($category_ids as $category_id) {
                                                    if (!empty($category_id)) {
                                                        $term = get_term($category_id, 'ad_cats');
                                                        if ($term && !is_wp_error($term)) {
                                                            $category_names[] = $term->name;
                                                        }
                                                    }
                                                }
                                                echo !empty($category_names) ? implode(', ', $category_names) : 'All Categories';
                                            }
                                            ?>
                                        </span>
                                    </li>
                                </ul>
                                <div class="package-actions">
                                    <button type="button" class="button button-danger revoke-package-btn" 
                                            data-user-id="<?php echo esc_attr($user->ID); ?>" 
                                            data-package-id="<?php echo esc_attr($package_id); ?>"
                                            data-package-name="<?php echo esc_attr($product_title); ?>">
                                        <?php echo __('Revoke Package', 'redux-framework'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                }
                ?>
            </div>
        </div>
        <br/>
        <div>
            <h2><?php echo __('User Rating', 'redux-framework'); ?></h2>
            <br/>
            <table class="wp-list-table widefat fixed striped users">
                <tr>
                    <th width="15%"><strong><?php echo __('Username who rated', 'redux-framework'); ?></strong></th>
                    <th width="10%"><strong><?php echo __('Rating', 'redux-framework'); ?></strong></th>
                    <th width="65%"><strong><?php echo __('Comments', 'redux-framework'); ?></strong></th>
                    <th width="10%"><strong><?php echo __('Action', 'redux-framework'); ?></strong></th>
                </tr>
                <?php
                $author_id = $user->ID;
                $ratings = adforest_get_all_ratings($user->ID);
                if (count($ratings) > 0) {
                    foreach ($ratings as $rating) {
                        $data = explode('_separator_', $rating->meta_value);
                        $rated = $data[0];
                        $comments = $data[1];
                        $date = $data[2];
                        $reply = '';
                        $reply_date = '';
                        if (isset($data[3])) {
                            $reply = $data[3];
                        }
                        if (isset($data[4])) {
                            $reply_date = $data[4];
                        }
                        $_arr = explode('_user_', $rating->meta_key);
                        $rator = $_arr[1];
                        $user = get_user_by('ID', $rator);
                        ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($rated) . ' ' . __('Star', 'redux-framework'); ?></td>
                            <td><?php echo esc_html($comments); ?></td>
                            <td><a href="javascript:void(0);" class="get_user_meta_id"
                                   data-mid="<?php echo esc_attr($rating->umeta_id); ?>"><?php echo __('Delete', 'redux-framework'); ?></a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="4"><?php echo __('There is no rating of this user yet.', 'redux-framework'); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>
        <br/>
        <?php
    }

}

add_action('personal_options_update', 'sb_save_extra_profile_fields');
add_action('edit_user_profile_update', 'sb_save_extra_profile_fields');

if (!function_exists('sb_save_extra_profile_fields')) {

    function sb_save_extra_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id))
            return false;

        /* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */


        update_user_meta(absint($user_id), 'package_ad_expiry_days', wp_kses_post($_POST['package_ad_expiry_days']));
        update_user_meta(absint($user_id), 'package_adFeatured_expiry_days', wp_kses_post($_POST['package_adFeatured_expiry_days']));

        update_user_meta(absint($user_id), '_sb_pkg_type', wp_kses_post($_POST['_sb_pkg_type']));
        update_user_meta(absint($user_id), '_sb_simple_ads', wp_kses_post($_POST['_sb_simple_ads']));
        update_user_meta(absint($user_id), '_sb_featured_ads', wp_kses_post($_POST['_sb_featured_ads']));
        update_user_meta(absint($user_id), '_sb_bump_ads', wp_kses_post($_POST['_sb_bump_ads']));
        update_user_meta(absint($user_id), '_sb_expire_ads', wp_kses_post($_POST['_sb_expire_ads']));
        update_user_meta(absint($user_id), '_sb_badge_type', wp_kses_post($_POST['_sb_badge_type']));
        update_user_meta(absint($user_id), '_sb_badge_text', wp_kses_post($_POST['_sb_badge_text']));
        update_user_meta(absint($user_id), '_sb_is_ph_verified', wp_kses_post($_POST['_sb_ph_verified_']));
        update_user_meta(absint($user_id), '_sb_contact', wp_kses_post($_POST['_sb_ph_num_']));
        update_user_meta(absint($user_id), '_sb_user_type', wp_kses_post($_POST['_sb_user_type_']));
        update_user_meta(absint($user_id), '_sb_address', wp_kses_post($_POST['_sb_user_location_']));
        update_user_meta(absint($user_id), '_sb_user_intro', wp_kses_post($_POST['_sb_user_intro_']));
        update_user_meta(absint($user_id), '_sb_video_links', wp_kses_post($_POST['_sb_video_links']));
        update_user_meta(absint($user_id), '_sb_allow_tags', wp_kses_post($_POST['_sb_allow_tags']));
        update_user_meta(absint($user_id), '_sb_allow_bidding', wp_kses_post($_POST['_sb_allow_bidding']));

        update_user_meta(absint($user_id), '_sb_paid_biddings', wp_kses_post($_POST['_sb_paid_biddings']));

        update_user_meta(absint($user_id), '_sb_num_of_images', wp_kses_post($_POST['_sb_num_of_images']));
        update_user_meta(absint($user_id), '_sb_trusted_user', wp_kses_post($_POST['_sb_trusted_user']));


        if (isset($_POST['number_of_events']) && $_POST['number_of_events'] != "") {
            update_user_meta(absint($user_id), 'number_of_events', $_POST['number_of_events']);
        }

        error_log($_POST['give_user_package']);
        if (isset($_POST['give_user_package']) && $_POST['give_user_package'] != "") {
            adforest_give_user_package_from_admin($_POST['give_user_package'], $user_id);
        }

        if (isset($_POST['_sb_block_individual_messaging']) && $_POST['_sb_block_individual_messaging'] == 1) {
            update_user_meta(absint($user_id), '_sb_block_individual_messaging', wp_kses_post($_POST['_sb_block_individual_messaging']));
        } else {
            update_user_meta(absint($user_id), '_sb_block_individual_messaging', 0);
        }
        if (function_exists('adforest_social_profiles')) {
            $profiles = adforest_social_profiles();
            foreach ($profiles as $key => $value) {
                update_user_meta($user_id, '_sb_profile_' . $key, sanitize_textarea_field($_POST['_sb_profile_' . $key]));
            }
        }
    }

}

/* Adding Custom coulumn in user dashboard */
if (!function_exists('new_modify_user_table')) {

    function new_modify_user_table($column)
    {
        $role = $column['role'];
        $posts = $column['posts'];
        unset($column['name']);
        unset($column['role']);
        unset($column['posts']);
        $column['display_name'] = __('Display Name', 'redux-framework');
        $column['_sb_user_type'] = __('User Type', 'redux-framework');
        // $column['_sb_pkg_type'] = __('Package', 'redux-framework');
        $column['role'] = $role;

        $column['posts'] = $posts;
        $column['classified_ads'] = __('Ads', 'redux-framework');
        return $column;
    }

}
add_filter('manage_users_columns', 'new_modify_user_table');
if (!function_exists('new_modify_user_table_row')) {

    function new_modify_user_table_row($val, $column_name, $user_id)
    {
        $is_expired = adforest_check_if_package_expired($user_id);
        if ($is_expired) {
            $is_expired_txt = get_the_author_meta('_sb_pkg_type', $user_id);
            if ($is_expired_txt) {
                $is_expired_txt .= '<br />(' . __("Expired", "redux-framework") . ')';
            }
        } else {
            $is_expired_txt = get_the_author_meta('_sb_pkg_type', $user_id);
        }

        // $args = array(
        //     'author' => $user_id,
        //     'post_type' => 'ad_post',
        //     'post_status' => 'publish',
        // );
        // $author_posts = new WP_Query($args);        
        // $ads_count    =   $author_posts->found_posts;
        // wp_reset_postdata();

        $ads_count = count_user_posts($user_id, 'ad_post');
        $userdata = get_userdata($user_id);

        $display_name = isset($userdata->display_name) ? $userdata->display_name : "";

        $display_name_meta = get_the_author_meta('display_name', $user_id);

        $display_name = $display_name_meta != "" ? $display_name_meta : $display_name;

        switch ($column_name) {
            case '_sb_user_type':
                if (get_the_author_meta('_sb_user_type', $user_id) == 'Indiviual')
                    return __('Individual', 'redux-framework');
                if (get_the_author_meta('_sb_user_type', $user_id) == 'Dealer')
                    return __('Dealer', 'redux-framework');
                return get_the_author_meta('_sb_user_type', $user_id);
                break;
            case 'display_name':
                return $display_name;
                break;
            case '_sb_pkg_type':
                return $is_expired_txt;
                break;
            case 'classified_ads':
                return $ads_count;
                break;

            default:
        }
        return $val;
    }

}

add_filter('manage_users_custom_column', 'new_modify_user_table_row', 99, 3);
if (!function_exists('adforest_get_all_ratings')) {

    function adforest_get_all_ratings($user_id)
    {
        global $wpdb;
        $ratings = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s ORDER BY umeta_id DESC", intval($user_id), '_user_%'), OBJECT);
        return $ratings;
    }

}
/* Add custom column using 'manage_users_columns' filter */
/* <?php esc_html_e("By making the user trusted. User's ads will be approved even (Admin Approval) is turn on in Theme Options. ", "redux-framework")?> */
if (!function_exists('adforest_trusted_users_column')) {

    function adforest_trusted_users_column($columns)
    {
        global $adforest_theme;
        $sb_trusted_user = isset($adforest_theme['sb_trusted_user']) && $adforest_theme['sb_trusted_user'] ? TRUE : FALSE;
        if ($sb_trusted_user) {
            return array_merge($columns, array('_sb_trusted_user' => __('Trusted?', 'redux-framework')));
        } else {
            return $columns;
        }
    }

}

/* Add the content from usermeta's table by using 'manage_users_custom_column' hook */
if (!function_exists('adforest_trusted_users_column_value')) {

    function adforest_trusted_users_column_value($val, $column_name, $user_id)
    {
        global $adforest_theme;
        $sb_trusted_user = isset($adforest_theme['sb_trusted_user']) && $adforest_theme['sb_trusted_user'] ? TRUE : FALSE;
        if ($sb_trusted_user) {
            if ('_sb_trusted_user' == $column_name) {
                //Custom value
                $val = get_user_meta($user_id, '_sb_trusted_user', true);
                $is_checked = (isset($val) && $val == 1) ? 'checked="checked"' : '';
                $is_value = (isset($val) && $val == 1) ? 1 : 0;
                $confirmationtext = __("Are you sure you want to do this?", "redux-framework");
                /* //$confirmation_attr = "onclick='return confirm("'$confirmationtext'");'";
                  $confirmation_attr = "return confirm('$confirmationtext');";//onclick="'.$confirmation_attr .'" */
                $val = '<input type="checkbox" name="_sb_trusted_user" class="sb_trusted_user" ' . $is_checked . ' data-user-id="' . $user_id . '"  >';
            }
            return $val;
        }
    }

}
$sb_trusted_user = TRUE;
//if (class_exists('Redux')) {
//    $sb_trusted_user = Redux::getOption('adforest_theme', 'sb_trusted_user');
//    $sb_trusted_user = isset($sb_trusted_user) && $sb_trusted_user ? TRUE : FALSE;
//}

if ($sb_trusted_user) {
    // Hook into filter
    add_filter('manage_users_columns', 'adforest_trusted_users_column');
    add_action('manage_users_custom_column', 'adforest_trusted_users_column_value', 10, 3);
}

/* Ajax handler for add to cart */
add_action('wp_ajax_sb_add_trusted_user', 'adforest_sb_add_trusted_user_func');
if (!function_exists('adforest_sb_add_trusted_user_func')) {

    function adforest_sb_add_trusted_user_func()
    {
        if (isset($_POST['user_id']) && $_POST['user_id'] != "") {
            update_user_meta($_POST['user_id'], '_sb_trusted_user', $_POST['checkbox_value']);
        }
    }

}

/* Ajax handler for getting user info for package revocation */
add_action('wp_ajax_get_user_info_for_revoke', 'adforest_get_user_info_for_revoke_func');
if (!function_exists('adforest_get_user_info_for_revoke_func')) {

    function adforest_get_user_info_for_revoke_func()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'revoke_package_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);

        if (!$user) {
            wp_send_json_error('User not found');
        }

        wp_send_json_success(array(
            'display_name' => $user->display_name,
            'user_email' => $user->user_email
        ));
    }

}

/* Ajax handler for revoking user package */
add_action('wp_ajax_revoke_user_package', 'adforest_revoke_user_package_func');
if (!function_exists('adforest_revoke_user_package_func')) {

    function adforest_revoke_user_package_func()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'revoke_package_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = intval($_POST['user_id']);
        $package_id = sanitize_text_field($_POST['package_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        $send_email = isset($_POST['send_email']) && $_POST['send_email'] === 'true';

        if (empty($reason)) {
            wp_send_json_error('Reason is required');
        }

        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('User not found');
        }

        // Get package details
        $packages = get_user_meta($user_id, 'adforest_ads_package_details', true);
        if (!is_array($packages) || !isset($packages[$package_id])) {
            wp_send_json_error('Package not found for this user');
        }

        $package_details = $packages[$package_id];
        $product = wc_get_product($package_id);
        $package_name = $product ? $product->get_title() : $package_id;

        // Remove the package from user meta
        unset($packages[$package_id]);
        update_user_meta($user_id, 'adforest_ads_package_details', $packages);

        // Log the revocation
        $admin_user = wp_get_current_user();
        $revocation_log = array(
            'timestamp' => current_time('mysql'),
            'admin_id' => $admin_user->ID,
            'admin_name' => $admin_user->display_name,
            'package_id' => $package_id,
            'package_name' => $package_name,
            'reason' => $reason,
            'user_id' => $user_id,
            'user_email' => $user->user_email
        );

        $existing_logs = get_option('adforest_package_revocations', array());
        $existing_logs[] = $revocation_log;
        update_option('adforest_package_revocations', $existing_logs);

        // Send email notification if requested
        if ($send_email && !empty($user->user_email)) {
            adforest_send_package_revocation_email($user, $package_name, $reason, $admin_user);
        }

        wp_send_json_success('Package revoked successfully');
    }

}

/* Function to send package revocation email */
if (!function_exists('adforest_send_package_revocation_email')) {

    function adforest_send_package_revocation_email($user, $package_name, $reason, $admin_user)
    {
        global $adforest_theme;

        $subject = isset($adforest_theme['sb_package_revocation_subject']) ? 
                   $adforest_theme['sb_package_revocation_subject'] : 
                   __('Your Package Has Been Revoked - %site_name%', 'adforest');

        $from = isset($adforest_theme['sb_package_revocation_from']) ? 
                $adforest_theme['sb_package_revocation_from'] : 
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>';

        $message = isset($adforest_theme['sb_package_revocation_message']) ? 
                   $adforest_theme['sb_package_revocation_message'] : 
                   adforest_get_default_package_revocation_message();

        // Replace placeholders
        $replacements = array(
            '%site_name%' => get_bloginfo('name'),
            '%user_name%' => $user->display_name,
            '%user_email%' => $user->user_email,
            '%package_name%' => $package_name,
            '%admin_name%' => $admin_user->display_name,
            '%reason%' => $reason,
            '%date%' => current_time('mysql'),
            '%site_url%' => get_site_url()
        );

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $message = str_replace(array_keys($replacements), array_values($replacements), $message);

        $headers = array('Content-Type: text/html; charset=UTF-8', $from);

        wp_mail($user->user_email, $subject, $message, $headers);
    }

}

/* Default package revocation email message */
if (!function_exists('adforest_get_default_package_revocation_message')) {

    function adforest_get_default_package_revocation_message()
    {
        return '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
<td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
<div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
<table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
<tbody>
<tr>
<td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
<td class="alert" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff">
%site_name%
</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello </span><span style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;"><b>%user_name%,</b></span></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">We regret to inform you that your package "<strong>%package_name%</strong>" has been revoked by our administration team.</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Reason for revocation:</strong></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #e74c3c;">%reason%</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">This action was taken by: <strong>%admin_name%</strong></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Date: %date%</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">If you have any questions or concerns about this decision, please contact our support team.</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">%site_name% Team</p>
</td></tr></tbody></table></td></tr></tbody></table>
<div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="%site_url%">%site_name%</a></td></tr></tbody></table></div>&nbsp;</div></td><td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td></tr></tbody></table>&nbsp;';
    }

}

/* Ajax handler for clearing package revocation logs */
add_action('wp_ajax_clear_package_revocation_logs', 'adforest_clear_package_revocation_logs_func');
if (!function_exists('adforest_clear_package_revocation_logs_func')) {

    function adforest_clear_package_revocation_logs_func()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'clear_revocation_logs_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        delete_option('adforest_package_revocations');
        wp_send_json_success('Logs cleared successfully');
    }

}

/* Javascript functions to set/update checkbox */
add_action('admin_footer', 'quick_edit_javascript');
if (!function_exists('quick_edit_javascript')) {

    function quick_edit_javascript()
    {
        global $current_screen;
        $confirmationtext = __("Are you sure you want to do this?", "redux-framework");
        ?>
        <script type="text/javascript">
            jQuery(document).on('click', '.sb_trusted_user', function () {
                if (confirm('<?php echo esc_html($confirmationtext); ?>')) {
                    var user_id = jQuery(this).data('user-id');
                    var checkbox_value = 0;
                    if (jQuery(this).is(":checked")) {
                        var checkbox_value = 1;
                    }
                    if (user_id != "") {
                        var admin_ajax = jQuery("#sb-admin-ajax").val();
                        jQuery.post(admin_ajax, {
                            action: 'sb_add_trusted_user',
                            user_id: user_id,
                            checkbox_value: checkbox_value
                        }).done(function (response) { /* -- */
                        });
                    }
                } else {
                    if (jQuery(this).is(":checked")) {
                        jQuery(this).prop("checked", false);
                    } else {
                        jQuery(this).prop("checked", true);
                    }
                }
            });
        </script>
        
        <!-- Package Revocation Modal -->
        <div id="revoke-package-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 5px;">
                <div class="modal-header" style="border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px;">
                    <h2><?php echo __('Revoke Package', 'redux-framework'); ?></h2>
                    <span class="close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                </div>
                <div class="modal-body">
                    <p><strong><?php echo __('Package:', 'redux-framework'); ?></strong> <span id="package-name"></span></p>
                    <p><strong><?php echo __('User:', 'redux-framework'); ?></strong> <span id="user-name"></span></p>
                    <p><strong><?php echo __('User Email:', 'redux-framework'); ?></strong> <span id="user-email"></span></p>
                    
                    <div style="margin: 20px 0;">
                        <label for="revoke-reason" style="display: block; margin-bottom: 10px; font-weight: bold;">
                            <?php echo __('Reason for revocation (required):', 'redux-framework'); ?>
                        </label>
                        <textarea id="revoke-reason" name="revoke_reason" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px;" placeholder="<?php echo __('Please provide a reason for revoking this package...', 'redux-framework'); ?>"></textarea>
                    </div>
                    
                    <div style="margin: 20px 0;">
                        <label style="display: block; margin-bottom: 10px; font-weight: bold;">
                            <input type="checkbox" id="send-email-notification" checked>
                            <?php echo __('Send email notification to user', 'redux-framework'); ?>
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #ddd; padding-top: 20px; text-align: right;">
                    <button type="button" class="button" id="cancel-revoke"><?php echo __('Cancel', 'redux-framework'); ?></button>
                    <button type="button" class="button button-primary" id="confirm-revoke"><?php echo __('Revoke Package', 'redux-framework'); ?></button>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Package revocation functionality
                $('.revoke-package-btn').on('click', function() {
                    var $btn = $(this);
                    
                    // Prevent multiple clicks on any revoke button
                    if ($('.revoke-package-btn').hasClass('loading')) {
                        return;
                    }
                    
                    var userId = $btn.data('user-id');
                    var packageId = $btn.data('package-id');
                    var packageName = $btn.data('package-name');
                    
                    // Add loading state to all revoke buttons
                    $('.revoke-package-btn').addClass('loading').prop('disabled', true);
                    var originalText = $btn.text();
                    $btn.html('<span class="loading-spinner"></span> <?php echo __('Loading...', 'redux-framework'); ?>');
                    
                    // Get user info
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_user_info_for_revoke',
                            user_id: userId,
                            nonce: '<?php echo wp_create_nonce('revoke_package_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#user-name').text(response.data.display_name);
                                $('#user-email').text(response.data.user_email);
                                $('#package-name').text(packageName);
                                $('#revoke-package-modal').data('user-id', userId);
                                $('#revoke-package-modal').data('package-id', packageId);
                                $('#revoke-package-modal').show();
                            } else {
                                alert('<?php echo __('Error loading user information', 'redux-framework'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php echo __('Error loading user information', 'redux-framework'); ?>');
                        },
                        complete: function() {
                            // Remove loading state from all revoke buttons
                            $('.revoke-package-btn').removeClass('loading').prop('disabled', false);
                            $('.revoke-package-btn').text('<?php echo __('Revoke Package', 'redux-framework'); ?>');
                        }
                    });
                });
                
                // Close modal
                $('.close, #cancel-revoke').on('click', function() {
                    $('#revoke-package-modal').hide();
                    $('#revoke-reason').val('');
                });
                
                // Close modal when clicking outside
                $(window).on('click', function(event) {
                    if (event.target == document.getElementById('revoke-package-modal')) {
                        $('#revoke-package-modal').hide();
                        $('#revoke-reason').val('');
                    }
                });
                
                // Confirm revocation
                $('#confirm-revoke').on('click', function() {
                    var reason = $('#revoke-reason').val().trim();
                    var sendEmail = $('#send-email-notification').is(':checked');
                    var userId = $('#revoke-package-modal').data('user-id');
                    var packageId = $('#revoke-package-modal').data('package-id');
                    
                    if (!reason) {
                        alert('<?php echo __('Please provide a reason for revocation', 'redux-framework'); ?>');
                        return;
                    }
                    
                    if (!confirm('<?php echo __('Are you sure you want to revoke this package? This action cannot be undone.', 'redux-framework'); ?>')) {
                        return;
                    }
                    
                    // Disable button to prevent double submission
                    $(this).prop('disabled', true).text('<?php echo __('Processing...', 'redux-framework'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'revoke_user_package',
                            user_id: userId,
                            package_id: packageId,
                            reason: reason,
                            send_email: sendEmail,
                            nonce: '<?php echo wp_create_nonce('revoke_package_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php echo __('Package revoked successfully!', 'redux-framework'); ?>');
                                location.reload();
                            } else {
                                alert(response.data || '<?php echo __('Error revoking package', 'redux-framework'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php echo __('Error revoking package', 'redux-framework'); ?>');
                        },
                        complete: function() {
                            $('#confirm-revoke').prop('disabled', false).text('<?php echo __('Revoke Package', 'redux-framework'); ?>');
                            $('#revoke-package-modal').hide();
                            $('#revoke-reason').val('');
                        }
                    });
                });
            });
        </script>
        
        <style>
            /* Package revocation styles */
            .package-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            
            .package {
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .package-header {
                background: #f8f9fa;
                padding: 15px;
                border-bottom: 1px solid #ddd;
            }
            
            .package-header h3 {
                margin: 0;
                color: #333;
                font-size: 16px;
            }
            
            .package-body {
                padding: 15px;
            }
            
            .package-body ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .package-body li {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .package-body li:last-child {
                border-bottom: none;
            }
            
            .package-body li span:first-child {
                font-weight: 600;
                color: #555;
            }
            
            .package-body li span:last-child {
                color: #333;
            }
            
            .package-actions {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #f0f0f0;
                text-align: center;
            }
            
            .button-danger {
                background: #dc3545 !important;
                border-color: #dc3545 !important;
                color: #fff !important;
            }
            
            .button-danger:hover {
                background: #c82333 !important;
                border-color: #bd2130 !important;
            }
            
            /* Loading state styles */
            .revoke-package-btn.loading {
                opacity: 0.7;
                cursor: not-allowed;
                pointer-events: none;
            }
            
            .loading-spinner {
                display: inline-block;
                width: 12px;
                height: 12px;
                border: 2px solid #ffffff;
                border-radius: 50%;
                border-top-color: transparent;
                animation: spin 1s ease-in-out infinite;
                margin-right: 5px;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            /* Modal styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
            }
            
            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 50%;
                border-radius: 5px;
                max-width: 600px;
            }
            
            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            
            .close:hover,
            .close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }
            
            .modal-footer {
                border-top: 1px solid #ddd;
                padding-top: 20px;
                text-align: right;
            }
            
            .modal-footer .button {
                margin-left: 10px;
            }
        </style>
        <?php
    }

}