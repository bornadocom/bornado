<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class EventHomeHero extends Widget_Base {
    public function get_name()
    {
        return 'adforest_event_home_hero';
    }

    public function get_title()
    {
        return __('Event Home Hero', 'adforest-elementor');
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
            'section_bg_image', [
                'label' => __('Section Background Image', 'sb_pro'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'title' => '',
            ]
        );

        $this->add_control(
            'bg_img', [
                'label' => __('Section Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => __("", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'section_title', [
                'label' => __('Section Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Section Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_tagline', [
                'label' => __('Section Tagline', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Section Tagline', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_description', [
                'label' => __('Section Description', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Section Description', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'video_link', [
                'label' => __('Play Button Link', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Play Button Link', 'adforest-elementor'),
            ]
        );


        $this->end_controls_section();

        $this->start_controls_section(
            'search_section', [
                'label' => esc_html__('Search Section', 'adforest-elementor'),
            ]
        );


        $classified_repeater = new \Elementor\Repeater();
        $classified_repeater->add_control(
            'classified_field_type',
            [
                'label' => esc_html__('Field Type', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'title' => esc_html__('Title', 'adforest-elementor'),
                    'location' => esc_html__('Location', 'adforest-elementor'),
                    'category' => esc_html__('Category', 'adforest-elementor'),
                ],
                'default' => 'title',
            ]
        );

        $this->add_control(
            'classified_search_fields',
            [
                'label' => esc_html__('Classified Search Fields', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $classified_repeater->get_controls(),
                'default' => [
                    [
                        'classified_field_type' => 'title',
                    ],
                    [
                        'classified_field_type' => 'location',
                    ],
                ],
                'title_field' => '{{{ classified_field_type }}}',
            ]
        );

        $ad_categories = adforest_get_ad_taxonomy_callback('l_event_cat');

        $options = [];

        if (is_array($ad_categories) && count($ad_categories) > 0) {
            foreach ($ad_categories as $category) {
                $options[$category->slug] = $category->name;
            }
        }

        $this->add_control(
            'popular_categories',
            [
                'label' => esc_html__( 'Popular Categories', 'adforest-elementor' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => true,
                'options' => $options,
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
        $params['video_link'] = $atts['video_link'] ?? "";
        $params['section_tagline'] = $atts['section_tagline'] ?? "";
        $params['section_description'] = $atts['section_description'] ?? "";
        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['section_bg_image'] = $atts['section_bg_image'] ?? "";
        $params['classified_search_fields'] = $atts['classified_search_fields'] ?? "";
        $params['popular_categories'] = $atts['popular_categories'] ?? "";

        if (function_exists('adforest_event_home_hero')) {
            echo adforest_event_home_hero($params);
        }
    }
}