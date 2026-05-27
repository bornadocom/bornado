<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ElementorAdforest\Traits\SliderControlsTrait;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class CategoryBasedAds extends Widget_Base
{
    use SliderControlsTrait;
    public function get_name()
    {
        return 'adforest_category_based_ads';
    }

    public function get_title()
    {
        return __("Category Based Ads", 'adforest-elementor');
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

        $ad_categories = adforest_get_ad_taxonomy_callback( 'ad_cats' );

        $options = [];

        if (is_array($ad_categories) && count($ad_categories) > 0) {
            foreach ($ad_categories as $category) {
                $options[$category->term_id] = $category->name;

                $options += get_all_child_terms($category->term_id, 'ad_cats');
            }
        }

        $this->add_control(
            'ad_cats_select',
            [
                'label' => esc_html__('Select Ad Category', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $options,
                'multiple' => true,
                'default' => array_key_first($options),
                'label_block' => true,
            ]
        );

        $this->add_control('posts_per_page', [
            'label' => esc_html__('Number of Ads to show?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 10,
            'step' => 1,
            'default' => 5,
            'label_block' => true,
        ]);

        $this->add_control('section_title', [
            'label' => esc_html__("Section Title", "adforest-elementor"),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __("Ads", "adforest-elementor"),
            'label_block' => true,
        ]);

        $this->add_control('button_title', [
            'label' => esc_html__("Button Title", "adforest-elementor"),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __("View All", "adforest-elementor"),
            'label_block' => true,
        ]);

        $this->add_control('button_link', [
            'label' => esc_html__("Button Link", "adforest-elementor"),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __("#", "adforest-elementor"),
            'label_block' => true,
        ]);

        $this->add_control('ad_title_limit', [
            'label' => esc_html__("Ad Title Limit (In Characters)", "adforest-elementor"),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 15,
            'label_block' => true,
        ]);

        $this->add_control(
            'ad_grid_style', [
            'label' => esc_html__('Select Grid Style', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => esc_html__('Style 1', 'adforest-elementor'),
                '2' => esc_html__('Style 2', 'adforest-elementor'),
                '3' => esc_html__('Style 3', 'adforest-elementor'),
            ],
            'default' => '1',
            'label_block' => true,
        ]);

        $this->add_control(
            'carousel_ad_type', [
            'label' => esc_html__('Select Ad Type', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'recent' => esc_html__('Regular', 'adforest-elementor'),
                'featured' => esc_html__('Featured', 'adforest-elementor'),
                'both' => esc_html__('Both', 'adforest-elementor'),
            ],
            'default' => 'featured',
            'label_block' => true,
        ]);

        $this->add_control(
            'carousel_grid_columns', [
            'label' => esc_html__('Select No of Grid Columns', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => esc_html__('1', 'adforest-elementor'),
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
                '4' => esc_html__('4', 'adforest-elementor'),
                '5' => esc_html__('5', 'adforest-elementor'),
            ],
            'default' => '4',
            'label_block' => true,
        ]);

        $this->end_controls_section();
        $this->register_slider_controls();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['ad_cats_select'] = $atts['ad_cats_select'] ?? "";
        $params['posts_per_page'] = $atts['posts_per_page'] ?? "";
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['button_title'] = $atts['button_title'] ?? "";
        $params['button_link'] = $atts['button_link'] ?? "";
        $params['ad_title_limit'] = $atts['ad_title_limit'] ?? "";
        $params['ad_grid_style'] = $atts['ad_grid_style'] ?? "";
        $params['carousel_ad_type'] = $atts['carousel_ad_type'] ?? "";
        $params['carousel_grid_columns'] = $atts['carousel_grid_columns'] ?? "";
        $params['loop_slider'] = $atts['loop_slider'] ?? 'yes';
        $params['autoplay_slider'] = $atts['autoplay_slider'] ?? 'no';
        $params['pause_on_hover_slider'] = $atts['pause_on_hover_slider'] ?? 'no';
        $params['slider_speed'] = $atts['slider_speed'] ?? 3000;

        if (function_exists('adforest_category_based_ads')) {
            echo adforest_category_based_ads($params);
        }

        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-ads-carousel-widgets').each(function () {
                        var $carousel = $(this);
                        var columns = parseInt($carousel.data('columns')) || 4;

                        $carousel.owlCarousel({
                            loop: false,
                            rtl: is_rtl,
                            margin: 30,
                            nav: true,
                            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                            dots: false,
                            autoplayTimeout: 5000,
                            autoplayHoverPause: false,
                            responsive: {
                                0: {
                                    items: 1,
                                    margin: 30,
                                },
                                420: {
                                    items: Math.min(2, columns),
                                    margin: 10,
                                },
                                576: {
                                    items: Math.min(2, columns),
                                    margin: 10,
                                },
                                992: {
                                    items: Math.min(3, columns)
                                },
                                1200: {
                                    items: columns
                                }
                            }
                        });
                    });
                });

                $('.adt-property-ads-carousel-widgets').each(function () {
                    var $carousel = $(this);
                    var columns = parseInt($carousel.data('columns')) || 4;
                    console.log($carousel.data('columns'));

                    $carousel.owlCarousel({
                        loop: false,
                        rtl: is_rtl,
                        margin: 36,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        responsive: {
                            0: {
                                items: 1
                            },
                            576: {
                                items: Math.min(2, columns),
                                margin: 20,
                            },
                            768: {
                                items: Math.min(3, columns),
                                margin: 20,
                            },
                            992: {
                                items: Math.min(3, columns)
                            },
                            1200: {
                                items: columns
                            }
                        }
                    });
                });

                // Property image Carousel
                $('.adt-property-img-carousel').owlCarousel({
                    loop: false,
                    rtl: is_rtl,
                    margin: 0,
                    nav: true,
                    dots: false,
                    navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                    responsive: {
                        0: {
                            items: 1
                        },
                        600: {
                            items: 1
                        },
                        1000: {
                            items: 1
                        }
                    }
                });
            </script>
        <?php }
    }
}