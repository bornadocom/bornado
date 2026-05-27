<?php
add_action('wp_ajax_save_selected_category', 'adforest_save_selected_category');
add_action('wp_ajax_nopriv_save_selected_category', 'adforest_save_selected_category');

if (!function_exists('adforest_save_selected_category')) {
    function adforest_save_selected_category()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'save_selected_category_nonce')) {
            wp_send_json_error(esc_html__('Security check failed.', 'adforest'));
        }

        global $adforest_theme;

        // Check if AI intent fields should show in category tab (AI ready AND intent enabled)
        // When true: Ad Type, Condition, Warranty fields go to the Categories tab
        // When false: These fields follow legacy behavior (in General Info tab or template)
        $ai_intent_in_category = false;
        if ( function_exists( 'adforest_is_ai_ready' ) && function_exists( 'adforest_should_show_intent_tab' ) ) {
            $ai_intent_in_category = adforest_is_ai_ready() && adforest_should_show_intent_tab();
        }

        if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
            $category_id = sanitize_text_field($_POST['category_id']);
            $post_id = sanitize_text_field($_POST['post_id']) ?? "";
            $category_name = sanitize_text_field($_POST['category_name']);
            $category_template_id = get_term_meta($category_id, "_sb_category_template", true);

            $encoded_meta = "";
            if (isset($category_template_id)) {
                $category_template = get_term($category_template_id);
                if (isset($category_template->term_id)) {
                    $encoded_meta = get_term_meta($category_template->term_id, '_sb_dynamic_form_fields', true);
                }
            }

            $decoded_meta = "";
            if (isset($encoded_meta) && $encoded_meta !== "") {
                $decoded_meta = base64_decode($encoded_meta, true);
                // Validate base64 decode was successful
                if ($decoded_meta === false) {
                    $decoded_meta = "";
                }
            }

            $template_meta_array = [];
            if (isset($decoded_meta) && $decoded_meta !== "") {
                $template_meta_array = json_decode($decoded_meta, true);
                // Validate JSON decode was successful
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $template_meta_array = [];
                }
            }

            $html = [];
            $cat_template_img_allowed = false;
            if (isset($template_meta_array)) {
                $html = adforest_load_ad_post_fields($template_meta_array, $post_id, $ai_intent_in_category);
                if(isset($template_meta_array['_sb_default_cat_image_show']) && $template_meta_array['_sb_default_cat_image_show'] == 1) {
                    $cat_template_img_allowed = true;
                }
            }

            $no_template_selected = "";
            if ((!is_array($html) || empty($html['html'])) && (!is_array($template_meta_array) || count($template_meta_array) === 0)) {
                $no_template_selected = "no_template_selected";
            }

            $default_template_html = "";
            $default_template_warranty_and_condition = '';
            $default_template_image_container = '';
            $default_template_on = false;
            // AI Intent Fields (shown in Categories tab when AI enabled)
            $default_intent_ad_type_html = '';
            $default_intent_condition_warranty_html = '';

            if ($no_template_selected == "no_template_selected") {
                if (isset($adforest_theme['sb_default_adpost_template_on']) && $adforest_theme['sb_default_adpost_template_on'] == "1") {
                    $price = "";
                    $price_to = "";
                    $price_from = "";
                    $ad_price_type = "";
                    $ad_type = "";
                    $ad_yvideo = "";
                    $tags = "";
                    $ad_condition_value = "";
                    $ad_warranty_value = "";
                    $default_template_on = true;
                    $tags_allowed = true;
                    $video_allowed = true;
                    if ($post_id != "") {
                        $price = get_post_meta($post_id, '_adforest_ad_price', true);
                        $price_to = get_post_meta($post_id, '_adforest_ad_price_to', true);
                        $price_from = get_post_meta($post_id, '_adforest_ad_price_from', true);
                        $ad_price_type = get_post_meta($post_id, '_adforest_ad_price_type', true);
                        $ad_type = get_post_meta($post_id, '_adforest_ad_type', true);
                        $ad_yvideo = get_post_meta($post_id, '_adforest_ad_yvideo', true);
                        $tags_array = wp_get_object_terms($post_id, 'ad_tags', array('fields' => 'names'));
                        $tags = implode(',', $tags_array);
                        $ad_condition_value = get_post_meta($post_id, '_adforest_ad_condition', true);
                        $ad_warranty_value = get_post_meta($post_id, '_adforest_ad_warranty', true);
                        $ad_post_package = get_post_meta($post_id, '_adforest_ad_post_package', true);
                        $user_packages = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
                        $user_packages_default = prepare_default_packages();

                        if (is_array($user_packages) && is_array($user_packages_default)) {
                            $user_packages = $user_packages_default + $user_packages;
                        } else {
                            $user_packages = $user_packages_default;
                        }

                        if (isset($user_packages[$ad_post_package])) {
                            $tags_pkg = isset($user_packages[$ad_post_package]['allow_tags']) ? $user_packages[$ad_post_package]['allow_tags'] : "";
                            $video = isset($user_packages[$ad_post_package]['video_links']) ? $user_packages[$ad_post_package]['video_links'] : "";

                            $tags_allowed = ($tags_pkg === 'yes') ? true : false;
                            $video_allowed = ($video === 'yes') ? true : false;
                        }
                    }

                    $video_link_on = 1;
                    $ad_tags_on = 1;
                    $ad_images_on = 1;
                    $ad_condition_on = 1;
                    $ad_warranty_on = 1;

                    if (isset($adforest_theme['sb_default_adpost_template_ad_type']) && $adforest_theme['sb_default_adpost_template_ad_type'] == "1") {
                        $ad_type_terms = adforest_get_ad_taxonomy_callback('ad_type');
                        $is_required = isset($template_meta_array['_sb_default_cat_ad_type_required']) && $template_meta_array['_sb_default_cat_ad_type_required'] == 1 ? "true" : "false";

                        $ad_type_options = '';
                        if (!empty($ad_type_terms) && !is_wp_error($ad_type_terms) && is_array($ad_type_terms)) {
                            foreach ($ad_type_terms as $term) {
                                $ad_type_val = $term->term_id . '|' . $term->name;
                                $is_checked = ($ad_type === $term->name) ? 'checked' : '';
                                $ad_type_options .= '<li>
                                                        <input type="radio" name="ad_type" id="' . esc_attr($term->slug) . '" value="' . esc_attr($ad_type_val) . '" data-parsley-required="' . esc_attr($is_required) . '" data-parsley-error-message="' . __('Please Select the ad type.', 'adforest') . '" ' . $is_checked . ' />
                                                        <label class="dont_change_color" for="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</label>
                                                    </li>';
                            }
                        } else {
                            $ad_type_options .= '<p>' . __('No ad types available', 'adforest') . '</p>';
                        }

                        $ad_type_field_html = '<div class="col-lg-12">
                                                    <div class="field-box">
                                                        <label for="ad_type" class="form-label">' . __("Ad Type", "adforest") . '</label>
                                                        <div id="ad_type">
                                                            <ul class="select-user-type ad_type_list_adpost">
                                                                ' . $ad_type_options . '
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>';

                        // If AI enabled, put Ad Type in category tab intent container
                        // Otherwise, put it in the default template HTML (legacy behavior)
                        if ( $ai_intent_in_category ) {
                            $default_intent_ad_type_html = $ad_type_field_html;
                        } else {
                            $default_template_html .= $ad_type_field_html;
                        }
                    }

                    if (isset($adforest_theme['sb_default_adpost_template_price_type']) && $adforest_theme['sb_default_adpost_template_price_type'] == "1") {
                        $sb_price_types_strings = array('Fixed' => __('Fixed', 'adforest'), 'Negotiable' => __('Negotiable', 'adforest'), 'on_call' => __('Price on call', 'adforest'), 'auction' => __('Auction', 'adforest'), 'free' => __('Free', 'adforest'), 'no_price' => __('No price', 'adforest'), 'range' => __('Range', 'adforest'));
                        $is_required = isset($template_meta_array['_sb_default_cat_price_type_required']) && $template_meta_array['_sb_default_cat_price_type_required'] == 1 ? "true" : "false";

                        $new_types_array = array();
                        if (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) > 0) {
                            $sb_price_types = $adforest_theme['sb_price_types'];
                        } else if (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) == 0 && isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] == "") {
                            $sb_price_types = array('Fixed', 'Negotiable', 'on_call', 'auction', 'free', 'no_price', 'range');
                        } else {
                            $sb_price_types = array();
                        }

                        if (is_array($sb_price_types) && count($sb_price_types) > 0) {
                            foreach ($sb_price_types as $p_val) {
                                $new_types_array[$p_val] = $sb_price_types_strings[$p_val];
                            }
                        }

                        if (isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] != "") {
                            $sb_price_types_more_array = explode('|', $adforest_theme['sb_price_types_more']);
                            if (isset($sb_price_types_more_array) && is_array($sb_price_types_more_array) && count($sb_price_types_more_array)) {
                                foreach ($sb_price_types_more_array as $p_type_more) {
                                    $new_types_array[str_replace(' ', '_', $p_type_more)] = $p_type_more;
                                }
                            }
                        }

                        $price_type_options = '';
                        if (is_array($new_types_array) && count($new_types_array) > 0) {
                            foreach ($new_types_array as $key => $value) {
                                $selected = $key === $ad_price_type ? ' selected' : '';
                                $price_type_options .= '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($value) . '</option>';
                            }
                        }
                        $default_template_html .= '<div class="row" style="display: flex; flex-wrap: wrap;"><div class="col-6" style="padding-right: 10px;">
                                                    <div class="field-box">
                                                        <label for="ad_post_price_type" class="form-label">' . __("Price Type", "adforest") . '</label>
                                                        <select id="ad_post_price_type" name="ad_post_price_type" class="default-select" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required . ', 'adforest') . '">
                                                            <option value="">' . __("Select Option", 'adforest') . '</option>
                                                            ' . $price_type_options . '
                                                        </select>
                                                    </div>
                                                </div>';
                    }

                    if (isset($adforest_theme['sb_default_adpost_template_price']) && $adforest_theme['sb_default_adpost_template_price'] == "1") {
                        $default_template_html .= '<div class="col-6" style="padding-left: 10px;">
                                                    <div class="field-box ad_price_container">
                                                        <label for="ad_price" class="form-label"> ' . __("Price", "adforest") . '</label>
                                                        <input type="text" class="form-control" name="ad_price" id="ad_price" value="' . $price . '"
                                                            placeholder="' . esc_attr__("Enter Your Price", "adforest") . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required . ', 'adforest') . '">
                                                    </div>
                                                    <div class="field-box price_range_container" style="display: none;">
                                                        <label class="form-label">' . __("Price Range", "adforest") . '</label>
                                                        <div style="display: flex; gap: 10px;">
                                                            <input type="text" class="form-control" name="ad_price_from" id="ad_price_from" value="' . $price_from . '" placeholder="' . esc_attr("From") . '">
                                                            <input type="text" class="form-control" name="ad_price_to" id="ad_price_to" value="' . $price_to . '" placeholder="' . esc_attr("To") . '">
                                                        </div>
                                                    </div>
                                                </div></div>';
                    }

                    if (isset($adforest_theme['sb_default_adpost_template_videoURL']) && $adforest_theme['sb_default_adpost_template_videoURL'] == "1" && $video_allowed) {
                        $default_template_html .= '<div class="col-lg-6 ad_yvideo_container">
                                                    <div class="field-box">
                                                        <label for="ad_yvideo" class="form-label">' . __("Video Link", "adforest") . '</label>
                                                        <input class="form-control" name="ad_yvideo"
                                                               type="text"
                                                               id="ad_yvideo"
                                                               value="' . esc_attr($ad_yvideo) . '"
                                                               data-parsley-error-message="' . __("Should be valid youtube video url.", "adforest") . '"
                                                               data-parsley-pattern="/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/">
                                                    </div>
                                                </div>';
                    }

                    if (isset($adforest_theme['sb_default_adpost_template_tags']) && $adforest_theme['sb_default_adpost_template_tags'] == "1" && $tags_allowed) {
                        $default_template_html .= '<div class="col-lg-12 ad_tags_container">
                                                    <div class="field-box">
                                                        <div class="tags">
                                                            <label for="tags" class="control-label">
                                                                ' . __("Tags", "adforest") . '
                                                                <small>' . __("Comma separated", "adforest") . '</small>
                                                            </label>
                                                            <input class="form-control" name="tags" id="tags" value="' . esc_attr($tags) . '">
                                                        </div>
                                                    </div>
                                                </div>';
                    }

                    if (isset($adforest_theme['sb_default_adpost_template_images']) && $adforest_theme['sb_default_adpost_template_images'] == "1") {
                        $img_size_arr = explode('-', $adforest_theme['sb_upload_size']);
                        $display_size = $img_size_arr[1];
                        ob_start();
                        ?>
                        <label for="img_dropzone"
                               class="form-label"><?php echo esc_html__("Ad Images", 'adforest'); ?></label>
                        <div id="img_dropzone" class="dropzone"></div>
                        <small><?php echo esc_html( sprintf( __('Maximum file size: %s', 'adforest'), $display_size ) ); ?></small>
                        <?php
                        $default_template_image_container .= ob_get_clean();
                    }

                    if (isset($adforest_theme['sb_default_adpost_template_condition']) && $adforest_theme['sb_default_adpost_template_condition'] == "1") {
                        $ad_condition_taxonomies = adforest_get_ad_taxonomy_callback('ad_condition');
                        ob_start();
                        ?>
                        <div class="col-lg-6 ad_condition_container">
                            <div class="field-box">
                                <label for="condition"
                                       class="form-label"><?php echo __("Condition", 'adforest'); ?></label>
                                <div class="switch-btns-box" id="condition">
                                    <ul class="select-user-type ad_type_list_adpost">
                                        <?php if (!empty($ad_condition_taxonomies) && !is_wp_error($ad_condition_taxonomies) && is_array($ad_condition_taxonomies)): ?>
                                            <?php
                                            $selected_condition_value = $ad_condition_value;
                                            foreach ($ad_condition_taxonomies as $condition):
                                                $ad_condition_val = $condition->term_id . '|' . $condition->name;
                                                $is_checked = ($selected_condition_value === $condition->name);
                                                ?>
                                                <li>
                                                    <input type="radio" name="ad_condition"
                                                           id="check-condition-<?php echo esc_attr($condition->slug); ?>"
                                                           value="<?php echo esc_attr($ad_condition_val); ?>"
                                                           <?php echo $is_checked ? 'checked="checked"' : ''; ?>
                                                           data-parsley-error-message="<?php echo __('This field is required . ', 'adforest') ?>"/>
                                                    <label class="dont_change_color"
                                                           for="check-condition-<?php echo esc_attr($condition->slug); ?>">
                                                        <?php echo esc_html($condition->name); ?>
                                                    </label>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php
                        $condition_field_html = ob_get_clean();

                        // If AI enabled, put in category tab intent container; otherwise legacy behavior
                        if ( $ai_intent_in_category ) {
                            $default_intent_condition_warranty_html .= $condition_field_html;
                        } else {
                            $default_template_warranty_and_condition .= $condition_field_html;
                        }
                    }

                    if (isset($adforest_theme['sb_default_adpost_template_warranty']) && $adforest_theme['sb_default_adpost_template_warranty'] == "1") {
                        $ad_warranty_taxonomies = adforest_get_ad_taxonomy_callback('ad_warranty');
                        ob_start();
                        ?>
                        <div class="col-lg-6 ad_warranty_container">
                            <div class="field-box">
                                <label for="warranty"
                                       class="form-label"><?php echo __("Warranty", 'adforest'); ?></label>
                                <div class="switch-btns-box" id="warranty">
                                    <ul class="select-user-type ad_type_list_adpost">
                                        <?php if (!empty($ad_warranty_taxonomies) && !is_wp_error($ad_warranty_taxonomies) && is_array($ad_warranty_taxonomies)): ?>
                                            <?php
                                            $selected_warranty_value = $ad_warranty_value;
                                            foreach ($ad_warranty_taxonomies as $warranty):
                                                $ad_warranty_val = $warranty->term_id . '|' . $warranty->name;
                                                $is_checked = ($selected_warranty_value === $warranty->name);
                                                ?>
                                                <li>
                                                    <input type="radio" name="ad_warranty"
                                                           id="check-warranty-<?php echo esc_attr($warranty->slug); ?>"
                                                           value="<?php echo esc_attr($ad_warranty_val); ?>"
                                                           <?php echo $is_checked ? 'checked="checked"' : ''; ?>
                                                           data-parsley-error-message="<?php echo __('This field is required . ', 'adforest') ?>"/>
                                                    <label class="dont_change_color"
                                                           for="check-warranty-<?php echo esc_attr($warranty->slug); ?>">
                                                        <?php echo esc_html($warranty->name); ?>
                                                    </label>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php
                        $warranty_field_html = ob_get_clean();

                        // If AI enabled, put in category tab intent container; otherwise legacy behavior
                        if ( $ai_intent_in_category ) {
                            $default_intent_condition_warranty_html .= $warranty_field_html;
                        } else {
                            $default_template_warranty_and_condition .= $warranty_field_html;
                        }
                    }
                }
            }

            // Build AI intent fields from custom template if available
            $intent_ad_type_html = isset($html['intent_ad_type_html']) ? $html['intent_ad_type_html'] : '';
            $intent_condition_warranty_html = isset($html['intent_condition_warranty_html']) ? $html['intent_condition_warranty_html'] : '';

            // For default templates, use the default intent variables if set
            if ( $default_template_on && isset( $default_intent_ad_type_html ) ) {
                $intent_ad_type_html = $default_intent_ad_type_html;
            }
            if ( $default_template_on && isset( $default_intent_condition_warranty_html ) ) {
                $intent_condition_warranty_html = $default_intent_condition_warranty_html;
            }

            wp_send_json_success(array('id' => $category_id,
                'name' => $category_name,
                "category_template_html" => isset($html['html']) ? $html['html'] : "",
                "custom_fields_html" => isset($html['custom_fields']) ? $html['custom_fields'] : "",
                "condition_and_value_fields" => isset($html['condition_and_value_fields']) ? $html['condition_and_value_fields'] : "",
                "tags_and_video_fields" => isset($html['tags_and_video_fields']) ? $html['tags_and_video_fields'] : "",
                "image_field" => isset($html['image_field']) ? $html['image_field'] : "",
                'no_template_selected' => $no_template_selected,
                'default_template_html' => $default_template_html,
                'default_template_on' => $default_template_on,
                'more_options' => [
                    'video_link_on' => $video_link_on,
                    'ad_tags_on' => $ad_tags_on,
                    'ad_images_on' => $ad_images_on,
                    'ad_condition_on' => $ad_condition_on,
                    'ad_warranty_on' => $ad_warranty_on,
                    'default_template_warranty_and_condition' => $default_template_warranty_and_condition,
                    'default_template_image_container' => $default_template_image_container,
                ],
                'cat_template_img_allowed' => $cat_template_img_allowed,
                // AI Intent fields - only populated when AI is enabled (shown in category tab)
                'ai_intent_in_category' => $ai_intent_in_category,
                'intent_ad_type_html' => $intent_ad_type_html,
                'intent_condition_warranty_html' => $intent_condition_warranty_html,
            ));
        } else {
            wp_send_json_error(esc_html__('Category ID or name not set.', 'adforest'));
        }
        wp_die();
    }
}

if (!function_exists('adforest_load_ad_post_fields')) {
    function adforest_load_ad_post_fields($template_meta_array, $post_id = "", $ai_intent_in_category = false)
    {
        if (!is_array($template_meta_array) || count($template_meta_array) === 0) {
            return [
                "html" => [],
                "custom_fields" => [],
                "condition_and_value_fields" => [],
                "tags_and_video_fields" => [],
                "image_field" => [],
                "intent_ad_type_html" => [],
                "intent_condition_warranty_html" => [],
            ];
        }

//        error_log("META ARRAY: " . print_r($template_meta_array, true));
        global $adforest_theme;
        $show_price_type = isset($template_meta_array['_sb_default_cat_price_type_show']) ? $template_meta_array['_sb_default_cat_price_type_show'] : "";
        $show_ad_type = isset($template_meta_array['_sb_default_cat_ad_type_show']) ? $template_meta_array['_sb_default_cat_ad_type_show'] : "";
        $show_condition = isset($template_meta_array['_sb_default_cat_condition_show']) ? $template_meta_array['_sb_default_cat_condition_show'] : "";
        $show_warranty = isset($template_meta_array['_sb_default_cat_warranty_show']) ? $template_meta_array['_sb_default_cat_warranty_show'] : "";
        $show_tags = isset($template_meta_array['_sb_default_cat_tags_show']) ? $template_meta_array['_sb_default_cat_tags_show'] : "";
        $show_video = isset($template_meta_array['_sb_default_cat_video_show']) ? $template_meta_array['_sb_default_cat_video_show'] : "";
        $show_image = isset($template_meta_array['_sb_default_cat_image_show']) ? $template_meta_array['_sb_default_cat_image_show'] : "";
        $html = "";
        $intent_ad_type_html = "";       // AI: Ad Type field (shown in category tab when AI enabled)
        $intent_condition_warranty_html = "";  // AI: Condition & Warranty fields (shown in category tab when AI enabled)
        $price = "";
        $price_to = "";
        $price_from = "";
        $ad_price_type = "";
        $ad_type = "";
        $ad_condition_value = '';
        $ad_warranty_value = '';
        $tags = '';
        $ad_yvideo = '';
        $tags_allowed = true;
        $video_allowed = true;

        if ($post_id != "") {
            $price = get_post_meta($post_id, '_adforest_ad_price', true);
            $price_to = get_post_meta($post_id, '_adforest_ad_price_to', true);
            $price_from = get_post_meta($post_id, '_adforest_ad_price_from', true);
            $ad_price_type = get_post_meta($post_id, '_adforest_ad_price_type', true);
            $ad_type = get_post_meta($post_id, '_adforest_ad_type', true);
            $ad_condition_value = get_post_meta($post_id, '_adforest_ad_condition', true);
            $ad_warranty_value = get_post_meta($post_id, '_adforest_ad_warranty', true);
            $tags_array = wp_get_object_terms($post_id, 'ad_tags', array('fields' => 'names'));
            $tags = implode(',', $tags_array);
            $ad_yvideo = get_post_meta($post_id, '_adforest_ad_yvideo', true);
            $ad_post_package = get_post_meta($post_id, '_adforest_ad_post_package', true);
            // error_log("ad_post_package: " . print_r($ad_post_package, true));
            $user_packages = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
            $user_packages_default = prepare_default_packages();

            if (is_array($user_packages) && is_array($user_packages_default)) {
                $user_packages = $user_packages_default + $user_packages;
            } else {
                $user_packages = $user_packages_default;
            }

            if (isset($user_packages[$ad_post_package])) {
                $tags_pkg = isset($user_packages[$ad_post_package]['allow_tags']) ? $user_packages[$ad_post_package]['allow_tags'] : "";
                $video = isset($user_packages[$ad_post_package]['video_links']) ? $user_packages[$ad_post_package]['video_links'] : "";

                $tags_allowed = ($tags_pkg === 'yes') ? true : false;
                $video_allowed = ($video === 'yes') ? true : false;
            }
        }

        if ($show_ad_type == 1 || $show_ad_type == '1') {
            $ad_type_terms = adforest_get_ad_taxonomy_callback('ad_type');
            $is_required = isset($template_meta_array['_sb_default_cat_ad_type_required']) && $template_meta_array['_sb_default_cat_ad_type_required'] == 1 ? "true" : "false";

            $ad_type_options = '';
            if (!empty($ad_type_terms) && !is_wp_error($ad_type_terms) && is_array($ad_type_terms)) {
                foreach ($ad_type_terms as $term) {
                    $ad_type_val = $term->term_id . '|' . $term->name;
                    $is_checked = ($ad_type === $term->name) ? 'checked' : '';
                    $ad_type_options .= '<li>
                                            <input type="radio" name="ad_type" id="' . esc_attr($term->slug) . '" value="' . esc_attr($ad_type_val) . '" data-parsley-required="' . esc_attr($is_required) . '" data-parsley-error-message="' . __('Please Select the ad type.', 'adforest') . '" ' . $is_checked . ' />
                                            <label class="dont_change_color" for="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</label>
                                        </li>';
                }
            } else {
                $ad_type_options .= '<p>' . __('No ad types available', 'adforest') . '</p>';
            }

            $ad_type_field_html = '
                <div class="col-lg-12">
                    <div class="field-box">
                        <label for="ad_type" class="form-label">' . __("Ad Type", "adforest") . '</label>
                        <div id="ad_type">
                            <ul class="select-user-type ad_type_list_adpost">
                                ' . $ad_type_options . '
                            </ul>
                        </div>
                    </div>
                </div>
            ';

            // If AI enabled, put Ad Type in category tab intent container; otherwise legacy behavior
            if ( $ai_intent_in_category ) {
                $intent_ad_type_html .= $ad_type_field_html;
            } else {
                $html .= $ad_type_field_html;
            }

        }

        if (($show_price_type == 1 || $show_price_type == '1')) {
            $sb_price_types_strings = array('Fixed' => __('Fixed', 'adforest'), 'Negotiable' => __('Negotiable', 'adforest'), 'on_call' => __('Price on call', 'adforest'), 'auction' => __('Auction', 'adforest'), 'free' => __('Free', 'adforest'), 'no_price' => __('No price', 'adforest'), 'range' => __("Range", "adforest"));
            $is_required = isset($template_meta_array['_sb_default_cat_price_type_required']) && $template_meta_array['_sb_default_cat_price_type_required'] == 1 ? "true" : "false";

            $new_types_array = array();
            if (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) > 0) {
                $sb_price_types = $adforest_theme['sb_price_types'];
            } else if (isset($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) == 0 && isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] == "") {
                $sb_price_types = array('Fixed', 'Negotiable', 'on_call', 'auction', 'free', 'no_price', 'range');
            } else {
                $sb_price_types = array();
            }

            if (is_array($sb_price_types) && count($sb_price_types) > 0) {
                foreach ($sb_price_types as $p_val) {
                    $new_types_array[$p_val] = $sb_price_types_strings[$p_val];
                }
            }

            if (isset($adforest_theme['sb_price_types_more']) && $adforest_theme['sb_price_types_more'] != "") {
                $sb_price_types_more_array = explode('|', $adforest_theme['sb_price_types_more']);
                if (isset($sb_price_types_more_array) && is_array($sb_price_types_more_array) && count($sb_price_types_more_array)) {
                    foreach ($sb_price_types_more_array as $p_type_more) {
                        $new_types_array[str_replace(' ', '_', $p_type_more)] = $p_type_more;
                    }
                }
            }

            $price_type_options = '';
            if (is_array($new_types_array) && count($new_types_array) > 0) {
                foreach ($new_types_array as $key => $value) {
                    $selected = $key === $ad_price_type ? ' selected' : '';
                    $price_type_options .= '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($value) . '</option>';
                }
            }

            $html .= '
            <div class="row" style="display: flex; flex-wrap: wrap;">
                <div class="col-6" style="padding-right: 10px;">
                    <div class="field-box">
                        <label for="ad_post_price_type" class="form-label">' . __("Price Type", "adforest") . '</label>
                        <select id="ad_post_price_type" name="ad_post_price_type" class="default-select" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required . ', 'adforest') . '">
                            <option value="">' . __("Select Option", 'adforest') . '</option>
                            ' . $price_type_options . '
                        </select>
                    </div>
                </div>';
        }

        $show_price = isset($template_meta_array['_sb_default_cat_price_show']) ? $template_meta_array['_sb_default_cat_price_show'] : "";

        if ($show_price == 1 || $show_price == '1') {
            $is_required = isset($template_meta_array['_sb_default_cat_price_required']) && $template_meta_array['_sb_default_cat_price_required'] == 1 ? "true" : "false";
            $html .= '
                <div class="col-6" style="padding-left: 10px;">
                    <div class="field-box ad_price_container">
                        <label for="ad_price" class="form-label"> ' . __("Price", "adforest") . '</label>
                        <input type="text" class="form-control" name="ad_price" id="ad_price" value="' . $price . '"
                            placeholder="' . esc_attr__("Enter Your Price", "adforest") . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required . ', 'adforest') . '">
                    </div>
                    <div class="field-box price_range_container" style="display: none;">
                        <label class="form-label">' . __("Price Range", "adforest") . '</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" class="form-control" name="ad_price_from" id="ad_price_from" value="' . $price_from . '" placeholder="' . esc_attr("From") . '">
                            <input type="text" class="form-control" name="ad_price_to" id="ad_price_to" value="' . $price_to . '" placeholder="' . esc_attr("To") . '">
                        </div>
                    </div>
                </div>
            </div>';
        }

        // ========================= FOR CUSTOM FIELDS ========================= //
        $custom_fields_html = "";
        if (isset($template_meta_array['_sb_dynamic_form_types']) && count($template_meta_array['_sb_dynamic_form_types']) > 0) {
            for ($i = 0; $i < count($template_meta_array['_sb_dynamic_form_types']); $i++) {
                $type = $template_meta_array['_sb_dynamic_form_types'][$i];
                $is_active = $template_meta_array['_sb_dynamic_form_status'][$i] == "1";
                $is_required = $template_meta_array['_sb_dynamic_form_requires'][$i] == "1" ? 'true' : 'false';
                $title = $template_meta_array['_sb_dynamic_form_titles'][$i];
                $slug = $template_meta_array['_sb_dynamic_form_slugs'][$i];
                $key = '_adforest_tpl_field_' . $slug;
                $slugs = 'cat_template_field[' . $key . ']';
                $default_value = $template_meta_array['_sb_dynamic_form_values'][$i];
                $columns = $template_meta_array['_sb_dynamic_form_columns'][$i];
                $saved_value = "";
                if ($post_id != "") {
                    $saved_value = get_post_meta($post_id, $key, true);
                }

                if ($is_active) {
                    switch ($type) {
                        case "1": // Input - Textfield
                            $custom_fields_html .= '<div class="col-lg-' . esc_attr($columns) . '">
                                                        <div class="field-box">
                                                            <label for="' . esc_attr($slugs) . '" class="form-label">' . esc_html($title) . '</label>
                                                            <input type="text" class="form-control" name="' . esc_attr($slugs) . '" id="' . esc_attr($slugs) . '" value="' . esc_attr($saved_value) . '" placeholder="' . esc_attr($default_value) . '">
                                                        </div>
                                                    </div>';
                            break;
                        case "2": // Options - Select Box
                            $options = explode('|', $default_value);
                            $custom_fields_html .= '<div class="col-lg-' . esc_attr($columns) . '">
                                                            <div class="field-box">
                                                                <label for="' . esc_attr($slugs) . '" class="form-label">' . esc_html($title) . '</label>
                                                                <select class="default-select" name="' . esc_attr($slugs) . '" id="' . esc_attr($slugs) . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required.', 'adforest') . '">';

                            $custom_fields_html .= '<option value="" disabled selected>' . __('-- Select Option --', 'adforest') . '</option>';

                            if (is_array($options) && count($options) > 0) {
                                foreach ($options as $option) {
                                    $selected = ($option == $saved_value) ? 'selected' : '';
                                    $custom_fields_html .= '<option value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html($option) . '</option>';
                                }
                            }

                            $custom_fields_html .= '</select>
                                                            </div>
                                                        </div>';
                            break;                            
                        case "3": // Options - Check Box
                            $options = explode('|', $default_value);
                            $cleaned_value = str_replace(['[', ']', '"'], '', $saved_value);
                            if (is_array($cleaned_value)) {
                                $saved_values = is_array($cleaned_value[0]) ? $cleaned_value : json_decode($cleaned_value[0], true);
                            } else {
                                $saved_values = explode(',', $cleaned_value);
                            }

                            $custom_fields_html .= '
                            <div class="col-lg-' . esc_attr($columns) . '">
                                <div class="field-box">
                                    <label class="form-label">' . esc_html($title) . '</label>';

                            if (isset($options) && is_array($options) && count($options) > 0) {
                                foreach ($options as $option) {
                                    $checked = '';
                                    foreach ($saved_values as $saved_value) {
                                        if ($option === $saved_value) {
                                            $checked = 'checked';
                                            break;
                                        }
                                    }
                                    $custom_fields_html .= '<div class="form-check">
                                                                <div class="pretty p-default p-curve">
                                                                    <input type="checkbox" name="' . esc_attr($slugs) . '[]" value="' . esc_attr($option) . '" id="' . esc_attr($slugs . '_' . $option) . '" ' . $checked . ' />
                                                                    <div class="state">
                                                                        <label>' . esc_html($option) . '</label>
                                                                    </div>
                                                                </div>
                                                            </div>';
                                }
                            }

                            $custom_fields_html .= '</div></div>';
                            break;
                        case "4": // Date - Input
                            $custom_fields_html .= '<div class="col-lg-' . esc_attr($columns) . '">
                                                        <div class="field-box">
                                                            <label for="' . esc_attr($slugs) . '" class="form-label">' . esc_html($title) . '</label>
                                                            <input type="date" class="form-control" name="' . esc_attr($slugs) . '" id="' . esc_attr($slugs) . '" value="' . esc_attr($saved_value) . '" placeholder="' . esc_attr($default_value) . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required.', 'adforest') . '">
                                                        </div>
                                                    </div>';
                            break;
                        case "5": // Website URL
                            $custom_fields_html .= '<div class="col-lg-' . esc_attr($columns) . '">
                                                        <div class="field-box">
                                                            <label for="' . esc_attr($slugs) . '" class="form-label">' . esc_html($title) . '</label>
                                                            <input type="url" class="form-control" name="' . esc_attr($slugs) . '" id="' . esc_attr($slugs) . '" value="' . esc_attr($saved_value) . '" placeholder="' . esc_attr($default_value) . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required.', 'adforest') . '">
                                                        </div>
                                                    </div>';
                            break;
                        case "6": // Input - Number Range
                            $range_config = explode('|', $default_value);
                            $slider_min = isset($range_config[0]) ? $range_config[0] : 0;
                            $slider_max = isset($range_config[1]) ? $range_config[1] : 100;
                            $slider_step = isset($range_config[2]) ? $range_config[2] : 1;

                            if (is_array($saved_value)) {
                                $selected_min = isset($saved_value[0]) ? $saved_value[0] : $slider_min;
                                $selected_max = isset($saved_value[1]) ? $saved_value[1] : $slider_max;
                            } else {
                                $range_current = !empty($saved_value) ? json_decode($saved_value, true) : [];
                                $selected_min = isset($range_current[0]) ? (int)$range_current[0] : $slider_min;
                                $selected_max = isset($range_current[1]) ? (int)$range_current[1] : $slider_max;
                            }

                            $custom_fields_html .= '<div class="col-lg-' . esc_attr($columns) . '">
                                                        <div class="field-box">
                                                            <label for="' . esc_attr($slugs) . '" class="form-label">' . esc_html($title) . '</label>
                                                            <span class="price-slider-value">
                                                                <span id="' . esc_attr($slugs) . '_min_display">' . esc_html($selected_min) . '</span> - 
                                                                <span id="' . esc_attr($slugs) . '_max_display">' . esc_html($selected_max) . '</span>
                                                            </span>
                                                            <div class="range-slider">
                                                                <input type="text"
                                                                       class="adt-ads-range-slider"
                                                                       id="' . esc_attr($slugs) . '_slider"
                                                                       data-min="' . esc_attr($slider_min) . '"
                                                                       data-max="' . esc_attr($slider_max) . '"
                                                                       data-step="' . esc_attr($slider_step) . '"
                                                                       data-from="' . esc_attr($selected_min) . '"
                                                                       data-to="' . esc_attr($selected_max) . '"
                                                                       value=""/>
                                                            </div>
                                                            <div class="extra-controls">
                                                                <input type="number" class="adt-ads-input-from form-control"
                                                                       name="' . esc_attr($slugs) . '[min]"
                                                                       id="' . esc_attr($slugs) . '_min"
                                                                       value="' . esc_attr($selected_min) . '">
                                                                <div>&#9866;</div>
                                                                <input type="number" class="adt-ads-input-to form-control"
                                                                       name="' . esc_attr($slugs) . '[max]"
                                                                       id="' . esc_attr($slugs) . '_max"
                                                                       value="' . esc_attr($selected_max) . '">
                                                            </div>
                                                        </div>
                                                    </div>';
                            break;

                        case "7": // Select Colors
                            $custom_fields_html .= '<div class="col-lg-' . esc_attr($columns) . '">
                                                        <div class="field-box">
                                                            <label for="' . esc_attr($slugs) . '" class="form-label">' . esc_html($title) . '</label>
                                                            <input type="color" class="form-control" name="' . esc_attr($slugs) . '" id="' . esc_attr($slugs) . '" value="' . esc_attr($saved_value) . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required.', 'adforest') . '">
                                                        </div>
                                                    </div>';
                            break;
                        case "8": // Options - Radio Buttons
                            $options = explode('|', $default_value);
                            $custom_fields_html .= '
                <div class="col-lg-' . esc_attr($columns) . '">
                    <div class="field-box">
                        <label class="form-label">' . esc_html($title) . '</label>';
                            if (is_array($options) && count($options) > 0) {
                                foreach ($options as $option) {
                                    $checked = ($option == $saved_value) ? 'checked' : '';
                                    $custom_fields_html .= '<div class="form-check">
                                                                <input class="form-check-input" type="radio" name="' . esc_attr($slugs) . '" value="' . esc_attr($option) . '" id="' . esc_attr($slugs . '_' . $option) . '" data-parsley-required="' . $is_required . '" data-parsley-error-message="' . __('This field is required.', 'adforest') . '" ' . $checked . '>
                                                                <label class="form-check-label" for="' . esc_attr($slugs . '_' . $option) . '">
                                                                    ' . esc_html($option) . '
                                                                </label>
                                                            </div>';
                                }
                            }
                            $custom_fields_html .= '</div>
                                                </div>
                                                ';
                            break;
                        case "9": // Options - Advance Check Box
                            $options = explode('|', $default_value);
                            $cleaned_value = str_replace(['[', ']', '"'], '', $saved_value);
                            if (is_array($cleaned_value)) {
                                $saved_values = is_array($cleaned_value[0]) ? $cleaned_value : json_decode($cleaned_value[0], true);
                            } else {
                                $saved_values = explode(',', $cleaned_value);
                            }

                            $custom_fields_html .= '
                            <div class="col-lg-' . esc_attr($columns) . '">
                                <div class="field-box">
                                    <label class="form-label">' . esc_html($title) . '</label>';

                            if (isset($options) && is_array($options) && count($options) > 0) {
                                foreach ($options as $option) {
                                    $checked = '';
                                    foreach ($saved_values as $saved_value) {
                                        if ($option === $saved_value) {
                                            $checked = 'checked';
                                            break;
                                        }
                                    }
                                    $custom_fields_html .= '<div class="form-check">
                                                                <div class="pretty p-default p-curve">
                                                                    <input type="checkbox" name="' . esc_attr($slugs) . '[]" value="' . esc_attr($option) . '" id="' . esc_attr($slugs . '_' . $option) . '" ' . $checked . ' />
                                                                    <div class="state">
                                                                        <label>' . esc_html($option) . '</label>
                                                                    </div>
                                                                </div>
                                                            </div>';
                                }
                            }

                            $custom_fields_html .= '</div></div>';
                            break;
                    }
                }
            }
        }
        // ========================= FOR CUSTOM FIELDS ========================= //

        // =========================Condition and Warranty HTML ======================= //
        $ad_warranty_taxonomies = adforest_get_ad_taxonomy_callback('ad_warranty');
        $ad_condition_taxonomies = adforest_get_ad_taxonomy_callback('ad_condition');
        $condition_and_value_fields = '';

        if ($show_condition == 1 || $show_condition == '1') {
            $is_required = isset($template_meta_array['_sb_default_cat_condition_required']) && $template_meta_array['_sb_default_cat_condition_required'] == 1 ? "true" : "false";

            ob_start();
            ?>
            <div class="col-lg-6 ad_condition_container">
                <div class="field-box">
                    <label for="condition"
                           class="form-label"><?php echo __("Condition", 'adforest'); ?></label>
                    <div class="switch-btns-box" id="condition">
                        <ul class="select-user-type ad_type_list_adpost">
                            <?php if (!empty($ad_condition_taxonomies) && !is_wp_error($ad_condition_taxonomies) && is_array($ad_condition_taxonomies)): ?>
                                <?php
                                $selected_condition_value = $ad_condition_value;
                                foreach ($ad_condition_taxonomies as $condition):
                                    $ad_condition_val = $condition->term_id . '|' . $condition->name;
                                    $is_checked = ($selected_condition_value === $condition->name);
                                    ?>
                                    <li>
                                        <input type="radio" name="ad_condition"
                                               id="check-condition-<?php echo esc_attr($condition->slug); ?>"
                                               value="<?php echo esc_attr($ad_condition_val); ?>"
                                               <?php echo $is_checked ? 'checked="checked"' : ''; ?>
                                               data-parsley-required="<?php echo esc_attr($is_required); ?>"
                                               data-parsley-error-message="<?php echo __('This field is required . ', 'adforest') ?>"/>
                                        <label class="dont_change_color"
                                               for="check-condition-<?php echo esc_attr($condition->slug); ?>">
                                            <?php echo esc_html($condition->name); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
            $condition_field_html = ob_get_clean();

            // If AI enabled, put in category tab intent container; otherwise legacy behavior
            if ( $ai_intent_in_category ) {
                $intent_condition_warranty_html .= $condition_field_html;
            } else {
                $condition_and_value_fields .= $condition_field_html;
            }
        }

        if ($show_warranty == 1 || $show_warranty == '1') {
            $is_required = isset($template_meta_array['_sb_default_cat_warranty_required']) && $template_meta_array['_sb_default_cat_warranty_required'] == 1 ? "true" : "false";

            ob_start();
            ?>
            <div class="col-lg-6 ad_warranty_container">
                <div class="field-box">
                    <label for="warranty"
                           class="form-label"><?php echo __("Warranty", 'adforest'); ?></label>
                    <div class="switch-btns-box" id="warranty">
                        <ul class="select-user-type ad_type_list_adpost">
                            <?php if (!empty($ad_warranty_taxonomies) && !is_wp_error($ad_warranty_taxonomies) && is_array($ad_warranty_taxonomies)): ?>
                                <?php
                                $selected_warranty_value = $ad_warranty_value;
                                foreach ($ad_warranty_taxonomies as $warranty):
                                    $ad_warranty_val = $warranty->term_id . '|' . $warranty->name;
                                    $is_checked = ($selected_warranty_value === $warranty->name);
                                    ?>
                                    <li>
                                        <input type="radio" name="ad_warranty"
                                               id="check-warranty-<?php echo esc_attr($warranty->slug); ?>"
                                               value="<?php echo esc_attr($ad_warranty_val); ?>"
                                               <?php echo $is_checked ? 'checked="checked"' : ''; ?>
                                               data-parsley-required="<?php echo esc_attr($is_required); ?>"
                                               data-parsley-error-message="<?php echo __('This field is required . ', 'adforest') ?>"/>
                                        <label class="dont_change_color"
                                               for="check-warranty-<?php echo esc_attr($warranty->slug); ?>">
                                            <?php echo esc_html($warranty->name); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
            $warranty_field_html = ob_get_clean();

            // If AI enabled, put in category tab intent container; otherwise legacy behavior
            if ( $ai_intent_in_category ) {
                $intent_condition_warranty_html .= $warranty_field_html;
            } else {
                $condition_and_value_fields .= $warranty_field_html;
            }
        }
        // =========================Condition and Warranty HTML ======================= //

        // ========================= Tags and Video Links ========================= //
        $tags_and_video_fields = '';
        $user_id = get_current_user_id();
        $adforest_allow_video_links = get_user_meta($user_id, '_sb_video_links', true);
        $adforest_allow_tags = get_user_meta($user_id, '_sb_allow_tags', true);
        if (isset($show_tags) && $show_tags == '1' && $tags_allowed) {
                ob_start();
                ?>
                <div class="col-lg-12 ad_tags_container">
                    <div class="field-box">
                        <div class="tags">
                            <label for="tags" class="control-label">
                                <?php echo __('Tags', 'adforest'); ?>
                                <small><?php echo __('Comma separated', 'adforest'); ?></small>
                            </label>
                            <input class="form-control" name="tags" id="tags" value="<?php echo esc_attr($tags); ?>">
                        </div>
                    </div>
                </div>
                <?php
                $tags_and_video_fields .= ob_get_clean();
        }

        if (isset($show_video) && $show_video == '1') {
            if ($video_allowed) {
                ob_start();
                ?>
                <div class="col-lg-6 ad_yvideo_container">
                    <div class="field-box">
                        <label for="ad_yvideo" class="form-label"><?php echo __('Video Link', 'adforest'); ?></label>
                        <input class="form-control" name="ad_yvideo"
                               type="text"
                               id="ad_yvideo"
                               value="<?php echo esc_attr($ad_yvideo); ?>"
                               data-parsley-error-message="<?php echo __('Should be valid youtube video url.', 'adforest'); ?>"
                               data-parsley-pattern="/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/">
                    </div>
                </div>
                <?php
                $tags_and_video_fields .= ob_get_clean();
            }
        }
        // ========================= Tags and Video Links ========================= //

        $image_field_html = '';
        if(isset($show_image) && $show_image == '1') {
            $img_size_arr = explode('-', $adforest_theme['sb_upload_size']);
            $display_size = $img_size_arr[1];
            ob_start();
            ?>
            <label for="img_dropzone"
                   class="form-label"><?php echo esc_html__("Ad Images", 'adforest'); ?></label>
            <div id="img_dropzone" class="dropzone"></div>
            <small><?php echo esc_html__("Maximum file size: " . $display_size, 'adforest'); ?></small>
            <?php
            $image_field_html .= ob_get_clean();
        }

        return [
            "html" => $html,
            "custom_fields" => $custom_fields_html,
            "condition_and_value_fields" => $condition_and_value_fields,
            "tags_and_video_fields" => $tags_and_video_fields,
            "image_field" => $image_field_html,
            // AI Intent fields - only populated when AI is enabled (shown in category tab)
            "intent_ad_type_html" => $intent_ad_type_html,
            "intent_condition_warranty_html" => $intent_condition_warranty_html,
        ];
    }
}

add_action('wp_ajax_get_child_countries', 'get_child_countries');
add_action('wp_ajax_nopriv_get_child_countries', 'get_child_countries');

if (!function_exists('get_child_countries')) {
    function get_child_countries()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'get_child_countries_nonce')) {
            wp_send_json_error(esc_html__('Security check failed.', 'adforest'));
        }

        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        if ($parent_id === 0) {
            wp_send_json_error('Invalid parent ID.');
            return;
        }

        $args = array(
            'taxonomy' => 'ad_country',
            'parent' => $parent_id,
            'hide_empty' => false,
        );

        $categories = get_terms($args);

        if (is_wp_error($categories)) {
            wp_send_json_error($categories->get_error_message());
        } elseif (empty($categories)) {
            wp_send_json_error(__('No child countries found.', 'adforest'));
        } else {
            wp_send_json_success($categories);
        }

        wp_die();
    }
}