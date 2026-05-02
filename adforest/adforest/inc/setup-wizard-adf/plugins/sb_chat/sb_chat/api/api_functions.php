<?php
function sbchat_get_inbox_conversations_structured($current_user_id, $conversation_id) {
    $messages_data = [];
    $sbchat_messages = new Sb_Chat_Messages();
    $inbox_conversations = $sbchat_messages->get_single_conversation($current_user_id, $conversation_id);

    foreach ($inbox_conversations as $msg) {
        $attachments = [];

        if (!empty($msg->attachment_ids)) {
            $attachment_ids = explode(',', $msg->attachment_ids);
            foreach ($attachment_ids as $id) {
                $type = explode('/', get_post_mime_type($id))[0];
                $attachment = [
                    'name' => get_the_title($id),
                    'url' => get_the_guid($id),
                    'type' => $type
                ];
                $attachments[] = $attachment;
            }
        }

        $filtered_msg = sbChat_badwords_filter(
            explode(',', get_option('sb_plugin_options')['sb_chat_bad_words_filter']),
            $msg->message,
            get_option('sb_plugin_options')['sb_chat_bad_words_replace']
        );

        $messages_data[] = [
            'id'         => (int)$msg->id,
            'sender_id'  => (int)$msg->sender_id,
            'message'    => $filtered_msg,
            'timestamp'  => $msg->created,
            'attachments'=> $attachments,
            'type'       => filter_var($filtered_msg, FILTER_VALIDATE_URL) ? 'link' : 'text'
        ];
    }

    return $messages_data;
}