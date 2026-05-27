<?php
/*
 * Template Name: AdForest - Awaiting Approval (Modern)
 *
 * Standalone "Awaiting Approval" page used by the Modern User Menu.
 * Shows the current user's ads with post_status = 'pending'. Independent
 * from /dashboard/ — no dashboard files modified. When the Modern User
 * Menu toggle is OFF, this template is unused.
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

// Pull theme button colors so the page tracks Theme Options live.
// Uses the PRIMARY button palette — same fields that drive .ad-post-btn.
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

$packages_page_id = isset($adforest_theme['sb_packages_page']) ? $adforest_theme['sb_packages_page'] : '';
$packages_url     = $packages_page_id ? get_permalink($packages_page_id) : '#';

$profile_page_id  = isset($adforest_theme['sb_profile_page'])  ? $adforest_theme['sb_profile_page']  : '';
$dash_url         = $profile_page_id ? trailingslashit(get_permalink($profile_page_id)) : home_url('/');

// Sibling modern pages (so sub-nav links to the standalone Modern page when one is set,
// otherwise falls back to the classic dashboard page_type for that section)
$modern_listings_page_id  = isset($adforest_theme['sb_modern_my_listings_page']) ? $adforest_theme['sb_modern_my_listings_page'] : '';
$modern_listings_url      = $modern_listings_page_id ? get_permalink($modern_listings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_ads', $dash_url) : '#');
$modern_favorites_page_id = isset($adforest_theme['sb_modern_favorites_page']) ? $adforest_theme['sb_modern_favorites_page'] : '';
$modern_favorites_url     = $modern_favorites_page_id ? get_permalink($modern_favorites_page_id) : ($dash_url ? add_query_arg('page_type', 'fav_ads', $dash_url) : '#');
$modern_settings_page_id  = isset($adforest_theme['sb_modern_settings_page']) ? $adforest_theme['sb_modern_settings_page'] : '';
$modern_settings_url      = $modern_settings_page_id ? get_permalink($modern_settings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_profile', $dash_url) : '#');
$modern_invoices_page_id  = isset($adforest_theme['sb_modern_invoices_page']) ? $adforest_theme['sb_modern_invoices_page'] : '';
$modern_invoices_url      = $modern_invoices_page_id ? get_permalink($modern_invoices_page_id) : ($dash_url ? add_query_arg('page_type', 'invoices', $dash_url) : '#');
$modern_messages_page_id  = isset($adforest_theme['sb_modern_messages_page']) ? $adforest_theme['sb_modern_messages_page'] : '';
$modern_messages_url      = $modern_messages_page_id ? get_permalink($modern_messages_page_id) : ($dash_url ? add_query_arg('page_type', 'msg', $dash_url) : '#');
$modern_packages_page_id  = isset($adforest_theme['sb_modern_my_packages_page']) ? $adforest_theme['sb_modern_my_packages_page'] : '';
$modern_packages_url      = $modern_packages_page_id ? get_permalink($modern_packages_page_id) : ($dash_url ? add_query_arg('page_type', 'my_packages', $dash_url) : '#');

$current_page_url = remove_query_arg(array('paged', 'orderby', 's'));

// Query — pending posts only
$query_args = array(
    'post_type'      => 'ad_post',
    'author'         => $user_id,
    'posts_per_page' => $posts_per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => ($orderby_filter === 'oldest') ? 'ASC' : 'DESC',
    'post_status'    => array('pending'),
);

if ($search_q !== '') {
    $query_args['s'] = $search_q;
}

$pending_query = new WP_Query($query_args);

// Total pending count (for the badge next to the page title)
$pending_total = (int) $pending_query->found_posts;

// Horizontal account nav — same items, but "Awaiting Approval" is the active tab here
$account_nav = array(
    array('icon' => 'fa fa-plus-circle',     'label' => __('Add New',           'adforest'), 'url' => $modern_post_ad_url, 'active' => false),
    array('icon' => 'fa fa-clipboard-check', 'label' => __('Awaiting Approval', 'adforest'), 'url' => get_permalink(), 'active' => true),
    array('icon' => 'fa fa-receipt',         'label' => __('Invoices',          'adforest'), 'url' => $modern_invoices_url, 'active' => false),
    array('icon' => 'fa fa-list',            'label' => __('My Listings',       'adforest'), 'url' => $modern_listings_url, 'active' => false),
    array('icon' => 'fa fa-heart',           'label' => __('Favorites',         'adforest'), 'url' => $modern_favorites_url, 'active' => false),
    array('icon' => 'fa fa-envelope',        'label' => __('Messages',          'adforest'), 'url' => $modern_messages_url, 'active' => false),
    array('icon' => 'fa fa-box',             'label' => __('My Packages',       'adforest'), 'url' => $modern_packages_url, 'active' => false),
    array('icon' => 'fa fa-cog',             'label' => __('Profile Settings',  'adforest'), 'url' => $modern_settings_url, 'active' => false),
);
?>

<style id="adforest-awaiting-approval-css">
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
.adforest-page-header__count{display:inline-flex;align-items:center;justify-content:center;background:rgba(245,158,11,.12);color:#b45309;font-size:14px;font-weight:700;padding:4px 14px;border-radius:999px;line-height:1.4;}

/* Toolbar (sort + search) */
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

/* Listing cards (same pattern as My Listings) */
.adforest-listings{display:flex;flex-direction:column;gap:16px;}
.adforest-listing-card{display:grid;grid-template-columns:280px 1fr 220px;background:#fff;border-radius:12px;box-shadow:0 2px 6px rgba(17,24,39,.04);overflow:hidden;transition:box-shadow .2s ease,transform .2s ease;}
.adforest-listing-card:hover{box-shadow:0 10px 26px rgba(17,24,39,.07);transform:translateY(-2px);}
.adforest-listing-card__media{position:relative;display:block;background:#f6f7fb;overflow:hidden;min-height:220px;}
.adforest-listing-card__media img{width:100%;height:100%;object-fit:cover;display:block;position:absolute;inset:0;transition:transform .35s ease;}
.adforest-listing-card:hover .adforest-listing-card__media img{transform:scale(1.04);}
.adforest-listing-card__status{position:absolute;top:14px;left:14px;padding:6px 14px;border-radius:6px;font-size:12px;font-weight:600;color:#fff;letter-spacing:.02em;z-index:1;background:#f59e0b;}

.adforest-listing-card__body{padding:24px;display:flex;flex-direction:column;gap:10px;min-width:0;}
.adforest-listing-card__title{margin:0;font-size:20px;font-weight:700;line-height:1.3;}
.adforest-listing-card__title a{color:#1f2937;text-decoration:none;transition:color .15s ease;}
.adforest-listing-card__title a:hover{color:var(--adf-brand);}
.adforest-listing-card__cat{display:inline-flex;align-self:flex-start;background:#f3f4f6;color:#6b7280;padding:6px 14px;border-radius:6px;font-size:13px;font-weight:500;margin-top:4px;}
.adforest-listing-card__meta{font-size:13px;color:#6b7280;display:flex;flex-wrap:wrap;gap:18px;margin-top:8px;}
.adforest-listing-card__meta strong{color:var(--adf-brand);font-weight:600;margin-right:6px;}
.adforest-listing-card__price{font-size:22px;font-weight:800;color:#1f2937;letter-spacing:-.01em;margin-top:6px;}

.adforest-listing-card__actions{padding:24px;display:flex;flex-direction:column;justify-content:center;gap:14px;border-left:1px solid #eef0f4;}
.adforest-action-row{display:flex;flex-direction:column;gap:10px;}
.adforest-action{display:inline-flex;align-items:center;gap:8px;color:#6b7280;text-decoration:none;font-size:14px;font-weight:500;transition:color .15s ease;cursor:pointer;}
.adforest-action:hover{color:var(--adf-brand);}
.adforest-action--delete:hover{color:#ef4444;}
.adforest-action i{font-size:15px;}

/* Awaiting Approval — informational notice in actions column */
.adforest-pending-notice{font-size:12px;color:#b45309;background:rgba(245,158,11,.10);border:1px solid rgba(245,158,11,.25);padding:10px 12px;border-radius:8px;display:flex;align-items:flex-start;gap:8px;line-height:1.4;}
.adforest-pending-notice i{font-size:14px;margin-top:1px;flex-shrink:0;}

/* Empty state */
.adforest-empty{background:#fff;border-radius:12px;padding:60px 24px;text-align:center;color:#94a3b8;box-shadow:0 2px 6px rgba(17,24,39,.04);}
.adforest-empty p{margin:0;font-size:14px;}

/* Pagination */
.adforest-pagination{display:flex;justify-content:center;gap:6px;margin-top:32px;}
.adforest-pagination .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;background:#fff;border-radius:8px;color:#6b7280;text-decoration:none;font-weight:600;font-size:13px;box-shadow:0 1px 3px rgba(17,24,39,.04);transition:background .15s ease,color .15s ease;}
.adforest-pagination .page-numbers:hover{background:#f6f7fb;color:#1f2937;}
.adforest-pagination .page-numbers.current{background:var(--adf-brand);color:var(--adf-brand-text);}

/* Responsive */
@media (max-width:991px){
    .adforest-listing-card{grid-template-columns:240px 1fr;}
    .adforest-listing-card__actions{grid-column:1 / -1;border-left:0;border-top:1px solid #eef0f4;flex-direction:row;flex-wrap:wrap;justify-content:flex-start;align-items:center;padding:18px 24px;}
    .adforest-action-row{flex-direction:row;gap:18px;}
}
@media (max-width:600px){
    .adforest-account-page{padding:20px 0 40px;}
    .adforest-listing-card{grid-template-columns:1fr;}
    .adforest-listing-card__media{min-height:200px;aspect-ratio:16/10;}
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
body.rtl .adforest-listing-card__status{left:auto;right:14px;}
body.rtl .adforest-listing-card__meta strong{margin-right:0;margin-left:6px;}
body.rtl .adforest-listing-card__actions{border-left:0;border-right:1px solid #eef0f4;}
@media (max-width:991px){
    body.rtl .adforest-listing-card__actions{border-right:0;border-top:1px solid #eef0f4;}
}
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
                <?php esc_html_e('Awaiting Approval', 'adforest'); ?>
                <?php if ($pending_total > 0) : ?>
                    <span class="adforest-page-header__count"><?php echo esc_html(number_format_i18n($pending_total)); ?></span>
                <?php endif; ?>
            </h1>
        </div>

        <!-- Toolbar -->
        <?php if ($pending_total > 0 || $search_q !== '') : ?>
        <div class="adforest-toolbar">
            <form class="adforest-controls" method="get" action="<?php echo esc_url($current_page_url); ?>">
                <span class="adforest-controls__label">
                    <i class="fa fa-sort" aria-hidden="true"></i> <?php esc_html_e('Sort by:', 'adforest'); ?>
                </span>
                <select name="orderby" class="adforest-sort" onchange="this.form.submit()" aria-label="<?php esc_attr_e('Sort listings', 'adforest'); ?>">
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

        <!-- Listings -->
        <div class="adforest-listings">
            <?php if ($pending_query->have_posts()) : ?>
                <?php while ($pending_query->have_posts()) : $pending_query->the_post();
                    $ad_id      = get_the_ID();
                    $details    = function_exists('get_ad_post_details') ? get_ad_post_details($ad_id) : array();
                    $img        = !empty($details['img']) ? $details['img'] : '';
                    $title      = !empty($details['ad_title']) ? $details['ad_title'] : get_the_title();
                    $permalink  = !empty($details['ad_link']) ? $details['ad_link'] : get_permalink($ad_id);

                    $cats     = get_the_terms($ad_id, 'ad_cats');
                    $cat_name = (is_array($cats) && !empty($cats) && !is_wp_error($cats)) ? $cats[0]->name : '';

                    $added_date = get_the_date(get_option('date_format'), $ad_id);

                    $edit_url = $post_ad_page_id
                        ? (function_exists('adforest_set_url_param')
                            ? adforest_set_url_param(get_permalink($post_ad_page_id), 'id', $ad_id)
                            : add_query_arg('id', $ad_id, get_permalink($post_ad_page_id)))
                        : '#';
                ?>
                    <article class="adforest-listing-card holder-<?php echo esc_attr($ad_id); ?>">
                        <a class="adforest-listing-card__media" href="<?php echo esc_url($permalink); ?>">
                            <?php if ($img) : ?>
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" />
                            <?php endif; ?>
                            <span class="adforest-listing-card__status"><?php esc_html_e('Pending', 'adforest'); ?></span>
                        </a>

                        <div class="adforest-listing-card__body">
                            <h3 class="adforest-listing-card__title">
                                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                            </h3>
                            <?php if ($cat_name) : ?>
                                <span class="adforest-listing-card__cat"><?php echo esc_html($cat_name); ?></span>
                            <?php endif; ?>
                            <div class="adforest-listing-card__meta">
                                <span><strong><?php esc_html_e('Submitted:', 'adforest'); ?></strong><?php echo esc_html($added_date); ?></span>
                            </div>
                            <div class="adforest-listing-card__price">
                                <?php
                                $price_html = function_exists('adforest_adPrice') ? adforest_adPrice($ad_id, 'negotiable', 'p') : '';
                                echo $price_html ? wp_kses_post($price_html) : esc_html__('No Price', 'adforest');
                                ?>
                            </div>
                        </div>

                        <div class="adforest-listing-card__actions">
                            <div class="adforest-pending-notice">
                                <i class="fa fa-info-circle" aria-hidden="true"></i>
                                <?php esc_html_e('Waiting for admin review.', 'adforest'); ?>
                            </div>
                            <div class="adforest-action-row">
                                <a href="<?php echo esc_url($edit_url); ?>" class="adforest-action edit">
                                    <i class="fa fa-edit" aria-hidden="true"></i> <?php esc_html_e('Edit', 'adforest'); ?>
                                </a>
                                <a href="javascript:void(0)" class="adforest-action adforest-action--delete remove_ad"
                                   data-adid="<?php echo esc_attr($ad_id); ?>">
                                    <i class="fa fa-trash" aria-hidden="true"></i> <?php esc_html_e('Delete', 'adforest'); ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="adforest-empty">
                    <p>
                        <?php
                        if ($search_q !== '') {
                            esc_html_e('No pending ads match your search.', 'adforest');
                        } else {
                            esc_html_e('You have no ads awaiting approval.', 'adforest');
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>

        <?php if ($pending_query->max_num_pages > 1) : ?>
            <div class="adforest-pagination">
                <?php
                $pagination_args = array_filter(array(
                    'orderby' => $orderby_filter !== 'newest' ? $orderby_filter : null,
                    's'       => $search_q !== ''            ? $search_q       : null,
                ));
                echo paginate_links(array(
                    'total'     => $pending_query->max_num_pages,
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

<?php get_footer(); ?>
