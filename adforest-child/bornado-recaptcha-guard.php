<?php

if (!function_exists('bornado_recaptcha_guard_should_buffer_response')) {
    /**
     * Limit HTML buffering to public frontend requests where AdForest has already
     * enqueued the global reCAPTCHA loader.
     *
     * @return bool
     */
    function bornado_recaptcha_guard_should_buffer_response()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || wp_is_json_request()
            || is_feed()
            || is_robots()
            || is_trackback()
        ) {
            return false;
        }

        return wp_script_is('recaptcha', 'enqueued');
    }
}

if (!function_exists('bornado_should_disable_recaptcha_for_request')) {
    /**
     * Disable AdForest's global reCAPTCHA loader on request types that never use
     * auth/contact forms, especially Bornado's ad-search views.
     *
     * @return bool
     */
    function bornado_should_disable_recaptcha_for_request()
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST)
            || wp_is_json_request()
        ) {
            return false;
        }

        return function_exists('bornado_is_ad_search_view') && bornado_is_ad_search_view();
    }
}

if (!function_exists('bornado_disable_global_recaptcha_loader')) {
    /**
     * Remove the global `recaptcha` handle on views where Bornado never uses it.
     *
     * @return void
     */
    function bornado_disable_global_recaptcha_loader()
    {
        if (!bornado_should_disable_recaptcha_for_request()) {
            return;
        }

        wp_dequeue_script('recaptcha');
        wp_deregister_script('recaptcha');
    }
}

if (!function_exists('bornado_page_markup_uses_recaptcha')) {
    /**
     * Detect whether the rendered HTML actually contains known AdForest auth/contact
     * forms or visible reCAPTCHA markup.
     *
     * @param string $html Full page HTML.
     * @return bool
     */
    function bornado_page_markup_uses_recaptcha($html)
    {
        if (!is_string($html) || $html === '') {
            return false;
        }

        $needles = array(
            'id="adforest_login_user"',
            "id='adforest_login_user'",
            'id="adforest-signup-form"',
            "id='adforest-signup-form'",
            'id="user_contact_form"',
            "id='user_contact_form'",
            'class="g-recaptcha"',
            "class='g-recaptcha'",
            'data-sitekey=',
            'sb_google_captcha3_verification_nonce',
        );

        foreach ($needles as $needle) {
            if (stripos($html, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_strip_unused_recaptcha_loader')) {
    /**
     * Remove the global Google reCAPTCHA loader from pages that do not render any
     * matching form/markup, so console-only PAT noise does not appear site-wide.
     *
     * @param string $html Full page HTML.
     * @return string
     */
    function bornado_strip_unused_recaptcha_loader($html)
    {
        if (!is_string($html) || $html === '' || stripos($html, 'recaptcha/api.js') === false) {
            return $html;
        }

        if (bornado_should_disable_recaptcha_for_request()) {
            $patterns = array(
                '#<script\b[^>]*\bsrc=(["\'])(?:https?:)?//www\.google\.com/recaptcha/api\.js[^"\']*\1[^>]*>\s*</script>#i',
                '#<script\b[^>]*\bsrc=(["\'])(?:https?:)?//www\.gstatic\.com/recaptcha/[^"\']*\1[^>]*>\s*</script>#i',
            );

            $stripped_html = preg_replace($patterns, '', $html);

            return is_string($stripped_html) ? $stripped_html : $html;
        }

        if (bornado_page_markup_uses_recaptcha($html)) {
            return $html;
        }

        $patterns = array(
            '#<script\b[^>]*\bsrc=(["\'])(?:https?:)?//www\.google\.com/recaptcha/api\.js[^"\']*\1[^>]*>\s*</script>#i',
            '#<script\b[^>]*\bsrc=(["\'])(?:https?:)?//www\.gstatic\.com/recaptcha/[^"\']*\1[^>]*>\s*</script>#i',
        );

        $stripped_html = preg_replace($patterns, '', $html);

        return is_string($stripped_html) ? $stripped_html : $html;
    }
}

add_action('template_redirect', function () {
    if (!bornado_recaptcha_guard_should_buffer_response()) {
        return;
    }

    ob_start('bornado_strip_unused_recaptcha_loader');
}, 1);

add_action('wp_enqueue_scripts', 'bornado_disable_global_recaptcha_loader', 999);
add_action('wp_print_scripts', 'bornado_disable_global_recaptcha_loader', 1);
add_action('wp_print_footer_scripts', 'bornado_disable_global_recaptcha_loader', 1);
