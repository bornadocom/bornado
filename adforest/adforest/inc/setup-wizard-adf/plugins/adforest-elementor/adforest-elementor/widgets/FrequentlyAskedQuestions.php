<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class FrequentlyAskedQuestions extends Widget_Base
{
    public function get_name()
    {
        return 'faqs_short_base';
    }
    public function get_title()
    {
        return __('Adforest FAQs', 'adforest-elementor');
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
                "description" => __("524x464", 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'img_2',
            [ 
                'label' => __('small image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => __("294x280", 'adforest-elementor'),
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'faqs_settings',
            [ 
                'label' => esc_html__('FAQs Settings', 'adforest-elementor'),
            ]
        );
        $faqs_repeater = new \Elementor\Repeater();
        $faqs_repeater->add_control(
            'question',
            [ 
                'label' => __('Question', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Question', 'adforest-elementor'),
            ]
        );
        $faqs_repeater->add_control(
            'answer',
            [ 
                'label' => __('Answer', 'adforest-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'title' => __('Answer', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'faqs',
            [ 
                'label' => __('Add FAQ', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $faqs_repeater->get_controls(),
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
        $params['section_title'] = isset($atts['section_title']) ? $atts['section_title'] : "";
        $params['section_tagline'] = isset($atts['section_tagline']) ? $atts['section_tagline'] : "";
        $params['section_description'] = isset($atts['section_description']) ? $atts['section_description'] : "";
        $params['img_1'] = isset($atts['img_1']) ? $atts['img_1'] : "";
        $params['img_2'] = isset($atts['img_2']) ? $atts['img_2'] : "";
        $params['faqs'] = isset($atts['faqs']) ? $atts['faqs'] : "";
        
        $params['adforest_elementor'] = true;

        if (function_exists('faqs_short_base_func')) {
            echo faqs_short_base_func($params);
        }

    }
}