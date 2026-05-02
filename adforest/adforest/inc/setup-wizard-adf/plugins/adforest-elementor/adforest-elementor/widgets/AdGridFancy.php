<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdGridFancy extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_ad_grid_fancy';
    }

    public function get_title()
    {
        return __("Ad Grid Fancy", 'adforest-elementor');
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
                'default' => "",
                'placeholder' => __("Enter Section Title", "adforest-elementor"),
            ]
        );

        $this->add_control(
            'desc', [
                'label' => esc_html__('Section Description', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => "",
                'placeholder' => __("Enter Section Description", "adforest-elementor"),
            ]
        );

        $this->add_control(
            'ads_title_limit', [
                'label' => esc_html__('Ad Title Limit(In Characters)', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 25,
            ]
        );

        $this->add_control(
            'ads_limit', [
                'label' => esc_html__('Limit Number of Ads', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => "",
                'placeholder' => 8,
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
                'options' => ['simple' => 'Simple', 'featured' => 'Featured'],
                'default' => 'featured',
            ]
        );

        $this->add_control(
            'grid_columns', [
            'label' => esc_html__('Select No of Grid Columns', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
                '4' => esc_html__('4', 'adforest-elementor'),
                '5' => esc_html__('5', 'adforest-elementor'),
            ],
            'default' => '4',
            'label_block' => true,
        ]);
        $this->add_control(
            'grid_style', [
            'label' => esc_html__('Select Grid Style', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => esc_html__('1', 'adforest-elementor'),
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
                '4' => esc_html__('4', 'adforest-elementor'),
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
        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['ads_limit'] = $atts['ads_limit'] ?? "";
        $params['ads_title_limit'] = $atts['ads_title_limit'] ?? "";
        $params['grid_columns'] = $atts['grid_columns'] ?? "";
        $params['grid_style'] = $atts['grid_style'] ?? "";
        $params['adforest_elementor'] = true;

        if (function_exists('adforest_ad_grid_fancy_shortcode')) {
            echo adforest_ad_grid_fancy_shortcode($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) :
            ?>
            <script>
                jQuery(document).ready(function ($) {
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