<?php
/*
 * Template Name: AdForest - Add New (Modern)
 *
 * Standalone "Add New / Post Ad" page used by the Modern User Menu.
 * Renders the existing AdForest post-ad form (via the
 * `ad_post_short_base_func` callback exported by the AdForest
 * Elementor plugin) inside a SaaS-style wizard shell:
 *
 *   - Modern account sub-nav at the top (Add New active)
 *   - Horizontal step indicator that visually replaces the form's
 *     built-in vertical pill nav
 *   - Restyled inputs, dropdowns, dropzone, radios, buttons
 *
 * The underlying form markup is NOT modified. Every input id/name,
 * every JS hook (#adforest-ad-post-form, .next-btn/.prev-btn,
 * Bootstrap pill navigation, Dropzone init, Parsley validation,
 * AJAX category/location cascades), and every nonce stays exactly
 * as the original shortcode renders them, so the submit pipeline
 * keeps working. This template is purely a presentational shell.
 *
 * The classic Post Ad page assigned via Theme Options →
 * sb_post_ad_page is kept as-is so admins can roll back instantly.
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

// Brand colors → CSS vars
$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
$_rgb_parts      = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb   = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

// Theme-options-driven URLs
$post_ad_page_id = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : '';
$post_ad_page_id = apply_filters('adforest_ad_post_verified_id', $post_ad_page_id);
$classic_post_ad_url = $post_ad_page_id ? get_permalink($post_ad_page_id) : '#';

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
$modern_invoices_page_id  = isset($adforest_theme['sb_modern_invoices_page']) ? $adforest_theme['sb_modern_invoices_page'] : '';
$modern_invoices_url      = $modern_invoices_page_id ? get_permalink($modern_invoices_page_id) : ($dash_url ? add_query_arg('page_type', 'invoices', $dash_url) : '#');
$modern_packages_page_id  = isset($adforest_theme['sb_modern_my_packages_page']) ? $adforest_theme['sb_modern_my_packages_page'] : '';
$modern_packages_url      = $modern_packages_page_id ? get_permalink($modern_packages_page_id) : ($dash_url ? add_query_arg('page_type', 'my_packages', $dash_url) : '#');

$account_nav = array(
    array('icon' => 'fa fa-plus-circle',     'label' => __('Add New',           'adforest'), 'url' => get_permalink(), 'active' => true),
    array('icon' => 'fa fa-clipboard-check', 'label' => __('Awaiting Approval', 'adforest'), 'url' => $modern_pending_url, 'active' => false),
    array('icon' => 'fa fa-receipt',         'label' => __('Invoices',          'adforest'), 'url' => $modern_invoices_url, 'active' => false),
    array('icon' => 'fa fa-list',            'label' => __('My Listings',       'adforest'), 'url' => $modern_listings_url, 'active' => false),
    array('icon' => 'fa fa-heart',           'label' => __('Favorites',         'adforest'), 'url' => $modern_favorites_url, 'active' => false),
    array('icon' => 'fa fa-envelope',        'label' => __('Messages',          'adforest'), 'url' => $modern_messages_url, 'active' => false),
    array('icon' => 'fa fa-box',             'label' => __('My Packages',       'adforest'), 'url' => $modern_packages_url, 'active' => false),
    array('icon' => 'fa fa-cog',             'label' => __('Profile Settings',  'adforest'), 'url' => $modern_settings_url, 'active' => false),
);

// Steps shown in the modern stepper. Labels match the visible tab buttons
// rendered by ad_post_short_base_func so users see a consistent flow.
$wizard_steps = array(
    array('label' => __('Category',  'adforest'), 'sub' => __('Pick where it belongs',  'adforest')),
    array('label' => __('Details',   'adforest'), 'sub' => __('Tell buyers about it',   'adforest')),
    array('label' => __('Media',     'adforest'), 'sub' => __('Add images & video',     'adforest')),
    array('label' => __('Contact',   'adforest'), 'sub' => __('Location & how to reach', 'adforest')),
);

$shortcode_available = function_exists('ad_post_short_base_func');

// Resolve which page style to render. Default is `classic` so existing
// installs upgrade without any visual change. Site admins flip to
// `modern` via Theme Options → Ads Post Settings → Post Ad Page Style
// when they're ready. Both styles use the same `ad_post_short_base_func`
// rendering — only the surrounding shell differs.
$post_ad_page_style = isset($adforest_theme['adforest_post_ad_page_style']) ? $adforest_theme['adforest_post_ad_page_style'] : 'classic';

// Terms-checkbox config — the classic Post Ad page configures terms via
// the Elementor widget (`ad_post_short_base`) settings, not theme options.
// Hoisted here so BOTH style branches (classic + modern) can read it from
// the same place. If no Elementor data is found, sensible defaults keep
// the form usable.
$terms_switch = 'show';
$terms_title  = __('the terms and conditions', 'adforest');
$terms_link   = array('url' => '');
if ($shortcode_available && !empty($post_ad_page_id)) {
    $classic_elementor_data = get_post_meta((int) $post_ad_page_id, '_elementor_data', true);
    if (is_string($classic_elementor_data) && $classic_elementor_data !== '') {
        $classic_decoded = json_decode($classic_elementor_data, true);
        if (is_array($classic_decoded)) {
            $stack = $classic_decoded;
            while (!empty($stack)) {
                $node = array_shift($stack);
                if (!is_array($node)) continue;
                if (
                    isset($node['widgetType'])
                    && in_array($node['widgetType'], array('ad_post_short_base', 'adforest_ad_post_modern'), true)
                    && !empty($node['settings']) && is_array($node['settings'])
                ) {
                    $s = $node['settings'];
                    if (isset($s['terms_switch'])) $terms_switch = $s['terms_switch'];
                    if (!empty($s['terms_title']))  $terms_title  = $s['terms_title'];
                    if (!empty($s['terms_link']))   $terms_link   = $s['terms_link'];
                    break;
                }
                if (!empty($node['elements']) && is_array($node['elements'])) {
                    foreach ($node['elements'] as $child) $stack[] = $child;
                }
            }
        }
    }
}

// Classic branch: render the shortcode form alone, no Modern wizard
// shell. Pixel-identical to the previous Post Ad page so existing
// admins keep their familiar UX. Early-return skips every line of
// Modern CSS, the stepper HTML, and the JS bridge below.
if ($post_ad_page_style !== 'modern') {
    if ($shortcode_available) {
        echo ad_post_short_base_func(array(
            'form_title'   => '',
            'terms_switch' => $terms_switch,
            'terms_link'   => $terms_link,
            'terms_title'  => $terms_title,
        ));
    } else {
        ?>
        <section class="adforest-add-fallback-section" style="padding:60px 0;text-align:center;">
            <div class="container">
                <i class="fa fa-exclamation-triangle" style="font-size:36px;color:#d97706;"></i>
                <p style="margin-top:12px;font-size:15px;"><?php esc_html_e('The AdForest Elementor plugin is required to render the post-ad form. Please activate it, or use the classic Post Ad page.', 'adforest'); ?></p>
                <?php if ($classic_post_ad_url && $classic_post_ad_url !== '#') : ?>
                    <a href="<?php echo esc_url($classic_post_ad_url); ?>" style="display:inline-block;margin-top:8px;">
                        <i class="fa fa-arrow-right"></i> <?php esc_html_e('Go to Classic Post Ad page', 'adforest'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
    get_footer();
    return;
}

// Modern branch continues below: wizard shell with horizontal stepper,
// restyled inputs/dropzone/buttons, and the stepper↔pill bridge JS.
?>

<style id="adforest-addnew-css">
/* ===== Tokens ============================================== */
.adforest-add-new-page{
    --adf-brand:<?php echo esc_attr($theme_btn_color); ?>;
    --adf-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;
    --adf-brand-text:<?php echo esc_attr($theme_btn_text); ?>;
    --adf-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;

    --adf-ink-1:#0f172a;
    --adf-ink-2:#1f2937;
    --adf-ink-3:#475569;
    --adf-ink-4:#64748b;
    --adf-mute:#94a3b8;
    --adf-line:#e6e9ef;
    --adf-line-2:#eef1f6;
    --adf-bg:#f5f6fb;
    --adf-bg-soft:#fafbfd;
    --adf-card:#fff;

    --adf-radius-sm:8px;
    --adf-radius:12px;
    --adf-radius-lg:16px;

    --adf-shadow-sm:0 1px 2px rgba(15,23,42,.04);
    --adf-shadow:0 1px 2px rgba(15,23,42,.04), 0 4px 16px rgba(15,23,42,.06);
    --adf-shadow-lift:0 10px 26px rgba(15,23,42,.10);

    --adf-input-h:48px;
    --adf-btn-h:48px;
}
.adforest-account-page.adforest-add-new-page{
    background:var(--adf-bg);min-height:100vh;padding:28px 0 64px;
}
.adforest-add-new-page *{box-sizing:border-box;}

/* Tighten content width inside the theme container so the form
 * doesn't sprawl across wide screens. */
.adforest-add-new-page > .container,
.adforest-add-new-page > .container.adt-container{max-width:1140px;}

/* ===== Account sub-nav ===================================== */
/* Floating pill navigation — rounded card container, soft shadows,
 * strong active state, horizontal scroll on small screens. */
.adforest-add-new-page .adforest-account-nav{display:flex;flex-wrap:nowrap;align-items:center;gap:6px;background:var(--adf-card);border:1px solid var(--adf-line);border-radius:16px;padding:8px;margin-bottom:24px;box-shadow:0 0 10px rgba(15,23,42,.05);overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;}
.adforest-add-new-page .adforest-account-nav::-webkit-scrollbar{display:none;}
.adforest-add-new-page .adforest-account-nav a{position:relative;display:inline-flex;align-items:center;gap:8px;color:var(--adf-ink-4);font-size:14px;font-weight:600;text-decoration:none;padding:10px 18px;border-radius:10px;white-space:nowrap;flex-shrink:0;transition:color .18s ease,background .18s ease,box-shadow .18s ease,transform .12s ease;}
.adforest-add-new-page .adforest-account-nav a:hover{color:var(--adf-ink-2);background:var(--adf-bg-soft);}
.adforest-add-new-page .adforest-account-nav a.is-active{color:var(--adf-brand-text);background:var(--adf-brand);box-shadow:0 0 6px rgba(var(--adf-brand-rgb),.25);}
.adforest-add-new-page .adforest-account-nav a.is-active:hover{background:var(--adf-brand-hover);color:var(--adf-brand-text);}
.adforest-add-new-page .adforest-account-nav a i{font-size:13px;color:inherit;opacity:.75;}

/* ===== Page header ========================================= */
.adforest-add-header{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px;}
.adforest-add-header__text h1{font-size:30px;font-weight:800;color:var(--adf-ink-1);margin:0 0 6px;letter-spacing:-.02em;line-height:1.15;}
.adforest-add-header__text p{margin:0;color:var(--adf-ink-4);font-size:14px;line-height:1.5;max-width:560px;}

/* ===== Stepper (premium, continuous progress line) ========= */
.adforest-stepper{
    position:relative;background:var(--adf-card);border:1px solid var(--adf-line);
    border-radius:var(--adf-radius-lg);padding:20px 28px 24px;margin-bottom:24px;
    box-shadow:var(--adf-shadow-sm);
}
.adforest-stepper__meta{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    flex-wrap:wrap;margin:0 0 16px;
}
.adforest-stepper__pill{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(var(--adf-brand-rgb),.10);color:var(--adf-brand);
    border-radius:999px;padding:5px 12px;font-size:12px;font-weight:700;
    letter-spacing:.04em;text-transform:uppercase;
}
.adforest-stepper__pill strong{font-weight:800;color:var(--adf-brand);}
.adforest-stepper__heading{font-size:14px;color:var(--adf-ink-3);font-weight:600;}
.adforest-stepper__track{
    position:relative;
    display:grid;grid-template-columns:repeat(4, 1fr);align-items:start;gap:0;
    --adf-progress:0%;
}
.adforest-stepper__line,
.adforest-stepper__line-fill{
    position:absolute;top:22px;height:3px;border-radius:3px;left:12.5%;right:12.5%;
}
.adforest-stepper__line{background:var(--adf-line);z-index:0;}
.adforest-stepper__line-fill{
    background:linear-gradient(90deg, var(--adf-brand) 0%, var(--adf-brand-hover) 100%);
    /* The gray line spans the middle 75% of the track (left:12.5%, right:12.5%).
       --adf-progress is 0%..100% across step indices, so the fill width must be
       scaled to that 75% range — otherwise progress=100% paints a fill 100%
       wide on top of the 12.5% left offset and overflows the right edge of
       the card. calc(percentage * number) keeps the scaling unitless and works
       across all evergreen browsers. */
    width:calc(var(--adf-progress) * 0.75);right:auto;
    transition:width .35s cubic-bezier(.4,0,.2,1);
    box-shadow:0 0 0 0 rgba(var(--adf-brand-rgb),0);
}
.adforest-stepper__item{
    position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;
    gap:10px;cursor:pointer;user-select:none;padding:0 4px;
}
.adforest-stepper__num{
    width:46px;height:46px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
    background:var(--adf-card);color:var(--adf-mute);font-weight:700;font-size:15px;
    border:2px solid var(--adf-line);transition:all .25s cubic-bezier(.4,0,.2,1);
    box-shadow:var(--adf-shadow-sm);
}
.adforest-stepper__label{display:flex;flex-direction:column;line-height:1.25;min-width:0;text-align:center;}
.adforest-stepper__label strong{font-size:13.5px;font-weight:700;color:var(--adf-ink-3);letter-spacing:-.005em;}
.adforest-stepper__label span{font-size:11.5px;color:var(--adf-mute);margin-top:3px;}
.adforest-stepper__item.is-active .adforest-stepper__num{
    background:var(--adf-brand);color:var(--adf-brand-text);border-color:var(--adf-brand);
    box-shadow:0 0 0 6px rgba(var(--adf-brand-rgb),.14), var(--adf-shadow);
    transform:scale(1.05);
}
.adforest-stepper__item.is-active .adforest-stepper__label strong{color:var(--adf-ink-1);}
.adforest-stepper__item.is-done .adforest-stepper__num{
    background:var(--adf-brand);color:var(--adf-brand-text);border-color:var(--adf-brand);font-size:0;
}
.adforest-stepper__item.is-done .adforest-stepper__num::before{
    content:"\f00c";font-family:"Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:15px;
}
.adforest-stepper__item.is-done .adforest-stepper__label strong{color:var(--adf-ink-2);}
.adforest-stepper__item:hover .adforest-stepper__num{border-color:var(--adf-mute);}
.adforest-stepper__item.is-active:hover .adforest-stepper__num,
.adforest-stepper__item.is-done:hover .adforest-stepper__num{border-color:var(--adf-brand);}

/* ===== Form card ========================================== */
.adforest-add-card{
    background:var(--adf-card);border:1px solid var(--adf-line);border-radius:var(--adf-radius-lg);
    box-shadow:var(--adf-shadow);padding:0;overflow:hidden;
}

/* ===== Overrides on the shortcode markup ================== */

/* Strip the section's outer paddings */
.adforest-add-new-page .adt-ad-post-section{padding:0 !important;background:transparent !important;}
.adforest-add-new-page .adt-ad-post-section > .container{padding:0;max-width:none;}
.adforest-add-new-page .adt-ad-post-section > .container > .row{margin:0;}
.adforest-add-new-page .adt-ad-post-section > .container > .row > form{display:block;width:100%;}
.adforest-add-new-page .adt-ad-post-section .col-lg-12{padding:0;}

/* Hide original vertical pill nav (stepper above handles UX) */
.adforest-add-new-page .ad-post-tabs-wrapper{display:block;}
.adforest-add-new-page .ad-post-tabs{display:none !important;}

/* Tab content area */
.adforest-add-new-page .ad-post-tab-content{padding:32px 36px 28px;}
.adforest-add-new-page .ad-post-tab-box{padding:0 !important;background:transparent !important;border:0 !important;box-shadow:none !important;}

/* Section heading inside each step */
.adforest-add-new-page .ad-post-tab-box > h3{
    position:relative;font-size:22px;font-weight:800;color:var(--adf-ink-1);
    margin:0 0 6px;letter-spacing:-.015em;padding-left:14px;
}
.adforest-add-new-page .ad-post-tab-box > h3::before{
    content:"";position:absolute;left:0;top:6px;bottom:6px;width:4px;
    background:var(--adf-brand);border-radius:3px;
}
.adforest-add-new-page .ad-post-tab-box > h3 + span,
.adforest-add-new-page .ad-post-tab-box > h3 + p{
    display:block;color:var(--adf-ink-4);font-size:13.5px;margin:0 0 24px 14px;line-height:1.5;
}

/* Selected category breadcrumb */
.adforest-add-new-page .ad-post-selected-categories{padding:18px 36px 0;color:var(--adf-ink-4);font-size:13px;}
.adforest-add-new-page .ad-post-selected-categories:empty{display:none;}
.adforest-add-new-page .ad-post-selected-categories a{color:var(--adf-brand);text-decoration:none;font-weight:600;}

/* Labels */
.adforest-add-new-page .ad-post-tab-box .label-box{margin:20px 0 8px;display:flex;flex-direction:column;gap:2px;}
.adforest-add-new-page .ad-post-tab-box .label-box label{font-size:13px;font-weight:700;color:var(--adf-ink-2);display:inline-block;margin:0;letter-spacing:.01em;}
.adforest-add-new-page .ad-post-tab-box .label-box .category-box-label{display:block;font-size:12px;color:var(--adf-mute);line-height:1.4;}
.adforest-add-new-page .ad-post-tab-box .label-box .category-box-label .required{color:var(--adf-brand);font-weight:700;margin-right:4px;}
.adforest-add-new-page .ad-post-tab-box .label-box label .required,
.adforest-add-new-page .ad-post-tab-box label .required{color:var(--adf-brand);margin-left:3px;}

/* Top-level field labels (raw <label> outside .label-box) */
.adforest-add-new-page .ad-post-tab-box .field-box > label,
.adforest-add-new-page .ad-post-tab-box .form-group > label{
    font-size:13px;font-weight:700;color:var(--adf-ink-2);display:inline-block;margin:0 0 8px;letter-spacing:.01em;
}

/* Inputs / selects / textareas */
.adforest-add-new-page .ad-post-tab-box .form-control,
.adforest-add-new-page .ad-post-tab-box select.default-select,
.adforest-add-new-page .ad-post-tab-box .child-category-select,
.adforest-add-new-page .ad-post-tab-box input[type=text],
.adforest-add-new-page .ad-post-tab-box input[type=number],
.adforest-add-new-page .ad-post-tab-box input[type=email],
.adforest-add-new-page .ad-post-tab-box input[type=url],
.adforest-add-new-page .ad-post-tab-box input[type=tel],
.adforest-add-new-page .ad-post-tab-box select,
.adforest-add-new-page .ad-post-tab-box textarea{
    appearance:none;-webkit-appearance:none;
    border:1.5px solid var(--adf-line) !important;background:#fff !important;border-radius:10px !important;
    padding:12px 16px !important;font-size:14.5px !important;color:var(--adf-ink-1) !important;
    box-shadow:none !important;height:var(--adf-input-h) !important;width:100%;
    transition:border-color .18s ease,box-shadow .18s ease,background .18s ease;
    font-family:inherit;line-height:1.4;
}
.adforest-add-new-page .ad-post-tab-box textarea{min-height:140px;height:auto !important;line-height:1.55;padding-top:14px !important;padding-bottom:14px !important;}
.adforest-add-new-page .ad-post-tab-box input::placeholder,
.adforest-add-new-page .ad-post-tab-box textarea::placeholder{color:#9aa4b1;}
.adforest-add-new-page .ad-post-tab-box .form-control:hover,
.adforest-add-new-page .ad-post-tab-box select:hover,
.adforest-add-new-page .ad-post-tab-box input:hover,
.adforest-add-new-page .ad-post-tab-box textarea:hover{border-color:#cbd2dc !important;}
.adforest-add-new-page .ad-post-tab-box .form-control:focus,
.adforest-add-new-page .ad-post-tab-box select:focus,
.adforest-add-new-page .ad-post-tab-box input:focus,
.adforest-add-new-page .ad-post-tab-box textarea:focus{
    outline:none !important;border-color:var(--adf-brand) !important;
    box-shadow:0 0 0 4px rgba(var(--adf-brand-rgb),.13) !important;
}
.adforest-add-new-page .ad-post-tab-box select.default-select,
.adforest-add-new-page .ad-post-tab-box select{
    background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
    background-repeat:no-repeat !important;background-position:right 16px center !important;
    padding-right:42px !important;cursor:pointer;
}

/* Disabled / readonly */
.adforest-add-new-page .ad-post-tab-box input[readonly],
.adforest-add-new-page .ad-post-tab-box input:disabled,
.adforest-add-new-page .ad-post-tab-box select:disabled,
.adforest-add-new-page .ad-post-tab-box textarea:disabled{background:#f5f6fb !important;color:var(--adf-ink-4) !important;cursor:not-allowed;}

/* Spacing between fields */
.adforest-add-new-page .ad-post-tab-box .field-box,
.adforest-add-new-page .ad-post-tab-box .form-group{margin-bottom:20px;}
.adforest-add-new-page .ad-post-tab-box .row{margin-left:-10px;margin-right:-10px;}
.adforest-add-new-page .ad-post-tab-box .row > [class*="col-"]{padding-left:10px;padding-right:10px;margin-bottom:4px;}

/* Buttons (Next / Previous / Submit) */
.adforest-add-new-page .ad-post-btns-box{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    margin-top:32px;padding-top:24px;border-top:1px solid var(--adf-line-2);flex-wrap:wrap;
}
.adforest-add-new-page .ad-post-btns-box .next-btn,
.adforest-add-new-page .ad-post-btns-box .prev-btn,
.adforest-add-new-page .ad-post-btns-box button[type=submit],
.adforest-add-new-page .ad-post-btns-box .btn-adpost-start,
.adforest-add-new-page #ad_post_submit_button{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    background:var(--adf-brand) !important;color:var(--adf-brand-text) !important;
    border:1.5px solid var(--adf-brand) !important;border-radius:10px !important;
    padding:0 26px !important;height:var(--adf-btn-h);min-width:140px;
    font-size:14.5px !important;font-weight:700 !important;letter-spacing:.005em;
    cursor:pointer;text-decoration:none;
    transition:background .18s ease,box-shadow .18s ease,transform .12s ease,border-color .18s ease;
    font-family:inherit;line-height:1.2;
    box-shadow:0 1px 0 rgba(15,23,42,.02), 0 6px 16px rgba(var(--adf-brand-rgb),.18);
}
.adforest-add-new-page .ad-post-btns-box .next-btn:hover,
.adforest-add-new-page .ad-post-btns-box button[type=submit]:hover,
.adforest-add-new-page #ad_post_submit_button:hover{
    background:var(--adf-brand-hover) !important;border-color:var(--adf-brand-hover) !important;
    box-shadow:0 1px 0 rgba(15,23,42,.02), 0 10px 22px rgba(var(--adf-brand-rgb),.28);
    transform:translateY(-1px);
}
.adforest-add-new-page .ad-post-btns-box .next-btn:active,
.adforest-add-new-page .ad-post-btns-box button[type=submit]:active{transform:translateY(0);}
.adforest-add-new-page .ad-post-btns-box .next-btn:disabled,
.adforest-add-new-page .ad-post-btns-box button[type=submit]:disabled,
.adforest-add-new-page #ad_post_submit_button:disabled{opacity:.55;cursor:not-allowed;transform:none;box-shadow:none;}

.adforest-add-new-page .ad-post-btns-box .prev-btn{
    background:#fff !important;color:var(--adf-ink-2) !important;
    border:1.5px solid var(--adf-line) !important;box-shadow:var(--adf-shadow-sm);
}
.adforest-add-new-page .ad-post-btns-box .prev-btn:hover{
    background:var(--adf-bg-soft) !important;color:var(--adf-ink-1) !important;
    border-color:#cbd2dc !important;transform:translateY(-1px);
}
.adforest-add-new-page .ad-post-btns-box .next-btn::after,
.adforest-add-new-page .ad-post-btns-box button[type=submit]::after,
.adforest-add-new-page #ad_post_submit_button::after{
    content:"\f061";font-family:"Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:12px;
}
.adforest-add-new-page .ad-post-btns-box .prev-btn::before{
    content:"\f060";font-family:"Font Awesome 5 Free","FontAwesome";font-weight:900;font-size:12px;
}

/* If only Next (no Prev) is present, push Next to the right edge */
.adforest-add-new-page .ad-post-btns-box .btn-adpost-start{margin-left:auto;}

/* Package cards (rendered into #ad_post_packages_container) */
.adforest-add-new-page .package-card,
.adforest-add-new-page .package-radio-box,
.adforest-add-new-page #ad_post_packages_container > div{
    background:var(--adf-card);border:1.5px solid var(--adf-line);border-radius:var(--adf-radius);
    padding:16px 20px;margin-bottom:12px;cursor:pointer;
    transition:border-color .18s ease,box-shadow .18s ease,background .18s ease;
}
.adforest-add-new-page .package-card:hover,
.adforest-add-new-page .package-radio-box:hover{border-color:rgba(var(--adf-brand-rgb),.45);box-shadow:var(--adf-shadow);}
.adforest-add-new-page .package-card.is-selected,
.adforest-add-new-page .package-card.active,
.adforest-add-new-page .package-radio-box.is-selected{border-color:var(--adf-brand);background:rgba(var(--adf-brand-rgb),.04);}

/* Pill-style radios for condition/warranty/ad_type */
.adforest-add-new-page .ad-post-tab-box input[type=radio]+label,
.adforest-add-new-page .ad-post-tab-box .form-check-inline label{
    cursor:pointer;border:1.5px solid var(--adf-line);border-radius:999px;
    padding:9px 18px;background:#fff;color:var(--adf-ink-2);font-weight:600;font-size:13px;
    margin:0 8px 8px 0;transition:all .15s ease;display:inline-flex;align-items:center;gap:6px;
}
.adforest-add-new-page .ad-post-tab-box input[type=radio]+label:hover{border-color:#cbd2dc;}
.adforest-add-new-page .ad-post-tab-box input[type=radio]:checked+label{
    background:var(--adf-brand);color:var(--adf-brand-text);border-color:var(--adf-brand);
    box-shadow:0 4px 12px rgba(var(--adf-brand-rgb),.22);
}

/* Checkboxes — modernize the default checkbox without breaking
 * the underlying <input>. We rely on input + label adjacent. */
.adforest-add-new-page .ad-post-tab-box input[type=checkbox]{accent-color:var(--adf-brand);width:18px;height:18px;}

/* Dropzone — premium */
.adforest-add-new-page .ad_post_image_container,
.adforest-add-new-page #dropzone_video{
    position:relative;
    border:2px dashed #d1d8e1 !important;background:linear-gradient(180deg,#fcfdff 0%,#f5f7fc 100%) !important;
    border-radius:var(--adf-radius-lg) !important;padding:36px 24px !important;text-align:center;
    transition:border-color .2s ease,background .2s ease,transform .2s ease;
    min-height:220px;display:flex;align-items:center;justify-content:center;
}
.adforest-add-new-page .ad_post_image_container.dz-drag-hover,
.adforest-add-new-page #dropzone_video.dz-drag-hover,
.adforest-add-new-page .ad_post_image_container:hover,
.adforest-add-new-page #dropzone_video:hover{
    border-color:var(--adf-brand) !important;
    background:linear-gradient(180deg, rgba(var(--adf-brand-rgb),.04) 0%, rgba(var(--adf-brand-rgb),.07) 100%) !important;
}
.adforest-add-new-page .ad_post_image_container .dz-message,
.adforest-add-new-page #dropzone_video .dz-message{
    color:var(--adf-ink-3);font-size:15px;margin:0;display:flex;flex-direction:column;align-items:center;gap:10px;
}
.adforest-add-new-page .ad_post_image_container .dz-message::before,
.adforest-add-new-page #dropzone_video .dz-message::before{
    content:"";display:block;width:64px;height:64px;margin:0 auto 4px;
    background:rgba(var(--adf-brand-rgb),.1);border-radius:50%;
    background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='%23<?php echo esc_attr(ltrim($theme_btn_color, '#')); ?>' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='17 8 12 3 7 8'/%3E%3Cline x1='12' y1='3' x2='12' y2='15'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:center center;background-size:28px 28px;
}
.adforest-add-new-page .ad_post_image_container .dz-message > *,
.adforest-add-new-page #dropzone_video .dz-message > *{color:var(--adf-ink-2);}
.adforest-add-new-page .adf-dz-title{display:block;font-size:16px;font-weight:700;color:var(--adf-ink-1);}
.adforest-add-new-page .adf-dz-sub{display:block;font-size:13px;color:var(--adf-ink-4);margin-top:2px;}

/* Dropzone previews */
.adforest-add-new-page .ad_post_image_container .dz-preview{
    background:#fff;border:1px solid var(--adf-line);border-radius:var(--adf-radius);
    padding:8px;margin:8px;box-shadow:var(--adf-shadow-sm);overflow:hidden;
}
.adforest-add-new-page .ad_post_image_container .dz-preview .dz-image{border-radius:var(--adf-radius-sm);overflow:hidden;}
.adforest-add-new-page .ad_post_image_container .dz-preview .dz-progress{background:var(--adf-line-2);border-radius:999px;height:6px;overflow:hidden;}
.adforest-add-new-page .ad_post_image_container .dz-preview .dz-progress .dz-upload{background:var(--adf-brand);}
.adforest-add-new-page .ad_post_image_container .dz-preview .dz-remove{
    color:#fff !important;background:rgba(239,68,68,.92);border-radius:999px;padding:4px 10px;
    font-size:12px;font-weight:600;text-decoration:none;
}
.adforest-add-new-page .ad_post_image_container .dz-preview .dz-error-mark{color:#ef4444;}
.adforest-add-new-page .ad_post_image_container .dz-preview .dz-success-mark{color:#10b981;}

/* Map */
.adforest-add-new-page #adforest_map,
.adforest-add-new-page .map-container,
.adforest-add-new-page #leaflet-map-container,
.adforest-add-new-page .leaflet-container{
    border-radius:var(--adf-radius);overflow:hidden;border:1px solid var(--adf-line);min-height:320px;
}

/* Location/Contact step layout — preserve Bootstrap row/col widths.
 * The shortcode emits a mix of col-lg-12 (address, map) and col-lg-6
 * (lat/long, phone/name) inside the same .row, plus a no-col Featured
 * card. A grid-override here would ignore those widths and squash the
 * map / featured card. Keep the native flex-row behaviour and just
 * tighten the vertical rhythm. */
.adforest-add-new-page #v-pills-contact .ad-post-tab-box > .row{
    row-gap:16px;
}
.adforest-add-new-page #v-pills-contact .ad-post-tab-box > .row > .make-feature{
    flex:0 0 100%;max-width:100%;width:100%;
}

/* Validation / alerts */
.adforest-add-new-page .alert{border-radius:var(--adf-radius-sm);font-size:13px;border:1px solid var(--adf-line);padding:12px 16px;}
.adforest-add-new-page .alert-info{background:rgba(var(--adf-brand-rgb),.06);color:var(--adf-ink-1);border-color:rgba(var(--adf-brand-rgb),.18);}
.adforest-add-new-page .parsley-errors-list{list-style:none;margin:6px 0 0;padding:0;}
.adforest-add-new-page .parsley-errors-list li{color:#ef4444;font-size:12.5px;font-weight:500;}
.adforest-add-new-page .parsley-error{border-color:#ef4444 !important;box-shadow:0 0 0 4px rgba(239,68,68,.10) !important;}

/* Bidding accordion / sub-headers */
.adforest-add-new-page .card-header.sub-header,
.adforest-add-new-page .sub-header{
    background:var(--adf-bg-soft);border:1px solid var(--adf-line);border-radius:var(--adf-radius);
    padding:14px 18px;font-weight:700;color:var(--adf-ink-2);margin:6px 0 10px;
}

/* Terms */
.adforest-add-new-page .terms-box,
.adforest-add-new-page .checkbox-section{margin:22px 0 4px;color:var(--adf-ink-3);font-size:13.5px;display:flex;align-items:flex-start;gap:10px;}
.adforest-add-new-page .terms-box a{color:var(--adf-brand);font-weight:700;text-decoration:none;}
.adforest-add-new-page .terms-box a:hover{text-decoration:underline;}

/* Terms-and-conditions checkbox — the shortcode emits a `.pretty` checkbox
   (`<div class="pretty p-default p-curve">` inside `.skin-minimal.check-detail`),
   but prettycheckbox.css isn't reliably enqueued here. Self-style it so the
   checkbox always renders cleanly (visual mirrors the Featured card box). */
.adforest-add-new-page .skin-minimal.check-detail{margin:18px 0 6px;}
.adforest-add-new-page .skin-minimal.check-detail ul.list{list-style:none;margin:0;padding:0;}
.adforest-add-new-page .skin-minimal.check-detail ul.list > li{margin:0;padding:0;}
.adforest-add-new-page .skin-minimal.check-detail .pretty{
    display:flex;align-items:center;gap:10px;
    position:relative;margin:0;padding:0;font-size:13.5px;color:var(--adf-ink-2);
}
/* Constrain the invisible input so the hit area is the visible 18×18
   box only (vertically centered inside `.pretty`), nothing wider, nothing
   below. `top:0 + bottom:0 + margin:auto 0` is the reliable vertical
   centering pattern — `top:50%; transform:translateY(-50%)` was getting
   overridden by lower-specificity rules elsewhere in the cascade.
   Every property carries `!important` defensively because the page is
   under multiple stylesheets that style native checkboxes in unrelated
   contexts. */
.adforest-add-new-page .skin-minimal.check-detail .pretty input[type="checkbox"]{
    position:absolute !important;
    opacity:0 !important;
    left:0 !important;
    top:0 !important;
    bottom:0 !important;
    margin:auto 0 !important;
    width:18px !important;
    height:18px !important;
    padding:0 !important;
    transform:none !important;
    cursor:pointer !important;
    z-index:2 !important;
}
.adforest-add-new-page .skin-minimal.check-detail .pretty .state{
    display:flex;align-items:center;gap:10px;margin:0;position:relative;
}
.adforest-add-new-page .skin-minimal.check-detail .pretty .state::before{
    content:"";flex-shrink:0;width:18px;height:18px;
    border:1.5px solid rgba(15,23,42,.30);border-radius:4px;
    background:#fff;transition:border-color .15s ease,background .15s ease;
}
.adforest-add-new-page .skin-minimal.check-detail .pretty:hover .state::before{
    border-color:var(--adf-brand);
}
.adforest-add-new-page .skin-minimal.check-detail .pretty input[type="checkbox"]:checked + .state::before{
    background:var(--adf-brand);border-color:var(--adf-brand);
}
.adforest-add-new-page .skin-minimal.check-detail .pretty .state::after{
    content:"";position:absolute;left:5px;top:50%;
    width:8px;height:5px;border:2px solid #fff;border-top:0;border-right:0;
    transform:translateY(-70%) rotate(-45deg);opacity:0;transition:opacity .15s ease;
    pointer-events:none;
}
.adforest-add-new-page .skin-minimal.check-detail .pretty input[type="checkbox"]:checked ~ .state::after,
.adforest-add-new-page .skin-minimal.check-detail .pretty input[type="checkbox"]:checked + .state::after{
    opacity:1;
}
.adforest-add-new-page .skin-minimal.check-detail .pretty .state label{
    margin:0;font-weight:500;color:var(--adf-ink-2);line-height:1.4;cursor:pointer;
}
.adforest-add-new-page .skin-minimal.check-detail .pretty .state label a{
    color:var(--adf-brand);font-weight:700;text-decoration:none;
}
.adforest-add-new-page .skin-minimal.check-detail .pretty .state label a:hover{
    text-decoration:underline;
}
/* Suppress the Pretty Checkbox library's own box drawing.
   pretty-checkbox.css draws its checkbox on `.pretty .state label::before`
   and `::after`, but we render our own on `.state::before/::after` above.
   Without this override both render and the user sees a phantom empty
   checkbox sitting beside the real one. */
.adforest-add-new-page .skin-minimal.check-detail .pretty .state label::before,
.adforest-add-new-page .skin-minimal.check-detail .pretty .state label::after{
    content:none !important;
    display:none !important;
}

/* Currency / location box layouts */
.adforest-add-new-page .location-box,
.adforest-add-new-page .currency-box{margin-bottom:20px;}

/* Loading overlay (existing #sb_loading) */
.adforest-add-new-page #sb_loading{z-index:10;}

/* ===== Dynamic / additional fields ====================== */

/* Child category cascades — each AJAX-injected child sits in its
 * own row with consistent spacing. */
.adforest-add-new-page #child-category-container{display:flex;flex-direction:column;gap:14px;margin-top:14px;}
.adforest-add-new-page #child-category-container > *{margin:0 !important;}

/* Container that holds category-driven custom fields. Renders as
 * a tidy two-column grid at lg+, single column on mobile. */
.adforest-add-new-page #custom_field_container,
.adforest-add-new-page #cat_template_html{
    display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:16px 20px;
    margin-top:8px;
}
.adforest-add-new-page #custom_field_container > *,
.adforest-add-new-page #cat_template_html > *{margin:0 !important;}
.adforest-add-new-page #custom_field_container .full-width,
.adforest-add-new-page #cat_template_html .full-width,
.adforest-add-new-page #custom_field_container .field-full,
.adforest-add-new-page #cat_template_html .field-full,
.adforest-add-new-page #custom_field_container > .field-box.full,
.adforest-add-new-page #cat_template_html > .field-box.full{grid-column:1 / -1;}

/* AI intent block (renders inside Categories step when enabled) */
.adforest-add-new-page #ai-intent-fields-container{
    background:var(--adf-bg-soft);border:1px dashed var(--adf-line);border-radius:var(--adf-radius);
    padding:18px 20px;margin-top:18px;
}
.adforest-add-new-page #ai-intent-fields-container .ai-intent-separator{margin:0 0 12px !important;padding-top:0 !important;border-top:0 !important;}
.adforest-add-new-page #ai-intent-fields-container h4{font-size:14px !important;font-weight:700 !important;color:var(--adf-ink-1) !important;margin:0 0 4px !important;}
.adforest-add-new-page #ai-intent-fields-container .ai-intent-description{font-size:12.5px !important;color:var(--adf-ink-4) !important;margin:0 0 14px !important;}

/* Package picker container */
.adforest-add-new-page #ad_post_packages_container{display:flex;flex-direction:column;gap:10px;margin-top:8px;}
.adforest-add-new-page #ad_post_packages_container .package-label{margin-top:0;}

/* Tags & video link row */
.adforest-add-new-page #tags_and_video_link_box{display:grid;grid-template-columns:1fr;gap:14px;}
.adforest-add-new-page #tags_and_video_link_box .tagsinput{
    border:1.5px solid var(--adf-line) !important;border-radius:10px !important;
    min-height:var(--adf-input-h);padding:8px 10px !important;background:#fff !important;
}
.adforest-add-new-page #tags_and_video_link_box .tagsinput span.tag{
    background:rgba(var(--adf-brand-rgb),.10) !important;color:var(--adf-brand) !important;
    border:0 !important;border-radius:999px !important;padding:4px 12px !important;
    font-size:12.5px !important;font-weight:600 !important;
}
.adforest-add-new-page #tags_and_video_link_box .tagsinput span.tag a{color:var(--adf-brand) !important;}

/* Condition & warranty group */
.adforest-add-new-page #ad_condition_and_warranty_box{
    display:grid;grid-template-columns:1fr 1fr;gap:16px 20px;margin:14px 0 8px;
}
.adforest-add-new-page #ad_condition_and_warranty_box > *{margin:0 !important;}
@media (max-width:600px){
    .adforest-add-new-page #ad_condition_and_warranty_box{grid-template-columns:1fr;}
    .adforest-add-new-page #custom_field_container,
    .adforest-add-new-page #cat_template_html{grid-template-columns:1fr;}
}

/* Currency + price grid */
.adforest-add-new-page .currency-box,
.adforest-add-new-page .location-box{margin-bottom:18px;}

/* Help text / hint paragraphs near fields */
.adforest-add-new-page .ad-post-tab-box .help-block,
.adforest-add-new-page .ad-post-tab-box small.form-text,
.adforest-add-new-page .ad-post-tab-box .field-hint{
    display:block;font-size:12px;color:var(--adf-mute);margin-top:6px;line-height:1.4;
}

/* Featured / make-feature accent card
   The underlying shortcode emits a nested Bootstrap grid inside `.card.make-feature`:
     .make-feature > .no-padding.col-* > .pricing-list > .row > .col-* > (h3 + .pretty.make_featured_box)
   We reset that nesting to a clean two-row layout — heading row up top, checkbox
   row underneath — without touching the plugin's markup or JS hooks. */
.adforest-add-new-page .make-feature{
    background:rgba(var(--adf-brand-rgb),.05);
    border:1px solid rgba(var(--adf-brand-rgb),.18);
    border-radius:var(--adf-radius);
    padding:18px 20px;margin:14px 0;
}
.adforest-add-new-page .make-feature > [class*="col-"],
.adforest-add-new-page .make-feature .pricing-list,
.adforest-add-new-page .make-feature .pricing-list > .row,
.adforest-add-new-page .make-feature .pricing-list > .row > [class*="col-"]{
    padding:0;margin:0;width:100%;max-width:100%;flex:none;
    display:block;
}
.adforest-add-new-page .make-feature h3{
    display:flex;flex-wrap:wrap;align-items:baseline;gap:6px 12px;
    margin:0 0 12px;font-size:16px;font-weight:700;color:var(--adf-text);line-height:1.3;
}
.adforest-add-new-page .make-feature h3 small{
    font-size:12.5px;font-weight:600;color:var(--adf-brand);
    background:rgba(var(--adf-brand-rgb),.10);
    padding:3px 10px;border-radius:999px;line-height:1.4;
    white-space:nowrap;
}
/* Pretty-checkbox row — render our own checkbox visual so the layout
   doesn't depend on the prettycheckbox.css being enqueued on this page. */
.adforest-add-new-page .make-feature .make_featured_box{
    display:flex;align-items:center;gap:10px;
    position:relative;margin:0;padding:0;cursor:pointer;
}
.adforest-add-new-page .make-feature .make_featured_box input[type="checkbox"]{
    position:absolute;opacity:0;width:18px;height:18px;left:0;top:50%;
    transform:translateY(-50%);margin:0;cursor:pointer;z-index:2;
}
.adforest-add-new-page .make-feature .make_featured_box .state{
    display:flex;align-items:center;gap:10px;margin:0;
}
.adforest-add-new-page .make-feature .make_featured_box .state::before{
    content:"";flex-shrink:0;width:18px;height:18px;
    border:1.5px solid rgba(15,23,42,.30);border-radius:4px;
    background:#fff;transition:border-color .15s ease,background .15s ease;
}
.adforest-add-new-page .make-feature .make_featured_box:hover .state::before{
    border-color:var(--adf-brand);
}
.adforest-add-new-page .make-feature .make_featured_box input[type="checkbox"]:checked + .state::before{
    background:var(--adf-brand);border-color:var(--adf-brand);
}
.adforest-add-new-page .make-feature .make_featured_box .state::after{
    content:"";position:absolute;left:5px;top:50%;
    width:8px;height:5px;border:2px solid #fff;border-top:0;border-right:0;
    transform:translateY(-70%) rotate(-45deg);opacity:0;transition:opacity .15s ease;
    pointer-events:none;
}
.adforest-add-new-page .make-feature .make_featured_box input[type="checkbox"]:checked ~ .state::after,
.adforest-add-new-page .make-feature .make_featured_box input[type="checkbox"]:checked + .state::after{
    opacity:1;
}
.adforest-add-new-page .make-feature .make_featured_box label{
    margin:0;font-size:13.5px;line-height:1.45;color:var(--adf-text);
    font-weight:500;cursor:pointer;
}
.adforest-add-new-page .make-feature i{color:var(--adf-brand);font-size:18px;}

/* Empty / fallback notice when shortcode plugin isn't active */
.adforest-add-fallback{padding:60px 24px;text-align:center;color:var(--adf-mute);background:var(--adf-card);border-radius:var(--adf-radius-lg);}
.adforest-add-fallback i{font-size:30px;color:#cbd5e1;margin-bottom:10px;display:block;}
.adforest-add-fallback p{margin:0 0 12px;font-size:14px;}
.adforest-add-fallback a{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:10px 18px;border-radius:var(--adf-radius-sm);font-weight:700;font-size:13px;text-decoration:none;}

/* Responsive ========================================== */
@media (max-width:991px){
    .adforest-add-header__text h1{font-size:24px;}
    .adforest-stepper{padding:16px 16px 18px;}
    .adforest-stepper__meta{margin-bottom:12px;}
    .adforest-stepper__heading{display:none;}
    .adforest-stepper__line,
    .adforest-stepper__line-fill{
        position:absolute;top:auto;bottom:30px;display:block;left:8%;right:8%;
    }
    .adforest-stepper__track{gap:6px;}
    .adforest-stepper__num{width:38px;height:38px;font-size:14px;}
    .adforest-stepper__label strong{font-size:12.5px;}
    .adforest-stepper__label span{display:none;}
    .adforest-add-new-page .ad-post-tab-content{padding:22px 20px;}
    .adforest-add-new-page #v-pills-contact .ad-post-tab-box > .row{grid-template-columns:1fr;}
}
@media (max-width:600px){
    .adforest-account-page.adforest-add-new-page{padding:18px 0 40px;}
    .adforest-add-header{margin-bottom:14px;}
    .adforest-add-header__text h1{font-size:20px;}
    .adforest-add-header__text p{font-size:13px;}
    .adforest-add-new-page .adforest-account-nav{padding:6px;border-radius:14px;margin-bottom:18px;}
    .adforest-add-new-page .adforest-account-nav a{padding:8px 14px;font-size:13px;}
    .adforest-stepper{padding:14px;}
    .adforest-stepper__line,
    .adforest-stepper__line-fill{display:none;}
    .adforest-stepper__track{display:flex;overflow-x:auto;scrollbar-width:none;padding-bottom:4px;}
    .adforest-stepper__track::-webkit-scrollbar{display:none;}
    .adforest-stepper__item{flex:0 0 auto;min-width:80px;}
    .adforest-add-new-page .ad-post-tab-content{padding:18px 14px;}
    .adforest-add-new-page .ad-post-tab-box > h3{font-size:18px;}
    .adforest-add-new-page .ad-post-btns-box{flex-direction:column-reverse;align-items:stretch;}
    .adforest-add-new-page .ad-post-btns-box .next-btn,
    .adforest-add-new-page .ad-post-btns-box .prev-btn,
    .adforest-add-new-page .ad-post-btns-box button[type=submit]{width:100%;}
    .adforest-add-new-page .ad_post_image_container,
    .adforest-add-new-page #dropzone_video{min-height:180px;padding:26px 16px !important;}
    .adforest-add-new-page #adforest_map,
    .adforest-add-new-page .leaflet-container{min-height:240px;}
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above.
 * ========================================================== */
body.rtl .adforest-add-new-page .ad-post-tab-box > h3{padding-left:0;padding-right:14px;}
body.rtl .adforest-add-new-page .ad-post-tab-box > h3::before{left:auto;right:0;}
body.rtl .adforest-add-new-page .ad-post-tab-box > h3 + span,
body.rtl .adforest-add-new-page .ad-post-tab-box > h3 + p{margin-left:0;margin-right:14px;}
body.rtl .adforest-add-new-page .ad-post-tab-box .label-box .category-box-label .required{margin-right:0;margin-left:4px;}
body.rtl .adforest-add-new-page .ad-post-tab-box .label-box label .required,
body.rtl .adforest-add-new-page .ad-post-tab-box label .required{margin-left:0;margin-right:3px;}
body.rtl .adforest-add-new-page .ad-post-tab-box select.default-select,
body.rtl .adforest-add-new-page .ad-post-tab-box select{
    background-position:left 16px center !important;
    padding-right:16px !important;padding-left:42px !important;
}
body.rtl .adforest-add-new-page .ad-post-tab-box input[type=radio]+label,
body.rtl .adforest-add-new-page .ad-post-tab-box .form-check-inline label{margin:0 0 8px 8px;}
body.rtl .adforest-add-new-page .ad-post-btns-box .btn-adpost-start{margin-left:0;margin-right:auto;}
body.rtl .adforest-add-new-page .skin-minimal.check-detail .pretty input[type="checkbox"]{left:auto;right:0;}
body.rtl .adforest-add-new-page .skin-minimal.check-detail .pretty .state::after{left:auto;right:5px;
    transform:translateY(-70%) rotate(45deg);border:2px solid #fff;border-top:0;border-left:0;border-right:2px solid #fff;border-bottom:2px solid #fff;}
body.rtl .adforest-add-new-page .make-feature .make_featured_box input[type="checkbox"]{left:auto;right:0;}
body.rtl .adforest-add-new-page .make-feature .make_featured_box .state::after{left:auto;right:5px;
    transform:translateY(-70%) rotate(45deg);border:2px solid #fff;border-top:0;border-left:0;border-right:2px solid #fff;border-bottom:2px solid #fff;}
@media (max-width:991px){
    body.rtl .adforest-stepper__line,
    body.rtl .adforest-stepper__line-fill{left:8%;right:8%;}
}
</style>

<div class="adforest-account-page adforest-add-new-page">
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
        <div class="adforest-add-header">
            <div class="adforest-add-header__text">
                <h1><?php esc_html_e('Post a New Ad', 'adforest'); ?></h1>
                <p><?php esc_html_e('Follow the steps below to list your ad — it only takes a couple of minutes.', 'adforest'); ?></p>
            </div>
        </div>

        <!-- Stepper -->
        <div class="adforest-stepper" role="navigation" aria-label="<?php esc_attr_e('Post-ad steps', 'adforest'); ?>">
            <div class="adforest-stepper__meta">
                <span class="adforest-stepper__pill">
                    <?php
                    printf(
                        /* translators: 1: current step number, 2: total steps */
                        esc_html__('Step %1$s of %2$s', 'adforest'),
                        '<strong class="adforest-stepper__current">1</strong>',
                        esc_html(count($wizard_steps))
                    );
                    ?>
                </span>
                <span class="adforest-stepper__heading"><?php esc_html_e('Listing details', 'adforest'); ?></span>
            </div>
            <div class="adforest-stepper__track" style="--adf-progress:0%;">
                <span class="adforest-stepper__line" aria-hidden="true"></span>
                <span class="adforest-stepper__line-fill" aria-hidden="true"></span>
                <?php foreach ($wizard_steps as $i => $step) :
                    $is_first = ($i === 0);
                    $class    = $is_first ? 'is-active' : '';
                    $number   = $i + 1;
                    ?>
                    <div class="adforest-stepper__item <?php echo esc_attr($class); ?>"
                         data-step-index="<?php echo esc_attr($i); ?>"
                         role="button" tabindex="0">
                        <span class="adforest-stepper__num"><?php echo esc_html($number); ?></span>
                        <span class="adforest-stepper__label">
                            <strong><?php echo esc_html($step['label']); ?></strong>
                            <span><?php echo esc_html($step['sub']); ?></span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Form card -->
        <div class="adforest-add-card">
            <?php
            if ($shortcode_available) {
                // Terms vars ($terms_switch, $terms_title, $terms_link) are
                // hoisted near the top of this template so both Classic and
                // Modern branches can use them.
                echo ad_post_short_base_func(array(
                    'form_title'   => '',
                    'terms_switch' => $terms_switch,
                    'terms_link'   => $terms_link,
                    'terms_title'  => $terms_title,
                ));
            } else {
                ?>
                <div class="adforest-add-fallback">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p><?php esc_html_e('The AdForest Elementor plugin is required to render the post-ad form. Please activate it, or use the classic Post Ad page.', 'adforest'); ?></p>
                    <?php if ($classic_post_ad_url && $classic_post_ad_url !== '#') : ?>
                        <a href="<?php echo esc_url($classic_post_ad_url); ?>">
                            <i class="fa fa-arrow-right"></i> <?php esc_html_e('Go to Classic Post Ad page', 'adforest'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<?php if ($shortcode_available) : ?>
<script>
/* Stepper ↔ Bootstrap pills bridge. The form rendered by
 * ad_post_short_base_func uses Bootstrap pill tabs to switch
 * between Categories / General Info / Ad Details / Contact. We
 * replace its sidebar nav with a horizontal stepper and forward
 * step clicks to the hidden original tab buttons — so the form's
 * own JS (validation, next/prev) keeps doing its job.
 */
jQuery(function ($) {
    var $page = $('.adforest-add-new-page');
    var $form = $page.find('#adforest-ad-post-form');
    if (!$form.length) return;

    // The original pill nav exposes 4 tab triggers by id.
    var tabIds       = ['v-pills-category-tab', 'v-pills-info-tab', 'v-pills-images-tab', 'v-pills-contact-tab'];
    // Use wp_json_encode rather than esc_js — esc_js HTML-encodes `&` to
    // `&amp;`, which then displays verbatim when fed to .text(). json_encode
    // produces a properly-quoted JS string with the raw `&` preserved.
    var stepHeadings = [
        <?php echo wp_json_encode(__('Pick a category', 'adforest')); ?>,
        <?php echo wp_json_encode(__('Listing details', 'adforest')); ?>,
        <?php echo wp_json_encode(__('Add photos & video', 'adforest')); ?>,
        <?php echo wp_json_encode(__('Location & contact', 'adforest')); ?>
    ];
    var $track   = $page.find('.adforest-stepper__track');
    var $current = $page.find('.adforest-stepper__current');
    var $heading = $page.find('.adforest-stepper__heading');

    function setActiveStep(index) {
        var $items = $page.find('.adforest-stepper__item');
        var total  = $items.length;
        $items.each(function (i) {
            var $it = $(this);
            $it.removeClass('is-active is-done');
            if (i < index) $it.addClass('is-done');
            else if (i === index) $it.addClass('is-active');
        });
        // Update the continuous progress line. The fill should reach the
        // CENTER of the active circle, which sits at (index / (total-1))
        // of the track's inner span (matching the items' grid columns).
        if (total > 1 && $track.length) {
            var pct = (index / (total - 1)) * 100;
            $track.css('--adf-progress', pct + '%');
        }
        if ($current.length) $current.text(index + 1);
        if ($heading.length && stepHeadings[index]) $heading.text(stepHeadings[index]);
    }

    function currentTabIndex() {
        for (var i = 0; i < tabIds.length; i++) {
            if ($('#' + tabIds[i]).hasClass('active')) return i;
        }
        return 0;
    }

    // Sync stepper whenever the underlying pills change.
    $page.on('shown.bs.tab', '.ad-post-tabs .nav-link', function () {
        setActiveStep(currentTabIndex());
    });
    // Some Bootstrap bundles don't dispatch shown.bs.tab — safety net.
    $page.on('click', '.next-btn, .prev-btn', function () {
        setTimeout(function () { setActiveStep(currentTabIndex()); }, 80);
    });

    // Step header click / keyboard activation → forward to the hidden
    // tab trigger. Respects the original form's gating: if the underlying
    // tab button is hidden (paid-post mode hides later tabs until a
    // package is picked), don't try to jump there.
    //
    // Inspect ONLY the button's own inline display style. jQuery `:hidden`
    // also returns true when any ancestor is `display:none`, and Modern
    // hides the parent `.ad-post-tabs` permanently via the stylesheet —
    // using `:hidden` would block every stepper click and prevent users
    // from clicking backward to revisit an earlier step (a regression
    // from Classic where pill clicks worked freely). The shortcode's
    // paid-post gate writes `style="display:none"` directly onto the
    // pill button and clears it via jQuery `.show()` once a package is
    // picked — so the own-inline check captures exactly that intent.
    function activateStep($item) {
        var idx  = parseInt($item.attr('data-step-index'), 10);
        var $btn = $('#' + tabIds[idx]);
        if (!$btn.length) return;
        if ($btn[0].style && $btn[0].style.display === 'none') return;
        $btn.trigger('click');
    }
    $page.on('click', '.adforest-stepper__item', function () { activateStep($(this)); });
    $page.on('keydown', '.adforest-stepper__item', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activateStep($(this)); }
    });

    /* ------------------------------------------------------------
     * Premium empty-state for the image / video Dropzones.
     * Dropzone.js owns the .dz-message element; we wait for it to
     * render, then replace its plain text with a two-line layout
     * (title + helper). Re-runs whenever Dropzone re-renders the
     * message (e.g. after the last file is removed).
     * ---------------------------------------------------------- */
    function enrichDropzoneMessage($dz, title, sub) {
        if (!$dz.length) return;
        var $msg = $dz.find('.dz-message').first();
        if (!$msg.length || $msg.find('.adf-dz-title').length) return;
        // Preserve the original text so screen readers still get it,
        // but visually replace with our two-line structure.
        var original = $.trim($msg.text());
        $msg.html(
            '<span class="adf-dz-title">' + title + '</span>' +
            '<span class="adf-dz-sub">' + sub + '</span>'
        ).attr('aria-label', original || title);
    }
    function applyDropzoneEnhancements() {
        enrichDropzoneMessage(
            $page.find('.ad_post_image_container'),
            '<?php echo esc_js(__('Drop your photos here', 'adforest')); ?>',
            '<?php echo esc_js(__('or click to browse — JPG / PNG, up to the limit set by your package.', 'adforest')); ?>'
        );
        enrichDropzoneMessage(
            $page.find('#dropzone_video'),
            '<?php echo esc_js(__('Drop a short video here', 'adforest')); ?>',
            '<?php echo esc_js(__('or click to browse — MP4 / WebM.', 'adforest')); ?>'
        );
    }
    // Run now and after a small delay, since Dropzone is bound later
    // in the page's lifecycle (after category selection in the AdForest
    // flow). Observe the form for new .dz-message insertions.
    applyDropzoneEnhancements();
    setTimeout(applyDropzoneEnhancements, 600);
    setTimeout(applyDropzoneEnhancements, 1800);
    if (typeof MutationObserver !== 'undefined') {
        var mo = new MutationObserver(function () { applyDropzoneEnhancements(); });
        mo.observe($form.get(0), {childList: true, subtree: true});
    }

    // Initial sync (handles a non-default active tab on edit).
    setActiveStep(currentTabIndex());
});
</script>
<?php endif; ?>

<?php get_footer(); ?>
