<?php echo adforest_dashboard_breadcrumb(esc_html__("My Messages", "adforest")); ?>

<?php
$user_id = get_current_user_id();

$is_sbchat_active = SB_Chat::get_plugin_options('sbChat-active');

if (class_exists('SB_Chat') && $is_sbchat_active) {
?>
    <div class="card-style mb-30 message-area content-wrapper">
        <div class="chat-area">
            <div class="chatlist">
                <div class="modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="chat-header">
                            <div class="msg-search">
                                <input type="text" class="form-control" id="inlineFormInputGroup"
                                    placeholder="<?php echo esc_attr__('Search', 'adforest') ?>"
                                    aria-label="search">
                            </div>
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="Open-tab" data-bs-toggle="tab"
                                        data-bs-target="#Open" type="button" role="tab"
                                        aria-controls="Open"
                                        aria-selected="true"><?php echo esc_html__('All Conversations', 'adforest') ?></button>
                                </li>
                            </ul>
                        </div>
                        <div class="modal-body">
                            <div class="messages-inbox chat-list" data-context="inbox"><?php
                                                                                        $user_id = get_current_user_id();
                                                                                        $user_conversations = array();
                                                                                        $conversation_id = "";
                                                                                        $first_conversation_id = "";
                                                                                        $display_limit = 7;
                                                                                        if ($user_id !== 0)
                                                                                            $user_conversations = sbchat_get_conversations_by_user_id($user_id, $display_limit);
                                                                                        if ($user_conversations !== false && is_array($user_conversations)) : ?>
                                    <ul class="chat-list-detail"><?php
                                                                                            $first_iteration = true;
                                                                                            foreach ($user_conversations as $user_conversation) :
                                                                                                $recipient_id = ($user_id == $user_conversation['user_2']) ? absint($user_conversation['user_1']) : absint($user_conversation['user_2']);

                                                                                                $user_key = ($user_id == $user_conversation['user_1']) ? 'user_1' : 'user_2';
                                                                                                $chat_delete_key = ($user_key == 'user_1') ? 'deleted_by_user_1' : 'deleted_by_user_2';

                                                                                                if (isset($user_conversation[$chat_delete_key]) && $user_conversation[$chat_delete_key] == 1) {
                                                                                                    continue;
                                                                                                }
                                                                                                $recipient = get_userdata($recipient_id);

                                                                                                $recipient_output = '';

                                                                                                // $last_conversation_message = sbchat_get_last_conversation_message( $user_conversation['id'] );
                                                                                                $is_conversation_read = sbchat_get_conversation_status_check($user_conversation, $user_id);

                                                                                                $last_message_sent_ago = (string)human_time_diff(strtotime($user_conversation['updated']), current_time('timestamp', 1));


                                                                                                $dashboard_page = get_option('sb_plugin_options');

                                                                                                $dashboard_page = isset($dashboard_page['sb-dashboard-page']) ? get_the_permalink($dashboard_page['sb-dashboard-page']) : home_url();
                                                                                                $conversation_url = $dashboard_page . '?action=view&conversation_id=' . $user_conversation['id'];

                                                                                                $conversation_id = isset($user_conversation['id']) ? $user_conversation['id'] : "";

                                                                                                if ($first_conversation_id == "") {

                                                                                                    $first_conversation_id = $conversation_id;
                                                                                                }


                                                                                                $active_class = "";

                                                                                                if ($first_iteration) {
                                                                                                    $active_class = "active ";
                                                                                                }

                                                                                                $unread_class = "";
                                                                                                if (!$is_conversation_read) {

                                                                                                    $unread_class = "unread";
                                                                                                }
                                                                                                $first_iteration = false;

                                                                    ?>
                                            <li class="<?php echo esc_attr($active_class) . $unread_class; ?>"
                                                data-id="<?php echo esc_attr($user_conversation['id']) ?>">
                                                <a target="_self" data-recipient_id="<?php echo esc_attr($recipient_id); ?>"
                                                    data-conv="<?php echo esc_attr($conversation_id) ?>"
                                                    href="javascript:void(0)"
                                                    class="d-flex align-items-center con-chat-list">
                                                    <div class="flex-shrink-0 sb-avatar test1">
                                                        <?php
                                                                                                if (function_exists('sbchat_get_user_avatar')) {
                                                                                                    echo sbchat_get_user_avatar($recipient_id, 45);
                                                                                                } else {
                                                                                                    echo get_avatar($recipient_id, 45);
                                                                                                }
                                                        ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-1"><?php
                                                                                                if (!is_wp_error($recipient) && !empty($recipient)) {

                                                                                                    $recipient_nicename = esc_html($recipient->display_name);
                                                                                                    $recipient_fullname = esc_html($recipient->first_name) . ' ' . esc_html($recipient->last_name);
                                                                                                    $recipient_output = $recipient_fullname;

                                                                                                    if ($recipient_nicename != "") {
                                                                                                        $recipient_output = $recipient_nicename;
                                                                                                    }
                                                                                                } ?>
                                                        <h3 class="sender-details"><?php
                                                                                                if ($recipient_output == "") {
                                                                                                    $recipient_output = esc_html__('User has been removed', 'adforest');
                                                                                                }
                                                                                                echo esc_html($recipient_output) ?>
                                                        </h3>
                                                        <p>
                                                            <?php echo esc_html($last_message_sent_ago) . esc_html__(' ago', 'adforest'); ?>
                                                        </p>
                                                    </div>
                                                </a>
                                            </li><?php
                                                                                            endforeach; ?>
                                    </ul><?php

                                                                                            if (count($user_conversations) > $display_limit) { ?>

                                        <button type='button' class='btn btn-primary load-conversations'
                                            data-limit="<?php echo esc_attr($display_limit) ?>"
                                            data-offset="<?php echo esc_attr($display_limit) ?>">
                                            <?php echo esc_html__("Load more conversations", "adforest"); ?>
                                        </button><?php
                                                                                            }

                                                                                        endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="chatbox">
                <div class="modal-dialog-scrollable">
                    <div class="modal-content"><?php

                                                $recipient_id = '';
                                                $recipient = '';
                                                $recipient_output = '';

                                                $conversation_id = (isset($_GET['conversation_id']) && !empty($_GET['conversation_id'])) ? esc_html($_GET['conversation_id']) : $first_conversation_id;

                                                $current_conversation = false;
                                                if ($conversation_id !== 0) {
                                                    $current_conversation = sbchat_get_conversation_by_id($conversation_id);
                                                }

                                                if (!$current_conversation) { ?>

                            <div class="msg-head"></div>
                            <div class="modal-body" id="sbModalBody">
                                <div class="msg-body">
                                    <ul class="messages-list"></ul>
                                </div>
                            </div>
                            <div class="send-box chat-footer">
                                <h4 class="not-found"><?php esc_html_e('No Message found.', 'adforest'); ?></h4>
                            </div><?php
                                                }

                                                $user_1 = isset($current_conversation['user_1']) ? $current_conversation['user_1'] : 0;
                                                $user_2 = isset($current_conversation['user_2']) ? $current_conversation['user_2'] : 0;

                                                if ($user_id == $user_1 || $user_id == $user_2) {

                                                    $recipient_id = ($user_1 == $user_id) ? $user_2 : $user_1;
                                                    $recipient = get_userdata($recipient_id);


                                                    if (!is_wp_error($recipient) && isset($recipient->ID)) {

                                                        $recipient_nicename = esc_html($recipient->display_name);

                                                        $recipient_fullname = esc_html($recipient->first_name) . ' ' . esc_html($recipient->last_name);

                                                        $recipient_output = $recipient_nicename;
                                                        if ($recipient_nicename == "")
                                                            $recipient_output = $recipient_fullname;
                                                    }

                                                    if ($recipient_output == "") {
                                                        $recipient_output = esc_html__('User has been removed', 'adforest');
                                                    }
                                    ?>
                            <div class="msg-head">
                                <div class="row">
                                    <div class="col-7">
                                        <div class="d-flex align-items-center con-chat-list">
                                            <div class="flex-shrink-0 sb-avatar">
                                                <?php
                                                    if (function_exists('sbchat_get_user_avatar')) {
                                                        echo sbchat_get_user_avatar($recipient_id, 45);
                                                    } else {
                                                        echo get_avatar($recipient_id, 45);
                                                    }
                                                ?>
                                            </div>
                                            <div class="flex-grow-1 ms-1">
                                                <h3><?php echo esc_html($recipient_output); ?></h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-5">


                                        <ul class="moreoption">
                                            <li class="navbar nav-item dropdown dropstart">
                                                <div class="button-container">
                                                    <button class="delete-single-chat main-btn primary-btn square-btn btn-hover"
                                                        data-delete="<?php echo esc_attr__('Are you sure you want to remove this?', 'adforest'); ?>"><?php echo esc_html__('Delete', 'adforest') ?></button>
                                                </div>
                                                <div class="sb-notification success">
                                                    <p><?php esc_html_e('Conversation is removed', 'adforest'); ?></p>
                                                </div>
                                            </li>
                                        </ul>

                                    </div>
                                </div>
                            </div>
                            <div class="modal-body" id="sbModalBody">

                                <div class="message-spin-loader">
                                    <i class="mdi mdi-loading fa-spin booking-preloader"></i>
                                </div>
                                <div class="msg-body">
                                    <ul class="messages-list">
                                        <?php
                                                    $inbox_conversations = sbchat_get_inbox_conversations($user_id, $conversation_id);
                                                    if (!empty($inbox_conversations)) echo wp_kses($inbox_conversations, sbchat_inbox_conversations_allowed_html()); ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="send-box chat-footer">
                                <?php
                                ?>
                                <form action="" class="send-message" enctype="multipart/form-data">
                                    <div class="send-message-box">
                                        <input type="text" id="message_box" class="form-control message-details"
                                            aria-label="message…"
                                            placeholder="<?php echo esc_attr__('Write message…', 'adforest'); ?>">
                                        <button class="main-btn dark-btn square-btn btn-hover"
                                            type="submit"><i class="mdi mdi-send-circle"
                                                aria-hidden="true"></i>
                                        </button>
                                        <div id="sbchat-mu" class="sbchat_upload_items mdi mdi-paperclip">
                                        </div>
                                        <input type="hidden" id="conversation_id" name="conversation_id"
                                            value="<?php echo esc_attr($conversation_id) ?>">
                                        <input type="hidden" id="recipient_id" name="recipient_id"
                                            value="<?php echo esc_attr($recipient_id) ?>">
                                    </div>
                                    <div class="dropzone-settings" style="display: none;"><?php

                                                                                            if (get_option('sb_plugin_options') !== false)
                                                                                                $plugin_options = get_option('sb_plugin_options');

                                                                                            if (is_array($plugin_options) && count($plugin_options) > 0) {

                                                                                                // $allowed_mime_types = $plugin_options['sbchat_allowed_mime_types'];
                                                                                                $allowed_mime_types = isset($plugin_options['sbchat_allowed_mime_types']) ? $plugin_options['sbchat_allowed_mime_types'] : array();
                                                                                                $max_file_size = $plugin_options['sb_max_file_size'];
                                                                                                $max_files_upload = $plugin_options['sbchat_max_files_upload'];

                                                                                                $allowed_mime_types = (is_array($allowed_mime_types) && count($allowed_mime_types) > 0) ? implode(',', $allowed_mime_types) : '';
                                                                                                $max_file_size = (!empty($max_file_size) && $max_file_size > 0) ? absint($max_file_size / 1024) : 1;
                                                                                                $max_files_upload = (!empty($max_files_upload) && $max_files_upload > 0) ? absint($max_files_upload) : 7; ?>

                                            <input type="hidden" id="dz_max_file_size"
                                                value="<?php echo esc_attr($max_file_size) ?>" />
                                            <input type="hidden" id="dz_max_files_upload"
                                                value="<?php echo esc_attr($max_files_upload) ?>" />
                                            <input type="hidden" id="dz_allowed_mime_types"
                                                value="<?php echo esc_attr($allowed_mime_types) ?>" /><?php
                                                                                                    } ?>
                                    </div>
                                    <div id="attachment-wrapper" class="attachment-wrapper_main"></div>
                                </form>
                            </div><?php
                                                } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } else {
?>
    <div class="card-style mb-30 content-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="chat-description">
                        <h3><?php echo esc_html__('Please install Sb chat plugin to enable chat feature', 'adforest'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
