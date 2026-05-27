<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Home — Hero + Categories widget.
 *
 * Renders the brand-tinted hero (badge, headline, subtitle,
 * 3-field search bar) AND the category cards grid directly
 * below so the two sections share one continuous background.
 *
 * Optional inputs:
 *   - Hero background image (falls back to the soft gradient)
 *   - Category images set per term in WP Admin → Listings →
 *     Categories → edit term → upload Image. Reads them via the
 *     theme helper `adforest_taxonomy_image_url()`; falls back
 *     to a folder icon when none is set.
 */
class AdfHomeHero extends Widget_Base
{
    public function get_name() { return 'adf_home_hero'; }
    public function get_title() { return __('Modern Home — Hero + Categories', 'adforest-elementor'); }
    public function get_icon() { return 'eicon-search-bold'; }
    public function get_categories() { return ['adforest_widgets']; }
    public function get_keywords() { return ['adforest', 'home', 'hero', 'search', 'categories', 'modern']; }

    protected function register_controls()
    {
        $this->start_controls_section('content', [
            'label' => __('Content', 'adforest-elementor'),
        ]);

        $this->add_control('badge_text', [
            'label' => __('Badge text', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('100% Free Ads Forever', 'adforest-elementor'),
        ]);
        $this->add_control('welcome_text', [
            'label' => __('Small uppercase line', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Welcome to', 'adforest-elementor'),
        ]);
        $this->add_control('headline_before', [
            'label' => __('Headline (before accent)', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Visit, Classified, Web &', 'adforest-elementor'),
        ]);
        $this->add_control('headline_accent', [
            'label' => __('Accent word', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('More.', 'adforest-elementor'),
        ]);
        $this->add_control('subtitle', [
            'label' => __('Subtitle', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXTAREA,
            'rows'  => 3,
            'default' => __('AdForest is the most innovative, user-friendly and reliable classified ads WordPress theme from the trusted author.', 'adforest-elementor'),
        ]);

        $this->end_controls_section();

        $this->start_controls_section('background', [
            'label' => __('Hero background', 'adforest-elementor'),
        ]);

        $this->add_control('bg_image', [
            'label' => __('Background image', 'adforest-elementor'),
            'type'  => Controls_Manager::MEDIA,
            'default' => ['url' => ''],
            'description' => __('Optional. If empty, the section uses the soft brand-tinted gradient shown in the default layout.', 'adforest-elementor'),
        ]);

        $this->end_controls_section();

        $this->start_controls_section('search', [
            'label' => __('Search', 'adforest-elementor'),
        ]);

        $this->add_control('search_placeholder', [
            'label' => __('Search placeholder', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('What are you looking for?', 'adforest-elementor'),
        ]);
        $this->add_control('category_placeholder', [
            'label' => __('Category placeholder', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Select Category', 'adforest-elementor'),
        ]);
        $this->add_control('location_source', [
            'label' => __('Location filter source', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => 'taxonomy',
            'options' => [
                'taxonomy' => __('Custom dropdown (ad_country taxonomy)', 'adforest-elementor'),
                'google'   => __('Google Places autocomplete', 'adforest-elementor'),
            ],
            'description' => __('Google Places requires the Maps API key in <strong>Theme Options → Maps → Google Maps API Key</strong>. Falls back to the taxonomy dropdown when the key is missing.', 'adforest-elementor'),
        ]);
        $this->add_control('location_placeholder', [
            'label' => __('Location placeholder', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Select Location', 'adforest-elementor'),
        ]);
        $this->add_control('button_text', [
            'label' => __('Search button text', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Search Now', 'adforest-elementor'),
        ]);

        $this->end_controls_section();

        $this->start_controls_section('categories', [
            'label' => __('Categories below search', 'adforest-elementor'),
        ]);

        $this->add_control('show_categories', [
            'label' => __('Show categories grid', 'adforest-elementor'),
            'type'  => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'return_value' => 'yes',
        ]);
        $this->add_control('cats_limit', [
            'label' => __('Number of categories', 'adforest-elementor'),
            'type'  => Controls_Manager::NUMBER,
            'default' => 12,
            'min' => 1,
            'max' => 30,
            'description' => __('6 cards are visible at once. Extras become reachable via the navigation arrows.', 'adforest-elementor'),
            'condition' => ['show_categories' => 'yes'],
        ]);
        $this->add_control('cats_show_count', [
            'label' => __('Show ad count', 'adforest-elementor'),
            'type'  => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'return_value' => 'yes',
            'condition' => ['show_categories' => 'yes'],
        ]);
        $this->add_control('cats_orderby', [
            'label' => __('Order by', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => 'count',
            'options' => [
                'count'      => __('Most used', 'adforest-elementor'),
                'name'       => __('Name (A–Z)', 'adforest-elementor'),
                'term_order' => __('Custom order', 'adforest-elementor'),
            ],
            'condition' => ['show_categories' => 'yes'],
        ]);
        $this->add_control('cats_help', [
            'type' => Controls_Manager::RAW_HTML,
            'raw'  => __('Each category\'s image comes from <strong>WP Admin → Listings → Categories → edit term → Image</strong>. Empty images fall back to a folder icon.', 'adforest-elementor'),
            'content_classes' => 'elementor-descriptor',
            'condition' => ['show_categories' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        global $adforest_theme;
        $atts = $this->get_settings_for_display();

        $theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
        $theme_btn_hover = !empty($adforest_theme['opt-theme-btn-color']['hover'])   ? $adforest_theme['opt-theme-btn-color']['hover']   : '#d6002a';
        $theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
        $_rgb            = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
        $theme_btn_rgb   = (is_array($_rgb) && count($_rgb) === 3 && $_rgb[0] !== null) ? implode(',', $_rgb) : '255,0,46';

        $bg_image_url    = !empty($atts['bg_image']['url']) ? $atts['bg_image']['url'] : '';

        // Location filter mode: 'taxonomy' (Select2 dropdown) or 'google' (Places autocomplete).
        // Render whichever the admin chose — never silently revert. If Google is picked but the
        // gmap_api_key is missing, the input still renders so the change is visible; the
        // autocomplete simply won't attach and an editor-only warning surfaces below.
        $location_source       = ($atts['location_source'] ?? 'taxonomy') === 'google' ? 'google' : 'taxonomy';
        $gmap_api_key          = isset($adforest_theme['gmap_api_key']) ? trim((string) $adforest_theme['gmap_api_key']) : '';
        $google_mode_missing_key = ($location_source === 'google' && empty($gmap_api_key));
        $gmap_lang             = isset($adforest_theme['gmap_lang']) && $adforest_theme['gmap_lang'] !== '' ? $adforest_theme['gmap_lang'] : 'en';
        $gmap_lang             = function_exists('apply_filters') ? apply_filters('adforest_languages_code', $gmap_lang) : $gmap_lang;

        // When Google mode is on, enqueue AdForest's already-registered Maps + Places
        // script handle. Using the theme's enqueue path (same one the ad-post page uses)
        // avoids loading conflicts and the "loading=async without callback" pitfalls of
        // injecting our own <script> tag at render time.
        if ($location_source === 'google' && !empty($gmap_api_key)) {
            if (wp_script_is('google-map-callback', 'registered')) {
                wp_enqueue_script('google-map-callback');
            } else {
                wp_register_script(
                    'google-map-callback',
                    '//maps.googleapis.com/maps/api/js?key=' . rawurlencode($gmap_api_key) . '&libraries=geometry,places&language=' . rawurlencode($gmap_lang) . '&loading=async',
                    array(),
                    false,
                    true
                );
                wp_enqueue_script('google-map-callback');
            }
        }

        $categories_search = get_terms(['taxonomy' => 'ad_cats',    'hide_empty' => false, 'number' => 30, 'orderby' => 'count', 'order' => 'DESC', 'parent' => 0]);
        $locations         = get_terms(['taxonomy' => 'ad_country', 'hide_empty' => false, 'number' => 30, 'orderby' => 'count', 'order' => 'DESC']);
        if (is_wp_error($categories_search)) { $categories_search = []; }
        if (is_wp_error($locations))         { $locations         = []; }

        $show_categories   = ($atts['show_categories'] ?? 'yes') === 'yes';
        $cats_limit        = !empty($atts['cats_limit'])   ? (int) $atts['cats_limit']  : 6;
        $cats_show_count   = ($atts['cats_show_count'] ?? 'yes') === 'yes';
        $cats_orderby      = !empty($atts['cats_orderby']) ? $atts['cats_orderby']      : 'count';

        $cat_grid = [];
        if ($show_categories) {
            $cat_grid = get_terms([
                'taxonomy'   => 'ad_cats',
                'hide_empty' => false,
                'number'     => $cats_limit,
                'orderby'    => $cats_orderby,
                'order'      => $cats_orderby === 'name' ? 'ASC' : 'DESC',
                'parent'     => 0,
            ]);
            if (is_wp_error($cat_grid)) { $cat_grid = []; }
        }

        $palettes = [
            ['bg' => '#fff1cc', 'fg' => '#b07a00'],
            ['bg' => '#dbe6ff', 'fg' => '#1d4ed8'],
            ['bg' => '#d2f3e8', 'fg' => '#0f766e'],
            ['bg' => '#ffe2cc', 'fg' => '#b45309'],
            ['bg' => '#ffd9e6', 'fg' => '#be185d'],
            ['bg' => '#dff3e0', 'fg' => '#15803d'],
        ];
        ?>
        <style>
        .adf-hmw-hero{
            --hm-brand:<?php echo esc_attr($theme_btn_color); ?>;
            --hm-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;
            --hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;
            --hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;
            position:relative;padding:60px 0 60px;text-align:center;
            <?php if ($bg_image_url) : ?>
            background:#fff url('<?php echo esc_url($bg_image_url); ?>') center top / cover no-repeat;
            <?php else : ?>
            background:linear-gradient(180deg,#fafdfb 0%,#fff 60%);
            <?php endif; ?>
            overflow:hidden;box-sizing:border-box;
        }
        .adf-hmw-hero *{box-sizing:border-box;}
        .adf-hmw-hero::before,.adf-hmw-hero::after{content:"";position:absolute;width:520px;height:520px;background:radial-gradient(circle,rgba(var(--hm-brand-rgb),.05) 0%,transparent 70%);pointer-events:none;z-index:0;}
        .adf-hmw-hero::before{top:-180px;left:-160px;}
        .adf-hmw-hero::after{bottom:-220px;right:-180px;}
        .adf-hmw-hero__inner{position:relative;z-index:1;max-width:1000px;margin:0 auto;padding:0 24px;}
        .adf-hmw-hero__head{max-width:760px;margin:0 auto 6px;}
        .adf-hmw-hero__badge{display:inline-flex;align-items:center;gap:6px;background:#fef3c7;color:#92400e;border-radius:999px;padding:6px 14px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:18px;}
        .adf-hmw-hero__welcome{display:block;font-size:13px;letter-spacing:.32em;text-transform:uppercase;color:#94a3b8;font-weight:600;margin-bottom:14px;}
        .adf-hmw-hero h1{font-size:48px;font-weight:600;color:#0f172a;margin:0 0 20px;letter-spacing:-.025em;line-height:1.08;}
        .adf-hmw-hero h1 em{font-style:normal;color:#f59e0b;}
        .adf-hmw-hero__sub{margin:0 auto 40px;max-width:560px;color:#64748b;font-size:15px;line-height:1.6;}
        .adf-hmw-search-wrap{max-width:960px;margin:0 auto;background:rgba(var(--hm-brand-rgb),.08);border:1px solid rgba(var(--hm-brand-rgb),.14);border-radius:16px;padding:6px;text-align:left;}
        .adf-hmw-search{display:flex;align-items:stretch;gap:6px;background:transparent;border:0;border-radius:0;box-shadow:none;padding:0;margin:0;}
        .adf-hmw-search__fields{flex:1;display:flex;align-items:stretch;background:#fff;border-radius:10px;box-shadow:0 0 6px rgba(15,23,42,.04);padding:0;min-width:0;overflow:hidden;}
        .adf-hmw-search__field{flex:1;display:flex;align-items:center;gap:10px;padding:0 16px;height:52px;border-right:1px solid #eef1f5;min-width:0;}
        .adf-hmw-search__field:last-of-type{border-right:0;}
        .adf-hmw-search__field i{color:#94a3b8;font-size:14px;flex-shrink:0;}
        .adf-hmw-search__field input,.adf-hmw-search__field select{flex:1;min-width:0;border:0;background:transparent;outline:none;font-size:14px;color:#0f172a;font-family:inherit;appearance:none;-webkit-appearance:none;padding:0;line-height:1.4;height:100%;}
        .adf-hmw-search__field input::placeholder{color:#94a3b8;}
        .adf-hmw-search__field select{cursor:pointer;background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3e%3cpath fill='%2394a3b8' d='M5 6L0 1l1-1 4 4 4-4 1 1z'/%3e%3c/svg%3e");background-repeat:no-repeat;background-position:right 0 center;padding-right:18px;}

        /* Select2 overrides — target .select2-container directly inside the field
           so we beat AdForest's select2.min.css (which hard-codes height:55px and
           line-height:50px on .select2-container--default .select2-selection--single) */
        .adf-hmw-hero .adf-hmw-search__field .select2-container{flex:1 !important;min-width:0 !important;width:auto !important;height:52px !important;display:inline-flex !important;align-items:center !important;background:transparent !important;border:0 !important;}
        .adf-hmw-hero .adf-hmw-search__field .select2-container .selection{display:flex !important;align-items:center !important;width:100% !important;height:100% !important;}
        .adf-hmw-hero .adf-hmw-search__field .select2-container .select2-selection,
        .adf-hmw-hero .adf-hmw-search__field .select2-container .select2-selection--single{position:relative !important;height:52px !important;min-height:0 !important;background:transparent !important;border:0 !important;outline:none !important;padding:0 !important;box-shadow:none !important;width:100% !important;display:flex !important;align-items:center !important;border-radius:0 !important;}
        .adf-hmw-hero .adf-hmw-search__field .select2-container .select2-selection__rendered{flex:1;padding:0 22px 0 0 !important;line-height:52px !important;color:#0f172a !important;font-size:14px !important;font-family:inherit !important;height:52px !important;margin:0 !important;overflow:hidden !important;text-overflow:ellipsis !important;white-space:nowrap !important;text-align:left !important;}
        .adf-hmw-hero .adf-hmw-search__field .select2-container .select2-selection__placeholder{color:#94a3b8 !important;line-height:52px !important;text-align:left !important;}
        .adf-hmw-hero .adf-hmw-search__field .select2-container .select2-selection__arrow{height:52px !important;width:18px !important;right:0 !important;top:0 !important;position:absolute !important;background:transparent !important;border:0 !important;}
        .adf-hmw-hero .adf-hmw-search__field .select2-container .select2-selection__arrow b{border-color:#94a3b8 transparent transparent transparent !important;border-style:solid !important;border-width:5px 4px 0 4px !important;left:50% !important;margin-left:-4px !important;margin-top:-2px !important;position:absolute !important;top:50% !important;width:0 !important;}
        .adf-hmw-hero .adf-hmw-search__field .select2-container--open .select2-selection__arrow b{border-color:transparent transparent #94a3b8 transparent !important;border-width:0 4px 5px 4px !important;}
        .adf-hmw-select2-dropdown.select2-dropdown{border:1px solid #eef1f5 !important;border-radius:10px !important;box-shadow:0 6px 20px rgba(15,23,42,.08) !important;overflow:hidden;margin-top:6px;background:#fff !important;}
        .adf-hmw-select2-dropdown.select2-container--default .select2-results__option,
        .adf-hmw-select2-dropdown .select2-results__option{padding:9px 14px !important;font-size:13.5px !important;color:#1f2937 !important;font-family:inherit !important;}
        .adf-hmw-select2-dropdown.select2-container--default .select2-results__option--highlighted[aria-selected],
        .adf-hmw-select2-dropdown .select2-results__option--highlighted[aria-selected]{background:rgba(<?php echo esc_attr($theme_btn_rgb); ?>,.08) !important;color:<?php echo esc_attr($theme_btn_color); ?> !important;}
        .adf-hmw-select2-dropdown.select2-container--default .select2-results__option[aria-selected="true"],
        .adf-hmw-select2-dropdown .select2-results__option[aria-selected="true"]{background:rgba(<?php echo esc_attr($theme_btn_rgb); ?>,.06) !important;color:<?php echo esc_attr($theme_btn_color); ?> !important;font-weight:600;}
        .adf-hmw-select2-dropdown .select2-search--dropdown{padding:8px;}
        .adf-hmw-select2-dropdown .select2-search--dropdown .select2-search__field{border:1px solid #eef1f5 !important;border-radius:8px !important;padding:7px 10px !important;font-family:inherit;outline:none;}
        .adf-hmw-search__btn{background:var(--hm-brand);color:var(--hm-brand-text);border:0;border-radius:10px;padding:0 28px;height:52px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;flex-shrink:0;transition:background .15s ease,transform .12s ease;box-shadow:0 0 6px rgba(var(--hm-brand-rgb),.25);font-family:inherit;line-height:1;}
        .adf-hmw-search__btn:hover{background:var(--hm-brand-hover);transform:translateY(-1px);}

        /* Google Places autocomplete dropdown styling. z-index pinned high so it
           can't slip behind AdForest's sticky header / Elementor stacking. */
        .pac-container{z-index:99999 !important;border:1px solid #eef1f5 !important;border-radius:10px !important;box-shadow:0 6px 20px rgba(15,23,42,.08) !important;margin-top:6px;font-family:inherit !important;overflow:hidden;}
        .pac-container .pac-item{padding:9px 14px;font-size:13.5px;color:#1f2937;cursor:pointer;border-top:1px solid #f1f5f9;}
        .pac-container .pac-item:first-child{border-top:0;}
        .pac-container .pac-item:hover,.pac-container .pac-item-selected{background:rgba(<?php echo esc_attr($theme_btn_rgb); ?>,.08) !important;}
        .pac-container .pac-item-query{color:<?php echo esc_attr($theme_btn_color); ?>;font-weight:600;}
        .pac-container .pac-icon{margin-right:8px;}

        /* Google Places error banner — visible on both editor and public site when the
           API key is invalid, blocked, billing/quota issues, or the script fails to load */
        .adf-hmw-location-error{margin:10px auto 0;max-width:1100px;padding:10px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#991b1b;font-size:13px;text-align:left;display:flex;align-items:flex-start;gap:10px;line-height:1.45;}
        .adf-hmw-location-error[hidden]{display:none !important;}
        .adf-hmw-location-error i{flex-shrink:0;margin-top:1px;color:#dc2626;}
        .adf-hmw-location-error strong{display:block;font-weight:700;margin-bottom:2px;color:#7f1d1d;}

        .adf-hmw-hero__cats{--per-view:6;--gap:22px;position:relative;z-index:1;max-width:1200px;margin:38px auto 0;padding:0 24px;}
        .adf-hmw-hero__cats-track{display:flex;gap:var(--gap);overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;-webkit-overflow-scrolling:touch;scrollbar-width:none;margin:0;padding:4px 0;}
        .adf-hmw-hero__cats-track::-webkit-scrollbar{display:none;}
        .adf-hmw-hero__cat{flex:0 0 calc((100% - (var(--per-view) - 1) * var(--gap)) / var(--per-view));scroll-snap-align:start;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:16px;padding:28px 16px 22px;text-decoration:none;text-align:center;transition:transform .18s ease,box-shadow .18s ease;color:inherit;min-height:170px;gap:8px;}
        .adf-hmw-hero__cat:hover{transform:translateY(-3px);box-shadow:0 0 12px rgba(15,23,42,.06);}
        .adf-hmw-hero__cat-icon{width:70px;height:70px;border-radius:16px;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:inherit;margin-bottom:6px;overflow:hidden;}
        .adf-hmw-hero__cat-icon img{width:44px;height:44px;object-fit:contain;}
        .adf-hmw-hero__cat-icon i{font-size:26px;}
        .adf-hmw-hero__cat-name{font-weight:500;color:#0f172a;font-size:15px;line-height:1.2;}
        .adf-hmw-hero__cat-count{font-size:12px;color:#64748b;font-weight:500;line-height:1.2;}

        .adf-hmw-hero__cats-nav{position:absolute;top:50%;transform:translateY(-50%);width:38px;height:38px;border-radius:50%;background:#fff;border:1px solid rgba(var(--hm-brand-rgb),.18);color:var(--hm-brand);display:none;align-items:center;justify-content:center;cursor:pointer;font-size:14px;box-shadow:0 0 10px rgba(15,23,42,.08);transition:transform .15s ease,background .15s ease,color .15s ease,opacity .15s ease;z-index:2;font-family:inherit;padding:0;}
        .adf-hmw-hero__cats-nav:hover{background:var(--hm-brand);color:var(--hm-brand-text,#fff);transform:translateY(-50%) scale(1.04);}
        .adf-hmw-hero__cats-nav--prev{left:-6px;}
        .adf-hmw-hero__cats-nav--next{right:-6px;}
        .adf-hmw-hero__cats.has-nav .adf-hmw-hero__cats-nav{display:inline-flex;}
        .adf-hmw-hero__cats-nav[aria-disabled="true"]{opacity:0;pointer-events:none;}

        @media (max-width:1099px){.adf-hmw-hero__cats{--per-view:3;}}
        @media (max-width:768px){.adf-hmw-hero{padding:44px 0 32px;}.adf-hmw-hero h1{font-size:32px;}.adf-hmw-search-wrap{padding:6px;border-radius:14px;}.adf-hmw-search{flex-direction:column;gap:6px;}.adf-hmw-search__fields{flex-direction:column;align-items:stretch;}.adf-hmw-search__field{border-right:0;border-bottom:1px solid #eef1f5;padding:12px 14px;}.adf-hmw-search__field:last-of-type{border-bottom:0;}.adf-hmw-search__btn{width:100%;min-height:48px;}.adf-hmw-hero__cats{margin-top:28px;}}
        @media (max-width:600px){.adf-hmw-hero__cats{--per-view:2;--gap:14px;}.adf-hmw-hero__cat{padding:20px 10px 16px;min-height:150px;}.adf-hmw-hero__cat-icon{width:56px;height:56px;}.adf-hmw-hero__cats-nav{width:34px;height:34px;font-size:13px;}}
        @media (max-width:480px){.adf-hmw-hero h1{font-size:26px;}}
        </style>
        <section class="adf-hmw-hero">
            <div class="adf-hmw-hero__inner">
                <div class="adf-hmw-hero__head">
                    <?php if (!empty($atts['badge_text'])) : ?>
                        <span class="adf-hmw-hero__badge">★ <?php echo esc_html($atts['badge_text']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($atts['welcome_text'])) : ?>
                        <span class="adf-hmw-hero__welcome"><?php echo esc_html($atts['welcome_text']); ?></span>
                    <?php endif; ?>
                    <h1>
                        <?php echo esc_html($atts['headline_before']); ?>
                        <?php if (!empty($atts['headline_accent'])) : ?>
                            <em><?php echo esc_html($atts['headline_accent']); ?></em>
                        <?php endif; ?>
                    </h1>
                    <?php if (!empty($atts['subtitle'])) : ?>
                        <p class="adf-hmw-hero__sub"><?php echo esc_html($atts['subtitle']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="adf-hmw-search-wrap">
                    <form class="adf-hmw-search" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search">
                        <input type="hidden" name="post_type" value="ad_post">
                        <div class="adf-hmw-search__fields">
                            <div class="adf-hmw-search__field">
                                <i class="fa fa-search"></i>
                                <input type="search" name="s" placeholder="<?php echo esc_attr($atts['search_placeholder']); ?>" value="<?php echo esc_attr(get_search_query()); ?>">
                            </div>
                            <div class="adf-hmw-search__field">
                                <i class="fa fa-th-large"></i>
                                <select name="ad_cats">
                                    <option value=""><?php echo esc_html($atts['category_placeholder']); ?></option>
                                    <?php foreach ($categories_search as $c) : ?>
                                        <option value="<?php echo esc_attr($c->slug); ?>"><?php echo esc_html($c->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="adf-hmw-search__field">
                                <i class="fa fa-map-marker-alt"></i>
                                <?php if ($location_source === 'google') : ?>
                                    <input
                                        type="text"
                                        name="location"
                                        id="sb_user_address"
                                        class="adf-hmw-location-google"
                                        placeholder="<?php echo esc_attr($atts['location_placeholder']); ?>"
                                        autocomplete="off"
                                    >
                                    <input type="hidden" name="sb_user_lat" id="sb_user_lat" value="">
                                    <input type="hidden" name="sb_user_lng" id="sb_user_lng" value="">
                                <?php else : ?>
                                    <select name="ad_country">
                                        <option value=""><?php echo esc_html($atts['location_placeholder']); ?></option>
                                        <?php foreach ($locations as $l) : ?>
                                            <option value="<?php echo esc_attr($l->slug); ?>"><?php echo esc_html($l->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="submit" class="adf-hmw-search__btn">
                            <?php echo esc_html($atts['button_text']); ?>
                        </button>
                    </form>
                </div>
                <script>
                (function () {
                    function initAdfHmwSelect2() {
                        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 !== 'function') {
                            return false;
                        }
                        var $ = window.jQuery;
                        $('.adf-hmw-search select').each(function () {
                            var $sel = $(this);
                            if ($sel.data('adfSelect2Init')) { return; }
                            $sel.data('adfSelect2Init', true);
                            $sel.select2({
                                minimumResultsForSearch: 8,
                                width: '100%',
                                containerCssClass: 'adf-hmw-select2',
                                dropdownCssClass:  'adf-hmw-select2-dropdown',
                                placeholder: $sel.find('option[value=""]').first().text() || ''
                            });
                        });
                        return true;
                    }
                    if (!initAdfHmwSelect2()) {
                        var tries = 0;
                        var iv = setInterval(function () {
                            if (initAdfHmwSelect2() || ++tries > 40) { clearInterval(iv); }
                        }, 120);
                    }
                })();
                </script>

                <?php if ($location_source === 'google') : ?>
                <div class="adf-hmw-location-error" id="adf-hmw-location-error" role="alert" hidden>
                    <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    <div>
                        <strong class="adf-hmw-location-error__title"></strong>
                        <span class="adf-hmw-location-error__msg"></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($location_source === 'google') : ?>
                <script>
                (function () {
                    var hasInteracted = false;
                    var pendingError  = null;
                    var lastShown     = null;

                    function renderError(err) {
                        var box = document.getElementById('adf-hmw-location-error');
                        if (!box || !err) { return; }
                        var t = box.querySelector('.adf-hmw-location-error__title');
                        var m = box.querySelector('.adf-hmw-location-error__msg');
                        if (t) { t.textContent = err.title || ''; }
                        if (m) { m.textContent = err.msg   || ''; }
                        box.hidden = false;
                        lastShown = err;
                    }
                    // Show an error immediately. Used for real API failures the admin
                    // needs to act on (key invalid, Places API disabled, billing, etc.).
                    function showError(title, msg) {
                        var err = { title: title, msg: msg };
                        pendingError = err;
                        renderError(err);
                    }
                    // Queue an error — won't render until the user touches the location
                    // field. Reserved for the "key not set" case so visitors who never
                    // use the field don't see admin-y warnings on page load.
                    function queueError(title, msg) {
                        pendingError = { title: title, msg: msg };
                        if (hasInteracted) { renderError(pendingError); }
                    }

                    function bindInteraction() {
                        var input = document.getElementById('sb_user_address');
                        if (!input || input.dataset.adfErrInteractionBound === '1') { return; }
                        input.dataset.adfErrInteractionBound = '1';
                        var trigger = function () {
                            hasInteracted = true;
                            if (pendingError && pendingError !== lastShown) {
                                renderError(pendingError);
                            }
                        };
                        input.addEventListener('focus', trigger, { once: true });
                        input.addEventListener('input', trigger, { once: true });
                    }
                    if (document.readyState !== 'loading') { bindInteraction(); }
                    else { document.addEventListener('DOMContentLoaded', bindInteraction); }

                    <?php if (empty($gmap_api_key)) : ?>
                    // Key missing — queue the "not set" message and stop. We don't load
                    // any Maps script because there's nothing to load with.
                    queueError(
                        <?php echo wp_json_encode(__('Google Maps API key is not set', 'adforest-elementor')); ?>,
                        <?php echo wp_json_encode(__('Add the key in Theme Options → Maps → Google Maps API Key. Until then the location autocomplete will not work.', 'adforest-elementor')); ?>
                    );
                    return;
                    <?php endif; ?>

                    // gm_authFailure MUST be defined before the Maps script finishes loading.
                    // Google calls this for InvalidKeyMapError, RefererNotAllowedMapError,
                    // ExpiredKeyMapError, BillingNotEnabledMapError, etc. Chain to any
                    // previous handler so we don't clobber the theme's own logic.
                    var prevAuthFail = window.gm_authFailure;
                    window.gm_authFailure = function () {
                        showError(
                            <?php echo wp_json_encode(__('Google Maps API key error', 'adforest-elementor')); ?>,
                            <?php echo wp_json_encode(__('The configured key is invalid, restricted to other domains, expired, over its quota, or has billing disabled. Open the browser console for the exact Google error code, then fix the key in Theme Options → Maps.', 'adforest-elementor')); ?>
                        );
                        if (typeof prevAuthFail === 'function') {
                            try { prevAuthFail.apply(this, arguments); } catch (e) {}
                        }
                    };

                    // Probe the Places API with a throwaway request. gm_authFailure
                    // doesn't fire when the *Maps* JS API key is valid but the *Places*
                    // API isn't enabled on the project — that case shows up here as
                    // REQUEST_DENIED. Also catches OVER_QUERY_LIMIT and friends.
                    function probePlacesApi() {
                        try {
                            var svc = new google.maps.places.AutocompleteService();
                            svc.getPlacePredictions({ input: 'a' }, function (preds, status) {
                                var S = google.maps.places.PlacesServiceStatus;
                                if (status === S.OK || status === S.ZERO_RESULTS) { return; }
                                var detail = '';
                                if (status === S.REQUEST_DENIED) {
                                    detail = <?php echo wp_json_encode(__('The Places API is disabled, billing is off, or the key is restricted from this domain. In Google Cloud Console, enable "Places API" (the legacy one — "Places API (New)" alone will not work with this widget), confirm billing is active, and verify HTTP-referrer restrictions allow this site.', 'adforest-elementor')); ?>;
                                } else if (status === S.OVER_QUERY_LIMIT) {
                                    detail = <?php echo wp_json_encode(__('The Places API has exceeded its quota or rate limit. Check Google Cloud Console → APIs & Services → Quotas.', 'adforest-elementor')); ?>;
                                } else if (status === S.INVALID_REQUEST) {
                                    detail = <?php echo wp_json_encode(__('Places API rejected the request as invalid. Verify the API key configuration.', 'adforest-elementor')); ?>;
                                } else if (status === S.NOT_FOUND) {
                                    detail = <?php echo wp_json_encode(__('Places API endpoint was not found.', 'adforest-elementor')); ?>;
                                } else {
                                    detail = <?php echo wp_json_encode(__('Places API returned an unexpected status: ', 'adforest-elementor')); ?> + status;
                                }
                                showError(
                                    <?php echo wp_json_encode(__('Google Places API issue', 'adforest-elementor')); ?>,
                                    detail
                                );
                            });
                        } catch (e) {
                            showError(
                                <?php echo wp_json_encode(__('Google Places probe failed', 'adforest-elementor')); ?>,
                                (e && e.message) ? String(e.message) : <?php echo wp_json_encode(__('Could not instantiate the Places AutocompleteService.', 'adforest-elementor')); ?>
                            );
                        }
                    }

                    function initAdfHmwGooglePlaces() {
                        var input = document.getElementById('sb_user_address');
                        if (!input || input.dataset.adfGmapInit === '1') { return true; }
                        if (typeof window.google === 'undefined' || !window.google.maps || !window.google.maps.places) {
                            return false;
                        }
                        try {
                            input.dataset.adfGmapInit = '1';
                            var ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });
                            ac.addListener('place_changed', function () {
                                var p = ac.getPlace();
                                var lat = document.getElementById('sb_user_lat');
                                var lng = document.getElementById('sb_user_lng');
                                if (p && p.geometry && p.geometry.location) {
                                    if (lat) { lat.value = p.geometry.location.lat(); }
                                    if (lng) { lng.value = p.geometry.location.lng(); }
                                } else {
                                    if (lat) { lat.value = ''; }
                                    if (lng) { lng.value = ''; }
                                }
                            });
                            // Health-check the Places endpoint once Autocomplete is wired.
                            probePlacesApi();
                        } catch (err) {
                            showError(
                                <?php echo wp_json_encode(__('Google Places initialization failed', 'adforest-elementor')); ?>,
                                (err && err.message) ? String(err.message) : <?php echo wp_json_encode(__('An unknown error occurred while initializing the location autocomplete.', 'adforest-elementor')); ?>
                            );
                        }
                        return true;
                    }

                    // The Google Maps + Places script is enqueued via WordPress on the
                    // server side (handle: google-map-callback), so it's already in the
                    // DOM by the time this widget renders. We just poll for the API.
                    if (initAdfHmwGooglePlaces()) { return; }

                    // Detect outright script-load failures (network down, ad-blocker,
                    // CSP block). We watch the existing handle WP printed.
                    var existingMaps = document.querySelector('script[id*="google-map-callback"], script[src*="maps.googleapis.com/maps/api/js"]');
                    if (existingMaps && !existingMaps.dataset.adfHmwErrBound) {
                        existingMaps.dataset.adfHmwErrBound = '1';
                        existingMaps.addEventListener('error', function () {
                            showError(
                                <?php echo wp_json_encode(__('Google Maps script failed to load', 'adforest-elementor')); ?>,
                                <?php echo wp_json_encode(__('The browser could not reach maps.googleapis.com. Check your network, ad-blockers, or CSP rules.', 'adforest-elementor')); ?>
                            );
                        });
                    }

                    var tries = 0;
                    var iv = setInterval(function () {
                        if (initAdfHmwGooglePlaces() || ++tries > 80) { clearInterval(iv); }
                    }, 150);

                    // Safety net — if 15s passes and Places still isn't usable and no
                    // other error has been queued, queue a generic timeout message.
                    setTimeout(function () {
                        var notInit = (typeof window.google === 'undefined' || !window.google.maps || !window.google.maps.places);
                        if (!pendingError && notInit) {
                            showError(
                                <?php echo wp_json_encode(__('Google Places did not initialize', 'adforest-elementor')); ?>,
                                <?php echo wp_json_encode(__('The Maps API loaded slowly or partially. Verify the API key has the Places API enabled and that referrer restrictions allow this domain.', 'adforest-elementor')); ?>
                            );
                        }
                    }, 15000);
                })();
                </script>
                <?php endif; ?>

                <?php
                // Elementor editor-only warning when Google mode is selected but the
                // Maps API key is missing in Theme Options → Maps. Hidden on the
                // public-facing site so visitors never see it.
                if ($google_mode_missing_key && \Elementor\Plugin::$instance->editor->is_edit_mode()) :
                ?>
                    <div class="elementor-alert elementor-alert-warning" role="alert" style="margin:10px auto 0;max-width:1100px;">
                        <span class="elementor-alert-title"><?php esc_html_e('Google Places: API key missing', 'adforest-elementor'); ?></span>
                        <span class="elementor-alert-description"><?php esc_html_e('Set the Google Maps API key in Theme Options → Maps → Google Maps API Key. The location field will render but autocomplete will not attach until the key is saved.', 'adforest-elementor'); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($show_categories && !empty($cat_grid)) : ?>
                <div class="adf-hmw-hero__cats" data-adf-cats-carousel>
                    <button type="button" class="adf-hmw-hero__cats-nav adf-hmw-hero__cats-nav--prev" aria-label="<?php esc_attr_e('Previous categories', 'adforest-elementor'); ?>" aria-disabled="true">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <div class="adf-hmw-hero__cats-track" role="list">
                        <?php foreach ($cat_grid as $i => $cat) :
                            $palette  = $palettes[$i % count($palettes)];
                            $cat_link = get_term_link($cat);
                            if (is_wp_error($cat_link)) { $cat_link = '#'; }
                            $icon_url = function_exists('adforest_taxonomy_image_url')
                                ? adforest_taxonomy_image_url($cat->term_id, 'thumbnail', false)
                                : '';
                            $count    = (int) $cat->count;
                            ?>
                            <a class="adf-hmw-hero__cat" role="listitem" href="<?php echo esc_url($cat_link); ?>" style="background:<?php echo esc_attr($palette['bg']); ?>;color:<?php echo esc_attr($palette['fg']); ?>;">
                                <span class="adf-hmw-hero__cat-icon" style="color:<?php echo esc_attr($palette['fg']); ?>;">
                                    <?php if ($icon_url) : ?>
                                        <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($cat->name); ?>">
                                    <?php else : ?>
                                        <i class="fa fa-folder-open"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="adf-hmw-hero__cat-name"><?php echo esc_html($cat->name); ?></span>
                                <?php if ($cats_show_count) : ?>
                                    <span class="adf-hmw-hero__cat-count"><?php
                                        printf(
                                            esc_html(_n('%s Ad', '%s Ads', $count, 'adforest-elementor')),
                                            esc_html(number_format_i18n($count))
                                        );
                                    ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="adf-hmw-hero__cats-nav adf-hmw-hero__cats-nav--next" aria-label="<?php esc_attr_e('Next categories', 'adforest-elementor'); ?>">
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
                <script>
                (function () {
                    var carousels = document.querySelectorAll('[data-adf-cats-carousel]');
                    if (!carousels.length) return;
                    carousels.forEach(function (root) {
                        if (root.dataset.adfCatsInit === '1') return;
                        root.dataset.adfCatsInit = '1';
                        var track = root.querySelector('.adf-hmw-hero__cats-track');
                        var prev  = root.querySelector('.adf-hmw-hero__cats-nav--prev');
                        var next  = root.querySelector('.adf-hmw-hero__cats-nav--next');
                        if (!track || !prev || !next) return;

                        function step() {
                            var first = track.querySelector('.adf-hmw-hero__cat');
                            if (!first) return track.clientWidth;
                            var styles = window.getComputedStyle(track);
                            var gap    = parseFloat(styles.columnGap || styles.gap) || 0;
                            return first.getBoundingClientRect().width + gap;
                        }
                        function refresh() {
                            var overflow = track.scrollWidth - track.clientWidth > 1;
                            root.classList.toggle('has-nav', overflow);
                            prev.setAttribute('aria-disabled', track.scrollLeft <= 1 ? 'true' : 'false');
                            next.setAttribute('aria-disabled', (track.scrollLeft + track.clientWidth) >= (track.scrollWidth - 1) ? 'true' : 'false');
                        }
                        prev.addEventListener('click', function () { track.scrollBy({left: -step(), behavior: 'smooth'}); });
                        next.addEventListener('click', function () { track.scrollBy({left:  step(), behavior: 'smooth'}); });
                        track.addEventListener('scroll', refresh, {passive: true});
                        window.addEventListener('resize', refresh);
                        refresh();
                    });
                })();
                </script>
            <?php endif; ?>
        </section>
        <?php
    }
}
