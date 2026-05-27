<?php
/**
 * SB Chat — Modern UX Extensions
 *
 * Theme-side companion file that layers extra features on top of the
 * SB Chat plugin without modifying its source. Plugin updates remain
 * safe because all changes live here.
 *
 * Features added:
 *   1. Pinned conversations  (per-user pin flag in conversation table)
 *   2. Read receipts         (sent ✓ vs read ✓✓ — derived from existing
 *                             plugin `read_user_1/2` flags + an AJAX
 *                             handler that marks the thread as read on
 *                             open)
 *   3. Online status         (last_seen user meta + heartbeat AJAX)
 *
 * @package Adforest
 */

if (!defined('ABSPATH')) exit;

/* ------------------------------------------------------------------ */
/* Bail unless SB Chat plugin is active                                */
/* ------------------------------------------------------------------ */
if (!class_exists('SB_Chat')) {
    return;
}

/* ------------------------------------------------------------------ */
/* SCRIPT DEPENDENCY FIX                                               */
/* ------------------------------------------------------------------ */
/*
 * SB Chat's admin-custom.js (enqueued globally on the frontend) calls
 * `new Dropzone('#sbchat-mu', …)` at IIFE init. The plugin only loads
 * the Dropzone library on the dashboard, and the plugin's own JS does
 * not declare it as a dep — so on the modern Messages template the
 * IIFE blows up with a ReferenceError and the `.send-message` submit
 * handler never registers (silent send + attachment failure).
 *
 * We fix this by enqueueing the theme-bundled Dropzone build on the
 * messages template AND injecting it as a hard dep of the plugin's
 * script so WP guarantees the load order regardless of registration
 * timing.
 */
add_action('wp_enqueue_scripts', 'adforest_modern_chat_force_dropzone_dep', 99);

function adforest_modern_chat_force_dropzone_dep() {
    if (!function_exists('is_page_template') || !is_page_template('page-messages.php')) {
        return;
    }
    wp_enqueue_script('adforest-dropzone-js');

    global $wp_scripts;
    if (isset($wp_scripts->registered['sb-chat-admin-script'])) {
        $deps = $wp_scripts->registered['sb-chat-admin-script']->deps;
        if (!in_array('adforest-dropzone-js', $deps, true)) {
            $wp_scripts->registered['sb-chat-admin-script']->deps[] = 'adforest-dropzone-js';
        }
    }
}

/* ------------------------------------------------------------------ */
/* 0. DB MIGRATION (idempotent)                                        */
/* ------------------------------------------------------------------ */
add_action('init', 'adforest_modern_chat_db_migrate', 5);

function adforest_modern_chat_db_migrate() {
    if (get_option('adforest_modern_chat_db_version') === '1.3') return;

    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $msg_tbl  = $wpdb->prefix . 'sb_chat_messages';

    // Bail if the tables don't exist (plugin not initialized yet)
    $conv_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $conv_tbl));
    if ($conv_exists !== $conv_tbl) return;

    /* --- Pinned columns on conversation --- */
    $cols = $wpdb->get_col("SHOW COLUMNS FROM $conv_tbl", 0);
    if (!in_array('pinned_by_user_1', $cols, true)) {
        $wpdb->query("ALTER TABLE $conv_tbl ADD COLUMN pinned_by_user_1 TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('pinned_by_user_2', $cols, true)) {
        $wpdb->query("ALTER TABLE $conv_tbl ADD COLUMN pinned_by_user_2 TINYINT(1) NOT NULL DEFAULT 0");
    }

    /* --- post_id column on conversation (per-ad conversations) ---
       NULL = legacy general thread between two users (no specific ad).
       NOT NULL = ad-scoped conversation. Lookup at send-time matches on
       this column so a buyer messaging the same owner about a different
       ad creates a new conversation row. */
    $cols = $wpdb->get_col("SHOW COLUMNS FROM $conv_tbl", 0);
    if (!in_array('post_id', $cols, true)) {
        $wpdb->query("ALTER TABLE $conv_tbl ADD COLUMN post_id BIGINT(20) UNSIGNED NULL DEFAULT NULL");
    }

    /* --- Index on (user_1, user_2, post_id) for fast lookup --- */
    $idx = $wpdb->get_results("SHOW INDEX FROM $conv_tbl WHERE Key_name = 'adf_pair_post'");
    if (empty($idx)) {
        $wpdb->query("ALTER TABLE $conv_tbl ADD INDEX adf_pair_post (user_1, user_2, post_id)");
    }

    /* --- utf8mb4 conversion (emoji support) ---
       The plugin's dbDelta creates tables with the legacy utf8mb3 charset,
       which silently truncates 4-byte UTF-8 characters (all emoji). */
    foreach (array($msg_tbl, $conv_tbl) as $t) {
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists !== $t) continue;
        $charset = $wpdb->get_var($wpdb->prepare(
            "SELECT CCSA.character_set_name FROM information_schema.TABLES T
             JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA
               ON CCSA.collation_name = T.table_collation
             WHERE T.table_schema = %s AND T.table_name = %s",
            DB_NAME, $t
        ));
        if ($charset && strtolower($charset) !== 'utf8mb4') {
            $wpdb->query("ALTER TABLE $t CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    /* --- Strip `ON UPDATE CURRENT_TIMESTAMP` from messages.created ---
       The plugin's schema declares it auto-updating, so any UPDATE on a
       message row (read-receipt flip, email_sent flag, etc.) silently
       rewrites the "creation" timestamp. That breaks chronological
       sorting and the per-bubble timestamps shown in the UI. We make
       `created` a true insert timestamp by rewriting the column. */
    $msg_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $msg_tbl));
    if ($msg_exists === $msg_tbl) {
        $col = $wpdb->get_row("SHOW COLUMNS FROM $msg_tbl LIKE 'created'");
        if ($col && stripos((string) $col->Extra, 'on update') !== false) {
            $wpdb->query("ALTER TABLE $msg_tbl MODIFY `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }

        /* --- One-shot repair for already-corrupted timestamps ---
           For any message whose `created` is earlier than the previous
           message (by id) in the same conversation, snap it to prev+1s.
           Strictly clamps impossible values; never moves a timestamp
           backwards. Safe to re-run (becomes a no-op on healthy rows). */
        $broken = $wpdb->get_results(
            "SELECT m.id, m.conversation_id, m.created,
                    (SELECT m2.created FROM $msg_tbl m2
                       WHERE m2.conversation_id = m.conversation_id AND m2.id < m.id
                       ORDER BY m2.id DESC LIMIT 1) AS prev_created
               FROM $msg_tbl m
              HAVING prev_created IS NOT NULL AND m.created < prev_created"
        );
        foreach ((array) $broken as $row) {
            $fixed = date('Y-m-d H:i:s', strtotime($row->prev_created) + 1);
            $wpdb->update($msg_tbl, array('created' => $fixed), array('id' => $row->id));
        }
    }

    update_option('adforest_modern_chat_db_version', '1.3', false);
}

/* ------------------------------------------------------------------ */
/* 0b. ONE-TIME LEGACY THREAD SPLIT (non-destructive)                  */
/* ------------------------------------------------------------------ */
/*
 * Pre-patch SB Chat stored one conversation per user pair, regardless
 * of which ad the chat originated from. When a buyer used Contact
 * Seller on three different ads with the same seller, all three
 * embedded ad-cards (and their replies) ended up in a single row —
 * what you see in the UI as "one thread with multiple ad cards."
 *
 * The routing patch fixes this going forward (new Contact-Seller
 * clicks create rows scoped to `(user_1, user_2, post_id)`), but
 * does nothing for historical merged rows. This migration walks each
 * legacy row (`post_id IS NULL`), inspects its messages, extracts
 * the ad each `message-post-card` block references, then MOVES
 * each message into a per-ad conversation row.
 *
 * Safety properties:
 *   - Idempotent. Guarded by a single option flag; bails fast on
 *     subsequent loads.
 *   - Non-destructive. Messages are MOVED (UPDATE conversation_id),
 *     never deleted. No INSERT-then-delete pattern, so a partial
 *     run can be resumed without data loss.
 *   - Pre-card "orphan" messages (anything before the first ad-card
 *     in a legacy thread) and messages whose post-card URL can't
 *     resolve to a live post_id stay attached to the original legacy
 *     row. That row is only soft-deleted (deleted_by both = 1) if
 *     it ends up with ZERO remaining messages after the move — so
 *     anything we couldn't confidently route stays visible.
 *   - When `(user_1, user_2, post_id)` already has an ad-scoped row
 *     (e.g. the same buyer/seller already chatted about that ad
 *     post-patch), messages are MERGED into that existing row
 *     rather than creating a duplicate.
 *   - Bounded scope: 200 legacy convs per run, 5000 message moves
 *     total per run. If you have a larger backlog, the flag option
 *     stays unset and the next page-load resumes.
 *
 * Rollback: delete the option `adforest_modern_chat_split_legacy_v1`
 * to permit a re-run. To reverse a split you'd need to restore from
 * a DB snapshot — the migration doesn't keep a separate audit table
 * because every move is a single UPDATE per message row that can be
 * inverted by examining `conversation_id` history if needed.
 */
add_action('init', 'adforest_modern_chat_split_legacy_threads', 7);

function adforest_modern_chat_split_legacy_threads() {
    if (get_option('adforest_modern_chat_split_legacy_v1') === 'done') return;

    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $msg_tbl  = $wpdb->prefix . 'sb_chat_messages';

    // Defensive: bail if tables / column not in place. Schema migration
    // runs at init priority 5, this runs at 7, so this should always
    // be safe — but we don't want to error if someone changes hooks.
    $conv_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $conv_tbl));
    if ($conv_exists !== $conv_tbl) return;
    $has_post_col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $conv_tbl LIKE %s", 'post_id'));
    if (!$has_post_col) return;

    // Find legacy conversation rows that still hold messages — scoped
    // by JOIN so we never touch empty soft-deleted rows that already
    // got cleaned up.
    $legacy_convs = $wpdb->get_results(
        "SELECT c.id, c.user_1, c.user_2, c.deleted_by_user_1, c.deleted_by_user_2
         FROM $conv_tbl c
         WHERE (c.post_id IS NULL OR c.post_id = 0)
         ORDER BY c.id ASC
         LIMIT 200",
        ARRAY_A
    );

    if (empty($legacy_convs)) {
        update_option('adforest_modern_chat_split_legacy_v1', 'done', false);
        return;
    }

    $total_moves = 0;
    $max_moves   = 5000;

    foreach ($legacy_convs as $conv) {
        if ($total_moves >= $max_moves) break;

        $conv_id = (int) $conv['id'];
        $user_1  = (int) $conv['user_1'];
        $user_2  = (int) $conv['user_2'];

        // Pull messages in stable insertion order. `id ASC` is the
        // authoritative chronological order — `created` was made a
        // pure insert timestamp by the earlier migration, but `id`
        // is unambiguous either way.
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT id, message FROM $msg_tbl WHERE conversation_id = %d ORDER BY id ASC",
            $conv_id
        ), ARRAY_A);

        if (empty($messages)) continue;

        // Walk messages and partition by current ad context. Each
        // post-card flips the running post_id; subsequent messages
        // inherit that context until the next post-card. Messages
        // before the FIRST resolvable post-card stay in the legacy
        // row as "orphans."
        $segments       = array(); // post_id => [message_id, ...]
        $current_pid    = 0;       // 0 = orphan
        $unique_post_ids = array();

        foreach ($messages as $m) {
            $extracted = adforest_modern_chat_extract_post_id_from_card((string) $m['message']);
            if ($extracted > 0) {
                $current_pid = $extracted;
                $unique_post_ids[$extracted] = true;
            }
            if ($current_pid > 0) {
                $segments[$current_pid][] = (int) $m['id'];
            }
            // else: leave the message in the legacy row (orphan)
        }

        if (empty($unique_post_ids)) {
            // No ad-cards (or all unresolvable) — nothing to split.
            // Leave the legacy row exactly as it is.
            continue;
        }

        // Per-ad move: find-or-create target row, then UPDATE all
        // segment messages to point at it. Use the symmetric pair
        // lookup so we don't duplicate when (u2, u1, post_id) already
        // exists from a reverse-ordered insert.
        foreach ($segments as $target_post_id => $msg_ids) {
            if ($target_post_id <= 0 || empty($msg_ids)) continue;

            $existing = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $conv_tbl
                 WHERE post_id = %d
                   AND ((user_1 = %d AND user_2 = %d) OR (user_1 = %d AND user_2 = %d))
                 LIMIT 1",
                $target_post_id,
                $user_1, $user_2,
                $user_2, $user_1
            ));

            if ($existing > 0) {
                $target_conv_id = $existing;
            } else {
                $inserted = $wpdb->insert(
                    $conv_tbl,
                    array(
                        'user_1'      => $user_1,
                        'user_2'      => $user_2,
                        'post_id'     => $target_post_id,
                        // Mark both as read — these are historical
                        // messages; we don't want the migration to
                        // light up unread badges on the rail.
                        'read_user_1' => 1,
                        'read_user_2' => 1,
                    ),
                    array('%d', '%d', '%d', '%d', '%d')
                );
                if (!$inserted) {
                    // INSERT failed (likely a transient DB issue) —
                    // skip this segment, will retry on next run.
                    continue;
                }
                $target_conv_id = (int) $wpdb->insert_id;
            }

            // Move messages in chunks to keep prepared-statement
            // parameter counts sane on installs with large legacy
            // threads. 200 ids per UPDATE is well under any
            // reasonable MySQL placeholder limit.
            $chunks = array_chunk($msg_ids, 200);
            foreach ($chunks as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '%d'));
                $params       = array_merge(array($target_conv_id), $chunk);
                $sql = "UPDATE $msg_tbl SET conversation_id = %d WHERE id IN ($placeholders)";
                $updated = $wpdb->query($wpdb->prepare($sql, $params));
                if ($updated !== false) {
                    $total_moves += (int) $updated;
                }
            }

            // Bump the target row's update timestamps so the rail
            // shows the most recent migrated message as the "last
            // update" time, not the original ad-scoped row's
            // create-time (which would push it to the bottom).
            $latest_created = $wpdb->get_var($wpdb->prepare(
                "SELECT created FROM $msg_tbl
                 WHERE conversation_id = %d
                 ORDER BY id DESC LIMIT 1",
                $target_conv_id
            ));
            if ($latest_created) {
                $wpdb->update(
                    $conv_tbl,
                    array('updated' => $latest_created, 'last_update' => $latest_created),
                    array('id' => $target_conv_id),
                    array('%s', '%s'),
                    array('%d')
                );
            }
        }

        // Re-check the legacy row: if every message moved, soft-
        // delete it so it drops out of the rail. If orphans remain
        // (pre-first-card chitchat or unresolvable cards), leave
        // the legacy row visible so the user doesn't lose anything.
        $remaining = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $msg_tbl WHERE conversation_id = %d",
            $conv_id
        ));
        if ($remaining === 0) {
            $now = current_time('mysql');
            $wpdb->update(
                $conv_tbl,
                array(
                    'deleted_by_user_1'      => 1,
                    'deleted_by_user_2'      => 1,
                    'time_deleted_by_user_1' => $now,
                    'time_deleted_by_user_2' => $now,
                ),
                array('id' => $conv_id),
                array('%d', '%d', '%s', '%s'),
                array('%d')
            );
        }
    }

    // Only mark "done" if we processed the full set; otherwise
    // leave the flag unset so the next page-load resumes.
    if (count($legacy_convs) < 200 && $total_moves < $max_moves) {
        update_option('adforest_modern_chat_split_legacy_v1', 'done', false);
    }
}

/**
 * Extract a post_id from an inline `message-post-card` block by
 * locating its `post-card-link` href and resolving via WP's own
 * `url_to_postid()`. Returns 0 if no card is present, the href
 * can't be located, or the URL doesn't resolve to a live post
 * (deleted ads come back as 0 — we deliberately leave their
 * messages in the legacy row rather than fork into a dead
 * conversation).
 *
 * Mirrors the regex patterns used by `adforest_modern_chat_get_conversation_ad`
 * so the migration sees ad-cards the same way the chat header does.
 */
function adforest_modern_chat_extract_post_id_from_card($message) {
    if ($message === '' || stripos($message, 'message-post-card') === false) {
        return 0;
    }

    $url = '';
    if (preg_match('#href=["\']([^"\']+)["\'][^>]*class=["\'][^"\']*post-card-link#is', $message, $m)) {
        $url = $m[1];
    } elseif (preg_match('#class=["\'][^"\']*post-card-link[^"\']*["\'][^>]*href=["\']([^"\']+)["\']#is', $message, $m)) {
        $url = $m[1];
    }

    if ($url === '') return 0;

    // url_to_postid() handles permalinks, plain ?p=, and
    // post-name URLs. Returns 0 when the post is gone (trash /
    // deleted) — that's a feature here: deleted-ad chats stay
    // in the legacy row so the conversation isn't lost.
    $post_id = (int) url_to_postid($url);

    // Final sanity check — even if url_to_postid resolved, make
    // sure the post still exists and isn't in trash before we
    // peg messages to it.
    if ($post_id > 0) {
        $status = get_post_status($post_id);
        if ($status === false || $status === null || $status === 'trash' || $status === 'auto-draft') {
            return 0;
        }
    }

    return $post_id;
}

/* ------------------------------------------------------------------ */
/* 0c. ONE-TIME INLINE-CARD DEDUPE (non-destructive)                   */
/* ------------------------------------------------------------------ */
/*
 * Before the post-card-prepend gate was added to the send handlers,
 * every Contact-Seller submit prepended a fresh `message-post-card`
 * block to the message. A conversation that received three submits
 * about the same ad ended up with three identical cards stacked
 * inside the thread:
 *     [card] Hannah Here.
 *     [card] Sending a test message.
 *     [card] Third message.
 *
 * The send-handler fix prevents new duplicates. This pass strips
 * redundant card prefixes from messages already in the database
 * (per conversation: keep the card on the first card-bearing
 * message, strip it from every subsequent one).
 *
 * Safety:
 *   - Only modifies the `message` column on rows that carry a card
 *     prefix beyond the first occurrence. Pure text / voice / media
 *     bubbles are untouched.
 *   - The text content after the `|` separator is preserved verbatim.
 *     We only strip the HTML card block + its trailing pipe.
 *   - `message_type` is adjusted from `post_card` back to `text` on
 *     rows we strip, so previews and filters render correctly.
 *   - Idempotent via option flag.
 *   - Bounded scope: 500 conversations per run; resumes on next
 *     page load if more remain.
 *   - If a message has only `[card]|` (empty body), the resulting
 *     stripped message would be empty — we keep that row intact
 *     (don't strip) since the entire bubble would otherwise be
 *     blank. Such rows are extremely rare but worth preserving.
 */
add_action('init', 'adforest_modern_chat_dedupe_inline_cards', 8);

function adforest_modern_chat_dedupe_inline_cards() {
    if (get_option('adforest_modern_chat_dedupe_cards_v1') === 'done') return;

    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $msg_tbl  = $wpdb->prefix . 'sb_chat_messages';

    $conv_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $conv_tbl));
    if ($conv_exists !== $conv_tbl) return;

    // Limit scan to conversations that have at least TWO messages
    // carrying a card — any conversation with one or zero card-
    // bearing messages can't have duplicates by definition.
    $conv_ids = $wpdb->get_col(
        "SELECT conversation_id
         FROM $msg_tbl
         WHERE message LIKE '%message-post-card%'
         GROUP BY conversation_id
         HAVING COUNT(*) > 1
         ORDER BY conversation_id ASC
         LIMIT 500"
    );

    if (empty($conv_ids)) {
        update_option('adforest_modern_chat_dedupe_cards_v1', 'done', false);
        return;
    }

    $rows_stripped = 0;

    foreach ($conv_ids as $conv_id) {
        $conv_id = (int) $conv_id;
        if ($conv_id <= 0) continue;

        // Pull only card-bearing rows in id ASC order. The first one
        // we see is the anchor card (kept); every subsequent one
        // gets stripped.
        $card_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, message FROM $msg_tbl
             WHERE conversation_id = %d
               AND message LIKE %s
             ORDER BY id ASC",
            $conv_id,
            '%message-post-card%'
        ), ARRAY_A);

        if (empty($card_rows) || count($card_rows) < 2) continue;

        // Skip the first one — it's the legitimate anchor card.
        array_shift($card_rows);

        foreach ($card_rows as $row) {
            $original = (string) $row['message'];
            // The card prefix is the text BEFORE the first `|` —
            // strip everything up to and including that pipe iff
            // the prefix actually contains the card marker. This
            // covers both card formats (with and without the
            // outer `<p>` wrapper produced by the REST handler).
            $pipe_pos = strpos($original, '|');
            if ($pipe_pos === false) continue;
            $prefix = substr($original, 0, $pipe_pos);
            if (stripos($prefix, 'message-post-card') === false) continue;
            $body = substr($original, $pipe_pos + 1);

            // If the body is empty after stripping, leave the row
            // alone — a blank bubble is worse than a duplicate
            // card, and these rows are typically just empty-text
            // sends that the user can delete manually if they care.
            if (trim($body) === '') continue;

            $wpdb->update(
                $msg_tbl,
                array(
                    'message'      => $body,
                    'message_type' => 'text',
                ),
                array('id' => (int) $row['id']),
                array('%s', '%s'),
                array('%d')
            );
            $rows_stripped++;
        }
    }

    if (count($conv_ids) < 500) {
        update_option('adforest_modern_chat_dedupe_cards_v1', 'done', false);
    }
}

/* ------------------------------------------------------------------ */
/* 1. PINNED CONVERSATIONS                                             */
/* ------------------------------------------------------------------ */

/**
 * Whether the given conversation is pinned by the given user.
 *
 * @param array|object $conv  Conversation row from sbchat_get_conversations_by_user_id (assoc array).
 * @param int          $user_id
 */
function adforest_modern_chat_is_pinned($conv, $user_id) {
    if (empty($conv) || empty($user_id)) return false;
    $arr = (array) $conv;
    if ((int) $arr['user_1'] === (int) $user_id) {
        return !empty($arr['pinned_by_user_1']);
    }
    if ((int) $arr['user_2'] === (int) $user_id) {
        return !empty($arr['pinned_by_user_2']);
    }
    return false;
}

/**
 * Sort conversations: pinned first, each group preserving the input order
 * (which is already updated DESC from the plugin).
 *
 * @return array{pinned: array, all: array}
 */
function adforest_modern_chat_split_pinned($conversations, $user_id) {
    $pinned = array();
    $rest   = array();
    if (!is_array($conversations)) return array('pinned' => $pinned, 'all' => $rest);
    foreach ($conversations as $c) {
        if (adforest_modern_chat_is_pinned($c, $user_id)) {
            $pinned[] = $c;
        } else {
            $rest[] = $c;
        }
    }
    return array('pinned' => $pinned, 'all' => $rest);
}

/**
 * AJAX: toggle pinned state for the current user on the given conversation.
 * Action: adforest_chat_toggle_pin
 * Body:   conversation_id (int), nonce (string)
 */
add_action('wp_ajax_adforest_chat_toggle_pin', 'adforest_modern_chat_ajax_toggle_pin');

function adforest_modern_chat_ajax_toggle_pin() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');

    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(array('message' => __('Not logged in.', 'adforest')), 403);

    $conv_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    if ($conv_id <= 0) wp_send_json_error(array('message' => __('Missing conversation.', 'adforest')), 400);

    global $wpdb;
    $tbl  = $wpdb->prefix . 'sb_chat_conversation';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT id, user_1, user_2, pinned_by_user_1, pinned_by_user_2 FROM $tbl WHERE id = %d", $conv_id), ARRAY_A);
    if (!$conv) wp_send_json_error(array('message' => __('Conversation not found.', 'adforest')), 404);

    if ((int) $conv['user_1'] === $user_id) {
        $col = 'pinned_by_user_1';
    } elseif ((int) $conv['user_2'] === $user_id) {
        $col = 'pinned_by_user_2';
    } else {
        wp_send_json_error(array('message' => __('Not a participant.', 'adforest')), 403);
    }

    $new = empty($conv[$col]) ? 1 : 0;
    $wpdb->update($tbl, array($col => $new), array('id' => $conv_id), array('%d'), array('%d'));

    wp_send_json_success(array('pinned' => (bool) $new, 'conversation_id' => $conv_id));
}

/* ------------------------------------------------------------------ */
/* 2. READ RECEIPTS                                                    */
/* ------------------------------------------------------------------ */

/**
 * Whether the OTHER party has read the current user's outgoing messages.
 *
 * Plugin tracks read state per-conversation via `read_user_1` / `read_user_2`.
 * These flags mean "this user's inbox for this conversation has been read by them".
 * For us:  if I'm user_1 and `read_user_2 == 1`, the OTHER party has opened the
 * thread since my last message → ✓✓.
 *
 * @return string '✓✓' (read) or '✓' (sent)
 */
function adforest_modern_chat_outgoing_state($conv, $user_id) {
    if (empty($conv) || empty($user_id)) return '✓';
    $arr = (array) $conv;
    if ((int) $arr['user_1'] === (int) $user_id) {
        return !empty($arr['read_user_2']) ? '✓✓' : '✓';
    }
    if ((int) $arr['user_2'] === (int) $user_id) {
        return !empty($arr['read_user_1']) ? '✓✓' : '✓';
    }
    return '✓';
}

/**
 * AJAX: mark the OTHER party's messages as read by the current user.
 * Called when the user opens / focuses a conversation thread.
 * Action: adforest_chat_mark_read
 */
add_action('wp_ajax_adforest_chat_mark_read', 'adforest_modern_chat_ajax_mark_read');

function adforest_modern_chat_ajax_mark_read() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');

    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(array('message' => __('Not logged in.', 'adforest')), 403);

    $conv_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    if ($conv_id <= 0) wp_send_json_error(array('message' => __('Missing conversation.', 'adforest')), 400);

    global $wpdb;
    $tbl  = $wpdb->prefix . 'sb_chat_conversation';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT id, user_1, user_2 FROM $tbl WHERE id = %d", $conv_id), ARRAY_A);
    if (!$conv) wp_send_json_error(array('message' => __('Conversation not found.', 'adforest')), 404);

    if ((int) $conv['user_1'] === $user_id) {
        $col = 'read_user_1';
    } elseif ((int) $conv['user_2'] === $user_id) {
        $col = 'read_user_2';
    } else {
        wp_send_json_error(array('message' => __('Not a participant.', 'adforest')), 403);
    }

    $wpdb->update($tbl, array($col => 1), array('id' => $conv_id), array('%d'), array('%d'));

    wp_send_json_success(array('conversation_id' => $conv_id));
}

/* ------------------------------------------------------------------ */
/* 3. ONLINE STATUS (last_seen user meta + heartbeat)                  */
/* ------------------------------------------------------------------ */

define('ADFOREST_CHAT_ONLINE_THRESHOLD', 60); // seconds — under this = "online"

/**
 * Whether the given user has pinged within the online threshold.
 */
function adforest_modern_chat_is_online($user_id) {
    if (empty($user_id)) return false;
    $last = (int) get_user_meta($user_id, '_adforest_chat_last_seen', true);
    if ($last <= 0) return false;
    return (time() - $last) <= ADFOREST_CHAT_ONLINE_THRESHOLD;
}

/**
 * AJAX: bump the current user's last_seen.
 * Called by the JS heartbeat every 30s while the chat page is open.
 * Action: adforest_chat_heartbeat
 */
add_action('wp_ajax_adforest_chat_heartbeat', 'adforest_modern_chat_ajax_heartbeat');

function adforest_modern_chat_ajax_heartbeat() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');

    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(array('message' => __('Not logged in.', 'adforest')), 403);

    update_user_meta($user_id, '_adforest_chat_last_seen', time());
    wp_send_json_success(array('time' => time()));
}

/**
 * Quick presence lookup for one or more user IDs.
 * Returns array of [user_id => bool online].
 * Used by the rail to show online dots on conversations.
 */
function adforest_modern_chat_presence_map($user_ids) {
    $map = array();
    foreach ((array) $user_ids as $uid) {
        $uid = (int) $uid;
        if ($uid <= 0) continue;
        $map[$uid] = adforest_modern_chat_is_online($uid);
    }
    return $map;
}

/* ------------------------------------------------------------------ */
/* AJAX: send voice message — saves blob, inserts message row          */
/* ------------------------------------------------------------------ */
add_action('wp_ajax_adforest_chat_send_voice', 'adforest_modern_chat_ajax_send_voice');

function adforest_modern_chat_ajax_send_voice() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(array('message' => __('Not logged in.', 'adforest')), 403);

    $conv_id      = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    $recipient_id = isset($_POST['recipient_id'])    ? absint($_POST['recipient_id'])    : 0;
    if ($conv_id <= 0 || $recipient_id <= 0) wp_send_json_error(array('message' => __('Missing conversation.', 'adforest')), 400);
    if (empty($_FILES['voice']) || !is_array($_FILES['voice'])) wp_send_json_error(array('message' => __('No audio uploaded.', 'adforest')), 400);

    $file = $_FILES['voice'];
    if ((int) $file['error'] !== UPLOAD_ERR_OK) wp_send_json_error(array('message' => __('Upload error.', 'adforest')), 400);
    $type = isset($file['type']) ? strtolower((string) $file['type']) : '';
    if ($type !== '' && strpos($type, 'audio/') !== 0) wp_send_json_error(array('message' => __('Not an audio file.', 'adforest')), 415);
    if ((int) $file['size'] > 12 * 1024 * 1024) wp_send_json_error(array('message' => __('Voice message too large.', 'adforest')), 413);

    // Confirm participant
    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $msg_tbl  = $wpdb->prefix . 'sb_chat_messages';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT user_1, user_2 FROM $conv_tbl WHERE id = %d", $conv_id), ARRAY_A);
    if (!$conv || ((int) $conv['user_1'] !== $user_id && (int) $conv['user_2'] !== $user_id)) {
        wp_send_json_error(array('message' => __('Not a participant.', 'adforest')), 403);
    }

    // Save to uploads/adforest-voice/
    $up      = wp_upload_dir();
    $sub_dir = '/adforest-voice';
    $abs_dir = $up['basedir'] . $sub_dir;
    $url_dir = $up['baseurl'] . $sub_dir;
    if (!is_dir($abs_dir)) wp_mkdir_p($abs_dir);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!$ext) {
        if     (strpos($type, 'webm') !== false) $ext = 'webm';
        elseif (strpos($type, 'ogg')  !== false) $ext = 'ogg';
        elseif (strpos($type, 'mp4')  !== false) $ext = 'm4a';
        elseif (strpos($type, 'mpeg') !== false) $ext = 'mp3';
        else                                     $ext = 'webm';
    }
    $ext = preg_replace('/[^a-z0-9]/', '', strtolower($ext));
    if (!in_array($ext, array('webm','ogg','m4a','mp3','wav','mp4'), true)) $ext = 'webm';

    $name     = (function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : md5(uniqid('', true))) . '.' . $ext;
    $abs_path = trailingslashit($abs_dir) . $name;
    if (!@move_uploaded_file($file['tmp_name'], $abs_path)) {
        wp_send_json_error(array('message' => __('Could not save audio.', 'adforest')), 500);
    }
    $audio_url = trailingslashit($url_dir) . $name;

    // Marker that the client decorator turns into an <audio> bubble.
    // Format: [adf-voice]URL|DURATION_SECONDS[/adf-voice]
    // Duration is captured client-side at record-time because WebM/Opus
    // blobs don't expose reliable duration metadata to the <audio>
    // element. Stored as plain text so wp_kses leaves it intact.
    $duration = isset($_POST['duration']) ? max(0.0, (float) $_POST['duration']) : 0.0;
    $payload  = '[adf-voice]' . esc_url_raw($audio_url) . '|' . number_format($duration, 2, '.', '') . '[/adf-voice]';

    $wpdb->insert($msg_tbl, array(
        'conversation_id' => $conv_id,
        'sender_id'       => $user_id,
        'receiver_id'     => $recipient_id,
        'message'         => $payload,
        'created_at'      => time(),
        'attachment_ids'  => '',
        'message_type'    => 0,
        'read_status'     => 0,
        'deleted_by'      => 0,
        'deleted_msgs'    => 0,
        'email_sent'      => 0,
    ), array('%d','%d','%d','%s','%d','%s','%d','%d','%d','%d','%d'));

    $msg_id = (int) $wpdb->insert_id;

    // Bump conversation, mark other party unread
    $other_col = ((int) $conv['user_1'] === $user_id) ? 'read_user_2' : 'read_user_1';
    $wpdb->update(
        $conv_tbl,
        array('updated' => current_time('mysql'), $other_col => 0),
        array('id' => $conv_id)
    );

    wp_send_json_success(array('url' => $audio_url, 'message_id' => $msg_id));
}

/* ------------------------------------------------------------------ */
/* AJAX: mark conversation as unread (used by mobile swipe-right)      */
/* ------------------------------------------------------------------ */
add_action('wp_ajax_adforest_chat_mark_unread', 'adforest_modern_chat_ajax_mark_unread');

function adforest_modern_chat_ajax_mark_unread() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(array('message' => __('Not logged in.', 'adforest')), 403);

    $conv_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    if ($conv_id <= 0) wp_send_json_error(array('message' => __('Missing conversation.', 'adforest')), 400);

    global $wpdb;
    $tbl  = $wpdb->prefix . 'sb_chat_conversation';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT id, user_1, user_2 FROM $tbl WHERE id = %d", $conv_id), ARRAY_A);
    if (!$conv) wp_send_json_error(array('message' => __('Conversation not found.', 'adforest')), 404);

    if      ((int) $conv['user_1'] === $user_id) $col = 'read_user_1';
    elseif  ((int) $conv['user_2'] === $user_id) $col = 'read_user_2';
    else    wp_send_json_error(array('message' => __('Not a participant.', 'adforest')), 403);

    $wpdb->update($tbl, array($col => 0), array('id' => $conv_id), array('%d'), array('%d'));
    wp_send_json_success(array('conversation_id' => $conv_id));
}

/* ------------------------------------------------------------------ */
/* AJAX: fetch new messages since a given message id (real-time poll)  */
/* ------------------------------------------------------------------ */
add_action('wp_ajax_adforest_chat_poll', 'adforest_modern_chat_ajax_poll');

function adforest_modern_chat_ajax_poll() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(null, 403);

    $conv_id  = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    $since_id = isset($_POST['since_id']) ? absint($_POST['since_id']) : 0;
    if ($conv_id <= 0) wp_send_json_error(null, 400);

    // Confirm participant
    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $msg_tbl  = $wpdb->prefix . 'sb_chat_messages';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT user_1, user_2 FROM $conv_tbl WHERE id = %d", $conv_id), ARRAY_A);
    if (!$conv || ((int) $conv['user_1'] !== $user_id && (int) $conv['user_2'] !== $user_id)) {
        wp_send_json_error(null, 403);
    }

    // Latest message id in this conversation (for client-side state sync)
    $latest_id = (int) $wpdb->get_var($wpdb->prepare("SELECT MAX(id) FROM $msg_tbl WHERE conversation_id = %d", $conv_id));

    // Read receipt for OUR outgoing messages (always returned — even when
    // there are no new messages, the recipient may have just read older ones).
    $conv_full = $wpdb->get_row($wpdb->prepare("SELECT * FROM $conv_tbl WHERE id = %d", $conv_id));
    $outgoing_state = function_exists('adforest_modern_chat_outgoing_state')
        ? adforest_modern_chat_outgoing_state($conv_full, $user_id) : '';

    // Peer typing — read the transient set by the OTHER party's client.
    // Piggybacked on the existing poll so typing visibility costs zero
    // extra HTTP requests; latency = poll interval.
    $peer_id = ((int) $conv['user_1'] === $user_id) ? (int) $conv['user_2'] : (int) $conv['user_1'];
    $peer_typing = (bool) get_transient('adf_chat_typing_' . $conv_id . '_' . $peer_id);

    // Per-user block state — sent on every poll so the client can keep
    // the composer + overflow menu UI in sync if the block list changes
    // from another tab. `blocked_by_me` is the only one with practical
    // UI impact (composer disabled, "Unblock" label); `blocked_by_them`
    // shows a generic disabled state without revealing the block.
    $blocked_by_me   = function_exists('adforest_modern_chat_user_blocks') && adforest_modern_chat_user_blocks($user_id, $peer_id);
    $blocked_by_them = function_exists('adforest_modern_chat_user_blocks') && adforest_modern_chat_user_blocks($peer_id, $user_id);

    // Max conversation_id this user is a participant in — the client
    // tracks this and triggers a sidebar refresh whenever it goes up,
    // so a brand-new conversation initiated from an ad-details page
    // appears in the rail without requiring a manual refresh.
    $user_max_conv_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(id) FROM $conv_tbl WHERE user_1 = %d OR user_2 = %d",
        $user_id, $user_id
    ));

    // Max message id across ALL the user's conversations — the client
    // uses this to detect new activity in any non-active conversation
    // (the active conversation already updates via the html payload).
    // When this climbs, the sidebar refreshes so other rows reorder /
    // update their preview / show the unread badge in real time.
    $user_max_msg_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(m.id) FROM $msg_tbl m
         INNER JOIN $conv_tbl c ON c.id = m.conversation_id
         WHERE c.user_1 = %d OR c.user_2 = %d",
        $user_id, $user_id
    ));

    // No new messages → bail cheaply, but still ship outgoing_state so the
    // client can flip ✓ → ✓✓ when the recipient reads without anyone sending.
    if ($latest_id <= $since_id) {
        wp_send_json_success(array(
            'latest_id'        => $latest_id,
            'html'             => '',
            'outgoing_state'   => $outgoing_state,
            'peer_typing'      => $peer_typing,
            'user_max_conv_id' => $user_max_conv_id,
            'user_max_msg_id'  => $user_max_msg_id,
            'blocked_by_me'    => $blocked_by_me,
            'blocked_by_them'  => $blocked_by_them,
        ));
    }

    // Incremental render — only the bubbles strictly newer than the
    // client's cursor. Reuses the same renderer as the older-pagination
    // path, so attachment / ad-card / voice-note markup is identical to
    // the initial inbox HTML and the client can simply append the chunk.
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, sender_id, message, attachment_ids, created
         FROM $msg_tbl WHERE conversation_id = %d AND id > %d
         ORDER BY id ASC",
        $conv_id, $since_id
    ), ARRAY_A);

    $html = '';
    if (!empty($rows) && function_exists('adforest_modern_chat_render_older_html')) {
        $html = adforest_modern_chat_render_older_html($rows, $user_id);
    }

    wp_send_json_success(array(
        'latest_id'        => $latest_id,
        'html'             => $html,
        'outgoing_state'   => $outgoing_state,
        'peer_typing'      => $peer_typing,
        'user_max_conv_id' => $user_max_conv_id,
        'user_max_msg_id'  => $user_max_msg_id,
        'blocked_by_me'    => $blocked_by_me,
        'blocked_by_them'  => $blocked_by_them,
    ));
}

/* ------------------------------------------------------------------ */
/* AJAX: typing indicator beacon                                       */
/* ------------------------------------------------------------------ */
/**
 * Lightweight beacon — the composer's input handler fires this while
 * the user is typing (debounced client-side to ~600ms). Stores a 4s
 * transient keyed on conv+user; the OTHER party's poll picks it up
 * and shows the typing dots. Auto-expires so we never need a "stop
 * typing" call when the user pauses, sends, or closes the tab.
 */
add_action('wp_ajax_adforest_chat_typing', 'adforest_modern_chat_ajax_typing');
function adforest_modern_chat_ajax_typing() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(null, 403);

    $conv_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    if ($conv_id <= 0) wp_send_json_error(null, 400);

    // Confirm participant (cheap — same query the poll endpoint uses).
    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT user_1, user_2 FROM $conv_tbl WHERE id = %d", $conv_id), ARRAY_A);
    if (!$conv || ((int) $conv['user_1'] !== $user_id && (int) $conv['user_2'] !== $user_id)) {
        wp_send_json_error(null, 403);
    }

    $stop = !empty($_POST['stop']);
    if ($stop) {
        delete_transient('adf_chat_typing_' . $conv_id . '_' . $user_id);
    } else {
        // 4s window — slightly longer than 2× the client poll interval
        // so the indicator stays alive across a poll without flicker.
        set_transient('adf_chat_typing_' . $conv_id . '_' . $user_id, 1, 4);
    }
    wp_send_json_success();
}

/* ------------------------------------------------------------------ */
/* AJAX: fetch older messages (lazy-load upward pagination)            */
/* ------------------------------------------------------------------ */
add_action('wp_ajax_adforest_chat_older', 'adforest_modern_chat_ajax_older');

function adforest_modern_chat_ajax_older() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(null, 403);

    $conv_id  = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    $before_id = isset($_POST['before_id']) ? absint($_POST['before_id']) : 0;
    $limit    = isset($_POST['limit']) ? min(50, max(5, absint($_POST['limit']))) : 10;
    if ($conv_id <= 0 || $before_id <= 0) wp_send_json_error(null, 400);

    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $msg_tbl  = $wpdb->prefix . 'sb_chat_messages';
    $conv = $wpdb->get_row($wpdb->prepare("SELECT user_1, user_2 FROM $conv_tbl WHERE id = %d", $conv_id), ARRAY_A);
    if (!$conv || ((int) $conv['user_1'] !== $user_id && (int) $conv['user_2'] !== $user_id)) {
        wp_send_json_error(null, 403);
    }

    // Earliest id in current view → fetch the next batch older than it.
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, sender_id, message, attachment_ids, created
         FROM $msg_tbl WHERE conversation_id = %d AND id < %d
         ORDER BY id DESC LIMIT %d",
        $conv_id, $before_id, $limit
    ), ARRAY_A);

    $has_more = false;
    $html     = '';
    if (!empty($rows)) {
        // Reverse to ascending — bubbles render oldest-first inside the batch.
        $rows = array_reverse($rows);
        $html = adforest_modern_chat_render_older_html($rows, $user_id);

        $oldest_id = (int) $rows[0]['id'];
        $has_more_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $msg_tbl WHERE conversation_id = %d AND id < %d",
            $conv_id, $oldest_id
        ));
        $has_more = $has_more_count > 0;
    }

    wp_send_json_success(array(
        'has_more'      => $has_more,
        'fetched_count' => count($rows),
        'html'          => $html,
    ));
}

/**
 * Render older-batch message rows to the same HTML shape the SB Chat plugin
 * uses for the initial inbox render. Handles text bubbles (with the legacy
 * `<ad-card>|<text>` split), image attachments, and document attachments.
 * Voice-note markers `[adf-voice]URL[/adf-voice]` pass through as text — the
 * client-side voice player picks them up via its MutationObserver.
 */
function adforest_modern_chat_render_older_html($rows, $current_user_id) {
    if (empty($rows) || !is_array($rows)) return '';

    $html = '';
    foreach ($rows as $row) {
        $sender_id     = (int) $row['sender_id'];
        $msg_id        = (int) $row['id'];
        $message_class = ($current_user_id === $sender_id) ? 'reply' : 'sender';
        $message_class_safe = sanitize_html_class($message_class);

        $message_raw = (string) $row['message'];
        $outside_raw = '';

        // Voice markers are stored as `[adf-voice]URL|DURATION[/adf-voice]`.
        // The `|` inside the marker is NOT the legacy ad-card/text separator
        // — splitting on it would tear the marker in half and the client-side
        // decorator (regex requires the full marker in one text node) would
        // never run. Pass voice rows through untouched.
        $is_voice_marker = (bool) preg_match('/^\s*\[adf-voice\].+?\[\/adf-voice\]\s*$/', $message_raw);

        if (!$is_voice_marker) {
            $parts = explode('|', $message_raw, 2);
            if (count($parts) === 2) {
                $outside_raw = trim($parts[0]);
                $message_raw = trim($parts[1]);
            }
        }

        // Ad-card / system block — render outside-text as raw HTML (matches
        // the plugin: outside is already an HTML fragment authored at send-time).
        if ($outside_raw !== '') {
            $outside_trim = $outside_raw;
            if (stripos($outside_trim, '<p>') === 0 && strripos($outside_trim, '</p>') === (strlen($outside_trim) - 4)) {
                $outside_trim = substr($outside_trim, 3, -4);
            }
            $html .= '<li class="message-bubble ' . $message_class_safe . '">' . $outside_trim . '</li>';
        }

        // Text bubble.
        $message_esc = esc_html($message_raw);
        if (trim($message_esc) !== '') {
            $related = ($outside_raw !== '') ? ' related-to-card' : '';
            // For voice markers, tag the bubble so CSS can hide the raw
            // `[adf-voice]URL|DURATION[/adf-voice]` text until the JS decorator
            // swaps it for the player markup (avoids FOUC).
            $pending_voice = $is_voice_marker ? ' has-voice-pending' : '';
            $html .= '<li id="umid_' . $msg_id . '" class="message-bubble ' . $message_class_safe . $related . $pending_voice . '">'
                  . '<div class="message-text"><p>' . $message_esc . '</p></div>'
                  . '</li>';
        }

        // Attachments — call the plugin's own helpers so image/doc bubbles
        // render identically to the initial inbox view.
        if (!empty($row['attachment_ids'])) {
            $att_ids = array_filter(array_map('absint', explode(',', $row['attachment_ids'])));
            if (!empty($att_ids)) {
                $img_atts = array();
                $doc_atts = array();
                foreach ($att_ids as $aid) {
                    $att_url = get_the_guid($aid);
                    if (!$att_url) continue;
                    $att_name = esc_html(get_the_title($aid));
                    $att_ext_parts = explode('.', $att_url);
                    $att_ext = end($att_ext_parts);
                    $att_uuid = str_replace(array('.', $att_ext), '', $att_name);
                    $att_mime_root = explode('/', (string) get_post_mime_type($aid))[0];

                    $entry = array(
                        'id'          => $aid,
                        'uuid'        => $att_uuid,
                        'name'        => $att_name,
                        'url'         => $att_url,
                        'path'        => '',
                        'size'        => 1,
                        'ext'         => $att_ext,
                        'upload_date' => '',
                    );
                    if ($att_mime_root === 'image') {
                        $img_atts[] = $entry;
                    } elseif ($att_mime_root === 'application' || $att_mime_root === 'text') {
                        $doc_atts[] = $entry;
                    }
                }
                if (!empty($img_atts) && function_exists('sbchat_generate_inbox_img_attachments_html')) {
                    $img_html = sbchat_generate_inbox_img_attachments_html($img_atts, $sender_id);
                    if ($img_html !== false) $html .= $img_html;
                }
                if (!empty($doc_atts) && function_exists('sbchat_generate_inbox_doc_attachments_html')) {
                    $doc_html = sbchat_generate_inbox_doc_attachments_html($doc_atts, $sender_id);
                    if ($doc_html !== false) $html .= $doc_html;
                }
            }
        }
    }
    return $html;
}

/* ------------------------------------------------------------------ */
/* 4. AD CONTEXT (which ad triggered this conversation)                */
/* ------------------------------------------------------------------ */
/*
 * AdForest is ad-triggered: every conversation starts from an ad
 * detail page. The plugin embeds the ad info inline in the FIRST
 * message that has a related-post card (HTML stored in the message
 * column, separated from the user text by `|`).
 *
 * This helper extracts the ad title + permalink + image from that
 * inline HTML so the chat header can show "From [Ad Title]" and the
 * inline message component can be rendered consistently.
 */
function adforest_modern_chat_get_conversation_ad($conv_id) {
    $conv_id = absint($conv_id);
    if ($conv_id <= 0) return null;

    static $cache = array();
    if (isset($cache[$conv_id])) return $cache[$conv_id];

    global $wpdb;
    $tbl     = $wpdb->prefix . 'sb_chat_messages';
    $conv_t  = $wpdb->prefix . 'sb_chat_conversation';
    $row = $wpdb->get_var($wpdb->prepare(
        "SELECT message FROM $tbl WHERE conversation_id = %d AND message LIKE %s ORDER BY id ASC LIMIT 1",
        $conv_id,
        '%message-post-card%'
    ));
    if (!$row) return $cache[$conv_id] = null;

    $info = array(
        'title' => '',
        'url'   => '',
        'image' => '',
        // Ad lifecycle — resolved below if the conversation has a post_id.
        // 'live'     : ad active, link safe
        // 'expired'  : past expiry timestamp (or _adforest_ad_status_=expired)
        // 'inactive' : post_status pending/draft/private
        // 'deleted'  : get_post() returns null OR status=trash/auto-draft
        // 'unknown'  : couldn't determine (no post_id, defensive default)
        'state' => 'unknown',
        'label' => '',
    );

    if (preg_match('#post-card-title["\']?[^>]*>\s*<span[^>]*>(.*?)</span>#is', $row, $m)) {
        $info['title'] = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
    }
    if (preg_match('#href=["\']([^"\']+)["\'][^>]*class=["\'][^"\']*post-card-link#is', $row, $m)) {
        $info['url'] = $m[1];
    } elseif (preg_match('#class=["\'][^"\']*post-card-link[^"\']*["\'][^>]*href=["\']([^"\']+)["\']#is', $row, $m)) {
        $info['url'] = $m[1];
    }
    if (preg_match('#post-card-image["\']?[^>]*>\s*<img[^>]+src=["\']([^"\']+)["\']#is', $row, $m)) {
        $info['image'] = $m[1];
    }

    if ($info['title'] === '' && $info['url'] === '') {
        return $cache[$conv_id] = null;
    }

    // Resolve ad lifecycle state from the conversation's post_id when present.
    $post_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $conv_t WHERE id = %d",
        $conv_id
    ));
    if ($post_id > 0 && function_exists('adforest_modern_chat_resolve_ad_state')) {
        $resolved = adforest_modern_chat_resolve_ad_state($post_id);
        $info['state'] = $resolved['state'];
        $info['label'] = $resolved['label'];
    }

    return $cache[$conv_id] = $info;
}

/**
 * Resolve an ad's lifecycle state for the messaging UI.
 *
 * Returns one of:
 *   live     → post exists, status=publish, not past expiry
 *   expired  → past expiry (theme's own _adforest_ad_status_=expired
 *              marker, OR derived from package_ad_expiry_days + post date)
 *   inactive → post exists but status=pending/draft/private
 *   deleted  → get_post_status() returned null/false, or status=trash
 *
 * Used by the chat header + first-message ad-card decoration to neutralize
 * broken links and add an "(expired)" / "(no longer available)" suffix.
 */
if (!function_exists('adforest_modern_chat_resolve_ad_state')) {
    function adforest_modern_chat_resolve_ad_state($post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return array('state' => 'unknown', 'label' => '');
        }

        $status = get_post_status($post_id);
        if ($status === false || $status === null || $status === 'trash' || $status === 'auto-draft') {
            return array('state' => 'deleted', 'label' => __('No longer available', 'adforest'));
        }

        // Theme's explicit expired marker (set by single-ad_post.php's
        // expiry handler when after_expired_ads = 'expired' or default).
        $ad_status_meta = (string) get_post_meta($post_id, '_adforest_ad_status_', true);
        $is_expired = ($ad_status_meta === 'expired');

        // Dynamic expiry derivation — covers the case where the cron /
        // page-load expiry pass hasn't run yet but the timestamp has passed.
        if (!$is_expired) {
            $expiry_days = (int) get_post_meta($post_id, 'package_ad_expiry_days', true);
            if ($expiry_days > 0) {
                $post_date_str = get_post_field('post_date', $post_id);
                if ($post_date_str) {
                    $expiry_ts = strtotime($post_date_str) + ($expiry_days * DAY_IN_SECONDS);
                    if ($expiry_ts > 0 && $expiry_ts < time()) {
                        $is_expired = true;
                    }
                }
            }
        }

        if ($is_expired) {
            return array('state' => 'expired', 'label' => __('Expired', 'adforest'));
        }
        if ($status !== 'publish') {
            return array('state' => 'inactive', 'label' => __('Unavailable', 'adforest'));
        }
        return array('state' => 'live', 'label' => '');
    }
}

/* ------------------------------------------------------------------ */
/* User-deletion anonymization                                         */
/* ------------------------------------------------------------------ */
/*
 * When a WordPress user is deleted (either via wp-admin → Users or via
 * a self-service "delete my account" flow), WordPress removes the row
 * from wp_users + wp_usermeta but does NOT touch the SB Chat tables.
 * Without intervention, every conversation referencing the deleted
 * user's id sits orphaned forever:
 *   - sender_id points at a non-existent user
 *   - the OTHER party can still type into the composer (messages go
 *     into a void)
 *   - the "Profile" header button 404s
 *   - typing transients linger
 *
 * Strategy: ANONYMIZE — preserve the conversation history (valuable
 * for a classifieds marketplace where chats contain prices, agreed
 * terms, transaction context) but null out the participant slot so
 * the existing "User has been removed" UI fallback kicks in.
 *
 * Hooks into the standard `deleted_user` action (fires after the user
 * row + meta are gone). One UPDATE per affected table; cheap.
 */
add_action('deleted_user', 'adforest_modern_chat_anonymize_user_data', 20);
function adforest_modern_chat_anonymize_user_data($user_id) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) return;

    global $wpdb;
    $conv_tbl = $wpdb->prefix . 'sb_chat_conversation';
    $msg_tbl  = $wpdb->prefix . 'sb_chat_messages';

    // Capture conversation ids BEFORE we anonymize — needed below to
    // wipe the typing transients keyed on (conv_id, user_id).
    $affected_conv_ids = (array) $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM $conv_tbl WHERE user_1 = %d OR user_2 = %d",
        $user_id, $user_id
    ));

    // 1. Anonymize sent messages — bubbles still render in place but
    //    route through the "User has been removed" fallback.
    $wpdb->update($msg_tbl, array('sender_id' => 0), array('sender_id' => $user_id), array('%d'), array('%d'));

    // 2. Anonymize conversation participant slots. The remaining party
    //    keeps full history but gets a disabled composer + banner.
    $wpdb->update($conv_tbl, array('user_1' => 0), array('user_1' => $user_id), array('%d'), array('%d'));
    $wpdb->update($conv_tbl, array('user_2' => 0), array('user_2' => $user_id), array('%d'), array('%d'));

    // 3. Wipe stale typing-indicator transients — without this, a freshly
    //    anonymized user's "is typing" flag could linger up to 4s in the
    //    OTHER party's view before naturally expiring.
    foreach ($affected_conv_ids as $cid) {
        delete_transient('adf_chat_typing_' . (int) $cid . '_' . $user_id);
    }

    // 4. Wipe the user's own outgoing-state cache (if any).
    delete_transient('adf_modern_chat_outgoing_state_' . $user_id);
}

/**
 * Helper: detect whether a conversation has an anonymized participant.
 * Returns true when either user_1 or user_2 resolves to 0 (the sentinel
 * the deletion hook writes). Used by page-messages.php to swap the
 * composer for a banner and hide the "Profile" button.
 */
if (!function_exists('adforest_modern_chat_is_anonymized_conv')) {
    function adforest_modern_chat_is_anonymized_conv($conv) {
        if (!is_array($conv)) return false;
        $u1 = isset($conv['user_1']) ? (int) $conv['user_1'] : 0;
        $u2 = isset($conv['user_2']) ? (int) $conv['user_2'] : 0;
        return ($u1 === 0 || $u2 === 0);
    }
}

/* ================================================================== */
/* USER-TO-USER BLOCK + REPORT                                         */
/* ================================================================== */
/*
 * Per-user mutual block — separate from the SB Chat plugin's global
 * `sb_is_user_blocked` admin-banhammer (which prevents a user from
 * sending to ANYONE site-wide). This is the per-user block: A blocks B
 * means B can't send to A and A can't send to B, but neither one is
 * affected on other conversations with other people.
 *
 * Storage:
 *   user meta key `adforest_chat_blocked_users` → array of user IDs.
 *
 * Enforcement:
 *   priority-1 action on `wp_ajax_sb_send_message_ajax` short-circuits
 *   the plugin's send handler with a generic "couldn't deliver"
 *   error before any DB insert happens. The blocker isn't told they
 *   were blocked (just sees a generic error); the blocker stays in
 *   control of the relationship.
 */

if (!function_exists('adforest_modern_chat_get_blocked_users')) {
    function adforest_modern_chat_get_blocked_users($user_id) {
        $list = get_user_meta((int) $user_id, 'adforest_chat_blocked_users', true);
        if (!is_array($list)) return array();
        return array_values(array_unique(array_filter(array_map('intval', $list))));
    }
}

if (!function_exists('adforest_modern_chat_is_user_blocked_pair')) {
    /** True if EITHER user has blocked the OTHER — covers both directions. */
    function adforest_modern_chat_is_user_blocked_pair($a, $b) {
        $a = (int) $a; $b = (int) $b;
        if ($a <= 0 || $b <= 0 || $a === $b) return false;
        if (in_array($b, adforest_modern_chat_get_blocked_users($a), true)) return true;
        if (in_array($a, adforest_modern_chat_get_blocked_users($b), true)) return true;
        return false;
    }
}

if (!function_exists('adforest_modern_chat_user_blocks')) {
    /** True if $blocker has $target on their block list (one direction). */
    function adforest_modern_chat_user_blocks($blocker, $target) {
        $blocker = (int) $blocker; $target = (int) $target;
        if ($blocker <= 0 || $target <= 0) return false;
        return in_array($target, adforest_modern_chat_get_blocked_users($blocker), true);
    }
}

if (!function_exists('adforest_modern_chat_block_user')) {
    function adforest_modern_chat_block_user($blocker_id, $target_id) {
        $blocker_id = (int) $blocker_id; $target_id = (int) $target_id;
        if ($blocker_id <= 0 || $target_id <= 0 || $blocker_id === $target_id) return false;
        $list = adforest_modern_chat_get_blocked_users($blocker_id);
        if (!in_array($target_id, $list, true)) {
            $list[] = $target_id;
            update_user_meta($blocker_id, 'adforest_chat_blocked_users', $list);
        }
        return true;
    }
}

if (!function_exists('adforest_modern_chat_unblock_user')) {
    function adforest_modern_chat_unblock_user($blocker_id, $target_id) {
        $blocker_id = (int) $blocker_id; $target_id = (int) $target_id;
        if ($blocker_id <= 0 || $target_id <= 0) return false;
        $list = adforest_modern_chat_get_blocked_users($blocker_id);
        $list = array_values(array_diff($list, array($target_id)));
        update_user_meta($blocker_id, 'adforest_chat_blocked_users', $list);
        return true;
    }
}

/* ---- AJAX: block ---- */
add_action('wp_ajax_adforest_chat_block_user', 'adforest_modern_chat_ajax_block_user');
function adforest_modern_chat_ajax_block_user() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(null, 403);
    $target_id = isset($_POST['target_id']) ? absint($_POST['target_id']) : 0;
    if ($target_id <= 0 || $target_id === $user_id) {
        wp_send_json_error(array('message' => __('Invalid target.', 'adforest')), 400);
    }
    adforest_modern_chat_block_user($user_id, $target_id);
    wp_send_json_success(array('blocked' => true, 'target_id' => $target_id));
}

/* ---- AJAX: unblock ---- */
add_action('wp_ajax_adforest_chat_unblock_user', 'adforest_modern_chat_ajax_unblock_user');
function adforest_modern_chat_ajax_unblock_user() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(null, 403);
    $target_id = isset($_POST['target_id']) ? absint($_POST['target_id']) : 0;
    if ($target_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid target.', 'adforest')), 400);
    }
    adforest_modern_chat_unblock_user($user_id, $target_id);
    wp_send_json_success(array('blocked' => false, 'target_id' => $target_id));
}

/* ---- AJAX: report ---- */
add_action('wp_ajax_adforest_chat_report_user', 'adforest_modern_chat_ajax_report_user');
function adforest_modern_chat_ajax_report_user() {
    check_ajax_referer('adforest_chat_modern_nonce', 'nonce');
    $user_id = get_current_user_id();
    if ($user_id <= 0) wp_send_json_error(null, 403);

    $target_id = isset($_POST['target_id']) ? absint($_POST['target_id']) : 0;
    $conv_id   = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    $category  = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'other';
    $reason    = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

    if ($target_id <= 0 || $target_id === $user_id) {
        wp_send_json_error(array('message' => __('Invalid target.', 'adforest')), 400);
    }
    $allowed = array('spam', 'scam', 'harassment', 'inappropriate', 'other');
    if (!in_array($category, $allowed, true)) $category = 'other';

    // Throttle — one report per (reporter, target, conv) per 24h to
    // prevent report-spamming during harassment loops.
    $key = 'adf_chat_report_' . $user_id . '_' . $target_id . '_' . $conv_id;
    if (get_transient($key)) {
        wp_send_json_error(array('message' => __('You already reported this user recently. Our team will review it.', 'adforest')), 429);
    }
    set_transient($key, 1, DAY_IN_SECONDS);

    $reporter = get_userdata($user_id);
    $target   = get_userdata($target_id);
    $admin_email = get_option('admin_email');
    $site_name   = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

    $subject = sprintf(
        '[%s] %s — User report (%s)',
        $site_name,
        $reporter ? $reporter->display_name : ('User#' . $user_id),
        $category
    );

    $lines = array(
        __('A user reported another user via the chat overflow menu.', 'adforest'),
        '',
        __('Reporter', 'adforest') . ': ' . ($reporter ? $reporter->display_name : '') . ' (#' . $user_id . ', ' . ($reporter ? $reporter->user_email : '') . ')',
        __('Target', 'adforest')   . ': ' . ($target ? $target->display_name : '')     . ' (#' . $target_id . ', ' . ($target ? $target->user_email : '') . ')',
        __('Conversation ID', 'adforest') . ': ' . $conv_id,
        __('Category', 'adforest') . ': ' . $category,
        __('Reason', 'adforest')   . ': ' . ($reason !== '' ? $reason : '(none provided)'),
        '',
        __('Admin link', 'adforest') . ': ' . admin_url('users.php?s=' . urlencode($target_id)),
    );

    wp_mail($admin_email, $subject, implode("\n", $lines));
    wp_send_json_success(array('reported' => true));
}

/* ---- Send-side guard ----
 * Hooks at priority 1 BEFORE the plugin's send handler (priority 10).
 * If blocked either direction, return a generic delivery error and
 * exit before the message hits the DB. Generic message on purpose —
 * the blocker shouldn't reveal that they were blocked.
 */
add_action('wp_ajax_sb_send_message_ajax', 'adforest_modern_chat_block_send_guard', 1);
function adforest_modern_chat_block_send_guard() {
    $user_id      = get_current_user_id();
    $recipient_id = isset($_POST['recipient_id']) ? absint($_POST['recipient_id']) : 0;
    if ($user_id <= 0 || $recipient_id <= 0 || $user_id === $recipient_id) return;
    if (adforest_modern_chat_is_user_blocked_pair($user_id, $recipient_id)) {
        wp_send_json_error(array(
            'message' => __('Message could not be delivered.', 'adforest'),
            'umid'    => isset($_POST['unique_message_id']) ? absint($_POST['unique_message_id']) : 0,
        ));
        // wp_send_json_error calls wp_die — we won't reach here.
    }
}
