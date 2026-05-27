<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdtCallToAction extends Widget_Base
{
    public function get_name()
    {
        return 'adt_call_to_action_shortcode';
    }

    public function get_title()
    {
        return __("ADT Call to Action", 'adforest-elementor');
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
                'label' => esc_html__('Left', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'right_sec_bg', [
            'label' => esc_html__('Background Color', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFAEF',
        ]);

        $this->add_control(
            'right_img', [
            'label' => __('Advert', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src(),
            ],
        ]);

        $this->add_control(
            'right_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Title',
        ]);

        $this->add_control(
            'right_description', [
            'label' => esc_html__('Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Description',
        ]);

        $this->add_control(
            'right_btn_text', [
            'label' => esc_html__('Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->add_control(
            'right_btn_link', [
            'label' => esc_html__('Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->end_controls_section();

        $this->start_controls_section(
            'left', [
                'label' => esc_html__('Right', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'left_sec_bg', [
            'label' => esc_html__('Background Color', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFAEF',
        ]);

        $this->add_control(
            'left_img', [
            'label' => __('Advert', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src(),
            ],
        ]);

        $this->add_control(
            'left_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Title',
        ]);

        $this->add_control(
            'left_description', [
            'label' => esc_html__('Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Description',
        ]);

        $this->add_control(
            'left_btn_text', [
            'label' => esc_html__('Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->add_control(
            'left_btn_link', [
            'label' => esc_html__('Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['right_sec_bg'] = isset($atts['right_sec_bg']) ? $atts['right_sec_bg'] : "";
        $params['right_img'] = isset($atts['right_img']) ? $atts['right_img'] : "";
        $params['right_title'] = isset($atts['right_title']) ? $atts['right_title'] : "";
        $params['right_description'] = isset($atts['right_description']) ? $atts['right_description'] : "";
        $params['right_btn_text'] = isset($atts['right_btn_text']) ? $atts['right_btn_text'] : "";
        $params['right_btn_link'] = isset($atts['right_btn_link']) ? $atts['right_btn_link'] : "";
        $params['left_sec_bg'] = isset($atts['left_sec_bg']) ? $atts['left_sec_bg'] : "";
        $params['left_img'] = isset($atts['left_img']) ? $atts['left_img'] : "";
        $params['left_title'] = isset($atts['left_title']) ? $atts['left_title'] : "";
        $params['left_description'] = isset($atts['left_description']) ? $atts['left_description'] : "";
        $params['left_btn_text'] = isset($atts['left_btn_text']) ? $atts['left_btn_text'] : "";
        $params['left_btn_link'] = isset($atts['left_btn_link']) ? $atts['left_btn_link'] : "";
        $params['adforest_elementor'] = true;

        if (function_exists('adt_call_to_action_shortcode')) {
            echo adt_call_to_action_shortcode($params);
        }
    }
}