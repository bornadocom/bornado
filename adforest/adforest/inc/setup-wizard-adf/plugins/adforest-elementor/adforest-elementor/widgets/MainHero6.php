<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MainHero6 extends Widget_Base {
    public function get_name()
    {
        return 'adforest_main_hero_6';
    }

    public function get_title()
    {
        return __('Main Hero 6', 'adforest-elementor');
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

        $this->end_controls_section();

        $this->start_controls_section(
            'cat_section', [
                'label' => esc_html__('Category Section', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'cat_section_title', [
                'label' => __('Section Tagline', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Find By Categories', 'adforest-elementor'),
                'title' => __('Section Tagline', 'adforest-elementor'),
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
        $params['video_link'] = $atts['video_link'] ?? "";
        $params['section_bg_image'] = $atts['section_bg_image'] ?? "";
        $params['classified_search_fields'] = $atts['classified_search_fields'] ?? "";
        $params['category_main_repeater'] = $atts['category_main_repeater'] ?? [];
        $params['cat_section_title'] = $atts['cat_section_title'] ?? "";

        if (function_exists('adforest_main_hero_6')) {
            echo adforest_main_hero_6($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.default-select').select2();
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