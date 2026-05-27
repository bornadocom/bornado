<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class DirectoryHero extends Widget_Base {
    public function get_name()
    {
        return 'adforest_directory_hero';
    }

    public function get_title()
    {
        return __('Directory Hero', 'adforest-elementor');
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
                'label_block' => true
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
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'title' => __('Section Description', 'adforest-elementor'),
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

        $this->end_controls_section();

        $this->start_controls_section(
            'category_section', [
                'label' => esc_html__('Category Carousel Section', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'carousel_cat_title', [
                'label' => __('Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Find by Categories', 'adforest-elementor'),
                'title' => __('Title', 'adforest-elementor'),
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

        $cat_repeater = new \Elementor\Repeater();

        $cat_repeater->add_control(
            'category',
            [
                'label' => esc_html__('Select Category', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'options' => $options,
                'default' => '',
            ]
        );

        $cat_repeater->add_control(
            'image',
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
                'label' => esc_html__('Popular Categories', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $cat_repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ category }}}',
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
        $params['section_bg_image'] = $atts['section_bg_image'] ?? "";
        $params['classified_search_fields'] = $atts['classified_search_fields'] ?? "";
        $params['mini_categories_repeater'] = $atts['mini_categories_repeater'] ?? "";
        $params['carousel_categories_repeater'] = $atts['carousel_categories_repeater'] ?? "";
        $params['carousel_cat_title'] = $atts['carousel_cat_title'] ?? "";

        if (function_exists('adforest_directory_hero')) {
            echo adforest_directory_hero($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-find-by-categories-carousel').owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 36,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        autoplay: true,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: false,
                        responsive: {
                            0: {
                                items: 2,
                                margin: 10
                            },
                            420: {
                                items: 2,
                                margin: 10
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