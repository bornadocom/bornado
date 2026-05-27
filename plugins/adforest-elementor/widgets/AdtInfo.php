<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdtInfo extends Widget_Base
{
    public function get_name()
    {
        return 'adt_info_shortcode';
    }

    public function get_title()
    {
        return __("Adt Info", "adforest-elementor");
    }

    public function get_icon()
    {
        return "fa fa-audio-description";
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
        ]);

        $this->add_control(
            'section_title',
            array(
                'label' => __('Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
            )
        );

        $this->add_control(
            'section_description', [
            'label' => esc_html__('Section Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);

        $this->add_control(
            'section_btn_text', [
            'label' => esc_html__('Section Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);

        $this->add_control(
            'section_btn_link', [
            'label' => esc_html__('Section Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);

        $this->add_control(
            'bg_img', [
            'label' => __('Background Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [
                'url' => '',
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
        $params['section_description'] = $atts['section_description'] ?? "";
        $params['section_btn_text'] = $atts['section_btn_text'] ?? "";
        $params['section_btn_link'] = $atts['section_btn_link'] ?? "";
        $params['bg_img'] = $atts['bg_img'] ?? "";

        if (function_exists('adt_info_shortcode')) {
            echo adt_info_shortcode($params);
        }
    }
}