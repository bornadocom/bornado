<?php
namespace SbPro\Inc;

class Ajax_Actions {

    public function __construct() {
        add_action('wp_ajax_select2_ajax_ads', array($this, 'select2_ajax_ads_fun'));
        add_action('wp_ajax_upload_sb_pro_events_images', array($this, 'sb_pro_event_gallery'));
        add_action('wp_ajax_create_new_event', array($this, 'sb_pro_create_new_event'));
        add_action('wp_ajax_get_event_images', array($this, 'get_event_images_fun'));
        add_action('wp_ajax_delete_event_image', array($this, 'sb_pro_delete_event_images'));
        add_action('wp_ajax_my_new_event', array($this, 'sb_listing_my_new_event'));
        add_action('wp_ajax_remove_my_event', array($this, 'sb_remove_my_event'));
        add_action('wp_ajax_sb_allow_booking', array($this, 'sb_allow_booking_callback'));
        add_action('wp_ajax_sb_pro_create_booking', array($this, 'sb_create_booking_callback'));
        add_action('wp_ajax_nopriv_sb_pro_create_booking', array($this, 'sb_create_booking_callback'));
        add_action('wp_ajax_sb_booking_status', array($this, 'sb_booking_status_callback'));
        add_action('wp_ajax_sb_get_booking_details', array($this, 'sb_get_booking_details_callback'));
        add_action('wp_ajax_sb_get_calender_time', array($this, 'sb_get_calender_time_callback'));
        add_action('wp_ajax_nopriv_sb_get_calender_time', array($this, 'sb_get_calender_time_callback'));
        add_action('wp_ajax_sb_remove_booking', array($this, 'sb_remove_booking_callback'));
        add_action('wp_ajax_sb_get_booking_options', array($this, 'sb_get_booking_options_callback'));
        add_action('wp_ajax_sb_event_rating', array($this, 'sb_event_rating_callback'));
        add_action('wp_ajax_nopriv_sb_event_rating', array($this, 'sb_event_rating_callback'));
        add_action('wp_ajax_sb_event_rating_reply', array($this, 'adforest_ad_rating_reply_callback'));
        add_action('wp_ajax_nopriv_sb_event_rating_reply', array($this, 'adforest_ad_rating_reply_callback'));
        add_action('wp_ajax_sb_fav_event', array($this, 'sb_fav_event_callback'));
        add_action('wp_ajax_nopriv_sb_fav_event', array($this, 'sb_fav_event_callback'));
        add_action('wp_ajax_sb_ajax_search_events', array($this, 'sb_ajax_search_events_callback'));
        add_action('wp_ajax_nopriv_sb_ajax_search_events', array($this, 'sb_ajax_search_events_callback'));
        add_action('wp_ajax_sb_going_to_event', array($this, 'sb_going_to_event_callback'));
        add_action('wp_ajax_nopriv_sb_going_to_event', array($this, 'sb_going_to_event_callback'));
        add_action('wp_ajax_event_get_sub_states', array($this, 'event_get_sub_states_callback'));
        add_action('wp_ajax_nopriv_event_get_sub_states', array($this, 'event_get_sub_states_callback'));
        add_action('wp_ajax_sb_sort_event_images', array($this, 'sb_sort_event_images'));
    }

    /* Rearrange images */

    public function sb_sort_event_images() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        update_post_meta($_POST['ad_id'], 'downotown_event_arrangement_', $_POST['ids']);
        die();
    }

    public function event_get_sub_states_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $country_id = $_POST['country_id'];
        $event_country = adforest_get_cats('event_loc', $country_id, 0, 'events');
        if (count($event_country) > 0) {
            $cats_html = '<select class="category form-control">';
            $cats_html .= '<option label="' . esc_html__('Select Option', 'adforest') . '"></option>';
            foreach ($event_country as $ad_cat) {
                $cats_html .= '<option value="' . $ad_cat->term_id . '">' . $ad_cat->name . '</option>';
            }
            $cats_html .= '</select>';
            echo adforest_return_echo($cats_html);
            die();
        } else {
            echo "";
            die();
        }
    }

    function sb_going_to_event_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');

        $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : '';
        $status = isset($_POST['staus']) ? $_POST['staus'] : '';
        $user_id = get_current_user_id();
        $is_demo = sb_is_demo();
        $user_id = get_current_user_id();
        if ($is_demo) {
            wp_send_json_error(array('message' => esc_html__('Not Allowed in demo mode', 'sb_pro')));
        }
        $authenticate = sb_authenticate_check();
        if (!$authenticate) {
            wp_send_json_error(array('message' => esc_html__('Please login first', 'sb_pro')));
        }

        if ($event_id == "") {
            wp_send_json_error(array('message' => esc_html__('Select event id first', 'sb_pro')));
        }
        $all_attendees = get_post_meta($event_id, 'attending_users', true);

        $all_attendees = $all_attendees != "" ? $all_attendees : array();
        if ($status == 'no') {
            if (is_array($all_attendees) && ($key = array_search($user_id, $all_attendees)) !== false) {


                unset($all_attendees[$key]);
            }
            update_post_meta($event_id, 'attending_users', $all_attendees);
            wp_send_json_success(array('message' => esc_html__('Removed successfully', 'sb_pro')));
        } else {
            if (is_array($get_event_attending_user_ids) && empty($all_attendees)) {
                $all_attendees = array($user_id);
            } else {
                $all_attendees[] = $user_id;
            }
            update_post_meta($event_id, 'attending_users', $all_attendees);
            wp_send_json_success(array('message' => esc_html__('Added successfully', 'sb_pro')));
        }
    }

    function sb_ajax_search_events_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        global $adforest_theme;
        $params = array();
        $lat_lng_meta_query = array();
        parse_str($_POST['form_data'], $params);
        /* Listing Title */
        $event_title = '';
        if (isset($params['by_title']) && $params['by_title'] != "") {
            $event_title = $params['by_title'];
        }
        /* Categories */
        $category = '';
        if (isset($params['event_cat']) && $params['event_cat'] != "") {
            $category = array(
                array(
                    'taxonomy' => 'l_event_cat',
                    'field' => 'term_id',
                    'terms' => $params['event_cat'],
                    'include_children' => 1
                ),
            );
        }


        $custoom_location = '';
        if (isset($params['event_custom_loc']) && $params['event_custom_loc'] != "") {
            $custoom_location = array(
                array(
                    'taxonomy' => 'event_loc',
                    'field' => 'term_id',
                    'terms' => $params['event_custom_loc'],
                    'include_children' => 1
                ),
            );
        }
        /* Listing Street Address */
        $street_address = '';
        if (isset($params['location']) && $params['location'] != "") {
            $street_address = array(
                'key' => 'sb_pro_event_venue',
                'value' => $params['location'],
                'compare' => 'LIKE',
            );
        }
        /* Get start Event Date */
        $event_start_date = '';
        if (isset($params['by_date_start_filter']) && $params['by_date_start_filter'] != "") {
            $event_start_date = ($params['by_date_start_filter']);
        }
        /* Get End Event Date */
        $event_end_date = '';
        if (isset($params['by_date_end_filter']) && $params['by_date_end_filter'] != "") {
            $event_end_date = ($params['by_date_end_filter']);
        }
        $lat_lng_meta_query = array();
        if ($params['min_dis'] && $params['min_dis'] != "" && isset($params['event-lat']) && $params['event-lat'] != "") {
            $latitude = (isset($params['event-lat'])) ? $params['event-lat'] : '';
            $longitude = (isset($params['event-long'])) ? $params['event-long'] : '';
            $distance = (isset($params['min_dis'])) ? $params['min_dis'] : '';
            $data_array = array("latitude" => $latitude, "longitude" => $longitude, "distance" => $distance);
            if ($latitude != "" && $longitude != "") {
                $type_lat = "'DECIMAL'";
                $type_lon = "'DECIMAL'";
                $lats_longs = adforest_determine_minMax_latLong($data_array, false);
                if (isset($lats_longs) && count($lats_longs) > 0) {
                    //$lat_lng_meta_query['relation'] = 'AND';
                    $lat_lng_meta_query[] = array('key' => 'sb_pro_event_lat', 'value' => array($lats_longs['lat']['min'], $lats_longs['lat']['max']), 'compare' => 'BETWEEN', 'type' => 'DECIMAL',);
                    $lat_lng_meta_query[] = array('key' => 'sb_pro_event_long', 'value' => array($lats_longs['long']['min'], $lats_longs['long']['max']), 'compare' => 'BETWEEN', 'type' => 'DECIMAL',);
                }
            }
        }
        $event_date_query = '';
        if ($event_start_date != '' && $event_end_date != '') {
            $event_date_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'sb_pro_event_start_date',
                    'value' => $event_start_date,
                    'compare' => '>=',
                ),
                array(
                    'key' => 'sb_pro_event_end_date',
                    'value' => $event_end_date,
                    'compare' => '<=',
                ),
            );
        }
        $date_filter_query = "";
        $data_date = isset($_POST['data_date']) ? $_POST['data_date'] : "";
        if ($data_date != "") {
            $days = 0;
            $today_date = date('Y-m-d h:i a');
            if ($data_date == 'today') {
                $days = 1;
                $today_date = date('Y-m-d h:i a');
            } else if ($data_date == 'week') {
                $days = 7;
                $day_today = date('w');
                $today_date = date('Y-m-d h:i a', strtotime('-' . $day_today . ' days'));
            }if ($data_date == 'year') {
                $days = 365;
                $today_date = date('Y-m-d h:i a', strtotime(date('Y-01-01')));
            }
            if ($data_date == 'month') {
                $days = 30;
                $today_date = date('Y-m-d h:i a');
                $today_date = date('Y-m-d h:i a', strtotime(date('Y-m-01')));
            }
            if ($days > 0) {
                $next_date = date('Y-m-d  h:i a', strtotime($today_date . ' +' . $days . ' day'));
                $date_filter_query = array(
                    'key' => 'sb_pro_event_start_date',
                    'value' => array($today_date, $next_date),
                    'compare' => 'BETWEEN',
                        // 'type' => 'DATE'
                );
            }
        }
        /* only active events */
        $active_events = array(
            'key' => 'sb_pro_event_status',
            'value' => '1',
            'compare' => '='
        );
        $order = 'DESC';
        $order_by = 'date';
        if (isset($_POST['sort_by']) && $_POST['sort_by'] != "") {
            $orde_arr = explode('-', $_POST['sort_by']);
            $order = isset($orde_arr[1]) ? $orde_arr[1] : 'desc'; {
                $orderBy = isset($orde_arr[0]) ? $orde_arr[0] : 'ID';
            }
        }
        $page_no = '';
        if (isset($_POST['page_no'])) {
            $page_no = $_POST['page_no'];
        } else {
            $page_no = 1;
        }
        $grid_type = isset($_POST['grid_type']) ? $_POST['grid_type'] : 3;

        // Dynamic ACF Fields
        $additional_fields_query = [];
        if (isset($params['acf']) && is_array($params['acf'])) {
            foreach ($params['acf'] as $field_key => $value) {
                if (!empty($value)) {
                    $meta_key = get_meta_key_from_field_key($field_key);
                    if ($meta_key) {
                        $cleaned_key = substr($meta_key, 1);
                        $additional_fields_query[] = array(
                            'key' => $cleaned_key,
                            'value' => $value,
                            'compare' => 'LIKE',
                        );
                    }
                }
            }
        }


        /* Query */
        $args = array
            (
            's' => $event_title,
            'post_type' => 'events',
            'post_status' => 'publish',
            'tax_query' => array(
                $category,
                $custoom_location
            ),
            'meta_query' => array(
                $active_events,
                $street_address,
                $event_date_query,
                $lat_lng_meta_query,
                $date_filter_query,
                $additional_fields_query
            ),
            'order' => $order,
            'orderby' => $order_by,
            'paged' => $page_no,
        );
        $results = new \WP_Query($args);
        $fetch_output = "";
        $no_result = false;
        $total_posts = $results->found_posts;
        
        $grid_type  =   isset($_POST['grid_col']) &&  $_POST['grid_col'] != ""   ?   $_POST['grid_col']   : 4;
        $grid_cols = isset($adforest_theme['event_grid_col']) ? $adforest_theme['event_grid_col'] : "";
        if ($results->have_posts()) {
            while ($results->have_posts()) {
                $results->the_post();
                $event_id = get_the_ID();
                $grid_type = isset($adforest_theme['event_grid_type']) ? $adforest_theme['event_grid_type'] : "3";
                $function = "get_event_grid_type_$grid_type";
                $fetch_output .= $function($event_id, $grid_cols);
            }
        } else {
            $no_found = get_template_directory_uri() . '/images/nothing-found.png';
            $fetch_output = '<div class="col-xl-12 col-12 col-sm-12 col-md-12">
                                  <div class="nothing-found white search-bar">
                                    <img src="' . esc_url($no_found) . '" alt="">
                                    <h3>' . esc_html__("No Result Found", "sb_pro") . '</h3>
                                  </div> 
                             </div>';
            $no_result = true;
        }
        wp_reset_postdata();
        if (isset($_POST['pagination']) && $_POST['pagination'] != "") {
            ob_start();
            adforest_pagination_search($results);
            $output = ob_get_clean();
        }
        $event_meta= get_post_meta($event_id, '', true);
        wp_send_json_success(array('data' => $fetch_output, 'pagination' => $output, 'no_result' => $no_result, 'total' => $total_posts, 'additional_fields' => $additional_fields_query, "event_id" => $event_id));
    }

    // Add to favourites
    function sb_fav_event_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');

        $authenticate = sb_authenticate_check();
        if (!$authenticate) {
            wp_send_json_error(array('message' => esc_html__('Please login first', 'sb_pro')));
        }

        $is_demo = sb_is_demo();
        $user_id = get_current_user_id();

        if ($is_demo) {
            wp_send_json_error(array('message' => esc_html__('not Allowed in demo mode', 'sb_pro')));
        }
        $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : "";

        if (get_user_meta($user_id, '_sb_fav_event_' . $event_id, true) == $event_id) {

            delete_user_meta($user_id, '_sb_fav_event_' . $event_id);
            wp_send_json_success(array('message' => esc_html__('Removed from favourite', 'sb_pro')));
        } else {
            update_user_meta($user_id, '_sb_fav_event_' . $event_id, $event_id);

            wp_send_json_success(array('message' => esc_html__('Added in favourite events', 'sb_pro')));
        }
        die();
    }

    /* Ad rating */

    public function sb_event_rating_callback() {
        global $adforest_theme;
        check_ajax_referer('sb_review_secure', 'security');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }
        adforest_set_date_timezone();
        $sb_update_rating = isset($adforest_theme['sb_update_rating']) && $adforest_theme['sb_update_rating'] ? TRUE : FALSE;
        $sender_id = get_current_user_id();
        if (get_current_user_id() == "" || get_current_user_id() == 0) {
            echo '0|' . __("You are not logged in.", 'adforest');
            die();
        } else {

            $params = array();
            parse_str($_POST['sb_data'], $params);

            $rated_id = get_user_meta($sender_id, 'ad_ratting_' . $sender_id, true);
            $rated_already_flag = FALSE;
            if ($params['ad_id'] != '' && $params['ad_id'] == $rated_id) {
                $rated_already_flag = TRUE;
            }

            $sender = get_userdata($sender_id);

            if ($sender_id == $params['ad_owner']) {
                echo '0|' . __("Ad author can't post rating.", 'adforest');
                die();
            }

            if ($rated_already_flag && !$sb_update_rating) {
                echo '0|' . __("You've posted rating already.", 'adforest');
                die();
            }

            if (isset($adforest_theme['sb_update_rating']) && $adforest_theme['sb_update_rating']) {
                $args = array(
                    'type__in' => array('event_post_rating'),
                    'post_id' => $params['ad_id'],
                    'user_id' => $sender_id,
                    'number' => 1,
                    'parent' => 0,
                );
                $comment_exist = get_comments($args);
                if (count($comment_exist) > 0) {
                    $comment = array();
                    $comment['comment_ID'] = $comment_exist[0]->comment_ID;
                    $comment['comment_content'] = sanitize_textarea_field($params['rating_comments']);
                    wp_update_comment($comment);
                    update_comment_meta($comment_exist[0]->comment_ID, 'review_stars', $params['rating']);
                    if (isset($adforest_theme['sb_rating_email_author']) && $adforest_theme['sb_rating_email_author']) {
                        adforest_email_ad_rating($params['ad_id'], $sender_id, $params['rating'], $params['rating_comments']);
                    }
                    echo '1|' . __("Your rating has been updated.", 'adforest');
                    die();
                }
            }

            $time = date('Y-m-d H:i:s');
            $data = array(
                'comment_post_ID' => $params['ad_id'],
                'comment_author' => $sender->display_name,
                'comment_author_email' => $sender->user_email,
                'comment_author_url' => '',
                'comment_content' => sanitize_textarea_field($params['rating_comments']),
                'comment_type' => 'event_post_rating',
                'user_id' => $sender_id,
                'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                'comment_date' => $time,
                'comment_approved' => 1,
            );

            $comment_id = wp_insert_comment($data);
            if ($comment_id) {
                update_comment_meta($comment_id, 'review_stars', $params['rating']);
                update_user_meta($sender_id, 'ad_ratting_' . $sender_id, $params['ad_id']);
                if (isset($adforest_theme['sb_rating_email_author']) && $adforest_theme['sb_rating_email_author']) {
                    adforest_email_ad_rating($params['ad_id'], $sender_id, $params['rating'], $params['rating_comments']);
                }
                //do_action('adforest_wpml_comment_meta_updation', $comment_id, $params);
                echo '1|' . __("Your rating has been posted.", 'adforest');
                die();
            }
        }
    }

    /* event rating Reply */

    function adforest_ad_rating_reply_callback() {
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }
        check_ajax_referer('sb_review_reply_secure', 'security');
        adforest_set_date_timezone();
        if (get_current_user_id() == "") {
            echo '0|' . __("You are not logged in.", 'adforest');
            die();
        } else {

            global $adforest_theme;
            $params = array();
            parse_str($_POST['sb_data'], $params);

            $sender_id = get_current_user_id();
            $sender = get_userdata($sender_id);

            if ($sender_id != $params['ad_owner']) {
                echo '0|' . __("Only Ad owner can reply the rating.", 'adforest');
                die();
            }

            $args = array(
                'type__in' => array('event_post_rating'),
                'post_id' => $params['ad_id'],
                'user_id' => $sender_id,
                'number' => 1,
                'parent' => $params['parent_comment_id'],
            );
            $comment_exist = get_comments($args);
            if (count($comment_exist) > 0) {
                $comment = array();
                $comment['comment_ID'] = $comment_exist[0]->comment_ID;
                $comment['comment_content'] = $params['reply_comments'];
                wp_update_comment($comment);

                if (isset($adforest_theme['sb_rating_reply_email']) && $adforest_theme['sb_rating_reply_email']) {
                    $comment_data = get_comment($params['parent_comment_id']);
                    $rating = get_comment_meta($params['parent_comment_id'], 'review_stars', true);
                    adforest_email_ad_rating_reply($params['ad_id'], $comment_data->user_id, $params['reply_comments'], $rating, $comment_data->comment_content);
                }
                echo '1|' . __("Your reply has been updated.", 'adforest');
                die();
            }

            //$time = current_time('mysql');
            $time = date('Y-m-d H:i:s');

            $data = array(
                'comment_post_ID' => $params['ad_id'],
                'comment_author' => $sender->display_name,
                'comment_author_email' => $sender->user_email,
                'comment_author_url' => '',
                'comment_content' => $params['reply_comments'],
                'comment_type' => 'event_post_rating',
                'user_id' => $sender_id,
                'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                'comment_date' => $time,
                'comment_parent' => $params['parent_comment_id'],
                'comment_approved' => 1
            );

            $comment_id = wp_insert_comment($data);
            if ($comment_id) {
                if (isset($adforest_theme['sb_rating_reply_email']) && $adforest_theme['sb_rating_reply_email']) {
                    $comment_data = get_comment($params['parent_comment_id']);
                    $rating = get_comment_meta($params['parent_comment_id'], 'review_stars', true);
                    adforest_email_ad_rating_reply($params['ad_id'], $comment_data->user_id, $params['reply_comments'], $rating, $comment_data->comment_content);
                }
                echo '1|' . __("Your reply has been posted.", 'adforest');
                die();
            }
        }
    }

    public function sb_get_booking_options_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        global $adforest_theme;
        $is_demo = sb_is_demo();
        if ($is_demo) {
            wp_send_json_error(array('message' => esc_html__('not Allowed in demo mode', 'sb_pro')));
        }
        $ad_id = isset($_POST['ad_id']) ? $_POST['ad_id'] : "";
        if ($is_demo) {
            wp_send_json_error(array('message' => esc_html__('Ad id required', 'sb_pro')));
        }
        $ad_author = get_post_field('post_author', $ad_id);
        if ($ad_author != get_current_user_id()) {
            wp_send_json_error(array('message' => esc_html__('only author can do this', 'sb_pro')));
            die();
        }


        $booking_interval  =   get_post_meta($ad_id , 'booking_interval' , true);

        $booking_interval =  $booking_interval != ""  ? $booking_interval : 30;

        $widget_area = "";
        if (isset($adforest_theme['allow_timekit_booking']) && $adforest_theme['allow_timekit_booking']) {

            $widget_area = ' <div class="form-group has-feedback">
                                <label class="control-label">' . esc_html__('TimeKit widget', 'sb_pro') . '<span>*</span></label>
                                <div class="input-group">
            <textarea name = "timekit_widget_code" rows="5" placeholder ="window.timekitBookingConfig = {  app_key: , project_id:}">' . get_post_meta($ad_id, 'timekit_widget_code', true) . '</textarea>
                                </div>
                            </div>';
        }
        echo '<div class="col-lg-12 col-xl-12">						
                <div class="card card-default">
                    <div class="card-header card-header-border-bottom">
                        <h2>' . esc_html__('Update Booking', 'sb_pro') . '</h2>
                    </div>
                    <div class="card-body">
                        <form  id="update-booking-listing">                                   
                          <div class="input-style-1 has-feedback">
                                <label class="">' . esc_html__('Add minutes, time interval of booking', 'sb_pro') . '<span>*</span></label>
                                <div class="">
                                    <input class="" type="number" id="booking_interval" name = "booking_interval" value = "'.$booking_interval.'" data-parsley-required="true" min=0 max = 60>
                                </div>
                            </div>        
                          <div class="input-style-1 has-feedback">
                                <label class="">' . esc_html__('Select days to avoid booking', 'sb_pro') . '<span>*</span></label>
                                <div class="">
                                   <div  id="already_booked_day"></div>
                                </div>
                            </div>                          
                          ' . $widget_area . '
                            <div class="form-group has-feedback">                        
                                <div class="input-group">
                                   <button  type="submit" class="main-btn secondary-btn square-btn btn-hover adt-button-dark" data-style="expand-left">
                                    ' . esc_html__('Submit', 'sb_pro') . '</button>
                                 </div>
                            </div>       
                                <input type = "hidden"  id="booked_days"  name = "booked_days"  value=  "' . get_post_meta($ad_id, "booked_days", true) . '">
                                <input type ="hidden" value ="' . $ad_id . '"   name= "sb_ad_id">  
                          </form>
                    </div>
                </div></div>
                        ';
        die();
    }

    public function sb_remove_booking_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $is_demo = sb_is_demo();
        if ($is_demo) {
            wp_send_json_error(array('message' => esc_html__('not Allowed in demo mode', 'sb_pro')));
        }
        $ad_id = isset($_POST['ad_id']) ? $_POST['ad_id'] : "";
        if ($is_demo) {
            wp_send_json_error(array('message' => esc_html__('Ad id required', 'sb_pro')));
        }
        $ad_author = get_post_field('post_author', $ad_id);
        if ($ad_author != get_current_user_id()) {
            wp_send_json_error(array('message' => esc_html__('only author can do this', 'sb_pro')));
            die();
        }
        delete_post_meta($ad_id, 'is_ad_booking_allow');
        wp_send_json_success(array('message' => esc_html__('Removed Succesfully', 'sb_pro')));
    }

    public function sb_get_calender_time_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');

        $post = $_POST;
        $date = isset($post['date']) ? $post['date'] : "";
        $ad_id = isset($post['ad_id']) ? $post['ad_id'] : "";

     if(get_current_user_id() == "" ||  get_current_user_id() == "0"){

        $redirect_url = adforest_login_with_redirect_url_param(get_the_permalink($ad_id));
        wp_send_json_error(array('message' => esc_html__( 'Login first to make an appointment', 'sb_pro' ) ,'url'=>$redirect_url));
     }

        
        $timestamp = strtotime($date);
        $selected_day = date('l', $timestamp);
        $selected_month = date('m', $timestamp);
        $selected_month_name = date('F', $timestamp);
        $selected_date = date('d', $timestamp);
        $selected_year = date('Y', $timestamp);
        $date_data = array('date' => $selected_date, 'month' => $selected_month, 'month_name' => $selected_month_name, 'year' => $selected_year, 'day_name' => $selected_day);
        $current_day_today = strtolower(date('l', $timestamp));
        $is_open = get_post_meta($ad_id, '_timingz_' . $current_day_today . '_open', true);
        $timing_html = "";
        $status = 'closed';

        $always_open = 0;

        if (get_post_meta($ad_id, 'sb_pro_business_hours', true) == '1') {
            $always_open = 1;
        } else {
            $always_open = 2;
        }

        if ($is_open == 1 || $always_open = 1) {
            if ($always_open == 1) {
                $startTime = date('00:00:00');
                $endTime = date('24:00:00');
            } else {
                $startTime = date('H:i:s', strtotime(get_post_meta($ad_id, '_timingz_' . $current_day_today . '_from', true)));
                $endTime = date('H:i:s', strtotime(get_post_meta($ad_id, '_timingz_' . $current_day_today . '_to', true)));       
            }
            
      
            $startTime = strtotime($startTime);
            $endTime = strtotime($endTime);
            
             $booking_interval   =  get_post_meta($ad_id , 'booking_interval' , true);
             $booking_interval   =   $booking_interval  != ""  ?  $booking_interval  : 30;

            $interval = "$booking_interval mins";
            $current = time();
            $addTime = strtotime('+' . $interval, $current);
            $diff = $addTime - $current;
            $intervalEnd = $startTime + $diff;
            $count = 1;
            if ($endTime > $startTime) {
                $status = 'open';
                while ($startTime < $endTime) {
                    $appt_start = date(get_option('time_format'), (int) $startTime,);
                    $appt_end = date(get_option('time_format'), (int) $intervalEnd);
                    $timing_html .= '<div class="show_book_form">	
					         <label class="time-slot">
							  <span>'.$count.'</span> : 
                                                           <span class="start_time">' . $appt_start . '</span>
                                                          <span class="end_time">' . $appt_end . '</span>								
						  </label>
					  </div>';
                    $startTime += $diff;
                    $intervalEnd += $diff;
                    $count++;
                    /* will prevent from infinite loop */
                    if ($count == 97) {
                        return;
                    }
                }
            } else {
                $timing_html = '<div class="show_book_form_close">	
					         <label class="time-slot">
							  <span>' . esc_html__('Closed', 'sb_pro') . '</span>								
						  </label>
					  </div>';
            }
        } else {
            $timing_html = '<div class="show_book_form_close">	
					         <label class="time-slot">
							  <span>' . esc_html__('Closed', 'sb_pro') . '</span>								
						  </label>
					  </div>';
        }

        wp_send_json_success(array('date_data' => $date_data, 'timing_html' => $timing_html, 'status' => $status));
        die();
    }

    public function sb_get_booking_details_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $is_demo = sb_is_demo();

        $booking_id = isset($_POST['booking_id']) ? $_POST['booking_id'] : "";

        if ($booking_id == "") {
            wp_send_json_error(array('message' => esc_html__('Something went wrong', 'sb_pro')));
        } else {
            $booking_details = get_post_meta($booking_id, 'booking_details', true);
            $booking_details = $booking_details != "" ? json_decode($booking_details, true) : array();
            if (!empty($booking_details)) {
                $booker_name = $booking_details['booker_name'];
                $booker_email = $booking_details['booker_email'];
                $booker_phone = $booking_details['booker_phone'];
                $booking_slot_start = $booking_details['booking_slot_start'];
                $booking_slot_end = $booking_details['booking_slot_end'];

                $booking_date = $booking_details['booking_date'];
                $booking_month = $booking_details['booking_month'];
                $booking_day = $booking_details['booking_day'];
                $booking_ad_id = $booking_details['booking_ad_id'];

                $html = '<div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">'. esc_html__("Booking Details") .'</h5>
                                   <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                          <div class="row">
                              <div class="col-6">
                                  <div class= "form-group">
                                     <label> ' . esc_html__('Customer name', 'sb_pro') . '</label>
                               <p> ' . $booker_name . '  </p>
                                   </div>      
                              </div>
                              <div class="col-6">
                                  <div class= "form-group">
                                     <label> ' . esc_html__('Customer email', 'sb_pro') . '</label>
                                   <p>    ' . $booker_email . ' </p>
                                   </div>      
                              </div>
                              <div class="col-6">
                                  <div class= "form-group">
                                     <label> ' . esc_html__('Customer phone', 'sb_pro') . '</label>
                                 <p>      ' . $booker_phone . '   </p>
                                   </div>      
                              </div>
                              <div class="col-6">
                                  <div class= "form-group">
                                     <label> ' . esc_html__('Time slot', 'sb_pro') . '</label>
                                    <p>   ' . $booking_slot_start . '-' . $booking_slot_end . '   </p>
                                   </div>      
                              </div>
                             
                          </div>
                       
                         </div>
                         <div class="modal-footer">
                         <button type="button" class="btn btn-theme" data-dismiss="modal">Close</button>
                      </div>';
            }
            wp_send_json_success(array('message' => '', 'detail' => $html));
        }
    }

    public function sb_booking_status_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $is_demo = sb_is_demo();
        if ($is_demo) {
            wp_send_json_error(array('message' => esc_html__('not Allowed in demo mode', 'sb_pro')));
        }
        $extra_details = isset($_POST['extra_detail']) ? $_POST['extra_detail'] : "";
        $val = isset($_POST['val']) ? $_POST['val'] : "";
        $booking_id = isset($_POST['booking_id']) ? $_POST['booking_id'] : "";
        if ($val == "" || $val == "") {
            wp_send_json_error(array('message' => esc_html__('Something went wrong', 'sb_pro')));
        } else {
            if ($val == "2" || $val == "3") {
                do_action('sb_send_booking_status_change_email', $booking_id, $val, $extra_details);
            }
            update_post_meta($booking_id, 'booking_status', $val);
            wp_send_json_success(array('message' => esc_html__('Status updated succesfully', 'sb_pro')));
        }
    }

    public function sb_create_booking_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $user_id = get_current_user_id();
        $data = isset($_POST['data']) ? $_POST['data'] : "";
        $params = array();
        parse_str($data, $params);
        $booker_first_name = isset($params['booker_first_name']) ? sanitize_text_field($params['booker_first_name']) : "";
        $booker_last_name = isset($params['booker_last_name']) ? sanitize_text_field($params['booker_last_name']) : "";
        $booker_email = isset($params['booker_email']) ? sanitize_email($params['booker_email']) : "";
        $booker_phone = isset($params['booker_phone']) ? sanitize_text_field($params['booker_phone']) : "";
        $booker_comment = isset($params['booker_comment']) ? sanitize_text_field($params['booker_comment']) : "";
        $booking_ad_id = isset($params['booking_ad_id']) ? sanitize_text_field($params['booking_ad_id']) : "";
        $form_booking_day = isset($params['form_booking_day']) ? sanitize_text_field($params['form_booking_day']) : "";
        $form_booking_date = isset($params['form_booking_date']) ? sanitize_text_field($params['form_booking_date']) : "";
        $form_booking_month = isset($params['form_booking_month']) ? sanitize_text_field($params['form_booking_month']) : "";
        $form_booking_month_name = isset($params['form_booking_month_name']) ? sanitize_text_field($params['form_booking_month_name']) : "";
        $form_booking_year = isset($params['form_booking_year']) ? sanitize_text_field($params['form_booking_year']) : "";
        $form_slot_start = isset($params['form_slot_start']) ? sanitize_text_field($params['form_slot_start']) : "";
        $form_slot_end = isset($params['form_slot_end']) ? sanitize_text_field($params['form_slot_end']) : "";
        $booking_ad_id = isset($params['booking_ad_id']) ? sanitize_text_field($params['booking_ad_id']) : "";
        $author_id = get_post_field('post_author', $booking_ad_id);


         if(get_current_user_id() == "" ||  get_current_user_id() == "0"){

        $redirect_url = adforest_login_with_redirect_url_param(get_the_permalink($ad_id));
        wp_send_json_error(array('message' => esc_html__( 'Login first to make an appointment', 'sb_pro' ) ,'url'=>$redirect_url));
     }




        $args = array(
            'post_content' => $booker_comment,
            'post_status' => 'publish',
            'post_title' => $booker_first_name . ' ' . $booker_last_name . ' ' . '(' . get_the_title($booking_ad_id) . ')',
            'post_type' => 'sb_bookings',
            'post_author' => $user_id
        );
        $booking_id = wp_insert_post($args);
        if (!is_wp_error($booking_id)) {
            $booking_details = array();
            $booking_details['booker_name'] = $booker_first_name . "  " . $booker_last_name;
            $booking_details['booker_email'] = $booker_email;
            $booking_details['booker_phone'] = $booker_phone;
            $booking_details['booking_slot_start'] = $form_slot_start;
            $booking_details['booking_slot_end'] = $form_slot_end;
            $booking_details['booking_date'] = $form_booking_date;
            $booking_details['booking_month'] = $form_booking_month;
            $booking_details['booking_month_name'] = $form_booking_month_name;
            $booking_details['booking_day'] = $form_booking_day;
            $booking_details['booking_year'] = $form_booking_year;
            $booking_details['booking_ad_id'] = $booking_ad_id;
            update_post_meta($booking_id, 'booking_details', json_encode($booking_details));
            update_post_meta($booking_id, 'booking_org_date', strtotime($form_booking_year . "-" . $form_booking_month . "-" . $form_booking_date));
            update_post_meta($booking_id, 'booking_ad_owner', $author_id);
            update_post_meta($booking_id, 'booking_status', 1);
            wp_send_json_success(array('message' => __('Booking Created succesfully', 'sb_pro')));
        } else {
            wp_send_json_error(array('message' => __('something went wrong', 'sb_pro')));
        }
    }

    public function sb_allow_booking_callback() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $data = isset($_POST['sb_data']) ? $_POST['sb_data'] : "";
        $user_id = get_current_user_id();

    
        if ($data == "") {
            $message = __("Add id first", 'adforest');
            wp_send_json_error(array('message' => $message));
            die();
        } else {
            $params = array();
            parse_str($data, $params);
    
            $ad_id = isset($params['sb_ad_id']) ? $params['sb_ad_id'] : "";
            $booking_interval  =   isset($params['booking_interval']) ?  $params['booking_interval'] : 30; 
            $timekit_code = isset($params['timekit_widget_code']) ? $params['timekit_widget_code'] : "";
            if ($ad_id == "") {
                $message = __("Select ad first", 'sb_pro');
                wp_send_json_error(array('message' => $message));
                die();
            }
            $is_booking_allow = get_post_meta($ad_id, 'is_ad_booking_allow', true);
            $message = __("Added succesfully", 'sb_pro');
            if (isset($is_booking_allow) && $is_booking_allow != "") {
                $message = __("updated successfully", 'sb_pro');
            }

            update_post_meta($ad_id, 'booking_interval',  $booking_interval);
            update_post_meta($ad_id, 'is_ad_booking_allow', '1');
            update_post_meta($ad_id, 'timekit_widget_code', htmlspecialchars($timekit_code));
            update_post_meta($ad_id, 'booked_days', $params['booked_days']);
            wp_send_json_success(array('message' => $message));
            die();
        }
    }

    public function sb_remove_my_event() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $is_demo = adforest_is_demo();
        if ($is_demo) {
            echo '0|' . __("Not allowed in demo mode", 'adforest');
            die();
        }

        $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : "";
        $ad_author = get_post_field('post_author', $event_id);
        if ($ad_author != get_current_user_id()) {
            echo '0|' . __("You can not delete this event", 'adforest');
            die();
        }



        if ($event_id != "") {
            $event_id = $_POST['event_id'];
            if (wp_trash_post($event_id)) {
                echo '1|' . esc_html__("Event removed successfully.", 'sb_pro');
            } else {
                echo '0|' . esc_html__("There's some problem, please try again later.", 'sb_pro');
            }
        }
        die();
    }

    public function select2_ajax_ads_fun($param) {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $return = array();
        $user_id = get_current_user_id();
        if (is_user_logged_in()) {
            $is_active = array('key' => '_adforest_ad_status_', 'value' => 'active', 'compare' => '=',);
            $search_results = new \WP_Query(array(
                's' => esc_html($_GET['q']),
                'post_status' => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page' => 50,
                'post_type' => 'ad_post',
                'author' => $user_id,
                    //  'meta_query' => array($is_active),
            ));
            if ($search_results->have_posts()) :
                while ($search_results->have_posts()) : $search_results->the_post();
                    $title = ( mb_strlen($search_results->post->post_title) > 50 ) ? mb_substr($search_results->post->post_title, 0, 49) . '...' : $search_results->post->post_title;
                    $return[] = array($search_results->post->ID, $title, $disabled); // array( Post ID, Post Title )
                // shorten the title a little
                endwhile;
                wp_reset_postdata();
            endif;
        }
        echo json_encode($return);
        die();
    }

    // Event Images ...
    public function sb_pro_event_gallery() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        global $adforest_theme;

        // Check if user is logged in
        if (!is_user_logged_in() || get_current_user_id() == 0) {
            echo '0|' . esc_html__("You must be logged in to upload images.", 'sb_pro');
            die();
        }

        // Validate file upload
        if (!isset($_FILES['my_file_upload']) || $_FILES['my_file_upload']['error'] !== UPLOAD_ERR_OK) {
            echo '0|' . esc_html__("File upload error.", 'sb_pro');
            die();
        }

        // Validate MIME type
        $allowed_mime_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        $file_mime_type = mime_content_type($_FILES['my_file_upload']['tmp_name']);
        if (!in_array($file_mime_type, $allowed_mime_types)) {
            echo '0|' . esc_html__("Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed", 'sb_pro');
            die();
        }

        if (isset($adforest_theme['sb_pro_standard_images_size']) && $adforest_theme['sb_pro_standard_images_size']) {
            list($width, $height) = getimagesize($_FILES["my_file_upload"]["tmp_name"]);
            if ($width < 760) {
                echo '0|' . __("Minimum image dimension should be", 'sb_pro') . ' 750x450';
                die();
            }
            if ($height < 410) {
                echo '0|' . __("Minimum image dimension should be", 'sb_pro') . ' 750x450';
                die();
            }
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $size_arr = explode('-', $adforest_theme['sb_pro_event_images_size']);
        $display_size = $size_arr[1];
        $actual_size = $size_arr[0];
        
        // Improved file extension validation
        $file_name = sanitize_file_name($_FILES['my_file_upload']['name']);
        $file_parts = pathinfo($file_name);
        $imageFileType = isset($file_parts['extension']) ? strtolower($file_parts['extension']) : '';
        
        if (!in_array($imageFileType, array('jpg', 'jpeg', 'png', 'gif'))) {
            echo '0|' . esc_html__("Sorry, only JPG, JPEG, PNG and GIF files are allowed", 'sb_pro');
            die();
        }
        // Check file size
        if ($_FILES['my_file_upload']['size'] > $actual_size) {
            echo '0|' . esc_html__("Max allowed image size is", 'sb_pro') . " " . $display_size;
            die();
        }
        
        // Sanitize and validate event ID
        if (isset($_GET['is_update']) && $_GET['is_update'] != "") {
            $event_id = absint($_GET['is_update']);
        } else {
            $event_id = get_user_meta(get_current_user_id(), 'event_in_progress', true);
        }

        if ($event_id == "" || $event_id == 0) {
            echo '0|' . __("Please enter event title first in order to create event.", 'sb_pro');
            die();
        }

        // Verify user owns this event
        $event_author = get_post_field('post_author', $event_id);
        if ($event_author != get_current_user_id() && !current_user_can('manage_options')) {
            echo '0|' . esc_html__("You don't have permission to upload images to this event.", 'sb_pro');
            die();
        }
        // Check max image limit
        $media = get_attached_media('image', $event_id);
        if (count($media) >= $adforest_theme['sb_pro_event_upload_limit']) {
            $msg = esc_html__("Sorry you cant upload more than ", 'sb_pro');
            $images_l = esc_html__(" images ", 'sb_pro');
            echo '0|' . $msg . $adforest_theme['sb_pro_event_upload_limit'] . $images_l;
            die();
        }
        $attachment_id = media_handle_upload('my_file_upload', $event_id);
        if (!is_wp_error($attachment_id)) {
            $imgaes = get_post_meta($event_id, 'downotown_event_arrangement_', true);
            if ($imgaes != "") {
                $imgaes = $imgaes . ',' . $attachment_id;
                update_post_meta($event_id, 'downotown_event_arrangement_', $imgaes);
            } else {
                update_post_meta($event_id, 'downotown_event_arrangement_', $attachment_id);
            }
            echo '' . $attachment_id;
            die();
        } else {
            echo '0|' . esc_html__("Something went wrong please try later", 'sb_pro');
            die();
        }
    }

    /* Create Event By Title */

    public function sb_pro_create_new_event() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        if ($_POST['is_update'] != "") {
            die();
        }
        $event_title = sanitize_text_field($_POST['event_title']);
        if (get_current_user_id() == "" || get_current_user_id() == 0)
            die();
        if (!isset($event_title))
            die();
        $event_id = get_user_meta(get_current_user_id(), 'event_in_progress', true);

        if (get_post_status($event_id) && $event_id != "") {
            $my_post = array('ID' => $event_id, 'post_title' => $event_title);
            wp_update_post($my_post);
            die();
        }
        // Gather post data.
        $my_post = array(
            'post_title' => $event_title,
            'post_status' => 'pending',
            'post_author' => get_current_user_id(),
            'post_type' => 'events'
        );
        // Insert the post into the database.
        $id = wp_insert_post($my_post);
        if ($id) {
            update_user_meta(get_current_user_id(), 'event_in_progress', $id);
        }
        echo $id;
        die();
    }

// Fetch Event Images ...
    public function get_event_images_fun() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        if ($_POST['is_update'] != "") {
            $event_id = $_POST['is_update'];
        } else {
            $event_id = get_user_meta(get_current_user_id(), 'event_in_progress', true);
        }
        if ($event_id == "") {
            return '';
        }
        $path = '';
        $media = sb_pro_fetch_event_gallery($event_id);
        $result = array();
        foreach ($media as $m) {
            $mid = '';
            $guid = '';
            if (isset($m->ID)) {
                $mid = $m->ID;
                $source = wp_get_attachment_image_src($mid, 'sb_pro_user-dp');
                $path = $source[0];
            } else {
                $mid = $m;
                $source = wp_get_attachment_image_src($mid, 'sb_pro_user-dp');
                $path = $source[0];
            }

            $obj = array();
            $obj['dispaly_name'] = basename(get_attached_file($mid));
            ;
            $obj['name'] = $path;
            $obj['size'] = filesize(get_attached_file($mid));
            $obj['id'] = $mid;
            $result[] = $obj;
        }
        header('Content-type: text/json');
        header('Content-type: application/json');
        echo json_encode($result);
        die();
    }

    public function sb_pro_delete_event_images() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        if (get_current_user_id() == "" || get_current_user_id() == 0)
            die();
        if ($_POST['is_update'] != "") {
            $event_id = $_POST['is_update'];
        } else {
            $event_id = get_user_meta(get_current_user_id(), 'event_in_progress', true);
        }
        if (!is_super_admin(get_current_user_id()) && get_post_field('post_author', $event_id) != get_current_user_id())
            die();
        $attachmentid = $_POST['img'];
        wp_delete_attachment($attachmentid, true);
        if (get_post_meta($event_id, 'downotown_event_arrangement_', true) != "") {
            $ids = get_post_meta($event_id, 'downotown_event_arrangement_', true);
            $res = str_replace($attachmentid, "", $ids);
            $res = str_replace(',,', ",", $res);
            $img_ids = trim($res, ',');
            update_post_meta($event_id, 'downotown_event_arrangement_', $img_ids);
        }
        echo "1";
        die();
    }

    public function sb_listing_my_new_event() {
        check_ajax_referer('sb_pro_event_nonce', 'security');
        $is_demo = sb_is_demo();
        global $adforest_theme;
        if ($is_demo) {
            $message = __("Not allowed in demo mode", 'adforest');
            wp_send_json_error(array('message' => $message));
            die();
        }
        if (get_current_user_id() == "") {
            $message = __("Login First to create an event", 'adforest');
            wp_send_json_error(array('message' => $message));
            die();
        }
        // Getting values
        $params = array();
        parse_str(stripslashes($_POST['sb_data']), $params);
         $current_user_id =  get_current_user_id();

        $params_ads_package = $params['ads_package'];
        $packageDetails = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);

        if (isset($packageDetails[$params_ads_package])) {
            $package_single = $packageDetails[$params_ads_package];
        }

        $event_desc = isset($params['event_desc']) ? ($params['event_desc']) : "";
        $event_title = isset($params['event_title']) ? sanitize_text_field($params['event_title']) : "";
        $event_tagline = isset($params['event_tagline']) ? sanitize_text_field($params['event_tagline']) : "";
        $event_cat = isset($params['event_cat']) ? sanitize_text_field($params['event_cat']) : "";
        $event_number = isset($params['event_number']) ? sanitize_text_field($params['event_number']) : "";
        $event_email = isset($params['event_email']) ? sanitize_text_field($params['event_email']) : "";
        $event_date = isset($params['event_date']) ? sanitize_text_field($params['event_date']) : "";
        $event_start_date = isset($params['event_start_date']) ? sanitize_text_field($params['event_start_date']) : "";
        $event_end_date = isset($params['event_end_date']) ? sanitize_text_field($params['event_end_date']) : "";
        $event_venue = isset($params['sb_user_address']) ? sanitize_text_field($params['sb_user_address']) : "";
        $event_lat = isset($params['ad_map_lat']) ? sanitize_text_field($params['ad_map_lat']) : "";
        $event_long = isset($params['ad_map_long']) ? sanitize_text_field($params['ad_map_long']) : "";
        $event_parent_listing = isset($params['event_desc']) ? sanitize_text_field($params['sb_event_listing']) : ""; 


    if(strtotime($event_start_date) == strtotime($event_end_date) ||   strtotime($event_start_date) >   strtotime($event_end_date)){
        $message   =   esc_html__('Event end date must be greater than start date','sb_pro');
        wp_send_json_error(array('message' => $message,));
    }
  
    if (!is_super_admin($current_user_id) && $_POST['is_update'] == "") {
    //    $expiry = get_user_meta($current_user_id, '_sb_expire_ads', true);
        $expiry = '';
        $number_of_events = 0;

        if(isset($params['ads_package'])) {
            $selected_categories = get_user_meta($current_user_id, 'adforest_ads_package_details', true);
            if (is_array($selected_categories) && count($selected_categories) > 0) {
                foreach ($selected_categories as $package_id => $details) {
                    $number_of_events += intval($details['number_of_events']);
                    $expiry = $details['pkg_expiry_days'];
                }
            }
        } else {
            $number_of_events = get_user_meta(get_current_user_id(), 'number_of_events', true);
            $expiry = get_user_meta(get_current_user_id(), '_sb_expire_ads', true);
        }

        if($number_of_events == 0 || $number_of_events == ""){
           $message   =   esc_html__('Buy a package first to create and event','sb_pro');
           wp_send_json_error(array('message' => $message,));
        }
        if ($expiry != '-1') {
            if ($expiry < date('Y-m-d')) {
                wp_send_json_error(array('message' => 'Your package has expired.',));
            }
        } 
    }

       $message_posted = __("Event Created successfully", 'adforest');
       $product_ids = [];
       $deducted =0;

        // ad id to assign events
            $event_status = 'publish';
        if (isset($params['is_update']) && $params['is_update'] != "") {
              $message_posted = __("Event updated successfully", 'adforest');
              $event_id = $params['is_update'];
            if ($adforest_theme['sb_pro_event_up_approval'] == 'manual') {

                $event_status = 'pending';

            } else if (get_post_status($event_id) == 'pending') {

                $event_status = 'pending';
            }
        } else {
            if ($adforest_theme['sb_pro_event_approval'] == '0') {
                $event_status = 'pending';
                   do_action('sb_notify_on_new_event', $event_id, $val, $params);                   
            } else {
                $event_status = 'publish';
            }
            $event_id = get_user_meta(get_current_user_id(), 'event_in_progress', true);
            // Now user can post new ad
            delete_user_meta(get_current_user_id(), 'event_in_progress');
            //send email on event creation
            do_action('sb_notify_on_new_event', $event_id, $params);
            // $simple_ads = get_user_meta(get_current_user_id(), 'number_of_events', true);
            $number_of_events_new_pkg = 0;
            $selected_categories = get_user_meta($current_user_id, 'adforest_ads_package_details', true);
            if (is_array($selected_categories) && count($selected_categories) > 0) {
                foreach ($selected_categories as $package_id => $details) {
                    $number_of_events_new_pkg = intval($details['number_of_events']);
                }
            }

            $product_no_of_events = isset($package_single['number_of_events']) ? $package_single['number_of_events'] : '';

                if ($product_no_of_events > 0 && !is_super_admin(get_current_user_id())) {

                    $product_no_of_events = $product_no_of_events - 1;
                    $package_single['number_of_events'] = $product_no_of_events;
                    $packageDetails[$params_ads_package] = $package_single;
                    update_user_meta(get_current_user_id(), 'adforest_ads_package_details', $packageDetails);
                } elseif ($number_of_events != 0 || $number_of_events != "") {
                    $number_of_events = $number_of_events - 1;
                    update_user_meta(get_current_user_id(), 'number_of_events', $number_of_events);
                }
        }

         if($event_status   == "pending"){


            $message_posted = __("Waiting For admin approval", 'sb_pro');
         }
          

        $my_post = array(
            'ID' => $event_id,
            'post_title' => $event_title,
            'post_status' => $event_status,
            'post_content' => $event_desc,
            'post_name' => $event_title
        );
        wp_update_post($my_post);
        update_post_meta($event_id, 'sb_pro_event_status', '1');
        update_post_meta($event_id, 'sb_pro_event_contact', $event_number);
        update_post_meta($event_id, 'sb_pro_event_email', $event_email);
        update_post_meta($event_id, 'sb_pro_event_start_date', $event_start_date);
        update_post_meta($event_id, 'sb_pro_event_end_date', $event_end_date);
        update_post_meta($event_id, 'sb_pro_event_venue', $event_venue);
        update_post_meta($event_id, 'sb_pro_event_lat', $event_lat);
        update_post_meta($event_id, 'sb_pro_event_long', $event_long);
        update_post_meta($event_id, 'sb_pro_event_listing_id', $event_parent_listing);
        update_post_meta($event_id, 'adforest_event_category', $event_cat);
        error_log("Tags Before setting: " . print_r($params['tags'], true));
        if (isset($params['tags']) && $params['tags'] != "") {
            $tags = explode(',', $params['tags']);
            error_log("Tags Array: " . print_r($tags, true));
            wp_set_object_terms($event_id, $tags, 'event_tags');
        }

        if ($event_cat != "") {
            wp_set_post_terms($event_id, $event_cat, 'l_event_cat');
        }
        /* check if question are set */
        $event_questions = isset($params['event_question']) ? $params['event_question'] : "";
        $arrc = $questions = array();
        $proData = sb_convert_to_array($event_questions);
        $countNum = (isset($proData['arr']['question']) && is_array($proData['arr']['question'])) ? count($proData['arr']['question']) : 0;
        for ($i = 0; $i <= $countNum; $i++) {
            $arr = $proData['arr'];
            if (isset($arr['question'][$i]) && $arr['question'][$i] != "") {
                $arrc['question'] = sanitize_text_field($arr['question'][$i]);
                $arrc['answer'] = sanitize_text_field($arr['answer'][$i]);
                $questions[] = $arrc;
            }
        }
        if (!empty($questions)) {
            update_post_meta($event_id, 'event_question', $questions);
        }
        /* check if schedules are there set */
        $event_schedules = isset($params['event_schedules']) ? $params['event_schedules'] : "";
        $arrc = $schedules = array();
        $proData = sb_convert_to_array($event_schedules);
        $countNum = (isset($proData['arr']['day']) && is_array($proData['arr']['day'])) ? count($proData['arr']['day']) : 0;
        for ($i = 0; $i <= $countNum; $i++) {
            $arr = $proData['arr'];
            if (isset($arr['day'][$i]) && $arr['day'][$i] != "") {
                $arrc['day'] = sanitize_text_field($arr['day'][$i]);
                $arrc['day_val'] = $arr['day_val'][$i];
                $schedules[] = $arrc;
            }
        }
        if (!empty($questions)) {
            update_post_meta($event_id, 'event_schedules', $schedules);
        }

        $countries = array();
        if (isset($params['event_country']) && $params['event_country'] != "") {
            $countries[] = $params['event_country'];
        }
        if (isset($params['event_country_states']) && $params['event_country_states'] != "") {
            $countries[] = $params['event_country_states'];
        }
        if (isset($params['event_country_cities']) && $params['event_country_cities'] != "") {
            $countries[] = $params['event_country_cities'];
        }
        if (isset($params['event_country_towns']) && $params['event_country_towns'] != "") {
            $countries[] = $params['event_country_towns'];
        }

        wp_set_post_terms($event_id, $countries, 'event_loc');

          if(isset($_POST['is_update'] )  &&  $_POST['is_update'] == ""){  
            $events_count = get_user_meta($current_user_id, 'number_of_events', true);
                if ($events_count > 0 && !is_super_admin($current_user_id)) {
                    $events_count = $events_count - 1;
                    update_user_meta($current_user_id, 'number_of_events', $events_count);
                }
            }

        //saving custom fields//
        if (isset($params['acf']) && $params['acf'] != '' && class_exists('ACF')) {
            exertio_framework_acf_clear_object_cache($event_id);
            acf_update_values($params['acf'], $event_id);
        }

        /* == wpml duplicate post if switch on == */
        do_action('adforest_duplicate_posts_lang', $event_id, 'events');
        $event_update_url = '';
        $event_update_url = (get_the_permalink($event_id));


        $product_event_no=get_post_meta($params_ads_package, 'number_of_events', true);
        wp_send_json_success(array('message' => $message_posted, 'url' => $event_update_url, "product_event_no" =>  $product_event_no, "deducted" => $deducted));
        die();
    }

    /* Create New Event... */
}

new Ajax_Actions();
// Get Event Media Images
if (!function_exists('sb_is_demo')) {

    function sb_is_demo() {
        global $adforest_theme;
        $restrict_phone_show = ( isset($adforest_theme['is_demo']) ) ? $adforest_theme['is_demo'] : false;
        return $restrict_phone_show;
    }

}

// Function to get the meta key from the field key
function get_meta_key_from_field_key($field_key) {
    global $wpdb;
    $meta_key = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT meta_key FROM $wpdb->postmeta WHERE meta_value = %s LIMIT 1",
            $field_key
        )
    );
    return $meta_key;
}