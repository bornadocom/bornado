<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MultivendorProductsWithSidebar extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_multivendor_product_w_sidebar';
    }

    public function get_title()
    {
        return __("Multivendor Product With Sidebar", 'adforest_elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    private function get_product_categories_options() {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        $options = [];

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->term_id] = $term->name;
            }
        }

        return $options;
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
            'default' => "Our Latest Products",
        ]);

        $this->add_control(
            'button_text', [
            'label' => esc_html__('Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "View All",
        ]);

        $this->add_control(
            'btn_link', [
            'label' => esc_html__('Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "#",
        ]);

        $this->add_control(
            'product_title_limit_side', [
            'label' => esc_html__('Product Title Limit Sidebar', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 40,
        ]);

        $this->add_control(
            'ad_limit_side', [
            'label' => esc_html__('Product Limit Sidebar', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 8,
        ]);

        $this->add_control(
            'sidebar_position', [
            'label' => esc_html__('Sidebar Position', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => "left",
            'options' => [
                'left' => esc_html__( 'Left', 'adforest-elementor' ),
                'right'  => esc_html__( 'Right', 'adforest-elementor' ),
            ],
        ]);

        $this->add_control(
            'sidebar_title', [
            'label' => esc_html__('Sidebar Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Our Products",
        ]);

        // Get all WooCommerce product categories
        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ));

        $category_options = [];
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                $category_options[ $category->term_id ] = $category->name;
            }
        }

        // Add a select control with the product categories
        $this->add_control(
            'product_category', [
            'label' => esc_html__('Select Product Category', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $category_options,
            'default' => '',
            'description' => esc_html__('Choose a product category', 'adforest-elementor'),
        ]);

        $this->add_control(
            'ad_position', [
            'label' => esc_html__('Advert Position', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => "btm",
            'options' => [
                'top' => esc_html__( 'Top', 'adforest-elementor' ),
                'btm'  => esc_html__( 'Bottom', 'adforest-elementor' ),
            ],
        ]);

        $this->add_control(
            'advert_img', [
                'label' => __('Vertical Advert', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                "description" => __("303x485", 'adforest-elementor'),
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'main_products', [
            'label' => esc_html__('Products Grid', 'adforest-elementor'),
        ]);

        $this->add_control(
            'ad_limit', [
            'label' => esc_html__('Product Limit', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 8,
        ]);

        $this->add_control(
            'product_title_limit', [
            'label' => esc_html__('Product Title Limit', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 40,
        ]);

        $this->add_control(
            'product_categories_main', [
            'label' => esc_html__('Grid Product Categories', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $this->get_product_categories_options(),
            'label_block' => true,
            'description' => esc_html__('Select one or more product categories to show products from.', 'adforest-elementor'),
        ]);

        $this->add_control(
            'grid_style', [
            'label' => esc_html__('Grid Style', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'modern' => __('Modern', "adforest-elementor"),
                'fancy' => __("Fancy", "adforest-elementor"),
            ],
        ]);

        $this->add_control(
            'grid_cols', [
            'label' => esc_html__('Grid Columns', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '2' => __('Two', "adforest-elementor"),
                '3' => __("Three", "adforest-elementor"),
                '4' => __("Four", "adforest-elementor"),
                '5' => __("Five", "adforest-elementor"),
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['sidebar_position'] = $atts['sidebar_position'] ?? "";
        $params['sidebar_title'] = $atts['sidebar_title'] ?? "";
        $params['product_category'] = $atts['product_category'] ?? "";
        $params['advert_img'] = $atts['advert_img'] ?? "";
        $params['ad_position'] = $atts['ad_position'] ?? "";
        $params['btn_link'] = $atts['btn_link'] ?? "";
        $params['button_text'] = $atts['button_text'] ?? "";
        $params['ad_limit'] = $atts['ad_limit'] ?? "";
        $params['product_title_limit_side'] = $atts['product_title_limit_side'] ?? "";
        $params['product_title_limit'] = $atts['product_title_limit'] ?? "";
        $params['product_categories_main'] = $atts['product_categories_main'] ?? "";
        $params['grid_style'] = $atts['grid_style'] ?? "";
        $params['grid_cols'] = $atts['grid_cols'] ?? "";
        $params['ad_limit_side'] = $atts['ad_limit_side'] ?? "";

        if (function_exists('adforest_multivendor_product_w_sidebar')) {
            echo adforest_multivendor_product_w_sidebar($params);
        }
    }
}