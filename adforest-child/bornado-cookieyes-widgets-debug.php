<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_cookieyes_widgets_debug_is_enabled')) {
    /**
     * Enable CookieYes widgets-page diagnostics only for explicit admin requests.
     *
     * Usage:
     * - Append `?bornado_cookieyes_debug=1` to the widgets admin URL, or
     * - define `BORNADO_COOKIEYES_WIDGETS_DEBUG` as true.
     *
     * @return bool
     */
    function bornado_cookieyes_widgets_debug_is_enabled()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return false;
        }

        if (defined('BORNADO_COOKIEYES_WIDGETS_DEBUG') && BORNADO_COOKIEYES_WIDGETS_DEBUG) {
            return true;
        }

        $flag = isset($_GET['bornado_cookieyes_debug']) ? wp_unslash($_GET['bornado_cookieyes_debug']) : '';
        return in_array(strtolower(trim((string) $flag)), array('1', 'true', 'yes'), true);
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_is_widgets_screen')) {
    /**
     * Check whether the current admin screen is the widgets page.
     *
     * @return bool
     */
    function bornado_cookieyes_widgets_debug_is_widgets_screen()
    {
        if (!is_admin()) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && isset($screen->base) && (string) $screen->base === 'widgets') {
            return true;
        }

        $pagenow = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';
        return $pagenow === 'widgets.php';
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_log_path')) {
    /**
     * Resolve the writable log path for widgets-page CookieYes diagnostics.
     *
     * @return string
     */
    function bornado_cookieyes_widgets_debug_log_path()
    {
        $uploads = wp_upload_dir();
        $base_dir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
        if ($base_dir === '') {
            return '';
        }

        return trailingslashit($base_dir) . 'bornado-cookieyes-widgets-debug.log';
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_log')) {
    /**
     * Write one structured debug event as JSONL.
     *
     * @param string $event   Short event label.
     * @param array  $payload Structured event payload.
     * @return void
     */
    function bornado_cookieyes_widgets_debug_log($event, array $payload = array())
    {
        $log_path = bornado_cookieyes_widgets_debug_log_path();
        if ($log_path === '') {
            return;
        }

        $entry = array(
            'time'    => current_time('mysql'),
            'event'   => (string) $event,
            'request' => isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '',
            'payload' => $payload,
        );

        $line = wp_json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($line) || $line === '') {
            return;
        }

        @file_put_contents($log_path, $line . PHP_EOL, FILE_APPEND);
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_truncate_string')) {
    /**
     * Keep log payload strings readable and bounded.
     *
     * @param string $value String payload fragment.
     * @param int    $limit Maximum length.
     * @return string
     */
    function bornado_cookieyes_widgets_debug_truncate_string($value, $limit = 1200)
    {
        $value = is_string($value) ? $value : '';
        $limit = max(50, (int) $limit);

        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit) . '... [truncated]';
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_normalize_payload')) {
    /**
     * Recursively bound debug payload size before logging.
     *
     * @param mixed $value Raw payload value.
     * @param int   $depth Current recursion depth.
     * @return mixed
     */
    function bornado_cookieyes_widgets_debug_normalize_payload($value, $depth = 0)
    {
        if ($depth > 4) {
            return '[max-depth]';
        }

        if (is_string($value)) {
            return bornado_cookieyes_widgets_debug_truncate_string($value);
        }

        if (is_scalar($value) || null === $value) {
            return $value;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return '[unsupported]';
        }

        $normalized = array();
        $count = 0;
        foreach ($value as $key => $item) {
            $count++;
            if ($count > 100) {
                $normalized['__truncated__'] = 'Too many items';
                break;
            }

            $normalized[is_int($key) ? $key : sanitize_key((string) $key)] = bornado_cookieyes_widgets_debug_normalize_payload($item, $depth + 1);
        }

        return $normalized;
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_collect_environment')) {
    /**
     * Collect the key URL and host values that could trigger a CookieYes mismatch.
     *
     * @return array<string,mixed>
     */
    function bornado_cookieyes_widgets_debug_collect_environment()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $home_url = home_url('/');
        $site_url = site_url('/');
        $admin_widgets_url = admin_url('widgets.php');

        return array(
            'http_host'         => isset($_SERVER['HTTP_HOST']) ? (string) wp_unslash($_SERVER['HTTP_HOST']) : '',
            'server_name'       => isset($_SERVER['SERVER_NAME']) ? (string) wp_unslash($_SERVER['SERVER_NAME']) : '',
            'request_uri'       => isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '',
            'https'             => is_ssl(),
            'home_url'          => $home_url,
            'site_url'          => $site_url,
            'admin_widgets_url' => $admin_widgets_url,
            'home_host'         => (string) wp_parse_url($home_url, PHP_URL_HOST),
            'site_host'         => (string) wp_parse_url($site_url, PHP_URL_HOST),
            'admin_host'        => (string) wp_parse_url($admin_widgets_url, PHP_URL_HOST),
            'screen_id'         => $screen && isset($screen->id) ? (string) $screen->id : '',
            'screen_base'       => $screen && isset($screen->base) ? (string) $screen->base : '',
            'referer'           => wp_get_referer(),
        );
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_log_environment')) {
    /**
     * Emit one top-level environment snapshot for the widgets request.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_log_environment()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        bornado_cookieyes_widgets_debug_log(
            'environment',
            bornado_cookieyes_widgets_debug_collect_environment()
        );
    }
}
add_action('current_screen', 'bornado_cookieyes_widgets_debug_log_environment', 5);

if (!function_exists('bornado_cookieyes_widgets_debug_request_id')) {
    /**
     * Generate one stable request id for correlating PHP and browser-side events.
     *
     * @return string
     */
    function bornado_cookieyes_widgets_debug_request_id()
    {
        static $request_id = null;

        if (is_string($request_id) && $request_id !== '') {
            return $request_id;
        }

        $request_id = function_exists('wp_generate_uuid4')
            ? (string) wp_generate_uuid4()
            : uniqid('bornado-cookieyes-', true);

        return $request_id;
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_option_scan_matches')) {
    /**
     * Search widget options for CookieYes-related fragments.
     *
     * @return array<int,array<string,mixed>>
     */
    function bornado_cookieyes_widgets_debug_option_scan_matches()
    {
        global $wpdb;

        $keywords = array(
            'cookieyes',
            'cdn-cookieyes',
            'cookie-script',
            'cookie-law-info',
            'ckyconsent',
            'cky-',
            'script.js',
            'app.cookieyes.com',
        );

        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget_%' OR option_name = 'sidebars_widgets'",
            ARRAY_A
        );

        if (!is_array($options) || empty($options)) {
            return array();
        }

        $matches = array();
        foreach ($options as $row) {
            $option_name = isset($row['option_name']) ? (string) $row['option_name'] : '';
            $option_value = isset($row['option_value']) ? maybe_unserialize($row['option_value']) : '';
            $search_blob = is_scalar($option_value)
                ? (string) $option_value
                : wp_json_encode($option_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $search_blob = is_string($search_blob) ? $search_blob : '';
            $search_blob_lc = strtolower($search_blob);
            $matched_keywords = array();

            foreach ($keywords as $keyword) {
                if (strpos($search_blob_lc, strtolower($keyword)) !== false) {
                    $matched_keywords[] = $keyword;
                }
            }

            if (empty($matched_keywords)) {
                continue;
            }

            $matches[] = array(
                'option_name'       => $option_name,
                'matched_keywords'  => array_values($matched_keywords),
                'serialized_snippet'=> bornado_cookieyes_widgets_debug_truncate_string($search_blob, 1800),
            );
        }

        return $matches;
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_log_option_scan')) {
    /**
     * Log server-side widget option matches before the page renders.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_log_option_scan()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        bornado_cookieyes_widgets_debug_log(
            'widget_option_scan',
            array(
                'request_id' => bornado_cookieyes_widgets_debug_request_id(),
                'matches'    => bornado_cookieyes_widgets_debug_option_scan_matches(),
            )
        );
    }
}
add_action('current_screen', 'bornado_cookieyes_widgets_debug_log_option_scan', 8);

if (!function_exists('bornado_cookieyes_widgets_debug_log_active_plugins')) {
    /**
     * Log active plugins/themes that may be relevant to script injection.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_log_active_plugins()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        $plugins = get_option('active_plugins', array());
        $mu_plugins = function_exists('get_mu_plugins') ? array_keys((array) get_mu_plugins()) : array();
        $stylesheet = function_exists('wp_get_theme') ? wp_get_theme()->get_stylesheet() : '';

        bornado_cookieyes_widgets_debug_log(
            'active_stack',
            array(
                'request_id'    => bornado_cookieyes_widgets_debug_request_id(),
                'active_plugins'=> array_values(array_map('strval', (array) $plugins)),
                'mu_plugins'    => array_values(array_map('strval', (array) $mu_plugins)),
                'stylesheet'    => (string) $stylesheet,
            )
        );
    }
}
add_action('current_screen', 'bornado_cookieyes_widgets_debug_log_active_plugins', 9);

if (!function_exists('bornado_cookieyes_widgets_debug_log_registered_scripts')) {
    /**
     * Capture any registered/enqueued scripts that look related to CookieYes.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_log_registered_scripts()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        global $wp_scripts;

        if (!($wp_scripts instanceof WP_Scripts)) {
            return;
        }

        $matches = array();
        foreach ((array) $wp_scripts->registered as $handle => $script) {
            $src = '';
            if ($script instanceof _WP_Dependency && isset($script->src)) {
                $src = (string) $script->src;
            }

            $haystacks = array(strtolower((string) $handle), strtolower($src));
            $looks_relevant = false;
            foreach ($haystacks as $haystack) {
                if ($haystack !== '' && (strpos($haystack, 'cookieyes') !== false || strpos($haystack, 'cookie-yes') !== false)) {
                    $looks_relevant = true;
                    break;
                }
            }

            if (!$looks_relevant) {
                continue;
            }

            $matches[] = array(
                'handle'    => (string) $handle,
                'src'       => $src,
                'deps'      => isset($script->deps) ? array_values(array_map('strval', (array) $script->deps)) : array(),
                'ver'       => isset($script->ver) ? (string) $script->ver : '',
                'enqueued'  => in_array((string) $handle, (array) $wp_scripts->queue, true),
                'to_do'     => in_array((string) $handle, (array) $wp_scripts->to_do, true),
                'done'      => in_array((string) $handle, (array) $wp_scripts->done, true),
            );
        }

        bornado_cookieyes_widgets_debug_log(
            'registered_scripts',
            array(
                'request_id'        => bornado_cookieyes_widgets_debug_request_id(),
                'cookieyes_matches' => $matches,
                'queue'             => array_values(array_map('strval', (array) $wp_scripts->queue)),
            )
        );
    }
}
add_action('admin_print_scripts', 'bornado_cookieyes_widgets_debug_log_registered_scripts', 999);

if (!function_exists('bornado_cookieyes_widgets_debug_log_registered_styles')) {
    /**
     * Capture stylesheet handles that look related to CookieYes or cookie banners.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_log_registered_styles()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        global $wp_styles;

        if (!($wp_styles instanceof WP_Styles)) {
            return;
        }

        $matches = array();
        foreach ((array) $wp_styles->registered as $handle => $style) {
            $src = '';
            if ($style instanceof _WP_Dependency && isset($style->src)) {
                $src = (string) $style->src;
            }

            $haystacks = array(strtolower((string) $handle), strtolower($src));
            foreach ($haystacks as $haystack) {
                if ($haystack !== '' && (strpos($haystack, 'cookie') !== false || strpos($haystack, 'cky') !== false)) {
                    $matches[] = array(
                        'handle'   => (string) $handle,
                        'src'      => $src,
                        'enqueued' => in_array((string) $handle, (array) $wp_styles->queue, true),
                    );
                    break;
                }
            }
        }

        bornado_cookieyes_widgets_debug_log(
            'registered_styles',
            array(
                'request_id'     => bornado_cookieyes_widgets_debug_request_id(),
                'cookie_matches' => $matches,
            )
        );
    }
}
add_action('admin_print_styles', 'bornado_cookieyes_widgets_debug_log_registered_styles', 999);

if (!function_exists('bornado_cookieyes_widgets_debug_extract_inline_script_matches')) {
    /**
     * Inspect final HTML for inline scripts that reference CookieYes-like terms.
     *
     * @param string $html Full output-buffer HTML.
     * @return array<int,array<string,string>>
     */
    function bornado_cookieyes_widgets_debug_extract_inline_script_matches($html)
    {
        $matches = array();
        if (!is_string($html) || $html === '') {
            return $matches;
        }

        if (!preg_match_all('#<script\b(?![^>]*\bsrc=)[^>]*>(.*?)</script>#is', $html, $script_matches, PREG_OFFSET_CAPTURE)) {
            return $matches;
        }

        $keywords = array('cookieyes', 'cdn-cookieyes', 'script.js', 'cky', 'cookie-script', 'createelement(\'script', 'createelement("script');
        foreach ($script_matches[1] as $script_match) {
            $content = isset($script_match[0]) ? (string) $script_match[0] : '';
            $content_lc = strtolower($content);
            $matched_keywords = array();

            foreach ($keywords as $keyword) {
                if (strpos($content_lc, strtolower($keyword)) !== false) {
                    $matched_keywords[] = $keyword;
                }
            }

            if (empty($matched_keywords)) {
                continue;
            }

            $matches[] = array(
                'matched_keywords' => implode(', ', array_values($matched_keywords)),
                'snippet'          => bornado_cookieyes_widgets_debug_truncate_string(trim(preg_replace('/\s+/', ' ', $content)), 1800),
            );
        }

        return $matches;
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_extract_html_matches')) {
    /**
     * Inspect final widgets-page HTML for CookieYes script tags and nearby context.
     *
     * @param string $html Full output-buffer HTML.
     * @return array<int,array<string,mixed>>
     */
    function bornado_cookieyes_widgets_debug_extract_html_matches($html)
    {
        $matches = array();
        if (!is_string($html) || $html === '') {
            return $matches;
        }

        $pattern = '#<script\b[^>]*\bsrc=(["\'])([^"\']*(?:cookieyes|cdn-cookieyes)[^"\']*)\1[^>]*>\s*</script>#i';
        if (!preg_match_all($pattern, $html, $all_matches, PREG_OFFSET_CAPTURE)) {
            return $matches;
        }

        foreach ($all_matches[0] as $index => $tag_match) {
            $tag_html = isset($tag_match[0]) ? (string) $tag_match[0] : '';
            $offset = isset($tag_match[1]) ? (int) $tag_match[1] : 0;
            $src = isset($all_matches[2][$index][0]) ? (string) $all_matches[2][$index][0] : '';
            $context_start = max(0, $offset - 240);
            $context_length = strlen($tag_html) + 480;
            $context = substr($html, $context_start, $context_length);
            $context = is_string($context) ? preg_replace('/\s+/', ' ', $context) : '';

            $origin_hint = '';
            $context_lc = strtolower((string) $context);
            if (strpos($context_lc, 'wp-widget-custom_html') !== false || strpos($context_lc, 'custom_html') !== false) {
                $origin_hint = 'custom_html_widget';
            } elseif (strpos($context_lc, 'widget block editor') !== false || strpos($context_lc, 'block-editor') !== false) {
                $origin_hint = 'block_widgets_editor';
            } elseif (strpos($context_lc, 'textarea') !== false) {
                $origin_hint = 'textarea_or_saved_markup';
            }

            $matches[] = array(
                'src'         => $src,
                'tag'         => $tag_html,
                'origin_hint' => $origin_hint,
                'context'     => $context,
            );
        }

        return $matches;
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_buffer_callback')) {
    /**
     * Log final HTML evidence without changing the response body.
     *
     * @param string $html Full widgets-page HTML.
     * @return string
     */
    function bornado_cookieyes_widgets_debug_buffer_callback($html)
    {
        $matches = bornado_cookieyes_widgets_debug_extract_html_matches($html);
        $inline_matches = bornado_cookieyes_widgets_debug_extract_inline_script_matches($html);

        bornado_cookieyes_widgets_debug_log(
            'final_html_scan',
            array(
                'request_id'             => bornado_cookieyes_widgets_debug_request_id(),
                'cookieyes_script_count' => count($matches),
                'matches'                => $matches,
                'inline_script_matches'  => $inline_matches,
            )
        );

        return $html;
    }
}

if (!function_exists('bornado_cookieyes_widgets_debug_start_buffer')) {
    /**
     * Start final-output inspection on the widgets page.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_start_buffer()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        ob_start('bornado_cookieyes_widgets_debug_buffer_callback');
    }
}
add_action('current_screen', 'bornado_cookieyes_widgets_debug_start_buffer', 15);

if (!function_exists('bornado_cookieyes_widgets_debug_ajax_event')) {
    /**
     * Persist browser-side debug batches from the widgets page.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_ajax_event()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }

        check_ajax_referer('bornado_cookieyes_widgets_debug', 'nonce');

        $request_id = isset($_POST['request_id']) ? sanitize_text_field(wp_unslash($_POST['request_id'])) : '';
        $events = isset($_POST['events']) ? wp_unslash($_POST['events']) : array();
        if (is_string($events)) {
            $decoded = json_decode($events, true);
            $events = is_array($decoded) ? $decoded : array();
        }

        bornado_cookieyes_widgets_debug_log(
            'browser_events',
            array(
                'request_id' => $request_id,
                'events'     => bornado_cookieyes_widgets_debug_normalize_payload($events),
            )
        );

        wp_send_json_success(array('stored' => count((array) $events)));
    }
}
add_action('wp_ajax_bornado_cookieyes_widgets_debug_event', 'bornado_cookieyes_widgets_debug_ajax_event');

if (!function_exists('bornado_cookieyes_widgets_debug_enqueue_client_probe')) {
    /**
     * Enqueue a one-off browser probe to catch dynamic script injection and JS errors.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_enqueue_client_probe()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        $config = array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('bornado_cookieyes_widgets_debug'),
            'requestId' => bornado_cookieyes_widgets_debug_request_id(),
        );

        wp_register_script('bornado-cookieyes-widgets-debug-probe', '', array(), null, true);
        wp_enqueue_script('bornado-cookieyes-widgets-debug-probe');
        wp_add_inline_script(
            'bornado-cookieyes-widgets-debug-probe',
            'window.BornadoCookieYesWidgetsDebug=' . wp_json_encode($config) . ';' . <<<'JS'
(function () {
    var config = window.BornadoCookieYesWidgetsDebug || {};
    var requestId = String(config.requestId || '');
    var ajaxUrl = String(config.ajaxUrl || '');
    var nonce = String(config.nonce || '');
    var queue = [];
    var flushTimer = 0;
    var scriptIds = 0;

    function truncate(value, limit) {
        value = String(value == null ? '' : value);
        limit = limit || 1200;
        return value.length > limit ? value.slice(0, limit) + '... [truncated]' : value;
    }

    function stackSnippet() {
        try {
            throw new Error('trace');
        } catch (error) {
            return truncate((error && error.stack) || '', 1400);
        }
    }

    function describeScript(node) {
        if (!node || !node.tagName || String(node.tagName).toLowerCase() !== 'script') {
            return null;
        }

        var text = '';
        try {
            text = node.textContent || '';
        } catch (error) {
            text = '';
        }

        return {
            src: truncate(node.src || node.getAttribute('src') || '', 1000),
            type: truncate(node.type || '', 200),
            async: !!node.async,
            defer: !!node.defer,
            id: truncate(node.id || '', 200),
            className: truncate(node.className || '', 300),
            datasetDebugId: truncate(node.getAttribute('data-bornado-debug-id') || '', 200),
            textSnippet: truncate(text.replace(/\s+/g, ' ').trim(), 800)
        };
    }

    function enqueue(eventType, payload) {
        queue.push({
            time: new Date().toISOString(),
            eventType: eventType,
            payload: payload || {}
        });

        if (queue.length >= 10) {
            flush();
            return;
        }

        if (!flushTimer) {
            flushTimer = window.setTimeout(flush, 1500);
        }
    }

    function flush() {
        if (!queue.length || !ajaxUrl || !nonce) {
            return;
        }

        if (flushTimer) {
            window.clearTimeout(flushTimer);
            flushTimer = 0;
        }

        var batch = queue.slice();
        queue = [];

        var form = new FormData();
        form.append('action', 'bornado_cookieyes_widgets_debug_event');
        form.append('nonce', nonce);
        form.append('request_id', requestId);
        form.append('events', JSON.stringify(batch));

        if (navigator.sendBeacon) {
            try {
                navigator.sendBeacon(ajaxUrl, form);
                return;
            } catch (error) {
                // Fall through to fetch.
            }
        }

        if (window.fetch) {
            fetch(ajaxUrl, {
                method: 'POST',
                body: form,
                credentials: 'same-origin',
                keepalive: true
            }).catch(function () {});
        }
    }

    function markScript(node, reason) {
        if (!node || !node.tagName || String(node.tagName).toLowerCase() !== 'script') {
            return;
        }

        if (!node.getAttribute('data-bornado-debug-id')) {
            scriptIds += 1;
            node.setAttribute('data-bornado-debug-id', 'script-' + scriptIds);
        }

        enqueue(reason, {
            script: describeScript(node),
            stack: stackSnippet()
        });
    }

    enqueue('client_probe_booted', {
        requestId: requestId,
        href: truncate(window.location.href, 1000),
        referrer: truncate(document.referrer || '', 1000),
        readyState: document.readyState
    });

    Array.prototype.forEach.call(document.scripts || [], function (node) {
        markScript(node, 'initial_script_snapshot');
    });

    var originalCreateElement = Document.prototype.createElement;
    Document.prototype.createElement = function (tagName) {
        var node = originalCreateElement.apply(this, arguments);
        if (String(tagName || '').toLowerCase() === 'script') {
            scriptIds += 1;
            node.setAttribute('data-bornado-debug-id', 'created-' + scriptIds);
            enqueue('script_created', {
                script: describeScript(node),
                stack: stackSnippet()
            });
        }
        return node;
    };

    var originalAppendChild = Node.prototype.appendChild;
    Node.prototype.appendChild = function (child) {
        if (child && child.tagName && String(child.tagName).toLowerCase() === 'script') {
            markScript(child, 'script_append_child');
        }
        return originalAppendChild.apply(this, arguments);
    };

    var originalInsertBefore = Node.prototype.insertBefore;
    Node.prototype.insertBefore = function (child) {
        if (child && child.tagName && String(child.tagName).toLowerCase() === 'script') {
            markScript(child, 'script_insert_before');
        }
        return originalInsertBefore.apply(this, arguments);
    };

    var originalSetAttribute = Element.prototype.setAttribute;
    Element.prototype.setAttribute = function (name, value) {
        if (
            this &&
            this.tagName &&
            String(this.tagName).toLowerCase() === 'script' &&
            String(name || '').toLowerCase() === 'src'
        ) {
            enqueue('script_set_attribute_src', {
                src: truncate(value || '', 1000),
                currentScript: describeScript(this),
                stack: stackSnippet()
            });
        }

        return originalSetAttribute.apply(this, arguments);
    };

    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes && mutation.addedNodes.length) {
                    Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                        if (node && node.tagName && String(node.tagName).toLowerCase() === 'script') {
                            markScript(node, 'mutation_added_script');
                        }
                    });
                }

                if (
                    mutation.type === 'attributes' &&
                    mutation.target &&
                    mutation.target.tagName &&
                    String(mutation.target.tagName).toLowerCase() === 'script'
                ) {
                    enqueue('mutation_script_attribute', {
                        attributeName: mutation.attributeName,
                        script: describeScript(mutation.target)
                    });
                }
            });
        }).observe(document.documentElement || document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['src', 'type']
        });
    }

    window.addEventListener('error', function (event) {
        enqueue('window_error', {
            message: truncate(event.message || '', 1000),
            filename: truncate(event.filename || '', 1000),
            lineno: event.lineno || 0,
            colno: event.colno || 0,
            stack: truncate(event.error && event.error.stack ? event.error.stack : '', 1400),
            currentScript: describeScript(document.currentScript)
        });
        flush();
    }, true);

    window.addEventListener('unhandledrejection', function (event) {
        var reason = event && event.reason;
        enqueue('unhandled_rejection', {
            reason: truncate(reason && reason.stack ? reason.stack : reason, 1400)
        });
        flush();
    });

    window.addEventListener('load', function () {
        var resourceScripts = [];
        if (window.performance && performance.getEntriesByType) {
            resourceScripts = performance.getEntriesByType('resource')
                .filter(function (entry) {
                    return entry && entry.initiatorType === 'script';
                })
                .map(function (entry) {
                    return {
                        name: truncate(entry.name || '', 1000),
                        initiatorType: entry.initiatorType || '',
                        duration: entry.duration || 0
                    };
                });
        }

        enqueue('window_load_snapshot', {
            scriptCount: document.scripts ? document.scripts.length : 0,
            scripts: Array.prototype.map.call(document.scripts || [], describeScript),
            resourceScripts: resourceScripts
        });
        flush();
    });

    document.addEventListener('DOMContentLoaded', function () {
        enqueue('dom_content_loaded', {
            scriptCount: document.scripts ? document.scripts.length : 0
        });
    });

    window.addEventListener('beforeunload', flush);
    window.setTimeout(flush, 4000);
})();
JS
        );
    }
}
add_action('admin_enqueue_scripts', 'bornado_cookieyes_widgets_debug_enqueue_client_probe', 1000);

if (!function_exists('bornado_cookieyes_widgets_debug_notice')) {
    /**
     * Show operators where the widgets CookieYes debug log is written.
     *
     * @return void
     */
    function bornado_cookieyes_widgets_debug_notice()
    {
        if (
            !bornado_cookieyes_widgets_debug_is_enabled()
            || !bornado_cookieyes_widgets_debug_is_widgets_screen()
        ) {
            return;
        }

        $log_path = bornado_cookieyes_widgets_debug_log_path();
        if ($log_path === '') {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('CookieYes widgets debug is active for this request. A structured log entry was written to:', 'adforest-child');
        echo ' <code>' . esc_html($log_path) . '</code>';
        echo '<br />';
        echo esc_html__('Request ID:', 'adforest-child');
        echo ' <code>' . esc_html(bornado_cookieyes_widgets_debug_request_id()) . '</code>';
        echo '</p></div>';
    }
}
add_action('admin_notices', 'bornado_cookieyes_widgets_debug_notice');
