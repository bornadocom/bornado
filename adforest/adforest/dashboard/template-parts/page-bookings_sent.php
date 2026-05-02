<?php
global $adforest_theme;
$allow_events = $adforest_theme['allow_booking_listing'] ? $adforest_theme['allow_booking_listing'] : false;
if (!$allow_events) {
    return;
}
?>

<?php echo adforest_dashboard_breadcrumb(esc_html__("Bookings Sent", "adforest")); ?>

<div class="row">
    <?php
    echo apply_filters('sb_get_booking_list', 'publish');
    ?>
</div>
