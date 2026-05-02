<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdGridCarouselFancy extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_ad_grid_carousel_fancy';
    }

    public function get_title()
    {
        return __("Ad Grid Carousel Fancy", 'adforest-elementor');
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
            'bg_img',
            [
                'label' => esc_html__('Background Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
            ]
        );

        $this->add_control(
            'title', [
                'label' => esc_html__('Section Title', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => "Properties To buy",
                'placeholder' => __("Enter Section Title", "adforest-elementor"),
            ]
        );

        $this->add_control(
            'desc', [
                'label' => esc_html__('Section Desc', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __("Enter Section Description", "adforest-elementor"),
            ]
        );

        $ad_categories = adforest_get_ad_taxonomy_callback( 'ad_cats' );

        $ad_cats_options = [];

        if (is_array($ad_categories) && count($ad_categories) > 0) {
            foreach ($ad_categories as $category) {
                $ad_cats_options[$category->term_id] = $category->name;

                $ad_cats_options += get_all_child_terms($category->term_id, 'ad_cats');
            }
        }

        $this->add_control(
            'ad_title_limit', [
                'label' => esc_html__('Ad Title Limit(In Characters)', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 25,
                'label_block' => true
            ]
        );

        $this->add_control(
            'ad_cats_select',
            [
                'label' => esc_html__('Select Ad Category', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $ad_cats_options,
                'multiple' => true,
                'default' => array_key_first($ad_cats_options),
                'label_block' => true
            ]
        );

        $this->add_control(
            'no_of_ads',
            [
                'label' => esc_html__('Number of Ads', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'max' => 50,
                'label_block' => true
            ]
        );

        $this->add_control(
            'btn_title', [
                'label' => esc_html__('Button Title', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => "View All",
                'placeholder' => __("Enter Button Title", "adforest-elementor"),
            ]
        );

        $this->add_control(
            'btn_link', [
                'label' => esc_html__('Button Link', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => "#",
                'placeholder' => __("Enter Button Link", "adforest-elementor"),
            ]
        );

        $ad_cats = get_terms(array(
            'taxonomy' => 'ad_cats',
            'hide_empty' => false,
        ));

        $ad_cats_options = array();
        if (!is_wp_error($ad_cats)) {
            foreach ($ad_cats as $ad_cat) {
                $ad_cats_options[$ad_cat->term_id] = $ad_cat->name;
            }
        }

        $this->add_control(
            'ad_select',
            [
                'label' => esc_html__('Select Ad Type', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => ['simple' => 'Simple', 'featured' => 'Featured', 'both' => 'Both'],
                'default' => 'featured',
            ]
        );

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
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['title'] = $atts['title'] ?? "";
        $params['desc'] = $atts['desc'] ?? "";
        $params['ad_select'] = $atts['ad_select'] ?? "";
        $params['btn_link'] = $atts['btn_link'] ?? "";
        $params['btn_title'] = $atts['btn_title'] ?? "";
        $params['carousel_grid_columns'] = $atts['carousel_grid_columns'] ?? "";
        $params['no_of_ads'] = $atts['no_of_ads'] ?? "";
        $params['ad_cats_select'] = $atts['ad_cats_select'] ?? "";
        $params['ad_title_limit'] = $atts['ad_title_limit'] ?? "";
        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['adforest_elementor'] = true;

        if (function_exists('adforest_ad_grid_carousel_fancy_shortcode')) {
            echo adforest_ad_grid_carousel_fancy_shortcode($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) :
            ?>
            <script>
                jQuery(document).ready(function ($) {
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
                });
            </script>
        <?php
        endif;
    }
}