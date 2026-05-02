<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_cats_event extends Widget_Base {

    public function get_name() {
        return 'cats_event_short_base';
    }

    public function get_title() {
        return __('Event - Categories', 'sb_pro');
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
                'basic_settings', [
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
                'header_style',
                [
                    'label' => __('Header Style', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'multiple' => true,
                    'description' => __('Choose Header Style', 'sb_pro'),
                    'options' => [
                        '' => __('No Header', 'sb_pro'),
                        'classic' => __('Classic', 'sb_pro'),
                        'regular' => __('Regular', 'sb_pro'),
                        'new' => __('New', 'sb_pro')
                    ],
                ]
        );
        $this->add_control(
                'section_tagline', [
            'label' => __('Section Tagline', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'header_style',
                        'operator' => 'in',
                        'value' => [
                            'new'
                        ],
                    ],
                ],
            ],
                ]
        );

        $this->add_control(
                'section_title',
                [
                    'label' => __('Section Title', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    "description" => __('For color {color}warp text within this tag{/color}', 'sb_pro'),
                    'conditions' => [
                        'terms' => [
                            [
                                'name' => 'header_style',
                                'operator' => 'in',
                                'value' => [
                                    'classic',
                                    'new'
                                ],
                            ],
                        ],
                    ],
                ]
        );
        $this->add_control(
                'section_description',
                [
                    'label' => __('Section Description', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::TEXTAREA,
                    'conditions' => [
                        'terms' => [
                            [
                                'name' => 'header_style',
                                'operator' => 'in',
                                'value' => [
                                    'classic',
                                    'new'
                                ],
                            ],
                        ],
                    ],
                ]
        );
        $this->add_control(
                'section_title_regular',
                [
                    'label' => __('Section Title', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    "description" => __('For color {color}warp text within this tag{/color} d', 'sb_pro'),
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


       

        $this->end_controls_section();

        $this->start_controls_section(
                'cats_settings', [
            'label' => esc_html__('Categories', 'sb_pro'),
                ]
        );

        $adforest_elementor_repetor = new \Elementor\Repeater();

        $adforest_elementor_repetor->add_control(
                'cat', array(
            'label' => __('Select Category', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'default' => '',
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'l_event_cat')
                )
        );
        $adforest_elementor_repetor->add_control(
                'icon', [
            'label' => __('Icon', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::MEDIA,
                ]
        );

        $this->add_control(
                'cats_classic', [
            'label' => __('Add Category', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $adforest_elementor_repetor->get_controls(),
            'default' => [],
            'title_field' => '{{{cat}}}',
                ]
        );
        $this->end_controls_section();
    }

    protected function render() {

        $category_settings_fields = $this->get_settings_for_display();
        $adforest_render_params = array();
        // basic
        $adforest_render_params['adforest_elementor'] = TRUE;
        $adforest_render_params['cat_link_page'] = isset($category_settings_fields['cat_link_page']) ? $category_settings_fields['cat_link_page'] : '';
        $adforest_render_params['section_bg'] = isset($category_settings_fields['section_bg']) ? $category_settings_fields['section_bg'] : '';
        $adforest_render_params['bg_img'] = isset($category_settings_fields['bg_img']['id']) ? $category_settings_fields['bg_img']['id'] : '';
        $adforest_render_params['header_style'] = isset($category_settings_fields['header_style']) ? $category_settings_fields['header_style'] : '';
        $adforest_render_params['section_title'] = isset($category_settings_fields['section_title']) ? $category_settings_fields['section_title'] : '';
        $adforest_render_params['section_title_regular'] = isset($category_settings_fields['section_title_regular']) ? $category_settings_fields['section_title_regular'] : '';
        $adforest_render_params['section_description'] = isset($category_settings_fields['section_description']) ? $category_settings_fields['section_description'] : '';
        $adforest_render_params['sub_limit'] = isset($category_settings_fields['sub_limit']) ? $category_settings_fields['sub_limit'] : '';
        $adforest_render_params['cats'] = isset($category_settings_fields['cats_classic']) ? $category_settings_fields['cats_classic'] : '';

        $atts = $adforest_render_params;
        extract($atts);

        require trailingslashit(SB_DIR_PATH) . "inc/elementor/header_layout.php";
        $categories_html = '';
        if (isset($atts['cats'])) {
            if (isset($adforest_elementor) && $adforest_elementor) {
                $rows = $atts['cats'];
            } else {
                $rows = vc_param_group_parse_atts($atts['cats']);
                $rows = apply_filters('adforest_validate_term_type', $rows);
            }
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    if (isset($row['cat']) && isset($row['icon'])) {
                        $category = get_term($row['cat']);
                        if (count((array) $category) == 0)
                            continue;
                        $count = $category->count;
                        $icon_class = $row['icon'];
                        
                    $source   =  '';
                        if (isset($adforest_elementor) && $adforest_elementor) {
                            $source =  isset($row['icon']['url'])  ? $row['icon']['url']  : "" ;
                        }
                        $cat_link_page = get_term_link($category->term_id);
                        $categories_html .= '<div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl-2 col-xxl-2">
                    <div class="ctg-box">
                        <div class="img-box">
                            <img src="'.$source.'" alt="category-img">
                        </div>
                       <a href="' . $cat_link_page . '"><h4 class="title">' . $category->name . '</h4></a>
                        <div class="ads-box"><span>(' . $count . ' ' . __('Events', 'adforest') . ')</span></div>
                    </div>
                </div>';
                    }
                }
            }
        }

        echo '<section class="best-categories-section  custom-padding ' . $bg_color . '" ' . $style . '>
            <div class="container">
            ' . $header . '
               <div class="row">		   		
			  '.$categories_html.'
            </div>
         </section>';
    }

}
