<?php
global $adforest_theme;
$allow_events = $adforest_theme['allow_event_create'] ? $adforest_theme['allow_event_create'] : false;
if (!$allow_events) {
    return;
}

if (function_exists('adforest_check_event_validity')) {
    if (isset($_GET['id'])) {

    } else {
        adforest_check_event_validity();
    }
}
?>
<div class="content-wrapper">
    <div class="content">
        <?php
        echo apply_filters('events_stats', '');
        ?>
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                <?php
                echo apply_filters('sb_get_event_creat_form', '');
                ?>
            </div>

        </div>
    </div>
</div>

<?php
// echo  apply_filters( 'sb_get_recent_event_list','');
?> 