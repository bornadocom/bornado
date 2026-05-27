<?php
function sbchat_filter_cron_interval( $schedules ) {

    $run_cron_after_minutes = (int) SB_Chat::get_plugin_options( 'run_cron_after_minutes' );
    if ( $run_cron_after_minutes <= 0 || $run_cron_after_minutes > 1440 ) {
        $run_cron_after_minutes = 15;
    }

    $schedules['sbchat_cron_minutes'] = array(
        'interval' => MINUTE_IN_SECONDS * $run_cron_after_minutes,
        'display'  => esc_html__( 'SBChat Cron Duration', 'sb_chat' ),
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'sbchat_filter_cron_interval' );

function sbchat_cron_notify_unread_messages_via_email() {

    error_log( '[SBChat] Cron start: sbchat_cron_notify_unread_messages_via_email' );

    $unemailed_unread_messages = sbchat_get_all_unemailed_unread_messages();
    if ( $unemailed_unread_messages === false ) {
        error_log( '[SBChat] No un-emailed unread messages found.' );
        return false;
    }
    error_log( '[SBChat] Un-emailed unread messages fetched: ' . count( (array) $unemailed_unread_messages ) );

    $email_notifications = array();
    foreach( $unemailed_unread_messages as $row ) {
        $receiver_id = (int) $row['receiver_id'];
        if ( $receiver_id <= 0 ) {
            continue;
        }
        $email_notifications[$receiver_id][] = array(
            'message_id' => (int) $row['id'],
            'conversation_id' => (int) $row['conversation_id'],
            'message_sender_id' => (int) $row['sender_id'],
            'notification_receiver_id' => $receiver_id,
        );
    }

    if ( is_array( $email_notifications ) && count( $email_notifications ) > 0 )
        $email_notifications = array_values( $email_notifications );
    error_log( '[SBChat] Recipients to notify: ' . ( is_array( $email_notifications ) ? count( $email_notifications ) : 0 ) );

    foreach( $email_notifications as $email_notification ) {

        $receiver_id = $email_notification[0]['notification_receiver_id'];
        $receiver_firstname = get_user_meta( $receiver_id, 'first_name', true );
        $receiver_lastname = get_user_meta( $receiver_id, 'last_name', true );

        $receiver_fullname = '';
        if ( ( isset( $receiver_firstname ) && isset( $receiver_lastname ) ) && ( ! empty( $receiver_firstname ) && ! empty( $receiver_lastname ) )  )
            $receiver_fullname = ucwords( esc_html( $receiver_firstname . ' ' . $receiver_lastname ) );

        $receiver_exist = get_user_by( 'id', $receiver_id );
        if ( ! $receiver_exist )
            continue;

        $receiver_data = get_userdata( $receiver_id );

        if ( empty( $receiver_fullname ) )
            $receiver_fullname = $receiver_data->user_nicename;

        $total_unread_messages_received = count( $email_notification );
        error_log( '[SBChat] Preparing email for user_id=' . $receiver_id . ' unread_count=' . $total_unread_messages_received );
        $email_notification_body = '';

        $message_ids = array();

        foreach( $email_notification as $unread_message_notification ) {

            $sender_id = $unread_message_notification['message_sender_id'];
            $sender_firstname = get_user_meta( $sender_id, 'first_name', true );
            $sender_lastname = get_user_meta( $sender_id, 'last_name', true );

            $sender_fullname = '';
            if ( ( isset( $sender_firstname ) && isset( $sender_lastname ) ) && ( ! empty( $sender_firstname ) && ! empty( $sender_lastname ) )  )
                $sender_fullname = ucwords( esc_html( $sender_firstname . ' ' . $sender_lastname ) );

            $sender_exist = get_user_by( 'id', $sender_id );
            if ( ! $sender_exist )
                continue;

            $sender_data = get_userdata( $sender_id );
            if ( empty( $sender_fullname ) )
                $sender_fullname = $sender_data->user_nicename;

            /* translators: %s: sender's full name */
            $new_message_text = sprintf( __( '%s has sent you a new message.', 'sb_chat' ), '<i>' . $sender_fullname . '</i>' );
            $email_notification_body .= '<span style="font-size: 17px; width: 100%; display: block; white-space: nowrap; line-height: 1.5;"> &#9745; ' . $new_message_text . ' </span>';

            $message_ids[] = (int) $unread_message_notification['message_id'];
        }

        $email_notification_template = SB_Chat::get_plugin_options( 'unread_messages_templates' );
        if ( isset( $email_notification_template ) && ! empty( $email_notification_template ) ) {

            $dashboard_page_id = SB_Chat::get_plugin_options( 'sb-dashboard-page' );
            $dashboard_link = '';
            if ( ! empty( $dashboard_page_id ) ) {
                $dashboard_link = get_permalink( $dashboard_page_id );
            }
            if ( empty( $dashboard_link ) ) {
                $dashboard_link = admin_url( 'admin.php?page=sbChat-menu' );
            }
            $dashboard_link .= '?page_type=msg';
            error_log( '[SBChat] Dashboard link resolved for user_id=' . $receiver_id . ' link=' . $dashboard_link );

            $notification_message = sprintf( esc_html__( 'You have %d unread messages.', 'sb_chat' ), $total_unread_messages_received );
            $notification_message .= $email_notification_body;

            $email_notification_template = str_replace(
                array( '%receiver_name%', '%notification_message%', '%dashboard_link%' ),
                array( $receiver_fullname, $notification_message, esc_url( $dashboard_link ) ),
                $email_notification_template
            );

            if ( ! empty( $receiver_data->user_email ) ) {
                $headers = array( 'Content-Type: text/html; charset=UTF-8' );
                error_log( '[SBChat] Sending email to user_id=' . $receiver_id . ' email=' . $receiver_data->user_email );
                $receiver_notified = wp_mail( $receiver_data->user_email, __( 'Unread Message Notification', 'sb_chat' ), $email_notification_template, $headers );
                error_log( '[SBChat] Email send result for user_id=' . $receiver_id . ' success=' . ( $receiver_notified ? '1' : '0' ) );
            } else {
                $receiver_notified = false;
                error_log( '[SBChat] Receiver has no email address. user_id=' . $receiver_id );
            }

            if ( $receiver_notified ) {
                if ( is_array( $message_ids ) && count( $message_ids ) > 0 ) {
                    sbchat_mark_messages_emailed( $message_ids );
                }
                error_log( '[SBChat] Marked messages as notified: ' . implode( ',', $message_ids ) );
            } else {
                error_log( '[SBChat] Email not sent; messages remain unemailed for user_id=' . $receiver_id );
            }
        } else {
            error_log( '[SBChat] Email template is empty or missing. Skipping notifications.' );
        }
    }
    error_log( '[SBChat] Cron end: sbchat_cron_notify_unread_messages_via_email' );
}
add_action( 'unread_conversations_notify_cron', 'sbchat_cron_notify_unread_messages_via_email' );
