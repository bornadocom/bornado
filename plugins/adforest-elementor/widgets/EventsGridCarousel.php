<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class EventsGridCarousel extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_event_grid_carousel';
    }

    public function get_title()
    {
        return __('Event Grid Carousel', 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'basic', [
                'label' => esc_html__('Basic', 'adforest-elementor'),
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
            'event_title_limit', [
                'label' => esc_html__('Event Title Limit(In Characters)', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 25,
                'label_block' => true
            ]
        );

        $event_categories = adforest_get_ad_taxonomy_callback('l_event_cat');

        $event_cats_options = [];

        if (is_array($event_categories) && count($event_categories) > 0) {
            foreach ($event_categories as $category) {
                $event_cats_options[$category->term_id] = $category->name;

                $event_cats_options += get_all_child_terms($category->term_id, 'l_event_cat');
            }
        }
        $this->add_control(
            'event_cats_select',
            [
                'label' => esc_html__('Select Event Category', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $event_cats_options,
                'multiple' => true,
                'default' => array_key_first($event_cats_options),
                'label_block' => true
            ]
        );
        $this->add_control(
            'no_of_events',
            [
                'label' => esc_html__('Number of Events', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'max' => 50,
                'label_block' => true
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_title'] = $atts['section_title'] ?? "";
        $params['event_title_limit'] = $atts['event_title_limit'] ?? "";
        $params['event_cats_select'] = $atts['event_cats_select'] ?? "";
        $params['no_of_events'] = $atts['no_of_events'] ?? "";

        if (function_exists('adforest_event_grid_carousel')) {
            echo adforest_event_grid_carousel($params);
        }

        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-event-list-carousel').owlCarousel({
                        loop: false,
                        rtl: is_rtl,
                        margin: 36,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        responsive: {
                            0: {
                                items: 1
                            },
                            576: {
                                items: 2
                            },
                            768: {
                                margin: 20,
                                items: 3
                            },
                            992: {
                                items: 3
                            },
                            1200: {
                                margin: 20,
                                items: 4
                            },
                            1400: {
                                items: 4
                            }
                        }
                    });
                });
            </script>
        <?php }
    }
}