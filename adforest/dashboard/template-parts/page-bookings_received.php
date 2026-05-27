<?php
global $adforest_theme;
$allow_events = $adforest_theme['allow_booking_listing'] ? $adforest_theme['allow_booking_listing'] : false;
if (!$allow_events) {
    return;
} ?>

<div class="title-wrapper">
    <div class="row align-items-center">
        <div class="col-md-6">
            <div class="title">
                <h2><?php echo esc_html__('Received Bookings', 'adforest') ?></h2>
            </div>
        </div>
        <div class="col-md-6">
            <div class="breadcrumb-wrapper">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?php echo get_the_permalink() . "?page_type=bookings_received&makeCsv=yes"; ?>"
                               class="main-btn secondary-btn square-btn btn-hover adt-button-dark"> <?php echo esc_html__('Generate csv', 'adforest') ?> </a>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php
    echo apply_filters('sb_get_booking_list', 'publish');
    ?>
</div>
<?php
if (isset($_GET['makeCsv']) && $_GET['makeCsv'] == 'yes') {
    if (function_exists('sb_generate_booking_csv')) {
        sb_generate_booking_csv();
    }
}
?>