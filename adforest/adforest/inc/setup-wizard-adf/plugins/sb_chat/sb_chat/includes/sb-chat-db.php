<?php
/*
  Create Database Table
 */

class sb_chat_db_tables {

    public static function sb_chat_create_db_tables() {

        global $sb_chat_message_tbl;
        global $sb_chat_conversation_tbl;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_preferences = "CREATE TABLE {$sb_chat_message_tbl} (
          `id` int(20) NOT NULL AUTO_INCREMENT,
          `conversation_id` int(20) NOT NULL,
          `sender_id` int(20) NOT NULL,
          `receiver_id` int(11) NOT NULL,
          `message` text NOT NULL,
          `created_at` int(20) NOT NULL,
          `attachment_ids` text NOT NULL,
          `message_type` int(20) NOT NULL,
          `read_status` tinyint(1) NOT NULL DEFAULT 0,
          `deleted_by` int(20) NOT NULL,
          `deleted_msgs` int(20) NOT NULL,
          `email_sent` tinyint(1) NOT NULL DEFAULT 0,
          `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

        maybe_create_table($sb_chat_message_tbl, $sql_preferences);

        $sql_preferences = "CREATE TABLE {$sb_chat_conversation_tbl} (
          `id` int(20) NOT NULL AUTO_INCREMENT,
          `timestamp` varchar(255) NOT NULL DEFAULT '',
          `user_1` int(11) NOT NULL,
          `user_2` int(11) NOT NULL,
          `read_user_1` int(11) NOT NULL,
          `read_user_2` int(11) NOT NULL,
          `last_update` timestamp NULL DEFAULT NULL,
          `deleted_by_user_1` int(11) NOT NULL,
          `deleted_by_user_2` int(11) NOT NULL,
          `time_deleted_by_user_1` timestamp NULL DEFAULT NULL,
          `time_deleted_by_user_2` timestamp NULL DEFAULT NULL,
          `email_sent` tinyint(1) DEFAULT 0,
          `notification` varchar(20) DEFAULT '',
          `created` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

        maybe_create_table($sb_chat_conversation_tbl, $sql_preferences);
    }
}