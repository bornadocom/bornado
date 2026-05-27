<?php
/**
 * Modern Home footer — markup only (no wp_footer() or closing tags).
 *
 * Loaded two ways:
 *   1. By `footer.php` when admin picks "Footer Modern" in
 *      Theme Options → Footer Style (site-wide).
 *   2. By the root `footer-home-modern.php` wrapper, which is
 *      itself loaded by `get_footer('home-modern')` from the
 *      "AdForest - Home (Modern)" page template.
 *
 * Single source of truth for the modern footer markup + styles.
 *
 * @package Adforest
 */

global $adforest_theme;

$theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
$theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover']) ? $adforest_theme['opt-theme-btn-color']['hover'] : '#d6002a';
$theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
$_rgb_parts      = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
$theme_btn_rgb   = (is_array($_rgb_parts) && count($_rgb_parts) === 3 && $_rgb_parts[0] !== null) ? implode(',', $_rgb_parts) : '255,0,46';

$site_logo = isset($adforest_theme['sb_site_logo']['url']) && $adforest_theme['sb_site_logo']['url']
    ? $adforest_theme['sb_site_logo']['url']
    : (defined('ADFOREST_IMAGE_PATH') ? ADFOREST_IMAGE_PATH . '/adt-logo.svg' : get_template_directory_uri() . '/images/adt-logo.svg');

// Admin-overridable copy — fields live under Theme Options → Footer
// Settings (visible only when "Modern" footer style is selected).
// Each field has a sensible default so the footer never renders empty.
$about_text = !empty($adforest_theme['footer_modern_description'])
    ? $adforest_theme['footer_modern_description']
    : __('A modern, user-friendly classifieds marketplace built with AdForest. Find what you need, sell what you don\'t.', 'adforest');

$col1_title = !empty($adforest_theme['footer_modern_col1_title'])
    ? $adforest_theme['footer_modern_col1_title']
    : __('Quick Links', 'adforest');

$col2_title = !empty($adforest_theme['footer_modern_col2_title'])
    ? $adforest_theme['footer_modern_col2_title']
    : __('Help & Info', 'adforest');

$news_title = !empty($adforest_theme['footer_modern_newsletter_title'])
    ? $adforest_theme['footer_modern_newsletter_title']
    : __('Subscribe Newsletter', 'adforest');

$news_desc = !empty($adforest_theme['footer_modern_newsletter_desc'])
    ? $adforest_theme['footer_modern_newsletter_desc']
    : __('Subscribe our newsletter to get our latest updates & news.', 'adforest');

$news_placeholder = !empty($adforest_theme['footer_modern_newsletter_placeholder'])
    ? $adforest_theme['footer_modern_newsletter_placeholder']
    : __('Enter your email', 'adforest');

// Copyright supports %YEAR% / %SITE% placeholders so admins can write
// "© %YEAR% %SITE%. All rights reserved." without touching PHP.
$copyright_raw = !empty($adforest_theme['footer_modern_copyright'])
    ? $adforest_theme['footer_modern_copyright']
    : __('&copy; %YEAR% <a href="' . esc_url(home_url('/')) . '">%SITE%</a>. All Rights Reserved.', 'adforest');
$copyright_html = strtr($copyright_raw, [
    '%YEAR%' => date_i18n('Y'),
    '%SITE%' => esc_html(get_bloginfo('name')),
]);

// WPML translation for any of the modern footer texts when set.
if (function_exists('icl_t')) {
    $about_text       = icl_t('adforest_theme', 'footer_modern_description',             $about_text);
    $col1_title       = icl_t('adforest_theme', 'footer_modern_col1_title',              $col1_title);
    $col2_title       = icl_t('adforest_theme', 'footer_modern_col2_title',              $col2_title);
    $news_title       = icl_t('adforest_theme', 'footer_modern_newsletter_title',        $news_title);
    $news_desc        = icl_t('adforest_theme', 'footer_modern_newsletter_desc',         $news_desc);
    $news_placeholder = icl_t('adforest_theme', 'footer_modern_newsletter_placeholder',  $news_placeholder);
}

// Social URLs from theme options (best-effort — falls back gracefully)
$social_links = array();
foreach (array(
    'fa-facebook-f'  => 'facebook_url',
    'fa-instagram'   => 'instagram_url',
    'fa-twitter'     => 'twitter_url',
    'fa-linkedin-in' => 'linkedin_url',
) as $icon => $key) {
    if (!empty($adforest_theme[$key])) {
        $social_links[$icon] = $adforest_theme[$key];
    }
}
if (empty($social_links)) {
    $social_links = array(
        'fa-facebook-f'  => '#',
        'fa-instagram'   => '#',
        'fa-twitter'     => '#',
        'fa-linkedin-in' => '#',
    );
}

/**
 * Normalise the admin's page selection into a list of [label, url] pairs.
 * Redux returns either a comma-string of IDs or an array depending on how
 * the value was saved — accept both.
 */
if (!function_exists('adforest_hm_footer_resolve_pages')) {
    function adforest_hm_footer_resolve_pages($value)
    {
        if (empty($value)) {
            return array();
        }
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }
        if (!is_array($value)) {
            return array();
        }
        $links = array();
        foreach ($value as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) { continue; }
            $title = get_the_title($pid);
            $url   = get_permalink($pid);
            if ($title && $url) {
                $links[] = array('label' => $title, 'url' => $url);
            }
        }
        return $links;
    }
}

$col1_pages = adforest_hm_footer_resolve_pages($adforest_theme['footer_modern_col1_pages'] ?? '');
$col2_pages = adforest_hm_footer_resolve_pages($adforest_theme['footer_modern_col2_pages'] ?? '');

// App download band — toggle + admin-overridable copy and store URLs.
// Sits as a full-width band between the columns and the copyright bar.
$app_enabled = !isset($adforest_theme['footer_modern_app_enable']) || !empty($adforest_theme['footer_modern_app_enable']);
$app_title = !empty($adforest_theme['footer_modern_app_title'])
    ? $adforest_theme['footer_modern_app_title']
    : __('Get the App', 'adforest');
$app_desc = !empty($adforest_theme['footer_modern_app_desc'])
    ? $adforest_theme['footer_modern_app_desc']
    : __('Browse, post and chat on the go — download our mobile app.', 'adforest');
$app_gplay_url = !empty($adforest_theme['footer_modern_app_gplay_url']) ? $adforest_theme['footer_modern_app_gplay_url'] : '';
$app_ios_url   = !empty($adforest_theme['footer_modern_app_ios_url'])   ? $adforest_theme['footer_modern_app_ios_url']   : '';
if (function_exists('icl_t')) {
    $app_title = icl_t('adforest_theme', 'footer_modern_app_title', $app_title);
    $app_desc  = icl_t('adforest_theme', 'footer_modern_app_desc',  $app_desc);
}

// Trust strip — configurable via Theme Options. Each cell falls back
// to the original hard-coded copy if the admin leaves it blank.
$trust_enabled = !isset($adforest_theme['footer_modern_trust_enable']) || !empty($adforest_theme['footer_modern_trust_enable']);
$trust_defaults = array(
    1 => array('icon' => 'fa fa-tag',         'title' => __('100% Free', 'adforest'), 'desc' => __('Post ads with no hidden fees, ever.', 'adforest')),
    2 => array('icon' => 'fa fa-shield-alt',  'title' => __('Secure',    'adforest'), 'desc' => __('Every listing is reviewed before going live.', 'adforest')),
    3 => array('icon' => 'fa fa-handshake',   'title' => __('Trusted',   'adforest'), 'desc' => __('Thousands of buyers and sellers every day.', 'adforest')),
    4 => array('icon' => 'fa fa-headset',     'title' => __('Support',   'adforest'), 'desc' => __('Friendly help is one click away.', 'adforest')),
);
$trust_cells = array();
foreach ($trust_defaults as $i => $d) {
    // An explicitly-empty icon means the admin cleared the field to remove
    // this badge. Distinguish that from "key not set yet" (fresh install,
    // pre-save) — only the former should drop the cell.
    $raw_icon = array_key_exists("footer_modern_trust{$i}_icon", (array) $adforest_theme)
        ? trim((string) $adforest_theme["footer_modern_trust{$i}_icon"])
        : null;
    if ($raw_icon === '') {
        continue;
    }
    $icon  = ($raw_icon !== null && $raw_icon !== '') ? $raw_icon : $d['icon'];
    $title = !empty($adforest_theme["footer_modern_trust{$i}_title"]) ? $adforest_theme["footer_modern_trust{$i}_title"] : $d['title'];
    $desc  = !empty($adforest_theme["footer_modern_trust{$i}_desc"])  ? $adforest_theme["footer_modern_trust{$i}_desc"]  : $d['desc'];
    if (function_exists('icl_t')) {
        $title = icl_t('adforest_theme', "footer_modern_trust{$i}_title", $title);
        $desc  = icl_t('adforest_theme', "footer_modern_trust{$i}_desc",  $desc);
    }
    $trust_cells[] = array('icon' => $icon, 'title' => $title, 'desc' => $desc);
}
?>

<style id="adf-home-modern-footer-css">
.adf-hm-trust{
    --hm-brand:<?php echo esc_attr($theme_btn_color); ?>;
    --hm-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;
    --hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;
    --hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;
    background:rgba(var(--hm-brand-rgb),.05);border-top:1px solid rgba(var(--hm-brand-rgb),.12);padding:36px 0;
}
.adf-hm-trust__inner{max-width:1200px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:repeat(4,1fr);gap:24px;}
.adf-hm-trust__cell{display:flex;align-items:center;gap:14px;}
.adf-hm-trust__icon{width:50px;height:50px;flex-shrink:0;border-radius:12px;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:var(--hm-brand);font-size:20px;box-shadow:0 0 6px rgba(15,23,42,.04);}
.adf-hm-trust__txt strong{display:block;font-size:15px;font-weight:500;color:#0f172a;margin-bottom:2px;}
.adf-hm-trust__txt p{margin:0;color:#64748b;font-size:12.5px;line-height:1.5;}

.adf-hm-footer{
    --hm-brand:<?php echo esc_attr($theme_btn_color); ?>;
    --hm-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;
    --hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;
    --hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;
    background:#fff;color:#475569;font-size:14px;position:relative;overflow:hidden;
}
.adf-hm-footer *{box-sizing:border-box;}
/* Soft brand-tinted botanical accents in the top-left & bottom-right
   corners — pure CSS, follows the example's decorative line-art motif
   without requiring external SVG assets. Sits behind the content. */
.adf-hm-footer::before,
.adf-hm-footer::after{content:"";position:absolute;width:280px;height:280px;background:radial-gradient(circle at center,rgba(var(--hm-brand-rgb),.06) 0%,transparent 70%);pointer-events:none;z-index:0;}
.adf-hm-footer::before{top:-90px;left:-90px;}
.adf-hm-footer::after{bottom:-90px;right:-90px;}
.adf-hm-footer__inner{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:60px 24px 30px;display:grid;grid-template-columns:1.4fr 1fr 1fr 1.4fr;gap:40px;}
.adf-hm-footer h4{font-size:16px;font-weight:600;color:#0f172a;margin:0 0 18px;letter-spacing:-.01em;}
.adf-hm-footer__about img{max-height:38px;width:auto;display:block;margin-bottom:14px;}
.adf-hm-footer__about p{margin:0 0 18px;line-height:1.65;font-size:13.5px;color:#64748b;}
/* Circular social icons with a brand-tinted ring — neutral by default,
   solid brand on hover. Smaller and lighter than the previous square
   chips to match the example's understated style. */
.adf-hm-footer__social{display:flex;gap:10px;}
.adf-hm-footer__social a{width:34px;height:34px;border-radius:50%;background:#fff;color:var(--hm-brand);border:1px solid rgba(var(--hm-brand-rgb),.25);display:inline-flex;align-items:center;justify-content:center;font-size:13px;transition:background .15s ease,color .15s ease,border-color .15s ease,transform .12s ease;text-decoration:none;}
.adf-hm-footer__social a:hover{background:var(--hm-brand);color:var(--hm-brand-text);border-color:var(--hm-brand);transform:translateY(-1px);}
.adf-hm-footer__menu{list-style:none;margin:0;padding:0;}
.adf-hm-footer__menu li{margin:0 0 12px;}
.adf-hm-footer__menu li a{color:#475569;text-decoration:none;font-size:14px;font-weight:500;display:inline-flex;align-items:center;gap:10px;transition:color .15s ease,gap .15s ease;}
.adf-hm-footer__menu li a::before{content:"";flex-shrink:0;width:6px;height:6px;border-radius:50%;background:var(--hm-brand);display:inline-block;}
.adf-hm-footer__menu li a:hover{color:var(--hm-brand);gap:12px;}
.adf-hm-footer__news p{margin:0 0 14px;color:#64748b;font-size:13.5px;line-height:1.55;}
.adf-hm-footer__news-form{position:relative;display:flex;align-items:center;gap:8px;}
.adf-hm-footer__news-form input[type=email]{flex:1;min-width:0;border:1px solid #e3e7ee;border-radius:10px;padding:0 14px;height:46px;font-size:14px;color:#0f172a;background:#fff;outline:none;transition:border-color .15s ease,box-shadow .15s ease;font-family:inherit;}
.adf-hm-footer__news-form input[type=email]:focus{border-color:var(--hm-brand);box-shadow:0 0 0 4px rgba(var(--hm-brand-rgb),.12);}
.adf-hm-footer__news-form button{width:46px;height:46px;border-radius:10px;background:var(--hm-brand);color:var(--hm-brand-text);border:0;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;transition:background .15s ease,transform .12s ease;box-shadow:0 0 6px rgba(var(--hm-brand-rgb),.25);}
.adf-hm-footer__news-form button:hover{background:var(--hm-brand-hover);transform:translateY(-1px);}

/* App download band — full-width strip sitting between the footer
   columns and the copyright bar. Two store buttons on the right,
   title + description on the left. Brand-tinted, lightweight. */
.adf-hm-footer__app{position:relative;z-index:1;border-top:1px solid #eef1f5;background:rgba(var(--hm-brand-rgb),.04);}
.adf-hm-footer__app-inner{max-width:1200px;margin:0 auto;padding:26px 24px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;}
.adf-hm-footer__app-txt{flex:1 1 280px;min-width:0;}
.adf-hm-footer__app-txt h4{margin:0 0 4px;font-size:17px;font-weight:600;color:#0f172a;letter-spacing:-.01em;}
.adf-hm-footer__app-txt p{margin:0;color:#64748b;font-size:13.5px;line-height:1.55;}
.adf-hm-footer__app-btns{display:flex;gap:10px;flex-wrap:wrap;}
.adf-hm-footer__app-btn{display:inline-flex;align-items:center;gap:10px;background:#0f172a;color:#fff;text-decoration:none;border-radius:10px;padding:8px 16px;min-width:160px;transition:background .15s ease,transform .12s ease;}
.adf-hm-footer__app-btn:hover{background:#1e293b;color:#fff;transform:translateY(-1px);}
.adf-hm-footer__app-btn i{font-size:26px;flex-shrink:0;line-height:1;}
.adf-hm-footer__app-btn span{display:flex;flex-direction:column;line-height:1.15;text-align:left;}
.adf-hm-footer__app-btn span small{font-size:10.5px;opacity:.75;font-weight:400;letter-spacing:.02em;text-transform:uppercase;}
.adf-hm-footer__app-btn span strong{font-size:14px;font-weight:600;}

.adf-hm-footer__bottom{position:relative;z-index:1;border-top:1px solid #eef1f5;padding:18px 24px;}
.adf-hm-footer__bottom-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;}
.adf-hm-footer__copy{color:#94a3b8;font-size:13px;margin:0;}
.adf-hm-footer__copy a{color:var(--hm-brand);text-decoration:none;font-weight:600;}
.adf-hm-footer__pay{display:flex;align-items:center;gap:14px;font-size:26px;color:#cbd5e1;}
.adf-hm-footer__pay i{transition:color .15s ease;}
.adf-hm-footer__pay i.fa-paypal:hover{color:#003087;}
.adf-hm-footer__pay i.fa-cc-visa:hover{color:#1a1f71;}
.adf-hm-footer__pay i.fa-cc-mastercard:hover{color:#eb001b;}
.adf-hm-footer__pay i.fa-cc-discover:hover{color:#ff6000;}
.adf-hm-footer__pay i.fa-cc-amex:hover{color:#2e77bb;}

@media (max-width:1099px){
    .adf-hm-footer__inner{grid-template-columns:1fr 1fr;gap:30px;padding:48px 22px 24px;}
    .adf-hm-trust__inner{grid-template-columns:1fr 1fr;}
}
@media (max-width:600px){
    .adf-hm-trust{padding:26px 0;}
    .adf-hm-trust__inner{grid-template-columns:1fr;gap:16px;padding:0 18px;}
    .adf-hm-footer__inner{grid-template-columns:1fr;gap:32px;padding:40px 18px 20px;}
    .adf-hm-footer__app-inner{padding:22px 18px;justify-content:center;text-align:center;}
    .adf-hm-footer__app-txt{flex:1 1 100%;}
    .adf-hm-footer__app-btns{width:100%;justify-content:center;}
    .adf-hm-footer__bottom-inner{justify-content:center;text-align:center;}
    .adf-hm-footer__pay{font-size:22px;gap:10px;}
}
</style>

<?php if ($trust_enabled && !empty($trust_cells)) : ?>
<section class="adf-hm-trust" aria-label="<?php esc_attr_e('Trust badges', 'adforest'); ?>">
    <div class="adf-hm-trust__inner">
        <?php foreach ($trust_cells as $cell) : ?>
            <div class="adf-hm-trust__cell">
                <span class="adf-hm-trust__icon"><i class="<?php echo esc_attr($cell['icon']); ?>"></i></span>
                <div class="adf-hm-trust__txt">
                    <strong><?php echo esc_html($cell['title']); ?></strong>
                    <p><?php echo esc_html($cell['desc']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<footer class="adf-hm-footer" role="contentinfo">
    <div class="adf-hm-footer__inner">

        <div class="adf-hm-footer__about">
            <img src="<?php echo esc_url($site_logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <p><?php echo esc_html($about_text); ?></p>
            <div class="adf-hm-footer__social">
                <?php foreach ($social_links as $icon => $url) : ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr(str_replace(array('fa-', '-'), array('', ' '), $icon)); ?>">
                        <i class="fab <?php echo esc_attr($icon); ?>"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <h4><?php echo esc_html($col1_title); ?></h4>
            <?php
            if (!empty($col1_pages)) {
                // Admin selected specific pages — render those.
                ?>
                <ul class="adf-hm-footer__menu">
                    <?php foreach ($col1_pages as $link) : ?>
                        <li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php
            } elseif (has_nav_menu('footer_1')) {
                wp_nav_menu(array(
                    'theme_location' => 'footer_1',
                    'container'      => false,
                    'menu_class'     => 'adf-hm-footer__menu',
                    'depth'          => 1,
                ));
            } else {
                ?>
                <ul class="adf-hm-footer__menu">
                    <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('Listings', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('Categories', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('Blog', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('Contact', 'adforest'); ?></a></li>
                </ul>
                <?php
            }
            ?>
        </div>

        <div>
            <h4><?php echo esc_html($col2_title); ?></h4>
            <?php
            if (!empty($col2_pages)) {
                // Admin selected specific pages — render those.
                ?>
                <ul class="adf-hm-footer__menu">
                    <?php foreach ($col2_pages as $link) : ?>
                        <li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php
            } elseif (has_nav_menu('footer_2')) {
                wp_nav_menu(array(
                    'theme_location' => 'footer_2',
                    'container'      => false,
                    'menu_class'     => 'adf-hm-footer__menu',
                    'depth'          => 1,
                ));
            } else {
                ?>
                <ul class="adf-hm-footer__menu">
                    <li><a href="#"><?php esc_html_e('About Us', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('FAQ', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('Terms & Conditions', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('Privacy Policy', 'adforest'); ?></a></li>
                    <li><a href="#"><?php esc_html_e('How It Works', 'adforest'); ?></a></li>
                </ul>
                <?php
            }
            ?>
        </div>

        <div class="adf-hm-footer__news">
            <h4><?php echo esc_html($news_title); ?></h4>
            <p><?php echo esc_html($news_desc); ?></p>
            <form class="adf-hm-footer__news-form" method="post" action="#" novalidate>
                <input type="email" name="email" placeholder="<?php echo esc_attr($news_placeholder); ?>" required>
                <button type="submit" aria-label="<?php esc_attr_e('Subscribe', 'adforest'); ?>"><i class="fa fa-paper-plane"></i></button>
            </form>
        </div>

    </div>

    <?php if ($app_enabled && ($app_gplay_url || $app_ios_url)) : ?>
    <div class="adf-hm-footer__app">
        <div class="adf-hm-footer__app-inner">
            <div class="adf-hm-footer__app-txt">
                <h4><?php echo esc_html($app_title); ?></h4>
                <p><?php echo esc_html($app_desc); ?></p>
            </div>
            <div class="adf-hm-footer__app-btns">
                <?php if ($app_gplay_url) : ?>
                    <a class="adf-hm-footer__app-btn" href="<?php echo esc_url($app_gplay_url); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e('Get it on Google Play', 'adforest'); ?>">
                        <i class="fab fa-google-play"></i>
                        <span>
                            <small><?php esc_html_e('Get it on', 'adforest'); ?></small>
                            <strong><?php esc_html_e('Google Play', 'adforest'); ?></strong>
                        </span>
                    </a>
                <?php endif; ?>
                <?php if ($app_ios_url) : ?>
                    <a class="adf-hm-footer__app-btn" href="<?php echo esc_url($app_ios_url); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e('Download on the App Store', 'adforest'); ?>">
                        <i class="fab fa-apple"></i>
                        <span>
                            <small><?php esc_html_e('Download on the', 'adforest'); ?></small>
                            <strong><?php esc_html_e('App Store', 'adforest'); ?></strong>
                        </span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="adf-hm-footer__bottom">
        <div class="adf-hm-footer__bottom-inner">
            <p class="adf-hm-footer__copy"><?php echo wp_kses_post($copyright_html); ?></p>
            <div class="adf-hm-footer__pay" aria-label="<?php esc_attr_e('Accepted payment methods', 'adforest'); ?>">
                <i class="fab fa-paypal" title="PayPal"></i>
                <i class="fab fa-cc-visa" title="Visa"></i>
                <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                <i class="fab fa-cc-discover" title="Discover"></i>
                <i class="fab fa-cc-amex" title="American Express"></i>
            </div>
        </div>
    </div>
</footer>
