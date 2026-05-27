<?php
/*
 * Template Name: AdForest - Messages (Modern)
 *
 * Standalone "Messages" page used by the Modern User Menu. Modern
 * SaaS-style chat UI on top of the SB Chat plugin, adapted for an
 * ad-triggered marketplace flow (conversations always start from an
 * ad detail page — no "new chat" cold start).
 *
 * Plugin DOM contract preserved exactly so the plugin's JS / AJAX
 * keep working unmodified:
 *   - rail:    .chat-list[data-context=inbox] > ul.chat-list-detail > li[data-id] > a.con-chat-list[data-conv][data-recipient_id]
 *   - thread:  ul.messages-list, .message-spin-loader, #sbModalBody
 *   - composer: form.send-message, #message_box, #conversation_id, #recipient_id, #sbchat-mu, #attachment-wrapper, .dropzone-settings hidden inputs
 *   - actions: .delete-single-chat, .load-conversations, search #inlineFormInputGroup
 *
 * @package Adforest
 */

if (function_exists('adforest_user_not_logged_in')) {
    adforest_user_not_logged_in();
}

global $adforest_theme;

get_header();

/* ------------------------------------------------------------------ */
/* Setup                                                              */
/* ------------------------------------------------------------------ */
$user_id   = get_current_user_id();
$user_info = get_userdata($user_id);

// Theme button colors → CSS variables
$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
$_rgb_parts      = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb   = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

// Theme-options URLs
$post_ad_page_id = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : '';
$post_ad_page_id = apply_filters('adforest_ad_post_verified_id', $post_ad_page_id);
$post_ad_url     = $post_ad_page_id ? get_permalink($post_ad_page_id) : '#';
$modern_post_ad_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? $adforest_theme['sb_modern_post_ad_page'] : '';
$modern_post_ad_url     = $modern_post_ad_page_id ? get_permalink($modern_post_ad_page_id) : $post_ad_url;

$profile_page_id = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
$dash_url        = $profile_page_id ? trailingslashit(get_permalink($profile_page_id)) : home_url('/');

$modern_listings_page_id  = isset($adforest_theme['sb_modern_my_listings_page']) ? $adforest_theme['sb_modern_my_listings_page'] : '';
$modern_listings_url      = $modern_listings_page_id ? get_permalink($modern_listings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_ads', $dash_url) : '#');
$modern_pending_page_id   = isset($adforest_theme['sb_modern_awaiting_approval_page']) ? $adforest_theme['sb_modern_awaiting_approval_page'] : '';
$modern_pending_url       = $modern_pending_page_id ? get_permalink($modern_pending_page_id) : ($dash_url ? add_query_arg('page_type', 'inactive_ads', $dash_url) : '#');
$modern_favorites_page_id = isset($adforest_theme['sb_modern_favorites_page']) ? $adforest_theme['sb_modern_favorites_page'] : '';
$modern_favorites_url     = $modern_favorites_page_id ? get_permalink($modern_favorites_page_id) : ($dash_url ? add_query_arg('page_type', 'fav_ads', $dash_url) : '#');
$modern_settings_page_id  = isset($adforest_theme['sb_modern_settings_page']) ? $adforest_theme['sb_modern_settings_page'] : '';
$modern_settings_url      = $modern_settings_page_id ? get_permalink($modern_settings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_profile', $dash_url) : '#');
$modern_invoices_page_id  = isset($adforest_theme['sb_modern_invoices_page']) ? $adforest_theme['sb_modern_invoices_page'] : '';
$modern_invoices_url      = $modern_invoices_page_id ? get_permalink($modern_invoices_page_id) : ($dash_url ? add_query_arg('page_type', 'invoices', $dash_url) : '#');
$modern_packages_page_id  = isset($adforest_theme['sb_modern_my_packages_page']) ? $adforest_theme['sb_modern_my_packages_page'] : '';
$modern_packages_url      = $modern_packages_page_id ? get_permalink($modern_packages_page_id) : ($dash_url ? add_query_arg('page_type', 'my_packages', $dash_url) : '#');

$account_nav = array(
    array('icon' => 'fa fa-plus-circle',     'label' => __('Add New',           'adforest'), 'url' => $modern_post_ad_url, 'active' => false),
    array('icon' => 'fa fa-clipboard-check', 'label' => __('Awaiting Approval', 'adforest'), 'url' => $modern_pending_url, 'active' => false),
    array('icon' => 'fa fa-receipt',         'label' => __('Invoices',          'adforest'), 'url' => $modern_invoices_url, 'active' => false),
    array('icon' => 'fa fa-list',            'label' => __('My Listings',       'adforest'), 'url' => $modern_listings_url, 'active' => false),
    array('icon' => 'fa fa-heart',           'label' => __('Favorites',         'adforest'), 'url' => $modern_favorites_url, 'active' => false),
    array('icon' => 'fa fa-envelope',        'label' => __('Messages',          'adforest'), 'url' => get_permalink(), 'active' => true),
    array('icon' => 'fa fa-box',             'label' => __('My Packages',       'adforest'), 'url' => $modern_packages_url, 'active' => false),
    array('icon' => 'fa fa-cog',             'label' => __('Profile Settings',  'adforest'), 'url' => $modern_settings_url, 'active' => false),
);

$is_sbchat_active = false;
if (class_exists('SB_Chat')) {
    $is_sbchat_active = SB_Chat::get_plugin_options('sbChat-active');
}

/* ------------------------------------------------------------------ */
/* Conversation prefetch                                              */
/* ------------------------------------------------------------------ */
$user_conversations    = array();
$first_conversation_id = "";
$display_limit         = 50;
if ($is_sbchat_active && $user_id !== 0 && function_exists('sbchat_get_conversations_by_user_id')) {
    $fetched = sbchat_get_conversations_by_user_id($user_id, $display_limit);
    if (is_array($fetched)) {
        foreach ($fetched as $c) {
            $is_user_1       = ($user_id == $c['user_1']);
            $chat_delete_key = $is_user_1 ? 'deleted_by_user_1' : 'deleted_by_user_2';
            if (!empty($c[$chat_delete_key])) continue;
            $user_conversations[] = $c;
        }
    }
}
$has_conversations = !empty($user_conversations);

$grouped_convs = function_exists('adforest_modern_chat_split_pinned')
    ? adforest_modern_chat_split_pinned($user_conversations, $user_id)
    : array('pinned' => array(), 'all' => $user_conversations);

/* ------------------------------------------------------------------ */
/* Buyer-grouped rail (Phase 2 of per-ad conversations)                */
/* ------------------------------------------------------------------ */
$buyer_groups_for = function ($conv_list) use ($user_id) {
    $by_user = array();
    foreach ((array) $conv_list as $c) {
        $other = ($user_id == $c['user_2']) ? (int) $c['user_1'] : (int) $c['user_2'];
        if (!isset($by_user[$other])) $by_user[$other] = array();
        $by_user[$other][] = $c;
    }
    foreach ($by_user as $oid => &$arr) {
        usort($arr, function ($a, $b) { return strtotime($b['updated']) - strtotime($a['updated']); });
    }
    unset($arr);
    uksort($by_user, function ($a, $b) use ($by_user) {
        return strtotime($by_user[$b][0]['updated']) - strtotime($by_user[$a][0]['updated']);
    });
    return $by_user;
};
$buyer_groups_pinned = $buyer_groups_for($grouped_convs['pinned']);
$buyer_groups_all    = $buyer_groups_for($grouped_convs['all']);

$recipient_ids = array();
foreach ($user_conversations as $c) {
    $recipient_ids[] = ($user_id == $c['user_2']) ? (int) $c['user_1'] : (int) $c['user_2'];
}
$presence_map = function_exists('adforest_modern_chat_presence_map')
    ? adforest_modern_chat_presence_map($recipient_ids)
    : array();

$adforest_chat_modern_nonce = wp_create_nonce('adforest_chat_modern_nonce');

// Last-message preview + unread-count cache (one query each, used by the rail)
$last_messages_map = array();
$unread_count_map  = array();
if ($has_conversations) {
    global $wpdb;
    $msg_tbl = $wpdb->prefix . 'sb_chat_messages';
    $conv_ids = array_map('intval', wp_list_pluck($user_conversations, 'id'));
    if (!empty($conv_ids)) {
        $placeholders = implode(',', array_fill(0, count($conv_ids), '%d'));

        // Latest message per conversation
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m1.conversation_id, m1.message, m1.attachment_ids, m1.sender_id
             FROM $msg_tbl m1
             INNER JOIN (
               SELECT conversation_id, MAX(id) AS max_id
               FROM $msg_tbl
               WHERE conversation_id IN ($placeholders)
               GROUP BY conversation_id
             ) m2 ON m1.id = m2.max_id",
            $conv_ids
        ), ARRAY_A);
        foreach ((array) $rows as $r) {
            $last_messages_map[(int) $r['conversation_id']] = $r;
        }

        // Unread count per conversation = messages from the OTHER party.
        // Only meaningful when read_user_X = 0 for the current user; we cap
        // the rendered badge at "9+" so it never grows visually.
        $count_args = array_merge(array($user_id), $conv_ids);
        $cnt_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT conversation_id, COUNT(*) AS cnt
             FROM $msg_tbl
             WHERE sender_id != %d
               AND conversation_id IN ($placeholders)
             GROUP BY conversation_id",
            $count_args
        ), ARRAY_A);
        foreach ((array) $cnt_rows as $r) {
            $unread_count_map[(int) $r['conversation_id']] = (int) $r['cnt'];
        }
    }
}
?>

<style id="adforest-messages-css">
/* ============================================================
   ADFOREST MODERN MESSAGES — chat design system
   ============================================================ */

/* ============================================================
   CHAT ACCENT — dedicated colour token (decoupled from global
   theme primary). Override at :root to recolour just the chat.
   ============================================================ */
:root{
    /* Action/accent — send button, unread badges, links, focus, hover lifts.
       Restored to rose family — only the outgoing-bubble surface tokens
       are the new flat slate. */
    --chat-accent-color:        #d95b72;
    --chat-accent-color-hover:  #c94f66;
    --chat-accent-color-text:   #ffffff;
    --chat-accent-color-rgb:    217, 91, 114;
    /* Bubble surface — flat slate tones, scoped ONLY to outgoing bubbles */
    --chat-bubble-color:        #cfd7e6;
    --chat-bubble-color-hover:  #c2cbdd;
    --chat-bubble-color-text:   #1f2937;
    --chat-bubble-color-soft:   #dde4f0;
}

.adf-msg-page{
    /* Internal aliases — `--brand*` = action token (button/badge/link),
       `--bubble*` = surface token (outgoing message bubbles). Both
       resolve from the chat-scoped `:root` tokens above. */
    --brand:       var(--chat-accent-color);
    --brand-hover: var(--chat-accent-color-hover);
    --brand-text:  var(--chat-accent-color-text);
    --brand-rgb:   var(--chat-accent-color-rgb);
    --bubble:      var(--chat-bubble-color);
    --bubble-hover:var(--chat-bubble-color-hover);
    --bubble-text: var(--chat-bubble-color-text);
    --bubble-soft: var(--chat-bubble-color-soft);
    --bg:#f7f8fa;
    --surface:#ffffff;
    --surface-2:#eef0f3;
    --surface-3:#f3f4f7;
    --border:#eceef2;
    --text:#0f172a;
    --text-muted:#64748b;
    --text-subtle:#94a3b8;
    --shadow-sm:0 1px 2px rgba(15,23,42,.04);
    --shadow-md:0 6px 22px rgba(15,23,42,.06);
    --radius:14px;
    --radius-sm:10px;
    --gap:8px;
    background:var(--bg);min-height:100vh;padding:24px 0 36px;font-family:inherit;
}
.adf-msg-page *{box-sizing:border-box;}

/* Account sub-nav (consistent with sibling modern pages) */
/* Floating pill navigation — rounded card, soft shadow, strong active pill. */
.adf-msg-page .adforest-account-nav{display:flex;flex-wrap:nowrap;align-items:center;gap:6px;background:#fff;border:1px solid var(--border);border-radius:16px;padding:8px;margin-bottom:18px;box-shadow:0 0 10px rgba(15,23,42,.05);overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;}
.adf-msg-page .adforest-account-nav::-webkit-scrollbar{display:none;}
.adf-msg-page .adforest-account-nav a{position:relative;display:inline-flex;align-items:center;gap:8px;color:var(--text-muted);font-size:14px;font-weight:600;text-decoration:none;padding:10px 18px;border-radius:10px;white-space:nowrap;flex-shrink:0;transition:color .18s ease,background .18s ease,box-shadow .18s ease,transform .12s ease;}
.adf-msg-page .adforest-account-nav a:hover{color:var(--text);background:#f6f7fb;}
/* Active pill uses the theme brand color (matches sibling pages),
 * not the page's chat-accent — chat UI keeps its own palette. */
.adf-msg-page .adforest-account-nav a.is-active{color:#fff;background:<?php echo esc_attr($theme_btn_color); ?>;box-shadow:0 0 6px rgba(<?php echo esc_attr($theme_btn_rgb); ?>,.25);}
.adf-msg-page .adforest-account-nav a.is-active:hover{background:<?php echo esc_attr($theme_btn_hover); ?>;color:#fff;}
.adf-msg-page .adforest-account-nav a i{font-size:13px;color:inherit;opacity:.75;}

/* Page title */
.adf-msg-page__heading{margin:0 0 14px;font-size:22px;font-weight:800;color:var(--text);letter-spacing:-.01em;}

/* ============================================================
   SHELL — 30 / 70 split
   ============================================================ */
.adf-chat{
    background:var(--surface);
    border-radius:16px;
    box-shadow:var(--shadow-md);
    overflow:hidden;
    display:grid;
    grid-template-columns:30% 70%;
    /* Without an explicit row track, the implicit row sizes to its
       content's intrinsic height — meaning a long thread makes the grid
       grow vertically, the thread column grows with it, and the body's
       overflow-y never engages. minmax(0, 1fr) forces the single row to
       exactly fill the grid's specified height while permitting children
       to shrink below their content size, so the body can finally scroll
       independently like WhatsApp/Messenger. */
    grid-template-rows:minmax(0, 1fr);
    /* Hard height clamp via min(): pick the smaller of a comfortable
       fixed cap and the viewport-derived ideal. Prevents the shell from
       growing past viewport on tall content AND prevents it from
       shrinking ridiculously on phone-landscape. */
    height:min(calc(100vh - 180px), 760px);
    min-height:520px;
    max-height:calc(100vh - 100px);
    border:1px solid var(--border);
    /* Sticky-pin the shell into the viewport as the page scrolls — the
       header/composer stay reachable even if the user accidentally
       scrolls past, and matches WhatsApp Web's "chat panel doesn't
       slide off-screen" behavior. The 90px offset clears the theme
       header + WP admin bar without overlapping. */
    position:sticky;
    top:90px;
    align-self:start;
}

/* ============================================================
   SIDEBAR
   ============================================================ */
.adf-chat__sidebar{display:flex;flex-direction:column;min-width:0;min-height:0;border-right:1px solid var(--border);background:var(--surface);}

.adf-chat__sidebar-head{padding:16px 16px 8px;}
.adf-chat__sidebar-titlebar{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.adf-chat__sidebar-title{margin:0;font-size:16px;font-weight:700;color:var(--text);letter-spacing:-.01em;}
.adf-chat__newchat{display:none;}

.adf-chat__search{position:relative;}
.adf-chat__search::before{content:"\f002";font-family:"Font Awesome 6 Free","Font Awesome 5 Free","FontAwesome";font-weight:900;position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-subtle);font-size:12px;pointer-events:none;}
.adf-chat__search input{width:100%;height:38px;padding:0 14px 0 36px;border:0;background:var(--surface-3);border-radius:999px;font-size:13px;color:var(--text);font-family:inherit;outline:none;transition:background .15s ease,box-shadow .15s ease;}
.adf-chat__search input::placeholder{color:var(--text-subtle);}
.adf-chat__search input:focus{background:var(--surface);box-shadow:0 0 0 2px rgba(var(--brand-rgb),.18);}

/* Sidebar list */
.adf-chat__list{flex:1;overflow-y:auto;padding:4px 8px 12px;}
.adf-chat__section-label{padding:14px 12px 6px;font-size:10px;font-weight:700;color:var(--text-subtle);letter-spacing:.1em;text-transform:uppercase;}
.adf-chat__list ul.chat-list-detail{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:1px;}

/* ============================================================
   SIDEBAR ITEM  (.sidebar-item  →  .adf-chat__row .con-chat-list)
   .text          →  .adf-chat__row-body
   .name          →  .adf-chat__row-name
   .last-message  →  .adf-chat__row-preview
   .time          →  .adf-chat__row-time
   ============================================================ */
.adf-chat__row{position:relative;border-radius:10px;transition:background .15s ease;}
.adf-chat__row:hover{background:#f3f4f7;}
.adf-chat__row.active{background:#f3f4f7;}
.adf-chat__row .con-chat-list{display:flex;align-items:center;gap:10px;padding:10px 12px;text-decoration:none;color:inherit;width:100%;}

.adf-chat__avatar{position:relative;flex-shrink:0;width:40px;height:40px;border-radius:50%;overflow:hidden;background:#f1f3f6;}
.adf-chat__avatar img{width:100%;height:100%;object-fit:cover;display:block;border-radius:50%;}
.adf-chat__avatar.is-online::after{content:"";position:absolute;right:1px;bottom:1px;width:10px;height:10px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 2px #fff;}
.adf-chat__row.active .adf-chat__avatar.is-online::after,
.adf-chat__row:hover .adf-chat__avatar.is-online::after{box-shadow:0 0 0 2px #f3f4f7;}

.adf-chat__row-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:1px;}
.adf-chat__row-top{display:flex;align-items:center;justify-content:space-between;gap:8px;}
.adf-chat__row-name{margin:0;font-weight:600;font-size:14px;color:var(--text);line-height:1.35;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.adf-chat__row-time{font-size:12px;color:#999;white-space:nowrap;flex-shrink:0;font-weight:500;margin-left:auto;align-self:flex-start;margin-top:2px;}
.adf-chat__row-bottom{display:flex;align-items:center;justify-content:space-between;gap:6px;}
.adf-chat__row-preview{margin:0;font-size:12.5px;color:#888;line-height:1.35;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;min-width:0;}
.adf-chat__row-badge{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 6px;border-radius:999px;background:var(--brand);color:#fff;font-size:10px;font-weight:700;flex-shrink:0;line-height:1;}
.adf-chat__row.unread .adf-chat__row-name{font-weight:700;color:var(--text);}
.adf-chat__row.unread .adf-chat__row-preview{color:var(--text);font-weight:600;}
.adf-chat__row.unread .adf-chat__row-time{color:var(--brand);font-weight:700;}
.adf-chat__row.unread::before{content:"";position:absolute;left:0;top:8px;bottom:8px;width:3px;border-radius:2px;background:var(--brand);}
.adf-chat__row.unread.active::before{background:var(--brand);}

/* Pin button (shows on hover, persistent if pinned) */
.adf-chat__row-pin{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:26px;height:26px;border-radius:50%;border:0;background:transparent;color:var(--text-subtle);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:10px;transition:background .15s ease,color .15s ease;}
.adf-chat__row-pin:hover{background:rgba(15,23,42,.06);color:var(--text);}
.adf-chat__row:hover .adf-chat__row-pin{display:inline-flex;}
.adf-chat__row[data-pinned="1"] .adf-chat__row-pin{display:inline-flex;color:var(--brand);}

.adf-chat__loadmore{margin:10px;padding:8px 14px;border:1px solid var(--border);background:var(--surface);border-radius:8px;font-size:12px;font-weight:600;color:var(--text-muted);cursor:pointer;width:calc(100% - 20px);transition:background .15s ease,color .15s ease;}
.adf-chat__loadmore:hover{background:var(--surface-3);color:var(--text);}

/* Search empty-state — shown only when no conversation matches query */
.adf-chat__search-empty{padding:22px 12px;text-align:center;color:var(--text-subtle);font-size:12.5px;font-weight:500;}
.adf-chat__search-empty[hidden]{display:none;}

/* Buyer-grouped sidebar (multi-ad threads) */
/* Buyer-group head — same row template as a single-conversation row
   so the sidebar reads as one uniform list, but with three subtle
   "this is a group" cues so users can tell at a glance it represents
   multiple ads:
     1. Left brand-accent stripe (3px)
     2. Soft brand-tinted background (~5% opacity)
     3. Layered-cards glyph behind the avatar
   Behaviour stays accordion-toggle: cursor pointer, focus ring,
   chevron rotates with aria-expanded. */
.adf-chat__buyer-head{display:flex;align-items:center;gap:10px;padding:10px 12px 10px 16px;list-style:none;cursor:pointer;border-radius:10px;transition:background .15s ease, box-shadow .15s ease;position:relative;background:rgba(var(--brand-rgb),.045);}
.adf-chat__buyer-head::before{content:"";position:absolute;left:0;top:8px;bottom:8px;width:3px;border-radius:3px;background:var(--brand);opacity:.65;}
.adf-chat__buyer-head:hover{background:rgba(var(--brand-rgb),.085);}
.adf-chat__buyer-head:hover::before{opacity:1;}
.adf-chat__buyer-head:focus-visible{outline:2px solid rgba(var(--brand-rgb),.35);outline-offset:1px;}
/* Layered-cards glyph behind the avatar — communicates "multiple
   conversations" without needing extra DOM. Built with two stacked
   shadow rings so it works against any avatar shape. */
.adf-chat__buyer-head .adf-chat__avatar{flex-shrink:0;width:40px;height:40px;box-shadow:3px 3px 0 -1px rgba(var(--brand-rgb),.15), 6px 6px 0 -2px rgba(var(--brand-rgb),.10);}
.adf-chat__buyer-head .adf-chat__row-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:1px;}
/* Stronger pill — solid brand fill so the "2 ads" badge clearly
   reads as a grouping count, not just decorative. */
.adf-chat__buyer-count-pill{display:inline-flex;align-items:center;gap:4px;padding:0 8px;height:18px;border-radius:999px;background:var(--brand);color:var(--brand-text,#fff);font-size:10px;font-weight:700;flex-shrink:0;line-height:1;letter-spacing:.02em;box-shadow:0 1px 2px rgba(var(--brand-rgb),.20);}
.adf-chat__buyer-count-pill::before{content:"\f0c9";font-family:"Font Awesome 6 Free","Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:8px;opacity:.9;}
/* Chevron rotation reflects expanded/collapsed state — brand-tinted
   so it visually pairs with the rest of the group cues. */
.adf-chat__buyer-toggle{font-size:11px;color:var(--brand);transition:transform .2s ease;flex-shrink:0;width:14px;text-align:center;opacity:.75;}
.adf-chat__buyer-head:hover .adf-chat__buyer-toggle{opacity:1;}
.adf-chat__buyer-head[aria-expanded="false"] .adf-chat__buyer-toggle{transform:rotate(-90deg);}
/* When collapsed, soften the accent so the row reads more like a
   passive item rather than an active group with active threads. */
.adf-chat__buyer-head[aria-expanded="false"]{background:rgba(var(--brand-rgb),.025);}
.adf-chat__buyer-head[aria-expanded="false"]::before{opacity:.35;}
/* Hide all rows belonging to a collapsed buyer group — matches both
   visible thread rows and the "+N more" toggle row. The `.is-collapsed`
   class still applied by the +N more pagination is independent and
   continues to work when the group is re-expanded. */
.adf-chat__row--thread.is-buyer-collapsed,
.adf-chat__buyer-more-row.is-buyer-collapsed{display:none;}
.adf-chat__buyer-head .adf-chat__avatar--sm{width:32px;height:32px;}
.adf-chat__buyer-head .adf-chat__avatar--sm img{width:100%;height:100%;border-radius:50%;object-fit:cover;}
.adf-chat__buyer-info{display:flex;flex-direction:column;gap:1px;min-width:0;flex:1;}
.adf-chat__buyer-name{margin:0;font-size:13px;font-weight:700;color:var(--text);line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.adf-chat__buyer-count{font-size:11px;color:var(--text-subtle);font-weight:500;}

/* Thread sub-row inside a buyer group — indented + ad thumbnail */
.adf-chat__row--thread .con-chat-list{padding-left:46px !important;}
.adf-chat__row--thread .adf-chat__row-name{font-size:13px;font-weight:600;color:var(--text);}
.adf-chat__row--thread.is-collapsed,
.adf-chat__row--thread[hidden]{display:none;}
.adf-thread-thumb{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:24px;height:24px;border-radius:6px;overflow:hidden;background:var(--surface-3);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text-subtle);font-size:10px;}
.adf-thread-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
.adf-thread-thumb.is-placeholder i{font-size:11px;}

/* "+N more" toggle row */
.adf-chat__buyer-more-row{padding:0 12px 6px 46px;list-style:none;}
.adf-chat__buyer-more{display:inline-flex;align-items:center;gap:4px;background:transparent;border:0;color:var(--text-muted);font-size:11.5px;font-weight:600;cursor:pointer;padding:4px 8px;border-radius:6px;font-family:inherit;}
.adf-chat__buyer-more:hover{background:var(--surface-3);color:var(--text);}
.adf-chat__buyer-more[aria-expanded="true"]::after{content:" −";}
.adf-chat__buyer-more:not([aria-expanded="true"])::after{content:"";}


/* ============================================================
   THREAD PANEL
   ============================================================ */
/* min-height:0 on the thread column is critical — without it the flex
   parent refuses to shrink below its children's intrinsic content height,
   so .adf-chat__body's overflow-y:auto never engages and long threads
   spill out of the shell instead of scrolling inside it. The thread
   panel itself is height:100% so it actually takes the grid row's full
   allocated height (some browsers need this even with align-items:stretch
   on the grid). */
.adf-chat__thread{display:flex;flex-direction:column;min-width:0;min-height:0;height:100%;background:var(--bg);overflow:hidden;}
.adf-chat__thread-inner{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;height:100%;overflow:hidden;}
/* Explicit flex roles for the three children so the body is the only
   thing that can grow/scroll. .adf-chat__head and .adf-chat__composer
   stay at their intrinsic heights, the body fills everything between. */
.adf-chat__thread-inner > .adf-chat__head,
.adf-chat__thread-inner > .adf-chat__composer{flex:0 0 auto;}
.adf-chat__thread-inner > .adf-chat__body{flex:1 1 auto;min-height:0;}

/* ============================================================
   THREAD HEADER
   .chat-header h4         →  .adf-chat__head-name
   .chat-header .meta      →  .adf-chat__head-meta
   .chat-header .meta a    →  .adf-chat__head-meta a (acts as .ad-link)
   ============================================================ */
.adf-chat__head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;background:var(--surface);border-bottom:1px solid var(--border);min-height:62px;}
.adf-chat__head-left{display:flex;align-items:center;gap:12px;min-width:0;flex:1;}
.adf-chat__head-back{display:none;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:transparent;color:var(--text);border:0;cursor:pointer;font-size:13px;}
.adf-chat__head-info{min-width:0;}
.adf-chat__head-name{font-size:15px;font-weight:600;margin:0;color:var(--text);line-height:1.25;}
.adf-chat__head-meta{margin:2px 0 0;font-size:12px;color:#8a8f98;font-weight:500;display:flex;align-items:center;gap:6px;flex-wrap:wrap;line-height:1.3;}
.adf-chat__head-meta .dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:#cbd5e1;}
.adf-chat__head-meta.is-online .dot{background:#22c55e;}
.adf-chat__head-meta .sep{color:#cbd5e1;}
.adf-chat__head-meta a{color:var(--brand);text-decoration:none;font-weight:500;}
.adf-chat__head-meta a:hover{text-decoration:underline;}

.adf-chat__head-actions{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.adf-chat__btn-ghost,
.adf-chat__btn-icon{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--border);background:var(--surface);color:var(--text);cursor:pointer;font-family:inherit;transition:background .15s ease,color .15s ease,border-color .15s ease;text-decoration:none;}
.adf-chat__btn-ghost{gap:6px;padding:7px 14px;border-radius:999px;font-size:12px;font-weight:600;}
.adf-chat__btn-ghost:hover{background:var(--surface-3);color:var(--text);}
.adf-chat__btn-icon{width:34px;height:34px;border-radius:50%;font-size:12px;color:var(--text-muted);}
.adf-chat__btn-icon:hover{background:var(--surface-3);color:var(--text);}
.adf-chat__btn-icon--danger{border-color:rgba(239,68,68,.2);color:#ef4444;background:transparent;}
.adf-chat__btn-icon--danger:hover{background:#ef4444;color:#fff;border-color:#ef4444;}

/* ============================================================
   THREAD BODY  (.chat-body  →  .adf-chat__body)
   ============================================================ */
/* overflow-y:auto so the scrollbar appears only when the thread is
   actually taller than the panel — matches the user's request and
   prevents an empty-track gutter when the conversation fits. */
.adf-chat__body{display:flex;flex-direction:column;gap:6px;padding:16px 20px;background:#f7f8fa;flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;}
/* SB Chat plugin's sb-style.css ships `.msg-body ul { overflow: hidden }`,
   which sits inside our chat body and clips the messages-list — preventing
   the body's overflow-y from ever engaging because the ul itself isn't
   reporting an overflow. Force the inner wrapper + ul back to visible so
   the body becomes the single scroll container. */
.adf-chat__body .msg-body,
.adf-chat__body .msg-body ul,
.adf-chat__body ul.messages-list{overflow:visible;}
.adf-chat__body > .messages-list,
.adf-chat__body > .adf-chat__date{max-width:760px;margin-left:auto;margin-right:auto;width:100%;}
.adf-chat__body .message-spin-loader{align-self:center;color:var(--brand);font-size:18px;margin-bottom:6px;}

.adf-chat__date{align-self:center;margin:4px 0 8px;}
.adf-chat__date span{display:inline-block;padding:3px 10px;border-radius:999px;background:rgba(15,23,42,.04);color:var(--text-muted);font-size:11px;font-weight:600;letter-spacing:.01em;}

/* ============================================================
   MESSAGE ROW  (.message  →  ul.messages-list li.message-bubble)
   .sent     →  li.message-bubble.reply
   .received →  li.message-bubble.sender
   ============================================================ */
.adf-chat__body ul.messages-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px;flex:1;min-height:0;}

.adf-chat__body ul.messages-list li.message-bubble{display:flex;width:100%;max-width:none;margin:0 0 2px;padding:0;list-style:none;align-items:flex-end;gap:8px;clear:both;float:none;}
.adf-chat__body ul.messages-list li.message-bubble.reply{justify-content:flex-end;padding-right:4px;}
.adf-chat__body ul.messages-list li.message-bubble.sender{justify-content:flex-start;align-items:flex-start;}

/* Hide plugin's decorative arrow tails */
.adf-chat__body ul.messages-list li.sender::before,
.adf-chat__body ul.messages-list li.reply::before,
.adf-chat__body ul.messages-list li.reply::after{display:none;content:none;}

/* ============================================================
   BUBBLE  (.bubble  →  .message-text  /  > p)
   ============================================================ */
.adf-chat__body ul.messages-list li.message-bubble .message-text,
.adf-chat__body ul.messages-list li.message-bubble > p{max-width:65%;padding:10px 14px;border-radius:14px;font-size:14px;line-height:1.4;margin:0;display:inline-block;text-align:left;word-wrap:break-word;overflow-wrap:break-word;border:0;box-shadow:none;}
.adf-chat__body ul.messages-list li.message-bubble .message-text p,
.adf-chat__body ul.messages-list li.message-bubble .message-text a{margin:0;padding:0;font-size:14px;line-height:1.4;background:transparent;}

/* Outgoing — colors only (shadows/transitions restored) */
.adf-chat__body ul.messages-list li.message-bubble.reply .message-text,
.adf-chat__body ul.messages-list li.message-bubble.reply > p{background:#cfd7e6;color:#1f2937;box-shadow:0 2px 8px rgba(var(--brand-rgb),.12);transition:background-color .2s ease,box-shadow .2s ease,transform .2s ease,opacity .2s ease;}
.adf-chat__body ul.messages-list li.message-bubble.reply .message-text:hover,
.adf-chat__body ul.messages-list li.message-bubble.reply > p:hover{background:#c2cbdd;box-shadow:0 4px 14px rgba(var(--brand-rgb),.18);}
.adf-chat__body ul.messages-list li.message-bubble.reply .message-text *{color:#1f2937;}

/* Incoming — soft grey */
.adf-chat__body ul.messages-list li.message-bubble.sender .message-text,
.adf-chat__body ul.messages-list li.message-bubble.sender > p{background:#f1f3f6;color:#333;}

/* Group-shape: stacked same-author bubbles share an inner corner */
.adf-chat__body ul.messages-list li.message-bubble.sender + li.message-bubble.sender{margin-top:2px;}
.adf-chat__body ul.messages-list li.message-bubble.reply  + li.message-bubble.reply {margin-top:2px;}
.adf-chat__body ul.messages-list li.message-bubble.sender:not(:last-child) .message-text,
.adf-chat__body ul.messages-list li.message-bubble.sender:not(:last-child) > p{border-bottom-left-radius:6px;}
.adf-chat__body ul.messages-list li.message-bubble.sender + li.message-bubble.sender .message-text,
.adf-chat__body ul.messages-list li.message-bubble.sender + li.message-bubble.sender > p{border-top-left-radius:6px;}
.adf-chat__body ul.messages-list li.message-bubble.reply:not(:last-child) .message-text,
.adf-chat__body ul.messages-list li.message-bubble.reply:not(:last-child) > p{border-bottom-right-radius:6px;}
.adf-chat__body ul.messages-list li.message-bubble.reply + li.message-bubble.reply .message-text,
.adf-chat__body ul.messages-list li.message-bubble.reply + li.message-bubble.reply > p{border-top-right-radius:6px;}

/* ============================================================
   RECEIVER AVATAR (last in group)
   ============================================================ */
.adf-chat__body ul.messages-list li.message-bubble.sender .adf-bubble-avatar{display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;overflow:hidden;background:#f1f3f6;flex-shrink:0;align-self:flex-start;order:-1;margin-top:2px;}
.adf-chat__body ul.messages-list li.message-bubble.sender .adf-bubble-avatar img{width:100%;height:100%;object-fit:cover;display:block;margin-top:2px;}
.adf-chat__body ul.messages-list li.message-bubble.sender:not(.is-with-avatar){padding-left:36px;}
.adf-chat__body ul.messages-list li.message-bubble.sender:not(.is-with-avatar) .adf-bubble-avatar{display:none;}

/* ============================================================
   READ RECEIPT (under last outgoing)
   ============================================================ */
.adf-chat__body ul.messages-list li.message-bubble.reply .adf-receipt{font-size:11px;color:var(--text-subtle);margin-left:6px;padding:0 6px 0 0;font-weight:500;align-self:flex-end;}
.adf-chat__body ul.messages-list li.message-bubble.reply .adf-receipt.is-read{color:#22c55e;}

/* ============================================================
   AD ATTACHMENT  (.ad-attachment  →  .message-post-card)
   ============================================================ */
/* AD ATTACHMENT — selectors match plugin's real DOM:
   .message-post-card > .post-card-content (flex) > .post-card-image + .post-card-info
   .post-card-info > .post-card-title (h4 with inner span) + .post-card-link
   High-specificity selectors avoid the need for !important. */
.adf-chat__body ul.messages-list li.message-bubble .message-post-card{max-width:300px;display:flex;gap:8px;align-items:center;padding:6px 8px;background:#f3f4f6;border:0;border-radius:12px;box-shadow:none;text-decoration:none;position:relative;overflow:hidden;}
.adf-chat__body ul.messages-list li.message-bubble.reply .message-post-card{background:rgba(255,255,255,.96);}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card::before,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card::after{display:none;content:none;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content{display:flex;gap:8px;align-items:center;flex:1;min-width:0;width:100%;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content .post-card-image{width:48px;height:48px;flex:0 0 48px;border-radius:8px;overflow:hidden;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content .post-card-image img{width:48px;height:48px;border-radius:8px;object-fit:cover;display:block;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content .post-card-info{flex:1;min-width:0;width:auto;display:flex;flex-direction:column;gap:2px;padding:0;overflow:hidden;}
/* Title clamp. The stored ad-card HTML doesn't always include an
 * h4.post-card-title wrapper — some conversations render the title
 * as a bare <span> directly inside .post-card-info. We cover both
 * cases: the .post-card-title element when present, and the first
 * <span> child of .post-card-info otherwise. */
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-info .post-card-title,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-info > span,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-info > span:first-child{font-size:13px !important;font-weight:500 !important;line-height:1.35 !important;color:#222 !important;margin:0 !important;display:-webkit-box !important;-webkit-line-clamp:2 !important;line-clamp:2 !important;-webkit-box-orient:vertical !important;overflow:hidden !important;word-break:break-word;text-overflow:ellipsis;max-height:calc(1.35em * 2) !important;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content .post-card-info .post-card-title span{display:inline;color:inherit;font-weight:inherit;font-size:inherit;line-height:inherit;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content .post-card-info .post-card-link{font-size:12px;color:var(--brand);text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:4px;align-self:flex-start;padding:0;border:0;background:transparent;margin:0;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content .post-card-info .post-card-link::before{content:none;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card .post-card-content .post-card-info .post-card-link::after{content:"\f061";font-family:"Font Awesome 6 Free","FontAwesome";font-weight:900;font-size:9px;}
.adf-chat__body ul.messages-list li.message-bubble.related-to-card{margin-top:0;}
/* Plugin's sb-style.css decorates the bubble that follows a post card with
   a blue/grey vertical accent — strip it on both incoming and outgoing. */
.adf-chat__body ul.messages-list li.message-bubble.related-to-card .message-text p,
.adf-chat__body ul.messages-list li.message-bubble.related-to-card.reply .message-text p,
.adf-chat__body ul.messages-list li.message-bubble.related-to-card.sender .message-text p{border-left:0;border-right:0;}

/* ============================================================
   IMAGE ATTACHMENTS (plugin-emitted)
   ============================================================ */
.adf-chat__body ul.messages-list li.message-bubble .message-media{padding:0;background:transparent;border-radius:14px;display:inline-block;max-width:100%;box-shadow:none;overflow:hidden;width:auto !important;height:auto !important;}
.adf-chat__body ul.messages-list li.message-bubble .message-media img{max-width:240px;height:auto;border-radius:14px;display:block;cursor:pointer;}

/* ============================================================
   COMPOSER  (.chat-input  →  .adf-chat__composer .send-message-box)
   .chat-input input   →  #message_box / .message-details
   .chat-input button  →  button[type="submit"]
   ============================================================ */
.adf-chat__composer{background:var(--bg);margin-top:auto;}
.adf-chat__composer .send-message{margin:0;}
/* Anonymized-recipient banner — replaces the composer when the OTHER
   party's WP account has been deleted. Conversation history above
   stays fully readable; this just blocks new outbound messages and
   tells the user why. */
.adf-chat__composer--disabled{padding:14px 18px;background:var(--surface);border-top:1px solid var(--border);}
.adf-chat__deleted-banner{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;background:#fef3c7;color:#78350f;font-size:13px;line-height:1.4;border:1px solid #fde68a;}
.adf-chat__deleted-banner i{font-size:16px;color:#b45309;flex-shrink:0;}
.adf-chat__deleted-banner span{flex:1;}
.adf-chat__deleted-banner--block{background:#fee2e2;color:#7f1d1d;border-color:#fecaca;}
.adf-chat__deleted-banner--block i{color:#b91c1c;}

/* ---- Header overflow (⋮) menu ---- */
.adf-chat__overflow{position:relative;display:inline-block;}
.adf-chat__overflow-btn{width:36px;height:36px;border-radius:999px;border:0;background:transparent;color:var(--text-muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:background .15s ease,color .15s ease;}
.adf-chat__overflow-btn:hover{background:var(--surface-3);color:var(--text);}
.adf-chat__overflow-btn[aria-expanded="true"]{background:var(--surface-3);color:var(--text);}
.adf-chat__overflow-menu{position:absolute;top:calc(100% + 6px);right:0;min-width:200px;list-style:none;margin:0;padding:6px;background:var(--surface);border:1px solid var(--border);border-radius:10px;box-shadow:0 12px 30px rgba(15,23,42,.10);z-index:30;}
.adf-chat__overflow-menu[hidden]{display:none;}
.adf-chat__menu-item{display:flex;align-items:center;gap:10px;width:100%;padding:8px 10px;border:0;background:transparent;color:var(--text);font-size:13px;font-weight:500;text-align:left;cursor:pointer;border-radius:6px;transition:background .12s ease,color .12s ease;font-family:inherit;}
.adf-chat__menu-item:hover{background:var(--surface-3);}
.adf-chat__menu-item i{width:16px;font-size:13px;color:var(--text-muted);text-align:center;}
.adf-chat__menu-item--block{color:#b91c1c;}
.adf-chat__menu-item--block i{color:#b91c1c;}
.adf-chat__menu-item--block:hover{background:#fef2f2;}
.adf-chat__menu-item--report{color:#92400e;}
.adf-chat__menu-item--report i{color:#92400e;}
.adf-chat__menu-item--report:hover{background:#fffbeb;}

/* ---- Sidebar treatment for blocked conversations ---- */
.adf-chat__row.is-blocked{opacity:.55;}
.adf-chat__row.is-blocked .adf-chat__row-name::after{content:" 🚫";font-size:11px;letter-spacing:.02em;}

/* ---- Modal (Block confirm + Report) ----
   Self-contained styling — modals render OUTSIDE the .adf-msg-page
   wrapper (right before the closing scripts) so the chat-scoped CSS
   custom properties (--surface, --border, --brand-rgb, etc.) don't
   resolve here. Use explicit color values to guarantee the card
   actually has a background instead of falling back to transparent. */
.adf-chat__modal{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:99999;}
.adf-chat__modal[hidden]{display:none;}
.adf-chat__modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);}
.adf-chat__modal-card{position:relative;width:min(440px,calc(100% - 32px));background:#ffffff;color:#0f172a;border-radius:16px;padding:22px 22px 18px;box-shadow:0 20px 60px rgba(15,23,42,.30);border:1px solid #eceef2;animation:adfModalIn .18s ease-out;}
@keyframes adfModalIn{from{opacity:0;transform:translateY(6px) scale(.98);}to{opacity:1;transform:none;}}
.adf-chat__modal-title{margin:0 0 8px;font-size:17px;font-weight:700;color:#0f172a;letter-spacing:-.01em;}
.adf-chat__modal-body{margin:0 0 16px;font-size:13.5px;line-height:1.5;color:#64748b;}
.adf-chat__modal-form{margin:0;}
.adf-chat__modal-label{display:block;margin:10px 0 6px;font-size:12px;font-weight:600;color:#0f172a;letter-spacing:.01em;}
.adf-chat__modal-select,
.adf-chat__modal-textarea{width:100%;padding:9px 12px;border:1px solid #eceef2;border-radius:8px;background:#ffffff;color:#0f172a;font-size:13px;font-family:inherit;transition:border-color .15s ease,box-shadow .15s ease;box-sizing:border-box;}
.adf-chat__modal-select:focus,
.adf-chat__modal-textarea:focus{outline:none;border-color:rgba(217,91,114,.45);box-shadow:0 0 0 3px rgba(217,91,114,.10);}
.adf-chat__modal-textarea{resize:vertical;min-height:80px;}
.adf-chat__modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px;}
.adf-chat__modal-btn{padding:8px 14px;border-radius:8px;border:1px solid transparent;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s ease,border-color .15s ease,color .15s ease,transform .12s ease;}
.adf-chat__modal-btn:active{transform:translateY(1px);}
.adf-chat__modal-btn--ghost{background:transparent;border-color:#eceef2;color:#64748b;}
.adf-chat__modal-btn--ghost:hover{background:#f3f4f7;color:#0f172a;}
.adf-chat__modal-btn--primary{background:#d95b72;color:#ffffff;}
.adf-chat__modal-btn--primary:hover{background:#c94f66;}
.adf-chat__modal-btn--danger{background:#dc2626;color:#ffffff;}
.adf-chat__modal-btn--danger:hover{background:#b91c1c;}

/* ---- Toast ---- */
.adf-chat__toast{position:fixed;left:50%;bottom:32px;transform:translateX(-50%);max-width:360px;padding:12px 18px;background:#0f172a;color:#fff;border-radius:999px;font-size:13px;font-weight:500;box-shadow:0 12px 30px rgba(15,23,42,.25);z-index:9999;animation:adfToastIn .2s ease-out;}
.adf-chat__toast[hidden]{display:none;}
@keyframes adfToastIn{from{opacity:0;transform:translate(-50%,8px);}to{opacity:1;transform:translate(-50%,0);}}

/* ---- Ad-context state indicators ----
   Subtle parenthetical badge after the "From {Ad Title}" label in the
   chat header. Different colours per state so users can scan quickly:
   expired = amber, deleted/inactive = muted grey. Live ads show no
   badge at all (the existing link is the only signal). */
.adf-chat__head-ad-state{margin-left:4px;font-size:11px;font-weight:600;letter-spacing:.01em;}
.adf-chat__head-ad-state--expired{color:#b45309;}
.adf-chat__head-ad-state--deleted,
.adf-chat__head-ad-state--inactive{color:#6b7280;}
.adf-chat__head-ad-link--deleted,
.adf-chat__head-ad-link--inactive{color:var(--text-muted);text-decoration:line-through;text-decoration-color:rgba(107,114,128,.5);text-decoration-thickness:1px;}
.adf-chat__head-ad-link--expired{opacity:.85;}

/* ---- First-message embedded ad-card decoration ----
   The plugin renders the original ad-card HTML stored at conversation-
   creation time inside the FIRST message bubble. When the underlying
   post is gone (deleted) or in a non-public state (inactive), we
   greyscale the card and overlay a small badge so users don't expect
   it to behave like a live link. The JS decoration adds an
   `adf-card-state-{state}` class to .message-post-card based on the
   body's data-ad-state attribute. */
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-deleted,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-inactive{filter:grayscale(.7);opacity:.7;cursor:default;pointer-events:none;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-deleted::after,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-inactive::after,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-expired::after{content:attr(data-state-label);position:absolute;top:6px;right:6px;font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:2px 6px;border-radius:4px;background:#fef3c7;color:#78350f;line-height:1.2;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-deleted::after,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-inactive::after{background:#e5e7eb;color:#374151;}
.adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-expired{opacity:.92;}
.adf-chat__composer .send-message-box{display:flex;align-items:center;gap:10px;padding:8px 12px;border-top:1px solid #eee;background:#fff;border-radius:0;border-left:0;border-right:0;border-bottom:0;transition:none;}
.adf-chat__composer .send-message-box:focus-within{box-shadow:none;}
.adf-chat__composer #message_box,
.adf-chat__composer .message-details{flex:1;border:none;outline:none;font-size:14px;background:transparent;color:var(--text);height:38px;padding:0 4px;font-family:inherit;box-shadow:none;min-width:0;}
.adf-chat__composer #message_box::placeholder{color:#9aa3b2;opacity:1;}

.adf-chat__composer #sbchat-mu,
.adf-chat__composer .sbchat_upload_items,
.adf-chat__composer .adf-composer-icon{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;color:var(--text-muted);font-size:14px;cursor:pointer;border-radius:50%;flex-shrink:0;background:transparent;border:0;transition:background .15s ease,color .15s ease;font-family:inherit;}
.adf-chat__composer #sbchat-mu:hover,
.adf-chat__composer .sbchat_upload_items:hover,
.adf-chat__composer .adf-composer-icon:hover{background:#f3f4f7;color:var(--text);}
.adf-chat__composer #sbchat-mu::before{font-family:"Font Awesome 6 Free","FontAwesome";content:"\f0c6";font-weight:900;font-size:14px;color:inherit;line-height:1;}
.adf-chat__composer .sbchat_upload_items.mdi{font-family:inherit;}

.adf-chat__composer button[type="submit"]{width:38px;height:38px;border-radius:50%;background:var(--brand);color:var(--brand-text);border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;padding:0;box-shadow:0 2px 8px rgba(var(--brand-rgb),.18);transition:background-color .2s ease,box-shadow .2s ease,transform .2s ease,opacity .2s ease;}
.adf-chat__composer button[type="submit"]:hover{background:var(--brand-hover);box-shadow:0 4px 14px rgba(var(--brand-rgb),.28);transform:translateY(-1px);}
.adf-chat__composer button[type="submit"] i{font-size:14px;line-height:1;}
.adf-chat__composer button[type="submit"] i.mdi-send-circle::before{font-family:"Font Awesome 6 Free","FontAwesome";content:"\f1d8";font-weight:900;}

.adf-chat__composer .attachment-wrapper_main{margin-top:0;padding:6px 12px;background:#fff;}

/* Empty thread (rail has rows but no thread selected) */
.adf-chat__thread-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;text-align:center;color:var(--text-subtle);padding:30px 24px;}
.adf-chat__thread-empty i{font-size:42px;color:#cbd5e1;margin-bottom:10px;}
.adf-chat__thread-empty p{margin:0;font-size:13px;}

/* ============================================================
   ZERO STATE — no conversations at all
   ============================================================ */
.adf-msg-zero{background:var(--surface);border-radius:18px;box-shadow:var(--shadow-md);padding:64px 24px 72px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:520px;}
.adf-msg-zero__art{width:200px;height:200px;display:flex;align-items:center;justify-content:center;margin-bottom:6px;}
.adf-msg-zero__art svg{width:100%;height:100%;display:block;}
.adf-msg-zero h2{font-size:22px;font-weight:800;color:var(--text);margin:14px 0 8px;}
.adf-msg-zero p{margin:0 0 22px;color:var(--text-muted);font-size:14px;max-width:380px;line-height:1.55;}
.adf-msg-zero__btn{display:inline-flex;align-items:center;gap:8px;background:var(--brand);color:var(--brand-text) !important;border:0;padding:11px 28px;border-radius:999px;font-weight:600;font-size:14px;text-decoration:none;cursor:pointer;font-family:inherit;transition:background .15s ease,transform .15s ease;}
.adf-msg-zero__btn:hover{background:var(--brand-hover);transform:translateY(-1px);}

/* Plugin-not-active notice */
.adf-msg-notice{background:var(--surface);border-radius:18px;box-shadow:var(--shadow-md);padding:32px 24px;text-align:center;color:var(--text-muted);}
.adf-msg-notice h3{margin:0 0 8px;font-size:18px;font-weight:700;color:var(--text);}

/* Print view (existing flow keeps working) */
#adf-inv-print{display:none;}

/* ============================================================
   REAL-TIME POLISH — micro-UX (transitions, hover, focus, scrollbar)
   ============================================================ */

/* 1. Message appear animation */
@keyframes adfMsgIn{from{opacity:0;transform:translateY(4px);}to{opacity:1;transform:none;}}
.adf-chat__body ul.messages-list li.message-bubble{animation:adfMsgIn .22s ease both;}
.adf-chat__body ul.messages-list li.message-bubble.new-message{animation:adfMsgIn .26s ease both;}

/* 8. Consistent transition system */
.adf-chat__row,
.adf-chat__row .con-chat-list,
.adf-chat__row-pin,
.adf-chat__btn-ghost,
.adf-chat__btn-icon,
.adf-chat__head-back,
.adf-chat__composer .send-message-box,
.adf-chat__composer #message_box,
.adf-chat__composer #sbchat-mu,
.adf-chat__composer .sbchat_upload_items,
.adf-chat__composer .adf-composer-icon,
.adf-chat__composer button[type="submit"],
.adf-chat__body ul.messages-list li.message-bubble .message-text,
.adf-chat__body ul.messages-list li.message-bubble > p,
.adf-chat__body ul.messages-list li.message-bubble .message-post-card,
.adf-chat__search input{transition:background-color .2s ease,color .2s ease,border-color .2s ease,box-shadow .2s ease,transform .2s ease,opacity .2s ease;}

/* 2. Hover states */
.adf-chat__row:hover{background:#f3f4f7;}
.adf-chat__row:hover .adf-chat__row-name{color:#0f172a;}
.adf-chat__btn-ghost:hover,
.adf-chat__btn-icon:hover{transform:translateY(-1px);box-shadow:0 2px 6px rgba(15,23,42,.06);}
.adf-chat__composer button[type="submit"]:hover{box-shadow:0 4px 10px rgba(var(--brand-rgb),.28);}

/* Ad attachment hover lift */
.adf-chat__body ul.messages-list li.message-bubble .message-post-card:hover{background:#eef0f3;box-shadow:0 2px 8px rgba(15,23,42,.06);transform:translateY(-1px);}
.adf-chat__body ul.messages-list li.message-bubble.reply .message-post-card:hover{background:#fff;}

/* 5. Bubble micro-interaction (very subtle) */
.adf-chat__body ul.messages-list li.message-bubble .message-text:hover,
.adf-chat__body ul.messages-list li.message-bubble > p:hover{filter:brightness(.985);}
.adf-chat__body ul.messages-list li.message-bubble.reply .message-text:hover,
.adf-chat__body ul.messages-list li.message-bubble.reply > p:hover{filter:brightness(1.05);}

/* 3. Read / sent receipt polish */
.adf-chat__body ul.messages-list li.message-bubble.reply .adf-receipt{opacity:.7;letter-spacing:.5px;font-feature-settings:"tnum" 1;}
.adf-chat__body ul.messages-list li.message-bubble.reply .adf-receipt.is-read{opacity:1;}
.adf-chat__body ul.messages-list li.message-bubble.reply:hover .adf-receipt{opacity:1;}

/* 4. Composer focus experience — no ring/glow on focus, keep the
   border subtle so the input doesn't draw attention away from the
   thread when the user clicks into it. */
.adf-chat__composer .send-message-box:focus-within{border-color:var(--border);box-shadow:none;}

/* Search input focus polish */
.adf-chat__search input:focus{box-shadow:0 0 0 3px rgba(var(--brand-rgb),.10);}

/* 6. Modern scrollbar — message area + sidebar list. Body uses a wider,
   always-visible thumb so users can clearly see the scroll affordance
   when a thread overflows the panel; sidebar stays slim. */
.adf-chat__body{scrollbar-width:auto;scrollbar-color:#94a3b8 #eef2f6;}
.adf-chat__list{scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;}
.adf-chat__body::-webkit-scrollbar{width:10px;height:10px;}
.adf-chat__list::-webkit-scrollbar{width:6px;height:6px;}
.adf-chat__body::-webkit-scrollbar-track{background:#eef2f6;border-radius:8px;}
.adf-chat__list::-webkit-scrollbar-track{background:transparent;}
.adf-chat__body::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:8px;border:2px solid #eef2f6;min-height:32px;}
.adf-chat__list::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:6px;border:1px solid transparent;}
.adf-chat__body::-webkit-scrollbar-thumb:hover{background:#64748b;}
.adf-chat__list::-webkit-scrollbar-thumb:hover{background:#94a3b8;}

/* 7. Empty-space balance — keep messages centered within a comfortable column */
.adf-chat__body > .messages-list,
.adf-chat__body > .adf-chat__date{max-width:760px;margin-left:auto;margin-right:auto;width:100%;}

/* Active row keeps a tiny color shift on hover for distinction */
.adf-chat__row.active:hover{background:#eef0f3;}

/* Pin button slight scale on hover for tactile feedback */
.adf-chat__row-pin:hover{transform:translateY(-50%) scale(1.08);}

/* Emoji-only message — outgoing colors only (sizing/shape preserved) */
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only .message-text,
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only > p{padding:6px 12px;border-radius:18px;font-size:34px;line-height:1.2;letter-spacing:0;}
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only.reply .message-text,
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only.reply > p{background:#dde4f0;color:#1f2937;}
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only.is-emoji-medium .message-text,
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only.is-emoji-medium > p{font-size:24px;}
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only.is-emoji-small .message-text,
.adf-chat__body ul.messages-list li.message-bubble.is-emoji-only.is-emoji-small > p{font-size:22px;}

/* ===== Voice recording state ===== */
.adf-chat__composer .adf-composer-mic{position:relative;}
.adf-chat__composer.is-recording .adf-composer-mic{background:#ef4444 !important;color:#fff !important;}
.adf-chat__composer.is-recording .adf-composer-mic::after{content:"";position:absolute;inset:-2px;border-radius:50%;background:rgba(239,68,68,.35);animation:adfMicPulse 1.2s ease-out infinite;z-index:-1;}
@keyframes adfMicPulse{0%{transform:scale(1);opacity:.6;}100%{transform:scale(1.6);opacity:0;}}
.adf-chat__composer.is-recording #message_box{display:none;}
.adf-chat__composer .adf-recording-bar{display:none;flex:1;align-items:center;gap:10px;padding:0 6px;color:#1f2937;font-size:13px;font-weight:500;}
.adf-chat__composer.is-recording .adf-recording-bar{display:inline-flex;}
.adf-chat__composer .adf-recording-bar .dot{width:9px;height:9px;border-radius:50%;background:#ef4444;animation:adfRecBlink 1s ease-in-out infinite;}
@keyframes adfRecBlink{0%,100%{opacity:.4;}50%{opacity:1;}}
.adf-chat__composer .adf-recording-bar .timer{font-feature-settings:"tnum" 1;color:#1f2937;}
.adf-chat__composer .adf-recording-bar .hint{color:#94a3b8;font-weight:400;}
.adf-chat__composer .adf-rec-cancel{display:none;align-items:center;justify-content:center;width:34px;height:34px;border:0;background:transparent;color:#64748b;cursor:pointer;border-radius:50%;font-size:14px;flex-shrink:0;}
.adf-chat__composer.is-recording .adf-rec-cancel{display:inline-flex;}
.adf-chat__composer .adf-rec-cancel:hover{background:#f3f4f7;color:#1f2937;}

/* Voice-message player — WhatsApp/Messenger style, scoped to .has-voice */
.adf-chat__body ul.messages-list li.message-bubble.has-voice .message-text,
.adf-chat__body ul.messages-list li.message-bubble.has-voice > p{padding:0 !important;background:transparent !important;border:0 !important;box-shadow:none !important;max-width:none;}

/* Pre-decoration FOUC guard — the server emits raw `[adf-voice]URL|DUR[/adf-voice]`
   text on initial render. Hide that marker until the JS decorator swaps it for
   the player markup (which removes `.has-voice-pending` is unnecessary — the
   selector below stops applying once `.has-voice` is added). */
.adf-chat__body ul.messages-list li.message-bubble.has-voice-pending:not(.has-voice) .message-text p,
.adf-chat__body ul.messages-list li.message-bubble.has-voice-pending:not(.has-voice) > p{
    visibility:hidden;
}

.adf-voice{
    display:inline-flex;align-items:center;gap:8px;
    padding:5px 10px 5px 5px;
    border-radius:999px;
    background:#f1f3f6;
    min-width:200px;max-width:224px;
    color:#1f2937;line-height:1;
    /* Shared waveform mask — softer height variance for a calmer feel */
    --voice-wave:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 18'%3E%3Crect x='0' y='5' width='2' height='8' rx='1' fill='%23000'/%3E%3Crect x='4' y='3' width='2' height='12' rx='1' fill='%23000'/%3E%3Crect x='8' y='6' width='2' height='6' rx='1' fill='%23000'/%3E%3Crect x='12' y='3.5' width='2' height='11' rx='1' fill='%23000'/%3E%3Crect x='16' y='4.5' width='2' height='9' rx='1' fill='%23000'/%3E%3Crect x='20' y='6' width='2' height='6' rx='1' fill='%23000'/%3E%3Crect x='24' y='4' width='2' height='10' rx='1' fill='%23000'/%3E%3Crect x='28' y='5' width='2' height='8' rx='1' fill='%23000'/%3E%3C/svg%3E");
}
.adf-chat__body ul.messages-list li.message-bubble.reply.has-voice .adf-voice{background:#cfd7e6;}

/* Play / pause */
.adf-voice__play{
    position:relative;width:32px;height:32px;border-radius:50%;border:0;
    background:#1f2937;color:#fff;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;line-height:1;
    font-family:"Font Awesome 6 Free","Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:0;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 1px 2px rgba(15,23,42,.16);
    transition:background-color .18s ease,transform .12s ease,box-shadow .18s ease;
}
.adf-voice__play:hover{background:#0f172a;box-shadow:inset 0 1px 0 rgba(255,255,255,.08),0 2px 4px rgba(15,23,42,.20);}
.adf-voice__play:active{transform:scale(.96);box-shadow:inset 0 1px 0 rgba(255,255,255,.04),0 1px 1px rgba(15,23,42,.14);}
.adf-voice__play i{display:none;}
.adf-voice__play::before{content:"\f04b";font-family:"Font Awesome 6 Free","Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:10px;line-height:1;color:#fff;margin-left:2px;}
.adf-voice__play.is-playing::before{content:"\f04c";font-size:10px;margin-left:0;}

/* Waveform track */
.adf-voice__track{
    flex:1;height:20px;cursor:pointer;position:relative;min-width:96px;
    display:flex;align-items:center;
}
.adf-voice__track::before{
    content:"";position:absolute;left:0;right:0;top:0;bottom:0;
    background:rgba(15,23,42,.32);
    -webkit-mask-image:var(--voice-wave);mask-image:var(--voice-wave);
    -webkit-mask-repeat:repeat-x;mask-repeat:repeat-x;
    -webkit-mask-position:0 center;mask-position:0 center;
    -webkit-mask-size:32px 18px;mask-size:32px 18px;
}
.adf-chat__body ul.messages-list li.message-bubble.reply.has-voice .adf-voice__track::before{background:rgba(15,23,42,.40);}

.adf-voice__bar{
    position:absolute;left:0;top:0;bottom:0;width:0%;
    background:#0f172a;
    -webkit-mask-image:var(--voice-wave);mask-image:var(--voice-wave);
    -webkit-mask-repeat:repeat-x;mask-repeat:repeat-x;
    -webkit-mask-position:0 center;mask-position:0 center;
    -webkit-mask-size:32px 18px;mask-size:32px 18px;
    transition:width .2s cubic-bezier(.4,0,.2,1);will-change:width;
}
.adf-voice__bar::after{
    content:"";position:absolute;right:-4px;top:50%;
    width:9px;height:9px;border-radius:50%;background:#0f172a;
    transform:translateY(-50%) scale(.4);opacity:0;
    transition:opacity .2s ease,transform .2s ease;
    box-shadow:0 0 0 3px rgba(15,23,42,.06);
}
.adf-voice.is-active .adf-voice__bar::after{opacity:1;transform:translateY(-50%) scale(1);}

/* Time */
.adf-voice__time{
    font-size:10px;font-weight:500;color:#64748b;
    flex-shrink:0;font-feature-settings:"tnum" 1;font-variant-numeric:tabular-nums;
    min-width:28px;text-align:right;letter-spacing:.02em;
    align-self:center;
}
.adf-voice__src{display:none;}

/* Reduce-motion: disable bar transition so screen-reader-driven seek isn't laggy */
@media (prefers-reduced-motion: reduce){
    .adf-voice__bar{transition:none;}
    .adf-voice__bar::after{transition:opacity .1s ease;}
}

/* ===== Emoji picker panel ===== */
.adf-emoji-panel{position:absolute;z-index:9999;background:#fff;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,.18);overflow:hidden;transform-origin:bottom right;transition:opacity .15s ease,transform .15s ease;}
.adf-emoji-panel[hidden]{display:none !important;}
.adf-emoji-panel.is-closing{opacity:0;transform:scale(.96);}
.adf-emoji-panel emoji-picker{display:block;height:360px;width:340px;--background:#fff;--border-color:#eef0f4;--input-border-color:#e5e7eb;--input-font-color:#0f172a;--input-placeholder-color:#94a3b8;--button-active-background:#f3f4f7;--button-hover-background:#f3f4f7;--indicator-color:var(--brand);--num-columns:8;}
@media (max-width:600px){
    .adf-emoji-panel{position:fixed !important;left:8px !important;right:8px !important;bottom:calc(8px + env(safe-area-inset-bottom)) !important;top:auto !important;}
    .adf-emoji-panel emoji-picker{width:100%;height:50vh;}
}

/* ============================================================
   ADVANCED UX — typing indicator, lazy-load, swipe, mobile
   ============================================================ */

/* Typing indicator (animated 3 dots) */
.adf-typing{padding:4px 4px 8px 36px;align-self:flex-start;}
.adf-typing[hidden]{display:none !important;}
.adf-typing__bubble{display:inline-flex;align-items:center;gap:4px;padding:9px 14px;background:var(--surface-2);border-radius:14px 14px 14px 4px;}
.adf-typing__dot{width:6px;height:6px;border-radius:50%;background:#94a3b8;display:inline-block;animation:adfTypingDot 1.2s ease-in-out infinite;}
.adf-typing__dot:nth-child(2){animation-delay:.18s;}
.adf-typing__dot:nth-child(3){animation-delay:.36s;}
@keyframes adfTypingDot{0%,80%,100%{opacity:.35;transform:translateY(0);}40%{opacity:1;transform:translateY(-2px);}}

/* "Load older" button */
.adf-chat__load-older{align-self:center;margin:0 0 10px;padding:6px 14px;border:1px solid var(--border);background:var(--surface);color:var(--text-muted);border-radius:999px;font-size:11px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:background .2s ease,color .2s ease,border-color .2s ease,transform .2s ease;}
.adf-chat__load-older[hidden]{display:none;}
.adf-chat__load-older:hover{background:var(--surface-3);color:var(--text);}
.adf-chat__load-older.is-loading{pointer-events:none;opacity:.65;}
.adf-chat__load-older.is-loading i{animation:adfSpin .9s linear infinite;}
@keyframes adfSpin{to{transform:rotate(360deg);}}

/* Swipe action visuals on sidebar rows (mobile) */
.adf-chat__row{overflow:hidden;}
.adf-chat__row .con-chat-list,
.adf-chat__row .adf-chat__row-pin{transition:transform .22s ease,background-color .2s ease;will-change:transform;}
.adf-chat__row.is-swiping .con-chat-list,
.adf-chat__row.is-swiping .adf-chat__row-pin{transition:none;}
.adf-chat__row::before,
.adf-chat__row::after{position:absolute;top:0;bottom:0;display:flex;align-items:center;justify-content:center;width:64px;font-size:14px;color:#fff;pointer-events:none;opacity:0;transition:opacity .2s ease;}
.adf-chat__row::before{content:"\f024";font-family:"Font Awesome 6 Free","FontAwesome";font-weight:900;left:0;background:#3b82f6;border-radius:10px 0 0 10px;}
.adf-chat__row::after{content:"\f1f8";font-family:"Font Awesome 6 Free","FontAwesome";font-weight:900;right:0;background:#ef4444;border-radius:0 10px 10px 0;}
.adf-chat__row.swipe-right::before{opacity:1;}
.adf-chat__row.swipe-left::after{opacity:1;}

/* New-message insertion micro-animation override (smoother than the
   default fade for live-injected bubbles) */
@keyframes adfMsgArrive{from{opacity:0;transform:translateY(6px) scale(.985);}to{opacity:1;transform:none;}}
.adf-chat__body ul.messages-list li.message-bubble.adf-just-arrived{animation:adfMsgArrive .24s ease-out both;}

/* Unread separator for live-arrived messages (UI-only, optional) */
.adf-chat__body .adf-unread-divider{align-self:center;display:flex;align-items:center;gap:8px;width:100%;max-width:760px;margin:8px auto;color:var(--brand);font-size:11px;font-weight:700;letter-spacing:.06em;}
.adf-chat__body .adf-unread-divider::before,
.adf-chat__body .adf-unread-divider::after{content:"";flex:1;height:1px;background:rgba(var(--brand-rgb),.20);}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width:991px){
    .adf-chat{grid-template-columns:1fr;height:calc(100vh - 120px);height:calc(100dvh - 120px);}
    .adf-chat__sidebar{display:flex;}
    .adf-chat__thread{display:none;}
    .adf-chat.is-thread-open .adf-chat__sidebar{display:none;}
    .adf-chat.is-thread-open .adf-chat__thread{display:flex;}
    .adf-chat__head-back{display:inline-flex;}
    /* Sidebar becomes a drawer on mobile when thread is open — see is-thread-open above */
    .adf-chat__sidebar{position:relative;}
    .adf-chat__sidebar-head{position:sticky;top:0;background:var(--surface);z-index:2;padding-top:12px;}
}
@media (max-width:600px){
    .adf-msg-page{padding:14px 0 0;}
    .adf-msg-page__heading{font-size:18px;margin-bottom:10px;}
    .adf-chat{border-radius:12px;height:calc(100vh - 100px);height:calc(100dvh - 100px);}
    .adf-chat__body{padding:12px 12px 8px;}
    .adf-chat__body ul.messages-list li.message-bubble{max-width:82% !important;}
    .adf-chat__head{padding:10px 12px;min-height:56px;}
    .adf-chat__head-actions{gap:4px;}
    .adf-chat__btn-ghost{padding:6px 10px;font-size:11px;}
    .adf-chat__btn-icon,
    .adf-chat__head-back{width:38px;height:38px;}
    /* Sticky composer respecting iOS safe-area */
    .adf-chat__composer{position:sticky;bottom:0;padding:8px 10px calc(8px + env(safe-area-inset-bottom)) 10px;background:#fff;z-index:3;}
    .adf-chat__composer .send-message-box{border-radius:999px;border:1px solid var(--border);}
    /* Touch targets ≥ 44px */
    .adf-chat__row .con-chat-list{padding:12px;min-height:60px;}
    .adf-chat__composer #sbchat-mu,
    .adf-chat__composer .sbchat_upload_items,
    .adf-chat__composer .adf-composer-icon{width:40px;height:40px;}
    .adf-chat__composer button[type="submit"]{width:42px;height:42px;}
    .adf-chat__sidebar-head{padding:12px 12px 8px;}
    .adf-chat__list{padding:4px;}
    /* Avoid horizontal overflow on long previews */
    .adf-chat__row-name,
    .adf-chat__row-preview{max-width:100%;}
    /* Account pill nav — slight compaction on small screens */
    .adf-msg-page .adforest-account-nav{padding:6px;border-radius:14px;margin-bottom:14px;-webkit-overflow-scrolling:touch;}
    .adf-msg-page .adforest-account-nav a{padding:8px 14px;font-size:13px;}
    /* Typing bubble shifts left when avatar slot is removed */
    .adf-typing{padding-left:14px;}
}
@media (max-width:380px){
    .adf-chat__btn-ghost{display:none;} /* keep just delete + back to save space */
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above.
 * ========================================================== */
body.rtl .adf-chat__search::before{left:auto;right:14px;}
body.rtl .adf-chat__search input{padding:0 36px 0 14px;}
body.rtl .adf-chat__row-time{margin-left:0;margin-right:auto;}
body.rtl .adf-chat__row.unread::before{left:auto;right:0;}
body.rtl .adf-chat__row-pin{right:auto;left:10px;}
body.rtl .adf-chat__sidebar{border-right:0;border-left:1px solid var(--border);}
body.rtl .adf-chat__buyer-head{padding:10px 16px 10px 12px;}
body.rtl .adf-chat__buyer-head::before{left:auto;right:0;}
body.rtl .adf-chat__buyer-head[aria-expanded="false"] .adf-chat__buyer-toggle{transform:rotate(90deg);}
body.rtl .adf-chat__row--thread .con-chat-list{padding-left:12px !important;padding-right:46px !important;}
body.rtl .adf-thread-thumb{left:auto;right:14px;}
body.rtl .adf-chat__buyer-more-row{padding:0 46px 6px 12px;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.reply{padding-right:0;padding-left:4px;justify-content:flex-start;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.sender{justify-content:flex-end;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble .message-text,
body.rtl .adf-chat__body ul.messages-list li.message-bubble > p{text-align:right;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.sender:not(:last-child) .message-text,
body.rtl .adf-chat__body ul.messages-list li.message-bubble.sender:not(:last-child) > p{border-bottom-left-radius:14px;border-bottom-right-radius:6px;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.sender + li.message-bubble.sender .message-text,
body.rtl .adf-chat__body ul.messages-list li.message-bubble.sender + li.message-bubble.sender > p{border-top-left-radius:14px;border-top-right-radius:6px;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.reply:not(:last-child) .message-text,
body.rtl .adf-chat__body ul.messages-list li.message-bubble.reply:not(:last-child) > p{border-bottom-right-radius:14px;border-bottom-left-radius:6px;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.reply + li.message-bubble.reply .message-text,
body.rtl .adf-chat__body ul.messages-list li.message-bubble.reply + li.message-bubble.reply > p{border-top-right-radius:14px;border-top-left-radius:6px;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.sender:not(.is-with-avatar){padding-left:0;padding-right:36px;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble.reply .adf-receipt{margin-left:0;margin-right:6px;padding:0 0 0 6px;}
body.rtl .adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-deleted::after,
body.rtl .adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-inactive::after,
body.rtl .adf-chat__body ul.messages-list li.message-bubble .message-post-card.adf-card-state-expired::after{right:auto;left:6px;}
body.rtl .adf-chat__overflow-menu{right:auto;left:0;}
body.rtl .adf-chat__menu-item{text-align:right;}
body.rtl .adf-chat__head-ad-state{margin-left:0;margin-right:4px;}
body.rtl .adf-chat__modal-actions{justify-content:flex-start;}
body.rtl .adf-voice__time{text-align:left;}
body.rtl .adf-voice__bar::after{right:auto;left:-4px;}
body.rtl .adf-voice__play::before{margin-left:0;margin-right:2px;}
body.rtl .adf-voice__play.is-playing::before{margin-right:0;}
body.rtl .adf-typing{padding:4px 36px 8px 4px;}
body.rtl .adf-typing__bubble{border-radius:14px 14px 4px 14px;}
body.rtl .adf-chat__row::before{left:auto;right:0;border-radius:0 10px 10px 0;}
body.rtl .adf-chat__row::after{right:auto;left:0;border-radius:10px 0 0 10px;}
@media (max-width:600px){
    body.rtl .adf-typing{padding-left:0;padding-right:14px;}
}
</style>

<div class="adforest-account-page adf-msg-page">
    <div class="container adt-container">

        <!-- Account sub-nav -->
        <nav class="adforest-account-nav" aria-label="<?php esc_attr_e('Account navigation', 'adforest'); ?>">
            <?php foreach ($account_nav as $nav_item) : ?>
                <a href="<?php echo esc_url($nav_item['url']); ?>" class="<?php echo $nav_item['active'] ? 'is-active' : ''; ?>">
                    <i class="<?php echo esc_attr($nav_item['icon']); ?>"></i>
                    <?php echo esc_html($nav_item['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <h1 class="adf-msg-page__heading"><?php esc_html_e('Messages', 'adforest'); ?></h1>

        <?php if (!$is_sbchat_active) : ?>

            <div class="adf-msg-notice">
                <h3><?php esc_html_e('Messaging is not available', 'adforest'); ?></h3>
                <p><?php esc_html_e('Please install the SB Chat plugin to enable the chat feature.', 'adforest'); ?></p>
            </div>

        <?php elseif (!$has_conversations) : ?>

            <!-- Zero state — no conversations -->
            <div class="adf-msg-zero">
                <div class="adf-msg-zero__art" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" fill="none">
                        <circle cx="120" cy="120" r="92" fill="#f3f4f6"/>
                        <path d="M48 92c0-3.3 2.7-6 6-6h132c3.3 0 6 2.7 6 6v60c0 3.3-2.7 6-6 6H54c-3.3 0-6-2.7-6-6V92z" fill="#e5e7eb"/>
                        <path d="M48 92l72 50 72-50v60c0 3.3-2.7 6-6 6H54c-3.3 0-6-2.7-6-6V92z" fill="#cbd5e1"/>
                        <path d="M48 92h144l-72 50L48 92z" fill="#1f2937"/>
                        <rect x="74" y="76" width="92" height="50" rx="3" fill="#fff"/>
                        <rect x="84" y="92" width="72" height="6" rx="3" fill="var(--brand)"/>
                        <rect x="84" y="104" width="56" height="4" rx="2" fill="#e5e7eb"/>
                        <rect x="84" y="113" width="64" height="4" rx="2" fill="#e5e7eb"/>
                    </svg>
                </div>
                <h2><?php esc_html_e('No conversations yet 👋', 'adforest'); ?></h2>
                <p><?php esc_html_e('Start by contacting sellers from any ad. Your conversations will appear here.', 'adforest'); ?></p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="adf-msg-zero__btn">
                    <?php esc_html_e('Browse Ads', 'adforest'); ?>
                </a>
            </div>

        <?php else : ?>

            <!-- ============ CHAT SHELL ============ -->
            <div class="adf-chat message-area content-wrapper">

                <!-- ============ SIDEBAR ============ -->
                <aside class="adf-chat__sidebar">
                    <div class="adf-chat__sidebar-head">
                        <div class="adf-chat__sidebar-titlebar">
                            <h3 class="adf-chat__sidebar-title"><?php esc_html_e('Chat', 'adforest'); ?></h3>
                        </div>
                        <div class="adf-chat__search">
                            <input type="text" id="inlineFormInputGroup" placeholder="<?php esc_attr_e('Search', 'adforest'); ?>" aria-label="<?php esc_attr_e('Search', 'adforest'); ?>">
                        </div>
                    </div>

                    <?php
                    // Authoritative cursors for sidebar real-time updates:
                    //   data-max-conv-id : highest conversation id this user
                    //                      is in. Climbs when a buyer creates
                    //                      a new conversation from an ad page.
                    //   data-max-msg-id  : highest message id across ALL of
                    //                      this user's conversations. Climbs
                    //                      whenever a message arrives in any
                    //                      conversation (active or not), so
                    //                      the rail can re-order and patch
                    //                      previews / unread badges.
                    global $wpdb;
                    $__conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
                    $__msg_tbl_idx = $wpdb->prefix . 'sb_chat_messages';
                    $__max_conv_id = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT MAX(id) FROM $__conv_tbl WHERE user_1 = %d OR user_2 = %d",
                        $user_id, $user_id
                    ));
                    $__max_msg_id = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT MAX(m.id) FROM $__msg_tbl_idx m
                         INNER JOIN $__conv_tbl c ON c.id = m.conversation_id
                         WHERE c.user_1 = %d OR c.user_2 = %d",
                        $user_id, $user_id
                    ));
                    ?>
                    <div class="adf-chat__list" data-max-conv-id="<?php echo esc_attr($__max_conv_id); ?>" data-max-msg-id="<?php echo esc_attr($__max_msg_id); ?>">
                        <?php
                        /*
                         * data-context is intentionally NOT set to "inbox" / "user-dashboard"
                         * / "sbchat" because the plugin's admin-custom.js (line 462) starts a
                         * setInterval against those values that posts to inbox_reload_incoming_messages
                         * and overwrites .chat-list-detail's HTML with the plain dashboard markup
                         * (no preview, no time-right, no unread badge). Using "modern-inbox" keeps
                         * the rail intact. The "Load more" handler (also gated on this attribute)
                         * is fine because we render the whole list server-side and don't show
                         * the load-more button unless conversations exceed the prefetch limit.
                         */
                        ?>
                        <div class="messages-inbox chat-list" data-context="modern-inbox">
                            <?php
                            // Closure to render one conversation row, with rail-card styling.
                            $render_row = function ($conv, &$first_iteration) use ($user_id, &$first_conversation_id, $presence_map, $last_messages_map, $unread_count_map) {
                                $recipient_id    = ($user_id == $conv['user_2']) ? absint($conv['user_1']) : absint($conv['user_2']);
                                $recipient       = get_userdata($recipient_id);
                                $is_read         = function_exists('sbchat_get_conversation_status_check') ? sbchat_get_conversation_status_check($conv, $user_id) : true;
                                $relative_time   = (string) human_time_diff(strtotime($conv['updated']), current_time('timestamp', 1));
                                $conversation_id = isset($conv['id']) ? $conv['id'] : "";

                                if ($first_conversation_id == "") {
                                    $first_conversation_id = $conversation_id;
                                }

                                $is_pinned = function_exists('adforest_modern_chat_is_pinned') && adforest_modern_chat_is_pinned($conv, $user_id);
                                $is_online = !empty($presence_map[$recipient_id]);

                                $name = '';
                                if (!is_wp_error($recipient) && !empty($recipient)) {
                                    $name = $recipient->display_name ? $recipient->display_name : trim($recipient->first_name . ' ' . $recipient->last_name);
                                }
                                if ($name === '') $name = __('User has been removed', 'adforest');

                                // Last-message preview
                                $preview = '';
                                if (isset($last_messages_map[(int) $conversation_id])) {
                                    $lm = $last_messages_map[(int) $conversation_id];
                                    $raw = (string) $lm['message'];
                                    if ($raw !== '') {
                                        // Voice message marker → friendly preview
                                        if (preg_match('/^\s*\[adf-voice\].+?\[\/adf-voice\]\s*$/', $raw)) {
                                            $preview = __('🎤 Voice message', 'adforest');
                                        } else {
                                            // Strip the post-card prefix if present
                                            $parts = explode('|', $raw, 2);
                                            $body  = isset($parts[1]) ? $parts[1] : $parts[0];
                                            $body  = trim(wp_strip_all_tags($body));
                                            if ($body === '') $body = __('Shared a listing', 'adforest');
                                            $preview = $body;
                                        }
                                    } elseif (!empty($lm['attachment_ids'])) {
                                        $preview = __('📎 Attachment', 'adforest');
                                    }
                                    if ((int) $lm['sender_id'] === (int) $user_id && $preview !== '') {
                                        $preview = __('You: ', 'adforest') . $preview;
                                    }
                                }
                                if ($preview === '') $preview = __('No messages yet', 'adforest');
                                if (function_exists('mb_strlen') && mb_strlen($preview) > 60) {
                                    $preview = mb_substr($preview, 0, 60) . '…';
                                }

                                $row_classes = array('adf-chat__row');
                                if ($first_iteration) $row_classes[] = 'active';
                                if (!$is_read)        $row_classes[] = 'unread';
                                $first_iteration = false;

                                // Header-swap payload — when the user clicks this row, the
                                // modern .adf-chat__head must re-point to this recipient.
                                // The legacy plugin handler only updates .msg-head (absent
                                // in the modern layout), so JS reads these attrs instead.
                                $row_anonymized  = ((int) $recipient_id <= 0) || (function_exists('adforest_modern_chat_is_anonymized_conv') && adforest_modern_chat_is_anonymized_conv($conv));
                                $row_profile_url = ($recipient_id && !$row_anonymized) ? get_author_posts_url($recipient_id) : '';

                                // Ad payload for the "From {ad}" portion of the chat
                                // header on switch (mirrors $render_thread).
                                $row_ad_title = '';
                                $row_ad_url   = '';
                                $row_ad_state = 'unknown';
                                $row_ad_label = '';
                                if (function_exists('adforest_modern_chat_get_conversation_ad')) {
                                    $row_ad_info  = adforest_modern_chat_get_conversation_ad($conversation_id);
                                    if (!empty($row_ad_info['title'])) $row_ad_title = $row_ad_info['title'];
                                    if (!empty($row_ad_info['url']))   $row_ad_url   = $row_ad_info['url'];
                                    if (!empty($row_ad_info['state'])) $row_ad_state = $row_ad_info['state'];
                                    if (!empty($row_ad_info['label'])) $row_ad_label = $row_ad_info['label'];
                                }
                                ?>
                                <li class="<?php echo esc_attr(implode(' ', $row_classes)); ?>"
                                    data-id="<?php echo esc_attr($conv['id']); ?>"
                                    data-pinned="<?php echo $is_pinned ? '1' : '0'; ?>">
                                    <a target="_self"
                                       data-recipient_id="<?php echo esc_attr($recipient_id); ?>"
                                       data-conv="<?php echo esc_attr($conversation_id); ?>"
                                       data-recipient-name="<?php echo esc_attr($name); ?>"
                                       data-profile-url="<?php echo esc_attr($row_profile_url); ?>"
                                       data-anonymized="<?php echo $row_anonymized ? '1' : '0'; ?>"
                                       data-online="<?php echo $is_online ? '1' : '0'; ?>"
                                       data-ad-title="<?php echo esc_attr($row_ad_title); ?>"
                                       data-ad-url="<?php echo esc_attr($row_ad_url); ?>"
                                       data-ad-state="<?php echo esc_attr($row_ad_state); ?>"
                                       data-ad-label="<?php echo esc_attr($row_ad_label); ?>"
                                       href="javascript:void(0)"
                                       class="con-chat-list">
                                        <div class="adf-chat__avatar <?php echo $is_online ? 'is-online' : ''; ?>">
                                            <?php
                                            if (function_exists('sbchat_get_user_avatar')) {
                                                echo sbchat_get_user_avatar($recipient_id, 42);
                                            } else {
                                                echo get_avatar($recipient_id, 42);
                                            }
                                            ?>
                                        </div>
                                        <div class="adf-chat__row-body">
                                            <div class="adf-chat__row-top">
                                                <h3 class="adf-chat__row-name sender-details"><?php echo esc_html($name); ?></h3>
                                                <span class="adf-chat__row-time"><?php echo esc_html($relative_time); ?></span>
                                            </div>
                                            <div class="adf-chat__row-bottom">
                                                <p class="adf-chat__row-preview"><?php echo esc_html($preview); ?></p>
                                                <?php if (!$is_read) :
                                                    $unread_n = isset($unread_count_map[(int) $conversation_id]) ? (int) $unread_count_map[(int) $conversation_id] : 1;
                                                    $unread_label = $unread_n > 9 ? '9+' : (string) max(1, $unread_n);
                                                ?>
                                                    <span class="adf-chat__row-badge"><?php echo esc_html($unread_label); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                    <button type="button" class="adf-chat__row-pin" aria-label="<?php esc_attr_e('Pin / Unpin', 'adforest'); ?>" data-conv="<?php echo esc_attr($conversation_id); ?>">
                                        <i class="fa fa-thumbtack"></i>
                                    </button>
                                </li>
                                <?php
                            };

                            // Thread sub-row renderer — used inside a multi-conversation buyer card
                            $render_thread = function ($conv, $is_active) use ($user_id, $last_messages_map, $unread_count_map, &$first_conversation_id, $presence_map) {
                                $conversation_id = $conv['id'];
                                // Mirror $render_row: claim the first-seen conv id
                                // so the right pane initial load matches the row
                                // visually highlighted as active in the sidebar.
                                if ($first_conversation_id === '' || $first_conversation_id === 0) {
                                    $first_conversation_id = $conversation_id;
                                }
                                $recipient_id    = ($user_id == $conv['user_2']) ? (int) $conv['user_1'] : (int) $conv['user_2'];
                                $post_id         = !empty($conv['post_id']) ? (int) $conv['post_id'] : 0;
                                $ad_title        = '';
                                $ad_thumb        = '';
                                if ($post_id > 0) {
                                    $ad_title = get_the_title($post_id);
                                    // AdForest ad listings skip the WP featured
                                    // image — they store images via the theme's
                                    // adforest_get_ad_images() helper, which
                                    // reads `_sb_photo_arrangement_` (CSV of
                                    // attachment IDs) or falls back to
                                    // get_attached_media().
                                    $ad_thumb = get_the_post_thumbnail_url($post_id, array(40, 40));
                                    if (empty($ad_thumb) && function_exists('adforest_get_ad_images')) {
                                        $imgs = adforest_get_ad_images($post_id);
                                        if (!empty($imgs)) {
                                            $first = reset($imgs);
                                            // Arrangement → ID; get_attached_media → WP_Post object
                                            if (is_object($first) && isset($first->ID)) {
                                                $ad_thumb = wp_get_attachment_image_url((int) $first->ID, array(40, 40));
                                            } elseif (is_numeric($first)) {
                                                $ad_thumb = wp_get_attachment_image_url((int) $first, array(40, 40));
                                            }
                                        }
                                    }
                                }
                                // Fall back to scraping the first message's
                                // embedded post-card. Used for legacy threads
                                // (post_id NULL) AND for ad listings whose
                                // thumbnail meta couldn't be resolved.
                                if (($ad_title === '' || empty($ad_thumb)) && function_exists('adforest_modern_chat_get_conversation_ad')) {
                                    $scraped = adforest_modern_chat_get_conversation_ad($conversation_id);
                                    if ($ad_title === '' && !empty($scraped['title'])) $ad_title = $scraped['title'];
                                    if (empty($ad_thumb) && !empty($scraped['image'])) $ad_thumb = $scraped['image'];
                                }
                                if ($ad_title === '') $ad_title = __('General', 'adforest');
                                $is_read         = function_exists('sbchat_get_conversation_status_check') ? sbchat_get_conversation_status_check($conv, $user_id) : true;
                                $relative_time   = (string) human_time_diff(strtotime($conv['updated']), current_time('timestamp', 1));

                                $preview = '';
                                if (isset($last_messages_map[(int) $conversation_id])) {
                                    $lm  = $last_messages_map[(int) $conversation_id];
                                    $raw = (string) $lm['message'];
                                    if ($raw !== '') {
                                        if (preg_match('/^\s*\[adf-voice\].+?\[\/adf-voice\]\s*$/', $raw)) {
                                            $preview = __('🎤 Voice message', 'adforest');
                                        } else {
                                            $parts = explode('|', $raw, 2);
                                            $body  = isset($parts[1]) ? $parts[1] : $parts[0];
                                            $body  = trim(wp_strip_all_tags($body));
                                            if ($body === '') $body = __('Shared a listing', 'adforest');
                                            $preview = $body;
                                        }
                                    } elseif (!empty($lm['attachment_ids'])) {
                                        $preview = __('📎 Attachment', 'adforest');
                                    }
                                    if ((int) $lm['sender_id'] === (int) $user_id && $preview !== '') {
                                        $preview = __('You: ', 'adforest') . $preview;
                                    }
                                }
                                if ($preview === '') $preview = __('No messages yet', 'adforest');
                                if (function_exists('mb_strlen') && mb_strlen($preview) > 60) $preview = mb_substr($preview, 0, 60) . '…';

                                $row_classes = array('adf-chat__row', 'adf-chat__row--thread');
                                if ($is_active) $row_classes[] = 'active';
                                if (!$is_read)  $row_classes[] = 'unread';

                                // Header-swap payload — see $render_row for context.
                                // Thread rows show the ad title in the rail, but the
                                // chat header still binds to the buyer behind them.
                                $thread_recipient = ($recipient_id > 0) ? get_userdata($recipient_id) : null;
                                $thread_name = '';
                                if ($thread_recipient && !is_wp_error($thread_recipient)) {
                                    $thread_name = $thread_recipient->display_name ? $thread_recipient->display_name : trim($thread_recipient->first_name . ' ' . $thread_recipient->last_name);
                                }
                                if ($thread_name === '') $thread_name = __('User has been removed', 'adforest');
                                $thread_anonymized  = ((int) $recipient_id <= 0) || (function_exists('adforest_modern_chat_is_anonymized_conv') && adforest_modern_chat_is_anonymized_conv($conv));
                                $thread_profile_url = ($recipient_id && !$thread_anonymized) ? get_author_posts_url($recipient_id) : '';

                                // Ad payload for the "From {ad}" portion of the chat
                                // header on switch. $post_id resolves to a live ad
                                // (with state/label); legacy convs (no post_id) fall
                                // back to the scraped title — no url/state available.
                                $thread_ad_url   = '';
                                $thread_ad_state = 'unknown';
                                $thread_ad_label = '';
                                if (function_exists('adforest_modern_chat_get_conversation_ad')) {
                                    $thread_ad_info  = adforest_modern_chat_get_conversation_ad($conversation_id);
                                    if (!empty($thread_ad_info['url']))   $thread_ad_url   = $thread_ad_info['url'];
                                    if (!empty($thread_ad_info['state'])) $thread_ad_state = $thread_ad_info['state'];
                                    if (!empty($thread_ad_info['label'])) $thread_ad_label = $thread_ad_info['label'];
                                }
                                ?>
                                <li class="<?php echo esc_attr(implode(' ', $row_classes)); ?>" data-id="<?php echo esc_attr($conversation_id); ?>">
                                    <a target="_self"
                                       data-recipient_id="<?php echo esc_attr($recipient_id); ?>"
                                       data-conv="<?php echo esc_attr($conversation_id); ?>"
                                       data-recipient-name="<?php echo esc_attr($thread_name); ?>"
                                       data-profile-url="<?php echo esc_attr($thread_profile_url); ?>"
                                       data-anonymized="<?php echo $thread_anonymized ? '1' : '0'; ?>"
                                       data-online="<?php echo (!empty($presence_map[$recipient_id])) ? '1' : '0'; ?>"
                                       data-ad-title="<?php echo esc_attr($ad_title); ?>"
                                       data-ad-url="<?php echo esc_attr($thread_ad_url); ?>"
                                       data-ad-state="<?php echo esc_attr($thread_ad_state); ?>"
                                       data-ad-label="<?php echo esc_attr($thread_ad_label); ?>"
                                       href="javascript:void(0)"
                                       class="con-chat-list">
                                        <div class="adf-thread-thumb<?php echo $ad_thumb ? '' : ' is-placeholder'; ?>">
                                            <?php if ($ad_thumb) : ?>
                                                <img src="<?php echo esc_url($ad_thumb); ?>" alt="">
                                            <?php else : ?>
                                                <i class="fa fa-tag" aria-hidden="true"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="adf-chat__row-body">
                                            <div class="adf-chat__row-top">
                                                <h4 class="adf-chat__row-name"><?php echo esc_html($ad_title); ?></h4>
                                                <span class="adf-chat__row-time"><?php echo esc_html($relative_time); ?></span>
                                            </div>
                                            <div class="adf-chat__row-bottom">
                                                <p class="adf-chat__row-preview"><?php echo esc_html($preview); ?></p>
                                                <?php if (!$is_read) : ?>
                                                    <span class="adf-chat__row-badge">
                                                    <?php
                                                        $unread_n = isset($unread_count_map[(int) $conversation_id]) ? (int) $unread_count_map[(int) $conversation_id] : 1;
                                                        echo esc_html($unread_n > 9 ? '9+' : (string) max(1, $unread_n));
                                                    ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <?php
                            };

                            // Multi-conversation buyer block: header pseudo-row + thread sub-rows + show-more
                            $render_buyer_block = function ($buyer_id, $convs, &$first_iteration) use ($presence_map, $render_thread) {
                                $buyer = get_userdata($buyer_id);
                                $buyer_name = '';
                                if (!is_wp_error($buyer) && !empty($buyer)) {
                                    $buyer_name = $buyer->display_name ? $buyer->display_name : trim($buyer->first_name . ' ' . $buyer->last_name);
                                }
                                if ($buyer_name === '') $buyer_name = __('User', 'adforest');
                                $is_online = !empty($presence_map[$buyer_id]);
                                $count     = count($convs);
                                $extra     = max(0, $count - 3);

                                // Most-recent activity time across the buyer's conversations
                                // — $convs is already sorted DESC by the buyer_groups_for
                                // sorter, so $convs[0] is the freshest.
                                $latest_conv = isset($convs[0]) ? $convs[0] : null;
                                $latest_time = $latest_conv ? (string) human_time_diff(strtotime($latest_conv['updated']), current_time('timestamp', 1)) : '';
                                ?>
                                <li class="adf-chat__buyer-head" data-buyer-id="<?php echo esc_attr($buyer_id); ?>" role="button" tabindex="0" aria-expanded="true" aria-controls="adf-buyer-group-<?php echo esc_attr($buyer_id); ?>">
                                    <div class="adf-chat__avatar <?php echo $is_online ? 'is-online' : ''; ?>">
                                        <?php
                                        if (function_exists('sbchat_get_user_avatar')) {
                                            echo sbchat_get_user_avatar($buyer_id, 42);
                                        } else {
                                            echo get_avatar($buyer_id, 42);
                                        }
                                        ?>
                                    </div>
                                    <div class="adf-chat__row-body">
                                        <div class="adf-chat__row-top">
                                            <h3 class="adf-chat__row-name"><?php echo esc_html($buyer_name); ?></h3>
                                            <?php if ($latest_time !== '') : ?>
                                                <span class="adf-chat__row-time"><?php echo esc_html($latest_time); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="adf-chat__row-bottom">
                                            <span class="adf-chat__buyer-count-pill" aria-label="<?php echo esc_attr(sprintf(_n('%d ad', '%d ads', $count, 'adforest'), $count)); ?>">
                                                <?php echo esc_html(sprintf(_n('%d ad', '%d ads', $count, 'adforest'), $count)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <i class="fa fa-chevron-down adf-chat__buyer-toggle" aria-hidden="true"></i>
                                </li>
                                <?php
                                $i = 0;
                                foreach ($convs as $conv) {
                                    $thread_active = false;
                                    if ($first_iteration) { $thread_active = true; $first_iteration = false; }
                                    $hidden = $i >= 3 ? ' is-collapsed' : '';
                                    if ($hidden) {
                                        // Wrap into a hidden marker without breaking <li> structure
                                        echo '<li class="adf-chat__row adf-chat__row--thread is-collapsed" data-buyer-id="' . esc_attr($buyer_id) . '" hidden>';
                                        // Re-render content directly to keep DOM consistent
                                        ob_start();
                                        $render_thread($conv, $thread_active);
                                        $rendered = ob_get_clean();
                                        // The closure emits its own <li>; we want to merge the inner without nested <li>.
                                        // Strip the outer <li>...</li> and re-emit content inside our hidden <li>.
                                        $inner = preg_replace('/^\s*<li[^>]*>/', '', $rendered, 1);
                                        $inner = preg_replace('/<\/li>\s*$/', '', $inner, 1);
                                        echo $inner;
                                        echo '</li>';
                                    } else {
                                        // Visible — let the closure emit its own <li> with proper data-id
                                        ob_start();
                                        $render_thread($conv, $thread_active);
                                        $rendered = ob_get_clean();
                                        // Tag the buyer-id for grouping CSS / JS
                                        $rendered = preg_replace('/^(\s*<li\b[^>]*)/', '$1 data-buyer-id="' . esc_attr($buyer_id) . '"', $rendered, 1);
                                        echo $rendered;
                                    }
                                    $i++;
                                }
                                if ($extra > 0) {
                                    ?>
                                    <li class="adf-chat__buyer-more-row" data-buyer-id="<?php echo esc_attr($buyer_id); ?>">
                                        <button type="button" class="adf-chat__buyer-more" aria-expanded="false">
                                            <?php printf(esc_html__('+%d more', 'adforest'), $extra); ?>
                                        </button>
                                    </li>
                                    <?php
                                }
                            };

                            $first_iteration = true;
                            ?>

                            <?php if (!empty($grouped_convs['pinned'])) : ?>
                                <div class="adf-chat__section-label"><?php esc_html_e('PINNED', 'adforest'); ?></div>
                                <ul class="chat-list-detail">
                                    <?php foreach ($buyer_groups_pinned as $bid => $convs) :
                                        if (count($convs) === 1) $render_row($convs[0], $first_iteration);
                                        else                     $render_buyer_block($bid, $convs, $first_iteration);
                                    endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (!empty($grouped_convs['all'])) : ?>
                                <?php if (!empty($grouped_convs['pinned'])) : ?>
                                    <div class="adf-chat__section-label"><?php esc_html_e('ALL', 'adforest'); ?></div>
                                <?php endif; ?>
                                <ul class="chat-list-detail">
                                    <?php foreach ($buyer_groups_all as $bid => $convs) :
                                        if (count($convs) === 1) $render_row($convs[0], $first_iteration);
                                        else                     $render_buyer_block($bid, $convs, $first_iteration);
                                    endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (count($user_conversations) > $display_limit) : ?>
                                <button type="button" class="adf-chat__loadmore load-conversations" data-limit="<?php echo esc_attr($display_limit); ?>" data-offset="<?php echo esc_attr($display_limit); ?>">
                                    <?php esc_html_e('Load more', 'adforest'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>

                <!-- ============ THREAD ============ -->
                <main class="adf-chat__thread">
                    <div class="adf-chat__thread-inner">
                        <?php
                        $conversation_id      = (isset($_GET['conversation_id']) && !empty($_GET['conversation_id'])) ? esc_html($_GET['conversation_id']) : $first_conversation_id;
                        $current_conversation = false;
                        if ($conversation_id !== 0 && !empty($conversation_id) && function_exists('sbchat_get_conversation_by_id')) {
                            $current_conversation = sbchat_get_conversation_by_id($conversation_id);
                        }

                        if (!$current_conversation) :
                        ?>
                            <div class="adf-chat__head"></div>
                            <div id="sbModalBody">
                                <div class="adf-chat__thread-empty">
                                    <i class="fa fa-comments"></i>
                                    <p><?php esc_html_e('Select a conversation to start chatting.', 'adforest'); ?></p>
                                </div>
                                <div class="msg-body" style="display:none;">
                                    <ul class="messages-list"></ul>
                                </div>
                            </div>
                            <div class="adf-chat__composer send-box chat-footer" style="display:none;">
                                <h4 class="not-found"></h4>
                            </div>
                        <?php
                        else :
                            $u1 = isset($current_conversation['user_1']) ? $current_conversation['user_1'] : 0;
                            $u2 = isset($current_conversation['user_2']) ? $current_conversation['user_2'] : 0;
                            if ($user_id == $u1 || $user_id == $u2) :
                                $recipient_id = ($u1 == $user_id) ? $u2 : $u1;
                                $recipient    = get_userdata($recipient_id);
                                $recipient_output = '';
                                if (!is_wp_error($recipient) && isset($recipient->ID)) {
                                    $recipient_output = $recipient->display_name ? $recipient->display_name : trim($recipient->first_name . ' ' . $recipient->last_name);
                                }
                                if ($recipient_output === '') $recipient_output = __('User has been removed', 'adforest');

                                // Anonymized participant detection — recipient_id resolves
                                // to 0 when the OTHER party's WP account was deleted (the
                                // deleted_user hook in sb-chat-modern-extensions.php sets
                                // sender_id / user_X = 0). Disables outbound interaction
                                // while preserving the full conversation history.
                                $recipient_anonymized = ((int) $recipient_id <= 0)
                                    || (function_exists('adforest_modern_chat_is_anonymized_conv') && adforest_modern_chat_is_anonymized_conv($current_conversation));

                                // Per-user block state — used to swap the composer for a
                                // banner and label the overflow menu (Block ↔ Unblock).
                                $is_blocked_by_me   = !$recipient_anonymized && function_exists('adforest_modern_chat_user_blocks') && adforest_modern_chat_user_blocks($user_id, $recipient_id);
                                $is_blocked_by_them = !$recipient_anonymized && function_exists('adforest_modern_chat_user_blocks') && adforest_modern_chat_user_blocks($recipient_id, $user_id);
                                $is_block_active    = $is_blocked_by_me || $is_blocked_by_them;

                                $recipient_is_online = !$recipient_anonymized && function_exists('adforest_modern_chat_is_online') && adforest_modern_chat_is_online($recipient_id);
                                $outgoing_state      = function_exists('adforest_modern_chat_outgoing_state') ? adforest_modern_chat_outgoing_state($current_conversation, $user_id) : '';
                                $recipient_profile_url = ($recipient_id && !$recipient_anonymized) ? get_author_posts_url($recipient_id) : '';
                                $ad_info             = function_exists('adforest_modern_chat_get_conversation_ad') ? adforest_modern_chat_get_conversation_ad($conversation_id) : null;
                        ?>
                            <header class="adf-chat__head">
                                <div class="adf-chat__head-left">
                                    <button type="button" class="adf-chat__head-back" aria-label="<?php esc_attr_e('Back', 'adforest'); ?>"><i class="fa fa-arrow-left"></i></button>
                                    <div class="adf-chat__avatar <?php echo $recipient_is_online ? 'is-online' : ''; ?>">
                                        <?php
                                        if (function_exists('sbchat_get_user_avatar')) {
                                            echo sbchat_get_user_avatar($recipient_id, 40);
                                        } else {
                                            echo get_avatar($recipient_id, 40);
                                        }
                                        ?>
                                    </div>
                                    <div class="adf-chat__head-info">
                                        <h2 class="adf-chat__head-name"><?php echo esc_html($recipient_output); ?></h2>
                                        <p class="adf-chat__head-meta <?php echo $recipient_is_online ? 'is-online' : ''; ?>">
                                            <span class="dot"></span>
                                            <?php echo $recipient_is_online ? esc_html__('Online', 'adforest') : esc_html__('Offline', 'adforest'); ?>
                                            <?php if (!empty($ad_info) && !empty($ad_info['title'])) :
                                                $__ad_state = isset($ad_info['state']) ? $ad_info['state'] : 'unknown';
                                                $__ad_label = isset($ad_info['label']) ? $ad_info['label'] : '';
                                                // Neutralize the ad link when the post is gone or in a
                                                // non-public state — avoids 404s and "ghost-clickable"
                                                // affordances. Expired ads keep the link clickable so
                                                // sellers can still hit the ad page (and possibly re-list);
                                                // the theme's expiry view decides what to render there.
                                                $__ad_link_safe = !empty($ad_info['url']) && in_array($__ad_state, array('live', 'expired', 'unknown'), true);
                                            ?>
                                                <span class="sep" aria-hidden="true">·</span>
                                                <?php esc_html_e('From', 'adforest'); ?>
                                                <?php if ($__ad_link_safe) : ?>
                                                    <a href="<?php echo esc_url($ad_info['url']); ?>" target="_blank" rel="noopener" class="adf-chat__head-ad-link adf-chat__head-ad-link--<?php echo esc_attr($__ad_state); ?>"><?php echo esc_html($ad_info['title']); ?></a>
                                                <?php else : ?>
                                                    <strong class="adf-chat__head-ad-link adf-chat__head-ad-link--<?php echo esc_attr($__ad_state); ?>"><?php echo esc_html($ad_info['title']); ?></strong>
                                                <?php endif; ?>
                                                <?php if ($__ad_label !== '') : ?>
                                                    <span class="adf-chat__head-ad-state adf-chat__head-ad-state--<?php echo esc_attr($__ad_state); ?>">(<?php echo esc_html(strtolower($__ad_label)); ?>)</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="adf-chat__head-actions" data-conv="<?php echo esc_attr($conversation_id); ?>" data-outgoing-state="<?php echo esc_attr($outgoing_state); ?>" data-anonymized="<?php echo $recipient_anonymized ? '1' : '0'; ?>" data-blocked-by-me="<?php echo $is_blocked_by_me ? '1' : '0'; ?>" data-blocked-by-them="<?php echo $is_blocked_by_them ? '1' : '0'; ?>" data-recipient-id="<?php echo esc_attr($recipient_id); ?>">
                                    <?php if ($recipient_profile_url && !$recipient_anonymized) : ?>
                                        <a href="<?php echo esc_url($recipient_profile_url); ?>" class="adf-chat__btn-ghost" target="_blank" rel="noopener">
                                            <i class="fa fa-user"></i> <?php esc_html_e('Profile', 'adforest'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$recipient_anonymized && (int) $recipient_id > 0) : ?>
                                        <div class="adf-chat__overflow">
                                            <button type="button" class="adf-chat__btn-icon adf-chat__overflow-btn" aria-label="<?php esc_attr_e('More options', 'adforest'); ?>" aria-haspopup="menu" aria-expanded="false">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="adf-chat__overflow-menu" role="menu" hidden>
                                                <li role="none">
                                                    <button type="button" role="menuitem" class="adf-chat__menu-item adf-chat__menu-item--block" data-action="<?php echo $is_blocked_by_me ? 'unblock' : 'block'; ?>">
                                                        <i class="fa <?php echo $is_blocked_by_me ? 'fa-undo' : 'fa-ban'; ?>" aria-hidden="true"></i>
                                                        <span class="adf-chat__menu-label"><?php echo $is_blocked_by_me ? esc_html__('Unblock user', 'adforest') : esc_html__('Block user', 'adforest'); ?></span>
                                                    </button>
                                                </li>
                                                <li role="none">
                                                    <button type="button" role="menuitem" class="adf-chat__menu-item adf-chat__menu-item--report" data-action="report">
                                                        <i class="fa fa-flag" aria-hidden="true"></i>
                                                        <span><?php esc_html_e('Report user', 'adforest'); ?></span>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <button type="button" class="adf-chat__btn-icon delete-single-chat" data-delete="<?php esc_attr_e('Are you sure you want to remove this?', 'adforest'); ?>" aria-label="<?php esc_attr_e('Delete conversation', 'adforest'); ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    <div class="sb-notification success" style="display:none;"><p><?php esc_html_e('Conversation is removed', 'adforest'); ?></p></div>
                                </div>
                            </header>

                            <div id="sbModalBody" class="adf-chat__body" data-ad-state="<?php echo esc_attr(!empty($ad_info['state']) ? $ad_info['state'] : 'unknown'); ?>">
                                <div class="message-spin-loader">
                                    <i class="mdi mdi-loading fa-spin booking-preloader"></i>
                                </div>
                                <div class="msg-body" style="display:contents;">
                                    <button type="button" class="adf-chat__load-older" hidden>
                                        <i class="fa fa-arrow-up" aria-hidden="true"></i>
                                        <?php esc_html_e('Load older messages', 'adforest'); ?>
                                    </button>
                                    <?php
                                        // Authoritative cursor for incremental polling — without
                                        // it, the first poll would treat since_id=0 and re-fetch
                                        // the entire conversation, duplicating bubbles after the
                                        // append-only refactor. The poll endpoint always returns
                                        // a server-side latest_id which the client uses to update
                                        // this cursor as new messages arrive.
                                        global $wpdb;
                                        $__msg_tbl = $wpdb->prefix . 'sb_chat_messages';
                                        $__latest_msg_id = (int) $wpdb->get_var($wpdb->prepare(
                                            "SELECT MAX(id) FROM $__msg_tbl WHERE conversation_id = %d",
                                            (int) $conversation_id
                                        ));
                                    ?>
                                    <ul class="messages-list" data-latest-id="<?php echo esc_attr($__latest_msg_id); ?>"><?php
                                        if (function_exists('sbchat_get_inbox_conversations')) {
                                            // Plugin's attachment renderer doesn't null-check
                                            // wp_get_attachment_image_src(), so a stale media
                                            // row leaks an Xdebug warning table into the
                                            // output. Lower the error filter for the call and
                                            // discard any non-bubble HTML caught by ob_start.
                                            $__prev = error_reporting();
                                            error_reporting($__prev & ~E_WARNING & ~E_NOTICE & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_STRICT);
                                            ob_start();
                                            $inbox = sbchat_get_inbox_conversations($user_id, $conversation_id);
                                            $__stray = ob_get_clean();
                                            error_reporting($__prev);
                                            if (!empty($inbox) && function_exists('sbchat_inbox_conversations_allowed_html')) {
                                                echo wp_kses($inbox, sbchat_inbox_conversations_allowed_html());
                                            }
                                            unset($__stray);
                                        }
                                    ?></ul>
                                    <div class="adf-typing" hidden aria-hidden="true">
                                        <div class="adf-typing__bubble">
                                            <span class="adf-typing__dot"></span>
                                            <span class="adf-typing__dot"></span>
                                            <span class="adf-typing__dot"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($recipient_anonymized) : ?>
                                <footer class="adf-chat__composer adf-chat__composer--disabled" aria-disabled="true">
                                    <div class="adf-chat__deleted-banner" role="status">
                                        <i class="fa fa-user-slash" aria-hidden="true"></i>
                                        <span><?php esc_html_e('This user is no longer available. You can no longer send messages in this conversation.', 'adforest'); ?></span>
                                    </div>
                                </footer>
                            <?php elseif ($is_blocked_by_me) : ?>
                                <footer class="adf-chat__composer adf-chat__composer--disabled adf-chat__composer--blocked" aria-disabled="true">
                                    <div class="adf-chat__deleted-banner adf-chat__deleted-banner--block" role="status">
                                        <i class="fa fa-ban" aria-hidden="true"></i>
                                        <span><?php esc_html_e('You blocked this user. They can no longer message you. Use the menu above to unblock.', 'adforest'); ?></span>
                                    </div>
                                </footer>
                            <?php elseif ($is_blocked_by_them) : ?>
                                <footer class="adf-chat__composer adf-chat__composer--disabled adf-chat__composer--blocked-by-them" aria-disabled="true">
                                    <div class="adf-chat__deleted-banner adf-chat__deleted-banner--block" role="status">
                                        <i class="fa fa-ban" aria-hidden="true"></i>
                                        <span><?php esc_html_e('Messages cannot be delivered to this user.', 'adforest'); ?></span>
                                    </div>
                                </footer>
                            <?php else : ?>
                            <footer class="adf-chat__composer send-box chat-footer">
                                <form action="" class="send-message" enctype="multipart/form-data">
                                    <div class="send-message-box">
                                        <div id="sbchat-mu" class="sbchat_upload_items mdi mdi-paperclip" title="<?php esc_attr_e('Attach file', 'adforest'); ?>"></div>
                                        <input type="text" id="message_box" class="form-control message-details" aria-label="message" placeholder="<?php esc_attr_e('Write message…', 'adforest'); ?>">
                                        <button type="button" class="adf-composer-icon adf-composer-emoji" aria-label="<?php esc_attr_e('Emoji', 'adforest'); ?>"><i class="fa fa-smile"></i></button>
                                        <span class="adf-recording-bar" aria-hidden="true">
                                            <span class="dot"></span>
                                            <span class="timer">0:00</span>
                                            <span class="hint"><?php esc_html_e('Tap mic to send', 'adforest'); ?></span>
                                        </span>
                                        <button type="button" class="adf-rec-cancel" aria-label="<?php esc_attr_e('Cancel recording', 'adforest'); ?>"><i class="fa fa-times"></i></button>
                                        <button type="button" class="adf-composer-icon adf-composer-mic" aria-label="<?php esc_attr_e('Voice message', 'adforest'); ?>"><i class="fa fa-microphone"></i></button>
                                        <button class="main-btn" type="submit" aria-label="<?php esc_attr_e('Send', 'adforest'); ?>">
                                            <i class="mdi mdi-send-circle" aria-hidden="true"></i>
                                        </button>
                                        <input type="hidden" id="conversation_id" name="conversation_id" value="<?php echo esc_attr($conversation_id); ?>">
                                        <input type="hidden" id="recipient_id" name="recipient_id" value="<?php echo esc_attr($recipient_id); ?>">
                                    </div>
                                    <div class="dropzone-settings" style="display:none;"><?php
                                        $plugin_options = array();
                                        if (get_option('sb_plugin_options') !== false) {
                                            $plugin_options = get_option('sb_plugin_options');
                                        }
                                        if (is_array($plugin_options) && count($plugin_options) > 0) {
                                            $allowed_mime_types = isset($plugin_options['sbchat_allowed_mime_types']) ? $plugin_options['sbchat_allowed_mime_types'] : array();
                                            $max_file_size      = isset($plugin_options['sb_max_file_size']) ? $plugin_options['sb_max_file_size'] : 0;
                                            $max_files_upload   = isset($plugin_options['sbchat_max_files_upload']) ? $plugin_options['sbchat_max_files_upload'] : 0;
                                            $allowed_mime_types = (is_array($allowed_mime_types) && count($allowed_mime_types) > 0) ? implode(',', $allowed_mime_types) : '';
                                            $max_file_size      = (!empty($max_file_size) && $max_file_size > 0) ? absint($max_file_size / 1024) : 1;
                                            $max_files_upload   = (!empty($max_files_upload) && $max_files_upload > 0) ? absint($max_files_upload) : 7;
                                        ?>
                                        <input type="hidden" id="dz_max_file_size" value="<?php echo esc_attr($max_file_size); ?>" />
                                        <input type="hidden" id="dz_max_files_upload" value="<?php echo esc_attr($max_files_upload); ?>" />
                                        <input type="hidden" id="dz_allowed_mime_types" value="<?php echo esc_attr($allowed_mime_types); ?>" />
                                        <?php } ?>
                                    </div>
                                    <div id="attachment-wrapper" class="attachment-wrapper_main"></div>
                                </form>
                            </footer>
                            <?php endif; // recipient_anonymized swap ?>
                        <?php endif; // user is participant ?>
                        <?php endif; // current conversation ?>
                    </div>
                </main>

            </div>

        <?php endif; ?>

    </div>
</div>

<?php /* ----- Modals (Block confirm + Report) — appended once at page end ----- */ ?>
<div class="adf-chat__modal" id="adf-block-confirm" hidden role="dialog" aria-modal="true" aria-labelledby="adf-block-confirm-title">
    <div class="adf-chat__modal-backdrop" data-modal-close></div>
    <div class="adf-chat__modal-card" role="document">
        <h3 id="adf-block-confirm-title" class="adf-chat__modal-title"><?php esc_html_e('Block this user?', 'adforest'); ?></h3>
        <p class="adf-chat__modal-body">
            <?php esc_html_e('They won\'t be able to message you on any of your ads. You can unblock them later from this conversation\'s menu.', 'adforest'); ?>
        </p>
        <div class="adf-chat__modal-actions">
            <button type="button" class="adf-chat__modal-btn adf-chat__modal-btn--ghost" data-modal-close><?php esc_html_e('Cancel', 'adforest'); ?></button>
            <button type="button" class="adf-chat__modal-btn adf-chat__modal-btn--danger" id="adf-block-confirm-go"><?php esc_html_e('Block user', 'adforest'); ?></button>
        </div>
    </div>
</div>

<div class="adf-chat__modal" id="adf-report-modal" hidden role="dialog" aria-modal="true" aria-labelledby="adf-report-modal-title">
    <div class="adf-chat__modal-backdrop" data-modal-close></div>
    <div class="adf-chat__modal-card" role="document">
        <h3 id="adf-report-modal-title" class="adf-chat__modal-title"><?php esc_html_e('Report user', 'adforest'); ?></h3>
        <p class="adf-chat__modal-body"><?php esc_html_e('Tell us what\'s wrong. Our team will review the conversation.', 'adforest'); ?></p>
        <form id="adf-report-form" class="adf-chat__modal-form">
            <label class="adf-chat__modal-label" for="adf-report-category"><?php esc_html_e('Reason', 'adforest'); ?></label>
            <select id="adf-report-category" name="category" class="adf-chat__modal-select" required>
                <option value="spam"><?php esc_html_e('Spam or unwanted promotion', 'adforest'); ?></option>
                <option value="scam"><?php esc_html_e('Scam or fraud', 'adforest'); ?></option>
                <option value="harassment"><?php esc_html_e('Harassment or abuse', 'adforest'); ?></option>
                <option value="inappropriate"><?php esc_html_e('Inappropriate content', 'adforest'); ?></option>
                <option value="other"><?php esc_html_e('Something else', 'adforest'); ?></option>
            </select>
            <label class="adf-chat__modal-label" for="adf-report-reason"><?php esc_html_e('Details (optional)', 'adforest'); ?></label>
            <textarea id="adf-report-reason" name="reason" rows="4" class="adf-chat__modal-textarea" placeholder="<?php esc_attr_e('Add any helpful context — links, screenshots\' descriptions, etc.', 'adforest'); ?>"></textarea>
            <div class="adf-chat__modal-actions">
                <button type="button" class="adf-chat__modal-btn adf-chat__modal-btn--ghost" data-modal-close><?php esc_html_e('Cancel', 'adforest'); ?></button>
                <button type="submit" class="adf-chat__modal-btn adf-chat__modal-btn--primary"><?php esc_html_e('Submit report', 'adforest'); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="adf-chat__toast" id="adf-chat-toast" hidden role="status" aria-live="polite"></div>

<script>
jQuery(document).ready(function($) {
    var $shell = $('.adf-chat');
    if (!$shell.length) return;

    var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    var nonce   = '<?php echo esc_js($adforest_chat_modern_nonce); ?>';

    /* ─── Block any plugin AJAX that would overwrite our rich rail ─── */
    $(document).ajaxSend(function(e, xhr, options) {
        var data = (options && options.data) || '';
        var hit  = false;
        if (typeof data === 'string') {
            hit = data.indexOf('action=inbox_reload_incoming_messages') !== -1
               || data.indexOf('action=load_conversations_list') !== -1;
        } else if (data && typeof data === 'object') {
            hit = data.action === 'inbox_reload_incoming_messages'
               || data.action === 'load_conversations_list';
        }
        if (hit) xhr.abort();
    });

    /* ─── Snapshot the server-rendered rail and restore it if anything wipes it ─── */
    var $rail = $shell.find('.messages-inbox.chat-list');
    var railHtml = $rail.length ? $rail.html() : '';
    if ($rail.length && window.MutationObserver) {
        var railObs = new MutationObserver(function() {
            // If the rail no longer contains a single .adf-chat__row, the plugin
            // replaced it with its plain markup — put ours back.
            if (!$rail.find('.adf-chat__row').length && railHtml) {
                $rail.html(railHtml);
            }
        });
        railObs.observe($rail[0], { childList: true, subtree: true });
    }

    /* ─── Sidebar search — delegated on $shell so it survives any rail
        re-render (rail-snapshot restore, polling, MutationObserver). ─── */
    var searchTimer = null;
    var lastSearchQuery = '';

    function searchEmptyEl() {
        var $listRoot = $shell.find('.adf-chat__list');
        var $existing = $listRoot.children('.adf-chat__search-empty');
        if (!$existing.length) {
            $existing = $('<div class="adf-chat__search-empty" hidden><?php echo esc_js(__('No conversations found', 'adforest')); ?></div>');
            $listRoot.append($existing);
        }
        return $existing;
    }

    function applyConvSearch(query) {
        query = (query || '').trim().toLowerCase();
        lastSearchQuery = query;
        var $rows   = $shell.find('.adf-chat__row');
        var $heads  = $shell.find('.adf-chat__buyer-head');
        var $more   = $shell.find('.adf-chat__buyer-more-row');
        var $labels = $shell.find('.adf-chat__section-label');
        var $groups = $shell.find('.adf-chat__list ul.chat-list-detail');
        if (!query) {
            // Clear inline display styles so class-based rules take over —
            // critical for .is-buyer-collapsed (accordion) and .is-collapsed
            // (+N more pagination), which use CSS display:none. Using .show()
            // here would override those and break the user's collapse state.
            $rows.css('display', '');
            $heads.css('display', '');
            $more.css('display', '');
            $labels.css('display', '');
            $groups.css('display', '');
            searchEmptyEl().prop('hidden', true);
            return;
        }
        var visibleTotal = 0;
        // Pass 1 — single-conversation rows + thread sub-rows.
        // Match against name + preview + time.
        $rows.each(function() {
            var $row = $(this);
            var hay = (
                ($row.find('.adf-chat__row-name').text() || '') + ' ' +
                ($row.find('.adf-chat__row-preview').text() || '') + ' ' +
                ($row.find('.adf-chat__row-time').text() || '')
            ).toLowerCase();
            var match = hay.indexOf(query) !== -1;
            $row.toggle(match);
            if (match) visibleTotal++;
        });
        // Pass 2 — buyer-heads. Match against the buyer's name OR keep
        // visible if any of their grouped thread rows survived pass 1.
        // Without this pass the buyer-head was never tested at all, so
        // a search like "maya" would correctly show Maya Faraz (single
        // row) but ALSO leave Hannah / Jackob k visible — exactly the
        // bug from the screenshot.
        $heads.each(function() {
            var $head = $(this);
            var bid   = $head.attr('data-buyer-id');
            if (!bid) return;
            var nameHay   = ($head.find('.adf-chat__row-name').text() || '').toLowerCase();
            var nameMatch = nameHay.indexOf(query) !== -1;
            var $threads  = $shell.find('.adf-chat__row--thread[data-buyer-id="' + bid + '"]');
            var anyThreadVisible = $threads.filter(':visible').length > 0;
            var show = nameMatch || anyThreadVisible;
            // If the buyer's NAME matched but none of their threads
            // survived pass 1 (e.g. user searched "Hannah" — buyer name
            // matches, but ad titles don't), reveal her threads so the
            // user sees the full group rather than a lonely empty head.
            // Threads parked by +N more pagination or the user's manual
            // accordion collapse stay hidden.
            if (nameMatch && !anyThreadVisible) {
                $threads.not('.is-collapsed').not('.is-buyer-collapsed').show();
            }
            $head.toggle(show);
            if (show) visibleTotal++;
            $shell.find('.adf-chat__buyer-more-row[data-buyer-id="' + bid + '"]').toggle(show);
        });
        $groups.each(function() {
            var $ul = $(this);
            var hasVisible = $ul.children('li:visible').length > 0;
            $ul.toggle(hasVisible);
            var $prev = $ul.prev('.adf-chat__section-label');
            if ($prev.length) $prev.toggle(hasVisible);
        });
        searchEmptyEl().prop('hidden', visibleTotal > 0);
    }

    // Delegated listeners — bind once on $shell, survive any inner DOM swap
    $shell.on('input', '#inlineFormInputGroup', function() {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { applyConvSearch(v); }, 80);
    });
    $shell.on('keydown', '#inlineFormInputGroup', function(e) {
        if (e.key === 'Escape' && this.value) {
            this.value = '';
            applyConvSearch('');
            e.preventDefault();
        }
    });
    // Re-apply existing filter after any rail re-render so search state
    // isn't silently lost when polling refreshes the conversation list.
    if ($rail.length && window.MutationObserver) {
        new MutationObserver(function() {
            if (lastSearchQuery) applyConvSearch(lastSearchQuery);
        }).observe($rail[0], { childList: true, subtree: false });
    }

    /* ─── Buyer-group accordion toggle ───
       Click (or Enter/Space) on a buyer head collapses/expands its
       grouped thread rows. State is persisted per-buyer in localStorage
       so the user's collapse choices survive reloads and sidebar
       refreshes. */
    function setBuyerGroupCollapsed($head, collapsed) {
        var bid = $head.attr('data-buyer-id');
        if (!bid) return;
        $head.attr('aria-expanded', collapsed ? 'false' : 'true');
        var $matching = $shell.find(
            '.adf-chat__row--thread[data-buyer-id="' + bid + '"], ' +
            '.adf-chat__buyer-more-row[data-buyer-id="' + bid + '"]'
        );
        $matching.toggleClass('is-buyer-collapsed', collapsed);
        try {
            var key = 'adf_chat_buyer_collapsed_' + bid;
            if (collapsed) localStorage.setItem(key, '1');
            else           localStorage.removeItem(key);
        } catch (e) {}
    }
    $shell.on('click', '.adf-chat__buyer-head', function(e) {
        // Don't trigger when the user clicks the avatar's profile link
        // or any descendant interactive element (none currently — the
        // head is purely a toggle — but defensive).
        if ($(e.target).closest('a, button:not(.adf-chat__buyer-toggle)').length) return;
        var $head = $(this);
        var collapsed = $head.attr('aria-expanded') !== 'false'; // currently expanded → about to collapse
        setBuyerGroupCollapsed($head, collapsed);
    });
    $shell.on('keydown', '.adf-chat__buyer-head', function(e) {
        if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
            e.preventDefault();
            $(this).trigger('click');
        }
    });
    // Restore persisted collapsed state on init + after every sidebar
    // refresh (the refreshSidebar() path swaps the inner HTML, dropping
    // any classes we'd added — re-applying from localStorage keeps the
    // user's collapse choices stable across real-time updates).
    function restoreBuyerCollapsedState() {
        $shell.find('.adf-chat__buyer-head[data-buyer-id]').each(function() {
            var $head = $(this);
            var bid   = $head.attr('data-buyer-id');
            if (!bid) return;
            try {
                if (localStorage.getItem('adf_chat_buyer_collapsed_' + bid) === '1') {
                    setBuyerGroupCollapsed($head, true);
                }
            } catch (e) {}
        });
    }
    restoreBuyerCollapsedState();
    // Hook into the existing sidebar-refresh observer so collapsed
    // groups stay collapsed after a real-time sidebar swap.
    $shell.find('.adf-chat__list').on('adf:sidebar:refreshed', restoreBuyerCollapsedState);

    /* ─── Block + Report (overflow menu, modals, toast) ─── */
    var $blockModal  = $('#adf-block-confirm');
    var $reportModal = $('#adf-report-modal');
    var $toast       = $('#adf-chat-toast');
    var toastTimer   = null;

    function adfToast(msg) {
        if (!$toast.length) return;
        $toast.text(msg).prop('hidden', false);
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() { $toast.prop('hidden', true); }, 3200);
    }
    function openModal($m)  { $m.prop('hidden', false); }
    function closeModal($m) { $m.prop('hidden', true); }
    function closeAllOverflow() {
        $shell.find('.adf-chat__overflow-btn[aria-expanded="true"]').attr('aria-expanded', 'false');
        $shell.find('.adf-chat__overflow-menu').prop('hidden', true);
    }

    // Toggle the overflow menu — clicking the ⋮ button.
    $shell.on('click', '.adf-chat__overflow-btn', function(e) {
        e.preventDefault(); e.stopPropagation();
        var $btn = $(this);
        var $menu = $btn.next('.adf-chat__overflow-menu');
        var willOpen = $menu.prop('hidden');
        closeAllOverflow();
        if (willOpen) {
            $menu.prop('hidden', false);
            $btn.attr('aria-expanded', 'true');
        }
    });
    // Click-outside / Escape closes the menu.
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.adf-chat__overflow').length) closeAllOverflow();
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllOverflow();
            closeModal($blockModal); closeModal($reportModal);
        }
    });

    // Modal close handlers (backdrop + cancel buttons).
    $('[data-modal-close]').on('click', function() {
        var $modal = $(this).closest('.adf-chat__modal');
        closeModal($modal);
    });

    // Helper: read recipient_id from the active head-actions container.
    function activeRecipientId() {
        var v = $shell.find('.adf-chat__head-actions').attr('data-recipient-id');
        return v ? parseInt(v, 10) : 0;
    }
    function activeConversationId() {
        return $shell.find('#conversation_id').val() || '';
    }

    // ---- Block flow ----
    $shell.on('click', '.adf-chat__menu-item--block', function() {
        closeAllOverflow();
        var action = $(this).attr('data-action');
        if (action === 'unblock') {
            // Unblock is non-destructive — fire immediately, no confirm.
            performUnblock();
        } else {
            openModal($blockModal);
        }
    });
    $('#adf-block-confirm-go').on('click', function() {
        closeModal($blockModal);
        performBlock();
    });
    function performBlock() {
        var targetId = activeRecipientId();
        if (!targetId) return;
        $.post(ajaxUrl, {
            action: 'adforest_chat_block_user',
            nonce: nonce,
            target_id: targetId
        }).done(function(res) {
            if (res && res.success) {
                applyBlockState(true);
                adfToast('<?php echo esc_js(__('User blocked.', 'adforest')); ?>');
            } else {
                adfToast('<?php echo esc_js(__('Couldn\'t block. Try again.', 'adforest')); ?>');
            }
        }).fail(function() {
            adfToast('<?php echo esc_js(__('Couldn\'t block. Try again.', 'adforest')); ?>');
        });
    }
    function performUnblock() {
        var targetId = activeRecipientId();
        if (!targetId) return;
        $.post(ajaxUrl, {
            action: 'adforest_chat_unblock_user',
            nonce: nonce,
            target_id: targetId
        }).done(function(res) {
            if (res && res.success) {
                applyBlockState(false);
                adfToast('<?php echo esc_js(__('User unblocked.', 'adforest')); ?>');
            } else {
                adfToast('<?php echo esc_js(__('Couldn\'t unblock. Try again.', 'adforest')); ?>');
            }
        }).fail(function() {
            adfToast('<?php echo esc_js(__('Couldn\'t unblock. Try again.', 'adforest')); ?>');
        });
    }
    // Reflect block state in the live UI without a page reload — composer
    // swaps to the banner, menu label flips Block ↔ Unblock, sidebar row
    // greys out (or restores). Polling keeps this in sync if state
    // changes from another tab.
    function applyBlockState(blocked) {
        var $actions = $shell.find('.adf-chat__head-actions');
        $actions.attr('data-blocked-by-me', blocked ? '1' : '0');
        var $btn = $shell.find('.adf-chat__menu-item--block');
        if ($btn.length) {
            $btn.attr('data-action', blocked ? 'unblock' : 'block');
            $btn.find('.adf-chat__menu-label').text(blocked
                ? '<?php echo esc_js(__('Unblock user', 'adforest')); ?>'
                : '<?php echo esc_js(__('Block user', 'adforest')); ?>');
            var $icon = $btn.find('i');
            $icon.removeClass('fa-ban fa-undo').addClass(blocked ? 'fa-undo' : 'fa-ban');
        }
        // Composer banner swap — replace the composer footer's contents.
        var $composer = $shell.find('.adf-chat__composer').first();
        if ($composer.length) {
            if (blocked) {
                $composer.replaceWith(
                    '<footer class="adf-chat__composer adf-chat__composer--disabled adf-chat__composer--blocked" aria-disabled="true">' +
                    '  <div class="adf-chat__deleted-banner adf-chat__deleted-banner--block" role="status">' +
                    '    <i class="fa fa-ban" aria-hidden="true"></i>' +
                    '    <span><?php echo esc_js(__('You blocked this user. They can no longer message you. Use the menu above to unblock.', 'adforest')); ?></span>' +
                    '  </div>' +
                    '</footer>'
                );
            } else if ($composer.hasClass('adf-chat__composer--blocked')) {
                // Was blocked — restoring requires the original composer markup.
                // Cheapest reliable path: refresh the page so the SB Chat plugin
                // re-binds its composer JS without us having to recreate it.
                window.location.reload();
                return;
            }
        }
        // Sidebar row treatment.
        var convId = activeConversationId();
        if (convId) {
            $shell.find('.adf-chat__row[data-conv="' + convId + '"]').toggleClass('is-blocked', blocked);
        }
    }

    // ---- Report flow ----
    $shell.on('click', '.adf-chat__menu-item--report', function() {
        closeAllOverflow();
        $('#adf-report-form')[0].reset();
        openModal($reportModal);
    });
    $('#adf-report-form').on('submit', function(e) {
        e.preventDefault();
        var targetId = activeRecipientId();
        var convId   = activeConversationId();
        if (!targetId) return;
        var category = $('#adf-report-category').val();
        var reason   = $('#adf-report-reason').val();
        var $submit  = $(this).find('button[type="submit"]').prop('disabled', true);
        $.post(ajaxUrl, {
            action: 'adforest_chat_report_user',
            nonce: nonce,
            target_id: targetId,
            conversation_id: convId,
            category: category,
            reason: reason
        }).always(function() {
            $submit.prop('disabled', false);
        }).done(function(res) {
            closeModal($reportModal);
            if (res && res.success) {
                adfToast('<?php echo esc_js(__('Report submitted. Thank you.', 'adforest')); ?>');
            } else {
                var msg = (res && res.data && res.data.message) ? res.data.message : '<?php echo esc_js(__('Couldn\'t submit. Try again.', 'adforest')); ?>';
                adfToast(msg);
            }
        }).fail(function() {
            closeModal($reportModal);
            adfToast('<?php echo esc_js(__('Couldn\'t submit. Try again.', 'adforest')); ?>');
        });
    });

    /* "+N more" buyer-card toggle — reveals collapsed thread sub-rows */
    $shell.on('click', '.adf-chat__buyer-more', function(e) {
        e.preventDefault();
        var $btn   = $(this);
        var bid    = $btn.closest('.adf-chat__buyer-more-row').attr('data-buyer-id');
        var $extra = $shell.find('.adf-chat__row--thread[data-buyer-id="' + bid + '"][hidden], .adf-chat__row--thread[data-buyer-id="' + bid + '"].is-collapsed');
        var open = $btn.attr('aria-expanded') === 'true';
        if (open) {
            $extra.prop('hidden', true).addClass('is-collapsed');
            $btn.attr('aria-expanded', 'false');
            $btn.text($btn.data('label-more') || $btn.text());
        } else {
            $extra.prop('hidden', false).removeClass('is-collapsed');
            $btn.attr('aria-expanded', 'true');
            if (!$btn.data('label-more')) $btn.data('label-more', $btn.text());
            $btn.text('<?php echo esc_js(__('Show less', 'adforest')); ?>');
        }
    });

    /* Mobile pane swap */
    $shell.on('click', '.con-chat-list', function() { $shell.addClass('is-thread-open'); });
    $shell.on('click', '.adf-chat__head-back', function(e) { e.preventDefault(); $shell.removeClass('is-thread-open'); });

    /* Auto-scroll thread to bottom on first paint.
       Multi-tick + image-load listeners are deliberate — DOMReady fires
       before images decode, before the voice-player JS converts markers
       into <audio> elements, before the ad-card / receipt decorations
       inflate the layout. A single scrollTop call here measures
       scrollHeight while it's still settling, and the user lands at
       a stale position with the latest message hidden below the fold.
       Re-applying after each settling milestone reliably parks the
       view on the newest bubble — same approach Slack/Messenger use. */
    var $body = $shell.find('.adf-chat__body');
    function adfScrollToBottom() {
        if (!$body.length || !$body[0]) return;
        $body[0].scrollTop = $body[0].scrollHeight;
    }
    adfScrollToBottom();
    // Settling milestones — covers DOM mutations, image decoding,
    // voice-marker → player conversion, and webfont-driven reflow.
    setTimeout(adfScrollToBottom, 50);
    setTimeout(adfScrollToBottom, 200);
    setTimeout(adfScrollToBottom, 600);
    setTimeout(adfScrollToBottom, 1200);
    // Re-anchor after every image in the body finishes loading. Without
    // this an attachment image rendered late shifts the scrollHeight
    // and the user's "bottom" pin slides up.
    $body.find('img').each(function() {
        if (this.complete && this.naturalWidth) return;
        $(this).on('load error', adfScrollToBottom);
    });
    // Expose so other init code (post-decoration callbacks) can call it.
    window.__adfScrollBottom = adfScrollToBottom;

    /* ─── Pin / unpin ─── */
    $shell.on('click', '.adf-chat__row-pin', function(e) {
        e.preventDefault(); e.stopPropagation();
        var $btn = $(this), $li = $btn.closest('li'), conv = $btn.data('conv');
        if (!conv) return;
        $.post(ajaxUrl, { action: 'adforest_chat_toggle_pin', nonce: nonce, conversation_id: conv })
         .done(function(res) {
             if (res && res.success) {
                 $li.attr('data-pinned', res.data.pinned ? '1' : '0');
                 window.location.reload();
             }
         });
    });

    /* ─── Mark as read on open ─── */
    function markRead(conv) {
        if (!conv) return;
        $.post(ajaxUrl, { action: 'adforest_chat_mark_read', nonce: nonce, conversation_id: conv })
         .done(function() {
             var $row = $shell.find('.adf-chat__row[data-id="' + conv + '"]');
             $row.removeClass('unread');
             $row.find('.adf-chat__row-badge').remove();
         });
    }
    var initialConv = $shell.find('#conversation_id').val();
    if (initialConv) markRead(initialConv);
    $shell.on('click', '.con-chat-list', function() { markRead($(this).data('conv')); });

    /* ─── Header swap on conversation switch ─── */
    /* The legacy sb_chat plugin handler (admin-custom.js) loads a clicked
       conversation's messages but only refreshes `.msg-head` — a node the
       modern layout doesn't render. Result: the .adf-chat__head (name,
       avatar, Profile link, data-recipient-id) stays pointed at whichever
       conversation rendered server-side on page load, so every row opens
       under the wrong identity. Each row carries its recipient payload as
       data-* attrs (set in $render_row / $render_thread); swap the header
       synchronously from those. */
    function _adfChatEscHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function _adfChatEscAttr(s) { return _adfChatEscHtml(s); }
    $shell.on('click', '.con-chat-list', function () {
        var $a       = $(this);
        var $head    = $shell.find('.adf-chat__head');
        if (!$head.length) return;

        var convId   = String($a.attr('data-conv') || '');
        var recId    = String($a.attr('data-recipient_id') || '');
        var name     = $a.attr('data-recipient-name') || '';
        var profUrl  = $a.attr('data-profile-url') || '';
        var anon     = $a.attr('data-anonymized') === '1';
        var online   = $a.attr('data-online') === '1';

        var newAvatarSrc = $a.find('.adf-chat__avatar img').first().attr('src') || '';

        // Name + avatar
        if (name) { $head.find('.adf-chat__head-name').text(name); }
        var $avatar = $head.find('.adf-chat__avatar').first();
        $avatar.toggleClass('is-online', online);
        if (newAvatarSrc) { $avatar.find('img').attr('src', newAvatarSrc); }
        if (typeof recipientAvatarUrl !== 'undefined') { recipientAvatarUrl = newAvatarSrc; }

        // Online/offline label + rebuild the "From {ad}" portion so it
        // points at the conversation's actual ad, not the one that was
        // server-rendered on initial page load.
        var $meta = $head.find('.adf-chat__head-meta');
        $meta.toggleClass('is-online', online);
        var firstText = online ? '<?php echo esc_js(__('Online', 'adforest')); ?>' : '<?php echo esc_js(__('Offline', 'adforest')); ?>';
        var html = '<span class="dot"></span> ' + firstText;

        var adTitle = $a.attr('data-ad-title') || '';
        var adUrl   = $a.attr('data-ad-url') || '';
        var adState = $a.attr('data-ad-state') || 'unknown';
        var adLabel = $a.attr('data-ad-label') || '';
        if (adTitle) {
            // Mirror PHP: linkable for live/expired/unknown states; plain
            // <strong> for hidden/deleted/etc. (the safe-url whitelist).
            var linkSafe = !!adUrl && (adState === 'live' || adState === 'expired' || adState === 'unknown');
            var sep = ' <span class="sep" aria-hidden="true">·</span> ';
            var fromText = '<?php echo esc_js(__('From', 'adforest')); ?>';
            var stateClass = 'adf-chat__head-ad-link adf-chat__head-ad-link--' + _adfChatEscAttr(adState);
            var titleNode = linkSafe
                ? '<a href="' + _adfChatEscAttr(adUrl) + '" target="_blank" rel="noopener" class="' + stateClass + '">' + _adfChatEscHtml(adTitle) + '</a>'
                : '<strong class="' + stateClass + '">' + _adfChatEscHtml(adTitle) + '</strong>';
            var labelNode = adLabel
                ? ' <span class="adf-chat__head-ad-state adf-chat__head-ad-state--' + _adfChatEscAttr(adState) + '">(' + _adfChatEscHtml(adLabel.toLowerCase()) + ')</span>'
                : '';
            html += sep + fromText + ' ' + titleNode + labelNode;
        }
        $meta.html(html);

        // Profile button — show when we have a URL and recipient is real
        var $profile = $head.find('.adf-chat__btn-ghost').first();
        if (profUrl && !anon) {
            if ($profile.length) {
                $profile.attr('href', profUrl).show();
            }
        } else if ($profile.length) {
            $profile.hide();
        }

        // Refresh head-actions wiring (block/report/delete read these)
        var $actions = $head.find('.adf-chat__head-actions');
        $actions.attr('data-conv', convId);
        $actions.attr('data-recipient-id', recId);
        $actions.attr('data-anonymized', anon ? '1' : '0');
    });

    /* ─── Heartbeat (online status) ─── */
    function heartbeat() { $.post(ajaxUrl, { action: 'adforest_chat_heartbeat', nonce: nonce }); }
    heartbeat();
    setInterval(heartbeat, 30000);

    /* ─── Receipt ✓ / ✓✓ on last outgoing bubble ─── */
    /* The outgoing-state attr is set by PHP at page load and refreshed by
       the poll. Between those two events a freshly-sent message would
       otherwise inherit the stale state — so for any reply bubble whose
       id is newer than the last server-confirmed state sync, we force ✓
       (the recipient hasn't had a chance to read it yet). */
    var stateLastSyncId = -1; // -1 = uninitialized; first injectReceipt() seeds it.
    function injectReceipt() {
        var $head = $shell.find('.adf-chat__head-actions');
        if (!$head.length) return;
        var cached = $head.attr('data-outgoing-state') || '✓';
        var $replies = $shell.find('ul.messages-list li.message-bubble.reply');
        $replies.find('.adf-receipt').remove();
        if (!$replies.length) return;
        var $last = $replies.last();
        var lastIdMatch = ($last.attr('id') || '').match(/(\d+)$/);
        var lastId = lastIdMatch ? parseInt(lastIdMatch[1], 10) : 0;
        // First call: cached state applies to the bubbles already on screen.
        if (stateLastSyncId < 0) {
            var ids = $shell.find('ul.messages-list li.message-bubble[id^="umid_"]').map(function() {
                var m = (this.id || '').match(/(\d+)$/);
                return m ? parseInt(m[1], 10) : 0;
            }).get();
            stateLastSyncId = ids.length ? Math.max.apply(null, ids) : 0;
        }
        // Force ✓ on bubbles newer than the last state-sync; otherwise trust cache.
        var state = (lastId > stateLastSyncId) ? '✓' : cached;
        var isRead = state.indexOf('✓✓') !== -1;
        $last.append('<span class="adf-receipt ' + (isRead ? 'is-read' : '') + '">' + state + '</span>');
    }

    /* ─── Avatar next to LAST incoming bubble in a group ─── */
    var recipientAvatarUrl = $shell.find('.adf-chat__head .adf-chat__avatar img').first().attr('src') || '';
    function injectIncomingAvatars() {
        if (!recipientAvatarUrl) return;
        $shell.find('ul.messages-list li.message-bubble.sender .adf-bubble-avatar').remove();
        $shell.find('ul.messages-list li.message-bubble.sender').removeClass('is-with-avatar').each(function() {
            var $li = $(this), $next = $li.next('li');
            if (!$next.length || !$next.hasClass('sender')) {
                $li.addClass('is-with-avatar');
                $li.prepend('<span class="adf-bubble-avatar"><img src="' + recipientAvatarUrl + '" alt=""></span>');
            }
        });
    }

    /* ─── Date separator chip (one-shot, lives outside .messages-list) ─── */
    var dateChipPlaced = false;
    function injectDateChip() {
        if (dateChipPlaced) return;
        var $bodyEl = $shell.find('.adf-chat__body');
        var $list   = $shell.find('ul.messages-list');
        if (!$bodyEl.length || !$list.length || !$list.children('li.message-bubble').length) return;
        var label = new Date().toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' });
        $bodyEl.children('.adf-chat__date').remove();
        $('<div class="adf-chat__date"><span>' + label + '</span></div>').prependTo($bodyEl);
        dateChipPlaced = true;
    }

    /* ─── Compact post-card enforcement — restore any text truncated by an
        earlier session, then let CSS line-clamp do the visual clipping ─── */
    function compactPostCards() {
        $shell.find('.message-post-card .post-card-title span').each(function() {
            var $span = $(this);
            var orig  = $span.data('original');
            if (orig && $span.text() !== orig) {
                $span.text(orig);
            }
        });
        // Decorate the embedded first-message ad-card with the resolved
        // ad-state — disarms the link + adds the badge overlay when the
        // underlying post is gone or in a non-public state. Idempotent
        // (skips already-decorated cards).
        var adState = $shell.find('.adf-chat__body').attr('data-ad-state') || 'unknown';
        if (adState === 'live' || adState === 'unknown') return;
        var stateLabels = {
            expired:  'Expired',
            inactive: 'Unavailable',
            deleted:  'No longer available'
        };
        var label = stateLabels[adState] || '';
        $shell.find('.message-post-card').each(function() {
            var $card = $(this);
            if ($card.attr('data-adf-state-applied')) return;
            $card.attr('data-adf-state-applied', '1');
            $card.addClass('adf-card-state-' + adState);
            if (label) $card.attr('data-state-label', label);
            // Neutralize the link for deleted/inactive states. Expired
            // ads keep the click-through (theme decides what to render
            // on the ad page) — only visual emphasis is reduced.
            if (adState === 'deleted' || adState === 'inactive') {
                $card.find('a[href]').each(function() {
                    var $a = $(this);
                    if (!$a.attr('data-adf-href-orig')) {
                        $a.attr('data-adf-href-orig', $a.attr('href') || '');
                    }
                    $a.removeAttr('href').css({ 'pointer-events': 'none', 'cursor': 'default' });
                });
            }
        });
    }

    /* ─── Emoji-only detection (size up bubbles like Messenger/WA) ─── */
    var EMOJI_ONLY_RE;
    try {
        // Unicode property escapes — modern browsers (ES2018+)
        EMOJI_ONLY_RE = new RegExp('^(?:\\s|\\p{Extended_Pictographic}|\\u200D|\\uFE0F|\\u20E3|[\\u{1F1E6}-\\u{1F1FF}])+$', 'u');
    } catch (e) {
        // Fallback: look for any code-point ≥ 0x1F000 with no ASCII letters
        EMOJI_ONLY_RE = null;
    }
    function tagEmojiOnly() {
        $shell.find('ul.messages-list li.message-bubble').each(function() {
            var $li = $(this);
            // Skip bubbles that include attachments or post cards
            if ($li.find('.message-post-card, .message-media').length) return;
            var $textNode = $li.find('.message-text p').first();
            var raw = ($textNode.length ? $textNode : $li).text() || '';
            var trimmed = raw.replace(/\s+/g, '');
            if (!trimmed) return;
            var pts = Array.from(trimmed); // split by code-point
            if (pts.length > 12) { $li.removeClass('is-emoji-only is-emoji-small is-emoji-medium'); return; }
            var ok;
            if (EMOJI_ONLY_RE) {
                ok = EMOJI_ONLY_RE.test(trimmed);
            } else {
                ok = !/[A-Za-z0-9]/.test(trimmed) && /[\uD800-\uDBFF]/.test(trimmed);
            }
            if (!ok) { $li.removeClass('is-emoji-only is-emoji-small is-emoji-medium'); return; }
            $li.addClass('is-emoji-only');
            // Scale step based on emoji count
            $li.toggleClass('is-emoji-medium', pts.length > 1 && pts.length <= 3);
            $li.toggleClass('is-emoji-small',  pts.length > 3);
        });
    }

    /* ─── Voice bubble renderer — turns [adf-voice]URL[/adf-voice] markers
        into a custom bubble-styled player. ─── */
    function fmtVoiceTime(t) {
        if (!isFinite(t) || t < 0) t = 0;
        var s = Math.floor(t), m = Math.floor(s / 60);
        s = s % 60;
        return m + ':' + (s < 10 ? '0' + s : s);
    }
    /* HTML5 media events (play/pause/timeupdate/ended/etc.) do NOT bubble,
       so jQuery delegation on $shell wouldn't fire. Bind directly to each
       <audio> the moment it's rendered. Idempotent via data-adf-bound.
       A rAF tick is started on play and stopped on pause/ended — this
       keeps the timer + waveform in sync even on receiver-side audio
       elements where the browser is stingy with `timeupdate` events. */
    function startVoiceTick(audio) {
        if (audio.__adfTick) return;
        var raf = window.requestAnimationFrame || function(cb){ return setTimeout(cb, 1000/30); };
        var loop = function() {
            if (audio.paused || audio.ended) { audio.__adfTick = 0; return; }
            refreshVoiceUI(audio);
            audio.__adfTick = raf(loop);
        };
        audio.__adfTick = raf(loop);
    }
    function stopVoiceTick(audio) {
        if (!audio.__adfTick) return;
        var caf = window.cancelAnimationFrame || clearTimeout;
        try { caf(audio.__adfTick); } catch (e) {}
        audio.__adfTick = 0;
    }
    function bindVoiceAudioEvents(audio) {
        if (audio.getAttribute('data-adf-bound')) return;
        audio.setAttribute('data-adf-bound', '1');

        audio.addEventListener('play',     function() { refreshVoiceUI(audio); startVoiceTick(audio); });
        audio.addEventListener('playing',  function() { refreshVoiceUI(audio); startVoiceTick(audio); });
        audio.addEventListener('pause',    function() { stopVoiceTick(audio); refreshVoiceUI(audio); });
        audio.addEventListener('timeupdate', function() { refreshVoiceUI(audio); });
        audio.addEventListener('ended', function() {
            stopVoiceTick(audio);
            try { audio.currentTime = 0; } catch (e) {}
            var $player = $(audio).closest('.adf-voice');
            $player.removeClass('is-active');
            $player.find('.adf-voice__play').removeClass('is-playing');
            $player.find('.adf-voice__bar').css('width', '0%');
            refreshVoiceUI(audio); // restores total-duration label
        });
        audio.addEventListener('durationchange', function() { refreshVoiceUI(audio); });
        audio.addEventListener('loadedmetadata', function() { fixVoiceDuration(audio); });
        audio.addEventListener('loadeddata',     function() { fixVoiceDuration(audio); });
        audio.addEventListener('canplay',        function() { fixVoiceDuration(audio); });
    }

    function renderVoiceBubbles() {
        $shell.find('ul.messages-list li.message-bubble').each(function() {
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
            var label = (dur > 0) ? fmtVoiceTime(dur) : '0:00';
            $p.html(
                '<div class="adf-voice"' + attr + '>' +
                    '<button type="button" class="adf-voice__play" aria-label="<?php echo esc_attr(__('Play voice', 'adforest')); ?>"></button>' +
                    '<div class="adf-voice__track" role="slider" tabindex="0" aria-label="<?php echo esc_attr(__('Seek', 'adforest')); ?>"><div class="adf-voice__bar"></div></div>' +
                    '<span class="adf-voice__time">' + label + '</span>' +
                    '<audio class="adf-voice__src" preload="auto" src="' + url + '"></audio>' +
                '</div>'
            );
            $li.addClass('has-voice');
            var newAudio = $p.find('audio')[0];
            if (newAudio) {
                bindVoiceAudioEvents(newAudio);
                if (!(dur > 0)) loadDurationViaWebAudio(newAudio, $p.find('.adf-voice'));
            }
        });
    }

    /* Web Audio API duration resolver — reliable for WebM/Opus blobs whose
       <audio>.duration is Infinity. Decodes the file once, writes the
       resolved seconds onto the player as data-duration, refreshes UI. */
    function loadDurationViaWebAudio(audio, $player) {
        if (audio.getAttribute('data-adf-wa')) return;
        audio.setAttribute('data-adf-wa', '1');
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC || typeof fetch !== 'function') return;
        var src = audio.currentSrc || audio.src;
        if (!src) return;
        fetch(src, { credentials: 'same-origin' })
            .then(function(r) { return (r && r.ok) ? r.arrayBuffer() : null; })
            .then(function(buf) {
                if (!buf) return;
                var ctx = new AC();
                return ctx.decodeAudioData(buf).then(function(decoded) {
                    if (ctx.close) try { ctx.close(); } catch(e){}
                    var d = decoded && decoded.duration;
                    if (isFinite(d) && d > 0) {
                        $player.attr('data-duration', d);
                        // Update label immediately if audio is idle
                        if (audio.paused && audio.currentTime === 0) {
                            $player.find('.adf-voice__time').text(fmtVoiceTime(d));
                        }
                        refreshVoiceUI(audio);
                    }
                });
            })
            .catch(function(){ /* silent — UI keeps showing 0:00 */ });
    }

    var decorating = false;
    function applyDecorations() {
        if (decorating) return;
        decorating = true;
        try { injectReceipt(); injectIncomingAvatars(); injectDateChip(); compactPostCards(); tagEmojiOnly(); renderVoiceBubbles(); }
        finally { setTimeout(function() { decorating = false; }, 0); }
    }
    applyDecorations();

    var msgListEl = $shell.find('ul.messages-list')[0];
    var lastReplyCount = $shell.find('ul.messages-list li.message-bubble.reply').length;
    if (msgListEl && window.MutationObserver) {
        new MutationObserver(function(mutations) {
            applyDecorations();
            // Detect a freshly-added outgoing (.reply) bubble — that means
            // THIS user just sent a message. Auto-scroll the body to the
            // bottom so they can see their new message immediately, the
            // same way WhatsApp/Messenger do. Don't auto-scroll for
            // incoming bubbles here — pollNewMessages already handles that
            // case while preserving scroll position when the user has
            // scrolled up to read history.
            var $replies = $shell.find('ul.messages-list li.message-bubble.reply');
            var currentCount = $replies.length;
            if (currentCount > lastReplyCount) {
                var $body = $shell.find('.adf-chat__body');
                if ($body.length) {
                    // requestAnimationFrame ensures layout settled (incl.
                    // image/voice-player decorations) before measuring.
                    var raf = window.requestAnimationFrame || function(cb){ return setTimeout(cb, 16); };
                    raf(function() { $body.scrollTop($body[0].scrollHeight); });
                }
            }
            lastReplyCount = currentCount;

            // If a new outgoing bubble appeared, trigger a poll immediately
            // so the server confirms ✓ (recipient hasn't read yet) without
            // waiting for the next 8s tick.
            if (!$replies.length) return;
            var lastIdMatch = ($replies.last().attr('id') || '').match(/(\d+)$/);
            var lastId = lastIdMatch ? parseInt(lastIdMatch[1], 10) : 0;
            if (lastId > stateLastSyncId && typeof pollNewMessages === 'function') {
                pollNewMessages();
            }
        }).observe(msgListEl, { childList: true });
    }

    /* ===========================================================
       ADVANCED UX
       =========================================================== */

    /* ─── Typing indicator API (call externally to toggle) ─── */
    var typingHideTimer;
    window.adfChatTyping = function(show, autoHideMs) {
        var $t = $shell.find('.adf-typing');
        if (!$t.length) return;
        clearTimeout(typingHideTimer);
        if (show) {
            $t.prop('hidden', false);
            if (autoHideMs && autoHideMs > 0) {
                typingHideTimer = setTimeout(function() { $t.prop('hidden', true); }, autoHideMs);
            }
            // Keep view scrolled to indicator
            var $body = $shell.find('.adf-chat__body');
            if ($body.length) $body.scrollTop($body[0].scrollHeight);
        } else {
            $t.prop('hidden', true);
        }
    };

    /* ─── Real-time poll (incremental, append-only) ───
       Cursor priority:
         1. data-latest-id rendered server-side onto the <ul> (authoritative)
         2. highest umid_ id found in the existing DOM (fallback)
       Without (1) the first poll would request since_id=0 and the server
       would return EVERY message, which the new append path would duplicate
       on top of the initial render. */
    var lastSeenMsgId = (function() {
        var attr = parseInt($shell.find('ul.messages-list').attr('data-latest-id') || '0', 10);
        if (attr > 0) return attr;
        var ids = $shell.find('ul.messages-list li.message-bubble[id^="umid_"]').map(function() {
            var m = (this.id || '').match(/(\d+)$/);
            return m ? parseInt(m[1], 10) : 0;
        }).get();
        return ids.length ? Math.max.apply(null, ids) : 0;
    })();

    function isScrolledToBottom($el) {
        if (!$el.length) return true;
        var el = $el[0];
        return (el.scrollHeight - el.scrollTop - el.clientHeight) < 60;
    }

    /* ─── Sidebar real-time refresh ───
       Triggered when EITHER cursor climbs:
         data-max-conv-id : new conversation appeared (buyer messaged from ad page)
         data-max-msg-id  : any new message in any conversation (preview /
                            timestamp / unread / ordering needs to update)
       Throttled so two near-simultaneous polls can't double-fetch. */
    var userMaxConvId = parseInt($shell.find('.adf-chat__list').attr('data-max-conv-id') || '0', 10);
    var userMaxMsgId  = parseInt($shell.find('.adf-chat__list').attr('data-max-msg-id')  || '0', 10);
    var sidebarRefreshInFlight = false;
    var sidebarLastRefreshAt   = 0;
    var SIDEBAR_REFRESH_MIN_MS = 4000;  // hard floor between refreshes
    var sidebarRefreshPending  = null;  // setTimeout handle
    function refreshSidebar() {
        var now = Date.now();
        var since = now - sidebarLastRefreshAt;
        if (sidebarRefreshInFlight) return;
        if (since < SIDEBAR_REFRESH_MIN_MS) {
            // Schedule the refresh once the cool-down expires; coalesce
            // any further requests into the single pending one.
            if (!sidebarRefreshPending) {
                sidebarRefreshPending = setTimeout(function() {
                    sidebarRefreshPending = null;
                    refreshSidebar();
                }, SIDEBAR_REFRESH_MIN_MS - since);
            }
            return;
        }
        sidebarRefreshInFlight = true;
        sidebarLastRefreshAt   = now;
        // Fetch the current page via AJAX and extract the rendered
        // sidebar — reuses the existing PHP render closures so the
        // refreshed rows match the initial render exactly (preview,
        // unread badge, online dot, ad-grouping, pinned state, etc.).
        $.get(window.location.pathname + window.location.search).always(function() {
            sidebarRefreshInFlight = false;
        }).done(function(html) {
            try {
                var $newDoc      = $('<div>').append($.parseHTML(html));
                var $newList     = $newDoc.find('.adf-chat__list .messages-inbox.chat-list');
                var $oldList     = $shell.find('.adf-chat__list .messages-inbox.chat-list');
                if (!$newList.length || !$oldList.length) return;

                // Preserve the user's current state across the swap so
                // the refresh feels like a passive update, not a reload:
                //   - active conversation highlight
                //   - sidebar scroll position
                //   - in-flight search query
                var activeConv     = $shell.find('.adf-chat__row.active').attr('data-conv') || $shell.find('#conversation_id').val();
                var $listScroll    = $shell.find('.adf-chat__list');
                var prevScrollTop  = $listScroll.scrollTop();
                var searchQ        = ($shell.find('.adf-chat__search input').val() || '').trim();

                $oldList.html($newList.html());

                // Re-mark the active row.
                if (activeConv) {
                    $shell.find('.adf-chat__row').removeClass('active');
                    $shell.find('.adf-chat__row[data-conv="' + activeConv + '"]').addClass('active');
                }
                // Update both cursors from the new server-rendered values.
                var newConvCursor = parseInt($newDoc.find('.adf-chat__list').attr('data-max-conv-id') || '0', 10);
                var newMsgCursor  = parseInt($newDoc.find('.adf-chat__list').attr('data-max-msg-id')  || '0', 10);
                if (newConvCursor > 0) {
                    userMaxConvId = newConvCursor;
                    $shell.find('.adf-chat__list').attr('data-max-conv-id', newConvCursor);
                }
                if (newMsgCursor > 0) {
                    userMaxMsgId = newMsgCursor;
                    $shell.find('.adf-chat__list').attr('data-max-msg-id', newMsgCursor);
                }
                // Restore scroll, then re-apply search filter if active.
                $listScroll.scrollTop(prevScrollTop);
                if (searchQ && typeof applyConvSearch === 'function') {
                    applyConvSearch(searchQ);
                }
                // Notify listeners (buyer-group accordion) that the rail
                // was swapped so they can re-apply their persisted state.
                $listScroll.trigger('adf:sidebar:refreshed');
            } catch (e) { /* silent — sidebar simply waits for next tick */ }
        });
    }

    var pollInFlight = false;
    // Track the conversation we last polled. When the user clicks a
    // different conversation in the sidebar, the SB Chat plugin AJAX-
    // replaces the messages-list with the new thread's bubbles — but
    // `lastSeenMsgId` still points at the OLD conversation's max id.
    // The next poll would then fetch everything > old cursor for the
    // new conversation (= all of its messages) and APPEND them on top
    // of what the plugin just rendered, producing the duplicate the
    // user sees on first-open of a brand-new conversation.
    var lastPolledConvId = $shell.find('#conversation_id').val() || '';
    function pollNewMessages() {
        if (pollInFlight) return;
        var convId = $shell.find('#conversation_id').val();
        if (!convId) return;
        // Conversation switched since the last poll — sync the cursor
        // without rendering. We send a sentinel since_id so the server
        // takes the cheap "no new messages" branch and just returns
        // latest_id; the plugin's render is left untouched.
        var conversationSwitched = (lastPolledConvId !== '' && lastPolledConvId !== convId);
        var requestSinceId       = conversationSwitched ? 99999999 : lastSeenMsgId;
        var requestedConvId      = convId;
        pollInFlight = true;
        $.post(ajaxUrl, {
            action: 'adforest_chat_poll',
            nonce: nonce,
            conversation_id: convId,
            since_id: requestSinceId
        }).always(function() {
            pollInFlight = false;
        }).done(function(res) {
            // Discard if the user switched conversations while this
            // request was in flight — the response is for a stale thread.
            if ($shell.find('#conversation_id').val() !== requestedConvId) return;
            if (!res || !res.success) return;
            var d = res.data || {};

            // Cursor-sync mode: the plugin already rendered the new
            // conversation's bubbles. Just record the server's latest_id
            // so subsequent polls only fetch GENUINELY new messages.
            if (conversationSwitched) {
                lastPolledConvId = requestedConvId;
                if (typeof d.latest_id === 'number' && d.latest_id > 0) {
                    lastSeenMsgId = d.latest_id;
                    $shell.find('ul.messages-list').attr('data-latest-id', lastSeenMsgId);
                }
                // Sidebar cursors still apply — handle them then bail.
                if (typeof d.user_max_conv_id === 'number' && d.user_max_conv_id > userMaxConvId) {
                    userMaxConvId = d.user_max_conv_id;
                }
                if (typeof d.user_max_msg_id === 'number' && d.user_max_msg_id > userMaxMsgId) {
                    userMaxMsgId = d.user_max_msg_id;
                }
                return;
            }
            lastPolledConvId = requestedConvId;
            // Always sync the outgoing-state cache — recipient may have read
            // older messages without anyone sending, in which case d.html is
            // empty but d.outgoing_state has flipped ✓ → ✓✓.
            if (typeof d.outgoing_state === 'string' && d.outgoing_state) {
                var $head = $shell.find('.adf-chat__head-actions');
                if ($head.length) $head.attr('data-outgoing-state', d.outgoing_state);
                stateLastSyncId = d.latest_id || stateLastSyncId;
                injectReceipt();
            }
            // Peer typing — piggybacked on every poll. Show the dots
            // while the other party's transient is alive; hide as soon
            // as it expires (auto-expiry, no separate "stopped" call).
            if (typeof window.adfChatTyping === 'function') {
                window.adfChatTyping(!!d.peer_typing);
            }
            // Block state — keep the UI in sync if the user blocked /
            // unblocked from another tab. Only react when state actually
            // flipped to avoid touching the DOM on every poll tick.
            if (typeof d.blocked_by_me === 'boolean') {
                var $a = $shell.find('.adf-chat__head-actions');
                var current = $a.attr('data-blocked-by-me') === '1';
                if (d.blocked_by_me !== current && typeof applyBlockState === 'function') {
                    applyBlockState(d.blocked_by_me);
                }
            }
            // Real-time sidebar updates — refresh whenever EITHER cursor
            // climbs. user_max_conv_id catches brand-new conversations
            // (buyer messaging from an ad page). user_max_msg_id catches
            // new messages in any non-active conversation (rail re-orders,
            // preview / timestamp / unread badge update). Both flow into
            // the same throttled refreshSidebar() — at most one fetch
            // per SIDEBAR_REFRESH_MIN_MS regardless of how many cursors
            // changed in the same tick.
            var sidebarStale = false;
            if (typeof d.user_max_conv_id === 'number' && d.user_max_conv_id > userMaxConvId) {
                userMaxConvId = d.user_max_conv_id;
                sidebarStale = true;
            }
            if (typeof d.user_max_msg_id === 'number' && d.user_max_msg_id > userMaxMsgId) {
                userMaxMsgId = d.user_max_msg_id;
                sidebarStale = true;
            }
            if (sidebarStale) refreshSidebar();
            // Server already enforces id > since_id, so absence of html means
            // no new bubbles — bail without touching the DOM.
            if (!d.html || (d.latest_id && d.latest_id <= lastSeenMsgId)) return;

            var $body = $shell.find('.adf-chat__body');
            var atBottom = isScrolledToBottom($body);
            var prevHeight = $body[0] ? $body[0].scrollHeight : 0;
            var prevSeen   = lastSeenMsgId;

            // APPEND only the new bubbles — preserves all existing nodes
            // (no flicker, no audio reload on voice notes already bound,
            // no re-fetch of attachment images). The MutationObserver on
            // ul.messages-list re-runs applyDecorations() so voice-note
            // markers, emoji sizing, ad cards, receipts get applied to
            // freshly-injected bubbles only.
            //
            // Dedup safety net — every server-rendered bubble carries an
            // id="umid_X". Skip any bubble whose id is already in the DOM.
            // Catches edge cases like a polling response arriving after a
            // late MutationObserver-triggered render of the same message.
            var $existingList = $shell.find('ul.messages-list');
            var $incoming = $($.parseHTML(d.html));
            $incoming.each(function() {
                if (!this || this.nodeType !== 1) return;
                var bubbleId = this.id;
                if (bubbleId && $existingList.find('#' + bubbleId).length) return;
                $existingList[0].appendChild(this);
            });
            lastSeenMsgId = d.latest_id || lastSeenMsgId;

            // Animate just the bubbles that came in this tick.
            var $newBubbles = $shell.find('ul.messages-list li.message-bubble[id^="umid_"]').filter(function() {
                var m = (this.id || '').match(/(\d+)$/);
                return m && parseInt(m[1], 10) > prevSeen;
            });
            $newBubbles.addClass('adf-just-arrived');
            setTimeout(function() { $newBubbles.removeClass('adf-just-arrived'); }, 400);

            // Auto-scroll only when the user was already pinned to the
            // bottom — never yank them away from older messages they're
            // reading. Otherwise anchor by height delta so the visible
            // message stays in place.
            if (atBottom) {
                if ($body[0]) $body.scrollTop($body[0].scrollHeight);
            } else if ($body[0]) {
                var delta = $body[0].scrollHeight - prevHeight;
                if (delta > 0) $body.scrollTop($body.scrollTop() + delta);
            }

            // Mark active conversation as read — incoming messages should
            // never leave a stale unread badge sitting on the open thread.
            if ($newBubbles.filter('.sender').length) {
                $.post(ajaxUrl, {
                    action: 'adforest_chat_mark_read',
                    nonce: nonce,
                    conversation_id: convId
                });
                // Reflect in sidebar instantly — drop the unread highlight
                // from the row matching this conversation.
                $shell.find('.adf-chat__row[data-conv="' + convId + '"]').removeClass('unread')
                      .find('.adf-chat__row-badge').remove();
            }
        });
    }
    // Tighter polling cadence for near-real-time feel (was 8s). Skipped
    // when the tab is hidden to avoid wasted requests; resumed instantly
    // on visibilitychange so users see new messages the moment they
    // return to the tab.
    var pollTimer = setInterval(function() {
        if (document.visibilityState === 'visible') pollNewMessages();
    }, 3000);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') pollNewMessages();
    });

    /* ─── Lazy-load older messages ─── */
    var olderBatchSize = 10;
    var olderState = { loading: false, exhausted: false };

    function loadOlderMessages($trigger) {
        if (olderState.loading || olderState.exhausted) return;
        var convId = $shell.find('#conversation_id').val();
        var $first = $shell.find('ul.messages-list li.message-bubble[id^="umid_"]').first();
        var beforeId = 0;
        if ($first.length && $first[0].id) {
            var m = $first[0].id.match(/(\d+)$/);
            if (m) beforeId = parseInt(m[1], 10);
        }
        // Initial server render doesn't add umid_ ids on every bubble — fall
        // back to scanning every bubble id, otherwise pick the smallest known
        // server msg id (recorded by the polling cache) as the cursor.
        if (!beforeId) {
            $shell.find('ul.messages-list li.message-bubble').each(function() {
                var idAttr = (this.id || '').match(/(\d+)$/);
                if (idAttr) {
                    var n = parseInt(idAttr[1], 10);
                    if (!beforeId || n < beforeId) beforeId = n;
                }
            });
        }
        if (!convId || !beforeId) {
            // No anchor → can't paginate. Hide the button.
            $shell.find('.adf-chat__load-older').prop('hidden', true);
            olderState.exhausted = true;
            return;
        }

        olderState.loading = true;
        if ($trigger && $trigger.length) $trigger.addClass('is-loading');

        var $body = $shell.find('.adf-chat__body');
        var prevScrollHeight = $body.length ? $body[0].scrollHeight : 0;
        var prevScrollTop    = $body.length ? $body.scrollTop()      : 0;

        $.post(ajaxUrl, {
            action: 'adforest_chat_older',
            nonce: nonce,
            conversation_id: convId,
            before_id: beforeId,
            limit: olderBatchSize
        }).always(function() {
            olderState.loading = false;
            if ($trigger && $trigger.length) $trigger.removeClass('is-loading');
        }).done(function(res) {
            if (!res || !res.success) return;
            var d = res.data || {};
            if (d.html) {
                // Prepend the older batch, then anchor the scroll position
                // so the user's currently-visible message stays in place
                // (no "jump up" that loses the reading position).
                $shell.find('ul.messages-list').prepend(d.html);
                if ($body.length) {
                    var newScrollHeight = $body[0].scrollHeight;
                    $body.scrollTop(prevScrollTop + (newScrollHeight - prevScrollHeight));
                }
            }
            if (!d.has_more) {
                olderState.exhausted = true;
                $shell.find('.adf-chat__load-older').prop('hidden', true);
            }
        });
    }

    $shell.on('click', '.adf-chat__load-older', function() {
        loadOlderMessages($(this));
    });

    // Auto-load when the user scrolls near the top — same UX as
    // WhatsApp/Messenger. 10 messages per batch as requested.
    $shell.on('scroll', '.adf-chat__body', function() {
        if (this.scrollTop < 60 && !olderState.loading && !olderState.exhausted) {
            loadOlderMessages($shell.find('.adf-chat__load-older'));
        }
    });

    // Auto-show "Load older" only if the rendered list is dense enough to scroll
    setTimeout(function() {
        var $body = $shell.find('.adf-chat__body')[0];
        var $list = $shell.find('ul.messages-list')[0];
        if ($body && $list && $list.scrollHeight > $body.clientHeight + 40) {
            $shell.find('.adf-chat__load-older').prop('hidden', false);
        }
    }, 200);

    /* ─── Typing indicator beacon (outgoing) ───
       Fires while the user is typing in the composer. Throttled so we
       send at most one beacon per ~1.5s (the server-side transient TTL
       is 4s, so this comfortably keeps the peer's "is_typing" alive
       while typing continues). Stops the moment the user clears the
       input or sends a message. */
    var typingMinIntervalMs = 1500;
    var lastTypingBeaconAt  = 0;
    function sendTypingBeacon(stop) {
        var convId = $shell.find('#conversation_id').val();
        if (!convId) return;
        var now = Date.now();
        if (!stop && (now - lastTypingBeaconAt) < typingMinIntervalMs) return;
        lastTypingBeaconAt = stop ? 0 : now;
        $.post(ajaxUrl, {
            action: 'adforest_chat_typing',
            nonce: nonce,
            conversation_id: convId,
            stop: stop ? 1 : 0
        });
    }
    $shell.on('input', '#message_box', function() {
        if (this.value && this.value.length) {
            sendTypingBeacon(false);
        } else {
            sendTypingBeacon(true);
        }
    });
    // Stop the indicator the instant the user sends — the SB Chat
    // plugin's submit handler clears the field but doesn't fire input,
    // so the peer would otherwise see "typing…" lingering for a few
    // seconds after the message arrives.
    $shell.on('submit', 'form.send-message', function() {
        sendTypingBeacon(true);
    });

    /* ─── Emoji picker (lazy-loaded emoji-picker-element web component) ─── */
    var emojiPickerLoading = null;
    var $emojiPanel = null;
    var $emojiBtn = $shell.find('.adf-composer-emoji');

    function loadEmojiModule() {
        if (emojiPickerLoading) return emojiPickerLoading;
        // Dynamic import via runtime — avoids needing type="module" tags
        emojiPickerLoading = new Promise(function(resolve, reject) {
            try {
                // eslint-disable-next-line no-new-func
                (new Function('u', 'return import(u)'))('https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js')
                    .then(resolve, reject);
            } catch (err) { reject(err); }
        });
        return emojiPickerLoading;
    }

    function insertAtCaret(el, text) {
        if (!el) return;
        el.focus();
        var start = (typeof el.selectionStart === 'number') ? el.selectionStart : el.value.length;
        var end   = (typeof el.selectionEnd   === 'number') ? el.selectionEnd   : start;
        var v = el.value || '';
        el.value = v.substring(0, start) + text + v.substring(end);
        var pos = start + text.length;
        try { el.setSelectionRange(pos, pos); } catch (e) {}
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function positionEmojiPanel() {
        if (!$emojiPanel || !$emojiBtn.length) return;
        var btn = $emojiBtn[0].getBoundingClientRect();
        var pw  = $emojiPanel.outerWidth() || 340;
        var ph  = $emojiPanel.outerHeight() || 360;
        var top  = btn.top + window.pageYOffset - ph - 8;
        var left = btn.left + window.pageXOffset - (pw - btn.width) / 2;
        // Clamp to viewport horizontally
        left = Math.max(8, Math.min(left, window.innerWidth - pw - 8 + window.pageXOffset));
        if (btn.top - ph - 8 < 0) top = btn.bottom + window.pageYOffset + 8;
        $emojiPanel.css({ top: top + 'px', left: left + 'px' });
    }

    function openEmojiPanel() {
        loadEmojiModule().then(function() {
            if (!$emojiPanel) {
                $emojiPanel = $('<div class="adf-emoji-panel" hidden><emoji-picker></emoji-picker></div>');
                $('body').append($emojiPanel);
                var picker = $emojiPanel[0].querySelector('emoji-picker');
                picker.addEventListener('emoji-click', function(ev) {
                    var input = $shell.find('#message_box')[0];
                    insertAtCaret(input, ev.detail && ev.detail.unicode ? ev.detail.unicode : '');
                });
            }
            $emojiPanel.prop('hidden', false).removeClass('is-closing');
            positionEmojiPanel();
        }).catch(function() {
            // Fail silent — picker just won't open if CDN is unreachable
        });
    }

    function closeEmojiPanel() {
        if (!$emojiPanel || $emojiPanel.prop('hidden')) return;
        $emojiPanel.addClass('is-closing');
        setTimeout(function() { if ($emojiPanel) $emojiPanel.prop('hidden', true).removeClass('is-closing'); }, 160);
    }

    $emojiBtn.on('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        if ($emojiPanel && !$emojiPanel.prop('hidden')) { closeEmojiPanel(); return; }
        openEmojiPanel();
    });
    $(document).on('mousedown touchstart', function(e) {
        if (!$emojiPanel || $emojiPanel.prop('hidden')) return;
        if ($emojiPanel[0].contains(e.target)) return;
        if ($emojiBtn.length && $emojiBtn[0].contains(e.target)) return;
        closeEmojiPanel();
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $emojiPanel && !$emojiPanel.prop('hidden')) closeEmojiPanel();
    });
    $(window).on('resize scroll', function() {
        if ($emojiPanel && !$emojiPanel.prop('hidden')) positionEmojiPanel();
    });

    /* ─── Voice recording (MediaRecorder → POST adforest_chat_send_voice) ─── */
    var $composer = $shell.find('.adf-chat__composer');
    var $micBtn   = $composer.find('.adf-composer-mic');
    var $recBar   = $composer.find('.adf-recording-bar');
    var $recCancel= $composer.find('.adf-rec-cancel');
    var $timerEl  = $recBar.find('.timer');
    var voiceState = { recording: false, stream: null, recorder: null, chunks: [], started: 0, timerInt: null, cancelled: false };

    function fmtTimer(ms) {
        var total = Math.floor(ms / 1000);
        var m = Math.floor(total / 60), s = total % 60;
        return m + ':' + (s < 10 ? '0' + s : s);
    }

    function pickAudioMime() {
        if (typeof MediaRecorder === 'undefined' || !MediaRecorder.isTypeSupported) return '';
        var prefs = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];
        for (var i = 0; i < prefs.length; i++) if (MediaRecorder.isTypeSupported(prefs[i])) return prefs[i];
        return '';
    }

    function stopStream() {
        if (voiceState.stream) {
            try { voiceState.stream.getTracks().forEach(function(t){ t.stop(); }); } catch (e) {}
            voiceState.stream = null;
        }
        if (voiceState.timerInt) { clearInterval(voiceState.timerInt); voiceState.timerInt = null; }
        $composer.removeClass('is-recording');
        $timerEl.text('0:00');
        voiceState.recording = false;
    }

    function uploadVoice(blob, mime, durationSec) {
        var convId = $shell.find('#conversation_id').val();
        var recipId = $shell.find('#recipient_id').val();
        if (!convId || !recipId) return;
        var ext = mime.indexOf('webm') > -1 ? 'webm' : (mime.indexOf('ogg') > -1 ? 'ogg' : (mime.indexOf('mp4') > -1 ? 'm4a' : 'webm'));
        var dur = (typeof durationSec === 'number' && isFinite(durationSec) && durationSec > 0) ? durationSec : 0;
        var fd = new FormData();
        fd.append('action', 'adforest_chat_send_voice');
        fd.append('nonce', nonce);
        fd.append('conversation_id', convId);
        fd.append('recipient_id', recipId);
        fd.append('duration', dur.toFixed(2));
        fd.append('voice', new File([blob], 'voice.' + ext, { type: mime || 'audio/webm' }));
        $micBtn.prop('disabled', true);
        $.ajax({
            url: ajaxUrl, method: 'POST', data: fd, processData: false, contentType: false
        }).always(function() {
            $micBtn.prop('disabled', false);
        }).done(function(res) {
            if (!res || !res.success || !res.data || !res.data.url) return;
            // Optimistic insert — embed the duration in the marker so
            // renderVoiceBubbles picks it up immediately.
            var marker = '[adf-voice]' + res.data.url + '|' + dur.toFixed(2) + '[/adf-voice]';
            var bubble = '<li class="message-bubble reply adf-just-arrived"><div class="message-text"><p>' + marker + '</p></div></li>';
            $shell.find('ul.messages-list').append(bubble);
            applyDecorations();
            var $body = $shell.find('.adf-chat__body');
            if ($body.length) $body.scrollTop($body[0].scrollHeight);
            if (typeof lastSeenMsgId !== 'undefined' && res.data.message_id) {
                lastSeenMsgId = Math.max(lastSeenMsgId, parseInt(res.data.message_id, 10) || 0);
            }
        });
    }

    function startRecording() {
        if (voiceState.recording) return;
        if (typeof navigator === 'undefined' || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
            alert('<?php echo esc_js(__('Voice recording is not supported in this browser.', 'adforest')); ?>');
            return;
        }
        var convId = $shell.find('#conversation_id').val();
        if (!convId) return;
        navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
            voiceState.stream = stream;
            voiceState.chunks = [];
            voiceState.cancelled = false;
            var mime = pickAudioMime();
            try {
                voiceState.recorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
            } catch (e) {
                voiceState.recorder = new MediaRecorder(stream);
            }
            voiceState.recorder.addEventListener('dataavailable', function(ev) {
                if (ev.data && ev.data.size > 0) voiceState.chunks.push(ev.data);
            });
            voiceState.recorder.addEventListener('stop', function() {
                var actualMime = (voiceState.recorder && voiceState.recorder.mimeType) || mime || 'audio/webm';
                var elapsedSec = (Date.now() - voiceState.started) / 1000;
                stopStream();
                if (voiceState.cancelled) return;
                if (!voiceState.chunks.length) return;
                var blob = new Blob(voiceState.chunks, { type: actualMime });
                if (blob.size < 200) return; // Effectively empty
                uploadVoice(blob, actualMime, elapsedSec);
            });
            voiceState.recording = true;
            voiceState.started = Date.now();
            voiceState.recorder.start();
            $composer.addClass('is-recording');
            $timerEl.text('0:00');
            voiceState.timerInt = setInterval(function() {
                var elapsed = Date.now() - voiceState.started;
                $timerEl.text(fmtTimer(elapsed));
                // Hard cap at 2 minutes
                if (elapsed > 120000) stopRecording(false);
            }, 250);
        }).catch(function() {
            alert('<?php echo esc_js(__('Microphone access denied.', 'adforest')); ?>');
        });
    }

    function stopRecording(cancel) {
        if (!voiceState.recording || !voiceState.recorder) return;
        voiceState.cancelled = !!cancel;
        try { voiceState.recorder.stop(); } catch (e) { stopStream(); }
    }

    $micBtn.on('click', function(e) {
        e.preventDefault();
        if (voiceState.recording) stopRecording(false); else startRecording();
    });
    $recCancel.on('click', function(e) {
        e.preventDefault();
        stopRecording(true);
    });

    /* ─── Voice player controls (delegated, works for live + polled) ─── */
    $shell.on('click', '.adf-voice__play', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $player = $btn.closest('.adf-voice');
        var audio = $player.find('audio')[0];
        if (!audio) return;
        if (audio.paused) {
            // Pause any other voice playing in this thread
            $shell.find('.adf-voice audio').each(function() {
                if (this !== audio && !this.paused) this.pause();
            });
            if (!isFinite(audio.currentTime) || audio.currentTime < 0) {
                try { audio.currentTime = 0; } catch (e) {}
            }
            // Optimistic icon swap — instant feedback even before 'play'
            // event fires; reverted in .catch() if playback fails.
            $btn.addClass('is-playing');
            $player.addClass('is-active');
            refreshVoiceUI(audio);
            var p = audio.play();
            // Kick the rAF UI loop immediately — covers cases where the
            // 'play'/'playing' event fires late (or never, on some
            // codecs) so the timer + waveform still update during
            // playback.
            startVoiceTick(audio);
            if (p && p.catch) {
                p.catch(function() {
                    stopVoiceTick(audio);
                    $btn.removeClass('is-playing');
                    if (audio.currentTime === 0) $player.removeClass('is-active');
                });
            }
        } else {
            audio.pause();
            stopVoiceTick(audio);
            $btn.removeClass('is-playing'); // optimistic pause swap
        }
    });
    $shell.on('click', '.adf-voice__track', function(e) {
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
    function refreshVoiceUI(audio) {
        var $player = $(audio).closest('.adf-voice');
        if (!$player.length) return;
        var $bar    = $player.find('.adf-voice__bar');
        var $time   = $player.find('.adf-voice__time');
        var $btn    = $player.find('.adf-voice__play');
        var $track  = $player.find('.adf-voice__track');

        var playing = !audio.paused && !audio.ended;
        $btn.toggleClass('is-playing', playing);
        // is-active toggles the thumb dot — visible whenever the user has
        // interacted (playing or paused mid-clip)
        $player.toggleClass('is-active', playing || (audio.currentTime > 0 && !audio.ended));

        // Prefer persisted duration over the unreliable audio.duration
        var fixed = parseFloat($player.attr('data-duration')) || 0;
        var dur   = fixed > 0 ? fixed : ((isFinite(audio.duration) && audio.duration > 0) ? audio.duration : 0);

        // Clamp currentTime defensively — only the cheap finite/negative
        // guard remains now that the destructive 1e10 seek-fix hack is gone.
        var ct = audio.currentTime;
        if (!isFinite(ct) || ct < 0) ct = 0;
        if (dur > 0 && ct > dur) ct = dur;

        var pct   = dur > 0 ? Math.min(100, Math.max(0, (ct / dur) * 100)) : 0;
        $bar.css('width', pct + '%');
        $track.attr('aria-valuemin', '0').attr('aria-valuemax', dur > 0 ? Math.round(dur) : 0).attr('aria-valuenow', Math.round(ct));

        // Idle state shows total duration; playing/paused-mid shows current
        var t = (audio.paused && ct === 0) ? dur : ct;
        $time.text(fmtVoiceTime(t));
    }

    /* Resolve duration without the destructive `currentTime = 1e10` seek
       hack. The hack works for the SENDER (their fresh MediaRecorder blob
       resolves duration via loadedmetadata before fixVoiceDuration ever
       needs the seek path), but breaks the RECEIVER — when the audio is
       fetched from the server, the WebM/Opus container reports
       `duration: Infinity` and the seek hack leaves the audio element in
       a state where `currentTime` no longer advances during playback,
       freezing the timer + waveform. Cheap paths first (persisted attr,
       finite audio.duration), then Web Audio decode as the fallback. No
       mutation of the audio element. */
    function fixVoiceDuration(audio) {
        if (audio.getAttribute('data-adf-init')) return;
        var $player = $(audio).closest('.adf-voice');
        if (!$player.length) return;
        var persisted = parseFloat($player.attr('data-duration')) || 0;
        if (persisted > 0) {
            audio.setAttribute('data-adf-init', '1');
            refreshVoiceUI(audio);
            return;
        }
        var d = audio.duration;
        if (isFinite(d) && d > 0) {
            audio.setAttribute('data-adf-init', '1');
            refreshVoiceUI(audio);
            return;
        }
        audio.setAttribute('data-adf-init', '1');
        loadDurationViaWebAudio(audio, $player);
    }

    /* Poll all unfixed voice players — covers the case where the audio
       loaded its metadata silently (no event), or the page re-rendered
       without firing loadedmetadata for already-cached audio. */
    function tryFixAllVoice() {
        $shell.find('.adf-voice audio').each(function() {
            var a = this;
            bindVoiceAudioEvents(a); // safety — idempotent
            if (a.getAttribute('data-adf-init')) return;
            if (a.readyState >= 1) fixVoiceDuration(a);
        });
    }
    /* Media events don't bubble, so delegated listeners on $shell never
       fired. All audio events are now bound directly to each <audio> in
       bindVoiceAudioEvents() (called from renderVoiceBubbles). The
       polling loop is kept as a safety net for any audio that wasn't
       caught by the metadata events. */
    var voicePollTries = 0;
    var voicePoll = setInterval(function() {
        tryFixAllVoice();
        if (++voicePollTries > 30) clearInterval(voicePoll);
    }, 200);

    /* ─── Mobile swipe actions on sidebar rows ─── */
    var SWIPE_THRESHOLD = 70;
    $shell.find('.adf-chat__row').each(function() {
        var row = this;
        var startX = 0, startY = 0, currentX = 0, dragging = false, locked = false;
        var $row = $(row);

        function onStart(e) {
            var t = e.touches ? e.touches[0] : null;
            if (!t) return;
            startX = t.clientX; startY = t.clientY; currentX = 0; dragging = true; locked = false;
            $row.addClass('is-swiping');
        }
        function onMove(e) {
            if (!dragging) return;
            var t = e.touches ? e.touches[0] : null;
            if (!t) return;
            var dx = t.clientX - startX;
            var dy = t.clientY - startY;
            // Lock to horizontal once exceeded the gesture threshold
            if (!locked) {
                if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
                if (Math.abs(dy) > Math.abs(dx)) { dragging = false; $row.removeClass('is-swiping swipe-left swipe-right'); $row.find('.con-chat-list').css('transform',''); return; }
                locked = true;
            }
            currentX = Math.max(-120, Math.min(120, dx));
            $row.find('.con-chat-list').css('transform','translateX(' + currentX + 'px)');
            $row.toggleClass('swipe-left', currentX < -20);
            $row.toggleClass('swipe-right', currentX > 20);
            if (e.cancelable) e.preventDefault();
        }
        function onEnd() {
            if (!dragging) return;
            dragging = false;
            $row.removeClass('is-swiping');
            var $cl = $row.find('.con-chat-list');
            if (currentX <= -SWIPE_THRESHOLD) {
                // Swipe LEFT → delete (soft, current user)
                $cl.css('transform','translateX(-100%)');
                var conv = $cl.data('conv');
                $.post(ajaxUrl, { action: 'sb_delete_single_user_chat', conv_id: conv });
                setTimeout(function() { $row.slideUp(180, function() { $row.remove(); }); }, 200);
            } else if (currentX >= SWIPE_THRESHOLD) {
                // Swipe RIGHT → mark unread
                var convR = $cl.data('conv');
                $.post(ajaxUrl, { action: 'adforest_chat_mark_unread', nonce: nonce, conversation_id: convR })
                 .done(function(r) {
                    if (r && r.success) $row.addClass('unread');
                 });
                $cl.css('transform','');
                $row.removeClass('swipe-right swipe-left');
            } else {
                // Reset
                $cl.css('transform','');
                $row.removeClass('swipe-left swipe-right');
            }
        }

        row.addEventListener('touchstart', onStart, { passive: true });
        row.addEventListener('touchmove',  onMove,  { passive: false });
        row.addEventListener('touchend',   onEnd);
        row.addEventListener('touchcancel',onEnd);
    });
});
</script>

<?php get_footer(); ?>
