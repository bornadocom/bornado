<?php
namespace Adforest\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-license-handler.php';

/**
 * Verify & activate license via AJAX.
 */
add_action( 'wp_ajax_adforest_verify_license', __NAMESPACE__ . '\\adforest_verify_license' );
function adforest_verify_license() {
    check_ajax_referer( 'adforest_wizard_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'adforest' ) ], 403 );
    }

    $code = sanitize_text_field( $_POST['license_key'] ?? '' );
    if ( empty( $code ) ) {
        wp_send_json_error( [ 'message' => __( 'Please provide a license key.', 'adforest' ) ], 400 );
    }

    // Build request
    $response = wp_remote_post( 'https://authenticate.scriptsbundle.com/adforest/tm-re-verify.php', [
        'method'      => 'POST',
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => [ 'theme-sb-validation' => true ],
        'body'        => [
            'purchase_code' => $code,
            'id'            => get_option( 'admin_email' ),
            'url'           => home_url(),
            'theme_name'    => 'Adforest',
        ],
    ] );

    // error_log("Activate License Response: " . print_r($response, true));

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [
            'message' => esc_html__( "Could not verify your purchase code. Please check your internet connection or firewall settings to allow outbound requests from your server. If the problem persists, contact support.", 'adforest' )
        ], 500 );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data ) || ! isset( $data['success'] ) ) {
        wp_send_json_error( [
            'message' => __( 'Unexpected response from the API server. Please try again later.', 'adforest' )
        ], 500 );
    }

    if ( (int) $data['success'] === 1 && (int) ($data['status'] ?? 0) === 1 ) {
        $token = sanitize_text_field( $data['token'] ?? '' );
        Adforest_License_Handler::adforest_activate( $code, $token );
        wp_send_json_success( [ 'message' => $data['message'] ?? __( 'License activated.', 'adforest' ) ] );
    }

    // any other case is failure
    $msg = $data['message'] ?? __( 'License verification failed.', 'adforest' );
    wp_send_json_error( [ 'message' => $msg ], 400 );
}

/**
 * Deactivate license via AJAX.
 */
add_action( 'wp_ajax_adforest_deactivate_license', __NAMESPACE__ . '\\adforest_deactivate_license' );
function adforest_deactivate_license() {
    check_ajax_referer( 'adforest_wizard_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'adforest' ) ], 403 );
    }

    // error_log("License Token: " . get_option( 'adforest_license_token' ));
    // error_log("License Code: " . get_option( 'adforest_license_key' ));

    $info = array(
        'method'      => 'POST',
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => array( 'theme-sb-validation' => true ),
        'body'        => array(
            'purchase_code' => get_option( 'adforest_license_key' ),
            'id'            => get_option( 'admin_email' ),
            'url'           => get_option( 'siteurl' ),
            'theme_name'    => 'Adforest',
            'token' => get_option( 'adforest_license_token' ),
            'request-type'  => 'deactive'
        ),
        'cookies'     => array()
    );
    $url = 'https://authenticate.scriptsbundle.com/adforest/tm-re-verify.php';
    $response = wp_remote_post( $url, $info );
    // error_log("Deactivate License Response: " . print_r($response, true));

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        wp_send_json_error( [ 'message' => __( "Something went wrong: " . esc_html($error_message), 'adforest' ) ], 400 );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! empty( $data ) ) {
        if ( isset( $data['status'] ) && ( $data['status'] === false || $data['status'] === 0 || $data['status'] === '0' ) ) {
            $msg = $data['message'] ?? __( 'License deactivation failed.', 'adforest' );
            wp_send_json_error( [ 'message' => $msg ], 400 );
        }
    }

    Adforest_License_Handler::adforest_deactivate();
    wp_send_json_success( [ 'message' => __( 'License deactivated.', 'adforest' ) ] );
}
