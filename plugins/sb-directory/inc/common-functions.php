<?php
ob_start();
function sb_generate_booking_csv()
{
    $event_title = get_the_title();
    $attendees_data = array(
        array('username', 'Phone', 'Email', 'Time Slot', 'Date', 'Ad title', 'Ad owner'),
    );
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="bookings.csv');
    ob_end_clean();
    $f = fopen('php://output', 'w');
    $user_id = get_current_user_id();
    $paged = get_query_var('paged', 1);
    $args = array(
        'post_type' => 'sb_bookings',
        'post_status' => 'publish',
        'posts_per_page' => get_option('posts_per_page'),
        'paged' => $paged,
        'meta_query' => array(
            array(
                'key' => 'booking_ad_owner',
                'value' => $user_id,
                'compare' => '=',
            )
        )
    );
    $args = apply_filters('adforest_wpml_show_all_posts', $args);
    $query = new \WP_Query($args);
    $html = "";
    $count = 0;
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $booking_id = get_the_ID();
            $booking_status = get_post_meta($booking_id, 'booking_status', true);
            $booking_details = get_post_meta($booking_id, 'booking_details', true);
            $booking_details = json_decode($booking_details, true);
            $booker_name = isset($booking_details['booker_name']) ? $booking_details['booker_name'] : "";
            $booker_email = isset($booking_details['booker_email']) ? $booking_details['booker_email'] : "";
            $booker_phone = isset($booking_details['booker_phone']) ? $booking_details['booker_phone'] : "";
            $booking_slot_start = isset($booking_details['booking_slot_start']) ? $booking_details['booking_slot_start'] : "";
            $booking_slot_end = isset($booking_details['booking_slot_end']) ? $booking_details['booking_slot_end'] : "";
            $booking_date = isset($booking_details['booking_date']) ? $booking_details['booking_date'] : "";
            $booking_ad = isset($booking_details['booking_ad_id']) ? $booking_details['booking_ad_id'] : "";
            $booking_ad_title = $booking_ad != "" ? get_the_title($booking_ad) : "";
            $formated_date = "";
            $booking_org_date = get_post_meta($booking_id, 'booking_org_date', true);
            if ($booking_org_date != "") {

                $formated_date = date(get_option('date_format'), $booking_org_date);
            }
            $formated_slot = $booking_slot_start . "-" . $booking_slot_end;
            if ($booking_status == 2) {
                $booking_status_text = esc_html__('Accepted', 'sb_pro');
            } else if ($booking_status == 3) {
                $booking_status_text = esc_html__('Rejected', 'sb_pro');
            } else {
                $booking_status_text = esc_html__('Pending', 'sb_pro');
            }
            $attendees_data[] = array($booker_name, $booker_phone, $booker_email, $formated_slot, $formated_date, $booking_ad_title, 'tasawariii');
            $count++;
        }
    }
    foreach ($attendees_data as $line) {
        fputcsv($f, $line, ',');
    }
    fclose($f);
    exit();
}
function sb_convert_to_array($data = array())
{
    $count = 0;
    $arr = array();
    foreach ($data as $key => $val) {
        $key = str_replace("'", "", $key);
        $arr[ $key ] = $val;
    }
    $count = count($arr);
    return array("count" => $count, "arr" => $arr);
}
// Get Event Media Images
if (!function_exists('sb_pro_fetch_event_gallery')) {
    function sb_pro_fetch_event_gallery($event_id)
    {
        global $sb_pro_options;
        $re_order = get_post_meta($event_id, 'downotown_event_arrangement_', true);
        if ($re_order != "") {
            return explode(',', $re_order);
        } else {
            global $wpdb;
            $query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent = '" . $event_id . "'";
            $results = $wpdb->get_results($query, OBJECT);
            return $results;
        }
    }
}
// Return Event Images media
if (!function_exists('sb_pro_return_event_idz')) {
    function sb_pro_return_event_idz($media, $thumbnail_size)
    {
        global $sb_pro_options;
        if (count($media) > 0) {
            $i = 1;
            foreach ($media as $m) {
                if ($i > 1)
                    break;
                $mid = '';
                if (isset($m->ID)) {
                    $mid = $m->ID;
                } else {
                    $mid = $m;
                }
                if (wp_attachment_is_image($mid)) {
                    $image = wp_get_attachment_image_src($mid, $thumbnail_size);
                    return $image[0];
                } else {
                    return adforest_get_ad_default_image_url();
                }
            }
        } else {
            return adforest_get_ad_default_image_url();
        }
    }
}
// Get Listing Owner Details
if (!function_exists('sb_pro_event_owner')) {
    function sb_pro_event_owner($listing_id, $field = '')
    {
        if ($user_info != "") {
            if ($field == 'id') {
                return $get_owner_id = $get_owner_id;
            }
            if ($field == 'dp') {
                return sb_pro_listing_get_user_dp($get_owner_id, 'sb_pro_listing_user-dp');
            }
            if ($field == 'name') {
                return $user_info->display_name;
            }
            if ($field == 'email') {
                return $user_info->user_email;
            }
            if ($field == 'location') {
                return $user_info->d_user_location;
            }
            if ($field == 'url') {
                $author_posts_url = '';
                //$author_posts_url = sb_pro_listing_set_url_param(get_author_posts_url($get_owner_id), 'type', 'listings');
                $author_posts_url = sb_pro_listing_set_url_params_multi(get_author_posts_url($get_owner_id), array('type' => 'listings'));
                return esc_url(sb_pro_listing_page_lang_url_callback($author_posts_url));
            }
            if ($field == 'contact') {
                return $user_info->d_user_contact;
            }
        } else {
            return '';
        }
    }
}

if (!function_exists('event_timer_html')) {

    function event_timer_html($bid_end_date, $show_unit = true, $unit_style = 1, $style = 1)
    {
        global $adforest_theme;
        $bid_end_date = date("Y-m-d H:i:s", strtotime($bid_end_date));
        if ($bid_end_date == "")
            return '';
        $days = $hours = $minutes = $seconds = '';
        if ($show_unit) {
            $days = '<span class="timer-div colour-1">' . __('Days', 'adforest') . '</small>';
            $hours = '<span class="timer-div colour-2">' . __('Hours', 'adforest') . '</small>';
            $minutes = '<span class="timer-div colour-3">' . __('minutes', 'sb_pro') . '</small>';
            $seconds = '<span class="timer-div colour-4">' . __('seconds', 'sb_pro') . '</small>';
        }
        $mt_rand = mt_rand();

        $ext_class = $style == 2 ? "counter-box" : "";

        $html = '<ul class="clock ' . esc_attr($ext_class) . '" data-rand="' . esc_attr($mt_rand) . '" data-date="' . $bid_end_date . '"><li class="column-time clock-days"><span class="bidding_timer days-' . esc_attr($mt_rand) . '  colour-1" id="days-' . esc_attr($mt_rand) . '"></span>' . $days . '</li><li class="column-time"><span class="bidding_timer colour-2 hours-' . esc_attr($mt_rand) . '" id="hours-' . esc_attr($mt_rand) . '"></span>' . $hours . '</li><li class="column-time"><span class="bidding_timer  colour-3 minutes-' . esc_attr($mt_rand) . '" id="minutes-' . esc_attr($mt_rand) . '"></span>' . $minutes . '</li><li class="column-time"><span class="bidding_timer colour-4 seconds-' . esc_attr($mt_rand) . '" id="seconds-' . esc_attr($mt_rand) . '"></span>' . $seconds . '</li></ul>';
        return $html;
    }
}

if (!function_exists('sb_authenticate_check')) {

    function sb_authenticate_check()
    {
        if (get_current_user_id() == "" || get_current_user_id() == 0) {
            return false;
        }
        return true;
    }
}

function get_event_grid_type_2($event_id, $col = 4)
{
    $col_size = "col-xl-$col col-lg-$col col-md-$col col-12 col-sm-6 masonery_item";
    $animation = 'foo';
    $clock_icon = $event_dates = $event_start = '';
    $event_venue_loc = $event_end = '';
    //get media
    $media = sb_pro_fetch_event_gallery($event_id);
    $event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
    $event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
    $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
    // $categories = sb_pro_listing_events_assigned_cats($event_id);
    if ($event_venue != "") {
        $event_venue_loc = '<span><i class="fa fa-location-arrow"></i>' . $event_venue . '</span>';
    }
    if ($event_start_date != "" && $event_end_date != "") {
        $event_start = date_i18n(get_option('date_format'), strtotime($event_start_date));
        $event_end = date_i18n(get_option('date_format'), strtotime($event_end_date));

        $event_dates = '<div class="event-dates">
					' . $event_start . ' - ' . $event_end . '
				</div>';
    }

    $event_start_date_timer = date('Y-m-d H:i:s', strtotime($event_start_date));
    $event_timer = "";
    if ($event_start_date_timer > date('Y-m-d H:i:s')) {
        $event_timer = event_timer_html($event_start_date, true, 2);
    } else {
        $event_timer = event_get_default_timer();
    }
    $clock_icon = '<div class="sb_pro_listing_timer-icon"><i class="tool-tip fa fa-clock-o"  title="' . esc_html__('Event Will Begin In', 'sb_pro-listing') . '"></i></div>';
    $custom_color = '';
    //if event is started
    if (sb_pro_listing_check_event_starting($event_id) == '0') {
        $custom_color = 'eventz-statred';
        $clock_icon = '<div class="sb_pro_listing_timer-icon green-clock"><i class="tool-tip fa fa-clock-o"  title="' . esc_html__('Event Started', 'sb_pro-listing') . '"></i></div>';
    }
    //user dp
    $poster_id = get_post_field('post_author', $event_id);
    $user_info = get_userdata($poster_id);
    $poster_name = $user_info->display_name;
    $get_user_dp = adforest_get_user_dp($poster_id);
    $user_address = get_user_meta($poster_id, '_sb_address', true);
    $replace_title = stripslashes_deep(wp_strip_all_tags(str_replace("|", " ", get_the_title($event_id))));
    return '<div class="' . esc_attr($col_size) . '">
        <div class="list-contain-area ' . esc_attr($animation) . '  event-grid-1">
          <div class="list-boxes-submit-area">
		  
            <div class="list-style-images-area"><a  href="' . get_the_permalink($event_id) . '"> 
            <img src="' . sb_return_event_image_id($media, 'adforest_single_product') . '" alt="' . $replace_title . '" class="event-img img-responsive"></a>
			
			 <div class="profile-avtar">
				<a href="' . esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')) . '"><img src="' . $get_user_dp . '" class="img-responsive" alt="' . $replace_title . '"></a>
			 </div>
			<div class="overlays list-contain-text"> 
              <h2><a  href="' . get_the_permalink($event_id) . '">' . $replace_title . '</a></h2>
			  ' . $event_dates . '
              ' . $event_venue_loc . '
          </div>
            </div>
          <div class="list-bottom-area ' . $custom_color . '">
			  ' . $clock_icon . '
			  <div class="sb_pro_listing_timer-count">
					<div class="sb_pro_countdown-timer">
						<div class="timer-countdown-box">
							' . $event_timer . '
						</div>
					</div>
			  </div>
		   </div>
        </div>
      </div></div>';
}

function get_event_grid_type_1($event_id, $col = 4)
{
    $col_size = "col-12 col-sm-6 col-md-6 col-lg-$col col-xl-$col col-xxl-$col masonery_item";
    $animation = 'foo';
    $event_dates = $event_start = '';
    $event_venue_loc = $event_end = '';

    // Get media
    $media = sb_pro_fetch_event_gallery($event_id);
    $event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
    $event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
    $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);

    $cats_html = '';
    $post_categories = wp_get_object_terms($event_id, array('l_event_cat'), array('orderby' => 'term_group'));
    if (!empty($post_categories) && is_array($post_categories)) {
        foreach ($post_categories as $c) {
            $cat = get_term($c);
            $cats_html .= '<a href="' . get_term_link($cat->term_id) . '">' . esc_html($cat->name) . '</a> ';
        }
    }

    if (!empty($event_venue)) {
        $event_venue_loc = '<span><i class="fa fa-location-arrow"></i>' . esc_html($event_venue) . '</span>';
    }

    if (!empty($event_start_date) && !empty($event_end_date)) {
        $event_start = date_i18n(get_option('date_format'), strtotime($event_start_date));
        $event_end = date_i18n(get_option('date_format'), strtotime($event_end_date));
        $event_dates = '<p style="color: #ffffff !important;" class="txt">' . $event_start . ' - ' . $event_end . '</p>';
    }

    $event_start_date_timer = date('Y-m-d H:i:s', strtotime($event_start_date));
    $event_timer_html = '';
    if ($event_start_date_timer > date('Y-m-d H:i:s')) {
        // Add event timer
        $event_timer_html = '
            <div class="event-bid-time" data-event-date="' . esc_attr($event_start_date_timer) . '">
                <span class="days">00</span>
                <span class="hours">00</span>
                <span class="minutes">00</span>
                <span class="seconds">00</span>
            </div>';
    }

    $poster_id = get_post_field('post_author', $event_id);
    $user_info = get_userdata($poster_id);
    $poster_name = $user_info->display_name;
    $get_user_dp = adforest_get_user_dp($poster_id);
    $replace_title = wp_trim_words(get_the_title($event_id), 3, '...');

    return '<div class="' . $col_size . '">
                <div class="item">
                    <div class="adt-event-list-card">
                        <a href="' . get_the_permalink($event_id) . '">
                            <img class="main_event_image" src="' . sb_return_event_image_id($media, 'adforest_single_product') . '" alt="' . esc_attr($replace_title) . '">
                        </a>
                        ' . $event_timer_html . '
                        <div class="event-list-content">
                            <span><i class="fas fa-calendar-alt"></i>' . $event_dates . '</span>
                            <a href="' . get_the_permalink($event_id) . '"><h6>' . get_the_title($event_id) . '</h6></a>
                            <div class="meta">
                                <img src="' . esc_url($get_user_dp) . '" alt="author">
                                <span>' . esc_html($poster_name) . '</span>
                                <i class="far fa-eye"></i>
                                <span>' . esc_html(adforest_getPostViews($event_id)) . ' ' . esc_html__('Views', 'adforest') . '</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
}

function get_event_grid_type_3($pid, $col = 4)
{
    global $adforest_theme;
    $img = '';
    $event_id = $pid;
    $media = sb_pro_fetch_event_gallery($event_id);
    $ad_title = get_the_title();
    if (function_exists('adforest_title_limit')) {
        $ad_title = adforest_title_limit($ad_title);
    }
    $cats_html = '';
    $post_categories = wp_get_object_terms($pid, array('l_event_cat'), array('orderby' => 'term_group'));
    if (isset($post_categories) && !empty($post_categories) && is_array($post_categories)) {
        foreach ($post_categories as $c) {
            $cat = get_term($c);
            $cats_html = ' <a href="' . get_term_link($cat->term_id) . '" class="">' . esc_html($cat->name) . '</a> ';
            // break;
        }
    }
    $author_id = get_post_field('post_author', $pid);
    $html = '';
    // featured code
    $ribbion = 'featured-ribbon';
    if (is_rtl()) {
        $ribbion = 'featured-ribbon-rtl';
    }
    $is_feature = '';
    if (get_post_meta(get_the_ID(), '_adforest_is_feature', true) == '1') {
        $is_feature = '<div class="' . esc_attr($ribbion) . '"><span>' . __('Featured', 'adforest') . '</span></div>';
    }
    $event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
    $event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
    $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
    $event_start_date_timer = date('Y-m-d H:i:s', strtotime($event_start_date));
    $timer_html = "";
    if ($event_start_date_timer > date('Y-m-d H:i:s')) {
        $date_formated = date("Y-m-d H:i:s", strtotime($event_start_date));
        $timer_html .= '<div class="listing-bidding">' . event_timer_html($date_formated, false) . '</div>';
    }
    $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
    $day_content = '<div class="event-day-date">
                       <div class="event-date">
                            <span>' . date('d', strtotime($event_start_date)) . '</span>
                          <span>' . date('M', strtotime($event_start_date)) . '</span>
                      </div>
                   </div>';

    $time_content = date('h:i a', strtotime($event_start_date)) . ", " . date('D', strtotime($event_start_date));


    $col2_style = "col-12";
    if ($col == 0) {
        $html .= '<div class="item">';
    } else {
        $html .= '<div class="col-lg-' . $col . '  col-xl-' . esc_attr($col) . ' ' . $col2_style . ' col-md-6  col-sm-6 masonery_item" >';
    }

    $img = '<a href="' . get_the_permalink() . '"><img class="main-img" src="' . sb_return_event_image_id($media, 'adforest_single_product') . '" alt="' . $ad_title . '"></a>';
    $html .= ' <div class="prop-newest-main-section event-grid-3  ad-grid-8"><div class="prop-newest-image">  ' . ($img) . $timer_html . $day_content . '</div><div class="prop-main-contents"><div class="prop-real-estate-box"><div class="prop-estate-advertisement"><div class="prop-estate-text-section"><div class="prop-estate-rent"> ' . ($cats_html) . ' </div><a href="' . get_the_permalink() . '"><h2>' . esc_html($ad_title) . '</h2></a><p><i class="fa fa-map-marker"></i>' . adforest_ad_locations_limit($event_venue) . '</p></div></div><div class="prop-estate-table"><ul class="list-inline prop-content-area"><li><i class="fa fa-clock-o"></i><span class="items">' . $time_content . '</span></li><li><i class="fa fa-eye"></i><span class="items">' . adforest_getPostViews(get_the_ID()) . ' ' . __('Views', 'adforest') . '</span></li></ul></div></div></div></div></div>';

    $div_col_num = "<div>";
    if ($col == 0) {
        $div_col_num .= '<div class="item">';
    } else {
        $div_col_num .= '<div class="col-lg-' . $col . '  col-xl-' . esc_attr($col) . ' ' . $col2_style . ' col-md-6  col-sm-6 masonery_item" >';
    }
    return $html;
}


function get_event_grid_type_4($pid, $col = 4)
{
    global $adforest_theme;
    $img = '';
    $event_id = $pid;
    $media = sb_pro_fetch_event_gallery($event_id);
    $ad_title = get_the_title();
    if (function_exists('adforest_title_limit')) {
        $ad_title = adforest_title_limit($ad_title);
    }
    $cats_html = '';
    $post_categories = wp_get_object_terms($pid, array('l_event_cat'), array('orderby' => 'term_group'));
    if (isset($post_categories) && !empty($post_categories) && is_array($post_categories)) {
        foreach ($post_categories as $c) {
            $cat = get_term($c);
            $cats_html = ' <a href="' . get_term_link($cat->term_id) . '" class="">' . esc_html($cat->name) . '</a> ';
            // break;
        }
    }
    $author_id = get_post_field('post_author', $pid);
    $html = '';
    // featured code
    $ribbion = 'featured-ribbon';
    if (is_rtl()) {
        $ribbion = 'featured-ribbon-rtl';
    }
    $is_feature = '';
    if (get_post_meta(get_the_ID(), '_adforest_is_feature', true) == '1') {
        $is_feature = '<div class="' . esc_attr($ribbion) . '"><span>' . __('Featured', 'adforest') . '</span></div>';
    }
    $event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
    $event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
    $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
    $event_start_date_timer = date_i18n('Y-m-d H:i:s', strtotime($event_start_date));



    $timer_html = "";
    if (($event_start_date_timer) > date('Y-m-d H:i:s')) {
        $date_formated = date_i18n("Y-m-d H:i:s", strtotime($event_start_date));


        $timer_html .= '<div class="listing-bidding">' . event_timer_html($date_formated, false) . '</div>';
    }
    $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
    $day_content = '
                       <div class="date-box">
                            <span>' . date_i18n('d', strtotime($event_start_date)) . '</span>
                          <small>' . date_i18n('M', strtotime($event_start_date)) . '</small>
                      </div>
                   ';

    $time_content = date_i18n('h:i a', strtotime($event_start_date)) . ", " . date_i18n('l', strtotime($event_start_date));
    $poster_id = get_post_field('post_author', $event_id);
    $user_info = get_userdata($poster_id);
    $poster_name = $user_info->display_name;
    $get_user_dp = adforest_get_user_dp($poster_id);
    $img = '<a href="' . get_the_permalink() . '"><img class="main-img" src="' . sb_return_event_image_id($media, '') . '" alt="' . $ad_title . '"></a>';


    $html .= '<div class="swiper-slide"><div class="ad-grid-box event-hero-grid">
                                        ' . $img . '
                                        <div class="content-box">
                                           ' . $day_content . '
                                            <span class="ctg">' . $cats_html . '</span>
                                            <a href="' . get_the_permalink() . '">
                                                <h4 class="heading">' . $ad_title . '</h4>
                                            </a>
                                            <div class="prf-view">
                                               <a href="' . esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')) . '"><img src="' . $get_user_dp . '"  alt="' . $ad_title . '" class ="author-img"></a>
                                                <a class="auth-name" href="#">' . $poster_name . '</a>
                                                <span class="views"><i class="fa fa-eye"></i> ' . adforest_getPostViews($event_id) . ' ' . __('Views', 'adforest') . '</span>
                                            </div>
                                        </div>
                                        <div class="location">
                                            <i class="fa fa-map-marker"></i>
                                            <span>' . get_post_meta($event_id, 'sb_pro_event_venue', true) . '</span>
                                        </div>   
                                        ' . $timer_html . '
                                    </div></div>';
    return $html;
}



function get_ads_grid_type_4($pid, $col = 4)
{
    global $adforest_theme;
    $img = '';
    $event_id = $pid;
    $media = adforest_get_ad_images($event_id);
    $ad_title = get_the_title();
    if (function_exists('adforest_title_limit')) {
        $ad_title = adforest_title_limit($ad_title);
    }
    $cats_html = '';
    $post_categories = wp_get_object_terms($pid, array('ad_cats'), array('orderby' => 'term_group'));
    if (isset($post_categories) && !empty($post_categories) && is_array($post_categories)) {
        foreach ($post_categories as $c) {
            $cat = get_term($c);
            $cats_html = ' <a href="' . get_term_link($cat->term_id) . '" class="">' . esc_html($cat->name) . '</a> ';
            // break;
        }
    }
    $author_id = get_post_field('post_author', $pid);
    $html = '';
    // featured code
    $ribbion = 'featured-ribbon';
    if (is_rtl()) {
        $ribbion = 'featured-ribbon-rtl';
    }
    $is_feature = '';
    if (get_post_meta(get_the_ID(), '_adforest_is_feature', true) == '1') {
        $is_feature = '<div class="' . esc_attr($ribbion) . '"><span>' . __('Featured', 'adforest') . '</span></div>';
    }

    $event_venue = get_post_meta($event_id, '_adforest_ad_location', true);

    $timer_html = "";
    $bid_end_date = get_post_meta($pid, '_adforest_ad_bidding_date', true);
    if ($bid_end_date != "" && date_i18n('Y-m-d H:i:s') < $bid_end_date) {

        $timer_html .= '<div class="listing-bidding">' . event_timer_html($bid_end_date, false) . '</div>';
    }
    $day_content = '
                       <div class="date-box">
                            <span>' . date_i18n('d', strtotime(get_the_date(get_option('date_format'), $event_id))) . '</span>
                          <small>' . date_i18n('M', strtotime(get_the_date(get_option('date_format'), $event_id))) . '</small>
                      </div>
                   ';

    $poster_id = get_post_field('post_author', $event_id);
    $user_info = get_userdata($poster_id);
    $poster_name = $user_info->display_name;
    $get_user_dp = adforest_get_user_dp($poster_id);
    $img = '<img class="main-img" src="' . sb_return_event_image_id($media, '') . '" alt="' . $ad_title . '">';

    $html .= '<div class="swiper-slide"><div class="ad-grid-box event-hero-grid">
                                        ' . $img . '
                                        <div class="content-box">
                                           ' . $day_content . '
                                            <span class="ctg">' . $cats_html . '</span>
                                            <a href="' . get_the_permalink() . '">
                                                <h4 class="heading">' . $ad_title . '</h4>
                                            </a>
                                            <div class="prf-view">
                                               <a href="' . esc_url(adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads')) . '"><img src="' . $get_user_dp . '"  alt="' . $ad_title . '" class ="author-img"></a>
                                                <a class="auth-name" href="#">' . $poster_name . '</a>
                                                <span class="views"><i class="fa fa-eye"></i> ' . adforest_getPostViews($event_id) . ' ' . __('Views', 'adforest') . '</span>
                                            </div>
                                        </div>
                                        <div class="location">
                                            <i class="fa fa-map-marker"></i>
                                            <span>' . $event_venue . '</span>
                                        </div>   
                                        ' . $timer_html . '
                                    </div></div>';
    return $html;
}

// Event Time Expiry Started
if (!function_exists('sb_pro_listing_check_event_starting')) {

    function sb_pro_listing_check_event_starting($event_id)
    {
        if ($event_id == '')
            return '';
        global $adforest_theme;
        //must have end time
        if (get_post_meta($event_id, 'sb_pro_event_start_date', true) != '') {
            $event_start_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
            $start_timing = strtotime($event_start_date);
            //listing owner timezone
            $author_id = get_post_field('post_author', $event_id);
            if (get_user_meta($author_id, 'd_user_timezone', true) != "") {
                $user_time = get_user_meta($author_id, 'd_user_timezone', true);
                if (sb_pro_listing_checktimezone($user_time) == true) {
                    $user_timezone = new DateTime("now", new DateTimeZone($user_time));
                    $currentTime = $user_timezone->format('m/d/Y g:i a');
                } else { //no timezone :(
                    $currentTime = date('m/d/Y g:i a');
                }
            } else {
                //no timezone :(
                $currentTime = date('m/d/Y g:i a');
            }
            $time_need_to_check = strtotime($currentTime);
            if ($time_need_to_check < $start_timing) {
                //not expired
                return '1';
            } else {
                //coupon expired
                return '0';
            }
        }
    }
}

// Return Event Images media
if (!function_exists('sb_return_event_image_id')) {

    function sb_return_event_image_id($media, $thumbnail_size)
    {
        global $adforest_theme;
        if (count($media) > 0) {
            $i = 1;
            foreach ($media as $m) {
                if ($i > 1)
                    break;
                $mid = '';
                if (isset($m->ID)) {
                    $mid = $m->ID;
                } else {
                    $mid = $m;
                }
                if (wp_attachment_is_image($mid)) {
                    $image = wp_get_attachment_image_src($mid, $thumbnail_size);
                    return $image[0];
                } else {
                    return adforest_get_ad_default_image_url();
                }
            }
        } else {
            return $adforest_theme['event_breadcrumb']['url'];
        }
    }
}

function event_get_default_timer()
{
    return '<div class="sb_pro_countdown-timer">
						<div class="timer-countdown-box">
							<div class="countdown sb_pro_custom-timer" data-countdown-time="11/14/2021 11:19 am"><li> <div class="timer-countdown-box"> <span class="timer-days">00</span> <span class="timer-div">days</span> </div> </li> <li> <div class="timer-countdown-box"> <span class="timer-hours">00</span> <span class="timer-div color-1">hours</span> </div> </li> <li> <div class="timer-countdown-box"> <span class="timer-minutes">00</span> <span class="timer-div color-2">minutes</span> </div> </li> <li> <div class="timer-countdown-box"> <span class="timer-seconds">00</span> <span class="timer-div color-3">seconds</span> </div> </li></div>
						</div>
					</div>';
}

if (!has_filter('get_meta_sql', 'adforest_cast_decimal_precision')) {
    add_filter('get_meta_sql', 'adforest_cast_decimal_precision');
}

if (!function_exists('adforest_cast_decimal_precision')) {
    function adforest_cast_decimal_precision($array)
    {
        if (isset($array['where'])) {
            $array['where'] = str_replace('DECIMAL', 'DECIMAL(10,3)', $array['where']);
        }

        return $array;
    }
}


function sb_expire_the_event($event_id)
{
    global $adforest_theme;
    $aid = $event_id;
    $sb_pro_event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
    if ($sb_pro_event_end_date != "" && date('Y-m-d H:i:s', strtotime(' +2 day')) > $sb_pro_event_end_date) {
        $after_expired_ads = isset($adforest_theme['after_expired_events']) && !empty($adforest_theme['after_expired_events']) ? $adforest_theme['after_expired_events'] : 'trashed';
        if ($after_expired_ads == 'expired') {
            update_post_meta($aid, 'sb_pro_event_status', 0);
            $my_post = array(
                'ID' => $aid,
                'post_status' => 'draft',
                'post_type' => 'events',
            );
            wp_update_post($my_post);
            update_post_meta($aid, 'sb_pro_event_status', 0);
        } else if ($after_expired_ads == 'trashed') {
            update_post_meta($aid, 'sb_pro_event_status', 0);
            wp_trash_post($aid);
        } else {
            if ($simple_days > $expiry_days) {
                update_post_meta($aid, 'sb_pro_event_status', 0);
                $my_post = array(
                    'ID' => $aid,
                    'post_status' => 'publish',
                    'post_type' => 'ad_post',
                );
                wp_update_post($my_post);
            }
        }
    }
}
//Allow Pending products to be viewed by listing/product owner
if (!function_exists('sb_show_pending_post_to_author')) {
    function sb_show_pending_post_to_author($query)
    {
        if (isset($_GET['post_type']) && $_GET['post_type'] == "events" && isset($_GET['p'])) {
            $listing_id = $_GET['p'];
            $post_author = get_post_field('post_author', $listing_id);
            if (is_user_logged_in() && get_current_user_id() == $post_author) {
                $query->set('post_status', array('publish', 'pending', 'draft', 'trash'));
                return $query;
            } else {
                return $query;
            }
        } else {
            return $query;
        }
    }
}
add_filter('pre_get_posts', 'sb_show_pending_post_to_author');
// check permission for ad posting
if (!function_exists('adforest_check_event_validity')) {
    function adforest_check_event_validity()
    {
        global $adforest_theme;
        $uid = get_current_user_id();
        $selected_categories = get_user_meta($uid, 'adforest_ads_package_details', true);
        $sb_packages_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_packages_page']);
        $number_of_events = 0;
        $pkg_expiry_date = 0;

        if (is_array($selected_categories) && count($selected_categories) > 0) {
            foreach ($selected_categories as $package_id => $details) {
                $number_of_events += intval($details['number_of_events']);
                $pkg_expiry_date = intval($details['pkg_expiry_days']);
            }
        }

        if (get_user_meta($uid, 'number_of_events', true) == 0 || get_user_meta($uid, 'number_of_events', true) == "") {
            if ($number_of_events == 0 || $number_of_events == "") {
                adforest_redirect_with_msg(get_the_permalink($sb_packages_page), __('Please subscribe to a package to post an event.', 'sb_pro'));
                exit;
            } else {
                if (get_user_meta($uid, '_sb_expire_ads', true) != '-1') {
                    if ($pkg_expiry_date != '-1') {
                        if (get_user_meta($uid, '_sb_expire_ads', true) < date('Y-m-d')) {
                            if ($pkg_expiry_date < 0) {
                                // update_user_meta($uid, 'number_of_events', 0);

                                adforest_redirect_with_msg(get_the_permalink($sb_packages_page), __("Your package has been expired.", 'sb_pro'));
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }
}