<?php
$by_title = '';
//selective
if (isset($_GET['by_title']) && $_GET['by_title'] != "") {
    $by_title = $_GET['by_title'];
}
?>
<div class="col-md-4 col-xs-12 col-sm-4">
    <div class="form-group">
        <label class="control-label"><?php echo esc_html__('Title', 'sb_pro'); ?></label>
        <div class="input-group">
            <input type="text" class="form-control " placeholder="<?php echo esc_html__('Seach by title', 'sb_pro'); ?>"  autocomplete="off" name="by_title" value="<?php echo esc_attr($by_title); ?>">           
            <button id="get_title" class="btn btn-theme btn-default btn-event-search" type="button"><span class="fa fa-search"></span></button>
        </div>
    </div>   
</div>