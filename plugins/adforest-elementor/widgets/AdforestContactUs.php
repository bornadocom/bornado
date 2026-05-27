<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class AdforestContactUs extends Widget_Base
{

    public function get_name()
    {
        return 'contact_us_elementor';
    }

    public function get_title()
    {
        return __('Contact Us', 'adforest-elementor');
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
            'basic_settings',
            [ 
                'label' => esc_html__('Basic Settings', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'right_title',
            [ 
                'label' => __('Contact Form Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Contact Form Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'right_description',
            [ 
                'label' => __('Contact Info Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'title' => __('Contact Info Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'contact_short_code',
            [ 
                'label' => __('Contact form 7 shortcode', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'left_settings',
            [ 
                'label' => esc_html__('Left Side Settings', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'left_title_1',
            [ 
                'label' => __('Title 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Title', 'adforest-elementor'),
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
            'left_desc_1',
            [ 
                'label' => __('Description 1', 'adforest-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'title' => __('Description', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'left_title_2',
            [ 
                'label' => __('Title 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Title', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'left_desc_2',
            [ 
                'label' => __('Description 2', 'adforest-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'title' => __('Description', 'adforest-elementor'),
            ]
        );

        $this->end_controls_section();

        // Social Media Icons Section
        $this->start_controls_section(
            'social_media_settings',
            [ 
                'label' => esc_html__('Social Media Icons', 'adforest-elementor'),
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'social_icon',
            [ 
                'label' => __('Icon', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [ 
                    'value' => 'fab fa-facebook-f',
                    'library' => 'fa-brands',
                ],
            ]
        );

        $repeater->add_control(
            'social_link',
            [ 
                'label' => __('Link', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-link.com', 'adforest-elementor'),
                'default' => [ 
                    'url' => '',
                ],
            ]
        );

        $this->add_control(
            'social_icons_list',
            [ 
                'label' => __('Social Icons', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ social_icon.value }}}',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'address_settings',
            [ 
                'label' => esc_html__('Address', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'address',
            [ 
                'label' => __('Address', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'phone_settings',
            [ 
                'label' => esc_html__('Phone', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'phone',
            [ 
                'label' => __('Phone', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'email_settings',
            [ 
                'label' => esc_html__('Email', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'email',
            [ 
                'label' => __('Email', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'title' => '',
                'rows' => 3,
                'placeholder' => '',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['right_title'] = isset($atts['right_title']) ? $atts['right_title'] : "";
        $params['right_description'] = isset($atts['right_description']) ? $atts['right_description'] : "";
        $params['left_title_1'] = isset($atts['left_title_1']) ? $atts['left_title_1'] : "";
        $params['left_desc_1'] = isset($atts['left_desc_1']) ? $atts['left_desc_1'] : "";
        $params['left_title_2'] = isset($atts['left_title_2']) ? $atts['left_title_2'] : "";
        $params['left_desc_2'] = isset($atts['left_desc_2']) ? $atts['left_desc_2'] : "";
        $params['address'] = isset($atts['address']) ? $atts['address'] : "";
        $params['phone'] = isset($atts['phone']) ? $atts['phone'] : "";
        $params['bg_img'] = isset($atts['bg_img']) ? $atts['bg_img']['url'] : "";
        $params['email'] = isset($atts['email']) ? $atts['email'] : "";
        $params['contact_short_code'] = isset($atts['contact_short_code']) ? $atts['contact_short_code'] : "";

        $social_icons_list = isset($atts['social_icons_list']) ? $atts['social_icons_list'] : [];
        $params['social_icons_list'] = [];

        foreach ($social_icons_list as $icon) {
            $params['social_icons_list'][] = [ 
                'icon' => $icon['social_icon'],
                'link' => $icon['social_link']['url']
            ];
        }

        if (function_exists('contact_us_elementor')) {
            echo contact_us_elementor($params);
        }
    }
}