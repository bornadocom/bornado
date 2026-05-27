<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class FlashSaleWidget extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_flash_sale_shortcode';
    }
    public function get_title()
    {
        return __('Flash Sale Widget', 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return [ 'adforest_widgets' ];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'basic',
            [
                'label' => esc_html__('Basic', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'bg_color', [
                'label' => __('Background Color', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::COLOR,
            ]
        );

        $this->add_control(
            'header_image',
            [
                'label' => __('Header image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
            ]
        );

        $this->add_control(
            'left_img_ad',
            [
                'label' => __('Left Image Ad', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
            ]
        );

        $this->add_control(
            'right_img_ad',
            [
                'label' => __('Right Image Ad', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
            ]
        );

        $this->add_control(
            'no_of_products',
            [
                'label' => __('No of Ads to Show', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6
            ]
        );
        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['bg_color'] = isset($atts['bg_color']) ? $atts['bg_color'] : "";
        $params['header_image'] = isset($atts['header_image']) ? $atts['header_image'] : "";
        $params['left_img_ad'] = isset($atts['left_img_ad']) ? $atts['left_img_ad'] : "";
        $params['right_img_ad'] = isset($atts['right_img_ad']) ? $atts['right_img_ad'] : "";
        $params['no_of_products'] = isset($atts['no_of_products']) ? $atts['no_of_products'] : "";

        if (function_exists('adforest_flash_sale_shortcode')) {
            echo adforest_flash_sale_shortcode($params);
        }
    }
}