<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class HorizontalAd extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_horizontal_ad';
    }

    public function get_title()
    {
        return __("Horizontal AD", 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'basic', [
                'label' => esc_html__('Basic', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'ad_type',
            [
                'label' => __('Ad Type', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'image' => __('Image', 'adforest-elementor'),
                    'custom_html' => __('Custom HTML', 'adforest-elementor'),
                    'adsense' => __('Google AdSense', 'adforest-elementor'),
                ],
                'default' => 'image',
            ]
        );

        $this->add_control(
            'ad_cats_ad',
            [
                'label' => __('Horizontal Advertisement image', 'adforest-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                "description" => __("1320×140. For AdSense: paste your full AdSense code (script + ins tags).", 'adforest-elementor'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['ad_cats_ad'] = $atts['ad_cats_ad'] ?? "";
        $params['ad_type'] = $atts['ad_type'] ?? "image";
        $params['adforest_elementor'] = true;

        // In Elementor editor/preview, show a placeholder for AdSense to avoid
        // script injection issues and broken previews.
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
            if ( $params['ad_type'] === 'adsense' && ! empty( $params['ad_cats_ad'] ) ) {
                echo '<section class="adt-horizontal-advert-section">';
                echo '<div class="container"><div class="row"><div class="col-lg-12">';
                echo '<div class="adt-horizontal-ad-box" style="background:#f0f0f0;border:2px dashed #ccc;padding:20px;text-align:center;color:#666;">';
                echo esc_html__( 'Google AdSense Ad — renders on frontend only', 'adforest-elementor' );
                echo '</div>';
                echo '</div></div></div></section>';
                return;
            }
        }

        if (function_exists('adforest_horizontal_ad')) {
            echo adforest_horizontal_ad($params);
        }
    }
}
