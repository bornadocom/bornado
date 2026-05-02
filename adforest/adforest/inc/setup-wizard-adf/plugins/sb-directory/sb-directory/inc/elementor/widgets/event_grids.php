<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_event_grids extends Widget_Base {

    public function get_name() {
        return 'ads_short_base_grid';
    }

    public function get_title() {
        return __('Event Grids', 'sb_pro');
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
                'section_bg', array(
            'label' => __('Background', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                '' => __('White', 'sb_pro'),
                'gray' => __('Gray', 'sb_pro'),
                'img' => __('Image', 'sb_pro'),
            ),
                )
        );

        $this->add_control(
                'bg_img', array(
            'label' => __('Background Image', 'sb_pro'),
            'type' => Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src(),
            ],
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'section_bg',
                        'operator' => 'in',
                        'value' => [
                            'img',
                        ],
                    ],
                ],
            ],
                )
        );

        $this->add_control(
                'header_style', array(
            'label' => __('Header Style', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                '' => __('No Header', 'sb_pro'),
                'classic' => __('Classic', 'sb_pro'),
                'regular' => __('Regular', 'sb_pro'),
            ),
                )
        );

        $this->add_control(
                'section_title', [
            'label' => __('Section Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'description' => __('For color {color}warp text within this tag{/color}', 'sb_pro'),
            'title' => __('Section Title', 'sb_pro'),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'header_style',
                        'operator' => 'in',
                        'value' => [
                            'classic',
                        ],
                    ],
                ],
            ],
                ]
        );
        $this->add_control(
                'section_title_regular', [
            'label' => __('Section Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section Title', 'sb_pro'),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'header_style',
                        'operator' => 'in',
                        'value' => [
                            'regular',
                        ],
                    ],
                ],
            ],
                ]
        );

        $this->add_control(
                'section_description', [
            'label' => __('Section Description', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'title' => '',
            'rows' => 3,
            'placeholder' => '',
                ]
        );
        $this->add_control(
                'link_title', [
            'label' => __('Link Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Link Title', 'sb_pro'),
                ]
        );
        $this->add_control(
                'main_link', [
            'label' => __('Read More Link', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => '',
            'show_external' => true,
            'default' => [
                'url' => '',
                'is_external' => true,
                'nofollow' => true,
            ],
                ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
                'ads_settings', [
            'label' => esc_html__('Ads Settings', 'sb_pro'),
                ]
        );

        $this->add_control(
                'show_date', array(
            'label' => __('Show Date Filters', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                'yes' => __('Yes', 'sb_pro'),
                'no' => __('No', 'sb_pro'),
            ),
                )
        );

        $this->add_control(
                'ad_order', array(
            'label' => __('Order By', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                '' => __('Select Ads order', 'sb_pro'),
                'asc' => __('Oldest', 'sb_pro'),
                'desc' => __('Latest', 'sb_pro'),
                'rand' => __('Random', 'sb_pro'),
            ),
                )
        );

        $this->add_control(
                'layout_type', array(
            'label' => __('Layout Type', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                '1' => __('Grid Style 1', 'sb_pro'),
                '2' => __('Grid Style 2', 'sb_pro'),
                '3' => __('Grid Style 3', 'sb_pro'),
            ),
                )
        );
        
          $this->add_control(
                'col', array(
            'label' => __('No. of event in row', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                '4' => __('3', 'sb_pro'),
                '3' => __('4', 'sb_pro'),
            ),
                )
        );

        $this->add_control(
                'no_of_ads', array(
            'label' => __('Number fo Ads', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 10,
            'min' => 1,
            'max' => 500,
                )
        );

        $this->end_controls_section();

        $this->start_controls_section(
                'ad_categories', [
            'label' => esc_html__('Categories', 'sb_pro'),
                ]
        );

        $this->add_control(
                'cats', array(
            'label' => __('Select Category', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'default' => '',
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'l_event_cat', 'yes')
                )
        );
        $this->end_controls_section();
    }

    protected function render() {
        $ads_settings_fields = $this->get_settings_for_display();
        $adforest_render_params = array();
        // basic
        $adforest_render_params['adforest_elementor'] = true;
        $adforest_render_params['section_bg'] = isset($ads_settings_fields['section_bg']) ? $ads_settings_fields['section_bg'] : '';
        $adforest_render_params['bg_img'] = isset($ads_settings_fields['bg_img']['id']) ? $ads_settings_fields['bg_img']['id'] : '';
        $adforest_render_params['header_style'] = isset($ads_settings_fields['header_style']) ? $ads_settings_fields['header_style'] : '';
        $adforest_render_params['section_title'] = isset($ads_settings_fields['section_title']) ? $ads_settings_fields['section_title'] : '';
        $adforest_render_params['section_title_regular'] = isset($ads_settings_fields['section_title_regular']) ? $ads_settings_fields['section_title_regular'] : '';
        $adforest_render_params['section_description'] = isset($ads_settings_fields['section_description']) ? $ads_settings_fields['section_description'] : '';
        $adforest_render_params['main_link'] = isset($ads_settings_fields['main_link']) ? ($ads_settings_fields['main_link']) : '';
        $adforest_render_params['link_title'] = isset($ads_settings_fields['link_title']) ? ($ads_settings_fields['link_title']) : '';
        // ads settings
        $adforest_render_params['ad_type'] = isset($ads_settings_fields['ad_type']) ? $ads_settings_fields['ad_type'] : '';
        $adforest_render_params['ad_order'] = isset($ads_settings_fields['ad_order']) ? $ads_settings_fields['ad_order'] : '';
        $adforest_render_params['layout_type'] = isset($ads_settings_fields['layout_type']) ? $ads_settings_fields['layout_type'] : '';
        $adforest_render_params['no_of_ads'] = isset($ads_settings_fields['no_of_ads']) ? $ads_settings_fields['no_of_ads'] : '';
        //cats
        $adforest_render_params['cats'] = isset($ads_settings_fields['cats']) ? $ads_settings_fields['cats'] : '';
        $adforest_render_params['show_date'] = isset($ads_settings_fields['show_date']) ? $ads_settings_fields['show_date'] : '';
        $adforest_render_params['col'] = isset($ads_settings_fields['col']) ? $ads_settings_fields['col'] : 4;
        
       
        $atts = $adforest_render_params;
        require trailingslashit(SB_DIR_PATH) . "inc/elementor/event_layout.php";
        require trailingslashit(SB_DIR_PATH) . "inc/elementor/header_layout.php";
        $days_list = "";
        if ($show_date == "yes") {
            $days_list = '<ul class="date-query-list">  
                              <li><a href="javascript:void(0)" class="filter-date-event" data-id = "today"> ' . esc_html__('Today', 'sb_pro') . ' </a></li>
                              <li><a href="javascript:void(0)" class="filter-date-event" data-id = "week"> ' . esc_html__('This Week', 'sb_pro') . ' </a></li>
                              <li><a href="javascript:void(0)" class="filter-date-event" data-id = "month"> ' . esc_html__('This Month', 'sb_pro') . ' </a></li>
                              <li><a href="javascript:void(0)" class="filter-date-event" data-id = "year"> ' . esc_html__('This year', 'sb_pro') . ' </a></li>
                              <li><a href="javascript:void(0)" class="filter-date-event" data-id = "all"> ' . esc_html__('see All', 'sb_pro') . ' </a></li>               
                            </ul>';
        }

        $parallex = '';
        if ($section_bg == 'img') {
            $parallex = 'parallex';
        }
        $btnHTML = '';
        if (isset($adforest_elementor) && $adforest_elementor) {
            $btn_args = array(
                'btn_key' => $main_link,
                'adforest_elementor' => $adforest_elementor,
                'btn_class' => 'btn btn-theme text-center',
                'iconBefore' => '',
                'iconAfter' => '',
                'titleText' => $link_title,
            );
            $btnHTML = apply_filters('adforest_elementor_url_field', $btnHTML, $btn_args);
        } else {
            if ($main_link != "") {
                $aHTML = adforest_ThemeBtn($main_link, 'btn btn-theme text-center', false);
                if ($aHTML != "") {
                    $btnHTML = '<div class="row"><div class="col-12"><div class="text-center">' . $aHTML . '</div></div></div>';
                }
            }
        }
    

       //$col   =  $col == 3   ? 4 :  3;

        $type = '<input type ="hidden" id="layout_type" value="' . $layout_type . '">';
        $type  =  '<input type="hidden"  id="no_of_ads"   value ="'.$no_of_ads.'">';
        $coloumn   = '<input type="hidden"  id="grid_col"   value ="'.$col.'">';
        echo '<section class="custom-padding ads-grid-selector event-grids ' . $bg_color . '" ' . $style . '>'.$coloumn. '' . $type . '<div class="container">' . $header . ' ' . $days_list . ' ' . $html . '<div class="row"><div class="col-12 text-center"> '.$btnHTML . '</div></div></div></section>';
    }
}
