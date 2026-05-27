<?php
/**
 * Centralised theme notifications helpers.
 *
 * @package adforest
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('adforest_get_notifications_table_name')) {
	/**
	 * Returns the fully qualified notifications table name.
	 *
	 * @return string
	 */
	function adforest_get_notifications_table_name()
	{
		global $wpdb;

		return $wpdb->prefix . 'adforest_notifications';
	}
}

if (!function_exists('adforest_install_notifications_table')) {
	/**
	 * Creates or updates the notifications table.
	 *
	 * @param string $new_name  Optional theme name passed by WP.
	 * @param mixed  $new_theme Optional theme object passed by WP.
	 */
	function adforest_install_notifications_table($new_name = '', $new_theme = null)
	{ // phpcs:ignore
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = adforest_get_notifications_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			type VARCHAR(100) NOT NULL,
			object_id BIGINT(20) UNSIGNED DEFAULT 0,
			title VARCHAR(255) NOT NULL DEFAULT '',
			message TEXT NOT NULL,
			status_key VARCHAR(100) NOT NULL DEFAULT '',
			meta LONGTEXT NULL,
			is_read TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY type (type),
			KEY object_id (object_id),
			KEY status_key (status_key),
			KEY is_read (is_read),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta($sql);

		update_option('adforest_notifications_table_version', '1.0');
	}
}

if (!function_exists('adforest_maybe_install_notifications_table')) {
	/**
	 * Ensures the notifications table exists on init.
	 */
	function adforest_maybe_install_notifications_table()
	{
		$installed_version = get_option('adforest_notifications_table_version', '');

		if ('1.0' !== $installed_version) {
			adforest_install_notifications_table();
		}
	}
}

add_action('init', 'adforest_maybe_install_notifications_table', 5);
add_action('after_switch_theme', 'adforest_install_notifications_table', 10, 2);

if (!function_exists('adforest_add_theme_notification')) {
	/**
	 * Inserts a new notification record.
	 *
	 * @param array $args Notification parameters.
	 *
	 * @return int|false Inserted notification ID or false on failure.
	 */
	function adforest_add_theme_notification($args)
	{
		global $wpdb;

		adforest_maybe_install_notifications_table();

		$defaults = array(
			'user_id' => 0,
			'type' => '',
			'object_id' => 0,
			'title' => '',
			'message' => '',
			'status_key' => '',
			'meta' => null,
			'is_read' => 0,
			'created_at' => current_time('mysql'),
		);

		$data = wp_parse_args($args, $defaults);

		$data['user_id'] = absint($data['user_id']);
		$data['object_id'] = absint($data['object_id']);
		$data['is_read'] = (int) (bool) $data['is_read'];

		if (empty($data['user_id']) || empty($data['type']) || empty($data['message'])) {
			return false;
		}

		$table_name = adforest_get_notifications_table_name();

		$insert_data = array(
			'user_id' => $data['user_id'],
			'type' => sanitize_key($data['type']),
			'object_id' => $data['object_id'],
			'title' => sanitize_text_field($data['title']),
			'message' => wp_kses_post($data['message']),
			'status_key' => sanitize_key($data['status_key']),
			'meta' => empty($data['meta']) ? null : maybe_serialize($data['meta']),
			'is_read' => $data['is_read'],
			'created_at' => $data['created_at'],
		);

		$format = array(
			'%d',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
		);

		$inserted = $wpdb->insert($table_name, $insert_data, $format);

		return $inserted ? $wpdb->insert_id : false;
	}
}

if (!function_exists('adforest_theme_notification_exists')) {
	/**
	 * Checks if a notification already exists for the supplied constraints.
	 *
	 * @param array $args Arguments to match existing records.
	 *
	 * @return int|false Notification ID or false if not found.
	 */
	function adforest_theme_notification_exists($args)
	{
		global $wpdb;

		$defaults = array(
			'user_id' => 0,
			'type' => '',
			'object_id' => 0,
			'status_key' => '',
		);

		$args = wp_parse_args($args, $defaults);

		if (empty($args['user_id']) || empty($args['type'])) {
			return false;
		}

		$table_name = adforest_get_notifications_table_name();

		$where = array('user_id = %d', 'type = %s');
		$params = array($args['user_id'], sanitize_key($args['type']));

		if (!empty($args['object_id'])) {
			$where[] = 'object_id = %d';
			$params[] = absint($args['object_id']);
		}

		if (!empty($args['status_key'])) {
			$where[] = 'status_key = %s';
			$params[] = sanitize_key($args['status_key']);
		}

		$sql = "SELECT id FROM {$table_name} WHERE " . implode(' AND ', $where) . ' LIMIT 1';

		return $wpdb->get_var($wpdb->prepare($sql, $params)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}
}

if (!function_exists('adforest_get_user_theme_notifications')) {
	/**
	 * Retrieves notifications for a given user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 *
	 * @return array
	 */
	function adforest_get_user_theme_notifications($user_id, $args = array())
	{
		global $wpdb;

		$user_id = absint($user_id);

		if (!$user_id) {
			return array();
		}

		$defaults = array(
			'limit' => 20,
			'include_read' => true,
			'type' => '',
		);

		$args = wp_parse_args($args, $defaults);

		$limit = absint($args['limit']);
		$limit = $limit > 0 ? $limit : 20;
		$table_name = adforest_get_notifications_table_name();
		$where_clauses = array('user_id = %d');
		$params = array($user_id);

		if (!$args['include_read']) {
			$where_clauses[] = 'is_read = 0';
		}

		if (!empty($args['type'])) {
			$where_clauses[] = 'type = %s';
			$params[] = sanitize_key($args['type']);
		}

		$params[] = $limit;

		$sql = "SELECT * FROM {$table_name} WHERE " . implode(' AND ', $where_clauses) . ' ORDER BY created_at DESC LIMIT %d';

		$results = $wpdb->get_results($wpdb->prepare($sql, $params)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if (empty($results)) {
			return array();
		}

		foreach ($results as $result) {
			$result->meta = maybe_unserialize($result->meta);
		}

		return $results;
	}
}

if (!function_exists('adforest_get_translated_theme_notification')) {
	/**
	 * Rebuild a notification's title + message at DISPLAY time using the
	 * current locale.
	 *
	 * Notifications are inserted into the DB with their title/message
	 * already translated against whatever locale was active at creation
	 * time. That snapshot never changes — so a notification created while
	 * the site was English stays English even if the user later switches
	 * to French / Arabic. This helper re-renders the strings from the
	 * `event` (or `type` + `status`) recorded in the `meta` column, so
	 * the current locale's translation always wins.
	 *
	 * Falls back to the stored title/message when the event is unknown
	 * (custom notification types added by plugins, third-party callers,
	 * legacy rows without `event` in meta).
	 *
	 * @param object $notification Row returned by adforest_get_user_theme_notifications().
	 * @return array { title: string, message: string }
	 */
	function adforest_get_translated_theme_notification($notification) {
		$meta = isset($notification->meta) ? $notification->meta : array();
		if (!is_array($meta)) {
			$meta = maybe_unserialize($meta);
			if (!is_array($meta)) {
				$meta = array();
			}
		}

		$type  = isset($notification->type) ? $notification->type : '';
		$event = isset($meta['event']) ? $meta['event'] : '';
		$ad_id = isset($meta['ad_id']) ? (int) $meta['ad_id'] : 0;

		$title   = '';
		$message = '';

		if ('ad_post' === $type && $event !== '') {
			$ad_title = $ad_id ? get_the_title($ad_id) : '';
			if (!$ad_title) {
				$ad_title = __('Your ad', 'adforest');
			}

			switch ($event) {
				case 'submitted':
					$title   = __('Ad submitted', 'adforest');
					/* translators: %s: ad title */
					$message = sprintf(__('Your ad "%s" has been submitted.', 'adforest'), $ad_title);
					break;
				case 'pending':
					$title   = __('Ad under review', 'adforest');
					/* translators: %s: ad title */
					$message = sprintf(__('Your ad "%s" is awaiting approval.', 'adforest'), $ad_title);
					break;
				case 'published':
					$title   = __('Ad published', 'adforest');
					/* translators: %s: ad title */
					$message = sprintf(__('Your ad "%s" is now live.', 'adforest'), $ad_title);
					break;
				case 'expired':
					$title   = __('Ad expired', 'adforest');
					/* translators: %s: ad title */
					$message = sprintf(__('Your ad "%s" has expired.', 'adforest'), $ad_title);
					break;
				case 'featured':
					$title   = __('Ad featured', 'adforest');
					/* translators: %s: ad title */
					$message = sprintf(__('Your ad "%s" is marked featured.', 'adforest'), $ad_title);
					break;
				case 'bump':
					$title   = __('Ad bumped up', 'adforest');
					/* translators: %s: ad title */
					$message = sprintf(__('Your ad "%s" is bumped to the top.', 'adforest'), $ad_title);
					break;
				case 'bid':
					$title       = __('New bid received', 'adforest');
					$bidder_id   = isset($meta['bidder_id']) ? (int) $meta['bidder_id'] : 0;
					$bid_amount  = isset($meta['bid_amount']) ? $meta['bid_amount'] : '';
					$bidder_name = __('Someone', 'adforest');
					if ($bidder_id) {
						$bidder = get_userdata($bidder_id);
						if ($bidder && $bidder->display_name) {
							$bidder_name = $bidder->display_name;
						}
					}
					if ('' !== $bid_amount) {
						/* translators: 1: bidder name, 2: bid amount, 3: ad title */
						$message = sprintf(__('%1$s offered %2$s on "%3$s".', 'adforest'), $bidder_name, $bid_amount, $ad_title);
					} else {
						/* translators: 1: bidder name, 2: ad title */
						$message = sprintf(__('%1$s placed a bid on "%2$s".', 'adforest'), $bidder_name, $ad_title);
					}
					break;
			}
		}

		if ('package_order' === $type) {
			$order_number = isset($meta['order_number']) ? $meta['order_number'] : '';
			$status       = isset($meta['status']) ? $meta['status'] : '';
			if ($order_number !== '' && $status !== '') {
				$status_label = 'completed' === $status
					? __('completed', 'adforest')
					: __('processing', 'adforest');
				/* translators: %1$s: order number, %2$s: order status */
				$title   = sprintf(__('Package order #%1$s is %2$s', 'adforest'), $order_number, $status_label);
				$message = 'completed' === $status
					? __('Your package purchase is complete. You can now use the package benefits in your account.', 'adforest')
					: __('We are processing your package purchase. We will let you know when it is completed.', 'adforest');
			}
		}

		// Fallback to whatever was stored at creation time if we don't
		// recognize the event (third-party notification types, legacy rows).
		if ('' === $title) {
			$title = !empty($notification->title) ? $notification->title : __('New notification', 'adforest');
		}
		if ('' === $message) {
			$message = !empty($notification->message) ? $notification->message : '';
		}

		return array(
			'title'   => $title,
			'message' => $message,
		);
	}
}

if (!function_exists('adforest_mark_theme_notification_as_read')) {
	/**
	 * Marks a notification as read.
	 *
	 * @param int $notification_id Notification ID.
	 *
	 * @return bool
	 */
	function adforest_mark_theme_notification_as_read($notification_id, $user_id = 0)
	{
		global $wpdb;

		$notification_id = absint($notification_id);
		$user_id = absint($user_id);

		if (!$notification_id) {
			return false;
		}

		$table_name = adforest_get_notifications_table_name();
		$where = array('id' => $notification_id);

		if ($user_id) {
			$where['user_id'] = $user_id;
		}

		$updated = $wpdb->update(
			$table_name,
			array('is_read' => 1),
			$where,
			array('%d'),
			array_fill(0, count($where), '%d')
		);

		return false !== $updated;
	}
}

if (!function_exists('adforest_count_theme_notifications')) {
	/**
	 * Counts notifications for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Arguments.
	 *
	 * @return int
	 */
	function adforest_count_theme_notifications($user_id, $args = array())
	{
		global $wpdb;

		$user_id = absint($user_id);

		if (!$user_id) {
			return 0;
		}

		$defaults = array(
			'include_read' => false,
			'type' => '',
		);

		$args = wp_parse_args($args, $defaults);

		$table_name = adforest_get_notifications_table_name();
		$where_clauses = array('user_id = %d');
		$params = array($user_id);

		if (!$args['include_read']) {
			$where_clauses[] = 'is_read = 0';
		}

		if (!empty($args['type'])) {
			$where_clauses[] = 'type = %s';
			$params[] = sanitize_key($args['type']);
		}

		$sql = "SELECT COUNT(*) FROM {$table_name} WHERE " . implode(' AND ', $where_clauses);

		$count = $wpdb->get_var($wpdb->prepare($sql, $params)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return intval($count);
	}
}

if (!function_exists('adforest_mark_all_theme_notifications_read')) {
	/**
	 * Marks all notifications for a user as read.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int Number of updated rows.
	 */
	function adforest_mark_all_theme_notifications_read($user_id)
	{
		global $wpdb;

		$user_id = absint($user_id);

		if (!$user_id) {
			return 0;
		}

		$table_name = adforest_get_notifications_table_name();

		$updated = $wpdb->update(
			$table_name,
			array(
				'is_read' => 1,
			),
			array(
				'user_id' => $user_id,
				'is_read' => 0,
			),
			array('%d'),
			array('%d', '%d')
		);

		return false === $updated ? 0 : intval($updated);
	}
}

if (!function_exists('adforest_add_ad_post_notification')) {
	/**
	 * Adds a notification for ad post related events.
	 *
	 * @param int    $ad_id Ad post ID.
	 * @param string $event Event key.
	 * @param array  $args  Optional arguments.
	 *
	 * @return int|false
	 */
	function adforest_add_ad_post_notification($ad_id, $event, $args = array())
	{
		$ad_id = absint($ad_id);

		if (!$ad_id) {
			return false;
		}

		$author_id = (int) get_post_field('post_author', $ad_id);

		if (!$author_id) {
			return false;
		}

		$event = sanitize_key($event);
		$allowed_events = array('submitted', 'pending', 'published', 'expired', 'bid', 'featured', 'bump');

		if (!in_array($event, $allowed_events, true)) {
			return false;
		}

		$ad_title = get_the_title($ad_id);
		if (!$ad_title) {
			$ad_title = __('Your ad', 'adforest');
		}

		$permalink = get_permalink($ad_id);
		if (empty($permalink)) {
			$permalink = home_url();
		}

		$title = '';
		$message = '';
		$meta = array(
			'ad_id' => $ad_id,
			'event' => $event,
			'link' => $permalink,
		);

		switch ($event) {
			case 'submitted':
				$title = __('Ad submitted', 'adforest');
				$message = sprintf(
					/* translators: %s: ad title */
					__('Your ad "%s" has been submitted.', 'adforest'),
					$ad_title
				);
				break;
			case 'pending':
				$title = __('Ad under review', 'adforest');
				$message = sprintf(
					/* translators: %s: ad title */
					__('Your ad "%s" is awaiting approval.', 'adforest'),
					$ad_title
				);
				break;
			case 'published':
				$title = __('Ad published', 'adforest');
				$message = sprintf(
					/* translators: %s: ad title */
					__('Your ad "%s" is now live.', 'adforest'),
					$ad_title
				);
				break;
			case 'expired':
				$title = __('Ad expired', 'adforest');
				$message = sprintf(
					/* translators: %s: ad title */
					__('Your ad "%s" has expired.', 'adforest'),
					$ad_title
				);
				break;
			case 'bid':
				$title = __('New bid received', 'adforest');
				$bidder_id = isset($args['bidder_id']) ? absint($args['bidder_id']) : 0;
				$bid_amount = isset($args['amount']) ? sanitize_text_field($args['amount']) : '';

				$bidder_name = __('Someone', 'adforest');
				if ($bidder_id) {
					$bidder = get_userdata($bidder_id);
					$bidder_name = $bidder && $bidder->display_name ? $bidder->display_name : $bidder_name;
					$meta['bidder_id'] = $bidder_id;
				}

				if ('' !== $bid_amount) {
					$meta['bid_amount'] = $bid_amount;
					$message = sprintf(
						/* translators: 1: bidder name, 2: bid amount, 3: ad title */
						__('%1$s offered %2$s on "%3$s".', 'adforest'),
						$bidder_name,
						$bid_amount,
						$ad_title
					);
				} else {
					$message = sprintf(
						/* translators: 1: bidder name, 2: ad title */
						__('%1$s placed a bid on "%2$s".', 'adforest'),
						$bidder_name,
						$ad_title
					);
				}
				break;
			case 'featured':
				$title = __('Ad featured', 'adforest');
				$message = sprintf(
					/* translators: %s: ad title */
					__('Your ad "%s" is marked featured.', 'adforest'),
					$ad_title
				);
				break;
			case 'bump':
				$title = __('Ad bumped up', 'adforest');

				if (isset($args['bump_date'])) {
					$meta['bump_date'] = sanitize_text_field($args['bump_date']);
				}

				$message = sprintf(
					/* translators: %s: ad title */
					__('Your ad "%s" is bumped to the top.', 'adforest'),
					$ad_title
				);
				break;
		}

		$allow_multiple = in_array($event, array('bid', 'bump'), true);
		$status_key = sanitize_key('ad_' . $event);

		if (!$allow_multiple) {
			$existing = adforest_theme_notification_exists(
				array(
					'user_id' => $author_id,
					'type' => 'ad_post',
					'object_id' => $ad_id,
					'status_key' => $status_key,
				)
			);

			if ($existing) {
				return $existing;
			}
		} else {
			$unique_suffix = isset($args['unique']) ? sanitize_key($args['unique']) : sanitize_key(uniqid('', false));
			$status_key = sanitize_key('ad_' . $event . '_' . $unique_suffix);
		}

		return adforest_add_theme_notification(
			array(
				'user_id' => $author_id,
				'type' => 'ad_post',
				'object_id' => $ad_id,
				'title' => $title,
				'message' => $message,
				'status_key' => $status_key,
				'meta' => $meta,
			)
		);
	}
}

if (!function_exists('adforest_handle_ad_post_status_transition')) {
	/**
	 * Hooks into post status transitions to create ad notifications.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	function adforest_handle_ad_post_status_transition($new_status, $old_status, $post)
	{
		if (empty($post) || 'ad_post' !== $post->post_type) {
			return;
		}

		if ($new_status === $old_status) {
			return;
		}

		if ('pending' === $new_status) {
			adforest_add_ad_post_notification($post->ID, 'pending');
			return;
		}

		if ('publish' === $new_status) {
			$ad_state = get_post_meta($post->ID, '_adforest_ad_status_', true);
			if ('expired' === $ad_state) {
				adforest_add_ad_post_notification($post->ID, 'expired');
			} else {
				adforest_add_ad_post_notification($post->ID, 'published');
			}
			return;
		}

		if (in_array($new_status, array('draft', 'trash'), true)) {
			$ad_state = get_post_meta($post->ID, '_adforest_ad_status_', true);
			if ('expired' === $ad_state) {
				adforest_add_ad_post_notification($post->ID, 'expired');
			}
		}
	}
}

add_action('transition_post_status', 'adforest_handle_ad_post_status_transition', 20, 3);

if (!function_exists('adforest_handle_package_order_notification')) {
	/**
	 * Handles package order notifications for supported statuses.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status   Order status slug.
	 */
	function adforest_handle_package_order_notification($order_id, $status)
	{
		if (!function_exists('wc_get_order')) {
			return;
		}

		$order = wc_get_order($order_id);

		if (!$order) {
			return;
		}

		$user_id = $order->get_user_id();

		if (!$user_id) {
			return;
		}

		$contains_target_package = false;

		foreach ($order->get_items() as $item) {
			$product = $item->get_product();

			if (!$product) {
				continue;
			}

			if ('adforest_classified_pkgs' === $product->get_type()) {
				$contains_target_package = true;
				break;
			}
		}

		if (!$contains_target_package) {
			return;
		}

		$exists = adforest_theme_notification_exists(
			array(
				'user_id' => $user_id,
				'type' => 'package_order',
				'object_id' => $order_id,
				'status_key' => $status,
			)
		);

		if ($exists) {
			return;
		}

		$order_number = $order->get_order_number();
		$status_label = 'completed' === $status ? __('completed', 'adforest') : __('processing', 'adforest');

		$title = sprintf(
			/* translators: %1$s: order number, %2$s: order status */
			__('Package order #%1$s is %2$s', 'adforest'),
			$order_number,
			$status_label
		);

		if ('completed' === $status) {
			$message = __('Your package purchase is complete. You can now use the package benefits in your account.', 'adforest');
		} else {
			$message = __('We are processing your package purchase. We will let you know when it is completed.', 'adforest');
		}

		$order_link = '';

		if (method_exists($order, 'get_view_order_url')) {
			$order_link = $order->get_view_order_url();
		}

		if (empty($order_link) && function_exists('wc_get_endpoint_url') && function_exists('wc_get_page_permalink')) {
			$order_link = wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount'));
		}

		$meta = array(
			'order_id' => $order_id,
			'order_number' => $order_number,
			'status' => $status,
			'link' => $order_link,
		);

		adforest_add_theme_notification(
			array(
				'user_id' => $user_id,
				'type' => 'package_order',
				'object_id' => $order_id,
				'title' => $title,
				'message' => $message,
				'status_key' => $status,
				'meta' => $meta,
			)
		);
	}
}

if (!function_exists('adforest_package_order_notification_processing')) {
	/**
	 * Fires package notification when order enters processing.
	 *
	 * @param int $order_id Order ID.
	 */
	function adforest_package_order_notification_processing($order_id)
	{
		adforest_handle_package_order_notification($order_id, 'processing');
	}
}

if (!function_exists('adforest_package_order_notification_completed')) {
	/**
	 * Fires package notification when order is completed.
	 *
	 * @param int $order_id Order ID.
	 */
	function adforest_package_order_notification_completed($order_id)
	{
		adforest_handle_package_order_notification($order_id, 'completed');
	}
}

add_action('woocommerce_order_status_processing', 'adforest_package_order_notification_processing', 20);
add_action('woocommerce_order_status_completed', 'adforest_package_order_notification_completed', 20);
