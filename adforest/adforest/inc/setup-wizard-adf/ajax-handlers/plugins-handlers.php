<?php
// File: inc/setup-wizard-adf/ajax-handlers/plugins-handlers.php
namespace Adforest\SetupWizard;
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once get_template_directory() . '/inc/setup-wizard-adf/setup-wizard.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php'; 

/* Initialize the WP_Filesystem, so Plugin_Upgrader can write files. */
function adforest_demo_import_init_filesystem() {
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    \WP_Filesystem();
}

/* Batch install & activate all selected plugins, returning detailed errors. */
function adforest_install_plugins() {
    check_ajax_referer( 'adforest_wizard_nonce', 'nonce' );

    if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
        wp_send_json_error( [ 'message' => __( 'You do not have permission.', 'adforest' ) ], 403 );
    }

    // ensure get_plugin_data() exists
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    adforest_demo_import_init_filesystem();

    $upgrader    = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
    $all_plugins = get_plugins(); // [ 'plugin-folder/plugin-file.php' => metadata ]

    // — restrict to single slug if only one requested
    $to_install = (array) ( $_POST['install'] ?? [] );
    $plugins    = \Adforest_Setup_Wizard::get_plugins();

    if ( count( $to_install ) === 1 ) {
        $requested = sanitize_key( key( $to_install ) );
        $plugins   = array_filter( $plugins, function( $p ) use ( $requested ) {
            return sanitize_key( $p['slug'] ) === $requested;
        } );
    }

    $results = [];

    foreach ( $plugins as $plugin ) {
        $slug     = sanitize_key( $plugin['slug'] );
        $required = ! empty( $plugin['required'] );
        $install  = ! empty( $to_install[ $slug ] );

        // Skip optional if not selected
        if ( ! $required && ! $install ) {
            $results[ $slug ] = 'skipped';
            continue;
        }

        // Try to locate plugin file based on slug
        $file_path = null;
        foreach ( $all_plugins as $plugin_file => $meta ) {
            if ( strpos( $plugin_file, "{$slug}/" ) === 0 || $plugin_file === "{$slug}.php" ) {
                $file_path = $plugin_file;
                break;
            }
        }

        // Install if missing
        if ( ! $file_path ) {
            if ( ! empty( $plugin['source'] ) && file_exists( $plugin['source'] ) ) {
                $source = $plugin['source'];
            } elseif ( ! empty( $plugin['external_url'] ) ) {
                $source = $plugin['external_url'];
            } else {
                $source = "https://downloads.wordpress.org/plugin/{$slug}.zip";
            }

            $install_result = $upgrader->install( $source );
            if ( is_wp_error( $install_result ) || ! $install_result ) {
                $errors = is_wp_error( $install_result )
                    ? $install_result->get_error_messages()
                    : [ __( 'Installation failed for unknown reasons.', 'adforest' ) ];
                $results[ $slug ] = [
                    'status' => 'failed',
                    'errors' => $errors,
                ];
                continue;
            }

            // Refresh plugin list after install
            $all_plugins = get_plugins();
            foreach ( $all_plugins as $plugin_file => $meta ) {
                if ( strpos( $plugin_file, "{$slug}/" ) === 0 || $plugin_file === "{$slug}.php" ) {
                    $file_path = $plugin_file;
                    break;
                }
            }
        }

        if ( ! $file_path ) {
            $results[ $slug ] = [
                'status' => 'failed',
                'errors' => [ __( 'Could not determine plugin entry file.', 'adforest' ) ],
            ];
            continue;
        }

        // Already active?
        if ( is_plugin_active( $file_path ) ) {
            $data = get_plugin_data( WP_PLUGIN_DIR . "/{$file_path}" );
            $results[ $slug ] = [
                'status'  => 'active',
                'version' => $data['Version'] ?? '',
            ];
            continue;
        }

        // Activate
        $activate = activate_plugin( $file_path );
        if ( is_wp_error( $activate ) ) {
            $results[ $slug ] = [
                'status' => 'installed',
                'errors' => $activate->get_error_messages(),
            ];
        } else {
            $data = get_plugin_data( WP_PLUGIN_DIR . "/{$file_path}" );
            $results[ $slug ] = [
                'status'  => 'active',
                'version' => $data['Version'] ?? '',
            ];
        }
    }

    wp_send_json_success( $results );
}


/**
 * Return installed/active/version status for each plugin.
 */
function adforest_check_plugins() {
    check_ajax_referer( 'adforest_wizard_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied', 'adforest' ) ], 403 );
    }

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $all    = \get_plugins();
    $files  = (array) ( $_POST['plugins'] ?? [] );
    $status = [];

    foreach ( $files as $file ) {
        $status[ $file ] = [
            'installed' => isset( $all[ $file ] ),
            'active'    => \is_plugin_active( $file ),
            'version'   => isset( $all[ $file ] ) ? $all[ $file ]['Version'] : '',
        ];
    }

    wp_send_json_success( $status );
}

// Hook AJAX actions
add_action( 'wp_ajax_adforest_install_plugins', __NAMESPACE__ . '\\adforest_install_plugins' );
add_action( 'wp_ajax_adforest_check_plugins',   __NAMESPACE__ . '\\adforest_check_plugins' );
