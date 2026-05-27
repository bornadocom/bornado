<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MainHero5 extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_main_hero5';
    }

    public function get_title()
    {
        return __("Main Hero 5", 'adforest_elementor');
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
            'main', [
            'label' => esc_html__('Main', 'adforest-elementor'),
        ]);

        $this->add_control(
            'section_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Title",
        ]);

        $this->add_control(
            'section_tagline', [
            'label' => esc_html__('Tagline', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Tagline",
        ]);

        $this->add_control(
            'section_btn_text', [
            'label' => esc_html__('Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Button Text",
        ]);

        $this->add_control(
            'section_btn_link', [
            'label' => esc_html__('Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Button Link",
        ]);

        $this->add_control(
            'section_bg_img', [
            'label' => esc_html__('Section Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            "description" => __("724×335", 'adforest-elementor'),
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src('1320x140'),
            ],
        ]);

        $this->add_control(
            'section_bg_color', [
            'label' => esc_html__('Section Background Color', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => "##FFFAEF",
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['section_tagline'] = $atts['section_tagline'] ?? "";
        $params['section_btn_text'] = $atts['section_btn_text'] ?? "";
        $params['section_btn_link'] = $atts['section_btn_link'] ?? "";
        $params['section_bg_img'] = $atts['section_bg_img'] ?? "";
        $params['section_bg_color'] = $atts['section_bg_color'] ?? "";

        if (function_exists('main_hero_5_shortcode')) {
            echo main_hero_5_shortcode($params);
        }
    }
}