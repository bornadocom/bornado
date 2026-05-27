<?php
/**
 * AI-Based Title & Description Suggestions for Ad Posting
 *
 * This file handles AJAX requests to generate AI-powered suggestions
 * for ad titles and descriptions based on selected category and custom fields.
 *
 * @package AdForest
 * @since 1.0.0
 *
 * HOW TO CONNECT TO A REAL AI API:
 * --------------------------------
 * Replace the mock function `adforest_mock_ai_response()` with actual API calls.
 *
 * For OpenAI:
 * - Use wp_remote_post() to call 'https://api.openai.com/v1/chat/completions'
 * - Pass your API key in the Authorization header
 *
 * For Claude/Anthropic:
 * - Use wp_remote_post() to call 'https://api.anthropic.com/v1/messages'
 * - Pass your API key in the x-api-key header
 *
 * See adforest_call_real_ai_api() function below for implementation examples.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * ============================================================================
 * AI READINESS - SINGLE SOURCE OF TRUTH
 * ============================================================================
 */

/**
 * Check if AI suggestions feature is fully configured and ready
 *
 * This is the SINGLE SOURCE OF TRUTH for AI readiness.
 * All other code (PHP and JS) must defer to this function.
 *
 * AI is considered ready only if:
 * 1. Theme option sb_enable_ai_ad_suggestions is enabled (true)
 * 2. Theme option sb_ai_provider is set to a valid provider (mock, openai, or anthropic)
 *
 * When AI is NOT ready:
 * - No AI-related UI elements should be rendered
 * - No AI scripts should be enqueued
 * - The ad posting flow must behave exactly as before AI existed
 *
 * @return bool True if AI is fully configured and ready, false otherwise
 */
function adforest_is_ai_ready() {
    global $adforest_theme;

    // Condition 1: AI suggestions must be explicitly enabled
    $ai_enabled = isset( $adforest_theme['sb_enable_ai_ad_suggestions'] )
        ? (bool) $adforest_theme['sb_enable_ai_ad_suggestions']
        : false;

    if ( ! $ai_enabled ) {
        return false;
    }

    // Condition 2: A valid AI provider must be selected
    $ai_provider = isset( $adforest_theme['sb_ai_provider'] )
        ? $adforest_theme['sb_ai_provider']
        : '';

    $valid_providers = array( 'mock', 'openai', 'anthropic' );

    if ( empty( $ai_provider ) || ! in_array( $ai_provider, $valid_providers, true ) ) {
        return false;
    }

    // All conditions met - AI is ready
    return true;
}

/**
 * Get intent-related field values for AI context
 *
 * Retrieves the values of intent fields (Condition, Warranty, Ad Type)
 * that should be passed to the AI for better context.
 *
 * @param array $post_data POST data from the form submission
 * @return array Array of intent field values
 */
function adforest_get_ai_intent_context( $post_data ) {
    $intent = array();

    // Ad Type (Buy/Sell/Exchange/etc.)
    if ( ! empty( $post_data['ad_type'] ) ) {
        $ad_type_id = absint( $post_data['ad_type'] );
        $ad_type_term = get_term( $ad_type_id, 'ad_type' );
        if ( $ad_type_term && ! is_wp_error( $ad_type_term ) ) {
            $intent['ad_type'] = $ad_type_term->name;
        }
    }

    // Condition (New/Used/etc.)
    if ( ! empty( $post_data['ad_condition'] ) ) {
        $condition_id = absint( $post_data['ad_condition'] );
        $condition_term = get_term( $condition_id, 'ad_condition' );
        if ( $condition_term && ! is_wp_error( $condition_term ) ) {
            $intent['condition'] = $condition_term->name;
        }
    }

    // Warranty
    if ( ! empty( $post_data['ad_warranty'] ) ) {
        $warranty_id = absint( $post_data['ad_warranty'] );
        $warranty_term = get_term( $warranty_id, 'ad_warranty' );
        if ( $warranty_term && ! is_wp_error( $warranty_term ) ) {
            $intent['warranty'] = $warranty_term->name;
        }
    }

    return $intent;
}

/**
 * Check if the Ad Intent tab should be rendered
 *
 * The Ad Intent tab should only be shown if at least one of the
 * intent fields (Condition, Warranty, Ad Type) is visible and enabled.
 *
 * @return bool True if Ad Intent tab should be rendered
 */
function adforest_should_show_intent_tab() {
    global $adforest_theme;

    // First, AI must be ready
    if ( ! adforest_is_ai_ready() ) {
        return false;
    }

    // Check if at least one intent field is enabled
    $show_ad_type = isset( $adforest_theme['sb_ad_type'] ) ? (bool) $adforest_theme['sb_ad_type'] : true;
    $show_condition = isset( $adforest_theme['sb_ad_condition'] ) ? (bool) $adforest_theme['sb_ad_condition'] : true;
    $show_warranty = isset( $adforest_theme['sb_ad_warranty'] ) ? (bool) $adforest_theme['sb_ad_warranty'] : false;

    // Show intent tab if ANY of the intent fields are visible
    return ( $show_ad_type || $show_condition || $show_warranty );
}

/**
 * Check if the AI suggestions button should be rendered
 *
 * The AI button should be shown in the General Information / Ad Content tab
 * when AI is ready. This is independent of:
 * - Intent tab visibility
 * - Field routing flags
 *
 * @return bool True if AI button should be rendered
 */
function adforest_should_show_ai_button() {
    // AI button visibility depends ONLY on AI readiness
    // It does NOT depend on intent tab or field routing
    return adforest_is_ai_ready();
}

/**
 * ============================================================================
 * END AI READINESS FUNCTIONS
 * ============================================================================
 */

/**
 * Register AJAX handlers for AI suggestions
 */
add_action( 'wp_ajax_adforest_get_ai_suggestions', 'adforest_get_ai_suggestions_handler' );
add_action( 'wp_ajax_nopriv_adforest_get_ai_suggestions', 'adforest_get_ai_suggestions_handler' );

/**
 * ============================================================================
 * AI USAGE TRACKING & LIMITING FUNCTIONS
 * ============================================================================
 */

/**
 * Get the current date key for usage tracking
 *
 * @return string Date key in YYYYMMDD format
 */
function adforest_ai_get_date_key() {
    return gmdate( 'Ymd' );
}

/**
 * Get AI usage count for a user
 *
 * @param int $user_id User ID (0 for guest)
 * @return int Current usage count for today
 */
function adforest_ai_get_user_usage( $user_id = 0 ) {
    $date_key = adforest_ai_get_date_key();
    $meta_key = 'sb_ai_usage_' . $date_key;

    if ( $user_id > 0 ) {
        // Logged-in user: use user meta
        $count = get_user_meta( $user_id, $meta_key, true );
        return $count ? absint( $count ) : 0;
    } else {
        // Guest user: use transient based on IP
        $ip_hash = md5( adforest_ai_get_client_ip() );
        $transient_key = 'sb_ai_guest_' . $ip_hash . '_' . $date_key;
        $count = get_transient( $transient_key );
        return $count ? absint( $count ) : 0;
    }
}

/**
 * Increment AI usage count for a user
 *
 * @param int $user_id User ID (0 for guest)
 * @return int New usage count
 */
function adforest_ai_increment_usage( $user_id = 0 ) {
    $date_key = adforest_ai_get_date_key();
    $meta_key = 'sb_ai_usage_' . $date_key;

    if ( $user_id > 0 ) {
        // Logged-in user: use user meta
        $current = adforest_ai_get_user_usage( $user_id );
        $new_count = $current + 1;
        update_user_meta( $user_id, $meta_key, $new_count );

        // Clean up old usage meta (older than 7 days) to prevent bloat
        adforest_ai_cleanup_old_usage_meta( $user_id );

        return $new_count;
    } else {
        // Guest user: use transient based on IP (expires at end of day)
        $ip_hash = md5( adforest_ai_get_client_ip() );
        $transient_key = 'sb_ai_guest_' . $ip_hash . '_' . $date_key;
        $current = adforest_ai_get_user_usage( 0 );
        $new_count = $current + 1;

        // Calculate seconds until midnight
        $seconds_until_midnight = strtotime( 'tomorrow' ) - time();
        set_transient( $transient_key, $new_count, $seconds_until_midnight );

        return $new_count;
    }
}

/**
 * Check if user has exceeded daily AI usage limit
 *
 * @param int $user_id User ID (0 for guest)
 * @return bool|array True if within limit, array with error details if exceeded
 */
function adforest_ai_check_usage_limit( $user_id = 0 ) {
    global $adforest_theme;

    // Check if usage limits are enabled
    $limits_enabled = isset( $adforest_theme['sb_ai_usage_limit_enabled'] ) ? (bool) $adforest_theme['sb_ai_usage_limit_enabled'] : false;

    if ( ! $limits_enabled ) {
        return true; // No limits, allow
    }

    // Get the daily limit
    $daily_limit = isset( $adforest_theme['sb_ai_max_requests_per_day'] ) ? absint( $adforest_theme['sb_ai_max_requests_per_day'] ) : 5;

    // If limit is 0, treat as unlimited
    if ( $daily_limit === 0 ) {
        return true;
    }

    // Get current usage
    $current_usage = adforest_ai_get_user_usage( $user_id );

    if ( $current_usage >= $daily_limit ) {
        return array(
            'exceeded' => true,
            'current'  => $current_usage,
            'limit'    => $daily_limit,
            'message'  => sprintf(
                /* translators: %d: daily limit number */
                __( 'Daily AI suggestion limit reached (%d per day). Please try again tomorrow.', 'adforest' ),
                $daily_limit
            ),
        );
    }

    return true;
}

/**
 * Get client IP address
 *
 * @return string Client IP address
 */
function adforest_ai_get_client_ip() {
    $ip = '';

    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        // Can contain multiple IPs, take the first one
        $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
        $ip = trim( $ips[0] );
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }

    // Validate IP format
    if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        return $ip;
    }

    return '127.0.0.1'; // Fallback
}

/**
 * Clean up old AI usage meta for a user (older than 7 days)
 *
 * @param int $user_id User ID
 */
function adforest_ai_cleanup_old_usage_meta( $user_id ) {
    global $wpdb;

    // Only run cleanup occasionally (1% chance per request)
    if ( wp_rand( 1, 100 ) > 1 ) {
        return;
    }

    // Calculate cutoff date (7 days ago)
    $cutoff_date = gmdate( 'Ymd', strtotime( '-7 days' ) );

    // Delete old usage meta keys
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta}
             WHERE user_id = %d
             AND meta_key LIKE %s
             AND meta_key < %s",
            $user_id,
            'sb_ai_usage_%',
            'sb_ai_usage_' . $cutoff_date
        )
    );
}

/**
 * ============================================================================
 * END USAGE TRACKING FUNCTIONS
 * ============================================================================
 */

/**
 * Main AJAX handler for AI suggestions
 *
 * Receives category, subcategory, and custom fields data via AJAX
 * and returns AI-generated title and description suggestions.
 *
 * @return void Outputs JSON response
 */
function adforest_get_ai_suggestions_handler() {
    global $adforest_theme;

    // CRITICAL: Validate AI readiness using single source of truth
    // This prevents any AI processing when not fully configured
    if ( ! adforest_is_ai_ready() ) {
        wp_send_json_error( array(
            'message' => __( 'AI suggestions are not configured. Please contact the site administrator.', 'adforest' )
        ) );
    }

    // Verify nonce for security
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'adforest_ai_suggestions_nonce' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Security verification failed.', 'adforest' )
        ) );
    }

    // Check if user is logged in (ad posting requires login)
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array(
            'message' => __( 'Please log in to use this feature.', 'adforest' )
        ) );
    }

    // Check usage limits before proceeding
    $user_id = get_current_user_id();
    $usage_check = adforest_ai_check_usage_limit( $user_id );

    if ( is_array( $usage_check ) && isset( $usage_check['exceeded'] ) && $usage_check['exceeded'] ) {
        wp_send_json_error( array(
            'message' => $usage_check['message']
        ) );
    }

    // Sanitize and validate input data
    $category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
    $subcategory_id = isset( $_POST['subcategory_id'] ) ? absint( $_POST['subcategory_id'] ) : 0;
    $custom_fields = isset( $_POST['custom_fields'] ) ? adforest_sanitize_custom_fields( $_POST['custom_fields'] ) : array();

    // Validate category exists
    if ( $category_id <= 0 ) {
        wp_send_json_error( array(
            'message' => __( 'Please select a category first.', 'adforest' )
        ) );
    }

    // Get category and subcategory names
    $category_name = '';
    $subcategory_name = '';
    $category_hierarchy = array();

    // Get main category
    $category_term = get_term( $category_id, 'ad_cats' );
    if ( $category_term && ! is_wp_error( $category_term ) ) {
        $category_name = $category_term->name;
        $category_hierarchy[] = $category_name;
    }

    // Get subcategory and build full hierarchy
    if ( $subcategory_id > 0 ) {
        $subcategory_term = get_term( $subcategory_id, 'ad_cats' );
        if ( $subcategory_term && ! is_wp_error( $subcategory_term ) ) {
            $subcategory_name = $subcategory_term->name;

            // Get full ancestry for better context
            // Example: Vehicles > Cars > Kia > Sorento
            $ancestors = get_ancestors( $subcategory_id, 'ad_cats', 'taxonomy' );
            $ancestors = array_reverse( $ancestors ); // Root to leaf order

            $category_hierarchy = array();
            foreach ( $ancestors as $ancestor_id ) {
                $ancestor_term = get_term( $ancestor_id, 'ad_cats' );
                if ( $ancestor_term && ! is_wp_error( $ancestor_term ) ) {
                    $category_hierarchy[] = $ancestor_term->name;
                }
            }
            $category_hierarchy[] = $subcategory_name; // Add the deepest category
        }
    }

    // Get intent context (Ad Type, Condition, Warranty)
    // These fields are critical for generating appropriate language
    $intent_context = adforest_get_ai_intent_context( $_POST );

    // Build context data for AI
    $context_data = array(
        'category'           => $category_name,
        'subcategory'        => $subcategory_name,
        'category_hierarchy' => $category_hierarchy, // Full path: ['Vehicles', 'Cars', 'Kia', 'Sorento']
        'custom_fields'      => $custom_fields,
        'intent'             => $intent_context, // Ad Type, Condition, Warranty
        'locale'             => get_locale(), // For multilingual support
    );

    // Get AI provider setting (default: mock)
    $ai_provider = isset( $adforest_theme['sb_ai_provider'] ) ? $adforest_theme['sb_ai_provider'] : 'mock';

    // Get the appropriate API key based on provider
    $ai_api_key = '';
    if ( $ai_provider === 'openai' ) {
        $ai_api_key = isset( $adforest_theme['sb_openai_api_key'] ) ? trim( $adforest_theme['sb_openai_api_key'] ) : '';
    } elseif ( $ai_provider === 'anthropic' ) {
        $ai_api_key = isset( $adforest_theme['sb_claude_api_key'] ) ? trim( $adforest_theme['sb_claude_api_key'] ) : '';
    }

    // Validate API key for real providers
    if ( $ai_provider !== 'mock' && empty( $ai_api_key ) ) {
        wp_send_json_error( array(
            'message' => sprintf(
                /* translators: %s: AI provider name */
                __( 'API key is not configured for %s. Please add your API key in Theme Options → Ads Settings → Ad Posting.', 'adforest' ),
                $ai_provider === 'openai' ? 'OpenAI' : 'Claude/Anthropic'
            )
        ) );
    }

    // Route to appropriate handler
    if ( $ai_provider === 'openai' && ! empty( $ai_api_key ) ) {
        // Use OpenAI API
        $ai_response = adforest_call_openai_api( $context_data, $ai_api_key );
    } elseif ( $ai_provider === 'anthropic' && ! empty( $ai_api_key ) ) {
        // Use Claude/Anthropic API
        $ai_response = adforest_call_claude_api( $context_data, $ai_api_key );
    } else {
        // Use mock response for development/testing (default)
        $ai_response = adforest_mock_ai_response( $context_data );
    }

    if ( is_wp_error( $ai_response ) ) {
        wp_send_json_error( array(
            'message' => $ai_response->get_error_message()
        ) );
    }

    // Increment usage count only on successful response
    // Note: $user_id was set earlier during usage limit check
    adforest_ai_increment_usage( $user_id );

    // Return success response with AI suggestions
    wp_send_json_success( array(
        'title'       => sanitize_text_field( $ai_response['title'] ),
        'description' => wp_kses_post( $ai_response['description'] ),
        'message'     => __( 'AI suggestions generated successfully.', 'adforest' )
    ) );
}

/**
 * Sanitize custom fields array
 *
 * @param array $fields Raw custom fields data
 * @return array Sanitized custom fields
 */
function adforest_sanitize_custom_fields( $fields ) {
    if ( ! is_array( $fields ) ) {
        return array();
    }

    $sanitized = array();
    foreach ( $fields as $key => $value ) {
        $clean_key = sanitize_key( $key );
        if ( is_array( $value ) ) {
            $sanitized[ $clean_key ] = array_map( 'sanitize_text_field', $value );
        } else {
            $sanitized[ $clean_key ] = sanitize_text_field( $value );
        }
    }

    return $sanitized;
}

/**
 * Mock AI response function for development/testing
 *
 * This function generates placeholder suggestions based on the category,
 * custom fields, and INTENT fields (Ad Type, Condition, Warranty).
 *
 * IMPORTANT: This function respects user intent:
 * - If Ad Type = "Buy" → generates buyer-intent language
 * - If Ad Type = "Sell" → generates seller-intent language
 * - Never uses "for sale" unless Ad Type explicitly indicates selling
 *
 * @param array $context_data Context data including category, subcategory, intent, and custom fields
 * @return array|WP_Error Array with 'title' and 'description' keys, or WP_Error on failure
 */
function adforest_mock_ai_response( $context_data ) {
    $category = $context_data['category'];
    $subcategory = $context_data['subcategory'];
    $custom_fields = $context_data['custom_fields'];
    $locale = $context_data['locale'];

    // Get intent fields
    $intent = isset( $context_data['intent'] ) ? $context_data['intent'] : array();
    $ad_type = isset( $intent['ad_type'] ) ? strtolower( $intent['ad_type'] ) : '';
    $condition = isset( $intent['condition'] ) ? $intent['condition'] : '';
    $warranty = isset( $intent['warranty'] ) ? $intent['warranty'] : '';

    // Determine intent type for language selection
    $is_buying = in_array( $ad_type, array( 'buy', 'buying', 'wanted', 'looking for' ), true );
    $is_selling = in_array( $ad_type, array( 'sell', 'selling', 'for sale' ), true );
    $is_exchange = in_array( $ad_type, array( 'exchange', 'swap', 'trade' ), true );
    $is_lost_found = in_array( $ad_type, array( 'lost', 'found', 'lost & found' ), true );

    // Get the full category hierarchy for better context
    $category_hierarchy = isset( $context_data['category_hierarchy'] ) ? $context_data['category_hierarchy'] : array();

    // Build a descriptive title based on available data
    $title_parts = array();

    // For vehicles: prefer Year + Brand/Make + Model format
    $has_vehicle_data = false;
    if ( ! empty( $custom_fields['year'] ) ) {
        $title_parts[] = $custom_fields['year'];
        $has_vehicle_data = true;
    }

    // Add brand/make if available
    if ( ! empty( $custom_fields['brand'] ) ) {
        $title_parts[] = $custom_fields['brand'];
        $has_vehicle_data = true;
    } elseif ( ! empty( $custom_fields['make'] ) ) {
        $title_parts[] = $custom_fields['make'];
        $has_vehicle_data = true;
    }

    // Add model if available
    if ( ! empty( $custom_fields['model'] ) ) {
        $title_parts[] = $custom_fields['model'];
        $has_vehicle_data = true;
    }

    // If no custom fields, use the category hierarchy to build title
    // Example: Vehicles > Cars > Kia > Sorento -> use "Kia Sorento"
    if ( empty( $title_parts ) ) {
        $hierarchy_count = count( $category_hierarchy );

        if ( $hierarchy_count >= 4 ) {
            // Deep hierarchy: assume pattern like Vehicles > Cars > Make > Model
            // Use last two items as Brand + Model
            $title_parts[] = $category_hierarchy[ $hierarchy_count - 2 ]; // e.g., "Kia"
            $title_parts[] = $category_hierarchy[ $hierarchy_count - 1 ]; // e.g., "Sorento"
        } elseif ( $hierarchy_count >= 3 ) {
            // Medium hierarchy: use last two items
            $title_parts[] = $category_hierarchy[ $hierarchy_count - 2 ];
            $title_parts[] = $category_hierarchy[ $hierarchy_count - 1 ];
        } elseif ( $hierarchy_count >= 2 ) {
            // Shallow hierarchy: use the deepest
            $title_parts[] = $category_hierarchy[ $hierarchy_count - 1 ];
        } elseif ( ! empty( $subcategory ) ) {
            $title_parts[] = $subcategory;
        } elseif ( ! empty( $category ) ) {
            $title_parts[] = $category;
        }
    }

    // Add condition indicator if relevant
    if ( ! empty( $custom_fields['condition'] ) ) {
        $condition = strtolower( trim( $custom_fields['condition'] ) );
        // Only add condition if it's meaningful
        if ( $condition === 'new' || $condition === 'used' || $condition === 'refurbished' ) {
            // For title, prepend condition for used items
            if ( $condition !== 'new' ) {
                array_unshift( $title_parts, ucfirst( $condition ) );
            }
        }
    }

    // Add intent-appropriate suffix if title is too short
    // IMPORTANT: Never guess intent - only add based on explicit Ad Type selection
    if ( count( $title_parts ) < 2 ) {
        if ( $is_buying ) {
            $title_parts[] = __( 'Wanted', 'adforest' );
        } elseif ( $is_selling ) {
            $title_parts[] = __( 'For Sale', 'adforest' );
        } elseif ( $is_exchange ) {
            $title_parts[] = __( 'For Trade', 'adforest' );
        } elseif ( $is_lost_found ) {
            $title_parts[] = __( 'Lost & Found', 'adforest' );
        } else {
            // No explicit intent - use neutral language
            $title_parts[] = __( 'Available', 'adforest' );
        }
    }

    // Generate mock title
    $suggested_title = implode( ' ', array_filter( $title_parts ) );

    // Generate mock description
    $description_lines = array();
    $hierarchy_count = count( $category_hierarchy );

    // Build item name from category hierarchy or title
    $item_name = $suggested_title;
    if ( $hierarchy_count >= 2 ) {
        // Use last two categories as the item identifier
        $item_name = $category_hierarchy[ $hierarchy_count - 2 ] . ' ' . $category_hierarchy[ $hierarchy_count - 1 ];
    }

    // Opening line - INTENT-AWARE language
    // IMPORTANT: Language varies based on Ad Type
    if ( $is_buying ) {
        // BUYER INTENT - looking to purchase
        if ( $hierarchy_count >= 2 ) {
            $top_category = $category_hierarchy[0];
            $description_lines[] = sprintf(
                /* translators: %1$s: item name, %2$s: top category */
                __( 'Looking to buy a %1$s in the %2$s category.', 'adforest' ),
                $item_name,
                $top_category
            );
        } elseif ( ! empty( $subcategory ) ) {
            $description_lines[] = sprintf(
                /* translators: %1$s: subcategory name, %2$s: category name */
                __( 'Seeking a %1$s from the %2$s category.', 'adforest' ),
                $subcategory,
                $category
            );
        } else {
            $description_lines[] = sprintf(
                /* translators: %s: category name */
                __( 'Looking to purchase an item from the %s category.', 'adforest' ),
                $category
            );
        }
    } elseif ( $is_exchange ) {
        // EXCHANGE INTENT - trading
        if ( $hierarchy_count >= 2 ) {
            $top_category = $category_hierarchy[0];
            $description_lines[] = sprintf(
                /* translators: %1$s: item name, %2$s: top category */
                __( 'Looking to trade this %1$s in the %2$s category.', 'adforest' ),
                $item_name,
                $top_category
            );
        } else {
            $description_lines[] = sprintf(
                /* translators: %s: category name */
                __( 'This item is available for exchange in the %s category.', 'adforest' ),
                $category
            );
        }
    } elseif ( $is_lost_found ) {
        // LOST & FOUND INTENT
        $description_lines[] = sprintf(
            /* translators: %s: item name */
            __( 'This is a lost & found listing for %s.', 'adforest' ),
            $item_name
        );
    } else {
        // SELLER INTENT (explicit or default)
        if ( $hierarchy_count >= 2 ) {
            $top_category = $category_hierarchy[0]; // e.g., "Vehicles"
            $description_lines[] = sprintf(
                /* translators: %1$s: item name (e.g., "Kia Sorento"), %2$s: top category (e.g., "Vehicles") */
                __( 'Offering this excellent %1$s for sale in the %2$s category.', 'adforest' ),
                $item_name,
                $top_category
            );
        } elseif ( ! empty( $subcategory ) ) {
            $description_lines[] = sprintf(
                /* translators: %1$s: subcategory name, %2$s: category name */
                __( 'Offering this excellent %1$s from the %2$s category.', 'adforest' ),
                $subcategory,
                $category
            );
        } elseif ( ! empty( $category ) ) {
            $description_lines[] = sprintf(
                /* translators: %s: category name */
                __( 'Offering this excellent item from the %s category.', 'adforest' ),
                $category
            );
        }
    }

    // Add condition if available (from intent or custom fields)
    $display_condition = ! empty( $condition ) ? $condition : ( ! empty( $custom_fields['condition'] ) ? $custom_fields['condition'] : '' );
    if ( ! empty( $display_condition ) ) {
        $display_condition = ucfirst( strtolower( trim( $display_condition ) ) );
        $description_lines[] = sprintf(
            /* translators: %s: condition */
            __( 'Condition: %s', 'adforest' ),
            $display_condition
        );
    }

    // Add warranty if available
    if ( ! empty( $warranty ) ) {
        $description_lines[] = sprintf(
            /* translators: %s: warranty */
            __( 'Warranty: %s', 'adforest' ),
            ucfirst( $warranty )
        );
    }

    // Add year/mileage for vehicles
    if ( ! empty( $custom_fields['year'] ) ) {
        $description_lines[] = sprintf(
            /* translators: %s: year */
            __( 'Year: %s', 'adforest' ),
            $custom_fields['year']
        );
    }

    if ( ! empty( $custom_fields['mileage'] ) ) {
        $description_lines[] = sprintf(
            /* translators: %s: mileage */
            __( 'Mileage: %s', 'adforest' ),
            $custom_fields['mileage']
        );
    }

    // Add price if available (only for selling intent)
    if ( ! empty( $custom_fields['price'] ) && ! $is_buying ) {
        $description_lines[] = sprintf(
            /* translators: %s: price */
            __( 'Price: %s', 'adforest' ),
            $custom_fields['price']
        );
    }

    // Add INTENT-AWARE call to action
    $description_lines[] = '';

    if ( $is_buying ) {
        $description_lines[] = __( 'If you have what I\'m looking for, please contact me with details and your asking price.', 'adforest' );
    } elseif ( $is_exchange ) {
        $description_lines[] = __( 'Open to trades and exchanges. Contact me to discuss what you have to offer.', 'adforest' );
    } elseif ( $is_lost_found ) {
        $description_lines[] = __( 'If you have any information, please contact me immediately. Thank you!', 'adforest' );
    } else {
        // Seller intent (default)
        $description_lines[] = __( 'Contact me for more details or to arrange a viewing. Serious inquiries only.', 'adforest' );
    }

    $suggested_description = implode( "\n", $description_lines );

    return array(
        'title'       => $suggested_title,
        'description' => $suggested_description,
    );
}

/**
 * Build the AI prompt from context data
 *
 * This function constructs a detailed prompt that includes:
 * - Category hierarchy
 * - Intent fields (Ad Type, Condition, Warranty)
 * - Custom fields
 * - Language preferences
 *
 * IMPORTANT: The prompt respects user intent:
 * - If Ad Type = "Buy" → generates buyer-intent language
 * - If Ad Type = "Sell" → generates seller-intent language
 * - Never uses "for sale" unless Ad Type explicitly indicates selling
 *
 * @param array $context_data Context data including category, subcategory, and custom fields
 * @return string The prompt text
 */
function adforest_build_ai_prompt( $context_data ) {
    $category = $context_data['category'];
    $subcategory = $context_data['subcategory'];
    $custom_fields = $context_data['custom_fields'];
    $locale = $context_data['locale'];
    $category_hierarchy = isset( $context_data['category_hierarchy'] ) ? $context_data['category_hierarchy'] : array();

    // Extract intent fields from context
    $ad_type = isset( $context_data['intent']['ad_type'] ) ? $context_data['intent']['ad_type'] : '';
    $condition = isset( $context_data['intent']['condition'] ) ? $context_data['intent']['condition'] : '';
    $warranty = isset( $context_data['intent']['warranty'] ) ? $context_data['intent']['warranty'] : '';

    // Also check custom_fields for intent data (backward compatibility)
    if ( empty( $ad_type ) && ! empty( $custom_fields['ad_type'] ) ) {
        $ad_type = $custom_fields['ad_type'];
        unset( $custom_fields['ad_type'] );
    }
    if ( empty( $condition ) && ! empty( $custom_fields['condition'] ) ) {
        $condition = $custom_fields['condition'];
        unset( $custom_fields['condition'] );
    }
    if ( empty( $warranty ) && ! empty( $custom_fields['warranty'] ) ) {
        $warranty = $custom_fields['warranty'];
        unset( $custom_fields['warranty'] );
    }

    // Determine language instruction based on locale
    $language_instruction = '';
    if ( strpos( $locale, 'fr' ) === 0 ) {
        $language_instruction = 'Respond in French.';
    } elseif ( strpos( $locale, 'ar' ) === 0 ) {
        $language_instruction = 'Respond in Arabic.';
    } elseif ( strpos( $locale, 'es' ) === 0 ) {
        $language_instruction = 'Respond in Spanish.';
    } elseif ( strpos( $locale, 'de' ) === 0 ) {
        $language_instruction = 'Respond in German.';
    }

    // Build category path
    $category_path = ! empty( $category_hierarchy ) ? implode( ' > ', $category_hierarchy ) : $category;

    // Build intent section (Ad Type, Condition, Warranty)
    $intent_text = '';
    $intent_parts = array();

    if ( ! empty( $ad_type ) ) {
        $intent_parts[] = "Ad Type: {$ad_type}";
    }
    if ( ! empty( $condition ) ) {
        $intent_parts[] = "Condition: {$condition}";
    }
    if ( ! empty( $warranty ) ) {
        $intent_parts[] = "Warranty: {$warranty}";
    }

    if ( ! empty( $intent_parts ) ) {
        $intent_text = "\n\nIntent & Status:\n" . implode( "\n", $intent_parts );
    }

    // Build the custom fields text (excluding intent fields already processed)
    $fields_text = '';
    if ( ! empty( $custom_fields ) ) {
        $fields_list = array();
        foreach ( $custom_fields as $key => $value ) {
            if ( ! empty( $value ) ) {
                $fields_list[] = ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . ( is_array( $value ) ? implode( ', ', $value ) : $value );
            }
        }
        if ( ! empty( $fields_list ) ) {
            $fields_text = "\n\nAdditional details:\n" . implode( "\n", $fields_list );
        }
    }

    // Build intent-specific instructions
    $intent_instructions = '';
    $ad_type_lower = strtolower( $ad_type );

    if ( in_array( $ad_type_lower, array( 'buy', 'buying', 'wanted', 'looking for' ), true ) ) {
        $intent_instructions = "
CRITICAL - BUYER INTENT:
- This is a BUYING/WANTED ad - the user is LOOKING TO PURCHASE
- Use buyer-intent language (e.g., \"Looking for\", \"Wanted\", \"Seeking\")
- NEVER use phrases like \"for sale\", \"selling\", or seller language
- Focus on what the buyer is looking for and their requirements";
    } elseif ( in_array( $ad_type_lower, array( 'sell', 'selling', 'for sale' ), true ) ) {
        $intent_instructions = "
SELLER INTENT:
- This is a SELLING ad - the user is OFFERING an item
- Use seller-intent language (e.g., \"For Sale\", \"Offering\", \"Available\")
- Highlight the item's features and benefits
- Include appropriate selling phrases";
    } elseif ( in_array( $ad_type_lower, array( 'exchange', 'swap', 'trade' ), true ) ) {
        $intent_instructions = "
EXCHANGE INTENT:
- This is an EXCHANGE/TRADE ad
- Use exchange language (e.g., \"Looking to trade\", \"Will exchange for\")
- Mention both what they have and what they're looking for";
    } elseif ( in_array( $ad_type_lower, array( 'lost', 'found', 'lost & found' ), true ) ) {
        $intent_instructions = "
LOST & FOUND INTENT:
- This is a LOST or FOUND item ad
- Use appropriate language based on context
- Focus on identification details and contact information";
    }

    $prompt = "Generate a compelling classified ad title and description for the following:

Category path: {$category_path}
" . ( ! empty( $subcategory ) ? "Specific category: {$subcategory}\n" : '' ) . $intent_text . $fields_text . "

Requirements:
- Title should be concise (under 60 characters), catchy, and include key details
- Description should be 3-5 sentences, highlighting key features and benefits
- Use a professional but friendly tone
- Include a call to action at the end
- NEVER guess the user's intent - only use the Ad Type provided
- If no Ad Type is specified, use neutral language
{$intent_instructions}
{$language_instruction}

Respond ONLY with valid JSON in this exact format:
{\"title\": \"your suggested title\", \"description\": \"your suggested description\"}";

    return $prompt;
}

/**
 * Call OpenAI API for AI suggestions
 *
 * @param array  $context_data Context data for AI
 * @param string $api_key      OpenAI API key
 * @return array|WP_Error Array with 'title' and 'description', or WP_Error on failure
 */
function adforest_call_openai_api( $context_data, $api_key ) {
    global $adforest_theme;

    $prompt = adforest_build_ai_prompt( $context_data );

    // Get selected model from theme options (default: gpt-4o-mini)
    $model = isset( $adforest_theme['sb_openai_model'] ) ? $adforest_theme['sb_openai_model'] : 'gpt-4o-mini';

    $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'model'       => $model,
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => 'You are a helpful assistant that generates classified ad titles and descriptions. Always respond with valid JSON only, no additional text.'
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens'  => 500,
            'temperature' => 0.7,
        ) ),
    ) );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', __( 'Failed to connect to OpenAI. Please check your internet connection.', 'adforest' ) );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    // Handle HTTP errors
    if ( $response_code !== 200 ) {
        if ( $response_code === 401 ) {
            return new WP_Error( 'api_error', __( 'Invalid OpenAI API key. Please check your API key in Theme Options.', 'adforest' ) );
        } elseif ( $response_code === 429 ) {
            return new WP_Error( 'api_error', __( 'OpenAI rate limit exceeded. Please try again later.', 'adforest' ) );
        } elseif ( $response_code === 500 || $response_code === 503 ) {
            return new WP_Error( 'api_error', __( 'OpenAI service is temporarily unavailable. Please try again later.', 'adforest' ) );
        } elseif ( isset( $body['error']['message'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] );
        }
        return new WP_Error( 'api_error', __( 'OpenAI API error. Please try again.', 'adforest' ) );
    }

    // Parse successful response
    if ( isset( $body['choices'][0]['message']['content'] ) ) {
        $ai_content = trim( $body['choices'][0]['message']['content'] );

        // Try to parse JSON directly
        $parsed = json_decode( $ai_content, true );

        // If direct parse fails, try to extract JSON from response
        if ( ! $parsed ) {
            preg_match( '/\{[\s\S]*?"title"[\s\S]*?"description"[\s\S]*?\}/', $ai_content, $matches );
            if ( ! empty( $matches[0] ) ) {
                $parsed = json_decode( $matches[0], true );
            }
        }

        if ( $parsed && isset( $parsed['title'] ) && isset( $parsed['description'] ) ) {
            return array(
                'title'       => $parsed['title'],
                'description' => $parsed['description'],
            );
        }
    }

    // Fallback to mock if parsing fails
    return adforest_mock_ai_response( $context_data );
}

/**
 * Call Claude/Anthropic API for AI suggestions
 *
 * @param array  $context_data Context data for AI
 * @param string $api_key      Anthropic API key
 * @return array|WP_Error Array with 'title' and 'description', or WP_Error on failure
 */
function adforest_call_claude_api( $context_data, $api_key ) {
    global $adforest_theme;

    $prompt = adforest_build_ai_prompt( $context_data );

    // Get selected model from theme options (default: claude-3-5-sonnet-20241022)
    $model = isset( $adforest_theme['sb_claude_model'] ) ? $adforest_theme['sb_claude_model'] : 'claude-3-5-sonnet-20241022';

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'timeout' => 30,
        'headers' => array(
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'model'      => $model,
            'max_tokens' => 500,
            'messages'   => array(
                array(
                    'role'    => 'user',
                    'content' => $prompt
                )
            ),
        ) ),
    ) );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', __( 'Failed to connect to Claude. Please check your internet connection.', 'adforest' ) );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    // Handle HTTP errors
    if ( $response_code !== 200 ) {
        if ( $response_code === 401 ) {
            return new WP_Error( 'api_error', __( 'Invalid Claude API key. Please check your API key in Theme Options.', 'adforest' ) );
        } elseif ( $response_code === 429 ) {
            return new WP_Error( 'api_error', __( 'Claude rate limit exceeded. Please try again later.', 'adforest' ) );
        } elseif ( $response_code === 500 || $response_code === 503 ) {
            return new WP_Error( 'api_error', __( 'Claude service is temporarily unavailable. Please try again later.', 'adforest' ) );
        } elseif ( isset( $body['error']['message'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] );
        }
        return new WP_Error( 'api_error', __( 'Claude API error. Please try again.', 'adforest' ) );
    }

    // Parse successful response
    if ( isset( $body['content'][0]['text'] ) ) {
        $ai_content = trim( $body['content'][0]['text'] );

        // Try to parse JSON directly
        $parsed = json_decode( $ai_content, true );

        // If direct parse fails, try to extract JSON from response
        if ( ! $parsed ) {
            preg_match( '/\{[\s\S]*?"title"[\s\S]*?"description"[\s\S]*?\}/', $ai_content, $matches );
            if ( ! empty( $matches[0] ) ) {
                $parsed = json_decode( $matches[0], true );
            }
        }

        if ( $parsed && isset( $parsed['title'] ) && isset( $parsed['description'] ) ) {
            return array(
                'title'       => $parsed['title'],
                'description' => $parsed['description'],
            );
        }
    }

    // Fallback to mock if parsing fails
    return adforest_mock_ai_response( $context_data );
}

/**
 * Enqueue AI suggestions JavaScript
 *
 * IMPORTANT: Scripts are only enqueued when AI is fully ready.
 * This ensures no AI-related UI or behavior when AI is not configured.
 */
add_action( 'wp_enqueue_scripts', 'adforest_enqueue_ai_suggestions_script', 20 );

function adforest_enqueue_ai_suggestions_script() {
    global $adforest_theme;

    // CRITICAL: Use single source of truth for AI readiness
    // If AI is not ready, do NOT enqueue any scripts or modify UI
    if ( ! adforest_is_ai_ready() ) {
        return;
    }

    // Only load on ad posting page
    $sb_post_ad_page = isset( $adforest_theme['sb_post_ad_page'] ) ? $adforest_theme['sb_post_ad_page'] : '';
    $sb_post_ad_page = apply_filters( 'adforest_language_page_id', $sb_post_ad_page );

    if ( ! is_page( $sb_post_ad_page ) && ! has_shortcode( get_post_field( 'post_content', get_the_ID() ), 'ad-post-form' ) ) {
        // Check if we're on a page with the ad post form shortcode
        $content = get_post_field( 'post_content', get_the_ID() );
        if ( strpos( $content, '[ad-post-form' ) === false && strpos( $content, 'ad_post_short_base_func' ) === false ) {
            return;
        }
    }

    // Enqueue the AI suggestions script
    wp_enqueue_script(
        'adforest-ai-suggestions',
        trailingslashit( get_template_directory_uri() ) . 'assets/js/ai-suggestions.js',
        array( 'jquery' ),
        filemtime( get_template_directory() . '/assets/js/ai-suggestions.js' ),
        true
    );

    // Get AI provider for context
    $ai_provider = isset( $adforest_theme['sb_ai_provider'] ) ? $adforest_theme['sb_ai_provider'] : 'mock';

    // Check if intent tab should be shown
    $show_intent_tab = adforest_should_show_intent_tab();

    // Localize script with necessary data
    // IMPORTANT: 'ready' is the primary flag JS should check
    wp_localize_script( 'adforest-ai-suggestions', 'adforestAiSuggestions', array(
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'adforest_ai_suggestions_nonce' ),
        'ready'         => true, // AI is fully configured and ready (single source of truth for JS)
        'enabled'       => true, // Kept for backward compatibility
        'provider'      => $ai_provider,
        'debug'         => defined( 'WP_DEBUG' ) && WP_DEBUG, // Enable JS debug logs when WP_DEBUG is true
        'showIntentTab' => $show_intent_tab,
        'strings'       => array(
            'generating'       => __( 'Generating AI suggestions...', 'adforest' ),
            'error'            => __( 'Failed to generate suggestions. Please try again.', 'adforest' ),
            'success'          => __( 'AI suggestions applied!', 'adforest' ),
            'buttonText'       => __( 'Get AI Suggestions', 'adforest' ),
            'buttonLoading'    => __( 'Generating...', 'adforest' ),
            'selectCategory'   => __( 'Please select a category first to get AI suggestions.', 'adforest' ),
            'confirmOverwrite' => __( 'This will replace your current title and description. Continue?', 'adforest' ),
            'intentRequired'   => __( 'Please select Ad Type and Condition for better AI suggestions.', 'adforest' ),
        ),
    ) );
}
