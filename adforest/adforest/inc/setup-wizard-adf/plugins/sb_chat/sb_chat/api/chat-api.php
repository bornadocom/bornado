<?php
add_action('rest_api_init', function () {
    register_rest_route('sbchat/v1', '/conversations', [
        'methods' => 'GET',
        'callback' => 'sbchat_api_get_conversations',
        'permission_callback' => 'sbchat_basic_auth_permission_callback',
        'args' => [
            'user_id' => [
                'required' => false,
                'type' => 'integer',
            ],
            'limit' => [
                'required' => false,
                'type' => 'integer',
                'default' => 7,
            ],
            'offset' => [
                'required' => false,
                'type' => 'integer',
                'default' => 0,
            ],
        ],
    ]);
});


function sbchat_api_get_conversations(WP_REST_Request $request)
{
    $current_user = get_current_user_id();
    $user_id = $request->get_param('user_id') ?: $current_user;
    $limit = $request->get_param('limit');
    $offset = $request->get_param('offset');

    if (!$user_id) {
        return new WP_Error('no_user', __('User ID is required', 'sb_chat'), ['status' => 400]);
    }

    $conversations = sbchat_get_conversations_by_user_id($user_id, $limit, $offset);

    $data = [
        'text_strings' => [
            "all_conversations" => esc_html__("All Chats", "sb_chat"),
            "search_conversations" => esc_html__("Search a chat", "sb_chat"),
            "no_conversations" => esc_html__("No chat Found!", "sb_chat"),
            "delete" => esc_html__("Delete", "sb_chat"),
            "offline" => esc_html__("Offline", "sb_chat"),
            "online" => esc_html__("Online", "sb_chat"),
            "allowed_types_text" => __("Allowed File Types are", "ab_chat"),
            "select_image" => esc_html__("Image", "sb_chat"),
            "select_files" => esc_html__("Files", "sb_chat"),
            "enter_message" => esc_html__("Please enter a message", "sb_chat"),
            "file_downloaded" => esc_html__("File downloaded", "sb_chat"),
            "type_message" => esc_html__("Type a message...", "sb_chat"),
            "allowed_types" => esc_html__("jpg, jpeg, png, bmp, gif, webp, pdf, txt, doc, docx, xls, xlxs, ppt, pptx", "sb_chat"),
        ],
        'conversations' => []
    ];

    if (!$conversations) {
        return rest_ensure_response($data);
    }

    global $wpdb;
    $messages_table = $wpdb->prefix . 'sb_chat_messages';

    foreach ($conversations as $conv) {
        $recipient_id = ($user_id == $conv['user_2']) ? absint($conv['user_1']) : absint($conv['user_2']);
        $recipient = get_userdata($recipient_id);

        $user_key = ($user_id == $conv['user_1']) ? 'user_1' : 'user_2';
        $chat_delete_key = ($user_key == 'user_1') ? 'deleted_by_user_1' : 'deleted_by_user_2';

        if (isset($conv[$chat_delete_key]) && $conv[$chat_delete_key] == 1) {
            continue;
        }

        $sender_image = function_exists('adforest_get_user_dp') ? adforest_get_user_dp($user_id) : '';
        $recipient_image = function_exists('adforest_get_user_dp') ? adforest_get_user_dp($recipient_id) : '';

        $last_message = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT message, created FROM $messages_table 
                 WHERE conversation_id = %d 
                 ORDER BY id DESC LIMIT 1",
                $conv['id']
            ),
            ARRAY_A
        );

        $unread_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $messages_table 
                 WHERE conversation_id = %d
                 AND read_status = 0",
                $conv['id'], $user_id
            )
        );

        $is_online = sbchat_is_user_online($recipient_id);
        $last_seen = sbchat_get_user_last_seen($recipient_id);

        $data["conversations"][] = [
            'id' => $conv['id'],
            'recipient_id' => $recipient_id,
            'recipient_name' => is_wp_error($recipient) ? __('Removed User', 'sb_chat') : $recipient->display_name,
            'recipient_image' => $recipient_image,
            'recipient_online_status' => $is_online,
            'sender_id' => $user_id,
            'sender_image' => $sender_image,
            // 'updated' => date('F j, Y g:i A', strtotime($conv['updated'])),
            'updated' => $conv['updated'],
            'updated_human' => human_time_diff(strtotime($conv['updated']), current_time('timestamp', 1)) . ' ' . __('ago', 'sb_chat'),
            'is_unread' => ($unread_count > 0) ? true : false,
            'unread_count' => intval($unread_count),
            'last_message' => isset($last_message['message'])
                ? wp_trim_words(wp_strip_all_tags(explode('|', $last_message['message'])[1] ?? $last_message['message']), 100)
                : '',
        ];
    }

    return rest_ensure_response($data);
}

function sbchat_is_user_online($user_id)
{
    $sessions = WP_Session_Tokens::get_instance($user_id);
    $active_sessions = $sessions->get_all();

    if (empty($active_sessions)) {
        return false;
    }

    $online_threshold = 15 * 60;
    $current_time = current_time('timestamp', 1);

    $recent_activity = false;

    $last_login = get_user_meta($user_id, 'wp_last_login', true);
    if ($last_login && ($current_time - strtotime($last_login)) <= $online_threshold) {
        $recent_activity = true;
    }

    if (!$recent_activity) {
        global $wpdb;

        $recent_post = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_author = %d 
             AND post_date > %s 
             LIMIT 1",
            $user_id,
            date('Y-m-d H:i:s', $current_time - $online_threshold)
        ));

        if ($recent_post) {
            $recent_activity = true;
        }
    }

    if (!$recent_activity) {
        $recent_comment = $wpdb->get_var($wpdb->prepare(
            "SELECT comment_ID FROM {$wpdb->comments} 
             WHERE user_id = %d 
             AND comment_date > %s 
             LIMIT 1",
            $user_id,
            date('Y-m-d H:i:s', $current_time - $online_threshold)
        ));

        if ($recent_comment) {
            $recent_activity = true;
        }
    }

    $user_activity_transient = get_transient('user_activity_' . $user_id);
    if ($user_activity_transient) {
        $recent_activity = true;
    }

    return !empty($active_sessions) && $recent_activity;
}

function sbchat_get_user_last_seen($user_id)
{
    global $wpdb;

    $timestamps = [];

    $last_login = get_user_meta($user_id, 'wp_last_login', true);
    if ($last_login) {
        $timestamps[] = strtotime($last_login);
    }

    $last_post_date = $wpdb->get_var($wpdb->prepare(
        "SELECT post_date FROM {$wpdb->posts} 
         WHERE post_author = %d 
         AND post_status IN ('publish', 'draft', 'private') 
         ORDER BY post_date DESC LIMIT 1",
        $user_id
    ));
    if ($last_post_date) {
        $timestamps[] = strtotime($last_post_date);
    }

    $last_comment_date = $wpdb->get_var($wpdb->prepare(
        "SELECT comment_date FROM {$wpdb->comments} 
         WHERE user_id = %d 
         ORDER BY comment_date DESC LIMIT 1",
        $user_id
    ));
    if ($last_comment_date) {
        $timestamps[] = strtotime($last_comment_date);
    }

    $user_data = get_userdata($user_id);
    if ($user_data && $user_data->user_registered) {
        $timestamps[] = strtotime($user_data->user_registered);
    }

    if (!empty($timestamps)) {
        $latest_timestamp = max($timestamps);
        return date('Y-m-d H:i:s', $latest_timestamp);
    }

    return null;
}

function sbchat_track_user_activity($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if ($user_id) {
        set_transient('user_activity_' . $user_id, current_time('mysql', 1), 10 * MINUTE_IN_SECONDS);

        if (current_filter() === 'wp_login') {
            update_user_meta($user_id, 'wp_last_login', current_time('mysql', 1));
        }
    }
}

add_action('wp_login', 'sbchat_track_user_activity_on_login', 10, 2);
add_action('wp_ajax_nopriv_heartbeat', 'sbchat_track_user_activity');
add_action('wp_ajax_heartbeat', 'sbchat_track_user_activity');
add_action('save_post', 'sbchat_track_user_activity_on_post_save');
add_action('comment_post', 'sbchat_track_user_activity_on_comment', 10, 2);
add_action('wp_insert_comment', 'sbchat_track_user_activity_on_comment_insert');

function sbchat_track_user_activity_on_login($user_login, $user)
{
    sbchat_track_user_activity($user->ID);
}

function sbchat_track_user_activity_on_post_save($post_id)
{
    $post = get_post($post_id);
    if ($post && $post->post_author) {
        sbchat_track_user_activity($post->post_author);
    }
}

function sbchat_track_user_activity_on_comment($comment_id, $approved)
{
    $comment = get_comment($comment_id);
    if ($comment && $comment->user_id) {
        sbchat_track_user_activity($comment->user_id);
    }
}

function sbchat_track_user_activity_on_comment_insert($comment_id)
{
    $comment = get_comment($comment_id);
    if ($comment && $comment->user_id) {
        sbchat_track_user_activity($comment->user_id);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('sbchat/v1', '/conversation/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'sbchat_api_get_single_conversation',
        'permission_callback' => 'sbchat_basic_auth_permission_callback',
        'args' => [
            'id' => [
                'required' => true,
                'type' => 'integer',
            ],
        ],
    ]);
});

function sbchat_api_get_single_conversation($request)
{
    $conversation_id = (int)$request['id'];
    $user_id = get_current_user_id();

    if (!$conversation_id || !$user_id) {
        return new WP_Error('invalid_data', __('Invalid conversation ID or user not authenticated', 'sb_chat'), ['status' => 400]);
    }

    $conversation = sbchat_get_conversation_by_id($conversation_id);

    if (!$conversation) {
        return new WP_Error('not_found', __('Conversation not found', 'sb_chat'), ['status' => 404]);
    }

    if ($conversation['user_1'] != $user_id && $conversation['user_2'] != $user_id) {
        return new WP_Error('forbidden', __('You do not have access to this conversation', 'sb_chat'), ['status' => 403]);
    }

    // Check if the conversation has been deleted by the requesting user
    if ($conversation['user_1'] == $user_id && $conversation['deleted_by_user_1'] == 1) {
        return new WP_Error('not_found', __('Conversation not found', 'sb_chat'), ['status' => 404]);
    }

    if ($conversation['user_2'] == $user_id && $conversation['deleted_by_user_2'] == 1) {
        return new WP_Error('not_found', __('Conversation not found', 'sb_chat'), ['status' => 404]);
    }

    // Log core conversation state for debugging
    if (function_exists('error_log')) {
        error_log(sprintf(
            '[SBCHAT DEBUG] GET /conversation: user=%d conv=%d deleted_by_u1=%s deleted_by_u2=%s tdel_u1=%s tdel_u2=%s',
            $user_id,
            $conversation_id,
            isset($conversation['deleted_by_user_1']) ? (string)$conversation['deleted_by_user_1'] : 'NA',
            isset($conversation['deleted_by_user_2']) ? (string)$conversation['deleted_by_user_2'] : 'NA',
            isset($conversation['time_deleted_by_user_1']) ? (string)$conversation['time_deleted_by_user_1'] : 'NA',
            isset($conversation['time_deleted_by_user_2']) ? (string)$conversation['time_deleted_by_user_2'] : 'NA'
        ));
    }

    $recipient_id = ($conversation['user_1'] == $user_id) ? $conversation['user_2'] : $conversation['user_1'];
    $recipient = get_userdata($recipient_id);
    $is_online = sbchat_is_user_online($recipient_id);
    $recipient_name = is_wp_error($recipient) ? __('Removed User', 'sb_chat') : $recipient->display_name;

    $sender_image = '';
    if (function_exists('adforest_get_user_dp')) {
        $sender_image = adforest_get_user_dp($user_id);
    }

    $recipient_image = '';
    if (function_exists('adforest_get_user_dp')) {
        $recipient_image = adforest_get_user_dp($recipient_id);
    }

    global $wpdb;

    $wpdb->update(
        "{$wpdb->prefix}sb_chat_messages",
        ['read_status' => 1],
        [
            'conversation_id' => $conversation_id,
            'read_status' => 0
        ]
    );

    // If the conversation was previously deleted by the current user,
    // only return messages created AFTER the user's deletion time.
    $deleted_at = null;
    if ($conversation['user_1'] == $user_id) {
        $deleted_at = isset($conversation['time_deleted_by_user_1']) ? $conversation['time_deleted_by_user_1'] : null;
    } else {
        $deleted_at = isset($conversation['time_deleted_by_user_2']) ? $conversation['time_deleted_by_user_2'] : null;
    }

    // Normalize invalid zero date values
    if (!empty($deleted_at) && strpos($deleted_at, '0000-00-00') === 0) {
        $deleted_at = null;
    }

    $deleted_at_gmt = null;
    if (!empty($deleted_at)) {
        $deleted_at_gmt = get_gmt_from_date($deleted_at, 'Y-m-d H:i:s');
    }

    if (function_exists('error_log')) {
        error_log(sprintf('[SBCHAT DEBUG] GET /conversation: computed_deleted_at_local=%s computed_deleted_at_gmt=%s', $deleted_at ? $deleted_at : 'NULL', $deleted_at_gmt ? $deleted_at_gmt : 'NULL'));
    }

    if (!empty($deleted_at_gmt)) {
        // Treat deleted_at as GMT and compare against message created converted to UTC
        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sb_chat_messages 
                 WHERE conversation_id = %d 
                   AND CONVERT_TZ(created, @@session.time_zone, '+00:00') > %s 
                 ORDER BY created ASC",
                $conversation_id,
                $deleted_at_gmt
            ),
            ARRAY_A
        );
    } else {
        $messages = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}sb_chat_messages WHERE conversation_id = %d ORDER BY created ASC", $conversation_id),
            ARRAY_A
        );
    }

    if (function_exists('error_log')) {
        $db_count = is_array($messages) ? count($messages) : 0;
        error_log(sprintf('[SBCHAT DEBUG] GET /conversation: db_messages_count=%d (pre-safety-filter)', $db_count));
    }

    // Safety net: enforce deletion cutoff in PHP as well, normalize both to GMT
    if (!empty($deleted_at_gmt) && !empty($messages)) {
        $deleted_gmt_ts = strtotime($deleted_at_gmt);
        if ($deleted_gmt_ts !== false) {
            $messages = array_values(array_filter($messages, function ($m) use ($deleted_gmt_ts) {
                if (!isset($m['created'])) {
                    return false;
                }
                $created_local = $m['created'];
                // Convert message created (assumed local/DB session) to GMT for a fair comparison
                $created_gmt = get_gmt_from_date($created_local, 'Y-m-d H:i:s');
                $mc_gmt_ts = $created_gmt ? strtotime($created_gmt) : false;
                return $mc_gmt_ts !== false && $mc_gmt_ts > $deleted_gmt_ts;
            }));
        }
    }

    if (function_exists('error_log')) {
        $final_count = is_array($messages) ? count($messages) : 0;
        $first_created = $final_count > 0 ? $messages[0]['created'] : 'NA';
        $last_created = $final_count > 0 ? $messages[$final_count - 1]['created'] : 'NA';
        // Also log GMT conversions for deeper visibility
        $first_created_gmt = $final_count > 0 ? get_gmt_from_date($first_created, 'Y-m-d H:i:s') : 'NA';
        $last_created_gmt = $final_count > 0 ? get_gmt_from_date($last_created, 'Y-m-d H:i:s') : 'NA';
        error_log(sprintf('[SBCHAT DEBUG] GET /conversation: final_messages_count=%d first_created=%s first_created_gmt=%s last_created=%s last_created_gmt=%s', $final_count, $first_created, $first_created_gmt, $last_created, $last_created_gmt));
    }

    $messages_data = [];

    foreach ($messages as $message) {
        $post_id = null;
        $ad_title = null;
        $post_url = null;
        $first_img = null;
        $user_message = $message['message'];
        $message_attachment_ids = $message['attachment_ids'];

        $attachment_ids_array = [];
        $attachments = [];

        if (!empty($message_attachment_ids)) {
            $attachment_ids_array = array_filter(array_map('intval', explode(',', $message_attachment_ids)));

            foreach ($attachment_ids_array as $aid) {
                $attachment = get_post($aid);
                if ($attachment) {
                    $filename = get_post_meta($aid, '_wp_attached_file', true);

                    if ($filename) {
                        $basename = basename($filename);
                        $url = site_url('/wp-content/plugins/sb_chat/uploads/' . $basename);
                    } else {
                        $url = wp_get_attachment_url($aid);
                    }

                    if ($url) {
                        $attachments[] = [
                            'id' => $aid,
                            'url' => esc_url_raw($url),
                        ];
                    }
                }
            }
        }

        // Check if message contains post card data
        if (strpos($message['message'], '|') !== false) {
            [$ad_html, $user_message] = explode('|', $message['message'], 2);

            if (preg_match('/href=["\'](https?:\/\/[^"\']+)["\']/', $ad_html, $matches)) {
                $post_url = esc_url_raw($matches[1]);
                $post_id = url_to_postid($post_url);
            }

            if (preg_match('/<span[^>]*>(.*?)<\/span>/', $ad_html, $title_match)) {
                $ad_title = wp_strip_all_tags($title_match[1]);
            }

            if ($post_id && function_exists('adforest_get_ad_images')) {
                $image_thumbnail_size = 'adforest-single-post';
                $media = adforest_get_ad_images($post_id);
                $media_ids = [];

                if (is_array($media)) {
                    foreach ($media as $item) {
                        if ($item instanceof WP_Post) {
                            $media_ids[] = (int) $item->ID;
                        } elseif (is_numeric($item)) {
                            $media_ids[] = (int) $item;
                        }
                    }
                } elseif ($media instanceof WP_Post) {
                    $media_ids[] = (int) $media->ID;
                } elseif (!empty($media) && is_numeric($media)) {
                    $media_ids[] = (int) $media;
                }

                $media_ids = array_values(array_unique(array_filter($media_ids)));

                $img = (!empty($media_ids)) ? wp_get_attachment_image_src($media_ids[0], $image_thumbnail_size) : null;

                $first_img = isset($img[0]) ? $img[0] : "";
                
                if (empty($first_img)) {
                    $first_img = plugin_dir_url(dirname(__FILE__)) . 'assets/images/no-image.jpg';
                }
            }
        }

        $clean_created = preg_replace('/\s*:\s*/', ':', $message['created']);
        $timestamp = strtotime($clean_created);

        $wp_timestamp = $timestamp ? get_date_from_gmt(gmdate('Y-m-d H:i:s', $timestamp), 'F j, Y g:i A') : null;

        // Build base message data
        $message_data = [
            'id' => (int)$message['id'],
            'sender_id' => (int)$message['sender_id'],
            'message' => trim($user_message),
            'timestamp' => $message['created'],
            'attachments' => $attachments,
            'type' => $message['message_type'] == 1 ? 'text' : 'attachment',
        ];

        // Only add post-related fields if post_id exists
        if ($post_id) {
            $message_data['post_id'] = $post_id;
            $message_data['ad_title'] = $ad_title;
            $message_data['ad_link'] = $post_url;
            $message_data['ad_img'] = $first_img;
            $message_data['ad_price'] = html_entity_decode(get_woocommerce_currency_symbol()) . " " . get_post_meta($post_id, '_adforest_ad_price', true);
            if (function_exists('get_woocommerce_currency_symbol')) {
                $message_data['ad_currency'] = html_entity_decode(get_woocommerce_currency_symbol());
            } else {
                $message_data['ad_currency'] = '';
            }
        }

        $messages_data[] = $message_data;
    }

    $text_strings = [
        "online" => __("Online", "sb_chat"),
        "view_post" => __("View Post", "sb_chat"),
        "offline" => __("Offline", "sb_chat"),
        "gallery" => __("Gallery", "sb_chat"),
        "allowed_types_text" => __("Allowed File Types are", "ab_chat"),
        "select_image" => esc_html__("Image", "sb_chat"),
        "select_files" => esc_html__("Files", "sb_chat"),
        "allowed_types" => esc_html__("jpg, jpeg, png, bmp, gif, webp, pdf, txt, doc, docx, xls, xlxs, ppt, pptx", "sb_chat"),
        "max_size_allowed" => (int)SB_Chat::get_plugin_options('sb_max_file_size'),
        "max_upload_limit" => (int)SB_Chat::get_plugin_options('sbchat_max_files_upload'),
        "allowed_mime_types" => SB_Chat::get_plugin_options('sbchat_allowed_mime_types'),
        "type_message" => esc_html__("Type a message", "sb_chat"),
        "max_file_size_text" => esc_html__("Maximum upload size", "sb_chat"),
        "delete_chat" => __("Delete Chat", "sb_chat"),
        "delele_confirmation" => __("Are you sure you want to delete this chat?", "sb_chat"),
        "delete" => __("Delete", "sb_chat"),
        "cancel" => __("Cancel", "sb_chat"),
    ];

    return rest_ensure_response([
        'text_strings' => $text_strings,
        'conversation_id' => $conversation_id,
        'recipient_id' => $recipient_id,
        'is_recipient_online' => $is_online,
        'recipient_name' => $recipient_name,
        'recipient_image' => $recipient_image,
        'sender_id' => $user_id,
        'sender_image' => $sender_image,
        'updated' => $conversation['updated'],
        'messages_html' => $messages_data,
    ]);
}

add_action('rest_api_init', function () {
    register_rest_route('sbchat/v1', '/conversation/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'sbchat_api_delete_conversation',
        'permission_callback' => 'sbchat_basic_auth_permission_callback',
        'args' => [
            'id' => [
                'required' => true,
                'type' => 'integer',
            ],
        ],
    ]);
});

function sbchat_api_delete_conversation($request)
{
    global $wpdb;
    $conversation_id = (int)$request['id'];
    $user_id = get_current_user_id();

    if (!$conversation_id || !$user_id) {
        return new WP_Error('invalid_data', __('Invalid conversation ID or not authenticated.', 'sb_chat'), ['status' => 400]);
    }

    $table = SBCHAT_TABLE_CONVERSATIONS;

    $conversation = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $conversation_id),
        ARRAY_A
    );

    if (!$conversation) {
        return new WP_Error('not_found', __('Conversation not found.', 'sb_chat'), ['status' => 404]);
    }

    if ($user_id != $conversation['user_1'] && $user_id != $conversation['user_2']) {
        return new WP_Error('forbidden', __('You are not part of this conversation.', 'sb_chat'), ['status' => 403]);
    }

    if (function_exists('error_log')) {
        error_log(sprintf('[SBCHAT DEBUG] DELETE /conversation: user=%d conv=%d', $user_id, $conversation_id));
    }

    $wpdb->query('START TRANSACTION');

    try {
        // Determine which user is deleting and set the appropriate fields
        $update_data = [];
        $update_format = [];

        if ($user_id == $conversation['user_1']) {
            $update_data['deleted_by_user_1'] = 1;
            $update_data['time_deleted_by_user_1'] = current_time('mysql');
            $update_format[] = '%d';
            $update_format[] = '%s';
        } else {
            $update_data['deleted_by_user_2'] = 1;
            $update_data['time_deleted_by_user_2'] = current_time('mysql');
            $update_format[] = '%d';
            $update_format[] = '%s';
        }

        $updated = $wpdb->update(
            $table,
            $update_data,
            ['id' => $conversation_id],
            $update_format,
            ['%d']
        );

        if ($updated === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('db_error', __('Failed to delete conversation.', 'sb_chat'), ['status' => 500]);
        }

        if (function_exists('error_log')) {
            error_log(sprintf('[SBCHAT DEBUG] DELETE /conversation: updated=%s data=%s', json_encode($updated), json_encode($update_data)));
        }

        // Check if both users have deleted the conversation
        $updated_conversation = $wpdb->get_row(
            $wpdb->prepare("SELECT deleted_by_user_1, deleted_by_user_2 FROM $table WHERE id = %d", $conversation_id),
            ARRAY_A
        );

        $both_deleted = false;
        if ($updated_conversation && $updated_conversation['deleted_by_user_1'] == 1 && $updated_conversation['deleted_by_user_2'] == 1) {
            // Both users have deleted - now we can physically delete the conversation
            $wpdb->delete(
                $table,
                ['id' => $conversation_id],
                ['%d']
            );
            $both_deleted = true;
        }

        $wpdb->query('COMMIT');

        if (function_exists('error_log')) {
            error_log(sprintf('[SBCHAT DEBUG] DELETE /conversation: both_deleted=%s', $both_deleted ? 'true' : 'false'));
        }

        do_action('sbchat_conversation_deleted', $conversation_id, $conversation, $user_id, $both_deleted);

        return rest_ensure_response([
            'success' => true,
            'message' => $both_deleted
                ? __('Conversation permanently deleted.', 'sb_chat')
                : __('Conversation deleted for you.', 'sb_chat'),
            'conversation_id' => $conversation_id,
            'permanently_deleted' => $both_deleted
        ]);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('db_error', __('Database error occurred while deleting conversation.', 'sb_chat'), ['status' => 500]);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('sbchat/v1', '/send-message', array(
        'methods' => 'POST',
        'callback' => 'sbchat_rest_send_message',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function sbchat_rest_send_message(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Unauthorized. Please log in.', 'sb_chat')
        ], 403);
    }

    $start_new = $request->get_param('start_new') === true || $request->get_param('start_new') === 'true';
    $conversation_id = absint($request->get_param('conversation_id'));
    $recipient_id = absint($request->get_param('recipient_id'));
    $message = wp_kses_post($request->get_param('message'));
    $post_id = absint($request->get_param('post_id'));
    $uuids = $request->get_param('uuids') ?? [];
    $durls = $request->get_param('durls') ?? [];

    if (function_exists('error_log')) {
        $files = $request->get_file_params();
        error_log(sprintf('[SBCHAT DEBUG] POST /send-message: user=%d start_new=%s conv_id_param=%d recipient=%d post_id=%d files=%d', $user_id, $start_new ? 'true' : 'false', $conversation_id, $recipient_id, $post_id, is_array($files) ? count($files) : 0));
    }

    if ($recipient_id <= 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Recipient is required.', 'sb_chat')
        ], 400);
    }

    if ($recipient_id === $user_id) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('You cannot send a message to yourself.', 'sb_chat')
        ], 400);
    }

    if (get_user_meta($user_id, 'sb_is_user_blocked', true)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('You are blocked from messaging.', 'sb_chat')
        ], 403);
    }

    $files = $request->get_file_params();
    if (empty($message) && empty($files)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => __('Message content or files are required.', 'sb_chat')
        ], 400);
    }

    $chat = new Sb_Chat_Messages();

    if ($start_new) {
        $conversation_id = $chat->sb_start_conversation([
            'recipient' => $recipient_id,
            'message' => $message ?: __('File attachment', 'sb_chat'),
        ]);
    } else {
        if ($conversation_id <= 0) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Conversation ID is required when not starting a new one.', 'sb_chat')
            ], 400);
        }

        global $wpdb;
        $table = SBCHAT_TABLE_CONVERSATIONS;

        $query = $wpdb->prepare(
            "SELECT id FROM {$table} WHERE id = %d AND (user_1 = %d OR user_2 = %d)",
            $conversation_id,
            $user_id,
            $user_id
        );

        $conversation_exists = $wpdb->get_var($query);

        if (!$conversation_exists) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Conversation does not exist or you are not a participant.', 'sb_chat')
            ], 403);
        }
    }

    if (function_exists('error_log')) {
        error_log(sprintf('[SBCHAT DEBUG] POST /send-message: resolved_conversation_id=%d', $conversation_id));
    }

    $message_type = 'text';
    $post_attachment_ids = [];
    $upload_previews = '';

    if ($post_id > 0) {
        $post = get_post($post_id);
        if ($post) {
            $post_title = get_the_title($post_id);
            $post_link = get_permalink($post_id);
            $media = function_exists('adforest_get_ad_images') ? adforest_get_ad_images($post_id) : [];
            
            // Handle both attachment IDs and attachment objects
            $attachment_id = null;
            if (!empty($media) && is_array($media) && isset($media[0])) {
                // Check if it's an attachment object or an ID
                if (is_object($media[0]) && isset($media[0]->ID)) {
                    $attachment_id = $media[0]->ID;
                } elseif (is_numeric($media[0])) {
                    $attachment_id = $media[0];
                }
            }
            
            $img_url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'adforest-single-post') : '';
            
            if (empty($img_url)) {
                // Correct path: go up one directory from 'api' to 'sb_chat', then into 'assets'
                $img_url = plugin_dir_url(dirname(__FILE__)) . 'assets/images/no-image.jpg';
            }

            $post_card = '<p><div class="message-post-card"><div class="post-card-content"><div class="post-card-image"><img src="' . esc_url($img_url) . '" alt="' . esc_attr($post_title) . '"></div><div class="post-card-info"><h4 class="post-card-title"><span>' . esc_html($post_title) . '</span></h4><a href="' . esc_url($post_link) . '" class="post-card-link" target="_blank">' . __("View Post", "sb_chat") . '</a></div></div></div></p>';
            $message = $post_card . "|" . $message;
            $message_type = 'post_card';
        }
    }

    if (!empty($files)) {
        $message_type = 'media';

        $allowed_mime_types = SB_Chat::get_plugin_options('sbchat_allowed_mime_types');
        if ((is_array($allowed_mime_types) && count($allowed_mime_types) === 0) || empty($allowed_mime_types)) {
            $allowed_mime_types = array('image/png', 'image/jpg', 'image/jpeg');
        }

        $max_file_upload_size = (int)SB_Chat::get_plugin_options('sb_max_file_size');
        if (!isset($max_file_upload_size) || empty($max_file_upload_size) || $max_file_upload_size == 0) {
            $max_file_upload_size = 1024;
        }

        $max_files_upload = (int)SB_Chat::get_plugin_options('sbchat_max_files_upload');
        if (!isset($max_files_upload) || empty($max_files_upload) || $max_files_upload == 0) {
            $max_files_upload = 7;
        }

        if (count($files) > $max_files_upload) {
            return new WP_REST_Response([
                'success' => false,
                'message' => sprintf(__("Only %d files can be uploaded at a time.", 'sb_chat'), $max_files_upload)
            ], 400);
        }

        add_filter('upload_dir', array($chat, 'sbchat_upload_dir'), 10);

        $uploaded_media = [
            'images' => ['valid' => [], 'invalid' => []],
            'docs' => ['valid' => [], 'invalid' => []]
        ];

        foreach ($files as $file_key => $file_attachment) {
            if ($file_attachment['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $media_type = explode('/', $file_attachment['type'])[0];
            $media_size = $file_attachment['size'] / 1024;
            $media_index = str_replace('sbchat_mu_', '', $file_key);

            $media_ext = pathinfo($file_attachment['name'], PATHINFO_EXTENSION);
            $media_name = isset($uuids[$media_index]) ? $uuids[$media_index] . '.' . $media_ext : uniqid() . '.' . $media_ext;

            $is_valid_file = ($media_size <= $max_file_upload_size) && in_array($file_attachment['type'], $allowed_mime_types, true);
//            echo "SIZE: " . $media_size;
//            echo "Attachment Type: " . $file_attachment['type'];

            if ($media_type === 'image') {
                if (!$is_valid_file) {
                    $uploaded_media['images']['invalid'][] = [
                        'name' => $file_attachment['name'],
                        'uuid' => isset($uuids[$media_index]) ? $uuids[$media_index] : uniqid(),
                        'durl' => isset($durls[$media_index]) ? $durls[$media_index] : ''
                    ];
                    continue;
                }

                $chat->current_uuid = $media_name;

                $attachment_args = array(
                    'post_author' => $user_id,
                    'post_title' => $media_name,
                );

                $post_attachment_id = media_handle_upload($file_key, 0, $attachment_args, array(
                    'test_form' => false,
                    'unique_filename_callback' => array($chat, 'unique_filename_cb')
                ));

                if (is_wp_error($post_attachment_id)) {
                    $uploaded_media['images']['invalid'][] = [
                        'name' => $file_attachment['name'],
                        'uuid' => isset($uuids[$media_index]) ? $uuids[$media_index] : uniqid(),
                        'durl' => isset($durls[$media_index]) ? $durls[$media_index] : ''
                    ];
                    continue;
                }

                $post_attachment_ids[] = $post_attachment_id;
                $uploaded_media['images']['valid'][] = [
                    'id' => $post_attachment_id,
                    'path' => get_bloginfo('url') . '/wp-content/plugins/sb_chat/uploads/' . $media_name,
                    'uuid' => isset($uuids[$media_index]) ? $uuids[$media_index] : uniqid()
                ];
            } elseif ($media_type === 'application' || $media_type === 'text') {
                if (!$is_valid_file) {
                    $uploaded_media['docs']['invalid'][] = [
                        'name' => $file_attachment['name'],
                        'ext' => $media_ext,
                        'size' => $media_size,
                        'uuid' => isset($uuids[$media_index]) ? $uuids[$media_index] : uniqid()
                    ];
                    continue;
                }

                $chat->current_uuid = $media_name;

                $attachment_args = array(
                    'post_author' => $user_id,
                    'post_title' => $media_name,
                );

                $post_attachment_id = media_handle_upload($file_key, 0, $attachment_args, array(
                    'test_form' => false,
                    'unique_filename_callback' => array($chat, 'unique_filename_cb')
                ));

                if (is_wp_error($post_attachment_id)) {
                    $uploaded_media['docs']['invalid'][] = [
                        'name' => $file_attachment['name'],
                        'ext' => $media_ext,
                        'size' => $media_size,
                        'uuid' => isset($uuids[$media_index]) ? $uuids[$media_index] : uniqid()
                    ];
                    continue;
                }

                $post_attachment_ids[] = $post_attachment_id;
                $uploaded_media['docs']['valid'][] = [
                    'id' => $post_attachment_id,
                    'name' => $file_attachment['name'],
                    'path' => get_bloginfo('url') . '/wp-content/plugins/sb_chat/uploads/' . $media_name,
                    'ext' => $media_ext,
                    'size' => $media_size,
                    'uuid' => isset($uuids[$media_index]) ? $uuids[$media_index] : uniqid()
                ];
            }
        }

        $upload_previews = sbchat_generate_upload_previews($uploaded_media);

        remove_filter('upload_dir', array($chat, 'sbchat_upload_dir'));

        $chat->current_uuid = null;
    }

    $new_message = [
        'conversation_id' => $conversation_id,
        'sender_id' => $user_id,
        'receiver_id' => $recipient_id,
        'message' => $message ?: '',
        'attachment_ids' => !empty($post_attachment_ids) ? implode(',', $post_attachment_ids) : '',
        'message_type' => $message_type,
    ];

    if ($post_id > 0) {
        $new_message['post_id'] = $post_id;
    }

    // Enhanced error handling for emoji/character encoding issues
    try {
        // Validate UTF-8 encoding
        if (!empty($message) && !mb_check_encoding($message, 'UTF-8')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Invalid character encoding in message.', 'sb_chat')
            ], 400);
        }

        // Check for 4-byte characters (emojis)
        if (!empty($message) && preg_match('/[\x{10000}-\x{10FFFF}]/u', $message)) {
            // Log this for debugging
//            error_log('SB Chat: Message contains 4-byte UTF-8 characters (emojis): ' . $message);

            // Check if database supports utf8mb4
            global $wpdb;
            $charset = $wpdb->get_var("SELECT @@character_set_database");
            if ($charset !== 'utf8mb4') {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => __('Emojis are not supported. Please use text only.', 'sb_chat')
                ], 400);
            }
        }

        $message_sent = $chat->sb_send_new_message($new_message);

        if ($message_sent) {
            if (function_exists('adforestAPI_messages_sent_func')) {
                try {
                    $notification_text = $message;
                    if ($message_type === 'media' && empty($message)) {
                        $notification_text = __('File attachment', 'sb_chat');
                    } elseif ($message_type === 'post_card') {
                        $notification_text = __('Shared a post', 'sb_chat');
                    }

                    adforestAPI_messages_sent_func(
                        'sent',
                        $recipient_id,
                        $user_id,
                        $user_id,
                        $conversation_id,
                        0,
                        $notification_text,
                        current_time('timestamp')
                    );
                } catch (\Exception $e) {
                    // Silently ignore push notification errors (e.g. Firebase not configured)
                }
            }

            if (function_exists('error_log')) {
                error_log(sprintf('[SBCHAT DEBUG] POST /send-message: message_sent id=%s conv_id=%d', json_encode($message_sent), $conversation_id));
            }

            $response_data = [
                'success' => true,
                'message' => __('Message sent successfully.', 'sb_chat'),
                'conversation_id' => $conversation_id,
            ];

            if (!empty($upload_previews)) {
                $response_data['upload_previews'] = $upload_previews;
                $response_data['uploaded_media'] = $uploaded_media;
            }

            return new WP_REST_Response($response_data, 200);
        }

    } catch (Exception $e) {
        // Log the actual error for debugging
        error_log('SB Chat Error: ' . $e->getMessage());

        // Check if it's a charset/encoding error
        if (strpos($e->getMessage(), 'charset') !== false ||
            strpos($e->getMessage(), 'utf8') !== false ||
            strpos($e->getMessage(), 'character set') !== false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Character encoding error. Emojis may not be supported.', 'sb_chat')
            ], 400);
        }

        return new WP_REST_Response([
            'success' => false,
            'message' => __('Failed to send message.', 'sb_chat')
        ], 500);
    }

    return new WP_REST_Response([
        'success' => false,
        'message' => __('Failed to send message.', 'sb_chat')
    ], 500);
}

// Helper function to check database emoji support
function sbchat_check_emoji_support()
{
    global $wpdb;

    // Check database charset
    $db_charset = $wpdb->get_var("SELECT @@character_set_database");
    $connection_charset = $wpdb->get_var("SELECT @@character_set_connection");

    return ($db_charset === 'utf8mb4' && $connection_charset === 'utf8mb4');
}

// Sanitization function that preserves emojis
function sbchat_sanitize_message($message)
{
    // Use wp_kses_post to allow HTML but preserve emojis
    return wp_kses_post($message);

    // Alternative: If you want plain text only but with emojis
    // return sanitize_textarea_field($message);
}

/**
 * Generate upload previews for mobile app
 */
function sbchat_generate_upload_previews($uploaded_media)
{
    $previews = '';
    $valid_images = $uploaded_media['images']['valid'];
    if (!empty($valid_images)) {
        if (count($valid_images) <= 4) {
            foreach ($valid_images as $image) {
                $img_src_full = wp_get_attachment_image_src($image['id'], 'full');
                $img_src_thumbnail = wp_get_attachment_image_src($image['id'], array(300, 200));

                $previews .= '<li class="message-bubble reply" style="display: none;">';
                $previews .= '<div class="message-media">';
                $previews .= '<a data-fslightbox="fsl_' . esc_attr($image['uuid']) . '" href="' . $img_src_full[0] . '">';
                $previews .= '<img src="' . $img_src_thumbnail[0] . '" id="' . esc_attr($image['uuid']) . '" />';
                $previews .= '</a>';
                $previews .= '</div>';
                $previews .= '</li>';
            }
        } else {
            $previews .= '<li class="message-bubble reply" style="display: none;">';
            $previews .= '<div class="message-media">';
            $previews .= '<div class="grid-media">';

            $last_image_key = count($valid_images) - 1;
            foreach ($valid_images as $index => $image) {
                $img_src_full = wp_get_attachment_image_src($image['id'], 'full');
                $img_src_thumbnail = wp_get_attachment_image_src($image['id'], array(300, 200));

                $previews .= '<a data-fslightbox="fsl_' . esc_attr($valid_images[$last_image_key]['uuid']) . '" href="' . $img_src_full[0] . '">';
                $previews .= '<img src="' . $img_src_thumbnail[0] . '" id="' . esc_attr($image['uuid']) . '" />';
                $previews .= '</a>';

                if ($index === $last_image_key) {
                    $previews .= '<div class="overlay">';
                    $previews .= '<span class="images-counter">' . (count($valid_images) - 1) . '+</span>';
                    $previews .= '</div>';
                }
            }

            $previews .= '</div>';
            $previews .= '</div>';
            $previews .= '</li>';
        }
    }

    $invalid_images = $uploaded_media['images']['invalid'];
    if (!empty($invalid_images)) {
        foreach ($invalid_images as $image) {
            $previews .= '<li class="message-bubble reply" style="display: none;">';
            $previews .= '<div class="message-media disable-media">';
            $previews .= '<img src="' . esc_attr($image['durl']) . '" id="' . esc_attr($image['uuid']) . '" />';
            $previews .= '<span class="error-msg">' . __("Could not upload the image!", "sb_chat") . '</span>';
            $previews .= '<div class="disable-overlay"></div>';
            $previews .= '</div>';
            $previews .= '</li>';
        }
    }

    $valid_docs = $uploaded_media['docs']['valid'];
    if (!empty($valid_docs)) {
        foreach ($valid_docs as $doc) {
            $previews .= '<li class="message-bubble reply" style="display: none;">';
            $previews .= '<div class="message-file-main">';
            $previews .= '<div class="message-file">';
            $previews .= '<div class="main-left">';
            $previews .= '<div class="icon">';
            $previews .= '<img src="' . get_bloginfo('url') . '/wp-content/plugins/sb_chat/assets/images/icons/' . esc_attr($doc['ext']) . '-icon.svg" />';
            $previews .= '</div>';
            $previews .= '<div class="right-cont">';
            $previews .= '<span class="title"><a target="_blank" href="' . esc_url($doc['path']) . '">' . esc_html($doc['name']) . '</a></span>';
            $previews .= '<small class="size">' . esc_html(round($doc['size'], 2)) . ' ' . __("KB", "sb_chat") . '</small>';
            $previews .= '<span class="type">' . __("Uploaded", "sb_chat") . ' ' . date('Y/m/d') . '</span>';
            $previews .= '</div>';
            $previews .= '</div>';
            $previews .= '</div>';
            $previews .= '</div>';
            $previews .= '</li>';
        }
    }

    $invalid_docs = $uploaded_media['docs']['invalid'];
    if (!empty($invalid_docs)) {
        foreach ($invalid_docs as $doc) {
            $previews .= '<li class="message-bubble reply" style="display: none;">';
            $previews .= '<div class="message-file-main disable-file">';
            $previews .= '<div class="message-file">';
            $previews .= '<div class="main-left">';
            $previews .= '<div class="icon">';
            $previews .= '<img src="' . get_bloginfo('url') . '/wp-content/plugins/sb_chat/assets/images/icons/' . esc_attr($doc['ext']) . '-icon.svg" />';
            $previews .= '</div>';
            $previews .= '<div class="right-cont">';
            $previews .= '<span class="title">' . esc_html($doc['name']) . '</span>';
            $previews .= '<small class="size">' . esc_html(round($doc['size'], 2)) . ' ' . __("KB", "sb_chat") . '</small>';
            $previews .= '<span class="type">' . __("Uploaded", "sb_chat") . ' ' . date('Y/m/d') . '</span>';
            $previews .= '</div>';
            $previews .= '</div>';
            $previews .= '<div class="main-right">';
            $previews .= '<img src="' . get_bloginfo('url') . '/wp-content/plugins/sb_chat/assets/images/icons/error-icon.svg" />';
            $previews .= '</div>';
            $previews .= '</div>';
            $previews .= '<span class="error-msg">' . __("Failed to upload file!", "sb_chat") . '</span>';
            $previews .= '</div>';
            $previews .= '</li>';
        }
    }

    return $previews;
}

add_action('rest_api_init', function () {
    register_rest_route('sbchat/v1', '/check-conversation', [
        'methods' => 'GET',
        'callback' => 'sbchat_api_check_conversation',
        'permission_callback' => 'sbchat_basic_auth_permission_callback',
        'args' => [
            'recipient_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param) && $param > 0;
                }
            ],
        ],
    ]);
});

function sbchat_api_check_conversation(WP_REST_Request $request)
{
    global $wpdb;

    $current_user = get_current_user_id();
    $recipient_id = absint($request->get_param('recipient_id'));

    if (!$current_user) {
        return new WP_Error('no_user', __('User not authenticated', 'sb_chat'), ['status' => 401]);
    }

    if (!$recipient_id) {
        return new WP_Error('invalid_recipient', __('Invalid recipient ID', 'sb_chat'), ['status' => 400]);
    }

    if ($current_user === $recipient_id) {
        return new WP_Error('same_user', __('Cannot check conversation with yourself', 'sb_chat'), ['status' => 400]);
    }

    $recipient = get_userdata($recipient_id);
    if (!$recipient || is_wp_error($recipient)) {
        return new WP_Error('user_not_found', __('Recipient user not found', 'sb_chat'), ['status' => 404]);
    }

    $table = SBCHAT_TABLE_CONVERSATIONS;
    $conversation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, user_1, user_2, deleted_by_user_1, deleted_by_user_2, created, updated 
             FROM $table 
             WHERE (user_1 = %d AND user_2 = %d) 
             OR (user_1 = %d AND user_2 = %d)",
            $current_user, $recipient_id, $recipient_id, $current_user
        ),
        ARRAY_A
    );

    $exists = false;
    $conversation_id = null;
    $is_deleted = false;

    if ($conversation) {
        $conversation_id = (int)$conversation['id'];
        $exists = true;

        $user_key = ($current_user == $conversation['user_1']) ? 'deleted_by_user_1' : 'deleted_by_user_2';
        $is_deleted = isset($conversation[$user_key]) && $conversation[$user_key] == 1;
    }

    $response_data = [
        'exists' => $exists,
        'conversation_id' => $conversation_id,
        'is_deleted' => $is_deleted,
        'current_user_id' => $current_user,
        'recipient_id' => $recipient_id,
    ];

    if ($exists && $conversation) {
        $response_data['created'] = $conversation['created'];
        $response_data['updated'] = $conversation['updated'];
        $response_data['updated_human'] = human_time_diff(strtotime($conversation['updated']), current_time('timestamp', 1)) . ' ' . __('ago', 'sb_chat');
    }

    return rest_ensure_response($response_data);
}