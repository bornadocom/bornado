<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MultivendorCategorySliderMini extends Widget_Base
{
    public function get_name() {
        return 'adforest_multivendor_mini_category_slider';
    }

    public function get_title()
    {
        return __('Multivendor Mini Category Slider', 'adforest-elementor');
    }

    public function get_icon()
    {
        return "fa fa-audio-description";
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

        $product_categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        $options = [];

        if ( is_array( $product_categories ) && count( $product_categories ) > 0 ) {
            foreach ( $product_categories as $category ) {
                $options[ $category->term_id ] = $category->name;
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

        $this->add_control(
            'product_categories_repeater',
            [
                'label' => esc_html__( 'Product Categories', 'adforest-elementor' ),
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
        $params['product_categories_repeater'] = $atts['product_categories_repeater'] ?? [];
        if (function_exists('adforest_multivendor_mini_category_slider')) {
            echo adforest_multivendor_mini_category_slider($params);
        }
    }
}