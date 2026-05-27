<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdListModern extends Widget_Base
{
    public function get_name()
    {
        return 'ads_list_modern_shortcode';
    }

    public function get_title()
    {
        return __("Ads List Modern", 'adforest-elementor');
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
                'label' => esc_html__('Right', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);

        $this->add_control(
            'description', [
            'label' => esc_html__('Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);

        $this->add_control(
            'type_of_ads',
            [
                'label' => esc_html__('Select Ad Type', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => ['simple' => 'Simple', 'featured' => 'Featured', 'both' => 'Both'],
                'default' => 'featured',
            ]
        );

        $this->add_control(
            'number_of_ads',
            [
                'label' => esc_html__('Select Number of Ads to Show', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['title'] = isset($atts['title']) ? $atts['title'] : "";
        $params['description'] = isset($atts['description']) ? $atts['description'] : "";
        $params['type_of_ads'] = isset($atts['type_of_ads']) ? $atts['type_of_ads'] : "";
        $params['number_of_ads'] = isset($atts['number_of_ads']) ? $atts['number_of_ads'] : "";
        $params['adforest_elementor'] = true;

        if (function_exists('ads_list_modern_shortcode')) {
            echo ads_list_modern_shortcode($params);
        }
    }
}