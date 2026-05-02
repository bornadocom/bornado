<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class ClassicHome extends Widget_Base
{
    public function get_name()
    {
        return 'classic_home_widget_base';
    }

    public function get_title()
    {
        return __('Classic Home Widget', 'adforest-elementor');
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
            'cats',
            [
                'label' => esc_html__('Categories', 'adforest-elementor'),
            ]
        );

        $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');

        $options = [];

        if (is_array($ad_categories) && count($ad_categories) > 0) {
            foreach ($ad_categories as $category) {
                $options[$category->slug] = $category->name;
                $options += get_all_child_terms_slug($category->term_id, 'ad_cats');
            }
        }

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'mini_category',
            [
                'label' => esc_html__('Select Category', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'options' => $options,
                'default' => '',
            ]
        );

        $repeater->add_control(
            'category_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'mini_categories_repeater',
            [
                'label' => esc_html__('Categories Repeater', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ (function() {
                    const categoryId = mini_category;
                    const categories = ' . json_encode($options) . ';
                    return categories[categoryId] || "Category #" + categoryId;
                })() }}}',
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'basic',
            [
                'label' => esc_html__('Filters', 'adforest-elementor'),
            ]
        );


        $this->add_control(
            'filter_text',
            [
                'label' => __('Filter Button Text', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Filter Button Text', 'adforest-elementor'),
                'default' => 'All Filters',
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'field_type',
            [
                'label' => __('Field Type', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'ad_title' => __('Title', 'adforest-elementor'),
                    'radius_search' => __('Radius Search', 'adforest-elementor'),
                    'condition' => __('Condition', 'adforest-elementor'),
                    'warranty' => __('Warranty', 'adforest-elementor'),
                    'price' => __('Price', 'adforest-elementor'),
                    'ad' => __('Ad', 'adforest-elementor'),
                    'ad_categories' => __('Ad Categories', 'adforest-elementor'),
                    'ad_type' => __('Ad Type', 'adforest-elementor'),
                    'ad_locations' => __('Ad Locations', 'adforest-elementor'),
                    'ad_currency' => __('Ad Currency', 'adforest-elementor'),
                ],
                'default' => 'condition',
                'description' => __('Select the field type for this item', 'adforest-elementor'),
            ]
        );

        $repeater->add_control(
            'ad_title_title',
            [
                'label' => __('Ad Title Heading', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => [
                    'field_type' => 'ad_title',
                ],
            ]
        );

        $repeater->add_control(
            'ad_currency_title',
            [
                'label' => __('Ad Currency Heading', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => [
                    'field_type' => 'ad_currency',
                ],
            ]
        );

        $repeater->add_control(
            'radius_search_title',
            [
                'label' => __('Radius Search Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => [
                    'field_type' => 'radius_search',
                ],
            ]
        );

        $repeater->add_control(
            'condition_title',
            [
                'label' => __('Condition Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Enter the title for the condition', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'condition',
                ],
            ]
        );

        $repeater->add_control(
            'ad_locations_title',
            [
                'label' => __('Ad Locations Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Enter the title for the Locations', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'ad_locations',
                ],
            ]
        );

        $repeater->add_control(
            'ad_type_title',
            [
                'label' => __('Ad Type Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Enter the title for the Ad Type', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'ad_type',
                ],
            ]
        );

        $repeater->add_control(
            'warranty_title',
            [
                'label' => __('Warranty Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Enter the title for the warranty', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'warranty',
                ],
            ]
        );

        $repeater->add_control(
            'min_price',
            [
                'label' => __('Minimum Price', 'adforest-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'description' => __('Enter the minimum price', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'price',
                ],
            ]
        );

        $repeater->add_control(
            'max_price',
            [
                'label' => __('Maximum Price', 'adforest-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'description' => __('Enter the maximum price', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'price',
                ],
            ]
        );

        $repeater->add_control(
            'adv_image',
            [
                'label' => __('Ad Image', 'adforest-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                'description' => __('Upload an image for the ad', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'ad',
                ],
            ]
        );

        $repeater->add_control(
            'ad_categories_title',
            [
                'label' => __('Ad Categories Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Enter the title for the Categories', 'adforest-elementor'),
                'condition' => [
                    'field_type' => 'ad_categories',
                ],
            ]
        );

        $repeater->add_control(
            'enable_show_more_on_cats',
            [
                'label' => __('Enable Show More On Categories', 'adforest-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'adforest-elementor'),
                'label_off' => __('No', 'adforest-elementor'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'field_type' => 'ad_categories',
                ],
            ]
        );

        $repeater->add_control(
            'no_of_cats_before_show_more',
            [
                'label' => __('Number Of Categories Before Show More', 'adforest-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
                'condition' => [
                    'field_type' => 'ad_categories',
                ],
            ]
        );

        $this->add_control(
            'items',
            [
                'label' => __('Repeater Items', 'adforest-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ field_type }}}',
            ]
        );

        $this->end_controls_section();
        $this->start_controls_section(
            'ads_settings',
            [
                'label' => esc_html__('Ads', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'ads_title_limit',
            [
                'label' => __('Ad title (limit in characters)', 'adforest-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 40,
            ]
        );

        $this->add_control(
            'section_grid_style', [
                'label'   => __( 'Ad Grid Style', 'adforest-elementor' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    '1' => esc_html__( 'Style 1', 'adforest-elementor' ),
                    '2' => esc_html__( 'Style 2', 'adforest-elementor' ),
                    '3' => esc_html__( 'Style 3', 'adforest-elementor' ),
                ],
                'default' => 'featured',
            ]
        );

        $this->add_control(
            'section_grid_cols', [
                'label'   => __( 'Ad Grid Columns', 'adforest-elementor' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    '2' => esc_html__( '2', 'adforest-elementor' ),
                    '3' => esc_html__( '3', 'adforest-elementor' ),
                    '4' => esc_html__( '4', 'adforest-elementor' ),
                    '5' => esc_html__( '5', 'adforest-elementor' ),
                ],
                'default' => 4,
            ]
        );

        $this->add_control(
            'number_of_ads', [
                'label'   => __( 'Number of Ads Per page', 'adforest-elementor' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 10,
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['filter_text'] = $atts['filter_text'] ?? "";
        $params['items'] = $atts['items'] ?? "";
        $params['mini_categories_repeater'] = $atts['mini_categories_repeater'] ?? [];
        $params['ads_title_limit'] = $atts['ads_title_limit'] ?? [];
        $params['section_grid_style'] = $atts['section_grid_style'] ?? [];
        $params['number_of_ads'] = $atts['number_of_ads'] ?? [];
        $params['section_grid_cols'] = $atts['section_grid_cols'] ?? [];

        if (function_exists('classic_home_widget_base_shortcode')) {
            echo classic_home_widget_base_shortcode($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-category-types-carousel').owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 0,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        responsive: {
                            0: {
                                items: 2
                            },
                            576: {
                                items: 2
                            },
                            768: {
                                items: 4
                            },
                            992: {
                                items: 7
                            },
                            1200: {
                                items: 9
                            },
                            1400: {
                                items: 12
                            }
                        }
                    });
                });
            </script>
        <?php }
    }
}