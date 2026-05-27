<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class LocationsModern extends Widget_Base
{
    public function get_name()
    {
        return 'locations_modern_shortcode';
    }

    public function get_title()
    {
        return __("Locations Modern", 'adforest-elementor');
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
            'section_style', [
            'label' => esc_html__('Style', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '1' => '1',
                '2' => '2'
            ],
            'default' => '1',
        ]);

        $this->add_control(
            'location_bg',
            [
                'label' => esc_html__('Background Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'main_sec_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Latest Ads',
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
            'default' => '#',
        ]);

        $this->add_control(
            'button_style', [
            'label' => esc_html__('Button Style', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'default' => __("Default","adforest-elementor"),
                'dark' => __("Dark","adforest-elementor"),
            ],
            'default' => 'default',
        ]);

        $this->add_control(
            'top_text', [
            'label' => __('Top Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'label_block' => true,
            'condition' => [
                'section_style' => '2',
            ],
        ]);

        $ad_countries = adforest_get_ad_taxonomy_callback('ad_country');

        $options = [];

        if (is_array($ad_countries) && count($ad_countries) > 0) {
            foreach ($ad_countries as $category) {
                $options[$category->slug] = $category->name;
                $options += get_all_child_terms_slug($category->term_id, 'ad_country');
            }
        }

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'locations',
            [
                'label' => esc_html__('Select Location', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'options' => $options,
                'default' => '',
            ]
        );

        $repeater->add_control(
            'location_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'location_repeater',
            [
                'label' => esc_html__('Select Locations', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ (function() {
                    const categoryId = locations;
                    const categories = ' . json_encode($options) . ';
                    return categories[categoryId] || "Location #" + categoryId;
                })() }}}',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['section_style'] = isset($atts['section_style']) ? $atts['section_style'] : "";
        $params['main_sec_title'] = isset($atts['main_sec_title']) ? $atts['main_sec_title'] : "";
        $params['main_sec_btn_text'] = isset($atts['main_sec_btn_text']) ? $atts['main_sec_btn_text'] : "";
        $params['main_sec_btn_link'] = isset($atts['main_sec_btn_link']) ? $atts['main_sec_btn_link'] : "";
        $params['location_repeater'] = isset($atts['location_repeater']) ? $atts['location_repeater'] : "";
        $params['location_bg'] = isset($atts['location_bg']) ? $atts['location_bg'] : "";
        $params['button_style'] = isset($atts['button_style']) ? $atts['button_style'] : "";
        $params['top_text'] = isset($atts['top_text']) ? $atts['top_text'] : "";
        $params['adforest_elementor'] = true;

        if (function_exists('locations_modern_shortcode')) {
            echo locations_modern_shortcode($params);
        }
    }
}