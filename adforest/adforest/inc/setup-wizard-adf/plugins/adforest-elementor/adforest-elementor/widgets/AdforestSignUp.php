<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdforestSignUp extends Widget_Base
{

    public function get_name()
    {
        return 'register_user_elementor';
    }

    public function get_title()
    {
        return __('Sign Up', 'adforest-elementor');
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
            'general_sett',
            [
                'label' => esc_html__('General', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_title',
            [
                'label' => __('Section Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('For color {color}warp text within this tag{/color}', 'adforest-elementor'),
                'title' => __('Section Title', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'description',
            [
                'label' => __('Short Description', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );
        $this->add_control(
            'terms_title',
            [
                'label' => __('Terms & Condition Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Terms & Condition Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'terms_link',
            [
                'label' => __('Terms & Conditions', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-link.com', 'adforest-elementor'),
                'show_external' => true,
                'default' => [
                    'url' => '',
                    'is_external' => true,
                    'nofollow' => true,
                ],
            ]
        );

        $this->add_control(
            'is_captcha',
            array(
                'label' => __('Capcha Code', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                "description" => __("Captcha is for stop spamming", "adforest-elementor"),
                'options' => array(
                    '' => __('Please select', 'adforest-elementor'),
                    'with' => __('With Capcha', 'adforest-elementor'),
                    'without' => __('Without Capcha', 'adforest-elementor'),
                ),
            )
        );

        $this->end_controls_section();


        $this->start_controls_section(
            'general_sett2',
            [
                'label' => esc_html__('Other section settings', 'adforest-elementor'),
            ]
        );


        $this->add_control(
            'section_title_2',
            [
                'label' => __('Section Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('For color {color}warp text within this tag{/color}', 'adforest-elementor'),
                'title' => __('Section Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'description_2',
            [
                'label' => __('Short Description', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );

        $this->add_control(
            'bg_img',
            array(
                'label' => __('Section Image', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],

            )
        );
        $this->add_control(
            'button_title',
            [
                'label' => __('Button  Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Button Title', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'button_link',
            [
                'label' => __('Button link', 'adforest-elementor'),
                'type' => Controls_Manager::URL,

                'title' => __('Button Link', 'adforest-elementor'),
            ]
        );
        $this->end_controls_section();
    }
    protected function render() {
        $atts = $this->get_settings_for_display();

        $params = array(
            'section_title' => $atts['section_title'],
            'description' => $atts['description'],
            'section_title_2' => $atts['section_title_2'],
            'description_2' => $atts['description_2'],
            'terms_link' => $atts['terms_link']['url'],
            'terms_title' => $atts['terms_title'],
            'is_captcha' => $atts['is_captcha'],
            'bg_img' => $atts['bg_img']['url'],
            'main_link' => '', // Adjust as needed
            'button_link' => $atts['button_link']['url'],
            'button_title' => $atts['button_title'],
        );

        echo register_user_elementor($params);
    }

}