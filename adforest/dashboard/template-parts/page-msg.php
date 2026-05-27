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
                                                                                        // Bumped from 7 → 50 to match the modern page-messages.php
                                                                                        // prefetch window. With a small SQL LIMIT, the deleted_by_user_X
                                                                                        // soft-delete filter applied below could trim the list to almost
                                                                                        // nothing — users would see far fewer rows here than on the
                                                                                        // modern board even though the underlying data is identical.
                                                                                        $display_limit = 50;
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
                                                                                            // SB Chat's admin-custom.js polls inbox_reload_incoming_messages
                                                                                            // every notification_time ms and OVERWRITES .chat-list-detail
                                                                                            // with the AJAX response. The AJAX limit comes from
                                                                                            // $('.load-conversations').attr('data-offset') — when that
                                                                                            // element is absent, the JS falls back to 7. Render the
                                                                                            // button unconditionally with data-offset matching our PHP
                                                                                            // prefetch so the AJAX re-render uses the same window;
                                                                                            // hide it visually until the conversation count actually
                                                                                            // exceeds the limit.
                                                                                            $show_load_more = (count($user_conversations) > $display_limit); ?>
                                        <button type='button' class='btn btn-primary load-conversations<?php echo $show_load_more ? '' : ' d-none'; ?>'
                                            data-limit="<?php echo esc_attr($display_limit) ?>"
                                            data-offset="<?php echo esc_attr($display_limit) ?>"<?php echo $show_load_more ? '' : ' aria-hidden="true" tabindex="-1"'; ?>>
                                            <?php echo esc_html__("Load more conversations", "adforest"); ?>
                                        </button><?php

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
                                                    // Plugin's attachment renderer (sbchat_generate_inbox_img_attachments_html)
                                                    // does not null-check wp_get_attachment_image_src(), so stale attachment
                                                    // rows leak Xdebug warning HTML directly into the messages-list, hiding
                                                    // the actual conversation. Lower the error filter for the call and
                                                    // discard any stray output captured by ob_start so the real messages
                                                    // render cleanly. Same pattern used by the modern page-messages.php.
                                                    $inbox_conversations = '';
                                                    if (function_exists('sbchat_get_inbox_conversations')) {
                                                        $__prev = error_reporting();
                                                        error_reporting($__prev & ~E_WARNING & ~E_NOTICE & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_STRICT);
                                                        ob_start();
                                                        $inbox_conversations = sbchat_get_inbox_conversations($user_id, $conversation_id);
                                                        $__stray = ob_get_clean();
                                                        error_reporting($__prev);
                                                        unset($__stray);
                                                    }
                                                    if (!empty($inbox_conversations) && function_exists('sbchat_inbox_conversations_allowed_html')) {
                                                        echo wp_kses($inbox_conversations, sbchat_inbox_conversations_allowed_html());
                                                    }
                                                    ?>
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
} ?>

<?php
/* ----------------------------------------------------------------------
 * Voice-note player (dashboard / user-side messages)
 *
 * The modern owner-side template `page-messages.php` ships its own inline
 * voice-player JS scoped to a `.adf-chat` shell. This dashboard template
 * doesn't have that shell, so voice-marker bubbles render as raw text
 * (`[adf-voice]URL[/adf-voice]`) and never become a player on the user
 * side. The block below mirrors the owner-side behavior with selectors
 * that don't require the modern shell — works for sent + received
 * bubbles, legacy markers, AJAX updates, and DOM mutations.
 * --------------------------------------------------------------------*/
?>
<style id="adf-voice-player-styles">
.messages-list li.message-bubble.has-voice .message-text,
.messages-list li.message-bubble.has-voice > p{padding:0 !important;background:transparent !important;border:0 !important;box-shadow:none !important;max-width:none;}
/* Pre-decoration FOUC guard — hide the raw `[adf-voice]URL|DUR[/adf-voice]`
   marker until the JS decorator adds `.has-voice` and swaps in the player. */
.messages-list li.message-bubble.has-voice-pending:not(.has-voice) .message-text p,
.messages-list li.message-bubble.has-voice-pending:not(.has-voice) > p{visibility:hidden;}
.messages-list .adf-voice{
    display:inline-flex;align-items:center;gap:8px;
    padding:5px 10px 5px 5px;border-radius:999px;
    background:#f1f3f6;min-width:200px;max-width:224px;
    color:#1f2937;line-height:1;
    --voice-wave:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 18'%3E%3Crect x='0' y='5' width='2' height='8' rx='1' fill='%23000'/%3E%3Crect x='4' y='3' width='2' height='12' rx='1' fill='%23000'/%3E%3Crect x='8' y='6' width='2' height='6' rx='1' fill='%23000'/%3E%3Crect x='12' y='3.5' width='2' height='11' rx='1' fill='%23000'/%3E%3Crect x='16' y='4.5' width='2' height='9' rx='1' fill='%23000'/%3E%3Crect x='20' y='6' width='2' height='6' rx='1' fill='%23000'/%3E%3Crect x='24' y='4' width='2' height='10' rx='1' fill='%23000'/%3E%3Crect x='28' y='5' width='2' height='8' rx='1' fill='%23000'/%3E%3C/svg%3E");
}
.messages-list li.message-bubble.reply.has-voice .adf-voice{background:#cfd7e6;}
.messages-list .adf-voice__play{
    position:relative;width:32px;height:32px;border-radius:50%;border:0;
    background:#1f2937;color:#fff;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;line-height:1;
    font-family:"Font Awesome 6 Free","Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:0;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 1px 2px rgba(15,23,42,.16);
    transition:background-color .18s ease,transform .12s ease,box-shadow .18s ease;
}
.messages-list .adf-voice__play:hover{background:#0f172a;}
.messages-list .adf-voice__play:active{transform:scale(.96);}
.messages-list .adf-voice__play i{display:none;}
.messages-list .adf-voice__play::before{content:"\f04b";font-family:"Font Awesome 6 Free","Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:10px;line-height:1;color:#fff;margin-left:2px;}
.messages-list .adf-voice__play.is-playing::before{content:"\f04c";font-size:10px;margin-left:0;}
.messages-list .adf-voice__track{flex:1;height:20px;cursor:pointer;position:relative;min-width:96px;display:flex;align-items:center;}
.messages-list .adf-voice__track::before{
    content:"";position:absolute;left:0;right:0;top:0;bottom:0;
    background:rgba(15,23,42,.32);
    -webkit-mask-image:var(--voice-wave);mask-image:var(--voice-wave);
    -webkit-mask-repeat:repeat-x;mask-repeat:repeat-x;
    -webkit-mask-position:0 center;mask-position:0 center;
    -webkit-mask-size:32px 18px;mask-size:32px 18px;
}
.messages-list li.message-bubble.reply.has-voice .adf-voice__track::before{background:rgba(15,23,42,.40);}
.messages-list .adf-voice__bar{
    position:absolute;left:0;top:0;bottom:0;width:0%;
    background:#0f172a;
    -webkit-mask-image:var(--voice-wave);mask-image:var(--voice-wave);
    -webkit-mask-repeat:repeat-x;mask-repeat:repeat-x;
    -webkit-mask-position:0 center;mask-position:0 center;
    -webkit-mask-size:32px 18px;mask-size:32px 18px;
    transition:width .2s cubic-bezier(.4,0,.2,1);will-change:width;
}
.messages-list .adf-voice__time{
    font-size:10px;font-weight:500;color:#64748b;
    flex-shrink:0;font-feature-settings:"tnum" 1;font-variant-numeric:tabular-nums;
    min-width:28px;text-align:right;letter-spacing:.02em;align-self:center;
}
.messages-list .adf-voice__src{display:none;}
@media (prefers-reduced-motion: reduce){
    .messages-list .adf-voice__bar{transition:none;}
}
</style>

<script id="adf-voice-player-script">
(function() {
    if (typeof jQuery === 'undefined') return;
    jQuery(function($) {
        // Prevent double-init if the modern shell loaded its own player on the same page.
        if (window.__adfVoicePlayerInit) return;
        window.__adfVoicePlayerInit = true;

        var $doc = $(document);

        function fmt(t) {
            if (!isFinite(t) || t < 0) t = 0;
            var s = Math.floor(t), m = Math.floor(s / 60);
            return m + ':' + ((s % 60) < 10 ? '0' + (s % 60) : (s % 60));
        }

        // Cache direct DOM references on the audio element so refreshUI
        // doesn't have to walk the DOM with closest()/find() on every rAF
        // tick. This also removes any ambiguity about which player wrapper
        // a given audio belongs to — sender-side and reply-side bubbles
        // share the same internal structure but the cached refs lock onto
        // the exact nodes that were inserted alongside this audio.
        function attachRefs(audio) {
            if (audio.__adfPlayer) return;
            var player = audio.closest ? audio.closest('.adf-voice') : null;
            if (!player) {
                // Pre-IE-removal fallback — walk parents manually.
                var n = audio.parentNode;
                while (n && n.nodeType === 1 && !(n.classList && n.classList.contains('adf-voice'))) n = n.parentNode;
                player = (n && n.nodeType === 1) ? n : null;
            }
            if (!player) return;
            audio.__adfPlayer = player;
            audio.__adfBar    = player.querySelector('.adf-voice__bar');
            audio.__adfTime   = player.querySelector('.adf-voice__time');
            audio.__adfBtn    = player.querySelector('.adf-voice__play');
        }

        function refreshUI(audio) {
            attachRefs(audio);
            var player = audio.__adfPlayer;
            if (!player) return;
            var playing = !audio.paused && !audio.ended;
            if (audio.__adfBtn) audio.__adfBtn.classList.toggle('is-playing', playing);
            player.classList.toggle('is-active', playing || (audio.currentTime > 0 && !audio.ended));
            var fixed = parseFloat(player.getAttribute('data-duration')) || 0;
            var dur = fixed > 0 ? fixed : ((isFinite(audio.duration) && audio.duration > 0) ? audio.duration : 0);
            // Clamp currentTime to a sane range — defensive against any stale
            // stranded values from prior duration-resolution attempts.
            var ct = audio.currentTime;
            if (!isFinite(ct) || ct < 0) ct = 0;
            if (dur > 0 && ct > dur) ct = dur;
            else if (dur === 0 && ct > 7200) ct = 0;
            var pct = dur > 0 ? Math.min(100, Math.max(0, (ct / dur) * 100)) : 0;
            if (audio.__adfBar) audio.__adfBar.style.width = pct + '%';
            // While playing, always show ct so the timer ticks even when
            // duration is unresolved. Only fall back to dur when paused at
            // the very start (idle state shows total length).
            var t = (audio.paused && ct === 0) ? dur : ct;
            if (audio.__adfTime) audio.__adfTime.textContent = fmt(t);
            // One-shot diagnostic per audio — easy to grep in dev console
            // if anything ever feels off again. Logs the resolved nodes the
            // FIRST time refreshUI runs for this audio element only.
            if (!audio.__adfLogged && window.console && console.log) {
                audio.__adfLogged = 1;
                try {
                    console.log('[adf-voice user-side] bound', {
                        audio: audio,
                        player: player,
                        bar: audio.__adfBar,
                        time: audio.__adfTime,
                        btn: audio.__adfBtn,
                        bubbleClass: (player.closest && player.closest('.message-bubble') ? player.closest('.message-bubble').className : '?')
                    });
                } catch (e) {}
            }
        }

        // HTML5 media events do NOT bubble — bind directly. Idempotent.
        // We also drive a requestAnimationFrame loop while the audio is
        // actually playing so the timer + waveform progress refresh smoothly
        // even when the browser is stingy with `timeupdate` events on the
        // user-side dashboard view (observed: timeupdate intermittently
        // throttled to <1Hz on inactive tabs / certain WebM streams).
        function startTick(audio) {
            if (audio.__adfTick) return;
            var raf = window.requestAnimationFrame || function(cb){ return setTimeout(cb, 1000/30); };
            var loop = function() {
                if (audio.paused || audio.ended) {
                    audio.__adfTick = 0;
                    return;
                }
                refreshUI(audio);
                audio.__adfTick = raf(loop);
            };
            audio.__adfTick = raf(loop);
        }
        function stopTick(audio) {
            if (!audio.__adfTick) return;
            var caf = window.cancelAnimationFrame || clearTimeout;
            try { caf(audio.__adfTick); } catch (e) {}
            audio.__adfTick = 0;
        }
        function bindAudio(audio) {
            if (audio.getAttribute('data-adf-bound')) return;
            audio.setAttribute('data-adf-bound', '1');
            attachRefs(audio);
            audio.addEventListener('play',           function(){ refreshUI(audio); startTick(audio); });
            audio.addEventListener('playing',        function(){ refreshUI(audio); startTick(audio); });
            audio.addEventListener('pause',          function(){ stopTick(audio); refreshUI(audio); });
            audio.addEventListener('timeupdate',     function(){ refreshUI(audio); });
            audio.addEventListener('durationchange', function(){ refreshUI(audio); });
            audio.addEventListener('loadedmetadata', function(){ fixDuration(audio); refreshUI(audio); });
            audio.addEventListener('loadeddata',     function(){ fixDuration(audio); refreshUI(audio); });
            audio.addEventListener('canplay',        function(){ fixDuration(audio); refreshUI(audio); });
            audio.addEventListener('ended', function() {
                stopTick(audio);
                try { audio.currentTime = 0; } catch (e) {}
                if (audio.__adfPlayer) audio.__adfPlayer.classList.remove('is-active');
                if (audio.__adfBtn)    audio.__adfBtn.classList.remove('is-playing');
                if (audio.__adfBar)    audio.__adfBar.style.width = '0%';
                refreshUI(audio);
            });
        }

        // Resolve duration without the destructive `currentTime = 1e10`
        // seek hack — that hack is fragile on the user-side dashboard view
        // (observed: leaves the underlying media element in a state where
        // currentTime stops advancing during playback even though audio
        // output continues, so the timer + waveform stay frozen). We try
        // the cheap paths first (persisted attr, finite audio.duration) and
        // fall back to a Web Audio decode of the file for legacy markers
        // that lack a |DURATION pair. No mutation of the audio element.
        function fixDuration(audio) {
            if (audio.getAttribute('data-adf-init')) return;
            attachRefs(audio);
            var player = audio.__adfPlayer;
            if (!player) return;
            var persisted = parseFloat(player.getAttribute('data-duration')) || 0;
            if (persisted > 0) { audio.setAttribute('data-adf-init','1'); refreshUI(audio); return; }
            var d = audio.duration;
            if (isFinite(d) && d > 0) { audio.setAttribute('data-adf-init','1'); refreshUI(audio); return; }
            audio.setAttribute('data-adf-init', '1');
            loadDurationViaWebAudio(audio, $(player));
        }

        // Web Audio decode fallback for legacy markers without |DURATION.
        function loadDurationViaWebAudio(audio, $player) {
            if (audio.getAttribute('data-adf-wa')) return;
            audio.setAttribute('data-adf-wa', '1');
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC || typeof fetch !== 'function') return;
            var src = audio.currentSrc || audio.src;
            if (!src) return;
            fetch(src, { credentials: 'same-origin' })
                .then(function(r){ return (r && r.ok) ? r.arrayBuffer() : null; })
                .then(function(buf){
                    if (!buf) return;
                    var ctx = new AC();
                    return ctx.decodeAudioData(buf).then(function(decoded){
                        if (ctx.close) try { ctx.close(); } catch(e){}
                        var d = decoded && decoded.duration;
                        if (isFinite(d) && d > 0) {
                            $player.attr('data-duration', d);
                            if (audio.paused && audio.currentTime === 0) {
                                $player.find('.adf-voice__time').text(fmt(d));
                            }
                            refreshUI(audio);
                        }
                    });
                })
                .catch(function(){});
        }

        // Role-agnostic: matches every .message-bubble inside any .messages-list
        // (sender, reply, has-voice variants, dynamically inserted ones).
        function renderVoiceBubbles(root) {
            var $scope = root ? $(root) : $doc;
            $scope.find('ul.messages-list li.message-bubble, .messages-list li.message-bubble').each(function() {
                var $li = $(this);
                if ($li.hasClass('has-voice')) return;
                var $p = $li.find('.message-text p').first();
                if (!$p.length) $p = $li.find('> p').first();
                if (!$p.length) return;
                var raw = $p.text();
                var m = raw.match(/^\s*\[adf-voice\]([^\|\[]+)(?:\|([\d.]+))?\[\/adf-voice\]\s*$/);
                if (!m) return;
                var url   = m[1].replace(/"/g, '&quot;');
                var dur   = m[2] ? parseFloat(m[2]) : 0;
                var attr  = (dur > 0) ? ' data-duration="' + dur + '"' : '';
                var label = (dur > 0) ? fmt(dur) : '0:00';
                $p.html(
                    '<div class="adf-voice"' + attr + '>' +
                        '<button type="button" class="adf-voice__play" aria-label="Play voice"></button>' +
                        '<div class="adf-voice__track" role="slider" tabindex="0" aria-label="Seek"><div class="adf-voice__bar"></div></div>' +
                        '<span class="adf-voice__time">' + label + '</span>' +
                        '<audio class="adf-voice__src" preload="auto" src="' + url + '"></audio>' +
                    '</div>'
                );
                $li.addClass('has-voice');
                var newAudio = $p.find('audio')[0];
                if (newAudio) {
                    bindAudio(newAudio);
                    if (!(dur > 0)) loadDurationViaWebAudio(newAudio, $p.find('.adf-voice'));
                }
            });
        }

        // Document-level click delegation — works regardless of shell wrapper.
        $doc.on('click', '.messages-list .adf-voice__play', function(e) {
            e.preventDefault();
            var btn = this;
            var player = btn.closest('.adf-voice');
            if (!player) return;
            var audio = player.querySelector('audio');
            if (!audio) return;
            attachRefs(audio);
            if (audio.paused) {
                // Pause any other voice currently playing in this thread.
                var others = document.querySelectorAll('.messages-list .adf-voice audio');
                for (var i = 0; i < others.length; i++) {
                    if (others[i] !== audio && !others[i].paused) others[i].pause();
                }
                if (!isFinite(audio.currentTime) || audio.currentTime < 0) {
                    try { audio.currentTime = 0; } catch (er) {}
                }
                btn.classList.add('is-playing');
                player.classList.add('is-active');
                refreshUI(audio);
                var p = audio.play();
                // Kick the rAF UI loop immediately — covers cases where the
                // 'play'/'playing' event fires late (or never, on some
                // codecs) so the timer + waveform still update during
                // playback.
                startTick(audio);
                if (p && p.catch) p.catch(function() {
                    stopTick(audio);
                    btn.classList.remove('is-playing');
                    if (audio.currentTime === 0) player.classList.remove('is-active');
                });
            } else {
                audio.pause();
                stopTick(audio);
                btn.classList.remove('is-playing');
            }
        });
        $doc.on('click', '.messages-list .adf-voice__track', function(e) {
            var $track = $(this);
            var $player = $track.closest('.adf-voice');
            var audio = $player.find('audio')[0];
            if (!audio) return;
            var fixed = parseFloat($player.attr('data-duration')) || 0;
            var dur = fixed > 0 ? fixed : ((isFinite(audio.duration) && audio.duration > 0) ? audio.duration : 0);
            if (dur <= 0) return;
            var rect = $track[0].getBoundingClientRect();
            var pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            try { audio.currentTime = pct * dur; } catch (err) {}
        });

        // Initial render + safety poll for late-arriving metadata.
        renderVoiceBubbles();
        var tries = 0;
        var poll = setInterval(function() {
            $('.messages-list .adf-voice audio').each(function() {
                bindAudio(this);
                if (!this.getAttribute('data-adf-init') && this.readyState >= 1) fixDuration(this);
            });
            if (++tries > 30) clearInterval(poll);
        }, 200);

        // MutationObserver — picks up plugin AJAX re-renders, polled updates,
        // and any newly-injected user-side bubble.
        if (window.MutationObserver) {
            var mo = new MutationObserver(function() { renderVoiceBubbles(); });
            $('.messages-list').each(function() {
                mo.observe(this, { childList: true, subtree: true });
            });
            // Also observe the dashboard body in case the messages-list itself
            // gets replaced wholesale by an AJAX swap.
            mo.observe(document.body, { childList: true, subtree: true });
        }
    });
})();
</script>
