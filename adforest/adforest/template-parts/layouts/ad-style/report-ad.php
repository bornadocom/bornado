<?php
global $adforest_theme;
$pid = get_the_ID();
?>
<div class="modal fade report-quote" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn close" data-bs-dismiss="modal"><span
                            aria-hidden="true">&#10005;</span><span
                            class="sr-only"><?php echo __('Close', 'adforest'); ?></span></button>
                <div class="modal-title"><?php echo __('Why are you reporting this ad?', 'adforest'); ?></div>
            </div>
            <div class="modal-body">
                <form>
                    <div class="skin-minimal">
                        <div class="form-group col-md-12 col-sm-12">
                            <label><?php echo esc_html__("Type", "adforest"); ?></label>
                            <ul class="list">
                                <li><select class="alerts custom-select form-control default-select"
                                            id="report_option"><?php $options = explode('|', $adforest_theme['report_options']);
                                        foreach ($options as $option) { ?>
                                            <option
                                            value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option><?php } ?>
                                    </select></li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group  col-md-12 col-sm-12">
                        <label><?php echo esc_html__("Comments", "adforest"); ?></label>
                        <textarea placeholder="<?php echo __('Write your comments.', 'adforest'); ?>" rows="3"
                                  class="form-control" id="report_comments"></textarea>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12 col-sm-12 margin-bottom-20 margin-top-20">
                        <input type="hidden" id="ad_id" value="<?php echo esc_attr($pid); ?>"/>
                        <button type="button" class="adt-button-dark btn-block"
                                id="sb_mark_it"><?php echo __('Submit', 'adforest'); ?></button>
                        <input type="hidden" id="sb_report_ad_nonce" value="<?php echo wp_create_nonce("adforest_report_ad_nonce"); ?>">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>