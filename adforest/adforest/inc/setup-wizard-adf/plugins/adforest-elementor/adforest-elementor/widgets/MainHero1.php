<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MainHero1 extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_main_hero1';
    }

    public function get_title()
    {
        return __('Main Hero 1', 'adforest-elementor');
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
                "description" => __("1280x800", 'adforest-elementor'),
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
        );$this->add_control(
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
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );

        $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');

        $options = [];

        if (is_array($ad_categories) && count($ad_categories) > 0) {
            foreach ($ad_categories as $category) {
                $options[$category->slug] = $category->name;
                $options += get_all_child_terms_slug($category->term_id, 'ad_cats');
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

        $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');

        $options = [];

        if (is_array($ad_categories) && count($ad_categories) > 0) {
            foreach ($ad_categories as $category) {
                $options[$category->slug] = $category->name;

                $options += get_all_child_terms_slug($category->term_id, 'ad_cats');
            }
        }

	    $repeater = new \Elementor\Repeater();

	    $repeater->add_control(
		    'mini_category',
		    [
			    'label' => esc_html__('Select Category', 'adforest-elementor'),
			    'type' => \Elementor\Controls_Manager::SELECT2,
			    'label_block' => true,
			    'multiple' => false,
			    'options' => $options,
			    'default' => '',
		    ]
	    );

	    $repeater->add_control(
		    'category_image',
		    [
			    'label' => esc_html__('Upload Image', 'adforest-elementor'),
			    'type' => \Elementor\Controls_Manager::MEDIA,
			    'default' => [
				    'url' => \Elementor\Utils::get_placeholder_image_src(),
			    ],
		    ]
	    );

	    $this->add_control(
		    'mini_categories_repeater',
		    [
			    'label' => esc_html__('Sidebar Categories', 'adforest-elementor'),
			    'type' => \Elementor\Controls_Manager::REPEATER,
			    'fields' => $repeater->get_controls(),
			    'default' => [],
			    'title_field' => '{{{ mini_category }}}',
		    ]
	    );

        $main_repeater = new \Elementor\Repeater();

        $main_repeater->add_control(
            'main_category',
            [
                'label' => esc_html__('Select Category', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'options' => $options,
                'default' => '',
            ]
        );

        $main_repeater->add_control(
            'category_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'category_main_repeater',
            [
                'label' => esc_html__( 'Main Section Categories', 'adforest-elementor' ),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $main_repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ main_category }}}',
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
        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['mini_categories_repeater'] = $atts['mini_categories_repeater'] ?? "";
        $params['category_main_repeater'] = $atts['category_main_repeater'] ?? "";
        $params['popular_categories'] = $atts['popular_categories'] ?? "";

        if (function_exists('main_hero_1_shortcode')) {
            echo main_hero_1_shortcode($params);
        }
    }
}