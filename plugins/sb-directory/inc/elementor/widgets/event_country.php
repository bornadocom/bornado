<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_event_country extends Widget_Base {

    public function get_name() {
        return 'event_country_base';
    }

    public function get_title() {
        return __('Event Custom Location', 'sb_pro');
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
                'section_bg',
                [
                    'label' => __('Background Color', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    //'multiple' => true,
                    "description" => __("Select background color", 'sb_pro'),
                    'options' => [
                        '' => __('White', 'sb_pro'),
                        'gray' => __('Gray', 'sb_pro'),
                    ],
                ]
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
                'select_locations111',
                [
                    'label' => __('Locations', 'sb_pro'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );
        $this->add_control(
                'loc_style',
                [
                    'label' => __('Location style', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    "description" => __("Select location frontend  view", 'sb_pro'),
                    'options' => [
                        '1' => __('Style 1', 'sb_pro'),
                        '2' => __('Style 2', 'sb_pro'),
                        '3' => __('Style 3', 'sb_pro'),
                    ],
                ]
        );
        $repeater_country = new \Elementor\Repeater();

        $repeater_country->add_control(
                'name', [
            'label' => __('Select Locations', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'label_block' => true,
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'event_loc'),
                ]
        );

        $repeater_country->add_control(
                'img',
                [
                    'label' => __('Location Background Image', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    "description" => __("Recommended size 250x160", 'sb_pro'),
                    'default' => [
                        'url' => \Elementor\Utils::get_placeholder_image_src(),
                    ],
                ]
        );
        $this->add_control(
                'select_locations',
                [
                    'label' => __('Select Locations', 'sb_pro'),
                    'type' => \Elementor\Controls_Manager::REPEATER,
                    'fields' => $repeater_country->get_controls(),
                    'title_field' => '{{{ name }}}',
                ]
        );

        $this->end_controls_section();
    }

    protected function render() {

        $params = $this->get_settings_for_display();
        $atts = array();
        $atts['adforest_elementor'] = true;
        $atts['cat_link_page'] = isset($params['cat_link_page']) ? $params['cat_link_page'] : "search";
        $atts['section_bg'] = isset($params['section_bg']) ? $params['section_bg'] : "";
        $atts['section_tagline'] = isset($params['section_tagline']) ? $params['section_tagline'] : "";
        $atts['section_title'] = isset($params['section_title']) ? $params['section_title'] : "";
        $atts['section_description'] = isset($params['section_description']) ? $params['section_description'] : "";
        $atts['select_locations'] = isset($params['select_locations']) ? $params['select_locations'] : "";
        $atts['header_style'] = isset($params['header_style']) ? $params['header_style'] : "";
        

        $atts['section_title_regular'] = isset($params['section_title_regular']) ? $params['section_title_regular'] : "";
        $atts['loc_style'] = isset($params['loc_style']) ? $params['loc_style'] : "1";

        extract($atts);
        require trailingslashit(SB_DIR_PATH) . "inc/elementor/header_layout.php";

        $adforest_render_params = array();
        $section_bg = isset($section_bg) && $section_bg != "" ? 'bg-gray' : "";
        $marker_div = '<div class="marker-img"><img src="' . trailingslashit(get_template_directory_uri()) . 'images/route.png' . '" alt="' . __(' location', 'adforest') . '"></div>';
        $locations_html = '';

        if (isset($atts['select_locations']) && $atts['select_locations'] != '') {
            if (isset($adforest_elementor) && $adforest_elementor) {
                $rows = $atts['select_locations'];
            } else {
                $rows = vc_param_group_parse_atts($atts['select_locations']);
            }

             $loc_style = isset($loc_style) ? $loc_style : '1';
            if (is_array($rows) && count($rows) > 0) {
                foreach ($rows as $r) {
                    if ($r != '') {
                        $img_thumb = '';
                        if (isset($adforest_elementor) && $adforest_elementor) {
                            $img = (isset($r['img']['id'])) ? $r['img']['id'] : '';
                        } else {
                            $img = (isset($r['img'])) ? $r['img'] : '';
                        }
                        $id = (isset($r['name'])) ? $r['name'] : '';
                        if (isset($adforest_elementor) && $adforest_elementor) {
                            $term = get_term_by('id', $id, 'event_loc');
                        } else {
                            $term = get_term_by('slug', $id, 'event_loc');
                        }
                        $img_thumb = isset($r['img']['url']) ? $r['img']['url'] : "";

                        if ($loc_style == '1') {

                            if (isset($term->name)) {
                                $id_get = $term->term_id;
                                $slug = $term->slug;
                                $name = $term->name;
                                $count = $term->count;
                                $link = get_term_link($id_get, 'event_loc');
                                if (is_wp_error($link)) {
                                    continue;
                                }
                                $parent = $term->parent;
                                $locations_html .= '<div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                <div class="sect-location">
                                    <div class="sect-location-img">
                                       <img src="' . $img_thumb . '" alt="' . $name . '">
                                    </div>
                                    <div class="sect-location-heding">
                                        <div class="sect-location-country-heading">
                                            <a href="' . adforest_cat_link_page($id_get, $cat_link_page, 'country_id') . '"> <h5>' . esc_html($name) . '</h5></a>
                                             <span class="">( ' . $count . ' ' . esc_html__('ads', 'adforest') . ' )</span>
                                        </div>
                                        <div class="sect-location-marker">
                                             <img src="' . get_template_directory_uri() . '/images/route.png" alt="' . esc_html__('icon', 'adforest') . '">
                                        </div>
                                    </div>
                                </div>
                            </div>';
                            }
                        } else if ($loc_style == "2") {

                            if (isset($term->name)) {
                                $id_get = $term->term_id;
                                $slug = $term->slug;
                                $name = $term->name;
                                $count = $term->count;
                                $link = get_term_link($id_get, 'event_loc');
                                if (is_wp_error($link)) {
                                    continue;
                                }
                                $parent = $term->parent;
                                $locations_html .= '          <div class="item">
                                                    <div class="most-popular-city">
                                                <a href="' . adforest_cat_link_page($id_get, $cat_link_page, 'country_id') . '">        <img src="' . $img_thumb . '" alt="' . $name . '"></a>
                                                    </div>
                                                     <div class="places-content">

                                                     <div class="place-content-heding">
                                                     <a href="' . adforest_cat_link_page($id_get, $cat_link_page, 'country_id') . '"> <h5>' . $name . '</h5></a>
                                                      <span class="">( ' . $count . ' ' . esc_html__('ads', 'adforest') . ' )</span>
                                                      </div>
                                                <div class="places-icon">
                                            <img src="' . get_template_directory_uri() . '/images/icon-map.png" alt="' . esc_html__('icon', 'adforest') . '">
                                           </div>
                                       </div>
                                    </div>';
                            }
                        } else if ($loc_style == "3") {
                            $id_get = $term->term_id;
                                $slug = $term->slug;
                                $name = $term->name;
                                $count = $term->count;
                                $link = get_term_link($id_get, 'event_loc');
                                if (is_wp_error($link)) {
                                    continue;
                                }
                                $parent = $term->parent;
                            $locations_html .= '
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3 grid-item">
                    <div class="ads-location-box">
                       <a href="' . adforest_cat_link_page($id_get, $cat_link_page, 'country_id') . '">        <img src="' . $img_thumb . '" alt="' . $name . '"></a>
                        <div class="meta">
                            <h3 class="title">'.$name.'</h3>
                           <small class=""> ' . $count . ' ' . esc_html__('ads', 'adforest') . ' </small>
                        </div>
                          <a href="'.$link.'">
                            <div class="next-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--bx" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24" data-icon="bx:chevron-right"><path fill="currentColor" d="M10.707 17.707L16.414 12l-5.707-5.707l-1.414 1.414L13.586 12l-4.293 4.293z"></path></svg>
                            </div>
                        </a>
                    </div>
                </div>';
                        }
                    }
                }
            }
        }
        if ($loc_style == '1') {
            echo '<section class="ads-location event   ' . $section_bg . '">
    <div class="container">
        <div class="row">
         ' . ($header) . '
            ' . $locations_html . '
        </div>
    </div>
</section>';
        }

        if ($loc_style == '2') {
            echo '<section class="most-popular custom-padding event  ' . $section_bg . '">
   <div class="container">
<div class="sb-short-head center">
 <span>' . esc_html($section_tagline) . '</span>
      <h2>' . $section_title . '</h2>
      <p>' . $section_description . '</p>
    </div>
    <div class="carousel-wrap">
    <div class="owl-carousel1  location-ad-carousel">

  ' . $locations_html . '
</div>
</div>
</div>
</section>';
        }

        if ($loc_style == '3') {
            echo '<section class="ads-location-section event ' . $section_bg . '">
    <div class="container">
        <div class="row">
             ' . ($header) . '
            ' . $locations_html . '
        </div>
    </div>
</section>';
        }
    }

}
