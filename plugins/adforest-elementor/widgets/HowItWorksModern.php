<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class HowItWorksModern extends Widget_Base
{
    public function get_name()
    {
        return 'how_it_works_modern_shortcode';
    }
    public function get_title()
    {
        return __('How It Works Modern', 'adforest-elementor');
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
            'basic',
            [
                'label' => esc_html__('Basic', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'bg_img',
            array(
                'label' => __('Section Image', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => '',
                ],

            )
        );

        $this->add_control(
            'section_title',
            [
                'label' => __('Section Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Section Title', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'section_tagline',
            [
                'label' => __('Section Tag Line', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Section Title', 'adforest-elementor'),
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'workflow_section',
            [
                'label' => esc_html__('Workflow', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'icon_1',
            [
                'label' => esc_html__('Icon 1', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-circle',
                    'library' => 'fa-solid',
                ],
                'recommended' => [
                    'fa-solid' => [
                        'circle',
                        'dot-circle',
                        'square-full',
                    ],
                    'fa-regular' => [
                        'circle',
                        'dot-circle',
                        'square-full',
                    ],
                ],
            ]
        );
        $this->add_control(
            'workflow_title_1',
            [
                'label' => __('Workflow Title 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Workflow Title 1', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'workflow_tagline_1',
            [
                'label' => __('Workflow Tag Line 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Workflow Tag Line 1', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'icon_2',
            [
                'label' => esc_html__('Icon 2', 'textdomain'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-circle',
                    'library' => 'fa-solid',
                ],
                'recommended' => [
                    'fa-solid' => [
                        'circle',
                        'dot-circle',
                        'square-full',
                    ],
                    'fa-regular' => [
                        'circle',
                        'dot-circle',
                        'square-full',
                    ],
                ],
            ]
        );
        $this->add_control(
            'workflow_title_2',
            [
                'label' => __('Workflow Title 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Workflow Title 2', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'workflow_tagline_2',
            [
                'label' => __('Workflow Tag Line 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Workflow Tag Line 2', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'icon_3',
            [
                'label' => esc_html__('Icon 3', 'textdomain'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-circle',
                    'library' => 'fa-solid',
                ],
                'recommended' => [
                    'fa-solid' => [
                        'circle',
                        'dot-circle',
                        'square-full',
                    ],
                    'fa-regular' => [
                        'circle',
                        'dot-circle',
                        'square-full',
                    ],
                ],
            ]
        );
        $this->add_control(
            'workflow_title_3',
            [
                'label' => __('Workflow Title 3', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Workflow Title 3', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'workflow_tagline_3',
            [
                'label' => __('Workflow Tag Line 3', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Workflow Tag Line 3', 'adforest-elementor'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'last_section',
            [
                'label' => esc_html__('3rd Section', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_title_1',
            [
                'label' => __('Box Title 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Title 1', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_bg_1',
            [
                'label' => __('Box Background Color', 'adforest-elementor'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFFFFF',
            ]
        );

        $this->add_control(
            'box_tagline_1',
            [
                'label' => __('Box Tagline 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Tagline 1', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_btn_text_1',
            [
                'label' => __('Box Btn Text 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Btn Text 1', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_btn_link_1',
            [
                'label' => __('Box Btn Link 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Btn Link 1', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_bg_img_1',
            array(
                'label' => __('Box Image 1', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],

            )
        );

        $this->add_control(
            'box_title_2',
            [
                'label' => __('Box Title 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Title 2', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_bg_2',
            [
                'label' => __('Box Background Color', 'adforest-elementor'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFFFFF',
            ]
        );

        $this->add_control(
            'box_tagline_2',
            [
                'label' => __('Box Tagline 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Tagline 2', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_btn_text_2',
            [
                'label' => __('Box Btn Text 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Btn Text 2', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_btn_link_2',
            [
                'label' => __('Box Btn Link 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Box Btn Link 2', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'box_bg_img_2',
            array(
                'label' => __('Box Image 2', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],

            )
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_title'] = isset($atts['section_title']) ? $atts['section_title'] : "";
        $params['section_tagline'] = isset($atts['section_tagline']) ? $atts['section_tagline'] : "";
        $params['bg_img'] = isset($atts['bg_img']) ? $atts['bg_img']['url'] : "";
        $params['icon_1'] = isset($atts['icon_1']) ? $atts['icon_1'] : "";
        $params['workflow_title_1'] = isset($atts['workflow_title_1']) ? $atts['workflow_title_1'] : "";
        $params['workflow_tagline_1'] = isset($atts['workflow_tagline_1']) ? $atts['workflow_tagline_1'] : "";
        $params['icon_2'] = isset($atts['icon_2']) ? $atts['icon_2'] : "";
        $params['workflow_title_2'] = isset($atts['workflow_title_2']) ? $atts['workflow_title_2'] : "";
        $params['workflow_tagline_2'] = isset($atts['workflow_tagline_2']) ? $atts['workflow_tagline_2'] : "";
        $params['icon_3'] = isset($atts['icon_3']) ? $atts['icon_3'] : "";
        $params['workflow_title_3'] = isset($atts['workflow_title_3']) ? $atts['workflow_title_3'] : "";
        $params['workflow_tagline_3'] = isset($atts['workflow_tagline_3']) ? $atts['workflow_tagline_3'] : "";
        $params['box_title_1'] = isset($atts['box_title_1']) ? $atts['box_title_1'] : "";
        $params['box_tagline_1'] = isset($atts['box_tagline_1']) ? $atts['box_tagline_1'] : "";
        $params['box_btn_text_1'] = isset($atts['box_btn_text_1']) ? $atts['box_btn_text_1'] : "";
        $params['box_btn_link_1'] = isset($atts['box_btn_link_1']) ? $atts['box_btn_link_1'] : "";
        $params['box_bg_img_1'] = isset($atts['box_bg_img_1']) ? $atts['box_bg_img_1']['url'] : "";
        $params['box_title_2'] = isset($atts['box_title_2']) ? $atts['box_title_2'] : "";
        $params['box_tagline_2'] = isset($atts['box_tagline_2']) ? $atts['box_tagline_2'] : "";
        $params['box_btn_text_2'] = isset($atts['box_btn_text_2']) ? $atts['box_btn_text_2'] : "";
        $params['box_btn_link_2'] = isset($atts['box_btn_link_2']) ? $atts['box_btn_link_2'] : "";
        $params['box_bg_img_2'] = isset($atts['box_bg_img_2']) ? $atts['box_bg_img_2']['url'] : "";
        $params['box_bg_1'] = isset($atts['box_bg_1']) ? $atts['box_bg_1'] : "";
        $params['box_bg_2'] = isset($atts['box_bg_2']) ? $atts['box_bg_2'] : "";
        $params['adforest_elementor'] = true;

        if (function_exists('how_it_works_modern_shortcode')) {
            echo how_it_works_modern_shortcode($params);
        }

    }
}