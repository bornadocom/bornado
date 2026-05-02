<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdLocations extends Widget_Base
{
    public function get_name()
    {
        return 'ad_locations_shortcode';
    }

    public function get_title()
    {
        return __("Ad Locations", "adforest-elementor");
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
            'section_bg_color',
            array(
                'label' => __('Background Color', 'adforest-elementor'),
                'type' => Controls_Manager::COLOR,
                'default' => "#fffaef"
            )
        );

        $this->add_control(
            'section_title', [
            'label' => esc_html__('Section Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Popular Location',
        ]);
        $this->add_control(
            'section_left_btn_text', [
            'label' => esc_html__('Section Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'View All Locations',
        ]);

        $this->add_control(
            'section_btn_link', [
            'label' => esc_html__('Section Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
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
            'location',
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
            'locations_repeater',
            [
                'label' => esc_html__('Locations Repeater', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ (function() {
                    const categoryId = location;
                    const categories = ' . json_encode($options) . ';
                    return categories[categoryId] || "Location #" + categoryId;
                })() }}}',
            ]
        );

        $this->add_control(
            'show_ad', [
            'label' => esc_html__('Do you want to show an Ad?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__('Yes', 'adforest-elementor'),
                'no' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'yes',
        ]);
        $this->add_control(
            'advert_img', [
            'label' => __('Advert Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            "description" => __("302×464", 'adforest-elementor'),
            'condition' => [
                'show_ad' => 'yes',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_bg_color'] = $atts['section_bg_color'] ?? "";
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['section_left_btn_text'] = $atts['section_left_btn_text'] ?? "";
        $params['advert_img'] = $atts['advert_img'] ?? "";
        $params['show_ad'] = $atts['show_ad'] ?? "";
        $params['locations_repeater'] = $atts['locations_repeater'] ?? "";
        $params['section_btn_link'] = $atts['section_btn_link'] ?? "";

        if (function_exists('ad_locations_shortcode')) {
            echo ad_locations_shortcode($params);
        }
    }
}