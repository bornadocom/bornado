<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Bornado_Ad_Currency_Sync
{
    const POST_TYPE = 'ad_post';
    const LOCATION_TAXONOMY = 'ad_country';
    const CURRENCY_TAXONOMY = 'ad_currency';
    const POST_CURRENCY_META = '_adforest_ad_currency';
    const ISSUE_META = '_bornado_currency_sync_issue';

    /**
     * Prevent recursive sync loops when the class updates terms/meta/status itself.
     *
     * @var array<int,bool>
     */
    private static $processing_posts = array();

    /**
     * Bootstrap hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_action('set_object_terms', array(__CLASS__, 'handle_set_object_terms'), 20, 6);
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'handle_save_post'), 100, 3);
        add_action('added_post_meta', array(__CLASS__, 'handle_currency_meta_change'), 20, 4);
        add_action('updated_post_meta', array(__CLASS__, 'handle_currency_meta_change'), 20, 4);
        add_action('rest_after_insert_' . self::POST_TYPE, array(__CLASS__, 'handle_rest_insert'), 20, 3);
        add_action('admin_notices', array(__CLASS__, 'render_admin_notice'));
    }

    /**
     * Re-sync currency whenever location or currency terms are changed.
     *
     * @param int          $object_id Post ID.
     * @param array<mixed> $terms Assigned terms.
     * @param array<mixed> $tt_ids Term taxonomy IDs.
     * @param string       $taxonomy Taxonomy slug.
     * @param bool         $append Whether to append terms.
     * @param array<mixed> $old_tt_ids Previous term taxonomy IDs.
     * @return void
     */
    public static function handle_set_object_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids)
    {
        unset($terms, $tt_ids, $append, $old_tt_ids);

        if (!in_array($taxonomy, array(self::LOCATION_TAXONOMY, self::CURRENCY_TAXONOMY), true)) {
            return;
        }

        $post_id = (int) $object_id;
        if (!self::should_process_post($post_id)) {
            return;
        }

        self::sync_post_currency($post_id);
    }

    /**
     * Fallback sync for direct admin saves that do not touch terms in the current request.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post Post object.
     * @param bool    $update Whether this is an update.
     * @return void
     */
    public static function handle_save_post($post_id, $post, $update)
    {
        unset($update);

        if (!($post instanceof WP_Post) || self::POST_TYPE !== $post->post_type) {
            return;
        }

        if (!self::should_process_post($post_id)) {
            return;
        }

        if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
            return;
        }

        self::sync_post_currency((int) $post_id);
    }

    /**
     * Keep post meta aligned even if a flow writes the wrong currency name after term sync.
     *
     * @param int    $meta_id Meta ID.
     * @param int    $post_id Post ID.
     * @param string $meta_key Meta key.
     * @param mixed  $meta_value Meta value.
     * @return void
     */
    public static function handle_currency_meta_change($meta_id, $post_id, $meta_key, $meta_value)
    {
        unset($meta_id, $meta_value);

        if (self::POST_CURRENCY_META !== $meta_key) {
            return;
        }

        $post_id = (int) $post_id;
        if (!self::should_process_post($post_id)) {
            return;
        }

        self::sync_post_currency($post_id);
    }

    /**
     * Final REST safeguard after the post, terms, and meta have been inserted.
     *
     * @param WP_Post         $post Inserted post.
     * @param WP_REST_Request $request Request object.
     * @param bool            $creating Whether this is a create operation.
     * @return void
     */
    public static function handle_rest_insert($post, $request, $creating)
    {
        unset($request, $creating);

        if (!($post instanceof WP_Post) || self::POST_TYPE !== $post->post_type) {
            return;
        }

        if (!self::should_process_post((int) $post->ID)) {
            return;
        }

        self::sync_post_currency((int) $post->ID);
    }

    /**
     * Show a clear admin notice when a post was forced back to pending review.
     *
     * @return void
     */
    public static function render_admin_notice()
    {
        if (!is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || self::POST_TYPE !== $screen->post_type) {
            return;
        }

        $post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
        if ($post_id < 1) {
            return;
        }

        $issue = get_post_meta($post_id, self::ISSUE_META, true);
        if (!is_string($issue) || '' === $issue) {
            return;
        }

        $message = self::get_issue_message($issue);
        if ('' === $message) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html($message)
        );
    }

    /**
     * Resolve and enforce the expected post currency.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private static function sync_post_currency($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1 || isset(self::$processing_posts[$post_id])) {
            return;
        }

        if (!function_exists('bornado_get_country_currency_payload_for_post')) {
            return;
        }

        self::$processing_posts[$post_id] = true;

        try {
            $payload = bornado_get_country_currency_payload_for_post($post_id);

            if (!self::is_valid_payload($payload)) {
                self::clear_post_currency($post_id);
                self::store_issue($post_id, !empty($payload['reason']) ? (string) $payload['reason'] : 'missing_country_currency');
                self::maybe_force_pending($post_id);
                return;
            }

            self::assign_currency(
                $post_id,
                (int) $payload['currency_term_id'],
                (string) $payload['currency_meta']
            );

            delete_post_meta($post_id, self::ISSUE_META);
        } finally {
            unset(self::$processing_posts[$post_id]);
        }
    }

    /**
     * Validate the resolved payload structure.
     *
     * @param mixed $payload Payload array.
     * @return bool
     */
    private static function is_valid_payload($payload)
    {
        return is_array($payload)
            && !empty($payload['is_valid'])
            && !empty($payload['currency_term_id'])
            && !empty($payload['currency_meta']);
    }

    /**
     * Apply the expected taxonomy term and mirrored post meta.
     *
     * @param int    $post_id Post ID.
     * @param int    $currency_term_id Currency term ID.
     * @param string $currency_meta Currency display name.
     * @return void
     */
    private static function assign_currency($post_id, $currency_term_id, $currency_meta)
    {
        $post_id = (int) $post_id;
        $currency_term_id = (int) $currency_term_id;
        $currency_meta = sanitize_text_field($currency_meta);

        $current_term_ids = wp_get_post_terms(
            $post_id,
            self::CURRENCY_TAXONOMY,
            array(
                'fields' => 'ids',
            )
        );
        $current_term_ids = is_wp_error($current_term_ids) ? array() : array_map('intval', (array) $current_term_ids);

        if (array($currency_term_id) !== array_values($current_term_ids)) {
            wp_set_object_terms($post_id, array($currency_term_id), self::CURRENCY_TAXONOMY, false);
        }

        if ((string) get_post_meta($post_id, self::POST_CURRENCY_META, true) !== $currency_meta) {
            update_post_meta($post_id, self::POST_CURRENCY_META, $currency_meta);
        }
    }

    /**
     * Remove currency data when the location/country configuration is invalid.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private static function clear_post_currency($post_id)
    {
        $post_id = (int) $post_id;

        $current_term_ids = wp_get_post_terms(
            $post_id,
            self::CURRENCY_TAXONOMY,
            array(
                'fields' => 'ids',
            )
        );
        $current_term_ids = is_wp_error($current_term_ids) ? array() : array_filter(array_map('intval', (array) $current_term_ids));

        if (!empty($current_term_ids)) {
            wp_set_object_terms($post_id, array(), self::CURRENCY_TAXONOMY, false);
        }

        if ('' !== (string) get_post_meta($post_id, self::POST_CURRENCY_META, true)) {
            delete_post_meta($post_id, self::POST_CURRENCY_META);
        }
    }

    /**
     * Move invalid posts to pending review so they cannot stay published without a valid currency.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private static function maybe_force_pending($post_id)
    {
        $post = get_post($post_id);
        if (!($post instanceof WP_Post) || self::POST_TYPE !== $post->post_type) {
            return;
        }

        if (in_array($post->post_status, array('auto-draft', 'inherit', 'trash', 'pending'), true)) {
            return;
        }

        remove_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'handle_save_post'), 100);
        wp_update_post(
            array(
                'ID' => (int) $post_id,
                'post_status' => 'pending',
            )
        );
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'handle_save_post'), 100, 3);
    }

    /**
     * Persist the latest validation issue for later inspection in wp-admin.
     *
     * @param int    $post_id Post ID.
     * @param string $issue Issue code.
     * @return void
     */
    private static function store_issue($post_id, $issue)
    {
        update_post_meta($post_id, self::ISSUE_META, sanitize_key($issue));
    }

    /**
     * Whether a post can be processed by the sync layer.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function should_process_post($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1 || isset(self::$processing_posts[$post_id])) {
            return false;
        }

        return self::POST_TYPE === get_post_type($post_id);
    }

    /**
     * Convert an issue code into a human-readable admin notice.
     *
     * @param string $issue Issue code.
     * @return string
     */
    private static function get_issue_message($issue)
    {
        switch ((string) $issue) {
            case 'missing_location':
                return 'این آگهی به دلیل نداشتن کشور یا شهر معتبر به حالت در انتظار بازبینی رفت تا ابتدا لوکیشن آن تکمیل شود و سپس کارنسی درست به‌صورت خودکار اعمال گردد.';
            case 'missing_country':
                return 'این آگهی به حالت در انتظار بازبینی رفت چون کشور ریشه برای لوکیشن انتخاب‌شده قابل تشخیص نبود و در نتیجه تعیین کارنسی ممکن نشد.';
            case 'missing_country_currency':
            default:
                return 'این آگهی به حالت در انتظار بازبینی رفت چون برای کشور انتخاب‌شده هنوز کارنسی تعریف نشده است. ابتدا روی کشور ریشه یک کارنسی انتخاب کنید.';
        }
    }
}

Bornado_Ad_Currency_Sync::init();
