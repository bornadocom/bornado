<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

class AdforestPackages extends Widget_Base
{

    public function get_name()
    {
        return 'price_modern2_short_base';
    }

    public function get_title()
    {
        return __('Products - Modern 2', 'adforest-elementor');
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

        // ──────────────────────────────────────────────────────────
        // BASIC SECTION (unchanged)
        // ──────────────────────────────────────────────────────────
        $this->start_controls_section(
            'basic',
            [
                'label' => esc_html__('Basic', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_design',
            [
                'label' => __('Section Design', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'modern' => esc_html__('Modern', 'adforest-elementor'),
                    'fancy' => esc_html__('Fancy', 'adforest-elementor'),
                ],
                'default' => 'modern',
            ]
        );

        $this->add_control(
            'section_title',
            [
                'label' => __('Section Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'description' => __('For color {color}warp text within this tag{/color}', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_description',
            [
                'label' => __('Section Description', 'adforest-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => __('Type your description here', 'adforest-elementor'),
            ]
        );

        $this->end_controls_section();


        // ──────────────────────────────────────────────────────────
        // PRODUCTS SECTION with repeater
        // ──────────────────────────────────────────────────────────
        $this->start_controls_section(
            'products_section',
            [
                'label' => esc_html__('Products', 'adforest-elementor'),
            ]
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'product_id',
            [
                'label' => __('Select Product', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT2,
                'options' => apply_filters('adforest_elementor_get_packages', []),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'packages',
            [
                'label' => esc_html__('Product Items', 'adforest-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ (function() {
                    const productId = product_id;
                    const products = ' . json_encode(apply_filters('adforest_elementor_get_packages', [])) . ';
                    return products[productId] || "Product #" + productId;
                })() }}}',
            ]
        );

        $this->add_control(
            'cols_option',
            [
                'label' => __('Select Columns', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'default' => '4',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $params = [
            'section_design' => isset($settings['section_design']) ? $settings['section_design'] : "",
            'section_title' => isset($settings['section_title']) ? $settings['section_title'] : "",
            'section_description' => isset($settings['section_description']) ? $settings['section_description'] : "",
            'packages' => isset($settings['packages']) ? $settings['packages'] : "",
            'cols_option' => isset($settings['cols_option']) ? $settings['cols_option'] : "",
        ];

        echo price_modern3_short_base_func($params);
    }
}