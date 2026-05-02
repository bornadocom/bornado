<?php
/* Make cats selected on update Job */
global $adforest_theme;
$today = date("Y/m/d");
$paid_alert_check = isset($adforest_theme['sb_ad_alerts_paid']) ? $adforest_theme['sb_ad_alerts_paid'] : false;
$alert_end = "";
if ($paid_alert_check) {
    $product_id = isset($nokri['job_alert_package']) ? $nokri['job_alert_package'] : "";
    $days = get_post_meta($product_id, 'package_expiry_days', true);
    $end_date = date("Y/m/d", strtotime("+$days days"));
    $alert_end = '<input type="hidden" name="alert_end" value=' . $end_date . '>';
}
$ad_cats = adforest_get_cats('ad_cats', 0, 0, 'post_ad');
$cats_html = '';
foreach ($ad_cats as $ad_cat) {
    $cats_html .= '<option value="' . $ad_cat->term_id . '" data-name = "' . $ad_cat->name . '">' . $ad_cat->name . '</option>';
}
$categories_style_html = '
                                    <div class="col-md-6 col-xl-12 col-lg-12 col-12 col-sm-12">
                                           <label class="control-label">' . __('Category', 'adforest') . ' <span class="required">*</span> <small>' . __('Select suitable category for your alert', 'adforest') . '</small></label>
                                           <select class="category form-control" id="ad_alert_category_select" name="ad_alert_category_select" data-parsley-required="true" data-parsley-error-message="' . __('This field is required.', 'adforest') . '">
                                                  <option value="">' . esc_html__("Select Option", "adforest") . '</option>
                                                  ' . $cats_html . '
                                           </select>
                                    </div>
                                    <div id="child-category-container"></div>
                                           <div class="col-md-6 col-xl-12 col-lg-12 col-12 col-sm-12"  id="ad_cat_sub_div">
                                                <label class="control-label"><small>' . __('Select suitable subcategory for your ad', 'adforest') . '</small></label>
                                                 <select class="category form-control" id="ad_cat_sub" name="ad_cat_sub">
                                                         
                                                 </select>
                                           </div>
                                        <div class="col-md-6 col-xl-12 col-lg-12 col-12 col-sm-12" id="ad_cat_sub_sub_div" >
                                        <label class="control-label"><small>' . __('Select suitable subcategory for your ad', 'adforest') . '</small></label>
                                           <div class="col-md-12 col-lg-12 col-xl-12 col-12 col-sm-12">
                                                 <select class="category form-control" id="ad_cat_sub_sub" name="ad_cat_sub_sub">
                                                       
                                                 </select>
                                           </div>
                                         </div>
                                        <div class="col-md-6 col-xl-6 col-lg-12 col-12 col-sm-12" id="ad_cat_sub_sub_sub_div">
                                        <label class="control-label"><small>' . __('Select suitable subcategory for your ad', 'adforest') . '</small></label>
                                           <div class="col-md-12 col-lg-12 col-xl-12 col-12 col-sm-12">
                                                 <select class="category form-control" id="ad_cat_sub_sub_sub" name="ad_cat_sub_sub_sub"></select>
                                           </div>
                                  </div>';

?>
<div class="cp-loader"></div>
<div class="modal fade resume-action-modal" id="ad-alert-subscribtion">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <form method="post" id="alert_job_form" class="alert-job-modal-popup">
                <div class="modal-header">
                    <h4 class="modal-title"><?php echo esc_html__('Want to subscribe ad alerts?', 'adforest'); ?></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label>
                                    <?php echo __('Alert Name', 'adforest'); ?><span class="color-red">*</span>
                                </label>
                                <input placeholder="<?php echo __('Enter alert name', 'adforest'); ?>"
                                       class="form-control" type="text" data-parsley-required="true"
                                       data-parsley-error-message="<?php echo __('Please enter alert name', 'adforest'); ?>"
                                       data-parsley-trigger="change" name="alert_name">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label>
                                    <?php echo __('Your Email', 'adforest'); ?><span class="color-red">*</span>
                                </label>
                                <input placeholder="<?php echo __('Enter your email address', 'adforest'); ?>"
                                       class="form-control" type="email" data-parsley-type="email"
                                       data-parsley-required="true"
                                       data-parsley-error-message="<?php echo __('Please enter your valid email', 'adforest'); ?>"
                                       data-parsley-trigger="change" name="alert_email">
                            </div>
                        </div>

                        <?php echo adforest_return_echo($categories_style_html); ?>
                        <input type="hidden" name="alert_category" id="alert_category" value=""/>

                    </div>
                    <?php echo wp_kses_post($alert_end) ?>
                    <input type="hidden" name="alert_start" value="<?php echo esc_attr($today); ?>"/>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="submit" class="btn btn-theme" id="submit_alert">
                        <?php echo esc_html__('Submit', 'adforest'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>    