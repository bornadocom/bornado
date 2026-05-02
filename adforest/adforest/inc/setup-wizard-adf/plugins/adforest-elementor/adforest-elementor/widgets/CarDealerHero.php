<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class CarDealerHero extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_car_dealer_hero';
    }

    public function get_title()
    {
        return __('Car Dealer Hero', 'adforest-elementor');
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

        $this->end_controls_section();

        $this->start_controls_section(
            'search_section', [
                'label' => esc_html__('Search Section', 'adforest-elementor'),
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

        $this->add_control(
            'search_title_field_switch',
            [
                'label' => esc_html__('Enable Title Search?', 'elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => true,
            ]
        );

        $this->add_control(
            'search_title_field_label',
            [
                'label' => esc_html__('Title Field Label', 'elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __("Explore", "adforest-elementor"),
                'condition' => [
                    'search_title_field_switch' => 'yes',
                ],
                'label_block' => true,
            ]
        );

        // --- New Control: Select Taxonomy from ad_post ---
        $taxonomies = get_object_taxonomies('ad_post', 'objects');
        $tax_options = [];

        if (!empty($taxonomies)) {
            foreach ($taxonomies as $tax_slug => $tax_obj) {
                $tax_options[$tax_slug] = $tax_obj->labels->name;
            }
        }

        $this->add_control(
            'ad_post_taxonomy',
            [
                'label' => __('Select Taxonomy', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => $tax_options,
                'default' => key($tax_options),
                'description' => __("Select a taxonomy to show on the search form.", 'adforest-elementor'),
            ]
        );
        // --- End New Control ---

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
        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['classified_search_ad_types'] = $atts['classified_search_ad_types'] ?? "";
        $params['search_title_field_label'] = $atts['search_title_field_label'] ?? "";
        $params['ad_post_taxonomy'] = $atts['ad_post_taxonomy'] ?? "";
        $params['search_title_field_switch'] = $atts['search_title_field_switch'] ?? "";

        if (function_exists('adforest_car_dealer_hero_shortcode')) {
            echo adforest_car_dealer_hero_shortcode($params);
        }
    }
}