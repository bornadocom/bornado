<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Home — Pricing Plans.
 *
 * Renders AdForest's WooCommerce package products
 * (`adforest_classified_pkgs`) as a 3-card pricing row. The middle
 * card gets a "Popular" badge. Falls back to a static
 * Basic / Gold / Silver layout when WooCommerce isn't active or
 * no packages exist.
 */
class AdfHomePricing extends Widget_Base
{
    public function get_name() { return 'adf_home_pricing'; }
    public function get_title() { return __('Modern Home — Pricing Plans', 'adforest-elementor'); }
    public function get_icon() { return 'eicon-price-table'; }
    public function get_categories() { return ['adforest_widgets']; }
    public function get_keywords() { return ['adforest', 'home', 'pricing', 'plans', 'packages', 'modern']; }

    /**
     * Build the option list of `adforest_classified_pkgs` products
     * for the manual Repeater select. Empty when WooCommerce is off.
     */
    private function get_package_options()
    {
        $options = ['' => __('— Select package —', 'adforest-elementor')];
        if (!function_exists('wc_get_products')) {
            return $options;
        }
        $products = wc_get_products([
            'type'    => 'adforest_classified_pkgs',
            'status'  => 'publish',
            'limit'   => -1,
            'orderby' => 'title',
            'order'   => 'ASC',
        ]);
        if (!empty($products) && is_array($products)) {
            foreach ($products as $p) {
                $options[(string) $p->get_id()] = $p->get_title();
            }
        }
        return $options;
    }

    protected function register_controls()
    {
        $this->start_controls_section('content', [
            'label' => __('Content', 'adforest-elementor'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Our Pricing Plans', 'adforest-elementor'),
        ]);
        $this->add_control('subtitle', [
            'label' => __('Subtitle', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXTAREA,
            'rows'  => 2,
            'default' => __('Choose the perfect plan for your needs and start posting ads today. All plans include core features.', 'adforest-elementor'),
        ]);

        $this->add_control('source_mode', [
            'label' => __('Source', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto'   => __('Auto — recent packages', 'adforest-elementor'),
                'manual' => __('Manual — choose specific packages', 'adforest-elementor'),
            ],
        ]);

        $this->add_control('limit', [
            'label' => __('Plans to show', 'adforest-elementor'),
            'type'  => Controls_Manager::NUMBER,
            'default' => 3,
            'min' => 1,
            'max' => 4,
            'condition' => ['source_mode' => 'auto'],
        ]);
        $this->add_control('popular_index', [
            'label' => __('"Popular" card index', 'adforest-elementor'),
            'type'  => Controls_Manager::NUMBER,
            'default' => 1,
            'min' => -1,
            'max' => 3,
            'description' => __('Zero-indexed (0 = first, 1 = middle, etc.). Set to -1 to disable.', 'adforest-elementor'),
            'condition' => ['source_mode' => 'auto'],
        ]);

        // Manual selection — repeater with package picker + per-item "Popular" toggle.
        $repeater = new Repeater();
        $repeater->add_control('product_id', [
            'label' => __('Package', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => '',
            'options' => $this->get_package_options(),
        ]);
        $repeater->add_control('is_popular', [
            'label' => __('Mark as Popular', 'adforest-elementor'),
            'type'  => Controls_Manager::SWITCHER,
            'default' => '',
            'return_value' => 'yes',
            'description' => __('Highlights this card with the "Popular" badge. Enable on one card at most.', 'adforest-elementor'),
        ]);

        $this->add_control('manual_items', [
            'label' => __('Packages', 'adforest-elementor'),
            'type'  => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'condition' => ['source_mode' => 'manual'],
            'title_field' => '{{{ is_popular === "yes" ? "★ " : "" }}}Package',
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

        $heading       = $atts['heading']  ?? '';
        $subtitle      = $atts['subtitle'] ?? '';
        $limit         = !empty($atts['limit']) ? (int) $atts['limit'] : 3;
        $popular_idx   = isset($atts['popular_index']) ? (int) $atts['popular_index'] : 1;
        $source_mode   = ($atts['source_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';

        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$';

        // Packages page fallback (CTA target for static-fallback plans)
        $packages_page_id = isset($adforest_theme['sb_packages_page']) ? $adforest_theme['sb_packages_page'] : '';
        $packages_url     = $packages_page_id ? get_permalink($packages_page_id) : '#';

        // Build a normalized $plans array of items shaped as:
        //   ['product' => WC_Product, 'is_popular' => bool]
        $plans = [];

        if ($source_mode === 'manual') {
            $items = !empty($atts['manual_items']) && is_array($atts['manual_items']) ? $atts['manual_items'] : [];
            foreach ($items as $item) {
                $pid = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                if ($pid <= 0) { continue; }
                $product = function_exists('wc_get_product') ? wc_get_product($pid) : null;
                if (!$product) { continue; }
                $plans[] = [
                    'product'    => $product,
                    'is_popular' => isset($item['is_popular']) && $item['is_popular'] === 'yes',
                ];
            }
            // Admin explicitly picked manual — if no valid items, render nothing.
            if (empty($plans)) {
                return;
            }
        } else {
            if (function_exists('wc_get_products')) {
                $plan_products = wc_get_products([
                    'type'   => 'adforest_classified_pkgs',
                    'status' => 'publish',
                    'limit'  => $limit,
                ]);
                if (!empty($plan_products) && is_array($plan_products)) {
                    foreach ($plan_products as $idx => $p) {
                        $plans[] = [
                            'product'    => $p,
                            'is_popular' => ($popular_idx >= 0 && $idx === $popular_idx),
                        ];
                    }
                }
            }
        }
        $plan_icons = ['fa-paper-plane', 'fa-crown', 'fa-star', 'fa-gem'];

        // Column count for the responsive grid — uses live count when real plans
        // are available, otherwise falls back to the configured $limit (static).
        $col_count = !empty($plans) ? count($plans) : $limit;
        $col_count = max(1, min((int) $col_count, 3));
        ?>
        <style>
        .adf-hmw-plans{--hm-brand:<?php echo esc_attr($theme_btn_color); ?>;--hm-brand-hover:<?php echo esc_attr($theme_btn_hover); ?>;--hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;--hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;padding:48px 0 56px;background:#fafbfd;border-top:1px solid #eef1f5;box-sizing:border-box;}
        .adf-hmw-plans *{box-sizing:border-box;}
        .adf-hmw-plans__wrap{max-width:1200px;margin:0 auto;padding:0 24px;}
        .adf-hmw-plans__head{text-align:center;margin:0 0 30px;}
        .adf-hmw-plans__head h2{font-size:28px;font-weight:600;color:#0f172a;margin:0 0 20px;letter-spacing:-.02em;display:inline-flex;align-items:center;gap:8px;}
        .adf-hmw-plans__head h2 i{color:var(--hm-brand);font-size:22px;}
        .adf-hmw-plans__head p{margin:0 auto;max-width:520px;color:#64748b;font-size:14px;line-height:1.55;}
        .adf-hmw-plans__grid{display:grid;grid-template-columns:repeat(<?php echo (int) $col_count; ?>,minmax(0,1fr));gap:22px;}
        .adf-hmw-plan{position:relative;background:#fff;border:1px solid #eef1f5;border-radius:18px;padding:30px 26px;display:flex;flex-direction:column;gap:14px;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;}
        .adf-hmw-plan:hover{transform:translateY(-3px);box-shadow:0 0 12px rgba(15,23,42,.06);}
        .adf-hmw-plan__icon{width:54px;height:54px;border-radius:14px;background:rgba(var(--hm-brand-rgb),.10);color:var(--hm-brand);display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:4px;}
        .adf-hmw-plan__name{font-size:18px;font-weight:600;color:#0f172a;margin:0;letter-spacing:-.01em;}
        .adf-hmw-plan__price{display:flex;align-items:baseline;gap:6px;}
        .adf-hmw-plan__price strong{font-size:32px;font-weight:800;color:#0f172a;letter-spacing:-.02em;line-height:1;}
        .adf-hmw-plan__price span{font-size:13px;color:#64748b;font-weight:600;}
        .adf-hmw-plan__feats{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;font-size:13.5px;color:#475569;}
        .adf-hmw-plan__feats li{display:flex;align-items:center;gap:10px;}
        .adf-hmw-plan__feats li i{color:#10b981;font-size:11px;width:18px;height:18px;border-radius:50%;background:rgba(16,185,129,.10);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;}
        .adf-hmw-plan__feats li.is-off{color:#94a3b8;}
        .adf-hmw-plan__feats li.is-off i{color:#ef4444;background:rgba(239,68,68,.10);}
        .adf-hmw-plan__cta{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1.5px solid #eef1f5;background:#fff;color:#1f2937;border-radius:10px;padding:11px 18px;font-size:14px;font-weight:500;text-decoration:none;transition:all .15s ease;margin-top:6px;}
        .adf-hmw-plan__cta:hover{background:var(--hm-brand);color:var(--hm-brand-text);border-color:var(--hm-brand);}
        .adf-hmw-plan.is-popular{background:linear-gradient(180deg,#fef9c3 0%,#fef3c7 100%);border-color:#fde68a;}
        .adf-hmw-plan.is-popular::before{content:"<?php echo esc_attr__('Popular', 'adforest-elementor'); ?>";position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#f59e0b;color:#fff;border-radius:999px;padding:5px 16px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;}
        .adf-hmw-plan.is-popular .adf-hmw-plan__cta{background:#f59e0b;color:#fff;border-color:#f59e0b;}
        .adf-hmw-plan.is-popular .adf-hmw-plan__cta:hover{background:#d97706;border-color:#d97706;}
        @media (max-width:1099px){.adf-hmw-plans__grid{grid-template-columns:1fr;}}
        </style>
        <section class="adf-hmw-plans">
            <div class="adf-hmw-plans__wrap">
                <?php if ($heading || $subtitle) : ?>
                    <div class="adf-hmw-plans__head">
                        <?php if ($heading) : ?>
                            <h2><i class="fa fa-star"></i> <?php echo esc_html($heading); ?></h2>
                        <?php endif; ?>
                        <?php if ($subtitle) : ?>
                            <p><?php echo esc_html($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="adf-hmw-plans__grid">
                    <?php if (!empty($plans)) :
                        foreach ($plans as $idx => $row) :
                            $plan       = $row['product'];
                            $is_pop     = !empty($row['is_popular']);
                            $product_id = $plan->get_id();
                            $title      = $plan->get_title();
                            $price_raw  = $plan->get_price();
                            $is_free    = (float) $price_raw === 0.0;
                            $cart_url   = $plan->add_to_cart_url();
                            // AdForest stores package limits under the `package_*` meta
                            // keys on the adforest_classified_pkgs product. Mirror the
                            // set of features the legacy Packages widget renders (see
                            // widget_shortcodes.php → $package_featured_ads etc.) so
                            // this widget shows the same data without duplication.
                            $pkg_validity    = get_post_meta($product_id, 'package_expiry_days',           true);
                            $pkg_ads         = get_post_meta($product_id, 'package_free_ads',              true);
                            $pkg_ad_expiry   = get_post_meta($product_id, 'package_ad_expiry_days',        true);
                            $pkg_featured    = get_post_meta($product_id, 'package_featured_ads',          true);
                            $pkg_feat_expiry = get_post_meta($product_id, 'package_adFeatured_expiry_days',true);
                            $pkg_bumpups     = get_post_meta($product_id, 'package_bump_ads',              true);
                            $pkg_images      = get_post_meta($product_id, 'package_num_of_images',         true);
                            $pkg_bidding     = get_post_meta($product_id, 'package_allow_bidding',         true);
                            $pkg_video       = get_post_meta($product_id, 'package_video_links',           true);
                            $pkg_tags        = get_post_meta($product_id, 'package_allow_tags',            true);

                            // AdForest convention: -1 = Unlimited, 0 / blank = off,
                            // any positive integer = hard cap. For Yes/No toggles
                            // "yes" / "no" strings are used.
                            $unlimited_label = __('Unlimited', 'adforest-elementor');
                            $num_row = function ($value, $label) use ($unlimited_label) {
                                if ($value === '' || $value === null) {
                                    return null; // hide row entirely when admin didn't set it
                                }
                                if ((string) $value === '-1') {
                                    return ['on' => true, 'text' => $label . ': ' . $unlimited_label];
                                }
                                $n = is_numeric($value) ? (int) $value : 0;
                                if ($n > 0) {
                                    return ['on' => true, 'text' => $label . ': ' . (string) $n];
                                }
                                return ['on' => false, 'text' => $label];
                            };
                            $bool_row = function ($value, $label) {
                                $v = strtolower((string) $value);
                                if ($v === '' || $v === 'no' || $v === '0' || $v === 'false') {
                                    return ['on' => false, 'text' => $label];
                                }
                                return ['on' => true, 'text' => $label];
                            };

                            // Only render the features that exist on the original
                            // Packages page — no synthetic "Post Ads / Edit Ads /
                            // Delete Ads" rows. Customer Support stays as a
                            // marketing line below the package meta.
                            $feat_rows = [];

                            $optional = [
                                [$num_row,  $pkg_validity,    __('Validity (Days)',    'adforest-elementor')],
                                [$num_row,  $pkg_ads,         __('Ads',                'adforest-elementor')],
                                [$num_row,  $pkg_ad_expiry,   __('Ad Expiry (Days)',   'adforest-elementor')],
                                [$num_row,  $pkg_featured,    __('Feature Ads',        'adforest-elementor')],
                                [$num_row,  $pkg_feat_expiry, __('Featured Expiry',    'adforest-elementor')],
                                [$num_row,  $pkg_bumpups,     __('Bump-up Ads',        'adforest-elementor')],
                                [$num_row,  $pkg_images,      __('Images per Ad',      'adforest-elementor')],
                                [$bool_row, $pkg_bidding,     __('Allow Bidding',      'adforest-elementor')],
                                [$bool_row, $pkg_video,       __('Video URLs',         'adforest-elementor')],
                                [$bool_row, $pkg_tags,        __('Allow Tags',         'adforest-elementor')],
                            ];
                            foreach ($optional as [$resolver, $val, $label]) {
                                // Numeric rows are skipped when the meta is blank,
                                // matching the existing Packages widget behaviour.
                                // Yes/No rows always render so admins can see what's
                                // disabled at a glance.
                                if ($resolver === $num_row && ($val === '' || $val === null)) { continue; }
                                $row = $resolver($val, $label);
                                if ($row !== null) {
                                    $feat_rows[] = $row;
                                }
                            }
                            ?>
                            <article class="adf-hmw-plan <?php echo $is_pop ? 'is-popular' : ''; ?>">
                                <span class="adf-hmw-plan__icon"><i class="fa <?php echo esc_attr($plan_icons[$idx % count($plan_icons)]); ?>"></i></span>
                                <h3 class="adf-hmw-plan__name"><?php echo esc_html($title); ?></h3>
                                <div class="adf-hmw-plan__price">
                                    <?php if ($is_free) : ?>
                                        <strong><?php echo esc_html($currency_symbol); ?>0</strong>
                                        <span>/<?php esc_html_e('Free', 'adforest-elementor'); ?></span>
                                    <?php else : ?>
                                        <strong><?php echo wp_kses_post(wc_price($price_raw)); ?></strong>
                                        <span>/<?php esc_html_e('Monthly', 'adforest-elementor'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <ul class="adf-hmw-plan__feats">
                                    <?php foreach ($feat_rows as $r) : ?>
                                        <li class="<?php echo $r['on'] ? '' : 'is-off'; ?>"><i class="fa <?php echo $r['on'] ? 'fa-check' : 'fa-times'; ?>"></i> <?php echo esc_html($r['text']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="<?php echo esc_url($cart_url); ?>" class="adf-hmw-plan__cta"><?php esc_html_e('Get Started', 'adforest-elementor'); ?></a>
                            </article>
                        <?php endforeach;
                    else :
                        $static = [
                            ['name' => __('Basic Plan',  'adforest-elementor'), 'price' => '0',     'period' => __('Free',    'adforest-elementor'), 'icon' => 'fa-paper-plane', 'images' => '3',  'feat' => false],
                            ['name' => __('Gold Plan',   'adforest-elementor'), 'price' => '19.99', 'period' => __('Monthly', 'adforest-elementor'), 'icon' => 'fa-crown',       'images' => '10', 'feat' => true],
                            ['name' => __('Silver Plan', 'adforest-elementor'), 'price' => '9.99',  'period' => __('Monthly', 'adforest-elementor'), 'icon' => 'fa-star',        'images' => '5',  'feat' => false],
                        ];
                        $static = array_slice($static, 0, $limit);
                        foreach ($static as $idx => $plan) :
                            $is_pop = ($popular_idx >= 0 && $idx === $popular_idx);
                            ?>
                            <article class="adf-hmw-plan <?php echo $is_pop ? 'is-popular' : ''; ?>">
                                <span class="adf-hmw-plan__icon"><i class="fa <?php echo esc_attr($plan['icon']); ?>"></i></span>
                                <h3 class="adf-hmw-plan__name"><?php echo esc_html($plan['name']); ?></h3>
                                <div class="adf-hmw-plan__price">
                                    <strong><?php echo esc_html($currency_symbol . $plan['price']); ?></strong>
                                    <span>/<?php echo esc_html($plan['period']); ?></span>
                                </div>
                                <ul class="adf-hmw-plan__feats">
                                    <li><i class="fa fa-check"></i> <?php esc_html_e('Post Ads', 'adforest-elementor'); ?></li>
                                    <li><i class="fa fa-check"></i> <?php esc_html_e('Edit Ads', 'adforest-elementor'); ?></li>
                                    <li><i class="fa fa-check"></i> <?php esc_html_e('Delete Ads', 'adforest-elementor'); ?></li>
                                    <li class="<?php echo $plan['feat'] ? '' : 'is-off'; ?>"><i class="fa <?php echo $plan['feat'] ? 'fa-check' : 'fa-times'; ?>"></i> <?php esc_html_e('Feature Ads', 'adforest-elementor'); ?></li>
                                    <li><i class="fa fa-check"></i> <?php esc_html_e('Images per Ad', 'adforest-elementor'); ?>: <?php echo esc_html($plan['images']); ?></li>
                                    <li class="<?php echo $plan['feat'] ? '' : 'is-off'; ?>"><i class="fa <?php echo $plan['feat'] ? 'fa-check' : 'fa-times'; ?>"></i> <?php esc_html_e('Customer Support', 'adforest-elementor'); ?></li>
                                </ul>
                                <a href="<?php echo esc_url($packages_url); ?>" class="adf-hmw-plan__cta"><?php esc_html_e('Get Started', 'adforest-elementor'); ?></a>
                            </article>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>
        </section>
        <?php
    }
}
