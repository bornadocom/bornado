<?php
/**
 * Modern Home header — header bar only (no doctype wrapper).
 *
 * Loaded two ways:
 *   1. By `header.php` when admin picks "Header Modern" in
 *      Theme Options → Header Style (site-wide).
 *   2. By the root `header-home-modern.php` wrapper, which is
 *      itself loaded by `get_header('home-modern')` from the
 *      "AdForest - Home (Modern)" page template.
 *
 * Single source of truth for the modern header markup + styles.
 *
 * @package Adforest
 */

global $adforest_theme;

$site_logo = isset($adforest_theme['sb_site_logo']['url']) && $adforest_theme['sb_site_logo']['url']
    ? $adforest_theme['sb_site_logo']['url']
    : (defined('ADFOREST_IMAGE_PATH') ? ADFOREST_IMAGE_PATH . '/adt-logo.svg' : get_template_directory_uri() . '/images/adt-logo.svg');

$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
$_rgb_parts      = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb   = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

// Post Ad URL — prefer the modern Add New page if configured
$sb_post_ad_page = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : '';
$sb_post_ad_page = apply_filters('adforest_ad_post_verified_id', $sb_post_ad_page);
$modern_post_ad_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? $adforest_theme['sb_modern_post_ad_page'] : '';
$post_ad_url = $modern_post_ad_page_id
    ? get_permalink($modern_post_ad_page_id)
    : ($sb_post_ad_page ? get_permalink($sb_post_ad_page) : '#');

// User-menu inputs (matches the pattern other AdForest headers use)
$adf_hm_user_id      = get_current_user_id();
$adf_hm_sign_in_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : '';
$adf_hm_sign_up_page = isset($adforest_theme['sb_sign_up_page']) ? $adforest_theme['sb_sign_up_page'] : '';
$adf_hm_profile_page = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
?>
<style id="adf-home-modern-header-css">
/* ---- Transparent header overlaying the hero ----
   Outer <header> is fixed at top:0, fully transparent, so the hero's
   background flows through behind/around it. Only the inner content strip
   (logo → CTA) carries a light background. The corners outside the strip
   show whatever the hero has set as its bg image (or pattern). */
.adf-hm-header{
    --hm-brand:<?php echo esc_attr($theme_btn_color); ?>;
    --hm-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;
    --hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;
    --hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;
    --ease:cubic-bezier(.4,0,.2,1);
    background:transparent;border:0;
    position:fixed;top:0;left:0;right:0;z-index:100;
}
.adf-hm-header *{box-sizing:border-box;}
/* WordPress admin bar sits at viewport top:0 (height 32px desktop, 46px mobile).
   Push our fixed header below it so the logo isn't clipped. */
body.admin-bar .adf-hm-header{top:32px;}
@media (max-width:782px){
    body.admin-bar .adf-hm-header{top:46px;}
}

.adf-hm-header__inner{
    position:relative;
    max-width:1280px;
    margin:10px auto 0;
    padding:10px 28px;
    display:flex;align-items:center;justify-content:space-between;gap:24px;
    background:rgba(255,255,255,.78);
    border:1px solid rgba(255,255,255,.6);
    border-radius:8px;
    -webkit-backdrop-filter:blur(20px) saturate(180%);
    backdrop-filter:blur(20px) saturate(180%);
    /* Layered shadow: inset top highlight + tight contact + soft ambient lift */
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.6),
        0 1px 2px rgba(15,23,42,.04),
        0 8px 24px rgba(15,23,42,.06);
    transition:
        background .3s var(--ease),
        border-color .3s var(--ease),
        box-shadow .3s var(--ease),
        -webkit-backdrop-filter .3s var(--ease),
        backdrop-filter .3s var(--ease);
}
/* Fallback for browsers without backdrop-filter — denser white surface */
@supports not ((backdrop-filter:blur(20px)) or (-webkit-backdrop-filter:blur(20px))){
    .adf-hm-header__inner{background:rgba(255,255,255,.94);}
}
.adf-hm-header.is-scrolled .adf-hm-header__inner{
    background:rgba(255,255,255,.88);
    border-color:rgba(255,255,255,.75);
    -webkit-backdrop-filter:blur(24px) saturate(180%);
    backdrop-filter:blur(24px) saturate(180%);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.7),
        0 1px 3px rgba(15,23,42,.06),
        0 12px 32px rgba(15,23,42,.10);
}
@supports not ((backdrop-filter:blur(20px)) or (-webkit-backdrop-filter:blur(20px))){
    .adf-hm-header.is-scrolled .adf-hm-header__inner{background:rgba(255,255,255,.98);}
}

/* Hero clears the fixed header so its content sits below the strip.
   Bottom padding stays at the widget's default 44px so the next widget
   has proper breathing room below the categories. This CSS block only
   renders when the modern header is active, so it's implicitly scoped. */
.adf-hmw-hero{padding-top:155px !important;}
@media (max-width:991px){
    .adf-hmw-hero{padding-top:125px !important;}
}
@media (max-width:600px){
    .adf-hmw-hero{padding-top:105px !important;}
}

/* Interior-page clearance — the modern header is position:fixed and
   covers the top ~108px of the viewport (admin bar + 10px gap + ~66px
   inner card). Without clearance, every interior page (ad detail,
   blog, category, dashboard, etc.) renders its first element hidden
   under the header. Apply body-level padding so all interior pages
   get the spacer "for free", then reset ONLY on the AdForest - Home
   (Modern) page template — that template's hero widget
   (`.adf-hmw-hero`) renders its own padding-top so it flows under the
   glass header on purpose.
   Earlier this rule also zeroed `body.home` and `body.front-page`,
   which collapsed the clearance on every Elementor-built home page
   too, leaving the categories carousel / first widget hidden behind
   the fixed header. The home-template marker class is enough.
   Scoped to this header file: this <style> block only emits when
   the modern header is loaded, so classic-header sites are unaffected. */
body{padding-top:108px;}
body.page-template-page-home-modern,
body.adf-hm-transparent-header{padding-top:0;}
@media (max-width:991px){
    body{padding-top:95px;}
    body.adf-hm-transparent-header{padding-top:0;}
}
@media (max-width:600px){
    body{padding-top:85px;}
    body.adf-hm-transparent-header{padding-top:0;}
}

.adf-hm-header__logo{display:inline-flex;align-items:center;flex-shrink:0;}
.adf-hm-header__logo img{max-height:40px;width:auto;display:block;}

/* Navigation — refined typography + smoother hover */
.adf-hm-header__nav{display:flex;align-items:center;gap:4px;list-style:none;margin:0;padding:0;}
.adf-hm-header__nav li{list-style:none;position:relative;}
.adf-hm-header__nav li a{
    color:#1f2937;font-size:14.5px;font-weight:600;letter-spacing:-0.005em;
    text-decoration:none;padding:9px 14px;border-radius:10px;
    display:inline-flex;align-items:center;gap:6px;
    transition:color .25s var(--ease),background .25s var(--ease);
}
.adf-hm-header__nav li a:hover,
.adf-hm-header__nav li.current-menu-item > a,
.adf-hm-header__nav li.current-menu-parent > a{
    color:var(--hm-brand);background:rgba(var(--hm-brand-rgb),.08);
}

/* Submenu — floats with the same glass card aesthetic */
.adf-hm-header__nav .sub-menu,
.adf-hm-header__nav ul.drop-down-multilevel,
.adf-hm-header__nav ul.drop-down{
    display:none;position:absolute;top:calc(100% + 8px);left:0;
    background:#fff;border:1px solid rgba(15,23,42,.06);
    border-radius:14px;
    box-shadow:0 12px 32px rgba(15,23,42,.10),0 2px 6px rgba(15,23,42,.04);
    min-width:210px;padding:6px;margin:0;list-style:none;z-index:60;
}
.adf-hm-header__nav li:hover > .sub-menu,
.adf-hm-header__nav li:hover > ul.drop-down-multilevel,
.adf-hm-header__nav li:hover > ul.drop-down{display:block;}
.adf-hm-header__nav .sub-menu li a,
.adf-hm-header__nav ul.drop-down-multilevel li a,
.adf-hm-header__nav ul.drop-down li a{
    display:block;padding:9px 12px;border-radius:8px;
    font-weight:500;font-size:14px;
}

/* Multi-level (nested) drop-down — opens to the side, not stacked below. */
.adf-hm-header__nav ul.drop-down-multilevel ul.drop-down-multilevel,
.adf-hm-header__nav ul.drop-down-multilevel ul.sub-menu,
.adf-hm-header__nav .sub-menu .sub-menu{
    top:0;left:100%;margin-left:6px;
}

/* ===========================================================
   MEGA MENU
   AdForest's nav walker emits this markup for items configured
   with the "Mega Menu" CSS-class option in WP-admin → Menus:
     li.mega-menu
       a (label)
       ul.mega-menu-list.grid-col-N
         li
           div.drop-down.mega-menu-container
             div.grid-row.row
               div.grid-col-N
                 h4 a (column title)
                 ul > li > a (column items)
   Without dedicated styles the columns collapse vertically and
   the column titles look like nested dropdowns (see "Home Demos"
   bug). The styles below make the panel a wide flex card.
   =========================================================== */
.adf-hm-header__nav li.mega-menu{position:static;}
.adf-hm-header__nav ul.mega-menu-list{
    display:none;position:absolute;
    top:calc(100% + 8px);left:0;right:0;
    background:#fff;border:1px solid rgba(15,23,42,.06);
    border-radius:14px;
    box-shadow:0 16px 40px rgba(15,23,42,.12),0 2px 6px rgba(15,23,42,.04);
    padding:22px 24px;margin:0;list-style:none;z-index:60;
}
.adf-hm-header__nav li.mega-menu:hover > ul.mega-menu-list{display:block;}
.adf-hm-header__nav ul.mega-menu-list > li{
    list-style:none;position:relative;width:100%;
}
.adf-hm-header__nav .mega-menu-container{width:100%;padding:0;}
.adf-hm-header__nav .mega-menu-container > .grid-row,
.adf-hm-header__nav .mega-menu-container > .row{
    display:flex;flex-wrap:wrap;gap:24px;margin:0;align-items:flex-start;
}
/* Each .grid-col-N column distributes equally inside the flex row.
   The walker outputs grid-col-1..12 — translate to a percentage
   flex-basis so a 4-column menu spreads to ~25% each, etc. The
   `min-width:0` lets long item labels truncate cleanly. */
.adf-hm-header__nav .mega-menu-container [class*="grid-col-"]{
    flex:1 1 0;min-width:0;padding:0;
}
.adf-hm-header__nav .mega-menu-container .grid-col-1{flex-basis:calc(8.3333% - 22px);}
.adf-hm-header__nav .mega-menu-container .grid-col-2{flex-basis:calc(16.6666% - 22px);}
.adf-hm-header__nav .mega-menu-container .grid-col-3{flex-basis:calc(25% - 18px);}
.adf-hm-header__nav .mega-menu-container .grid-col-4{flex-basis:calc(33.3333% - 16px);}
.adf-hm-header__nav .mega-menu-container .grid-col-5{flex-basis:calc(41.6666% - 14px);}
.adf-hm-header__nav .mega-menu-container .grid-col-6{flex-basis:calc(50% - 12px);}
.adf-hm-header__nav .mega-menu-container .grid-col-7{flex-basis:calc(58.3333% - 10px);}
.adf-hm-header__nav .mega-menu-container .grid-col-8{flex-basis:calc(66.6666% - 8px);}
.adf-hm-header__nav .mega-menu-container .grid-col-9{flex-basis:calc(75% - 6px);}
.adf-hm-header__nav .mega-menu-container .grid-col-10{flex-basis:calc(83.3333% - 4px);}
.adf-hm-header__nav .mega-menu-container .grid-col-11{flex-basis:calc(91.6666% - 2px);}
.adf-hm-header__nav .mega-menu-container .grid-col-12{flex-basis:100%;}
/* Column heading (e.g. "Classified", "Directory & Events") */
.adf-hm-header__nav .mega-menu-container h4{
    margin:0 0 10px;padding:0 4px 8px;
    font-size:13px;font-weight:700;
    color:#0f172a;letter-spacing:.02em;
    text-transform:uppercase;
    border-bottom:1px solid rgba(15,23,42,.08);
}
.adf-hm-header__nav .mega-menu-container h4 a{
    display:inline;padding:0;background:transparent;border-radius:0;
    color:inherit;font-size:inherit;font-weight:inherit;
}
.adf-hm-header__nav .mega-menu-container h4 a:hover{
    background:transparent;color:var(--hm-brand);
}
.adf-hm-header__nav .mega-menu-container ul{
    list-style:none;margin:0;padding:0;
    display:flex;flex-direction:column;gap:1px;
}
.adf-hm-header__nav .mega-menu-container ul li{
    list-style:none;position:relative;
}
.adf-hm-header__nav .mega-menu-container ul li a{
    display:flex;align-items:center;gap:6px;
    padding:7px 8px;border-radius:8px;
    font-weight:500;font-size:13.5px;color:#334155;
    transition:color .2s var(--ease),background .2s var(--ease);
}
.adf-hm-header__nav .mega-menu-container ul li a:hover{
    color:var(--hm-brand);background:rgba(var(--hm-brand-rgb),.06);
}
/* Suppress the down-arrow indicator that the walker bakes onto every
   parent item — column titles in the mega menu shouldn't look like
   another expandable level. */
.adf-hm-header__nav .mega-menu-container .fa-indicator{display:none;}
.adf-hm-header__nav .mega-menu-container .custom-icon{margin-right:2px;}

/* Actions cluster (user dropdown + CTA) */
.adf-hm-header__actions{display:flex;align-items:center;gap:16px;flex-shrink:0;}

/* Premium Post-an-Ad CTA — inset top highlight + three-layer brand-tinted shadow */
.adf-hm-header__cta{
    display:inline-flex;align-items:center;gap:8px;
    background:var(--hm-brand);color:var(--hm-brand-text) !important;
    border:0;border-radius:12px;
    padding:11px 22px;
    font-size:14px;font-weight:600;letter-spacing:-0.005em;
    text-decoration:none;font-family:inherit;line-height:1;
    /* Inset top highlight + contact + ambient + soft diffuse */
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.2),
        0 1px 2px rgba(var(--hm-brand-rgb),.18),
        0 4px 14px rgba(var(--hm-brand-rgb),.25),
        0 8px 24px rgba(var(--hm-brand-rgb),.12);
    transition:background .3s var(--ease),transform .2s var(--ease),box-shadow .3s var(--ease);
}
.adf-hm-header__cta:hover{
    background:var(--hm-brand-hover);color:var(--hm-brand-text) !important;
    transform:translateY(-1px);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.28),
        0 2px 4px rgba(var(--hm-brand-rgb),.20),
        0 6px 20px rgba(var(--hm-brand-rgb),.32),
        0 12px 32px rgba(var(--hm-brand-rgb),.18);
}
.adf-hm-header__cta:active{
    transform:translateY(0);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.15),
        0 1px 4px rgba(var(--hm-brand-rgb),.25);
}
.adf-hm-header__cta i{font-size:13px;}

/* Mobile burger toggle — subtle hover affordance */
.adf-hm-mobile-toggle{
    display:none;background:transparent;border:0;cursor:pointer;
    color:#1f2937;font-size:20px;
    width:40px;height:40px;border-radius:10px;
    align-items:center;justify-content:center;
    transition:background .2s var(--ease),color .2s var(--ease);
}
.adf-hm-mobile-toggle:hover{background:rgba(15,23,42,.05);color:var(--hm-brand);}

@media (max-width:991px){
    .adf-hm-header__inner{padding:12px 18px;}
    .adf-hm-mobile-toggle{display:inline-flex;}
    /* Mobile nav drawer — clean rounded panel that opens below the header */
    .adf-hm-header__nav{
        display:none;position:absolute;top:100%;left:0;right:0;
        flex-direction:column;align-items:stretch;
        background:#fff;
        border-top:1px solid rgba(15,23,42,.05);
        padding:10px 12px;gap:2px;
        box-shadow:0 12px 28px rgba(15,23,42,.08);
    }
    .adf-hm-header__nav.is-open{display:flex;}
    .adf-hm-header__nav li a{padding:10px 14px;border-radius:10px;}
    .adf-hm-header__nav .sub-menu,
    .adf-hm-header__nav ul.drop-down-multilevel,
    .adf-hm-header__nav ul.drop-down,
    .adf-hm-header__nav ul.mega-menu-list{
        position:static;border:0;box-shadow:none;background:transparent;
        padding:0 0 0 14px;border-radius:0;
    }
    /* Multi-level nested drop-downs flip back to stacked layout on mobile */
    .adf-hm-header__nav ul.drop-down-multilevel ul.drop-down-multilevel,
    .adf-hm-header__nav ul.drop-down-multilevel ul.sub-menu,
    .adf-hm-header__nav .sub-menu .sub-menu{
        left:0;margin-left:0;
    }
    /* Mega-menu collapses to a single-column accordion on mobile —
       no flex row, no card chrome, just inline content. */
    .adf-hm-header__nav .mega-menu-container > .grid-row,
    .adf-hm-header__nav .mega-menu-container > .row{
        display:block;gap:0;
    }
    .adf-hm-header__nav .mega-menu-container [class*="grid-col-"]{
        flex-basis:auto;padding:0;margin-bottom:10px;
    }
    .adf-hm-header__nav .mega-menu-container h4{
        padding:6px 4px;border-bottom:0;
    }
}
@media (max-width:600px){
    .adf-hm-header__inner{padding:10px 16px;}
    .adf-hm-header__logo img{max-height:34px;}
    .adf-hm-header__cta{padding:10px 14px;font-size:13px;border-radius:11px;}
    .adf-hm-header__cta span{display:none;}
    .adf-hm-header__actions{gap:10px;}
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above. The header is position:fixed
 * with `left:0;right:0` (symmetric) so no flip needed there;
 * only nested submenus and the multi-level offset are directional.
 * ========================================================== */
body.rtl .adf-hm-header__nav .sub-menu,
body.rtl .adf-hm-header__nav ul.drop-down-multilevel,
body.rtl .adf-hm-header__nav ul.drop-down{left:auto;right:0;}
body.rtl .adf-hm-header__nav ul.drop-down-multilevel ul.drop-down-multilevel,
body.rtl .adf-hm-header__nav ul.drop-down-multilevel ul.sub-menu,
body.rtl .adf-hm-header__nav .sub-menu .sub-menu{left:auto;right:100%;margin-left:0;margin-right:6px;}
body.rtl .adf-hm-header__nav ul.mega-menu-list{left:0;right:0;}
@media (max-width:991px){
    body.rtl .adf-hm-header__nav .sub-menu,
    body.rtl .adf-hm-header__nav ul.drop-down-multilevel,
    body.rtl .adf-hm-header__nav ul.drop-down,
    body.rtl .adf-hm-header__nav ul.mega-menu-list{padding:0 14px 0 0;}
    body.rtl .adf-hm-header__nav ul.drop-down-multilevel ul.drop-down-multilevel,
    body.rtl .adf-hm-header__nav ul.drop-down-multilevel ul.sub-menu,
    body.rtl .adf-hm-header__nav .sub-menu .sub-menu{right:0;margin-right:0;}
}
</style>

<header class="adf-hm-header" id="adf-hm-header" role="banner">
    <div class="adf-hm-header__inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="adf-hm-header__logo" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <img src="<?php echo esc_url($site_logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>

        <button type="button" class="adf-hm-mobile-toggle" aria-controls="adf-hm-nav" aria-expanded="false" aria-label="<?php esc_attr_e('Open menu', 'adforest'); ?>">
            <i class="fa fa-bars"></i>
        </button>

        <ul id="adf-hm-nav" class="adf-hm-header__nav">
            <?php
            // Render the WP menu assigned to the "Adforest Primary Menu" (main_menu)
            // location, using AdForest's own walker for consistency with other headers.
            if (function_exists('adforest_theme_menu')) {
                adforest_theme_menu('main_menu');
            }
            ?>
        </ul>

        <div class="adf-hm-header__actions">
            <?php
            // User profile dropdown — sign-in/sign-up links for guests, avatar
            // dropdown for logged-in users. Function is shared by header-1/2/3.
            if (function_exists('adforest_get_header_user_menu_markup')) {
                echo adforest_get_header_user_menu_markup(array(
                    'sign_in_page' => $adf_hm_sign_in_page,
                    'sign_up_page' => $adf_hm_sign_up_page,
                    'profile_page' => $adf_hm_profile_page,
                    'user_id'      => $adf_hm_user_id,
                ));
            }
            ?>
            <a href="<?php echo esc_url($post_ad_url); ?>" class="adf-hm-header__cta">
                <i class="fa fa-plus"></i> <span><?php esc_html_e('Post an Ad', 'adforest'); ?></span>
            </a>
        </div>
    </div>
</header>

<script>
(function () {
    var t = document.querySelector('.adf-hm-mobile-toggle');
    var n = document.getElementById('adf-hm-nav');
    if (t && n) {
        t.addEventListener('click', function () {
            var open = n.classList.toggle('is-open');
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Sticky-state — adds .is-scrolled when the page is scrolled past a small
    // threshold so the header gains a soft shadow once it detaches from the top.
    var header = document.getElementById('adf-hm-header');
    if (header) {
        var threshold = 30;
        var ticking   = false;
        var apply = function () {
            ticking = false;
            if (window.pageYOffset > threshold) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        };
        var onScroll = function () {
            if (!ticking) {
                window.requestAnimationFrame(apply);
                ticking = true;
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        apply();
    }
})();
</script>
