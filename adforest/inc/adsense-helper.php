<?php
/**
 * AdSense Helper - Centralized ad rendering for AdForest theme.
 *
 * Provides safe rendering for Image, Custom HTML, and Google AdSense ad types.
 * All functions are prefixed with `adforest_` per ScriptBundle naming conventions.
 *
 * @package    adforest
 * @subpackage adforest/inc
 * @since      4.7.0
 *
 * == Helper API ==
 *
 * adforest_render_ad( string $ad_type, string $ad_content [, string $wrapper_class] )
 *     Primary rendering function. Outputs ad HTML based on type.
 *
 * adforest_render_theme_ad( string $option_key [, string $wrapper_class] )
 *     Reads ad type + content from Redux theme options, then calls adforest_render_ad().
 *     Use this in templates to replace `echo wp_kses_post( $adforest_theme['key'] )`.
 *
 * adforest_get_ad_type( string $option_key ) : string
 *     Returns the ad type for a Redux option key. Result is cached per request.
 *
 * adforest_is_valid_adsense( string $content ) : bool
 *     Validates content contains legitimate Google AdSense markers.
 *
 * == Filters ==
 *
 * 'adforest_ad_types' (array)
 *     Filter the allowed ad type slugs. Default: ['image','custom_html','adsense']
 *
 * 'adforest_render_ad_output' (string $html, string $ad_type, string $ad_content)
 *     Filter rendered ad HTML before output. Return empty string to suppress.
 *
 * 'adforest_adsense_is_valid' (bool $valid, string $content)
 *     Override AdSense validation result.
 *
 * == Extending ==
 *
 * To add a custom ad type (e.g. 'amazon_native'):
 *   1. Filter 'adforest_ad_types' to include your slug
 *   2. Filter 'adforest_render_ad_output' to handle rendering for that slug
 *   3. Add the selector to Redux via the '{field}_type' button_set pattern
 *
 * == Theme Update Safety ==
 *
 * This file is an addition (not a core theme file modification).
 * During theme updates this file will NOT be overwritten.
 * Template-level patches (style-1.php, style-2.php, etc.) may need re-applying.
 * Consider child-theme overrides for long-term stability.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the whitelist of recognised ad type slugs.
 *
 * @return array
 */
if ( ! function_exists( 'adforest_get_allowed_ad_types' ) ) {
    function adforest_get_allowed_ad_types() {
        static $types = null;
        if ( null === $types ) {
            $types = apply_filters( 'adforest_ad_types', array( 'image', 'custom_html', 'adsense' ) );
        }
        return $types;
    }
}

/**
 * Render an advertisement based on its type.
 *
 * @param string $ad_type       The ad type slug.
 * @param string $ad_content    Raw ad content (HTML / AdSense code).
 * @param string $wrapper_class Optional CSS class appended to the type-wrapper div.
 */
if ( ! function_exists( 'adforest_render_ad' ) ) {
    function adforest_render_ad( $ad_type, $ad_content, $wrapper_class = '' ) {
        $ad_content = trim( (string) $ad_content );
        if ( '' === $ad_content ) {
            return;
        }

        $allowed = adforest_get_allowed_ad_types();
        $ad_type = in_array( $ad_type, $allowed, true ) ? $ad_type : 'image';

        // Build output via buffer so the filter can inspect/modify it.
        ob_start();

        switch ( $ad_type ) {
            case 'adsense':
                $is_valid = adforest_is_valid_adsense( $ad_content );
                if ( $is_valid ) {
                    echo $ad_content;
                }
                break;

            case 'custom_html':
                if ( defined( 'ADFOREST_ALLOWED_FORM_HTML' ) ) {
                    echo wp_kses( $ad_content, ADFOREST_ALLOWED_FORM_HTML );
                } else {
                    echo wp_kses_post( $ad_content );
                }
                break;

            case 'image':
            default:
                echo wp_kses_post( $ad_content );
                break;
        }

        $html = ob_get_clean();

        /**
         * Filter rendered ad HTML before output.
         *
         * @param string $html       The rendered HTML.
         * @param string $ad_type    The ad type slug.
         * @param string $ad_content The raw stored content.
         */
        $html = apply_filters( 'adforest_render_ad_output', $html, $ad_type, $ad_content );

        if ( '' !== $html ) {
            echo $html;
        }
    }
}

/**
 * Validate that content contains legitimate Google AdSense markers.
 *
 * Checks for:
 *  - The `adsbygoogle` class name (required by all AdSense units)
 *  - The `data-ad-client` attribute (pub-XXXXXXXX identifier)
 *  - The official Google syndication domain in the script src
 *
 * @param string $content The ad content to validate.
 * @return bool
 */
if ( ! function_exists( 'adforest_is_valid_adsense' ) ) {
    function adforest_is_valid_adsense( $content ) {
        $valid = (
            false !== strpos( $content, 'adsbygoogle' ) &&
            false !== strpos( $content, 'data-ad-client' ) &&
            false !== strpos( $content, 'pagead2.googlesyndication.com' )
        );

        /**
         * Override the AdSense validation result.
         *
         * @param bool   $valid   Whether the content passed validation.
         * @param string $content The raw ad content being checked.
         */
        return (bool) apply_filters( 'adforest_adsense_is_valid', $valid, $content );
    }
}

/**
 * Get the ad type for a given theme option key (cached per request).
 *
 * @param string $option_key The base option key (e.g. 'style_ad_720_1').
 * @return string Ad type slug. Defaults to 'image'.
 */
if ( ! function_exists( 'adforest_get_ad_type' ) ) {
    function adforest_get_ad_type( $option_key ) {
        static $cache = array();

        if ( isset( $cache[ $option_key ] ) ) {
            return $cache[ $option_key ];
        }

        global $adforest_theme;
        $type_key = $option_key . '_type';
        $ad_type  = isset( $adforest_theme[ $type_key ] ) ? $adforest_theme[ $type_key ] : 'image';
        $allowed  = adforest_get_allowed_ad_types();

        $cache[ $option_key ] = in_array( $ad_type, $allowed, true ) ? $ad_type : 'image';

        return $cache[ $option_key ];
    }
}

/**
 * Render a theme-option advertisement by its Redux option key.
 *
 * Reads both the type ({key}_type) and content ({key}) from $adforest_theme,
 * then delegates to adforest_render_ad(). Safe to call even when content is empty.
 *
 * @param string $option_key    The base option key (e.g. 'style_ad_720_1').
 * @param string $wrapper_class Optional CSS class.
 */
/**
 * Check whether a Redux ad option has real visible content.
 *
 * Returns true only when the stored value contains either plain text
 * (after stripping all tags) OR a content-bearing void element such as
 * <img>, <iframe>, <script>, <ins> (AdSense), <video>, etc.
 *
 * Use this in templates to decide whether to emit an ad wrapper <div>,
 * avoiding empty wrapper elements in the rendered HTML.
 *
 * @param string $option_key The Redux option key.
 * @return bool
 */
if ( ! function_exists( 'adforest_has_visible_ad_content' ) ) {
    function adforest_has_visible_ad_content( $option_key ) {
        global $adforest_theme;

        $ad_content = isset( $adforest_theme[ $option_key ] )
            ? trim( (string) $adforest_theme[ $option_key ] )
            : '';

        if ( '' === $ad_content ) {
            return false;
        }

        $clean     = trim( wp_strip_all_tags( $ad_content ) );
        $has_tags  = (bool) preg_match( '/<(img|iframe|script|ins|video|embed|object|audio|canvas|svg|picture|source)\b/i', $ad_content );

        return ( '' !== $clean || $has_tags );
    }
}

if ( ! function_exists( 'adforest_render_theme_ad' ) ) {
    function adforest_render_theme_ad( $option_key, $wrapper_class = '' ) {
        global $adforest_theme;

        // --- CONTEXT GUARD ---
        // Prevent cross-context leakage: search_ad_* keys must never render on a single ad page,
        // and style_ad_* keys must never render outside a single ad page. Templates already route
        // these correctly, but this guard makes the invariant enforceable even if a future hook,
        // include, or third-party plugin calls the function from an unexpected context.
        $is_single_ad = function_exists( 'is_singular' ) && is_singular( 'ad_post' );
        if ( $is_single_ad && strpos( $option_key, 'search_' ) === 0 ) {
            return;
        }
        if ( ! $is_single_ad && strpos( $option_key, 'style_ad_' ) === 0 ) {
            return;
        }

        // Read the stored value for the SPECIFIC key. No fallback to other keys.
        $ad_content = isset( $adforest_theme[ $option_key ] )
            ? trim( (string) $adforest_theme[ $option_key ] )
            : '';

        // Strict "visibly empty" check: strip tags and whitespace. If result is empty
        // AND the original contains no content-bearing void elements (img, iframe,
        // adsense ins/script, etc.), treat as empty and render nothing. This catches
        // whitespace-only values and empty HTML like <div></div> while preserving
        // legitimate image banners and AdSense code that have no plain-text content.
        $ad_content_clean   = trim( wp_strip_all_tags( $ad_content ) );
        $has_visible_tags   = (bool) preg_match( '/<(img|iframe|script|ins|video|embed|object|audio|canvas|svg|picture|source)\b/i', $ad_content );

        if ( '' === $ad_content_clean && ! $has_visible_tags ) {
            return;
        }

        // Optional debug marker — enable via wp-config.php: define('ADF_DEBUG_ADS', true);
        if ( defined( 'ADF_DEBUG_ADS' ) && ADF_DEBUG_ADS ) {
            echo '<div style="background:yellow;color:black;padding:2px 6px;font:11px monospace;">AD KEY: ' . esc_html( $option_key ) . '</div>';
        }

        $ad_type = adforest_get_ad_type( $option_key );
        adforest_render_ad( $ad_type, $ad_content, $wrapper_class );
    }
}
