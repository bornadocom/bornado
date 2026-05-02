<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AboutUsModern extends Widget_Base
{

    public function get_name()
    {
        return 'about_us_modern_short_base';
    }

    public function get_title()
    {
        return __('About us Modern', 'adforest-elementor');
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

//        $this->add_control(
//            'bg_img',
//            [
//                'label' => __('Background Image', 'adforest-elementor'),
//                'type' => \Elementor\Controls_Manager::MEDIA,
//            ]
//        );

        $this->add_control(
            'section_title',
            [
                'label' => __('Section Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Section Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_tagline',
            [
                'label' => __('Section Tag Line', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Section Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_description',
            [
                'label' => __('Section Description', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );

        $this->add_control(
            'img_1',
            [
                'label' => __('large image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
            ]
        );

        $this->add_control(
            'video_link',
            [
                'label' => __('URL or Link', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                "description" => __("Youtube video link", 'adforest-elementor'),
                'default' => '',
            ]
        );

        $this->add_control(
            'exp_head',
            [
                'label' => __('Experience Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                "description" => __("Experience heading", 'adforest-elementor'),
                'default' => '',
            ]
        );

        $this->add_control(
            'exp_desc',
            [
                'label' => __('Experience Description', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                "description" => __("Experience description", 'adforest-elementor'),
                'default' => '',
            ]
        );

        $this->add_control(
            'btn_text', [
                'label' => __('Button Text', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'btn_link', [
                'label' => __('Button Link', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->end_controls_section();
        $this->start_controls_section(
            'features_settings',
            [
                'label' => esc_html__('Features Settings', 'adforest-elementor'),
            ]
        );
        $adforest_elementor_repeater = new \Elementor\Repeater();
        $adforest_elementor_repeater->add_control(
            'title',
            [
                'label' => __('Service', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('service', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'features',
            [
                'label' => __('Add Client', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $adforest_elementor_repeater->get_controls(),
                'default' => [],
            ]
        );
        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['section_tagline'] = $atts['section_tagline'] ?? "";
        $params['section_description'] = $atts['section_description'] ?? "";
//        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['img_1'] = $atts['img_1'] ?? "";
        $params['exp_head'] = $atts['exp_head'] ?? "";
        $params['exp_desc'] = $atts['exp_desc'] ?? "";
        $params['video_link'] = $atts['video_link'] ?? "";
        $params['features'] = $atts['features'] ?? "";
        $params['btn_text'] = $atts['btn_text'] ?? "";
        $params['btn_link'] = $atts['btn_link'] ?? "";
        $params['adforest_elementor'] = true;

        if (function_exists('about_us_modern_short_base')) {
            echo about_us_modern_short_base($params);
        }
    }
}