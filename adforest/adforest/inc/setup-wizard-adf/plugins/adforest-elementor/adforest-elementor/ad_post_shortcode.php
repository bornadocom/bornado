<?php
if (!function_exists('ad_post_short_base_func')) {
    function ad_post_short_base_func($params)
    {
        wp_enqueue_style('rangeslider-css');
        wp_enqueue_script('rangeslider-min');
        wp_enqueue_style('adforest-dropzone-css');
        wp_enqueue_script('adforest-dropzone-js');
        wp_enqueue_script('adforest-dt-sb', trailingslashit(get_template_directory_uri()) . '/assets/js/datepicker.min.js', false, false, true);
        // Remove Expired Packages //
        adforest_remove_expired_packages();
        // Remove Expired Packages //


        $form_title = $params['form_title'] ?? '';
        $terms_switch = $params['terms_switch'] ?? '';
        $terms_link = $params['terms_link'] ?? '';
        $terms_title = $params['terms_title'] ?? '';
        global $adforest_theme;
        $ad_post_title_limit = isset($adforest_theme['ad_post_title_limit']) ? $adforest_theme['ad_post_title_limit'] : 50;
        $user_id = get_current_user_id();
        do_action('adforest_validate_phone_verification');
        $img_size_arr = explode('-', $adforest_theme['sb_upload_size']);
        adforest_user_not_logged_in();
        $user_info = get_userdata(get_current_user_id());
        $description = '';
        $title = '';
        $tagline = "";
        $poster_name = $user_info->display_name ?? '';
        $poster_webiste = '';
        $ad_location = '';
        $is_update = '';
        $ad_map_lat = '';
        $ad_map_long = '';
        if (isset($adforest_theme['allow_lat_lon']) && $adforest_theme['allow_lat_lon'] == '1') {
            $ad_map_lat = isset($adforest_theme['sb_default_lat']) ? $adforest_theme['sb_default_lat'] : '';
            $ad_map_long = isset($adforest_theme['sb_default_long']) ? $adforest_theme['sb_default_long'] : '';
        }
        $country_states = '';
        $country_cities = '';
        $country_towns = '';
        $submit_btn_text = __("Post Ad", 'adforest-elementor');
        $is_feature_ad = 0;
        $ad_currency_value = '';
        $ad_contact_num = get_user_meta($user_id, '_sb_contact', true) ?? '';
        $ad_bidding_date = '';
        $adforest_ad_html = isset($adforest_theme['sb_ad_desc_html']) ? $adforest_theme['sb_ad_desc_html'] : false;
        $update_notice = '';
        $ad_bidding = '';
        $id = '';

        $phone_readonly = '';
        if (isset($adforest_theme['sb_change_ph']) && $adforest_theme['sb_change_ph'] != '1') {
            $phone_readonly = "readonly";
        }

        $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
        $ad_currency_taxonomies = adforest_get_ad_taxonomy_callback('ad_currency');


        $selected_packages = get_user_meta($user_id, 'adforest_ads_package_details', true);
        $adforest_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);

        $Ad_packages = '';

        $pay_per_post = isset($adforest_theme['sb_pay_per_post_option']) ? $adforest_theme['sb_pay_per_post_option'] : false;
        if (isset($pay_per_post) && $pay_per_post != 1) {
            $selected_packages_default = prepare_default_packages();

            if (is_array($selected_packages) && is_array($selected_packages_default)) {
                $selected_packages = $selected_packages_default + $selected_packages;
            } else {
                $selected_packages = $selected_packages_default;
            }

            if (is_array($selected_packages) && count($selected_packages) > 0 && !isset($_GET['id'])) {
                $Ad_packages .= "<div>";
                foreach ($selected_packages as $product_id => $packageDetails) {
                    $Pkg_radio = '<input class="form-check-input" type="radio" name="ads_package" id="ads_package_' . $product_id . '" value="' . $product_id . '" required="">';
                    $product = wc_get_product($product_id);
                    $product_title = "";
                    if ($product) {
                        $product_title = $product->get_title();
                    }

                    if ($product_id == 0) {
                        $product_title = __("Default", "adforest-elementor");
                    }
                    $Ad_packages .= '<div class="package-card">
                        <label for="ads_package_' . $product_id . '">
                            <div class="card-content">
                                <div class="r-meta">
                                    ' . $Pkg_radio . '
                                    <span class="product-title">' . $product_title . '</span>
                                </div>
                                <ul class="package-details">';
                    if (is_array($packageDetails) && count($packageDetails) > 0) {
                        foreach ($packageDetails as $detailName => $detailValue) {
                            if (in_array($detailName, ['free_ads', 'featured_ads', 'pkg_expiry_days', 'ad_expiry_days', 'featured_expiry_days'])) {
                                $custom_titles = [
                                    'free_ads' => 'Free Ads',
                                    'featured_ads' => 'Featured Ads',
                                    'pkg_expiry_days' => 'Package Expiry Days',
                                    'ad_expiry_days' => 'Simple Expiry Days',
                                    'featured_expiry_days' => 'Featured Expiry Days',
                                ];
                                $custom_title = $custom_titles[$detailName] ?? $detailName;
                                $Ad_packages .= '<li>' . $custom_title . ': ' . $detailValue . '</li>';
                            }
                        }
                    }
                    $Ad_packages .= '</ul>
                                </div>
                            </label>
                        </div>';
                }
                $Ad_packages .= "</div>";
            }
        }

        $sb_upload_limit_admin = $adforest_theme['sb_upload_limit'] ?? 0;
        $user_upload_max_images = $sb_upload_limit_admin;

        if (is_user_logged_in()) {
            $current_user = $user_id;
            if ($current_user) {
                update_user_meta($current_user, '_sb_last_login', time());
            }

            $user_packages_images = get_user_meta($current_user, '_sb_num_of_images', true);
            if (isset($user_packages_images) && $user_packages_images == '-1') {
                $user_upload_max_images = 'null';
            } else if (isset($user_packages_images) && $user_packages_images > 0) {
                $user_upload_max_images = $user_packages_images;
            }
        }

        $tags = '';
        if (isset($_GET['id'])) {
            $post_id = intval($_GET['id']);
            $post = get_post($post_id);

            if ($post) {
                $current_user_id = get_current_user_id();
                $post_author_id = $post->post_author;

                if ($current_user_id != $post_author_id && !current_user_can('administrator')) {
                    //                    adforest_show_toastr_and_redirect();
                    //                    return;
                }
            }
            $id = isset($_GET['id']) ? intval($_GET['id']) : '';
            if (isset($id) && $id != "") {
                $update_notice = '
                      <div role="alert" class="alert alert-info alert-dismissible">
                      <i class="fa fa-info-circle"></i>
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      ' . esc_html($adforest_theme['sb_ad_update_notice']) . '
                      </div>                    
                    ';
            }
            $submit_btn_text = __("Update Ad", 'adforest-elementor');
            $is_update = $id;
            if (get_post_field('post_author', $id) != get_current_user_id() && !is_super_admin(get_current_user_id())) {
                echo adforest_redirect(home_url('/'));
                exit;
            } else {
                $ad_cats_in_update = wp_get_post_terms($id, 'ad_cats', array(
                    'orderby' => 'parent',
                    'order' => 'ASC'
                ));
                $selected_categories = wp_list_pluck($ad_cats_in_update, 'term_id');
                wp_localize_script('adforest-custom', 'preselectedCategories', array(
                    'categories' => $selected_categories
                ));

                $post = get_post($id);
                $description = $post->post_content;
                $description = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $description);
                $title = esc_html($post->post_title);
                $tagline = get_post_meta($id, '_adforest_ad_tagline', true) ?? "";
                $poster_name = get_post_meta($id, '_adforest_poster_name', true);
                $ad_location = get_post_meta($id, '_adforest_ad_location', true);
                $ad_map_lat = get_post_meta($id, '_adforest_ad_map_lat', true);
                $ad_map_long = get_post_meta($id, '_adforest_ad_map_long', true);
                $ad_bidding = get_post_meta($id, '_adforest_ad_bidding', true);
                $is_feature_ad = get_post_meta($id, '_adforest_is_feature', true);
                $ad_currency_value = get_post_meta($id, '_adforest_ad_currency', true);
                $ad_contact_num = get_post_meta($id, '_adforest_poster_contact', true);
                $ad_bidding_date = get_post_meta($id, '_adforest_ad_bidding_date', true);
                $poster_webiste = get_post_meta($id, '_adforest_ad_website', true);
                $is_update = $id;

                //Countries
                $countries = adforest_get_ad_cats($id, '', true);
                $levelz = count($countries);
                /* Make cats selected on update ad */
                $ad_countries = adforest_get_cats('ad_country', 0, 0, 'post_ad');

                $country_html = '';
                if (is_array($ad_countries) && count($ad_countries) > 0) {
                    foreach ($ad_countries as $ad_country) {
                        $selected = '';
                        if ($levelz > 0 && $ad_country->term_id == $countries[0]['id']) {
                            $selected = 'selected="selected"';
                        }
                        $country_html .= '<option value="' . $ad_country->term_id . '" ' . $selected . '>' . $ad_country->name . '</option>';
                    }
                }

                if ($levelz >= 2) {
                    $ad_states = adforest_get_cats('ad_country', $countries[0]['id'], 0, 'post_ad');
                    $country_states = '';
                    if (is_array($ad_states) && count($ad_states) > 0) {
                        foreach ($ad_states as $ad_state) {
                            $selected = '';
                            if ($levelz > 0 && $ad_state->term_id == $countries[1]['id']) {
                                $selected = 'selected="selected"';
                            }
                            $country_states .= '<option value="' . $ad_state->term_id . '" ' . $selected . '>' . $ad_state->name . '</option>';
                        }
                    }
                }

                if ($levelz >= 3) {
                    $ad_country_cities = adforest_get_cats('ad_country', $countries[1]['id'], 0, 'post_ad');
                    $country_cities = '';
                    if (is_array($ad_country_cities) && count($ad_country_cities) > 0) {
                        foreach ($ad_country_cities as $ad_city) {
                            $selected = '';
                            if ($levelz > 0 && $ad_city->term_id == $countries[2]['id']) {
                                $selected = 'selected="selected"';
                            }
                            $country_cities .= '<option value="' . $ad_city->term_id . '" ' . $selected . '>' . $ad_city->name . '</option>';
                        }
                    }
                }

                if ($levelz >= 4) {
                    $ad_country_town = adforest_get_cats('ad_country', $countries[2]['id'], 0, 'post_ad');
                    $country_towns = '';
                    if (is_array($ad_country_town) && count($ad_country_town) > 0) {
                        foreach ($ad_country_town as $ad_town) {
                            $selected = '';
                            if ($levelz > 0 && $ad_town->term_id == $countries[3]['id']) {
                                $selected = 'selected="selected"';
                            }
                            $country_towns .= '<option value="' . $ad_town->term_id . '" ' . $selected . '>' . $ad_town->name . '</option>';
                        }
                    }
                }
            }
        } else {
            $pay_per_post_check = $adforest_theme['sb_pay_per_post_option'] ?? "";
            if (!$pay_per_post_check) {
                $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
                $packages = isset($packageDetails) && is_array($packageDetails) ? count($packageDetails) : 0;
                $free_ads = "";
                $pkg_expiry_days = "";
                if ($packages > 0) {
                    if (is_array($packageDetails) && count($packageDetails) > 0) {
                        foreach ($packageDetails as $key => $subArray) {
                            if (isset($subArray['free_ads'])) {
                                $free_ads = $subArray['free_ads'];
                                $pkg_expiry_days = $subArray['pkg_expiry_days'];
                            }
                        }
                    }
                }
                if (!$adforest_theme['admin_allow_unlimited_ads']) {
                    //                    adforest_check_validity($free_ads, $pkg_expiry_days);
                }
                if (!is_super_admin(get_current_user_id())) {
                    //                    adforest_check_validity($free_ads, $pkg_expiry_days);
                }
            }

            $ad_cats = adforest_get_cats('ad_cats', 0, 0, 'post_ad');
            $cats_html = '';
            if (is_array($ad_cats) && count($ad_cats) > 0) {
                foreach ($ad_cats as $ad_cat) {
                    $cats_html .= '<option value="' . $ad_cat->term_id . '" data-name = "' . $ad_cat->name . '">' . $ad_cat->name . '</option>';
                }
            }
            //Countries
            $ad_country = adforest_get_cats('ad_country', 0, 0, 'post_ad');
            $country_html = '';
            if (is_array($ad_country) && count($ad_country) > 0) {
                foreach ($ad_country as $ad_count) {
                    $country_html .= '<option value="' . $ad_count->term_id . '">' . $ad_count->name . '</option>';
                }
            }
        }

        $custom_locations_html = '';
        if (isset($adforest_theme['sb_custom_location']) && $adforest_theme['sb_custom_location'] == '1') {
            $loc_lvl_1 = __('Select Your Country', 'adforest-elementor');
            $loc_lvl_2 = __('Select Your State', 'adforest-elementor');
            $loc_lvl_3 = __('Select Your City', 'adforest-elementor');
            $loc_lvl_4 = __('Select Your Town', 'adforest-elementor');
            if (isset($adforest_theme['sb_location_titles']) && $adforest_theme['sb_location_titles'] != "") {
                $titles_array = explode("|", $adforest_theme['sb_location_titles']);

                if (count($titles_array) > 0) {
                    if (isset($titles_array[0]))
                        $loc_lvl_1 = $titles_array[0];
                    if (isset($titles_array[1]))
                        $loc_lvl_2 = $titles_array[1];
                    if (isset($titles_array[2]))
                        $loc_lvl_3 = $titles_array[2];
                    if (isset($titles_array[3]))
                        $loc_lvl_4 = $titles_array[3];
                }
            }

            $custom_locations_html = '<div class="row">
                                          <div class="col-md-6 col-lg-6 col-sm-6 col-xs-12">
                                             <label class="control-label">' . $loc_lvl_1 . ' <span class="required">*</span></label>
                                             <select class="country form-control" id="ad_country" name="ad_country" data-parsley-required="true" data-parsley-error-message="' . esc_html__('This field is required.', 'adforest-elementor') . '">
                                                <option value="">' . esc_html__('Select Option', 'adforest-elementor') . '</option>
                                                ' . $country_html . '
                                             </select>
                                             <input type="hidden" name="ad_country_id" id="ad_country_id" value="" />
                                          </div>
                            
                                          <div class="col-md-6 col-lg-6 col-sm-6 col-xs-12" id="ad_country_sub_div">
                                              <label class="control-label">' . $loc_lvl_2 . '</label>
                                              <select class="category form-control" id="ad_country_states" name="ad_country_states">
                                                  ' . $country_states . '
                                              </select>
                                          </div>
                                       </div>

                                       <div class="row">
                                          <div class="col-md-6 col-lg-6 col-sm-6 col-xs-12" id="ad_country_sub_sub_div">
                                              <label class="control-label">' . $loc_lvl_3 . '</label>
                                              <select class="category form-control" id="ad_country_cities" name="ad_country_cities">
                                                  ' . $country_cities . '
                                              </select>
                                          </div>
                            
                                          <div class="col-md-6 col-lg-6 col-sm-6 col-xs-12" id="ad_country_sub_sub_sub_div">
                                              <label class="control-label">' . $loc_lvl_4 . '</label>
                                              <select class="category form-control" id="ad_country_towns" name="ad_country_towns">
                                                  ' . $country_towns . '
                                              </select>
                                          </div>
                                       </div>';
        }

        $cats = adforest_get_ad_cats($id);
        $ad_cats = adforest_get_cats('ad_cats', 0, 0, 'post_ad');

        if (isset($cats) && is_array($cats) && count($cats) > 0) {
            echo '<input type="hidden" value="' . esc_attr($cats[0]['id']) . '"  id="ad_post_page_get_packages_cat_based" />';
        }

        $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
        $selected_package_default = prepare_default_packages();

        if (is_array($packageDetails) && is_array($selected_package_default)) {
            $packageDetails = $selected_package_default + $packageDetails;
        } else {
            $packageDetails = $selected_package_default;
        }
        $has_featured_ads = false;
        $total_featured_ads = 0;
        $default_featured_ads = get_user_meta(get_current_user_id(), '_sb_featured_ads', true);

        if (is_array($packageDetails) && !empty($packageDetails)) {
            foreach ($packageDetails as $package) {
                if (isset($package['featured_ads']) && $package['featured_ads'] > 0) {
                    $has_featured_ads = true;
                    $total_featured_ads += (int)$package['featured_ads'];
                } elseif (isset($package['featured_ads']) && $package['featured_ads'] == "-1") {
                    $has_featured_ads = true;
                    $total_featured_ads = $package['featured_ads'];
                }
            }
        }

        if (isset($default_featured_ads) && $default_featured_ads > 0 && $total_featured_ads != "-1") {
            $total_featured_ads = $total_featured_ads + intval($default_featured_ads);
        }

        $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);

        $package_details = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);

        if (isset($adforest_theme['adforest_ad_posting_mode']) && $adforest_theme['adforest_ad_posting_mode'] == 'paid_post') {
            if (!is_array($package_details) || empty($package_details) || count($package_details) === 0) {
                if (!isset($_GET['id']) && $pay_per_post != 1) {
                    if (!current_user_can('administrator')) {
                        $redirect_url = get_the_permalink($sb_packages_page);

?>
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
                        <link rel="stylesheet"
                            href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                toastr.warning('Please purchase a package first to post an ad.');
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_url($redirect_url); ?>';
                                }, 1000);
                            });
                        </script>
                    <?php
                        exit;
                    }
                    if (isset($adforest_theme['admin_allow_unlimited_ads']) && $adforest_theme['admin_allow_unlimited_ads'] != 1) {
                        $redirect_url = get_the_permalink($sb_packages_page);

                    ?>
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
                        <link rel="stylesheet"
                            href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                toastr.warning('Please purchase a package first to post an ad.');
                                setTimeout(function() {
                                    window.location.href = '<?php echo esc_url($redirect_url); ?>';
                                }, 1000);
                            });
                        </script>
        <?php
                        exit;
                    }
                }
            }
        }

        $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
        $simple_feature_html = '';

        if (isset($adforest_theme['allow_featured_on_ad']) && $adforest_theme['allow_featured_on_ad']) {
            $_sb_expire_ads = get_user_meta(get_current_user_id(), '_sb_expire_ads', true);
            $_sb_featured_ads = get_user_meta(get_current_user_id(), '_sb_featured_ads', true);
            $_package_ad_expiry_days = get_user_meta(get_current_user_id(), 'package_ad_expiry_days', true);
            if ($is_feature_ad == 0 && ($_sb_featured_ads > 0 || $_sb_featured_ads == '-1') && ($_sb_expire_ads == '-1' || strtotime($_sb_expire_ads) > time())) {
                if ($total_featured_ads > 0 || $total_featured_ads == "-1") {
                    if (get_user_meta(get_current_user_id(), '_sb_featured_ads', true) == '-1') {
                        $count_featured_ads = __('Featured ads remaining: Unlimited', 'adforest-elementor');
                    }

                    if (get_user_meta(get_current_user_id(), '_sb_featured_ads', true) > 0 || ($has_featured_ads && ($total_featured_ads > 0 || $total_featured_ads == '-1'))) {
                        $count_featured_ads = __('Featured ads remaining:', 'adforest-elementor') . ($total_featured_ads == "-1" ? __("Unlimited", 'adforest-elementor') : $total_featured_ads);
                    }

                    $feature_text = '';
                    if (isset($adforest_theme['sb_feature_desc']) && $adforest_theme['sb_feature_desc'] != "") {
                        $feature_text = $adforest_theme['sb_feature_desc'];
                    }
                    $simple_feature_html = '<div class="card make-feature">
                                                  <div class="no-padding col-md-12 col-lg-12 col-xs-12 col-sm-12">
                                                     <div class="pricing-list">
                                                        <div class="row">
                                                           <div class="col-md-12 col-sm-12 col-xs-12">
                                                              <h3>' . __('Make it featured', 'adforest-elementor') . '  <small>' . $count_featured_ads . '</small></h3>
                                                              <div class="pretty p-default p-curve make_featured_box">
                                                                    <input type="checkbox" name="sb_make_it_feature" id="sb_make_it_feature" />
                                                                    <div class="state">
                                                                        <label>' . esc_html($feature_text) . '</label>
                                                                    </div>
                                                              </div>
                                                           </div>
                                                        </div>
                                                     </div>
                                                  </div>
                                               </div>';
                } else {
                    $simple_feature_html = '<div role="alert" class="alert alert-info alert-dismissible">
                                                <button aria-label="Close" data-dismiss="alert" class="close" type="button"></button>
                                                ' . __('If you want to make it featured then please have a look on', 'adforest-elementor') . ' 
                                                <a href="' . get_the_permalink($sb_packages_page) . '" class="sb_anchor" target="_blank">
                                                ' . __('Packages. ', 'adforest-elementor') . '
                                                </a></div>';
                }
            } elseif ($has_featured_ads) {
                if ($total_featured_ads > 0 || $total_featured_ads == '-1') {
                    $count_featured_ads = __('Featured ads remaining: Unlimited', 'adforest-elementor');

                    if ($total_featured_ads > 0) {
                        $count_featured_ads = __('Featured ads remaining:', 'adforest-elementor') . $total_featured_ads;
                    }
                    $feature_text = '';
                    if (isset($adforest_theme['sb_feature_desc']) && $adforest_theme['sb_feature_desc'] != "") {
                        $feature_text = $adforest_theme['sb_feature_desc'];
                    }
                    $simple_feature_html = '<div class="card make-feature">
                                                  <div class="no-padding col-md-12 col-lg-12 col-xs-12 col-sm-12">
                                                     <div class="pricing-list">
                                                        <div class="row">
                                                           <div class="col-md-12 col-sm-12 col-xs-12">
                                                              <h3>' . __('Make it featured', 'adforest-elementor') . '  <small>' . $count_featured_ads . '</small></h3>
                                                              <div class="pretty p-default p-curve make_featured_box">
                                                                    <input type="checkbox" name="sb_make_it_feature" id="sb_make_it_feature" />
                                                                    <div class="state">
                                                                        <label>' . $feature_text . '</label>
                                                                    </div>
                                                              </div>
                                                           </div>
                                                        </div>
                                                     </div>
                                                  </div>
                                                </div>';
                }
            } else {
                $simple_feature_html = '<div role="alert" class="alert alert-info alert-dismissible">
                                            <button aria-label="Close" data-dismiss="alert" class="close" type="button"></button>
                                            ' . __('If you want to make it featured then please have a look on', 'adforest-elementor') . ' 
                                            <a href="' . get_the_permalink($sb_packages_page) . '" class="sb_anchor" target="_blank">
                                            ' . __('Packages. ', 'adforest-elementor') . '
                                            </a></div>';
            }


            if ($is_feature_ad == 1) {
                $simple_feature_html = '<div role="alert" class="alert alert-info alert-dismissible">
                                            <button aria-label="Close" data-dismiss="alert" class="close" type="button"></button>
                                            ' . __('This ad is already featured.', 'adforest-elementor') . '</div>';
            }
        }

        if (isset($adforest_theme['make_feature_paid']) && $adforest_theme['make_feature_paid']) {
            $simple_feature_html = "";
        }

        $video_logo_url = plugin_dir_url(__FILE__) . '/images/video-logo.jpg';
        $max_upload_vid_limit_opt = isset($adforest_theme['sb_upload_video_limit']) ? $adforest_theme['sb_upload_video_limit'] : "";

        $busniess_hours = apply_filters('sb_get_business_hous_post', $is_update);
        if ($busniess_hours == $is_update) {
            $busniess_hours = "";
        }

        $categorize_bid = true;
        $categorize_bid = apply_filters('adforest_make_bid_categ', $categorize_bid);

        // Visibility gate for the bidding section. The HTML is ALWAYS rendered
        // so the category-change AJAX listener (adfCheckBiddingCat) can toggle
        // it in real time. Initial visibility is driven by the selected ad
        // category — empty / non-allowed → display:none, allowed → display:block.
        // No HTML is removed from the DOM at any stage.
        $selected_cats = array();
        $adf_edit_ad_id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;
        if ($adf_edit_ad_id > 0) {
            $adf_ad_terms = wp_get_post_terms($adf_edit_ad_id, 'ad_cats', array('fields' => 'ids'));
            if (!is_wp_error($adf_ad_terms) && !empty($adf_ad_terms)) {
                $selected_cats = array_map('intval', $adf_ad_terms);
            }
        }

        $adf_show_bidding = false;
        if (!empty($selected_cats) && is_array($selected_cats)) {
            $adf_initial_cat_id = intval($selected_cats[0]);
            if (function_exists('adforest_is_bidding_allowed_for_category')) {
                $adf_show_bidding = adforest_is_bidding_allowed_for_category($adf_initial_cat_id);
            }
        }

        $adf_header_style  = $adf_show_bidding ? ''                : 'display:none;';
        $adf_panel_style   = $adf_show_bidding ? 'display:block;' : 'display:none;';

        $bidable = '';
        if (isset($adforest_theme['sb_enable_comments_offer']) && $adforest_theme['sb_enable_comments_offer'] && isset($adforest_theme['sb_enable_comments_offer_user']) && $adforest_theme['sb_enable_comments_offer_user']) {
            $adf_header_collapsed = $adf_show_bidding ? '' : 'collapsed';
            $adf_panel_show_class = $adf_show_bidding ? 'show' : '';
            $bidable .= '   <div class="card-header sub-header bidding-content ' . esc_attr($adf_header_collapsed) . '" style="' . esc_attr($adf_header_style) . '"  data-bs-toggle="collapse" data-bs-target="#biddingSection" aria-expanded="false">
                                <h2 class="mb1-1">
                                    <button class="ad_post_bidding_button btn btn-link" type="button">
                                        <span class="button-text">' . $adforest_theme['sb_enable_comments_offer_user_title'] . '</span>
                                        <i class="fa fa-angle-right"></i>
                                    </button>
                                </h2>
                            </div>
                         <div id="biddingSection" class="collapse ' . esc_attr($adf_panel_show_class) . '" style="' . esc_attr($adf_panel_style) . '" aria-labelledby="headingTwo" data-parent="#accordionExample"><div class="ad-bidding-information"><div class="row">';

            $bid_on = 'selected=selected';
            $bid_off = '';
            if ($ad_bidding == 1) {
                $bid_on = 'selected=selected';
            } else {
                $bid_off = 'selected=selected';
            }

            $bidding_options = '<option value="1" ' . $bid_on . '>' . __('ON', 'adforest-elementor') . '</option>';
            $bidding_options .= '<option value="0" ' . $bid_off . '>' . __('OFF', 'adforest-elementor') . '</option>';
            $bidable .= '<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                            <label class="control-label">' . __('Choose Bidding option', 'adforest-elementor') . '</label>
                            <select class="form-control" name="ad_bidding" id="ad_bidding" data-parsley-required="true" data-parsley-error-message="' . __('This field is required.', 'adforest-elementor') . '">
                                    ' . $bidding_options . '
                            </select>
                          </div>';
            if (isset($adforest_theme['bidding_timer']) && $adforest_theme['bidding_timer']) {
                $bidable .= '<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 biddind_div">
                               <label class="control-label">' . __('Bidding close date', 'adforest-elementor') . '<small>' . __('For countdown', 'adforest-elementor') . '</small></label>
                               <input class="form-control" placeholder="' . __('Click to select', 'adforest-elementor') . '" type="text" name="ad_bidding_date" id="ad_bidding_date"  value="' . $ad_bidding_date . '"  autocomplete="off">
                             </div>';
            }
            $bidable .= '</div></div></div>';
        }
        ?>
        <section class="adt-ad-post-section">
            <div class="loading" id="sb_loading"><?php __('Loading', 'adforest-elementor'); ?>&#8230;</div>
            <div class="container">
                <div class="row">
                    <form id="adforest-ad-post-form" method="post" novalidate>
                        <div class="col-lg-12">
                            <div class="ad-post-selected-categories"></div>
                            <div class="ad-post-tabs-wrapper">
                                <div class="nav flex-column nav-pills ad-post-tabs" id="v-pills-tab" role="tablist"
                                    aria-orientation="vertical">
                                    <h6><?php echo esc_html($form_title) ?></h6>
                                    <button class="nav-link active" id="v-pills-category-tab" data-bs-toggle="pill"
                                        data-bs-target="#v-pills-category" type="button" role="tab"
                                        aria-controls="v-pills-category" aria-selected="false">
                                        <?php echo __("All Categories", 'adforest-elementor') ?>
                                    </button>
                                    <?php
                                    $style = "";
                                    if (isset($adforest_theme['adforest_ad_posting_mode']) && $adforest_theme['adforest_ad_posting_mode'] == 'paid_post') {
                                        if (!isset($_GET['id']) && $pay_per_post != 1) {
                                            if (!current_user_can('administrator')) {
                                                $style = 'style="display: none;"';
                                            }
                                            if (
                                                isset($adforest_theme['admin_allow_unlimited_ads']) &&
                                                $adforest_theme['admin_allow_unlimited_ads'] != 1
                                            ) {
                                                $style = 'style="display: none;"';
                                            }
                                        }
                                    }

                                    // Check if AI is ready - if so, show intent fields in Categories tab (no separate tab)
                                    $show_ai_intent_in_category = false;
                                    if ( function_exists( 'adforest_is_ai_ready' ) && function_exists( 'adforest_should_show_intent_tab' ) ) {
                                        $show_ai_intent_in_category = adforest_is_ai_ready() && adforest_should_show_intent_tab();
                                    }
                                    ?>
                                    <?php // AI Intent tab removed - fields now shown in Categories tab ?>

                                    <button class="nav-link" id="v-pills-info-tab" data-tab-target="info"
                                        data-bs-toggle="pill"
                                        data-bs-target="#v-pills-info" type="button" role="tab"
                                        aria-controls="v-pills-info"
                                        aria-selected="false"
                                        <?php echo $style ?>>
                                        <?php echo __("General Information", 'adforest-elementor') ?>
                                    </button>
                                    <button class="nav-link" id="v-pills-images-tab" data-tab-target="images"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-images" type="button"
                                        role="tab"
                                        aria-controls="v-pills-images" aria-selected="false"
                                        <?php echo $style ?>>
                                        <?php echo __("Ad Details", 'adforest-elementor') ?>
                                    </button>
                                    <button class="nav-link" id="v-pills-contact-tab" data-tab-target="contact"
                                        data-bs-toggle="pill" data-bs-target="#v-pills-contact" type="button"
                                        role="tab"
                                        aria-controls="v-pills-contact" aria-selected="false"
                                        <?php echo $style ?>>
                                        <?php echo __("Contact Information", 'adforest-elementor') ?>
                                    </button>
                                </div>
                                <div class="tab-content ad-post-tab-content" id="v-pills-tabContent">
                                    <div class="tab-pane fade show active" id="v-pills-category" role="tabpanel"
                                        aria-labelledby="v-pills-category-tab" tabindex="0">
                                        <div class="ad-post-tab-box">
                                            <?php echo $update_notice; ?>
                                            <h3><?php echo __("Select Your Category", 'adforest-elementor') ?></h3>
                                            <div class="label-box">
                                                <label
                                                    for="ad_post_category_select"><?php echo __("Select Your Categories", 'adforest-elementor') ?></label>
                                                <div class="category-box-label">
                                                    <span class="required">*</span>
                                                    <span><?php echo __("Select Suitable Category For Your Ad", 'adforest-elementor') ?></span>
                                                </div>
                                            </div>
                                            <select name="ad_post_category_select" id="ad_post_category_select"
                                                class="default-select"
                                                data-parsley-required="true"
                                                data-parsley-error-message="<?php echo __('This field is required.', 'adforest-elementor'); ?>">
                                                <option value=""><?php echo __("Select Option", 'adforest-elementor'); ?>
                                                </option>
                                                <?php if (!empty($ad_categories) && !is_wp_error($ad_categories) && is_array($ad_categories)): ?>
                                                    <?php foreach ($ad_categories as $category): ?>
                                                        <option value="<?php echo esc_attr($category->term_id); ?>">
                                                            <?php echo esc_html($category->name); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            <div id="select-error"></div>
                                            <!-- Container for child category select boxes -->
                                            <div id="child-category-container"></div>
                                            <!-- Container for child category select boxes -->
                                            <input type="hidden" name="cat_temp_meta" id="cat_temp_meta">
                                            <div class="label-box package-label" style="display: none;">
                                                <label><?php echo esc_html__("Select Your Package", 'adforest-elementor') ?></label>
                                                <div class="category-box-label">
                                                    <span class="required">*</span>
                                                    <span><?php echo esc_html__("Select a package from which you want to post the Ad", 'adforest-elementor') ?></span>
                                                </div>
                                            </div>
                                            <div id="ad_post_packages_container"></div>

                                            <?php // AI Intent Fields - Shown in Categories tab when AI is enabled ?>
                                            <?php if ( $show_ai_intent_in_category ) : ?>
                                            <div id="ai-intent-fields-container" class="ai-intent-in-category" style="display: none;">
                                                <div class="ai-intent-separator" style="margin: 20px 0; padding-top: 15px; border-top: 1px solid #eee;">
                                                    <h4 style="font-size: 16px; margin-bottom: 15px; color: #333;">
                                                        <?php echo __("Ad Details for AI Suggestions", 'adforest-elementor'); ?>
                                                    </h4>
                                                    <p class="ai-intent-description" style="font-size: 13px; color: #666; margin-bottom: 15px;">
                                                        <?php echo __("These details help generate better AI-powered title and description suggestions.", 'adforest-elementor'); ?>
                                                    </p>
                                                </div>
                                                <div class="row">
                                                    <!-- Container for Ad Type field -->
                                                    <div id="ad_intent_type_container" class="col-lg-12"></div>
                                                    <!-- Container for Condition and Warranty fields -->
                                                    <div id="ad_intent_condition_warranty_container" class="row" style="width: 100%; margin: 0;"></div>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <div class="ad-post-btns-box">
                                                <button type="button"
                                                    class="next-btn adt-theme-button-2 btn-adpost-start"><?php echo __("Next", 'adforest-elementor'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-info" role="tabpanel"
                                        aria-labelledby="v-pills-info-tab" tabindex="0">
                                        <div class="ad-post-tab-box">
                                            <h3><?php echo __("General information", 'adforest-elementor'); ?></h3>
                                            <span id="ad-post-category-box"></span>
                                            <div class="row">
                                                <?php
                                                $title_cols = 6;
                                                if (isset($adforest_theme['sb_allow_tagline']) && $adforest_theme['sb_allow_tagline'] != "1") {
                                                    $title_cols = 12;
                                                }
                                                ?>
                                                <div class="col-lg-<?php echo esc_attr($title_cols) ?>">
                                                    <div class="field-box">
                                                        <label for="ad_title"
                                                            class="form-label"><?php echo __("Title", 'adforest-elementor'); ?></label>
                                                        <input type="text" class="form-control" name="ad_title"
                                                            id="ad_title"
                                                            value="<?php echo esc_attr($title) ?>"
                                                            placeholder="<?php echo esc_attr(sprintf(__('Enter title character limit (%d)', 'adforest-elementor'), $ad_post_title_limit)); ?>"
                                                            data-parsley-required="true"
                                                            data-parsley-error-message="<?php echo __('This field is required.', 'adforest-elementor'); ?>"
                                                            maxlength="<?php echo $ad_post_title_limit; ?>"
                                                            data-parsley-maxlength="<?php echo $ad_post_title_limit; ?>">
                                                    </div>
                                                </div>
                                                <?php if (isset($adforest_theme['sb_allow_tagline']) && $adforest_theme['sb_allow_tagline'] == "1") { ?>
                                                    <div class="col-lg-6">
                                                        <div class="field-box">
                                                            <label for="ad_tagline"
                                                                class="form-label"><?php echo __("Tagline", 'adforest-elementor'); ?></label>
                                                            <input type="text" class="form-control" name="ad_tagline"
                                                                value="<?php echo esc_attr($tagline) ?>"
                                                                id="ad_tagline"
                                                                placeholder="<?php echo esc_attr__("Ad Tagline", "adforest-elementor"); ?>">
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div><!-- /.row for Title/Tagline -->

                                            <?php
                                            // AI Suggestions Button - Placed after Title row, outside of grid
                                            // Visibility is controlled entirely by PHP - no JS visibility logic needed
                                            // The wizard guarantees category selection before reaching this step
                                            $show_ai_button = false;
                                            if ( function_exists( 'adforest_should_show_ai_button' ) ) {
                                                $show_ai_button = adforest_should_show_ai_button();
                                            }
                                            if ( $show_ai_button ) : ?>
                                                <div class="ai-suggestions-button-box" style="margin: 15px 0; position: relative; z-index: 100;">
                                                    <button type="button"
                                                        id="adforest-get-ai-suggestions"
                                                        class="btn ai-suggestions-btn"
                                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #fff; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;">
                                                        <i class="fa fa-magic"></i>
                                                        <?php echo esc_html__( 'Get AI Suggestions', 'adforest-elementor' ); ?>
                                                    </button>
                                                    <span id="ai-suggestions-loading" class="ai-suggestions-loading" style="display: none; margin-left: 10px; color: #667eea;">
                                                        <i class="fa fa-spinner fa-spin"></i>
                                                        <?php echo esc_html__( 'Generating AI suggestions...', 'adforest-elementor' ); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>

                                            <div class="row">
                                                <div id="ad_condition_and_warranty_box" class="row"></div>
                                                <div class="col-lg-12">
                                                    <?php
                                                    $description_allowed   = $adforest_theme['sb_allow_description'] ?? false;
                                                    $description_required  = $adforest_theme['sb_default_ad_description_required'] ?? false;
                                                    ?>

                                                    <?php if ($description_allowed) { ?>
                                                        <div class="field-box ad_description_box_ad_post">
                                                            <label for="ad_description" class="form-label">
                                                                <?php echo __("Description", 'adforest-elementor'); ?>
                                                            </label>
                                                            <textarea
                                                                class="form-control"
                                                                name="ad_description"
                                                                id="ad_description"
                                                                placeholder="<?php echo esc_attr__("Ad Description", 'adforest-elementor') ?>"
                                                                <?php if ($description_required) : ?>
                                                                data-parsley-required="true"
                                                                data-parsley-error-message="<?php echo esc_attr__('This field is required.', 'adforest-elementor'); ?>"
                                                                <?php endif; ?>><?php echo esc_textarea($description); ?></textarea>
                                                        </div>
                                                    <?php } ?>

                                                    <?php
                                                    $defult_currency = $adforest_theme['sb_currency'] ?? "";
                                                    $ad_currency_option = $adforest_theme['sb_currency_option_ad_post'] ?? "";
                                                    if ($defult_currency == "") {
                                                        if ($ad_currency_option != "") {
                                                    ?>
                                                            <div class="field-box location-box">
                                                                <label for="ad_currency"
                                                                    class="form-label"><?php echo __("Currency", "adforest-elementor"); ?></label>
                                                                <select name="ad_currency" id="ad_currency"
                                                                    class="default-select">
                                                                    <option value="<?php echo esc_attr__("Choose option", "adforest-elementor") ?>">
                                                                        <?php echo __("Select Option", "adforest-elementor"); ?>
                                                                    </option>
                                                                    <?php
                                                                    if (!empty($ad_currency_taxonomies) && !is_wp_error($ad_currency_taxonomies) && is_array($ad_currency_taxonomies)) {
                                                                        $selected_currency_value = $ad_currency_value;
                                                                        foreach ($ad_currency_taxonomies as $currency):
                                                                            $ad_currency_val = $currency->term_id . '|' . $currency->name;
                                                                            $is_selected = ($selected_currency_value === $currency->name) ? 'selected' : '';
                                                                            if ($ad_currency_value == "" && isset($adforest_theme['sb_multi_currency_default']) && $adforest_theme['sb_multi_currency_default'] != "") {
                                                                                if ($adforest_theme['sb_multi_currency_default'] == $currency->term_id) {
                                                                                    $is_selected = ' selected="selected"';
                                                                                }
                                                                            }
                                                                    ?>
                                                                            <option value="<?php echo esc_attr($ad_currency_val); ?>"
                                                                                <?php echo $is_selected; ?>>
                                                                                <?php echo esc_html($currency->name); ?>
                                                                            </option>
                                                                    <?php endforeach;
                                                                    } ?>
                                                                </select>
                                                            </div>
                                                    <?php }
                                                    } ?>
                                                    <div id="tags_and_video_link_box" class="row"></div>
                                                    <div id="cat_template_html"></div>
                                                    <?php echo $bidable; ?>
                                                    <?php echo $busniess_hours; ?>
                                                    <div class="ad-post-btns-box">
                                                        <button
                                                            class="prev-btn adt-theme-button-1"><?php echo __("Previous", 'adforest-elementor'); ?></button>
                                                        <button
                                                            class="next-btn adt-theme-button-2"><?php echo __("Next", 'adforest-elementor'); ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-images" role="tabpanel"
                                        aria-labelledby="v-pills-images-tab" tabindex="0">
                                        <div class="ad-post-tab-box">
                                            <h3><?php echo esc_html__("Media", 'adforest-elementor'); ?></h3>
                                            <div class="field-box ad_post_image_container">
                                            </div>
                                            <?php if (isset($adforest_theme['sb_allow_upload_video']) && $adforest_theme['sb_allow_upload_video']) {
                                                $max_upload_vid_size = isset($adforest_theme['sb_upload_video_mb_limit']) ? $adforest_theme['sb_upload_video_mb_limit'] : 2;
                                            ?>
                                                <div class="field-box">
                                                    <label for="ad_post_ad_video"
                                                        class="form-label"><?php echo __('Click the box below to ad Videos', 'adforest-elementor'); ?></label>
                                                    <div id="dropzone_video" class="dropzone"></div>
                                                    <small><?php echo __('upload only videos (mp4, ogg, webm) files with a max file size of' . ' ' . $max_upload_vid_size, 'adforest-elementor'); ?></small>
                                                </div>
                                            <?php } ?>
                                            <div id="custom_field_container" class="row"></div>
                                            <div class="ad-post-btns-box">
                                                <button
                                                    class="prev-btn adt-theme-button-1"><?php echo __("Previous", 'adforest-elementor'); ?></button>
                                                <button
                                                    class="next-btn adt-theme-button-2"><?php echo __("Next", 'adforest-elementor'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-contact" role="tabpanel"
                                        aria-labelledby="v-pills-contact-tab" tabindex="0">
                                        <div class="ad-post-tab-box">
                                            <h3><?php echo __("Contact Information", "adforest-elementor"); ?></h3>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="field-box">
                                                        <label for="ad_contact_number"
                                                            class="form-label"><?php echo esc_html__("Phone Number", "adforest-elementor"); ?></label>
                                                        <input type="text" class="form-control" name="ad_contact_number"
                                                            id="ad_contact_number"
                                                            value="<?php echo $ad_contact_num; ?>"
                                                            placeholder="<?php echo esc_attr__("Phone Number", "adforest-elementor"); ?>" <?php echo esc_attr($phone_readonly) ?>>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="field-box">
                                                        <label for="sb_user_name"
                                                            class="form-label"><?php echo __("Your Name", "adforest-elementor"); ?></label>
                                                        <input type="text" class="form-control" name="sb_user_name"
                                                            id="sb_user_name"
                                                            value="<?php echo esc_attr($poster_name); ?>"
                                                            placeholder="<?php echo esc_attr__("Enter Your Name", "adforest-elementor"); ?>">
                                                    </div>
                                                </div>
                                                <?php if (isset($adforest_theme['sb_allow_website_field_on_ad_post']) && $adforest_theme['sb_allow_website_field_on_ad_post'] == 1) { ?>
                                                    <div class="col-lg-6">
                                                        <div class="field-box">
                                                            <label for="ad_website"
                                                                class="form-label"><?php echo __("Website", "adforest-elementor"); ?></label>
                                                            <input type="text" class="form-control" name="ad_website"
                                                                id="ad_website"
                                                                value="<?php echo esc_attr($poster_webiste); ?>"
                                                                placeholder="<?php echo esc_attr__("Listing Website eg. http://example.com", "adforest-elementor"); ?>">
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <h3><?php echo __("Location", "adforest-elementor"); ?></h3>
                                            <div class="row">
                                                <div class="col-lg-12 col-md-6 col-sm-12">
                                                    <div class="form-group country-heading">
                                                        <?php echo $custom_locations_html; ?>
                                                    </div>
                                                </div>
                                                <!-- Container for child category select boxes -->
                                                <div id="child-country-container"></div>
                                                <!-- Container for child category select boxes -->
                                                <?php if (isset($adforest_theme['sb_allow_address']) && $adforest_theme['sb_allow_address'] == '1') {
                                                    $adf_addr_required = function_exists('adforest_is_address_required')
                                                        ? adforest_is_address_required()
                                                        : true;
                                                ?>
                                                    <div class="col-lg-12">
                                                        <div class="field-box">
                                                            <label for="ad_address"
                                                                class="form-label"><?php echo __("Address", "adforest-elementor"); ?><?php echo $adf_addr_required ? ' *' : ''; ?></label>
                                                            <div class="input-container">
                                                                <input type="text" class="form-control"
                                                                    name="ad_address"
                                                                    id="ad_address"
                                                                    placeholder="<?php echo esc_attr__("Ad Address", "adforest-elementor"); ?>"
                                                                    value="<?php echo esc_attr($ad_location); ?>"
                                                                    <?php if ($adf_addr_required) : ?>data-parsley-required="true"
                                                                    data-parsley-error-message="<?php echo esc_attr__('This field is required.', 'adforest-elementor'); ?>"<?php endif; ?>>
                                                                <ul id="google-map-btn" class="ad-post-map">
                                                                    <?php
                                                                    if (isset($adforest_theme['allow_lat_lon']) && $adforest_theme['allow_lat_lon'] == '1') { ?>
                                                                        <li>
                                                                            <a href="javascript:void(0);"
                                                                                id="your_current_location"
                                                                                title="<?php echo __('You Current Location', 'adforest-elementor') ?>">
                                                                                <?php if (isset($adforest_theme['map-setings-map-type']) && $adforest_theme['map-setings-map-type'] == "google_map") { ?>
                                                                                    <i class="fa fa-crosshairs"></i>
                                                                                <?php } ?>
                                                                            </a>
                                                                        </li>
                                                                    <?php
                                                                    } ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <?php
                                                $map_handler = new AdforestMapHandler($adforest_theme, $is_update ?? false, $ad_map_lat ?? '', $ad_map_long ?? '');
                                                $map_handler->render();
                                                ?>
                                                <?php
                                                if (isset($adforest_theme['adforest_ad_posting_mode']) && $adforest_theme['adforest_ad_posting_mode'] == 'paid_post') {
                                                    if (!((isset($_GET['id']) && $_GET['id'] !== '') || (isset($pay_per_post) && $pay_per_post == 1))) {
                                                        echo $simple_feature_html;
                                                    }
                                                }
                                                ?>
                                                <?php if ($terms_switch) { ?>
                                                    <div class="col-lg-6">
                                                        <div class="skin-minimal check-detail">
                                                            <ul class="list">
                                                                <li>
                                                                    <div class="pretty p-default p-curve">
                                                                        <input type="checkbox" id="minimal-checkbox-1"
                                                                            name="minimal-checkbox-1"
                                                                            data-parsley-required="true"
                                                                            data-parsley-error-message="<?php echo __('Please accept terms and conditions.', 'adforest-elementor'); ?>" />
                                                                        <div class="state">
                                                                            <label><?php echo esc_html__('I agree to ', 'adforest-elementor'); ?>
                                                                                <?php $terms_link_url = $terms_link['url'] ?? ''; ?>
                                                                                <a href="<?php echo esc_url($terms_link_url); ?>">
                                                                                    <?php echo esc_html($terms_title); ?>
                                                                                </a>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="ad-post-btns-box">
                                                <button
                                                    class="prev-btn adt-theme-button-1"><?php echo __("Previous", "adforest-elementor"); ?></button>
                                                <button type="submit" id="ad_post_submit_button"
                                                    class="adt-theme-button-2"><?php echo esc_html($submit_btn_text) ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="adforest_max_upload_reach"
                            value="<?php echo __('Maximum upload limit reached', 'adforest-elementor'); ?>" />
                        <input type="hidden" id="dictDefaultMessage"
                            value="<?php echo __('Drop your files here or click to upload.', 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictFallbackMessage"
                            value="<?php echo __('Your browser does not support drag\'n\'drop file uploads.', 'adforest-elementor') ?>" />
                        <input type="hidden" id="adforest-post-token"
                            value="<?php echo wp_create_nonce('sb_post_secure'); ?>" />
                        <input type="hidden" id="dictFallbackText"
                            value="<?php echo __('Please use the fallback form below to upload your files like in the olden days.', 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictFileTooBig"
                            value="<?php echo __('File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.', 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictInvalidFileType"
                            value="<?php echo __('You can\'t upload files of this type.', 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictResponseError"
                            value="<?php echo __('Server responded with {{statusCode}} code.', 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictCancelUpload"
                            value="<?php echo __('Cancel upload', 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictCancelUploadConfirmation"
                            value="<?php echo __('Are you sure you want to cancel this upload?', 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictRemoveFile"
                            value="<?php echo __('Remove file', 'adforest-elementor') ?>" />
                        <input type="hidden" id="is_update" value="<?php echo __($is_update, 'adforest-elementor') ?>" />
                        <input type="hidden" id="dictMaxFilesExceeded"
                            value="<?php echo __('You can not upload any more files.', 'adforest-elementor') ?>" />
                        <input type="hidden" id="sb_upload_limit"
                            value="<?php echo esc_attr($user_upload_max_images) ?>" />
                        <input type="hidden" id="max_upload_images" value="<?php echo esc_attr(
                                                                                sprintf(
                                                                                    __('You can only upload %d images.', 'adforest-elementor'),
                                                                                    $user_upload_max_images
                                                                                )
                                                                            ); ?>" />
                        <input type="hidden" id="adforest_packages_page"
                            value="<?php echo get_the_permalink($adforest_packages_page); ?>" />
                        <input type="hidden" id="ad_limit_msg"
                            value="<?php echo __('Your package has been used or expired, please purchase new.', 'adforest-elementor'); ?>" />
                        <input type="hidden" id="ad_post_id_on_update"
                            value="<?php echo $id; ?>" />
                        <input type="hidden" id="adforest_ad_html" value="<?php echo $adforest_ad_html; ?>"
                            <input type="hidden" id="video_logo_url" value="<?php echo $video_logo_url; ?>"
                            <input type="hidden" id="sb_upload_video_limit" value="<?php echo $max_upload_vid_limit_opt; ?>"
                            </form>
                </div>
            </div>
        </section>
        <input type="hidden" id="select_cat_first"
            value="<?php echo esc_html__('Please Select Category first', 'adforest-elementor') ?>" />
<?php
    }
}
?>