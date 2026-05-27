<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class RealEstateHero extends Widget_Base {
    public function get_name()
    {
        return 'adforest_real_estate_hero';
    }

    public function get_title()
    {
        return __('Real Estate Hero', 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    public function get_script_depends() {
        return [];
    }

    public function get_style_depends() {
        return ['adforest-real-estate-hero-bg'];
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
                'label' => __('Side Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => __("This image will appear on the right side of the hero section", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'section_bg_img', [
                'label' => __('Section Background Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => __("This image will be used as the background for the entire hero section", 'adforest-elementor'),
                'default' => [
                    'url' => '',
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
			    'label' => esc_html__('Popular Categories', 'adforest-elementor'),
			    'type' => \Elementor\Controls_Manager::REPEATER,
			    'fields' => $repeater->get_controls(),
			    'default' => [],
			    'title_field' => '{{{ mini_category }}}',
		    ]
	    );

        $this->end_controls_section();
	    $this->start_controls_section(
		    'cats_section', [
			    'label' => esc_html__('Category Section', 'adforest-elementor'),
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
		    'carousel_category',
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
		    'carousel_category_image',
		    [
			    'label' => esc_html__('Upload Image', 'adforest-elementor'),
			    'type' => \Elementor\Controls_Manager::MEDIA,
			    'default' => [
				    'url' => \Elementor\Utils::get_placeholder_image_src(),
			    ],
		    ]
	    );

	    $this->add_control(
		    'carousel_categories_repeater',
		    [
			    'label' => esc_html__('Carousel Categories', 'adforest-elementor'),
			    'type' => \Elementor\Controls_Manager::REPEATER,
			    'fields' => $repeater->get_controls(),
			    'default' => [],
			    'title_field' => '{{{ carousel_category }}}',
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
        $params['section_bg_img'] = $atts['section_bg_img'] ?? "";
        $params['classified_search_ad_types'] = $atts['classified_search_ad_types'] ?? "";
        $params['classified_search_fields'] = $atts['classified_search_fields'] ?? "";
        $params['mini_categories_repeater'] = $atts['mini_categories_repeater'] ?? "";
        $params['carousel_categories_repeater'] = $atts['carousel_categories_repeater'] ?? "";

        if (function_exists('adforest_real_estate_hero_shortcode')) {
            echo adforest_real_estate_hero_shortcode($params);
        }

	    if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
		    ?>
		    <script>
                jQuery(document).ready(function ($) {
                    $('.adt-find-by-categories-carousel').owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 36,
                        nav: false,
                        dots: false,
                        autoplay: true,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: false,
                        responsive: {
                            0: {
                                items: 1,
                                margin: 20
                            },
                            420: {
                                items: 2,
                                margin: 20
                            },
                            576: {
                                items: 3,
                                margin: 20
                            },
                            768: {
                                items: 4,
                                margin: 20
                            },
                            992: {
                                items: 5
                            },
                            1200: {
                                items: 6
                            }
                        }
                    });
                });
		    </script>
	    <?php }
    }
}