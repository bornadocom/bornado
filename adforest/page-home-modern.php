<?php
/*
 * Template Name: AdForest - Home (Modern)
 *
 * Standalone modern home page. Uses its own header / footer
 * template-parts (header-home-modern.php / footer-home-modern.php)
 * loaded via get_header('home-modern') / get_footer('home-modern')
 * so admins can keep their existing classic header & footer for
 * the rest of the site.
 *
 * Sections (top → bottom):
 *   1. Hero with brand badge, headline, and 3-field search
 *   2. Category cards (from `ad_cats` taxonomy)
 *   3. Featured Advertisements with tab filter (from `ad_post`)
 *   4. Top Locations (from `ad_country` taxonomy)
 *   5. Pricing Plans (from `adforest_classified_pkgs` WC products)
 *
 * The trust strip + footer + copyright live in
 * footer-home-modern.php so they wrap every section consistently.
 *
 * @package Adforest
 */

global $adforest_theme;

get_header('home-modern');

// Brand color → CSS vars (shared with header/footer scope)
$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
$_rgb_parts      = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb   = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

// Search results URL (uses WP default `?s=...` against ad_post type)
$search_action_url = home_url('/');

// Packages page link for pricing CTA fallback
$packages_page_id = isset($adforest_theme['sb_packages_page']) ? $adforest_theme['sb_packages_page'] : '';
$packages_url     = $packages_page_id ? get_permalink($packages_page_id) : '#';

// Single-ad page slug (for permalinks rendered server-side)
// already handled via get_permalink on each ad

// Currency symbol (best-effort via WC, falls back to $)
$currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$';

/* -----------------------------------------------------------------
 * Category cards — top 6 most-used categories from ad_cats
 * --------------------------------------------------------------- */
$home_categories = get_terms(array(
    'taxonomy'   => 'ad_cats',
    'hide_empty' => false,
    'number'     => 6,
    'orderby'    => 'count',
    'order'      => 'DESC',
    'parent'     => 0,
));
if (is_wp_error($home_categories)) { $home_categories = array(); }
// Soft pastel palette rotated across cards
$cat_palettes = array(
    array('bg' => '#fff1cc', 'fg' => '#b07a00'),
    array('bg' => '#dbe6ff', 'fg' => '#1d4ed8'),
    array('bg' => '#d2f3e8', 'fg' => '#0f766e'),
    array('bg' => '#ffe2cc', 'fg' => '#b45309'),
    array('bg' => '#ffd9e6', 'fg' => '#be185d'),
    array('bg' => '#dff3e0', 'fg' => '#15803d'),
);

/* -----------------------------------------------------------------
 * Featured ads — most recent ads flagged as featured
 * --------------------------------------------------------------- */
$featured_ads = new WP_Query(array(
    'post_type'      => 'ad_post',
    'posts_per_page' => 8,
    'post_status'    => 'publish',
    'meta_query'     => array(
        array(
            'key'     => '_adforest_is_feature',
            'value'   => '1',
            'compare' => '=',
        ),
    ),
    'no_found_rows'  => true,
));

// Tab categories — top 7 most-used (used to filter the grid via JS)
$tab_categories = get_terms(array(
    'taxonomy'   => 'ad_cats',
    'hide_empty' => false,
    'number'     => 7,
    'orderby'    => 'count',
    'order'      => 'DESC',
    'parent'     => 0,
));
if (is_wp_error($tab_categories)) { $tab_categories = array(); }

/* -----------------------------------------------------------------
 * Top locations — 4 cities (top-level countries / locations)
 * --------------------------------------------------------------- */
$top_locations = get_terms(array(
    'taxonomy'   => 'ad_country',
    'hide_empty' => false,
    'number'     => 4,
    'orderby'    => 'count',
    'order'      => 'DESC',
));
if (is_wp_error($top_locations)) { $top_locations = array(); }

/* -----------------------------------------------------------------
 * Pricing plans — AdForest's WooCommerce package products
 * --------------------------------------------------------------- */
$pricing_plans = array();
if (function_exists('wc_get_products')) {
    $plan_products = wc_get_products(array(
        'type'   => 'adforest_classified_pkgs',
        'status' => 'publish',
        'limit'  => 3,
    ));
    if (!empty($plan_products) && is_array($plan_products)) {
        $pricing_plans = $plan_products;
    }
}
?>

<style id="adf-home-modern-css">
.adf-hm{
    --hm-brand:<?php echo esc_attr($theme_btn_color); ?>;
    --hm-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;
    --hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;
    --hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;
    --hm-ink-1:#0f172a;
    --hm-ink-2:#1f2937;
    --hm-ink-3:#475569;
    --hm-ink-4:#64748b;
    --hm-mute:#94a3b8;
    --hm-line:#eef1f5;
    --hm-bg:#fafbfd;
    --hm-radius:14px;
    --hm-radius-lg:18px;
    --hm-shadow-sm:0 0 6px rgba(15,23,42,.04);
    --hm-shadow:0 0 12px rgba(15,23,42,.06);
    background:#fff;color:var(--hm-ink-2);
}
.adf-hm *{box-sizing:border-box;}
.adf-hm__wrap{max-width:1200px;margin:0 auto;padding:0 24px;}

/* ===== Hero ============================================ */
.adf-hm-hero{position:relative;padding:72px 0 56px;text-align:center;background:linear-gradient(180deg,#fafdfb 0%,#fff 60%);overflow:hidden;}
.adf-hm-hero::before,
.adf-hm-hero::after{content:"";position:absolute;width:520px;height:520px;background:radial-gradient(circle, rgba(var(--hm-brand-rgb),.05) 0%, transparent 70%);pointer-events:none;z-index:0;}
.adf-hm-hero::before{top:-180px;left:-160px;}
.adf-hm-hero::after{bottom:-220px;right:-180px;}
.adf-hm-hero__inner{position:relative;z-index:1;max-width:760px;margin:0 auto;padding:0 24px;}
.adf-hm-hero__badge{display:inline-flex;align-items:center;gap:6px;background:#fef3c7;color:#92400e;border-radius:999px;padding:6px 14px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:14px;}
.adf-hm-hero__welcome{display:block;font-size:13px;letter-spacing:.32em;text-transform:uppercase;color:var(--hm-mute);font-weight:600;margin-bottom:10px;}
.adf-hm-hero h1{font-size:54px;font-weight:800;color:var(--hm-ink-1);margin:0 0 16px;letter-spacing:-.025em;line-height:1.05;}
.adf-hm-hero h1 em{font-style:normal;color:#f59e0b;}
.adf-hm-hero__sub{margin:0 auto 30px;max-width:560px;color:var(--hm-ink-4);font-size:15px;line-height:1.6;}

.adf-hm-search{background:#fff;border:1px solid var(--hm-line);border-radius:14px;box-shadow:var(--hm-shadow);display:flex;align-items:center;gap:0;padding:6px;max-width:780px;margin:0 auto;}
.adf-hm-search__field{flex:1;display:flex;align-items:center;gap:10px;padding:8px 14px;border-right:1px solid var(--hm-line);min-width:0;}
.adf-hm-search__field:last-of-type{border-right:0;}
.adf-hm-search__field i{color:var(--hm-mute);font-size:14px;flex-shrink:0;}
.adf-hm-search__field input,
.adf-hm-search__field select{flex:1;min-width:0;border:0;background:transparent;outline:none;font-size:14px;color:var(--hm-ink-1);font-family:inherit;appearance:none;-webkit-appearance:none;padding:8px 0;}
.adf-hm-search__field input::placeholder{color:var(--hm-mute);}
.adf-hm-search__field select{cursor:pointer;}
.adf-hm-search__btn{background:var(--hm-brand);color:var(--hm-brand-text);border:0;border-radius:10px;padding:0 22px;height:46px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;flex-shrink:0;transition:background .15s ease,transform .12s ease;box-shadow:0 0 6px rgba(var(--hm-brand-rgb),.25);font-family:inherit;}
.adf-hm-search__btn:hover{background:var(--hm-brand-hover);transform:translateY(-1px);}

/* ===== Categories ======================================= */
.adf-hm-cats{padding:48px 0 24px;background:#fff;}
.adf-hm-cats__grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:18px;}
.adf-hm-cat{display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:var(--hm-radius);padding:24px 14px 18px;text-decoration:none;text-align:center;transition:transform .18s ease,box-shadow .18s ease;color:inherit;min-height:160px;gap:6px;}
.adf-hm-cat:hover{transform:translateY(-3px);box-shadow:var(--hm-shadow);}
.adf-hm-cat__icon{width:62px;height:62px;border-radius:14px;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:inherit;margin-bottom:6px;overflow:hidden;}
.adf-hm-cat__icon img{width:38px;height:38px;object-fit:contain;}
.adf-hm-cat__icon i{font-size:24px;}
.adf-hm-cat__name{font-weight:700;color:var(--hm-ink-1);font-size:15px;}
.adf-hm-cat__count{font-size:12px;color:var(--hm-ink-4);font-weight:500;}

/* ===== Section heading ================================== */
.adf-hm-sec-head{text-align:center;margin:0 0 30px;}
.adf-hm-sec-head h2{font-size:28px;font-weight:800;color:var(--hm-ink-1);margin:0 0 6px;letter-spacing:-.02em;display:inline-flex;align-items:center;gap:8px;}
.adf-hm-sec-head h2 i{color:var(--hm-brand);font-size:22px;}
.adf-hm-sec-head p{margin:0 auto;max-width:520px;color:var(--hm-ink-4);font-size:14px;line-height:1.55;}

/* ===== Featured ads ==================================== */
.adf-hm-featured{padding:48px 0;background:#fff;}
.adf-hm-featured__tabs{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;border-bottom:1px solid var(--hm-line);margin-bottom:24px;padding-bottom:2px;}
.adf-hm-featured__tablist{display:flex;align-items:center;gap:4px;flex-wrap:wrap;list-style:none;margin:0;padding:0;}
.adf-hm-featured__tablist li{list-style:none;}
.adf-hm-featured__tab{background:transparent;border:0;color:var(--hm-ink-3);font-size:14px;font-weight:600;padding:10px 14px 12px;cursor:pointer;position:relative;font-family:inherit;border-radius:8px 8px 0 0;}
.adf-hm-featured__tab:hover{color:var(--hm-ink-1);}
.adf-hm-featured__tab.is-active{color:var(--hm-brand);}
.adf-hm-featured__tab.is-active::after{content:"";position:absolute;left:8px;right:8px;bottom:-1px;height:3px;background:var(--hm-brand);border-radius:3px 3px 0 0;}
.adf-hm-featured__viewall{color:var(--hm-brand);font-weight:700;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.adf-hm-featured__viewall:hover{color:var(--hm-brand-hover);}

.adf-hm-featured__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;}
.adf-hm-card{background:#fff;border:1px solid var(--hm-line);border-radius:var(--hm-radius);overflow:hidden;text-decoration:none;color:inherit;transition:transform .18s ease,box-shadow .18s ease;display:flex;flex-direction:column;}
.adf-hm-card:hover{transform:translateY(-3px);box-shadow:var(--hm-shadow);}
.adf-hm-card__media{position:relative;aspect-ratio:4/3;background:#f5f6fb;overflow:hidden;}
.adf-hm-card__media img{width:100%;height:100%;object-fit:cover;display:block;}
.adf-hm-card__badge{position:absolute;top:10px;left:10px;background:var(--hm-brand);color:var(--hm-brand-text);border-radius:6px;padding:4px 10px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;}
.adf-hm-card__fav{position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.92);color:var(--hm-ink-3);display:inline-flex;align-items:center;justify-content:center;border:0;cursor:pointer;font-size:13px;}
.adf-hm-card__fav:hover{color:var(--hm-brand);}
.adf-hm-card__body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:8px;flex:1;}
.adf-hm-card__title{font-size:15px;font-weight:700;color:var(--hm-ink-1);margin:0;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.adf-hm-card__loc{display:flex;align-items:center;justify-content:space-between;font-size:12.5px;color:var(--hm-ink-4);gap:8px;}
.adf-hm-card__loc span:first-child{display:inline-flex;align-items:center;gap:5px;}
.adf-hm-card__loc i{color:var(--hm-mute);font-size:11px;}
.adf-hm-card__discount{color:var(--hm-brand);font-weight:700;font-size:12px;}
.adf-hm-card__foot{margin-top:auto;display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--hm-line);}
.adf-hm-card__price{font-size:17px;font-weight:800;color:var(--hm-ink-1);}
.adf-hm-card__condition{background:#dcfce7;color:#166534;font-size:11px;font-weight:700;padding:3px 10px;border-radius:6px;text-transform:capitalize;}
.adf-hm-card__condition.is-used{background:#fef3c7;color:#92400e;}

/* Carousel arrows under the featured grid */
.adf-hm-featured__nav{display:flex;justify-content:center;align-items:center;gap:10px;margin-top:24px;}
.adf-hm-featured__nav button{width:38px;height:38px;border-radius:50%;border:1px solid var(--hm-line);background:#fff;color:var(--hm-ink-3);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:13px;transition:border-color .15s ease,color .15s ease,background .15s ease;}
.adf-hm-featured__nav button:hover{border-color:var(--hm-brand);color:var(--hm-brand);}

/* ===== Top locations ==================================== */
.adf-hm-locs{padding:36px 0 48px;background:#fff;}
.adf-hm-locs__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;}
.adf-hm-loc{position:relative;display:block;border-radius:var(--hm-radius);overflow:hidden;text-decoration:none;color:#fff;aspect-ratio:5/4;background:#1f2937;transition:transform .18s ease,box-shadow .18s ease;}
.adf-hm-loc:hover{transform:translateY(-3px);box-shadow:var(--hm-shadow);color:#fff;}
.adf-hm-loc img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s ease;}
.adf-hm-loc:hover img{transform:scale(1.05);}
.adf-hm-loc::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(15,23,42,0) 40%,rgba(15,23,42,.75) 100%);}
.adf-hm-loc__pin{position:absolute;top:12px;left:12px;width:30px;height:30px;border-radius:50%;background:var(--hm-brand);color:var(--hm-brand-text);display:inline-flex;align-items:center;justify-content:center;font-size:13px;z-index:2;}
.adf-hm-loc__meta{position:absolute;left:14px;right:14px;bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:8px;z-index:2;}
.adf-hm-loc__name{font-weight:700;font-size:16px;letter-spacing:-.005em;}
.adf-hm-loc__count{background:rgba(255,255,255,.18);backdrop-filter:blur(6px);border-radius:6px;padding:3px 10px;font-size:12px;font-weight:600;}

/* ===== Pricing plans ==================================== */
.adf-hm-plans{padding:48px 0 56px;background:#fafbfd;border-top:1px solid var(--hm-line);}
.adf-hm-plans__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;}
.adf-hm-plan{position:relative;background:#fff;border:1px solid var(--hm-line);border-radius:var(--hm-radius-lg);padding:30px 26px;display:flex;flex-direction:column;gap:14px;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;}
.adf-hm-plan:hover{transform:translateY(-3px);box-shadow:var(--hm-shadow);}
.adf-hm-plan__icon{width:54px;height:54px;border-radius:14px;background:rgba(var(--hm-brand-rgb),.10);color:var(--hm-brand);display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:4px;}
.adf-hm-plan__name{font-size:18px;font-weight:800;color:var(--hm-ink-1);margin:0;letter-spacing:-.01em;}
.adf-hm-plan__price{display:flex;align-items:baseline;gap:6px;}
.adf-hm-plan__price strong{font-size:32px;font-weight:800;color:var(--hm-ink-1);letter-spacing:-.02em;line-height:1;}
.adf-hm-plan__price span{font-size:13px;color:var(--hm-ink-4);font-weight:600;}
.adf-hm-plan__feats{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;font-size:13.5px;color:var(--hm-ink-3);}
.adf-hm-plan__feats li{display:flex;align-items:center;gap:10px;}
.adf-hm-plan__feats li i{color:#10b981;font-size:11px;width:18px;height:18px;border-radius:50%;background:rgba(16,185,129,.10);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;}
.adf-hm-plan__feats li.is-off{color:var(--hm-mute);}
.adf-hm-plan__feats li.is-off i{color:#ef4444;background:rgba(239,68,68,.10);}
.adf-hm-plan__cta{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1.5px solid var(--hm-line);background:#fff;color:var(--hm-ink-2);border-radius:10px;padding:11px 18px;font-size:14px;font-weight:700;text-decoration:none;transition:all .15s ease;margin-top:6px;}
.adf-hm-plan__cta:hover{background:var(--hm-brand);color:var(--hm-brand-text);border-color:var(--hm-brand);}
/* Highlight middle/popular plan */
.adf-hm-plan.is-popular{background:linear-gradient(180deg,#fef9c3 0%,#fef3c7 100%);border-color:#fde68a;}
.adf-hm-plan.is-popular::before{content:"<?php echo esc_attr__('Popular', 'adforest'); ?>";position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#f59e0b;color:#fff;border-radius:999px;padding:5px 16px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;}
.adf-hm-plan.is-popular .adf-hm-plan__cta{background:#f59e0b;color:#fff;border-color:#f59e0b;}
.adf-hm-plan.is-popular .adf-hm-plan__cta:hover{background:#d97706;border-color:#d97706;}

/* ===== Responsive ====================================== */
@media (max-width:1099px){
    .adf-hm-cats__grid{grid-template-columns:repeat(3,minmax(0,1fr));}
    .adf-hm-featured__grid{grid-template-columns:repeat(2,minmax(0,1fr));}
    .adf-hm-locs__grid{grid-template-columns:repeat(2,minmax(0,1fr));}
    .adf-hm-plans__grid{grid-template-columns:1fr;}
    .adf-hm-hero h1{font-size:42px;}
}
@media (max-width:768px){
    .adf-hm-hero{padding:50px 0 36px;}
    .adf-hm-hero h1{font-size:34px;}
    .adf-hm-search{flex-direction:column;align-items:stretch;padding:10px;gap:6px;}
    .adf-hm-search__field{border-right:0;border-bottom:1px solid var(--hm-line);padding:10px 12px;}
    .adf-hm-search__field:nth-last-of-type(1){border-bottom:0;}
    .adf-hm-search__btn{width:100%;justify-content:center;height:50px;}
    .adf-hm-cats__grid{grid-template-columns:repeat(2,minmax(0,1fr));}
    .adf-hm-featured__grid{grid-template-columns:1fr;}
    .adf-hm-locs__grid{grid-template-columns:1fr;}
}
@media (max-width:480px){
    .adf-hm-hero h1{font-size:28px;}
    .adf-hm-sec-head h2{font-size:22px;}
    .adf-hm-cat{padding:18px 10px 14px;min-height:140px;}
    .adf-hm-cat__icon{width:52px;height:52px;}
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above.
 * ========================================================== */
body.rtl .adf-hm-hero::before{left:auto;right:-160px;}
body.rtl .adf-hm-hero::after{right:auto;left:-180px;}
body.rtl .adf-hm-search__field{border-right:0;border-left:1px solid var(--hm-line);}
body.rtl .adf-hm-search__field:last-of-type{border-left:0;}
body.rtl .adf-hm-featured__tab.is-active::after{left:8px;right:8px;}
body.rtl .adf-hm-card__badge{left:auto;right:10px;}
body.rtl .adf-hm-card__fav{right:auto;left:10px;}
body.rtl .adf-hm-loc__pin{left:auto;right:12px;}
body.rtl .adf-hm-loc__meta{left:14px;right:14px;}
body.rtl .adf-hm-plan.is-popular::before{left:50%;right:auto;transform:translateX(50%);}
@media (max-width:768px){
    body.rtl .adf-hm-search__field{border-left:0;border-bottom:1px solid var(--hm-line);}
}
</style>

<div class="adf-hm">

    <!-- ===== Hero ===== -->
    <section class="adf-hm-hero">
        <div class="adf-hm-hero__inner">
            <span class="adf-hm-hero__badge">★ <?php esc_html_e('100% Free Ads Forever', 'adforest'); ?></span>
            <span class="adf-hm-hero__welcome"><?php esc_html_e('Welcome to', 'adforest'); ?></span>
            <h1>
                <?php
                printf(
                    /* translators: %s: highlighted word styled as accent */
                    esc_html__('Visit, Classified, Web & %s', 'adforest'),
                    '<em>' . esc_html__('More.', 'adforest') . '</em>'
                );
                ?>
            </h1>
            <p class="adf-hm-hero__sub"><?php esc_html_e('AdForest is the most innovative, user-friendly and reliable classified ads WordPress theme from the trusted author.', 'adforest'); ?></p>

            <form class="adf-hm-search" method="get" action="<?php echo esc_url($search_action_url); ?>" role="search">
                <input type="hidden" name="post_type" value="ad_post">
                <div class="adf-hm-search__field">
                    <i class="fa fa-search"></i>
                    <input type="search" name="s" placeholder="<?php esc_attr_e('What are you looking for?', 'adforest'); ?>" value="<?php echo esc_attr(get_search_query()); ?>">
                </div>
                <div class="adf-hm-search__field">
                    <i class="fa fa-th-large"></i>
                    <select name="ad_cats">
                        <option value=""><?php esc_html_e('Select Category', 'adforest'); ?></option>
                        <?php foreach ($home_categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adf-hm-search__field">
                    <i class="fa fa-map-marker-alt"></i>
                    <select name="ad_country">
                        <option value=""><?php esc_html_e('Select Location', 'adforest'); ?></option>
                        <?php foreach ($top_locations as $loc) : ?>
                            <option value="<?php echo esc_attr($loc->slug); ?>"><?php echo esc_html($loc->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="adf-hm-search__btn">
                    <i class="fa fa-search"></i> <?php esc_html_e('Search Now', 'adforest'); ?>
                </button>
            </form>
        </div>
    </section>

    <!-- ===== Categories ===== -->
    <?php if (!empty($home_categories)) : ?>
    <section class="adf-hm-cats">
        <div class="adf-hm__wrap">
            <div class="adf-hm-cats__grid">
                <?php foreach ($home_categories as $i => $cat) :
                    $palette  = $cat_palettes[$i % count($cat_palettes)];
                    $cat_link = get_term_link($cat);
                    if (is_wp_error($cat_link)) { $cat_link = '#'; }
                    $icon_id   = get_term_meta($cat->term_id, 'taxonomy_image', true);
                    $icon_url  = $icon_id ? wp_get_attachment_image_url((int) $icon_id, 'thumbnail') : '';
                    $ad_count  = (int) $cat->count;
                    ?>
                    <a class="adf-hm-cat" href="<?php echo esc_url($cat_link); ?>" style="background:<?php echo esc_attr($palette['bg']); ?>;color:<?php echo esc_attr($palette['fg']); ?>;">
                        <span class="adf-hm-cat__icon" style="color:<?php echo esc_attr($palette['fg']); ?>;">
                            <?php if ($icon_url) : ?>
                                <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($cat->name); ?>">
                            <?php else : ?>
                                <i class="fa fa-folder-open"></i>
                            <?php endif; ?>
                        </span>
                        <span class="adf-hm-cat__name"><?php echo esc_html($cat->name); ?></span>
                        <span class="adf-hm-cat__count"><?php
                            printf(
                                esc_html(_n('%s Ad', '%s Ads', $ad_count, 'adforest')),
                                esc_html(number_format_i18n($ad_count))
                            );
                        ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== Featured ads ===== -->
    <?php if ($featured_ads->have_posts()) : ?>
    <section class="adf-hm-featured">
        <div class="adf-hm__wrap">
            <div class="adf-hm-sec-head">
                <h2><i class="fa fa-fire"></i> <?php esc_html_e('Featured Advertisings', 'adforest'); ?></h2>
                <p><?php esc_html_e('Discover our featured listings and find the best deals on a wide range of products and services.', 'adforest'); ?></p>
            </div>

            <div class="adf-hm-featured__tabs">
                <ul class="adf-hm-featured__tablist" role="tablist">
                    <li><button type="button" class="adf-hm-featured__tab is-active" data-filter="all"><?php esc_html_e('All', 'adforest'); ?></button></li>
                    <?php foreach ($tab_categories as $tab_cat) : ?>
                        <li><button type="button" class="adf-hm-featured__tab" data-filter="cat-<?php echo esc_attr($tab_cat->term_id); ?>"><?php echo esc_html($tab_cat->name); ?></button></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo esc_url(home_url('/?post_type=ad_post')); ?>" class="adf-hm-featured__viewall">
                    <?php esc_html_e('View All', 'adforest'); ?> <i class="fa fa-arrow-right"></i>
                </a>
            </div>

            <div class="adf-hm-featured__grid">
                <?php while ($featured_ads->have_posts()) : $featured_ads->the_post();
                    $ad_id        = get_the_ID();
                    $ad_link      = get_permalink();
                    $ad_title     = get_the_title();
                    $thumb        = get_the_post_thumbnail_url($ad_id, 'medium_large');
                    if (!$thumb) { $thumb = get_template_directory_uri() . '/images/Photo-Not-Available.png'; }
                    $price_meta   = get_post_meta($ad_id, '_adforest_ad_price', true);
                    $condition    = get_post_meta($ad_id, '_adforest_ad_condition', true);
                    $cond_class   = (strtolower($condition) === 'used') ? 'is-used' : '';
                    $ad_cats_arr  = wp_get_post_terms($ad_id, 'ad_cats', array('fields' => 'ids'));
                    $cat_class    = !empty($ad_cats_arr) ? implode(' cat-', array_map('intval', $ad_cats_arr)) : '';
                    if ($cat_class !== '') { $cat_class = 'cat-' . $cat_class; }
                    $loc_terms    = wp_get_post_terms($ad_id, 'ad_country', array('number' => 1));
                    $loc_label    = (!empty($loc_terms) && !is_wp_error($loc_terms)) ? $loc_terms[0]->name : '';
                    ?>
                    <a href="<?php echo esc_url($ad_link); ?>" class="adf-hm-card" data-cats="<?php echo esc_attr($cat_class); ?>">
                        <div class="adf-hm-card__media">
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($ad_title); ?>" loading="lazy">
                            <span class="adf-hm-card__badge"><?php esc_html_e('Featured', 'adforest'); ?></span>
                            <button type="button" class="adf-hm-card__fav" aria-label="<?php esc_attr_e('Add to favourites', 'adforest'); ?>" onclick="event.preventDefault();">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="adf-hm-card__body">
                            <h3 class="adf-hm-card__title"><?php echo esc_html($ad_title); ?></h3>
                            <div class="adf-hm-card__loc">
                                <?php if ($loc_label) : ?>
                                    <span><i class="fa fa-map-marker-alt"></i> <?php echo esc_html($loc_label); ?></span>
                                <?php else : ?>
                                    <span></span>
                                <?php endif; ?>
                            </div>
                            <div class="adf-hm-card__foot">
                                <span class="adf-hm-card__price">
                                    <?php
                                    if ($price_meta !== '' && is_numeric($price_meta)) {
                                        echo esc_html($currency_symbol) . esc_html(number_format_i18n((float) $price_meta, 2));
                                    } else {
                                        echo esc_html__('—', 'adforest');
                                    }
                                    ?>
                                </span>
                                <?php if ($condition !== '') : ?>
                                    <span class="adf-hm-card__condition <?php echo esc_attr($cond_class); ?>"><?php echo esc_html($condition); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== Top locations ===== -->
    <?php if (!empty($top_locations)) : ?>
    <section class="adf-hm-locs">
        <div class="adf-hm__wrap">
            <div class="adf-hm-sec-head">
                <h2><i class="fa fa-map-marker-alt"></i> <?php esc_html_e('Top Locations', 'adforest'); ?></h2>
                <p><?php esc_html_e('Find ads by top locations.', 'adforest'); ?></p>
            </div>
            <div class="adf-hm-locs__grid">
                <?php foreach ($top_locations as $loc) :
                    $loc_link  = get_term_link($loc);
                    if (is_wp_error($loc_link)) { $loc_link = '#'; }
                    $loc_img_id = get_term_meta($loc->term_id, 'taxonomy_image', true);
                    $loc_img    = $loc_img_id ? wp_get_attachment_image_url((int) $loc_img_id, 'medium_large') : '';
                    if (!$loc_img) {
                        // Fallback placeholder via Unsplash-style CSS gradient
                        $loc_img = get_template_directory_uri() . '/images/Photo-Not-Available.png';
                    }
                    $loc_count = (int) $loc->count;
                    ?>
                    <a href="<?php echo esc_url($loc_link); ?>" class="adf-hm-loc">
                        <img src="<?php echo esc_url($loc_img); ?>" alt="<?php echo esc_attr($loc->name); ?>" loading="lazy">
                        <span class="adf-hm-loc__pin"><i class="fa fa-map-marker-alt"></i></span>
                        <div class="adf-hm-loc__meta">
                            <span class="adf-hm-loc__name"><?php echo esc_html($loc->name); ?></span>
                            <span class="adf-hm-loc__count"><?php
                                printf(
                                    esc_html(_n('%s Ad', '%s Ads', $loc_count, 'adforest')),
                                    esc_html(number_format_i18n($loc_count))
                                );
                            ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== Pricing plans ===== -->
    <section class="adf-hm-plans">
        <div class="adf-hm__wrap">
            <div class="adf-hm-sec-head">
                <h2><i class="fa fa-star"></i> <?php esc_html_e('Our Pricing Plans', 'adforest'); ?></h2>
                <p><?php esc_html_e('Choose the perfect plan for your needs and start posting ads today. All plans include core features.', 'adforest'); ?></p>
            </div>

            <div class="adf-hm-plans__grid">
                <?php
                if (!empty($pricing_plans)) {
                    $plan_icons = array('fa-paper-plane', 'fa-crown', 'fa-star');
                    foreach ($pricing_plans as $idx => $plan) :
                        $is_popular = ($idx === 1); // middle card highlight
                        $product_id = $plan->get_id();
                        $title      = $plan->get_title();
                        $price_raw  = $plan->get_price();
                        $is_free    = (float) $price_raw === 0.0;
                        $cart_url   = $plan->add_to_cart_url();
                        $features   = array(
                            array('label' => __('Post Ads',      'adforest'), 'on' => true),
                            array('label' => __('Edit Ads',      'adforest'), 'on' => true),
                            array('label' => __('Delete Ads',    'adforest'), 'on' => true),
                            array('label' => __('Feature Ads',   'adforest'), 'on' => (int) get_post_meta($product_id, 'sb_pkg_featured_ads', true) > 0),
                            array('label' => __('Images per Ad', 'adforest') . ': ' . esc_html((string) (get_post_meta($product_id, 'sb_pkg_num_of_images', true) ?: '—')), 'on' => true),
                            array('label' => __('Customer Support', 'adforest'), 'on' => true),
                        );
                        ?>
                        <article class="adf-hm-plan <?php echo $is_popular ? 'is-popular' : ''; ?>">
                            <span class="adf-hm-plan__icon"><i class="fa <?php echo esc_attr($plan_icons[$idx % 3]); ?>"></i></span>
                            <h3 class="adf-hm-plan__name"><?php echo esc_html($title); ?></h3>
                            <div class="adf-hm-plan__price">
                                <?php if ($is_free) : ?>
                                    <strong><?php echo esc_html($currency_symbol); ?>0</strong>
                                    <span>/<?php esc_html_e('Free', 'adforest'); ?></span>
                                <?php else : ?>
                                    <strong><?php echo wp_kses_post(wc_price($price_raw)); ?></strong>
                                    <span>/<?php esc_html_e('Monthly', 'adforest'); ?></span>
                                <?php endif; ?>
                            </div>
                            <ul class="adf-hm-plan__feats">
                                <?php foreach ($features as $feat) : ?>
                                    <li class="<?php echo $feat['on'] ? '' : 'is-off'; ?>">
                                        <i class="fa <?php echo $feat['on'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                        <?php echo esc_html($feat['label']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?php echo esc_url($cart_url); ?>" class="adf-hm-plan__cta"><?php esc_html_e('Get Started', 'adforest'); ?></a>
                        </article>
                    <?php endforeach;
                } else {
                    // Fallback static plans when WooCommerce isn't active or no packages exist
                    $static_plans = array(
                        array('name' => __('Basic Plan',  'adforest'), 'price' => '0',     'period' => __('Free',    'adforest'), 'icon' => 'fa-paper-plane', 'images' => '3',  'feat' => false),
                        array('name' => __('Gold Plan',   'adforest'), 'price' => '19.99', 'period' => __('Monthly', 'adforest'), 'icon' => 'fa-crown',       'images' => '10', 'feat' => true),
                        array('name' => __('Silver Plan', 'adforest'), 'price' => '9.99',  'period' => __('Monthly', 'adforest'), 'icon' => 'fa-star',        'images' => '5',  'feat' => false),
                    );
                    foreach ($static_plans as $idx => $plan) :
                        $is_popular = ($idx === 1);
                        ?>
                        <article class="adf-hm-plan <?php echo $is_popular ? 'is-popular' : ''; ?>">
                            <span class="adf-hm-plan__icon"><i class="fa <?php echo esc_attr($plan['icon']); ?>"></i></span>
                            <h3 class="adf-hm-plan__name"><?php echo esc_html($plan['name']); ?></h3>
                            <div class="adf-hm-plan__price">
                                <strong><?php echo esc_html($currency_symbol . $plan['price']); ?></strong>
                                <span>/<?php echo esc_html($plan['period']); ?></span>
                            </div>
                            <ul class="adf-hm-plan__feats">
                                <li><i class="fa fa-check"></i> <?php esc_html_e('Post Ads', 'adforest'); ?></li>
                                <li><i class="fa fa-check"></i> <?php esc_html_e('Edit Ads', 'adforest'); ?></li>
                                <li><i class="fa fa-check"></i> <?php esc_html_e('Delete Ads', 'adforest'); ?></li>
                                <li class="<?php echo $plan['feat'] ? '' : 'is-off'; ?>"><i class="fa <?php echo $plan['feat'] ? 'fa-check' : 'fa-times'; ?>"></i> <?php esc_html_e('Feature Ads', 'adforest'); ?></li>
                                <li><i class="fa fa-check"></i> <?php esc_html_e('Images per Ad', 'adforest'); ?>: <?php echo esc_html($plan['images']); ?></li>
                                <li class="<?php echo $plan['feat'] ? '' : 'is-off'; ?>"><i class="fa <?php echo $plan['feat'] ? 'fa-check' : 'fa-times'; ?>"></i> <?php esc_html_e('Customer Support', 'adforest'); ?></li>
                            </ul>
                            <a href="<?php echo esc_url($packages_url); ?>" class="adf-hm-plan__cta"><?php esc_html_e('Get Started', 'adforest'); ?></a>
                        </article>
                    <?php endforeach;
                }
                ?>
            </div>
        </div>
    </section>

</div>

<script>
/* Featured ads — client-side tab filter by category */
(function () {
    var tabs  = document.querySelectorAll('.adf-hm-featured__tab');
    var cards = document.querySelectorAll('.adf-hm-featured__grid .adf-hm-card');
    if (!tabs.length || !cards.length) return;
    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var filter = btn.getAttribute('data-filter');
            tabs.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            cards.forEach(function (card) {
                if (filter === 'all') {
                    card.style.display = '';
                    return;
                }
                var cats = (card.getAttribute('data-cats') || '').split(' ');
                card.style.display = cats.indexOf(filter) !== -1 ? '' : 'none';
            });
        });
    });
})();
</script>

<?php get_footer('home-modern'); ?>
