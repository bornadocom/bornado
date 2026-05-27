<?php
/*
 * Template Name: AdForest - My Packages (Modern)
 *
 * Standalone "My Packages" page used by the Modern User Menu. Reads
 * the same per-user package data as the classic dashboard page
 * (user meta key `adforest_ads_package_details`) and renders it as a
 * SaaS-style card grid with status pills, feature stats, and a
 * Browse-more CTA. Independent from /dashboard/.
 *
 * @package Adforest
 */

if (function_exists('adforest_user_not_logged_in')) {
    adforest_user_not_logged_in();
}

global $adforest_theme;

get_header();

$user_id   = get_current_user_id();
$user_info = get_userdata($user_id);

// Theme button colors → CSS variables
$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';

$_rgb_parts    = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

// Theme-options-driven URLs
$post_ad_page_id = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : '';
$post_ad_page_id = apply_filters('adforest_ad_post_verified_id', $post_ad_page_id);
$post_ad_url     = $post_ad_page_id ? get_permalink($post_ad_page_id) : '#';
$modern_post_ad_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? $adforest_theme['sb_modern_post_ad_page'] : '';
$modern_post_ad_url     = $modern_post_ad_page_id ? get_permalink($modern_post_ad_page_id) : $post_ad_url;

$profile_page_id = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
$dash_url        = $profile_page_id ? trailingslashit(get_permalink($profile_page_id)) : home_url('/');

$packages_page_id = isset($adforest_theme['sb_packages_page']) ? $adforest_theme['sb_packages_page'] : '';
$browse_packages_url = $packages_page_id ? get_permalink($packages_page_id) : '';

// Sibling modern pages
$modern_listings_page_id  = isset($adforest_theme['sb_modern_my_listings_page']) ? $adforest_theme['sb_modern_my_listings_page'] : '';
$modern_listings_url      = $modern_listings_page_id ? get_permalink($modern_listings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_ads', $dash_url) : '#');
$modern_pending_page_id   = isset($adforest_theme['sb_modern_awaiting_approval_page']) ? $adforest_theme['sb_modern_awaiting_approval_page'] : '';
$modern_pending_url       = $modern_pending_page_id ? get_permalink($modern_pending_page_id) : ($dash_url ? add_query_arg('page_type', 'inactive_ads', $dash_url) : '#');
$modern_favorites_page_id = isset($adforest_theme['sb_modern_favorites_page']) ? $adforest_theme['sb_modern_favorites_page'] : '';
$modern_favorites_url     = $modern_favorites_page_id ? get_permalink($modern_favorites_page_id) : ($dash_url ? add_query_arg('page_type', 'fav_ads', $dash_url) : '#');
$modern_settings_page_id  = isset($adforest_theme['sb_modern_settings_page']) ? $adforest_theme['sb_modern_settings_page'] : '';
$modern_settings_url      = $modern_settings_page_id ? get_permalink($modern_settings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_profile', $dash_url) : '#');
$modern_messages_page_id  = isset($adforest_theme['sb_modern_messages_page']) ? $adforest_theme['sb_modern_messages_page'] : '';
$modern_messages_url      = $modern_messages_page_id ? get_permalink($modern_messages_page_id) : ($dash_url ? add_query_arg('page_type', 'msg', $dash_url) : '#');
$modern_invoices_page_id  = isset($adforest_theme['sb_modern_invoices_page']) ? $adforest_theme['sb_modern_invoices_page'] : '';
$modern_invoices_url      = $modern_invoices_page_id ? get_permalink($modern_invoices_page_id) : ($dash_url ? add_query_arg('page_type', 'invoices', $dash_url) : '#');

$account_nav = array(
    array('icon' => 'fa fa-plus-circle',     'label' => __('Add New',           'adforest'), 'url' => $modern_post_ad_url, 'active' => false),
    array('icon' => 'fa fa-clipboard-check', 'label' => __('Awaiting Approval', 'adforest'), 'url' => $modern_pending_url, 'active' => false),
    array('icon' => 'fa fa-receipt',         'label' => __('Invoices',          'adforest'), 'url' => $modern_invoices_url, 'active' => false),
    array('icon' => 'fa fa-list',            'label' => __('My Listings',       'adforest'), 'url' => $modern_listings_url, 'active' => false),
    array('icon' => 'fa fa-heart',           'label' => __('Favorites',         'adforest'), 'url' => $modern_favorites_url, 'active' => false),
    array('icon' => 'fa fa-envelope',        'label' => __('Messages',          'adforest'), 'url' => $modern_messages_url, 'active' => false),
    array('icon' => 'fa fa-box',             'label' => __('My Packages',       'adforest'), 'url' => get_permalink(), 'active' => true),
    array('icon' => 'fa fa-cog',             'label' => __('Profile Settings',  'adforest'), 'url' => $modern_settings_url, 'active' => false),
);

/* ------------------------------------------------------------------ */
/* Package data                                                        */
/* ------------------------------------------------------------------ */
$selected_packages = get_user_meta($user_id, 'adforest_ads_package_details', true);
if (!is_array($selected_packages)) {
    $selected_packages = array();
}
// Drop registration-bonus packages (matches the classic dashboard view).
$selected_packages = array_filter($selected_packages, function ($p) {
    return !(is_array($p) && isset($p['assigned_on_registration']) && $p['assigned_on_registration'] == 1);
});

if (!function_exists('adforest_mp_is_unlimited')) {
    function adforest_mp_is_unlimited($value) {
        return ($value === '-1' || $value === -1);
    }
}

if (!function_exists('adforest_mp_format_value')) {
    function adforest_mp_format_value($details, $key) {
        if (!isset($details[$key]) || $details[$key] === '' || $details[$key] === null) return false;
        $value = $details[$key];
        if (adforest_mp_is_unlimited($value)) return esc_html__('Unlimited', 'adforest');
        if (strtolower((string) $value) === 'yes') return esc_html__('Yes', 'adforest');
        if (strtolower((string) $value) === 'no')  return esc_html__('No', 'adforest');
        return esc_html($value);
    }
}

if (!function_exists('adforest_mp_format_expiry')) {
    function adforest_mp_format_expiry($details, $key) {
        if (!isset($details[$key]) || $details[$key] === '' || $details[$key] === null) return false;
        $value = $details[$key];
        if (adforest_mp_is_unlimited($value)) return esc_html__('Unlimited', 'adforest');
        $ts = strtotime((string) $value);
        if ($ts) return esc_html(date_i18n(get_option('date_format'), $ts));
        return false;
    }
}

if (!function_exists('adforest_mp_is_expired')) {
    function adforest_mp_is_expired($details) {
        if (!isset($details['pkg_expiry_days']) || $details['pkg_expiry_days'] === '' || $details['pkg_expiry_days'] === null) {
            return false;
        }
        $v = $details['pkg_expiry_days'];
        if (adforest_mp_is_unlimited($v)) return false;
        $ts = strtotime((string) $v);
        if (!$ts) return false;
        return $ts < current_time('timestamp');
    }
}

// Stat aggregates
$active_count   = 0;
$expired_count  = 0;
$has_unlimited_free     = false;
$has_unlimited_featured = false;
$total_free     = 0;
$total_featured = 0;

foreach ($selected_packages as $details) {
    if (!is_array($details)) continue;
    if (adforest_mp_is_expired($details)) {
        $expired_count++;
    } else {
        $active_count++;
    }
    if (isset($details['free_ads'])) {
        if (adforest_mp_is_unlimited($details['free_ads'])) {
            $has_unlimited_free = true;
        } elseif (is_numeric($details['free_ads'])) {
            $total_free += (int) $details['free_ads'];
        }
    }
    if (isset($details['featured_ads'])) {
        if (adforest_mp_is_unlimited($details['featured_ads'])) {
            $has_unlimited_featured = true;
        } elseif (is_numeric($details['featured_ads'])) {
            $total_featured += (int) $details['featured_ads'];
        }
    }
}

$total_packages = $active_count + $expired_count;

// Feature rows definition — label + key + icon. Rendered as a tidy
// two-column grid inside every package card; rows whose key is empty
// for that package are skipped automatically.
$feature_rows = array(
    array('key' => 'free_ads',             'label' => __('Free Ads',             'adforest'), 'icon' => 'fa fa-bullhorn'),
    array('key' => 'featured_ads',         'label' => __('Featured Ads',         'adforest'), 'icon' => 'fa fa-star'),
    array('key' => 'bump_ads',             'label' => __('Bump Ads',             'adforest'), 'icon' => 'fa fa-arrow-up'),
    array('key' => 'ad_expiry_days',       'label' => __('Ad Expiry (days)',     'adforest'), 'icon' => 'fa fa-hourglass-half'),
    array('key' => 'featured_expiry_days', 'label' => __('Featured Expiry (days)','adforest'),'icon' => 'fa fa-clock'),
    array('key' => 'video_links',          'label' => __('Video Links',          'adforest'), 'icon' => 'fa fa-video'),
    array('key' => 'num_of_images',        'label' => __('Images per Ad',        'adforest'), 'icon' => 'fa fa-image'),
    array('key' => 'allow_tags',           'label' => __('Allow Tags',           'adforest'), 'icon' => 'fa fa-tags'),
    array('key' => 'allow_bidding',        'label' => __('Allow Bidding',        'adforest'), 'icon' => 'fa fa-gavel'),
    array('key' => 'number_of_events',     'label' => __('Number of Events',     'adforest'), 'icon' => 'fa fa-calendar'),
    array('key' => 'paid_biddings',        'label' => __('Paid Biddings',        'adforest'), 'icon' => 'fa fa-coins'),
);
?>

<style id="adforest-my-packages-css">
.adforest-account-page{
    --adf-brand:<?php echo esc_attr($theme_btn_color); ?>;
    --adf-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;
    --adf-brand-text:<?php echo esc_attr($theme_btn_text); ?>;
    --adf-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;
    background:#f6f7fb;min-height:100vh;padding:32px 0 60px;
}
.adforest-account-page *{box-sizing:border-box;}

/* Account sub-nav */
/* Floating pill navigation — rounded card container, soft shadows,
 * strong active state, horizontal scroll on small screens. */
.adforest-account-nav{display:flex;flex-wrap:nowrap;align-items:center;gap:6px;background:#fff;border:1px solid #eef1f6;border-radius:16px;padding:8px;margin-bottom:28px;box-shadow:0 0 10px rgba(15,23,42,.05);overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;}
.adforest-account-nav::-webkit-scrollbar{display:none;}
.adforest-account-nav a{position:relative;display:inline-flex;align-items:center;gap:8px;color:#6b7280;font-size:14px;font-weight:600;text-decoration:none;padding:10px 18px;border-radius:10px;white-space:nowrap;flex-shrink:0;transition:color .18s ease,background .18s ease,box-shadow .18s ease,transform .12s ease;}
.adforest-account-nav a:hover{color:#1f2937;background:#f6f7fb;}
.adforest-account-nav a.is-active{color:var(--adf-brand-text);background:var(--adf-brand);box-shadow:0 0 6px rgba(var(--adf-brand-rgb),.25);}
.adforest-account-nav a.is-active:hover{background:var(--adf-brand-hover);color:var(--adf-brand-text);}
.adforest-account-nav a i{font-size:13px;color:inherit;opacity:.75;}

/* Page header */
.adforest-pk-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;}
.adforest-pk-header h1{font-size:28px;font-weight:700;color:#1f2937;margin:0;}
.adforest-pk-header .adforest-cta{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:10px 18px;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;transition:background .15s ease;}
.adforest-pk-header .adforest-cta:hover{background:var(--adf-brand-hover);}

/* Stat cards */
.adforest-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:24px;}
.adforest-stat{background:#fff;border-radius:14px;padding:20px 22px;box-shadow:0 2px 6px rgba(17,24,39,.04);position:relative;}
.adforest-stat__head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.adforest-stat__title{font-size:14px;color:#6b7280;font-weight:500;}
.adforest-stat__icon{width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}
.adforest-stat__count{font-size:30px;font-weight:800;color:#1f2937;line-height:1.1;letter-spacing:-.01em;}
.adforest-stat__sub{font-size:12px;color:#6b7280;margin-top:8px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.adforest-stat__sub strong{color:#1f2937;font-weight:700;}
.adforest-stat--active .adforest-stat__icon{background:rgba(16,185,129,.12);color:#10b981;}
.adforest-stat--free .adforest-stat__icon{background:rgba(59,130,246,.12);color:#3b82f6;}
.adforest-stat--featured .adforest-stat__icon{background:rgba(245,158,11,.12);color:#f59e0b;}

/* List card */
.adforest-list-card{background:#fff;border-radius:14px;box-shadow:0 2px 6px rgba(17,24,39,.04);padding:22px;}
.adforest-list-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;}
.adforest-list-title{font-size:18px;font-weight:700;color:#1f2937;margin:0;}
.adforest-list-meta{font-size:13px;color:#6b7280;}

/* Package grid */
.adforest-pk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;}
.adforest-pk-card{background:#fff;border:1px solid #eef0f4;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:14px;transition:border-color .15s ease,box-shadow .15s ease;}
.adforest-pk-card:hover{border-color:rgba(var(--adf-brand-rgb),.35);box-shadow:0 6px 18px rgba(17,24,39,.06);}
.adforest-pk-card__head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.adforest-pk-card__title{display:flex;align-items:center;gap:12px;min-width:0;}
.adforest-pk-card__icon{width:42px;height:42px;border-radius:10px;background:rgba(var(--adf-brand-rgb),.12);color:var(--adf-brand);display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.adforest-pk-card__name{font-size:16px;font-weight:700;color:#1f2937;line-height:1.3;word-break:break-word;}
.adforest-pk-card__id{font-size:12px;color:#94a3b8;margin-top:2px;}

/* Status pills */
.adforest-status{display:inline-flex;align-items:center;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;line-height:1.4;white-space:nowrap;}
.adforest-status.is-active{background:rgba(16,185,129,.10);color:#10b981;}
.adforest-status.is-expired{background:rgba(239,68,68,.10);color:#ef4444;}
.adforest-status.is-neutral{background:#f1f5f9;color:#475569;}

/* Expiry row */
.adforest-pk-expiry{display:flex;align-items:center;gap:8px;font-size:13px;color:#475569;background:#f9fafb;border-radius:10px;padding:10px 12px;}
.adforest-pk-expiry i{color:#94a3b8;}
.adforest-pk-expiry strong{color:#1f2937;font-weight:700;margin-left:auto;}

/* Feature grid */
.adforest-pk-features{display:grid;grid-template-columns:repeat(2,1fr);gap:10px 14px;margin:0;padding:0;list-style:none;}
.adforest-pk-features li{display:flex;align-items:center;gap:8px;font-size:13px;color:#475569;min-width:0;}
.adforest-pk-features li i{width:18px;color:#94a3b8;text-align:center;font-size:13px;flex-shrink:0;}
.adforest-pk-features li .adforest-pk-feat__label{color:#6b7280;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.adforest-pk-features li .adforest-pk-feat__value{color:#1f2937;font-weight:700;flex-shrink:0;}

/* Categories row */
.adforest-pk-cats{font-size:13px;color:#475569;border-top:1px dashed #eef0f4;padding-top:12px;}
.adforest-pk-cats__label{color:#6b7280;font-weight:600;margin-right:6px;}
.adforest-pk-cats__chips{display:inline-flex;flex-wrap:wrap;gap:6px;margin-top:6px;}
.adforest-pk-cats__chips .adforest-chip{background:rgba(var(--adf-brand-rgb),.08);color:var(--adf-brand);padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;}

/* Empty state */
.adforest-pk-empty{padding:60px 24px;text-align:center;color:#94a3b8;}
.adforest-pk-empty i{font-size:30px;color:#cbd5e1;margin-bottom:10px;display:block;}
.adforest-pk-empty p{margin:0 0 16px;font-size:14px;}
.adforest-pk-empty .adforest-empty__cta{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:10px 20px;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;transition:background .15s ease;}
.adforest-pk-empty .adforest-empty__cta:hover{background:var(--adf-brand-hover);color:var(--adf-brand-text) !important;}

/* Responsive */
@media (max-width:991px){
    .adforest-stats{grid-template-columns:1fr;}
}
@media (max-width:600px){
    .adforest-account-page{padding:20px 0 40px;}
    .adforest-pk-header h1{font-size:22px;}
    .adforest-account-nav{padding:6px;border-radius:14px;margin-bottom:18px;}
    .adforest-account-nav a{padding:8px 14px;font-size:13px;}
    .adforest-list-card{padding:14px;}
    .adforest-pk-grid{grid-template-columns:1fr;}
    .adforest-pk-features{grid-template-columns:1fr;}
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above.
 * ========================================================== */
body.rtl .adforest-pk-expiry strong{margin-left:0;margin-right:auto;}
body.rtl .adforest-pk-cats__label{margin-right:0;margin-left:6px;}
</style>

<div class="adforest-account-page">
    <div class="container adt-container">

        <!-- Account sub-nav -->
        <nav class="adforest-account-nav" aria-label="<?php esc_attr_e('Account navigation', 'adforest'); ?>">
            <?php foreach ($account_nav as $nav_item) : ?>
                <a href="<?php echo esc_url($nav_item['url']); ?>"
                   class="<?php echo $nav_item['active'] ? 'is-active' : ''; ?>">
                    <i class="<?php echo esc_attr($nav_item['icon']); ?>"></i>
                    <?php echo esc_html($nav_item['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Page header -->
        <div class="adforest-pk-header">
            <h1><?php esc_html_e('My Packages', 'adforest'); ?></h1>
            <?php if ($browse_packages_url) : ?>
                <a href="<?php echo esc_url($browse_packages_url); ?>" class="adforest-cta">
                    <i class="fa fa-shopping-cart"></i>
                    <?php esc_html_e('Browse Packages', 'adforest'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Stat cards -->
        <div class="adforest-stats">
            <div class="adforest-stat adforest-stat--active">
                <div class="adforest-stat__head">
                    <span class="adforest-stat__title"><?php esc_html_e('Active Packages', 'adforest'); ?></span>
                    <span class="adforest-stat__icon"><i class="fa fa-check-circle"></i></span>
                </div>
                <div class="adforest-stat__count"><?php echo esc_html(number_format_i18n($active_count)); ?></div>
                <div class="adforest-stat__sub">
                    <?php if ($expired_count > 0) : ?>
                        <strong><?php echo esc_html(number_format_i18n($expired_count)); ?></strong>
                        <?php esc_html_e('expired', 'adforest'); ?>
                    <?php else : ?>
                        <?php esc_html_e('currently in use', 'adforest'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="adforest-stat adforest-stat--free">
                <div class="adforest-stat__head">
                    <span class="adforest-stat__title"><?php esc_html_e('Free Ads Available', 'adforest'); ?></span>
                    <span class="adforest-stat__icon"><i class="fa fa-bullhorn"></i></span>
                </div>
                <div class="adforest-stat__count">
                    <?php echo $has_unlimited_free ? esc_html__('Unlimited', 'adforest') : esc_html(number_format_i18n($total_free)); ?>
                </div>
                <div class="adforest-stat__sub">
                    <?php esc_html_e('across all packages', 'adforest'); ?>
                </div>
            </div>

            <div class="adforest-stat adforest-stat--featured">
                <div class="adforest-stat__head">
                    <span class="adforest-stat__title"><?php esc_html_e('Featured Ads Available', 'adforest'); ?></span>
                    <span class="adforest-stat__icon"><i class="fa fa-star"></i></span>
                </div>
                <div class="adforest-stat__count">
                    <?php echo $has_unlimited_featured ? esc_html__('Unlimited', 'adforest') : esc_html(number_format_i18n($total_featured)); ?>
                </div>
                <div class="adforest-stat__sub">
                    <?php esc_html_e('across all packages', 'adforest'); ?>
                </div>
            </div>
        </div>

        <!-- List card -->
        <div class="adforest-list-card">
            <div class="adforest-list-toolbar">
                <h2 class="adforest-list-title"><?php esc_html_e('Purchased Packages', 'adforest'); ?></h2>
                <?php if ($total_packages > 0) : ?>
                    <span class="adforest-list-meta">
                        <?php
                        printf(
                            esc_html(_n('%s package', '%s packages', $total_packages, 'adforest')),
                            esc_html(number_format_i18n($total_packages))
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($selected_packages)) : ?>
                <div class="adforest-pk-empty">
                    <i class="fa fa-box-open" aria-hidden="true"></i>
                    <p><?php esc_html_e('You have not purchased any packages yet.', 'adforest'); ?></p>
                    <?php if ($browse_packages_url) : ?>
                        <a href="<?php echo esc_url($browse_packages_url); ?>" class="adforest-empty__cta">
                            <i class="fa fa-shopping-cart"></i> <?php esc_html_e('Browse Packages', 'adforest'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="adforest-pk-grid">
                    <?php foreach ($selected_packages as $package_id => $details) :
                        if (!is_array($details)) continue;

                        $product_title = '';
                        if (function_exists('wc_get_product')) {
                            $product = wc_get_product($package_id);
                            if ($product) {
                                $product_title = $product->get_title();
                            }
                        }
                        $card_title    = $product_title !== '' ? $product_title : esc_html__('Unknown Package', 'adforest');
                        $title_initial = strtoupper(function_exists('mb_substr') ? mb_substr($card_title, 0, 1) : substr($card_title, 0, 1));

                        $is_expired   = adforest_mp_is_expired($details);
                        $expiry_str   = adforest_mp_format_expiry($details, 'pkg_expiry_days');

                        // Categories
                        $category_ids = isset($details['allow_cate']) && $details['allow_cate'] !== '' ? explode(',', $details['allow_cate']) : array();
                        $category_chips = array();
                        if (!empty($category_ids)) {
                            if ($category_ids[0] === 'all') {
                                $category_chips[] = esc_html__('All Categories', 'adforest');
                            } else {
                                foreach ($category_ids as $cid) {
                                    $term = get_term((int) $cid, 'ad_cats');
                                    if ($term && !is_wp_error($term)) {
                                        $category_chips[] = $term->name;
                                    }
                                }
                            }
                        }
                    ?>
                        <article class="adforest-pk-card">
                            <div class="adforest-pk-card__head">
                                <div class="adforest-pk-card__title">
                                    <span class="adforest-pk-card__icon"><?php echo esc_html($title_initial); ?></span>
                                    <div>
                                        <div class="adforest-pk-card__name"><?php echo esc_html($card_title); ?></div>
                                        <div class="adforest-pk-card__id">#<?php echo esc_html($package_id); ?></div>
                                    </div>
                                </div>
                                <?php if ($expiry_str !== false) : ?>
                                    <span class="adforest-status <?php echo $is_expired ? 'is-expired' : 'is-active'; ?>">
                                        <?php echo $is_expired ? esc_html__('Expired', 'adforest') : esc_html__('Active', 'adforest'); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="adforest-status is-neutral"><?php esc_html_e('Lifetime', 'adforest'); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($expiry_str !== false) : ?>
                                <div class="adforest-pk-expiry">
                                    <i class="fa fa-calendar-alt"></i>
                                    <span><?php esc_html_e('Expires on', 'adforest'); ?></span>
                                    <strong><?php echo $expiry_str; ?></strong>
                                </div>
                            <?php endif; ?>

                            <ul class="adforest-pk-features">
                                <?php foreach ($feature_rows as $row) :
                                    $val = adforest_mp_format_value($details, $row['key']);
                                    if ($val === false) continue;
                                ?>
                                    <li>
                                        <i class="<?php echo esc_attr($row['icon']); ?>"></i>
                                        <span class="adforest-pk-feat__label"><?php echo esc_html($row['label']); ?></span>
                                        <span class="adforest-pk-feat__value"><?php echo $val; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if (!empty($category_chips)) : ?>
                                <div class="adforest-pk-cats">
                                    <span class="adforest-pk-cats__label"><?php esc_html_e('Allowed Categories:', 'adforest'); ?></span>
                                    <div class="adforest-pk-cats__chips">
                                        <?php foreach ($category_chips as $chip) : ?>
                                            <span class="adforest-chip"><?php echo esc_html($chip); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
