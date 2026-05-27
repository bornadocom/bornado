<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ElementorAdforest\Traits\SliderControlsTrait;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MiniCategorySlider extends Widget_Base
{
    use SliderControlsTrait;
    public function get_name() {
        return 'mini_category_slider';
    }

    public function get_title()
    {
        return __('Mini Category Slider', 'adforest-elementor');
    }

    public function get_icon()
    {
        return "fa fa-audio-description";
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
            'hide_cats_with_no_ads', [
            'label' => esc_html__('Hide categories that have no ads?', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'true' => esc_html__('Yes', 'adforest-elementor'),
                'false' => esc_html__('No', 'adforest-elementor'),
            ],
            'default' => 'false',
        ]);

        $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
        $options = [];

        if (is_array($ad_categories) && count($ad_categories) > 0) {
            foreach ($ad_categories as $category) {
                $options[$category->slug] = $category->name;
                $options += get_all_child_terms_slug($category->term_id, 'ad_cats');
            }
        }

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'mini_category',
            [
                'label' => esc_html__('Select Category', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'options' => $options,
                'default' => array_key_first($options),
            ]
        );

        $repeater->add_control(
            'category_image',
            [
                'label' => esc_html__('Upload Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'mini_categories_repeater',
            [
                'label' => esc_html__('Categories Repeater', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ (function() {
                    const categoryId = mini_category;
                    const categories = ' . json_encode($options) . ';
                    return categories[categoryId] || "Category #" + categoryId;
                })() }}}',
            ]
        );

        $this->end_controls_section();
        $this->register_slider_controls();
    }

    protected function render()
    {
        $atts = $this->get_settings_for_display();
        $params = array();
        $params['adforest_elementor'] = true;
        $params['hide_cats_with_no_ads'] = $atts['hide_cats_with_no_ads'] ?? "";
        $params['mini_categories_repeater'] = $atts['mini_categories_repeater'] ?? "";
        $params['loop_slider'] = $atts['loop_slider'] ?? 'yes';
        $params['autoplay_slider'] = $atts['autoplay_slider'] ?? 'no';
        $params['pause_on_hover_slider'] = $atts['pause_on_hover_slider'] ?? 'no';
        $params['slider_speed'] = $atts['slider_speed'] ?? 3000;

        if (function_exists('mini_category_slider')) {
            echo mini_category_slider($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    const miniCategoryCarousel = $('.category-mini-carousel');
                    if (miniCategoryCarousel.length > 0) {
                        const loop = miniCategoryCarousel.data('loop');
                        const autoplay = miniCategoryCarousel.data('autoplay');
                        const pause_on_hover_slider = miniCategoryCarousel.data('pause-on-hover');
                        const autoplayTimeout = miniCategoryCarousel.data('autoplay-timeout');

                        miniCategoryCarousel.owlCarousel({
                            loop: loop,
                            rtl: is_rtl,
                            margin: 10,
                            nav: false,
                            dots: false,
                            autoplay: autoplay,
                            autoplayTimeout: autoplayTimeout,
                            autoplayHoverPause: pause_on_hover_slider,
                            responsive: {
                                0: {
                                    items: 2
                                }, 576: {
                                    items: 3
                                }, 992: {
                                    items: 5
                                }, 1200: {
                                    items: 7
                                }, 1400: {
                                    items: 9
                                }
                            }
                        });
                    }
                });
            </script>
        <?php }
    }
}