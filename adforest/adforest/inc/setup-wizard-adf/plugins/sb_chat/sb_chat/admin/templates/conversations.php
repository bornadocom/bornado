<?php
if (isset($_GET['debug'])) {
echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
echo '<h4>Debug Information:</h4>';
    $page_id = (isset($_GET['page']) && ! empty($_GET['page'])) ? esc_html($_GET['page']) : '';
    $user_id = (isset($_GET['user_id']) && ! empty($_GET['user_id']) && $_GET['user_id'] > 0) ? $_GET['user_id'] : 0;

echo '<p><strong>Page ID:</strong> ' . $page_id . '</p>';
echo '<p><strong>User ID from GET:</strong> ' . $user_id . '</p>';
echo '<p><strong>Current User ID:</strong> ' . get_current_user_id() . '</p>';

if (function_exists('sbchat_get_conversations_by_user_id')) {
    echo '<p><strong>Function sbchat_get_conversations_by_user_id:</strong> EXISTS</p>';
} else {
    echo '<p style="color: red;"><strong>Function sbchat_get_conversations_by_user_id:</strong> DOES NOT EXIST</p>';
}

if ($user_id !== 0) {
        if (function_exists('sbchat_get_conversations_by_user_id')) {
    $test_conversations = sbchat_get_conversations_by_user_id($user_id, 5);
    echo '<p><strong>Conversations found:</strong> ' . (is_array($test_conversations) ? count($test_conversations) : 'NONE/ERROR') . '</p>';
    if (is_array($test_conversations) && !empty($test_conversations)) {
        echo '<p><strong>Sample conversation:</strong> ' . print_r($test_conversations[0], true) . '</p>';
            }
    }
} else {
    echo '<p style="color: orange;"><strong>No user ID provided - checking all conversations</strong></p>';
    $current_user_id = get_current_user_id();
        if ($current_user_id > 0 && function_exists('sbchat_get_conversations_by_user_id')) {
        $admin_conversations = sbchat_get_conversations_by_user_id($current_user_id, 5);
        echo '<p><strong>Admin user conversations:</strong> ' . (is_array($admin_conversations) ? count($admin_conversations) : 'NONE/ERROR') . '</p>';
    }
}

echo '</div>';
}

$page_id = (isset($_GET['page']) && ! empty($_GET['page'])) ? esc_html($_GET['page']) : '';
$user_id = (isset($_GET['user_id']) && ! empty($_GET['user_id']) && $_GET['user_id'] > 0) ? $_GET['user_id'] : 0;

$sender_info = null;
$sender_fullname = '';
if ($user_id != 0) {
    $sender_info = get_userdata($user_id);
    if ($sender_info) {
        $sender_fullname = esc_html($sender_info->first_name) . ' ' . esc_html($sender_info->last_name);
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('SbChat Conversations', 'sb_chat'); ?></h1>

<section class="message-area">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="chat-area">
                    <div class="chatlist">
                        <div class="modal-dialog-scrollable">
                                <div class="modal-content" style="width: 100%; !important;">
                                <div class="chat-header">
                                    <?php
                                    $all_users = get_users(array(
                                        'meta_query' => array(
                                            array(
                                                'key' => 'sbchat_has_conversations',
                                                'compare' => 'EXISTS'
                                            )
                                        )
                                    ));
                                    
                                    if (empty($all_users)) {
                                        $all_users = get_users(array('number' => 50));
                                    }
                                    ?>
                                    
                                    <form method="GET" style="margin-bottom: 20px;">
                                        <input type="hidden" name="page" value="sbchat_conversations" />
                                        <select name="user_id" class="postform sbchat-users" onchange="this.form.submit()">
                                            <option value=""><?php echo esc_html__('Select a user...', 'sb_chat'); ?></option>
                                            <?php foreach ($all_users as $user): ?>
                                                <option value="<?php echo esc_attr($user->ID); ?>" 
                                                    <?php selected($user_id, $user->ID); ?>>
                                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>

                                    <div class="msg-search">
                                        <input type="text" class="form-control" id="inlineFormInputGroup" 
                                                placeholder="<?php echo esc_attr__('Search', 'sb_chat') ?>" aria-label="search">
                                    </div>
                                    
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                                        <li class="nav-item user_nav" role="presentation">
                                                <?php if ($user_id > 0):
                                                $user_name = get_userdata($user_id);
                                                $author_name = $user_name ? $user_name->display_name : 'Unknown User';
                                            ?>
                                            <div class="main_user_name">
                                                <p><span>User Name:</span> <?php echo esc_html(ucfirst($author_name)); ?></p>
                                            </div>    
                                            <?php endif; ?> 
                                            
                                            <button class="nav-link active" id="Open-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#Open" type="button" role="tab" 
                                                    aria-controls="Open" aria-selected="true">
                                                    <?php echo esc_html__('All Conversations', 'sb_chat') ?>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="modal-body">
                                    <div class="messages-inbox chat-list" data-context="sbchat">
                                        <?php
                                        $user_conversations = array();
                                        $display_limit = 7;

                                        if ($user_id !== 0) {
                                            if (function_exists('sbchat_get_conversations_by_user_id')) {
                                                $user_conversations = sbchat_get_conversations_by_user_id($user_id, $display_limit);
                                            } else {
                                                echo '<p style="color: red;">Error: sbchat_get_conversations_by_user_id function not found!</p>';
                                            }
                                        }
                                        
                                        if (!empty($user_conversations) && is_array($user_conversations)): ?>
                                            <ul class="chat-list-detail">
                                                    <?php foreach ($user_conversations as $user_conversation):
                                                    $recipient_id = ($user_id == $user_conversation['user_2']) ? 
                                                                   absint($user_conversation['user_1']) : 
                                                                   absint($user_conversation['user_2']);
                                                    $recipient = get_userdata($recipient_id);
                                                    $recipient_output = '';
                                                    
                                                    if (!is_wp_error($recipient) && !empty($recipient)) {
                                                        $recipient_nicename = esc_html($recipient->display_name);
                                                        $recipient_fullname = esc_html($recipient->first_name) . ' ' . esc_html($recipient->last_name);
                                                        $recipient_output = $recipient_nicename ?: $recipient_fullname;
                                                    }
                                                    
                                                    if (empty($recipient_output)) {
                                                        $recipient_output = __('User has been removed', 'sb_chat');
                                                    }
                                                    
                                                    $is_conversation_read = function_exists('sbchat_get_conversation_status_check') ? 
                                                                          sbchat_get_conversation_status_check($user_conversation, $user_id) : true;
                                                    $last_message_sent_ago = human_time_diff(strtotime($user_conversation['updated']));
                                                    
                                                    $conversation_url = admin_url('admin.php?page=sbchat_conversations&action=view&user_id=' . $user_id . '&conversation_id=' . $user_conversation['id']);
                                                ?>
                                                <li <?php if ($is_conversation_read === false) echo 'class="unread"' ?> 
                                                    data-id="<?php echo esc_attr($user_conversation['id']) ?>">
                                                    <a target="_self" 
                                                       data-conv="<?php echo esc_attr($user_conversation['id']) ?>" 
                                                       href="<?php echo esc_url($conversation_url) ?>" 
                                                       class="d-flex align-items-center">
                                                        <div class="flex-shrink-0 sb-avatar">
                                                            <?php
                                                            if(function_exists('sbchat_get_user_avatar')) {
                                                                echo sbchat_get_user_avatar($recipient_id, 45); 
                                                            } else {
                                                                echo get_avatar($recipient_id, 45);
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h3 class="sender-details">
                                                                <?php echo esc_html($recipient_output) ?>
                                                            </h3>
                                                            <p><?php echo esc_html($last_message_sent_ago) . ' ago'; ?></p>
                                                        </div>
                                                    </a>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            
                                            <?php if (count($user_conversations) > $display_limit): ?>
                                                <button type='button' class='btn btn-primary load-conversations' 
                                                        data-context="sbchat" 
                                                        data-limit="<?php echo esc_attr($display_limit) ?>" 
                                                        data-offset="<?php echo esc_attr($display_limit) ?>">
                                                    Load more conversations
                                                </button>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <div class="no-conversations">
                                                <?php if ($user_id === 0): ?>
                                                    <p><?php echo esc_html__('Please select a user to view their conversations.', 'sb_chat'); ?></p>
                                                <?php else: ?>
                                                    <p><?php echo esc_html__('No conversations found for this user.', 'sb_chat'); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chatbox">
                        <div class="modal-dialog-scrollable">
                                <div class="modal-content" style="width: 100%; !important;">
                                <?php
                                $conversation_id = (isset($_GET['conversation_id']) && !empty($_GET['conversation_id'])) ? 
                                                 esc_html($_GET['conversation_id']) : 0;

                                $current_conversation = "";
                                if ($conversation_id !== 0 && function_exists('sbchat_get_conversation_by_id')) {
                                    $current_conversation = sbchat_get_conversation_by_id($conversation_id);
                                }

                                if (!$current_conversation): ?>
                                    <div class="msg-head"></div>
                                    <div class="modal-body" id="sbModalBody">
                                        <div class="msg-body">
                                            <ul class="messages-list"></ul>
                                        </div>
                                    </div>
                                    <div class="send-box chat-footer">
                                        <h4 class="not-found">
                                            <?php 
                                            if ($conversation_id === 0) {
                                                    esc_html_e('Select a conversation to view messages.', 'sb_chat');
                                            } else {
                                                    esc_html_e('No conversation found.', 'sb_chat');
                                            }
                                            ?>
                                        </h4>
                                    </div>
                                <?php else:
                                    $user_1 = isset($current_conversation['user_1']) ? $current_conversation['user_1'] : 0;
                                    $user_2 = isset($current_conversation['user_2']) ? $current_conversation['user_2'] : 0;

                                    if ($user_id == $user_1 || $user_id == $user_2):
                                        $recipient_id = ($user_1 == $user_id) ? $user_2 : $user_1;
                                        $recipient = get_userdata($recipient_id);
                                        
                                        $recipient_output = '';
                                        if (!is_wp_error($recipient) && !empty($recipient)) {
                                            $recipient_nicename = esc_html($recipient->display_name);
                                            $recipient_fullname = esc_html($recipient->first_name) . ' ' . esc_html($recipient->last_name);
                                            $recipient_output = $recipient_nicename ?: $recipient_fullname;
                                        }
                                        
                                        if (empty($recipient_output)) {
                                            $recipient_output = __('User has been deleted', 'sb_chat');
                                        }
                                ?>
                                    <div class="msg-head">
                                        <div class="row">
                                            <div class="col-8">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 sb-avatar">
                                                        <?php
                                                        if(function_exists('sbchat_get_user_avatar')) {
                                                            echo sbchat_get_user_avatar($recipient_id, 45); 
                                                        } else {
                                                            echo get_avatar($recipient_id, 45);
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h3><?php echo esc_html($recipient_output); ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <ul class="moreoption">
                                                    <li class="navbar nav-item dropdown dropstart">
                                                        <?php
                                                        ?>
                                                                <!-- <a class="dropdown-toggle delete-single-chat e-button"
                                                           data-delete="<?php echo esc_attr__('Are You Sure?', 'sb_chat'); ?>" 
                                                           href="javascript:void(0)" 
                                                           data-conversation="<?php echo esc_attr($conversation_id); ?>">
                                                                    <span><?php echo __('Delete', 'sb_chat'); ?></span>
                                                                </a> -->
                                                        <div class="sb-notification success">
                                                            <p><?php esc_html_e('Conversation is removed', 'sb_chat'); ?></p>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-body" id="sbModalBody">
                                        <div class="msg-body">
                                                    <ul class="messages-list test">
                                                <?php
                                                if (function_exists('sbchat_get_inbox_conversations')) {
                                                    $inbox_conversations = sbchat_get_inbox_conversations($user_id, $conversation_id); 
                                                    if (!empty($inbox_conversations)) {
                                                                $decoded_conversations = $inbox_conversations;
                                                                $decoded_conversations = html_entity_decode($decoded_conversations, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                                                $decoded_conversations = html_entity_decode($decoded_conversations, ENT_QUOTES | ENT_HTML5, 'UTF-8');                                                                
                                                                $decoded_conversations = preg_replace('/<p>\s*<div/', '<div', $decoded_conversations);
                                                                $decoded_conversations = preg_replace('/<\/div>\s*<\/p>/', '</div>', $decoded_conversations);
                                                                $decoded_conversations = preg_replace('/<p>\s*<\/p>/', '', $decoded_conversations);                                                                
                                                                $decoded_conversations = preg_replace('/<span><\/span>/', '', $decoded_conversations);
                                                                
                                                                if (function_exists('sbchat_inbox_conversations_allowed_html')) {
                                                                    echo wp_kses($decoded_conversations, sbchat_inbox_conversations_allowed_html());
                                                                } else {
                                                                    echo wp_kses_post($decoded_conversations);
                                                                }
                                                    } else {
                                                        echo '<li><p>No messages found in this conversation.</p></li>';
                                                    }
                                                } else {
                                                    echo '<li><p style="color: red;">Error: sbchat_get_inbox_conversations function not found!</p></li>';
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>

<style>
    .message-area {
        margin-top: 20px;
    }

    .chat-area {
        display: flex;
        gap: 20px;
    }

    .chatlist {
        flex: 1;
        max-width: 400px;
    }

    .chatbox {
        flex: 2;
    }

    /* Ensure admin chat view scrolls properly and doesn't cut off */
    .modal-dialog-scrollable {
        height: auto;
        max-height: none;
    }
    .modal-dialog-scrollable .modal-body {
        height: auto;
        max-height: calc(100vh - 240px);
        overflow-y: auto;
    }
    /* Frontend CSS hides overflow; override it in admin so long threads are visible */
    .wrap .msg-body ul {
        overflow: visible;
    }

    .modal-content {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
    }

    .chat-header {
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 15px;
    }

    .chat-list-detail {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .chat-list-detail li {
        border-bottom: 1px solid #f0f0f0;
        padding: 10px 0;
    }

    .chat-list-detail a {
        text-decoration: none;
        color: inherit;
    }

    .chat-list-detail a:hover {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 8px;
    }

    .sb-avatar img {
        border-radius: 50%;
    }

    .sender-details {
        font-size: 16px;
        margin: 0;
        font-weight: 500;
    }

    .no-conversations {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }

    .debug-info {
        background: #f0f0f0;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 4px;
    }


    .message-post-card {
        position: relative;
        padding: 15px;
        background-color: #ECF3FE;
        border-radius: 10px;
        border-top-left-radius: 0px;
        display: inline-block;
        max-width: 400px;
    }

    .reply .message-post-card {
        background-color: #F7F7F7;
    }

    .message-post-card::before {
        display: block;
        clear: both;
        content: '';
        position: absolute;
        top: -6px;
        left: -7px;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 0 12px 15px 12px;
        border-color: transparent transparent #ECF3FE transparent;
        -webkit-transform: rotate(-37deg);
        -ms-transform: rotate(-37deg);
        transform: rotate(-37deg);
    }

    .message-post-card .post-card-content {
        display: flex;
        gap: 10px;
    }

    .message-post-card .post-card-content .post-card-image {
        width: 220px;
        height: 136px;
    }

    .message-post-card .post-card-content .post-card-image img {
        width: 100%;
        height: 100%;
        border-radius: 4px;
        object-fit: cover;
    }

    .message-post-card .post-card-content .post-card-info {
        width: calc(100% - 210px);
    }

    .message-post-card .post-card-content .post-card-info .post-card-title {
        font-size: 14px;
        line-height: 22px;
    }

    .message-post-card .post-card-content .post-card-info .post-card-title span {
        color: #242424;
    }

    .message-post-card .post-card-content .post-card-info .post-card-link {
        padding: 0;
        background-color: transparent;
        font-size: 12px;
        color: #6d6d6d;
        margin-top: 5px;
    }
</style>