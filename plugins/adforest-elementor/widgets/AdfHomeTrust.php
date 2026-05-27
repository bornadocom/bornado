<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Home — Trust Strip.
 *
 * A four-cell strip with icon + title + small description per
 * cell. Each cell is editable via a repeater so admins can swap
 * labels / icons / descriptions without touching code. Honors the
 * theme-options brand color for the icon tint.
 */
class AdfHomeTrust extends Widget_Base
{
    public function get_name() { return 'adf_home_trust'; }
    public function get_title() { return __('Modern Home — Trust Strip', 'adforest-elementor'); }
    public function get_icon() { return 'eicon-info-box'; }
    public function get_categories() { return ['adforest_widgets']; }
    public function get_keywords() { return ['adforest', 'home', 'trust', 'badges', 'modern']; }

    protected function register_controls()
    {
        $this->start_controls_section('content', [
            'label' => __('Content', 'adforest-elementor'),
        ]);

        $repeater = new Repeater();
        $repeater->add_control('icon', [
            'label' => __('Icon class', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => 'fa fa-tag',
            'description' => __('Font Awesome class — e.g. "fa fa-tag", "fa fa-shield-alt".', 'adforest-elementor'),
        ]);
        $repeater->add_control('title', [
            'label' => __('Title', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('100% Free', 'adforest-elementor'),
        ]);
        $repeater->add_control('text', [
            'label' => __('Description', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXTAREA,
            'rows'  => 2,
            'default' => __('Post ads with no hidden fees, ever.', 'adforest-elementor'),
        ]);

        $this->add_control('cells', [
            'label' => __('Cells', 'adforest-elementor'),
            'type'  => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'default' => [
                ['icon' => 'fa fa-tag',         'title' => __('100% Free', 'adforest-elementor'), 'text' => __('Post ads with no hidden fees, ever.', 'adforest-elementor')],
                ['icon' => 'fa fa-shield-alt',  'title' => __('Secure',    'adforest-elementor'), 'text' => __('Every listing is reviewed before going live.', 'adforest-elementor')],
                ['icon' => 'fa fa-handshake',   'title' => __('Trusted',   'adforest-elementor'), 'text' => __('Thousands of buyers and sellers every day.', 'adforest-elementor')],
                ['icon' => 'fa fa-headset',     'title' => __('Support',   'adforest-elementor'), 'text' => __('Friendly help is one click away.', 'adforest-elementor')],
            ],
            'title_field' => '{{{ title }}}',
        ]);

        $this->add_control('use_brand_bg', [
            'label' => __('Tint background with brand color', 'adforest-elementor'),
            'type'  => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'return_value' => 'yes',
            'description' => __('When off, the strip uses a neutral white background.', 'adforest-elementor'),
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

        $cells        = !empty($atts['cells']) && is_array($atts['cells']) ? $atts['cells'] : [];
        $use_brand_bg = ($atts['use_brand_bg'] ?? 'yes') === 'yes';
        $cell_count   = max(1, count($cells));
        ?>
        <style>
        .adf-hmw-trust{--hm-brand:<?php echo esc_attr($theme_btn_color); ?>;--hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;<?php echo $use_brand_bg ? 'background:rgba(var(--hm-brand-rgb),.05);border-top:1px solid rgba(var(--hm-brand-rgb),.12);' : 'background:#fff;border-top:1px solid #eef1f5;'; ?>padding:36px 0;box-sizing:border-box;}
        .adf-hmw-trust *{box-sizing:border-box;}
        .adf-hmw-trust__inner{max-width:1200px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:repeat(<?php echo (int) min($cell_count, 4); ?>,1fr);gap:24px;}
        .adf-hmw-trust__cell{display:flex;align-items:center;gap:14px;}
        .adf-hmw-trust__icon{width:50px;height:50px;flex-shrink:0;border-radius:12px;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:var(--hm-brand);font-size:20px;box-shadow:0 0 6px rgba(15,23,42,.04);}
        .adf-hmw-trust__txt strong{display:block;font-size:15px;font-weight:500;color:#0f172a;margin-bottom:2px;}
        .adf-hmw-trust__txt p{margin:0;color:#64748b;font-size:12.5px;line-height:1.5;}
        @media (max-width:1099px){.adf-hmw-trust__inner{grid-template-columns:repeat(2,1fr);}}
        @media (max-width:600px){.adf-hmw-trust{padding:26px 0;}.adf-hmw-trust__inner{grid-template-columns:1fr;gap:16px;padding:0 18px;}}
        </style>
        <section class="adf-hmw-trust" aria-label="<?php esc_attr_e('Trust badges', 'adforest-elementor'); ?>">
            <div class="adf-hmw-trust__inner">
                <?php foreach ($cells as $cell) :
                    $icon  = !empty($cell['icon'])  ? $cell['icon']  : 'fa fa-check';
                    $title = !empty($cell['title']) ? $cell['title'] : '';
                    $text  = !empty($cell['text'])  ? $cell['text']  : '';
                    ?>
                    <div class="adf-hmw-trust__cell">
                        <span class="adf-hmw-trust__icon"><i class="<?php echo esc_attr($icon); ?>"></i></span>
                        <div class="adf-hmw-trust__txt">
                            <strong><?php echo esc_html($title); ?></strong>
                            <?php if ($text) : ?>
                                <p><?php echo esc_html($text); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
