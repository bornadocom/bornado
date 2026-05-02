<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MultivendorServices extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_multivendor_services';
    }

    public function get_title()
    {
        return __("Multivendor Services", 'adforest_elementor');
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

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'service_title', [
            'label' => esc_html__('Service Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => esc_html__('Service Title', 'adforest-elementor'),
            'label_block' => true,
        ]);

        $repeater->add_control(
            'service_description', [
            'label' => esc_html__('Service Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => esc_html__('Service Description', 'adforest-elementor'),
            'label_block' => true,
        ]);

        $repeater->add_control(
            'service_icon', [
            'label' => esc_html__('Icon Class (e.g., fas fa-shipping-fast)', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'fas fa-shipping-fast',
            'label_block' => true,
        ]);

        $this->add_control(
            'services_list', [
            'label' => esc_html__('Services Boxes', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ service_title }}}',
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['services_list'] = $atts['services_list'] ?? "";
        $params['adforest_elementor'] = true;

        if (function_exists('adforest_multivendor_services_shortcode')) {
            echo adforest_multivendor_services_shortcode($params);
        }
    }
}