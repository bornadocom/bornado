<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MultivendorProductCarouselWithAds extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_multivendor_product_carousel_w_ads';
    }

    public function get_title()
    {
        return __("Multivendor Product Carousel With Ads", 'adforest_elementor');
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
            'main', [
            'label' => esc_html__('Main', 'adforest-elementor'),
        ]);

        $this->add_control(
            'section_title', [
            'label' => esc_html__('Header Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Title",
        ]);

        $this->add_control(
            'section_header_image', [
            'label' => esc_html__('Header Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::MEDIA,
        ]);

        $this->add_control(
            'section_btn_title', [
            'label' => esc_html__('Header Button Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Shop Now",
        ]);

        $this->add_control(
            'section_btn_link', [
            'label' => esc_html__('Header Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Our Latest Products",
        ]);

        $this->add_control(
            'product_limit', [
            'label' => esc_html__('Limit Products', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 5,
        ]);

        $this->add_control(
            'product_title_limit', [
            'label' => esc_html__('Limit Products Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 5,
        ]);

        $this->add_control(
            'section_left_advert', [
            'label' => esc_html__('Left Advert', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
        ]);

        $this->add_control(
            'section_right_advert', [
            'label' => esc_html__('Right Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['section_header_image'] = $atts['section_header_image'] ?? "";
        $params['section_btn_title'] = $atts['section_btn_title'] ?? "";
        $params['section_btn_link'] = $atts['section_btn_link'] ?? "";
        $params['section_left_advert'] = $atts['section_left_advert'] ?? "";
        $params['section_right_advert'] = $atts['section_right_advert'] ?? "";
        $params['product_limit'] = $atts['product_limit'] ?? "";
        $params['product_title_limit'] = $atts['product_title_limit'] ?? "";

        if (function_exists('adforest_multivendor_product_carousel_w_ads_shortcode')) {
            echo adforest_multivendor_product_carousel_w_ads_shortcode($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.cyber-products-mini-carousel').owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 10,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        autoplay: true,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: true,
                        responsive: {
                            0: {
                                items: 1
                            },
                            400: {
                                items: 2
                            },
                            576: {
                                items: 2
                            },
                            768: {
                                items: 3
                            },
                            992: {
                                items: 2
                            },
                            1200: {
                                items: 2,
                                margin: 20
                            },
                            1400: {
                                items: 2.9
                            }
                        }
                    });
                });
            </script>
        <?php }
    }
}