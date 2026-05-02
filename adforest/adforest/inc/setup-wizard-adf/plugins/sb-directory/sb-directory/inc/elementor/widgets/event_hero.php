<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_event_hero extends Widget_Base {

    public function get_name() {
        return 'adforest_event_hero';
    }

    public function get_title() {
        return __('Event Hero', 'sb_pro');
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
            'label' => esc_html__('Basic', 'sb_pro'),
                ]
        );

        $this->add_control(
                'section_title', [
            'label' => __('Section Title', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section Title', 'sb_pro'),
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
        $this->end_controls_section();
        $this->start_controls_section(
                'screen', [
            'label' => esc_html__('Search Settings', 'sb_pro'),
                ]
        );

        $this->add_control(
                'keyword_label', [
            'label' => __('Search Keyword Label', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'title' => __('Section Title', 'sb_pro'),
                ]
        );
        $this->add_control(
                'keyword_placeholder', [
            'label' => __('Search Keyword Placeholder', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'title' => __('Search Keyword Placeholder', 'sb_pro'),
            'default' => __('What are you looking for.....', 'sb_pro'),
                ]
        );

        $this->add_control(
                'cat_label', [
            'label' => __('Search Category Label', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Category', 'sb_pro'),
                ]
        );

        $this->end_controls_section();
        $this->start_controls_section(
                'tags', [
            'label' => esc_html__('Tags', 'sb_pro'),
                ]
        );
        $this->add_control(
                'tag_heading', [
            'label' => __('Tags Heading', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Trending Keywords', 'sb_pro'),
                ]
        );

        $this->add_control(
                'is_display_tags', array(
            'label' => __('Display tags ?', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                '1' => __('Yes', 'sb_pro'),
                '0' => __('No', 'sb_pro'),
            ),
                )
        );

        $this->add_control(
                'max_tags_limit', array(
            'label' => __('Max number of tags', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 5,
            'min' => 1,
            'max' => 500,
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'is_display_tags',
                        'operator' => 'in',
                        'value' => [
                            '1',
                        ],
                    ],
                ],
            ],
                )
        );
        $this->end_controls_section();
        $this->start_controls_section(
                'clocation', [
            'label' => esc_html__('Custom Locations', 'sb_pro'),
                ]
        );
        $this->add_control(
                'location_label', [
            'label' => __('Search Location Label', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Search Locaton', 'sb_pro'),
                ]
        );
        $this->add_control(
                'location_type', array(
            'label' => __('Location type', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'description' => __('Chose header style.', 'sb_pro'),
            'options' => array(
                'g_locations' => __('Google', 'sb_pro'),
                'custom_locations' => __('Custom Location', 'sb_pro'),
            ),
                )
        );

        $this->add_control(
                'locations', array(
            'label' => __('Location', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'separator' => 'before',
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'event_loc', 'yes'),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'location_type',
                        'operator' => '=',
                        'value' => 'custom_locations',
                    ],
                ],
            ],
                )
        );
        $this->end_controls_section();
        $this->start_controls_section(
                'categories_slider', [
            'label' => esc_html__('Category List', 'sb_pro'),
                ]
        );

        $this->add_control(
                'categories_list', array(
            'label' => __('Categories', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'separator' => 'before',
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'l_event_cat', 'yes'),
                )
        );

        $this->end_controls_section();

        $this->start_controls_section(
                'categories', [
            'label' => esc_html__('Category slider', 'sb_pro'),
                ]
        );
         $this->add_control(
                'slider_tagline', [
            'label' => __('Slider tagline', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Explore Event', 'sb_pro'),
                ]
        );
        $this->add_control(
                'slider_heading', [
            'label' => __('Slider Heading', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Browse Categories', 'sb_pro'),
                ]
        );
        $this->add_control(
                'cat_link_page',
                [
                    'label' => __('Category link Page', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    //'multiple' => true,
                    'options' => [
                        'search' => __('Search Page', 'sb_pro'),
                        'category' => __('Category Page', 'sb_pro'),
                    ],
                ]
        );
        $this->add_control(
                'cats', array(
            'label' => __('Categories', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'l_event_cat', 'no', false),
                )
        );

        $this->end_controls_section();
    }

    protected function render() {

        global $adforest_theme;
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_bg'] = isset($atts['section_bg']) ? $atts['section_bg'] : "";
        $params['section_tagline'] = isset($atts['section_tagline']) ? $atts['section_tagline'] : "";
        $params['section_title'] = isset($atts['section_title']) ? $atts['section_title'] : "";
        $params['section_description'] = isset($atts['section_description']) ? $atts['section_description'] : "";
        $params['keyword_label'] = isset($atts['keyword_label']) ? $atts['keyword_label'] : "";
        $params['keyword_placeholder'] = isset($atts['keyword_placeholder']) ? $atts['keyword_placeholder'] : "";
        $params['location_label'] = isset($atts['location_label']) ? $atts['location_label'] : "";
        $params['location_placeholder'] = isset($atts['location_placeholder']) ? $atts['location_placeholder'] : "";
        $params['signature_img'] = isset($atts['signature_img']) ? $atts['signature_img'] : "";
        $params['location_type'] = isset($atts['location_type']) ? $atts['location_type'] : "";
        $params['locations'] = isset($atts['locations']) ? $atts['locations'] : "";
        $params['cat_link_page'] = isset($atts['cat_link_page']) ? $atts['cat_link_page'] : "";
        $params['cats'] = isset($atts['cats']) ? $atts['cats'] : "";
        $params['categories_list'] = isset($atts['categories_list']) ? $atts['categories_list'] : "";
        $params['cat_label'] = isset($atts['cat_label']) ? $atts['cat_label'] : "";
        $is_display_tags = isset($atts['is_display_tags']) ? $atts['is_display_tags'] : false;
        $max_tags_limit = isset($atts['max_tags_limit']) ? $atts['max_tags_limit'] : 5;
        $tag_heading = isset($atts['tag_heading']) ? $atts['tag_heading'] : esc_html__('Trending Keywords', 'sb_pro');
        $slider_heading = isset($atts['slider_heading']) ? $atts['slider_heading'] : "";
         $slider_tagline = isset($atts['slider_tagline']) ? $atts['slider_tagline'] : "";


        extract($params);

        $args = array('hide_empty' => 0);
        $args = apply_filters('adforest_wpml_show_all_posts', $args); // for all lang texonomies
        $final_loc_html = '';
        $categories_list_html = '';
        $loc_flag = FALSE;
        $rows = isset($categories_list) && $categories_list != '' ? $categories_list : array();
        if (is_array($rows) && !empty($rows)) {
            $categories_list_html .=  '<option label="' . __('Select Category', 'adforest') . '" value="">' . __('Select Category', 'adforest') . '</option>';
            foreach ($rows as $row) {

                $loc_id = $row;
                if ($loc_id == "all") {
                    $loc_flag = TRUE;
                    $categories_list_html = "";
                    break;
                }
                if (isset($loc_id) && $loc_id != "") {
                    $term = get_term($loc_id, 'l_event_cat');
                    $categories_list_html .= ' <option value="' . $loc_id . '">' . $term->name . '</option> ';
                }
            }
        }

        if ($loc_flag) {
            $categories_list_html .=  '<option label="' . __('Select Category', 'adforest') . '" value="">' . __('Select Category', 'adforest') . '</option>';
            if (isset($adforest_theme['display_taxonomies']) && $adforest_theme['display_taxonomies'] == 'hierarchical') {
                $args = array(
                    'type' => 'html',
                    'taxonomy' => 'l_event_cat',
                    'tag' => 'option',
                    'parent_id' => 0,
                );
                $categories_list_html = apply_filters('adforest_tax_hierarchy', $categories_list_html, $args);
            } else {

                $ad_country_arr = get_terms('l_event_cat', $args);
                if (isset($ad_country_arr) && count($ad_country_arr) > 0) {
                    foreach ($ad_country_arr as $loc_value) {
                        $categories_list_html .= ' <option value="' . intval($loc_value->term_id) . '">' . esc_html($loc_value->name) . ' </option> ';
                    }
                }
            }
        }




        if (isset($location_type) && $location_type == 'custom_locations') {
            $args = array('hide_empty' => 0);
            $args = apply_filters('adforest_wpml_show_all_posts', $args); // for all lang texonomies
            $final_loc_html = '';
            $locations_html = '';
            $loc_flag = FALSE;
            $rows = isset($locations) && $locations != '' ? $locations : array();
            if (isset($adforest_elementor) && $adforest_elementor) {
                $rows = ($locations);
            } else {
                $rows = vc_param_group_parse_atts($atts['locations']);
                $rows = apply_filters('adforest_validate_term_type', $rows);
            }
            if (is_array($rows) && !empty($rows)) {
                $locations_html .= '';
                foreach ($rows as $row) {

                    if (isset($adforest_elementor) && $adforest_elementor) {
                        $loc_id = $row;
                    } else {
                        $loc_id = isset($row['location']) ? $row['location'] : "";
                    }
                    if ($loc_id == "all") {
                        $loc_flag = TRUE;
                        break;
                    }
                    if (isset($loc_id) && $loc_id != "") {
                        $term = get_term($loc_id, 'ad_country');
                        $locations_html .= ' <option value="' . $loc_id . '">' . $term->name . '</option> ';
                    }
                }
            }
            if ($loc_flag) {
                $locations_html .= ' <option value="">' . esc_html__('Select location', 'adforest') . ' </option> ';
                if (isset($adforest_theme['display_taxonomies']) && $adforest_theme['display_taxonomies'] == 'hierarchical') {
                    $args = array(
                        'type' => 'html',
                        'taxonomy' => 'event_loc',
                        'tag' => 'option',
                        'parent_id' => 0,
                    );
                    $locations_html = apply_filters('adforest_tax_hierarchy', $locations_html, $args);
                } else {
                    $ad_country_arr = get_terms('event_loc', $args);
                    if (isset($ad_country_arr) && count($ad_country_arr) > 0) {
                        foreach ($ad_country_arr as $loc_value) {
                            $locations_html .= ' <option value="' . intval($loc_value->term_id) . '">' . esc_html($loc_value->name) . ' </option> ';
                        }
                    }
                }
            }
        }
        
         $contries_script = '';
        if (isset($location_type) && $location_type == 'custom_locations') {
            $final_loc_html .= '<select class="js-example-basic-single" name="country_id" data-placeholder="' . __('Select Location', 'adforest') . '">';
            $final_loc_html .= '<option label="' . __('Select Location', 'adforest') . '" value="">' . __('Select Location', 'adforest') . '</option>';
            $final_loc_html .= $locations_html;
            $final_loc_html .= '</select>';
        } else {
            ob_start();
            adforest_load_search_countries();
            $contries_script = ob_get_contents();
            ob_end_clean();
            wp_enqueue_script('google-map-callback');
            $final_loc_html = '<input class="form-control" name="by_location"  id="sb_user_address" placeholder="' . $location_placeholder . '"  type="text">';
        }
        $html = '';
        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        if (isset($adforest_elementor) && $adforest_elementor) {
            $cats_arr = $cats;
        } else {
            $cats_arr = vc_param_group_parse_atts($atts['cats']);
            $cats_arr = apply_filters('adforest_validate_term_type', $cats_arr);
        }
        $cats_html = "";
        $cat_link_page = isset($cat_link_page) ? $cat_link_page : "";
        foreach ($cats_arr as $cat_id) {
            $cat_id = $cat_id;
            if (isset($adforest_elementor) && $adforest_elementor) {
                $cat_id = $cat_id;
            } else {
                $cat_id = $cat_id['cat'];
            }
            if (isset($cat_id)) {
                $term = get_term($cat_id, 'l_event_cat');
                if (isset($term->term_id)) {
                    $term_meta = get_option("taxonomy_term_$term->term_id");
                    $term_meta = isset($term_meta['ad_cat_icon']) ? $term_meta['ad_cat_icon'] : "";
                    $cats_html .= ' 
                                      <div class="item">
                                <div class="ctg-box">
                                     <i class="' . $term_meta . '"></i>
                                    <p class="txt"><a href="' . adforest_cat_link_page($term->term_id, $cat_link_page, 'cat_id') . '">' . $term->name . '</a></p>
                                    <small> ' . $term->count . esc_html__('  Ads', 'adforest') . '</small>
                                </div>
                            </div>';
                }
            }
        }
        $tags = '';
        if ($is_display_tags == 1) {
            $tags .= '<div class="keyword-box"><h6>' . esc_html($tag_heading) . ' </h6>';
            $args = array(
                'smallest' => 12,
                'largest' => 12,
                'unit' => 'px',
                'number' => 5,
                'format' => 'list',
                'separator' => "\n",
                'orderby' => 'name',
                'order' => 'ASC',
                'link' => 'view',
                'taxonomy' => 'event_tags',
                'echo' => false,
            );
            $tags .= wp_tag_cloud($args);
            $tags .= '</div>';
        }

        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_pro_event_page']);
        echo ''.$contries_script.' <div class="ad-listing-hero-main">
        <div class="ad-listing-hero">
            <div class="cont-box">
                <h1 class="main-heading">' . $section_title . '</h1>
                <p class="txt">' . $section_description . '</p>
                <div class="search-bar-box">
                   <form  action="' . urldecode(get_the_permalink($sb_search_page)) . '">
                    <div class="srh-bar">       
                        <div class="input-srh">
                            <input type="text" placeholder="' . $keyword_placeholder . '" name ="by_title">
                            <span>' . $keyword_label . '</span>
                        </div>
                        <div class="ctg-srh">
                            <select class="default-select post-type-change" name="event_cat" data-placeholder="' . __('Select Category', 'adforest') . '">
                                   ' . $categories_list_html . '
                            </select>
                            <span class="title">' . $cat_label . '</span>
                        </div>
                        <div class="loct-srh">
                           ' . $final_loc_html . '
                            <span class="title">' . $location_label . '</span>
                        </div>
                    
                  
                        </div>
                          <button class="btn btn-theme srh-btn">' . esc_html__('Search', 'sb_pro') . '</button>
                        </form>
                </div>
                      ' . $tags . '                    
            </div>
        </div>
        <div class="ctg-ads-carousel">
            <div class="container">
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4 col-xxl-4">
                        <div class="ctg-title">
                           <span>' . $slider_tagline . '</span>
                            <h3>' . $slider_heading . '</h3>
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8">
                        <div class="owl-carousel ad-category-carousel">                     
                           ' . $cats_html . '
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
        
         if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <script>
                jQuery('select').select2();
            </script>
            <?php

        }
    }

}
