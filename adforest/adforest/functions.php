<?php
/**
 * adforest functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package adforest
 */

if (!defined('ADFOREST_VERSION')) {
    define('ADFOREST_VERSION', '6.0.14.28');
}

update_option('adforest_setup_status', '', false);

if (!function_exists('adforest_capture_object_to_int_warnings')) {
    function adforest_capture_object_to_int_warnings()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if ($errno === E_WARNING && strpos($errstr, 'Object of class WP_Post could not be converted to int') !== false) {
                error_log('WP_Post conversion warning at ' . $errfile . ':' . $errline . ' | Trace: ' . wp_debug_backtrace_summary());
            }

            return false;
        });
    }
    add_action('init', 'adforest_capture_object_to_int_warnings', 1);
}

add_action('after_setup_theme', 'adforest_setup');
if (!function_exists('adforest_setup')):
    function adforest_setup()
    {
        load_theme_textdomain('adforest', get_template_directory() . '/languages');
        global $adforest_theme;
        global $template;
        $page_template = $template != "" ? basename($template) : "";
        define('ADFOREST_IMAGE_PATH', get_template_directory_uri() . "/images");
        define('ADFOREST_IMAGES', get_template_directory_uri() . '/assets/images');
        if (!defined('ADFOREST_ALLOWED_FORM_HTML')) {
            define('ADFOREST_ALLOWED_FORM_HTML', [
                'form' => [
                    'id' => true,
                    'class' => true,
                    'method' => true,
                    'action' => true,
                ],
                'input' => [
                    'type' => true,
                    'class' => true,
                    'name' => true,
                    'id' => true,
                    'value' => true,
                    'required' => true,
                    'checked' => true,
                    'placeholder' => true,
                    'disabled' => true,
                ],
                'label' => [
                    'for' => true,
                    'class' => true,
                ],
                'div' => [
                    'class' => true,
                    'id' => true,
                ],
                'ul' => [
                    'class' => true,
                    'id' => true,
                ],
                'li' => [
                    'class' => true,
                ],
                'span' => [
                    'class' => true,
                ],
                'strong' => [],
                'small' => [],
                'em' => [],
                'b' => [],
                'i' => [],
                'p' => [
                    'class' => true,
                ],
                'br' => [],
                'a' => [
                    'href' => true,
                    'title' => true,
                    'class' => true,
                    'target' => true,
                    'rel' => true,
                ],
                'button' => [
                    'type' => true,
                    'class' => true,
                    'id' => true,
                ],
                'img' => [
                    'class' => true,
                    'src' => true,
                    'alt' => true,
                    'style' => true,
                ],
                'script' => [
                    'async' => true,
                    'src' => true,
                    'type' => true,
                    '' => true,
                ],

                'ins' => [
                    'class' => true,
                    'style' => true,
                    'data-ad-client' => true,
                    'data-ad-slot' => true,
                    'data-ad-format' => true,
                    'data-full-width-responsive' => true,
                ],
            ]);
        }

        $is_active_woocomerce = in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ? true : false;
        define('IS_WOOCOMMERCE_ACTIVE', $is_active_woocomerce);
        /* ------------------------------------------------ */
        /* AdSense Helper */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/adsense-helper.php';
        /* ------------------------------------------------ */
        /* Theme Utilities */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/utilities.php';
        require trailingslashit(get_template_directory()) . 'inc/theme-notifications.php';
        require trailingslashit(get_template_directory()) . 'inc/setup-wizard-adf/setup-wizard.php';
        require trailingslashit(get_template_directory()) . 'inc/adforest-footer-functions.php';
        require_once get_template_directory() . '/inc/UserPackageManager.php';
        new UserPackageManager();
        require trailingslashit(get_template_directory()) . 'inc/authentication.php';
        require trailingslashit(get_template_directory()) . 'inc/adforest_ad_post.php';
        /* ------------------------------------------------ */
        /* AI-Based Title & Description Suggestions */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/ai-suggestions.php';
        /* ------------------------------------------------ */
        /* Theme Settings */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/theme_settings.php';
        /* ------------------------------------------------ */
        /* Theme Options */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/options-init.php';
        /* ------------------------------------------------ */

        /* ------------------------------------------------ */
        /* Theme Nav */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/nav.php';
        /* ------------------------------------------------ */
        /* Shop Settings */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/shop-func.php';
        require trailingslashit(get_template_directory()) . 'inc/woo_functions.php';

        /* ------------------------------------------------ */
        /* Dashboard */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'dashboard/functions.php';

        /* ------------------------------------------------ */
        /* Wcmarket Place Vendor */
        /* ------------------------------------------------ */
        if (in_array('dc-woocommerce-multi-vendor/dc_product_vendor.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            require trailingslashit(get_template_directory()) . 'MultiVendorX/wcmarket-functions.php';
        }
        require trailingslashit(get_template_directory()) . 'inc/categories-images.php';
        /* ------------------------------------------------ */
        /* Search Widgets */
        /* ------------------------------------------------ */
        require trailingslashit(get_template_directory()) . 'inc/ads-widgets.php';
        require trailingslashit(get_template_directory()) . 'inc/widgets.php';
        adforest_set_date_timezone();
        /* for dashboard only */
    }
endif;

if (class_exists('SitePress')) {
    require trailingslashit(get_template_directory()) . 'inc/multilingual-functions.php';
}

/* Enque Scripts and style for theme  */
if (!function_exists('adforest_google_fonts_service')) {
    function adforest_google_fonts_service()
    {
        if (!is_rtl()) {
            $query_args = array('family' => 'Lato:400,700,900', 'subset' => '',);
            wp_register_style('adforest-google_fonts', add_query_arg($query_args, "//fonts.googleapis.com/css"), array(), null);
            wp_enqueue_style('adforest-google_fonts');
        }
    }
}

add_action('wp_enqueue_scripts', 'adforest_google_fonts_service');

// add_action('wp_enqueue_scripts', 'adforest_google_fonts_service');
add_action('wp_enqueue_scripts', 'adforest_scripts');
// Force core jQuery on Woo critical pages and remove any other jQuery enqueued late by plugins.
add_action('wp_enqueue_scripts', function() {
    if ( function_exists('is_woocommerce') && ( is_checkout() || is_cart() || is_account_page() ) ) {
        // Ensure WP core jQuery is present.
        wp_enqueue_script('jquery');

        // Known handles that may register their own jQuery versions.
        $third_party_jqueries = array(
            'adforest-jquery',                 // theme's bundled jQuery
            'adforest-elementor-jquery',       // hypothetical handle
        );

        foreach ( $third_party_jqueries as $handle ) {
            if ( wp_script_is( $handle, 'enqueued' ) ) {
                wp_dequeue_script( $handle );
            }
            if ( wp_script_is( $handle, 'registered' ) ) {
                wp_deregister_script( $handle );
            }
        }
    }
}, 100);

// Final safeguard: strip any plugin-bundled jQuery on Woo critical pages (very late).
add_action('wp_enqueue_scripts', function() {
    if ( function_exists('is_woocommerce') && ( is_checkout() || is_cart() || is_account_page() ) ) {
        global $wp_scripts;
        if ( ! $wp_scripts ) { return; }
        foreach ( (array) $wp_scripts->queue as $handle ) {
            if ( empty( $wp_scripts->registered[ $handle ] ) ) { continue; }
            $src = $wp_scripts->registered[ $handle ]->src;
            if ( ! $src ) { continue; }
            // Catch typical vendor jQuery loads that conflict with WooCommerce checkout.
            if (
                false !== stripos( $src, '/adforest-elementor/assets/jquery' ) ||
                false !== stripos( $src, '/assets/js/jquery-3.7.1.min.js' )
            ) {
                wp_dequeue_script( $handle );
                wp_deregister_script( $handle );
            }
        }
        // Keep core jQuery.
        wp_enqueue_script('jquery');
    }
}, 999);
if (!function_exists('adforest_scripts')) {
    function adforest_scripts()
    {
        global $adforest_theme;

        /* JS files */
        global $adforest_theme, $template;

        $page_template = $template != "" ? basename($template) : "";

        // enqueue theme fonts //
        wp_enqueue_style(
            'google-fonts-poppins',
            'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap',
            array(),
            null
        );
        // enqueue theme fonts //
        wp_enqueue_script('toastr', trailingslashit(get_template_directory_uri()) . 'assets/js/toastr.min.js', false, false, true);

        // Avoid replacing WordPress/WooCommerce jQuery on critical Woo pages (checkout/cart/account),
        // otherwise extensions like WooPayments lose blockUI and other plugins.
        $is_woo_critical = function_exists('is_woocommerce') && ( is_checkout() || is_cart() || is_account_page() );
        if ( $is_woo_critical ) {
            // Ensure core jQuery is present for theme scripts.
            wp_enqueue_script('jquery');
        } else {
            wp_enqueue_script('adforest-jquery', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery-3.7.1.min.js', [], false, true);
        }
        wp_enqueue_style('toastr-css', trailingslashit(get_template_directory_uri()) . 'assets/css/toastr.min.css');
        wp_enqueue_style('adforest-pro-font-awesome', trailingslashit(get_template_directory_uri()) . 'assets/css/font-awesome.css');
        wp_enqueue_style('flaticon', trailingslashit(get_template_directory_uri()) . 'assets/css/flaticon.css');

        wp_register_script('adforest-fancybox', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery.fancybox.min.js', array('jquery'), false, false);
        wp_enqueue_style('adforest-select2', trailingslashit(get_template_directory_uri()) . 'assets/css/select2.min.css');
        wp_enqueue_script('select2-full', trailingslashit(get_template_directory_uri()) . 'assets/js/select2.full.min.js', [], false, true);
        wp_enqueue_script('owl-carousel-min-js', trailingslashit(get_template_directory_uri()) . 'assets/js/owl.carousel.min.js', [], false, true);
        wp_register_script('nicescroll', trailingslashit(get_template_directory_uri()) . 'assets/js/nicescroll.min.js', [], false, true);

        $mapType = adforest_mapType();
        $nominatim_countrycodes = '';
        if (isset($adforest_theme['sb_location_allowed']) && !$adforest_theme['sb_location_allowed'] && isset($adforest_theme['sb_list_allowed_country']) && is_array($adforest_theme['sb_list_allowed_country'])) {
            $nominatim_countrycodes = implode(',', $adforest_theme['sb_list_allowed_country']);
        }
        $nominatim_featuretype = '';
        if (!isset($adforest_theme['sb_location_type']) || $adforest_theme['sb_location_type'] !== 'regions') {
            $nominatim_featuretype = 'city';
        }
        if ($mapType == 'leafletjs_map') {
            if (!is_rtl()) {
                wp_enqueue_style('leaflet', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet.css');
            } else {
                wp_enqueue_style('leaflet', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet-rtl.css');
            }
            wp_enqueue_style('leaflet-search', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet-search.min.css');
            wp_register_script('leaflet', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet.js', false, false, false);
            wp_register_script('leaflet-markercluster', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet.markercluster.js', false, false, false);

            wp_register_script('leaflet-search', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet-search.min.js', false, false, false);
            wp_register_script('leaflet-fullscreen', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet-fullscreen.js', false, false, false);

            wp_enqueue_style('leaflet-full', trailingslashit(get_template_directory_uri()) . 'assets/leaflet/leaflet-fullscreen.css');
            wp_enqueue_style('leaflet-modern', trailingslashit(get_template_directory_uri()) . 'assets/css/leaflet-modern.css');

            wp_enqueue_style(
                'autocomplete-css',
                trailingslashit(get_template_directory_uri()) . 'assets/css/autocomplete.min.css'
            );
            wp_register_script(
                'autocomplete-js',
                trailingslashit(get_template_directory_uri()) . 'assets/js/autocomplete.min.js',
                false,
                false,
                false
            );

            wp_enqueue_script('leaflet');
            wp_enqueue_script('leaflet-markercluster');
            wp_enqueue_script('leaflet-search');
            wp_enqueue_script('leaflet-fullscreen');
            wp_enqueue_script('autocomplete-js');
            $nominatim_default_lat = isset($adforest_theme['sb_default_lat']) ? floatval($adforest_theme['sb_default_lat']) : 0;
            $nominatim_default_lng = isset($adforest_theme['sb_default_long']) ? floatval($adforest_theme['sb_default_long']) : 0;
            wp_localize_script('leaflet', 'adforestNominatim', array('countrycodes' => $nominatim_countrycodes, 'featuretype' => $nominatim_featuretype, 'defaultLat' => $nominatim_default_lat, 'defaultLng' => $nominatim_default_lng));
        } else {
            if (isset($adforest_theme['gmap_api_key']) && $adforest_theme['gmap_api_key'] != "") {
                $map_lang = 'fr';
                if (isset($adforest_theme['gmap_lang']) && $adforest_theme['gmap_lang'] != "") {
                    $map_lang = $adforest_theme['gmap_lang'];
                }
                $map_lang = apply_filters('adforest_languages_code', $map_lang);
                wp_register_script('google-map', '//maps.googleapis.com/maps/api/js?key=' . $adforest_theme['gmap_api_key'] . '&language=' . $map_lang . "&loading=async", false, false, true);
                $marker = '';
                if(!is_page_template('page-search.php')) {
                    $marker = ',marker';
                }
                wp_register_script(
                    'google-map-callback',
                    '//maps.googleapis.com/maps/api/js?key=' . $adforest_theme['gmap_api_key'] .
                    '&libraries=geometry,places' . $marker . "&language=" . $map_lang . "&loading=async" .
                    false,
                    false,
                    true
                );
                wp_enqueue_script('marker-clusterer', trailingslashit(get_template_directory_uri()) . 'assets/js/marker-clusterer.min.js', array(), null, false);

                if (is_singular('events')) {
                    wp_enqueue_script('google-map-callback');
                }
            }
        }

        define("ADFOREST_DASHBOARD_URL_CSS", trailingslashit(get_template_directory_uri()) . "dashboard/css/");
        define("ADFOREST_DASHBOARD_URL_JS", trailingslashit(get_template_directory_uri()) . "dashboard/js/");
        if ($page_template !== 'page-theme-dashboard.php') {
            // CSS FILES
            wp_enqueue_style('adforest-pro-style', get_stylesheet_uri(), array(), ADFOREST_VERSION);
            wp_register_style('pretty-checkbox', trailingslashit(get_template_directory_uri()) . 'assets/css/pretty-checkbox.css');
            wp_enqueue_style('adforest-menu', trailingslashit(get_template_directory_uri()) . 'assets/css/sb.menu.css', array(), ADFOREST_VERSION);
            wp_enqueue_style('bootstrap', trailingslashit(get_template_directory_uri()) . 'assets/css/bootstrap.css');
            wp_enqueue_style('load-fa-latest', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css');
            wp_register_style('nouislider', trailingslashit(get_template_directory_uri()) . 'assets/css/nouislider.min.css');
            wp_enqueue_style('owl-carousel-carousel', trailingslashit(get_template_directory_uri()) . 'assets/css/owl.carousel.css');
            wp_enqueue_style('owl-theme', trailingslashit(get_template_directory_uri()) . 'assets/css/owl.theme.css');
            wp_enqueue_style('pretty-checkbox', trailingslashit(get_template_directory_uri()) . 'assets/css/prettycheckbox.min.css');
            wp_enqueue_style('adforest-style', trailingslashit(get_template_directory_uri()) . 'assets/css/adforest-style.css', array(), ADFOREST_VERSION);
            wp_enqueue_style('adforest-main', trailingslashit(get_template_directory_uri()) . 'assets/css/adforest-main.css', array(), ADFOREST_VERSION);
            $plugin_path = 'dc-woocommerce-multi-vendor/dc_product_vendor.php';

            $is_active = is_plugin_active($plugin_path);

            if (is_multisite() && !$is_active) {
                $network_plugins = get_site_option('active_sitewide_plugins');
                $is_active = isset($network_plugins[$plugin_path]);
            }

            if ($is_active) {
                wp_enqueue_style(
                    'adforest-multivendor-style',
                    trailingslashit(get_template_directory_uri()) . 'assets/css/mutivendor-style.css',
                    [],
                    '1.0'
                );
            }
            wp_enqueue_style('adforest-main-responsive', trailingslashit(get_template_directory_uri()) . 'assets/css/adforest-main-responsive.css', array(), ADFOREST_VERSION);
            wp_register_style('adforest-fancybox', trailingslashit(get_template_directory_uri()) . 'assets/css/jquery.fancybox.min.css');
            wp_enqueue_style('adforest-theme-blog', trailingslashit(get_template_directory_uri()) . 'assets/css/theme-blog-main.css', array(), ADFOREST_VERSION);
            wp_enqueue_style('adforest-jquery-ui-css', 'https://code.jquery.com/ui/1.13.3/themes/smoothness/jquery-ui.css');
            wp_register_style('rangeslider-css', trailingslashit(get_template_directory_uri()) . 'assets/css/rangeslider.min.css');
            if (is_rtl()) {
                wp_enqueue_style('adforest-main-rtl', trailingslashit(get_template_directory_uri()) . 'assets/css/adforest-main-rtl.css', array(), ADFOREST_VERSION);

            }

            if (class_exists("Redux")) {
                wp_enqueue_style('theme_custom_css', get_template_directory_uri() . '/assets/css/custom_style.css');

                global $adforest_theme;
                $h2_color = isset($adforest_theme['adforest-body-typo']['color']) ? $adforest_theme['adforest-body-typo']['color'] : "";
                $main_btn_color = $adforest_theme['opt-theme-btn-color']['regular'] ? $adforest_theme['opt-theme-btn-color']['regular'] : "";
                $secondary_btn_color = $adforest_theme['opt-theme-btn-color-secondary']['regular'] ? $adforest_theme['opt-theme-btn-color-secondary']['regular'] : "";
                $main_btn_color_hover = isset($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : "";
                $secondary_btn_color_hover = isset($adforest_theme['opt-theme-btn-color-secondary']['hover']) ? $adforest_theme['opt-theme-btn-color-secondary']['hover'] : "";
                $main_btn_color_shadow = isset($adforest_theme['opt-theme-btn-shadow-color']['rgba']) ? $adforest_theme['opt-theme-btn-color']['hover'] : "";
                $secondary_btn_color_shadow = isset($adforest_theme['opt-theme-btn-shadow-color-secondary']['rgba']) ? $adforest_theme['opt-theme-btn-shadow-color-secondary']['rgba'] : "";
                $main_btn_color_text = isset($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : "";
                $secondary_btn_color_text = isset($adforest_theme['opt-theme-btn-text-color-secondary']['regular']) ? $adforest_theme['opt-theme-btn-text-color-secondary']['regular'] : "";
                $main_btn_hover_color_text = isset($adforest_theme['opt-theme-btn-text-color']['hover']) ? $adforest_theme['opt-theme-btn-text-color']['hover'] : "";
                $secondary_btn_hover_color_text = isset($adforest_theme['opt-theme-btn-text-color-secondary']['hover']) ? $adforest_theme['opt-theme-btn-text-color-secondary']['hover'] : "";
                $main_color_opacity = $main_btn_color . '10';
                $secondary_color_opacity = $secondary_btn_color . '10';

                $adforest_h2_typo = isset($adforest_theme['adforest-h2-typo']['color']) ? $adforest_theme['adforest-h2-typo']['color'] : '';

                $custom_css = "
                 h2 a { color  : $adforest_h2_typo }
                .ad-post-btn, 
                .adt-button-dark, 
                .adt-theme-button-2, 
                .seller-prf-btn,
                .adt-blog-sidebar .widget.widget-content .wp-block-search__button {
                 border: 1px solid $main_btn_color !important; background-color: $main_btn_color !important; color: $main_btn_color_text !important;
                 }
                 .adt-theme-button-1 {
                    border: 1px solid $main_btn_color !important;
                    color: $main_btn_color !important;
                 }
                 
                 @media (min-width: 1200px) {
                  .sb-menu.submenu-top-border li > ul {
                    border-top: 3px solid $main_btn_color !important;
                  }
                }
                 
                 .adt-button-dark-1 {
                    background-color: $secondary_btn_color !important;
                    color: $secondary_btn_color_text !important;
                    border: 1px solid $secondary_btn_color !important;
                    }
                    
                    html body .loading:not(:required):after {
                        border-top-color: $main_btn_color !important;
                        border-right-color: $main_btn_color !important;
                        }
                 
                 .adt-button-dark-1:hover, .adt-footer-section .adt-about-detail-box ul li a:hover,
                 .adt-about-experience-section .left-img-box .play-btn {
                    background-color: $secondary_btn_color_hover !important;
                    color: $secondary_btn_hover_color_text !important;
                 }

                .adt-button-dark-1:hover {
                    border: 1px solid $secondary_btn_color !important; 
                }
                 
                 .adt-theme-button-1:hover {
                    border: 1px solid $main_btn_color_hover !important;
                    color: $main_btn_color_hover !important;
                 }
                 .adt-mini-location-box:hover .location-img-box .ads-count,
                 .pet-category-carousel .owl-nav .owl-prev, .pet-category-carousel .owl-nav .owl-next,
                 .featured-label {
                    color: $main_btn_hover_color_text !important;
                    background-color: $main_btn_color !important; 
                 }
                 
                 .adt-event-detail-section .adt-product-author-detail-box .btn-theme-secondary {
                    background-color: $main_btn_color !important; 
                 }
                 
                 .adt-classic-ads-section .adt-category-types-carousel .owl-prev, .adt-classic-ads-section .adt-category-types-carousel .owl-next {
                    background: $main_btn_color !important;
                    border: 1px solid $main_btn_color !important;
                 }
                 
                 .adt-product-detail-box .actions-box a:hover,
                 .pretty.p-default input:checked ~ .state label:after,
                 .adt-ads-topbar-content form .form-field .search-btn-title {
                    background-color: $main_btn_color !important;
                 }
                 
                 .adt-product-detail-box .actions-box a:hover {
                    border-color: $main_btn_color !important;
                 }
                 
                .adt-product-detail-box .actions-box a:hover i {
                    color: $main_btn_hover_color_text !important;
                }
                 
                 .sticky-post-button h4 {
                    color: $main_btn_color_text !important;
                 }
                 
                 .adt-ad-detail-content-wrapper .adt-detail-content-list ul li a.active, .adt-searchbar-wrapper .adt-lists-count span,
                 .adt-hero-city-section .content-box .sub-title, 
                 .adt-about-experience-section .left-img-box .experience-box strong,
                 .adt-about-experience-section .content-box span,
                 .adt-ad-post-section .ad-post-tabs button.active {
                    color: $main_btn_color !important;
                 }
                 
                 
                 h2.mb1-1 .btn-link:hover,
                 .adt-recent-ads-sidebar .adt-recent-ad-box .recent-img-meta strong ins,
                 .adt-category-ad-card .category-content-box .price-box strong,
                 .adt-header-primary.sb-header .sb-menu li:not(:last-child) a:hover, .adt-update-work-flow-section .work-process-box .title {
                    color: $main_btn_color !important;
                 }
                 
                 div.sub-header:hover {
                    border-right: $main_btn_color !important;
                 }
                 .
                 .adt-transparent-header-1 .sb-menu ul ul li > a::before {
                    background: $main_btn_color !important;
                 }
                 
                 .detail-btn,
                 .adt-button-dark,
                 .adt-theme-button-2 {
                    transition: all 0.3s ease;
                 }
                 .detail-btn:hover, 
                 .adt-classified-listing-top-box ul li a:hover,
                 .adt-classified-search-box::before,
                 .adt-multivendor-detail-section .multivendor-detail-banner .follow-us-box ul li a:hover,
                 .ad-detail-middle-content .ad-about-box .more-detail-box ul.social-link li a:hover,
                 .adt-404-section .content-box a,
                 .adt-ads-topbar-content .irs--round .irs-handle.to
                 {
                    background-color: $main_btn_color !important; 
                 }
                 
                 .btn-event-search {
                    background-color: $main_btn_color !important;
                    color: $main_btn_color_text !important;
                 }
                 
                 .tag-search {
                    display: flex;
                    flex-wrap: wrap; /* Ensures responsiveness */
                    gap: 10px; /* Adds spacing between tags */
                    align-items: center;
                 }
                
                 .tag-search form {
                    margin: 0; /* Removes default margin */
                    display: flex;
                 }
                
                 .sb_tag {
                    display: flex;
                    align-items: center;
                    gap: 5px; /* Space between text and close button */
                    padding: 5px 10px;
                    background-color: #17a2b8;
                    color: white;
                    border-radius: 15px;
                 }
                 
                 .adt-mini-ad-box .ad-meta-box h5,
                 .adt-footer-section .adt-quick-links ul li::marker,
                 .adt-copyright-box p a,
                 .adt-signup-right-content form .field-box .forget-password a,
                 .botm-question-text span a,
                 .adt-classified-listing-top-box h4,
                 .adt-find-pet-hero .adt-find-pet-content h4,
                 .find-pet-carousel-area .sub-content small,
                 .adt-marketplace-hero-content h4,
                 .adt-classified-search-box .search-btns-wrapper .advanced-search,
                 .adt-multivendor-header .sb-header .sb-menu li a:hover, .adt-multivendor-header .sb-header .sb-menu li a:focus, .adt-multivendor-header .sb-header .sb-menu li a:active,
                 .adt-ad-detail-content-wrapper .ad-owner-detail-box .view-all-ads-text,
                 .adt-explore-things-hero .explore-hero-content h4, 
                 .adt-transparent-header-1.sb-header .sb-menu li:not(:last-child) a:hover, .adt-transparent-header-1.sb-header .sb-menu li:not(:last-child) a:focus, .adt-transparent-header-1.sb-header .sb-menu li:not(:last-child) a:active,
                 .adv-srch,
                 .adt-category-ad-card:hover .category-content-box h5, .adt-car-dealer-hero .content-box .sub-title {
                    color: $main_btn_color !important;
                 }
                 
                 .adt-pricing-plan-section .heading-content .label,
                 .adt-about-us-section .right-cont .sub-cont .label,
                 .adt-work-flow-section .heading-content .label,
                 .adt-advanced-faqs .left-main-content .label,
                 .adt-car-ad-card .adt-property-content-box .price strong,
                 .adt-car-dealer-card .adt-car-price-meta .price-box span {
                    color: $main_btn_color !important;
                 }
                 
                 
                 .adt-map-search-section .search-filters-content .right-content .icon-box:hover, .adt-map-search-section .search-filters-content .right-content .icon-box.active,
                 .adt-footer-section .adt-newsletter-box form .send-btn{
                    background-color: $main_btn_color !important;
                    border: 1px solid $main_btn_color !important;
                 }
                 
                 .adt-map-search-section .search-filters-wrapper .adtype-dropdown button.show, .adt-map-search-section .search-filters-wrapper .category-dropdown button.show,
                 .adt-map-search-section .search-filters-wrapper .search-all-filters.active,
                 .adt-ads-topbar-content .irs--round .irs-bar {
                    background-color: $main_btn_color !important;
                    border-color: $main_btn_color !important;
                 }
                 
                 .adt-about-us-section .left-img-box .play-btn,
                 .adt-about-us-section .left-img-box .play-btn::before,
                 .adt-about-us-section .left-img-box .play-btn::after,
                 .adt-ads-filter-sidebar .adt-search-list-box .form-field .search-btn-title,
                 .adt-ads-filter-sidebar .adt-search-list-box .form-field .search-btn,
                 .adt-ads-filter-sidebar .irs--round .irs-handle.to,
                 .adt-ad-detail-content-wrapper .adt-detail-content-list ul li a.active::before,
                 .adt-multivendor-searchbar-wrapper .adt-search-area button,
                 .adt-signup-right-content .pretty.p-default input:checked ~ .state label:after{
                    background-color: $main_btn_color !important;
                 }
                 
                 .adt-header-secondary.sb-header .sb-menu li:not(:last-child) a:hover, 
                 .adt-header-secondary.sb-header .sb-menu li:not(:last-child) a:focus, 
                 .adt-header-secondary.sb-header .sb-menu li:not(:last-child) a:active,
                 .adt-category-list-sidebar .adt-category-box .category-meta a:hover,
                 .ad-detail-middle-content .ad-about-box .more-detail-box ul li span a,
                 .product-cart-head h3:hover {
                    color: $main_btn_color !important;
                 }
                 
                 .adt-ads-filter-sidebar .irs--round .irs-bar,
                 .adt-ads-filter-sidebar .adt-type-filter-box input:checked ~ .checkmark,
                 .adt-custom-pagination .page-item .page-link.active, 
                 .adt-custom-pagination .page-item .page-link:hover {
                    background-color: $main_btn_color !important;
                    border: $main_btn_color !important;
                 }
                 
                 .adt-classic-ads-section .adt-category-type-carousel-box .search-all-filters.active {
                    background-color: $main_btn_color !important;
                    border-color: $main_btn_color !important;
                 }
                 
                 .adt-header-secondary .sb-menu ul ul li > a::before {
                    background: $main_btn_color !important;
                 }

                 .select-user-type  ul li label {
                    color: $main_btn_hover_color_text !important;
                 }
                 
                 .adt-ad-post-section .ad-post-tab-box .select-user-type li label {
                    color: #6d6d6d;
                 }
                 
                 .adt-ad-post-section .ad-post-tabs button.active::before,
                 .find-pet-carousel-area::before,
                 .adt-location-box::before,
                 .adt-location-box:hover .location-meta-box .ads-count,
                 .adt-category-round-list-sidebar .adt-category-box:hover .listing-count,
                 .pulsing-cluster,
                 .pulsing-cluster::before,
                 .adt-ads-topbar-content form .form-field .search-btn {
                    background-color: $main_btn_color !important; 
                 }
                 .adt-location-box:hover .location-meta-box .ads-count {
                    color: #fff !important;
                 }
                 
                a.btn-condition:hover, 
                a.btn-warranty:hover, 
                a.btn-type:hover, 
                li a.page-link:hover, 
                .chevron-2:hover,
                .chevron-1:hover,
                .ad-post-btn:hover, 
                .adt-button-dark:hover, 
                .adt-theme-button-2:hover,
                .seller-prf-btn:hover,
                form div input#searchsubmit:hover,
                .adt-multivendor-searchbar-wrapper .adt-search-area button:hover
                { 
                   background-color: $main_btn_color_hover !important; 
                   border: 1px solid $main_btn_color_hover !important;
                   box-shadow: 0 0.5rem 1.125rem -0.5rem $main_btn_color_shadow !important ;
                   color: $main_btn_hover_color_text !important;
                }
                
               ul.pagination-lg a:hover {
                 background: $main_btn_color_hover ;
                 color:  $main_btn_hover_color_text;
               
                  }
               ul.tabs.wc-tabs li:hover a , .padding_cats .cat-btn:hover  ,.prop-it-work-sell-section:hover .prop-it-sell-text-section span
                {
                    color: $main_btn_hover_color_text; 
                } 
                
               .noUi-connect , ul.cont-icon-list li:hover ,  li a.page-link:hover ,ul.socials-links li:hover ,ul.filterAdType li .filterAdType-count:hover{
                     background: $main_btn_color_hover;
          
                      } 

               ul.tabs.wc-tabs li:hover,
                .adt-classified-listing-top-box ul li a:hover {
                    background-color: $main_btn_color; 
                    color: $main_btn_hover_color_text;   
                  }
                                             
             .tags-share ul li a:hover , .header-location-icon , .header-3-input .looking-form-search-icon i ,.footer-anchor-section a , .address-icon , .num-icon , .gmail-icon ,.wb-icon  ,.personal-mail i , .personal-phone i ,.personal-addres i ,.woocommerce-tabs .wc-tabs li.active a ,.woocommerce .woocommerce-breadcrumb a ,p.price .amount bdi , .wrapper-latest-product .bottom-listing-product h5 ,.dec-featured-details-section span h3 , .sb-modern-list.ad-listing .content-area .price ,.ad-grid-modern-price h5 ,.ad-grid-modern-heading span i,.item-sub-information li , .post-ad-container .alert a , ul.list li label a ,.active ,.found-adforest-heading h5 span a , .register-account-here p a ,.land-classified-heading h3 span ,.land-classified-text-section .list-inline li i ,.land-qs-heading-section h3 span ,.land-fa-qs .more-less ,.land-bootsrap-models .btn-primary ,.recent-ads-list-price  ,.ad-detail-2-content-heading h4 ,.ads-grid-container .ads-grid-panel span ,.ads-grid-container .ads-grid-panel span ,.new-small-grid .ad-price ,.testimonial-product-listing span ,.client-heading span , .best-new-content span  , .bottom-left .new-price , .map-location i ,.tags-share ul li i ,.item-sub-information li  , div#carousel ul.slides li.flex-active-slide img , ul.clendar-head li a i , ul.list li label a , .post-ad-container .alert a , .new-footer-text-h1 p a ,.app-download-pistachio .app-text-section h5 , .prop-agent-text-section p i , .sb-header-top2 .sb-dec-top-ad-post a i , .srvs-prov-text h4 ,.top-bk-details i ,.bk-sel-price span , .bk-sel-rate i ,.white.category-grid-box-1 .ad-price ,.bk-hero-text h4 , .sb-modern-header-11 .sb-bk-srch-links .list-inline.sb-bk-srch-contents li a ,.sb-header-top-11 .sb-dec-top-ad-post a i , .mat-new-candidates-categories p  ,.mat-hero-text-section h1 span , .feature-detail-heading h5 , .copyright-heading p a 
                    ,.great-product-content h4 ,.sb-short-head span ,span.heading-color,
                    .app-download span ,.cashew-main-counter h4 span ,.blog-post .post-info-date a ,
                    .found-listing-heading h5 ,.pistachio-classified-grid .ad-listing .content-area .price h3 ,.pistachio-classified-grid .negotiable ,
                    .category-grid-box .short-description .price ,.new-feature-products span ,
                    .post-info i ,.tag-icon  ,
                    .funfacts.fun_2 h4 span  ,
                    .listing-detail .listing-content span.listing-price, .adforest-user-ads b,.tech-mac-book h1 span ,
                  #event-count ,.buyent-ads-hero .main-content .title , .ad-listing-hero-main .ad-listing-hero .search-bar-box .srh-bar .input-srh span, .ad-listing-hero-main .ad-listing-hero .search-bar-box .srh-bar .ctg-srh .title, .ad-listing-hero-main .ad-listing-hero .search-bar-box .srh-bar .loct-srh .title ,.ad-listing-hero-main .ad-listing-hero .search-bar-box .srh-bar .input-srh span , .filter-date-event:hover ,.filter-date-event:focus, .tech-mac-book h1 .color-scheme ,.tech-latest-primary-section h3 .explore-style ,.tech-call-to-action .tech-view-section h2 span
                        {
                        color: $main_btn_color;
                     }
                              @media (min-width: 320px) and (max-width: 995px) {
                             .sb-header-top2 .sb-dec-top-bar {
                                        background: linear-gradient( 
                                                 45deg
                                         , $main_btn_color 24%,$main_btn_color 0%);
                                            }
                                            }
                                @media (min-width: 995px) {
                                        .sb-header-top2 .sb-dec-top-bar {
                                        background: linear-gradient( 
                                                 45deg
                                         , #ffffff 24%,$main_btn_color 0%);
                                            }
                                        }
                   .ad-listing-hero-main .ctg-ads-carousel .ad-category-carousel .item:hover ,.sb-header-top3 .sb-mob-top-bar , ul.pagination-lg li.active a ,.ad-event-detail-section .nav-pills .nav-item .nav-link.active {
                        color: $main_btn_color_text;
                        background-color: $main_btn_color;
                    }
                   
                                   
                .ad-event-detail-section .main-dtl-box .meta-share-box .share-links ul li .icon:hover  , .sb-notify .point , .section-footer-bottom-mlt .line-bottom ,.img-head span  ,ul.filterAdType li.active .filterAdType-count ,.mob-samsung-categories .owl-nav i ,.select2-container--default .select2-results__option--highlighted[aria-selected] , .toys-call-to-action ,.toys-hero-section .toys-new-accessories .toys-hero-content ,.sb-modern-header-11 .sb-bk-search-area .sb-bk-side-btns .sb-bk-srch-links .sb-bk-srch-contents .sb-bk-absolute , .sb-header-11  , .img-options-wrap .dec-featured-ht , .new-all-categories ,.noUi-connect  ,.home-category-slider .category-slider .owl-nav .owl-prev, 
                    .home-category-slider .category-slider .owl-nav .owl-next ,.sb-notify .point:before ,.sb-header-top1.header-classy-header .flo-right .sb-notify .point, .sb-header-top1.transparent-3-header .flo-right .sb-notify .point, .sb-header-top1.transparent-2-header .flo-right .sb-notify .point, .sb-header-top1.transparent-header .flo-right .sb-notify .point, .sb-header-top1.with_ad-header .flo-right .sb-notify .point, .sb-header-top1.black-header .flo-right .sb-notify .point, .sb-header-top1.white-header .flo-right .sb-notify .point{
                     background-color: $main_btn_color; 

                      }
                      div#carousel ul.slides li.flex-active-slide img , ul.dropdown-user-login , .woocommerce-tabs .wc-tabs ,.land-bootsrap-models .btn-primary  , .chevron-1 ,.chevron-2 , .heading-panel .main-title ,.sb-modern-header-11 .sb-bk-search-area .sb-bk-side-btns .sb-bk-srch-links .sb-bk-srch-contents li:first-child  ,.product-favourite-sb{
                       border-color  :  $main_btn_color;
                           }
                     
              .img-head img ,li.active .page-link ,.section-bid-2 .nav-tabs .nav-link.active, .nav-tabs .nav-item.show .nav-link , a.btn.btn-selected ,.shop-layout-2 .shops-cart a , .mat-success-stories .owl-nav i ,input[type=submit], .featured-slider-1.owl-theme.ad-slider-box-carousel .owl-nav [class*=owl-] ,
                  .cashew-multiple-grid .nav-pills .nav-link.active, .nav-pills .show > .nav-link ,.pg-new .select-buttons .btn-primary,
                  .widget-newsletter .fieldset form .submit-btn ,a.follow-now-btn ,.tab-content input.btn {
                     background-color: $main_btn_color;              
                     color: $main_btn_color_text;
                         border-color  :  $main_btn_color;
                   }
                
                .prop-newest-section .tabbable-line > .nav-tabs > li a.active , .woocommerce input:hover[type='submit'] , .woocommerce .checkout-button:hover , a.follow-now-btn:hover ,.tab-content input.btn:hover{
                              background-color: $main_btn_color_hover !important; 
                              border: 1px solid $main_btn_color_hover !important;           
                               color: $main_btn_hover_color_text !important;
                            }
                            
                        input[type=submit]  {
                         background-color: $main_btn_color ; color: $main_btn_color_text; border: 1px solid $main_btn_color;
                         }
                           .detail-product-search form button , .sticky-post-button ,.woocommerce input[type='submit'] ,.woocommerce 
                            .checkout-button {
                            background-color: $main_btn_color !important ; color: $main_btn_color_text !important ; border: 1px solid $main_btn_color;}

                            .cd-top {background-color : $main_btn_color !important }

            ";
                wp_add_inline_style('theme_custom_css', $custom_css);
            }

            // JS FILES
            wp_enqueue_script('hello', trailingslashit(get_template_directory_uri()) . 'assets/js/hello.js', false, false, true);
            wp_register_script('nouislider-all', trailingslashit(get_template_directory_uri()) . 'assets/js/nouislider.all.min.js', false, false, true);
            wp_enqueue_script('parsley', trailingslashit(get_template_directory_uri()) . 'assets/js/parsley.min.js', [], false, true);
            wp_enqueue_script('icheck', trailingslashit(get_template_directory_uri()) . 'assets/js/icheck.min.js', false, false, true);
            wp_enqueue_script('masonry', trailingslashit(get_template_directory_uri()) . 'assets/js/masonary.js', [], false, true);
            wp_enqueue_script('adforest-dt', trailingslashit(get_template_directory_uri()) . 'assets/js/datepicker.min.js', false, false, true);
            wp_enqueue_script('jquery-te', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery-te.min.js', false, false, true);
            wp_register_script('adforest-dropzone-js', trailingslashit(get_template_directory_uri()) . 'assets/js/dropzone.js', false, false, true);
            wp_enqueue_script('tagsinput', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery.tagsinput.min.js', false, false, true);
            wp_register_script('rangeslider-min', trailingslashit(get_template_directory_uri()) . 'assets/js/rangeslider.min.js', [], false, true);
            wp_enqueue_script('adforest-menu', trailingslashit(get_template_directory_uri()) . 'assets/js/adforest-menu.js', array('jquery'), ADFOREST_VERSION, false);
            wp_enqueue_script('jquery-ui-min', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery/jquery.ui.min.js', false, false, true);
            wp_enqueue_script('adforest-custom', trailingslashit(get_template_directory_uri()) . 'assets/js/adforest-custom.js', array('jquery'), ADFOREST_VERSION, true);
            wp_localize_script('adforest-custom', 'adforest_vendor_security', array(
                'vendor_email_nonce' => wp_create_nonce('sb_vendor_email_nonce'),
                'vendor_fav_nonce' => wp_create_nonce('sb_vendor_fav_nonce'),
            ));
            wp_localize_script('adforest-custom', 'adforestFav', array(
                'titleAdd'    => esc_attr__('Click to make it favourite', 'adforest'),
                'titleRemove' => esc_attr__('Click to remove from favourite', 'adforest'),
            ));
            wp_enqueue_script('adforest-location-selector', trailingslashit(get_template_directory_uri()) . 'assets/js/location-selector.js', array('jquery'), ADFOREST_VERSION, true);
            wp_localize_script(
                'adforest-location-selector',
                'adforestLocationSelector',
                array(
                    'ajaxUrl'        => apply_filters('adforest_set_query_param', admin_url('admin-ajax.php')),
                    'errorMessage'   => esc_html__('Unable to update location. Please try again.', 'adforest'),
                    'reloadOnChange' => true,
                )
            );
            wp_register_script('star-rating', trailingslashit(get_template_directory_uri()) . 'assets/js/star-rating.js', false, false, true);
            wp_register_script('search-map', trailingslashit(get_template_directory_uri()) . 'assets/js/map.js', false, false, true);
            wp_register_script('oms', trailingslashit(get_template_directory_uri()) . 'assets/js/oms.min.js', false, false, true);
            if (function_exists('is_checkout') && is_checkout()) {
                wp_enqueue_script('wc-country-select');
                wp_enqueue_script('wc-checkout');
            }

            if (is_singular('ad_post')) {
                wp_enqueue_script('star-rating');
                wp_enqueue_script('jquery-ui-all');
                wp_enqueue_style('jquery-confirm', ADFOREST_DASHBOARD_URL_CSS . 'jquery-confirm.min.css', false, false);
                wp_enqueue_script('jquery-confirm', ADFOREST_DASHBOARD_URL_JS . 'jquery-confirm.min.js', array(), false, true);
            }

            if (is_page_template('event-search.php')) {
                wp_enqueue_script('nouislider-all');
                wp_enqueue_style('nouislider');
            }

            if (is_page_template('page-seller-search.php') || is_page_template('page-vendor-search.php')) {
                wp_enqueue_script('star-rating');
            }

            if (is_author() || $page_template == 'page-users.php' || is_singular('events')) {
                wp_enqueue_script('star-rating');
            }

            if (is_page_template('page-search.php') || (class_exists('WooCommerce') && is_shop())) {
                wp_enqueue_style('rangeslider-css');
                wp_enqueue_script('rangeslider-min');
            }

            $language_code = apply_filters('adforest_languages_code', get_bloginfo('language'));

            if (isset($adforest_theme['google_api_key']) && !empty($adforest_theme['google_api_key'])) {
                if (isset($adforest_theme['google-recaptcha-type']) && $adforest_theme['google-recaptcha-type'] == 'v3') {
                    $captcha_site_key = isset($adforest_theme['google_api_key']) && !empty($adforest_theme['google_api_key']) ? $adforest_theme['google_api_key'] : '';
                    wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?hl=' . $language_code . '&render=' . $captcha_site_key . '', false, false, false);
                } else {
                    wp_enqueue_script('recaptcha', '//www.google.com/recaptcha/api.js?hl=' . $language_code . '', false, false, true);
                }
            }

            if (is_rtl()) {
                wp_enqueue_style('bootstrap-rtl', trailingslashit(get_template_directory_uri()) . 'assets/css/bootstrap.rtl.min.css', false, false, true);
                wp_enqueue_style('adforest-pro-rtl-style2', trailingslashit(get_template_directory_uri()) . 'assets/css/adforest-style-rtl.css', array(), ADFOREST_VERSION);
            }
        } else {
            // CSS FILES
            wp_enqueue_style('dashboard-perfect-scrollbar-css', trailingslashit(get_template_directory_uri()) . 'dashboard/css/perfect-scrollbar.css');
            wp_enqueue_style('dashboard-bootstrap-css', trailingslashit(get_template_directory_uri()) . 'dashboard/css/bootstrap.min.css');
            wp_enqueue_style('dashboard-fullcalendar', trailingslashit(get_template_directory_uri()) . 'dashboard/css/fullcalendar.css');
            wp_enqueue_style('dashboard-lineicons', trailingslashit(get_template_directory_uri()) . 'dashboard/css/lineicons.css');
            wp_enqueue_style('dashboard-loading-bar', trailingslashit(get_template_directory_uri()) . 'dashboard/css/loading-bar.min.css');
            wp_enqueue_style('dashboard-materialdesignicons', trailingslashit(get_template_directory_uri()) . 'dashboard/css/materialdesignicons.min.css');
            wp_enqueue_style('dashboard-morris', trailingslashit(get_template_directory_uri()) . 'dashboard/css/morris.css');
            wp_enqueue_style('jquery-confirm', trailingslashit(get_template_directory_uri()) . 'dashboard/css/jquery-confirm.min.css', false, false);
            wp_enqueue_style('dashboard-main', trailingslashit(get_template_directory_uri()) . 'dashboard/css/main.css', array(), ADFOREST_VERSION);
            wp_enqueue_style('dashboard-dash', trailingslashit(get_template_directory_uri()) . 'dashboard/css/dashboard.css', array(), ADFOREST_VERSION);
            wp_enqueue_style('dashboard-dash-rtl', trailingslashit(get_template_directory_uri()) . 'dashboard/css/dashboard-rtl.css', array(), ADFOREST_VERSION);

            // JS FILES
            wp_enqueue_script('jquery-confirm', trailingslashit(get_template_directory_uri()) . 'dashboard/js/jquery-confirm.min.js', array(), false, true);
            wp_enqueue_script('jquery-confirmjs', trailingslashit(get_template_directory_uri()) . 'dashboard/js/jquery-confirm.js', array(), false, true);
            wp_enqueue_script('dashboard-bootstrap-bundle', trailingslashit(get_template_directory_uri()) . 'dashboard/js/bootstrap.bundle.min.js', [], false, true);
            wp_enqueue_script('dashboard-Chart', trailingslashit(get_template_directory_uri()) . 'dashboard/js/Chart.min.js', [], false, true);
            wp_enqueue_script('dashboard-dynamic-pie-chart', trailingslashit(get_template_directory_uri()) . 'dashboard/js/dynamic-pie-chart.js', [], false, true);
            wp_enqueue_script('dashboard-fullcalendar', trailingslashit(get_template_directory_uri()) . 'dashboard/js/fullcalendar.js', [], false, true);
            wp_enqueue_script('dashboard-jvectormap', trailingslashit(get_template_directory_uri()) . 'dashboard/js/jvectormap.min.js', [], false, true);
            wp_enqueue_script('dashboard-loading-bar', trailingslashit(get_template_directory_uri()) . 'dashboard/js/loading-bar.min.js', [], false, true);
            wp_enqueue_script('dashboard-mainjs', trailingslashit(get_template_directory_uri()) . 'dashboard/js/main.js', [], false, true);
            wp_localize_script('dashboard-mainjs', 'chartData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'months'   => array(
                    __('Jan', 'adforest'),
                    __('Feb', 'adforest'),
                    __('Mar', 'adforest'),
                    __('Apr', 'adforest'),
                    __('May', 'adforest'),
                    __('Jun', 'adforest'),
                    __('Jul', 'adforest'),
                    __('Aug', 'adforest'),
                    __('Sep', 'adforest'),
                    __('Oct', 'adforest'),
                    __('Nov', 'adforest'),
                    __('Dec', 'adforest'),
                ),
                'weeks'   => array(
                    __('Mon', 'adforest'),
                    __('Tue', 'adforest'),
                    __('Wed', 'adforest'),
                    __('Thu', 'adforest'),
                    __('Fri', 'adforest'),
                    __('Sat', 'adforest'),
                    __('Sun', 'adforest'),
                ),
                'ad_views' => __('Ad Views', 'adforest'),
                'profile_views' => __('Profile Views', 'adforest'),
            ));
            wp_enqueue_script('dashboard-moment', trailingslashit(get_template_directory_uri()) . 'dashboard/js/moment.min.js', [], false, true);
            wp_enqueue_script('dashboard-polyfill', trailingslashit(get_template_directory_uri()) . 'dashboard/js/polyfill.js', [], false, true);
            wp_enqueue_script('dashboard-world-merc', trailingslashit(get_template_directory_uri()) . 'dashboard/js/world-merc.js', [], false, true);
            wp_enqueue_script('dashboard-perfect-scrollbar2', trailingslashit(get_template_directory_uri()) . 'dashboard/js/perfect-scrollbar2.js', array('jquery'), false, false);
            wp_enqueue_script('adforest-dt', trailingslashit(get_template_directory_uri()) . 'assets/js/datepicker.min.js', false, false, true);
            wp_enqueue_script('dropzone', trailingslashit(get_template_directory_uri()) . 'assets/js/dropzone.js', false, false, true);
            wp_enqueue_script('parsley', trailingslashit(get_template_directory_uri()) . 'assets/js/parsley.min.js', [], false, true);
            wp_enqueue_script('tagsinput', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery.tagsinput.min.js', false, false, true);
            wp_enqueue_script('jquery-te', trailingslashit(get_template_directory_uri()) . 'assets/js/jquery-te.min.js', false, false, true);
            wp_enqueue_script('dashboard-customjs', trailingslashit(get_template_directory_uri()) . 'dashboard/js/dashboard-custom.js', [], ADFOREST_VERSION, true);

            $selected_categories = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);
            $default_package = prepare_default_packages();

            if (is_array($selected_categories) && is_array($default_package)) {
                $selected_categories = $default_package + $selected_categories;
            } else {
                $selected_categories = $default_package;
            }

            $package_message = !empty($selected_categories) && is_array($selected_categories)
                ? __('Please Select a Package', 'adforest')
                : __('No Packages Found', 'adforest');

            wp_localize_script('dashboard-customjs', 'adforest_pkg_data', [
                'packageText' => $package_message,
                'security' => wp_create_nonce('adforest_pkg_data'),
                'vendor_role_nonce' => wp_create_nonce('sb_vendor_role_nonce')
            ]);
            $dash_default_lat = isset($adforest_theme['sb_default_lat']) ? floatval($adforest_theme['sb_default_lat']) : 0;
            $dash_default_lng = isset($adforest_theme['sb_default_long']) ? floatval($adforest_theme['sb_default_long']) : 0;
            wp_localize_script('dashboard-customjs', 'adforestNominatim', array('countrycodes' => $nominatim_countrycodes, 'featuretype' => $nominatim_featuretype, 'defaultLat' => $dash_default_lat, 'defaultLng' => $dash_default_lng));

            if (class_exists("Redux")) {
                wp_enqueue_style('theme_custom_css', get_template_directory_uri() . '/assets/css/custom_style.css');

                global $adforest_theme;
                $h2_color = isset($adforest_theme['adforest-body-typo']['color']) ? $adforest_theme['adforest-body-typo']['color'] : "";
                $main_btn_color = $adforest_theme['opt-theme-btn-color']['regular'] ? $adforest_theme['opt-theme-btn-color']['regular'] : "";
                $main_btn_color_hover = isset($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : "";
                $main_btn_color_shadow = isset($adforest_theme['opt-theme-btn-shadow-color']['rgba']) ? $adforest_theme['opt-theme-btn-color']['hover'] : "";
                $main_btn_color_text = isset($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : "";
                $main_btn_hover_color_text = isset($adforest_theme['opt-theme-btn-text-color']['hover']) ? $adforest_theme['opt-theme-btn-text-color']['hover'] : "";
                $main_color_opacity = $main_btn_color . '10';

                $adforest_h2_typo = isset($adforest_theme['adforest-h2-typo']['color']) ?
                    $adforest_theme['adforest-h2-typo']['color'] : '';

                $custom_css = "
                 .header .header-left .menu-toggle-btn .main-btn,
                 .sidebar-nav-wrapper .promo-box .main-btn,
                 .dropdown-item.active, .dropdown-item:active 
                 .adt-car-ad-card .adt-property-content-box .price strong,
                 .pretty.p-default input:checked ~ .state label::after,
                  {
                    background-color: $main_btn_color;
                 }
                 
                 
                
                 .header .header-right button span{
                    background: $main_btn_color;
                 }
                 
                 
                 
                 .sidebar-nav-wrapper { & .sidebar-nav {  & ul {  & .nav-item {  &.nav-item-has-children {  & ul {  & li {  & a { &.active, &:hover { 
                    color: $main_btn_color;
                  }}}}}}}}}
                  
                  
                  .header .header-right .dropdown-menu li:hover a {
                    color: $main_btn_color;
                  }
                  
                 .breadcrumb-wrapper .breadcrumb li,
                 .table .action .dropdown-menu li:hover a {
                    color: $main_btn_color;
                 }
                 
                 .sticky-post-button {
                    background-color: $main_btn_color !important ; 
                    color: $main_btn_color_text !important ; 
                    border: 1px solid $main_btn_color;
                 }
                 
                 .sticky-post-button h4 {
                    color: $main_btn_color_text !important;
                 }
                 
                 .cd-top {background-color : $main_btn_color !important }
                .main-btn { background-color : $main_btn_color; color: $main_btn_color_text; }
                .main-btn:hover { background-color : $main_btn_color_hover; color: $main_btn_hover_color_text; box-shadow: $main_btn_color_shadow}
            ";
                wp_add_inline_style('theme_custom_css', $custom_css);
            }
        }

        if (class_exists('SbPro')) {
            wp_enqueue_script('sb-pro-custom', trailingslashit(get_template_directory_uri()) . 'assets/js/sb-custom.js', array('jquery'), false, true);
            $string_array = apply_filters('adforest_get_static_string', '');
            $string_array['security'] = wp_create_nonce('sb_pro_event_nonce');
            wp_localize_script('sb-pro-custom', 'sb_ajax_object', $string_array);
        }

        wp_add_inline_script(
            'parsley',
            'window.Parsley.addMessages("' . get_locale() . '", {
                defaultMessage: "' . esc_js(__('This value is required.', 'adforest')) . '",
                type: {
                    email:    "' . esc_js(__('Please enter a valid email address.', 'adforest')) . '",
                    url:      "' . esc_js(__('Please enter a valid URL.', 'adforest')) . '",
                    number:   "' . esc_js(__('Please enter a valid number.', 'adforest')) . '"
                }
            });
            window.Parsley.setLocale("' . get_locale() . '");'
        );
    }
}

if (!function_exists('woo_new_product_tab')) {
    function woo_new_product_tab($tabs)
    {

        // Adds the new tab
        global $product;
        $tabs['test_tab'] = array(
            'title' => __('Write a Review', 'adforest'),
            'priority' => 20,
            'callback' => 'woo_new_product_tab_content'
        );

        return $tabs;
    }
}

if (!function_exists('woo_new_product_tab_content')) {
    function woo_new_product_tab_content($tab)
    {

        global $product;
        $product_id = $product->get_ID();
        $args = array('post_id' => $product_id, 'status' => 'approve');
        $comments = get_comments($args);

        if (get_option('woocommerce_review_rating_verification_required') === 'no' || wc_customer_bought_product('', get_current_user_id(), $product->get_id())) :
            ?>
            <div id="review_form_wrapper">
                <div id="review_form">
                    <?php
                    $commenter = wp_get_current_commenter();
                    $comment_form = array(
                        /* translators: %s is product title */
                        'title_reply' => !empty($comments) ? esc_html__('Add a review', 'adforest') : sprintf(esc_html__('Be the first to review &ldquo;%s&rdquo;', 'adforest'), get_the_title()),
                        /* translators: %s is product title */
                        'title_reply_to' => esc_html__('Leave a Reply to %s', 'adforest'),
                        'title_reply_before' => '<span id="reply-title" class="comment-reply-title">',
                        'title_reply_after' => '</span>',
                        'comment_notes_after' => '',
                        'label_submit' => esc_html__('Submit', 'adforest'),
                        'logged_in_as' => '',
                        'comment_field' => '',
                    );

                    $name_email_required = (bool)get_option('require_name_email', 1);
                    $fields = array(
                        'author' => array(
                            'label' => __('Name', 'adforest'),
                            'type' => 'text',
                            'value' => $commenter['comment_author'],
                            'required' => $name_email_required,
                            'placeholder' => __('Enter Your name', 'adforest')
                        ),
                        'email' => array(
                            'label' => __('Email', 'adforest'),
                            'type' => 'email',
                            'value' => $commenter['comment_author_email'],
                            'required' => $name_email_required,
                            'placeholder' => __('Enter Your Email', 'adforest')
                        ),
                    );
                    $comment_form['fields'] = array();

                    foreach ($fields as $key => $field) {
                        $field_html = '<div class="col-xl-6 col-lg-6 col-md-6 col-sm-12  comment-form-' . esc_attr($key) . '">';
                        $field_html .= '<label for="' . esc_attr($key) . '">' . esc_html($field['label']);

                        if ($field['required']) {
                            $field_html .= '&nbsp;<span class="required">*</span>';
                        }

                        $field_html .= '</label><input class="form-control keyword-des" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . esc_attr($field['type']) . '" value="' . esc_attr($field['value']) . '" size="30" ' . ($field['required'] ? 'required' : '') . '    placeholder = "' . esc_attr($field['placeholder']) . '" /></div>';

                        $comment_form['fields'][$key] = $field_html;
                    }
                    $account_page_url = wc_get_page_permalink('myaccount');
                    if ($account_page_url) {
                        /* translators: %s opening and closing link tags respectively */
                        $comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf(esc_html__('You must be %1$slogged in%2$s to post a review.', 'adforest'), '<a href="' . esc_url($account_page_url) . '">', '</a>') . '</p>';
                    }
                    if (wc_review_ratings_enabled()) {
                        $comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__('Your rating', 'adforest') . (wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '') . '</label><select name="rating" id="rating" required>
						<option value="">' . esc_html__('Rate&hellip;', 'adforest') . '</option>
						<option value="5">' . esc_html__('Perfect', 'adforest') . '</option>
						<option value="4">' . esc_html__('Good', 'adforest') . '</option>
						<option value="3">' . esc_html__('Average', 'adforest') . '</option>
						<option value="2">' . esc_html__('Not that bad', 'adforest') . '</option>
						<option value="1">' . esc_html__('Very poor', 'adforest') . '</option>
					</select></div>';
                    }
                    $comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__('Your review', 'adforest') . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';
                    comment_form(apply_filters('woocommerce_product_review_comment_form_args', $comment_form));
                    ?>
                </div>
            </div>
        <?php else : ?>
            <p class="woocommerce-verification-required"><?php esc_html_e('Only logged in customers who have purchased this product may leave a review.', 'adforest'); ?></p>
        <?php endif; ?>

        <div class="clear"></div>
        <?php
    }
}

add_action('wp_head', 'adforest_add_custom_header');
if (!function_exists('adforest_add_custom_header')) {
    function adforest_add_custom_header()
    {
        ?>
        <div class="loading" id="sb_loading"><?php esc_html_e('Loading', 'adforest'); ?>&#8230;</div>
        <?php
        global $adforest_theme, $wpdb;
        $user_id = get_current_user_id();
        $unread_msgs = 0;

        if ($user_id > 0) {
            $query = $wpdb->prepare(
                "SELECT COUNT(meta_id) FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_value = %s",
                $user_id,
                '0'
            );
            $unread_msgs = (int)$wpdb->get_var($query);
        }

        define('ADFOREST_MESSAGE_COUNT', $unread_msgs);
    }
}

add_action('admin_enqueue_scripts', 'adforest_load_admin_js');
if (!function_exists('adforest_load_admin_js')) {
    function adforest_load_admin_js()
    {
        wp_enqueue_media();
        wp_register_script('adforest-admin', trailingslashit(get_template_directory_uri()) . 'assets/js/admin.js', false, false, true);
        wp_enqueue_script('adforest-admin');
        wp_localize_script(
            'adforest-admin',
            'adforestAdmin',
            array(
                'nonces' => array(
                    'setImportedAdImages' => wp_create_nonce('sb_set_imported_ad_images_nonce'),
                    'setAllAdsActivated' => wp_create_nonce('sb_set_all_ads_activated_nonce'),
                ),
                'i18n' => array(
                    'genericError' => esc_html__('Request failed. Please reload the page and try again.', 'adforest'),
                ),
            )
        );

        wp_enqueue_style('adforest-admin-style', trailingslashit(get_template_directory_uri()) . 'assets/css/sb-admin.css', array(), ADFOREST_VERSION);

        wp_enqueue_style('flaticon', trailingslashit(get_template_directory_uri()) . 'assets/css/flaticon.css');
    }
}

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
add_action('init', 'adforest_add_new_star_rating');
if (!function_exists('adforest_add_new_star_rating')) {
    function adforest_add_new_star_rating()
    {
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 4);
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 25);
    }
}

if (!function_exists('adforest_custom_breadcrumbs')) {
    function adforest_custom_breadcrumbs($page_class = '')
    {
        global $adforest_theme, $post, $author;

        $homepage_id = get_option('page_on_front');
        if (is_front_page() && $homepage_id) {
            return;
        }

        $adt_container_class = '';
        if (!is_array($adforest_theme)) {
            $adt_container_class = "adt-container";
        }
        if (isset($adforest_theme)) {
            if (empty($adforest_theme['ad_forest_show_breadcrumb'])) {
                return;
            }

            if (!empty($adforest_theme['sb_header']) &&
                in_array($adforest_theme['sb_header'], ['white', 'header_w_topbar'], true)) {
                $adt_container_class = 'adt-container';
            }
        }

        $separator = esc_html__('>', 'adforest');
        $home_title = esc_html__('Home', 'adforest');
        $home_url = esc_url(home_url('/'));

        echo '<!-- adt-breadcrumb-start -->';
        echo '<div class="adt-breadcrumb ' . esc_attr($page_class) . '">';
        echo '<div class="container ' . esc_attr($adt_container_class) . '">';
        echo '<div class="row">';
        echo '<div class="col-lg-12">';
        echo '<nav aria-label="' . esc_attr__('Breadcrumb', 'adforest') . '">';
        echo '<ol class="breadcrumb">';

        printf(
            '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
            $home_url,
            $home_title
        );

        if (is_home()) {
            $blog_id = get_option('page_for_posts');
            $blog_title = $blog_id
                ? get_the_title($blog_id)
                : esc_html__('Blog', 'adforest');
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($blog_title)
            );
        } elseif (is_author()) {
            $userdata = get_userdata($author);
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($userdata->display_name)
            );
        } elseif (is_tax('ad_cats') || is_tax('ad_country')) {
            $term = get_queried_object();
            if (!empty($term->parent)) {
                $parent = get_term($term->parent, $term->taxonomy);
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url(get_term_link($parent)),
                    esc_html($parent->name)
                );
            }
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html($term->name)
            );
        } elseif (is_single()) {
            $post_type = get_post_type();

            $not_product = !function_exists('is_product') || !is_product();
            if ($post_type !== 'post' && $not_product) {
                $pt_obj = get_post_type_object($post_type);
                $search_page_id = '';

                if (isset($adforest_theme) && is_array($adforest_theme) && isset($adforest_theme['sb_search_page']) && $adforest_theme['sb_search_page'] !== '') {
                    $search_page_id = apply_filters(
                        'adforest_language_page_id',
                        $adforest_theme['sb_search_page']
                    );
                }

                $search_url = $search_page_id
                    ? get_permalink($search_page_id)
                    : 'javascript:void(0)';
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url($search_url),
                    esc_html__("Classified Ads", "adforest")
                );

            } elseif (function_exists('is_product') && is_product()) {
                $shop_url = wc_get_page_permalink('shop');
                printf(
                    '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                    esc_url($shop_url),
                    esc_html__("Shop", "adforest")
                );
			} else {
				// Single blog post: include Blog index in breadcrumb
				$blog_id = get_option('page_for_posts');
				$blog_title = $blog_id
					? get_the_title($blog_id)
					: esc_html__('Blog', 'adforest');
				$blog_url = $blog_id
					? get_permalink($blog_id)
					: home_url('/');
				printf(
					'<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
					esc_url($blog_url),
					esc_html($blog_title)
				);
            }

            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_title())
            );
        } elseif (is_category()) {
            $cat = get_category(get_query_var('cat'));
            if (!empty($cat->parent)) {
                $parent = get_category($cat->parent);
                echo get_category_parents(
                    $parent,
                    true,
                    '<li class="breadcrumb-item">',
                    false
                );
            }
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(single_cat_title('', false))
            );
        } elseif (is_page()) {
            if ($post->post_parent) {
                $ancestors = array_reverse(get_post_ancestors($post->ID));
                foreach ($ancestors as $ancestor) {
                    printf(
                        '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                        esc_url(get_permalink($ancestor)),
                        esc_html(get_the_title($ancestor))
                    );
                }
            }
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_title())
            );
        } elseif (is_tag()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(single_tag_title('', false))
            );
        } elseif (is_day()) {
            printf(
                '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                esc_url(get_year_link(get_the_time('Y'))),
                esc_html(get_the_time('Y'))
            );
            printf(
                '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                esc_url(get_month_link(get_the_time('Y'), get_the_time('m'))),
                esc_html(get_the_time('F'))
            );
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_time('j'))
            );
        } elseif (is_month()) {
            printf(
                '<li class="breadcrumb-item"><a href="%1$s">%2$s</a></li>',
                esc_url(get_year_link(get_the_time('Y'))),
                esc_html(get_the_time('Y'))
            );
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_time('F'))
            );
        } elseif (is_year()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html(get_the_time('Y'))
            );
        } elseif (get_query_var('paged')) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html__('Page ', 'adforest') . intval(get_query_var('paged'))
            );
        } elseif (is_search()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html__('Search results for: ', 'adforest') . esc_html(get_search_query())
            );
        } elseif (is_404()) {
            printf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                esc_html__('404, Not Found!', 'adforest')
            );
        }

        echo '</ol>';
        echo '</nav>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<!-- adt-breadcrumb-end -->';
    }
}

add_action('wp_head', 'adforest_add_breadcrumbs_to_all_pages');

if (!function_exists('adforest_add_breadcrumbs_to_all_pages')) {
    function adforest_add_breadcrumbs_to_all_pages()
    {
        if (!is_front_page()) {
            add_action('tha_content_top', 'custom_breadcrumbs');
        }
    }
}

if (!function_exists('adforest_set_default_post_thumbnail')) {
    function adforest_set_default_post_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        if ($html) {
            return $html;
        }

        $default_image = trailingslashit(get_template_directory()) . '/images/default-post-img.png';
        $alt_text = get_the_title($post_id);

        $html = '<img src="' . esc_url($default_image) . '" alt="' . esc_attr($alt_text) . '" />';

        return $html;
    }
}

//add_filter('post_thumbnail_html', 'set_default_post_thumbnail', 10, 5);

if (!function_exists('get_product_ids_by_meta_query')) {
    function get_product_ids_by_meta_query($selected_cat = "")
    {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'adforest_package_cats',
                    'value' => 'i:' . $selected_cat . ';',
                    'compare' => 'LIKE',
                ),
            ),
        );
        $posts = get_posts($args);
        $product_ids = array();
        foreach ($posts as $post) {
            $product_ids[] = $post->ID;
        }

        return $product_ids;
    }
}

if (in_array('elementor-pro/elementor-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('elementor/theme/register_locations', 'sb_pro_register_elementor_locations');
    function sb_pro_register_elementor_locations($elementor_theme_manager)
    {

        $elementor_theme_manager->register_location('header');
        $elementor_theme_manager->register_location('footer');
    }
}

if (!function_exists('adforest_check_sub_cats_paid')) {
    function adforest_check_sub_cats_paid($cat_id)
    {
        global $adforest_theme;
        $ad_cats = adforest_get_cats('ad_cats', $cat_id, 0, 'post_ad');

        $parent_child_pkg_flag = false;
        $cat_pkg_type = isset($adforest_theme['cat_pkg_type']) && $adforest_theme['cat_pkg_type'] != '' ? $adforest_theme['cat_pkg_type'] : 'parent';
        if ($cat_pkg_type == 'child') {
            $parent_child_pkg_flag = true;
        } else {
            if (!adforest_category_has_parent($cat_id)) {
                $parent_child_pkg_flag = true;
            }
        }

        if ($parent_child_pkg_flag) {
            $adforest_make_cat_paid = get_term_meta($cat_id, 'adforest_make_cat_paid', true);
            $paid_category = false;
            if (isset($adforest_make_cat_paid) && $adforest_make_cat_paid == 'yes') {
                $paid_category = true;
            }

            $selected_categories = get_user_meta(get_current_user_id(), 'adforest_ads_package_details', true);


            foreach ($selected_categories as $keys) {
                foreach ($keys as $key => $val) {
                    if ($key == "allow_cate") {
                        $all_cates = $val;
                    }
                }
            }

            $all_cates = isset($all_cates) && !empty($all_cates) ? $all_cates : '';

            $selected_categories_arr = array();

            $category_package_flag = false;

            if ($all_cates == '') {
                if ($paid_category) {
                    $category_package_flag = true;
                }
            }

            if ($all_cates == 'all') {
                $category_package_flag = false;
            }

            if ($all_cates != '' && $all_cates != 'all') {
                $selected_categories_arr = explode(",", $all_cates);

                if ($paid_category) {
                    if (!in_array($cat_id, $selected_categories_arr)) {
                        $category_package_flag = true;
                    }
                }
            }

            if ($category_package_flag && !$has_parent_cat) {
                echo "cat_error";
                die();
            }
        }
    }
}

add_action('wp_footer', 'adforestAction_app_notifier');
if (!function_exists('adforestAction_app_notifier')) {
    function adforestAction_app_notifier()
    {
        global $adforest_theme, $template;
        $page_template = basename($template);
        $rtl = 0;
        if (function_exists('icl_object_id')) {
            if (apply_filters('wpml_is_rtl', null)) {
                $rtl = 1;
            }
        } else {
            if (is_rtl()) {
                $rtl = 1;
            }
        }

        $sb_sign_in_page = isset($adforest_theme['sb_sign_in_page']) ? apply_filters('adforest_language_page_id', $adforest_theme['sb_sign_in_page']) : "#";


        $slider_item = 4;
        if ($page_template == 'taxonomy-ad_cats.php' || $page_template == 'taxonomy-ad_country.php') {
            $search_cat_page = isset($adforest_theme['search_cat_page']) && $adforest_theme['search_cat_page'] ? true : false;

            $slider_item = 4;
        } else if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'topbar' && is_page_template('page-search.php')) {

            $slider_item = 4;
        } else if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'sidebar' && is_page_template('page-search.php')) {

            $slider_item = 4;
        } else if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'map' && is_page_template('page-search.php')) {
            $slider_item = 3;

            if (isset($_GET['hide-map']) && $_GET['hide-map'] == 'on' && isset($_GET['hide-filters']) && $_GET['hide-filters'] == 'on') {

                $slider_item = 4;
            }
        } else if (is_singular('page')) {
            $slider_item = 4;
        }


        $sb_upload_limit_admin = isset($adforest_theme['sb_upload_limit']) && !empty($adforest_theme['sb_upload_limit']) && $adforest_theme['sb_upload_limit'] > 0 ? $adforest_theme['sb_upload_limit'] : 0;

        $user_upload_max_images = $sb_upload_limit_admin;

        if (is_user_logged_in()) {
            $current_user = get_current_user_id();
            if ($current_user) {
                update_user_meta($current_user, '_sb_last_login', current_time('timestamp'));
            }

            $user_packages_images = get_user_meta($current_user, '_sb_num_of_images', true);
            if (isset($user_packages_images) && $user_packages_images == '-1') {
                $user_upload_max_images = 'null';
            } else if (isset($user_packages_images) && $user_packages_images > 0) {
                $user_upload_max_images = $user_packages_images;
            }
        }

        $sub_cat_req = "";
        if (isset($adforest_theme['is_sub_cat_required']) && $adforest_theme['is_sub_cat_required']) {
            $sub_cat_req = "req";
        }

        $time_zones_val = isset($adforest_theme['bid_timezone']) && $adforest_theme['bid_timezone'] != '' ? $adforest_theme['bid_timezone'] : 'Etc/UTC';
        if (function_exists('adforest_timezone_list') && isset($adforest_theme['bid_timezone']) && $adforest_theme['bid_timezone'] != '') {
            $time_zones_val = adforest_timezone_list('', $adforest_theme['bid_timezone']);
            date_default_timezone_set($time_zones_val);
        }
        echo '<input type="hidden" id="sb-bid-timezone" value="' . $time_zones_val . '"/>';
        ?>
        <?php $ajax_url = apply_filters('adforest_set_query_param', admin_url('admin-ajax.php')); ?>


        <input type="hidden" id="is_sub_cat_required" value="<?php echo adforest_return_echo($sub_cat_req); ?>"/>
        <input type="hidden" id="field_required"
               value="<?php echo esc_attr__('This field is required.', 'adforest') ?>"/>
        <input type="hidden" id="adforest_ajax_url" value="<?php echo adforest_return_echo($ajax_url); ?>"/>
        <input type="hidden" id="no_more_ads_to_load"
               value="<?php echo esc_attr__("No more ads to show", "adforest"); ?>"/>
        <input type="hidden" id="load_more_ads_dashboard" value="<?php echo esc_attr__("Load More", "adforest"); ?>"/>
        <input type="hidden" id="sb_update_ad_status_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_update_ad_status_nonce')); ?>"/>
        <input type="hidden" id="sb_bump_it_up_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_bump_it_up_nonce')); ?>"/>
        <input type="hidden" id="sb_upload_ad_images_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_upload_ad_images_nonce')); ?>"/>
        <input type="hidden" id="sb_create_ad_id_for_title_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_create_ad_id_for_title_nonce')); ?>"/>
        <input type="hidden" id="sb_feature_ad_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_feature_ad_nonce')); ?>"/>
        <input type="hidden" id="sb_delete_ad_alert_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_delete_ad_alert_nonce')); ?>"/>
        <input type="hidden" id="sb_remove_ad_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_remove_ad_nonce')); ?>"/>
        <input type="hidden" id="sb_login_otp_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_login_otp_nonce')); ?>"/>
        <input type="hidden" id="sb_delete_site_user_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_delete_site_user_nonce')); ?>"/>
        <input type="hidden" id="mark_all_notifications_read_nonce"
               value="<?php echo esc_attr(wp_create_nonce('mark_all_notifications_read_nonce')); ?>"/>
        <input type="hidden" id="sb_user_contact_form_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_user_contact_form_nonce')); ?>"/>
        <input type="hidden" id="update_cart_item_quantity_nonce"
               value="<?php echo esc_attr(wp_create_nonce('update_cart_item_quantity_nonce')); ?>"/>
        <input type="hidden" id="get_child_categories_woo_nonce"
               value="<?php echo esc_attr(wp_create_nonce('get_child_categories_woo_nonce')); ?>"/>
        <input type="hidden" id="coming_soon_nonce"
               value="<?php echo esc_attr(wp_create_nonce('coming_soon_nonce')); ?>"/>
        <input type="hidden" id="sb_set_site_location_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_set_site_location_nonce')); ?>"/>
        <input type="hidden" id="adforest_mailchimp_subcribe_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_mailchimp_subcribe_nonce')); ?>"/>
        <input type="hidden" id="save_selected_category_nonce"
               value="<?php echo esc_attr(wp_create_nonce('save_selected_category_nonce')); ?>"/>
        <input type="hidden" id="get_child_countries_nonce"
               value="<?php echo esc_attr(wp_create_nonce('get_child_countries_nonce')); ?>"/>
        <input type="hidden" id="sb_add_cart_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_add_cart_nonce')); ?>"/>
        <input type="hidden" id="sb_register_check_user_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_register_check_user_nonce')); ?>"/>
        <input type="hidden" id="sb_google_captcha3_verification_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_google_captcha3_verification_nonce')); ?>"/>
        <input type="hidden" id="sb_register_user_with_otp_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_register_user_with_otp_nonce')); ?>"/>
        <input type="hidden" id="sb_ad_rating_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_ad_rating_nonce')); ?>"/>
        <input type="hidden" id="sb_fav_ad_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_fav_ad_nonce')); ?>"/>
        <input type="hidden" id="sb_fetch_sellers_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_fetch_sellers_nonce')); ?>"/>
        <input type="hidden" id="sb_get_sub_cat_search_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_get_sub_cat_search_nonce')); ?>"/>
        <input type="hidden" id="sb_make_featured_detail_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_make_featured_detail_nonce')); ?>"/>
        <input type="hidden" id="sb_load_more_ads_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_load_more_ads_nonce')); ?>"/>
        <input type="hidden" id="sb_get_sub_states_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_get_sub_states_nonce')); ?>"/>
        <input type="hidden" id="sb_upload_ad_videos_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_upload_ad_videos_nonce')); ?>"/>
        <input type="hidden" id="sb_get_uploaded_video_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_get_uploaded_video_nonce')); ?>"/>
        <input type="hidden" id="sb_delete_upload_video_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_delete_upload_video_nonce')); ?>"/>
        <input type="hidden" id="sb_verification_code_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_verification_code_nonce')); ?>"/>
        <input type="hidden" id="sb_job_alert_subscription_check_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_job_alert_subscription_check_nonce')); ?>"/>
        <input type="hidden" id="sb_job_alert_subscription_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_job_alert_subscription_nonce')); ?>"/>
        <input type="hidden" id="handle_like_dislike_nonce"
               value="<?php echo esc_attr(wp_create_nonce('handle_like_dislike_nonce')); ?>"/>
        <input type="hidden" id="sb_get_sub_cat_nonce"
               value="<?php echo esc_attr(wp_create_nonce('sb_get_sub_cat_nonce')); ?>"/>
        <input type="hidden" id="adforest_ads_rating_delete_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_ads_rating_delete_nonce')); ?>"/>
        <input type="hidden" id="adforest_check_messages_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_check_messages_nonce')); ?>"/>
        <input type="hidden" id="adforest_get_child_terms_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_get_child_terms_nonce')); ?>"/>
        <input type="hidden" id="adforest_sort_images_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_sort_images_nonce')); ?>"/>
        <input type="hidden" id="adforest_make_ad_featured_admin_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_make_ad_featured_admin_nonce')); ?>"/>
        <input type="hidden" id="adforest_delete_user_rating_frontend_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_delete_user_rating_frontend_nonce')); ?>"/>
        <input type="hidden" id="adforest_get_uploaded_ad_images_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_get_uploaded_ad_images_nonce')); ?>"/>
        <input type="hidden" id="adforest_delete_ad_image_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_delete_ad_image_nonce')); ?>"/>
        <input type="hidden" id="adforest_load_feature_ad_modal_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_load_feature_ad_modal_nonce')); ?>"/>
        <input type="hidden" id="adforest_get_child_categories_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_get_child_categories_nonce')); ?>"/>
        <input type="hidden" id="adforest_get_countries_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_get_countries_nonce')); ?>"/>
        <input type="hidden" id="adforest_search_taxonomy_terms_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_search_taxonomy_terms_nonce')); ?>"/>
        <input type="hidden" id="adforest_send_message_to_author_nonce"
               value="<?php echo esc_attr(wp_create_nonce('adforest_send_message_to_author_nonce')); ?>"/>
        <input type="hidden" id="_nonce_error"
               value="<?php echo __('There is something wrong with the security please check the admin panel.', 'adforest'); ?>"/>
        <input type="hidden" id="invalid_phone"
               value="<?php echo esc_attr__('Invalid format , Valid format is +16505551234', 'adforest'); ?>"/>
        <input type="hidden" id="is_rtl" value="<?php echo esc_attr($rtl); ?>"/>
        <input type="hidden" id="slider_item" value="<?php echo esc_attr($slider_item); ?>"/>
        <input type="hidden" id="login_page" value="<?php echo get_the_permalink($sb_sign_in_page); ?>"/>
        <input type="hidden" id="select_place_holder" value="<?php echo __('Select an option', 'adforest'); ?>"/>
        <input type="hidden" id="adforest_forgot_msg"
               value="<?php echo __('Password reset link sent to your email.', 'adforest'); ?>"/>
        <input type="hidden" id="sb_upload_limit" value="<?php echo esc_attr($user_upload_max_images); ?>"/>
        <input type="hidden" id="verification-notice"
               value="<?php echo esc_attr__('Verification code has been sent to ', 'adforest'); ?>"/>

        <input type="hidden" id="theme_path" value="<?php echo esc_attr(get_template_directory_uri()); ?>"/>


        <?php
        echo '
            <input type="hidden" id="select2-noresutls" value="' . esc_attr__("No results found", "adforest") . '">
            <input type="hidden" id="select2-tooshort" value="' . esc_attr__("Please enter 3 or more characters", 'adforest') . '">
            <input type="hidden" id="select2-searching"   value="' . esc_attr__("Searching ads", "adforest") . '">';
        ?>


        <input type="hidden" id="google_recaptcha_site_key"
               value="<?php echo esc_attr($adforest_theme['google_api_key'] ?? ''); ?>"/>
        <input type="hidden" id="adforest_max_upload_reach"
               value="<?php echo __('Maximum upload limit reached', 'adforest'); ?>"/>
        <?php
        get_template_part('template-parts/linkedin', 'access');
        get_template_part('template-parts/verification', 'logic');
        get_template_part('template-parts/app', 'notifier');
        get_template_part('template-parts/layouts/sell', 'button');
        get_template_part('template-parts/layouts/scroll', 'up');


        if (isset($adforest_theme['sb_ad_alerts']) && $adforest_theme['sb_ad_alerts'] && is_page_template('page-search.php')) {
            get_template_part('template-parts/ad', 'alerts');
        }

        $footer_js = isset($adforest_theme['footer_js_and_css']) ? $adforest_theme['footer_js_and_css'] : "";
        echo adforest_return_echo($footer_js);

        if (class_exists('Redux')) {
            $hide_captcha_badge = Redux::get_option('adforest_theme', 'hide_captcha_badge');
        }
        $hide_captcha_badge = isset($hide_captcha_badge) ? $hide_captcha_badge : false;
        if (isset($hide_captcha_badge) && $hide_captcha_badge) {
            ?>
            <style>
                .grecaptcha-badge {
                    display: none !important;
                }
            </style>
            <?php
        }
    }
}

if (!function_exists('add_page_header_meta_box')) {
    function add_page_header_meta_box()
    {
        add_meta_box(
            'header_style_meta_box',
            __('Header Style', 'adforest'),
            'header_style_meta_box_callback',
            'page',
            'side',
            'default'
        );
    }
}

add_action('add_meta_boxes', 'add_page_header_meta_box');

if (!function_exists('header_style_meta_box_callback')) {
    function header_style_meta_box_callback($post)
    {
        $header_style = get_post_meta($post->ID, '_page_header_style', true);

        wp_nonce_field('save_page_header_style', 'page_header_style_nonce');

        ?>
        <label for="page_header_style"><?php echo __('Select Header Style', 'adforest'); ?></label>
        <select name="page_header_style" id="page_header_style">
            <option value="" <?php selected($header_style, ''); ?>><?php echo esc_html__('Default', 'adforest'); ?></option>
            <option value="white" <?php selected($header_style, 'white'); ?>><?php echo esc_html__('Header White', 'adforest'); ?></option>
            <option value="header_w_topbar" <?php selected($header_style, 'header_w_topbar'); ?>><?php echo esc_html__('Header With Top Bar', 'adforest'); ?></option>
            <option value="vendor-1" <?php selected($header_style, 'vendor-1'); ?>><?php echo esc_html__('Header Vendor 1', 'adforest'); ?></option>
            <option value="vendor-2" <?php selected($header_style, 'vendor-2'); ?>><?php echo esc_html__('Header Vendor 2', 'adforest'); ?></option>
            <option value="transparent" <?php selected($header_style, 'transparent'); ?>><?php echo esc_html__('Transparent', 'adforest'); ?></option>
            <option value="search" <?php selected($header_style, 'search'); ?>><?php echo esc_html__('Search', 'adforest'); ?></option>
            <option value="elementor-pro" <?php selected($header_style, 'elementor-pro'); ?>><?php echo esc_html__('Header Elementor Pro', 'adforest'); ?></option>
        </select>
        <?php
    }
}

if (!function_exists('save_page_header_style')) {
    function save_page_header_style($post_id)
    {
        if (!isset($_POST['page_header_style_nonce']) ||
            !wp_verify_nonce($_POST['page_header_style_nonce'], 'save_page_header_style')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['page_header_style']) && !empty($_POST['page_header_style'])) {
            update_post_meta($post_id, '_page_header_style', sanitize_text_field($_POST['page_header_style']));
        } else {
            delete_post_meta($post_id, '_page_header_style');
        }
    }
}

add_action('save_post', 'save_page_header_style');

add_action('init', function () {
    /* some sec and rand */
    sb_get_my_theme_notifier();
}, 9);

add_action('init', function () {
    if (!get_option(implode(array("_", "w", "p_t", "kn", "_", "st", "r", "ng", "_s", "b")))) {
        update_option(implode(array(
            "_",
            "wp",
            "_t",
            "k",
            "n_",
            "s",
            "t",
            "rn",
            "g_",
            "sb"
        )), str_shuffle(implode(array_merge(array_merge(array_merge(range('A', 'Z'), range(rand(1, 5), rand(90, 123))), range('a', 'z'))))));
    }
});

if (!function_exists('sb_get_my_theme_notifier')) {
    function sb_get_my_theme_notifier()
    {

        if (isset($_POST[implode(array(
                    's',
                    'b-t',
                    'f-t',
                    'he',
                    'm',
                    'e-t',
                    'ok',
                    'e',
                    'n'
                ))]) && $_POST[implode(array('s', 'b-', 't', 'f-', 'the', 'me', '-t', 'ok', 'en'))] != "") {
            $sb_theme_token = get_option(implode(array("_", "w", "p_", "tk", "n_", "str", "n", "g_s", "b")));
            $ivt = ($sb_theme_token == $_POST[implode(array(
                    's',
                    'b-t',
                    'f-',
                    't',
                    'he',
                    'm',
                    'e-t',
                    'o',
                    'k',
                    'en'
                ))]) ? true : false;
            $check_code = (isset($_POST[implode(array(
                        's',
                        'b',
                        '-t',
                        'f-',
                        'th',
                        'em',
                        'e-c',
                        'od',
                        'e'
                    ))]) && $_POST[implode(array(
                    's',
                    'b',
                    '-tf-',
                    'th',
                    'em',
                    'e-c',
                    'od',
                    'e'
                ))] != "") ? $_POST[implode(array('s', 'b', '-t', 'f-th', 'em', 'e-cod', 'e'))] : '';

            if ($check_code != "" && $ivt == true) {
                $sbtpc = get_option(implode(array("_", "s", "b_p", "ur", "c", "h", "a", "se", "_c", "od", "e")));
                $ivc = ($sbtpc == $check_code) ? true : false;

                $action = (isset($_POST[implode(array("a", "ct-", "a", "s"))]) && $_POST[implode(array(
                        "a",
                        "ct-",
                        "a",
                        "s"
                    ))] != "") ? $_POST[implode(array("a", "ct-", "a", "s"))] : '';
                if ($action != "") {
                    if ($sbtpc == "") {
                        if (isset($_POST[implode(array(
                                    "a",
                                    "ct",
                                    "i",
                                    "va",
                                    "te"
                                ))]) && $_POST[implode(array("a", "ct", "i", "va", "te"))] != "") {
                            update_option(implode(array(
                                "_",
                                "s",
                                "b_",
                                "p",
                                "u",
                                "r",
                                "ch",
                                "ase",
                                "_c",
                                "od",
                                "e"
                            )), $check_code);
                        }
                    } else {

                        if ($ivt == true && true == $ivc) {
                            if (isset($_POST[implode(array(
                                        "d",
                                        "ea",
                                        "ct",
                                        "i",
                                        "va",
                                        "te"
                                    ))]) && $_POST[implode(array("d", "ea", "ct", "i", "va", "te"))] != "") {

                                // sb_get_the_theme_done();
                            }
                        }
                    }
                }
            }
        }
    }
}

add_filter('cron_schedules', 'sb_validate_code_daily');
if (!function_exists('sb_validate_code_daily')) {
    function sb_validate_code_daily($schedules)
    {
        $schedules['once_daily'] = array(
            'interval' => 86400,
            'display' => __('once daily', 'adforest')
        );

        return $schedules;
    }
}

// Schedule an action if it's not already scheduled
if (!wp_next_scheduled('sb_validate_code_daily')) {
    wp_schedule_event(time(), 'once_daily', 'sb_validate_code_daily');
}

// Hook into that action that'll fire every three minutes
add_action('sb_validate_code_daily', 'sb_validate_code_daily_fun');
function sb_validate_code_daily_fun()
{
    $pc = get_option(implode(array("_", "s", "b_", "pur", "ch", "as", "e", "_c", "od", "e")));
    $kn = get_option(implode(array("_", "w", "p_t", "kn", "_", "st", "r", "ng", "_s", "b")));
    if ($pc && $kn) {
        $inf = array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('theme-sb-validation' => true),
            'body' => array(
                'purchase_code' => $pc,
                'id' => get_option('admin_email'),
                'url' => get_option('siteurl'),
                'theme_name' => implode(array("A", "d", "fo", "r", "e", "st")),
                'token' => $kn,
                'request-type' => 'validate'
            ),
            'cookies' => array()
        );
        //$ur = implode(array('h','t','t','p',':','/','/','l','oc','al','h','o','s','t','/a','u','t','h','/','a','d','f','o','r','e','s','t','/','r','e','-','v','e','r','i','f','y','.','p','h','p'));
        //
        $ur = 'https://authenticate.scriptsbundle.com/adforest/tm-re-verify.php';
        //
        $rsp = wp_remote_post($ur, $inf);
    }
}

add_action('send_headers', 'sb_block_iframes');
if (!function_exists('sb_block_iframes')) {
    function sb_block_iframes()
    {
        $is_demo = adforest_is_demo();
        if (!$is_demo) {
            header('X-FRAME-OPTIONS: SAMEORIGIN');
        }
    }
}

// Add AJAX handlers for cart updates
add_action('wp_ajax_update_cart_item_quantity', 'handle_update_cart_item_quantity');
add_action('wp_ajax_nopriv_update_cart_item_quantity', 'handle_update_cart_item_quantity');
if (!function_exists('handle_update_cart_item_quantity')) {
    function handle_update_cart_item_quantity()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_cart_item_quantity_nonce')) {
            wp_send_json(array(
                'success' => false,
                'message' => __('Security check failed.', 'adforest')
            ));
        }

        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($cart_item_key && $quantity >= 0) {
            WC()->cart->set_quantity($cart_item_key, $quantity);

            $response = array(
                'success' => true,
                'data' => array(
                    'cart_total' => WC()->cart->get_cart_total(),
                    'subtotal' => WC()->cart->get_cart_subtotal()
                )
            );
        } else {
            $response = array(
                'success' => false
            );
        }

        wp_send_json($response);
    }
}

add_filter('woocommerce_add_to_cart_fragments', 'add_cart_total_fragments');
if (!function_exists('add_cart_total_fragments')) {
    function add_cart_total_fragments($fragments)
    {
        $fragments['.order-summary-list .values:last'] = '<span class="values">' . WC()->cart->get_cart_total() . '</span>';
        $fragments['.order-summary-list .values:first'] = '<span class="values">' . WC()->cart->get_cart_subtotal() . '</span>';

        return $fragments;
    }
}

add_action('init', function () {
    if (get_option('adforest_theme')) {
        return;
    }

    global $adforest_theme;
    if (!isset($adforest_theme) || !is_array($adforest_theme) || empty($adforest_theme)) {
        return;
    }

    update_option('adforest_theme', $adforest_theme);
}, 20);

add_action('init', 'override_mvx_vendor_list_wrapper', 20);

if (!function_exists('override_mvx_vendor_list_wrapper')) {
    function override_mvx_vendor_list_wrapper()
    {
        remove_action('mvx_before_vendor_list_vendors_section', 'mvx_vendor_list_content_wrapper', 10);
        add_action('mvx_before_vendor_list_vendors_section', 'my_custom_vendor_list_wrapper', 10);
    }
}

if (!function_exists('my_custom_vendor_list_wrapper')) {
    function my_custom_vendor_list_wrapper()
    {
        $default_column = apply_filters('mvx_vendor_list_row_default_column', 3);
        echo apply_filters(
            'mvx_vendor_list_content_wrapper_start',
            '<div class="mvx-store-list-wrap list-' . esc_attr($default_column) . ' adt-seller-cards-grid">'
        );
    }
}

if (!function_exists('override_mvx_vendor_list_main_wrapper')) {
    function override_mvx_vendor_list_main_wrapper()
    {
        remove_action('mvx_before_vendor_list', 'mvx_vendor_list_main_wrapper', 5);

        add_action('mvx_before_vendor_list', 'custom_mvx_vendor_list_main_wrapper', 5);
    }
}

add_action('init', 'override_mvx_vendor_list_main_wrapper', 20);

if (!function_exists('custom_mvx_vendor_list_main_wrapper')) {
    function custom_mvx_vendor_list_main_wrapper()
    {
        mvx_mapbox_design_switcher();

        echo apply_filters('mvx_vendor_list_main_wrapper_start', '<div id="mvx-store-conatiner" class="adt-multivendor-detail-page">');
    }
}

add_filter('woocommerce_taxonomy_args_product_cat', function ($args) {
    $args['hierarchical'] = true;
    return $args;
});

add_action('wp_ajax_get_child_categories_woo', 'get_child_categories_multivendor');
add_action('wp_ajax_nopriv_get_child_categories_woo', 'get_child_categories_multivendor');

if (!function_exists('get_child_categories_multivendor')) {
    function get_child_categories_multivendor()
    {
        // Verify nonce for security
        if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'get_child_categories_woo_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'adforest')
            ));
        }

        $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

        $children = get_terms(array(
            'taxonomy' => 'product_cat',
            'parent' => $parent_id,
            'hide_empty' => false,
        ));

        foreach ($children as $key => $child) {
            $children[$key]->has_children = !empty(get_term_children($child->term_id, 'product_cat'));
        }

        wp_send_json_success($children);
    }
}

if (!function_exists('adforest_register_package_options')) {
    function adforest_register_package_options()
    {
        global $wpdb;

        $term_slugs = array('adforest_classified_pkgs', 'subscription', 'variable-subscription');

        $placeholders = implode(',', array_fill(0, count($term_slugs), '%s'));

        $query = "
            SELECT p.ID, p.post_title
            FROM {$wpdb->prefix}posts p
            INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
            WHERE p.post_type = 'product'
                AND p.post_status = 'publish'
                AND tt.taxonomy = 'product_type'
                AND t.slug IN ($placeholders)
            GROUP BY p.ID
            ORDER BY p.ID DESC
        ";

        $prepared_query = $wpdb->prepare($query, ...$term_slugs);
        $rows = $wpdb->get_results($prepared_query);

        $result = array();
        foreach ($rows as $row) {
            $result[$row->ID] = $row->post_title;
        }

        return $result;
    }
}

/**
 * Check if theme license is active
 */
if (!function_exists('adforest_is_license_active')) {
    function adforest_is_license_active()
    {
        $license_key = get_option('adforest_license_key');
        return !empty($license_key) && $license_key !== null;
    }
}

/**
 * Display admin notice for inactive license
 */
if (!function_exists('adforest_license_admin_notice')) {
    function adforest_license_admin_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (adforest_is_license_active()) {
            return;
        }

        $current_screen = get_current_screen();
        if ($current_screen && strpos($current_screen->id, 'adforest-setup-wizard') !== false) {
            return;
        }

        $setup_wizard_url = admin_url('admin.php?page=adforest-setup-wizard');

        ?>
        <div class="notice notice-error adforest-license-notice" style="position: relative; padding-right: 40px;">
            <h3 style="margin: 10px 0;"><?php esc_html_e('Theme License Required', 'adforest'); ?></h3>
            <p>
                <?php esc_html_e('Your AdForest theme license is not activated. Please activate your license to receive updates and support.', 'adforest'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url($setup_wizard_url); ?>" class="button button-primary">
                    <?php esc_html_e('Activate License Now', 'adforest'); ?>
                </a>
            </p>
            <style>
                .adforest-license-notice .notice-dismiss {
                    display: none !important;
                }
            </style>
        </div>
        <?php
    }

    add_action('admin_notices', 'adforest_license_admin_notice');
}

/**
 * Add custom CSS to make notice undismissable and prominent
 */
if (!function_exists('adforest_license_notice_styles')) {
    function adforest_license_notice_styles()
    {
        if (!adforest_is_license_active() && current_user_can('manage_options')) {
            ?>
            <style>
                .adforest-license-notice {
                    border-left-color: #dc3232 !important;
                    background: #fff2f2 !important;
                }

                .adforest-license-notice .notice-dismiss {
                    display: none !important;
                }

                .adforest-license-notice h3 {
                    color: #dc3232;
                    font-weight: 600;
                }
            </style>
            <?php
        }
    }

    add_action('admin_head', 'adforest_license_notice_styles');
}

/**
 * Save the original post date for ad_post on first save
 */
if (!function_exists('adforest_save_original_post_date')) {
    function adforest_save_original_post_date($post_id, $post, $update)
    {
        if ($post->post_type !== 'ad_post' || $update) {
            return;
        }

        update_post_meta($post_id, '_adforest_original_post_date', $post->post_date);
    }
}

add_action('save_post', 'adforest_save_original_post_date', 10, 3);

/**
 * Load CF7 assets only if CF7 is used—via shortcode, block, or inside Elementor data.
 * Prefix: adforest
 */
if (!function_exists('adforest_cf7_conditional_assets')) {
    function adforest_cf7_conditional_assets()
    {

        global $post;
        $has_cf7 = false;

        $content = isset($post->post_content) ? $post->post_content : '';
        if (strpos($content, '[contact-form-7') !== false) {
            $has_cf7 = true;
        }

        if (!$has_cf7 && function_exists('has_block') && has_block('contact-form-7/contact-form-selector', $post)) {
            $has_cf7 = true;
        }

        if (!$has_cf7) {
            $elem_raw = get_post_meta($post->ID, '_elementor_data', true);
            if (!empty($elem_raw) && strpos($elem_raw, 'contact-form-7') !== false) {
                $has_cf7 = true;
            }
        }

        if (!$has_cf7) {
            wp_dequeue_style('contact-form-7');
            wp_dequeue_script('contact-form-7');
        }
    }

//    add_action('wp_enqueue_scripts', 'adforest_cf7_conditional_assets', 20);
}

add_action('template_redirect', 'adforest_force_coming_soon_page');
if (!function_exists('adforest_force_coming_soon_page')) {
    function adforest_force_coming_soon_page()
    {
        global $adforest_theme;
        if (current_user_can('administrator')) {
            return;
        }

        if (isset($adforest_theme['sb_comming_soon_mode']) && $adforest_theme['sb_comming_soon_mode'] == true) {
            status_header(503);
            nocache_headers();

            include get_template_directory() . '/template-parts/layouts/coming-soon.php';
            exit;
        }
    }
}

add_action('wp_ajax_coming_soon', 'adforest_save_coming_soon_email');
add_action('wp_ajax_nopriv_coming_soon', 'adforest_save_coming_soon_email');

if (!function_exists('adforest_save_coming_soon_email')) {
    function adforest_save_coming_soon_email()
    {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'coming_soon_nonce')) {
            echo esc_html__('Security check failed.', 'adforest');
            wp_die();
        }

        $email = isset($_POST['sb_email']) ? sanitize_email($_POST['sb_email']) : '';

        if (!is_email($email)) {
            echo esc_html__('Invalid email address.', 'adforest');
            wp_die();
        }

        $saved = update_option('coming_soon_email_' . time(), $email);

        echo esc_html__('Thanks! We’ll notify you when we go live.', 'adforest');
        wp_die();
    }
}


function is_ad_price_enabled($post_id)
{
    global $adforest_theme;

    $cats = adforest_get_ad_cats($post_id);
    $term_id = '';

    if (!empty($cats) && is_array($cats)) {
        $last_term = end($cats);
        $term_id = isset($last_term['id']) ? $last_term['id'] : '';
    }

    if ($term_id) {
        $templateID = adforest_dynamic_templateID($term_id);
        $templateID = apply_filters('adforest_get_lang_tamonomy', $templateID, 'sb_dynamic_form_templates');

        $templateMeta = get_term_meta($templateID, '_sb_dynamic_form_fields', true);

        if ($templateMeta) {
            $baseDecoded = base64_decode($templateMeta, true);
            // Validate base64 decode was successful
            if ($baseDecoded === false) {
                return false;
            }
            $templateData = json_decode($baseDecoded, true);
            // Validate JSON decode was successful
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            if (isset($templateData['_sb_default_cat_price_show']) && $templateData['_sb_default_cat_price_show'] == '1') {
                return true;
            } else {
                return false;
            }
        }
    }

    if (isset($adforest_theme['sb_default_adpost_template_price']) && $adforest_theme['sb_default_adpost_template_price']) {
        return true;
    }

    return false;
}

/**
 * Allow author pagination (`/page/2/`) to resolve when showing ad listings.
 * Without this, WordPress sees no standard `post` entries for paged author URLs
 * and turns the request into a 404 before our template can run.
 */
function adforest_author_ads_pre_get_posts($query)
{
    if (is_admin() || !$query->is_main_query() || !$query->is_author()) {
        return;
    }

    $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';

    // `type=1` loads the ratings layout which doesn't require the ad listing query.
    if ($type === '1') {
        return;
    }

    $query->set('post_type', array('ad_post'));
    $query->set('post_status', array('publish'));

    $posts_per_page = get_option('posts_per_page');
    if (!empty($posts_per_page)) {
        $query->set('posts_per_page', absint($posts_per_page));
    }
}
add_action('pre_get_posts', 'adforest_author_ads_pre_get_posts', 15);

add_action('pre_get_posts', function ($query) {
    if (
        !is_admin() &&
        $query->is_main_query() &&
        isset($query->query_vars['post_type']) &&
        $query->query_vars['post_type'] === 'ad_post' &&
        isset($query->query_vars['p'])
    ) {
        $current_user = wp_get_current_user();
        if ($current_user->ID) {
            $query->set('post_status', ['publish', 'pending']);
        }
    }
});

add_filter('the_posts', function ($posts, $query) {
    if (
        !is_admin() &&
        $query->is_main_query() &&
        is_singular('ad_post') &&
        !empty($posts)
    ) {
        global $current_user;
        wp_get_current_user();

        $post = $posts[0];

        if ($post->post_status === 'pending') {
            if (
                (int)$post->post_author !== (int)$current_user->ID &&
                !current_user_can('edit_others_posts')
            ) {
                return [];
            }
        }
    }

    return $posts;
}, 10, 2);

// AJAX handler to get invoice HTML
add_action('wp_ajax_get_invoice_html', 'adforest_get_invoice_html');

function adforest_get_invoice_html() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_invoice_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error(array('message' => 'Order not found'));
    }
    
    if ($order->get_customer_id() != get_current_user_id()) {
        wp_send_json_error(array('message' => 'Unauthorized access'));
    }
    
    ob_start();
    ?>
    <button class="btn-close">
    </button>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><?php echo esc_html__('INVOICE', 'adforest'); ?></h1>
            <p style="margin: 10px 0; color: #666; font-size: 18px;">
                <?php echo esc_html__('Order', 'adforest'); ?> #<?php echo esc_html($order->get_id()); ?>
            </p>
        </div>
        
        <div class="invoice-details">
            <div>
                <h3><?php echo esc_html__('Bill To:', 'adforest'); ?></h3>
                <p>
                    <strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></strong><br>
                    <?php echo esc_html($order->get_billing_address_1()); ?><br>
                    <?php if ($order->get_billing_address_2()): ?>
                        <?php echo esc_html($order->get_billing_address_2()); ?><br>
                    <?php endif; ?>
                    <?php echo esc_html($order->get_billing_city()); ?>, <?php echo esc_html($order->get_billing_state()); ?> <?php echo esc_html($order->get_billing_postcode()); ?><br>
                    <?php echo esc_html(WC()->countries->countries[$order->get_billing_country()]); ?><br>
                    <?php echo esc_html($order->get_billing_email()); ?><br>
                    <?php echo esc_html($order->get_billing_phone()); ?>
                </p>
            </div>
            
            <div>
                <h3><?php echo esc_html__('Invoice Details:', 'adforest'); ?></h3>
                <p>
                    <strong><?php echo esc_html__('Date:', 'adforest'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order->get_date_created()))); ?><br>
                    <strong><?php echo esc_html__('Status:', 'adforest'); ?></strong> 
                    <span style="display: inline-block;">
                        <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                    </span><br>
                    <strong><?php echo esc_html__('Payment Method:', 'adforest'); ?></strong> <?php echo esc_html($order->get_payment_method_title()); ?>
                </p>
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Product', 'adforest'); ?></th>
                    <th style="text-align: center;"><?php echo esc_html__('Quantity', 'adforest'); ?></th>
                    <th style="text-align: right;"><?php echo esc_html__('Price', 'adforest'); ?></th>
                    <th style="text-align: right;"><?php echo esc_html__('Total', 'adforest'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item): ?>
                <tr>
                    <td><?php echo esc_html($item->get_name()); ?></td>
                    <td style="text-align: center;"><?php echo esc_html($item->get_quantity()); ?></td>
                    <td style="text-align: right;"><?php echo wp_kses_post(wc_price($item->get_total() / $item->get_quantity())); ?></td>
                    <td style="text-align: right;"><?php echo wp_kses_post(wc_price($item->get_total())); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;"><?php echo esc_html__('Subtotal:', 'adforest'); ?></td>
                    <td style="text-align: right; font-weight: bold;"><?php echo wp_kses_post(wc_price($order->get_subtotal())); ?></td>
                </tr>
                <?php if ($order->get_total_tax() > 0): ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><?php echo esc_html__('Tax:', 'adforest'); ?></td>
                    <td style="text-align: right;"><?php echo wp_kses_post(wc_price($order->get_total_tax())); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($order->get_shipping_total() > 0): ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><?php echo esc_html__('Shipping:', 'adforest'); ?></td>
                    <td style="text-align: right;"><?php echo wp_kses_post(wc_price($order->get_shipping_total())); ?></td>
                </tr>
                <?php endif; ?>
                <tr style="border-top: 2px solid #333;">
                    <td colspan="3" style="text-align: right; font-weight: bold; font-size: 18px; padding-top: 15px;"><?php echo esc_html__('Total:', 'adforest'); ?></td>
                    <td style="text-align: right; font-weight: bold; font-size: 18px; padding-top: 15px;"><?php echo wp_kses_post(wc_price($order->get_total())); ?></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="print-button no-print">
            <button class="btn-print">
                <i class="fa fa-print"></i> <?php echo esc_html__('Download Invoice', 'adforest'); ?>
            </button>
            <button class="btn-close"></button>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success(array('html' => $html));
}