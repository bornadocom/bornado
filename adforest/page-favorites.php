<?php
/*
 * Template Name: AdForest - Favorites (Modern)
 *
 * Standalone "Favorites" page used by the Modern User Menu.
 * Lists ads the current user has saved as favorites (via _sb_fav_id_*
 * user meta). Independent from /dashboard/ — no dashboard files modified.
 *
 * @package Adforest
 */

if (function_exists('adforest_user_not_logged_in')) {
    adforest_user_not_logged_in();
}

global $adforest_theme;

get_header();

$user_id        = get_current_user_id();
$orderby_filter = isset($_GET['orderby']) && in_array($_GET['orderby'], array('newest', 'oldest'), true) ? sanitize_key($_GET['orderby']) : 'newest';
$search_q       = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$paged          = max(1, (int) get_query_var('paged', 1));
$posts_per_page = (int) get_option('posts_per_page');

// Theme button colors → CSS variables (track Theme Options live)
$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular'])
    ? $adforest_theme['opt-theme-btn-color']['regular']
    : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover'])
    ? $adforest_theme['opt-theme-btn-color']['hover']
    : '#d6002a';
$theme_btn_text = !empty($adforest_theme['opt-theme-btn-text-color']['regular'])
    ? $adforest_theme['opt-theme-btn-text-color']['regular']
    : '#ffffff';

$_rgb_parts = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null)
    ? implode(',', $_rgb_parts)
    : '255,0,46';

// Theme-options-driven URLs
$post_ad_page_id  = isset($adforest_theme['sb_post_ad_page'])  ? $adforest_theme['sb_post_ad_page']  : '';
$post_ad_page_id  = apply_filters('adforest_ad_post_verified_id', $post_ad_page_id);
$post_ad_url      = $post_ad_page_id ? get_permalink($post_ad_page_id) : '#';
$modern_post_ad_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? $adforest_theme['sb_modern_post_ad_page'] : '';
$modern_post_ad_url     = $modern_post_ad_page_id ? get_permalink($modern_post_ad_page_id) : $post_ad_url;

$profile_page_id  = isset($adforest_theme['sb_profile_page'])  ? $adforest_theme['sb_profile_page']  : '';
$dash_url         = $profile_page_id ? trailingslashit(get_permalink($profile_page_id)) : home_url('/');

// Sibling modern pages (cross-link sub-nav to standalone Modern pages where set)
$modern_listings_page_id = isset($adforest_theme['sb_modern_my_listings_page']) ? $adforest_theme['sb_modern_my_listings_page'] : '';
$modern_listings_url     = $modern_listings_page_id ? get_permalink($modern_listings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_ads', $dash_url) : '#');
$modern_pending_page_id  = isset($adforest_theme['sb_modern_awaiting_approval_page']) ? $adforest_theme['sb_modern_awaiting_approval_page'] : '';
$modern_pending_url      = $modern_pending_page_id ? get_permalink($modern_pending_page_id) : ($dash_url ? add_query_arg('page_type', 'inactive_ads', $dash_url) : '#');
$modern_settings_page_id = isset($adforest_theme['sb_modern_settings_page']) ? $adforest_theme['sb_modern_settings_page'] : '';
$modern_settings_url     = $modern_settings_page_id ? get_permalink($modern_settings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_profile', $dash_url) : '#');
$modern_invoices_page_id = isset($adforest_theme['sb_modern_invoices_page']) ? $adforest_theme['sb_modern_invoices_page'] : '';
$modern_invoices_url     = $modern_invoices_page_id ? get_permalink($modern_invoices_page_id) : ($dash_url ? add_query_arg('page_type', 'invoices', $dash_url) : '#');
$modern_messages_page_id = isset($adforest_theme['sb_modern_messages_page']) ? $adforest_theme['sb_modern_messages_page'] : '';
$modern_messages_url     = $modern_messages_page_id ? get_permalink($modern_messages_page_id) : ($dash_url ? add_query_arg('page_type', 'msg', $dash_url) : '#');
$modern_packages_page_id = isset($adforest_theme['sb_modern_my_packages_page']) ? $adforest_theme['sb_modern_my_packages_page'] : '';
$modern_packages_url     = $modern_packages_page_id ? get_permalink($modern_packages_page_id) : ($dash_url ? add_query_arg('page_type', 'my_packages', $dash_url) : '#');

$current_page_url = remove_query_arg(array('paged', 'orderby', 's'));

// Favorites lookup — same logic as adforest_get_ads_query_args('fav_ads')
global $wpdb;
$fav_like = $wpdb->esc_like('_sb_fav_id_') . '%';
$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
        $user_id,
        $fav_like
    )
);
$fav_ids = array(0); // 0 ensures empty result set when user has no favorites
foreach ($rows as $row) {
    $fav_ids[] = (int) $row->meta_value;
}

$query_args = array(
    'post_type'      => 'ad_post',
    'post__in'       => $fav_ids,
    'posts_per_page' => $posts_per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => ($orderby_filter === 'oldest') ? 'ASC' : 'DESC',
    'post_status'    => 'publish',
    'meta_query'     => array(
        array(
            'key'     => '_adforest_ad_status_',
            'value'   => array('expired', 'sold'),
            'compare' => 'NOT IN',
        ),
    ),
);

if ($search_q !== '') {
    $query_args['s'] = $search_q;
}

$favorites_query = new WP_Query($query_args);
$favorites_total = (int) $favorites_query->found_posts;

// Single nonce used by all .remove_fav_ad clicks on this page (matches existing AJAX handler)
$fav_remove_nonce = wp_create_nonce('sb_fav_remove_ad_nonce');

$account_nav = array(
    array('icon' => 'fa fa-plus-circle',     'label' => __('Add New',           'adforest'), 'url' => $modern_post_ad_url, 'active' => false),
    array('icon' => 'fa fa-clipboard-check', 'label' => __('Awaiting Approval', 'adforest'), 'url' => $modern_pending_url, 'active' => false),
    array('icon' => 'fa fa-receipt',         'label' => __('Invoices',          'adforest'), 'url' => $modern_invoices_url, 'active' => false),
    array('icon' => 'fa fa-list',            'label' => __('My Listings',       'adforest'), 'url' => $modern_listings_url, 'active' => false),
    array('icon' => 'fa fa-heart',           'label' => __('Favorites',         'adforest'), 'url' => get_permalink(), 'active' => true),
    array('icon' => 'fa fa-envelope',        'label' => __('Messages',          'adforest'), 'url' => $modern_messages_url, 'active' => false),
    array('icon' => 'fa fa-box',             'label' => __('My Packages',       'adforest'), 'url' => $modern_packages_url, 'active' => false),
    array('icon' => 'fa fa-cog',             'label' => __('Profile Settings',  'adforest'), 'url' => $modern_settings_url, 'active' => false),
);
?>

<style id="adforest-favorites-css">
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

/* Header row */
.adforest-page-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;}
.adforest-page-header h1{font-size:28px;font-weight:700;color:#1f2937;margin:0;display:inline-flex;align-items:center;gap:12px;}
.adforest-page-header__count{display:inline-flex;align-items:center;justify-content:center;background:rgba(var(--adf-brand-rgb),.10);color:var(--adf-brand);font-size:14px;font-weight:700;padding:4px 14px;border-radius:999px;line-height:1.4;}

/* Toolbar */
.adforest-toolbar{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:24px;}
.adforest-controls{display:inline-flex;align-items:center;gap:12px;flex-wrap:wrap;margin:0;}
.adforest-controls__label{color:#6b7280;font-size:14px;font-weight:500;display:inline-flex;align-items:center;gap:6px;}
.adforest-controls__label i{color:#94a3b8;font-size:13px;}
.adforest-controls select,.adforest-controls input[type="search"]{appearance:none;-webkit-appearance:none;border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:9px 14px;font-size:13px;color:#1f2937;min-width:180px;height:42px;font-family:inherit;}
.adforest-controls select{padding-right:36px;cursor:pointer;background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;}
.adforest-controls input[type="search"]:focus,.adforest-controls select:focus{outline:none;border-color:var(--adf-brand);box-shadow:0 0 0 3px rgba(var(--adf-brand-rgb),.12);}
.adforest-search-wrap{position:relative;display:inline-flex;align-items:center;}
.adforest-search-wrap .adforest-search-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none;}
.adforest-search-wrap input[type="search"]{padding-left:38px;min-width:200px;}

/* Listing cards (3-column grid for favorites — cleaner browse view) */
.adforest-listings{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.adforest-listing-card{background:#fff;border-radius:12px;box-shadow:0 2px 6px rgba(17,24,39,.04);overflow:hidden;transition:box-shadow .2s ease,transform .2s ease;display:flex;flex-direction:column;}
.adforest-listing-card:hover{box-shadow:0 10px 26px rgba(17,24,39,.07);transform:translateY(-2px);}

/* Media */
.adforest-listing-card__media{position:relative;display:block;background:#f6f7fb;overflow:hidden;aspect-ratio:16/11;}
.adforest-listing-card__media img{width:100%;height:100%;object-fit:cover;display:block;position:absolute;inset:0;transition:transform .35s ease;}
.adforest-listing-card:hover .adforest-listing-card__media img{transform:scale(1.04);}
.adforest-fav-toggle{position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:50%;background:#fff;color:var(--adf-brand);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border:0;cursor:pointer;box-shadow:0 2px 6px rgba(17,24,39,.12);transition:background .15s ease,color .15s ease,transform .15s ease;z-index:1;}
.adforest-fav-toggle:hover{background:var(--adf-brand);color:var(--adf-brand-text);transform:scale(1.05);}
.adforest-fav-toggle i{font-size:14px;}

/* Body */
.adforest-listing-card__body{padding:18px 20px 20px;display:flex;flex-direction:column;gap:8px;flex:1;min-width:0;}
.adforest-listing-card__title{margin:0;font-size:16px;font-weight:700;line-height:1.35;}
.adforest-listing-card__title a{color:#1f2937;text-decoration:none;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;transition:color .15s ease;}
.adforest-listing-card__title a:hover{color:var(--adf-brand);}
.adforest-listing-card__cat{display:inline-flex;align-self:flex-start;background:#f3f4f6;color:#6b7280;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:500;}
.adforest-listing-card__meta{font-size:12px;color:#6b7280;display:flex;flex-wrap:wrap;gap:14px;}
.adforest-listing-card__meta strong{color:var(--adf-brand);font-weight:600;margin-right:4px;}
.adforest-listing-card__price{font-size:18px;font-weight:800;color:#1f2937;letter-spacing:-.01em;margin-top:auto;padding-top:8px;}

/* Footer with seller + remove action */
.adforest-listing-card__footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;border-top:1px solid #eef0f4;background:#fafbfc;font-size:12px;}
.adforest-seller{display:inline-flex;align-items:center;gap:8px;color:#6b7280;text-decoration:none;min-width:0;flex:1;}
.adforest-seller img{width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;}
.adforest-seller .name{font-weight:600;color:#1f2937;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.adforest-seller:hover .name{color:var(--adf-brand);}
.adforest-remove-fav{display:inline-flex;align-items:center;gap:6px;background:none;border:0;color:#6b7280;cursor:pointer;font-size:12px;font-weight:600;padding:4px 8px;border-radius:6px;transition:background .15s ease,color .15s ease;text-decoration:none;}
.adforest-remove-fav:hover{background:rgba(239,68,68,.08);color:#ef4444;}
.adforest-remove-fav i{font-size:13px;}

/* Empty state */
.adforest-empty{background:#fff;border-radius:12px;padding:60px 24px;text-align:center;color:#94a3b8;box-shadow:0 2px 6px rgba(17,24,39,.04);grid-column:1 / -1;}
.adforest-empty p{margin:0 0 16px;font-size:14px;}
.adforest-empty .adforest-empty__cta{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:10px 20px;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;transition:background .15s ease;}
.adforest-empty .adforest-empty__cta:hover{background:var(--adf-brand-hover);color:var(--adf-brand-text) !important;}

/* Pagination */
.adforest-pagination{display:flex;justify-content:center;gap:6px;margin-top:32px;grid-column:1 / -1;}
.adforest-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;background:#fff;border-radius:8px;color:#6b7280;text-decoration:none;font-weight:600;font-size:13px;box-shadow:0 1px 3px rgba(17,24,39,.04);transition:background .15s ease,color .15s ease;}
.adforest-pagination .page-numbers:hover{background:#f6f7fb;color:#1f2937;}
.adforest-pagination .page-numbers.current{background:var(--adf-brand);color:var(--adf-brand-text);}

/* Card removal animation (when remove_fav_ad AJAX hides the .holder-XX wrapper) */
.adforest-listing-card.is-removing{opacity:.4;pointer-events:none;transition:opacity .25s ease;}

/* Responsive */
@media (max-width:991px){
    .adforest-listings{grid-template-columns:repeat(2,1fr);}
}
@media (max-width:600px){
    .adforest-account-page{padding:20px 0 40px;}
    .adforest-listings{grid-template-columns:1fr;gap:14px;}
    .adforest-page-header h1{font-size:22px;}
    .adforest-account-nav{padding:6px;border-radius:14px;margin-bottom:18px;}
    .adforest-account-nav a{padding:8px 14px;font-size:13px;}
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above.
 * ========================================================== */
body.rtl .adforest-search-wrap .adforest-search-icon{left:auto;right:14px;}
body.rtl .adforest-search-wrap input[type="search"]{padding-left:14px;padding-right:38px;}
body.rtl .adforest-fav-toggle{right:auto;left:12px;}
body.rtl .adforest-listing-card__meta strong{margin-right:0;margin-left:4px;}
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
        <div class="adforest-page-header">
            <h1>
                <?php esc_html_e('Favorites', 'adforest'); ?>
                <?php if ($favorites_total > 0) : ?>
                    <span class="adforest-page-header__count"><?php echo esc_html(number_format_i18n($favorites_total)); ?></span>
                <?php endif; ?>
            </h1>
        </div>

        <!-- Toolbar (only when there's something to filter) -->
        <?php if ($favorites_total > 0 || $search_q !== '') : ?>
        <div class="adforest-toolbar">
            <form class="adforest-controls" method="get" action="<?php echo esc_url($current_page_url); ?>">
                <span class="adforest-controls__label">
                    <i class="fa fa-sort" aria-hidden="true"></i> <?php esc_html_e('Sort by:', 'adforest'); ?>
                </span>
                <select name="orderby" class="adforest-sort" onchange="this.form.submit()" aria-label="<?php esc_attr_e('Sort favorites', 'adforest'); ?>">
                    <option value="newest" <?php selected($orderby_filter, 'newest'); ?>><?php esc_html_e('Newest', 'adforest'); ?></option>
                    <option value="oldest" <?php selected($orderby_filter, 'oldest'); ?>><?php esc_html_e('Oldest', 'adforest'); ?></option>
                </select>
                <span class="adforest-search-wrap">
                    <i class="fa fa-search adforest-search-icon" aria-hidden="true"></i>
                    <input type="search" name="s" class="adforest-search"
                           placeholder="<?php esc_attr_e('Search', 'adforest'); ?>"
                           value="<?php echo esc_attr($search_q); ?>">
                </span>
            </form>
        </div>
        <?php endif; ?>

        <!-- Favorites grid -->
        <div class="adforest-listings">
            <?php if ($favorites_query->have_posts()) : ?>
                <?php while ($favorites_query->have_posts()) : $favorites_query->the_post();
                    $ad_id      = get_the_ID();
                    $details    = function_exists('get_ad_post_details') ? get_ad_post_details($ad_id) : array();
                    $img        = !empty($details['img']) ? $details['img'] : '';
                    $title      = !empty($details['ad_title']) ? $details['ad_title'] : get_the_title();
                    $permalink  = !empty($details['ad_link']) ? $details['ad_link'] : get_permalink($ad_id);

                    $cats     = get_the_terms($ad_id, 'ad_cats');
                    $cat_name = (is_array($cats) && !empty($cats) && !is_wp_error($cats)) ? $cats[0]->name : '';

                    $added_date = get_the_date(get_option('date_format'), $ad_id);

                    // Seller
                    $author_id     = (int) get_post_field('post_author', $ad_id);
                    $author_data   = get_userdata($author_id);
                    $author_name   = ($author_data && !empty($author_data->display_name)) ? $author_data->display_name : '';
                    $author_url    = $author_id ? get_author_posts_url($author_id) : '#';
                    $author_avatar = function_exists('adforest_get_user_dp') ? adforest_get_user_dp($author_id) : get_avatar_url($author_id);
                ?>
                    <article class="adforest-listing-card holder-<?php echo esc_attr($ad_id); ?>">
                        <a class="adforest-listing-card__media" href="<?php echo esc_url($permalink); ?>">
                            <?php if ($img) : ?>
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" />
                            <?php endif; ?>
                            <a href="javascript:void(0)" class="adforest-fav-toggle remove_fav_ad"
                               data-aaa-id="<?php echo esc_attr($ad_id); ?>"
                               data-nonce="<?php echo esc_attr($fav_remove_nonce); ?>"
                               title="<?php esc_attr_e('Remove from favorites', 'adforest'); ?>"
                               aria-label="<?php esc_attr_e('Remove from favorites', 'adforest'); ?>">
                                <i class="fa fa-heart" aria-hidden="true"></i>
                            </a>
                        </a>

                        <div class="adforest-listing-card__body">
                            <h3 class="adforest-listing-card__title">
                                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                            </h3>
                            <?php if ($cat_name) : ?>
                                <span class="adforest-listing-card__cat"><?php echo esc_html($cat_name); ?></span>
                            <?php endif; ?>
                            <div class="adforest-listing-card__meta">
                                <span><strong><?php esc_html_e('Added:', 'adforest'); ?></strong><?php echo esc_html($added_date); ?></span>
                            </div>
                            <div class="adforest-listing-card__price">
                                <?php
                                $price_html = function_exists('adforest_adPrice') ? adforest_adPrice($ad_id, 'negotiable', 'p') : '';
                                echo $price_html ? wp_kses_post($price_html) : esc_html__('No Price', 'adforest');
                                ?>
                            </div>
                        </div>

                        <div class="adforest-listing-card__footer">
                            <a class="adforest-seller" href="<?php echo esc_url($author_url); ?>">
                                <?php if ($author_avatar) : ?>
                                    <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>" />
                                <?php endif; ?>
                                <span class="name"><?php echo esc_html($author_name ?: __('Seller', 'adforest')); ?></span>
                            </a>
                            <a href="javascript:void(0)" class="adforest-remove-fav remove_fav_ad"
                               data-aaa-id="<?php echo esc_attr($ad_id); ?>"
                               data-nonce="<?php echo esc_attr($fav_remove_nonce); ?>">
                                <i class="fa fa-times" aria-hidden="true"></i>
                                <?php esc_html_e('Remove', 'adforest'); ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="adforest-empty">
                    <p>
                        <?php
                        if ($search_q !== '') {
                            esc_html_e('No favorites match your search.', 'adforest');
                        } else {
                            esc_html_e("You haven't favorited any ads yet.", 'adforest');
                        }
                        ?>
                    </p>
                    <?php if ($search_q === '') : ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="adforest-empty__cta">
                            <i class="fa fa-search" aria-hidden="true"></i>
                            <?php esc_html_e('Browse Listings', 'adforest'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>

            <?php if ($favorites_query->max_num_pages > 1) : ?>
                <div class="adforest-pagination">
                    <?php
                    $pagination_args = array_filter(array(
                        'orderby' => $orderby_filter !== 'newest' ? $orderby_filter : null,
                        's'       => $search_q !== ''            ? $search_q       : null,
                    ));
                    echo paginate_links(array(
                        'total'     => $favorites_query->max_num_pages,
                        'current'   => $paged,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'add_args'  => $pagination_args,
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php
/* Empty-state markup, kept hidden — JS shows it after the last
 * favorite is removed without a full page reload. Mirrors the
 * server-rendered empty state above (no-search variant). */
?>
<template id="adforest-favorites-empty-template">
    <div class="adforest-empty">
        <p><?php esc_html_e("You haven't favorited any ads yet.", 'adforest'); ?></p>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="adforest-empty__cta">
            <i class="fa fa-search" aria-hidden="true"></i>
            <?php esc_html_e('Browse Listings', 'adforest'); ?>
        </a>
    </div>
</template>

<script>
/* Remove-favorite handler — standalone copy of the handler from
 * dashboard-custom.js. The dashboard JS is only enqueued on the
 * dashboard page template, so on this modern Favorites page the
 * .remove_fav_ad clicks have no listener and the button silently
 * does nothing. Wires the same AJAX flow (action: sb_fav_remove_ad)
 * so the existing server endpoint and pipe-delimited response
 * format keep working unchanged. Also handles UI side effects:
 * fade-out the card, decrement the count badge, swap in the
 * empty-state when the last favorite is removed. */
jQuery(function ($) {
    var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

    function notify(type, msg) {
        if (typeof toastr !== 'undefined') {
            toastr[type](msg || '', '', {timeOut: 4000, closeButton: true, positionClass: 'toast-top-right'});
        } else if (type === 'error') {
            alert(msg || 'Error');
        }
    }

    function updateCountBadge(delta) {
        var $badge = $('.adforest-page-header__count').first();
        if (!$badge.length) return;
        var n = Math.max(0, (parseInt($badge.text().replace(/[^\d]/g, ''), 10) || 0) + delta);
        if (n === 0) {
            $badge.remove();
        } else {
            $badge.text(n);
        }
    }

    function maybeShowEmptyState() {
        var $grid = $('.adforest-listings');
        if (!$grid.length) return;
        if ($grid.find('article.adforest-listing-card').length === 0) {
            var tpl = document.getElementById('adforest-favorites-empty-template');
            if (tpl && 'content' in tpl) {
                $grid.html('').append(document.importNode(tpl.content, true));
            }
            // Hide the toolbar (search + sort) once there are zero favorites.
            $('.adforest-toolbar').hide();
        }
    }

    $(document).on('click', '.remove_fav_ad', function (e) {
        e.preventDefault();
        var $this = $(this);
        var id    = $this.attr('data-aaa-id');
        var nonce = $this.data('nonce') || $this.attr('data-nonce');

        if (!nonce || !id) {
            notify('error', '<?php echo esc_js(__('Missing required data. Please reload the page.', 'adforest')); ?>');
            return;
        }

        var $card = $this.closest('article.adforest-listing-card');
        if ($card.length) $card.addClass('is-removing');

        $.post(ajaxUrl, {
            action: 'sb_fav_remove_ad',
            ad_id: id,
            security: nonce
        }).done(function (response) {
            var parts = (response || '').split('|');
            var ok    = (parts[0] || '').trim() === '1';
            var msg   = parts[1] || '';
            if (ok) {
                var $row = $('.holder-' + id);
                if (!$row.length) $row = $card;
                $row.fadeOut(280, function () {
                    $(this).remove();
                    updateCountBadge(-1);
                    maybeShowEmptyState();
                });
                notify('success', msg);
            } else {
                if ($card.length) $card.removeClass('is-removing');
                notify('error', msg || '<?php echo esc_js(__('Could not remove. Try again.', 'adforest')); ?>');
            }
        }).fail(function () {
            if ($card.length) $card.removeClass('is-removing');
            notify('error', '<?php echo esc_js(__('Network error. Please try again.', 'adforest')); ?>');
        });
    });
});
</script>

<?php get_footer(); ?>
