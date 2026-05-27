<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_event_hero_2 extends Widget_Base {

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        wp_register_script('buyent-swiper', SB_DIR_URL . 'assets/js/swiper-bundle.min.js', ['jquery'], false, true);
        wp_register_script('buyent-custom', SB_DIR_URL . 'assets/js/sb-pro-custom.js', ['jquery'], false, true);
    }

    public function get_script_depends() {
        return ['buyent-swiper', 'buyent-custom'];
    }

    public function get_name() {
        return 'adforest_event_hero_2';
    }

    public function get_title() {
        return __('Event Hero 2', 'sb_pro');
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
                'section_tagline', [
            'label' => __('Section Tagline', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section tagline', 'sb_pro'),
            'label_block' => true,
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
                'form_heading', [
            'label' => __('Form Heading', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'title' => '',
            'rows' => 3,
            'placeholder' => '',
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
                'location_placeholder', [
            'label' => __('Location Placeholer', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Enter Location', 'sb_pro'),
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
                'post_type', array(
            'label' => __('post type', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'description' => __('Select Post type ', 'sb_pro'),
            'options' => array(
                'event' => __('Events', 'sb_pro'),
                'ads' => __('Listings', 'sb_pro'),
            ),
            'default' => 'event',
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
                    [
                        'name' => 'post_type',
                        'operator' => '=',
                        'value' => 'event',
                    ],
                ],
            ],
                )
        );
        $this->add_control(
                'ads_locations', array(
            'label' => __('Location', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'separator' => 'before',
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'ad_country', 'yes'),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'location_type',
                        'operator' => '=',
                        'value' => 'custom_locations',
                    ],
                    [
                        'name' => 'post_type',
                        'operator' => '=',
                        'value' => 'ads',
                    ],
                ],
            ],
                )
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
                'cats_search', array(
            'label' => __('Categories', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'l_event_cat', 'yes', false),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'post_type',
                        'operator' => '=',
                        'value' => 'event',
                    ],
                ],
            ],
                )
        );

        $this->add_control(
                'ads_cat_search', array(
            'label' => __('Categories', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'ad_cats', 'yes', false),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'post_type',
                        'operator' => '=',
                        'value' => 'ads',
                    ],
                ],
            ],
                )
        );

        $this->end_controls_section();
        $this->start_controls_section(
                'ads_settings', [
            'label' => esc_html__('Ads Settings', 'sb_pro'),
                ]
        );

        $this->add_control(
                'ad_heading', array(
            'label' => __('Event Section Heading', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
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
                'ad_type', array(
            'label' => __('Ad type', 'sb_pro'),
            'type' => Controls_Manager::SELECT,
            'options' => array(
                '' => __('Select Ads order', 'sb_pro'),
                'feature' => __('Feature', 'sb_pro'),
                'regular' => __('Regular', 'sb_pro'),
                'both' => __('both', 'sb_pro'),
            ),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'post_type',
                        'operator' => '=',
                        'value' => 'ads',
                    ],
                ],
            ],
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

        $this->add_control(
                'cats', array(
            'label' => __('Select Category For Events', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'default' => '',
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'l_event_cat', 'yes'),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'post_type',
                        'operator' => '=',
                        'value' => 'event',
                    ],
                ],
            ],
                )
        );

        $this->add_control(
                'ad_cats', array(
            'label' => __('Select Category For ads', 'sb_pro'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'default' => '',
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'ad_cats', 'yes'),
            'conditions' => [
                'terms' => [
                    [
                        'name' => 'post_type',
                        'operator' => '=',
                        'value' => 'ads',
                    ],
                ],
            ],
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
        $params['form_heading'] = isset($atts['form_heading']) ? $atts['form_heading'] : "";
        $params['keyword_placeholder'] = isset($atts['keyword_placeholder']) ? $atts['keyword_placeholder'] : "";
        $params['location_placeholder'] = isset($atts['location_placeholder']) ? $atts['location_placeholder'] : "";
        $params['location_type'] = isset($atts['location_type']) ? $atts['location_type'] : "";
        $params['locations'] = isset($atts['locations']) ? $atts['locations'] : "";
        $params['ads_locations'] = isset($atts['ads_locations']) ? $atts['ads_locations'] : "";
        $params['cat_link_page'] = isset($atts['cat_link_page']) ? $atts['cat_link_page'] : "";
        $params['cats'] = isset($atts['cats']) ? $atts['cats'] : "";
        $params['cats_search'] = isset($atts['cats_search']) ? $atts['cats_search'] : "";
        $params['ads_cat_search'] = isset($atts['ads_cat_search']) ? $atts['ads_cat_search'] : "";
        $params['ad_type'] = isset($atts['ad_type']) ? $atts['ad_type'] : 'both';
        $params['ad_order'] = isset($atts['ad_order']) ? $atts['ad_order'] : '';
        $params['layout_type'] = isset($atts['layout_type']) ? $atts['layout_type'] : '4';
        $params['no_of_ads'] = isset($atts['no_of_ads']) ? $atts['no_of_ads'] : '';
        $params['cats'] = isset($atts['cats']) ? $atts['cats'] : '';
        $params['ad_cats'] = isset($atts['ad_cats']) ? $atts['ad_cats'] : '';
        $params['ad_heading'] = isset($atts['ad_heading']) ? $atts['ad_heading'] : '';
        $params['post_type'] = isset($atts['post_type']) ? $atts['post_type'] : '';

        $is_display_tags = isset($atts['is_display_tags']) ? $atts['is_display_tags'] : false;
        $max_tags_limit = isset($atts['max_tags_limit']) ? $atts['max_tags_limit'] : 5;
        $tag_heading = isset($atts['tag_heading']) ? $atts['tag_heading'] : esc_html__('Trending Keywords', 'sb_pro');
        $slider_heading = isset($atts['slider_heading']) ? $atts['slider_heading'] : "";
        $slider_tagline = isset($atts['slider_tagline']) ? $atts['slider_tagline'] : "";
        wp_enqueue_style('swiper-custom', SB_DIR_URL . 'assets/css/swiper.min.css');
        $atts = $params;
        extract($atts);

        $current_post = isset($atts['post_type']) ? $atts['post_type'] : 'event';

        if ($current_post == 'event') { /* if post type in event then this code will be run */

            $args = array('hide_empty' => 0);
            $args = apply_filters('adforest_wpml_show_all_posts', $args); // for all lang texonomies
            $final_loc_html = '';
            $categories_list_html = '';
            $loc_flag = FALSE;
            $rows = isset($cats_search) && $cats_search != '' ? $cats_search : array();
            if (is_array($rows) && !empty($rows)) {
                $categories_list_html .= '<option label="' . __('Select Category', 'adforest') . '" value="">' . __('Select Category', 'adforest') . '</option>';
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
                $categories_list_html .= '<option label="' . __('Select Category', 'adforest') . '" value="">' . __('Select Category', 'adforest') . '</option>';
                $args = array(
                    'type' => 'html',
                    'taxonomy' => 'l_event_cat',
                    'tag' => 'option',
                    'parent_id' => 0,
                );
                $categories_list_html = apply_filters('adforest_tax_hierarchy', $categories_list_html, $args);
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
                            $term = get_term($loc_id, 'event_loc');
                            $locations_html .= ' <option value="' . $loc_id . '">' . $term->name . '</option> ';
                        }
                    }
                }
                if ($loc_flag) {
                    $locations_html .= ' <option value="">' . esc_html__('Select location', 'adforest') . ' </option> ';
                    $args = array(
                        'type' => 'html',
                        'taxonomy' => 'event_loc',
                        'tag' => 'option',
                        'parent_id' => 0,
                    );
                    $locations_html = apply_filters('adforest_tax_hierarchy', $locations_html, $args);
                }
            }
            $contries_script = '';
            if (isset($location_type) && $location_type == 'custom_locations') {
                $final_loc_html .= '<select class="js-example-basic-single post-type-change" name="by_location" data-placeholder="' . __('Select Location', 'adforest') . '">';
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

          
            $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_pro_event_page']);
            $bg_img1 = SB_DIR_URL . 'images/vector-img-2.png';
            $bg_img2 = SB_DIR_URL . 'images/vector-img.png';
            $zigzag = SB_DIR_URL . 'images/zig-zag-img.png';

            /* ads section */
            $rows = array();
            if (isset($adforest_elementor) && $adforest_elementor) {
                $rows = isset($atts['cats']) ? $atts['cats'] : "";
            } else {
                if (isset($atts['cats']) && $atts['cats'] != '') {
                    $rows = vc_param_group_parse_atts($atts['cats']);
                    $rows = apply_filters('adforest_validate_term_type', $rows);
                }
            }
            if (isset($rows) && $rows != '' && is_array($rows) && count($rows) > 0) {
                foreach ($rows as $row) {
                    if (isset($adforest_elementor) && $adforest_elementor) {
                        $cat_id = $row;
                    } else {
                        $cat_id = $row['cat'];
                    }
                    if ($cat_id != 'all') {
                        $cats[] = $cat_id;
                    }
                }
            }
            $is_all = false;
            $category = '';
            if (isset($cats) && !empty($cats) && count($cats) > 0) {
                $category = array('taxonomy' => 'l_event_cat', 'field' => 'term_id', 'terms' => $cats);
            }
            $ordering = 'DESC';
            $order_by = 'date';
            if ($ad_order == 'asc') {
                $ordering = 'ASC';
            } else if ($ad_order == 'desc') {
                $ordering = 'DESC';
            } else if ($ad_order == 'rand') {
                $order_by = 'rand';
            }
            $args = array(
                'post_type' => 'events',
                'post_status' => 'publish',
                'posts_per_page' => $no_of_ads,
                'orderby' => $order_by,
                'order' => $ordering,
                    // 'meta_query' => array($is_feature, $is_active,),
            );

            if ($category != '') {
                $args['tax_query'] = array($category);
            }
            $args = apply_filters('adforest_wpml_show_all_posts', $args);
            $html = '';
            global $adforest_theme;
            $out = "";
            $results = new \WP_Query($args);
            if ($results->have_posts()) {
                while ($results->have_posts()) {
                    $results->the_post();
                    $pid = get_the_ID();
                    $fun = "get_event_grid_type_4";
                    $html .= $fun(get_the_ID(), 4);
                }
            }



            echo $contries_script . ' <section class="buyent-new-hero buyent-ads-hero">
        <div class="container">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-5 col-xl-5 col-xxl-5"> 
           <form  action="' . urldecode(get_the_permalink($sb_search_page)) . '">
                    <div class="main-content">
                        <span class="title">' . $section_tagline . '</span>
                        <h1 class="main-heading">' . $section_title . '</h1>
                        <p class="txt">' . $section_description . '</p>
                        <div class="main-search-box form-group">
                            <h6 class="title">' . $form_heading . '</h6>
                            <div class="input-main">
                                  <input type="text" placeholder="' . $keyword_placeholder . '" name ="by_title">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="srh-main">
                                <div class="srh-sub">
                                   <select class="default-select post-type-change" name="event_cat" data-placeholder="' . __('Select Category', 'adforest') . '">
                                   ' . $categories_list_html . '
                                 </select>
                                </div>
                                <div class="srh-sub">
                                ' . $final_loc_html . '
                                </div>
                            </div>
                            <div class="botm-btn">
                                <button type="submit" class="btn btn-theme btn-block srh-btn"><i class="fa fa-magnifying-glass"></i>Search Now</a>
                            </div>
                        </div>
                        <img class="zig-zag-img" src="' . $zigzag . '" alt="zig-zag-img">
                    </div>
   </form>
                </div>
                <div class="col-12 col-sm-12 col-md-12 col-lg-7 col-xl-7 col-xxl-7 change-bg">
    
<div class="listing-container">
                    <div class="right-cont">
                        <h3 class="main-heading">' . $ad_heading . '</h3>
                    </div>
                    <div class="swiper-block my-ads-swiper">
                        <!-- Swiper -->
                        <div class="swiper-container swiper-event-container">
                            <div class="swiper-wrapper">
                                ' . $html . '           
                            <div class="swiper-pagination"></div>
                      
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>
                    </div>
                </div>
</div>
            </div>
        </div>
        <img class="vector-img" src="' . esc_url($bg_img2) . '" alt="vector-img">
        <img class="vector-img-2" src="' . esc_url($bg_img1) . '" alt="vector-img-2">
    </section> ';
        } else {

            $args = array('hide_empty' => 0);
            $args = apply_filters('adforest_wpml_show_all_posts', $args); // for all lang texonomies
            $final_loc_html = '';
            $categories_list_html = '';
            $loc_flag = FALSE;
            $rows = isset($ads_cat_search) && $ads_cat_search != '' ? $ads_cat_search : array();
            if (is_array($rows) && !empty($rows)) {
                $categories_list_html .= '<option label="' . __('Select Category', 'adforest') . '" value="">' . __('Select Category', 'adforest') . '</option>';
                foreach ($rows as $row) {
                    $loc_id = $row;
                    if ($loc_id == "all") {
                        $loc_flag = TRUE;
                        $categories_list_html = "";
                        break;
                    }
                    if (isset($loc_id) && $loc_id != "") {
                        $term = get_term($loc_id, 'ad_cats');
                        $categories_list_html .= ' <option value="' . $loc_id . '">' . $term->name . '</option> ';
                    }
                }
            }
            if ($loc_flag) {
                $categories_list_html .= '<option label="' . __('Select Category', 'adforest') . '" value="">' . __('Select Category', 'adforest') . '</option>';
                $args = array(
                    'type' => 'html',
                    'taxonomy' => 'ad_cats',
                    'tag' => 'option',
                    'parent_id' => 0,
                );
                $categories_list_html = apply_filters('adforest_tax_hierarchy', $categories_list_html, $args);
            }
            if (isset($location_type) && $location_type == 'custom_locations') {
                $args = array('hide_empty' => 0);
                $args = apply_filters('adforest_wpml_show_all_posts', $args); // for all lang texonomies
                $final_loc_html = '';
                $locations_html = '';
                $loc_flag = FALSE;
                $rows = isset($ads_locations) && $ads_locations != '' ? $ads_locations : array();
                if (isset($adforest_elementor) && $adforest_elementor) {
                    $rows = ($ads_locations);
                } else {
                    $rows = vc_param_group_parse_atts($atts['ads_locations']);
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
                    $args = array(
                        'type' => 'html',
                        'taxonomy' => 'ad_country',
                        'tag' => 'option',
                        'parent_id' => 0,
                    );
                    $locations_html = apply_filters('adforest_tax_hierarchy', $locations_html, $args);
                }
            }
            $contries_script = '';
            if (isset($location_type) && $location_type == 'custom_locations') {
                $final_loc_html .= '<select class="js-example-basic-single post-type-change" name="country_id" data-placeholder="' . __('Select Location', 'adforest') . '">';
                $final_loc_html .= '<option label="' . __('Select Location', 'adforest') . '" value="">' . __('Select Location', 'adforest') . '</option>';
                $final_loc_html .= $locations_html;
                $final_loc_html .= '</select>';
            } else {
                ob_start();
                adforest_load_search_countries();
                $contries_script = ob_get_contents();
                ob_end_clean();
                wp_enqueue_script('google-map-callback');
                $final_loc_html = '<input class="form-control" name="location"  id="sb_user_address" placeholder="' . $location_placeholder . '"  type="text">';
            }

            $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
            $bg_img1 = SB_DIR_URL . 'images/vector-img-2.png';
            $bg_img2 = SB_DIR_URL . 'images/vector-img.png';
            $zigzag = SB_DIR_URL . 'images/zig-zag-img.png';

            /* ads section */
            $rows = array();
            if (isset($adforest_elementor) && $adforest_elementor) {
                $rows = isset($atts['ad_cats']) ? $atts['ad_cats'] : "";
            } else {
                if (isset($atts['ad_cats']) && $atts['ad_cats'] != '') {
                    $rows = vc_param_group_parse_atts($atts['ad_cats']);
                    $rows = apply_filters('adforest_validate_term_type', $rows);
                }
            }
            if (isset($rows) && $rows != '' && is_array($rows) && count($rows) > 0) {
                foreach ($rows as $row) {
                    if (isset($adforest_elementor) && $adforest_elementor) {
                        $cat_id = $row;
                    } else {
                        $cat_id = $row['cat'];
                    }
                    if ($cat_id != 'all') {
                        $cats[] = $cat_id;
                    }
                }
            }
            $is_all = false;
            $category = '';
            if (isset($cats) && !empty($cats) && count($cats) > 0) {
                $category = array('taxonomy' => 'ad_cats', 'field' => 'term_id', 'terms' => $cats);
            }
            $ordering = 'DESC';
            $order_by = 'date';
            if ($ad_order == 'asc') {
                $ordering = 'ASC';
            } else if ($ad_order == 'desc') {
                $ordering = 'DESC';
            } else if ($ad_order == 'rand') {
                $order_by = 'rand';
            }

            $is_feature = '';
            if ($ad_type == 'feature') {
                $is_feature = array('key' => '_adforest_is_feature', 'value' => 1, 'compare' => '=',);
            } else if ($ad_type == 'both') {
                $is_feature = '';
            } else {
                $is_feature = array('key' => '_adforest_is_feature', 'value' => 0, 'compare' => '=',);
            }

            $is_active = array('key' => '_adforest_ad_status_', 'value' => 'active', 'compare' => '=',);

            $args = array(
                'post_type' => 'ad_post',
                'post_status' => 'publish',
                'posts_per_page' => $no_of_ads,
                'orderby' => $order_by,
                'order' => $ordering,
                'meta_query' => array($is_feature, $is_active,),
            );
            if ($category != '') {
                $args['tax_query'] = array($category);
            }
            $args = apply_filters('adforest_wpml_show_all_posts', $args);
            $html = '';
            global $adforest_theme;
            $out = "";
            $results = new \WP_Query($args);
            if ($results->have_posts()) {
                while ($results->have_posts()) {
                    $results->the_post();
                    $pid = get_the_ID();
                    $fun = "get_ads_grid_type_4";
                    $html .= $fun(get_the_ID(), 4);
                }
            }




            echo $contries_script . ' <section class="buyent-new-hero buyent-ads-hero">
        <div class="container">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-5 col-xl-5 col-xxl-5"> 
           <form  action="' . urldecode(get_the_permalink($sb_search_page)) . '">
                    <div class="main-content">
                        <span class="title">' . $section_tagline . '</span>
                        <h1 class="main-heading">' . $section_title . '</h1>
                        <p class="txt">' . $section_description . '</p>
                        <div class="main-search-box form-group">
                            <h6 class="title">' . $form_heading . '</h6>
                            <div class="input-main">
                                  <input type="text" placeholder="' . $keyword_placeholder . '" name ="ad_title">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="srh-main">
                                <div class="srh-sub">
                                   <select class="default-select post-type-change" name="cat_id" data-placeholder="' . __('Select Category', 'adforest') . '">
                                   ' . $categories_list_html . '
                                 </select>
                                </div>
                                <div class="srh-sub">
                                ' . $final_loc_html . '
                                </div>
                            </div>
                            <div class="botm-btn">
                                <button type="submit" class="btn btn-theme btn-block srh-btn"><i class="fa fa-magnifying-glass"></i>Search Now</a>
                            </div>
                        </div>
                        <img class="zig-zag-img" src="' . $zigzag . '" alt="zig-zag-img">
                    </div>
   </form>
                </div>
                <div class="col-12 col-sm-12 col-md-12 col-lg-7 col-xl-7 col-xxl-7 change-bg">
                    <div class="right-cont">
                        <h3 class="main-heading">' . $ad_heading . '</h3>
                    </div>
                    <div class="swiper-block my-ads-swiper">
                        <!-- Swiper -->
                        <div class="swiper-container swiper-event-container">
                            <div class="swiper-wrapper">
                                ' . $html . '           
                            <div class="swiper-pagination"></div>
                      
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <img class="vector-img" src="' . esc_url($bg_img2) . '" alt="vector-img">
        <img class="vector-img-2" src="' . esc_url($bg_img1) . '" alt="vector-img-2">
    </section> ';
        }



        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <script>
                jQuery('select').select2();
            </script>
            <?php

        }
    }

}
