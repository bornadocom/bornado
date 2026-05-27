<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use ElementorAdforest\Traits\SliderControlsTrait;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
class MainHero4 extends Widget_Base
{
    use SliderControlsTrait;

    public function get_name()
    {
        return 'adforest_main_hero4';
    }

    public function get_title()
    {
        return __('Main Hero 4', 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    public function get_style_depends()
    {
        return ['adforest-select2'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'main', [
            'label' => esc_html__('Top Advertisement Carousel', 'adforest-elementor'),
        ]);

        $this->add_control(
            'show_main_ad', [
            'label' => __('Show Main Section Top Advert', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__("Yes", 'adforest-elementor'),
                'no' => esc_html__("No", 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'ad_image',
            [
                'label' => __('Ad Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'description' => __('Image Size: 1320x140', 'adforest-elementor'),
            ]
        );


        $this->add_control(
            'ads_lists',
            [
                'label' => __('Ads List', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ ad_image }}}',
                'condition' => [
                    'show_main_ad' => 'yes',
                ],
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'middle_ad', [
            'label' => esc_html__('Middle Advertisement', 'adforest-elementor'),
        ]);
        $this->add_control(
            'show_main_ad_2', [
            'label' => __('Show Main Section Center Advert', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__("Yes", 'adforest-elementor'),
                'no' => esc_html__("No", 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);
        $this->add_control(
            'main_ads_2', [
            'label' => __('Main Section Center Advert', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            "description" => __("1320×140", 'adforest-elementor'),
            'label_block' => true,
            'condition' => [
                'show_main_ad_2' => 'yes',
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section(
            'featured_ads', [
            'label' => esc_html__('Featured Ads', 'adforest-elementor'),
        ]);

        $this->add_control(
            'show_featured_ads_carousel', [
            'label' => __('Show Featured Ad Carousel', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__("Yes", 'adforest-elementor'),
                'no' => esc_html__("No", 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);
        $this->add_control(
            'featured_ads_title', [
            'label' => esc_html__('Featured Ads Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Featured Ads",
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'featured_ads_btn_text', [
            'label' => esc_html__('Featured Ads Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "View All",
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'featured_ads_btn_link', [
            'label' => esc_html__('Featured Ads Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'slider_ad_type', [
            'label' => esc_html__('Select Ad Type In Carousel', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'recent' => esc_html__('Regular', 'adforest-elementor'),
                'featured' => esc_html__('Featured', 'adforest-elementor'),
                'both' => esc_html__('Both', 'adforest-elementor'),
            ],
            'default' => 'featured',
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'featured_ads_ppp', [
            'label' => esc_html__('Number of Ads to show in Featured ads.', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 100,
            'step' => 1,
            'default' => 5,
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'featured_ads_title_limit', [
            'label' => esc_html__('Number of letters in Ad Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 100,
            'step' => 1,
            'default' => 30,
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'slider_grid_type', [
            'label' => esc_html__('Select Grid Type', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => esc_html__('1', 'adforest-elementor'),
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
            ],
            'default' => '2',
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'slider_grid_columns', [
            'label' => esc_html__('Select No of Grid Columns', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => esc_html__('1', 'adforest-elementor'),
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
            ],
            'default' => '4',
            'label_block' => true,
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section(
            'regular_ads', [
            'label' => esc_html__('Regular Ads', 'adforest-elementor'),
        ]);

        $this->add_control(
            'show_regular_ads_carousel', [
            'label' => __('Show Regular Ad Section', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__("Yes", 'adforest-elementor'),
                'no' => esc_html__("No", 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);
        $this->add_control(
            'regular_ads_title', [
            'label' => esc_html__('Regular Ads Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Regular Ads",
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);

        $this->add_control(
            'regular_ads_btn_text', [
            'label' => esc_html__('Regular Ads Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "View All",
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);

        $this->add_control(
            'regular_ads_btn_link', [
            'label' => esc_html__('Regular Ads Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);

        $this->add_control(
            'section_ad_type', [
            'label' => esc_html__('Select Ad Type In Section', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'recent' => esc_html__('Regular', 'adforest-elementor'),
                'featured' => esc_html__('Featured', 'adforest-elementor'),
                'both' => esc_html__('Both', 'adforest-elementor'),
            ],
            'default' => 'recent',
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'regular_ads_ppp', [
            'label' => esc_html__('Number of Ads to show in Regular ads.', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 100,
            'step' => 1,
            'default' => 5,
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'regular_ads_title_limit', [
            'label' => esc_html__('Number of letters in Ad Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 100,
            'step' => 1,
            'default' => 40,
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'regular_grid_type', [
            'label' => esc_html__('Select Grid Type', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => esc_html__('1', 'adforest-elementor'),
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
            ],
            'default' => '2',
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);
        $this->add_control(
            'regular_grid_columns', [
            'label' => esc_html__('Select No of Grid Columns', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => esc_html__('1', 'adforest-elementor'),
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
            ],
            'default' => '4',
            'label_block' => true,
            'condition' => [
                'show_regular_ads_carousel' => 'yes',
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section(
            'left_sidebar', [
            'label' => esc_html__('Left Sidebar Settings', 'adforest-elementor'),
        ]);

        $this->add_control(
            'show_search', [
            'label' => esc_html__('Show Search in the sidebar?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__('Yes', 'adforest-elementor'),
                'no' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);

        $this->add_control(
            'search_title', [
            'label' => esc_html__('Search Sidebar Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Classified Search",
            'label_block' => true,
            'condition' => [
                'show_search' => 'yes',
            ],
        ]);

        $repeater = new Repeater();

        $repeater->add_control(
            'field_type',
            [
                'label' => __('Field Type', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'title' => __('Title', 'adforest-elementor'),
                    'category' => __('Category', 'adforest-elementor'),
                    'location' => __('Location', 'adforest-elementor'),
                    'address' => __('Address (Map Autocomplete)', 'adforest-elementor'),
                ],
                'default' => 'title',
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'field_name',
            [
                'label' => __('Field Type', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => 'title',
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'placeholder_text',
            [
                'label' => __('Placeholder Text', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'label_block' => true,
                'placeholder' => __('Enter placeholder text...', 'adforest-elementor'),
            ]
        );

        $repeater->add_control(
            'field_mode',
            [
                'label' => __('Load Mode', 'adforest-elementor'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'default' => [
                        'title' => __('Default', 'adforest-elementor'),
                        'icon' => 'eicon-check-circle',
                    ],
                    'ajax' => [
                        'title' => __('AJAX Based', 'adforest-elementor'),
                        'icon' => 'eicon-sync',
                    ],
                ],
                'default' => 'default',
                'toggle' => false,
                'condition' => [
                    'field_type' => [ 'category', 'location' ],
                ],
            ]
        );

        $this->add_control(
            'fields',
            [
                'label' => __('Fields', 'adforest-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'field_type' => 'title',
                        'field_name' => 'title',
                        'placeholder_text' => __('Keywords', 'adforest-elementor'),
                    ],
                    [
                        'field_type' => 'category',
                        'field_name' => 'category',
                        'placeholder_text' => __('Select category...', 'adforest-elementor'),
                    ],
                    [
                        'field_type' => 'location',
                        'field_name' => 'location',
                        'placeholder_text' => __('Enter location...', 'adforest-elementor'),
                    ],
                ],
                'title_field' => '{{{ field_type }}}',
                'label_block' => true,
                'condition' => [
                    'show_search' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'search_btn_text', [
            'label' => esc_html__('Search Button Text', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Search Now",
            'label_block' => true,
            'condition' => [
                'show_search' => 'yes',
            ],
        ]);

        $this->add_control(
            'show_categories', [
            'label' => esc_html__('Show Locations in the sidebar?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__('Yes', 'adforest-elementor'),
                'no' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);

        $this->add_control(
            'cat_title', [
            'label' => esc_html__('Location Sidebar Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Locations",
            'label_block' => true,
            'condition' => [
                'show_categories' => 'yes',
            ],
        ]);


        $ad_countries = adforest_get_ad_taxonomy_callback('ad_country');

        $options = [];

        if (is_array($ad_countries) && count($ad_countries) > 0) {
            foreach ($ad_countries as $category) {
                $options[$category->slug] = $category->name;
                $options += get_all_child_terms_slug($category->term_id, 'ad_country');
            }
        }

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'location',
            [
                'label' => esc_html__('Select Location', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'options' => $options,
                'default' => '',
            ]
        );

        $repeater->add_control(
            'location_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'sidebar_locations_repeater',
            [
                'label' => esc_html__('Sidebar Locations Repeater', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ location }}}',
                'condition' => [
                    'show_categories' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_left_ad', [
            'label' => esc_html__('Show Ad in the Left sidebar?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__('Yes', 'adforest-elementor'),
                'no' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);

        $this->add_control(
            'left_ad', [
            'label' => __('Left Advert Image', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            "description" => __("303x485", 'adforest-elementor'),
            'condition' => [
                'show_left_ad' => 'yes',
            ],
        ]);

        $this->end_controls_section();


        $this->start_controls_section(
            'right_sidebar', [
            'label' => esc_html__('Right Sidebar Settings', 'adforest-elementor'),
        ]);

        $this->add_control(
            'show_right_ad_1', [
            'label' => esc_html__('Show Ad 1 in the right sidebar?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__('Yes', 'adforest-elementor'),
                'no' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);

        $this->add_control(
            'right_ad_1', [
            'label' => __('Right Advert Image 1', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            "description" => __("303x485", 'adforest-elementor'),
            'condition' => [
                'show_right_ad_1' => 'yes',
            ],
        ]);

        $this->add_control(
            'show_right_sec_ad_type', [
            'label' => esc_html__('Show Right sidebar Ads?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__('Yes', 'adforest-elementor'),
                'no' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);
        $this->add_control(
            'right_sec_ad_type', [
            'label' => esc_html__('What type of ads do you want to show on the Right sidebar?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'recent' => esc_html__('Recent', 'adforest-elementor'),
                'featured' => esc_html__('Featured', 'adforest-elementor'),
            ],
            'default' => 'featured',
            'label_block' => true,
            'condition' => [
                'show_right_sec_ad_type' => 'yes',
            ],
        ]);

        $this->add_control(
            'right_section_ppp', [
            'label' => esc_html__('Number of Ads to show in Right Sidebar?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 100,
            'step' => 1,
            'default' => 5,
            'label_block' => true,
            'condition' => [
                'show_right_sec_ad_type' => 'yes',
            ],
        ]);

        $this->add_control(
            'right_section_title_limit', [
            'label' => esc_html__('Number of letters in Ad Title?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 100,
            'step' => 1,
            'default' => 5,
            'label_block' => true,
            'condition' => [
                'show_right_sec_ad_type' => 'yes',
            ],
        ]);

        $this->add_control(
            'show_right_ad_2', [
            'label' => esc_html__('Show Ad 2 in the right sidebar?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'yes' => esc_html__('Yes', 'adforest-elementor'),
                'no' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'yes',
            'label_block' => true,
        ]);

        $this->add_control(
            'right_ad_2', [
            'label' => __('Right Advert Image 2', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            "description" => __("303x485", 'adforest-elementor'),
            'condition' => [
                'show_right_ad_2' => 'yes',
            ],
        ]);

        $this->end_controls_section();

        $this->register_slider_controls([
            'section_id' => 'hero_carousel_settings',
            'section_label' => esc_html__('Hero Slider Settings', 'adforest-elementor'),
            'condition' => [
                'show_main_ad' => 'yes',
            ],
        ]);

        $this->register_slider_controls([
            'section_id' => 'featured_carousel_settings',
            'section_label' => esc_html__('Featured Ads Slider Settings', 'adforest-elementor'),
            'prefix' => 'featured',
            'condition' => [
                'show_featured_ads_carousel' => 'yes',
            ],
        ]);
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['show_main_ad'] = $atts['show_main_ad'] ?? "";
        $params['show_right_sec_ad_type'] = $atts['show_right_sec_ad_type'] ?? "";
        $params['ads_lists'] = $atts['ads_lists'] ?? "";
        $params['show_main_ad_2'] = $atts['show_main_ad_2'] ?? "";
        $params['main_ads_2'] = $atts['main_ads_2'] ?? "";
        $params['show_featured_ads_carousel'] = $atts['show_featured_ads_carousel'] ?? "";
        $params['featured_ads_title'] = $atts['featured_ads_title'] ?? "";
        $params['section_ad_type'] = $atts['section_ad_type'] ?? "";
        $params['slider_ad_type'] = $atts['slider_ad_type'] ?? "";
        $params['regular_ads_ppp'] = $atts['regular_ads_ppp'] ?? "";
        $params['featured_ads_ppp'] = $atts['featured_ads_ppp'] ?? "";
        $params['featured_ads_btn_text'] = $atts['featured_ads_btn_text'] ?? "";
        $params['featured_ads_btn_link'] = $atts['featured_ads_btn_link'] ?? "";
        $params['regular_ads_title'] = $atts['regular_ads_title'] ?? "";
        $params['regular_ads_btn_text'] = $atts['regular_ads_btn_text'] ?? "";
        $params['show_search'] = $atts['show_search'] ?? "";
        $params['search_title'] = $atts['search_title'] ?? "";
        $params['fields'] = $atts['fields'] ?? "";
        $params['search_btn_text'] = $atts['search_btn_text'] ?? "";
        $params['show_categories'] = $atts['show_categories'] ?? "";
        $params['cat_title'] = $atts['cat_title'] ?? "";
        $params['show_left_ad'] = $atts['show_left_ad'] ?? "";
        $params['left_ad'] = $atts['left_ad'] ?? "";
        $params['show_right_ad_1'] = $atts['show_right_ad_1'] ?? "";
        $params['right_ad_1'] = $atts['right_ad_1'] ?? "";
        $params['right_sec_ad_type'] = $atts['right_sec_ad_type'] ?? "";
        $params['right_section_ppp'] = $atts['right_section_ppp'] ?? "";
        $params['show_right_ad_2'] = $atts['show_right_ad_2'] ?? "";
        $params['right_ad_2'] = $atts['right_ad_2'] ?? "";
        $params['regular_ads_btn_link'] = $atts['regular_ads_btn_link'] ?? "";
        $params['show_regular_ads_carousel'] = $atts['show_regular_ads_carousel'] ?? "";
        $params['sidebar_locations_repeater'] = $atts['sidebar_locations_repeater'] ?? "";
        $params['featured_ads_title_limit'] = $atts['featured_ads_title_limit'] ?? 0;
        $params['regular_ads_title_limit'] = $atts['regular_ads_title_limit'] ?? 0;
        $params['right_section_title_limit'] = $atts['right_section_title_limit'] ?? 0;
        $params['slider_grid_columns'] = $atts['slider_grid_columns'] ?? 0;
        $params['right_ad_2_link'] = $atts['right_ad_2_link'] ?? [];
        $params['right_ad_1_link'] = $atts['right_ad_1_link'] ?? [];
        $params['slider_grid_type'] = $atts['slider_grid_type'] ?? [];
        $params['regular_grid_type'] = $atts['regular_grid_type'] ?? [];
        $params['regular_grid_columns'] = $atts['regular_grid_columns'] ?? [];
        $params['loop_slider'] = $atts['loop_slider'] ?? 'yes';
        $params['autoplay_slider'] = $atts['autoplay_slider'] ?? 'no';
        $params['pause_on_hover_slider'] = $atts['pause_on_hover_slider'] ?? 'no';
        $params['slider_speed'] = $atts['slider_speed'] ?? 3000;
        $params['featured_loop_slider'] = $atts['featured_loop_slider'] ?? 'yes';
        $params['featured_autoplay_slider'] = $atts['featured_autoplay_slider'] ?? 'no';
        $params['featured_pause_on_hover_slider'] = $atts['featured_pause_on_hover_slider'] ?? 'no';
        $params['featured_slider_speed'] = $atts['featured_slider_speed'] ?? 3000;

        if (function_exists('main_hero_4_shortcode')) {
            echo main_hero_4_shortcode($params);
        }
        ?>
        <?php
        // Only run this script if we're in Elementor's edit mode.
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) :
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.default-select').select2();

                    $('.adt-ads-sub-carousel').each(function () {
                        const $carousel = $(this);
                        const columnCount = parseInt($carousel.data('columns')) || 4; // Default fallback: 4

                        const sliderLoop = $carousel.data('loop') === true;
                        const sliderAutoplay = $carousel.data('autoplay') === true;
                        const sliderSpeed = $carousel.data('autoplay-timeout') || 3000;
                        const pause_on_hover = $carousel.data('pause-on-hover') === true;

                        $carousel.owlCarousel({
                            loop: sliderLoop,
                            rtl: is_rtl,
                            margin: 20,
                            nav: true,
                            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                            dots: false,
                            autoplay: sliderAutoplay,
                            autoplayTimeout: sliderSpeed,
                            autoplayHoverPause: pause_on_hover,
                            responsive: {
                                0: {
                                    items: 1
                                },
                                420: {
                                    items: Math.min(columnCount, 2)
                                },
                                576: {
                                    items: Math.min(columnCount, 2)
                                },
                                768: {
                                    items: Math.min(columnCount, 3)
                                },
                                992: {
                                    items: Math.min(columnCount, 4)
                                },
                                1200: {
                                    items: Math.min(columnCount, columnCount)
                                },
                                1400: {
                                    items: Math.min(columnCount, columnCount)
                                }
                            }
                        });
                    });

                    $('.category-mini-carousel').owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 10,
                        nav: false,
                        dots: false,
                        autoplay: true,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: false,
                        responsive: {
                            0: {
                                items: 2
                            }, 576: {
                                items: 3
                            }, 992: {
                                items: 5
                            }, 1200: {
                                items: 7
                            }, 1400: {
                                items: 9
                            }
                        }
                    });

                    $('.owl-carousel-main-hero-4').each(function() {
                        const slider = $(this);

                        var sliderLoop = slider.data('loop') === true;
                        var sliderAutoplay = slider.data('autoplay') === true;
                        var sliderSpeed = slider.data('autoplay-timeout') || 3000;
                        var pause_on_hover = slider.data('pause-on-hover') === true;


                        slider.owlCarousel({
                            loop: sliderLoop,
                            rtl: is_rtl,
                            margin: 10,
                            nav: false,
                            dots: false,
                            autoplay: sliderAutoplay,
                            autoplayTimeout: sliderSpeed,
                            autoplayHoverPause: pause_on_hover,
                            responsive: {
                                0: {
                                    items: 1
                                },
                                576: {
                                    items: 1
                                },
                                992: {
                                    items: 1
                                },
                                1200: {
                                    items: 1
                                }
                            }
                        });
                    });
                });
            </script>
        <?php
        endif;
    }
}
