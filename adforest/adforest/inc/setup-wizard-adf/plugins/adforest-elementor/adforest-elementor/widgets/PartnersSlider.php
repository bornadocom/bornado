<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ElementorAdforest\Traits\SliderControlsTrait;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class PartnersSlider extends Widget_Base
{
    use SliderControlsTrait;

    public function get_name()
    {
        return 'client_partner_classic_short_base';
    }

    public function get_title()
    {
        return __('Clients/Partners', 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return [ 'adforest_widgets' ];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'basic',
            [
                'label' => esc_html__('Basic', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'section_bg_color',
            array(
                'label' => __('Background Color', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'light' => __('Light', 'adforest-elementor'),
                    'dark' => __('Dark', 'adforest-elementor'),
                ),
                'default' => "dark"
            )
        );

        $this->end_controls_section();

        $this->register_slider_controls();

        $this->start_controls_section(
            'client_settings',
            [
                'label' => esc_html__('Client or Partners', 'adforest-elementor'),
            ]
        );
        $adforest_elementor_repetor = new \Elementor\Repeater();

        $adforest_elementor_repetor->add_control(
            'logo',
            array(
                'label' => __('Logo', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                "description" => __("135x35", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            )
        );

        $this->add_control(
            'clients',
            [
                'label' => __('Add Client', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $adforest_elementor_repetor->get_controls(),
                'default' => [],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['section_bg_color'] = isset($atts['section_bg_color']) ? $atts['section_bg_color'] : "";
        $params['clients'] = isset($atts['clients']) ? $atts['clients'] : "";
        $params['loop_slider'] = isset($atts['loop_slider']) ? $atts['loop_slider'] : "yes";
        $params['autoplay_slider'] = isset($atts['autoplay_slider']) ? $atts['autoplay_slider'] : "no";
        $params['slider_speed'] = isset($atts['slider_speed']) ? $atts['slider_speed'] : 3000;


        if (function_exists('partners_sliders_short_base_func')) {
            echo partners_sliders_short_base_func($params);
        }

        //brand-carousel
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.brand-carousel').each(function() {
                        var $slider = $(this);

                        var sliderLoop = $slider.data('loop') === true;
                        var sliderAutoplay = $slider.data('autoplay') === true;
                        var sliderSpeed = $slider.data('autoplay-timeout') || 3000;

                        $slider.owlCarousel({
                            loop: sliderLoop,
                            rtl: is_rtl,
                            margin: 10,
                            nav: false,
                            dots: false,
                            autoplay: sliderAutoplay,
                            autoplayTimeout: sliderSpeed,
                            autoplayHoverPause: false,
                            responsive: {
                                0: {
                                    items: 1
                                }, 576: {
                                    items: 3
                                }, 992: {
                                    items: 5
                                }, 1200: {
                                    items: 6
                                }
                            }
                        });
                    });
                });
            </script>
        <?php }
    }

}