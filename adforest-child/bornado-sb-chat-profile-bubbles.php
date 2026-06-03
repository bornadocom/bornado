<?php
/**
 * SB Chat bubble skin overrides for profile/messages pages.
 *
 * Keeps all chat UI tweaks inside the child theme so parent theme and
 * plugin updates remain safe.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_is_sb_chat_messages_screen')) {
    /**
     * Detect the classic dashboard chat view and the modern messages template.
     *
     * @return bool
     */
    function bornado_is_sb_chat_messages_screen()
    {
        if (is_admin()) {
            return false;
        }

        if (function_exists('is_page_template') && is_page_template('page-messages.php')) {
            return true;
        }

        if (function_exists('is_page_template') && is_page_template('page-theme-dashboard.php')) {
            $page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';
            return $page_type === 'msg';
        }

        return false;
    }
}

if (!function_exists('bornado_enqueue_sb_chat_profile_bubbles_assets')) {
    /**
     * Load child-theme-only chat bubble overrides.
     */
    function bornado_enqueue_sb_chat_profile_bubbles_assets()
    {
        if (!bornado_is_sb_chat_messages_screen()) {
            return;
        }

        $style_path = get_stylesheet_directory() . '/assets/css/bornado-sb-chat-profile-bubbles.css';
        if (!file_exists($style_path)) {
            return;
        }

        wp_enqueue_style(
            'bornado-sb-chat-profile-bubbles',
            get_stylesheet_directory_uri() . '/assets/css/bornado-sb-chat-profile-bubbles.css',
            array(),
            (string) filemtime($style_path)
        );

        $admin_proxy_path = get_stylesheet_directory() . '/assets/js/bornado-sb-chat-admin-proxy.js';
        if ((wp_script_is('sb-chat-admin-script', 'registered') || wp_script_is('sb-chat-admin-script', 'enqueued')) && file_exists($admin_proxy_path)) {
            global $wp_scripts;
            if (isset($wp_scripts->registered['sb-chat-admin-script'])) {
                $original_admin_src = $wp_scripts->registered['sb-chat-admin-script']->src;
                $wp_scripts->registered['sb-chat-admin-script']->src = get_stylesheet_directory_uri() . '/assets/js/bornado-sb-chat-admin-proxy.js';
                wp_add_inline_script(
                    'sb-chat-admin-script',
                    'window.BornadoSbChatAdminSource = ' . wp_json_encode($original_admin_src) . ';',
                    'before'
                );
            }
        }

        $script_path = get_stylesheet_directory() . '/assets/js/bornado-sb-chat-profile-bubbles.js';
        if (!file_exists($script_path)) {
            return;
        }

        wp_enqueue_script(
            'bornado-sb-chat-profile-bubbles',
            get_stylesheet_directory_uri() . '/assets/js/bornado-sb-chat-profile-bubbles.js',
            array('jquery'),
            (string) filemtime($script_path),
            true
        );

        wp_localize_script(
            'bornado-sb-chat-profile-bubbles',
            'BornadoSbChatBubbles',
            array(
                'locale' => determine_locale(),
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'bornado_enqueue_sb_chat_profile_bubbles_assets', 250);

if (!function_exists('bornado_sb_chat_message_unix_from_local')) {
    /**
     * Convert a WP-local datetime string into a Unix timestamp in UTC.
     *
     * @param string $created_local MySQL datetime stored by the chat plugin.
     * @return int
     */
    function bornado_sb_chat_message_unix_from_local($created_local)
    {
        $created_local = is_string($created_local) ? trim($created_local) : '';
        if ($created_local === '') {
            return 0;
        }

        $created_gmt = get_gmt_from_date($created_local, 'Y-m-d H:i:s');
        $unix = $created_gmt ? strtotime($created_gmt . ' UTC') : false;
        if ($unix !== false && $unix > 0) {
            return (int) $unix;
        }

        $fallback = strtotime($created_local);
        return $fallback !== false ? (int) $fallback : 0;
    }
}

if (!function_exists('bornado_sb_chat_build_bubble_meta')) {
    /**
     * Build one timestamp entry per rendered chat bubble.
     *
     * @param int $current_user_id Current user ID.
     * @param int $conversation_id Conversation ID.
     * @return array<int,array<string,mixed>>
     */
    function bornado_sb_chat_build_bubble_meta($current_user_id, $conversation_id)
    {
        global $wpdb, $sb_plugin_options;

        $current_user_id = (int) $current_user_id;
        $conversation_id = (int) $conversation_id;
        if ($current_user_id <= 0 || $conversation_id <= 0) {
            return array();
        }

        $messages_table = $wpdb->prefix . 'sb_chat_messages';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, sender_id, message, attachment_ids, created
                 FROM $messages_table
                 WHERE conversation_id = %d
                 ORDER BY created ASC, id ASC",
                $conversation_id
            )
        );

        if (empty($rows)) {
            return array();
        }

        $words_filters = isset($sb_plugin_options['sb_chat_bad_words_filter']) ? $sb_plugin_options['sb_chat_bad_words_filter'] : array();
        $words = is_array($words_filters)
            ? array_filter(array_map('trim', $words_filters))
            : array_filter(array_map('trim', explode(',', (string) $words_filters)));
        $replace = isset($sb_plugin_options['sb_chat_bad_words_replace']) ? $sb_plugin_options['sb_chat_bad_words_replace'] : '';

        $bubbles = array();

        foreach ($rows as $row) {
            $sender_id = isset($row->sender_id) ? (int) $row->sender_id : 0;
            $message_class = ($current_user_id === $sender_id) ? 'reply' : 'sender';
            $message = isset($row->message) ? (string) $row->message : '';
            $unix = bornado_sb_chat_message_unix_from_local(isset($row->created) ? (string) $row->created : '');

            $outside_raw = '';
            $message_raw = $message;
            $is_voice_marker = (bool) preg_match('/^\s*\[adf-voice\].+?\[\/adf-voice\]\s*$/', $message);

            if (!$is_voice_marker) {
                $parts = explode('|', $message, 2);
                if (count($parts) === 2) {
                    $outside_raw = trim($parts[0]);
                    $message_raw = trim($parts[1]);
                }
            }

            if (!$is_voice_marker && !empty($words) && $message_raw !== '') {
                $message_raw = sbChat_badwords_filter($words, $message_raw, $replace);
            }

            if ($outside_raw !== '') {
                $bubbles[] = array(
                    'class' => $message_class,
                    'kind'  => 'card',
                    'unix'  => $unix,
                );
            }

            if (trim((string) $message_raw) !== '') {
                $bubbles[] = array(
                    'class' => $message_class,
                    'kind'  => $is_voice_marker ? 'voice' : 'text',
                    'unix'  => $unix,
                );
            }

            $attachment_ids = array_values(array_filter(array_map('absint', explode(',', (string) $row->attachment_ids))));
            if (empty($attachment_ids)) {
                continue;
            }

            $image_count = 0;
            $doc_count = 0;
            foreach ($attachment_ids as $attachment_id) {
                $mime_type = (string) get_post_mime_type($attachment_id);
                $mime_root = explode('/', $mime_type)[0];
                if ($mime_root === 'image') {
                    $image_count++;
                } elseif ($mime_root === 'application' || $mime_root === 'text') {
                    $doc_count++;
                }
            }

            if ($image_count > 0) {
                $rendered_image_bubbles = ($image_count <= 4) ? $image_count : 1;
                for ($i = 0; $i < $rendered_image_bubbles; $i++) {
                    $bubbles[] = array(
                        'class' => $message_class,
                        'kind'  => 'media',
                        'unix'  => $unix,
                    );
                }
            }

            if ($doc_count > 0) {
                for ($i = 0; $i < $doc_count; $i++) {
                    $bubbles[] = array(
                        'class' => $message_class,
                        'kind'  => 'file',
                        'unix'  => $unix,
                    );
                }
            }
        }

        return $bubbles;
    }
}

if (!function_exists('bornado_sb_chat_format_bubble_time_label')) {
    /**
     * Build a compact fallback time label for initial/server-rendered bubbles.
     *
     * @param int $unix Unix timestamp.
     * @return string
     */
    function bornado_sb_chat_format_bubble_time_label($unix)
    {
        $unix = (int) $unix;
        if ($unix <= 0) {
            return '';
        }

        return wp_date(get_option('time_format'), $unix, wp_timezone());
    }
}

if (!function_exists('bornado_sb_chat_decorate_bubbles_html')) {
    /**
     * Inject timestamp nodes directly into bubble HTML.
     *
     * @param string $html       Chat bubbles HTML.
     * @param array  $bubble_meta Sequential bubble meta built from DB rows.
     * @return string
     */
    function bornado_sb_chat_decorate_bubbles_html($html, $bubble_meta)
    {
        $html = is_string($html) ? trim($html) : '';
        if ($html === '' || empty($bubble_meta) || !class_exists('DOMDocument')) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped_html = '<ul id="bornado-sb-chat-bubbles-root">' . $html . '</ul>';
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $html;
        }

        $xpath = new DOMXPath($dom);
        $bubble_nodes = $xpath->query('//ul[@id="bornado-sb-chat-bubbles-root"]/li[contains(concat(" ", normalize-space(@class), " "), " message-bubble ")]');
        if (!$bubble_nodes || $bubble_nodes->length === 0) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $html;
        }

        $limit = min($bubble_nodes->length, count($bubble_meta));
        for ($i = 0; $i < $limit; $i++) {
            $meta = isset($bubble_meta[$i]) && is_array($bubble_meta[$i]) ? $bubble_meta[$i] : array();
            $unix = isset($meta['unix']) ? (int) $meta['unix'] : 0;
            if ($unix <= 0) {
                continue;
            }

            $bubble = $bubble_nodes->item($i);
            if (!$bubble instanceof DOMElement) {
                continue;
            }

            $target = null;
            $text_p = $xpath->query('./div[contains(concat(" ", normalize-space(@class), " "), " message-text ")]/p[1]', $bubble);
            if ($text_p && $text_p->length > 0) {
                $target = $text_p->item(0);
            } else {
                $fallback_queries = array(
                    './div[contains(concat(" ", normalize-space(@class), " "), " message-file-main ")][1]',
                    './a[contains(concat(" ", normalize-space(@class), " "), " message-post-card ")][1]',
                    './div[contains(concat(" ", normalize-space(@class), " "), " message-post-card ")][1]',
                    './div[contains(concat(" ", normalize-space(@class), " "), " message-media ")][1]',
                    './p[1]',
                    './div[contains(concat(" ", normalize-space(@class), " "), " message-text ")][1]',
                );

                foreach ($fallback_queries as $query) {
                    $nodes = $xpath->query($query, $bubble);
                    if ($nodes && $nodes->length > 0) {
                        $target = $nodes->item(0);
                        break;
                    }
                }
            }

            if (!$target instanceof DOMElement) {
                continue;
            }

            $time = $dom->createElement('span', esc_html(bornado_sb_chat_format_bubble_time_label($unix)));
            $class_name = 'bornado-bubble-time';
            if (strpos(' ' . $target->getAttribute('class') . ' ', ' message-media ') !== false) {
                $class_name .= ' bornado-bubble-time--overlay';
            }
            $time->setAttribute('class', $class_name);
            $time->setAttribute('data-unix', (string) $unix);
            $time->setAttribute('aria-hidden', 'true');
            $target->appendChild($time);
        }

        $root = $xpath->query('//ul[@id="bornado-sb-chat-bubbles-root"]')->item(0);
        $output = '';
        if ($root instanceof DOMElement) {
            foreach ($root->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $output !== '' ? $output : $html;
    }
}

if (!function_exists('bornado_ajax_sb_chat_bubble_meta')) {
    /**
     * AJAX: return one timestamp per rendered bubble for the current conversation.
     */
    function bornado_ajax_sb_chat_bubble_meta()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Authentication required.', 'adforest')), 401);
        }

        check_ajax_referer('bornado_sb_chat_bubble_meta', 'nonce');

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $user_id = get_current_user_id();

        if ($conversation_id <= 0 || $user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid conversation.', 'adforest')), 400);
        }

        if (!function_exists('sbchat_get_conversation_by_id')) {
            wp_send_json_error(array('message' => __('Chat helpers are unavailable.', 'adforest')), 500);
        }

        $conversation = sbchat_get_conversation_by_id($conversation_id);
        if (empty($conversation) || !is_array($conversation)) {
            wp_send_json_error(array('message' => __('Conversation not found.', 'adforest')), 404);
        }

        $user_1 = isset($conversation['user_1']) ? (int) $conversation['user_1'] : 0;
        $user_2 = isset($conversation['user_2']) ? (int) $conversation['user_2'] : 0;
        if ($user_id !== $user_1 && $user_id !== $user_2) {
            wp_send_json_error(array('message' => __('Access denied.', 'adforest')), 403);
        }

        wp_send_json_success(
            array(
                'bubbles' => bornado_sb_chat_build_bubble_meta($user_id, $conversation_id),
            )
        );
    }
}
add_action('wp_ajax_bornado_sb_chat_bubble_meta', 'bornado_ajax_sb_chat_bubble_meta');

if (!function_exists('bornado_buffer_sb_chat_initial_markup')) {
    /**
     * Decorate initially rendered classic dashboard chat bubbles before output.
     */
    function bornado_buffer_sb_chat_initial_markup()
    {
        if (is_admin() || !function_exists('is_page_template') || !is_page_template('page-theme-dashboard.php')) {
            return;
        }

        $page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';
        $conversation_id = isset($_GET['conversation_id']) ? absint(wp_unslash($_GET['conversation_id'])) : 0;
        if ($page_type !== 'msg' || $conversation_id <= 0 || !is_user_logged_in() || !function_exists('sbchat_get_inbox_conversations')) {
            return;
        }

        ob_start('bornado_filter_sb_chat_initial_markup');
    }
}
add_action('template_redirect', 'bornado_buffer_sb_chat_initial_markup', 20);

if (!function_exists('bornado_filter_sb_chat_initial_markup')) {
    /**
     * Replace the initially rendered messages list with a decorated version.
     *
     * @param string $html Full page HTML.
     * @return string
     */
    function bornado_filter_sb_chat_initial_markup($html)
    {
        if (!is_string($html) || $html === '') {
            return $html;
        }

        $conversation_id = isset($_GET['conversation_id']) ? absint(wp_unslash($_GET['conversation_id'])) : 0;
        $user_id = get_current_user_id();
        if ($conversation_id <= 0 || $user_id <= 0 || !function_exists('sbchat_get_inbox_conversations')) {
            return $html;
        }

        $messages_html = sbchat_get_inbox_conversations($user_id, $conversation_id);
        $messages_html = bornado_sb_chat_decorate_bubbles_html(
            $messages_html,
            bornado_sb_chat_build_bubble_meta($user_id, $conversation_id)
        );

        return preg_replace(
            '/(<ul class="messages-list">)(.*?)(<\/ul>)/s',
            '$1' . $messages_html . '$3',
            $html,
            1
        );
    }
}

if (!function_exists('bornado_override_sb_chat_ajax_actions')) {
    /**
     * Replace chat AJAX handlers with child-theme wrappers that also ship bubble meta.
     */
    function bornado_override_sb_chat_ajax_actions()
    {
        if (!class_exists('Sb_Chat_Messages') || !function_exists('sbchat_get_inbox_conversations')) {
            return;
        }

        remove_all_actions('wp_ajax_sb_notification_ajax');
        add_action('wp_ajax_sb_notification_ajax', 'bornado_sb_notification_ajax', 1);

        remove_all_actions('wp_ajax_inbox_reload_incoming_messages');
        add_action('wp_ajax_inbox_reload_incoming_messages', 'bornado_sbchat_inbox_reload_incoming_messages', 1);
    }
}
add_action('init', 'bornado_override_sb_chat_ajax_actions', 50);

if (!function_exists('bornado_sb_chat_build_notification_chat_list')) {
    /**
     * Rebuild sidebar conversation list HTML for chat AJAX responses.
     *
     * @param int $user_id Current user ID.
     * @return string
     */
    function bornado_sb_chat_build_notification_chat_list($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !function_exists('sbchat_get_conversations_by_user_id')) {
            return '';
        }

        $chat_list = '';
        $user_conversations = sbchat_get_conversations_by_user_id($user_id);
        foreach ((array) $user_conversations as $user_conversation) {
            $recipient_id = ($user_id == $user_conversation['user_2']) ? absint($user_conversation['user_1']) : absint($user_conversation['user_2']);
            $user_key = ($user_id == $user_conversation['user_1']) ? 'user_1' : 'user_2';
            $chat_delete_key = ($user_key == 'user_1') ? 'deleted_by_user_1' : 'deleted_by_user_2';
            if (isset($user_conversation[$chat_delete_key]) && (int) $user_conversation[$chat_delete_key] === 1) {
                continue;
            }

            $recipient = get_userdata($recipient_id);
            if (!is_wp_error($recipient) && $recipient) {
                $recipient_nicename = esc_html($recipient->display_name);
                $recipient_fullname = esc_html($recipient->first_name) . ' ' . esc_html($recipient->last_name);
                $recipient_output = ($recipient_nicename !== '') ? $recipient_nicename : $recipient_fullname;
            } else {
                $recipient_output = __('User has been removed', 'sb_chat');
            }

            $is_conversation_read = sbchat_get_conversation_status_check($user_conversation, $user_id);
            $last_message_sent_ago = (string) human_time_diff(strtotime($user_conversation['updated']), current_time('timestamp', 1));
            $conversation_id = (int) $user_conversation['id'];
            $unread = $is_conversation_read ? '' : 'unread';

            $chat_list .= '<li class="' . esc_attr($unread) . '" data-id="' . esc_attr($conversation_id) . '"><a target="_self" data-recipient_id="' . esc_attr($recipient_id) . '" data-conv="' . esc_attr($conversation_id) . '" href="" class="d-flex align-items-center con-chat-list"><div class="flex-shrink-0 sb-avatar">' . get_avatar($recipient_id, 45) . '</div><div class="flex-grow-1 ms-1"><h3 class="sender-details">' . esc_html($recipient_output) . '</h3><p>' . esc_html($last_message_sent_ago . ' ago') . '</p></div></a></li>';
        }

        return $chat_list;
    }
}

if (!function_exists('bornado_sb_chat_build_notification_head_footer')) {
    /**
     * Rebuild current conversation head/footer payload for chat AJAX responses.
     *
     * @param Sb_Chat_Messages $messages_obj Chat utility instance.
     * @param int              $conv_id      Conversation ID.
     * @param int              $current_user_id Current user ID.
     * @return array<string,string|int>
     */
    function bornado_sb_chat_build_notification_head_footer($messages_obj, $conv_id, $current_user_id)
    {
        $payload = array(
            'head'          => '',
            'footer'        => '',
            'recipient_id'  => 0,
            'conversation_id' => (int) $conv_id,
        );

        $this_conv = $messages_obj->sb_get_conversation($conv_id);
        $user_1 = isset($this_conv[0]->user_1) ? (int) $this_conv[0]->user_1 : 0;
        $user_2 = isset($this_conv[0]->user_2) ? (int) $this_conv[0]->user_2 : 0;
        if ($current_user_id !== $user_1 && $current_user_id !== $user_2) {
            return $payload;
        }

        $opponent = ($user_1 === $current_user_id) ? $user_2 : $user_1;
        $payload['recipient_id'] = $opponent;

        $recipient = get_userdata($opponent);
        if (!$recipient) {
            $name = esc_html__('User has been removed', 'sb_chat');
        } else {
            $name = $recipient->display_name;
            if ($name === '') {
                $name = trim($recipient->first_name . ' ' . $recipient->last_name);
            }
            if ($name === '') {
                $name = esc_html__('User has been removed', 'sb_chat');
            }
        }

        $plugin_options = get_option('sb_plugin_options');
        $allowed_mime_types = '';
        $max_file_size = 1;
        $max_files_upload = 7;
        if (is_array($plugin_options) && !empty($plugin_options)) {
            $allowed_raw = isset($plugin_options['sbchat_allowed_mime_types']) ? $plugin_options['sbchat_allowed_mime_types'] : array();
            $allowed_mime_types = (is_array($allowed_raw) && count($allowed_raw) > 0) ? implode(',', $allowed_raw) : '';
            $max_file_size = (!empty($plugin_options['sb_max_file_size']) && $plugin_options['sb_max_file_size'] > 0) ? absint($plugin_options['sb_max_file_size'] / 1024) : 1;
            $max_files_upload = (!empty($plugin_options['sbchat_max_files_upload']) && $plugin_options['sbchat_max_files_upload'] > 0) ? absint($plugin_options['sbchat_max_files_upload']) : 7;
        }

        $recipient_avatar = function_exists('sbchat_get_user_avatar') ? sbchat_get_user_avatar($opponent, 45) : get_avatar($opponent, 45);

        $payload['head'] = '<div class="row"><div class="col-7"><div class="d-flex align-items-center"><div class="flex-shrink-0 sb-avatar head">' . $recipient_avatar . '</div><div class="flex-grow-1 ms-1"><h3>' . esc_html($name) . '</h3></div></div></div><div class="col-5"><nav class="sb-menu menu-caret submenu-top-border submenu-scale"><ul class="moreoption"><li class="navbar nav-item dropdown dropstart"><div class="button-container"><button class="delete-single-chat main-btn primary-btn square-btn btn-hover" data-delete="' . esc_attr__('Are you sure you want to remove this?', 'sb_chat') . '" href="#">' . esc_html__('Delete', 'sb_chat') . '</button></div><div class="sb-notification success"><p>' . esc_html__('Conversation was removed', 'sb_chat') . '</p></div></li></ul></nav></div></div>';

        $payload['footer'] = '<form action="" class="send-message"><div class="d-flex"><input type="text" id="message_box" class="form-control message-details" aria-label="message…" placeholder="' . esc_attr__('Write message…', 'sb_chat') . '"><button class="btn btn-theme btn-icon send-btn text-light mb-1" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i>' . esc_html__('Send', 'sb_chat') . '</button><input type="hidden" id="conversation_id" name="conversation_id" value="' . esc_attr($conv_id) . '"><input type="hidden" id="recipient_id" name="recipient_id" value="' . esc_attr($opponent) . '"></div><div id="sbchat-mu" class="sbchat_upload_items">' . esc_html__('Add Attachments', 'sb_chat') . '</div><div class="dropzone-settings" style="display: none;"><input type="hidden" id="dz_max_file_size" value="' . esc_attr($max_file_size) . '" /><input type="hidden" id="dz_max_files_upload" value="' . esc_attr($max_files_upload) . '" /><input type="hidden" id="dz_allowed_mime_types" value="' . esc_attr($allowed_mime_types) . '" /></div></form>';

        return $payload;
    }
}

if (!function_exists('bornado_sb_notification_ajax')) {
    /**
     * Child-theme wrapper for the plugin's chat refresh action.
     */
    function bornado_sb_notification_ajax()
    {
        global $wpdb;

        $current_user_id = $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'sb_chat_messages';
        $conv_id = isset($_POST['conv_id']) ? absint($_POST['conv_id']) : 0;
        $messages_obj = new Sb_Chat_Messages();

        if ($conv_id <= 0 || $current_user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid conversation.', 'adforest')));
        }

        $messages_obj->sb_mark_as_read($conv_id);

        $query = $wpdb->prepare(
            "SELECT message, attachment_ids, sender_id FROM $table WHERE conversation_id = %d ORDER BY ID DESC LIMIT 10",
            $conv_id
        );
        $results = $wpdb->get_results($query);

        if (!$results) {
            $no_message = '<h2 class="no-message">' . esc_html__('No Messages Found', 'sb_chat') . '</h2>';
            wp_send_json_error(array('message' => $no_message));
        }

        $bubble_meta = bornado_sb_chat_build_bubble_meta($current_user_id, $conv_id);
        $html = sbchat_get_inbox_conversations($current_user_id, $conv_id);
        $html = bornado_sb_chat_decorate_bubbles_html($html, $bubble_meta);
        $chat_list = bornado_sb_chat_build_notification_chat_list($user_id);
        $head_footer = bornado_sb_chat_build_notification_head_footer($messages_obj, $conv_id, $current_user_id);

        $dashboard_page = get_option('sb_plugin_options');
        $dashboard_page = isset($dashboard_page['sb-dashboard-page']) ? get_the_permalink($dashboard_page['sb-dashboard-page']) : home_url();
        $newurl = add_query_arg(array('action' => 'view', 'conv_id' => $conv_id), esc_url($dashboard_page));

        wp_send_json_success(
            array(
                'result'          => $html,
                'chat'            => $chat_list,
                'head'            => $head_footer['head'],
                'footer'          => $head_footer['footer'],
                'url'             => $newurl,
                'conversation_id' => $head_footer['conversation_id'],
                'recipient_id'    => $head_footer['recipient_id'],
                'bubble_meta'     => $bubble_meta,
            )
        );
    }
}

if (!function_exists('bornado_sbchat_inbox_reload_incoming_messages')) {
    /**
     * Child-theme wrapper for periodic inbox refresh so timestamps arrive with HTML.
     */
    function bornado_sbchat_inbox_reload_incoming_messages()
    {
        $nonce = isset($_POST['security']) ? sanitize_text_field($_POST['security']) : '';
        if (!wp_verify_nonce($nonce, 'sbchat_reload_incoming_messages_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'sb_chat')), 403);
        }

        $conversations_limit = (isset($_POST['conversations_offset']) && !empty($_POST['conversations_offset']) && $_POST['conversations_offset'] > 0) ? absint($_POST['conversations_offset']) : 7;
        $context = (isset($_POST['context']) && !empty($_POST['context'])) ? esc_html($_POST['context']) : false;

        $user_id = get_current_user_id();
        if (!is_user_logged_in() || $user_id <= 0) {
            wp_send_json_error(array('message' => __('User authentication failed!', 'sb_chat')));
        }

        $conversations = sbchat_get_conversations_by_user_id($user_id, $conversations_limit);
        if ($conversations === false) {
            wp_send_json_error(array('message' => __('This user has no conversations.', 'sb_chat')));
        }

        $conversation_list_html = '';
        $page_context = '';
        $current_conversation = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;

        foreach ((array) $conversations as $conversation) {
            $unread_class = '';
            $active_class = '';

            $sender_id = ($user_id == $conversation['user_2']) ? absint($conversation['user_1']) : absint($conversation['user_2']);
            if (!isset($sender_id) || empty($sender_id) || $sender_id === 0 || !is_numeric($sender_id)) {
                continue;
            }

            $user_key = ($user_id == $conversation['user_1']) ? 'user_1' : 'user_2';
            $chat_delete_key = ($user_key == 'user_1') ? 'deleted_by_user_1' : 'deleted_by_user_2';
            if (isset($conversation[$chat_delete_key]) && $conversation[$chat_delete_key] == 1) {
                continue;
            }

            $sender = get_userdata($sender_id);
            if (is_wp_error($sender)) {
                continue;
            }

            $sender_fullname = $sender->display_name;
            if ($sender_fullname === '') {
                $sender_fullname = esc_html($sender->first_name) . ' ' . esc_html($sender->last_name);
            }

            if ($context == 'user-dashboard') {
                $page_context = '/dashboard/?action=view&ext=inbox';
            }
            if ($context == 'inbox' || $context == 'sbchat') {
                $page_context = '/inbox/?action=view';
            }

            $dashboard_page = get_option('sb_plugin_options');
            $dashboard_page = isset($dashboard_page['sb-dashboard-page']) ? get_the_permalink($dashboard_page['sb-dashboard-page']) : home_url();
            $inbox_url = $dashboard_page . '?action=view&conversation_id=' . $conversation['id'];

            $sender_avatar = function_exists('sbchat_get_user_avatar') ? sbchat_get_user_avatar($sender_id, 45) : get_avatar($sender_id, 45);
            $timestamp = human_time_diff(strtotime($conversation['updated']), current_time('timestamp', 1));
            $is_conversation_read = sbchat_get_conversation_status_check($conversation, $user_id);
            if (!$is_conversation_read) {
                $unread_class = 'unread';
            }
            if ($current_conversation == $conversation['id']) {
                $active_class = ' active';
            }
            if (trim($sender_fullname) === '') {
                $sender_fullname = __('User has been removed', 'sb_chat');
            }

            $conversation_list_html .= '<li class="' . esc_attr($unread_class . $active_class) . '" data-id="' . esc_attr($conversation['id']) . '"><a target="_self" href="' . esc_url($inbox_url) . '" class="d-flex align-items-center con-chat-list" data-recipient_id="' . esc_attr($sender_id) . '" data-conv="' . esc_attr($conversation['id']) . '"><div class="flex-shrink-0 sb-avatar">' . $sender_avatar . '</div><div class="flex-grow-1 ms-3"><h3 class="sender-details">' . esc_html($sender_fullname) . '</h3><p>' . esc_html($timestamp . ' ago') . '</p></div></a></li>';
        }

        $conversation_messages_html = '';
        $bubble_meta = array();
        if ($current_conversation > 0) {
            $bubble_meta = bornado_sb_chat_build_bubble_meta($user_id, $current_conversation);
            $conversation_messages_html = trim(sbchat_get_inbox_conversations($user_id, $current_conversation));
            $conversation_messages_html = bornado_sb_chat_decorate_bubbles_html($conversation_messages_html, $bubble_meta);
        }

        wp_send_json_success(
            array(
                'message'                 => 'Incoming messages retreived successfully!',
                'conversation_list_items' => trim($conversation_list_html),
                'conversation_messages'   => $conversation_messages_html,
                'bubble_meta'             => $bubble_meta,
            )
        );
    }
}
