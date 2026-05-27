<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MultivendorProductCategories extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_multivendor_product_categories';
    }

    public function get_title()
    {
        return __("Multivendor Product Categories", 'adforest_elementor');
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
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Our Best Categories",
        ]);

        $this->add_control(
            'btn_title', [
            'label' => esc_html__('Button Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __("View All", "adforest-elementor"),
        ]);

        $this->add_control(
            'btn_link', [
            'label' => esc_html__('Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "#",
        ]);

        $this->add_control(
            'category_source',
            [
                'label' => esc_html__('Category Source', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'product' => esc_html__('WooCommerce', 'adforest-elementor'),
                    'classified' => esc_html__('Classified', 'adforest-elementor'),
                ],
            ]
        );

        $product_terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        $product_options = [];
        if (is_array($product_terms)) {
            foreach ($product_terms as $term) {
                $product_options[$term->term_id] = $term->name;
            }
        }

        $ad_cats = adforest_get_ad_taxonomy_callback('ad_cats');

        $classified_options = [];
        if (is_array($ad_cats) && count($ad_cats) > 0) {
            foreach ($ad_cats as $category) {
                $classified_options[$category->slug] = $category->name;
                $classified_options += get_all_child_terms_slug($category->term_id, 'ad_cats');
            }
        }

        $wc_repeater = new \Elementor\Repeater();
        $wc_repeater->add_control(
            'main_category',
            [
                'label' => esc_html__('Select Product Category', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT2,
                'options' => $product_options,
                'label_block' => true,
                'multiple' => false,
            ]
        );
        $wc_repeater->add_control(
            'category_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );
        $this->add_control(
            'product_categories_repeater',
            [
                'label' => esc_html__('Product Categories', 'adforest-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $wc_repeater->get_controls(),
                'condition' => [
                    'category_source' => 'product',
                ],
                'title_field' => '{{{ main_category }}}',
            ]
        );

        $cls_repeater = new \Elementor\Repeater();
        $cls_repeater->add_control(
            'main_category',
            [
                'label' => esc_html__('Select Classified Category', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT2,
                'options' => $classified_options,
                'label_block' => true,
                'multiple' => false,
            ]
        );
        $cls_repeater->add_control(
            'category_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );
        $this->add_control(
            'classified_categories_repeater',
            [
                'label' => esc_html__('Classified Categories', 'adforest-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $cls_repeater->get_controls(),
                'condition' => [
                    'category_source' => 'classified',
                ],
                'title_field' => '{{{ main_category }}}',
            ]
        );
        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();

        if ( $atts['category_source'] === 'product' ) {
            $items = $atts['product_categories_repeater'];
        } else {
            $items = $atts['classified_categories_repeater'];
        }

        $params = [
            'adforest_elementor'             => true,
            'section_title'                  => $atts['section_title']                  ?? '',
            'btn_title'                      => $atts['btn_title']                      ?? '',
            'btn_link'                       => $atts['btn_link']                       ?? '',
            'category_source'                => $atts['category_source']                ?? 'product',
            'product_categories_repeater'    => $atts['product_categories_repeater']    ?? [],
            'classified_categories_repeater' => $atts['classified_categories_repeater'] ?? [],
        ];

        if ( function_exists( 'adforest_multivendor_product_categories' ) ) {
            echo adforest_multivendor_product_categories( $params );
        }

        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-multivendor-mini-ctg-carousel').owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 20,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: false,
                        responsive: {
                            0: {
                                items: 2
                            }, 450: {
                                items: 3
                            }, 576: {
                                items: 3
                            }, 768: {
                                items: 5
                            }, 992: {
                                items: 6
                            }, 1200: {
                                items: 8
                            }, 1400: {
                                items: 9
                            }
                        }
                    });
                });
            </script>
        <?php }
    }
}