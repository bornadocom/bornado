<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MainHero3 extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_main_hero3';
    }

    public function get_title()
    {
        return __('Main Hero 3', 'adforest-elementor');
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
            'bg_img', [
                'label' => __('Background Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => __("", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'bg_color',
            [
                'label' => esc_html__( 'Background Color', 'textdomain' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#fffaef'
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
            'secondary_text_1', [
                'label' => __('Secondary Text Top', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Secondary Text Top', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'secondary_text_2', [
                'label' => __('Secondary Text Center', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Secondary Text Center', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'secondary_text_3', [
                'label' => __('Secondary Text Bottom', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Secondary Text Bottom', 'adforest-elementor'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'search_section', [
                'label' => esc_html__('Search Section', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'search_section_title', [
                'label' => __('Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Title', 'adforest-elementor'),
            ]
        );

        $ad_type_repeater = new \Elementor\Repeater();
        $ad_types = adforest_get_ad_taxonomy_callback('ad_type');
        $ad_type_options = [];

        if (!empty($ad_types) && is_array($ad_types)) {
            foreach ($ad_types as $ad_type) {
                $ad_type_options[$ad_type->term_id] = $ad_type->name;
            }
        }

        $ad_type_repeater->add_control(
            'classified_ad_type',
            [
                'label' => esc_html__('Field Type', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $ad_type_options,
                'default' => key($ad_type_options),
            ]
        );

        // Add the repeater to the section
        $this->add_control(
            'classified_search_ad_types',
            [
                'label' => esc_html__('Classified Ad Types', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $ad_type_repeater->get_controls(),
                'title_field' => '{{{ classified_ad_type }}}',
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

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['section_tagline'] = $atts['section_tagline'] ?? "";
        $params['secondary_text_1'] = $atts['secondary_text_1'] ?? "";
        $params['secondary_text_2'] = $atts['secondary_text_2'] ?? "";
        $params['secondary_text_3'] = $atts['secondary_text_3'] ?? "";
        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['bg_color'] = $atts['bg_color'] ?? "";
        $params['search_section_title'] = $atts['search_section_title'] ?? "";
        $params['classified_search_fields'] = $atts['classified_search_fields'] ?? "";
        $params['classified_search_ad_types'] = $atts['classified_search_ad_types'] ?? "";
        $params['adforest_elementor'] = true;

        if (function_exists('main_hero_3_shortcode')) {
            echo main_hero_3_shortcode($params);
        }
    }
}