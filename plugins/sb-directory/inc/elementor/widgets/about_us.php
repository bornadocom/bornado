<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_about_us_event extends Widget_Base {

    public function get_name() {
        return 'about_us_event_short_base';
    }

    public function get_title() {
        return __('About us Event', 'sb_pro');
    }

    public function get_icon() {
        return 'fa fa-audio-description';
    }

    public function get_categories() {
        return ['adforest_elementor'];
    }

    protected function register_controls() {
        $this->start_controls_section(
                'basic', [
            'label' => esc_html__('Basic', 'adforest-elementor'),
                ]
        );
        $this->add_control(
                'section_tagline', [
            'label' => __('Section Tag Line', 'adforest-elementor'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section Title', 'adforest-elementor'),
                ]
        );
        $this->add_control(
                'section_title', [
            'label' => __('Section Title', 'adforest-elementor'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section Title', 'adforest-elementor'),
                ]
        );
        $this->add_control(
                'section_description', [
            'label' => __('Section Description', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'title' => '',
            'rows' => 3,
            'placeholder' => '',
                ]
        );

        $this->add_control(
                'link', [
            'label' => __('URL or Link', 'adforest-elementor'),
            'type' => Controls_Manager::TEXT,
            "description" => __("Youtube video link", 'adforest-elementor'),
            'default' => '',
                ]
        );
        $this->end_controls_section();
        $this->start_controls_section(
                'client_settings', [
            'label' => esc_html__('Client or Partners', 'adforest-elementor'),
                ]
        );
        $adforest_elementor_repetor = new \Elementor\Repeater();
        $adforest_elementor_repetor->add_control(
                'small_title', [
            'label' => __('Section Title', 'adforest-elementor'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section Title', 'adforest-elementor'),
                ]
        );
        $adforest_elementor_repetor->add_control(
                'small_desc', [
            'label' => __('Section Description', 'adforest-elementor'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section Title', 'adforest-elementor'),
                ]
        );
        $adforest_elementor_repetor->add_control(
                'icon',
                [
                    'label' => __('Icon', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::ICONS,
                    'description' => esc_html__('Please use reguler font', 'adforest-elementor'),
                    'default' => [
                        'value' => 'fas fa-star',
                        'library' => 'solid',
                    ],
                ]
        );
        $this->add_control(
                'clients', [
            'label' => __('Add Client', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $adforest_elementor_repetor->get_controls(),
            'default' => [],
                ]
        );
        $this->end_controls_section();
    }

    protected function render() {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_tagline'] = isset($atts['section_tagline']) ? $atts['section_tagline'] : "";
        $params['section_title'] = isset($atts['section_title']) ? $atts['section_title'] : "";
        $params['section_description'] = isset($atts['section_description']) ? $atts['section_description'] : "";
        $params['link'] = isset($atts['link']) ? $atts['link'] : "";
        $params['clients'] = isset($atts['clients']) ? $atts['clients'] : "";
        $params['adforest_elementor'] = true;

        $bg_img = SB_DIR_URL . '/images/service-main-img.png';

        extract($params);

        if (isset($adforest_elementor) && $adforest_elementor) {
            $rows = isset($atts['clients']) ? $atts['clients'] : array();
        } else {
            $rows = vc_param_group_parse_atts($atts['clients']);
            $rows = apply_filters('adforest_validate_term_type', $rows);
        }
        //$rows = vc_param_group_parse_atts($atts['clients']);
        $short_content = '';
        if (isset($rows) && is_array($rows) && count($rows) > 0) {
            foreach ($rows as $row) {
                if (isset($adforest_elementor) && $adforest_elementor) {
                    $icon = $row['icon']['value'];
                } else {
                    $icon = $row['icon'];
                }
                $short_content .= '<li>
                                <div class="services-box">
                                    <i class="' . $icon = $row['icon']['value'] . '"></i>
                                    <h4 class="title">' . $row['small_title'] . '</h4>
                                    <p class="txt">' . $row['small_desc'] . '</p>
                            </li>';
            }
        }
        $link = '<a id="play-video" class="video-play-button" href="' . $link . '">
                       <span></span>
                 </a>';
        echo '<section class="more-ad-listing-section">
        <div class="container">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-7 col-xl-7 col-xxl-7">
                    <div class="listing-cont-meta">
                        <small class="theme-title">' . $section_tagline . '</small>
                        <h2 class="theme-heading">' . $section_title . '</h2>
                        <p class="txt">' . $section_description . '</p>
                        <ul>
                            ' . $short_content . '
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-12 col-lg-5 col-xl-5 col-xxl-5">
                    <div class="service-img-box">
                        <img src="' . $bg_img . '" alt="service-main-img">
                            ' . $link . '
                    </div>
                </div>
            </div>
        </div>
    </section>';
    }

}
