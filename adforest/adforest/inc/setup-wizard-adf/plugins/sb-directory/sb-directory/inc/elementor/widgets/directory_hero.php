<?php
namespace Elementor;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Widget_directory_hero extends Widget_Base {

    public function get_name() {
        return 'adforest_directory_hero';
    }

    public function get_title() {
        return __('Directory Hero', 'sb_pro');
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
            'label' => __('Section tagline', 'sb_pro'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'title' => __('Section Tagline', 'sb_pro'),
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
         $this->add_control(
                'section_bg', [
            'label' => __('Section Backgorund', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'title' => '',
            ]
        );
        $this->end_controls_section();
        $this->start_controls_section(
                'screen', [
            'label' => esc_html__('Search Settings', 'sb_pro'),
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
            'label' => __('Location Placeholder', 'sb_pro'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'title' => __('Location Placeholder', 'sb_pro'),
            'default' => __('Enter Location', 'sb_pro'),
                ]
        );


        $this->end_controls_section();

        $this->start_controls_section(
                'clocation', [
            'label' => esc_html__('Custom Locations', 'sb_pro'),
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
            'options' => apply_filters('adforest_elementor_ads_categories', array(), 'ad_country', 'yes'),
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

        $params['keyword_placeholder'] = isset($atts['keyword_placeholder']) ? $atts['keyword_placeholder'] : "";

        $params['location_placeholder'] = isset($atts['location_placeholder']) ? $atts['location_placeholder'] : "";

        $params['location_type'] = isset($atts['location_type']) ? $atts['location_type'] : "";
        $params['locations'] = isset($atts['locations']) ? $atts['locations'] : "";
        $params['section_bg']   =   isset($atts['section_bg'])  ?  $atts['section_bg'] : "";


        extract($params);

        $args = array('hide_empty' => 0);
        $args = apply_filters('adforest_wpml_show_all_posts', $args); // for all lang texonomies
        $final_loc_html = '';
        $categories_list_html = '';
        $loc_flag = FALSE;


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
                        'taxonomy' => 'ad_country',
                        'tag' => 'option',
                        'parent_id' => 0,
                    );
                    $locations_html = apply_filters('adforest_tax_hierarchy', $locations_html, $args);
                } else {
                    $ad_country_arr = get_terms('ad_country', $args);
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
            $final_loc_html = '<input class="form-control google_location"   name="location"  id="sb_user_address" placeholder="' . $location_placeholder . '"  type="text" >';
        }

        $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);

        $section_bg  =   isset($section_bg['url'])  ? $section_bg['url']  : "";


         $style = ( $section_bg  != "" ) ? ' style="background-image: url(' . $section_bg . '); "' : "";


 echo  ''.$contries_script.' <section class="event-dir-hero-2" '.$style.'>
        <div class="container">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-10 col-xl-7 col-xxl-7">
                    <div class="hero-cont-container">
                        <small>'.$section_tagline.'</small>
                        <h1 class="main-heading">'.$section_title.'</h1>
                        <p class="txt">'.$section_description.'</p>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-12 col-lg-5 col-xl-5 col-xxl-5"></div>
            </div>
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 col-xxl-12">
                    <div class="hero-search-bar-container">    
                    <form action="' . urldecode(get_the_permalink($sb_search_page)) . '">
                        <div class="form-group">
                            <input type="text" placeholder="' . $keyword_placeholder . '" name ="ad_title" class="title-input">
                            <div class="srch-location-main">
                                 '.$final_loc_html.'
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--et scope-icon" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 32 32" data-icon="et:scope"><g fill="currentColor"><path d="M17 6.5a.5.5 0 0 0-1 0V15H6.5a.5.5 0 0 0 0 1H16v9.469a.5.5 0 0 0 1 0V16h9.469a.5.5 0 0 0 0-1H17V6.5z"></path><path d="M31.562 15h-2.613C28.461 8.63 23.37 3.539 17 3.051V.5a.5.5 0 0 0-1 0V3C9.17 3 3.565 8.299 3.051 15H.562a.5.5 0 0 0 0 1H3c0 7.168 5.832 13 13 13v2.5a.5.5 0 0 0 1 0v-2.551C23.701 28.435 29 22.83 29 16h2.562a.5.5 0 0 0 0-1zM16 28C9.383 28 4 22.617 4 16S9.383 4 16 4s12 5.383 12 12s-5.383 12-12 12z"></path></g></svg>
                            </div>
                             <button class="btn btn-theme search-btn">' . esc_html__('Search', 'sb_pro') . '</button>
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--fe srh-icon" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24" data-icon="fe:search"><path fill="currentColor" fill-rule="evenodd" d="m16.325 14.899l5.38 5.38a1.008 1.008 0 0 1-1.427 1.426l-5.38-5.38a8 8 0 1 1 1.426-1.426ZM10 16a6 6 0 1 0 0-12a6 6 0 0 0 0 12Z"></path></svg>          
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </section>';




         if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <script>
                jQuery('select').select2();
            </script>
            <?php

        }
    }

}
