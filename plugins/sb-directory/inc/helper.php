<?php
namespace SbPro\Inc;

class Sb_Helper
{

    public function __construct()
    {
        /* all necessary action and filters here */
        add_action('init', array($this, 'register_required_types'), 10);
        add_filter('get_event_detail_page_template', array($this, 'get_event_detail_page_template_callback'), 10, 2);
        add_filter('sb_get_anchor', array($this, 'sb_get_anchor_fun'), 10, 3);
        add_filter('sb_get_booking_anchor', array($this, 'sb_get_booking_anchor_callback'), 10, 3);
        add_filter('sb_get_event_creat_form', array($this, 'sb_get_event_creat_form_fun'), 10);
        add_filter('sb_get_booking_creat_form', array($this, 'sb_get_booking_creat_form_fun'), 10);
        add_filter('events_options', array($this, 'events_options_callback'), 10, 1);
        add_filter('sb_listings_options', array($this, 'sb_listings_options_callback'), 10, 1);
        add_filter('events_stats', array($this, 'events_stats_callback'), 10, 1);
        add_filter('sb_get_recent_event_list', array($this, 'sb_get_recent_event_list_callback'), 10, 1);
        add_filter('sb_get_event_list', array($this, 'sb_get_event_list_callback'), 10, 2);
        add_filter('sb_get_event_list_html', array($this, 'sb_get_event_list_html_callback'), 10, 2);
        add_filter('sb_get_business_hous_post', array($this, 'sb_get_business_hous_post_callback'), 10, 2);
        add_filter('sb_show_business_hours', array($this, 'sb_show_business_hours_callback'), 10, 2);
        add_filter('sb_show_booking_option', array($this, 'sb_show_booking_option_callback'), 10, 2);
        add_filter('sb_get_booking_list', array($this, 'sb_get_booking_list_callback'), 10, 2);
        add_filter('sb_get_sent_booking_list', array($this, 'sb_get_sent_booking_list_callback'), 10, 2);
        add_filter('manage_sb_bookings_posts_columns', array($this, 'sb_bookings_posts_columns_callback'), 10, 2);
        add_action('manage_sb_bookings_posts_custom_column', array($this, 'sb_bookings_posts_custom_column_content_callback'), 10, 2);
        add_filter('manage_edit-sb_bookings_sortable_columns', array($this, 'sb_bookings_sortable_columns'), 10, 2);
        add_action('pre_get_posts', array($this, 'sb_sort_booking_by_booking_date'), 10, 2);
        add_filter('adforest_pro_get_booked_ads_list', array($this, 'adforest_pro_get_booked_ads_list_callback'), 10, 2);
        add_filter('page_template', array($this, 'wpa3396_page_template'));
        /**
         * Add "Custom" template to page attirbute template section.
         */
        add_filter('theme_page_templates', array($this, 'wpse_288589_add_template_to_select'), 10, 4);
    }

    function wpa3396_page_template($page_template)
    {
        if (get_page_template_slug() == 'event-search.php') {
            $page_template = SB_DIR_PATH . '/template-parts/event-search/search.php';
        }
        return $page_template;
    }

    function wpse_288589_add_template_to_select($post_templates, $wp_theme, $post, $post_type)
    {
// Add custom template named template-custom.php to select dropdown 
        $post_templates['event-search.php'] = __('event search', 'sb_pro');
        return $post_templates;
    }

    function sb_sort_booking_by_booking_date($query)
    {
        if (!is_admin())
            return;
        $orderby = $query->get('orderby');
        if ('booking_date' == $orderby) {
            $query->set('meta_key', 'booking_org_date');
            $query->set('orderby', 'meta_value_num');
        }
    }

    public function sb_bookings_sortable_columns($col)
    {
        $col['booking_date'] = 'booking_date';
        return $col;
    }

    public function sb_bookings_posts_custom_column_content_callback($column, $booking_id)
    {
        $booking_details = get_post_meta($booking_id, 'booking_details', true);

        $booking_details = $booking_details != "" ? json_decode($booking_details, true) : array();
        $formated_date = $booker_name = $booker_email = $booker_phone = $booking_slot_start = $booking_slot_end = $booking_date = $booking_month = $booking_day = $booking_ad_id = "";
        if (!empty($booking_details)) {
            $booker_name = $booking_details['booker_name'];
            $booker_email = $booking_details['booker_email'];
            $booker_phone = $booking_details['booker_phone'];
            $booking_slot_start = $booking_details['booking_slot_start'];
            $booking_slot_end = $booking_details['booking_slot_end'];
            $booking_date = $booking_details['booking_date'];
            $booking_month = $booking_details['booking_month'];
            $booking_day = $booking_details['booking_day'];
            $booking_ad_id = isset($booking_details['booking_ad_id']) ? $booking_details['booking_ad_id'] : "";

            $booking_org_date = get_post_meta($booking_id, 'booking_org_date', true);     /* extra date saved for direct get date */
            $formated_date = "";
            if ($booking_org_date != "") {

                $formated_date = date(get_option('date_format'), $booking_org_date);
            }


            $status_string = array(__('Pending', 'sb_pro'), __('Pending', 'sb_pro'), __('Approved', 'sb_pro'), __('Rejected', 'sb_pro'));
            $booking_status = get_post_meta($booking_id, 'booking_status', true);
            $booking_status = isset($status_string[$booking_status]) ? $status_string[$booking_status] : "";
        }
        switch ($column) {
            case 'booker_email':
                echo $booker_email;
                break;
            case 'booker_phone':
                echo $booker_phone;
                break;
            case 'booking_date':
                echo $formated_date;
                break;
            case 'booking_start_time':
                echo $booking_slot_start;
                break;
            case 'booking_end_time':
                echo $booking_slot_end;
                break;
            case 'booking_status':
                echo $booking_status;
                break;
            default:
                break;
        }
    }

    public function sb_bookings_posts_columns_callback($col)
    {
        $col['booker_email'] = esc_html__('Email', 'sb_pro');
        $col['booker_phone'] = esc_html__('Phone', 'sb_pro');
        $col['booking_date'] = esc_html__('Booking Date', 'sb_pro');
        $col['booking_start_time'] = esc_html__('Booking Start tiime', 'sb_pro');
        $col['booking_end_time'] = esc_html__('Booking End Time', 'sb_pro');
        $col['booking_status'] = esc_html__('Booking Status', 'sb_pro');
        return $col;
    }

    public function sb_show_booking_option_callback($pid)
    {
        global $adforest_theme;
        $allow_booking = isset($adforest_theme['allow_booking_listing']) ? $adforest_theme['allow_booking_listing'] : false;
        if (!$allow_booking) {
            return;
        }
        $user_id = get_current_user_id();
        $is_ad_booking_allow = get_post_meta($pid, 'is_ad_booking_allow', true);
        $widget_code = get_post_meta($pid, 'timekit_widget_code', true);
        $booked_days = get_post_meta($pid, 'booked_days', true);
        if (isset($is_ad_booking_allow) && $is_ad_booking_allow != "") {
            ?>
            <?php if (isset($adforest_theme['allow_timekit_booking']) && $adforest_theme['allow_timekit_booking']) { ?>
                <div class="main-section-bid">
                    <div id="bookingjs"></div>
                    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" defer></script>
                    <script src="https://cdn.timekit.io/booking-js/v2/booking.min.js" defer></script>
                    <script>
                        <?php echo htmlspecialchars_decode($widget_code); ?>
                    </script>
                </div>
            <?php } else {
                ?>
                <div class="main-section-bid booking-section">
                    <h3>
                        <i class="far fa-calendar"></i> <?php echo esc_html__('Appointment', 'sb_pro'); ?>
                    </h3>
                    <div class="current-selected-date">
                        <div class="selectd_booking_day"> <?php echo date_i18n("l"); ?> </div>
                        <span class="selectd_booking_month"><?php echo date_i18n('F'); ?></span>
                        <span class="selectd_booking_date"><?php echo date('j'); ?></span>
                        <span class="selectd_booking_year"><?php echo date_i18n('Y'); ?></span>

                        <div id="selectd_booking_time"></div>
                        <input id="selectd_booking_day" type="hidden" value="<?php echo date_i18n("l"); ?> ">
                    </div>
                    <div class="calender-container">
                        <input type="text" id="calender-booking" class="form-control" placeholder="Select Date"
                               data-ad-id="<?php echo $pid ?>" readonly="readonly" value=<?php echo date('Y-m-d') ?>>
                        <i class="far fa-calendar"></i>
                        <input type="hidden" value="<?php echo esc_attr(($booked_days)) ?>" id="booked_days"/>

                        <?php //echo $timing_html; ?>
                        <div class="all-booking-timing">
                            <div class="panel-dropdown dropdown form-control">
                                <div class="dropdown-toggle" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                     aria-expanded="false"><?php echo esc_html__('Choose Time Slot...', 'sb_pro'); ?></div>
                                <?php
                                $current_day_today = strtolower(date("l"));
                                $is_open = get_post_meta($pid, '_timingz_' . $current_day_today . '_open', true);
                                if ($is_open == 1) {
                                    $startTime = date('H:i:s', strtotime(get_post_meta($pid, '_timingz_' . $current_day_today . '_from', true)));
                                    $endTime = date('H:i:s', strtotime(get_post_meta($pid, '_timingz_' . $current_day_today . '_to', true)));
                                    $startTime = strtotime($startTime);
                                    $endTime = strtotime($endTime);

                                    $booking_interval = get_post_meta($pid, 'booking_interval', true);
                                    $booking_interval = $booking_interval != "" ? $booking_interval : 30;
                                    $interval = "$booking_interval mins";
                                    $current = time();
                                    $addTime = strtotime('+' . $interval, $current);
                                    $diff = $addTime - $current;
                                    $intervalEnd = $startTime + $diff;
                                    $count = 1;
                                    $timing_html = "";

                                    while ($startTime < $endTime) {
                                        $appt_start = date(get_option('time_format'), (int)$startTime,);
                                        $appt_end = date(get_option('time_format'), (int)$intervalEnd);
                                        $timing_html .= '<div class="show_book_form">	
					         <label class="time-slot">
							  <span> ' . $count . '</span> : 
                                                           <span class="start_time">' . $appt_start . '</span>
                                                          <span class="start_time">' . $appt_end . '</span>								
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
                                }
                                ?>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <div class="panel-dropdown-scrollable">
                                        <?php echo $timing_html; ?>
                                    </div>
                                </ul>
                                <?php
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="booking-form-container" data-lid="<?php echo $pid ?>">
                        <div class="booking-form-wrapper">
                            <form class="create-booking-form">
                                <?php
                                $current_booker = wp_get_current_user();
                                $booker_phone = get_user_meta($current_booker->ID, '_sb_contact', true);
                                ?>
                                <div class="form-group">
                                    <input value="<?php echo $current_booker->first_name; ?>" type="text"
                                           placeholder="First Name"
                                           class="form-control" name="booker_first_name" required="">
                                </div>
                                <div class="form-group">
                                    <input value="<?php echo $current_booker->last_name; ?>" type="text"
                                           placeholder="Last Name"
                                           class="form-control" name="booker_last_name" required="">
                                </div>
                                <div class="form-group">
                                    <input value="<?php echo $current_booker->user_email; ?>" type="text"
                                           placeholder="Email"
                                           class="form-control" name="booker_email" required="">
                                </div>
                                <div class="form-group">
                                    <input value="<?php echo $booker_phone; ?>" type="text" placeholder="Phone"
                                           class="form-control" name="booker_phone" required="">
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control" placeholder="Comment"
                                              name="booker_comment"></textarea>
                                </div>
                                <div class="form-group">
                                    <button type="submit"
                                            class="creat-booking-submit btn btn-theme btn-block"><?php echo esc_html__('Request Booking', 'sb_pro'); ?>
                                        <i class="fa fa-spinner fa-spin lp-booking-preloader-spinner"></i></button>
                                </div>
                                <span class="email-caption"><i class="fa fa-info-circle"
                                                               aria-hidden="true"></i><?php echo esc_html__('Appointment confirmation email will be sent upon approval.', 'sb_pro'); ?></span>
                                <input type="hidden" class="booking_ad_id" value="<?php echo $pid ?>"
                                       name="booking_ad_id">
                                <input type="hidden" class="form_booking_day" value="<?php echo date_i18n("l"); ?>"
                                       name="form_booking_day">
                                <input type="hidden" class="form_booking_date" value="<?php echo date('j'); ?>"
                                       name="form_booking_date">
                                <input type="hidden" class="form_booking_month" value="<?php echo date_i18n('m'); ?>"
                                       name="form_booking_month">
                                <input type="hidden" class="form_booking_month_name"
                                       value="<?php echo date_i18n('F'); ?>" name="form_booking_month_name">
                                <input type="hidden" class="form_booking_year" value="<?php echo date_i18n('Y'); ?>"
                                       name="form_booking_year">
                                <input type="hidden" class="form_slot_start" value="" name="form_slot_start">
                                <input type="hidden" class="form_slot_end" value="" name="form_slot_end">
                            </form>
                        </div>
                    </div>
                    <div class="booking-confirmed">
                        <div class="booking-confirmation-close">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </div>
                        <div class="booking-confirmation">
                            <i class="fa fa-calendar-check-o confirmation-close"></i>
                            <p class=""><?php echo esc_html__('Awesome Job!', 'sb_pro'); ?></p>
                            <p class=""><?php echo esc_html__('We have received your appointment and will notify you on your provided email.', 'sb_pro'); ?></p>
                        </div>
                    </div>
                    <div class="booking-spin-loader">
                        <i class="mdi mdi-loading fa-spin booking-preloader"></i>
                    </div>
                </div>
                <?php
            }
        }
    }

    public function register_required_types()
    {
        global $adforest_theme;
        $event_slug = 'events';
        $args = array(
            'public' => true,
            'menu_icon' => 'dashicons-calendar',
            'label' => __('Events', 'sb_pro'),
            'supports' => array('title', 'editor', 'comments'),
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'has_archive' => true,
            'rewrite' => array('with_front' => false, 'slug' => $event_slug)
        );
        register_post_type('events', $args);
        if (isset($adforest_theme['allow_booking_listing']) && $adforest_theme['allow_booking_listing']) {
            $args = array(
                'public' => true,
                'menu_icon' => 'dashicons-calendar',
                'label' => __('Appointments', 'sb_pro'),
                'supports' => array('title', 'editor', 'comments'),
                'show_ui' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'has_archive' => true,
                'rewrite' => array('with_front' => false, 'slug' => 'sb_bookings')
            );
            register_post_type('sb_bookings', $args);
        }
        register_taxonomy('l_event_cat', array('events'), array(
            'hierarchical' => true,
            'show_ui' => true,
            'label' => __('Event Categories', 'sb_pro'),
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'l_event_cat'),
        ));
        register_taxonomy('event_loc', array('events'), array(
            'hierarchical' => true,
            'show_ui' => true,
            'label' => __('Event Locations', 'sb_pro'),
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'event_loc'),
        ));
        register_taxonomy('event_tags', array('events'), array(
            'hierarchical' => false,
            'label' => __('Tags', 'sb_pro'),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'event_tag'),
        ));

        register_sidebar(array(
            'name' => esc_html__('Event sidebar', 'adforest'),
            'id' => 'sb_event_sidebar',
            'before_widget' => '<div class="widget widget-content"><div id="%1$s">',
            'after_widget' => '</div></div>',
            'before_title' => '<div class="widget-heading"><h4 class="panel-title"><span>',
            'after_title' => '</span></h4></div>'
        ));
    }

    public function sb_get_booking_anchor_callback($val = "", $param = "", $value = "")
    {
        global $adforest_theme;
        $allow_booking = isset($adforest_theme['allow_booking_listing']) ? $adforest_theme['allow_booking_listing'] : false;
        if (!$allow_booking) {
            return;
        }
        $url = sprintf("?%s=%s", $param, $value);

        $active_bookings = '';
        $expandBookings = "";
        if (isset($_GET['page_type']) && ($_GET['page_type'] == 'bookings' || $_GET['page_type'] == 'booked_ads' || $_GET['page_type'] == 'bookings_received' || $_GET['page_type'] == 'bookings_sent')) {
            $expandBookings = 'show';
            $active_bookings = 'active';
        }


        $create_bookings = "";
        $booked_ads = "";
        $bookings_received = "";
        $bookings_sent = "";
        if (isset($_GET['page_type']) && $_GET['page_type'] == 'bookings') {
            $create_bookings = 'active';
        } elseif (isset($_GET['page_type']) && $_GET['page_type'] == 'booked_ads') {
            $booked_ads = 'active';
        } elseif (isset($_GET['page_type']) && $_GET['page_type'] == 'bookings_received') {
            $bookings_received = 'active';
        } elseif (isset($_GET['page_type']) && $_GET['page_type'] == 'bookings_sent') {
            $bookings_sent = 'active';
        }

        return '
                <li class="nav-item nav-item-has-children ' . $active_bookings . '">
                    <a
                        href="' . esc_url($url) . '"
                        class="collapsed"
                        data-bs-toggle="collapse"
                        data-bs-target="#ddmenu_4"
                        aria-controls="ddmenu_4"
                        aria-expanded="false"
                        aria-label="Toggle navigation"
                    >
                        <span class="icon">
                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_4462_874)">
                            <path d="M9.6263 19.2502H5.5013C5.01507 19.2502 4.54876 19.057 4.20494 18.7132C3.86112 18.3694 3.66797 17.9031 3.66797 17.4168V6.41683C3.66797 5.9306 3.86112 5.46428 4.20494 5.12047C4.54876 4.77665 5.01507 4.5835 5.5013 4.5835H16.5013C16.9875 4.5835 17.4538 4.77665 17.7977 5.12047C18.1415 5.46428 18.3346 5.9306 18.3346 6.41683V9.16683" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14.668 2.75V6.41667" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7.33203 2.75V6.41667" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3.66797 10.0835H12.8346" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12.832 16.5002C12.832 17.4726 13.2183 18.4053 13.906 19.0929C14.5936 19.7805 15.5262 20.1668 16.4987 20.1668C17.4712 20.1668 18.4038 19.7805 19.0914 19.0929C19.7791 18.4053 20.1654 17.4726 20.1654 16.5002C20.1654 15.5277 19.7791 14.5951 19.0914 13.9074C18.4038 13.2198 17.4712 12.8335 16.4987 12.8335C15.5262 12.8335 14.5936 13.2198 13.906 13.9074C13.2183 14.5951 12.832 15.5277 12.832 16.5002Z" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16.5 15.125V16.5L16.9583 16.9583" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                            <defs>
                            <clipPath id="clip0_4462_874">
                            <rect width="22" height="22" fill="white"/>
                            </clipPath>
                            </defs>
                            </svg>
                        </span>
                        <span class="text">' . esc_html__("Appointments", "sb_pro") . '</span>
                    </a>
                    <ul id="ddmenu_4" class="collapse dropdown-nav ' . $expandBookings . '">
                        <li>
                            <a href="' . $url . '" class="' . $create_bookings . '">
                                <span class="text">' . esc_html__("Allow Appointment", "sb_pro") . '</span>
                            </a>
                        </li>
                        <li>
                            <a href="' . sprintf("?%s=%s", $param, "booked_ads") . '" class="' . $booked_ads . '">
                                <span class="text">' . esc_html__("Appointment Ads", "sb_pro") . '</span>
                            </a>
                        </li>
                        <li>
                            <a href="' . sprintf("?%s=%s", $param, "bookings_received") . '" class="' . $bookings_received . '">
                                <span class="text">' . esc_html__("Appointments Received", "sb_pro") . '</span>
                            </a>
                        </li>
                        <li>
                            <a href="' . sprintf("?%s=%s", $param, "bookings_sent") . '" class="' . $bookings_sent . '">
                                <span class="text">' . esc_html__("Appointments Sent", "sb_pro") . '</span>
                            </a>
                        </li>
                    </ul>
                </li>
                ';
    }

    public function sb_get_anchor_fun($val = "", $param = "", $value = "")
    {
        global $adforest_theme;
        $allow_events = isset($adforest_theme['allow_event_create']) ? $adforest_theme['allow_event_create'] : false;
        if (!$allow_events) {
            return;
        }
        $url = sprintf("?%s=%s", $param, $value);

        $active_events = '';
        $expandEvents = "";
        if (isset($_GET['page_type']) && ($_GET['page_type'] == 'events' || $_GET['page_type'] == 'publish_events' || $_GET['page_type'] == 'pen_events' || $_GET['page_type'] == 'expire_events')) {
            $expandEvents = 'show';
            $active_events = 'active';
        }


        $create_events = "";
        $publish_events = "";
        $pen_events = "";
        $expire_events = "";
        if (isset($_GET['page_type']) && $_GET['page_type'] == 'events') {
            $create_events = 'active';
        } elseif (isset($_GET['page_type']) && $_GET['page_type'] == 'publish_events') {
            $publish_events = 'active';
        } elseif (isset($_GET['page_type']) && $_GET['page_type'] == 'pen_events') {
            $pen_events = 'active';
        } elseif (isset($_GET['page_type']) && $_GET['page_type'] == 'expire_events') {
            $expire_events = 'active';
        }

        return '
                <li id="this_is_a_testid" class="nav-item nav-item-has-children ' . $active_events . '">
                    <a
                        href="' . esc_url($url) . '"
                        class="collapsed"
                        data-bs-toggle="collapse"
                        data-bs-target="#ddmenu_3"
                        aria-controls="ddmenu_3"
                        aria-expanded="false"
                        aria-label="Toggle navigation"
                    >
                        <span class="icon">
                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_4462_867)">
                            <path d="M2.75 3.6665H19.25" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3.66797 3.6665V12.8332C3.66797 13.3194 3.86112 13.7857 4.20494 14.1295C4.54876 14.4733 5.01507 14.6665 5.5013 14.6665H16.5013C16.9875 14.6665 17.4538 14.4733 17.7977 14.1295C18.1415 13.7857 18.3346 13.3194 18.3346 12.8332V3.6665" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M11 14.6665V18.3332" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8.25 18.3335H13.75" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7.33203 11.0002L10.082 8.25016L11.9154 10.0835L14.6654 7.3335" stroke="black" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                            <defs>
                            <clipPath id="clip0_4462_867">
                            <rect width="22" height="22" fill="white"/>
                            </clipPath>
                            </defs>
                            </svg>
                        </span>
                        <span class="text">' . esc_html__("All Events", "sb_pro") . '</span>
                    </a>
                    <ul id="ddmenu_3" class="collapse dropdown-nav ' . $expandEvents . '">
                    
                        <li>
                            <a href="' . $url . '" class="' . $create_events . '">
                                <span class="text">' . esc_html__("Create Event", "sb_pro") . '</span>
                            </a>
                        </li>
                        <li>
                            <a class="' . $publish_events . '" href="' . sprintf("?%s=%s", $param, "publish_events") . '">
                                <span class="text">' . esc_html__("Published Events", "sb_pro") . '</span>
                            </a>
                        </li>
                        <li>
                            <a class="' . $pen_events . '" href="' . sprintf("?%s=%s", $param, "pen_events") . '">
                                <span class="text">' . esc_html__("Pending Events", "sb_pro") . '</span>
                            </a>
                        </li>
                        <li>
                            <a class="' . $expire_events . '" href="' . sprintf("?%s=%s", $param, "expire_events") . '">
                                <span class="text">' . esc_html__("Expired Events", "sb_pro") . '</span>
                            </a>
                        </li>
                    </ul>
                </li>
                ';
    }

    public function sb_get_event_creat_form_fun($param)
    {
        global $adforest_theme;
        $allow_events = $adforest_theme['allow_event_create'] ? $adforest_theme['allow_event_create'] : false;
        if (!$allow_events) {
            return;
        }
        $event_title = '';
        $userID = get_current_user_id();
        $user_info = get_userdata($userID);
        $event_id = isset($_GET['id']) ? $_GET['id'] : "";
        $is_update = $event_id;
        $author_id = get_post_field('post_author', $event_id);
        $max_upload = isset($adforest_theme['sb_pro_event_upload_limit']) ? $adforest_theme['sb_pro_event_upload_limit'] : 1;
        if (function_exists('adforest_load_search_countries')) {
            adforest_load_search_countries(1);
        }
        wp_enqueue_script('google-map-callback');
        wp_enqueue_style('jquery-te', trailingslashit(get_template_directory_uri()) . 'assets/css/jquery-te.css');
        wp_enqueue_style('jquery-tagsinput', trailingslashit(get_template_directory_uri()) . 'assets/css/jquery.tagsinput.min.css');
        wp_enqueue_script('tagsinput', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery.tagsinput.min.js', false, false, true);
        wp_enqueue_script('parsley', trailingslashit(get_template_directory_uri()) . 'assets/js/parsley.min.js', array('jquery'), null, true);

        /* starting map code */
        $mapType = isset($adforest_theme['map-setings-map-type']) ? $adforest_theme['map-setings-map-type'] : "";
        $lat_long_html = '';
        $lat_lon_script = '';
        $for_g_map = '';
        $is_allow_map = 1;
        $ad_map_lat = get_post_meta($event_id, 'sb_pro_event_lat', true);
        $ad_map_long = get_post_meta($event_id, 'sb_pro_event_long', true);
        $ad_location = '';
        $col_class = "col-lg-6 col-md-12 col-12 col-xs-12";
        if (isset($adforest_theme['allow_lat_lon']) && !$adforest_theme['allow_lat_lon']) {
            $is_allow_map = 2;
        } else {
            $pin_lat = $ad_map_lat;
            $pin_long = $ad_map_long;
            if ($ad_map_lat == "" && $ad_map_long == "" && isset($adforest_theme['sb_default_lat']) && $adforest_theme['sb_default_lat'] && isset($adforest_theme['sb_default_long']) && $adforest_theme['sb_default_long']) {
                $pin_lat = $adforest_theme['sb_default_lat'];
                $pin_long = $adforest_theme['sb_default_long'];
            }
            $libutton = '';
            if ($mapType != 'leafletjs_map') {
                $libutton = '<li><a href="javascript:void(0);" id="your_current_location" title="' . __('You Current Location', 'sb_pro') . '"><i class="lni lni-location-arrow"></i></a></li>';
            }
            $for_g_map = '<div class="row">
		                    <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12">
		                        <div class="input-style-1">
			                        <div id="dvMap" style="width: 100%; height: 350px"></div>
			                        <em><small>' . __('Drag pin for your pin-point location.', 'sb_pro') . '</small></em>
                                </div>
			                </div>
                         </div>';
            ?>
            <?php
            $nominatim_cc = '';
            if (isset($adforest_theme['sb_location_allowed']) && !$adforest_theme['sb_location_allowed'] && isset($adforest_theme['sb_list_allowed_country']) && is_array($adforest_theme['sb_list_allowed_country'])) {
                $nominatim_cc = '&countrycodes=' . implode(',', $adforest_theme['sb_list_allowed_country']);
            }
            $nominatim_ft = '';
            if (!isset($adforest_theme['sb_location_type']) || $adforest_theme['sb_location_type'] !== 'regions') {
                $nominatim_ft = '&featuretype=city';
            }
            $nominatim_vb = '';
            if ($pin_lat && $pin_long) {
                $nominatim_vb = '&viewbox=' . ($pin_long - 1) . ',' . ($pin_lat - 1) . ',' . ($pin_long + 1) . ',' . ($pin_lat + 1) . '&bounded=0';
            }
            if ($mapType == 'leafletjs_map') {
                $lat_lon_script = '<script type="text/javascript">var mymap = L.map(\'dvMap\', {zoomSnap: 0.25, zoomDelta: 0.5, wheelDebounceTime: 80}).setView([' . $pin_lat . ', ' . $pin_long . '], 13);
                 L.tileLayer(\'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png\', {maxZoom: 19, detectRetina: true, updateWhenZooming: false, attribution: \'\'}).addTo(mymap);mymap.createPane(\'labels\');mymap.getPane(\'labels\').style.zIndex=450;mymap.getPane(\'labels\').style.pointerEvents=\'none\';L.tileLayer(\'https://tile.openstreetmap.org/{z}/{x}/{y}.png\', {maxZoom: 19, updateWhenZooming: false, pane: \'labels\', minZoom: 5}).addTo(mymap);var modernIcon = L.divIcon({className: \'leaflet-modern-marker\', iconSize: [32, 42], iconAnchor: [16, 42], popupAnchor: [0, -42]});var markerz = L.marker([' . $pin_lat . ', ' . $pin_long . '],{icon: modernIcon, draggable: true}).addTo(mymap);
                 var searchControl 	=	new L.Control.Search({url: \'//nominatim.openstreetmap.org/search?format=json&q={s}' . $nominatim_cc . $nominatim_ft . $nominatim_vb . '\',jsonpParam: \'json_callback\',propertyName: \'display_name\',propertyLoc: [\'lat\',\'lon\'],marker: markerz,autoCollapse: true,autoType: true,minLength: 2,});
                 searchControl.on(\'search:locationfound\', function(obj) {		var lt	=	obj.latlng + \'\';var res = lt.split( "LatLng(" );
                    res = res[1].split( ")" );res = res[0].split( "," );
                    document.getElementById(\'ad_map_lat\').value = res[0];document.getElementById(\'ad_map_long\').value = res[1];});
                    mymap.addControl( searchControl );
                    markerz.on(\'dragend\', function (e) {document.getElementById(\'ad_map_lat\').value = markerz.getLatLng().lat;
                        document.getElementById(\'ad_map_long\').value = markerz.getLatLng().lng;});</script>';
            } else if ($mapType == 'google_map') {
                $lat_lon_script = '<script type="text/javascript">
			var my_map;var marker;
			var markers = [{"title": "","lat": "' . $pin_lat . '","lng": "' . $pin_long . '",},];
			window.onload = function () {my_g_map(markers);}
				function my_g_map(markers1){var mapOptions = {center: new google.maps.LatLng(markers1[0].lat, markers1[0].lng),zoom: 12,mapTypeId: google.maps.MapTypeId.ROADMAP };
				var infoWindow = new google.maps.InfoWindow();
				var latlngbounds = new google.maps.LatLngBounds();
				var geocoder = geocoder = new google.maps.Geocoder();
				my_map = new google.maps.Map(document.getElementById("dvMap"), mapOptions);
					var data = markers1[0]
					var myLatlng = new google.maps.LatLng(data.lat, data.lng);
					marker = new google.maps.Marker({position: myLatlng,map: my_map,title: data.title,draggable: true, animation: google.maps.Animation.DROP });
					(function (marker, data) {
						google.maps.event.addListener(marker, "click", function (e) {
							infoWindow.setContent(data.description);
							infoWindow.open(map, marker);
						});
						google.maps.event.addListener(marker, "dragend", function (e) {
							document.getElementById("sb_loading").style.display	= "block";
							var lat, lng, address;
							geocoder.geocode({ "latLng": marker.getPosition() }, function (results, status) {
								
								if (status == google.maps.GeocoderStatus.OK) {
									lat = marker.getPosition().lat();
									lng = marker.getPosition().lng();
									address = results[0].formatted_address;
									document.getElementById("ad_map_lat").value = lat;
									document.getElementById("ad_map_long").value = lng;
									document.getElementById("sb_user_address").value = address;
									document.getElementById("sb_loading").style.display	= "none";
								}
							});
						});
					})(marker, data);
					latlngbounds.extend(marker.position);
				}
				jQuery(document).ready(function($) {
			$("#your_current_location").click(function() {
				$.ajax({
				url: "https://geolocation-db.com/jsonp",
				jsonpCallback: "callback",
				dataType: "jsonp",
				success: function( location ) {
					var pos = new google.maps.LatLng(location.latitude, location.longitude);
					my_map.setCenter(pos);
					my_map.setZoom(12);
					$("#sb_user_address").val(location.city + ", " + location.state + ", " + location.country_name );
					document.getElementById("ad_map_long").value = location.longitude;
					document.getElementById("ad_map_long").value = location.longitude;
					var markers2 = [{title: "",lat: location.latitude,lng: location.longitude,},];my_g_map(markers2);}});});});</script>';
            }
            $lat_long_html = $for_g_map . '<div class="row">
			  <div class="col-md-6 col-lg-6 col-xs-12 col-sm-12">
			    <div class="input-style-1">
				 <label>' . __('Latitude', 'sb_pro') . '</label>
				 <input type="text" name="ad_map_lat" id="ad_map_lat" value="' . $pin_lat . '">
				</div>
			  </div>
			  <div class="col-md-6 col-lg-6 col-xs-12 col-sm-12">
                  <div class="input-style-1">
                     <label>' . __('Longitude', 'sb_pro') . '</label>
                     <input name="ad_map_long" id="ad_map_long" value="' . $pin_long . '" type="text">
                  </div>
			  </div>
		   </div>';
        }
        $event_cats = get_terms(array(
            'taxonomy' => 'l_event_cat',
            'hide_empty' => false,
            'parent' => 0, // Only get top-level categories first
        ));
        $selected_cat = array();
        if ($is_update != "") {
            $selected_cat = wp_get_object_terms($is_update, 'l_event_cat', array('fields' => 'ids'));
        }
        $cats_options = '';
        if (!empty($event_cats) && is_array($event_cats) && count($event_cats) > 0) {
            $cats_options = '<option value="">'. esc_html__("Select Category", "sb_pro") .'</option>';
            foreach ($event_cats as $eventz) {
                if (!isset($eventz->term_id)) {
                    continue;
                }
                $selected = "";
                if ($is_update != "" && in_array($eventz->term_id, $selected_cat)) {
                    $selected = "selected=selected";
                }
                $cats_options .= '<option value="' . esc_attr($eventz->term_id) . '"   ' . $selected . '>' . esc_attr($eventz->name) . '</option>';

                // Get child categories
                $child_cats = get_terms(array(
                    'taxonomy' => 'l_event_cat',
                    'hide_empty' => false,
                    'parent' => $eventz->term_id,
                ));

                if (!empty($child_cats) && is_array($child_cats)) {
                    foreach ($child_cats as $child) {
                        $child_selected = "";
                        if ($is_update != "" && in_array($child->term_id, $selected_cat)) {
                            $child_selected = "selected=selected";
                        }
                        $cats_options .= '<option value="' . esc_attr($child->term_id) . '"   ' . $child_selected . '>- ' . esc_attr($child->name) . '</option>';

                        // Get grandchild categories
                        $grandchild_cats = get_terms(array(
                            'taxonomy' => 'l_event_cat',
                            'hide_empty' => false,
                            'parent' => $child->term_id,
                        ));

                        if (!empty($grandchild_cats) && is_array($grandchild_cats)) {
                            foreach ($grandchild_cats as $grandchild) {
                                $grandchild_selected = "";
                                if ($is_update != "" && in_array($grandchild->term_id, $selected_cat)) {
                                    $grandchild_selected = "selected=selected";
                                }
                                $cats_options .= '<option value="' . esc_attr($grandchild->term_id) . '"   ' . $grandchild_selected . '>-- ' . esc_attr($grandchild->name) . '</option>';

                                // Get great-grandchild categories (if needed)
                                $great_grandchild_cats = get_terms(array(
                                    'taxonomy' => 'l_event_cat',
                                    'hide_empty' => false,
                                    'parent' => $grandchild->term_id,
                                ));

                                if (!empty($great_grandchild_cats) && is_array($great_grandchild_cats)) {
                                    foreach ($great_grandchild_cats as $great_grandchild) {
                                        $great_grandchild_selected = "";
                                        if ($is_update != "" && in_array($great_grandchild->term_id, $selected_cat)) {
                                            $great_grandchild_selected = "selected=selected";
                                        }
                                        $cats_options .= '<option value="' . esc_attr($great_grandchild->term_id) . '"   ' . $great_grandchild_selected . '>--- ' . esc_attr($great_grandchild->name) . '</option>';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $acf_id = "";
        if (class_exists('ACF')) {
            $acf_id = 'sb_event_cat';
        }
        $cats_html = '  <div class="' . $col_class . '" >
                      <div class="select-style-1">
                        <label>' . esc_html__('Select Category', 'sb_pro') . ' <span>*</span></label>
                        <div class="select-position">
                            <select data-parsley-required="true"  data-parsley-error-message="' . __('This field is required.', 'sb_pro') . '"  data-placeholder="' . esc_html__('Select Event Category', 'sb_pro') . '" id = "' . $acf_id . '" name="event_cat">
                             ' . $cats_options . '
                            </select>
                        </div>
                      </div>
                </div>';

        if ($author_id != $userID && $event_id != "") {
            return '<div class="alert alert-warning" role="alert">
			' . esc_html__('You are not allowed to edit this ad !', 'sb_pro') . '
		   </div>';
        }
        $event_desc = $event_number = $event_email = $event_start_date = $event_end_date = $event_venue = $event_lat = $event_long = $event_parent_listing = "";
        $selected_custom_data = $fetch_custom_data = '';
        $custom_field_dispaly = 'style=display:none;';
        if ($event_id != "") {
            $event_title = get_the_title($event_id);
            $event_desc = get_the_content(" ", " ", $event_id);
            $event_number = get_post_meta($event_id, 'sb_pro_event_contact', true);
            $event_email = get_post_meta($event_id, 'sb_pro_event_email', true);
            $event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
            $event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
            $event_venue = get_post_meta($event_id, 'sb_pro_event_venue', true);
            $event_lat = get_post_meta($event_id, 'sb_pro_event_lat', true);
            $event_long = get_post_meta($event_id, 'sb_pro_event_long', true);
            $event_parent_listing = get_post_meta($event_id, 'sb_pro_event_listing_id', true);

            if (class_exists('ACF')) {
                $selected_custom_data = sb_framework_fields_by_listing_id($event_id);
                if (is_array($selected_custom_data)) {
                    if (!empty($selected_custom_data)) {
                        $custom_field_dispaly = '';
                    }
                    //$custom_field_dispaly = '';
                    $fetch_custom_data = $selected_custom_data;
                }
            }

        }
        $user_email = isset($user_info->user_email) ? $user_info->user_email : "";
        $event_email = $event_email != "" ? $event_email : $user_email;
        $event_number = $event_number != "" ? $event_number : get_user_meta($userID, '_sb_contact', true);

        /* Question Answers */

        $event_questions = get_post_meta($event_id, 'event_question', true);
        $saved_questions = "";
        if ($event_questions && !empty($event_questions)) {
            $count = 0;
            foreach ($event_questions as $que) {
                $saved_questions .= '<div class="row group remove-que">
                                    <div class="col-lg-112 col-md-12 col-sm-12 col-xs-12">
                                        <div class="input-style-1">
                                            <label>
                                              ' . esc_html__("Event Question", 'sb_pro') . '                                                              </label>
                                            <input type="text" placeholder="' . esc_html__("Event Question", 'sb_pro') . ' " name="event_question[\'question\'][]" value="' . $que['question'] . '">
                                        </div>
                                         <div class="input-style-1">
                                            <label>' . esc_html__("Answer", "sb_pro") . '</label>
                                            <input type="text" placeholder="Event Answer" name="event_question[\'answer\'][]" value="' . $que['answer'] . '">
                                        </div>
                                        <div class="input-style-1"><button type="button" class="main-btn success-btn square-btn btn-hover" btnRemoveQuestion adt-button-dark" data-id="">' . esc_html__("Remove", "sb_pro") . '</button></div>
                                    </div>                                                    
                                </div>';
            }
        } else {
            $saved_questions = ' <div class="row group">
                <div class="col-lg-112 col-md-12 col-sm-12 col-xs-12">
                    <div class="input-style-1">
                        <label>' . esc_html__("Event Question", 'sb_pro') . '</label>
                        <input type="text" placeholder="' . esc_html__('Event Question', 'sb_pro') . '" name="event_question[\'question\'][]" value="">
                    </div>
                     <div class="input-style-1">
                        <label>' . esc_html__("Answer", 'sb_pro') . '</label>
                        <input type="text" placeholder="' . esc_html__('Event answer', 'sb_pro') . '" name="event_question[\'answer\'][]" value="">
                    </div>
                </div>                                                    
            </div>';
        }
        $job_questions = ' 
           <div class="questions content event_qstns">
                          ' . $saved_questions . '                                            
                        <div class="event_question_continer" id="event_question_continer"> 
                       </div>                                                                                
                    <div class="input-style-1">
                         <button type="button" id="add_event_btn" class="main-btn success-btn square-btn btn-hover adt-button-dark-1">
                          ' . esc_html__('Add more', 'sb_pro') . '
                          </button>
                    </div></div>';

        /* Event schedules* */
        $event_schedules = get_post_meta($event_id, 'event_schedules', true);
        $saved_event_schedules = "";
        if ($event_schedules && !empty($event_schedules)) {
            $count = 0;
            foreach ($event_schedules as $que) {
                $saved_event_schedules .= '<div class="row group remove-schedule">
                                                        <div class="col-lg-112 col-md-12 col-sm-12 col-xs-12">
                                                            <div class="input-style-1">
                                                                <label>' . esc_html__('Day', 'sb_pro') . '</label>
                                                                <input type="text" placeholder=" ' . esc_html__('Day', 'sb_pro') . '   " name="event_schedules[\'day\'][]" value="' . $que['day'] . '">
                                                            </div>
                                                             <div class="input-style-1">
                                                                <label>' . esc_html__('Schedule', 'sb_pro') . '</label>
                                                                <textarea  class="event_day_schedule" placeholder="Day schedules" name="event_schedules[\'day_val\'][]"> ' . $que['day_val'] . ' </textarea>
                                                            </div>
                                                            <div class="input-style-1"><button type="button" class="main-btn success-btn square-btn btn-hover btnRemoveDay adt-button-dark" data-id="">' . esc_html__("Remove", "sb_pro") . '</button></div>
                                                        </div>                                                    
                                                    </div>';
            }
        } else {
            $saved_event_schedules = ' <div class="row group">
                                        <div class="col-lg-112 col-md-12 col-sm-12 col-xs-12">
                                            <div class="input-style-1">
                                                <label>
                                                    ' . esc_html__('Day', 'sb_pro') . '                                                              </label>
                                                <input type="text" placeholder=" ' . esc_html__('Day', 'sb_pro') . ' " name="event_schedules[\'day\'][]" value="">
                                            </div>
                                             <div class="input-style-1">
                                                <label>
                                                       
                                                       ' . esc_html__('Schedule', 'sb_pro') . '                                                               </label>
                                                <textarea  class="form-control event_day_schedule" placeholder="Schedule" name="event_schedules[\'day_val\'][]" value=""></textarea>
                                            </div>
                                        </div>                                                    
                                    </div>';
        }
        $event_schedules = '
                                   <div class="questions content event_schedules">
                                      ' . $saved_event_schedules . '                                            
                                    <div class="event_schedule_continer" id="event_schedule_continer"> 
                                   </div>                                                                                
                                <div class="input-style-1">
                                     <button type="button" id="add_event_schedule" class="main-btn success-btn square-btn btn-hover adt-button-dark-1"">
                                          
                                                           ' . esc_html__('Add More', 'sb_pro') . '     </button>
                                </div></div>';

        $tags_array = wp_get_object_terms($event_id, 'event_tags', array('fields' => 'names'));
        $tags = implode(',', $tags_array);

        $tags_html = '<div class="' . $col_class . '" >
                            <div class="input-style-3">
                                <label>' . __('Tags', 'adforest') . ' <small>' . __('Comma(,) separated', 'adforest') . '</small></label>
                                <div class="input-group">
                                     <input class="form-control" name="tags" id="tags" value="' . $tags . '" >
                                </div>
                            </div></div>';

        $ads_html = "";
        $country_html = '';
        $country_states = '';
        $country_cities = '';
        $country_towns = '';
        $levelz = "";

        if ($event_id != "") {
            $countries = adforest_get_ad_cats($event_id, '', true, $taxonomy = "event_loc");
            $levelz = count($countries);
            /* Make cats selected on update ad */
            $ad_countries = adforest_get_cats('event_loc', 0, 0, 'events');
            foreach ($ad_countries as $event_country) {
                $selected = '';
                if ($levelz > 0 && $event_country->term_id == $countries[0]['id']) {
                    $selected = 'selected="selected"';
                }
                $country_html .= '<option value="' . $event_country->term_id . '" ' . $selected . '>' . $event_country->name . '</option>';
            }
            if ($levelz >= 2) {
                $ad_states = adforest_get_cats('event_loc', $countries[0]['id'], 0, 'events');
                $country_states = '';
                foreach ($ad_states as $ad_state) {
                    $selected = '';
                    if ($levelz > 0 && $ad_state->term_id == $countries[1]['id']) {
                        $selected = 'selected="selected"';
                    }
                    $country_states .= '<option value="' . $ad_state->term_id . '" ' . $selected . '>' . $ad_state->name . '</option>';
                }
            }
            if ($levelz >= 3) {
                $event_country_cities = adforest_get_cats('event_loc', $countries[1]['id'], 0, 'events');
                $country_cities = '';
                foreach ($event_country_cities as $ad_city) {
                    $selected = '';
                    if ($levelz > 0 && $ad_city->term_id == $countries[2]['id']) {
                        $selected = 'selected="selected"';
                    }
                    $country_cities .= '<option value="' . $ad_city->term_id . '" ' . $selected . '>' . $ad_city->name . '</option>';
                }
            }

            if ($levelz >= 4) {
                $event_country_town = adforest_get_cats('event_loc', $countries[2]['id'], 0, 'events');
                $country_towns = '';
                foreach ($event_country_town as $ad_town) {
                    $selected = '';
                    if ($levelz > 0 && $ad_town->term_id == $countries[3]['id']) {
                        $selected = 'selected="selected"';
                    }
                    $country_towns .= '<option value="' . $ad_town->term_id . '" ' . $selected . '>' . $ad_town->name . '</option>';
                }
            }
        } else {

            $event_country = adforest_get_cats('event_loc', 0, 0, 'events');
            $country_html = '';
            foreach ($event_country as $ad_count) {
                $country_html .= '<option value="' . $ad_count->term_id . '">' . $ad_count->name . '</option>';
            }
        }

        $additional_fields_html = '';
        if (is_array($selected_custom_data) && !empty($selected_custom_data)) {
            if ($is_update != '' && $event_id != '' && class_exists('ACF')) {
                $additional_fields_html = apply_filters('dwt_listing_framework_acf_frontend_html', '', $selected_custom_data);
            }
        }

        $custom_locations_html = '';
        if (isset($adforest_theme['sb_custom_location']) && $adforest_theme['sb_custom_location']) {
            $loc_lvl_1 = __('Select Your Country', 'adforest');
            $loc_lvl_2 = __('Select Your State', 'adforest');
            $loc_lvl_3 = __('Select Your City', 'adforest');
            $loc_lvl_4 = __('Select Your Town', 'adforest');
            if (isset($adforest_theme['sb_location_titles']) && $adforest_theme['sb_location_titles'] != "") {
                $titles_array = explode("|", $adforest_theme['sb_location_titles']);

                if (count($titles_array) > 0) {
                    if (isset($titles_array[0]))
                        $loc_lvl_1 = $titles_array[0];
                    if (isset($titles_array[1]))
                        $loc_lvl_2 = $titles_array[1];
                    if (isset($titles_array[2]))
                        $loc_lvl_3 = $titles_array[2];
                    if (isset($titles_array[3]))
                        $loc_lvl_4 = $titles_array[3];
                }
            }

            $custom_locations_html = '
			  <div class="' . $col_class . '">
                              <div class="form-group has-feedback">
				 <label class="control-label">' . $loc_lvl_1 . ' <span class="required">*</span></label>
				 <select class="country form-control" id="event_country" name="event_country" data-parsley-required="true" data-parsley-error-message="' . esc_html__('This field is required.', 'adforest') . '">
					<option value="">' . esc_html__('Select Option', 'adforest') . '</option>
					' . $country_html . '
				 </select>
				 <input type="hidden" name="event_country_id" id="event_country_id" value="" />
                                 </div>
			  </div>	  
			  <div class="' . $col_class . '"  id="event_country_sub_div">
                              <div class="form-group has-feedback">
			  <label class="control-label">' . $loc_lvl_2 . '</label>
				<select class="category form-control" id="event_country_states" name="event_country_states">
					' . $country_states . '
				</select>
                                </div>
			  </div>	
			  <div class="' . $col_class . '" id="event_country_sub_sub_div">
                              <div class="form-group has-feedback">
			  <label class="control-label">' . $loc_lvl_3 . '</label>
				<select class="category form-control" id="event_country_cities" name="event_country_cities">
					' . $country_cities . '
				</select>
                                </div>
			  </div>		
			  <div class="' . $col_class . '" id="event_country_sub_sub_sub_div">
                              <div class="form-group has-feedback">
			  <label class="control-label">' . $loc_lvl_4 . '</label>
				<select class="category form-control" id="event_country_towns" name="event_country_towns">
					' . $country_towns . '
				</select>
                                </div>
			  </div>
		';
        }

        /*  Package Details Starts   */
        $selected_categories = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
        $Ads_pachages = '';
        if (is_array($selected_categories)) {
            $Ads_pachages = '';
            foreach ($selected_categories as $product_id => $packageDetails) {
                // Skip the package if the number of events is 0 or ""
                if (isset($packageDetails['number_of_events']) && ($packageDetails['number_of_events'] == "" || $packageDetails['number_of_events'] == 0)) {
                    continue;
                }

                $Pkg_radio = '<input class="form-check-input" type="radio" name="ads_package" id="ads_package_' . $product_id . '" value="' . $product_id . '" required="">';
                $product = wc_get_product($product_id);
                if ($product) {
                    $product_title = $product->get_title();
                }
                $Ads_pachages .= '<div class="card-style" style="margin: 10px" id="featured-duration">
                <ul class="featured-duration-list" id="cate_package">
                    <li> 
                        <label for="ads_package_' . $product_id . '">
                            <div class="type-box">
                                <div class="r-meta">
                                    ' . $Pkg_radio . '
                                    <span style="padding: 3px">' . $product_title . '</span>
                                </div>
                            </div>
                        </label>
                    </li>
                    ';

                foreach ($packageDetails as $detailName => $detailValue) {
                    // Check if the current key is one of the desired keys
                    if (in_array($detailName, ['pkg_expiry_days', 'number_of_events'])) {

                        $custom_titles = [
                            'pkg_expiry_days' => 'Package Expiry',
                            'number_of_events' => "Number of Events"
                        ];

                        // Get the custom title for the current key, or use the original key if not found
                        $title = isset($custom_titles[$detailName]) ? $custom_titles[$detailName] : $detailName;

                        // Add the custom title and value to the HTML output
                        $Ads_pachages .= '<li>' . $title . ': ' . $detailValue . '</li>';
                    }
                }

                $Ads_pachages .= '</ul></div>';
            }
            $number_of_events = get_user_meta(get_current_user_id(), 'number_of_events', true);
            $sb_expire_ads = get_user_meta(get_current_user_id(), '_sb_expire_ads', true);
            if ($number_of_events > 0 && ($sb_expire_ads == '-1' || $sb_expire_ads > date('Y-m-d'))) {
                $Ads_pachages = '';
            }
        }


        return '	
	            <div class="title-wrapper">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="title">
                                <h2>' . esc_html__('Create Events', 'sb_pro') . '</h2>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="breadcrumb-wrapper">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="' . get_the_permalink() . '">
                                                ' . esc_html__('Dashboard', 'sb_pro') . '
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">
                                            ' . esc_html__('Create Events', 'sb_pro') . '
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>				
                <div class="card-style mb-30">
                    <div class="card-body">
                        <form  id="my-events">
                        <div class="row">
                           ' . $ads_html . ' 
                        <div class="' . $col_class . '" >
                            <div class="form-group has-feedback">
                                <label class="custom-dashboard-form-label">' . esc_html__('Event Title', 'sb_pro') . '<span>*</span></label>
                                <div class="input-style-3">
                                    <input id="event_title" type="text" name="event_title" placeholder="' . esc_html__('Event Title', 'sb_pro') . '" value="' . esc_attr($event_title) . '" data-parsley-required="true" >
                                    <span class="icon"><i class="lni lni-pencil-alt"></i></span>
                                    <div id="show-me" class="loader-field"></div>
                                </div>
                            </div>
                           </div>
                            ' . $cats_html . '
                            <div class="' . $col_class . '" >
                                <div class="form-group">
                                    <label class="custom-dashboard-form-label">' . esc_html__("Phone Number", "sb_pro") . '</label>
                                    <div class="input-style-3">
                                        <input type="text" name="event_number" placeholder="' . esc_html__('+99 3331 234567', 'sb_pro') . '" value="' . esc_attr($event_number) . '">
                                        <span class="icon"><i class="lni lni-phone"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="' . $col_class . '" >
                                <div class="form-group">
                                    <label class="custom-dashboard-form-label">' . esc_html__("Contact Email", "sb_pro") . '<span>*</span></label>
                                    <div class="input-style-3">
                                        <input type="email" data-parsley-required="true" name="event_email" placeholder="' . esc_html__('abc@xyz.com', 'sb_pro') . '" value="' . esc_attr($event_email) . '">
                                        <span class="icon"><i class="lni lni-envelope"></i></span>
                                    </div>
                                </div>
                            </div>
                            ' . $tags_html . '
                            <div class="col-12" >
                                <label class="custom-dashboard-form-label">' . esc_html__("Description", "sb_pro") . '<span>*</span></label>
                                <div class="input-style-3">
                                    <textarea  class="event_desc"  name="event_desc" class="jqte-test" rows = "10" data-parsley-required="true"  >' . $event_desc . '</textarea>
                                </div>
                            </div>
                            
                     <div class="' . $col_class . '" >
                            <div class="form-group">
                                <label class="custom-dashboard-form-label">' . esc_html__("Event Start Date", "sb_pro") . '<span>*</span></label>
                                <div class="input-style-3">
                                    <input name="event_start_date" type="text" id="event_start" data-time-format="hh:ii aa" value=" ' . esc_attr($event_start_date) . '" data-parsley-required="true" autocomplete="off"/>
                                    <span class="icon"><i class="lni lni-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                             <div class="' . $col_class . '" >
                            <div class="form-group">
                                <label class="custom-dashboard-form-label">' . esc_html__("Event End Date", "sb_pro") . '<span>*</span></label>
                                <div class="input-style-3">
                                    <input name="event_end_date" type="text" id="event_end" data-time-format="hh:ii aa" value="' . esc_attr($event_end_date) . '" data-parsley-required="true"  autocomplete="off"/>
                                    <span class="icon"><i class="lni lni-calendar"></i></span>
                                </div>
                            </div>
                            </div>
                             <div class="col-12" >
                            <div class="input-style-3">
                                <label>' . esc_html__('Event gallery , please upload first image vertical , so it may look good on grids', 'sb_pro') . '</label>
                                <div id="event_dropzone" class="dropzone upload-ad-images event_zone"><div class="dz-message needsclick">
                                        ' . esc_html__("Event Gallery Images", "sb_pro") . '
                                        <br />
                                        <span class="note needsclick">' . esc_html__("Drop files here or click to upload", "sb_pro") . ' </span>
                                    </div></div>
                            </div>
                            </div> 

                            <div class="col-12 additional-fields" ' . $custom_field_dispaly . '>
                                <div class="card-body">
                                    <label class="control-label">
                                        ' . esc_html__('Additional fields', 'dwt-listing') . '
                                    </label>
                                    <div class="additional-fields-container">
                                     ' . $additional_fields_html . '
                                    </div>
                                </div>
                            </div>

                         ' . $custom_locations_html . '
                      <div class="col-12" >
                            <label class="custom-dashboard-form-label">' . __('Address', 'sb_pro') . ' <span class="required">*</span></label>
                        <div class="input-style-3 event-address">
                            <input value="' . $event_venue . '" type="text" name="sb_user_address" id="sb_user_address" data-parsley-required="true" data-parsley-error-message="' . __('This field is required.', 'sb_pro') . '" placeholder="' . __('Enter a location', 'sb_pro') . '" onkeydown="return (event.keyCode != 13);">
                            <span class="icon"><i class="lni lni-pin"></i></span>
                            <ul id="google-map-btn" class="ad-post-map">' . $libutton . '</ul>
                        </div>
                            ' . $lat_long_html . '
                            ' . $lat_lon_script . '  
                           </div>
                            ' . $job_questions . '                          
                            ' . $event_schedules . '  
                            <div class="purchase-package featured-duration" id="purchase-package">
                                            ' . $Ads_pachages . '
                            </div>                        
                                     <div class="col-12 event-submit">
                                          <div class="form-group">
                                             <input type="hidden" id="is_update" name="is_update" value="' . esc_attr($is_update) . '">
                                                 <button class="main-btn secondary-btn square-btn btn-hover adt-button-dark" data-style="expand-left">
			                          ' . esc_html__('Submit', 'sb_pro') . '</button>
                                                  </div>
                                                </div>
                                            </div>
                            <input type="hidden" id="dictDefaultMessage" value="' . __('Drop files here or click to upload.', 'sb_pro') . '" />
                            <input type="hidden" id="dictFallbackMessage" value="' . __('Your browser does not support drag\'n\'drop file uploads.', 'sb_pro') . '" />
                            <input type="hidden" id="dictFallbackText" value="' . __('Please use the fallback form below to upload your files like in the olden days.', 'sb_pro') . '" />
                            <input type="hidden" id="dictFileTooBig" value="' . __('File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.', 'sb_pro') . '" />
                            <input type="hidden" id="dictInvalidFileType" value="' . __('You can\'t upload files of this type.', 'sb_pro') . '" />
                            <input type="hidden" id="dictResponseError" value="' . __('Server responded with {{statusCode}} code.', 'sb_pro') . '" />
                            <input type="hidden" id="dictCancelUpload" value="' . __('Cancel upload', 'sb_pro') . '" />
                            <input type="hidden" id="dictCancelUploadConfirmation" value="' . __('Are you sure you want to cancel this upload?', 'sb_pro') . '" />
                            <input type="hidden" id="dictRemoveFile" value="' . __('Remove file', 'sb_pro') . '" />
                            <input type="hidden" id="dictMaxFilesExceeded" value="' . __('You can not upload any more files.', 'sb_pro') . '" />
                            <input type="hidden" id="country_level" name="country_level" value="' . $levelz . '" />
                            <input type="hidden" id="event_upload_limit" value="' . esc_attr($max_upload) . '" />
                             <input type="hidden" id="visit_text" value="' . esc_html__('Want to visit detail page', 'sb_pro') . '" />                            
                        </form>
                    </div>
                </div>';
    }

    public function sb_get_booking_creat_form_fun($param)
    {
        global $adforest_theme;
        $user_id = get_current_user_id();
        $allow_booking = $adforest_theme['allow_booking_listing'] ? $adforest_theme['allow_booking_listing'] : false;
        if (!$allow_booking) {
            return;
        }
        $event_title = '';
        $userID = get_current_user_id();
        $widget_area = "";


        $calender_booking = ' <div class="input-style-1 has-feedback">
                                <label class="">' . esc_html__('Select days to avoid booking', 'sb_pro') . '<span>*</span></label>
                                <div class="">
                                   <div  id="already_booked_day"></div>
                                </div>
                            </div>';

        if (isset($adforest_theme['allow_timekit_booking']) && $adforest_theme['allow_timekit_booking']) {
            $widget_area = ' <div class="form-group has-feedback">
                                <label class="control-label">' . esc_html__('TimeKit widget', 'sb_pro') . '<span>*</span></label>
                                <div class="input-group">
            <textarea name = "timekit_widget_code" rows="5"  cols="60" placeholder ="window.timekitBookingConfig = {  app_key: , project_id:}"></textarea>
                                </div>
                                <div class="time_link"> <a href="https://documentation.scriptsbundle.com/docs/adforest-wordpress-theme/#19185">' . esc_html__('How to creat timekit widget', 'sb_pro') . '</a>
                                </div>
                            </div>';

            $calender_booking = "";
        }

        return '
                    <div class="col-lg-12 col-xl-12">						
                <div class="card card-style mb-30">
                    <h6 class="text-medium">' . esc_html__('    Create Booking', 'sb_pro') . '</h6>
                    <div class="card-body">
                        <form  id="my-bookings-listing">
                            <div class="select-style-1 has-feedback">
                                <label class="">' . esc_html__('Select Ad', 'sb_pro') . '<span>*</span></label>
                                
                                <select  name="sb_ad_id" class="sb-select2-ajax" data-parsley-required-message = "' . esc_html__('Please select ad first', 'sb_pro') . '"  data-parsley-required="true"  >
                                    <option value="0">' . esc_html__('Select option', 'sb_pro') . '</option>
                                </select>
                                
                            </div>   

                            <div class="input-style-1 has-feedback">
                                <label class="">' . esc_html__('Add minutes, time interval of booking', 'sb_pro') . '<span>*</span></label>
                                <div class="input-group">
                                    <input class="" type="number" id="booking_interval" name = "booking_interval" value = "30" data-parsley-required="true" min=0 max =60>
                                </div>
                            </div>   

                             ' . $widget_area . '
                              ' . $calender_booking . '
                            <div class="form-group has-feedback">                        
                                <div class="input-group">
                                   <button  type="submit" class="main-btn secondary-btn square-btn btn-hover adt-button-dark" data-style="expand-left">
                                    ' . esc_html__('Submit', 'sb_pro') . '</button>
                                 </div>
                            </div>                            
                                 <input type = "hidden"  id="booked_days"  name = "booked_days" >
                            </form>
                    </div>
                </div></div>
                        ';
    }

    public function sb_listings_options_callback($options = array())
    {
        return array(
            'title' => __('Listing Settings', 'sb_pro'),
            'id' => 'sb_listings_settings',
            'desc' => '',
            'icon' => 'el el-adjust-alt',
            'fields' => array(
                array(
                    'id' => 'allow_business_hours',
                    'type' => 'switch',
                    'title' => __('Allow business hours on listings', 'sb_pro'),
                    'default' => false
                ),
                array(
                    'id' => 'allow_booking_listing',
                    'type' => 'switch',
                    'title' => __('Allow booking on listings', 'sb_pro'),
                    'default' => false
                ),
                array(
                    'id' => 'allow_timekit_booking',
                    'type' => 'switch',
                    'title' => __('Allow Timekit booking', 'sb_pro'),
                    'desc' => adforest_make_link('https://documentation.scriptsbundle.com/docs/adforest-wordpress-theme/#19185', __('How to get timekit widget', 'sb_pro')),
                    'default' => false
                ),
                array(
                    'id' => 'send_booking_status_email',
                    'type' => 'switch',
                    'title' => __('Send email to customer', 'sb_pro'),
                    'desc' => __('Send email to customer when status of booking is changed', 'sb_pro'),
                    'default' => false
                ),
                array(
                    'id' => 'send_booking_status_from',
                    'type' => 'text',
                    'title' => esc_html__('Booking Status change Email FROM', 'sb_pro'),
                    'desc' => esc_html__('FROM: NAME valid@email.com is compulsory as we gave in default.', 'sb_pro'),
                    'default' => 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                    'required' => array('send_booking_status_email', '=', array('1')),
                ),
                array(
                    'id' => 'send_booking_status_subject',
                    'type' => 'text',
                    'title' => esc_html__('Booking Status subject', 'sb_pro'),
                    'default' => 'Booking Staus info',
                    'required' => array('send_booking_status_email', '=', array('1')),
                ),
                array(
                    'id' => 'sb_booking_status_approved_message',
                    'type' => 'editor',
                    'required' => array('send_booking_status_email', '=', array('1')),
                    'title' => esc_html__('Booking approved email template', 'sb_pro'),
                    'args' => array(
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'wpautop' => false,
                    ),
                    'desc' => esc_html__('%customer% , %booking_date% , %booking_time% , %extra_details%  , %ad_title%  ,   %ad_link%  will be translated accordingly.', 'sb_pro'),
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
<td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
<div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
<table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
<tbody>
<tr>
<td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
<td class="alert" style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff">A Designing and development company</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello</span><span style="font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;"> <b>%customer%,</b></span></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">It’s confirmed, we’ll see you on <span style="font-family: "Helvetica Neue"", Helvetica, Arial, sans-serif;"><b>%booking_date%</b></span>  at  <span style="font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;"><b>%booking_time%</b></span>  Thank you for booking. <span style="font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;"> <b>%extra_details%,</b></span></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Ad Title: %ad_title%</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Ad Link: <a href="%ad_link%">%ad_title%</a></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
</tr>
</tbody>
</table>
</div>
 </div>
</td>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
&nbsp;',
                ),
                array(
                    'id' => 'sb_booking_status_decline_message',
                    'type' => 'editor',
                    'title' => esc_html__('Booking rejected template', 'sb_pro'),
                    'required' => array('send_booking_status_email', '=', array('1')),
                    'args' => array(
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'wpautop' => false,
                    ),
                    'desc' => esc_html__('%customer% , %booking_date% , %booking_time% , %extra_details%  , %ad_title%  ,   %ad_link%  will be translated accordingly.', 'sb_pro'),
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
<td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
<div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
<table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
<tbody>
<tr>
<td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
<td class="alert" style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff">A Designing and development company</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello</span><span style="font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;"> <b>%customer%,</b></span></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Thank you for asking about  <span style="font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;"><b>%ad_title%</b></span> . We regret to inform you that we cannot be of service to you .  <b>%extra_details%,</b></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"> Ad title : %<span style="font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;"><b>ad_title</b></span>%</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Ad link  : <a href="%ad_link%">%ad_title%</a></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">For further information contact .</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
</tr>
</tbody>
</table>
</div>
 </div>
</td>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
&nbsp;',
                ),
            )
        );
    }

    public function events_options_callback($options = array())
    {

        $options = array(
            'title' => esc_html__('Events', 'sb_pro'),
            'id' => 'sb_pro_events-settingss',
            'desc' => '',
            'icon' => 'el el-plane',
            'subsection' => true,
            'fields' => array(
                array(
                    'id' => 'allow_event_create',
                    'type' => 'switch',
                    'title' => esc_html__('Allow user to create events', 'sb_pro'),
                    'default' => false,
                ),
                array(
                    'id' => 'sb_pro_event_page',
                    'type' => 'select',
                    'data' => 'pages',
                    'title' => esc_html__('Events Search Page', 'sb_pro'),
                    'default' => '',
                ),

                array(
                    'id' => 'event_grid_type',
                    'type' => 'button_set',
                    'options' => array(
                        '1' => esc_html__('Style 1', 'sb_pro')),
                    'title' => esc_html__('Search page grid style', 'sb_pro'),
                    'default' => '1',
                ),

                array(
                    'id' => 'event_grid_col',
                    'type' => 'button_set',
                    'options' => array(
                        '4' => esc_html__('3 Events', 'sb_pro'),
                        '3' => esc_html__('4  Events', 'sb_pro'),
                    ),
                    'title' => esc_html__('Number of event in row on search page', 'sb_pro'),
                    'default' => '4',
                ),


                array(
                    'id' => 'sb_pro_event_stats',
                    'type' => 'switch',
                    'title' => esc_html__('Events Stats', 'sb_pro'),
                    'default' => true,
                ),

                array(
                    'id' => 'sb_pro_standard_images_size',
                    'type' => 'switch',
                    'title' => __('Strict image mode', 'sb_pro'),
                    'subtitle' => __('Not allowed less than 760x410', 'sb_pro'),
                    'default' => true,
                ),
                array(
                    'id' => 'event_share_allow',
                    'type' => 'switch',
                    'title' => __('Show share button on event detail page', 'sb_pro'),
                    'default' => true,
                ),
                array(
                    'id' => 'event_review_allowed',
                    'type' => 'switch',
                    'title' => __('Review on Event detail page', 'sb_pro'),
                    'default' => true,
                ),
                array(
                    'id' => 'event_breadcrumb',
                    'type' => 'media',
                    'title' => __('Event Detail page breadcrumb image', 'sb_pro'),
                    'default' => true,
                ),
                array(
                    'id' => 'user_contact_form_event',
                    'type' => 'switch',
                    'title' => __('User contact form', 'adforest'),
                    'subtitle' => __('on event detail  page', 'adforest'),
                    'default' => true,
                ),
                array(
                    'id' => 'contact_form_recaptcha_event',
                    'type' => 'switch',
                    'title' => __('Contact Form Google reCAPTCHA', 'adforest'),
                    'subtitle' => __('Hide/Show google recaptcha on user contact form.', 'adforest'),
                    'required' => array('user_contact_form_event', '=', true),
                    'default' => true,
                    'desc' => __('After enabling please verify the <b>Google reCAPTCHA</b> API keys.', 'adforest'),
                ),


                array(
                    'id' => 'sb_pro_event_map',
                    'type' => 'switch',
                    'title' => esc_html__('Show map in events', 'sb_pro'),
                    'default' => true,
                ),
                array(
                    'id' => 'sb_pro_related_events',
                    'type' => 'switch',
                    'title' => esc_html__('Show related events on event detail page', 'sb_pro'),
                    'default' => true,
                ),
                array(
                    'id' => 'sb_pro_related_events_count',
                    'type' => 'select',
                    'title' => __('Max Num of related events on event details page', 'adforest'),
                    'required' => array(array('sb_pro_related_events', '=', true)),
                    'options' => range(0, 10),
                    'default' => 3,
                ),


                array(
                    'id' => 'sb_pro_event_approval',
                    'type' => 'button_set',
                    'title' => esc_html__('Event Approval', 'sb_pro'),
                    'options' => array(
                        '1' => esc_html__('Auto Approval', 'sb_pro'),
                        '0' => esc_html__('Admin Approval', 'sb_pro'),
                    ),
                    'default' => '1'
                ),
                array(
                    'id' => 'sb_pro_event_up_approval',
                    'type' => 'select',
                    'options' => array('auto' => 'Auto Approval', 'manual' => 'Admin Approval'),
                    'title' => esc_html__('Event Update Approval', 'sb_pro'),
                    'default' => 'auto',
                ),
                array(
                    'id' => 'sb_pro_event_upload_limit',
                    'type' => 'select',
                    'title' => esc_html__('Events Gallery Limit', 'sb_pro'),
                    'options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15),
                    'default' => 1,
                ),
                array(
                    'id' => 'sb_pro_event_images_size',
                    'type' => 'select',
                    'title' => __('Events Image Upload Size', 'sb_pro'),
                    'options' => array('307200-300kb' => '300kb', '614400-600kb' => '600kb', '819200-800kb' => '800kb', '1048576-1MB' => '1MB', '2097152-2MB' => '2MB', '3145728-3MB' => '3MB', '4194304-4MB' => '4MB', '5242880-5MB' => '5MB', '6291456-6MB' => '6MB', '7340032-7MB' => '7MB', '8388608-8MB' => '8MB', '9437184-9MB' => '9MB', '10485760-10MB' => '10MB', '11534336-11MB' => '11MB', '12582912-12MB' => '12MB', '13631488-13MB' => '13MB', '14680064-14MB' => '14MB', '15728640-15MB' => '15MB', '20971520-20MB' => '20MB', '26214400-25MB' => '25MB'),
                    'default' => '2097152-2MB',
                ),
                array(
                    'id' => 'events-filter-manager',
                    'type' => 'sorter',
                    'title' => 'Event Search Filters',
                    'compiler' => 'true',
                    'options' => array('enabled' => array('by_title' => 'By Title', 'by_category' => 'By Categories', 'by_location' => 'By Location', 'by_date' => 'By Date', 'by_custom_location' => 'Custom Location', 'by_radius' => 'Radius Search'),
                        'disabled' => array(),
                    ),
                ),
                array(
                    'id' => 'sb_pro_email_event_expire',
                    'type' => 'switch',
                    'title' => __('Event Expiry Email', 'sb_pro'),
                    'default' => false,
                    'desc' => __('Turn On if you send email on Event Expire', 'sb_pro'),
                ),
                array(
                    'id' => 'after_expired_events',
                    'type' => 'button_set',
                    'title' => __('After Removal events Should be', 'sb_pro'),
                    'options' => array(
                        'published' => __('Published', 'adforest'),
                        'trashed' => __('Trashed', 'adforest-rest-api'),
                        'expired' => __('Draft', 'adforest-rest-api'),
                    ),
                    'default' => 'expired'
                ),

                array(
                    'id' => 'sb_pro_event_send_email_admin',
                    'type' => 'switch',
                    'title' => esc_html__('Send Event Email To Admin', 'sb_pro'),
                    'default' => true,
                ),

                array(
                    'id' => 'sb_new_event_subject',
                    'type' => 'text',
                    'title' => esc_html__('New Event Email For Admin', 'dwt-listing'),
                    'desc' => ('%site_name% , %event_owner% , %event_title% will be translated accordingly.'),
                    'default' => 'You have New Event - DWT Listing',
                ),
                array(
                    'id' => 'sb_pro_event_from',
                    'type' => 'text',
                    'title' => esc_html__('Admin New Event FROM', 'dwt-listing'),
                    'desc' => esc_html__('FROM: NAME valid@email.com is compulsory as we gave in default.', 'dwt-listing'),
                    'default' => 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                ),
                array(
                    'id' => 'sb_pro_event_admin_email',
                    'type' => 'text',
                    'title' => __('Email for notification.', 'sb_pro'),
                    'required' => array('sb_pro_event_send_email_admin', '=', '1'),
                    'default' => get_option('admin_email'),
                ),
                array(
                    'id' => 'sb_pro_event_detial_message',
                    'type' => 'editor',
                    'title' => esc_html__('New Event Details', 'dwt-listing'),
                    'args' => array(
                        'teeny' => true,
                        'textarea_rows' => 10,
                        'wpautop' => false,
                    ),
                    'desc' => ('%site_name% , %event_owner% , %event_title% , %event_link% , %event_date%   will be translated accordingly.'),
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
<td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
<div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
<table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
<tbody>
<tr>
<td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
<td class="alert" style="font-family: \'Helvetica Neue\',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #000; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; background-color: #fff; margin: 0; padding: 20px;" align="center" valign="top" bgcolor="#fff"><img class="alignnone size-full wp-image-1437" src="https://listing.dwt_listing_directory.com/wp-content/uploads/2018/02/logo.png" alt="' . esc_html__('not found', 'dwt-listing') . '" width="200" height="40" /><br/>
A Designing and development company</td>
</tr>
<tr>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;"><b>Hy Admin,</b></span></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">A new event has been created</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Title: %event_title%</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Link: <a href="%event_link%">%event_title%</a></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Event Owner: %event_owner%</p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
<table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
</tr>
</tbody>
</table>
</div>
&nbsp;

</div></td>
<td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"></td>
</tr>
</tbody>
</table>
&nbsp;',
                ),
            ),
        );

        return $options;
    }

    public function events_stats_callback()
    {
        global $adforest_theme;
        $user_id = get_current_user_id();
        if (isset($adforest_theme['sb_pro_event_stats']) && $adforest_theme['sb_pro_event_stats']) {
            echo ' <div class="row">
                            <div class="col-xl-3 col-lg-4 col-sm-6">
                                <div class="icon-card mb-30">
                                    <div class="icon purple">
                                        <i class="lni lni-cart-full"></i>
                                    </div>
                                    <div class="content">
                                        <h6 class="mb-10">' . __('Total Events', 'sb_pro') . '</h6>
                                        <h3 class="text-semi-bold mb-10">' . sb_pro_get_all_events($user_id) . '</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-sm-6">
                                <div class="icon-card mb-30">
                                    <div class="icon success">
                                        <i class="lni lni-dollar"></i>
                                    </div>
                                    <div class="content">
                                        <h6 class="mb-10">' . __('Pending Events', 'sb_pro') . '</h6>
                                        <h3 class="text-semi-bold mb-10">' . sb_pro_listing_get_pending_events_count($user_id) . '</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-4 col-sm-6">
                                <div class="icon-card mb-30">
                                    <div class="icon primary">
                                        <i class="lni lni-credit-cards"></i>
                                    </div>
                                    <div class="content">
                                        <h6 class="mb-10">' . __('Published Events', 'sb_pro') . '</h6>
                                        <h3 class="text-semi-bold mb-10">' . sb_pro_listing_get_publish_events_count($user_id) . '</h3>
                                    </div>
                                </div>
                            </div>
                           <div class="col-xl-3 col-lg-4 col-sm-6">
                                <div class="icon-card mb-30">
                                    <div class="icon orange">
                                        <i class="lni lni-cross-circle"></i>
                                    </div>
                                    <div class="content">
                                        <h6 class="mb-10">' . __('Expired Events ', 'sb_pro') . '</h6>
                                        <h3 class="text-semi-bold mb-10">' . sb_pro_listing_get_events_status_count($user_id) . '</h3>
                                    </div>
                                </div>
                            </div>
                             </div>';
        }
    }

    public function sb_get_recent_event_list_callback()
    {
        $user_id = get_current_user_id();
        if ($user_id == 0) {
            return;
        }
        $args = array('post_type' => 'events', 'author' => $user_id, 'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'sb_pro_event_status',
                    'value' => 1,
                    'compare' => '=',
                ),
            ),
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $query = new \WP_Query($args);

        $html = "";
        if ($query->have_posts()) {
            $number = 0;
            $remove = '';
            while ($query->have_posts()) {
                $query->the_post();
                $pid = get_the_ID();
                $media = sb_pro_fetch_event_gallery($pid);

                $image[0] = "";
                if (count($media) > 0) {
                    $counting = 1;
                    foreach ($media as $m) {
                        if ($counting > 1)
                            break;
                        $mid = '';
                        if (isset($m->ID))
                            $mid = $m->ID;
                        else
                            $mid = $m;
                        $image = wp_get_attachment_image_src($mid, 'adforest-single-small');

                        $image[0] = isset($image[0]) ? $image[0] : adforest_get_ad_default_image_url('adforest-single-small');
                        $counting++;
                    }
                } else {
                    $image[0] = adforest_get_ad_default_image_url('adforest-single-small');
                }

                $event_start_date = get_post_meta($pid, 'sb_pro_event_start_date', true);
                $event_end_date = get_post_meta($pid, 'sb_pro_event_end_date', true);

                $html .= '<div class="card card-default  recent-event-list">  
        <div class="media text-secondary">
            <img src="' . $image[0] . '" class="mr-3 img-fluid rounded my-ads-image" alt="image">
            <div class="media-body">
                <h5 class="mt-0 mb-2 text-dark"><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h5>
                <ul class="list-unstyled">
                    <li class="d-flex">
                     <span> ' . esc_html__('From', 'sb_pro') . ' : ' . $event_start_date . '</span>
                    </li>
                    <li class="d-flex mb-1">
                      <span>' . esc_html__('To', 'sb_pro') . ' : ' . $event_start_date . '</span>
                    </li>
                </ul>
            </div>  
        </div>
    </div>';
            }
            wp_reset_postdata();
        } else {
            $no_found = get_template_directory_uri() . '/images/nothing-found.png';
            $html = '<div class="nothing-found recent-events">
                        <img src="' . $no_found . '" alt="">
                    <span>' . esc_html__('No Result Found', 'sb_pro') . '</span>
                  </div>';
        }
        return $html;
    }

    public function sb_get_sent_booking_list_callback($type)
    {
        $user_id = get_current_user_id();
        $paged = get_query_var('paged', 1);

        $args = array(
            'post_type' => 'sb_bookings',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged,
            'author' => $user_id
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $query = new \WP_Query($args);

        $html = "";
        $count = 0;
        if ($query->have_posts()) {
            $number = 0;
            $remove = '';
            /* Modal : booking detail model html */

            $html .= '
           <div>
           </div>     
           <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12">
                <div class="panel  event-container">          
                <div class="panel-body">
                <div class="table-responsive">
                <table class="table sb-admin-tabelz-panel table-hover ">
                              <thead>
                                    <tr>
                                        <th> ' . esc_html('User', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Listing', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Time Slot', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Date', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Status', 'sb_pro') . ' </th>
                                        <th> ' . esc_html('Details', 'sb_pro') . ' </th>
                                    </tr>
                               </thead>
                            <tbody> ';

            $order = isset($_GET['order_booking']) ? $_GET['order_booking'] : "";

            while ($query->have_posts()) {
                $query->the_post();
                $booking_id = get_the_ID();
                $booking_status = get_post_meta($booking_id, 'booking_status', true);
                if (isset($order) && $order != "4" && $order != "") {
                    if ($booking_status != $order) {
                        continue;
                    }
                }
                $booking_details = get_post_meta($booking_id, 'booking_details', true);
                $booking_details = json_decode($booking_details, true);
                $booker_name = isset($booking_details['booker_name']) ? $booking_details['booker_name'] : "";
                $booker_email = isset($booking_details['booker_email']) ? $booking_details['booker_email'] : "";
                $booker_phone = isset($booking_details['booker_phone']) ? $booking_details['booker_phone'] : "";
                $booking_slot_start = isset($booking_details['booking_slot_start']) ? $booking_details['booking_slot_start'] : "";
                $booking_slot_end = isset($booking_details['booking_slot_end']) ? $booking_details['booking_slot_end'] : "";

                $booking_date = isset($booking_details['booking_date']) ? $booking_details['booking_date'] : "";
                $booking_month = isset($booking_details['booking_month']) ? $booking_details['booking_month'] : "";
                $booking_day = isset($booking_details['booking_day']) ? $booking_details['booking_day'] : "";
                $booking_ad = isset($booking_details['booking_ad_id']) ? $booking_details['booking_ad_id'] : "";

                $formated_date = "";
                $booking_org_date = get_post_meta($booking_id, 'booking_org_date', true);
                if ($booking_org_date != "") {

                    $formated_date = date(get_option('date_format'), $booking_org_date);
                }

                $status = $booking_status;
                if ($status == 2) {
                    $status = esc_html__('Accepted', 'sb_pro');
                } else if ($status == 3) {
                    $status = esc_html__('Rejected', 'sb_pro');
                } else {
                    $status = esc_html__('Pending', 'sb_pro');
                }
                $html .= '<tr>
                     <td><span class="admin-listing-img">' . $booker_name . '</span>
                     </td>
                     <td><a href="' . get_the_permalink($booking_ad) . '"><span class="admin-listing-img">' . get_the_title($booking_ad) . '</span></a>
                     </td>
                  
                     <td><span class="admin-listing-img">' . $booking_slot_start . '-' . $booking_slot_end . '</span>
                     </td>      
                     <td><span class="admin-listing-img">' . $formated_date . '</span>
                     </td>
                     
                     <td><span class="admin-listing-img">' . $status . '</span>
                        </td>               
                     <td><a href="javascript:void(0)" class="view_booking_details" data-id ="' . $booking_id . '"><span class="admin-listing-img">' . esc_html__('View Detail', 'sb_pro') . '</span>
                        </td>            
                    </tr>';
                $count++;
            }
            wp_reset_postdata();
            $html .= '</tbody></table></div></div></div></div>';
            $html .= '<div  class="col-12"><div class="pagination-item">' . adforest_pagination_ads($query) . '</div></div>';
        }
        if ($count == 0) {
            $no_found = get_template_directory_uri() . '/images/nothing-found.png';
            $html .= '<div class="col-lg-12 col-md-12 col-xs-12  col-12"><div class="nothing-found recent-events dash-events">
                        <img src="' . $no_found . '" alt="">
                    <span>' . esc_html__('No Result Found', 'sb_pro') . '</span>
                  </div></div>';
        }

        $html .= '<div class="modal fade" id="booking-detail-modal" tabindex="-1" aria-labelledby="booking-detail-modal" aria-hidden="true">
                       <div class="modal-dialog">   
                       <div class="modal-content"  id = "booking-detail-content" >

                      </div>
                    </div>
                  </div>
                  <input type="hidden" id="prompt_heading" value = "' . esc_html__('Enter extra details here', 'sb_pro') . '">
                  <input type="hidden" id="no-detail-notify" value = "' . esc_html__('Enter details first', 'sb_pro') . '">
                   
                   ';

        return $html;
    }

    public function sb_get_booking_list_callback($type)
    {
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
            $number = 0;
            $remove = '';
            /* Modal : booking detail model html */

            $html .= '  
           <div class="col-md-12 col-lg-12 col-xs-12 col-sm-12 mb-30">
                <div class="panel  event-container">          
                <div class="panel-body">
                <div class="table-responsive">
                <table class="table sb-admin-tabelz-panel">
                              <thead>
                                    <tr>
                                        <th> ' . esc_html('User', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Listing', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Time Slot', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Date', 'sb_pro') . '</th>
                                        <th> ' . esc_html('Status', 'sb_pro') . ' </th>
                                        <th> ' . esc_html('Details', 'sb_pro') . ' </th>
                                    </tr>
                               </thead>
                            <tbody> ';

            $order = isset($_GET['order_booking']) ? $_GET['order_booking'] : "";

            while ($query->have_posts()) {
                $query->the_post();
                $booking_id = get_the_ID();
                $booking_status = get_post_meta($booking_id, 'booking_status', true);
                if (isset($order) && $order != "4" && $order != "") {
                    if ($booking_status != $order) {
                        continue;
                    }
                }
                $booking_details = get_post_meta($booking_id, 'booking_details', true);
                $booking_details = json_decode($booking_details, true);
                $booker_name = isset($booking_details['booker_name']) ? $booking_details['booker_name'] : "";
                $booker_email = isset($booking_details['booker_email']) ? $booking_details['booker_email'] : "";
                $booker_phone = isset($booking_details['booker_phone']) ? $booking_details['booker_phone'] : "";
                $booking_slot_start = isset($booking_details['booking_slot_start']) ? $booking_details['booking_slot_start'] : "";
                $booking_slot_end = isset($booking_details['booking_slot_end']) ? $booking_details['booking_slot_end'] : "";

                $booking_date = isset($booking_details['booking_date']) ? $booking_details['booking_date'] : "";
                $booking_month = isset($booking_details['booking_month']) ? $booking_details['booking_month'] : "";
                $booking_day = isset($booking_details['booking_day']) ? $booking_details['booking_day'] : "";
                $booking_ad = isset($booking_details['booking_ad_id']) ? $booking_details['booking_ad_id'] : "";

                $formated_date = "";
                $booking_org_date = get_post_meta($booking_id, 'booking_org_date', true);
                if ($booking_org_date != "") {

                    $formated_date = date(get_option('date_format'), $booking_org_date);
                }
                $status = '<select class="booking_status custom-select2" data-id = "' . $booking_id . '">
                         <option value="1"  ' . (($booking_status == 1) ? 'selected' : '') . '>' . esc_html__('Pending', 'sb_pro') . '</option>
                         <option value="2" ' . (($booking_status == 2) ? 'selected' : '') . '>' . esc_html__('Accepted', 'sb_pro') . '</option>
                         <option value = "3" ' . (($booking_status == 3) ? 'selected' : '') . '>' . esc_html__('Rejected', 'sb_pro') . '</option>
                         </select>';

                $html .= '<tr>
                     <td><span class="admin-listing-img">' . $booker_name . '</span>
                     </td>
                     <td><a href="' . get_the_permalink($booking_ad) . '"><span class="admin-listing-img">' . get_the_title($booking_ad) . '</span></a>
                     </td>
                  
                     <td><span class="admin-listing-img">' . $booking_slot_start . ' - ' . $booking_slot_end . '</span>
                     </td>      
                     <td><span class="admin-listing-img">' . $formated_date . '</span>
                     </td>
                     
                     <td><span class="admin-listing-img">' . $status . '</span>
                        </td>               
                     <td><a href="javascript:void(0)" class="view_booking_details" data-id ="' . $booking_id . '"><span class="admin-listing-img">' . esc_html__('View Detail', 'sb_pro') . '</span>
                        </td>            
                    </tr>';
                $count++;
            }
            wp_reset_postdata();
            $html .= '</tbody></table></div></div></div></div>';
            $html .= '<div  class="col-12"><div class="pagination-item">' . adforest_pagination_ads($query) . '</div></div>';
        }

        if ($count == 0) {
            $no_found = get_template_directory_uri() . '/images/nothing-found.png';
            $html .= '<div class="col-lg-12 col-md-12 col-xs-12  col-12"><div class="nothing-found recent-events dash-events card-style">
                        <img src="' . $no_found . '" alt="">
                    <span>' . esc_html__('No Result Found', 'sb_pro') . '</span>
                  </div></div>';
        }

        $html .= '<div class="modal fade" id="booking-detail-modal" tabindex="-1" aria-labelledby="booking-detail-modal" aria-hidden="true">
                       <div class="modal-dialog">   
                       <div class="modal-content"  id = "booking-detail-content" >

                      </div>
                    </div>
                  </div>
                  <input type="hidden" id="prompt_heading" value = "' . esc_html__('Enter extra details here', 'sb_pro') . '">
                  <input type="hidden" id="no-detail-notify" value = "' . esc_html__('Enter details first', 'sb_pro') . '">
                   
                   ';

        return $html;
    }

    public function sb_get_event_list_callback($type)
    {
        $user_id = get_current_user_id();
        $paged = get_query_var('paged', 1);
        if ($type == 'publish') {
            $args = array('post_type' => 'events', 'author' => $user_id, 'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'sb_pro_event_status',
                        'value' => 1,
                        'compare' => '=',
                    ),
                ),
                'posts_per_page' => get_option('posts_per_page'),
                'paged' => $paged,
            );
            $args = apply_filters('adforest_wpml_show_all_posts', $args);
            $query = new \WP_Query($args);
        }
        if ($type == 'pending') {
            $args = array(
                'post_type' => 'events',
                'post_status' => 'pending',
                'fields' => 'ids',
                'no_found_rows' => false,
                'author' => $user_id,
                'posts_per_page' => get_option('posts_per_page'),
                'paged' => $paged,
            );
            $args = apply_filters('adforest_wpml_show_all_posts', $args);
            $query = new \WP_Query($args);
        }
        if ($type == "expire") {
            $args = array('post_type' => 'events', 'author' => $user_id, 'post_status' => array('pending', 'draft', 'publish'),
                'meta_query' => array(
                    array(
                        'key' => 'sb_pro_event_status',
                        'value' => 0,
                        'compare' => '=',
                    ),
                ),
                'posts_per_page' => get_option('posts_per_page'),
                'paged' => $paged,
            );
            $args = apply_filters('adforest_wpml_show_all_posts', $args);
            $query = new \WP_Query($args);
        }
        $html = "";
        if ($query->have_posts()) {
            $number = 0;
            $remove = '';
            $html .= '<div class="card-style mb-30"><div class="col-md-12 col-lg-12 col-xs-12 col-sm-12">
                
                <div class="table-responsive">
                <table class="table top-selling-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>' . esc_html__('Events', 'sb_pro') . '</th>
                                        <th>' . esc_html__('From', 'sb_pro') . '</th>
                                        <th>' . esc_html__('To', 'sb_pro') . '</th>
                                        <th>' . esc_html__('Views', 'sb_pro') . '</th>
                                        <th>' . esc_html__('Action', 'sb_pro') . '</th>
                                    </tr>
                                </thead>
                                <tbody>
                                                                       ';
            while ($query->have_posts()) {
                $query->the_post();
                $pid = get_the_ID();
                $html .= apply_filters('sb_get_event_list_html', $pid);
            }
            wp_reset_postdata();
            $html .= '</tbody></table></div></div>';
            $html .= '<div  class="col-12"><div class="pagination-item">' . adforest_pagination_ads($query) . '</div></div></div>';
        } else {
            $no_found = get_template_directory_uri() . '/images/nothing-found.png';
            $html .= '<div class="col-lg-12 col-md-12 col-xs-12  col-12"><div class="card-style mb-30"><div class="nothing-found recent-events dash-events">
                        <img src="' . $no_found . '" alt="">
                    <span>' . esc_html__('No Result Found', 'sb_pro') . '</span>
                  </div></div></div>';
        }

        return $html;
    }


    public function sb_get_event_list_html_callback($pid)
    {
        if ($pid == "") {
            return;
        }
        global $adforest_theme;
        $media = sb_pro_fetch_event_gallery($pid);
        $image[0] = "";
        if (count($media) > 0) {
            $counting = 1;
            foreach ($media as $m) {
                if ($counting > 1)
                    break;
                $mid = '';
                if (isset($m->ID))
                    $mid = $m->ID;
                else
                    $mid = $m;
                $image = wp_get_attachment_image_src($mid, 'adforest-single-small');

                $image[0] = isset($image[0]) ? $image[0] : adforest_get_ad_default_image_url('adforest-single-small');
                $counting++;
            }
        } else {
            $image[0] = adforest_get_ad_default_image_url('adforest-single-small');
        }
        $event_start_date = get_post_meta($pid, 'sb_pro_event_start_date', true);
        $event_end_date = get_post_meta($pid, 'sb_pro_event_end_date', true);
        $url = get_the_permalink();
        $title = get_the_title();
        $views = "";
        if (function_exists('pvc_get_post_views')) {
            $views = pvc_get_post_views();
        }
        $sb_profile_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_profile_page']);
        $time_noww = strtotime("today");
        $inactive_class = "";
        $title_status = esc_html__('Active', 'sb_pro');
        if (strtotime($event_end_date) < $time_noww) {
            $inactive_class = "inactive";

            $title_status = esc_html__('Inactive', 'sb_pro');
        }
        return '<tr>
                     <td><span class="admin-listing-img"><a href="' . esc_url($url) . '">
                            <img class="img-responsive" src="' . $image[0] . '" alt="testing events"></a></span>
                     </td>
                     <td class="event_title"><a href="' . $url . '"><span class="admin-listing-title">' . $title . '</span> </a>
                         <a class="admin-listing-date" href="' . get_the_permalink($sb_profile_page) . '?page_type=attendee&id=' . get_the_ID() . ' "><i class="lnr lnr-calendar-full"></i> ' . esc_html__('View Attendees', 'sb_pro') . '</a>
                          <span title= "' . $title_status . '" class="sb_event_status ' . esc_attr($inactive_class) . '">
                                                        </span>                  
                      </td>
                        <td class="event-timingz">
                                <span>' . $event_start_date . '</span>
                            </td>
                            <td class="event-timingz">
                                <span>' . $event_end_date . '</span>
                             </td>
                           <td>' . $views . '</td>
                            <td class="action-data">
                                <button class="more-btn ml-10 dropdown-toggle"
                                                    id="moreAction' . get_the_ID() . '" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                <i class="lni lni-more-alt"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="moreAction<?php echo get_the_ID(); ?>">
                                    <li class="dropdown-item">
                                        <a href="' . get_the_permalink($sb_profile_page) . '?page_type=events&id=' . get_the_ID() . '">' . esc_html__("Edit", "adforest") . '</a>
                                    </li>
                                    <li class="dropdown-item">
                                        <a href="javascript:void(0)"  class="delete-my-events" data-myevent-id="' . get_the_ID() . '">' . esc_html__("Remove", "adforest") . '</a>
                                    </li>
                                </ul>
                            </td>                         
                            </tr>';
    }

    public function sb_get_business_hous_post_callback($ad_id)
    {
        global $adforest_theme;
        wp_enqueue_script('adforest-dt-sb', trailingslashit(get_template_directory_uri()) . '/assets/js/datepicker.min.js', false, false, true);
        $allow_business_hours = isset($adforest_theme['allow_business_hours']) ? $adforest_theme['allow_business_hours'] : false;
        if (!$allow_business_hours) {
            return;
        }
        $my_class = "";
        wp_enqueue_script("typeahead-adv", get_template_directory_uri() . "/assets/js/typeahead.adv.js", false, false, true);
        wp_enqueue_style("typeahead-adv", get_template_directory_uri() . "/assets/css//tyahead.css", false, false, 'all');
//        wp_enqueue_script("jqueryui", get_template_directory_uri() . "/assets/js/jquery/jquery.ui.min.js", false, false, false);
//        wp_enqueue_style('jquery-ui.min.css', get_template_directory_uri() . "/assets/css/jquery-ui.min.css", false, false, 'all');
//        wp_enqueue_script('Jquery-date-en', trailingslashit(get_template_directory_uri()) . '/assets/js/date-en-US.js', false, false, true);
        wp_enqueue_script("timeselect", get_template_directory_uri() . "/assets/js/timeselect.js", array("jquery"), false, false);

        $days = array();
        $listing_timezone = get_post_meta($ad_id, 'sb_pro_user_timezone', true);
        if (!empty(sb_pro_fetch_business_hours($ad_id))) {
            $days = sb_pro_fetch_business_hours($ad_id);
        } else {
            $dayss = sb_pro_week_days();
            foreach ($dayss as $key => $val) {
                $days[] = array("day_name" => $val, "start_time" => '', "end_time" => '', "closed" => '');
            }
        }
        $main_btn_color_text = isset($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : "";
        $days_name = "";
        foreach ($days as $key => $day) {
            $active = ($key == 0) ? "active" : "";
            $days_name .= '<li class="nav-item ">
                                                <a style="color: '.$main_btn_color_text.'" class="nav-link ' . $active . '"href="#tab1' . esc_attr($key) . '" data-bs-toggle="tab">' . esc_attr($day['day_name']) . '</a>
                                            </li>';
        }
        $tabs = "";
        foreach ($days as $key => $day) {
//get and set the start break and end break
            $break_from = isset($day['break_from']) && $day['break_from'] != "" ? trim(date("g:i A", strtotime($day['break_from']))) : "";
            $break_to = isset($day['break_too']) && $day['break_too'] != "" ? trim(date("g:i A", strtotime($day['break_too']))) : "";
            $on_off_break = isset($day['break']) ? $day['break'] : "";
            $show = $key == 0 ? 'in active show' : "";
            $closed_checked = ($day['closed'] == 1) ? 'checked = checked' : '';
            $break_checked = ($on_off_break) ? 'checked = checked' : '' .
                $tabs .= '<div class="tab-pane fade  ' . $show . '" id="tab1' . esc_attr($key) . '">
                                                <div class="row">
                                                    <div class="col-md-5 col-xs-12 col-sm-6">
                                                        <div class="form-group">
                                                            <label class="control-label"> ' . esc_html__('From', 'sb_pro') . ' </label>
                                                            <div class="input-group">
                                                                <span class="input-group-addon d-flex justify-content-center align-items-center"><i class="fa fa-clock"></i></span>
                                                                <input type="text" class="for_specific_page form-control timepicker" name="from[]" id="from-' . esc_attr($key) . '" placeholder="' . esc_html__('Select your business hours', 'sb_pro') . '" value="' . trim(date("g:i A", strtotime($day['start_time']))) . '">
                                                            </div>
                                                            <br>
                                                             <label class="control-label"> ' . esc_html__('Break From', 'sb_pro') . ' </label>
                                                            <div class="input-group">
                                                                <span class="input-group-addon d-flex justify-content-center align-items-center"><i class="fa fa-clock"></i></span>
                                                                <input type="text" class="for_specific_page form-control timepicker" name="breakfrom[]" id="breakfrom-' . esc_attr($key) . '" placeholder="' . esc_html__('Start Break', 'sb_pro') . '" value="' . esc_attr($break_from) . '">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5 col-xs-12 col-sm-6">
                                                        <div class="form-group">
                                                            <label class="control-label">' . esc_html__('To', 'sb_pro') . '</label>
                                                            <div class="input-group">
                                                                <span class="input-group-addon d-flex justify-content-center align-items-center"><i class="fa fa-clock"></i></span>
                                                                <input type="text" class="for_specific_page form-control timepicker"
                                                                       id="to-' . esc_attr($key) . '" name="to[]"
                                                                       placeholder="' . esc_html__('Select your business hours', 'sb_pro') . '"
                                                                       value="' . trim(date("g:i A", strtotime($day['end_time']))) . '">
                                                            </div>
                                                            <br>
                                                             <label class="control-label"> ' . esc_html__('Break to', 'sb_pro') . ' </label>
                                                            <div class="input-group">
                                                            
                                                                <span class="input-group-addon d-flex justify-content-center align-items-center"><i class="fa fa-clock"></i></span>
                                                                <input type="text" class="for_specific_page form-control timepicker" id="breakto-' . esc_attr($key) . '" name="breakto[]"
                                                                       placeholder="' . esc_html__('End Break', 'sb_pro') . '" value="' . esc_attr($break_to) . '">
                                                            </div>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 col-xs-12 col-sm-2">
                                                        <div class="form-group is_closed">
                                                            <label class="control-label">' . esc_html__('Closed', 'sb_pro') . ' </label>
                                                            <input name="is_closed[]" id="is_closed-' . esc_attr($key) . '" value="' . esc_attr($key) . '"  type="checkbox" ' . $closed_checked . ' class="custom-checkbox is_closed"></span>
                                                        </div>
                                             
                                                        <div class="form-group is_break">
                                                            <label class="control-label"> ' . esc_html__('Break', 'sb_pro') . ' </label>
                                                            <input name="is_break[]" id="is_break-' . esc_attr($key) . '" value="' . esc_attr($key) . '"  type="checkbox"  class="custom-checkbox is_break"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>';
        }
        $selected_val = 0;
        if (get_post_meta($ad_id, 'sb_pro_is_hours_allow', true) == 1) {
            if (get_post_meta($ad_id, 'sb_pro_business_hours', true) == '1') {
                $selected_val = 1;
            } else {
                $selected_val = 2;
            }
        }
        $main_btn_color = $adforest_theme['opt-theme-btn-color']['regular'] ? $adforest_theme['opt-theme-btn-color']['regular'] : "";
        return ' <div  class="business_hours_container" > <div class="row">  <div class="col-md-12 col-xs-12 col-sm-12"><div class="form-group has-feedback">
                    <label class="control-label"><strong> ' . esc_html__('Business Hours', 'sb_pro') . '</strong> </label>
                    <div class="pull-right">
                        <ul class="frontend_hours list-inline">
                            <li>
                                <input id="na" class="custom-checkbox"  name="type_hours"  value="0" ' . checked(0, $selected_val, false) . '  type="radio">
                                <label for="na">' . esc_html__('N/A', 'sb_pro') . '</label>
                            </li>
                            <li>
                                <input id="open" class="custom-checkbox"  name="type_hours" value="1" ' . checked(1, $selected_val, false) . '  type="radio">
                                <label for="open">' . esc_html__('Open 24/7', 'sb_pro') . '</label>
                            </li>
                            <li>
                                <input id="selective" class="custom-checkbox"  name="type_hours" value="2" ' . checked(2, $selected_val, false) . '  type="radio">
                                <label for="selective"> ' . esc_html__('Selective Hours', 'sb_pro') . '</label>
                            </li>
                            <input type="hidden" id="hours_type" name="hours_type" value="' . esc_attr($selected_val) . '">
                        </ul>
                    </div>
                </div>
                </div>
                </div>
          <div class="form-group  my-zones" id="timezone">
                    <label class="control-label">' . esc_html__('Select time zone', 'sb_pro') . '</label>
                    <div class="typeahead__container">
                        <div class="typeahead__field">
                            <div class="typeahead__query">
                                <input id="timezones" autocomplete="off" type="search" class="myzones-t form-control" value="' . esc_attr($listing_timezone) . '" name="listing_timezome">
                            </div>
                        </div>
                    </div>
                </div>     
                <div id="business-hours-fields" class="' . esc_attr($my_class) . '" >
                    <div class="row">
                     
                        <div class="col-md-12 col-xs-12 col-sm-12">
                            <div class="panel with-nav-tabs panel-info business-hours-post">
                                <div class="panel-heading">
                                    <ul class="nav nav-tabs" style="background-color: '. $main_btn_color .' ">
                                       ' . $days_name . '
                                    </ul>
                                </div>
                                <div class="panel-body">
                                    <div class="tab-content">
                                             ' . $tabs . '
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>  </div>';
    }

    public function sb_show_business_hours_callback($listing_id)
    {
//if busines hours allowed
        global $adforest_theme;
        $allow_business_hours = isset($adforest_theme['allow_business_hours']) ? $adforest_theme['allow_business_hours'] : false;

        if (!$allow_business_hours) {
            return;
        }


        if (get_post_meta($listing_id, 'sb_pro_is_hours_allow', true) == '1') {
//now check if its 24/7 or selective timimgz
            if (get_post_meta($listing_id, 'sb_pro_business_hours', true) == '1') {
                ?>
                <div class='widget-opening-hours widget'>
                    <div class='opening-hours-title tool-tip'
                         title="<?php echo esc_html__('Business Hours', 'sb_pro'); ?>">
                        <i class="far fa-clock"></i>
                        <span><?php echo esc_html__('Always Open', 'sb_pro'); ?></span>
                    </div>
                </div>
                <?php
            } else {
                $get_hours = sb_pro_show_business_hours($listing_id);
                $status_type = sb_pro_business_hours_status($listing_id);
                if ($status_type == 0 || $status_type == "") {
                    $business_hours_status = esc_html__('Closed', 'sb_pro');
                    $listing_timezone_for_break = get_post_meta($listing_id, 'sb_pro_user_timezone', true);
                    if (sb_pro_checktimezone($listing_timezone_for_break) == true) {
                        if ($listing_timezone_for_break != "") {
                            /* $status = esc_html__('Closed','sb_pro'); */
                            /* current day */
                            $current_day_today = lcfirst(date("l"));
                            /* current time */
                            $date_for_break = new \DateTime("now", new \DateTimeZone($listing_timezone_for_break));
                            $current_time_now = $date_for_break->format('h:i:s');
// numaric values of open time
                            $current_time_num = strtotime($current_time_now);
// start day time
                            $time_from1111 = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_' . $current_day_today . '_from', true)));
// start day numaric value
                            $start_time_numaric = strtotime($time_from1111);
//numaric values of opening soon
                            $startTime11 = date('H:i:s', strtotime("-30 minutes", strtotime($time_from1111)));
                            $startTime11_num = strtotime($startTime11);
                            if ($current_time_num > $startTime11_num && $startTime11_num < $start_time_numaric) {
                                $business_hours_status = esc_html__('Opening Soon', 'sb_pro');
                            }
                        }
                    }
                } else {
                    /* timezone of selected business hours */
                    $listing_timezone_for_break = get_post_meta($listing_id, 'sb_pro_user_timezone', true);

                    if (sb_pro_checktimezone($listing_timezone_for_break) == true) {
                        if ($listing_timezone_for_break != "") {
                            /* $status = esc_html__('Closed','sb_pro'); */
                            /* current day */
                            $current_day_for_break = lcfirst(date("l"));

                            /* current time */
                            $date_for_break = new \DateTime("now", new \DateTimeZone($listing_timezone_for_break));
                            $current_time_now = $date_for_break->format('H:i:s');
//current day
                            $current_day = strtolower(date('l'));
//check if current day is open or not
                            $is_break_on = get_post_meta($listing_id, '_timingz_break_' . $current_day . '_open', true);
// get start and end time of break of current time
                            $breeak_from1 = get_post_meta($listing_id, '_timingz_break_' . $current_day . '_breakfrom', true);
                            $breeak_tooo1 = get_post_meta($listing_id, '_timingz_break_' . $current_day . '_breakto', true);
// numaric values of current day start and end break
                            $current_time_num = strtotime($current_time_now);
                            $break_from_num = strtotime($breeak_from1);
                            $break_too_num = strtotime($breeak_tooo1);
//get start and end time of current day
                            $time_to_end = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_' . $current_day . '_to', true)));

// numaric values of current day
                            $end_time_numaric = strtotime($time_to_end);

// numaric value of closed soon
                            $endTime11 = date('H:i:s', strtotime("-30 minutes", strtotime($time_to_end)));
                            $endTime11_num = strtotime($endTime11);

                            if ($is_break_on == '1' && $current_time_num > $break_from_num && $current_time_num < $break_too_num) {
                                $business_hours_status = esc_html__('Break', 'sb_pro');
                            } elseif ($endTime11_num < $end_time_numaric && $current_time_num > $endTime11_num) {
                                $business_hours_status = esc_html__('Closing Soon', 'sb_pro');
                            } else {
                                $business_hours_status = $break_check_button = esc_html__('Open Now', 'sb_pro');
                            }
                        }
                    }
                }


                $class = '';
                if (is_rtl()) {
                    $class = 'flip';
                }
                ?>
                <div class='widget-opening-hours widget'>
                    <div class='opening-hours-title tool-tip'
                         title="<?php echo esc_html__('Business Hours', 'sb_pro'); ?>"
                         data-bs-toggle='collapse' data-bs-target='#opening-hours'>
                        <i class="far fa-clock"></i>
                        <span>
                            <?php echo esc_attr($business_hours_status); ?>
                        </span>
                        <i class="fa-solid fa-chevron-right <?php echo esc_attr($class); ?>"></i>
                    </div>
                    <div id='opening-hours' class='collapse in show'>
                        <?php
                        if (get_post_meta($listing_id, 'sb_pro_user_timezone', true) != '') {

                            echo '<div class="s-timezone"> ' . esc_html__('Business hours', 'sb_pro'
                                    . '') . ' : <strong>' . get_post_meta($listing_id, 'sb_pro_user_timezone', true) . '</strong></div>';
                        }
                        ?>
                        <ul>
                            <?php
                            if (is_array($get_hours) && count($get_hours) > 0) {


                                foreach ($get_hours as $key => $val) {
                                    $bk_f = isset($val['break_from']) && $val['break_from'] != "" ? trim(date("g:i A", strtotime($val['break_from']))) : "";
                                    $bk_to = isset($val['break_too']) && $val['break_too'] != "" ? trim(date("g:i A", strtotime($val['break_too']))) : "";
                                    $break_status = '';
                                    if ($bk_f != '' && $bk_to != '') {
                                        $break_status = esc_attr($bk_f) . '&nbsp - &nbsp' . esc_attr($bk_to);
                                    } else {
                                        $break_status = "";
                                    }
                                    if ($break_status != "") {
                                        $break_keyword = esc_html__('break', 'sb_pro') . ':';
                                    } else {
                                        $break_keyword = "";
                                    }
                                    $class = '';
                                    if ($val['current_day'] != '') {
                                        $class = 'current_day';
                                    }
                                    if ($val['closed'] == 1) {
                                        $class = 'closed';
                                        echo '' . $htm_return = '<li class="' . esc_html($class) . '"> 
                                 <span class="day-name"> ' . $val['day_name'] . ':</span>
                                 <span class="day-timing"> ' . esc_html__('Closed', 'sb_pro') . ' </span> </li>';
                                    } else {
                                        echo '' . $htm_return = ' <li class="' . esc_html($class) . '">
                                        <div>
                                <span class="day-name"> ' . $val['day_name'] . ':</span>
                                
                            
                                <span class="day-timing"> ' . esc_attr($val['start_time']) . '  -  ' . esc_attr($val['end_time']) . ' </span> 
                                  </div>
                                <div class="break_container">
                                 <span class="day-name"> ' . $break_keyword . '</span>
                                <span class="day-timing"> ' . $break_status . ' </span>
                                 <div>
                                 </li>';
                                    }
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <?php
            }
        }
    }

    /* adforest dashboard return all booked ads */

    function adforest_pro_get_booked_ads_list_callback()
    {
        global $adforest_theme;
        $sb_post_ad_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page']);
        $user_id = get_current_user_id();
        $paged = get_query_var('paged', 1);
        $args = array(
            'post_type' => 'ad_post',
            'author' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $paged,
            'order' => 'DESC',
            'orderby' => 'date',
            'meta_query' => array(
                array(
                    'key' => 'is_ad_booking_allow',
                    'value' => '1',
                    'compare' => '=',
                ),
            )
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $args = apply_filters('adforest_site_location_ads', $args, 'ads');
        $ads = new \WP_Query($args);
        $ads_list = "";

        if ($ads->have_posts()) {
            $number = 0;
            $remove = '';
            while ($ads->have_posts()) {
                $ads->the_post();
                $pid = get_the_ID();
                $status = get_post_meta(get_the_ID(), '_adforest_ad_status_', true);
                $cats_html = adforest_display_cats($pid);
                if ($status == '') {
                    $status = adforest_ad_statues('active');
                }
                $media = adforest_get_ad_images($pid);

                $image[0] = "";
                if (count($media) > 0) {
                    $counting = 1;
                    foreach ($media as $m) {
                        if ($counting > 1)
                            break;
                        $mid = '';
                        if (isset($m->ID))
                            $mid = $m->ID;
                        else
                            $mid = $m;
                        $image = wp_get_attachment_image_src($mid, 'adforest-single-small');

                        $image[0] = isset($image[0]) ? $image[0] : adforest_get_ad_default_image_url('adforest-single-small');
                        $counting++;
                    }
                } else {
                    $image[0] = adforest_get_ad_default_image_url('adforest-single-small');
                }

                $add_ons = '';

                $ad_featured = '<a class="mb-1 btn btn-sm btn-danger mr-2 sb_anchor sb_remove_booking adt-button-dark" href="javascript:void(0);"  data-aaa-id="' . esc_attr($pid) . '"  title ="' . esc_attr__('This will hide booking from ad', 'sb_pro') . '">' . __('Remove Booking', 'adforest') . '</a>';
                $add_ons = '<div class="bump-or-feature">
                     ' . $ad_featured . '
                     <a class="mb-1 btn btn-sm btn-info  edit_booking_option adt-button-dark-1" href="javascript:void(0);" data-aaa-id="' . esc_attr($pid) . '">' . __('Edit options', 'adforest') . '</a>
                    </div>';
                $edit_ad = '<a class="edit_booking_option" href="javascript:void(0);" data-aaa-id="' . esc_attr($pid) . '">' . __('Edit options', 'adforest') . '</a>';

                $ad_status = "";
                $status_container = "";

                $table_body = '
                    <tr class="holder-' . $pid . '">
                        <td>
                            <div class="product">
                                <div class="image">
                                    <img src="' . esc_url($image[0]) . '"
                                         alt="' . esc_attr(get_the_title()) . '"/>
                                </div>
                                <p class="text-sm">' . esc_html(get_the_title()) . '</p>
                            </div>
                        </td>  
                        <td>
                            <p class="text-sm">' . adforest_adPrice(get_the_ID(), '', '') . '</p>
                        </td>   
                        <td>
                            <div class="action justify-content-end">
                                <button class="more-btn ml-10 dropdown-toggle"
                                        id="moreAction' . get_the_ID() . '" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    <i class="lni lni-more-alt"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end"
                                    aria-labelledby="moreAction' . get_the_ID() . '">
                                    <li class="dropdown-item">
                                    ' . $edit_ad . '
                                    </li>
                                    <li class="dropdown-item">
                                    ' . $ad_featured . '
                                    </li>
                                </ul>
                            </div>
                        </td>           
                    </tr>
                ';

                $ads_list .= '
                                <div class="col-lg-6 col-xl-4 holder-' . $pid . '">
                                    <div class="card-style">  
                                        ' . $ad_status . '
                                        <div class="media text-secondary">
                                            <img src="' . esc_url($image[0]) . '" class="mr-3 img-fluid rounded my-ads-image" alt="' . esc_attr__('image', 'adforest') . '">
                                            <div class="media-body">
                                                <h5 class="mt-0 mb-2 text-dark"><a href="' . get_the_permalink($pid) . '"> ' . get_the_title() . '</a></h5>
                                                <ul class="list-unstyled">
                                                    <li class="d-flex mb-1">
                                
                                                        <span>' . adforest_adPrice(get_the_ID(), '', '') . '</span>
                                                    </li>
                                                    <li class="d-flex mb-1">                                    
                                                        ' . $add_ons . '
                                                    </li>
                                                </ul>
                                            </div>  
                                        </div>
                                    </div>
                                </div>';
            }
            wp_reset_postdata();

            $ads_list .= '<div  class="col-12"><div class="pagination-item">' . adforest_pagination_ads($ads) . '</div></div>';
        } else {
            $no_found = get_template_directory_uri() . '/images/nothing-found.png';
            $ads_list = '<div class="col-lg-12 col-md-12 col-xs-12  col-12"><div class="nothing-found recent-events dash-events">
                        <img src="' . $no_found . '" alt="">
                    <span>' . esc_html__('No Result Found', 'sb_pro') . '</span>
                  </div></div>';
        }
        return $ads_list;
//        return '
//            <div class="col-12">
//                <div class="card-style mb-30">
//                    <div class="table-responsive">
//                        <table class="table top-selling-table dashboard-my-ads">
//                            <thead>
//                            <tr>
//                                <th class="min-width">
//                                    <h6 class="text-sm text-medium">' . esc_html__("Ad Title", "adforest") . '</h6>
//                                </th>
//                                <th class="min-width">
//                                    <h6 class="text-sm text-medium">' . esc_html__("Price", "adforest") . '</h6>
//                                </th>
//                                <th>
//                                    <h6 class="text-sm text-medium text-end">' . esc_html__("Actions", "adforest") . '</h6>
//                                </th>
//                            </tr>
//                            </thead>
//                            <tbody>
//                            ' . $table_body . '
//                            </tbody>
//                        </table>
//                    </div>
//                 </div>
//            </div>
//        ';
    }

    public function get_event_detail_page_template_callback()
    {


        require_once DIR_PATH . 'template-parts/event-details.php';
    }
}

new Sb_Helper();
if (!function_exists('sb_pro_get_all_events')) {

    function sb_pro_get_all_events($user_id)
    {
        global $wpdb;
        $total = $wpdb->get_var("SELECT COUNT(*) AS total FROM  $wpdb->posts WHERE post_type = 'events'  AND post_author = '$user_id'");
        $total = apply_filters('adforest_get_lang_posts_by_author', $total, $user_id);
        return $total;
    }

}


if (!function_exists('sb_listing_get_pending_events_count')) {

    function sb_pro_listing_get_pending_events_count($user_id)
    {
        $args = array(
            'post_type' => 'events',
            'post_status' => 'pending',
            'fields' => 'ids',
            'no_found_rows' => false,
            'author' => $user_id,
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $result_query = new \WP_Query($args);
        wp_reset_postdata();
        return $result_query->found_posts;
    }

}


if (!function_exists('sb_pro_listing_get_publish_events_count')) {

    function sb_pro_listing_get_publish_events_count($user_id)
    {
        $args = array(
            'post_type' => 'events',
            'post_status' => 'publish',
            'fields' => 'ids',
            'no_found_rows' => false,
            'author' => $user_id,
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $result_query = new \WP_Query($args);
        wp_reset_postdata();
        return $result_query->found_posts;
    }

}

if (!function_exists('sb_pro_listing_get_events_status_count')) {

    function sb_pro_listing_get_events_status_count($user_id)
    {
        $count = 0;
        $args = array('post_type' => 'events', 'author' => $user_id, 'post_status' => array('publish', 'pending', 'draft', 'trash'),
            'meta_query' => array(
                array(
                    'key' => 'sb_pro_event_status',
                    'value' => 0,
                    'compare' => '=',
                ),
            ),
        );
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $query = new \WP_Query($args);
        return $query->post_count;
    }

}


if (!function_exists('sb_pro_week_days')) {

    function sb_pro_week_days()
    {
        return array(0 => esc_html__('Monday', 'sb_pro'), 1 => esc_html__('Tuesday', 'sb_pro'), 2 => esc_html__('Wednesday', 'sb_pro'), 3 => esc_html__('Thursday', 'sb_pro'), 4 => esc_html__('Friday', 'sb_pro'), 5 => esc_html__('Saturday', 'sb_pro'), 6 => esc_html__('Sunday', 'sb_pro'));
    }

}

/* fetch business hours */
if (!function_exists('sb_pro_fetch_business_hours')) {

    function sb_pro_fetch_business_hours($listing_id)
    {
        global $sb_pro_options;
        $days_name = sb_pro_week_days();
        $days = '';
        /* check option is yes or not */
        if (get_post_meta($listing_id, 'sb_pro_business_hours', true) != "") {
            $listing_is_opened = get_post_meta($listing_id, 'sb_pro_business_hours', true);
            $days = array();
            $custom_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            for ($a = 0; $a <= 6; $a++) {
                $week_days = lcfirst($custom_days[$a]);

                if (get_post_meta($listing_id, '_timingz_' . $week_days . '_open', true) == 1) {
//days which are opened
                    $is_break_on = get_post_meta($listing_id, '_timingz_break_' . $week_days . '_open', true);
                    $breeak_from1 = get_post_meta($listing_id, '_timingz_break_' . $week_days . '_breakfrom', true);
                    $breeak_tooo1 = get_post_meta($listing_id, '_timingz_break_' . $week_days . '_breakto', true);

                    $time_from = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_from', true)));
                    $time_to = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_to', true)));
                    $breeak_from = isset($breeak_from1) && $breeak_from1 != "" ? date('H:i:s', strtotime($breeak_from1)) : "";
                    $breeak_tooo = isset($breeak_tooo1) && $breeak_tooo1 != "" ? date('H:i:s', strtotime($breeak_tooo1)) : "";

                    $days[] = array("day_name" => $days_name[$a], "start_time" => $time_from, "end_time" => $time_to, "break_from" => $breeak_from, "break_too" => $breeak_tooo, "closed" => '', "break" => $is_break_on);
                } else {
//days which are closed
                    $time_from = date('g:i A', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_from', true)));
                    $time_to = date('g:i A', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_to', true)));
                    $days[] = array("day_name" => $days_name[$a], "start_time" => $time_from, "end_time" => $time_to, "closed" => 1, "break" => '');
                }
            }
            return $days;
        }
    }

}

if (!function_exists('sb_pro_show_business_hours')) {

    function sb_pro_show_business_hours($listing_id)
    {
        global $sb_pro_options;
        $days_name = sb_pro_week_days();
        $custom_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $days = '';
        $listing_is_opened = 0;
//check option is yes or not
        $listing_is_opened = get_post_meta($listing_id, 'sb_pro_business_hours', true);

        if ($listing_is_opened == 0) {
            $days = array();
            for ($a = 0; $a <= 6; $a++) {
                $week_days = lcfirst($custom_days[$a]);
                $user_id = get_post_field('post_author', $listing_id);
//current day
                $current_day = lcfirst(date("l"));
                if ($current_day == $week_days) {
                    $current_day = $current_day;
                } else {
                    $current_day = '';
                }
                if (get_post_meta($listing_id, '_timingz_' . $week_days . '_open', true) == 1) {

//days which are opened
                    if (get_user_meta($user_id, 'sb_pro_user_hours_type', true) != "" && get_user_meta($user_id, 'sb_pro_user_hours_type', true) == "24") {
                        $time_from = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_from', true)));
                        $time_to = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_to', true)));
                    } else {
                        $time_from = date('g:i a', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_from', true)));
                        $time_to = date('g:i a', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_to', true)));
                    }

                    $break_click_on = "";
                    $breeak_on = get_post_meta($listing_id, '_timingz_break_' . $week_days . '_open', true);
                    $break_click_on = isset($breeak_on) && $breeak_on != "" ? $breeak_on : "";
                    if ($break_click_on == 1) {
                        $breakk_from = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_break_' . $week_days . '_breakfrom', true)));
                        $breakk_to = date('H:i:s', strtotime(get_post_meta($listing_id, '_timingz_break_' . $week_days . '_breakto', true)));
                    } else {
                        $breakk_from = "";
                        $breakk_to = "";
                    }
                    $days[] = array("day_name" => $days_name[$a], "start_time" => $time_from, "end_time" => $time_to, "break_from" => $breakk_from, "break_too" => $breakk_to, "closed" => '', "current_day" => $current_day, 'break' => $break_click_on);

                    if (get_user_meta($user_id, 'sb_pro_user_hours_type', true) != "" && get_user_meta($user_id, 'sb_pro_user_hours_type', true) == "24") {

                    }
                } else {
//days which are closed
                    $days[] = array("day_name" => $days_name[$a], "closed" => '1', "current_day" => $current_day);
                }
            }
            return $days;
        }
    }

}

// Check Status Of Business Hours
if (!function_exists('sb_pro_business_hours_status')) {

    function sb_pro_business_hours_status($listing_id)
    {
        /* if listing open 24/7 */
        if (get_post_meta($listing_id, 'sb_pro_business_hours', true) == '1') {
            /* return esc_html__('Always Open','dwt-listing'); */
            return '2';
        } else if (get_post_meta($listing_id, 'sb_pro_business_hours', true) == '') {
            return '';
        } else {
            /* timezone of selected business hours */
            $listing_timezone = get_post_meta($listing_id, 'sb_pro_user_timezone', true);
            if (sb_pro_checktimezone($listing_timezone) == true) {
                if ($listing_timezone != "") {
                    /* $status = esc_html__('Closed','dwt-listing'); */
                    /* current day */
                    $current_day = lcfirst(date("l"));
                    /* current time */
                    $date = new \DateTime("now", new \DateTimeZone($listing_timezone));
                    $currentTime = $date->format('Y-m-d H:i:s');

                    $custom_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
                    /* get all weak days */
                    $times = array();
                    for ($a = 0; $a <= 6; $a++) {
                        $week_days = lcfirst($custom_days[$a]);
                        /* check if businnes hours avaible for current day */
                        /* if(get_post_meta($listing_id, '_timingz_'.$week_days.'_open', true) == 1) */
                        /* { */
                        $startTime = date('g:i a', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_from', true)));
                        $endTime = date('g:i a', strtotime(get_post_meta($listing_id, '_timingz_' . $week_days . '_to', true)));
                        $times[substr($week_days, 0, 3)] = $startTime . ' - ' . $endTime;
                        /* } */
                    }
                    $currentTime = strtotime($currentTime);

                    return isOpen($currentTime, $times);
                }
            }
        }
    }

}

function isOpen($now, $times)
{
    $open = "0"; // time until closing in seconds or 0 if closed
// merge opening hours of today and the day before
    $hours = array_merge(compileHours($times, strtotime('yesterday', $now)), compileHours($times, $now));
    foreach ($hours as $h) {
        if ($now >= $h[0] and $now < $h[1]) {
            $open = $h[1] - $now;
            return $open;
        }
    }
    return $open;
}

function compileHours($times, $timestamp)
{
    $times = $times[strtolower(date('D', $timestamp))];
    if (!strpos($times, '-'))
        return array();
    $hours = explode(",", $times);
    $hours = array_map('explode', array_pad(array(), count($hours), '-'), $hours);
    $hours = array_map('array_map', array_pad(array(), count($hours), 'strtotime'), $hours, array_pad(array(), count($hours), array_pad(array(), 2, $timestamp)));
    end($hours);
    if ($hours[key($hours)][0] > $hours[key($hours)][1])
        $hours[key($hours)][1] = strtotime('+1 day', $hours[key($hours)][1]);
    return $hours;
}

if (!function_exists('sb_pro_checktimezone')) {

    function sb_pro_checktimezone($timezone)
    {
        $zoneList = timezone_identifiers_list(); # list of (all) valid timezones
        if (in_array($timezone, $zoneList)) {
            return true;
        } else {
            return false;
        }
    }

}