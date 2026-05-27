<?php
global $adforest_theme;
$allow_events = $adforest_theme['allow_event_create'] ? $adforest_theme['allow_event_create'] : false;
if (!$allow_events) {
    return;
}
?>
<?php echo adforest_dashboard_breadcrumb(esc_html__("Pending Events", "adforest")); ?>

<div class="row">
    <?php
    echo apply_filters('sb_get_event_list', 'pending');
    ?>
</div>
