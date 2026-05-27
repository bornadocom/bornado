<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class FeaturedAds extends Widget_Base {
	public function get_name() {
		return 'featured_ads';
	}

	public function get_title() {
		return __( 'Featured Ads', 'adforest-elementor' );
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
			'section_title', [
				'label'       => __( 'Section Title', 'adforest-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'title'       => __( 'Section Title', 'adforest-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'section_top_btn_text', [
				'label'   => __( 'Section Top Button Text', 'adforest-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'View All', 'adforest-elementor' ),
			]
		);

		$this->add_control(
			'section_top_btn_link', [
				'label'   => __( 'Section Top Button Link', 'adforest-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->add_control(
			'section_title_limit', [
				'label'   => __( 'Grid Title Limit', 'adforest-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '30',
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
			'section_ad_type', [
				'label'   => __( 'Ad Type', 'adforest-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'recent'   => esc_html__( 'Regular', 'adforest-elementor' ),
					'featured' => esc_html__( 'Featured', 'adforest-elementor' ),
					'all'      => esc_html__( 'All', 'adforest-elementor' ),
				],
				'default' => 'featured',
			]
		);

		$this->add_control(
			'number_of_feat_ads_to_show', [
				'label'   => __( 'No. of Featured Ads to Show', 'adforest-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 8,
			]
		);

		$this->add_control(
			'section_bottom_btn_text', [
				'label'   => __( 'Section Bottom Button Text', 'adforest-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'View All', 'adforest-elementor' ),
			]
		);

		$this->add_control(
			'section_bottom_btn_link', [
				'label'   => __( 'Section Bottom Button Link', 'adforest-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

        $this->add_control(
            'grid_columns', [
            'label' => esc_html__('Select No of Grid Columns', 'adforest-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '2' => esc_html__('2', 'adforest-elementor'),
                '3' => esc_html__('3', 'adforest-elementor'),
                '4' => esc_html__('4', 'adforest-elementor'),
            ],
            'default' => '4',
        ]);


		$this->add_control(
			'show_advert', [
			'label'       => __( 'Show Main Section Top Advert', 'adforest-elementor' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'yes' => esc_html__( "Yes", 'adforest-elementor' ),
				'no'  => esc_html__( "No", 'adforest-elementor' ),
			],
			'default'     => 'yes',
			'label_block' => true,
		] );

		$this->add_control(
			'advert_horizontal',
			[
				'label'       => __( 'Horizontal Advertisement image', 'adforest-elementor' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				"description" => __( "728x90", 'adforest-elementor' ),
				'condition'   => [
					'show_advert' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'left_section', [
			'label' => esc_html__( 'Left Section Settings', 'adforest-elementor' ),
		] );

		$this->add_control(
			'show_left_sec_ad_type', [
			'label'       => __( 'Show Ads in Left Sidebar.', 'adforest-elementor' ),
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
			'left_sec_title_limit', [
			'label'     => __( 'Ads Sidebar Title Limit', 'adforest-elementor' ),
			'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '20',
			'condition' => [
				'show_left_sec_ad_type' => 'yes',
			],
		] );
		$this->add_control(
			'left_sec_ad_type', [
			'label'     => esc_html__( 'What type of ads do you want to show on the sidebar?', 'adforest-elementor' ),
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
			'label'     => esc_html__( 'Number of Ads to show in Sidebar?', 'adforest-elementor' ),
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
		$atts                                 = $this->get_settings_for_display();
		$params                               = array();
		$params['adforest_elementor']         = true;
		$params['section_title']              = $atts['section_title'] ?? "";
		$params['section_ad_type']            = $atts['section_ad_type'] ?? "";
		$params['advert_horizontal']          = $atts['advert_horizontal'] ?? "";
		$params['show_advert']                = $atts['show_advert'] ?? "";
		$params['left_sec_ad_type']           = $atts['left_sec_ad_type'] ?? "";
		$params['left_section_ppp']           = $atts['left_section_ppp'] ?? "";
		$params['show_left_ad']               = $atts['show_left_ad'] ?? "";
		$params['left_ad']                    = $atts['left_ad'] ?? "";
		$params['number_of_feat_ads_to_show'] = $atts['number_of_feat_ads_to_show'] ?? "";
		$params['left_sec_ads_title']         = $atts['left_sec_ads_title'] ?? "";
		$params['show_left_sec_ad_type']      = $atts['show_left_sec_ad_type'] ?? "";
		$params['section_top_btn_text']       = $atts['section_top_btn_text'] ?? "";
		$params['section_top_btn_link']       = $atts['section_top_btn_link'] ?? "";
		$params['section_bottom_btn_text']    = $atts['section_bottom_btn_text'] ?? "";
		$params['section_bottom_btn_link']    = $atts['section_bottom_btn_link'] ?? "";
		$params['section_grid_style']         = $atts['section_grid_style'] ?? "";
		$params['section_title_limit']         = $atts['section_title_limit'] ?? "";
		$params['left_sec_title_limit']         = $atts['left_sec_title_limit'] ?? "";
		$params['grid_columns']         = $atts['grid_columns'] ?? "";

		if ( function_exists( 'featured_ads_shortcode' ) ) {
			echo featured_ads_shortcode( $params );
		}

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-property-img-carousel').owlCarousel({
                        loop: false,
                        rtl: is_rtl,
                        margin: 0,
                        nav: true,
                        dots: false,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        responsive: {
                            0: {
                                items: 1
                            },
                            600: {
                                items: 1
                            },
                            1000: {
                                items: 1
                            }
                        }
                    });
                });
            </script>
		<?php }
	}
}