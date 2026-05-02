<?php wp_head(); ?>
<section class="message-area">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="chat-area">
                    <div class="chatlist">
                        <div class="modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="chat-header">
                                    
                                    <div class="msg-search">
                                        <input type="text" class="form-control" id="inlineFormInputGroup" placeholder="<?php echo esc_attr('Search','sb_chat')?>" aria-label="search">
                                    </div>
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="Open-tab" data-bs-toggle="tab" data-bs-target="#Open" type="button" role="tab" aria-controls="Open" aria-selected="true"><?php echo esc_html__('All Conversations','sb_chat')?></button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="modal-body">
                                    <div class="messages-inbox chat-list" data-context="inbox"><?php

                                         $user_id = get_current_user_id();
                                        $user_conversations = array();

                                        $display_limit = 7;

                                        if ( $user_id !== 0 )
                                    
                                             $user_conversations = sbchat_get_conversations_by_user_id( $user_id, $display_limit );

                                        if ( $user_conversations !== false ) : ?>
                                            <ul class="chat-list-detail"><?php
                                                
                                                foreach( $user_conversations as $user_conversation ) :

                            

                                                    $recipient_id = ( $user_id == $user_conversation['user_2'] ) ? absint( $user_conversation['user_1'] ) : absint( $user_conversation['user_2'] );
                                                 
                                                    $user_key   = ( $user_id == $user_conversation['user_1'] ) ? 'user_1'  : 'user_2' ;
                                                    $chat_delete_key   =  ( $user_key == 'user_1' ) ? 'deleted_by_user_1'  : 'deleted_by_user_2' ;

                                                    if(isset($user_conversation[$chat_delete_key]) && $user_conversation[$chat_delete_key]  == 1){
                                                        continue;
                                                    }

                                                    $recipient = get_userdata( $recipient_id );
                                                    $recipient_output = '';

                                                   // $last_conversation_message = sbchat_get_last_conversation_message( $user_conversation['id'] );
                                                    $is_conversation_read = sbchat_get_conversation_status_check( $user_conversation, $user_id );

                                                    $last_message_sent_ago = (string) human_time_diff( strtotime( $user_conversation['updated'] ), current_time( 'timestamp', 1 ) );
                                                   
   
                                                    $dashboard_page =  get_option('sb_plugin_options');

                                                    $dashboard_page  =  isset($dashboard_page['sb-dashboard-page']) ? get_the_permalink($dashboard_page['sb-dashboard-page']) : home_url();
                                                   $conversation_url =   $dashboard_page.'?action=view&conversation_id=' . $user_conversation['id']; ?>

                                                    <li <?php if ( !$is_conversation_read ) echo 'class="unread"' ?> data-id="<?php echo esc_attr( $user_conversation['id'] ) ?>">
                                                        <a target="_self" data-conv="<?php echo esc_attr( $user_conversation['id'] ) ?>" href="<?php echo esc_attr( $conversation_url ) ?>" class="d-flex align-items-center">
                                                            <div class="flex-shrink-0 sb-avatar">
                                                                <?php echo get_avatar( $recipient_id , 45 ) ?>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3"><?php
                                                                if ( ! is_wp_error( $recipient ) ) {
                                                                    
                                                                    $recipient_nicename = esc_html( $recipient->user_nicename );
                                                                    $recipient_fullname = esc_html( $recipient->first_name ) . ' ' . esc_html( $recipient->last_name );
                                                                    
                                                                    $recipient_output = $recipient_fullname;
                                                                    if ( empty( $recipient->first_name ) || empty( $recipient->last_name ) )
                                                                        $recipient_output = $recipient_nicename;
                                                                } ?>
                                                                <h3 class="sender-details"><?php 
                                                                    if ( empty( $recipient_output ) )
                                                                        $recipient_output = __( 'User has been removed', 'sb_chat' ); 
                                                                    echo esc_html( $recipient_output ) ?>
                                                                </h3>
                                                                <p>
                                                                    <?php echo esc_html( $last_message_sent_ago ) . ' ago'; ?>
                                                                </p>
                                                            </div>
                                                        </a>
                                                    </li><?php
                                                endforeach; ?>
                                            </ul><?php
                                            
                                            if ( count( $user_conversations ) > $display_limit ) { ?>
                                                
                                                <button type='button' class='btn btn-primary load-conversations' data-limit="<?php echo esc_attr( $display_limit ) ?>" data-offset="<?php echo esc_attr( $display_limit ) ?>">
                                                    Load more conversations
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

                                $conversation_id = ( isset( $_GET['conversation_id'] ) && ! empty( $_GET['conversation_id'] ) ) ? esc_html( $_GET['conversation_id'] ) : 0;

                                $current_conversation   =  false;
                                if ( $conversation_id !== 0 ){
                                    $current_conversation = sbchat_get_conversation_by_id( $conversation_id );
                                }

                                if ( ! $current_conversation ) { ?>

                                    <div class="msg-head"></div>
                                    <div class="modal-body" id="sbModalBody">
                                        <div class="msg-body"><ul class="messages-list"></ul></div>
                                    </div>
                                    <div class="send-box chat-footer">
                                        <h4 class="not-found"><?php esc_html_e('No Message found.','sb_chat'); ?></h4>
                                    </div><?php
                                }
                                
                                $user_1 = isset( $current_conversation['user_1'] ) ? $current_conversation['user_1'] : 0;
                                $user_2 = isset( $current_conversation['user_2'] ) ? $current_conversation['user_2'] : 0;

                                if ( $user_id == $user_1 || $user_id == $user_2 ) {

                                    $recipient_id = ( $user_1 == $user_id ) ? $user_2 : $user_1;
                                    $recipient = get_userdata( $recipient_id );

                                    if ( ! is_wp_error( $recipient ) &&  is_array($recipient) ) {
                                                                    
                                        $recipient_nicename = esc_html( $recipient->user_nicename );
                                        $recipient_fullname = esc_html( $recipient->first_name ) . ' ' . esc_html( $recipient->last_name );
                                        
                                        $recipient_output = $recipient_fullname;
                                        if ( empty( $recipient->first_name ) || empty( $recipient->last_name ) )
                                            $recipient_output = $recipient_nicename;
                                    }

                                    if ( empty( $recipient_output ) )
                                        $recipient_output = __( 'User has been removed', 'sb_chat' ); ?>

                                    <div class="msg-head">
                                        <div class="row">
                                            <div class="col-8">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 sb-avatar">
                                                        <?php echo get_avatar( $recipient_id , 45 ) ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h3><?php echo esc_html( $recipient_output ); ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                         
                                            <div class="col-4">
                                                
                                            <ul class="moreoption">
                                                    <li class="navbar nav-item dropdown dropstart">
                                                    <button class="delete-single-chat btn-theme" data-delete="<?php echo esc_attr__( 'Are you sure you want to remove this?', 'sb_chat' ); ?>" href="#"><?php echo esc_html__('Delete','sb_chat')  ?></button>    
                                                        <div class="sb-notification success"><p><?php esc_html_e( 'Conversation is removed', 'sb_chat' ); ?></p></div>

                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-body" id="sbModalBody">
                                        <div class="msg-body">
                                            <ul class="messages-list"><?php
                                                $inbox_conversations = sbchat_get_inbox_conversations( $user_id, $conversation_id );
                                                if ( ! empty( $inbox_conversations ) ) echo wp_kses( $inbox_conversations, sbchat_inbox_conversations_allowed_html() ); ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="send-box chat-footer">
                                        <?php
                                        ?>
                                        <form action="" class="send-message" enctype="multipart/form-data">
                                            <div class="d-flex">
                                                <input type="text" id="message_box" class="form-control message-details" aria-label="message…" placeholder="<?php echo esc_attr('Write message…','sb_chat');?>">
                                                <button class="btn btn-theme btn-icon send-btn text-light mb-1" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i><?php echo esc_html('Send','sb_chat')?></button>
                                                <input type="hidden" id="conversation_id" name="conversation_id" value="<?php echo esc_attr( $conversation_id ) ?>">
                                                <input type="hidden" id="recipient_id" name="recipient_id" value="<?php echo esc_attr( $recipient_id ) ?>">
                                            </div>
                                            <div id="sbchat-mu" class="sbchat_upload_items"><?php echo esc_html__("Add Attachments", "sb_chat"); ?></div>
                                            <div class="dropzone-settings" style="display: none;"><?php
                                             
                                                if ( get_option( 'sb_plugin_options' ) !== false )
                                                    $plugin_options = get_option( 'sb_plugin_options' );

                                                if ( is_array( $plugin_options ) && count( $plugin_options ) > 0 ) {

                                                    $allowed_mime_types = $plugin_options['sbchat_allowed_mime_types'];
                                                    $max_file_size = $plugin_options['sb_max_file_size'];
                                                    $max_files_upload = $plugin_options['sbchat_max_files_upload'];
                                                    
                                                    $allowed_mime_types = ( is_array( $allowed_mime_types ) && count( $allowed_mime_types ) > 0 ) ? implode( ',', $allowed_mime_types ) : '';
                                                    $max_file_size = ( ! empty( $max_file_size ) && $max_file_size > 0 ) ? absint( $max_file_size / 1024 ) : 1;
                                                    $max_files_upload = ( ! empty( $max_files_upload ) && $max_files_upload > 0 ) ? absint( $max_files_upload ) : 7; ?>

                                                    <input type="hidden" id="dz_max_file_size" value="<?php echo esc_attr( $max_file_size ) ?>" />
                                                    <input type="hidden" id="dz_max_files_upload" value="<?php echo esc_attr( $max_files_upload ) ?>" />
                                                    <input type="hidden" id="dz_allowed_mime_types" value="<?php echo esc_attr( $allowed_mime_types ) ?>" /><?php
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
        </div>
    </div>
    </div>
</section>
<?php wp_footer();