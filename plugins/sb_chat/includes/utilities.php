<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user avatar with local image support
 */
if (!function_exists('sbchat_get_user_avatar')) {
    function sbchat_get_user_avatar($user_id, $size = '45') {
        global $adforest_theme;
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return get_avatar($user_id, $size);
        }
        
        // Check for local user avatar
        $user_pic = trailingslashit(get_template_directory_uri()) . 'images/users/9.jpg';
        if (isset($adforest_theme['sb_user_dp']['url']) && $adforest_theme['sb_user_dp']['url'] != "") {
            $user_pic = $adforest_theme['sb_user_dp']['url'];
        }
        
        $image_link = array();
        if (get_user_meta($user_id, '_sb_user_pic', true) != "") {
            $attach_id = get_user_meta($user_id, '_sb_user_pic', true);
            $image_link = wp_get_attachment_image_src($attach_id, $size);
        }
        
        if (isset($image_link) && !empty($image_link) && is_array($image_link) && count($image_link) > 0) {
            if ($image_link[0] != "") {
                $headers = @get_headers($image_link[0]);
                if (strpos($headers[0], '404') === false) {
                    $image_link = $image_link[0];
                } else {
                    $image_link = $user_pic;
                }
            } else {
                $image_link = $user_pic;
            }
        } else {
            $image_link = $user_pic;
        }
        
        $avatar = $image_link;
        $avatar = "<img alt='User Avatar' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        return $avatar;
    }
}

/* Sb_Chat_Messages class */
class Sb_Chat_Messages
{

    public $current_uuid = null;

    public function __construct()
    {
        add_action('wp_ajax_sb_send_message_ajax', array($this, 'sb_send_message_ajax_callback'));
        add_action('wp_ajax_nopriv_sb_send_message_ajax', array($this, 'sb_send_message_ajax_callback'));
        add_action('wp_ajax_sb_get_popup_data', array($this, 'sb_get_popup_data_callback'));
        add_action('wp_ajax_sb_notification_ajax', array($this, 'sb_notification_ajax'));
        add_action('wp_ajax_sb_delete_chat', array($this, 'sb_delete_chat'));
        add_action('wp_ajax_sb_delete_single_user_chat', array($this, 'sb_custom_delete_chat'));
        add_action('wp_ajax_sb_block_single_user', array($this, 'sb_custom_block_user'));
        add_action('wp_ajax_sb_upload_attachments', array($this, 'sb_upload_attachments'));
        add_action('wp_ajax_delete_sb_attachment', array($this, 'delete_sb_attachment'));
        add_action('wp_ajax_sb_read_message', array($this, 'sb_read_message'));
        add_shortcode('sb_chat_shortcode_popup', array($this, 'sb_chat_popup'));
        add_action('wp_ajax_localize_file_attachments', array($this, 'sb_localize_file_attachments'));

        //if ( ! is_admin() )
        //add_filter( 'upload_dir', array( $this, 'sbchat_upload_dir' ), 10 );

        //add_filter( 'intermediate_image_sizes_advanced', array( $this, 'sbchat_thumbnail_image_sizes' ), 10, 1 );
    }

    /*
    public function sbchat_thumbnail_image_sizes( $new_sizes ) {

    $new_sizes = array();
    $new_sizes['chat_thumbnails_no_crop'] = array(
    'width' => 300,
    'height' => 300,
    'crop' => 1,
    );

    $new_sizes['chat_thumbnails_cropped'] = array(
    'width' => 300,
    'height' => 300,
    'crop' => 0,
    );

    return $new_sizes;
    }
     */

    public function sb_chat_popup($atts)
    {
        if(!class_exists('SB_Chat')) {
            return false;
        }

        // Define default attributes
        $default_atts = array(
            'post_id' => 0,
            'post_author_id' => 0,
            'class' => '',
            'icon' => '',
            'button_title' => '',
        );

        // Use WordPress function to properly parse attributes
        $shortcode_atts = shortcode_atts($default_atts, $atts, 'sb_chat_shortcode_popup');

        // Extract values with proper fallbacks
        $extra_class = !empty($shortcode_atts['class']) ? $shortcode_atts['class'] : "";
        $icon = !empty($shortcode_atts['icon']) ? $shortcode_atts['icon'] : "";
        $button_title = !empty($shortcode_atts['button_title']) ? $shortcode_atts['button_title'] : "";
        $post_id = !empty($shortcode_atts['post_id']) ? absint($shortcode_atts['post_id']) : "";
        $post_author_id = !empty($shortcode_atts['post_author_id']) ? absint($shortcode_atts['post_author_id']) : 0;

        if (empty($post_author_id)) {
            return false;
        }

        $current_user_id = get_current_user_id();

        $chat_button_protip = '';
        $chat_button_protip_title = '';
        $chat_button_class = " sbchat-myBtn ";

        if (!is_user_logged_in()) {
            $chat_button_class = '';
            $chat_button_protip = ' protip';
            $conversation_id = 0;
            $chat_button_protip_title = 'data-pt-title="' . esc_attr__('Please log in first to Send Message', 'sb_chat') . '" data-pt-position="top" data-pt-scheme="black" disabled="disabled"';
        }
        if ($current_user_id == $post_author_id) {
            $chat_button_class = '';
            $chat_button_protip = ' protip';
            $conversation_id = 0;
            $chat_button_protip_title = 'data-pt-title="' . esc_attr__('You can not send message to yourself!', 'sb_chat') . '" data-pt-position="top" data-pt-scheme="black" disabled="disabled"';
        }
        if ($current_user_id != $post_author_id) {
            $sbchat_inbox_page = SB_Chat::get_plugin_options('sb-dashboard-page');
            $sbchat_inbox_page = (!empty($sbchat_inbox_page)) ? get_permalink(absint($sbchat_inbox_page)) : 'javascript:void(0)';

            $sbchat_messages = new Sb_Chat_Messages();
            $sbchat_conversation = $sbchat_messages->sb_get_conversations($current_user_id);
            $conversation_id = (is_array($sbchat_conversation) && count($sbchat_conversation) > 0) ? esc_attr($sbchat_conversation[0]->id) : 0;
        }

        $shortcode_popup = '
        <a data-user_id="' . esc_attr($post_author_id) . '" data-post_id="' . esc_attr($post_id) . '" href="javascript:void(0)" class="scroll chat_toggler_popup ' . esc_attr($chat_button_class) . ' ' . esc_attr($extra_class) . '' . esc_attr($chat_button_protip) . '"  ' . $chat_button_protip_title . '>
            <i class="' . esc_attr($icon) . '"></i>
            ' . esc_html($button_title) . '
        </a>
        ';

        return $shortcode_popup;
    }

    /*get pop up modal data*/
    public function sb_get_popup_data_callback()
    {

        $current_user_id = get_current_user_id();
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $post_author_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $sender = get_userdata($post_author_id);
        $sender_fullname = $sender ? $sender->display_name : '';

        if ($current_user_id == $post_author_id) {
            $conversation_id = 0;
        }

        if ($current_user_id != $post_author_id) {
            $sbchat_inbox_page = SB_Chat::get_plugin_options('sb-dashboard-page');
            $sbchat_inbox_page = (!empty($sbchat_inbox_page)) ? get_permalink(absint($sbchat_inbox_page)) : 'javascript:void(0)';
            $sbchat_messages = new Sb_Chat_Messages();

            /* AdForest patch (per-ad conversations): pre-fill conversation_id
               with the EXACT ad-scoped conversation between this buyer and
               seller, not "the first conversation in the user's list". The
               legacy lookup picked sbchat_conversation[0]->id, which is
               whichever thread was most recently updated regardless of
               recipient or ad — wrong on every count.
               When post_id > 0, match (recipient, post_id). When post_id is
               absent (rare; only when shortcode invoked without ad context),
               fall back to the legacy (recipient, NULL) lookup so old
               popup-style chats still resume the right thread. The AJAX
               send handler enforces the same lookup at submit-time, but
               pre-filling correctly here keeps the request payload honest
               for any consumer that inspects it. */
            $resolved_conv = $sbchat_messages->sb_conversation_exists(
                (int) $post_author_id,
                $post_id > 0 ? $post_id : null
            );
            $conversation_id = ($resolved_conv && (int) $resolved_conv > 0) ? (int) $resolved_conv : 0;
        }

        $html = '
        <div class="sbchat-modal-content">
            <span class="sb-chat-close">&times;</span>
            <form action="" class="sbchat-popup-message" method="post">

                <div class="sb-message-box">
                <h3 class="title"> ' . __('Send message to ', 'sb_chat') . $sender_fullname . '</h3>
                     <div class="form-group col-md-12">
                    <textarea type="text" id="message_box" name="message_box" class="form-control" placeholder="' . esc_html__('Write message here', 'sb_chat') . '"></textarea>
                      </div>
                     <div class="form-group col-md-12">
                    <button class="adt-button-dark sbchat-popup-send btn-loading text-light mb-1">
                    ' . esc_html__('Send Message', 'sb_chat') . '
                    <div class="bubbles"> <i class="fa fa-circle"></i> <i class="fa fa-circle"></i> <i class="fa fa-circle"></i> </div>
                    </button>
                    <input type="hidden" id="conversation_id" name="conversation_id" value="' . $conversation_id . '">
                    <input type="hidden" id="recipient_id" name="recipient_id" value="' . $post_author_id . '">
                    <input type="hidden" id="post_id" name="post_id" value="' . $post_id . '">
                    </div>
                </div>
                <div class="msg-body">
                    <ul></ul>
                </div>
            </form>
        </div>
';

        wp_send_json_success(array('html' => $html));
    }

    public function unique_filename_cb($dir, $filename)
    {

        if (isset($this->current_uuid) && !empty($this->current_uuid)) {
            return $this->current_uuid;
        }
    }

    public function sb_send_message_ajax_callback()
    {
        global $wpdb;
        $umid = ((isset($_POST['unique_message_id']) && !empty($_POST['unique_message_id']) && $_POST['unique_message_id'] > 0) ? absint($_POST['unique_message_id']) : 0);

        if (is_array($_POST) && count($_POST) > 0) {
            foreach ($_POST as $post_key => $post_value) {
                if (strpos($post_key, 'sbchat_mu_uuid') !== false) {
                    $uuids[] = $_POST[$post_key];
                }

                if (strpos($post_key, 'sbchat_mu_durl_') !== false) {
                    $durls[] = $_POST[$post_key];
                }
            }
        }

        $user_id = get_current_user_id();
        if (!is_user_logged_in() || $user_id <= 0) {
            wp_send_json_error(array('message' => __('You must be logged in to send this message!', 'sb_chat'), 'umid' => $umid, 'uuids' => $uuids));
        }

        $conversation_id = ((isset($_POST['conversation_id']) && !empty($_POST['conversation_id']) && $_POST['conversation_id'] > 0) ? absint($_POST['conversation_id']) : 0);
        $recipient_id = ((isset($_POST['recipient_id']) && !empty($_POST['recipient_id']) && $_POST['recipient_id'] > 0) ? absint($_POST['recipient_id']) : 0);
        $post_id = ((isset($_POST['post_id']) && !empty($_POST['post_id']) && $_POST['post_id'] > 0) ? absint($_POST['post_id']) : 0);

        if ($recipient_id <= 0) {
            wp_send_json_error(array('message' => __('Conversation is flawed. No known recipient found!', 'sb_chat'), 'umid' => $umid, 'uuids' => $uuids));
        }

        if (get_user_meta($user_id, 'sb_is_user_blocked', true)) {
            wp_send_json_error(array('message' => __('Sorry you can not send message , please contact site admin', 'sb_chat'), 'umid' => $umid, 'uuids' => $uuids));
        }

        $message = ((isset($_POST['message']) && !empty($_POST['message'])) ? sanitize_textarea_field($_POST['message']) : '');

        $message_type = 'text';

        $post_card = '';
        if ($post_id > 0) {
            $post = get_post($post_id);
            if ($post) {
                /* AdForest patch (post-card de-duplication):
                   Only prepend the ad post-card to the FIRST message in an
                   ad-scoped conversation. Repeated Contact-Seller clicks
                   on the same ad with the same seller were prepending a
                   fresh card to every submit, producing a thread that
                   looked like:
                     [card] Hannah Here.
                     [card] Sending a test message.
                   The modern chat header already shows "From <ad>"
                   persistently, so the inline card only needs to appear
                   once at the top of the thread as the "this is what
                   we're talking about" anchor. We check via the same
                   per-ad lookup the routing uses — if a conversation row
                   already exists for (recipient, post_id) AND it has at
                   least one message, the upcoming message is a reply,
                   not a fresh thread start, so we skip the prepend. */
                $existing_conv_for_card = $this->sb_conversation_exists($recipient_id, $post_id);
                $skip_card_prepend = false;
                if ($existing_conv_for_card && (int) $existing_conv_for_card > 0) {
                    $msg_tbl = $wpdb->prefix . 'sb_chat_messages';
                    $has_any_message = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $msg_tbl WHERE conversation_id = %d",
                        (int) $existing_conv_for_card
                    ));
                    if ($has_any_message > 0) {
                        $skip_card_prepend = true;
                    }
                }

                if (!$skip_card_prepend) {
                    $post_title = get_the_title($post_id);
                    $post_permalink = get_permalink($post_id);
                    $image_thumbnail_size = 'adforest-single-post';
                    $media = adforest_get_ad_images($post_id);
                    $img = !empty($media) ? wp_get_attachment_image_src($media[0], $image_thumbnail_size) : null;

                    $first_img = isset($img[0]) ? $img[0] : "";
                    if (empty($first_img)) {
                        $first_img = plugin_dir_url(__FILE__) . 'assets/images/no-image.jpg';
                    }

                    if (!empty($first_img) && !preg_match('/^https?:\/\//', $first_img)) {
                        $first_img = 'https://' . ltrim($first_img, '/');
                    }

                    $post_card_label = esc_html__( 'Related post', 'sb_chat' );
                    $post_card = '<div class="message-post-card test" data-label="' . esc_attr( $post_card_label ) . '">';
                    $post_card .= '<div class="post-card-content">';
                    $post_card .= '<div class="post-card-image">';
                    $post_card .= '<img src="' . esc_url($first_img) . '" alt="' . esc_attr($post_title) . '">';
                    $post_card .= '</div>';
                    $post_card .= '<div class="post-card-info">';
                    $post_card .= '<h4 class="post-card-title"><span>' . esc_html($post_title) . '</span></h4>';
                    $post_card .= '<a href="' . esc_url($post_permalink) . '" class="post-card-link" target="_blank">'.__("View Post", "sb_chat") .'</a>';
                    $post_card .= '</div>';
                    $post_card .= '</div>';
                    $post_card .= '</div>';

                    $message = $post_card . "|" . $message;

                    $message_type = 'post_card';
                }
            }
        }

        error_log("Message Before Upload: " . $message);

        add_filter('upload_dir', array($this, 'sbchat_upload_dir'), 10);

        $file_attachments = $_FILES;
        if (is_array($file_attachments) && count($file_attachments) > 0) {

            $message_type = 'media';

            $allowed_mime_types = SB_Chat::get_plugin_options('sbchat_allowed_mime_types');
            if ((is_array($allowed_mime_types) && count($allowed_mime_types) === 0) || empty($allowed_mime_types)) {
                $allowed_mime_types = array('image/png', 'image/jpg', 'image/jpeg');
            }

            $max_file_upload_size = (int) SB_Chat::get_plugin_options('sb_max_file_size');
            if (!isset($max_file_upload_size) || empty($max_file_upload_size) || $max_file_upload_size == 0) {
                $max_file_upload_size = 1024;
            }

            $max_files_upload = (int) SB_Chat::get_plugin_options('sbchat_max_files_upload');
            if (!isset($max_files_upload) || empty($max_files_upload) || $max_files_upload == 0) {
                $max_files_upload = 7;
            }

            if (count($file_attachments) > $max_files_upload) {
                wp_send_json_error(array('message' => __("Only {$max_files_upload} files can be uploaded at a time.", 'sb_chat'), 'umid' => $umid, 'uuids' => $uuids));
            }

            $post_attachment_ids = array();
            $uploaded_media = array();

            foreach ($file_attachments as $file_key => $file_attachment) {

                $media_type = explode('/', $file_attachment['type'])[0];
                $media_size = $file_attachment['size'] / 1024;
                $media_index = str_replace('sbchat_mu_', '', $file_key);

                $media_ext = explode('.', $file_attachment['name']);
                $media_ext = $media_ext[count($media_ext) - 1];

                $media_name = $uuids[$media_index] . '.' . $media_ext;

                if ($media_type === 'image') {
                    $uploaded_image = null;

                    if (($media_size > $max_file_upload_size) || !in_array($file_attachment['type'], $allowed_mime_types, true)) {
                        $uploaded_image['durl'] = $durls[$media_index];
                        $uploaded_image['uuid'] = $uuids[$media_index];
                        $uploaded_media['images']['invalid'][] = $uploaded_image;
                        continue;
                    }

                    $this->current_uuid = $media_name;

                    $attachment_args = array(
                        'post_author' => $user_id,
                        'post_title' => $media_name,
                    );

                    $post_attachment_id = media_handle_upload($file_key, 0, $attachment_args, array('test_form' => false, 'unique_filename_callback' => array($this, 'unique_filename_cb')));

                    if (is_wp_error($post_attachment_id)) {
                        $uploaded_image['durl'] = $durls[$media_index];
                        $uploaded_image['uuid'] = $uuids[$media_index];
                        $uploaded_media['images']['invalid'][] = $uploaded_image;
                        continue;
                    }

                    $post_attachment_ids[] = $post_attachment_id;

                    $uploaded_image['id'] = $post_attachment_id;
                    $uploaded_image['path'] = get_bloginfo('url') . '/wp-content/plugins/sb_chat/uploads/' . $media_name;
                    $uploaded_image['uuid'] = $uuids[$media_index];
                    $uploaded_media['images']['valid'][] = $uploaded_image;
                }

                if ($media_type === 'application' || $media_type === 'text') {

                    $uploaded_doc = null;

                    if (($media_size > $max_file_upload_size) || !in_array($file_attachment['type'], $allowed_mime_types, true)) {
                        $uploaded_doc['name'] = $file_attachment['name'];
                        $uploaded_doc['ext'] = $media_ext;
                        $uploaded_doc['size'] = $media_size;
                        $uploaded_doc['uuid'] = $uuids[$media_index];
                        $uploaded_media['docs']['invalid'][] = $uploaded_doc;
                        continue;
                    }

                    $this->current_uuid = $media_name;

                    $attachment_args = array(
                        'post_author' => $user_id,
                        'post_title' => $media_name,
                    );

                    $post_attachment_id = media_handle_upload($file_key, 0, $attachment_args, array('test_form' => false, 'unique_filename_callback' => array($this, 'unique_filename_cb')));

                    /*
                    The media_handle_upload() function actually creates an attachment post,
                    and the resizing process happens when wp_generate_attachment_metadata gets called.
                    If you don't call that, then no resizing occurs.
                     */

                    if (is_wp_error($post_attachment_id)) {
                        $uploaded_doc['name'] = $file_attachment['name'];
                        $uploaded_doc['ext'] = $media_ext;
                        $uploaded_doc['size'] = $media_size;
                        $uploaded_doc['uuid'] = $uuids[$media_index];
                        $uploaded_media['docs']['invalid'][] = $uploaded_doc;
                        continue;
                    }

                    $post_attachment_ids[] = $post_attachment_id;

                    $uploaded_doc['id'] = $post_attachment_id;
                    $uploaded_doc['name'] = $file_attachment['name'];
                    $uploaded_doc['path'] = get_bloginfo('url') . '/wp-content/plugins/sb_chat/uploads/' . $media_name;
                    $uploaded_doc['ext'] = $media_ext;
                    $uploaded_doc['size'] = $media_size;
                    $uploaded_doc['uuid'] = $uuids[$media_index];
                    $uploaded_media['docs']['valid'][] = $uploaded_doc;
                }
            }

            $valid_image_uploads = $uploaded_media['images']['valid'];
            $invalid_image_uploads = $uploaded_media['images']['invalid'];

            if (is_array($valid_image_uploads) && count($valid_image_uploads) <= 4) {

                $valid_image_preview = '';
                foreach ($valid_image_uploads as $valid_image_upload) {

                    $img_uuid = esc_attr($valid_image_upload['uuid']);
                    $img_src_full = wp_get_attachment_image_src($valid_image_upload['id'], 'full');
                    $img_src_thumbnail = wp_get_attachment_image_src($valid_image_upload['id'], array(300, 200));

                    $valid_image_preview .= '<li class="message-bubble reply" style="display: none;">';
                    $valid_image_preview .= '<div class="message-media">';
                    $valid_image_preview .= '<a data-fslightbox="fsl_' . $img_uuid . '" href="' . $img_src_full[0] . '">';
                    $valid_image_preview .= '<img src="' . $img_src_thumbnail[0] . '" id="' . $img_uuid . '" />';
                    $valid_image_preview .= '</a>';
                    $valid_image_preview .= '</div>';
                    $valid_image_preview .= '</li>';
                }
            }

            if (is_array($valid_image_uploads) && count($valid_image_uploads) > 4) {

                $valid_image_preview = '';
                $valid_image_preview .= '<li class="message-bubble reply" style="display: none;">';
                $valid_image_preview .= '<div class="message-media">';
                $valid_image_preview .= '<div class="grid-media">';

                $last_image_key = count($valid_image_uploads) - 1;
                $last_image_uuid = esc_attr($valid_image_uploads[$last_image_key]['uuid']);
                $last_image_id = esc_attr($valid_image_uploads[$last_image_key]['id']);

                $last_img_src_full = wp_get_attachment_image_src($last_image_id, 'full');

                $valid_image_index = 0;
                foreach ($valid_image_uploads as $valid_image_upload) {

                    $img_uuid = esc_attr($valid_image_upload['uuid']);

                    $img_src_full = wp_get_attachment_image_src($valid_image_upload['id'], 'full');
                    $img_src_thumbnail = wp_get_attachment_image_src($valid_image_upload['id'], array(300, 200));

                    $valid_image_preview .= '<a data-fslightbox="fsl_' . $last_image_uuid . '" href="' . $img_src_full[0] . '">';
                    $valid_image_preview .= '<img src="' . $img_src_thumbnail[0] . '" id="' . $img_uuid . '" />';
                    $valid_image_preview .= '</a>';

                    if ($valid_image_index === (count($valid_image_uploads) - 1)) {

                        $valid_image_preview .= '<a data-fslightbox="fsl_' . $last_image_uuid . '" href="' . $last_img_src_full[0] . '">';
                        $valid_image_preview .= '<div class="overlay" id="overlay_' . $last_image_uuid . '" />';
                        $valid_image_preview .= '<span class="images-counter">' . (count($valid_image_uploads) - 1) . '+</span>';
                        $valid_image_preview .= '</div>';
                        $valid_image_preview .= '</a>';
                    }

                    $valid_image_index++;
                }

                $valid_image_preview .= '</div>';
                $valid_image_preview .= '</div>';
                $valid_image_preview .= '</li>';
            }

            if (is_array($invalid_image_uploads) && count($invalid_image_uploads) <= 4) {

                $invalid_image_preview = '';
                foreach ($invalid_image_uploads as $invalid_image_upload) {

                    $img_uuid = esc_attr($invalid_image_upload['uuid']);
                    $img_path = esc_attr($invalid_image_upload['durl']);

                    $invalid_image_preview .= '<li class="message-bubble reply" style="display: none;">';
                    $invalid_image_preview .= '<div class="message-media disable-media">';
                    $invalid_image_preview .= '<img src="' . $img_path . '" id="' . $img_uuid . '" />';
                    $invalid_image_preview .= '<span class="error-msg">Couldn\'t upload the image!</span>';
                    $invalid_image_preview .= '<div class="disable-overlay"></div>';
                    $invalid_image_preview .= '</div>';
                    $invalid_image_preview .= '</li>';
                }
            }

            if (is_array($invalid_image_uploads) && count($invalid_image_uploads) > 4) {

                $invalid_image_preview = '';
                $invalid_image_preview .= '<li class="message-bubble reply" style="display: none;">';
                $invalid_image_preview .= '<div class="message-media disable-media">';
                $invalid_image_preview .= '<div class="grid-media">';

                $last_image_key = count($invalid_image_uploads) - 1;
                $last_image_uuid = esc_attr($invalid_image_uploads[$last_image_key]['uuid']);
                $last_image_path = esc_attr($invalid_image_uploads[$last_image_key]['durl']);

                $invalid_image_index = 0;
                foreach ($invalid_image_uploads as $invalid_image_upload) {

                    $img_uuid = esc_attr($invalid_image_upload['uuid']);
                    $img_path = esc_attr($invalid_image_upload['durl']);

                    $invalid_image_preview .= '<img src="' . $img_path . '" id="' . $img_uuid . '" />';

                    if ($invalid_image_index === (count($invalid_image_uploads) - 1)) {
                        $invalid_image_preview .= '<div class="overlay"/>';
                        $invalid_image_preview .= '<span class="images-counter">' . (count($invalid_image_uploads) - 1) . '+</span>';
                        $invalid_image_preview .= '</div>';
                    }

                    $invalid_image_index++;
                }

                $invalid_image_preview .= '</div>';
                $invalid_image_preview .= '<span class="error-msg">Couldn\'t upload the images!</span>';
                $invalid_image_preview .= '<div class="disable-overlay"></div>';
                $invalid_image_preview .= '</div>';
                $invalid_image_preview .= '</li>';
            }

            $valid_doc_uploads = $uploaded_media['docs']['valid'];
            $invalid_doc_uploads = $uploaded_media['docs']['invalid'];

            if (is_array($valid_doc_uploads) && count($valid_doc_uploads) > 0) {

                $valid_doc_preview = '';
                foreach ($valid_doc_uploads as $valid_doc_upload) {

                    $doc_name = esc_attr($valid_doc_upload['name']);
                    $doc_path = esc_attr($valid_doc_upload['path']);
                    $doc_ext = esc_attr($valid_doc_upload['ext']);
                    $doc_size = esc_attr(round($valid_doc_upload['size'], 2));
                    $doc_uuid = esc_attr($valid_doc_upload['uuid']);

                    $valid_doc_preview .= '<li class="message-bubble reply" style="display: none;">';
                    $valid_doc_preview .= '<div class="message-file-main">';
                    $valid_doc_preview .= '<div class="message-file">';
                    $valid_doc_preview .= '<div class="main-left">';
                    $valid_doc_preview .= '<div class="icon">';
                    $valid_doc_preview .= '<img src="' . get_bloginfo('url') . '/wp-content/plugins/sb_chat/assets/images/icons/' . $doc_ext . '-icon.svg' . '" />';
                    $valid_doc_preview .= '</div>';
                    $valid_doc_preview .= '<div class="right-cont">';
                    $valid_doc_preview .= '<span class="title"><a target="_blank" href="' . $doc_path . '">' . $doc_name . '</a></span>';
                    $valid_doc_preview .= '<small class="size">' . $doc_size . 'KB</small>';
                    $valid_doc_preview .= '<span class="type">Uploaded ' . date('Y/m/d') . '</span>';
                    $valid_doc_preview .= '</div>';
                    $valid_doc_preview .= '</div>';
                    $valid_doc_preview .= '</div>';
                    $valid_doc_preview .= '</div>';
                    $valid_doc_preview .= '</li>';
                }
            }

            if (is_array($invalid_doc_uploads) && count($invalid_doc_uploads) > 0) {

                $invalid_doc_preview = '';
                foreach ($invalid_doc_uploads as $invalid_doc_upload) {

                    $doc_name = esc_attr($invalid_doc_upload['name']);
                    $doc_ext = esc_attr($invalid_doc_upload['ext']);
                    $doc_size = esc_attr(round($invalid_doc_upload['size'], 2));
                    $doc_uuid = esc_attr($invalid_doc_upload['uuid']);

                    $invalid_doc_preview .= '<li class="message-bubble reply" style="display: none;">';
                    $invalid_doc_preview .= '<div class="message-file-main disable-file">';
                    $invalid_doc_preview .= '<div class="message-file">';
                    $invalid_doc_preview .= '<div class="main-left">';
                    $invalid_doc_preview .= '<div class="icon">';
                    $invalid_doc_preview .= '<img src="' . get_bloginfo('url') . '/wp-content/plugins/sb_chat/assets/images/icons/' . $doc_ext . '-icon.svg' . '" />';
                    $invalid_doc_preview .= '</div>';
                    $invalid_doc_preview .= '<div class="right-cont">';
                    $invalid_doc_preview .= '<span class="title">' . $doc_name . '</span>';
                    $invalid_doc_preview .= '<small class="size">' . $doc_size . 'KB</small>';
                    $invalid_doc_preview .= '<span class="type">Uploaded ' . date('Y/m/d') . '</span>';
                    $invalid_doc_preview .= '</div>';
                    $invalid_doc_preview .= '</div>';
                    $invalid_doc_preview .= '<div class="main-right">';
                    $invalid_doc_preview .= '<img src="' . get_bloginfo('url') . '/wp-content/plugins/sb_chat/assets/images/icons/error-icon.svg' . '" />';
                    $invalid_doc_preview .= '</div>';
                    $invalid_doc_preview .= '</div>';
                    $invalid_doc_preview .= '<span class="error-msg">Failed to upload file!</span>';
                    $invalid_doc_preview .= '</div>';
                    $invalid_doc_preview .= '</li>';
                }
            }
        }

        remove_filter('upload_dir', array($this, 'sbchat_upload_dir'));

        $conversation['recipient'] = $recipient_id;
        $conversation['message']   = $message;
        if (isset($post_id) && $post_id > 0) {
            $conversation['post_id'] = $post_id;
        }
        error_log("Message Before Conversation: " . $message);

        /* AdForest patch (per-ad conversations):
           Priority order:
           1. If post_id is set (message originates from an ad detail page),
              ALWAYS look up by (recipient, post_id). The chat popup that
              opens from a listing pre-populates conversation_id with the
              existing buyer-owner conversation regardless of which ad —
              trusting it here would collapse all ads onto the first thread.
              When the lookup matches the same conversation that came in via
              POST, we naturally reuse it; otherwise we get a new ad-scoped
              row.
           2. If post_id is NOT set (sending from inside the messages page,
              where the form supplies conversation_id), trust conversation_id
              and skip the recipient lookup. */
        $has_post_id = isset($post_id) && $post_id > 0;
        if ($has_post_id) {
            $conversation_exists = $this->sb_conversation_exists($recipient_id, $post_id);
            if ($conversation_exists > 0 && !empty($conversation_exists)) {
                $conversation_id = $conversation_exists;
            } else {
                $conversation_id = $this->sb_start_conversation($conversation);
            }
        } elseif (!empty($conversation_id) && (int) $conversation_id > 0) {
            // already set from $_POST near the top of this function
        } else {
            // Fallback: legacy lookup with NULL post_id
            $conversation_exists = $this->sb_conversation_exists($recipient_id, null);
            if ($conversation_exists > 0 && !empty($conversation_exists)) {
                $conversation_id = $conversation_exists;
            } else {
                $conversation_id = $this->sb_start_conversation($conversation);
            }
        }

        global $sb_plugin_options;
        $words_fillters = $sb_plugin_options['sb_chat_bad_words_filter'];
        $words = explode(',', $sb_plugin_options['sb_chat_bad_words_filter']);

        // $replace = $sb_plugin_options['sb_chat_bad_words_replace'];
        ///$message = sbChat_badwords_filter($words, $message, $replace);
        //$array1 = explode(',', $words_fillters);
        //$array2 = explode(' ', $message);WW
        //  $matches = array_intersect($array1, $array2);

        if (!empty($conversation_id) && $conversation_id > 0) {
            $new_message['conversation_id'] = $conversation_id;
            $new_message['sender_id'] = $user_id;
            $new_message['receiver_id'] = $recipient_id;
            $new_message['message'] = $message;
            $new_message['attachment_ids'] = (isset($post_attachment_ids) && is_array($post_attachment_ids) && count($post_attachment_ids) > 0) ? implode(',', $post_attachment_ids) : '';
            $new_message['message_type'] = $message_type;
            if ($post_id > 0) {
                $new_message['post_id'] = $post_id;
            }
        }

        error_log("Message Before Send New Message: " . $message);

        $message_sent = $this->sb_send_new_message($new_message);

        $upload_previews  =  "";
        if (isset($valid_image_preview)) {
            $upload_previews = $valid_image_preview . $invalid_image_preview . $valid_doc_preview . $invalid_doc_preview;
        }
        $this->current_uuid = null;

        if ($message_sent) {
            wp_send_json_success(array('message' => esc_html__('Message sent successfully!', 'sb_chat'), 'upload_previews' => $upload_previews));
        }
    }

    /* functions */

    /* AdForest patch (per-ad conversations): match on post_id as well so a
       buyer messaging the same owner about a different ad gets a separate
       conversation row. NULL post_id matches NULL post_id (legacy threads).
       Re-apply this patch if the SB Chat plugin is updated. */
    public function sb_conversation_exists($recipient, $post_id = null)
    {
        $user_id = get_current_user_id();
        $post_id = (is_null($post_id) || $post_id === '' || $post_id <= 0) ? null : (int) $post_id;
        $conversations = $this->sb_get_conversations($user_id);
        foreach ($conversations as $key => $conv) {
            $conv_post = isset($conv->post_id) && !is_null($conv->post_id) && $conv->post_id > 0 ? (int) $conv->post_id : null;
            if ($conv_post !== $post_id) continue;
            if ($user_id == (string) $conv->user_1 && $recipient == (string) $conv->user_2) {
                return $conv->id;
            } elseif ($user_id == $conv->user_2 && $recipient == $conv->user_1) {
                return $conv->id;
            }
        }
        return false;
    }

    /* Get user conversations */
    public function sb_get_conversations($user_id, $limit = '')
    {

        global $wpdb;
        if ($limit != '') {
            $limit = " LIMIT " . esc_sql($limit);
        }

        $result = $wpdb->get_results("
        SELECT * FROM `" . $wpdb->prefix . "sb_chat_conversation`
        WHERE  user_1 = '$user_id' OR user_2 = '$user_id'
        ORDER BY last_update DESC $limit
        ");

        return $result;
    }
    /* Start New conversations */
    /* AdForest patch (per-ad conversations): persist post_id on the new
       conversation row so subsequent messages between the same pair on
       a different ad create a fresh conversation. */
    public function sb_start_conversation($args = 0)
    {

        global $wpdb;

        $read_user_1 = '1';
        $read_user_2 = '0';

        $row     = array(
            'user_1' => get_current_user_id(),
            'user_2' => $args['recipient'],
            'read_user_1' => $read_user_1,
            'read_user_2' => $read_user_2,
        );
        $formats = array('%d', '%d', '%d', '%d');

        if (isset($args['post_id']) && (int) $args['post_id'] > 0) {
            $row['post_id'] = (int) $args['post_id'];
            $formats[]      = '%d';
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'sb_chat_conversation',
            $row,
            $formats
        );

        if (isset($wpdb->insert_id)) {
            $id = $wpdb->insert_id;
        } else {
            $id = false;
        }

        return $id;
    }

    public function sbchat_upload_dir($upload)
    {

        if (!isset($this->current_uuid) || empty($this->current_uuid)) {
            return false;
        }

        if (!file_exists(SBCHAT_UPLOAD_DIR_PATH)) {
            mkdir(SBCHAT_UPLOAD_DIR_PATH, 0777, true);
        }

        $upload['path'] = SBCHAT_UPLOAD_DIR_PATH;
        $upload['url'] = SBCHAT_UPLOAD_DIR_URL;
        $upload['subdir'] = '';
        $upload['basedir'] = SBCHAT_UPLOAD_DIR_PATH;
        $upload['baseurl'] = SBCHAT_UPLOAD_DIR_URL;

        return $upload;
    }

    /* Send New Message */
    public function sb_send_new_message($args = 0)
    {
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'sb_chat_messages', array(
            'conversation_id' => $args['conversation_id'],
            'sender_id' => $args['sender_id'],
            'receiver_id' => $args['receiver_id'],
            'message' => stripslashes_deep($args['message']),
            'created' => current_time('mysql'),
            'attachment_ids' => $args['attachment_ids'],
            'message_type' => $args['message_type'],
        ));

        // echo '</br> wpdb->last_query: '. $wpdb->last_query;
        if (isset($wpdb->insert_id)) {
            $id = $wpdb->insert_id;
            $conversation = $this->sb_get_conversation($args['conversation_id']);
            if ($conversation[0]->user_1 == $args['sender_id']) {
                $user = 'user_2';
            } else {
                $user = 'user_1';
            }
            $this->sb_mark_as_unread($user, $args['conversation_id']);
            $this->sb_mark_as_undeleted($user, $args['conversation_id']);

            $this->sb_converstation_update_date($args['conversation_id']);
        } else {
            $id = false;
        }

        return $id;
    }
    /* Get Conversation */
    public function sb_get_conversation($conversation_id)
    {
        global $wpdb;
        $result = $wpdb->get_results("
        SELECT * FROM `" . $wpdb->prefix . "sb_chat_conversation`
        WHERE  id = '$conversation_id'

        ");
        return $result;
    }
    public function get_single_conversation($user_id, $conversation_id)
    {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $conversation = $this->sb_get_conversation($conversation_id);

        if (empty($conversation)) {
            return;
        }

        $user_type = (is_array($conversation) && $conversation[0]->user_1 == $current_user_id) ? 'user_1' : 'user_2';
        $deleted_at = $user_type == 'user_1' ? $conversation[0]->time_deleted_by_user_1 : $conversation[0]->time_deleted_by_user_2;

        /* AdForest patch: sort by `id ASC` instead of `created ASC`. The
           messages table's `created` column is `ON UPDATE CURRENT_TIMESTAMP`,
           so any row update silently rewrites it — a reply could end up
           displayed above the question it answered. `id` (auto-increment)
           is immutable and strictly reflects insert order. */
        if ($deleted_at != "") {
            $result = $wpdb->get_results("
        SELECT * FROM `" . $wpdb->prefix . "sb_chat_messages`
        WHERE conversation_id = '$conversation_id'
        AND DATE_FORMAT(created, '%Y-%m-%d %H:%i:%s') > '$deleted_at'
        ORDER BY id ASC
    ");
        } else {
            $result = $wpdb->get_results("
          SELECT * FROM `" . $wpdb->prefix . "sb_chat_messages`
          WHERE  conversation_id = '$conversation_id'
          ORDER BY id ASC
        ");
        }
        return $result;
    }
    /* Get users last message */
    public function sb_get_last_message($conversation)
    {

        global $wpdb;

        $result = $wpdb->get_results("
        SELECT * FROM `" . $wpdb->prefix . "sb_chat_messages`
        WHERE  conversation_id = '$conversation'
        ORDER BY created_at DESC LIMIT 1
        ");

        return $result;
    }
    /* Mark as unread */
    public function sb_converstation_update_date($conversation)
    {
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'sb_chat_conversation',
            array('updated' => current_time('mysql')),
            array('id' => $conversation)
        );
        return $result;
    }
    /* Mark as unread */
    public function sb_mark_as_unread($user, $conversation)
    {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'sb_chat_conversation',
            array('read_' . $user => 0, 'notification' => ''),
            array('id' => $conversation)
        );

        return $result;
    }

    public function sb_mark_as_undeleted($user, $conversation)
    {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'sb_chat_conversation',
            array('deleted_by_user_1' => 0, 'deleted_by_user_2' => 0),
            array('id' => $conversation)

        );

        return $result;
    }

    /* Mark as read */
    public function sb_mark_as_read($conversation_id)
    {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $conversation = $this->sb_get_conversation($conversation_id);
        $user_type = (is_array($conversation) && $conversation[0]->user_1 == $current_user_id) ? 'user_1' : 'user_2';
        $wpdb->update(
            $wpdb->prefix . 'sb_chat_conversation',
            array('read_' . $user_type => 1),
            array('id' => $conversation_id)
        );

        // Mark all messages in this conversation as read for the current user (receiver)
        $read_status_updated = $wpdb->update(
            $wpdb->prefix . 'sb_chat_messages',
            array('read_status' => 1),
            array('receiver_id' => $current_user_id, 'conversation_id' => $conversation_id)
        );

        return $read_status_updated;
    }

    public function sb_mark_as_delete($conversation_id)
    {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $date = date_create();
        $conversation_delete_time = date_format($date, "Y-m-d H:i:s");
        $conversation = $this->sb_get_conversation($conversation_id);
        $user_type = (is_array($conversation) && $conversation[0]->user_1 == $current_user_id) ? 'user_1' : 'user_2';
        $delete_status_updated = $wpdb->update(
            $wpdb->prefix . 'sb_chat_conversation',
            array(
                'deleted_by_' . $user_type => 1,
                'time_deleted_by_' . $user_type => $conversation_delete_time,
            ),
            array('id' => $conversation_id)
        );
        return $delete_status_updated;
    }

    /* Check if read */
    public function sb_check_if_read($conversation_data)
    {

        $user_id = get_current_user_id();

        if (isset($conversation_data)) {

            $conversation_id = $conversation_data[0]['id'];

            if ((string) $conversation_data[0]->user_1 == $user_id) {
                $conversation_read = $conversation_data[0]->read_user_1;
            } else {
                $conversation_read = $conversation_data[0]->read_user_2;
            }
        }
    }
    /* Get attachment id */
    public function sb_get_attachment_id($conversation_id)
    {
        global $wpdb;
        $result = $wpdb->get_results("
        SELECT attachment_ids FROM `" . $wpdb->prefix . "sb_chat_messages`
        WHERE  id = '$conversation_id'
        ");
        return $result;
    }

    /* Notification ajax */
    public function sb_notification_ajax($only_count = '')
    {
        global $wpdb;
        $current_user_id = $user_id = get_current_user_id();

        $table = $wpdb->prefix . "sb_chat_messages";
        $conv_table = $wpdb->prefix . "sb_chat_conversation";
        $Sb_Chat_Messages = new Sb_Chat_Messages();
        $conv_id = (isset($_POST['conv_id']) ? $_POST['conv_id'] : '');

        $marked_as_read = $Sb_Chat_Messages->sb_mark_as_read($conv_id);
        // $query = "SELECT message,attachment_ids,sender_id FROM $table WHERE conversation_id = '$conv_id' ORDER BY ID DESC LIMIT 10";
        // $results = $wpdb->get_results($query);
        $query = $wpdb->prepare(
            "SELECT message, attachment_ids, sender_id FROM $table WHERE conversation_id = %s ORDER BY ID DESC LIMIT 10",
            $conv_id
        );
        $results = $wpdb->get_results($query);




        $html = $chat_list = $msg_footer = $attachment_con = $msg_head = "";
        if ($results) {
            // foreach (array_reverse($results) as $key => $result) {
            //     $messages = $result->message;
            //     $attachment = $result->attachment_ids;
            //     $img_atts = wp_get_attachment_image_src($attachment);
            //     if ($messages) {
            //         $class_for_message = ($result->sender_id == $current_user_id) ? 'reply' : 'sender';
            //         $attachment_con .= '<li class="attachment message-bubble ' . esc_attr($class_for_message) . ' dw-att" data-id="' . esc_attr($attachment_con) . '">
            //                                     <div class="message-text"><p><a target="_blank" href="' . esc_url($img_atts['0']) . '"><img src="' . esc_url($img_atts['0']) . '"></p></a></div>
            //                                     </li>';
            //         if (isset($attachment) && $attachment != 0 && $attachment != '') {
            //             $attachment_con = $attachment_con;
            //         } else {
            //             $attachment_con = '';
            //         }
            //         $html .= '<li class="message-bubble ' . esc_attr($class_for_message) . '">
            //                         <div class="message-text"><p>' . $messages . '</p></div>
            //                        </li>
            //                        ' . $attachment_con . '';
            //     } else {
            //         $html .= '<h4 class="not-found">' . esc_html('No Message found.', 'sb_chat') . '</h4>';
            //     }

            // }


            $html  =  sbchat_get_inbox_conversations($current_user_id, $conv_id);

            $user_conversations = sbchat_get_conversations_by_user_id($current_user_id);

            foreach ($user_conversations as $user_conversation) {
                $recipient_id = ($user_id == $user_conversation['user_2']) ? absint($user_conversation['user_1']) : absint($user_conversation['user_2']);
                $user_key   = ($user_id == $user_conversation['user_1']) ? 'user_1'  : 'user_2';
                $chat_delete_key   =  ($user_key == 'user_1') ? 'deleted_by_user_1'  : 'deleted_by_user_2';
                if (isset($user_conversation[$chat_delete_key]) && $user_conversation[$chat_delete_key]  == 1) {
                    continue;
                }
                $recipient = get_userdata($recipient_id);
                $recipient_output = '';

                if (! is_wp_error($recipient)) {

                    $recipient_nicename = esc_html($recipient->display_name);
                    $recipient_fullname = esc_html($recipient->first_name) . ' ' . esc_html($recipient->last_name);

                    $recipient_output = $recipient_nicename;
                    if ($recipient_nicename == "")
                        $recipient_output = $recipient_fullname;
                } else {

                    $recipient_output = __('User has been removed', ' sb_chat');
                }
                // $last_conversation_message = sbchat_get_last_conversation_message( $user_conversation['id'] );
                $is_conversation_read = sbchat_get_conversation_status_check($user_conversation, $user_id);
                $last_message_sent_ago = (string) human_time_diff(strtotime($user_conversation['updated']), current_time('timestamp', 1));
                $dashboard_page =  get_option('sb_plugin_options');
                $dashboard_page  =  isset($dashboard_page['sb-dashboard-page']) ? get_the_permalink($dashboard_page['sb-dashboard-page']) : home_url();
                $conversation_url =   $dashboard_page . '?action=view&conversation_id=' . $user_conversation['id'];
                $conversation_id  =  $user_conversation['id'];
                $message_lists_url = "";
                $unread  =  $is_conversation_read ?  ""  :  "unread";
                $last_message_sent_ago = (string) human_time_diff(strtotime($user_conversation['updated']), current_time('timestamp', 1));


                $chat_list .= '<li class="' . $unread . '" data-id = "' . $conversation_id . '"><a target="_self"  data-recipient_id =  "' . $recipient_id . '" data-conv="' . $conversation_id . '" href="' . esc_url($message_lists_url) . '" class="d-flex align-items-center con-chat-list">
                                 <div class="flex-shrink-0 sb-avatar">' . get_avatar($recipient_id, 45) . '</div>
                                    <div class="flex-grow-1 ms-1"><h3 class="sender-details">' . esc_html($recipient_output) . '</h3><p>' . $last_message_sent_ago . ' ago' . '</p></div>
                                       </a></li>';
            }
            $this_conv = $Sb_Chat_Messages->sb_get_conversation($conv_id);
            $user_1 = isset($this_conv[0]->user_1) ? $this_conv[0]->user_1 : '';
            $user_2 = isset($this_conv[0]->user_2) ? $this_conv[0]->user_2 : '';
            if ($current_user_id == (int) $user_1 || $current_user_id == (int) $user_2) {
                // set who is opponent on that conversation
                $opponent = ($this_conv[0]->user_1 == $current_user_id) ? $this_conv[0]->user_2 : $this_conv[0]->user_1;
                $recipient = get_userdata($opponent);
                if (!$recipient) {
                    $name = esc_html__('User has been removed', 'sb_chat');
                } else {

                    $name = $recipient->display_name;
                    if ($name == "") {
                        $name = $recipient->first_name . ' ' . $recipient->last_name;
                    }
                }
                $pro_img_id = $Sb_Chat_Messages->sb_get_attachment_id($conv_id);
                $pro_img_ids = $pro_img_id[0]->attachment_ids;

                if (isset($pro_img_ids[0]) && $pro_img_ids[0] != '') {
                    //$atatchment_arr = explode( ',', $pro_img_ids );
                    $atatchment_arr = $pro_img_ids[0];
                    foreach ($atatchment_arr as $value) {
                        $icon = get_icon_for_attachments($value);

                        $filename = basename(wp_get_attachment_url($value));
                    }
                }
                if (get_option('sb_plugin_options') !== false)
                    $plugin_options = get_option('sb_plugin_options');

                if (is_array($plugin_options) && count($plugin_options) > 0) {

                    $allowed_mime_types = $plugin_options['sbchat_allowed_mime_types'];
                    $max_file_size = $plugin_options['sb_max_file_size'];
                    $max_files_upload = $plugin_options['sbchat_max_files_upload'];

                    $allowed_mime_types = (is_array($allowed_mime_types) && count($allowed_mime_types) > 0) ? implode(',', $allowed_mime_types) : '';
                    $max_file_size = (! empty($max_file_size) && $max_file_size > 0) ? absint($max_file_size / 1024) : 1;
                    $max_files_upload = (! empty($max_files_upload) && $max_files_upload > 0) ? absint($max_files_upload) : 7;
                }
                $seller_id = get_current_user_id();
                $recipient_avatar = sbchat_get_user_avatar($recipient_id, 45);
                error_log("recipient Avatar 1222212121212: " . $recipient_avatar);
                $msg_head .= '<div class="row">
                <div class="col-7">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 sb-avatar head">
                            ' . $recipient_avatar . '
                        </div>
                        <div class="flex-grow-1 ms-1">
                            <h3>' . esc_html($name) . '</h3>
                        </div>
                    </div>
                </div>
                <div class="col-5">
                  <nav class="sb-menu menu-caret submenu-top-border submenu-scale">
                    <ul class="moreoption">
                        <li class="navbar nav-item dropdown dropstart">
                        
                        <div class="button-container">';




                $msg_head .= '<button class="delete-single-chat main-btn primary-btn square-btn btn-hover" data-delete="' . esc_attr__('Are you sure you want to remove this?', 'sb_chat') . '" href="#">' . esc_html__('Delete', 'sb_chat') . '</button>
                        </div>
                        <div class="sb-notification success"><p>' . esc_html('Conversation was removed', 'sb_chat') . '</p></div>
                          </li>
                         </ul>
                         </nav>
                   </div>
                  </div>';
                $msg_footer .= '<form action="" class="send-message">
                                            <div class="d-flex">
                                                <input type="text" id="message_box" class="form-control message-details" aria-label="message…" placeholder="' . esc_attr('Write message…', 'sb_chat') . '">
                                                <button class="btn btn-theme btn-icon send-btn text-light mb-1" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i>' . esc_html('Send', 'sb_chat') . '</button>
                                                <input type="hidden" id="conversation_id" name="conversation_id" value="' . esc_attr($conv_id) . '">
                                                <input type="hidden" id="recipient_id" name="recipient_id" value="' . esc_attr($opponent) . '">
                                            </div>
                                            <div id="sbchat-mu" class="sbchat_upload_items">'. __("Add Attachments", "sb_chat").'</div>
                                            <div class="dropzone-settings" style="display: none;">

                                            <input type="hidden" id="dz_max_file_size" value="' . esc_attr($max_file_size) . '" />
                                            <input type="hidden" id="dz_max_files_upload" value="' . esc_attr($max_files_upload) . '" />
                                            <input type="hidden" id="dz_allowed_mime_types" value="' . esc_attr($allowed_mime_types) . '" />

                                            </div>
                                           

                                        </form>';
            }
            $newurl = add_query_arg(array('action' => 'view', 'conv_id' => $conv_id), esc_url(get_permalink($dashboard_page)));
            $return = array(
                'result' => $html,
                'chat' => $chat_list,
                'head' => $msg_head,
                'footer' => $msg_footer,
                'url' => $newurl,
                'conversation_id' => $conversation_id,
                'recipient_id' => $recipient_id
            );
            wp_send_json_success($return);
        } else {
            $no_message = '<h2 class="no-message"> ' . esc_html__('No Messages Found', 'sb_chat') . '</h2>';
            $return = array('message' => $no_message);
            wp_send_json_error($return);
        }
    }
    /* Delete Conversation */
    public function sb_delete_chat()
    {
        $conv_id = (isset($_POST['conv_id']) ? $_POST['conv_id'] : '');

        if ($conv_id) {
            $delete = $this->sb_delete_conversations($conv_id);
            if ($delete) {
                $return = array('message' => __('Conversation is deleted', 'sb_chat'), 'result' => 'Conversation was removed');
                wp_send_json_success($return);
            } else {
                $return = array('result' => 'Conversation can not be deleted');
                wp_send_json_error($return);
            }
        }
    }
    /* Delete Conversation*/
    public function sb_delete_conversations($conv_id)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $conversation = $this->sb_get_conversation($conv_id);
        if ($conversation) {

            $result = $wpdb->delete($wpdb->prefix . 'sb_chat_conversation', array('id' => $conv_id));
            $wpdb->delete($wpdb->prefix . 'sb_chat_messages', array('conversation_id' => $conv_id));
            return true;
        } else {
            return false;
        }

        return false;
    }

    /* Attachement upload*/
    public function sb_upload_attachments()
    {
        global $sb_plugin_options;
        $conv_id = $_POST['post-id'];
        $field_name = $_FILES['upload_attachment'];
        $attachment_size = isset($sb_plugin_options['sb_max_file_size']) ? $sb_plugin_options['sb_max_file_size'] : '600';
        if (!empty($field_name)) {

            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $files = $field_name;

            $attachment_ids = array();
            $attachment_idss = '';
            $data = '';
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = array(
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key],
                    );

                    $_FILES = array("sb_chat_attachment" => $file);

                    // Allow certain file formats
                    $tmp = explode('.', $file['name']);
                    $imageFileType = end($tmp);
                    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "pptx" && $imageFileType != "pdf" && $imageFileType != "doc" && $imageFileType != "docx" && $imageFileType != "ppt" && $imageFileType != "xls" && $imageFileType != "xlsx" && $imageFileType != "zip") {
                        echo '0|' . esc_html__("Sorry, only JPG, JPEG, PNG, docx, pptx, xlsx, pdf and zip files are allowed.", 'ab_chat');
                        die();
                    }

                    foreach ($_FILES as $file => $array) {
                        if ($array['size'] / 1000 > $attachment_size) {
                            echo '0|' . esc_html__("Max allowd attachment size is " . $attachment_size . ' Kb', 'ab_chat');
                            die();
                            break;
                        }

                        $attach_id = media_handle_upload($file, $conv_id);
                        $attachment_ids[] = $attach_id;

                        $icon = get_icon_for_attachments($attach_id);
                        $data .= '<div class="attachments pro-atta-' . $attach_id . '"> <img src="' . $icon . '" alt=""><span class="attachment-data"> <h4>' . get_the_title($attach_id) . ' </h4> <p> file size: ' . size_format(filesize(get_attached_file($attach_id))) . '</p> <a href="javascript:void(0)" class="sb-attach-delete" data-id="' . $attach_id . '" data-pid="' . $conv_id . '"> <i class="far fa-times-circle"></i></a> </span></div>';
                    }
                }
            }

            $attachment_idss = array_filter($attachment_ids);
            $attachment_idss = implode(',', $attachment_idss);
        }
        //if($exist_data_count < $condition_img)
        //{
        echo '1|' . esc_html__("Attachments uploaded", 'ab_chat') . '|' . $data . '|' . $attachment_idss;
        die;
        //}

    }
    /*DELETE GENERAL ATATCHMENT IDS*/
    public function delete_sb_attachment()
    {
        $attachment_id = $_POST['attach_id'];
        $pid = $_POST['pid'];
        $exist_data = $_POST['ex_values'];
        if ($attachment_id != '' && $pid != '') {
            wp_delete_attachment($attachment_id);
            $return = array('message' => esc_html__('Attachment deleted', 'sb_chat'));
            wp_send_json_success($return);
        } else {
            $return = array('message' => esc_html__('Error!!! attachment is not deleted', 'sb_chat'));
            wp_send_json_error($return);
        }
    }
    public function sb_read_message()
    {

        $conversation_id = (isset($_POST['conversation_id']) && !empty($_POST['conversation_id']) && $_POST['conversation_id'] > 0) ? esc_html($_POST['conversation_id']) : null;
        if ($conversation_id > 0) {

            $sbchat_messages = new Sb_Chat_Messages();
            $marked_as_read = $sbchat_messages->sb_mark_as_read($conversation_id);

            if ($marked_as_read !== false) {
                $response = array('conversation_id' => $conversation_id);
                wp_send_json_success($response);
            }
        }
    }
    public function sb_custom_delete_chat()
    {

        $conversation_id = (isset($_POST['conv_id']) && !empty($_POST['conv_id']) && $_POST['conv_id'] > 0) ? esc_html($_POST['conv_id']) : null;
        $from_admin = isset($_POST['from_admin']) ? $_POST['from_admin'] : "";

        if ($conversation_id > 0) {

            /*Delete chat permanently */
            if ($from_admin == "yes") {
                $sbchat_messages = new Sb_Chat_Messages();
                $marked_as_deleted = $sbchat_messages->sb_delete_chat($conversation_id);
            } else {
                $sbchat_messages = new Sb_Chat_Messages();
                $marked_as_deleted = $sbchat_messages->sb_mark_as_delete($conversation_id);
            }

            if ($marked_as_deleted !== false) {
                $dashboard_page = get_option('sb_plugin_options');
                $dashboard_page = isset($dashboard_page['sb-dashboard-page']) ? get_the_permalink($dashboard_page['sb-dashboard-page']) : home_url();

                $response = array('conversation_id' => $conversation_id, 'url' => $dashboard_page);
                wp_send_json_success($response);
            }
        }
    }

    public function sb_custom_block_user()
    {
        check_ajax_referer('my-ajax-nonce', 'security');
        // Get the user ID from the AJAX request.
        $user_id = $_POST['user_id'];
        // Update the user meta.
        $block_status = isset($_POST['block_status']) ? $_POST['block_status'] : 0;
        if ($block_status == 1) {
            delete_user_meta($user_id, 'sb_is_user_blocked', true);
            wp_send_json_success(array('message' => 'User has been unblocked'));
        }
        update_user_meta($user_id, 'sb_is_user_blocked', true);
        wp_send_json_success(array('message' => 'User has been blocked'));
    }
}

$Sb_Chat_Messages = new Sb_Chat_Messages();
if (!function_exists('sbChat_return')) {

    function sbChat_return($data = '')
    {
        return $data;
    }
}

// Bad word filter
if (!function_exists('sbChat_badwords_filter')) {
    function sbChat_badwords_filter($words = array(), $string = '', $replacement = '')
    {
        if (is_admin() && !wp_doing_ajax()) {
            foreach ($words as $word) {
                $string = preg_replace("/\b$word\b/", "<span>$word</span>", $string);
            }
        } else {
            if (is_array($words)) {
                foreach ($words as $word) {
                    $string = str_replace($word, $replacement, $string);
                }
            }
        }
        return $string;
    }
}
if (!function_exists('sbChat_globalVal')) {
    function sbChat_globalVal($key = '', $else = '')
    {
        if ($key != "") {
            if (isset($GLOBALS["sb_plugin_options"]["$key"]) && $GLOBALS["sb_plugin_options"]["$key"] != "") {
                return $GLOBALS["sb_plugin_options"]["$key"];
            } else if (isset($else) && $else != "") {
                return $else;
            } else {
                return '';
            }
        } else {
            return $GLOBALS["sb_plugin_options"];
        }
    }
}
if (!function_exists('get_icon_for_attachments')) {
    function get_icon_for_attachments($post_id, $size = '')
    {
        $base = get_template_directory_uri() . "/images/dashboard/";
        $type = get_post_mime_type($post_id);
        $img = wp_get_attachment_image_src($post_id, $size);
        switch ($type) {
            case 'application/pdf':
                return $base . "pdf.png";
                break;
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return $base . "doc.png";
                break;
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                return $base . "ppt.png";
                break;
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return $base . "xls.png";
                break;
            case 'application/zip':
                return $base . "zip.png";
                break;
            case 'image/png':
            case 'image/jpg':
            case 'image/jpeg':
                return $img[0];
                break;
            default:
                return $base . "file.png";
        }
    }
}