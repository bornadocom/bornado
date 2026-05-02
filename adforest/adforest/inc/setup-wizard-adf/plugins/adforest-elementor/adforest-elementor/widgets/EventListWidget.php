<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class EventListWidget extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_event_list_shortcode';
    }

    public function get_title()
    {
        return __("Event List Widget", 'adforest-elementor');
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
            'main_sec_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Latest Events',
        ]);

        $this->add_control(
            'main_sec_description', [
            'label' => esc_html__('Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Description',
        ]);

        $this->add_control(
            'main_sec_btn_text', [
            'label' => esc_html__('Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'View All',
        ]);

        $this->add_control(
            'main_sec_btn_link', [
            'label' => esc_html__('Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->add_control(
            'title_limit', [
            'label' => esc_html__('Event Title Limit (In Characters)', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 100,
        ]);

        $this->add_control(
            'main_section_ppp', [
            'label' => esc_html__('Number of Ads to show in main section?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 20,
            'step' => 1,
            'default' => 5,
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['main_sec_title'] = isset($atts['main_sec_title']) ? $atts['main_sec_title'] : "";
        $params['main_sec_description'] = isset($atts['main_sec_description']) ? $atts['main_sec_description'] : "";
        $params['main_sec_btn_text'] = isset($atts['main_sec_btn_text']) ? $atts['main_sec_btn_text'] : "";
        $params['main_sec_btn_link'] = isset($atts['main_sec_btn_link']) ? $atts['main_sec_btn_link'] : "";
        $params['main_section_ppp'] = isset($atts['main_section_ppp']) ? $atts['main_section_ppp'] : "";
        $params['title_limit'] = isset($atts['title_limit']) ? $atts['title_limit'] : "";
        $params['adforest_elementor'] = true;

        if (function_exists('adforest_event_list_shortcode')) {
            echo adforest_event_list_shortcode($params);
        }
    }
}