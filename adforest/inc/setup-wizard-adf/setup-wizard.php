<?php
// File: inc/setup-wizard-adf/setup-wizard.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Adforest_Setup_Wizard {

    protected $steps = [];

    public function __construct() {

        $this->steps = [
            'welcome'      => esc_html__( 'Welcome', 'adforest' ),
            'license'      => esc_html__( 'License Activation', 'adforest' ),
            'requirements' => esc_html__( 'Requirements', 'adforest' ),
            'plugins'      => esc_html__( 'Install Plugins', 'adforest' ),
            'demo'         => esc_html__( 'Import Demo', 'adforest' ),
            'done'         => esc_html__( 'Complete', 'adforest' ),
        ];

        add_action( 'after_switch_theme',        [ $this, 'redirect_to_wizard' ] );
        add_action( 'admin_menu',                [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts',     [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_adforest_get_step', [ $this, 'get_step_callback' ] );
        add_action( 'admin_init', [ $this, 'maybe_load_tgm' ] );
        // Load TGM for plugin management and update checking
        if ( ! class_exists( 'TGM_Plugin_Activation' ) ) 
        {
            require __DIR__ . '/tgm/tgm-plugin-activation.php';
        }          
        
        require_once __DIR__ . '/ajax-handlers.php';
        require_once __DIR__ . '/class-export-widgets.php';


        
    }


    public function add_menu() {
        add_theme_page( esc_html__( 'AdForest Setup Wizard', 'adforest' ), esc_html__( 'AdForest Setup Wizard', 'adforest' ), 'manage_options', 'adforest-setup-wizard', [ $this, 'wizard_page' ] );
    }

    //will start work from here
    public static function maybe_load_tgm() {
        // Ensure TGM is properly initialized
        if ( ! function_exists( 'tgmpa' ) ) {
            return;
        }
        
        // Force TGM to register plugins
        do_action( 'tgmpa_register' );
    }    
    public static function get_plugins() {
        return adforest_register_required_plugins_list();
    }


    public function enqueue_assets() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'appearance_page_adforest-setup-wizard' ) {
            return;
        }

        $base = get_template_directory_uri() . '/inc/setup-wizard-adf/assets';

        wp_enqueue_style( 'adforest-wizard-css',       "$base/css/wizard.css",        [], null );
        wp_enqueue_style( 'adforest-license-css',      "$base/css/license.css",       [], null );
        wp_enqueue_style( 'adforest-requirements-css', "$base/css/requirements.css",  [], null );
        wp_enqueue_style( 'adforest-plugins-css',      "$base/css/plugins.css",       [], null );
        wp_enqueue_style( 'adforest-demos-css',        "$base/css/demos.css",         [], null );
        wp_enqueue_style( 'adforest-done-css',         "$base/css/done.css",          [], null );

        wp_enqueue_script( 'adforest-wizard-js',        "$base/js/wizard.js",         [ 'jquery' ], null, true );
        wp_enqueue_script( 'adforest-license-js',       "$base/js/license.js",        [ 'jquery', 'adforest-wizard-js' ], null, true );
        wp_enqueue_script( 'adforest-plugins-js',       "$base/js/plugins.js",        [ 'jquery' ], null, true );
        wp_enqueue_script( 'adforest-demos-js',         "$base/js/demos.js",          [ 'jquery' ], null, true );
        wp_enqueue_script( 'adforest-requirements-js',  "$base/js/requirements.js",   [ 'jquery' ], null, true );

        wp_localize_script( 'adforest-demos-js', 'adforestWizard', [
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'adforest_wizard_nonce' ),
            'stepsNew'          => [ 'content','widgets','options' ],
            'stepsExisting'     => [ 'new-pages','new-media','new-widgets','new-theme-options' ],
            'importAllWarning'  => esc_html__( 'Please select at least one part to import.', 'adforest' ),
            'labels'            => [
                'importing'    => esc_html__( 'Importing', 'adforest' ),
                'complete'     => esc_html__( 'All done!', 'adforest' ),
                'stalled'      => esc_html__( 'Import stalled—please retry.', 'adforest' ),
                'retry'        => esc_html__( 'Retry', 'adforest' ),
                'ajaxError'    => esc_html__( 'AJAX Error', 'adforest' ),
                'close'        => esc_html__( 'Close', 'adforest' ),
                'of'           => esc_html__( 'of', 'adforest' ),
                'parts'        => [
                    'content' => esc_html__( 'Content XML',   'adforest' ),
                    'widgets' => esc_html__( 'Widgets',       'adforest' ),
                    'options' => esc_html__( 'Theme Options', 'adforest' ),
                ],
            ],
            // reuse license strings for license.js
            'license_status'    => get_option( 'adforest_license_status', '' ),
            'license'           => [
                'enter_code'        => esc_html__( 'Please enter your purchase code.',         'adforest' ),
                'invalid_code'      => esc_html__( 'That purchase code is invalid.',         'adforest' ),
                'error'             => esc_html__( 'Unexpected error. Please try later.',   'adforest' ),
                'success'           => esc_html__( 'License activated successfully!',       'adforest' ),
                'confirm_deactivate'=> esc_html__( 'Are you sure you want to deactivate?', 'adforest' ),
            ],
            'lockedMessage'     => esc_html__( 'Locked – reset DB to import.', 'adforest' ),
        ] );

        // **New** localization object for demos.js: sb_vars
        wp_localize_script( 'adforest-demos-js', 'sb_vars', [
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'adforest_wizard_nonce' ),
            'labels'      => [
                'importBTNConfirm'  => esc_html__( 'Are you sure you want to start the import? This may overwrite existing data.', 'adforest' ),
                'ajaxError'    => esc_html__( 'AJAX Error', 'adforest' ),
                'stalled'      => esc_html__( 'Import stalled—please retry.', 'adforest' ),
                'complete'     => esc_html__( 'All done!', 'adforest' ),
                'import'       => esc_html__( 'Import This Demo', 'adforest' ),
                'resetConfirm' => esc_html__( 'Are you sure you want to reset demo import?', 'adforest' ),
                'resetDone'    => esc_html__( 'Demo import has been reset.', 'adforest' ),
                'parts'        => [
                    'content' => esc_html__( 'Content XML',   'adforest' ),
                    'widgets' => esc_html__( 'Widgets',       'adforest' ),
                    'options' => esc_html__( 'Theme Options', 'adforest' ),
                ],
            ],
        ] );

        
    }


    /**  AJAX: Return a single step's HTML */
    public function get_step_callback() {
        check_ajax_referer( 'adforest_wizard_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) )
            wp_send_json_error( 'Unauthorized', 403 );

        $step = sanitize_key( $_POST['step'] ?? '' );
        if ( ! isset( $this->steps[ $step ] ) )
            wp_send_json_error( 'Invalid step', 400 );

        ob_start();
        printf('<section id="%1$s" class="sb-wizard-panel step active" role="tabpanel" aria-labelledby="%1$s-heading">', esc_attr( $step ));
        include __DIR__ . "/views/step-{$step}.php";
        echo '</section>';
        $html = ob_get_clean();
        wp_send_json_success( [ 'html' => $html, 'step' => $step ] );
    }

    public function wizard_page() {
        echo '<div id="sb-setup-wizard" class="sb-setup-wizard">';
        echo '<aside class="sb-wizard-sidebar" role="navigation" aria-label="'.esc_attr__( 'Setup Steps','adforest').'">';
        echo '<ol class="sb-wizard-steps" role="tablist">';
        $idx = 0;
        foreach ( $this->steps as $key => $label ) {
            $idx++;
            $tab_id    = "tab-{$key}";
            $selected  = $idx === 1 ? ' aria-selected="true" class="active"' : ' aria-selected="false"';
            printf('<li role="tab" id="%1$s" data-step="%2$s" data-step-index="%3$d"%4$s>%5$s</li>', esc_attr( $tab_id ), esc_attr( $key ), $idx, $selected, esc_html( $label ) );
        }
        echo '</ol>';
        echo '<div class="sb-wizard-progress"><div class="sb-wizard-progress-bar" style="width: 16.67%"></div></div>';
        echo '</aside>';

        // Main: only first step initially
        echo '<main class="sb-wizard-content">';
        reset( $this->steps );
        $first = key( $this->steps );
        printf('<section id="%1$s" class="sb-wizard-panel step active" role="tabpanel" aria-labelledby="%1$s-heading">', esc_attr( $first ) );
        include __DIR__ . "/views/step-{$first}.php";
        echo '</section>';
        echo '</main>';
        echo '</div>';
    }

    public function redirect_to_wizard() {
        if ( ! get_option( 'adforest_license_status' ) ) {
            wp_safe_redirect( admin_url( 'themes.php?page=adforest-setup-wizard' ) );
        }
    }
}


if( ! function_exists( 'adforest_register_required_plugins_list' ) ) {
    function adforest_register_required_plugins_list() {

        $plugins = array(
            array(
                'name' => esc_html__('Elementor Website Builder', 'adforest'),
                'slug' => 'elementor',
                'source' => '',
                'required' => true,
                'version' => '3.20.0',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => esc_url('https://downloads.wordpress.org/plugin/elementor.zip'),
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('Redux Framework', 'adforest'),
                'slug' => 'redux-framework',
                'source' => '',
                'required' => true,
                'version' => '4.4.0',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => esc_url('https://downloads.wordpress.org/plugin/redux-framework.zip'),
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('Woocommerce', 'adforest'), // The plugin name.
                'slug' => 'woocommerce',
                'source' => '',
                'required' => true,
                'version' => '8.0.0',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => esc_url('https://downloads.wordpress.org/plugin/woocommerce.9.8.5.zip'),
                'is_callable' => '',
            ),
           array(
               'name' => esc_html__('Multivendor Marketplace Solution for WooCommerce', 'adforest'),
               'slug' => 'dc-woocommerce-multi-vendor',
               'source' => '',
               'required' => false,
               'version' => '4.0.0',
               'force_activation' => false,
               'force_deactivation' => false,
               'external_url' => esc_url('https://downloads.wordpress.org/plugin/dc-woocommerce-multi-vendor.zip'),
               'is_callable' => '',
           ),
            array(
                'name' => esc_html__('Sb-directory for Events and Directory', 'adforest'),
                'slug' => 'sb-directory',
                'source' => get_theme_file_path( 'inc/setup-wizard-adf/plugins/sb-directory.zip' ),
                'required' => false,
                'version' => '2.1',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => '',
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('Adforest Framework', 'adforest'),
                'slug' => 'adforest-framework',
                'source' => get_theme_file_path( 'inc/setup-wizard-adf/plugins/adforest-framework.zip' ),
                'required' => true,
                'version' => '5.1',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => '',
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('Adforest elementor', 'adforest'),
                'slug' => 'adforest-elementor',
                'source' => get_theme_file_path( 'inc/setup-wizard-adf/plugins/adforest-elementor.zip'),
                'required' => true,
                'version' => '3.1',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => '',
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('SB Chat', 'adforest'),
                'slug' => 'sb_chat',
                'source' => get_theme_file_path( 'inc/setup-wizard-adf/plugins/sb_chat.zip'),
                'required' => true,
                'version' => '2.1',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => '',
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('Contact Form 7', 'adforest'),
                'slug' => 'contact-form-7',
                'source' => '',
                'required' => true,
                'version' => '5.7.0',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => esc_url('https://downloads.wordpress.org/plugin/contact-form-7.zip'),
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('Image Watermark', 'adforest'),
                'slug' => 'image-watermark',
                'source' => '',
                'required' => false,
                'version' => '1.7.0',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => esc_url('https://downloads.wordpress.org/plugin/image-watermark.zip'),
                'is_callable' => '',
            ),
            array(
                'name' => esc_html__('AddToAny Share Buttons', 'adforest'),
                'slug' => 'add-to-any',
                'source' => '',
                'required' => false,
                'version' => '1.8.0',
                'force_activation' => false,
                'force_deactivation' => false,
                'external_url' => esc_url('https://downloads.wordpress.org/plugin/add-to-any.zip'),
                'is_callable' => '',
            ),
        );

        return $plugins;
    }
}

if( ! function_exists( 'adforest_register_required_plugins' ) ) {
    function adforest_register_required_plugins() {
        // Check if TGM is properly loaded and available
        if ( ! function_exists( 'tgmpa' ) ) {
            return;
        }
        
        // Only register once
        static $registered = false;
        if ( $registered ) {
            return;
        }
        $registered = true;
        
        $plugins = adforest_register_required_plugins_list();
        $config = apply_filters( 'adforest_tgmpa_configs_plugins', array(
                'default_path' => '',                     
                'menu'         => 'tgmpa-install-plugins1',
                'has_notices'  => true,                    
                'dismissable'  => true,                    
                'dismiss_msg'  => '',                    
                'is_automatic' => false,                   
                'message'      => '',                     
                'strings'      => array(
                    'page_title'                      => esc_html__( 'Install Required Plugins', 'adforest' ),
                    'menu_title'                      => esc_html__( 'Install Plugins', 'adforest' ),
                    'installing'                      => esc_html__( 'Installing Plugin: %s', 'adforest' ),
                    'updating'                        => esc_html__( 'Updating Plugin: %s', 'adforest' ),
                    'oops'                            => esc_html__( 'Something went wrong with the plugin API.', 'adforest' ),
                    'notice_can_install_required'     => _n_noop(
                        'This theme requires the following plugin: %1$s.',
                        'This theme requires the following plugins: %1$s.',
                        'adforest'
                    ),
                    'notice_can_install_recommended'  => _n_noop(
                        'This theme recommends the following plugin: %1$s.',
                        'This theme recommends the following plugins: %1$s.',
                        'adforest'
                    ),
                    'notice_ask_to_update'            => _n_noop(
                        'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
                        'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
                        'adforest'
                    ),
                    'notice_ask_to_update_maybe'      => _n_noop(
                        'There is an update available for: %1$s.',
                        'There are updates available for the following plugins: %1$s.',
                        'adforest'
                    ),
                    'notice_can_activate_required'    => _n_noop(
                        'The following required plugin is currently inactive: %1$s.',
                        'The following required plugins are currently inactive: %1$s.',
                        'adforest'
                    ),
                    'notice_can_activate_recommended' => _n_noop(
                        'The following recommended plugin is currently inactive: %1$s.',
                        'The following recommended plugins are currently inactive: %1$s.',
                        'adforest'
                    ),
                    'install_link'                    => _n_noop(
                        'Begin installing plugin',
                        'Begin installing plugins',
                        'adforest'
                    ),
                    'update_link'                     => _n_noop(
                        'Begin updating plugin',
                        'Begin updating plugins',
                        'adforest'
                    ),
                    'activate_link'                   => _n_noop(
                        'Begin activating plugin',
                        'Begin activating plugins',
                        'adforest'
                    ),
                    'return'                          => esc_html__( 'Return to Required Plugins Installer', 'adforest' ),
                    'plugin_activated'                => esc_html__( 'Plugin activated successfully.', 'adforest' ),
                    /* translators: appears in the AdForest setup-wizard step that activates required plugins; context isolates it from the identical singular form of TGMPA's plural string. */
                    'activated_successfully'          => esc_html_x( 'The following plugin was activated successfully:', 'setup wizard', 'adforest' ),
                    'plugin_already_active'           => esc_html__( 'No action taken. Plugin %1$s was already active.', 'adforest' ),
                    'plugin_needs_higher_version'     => esc_html__( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'adforest' ),
                    'plugin_updated'                  => esc_html__( 'Plugin updated successfully.', 'adforest' ),
                    'complete'                        => esc_html__( 'All plugins installed and activated successfully. %1$s', 'adforest' ),
                    'dismiss'                         => esc_html__( 'Dismiss this notice', 'adforest' ),
                    'notice_cannot_install_activate'  => esc_html__( 'There are one or more required or recommended plugins to install, update or activate.', 'adforest' ),
                    'contact_admin'                   => esc_html__( 'Please contact the administrator of this site for help.', 'adforest' ),
                ),
            )
        );
        tgmpa( $plugins, $config );
    }
    
    // Register TGM plugins
    add_action( 'tgmpa_register', 'adforest_register_required_plugins' );
}

//add_action('wp_loaded', function () {
//    if ( get_option('adforest_elementor_safe_fix_done') == 1) return;
//
//    return;
//    $pages = get_posts([
//        'post_type'      => 'page',
//        'post_status'    => 'publish',
//        'posts_per_page' => -1,
//        'meta_query'     => [
//            [
//                'key'     => '_elementor_data',
//                'compare' => 'EXISTS',
//            ]
//        ]
//    ]);
//
//    if(isset($pages) && count($pages) > 0){
//        foreach ( $pages as $page ) {
//            $page_id = $page->ID;
//            $elementor_data = get_post_meta( $page_id, '_elementor_data', true );
//
//            // Set Elementor required meta
//            update_post_meta($page_id, '_elementor_edit_mode', 'builder');
//            update_post_meta($page_id, '_elementor_template_type', 'page');
//
//            // Inject placeholder content if empty
//            if ( empty( $page->post_content ) ) {
//                wp_update_post([
//                    'ID'           => $page_id,
//                    'post_content' => '[elementor-template]',
//                ]);
//            }
//
//            // Regenerate CSS (safe fallback)
//            if ( class_exists('\Elementor\Plugin') ) {
//                $files_manager = \Elementor\Plugin::$instance->files_manager;
//
//                if ( method_exists($files_manager, 'clear_cache') ) {
//                    $files_manager->clear_cache();
//                }
//
//                if ( method_exists($files_manager, 'regenerate_files') ) {
//                    $files_manager->regenerate_files($page_id);
//                }
//            }
//        }
//    }
//
//    update_option('adforest_elementor_safe_fix_done','1');
//
//});






if ( ! function_exists( 'adforest_redirect_if_purchase_code_missing' ) ) {
    function adforest_redirect_if_purchase_code_missing() {
        global $pagenow;

        // Prevent redirect loop on the setup wizard itself
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'adforest-setup-wizard' ) {
            return;
        }

        // Allow access to themes.php and plugins.php (so admins can install/update) 'plugins.php',
        if ( in_array( $pagenow, array( 'themes.php', 'theme-editor.php' ), true ) ) {
            return;
        }

        // Only run in wp-admin (not on AJAX, REST, or CLI)
        if ( ! is_admin() || defined( 'DOING_AJAX' ) || defined( 'WP_CLI' ) || defined( 'REST_REQUEST' ) ) {
            return;
        }

        // Check license status (adjust option name if needed)
        $license_status = get_option( 'adforest_license_status' );

        // If not valid, redirect to the setup wizard
        if ( $license_status !== 'valid' ) {
            wp_safe_redirect( admin_url( 'themes.php?page=adforest-setup-wizard' ) );
            exit;
        }
    }
    add_action( 'admin_init', 'adforest_redirect_if_purchase_code_missing' );
}




// Ensure TGM notices are displayed
if ( ! function_exists( 'adforest_ensure_tgm_notices' ) ) {
    function adforest_ensure_tgm_notices() {
        // Force TGM to check for updates
        if ( function_exists( 'tgmpa' ) ) {
            // Clear any cached plugin data
            delete_transient( 'tgmpa_plugin_updates' );
            
            // Force TGM to recheck plugin status
            do_action( 'tgmpa_register' );
        }
    }
    add_action( 'admin_init', 'adforest_ensure_tgm_notices', 20 );
}

// Debug function to check TGM status (remove in production)
if ( ! function_exists( 'adforest_debug_tgm_status' ) ) {
    function adforest_debug_tgm_status() {
        if ( current_user_can( 'manage_options' ) && isset( $_GET['debug_tgm'] ) ) {
            echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
            echo '<h3>TGM Debug Information</h3>';
            echo '<p><strong>TGM Class exists:</strong> ' . ( class_exists( 'TGM_Plugin_Activation' ) ? 'Yes' : 'No' ) . '</p>';
            echo '<p><strong>tgmpa function exists:</strong> ' . ( function_exists( 'tgmpa' ) ? 'Yes' : 'No' ) . '</p>';
            
            if ( function_exists( 'tgmpa' ) ) {
                global $tgmpa;
                if ( isset( $tgmpa ) ) {
                    echo '<p><strong>TGM Instance exists:</strong> Yes</p>';
                    echo '<p><strong>Registered plugins:</strong> ' . count( $tgmpa->plugins ) . '</p>';
                    
                    foreach ( $tgmpa->plugins as $plugin ) {
                        echo '<p>- ' . $plugin['name'] . ' (v' . $plugin['version'] . ')</p>';
                    }
                } else {
                    echo '<p><strong>TGM Instance exists:</strong> No</p>';
                }
            }
            echo '</div>';
        }
    }
    add_action( 'admin_notices', 'adforest_debug_tgm_status' );
}

new Adforest_Setup_Wizard();