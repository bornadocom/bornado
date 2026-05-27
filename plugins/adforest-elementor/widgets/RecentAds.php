<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class RecentAds extends Widget_Base {
	public function get_name() {
		return 'recent_ads_shortcode';
	}

	public function get_title() {
		return __( "Recent Ads", "adforest-elementor" );
	}

	public function get_icon() {
		return "fa fa-audio-description";
	}

	public function get_categories() {
		return [ 'adforest_widgets' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'basic', [
			'label' => esc_html__( 'Basic', 'adforest-elementor' ),
		] );

		$this->add_control(
			'main_sec_ad_type', [
			'label'   => esc_html__( 'What type of ads do you want to show on the main section?', 'adforest-elementor' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'recent'   => esc_html__( 'Recent', 'adforest-elementor' ),
				'featured' => esc_html__( 'Featured', 'adforest-elementor' ),
				'both' => esc_html__( 'Both', 'adforest-elementor' ),
			],
			'default' => 'recent',
		] );
		$this->add_control(
			'main_section_ppp', [
			'label'   => esc_html__( 'Number of Ads to show in main section?', 'adforest-elementor' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 10,
			'step'    => 1,
			'default' => 5,
		] );

        $this->add_control(
            'section_title', [
            'label'       => __( 'Section Title', 'adforest-elementor' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
        ] );

        $this->add_control(
            'btn_title', [
            'label'       => __( 'Button Title', 'adforest-elementor' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default' => __("View All", "adforest-elementor"),
        ] );

        $this->add_control(
            'btn_link', [
            'label'       => __( 'Button Link', 'adforest-elementor' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
        ] );

        $this->add_control(
            'ad_title_limit_main', [
            'label'       => __( 'Ad Title Limit Main', 'adforest-elementor' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default' => 20,
        ] );

		$this->end_controls_section();

		$this->start_controls_section(
			'right_ads', [
			'label' => esc_html__( 'Right Side Ads Section', 'adforest-elementor' ),
		] );

		$this->add_control(
			'show_right_ad_1', [
			'label'   => esc_html__( 'Do you want to show Ad 1 on the right section?', 'adforest-elementor' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'yes' => esc_html__( 'Yes', 'adforest-elementor' ),
				'no'  => esc_html__( 'No', 'adforest-elementor' ),
			],
			'default' => 'yes',
		] );

		$this->add_control(
			'advert_1', [
			'label'       => __( 'Ad 1', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			"description" => __( "303x485", 'adforest-elementor' ),
			'condition'   => [
				'show_right_ad_1' => 'yes',
			],
		] );


		$this->add_control(
			'show_right_ad_2', [
			'label'   => esc_html__( 'Do you want to show Ad 2 on the right section?', 'adforest-elementor' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'yes' => esc_html__( 'Yes', 'adforest-elementor' ),
				'no'  => esc_html__( 'No', 'adforest-elementor' ),
			],
			'default' => 'yes',
		] );
		$this->add_control(
			'advert_2', [
			'label'       => __( 'Ad 2', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			"description" => __( "303x485", 'adforest-elementor' ),
			'condition'   => [
				'show_right_ad_2' => 'yes',
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section(
			'left_section', [
			'label' => esc_html__( 'Left Section Settings', 'adforest-elementor' ),
		] );

		$this->add_control(
			'show_left_sec_ad_type', [
			'label'       => __( 'Show Sidebar Ads?', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( "Yes", 'adforest-elementor' ),
				'no'  => esc_html__( "No", 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
		] );

		$this->add_control(
			'left_sec_ads_title', [
			'label'     => __( 'Ads Sidebar Title', 'adforest-elementor' ),
			'type'      => \Elementor\Controls_Manager::TEXT,
			'condition' => [
				'show_left_sec_ad_type' => 'yes',
			],
		] );
		$this->add_control(
			'left_sec_ad_type', [
			'label'     => esc_html__( 'What type of ads do you want to show on the left sidebar?', 'adforest-elementor' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'options'   => [
				'recent'   => esc_html__( 'Recent', 'adforest-elementor' ),
				'featured' => esc_html__( 'Featured', 'adforest-elementor' ),
			],
			'default'   => 'featured',
			'condition' => [
				'show_left_sec_ad_type' => 'yes',
			],
		] );

		$this->add_control(
			'left_section_ppp', [
			'label'     => esc_html__( 'Number of Ads to show in Left Sidebar?', 'adforest-elementor' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 10,
			'step'      => 1,
			'default'   => 5,
			'condition' => [
				'show_left_sec_ad_type' => 'yes',
			],
		] );

        $this->add_control(
            'ad_title_limit_side', [
            'label'       => __( 'Ad Title Limit Sidebar', 'adforest-elementor' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default' => 20,
        ] );

		$this->add_control(
			'show_left_ad', [
			'label'       => __( 'Show Advert in Sidebar.', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( "Yes", 'adforest-elementor' ),
				'no'  => esc_html__( "No", 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
		] );
		$this->add_control(
			'left_ad', [
			'label'       => __( 'Left Advert Image', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			"description" => __( "303x485", 'adforest-elementor' ),
			'condition'   => [
				'show_left_ad' => 'yes',
			],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$atts                            = $this->get_settings_for_display();
		$params                          = array();
		$params['adforest_elementor']    = true;
		$params['main_sec_ad_type']      = $atts['main_sec_ad_type'] ?? "";
		$params['main_section_ppp']      = $atts['main_section_ppp'] ?? "";
		$params['show_right_ad_1']       = $atts['show_right_ad_1'] ?? "";
		$params['show_right_ad_2']       = $atts['show_right_ad_2'] ?? "";
		$params['advert_1']              = $atts['advert_1'] ?? "";
		$params['advert_2']              = $atts['advert_2'] ?? "";
		$params['left_sec_ad_type']      = $atts['left_sec_ad_type'] ?? "";
		$params['left_section_ppp']      = $atts['left_section_ppp'] ?? "";
		$params['left_ad']               = $atts['left_ad'] ?? "";
		$params['show_left_sec_ad_type'] = $atts['show_left_sec_ad_type'] ?? "";
		$params['show_left_ad']          = $atts['show_left_ad'] ?? "";
		$params['left_sec_ads_title']    = $atts['left_sec_ads_title'] ?? "";
		$params['section_title']    = $atts['section_title'] ?? "";
		$params['btn_link']    = $atts['btn_link'] ?? "";
		$params['btn_title']    = $atts['btn_title'] ?? "";
		$params['ad_title_limit_side']    = $atts['ad_title_limit_side'] ?? "";
		$params['ad_title_limit_main']    = $atts['ad_title_limit_main'] ?? "";

		if ( function_exists( 'recent_ads_shortcode' ) ) {
			echo recent_ads_shortcode( $params );
		}
	}
}