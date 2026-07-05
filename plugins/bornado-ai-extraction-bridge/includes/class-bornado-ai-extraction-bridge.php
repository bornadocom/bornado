<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Bornado_AI_Extraction_Bridge')) {
    final class Bornado_AI_Extraction_Bridge
    {
        /**
         * @var Bornado_AI_Extraction_Bridge|null
         */
        private static $instance = null;

        public static function instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct() {
            add_action('rest_api_init', array($this, 'register_routes'));

            if (is_admin()) {
                add_action('admin_menu', array($this, 'register_admin_page'));
                add_action('created_sb_dynamic_form_templates', array($this, 'handle_template_term_saved'), 10, 1);
                add_action('edited_sb_dynamic_form_templates', array($this, 'handle_template_term_saved'), 10, 1);
                add_action('admin_notices', array($this, 'render_template_validation_notice'));
            }
        }

        public function register_routes() {
            register_rest_route(
                'bornado-ai-bridge/v1',
                '/catalog',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'handle_catalog_request'),
                    'permission_callback' => array($this, 'can_access_service_route'),
                    'args'                => array(
                        'market' => array(
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                        'channel' => array(
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                    ),
                )
            );

            register_rest_route(
                'bornado-ai-bridge/v1',
                '/health',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'handle_health_request'),
                    'permission_callback' => array($this, 'can_access_service_route'),
                )
            );

            register_rest_route(
                'bornado-ai-bridge/v1',
                '/geo-city-lookup',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'handle_geo_city_lookup_request'),
                    'permission_callback' => array($this, 'can_access_service_route'),
                    'args'                => array(
                        'country_key' => array(
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'country_iso2' => array(
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'city_key' => array(
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'query' => array(
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'geoname_id' => array(
                            'type' => 'integer',
                            'required' => false,
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                )
            );

            register_rest_route(
                'bornado-ai-bridge/v1',
                '/ingest',
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'handle_ingest_request'),
                    'permission_callback' => array($this, 'can_access_service_route'),
                )
            );
        }

        public function can_access_service_route($request = null) {
            if (current_user_can('manage_options')) {
                return true;
            }

            if (
                $request instanceof WP_REST_Request &&
                WP_REST_Server::READABLE === $request->get_method() &&
                get_current_user_id() > 0
            ) {
                return true;
            }

            $expected = $this->get_service_key();
            $provided = trim((string) ($_SERVER['HTTP_X_BORNADO_SERVICE_KEY'] ?? ''));

            if ('' !== $expected && '' !== $provided && hash_equals($expected, $provided)) {
                return true;
            }

            $allow_query_key = (bool) apply_filters('bornado_ai_extraction_bridge_allow_query_key_auth', false);
            if (!$allow_query_key) {
                return false;
            }

            $provided_query = trim((string) ($_GET['key'] ?? ''));

            return '' !== $expected && '' !== $provided_query && hash_equals($expected, $provided_query);
        }

        public function handle_health_request(WP_REST_Request $request) {
            unset($request);

            return rest_ensure_response(
                array(
                    'service' => 'bornado-ai-extraction-bridge',
                    'status'  => 'ok',
                    'time'    => gmdate('c'),
                )
            );
        }

        public function handle_geo_city_lookup_request(WP_REST_Request $request) {
            $country_iso2 = $this->resolve_geo_lookup_country_iso2(
                (string) $request->get_param('country_iso2'),
                (string) $request->get_param('country_key')
            );
            $geoname_id = absint($request->get_param('geoname_id'));
            $city_key = sanitize_text_field((string) $request->get_param('city_key'));
            $query = sanitize_text_field((string) $request->get_param('query'));
            $lookup_query = '' !== trim($city_key) ? $city_key : $query;

            if ('' === $country_iso2) {
                return new WP_REST_Response(
                    array(
                        'resolved' => false,
                        'ambiguous' => false,
                        'message' => 'Country is required for geo city lookup.',
                    ),
                    422
                );
            }

            if ($geoname_id < 1 && '' === trim($lookup_query)) {
                return new WP_REST_Response(
                    array(
                        'resolved' => false,
                        'ambiguous' => false,
                        'message' => 'Either geoname_id or city query is required.',
                    ),
                    400
                );
            }

            $result = $this->lookup_geo_catalog_city($country_iso2, $lookup_query, $geoname_id);

            return rest_ensure_response($result);
        }

        public function handle_catalog_request(WP_REST_Request $request) {
            $market  = sanitize_key((string) $request->get_param('market'));
            $channel = sanitize_key((string) $request->get_param('channel'));
            $channel = '' !== $channel ? $channel : 'instagram';

            $root_country    = $this->resolve_root_country($market);
            if ('' !== $market && !$root_country instanceof WP_Term) {
                return new WP_REST_Response(
                    array(
                        'message' => 'Unknown market.',
                        'market' => $market,
                    ),
                    422
                );
            }
            $root_country_id = $root_country instanceof WP_Term ? (int) $root_country->term_id : 0;
            $templates       = $this->get_template_index();

            return rest_ensure_response(
                array(
                    'source' => array(
                        'mode' => 'wordpress-bridge',
                        'bridge' => 'bornado-ai-bridge/v1',
                    ),
                    'market' => array(
                        'requested_key' => $market,
                        'channel' => $channel,
                    ),
                    'categories' => $this->get_category_terms($templates),
                    'templates' => array_values($templates),
                    'locations' => array(
                        'country' => $root_country instanceof WP_Term ? $this->map_root_country($root_country) : array(),
                        'cities' => $root_country_id > 0 ? $this->get_city_terms($root_country_id) : array(),
                    ),
                    'enums' => array(
                        'ad_type' => $this->get_taxonomy_terms('ad_type'),
                        'ad_condition' => $this->get_taxonomy_terms('ad_condition'),
                        'ad_warranty' => $this->get_taxonomy_terms('ad_warranty'),
                        'ad_currency' => $this->get_taxonomy_terms('ad_currency'),
                    ),
                    'root_country' => $root_country instanceof WP_Term ? $this->map_root_country($root_country) : array(),
                )
            );
        }

        public function handle_ingest_request(WP_REST_Request $request) {
            $payload = $request->get_json_params();
            if (!is_array($payload)) {
                return new WP_REST_Response(
                    array(
                        'message' => 'Invalid JSON payload.',
                    ),
                    400
                );
            }

            $record = array();
            if (isset($payload['record']) && is_array($payload['record'])) {
                $record = $payload['record'];
            } elseif (isset($payload['target_payload']['wordpress_bridge']) && is_array($payload['target_payload']['wordpress_bridge'])) {
                $record = $payload['target_payload']['wordpress_bridge'];
            } elseif (isset($payload['target_payload']['bridge']) && is_array($payload['target_payload']['bridge'])) {
                $record = $payload['target_payload']['bridge'];
            } else {
                $record = $payload;
            }

            $status = sanitize_key((string) ($record['status'] ?? 'pending'));
            if (in_array($status, $this->get_skip_statuses(), true)) {
                return rest_ensure_response(
                    array(
                        'ingest_status' => 'skipped',
                        'reason' => 'Configured to skip this moderation status.',
                        'moderation_status' => $status,
                    )
                );
            }

            $post = isset($record['post']) && is_array($record['post']) ? $record['post'] : array();
            $title = sanitize_text_field((string) ($post['title'] ?? ''));
            if ('' === $title) {
                return new WP_REST_Response(
                    array(
                        'message' => 'Post title is required.',
                    ),
                    400
                );
            }

            $post_id = (int) ($post['id'] ?? 0);
            $post_status = $this->map_post_status($status);
            $post_args = array(
                'post_title'   => $title,
                'post_content' => wp_kses_post((string) ($post['content'] ?? '')),
                'post_name'    => sanitize_text_field((string) ($post['slug'] ?? $title)),
                'post_status'  => $post_status,
                'post_type'    => 'ad_post',
                'post_author'  => (int) ($post['author_id'] ?? $this->get_default_author_id()),
            );

            if ($post_id > 0) {
                $post_args['ID'] = $post_id;
                $post_result = wp_update_post(wp_slash($post_args), true);
            } else {
                $post_result = wp_insert_post(wp_slash($post_args), true);
            }

            if (is_wp_error($post_result) || (int) $post_result < 1) {
                return new WP_REST_Response(
                    array(
                        'message' => 'Failed to save ad post.',
                        'error' => is_wp_error($post_result) ? $post_result->get_error_message() : 'Unknown error',
                    ),
                    500
                );
            }

            $post_id = (int) $post_result;

            $taxonomies = isset($record['taxonomies']) && is_array($record['taxonomies']) ? $record['taxonomies'] : array();
            $meta = isset($record['meta']) && is_array($record['meta']) ? $record['meta'] : array();
            $dynamic_meta = isset($record['dynamic_meta']) && is_array($record['dynamic_meta']) ? $record['dynamic_meta'] : array();
            $flags = isset($record['flags']) && is_array($record['flags']) ? $record['flags'] : array();
            $geo_location = isset($record['geo_location']) && is_array($record['geo_location']) ? $record['geo_location'] : array();

            if (!empty($geo_location)) {
                $taxonomies = $this->apply_geo_location_taxonomy_fallback($taxonomies, $geo_location);
            }

            $this->save_taxonomy_terms($post_id, $taxonomies);
            $this->save_post_meta_map($post_id, $meta);

            if (!empty($flags['clear_dynamic_meta'])) {
                $this->clear_dynamic_template_meta($post_id);
            }

            $this->save_post_meta_map($post_id, $dynamic_meta);

            update_post_meta($post_id, '_adforest_ad_status_', 'approved' === $status ? 'active' : $status);

            return rest_ensure_response(
                array(
                    'ingest_status' => 'saved',
                    'moderation_status' => $status,
                    'post' => array(
                        'id' => $post_id,
                        'status' => get_post_status($post_id),
                        'edit_link' => get_edit_post_link($post_id, ''),
                        'view_link' => get_permalink($post_id),
                    ),
                )
            );
        }

        public function register_admin_page() {
            add_management_page(
                'Bornado AI Extraction Bridge',
                'Bornado AI Bridge',
                'manage_options',
                'bornado-ai-extraction-bridge',
                array($this, 'render_admin_page')
            );
        }

        public function render_admin_page() {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have permission to access this page.', 'bornado-ai-extraction-bridge'));
            }

            $service_health = array();
            $service_schema = array();
            $template_validation = array();

            if (
                'POST' === strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
                && isset($_POST['bornado_ai_bridge_action'])
            ) {
                check_admin_referer('bornado_ai_extraction_bridge_admin');

                $action = sanitize_key((string) wp_unslash($_POST['bornado_ai_bridge_action']));
                if ('test_service_health' === $action) {
                    $service_health = $this->request_remote_service('/health', false);
                } elseif ('test_service_schema' === $action) {
                    $service_schema = $this->request_remote_service('/schema?channel=instagram', true);
                } elseif ('validate_template_fields' === $action) {
                    $template_validation = $this->validate_all_template_fields();
                }
            }

            $catalog_url = rest_url('bornado-ai-bridge/v1/catalog');
            $health_url  = rest_url('bornado-ai-bridge/v1/health');
            $ingest_url  = rest_url('bornado-ai-bridge/v1/ingest');

            echo '<div class="wrap">';
            echo '<h1>Bornado AI Extraction Bridge</h1>';
            echo '<p>This plugin exposes a curated WordPress adapter for the independent AI extraction service. Core prompt, validation, and ingest orchestration remain outside WordPress.</p>';

            echo '<h2>Endpoints</h2>';
            echo '<table class="widefat striped" style="max-width:1000px;"><tbody>';
            echo '<tr><td>Catalog endpoint</td><td><code>' . esc_html($catalog_url) . '</code></td></tr>';
            echo '<tr><td>Health endpoint</td><td><code>' . esc_html($health_url) . '</code></td></tr>';
            echo '<tr><td>Ingest endpoint</td><td><code>' . esc_html($ingest_url) . '</code></td></tr>';
            echo '<tr><td>Remote service base URL</td><td><code>' . esc_html($this->get_service_base_url()) . '</code></td></tr>';
            echo '<tr><td>Remote service key</td><td>' . esc_html('' !== $this->get_service_key() ? 'Configured' : 'Missing') . '</td></tr>';
            echo '</tbody></table>';

            echo '<h2 style="margin-top:24px;">Remote Service Tests</h2>';
            echo '<form method="post" style="display:inline-block;margin-right:12px;">';
            wp_nonce_field('bornado_ai_extraction_bridge_admin');
            echo '<input type="hidden" name="bornado_ai_bridge_action" value="test_service_health" />';
            submit_button('Test service health', 'secondary', 'submit', false);
            echo '</form>';

            echo '<form method="post" style="display:inline-block;">';
            wp_nonce_field('bornado_ai_extraction_bridge_admin');
            echo '<input type="hidden" name="bornado_ai_bridge_action" value="test_service_schema" />';
            submit_button('Test service schema', 'primary', 'submit', false);
            echo '</form>';

            echo '<h2 style="margin-top:24px;">Template Field Validation</h2>';
            echo '<p>Validate category template fields before running Make. This checks supported types, slug conflicts, duplicate slugs, and malformed option values.</p>';
            echo '<form method="post" style="display:inline-block;">';
            wp_nonce_field('bornado_ai_extraction_bridge_admin');
            echo '<input type="hidden" name="bornado_ai_bridge_action" value="validate_template_fields" />';
            submit_button('Validate template fields', 'secondary', 'submit', false);
            echo '</form>';

            if (!empty($service_health)) {
                echo '<h2>Health Result</h2>';
                echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:1000px;overflow:auto;">' . esc_html(wp_json_encode($service_health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';
            }

            if (!empty($service_schema)) {
                echo '<h2>Schema Result</h2>';
                echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:1000px;overflow:auto;">' . esc_html(wp_json_encode($service_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';
            }

            if (!empty($template_validation)) {
                echo '<h2>Validation Result</h2>';
                echo '<p><strong>Templates checked:</strong> ' . esc_html((string) ($template_validation['templates_count'] ?? 0)) . ' | ';
                echo '<strong>Templates with issues:</strong> ' . esc_html((string) ($template_validation['templates_with_issues_count'] ?? 0)) . ' | ';
                echo '<strong>Errors:</strong> ' . esc_html((string) ($template_validation['error_count'] ?? 0)) . ' | ';
                echo '<strong>Warnings:</strong> ' . esc_html((string) ($template_validation['warning_count'] ?? 0)) . '</p>';

                if (empty($template_validation['templates'])) {
                    echo '<p>No templates were found.</p>';
                } else {
                    echo '<table class="widefat striped" style="max-width:1000px;margin:12px 0 18px;"><thead><tr>';
                    echo '<th>Template</th><th>Status</th><th>Errors</th><th>Warnings</th>';
                    echo '</tr></thead><tbody>';

                    foreach ((array) $template_validation['templates'] as $template_result) {
                        if (!is_array($template_result)) {
                            continue;
                        }

                        $errors = isset($template_result['errors']) && is_array($template_result['errors']) ? $template_result['errors'] : array();
                        $warnings = isset($template_result['warnings']) && is_array($template_result['warnings']) ? $template_result['warnings'] : array();
                        $status = $this->get_template_validation_status($template_result);

                        echo '<tr>';
                        echo '<td><strong>' . esc_html((string) ($template_result['template_label'] ?? 'Template')) . '</strong><br><code>' . esc_html((string) ($template_result['template_slug'] ?? '')) . '</code></td>';
                        echo '<td>' . $this->render_validation_status_badge($status) . '</td>';
                        echo '<td>' . esc_html((string) count($errors)) . '</td>';
                        echo '<td>' . esc_html((string) count($warnings)) . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';

                    foreach ((array) $template_validation['templates'] as $template_result) {
                        if (!is_array($template_result)) {
                            continue;
                        }

                        $errors = isset($template_result['errors']) && is_array($template_result['errors']) ? $template_result['errors'] : array();
                        $warnings = isset($template_result['warnings']) && is_array($template_result['warnings']) ? $template_result['warnings'] : array();
                        if (empty($errors) && empty($warnings)) {
                            continue;
                        }

                        echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:12px;margin:12px 0;max-width:1000px;">';
                        echo '<h3 style="margin-top:0;">' . esc_html((string) ($template_result['template_label'] ?? 'Template')) . ' <code>' . esc_html((string) ($template_result['template_slug'] ?? '')) . '</code></h3>';

                        if (!empty($errors)) {
                            echo '<p><strong>Errors</strong></p><ul style="list-style:disc;padding-left:20px;">';
                            foreach ($errors as $message) {
                                echo '<li>' . esc_html((string) $message) . '</li>';
                            }
                            echo '</ul>';
                        }

                        if (!empty($warnings)) {
                            echo '<p><strong>Warnings</strong></p><ul style="list-style:disc;padding-left:20px;">';
                            foreach ($warnings as $message) {
                                echo '<li>' . esc_html((string) $message) . '</li>';
                            }
                            echo '</ul>';
                        }

                        echo '</div>';
                    }

                    if (0 === (int) ($template_validation['templates_with_issues_count'] ?? 0)) {
                        echo '<p>All checked templates passed validation.</p>';
                    }
                }
            }

            echo '</div>';
        }

        public function handle_template_term_saved($term_id) {
            $term_id = (int) $term_id;
            if ($term_id < 1 || !current_user_can('manage_options')) {
                return;
            }

            set_transient($this->get_template_validation_notice_key(), $this->validate_template_fields($term_id), 300);
        }

        public function render_template_validation_notice() {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (function_exists('get_current_screen')) {
                $screen = get_current_screen();
                $screen_taxonomy = $screen ? (string) ($screen->taxonomy ?? '') : '';
                $screen_id = $screen ? (string) ($screen->id ?? '') : '';
                if (
                    $screen
                    && 'sb_dynamic_form_templates' !== $screen_taxonomy
                    && 'tools_page_bornado-ai-extraction-bridge' !== $screen_id
                ) {
                    return;
                }
            }

            $result = get_transient($this->get_template_validation_notice_key());
            if (!is_array($result) || empty($result)) {
                return;
            }

            delete_transient($this->get_template_validation_notice_key());

            $errors = isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : array();
            $warnings = isset($result['warnings']) && is_array($result['warnings']) ? $result['warnings'] : array();

            $notice_class = 'notice-success';
            $headline = 'Template field validation passed.';

            if (!empty($errors)) {
                $notice_class = 'notice-error';
                $headline = 'Template field validation found errors.';
            } elseif (!empty($warnings)) {
                $notice_class = 'notice-warning';
                $headline = 'Template field validation found warnings.';
            }

            echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible">';
            echo '<p><strong>' . esc_html($headline) . '</strong> ';
            echo esc_html((string) ($result['template_label'] ?? 'Template'));
            echo ' <code>' . esc_html((string) ($result['template_slug'] ?? '')) . '</code></p>';

            $messages = array_merge($errors, $warnings);
            if (!empty($messages)) {
                echo '<ul style="list-style:disc;padding-left:20px;">';
                foreach (array_slice($messages, 0, 8) as $message) {
                    echo '<li>' . esc_html((string) $message) . '</li>';
                }
                if (count($messages) > 8) {
                    echo '<li>' . esc_html(sprintf('%d more issue(s) not shown here.', count($messages) - 8)) . '</li>';
                }
                echo '</ul>';
            }

            echo '</div>';
        }

        /**
         * @return array<string,mixed>
         */
        private function request_remote_service($path, $with_service_key) {
            $base_url = $this->get_service_base_url();
            if ('' === $base_url) {
                return array(
                    'ok' => false,
                    'message' => 'Remote service base URL is missing.',
                );
            }

            $url = $this->build_service_url($base_url, $path);
            $args = array(
                'timeout' => 12,
                'headers' => array(),
            );

            if ($with_service_key && '' !== $this->get_service_key()) {
                $args['headers']['X-Bornado-Service-Key'] = $this->get_service_key();
            }

            $response = wp_remote_get($url, $args);
            if (is_wp_error($response)) {
                return array(
                    'ok' => false,
                    'message' => $response->get_error_message(),
                );
            }

            return array(
                'ok' => wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300,
                'statusCode' => (int) wp_remote_retrieve_response_code($response),
                'body' => json_decode((string) wp_remote_retrieve_body($response), true),
            );
        }

        private function build_service_url($base_url, $route) {
            $base_url = untrailingslashit((string) $base_url);
            $route = (string) $route;
            $route_path = $route;
            $route_query = '';

            if (false !== strpos($route, '?')) {
                $parts = explode('?', $route, 2);
                $route_path = (string) ($parts[0] ?? '');
                $route_query = (string) ($parts[1] ?? '');
            }

            $route_path = '/' . ltrim((string) $route_path, '/');
            $separator = false === strpos($base_url, '?') ? '?' : '&';
            $query_args = array(
                'route' => $route_path,
            );

            if ('' !== $route_query) {
                parse_str($route_query, $extra_args);
                if (is_array($extra_args)) {
                    $query_args = array_merge($query_args, $extra_args);
                }
            }

            return $base_url . $separator . http_build_query($query_args);
        }

        private function get_service_base_url() {
            $default = defined('BORNADO_AI_EXTRACTION_SERVICE_BASE_URL') ? (string) BORNADO_AI_EXTRACTION_SERVICE_BASE_URL : '';

            return esc_url_raw((string) apply_filters('bornado_ai_extraction_service_base_url', $default));
        }

        private function get_service_key() {
            $default = defined('BORNADO_AI_EXTRACTION_SERVICE_KEY') ? (string) BORNADO_AI_EXTRACTION_SERVICE_KEY : '';

            return (string) apply_filters('bornado_ai_extraction_service_key', $default);
        }

        /**
         * @return array<string,mixed>
         */
        private function validate_all_template_fields() {
            $terms = get_terms(
                array(
                    'taxonomy'   => 'sb_dynamic_form_templates',
                    'hide_empty' => false,
                    'number'     => 0,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            if (is_wp_error($terms) || !is_array($terms)) {
                return array(
                    'templates_count' => 0,
                    'templates_with_issues_count' => 1,
                    'error_count' => 1,
                    'warning_count' => 0,
                    'templates' => array(
                        array(
                            'template_label' => 'Template registry',
                            'template_slug' => 'sb_dynamic_form_templates',
                            'errors' => array('Unable to load template taxonomy terms for validation.'),
                            'warnings' => array(),
                        ),
                    ),
                );
            }

            $results = array();
            $templates_with_issues = 0;
            $error_count = 0;
            $warning_count = 0;

            foreach ($terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                $result = $this->validate_template_fields((int) $term->term_id);
                $results[] = $result;

                $template_errors = isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : array();
                $template_warnings = isset($result['warnings']) && is_array($result['warnings']) ? $result['warnings'] : array();

                if (!empty($template_errors) || !empty($template_warnings)) {
                    $templates_with_issues++;
                }

                $error_count += count($template_errors);
                $warning_count += count($template_warnings);
            }

            return array(
                'templates_count' => count($results),
                'templates_with_issues_count' => $templates_with_issues,
                'error_count' => $error_count,
                'warning_count' => $warning_count,
                'templates' => $results,
            );
        }

        /**
         * @return array<string,mixed>
         */
        private function validate_template_fields($template_id) {
            $template_id = (int) $template_id;
            $template = get_term($template_id, 'sb_dynamic_form_templates');
            if (!$template instanceof WP_Term) {
                return array(
                    'template_id' => $template_id,
                    'template_label' => 'Unknown template',
                    'template_slug' => '',
                    'errors' => array('Template could not be loaded for validation.'),
                    'warnings' => array(),
                );
            }

            if (!function_exists('sb_dynamic_form_data')) {
                return array(
                    'template_id' => $template_id,
                    'template_label' => (string) $template->name,
                    'template_slug' => (string) $template->slug,
                    'errors' => array('Theme helper sb_dynamic_form_data() is unavailable, so dynamic template fields cannot be validated.'),
                    'warnings' => array(),
                );
            }

            $rows = $this->get_template_validation_rows($template_id);
            $errors = array();
            $warnings = array();
            $seen_active_slugs = array();
            $seen_any_slugs = array();
            $static_field_keys = array();

            foreach ($this->get_static_template_fields($template_id) as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $field_key = sanitize_key((string) ($field['field_key'] ?? ''));
                if ('' !== $field_key) {
                    $static_field_keys[$field_key] = true;
                }
            }

            foreach ($rows as $row_index => $row) {
                if (!is_array($row) || !$this->has_template_field_row_content($row)) {
                    continue;
                }

                $result = $this->validate_template_field_row(
                    $row,
                    (int) $row_index,
                    $static_field_keys,
                    $seen_active_slugs,
                    $seen_any_slugs
                );

                $errors = array_merge($errors, $result['errors']);
                $warnings = array_merge($warnings, $result['warnings']);
            }

            return array(
                'template_id' => $template_id,
                'template_label' => (string) $template->name,
                'template_slug' => (string) $template->slug,
                'errors' => array_values(array_unique($errors)),
                'warnings' => array_values(array_unique($warnings)),
            );
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_category_terms($templates) {
            $terms = get_terms(
                array(
                    'taxonomy'   => 'ad_cats',
                    'hide_empty' => false,
                    'number'     => 0,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            if (is_wp_error($terms) || !is_array($terms)) {
                return array();
            }

            $mapped = array();
            foreach ($terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                $template_id = $this->resolve_template_term_id((int) $term->term_id);
                $template = ($template_id > 0 && isset($templates[$template_id])) ? $templates[$template_id] : null;

                $mapped[] = array(
                    'key' => $this->canonical_key((string) $term->slug),
                    'label' => (string) $term->name,
                    'slug' => (string) $term->slug,
                    'term_id' => (int) $term->term_id,
                    'parent_term_id' => (int) $term->parent,
                    'template_term_id' => $template_id,
                    'template_key' => is_array($template) ? (string) ($template['key'] ?? '') : '',
                    'template_label' => is_array($template) ? (string) ($template['label'] ?? '') : '',
                    'ai_fields_count' => is_array($template) ? count((array) ($template['ai_fields'] ?? array())) : 0,
                );
            }

            return $mapped;
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_taxonomy_terms($taxonomy) {
            $terms = get_terms(
                array(
                    'taxonomy'   => sanitize_key((string) $taxonomy),
                    'hide_empty' => false,
                    'number'     => 0,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            if (is_wp_error($terms) || !is_array($terms)) {
                return array();
            }

            return $this->map_terms($terms);
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_city_terms($root_country_id) {
            $root_country_id = (int) $root_country_id;
            if ($root_country_id < 1) {
                return array();
            }
            $root_country_key = $this->resolve_country_key_for_term($root_country_id);

            $aliases_by_key = apply_filters(
                'bornado_ai_extraction_bridge_location_aliases',
                array(
                    'london' => array('wembley', 'croydon', 'ealing', 'harrow'),
                    'manchester' => array('salford', 'stockport', 'bolton'),
                    'birmingham' => array('solihull'),
                    'liverpool' => array('bootle'),
                    'leeds' => array('bradford'),
                    'newcastle' => array('gateshead'),
                )
            );

            if (class_exists('Bornado_Location_Picker_Service') && method_exists('Bornado_Location_Picker_Service', 'get_city_options')) {
                $items = Bornado_Location_Picker_Service::get_city_options($root_country_id, false);
                $cities = array();

                foreach ((array) $items as $item) {
                    if (!is_array($item) || empty($item['id'])) {
                        continue;
                    }

                    $term_id = (int) ($item['id'] ?? 0);
                    $key = $this->canonical_key((string) ($item['slug'] ?? $item['label'] ?? ''));
                    $cities[] = array(
                        'key' => $key,
                        'label' => (string) ($item['label'] ?? ''),
                        'slug' => (string) ($item['slug'] ?? ''),
                        'term_id' => $term_id,
                        'geoname_id' => (int) ($item['geoname_id'] ?? get_term_meta($term_id, '_bornado_geo_source_id', true)),
                        'country_key' => $root_country_key,
                        'aliases' => array_values(array_unique(array_map('strval', (array) ($aliases_by_key[$key] ?? array())))),
                        'is_default' => false,
                    );
                }

                return $cities;
            }

            $terms = get_terms(
                array(
                    'taxonomy'   => 'ad_country',
                    'hide_empty' => false,
                    'number'     => 0,
                    'parent'     => $root_country_id,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            if (is_wp_error($terms) || !is_array($terms)) {
                return array();
            }

            $cities = array();
            foreach ($terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                $key = $this->canonical_key((string) $term->slug);
                $cities[] = array(
                    'key' => $key,
                    'label' => (string) $term->name,
                    'slug' => (string) $term->slug,
                    'term_id' => (int) $term->term_id,
                    'geoname_id' => (int) get_term_meta($term->term_id, '_bornado_geo_source_id', true),
                    'country_key' => $root_country_key,
                    'aliases' => array_values(array_unique(array_map('strval', (array) ($aliases_by_key[$key] ?? array())))),
                    'is_default' => false,
                );
            }

            return $cities;
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function map_terms($terms) {
            $mapped = array();

            foreach ($terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                $mapped[] = array(
                    'key' => $this->canonical_key((string) $term->slug),
                    'label' => (string) $term->name,
                    'slug' => (string) $term->slug,
                    'term_id' => (int) $term->term_id,
                    'parent_term_id' => (int) $term->parent,
                );
            }

            return $mapped;
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_template_index() {
            $terms = get_terms(
                array(
                    'taxonomy'   => 'sb_dynamic_form_templates',
                    'hide_empty' => false,
                    'number'     => 0,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            if (is_wp_error($terms) || !is_array($terms)) {
                return array();
            }

            $templates = array();
            foreach ($terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                $descriptor = $this->build_template_descriptor($term);
                $templates[(int) $term->term_id] = $descriptor;
            }

            return $templates;
        }

        /**
         * @return array<string,mixed>
         */
        private function build_template_descriptor($term) {
            $template_id = (int) $term->term_id;
            $template_key = $this->canonical_key((string) $term->slug);
            $dynamic_fields = $this->get_dynamic_template_fields($template_id);
            $static_fields = $this->get_static_template_fields($template_id);
            $all_fields = array_values($this->merge_fields_by_key($static_fields, $dynamic_fields));

            return array(
                'term_id' => $template_id,
                'key' => $template_key,
                'label' => (string) $term->name,
                'slug' => (string) $term->slug,
                'dynamic_fields' => $dynamic_fields,
                'static_fields' => $static_fields,
                'all_fields' => $all_fields,
                'ai_fields' => array_values(
                    array_filter(
                        $all_fields,
                        static function ($field) {
                            return is_array($field) && !empty($field['ai_exposed']);
                        }
                    )
                ),
            );
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_dynamic_template_fields($template_id) {
            $template_id = (int) $template_id;
            if ($template_id < 1 || !function_exists('sb_dynamic_form_data')) {
                return array();
            }

            $encoded = (string) get_term_meta($template_id, '_sb_dynamic_form_fields', true);
            if ('' === $encoded) {
                return array();
            }

            $rows = sb_dynamic_form_data($encoded);
            if (!is_array($rows)) {
                return array();
            }

            $fields = array();
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ((string) ($row['status'] ?? '') !== '1') {
                    continue;
                }

                $slug = sanitize_key((string) ($row['slugs'] ?? ''));
                if ('' === $slug) {
                    continue;
                }

                $fields[] = $this->normalize_dynamic_field_descriptor($row);
            }

            return array_values(array_filter($fields));
        }

        /**
         * @return array<string,mixed>
         */
        private function normalize_dynamic_field_descriptor($row) {
            $slug = sanitize_key((string) ($row['slugs'] ?? ''));
            $type_code = (string) ($row['types'] ?? '');
            $values = (string) ($row['values'] ?? '');
            $field_type = $this->map_dynamic_field_type($type_code);
            $multiple = in_array($type_code, array('3', '9'), true);

            $descriptor = array(
                'field_key' => $slug,
                'label_fa' => (string) ($row['titles'] ?? $slug),
                'type' => $field_type,
                'type_code' => $type_code,
                'required' => (string) ($row['requires'] ?? '') === '1',
                'active' => (string) ($row['status'] ?? '') === '1',
                'multiple' => $multiple,
                'choices' => array(),
                'rules' => array(),
                'storage' => array(
                    'kind' => 'post_meta',
                    'key' => '_adforest_tpl_field_' . $slug,
                ),
                'source' => 'category-template-dynamic',
                'ai_exposed' => true,
            );

            if (in_array($type_code, array('2', '3', '8', '9'), true)) {
                $descriptor['choices'] = $this->build_pipe_choices($values);
            } elseif ('7' === $type_code) {
                $descriptor['choices'] = $this->build_color_choices($values);
            } elseif ('6' === $type_code) {
                $descriptor['rules'] = $this->parse_number_range_rules($values);
            }

            return $descriptor;
        }

        private function map_dynamic_field_type($type_code) {
            $map = array(
                '1' => 'text',
                '2' => 'select',
                '3' => 'checkbox',
                '4' => 'date',
                '5' => 'url',
                '6' => 'number',
                '7' => 'color',
                '8' => 'radio',
                '9' => 'checkbox',
            );

            return isset($map[$type_code]) ? $map[$type_code] : 'text';
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_static_template_fields($template_id) {
            $fields = array();

            $fields[] = $this->maybe_build_static_field(
                $template_id,
                'ad_type',
                __('Type of Ad', 'adforest'),
                'taxonomy_select',
                '_sb_default_cat_ad_type_show',
                '_sb_default_cat_ad_type_required',
                $this->get_taxonomy_choices('ad_type'),
                array(
                    'kind' => 'taxonomy_meta',
                    'taxonomy' => 'ad_type',
                    'meta_key' => '_adforest_ad_type',
                )
            );

            $fields[] = $this->maybe_build_static_field(
                $template_id,
                'price_type',
                __('Price Type', 'adforest'),
                'select',
                '_sb_default_cat_price_type_show',
                '_sb_default_cat_price_type_required',
                $this->get_theme_price_type_choices(),
                array(
                    'kind' => 'post_meta',
                    'key' => '_adforest_ad_price_type',
                )
            );

            $fields[] = $this->maybe_build_static_field(
                $template_id,
                'currency',
                __('Currency', 'adforest'),
                'taxonomy_select',
                '_sb_default_cat_price_show',
                '_sb_default_cat_price_required',
                $this->get_taxonomy_choices('ad_currency'),
                array(
                    'kind' => 'taxonomy_meta',
                    'taxonomy' => 'ad_currency',
                    'meta_key' => '_adforest_ad_currency',
                )
            );

            $fields[] = $this->maybe_build_static_field(
                $template_id,
                'price',
                __('Price', 'adforest'),
                'number',
                '_sb_default_cat_price_show',
                '_sb_default_cat_price_required',
                array(),
                array(
                    'kind' => 'post_meta',
                    'key' => '_adforest_ad_price',
                )
            );

            $fields[] = $this->maybe_build_static_field(
                $template_id,
                'ad_condition',
                __('Item Condition', 'adforest'),
                'taxonomy_select',
                '_sb_default_cat_condition_show',
                '_sb_default_cat_condition_required',
                $this->get_taxonomy_choices('ad_condition'),
                array(
                    'kind' => 'taxonomy_meta',
                    'taxonomy' => 'ad_condition',
                    'meta_key' => '_adforest_ad_condition',
                )
            );

            $fields[] = $this->maybe_build_static_field(
                $template_id,
                'ad_warranty',
                __('Item Warranty', 'adforest'),
                'taxonomy_select',
                '_sb_default_cat_warranty_show',
                '_sb_default_cat_warranty_required',
                $this->get_taxonomy_choices('ad_warranty'),
                array(
                    'kind' => 'taxonomy_meta',
                    'taxonomy' => 'ad_warranty',
                    'meta_key' => '_adforest_ad_warranty',
                )
            );

            return array_values(array_filter($fields));
        }

        /**
         * @return array<string,mixed>|null
         */
        private function maybe_build_static_field($template_id, $field_key, $label, $type, $show_key, $required_key, $choices, $storage) {
            $switch = $this->get_template_switch($template_id, $show_key, $required_key);
            if (empty($switch['show'])) {
                return null;
            }

            if (in_array($type, array('select', 'taxonomy_select'), true) && empty($choices)) {
                return null;
            }

            return array(
                'field_key' => $field_key,
                'label_fa' => (string) $label,
                'type' => $type,
                'type_code' => 'static',
                'required' => !empty($switch['required']),
                'active' => true,
                'multiple' => false,
                'choices' => $choices,
                'rules' => array(),
                'storage' => $storage,
                'source' => 'category-template-static',
                'ai_exposed' => !in_array($field_key, array('dropzone', 'ad_yvideo'), true),
            );
        }

        /**
         * @return array{show:bool,required:bool}
         */
        private function get_template_switch($template_id, $show_key, $required_key) {
            $template_id = (int) $template_id;
            $encoded = (string) get_term_meta($template_id, '_sb_dynamic_form_fields', true);
            $show = true;
            $required = false;

            if ('' !== $encoded && function_exists('sb_custom_form_data')) {
                $show = '0' !== (string) sb_custom_form_data($encoded, $show_key);
                $required = '1' === (string) sb_custom_form_data($encoded, $required_key);
            }

            return array(
                'show' => $show,
                'required' => $required,
            );
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_taxonomy_choices($taxonomy) {
            $choices = array();
            foreach ($this->get_taxonomy_terms($taxonomy) as $term) {
                if (!is_array($term)) {
                    continue;
                }

                $choices[] = array(
                    'key' => (string) ($term['key'] ?? ''),
                    'label' => (string) ($term['label'] ?? ''),
                    'stored_value' => (string) ($term['label'] ?? ''),
                    'term_id' => (int) ($term['term_id'] ?? 0),
                );
            }

            return $choices;
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_theme_price_type_choices() {
            global $adforest_theme;

            $labels = array(
                'Fixed' => __('Fixed', 'adforest'),
                'Negotiable' => __('Negotiable', 'adforest'),
                'on_call' => __('Price on call', 'adforest'),
                'auction' => __('Auction', 'adforest'),
                'free' => __('Free', 'adforest'),
                'no_price' => __('No price', 'adforest'),
            );

            if (isset($adforest_theme['sb_price_types']) && is_array($adforest_theme['sb_price_types']) && count($adforest_theme['sb_price_types']) > 0) {
                $raw_types = $adforest_theme['sb_price_types'];
            } elseif (isset($adforest_theme['sb_price_types_more']) && '' === (string) $adforest_theme['sb_price_types_more']) {
                $raw_types = array('Fixed', 'Negotiable', 'on_call', 'auction', 'free', 'no_price');
            } else {
                $raw_types = array();
            }

            $choices = array();
            foreach ((array) $raw_types as $raw_type) {
                $raw_type = (string) $raw_type;
                if ('' === $raw_type) {
                    continue;
                }

                $choices[] = array(
                    'key' => $this->make_choice_key($raw_type, isset($labels[$raw_type]) ? (string) $labels[$raw_type] : $raw_type),
                    'label' => isset($labels[$raw_type]) ? (string) $labels[$raw_type] : $raw_type,
                    'stored_value' => $raw_type,
                );
            }

            if (isset($adforest_theme['sb_price_types_more']) && '' !== (string) $adforest_theme['sb_price_types_more']) {
                $extra_types = explode('|', (string) $adforest_theme['sb_price_types_more']);
                foreach ($extra_types as $raw_type) {
                    $raw_type = trim((string) $raw_type);
                    if ('' === $raw_type) {
                        continue;
                    }

                    $choices[] = array(
                        'key' => $this->make_choice_key(str_replace(' ', '_', $raw_type), $raw_type),
                        'label' => $raw_type,
                        'stored_value' => str_replace(' ', '_', $raw_type),
                    );
                }
            }

            return $choices;
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function build_pipe_choices($raw_values) {
            $choices = array();
            $items = explode('|', (string) $raw_values);
            foreach ($items as $item) {
                $item = trim((string) $item);
                if ('' === $item) {
                    continue;
                }

                $choices[] = array(
                    'key' => $this->make_choice_key($item, $item),
                    'label' => $item,
                    'stored_value' => $item,
                );
            }

            return $choices;
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function build_color_choices($raw_values) {
            $choices = array();
            $items = explode('|', (string) $raw_values);
            foreach ($items as $item) {
                $parts = explode(':', (string) $item, 2);
                $code = trim((string) ($parts[0] ?? ''));
                $label = trim((string) ($parts[1] ?? $code));
                if ('' === $code) {
                    continue;
                }

                $choices[] = array(
                    'key' => $this->make_choice_key($code, '' !== $label ? $label : $code),
                    'label' => $label,
                    'stored_value' => $code,
                );
            }

            return $choices;
        }

        /**
         * @return array<string,mixed>
         */
        private function parse_number_range_rules($raw_values) {
            $parts = explode('|', (string) $raw_values);

            return array(
                'min' => isset($parts[0]) && '' !== trim((string) $parts[0]) ? (float) $parts[0] : 0,
                'max' => isset($parts[1]) && '' !== trim((string) $parts[1]) ? (float) $parts[1] : 100,
                'step' => isset($parts[2]) && '' !== trim((string) $parts[2]) ? (float) $parts[2] : 1,
            );
        }

        /**
         * @param array<int,array<string,mixed>> $primary
         * @param array<int,array<string,mixed>> $secondary
         * @return array<string,array<string,mixed>>
         */
        private function merge_fields_by_key($primary, $secondary) {
            $merged = array();

            foreach (array_merge((array) $primary, (array) $secondary) as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $field_key = sanitize_key((string) ($field['field_key'] ?? ''));
                if ('' === $field_key || isset($merged[$field_key])) {
                    continue;
                }

                $merged[$field_key] = $field;
            }

            return $merged;
        }

        private function resolve_template_term_id($category_id) {
            $category_id = (int) $category_id;
            if ($category_id < 1) {
                return 0;
            }

            if (function_exists('adforest_dynamic_templateID')) {
                return (int) adforest_dynamic_templateID($category_id);
            }

            return (int) get_term_meta($category_id, '_sb_category_template', true);
        }

        private function map_post_status($moderation_status) {
            $moderation_status = sanitize_key((string) $moderation_status);
            $map = apply_filters(
                'bornado_ai_extraction_bridge_post_status_map',
                array(
                    'approved' => 'publish',
                    'pending' => 'pending',
                    'rejected' => 'draft',
                )
            );

            return isset($map[$moderation_status]) ? (string) $map[$moderation_status] : 'draft';
        }

        /**
         * @return array<int,string>
         */
        private function get_skip_statuses() {
            $statuses = apply_filters(
                'bornado_ai_extraction_bridge_skip_statuses',
                array('rejected')
            );

            return array_values(array_map('sanitize_key', (array) $statuses));
        }

        private function get_default_author_id() {
            $configured = defined('BORNADO_AI_EXTRACTION_AUTHOR_ID') ? (int) BORNADO_AI_EXTRACTION_AUTHOR_ID : 0;
            $configured = (int) apply_filters('bornado_ai_extraction_bridge_default_author_id', $configured);
            if ($configured > 0) {
                return $configured;
            }

            $admins = get_users(
                array(
                    'role__in' => array('administrator'),
                    'number' => 1,
                    'fields' => array('ID'),
                )
            );

            if (!empty($admins) && is_object($admins[0]) && isset($admins[0]->ID)) {
                return (int) $admins[0]->ID;
            }

            return 1;
        }

        private function apply_geo_location_taxonomy_fallback($taxonomies, $geo_location) {
            $taxonomies = is_array($taxonomies) ? $taxonomies : array();
            $geo_location = is_array($geo_location) ? $geo_location : array();

            $existing_terms = array_values(
                array_filter(
                    array_map('intval', (array) ($taxonomies['ad_country'] ?? array())),
                    static function ($term_id) {
                        return $term_id > 0;
                    }
                )
            );

            $existing_country_term_id = $this->extract_root_country_term_id($existing_terms);
            $existing_city_term_id = $this->extract_child_city_term_id($existing_terms);
            $country_term_id = (int) ($geo_location['country_term_id'] ?? 0);
            if ($country_term_id < 1 && $existing_country_term_id > 0) {
                $country_term_id = $existing_country_term_id;
            }

            $country_iso2 = strtoupper(sanitize_text_field((string) ($geo_location['country_iso2'] ?? '')));
            if ('' === $country_iso2 && $country_term_id > 0) {
                $country_iso2 = $this->resolve_country_iso2_for_term($country_term_id);
            }
            if (
                $country_term_id < 1 &&
                '' !== $country_iso2 &&
                class_exists('Bornado_Geo_Term_Manager') &&
                method_exists('Bornado_Geo_Term_Manager', 'ensure_root_country_term_by_iso2')
            ) {
                $country_term_id = (int) Bornado_Geo_Term_Manager::ensure_root_country_term_by_iso2($country_iso2);
            }

            $city_term_id = (int) ($geo_location['city_term_id'] ?? 0);
            if ($city_term_id < 1 && $existing_city_term_id > 0) {
                $city_term_id = $existing_city_term_id;
            }
            $city_geoname_id = (int) ($geo_location['city_geoname_id'] ?? 0);
            if (
                $city_term_id < 1 &&
                $city_geoname_id > 0 &&
                $country_term_id > 0 &&
                '' !== $country_iso2 &&
                class_exists('Bornado_Geo_Catalog') &&
                class_exists('Bornado_Geo_Term_Manager') &&
                method_exists('Bornado_Geo_Catalog', 'get_country_by_iso2') &&
                method_exists('Bornado_Geo_Catalog', 'get_city_by_country_and_geoname') &&
                method_exists('Bornado_Geo_Term_Manager', 'ensure_city_term')
            ) {
                $country = Bornado_Geo_Catalog::get_country_by_iso2($country_iso2);
                $city = Bornado_Geo_Catalog::get_city_by_country_and_geoname($country_iso2, $city_geoname_id);
                if (is_array($country) && !empty($country) && is_array($city) && !empty($city)) {
                    $city_term_id = (int) Bornado_Geo_Term_Manager::ensure_city_term($country, $city, $country_term_id);
                    if ($city_term_id > 0) {
                        $this->flush_location_picker_cache();
                    }
                }
            }

            $resolved_terms = array();
            if ($country_term_id > 0) {
                $resolved_terms[] = $country_term_id;
            }
            if ($city_term_id > 0) {
                $resolved_terms[] = $city_term_id;
            }

            if (!empty($resolved_terms)) {
                $taxonomies['ad_country'] = array_values(array_unique(array_map('intval', $resolved_terms)));
            }

            return $taxonomies;
        }

        private function save_taxonomy_terms($post_id, $taxonomies) {
            foreach ((array) $taxonomies as $taxonomy => $term_ids) {
                $taxonomy = sanitize_key((string) $taxonomy);
                if ('' === $taxonomy || !taxonomy_exists($taxonomy)) {
                    continue;
                }

                $normalized = array_values(
                    array_filter(
                        array_map('intval', (array) $term_ids),
                        static function ($term_id) {
                            return $term_id > 0;
                        }
                    )
                );

                wp_set_post_terms((int) $post_id, $normalized, $taxonomy, false);
            }
        }

        private function save_post_meta_map($post_id, $meta_map) {
            foreach ((array) $meta_map as $meta_key => $value) {
                $meta_key = trim((string) $meta_key);
                if ('' === $meta_key) {
                    continue;
                }

                if (null === $value || '' === $value) {
                    update_post_meta((int) $post_id, $meta_key, '');
                    continue;
                }

                if (is_array($value)) {
                    $value = wp_json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                update_post_meta((int) $post_id, $meta_key, sanitize_text_field((string) $value));
            }
        }

        private function clear_dynamic_template_meta($post_id) {
            global $wpdb;

            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
                    (int) $post_id,
                    $wpdb->esc_like('_adforest_tpl_field_') . '%'
                )
            );
        }

        private function resolve_geo_lookup_country_iso2($country_iso2, $country_key) {
            $country_iso2 = strtoupper(sanitize_text_field((string) $country_iso2));
            if (preg_match('/^[A-Z]{2}$/', $country_iso2)) {
                return $country_iso2;
            }

            $country_key = $this->canonical_key((string) $country_key);
            if (preg_match('/^[a-z]{2}$/', $country_key)) {
                return strtoupper($country_key);
            }

            $root_country = $this->resolve_root_country($country_key);
            if ($root_country instanceof WP_Term) {
                return $this->resolve_country_iso2_for_term((int) $root_country->term_id);
            }

            return '';
        }

        /**
         * @return array<string,mixed>
         */
        private function lookup_geo_catalog_city($country_iso2, $lookup_query, $geoname_id) {
            $country_iso2 = strtoupper(sanitize_text_field((string) $country_iso2));
            $lookup_query = sanitize_text_field((string) $lookup_query);
            $geoname_id = absint($geoname_id);

            if (
                '' === $country_iso2 ||
                !class_exists('Bornado_Geo_Catalog')
            ) {
                return array(
                    'resolved' => false,
                    'ambiguous' => false,
                );
            }

            if ($geoname_id > 0 && method_exists('Bornado_Geo_Catalog', 'get_city_by_country_and_geoname')) {
                $city = Bornado_Geo_Catalog::get_city_by_country_and_geoname($country_iso2, $geoname_id);
                if (is_array($city) && !empty($city)) {
                    return array(
                        'resolved' => true,
                        'ambiguous' => false,
                        'matched_by' => 'geoname_id',
                        'country_iso2' => $country_iso2,
                        'city' => $this->map_geo_catalog_city_payload($country_iso2, $city),
                    );
                }
            }

            if ('' === trim($lookup_query)) {
                return array(
                    'resolved' => false,
                    'ambiguous' => false,
                    'country_iso2' => $country_iso2,
                );
            }

            $canonical_query = $this->canonical_key($lookup_query);
            $exact_searches = array(
                'slug_candidate' => array_unique(array_filter(array($canonical_query))),
                'asciiname' => array_unique(array_filter(array($lookup_query))),
                'name_en' => array_unique(array_filter(array($lookup_query))),
                'name_fa' => array_unique(array_filter(array($lookup_query))),
            );

            if (method_exists('Bornado_Geo_Catalog', 'find_cities_by_exact_field')) {
                foreach ($exact_searches as $field => $values) {
                    foreach ($values as $value) {
                        $matches = Bornado_Geo_Catalog::find_cities_by_exact_field($country_iso2, $field, (string) $value, 5);
                        if (1 === count($matches) && is_array($matches[0])) {
                            return array(
                                'resolved' => true,
                                'ambiguous' => false,
                                'matched_by' => $field,
                                'country_iso2' => $country_iso2,
                                'city' => $this->map_geo_catalog_city_payload($country_iso2, $matches[0]),
                            );
                        }

                        if (count($matches) > 1) {
                            return array(
                                'resolved' => false,
                                'ambiguous' => true,
                                'matched_by' => $field,
                                'country_iso2' => $country_iso2,
                                'candidate_geoname_ids' => $this->collect_geo_catalog_candidate_ids($matches),
                            );
                        }
                    }
                }
            }

            if (method_exists('Bornado_Geo_Catalog', 'search_cities')) {
                $matches = Bornado_Geo_Catalog::search_cities($country_iso2, $lookup_query, 5);
                if (
                    1 === count($matches) &&
                    is_array($matches[0]) &&
                    $this->geo_catalog_city_matches_query($matches[0], $lookup_query)
                ) {
                    return array(
                        'resolved' => true,
                        'ambiguous' => false,
                        'matched_by' => 'search',
                        'country_iso2' => $country_iso2,
                        'city' => $this->map_geo_catalog_city_payload($country_iso2, $matches[0]),
                    );
                }

                if (count($matches) > 1) {
                    return array(
                        'resolved' => false,
                        'ambiguous' => true,
                        'matched_by' => 'search',
                        'country_iso2' => $country_iso2,
                        'candidate_geoname_ids' => $this->collect_geo_catalog_candidate_ids($matches),
                    );
                }
            }

            return array(
                'resolved' => false,
                'ambiguous' => false,
                'country_iso2' => $country_iso2,
            );
        }

        /**
         * @param array<string,mixed> $city
         */
        private function geo_catalog_city_matches_query($city, $query) {
            $query = trim((string) $query);
            if ('' === $query) {
                return false;
            }

            $canonical_query = $this->canonical_key($query);
            $raw_query = strtolower($query);
            foreach (array('slug_candidate', 'asciiname', 'name_en', 'name_fa') as $field) {
                $value = trim((string) ($city[$field] ?? ''));
                if ('' === $value) {
                    continue;
                }

                if ('' !== $canonical_query && $canonical_query === $this->canonical_key($value)) {
                    return true;
                }

                if ('' === $canonical_query && $raw_query === strtolower($value)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * @param array<string,mixed> $city
         * @return array<string,mixed>
         */
        private function map_geo_catalog_city_payload($country_iso2, $city) {
            $country_iso2 = strtoupper(sanitize_text_field((string) $country_iso2));
            $term_id = 0;
            $term = null;

            if (
                !empty($city['geoname_id']) &&
                class_exists('Bornado_Geo_Term_Manager') &&
                method_exists('Bornado_Geo_Term_Manager', 'find_city_term_by_source_id')
            ) {
                $term = Bornado_Geo_Term_Manager::find_city_term_by_source_id((int) $city['geoname_id']);
                if ($term instanceof WP_Term) {
                    $term_id = (int) $term->term_id;
                }
            }

            $label = $term instanceof WP_Term
                ? (string) $term->name
                : (!empty($city['name_fa']) ? (string) $city['name_fa'] : (string) ($city['name_en'] ?? ''));
            $slug = $term instanceof WP_Term
                ? (string) $term->slug
                : sanitize_title((string) ($city['slug_candidate'] ?? $city['asciiname'] ?? $city['name_en'] ?? $label));
            $key = $this->canonical_key($slug);
            if ('' === $key) {
                $key = $this->canonical_key((string) ($city['name_en'] ?? $city['asciiname'] ?? $label));
            }

            return array(
                'key' => $key,
                'label' => $label,
                'slug' => $slug,
                'term_id' => $term_id,
                'geoname_id' => (int) ($city['geoname_id'] ?? 0),
                'country_key' => strtolower($country_iso2),
                'aliases' => array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'strval',
                                array(
                                    $city['slug_candidate'] ?? '',
                                    $city['asciiname'] ?? '',
                                    $city['name_en'] ?? '',
                                    $city['name_fa'] ?? '',
                                )
                            )
                        )
                    )
                ),
            );
        }

        /**
         * @param array<int,array<string,mixed>> $matches
         * @return array<int,int>
         */
        private function collect_geo_catalog_candidate_ids($matches) {
            return array_values(
                array_filter(
                    array_map(
                        static function ($item) {
                            return is_array($item) && !empty($item['geoname_id']) ? (int) $item['geoname_id'] : 0;
                        },
                        (array) $matches
                    )
                )
            );
        }

        /**
         * @return WP_Term|null
         */
        private function resolve_root_country($market) {
            $market = sanitize_key((string) $market);

            if ('' === $market) {
                return null;
            }

            if (class_exists('Bornado_Location_Picker_Service') && method_exists('Bornado_Location_Picker_Service', 'get_root_country_options')) {
                $items = Bornado_Location_Picker_Service::get_root_country_options(false);
                foreach ((array) $items as $item) {
                    if (!is_array($item) || empty($item['id'])) {
                        continue;
                    }

                    $term = get_term((int) $item['id'], 'ad_country');
                    if (!$term instanceof WP_Term) {
                        continue;
                    }

                    $country_code = $this->resolve_country_key_for_term((int) $term->term_id);
                    $market_slug  = $this->canonical_key((string) $term->slug);

                    if ('' !== $market && ($market === $market_slug || $market === strtolower($country_code))) {
                        return $term;
                    }
                }

                if ('' !== $market) {
                    return null;
                }
            }

            if (
                '' !== $market &&
                preg_match('/^[a-z]{2}$/', $market) &&
                class_exists('Bornado_Geo_Term_Manager') &&
                method_exists('Bornado_Geo_Term_Manager', 'ensure_root_country_term_by_iso2')
            ) {
                $ensured_term_id = (int) Bornado_Geo_Term_Manager::ensure_root_country_term_by_iso2(strtoupper($market));
                if ($ensured_term_id > 0) {
                    $ensured_term = get_term($ensured_term_id, 'ad_country');
                    if ($ensured_term instanceof WP_Term) {
                        return $ensured_term;
                    }
                }
            }

            $terms = get_terms(
                array(
                    'taxonomy'   => 'ad_country',
                    'hide_empty' => false,
                    'number'     => 0,
                    'parent'     => 0,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            if (is_wp_error($terms) || !is_array($terms) || empty($terms)) {
                return null;
            }

            foreach ($terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                if ('' !== $market && $market === $this->canonical_key((string) $term->slug)) {
                    return $term;
                }
            }

            if ('' !== $market) {
                return null;
            }

            return null;
        }

        private function extract_root_country_term_id($term_ids) {
            foreach ((array) $term_ids as $term_id) {
                $term = get_term((int) $term_id, 'ad_country');
                if ($term instanceof WP_Term && 0 === (int) $term->parent) {
                    return (int) $term->term_id;
                }
            }

            return 0;
        }

        private function extract_child_city_term_id($term_ids) {
            foreach ((array) $term_ids as $term_id) {
                $term = get_term((int) $term_id, 'ad_country');
                if ($term instanceof WP_Term && (int) $term->parent > 0) {
                    return (int) $term->term_id;
                }
            }

            return 0;
        }

        private function resolve_country_iso2_for_term($term_id) {
            $term_id = (int) $term_id;
            if ($term_id < 1) {
                return '';
            }

            $country_code = strtoupper($this->canonical_key((string) $this->get_country_data_value($term_id, 'country_code')));
            if ('' !== $country_code) {
                return $country_code;
            }

            $meta_keys = array('_bornado_country_code', '_bornado_geo_country_iso2');
            foreach ($meta_keys as $meta_key) {
                $value = strtoupper(sanitize_text_field((string) get_term_meta($term_id, $meta_key, true)));
                if ('' !== $value) {
                    return $value;
                }
            }

            return '';
        }

        private function flush_location_picker_cache() {
            if (class_exists('Bornado_Location_Picker_Service') && method_exists('Bornado_Location_Picker_Service', 'flush_cache')) {
                Bornado_Location_Picker_Service::flush_cache();
            }
        }

        /**
         * @return array<string,mixed>
         */
        private function map_root_country($term) {
            if (!$term instanceof WP_Term) {
                return array();
            }

            $country_code = (string) $this->get_country_data_value($term, 'country_code');
            $display_name_en = (string) $this->get_country_data_value($term, 'display_name_en');
            $phone_dial_code = (string) $this->get_country_data_value($term, 'phone_dial_code');
            $market_status = (string) $this->get_country_data_value($term, 'market_status');
            $currency_name = (string) $this->get_country_data_value($term, 'currency_name');

            return array(
                'key' => $this->resolve_country_key_for_term((int) $term->term_id),
                'label' => (string) $term->name,
                'slug' => (string) $term->slug,
                'term_id' => (int) $term->term_id,
                'country_code' => $country_code,
                'display_name_en' => $display_name_en,
                'phone_dial_code' => $phone_dial_code,
                'market_status' => $market_status,
                'currency_name' => $currency_name,
            );
        }

        private function get_country_data_value($term, $key) {
            if (class_exists('Bornado_Country_Model') && method_exists('Bornado_Country_Model', 'get_country_data')) {
                $data = Bornado_Country_Model::get_country_data($term);
                if (is_array($data) && array_key_exists($key, $data)) {
                    return $data[$key];
                }
            }

            return '';
        }

        private function resolve_country_key_for_term($term_id) {
            $term = get_term((int) $term_id, 'ad_country');
            if (!$term instanceof WP_Term) {
                return '';
            }

            $country_code = $this->canonical_key((string) $this->get_country_data_value($term, 'country_code'));
            if ('' !== $country_code) {
                return $country_code;
            }

            $meta_keys = array('_bornado_country_code', '_bornado_geo_country_iso2');
            foreach ($meta_keys as $meta_key) {
                $meta_value = $this->canonical_key((string) get_term_meta($term->term_id, $meta_key, true));
                if ('' !== $meta_value) {
                    return $meta_value;
                }
            }

            return $this->canonical_key((string) $term->slug);
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        private function get_template_validation_rows($template_id) {
            $encoded = (string) get_term_meta((int) $template_id, '_sb_dynamic_form_fields', true);
            if ('' === $encoded) {
                return array();
            }

            $rows = sb_dynamic_form_data($encoded);

            return is_array($rows) ? $rows : array();
        }

        private function has_template_field_row_content($row) {
            $keys = array('titles', 'slugs', 'types', 'values');
            foreach ($keys as $key) {
                if ('' !== trim((string) ($row[$key] ?? ''))) {
                    return true;
                }
            }

            return (string) ($row['status'] ?? '') === '1';
        }

        /**
         * @param array<string,bool> $staticFieldKeys
         * @param array<string,int> $seenActiveSlugs
         * @param array<string,int> $seenAnySlugs
         * @return array<string,array<int,string>>
         */
        private function validate_template_field_row($row, $rowIndex, $staticFieldKeys, &$seenActiveSlugs, &$seenAnySlugs) {
            $errors = array();
            $warnings = array();
            $supported_types = $this->get_supported_dynamic_type_codes();
            $option_type_codes = array('2', '3', '8', '9');
            $reference = $this->build_template_field_reference($row, $rowIndex);
            $is_active = (string) ($row['status'] ?? '') === '1';
            $title = trim((string) ($row['titles'] ?? ''));
            $raw_slug = trim((string) ($row['slugs'] ?? ''));
            $slug = sanitize_key($raw_slug);
            $type_code = trim((string) ($row['types'] ?? ''));
            $values = trim((string) ($row['values'] ?? ''));

            if ($is_active && '' === $title) {
                $warnings[] = $reference . ': Field Name is empty.';
            }

            if ($is_active && '' === $raw_slug) {
                $errors[] = $reference . ': Slug Name is required.';
            } elseif ($slug !== $raw_slug) {
                $warnings[] = $reference . ': Slug will be normalized to "' . $slug . '". Use only lowercase letters, numbers, and underscores.';
            }

            if ('' !== $slug) {
                if (isset($seenAnySlugs[$slug])) {
                    $warnings[] = $reference . ': Slug "' . $slug . '" is repeated in multiple rows.';
                } else {
                    $seenAnySlugs[$slug] = $rowIndex;
                }

                if ($is_active && isset($seenActiveSlugs[$slug])) {
                    $errors[] = $reference . ': Active slug "' . $slug . '" is duplicated.';
                } elseif ($is_active) {
                    $seenActiveSlugs[$slug] = $rowIndex;
                }

                if ($is_active && isset($staticFieldKeys[$slug])) {
                    $errors[] = $reference . ': Slug "' . $slug . '" conflicts with a built-in template field key.';
                }
            }

            if ($is_active && '' === $type_code) {
                $errors[] = $reference . ': Select Option is required.';
            } elseif ($is_active && !isset($supported_types[$type_code])) {
                $errors[] = $reference . ': Unsupported field type code "' . $type_code . '".';
            }

            if ($is_active && in_array($type_code, $option_type_codes, true)) {
                $choices = $this->build_pipe_choices($values);
                if (empty($choices)) {
                    $errors[] = $reference . ': Option fields need at least one value in the Values box.';
                }

                $choice_keys = array();
                foreach ($choices as $choice) {
                    $choice_key = (string) ($choice['key'] ?? '');
                    if ('' === $choice_key) {
                        continue;
                    }

                    if (isset($choice_keys[$choice_key])) {
                        $warnings[] = $reference . ': Duplicate option key "' . $choice_key . '" may confuse AI resolution.';
                    } else {
                        $choice_keys[$choice_key] = true;
                    }
                }
            } elseif ($is_active && '7' === $type_code) {
                $choices = $this->build_color_choices($values);
                if (empty($choices)) {
                    $errors[] = $reference . ': Color fields need values like "#fff:white|#000:black".';
                }

                foreach ($choices as $choice) {
                    $stored_value = trim((string) ($choice['stored_value'] ?? ''));
                    if ('' !== $stored_value && !preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $stored_value)) {
                        $warnings[] = $reference . ': Color value "' . $stored_value . '" is not a standard hex color code.';
                    }
                }
            } elseif ($is_active && '6' === $type_code) {
                $parts = explode('|', $values);
                if (count($parts) < 3) {
                    $errors[] = $reference . ': Number range fields should use "min|max|step".';
                } else {
                    $min = trim((string) ($parts[0] ?? ''));
                    $max = trim((string) ($parts[1] ?? ''));
                    $step = trim((string) ($parts[2] ?? ''));

                    if (!is_numeric($min) || !is_numeric($max) || !is_numeric($step)) {
                        $errors[] = $reference . ': Number range values must all be numeric.';
                    } elseif ((float) $max < (float) $min) {
                        $errors[] = $reference . ': Number range max must be greater than or equal to min.';
                    } elseif ((float) $step <= 0) {
                        $errors[] = $reference . ': Number range step must be greater than zero.';
                    }
                }
            }

            return array(
                'errors' => $errors,
                'warnings' => $warnings,
            );
        }

        /**
         * @return array<string,bool>
         */
        private function get_supported_dynamic_type_codes() {
            return array(
                '1' => true,
                '2' => true,
                '3' => true,
                '4' => true,
                '5' => true,
                '6' => true,
                '7' => true,
                '8' => true,
                '9' => true,
            );
        }

        private function build_template_field_reference($row, $rowIndex) {
            $title = trim((string) ($row['titles'] ?? ''));
            $slug = trim((string) ($row['slugs'] ?? ''));
            $parts = array();

            if ('' !== $title) {
                $parts[] = $title;
            }

            if ('' !== $slug) {
                $parts[] = $slug;
            }

            if (!empty($parts)) {
                return 'Row ' . ((int) $rowIndex + 1) . ' (' . implode(' / ', $parts) . ')';
            }

            return 'Row ' . ((int) $rowIndex + 1);
        }

        private function get_template_validation_notice_key() {
            return 'bornado_ai_bridge_template_validation_' . (int) get_current_user_id();
        }

        private function get_template_validation_status($template_result) {
            $errors = isset($template_result['errors']) && is_array($template_result['errors']) ? $template_result['errors'] : array();
            $warnings = isset($template_result['warnings']) && is_array($template_result['warnings']) ? $template_result['warnings'] : array();

            if (!empty($errors)) {
                return 'error';
            }

            if (!empty($warnings)) {
                return 'warning';
            }

            return 'ok';
        }

        private function render_validation_status_badge($status) {
            $status = strtolower(trim((string) $status));
            $palette = array(
                'ok' => array(
                    'label' => 'OK',
                    'background' => '#edfaef',
                    'border' => '#99d5a5',
                    'text' => '#166534',
                ),
                'warning' => array(
                    'label' => 'Warning',
                    'background' => '#fff7e6',
                    'border' => '#f2c16b',
                    'text' => '#9a6700',
                ),
                'error' => array(
                    'label' => 'Error',
                    'background' => '#fef1f1',
                    'border' => '#f0a0a0',
                    'text' => '#b42318',
                ),
            );

            $config = isset($palette[$status]) ? $palette[$status] : $palette['ok'];

            return sprintf(
                '<span style="display:inline-block;padding:3px 10px;border-radius:999px;border:1px solid %1$s;background:%2$s;color:%3$s;font-weight:600;font-size:12px;line-height:1.6;">%4$s</span>',
                esc_attr((string) $config['border']),
                esc_attr((string) $config['background']),
                esc_attr((string) $config['text']),
                esc_html((string) $config['label'])
            );
        }

        private function canonical_key($value) {
            $value = trim((string) $value);
            if ('' === $value) {
                return '';
            }

            $slug = sanitize_title($value);
            if ('' !== $slug) {
                return str_replace('_', '-', $slug);
            }

            return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
        }

        private function make_choice_key($storedValue, $label) {
            $storedValue = trim((string) $storedValue);
            $label = trim((string) $label);

            $storedKey = $this->canonical_key($storedValue);
            if ('' !== $storedKey && false === strpos($storedKey, '%')) {
                return $storedKey;
            }

            $labelKey = $this->canonical_key($label);
            if ('' !== $labelKey && false === strpos($labelKey, '%')) {
                return $labelKey;
            }

            if ('' !== $storedValue) {
                return $storedValue;
            }

            return $label;
        }
    }
}
