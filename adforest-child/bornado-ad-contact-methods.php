<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Bornado_Ad_Contact_Methods')) {
    final class Bornado_Ad_Contact_Methods
    {
        const POST_TYPE = 'ad_post';
        const META_KEY = '_bornado_contact_methods';
        const VERSION_META_KEY = '_bornado_contact_methods_version';
        const VERSION = '1';

        /**
         * @var array<int, string>|null
         */
        private static $request_methods = null;

        /**
         * @var bool
         */
        private static $request_has_version = false;

        /**
         * @var array<int, bool>
         */
        private static $persisted_posts = array();

        /**
         * Bootstrap hooks.
         *
         * @return void
         */
        public static function init()
        {
            add_action('wp_ajax_sb_ad_posting', array(__CLASS__, 'prefilter_ajax_request'), 0);
            add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'persist_request_methods_on_save'), 20, 3);
            add_action('added_post_meta', array(__CLASS__, 'maybe_persist_request_methods'), 20, 4);
            add_action('updated_post_meta', array(__CLASS__, 'maybe_persist_request_methods'), 20, 4);
        }

        /**
         * Force ad-post submissions to use profile contact data and keep a copy
         * of the chosen contact methods for post-save persistence.
         *
         * @return void
         */
        public static function prefilter_ajax_request()
        {
            self::$request_methods = null;
            self::$request_has_version = false;
            self::$persisted_posts = array();

            if (empty($_POST['sb_data'])) {
                return;
            }

            $raw_data = wp_unslash($_POST['sb_data']);
            if (!is_string($raw_data) || '' === $raw_data) {
                return;
            }

            parse_str($raw_data, $params);
            if (!is_array($params)) {
                return;
            }

            $user_id = get_current_user_id();
            if ($user_id > 0) {
                $user = get_userdata($user_id);
                if ($user instanceof WP_User) {
                    $params['sb_user_name'] = (string) $user->display_name;
                    $params['ad_contact_number'] = (string) get_user_meta($user_id, '_sb_contact', true);
                }
            }

            self::$request_has_version = !empty($params['bornado_contact_methods_version']);
            if (self::$request_has_version) {
                $requested = isset($params['bornado_contact_methods']) ? $params['bornado_contact_methods'] : array();
                self::$request_methods = self::sanitize_contact_methods($requested, $user_id);
                $params['bornado_contact_methods'] = self::$request_methods;
                $params['bornado_contact_methods_version'] = self::VERSION;
            }

            $_POST['sb_data'] = wp_slash(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        }

        /**
         * Persist the normalized contact methods once AdForest has created or
         * updated the ad meta during the same AJAX request.
         *
         * @param int    $meta_id    Meta ID.
         * @param int    $post_id    Post ID.
         * @param string $meta_key   Meta key.
         * @param mixed  $meta_value Meta value.
         * @return void
         */
        public static function maybe_persist_request_methods($meta_id, $post_id, $meta_key, $meta_value)
        {
            unset($meta_id, $meta_value);

            if (!self::$request_has_version || !in_array((string) $meta_key, array('_adforest_poster_name', '_adforest_poster_contact'), true)) {
                return;
            }

            $post_id = (int) $post_id;
            if ($post_id < 1 || isset(self::$persisted_posts[$post_id]) || self::POST_TYPE !== get_post_type($post_id)) {
                return;
            }

            self::$persisted_posts[$post_id] = true;

            update_post_meta($post_id, self::META_KEY, is_array(self::$request_methods) ? self::$request_methods : array());
            update_post_meta($post_id, self::VERSION_META_KEY, self::VERSION);
        }

        /**
         * Persist contact methods whenever the ad itself is saved. This covers
         * edit requests where poster name/phone do not change, so post-meta
         * hooks for those keys never fire.
         *
         * @param int      $post_id Post ID.
         * @param WP_Post  $post    Saved post object.
         * @param bool     $update  Whether this is an existing post.
         * @return void
         */
        public static function persist_request_methods_on_save($post_id, $post, $update)
        {
            unset($update);

            if (
                !self::$request_has_version
                || isset(self::$persisted_posts[(int) $post_id])
                || !($post instanceof WP_Post)
                || self::POST_TYPE !== $post->post_type
                || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            ) {
                return;
            }

            $post_id = (int) $post_id;
            if ($post_id < 1) {
                return;
            }

            self::$persisted_posts[$post_id] = true;

            update_post_meta($post_id, self::META_KEY, is_array(self::$request_methods) ? self::$request_methods : array());
            update_post_meta($post_id, self::VERSION_META_KEY, self::VERSION);
        }

        /**
         * @param mixed $raw_methods
         * @param int   $user_id
         * @return array<int, string>
         */
        public static function sanitize_contact_methods($raw_methods, $user_id = 0)
        {
            $requested = array();
            foreach ((array) $raw_methods as $raw_method) {
                $key = sanitize_key((string) $raw_method);
                if ('' !== $key) {
                    $requested[] = $key;
                }
            }

            $requested = array_values(array_unique($requested));
            $allowed = array(
                'phone' => true,
                'whatsapp' => true,
                'email' => true,
                'site_message' => true,
            );
            $status_map = self::get_user_contact_method_statuses($user_id);
            $sanitized = array();

            foreach ($requested as $method) {
                if (!isset($allowed[$method])) {
                    continue;
                }

                if ('site_message' !== $method && empty($status_map[$method]['enabled'])) {
                    continue;
                }

                $sanitized[] = $method;
            }

            return array_values(array_unique($sanitized));
        }

        /**
         * @param int $post_id
         * @return bool
         */
        public static function has_saved_contact_methods($post_id)
        {
            $post_id = (int) $post_id;
            return $post_id > 0 && metadata_exists('post', $post_id, self::META_KEY);
        }

        /**
         * @param int $post_id
         * @return array<int, string>
         */
        public static function get_saved_contact_methods($post_id)
        {
            $post_id = (int) $post_id;
            if ($post_id < 1) {
                return array();
            }

            $stored = get_post_meta($post_id, self::META_KEY, true);
            if (!is_array($stored)) {
                $stored = array();
            }

            return self::sanitize_contact_methods($stored, (int) get_post_field('post_author', $post_id));
        }

        /**
         * @param int $post_id
         * @param int $user_id
         * @return array<int, string>
         */
        public static function get_form_contact_methods($post_id = 0, $user_id = 0)
        {
            $post_id = (int) $post_id;
            $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();

            if ($post_id > 0 && self::has_saved_contact_methods($post_id)) {
                return self::sanitize_contact_methods(self::get_saved_contact_methods($post_id), $user_id);
            }

            return self::get_legacy_default_contact_methods($post_id, $user_id);
        }

        /**
         * @param int $post_id
         * @param int $user_id
         * @return array<int, string>
         */
        public static function get_legacy_default_contact_methods($post_id = 0, $user_id = 0)
        {
            global $adforest_theme;

            $post_id = (int) $post_id;
            $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();
            $contact_num = $user_id > 0 ? (string) get_user_meta($user_id, '_sb_contact', true) : '';
            $status_map = self::get_user_contact_method_statuses($user_id);
            $communication_mode = isset($adforest_theme['communication_mode']) ? (string) $adforest_theme['communication_mode'] : 'both';
            $defaults = array();

            if ('' !== $contact_num && in_array($communication_mode, array('both', 'phone'), true)) {
                if (!empty($status_map['phone']['enabled'])) {
                    $defaults[] = 'phone';
                }
            }

            if ($post_id > 0 && self::has_saved_contact_methods($post_id)) {
                return self::sanitize_contact_methods(self::get_saved_contact_methods($post_id), $user_id);
            }

            return array_values(array_unique($defaults));
        }

        /**
         * @param int $user_id
         * @return array<string, array<string, mixed>>
         */
        public static function get_user_contact_method_statuses($user_id = 0)
        {
            $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();
            $user = $user_id > 0 ? get_userdata($user_id) : false;
            $profile_url = add_query_arg('page_type', 'my_profile', self::get_profile_url());
            $phone_value = $user_id > 0 ? (string) get_user_meta($user_id, '_sb_contact', true) : '';
            $phone_verified = '1' === (string) get_user_meta($user_id, '_sb_is_ph_verified', true);
            $email_status = function_exists('bornado_contact_verification_get_email_status')
                ? (array) bornado_contact_verification_get_email_status($user_id)
                : array(
                    'address' => $user instanceof WP_User ? (string) $user->user_email : '',
                    'is_verified' => false,
                );
            $whatsapp_status = function_exists('bornado_contact_verification_get_whatsapp_status')
                ? (array) bornado_contact_verification_get_whatsapp_status($user_id)
                : array(
                    'address' => $phone_value,
                    'is_verified' => false,
                );

            return array(
                'phone' => array(
                    'key' => 'phone',
                    'label' => 'تماس تلفنی',
                    'value' => $phone_value,
                    'status_label' => $phone_verified ? 'تایید شده' : ($phone_value !== '' ? 'تایید نشده' : 'ثبت نشده'),
                    'enabled' => $phone_verified && '' !== $phone_value,
                    'help_html' => wp_kses_post(
                        sprintf(
                            'برای ارتباط از طریق شماره تلفن باید ابتدا شماره تلفن خود را در <a href="%s">پروفایل</a> تایید کنید.',
                            esc_url($profile_url)
                        )
                    ),
                ),
                'whatsapp' => array(
                    'key' => 'whatsapp',
                    'label' => 'واتس اپ',
                    'value' => isset($whatsapp_status['address']) ? (string) $whatsapp_status['address'] : $phone_value,
                    'status_label' => !empty($whatsapp_status['is_verified']) ? 'تایید شده' : (!empty($whatsapp_status['address']) ? 'تایید نشده' : 'ثبت نشده'),
                    'enabled' => !empty($whatsapp_status['is_verified']) && !empty($whatsapp_status['address']),
                    'help_html' => wp_kses_post(
                        sprintf(
                            'برای ارتباط از طریق واتس اپ باید ابتدا شماره واتس اپ خود را در <a href="%s">پروفایل</a> تایید کنید.',
                            esc_url($profile_url)
                        )
                    ),
                ),
                'email' => array(
                    'key' => 'email',
                    'label' => 'ایمیل',
                    'value' => isset($email_status['address']) ? (string) $email_status['address'] : ($user instanceof WP_User ? (string) $user->user_email : ''),
                    'status_label' => !empty($email_status['is_verified']) ? 'تایید شده' : (!empty($email_status['address']) ? 'تایید نشده' : 'ثبت نشده'),
                    'enabled' => !empty($email_status['is_verified']) && !empty($email_status['address']),
                    'help_html' => wp_kses_post(
                        sprintf(
                            'برای ارتباط از طریق ایمیل باید ابتدا ایمیل خود را در <a href="%s">پروفایل</a> تایید کنید.',
                            esc_url($profile_url)
                        )
                    ),
                ),
                'site_message' => array(
                    'key' => 'site_message',
                    'label' => 'ارسال پیام در سایت',
                    'value' => '',
                    'status_label' => '',
                    'enabled' => true,
                    'help_html' => '',
                ),
            );
        }

        /**
         * @param int $user_id
         * @return array<string, mixed>
         */
        public static function get_ad_post_form_context($user_id = 0)
        {
            $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();
            $edit_post_id = self::get_edit_post_id();
            $user = $user_id > 0 ? get_userdata($user_id) : false;

            return array(
                'enabled' => $user_id > 0,
                'profileName' => $user instanceof WP_User ? (string) $user->display_name : '',
                'profilePhone' => $user_id > 0 ? (string) get_user_meta($user_id, '_sb_contact', true) : '',
                'selectedMethods' => self::get_form_contact_methods($edit_post_id, $user_id),
                'statusMap' => self::get_user_contact_method_statuses($user_id),
                'profileUrl' => add_query_arg('page_type', 'my_profile', self::get_profile_url()),
                'strings' => array(
                    'sectionTitle' => 'روش های ارتباطی برای این آگهی',
                    'sectionHint' => 'تنها روش های انتخاب شده شما ، در صفحه این آگهی به کاربران نمایش داده خواهد شد.',
                    'verified' => 'تایید شده',
                    'needVerification' => 'نیاز به تایید',
                    'helpLabel' => 'راهنمای فعال‌سازی',
                ),
            );
        }

        /**
         * @return int
         */
        private static function get_edit_post_id()
        {
            $post_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
            if ($post_id < 1 || self::POST_TYPE !== get_post_type($post_id)) {
                return 0;
            }

            $post_author_id = (int) get_post_field('post_author', $post_id);
            $current_user_id = (int) get_current_user_id();
            if ($post_author_id < 1) {
                return 0;
            }

            if ($post_author_id !== $current_user_id && !is_super_admin($current_user_id)) {
                return 0;
            }

            return $post_id;
        }

        /**
         * @return string
         */
        public static function get_profile_url()
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
    }
}

Bornado_Ad_Contact_Methods::init();

if (!function_exists('bornado_get_ad_post_contact_methods_context')) {
    /**
     * @param int $user_id
     * @return array<string, mixed>
     */
    function bornado_get_ad_post_contact_methods_context($user_id = 0)
    {
        return Bornado_Ad_Contact_Methods::get_ad_post_form_context((int) $user_id);
    }
}

if (!function_exists('bornado_get_user_contact_method_statuses')) {
    /**
     * @param int $user_id
     * @return array<string, array<string, mixed>>
     */
    function bornado_get_user_contact_method_statuses($user_id = 0)
    {
        return Bornado_Ad_Contact_Methods::get_user_contact_method_statuses((int) $user_id);
    }
}

if (!function_exists('bornado_get_ad_contact_methods')) {
    /**
     * @param int $post_id
     * @return array<int, string>
     */
    function bornado_get_ad_contact_methods($post_id)
    {
        return Bornado_Ad_Contact_Methods::get_saved_contact_methods((int) $post_id);
    }
}

if (!function_exists('bornado_has_ad_contact_methods')) {
    /**
     * @param int $post_id
     * @return bool
     */
    function bornado_has_ad_contact_methods($post_id)
    {
        return Bornado_Ad_Contact_Methods::has_saved_contact_methods((int) $post_id);
    }
}

if (!function_exists('bornado_get_profile_edit_url')) {
    /**
     * @return string
     */
    function bornado_get_profile_edit_url()
    {
        return add_query_arg('page_type', 'my_profile', Bornado_Ad_Contact_Methods::get_profile_url());
    }
}
