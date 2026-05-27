<?php echo adforest_dashboard_breadcrumb(esc_html__("Attendees", "adforest")); ?>

<?php
$event_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$user_id = get_current_user_id();

// Authorization check: Verify user has permission to view attendees
if ($event_id > 0) {
    $event = get_post($event_id);
    // Check if event exists and user is the owner or admin
    if (!$event || ($event->post_author != $user_id && !current_user_can('manage_options'))) {
        wp_die(__('You do not have permission to view this page.', 'adforest'));
    }
}

$all_attendees = get_post_meta($event_id, 'attending_users', true);
if (is_array($all_attendees) && !empty($all_attendees)) {
    $attendee_html = "";
    foreach ($all_attendees as $user) {
        $user_info = get_userdata($user);
        $poster_id = $user_info->ID;
        $poster_name = $user_info->display_name;
        $user_pic = adforest_get_user_dp($poster_id);
        $user_address = get_user_meta($poster_id, '_sb_address', true);

        $attendee_html .= '<div class ="col-3">
                                        <div class="attendee-container">
                                            <div class="attendee_avatr">
                                                <img src="' . $user_pic . '">
                                            </div>
                                            <div class="attendee_avatr">
                                                <a href=' . adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads') . '>' . $poster_name . '</a>                                       
                                            </div>
                                      </div>
                             </div>';
    }
    ?>
    <div class="col-lg-12">
        <div class="card-style">
            <div class="">
                <?php echo wp_kses_post($attendee_html) ?>
            </div>
        </div>
    </div>
    <?php
} else {
    $ads_list = '<div class="col-lg-12">
                    <div class="card-style">
                        <div class="">
                            <div class="alert alert-primary no-found-alert" role="alert">
                                ' . esc_html__('No Result Found for the following', 'adforest') . '
                            </div>
                        </div>
                    </div>
                 </div>';
    ?>

    <div class="row">
        <?php echo wp_kses_post($ads_list) ?>
    </div>
<?php }