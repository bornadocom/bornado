<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class OurArticles extends  Widget_Base
{
    public function get_name()
    {
        return 'our_articles_shortcode';
    }

    public function get_title()
    {
        return __("Our Articles", 'adforest-elementor');
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
            'main_sec_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Our Articles',
        ]);

        $this->add_control(
            'main_sec_description', [
            'label' => esc_html__('Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

	    $this->add_control(
		    'number_of_posts', [
		    'label' => esc_html__('No. of Articles to show.', 'adforest-elementor'),
		    'type' => \Elementor\Controls_Manager::NUMBER,
		    'default' => 5,
	    ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['main_sec_title'] = isset($atts['main_sec_title']) ? $atts['main_sec_title'] : "";
        $params['main_sec_description'] = isset($atts['main_sec_description']) ? $atts['main_sec_description'] : "";
        $params['number_of_posts'] = isset($atts['number_of_posts']) ? $atts['number_of_posts'] : "";
        $params['adforest_elementor'] = true;

        if (function_exists('our_articles_shortcode')) {
            echo our_articles_shortcode($params);
        }
    }
}