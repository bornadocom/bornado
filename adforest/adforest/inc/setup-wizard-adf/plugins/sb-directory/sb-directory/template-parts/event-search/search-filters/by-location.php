<?php
$venue = '';
//selective
if (isset($_GET['by_location']) && $_GET['by_location'] != "") {
    $venue = $_GET['by_location'];
}
?>

<div class="col-md-4 col-xs-12 col-sm-4">
    <div class="form-group with-top-bar clearfix row">
        <label class="control-label"><?php echo esc_html__('Location', 'sb_pro'); ?></label>
        <div class="input-group">
            <input name="location" class="form-control pac-target-input" id="sb_user_address"
                   placeholder="<?php echo esc_html__('Type Location...', 'sb_pro'); ?>" type="text"
                   data-parsley-required="true" data-parsley-error-message="" value="" autocomplete="off">
            <i id="you_current_location_text" class="fa fa-bullseye"></i>
            <button id="get_title" class="btn btn-theme btn-default btn-event-search" type="button"><span
                        class="fa fa-search"></span></button>
        </div>
    </div>
</div>