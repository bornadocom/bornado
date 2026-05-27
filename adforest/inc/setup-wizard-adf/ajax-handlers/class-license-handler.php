<?php
// File: inc/setup-wizard-adf/class-license-handler.php

namespace Adforest\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) exit;

class Adforest_License_Handler {
    /**
     * Activate (store) a validated license.
     */
    public static function adforest_activate( $code, $token = '', $dev = false ) {
        update_option( 'adforest_license_key',      sanitize_text_field( $code ) );
        update_option( 'adforest_license_token',    sanitize_text_field( $token ) );
        update_option( 'adforest_license_activated', 1 );
        update_option( 'adforest_license_dev',       $dev ? 1 : 0 );
        update_option( 'adforest_license_status',   'valid' );
    }

    /**
     * Deactivate (remove) stored license.
     */
    public static function adforest_deactivate() {
        delete_option( 'adforest_license_key' );
        delete_option( 'adforest_license_token' );
        delete_option( 'adforest_license_activated' );
        delete_option( 'adforest_license_dev' );
        update_option( 'adforest_license_status', 'deactivated' );
    }
}
