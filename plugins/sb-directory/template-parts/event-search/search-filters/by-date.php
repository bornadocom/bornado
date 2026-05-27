<div class="col-sm-12 col-md-12 col-xs-12">
    <div class="form-group">
        <label class="control-label"><?php echo esc_html__('Date','sb_pro'); ?></label>
        <div class="row row-date-start-end">
            <div class="col-sm-5 col-md-5 col-xs-12 date-search">
                <input type="text" class="form-control " placeholder="<?php echo esc_html__('Start Date','sb_pro'); ?>"  autocomplete="off" id="event_start" name="by_date_start_filter" value="">
            </div>
            <div class="col-sm-5 col-md-5 col-xs-12 date-search">
                <input type="text" class="form-control " placeholder="<?php echo esc_html__('End Date','sb_pro'); ?>"  autocomplete="off" id="event_end" name="by_date_end_filter" value="">
            </div>
            <div class="col-sm-2 col-md-2 col-xs-12 date-search">
            <button id="get_start_date_filter" class="btn btn-theme btn-block start-end-filter btn-event-search" type="button"><span class="fa fa-search"></span></button>
            </div>
        </div>
    </div>
</div>