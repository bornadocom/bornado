<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class BrandsCarousel extends Widget_Base
{

    public function get_name()
    {
        return 'brands_carousel_short_base';
    }

    public function get_title()
    {
        return __('Brands Carousel', 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return [ 'adforest_widgets' ];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'client_settings',
            [
                'label' => esc_html__('Brands', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'title', [
                'label' => __("Title", "adforest-elementor"),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $ad_categories = adforest_get_ad_taxonomy_callback( 'ad_cats' );

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
                'label'       => esc_html__( 'Select Category', 'adforest-elementor' ),
                'type'        => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => false,
                'options'     => $options,
                'default'     => '',
            ]
        );

        $repeater->add_control(
            'carousel_category_name',
            [
                'type'    => \Elementor\Controls_Manager::HIDDEN,
                'default' => '',
            ]
        );

        $repeater->add_control(
            'carousel_category_image',
            [
                'label'   => esc_html__( 'Upload Image', 'adforest-elementor' ),
                'type'    => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'carousel_categories_repeater',
            [
                'label'       => esc_html__( 'Carousel Categories', 'adforest-elementor' ),
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [],
                'title_field' => '{{{ carousel_category_name }}}',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['title'] = isset($atts['title']) ? $atts['title'] : "";
        $params['carousel_categories_repeater'] = isset($atts['carousel_categories_repeater']) ? $atts['carousel_categories_repeater'] : "";

        if (function_exists('brands_carousel_short_base')) {
            echo brands_carousel_short_base($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) :
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-car-category-carousel').owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 36,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        responsive: {
                            0: {
                                items: 2,
                                margin: 20
                            },
                            576: {
                                items: 3
                            },
                            768: {
                                items: 4
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
        <?php
        endif;
    }
}