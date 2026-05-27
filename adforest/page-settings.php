<?php
/*
 * Template Name: AdForest - Settings (Modern)
 *
 * Standalone "Settings" page used by the Modern User Menu — the modern
 * counterpart of the classic Edit Profile (page-my_profile.php). Reuses
 * EVERY existing AJAX hook by preserving the original IDs / classes /
 * field names — the existing sb-custom.js handlers fire as-is. No
 * dashboard files modified. When the Modern User Menu toggle is OFF,
 * this template is unused and the dashboard works as before.
 *
 * @package Adforest
 */

if (function_exists('adforest_user_not_logged_in')) {
    adforest_user_not_logged_in();
}

global $adforest_theme;

$user_id   = get_current_user_id();
$user_info = get_userdata($user_id);
$user_pic  = function_exists('adforest_get_user_dp') ? adforest_get_user_dp($user_id) : '';

/* ----- User type (Individual / Dealer) ----- */
$user_type    = get_user_meta($user_id, '_sb_user_type', true);
$is_indiviual = ($user_type === 'Indiviual') ? 'selected="selected"' : '';
$is_dealer    = ($user_type === 'Dealer')    ? 'selected="selected"' : '';
$user_type_html = '<option value="">' . esc_html__('Select option', 'adforest') . '</option>'
    . '<option value="Indiviual" ' . $is_indiviual . '>' . esc_html__('Individual', 'adforest') . '</option>'
    . '<option value="Dealer" ' . $is_dealer . '>' . esc_html__('Dealer', 'adforest') . '</option>';

/* ----- Email read-only logic (matches classic profile page) ----- */
$readonly   = '';
$email_name = 'name="user_email"';
if (!empty($user_info->user_email)) {
    $readonly   = 'readonly';
    $email_name = '';
}

/* ----- Phone placeholder ----- */
$ph_placeholder = esc_html__('+CountrycodePhonenumber', 'adforest');

/* ----- Social profile fields ----- */
$social_profiles = function_exists('adforest_social_profiles') ? adforest_social_profiles() : array();
$sb_disable_linkedin_edit = !empty($adforest_theme['sb_disable_linkedin_edit']);

/* ----- Delete account markup (preserves original click handlers) ----- */
$delete_account_html = '';
if (!empty($adforest_theme['sb_new_user_delete_option'])) {
    $confirm_msg = esc_html__('Are you sure you want to delete this account?', 'adforest');
    $delete_account_html = sprintf(
        '<a class="remove_user_profile delete_site_user adforest-delete-account" href="javascript:void(0);" data-btn-ok-label="%s" data-btn-cancel-label="%s" data-toggle="confirmation" data-singleton="true" data-title="%s" data-content="" data-user-id="%d" title="%s" onclick="return confirm(\'%s\');"><i class="fa fa-times-circle"></i> %s</a>',
            esc_attr__('Yes', 'adforest'),
            esc_attr__('No', 'adforest'),
            esc_attr($confirm_msg),
            (int) $user_id,
            esc_attr__('Delete Account?', 'adforest'),
            esc_js($confirm_msg),
            esc_html__('Delete Account', 'adforest')
    );
}

/* ----- Theme button colors → CSS variables (track Theme Options live) ----- */
$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover'])   ? $adforest_theme['opt-theme-btn-color']['hover']   : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
$_rgb_parts = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

/* ----- Sub-nav URLs (cross-link to other Modern pages where set) ----- */
$post_ad_page_id  = isset($adforest_theme['sb_post_ad_page'])  ? $adforest_theme['sb_post_ad_page']  : '';
$post_ad_page_id  = apply_filters('adforest_ad_post_verified_id', $post_ad_page_id);
$post_ad_url      = $post_ad_page_id ? get_permalink($post_ad_page_id) : '#';
$modern_post_ad_page_id = isset($adforest_theme['sb_modern_post_ad_page']) ? $adforest_theme['sb_modern_post_ad_page'] : '';
$modern_post_ad_url     = $modern_post_ad_page_id ? get_permalink($modern_post_ad_page_id) : $post_ad_url;
$packages_page_id = isset($adforest_theme['sb_packages_page']) ? $adforest_theme['sb_packages_page'] : '';
$packages_url     = $packages_page_id ? get_permalink($packages_page_id) : '#';
$profile_page_id  = isset($adforest_theme['sb_profile_page'])  ? $adforest_theme['sb_profile_page']  : '';
$dash_url         = $profile_page_id ? trailingslashit(get_permalink($profile_page_id)) : home_url('/');

$modern_listings_page_id  = isset($adforest_theme['sb_modern_my_listings_page']) ? $adforest_theme['sb_modern_my_listings_page'] : '';
$modern_listings_url      = $modern_listings_page_id ? get_permalink($modern_listings_page_id) : ($dash_url ? add_query_arg('page_type', 'my_ads', $dash_url) : '#');
$modern_pending_page_id   = isset($adforest_theme['sb_modern_awaiting_approval_page']) ? $adforest_theme['sb_modern_awaiting_approval_page'] : '';
$modern_pending_url       = $modern_pending_page_id ? get_permalink($modern_pending_page_id) : ($dash_url ? add_query_arg('page_type', 'inactive_ads', $dash_url) : '#');
$modern_favorites_page_id = isset($adforest_theme['sb_modern_favorites_page']) ? $adforest_theme['sb_modern_favorites_page'] : '';
$modern_favorites_url     = $modern_favorites_page_id ? get_permalink($modern_favorites_page_id) : ($dash_url ? add_query_arg('page_type', 'fav_ads', $dash_url) : '#');
$modern_invoices_page_id  = isset($adforest_theme['sb_modern_invoices_page']) ? $adforest_theme['sb_modern_invoices_page'] : '';
$modern_invoices_url      = $modern_invoices_page_id ? get_permalink($modern_invoices_page_id) : ($dash_url ? add_query_arg('page_type', 'invoices', $dash_url) : '#');
$modern_messages_page_id  = isset($adforest_theme['sb_modern_messages_page']) ? $adforest_theme['sb_modern_messages_page'] : '';
$modern_messages_url      = $modern_messages_page_id ? get_permalink($modern_messages_page_id) : ($dash_url ? add_query_arg('page_type', 'msg', $dash_url) : '#');
$modern_packages_page_id  = isset($adforest_theme['sb_modern_my_packages_page']) ? $adforest_theme['sb_modern_my_packages_page'] : '';
$modern_packages_url      = $modern_packages_page_id ? get_permalink($modern_packages_page_id) : ($dash_url ? add_query_arg('page_type', 'my_packages', $dash_url) : '#');

$account_nav = array(
    array('icon' => 'fa fa-plus-circle',     'label' => __('Add New',           'adforest'), 'url' => $modern_post_ad_url, 'active' => false),
    array('icon' => 'fa fa-clipboard-check', 'label' => __('Awaiting Approval', 'adforest'), 'url' => $modern_pending_url, 'active' => false),
    array('icon' => 'fa fa-receipt',         'label' => __('Invoices',          'adforest'), 'url' => $modern_invoices_url, 'active' => false),
    array('icon' => 'fa fa-list',            'label' => __('My Listings',       'adforest'), 'url' => $modern_listings_url, 'active' => false),
    array('icon' => 'fa fa-heart',           'label' => __('Favorites',         'adforest'), 'url' => $modern_favorites_url, 'active' => false),
    array('icon' => 'fa fa-envelope',        'label' => __('Messages',          'adforest'), 'url' => $modern_messages_url, 'active' => false),
    array('icon' => 'fa fa-box',             'label' => __('My Packages',       'adforest'), 'url' => $modern_packages_url, 'active' => false),
    array('icon' => 'fa fa-cog',             'label' => __('Profile Settings',  'adforest'), 'url' => get_permalink(), 'active' => true),
);

get_header();

/* Load helpers used by the classic profile form (location autocomplete, country list) */
if (function_exists('adforest_load_search_countries')) {
    adforest_load_search_countries();
}
wp_enqueue_script('google-map-callback');

$mapType           = function_exists('adforest_mapType') ? adforest_mapType() : '';
$location_input_id = ($mapType === 'leafletjs_map') ? 'sb_user_address_leaflet' : 'sb_user_address';
?>

<style id="adforest-settings-css">
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
.adforest-page-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px;}
.adforest-page-header h1{font-size:28px;font-weight:700;color:#1f2937;margin:0;}
.adforest-pkg-btn{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:11px 22px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;transition:background .15s ease,transform .15s ease,box-shadow .15s ease;}
.adforest-pkg-btn:hover{background:var(--adf-brand-hover);transform:translateY(-1px);box-shadow:0 4px 12px rgba(var(--adf-brand-rgb),.25);color:var(--adf-brand-text) !important;}

/* Settings stack */
.adforest-settings{display:flex;flex-direction:column;gap:14px;}
/* The wrapping <form> isn't a direct child grid item, so its inner sections
   wouldn't pick up the 14px gap. Mirror the same flex layout on the form. */
.adforest-settings > form{display:flex;flex-direction:column;gap:14px;margin:0;}

/* Accordion section (uses native <details>) */
.adforest-section{background:#fff;border-radius:12px;box-shadow:0 2px 6px rgba(17,24,39,.04);overflow:hidden;transition:box-shadow .2s ease;}
.adforest-section[open]{box-shadow:0 6px 18px rgba(17,24,39,.06);}
.adforest-section__head{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 26px;font-size:18px;font-weight:700;color:#1f2937;-webkit-tap-highlight-color:transparent;user-select:none;}
.adforest-section__head::-webkit-details-marker{display:none;}
.adforest-section__head::marker{display:none;}
.adforest-section__chevron{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:#f6f7fb;color:#6b7280;font-size:14px;flex-shrink:0;transition:background .15s ease,color .15s ease,transform .25s ease;}
.adforest-section[open] .adforest-section__chevron{background:var(--adf-brand);color:var(--adf-brand-text);transform:rotate(180deg);}
.adforest-section__body{padding:6px 26px 26px;border-top:1px solid #eef0f4;}

/* Form fields */
.adforest-field{margin-bottom:18px;}
.adforest-field__label{display:block;font-size:13px;font-weight:600;color:#1f2937;margin-bottom:8px;}
.adforest-field__label .req{color:#ef4444;margin-left:3px;}
.adforest-field input[type="text"],
.adforest-field input[type="email"],
.adforest-field input[type="password"],
.adforest-field input[type="url"],
.adforest-field textarea,
.adforest-field select{width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:11px 14px;font-size:14px;color:#1f2937;font-family:inherit;transition:border-color .15s ease,box-shadow .15s ease;}
.adforest-field input:focus,.adforest-field textarea:focus,.adforest-field select:focus{outline:none;border-color:var(--adf-brand);box-shadow:0 0 0 3px rgba(var(--adf-brand-rgb),.12);}
.adforest-field input[readonly]{background:#f9fafb;color:#6b7280;cursor:not-allowed;}
.adforest-field textarea{min-height:110px;resize:vertical;}

/* Two-column form rows */
.adforest-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;}
@media (max-width:700px){.adforest-grid-2{grid-template-columns:1fr;}}

/* Save button area */
.adforest-section__footer{display:flex;justify-content:flex-end;gap:10px;padding:18px 26px;border-top:1px solid #eef0f4;background:#fafbfc;margin:18px -26px -26px;}
.adforest-btn-primary{display:inline-flex;align-items:center;gap:8px;background:var(--adf-brand);color:var(--adf-brand-text) !important;padding:10px 22px;border-radius:8px;font-weight:600;font-size:14px;border:0;cursor:pointer;transition:background .15s ease,transform .15s ease,box-shadow .15s ease;text-decoration:none;}
.adforest-btn-primary:hover{background:var(--adf-brand-hover);transform:translateY(-1px);box-shadow:0 4px 10px rgba(var(--adf-brand-rgb),.25);}
.adforest-btn-primary i{font-size:13px;}

/* Avatar uploader */
.adforest-avatar-uploader{display:flex;align-items:center;gap:24px;padding:8px 0;}
.adforest-avatar-uploader__preview{position:relative;width:120px;height:120px;border-radius:50%;background:#f6f7fb;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;}
.adforest-avatar-uploader__preview img{width:100%;height:100%;object-fit:cover;display:block;}
.adforest-avatar-uploader__cam{position:absolute;bottom:6px;right:6px;width:32px;height:32px;border-radius:50%;background:var(--adf-brand);color:var(--adf-brand-text);display:inline-flex;align-items:center;justify-content:center;border:3px solid #fff;cursor:pointer;font-size:13px;}
.adforest-avatar-uploader__hint{display:flex;flex-direction:column;gap:4px;color:#6b7280;font-size:13px;line-height:1.5;}
.adforest-avatar-uploader__hint strong{color:#1f2937;font-size:14px;}

/* Social field with leading icon */
.adforest-social-field{position:relative;display:block;}
/* Use !important + chained class to outrank .adforest-field input[type="url"] (0,2,1) */
.adforest-field .adforest-social-field input,
.adforest-field .adforest-social-field input[type="url"]{padding-left:54px !important;}
.adforest-social-field__icon{position:absolute;left:1px;top:1px;bottom:1px;width:46px;display:inline-flex;align-items:center;justify-content:center;color:var(--adf-brand);background:rgba(var(--adf-brand-rgb),.08);border-radius:7px 0 0 7px;font-size:15px;pointer-events:none;line-height:1;}

/* Delete account row */
.adforest-delete-row{padding:18px 4px 0;}
.adforest-delete-account{color:#ef4444 !important;text-decoration:none;font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:color .15s ease;}
.adforest-delete-account:hover{color:#b91c1c !important;text-decoration:underline;}

/* Phone-related (whatsapp / viber checkboxes if shown) */
.adforest-checkboxes{display:flex;gap:18px;margin-top:10px;flex-wrap:wrap;}
.adforest-checkboxes label{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;cursor:pointer;}
.adforest-checkboxes input{accent-color:var(--adf-brand);}

@media (max-width:600px){
    .adforest-account-page{padding:20px 0 40px;}
    .adforest-page-header h1{font-size:22px;}
    .adforest-account-nav{padding:6px;border-radius:14px;margin-bottom:18px;}
    .adforest-account-nav a{padding:8px 14px;font-size:13px;}
    .adforest-section__head{padding:18px 20px;font-size:16px;}
    .adforest-section__body{padding:6px 20px 20px;}
    .adforest-section__footer{padding:14px 20px;margin:14px -20px -20px;}
    .adforest-avatar-uploader{flex-direction:column;align-items:flex-start;}
}

/* ============================================================
 * RTL overrides — flip directional rules when WordPress adds
 * `class="rtl"` to <body>. Keep this block at the end so it
 * cascades over everything above.
 * ========================================================== */
body.rtl .adforest-field__label .req{margin-left:0;margin-right:3px;}
body.rtl .adforest-avatar-uploader__cam{right:auto;left:6px;}
body.rtl .adforest-field .adforest-social-field input,
body.rtl .adforest-field .adforest-social-field input[type="url"]{padding-left:14px !important;padding-right:54px !important;}
body.rtl .adforest-social-field__icon{left:auto;right:1px;border-radius:0 7px 7px 0;}
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
            <h1><?php esc_html_e('Profile Settings', 'adforest'); ?></h1>
        </div>

        <div class="adforest-settings">

            <!-- The main profile form wraps Account Details + Social Links + Email so they share
                 the existing #sb_update_profile AJAX handler in sb-custom.js. -->
            <form id="sb_update_profile">

                <!-- ============ ACCOUNT DETAILS ============ -->
                <details class="adforest-section" open>
                    <summary class="adforest-section__head">
                        <span><?php esc_html_e('Account Details', 'adforest'); ?></span>
                        <span class="adforest-section__chevron"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
                    </summary>
                    <div class="adforest-section__body">
                        <div class="adforest-grid-2">
                            <div class="adforest-field">
                                <label class="adforest-field__label" for="sb_user_name"><?php esc_html_e('Display Name', 'adforest'); ?></label>
                                <input id="sb_user_name" name="sb_user_name" type="text"
                                       value="<?php echo esc_attr($user_info->display_name); ?>" />
                            </div>
                            <div class="adforest-field">
                                <label class="adforest-field__label"><?php esc_html_e('Email Address', 'adforest'); ?><span class="req">*</span></label>
                                <input <?php echo $email_name; ?> type="email" <?php echo esc_attr($readonly); ?>
                                    value="<?php echo esc_attr($user_info->user_email); ?>" />
                            </div>
                            <div class="adforest-field">
                                <label class="adforest-field__label" for="sb_user_contact"><?php esc_html_e('Phone Number', 'adforest'); ?><span class="req">*</span></label>
                                <input type="text" name="sb_user_contact" id="sb_user_contact"
                                       value="<?php echo esc_attr(get_user_meta($user_id, '_sb_contact', true)); ?>"
                                       placeholder="<?php echo esc_attr($ph_placeholder); ?>" />
                                <small></small>
                            </div>
                            <div class="adforest-field">
                                <label class="adforest-field__label"><?php esc_html_e('I AM', 'adforest'); ?><span class="req">*</span></label>
                                <select name="sb_user_type">
                                    <?php echo $user_type_html; ?>
                                </select>
                            </div>
                        </div>

                        <div class="adforest-field">
                            <label class="adforest-field__label"><?php esc_html_e('Address', 'adforest'); ?></label>
                            <input type="text"
                                   placeholder="<?php esc_attr_e('Enter your address', 'adforest'); ?>"
                                   name="sb_user_address" id="<?php echo esc_attr($location_input_id); ?>"
                                   autocomplete="on"
                                   value="<?php echo esc_attr(get_user_meta($user_id, '_sb_address', true)); ?>" />
                            <div id="suggestions-box" class="suggestions-box" style="position:absolute;background:#fff;z-index:9999;max-width:500px;max-height:200px;overflow-y:auto;"></div>
                        </div>

                        <div class="adforest-field">
                            <label class="adforest-field__label"><?php esc_html_e('Profile Description', 'adforest'); ?></label>
                            <textarea name="sb_user_intro" rows="5"
                                      placeholder="<?php esc_attr_e('Write something about yourself', 'adforest'); ?>"><?php echo esc_textarea(get_user_meta($user_id, '_sb_user_intro', true)); ?></textarea>
                        </div>

                        <?php if (!empty($adforest_theme['sb_show_whatsapp_intro']) && $adforest_theme['sb_show_whatsapp_intro'] == '1') : ?>
                            <div class="adforest-field">
                                <label class="adforest-field__label"><?php esc_html_e('Whatsapp Intro', 'adforest'); ?></label>
                                <textarea name="sb_user_whatsapp_intro" rows="4"><?php echo esc_textarea(get_user_meta($user_id, '_sb_user_whatsapp_intro', true)); ?></textarea>
                            </div>
                        <?php endif; ?>

                        <div class="adforest-section__footer">
                            <button type="submit" class="adforest-btn-primary main-btn">
                                <i class="fa fa-check" aria-hidden="true"></i>
                                <?php esc_html_e('Save Changes', 'adforest'); ?>
                            </button>
                        </div>
                    </div>
                </details>

                <!-- ============ SOCIAL LINKS ============ -->
                <?php if (!empty($adforest_theme['sb_enable_social_links']) && !empty($social_profiles)) : ?>
                <details class="adforest-section">
                    <summary class="adforest-section__head">
                        <span><?php esc_html_e('Social Links', 'adforest'); ?></span>
                        <span class="adforest-section__chevron"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
                    </summary>
                    <div class="adforest-section__body">
                        <div class="adforest-grid-2">
                            <?php
                            // Brand icons live in FA's "fab" family (FontAwesome 6+).
                            // The "fa fa-*" prefix only works for Solid/non-brand icons.
                            $social_icon_map = array(
                                'facebook'  => 'fab fa-facebook-f',
                                'twitter'   => 'fab fa-twitter',
                                'instagram' => 'fab fa-instagram',
                                'linkedin'  => 'fab fa-linkedin-in',
                                'youtube'   => 'fab fa-youtube',
                                'pinterest' => 'fab fa-pinterest-p',
                                'google'    => 'fab fa-google',
                                'skype'     => 'fab fa-skype',
                                'tumblr'    => 'fab fa-tumblr',
                                'tiktok'    => 'fab fa-tiktok',
                                'telegram'  => 'fab fa-telegram',
                                'whatsapp'  => 'fab fa-whatsapp',
                                'snapchat'  => 'fab fa-snapchat',
                                'reddit'    => 'fab fa-reddit',
                                'github'    => 'fab fa-github',
                                'flickr'    => 'fab fa-flickr',
                                'vimeo'     => 'fab fa-vimeo-v',
                                'dribbble'  => 'fab fa-dribbble',
                                'behance'   => 'fab fa-behance',
                            );
                            foreach ($social_profiles as $key => $label) :
                                $disabled = ($key === 'linkedin' && $sb_disable_linkedin_edit) ? 'disabled' : '';
                                $icon     = isset($social_icon_map[$key]) ? $social_icon_map[$key] : 'fa fa-link';
                                $value    = get_user_meta($user_id, '_sb_profile_' . $key, true);
                            ?>
                                <div class="adforest-field">
                                    <label class="adforest-field__label"><?php echo esc_html($label); ?></label>
                                    <div class="adforest-social-field">
                                        <span class="adforest-social-field__icon"><i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i></span>
                                        <input type="url" name="_sb_profile_<?php echo esc_attr($key); ?>"
                                               <?php echo $disabled; ?>
                                               value="<?php echo esc_attr($value); ?>"
                                               placeholder="<?php echo esc_attr(sprintf(__('Enter the url to your %s profile', 'adforest'), $label)); ?>" />
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="adforest-section__footer">
                            <button type="submit" class="adforest-btn-primary main-btn">
                                <i class="fa fa-check" aria-hidden="true"></i>
                                <?php esc_html_e('Save Changes', 'adforest'); ?>
                            </button>
                        </div>
                    </div>
                </details>
                <?php endif; ?>

                <!-- Hidden inputs the existing AJAX handler reads -->
                <input type="hidden" id="adforest_profile_msg" value="<?php esc_attr_e('Profile saved successfully.', 'adforest'); ?>" />
                <input type="hidden" id="sb-profile-token" value="<?php echo esc_attr(wp_create_nonce('sb_profile_secure')); ?>" />

            </form><!-- /#sb_update_profile -->


            <!-- ============ PROFILE IMAGE (separate block — uses #imgInp + #upload_user_dp) ============ -->
            <details class="adforest-section">
                <summary class="adforest-section__head">
                    <span><?php esc_html_e('Profile Image', 'adforest'); ?></span>
                    <span class="adforest-section__chevron"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
                </summary>
                <div class="adforest-section__body">
                    <div class="adforest-avatar-uploader">
                        <div class="adforest-avatar-uploader__preview user-dp-container" style="cursor:pointer;">
                            <?php if ($user_pic) : ?>
                                <img src="<?php echo esc_url($user_pic); ?>" alt="<?php esc_attr_e('Profile photo', 'adforest'); ?>" id="img-upload" />
                            <?php else : ?>
                                <i class="fa fa-user" style="font-size:32px;color:#9ca3af;"></i>
                                <img src="" alt="" id="img-upload" style="display:none;" />
                            <?php endif; ?>
                            <span class="adforest-avatar-uploader__cam edit-dp">
                                <i class="fa fa-camera" id="upload_user_dp" aria-hidden="true"></i>
                            </span>
                            <input type="file" id="imgInp" name="my_file_upload[]" accept="image/*"
                                   class="sb_files-data form-control" style="display:none;"
                                   data-security="<?php echo esc_attr(wp_create_nonce('upload_user_image_nonce')); ?>" />
                        </div>
                        <div class="adforest-avatar-uploader__hint">
                            <strong><?php esc_html_e('Add new photo', 'adforest'); ?></strong>
                            <span><?php esc_html_e('Click the camera icon to upload a new profile picture.', 'adforest'); ?></span>
                            <span><?php esc_html_e('Recommended: square image, JPG or PNG.', 'adforest'); ?></span>
                        </div>
                    </div>
                </div>
            </details>


            <!-- ============ CHANGE PASSWORD (uses existing #sb-change-password / #change_pwd) ============ -->
            <details class="adforest-section">
                <summary class="adforest-section__head">
                    <span><?php esc_html_e('Change Password', 'adforest'); ?></span>
                    <span class="adforest-section__chevron"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
                </summary>
                <div class="adforest-section__body">
                    <form id="sb-change-password">
                        <div class="adforest-grid-2">
                            <div class="adforest-field">
                                <label class="adforest-field__label" for="current_pass"><?php esc_html_e('Old Password', 'adforest'); ?></label>
                                <input type="password" name="current_pass" id="current_pass"
                                       placeholder="<?php esc_attr_e('Enter your old password', 'adforest'); ?>" />
                            </div>
                            <div class="adforest-field">
                                <label class="adforest-field__label" for="new_pass"><?php esc_html_e('New Password', 'adforest'); ?></label>
                                <input type="password" name="new_pass" id="new_pass"
                                       placeholder="<?php esc_attr_e('Enter your new password', 'adforest'); ?>" />
                            </div>
                        </div>
                        <div class="adforest-field">
                            <label class="adforest-field__label" for="con_new_pass"><?php esc_html_e('Confirm New Password', 'adforest'); ?></label>
                            <input type="password" name="con_new_pass" id="con_new_pass"
                                   placeholder="<?php esc_attr_e('Re-enter your new password', 'adforest'); ?>" />
                        </div>

                        <input type="hidden" id="sb-profile-reset-pass-token"
                               value="<?php echo esc_attr(wp_create_nonce('sb_profile_reset_pass_secure')); ?>" />

                        <div class="adforest-section__footer">
                            <button type="button" id="change_pwd" class="adforest-btn-primary dark-btn">
                                <i class="fa fa-lock" aria-hidden="true"></i>
                                <?php esc_html_e('Change Password', 'adforest'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </details>


            <!-- ============ DELETE ACCOUNT ============ -->
            <?php if ($delete_account_html) : ?>
                <div class="adforest-delete-row">
                    <?php echo $delete_account_html; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
/* Profile image uploader — standalone copy of the handler from
 * dashboard-custom.js. The dashboard JS is only enqueued on the
 * dashboard page template, so on this modern Settings page the
 * #upload_user_dp click and #imgInp change events have no listener
 * and the upload silently does nothing. Wires the same AJAX flow
 * (action: upload_user_pic) so the existing server endpoint and
 * pipe-delimited response format keep working unchanged.
 */
jQuery(function ($) {
    var $cam       = $('#upload_user_dp');
    var $fileInput = $('#imgInp');
    var $preview   = $('#img-upload');
    var $previewWrap = $('.user-dp-container');
    var ajaxUrl    = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

    if (!$cam.length || !$fileInput.length) return;

    function notifyError(msg) {
        if (typeof toastr !== 'undefined') {
            toastr.error(msg, '', {timeOut: 4000, closeButton: true, positionClass: 'toast-top-right'});
        } else {
            alert(msg);
        }
    }
    function notifySuccess(msg) {
        if (typeof toastr !== 'undefined') {
            toastr.success(msg, '', {timeOut: 3000, closeButton: true, positionClass: 'toast-top-right'});
        }
    }

    // Click anywhere on the avatar circle (or the camera icon) opens the
    // file picker. Guard against the click bubbling back from the file
    // input itself — without this the synthetic click bubbles up to the
    // wrapper and re-enters the handler → "too much recursion".
    $previewWrap.on('click', function (e) {
        if (e.target === $fileInput[0]) return;
        e.preventDefault();
        if ($fileInput.length) $fileInput[0].click();
    });

    $fileInput.on('change', function (e) {
        var files = e.target.files;
        if (!files || !files[0]) return;

        var fd = new FormData();
        fd.append('my_file_upload[0]', files[0]);
        fd.append('action', 'upload_user_pic');
        fd.append('security', $(this).data('security'));

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: fd,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $cam.css('opacity', 0.6);
            },
            success: function (res) {
                $cam.css('opacity', 1);
                var parts = (typeof res === 'string') ? res.split('|') : [];
                if ($.trim(parts[0]) === '1' && parts[1]) {
                    var url = parts[1];
                    // Update the inline preview and any avatars rendered on the page
                    if ($preview.length) {
                        $preview.attr('src', url).show();
                        $previewWrap.find('i.fa-user').hide();
                    }
                    $('#user_dp, .adt-user-avatar img, .adt-user-trigger img').attr('src', url);
                    notifySuccess('<?php echo esc_js(__('Profile picture updated.', 'adforest')); ?>');
                } else {
                    notifyError(parts[1] || '<?php echo esc_js(__('Upload failed. Please try again.', 'adforest')); ?>');
                }
                $fileInput.val('');
            },
            error: function () {
                $cam.css('opacity', 1);
                notifyError('<?php echo esc_js(__('Upload failed. Please try again.', 'adforest')); ?>');
                $fileInput.val('');
            }
        });
    });
});
</script>

<?php get_footer(); ?>
