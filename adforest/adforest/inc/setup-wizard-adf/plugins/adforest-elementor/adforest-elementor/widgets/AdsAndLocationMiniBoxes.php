<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ElementorAdforest\Traits\SliderControlsTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class AdsAndLocationMiniBoxes extends Widget_Base {
	use SliderControlsTrait;
	public function get_name() {
		return 'adforest_mini_ad_and_location';
	}

	public function get_title() {
		return __( "Mini ADs & Location", 'adforest-elementor' );
	}

	public function get_icon() {
		return 'fa fa-audio-description';
	}

	public function get_categories() {
		return [ 'adforest_widgets' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'basic', [
				'label' => esc_html__( 'Basic', 'adforest-elementor' ),
			]
		);

		$this->add_control(
			'show_main_sec_ad_type_1', [
			'label'       => __( 'Show Main Section Top Carousel', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( "Yes", 'adforest-elementor' ),
				'no'  => esc_html__( "No", 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
		] );
		$this->add_control(
			'main_sec_ad_type_1', [
			'label'       => esc_html__( 'What type of ads do you want to show on the upper 1st section?', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'recent'   => esc_html__( 'Recent', 'adforest-elementor' ),
				'featured' => esc_html__( 'Featured', 'adforest-elementor' ),
			],
			'default'     => 'featured',
			'label_block' => true,
			'condition'   => [
				'show_main_sec_ad_type_1' => 'yes',
			],
		] );

		$this->add_control(
			'main_sec_ad_type_1_title', [
			'label'       => esc_html__( '1st Section Title', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'Featured Ads',
			'label_block' => true,
			'condition'   => [
				'show_main_sec_ad_type_1' => 'yes',
			],
		] );

		$this->add_control(
			'main_sec_ad_type_1_btn_text', [
			'label'       => esc_html__( '1st Section Button Text', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'View All',
			'label_block' => true,
			'condition'   => [
				'show_main_sec_ad_type_1' => 'yes',
			],
		] );

		$this->add_control(
			'main_sec_ad_type_1_btn_link', [
			'label'       => esc_html__( '1st Section Button Link', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'label_block' => true,
			'condition'   => [
				'show_main_sec_ad_type_1' => 'yes',
			],
		] );

		$this->add_control(
			'main_section_ppp_1', [
			'label'       => esc_html__( 'Number of Ads to show in main section?', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 10,
			'step'        => 1,
			'default'     => 5,
			'label_block' => true,
			'condition'   => [
				'show_main_sec_ad_type_1' => 'yes',
			],
		] );

		$this->add_control(
			'main_sec_ad_type_2', [
			'label'       => esc_html__( 'What type of ads do you want to show on the upper 2nd section?', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'recent'   => esc_html__( 'Recent', 'adforest-elementor' ),
				'featured' => esc_html__( 'Featured', 'adforest-elementor' ),
			],
			'default'     => 'recent',
			'label_block' => true,
		] );

		$this->add_control(
			'main_sec_ad_type_2_title', [
			'label'       => esc_html__( '2nd Section Title', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'Recent Ads',
			'label_block' => true,
		] );

		$this->add_control(
			'main_sec_ad_type_2_btn_text', [
			'label'       => esc_html__( '2nd Section Button Text', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'View All',
			'label_block' => true,
		] );
		$this->add_control(
			'main_sec_ad_type_2_btn_link', [
			'label'       => esc_html__( '2nd Section Button Link', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'label_block' => true,
		] );

		$this->add_control(
			'main_section_ppp_2', [
			'label'       => esc_html__( 'Number of Ads to show in main section?', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 10,
			'step'        => 1,
			'default'     => 5,
			'label_block' => true,
		] );

		$this->add_control(
			'show_ad_cats_advert', [
			'label'       => esc_html__( 'Show Horizontal Advertisement.', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( 'Yes', 'adforest-elementor' ),
				'no'  => esc_html__( 'No', 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
		] );
		$this->add_control(
			'ad_cat_advert',
			[
				'label'       => __( 'Horizontal Advertisement image', 'adforest-elementor' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				"description" => __( "1320×140", 'adforest-elementor' ),
				'label_block' => true,
				'condition'   => [
					'show_ad_cats_advert' => 'yes',
				],
			]
		);

		$this->add_control(
			'location_section_title', [
			'label'       => esc_html__( 'Location Section Title', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'Popular Locations',
			'label_block' => true,
		] );

		$this->add_control(
			'location_section_btn_text', [
			'label'       => esc_html__( 'Locations Section Button Text', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'View All',
			'label_block' => true,
		] );

		$this->add_control(
			'location_section_btn_link', [
			'label'       => esc_html__( 'Locations Section Button Link', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'label_block' => true,
		] );

		$ad_countries = adforest_get_ad_taxonomy_callback( 'ad_country' );

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
				'label'       => esc_html__( 'Select Location', 'adforest-elementor' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => false,
				'options'     => $options,
				'default'     => '',
			]
		);

		$repeater->add_control(
			'location_image',
			[
				'label'   => esc_html__( 'Upload Image', 'adforest-elementor' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		$this->add_control(
			'locations_repeater',
			[
				'label'       => esc_html__( 'Locations Repeater', 'adforest-elementor' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [],
                'title_field' => '{{{ (function() {
                    const categoryId = location;
                    const categories = ' . json_encode($options) . ';
                    return categories[categoryId] || "location #" + categoryId;
                })() }}}',
				'label_block' => true,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'right_sec_settings', [
				'label' => esc_html__( 'Right Section Settings', 'adforest-elementor' ),
			]
		);

		$this->add_control(
			'hide_right_sidebar', [
			'label'       => esc_html__( 'Hide the Sidebar', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'no'  => esc_html__( 'No', 'adforest-elementor' ),
				'yes' => esc_html__( 'Yes', 'adforest-elementor' ),
			],
			'default'     => 'no',
			'label_block' => true,
		] );

		$this->add_control(
			'show_right_sec_ad_type', [
			'label'       => esc_html__( 'Show Ads in Right Sidebar.', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( 'Yes', 'adforest-elementor' ),
				'no'  => esc_html__( 'No', 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
		] );

		$this->add_control(
			'right_sec_ad_type_title', [
			'label'       => esc_html__( 'Section Title', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'label_block' => true,
			'condition'   => [
				'hide_right_sidebar'     => 'no',
				'show_right_sec_ad_type' => 'yes',
			],
		] );

		$this->add_control(
			'right_sec_ad_type', [
			'label'       => esc_html__( 'What type of ads do you want to show on the right sidebar?', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'recent'   => esc_html__( 'Recent', 'adforest-elementor' ),
				'featured' => esc_html__( 'Featured', 'adforest-elementor' ),
			],
			'default'     => 'featured',
			'label_block' => true,
			'condition'   => [
				'hide_right_sidebar'     => 'no',
				'show_right_sec_ad_type' => 'yes',
			],
		] );

		$this->add_control(
			'right_section_ppp', [
			'label'       => esc_html__( 'Number of Ads to show in Right Sidebar?', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 10,
			'step'        => 1,
			'default'     => 5,
			'label_block' => true,
			'condition'   => [
				'hide_right_sidebar'     => 'no',
				'show_right_sec_ad_type' => 'yes',
			],
		] );

		$this->add_control(
			'show_right_ad_img', [
			'label'       => esc_html__( 'Show Ad 1 in Right Sidebar.', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( 'Yes', 'adforest-elementor' ),
				'no'  => esc_html__( 'No', 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
			'condition'   => [
				'hide_right_sidebar' => 'no',
			],
		] );
		$this->add_control(
			'right_advert_img', [
			'label'       => __( 'Right Advert Image', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			"description" => __( "303x485", 'adforest-elementor' ),
			'label_block' => true,
			'condition'   => [
				'hide_right_sidebar' => 'no',
				'show_right_ad_img'  => 'yes',
			],
		] );

		$this->add_control(
			'show_right_ad_img_2', [
			'label'       => esc_html__( 'Show Ad 2 in Right Sidebar.', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( 'Yes', 'adforest-elementor' ),
				'no'  => esc_html__( 'No', 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
			'condition'   => [
				'hide_right_sidebar' => 'no',
			],
		] );
		$this->add_control(
			'right_advert_img_2', [
			'label'       => __( 'Right Advert Image 2', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			"description" => __( "303x294", 'adforest-elementor' ),
			'label_block' => true,
			'condition'   => [
				'hide_right_sidebar'   => 'no',
				'show_right_ad_img_2' => 'yes',
			],
		] );

		$this->end_controls_section();
		$this->register_slider_controls();
	}

	protected function render() {
		$atts                                  = $this->get_settings_for_display();
		$params                                = array();
		$params['right_sec_ad_type']           = isset( $atts['right_sec_ad_type'] ) ? $atts['right_sec_ad_type'] : "";
		$params['right_section_ppp']           = isset( $atts['right_section_ppp'] ) ? $atts['right_section_ppp'] : "";
		$params['right_advert_img']            = isset( $atts['right_advert_img'] ) ? $atts['right_advert_img'] : "";
		$params['right_advert_img_2']          = isset( $atts['right_advert_img_2'] ) ? $atts['right_advert_img_2'] : "";
		$params['hide_right_sidebar']          = isset( $atts['hide_right_sidebar'] ) ? $atts['hide_right_sidebar'] : "";
		$params['main_sec_ad_type_1']          = isset( $atts['main_sec_ad_type_1'] ) ? $atts['main_sec_ad_type_1'] : "";
		$params['main_sec_ad_type_1_btn_text'] = isset( $atts['main_sec_ad_type_1_btn_text'] ) ? $atts['main_sec_ad_type_1_btn_text'] : "";
		$params['main_section_ppp_1']          = isset( $atts['main_section_ppp_1'] ) ? $atts['main_section_ppp_1'] : "";
		$params['main_sec_ad_type_2']          = isset( $atts['main_sec_ad_type_2'] ) ? $atts['main_sec_ad_type_2'] : "";
		$params['main_sec_ad_type_2_btn_text'] = isset( $atts['main_sec_ad_type_2_btn_text'] ) ? $atts['main_sec_ad_type_2_btn_text'] : "";
		$params['main_section_ppp_2']          = isset( $atts['main_section_ppp_2'] ) ? $atts['main_section_ppp_2'] : "";
		$params['ad_cat_advert']               = isset( $atts['ad_cat_advert'] ) ? $atts['ad_cat_advert'] : "";
		$params['show_ad_cats_advert']         = isset( $atts['show_ad_cats_advert'] ) ? $atts['show_ad_cats_advert'] : "";
		$params['location_section_title']      = isset( $atts['location_section_title'] ) ? $atts['location_section_title'] : "";
		$params['location_section_btn_text']   = isset( $atts['location_section_btn_text'] ) ? $atts['location_section_btn_text'] : "";
		$params['main_sec_ad_type_2_title']    = isset( $atts['main_sec_ad_type_2_title'] ) ? $atts['main_sec_ad_type_2_title'] : "";
		$params['main_sec_ad_type_1_title']    = isset( $atts['main_sec_ad_type_1_title'] ) ? $atts['main_sec_ad_type_1_title'] : "";
		$params['locations_repeater']          = isset( $atts['locations_repeater'] ) ? $atts['locations_repeater'] : "";
		$params['show_main_sec_ad_type_1']     = isset( $atts['show_main_sec_ad_type_1'] ) ? $atts['show_main_sec_ad_type_1'] : "";
		$params['main_sec_ad_type_1_btn_link'] = isset( $atts['main_sec_ad_type_1_btn_link'] ) ? $atts['main_sec_ad_type_1_btn_link'] : "";
		$params['show_right_sec_ad_type']      = isset( $atts['show_right_sec_ad_type'] ) ? $atts['show_right_sec_ad_type'] : "";
		$params['right_sec_ad_type_title']     = isset( $atts['right_sec_ad_type_title'] ) ? $atts['right_sec_ad_type_title'] : "";
		$params['show_right_ad_img']           = isset( $atts['show_right_ad_img'] ) ? $atts['show_right_ad_img'] : "";
		$params['show_right_ad_img_2']         = isset( $atts['show_right_ad_img_2'] ) ? $atts['show_right_ad_img_2'] : "";
		$params['main_sec_ad_type_2_btn_link'] = isset( $atts['main_sec_ad_type_2_btn_link'] ) ? $atts['main_sec_ad_type_2_btn_link'] : "";
		$params['location_section_btn_link']   = isset( $atts['location_section_btn_link'] ) ? $atts['location_section_btn_link'] : "";
		$params['adforest_elementor']          = true;
		$params['loop_slider'] = $atts['loop_slider'] ?? 'yes';
        $params['autoplay_slider'] = $atts['autoplay_slider'] ?? 'no';
        $params['pause_on_hover_slider'] = $atts['pause_on_hover_slider'] ?? 'no';
        $params['slider_speed'] = $atts['slider_speed'] ?? 3000;

		if ( function_exists( 'adforest_mini_ad_and_location' ) ) {
			echo adforest_mini_ad_and_location( $params );
		}

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-mini-ads-carousel').each(function () {
                        const $carousel = $(this);
                        const mobileColumns = parseInt($carousel.data('mobile-columns')) || 4;
						var sliderLoop = $carousel.data('loop') === true;
						var sliderAutoplay = $carousel.data('autoplay') === true;
						var sliderSpeed = $carousel.data('autoplay-timeout') || 3000;
						var pause_on_hover = $carousel.data('pause-on-hover') === true;

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
                                    items: mobileColumns,
                                    margin: 10,
                                }, 576: {
                                    items: mobileColumns
                                }, 768: {
                                    items: 3
                                }, 992: {
                                    items: 3
                                }, 1200: {
                                    items: 4
                                }, 1400: {
                                    items: 5
                                }
                            }
                        });
                    });
                });
            </script>
        <?php }
	}
}
