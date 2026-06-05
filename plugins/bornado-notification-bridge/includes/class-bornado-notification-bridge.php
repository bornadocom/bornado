<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Bornado_Notification_Bridge')) {
    final class Bornado_Notification_Bridge
    {
        const VERSION = '1.0.0';

        /**
         * @var Bornado_Notification_Bridge|null
         */
        private static $instance = null;

        public static function instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct() {
            add_action('transition_post_status', array($this, 'handle_listing_transition'), 30, 3);
            add_action('user_register', array($this, 'handle_user_registered'), 20, 1);

            if (is_admin()) {
                add_action('admin_menu', array($this, 'register_admin_page'));
            }
        }

        public function register_admin_page() {
            add_management_page(
                'Bornado Notification Bridge',
                'Bornado Notifications',
                'manage_options',
                'bornado-notification-bridge',
                array($this, 'render_admin_page')
            );
        }

        public function render_admin_page() {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have permission to access this page.', 'bornado-notification-bridge'));
            }

            $flash_message = '';
            $flash_type    = 'success';

            if (
                'POST' === strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
                && isset($_POST['bornado_notification_bridge_action'])
            ) {
                check_admin_referer('bornado_notification_bridge_admin');

                $action = sanitize_key((string) wp_unslash($_POST['bornado_notification_bridge_action']));
                if ('test_service_ping' === $action) {
                    $result = $this->test_service_ping();
                    $flash_type = !empty($result['ok']) ? 'success' : 'error';
                    $flash_message = wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            $dispatch_status = get_option('bornado_notification_bridge_last_dispatch', array());
            $fallback_status = get_option('bornado_notification_bridge_last_fallback', array());
            $service_snapshot = $this->fetch_service_snapshot();
            $service_body = isset($service_snapshot['body']) && is_array($service_snapshot['body']) ? $service_snapshot['body'] : array();
            $service_mode = !empty($service_body['service']['paused']) ? 'Paused' : 'Running';
            $queue_counts = isset($service_body['queue']) && is_array($service_body['queue']) ? $service_body['queue'] : array();

            echo '<div class="wrap">';
            echo '<h1>Bornado Notification Bridge</h1>';
            echo '<p>This page only monitors the WordPress producer and the remote service health. Delivery logic stays in the notification service.</p>';

            if ('' !== $flash_message) {
                echo '<div class="notice notice-' . esc_attr('error' === $flash_type ? 'error' : 'success') . '"><p><code>' . esc_html($flash_message) . '</code></p></div>';
            }

            echo '<h2>Bridge Configuration</h2>';
            echo '<table class="widefat striped" style="max-width:900px;"><tbody>';
            echo '<tr><td>Ingest URL</td><td><code>' . esc_html($this->get_ingest_url()) . '</code></td></tr>';
            echo '<tr><td>Ops URL</td><td><code>' . esc_html($this->get_ops_url()) . '</code></td></tr>';
            echo '<tr><td>Source system</td><td><code>' . esc_html($this->get_source_system()) . '</code></td></tr>';
            echo '<tr><td>Shared secret</td><td>' . esc_html('' !== $this->get_shared_secret() ? 'Configured' : 'Missing') . '</td></tr>';
            echo '<tr><td>Ops key</td><td>' . esc_html('' !== $this->get_ops_key() ? 'Configured' : 'Missing') . '</td></tr>';
            echo '<tr><td>Remote service mode</td><td><strong>' . esc_html($service_mode) . '</strong></td></tr>';
            echo '<tr><td>Remote queue</td><td><code>pending=' . esc_html((string) ($queue_counts['pending'] ?? 0)) . ', processing=' . esc_html((string) ($queue_counts['processing'] ?? 0)) . ', failed=' . esc_html((string) ($queue_counts['failed'] ?? 0)) . '</code></td></tr>';
            echo '</tbody></table>';

            echo '<h2 style="margin-top:24px;">Connectivity</h2>';
            echo '<form method="post" style="margin-bottom:16px;">';
            wp_nonce_field('bornado_notification_bridge_admin');
            echo '<input type="hidden" name="bornado_notification_bridge_action" value="test_service_ping" />';
            submit_button('Send signed ping to service', 'primary', 'submit', false);
            echo '</form>';

            echo '<h2>Last WordPress Dispatch</h2>';
            echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:900px;overflow:auto;">' . esc_html(wp_json_encode($dispatch_status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';

            echo '<h2>Last Local Fallback</h2>';
            echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:900px;overflow:auto;">' . esc_html(wp_json_encode($fallback_status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';

            echo '<h2>Remote Service Snapshot</h2>';
            echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:900px;overflow:auto;">' . esc_html(wp_json_encode($service_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';
            echo '</div>';
        }

        /**
         * @param string  $new_status
         * @param string  $old_status
         * @param WP_Post $post
         */
        public function handle_listing_transition($new_status, $old_status, $post) {
            if (empty($post) || !($post instanceof WP_Post)) {
                return;
            }

            if ('ad_post' !== $post->post_type || 'publish' !== $new_status || 'publish' === $old_status) {
                return;
            }

            $event = $this->build_listing_published_event($post);
            if (!empty($event)) {
                $this->dispatch_event($event);
            }
        }

        /**
         * @param int $user_id
         */
        public function handle_user_registered($user_id) {
            $user = get_userdata($user_id);
            if (!$user instanceof WP_User) {
                return;
            }

            $event = $this->build_user_registered_event($user);
            $this->dispatch_event($event);
        }

        /**
         * @return array<string,mixed>
         */
        private function build_listing_published_event(WP_Post $post) {
            $author_id   = (int) $post->post_author;
            $user        = get_userdata($author_id);
            $manage_url  = $this->get_manage_ads_url();
            $edit_url    = $this->get_ad_edit_url($post->ID);
            $canonical   = get_permalink($post);
            $title       = get_the_title($post);
            $contact_num = (string) get_post_meta($post->ID, '_adforest_poster_contact', true);
            $modified_at = !empty($post->post_modified_gmt) ? (string) $post->post_modified_gmt : gmdate('Y-m-d H:i:s');

            if ('' === $contact_num && $author_id > 0) {
                $contact_num = (string) get_user_meta($author_id, '_sb_contact', true);
            }

            $user_payload = $this->build_user_payload(
                $user,
                $contact_num,
                array(
                    'post_id' => (int) $post->ID,
                )
            );

            $continue_token = $this->build_continue_token(
                $user_payload,
                array(
                    'listing_id'    => (int) $post->ID,
                    'redirect_url'  => $manage_url,
                    'listing_title' => (string) $title,
                    'display_name'  => isset($user_payload['displayName']) ? (string) $user_payload['displayName'] : '',
                )
            );

            $continue_url = $this->build_continue_url($continue_token);

            return array(
                'eventId'        => sprintf('listing.published.ad_post.%d.%s', $post->ID, gmdate('YmdHis', strtotime($modified_at))),
                'eventType'      => 'listing.published',
                'eventVersion'   => 1,
                'occurredAt'     => gmdate('c'),
                'sourceSystem'   => $this->get_source_system(),
                'idempotencyKey' => sha1('listing.published|' . $post->ID . '|' . $modified_at),
                'locale'         => determine_locale(),
                'payload'        => array(
                    'user'    => $user_payload,
                    'listing' => array(
                        'id'        => 'wp-post-' . $post->ID,
                        'externalId'=> (string) $post->ID,
                        'title'     => (string) $title,
                        'status'    => 'publish',
                        'url'       => $canonical ? (string) $canonical : '',
                        'editUrl'   => $edit_url,
                        'manageUrl' => $manage_url,
                        'deleteUrl' => $manage_url,
                        'continueUrl'   => $continue_url,
                        'continueToken' => $continue_token,
                    ),
                ),
            );
        }

        /**
         * @return array<string,mixed>
         */
        private function build_user_registered_event(WP_User $user) {
            $phone = (string) get_user_meta($user->ID, '_sb_contact', true);

            return array(
                'eventId'        => sprintf('user.registered.wp_user.%d', $user->ID),
                'eventType'      => 'user.registered',
                'eventVersion'   => 1,
                'occurredAt'     => gmdate('c'),
                'sourceSystem'   => $this->get_source_system(),
                'idempotencyKey' => sha1('user.registered|' . $user->ID),
                'locale'         => determine_locale(),
                'payload'        => array(
                    'user' => $this->build_user_payload($user, $phone),
                ),
            );
        }

        /**
         * @param WP_User|false $user
         * @return array<string,mixed>
         */
        private function build_user_payload($user, $phone, $context = array()) {
            $user_id            = $user instanceof WP_User ? (int) $user->ID : 0;
            $normalized_phone   = $this->normalize_phone_number((string) $phone, is_array($context) ? $context : array());
            $is_phone_verified  = '1' === (string) get_user_meta($user_id, '_sb_is_ph_verified', true);
            $email              = $user instanceof WP_User ? (string) $user->user_email : '';
            $display_name       = $user instanceof WP_User ? (string) $user->display_name : '';
            $profile_url        = $this->get_profile_url();
            $contacts           = array();

            if ('' !== $normalized_phone) {
                $contacts[] = array(
                    'channel'      => 'sms',
                    'address'      => $normalized_phone,
                    'verified'     => $is_phone_verified,
                    'primary'      => true,
                    'priority'     => 20,
                    'capabilities' => array(
                        'sms'           => true,
                        'transactional' => true,
                    ),
                );
                $contacts[] = array(
                    'channel'      => 'whatsapp',
                    'address'      => $normalized_phone,
                    'verified'     => $is_phone_verified,
                    'primary'      => true,
                    'priority'     => 10,
                    'capabilities' => array(
                        'whatsapp'      => 'unknown',
                        'transactional' => true,
                    ),
                );
            }

            if (is_email($email)) {
                $contacts[] = array(
                    'channel'      => 'email',
                    'address'      => strtolower($email),
                    'verified'     => true,
                    'primary'      => empty($contacts),
                    'priority'     => 30,
                    'capabilities' => array(
                        'email'         => true,
                        'transactional' => true,
                    ),
                );
            }

            return array(
                'id'                  => 'wp-user-' . $user_id,
                'externalId'          => (string) $user_id,
                'displayName'         => $display_name,
                'email'               => $email,
                'phone'               => $normalized_phone,
                'phoneVerified'       => $is_phone_verified,
                'profileUrl'          => $profile_url,
                'channelCapabilities' => array(
                    'whatsapp' => null,
                    'sms'      => '' !== $normalized_phone,
                    'email'    => is_email($email),
                ),
                'contacts'            => $contacts,
            );
        }

        /**
         * @param array<string,mixed> $event
         */
        private function dispatch_event($event) {
            $endpoint = $this->get_ingest_url();
            if ('' === $endpoint) {
                $this->enqueue_event_locally($event);
                return;
            }

            $body    = wp_json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (false === $body || '' === $body) {
                return;
            }

            $secret  = $this->get_shared_secret();
            $headers = array(
                'Content-Type' => 'application/json; charset=utf-8',
            );

            if ('' !== $secret) {
                $headers['X-Bornado-Signature'] = hash_hmac('sha256', $body, $secret);
            }

            $response = wp_remote_post(
                $endpoint,
                array(
                    'headers'  => $headers,
                    'body'     => $body,
                    'timeout'  => (float) apply_filters('bornado_notification_bridge_timeout', 5.0),
                    'blocking' => (bool) apply_filters('bornado_notification_bridge_blocking', true),
                )
            );

            if (is_wp_error($response)) {
                $this->record_dispatch_snapshot(
                    'fallback',
                    array(
                        'eventId' => isset($event['eventId']) ? (string) $event['eventId'] : '',
                        'reason'  => 'wp_remote_post_failed',
                        'error'   => $response->get_error_message(),
                    )
                );
                $this->enqueue_event_locally(
                    $event,
                    array(
                        'reason' => 'wp_remote_post_failed',
                        'error'  => $response->get_error_message(),
                    )
                );
                return;
            }

            $statusCode = (int) wp_remote_retrieve_response_code($response);
            if ($statusCode < 200 || $statusCode >= 300) {
                $responseBody = wp_remote_retrieve_body($response);
                $decodedBody  = json_decode((string) $responseBody, true);

                if (
                    503 === $statusCode
                    && is_array($decodedBody)
                    && 'service_paused' === (string) ($decodedBody['code'] ?? '')
                ) {
                    $this->record_dispatch_snapshot(
                        'service_paused',
                        array(
                            'eventId'     => isset($event['eventId']) ? (string) $event['eventId'] : '',
                            'status_code' => $statusCode,
                            'body'        => $decodedBody,
                        )
                    );

                    return;
                }

                $this->record_dispatch_snapshot(
                    'fallback',
                    array(
                        'eventId'     => isset($event['eventId']) ? (string) $event['eventId'] : '',
                        'reason'      => 'wp_remote_post_rejected',
                        'status_code' => $statusCode,
                    )
                );
                $this->enqueue_event_locally(
                    $event,
                    array(
                        'reason'      => 'wp_remote_post_rejected',
                        'status_code' => $statusCode,
                        'body'        => $responseBody,
                    )
                );

                return;
            }

            $this->record_dispatch_snapshot(
                'remote_accepted',
                array(
                    'eventId'     => isset($event['eventId']) ? (string) $event['eventId'] : '',
                    'status_code' => $statusCode,
                    'ingest_url'  => $endpoint,
                )
            );
        }

        /**
         * @param array<string,mixed> $event
         * @param array<string,mixed> $context
         */
        private function enqueue_event_locally($event, $context = array()) {
            $bootstrap = trailingslashit(ABSPATH) . 'Services/bornado-notification-platform/bootstrap.php';
            $config    = trailingslashit(ABSPATH) . 'Services/bornado-notification-platform/config/notification-platform.php';

            if (!file_exists($bootstrap) || !file_exists($config)) {
                return;
            }

            require_once $bootstrap;

            if (
                !class_exists('Bornado\\NotificationPlatform\\Contracts\\EventCatalog')
                || !class_exists('Bornado\\NotificationPlatform\\Infrastructure\\FileEventQueue')
                || !class_exists('Bornado\\NotificationPlatform\\Infrastructure\\FileDeliveryLog')
            ) {
                return;
            }

            $platformConfig = require $config;
            if (!is_array($platformConfig)) {
                return;
            }

            if (
                class_exists('Bornado\\NotificationPlatform\\Infrastructure\\ServiceOperations')
            ) {
                $service_ops = new \Bornado\NotificationPlatform\Infrastructure\ServiceOperations($platformConfig);
                if ($service_ops->isServicePaused()) {
                    $this->record_dispatch_snapshot(
                        'service_paused',
                        array(
                            'eventId' => isset($event['eventId']) ? (string) $event['eventId'] : '',
                            'reason'  => 'local_fallback_blocked_while_paused',
                        )
                    );

                    return;
                }
            }

            $errors = \Bornado\NotificationPlatform\Contracts\EventCatalog::validate($event);
            if (!empty($errors)) {
                return;
            }

            $queue = new \Bornado\NotificationPlatform\Infrastructure\FileEventQueue($platformConfig['queue']);
            $log   = new \Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog(
                $platformConfig['logging']['delivery_log'],
                $platformConfig['logging']['state_dir']
            );

            $queuePath = $queue->enqueue($event);
            $log->markEvent(
                $event,
                'queued',
                array_merge(
                    array(
                        'queuePath' => $queuePath,
                        'ingestion' => 'local_fallback',
                    ),
                    is_array($context) ? $context : array()
                )
            );

            $this->record_dispatch_snapshot(
                'local_fallback',
                array_merge(
                    array(
                        'eventId'   => isset($event['eventId']) ? (string) $event['eventId'] : '',
                        'queuePath' => $queuePath,
                    ),
                    is_array($context) ? $context : array()
                )
            );
        }

        private function get_ingest_url() {
            $default = defined('BORNADO_NOTIFICATION_INGEST_URL') ? (string) BORNADO_NOTIFICATION_INGEST_URL : '';
            $url     = apply_filters('bornado_notification_bridge_ingest_url', $default);

            return esc_url_raw((string) $url);
        }

        private function get_shared_secret() {
            $default = defined('BORNADO_NOTIFICATION_SHARED_SECRET') ? (string) BORNADO_NOTIFICATION_SHARED_SECRET : '';
            return (string) apply_filters('bornado_notification_bridge_shared_secret', $default);
        }

        private function get_source_system() {
            $default = defined('BORNADO_NOTIFICATION_SOURCE_SYSTEM') ? (string) BORNADO_NOTIFICATION_SOURCE_SYSTEM : 'bornado-wordpress';
            return (string) apply_filters('bornado_notification_bridge_source_system', $default);
        }

        private function get_ops_url() {
            $default = defined('BORNADO_NOTIFICATION_OPS_URL') ? (string) BORNADO_NOTIFICATION_OPS_URL : '';
            return esc_url_raw((string) $default);
        }

        private function get_ops_key() {
            $default = defined('BORNADO_NOTIFICATION_OPS_KEY') ? (string) BORNADO_NOTIFICATION_OPS_KEY : $this->get_shared_secret();
            return (string) $default;
        }

        private function get_profile_url() {
            global $adforest_theme;

            $page_id = isset($adforest_theme['sb_profile_page']) ? apply_filters('adforest_language_page_id', $adforest_theme['sb_profile_page']) : 0;
            if ($page_id) {
                $url = get_permalink($page_id);
                if ($url) {
                    return (string) $url;
                }
            }

            return home_url('/profile/');
        }

        private function get_manage_ads_url() {
            $profile_url = $this->get_profile_url();
            return add_query_arg(
                array(
                    'page_type' => 'my_ads',
                ),
                $profile_url
            );
        }

        private function build_continue_url($token) {
            if ('' === trim((string) $token)) {
                return '';
            }

            return add_query_arg(
                array(
                    't' => $token,
                ),
                home_url('/notification-continue.php')
            );
        }

        /**
         * @param array<string,mixed> $user_payload
         * @param array<string,mixed> $context
         */
        private function build_continue_token($user_payload, $context = array()) {
            if (!is_array($user_payload)) {
                return '';
            }

            $phone = trim((string) ($user_payload['phone'] ?? ''));
            $secret = $this->get_shared_secret();
            if ('' === $phone || '' === trim($secret)) {
                return '';
            }

            $ttl = (int) apply_filters('bornado_notification_bridge_continue_token_ttl', 72 * HOUR_IN_SECONDS);
            if ($ttl <= 0) {
                $ttl = 72 * HOUR_IN_SECONDS;
            }

            $payload = array(
                'purpose'      => 'listing_manage_continue',
                'flow_source'  => 'notification',
                'phone'        => $phone,
                'redirect_url' => is_array($context) ? (string) ($context['redirect_url'] ?? '') : '',
                'listing_id'   => is_array($context) ? (int) ($context['listing_id'] ?? 0) : 0,
                'user_id'      => isset($user_payload['externalId']) ? (int) $user_payload['externalId'] : 0,
                'display_name' => is_array($context) ? (string) ($context['display_name'] ?? '') : '',
                'exp'          => time() + $ttl,
            );

            $encoded_payload = $this->base64_url_encode(wp_json_encode($payload));
            if ('' === $encoded_payload) {
                return '';
            }

            $signature = hash_hmac('sha256', $encoded_payload, $secret);

            return $encoded_payload . '.' . $signature;
        }

        private function base64_url_encode($value) {
            $value = (string) $value;
            if ('' === $value) {
                return '';
            }

            return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        }

        private function get_ad_edit_url($post_id) {
            global $adforest_theme;

            $page_id = isset($adforest_theme['sb_post_ad_page']) ? apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page']) : 0;
            if ($page_id) {
                $url = get_permalink($page_id);
                if ($url && function_exists('adforest_set_url_param')) {
                    return (string) adforest_set_url_param($url, 'id', $post_id);
                }

                if ($url) {
                    return add_query_arg('id', $post_id, $url);
                }
            }

            return '';
        }

        private function normalize_phone_number($phone_number, $context = array()) {
            $phone_number = trim((string) $phone_number);
            $context      = is_array($context) ? $context : array();
            $post_id      = isset($context['post_id']) ? (int) $context['post_id'] : 0;

            if ('' === $phone_number) {
                return '';
            }

            if ($post_id > 0 && class_exists('Bornado_Country_Phone_Service')) {
                $payload = Bornado_Country_Phone_Service::get_phone_payload_for_post($post_id, $phone_number);
                if (!empty($payload['is_valid']) && !empty($payload['normalized_phone'])) {
                    return (string) $payload['normalized_phone'];
                }
            }

            if (function_exists('bornado_normalize_phone_with_dial_code')) {
                $normalized_phone = bornado_normalize_phone_with_dial_code($phone_number);
                if ('' !== $normalized_phone) {
                    return (string) $normalized_phone;
                }
            }

            $phone_number = trim((string) $phone_number);
            $phone_number = preg_replace('/[^\d+]/', '', $phone_number);

            if ('' === $phone_number) {
                return '';
            }

            if (0 === strpos($phone_number, '00')) {
                $phone_number = '+' . substr($phone_number, 2);
            } elseif ('+' !== substr($phone_number, 0, 1)) {
                $phone_number = '+' . ltrim($phone_number, '+');
            }

            $phone_number = '+' . preg_replace('/[^\d]/', '', $phone_number);

            return $phone_number;
        }

        /**
         * @param array<string,mixed> $context
         */
        private function record_dispatch_snapshot($status, $context = array()) {
            $payload = array(
                'status'     => (string) $status,
                'recordedAt' => gmdate('c'),
                'context'    => is_array($context) ? $context : array(),
            );

            update_option('bornado_notification_bridge_last_dispatch', $payload, false);

            if ('local_fallback' === $status || 'fallback' === $status) {
                update_option('bornado_notification_bridge_last_fallback', $payload, false);
            }
        }

        /**
         * @return array<string,mixed>
         */
        private function test_service_ping() {
            $ops_url = $this->get_ops_url();
            $secret  = $this->get_shared_secret();

            if ('' === $ops_url || '' === $secret) {
                return array(
                    'ok'      => false,
                    'message' => 'Ops URL or shared secret is missing.',
                );
            }

            $payload = array(
                'action'       => 'ping',
                'sourceSystem' => $this->get_source_system(),
                'requestedAt'  => gmdate('c'),
            );
            $body = wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (false === $body || '' === $body) {
                return array(
                    'ok'      => false,
                    'message' => 'Unable to encode ping payload.',
                );
            }

            $response = wp_remote_post(
                add_query_arg('format', 'json', $ops_url),
                array(
                    'headers' => array(
                        'Content-Type'        => 'application/json; charset=utf-8',
                        'X-Bornado-Signature' => hash_hmac('sha256', $body, $secret),
                    ),
                    'body'    => $body,
                    'timeout' => 10,
                )
            );

            if (is_wp_error($response)) {
                return array(
                    'ok'      => false,
                    'message' => $response->get_error_message(),
                );
            }

            return array(
                'ok'         => wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300,
                'statusCode' => (int) wp_remote_retrieve_response_code($response),
                'body'       => json_decode((string) wp_remote_retrieve_body($response), true),
            );
        }

        /**
         * @return array<string,mixed>
         */
        private function fetch_service_snapshot() {
            $ops_url = $this->get_ops_url();
            $ops_key = $this->get_ops_key();

            if ('' === $ops_url || '' === $ops_key) {
                return array(
                    'ok'      => false,
                    'message' => 'Ops URL or key is missing.',
                );
            }

            $response = wp_remote_get(
                add_query_arg(
                    array(
                        'format' => 'json',
                        'key'    => $ops_key,
                        'limit'  => 5,
                    ),
                    $ops_url
                ),
                array(
                    'timeout' => 10,
                )
            );

            if (is_wp_error($response)) {
                return array(
                    'ok'      => false,
                    'message' => $response->get_error_message(),
                );
            }

            $body = json_decode((string) wp_remote_retrieve_body($response), true);

            return array(
                'ok'         => wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300,
                'statusCode' => (int) wp_remote_retrieve_response_code($response),
                'body'       => is_array($body) ? $body : array('raw' => wp_remote_retrieve_body($response)),
            );
        }
    }
}
