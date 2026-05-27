<?php
/* Categories Based Form Fields */
if (!function_exists('adforest_taxonomy_add_new_meta_field')) {

    function adforest_taxonomy_add_new_meta_field($term)
    {
        $term_id = $term->term_id;
        $html = '';
        $result = get_term_meta($term_id, '_sb_dynamic_form_fields', true);
        if (isset($result) && $result != "") {
            $formData = sb_dynamic_form_data($result);
            foreach ($formData as $r) {
                if (trim($r['types']) != "") {
                    $html .= sb_dynamic_form_fields($r, 'yes');
                }

                if (isset($r['slugs'])) {
                    update_post_meta(10000000000, "_adforest_tpl_field_" . $r['slugs'], true);
                }
            }
        }

        $templatePriceShow = '_sb_default_cat_price_show';
        $templatePriceRequired = '_sb_default_cat_price_required';

        $templatePriceShowValue = sb_custom_form_data($result, $templatePriceShow);
        $templatePriceRequiredValue = sb_custom_form_data($result, $templatePriceRequired);

        $templatePriceTypeShow = '_sb_default_cat_price_type_show';
        $templatePriceTypeRequired = '_sb_default_cat_price_type_required';

        $templatePriceTypeShowValue = sb_custom_form_data($result, $templatePriceTypeShow);
        $templatePriceTypeRequiredValue = sb_custom_form_data($result, $templatePriceTypeRequired);

        $templateVideoShow = '_sb_default_cat_video_show';
        $templateVideoRequired = '_sb_default_cat_video_required';

        $templateVideoShowValue = sb_custom_form_data($result, $templateVideoShow);
        $templateVideoRequiredValue = sb_custom_form_data($result, $templateVideoRequired);

        $templateTagsShow = '_sb_default_cat_tags_show';
        $templateTagsRequired = '_sb_default_cat_tags_required';

        $templateTagsShowValue = sb_custom_form_data($result, $templateTagsShow);
        $templateTagsRequiredValue = sb_custom_form_data($result, $templateTagsRequired);

        $templateImageShow = '_sb_default_cat_image_show';
        $templateImageRequired = '_sb_default_cat_image_required';

        $templateImageShowValue = sb_custom_form_data($result, $templateImageShow);
        $templateImageRequiredValue = sb_custom_form_data($result, $templateImageRequired);

        $templateConditionShow = '_sb_default_cat_condition_show';
        $templateConditionRequired = '_sb_default_cat_condition_required';

        $templateConditionShowValue = sb_custom_form_data($result, $templateConditionShow);
        $templateConditionRequiredValue = sb_custom_form_data($result, $templateConditionRequired);

        $templateWarrantyShow = '_sb_default_cat_warranty_show';
        $templateWarrantyRequired = '_sb_default_cat_warranty_required';

        $templateWarrantyShowValue = sb_custom_form_data($result, $templateWarrantyShow);
        $templateWarrantyRequiredValue = sb_custom_form_data($result, $templateWarrantyRequired);

        $templateAdTypeShow = '_sb_default_cat_ad_type_show';
        $templateAdTypeRequired = '_sb_default_cat_ad_type_required';

        $templateAdTypeShowValue = sb_custom_form_data($result, $templateAdTypeShow);
        $templateAdTypeRequiredValue = sb_custom_form_data($result, $templateAdTypeRequired);
        ?>
        <table class="wp-list-table widefat striped">
            <tbody>
            <tr>
                <td colspan="3">
                    <p class="submit inline-edit-save">
                        <strong><?php //echo __('Those are default fields you can show or hide them.', 'redux-framework');
                            ?></strong>
                    </p>
                </td>
            </tr>
            <tr class="user-rich-editing-wrap">
                <th class="name column-name">
                    <strong><?php echo __('Field Name', 'redux-framework'); ?></strong>
                </th>
                <th class="username column-username has-row-actions column-primary">
                    <strong><?php echo __('Status', 'redux-framework'); ?></strong>
                </th>
                <th class="username column-username has-row-actions column-primary">
                    <strong><?php echo __('Is Required?', 'redux-framework'); ?></strong>
                </th>
            </tr>
            <tr class="user-rich-editing-wrap">
                <td class="name column-name"><label
                            for="rich_editing"><?php echo __('Price', 'redux-framework'); ?>:</label></td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templatePriceShow; ?>">
                        <select name="<?php echo $templatePriceShow; ?>" id="<?php echo $templatePriceShow; ?>">
                            <option value="1" <?php echo adforest_option_selected($templatePriceShowValue, '1'); ?>>
                                <?php echo __('Show', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templatePriceShowValue, '0'); ?>>
                                <?php echo __('Hide', 'redux-framework'); ?>
                            </option>
                        </select>
                    </label>
                </td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templatePriceRequired; ?>">
                        <select name="<?php echo $templatePriceRequired; ?>" id="<?php echo $templatePriceRequired; ?>">
                            <option value="1" <?php echo adforest_option_selected($templatePriceRequiredValue, '1'); ?>>
                                <?php echo __('Yes', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templatePriceRequiredValue, '0'); ?>>
                                <?php echo __('No', 'redux-framework'); ?>
                            </option>
                        </select>
                </td>
            </tr>
            <tr class="user-rich-editing-wrap">
                <td class="name column-name"><label
                            for="rich_editing"><?php echo __('Price Type', 'redux-framework'); ?>:</label></td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templatePriceTypeShow; ?>">
                        <select name="<?php echo $templatePriceTypeShow; ?>" id="<?php echo $templatePriceTypeShow; ?>">
                            <option value="1" <?php echo adforest_option_selected($templatePriceTypeShowValue, '1'); ?>>
                                <?php echo __('Show', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templatePriceTypeShowValue, '0'); ?>>
                                <?php echo __('Hide', 'redux-framework'); ?>
                            </option>
                        </select>
                    </label>
                </td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templatePriceTypeRequired; ?>">
                        <select name="<?php echo $templatePriceTypeRequired; ?>"
                                id="<?php echo $templatePriceTypeRequired; ?>">
                            <option value="1" <?php echo adforest_option_selected($templatePriceTypeRequiredValue, '1'); ?>>
                                <?php echo __('Yes', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templatePriceTypeRequiredValue, '0'); ?>>
                                <?php echo __('No', 'redux-framework'); ?>
                            </option>
                        </select>
                </td>
            </tr>
            <tr class="user-rich-editing-wrap">
                <td class="name column-name"><label
                            for="rich_editing"><?php echo __('Video URL', 'redux-framework'); ?></label></td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templateVideoShow; ?>">
                        <select name="<?php echo $templateVideoShow; ?>" id="<?php echo $templateVideoShow; ?>">
                            <option value="1" <?php echo adforest_option_selected($templateVideoShowValue, '1'); ?>>
                                <?php echo __('Show', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templateVideoShowValue, '0'); ?>>
                                <?php echo __('Hide', 'redux-framework'); ?>
                            </option>
                        </select>
                    </label>
                </td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templateVideoRequired; ?>">
                        <select name="<?php echo $templateVideoRequired; ?>" id="<?php echo $templateVideoRequired; ?>">
                            <option value="1" <?php echo adforest_option_selected($templateVideoRequiredValue, '1'); ?>>
                                <?php echo __('Yes', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templateVideoRequiredValue, '0'); ?>>
                                <?php echo __('No', 'redux-framework'); ?>
                            </option>
                        </select>
                    </label>
                </td>
            </tr>
            <tr class="user-rich-editing-wrap">
                <td class="name column-name"><label
                            for="rich_editing"><?php echo __('Ad Tags', 'redux-framework'); ?></label></td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templateTagsShow; ?>">
                        <select name="<?php echo $templateTagsShow; ?>" id="<?php echo $templateTagsShow; ?>">
                            <option value="1" <?php echo adforest_option_selected($templateTagsShowValue, '1'); ?>>
                                <?php echo __('Show', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templateTagsShowValue, '0'); ?>>
                                <?php echo __('Hide', 'redux-framework'); ?>
                            </option>
                        </select>
                    </label>
                </td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templateTagsRequired; ?>">
                        <select name="<?php echo $templateTagsRequired; ?>" id="<?php echo $templateTagsRequired; ?>">
                            <option value="1" <?php echo adforest_option_selected($templateTagsRequiredValue, '1'); ?>>
                                <?php echo __('Yes', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templateTagsRequiredValue, '0'); ?>>
                                <?php echo __('No', 'redux-framework'); ?>
                            </option>
                        </select>
                    </label>
                </td>
            </tr>
            <tr class="user-rich-editing-wrap">
                <td class="name column-name"><label
                            for="rich_editing"><?php echo __('Ad Images', 'redux-framework'); ?></label></td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templateImageShow; ?>">
                        <select name="<?php echo $templateImageShow; ?>" id="<?php echo $templateImageShow; ?>">
                            <option value="1" <?php echo adforest_option_selected($templateImageShowValue, '1'); ?>>
                                <?php echo __('Show', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templateImageShowValue, '0'); ?>>
                                <?php echo __('Hide', 'redux-framework'); ?>
                            </option>
                        </select>
                    </label>
                </td>
                <td class="username column-username has-row-actions column-primary">
                    <label for="<?php echo $templateImageRequired; ?>">
                        <select name="<?php echo $templateImageRequired; ?>" id="<?php echo $templateImageRequired; ?>">
                            <option value="1" <?php echo adforest_option_selected($templateImageRequiredValue, '1'); ?>>
                                <?php echo __('Yes', 'redux-framework'); ?>
                            </option>
                            <option value="0" <?php echo adforest_option_selected($templateImageRequiredValue, '0'); ?>>
                                <?php echo __('No', 'redux-framework'); ?>
                            </option>
                        </select>
                </td>
            </tr>
            <?php if (!apply_filters('adforest_directory_enabled', false)) { ?>
                <tr class="user-rich-editing-wrap">
                    <td class="name column-name">
                        <label for="rich_editing"><?php echo __('Item Condition', 'redux-framework'); ?></label>
                    </td>
                    <td class="username column-username has-row-actions column-primary">
                        <label for="<?php echo $templateConditionShow; ?>">
                            <select name="<?php echo $templateConditionShow; ?>"
                                    id="<?php echo $templateConditionShow; ?>">
                                <option value="1" <?php echo adforest_option_selected($templateConditionShowValue, '1'); ?>>
                                    <?php echo __('Show', 'redux-framework'); ?>
                                </option>
                                <option value="0" <?php echo adforest_option_selected($templateConditionShowValue, '0'); ?>>
                                    <?php echo __('Hide', 'redux-framework'); ?>
                                </option>
                            </select>
                        </label>
                    </td>
                    <td class="username column-username has-row-actions column-primary">
                        <label for="<?php echo $templateConditionRequired; ?>">
                            <select name="<?php echo $templateConditionRequired; ?>"
                                    id="<?php echo $templateConditionRequired; ?>">
                                <option value="1" <?php echo adforest_option_selected($templateConditionRequiredValue, '1'); ?>>
                                    <?php echo __('Yes', 'redux-framework'); ?>
                                </option>
                                <option value="0" <?php echo adforest_option_selected($templateConditionRequiredValue, '0'); ?>>
                                    <?php echo __('No', 'redux-framework'); ?>
                                </option>
                            </select>
                        </label>
                    </td>
                </tr>
                <tr class="user-rich-editing-wrap">
                    <td class="name column-name"><label
                                for="rich_editing"><?php echo __('Warranty', 'redux-framework'); ?></label></td>
                    <td class="username column-username has-row-actions column-primary">
                        <label for="<?php echo $templateWarrantyShow; ?>">
                            <select name="<?php echo $templateWarrantyShow; ?>"
                                    id="<?php echo $templateWarrantyShow; ?>">
                                <option value="1" <?php echo adforest_option_selected($templateWarrantyShowValue, '1'); ?>>
                                    <?php echo __('Show', 'redux-framework'); ?>
                                </option>
                                <option value="0" <?php echo adforest_option_selected($templateWarrantyShowValue, '0'); ?>>
                                    <?php echo __('Hide', 'redux-framework'); ?>
                                </option>
                            </select>
                        </label>
                    </td>
                    <td class="username column-username has-row-actions column-primary">
                        <label for="<?php echo $templateWarrantyRequired; ?>">
                            <select name="<?php echo $templateWarrantyRequired; ?>"
                                    id="<?php echo $templateWarrantyRequired; ?>">
                                <option value="1" <?php echo adforest_option_selected($templateWarrantyRequiredValue, '1'); ?>>
                                    <?php echo __('Yes', 'redux-framework'); ?>
                                </option>
                                <option value="0" <?php echo adforest_option_selected($templateWarrantyRequiredValue, '0'); ?>>
                                    <?php echo __('No', 'redux-framework'); ?>
                                </option>
                            </select>
                        </label>
                    </td>
                </tr>
                <tr class="user-rich-editing-wrap">
                    <td class="name column-name"><label
                                for="rich_editing"><?php echo __('Ad Type', 'redux-framework'); ?></label></td>
                    <td class="username column-username has-row-actions column-primary">
                        <label for="<?php echo $templateAdTypeShow; ?>">

                            <select name="<?php echo $templateAdTypeShow; ?>" id="<?php echo $templateAdTypeShow; ?>">
                                <option value="1" <?php echo adforest_option_selected($templateAdTypeShowValue, '1'); ?>>
                                    <?php echo __('Show', 'redux-framework'); ?>
                                </option>
                                <option value="0" <?php echo adforest_option_selected($templateAdTypeShowValue, '0'); ?>>
                                    <?php echo __('Hide', 'redux-framework'); ?>
                                </option>
                            </select>
                        </label>
                    </td>
                    <td class="username column-username has-row-actions column-primary">
                        <label for="<?php echo $templateAdTypeRequired; ?>">
                            <select name="<?php echo $templateAdTypeRequired; ?>"
                                    id="<?php echo $templateAdTypeRequired; ?>">
                                <option value="1" <?php echo adforest_option_selected($templateAdTypeRequiredValue, '1'); ?>>
                                    <?php echo __('Yes', 'redux-framework'); ?>
                                </option>
                                <option value="0" <?php echo adforest_option_selected($templateAdTypeRequiredValue, '0'); ?>>
                                    <?php echo __('No', 'redux-framework'); ?>
                                </option>
                            </select>
                        </label>
                    </td>
                </tr>
            <?php } ?>


            </tbody>
        </table>


        <!--ui-sortable-->
        <div class="wrap-custom">
            <div id="poststuff">
                <div id="postbox-container" class="postbox-container">
                    <div class="meta-box-sortables " id="normal-sortables">


                        <table class="wp-list-table widefat striped">
                            <tbody class="custom_fields_wrap custom_fields_table" id="sortable">

                            <?php echo $html; ?>
                            <!--tr goes here-->
                            </tbody>
                            <tfoot>
                            <br/>
                            <tr>
                                <td colspan="4">
                                    <input id="add-custom-field-button" class="button button-primary add_field_button"
                                           value="<?php echo __('Add More Fields', 'redux-framework'); ?>"
                                           type="button">
                                    </label>
                                </td>
                            </tr>
                            </tfoot>
                        </table>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('function adforest_option_selected')) {

    function adforest_option_selected($key, $val)
    {
        return ($key == $val) ? 'selected="selected"' : '';
    }

}

if (!function_exists('sb_custom_form_data')) {
    function sb_custom_form_data($result = '', $key = '')
    {
        $arr = '';
        $res = array();
        if ($result != "" && $key != "") {
            $baseDecode = base64_decode($result);
            $arr = json_decode($baseDecode, true);
            $arr = is_array($arr) ? array_map('stripslashes_deep', $arr) : stripslashes($arr);
            if (isset($arr["$key"])) {
                return ($arr["$key"]);
            } else {
                return 1;
            }
        }
    }
}

if (!function_exists('sb_dynamic_form_data')) {

    function sb_dynamic_form_data($result = '')
    {
        $arr = '';
        $res = array();
        if ($result != "") {
            $baseDecode = base64_decode($result);
            $arr = json_decode($baseDecode, true);
            $arr = is_array($arr) ? array_map('stripslashes_deep', $arr) : stripslashes($arr);
            $formTypes = isset($arr['_sb_dynamic_form_types']) ? $arr['_sb_dynamic_form_types'] : array();
            $countArr = count($formTypes);
            $i = 0;
            if ($countArr > 0 && $formTypes != "") {
                for ($i = 0; $i <= $countArr; $i++) {
                    $res[$i]['types'] = isset($arr['_sb_dynamic_form_types'][$i]) ? $arr['_sb_dynamic_form_types'][$i] : '';
                    $res[$i]['titles'] = isset($arr['_sb_dynamic_form_titles'][$i]) ? $arr['_sb_dynamic_form_titles'][$i] : '';
                    $res[$i]['columns'] = isset($arr['_sb_dynamic_form_columns'][$i]) ? $arr['_sb_dynamic_form_columns'][$i] : '';
                    $res[$i]['slugs'] = isset($arr['_sb_dynamic_form_slugs'][$i]) ? $arr['_sb_dynamic_form_slugs'][$i] : '';
                    $res[$i]['values'] = isset($arr['_sb_dynamic_form_values'][$i]) ? $arr['_sb_dynamic_form_values'][$i] : '';
                    $res[$i]['status'] = isset($arr['_sb_dynamic_form_status'][$i]) ? $arr['_sb_dynamic_form_status'][$i] : '';
                    $res[$i]['requires'] = isset($arr['_sb_dynamic_form_requires'][$i]) ? $arr['_sb_dynamic_form_requires'][$i] : '';
                    $res[$i]['in_search'] = isset($arr['_sb_dynamic_form_in_search'][$i]) ? $arr['_sb_dynamic_form_in_search'][$i] : '';
                    $res[$i]['in_ad_grid'] = isset($arr['_sb_dynamic_form_in_ad_grid'][$i]) ? $arr['_sb_dynamic_form_in_ad_grid'][$i] : '';
                }
            }
        }
        return $res;
    }

}

if (!function_exists('adforest_getCats_desc')) {

    function adforest_getCats_desc($postId)
    {
        $terms = wp_get_post_terms($postId, 'ad_cats', array('orderby' => 'id', 'order' => 'DESC'));
        $deepestTerm = false;
        $maxDepth = -1;
        $c = 0;
        foreach ($terms as $term) {
            $ancestors = get_ancestors($term->term_id, 'ad_cats');
            $termDepth = count($ancestors);
            $deepestTerm[$c] = $term;
            $maxDepth = $termDepth;
            $c++;
        }
        return ($deepestTerm);
    }

}

if (!function_exists('adforestCustomFieldsHTML')) {

    function adforestCustomFieldsHTML($post_id = '', $cols = 4, $ad_style = 'default')
    {
        global $adforest_theme;
        global $sitepress;
        if ($post_id == "") {
            return;
        }
        $html = '';
        $cats = adforest_get_ad_cats($post_id);
        $term_id = "";
        if (isset($cats) && count((array)$cats) > 0 && $cats != "") {

            $total_terms = count($cats);
            $loop_count = $total_terms - 1;

            $term = $cats[$loop_count];
            $term_id = isset($term['id']) ? $term['id'] : "";

            //$terms = adforest_getCats_desc($post_id);
//        $terms = wp_get_post_terms($post_id, 'ad_cats' ,array('orderby'=> 'parent'));      
//        if (isset($terms) && count((array) $terms) > 0 && $terms != "") {
//                
//            $total_terms = count($terms);
//           
//            for ($i = ($total_terms - 1); $i >= 0; $i--) {
//                $term = $terms[$i];            
//                if (isset($term->term_id) && $term->term_id != "") {
//                    $term_id = $term->term_id;
//                   
//                    $t = adforest_dynamic_templateID($term_id);                   
//                    if ($t) {
//                        break;
//                    }
//                }
//            }        
            $templateID = adforest_dynamic_templateID($term_id);
            $templateID = apply_filters('adforest_get_lang_tamonomy', $templateID, 'sb_dynamic_form_templates');
            $result = get_term_meta($templateID, '_sb_dynamic_form_fields', true);
            $is_update_wpml = get_post_meta($post_id, 'adforest_is_update_wpml', true);
            $is_translatebale_tempalte = isset($adforest_theme['sb_template_translate']) ? $adforest_theme['sb_template_translate'] : "";
            //      if($is_update_wpml ==  0 || $is_update_wpml == ""){

            $origional_post_id = get_post_meta($post_id, '_icl_lang_duplicate_of', true);

            if ($origional_post_id != $post_id && $origional_post_id != "" && $is_translatebale_tempalte) {
                update_post_meta($post_id, 'adforest_is_update_wpml', 1);

                $current_lang = $sitepress->get_current_language();
                $default_lang = $sitepress->get_default_language();


                if ($current_lang != $default_lang) {

                    $sitepress->switch_lang($default_lang);
                    $cats_origional = adforest_get_ad_cats($origional_post_id);
                    $total_terms_original = count($cats_origional);
                    $loop_count_original = $total_terms_original - 1;

                    $term_original = $cats_origional[$loop_count_original];
                    $term_id_original = isset($term_original['id']) ? $term_original['id'] : "";
                    $templateID_original = adforest_dynamic_templateID($term_id_original);
                    $result_arabic = get_term_meta($templateID_original, '_sb_dynamic_form_fields', true);
                    $sitepress->switch_lang($current_lang);

                } else {

                    $origional_post_language = get_post_meta($origional_post_id, 'lang_id', true);
                    if ($origional_post_language != "") {
                        $sitepress->switch_lang($origional_post_language);
                        $cats_origional = adforest_get_ad_cats($post_id);
                        $total_terms_original = count($cats_origional);
                        $loop_count_original = $total_terms_original - 1;
                        $term_original = $cats_origional[$loop_count_original];
                        $term_id_original = isset($term_original['id']) ? $term_original['id'] : "";
                        $templateID_original = adforest_dynamic_templateID($term_id_original);
                        $result_arabic = get_term_meta($templateID_original, '_sb_dynamic_form_fields', true);

                    }
                }

                if (isset($result) && $result != "") {
                    $formData = sb_dynamic_form_data($result);

                }
                if (isset($result_arabic) && $result_arabic != "") {
                    $formData_arabic = sb_dynamic_form_data($result_arabic);
                    if (count($formData_arabic) > 0) {
                        foreach ($formData_arabic as $key => $data) {
                            if ($data['titles'] != "") {
                                $arr = ['2', '3', '9', '8'];
                                if (in_array($data['types'], $arr)) {
                                    $values = get_post_meta($origional_post_id, "_adforest_tpl_field_" . $data['slugs'], true);

                                    $value = json_decode($values);
                                    $value = (is_array($value)) ? $value : array($values);
                                    $rang_values = @explode("|", $data['values']);


                                    $intersected_values = array_intersect($rang_values, $value);
                                    $array_keys = array_keys($intersected_values);
                                    $arabic_values = '';
                                    if (isset($formData[$key]['values'])) {
                                        $rang_values_arabic = @explode("|", $formData[$key]['values']);
                                        $arabic_values = array();
                                        foreach ($array_keys as $key => $value) {
                                            $arabic_values[] = $rang_values_arabic[$value];
                                            # code...
                                        }
                                        $datas = stripslashes(json_encode($arabic_values, JSON_UNESCAPED_UNICODE));
                                        $input_key = '_adforest_tpl_field_' . $data['slugs'];
                                        update_post_meta($post_id, $input_key, sanitize_text_field($datas));
                                        $result = get_term_meta($templateID, '_sb_dynamic_form_fields', true);

                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (isset($result) && $result != "") {
            $formData = sb_dynamic_form_data($result);
//            error_log(print_r($formData, true));
            if (count($formData) > 0) {
                foreach ($formData as $data) {
                    if ($data['titles'] != "") {
                        $values = get_post_meta($post_id, "_adforest_tpl_field_" . $data['slugs'], true);
                        $value = json_decode($values);
                        $value = (is_array($value)) ? implode(", ", $value) : $values;
                        $titles = ($data['titles']);
                        if ($value != "") {
                            if (isset($data['types']) && $data['types'] == 5) {
                                $sb_link_text = isset($adforest_theme['sb_link_text']) && !empty($adforest_theme['sb_link_text']) ? $adforest_theme['sb_link_text'] : __('View website', 'redux-framework');
                                $value = '<a href="' . esc_url($value) . '" target="_blank">' . $sb_link_text . '</a>';
                            } else if (isset($data['types']) && $data['types'] == 4) {
                                $value = date(get_option('date_format'), strtotime($value));
                            } else if (isset($data['types']) && $data['types'] == 7) {
                                $myDiv = '<span style="height:20px; width:20px;display: inline-block; background-color:' . $value . ';border-radius: 50%;vertical-align: middle;box-shadow: 0 0 7px 4px rgba(0,0,0,0.05);"></span>';
                                $value = $myDiv;
                            } elseif(isset($data['types']) && $data['types'] == 6) {
                                $value = esc_html(str_replace([' ', ','], ['', '-'], $value));
                            } else {
                                $value = esc_html($value);
                            }

                            if ($ad_style == 'style-6') {
                                $html .= ' <li>' . esc_html($titles) . ' : <span>' . ($value) . '</span></li>';
                            } else {
                                $html .= '<div class="col-sm-' . $cols . ' col-md-' . $cols . ' col-xs-12 no-padding">
                        <span><strong>' . esc_html($titles) . '</strong> :</span>
                        ' . ($value) . '
                        </div>';
                            }
                        }
                    }
                }
            }
        }
        //}
        if (isset($sitepress)) {
            $sitepress->switch_lang($current_lang);
        }
        return $html;
    }

}

if (!function_exists('sb_dynamic_form_fields')) {

    function sb_dynamic_form_fields($results = '', $loop = '')
    {
        $type = $title = $value = $status = $require = $selectVals = $columns = $slugs = $in_search = $in_ad_grid = '';
        if ($loop != "" && $results != "") {
            $type = (isset($results['types'])) ? $results['types'] : '';
            $title = (isset($results['titles'])) ? $results['titles'] : '';
            $slugs = (isset($results['slugs'])) ? $results['slugs'] : '';
            $columns = (isset($results['columns'])) ? $results['columns'] : '';
            $value = (isset($results['values'])) ? $results['values'] : '';
            $status = (isset($results['status'])) ? $results['status'] : '';
            $require = (isset($results['requires'])) ? $results['requires'] : '';
            $in_search = (isset($results['in_search'])) ? $results['in_search'] : '';
            $in_ad_grid = (isset($results['in_ad_grid'])) ? $results['in_ad_grid'] : '';
        }
        /* Get values and add in fields starts */
        $typeArray = array(
            "1" => __('Input - Textfield', 'redux-framework'),
            "2" => __('Options - Select Box', 'redux-framework'),
            "3" => __('Options - Check Box', 'redux-framework'),
            "9" => __('Options - Advance Check Box', 'redux-framework'),
            "4" => __('Date - Input', 'redux-framework'),
            "5" => __('Website URL', 'redux-framework'),
            "6" => __('Input - Number Range', 'redux-framework'),
            "7" => __('Select Colors', 'redux-framework'),
            "8" => __('Options - Radio Buttons', 'redux-framework'),
        );

        $typeLoopOptions = '<option value="">' . __('Select Option', 'redux-framework') . '</option>';
        foreach ($typeArray as $key => $val) {
            $typeSelected = (isset($type) && $type == $key) ? 'selected="selected"' : '';
            $typeLoopOptions .= '<option value="' . esc_attr($key) . '" ' . $typeSelected . '>' . esc_html($val) . '</option>';
        }

        $textareaHide = ($type == 2 || $type == 3 || $type == 6 || $type == 7 || $type == 8 || $type == 9) ? '' : 'style="display:none;"';

        $status1 = ($status == 1) ? 'selected="selected"' : '';
        $status2 = ($status == 0) ? 'selected="selected"' : '';

        $require1 = ($require == 1) ? 'selected="selected"' : '';
        $require2 = ($require == 0) ? 'selected="selected"' : '';

        $Columnselected1 = ($columns == 12) ? 'selected="selected"' : '';
        $Columnselected2 = ($columns == 6) ? 'selected="selected"' : '';
        $Columnselected3 = ($columns == 4) ? 'selected="selected"' : '';
        $Columnselected4 = ($columns == 3) ? 'selected="selected"' : '';

        $inSearchSelect1 = ($in_search == 'no') ? 'selected="selected"' : '';
        $inSearchSelect2 = ($in_search == 'yes') ? 'selected="selected"' : '';
        $inAdGridSelect1 = ($in_ad_grid == 'no') ? 'selected="selected"' : '';
        $inAdGridSelect2 = ($in_ad_grid == 'yes') ? 'selected="selected"' : '';
        /* Get values and add in fields ends */

        $selectNameAttr = '_sb_dynamic_form_types[]';
        $inputNameAttr = '_sb_dynamic_form_titles[]';
        $columnSelect = '_sb_dynamic_form_columns[]';
        $inputSlugNameAttr = '_sb_dynamic_form_slugs[]';
        $checkboxNameAttr = '_sb_dynamic_form_requires[]';
        $remBtnAttr = '_sb_dynamic_form_removes[]';
        $statusBtnAttr = '_sb_dynamic_form_status[]';
        $valuesAttr = '_sb_dynamic_form_values[]';
        $inSearchSelect = '_sb_dynamic_form_in_search[]';
        $inAdGridSelect = '_sb_dynamic_form_in_ad_grid[]';

        $fieldName = __('Field Name', 'redux-framework');
        $fieldSlugName = __('Slug Name', 'redux-framework');
        $columnName = __('Columns', 'redux-framework');
        $slectName = __('Select Option', 'redux-framework');
        $valuesName = __('Enter Values', 'redux-framework');
        $requiredName = __('Required?', 'redux-framework');
        $remdName = __('Remove Field', 'redux-framework');
        $statusName = __('Status', 'redux-framework');
        $inSearchName = __('In Search', 'redux-framework');
        $inAdGridName = __('In Ad Grid', 'redux-framework');

        $dynamic = ''; //__('Those are dynamic fields you can add texfield and select options as much as you want.', 'redux-framework');

        $titleName = isset($title) ? $title : __('New Field', 'redux-framework');
        $slugsId = (isset($slugs) && $slugs != "") ? $slugs : rand(1, 100000);

        $someExtraText = '';
        //$someExtraText .= __("and (Pleae don't add any white space while adding value before and after | )", 'redux-framework');
        $someExtraText .= "<br /><strong>*</strong>" . __("If you have option (Input - Number Range). Then the first value will be slider minimum value 2nd will be max value and 3rd will be intervals i.e   0|100000|1 ", "redux-framework");

        $someExtraText .= "<br /><strong>*</strong>" . __("If you have option (Select Colors). Then add them in following way. code:name|code:name|code:name  e.g.  #fff:white|#000:black ", "redux-framework");

        $moreHTML = '<tr class="inline-edit-row">
       <td><div class="postbox " id="postbox-1-' . $slugsId . '">
      <div title="' . __('Click to toggle', 'redux-framework') . '" class="handlediv"><br>
      </div>
      <button type="button" class="handlediv button-link" aria-expanded="false"> <span class="toggle-indicator" aria-hidden="true"></span></button>
      <h3 class="hndle"><span>' . $titleName . '&nbsp;</span></h3>
      <div class="inside">
        <p class="submit inline-edit-save"> <strong>' . $dynamic . '</strong> </p>
        <fieldset class="inline-edit-col-left">
          <br class="clear">
          <div>
            <div class="wp-clearfix">
              <label class="input-text-wrap">
              <span class="title">' . $slectName . '</span></label>
              <select name="' . $selectNameAttr . '" class="hideValuesBox" required="required">
                ' . $typeLoopOptions . '
              </select>
              &nbsp;&nbsp;&nbsp;
              <label class="inline-edit-status alignright"> <span class="title">' . $inSearchName . '</span>
                <select name="' . $inSearchSelect . '" required="required">
                  <option value="yes" ' . $inSearchSelect2 . '>' . __('Yes', 'redux-framework') . '</option>
                  <option value="no" ' . $inSearchSelect1 . '>' . __('No', 'redux-framework') . '</option>
                </select>
              </label>
              &nbsp;&nbsp;&nbsp;
              <label class="inline-edit-status alignright"> <span class="title">' . $inAdGridName . '</span>
                <select name="' . $inAdGridSelect . '" required="required">
                  <option value="yes" ' . $inAdGridSelect2 . '>' . __('Yes', 'redux-framework') . '</option>
                  <option value="no" ' . $inAdGridSelect1 . '>' . __('No', 'redux-framework') . '</option>
                </select>
              </label>
              &nbsp;&nbsp;&nbsp;
              <label class="inline-edit-status alignright"> <span class="title">' . $columnName . '</span>
                <select name="' . $columnSelect . '" required="required">
                  <option value="12" ' . $Columnselected1 . '>' . __('1 Column', 'redux-framework') . '</option>
                  <option value="6" ' . $Columnselected2 . '>' . __('2 Columns', 'redux-framework') . '</option>
                  <option value="4" ' . $Columnselected3 . '>' . __('3 Columns', 'redux-framework') . '</option>
                  <option value="3" ' . $Columnselected4 . '>' . __('4 Columns', 'redux-framework') . '</option>
                </select>
              </label>
              &nbsp;&nbsp;&nbsp;
              <label class="inline-edit-status alignright"> <span class="title">' . $requiredName . '</span>
                <select name="' . $checkboxNameAttr . '" required="required">
                  <option value="">' . __('Select Option', 'redux-framework') . '</option>
                  <option value="1" ' . $require1 . '>' . __('Yes', 'redux-framework') . '</option>
                  <option value="0" ' . $require2 . '>' . __('No', 'redux-framework') . '</option>
                </select>
              </label>
              &nbsp;&nbsp;&nbsp;
              <label class="inline-edit-status alignright"> <span class="title">' . $statusName . '</span>
                <select name="' . $statusBtnAttr . '" required="required">
                  <option value="">' . __('Select Option', 'redux-framework') . '</option>
                  <option value="1" ' . $status1 . '>' . __('Active', 'redux-framework') . '</option>
                  <option value="0" ' . $status2 . '>' . __('Inactive', 'redux-framework') . '</option>
                </select>
                &nbsp;&nbsp;&nbsp; </label>
            </div>
            <div class="wp-clearfix">
              <label> <span class="title">' . $fieldName . '</span> <span class="input-text-wrap">
                <input class="ptitle sb-get-tilte" value="' . $title . '" type="text" name="' . $inputNameAttr . '" required="required">
                <span>' . __("Enter Field title here.", "redux-framework") . '</span> </span> </label>
              <label> <span class="title">' . $fieldSlugName . '</span> <span class="input-text-wrap">
                <input class="ptitle sb-get-slug" value="' . $slugs . '" type="text" name="' . $inputSlugNameAttr . '" required="required" readonly>
                <input type="Checkbox" class="sb-get-slug-edit">
                <strong>(' . __("Edit", "redux-framework") . ')</strong> <span>' . __("Enter the slug name. It must be unique. Change it with caution. only alpha numeric and _, Do not use space between two words", "redux-framework") . '</span> </span> </label>
            </div>
            <label class="values-label" ' . $textareaHide . '> <span class="title">' . $valuesName . '</span> <span class="input-text-wrap">
              <textarea cols="22" rows="1" class="tax_input_post_tag" name="' . $valuesAttr . '">' . $value . '</textarea>
              <span>' . __("Enter Values seprated by ", "redux-framework") . ' | ' . $someExtraText . '</span> </span> </label>
            <button type="button" class="button button-primary cancel alignright sb_custom_rem_btn"  name="' . $remBtnAttr . '">' . $remdName . '</button>
            <br class="clear">
            <br class="clear">
          </div>
        </fieldset>
        <br class="clear">
      </div>
    </div></td>
</tr>';

        return ' ' . $moreHTML . '';
    }

}
// Save extra taxonomy fields callback function.
if (!function_exists('my_taxonomy_save_taxonomy_meta')) {

    function my_taxonomy_save_taxonomy_meta($term_id)
    {

        if (isset($_POST)) {
            $data = wp_json_encode($_POST);
            $data = base64_encode($data);
            update_term_meta($term_id, '_sb_dynamic_form_fields', $data);
        }
    }

}
//add_action( 'created_sb_dynamic_form_templates', 'my_taxonomy_save_taxonomy_meta');
add_action('edit_sb_dynamic_form_templates', 'my_taxonomy_save_taxonomy_meta');

// Register Custom Taxonomy
if (!function_exists('custom_taxonomy')) {

    function custom_taxonomy()
    {

        $labels = array(
            'name' => _x('Form Templates', 'Taxonomy General Name', 'redux-framework'),
            'singular_name' => _x('Form Template', 'Taxonomy Singular Name', 'redux-framework'),
            'menu_name' => __('Category Templates', 'redux-framework'),
            'all_items' => __('All Items', 'redux-framework'),
            'parent_item' => __('Parent Item', 'redux-framework'),
            'parent_item_colon' => __('Parent Item:', 'redux-framework'),
            'new_item_name' => __('New Item Name', 'redux-framework'),
            'add_new_item' => __('Add New Item', 'redux-framework'),
            'edit_item' => __('Edit Item', 'redux-framework'),
            'update_item' => __('Update Item', 'redux-framework'),
            'view_item' => __('View Item', 'redux-framework'),
            'separate_items_with_commas' => __('Separate items with commas', 'redux-framework'),
            'add_or_remove_items' => __('Add or remove items', 'redux-framework'),
            'choose_from_most_used' => __('Choose from the most used', 'redux-framework'),
            'popular_items' => __('Popular Items', 'redux-framework'),
            'search_items' => __('Search Items', 'redux-framework'),
            'not_found' => __('Not Found', 'redux-framework'),
            'no_terms' => __('No items', 'redux-framework'),
            'items_list' => __('Items list', 'redux-framework'),
            'items_list_navigation' => __('Items list navigation', 'redux-framework'),
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => true,
        );
        register_taxonomy('sb_dynamic_form_templates', array('ad_post'), $args);
    }

}
add_action('init', 'custom_taxonomy', 0);
add_action('sb_dynamic_form_templates_edit_form_fields', 'adforest_taxonomy_add_new_meta_field', 10, 2);
add_filter('sb_dynamic_form_templates_row_actions', 'adforest_cat_tempalte_remove_actions', 10, 1);

if (!function_exists('adforest_cat_tempalte_remove_actions')) {

    function adforest_cat_tempalte_remove_actions($actions)
    {
        $current_scrn = get_current_screen();
        if ($current_scrn->taxonomy == 'sb_dynamic_form_templates') {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

}
/* Add Javascript/Jquery Code Here */
if (isset($_GET['taxonomy']) && 'sb_dynamic_form_templates' == $_GET['taxonomy']) {
    add_action('admin_footer', 'adforest_admin_scripts_enqueue_cat_templates');
}

if (!function_exists('adforest_admin_scripts_enqueue_cat_templates')) {

    function adforest_admin_scripts_enqueue_cat_templates()
    {
        wp_enqueue_script('postbox');
        $confirmDelMeg = __('Are You sure you want to remove this field.', 'redux-framework');
        $moreHTML = sb_dynamic_form_fields();
        $output = str_replace(array("\r\n", "\r"), "\n", $moreHTML);
        $lines = explode("\n", $output);
        $new_lines = array();
        foreach ($lines as $i => $line) {
            if (!empty($line))
                $new_lines[] = trim($line);
        }
        $moreHTML = implode($new_lines);
        ?>

        <style type="text/css">
            #sortable {
                list-style-type: none;
                margin: 0;
                padding: 0;
                width: 60%;
            }

            #sortable li {
                margin: 0 3px 3px 3px;
                padding: 0.4em;
                padding-left: 1.5em;
                font-size: 1.4em;
                height: 18px;
            }

            #sortable li span {
                position: absolute;
                margin-left: -1.3em;
            }

            .custom_fields_table tr {
                border: 1px solid #CCCACA;
            }

            #sortable .postbox {
                margin-bottom: 10px !important;
                /* margin-top: 10px !important; */
            }
        </style>


        <script type="text/javascript">
            jQuery(function () {
                jQuery("#sortable").sortable();
                jQuery("#sortable").disableSelection();
            });

            jQuery(document).on('ready', function ($) {
                postboxes.save_state = function () {
                    return;
                };
                postboxes.save_order = function () {
                    return;
                };
                postboxes.add_postbox_toggles();
            });
        </script>
        <script type="text/javascript">
            function sb_confirm_delete_template() {
                confirm("<?php echo esc_js( $confirmDelMeg ); ?>");
                return;
            }

            jQuery(document).ready(function () {

                var max_fields = 10000;
                var wrapper = jQuery(".custom_fields_wrap");
                var add_button = jQuery("#add-custom-field-button");
                var rmv_add_button = jQuery(".sb_custom_rem_btn");

                var x = 1;
                jQuery(add_button).click(function (e) {
                    e.preventDefault();
                    if (x < max_fields) {
                        x++;
                        jQuery(wrapper).append('<?php echo addslashes($moreHTML); ?>');
                    }
                });

                jQuery(wrapper).on("click", ".sb_custom_rem_btn", function (e) {
                    e.preventDefault();
                    jQuery(this).closest('tr').remove();
                    x--;
                })

                jQuery(wrapper).on("click", "#submit", function (e) {
                    jQuery('input.sb-get-slug').each(function () {
                        if (jQuery(this).val() == this.defaultValue) {
                            alert('<?php echo __("Duplicate Slug Name", "redux-framework"); ?>');
                            return false;
                        }
                    });
                });
                jQuery(wrapper).on("click", ".sb-get-slug-edit", function (e) {
                    var checkedval = jQuery(this).is(':checked');
                    if (checkedval) {

                        jQuery(this).closest('tr').find('input.sb-get-slug').removeAttr('readonly');

                    } else {
                        jQuery(this).closest('tr').find('input.sb-get-slug').attr('readonly', 'readonly');
                    }

                });

                jQuery(wrapper).on("change", ".hideValuesBox", function (e) {
                    e.preventDefault();
                    var selectVal = jQuery(this).val();
                    if (selectVal == 1 || selectVal == 4 || selectVal == 5) {
                        jQuery(this).parent().parent().parent().find('label[class=values-label]').hide();
                    } else {
                        jQuery(this).parent().parent().parent().find('label[class=values-label]').show();
                    }
                });

                jQuery(wrapper).on("change", ".sb-get-tilte", function (e) {
                    var isData = jQuery(this).parent().parent().parent().find('input.sb-get-slug').val();
                    if (isData == "") {
                        var selectVal = jQuery(this).val();
                        var string = selectVal.toLowerCase();
                        var slugVal = string.trim().replace(/[^a-z0-9]+/gi, '_');
                        jQuery(this).parent().parent().parent().find('input.sb-get-slug').val(slugVal);
                    }
                });
            });

            jQuery(document).ready(function ($) {
                jQuery('.meta-box-sortables').sortable({
                    opacity: 0.6,
                    revert: true,
                    cursor: 'move',
                    handle: '.hndle'
                });
            });
        </script>
        <?php
    }

}

if (!function_exists('sb_add_template_to_cat')) {

    function sb_add_template_to_cat($taxonomy)
    {

        $terms = get_terms(array('taxonomy' => 'sb_dynamic_form_templates', 'hide_empty' => false, 'parent' => 0,));

        $optionsHtml = '';
        $selected1 = '';

        $term_id = isset($_GET['tag_ID']) ? $_GET['tag_ID'] : '';
        $result = get_term_meta($term_id, '_sb_category_template', true);
        $result = apply_filters('wpml_object_id', $result, 'sb_dynamic_form_templates');

        foreach ($terms as $tr) {

            $selected = ($result == $tr->term_id) ? 'selected="selected"' : '';
            $optionsHtml .= '<option value="' . esc_attr($tr->term_id) . '" ' . $selected . '>' . esc_html($tr->name) . '</option>';

            $selected1 = ($result == 0) ? 'selected="selected"' : '';
        }

        $cat_paid_check = '';
        $bid_base_check = '';

        $html_view = FALSE;

        if (isset($taxonomy->term_id) && !empty($taxonomy->term_id)) {
            $bid_cat_base = get_term_meta($taxonomy->term_id, 'adforest_make_bid_cat_base', true);

            if (isset($bid_cat_base) && $bid_cat_base == 'yes') {
                $bid_base_check = ' checked="checked" ';
            }
            $adforest_make_cat_paid = get_term_meta($taxonomy->term_id, 'adforest_make_cat_paid', true);

            if (isset($adforest_make_cat_paid) && $adforest_make_cat_paid == 'yes') {
                $cat_paid_check = ' checked="checked" ';
            }

            $html_view = TRUE;
        }
        ?>
        <tr class="form-field term-parent-wrap">
            <th scope="row"><label for="parent"><?php _e('Select Template', 'redux-framework'); ?></label></th>
            <td>
                <select name="_sb_ad_template">
                    <option value=""><?php echo __('Select Option', 'redux-framework'); ?></option>
                    <option value="0" <?php echo $selected1; ?>><?php echo __('Default Template', 'redux-framework'); ?>
                    </option>
                    <?php echo($optionsHtml); ?>
                </select>
                <p class="description"><?php echo __('You can assign this template each level category.', 'redux-framework'); ?>
                </p>
                <br/>
            </td>
        </tr>

        <tr class="form-field term-parent-wrap">
            <?php if ($html_view) { ?>
                <th scope="row"><label for="parent"><?php _e('Make Bidding', 'redux-framework'); ?></label></th>
            <?php } ?>
            <td>
                <input type="checkbox" name="adforest_make_bid_cat_base" <?php echo $bid_base_check; ?> />
                <?php if (!$html_view) { ?> <span
                        for="parent"><b><?php _e('Make Bidding', 'redux-framework'); ?></b></span><?php } ?>
                <p class="description">
                    <?php echo __('<b>Note : </b>Enable this option and please make sure you are using <b>"selective"</b> Bidding category type. <b>( Dashboard >> Appearance >> Theme options >> Bidding Settings
                    >> Bidding Category Type)</b>', 'redux-framework'); ?>
                </p>
                <br/>
            </td>
        </tr>
        <tr class="form-field term-parent-wrap">
            <?php if ($html_view) { ?>
                <th scope="row"><label for="parent"><?php _e('Is Paid', 'redux-framework'); ?></label></th>
            <?php } ?>
            <td>
                <input type="checkbox" name="adforest_make_cat_paid" <?php echo $cat_paid_check; ?> />
                <?php if (!$html_view) { ?><span for="parent"><b><?php _e('Is Paid', 'redux-framework'); ?></b>
                    </span><?php } ?>
                <p class="description">
                    <?php echo __('Enable this option to enforce users to buy category base package to post in this category.', 'redux-framework'); ?>
                </p>
                <br/>
            </td>
        </tr>


        <?php
    }

}
add_action('ad_cats_add_form_fields', 'sb_add_template_to_cat', 10, 2);
add_action('ad_cats_edit_form_fields', 'sb_add_template_to_cat', 10, 2);

/* Asign template to category */
if (!function_exists('adforest_assign_template_to_category')) {

    function adforest_assign_template_to_category($term_id)
    {


        if (isset($_POST) && isset($_POST['_sb_ad_template']) && $_POST['_sb_ad_template'] != "") {

            $templateID = (isset($_POST['_sb_ad_template']) && $_POST['_sb_ad_template'] > 0) ? $_POST['_sb_ad_template'] : '';
            //if( $templateID != "" ){
            update_term_meta($term_id, '_sb_category_template', $templateID);
            //}
        }
        if (isset($_POST) && isset($_POST['adforest_make_bid_cat_base']) && !empty($_POST['adforest_make_bid_cat_base'])) {
            update_term_meta($term_id, 'adforest_make_bid_cat_base', 'yes');
        } else {
            update_term_meta($term_id, 'adforest_make_bid_cat_base', 'no');
        }


        if (isset($_POST) && isset($_POST['adforest_make_cat_paid']) && !empty($_POST['adforest_make_cat_paid'])) {
            update_term_meta($term_id, 'adforest_make_cat_paid', 'yes');
        } else {
            update_term_meta($term_id, 'adforest_make_cat_paid', 'no');
        }
    }

}
add_action('create_ad_cats', 'adforest_assign_template_to_category');
add_action('edit_ad_cats', 'adforest_assign_template_to_category');

/* For Front End */
if (!function_exists('adforest_dynamic_templateID')) {

    function adforest_dynamic_templateID($cat_id)
    {
        $termTemplate = '';
        if ($cat_id != "") {

            $termTemplate = get_term_meta($cat_id, '_sb_category_template', true);

            $go_next = ($termTemplate == "" || $termTemplate == 0) ? true : false;
            if ($go_next) {
                $parent = get_term($cat_id);
                if ($parent->parent > 0) {
                    $cat_id = $parent->parent;
                    $termTemplate = get_term_meta($cat_id, '_sb_category_template', true);

                    $go_next = ($termTemplate == "" || $termTemplate == 0) ? true : false;
                    $parent = get_term($cat_id);
                    if ($parent->parent > 0 && $go_next) {
                        $cat_id = $parent->parent;
                        $termTemplate = get_term_meta($cat_id, '_sb_category_template', true);
                        $parent = get_term($cat_id);
                        $go_next = ($termTemplate == "" || $termTemplate == 0) ? true : false;
                        if ($parent->parent > 0 && $go_next) {
                            $cat_id = $parent->parent;
                            $termTemplate = get_term_meta($cat_id, '_sb_category_template', true);
                            $parent = get_term($cat_id);
                            $go_next = ($termTemplate == "" || $termTemplate == 0) ? true : false;
                            if ($parent->parent > 0 && $go_next) {
                                $cat_id = $parent->parent;
                                $termTemplate = get_term_meta($cat_id, '_sb_category_template', true);
                                $parent = get_term($cat_id);
                                $go_next = ($termTemplate == "" || $termTemplate == 0) ? true : false;
                                if ($parent->parent > 0 && $go_next) {
                                    $cat_id = $parent->parent;
                                    $termTemplate = get_term_meta($cat_id, '_sb_category_template', true);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $termTemplate;
    }
}
//Dynamic Fields starts
add_action('wp_ajax_sb_get_sub_template', 'adforest_post_ad_fields');

if (!function_exists('adforest_post_ad_fields')) {

    function adforest_post_ad_fields()
    {
        check_ajax_referer('adforest_get_sub_template_nonce', 'security');
        $html = $termTemplate = '';
        $id = isset($_POST['is_update']) ? $_POST['is_update'] : '';
        $cat_id = isset($_POST['cat_id']) ? $_POST['cat_id'] : '';

        $termTemplate = adforest_dynamic_templateID($cat_id);

        $html .= adforest_get_static_form($termTemplate, $id);
        $html .= adforest_get_dynamic_form($termTemplate, $id);
        echo($html);
        die();
    }

}

//Dynamic fields ends

if (!function_exists('adforest_returnHTML')) {

    function adforest_returnHTML($id = '')
    {

        if ($id == "")
            return '';
        $html = '';
        $mainCatId = '';

        $cats = adforest_get_ad_cats($id);
        foreach ($cats as $cat) {
            $mainCatId = $cat['id'];
        }
        /* $termTemplate =  get_term_meta( $mainCatId , '_sb_category_template' , true); */
        $termTemplate = adforest_dynamic_templateID($mainCatId);
        $html .= adforest_get_static_form($termTemplate, $id);
        $html .= adforest_get_dynamic_form($termTemplate, $id);

        return $html;
    }

}

if (!function_exists('adforest_get_dynamic_form')) {

    function adforest_get_dynamic_form($term_id = '', $post_id = '')
    {
        $html = '';
        if ($term_id == '') {
            return $html;
        }

        $result = get_term_meta($term_id, '_sb_dynamic_form_fields', true);

        if (isset($result) && $result != "") {
            $formData = sb_dynamic_form_data($result);
            foreach ($formData as $data) {
                $status = ($data['status']);
                if (isset($status) && $status == 1) {
                    $types = ($data['types']);
                    $titles = ($data['titles']);
                    $key = '_adforest_tpl_field_' . $data['slugs'];
                    $slugs = 'cat_template_field[' . $key . ']';
                    $values = ($data['values']);
                    $columns = ($data['columns']);
                    $requires = '';

                    $unique_class_name = '_adforest_tpl_field_' . $data['slugs'];

                    $required_html = $message = '';
                    if (isset($data['requires']) && $data['requires'] == '1') {
                        $required_html = '<span class="required"> *</span>';

                        if ($types != 5) {
                            $message = 'data-parsley-error-message="' . __('This field is required.', "redux-framework") . '"';
                        }
                        if ($types == 6) {
                            if (isset($data['values']) && !empty($data['values'])) {
                                $rang_values = explode("|", $data['values']);
                                $min = isset($rang_values[0]) && !empty($rang_values[0]) ? $rang_values[0] : 0;
                                $max = isset($rang_values[1]) && !empty($rang_values[1]) ? $rang_values[1] : 100;
                                $step = isset($rang_values[2]) && !empty($rang_values[2]) ? $rang_values[2] : 1;
                                $message = 'data-parsley-error-message="' . __('This field is required. only enteger are allowed between range ' . $min . ' to ' . $max . ' .', "redux-framework") . '"';

                            }
                        }
                        $requires = 'selected="selected" data-parsley-required="true" ' . $message;
                    }

                    $input_range_data = '';
                    if ($types == 6) {
                        if (isset($data['values']) && !empty($data['values'])) {
                            $rang_values = explode("|", $data['values']);
                            $min = isset($rang_values[0]) && !empty($rang_values[0]) ? $rang_values[0] : 0;
                            $max = isset($rang_values[1]) && !empty($rang_values[1]) ? $rang_values[1] : 100;
                            $step = isset($rang_values[2]) && !empty($rang_values[2]) ? $rang_values[2] : 1;
                            $range_error_msg = sprintf(__('Please enter the number between %d and %d', 'redux-framework'), $min, $max);
                            $input_range_data = ' min="' . $min . '" max="' . $max . '" step="' . $step . '" data-parsley-error-message="' . $range_error_msg . '"';
                        }
                    }


                    $fieldValue = (isset($post_id) && $post_id != "") ? get_post_meta($post_id, $key, true) : '';
                    if ($types == 1) {

                        $html .= '
                        <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20">
                            <label class="control-label">' . esc_html($titles) . $required_html . '</label>
                            <input class="form-control ' . esc_attr($unique_class_name) . '" name="' . esc_attr($slugs) . '" value="' . $fieldValue . '" type="text" ' . $requires . '>
                        </div>';
                    }
                    $options = '';
                    if ($types == 2) {

                        $vals = @explode("|", $values);
                        $fieldValue = trim($fieldValue);
                        foreach ($vals as $val) {

                            $val = trim($val);
                            $selected = ($fieldValue == $val) ? 'selected="selected"' : '';
                            $options .= '<option value="' . esc_attr($val) . '" ' . $selected . '>' . esc_html($val) . '</option>';
                        }

                        $html .= '
                      <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20">
                         <label class="control-label">' . esc_html($titles) . $required_html . '</label>
                         <select class="category form-control ' . esc_attr($unique_class_name) . '" name="' . esc_attr($slugs) . '" ' . $requires . '>
                            <option value="">' . __("Select Option", "redux-framework") . '</option>
                            ' . $options . '
                         </select>
                      </div>';
                    }
                    if ($types == 3 || $types == 9) {
                        $options = '';
                        $vals = @explode("|", $values);
                        $loop = 1;
                        $is_show_error = false;
                        $fieldValue = json_decode($fieldValue, true);
                        foreach ($vals as $val) {
                            $checked = '';
                            $valid_message = '';
                            $is_required = $valid_message = $message = $data_parsley_required = '';

                            if (isset($fieldValue) && $fieldValue != "") {
                                $checked = @in_array($val, $fieldValue) ? 'checked="checked"' : '';
                                if (trim($val) == $fieldValue && $required_html != "" && $loop == 1) {
                                    $is_show_error = true;
                                }
                                if ($loop == 1 && $required_html != "") {
                                    $is_show_error = true;
                                }
                            } else {
                                if ($loop == 1 && $required_html != "") {
                                    $is_show_error = true;
                                }
                            }
                            $is_required = $valid_message = $message = '';
                            if ($is_show_error) {
                                $is_required = 'required';
                                $valid_message = __("Please select a checkbox", "redux-framework");

                                $message = 'data-parsley-mincheck="1" data-parsley-error-message="' . __('Please select a checkbox.', "redux-framework") . '"  data-required-message="' . esc_attr($valid_message) . '" ';
                            }

                            $options .= '<li><input type="checkbox" id="minimal-checkbox-' . $loop . '-' . $slugs . '"  value="' . esc_html($val) . '" ' . $checked . ' name="' . esc_attr($slugs) . '[' . $val . ']" ' . $is_required . ' ' . $message . ' class="' . esc_attr($unique_class_name) . '" data-parsley-multiple="' . esc_attr($unique_class_name) . '"><label for="minimal-checkbox-' . $loop . '-' . $slugs . '">' . esc_html($val) . '</label></li>';
                            $loop++;
                        }

                        $html .= '<div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20">
                        <label class="control-label">' . esc_html($titles) . $required_html . '</label>
                         <div class="skin-minimal "><ul class="list checkbox-' . esc_attr($unique_class_name) . '">' . $options . '</ul></div>
                         </div>';
                    }
                    /* For Date Field */
                    if ($types == 4) {

                        $html .= '
                        <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20 calendar-div">
                            <label class="control-label">' . esc_html($titles) . $required_html . '</label>
                            <input class="form-control dynamic-form-date-fields" name="' . esc_attr($slugs) . '" value="' . $fieldValue . '" type="text" ' . $requires . ' class="' . esc_attr($unique_class_name) . '"><i class="fa fa-calendar"></i>
                        </div>';
                    }
                    /* For Website URL */
                    if ($types == 5) {
                        $valid_message = '';

                        $is_required = $valid_message = $message = $data_parsley_required = '';

                        $is_show_error = false;
                        if ($required_html != "") {
                            $is_show_error = true;
                        }

                        if ($is_show_error) {
                            $is_required = 'required';
                            $valid_message = __("Please enter a valid URL.", "redux-framework");
                            $data_parsley_required = 'data-parsley-required="true"';
                        }

                        $message = $data_parsley_required . ' data-parsley-error-message="' . __('Please enter a valid URL.', "redux-framework") . '"';

                        $html .= '
                  <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20 ">
                     <label class="control-label">' . esc_html($titles) . $required_html . '</label>
                     <input class="form-control ' . esc_attr($unique_class_name) . '" name="' . esc_attr($slugs) . '" value="' . $fieldValue . '" type="url" ' . $requires . ' data-required-message="' . esc_attr($valid_message) . '" data-parsley-type="url" ' . $message . ' >
                  </div>';
                    }
                    /* For Number Range */
                    if ($types == 6) {
                        $fieldValue = (isset($post_id) && $post_id != "") ? get_post_meta($post_id, $key, true) : '';
                        $html .= '<div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20">
                     <label class="control-label">' . esc_html($titles) . $required_html . '</label>
                     <input class="form-control ' . esc_attr($unique_class_name) . '" name="' . esc_attr($slugs) . '" value="' . $fieldValue . '" type="number" ' . $requires . '' . $input_range_data . '>
                  </div>';
                    }
                    /* For Color Options */
                    if ($types == 7) {

                        $vals = @explode("|", $values);
                        $loop_count = 1;
                        $colorsCss = $options = '';
                        foreach ($vals as $val) {
                            $colors = @explode(":", $val);

                            $code = (isset($colors[0]) && $colors[0] != "") ? $colors[0] : '';
                            $name = (isset($colors[1]) && $colors[1] != "") ? $colors[1] : '';
                            if ($code != "" && $name != "") {

                                $is_checked = ($fieldValue == $code) ? 'checked="checked"' : '';

                                $options .= '<div class="color-picker__item">
                            <input id="input-' . $loop_count . '-' . $data['slugs'] . '" type="radio" class="color-picker__input" name="' . esc_attr($slugs) . '" value="' . esc_attr($code) . '" ' . $is_checked . ' />
                            <label for="input-' . $loop_count . '-' . $data['slugs'] . '" class="color-picker__color  color-picker__color--' . $loop_count . '-' . $data['slugs'] . '"></label>
                          </div>';
                                $colorsCss .= '.color-picker__color--' . $loop_count . '-' . $data['slugs'] . ' {  background: ' . $code . '; }';
                                $loop_count++;
                            }
                        }

                        $html .= '<div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20 theme-input-colors "><label class="control-label">' . esc_html($titles) . $required_html . '</label><div class="color-picker">' . $options . '</div></div>';
                        $finalColor = '';
                        $html .= '<style>' . $colorsCss . '</style>';
                    }
                    if ($types == 8) {
                        $options = '';

                        $is_show_error = false;

                        $vals = @explode("|", $values);
                        $loop = 1;
                        foreach ($vals as $val) {
                            $checked = '';

                            if (isset($fieldValue) && $fieldValue != "") {
                                $checked = (trim($val) == $fieldValue) ? 'checked="checked"' : '';
                                if (trim($val) == $fieldValue && $required_html != "" && $loop == 1) {
                                    $is_show_error = true;
                                }
                                if ($loop == 1 && $required_html != "") {
                                    $is_show_error = true;
                                }
                            } else {
                                if ($loop == 1 && $required_html != "") {
                                    $is_show_error = true;
                                }
                            }
                            $is_required = $valid_message = $message = '';
                            if ($loop == 1) {
                                $is_required = 'required="required"';
                                $valid_message = __("Please select a radio button", "redux-framework");
                                $message = ' data-parsley-error-message="' . __('Please select a radio button.', "redux-framework") . '"';
                            }


                            $options .= '<li>
  <input type="radio" id="minimal-checkbox-' . $loop . '-' . $slugs . '"  value="' . esc_html(trim($val)) . '" ' . $checked . ' name="' . esc_attr($slugs) . '" ' . $is_required . ' ' . $message . ' data-required-message="' . esc_attr($valid_message) . '" class="' . esc_attr($unique_class_name) . '"><label for="minimal-checkbox-' . $loop . '-' . $slugs . '">' . esc_html($val) . '</label></li>';
                            $loop++;
                        }

                        $html .= '<div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12 margin-bottom-20 radios">
            <label class="control-label">' . esc_html($titles) . $required_html . '</label>
             <div class="skin-minimal "><ul class="list">' . $options . '</ul></div></div>';
                    }
                }/* Status ends */
            }
        }
        return '<div class="row clear-custom">' . $html . '</div>';
    }

}

if (!function_exists('adforest_return_input')) {

    function adforest_return_input($type = 'textfield', $post_id = '', $term_id = '', $vals = array())
    {
        $html = '';
        $post_id = $post_id;
        $term_id = $term_id;
        $post_meta = isset($vals['post_meta']) ? $vals['post_meta'] : '';
        $is_show = isset($vals['is_show']) ? $vals['is_show'] : '';
        $is_req = isset($vals['is_req']) ? $vals['is_req'] : '';
        $title = isset($vals['main_title']) ? $vals['main_title'] : '';
        $subtitle = isset($vals['sub_title']) ? $vals['sub_title'] : '';
        $fieldName = isset($vals['field_name']) ? $vals['field_name'] : '';
        $fieldID = isset($vals['field_id']) ? $vals['field_id'] : '';
        $fieldClass = isset($vals['field_class']) ? $vals['field_class'] : '';
        $fieldVals = isset($vals['field_value']) ? $vals['field_value'] : '';
        $fieldReq = isset($vals['field_req']) ? $vals['field_req'] : '';
        $catName = isset($vals['cat_name']) ? $vals['cat_name'] : '';
        $columns = isset($vals['columns']) ? $vals['columns'] : '6';
        $dataType = isset($vals['data-parsley-type']) ? $vals['data-parsley-type'] : '';
        $dataMsg = isset($vals['data-parsley-message']) ? $vals['data-parsley-message'] : '';
        $dataPattern = isset($vals['data-parsley-pattern']) ? $vals['data-parsley-pattern'] : '';

        $result = get_term_meta($term_id, '_sb_dynamic_form_fields', true);
        $showField = sb_custom_form_data($result, $is_show);
        $reqField = sb_custom_form_data($result, $is_req);
        $req = ($reqField == 1) ? 'true' : 'false';
        $dataTypes = ($dataType != '') ? 'data-parsley-type="' . $dataType . '" ' : '';
        $required = 'data-parsley-required="' . $req . '" ' . $dataTypes . ' data-parsley-error-message="' . $dataMsg . '"';

        $showField = ($term_id == "") ? 1 : $showField;

        $required_html = '';

        if ($req == 'true') {
            $required_html = '<span class="required"> *</span>';
        }


        $small_html = '';
        if ($subtitle != "") {
            $small_html = ' <small>' . $subtitle . '</small>';
        }

        if ($type == 'textfield' && $showField == 1) {
            if ($post_meta != "") {
                $fieldVals = get_post_meta($post_id, $post_meta, true);
            } else {
                $tags_array = wp_get_object_terms($post_id, $catName, array('fields' => 'names'));
                $fieldVals = implode(',', $tags_array);
            }

            if ($fieldName == 'ad_price') {
                if ($req == 'true') {
                    $req_data = ' data-parsley-required="true" ';
                } else {
                    $req_data = ' data-parsley-required="false"';
                }

                $html .= '<div class="col-lg-6">
                    <div class="field-box">
                    <label for = "' . $fieldName . '" >' . $title . $small_html . $required_html . '</label>
                    <input class="form-control ' . $fieldClass . '" name="' . $fieldName . '" id="' . $fieldID . '" value="' . $fieldVals . '" ' . $req_data . '  data-parsley-pattern="/^[0-9]+\.?[0-9]*$/" data-parsley-error-message="' . __('only numbers allowed.', "redux-framework") . '" />
                    </div></div>';
            } else {
                $pattern_y_vodeo = '';
                if ($fieldName == 'ad_yvideo') {
                    $pattern_y_vodeo = ' data-parsley-pattern="' . $dataPattern . '" ';
                }
                //   $_sb_allow_tags = get_user_meta(get_current_user_id(), '_sb_allow_tags', true);
                ///  if (isset($_sb_allow_tags) && $_sb_allow_tags == "yes") {

                $html .= '<div class="row">
            <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12">
            <label class="control-label">' . $title . $small_html . $required_html . '</label>
            <input class="form-control ' . $fieldClass . '" name="' . $fieldName . '" id="' . $fieldID . '" value="' . $fieldVals . '" ' . $required . ' ' . $pattern_y_vodeo . ' /></div></div>';
                //  }
            }
        }
        if ($type == 'image' && $showField == 1) {

            $html .= '<div class="row">
              <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12">
                <label class="control-label">' . $title . $required_html . $small_html . '</small></label>
                 <div id="' . $fieldID . '" class="' . $fieldClass . '" ' . $required . '></div></div></div>';
        }

        if ($type == 'video') {
            $html .= '<div class="row">
              <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12">
                <label class="control-label">' . $title . $required_html . $small_html . '</small></label>
                 <div id="' . $fieldID . '" class="' . $fieldClass . '" ' . $required . '></div></div></div>';
        }


        if ($type == 'select' && $showField == 1) {
            $optHtml = '';
            $selected = '';
            $selected = '';
            $fieldVals = get_post_meta($post_id, $post_meta, true);
            global $adforest_theme;
            $conditions = adforest_sb_get_cats($catName, 0);
            foreach ($conditions as $con) {
                $selected = '';
                if ($fieldName == 'ad_currency' && $post_id == "") {
                    if (isset($adforest_theme['sb_multi_currency_default']) && $adforest_theme['sb_multi_currency_default'] != "") {
                        if ($adforest_theme['sb_multi_currency_default'] == $con->term_id) {
                            $selected = ' selected="selected"';
                        }
                    } else {
                        $selected = ($fieldVals == $con->name) ? $selected = 'selected="selected"' : '';
                    }
                } else {
                    $selected = ($fieldVals == $con->name) ? $selected = 'selected="selected"' : '';
                }
                $optHtml .= '<option value="' . $con->term_id . '|' . $con->name . '"' . $selected . '>' . $con->name . '</option>';
            }
            $html .= '<div class="row" >
              <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12">
              <label class="control-label">' . $title . $small_html . $required_html . '</label>
              <select class="' . $fieldClass . ' form-control" id="' . $fieldID . '" name="' . $fieldName . '" ' . $required . '>
              <option value="">' . __('Select Option', "redux-framework") . '</option>' . $optHtml . '</select></div></div>';
        }

        if ($type == 'select_custom' && $showField == 1) {
            $optHtml = '';

            $fieldValz = get_post_meta($post_id, $post_meta, true);
            $conditions = $fieldVals;
            foreach ($conditions as $key => $val) {
                $selected = ($fieldValz == $key) ? $selected = 'selected="selected"' : '';
                $optHtml .= '<option value="' . $key . '"' . $selected . '>' . $val . '</option>';
            }
            $html .= '<div class="row" >
        <div class="col-md-' . $columns . ' col-lg-' . $columns . ' col-xs-12 col-sm-12">
        <label class="control-label">' . $title . $small_html . $required_html . '</label>
        <select class="' . $fieldClass . ' form-control" id="' . $fieldID . '" name="' . $fieldName . '" ' . $required . '>
        <option value="">' . __('Select Option', "redux-framework") . '</option>' . $optHtml . '</select></div></div>';
        }
        return $html;
    }

}

//SR Cate Meta Box Starts
add_action('add_meta_boxes', 'sb_rane_meta_box_add_adons', 2);
function sb_rane_meta_box_add_adons()
{
    global $adforest_theme;
    add_meta_box('adforest_pay_per_post_metaboxes', __('Package Essentials/Addons', 'redux-framework'), 'sb_render_meta_product_addons', 'product', 'normal', 'high');
}

function sb_render_meta_product_addons($post = '')
{
    // Add nonce field for security
    wp_nonce_field('my_meta_box_nonce_product', 'meta_box_nonce_product');
    ?>
    <div class="margin_top">
        <p><?php echo __('Simple Ad Expiry (in days)', 'redux-framework'); ?></p>
        <input type="text" name="pay_post_ad_expiry_days" class="project_meta"
               placeholder="<?php echo esc_attr__('Like 30, 40 or 50 but must be an inter value.', 'redux-framework'); ?>"
               size="30" value="<?php echo esc_attr(get_post_meta($post->ID, "pay_post_ad_expiry_days", true)); ?>"
               id="pay_post_ad_expiry_days" spellcheck="true" autocomplete="off">
        <div style="background-color: red; color: white; padding: 2px;">
            <?php echo __('Simple ads expiry in days, -1 means never experies. If simple expiry field is empty then it will use the expiry days from Theme Options.', 'redux-framework'); ?>
        </div>
    </div>
    <div class="margin_top">
        <p><strong><?php echo __('Categories', 'redux-framework'); ?></strong></p>
        <?php
        $pkgCats = array();
        $pkgCats = get_post_meta($post->ID, "adforest_package_cats", true);
        $pkgCats = (isset($pkgCats) && $pkgCats != "") ? $pkgCats : array();
        $checkBox = '';
        $cats = adforest_get_cats('ad_cats', 0);
        if (count((array)$cats) > 0) {
            foreach ($cats as $cat) {
                $boxVal = $cat->term_id; //.'|'.$cat->name;
                $selected = (in_array($boxVal, $pkgCats)) ? 'checked="checked"' : '';
                $checkBox .= '<input type="checkbox" name="adforest_package_cats[]" value="' . $boxVal . '" ' . $selected . '>' . $cat->name . '<br />';
            }
        }
        echo($checkBox);
        ?>
    </div>
    <?php
}

// Move the save function OUTSIDE of the render function and hook it properly
add_action('save_post', 'sb_themes_meta_save_product_adons');

function sb_themes_meta_save_product_adons($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'product') {
        return;
    }

    if (!isset($_POST['meta_box_nonce_product']) || !wp_verify_nonce($_POST['meta_box_nonce_product'], 'my_meta_box_nonce_product')) {
        // return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['pay_post_ad_expiry_days'])) {
        $expiry_days = sanitize_text_field($_POST['pay_post_ad_expiry_days']);
        $result = update_post_meta($post_id, 'pay_post_ad_expiry_days', $expiry_days);
    }

    if (isset($_POST['adforest_package_cats']) && is_array($_POST['adforest_package_cats'])) {
        $categories = array_map('intval', $_POST['adforest_package_cats']);
        $result = update_post_meta($post_id, 'adforest_package_cats', $categories);
    } else {
        delete_post_meta($post_id, 'adforest_package_cats');
    }
}

//SR Cate Meta Box Ends

if (!class_exists('adforest_duplicate_terms')) {

    class adforest_duplicate_terms
    {

        function __construct()
        {
            add_action('admin_menu', array($this, 'adforest_duplicate_it'));
            add_action('current_screen', array($this, 'adforest_verify_taxonomy'));
        }

        function adforest_make_duplicate_link($actions, $term)
        {
            $pt = '';
            if (isset($_REQUEST['post_type'])) {
                $pt = sanitize_text_field($_REQUEST['post_type']);
            }

            $duplicate_url = add_query_arg(
                array('term_duplicator_term' => $term->term_id, '_adfTheme_nonce' => wp_create_nonce('duplicate_term'), 'taxonomy' => $term->taxonomy, 'post_type' => $pt),
                admin_url('edit-tags.php')
            );
            $actions['term_duplicator'] = "<a href='{$duplicate_url}'>" . __('Duplicate', 'redux-framework') . "</a>";

            return $actions;
        }

        function adforest_duplicate_it()
        {
            if (isset($_REQUEST['_adfTheme_nonce']) && check_admin_referer('duplicate_term', '_adfTheme_nonce')) {
                $newTerm = false;
                $term_tax = sanitize_text_field($_REQUEST['taxonomy']);
                $term_id = (int)sanitize_key($_REQUEST['term_duplicator_term']);

                $oldTerm = get_term($term_id, $term_tax);

                $duplicated = __('Duplicated', 'redux-framework');
                if (taxonomy_exists($term_tax) && $oldTerm) {
                    $newTerm = wp_insert_term("{$oldTerm->name} - $duplicated", $term_tax, array('description' => $oldTerm->description, 'slug' => "{$oldTerm->slug}-copy", 'parent' => $oldTerm->parent));

                    if (!is_wp_error($newTerm) && $newTerm) {
                        $result = get_term_meta($term_id, '_sb_dynamic_form_fields', true);
                        update_term_meta($newTerm['term_id'], '_sb_dynamic_form_fields', $result);

                        $mytermURL = admin_url('edit-tags.php?taxonomy=sb_dynamic_form_templates&post_type=ad_post');
                        wp_redirect($mytermURL);
                        exit;
                    } else {

                        function adforest_catTemplate_duplicate_error()
                        {
                            $class = 'notice notice-error';
                            $message = __('A category template with the duplicated name already exists in this taxonomy.', 'redux-framework');
                            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
                        }

                        add_action('admin_notices', 'adforest_catTemplate_duplicate_error');
                    }
                }
            }
        }

        function adforest_verify_taxonomy()
        {
            $cs = get_current_screen();
            if ($cs->taxonomy == 'sb_dynamic_form_templates') {
                add_filter($cs->taxonomy . '_row_actions', array($this, 'adforest_make_duplicate_link'), 10, 2);
            }
        }

    }

    function adforest_duplicate_terms()
    {
        global $adforest_duplicate_terms;

        if (!isset($adforest_duplicate_terms)) {
            $adforest_duplicate_terms = new adforest_duplicate_terms();
        }

        return $adforest_duplicate_terms;
    }

    /* Execute The Function */
    if (function_exists('adforest_duplicate_terms')) {
        adforest_duplicate_terms();
    }
}

add_filter('save_post', 'adforest_save_wpml_posts', 10, 1);

if (!function_exists('adforest_save_wpml_posts')) {
    function adforest_save_wpml_posts($post_id)
    {

        $origional_post_id = get_post_meta($post_id, '_icl_lang_duplicate_of', true);

        if (class_exists('SitePress')) {
            global $sitepress;
            $current_lang = $sitepress->get_current_language();
            update_post_meta($post_id, 'lang_id', $current_lang);
        }
        if ($origional_post_id == 0) {
            update_post_meta($post_id, 'adforest_is_update_wpml', 0);
        }
    }
}