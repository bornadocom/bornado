<?php

global $adforest_theme, $template;
$page_template = basename($template);


$custom_cat_flag = FALSE;
$term_id = '';
$dyDTId = "";
if (isset($_GET['cat_id']) && $_GET['cat_id'] != "" && is_numeric($_GET['cat_id'])) {
    $custom_cat_flag = TRUE;
    $term_id = $_GET['cat_id'];
} else {


    if ($page_template == 'taxonomy-ad_cats.php' || $page_template == 'taxonomy-ad_country.php') {
        global $pass_term_id;
        $custom_cat_flag = TRUE;
        $term_id = $pass_term_id;
    } else {

        if (isset($adforest_theme['sb_default_dynamic_template_on']) && $adforest_theme['sb_default_dynamic_template_on'] == true) {
            if (isset($adforest_theme['sb_default_dynamic_template']) && $adforest_theme['sb_default_dynamic_template'] != "") {
                $dyDTId = $adforest_theme['sb_default_dynamic_template'];
            }
        }

        $template_IDz = $dyDTId;
        if ($template_IDz) {
            $custom_cat_flag = TRUE;
            $term_id = $template_IDz;
        }
    }
}


if ($custom_cat_flag && $term_id != '') {

    $result = adforest_dynamic_templateID($term_id);
    $templateID = get_term_meta($result, '_sb_dynamic_form_fields', true);

    if (isset($templateID) && $templateID != "") {
        $formData = sb_dynamic_form_data($templateID);
        $customHTML = '';
        $custom_id_count = 0;
        foreach ($formData as $r) {
            if (isset($r['types']) && trim($r['types']) != "") {
                $custom_id_count++;

                $custom_id = "customField{$custom_id_count}";
                if (isset($r['status']) && $r['status'] == 0) {
                    continue;
                }
                if (isset($r['types']) && $r['types'] == 5) {
                    continue;
                }

                $in_search = (isset($r['in_search']) && $r['in_search'] == "yes") ? 1 : 0;

                if ($r['titles'] != "" && $r['slugs'] != "" && $in_search == 1) {
                    $rand_ids = rand(123, 1234567);

                    global $wp;
                    $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
                    $sb_search_page = isset($sb_search_page) && $sb_search_page != '' ? get_the_permalink($sb_search_page) : 'javascript:void(0)';
                    $sb_search_page = apply_filters('adforest_category_widget_form_action', $sb_search_page);

                    $open_widget_cus = false;
                    $open_collapse = '';

                    if (isset($instance['open_widget']) && $instance['open_widget']) {
                        $open_widget_cus = true;
                        $open_collapse = ' in';
                    }

                    $customHTML .= '<div class="accordion-item">
                                      <div class="accordion-header" role="tab" id="heading-' . $rand_ids . '">
                                         <button style="padding: 0 !important;" class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapse' . $custom_id . '" aria-expanded="false" aria-controls="panelsStayOpen-collapse' . $custom_id . '-collapseFive" fdprocessedid="1bxzi">' . esc_html($title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title'])) . ' ' . esc_html($r['titles']) . '</button>
                                         </div>
                                      <form method="get" action="' . adforest_return_echo($sb_search_page) . '" class="custom-search-form">
                                      <div id="panelsStayOpen-collapse' . $custom_id . '" class="panel-collapse collapse' . $open_collapse . '" role="tabpanel" aria-labelledby="heading-' . $rand_ids . '" aria-expanded="' . $open_widget_cus . '"><div style="padding: 15px" class="panel-body"><div class="skin-minimal">';
                    $fieldName = "custom[" . esc_attr($r['slugs']) . "]";
                    $fieldValue = '';
                    $fieldValue = (isset($_GET["custom"]) && isset($_GET['custom'][esc_attr($r['slugs'])])) ? $_GET['custom'][esc_attr($r['slugs'])] : '';
                    if (isset($r['types']) && $r['types'] == 1) {
                        $customHTML .= '<div class="search-widget"><input placeholder="' . esc_attr($r['titles']) . '" name="' . $fieldName . '" value="' . $fieldValue . '" type="text"><button type="submit"><i class="fa fa-search"></i></button></div>';
                    }

                    if (isset($r['types']) && $r['types'] == 2) {
                        $options = '';
                        if (isset($r['values']) && $r['values'] != '') {
                            $varArrs = @explode("|", $r['values']);
                            $options .= '<option value="0">' . esc_html__("Select Option", "adforest") . '</option>';
                            foreach ($varArrs as $varArr) {
                                $selected = ($fieldValue == $varArr) ? 'selected="selected"' : '';
                                $options .= '<option value="' . esc_attr($varArr) . '" ' . $selected . '>' . esc_html($varArr) . '</option>';
                            }
                        }
                        $customHTML .= '<select name="' . $fieldName . '" class="custom-search-select default-select submit_on_select">' . $options . '</select>';
                    }

                    if (isset($r['types']) && $r['types'] == 3) {
                        $options = '';
                        if (isset($r['values']) && $r['values'] != '') {
                            $fieldName = "custom[" . esc_attr($r['slugs']) . "]";
                            $varArrs = @explode("|", $r['values']);

                            $loop = 1;
                            foreach ($varArrs as $val) {

                                $checked = '';
                                if (isset($fieldValue) && $fieldValue != "") {
                                    if (isset($fieldValue) && is_array($fieldValue)) {
                                        $checked = (in_array($val, $fieldValue)) ? 'checked="checked"' : '';
                                    } else {
                                        $checked = ($val == $fieldValue) ? 'checked="checked"' : '';
                                    }
                                }

                                $options .= '<li><input type="checkbox" class="submit_on_select" id="minimal-checkbox-' . $loop . '"  value="' . esc_html($val) . '" ' . $checked . ' name="' . $fieldName . '"><label for="minimal-checkbox-' . $loop . '">' . esc_html($val) . '</label></li>';
                                $loop++;
                            }
                        }
                        $customHTML .= '<div class="skin-minimal"><ul class="list">' . $options . '</ul></div>';
                    }

                    if (isset($r['types']) && $r['types'] == 9) {
                        $options = '';
                        if (isset($r['values']) && $r['values'] != '') {
                            $fieldName = "custom[" . esc_attr($r['slugs']) . "]";
                            $varArrs = @explode("|", $r['values']);

                            $loop = 1;
                            foreach ($varArrs as $val) {

                                $checked = '';
                                if (isset($fieldValue) && $fieldValue != "") {
                                    if (isset($fieldValue) && is_array($fieldValue)) {
                                        $checked = (in_array($val, $fieldValue)) ? 'checked="checked"' : '';
                                    } else {
                                        $checked = ($val == $fieldValue) ? 'checked="checked"' : '';
                                    }
                                }

                                $options .= '<li><input type="checkbox" id="minimal-checkbox-' . $loop . '"  value="' . esc_html($val) . '" ' . $checked . ' name="' . $fieldName . '[]"><label for="minimal-checkbox-' . $loop . '">' . esc_html($val) . '</label></li>';
                                $loop++;
                            }
                        }
                        $customHTML .= '<div class="skin-minimal"><ul class="list">' . $options . '</ul></div>';
                    }

                    if (isset($r['types']) && $r['types'] == 4) {

                        $minVal = (isset($_GET["min_custom"]) && isset($_GET['min_custom'][esc_attr($r['slugs'])])) ? $_GET['min_custom'][esc_attr($r['slugs'])] : '';
                        $maxVal = (isset($_GET["max_custom"]) && isset($_GET['max_custom'][esc_attr($r['slugs'])])) ? $_GET['max_custom'][esc_attr($r['slugs'])] : '';

                        $btn_cls = 'btn btn-theme btn-sm btn-block';
                        $customHTML .= '<div class="clearfix"></div><div class="search-widget col-md-12 no-padding"><input placeholder="' . esc_attr(__("From", "adforest")) . '" name="min_' . $fieldName . '" value="' . $minVal . '" type="text" class="dynamic-form-date-fields"><button type="submit" onclick="return false;"><i class="fa fa-calendar"></i></button></div><div class="search-widget col-md-12 no-padding"><input placeholder="' . esc_attr(__("To", "adforest")) . '" name="max_' . $fieldName . '" value="' . $maxVal . '" type="text" class="dynamic-form-date-fields"><button type="submit" onclick="return false;"><i class="fa fa-calendar"></i></button></div>
				<div class="col-md-12 no-padding"><button type="submit" class="' . esc_attr($btn_cls) . '"><i class="fa fa-search"></i></button></div>';
                    }
                    /* Type 6 is for URL */
                    if (isset($r['types']) && $r['types'] == 6) {
                        if (function_exists('wp_enqueue_script')) {
                            wp_enqueue_script('rangeslider-min');
                        }
                        if (function_exists('wp_enqueue_style')) {
                            wp_enqueue_style('rangeslider-css');
                        }

                        /* Values */
                        $varArrs = @explode("|", $r['values']);
                        $hiddenMin = (isset($varArrs[0]) && (int)$varArrs[0]) ? $varArrs[0] : 0;
                        $hiddenMax = (isset($varArrs[1]) && (int)$varArrs[1]) ? $varArrs[1] : 1000000;
                        $hiddenStp = (isset($varArrs[2]) && (int)$varArrs[2]) ? $varArrs[2] : 1000;

                        $minVal = (isset($_GET["min_custom"]) && isset($_GET['min_custom'][esc_attr($r['slugs'])])) ? $_GET['min_custom'][esc_attr($r['slugs'])] : $hiddenMin;
                        $maxVal = (isset($_GET["max_custom"]) && isset($_GET['max_custom'][esc_attr($r['slugs'])])) ? $_GET['max_custom'][esc_attr($r['slugs'])] : $hiddenMax;

                        $slider_base = sanitize_html_class($r['slugs']) . '_' . $rand_ids;
                        $slider_id = $slider_base . '_slider';

                        $customHTML .= '<div class="adt-range-slider">';
                        $customHTML .= '<span class="price-slider-value"><strong>' . __('Range', 'adforest') . ': </strong> 
					<span id="' . esc_attr($slider_base) . '_min_display">' . esc_html($minVal) . '</span> - <span id="' . esc_attr($slider_base) . '_max_display">' . esc_html($maxVal) . '</span> </span>';
                        $customHTML .= '<input type="text" class="adt-ads-range-slider" id="' . esc_attr($slider_id) . '" data-min="' . esc_attr($hiddenMin) . '" data-max="' . esc_attr($hiddenMax) . '" data-step="' . esc_attr($hiddenStp) . '" data-from="' . esc_attr($minVal) . '" data-to="' . esc_attr($maxVal) . '" data-display-min="#' . esc_attr($slider_base) . '_min_display" data-display-max="#' . esc_attr($slider_base) . '_max_display" data-input-min="#' . esc_attr($slider_base) . '_min" data-input-max="#' . esc_attr($slider_base) . '_max" />';
                        $customHTML .= '<div class="extra-controls margin-top-10">
					<input type="text" class="form-control adt-range-input-min" name="min_' . $fieldName . '" value="' . esc_attr($minVal) . '" placeholder="' . __('min', 'adforest') . '" id="' . esc_attr($slider_base) . '_min" />
					<div>&#9866;</div>
					<input type="text" class="form-control adt-range-input-max" name="max_' . $fieldName . '" value="' . esc_attr($maxVal) . '" placeholder="' . __('max', 'adforest') . '" id="' . esc_attr($slider_base) . '_max" />
					</div>';
                        $customHTML .= '<button type="submit" class="btn btn-theme btn-sm btn-block margin-top-10"><i class="fa fa-search"></i> ' . __('Search', 'adforest') . '</button>';
                        $customHTML .= '</div>';
                    }
                    /* Type 7 is for colors */
                    if (isset($r['types']) && $r['types'] == 7) {

                        $options = $myColors = '';
                        if (isset($r['values']) && $r['values'] != '') {
                            $varArrs = @explode("|", $r['values']);

                            $rand_name = rand(1111, 99999);

                            $loop = $loop_count = 1;
                            $colorsCss = '';
                            $count_more = (isset($varArrs) && count((array)$varArrs) > 6) ? ' more ' : ' no-more ';
                            foreach ($varArrs as $val) {
                                $checked = '';
                                if (isset($fieldValue) && $fieldValue != "") {
                                    $checked = ($val == $fieldValue) ? 'checked="checked"' : '';
                                }

                                $colors = @explode(":", $val);

                                $code = (isset($colors[0]) && $colors[0] != "") ? $colors[0] : '';
                                $name = (isset($colors[1]) && $colors[1] != "") ? $colors[1] : '';

                                if ($code != "" && $name != "") {


                                    $is_checked = ($fieldValue == $code) ? 'checked="checked"' : '';
                                    $options .= '<div class="color-picker__item">
										<input id="input-' . $loop_count . '-' . $rand_name . '" type="radio" class="color-picker__input" name="' . $fieldName . '" value="' . esc_attr($code) . '" ' . $is_checked . ' onclick="this.form.submit()"/>
										<label for="input-' . $loop_count . '-' . $rand_name . '" class="color-picker__color  color-picker__color--' . $loop_count . '-' . $rand_name . '  ' . $count_more . '"></label>
									  </div>';
                                    $colorsCss .= '.color-picker__color--' . $loop_count . '-' . $rand_name . ' {  background: ' . $code . '; }';
                                    $loop_count++;
                                    $loop++;
                                }
                            }
                            $myColors = '<style>' . $colorsCss . '</style>';
                        }
                        $btn_cls = 'btn btn-theme btn-sm btn-block';
                        $customHTML .= '<div class="skin-minimal theme-input-colors">' . $options . '</div>' . $myColors;
                    }
                    /* Type 8 is for radio buttons */
                    if (isset($r['types']) && $r['types'] == 8) {
                        $options = '';
                        if (isset($r['values']) && $r['values'] != '') {
                            $varArrs = @explode("|", $r['values']);

                            $loop = 1;
                            foreach ($varArrs as $val) {

                                $checked = '';
                                if (isset($fieldValue) && $fieldValue != "") {
                                    $checked = ($val == $fieldValue) ? 'checked="checked"' : '';
                                }

                                $options .= '<li><input type="radio" class="submit_on_select" id="minimal-checkbox-' . $loop . '"  value="' . esc_html($val) . '" ' . $checked . ' name="' . $fieldName . '"><label for="minimal-checkbox-' . $loop . '">' . esc_html($val) . '</label></li>';
                                $loop++;
                            }
                        }
                        $customHTML .= '<div class="skin-minimal"><ul class="list">' . $options . '</ul></div>';
                    }


                    $customHTML .= adforest_search_params($fieldName);
                    $customHTML .= '</div></div></div></div></form> ';
                }
            }
        }
    }
}
?>
