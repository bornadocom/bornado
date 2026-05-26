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

            return array(
                'eventId'        => sprintf('listing.published.ad_post.%d.%s', $post->ID, gmdate('YmdHis', strtotime($modified_at))),
                'eventType'      => 'listing.published',
                'eventVersion'   => 1,
                'occurredAt'     => gmdate('c'),
                'sourceSystem'   => $this->get_source_system(),
                'idempotencyKey' => sha1('listing.published|' . $post->ID . '|' . $modified_at),
                'locale'         => determine_locale(),
                'payload'        => array(
                    'user'    => $this->build_user_payload(
                        $user,
                        $contact_num,
                        array(
                            'post_id' => (int) $post->ID,
                        )
                    ),
                    'listing' => array(
                        'id'        => 'wp-post-' . $post->ID,
                        'externalId'=> (string) $post->ID,
                        'title'     => (string) $title,
                        'status'    => 'publish',
                        'url'       => $canonical ? (string) $canonical : '',
                        'editUrl'   => $edit_url,
                        'manageUrl' => $manage_url,
                        'deleteUrl' => $manage_url,
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

            wp_remote_post(
                $endpoint,
                array(
                    'headers'  => $headers,
                    'body'     => $body,
                    'timeout'  => (float) apply_filters('bornado_notification_bridge_timeout', 0.5),
                    'blocking' => (bool) apply_filters('bornado_notification_bridge_blocking', false),
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
    }
}
