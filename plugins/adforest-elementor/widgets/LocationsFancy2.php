<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class LocationsFancy2 extends Widget_Base
{
    public function get_name()
    {
        return 'locations_fancy_2_shortcode';
    }

    public function get_title()
    {
        return __("Locations Fancy 2", 'adforest-elementor');
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
            'main_sec_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

	    $ad_countries = adforest_get_ad_taxonomy_callback('event_loc');

        $event_loc_options = [];

        if (is_array($ad_countries) && count($ad_countries) > 0) {
            foreach ($ad_countries as $category) {
                $event_loc_options[$category->slug] = $category->name;

                $child_categories = get_terms([
                    'taxonomy'   => 'event_loc',
                    'parent'     => $category->term_id,
                    'hide_empty' => false,
                ]);

                if (!empty($child_categories) && !is_wp_error($child_categories)) {
                    foreach ($child_categories as $child) {
                        $event_loc_options[$child->slug] = '-- ' . $child->name;
                    }
                }
            }
        }

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'locations',
            [
                'label' => esc_html__('Select Location', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'options' => $event_loc_options,
                'default' => '',
            ]
        );

        $repeater->add_control(
            'location_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'location_repeater',
            [
                'label' => esc_html__('Select Locations', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ (function() {
                    const categoryId = locations;
                    const categories = ' . json_encode($event_loc_options) . ';
                    return categories[categoryId] || "Location #" + categoryId;
                })() }}}',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['main_sec_title'] = isset($atts['main_sec_title']) ? $atts['main_sec_title'] : "";
        $params['location_repeater'] = isset($atts['location_repeater']) ? $atts['location_repeater'] : "";
        $params['adforest_elementor'] = true;

        if (function_exists('locations_fancy_2_shortcode')) {
            echo locations_fancy_2_shortcode($params);
        }

        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.adt-popular-place-carousel').owlCarousel({
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