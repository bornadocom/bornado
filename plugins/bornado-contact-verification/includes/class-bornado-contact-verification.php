<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Bornado_Contact_Verification')) {
    final class Bornado_Contact_Verification
    {
        const VERSION = '1.0.0';
        const EMAIL_STATUS_KEY = 'bornado_email_verification_status';
        const EMAIL_VERIFIED_AT_KEY = 'bornado_email_verified_at';
        const EMAIL_ADDRESS_KEY = 'bornado_email_verification_address';
        const EMAIL_TOKEN_KEY = 'bornado_email_verification_token';
        const EMAIL_EXPIRES_AT_KEY = 'bornado_email_verification_expires_at';
        const EMAIL_SENT_AT_KEY = 'bornado_email_verification_sent_at';

        const WHATSAPP_STATUS_KEY = 'bornado_whatsapp_verification_status';
        const WHATSAPP_VERIFIED_AT_KEY = 'bornado_whatsapp_verified_at';
        const WHATSAPP_VERIFIED_PHONE_KEY = 'bornado_whatsapp_verified_phone';
        const WHATSAPP_PENDING_PHONE_KEY = 'bornado_whatsapp_verification_phone';
        const WHATSAPP_CODE_HASH_KEY = 'bornado_whatsapp_verification_code_hash';
        const WHATSAPP_EXPIRES_AT_KEY = 'bornado_whatsapp_verification_expires_at';
        const WHATSAPP_ATTEMPTS_KEY = 'bornado_whatsapp_verification_attempts';
        const WHATSAPP_REQUESTED_AT_KEY = 'bornado_whatsapp_verification_requested_at';

        /**
         * @var Bornado_Contact_Verification|null
         */
        private static $instance = null;

        /**
         * @var int
         */
        private $email_ttl = 86400;

        /**
         * @var int
         */
        private $whatsapp_ttl = 900;

        /**
         * @var int
         */
        private $resend_cooldown = 60;

        /**
         * @var int
         */
        private $max_whatsapp_attempts = 5;

        public static function instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct()
        {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 220);
            add_action('template_redirect', array($this, 'handle_email_verification_request'), 5);

            add_action('wp_ajax_bornado_contact_verification_send_email', array($this, 'ajax_send_email_verification'));
            add_action('wp_ajax_bornado_contact_verification_send_whatsapp', array($this, 'ajax_send_whatsapp_verification'));
            add_action('wp_ajax_bornado_contact_verification_verify_whatsapp', array($this, 'ajax_verify_whatsapp_code'));

            add_action('profile_update', array($this, 'handle_profile_update'), 20, 2);
            add_action('updated_user_meta', array($this, 'handle_phone_meta_change'), 20, 4);
            add_action('added_user_meta', array($this, 'handle_phone_meta_change'), 20, 4);
        }

        public function enqueue_assets()
        {
            if (is_admin() || !is_user_logged_in() || !$this->is_profile_page()) {
                return;
            }

            $plugin_url = plugin_dir_url(BORNADO_CONTACT_VERIFICATION_FILE);
            $plugin_dir = plugin_dir_path(BORNADO_CONTACT_VERIFICATION_FILE);
            $css_path   = $plugin_dir . 'assets/css/bornado-contact-verification.css';
            $js_path    = $plugin_dir . 'assets/js/bornado-contact-verification.js';

            wp_enqueue_style(
                'bornado-contact-verification',
                $plugin_url . 'assets/css/bornado-contact-verification.css',
                array(),
                file_exists($css_path) ? (string) filemtime($css_path) : self::VERSION
            );

            wp_enqueue_script(
                'bornado-contact-verification',
                $plugin_url . 'assets/js/bornado-contact-verification.js',
                array('jquery'),
                file_exists($js_path) ? (string) filemtime($js_path) : self::VERSION,
                true
            );

            wp_localize_script(
                'bornado-contact-verification',
                'bornadoContactVerification',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonces'  => array(
                        'email'    => wp_create_nonce('bornado_contact_verification_send_email'),
                        'whatsapp' => wp_create_nonce('bornado_contact_verification_send_whatsapp'),
                        'verify'   => wp_create_nonce('bornado_contact_verification_verify_whatsapp'),
                    ),
                    'messages' => array(
                        'sending'          => 'در حال ارسال...',
                        'verifying'        => 'در حال بررسی...',
                        'unknownError'     => 'خطایی رخ داد. لطفا دوباره تلاش کنید.',
                        'whatsappVerified' => 'واتس اپ شما با موفقیت تایید شد.',
                    ),
                )
            );
        }

        /**
         * @param int $user_id
         * @return array<string,mixed>
         */
        public function get_email_status_data($user_id = 0)
        {
            $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();
            $user    = $user_id > 0 ? get_userdata($user_id) : false;
            $email   = $user instanceof WP_User ? strtolower(trim((string) $user->user_email)) : '';
            $status  = (string) get_user_meta($user_id, self::EMAIL_STATUS_KEY, true);
            $address = strtolower(trim((string) get_user_meta($user_id, self::EMAIL_ADDRESS_KEY, true)));
            $expires = (int) get_user_meta($user_id, self::EMAIL_EXPIRES_AT_KEY, true);

            if ('' === $email) {
                return $this->build_status_data('', 'missing', 'ثبت نشده', false, false);
            }

            if ('verified' === $status && '' !== $address && $address === $email) {
                return $this->build_status_data($email, 'verified', 'تأیید شده', false, true);
            }

            if (
                'verified' === (string) get_user_meta($user_id, 'sb_user_email_verification_status', true)
                && 'verified' !== $status
            ) {
                return $this->build_status_data($email, 'verified', 'تأیید شده', false, true);
            }

            if ('pending' === $status && '' !== $address && $address === $email && $expires > time()) {
                return $this->build_status_data($email, 'pending', 'در انتظار تأیید', true, false);
            }

            return $this->build_status_data($email, 'unverified', 'تأیید نشده', true, false);
        }

        /**
         * @param int $user_id
         * @return array<string,mixed>
         */
        public function get_whatsapp_status_data($user_id = 0)
        {
            $user_id        = $user_id > 0 ? (int) $user_id : get_current_user_id();
            $phone          = $this->normalize_phone((string) get_user_meta($user_id, '_sb_contact', true));
            $status         = (string) get_user_meta($user_id, self::WHATSAPP_STATUS_KEY, true);
            $verified_phone = $this->normalize_phone((string) get_user_meta($user_id, self::WHATSAPP_VERIFIED_PHONE_KEY, true));
            $pending_phone  = $this->normalize_phone((string) get_user_meta($user_id, self::WHATSAPP_PENDING_PHONE_KEY, true));
            $expires        = (int) get_user_meta($user_id, self::WHATSAPP_EXPIRES_AT_KEY, true);

            if ('' === $phone) {
                return $this->build_status_data('', 'missing', 'ثبت نشده', false, false);
            }

            if ('verified' === $status && '' !== $verified_phone && $verified_phone === $phone) {
                return $this->build_status_data($phone, 'verified', 'تأیید شده', false, true);
            }

            if ('pending' === $status && '' !== $pending_phone && $pending_phone === $phone && $expires > time()) {
                return $this->build_status_data($phone, 'pending', 'در انتظار تأیید', true, false);
            }

            return $this->build_status_data($phone, 'unverified', 'تأیید نشده', true, false);
        }

        /**
         * @return array<string,string>
         */
        public function get_notice_from_request()
        {
            $code = isset($_GET['bornado_contact_notice']) ? sanitize_key(wp_unslash($_GET['bornado_contact_notice'])) : '';

            $notice_map = array(
                'email_verified' => array(
                    'type'    => 'success',
                    'message' => 'ایمیل شما با موفقیت تایید شد.',
                ),
                'email_invalid' => array(
                    'type'    => 'error',
                    'message' => 'لینک تایید ایمیل معتبر نیست.',
                ),
                'email_expired' => array(
                    'type'    => 'warning',
                    'message' => 'زمان استفاده از لینک تایید ایمیل به پایان رسیده است.',
                ),
                'email_missing' => array(
                    'type'    => 'warning',
                    'message' => 'آدرس ایمیل معتبری برای تایید پیدا نشد.',
                ),
            );

            return isset($notice_map[$code]) ? $notice_map[$code] : array();
        }

        public function ajax_send_email_verification()
        {
            $this->verify_ajax_nonce('bornado_contact_verification_send_email', 'security');
            $this->guard_demo_mode();

            $user_id = get_current_user_id();
            if ($user_id <= 0) {
                wp_send_json_error(array('message' => 'برای این عملیات باید وارد حساب خود شوید.'), 403);
            }

            $user  = get_userdata($user_id);
            $email = $user instanceof WP_User ? strtolower(trim((string) $user->user_email)) : '';
            if (!is_email($email)) {
                wp_send_json_error(array('message' => 'ابتدا یک ایمیل معتبر در پروفایل خود ثبت کنید.'), 422);
            }

            $status = $this->get_email_status_data($user_id);
            if (!empty($status['is_verified'])) {
                wp_send_json_success(array('message' => 'ایمیل شما قبلا تایید شده است.'));
            }

            $last_sent = (int) get_user_meta($user_id, self::EMAIL_SENT_AT_KEY, true);
            if ($last_sent > 0 && (time() - $last_sent) < $this->resend_cooldown) {
                wp_send_json_error(array('message' => 'لطفا کمی بعد دوباره تلاش کنید.'), 429);
            }

            try {
                $token = bin2hex(random_bytes(24));
            } catch (Exception $exception) {
                $token = wp_generate_password(48, false, false);
            }

            $expires_at = time() + $this->email_ttl;
            update_user_meta($user_id, self::EMAIL_STATUS_KEY, 'pending');
            update_user_meta($user_id, self::EMAIL_ADDRESS_KEY, $email);
            update_user_meta($user_id, self::EMAIL_TOKEN_KEY, $token);
            update_user_meta($user_id, self::EMAIL_EXPIRES_AT_KEY, $expires_at);
            update_user_meta($user_id, self::EMAIL_SENT_AT_KEY, time());
            update_user_meta($user_id, 'sb_user_email_verification_status', 'pending');
            update_user_meta($user_id, 'sb_email_verification_token', $token);

            $result = $this->send_email_message(
                $email,
                $this->build_email_subject($user),
                $this->build_email_body($user, $this->build_email_verification_link($user_id, $token, $expires_at))
            );

            if (is_wp_error($result)) {
                $this->reset_email_verification($user_id);
                wp_send_json_error(array('message' => $result->get_error_message()), 500);
            }

            wp_send_json_success(
                array(
                    'message' => 'لینک تایید ایمیل برای شما ارسال شد.',
                    'status'  => 'pending',
                )
            );
        }

        public function ajax_send_whatsapp_verification()
        {
            $this->verify_ajax_nonce('bornado_contact_verification_send_whatsapp', 'security');
            $this->guard_demo_mode();

            $user_id = get_current_user_id();
            if ($user_id <= 0) {
                wp_send_json_error(array('message' => 'برای این عملیات باید وارد حساب خود شوید.'), 403);
            }

            $phone = $this->normalize_phone((string) get_user_meta($user_id, '_sb_contact', true));
            if (!$this->is_valid_phone_number($phone)) {
                wp_send_json_error(array('message' => 'ابتدا یک شماره تماس معتبر در پروفایل خود ثبت کنید.'), 422);
            }

            $status = $this->get_whatsapp_status_data($user_id);
            if (!empty($status['is_verified'])) {
                wp_send_json_success(array('message' => 'واتس اپ این شماره قبلا تایید شده است.'));
            }

            $last_sent = (int) get_user_meta($user_id, self::WHATSAPP_REQUESTED_AT_KEY, true);
            if ($last_sent > 0 && (time() - $last_sent) < $this->resend_cooldown) {
                wp_send_json_error(array('message' => 'لطفا کمی بعد دوباره تلاش کنید.'), 429);
            }

            $code       = (string) wp_rand(100000, 999999);
            $expires_at = time() + $this->whatsapp_ttl;
            $event      = $this->build_whatsapp_verification_event($user_id, $phone, $code, $expires_at);
            $dispatch   = $this->publish_notification_event($event);

            if (is_wp_error($dispatch)) {
                wp_send_json_error(array('message' => $dispatch->get_error_message()), 500);
            }

            update_user_meta($user_id, self::WHATSAPP_STATUS_KEY, 'pending');
            update_user_meta($user_id, self::WHATSAPP_PENDING_PHONE_KEY, $phone);
            update_user_meta($user_id, self::WHATSAPP_CODE_HASH_KEY, wp_hash_password($code));
            update_user_meta($user_id, self::WHATSAPP_EXPIRES_AT_KEY, $expires_at);
            update_user_meta($user_id, self::WHATSAPP_ATTEMPTS_KEY, 0);
            update_user_meta($user_id, self::WHATSAPP_REQUESTED_AT_KEY, time());

            wp_send_json_success(
                array(
                    'message'    => 'کد تایید واتس اپ ارسال شد.',
                    'status'     => 'pending',
                    'expires_at' => $expires_at,
                )
            );
        }

        public function ajax_verify_whatsapp_code()
        {
            $this->verify_ajax_nonce('bornado_contact_verification_verify_whatsapp', 'security');
            $this->guard_demo_mode();

            $user_id = get_current_user_id();
            if ($user_id <= 0) {
                wp_send_json_error(array('message' => 'برای این عملیات باید وارد حساب خود شوید.'), 403);
            }

            $code = isset($_POST['code']) ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['code'])) : '';
            if (strlen($code) !== 6) {
                wp_send_json_error(array('message' => 'کد تایید باید ۶ رقم باشد.'), 422);
            }

            $phone        = $this->normalize_phone((string) get_user_meta($user_id, '_sb_contact', true));
            $pending_phone = $this->normalize_phone((string) get_user_meta($user_id, self::WHATSAPP_PENDING_PHONE_KEY, true));
            $hash         = (string) get_user_meta($user_id, self::WHATSAPP_CODE_HASH_KEY, true);
            $expires_at   = (int) get_user_meta($user_id, self::WHATSAPP_EXPIRES_AT_KEY, true);
            $attempts     = (int) get_user_meta($user_id, self::WHATSAPP_ATTEMPTS_KEY, true);

            if ('' === $phone || '' === $pending_phone || $phone !== $pending_phone || '' === $hash) {
                wp_send_json_error(array('message' => 'ابتدا درخواست ارسال کد واتس اپ را ثبت کنید.'), 409);
            }

            if ($expires_at <= time()) {
                $this->reset_whatsapp_verification($user_id);
                wp_send_json_error(array('message' => 'زمان این کد به پایان رسیده است. دوباره کد بگیرید.'), 410);
            }

            if ($attempts >= $this->max_whatsapp_attempts) {
                $this->reset_whatsapp_verification($user_id);
                wp_send_json_error(array('message' => 'تعداد تلاش‌های مجاز تمام شد. دوباره کد بگیرید.'), 429);
            }

            if (!wp_check_password($code, $hash)) {
                update_user_meta($user_id, self::WHATSAPP_ATTEMPTS_KEY, $attempts + 1);
                wp_send_json_error(array('message' => 'کد تایید واردشده صحیح نیست.'), 422);
            }

            update_user_meta($user_id, self::WHATSAPP_STATUS_KEY, 'verified');
            update_user_meta($user_id, self::WHATSAPP_VERIFIED_AT_KEY, time());
            update_user_meta($user_id, self::WHATSAPP_VERIFIED_PHONE_KEY, $phone);
            delete_user_meta($user_id, self::WHATSAPP_CODE_HASH_KEY);
            delete_user_meta($user_id, self::WHATSAPP_EXPIRES_AT_KEY);
            delete_user_meta($user_id, self::WHATSAPP_ATTEMPTS_KEY);
            delete_user_meta($user_id, self::WHATSAPP_REQUESTED_AT_KEY);

            wp_send_json_success(
                array(
                    'message' => 'واتس اپ شما با موفقیت تایید شد.',
                    'status'  => 'verified',
                )
            );
        }

        public function handle_email_verification_request()
        {
            $action = isset($_GET['bornado_contact_verify']) ? sanitize_key(wp_unslash($_GET['bornado_contact_verify'])) : '';
            if ('email' !== $action) {
                return;
            }

            $user_id = isset($_GET['bcv_uid']) ? absint(wp_unslash($_GET['bcv_uid'])) : 0;
            $token   = isset($_GET['bcv_token']) ? sanitize_text_field(wp_unslash($_GET['bcv_token'])) : '';

            if ($user_id <= 0 || '' === $token) {
                $this->redirect_with_notice('email_invalid');
            }

            $user          = get_userdata($user_id);
            $email         = $user instanceof WP_User ? strtolower(trim((string) $user->user_email)) : '';
            $stored_token  = (string) get_user_meta($user_id, self::EMAIL_TOKEN_KEY, true);
            $stored_email  = strtolower(trim((string) get_user_meta($user_id, self::EMAIL_ADDRESS_KEY, true)));
            $stored_expiry = (int) get_user_meta($user_id, self::EMAIL_EXPIRES_AT_KEY, true);

            if (!is_email($email) || '' === $stored_token || !hash_equals($stored_token, $token)) {
                $this->redirect_with_notice('email_invalid');
            }

            if ($stored_expiry <= time()) {
                $this->reset_email_verification($user_id);
                $this->redirect_with_notice('email_expired');
            }

            if ('' !== $stored_email && $stored_email !== $email) {
                $this->reset_email_verification($user_id);
                $this->redirect_with_notice('email_missing');
            }

            update_user_meta($user_id, self::EMAIL_STATUS_KEY, 'verified');
            update_user_meta($user_id, self::EMAIL_VERIFIED_AT_KEY, time());
            update_user_meta($user_id, self::EMAIL_ADDRESS_KEY, $email);
            update_user_meta($user_id, 'sb_user_email_verification_status', 'verified');
            update_user_meta($user_id, 'sb_email_verification_token', '');
            delete_user_meta($user_id, self::EMAIL_TOKEN_KEY);
            delete_user_meta($user_id, self::EMAIL_EXPIRES_AT_KEY);

            $this->redirect_with_notice('email_verified');
        }

        /**
         * @param int      $user_id
         * @param WP_User  $old_user_data
         */
        public function handle_profile_update($user_id, $old_user_data)
        {
            $user_id = (int) $user_id;
            if ($user_id <= 0 || !($old_user_data instanceof WP_User)) {
                return;
            }

            $new_user  = get_userdata($user_id);
            $old_email = strtolower(trim((string) $old_user_data->user_email));
            $new_email = $new_user instanceof WP_User ? strtolower(trim((string) $new_user->user_email)) : '';

            if ($old_email !== $new_email) {
                $this->reset_email_verification($user_id);
            }
        }

        /**
         * @param mixed  $meta_id
         * @param mixed  $object_id
         * @param string $meta_key
         * @param mixed  $meta_value
         */
        public function handle_phone_meta_change($meta_id, $object_id, $meta_key, $meta_value)
        {
            if ('_sb_contact' !== (string) $meta_key) {
                return;
            }

            $user_id        = (int) $object_id;
            $current_phone  = $this->normalize_phone((string) $meta_value);
            $verified_phone = $this->normalize_phone((string) get_user_meta($user_id, self::WHATSAPP_VERIFIED_PHONE_KEY, true));
            $pending_phone  = $this->normalize_phone((string) get_user_meta($user_id, self::WHATSAPP_PENDING_PHONE_KEY, true));

            if (
                ('' !== $verified_phone && $verified_phone !== $current_phone)
                || ('' !== $pending_phone && $pending_phone !== $current_phone)
            ) {
                $this->reset_whatsapp_verification($user_id);
            }
        }

        /**
         * @param string $address
         * @param string $status
         * @param string $label
         * @param bool   $can_send
         * @param bool   $is_verified
         * @return array<string,mixed>
         */
        private function build_status_data($address, $status, $label, $can_send, $is_verified)
        {
            return array(
                'address'     => (string) $address,
                'status'      => (string) $status,
                'label'       => (string) $label,
                'can_send'    => (bool) $can_send,
                'is_verified' => (bool) $is_verified,
            );
        }

        /**
         * @param WP_User|false $user
         * @return string
         */
        private function build_email_subject($user)
        {
            $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
            $subject   = sprintf('تایید ایمیل حساب شما در %s', $site_name);

            return (string) apply_filters('bornado_contact_verification_email_subject', $subject, $user);
        }

        /**
         * @param WP_User|false $user
         * @param string        $verification_link
         * @return string
         */
        private function build_email_body($user, $verification_link)
        {
            $display_name = $user instanceof WP_User && '' !== trim((string) $user->display_name)
                ? trim((string) $user->display_name)
                : 'کاربر برنادو';

            $html = '<p>سلام ' . esc_html($display_name) . '،</p>';
            $html .= '<p>برای تایید آدرس ایمیل حساب کاربری خود روی دکمه زیر کلیک کنید.</p>';
            $html .= '<p><a href="' . esc_url($verification_link) . '" style="display:inline-block;padding:10px 18px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:6px;">تایید ایمیل</a></p>';
            $html .= '<p>اگر دکمه بالا کار نکرد، این لینک را در مرورگر باز کنید:</p>';
            $html .= '<p><a href="' . esc_url($verification_link) . '">' . esc_html($verification_link) . '</a></p>';

            return (string) apply_filters('bornado_contact_verification_email_body', $html, $user, $verification_link);
        }

        /**
         * @param string $to
         * @param string $subject
         * @param string $body
         * @return true|WP_Error
         */
        private function send_email_message($to, $subject, $body)
        {
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $headers = apply_filters('bornado_contact_verification_email_headers', $headers, $to, $subject, $body);

            $sent = wp_mail($to, $subject, $body, $headers);
            if (!$sent) {
                return new WP_Error('mail_failed', 'ارسال ایمیل تایید انجام نشد. تنظیمات SMTP یا ایمیل وردپرس را بررسی کنید.');
            }

            return true;
        }

        /**
         * @param int    $user_id
         * @param string $token
         * @param int    $expires_at
         * @return string
         */
        private function build_email_verification_link($user_id, $token, $expires_at)
        {
            $args = array(
                'bornado_contact_verify' => 'email',
                'bcv_uid'                => (int) $user_id,
                'bcv_token'              => (string) $token,
                'bcv_exp'                => (int) $expires_at,
            );

            return add_query_arg($args, home_url('/'));
        }

        /**
         * @param int    $user_id
         * @param string $phone
         * @param string $code
         * @param int    $expires_at
         * @return array<string,mixed>
         */
        private function build_whatsapp_verification_event($user_id, $phone, $code, $expires_at)
        {
            $user         = get_userdata($user_id);
            $display_name = $user instanceof WP_User ? (string) $user->display_name : '';
            $email        = $user instanceof WP_User ? strtolower(trim((string) $user->user_email)) : '';
            $profile_url  = add_query_arg('page_type', 'my_profile', $this->get_profile_url());

            return array(
                'eventId'        => sprintf('user.verification_requested.whatsapp.%d.%s', $user_id, gmdate('YmdHis')),
                'eventType'      => 'user.verification_requested',
                'eventVersion'   => 1,
                'occurredAt'     => gmdate('c'),
                'sourceSystem'   => $this->get_source_system(),
                'idempotencyKey' => sha1('user.verification_requested|whatsapp|' . $user_id . '|' . $phone . '|' . $code),
                'locale'         => determine_locale(),
                'payload'        => array(
                    'user'         => array(
                        'id'                  => 'wp-user-' . $user_id,
                        'externalId'          => (string) $user_id,
                        'displayName'         => $display_name,
                        'email'               => $email,
                        'phone'               => $phone,
                        'phoneVerified'       => '1' === (string) get_user_meta($user_id, '_sb_is_ph_verified', true),
                        'profileUrl'          => $profile_url,
                        'channelCapabilities' => array(
                            'whatsapp' => true,
                            'sms'      => '' !== $phone,
                            'email'    => is_email($email),
                        ),
                        'contacts'            => array(
                            array(
                                'channel'      => 'whatsapp',
                                'address'      => $phone,
                                'verified'     => false,
                                'primary'      => true,
                                'priority'     => 10,
                                'capabilities' => array(
                                    'whatsapp'      => true,
                                    'transactional' => true,
                                ),
                            ),
                        ),
                    ),
                    'verification' => array(
                        'channel'       => 'whatsapp',
                        'code'          => $code,
                        'expiresAt'     => gmdate('c', $expires_at),
                        'expiresInMins' => (int) ceil(max(0, $expires_at - time()) / 60),
                        'manageUrl'     => $profile_url,
                    ),
                ),
            );
        }

        /**
         * @param array<string,mixed> $event
         * @return true|WP_Error
         */
        private function publish_notification_event(array $event)
        {
            $endpoint = $this->get_ingest_url();
            if ('' === $endpoint) {
                return $this->enqueue_event_locally($event);
            }

            $body = wp_json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (false === $body || '' === $body) {
                return new WP_Error('invalid_event_body', 'ساخت داده پیام واتس اپ نامعتبر بود.');
            }

            $headers = array(
                'Content-Type' => 'application/json; charset=utf-8',
            );
            $secret = $this->get_shared_secret();
            if ('' !== $secret) {
                $headers['X-Bornado-Signature'] = hash_hmac('sha256', $body, $secret);
            }

            $response = wp_remote_post(
                $endpoint,
                array(
                    'headers' => $headers,
                    'body'    => $body,
                    'timeout' => 8,
                )
            );

            if (is_wp_error($response)) {
                return $this->enqueue_event_locally(
                    $event,
                    array(
                        'reason' => 'wp_remote_post_failed',
                        'error'  => $response->get_error_message(),
                    )
                );
            }

            $status_code = (int) wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 300) {
                return $this->enqueue_event_locally(
                    $event,
                    array(
                        'reason'      => 'wp_remote_post_rejected',
                        'status_code' => $status_code,
                        'body'        => (string) wp_remote_retrieve_body($response),
                    )
                );
            }

            $this->trigger_notification_consumer(
                isset($event['eventId']) ? (string) $event['eventId'] : '',
                10
            );

            return true;
        }

        /**
         * @param array<string,mixed> $event
         * @param array<string,mixed> $context
         * @return true|WP_Error
         */
        private function enqueue_event_locally(array $event, array $context = array())
        {
            $bootstrap = trailingslashit(ABSPATH) . 'Services/bornado-notification-platform/bootstrap.php';
            $config    = trailingslashit(ABSPATH) . 'Services/bornado-notification-platform/config/notification-platform.php';

            if (!file_exists($bootstrap) || !file_exists($config)) {
                return new WP_Error('notification_service_missing', 'سرویس نوتیفیکیشن واتس اپ در دسترس نیست.');
            }

            require_once $bootstrap;

            if (
                !class_exists('Bornado\\NotificationPlatform\\Contracts\\EventCatalog')
                || !class_exists('Bornado\\NotificationPlatform\\Infrastructure\\FileEventQueue')
                || !class_exists('Bornado\\NotificationPlatform\\Infrastructure\\FileDeliveryLog')
            ) {
                return new WP_Error('notification_service_bootstrap_failed', 'راه‌اندازی سرویس نوتیفیکیشن انجام نشد.');
            }

            $platform_config = require $config;
            if (!is_array($platform_config)) {
                return new WP_Error('notification_config_invalid', 'تنظیمات سرویس نوتیفیکیشن نامعتبر است.');
            }

            $errors = \Bornado\NotificationPlatform\Contracts\EventCatalog::validate($event);
            if (!empty($errors)) {
                return new WP_Error('notification_event_invalid', implode(' ', $errors));
            }

            $queue = new \Bornado\NotificationPlatform\Infrastructure\FileEventQueue($platform_config['queue']);
            $log   = new \Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog(
                $platform_config['logging']['delivery_log'],
                $platform_config['logging']['state_dir']
            );

            $queue_path = $queue->enqueue($event);
            $log->markEvent(
                $event,
                'queued',
                array_merge(
                    array(
                        'queuePath' => $queue_path,
                        'ingestion' => 'wordpress_contact_verification',
                    ),
                    $context
                )
            );

            $this->trigger_notification_consumer(
                isset($event['eventId']) ? (string) $event['eventId'] : '',
                10
            );

            return true;
        }

        /**
         * Best-effort immediate queue processing for verification messages.
         *
         * Without this, the UI can show a success toast while the event still
         * waits for the scheduled consumer/cron to pick it up.
         *
         * @param string $event_id
         * @param int    $limit
         * @return void
         */
        private function trigger_notification_consumer($event_id = '', $limit = 10)
        {
            $bootstrap = trailingslashit(ABSPATH) . 'Services/bornado-notification-platform/bootstrap.php';
            $config    = trailingslashit(ABSPATH) . 'Services/bornado-notification-platform/config/notification-platform.php';

            if (!file_exists($bootstrap) || !file_exists($config)) {
                return;
            }

            require_once $bootstrap;

            if (!class_exists('Bornado\\NotificationPlatform\\Application\\QueueConsumer')) {
                return;
            }

            $platform_config = require $config;
            if (!is_array($platform_config)) {
                return;
            }

            try {
                $consumer = new \Bornado\NotificationPlatform\Application\QueueConsumer($platform_config);
                $result   = $consumer->run(max(1, (int) $limit), false);

                if ('' !== $event_id && is_array($result['results'] ?? null)) {
                    foreach ($result['results'] as $item) {
                        if (
                            is_array($item)
                            && ((string) ($item['eventId'] ?? '') === $event_id)
                            && 'failed' === (string) ($item['status'] ?? '')
                        ) {
                            error_log('Bornado contact verification consumer failed for event: ' . $event_id);
                            break;
                        }
                    }
                }
            } catch (\Throwable $throwable) {
                error_log('Bornado contact verification consumer trigger failed: ' . $throwable->getMessage());
            }
        }

        /**
         * @param int $user_id
         * @return void
         */
        private function reset_email_verification($user_id)
        {
            update_user_meta($user_id, self::EMAIL_STATUS_KEY, 'unverified');
            delete_user_meta($user_id, self::EMAIL_VERIFIED_AT_KEY);
            delete_user_meta($user_id, self::EMAIL_ADDRESS_KEY);
            delete_user_meta($user_id, self::EMAIL_TOKEN_KEY);
            delete_user_meta($user_id, self::EMAIL_EXPIRES_AT_KEY);
            delete_user_meta($user_id, self::EMAIL_SENT_AT_KEY);
            delete_user_meta($user_id, 'sb_email_verification_token');
            update_user_meta($user_id, 'sb_user_email_verification_status', '');
        }

        /**
         * @param int $user_id
         * @return void
         */
        private function reset_whatsapp_verification($user_id)
        {
            update_user_meta($user_id, self::WHATSAPP_STATUS_KEY, 'unverified');
            delete_user_meta($user_id, self::WHATSAPP_VERIFIED_AT_KEY);
            delete_user_meta($user_id, self::WHATSAPP_VERIFIED_PHONE_KEY);
            delete_user_meta($user_id, self::WHATSAPP_PENDING_PHONE_KEY);
            delete_user_meta($user_id, self::WHATSAPP_CODE_HASH_KEY);
            delete_user_meta($user_id, self::WHATSAPP_EXPIRES_AT_KEY);
            delete_user_meta($user_id, self::WHATSAPP_ATTEMPTS_KEY);
            delete_user_meta($user_id, self::WHATSAPP_REQUESTED_AT_KEY);
        }

        /**
         * @param string $notice_code
         * @return void
         */
        private function redirect_with_notice($notice_code)
        {
            $target = add_query_arg(
                'bornado_contact_notice',
                sanitize_key($notice_code),
                add_query_arg('page_type', 'my_profile', $this->get_profile_url())
            );

            wp_safe_redirect($target);
            exit;
        }

        /**
         * @param string $action
         * @param string $key
         * @return void
         */
        private function verify_ajax_nonce($action, $key)
        {
            $nonce = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            if (!wp_verify_nonce($nonce, $action)) {
                wp_send_json_error(array('message' => 'اعتبار امنیتی درخواست نامعتبر است.'), 403);
            }
        }

        /**
         * @return void
         */
        private function guard_demo_mode()
        {
            if (function_exists('adforest_is_demo') && adforest_is_demo()) {
                wp_send_json_error(array('message' => 'در حالت دمو این عملیات مجاز نیست.'), 403);
            }
        }

        /**
         * @param string $phone
         * @return string
         */
        private function normalize_phone($phone)
        {
            if (function_exists('bornado_normalize_phone_with_dial_code')) {
                $normalized = bornado_normalize_phone_with_dial_code((string) $phone, '');
                if ('' !== $normalized) {
                    return $normalized;
                }
            }

            $phone = trim((string) $phone);
            $phone = preg_replace('/[^\d+]/', '', $phone);
            if ('' === $phone) {
                return '';
            }

            if (0 === strpos($phone, '00')) {
                $phone = '+' . substr($phone, 2);
            } elseif ('+' !== substr($phone, 0, 1)) {
                $phone = '+' . ltrim($phone, '+');
            }

            return '+' . preg_replace('/[^\d]/', '', $phone);
        }

        /**
         * @param string $phone
         * @return bool
         */
        private function is_valid_phone_number($phone)
        {
            return (bool) preg_match('/^\+\d{8,16}$/', (string) $phone);
        }

        /**
         * @return bool
         */
        private function is_profile_page()
        {
            if (is_admin()) {
                return false;
            }

            $page_type = isset($_GET['page_type']) ? sanitize_key(wp_unslash($_GET['page_type'])) : '';

            return 'my_profile' === $page_type;
        }

        /**
         * @return string
         */
        private function get_profile_url()
        {
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

        /**
         * @return string
         */
        private function get_ingest_url()
        {
            if (defined('BORNADO_NOTIFICATION_INGEST_URL')) {
                return esc_url_raw((string) BORNADO_NOTIFICATION_INGEST_URL);
            }

            $config_path = trailingslashit(WP_PLUGIN_DIR) . 'bornado-notification-bridge/config/bornado-notification-bridge-config.php';
            if (file_exists($config_path)) {
                require_once $config_path;
                if (defined('BORNADO_NOTIFICATION_INGEST_URL')) {
                    return esc_url_raw((string) BORNADO_NOTIFICATION_INGEST_URL);
                }
            }

            return '';
        }

        /**
         * @return string
         */
        private function get_shared_secret()
        {
            if (defined('BORNADO_NOTIFICATION_SHARED_SECRET')) {
                return trim((string) BORNADO_NOTIFICATION_SHARED_SECRET);
            }

            $config_path = trailingslashit(WP_PLUGIN_DIR) . 'bornado-notification-bridge/config/bornado-notification-bridge-config.php';
            if (file_exists($config_path)) {
                require_once $config_path;
                if (defined('BORNADO_NOTIFICATION_SHARED_SECRET')) {
                    return trim((string) BORNADO_NOTIFICATION_SHARED_SECRET);
                }
            }

            return '';
        }

        /**
         * @return string
         */
        private function get_source_system()
        {
            if (defined('BORNADO_NOTIFICATION_SOURCE_SYSTEM')) {
                return (string) BORNADO_NOTIFICATION_SOURCE_SYSTEM;
            }

            return 'bornado-wordpress';
        }
    }
}
