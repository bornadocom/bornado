<?php
/* Template Name: Notification */
/**
 * The template for displaying Notifications.
 *
 * @package Adforest
 */
get_header();
adforest_custom_breadcrumbs();
global $wpdb;
$sb_profile_page = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
$profile_page_link = get_permalink($sb_profile_page);

$current_user_id = get_current_user_id();

$chat_notifications = $wpdb->get_results($wpdb->prepare("
       SELECT *
       FROM {$wpdb->prefix}sb_chat_messages
       WHERE receiver_id = %d
         AND read_status   = 0
       ORDER BY created DESC
       LIMIT 50
   ", $current_user_id));

$chat_unread_count = (int) $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->prefix}sb_chat_messages
    WHERE receiver_id = %d AND read_status = 0
", $current_user_id));

$theme_notifications = function_exists('adforest_get_user_theme_notifications')
    ? adforest_get_user_theme_notifications(
        $current_user_id,
        array(
            'limit'        => 50,
            'include_read' => false,
        )
    )
    : array();

$theme_unread_count = function_exists('adforest_count_theme_notifications')
    ? adforest_count_theme_notifications($current_user_id)
    : 0;

$unread_notifications = $chat_unread_count + $theme_unread_count;

$combined_notifications = array();

if (!empty($chat_notifications)) {
    foreach ($chat_notifications as $notification) {
        $combined_notifications[] = array(
            'type'      => 'chat',
            'timestamp' => strtotime($notification->created) ?: 0,
            'data'      => $notification,
        );
    }
}

if (!empty($theme_notifications)) {
    foreach ($theme_notifications as $notification) {
        $combined_notifications[] = array(
            'type'      => 'theme',
            'timestamp' => strtotime($notification->created_at) ?: 0,
            'data'      => $notification,
        );
    }
}

if (!empty($combined_notifications)) {
    usort(
        $combined_notifications,
        function ($a, $b) {
            $a_time = isset($a['timestamp']) ? (int) $a['timestamp'] : 0;
            $b_time = isset($b['timestamp']) ? (int) $b['timestamp'] : 0;

            if ($a_time === $b_time) {
                return 0;
            }

            return ($a_time < $b_time) ? 1 : -1;
        }
    );
}
?>

    <div class="main-content-area clearfix">
        <section class="section-padding notification-history">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="notification-header">
                            <div class="notification-left-content">
                                <h3 class="notification-heading">
                                    <?php echo __('Notifications', 'adforest'); ?>
                                    <i class="fa fa-bell text-muted"></i>
                                </h3>
                                <div class="notification-count">
                                    <?php
                                    printf(
                                        __('You have %s unread notifications', 'adforest'),
                                        '<span class="msgs_count">' . esc_html($unread_notifications) . '</span>'
                                    );
                                    ?>
                                </div>
                            </div>

                            <!-- Mark All as Read button -->
                            <?php if ($unread_notifications > 0) { ?>
                                <div class="notification-mark-btn-box">
                                    <button id="mark-all-read"
                                            class="adt-button-dark-1"
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('adforest_mark_all_read')); ?>">
                                        <?php esc_html_e('Mark all as read', 'adforest'); ?>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>


                        <div class="notification-list">
                            <?php if (!empty($combined_notifications)) : ?>
                                <?php foreach ($combined_notifications as $notification_item) :
                                    if ($notification_item['type'] === 'chat') {
                                        $notification = $notification_item['data'];
                                        $sender = get_userdata($notification->sender_id);
                                        $sender_name = $sender ? esc_html($sender->display_name) : __('Unknown User', 'adforest');
                                        $sender_avatar = get_avatar_url($notification->sender_id);
                                        $conversation_id = isset($notification->conversation_id) ? $notification->conversation_id : '';
                                        $notification_link = $profile_page_link ? add_query_arg(
                                            array(
                                                'page_type'       => 'msg',
                                                'conversation_id' => $conversation_id,
                                            ),
                                            $profile_page_link
                                        ) : '#';
                                        $message_raw = isset($notification->message) ? $notification->message : (isset($notification->content) ? $notification->content : '');
                                        $message_for_preview = str_replace('|', ' | ', $message_raw);
                                        $notification_context = wp_trim_words(wp_strip_all_tags($message_for_preview), 12, '...');
                                        $timestamp = isset($notification_item['timestamp']) ? (int) $notification_item['timestamp'] : time();
                                        $is_unread = isset($notification->read_status) ? intval($notification->read_status) === 0 : true;
                                        $item_class = $is_unread ? 'notification-item unread' : 'notification-item read';
                                        ?>
                                        <div class="<?php echo esc_attr($item_class); ?>">
                                            <a href="<?php echo esc_url($notification_link); ?>"
                                               class="notification-link notification-item-dash"
                                               data-notification-type="chat"
                                               data-message-id="<?php echo esc_attr($notification->id); ?>">
                                                <div class="notification-avatar">
                                                    <img src="<?php echo esc_url($sender_avatar); ?>"
                                                         alt="<?php echo esc_attr($sender_name); ?>">
                                                </div>
                                                <div class="notification-content">
                                                    <h6 class="notification-title">
                                                        <?php echo esc_html(sprintf(__('%s sent you a message', 'adforest'), $sender_name)); ?>
                                                    </h6>
                                                    <?php if (!empty($notification_context)) : ?>
                                                        <p class="notification-context">
                                                            <?php echo esc_html($notification_context); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <span class="notification-time">
                                                        <?php
                                                        echo esc_html(
                                                            wp_date(
                                                                get_option('date_format') . ' ' . get_option('time_format'),
                                                                $timestamp
                                                            )
                                                        );
                                                        ?>
                                                    </span>
                                                </div>
                                            </a>
                                        </div>
                                    <?php
                                    } else {
                                        $notification = $notification_item['data'];
                                        $timestamp = isset($notification_item['timestamp']) ? (int) $notification_item['timestamp'] : time();
                                        // Re-translate the title/message at display time — see
                                        // adforest_get_translated_theme_notification() for the rationale.
                                        $translated_notif = function_exists('adforest_get_translated_theme_notification')
                                            ? adforest_get_translated_theme_notification($notification)
                                            : array('title' => $notification->title, 'message' => $notification->message);
                                        $title = !empty($translated_notif['title']) ? $translated_notif['title'] : __('New notification', 'adforest');
                                        $message = !empty($translated_notif['message']) ? wp_strip_all_tags($translated_notif['message']) : '';
                                        $message_preview = wp_trim_words($message, 20, '...');
                                        $meta_link = '';
                                        if (is_array($notification->meta) && !empty($notification->meta['link'])) {
                                            $meta_link = $notification->meta['link'];
                                        }
                                        if (empty($meta_link)) {
                                            $dashboard_link = $profile_page_link ? $profile_page_link : get_the_permalink();
                                            $meta_link = add_query_arg('page_type', 'invoices', $dashboard_link);
                                        }
                                        $icon_map = array(
                                            'package_order' => 'fa fa-list-alt',
                                            'ad_post'       => 'fa fa-bullhorn',
                                        );
                                        $icon_class = isset($icon_map[$notification->type]) ? $icon_map[$notification->type] : 'lni lni-bell';
                                        $is_unread = isset($notification->is_read) ? intval($notification->is_read) === 0 : true;
                                        $item_class = $is_unread ? 'notification-item unread' : 'notification-item read';
                                        ?>
                                        <div class="<?php echo esc_attr($item_class); ?>">
                                            <a href="<?php echo esc_url($meta_link); ?>"
                                               class="notification-link notification-item-dash"
                                               data-notification-type="theme"
                                               data-notification-id="<?php echo esc_attr($notification->id); ?>">
                                                <div class="notification-avatar notification-icon">
                                                    <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <h6 class="notification-title">
                                                        <?php echo esc_html($title); ?>
                                                    </h6>
                                                    <?php if (!empty($message_preview)) : ?>
                                                        <p class="notification-context">
                                                            <?php echo esc_html($message_preview); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <span class="notification-time">
                                                        <?php
                                                        echo esc_html(
                                                            wp_date(
                                                                get_option('date_format') . ' ' . get_option('time_format'),
                                                                $timestamp
                                                            )
                                                        );
                                                        ?>
                                                    </span>
                                                </div>
                                            </a>
                                        </div>
                                        <?php
                                    }
                                endforeach; ?>
                            <?php else : ?>
                                <div class="no-notifications">
                                    <?php echo __('No notifications found.', 'adforest'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

<?php get_footer(); ?>
