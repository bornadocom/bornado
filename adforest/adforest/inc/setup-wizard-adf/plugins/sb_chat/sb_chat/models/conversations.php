<?php
function sbchat_get_inbox_conversations( $current_user_id, $conversation_id ) {

    global $sb_plugin_options;
    $sbchat_messages = new Sb_Chat_Messages();
    $inbox_conversations = $sbchat_messages->get_single_conversation( $current_user_id, $conversation_id );

    $conversation_html = '';

    foreach ( $inbox_conversations as $inbox_conversation ) {

        $sender_id = $inbox_conversation->sender_id;

        $message_class = sbchat_get_inbox_message_class( $sender_id );
        $message = ( $inbox_conversation->message );
        $message_html = '';

        $words_fillters = $sb_plugin_options['sb_chat_bad_words_filter'];
        // Normalize bad-words list: split, trim, remove empties
        $words = is_array($sb_plugin_options['sb_chat_bad_words_filter'])
            ? array_filter(array_map('trim', $sb_plugin_options['sb_chat_bad_words_filter']))
            : array_filter(array_map('trim', explode(',', (string)$sb_plugin_options['sb_chat_bad_words_filter'])));
        $replace = $sb_plugin_options['sb_chat_bad_words_replace'];

        if ( ! empty( $message ) ) {
            $apply_filter = true;
            if ( is_admin() ) {
                $referer = wp_get_referer();
                if ( $referer && strpos( $referer, admin_url() ) === 0 ) {
                    $apply_filter = false;
                } else {
                    $apply_filter = true;
                }
            }

            if (!empty($message) && is_string($message)) {
                if (filter_var($message, FILTER_VALIDATE_URL)) {
                    $message_data = esc_html__(' Join Zoom Meeting ', 'exertio_theme');
                    $message_html = <<<MESSAGE_HTML
                        <li class="message-bubble {$message_class}">
                            <div class="message-text"><a href="$message" target="_blank"><i class="fas fa-video"></i>{$message_data}</a></div>
                        </li>
            MESSAGE_HTML;
                } else {
                    $sanitized_message = $message;
                    $parts = explode( '|', $message, 2 );
                    if ( count( $parts ) === 2 ) {
                        $outside_raw  = trim( $parts[0] );
                        if ( $apply_filter ) {
                            $message_raw  = sbChat_badwords_filter( $words, trim( $parts[1] ), $replace );
                        } else {
                            $message_raw = trim( $parts[1] );
                        }
                    } else {
                        $outside_raw  = '';
                        if ( $apply_filter ) {
                            $message_raw  = sbChat_badwords_filter( $words, $message, $replace );
                        } else {
                            $message_raw = $message;
                        }
                    }

                    $outside_esc  = $outside_raw;
                    if ( is_string( $outside_esc ) ) {
                        $outside_trim = trim( $outside_esc );
                        if ( stripos( $outside_trim, '<p>' ) === 0 && strripos( $outside_trim, '</p>' ) === ( strlen( $outside_trim ) - 4 ) ) {
                            $outside_esc = substr( $outside_trim, 3, -4 );
                        }
                    }

                    $message_esc  = esc_html( $message_raw );

                    $message_class_safe = sanitize_html_class( $message_class );

                    $message_html = '';

                    if ( $outside_esc !== '' ) {
                        $message_html .= <<<HTML
                        <li class="message-bubble {$message_class_safe}">
                            {$outside_esc}
                        </li>
                    HTML;
                    }

                    // Only render text bubble if there is actual message content
                    if ( trim( $message_esc ) !== '' ) {
                        $related_class = ($outside_esc !== '') ? ' related-to-card' : '';
                        $message_html .= <<<HTML
                        <li class="message-bubble {$message_class_safe}{$related_class}">
                        <div class="message-text">
                            <p>{$message_esc}</p>
                        </div>
                        </li>
                        HTML;
                    }
                }
            } else {
                // Handle case where $message is empty or not a string (optional)
                $message_html = '';
            }


            $conversation_html .= trim( $message_html );
        }

        $message_attachments = $inbox_conversation->attachment_ids;
        if ( empty( $message_attachments ) )
            continue;

        $message_attachments = explode( ',', $message_attachments );
        if ( is_array( $message_attachments ) && count( $message_attachments ) === 0 )
            continue;

        $img_attachments = array();
        $doc_attachments = array();

        foreach ( $message_attachments as $message_attachment_id ) {
            $attachment_name = esc_html( get_the_title( $message_attachment_id ) );
            $attachment_url = get_the_guid( $message_attachment_id );
            $attachment_path = SBCHAT_UPLOAD_DIR_PATH . '/' . $attachment_name;
            $attachment_upload_date = wp_date( 'Y-m-d H:i:s', strtotime( $inbox_conversation->created ) );
            $attachment_size = (int) ( filesize( $attachment_path ) / 1024 );
            $attachment_size = ( $attachment_size === 0 ) ? 1 : $attachment_size;
            $attachment_ext = explode( '.', $attachment_url );
            $attachment_ext = $attachment_ext[ count( $attachment_ext ) - 1 ];
            $attachment_uuid = str_replace( array( '.', $attachment_ext ), '', $attachment_name );
            $attachment_mime_type = explode( '/', get_post_mime_type( $message_attachment_id ) )[0];

            if ( $attachment_mime_type === 'image' ) {

                $attachment_image['id'] = $message_attachment_id;
                $attachment_image['uuid'] = $attachment_uuid;
                $attachment_image['name'] = $attachment_name;
                $attachment_image['url'] = $attachment_url;
                $attachment_image['path'] = $attachment_path;
                $attachment_image['size'] = $attachment_size;
                $attachment_image['ext'] = $attachment_ext;
                $attachment_image['upload_date'] = $attachment_upload_date;
                $img_attachments[] = $attachment_image;
            }

            if ( $attachment_mime_type === 'application' || $attachment_mime_type === 'text' ) {

                $attachment_doc['id'] = $message_attachment_id;
                $attachment_doc['uuid'] = $attachment_uuid;
                $attachment_doc['name'] = $attachment_name;
                $attachment_doc['url'] = $attachment_url;
                $attachment_doc['path'] = $attachment_path;
                $attachment_doc['size'] = $attachment_size;
                $attachment_doc['ext'] = $attachment_ext;
                $attachment_doc['upload_date'] = $attachment_upload_date;
                $doc_attachments[] = $attachment_doc;
            }
        }

        $img_attachments_html = sbchat_generate_inbox_img_attachments_html( $img_attachments, $sender_id );
        if ( $img_attachments_html !== false )
            $conversation_html .= $img_attachments_html;

        $doc_attachments_html = sbchat_generate_inbox_doc_attachments_html( $doc_attachments, $sender_id );
        if ( $doc_attachments_html !== false )
            $conversation_html .= $doc_attachments_html;
    }

    return $conversation_html;
}

function sbchat_generate_inbox_img_attachments_html( $img_attachments, $sender_id ) {

    $sbchat_messages = new Sb_Chat_Messages();
    add_filter( 'upload_dir', array( $sbchat_messages, 'sbchat_upload_dir' ), 10 );

    if ( empty( $sender_id ) || $sender_id <= 0 )
        return false;

    $total_image_attachments = count( $img_attachments );
    if ( is_array( $img_attachments ) && $total_image_attachments === 0 || ! is_array( $img_attachments ) )
        return false;

    $message_class = sbchat_get_inbox_message_class( $sender_id );
    $img_attachment_html = '';

    if ( $total_image_attachments <= 4 ) {
        foreach ( $img_attachments as $img_attachment ) {

            $img_uuid = esc_attr( $img_attachment['uuid'] );
            $img_url = esc_attr( $img_attachment['url'] );

            $img_src_full = wp_get_attachment_image_src( $img_attachment['id'], 'full' );
            $img_src_thumbnail = wp_get_attachment_image_src( $img_attachment['id'], array( 300, 200 ) );

            $img_attachment_html .= <<< ATTACHMENT_IMAGES_HTML
                <li class="message-bubble {$message_class}">
                    <div class="message-media">
                        <a data-fslightbox="{$img_uuid}" href="{$img_src_full[0]}">
                            <img src="{$img_src_thumbnail[0]}" id="{$img_uuid}" />
                        </a>    
                    </div>
                </li>
            ATTACHMENT_IMAGES_HTML;
        }
    }
    else if ( $total_image_attachments > 4 ) {

        $img_index = 0;

        $last_img_key = count( $img_attachments ) - 1;
        $last_img_uuid = esc_attr( $img_attachments[$last_img_key]['uuid'] );

        $last_image_id = esc_attr( $img_attachments[$last_img_key]['id'] );
        $last_img_src_full = wp_get_attachment_image_src( $last_image_id, 'full' );

        foreach ( $img_attachments as $img_attachment ) {

            $img_uuid = esc_attr( $img_attachment['uuid'] );
            $img_url = esc_attr( $img_attachment['url'] );

            $img_src_full = wp_get_attachment_image_src( $img_attachment['id'], 'full' );
            $img_src_thumbnail = wp_get_attachment_image_src( $img_attachment['id'], array( 300, 200 ) );

            if ( $img_index === 0 ) {

                $img_attachment_html .= <<< ATTACHMENT_IMAGES_HTML
                    <li class="message-bubble {$message_class}">
                        <div class="message-media">
                            <div class="grid-media">
                ATTACHMENT_IMAGES_HTML;
            }

            $img_attachment_html .= <<< ATTACHMENT_IMAGES_HTML
                <a data-fslightbox="{$last_img_uuid}" href="{$img_src_full[0]}">
                    <img src="{$img_src_thumbnail[0]}" id="{$img_uuid}" />
                </a>
            ATTACHMENT_IMAGES_HTML;

            if ( $img_index === $last_img_key ) {

                $img_attachment_html .= <<< ATTACHMENT_IMAGES_HTML
                                <a data-fslightbox="{$last_img_uuid}" href="{$last_img_src_full[0]}">
                                    <div class="overlay" id="{$last_img_uuid}" />
                                        <span class="images-counter">{$last_img_key}+</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </li>
                ATTACHMENT_IMAGES_HTML;
            }

            $img_index++;
        }
    }

    remove_filter( 'upload_dir', array( $sbchat_messages, 'sbchat_upload_dir' ) );
    return trim( $img_attachment_html );
}

function sbchat_generate_inbox_doc_attachments_html( $doc_attachments, $sender_id ) {

    $sbchat_messages = new Sb_Chat_Messages();
    add_filter( 'upload_dir', array( $sbchat_messages, 'sbchat_upload_dir' ), 10 );

    if ( empty( $sender_id ) || $sender_id <= 0 )
        return false;

    if ( is_array( $doc_attachments ) && count( $doc_attachments ) === 0 || ! is_array( $doc_attachments ) )
        return false;

    $message_class = sbchat_get_inbox_message_class( $sender_id );
    $doc_attachment_html = '';

    foreach( $doc_attachments as $doc_attachment ) {

        $doc_icon = sbchat_get_inbox_attachment_icon( $doc_attachment['ext'] );
        $doc_url = $doc_attachment['url'];
        $doc_name = $doc_attachment['name'];
        $doc_size = $doc_attachment['size'];
        $doc_upload_date = $doc_attachment['upload_date'];

        $doc_attachment_html .= <<< ATTACHMENT_DOCUMENT_HTML
            <li class="message-bubble {$message_class}">
                <div class="message-file-main">
                    <div class="message-file">
                        <div class="main-left">
                            <div class="icon"><img src="{$doc_icon}"></div>
                            <div class="right-cont">
                                <span class="title"><a target="_blank" href="{$doc_url}">{$doc_name}</a></span>
                                <small class="size">{$doc_size}KB</small>
                                <span class="type">Uploaded {$doc_upload_date}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        ATTACHMENT_DOCUMENT_HTML;
    }

    remove_filter( 'upload_dir', array( $sbchat_messages, 'sbchat_upload_dir' ) );
    return trim( $doc_attachment_html );
}

function sbchat_get_inbox_message_class( $sender_id ) {
    $message_class = '';
    if ( $sender_id > 0 ) {
        $message_class = ( get_current_user_id() == $sender_id ) ? 'reply' : 'sender';
    }

    if ( isset($_GET['page'], $_GET['user_id']) ) {
        $page = absint( $_GET['page'] );
        $user_id = absint( $_GET['user_id'] );
        if ( $user_id === (int) $sender_id ) {
            $message_class = 'reply';
        } else {
            $message_class = 'sender';
        }
    }

    return $message_class;
}

function sbchat_get_inbox_attachment_icon( $extension ) {

    if ( isset( $extension ) && ! empty( $extension ) )
        $ext_icon = esc_attr( get_bloginfo( 'url' ) . '/wp-content/plugins/sb_chat/assets/images/icons/' . $extension . '-icon.svg' );
    return $ext_icon;
}



// function sbchat_get_conversations_by_user_id( $user_id, $limit = '', $offset = '' ) {

//     global $wpdb;

//     $table = SBCHAT_TABLE_CONVERSATIONS;

//     $query = "SELECT * FROM $table WHERE `user_1` = %d OR `user_2` = %d ORDER BY updated DESC";

//     if ( ! empty( $limit ) && $limit > 0 ) {
//         $query .= " LIMIT %d";
//     }

//     if ( ! empty( $offset ) && $offset > 0 ) {
//         $query .= " OFFSET %d";
//     }

//     $conversations_query = $wpdb->prepare( $query, $user_id, $user_id, $limit, $offset );
//     $user_conversations = $wpdb->get_results( $conversations_query, ARRAY_A );

//     if ( empty( $user_conversations ) ) {
//         return false;
//     }

//     return $user_conversations;
// }


function sbchat_get_conversations_by_user_id( $user_id, $limit = '', $offset = '' ) {
    global $wpdb;
    $table = SBCHAT_TABLE_CONVERSATIONS;
    $query = "SELECT * FROM $table WHERE `user_1` = %d OR `user_2` = %d ORDER BY updated DESC";
    if ( ! empty( $limit ) && $limit > 0 ) {
        $query .= " LIMIT %d";
    }
    if ( ! empty( $offset ) && $offset > 0 ) {
        $query .= " OFFSET %d";
    }
    $placeholders = array( $user_id, $user_id );
    if ( ! empty( $limit ) && $limit > 0 ) {
        $placeholders[] = $limit;
    }
    if ( ! empty( $offset ) && $offset > 0 ) {
        $placeholders[] = $offset;
    }
    $conversations_query = $wpdb->prepare( $query, $placeholders );
    $user_conversations = $wpdb->get_results( $conversations_query, ARRAY_A );

    if ( empty( $user_conversations ) ) {
        return false;
    }
    return $user_conversations;
}

function sbchat_get_conversation_by_id( $conversation_id ) {

    global $wpdb;
    $table = SBCHAT_TABLE_CONVERSATIONS;

    // $conversations_query = $wpdb->prepare( "SELECT * FROM $table WHERE `id` = $conversation_id" );
    $conversations_query = $wpdb->prepare( "SELECT * FROM $table WHERE `id` = %d", $conversation_id );
    $conversation = $wpdb->get_row( $conversations_query, ARRAY_A );
    if ( ( is_array( $conversation ) && count( $conversation ) === 0 ) || empty( $conversation ) )
        return false;

    return $conversation;
}

function sbchat_get_first_conversation_message( $conversation_id ) {

    global $wpdb;

    $table = SBCHAT_TABLE_MESSAGES;
    $query = $wpdb->prepare( "SELECT * FROM $table WHERE `conversation_id` = $conversation_id ORDER BY created DESC" );
    $first_conversation_message = $wpdb->get_row( $query, ARRAY_A );

    if ( ( is_array( $first_conversation_message ) && count( $first_conversation_message ) === 0 ) || empty( $first_conversation_message ) )
        return false;

    return $first_conversation_message;
}

function sbchat_get_last_conversation_message( $conversation_id ) {

    global $wpdb;

    $table = SBCHAT_TABLE_MESSAGES;
    $query = $wpdb->prepare( "SELECT * FROM $table WHERE `conversation_id` = $conversation_id ORDER BY created DESC" );
    $last_conversation_message = $wpdb->get_row( $query, ARRAY_A );

    if ( ( is_array( $last_conversation_message ) && count( $last_conversation_message ) === 0 ) || empty( $last_conversation_message ) )
        return false;

    return $last_conversation_message;
}

// function sbchat_get_conversation_status( $conversation, $user_id ) {

//     if ( ! is_array( $conversation ) || count( $conversation ) === 0 || empty( $conversation ) )
//         return null;

//     $conversation_id = absint( $conversation['id'] );
//     $conversation_messages = sbchat_get_unread_conversation_messages( $conversation_id, $user_id );

//     if ( $conversation_messages === false )
//         return null;

//     $conversation_read_status = ( is_array( $conversation_messages ) && count( $conversation_messages ) > 0 ) ? 'unread' : 'read';
//     return $conversation_read_status;
// }


if(!function_exists('sbchat_get_conversation_status_check')){

    function sbchat_get_conversation_status_check( $conversation, $user_id ) {

        $conversation_read_status = 1;
        if ( ! is_array( $conversation ) || count( $conversation ) === 0 ) {
            return $conversation_read_status;
        }

        if ( ! isset( $conversation['user_1'], $conversation['user_2'] ) ) {
            return $conversation_read_status;
        }

        $read_check = ( isset($conversation['user_1']) && $conversation['user_1'] == $user_id ) ? 'read_user_1' : 'read_user_2';

        if ( ! isset( $conversation[$read_check] ) ) {
            return $conversation_read_status;
        }

        $conversation_read_status = (bool) $conversation[$read_check];

        return $conversation_read_status;
    }
}


function sbchat_inbox_reload_incoming_messages() {

    $nonce = isset($_POST['security']) ? sanitize_text_field($_POST['security']) : '';
    if ( ! wp_verify_nonce( $nonce, 'sbchat_reload_incoming_messages_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sb_chat' ) ), 403 );
    }

    $conversations_limit = ( isset( $_POST['conversations_offset'] ) && ! empty( $_POST['conversations_offset'] ) && $_POST['conversations_offset'] > 0 ) ? absint( $_POST['conversations_offset'] ) : 7;
    $context = ( isset( $_POST['context'] ) && ! empty( $_POST['context'] ) ) ? esc_html( $_POST['context'] ) : false;

    $user_id = get_current_user_id();
    if ( ! is_user_logged_in() || $user_id <= 0 )
        wp_send_json_error( array( 'message' => __( 'User authentication failed!', 'sb_chat' ) ) );

    $conversations = sbchat_get_conversations_by_user_id( $user_id, $conversations_limit );
    if ( $conversations === false )
        wp_send_json_error( array( 'message' => __( 'This user has no conversations.', 'sb_chat' )) );

    $conversation_list_html = '';
    $page_context = '';

    $index = 0;

    $current_conversation =  isset($_POST['conversation_id'])  ?  $_POST['conversation_id'] : "";

    foreach( $conversations as $conversation ) {

        $unread_class = '';
        $active_class  = '';

        $sender_id = ( $user_id == $conversation['user_2'] ) ? absint( $conversation['user_1'] ) : absint( $conversation['user_2'] );
        if ( ! isset( $sender_id ) || empty( $sender_id ) || $sender_id === 0 || ! is_numeric( $sender_id ) )
            continue;


        $user_key   = ( $user_id == $conversation['user_1'] ) ? 'user_1'  : 'user_2' ;
        $chat_delete_key   =  ( $user_key == 'user_1' ) ? 'deleted_by_user_1'  : 'deleted_by_user_2' ;

        if(isset($conversation[$chat_delete_key]) && $conversation[$chat_delete_key]  == 1){
            continue;
        }

        $sender = get_userdata( $sender_id );
        if ( is_wp_error( $sender ) )
            continue;


        $sender_fullname = $sender->display_name;

        if($sender_fullname  == ""){

            $sender_fullname = esc_html( $sender->first_name ) . ' ' . esc_html( $sender->last_name );
        }

        if ( $context == 'user-dashboard' )
            $page_context = '/dashboard/?action=view&ext=inbox';

        if ( $context == 'inbox' )
            $page_context = '/inbox/?action=view';

        if ( $context == 'sbchat' )
            $page_context = '/inbox/?action=view';

        $inbox_url = get_bloginfo( 'url' ) . $page_context . '&conversation_id=' . $conversation['id'];
        $dashboard_page =  get_option('sb_plugin_options');
        $dashboard_page  =  isset($dashboard_page['sb-dashboard-page']) ? get_the_permalink($dashboard_page['sb-dashboard-page']) : home_url();
        $inbox_url =   $dashboard_page.'?action=view&conversation_id=' . $conversation['id'];


        $sender_avatar = sbchat_get_user_avatar($sender_id, 45); //get_avatar( $sender_id, 45 );

        $last_conversation_message = sbchat_get_last_conversation_message( $conversation['id'] );


        global $sb_plugin_options;
        $words_fillters = $sb_plugin_options['sb_chat_bad_words_filter'];
        $words = is_array($words_fillters)
            ? array_filter(array_map('trim', $words_fillters))
            : array_filter(array_map('trim', explode(',', (string)$words_fillters)));
        $replace = $sb_plugin_options['sb_chat_bad_words_replace'];
        $last_message_row = sbchat_get_last_conversation_message( $conversation['id'] );
        $last_message_text = is_array($last_message_row) && isset($last_message_row['message']) ? (string)$last_message_row['message'] : '';
        $message = sbChat_badwords_filter($words, $last_message_text, $replace);


        $timestamp = human_time_diff( strtotime( $conversation['updated'] ), current_time( 'timestamp', 1 ) );

        $is_conversation_read = sbchat_get_conversation_status_check( $conversation, $user_id );

        if ( !$is_conversation_read ){
            $unread_class = "unread";
        }


        if($current_conversation ==  $conversation['id']){

            $active_class = " active";
        }

        if(trim($sender_fullname) == '') {
            $sender_fullname = __("User has been removed", "sb_chat");
        }


        $conversation_list_html .= <<< SBCHAT_CONVERSATION

            <li class ="{$unread_class}{$active_class}" data-id="{$conversation['id']}">
                <a target="_self" href="{$inbox_url}" class="d-flex align-items-center con-chat-list" data-recipient_id = "{$sender_id}"  data-conv = "{$conversation['id']}">
                    <div class="flex-shrink-0 sb-avatar">
                        {$sender_avatar}
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="sender-details">{$sender_fullname}</h3>
                        <p>{$timestamp} ago</p>
                    </div>
                </a>
            </li>
        SBCHAT_CONVERSATION;


        $index++;
    }

    $conversation_id = absint($_POST['conversation_id']);
    $conversation_id = ( isset( $conversation_id ) && ! empty( $conversation_id ) && $conversation_id > 0 ) ? absint( $conversation_id ) : 0;
    if ( $conversation_id > 0 ) {

        $conversation_messages_html = sbchat_get_inbox_conversations( $user_id, $conversation_id );
        $conversation_messages_html = trim( $conversation_messages_html );
    }

    $conversation_list_html = trim( $conversation_list_html );
    if ( empty( $conversation_list_html ) ){
        $conversation_list_html = "";
    }

    if ( ! isset( $conversation_messages_html ) || empty( $conversation_messages_html ) ){
        $conversation_messages_html = "";
    }

    wp_send_json_success( array( 'message' => 'Incoming messages retreived successfully!', 'conversation_list_items' => $conversation_list_html, 'conversation_messages' => trim( $conversation_messages_html ) ) );
}
add_action( 'wp_ajax_inbox_reload_incoming_messages', 'sbchat_inbox_reload_incoming_messages' );
add_action( 'wp_ajax_nopriv_reload_incoming_messages', 'sbchat_inbox_reload_incoming_messages' );

function sbchat_get_conversation_messages( $conversation_id ) {

    global $wpdb;

    $table = SBCHAT_TABLE_MESSAGES;
    $query = $wpdb->prepare( "SELECT * FROM $table WHERE `conversation_id` = '". $conversation_id ."' " );
    $results = $wpdb->get_results( $query, ARRAY_A );

    if ( ( is_array( $results ) && count( $results ) === 0 ) || empty( $results ) )
        return false;

    return $results;
}

function sbchat_get_unread_conversation_messages( $conversation_id, $receiver_id ) {

    global $wpdb;

    $table = SBCHAT_TABLE_MESSAGES;
    $query = $wpdb->prepare( "SELECT * FROM $table WHERE `conversation_id` = $conversation_id AND `receiver_id` = $receiver_id AND `read_status` = 0" );
    $results = $wpdb->get_results( $query, ARRAY_A );

    if ( ( is_array( $results ) && count( $results ) === 0 ) || empty( $results ) )
        return false;

    return $results;
}

/**
 * Fetch all unread messages that have not yet been emailed
 * Returns an array of rows: id (message_id), conversation_id, sender_id, receiver_id
 */
function sbchat_get_all_unemailed_unread_messages() {

    global $wpdb;

    $table = SBCHAT_TABLE_MESSAGES;

    $query = "SELECT id, conversation_id, sender_id, receiver_id FROM $table WHERE read_status = 0 AND email_sent = 0 ORDER BY created ASC";

    $results = $wpdb->get_results( $query, ARRAY_A );

    if ( empty( $results ) )
        return false;

    return $results;
}

/**
 * Mark provided message IDs as emailed
 */
function sbchat_mark_messages_emailed( $message_ids ) {

    global $wpdb;

    if ( ! is_array( $message_ids ) || count( $message_ids ) === 0 )
        return false;

    $table = SBCHAT_TABLE_MESSAGES;

    $ids = array_map( 'absint', $message_ids );
    $ids = array_filter( $ids );
    if ( count( $ids ) === 0 )
        return false;

    $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
    $sql = "UPDATE $table SET email_sent = 1 WHERE id IN ( $placeholders )";
    $params = $ids;
    array_unshift( $params, $sql );
    $prepared = call_user_func_array( array( $wpdb, 'prepare' ), $params );
    $updated = $wpdb->query( $prepared );

    if ( $updated === false )
        return false;

    return $updated;
}

function sbchat_load_conversations_list() {

    $nonce = isset($_POST['security']) ? sanitize_text_field($_POST['security']) : '';
    if ( ! wp_verify_nonce( $nonce, 'sbchat_load_conversations_list_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sb_chat' ) ), 403 );
    }

    $limit = ( isset( $_POST['limit'] ) && ! empty( $_POST['limit'] ) && $_POST['limit'] > 0 ) ? absint( $_POST['limit'] ) : 0;
    $offset = ( isset( $_POST['offset'] ) && ! empty( $_POST['offset'] ) && $_POST['offset'] > 0 ) ? absint( $_POST['offset'] ) : 0;
    $context = ( isset( $_POST['context'] ) && ! empty( $_POST['context'] ) ) ? esc_html( $_POST['context'] ) : false;

    if ( $limit === 0 || $offset == 0 )
        wp_send_json_error( array( 'message' => __( 'No offset or limit is given! Conversations list cannot be retreived.', 'sb_chat' ) ) );

    if ( $offset < $limit )
        wp_send_json_error( array( 'message' => __( 'Cannot load conversations list, invalid offset is given!', 'sb_chat' ) ) );

    $user_id = get_current_user_id();
    $user_conversations = sbchat_get_conversations_by_user_id( $user_id, $limit, $offset );

    $offset = $offset + count( $user_conversations );

    if ( $user_conversations === false )
        wp_send_json_error( array( 'message' => __( 'No more conversations to load.', 'sb_chat' ) ) );

    $conversation_list_html = '';

    $index = 0;
    foreach( $user_conversations as $user_conversation ) {

        $unread_class = '';

        $first_conversation_message = sbchat_get_first_conversation_message( $user_conversation['id'] );
        if ( $first_conversation_message !== false )
            $sender_id = absint( $first_conversation_message['sender_id'] );

        if ( ! isset( $sender_id ) || empty( $sender_id ) || $sender_id === 0 || ! is_numeric( $sender_id ) )
            continue;


        $sender = get_userdata( $sender_id );
        if ( is_wp_error( $sender ) )
            continue;


        $sender_fullname = $sender->display_name;
        if($sender_fullname == ""){
            $sender_fullname = esc_html( $sender->first_name ) . ' ' . esc_html( $sender->last_name );
        }

        if ( $context === 'user-dashboard' )
            $page_context = '/dashboard/?action=view&ext=inbox';

        if ( $context === 'inbox' )
            $page_context = '/inbox/?action=view';

        if ( $context === 'sbchat' )
            $page_context = '/inbox/?action=view';

        $inbox_url = get_bloginfo( 'url' ) . $page_context . '&conversation_id=' . $user_conversation['id'];
        $sender_avatar = get_avatar( $sender_id, 45 );

        $last_conversation_message = sbchat_get_last_conversation_message( $user_conversation['id'] );

        $timestamp = human_time_diff( strtotime( $last_conversation_message['created'] ), current_time( 'timestamp', 1 ) );

        $is_conversation_read = sbchat_get_conversation_status_check( $user_conversation, $user_id );
        if ( ! $is_conversation_read )
            $unread_class = 'class="unread"';
        if ($sender) {
            $conversation_list_html .= <<<SBCHAT_CONVERSATION
                    <li {$unread_class} data-id="{$user_conversation['id']}" style="display: none;">
                        <a target="_self" href="{$inbox_url}" class="d-flex align-items-center">
                            <div class="flex-shrink-0 sb-avatar">
                                {$sender_avatar}
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="sender-details">{$sender_fullname}</h3>
                                <p>{$timestamp} ago</p>
                            </div>
                        </a>
                    </li>
            SBCHAT_CONVERSATION;
        }

        $index++;
    }

    wp_send_json_success( array( 'message' => __( 'Conversations list updated successfully!', 'sb_chat' ), 'user_conversations' => $user_conversations, 'count' => count( $user_conversations ), 'offset' => $offset, 'conversations_list' => $conversation_list_html ) );
}
add_action( 'wp_ajax_load_conversations_list', 'sbchat_load_conversations_list' );
add_action( 'wp_ajax_nopriv_load_conversations_list', 'sbchat_load_conversations_list' );

/**
 * Get all conversations for admin view
 * This function retrieves all conversations from the database for admin monitoring
 */
function sbchat_get_all_conversations_for_admin() {
    global $wpdb;
    $table = SBCHAT_TABLE_CONVERSATIONS;
    
    // Get all conversations ordered by most recent
    $query = "SELECT * FROM $table ORDER BY updated DESC";
    $all_conversations = $wpdb->get_results( $query, ARRAY_A );
    
    if ( empty( $all_conversations ) ) {
        return false;
    }
    
    return $all_conversations;
}

function sbchat_get_all_unread_conversations( $email_sent_status = 0 ) {
    global $wpdb;

    error_log( "sbchat_get_all_unread_conversations() called with email_sent_status = " . $email_sent_status );

    if ( $email_sent_status < 0 || $email_sent_status > 1 ) {
        error_log( "Invalid email_sent_status provided: " . $email_sent_status );
        return false;
    }

    $email_sent_condition = ( (int) $email_sent_status === 1 )
        ? 'AND ( email_sent = 1 )'
        : 'AND ( email_sent = 0 OR email_sent IS NULL )';

    $table = esc_sql( SBCHAT_TABLE_CONVERSATIONS );

    $query = "
        SELECT * FROM $table
        WHERE ( read_user_1 = 0 OR read_user_2 = 0 )
        $email_sent_condition
        ORDER BY created
    ";

    error_log( "sbchat_get_all_unread_conversations() running query: " . $query );

    $results = $wpdb->get_results( $query, ARRAY_A );

    if ( $wpdb->last_error ) {
        error_log( "Database error in sbchat_get_all_unread_conversations(): " . $wpdb->last_error );
    }

    if ( empty( $results ) ) {
        error_log( "sbchat_get_all_unread_conversations() returned no results." );
        return false;
    }

    error_log( "sbchat_get_all_unread_conversations() returned " . count( $results ) . " results." );

    return $results;
}

function sbchat_update_unread_conversation_status( $conversation_id, $email_sent_status = 0 ) {

    global $wpdb;

    $conversation_id = absint( $conversation_id );

    if ( $conversation_id <= 0 )
        return false;

    if ( $email_sent_status < 0 || $email_sent_status > 1 )
        return false;

    $status_value = ( $email_sent_status ) ? 1 : 0;

    $table = SBCHAT_TABLE_CONVERSATIONS;
    $updated = $wpdb->update(
        $table,
        array( 'email_sent' => $status_value ),
        array( 'id' => $conversation_id ),
        array( '%d' ),
        array( '%d' )
    );

    if ( $updated === false )
        return false;

    return $updated;
}

function sbchat_inbox_conversations_allowed_html() {

    $allowed_tags = array(
        'a' => array(
            'id' => array(),
            'class' => array(),
            'href'  => array(),
            'rel'   => array(),
            'title' => array(),
            'data-fslightbox' => array(),
            'style' => array(),
        ),
        'img' => array(
            'id'  => array(),
            'class'  => array(),
            'src'    => array(),
            'height' => array(),
            'width'  => array(),
            'style' => array(),
            'alt'    => array(),
        ),
        'span' => array(
            'id'  => array(),
            'class' => array(),
            'style' => array(),
        ),
        'small' => array(
            'id'  => array(),
            'class' => array(),
            'style' => array(),
        ),
        'p' => array(
            'id'  => array(),
            'class' => array(),
            'style' => array(),
        ),
        'div' => array(
            'id' => array(),
            'class' => array(),
            'style' => array(),
        ),
        'ul' => array(
            'id' => array(),
            'class' => array(),
            'style' => array(),
        ),
        'li' => array(
            'id' => array(),
            'class' => array(),
            'style' => array(),
        ),
    );

    return $allowed_tags;
}
