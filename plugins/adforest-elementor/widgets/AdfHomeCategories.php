<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Home — Categories grid.
 *
 * Renders the top categories from the `ad_cats` taxonomy as a row
 * of pastel cards. Each card pulls the category's icon via the
 * AdForest helper `adforest_taxonomy_image_url()` (image is set in
 * WP Admin → Listings → Categories → edit term → Image). Falls
 * back to a folder icon when no image is set.
 */
class AdfHomeCategories extends Widget_Base
{
    public function get_name() { return 'adf_home_categories'; }
    public function get_title() { return __('Modern Home — Categories', 'adforest-elementor'); }
    public function get_icon() { return 'eicon-posts-grid'; }
    public function get_categories() { return ['adforest_widgets']; }
    public function get_keywords() { return ['adforest', 'home', 'categories', 'modern']; }

    protected function register_controls()
    {
        $this->start_controls_section('content', [
            'label' => __('Content', 'adforest-elementor'),
        ]);

        $this->add_control('limit', [
            'label' => __('Number of categories', 'adforest-elementor'),
            'type'  => Controls_Manager::NUMBER,
            'default' => 6,
            'min' => 1,
            'max' => 12,
        ]);
        $this->add_control('show_count', [
            'label' => __('Show ad count', 'adforest-elementor'),
            'type'  => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'return_value' => 'yes',
        ]);
        $this->add_control('orderby', [
            'label' => __('Order by', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => 'count',
            'options' => [
                'count' => __('Most used', 'adforest-elementor'),
                'name'  => __('Name (A–Z)', 'adforest-elementor'),
                'term_order' => __('Custom order', 'adforest-elementor'),
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        global $adforest_theme;
        $atts = $this->get_settings_for_display();

        $theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
        $_rgb            = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
        $theme_btn_rgb   = (is_array($_rgb) && count($_rgb) === 3 && $_rgb[0] !== null) ? implode(',', $_rgb) : '255,0,46';

        $limit      = !empty($atts['limit'])   ? (int) $atts['limit']  : 6;
        $orderby    = !empty($atts['orderby']) ? $atts['orderby']      : 'count';
        $show_count = ($atts['show_count'] ?? 'yes') === 'yes';

        $categories = get_terms([
            'taxonomy'   => 'ad_cats',
            'hide_empty' => false,
            'number'     => $limit,
            'orderby'    => $orderby,
            'order'      => $orderby === 'name' ? 'ASC' : 'DESC',
            'parent'     => 0,
        ]);
        if (is_wp_error($categories) || empty($categories)) {
            return;
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
        .adf-hmw-cats{--hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;padding:24px 0;box-sizing:border-box;}
        .adf-hmw-cats *{box-sizing:border-box;}
        .adf-hmw-cats__wrap{max-width:1200px;margin:0 auto;padding:0 24px;}
        .adf-hmw-cats__grid{display:grid;grid-template-columns:repeat(<?php echo (int) min($limit, 6); ?>,minmax(0,1fr));gap:18px;}
        .adf-hmw-cat{display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:14px;padding:24px 14px 18px;text-decoration:none;text-align:center;transition:transform .18s ease,box-shadow .18s ease;color:inherit;min-height:160px;gap:6px;}
        .adf-hmw-cat:hover{transform:translateY(-3px);box-shadow:0 0 12px rgba(15,23,42,.06);}
        .adf-hmw-cat__icon{width:62px;height:62px;border-radius:14px;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:inherit;margin-bottom:6px;overflow:hidden;}
        .adf-hmw-cat__icon img{width:38px;height:38px;object-fit:contain;}
        .adf-hmw-cat__icon i{font-size:24px;}
        .adf-hmw-cat__name{font-weight:500;color:#0f172a;font-size:15px;}
        .adf-hmw-cat__count{font-size:12px;color:#64748b;font-weight:500;}
        @media (max-width:1099px){.adf-hmw-cats__grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
        @media (max-width:600px){.adf-hmw-cats__grid{grid-template-columns:repeat(2,minmax(0,1fr));}.adf-hmw-cat{padding:18px 10px 14px;min-height:140px;}.adf-hmw-cat__icon{width:52px;height:52px;}}
        </style>
        <section class="adf-hmw-cats">
            <div class="adf-hmw-cats__wrap">
                <div class="adf-hmw-cats__grid">
                    <?php foreach ($categories as $i => $cat) :
                        $palette  = $palettes[$i % count($palettes)];
                        $cat_link = get_term_link($cat);
                        if (is_wp_error($cat_link)) { $cat_link = '#'; }
                        $icon_url = function_exists('adforest_taxonomy_image_url')
                            ? adforest_taxonomy_image_url($cat->term_id, 'thumbnail', false)
                            : '';
                        $count    = (int) $cat->count;
                        ?>
                        <a class="adf-hmw-cat" href="<?php echo esc_url($cat_link); ?>" style="background:<?php echo esc_attr($palette['bg']); ?>;color:<?php echo esc_attr($palette['fg']); ?>;">
                            <span class="adf-hmw-cat__icon" style="color:<?php echo esc_attr($palette['fg']); ?>;">
                                <?php if ($icon_url) : ?>
                                    <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($cat->name); ?>">
                                <?php else : ?>
                                    <i class="fa fa-folder-open"></i>
                                <?php endif; ?>
                            </span>
                            <span class="adf-hmw-cat__name"><?php echo esc_html($cat->name); ?></span>
                            <?php if ($show_count) : ?>
                                <span class="adf-hmw-cat__count"><?php
                                    printf(
                                        esc_html(_n('%s Ad', '%s Ads', $count, 'adforest-elementor')),
                                        esc_html(number_format_i18n($count))
                                    );
                                ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
}
