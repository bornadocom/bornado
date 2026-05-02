<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_process_cycle_event extends Widget_Base {

    public function get_name() {
        return 'process_cycle_short_base_event';
    }

    public function get_title() {
        return __('Process Cycle event', 'sb_pro');
    }

    public function get_icon() {
        return 'fa fa-audio-description';
    }

    public function get_categories() {
        return ['adforest_elementor'];
    }

    protected function register_controls() {

        // basic

        $this->start_controls_section(
                'basic', [
            'label' => esc_html__('Basic', 'sb_pro'),
                ]
        );

    

        $this->add_control(
                'section_tagline', [
            'label' => __('Section Tagline', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
                ]
        );

        $this->add_control(
                'section_title', [
            'label' => __('Section Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'description' => __('For color {color}warp text within this tag{/color}', 'sb_pro'),
            'title' => __('Section Title', 'sb_pro'),
                ]
        );

       
        $this->end_controls_section();

        $this->start_controls_section(
                'step_1_settings', [
            'label' => esc_html__('Step 1', 'sb_pro'),
                ]
        );
        
          $this->add_control(
                's1_icon',
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
                's1_title', [
            'label' => __('Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Title', 'sb_pro'),
                ]
        );

        $this->add_control(
                's1_description', [
            'label' => __('Description', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'title' => '',
            'rows' => 5,
            'placeholder' => '',
                ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
                'step_2_settings', [
            'label' => esc_html__('Step 2', 'sb_pro'),
                ]
        );
       
        
         $this->add_control(
                's2_icon',
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
                's2_title', [
            'label' => __('Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Title', 'sb_pro'),
                ]
        );

        $this->add_control(
                's2_description', [
            'label' => __('Description', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'title' => '',
            'rows' => 5,
            'placeholder' => '',
                ]
        );
        $this->end_controls_section();
        $this->start_controls_section(
                'step_3_settings', [
            'label' => esc_html__('Step 3', 'sb_pro'),
                ]
        );
       
         $this->add_control(
                's3_icon',
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
                's3_title', [
            'label' => __('Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Title', 'sb_pro'),
                ]
        );
        $this->add_control(
                's3_description', [
            'label' => __('Description', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'title' => '',
            'rows' => 5,
            'placeholder' => '',
                ]
        );
        $this->end_controls_section();
    }

    protected function render() {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_bg'] = isset($atts['section_bg']) ? $atts['section_bg'] : "";
        $params['section_tagline'] = isset($atts['section_tagline']) ? $atts['section_tagline'] : "";
        $params['section_title'] = isset($atts['section_title']) ? $atts['section_title'] : "";
        $params['s1_title'] = isset($atts['s3_title']) ? $atts['s3_title'] : "";
        $params['s1_icon'] = isset($atts['s1_icon']) ? $atts['s1_icon'] : "";
        $params['s1_title'] = isset($atts['s1_title']) ? $atts['s1_title'] : "";
        $params['s2_title'] = isset($atts['s2_title']) ? $atts['s2_title'] : "";
        $params['s2_icon'] = isset($atts['s2_icon']) ? $atts['s2_icon'] : "";
        $params['s2_title'] = isset($atts['s2_title']) ? $atts['s2_title'] : "";
        $params['s3_icon'] = isset($atts['s3_icon']) ? $atts['s3_icon'] : "";
        $params['s3_title'] = isset($atts['s3_title']) ? $atts['s3_title'] : "";
        $params['s3_description'] = isset($atts['s3_description']) ? $atts['s3_description'] : "";
        $params['s2_description'] = isset($atts['s3_description']) ? $atts['s3_description'] : "";
        $params['s1_description'] = isset($atts['s3_description']) ? $atts['s3_description'] : "";

        extract($params);

        $s1_icon = isset($s1_icon) ? $s1_icon : "";
        $s2_icon = isset($s2_icon) ? $s2_icon : "";
        $s3_icon = isset($s3_icon) ? $s3_icon : "";

        $s1_icon = $s1_icon;
        if (isset($adforest_elementor) && $adforest_elementor) {
            $s1_icon = isset($s1_icon) ? $s1_icon['value'] : "";
        }

        $s2_icon = $s2_icon;
        if (isset($adforest_elementor) && $adforest_elementor) {
            $s2_icon = isset($s2_icon) ? $s2_icon['value'] : "";
        }

        $s3_icon = $s3_icon;
        if (isset($adforest_elementor) && $adforest_elementor) {
            $s3_icon = isset($s3_icon) ? $s3_icon['value'] : "";
        }

        echo '<section class="ad-how-it-works">
        <div class="container">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 col-xxl-12">
                    <div class="heading-meta">
                        <small class="theme-title">'.$section_tagline.'</small>
                        <h2 class="theme-heading">'.$section_title.'</h2>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                    <div class="prs-dtl-box">
                        <div class="icon">
                       <i class="' . esc_attr($s1_icon) . '"></i>
                            <span class="badge">01</span>
                        </div>
                        <h3 class="title">'.$s1_title.'</h3>
                       <p class="txt">'.$s1_description.'</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                    <div class="prs-dtl-box">
                        <div class="icon">
                            <i class="' . esc_attr($s2_icon) . '"></i>
                            <span class="badge">02</span>
                        </div>
                        <h3 class="title">'.$s2_title.'</h3>
                        <p class="txt">'.$s2_description.'</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                    <div class="prs-dtl-box">
                        <div class="icon">
                              <i class="' . esc_attr($s1_icon) . '"></i>
                            <span class="badge">03</span>
                        </div>
                        <h3 class="title">'.$s3_title.'</h3>
                        <p class="txt">'.$s3_description.'</p>
                    </div>
                </div>
            </div>
        </div>
    </section>';
    }

}
