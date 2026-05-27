<?php
function sbchat_basic_auth_permission_callback() {
    if (is_user_logged_in()) {
        return true;
    }

    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        return new WP_Error('unauthorized', __('Missing authorization headers', 'adforest'), ['status' => 401]);
    }

    $username = sanitize_text_field($_SERVER['PHP_AUTH_USER']);
    $password = $_SERVER['PHP_AUTH_PW'];

    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        return new WP_Error('unauthorized', __('Invalid credentials', 'adforest'), ['status' => 403]);
    }

    wp_set_current_user($user->ID);

    return true;
}