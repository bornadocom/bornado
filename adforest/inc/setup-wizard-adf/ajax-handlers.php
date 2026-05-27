<?php
// File: inc/setup-wizard-adf/ajax-handlers.php

namespace Adforest\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Core: load step via AJAX, enforce license requirement
add_action( 'wp_ajax_adforest_get_step', __NAMESPACE__ . '\load_step_ajax' );
function load_step_ajax() {
    check_ajax_referer( 'adforest_wizard_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied', 'adforest' ) ], 403 );
    }

    $step = sanitize_text_field( $_POST['step'] ?? '' );
    $allowed_steps = array_keys( ( new Adforest_Setup_Wizard() )->steps );
    if ( ! in_array( $step, $allowed_steps, true ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid step', 'adforest' ) ] );
    }

    // If license step is done or skipped, allow subsequent
    $license_status = get_option( 'adforest_license_status', '' );
    $license_required = 'license' !== $step && 'valid' !== $license_status;
    if ( $license_required ) {
        wp_send_json_error( [ 'redirect' => 'license' ] );
    }

    // Capture output of the view
    ob_start();
    include __DIR__ . "/../views/step-{$step}.php";
    $html = ob_get_clean();

    wp_send_json_success( [ 'html' => $html ] );
}

// Include other handlers
// require_once __DIR__ . '/demo-handlers.php';
// require_once __DIR__ . '/plugins-handlers.php';
// require_once __DIR__ . '/class-license-handler.php';


require_once __DIR__.'/ajax-handlers/demo-handlers.php';
require_once __DIR__.'/ajax-handlers/license-handlers.php';
require_once __DIR__.'/ajax-handlers/plugins-handlers.php';

// AJAX: Skip license (mark as skipped)
add_action( 'wp_ajax_adforest_skip_license', function() {
    check_ajax_referer( 'adforest_wizard_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied', 'adforest' ) ], 403 );
    }
    update_option( 'adforest_license_status', 'skipped' );
    do_action( 'adforest_license_activated' );
    wp_send_json_success();
} );
