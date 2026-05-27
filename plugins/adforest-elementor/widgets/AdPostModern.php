<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdPostModern extends Widget_Base
{

    public function get_name()
    {
        return 'ad_post_short_base';
    }

    public function get_title()
    {
        return __('Ad Post - Modern', 'adforest-elementor');
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
            'general_settings',
            [ 
                'label' => esc_html__('General Settings', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'form_title',
            [ 
                'label' => __('Form Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Ad Post', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'terms_switch',
            array(
                'label' => __('Terms & Condition Field', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'hide' => __('Hide', 'adforest-elementor'),
                    'show' => __('Show', 'adforest-elementor'),
                ),
            )
        );

        $this->add_control(
            'terms_title',
            [ 
                'label' => __('Terms & Condition Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'conditions' => [ 
                    'terms' => [ 
                        [ 
                            'name' => 'terms_switch',
                            'operator' => 'in',
                            'value' => [ 
                                'show',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'terms_link',
            [ 
                'label' => __('Terms & Conditions Link', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::URL,
                'show_external' => true,
                'conditions' => [ 
                    'terms' => [ 
                        [ 
                            'name' => 'terms_switch',
                            'operator' => 'in',
                            'value' => [ 
                                'show',
                            ],
                        ],
                    ],
                ],
                'default' => [ 
                    'url' => '',
                    'is_external' => true,
                    'nofollow' => true,
                ],
            ]
        );

        $adforest_elementor_repetor = new \Elementor\Repeater();

        $adforest_elementor_repetor->add_control(
            'title',
            [ 
                'label' => __('Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );
        $adforest_elementor_repetor->add_control(
            'slug',
            [ 
                'label' => __('Slug', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $adforest_elementor_repetor->add_control(
            'type',
            array(
                'label' => __('Terms & Condition Field', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'text' => __('Textfield', 'adforest-elementor'),
                    'select' => __('Select/List', 'adforest-elementor'),
                ),
            )
        );
        $adforest_elementor_repetor->add_control(
            'option_values',
            [ 
                'label' => __('Values for Select/List', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Like: value1,value2,value3', 'adforest-elementor'),
                'conditions' => [ 
                    'terms' => [ 
                        [ 
                            'name' => 'type',
                            'operator' => 'in',
                            'value' => [ 
                                'select',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->end_controls_section();
    }
    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['form_title'] = $atts['form_title'] ?? "";
        $params['terms_switch'] = $atts['terms_switch'] ?? "show";
        $params['terms_title'] = $atts['terms_title'] ?? "";
        $params['terms_link'] = $atts['terms_link'] ?? "";
        $params['extra_section_title'] = $atts['extra_section_title'] ?? "";
        $params['type'] = $atts['type'] ?? "";
        $params['fields'] = $atts['fields'] ?? "";

        if (function_exists('ad_post_short_base_func')) {
            echo ad_post_short_base_func($params);
        }
    }
}