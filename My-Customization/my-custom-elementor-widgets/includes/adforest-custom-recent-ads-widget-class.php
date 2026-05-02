<?php
/**
 * Elementor widget class for custom recent ads.
 *
 * @package My_Custom_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) || class_exists( 'MCEW_Custom_Recent_Ads_Widget' ) ) {
	return;
}

class MCEW_Custom_Recent_Ads_Widget extends \Elementor\Widget_Base {
	public function get_name() {
		return 'mcew_custom_recent_ads';
	}

	public function get_title() {
		return __( 'cudtom recent ads', 'my-custom-widgets' );
	}

	public function get_icon() {
		return 'fa fa-audio-description';
	}

	public function get_categories() {
		return array( 'adforest_widgets' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'basic',
			array(
				'label' => esc_html__( 'Basic', 'my-custom-widgets' ),
			)
		);

		$this->add_control(
			'main_sec_ad_type',
			array(
				'label'   => esc_html__( 'What type of ads do you want to show on the main section?', 'my-custom-widgets' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'recent'   => esc_html__( 'Recent', 'my-custom-widgets' ),
					'featured' => esc_html__( 'Featured', 'my-custom-widgets' ),
					'both'     => esc_html__( 'Both', 'my-custom-widgets' ),
				),
				'default' => 'recent',
			)
		);

		$this->add_control(
			'main_section_ppp',
			array(
				'label'       => esc_html__( 'Number of Ads to show in main section? (-1 = Unlimited)', 'my-custom-widgets' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => -1,
				'step'        => 1,
				'default'     => 5,
				'description' => esc_html__( 'Set to -1 for unlimited ads (useful with your infinity scroll mode).', 'my-custom-widgets' ),
			)
		);

		$this->add_control(
			'ad_title_limit_main',
			array(
				'label'   => __( 'Ad Title Limit Main', 'my-custom-widgets' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 20,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'right_ads',
			array(
				'label' => esc_html__( 'Right Side Ads Section', 'my-custom-widgets' ),
			)
		);

		$this->add_control(
			'show_right_ad_1',
			array(
				'label'   => esc_html__( 'Do you want to show Ad 1 on the right section?', 'my-custom-widgets' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'yes' => esc_html__( 'Yes', 'my-custom-widgets' ),
					'no'  => esc_html__( 'No', 'my-custom-widgets' ),
				),
				'default' => 'yes',
			)
		);

		$this->add_control(
			'advert_1',
			array(
				'label'       => __( 'Ad 1', 'my-custom-widgets' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'description' => __( '303x485', 'my-custom-widgets' ),
				'condition'   => array(
					'show_right_ad_1' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_right_ad_2',
			array(
				'label'   => esc_html__( 'Do you want to show Ad 2 on the right section?', 'my-custom-widgets' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'yes' => esc_html__( 'Yes', 'my-custom-widgets' ),
					'no'  => esc_html__( 'No', 'my-custom-widgets' ),
				),
				'default' => 'yes',
			)
		);

		$this->add_control(
			'advert_2',
			array(
				'label'       => __( 'Ad 2', 'my-custom-widgets' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'description' => __( '303x485', 'my-custom-widgets' ),
				'condition'   => array(
					'show_right_ad_2' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'left_section',
			array(
				'label' => esc_html__( 'Left Section Settings', 'my-custom-widgets' ),
			)
		);

		$this->add_control(
			'show_left_sec_ad_type',
			array(
				'label'       => __( 'Show Sidebar Ads?', 'my-custom-widgets' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => array(
					'yes' => esc_html__( 'Yes', 'my-custom-widgets' ),
					'no'  => esc_html__( 'No', 'my-custom-widgets' ),
				),
				'default'     => 'yes',
				'label_block' => true,
			)
		);

		$this->add_control(
			'left_sec_ads_title',
			array(
				'label'     => __( 'Ads Sidebar Title', 'my-custom-widgets' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'condition' => array(
					'show_left_sec_ad_type' => 'yes',
				),
			)
		);

		$this->add_control(
			'left_sec_ad_type',
			array(
				'label'     => esc_html__( 'What type of ads do you want to show on the left sidebar?', 'my-custom-widgets' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'recent'   => esc_html__( 'Recent', 'my-custom-widgets' ),
					'featured' => esc_html__( 'Featured', 'my-custom-widgets' ),
				),
				'default'   => 'featured',
				'condition' => array(
					'show_left_sec_ad_type' => 'yes',
				),
			)
		);

		$this->add_control(
			'left_section_ppp',
			array(
				'label'     => esc_html__( 'Number of Ads to show in Left Sidebar?', 'my-custom-widgets' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 10,
				'step'      => 1,
				'default'   => 5,
				'condition' => array(
					'show_left_sec_ad_type' => 'yes',
				),
			)
		);

		$this->add_control(
			'ad_title_limit_side',
			array(
				'label'   => __( 'Ad Title Limit Sidebar', 'my-custom-widgets' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 20,
			)
		);

		$this->add_control(
			'show_left_ad',
			array(
				'label'       => __( 'Show Advert in Sidebar.', 'my-custom-widgets' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => array(
					'yes' => esc_html__( 'Yes', 'my-custom-widgets' ),
					'no'  => esc_html__( 'No', 'my-custom-widgets' ),
				),
				'default'     => 'yes',
				'label_block' => true,
			)
		);

		$this->add_control(
			'left_ad',
			array(
				'label'       => __( 'Left Advert Image', 'my-custom-widgets' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'description' => __( '303x485', 'my-custom-widgets' ),
				'condition'   => array(
					'show_left_ad' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$atts   = $this->get_settings_for_display();
		$params = array(
			'adforest_elementor'    => true,
			'main_sec_ad_type'      => $atts['main_sec_ad_type'] ?? '',
			'main_section_ppp'      => $atts['main_section_ppp'] ?? '',
			'show_right_ad_1'       => $atts['show_right_ad_1'] ?? '',
			'show_right_ad_2'       => $atts['show_right_ad_2'] ?? '',
			'advert_1'              => $atts['advert_1'] ?? '',
			'advert_2'              => $atts['advert_2'] ?? '',
			'left_sec_ad_type'      => $atts['left_sec_ad_type'] ?? '',
			'left_section_ppp'      => $atts['left_section_ppp'] ?? '',
			'left_ad'               => $atts['left_ad'] ?? '',
			'show_left_sec_ad_type' => $atts['show_left_sec_ad_type'] ?? '',
			'show_left_ad'          => $atts['show_left_ad'] ?? '',
			'left_sec_ads_title'    => $atts['left_sec_ads_title'] ?? '',
			'ad_title_limit_side'   => $atts['ad_title_limit_side'] ?? '',
			'ad_title_limit_main'   => $atts['ad_title_limit_main'] ?? '',
		);

		mcew_custom_recent_ads_shortcode( $params );
	}
}
