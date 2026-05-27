<?php
$term_ID = $term_idz = $tax_name = $term_id = $queried_object = $term_ID = '';
$queried_object = get_queried_object();
if (!empty($queried_object) && count((array)$queried_object) > 0) {
    $term_id = $queried_object->term_id;
    $tax_name = $queried_object->taxonomy;
    if (!empty($term_id)) {
        $term_idz = get_term_by('id', $term_id, $tax_name);
        $term_ID = $term_idz->term_id;
        $term_name = $term_idz->name;
    }
}
//Get cats

$term_ID = isset($_GET['event_cat']) ? $_GET['event_cat'] : "";

$event_cats = adforest_get_cats('l_event_cat', 0);
if (is_array($event_cats) && count($event_cats) > 0) {
    ?>
    <div class="col-md-4 col-xs-12 col-sm-4">
        <div class="form-group">
            <div>
                <label class="control-label"><?php echo esc_html__('Category', 'sb_pro'); ?></label>
            </div>
            <select data-placeholder="<?php echo esc_html__('Select Event Category', 'sb_pro'); ?>" id="event_cat"
                    name="event_cat" class="allow_clear default-select">
                <option value=""><?php echo esc_html__('Select an option', 'sb_pro'); ?></option>
                <?php
                foreach ($event_cats as $cats) {
                    ?>
                    <option <?php if ($cats->term_id == $term_ID) { ?>selected="selected"<?php } ?>
                            value="<?php echo esc_attr($cats->term_id); ?>"><?php echo esc_html($cats->name); ?></option>
                    <?php
                }
                ?>
            </select>
        </div>
    </div>
    <?php
}
?>