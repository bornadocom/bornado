<?php
/*
 * Template Name: AdForest - Invoices (Modern)
 *
 * Standalone "Invoices" page used by the Modern User Menu. Lists the
 * current user's WooCommerce orders (package purchases) with stat
 * cards for paid / pending / failed and a searchable, status-
 * filterable invoice list. Independent from /dashboard/.
 *
 * @package Adforest
 */

if (function_exists('adforest_user_not_logged_in')) {
    adforest_user_not_logged_in();
}

global $adforest_theme;

get_header();

$user_id        = get_current_user_id();
$user_info      = get_userdata($user_id);
$paged          = max(1, (int) (isset($_GET['paged']) ? $_GET['paged'] : 1));
$posts_per_page = 10;
$status_filter  = isset($_GET['status']) && in_array($_GET['status'], array('all', 'paid', 'pending', 'failed'), true) ? sanitize_key($_GET['status']) : 'all';
$search_q       = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

// Theme button colors → CSS variables
$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';

$_rgb_parts = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

// Theme-options-driven URLs
$post_ad_page_id = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : '';
$post_ad_page_id = apply_filters('adforest_ad_post_verified_id', $post_ad_page_id);
$post_ad_url     = $post_ad_page_id ? get_permalink($post_ad_page_id) : '#';
$modern_post_ad_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? $adforest_theme['sb_modern_post_ad_page'] : '';
$modern_post_ad_url     = $modern_post_ad_page_id ? get_permalink($modern_post_ad_page_id) : $post_ad_url;

$profile_page_id = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
$dash_url        = $profile_page_id ? trailingslashit(get_permalink($profile_page_id)) : home_url('/');

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
$modern_packages_page_id  = isset($adforest_theme['sb_modern_my_packages_page']) ? $adforest_theme['sb_modern_my_packages_page'] : '';
$modern_packages_url      = $modern_packages_page_id ? get_permalink($modern_packages_page_id) : ($dash_url ? add_query_arg('page_type', 'my_packages', $dash_url) : '#');

$current_page_url = remove_query_arg(array('paged', 'status', 's'));

// WC status buckets → stat cards
$paid_statuses    = array('completed', 'processing');
$pending_statuses = array('pending', 'on-hold');
$failed_statuses  = array('failed', 'cancelled', 'refunded');

$all_orders = array();
$paid_count = $pending_count = $failed_count = 0;
$paid_amount = $pending_amount = $failed_amount = 0.0;

if (class_exists('WooCommerce') && function_exists('wc_get_orders')) {
    $all_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'limit'       => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'status'      => array_merge($paid_statuses, $pending_statuses, $failed_statuses),
    ));

    foreach ($all_orders as $o) {
        $st  = $o->get_status();
        $tot = (float) $o->get_total();
        if (in_array($st, $paid_statuses, true)) {
            $paid_count++;
            $paid_amount += $tot;
        } elseif (in_array($st, $pending_statuses, true)) {
            $pending_count++;
            $pending_amount += $tot;
        } elseif (in_array($st, $failed_statuses, true)) {
            $failed_count++;
            $failed_amount += $tot;
        }
    }
}

// Apply status + search filter
$filtered = array();
foreach ($all_orders as $o) {
    $st = $o->get_status();
    if ($status_filter === 'paid' && !in_array($st, $paid_statuses, true)) continue;
    if ($status_filter === 'pending' && !in_array($st, $pending_statuses, true)) continue;
    if ($status_filter === 'failed' && !in_array($st, $failed_statuses, true)) continue;

    if ($search_q !== '') {
        $needle = strtolower($search_q);
        $hit    = false;
        if (strpos((string) $o->get_id(), $search_q) !== false) $hit = true;
        if (!$hit) {
            foreach ($o->get_items() as $item) {
                if (strpos(strtolower($item->get_name()), $needle) !== false) { $hit = true; break; }
            }
        }
        if (!$hit && stripos((string) $o->get_billing_email(), $needle) !== false) $hit = true;
        if (!$hit) continue;
    }
    $filtered[] = $o;
}

$total_filtered = count($filtered);
$total_pages    = max(1, (int) ceil($total_filtered / $posts_per_page));
$paged          = min($paged, $total_pages);
$paged_orders   = array_slice($filtered, ($paged - 1) * $posts_per_page, $posts_per_page);

$account_nav = array(
    array('icon' => 'fa fa-plus-circle',     'label' => __('Add New',           'adforest'), 'url' => $modern_post_ad_url, 'active' => false),
    array('icon' => 'fa fa-clipboard-check', 'label' => __('Awaiting Approval', 'adforest'), 'url' => $modern_pending_url, 'active' => false),
    array('icon' => 'fa fa-receipt',         'label' => __('Invoices',          'adforest'), 'url' => get_permalink(), 'active' => true),
    array('icon' => 'fa fa-list',            'label' => __('My Listings',       'adforest'), 'url' => $modern_listings_url, 'active' => false),
    array('icon' => 'fa fa-heart',           'label' => __('Favorites',         'adforest'), 'url' => $modern_favorites_url, 'active' => false),
    array('icon' => 'fa fa-envelope',        'label' => __('Messages',          'adforest'), 'url' => $modern_messages_url, 'active' => false),
    array('icon' => 'fa fa-box',             'label' => __('My Packages',       'adforest'), 'url' => $modern_packages_url, 'active' => false),
    array('icon' => 'fa fa-cog',             'label' => __('Profile Settings',  'adforest'), 'url' => $modern_settings_url, 'active' => false),
);

if (!function_exists('adforest_modern_inv_status_meta')) {
    function adforest_modern_inv_status_meta($status) {
        if (in_array($status, array('completed', 'processing'), true)) return array(__('Paid', 'adforest'), 'is-paid');
        if (in_array($status, array('pending', 'on-hold'), true))     return array(__('Pending', 'adforest'), 'is-pending');
        if (in_array($status, array('failed', 'cancelled', 'refunded'), true)) return array(__('Failed', 'adforest'), 'is-failed');
        return array(ucfirst($status), '');
    }
}
?>

<style id="adforest-invoices-css">
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
.adforest-inv-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;}
.adforest-inv-header h1{font-size:28px;font-weight:700;color:#1f2937;margin:0;}
.adforest-inv-header .adforest-cta{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:10px 18px;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;transition:background .15s ease;}
.adforest-inv-header .adforest-cta:hover{background:var(--adf-brand-hover);}

/* Stat cards */
.adforest-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:24px;}
.adforest-stat{background:#fff;border-radius:14px;padding:20px 22px;box-shadow:0 2px 6px rgba(17,24,39,.04);position:relative;}
.adforest-stat__head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.adforest-stat__title{font-size:14px;color:#6b7280;font-weight:500;}
.adforest-stat__count{font-size:30px;font-weight:800;color:#1f2937;line-height:1.1;letter-spacing:-.01em;}
.adforest-stat__sub{font-size:12px;color:#6b7280;margin-top:8px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.adforest-stat__sub strong{color:#1f2937;font-weight:700;}
.adforest-stat__sub bdi{color:#1f2937;font-weight:700;}
.adforest-stat--paid .adforest-stat__chip{color:#10b981;}
.adforest-stat--pending .adforest-stat__chip{color:#f59e0b;}
.adforest-stat--failed .adforest-stat__chip{color:#ef4444;}
.adforest-stat__chip i{font-size:12px;}

/* List card */
.adforest-list-card{background:#fff;border-radius:14px;box-shadow:0 2px 6px rgba(17,24,39,.04);padding:22px;}
.adforest-list-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;}
.adforest-list-title{font-size:18px;font-weight:700;color:#1f2937;margin:0;}
.adforest-list-controls{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap;}
.adforest-search-wrap{position:relative;display:inline-flex;align-items:center;}
.adforest-search-wrap .adforest-search-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none;}
.adforest-search-wrap input[type="search"]{appearance:none;-webkit-appearance:none;border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:9px 14px 9px 38px;font-size:13px;color:#1f2937;min-width:220px;height:40px;font-family:inherit;}
.adforest-search-wrap input[type="search"]:focus{outline:none;border-color:var(--adf-brand);box-shadow:0 0 0 3px rgba(var(--adf-brand-rgb),.12);}
.adforest-pill-select{appearance:none;-webkit-appearance:none;border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:9px 36px 9px 14px;font-size:13px;color:#1f2937;height:40px;cursor:pointer;font-family:inherit;background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;}
.adforest-pill-select:focus{outline:none;border-color:var(--adf-brand);box-shadow:0 0 0 3px rgba(var(--adf-brand-rgb),.12);}

/* Table */
.adforest-inv-table-wrap{overflow-x:auto;}
.adforest-inv-table{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;}
.adforest-inv-table thead th{text-align:left;padding:12px 14px;color:#6b7280;font-weight:600;font-size:13px;background:#f9fafb;border-top:1px solid #eef0f4;border-bottom:1px solid #eef0f4;white-space:nowrap;}
.adforest-inv-table thead th:first-child{border-top-left-radius:8px;border-bottom-left-radius:8px;}
.adforest-inv-table thead th:last-child{border-top-right-radius:8px;border-bottom-right-radius:8px;text-align:right;}
.adforest-inv-table tbody td{padding:16px 14px;border-bottom:1px solid #eef0f4;color:#1f2937;vertical-align:middle;}
.adforest-inv-table tbody tr:last-child td{border-bottom:0;}
.adforest-inv-table tbody td:last-child{text-align:right;white-space:nowrap;}
.adforest-inv-id{color:#1f2937;font-weight:600;}
.adforest-inv-package{display:inline-flex;align-items:center;gap:10px;color:#1f2937;font-weight:600;}
.adforest-inv-package__icon{width:30px;height:30px;border-radius:50%;background:rgba(var(--adf-brand-rgb),.12);color:var(--adf-brand);display:inline-flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;font-weight:700;}
.adforest-inv-email{color:#6b7280;}
.adforest-inv-amount{color:#1f2937;font-weight:700;display:inline-block;}
.adforest-inv-amount bdi{color:#1f2937;font-weight:700;}

/* Status pills */
.adforest-status{display:inline-flex;align-items:center;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;line-height:1.4;}
.adforest-status.is-paid{background:rgba(16,185,129,.10);color:#10b981;}
.adforest-status.is-pending{background:rgba(245,158,11,.10);color:#f59e0b;}
.adforest-status.is-failed{background:rgba(239,68,68,.10);color:#ef4444;}

/* Action button (View Invoice) */
.adforest-inv-action{display:inline-flex;align-items:center;gap:6px;background:rgba(var(--adf-brand-rgb),.10);color:var(--adf-brand);border:0;border-radius:8px;padding:7px 12px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .15s ease,color .15s ease;font-family:inherit;}
.adforest-inv-action:hover{background:var(--adf-brand);color:var(--adf-brand-text);}
.adforest-inv-action i{font-size:12px;}

/* Footer pagination */
.adforest-inv-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:18px;padding-top:16px;border-top:1px solid #eef0f4;}
.adforest-inv-footer__info{color:#6b7280;font-size:13px;}
.adforest-inv-footer__nav{display:inline-flex;gap:8px;}
.adforest-inv-footer__nav a,.adforest-inv-footer__nav span{display:inline-flex;align-items:center;justify-content:center;min-width:84px;height:38px;padding:0 14px;border:1px solid #e5e7eb;background:#fff;border-radius:8px;color:#1f2937;text-decoration:none;font-weight:600;font-size:13px;transition:background .15s ease,color .15s ease,border-color .15s ease;}
.adforest-inv-footer__nav a:hover{background:#f9fafb;}
.adforest-inv-footer__nav .is-disabled{color:#cbd5e1;background:#f9fafb;cursor:not-allowed;}

/* Empty state */
.adforest-inv-empty{padding:60px 24px;text-align:center;color:#94a3b8;}
.adforest-inv-empty i{font-size:30px;color:#cbd5e1;margin-bottom:10px;display:block;}
.adforest-inv-empty p{margin:0 0 16px;font-size:14px;}
.adforest-inv-empty .adforest-empty__cta{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:10px 20px;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;transition:background .15s ease;}
.adforest-inv-empty .adforest-empty__cta:hover{background:var(--adf-brand-hover);color:var(--adf-brand-text) !important;}

/* Print view */
#adforest-inv-print{display:none;}
@media print{
    body *{visibility:hidden;}
    #adforest-inv-print, #adforest-inv-print *{visibility:visible;}
    #adforest-inv-print{position:absolute;left:0;top:0;width:100%;display:block !important;}
    .adforest-no-print{display:none !important;}
}

/* Responsive */
@media (max-width:991px){
    .adforest-stats{grid-template-columns:1fr;}
}
@media (max-width:600px){
    .adforest-account-page{padding:20px 0 40px;}
    .adforest-inv-header h1{font-size:22px;}
    .adforest-account-nav{padding:6px;border-radius:14px;margin-bottom:18px;}
    .adforest-account-nav a{padding:8px 14px;font-size:13px;}
    .adforest-list-card{padding:14px;}
    .adforest-search-wrap input[type="search"]{min-width:160px;}
    .adforest-list-toolbar{align-items:stretch;}
    .adforest-list-controls{width:100%;}
    .adforest-list-controls .adforest-search-wrap{flex:1;}
    .adforest-list-controls .adforest-search-wrap input[type="search"]{width:100%;}
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above.
 * ========================================================== */
body.rtl .adforest-inv-table thead th{text-align:right;}
body.rtl .adforest-inv-table thead th:first-child{border-top-left-radius:0;border-bottom-left-radius:0;border-top-right-radius:8px;border-bottom-right-radius:8px;}
body.rtl .adforest-inv-table thead th:last-child{border-top-right-radius:0;border-bottom-right-radius:0;border-top-left-radius:8px;border-bottom-left-radius:8px;text-align:left;}
body.rtl .adforest-inv-table tbody td:last-child{text-align:left;}
body.rtl .adforest-search-wrap .adforest-search-icon{left:auto;right:14px;}
body.rtl .adforest-search-wrap input[type="search"]{padding:9px 38px 9px 14px;}
body.rtl .adforest-pill-select{padding:9px 14px 9px 36px;background-position:left 12px center;}
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
        <div class="adforest-inv-header">
            <h1><?php esc_html_e('Bill & Invoice', 'adforest'); ?></h1>
        </div>

        <!-- Stat cards -->
        <div class="adforest-stats">
            <div class="adforest-stat adforest-stat--paid">
                <div class="adforest-stat__head">
                    <span class="adforest-stat__title"><?php esc_html_e('Paid Invoice', 'adforest'); ?></span>
                </div>
                <div class="adforest-stat__count"><?php echo esc_html(number_format_i18n($paid_count)); ?></div>
                <div class="adforest-stat__sub">
                    <span class="adforest-stat__chip"><i class="fa fa-arrow-up" aria-hidden="true"></i></span>
                    <strong><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($paid_amount) : number_format_i18n($paid_amount, 2)); ?></strong>
                    <?php esc_html_e('total received', 'adforest'); ?>
                </div>
            </div>

            <div class="adforest-stat adforest-stat--pending">
                <div class="adforest-stat__head">
                    <span class="adforest-stat__title"><?php esc_html_e('Pending Invoice', 'adforest'); ?></span>
                </div>
                <div class="adforest-stat__count"><?php echo esc_html(number_format_i18n($pending_count)); ?></div>
                <div class="adforest-stat__sub">
                    <span class="adforest-stat__chip"><i class="fa fa-clock" aria-hidden="true"></i></span>
                    <strong><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($pending_amount) : number_format_i18n($pending_amount, 2)); ?></strong>
                    <?php esc_html_e('awaiting payment', 'adforest'); ?>
                </div>
            </div>

            <div class="adforest-stat adforest-stat--failed">
                <div class="adforest-stat__head">
                    <span class="adforest-stat__title"><?php esc_html_e('Failed Invoice', 'adforest'); ?></span>
                </div>
                <div class="adforest-stat__count"><?php echo esc_html(number_format_i18n($failed_count)); ?></div>
                <div class="adforest-stat__sub">
                    <span class="adforest-stat__chip"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                    <strong><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($failed_amount) : number_format_i18n($failed_amount, 2)); ?></strong>
                    <?php esc_html_e('not collected', 'adforest'); ?>
                </div>
            </div>
        </div>

        <!-- List card -->
        <div class="adforest-list-card">
            <div class="adforest-list-toolbar">
                <h2 class="adforest-list-title"><?php esc_html_e('Invoices List', 'adforest'); ?></h2>
                <form class="adforest-list-controls" method="get" action="<?php echo esc_url($current_page_url); ?>">
                    <div class="adforest-search-wrap">
                        <i class="fa fa-search adforest-search-icon" aria-hidden="true"></i>
                        <input type="search" name="s" value="<?php echo esc_attr($search_q); ?>" placeholder="<?php esc_attr_e('Search', 'adforest'); ?>" />
                    </div>
                    <select name="status" class="adforest-pill-select" onchange="this.form.submit()">
                        <option value="all" <?php selected($status_filter, 'all'); ?>><?php esc_html_e('All Status', 'adforest'); ?></option>
                        <option value="paid" <?php selected($status_filter, 'paid'); ?>><?php esc_html_e('Paid', 'adforest'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'adforest'); ?></option>
                        <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php esc_html_e('Failed', 'adforest'); ?></option>
                    </select>
                </form>
            </div>

            <?php if ($total_filtered === 0) : ?>
                <div class="adforest-inv-empty">
                    <i class="fa fa-receipt" aria-hidden="true"></i>
                    <p><?php echo $search_q !== '' || $status_filter !== 'all' ? esc_html__('No invoices match your filters.', 'adforest') : esc_html__('You have no invoices yet.', 'adforest'); ?></p>
                    <?php if ($search_q !== '' || $status_filter !== 'all') : ?>
                        <a href="<?php echo esc_url($current_page_url); ?>" class="adforest-empty__cta">
                            <i class="fa fa-redo"></i> <?php esc_html_e('Reset Filters', 'adforest'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="adforest-inv-table-wrap">
                    <table class="adforest-inv-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Invoice ID', 'adforest'); ?></th>
                                <th><?php esc_html_e('Package', 'adforest'); ?></th>
                                <th><?php esc_html_e('Date', 'adforest'); ?></th>
                                <th><?php esc_html_e('Email', 'adforest'); ?></th>
                                <th><?php esc_html_e('Status', 'adforest'); ?></th>
                                <th><?php esc_html_e('Amount', 'adforest'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($paged_orders as $o) :
                            $items = $o->get_items();
                            $package_name = '';
                            foreach ($items as $item) {
                                $package_name .= $item->get_name() . ', ';
                            }
                            $package_name    = rtrim($package_name, ', ');
                            $package_initial = $package_name !== '' ? strtoupper(function_exists('mb_substr') ? mb_substr($package_name, 0, 1) : substr($package_name, 0, 1)) : '#';
                            $email_for_row   = $o->get_billing_email();
                            if (empty($email_for_row) && $user_info) $email_for_row = $user_info->user_email;
                            list($status_label, $status_class) = adforest_modern_inv_status_meta($o->get_status());
                            $date_created = $o->get_date_created();
                        ?>
                            <tr>
                                <td><span class="adforest-inv-id">#<?php echo esc_html($o->get_id()); ?></span></td>
                                <td>
                                    <span class="adforest-inv-package">
                                        <span class="adforest-inv-package__icon"><?php echo esc_html($package_initial); ?></span>
                                        <span><?php echo esc_html($package_name !== '' ? $package_name : __('Order', 'adforest')); ?></span>
                                    </span>
                                </td>
                                <td><?php echo esc_html($date_created ? date_i18n(get_option('date_format'), $date_created->getTimestamp()) : ''); ?></td>
                                <td class="adforest-inv-email"><?php echo esc_html($email_for_row); ?></td>
                                <td><span class="adforest-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                                <td>
                                    <span class="adforest-inv-amount"><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($o->get_total()) : $o->get_total()); ?></span>
                                    <button type="button" class="adforest-inv-action download-invoice-btn" data-order-id="<?php echo esc_attr($o->get_id()); ?>" style="margin-left:10px;">
                                        <i class="fa fa-eye"></i> <?php esc_html_e('View', 'adforest'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1) :
                    $prev_page = max(1, $paged - 1);
                    $next_page = min($total_pages, $paged + 1);
                ?>
                <div class="adforest-inv-footer">
                    <div class="adforest-inv-footer__info">
                        <?php
                        printf(
                            /* translators: 1: current page, 2: total pages */
                            esc_html__('Page %1$s of %2$s', 'adforest'),
                            esc_html(number_format_i18n($paged)),
                            esc_html(number_format_i18n($total_pages))
                        );
                        ?>
                    </div>
                    <div class="adforest-inv-footer__nav">
                        <?php if ($paged <= 1) : ?>
                            <span class="is-disabled"><?php esc_html_e('Previous', 'adforest'); ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $prev_page)); ?>"><?php esc_html_e('Previous', 'adforest'); ?></a>
                        <?php endif; ?>
                        <?php if ($paged >= $total_pages) : ?>
                            <span class="is-disabled"><?php esc_html_e('Next', 'adforest'); ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $next_page)); ?>"><?php esc_html_e('Next', 'adforest'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Invoice print modal (reuses existing get_invoice_html AJAX handler) -->
<div id="adforest-inv-print" style="display:none;">
    <div id="invoice-content"></div>
</div>

<script>
jQuery(document).ready(function($) {
    $(document).on('click', '.adforest-account-page .download-invoice-btn', function(e){
        e.preventDefault();
        var btn = $(this);
        var orderId = btn.data('order-id');
        var origHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'get_invoice_html',
                order_id: orderId,
                nonce: '<?php echo esc_js(wp_create_nonce('get_invoice_nonce')); ?>'
            },
            success: function(response){
                if (response && response.success){
                    $('.adforest-account-page').hide();
                    $('#invoice-content').html(response.data.html);
                    $('#adforest-inv-print').show();
                    window.scrollTo(0, 0);
                } else {
                    alert('<?php echo esc_js(__('Error loading invoice', 'adforest')); ?>');
                }
                btn.prop('disabled', false).html(origHtml);
            },
            error: function(){
                alert('<?php echo esc_js(__('Error loading invoice', 'adforest')); ?>');
                btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    $(document).on('click', '#adforest-inv-print .btn-close', function(){
        $('#adforest-inv-print').hide();
        $('.adforest-account-page').show();
    });
    $(document).on('click', '#adforest-inv-print .btn-print', function(){ window.print(); });
});
</script>

<?php get_footer(); ?>
