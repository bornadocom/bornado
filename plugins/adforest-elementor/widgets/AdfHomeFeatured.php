<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Home — Featured Advertisings.
 *
 * Pulls recent ads flagged as featured via the
 * `_adforest_is_feature == 1` post meta, with a client-side tab
 * filter across the top categories. Each card shows the ad image,
 * brand-colored "Featured" badge, heart fav, title (2-line clamp),
 * location, price, and condition pill.
 */
class AdfHomeFeatured extends Widget_Base
{
    public function get_name() { return 'adf_home_featured'; }
    public function get_title() { return __('Modern Home — Featured Ads', 'adforest-elementor'); }
    public function get_icon() { return 'eicon-products'; }
    public function get_categories() { return ['adforest_widgets']; }
    public function get_keywords() { return ['adforest', 'home', 'featured', 'ads', 'modern']; }

    protected function register_controls()
    {
        $this->start_controls_section('content', [
            'label' => __('Content', 'adforest-elementor'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Featured Advertisings', 'adforest-elementor'),
        ]);
        $this->add_control('subtitle', [
            'label' => __('Subtitle', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXTAREA,
            'rows'  => 2,
            'default' => __('Discover our featured listings and find the best deals on a wide range of products and services.', 'adforest-elementor'),
        ]);
        $this->add_control('ad_type', [
            'label' => __('Ad type to show', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => 'featured',
            'options' => [
                'featured' => __('Featured only', 'adforest-elementor'),
                'simple'   => __('Simple only (not featured)', 'adforest-elementor'),
                'both'     => __('Both featured and simple', 'adforest-elementor'),
            ],
            'description' => __('Filters which posts appear. "Featured only" uses the _adforest_is_feature meta = 1 condition.', 'adforest-elementor'),
        ]);
        $this->add_control('limit', [
            'label' => __('Ads to show', 'adforest-elementor'),
            'type'  => Controls_Manager::NUMBER,
            'default' => 8,
            'min' => 1,
            'max' => 24,
        ]);
        $this->add_control('tab_limit', [
            'label' => __('Category tabs', 'adforest-elementor'),
            'type'  => Controls_Manager::NUMBER,
            'default' => 7,
            'min' => 0,
            'max' => 15,
            'description' => __('0 hides the filter row entirely.', 'adforest-elementor'),
        ]);
        $this->add_control('view_all_url', [
            'label' => __('View All URL', 'adforest-elementor'),
            'type'  => Controls_Manager::URL,
            'placeholder' => __('https://...', 'adforest-elementor'),
            'default' => ['url' => home_url('/?post_type=ad_post')],
        ]);

        // Grid type — local override that shadows the global
        // `adforest_grid_layout` theme option just for this widget. The six
        // theme styles below all delegate to the theme's existing card
        // renderers (adforest_ad_grid_1/2/3, adforest_load_search_card) so
        // they match exactly how the search page would render them.
        // "Modern Card (Carousel)" is the widget's own custom design with
        // horizontal scroll.
        $this->add_control('grid_type', [
            'label' => __('Grid Type', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => 'custom',
            'options' => [
                'custom'       => __('Modern Card (Default — carousel)', 'adforest-elementor'),
                'simple'       => __('Style 1 (Classic)', 'adforest-elementor'),
                'with_labels'  => __('Style 2 (Classic)', 'adforest-elementor'),
                'modern'       => __('Style 3 (Classic)', 'adforest-elementor'),
                'modern_card'  => __('Modern Card', 'adforest-elementor'),
                'compact_grid' => __('Compact Grid', 'adforest-elementor'),
                'list_view'    => __('List View', 'adforest-elementor'),
            ],
            'description' => __('Mirrors the styles available under Theme Options → Ads settings → Search setting → Grid Type, but applies only to this widget. "Default" uses the carousel-based modern cards; the other six render in a static grid (rows of cards).', 'adforest-elementor'),
        ]);
        $this->add_control('cols_per_row', [
            'label' => __('Cards per row', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => '4',
            'options' => [
                '2' => __('2 cards', 'adforest-elementor'),
                '3' => __('3 cards', 'adforest-elementor'),
                '4' => __('4 cards', 'adforest-elementor'),
            ],
            'condition' => ['grid_type!' => ['custom', 'list_view']],
            'description' => __('Applies to the static grid layouts. List View always renders one card per row.', 'adforest-elementor'),
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

        $limit     = !empty($atts['limit'])     ? (int) $atts['limit']     : 8;
        $tab_limit = isset($atts['tab_limit'])  ? (int) $atts['tab_limit'] : 7;
        $ad_type   = isset($atts['ad_type'])    ? $atts['ad_type']         : 'featured';
        $heading   = $atts['heading']  ?? '';
        $subtitle  = $atts['subtitle'] ?? '';
        $view_all  = isset($atts['view_all_url']['url']) ? $atts['view_all_url']['url'] : home_url('/?post_type=ad_post');

        // Grid type — 'custom' keeps the widget's own carousel-based modern
        // cards; the other six slugs mirror the theme option in Ads → Search
        // and delegate to the theme's existing renderers via a temporary
        // override of $adforest_theme['adforest_grid_layout'].
        $valid_grids  = ['custom', 'simple', 'with_labels', 'modern', 'modern_card', 'compact_grid', 'list_view'];
        $grid_type    = isset($atts['grid_type']) && in_array($atts['grid_type'], $valid_grids, true)
            ? $atts['grid_type']
            : 'custom';
        $cols_per_row = isset($atts['cols_per_row']) ? (int) $atts['cols_per_row'] : 4;
        if (!in_array($cols_per_row, [2, 3, 4], true)) { $cols_per_row = 4; }
        if ($grid_type === 'list_view') { $cols_per_row = 1; }

        // The Search 2.0 card templates (modern_card / compact_grid / list_view)
        // rely on assets/css/search-ui.css for their typography & layout. The
        // theme only enqueues that stylesheet on search / taxonomy / author
        // pages, so on the home page the cards render with browser-default h3
        // sizing and overflow their column. Force-load it here when needed.
        $search_ui_url = '';
        if (in_array($grid_type, ['modern_card', 'compact_grid', 'list_view'], true)) {
            $handle = 'adforest-search-ui';
            $ver    = defined('ADFOREST_VERSION') ? ADFOREST_VERSION : '1.0.0';
            $search_ui_url = trailingslashit(get_template_directory_uri()) . 'assets/css/search-ui.css?ver=' . rawurlencode($ver);
            // Enqueue the standard way — works on the live page where the
            // widget renders before wp_print_footer_scripts. Idempotent if
            // the theme already enqueued it elsewhere.
            if (!wp_style_is($handle, 'enqueued') && !wp_style_is($handle, 'done')) {
                wp_enqueue_style(
                    $handle,
                    trailingslashit(get_template_directory_uri()) . 'assets/css/search-ui.css',
                    [],
                    $ver
                );
            }
        }

        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$';

        $query_args = [
            'post_type'      => 'ad_post',
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ];
        if ($ad_type === 'featured') {
            $query_args['meta_query'] = [[
                'key'     => '_adforest_is_feature',
                'value'   => '1',
                'compare' => '=',
            ]];
        } elseif ($ad_type === 'simple') {
            // Anything that isn't explicitly featured: meta missing OR value != 1.
            $query_args['meta_query'] = [
                'relation' => 'OR',
                ['key' => '_adforest_is_feature', 'compare' => 'NOT EXISTS'],
                ['key' => '_adforest_is_feature', 'value' => '1', 'compare' => '!='],
            ];
        }
        // 'both' → no meta filter, return all ads regardless of featured flag.

        $featured = new \WP_Query($query_args);
        if (!$featured->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $tabs = [];
        if ($tab_limit > 0) {
            $tabs = get_terms([
                'taxonomy'   => 'ad_cats',
                'hide_empty' => false,
                'number'     => $tab_limit,
                'orderby'    => 'count',
                'order'      => 'DESC',
                'parent'     => 0,
            ]);
            if (is_wp_error($tabs)) { $tabs = []; }
        }

        $uid = 'adfhmwf_' . wp_unique_id();
        ?>
        <?php if ($search_ui_url) :
            // Belt-and-suspenders: print the search-ui stylesheet link
            // directly in the widget output. wp_enqueue_style above is the
            // canonical path, but if the widget renders after wp_print_styles
            // has finalised the <head> (Elementor preview iframe, certain
            // caching plugins), the enqueue call silently no-ops. Echoing
            // the link here guarantees the modern_card / compact_grid /
            // list_view templates always pick up their typography & layout.
            ?>
            <link rel="stylesheet" href="<?php echo esc_url($search_ui_url); ?>">
        <?php endif; ?>
        <style>
        .adf-hmw-feat{--hm-brand:<?php echo esc_attr($theme_btn_color); ?>;--hm-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;--hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;--hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;padding:48px 0;background:#fff;box-sizing:border-box;}
        .adf-hmw-feat *{box-sizing:border-box;}
        .adf-hmw-feat__wrap{max-width:1200px;margin:0 auto;padding:0 24px;}
        .adf-hmw-feat__head{text-align:center;margin:0 0 30px;}
        .adf-hmw-feat__head h2{font-size:28px;font-weight:600;color:#0f172a;margin:0 0 20px;letter-spacing:-.02em;display:inline-flex;align-items:center;gap:8px;}
        .adf-hmw-feat__head h2 i{color:var(--hm-brand);font-size:22px;}
        .adf-hmw-feat__head p{margin:0 auto;max-width:520px;color:#64748b;font-size:14px;line-height:1.55;}
        .adf-hmw-feat__tabs{
            display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;
            background:#fff;border:1px solid #eef1f5;border-radius:12px;
            box-shadow:0 2px 12px rgba(15,23,42,.04),0 1px 3px rgba(15,23,42,.03);
            padding:8px 18px;margin-bottom:28px;
        }
        .adf-hmw-feat__tablist{display:flex;align-items:center;gap:4px;flex-wrap:wrap;list-style:none;margin:0;padding:0;}
        .adf-hmw-feat__tablist li{list-style:none;}
        .adf-hmw-feat__tab{background:transparent;border:0;color:#475569;font-size:14px;font-weight:500;padding:10px 14px;cursor:pointer;position:relative;font-family:inherit;border-radius:8px;}
        .adf-hmw-feat__tab:hover{color:#0f172a;}
        .adf-hmw-feat__tab.is-active{color:var(--hm-brand);}
        .adf-hmw-feat__tab.is-active::after{content:"";position:absolute;left:14px;right:14px;bottom:2px;height:2px;background:var(--hm-brand);border-radius:2px;}
        .adf-hmw-feat__viewall{color:var(--hm-brand);font-weight:700;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
        .adf-hmw-feat__viewall:hover{color:var(--hm-brand-hover);}

        /* Carousel track — cards visible per view is driven by --per-view.
           Custom mode keeps the original 4; theme grid modes use the admin's
           "Cards per row" choice. Single-row layout with scroll-snap. */
        .adf-hmw-feat__cards{--per-view:<?php echo (int) ($grid_type === 'custom' ? 4 : $cols_per_row); ?>;--gap:20px;position:relative;}
        .adf-hmw-feat__cards-track{display:flex;gap:var(--gap);overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding:4px 0;}
        .adf-hmw-feat__cards-track::-webkit-scrollbar{display:none;}
        .adf-hmw-feat__nav-bottom{display:none;justify-content:flex-end;gap:10px;margin-top:18px;}
        .adf-hmw-feat__cards.has-nav .adf-hmw-feat__nav-bottom{display:flex;}
        .adf-hmw-feat__nav-btn{
            width:40px;height:40px;border-radius:50%;
            background:#fff;border:1px solid rgba(var(--hm-brand-rgb),.25);color:var(--hm-brand);
            font-size:14px;cursor:pointer;
            display:inline-flex;align-items:center;justify-content:center;
            box-shadow:0 1px 3px rgba(15,23,42,.05);
            transition:background .18s ease,color .18s ease,transform .15s ease,border-color .18s ease,opacity .18s ease;
            font-family:inherit;padding:0;
        }
        .adf-hmw-feat__nav-btn:hover:not([aria-disabled="true"]){background:var(--hm-brand);color:var(--hm-brand-text,#fff);transform:translateY(-1px);border-color:var(--hm-brand);}
        .adf-hmw-feat__nav-btn[aria-disabled="true"]{opacity:.4;cursor:not-allowed;}

        .adf-hmw-card{flex:0 0 calc((100% - (var(--per-view) - 1) * var(--gap)) / var(--per-view));scroll-snap-align:start;background:#fff;border:1px solid #eef1f5;border-radius:14px;overflow:hidden;text-decoration:none;color:inherit;transition:transform .18s ease,box-shadow .18s ease;display:flex;flex-direction:column;}
        .adf-hmw-card:hover{transform:translateY(-3px);box-shadow:0 0 12px rgba(15,23,42,.06);}
        .adf-hmw-card__media{position:relative;aspect-ratio:4/3;background:#f5f6fb;overflow:hidden;}
        .adf-hmw-card__media img{width:100%;height:100%;object-fit:cover;display:block;}
        .adf-hmw-card__badge{position:absolute;top:10px;left:10px;background:var(--hm-brand);color:var(--hm-brand-text);border-radius:6px;padding:4px 10px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;}
        .adf-hmw-card__fav{position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.92);color:#475569;display:inline-flex;align-items:center;justify-content:center;border:0;cursor:pointer;font-size:13px;}
        .adf-hmw-card__fav:hover{color:var(--hm-brand);}
        .adf-hmw-card__body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:8px;flex:1;}
        .adf-hmw-card__title{font-size:15px;font-weight:700;color:#0f172a;margin:0;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
        .adf-hmw-card__loc{display:flex;align-items:center;justify-content:space-between;font-size:12.5px;color:#64748b;gap:8px;}
        .adf-hmw-card__loc span:first-child{display:inline-flex;align-items:center;gap:5px;}
        .adf-hmw-card__loc i{color:#94a3b8;font-size:11px;}
        .adf-hmw-card__foot{margin-top:auto;display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid #eef1f5;}
        .adf-hmw-card__price{font-size:17px;font-weight:800;color:#0f172a;}
        .adf-hmw-card__condition{background:#dcfce7;color:#166534;font-size:11px;font-weight:700;padding:3px 10px;border-radius:6px;text-transform:capitalize;}
        .adf-hmw-card__condition.is-used{background:#fef3c7;color:#92400e;}
        @media (max-width:1099px){.adf-hmw-feat__cards{--per-view:<?php echo (int) ($grid_type === 'custom' ? 2 : min($cols_per_row, 2)); ?>;}}
        @media (max-width:600px){.adf-hmw-feat__cards{--per-view:1;}.adf-hmw-feat__nav-btn{width:36px;height:36px;font-size:13px;}}

        /* Theme-grid card wrappers share the carousel's flex-sizing so all
           rendered cards line up in one scrollable row. Resets stray margins
           on the inner theme-card markup so the gap is consistent. */
        .adf-hmw-feat__card-wrap{flex:0 0 calc((100% - (var(--per-view) - 1) * var(--gap)) / var(--per-view));scroll-snap-align:start;min-width:0;display:flex;flex-direction:column;}
        .adf-hmw-feat__card-wrap > .item{margin:0;width:100%;height:100%;display:flex;flex-direction:column;}
        .adf-hmw-feat__card-wrap .adf-card{height:100%;}

        /* Search 2.0 card overrides — scoped to our widget so layout works
           even when assets/css/search-ui.css loses specificity (or fails to
           load) against the home page's global CSS cascade. The native rules
           in that file expect height:100% on the inner img + aspect-ratio
           on the media wrapper; cascading WP/theme rules can suppress them. */
        .adf-hmw-feat__card-wrap .adf-card-modern__media,
        .adf-hmw-feat__card-wrap .adf-card-compact__media{position:relative;overflow:hidden;aspect-ratio:4/3;background:#f1f5f9;}
        .adf-hmw-feat__card-wrap .adf-card-modern__media-link,
        .adf-hmw-feat__card-wrap .adf-card-compact__media a:first-child{display:block;width:100%;height:100%;}
        .adf-hmw-feat__card-wrap .adf-card-modern__img,
        .adf-hmw-feat__card-wrap .adf-card-compact__img{width:100%!important;height:100%!important;object-fit:cover;display:block;}
        .adf-hmw-feat__card-wrap .adf-card-list__media{position:relative;overflow:hidden;aspect-ratio:4/3;background:#f1f5f9;}
        .adf-hmw-feat__card-wrap .adf-card-list__img{width:100%!important;height:100%!important;object-fit:cover;display:block;}

        /* Title typography — defends against page-template h3/h4 styles
           leaking onto the cards. */
        .adf-hmw-feat__card-wrap .adf-card-modern__title{font-size:16px;font-weight:500;line-height:1.35;margin:0;color:#0f172a;}
        .adf-hmw-feat__card-wrap .adf-card-compact__title{font-size:14px;font-weight:500;line-height:1.35;margin:0;color:#0f172a;}
        .adf-hmw-feat__card-wrap .adf-card-list__title{font-size:16px;font-weight:500;line-height:1.35;margin:0;color:#0f172a;}

        /* Price + footer — strong/small inherit from ancestors so any large
           heading/strong styling on the page would bleed in. Pin them. */
        .adf-hmw-feat__card-wrap .adf-card-modern__price,
        .adf-hmw-feat__card-wrap .adf-card-modern__price strong{font-size:18px!important;font-weight:500!important;line-height:1.2!important;color:#0f172a;}
        .adf-hmw-feat__card-wrap .adf-card-compact__price,
        .adf-hmw-feat__card-wrap .adf-card-compact__price strong{font-size:14px!important;font-weight:500!important;line-height:1.2!important;color:#0f172a;}
        .adf-hmw-feat__card-wrap .adf-card-list__price,
        .adf-hmw-feat__card-wrap .adf-card-list__price strong{font-size:18px!important;font-weight:500!important;line-height:1.2!important;color:#0f172a;}
        .adf-hmw-feat__card-wrap .adf-card-modern__price small,
        .adf-hmw-feat__card-wrap .adf-card-compact__price small,
        .adf-hmw-feat__card-wrap .adf-card-list__price small{font-size:11px!important;font-weight:600!important;color:#64748b;}

        /* Meta rows (location, date) and CTA — small, muted, never-grown.
           Stack vertically so long locations stay on one line (truncated
           with an ellipsis) and the posted date always sits below them.
           Without this, search-ui.css uses flex-wrap which causes short
           locations to share a row with the date and long ones to wrap. */
        .adf-hmw-feat__card-wrap .adf-card-modern__meta,
        .adf-hmw-feat__card-wrap .adf-card-list__meta{display:flex;flex-direction:column;align-items:flex-start;gap:4px;font-size:12px;color:#64748b;}
        .adf-hmw-feat__card-wrap .adf-card-modern__location,
        .adf-hmw-feat__card-wrap .adf-card-modern__date,
        .adf-hmw-feat__card-wrap .adf-card-list__location,
        .adf-hmw-feat__card-wrap .adf-card-list__date{display:inline-flex;align-items:center;gap:5px;max-width:100%;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .adf-hmw-feat__card-wrap .adf-card-compact__location{font-size:11px;color:#64748b;display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .adf-hmw-feat__card-wrap .adf-card-modern__cta,
        .adf-hmw-feat__card-wrap .adf-card-list__cta{font-size:13px;font-weight:600;}

        /* Body padding + footer — keep the layout consistent if search-ui.css
           never makes it onto the page. */
        .adf-hmw-feat__card-wrap .adf-card-modern__body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:8px;flex-grow:1;}
        .adf-hmw-feat__card-wrap .adf-card-compact__body{padding:10px 12px 12px;display:flex;flex-direction:column;gap:4px;}
        .adf-hmw-feat__card-wrap .adf-card-modern__footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:10px;border-top:1px solid rgba(15,23,42,.06);}

        /* Category pill + badges — pin to small uppercase chip styling. */
        .adf-hmw-feat__card-wrap .adf-card-modern__category{align-self:flex-start;font-size:11px;font-weight:400;text-transform:uppercase;letter-spacing:.06em;padding:3px 10px;border-radius:999px;text-decoration:none;}
        .adf-hmw-feat__card-wrap .adf-badge{font-size:11px;font-weight:400;letter-spacing:.04em;text-transform:uppercase;padding:3px 9px;border-radius:6px;color:#fff;line-height:1.4;}
        </style>
        <section class="adf-hmw-feat" id="<?php echo esc_attr($uid); ?>">
            <div class="adf-hmw-feat__wrap">
                <?php if ($heading || $subtitle) : ?>
                    <div class="adf-hmw-feat__head">
                        <?php if ($heading) : ?>
                            <h2><i class="fa fa-fire"></i> <?php echo esc_html($heading); ?></h2>
                        <?php endif; ?>
                        <?php if ($subtitle) : ?>
                            <p><?php echo esc_html($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($tabs)) : ?>
                    <div class="adf-hmw-feat__tabs">
                        <ul class="adf-hmw-feat__tablist" role="tablist">
                            <li><button type="button" class="adf-hmw-feat__tab is-active" data-filter="all"><?php esc_html_e('All', 'adforest-elementor'); ?></button></li>
                            <?php foreach ($tabs as $tab) : ?>
                                <li><button type="button" class="adf-hmw-feat__tab" data-filter="cat-<?php echo esc_attr($tab->term_id); ?>"><?php echo esc_html($tab->name); ?></button></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo esc_url($view_all); ?>" class="adf-hmw-feat__viewall">
                            <?php esc_html_e('View All', 'adforest-elementor'); ?> <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="adf-hmw-feat__cards" data-adf-feat-carousel>
                    <div class="adf-hmw-feat__cards-track">
                    <?php if ($grid_type === 'custom') :
                        while ($featured->have_posts()) : $featured->the_post();
                        $ad_id      = get_the_ID();
                        $is_featured = (string) get_post_meta($ad_id, '_adforest_is_feature', true) === '1';

                        // AdForest stores ad images in _sb_photo_arrangement_ meta or
                        // attached media, NOT in the WP featured image. Match the theme's
                        // own helper (used by ad-img-carousel.php) so cards actually load
                        // images regardless of whether a WP featured image is set.
                        $thumb = '';
                        if (function_exists('adforest_get_ad_images')) {
                            $images = adforest_get_ad_images($ad_id);
                            if (!empty($images) && is_array($images)) {
                                $first    = reset($images);
                                $first_id = is_object($first) ? (int) ($first->ID ?? 0) : (int) $first;
                                if ($first_id > 0) {
                                    $src = wp_get_attachment_image_src($first_id, 'medium_large');
                                    if (is_array($src) && !empty($src[0])) {
                                        $thumb = $src[0];
                                    }
                                }
                            }
                        }
                        if (!$thumb) {
                            $thumb = get_the_post_thumbnail_url($ad_id, 'medium_large');
                        }
                        if (!$thumb && function_exists('adforest_get_ad_default_image_url')) {
                            $thumb = adforest_get_ad_default_image_url('medium_large');
                        }
                        if (!$thumb) {
                            $thumb = get_template_directory_uri() . '/images/Photo-Not-Available.png';
                        }

                        $price     = get_post_meta($ad_id, '_adforest_ad_price', true);
                        $condition = get_post_meta($ad_id, '_adforest_ad_condition', true);
                        $cond_cls  = (strtolower((string) $condition) === 'used') ? 'is-used' : '';
                        $cat_ids   = wp_get_post_terms($ad_id, 'ad_cats', ['fields' => 'ids']);
                        $cat_class = '';
                        if (!empty($cat_ids) && is_array($cat_ids)) {
                            $cat_class = 'cat-' . implode(' cat-', array_map('intval', $cat_ids));
                        }
                        $loc_terms = wp_get_post_terms($ad_id, 'ad_country', ['number' => 1]);
                        $loc_label = (!empty($loc_terms) && !is_wp_error($loc_terms)) ? $loc_terms[0]->name : '';
                        ?>
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="adf-hmw-card" data-cats="<?php echo esc_attr($cat_class); ?>">
                            <div class="adf-hmw-card__media">
                                <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                                <?php if ($is_featured) : ?>
                                    <span class="adf-hmw-card__badge"><?php esc_html_e('Featured', 'adforest-elementor'); ?></span>
                                <?php endif; ?>
                                <button type="button" class="adf-hmw-card__fav" aria-label="<?php esc_attr_e('Add to favourites', 'adforest-elementor'); ?>" onclick="event.preventDefault();">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="adf-hmw-card__body">
                                <h3 class="adf-hmw-card__title"><?php echo esc_html(get_the_title()); ?></h3>
                                <div class="adf-hmw-card__loc">
                                    <?php if ($loc_label) : ?>
                                        <span><i class="fa fa-map-marker-alt"></i> <?php echo esc_html($loc_label); ?></span>
                                    <?php else : ?>
                                        <span></span>
                                    <?php endif; ?>
                                </div>
                                <div class="adf-hmw-card__foot">
                                    <span class="adf-hmw-card__price">
                                        <?php
                                        if ($price !== '' && is_numeric($price)) {
                                            echo esc_html($currency_symbol) . esc_html(number_format_i18n((float) $price, 2));
                                        } else {
                                            echo esc_html__('—', 'adforest-elementor');
                                        }
                                        ?>
                                    </span>
                                    <?php if ($condition !== '') : ?>
                                        <span class="adf-hmw-card__condition <?php echo esc_attr($cond_cls); ?>"><?php echo esc_html($condition); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; wp_reset_postdata();
                    else :
                        // Theme-grid path — delegates each card to the theme's own
                        // renderer so it matches what the search page would show.
                        // Override $adforest_theme['adforest_grid_layout'] just for
                        // this loop, then restore so we don't leak into later code.
                        $prev_grid_layout = isset($adforest_theme['adforest_grid_layout']) ? $adforest_theme['adforest_grid_layout'] : null;
                        $adforest_theme['adforest_grid_layout'] = $grid_type;
                        while ($featured->have_posts()) : $featured->the_post();
                        $ad_id = get_the_ID();

                        // Build the cat class first — it powers the tab filter
                        // regardless of which renderer we dispatch to below.
                        $cat_ids = wp_get_post_terms($ad_id, 'ad_cats', ['fields' => 'ids']);
                        $cat_class = '';
                        if (!empty($cat_ids) && is_array($cat_ids)) {
                            $cat_class = 'cat-' . implode(' cat-', array_map('intval', $cat_ids));
                        }

                        // Pull the rich ad-details bundle the theme renderers
                        // expect. Falls back to a minimal stub if the helper
                        // isn't loaded (unlikely on a live AdForest install).
                        $ad_details = function_exists('get_ad_post_details') ? get_ad_post_details($ad_id) : [];
                        if (empty($ad_details) || !is_array($ad_details)) {
                            continue;
                        }
                        $first_img          = isset($ad_details['img'])              ? $ad_details['img']              : '';
                        $ad_permalink       = isset($ad_details['ad_link'])          ? $ad_details['ad_link']          : get_permalink($ad_id);
                        $heart_class        = isset($ad_details['heart_class'])      ? $ad_details['heart_class']      : 'far fa-heart';
                        $is_featured        = !empty($ad_details['is_featured']);
                        $all_ad_images      = isset($ad_details['all_ad_images'])    ? $ad_details['all_ad_images']    : [];
                        $ad_poster_img      = isset($ad_details['ad_poster_img'])    ? $ad_details['ad_poster_img']    : '';
                        $ad_poster_name     = isset($ad_details['ad_poster_name'])   ? $ad_details['ad_poster_name']   : '';
                        $ad_categories_post = isset($ad_details['categories'])       ? $ad_details['categories']       : '';
                        $price_html         = isset($ad_details['price_html'])       ? $ad_details['price_html']       : '';
                        $is_fav             = !empty($ad_details['is_fav']);
                        $fav_title          = $is_fav ? esc_html__('Click to remove from favourite', 'adforest') : esc_html__('Click to make it favourite', 'adforest');
                        $fav_extra          = $is_fav ? ' ad-favourited' : '';
                        $title_raw          = isset($ad_details['ad_title']) ? $ad_details['ad_title'] : get_the_title($ad_id);
                        $location_raw       = isset($ad_details['location']) ? $ad_details['location'] : '';
                        $truncated_title    = function_exists('truncate_string') ? truncate_string($title_raw, 40)   : $title_raw;
                        $truncated_location = function_exists('truncate_string') ? truncate_string($location_raw, 40) : $location_raw;
                        $ad_title           = $truncated_title;
                        $ad_type_meta       = get_post_meta($ad_id, '_adforest_ad_type', true);
                        $featured_tag       = $is_featured
                            ? '<img style="transform: rotate(180deg);" src="' . esc_url(get_template_directory_uri()) . '/images/featured.png" alt="featured-tag" class="featured-tag">'
                            : '';
                        ?>
                        <div class="adf-hmw-feat__card-wrap" data-cats="<?php echo esc_attr($cat_class); ?>">
                            <?php
                            if ($grid_type === 'simple' && function_exists('adforest_ad_grid_1')) {
                                echo adforest_ad_grid_1($ad_permalink, $first_img, $is_featured, $ad_categories_post, $ad_details, $truncated_title, $truncated_location, $price_html, $heart_class);
                            } elseif ($grid_type === 'with_labels' && function_exists('adforest_ad_grid_2')) {
                                ?>
                                <div class="item search_with_labels_grid">
                                    <?php echo adforest_ad_grid_2($all_ad_images, $ad_permalink, $is_featured, $ad_poster_img, $ad_poster_name, $ad_title, $truncated_location, $price_html, $heart_class, $ad_id); ?>
                                </div>
                                <?php
                            } elseif ($grid_type === 'modern' && function_exists('adforest_ad_grid_3')) {
                                ?>
                                <div class="item search_with_labels_grid">
                                    <?php echo adforest_ad_grid_3($all_ad_images, $ad_permalink, $heart_class, $featured_tag, $ad_poster_img, $ad_poster_name, $ad_type_meta, $ad_title, $price_html, $truncated_location, $ad_id); ?>
                                </div>
                                <?php
                            } elseif (function_exists('adforest_load_search_card') && in_array($grid_type, ['modern_card', 'compact_grid', 'list_view'], true)) {
                                ?>
                                <div class="item adf-card-item adf-card-item--<?php echo esc_attr($grid_type); ?>">
                                    <?php
                                    adforest_load_search_card($grid_type, [
                                        'ad_permalink'           => $ad_permalink,
                                        'first_img'              => $first_img,
                                        'all_ad_images'          => $all_ad_images,
                                        'is_featured'            => $is_featured,
                                        'heart_class'            => $heart_class,
                                        'is_fav'                 => $is_fav,
                                        'fav_title'              => $fav_title,
                                        'fav_extra'              => $fav_extra,
                                        'truncated_title'        => $truncated_title,
                                        'ad_title'               => $ad_title,
                                        'truncated_location'     => $truncated_location,
                                        'price_html'             => $price_html,
                                        'ad_categories_post'     => $ad_categories_post,
                                        'ad_poster_img'          => $ad_poster_img,
                                        'ad_poster_name'         => $ad_poster_name,
                                        'ad_type'                => $ad_type_meta,
                                        'ad_details'             => $ad_details,
                                        'top_bar_specific_style' => '',
                                        'featured_tag'           => $featured_tag,
                                    ]);
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    <?php endwhile; wp_reset_postdata();

                        // Restore the global so the rest of the page (footers,
                        // sidebars, other widgets) sees the original setting.
                        if ($prev_grid_layout === null) {
                            unset($adforest_theme['adforest_grid_layout']);
                        } else {
                            $adforest_theme['adforest_grid_layout'] = $prev_grid_layout;
                        }
                    endif; ?>
                    </div><!-- /.adf-hmw-feat__cards-track -->
                    <div class="adf-hmw-feat__nav-bottom">
                        <button type="button" class="adf-hmw-feat__nav-btn adf-hmw-feat__nav-btn--prev" aria-label="<?php esc_attr_e('Previous', 'adforest-elementor'); ?>" aria-disabled="true">
                            <i class="fa fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="adf-hmw-feat__nav-btn adf-hmw-feat__nav-btn--next" aria-label="<?php esc_attr_e('Next', 'adforest-elementor'); ?>">
                            <i class="fa fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div><!-- /.adf-hmw-feat__cards -->
            </div>
        </section>
        <script>
        (function () {
            var root  = document.getElementById('<?php echo esc_js($uid); ?>');
            if (!root) return;
            var carousel = root.querySelector('[data-adf-feat-carousel]');
            var track    = carousel ? carousel.querySelector('.adf-hmw-feat__cards-track') : null;
            var prev     = carousel ? carousel.querySelector('.adf-hmw-feat__nav-btn--prev') : null;
            var next     = carousel ? carousel.querySelector('.adf-hmw-feat__nav-btn--next') : null;
            var tabs     = root.querySelectorAll('.adf-hmw-feat__tab');
            // Filterable cards live in both modes inside the carousel track —
            // custom uses .adf-hmw-card, theme grids use .adf-hmw-feat__card-wrap.
            var cards    = track ? track.querySelectorAll('.adf-hmw-card, .adf-hmw-feat__card-wrap') : [];

            // Tab filter — toggles card visibility by data-cats class slug.
            tabs.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var f = btn.getAttribute('data-filter');
                    tabs.forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    cards.forEach(function (c) {
                        if (f === 'all') { c.style.display = ''; return; }
                        var cats = (c.getAttribute('data-cats') || '').split(' ');
                        c.style.display = cats.indexOf(f) !== -1 ? '' : 'none';
                    });
                    if (track) { track.scrollLeft = 0; }
                    refresh();
                });
            });

            // Carousel — bottom-right prev/next arrows scroll one card width + gap.
            function step() {
                if (!track) return 0;
                var first = track.querySelector('.adf-hmw-card, .adf-hmw-feat__card-wrap');
                if (!first) return track.clientWidth;
                var styles = window.getComputedStyle(track);
                var gap    = parseFloat(styles.columnGap || styles.gap) || 0;
                return first.getBoundingClientRect().width + gap;
            }
            function refresh() {
                if (!track || !carousel) return;
                var overflow = track.scrollWidth - track.clientWidth > 1;
                carousel.classList.toggle('has-nav', overflow);
                if (prev) prev.setAttribute('aria-disabled', track.scrollLeft <= 1 ? 'true' : 'false');
                if (next) next.setAttribute('aria-disabled', (track.scrollLeft + track.clientWidth) >= (track.scrollWidth - 1) ? 'true' : 'false');
            }
            if (prev) prev.addEventListener('click', function () { if (track) track.scrollBy({left: -step(), behavior: 'smooth'}); });
            if (next) next.addEventListener('click', function () { if (track) track.scrollBy({left:  step(), behavior: 'smooth'}); });

            // Owl Carousel init for theme-rendered cards. Style 2 / Style 3
            // wrap their ad image inside .adt-property-img-carousel which the
            // owl-carousel CSS keeps `display:none` until JS adds .owl-loaded.
            // The theme's global init fires at document.ready and can miss
            // late-laid-out flex children, so we belt-and-suspenders here.
            function initOwl() {
                if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.owlCarousel !== 'function') {
                    return false;
                }
                var $ = window.jQuery;
                $(root).find('.adt-property-img-carousel').each(function () {
                    var $c = $(this);
                    if ($c.hasClass('owl-loaded') || $c.data('adfFeatOwlInit')) { return; }
                    $c.data('adfFeatOwlInit', true);
                    $c.owlCarousel({
                        loop: false,
                        margin: 0,
                        nav: true,
                        dots: false,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        responsive: { 0: { items: 1 }, 600: { items: 1 }, 1000: { items: 1 } }
                    });
                });
                return true;
            }
            if (!initOwl()) {
                var owlTries = 0;
                var owlIv = setInterval(function () {
                    if (initOwl() || ++owlTries > 60) { clearInterval(owlIv); }
                }, 150);
            }
            if (track) {
                track.addEventListener('scroll', refresh, {passive: true});
                window.addEventListener('resize', refresh);
                refresh();
            }
        })();
        </script>
        <?php
    }
}
