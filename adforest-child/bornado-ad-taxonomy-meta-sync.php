<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Bornado_Ad_Taxonomy_Meta_Sync
{
    const POST_TYPE = 'ad_post';

    /**
     * Prevent recursive updates while the sync layer adjusts terms/meta.
     *
     * @var array<int,bool>
     */
    private static $processing_posts = array();

    /**
     * Taxonomy/meta pairs that AdForest still reads from legacy post meta.
     *
     * @return array<string,string>
     */
    private static function get_synced_fields()
    {
        return array(
            'ad_type'      => '_adforest_ad_type',
            'ad_condition' => '_adforest_ad_condition',
            'ad_warranty'  => '_adforest_ad_warranty',
        );
    }

    /**
     * Bootstrap hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_action('set_object_terms', array(__CLASS__, 'handle_set_object_terms'), 20, 6);
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'handle_save_post'), 100, 3);
        add_action('added_post_meta', array(__CLASS__, 'handle_meta_change'), 20, 4);
        add_action('updated_post_meta', array(__CLASS__, 'handle_meta_change'), 20, 4);
        add_action('rest_after_insert_' . self::POST_TYPE, array(__CLASS__, 'handle_rest_insert'), 20, 3);
    }

    /**
     * Mirror taxonomy changes into AdForest's legacy post meta.
     *
     * @param int          $object_id Post ID.
     * @param array<mixed> $terms Assigned terms.
     * @param array<mixed> $tt_ids Term taxonomy IDs.
     * @param string       $taxonomy Taxonomy slug.
     * @param bool         $append Whether terms are appended.
     * @param array<mixed> $old_tt_ids Previous term taxonomy IDs.
     * @return void
     */
    public static function handle_set_object_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids)
    {
        unset($terms, $tt_ids, $append, $old_tt_ids);

        if (!isset(self::get_synced_fields()[$taxonomy])) {
            return;
        }

        $post_id = (int) $object_id;
        if (!self::should_process_post($post_id)) {
            return;
        }

        self::sync_field_from_terms($post_id, $taxonomy);
    }

    /**
     * Fallback sync for wp-admin saves where the term hook may not be enough.
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

        if (!self::should_process_post((int) $post_id)) {
            return;
        }

        if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
            return;
        }

        self::sync_all_fields_from_terms((int) $post_id);
    }

    /**
     * Backfill taxonomy assignments when only the legacy meta is written.
     *
     * @param int    $meta_id Meta ID.
     * @param int    $post_id Post ID.
     * @param string $meta_key Meta key.
     * @param mixed  $meta_value Meta value.
     * @return void
     */
    public static function handle_meta_change($meta_id, $post_id, $meta_key, $meta_value)
    {
        unset($meta_id, $meta_value);

        $taxonomy = self::get_taxonomy_by_meta_key((string) $meta_key);
        if ('' === $taxonomy) {
            return;
        }

        $post_id = (int) $post_id;
        if (!self::should_process_post($post_id)) {
            return;
        }

        self::sync_field_from_meta($post_id, $taxonomy);
    }

    /**
     * Final safeguard for REST-created or REST-updated ads.
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

        self::sync_all_fields_from_terms((int) $post->ID);
    }

    /**
     * Resolve the taxonomy name from a mirrored meta key.
     *
     * @param string $meta_key Meta key.
     * @return string
     */
    private static function get_taxonomy_by_meta_key($meta_key)
    {
        foreach (self::get_synced_fields() as $taxonomy => $field_meta_key) {
            if ($field_meta_key === $meta_key) {
                return (string) $taxonomy;
            }
        }

        return '';
    }

    /**
     * Make all mirrored post meta fields match their assigned taxonomy terms.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    private static function sync_all_fields_from_terms($post_id)
    {
        foreach (array_keys(self::get_synced_fields()) as $taxonomy) {
            self::sync_field_from_terms((int) $post_id, (string) $taxonomy);
        }
    }

    /**
     * Make one post meta field match the assigned taxonomy term.
     *
     * @param int    $post_id Post ID.
     * @param string $taxonomy Taxonomy slug.
     * @return void
     */
    private static function sync_field_from_terms($post_id, $taxonomy)
    {
        $post_id = (int) $post_id;
        $taxonomy = (string) $taxonomy;
        $meta_key = self::get_synced_fields()[$taxonomy] ?? '';

        if ($post_id < 1 || '' === $meta_key || isset(self::$processing_posts[$post_id][$taxonomy])) {
            return;
        }

        if (!isset(self::$processing_posts[$post_id])) {
            self::$processing_posts[$post_id] = array();
        }
        self::$processing_posts[$post_id][$taxonomy] = true;

        try {
            $terms = wp_get_post_terms(
                $post_id,
                $taxonomy,
                array(
                    'orderby' => 'term_id',
                    'order'   => 'ASC',
                )
            );

            if (is_wp_error($terms) || empty($terms) || !isset($terms[0]->name)) {
                if ('' !== (string) get_post_meta($post_id, $meta_key, true)) {
                    delete_post_meta($post_id, $meta_key);
                }
                return;
            }

            $canonical_name = sanitize_text_field((string) $terms[0]->name);
            if ((string) get_post_meta($post_id, $meta_key, true) !== $canonical_name) {
                update_post_meta($post_id, $meta_key, $canonical_name);
            }
        } finally {
            unset(self::$processing_posts[$post_id][$taxonomy]);
            if (empty(self::$processing_posts[$post_id])) {
                unset(self::$processing_posts[$post_id]);
            }
        }
    }

    /**
     * Make one taxonomy assignment match the stored legacy meta value.
     *
     * @param int    $post_id Post ID.
     * @param string $taxonomy Taxonomy slug.
     * @return void
     */
    private static function sync_field_from_meta($post_id, $taxonomy)
    {
        $post_id = (int) $post_id;
        $taxonomy = (string) $taxonomy;
        $meta_key = self::get_synced_fields()[$taxonomy] ?? '';

        if ($post_id < 1 || '' === $meta_key || isset(self::$processing_posts[$post_id][$taxonomy])) {
            return;
        }

        $field_value = sanitize_text_field((string) get_post_meta($post_id, $meta_key, true));
        if ('' === $field_value) {
            self::sync_field_from_terms($post_id, $taxonomy);
            return;
        }

        if (!isset(self::$processing_posts[$post_id])) {
            self::$processing_posts[$post_id] = array();
        }
        self::$processing_posts[$post_id][$taxonomy] = true;

        try {
            $term = get_term_by('name', $field_value, $taxonomy);
            if (!($term instanceof WP_Term)) {
                $term = get_term_by('slug', sanitize_title($field_value), $taxonomy);
            }

            if (!($term instanceof WP_Term)) {
                return;
            }

            $target_term_id = (int) $term->term_id;
            $current_term_ids = wp_get_post_terms(
                $post_id,
                $taxonomy,
                array(
                    'fields' => 'ids',
                )
            );
            $current_term_ids = is_wp_error($current_term_ids) ? array() : array_values(array_filter(array_map('intval', (array) $current_term_ids)));

            if (array($target_term_id) !== $current_term_ids) {
                wp_set_object_terms($post_id, array($target_term_id), $taxonomy, false);
            }

            $canonical_name = sanitize_text_field((string) $term->name);
            if ((string) get_post_meta($post_id, $meta_key, true) !== $canonical_name) {
                update_post_meta($post_id, $meta_key, $canonical_name);
            }
        } finally {
            unset(self::$processing_posts[$post_id][$taxonomy]);
            if (empty(self::$processing_posts[$post_id])) {
                unset(self::$processing_posts[$post_id]);
            }
        }
    }

    /**
     * Whether this post is eligible for synchronization.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private static function should_process_post($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return false;
        }

        return self::POST_TYPE === get_post_type($post_id);
    }
}

Bornado_Ad_Taxonomy_Meta_Sync::init();
