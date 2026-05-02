<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class CyberSaleHero extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_cyber_sale_hero';
    }

    public function get_title()
    {
        return __("Cyber Sale Hero", 'adforest_elementor');
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
            'section_sale_text', [
            'label' => esc_html__('Sale Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Button Text",
        ]);

        $this->add_control(
            'sale_end_date', [
            'label' => esc_html__( 'Sale End Date', 'adforest-elementor' ),
            'type' => \Elementor\Controls_Manager::DATE_TIME,
        ]);

        $this->add_control(
            'section_bg_img', [
            'label' => esc_html__('Section Background Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            "description" => __("724×335", 'adforest-elementor'),
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src('1320x140'),
            ],
        ]);

        $this->add_control(
            'section_main_img', [
            'label' => esc_html__('Section Main Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src(),
            ],
        ]);

        $this->add_control(
            'section_second_img', [
            'label' => esc_html__('Section Sale Text Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src('397x170'),
            ],
        ]);

        $this->add_control(
            'section_discount_img', [
            'label' => esc_html__('Section Discount Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src('156x144'),
            ],
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
        $params['section_sale_text'] = $atts['section_sale_text'] ?? "";
        $params['sale_end_date'] = $atts['sale_end_date'] ?? "";
        $params['section_bg_img'] = $atts['section_bg_img'] ?? "";
        $params['section_main_img'] = $atts['section_main_img'] ?? "";
        $params['section_second_img'] = $atts['section_second_img'] ?? "";
        $params['section_discount_img'] = $atts['section_discount_img'] ?? "";

        if (function_exists('adforest_cyber_sale_hero_shortcode')) {
            echo adforest_cyber_sale_hero_shortcode($params);
        }
    }
}