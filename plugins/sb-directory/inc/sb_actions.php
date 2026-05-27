<?php
namespace SbPro\Inc;
class Sb_Actions {
    public function __construct() {
        add_action('sb_send_booking_status_change_email', array($this, 'sb_send_booking_status_change_email_callback'), 10, 2);
        add_action('sb_notify_on_new_event', array($this, 'sb_notify_on_new_event_callback'), 10, 2);
    }

    public function sb_send_booking_status_change_email_callback($booking_id = "", $status = "", $extra_details = "") {
        global $adforest_theme;
        $is_email_allowed = isset($adforest_theme['send_booking_status_email']) ? $adforest_theme['send_booking_status_email'] : "";
        if ($is_email_allowed && $booking_id != "") { /* either email allowed from theme option and booking id not empty */
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
            if ($booking_day != "" && $booking_date != "" && $booking_month != "") {
                $formated_date = strtotime($booking_day . $booking_date . $booking_month);
                $formated_date = date(get_option('date_format'), $formated_date);
            }

            $booking_time = $booking_slot_start . "-" . $booking_slot_end;

            $ad_title = get_the_title($booking_ad);
            $ad_link = get_the_permalink($booking_ad);

            if ($booker_email != "" && $status == "2") { /* if status is accepted */
                $msg_keywords = array('%customer%', '%booking_date%', '%booking_time%', '%ad_title%', '%ad_link%', '%extra_details%',);
                $msg_replaces = array($booker_name, $formated_date, $booking_time, $ad_title, $ad_link, $extra_details);
                $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_booking_status_approved_message']);
                $subject = isset($adforest_theme['send_booking_status_subject']) ? $adforest_theme['send_booking_status_subject'] : "Booking info";
                $from = isset($adforest_theme['send_booking_status_from']) ? $adforest_theme['send_booking_status_from'] : "Booking info";
                $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");
                wp_mail($booker_email, $subject, $body, $headers);
            } else if ($booker_email != "" && $status == "3") { /* if status is rejected */
                $msg_keywords = array('%customer%', '%booking_date%', '%booking_time%', '%ad_title%', '%ad_link%', '%extra_details%');
                $msg_replaces = array($booker_name, $formated_date, $booking_time, $ad_title, $ad_link, $extra_details);
                $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_booking_status_approved_message']);
                $subject = isset($adforest_theme['send_booking_status_subject']) ? $adforest_theme['send_booking_status_subject'] : "Booking info";
                $from = isset($adforest_theme['send_booking_status_from']) ? $adforest_theme['send_booking_status_from'] : "Booking info";
                $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");
                wp_mail($booker_email, $subject, $body, $headers);
            }
        }
    }
    
    
    
    //Send email on new Event
 public   function sb_notify_on_new_event_callback($event_id , $params)
    {
        global $adforest_theme;
        //send email to admin
        $author_id = get_post_field('post_author', $event_id);
        $user_info = get_userdata($author_id);
        $event_owner_name = $user_info->user_email;
        
        /* fetch event meta */
        $event_venue = $event_date = '';
        $event_start_date = isset($params['event_start_date']) ? sanitize_text_field($params['event_start_date']) : "";
        $event_end_date   = isset($params['event_end_date']) ? sanitize_text_field($params['event_end_date']) : "";
        $event_venue      = isset($params['sb_user_address']) ? sanitize_text_field($params['sb_user_address']) : "";    
        
        $event_date   =    "";
        if ($event_start_date != "" && $event_end_date != "") {
            $to   = esc_html__('To : ', 'sb_pro');
            $from = esc_html__('From : ', 'sb_pro');
            $event_date = $to . ' ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event_start_date)) . ' ' . $from . ' ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event_end_date));
        }
        if (isset($adforest_theme['sb_pro_event_send_email_admin']) && $adforest_theme['sb_pro_event_send_email_admin'] == '1') {
            
            $to =  isset($adforest_theme['sb_pro_event_admin_email'])  ? $adforest_theme['sb_pro_event_admin_email']  :  get_option('admin_email');
            $subject = __('New Event', 'sb_pro') . '-' . get_bloginfo('name');
            $body = '<html><body><p>' . __('Got new event', 'sb_pro') . ' <a href="' . get_edit_post_link($event_id) . '">' . get_the_title($event_id) . '</a></p></body></html>';
            $from = get_bloginfo('name');
         
            if (isset($adforest_theme['sb_pro_event_from']) && $adforest_theme['sb_pro_event_from'] != "") {
                $from = $adforest_theme['sb_pro_event_from'];
            }
            $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");
            $author_id = get_post_field('post_author', $event_id);
            $user_info = get_userdata($author_id);
            if (isset($adforest_theme['sb_pro_event_detial_message']) && $adforest_theme['sb_pro_event_detial_message'] != "") {
                $subject_keywords = array('%site_name%', '%event_owner%', '%event_title%');
                $subject_replaces = array(get_bloginfo('name'), $user_info->display_name, get_the_title($event_id));
                $subject = str_replace($subject_keywords, $subject_replaces, $adforest_theme['sb_new_event_subject']);
                $msg_keywords = array('%site_name%', '%event_owner%', '%event_title%', '%event_link%' ,  '%event_date%' );
                $msg_replaces = array(get_bloginfo('name'), $user_info->display_name, get_the_title($event_id), get_the_permalink($event_id) , $event_date);
            }
            $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_pro_event_detial_message']);
            wp_mail($to, $subject, $body, $headers);
        }  
    }
}


new Sb_Actions();
