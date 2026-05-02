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
$locations_html = "";
$args = array(
    'type' => 'html',
    'taxonomy' => 'event_loc',
    'tag' => 'option',
    'parent_id' => 0,
);
$locations_html = apply_filters('adforest_tax_hierarchy', $locations_html, $args);

?>
<div class="col-md-4 col-xs-12 col-sm-4">
    <div class="form-group">
        <label class="control-label"><?php echo esc_html__('Custom Location', 'sb_pro'); ?></label>
        <select data-placeholder="<?php echo esc_html__('Select Event Location', 'sb_pro'); ?>" id="event_custom_loc"
                name="event_custom_loc" class="allow_clear default-select">
            <option value=""><?php echo esc_html__('Select an option', 'sb_pro'); ?></option>
            <?php echo $locations_html; ?>
        </select>
    </div>
</div>