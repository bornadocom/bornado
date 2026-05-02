<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class MainHero2 extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_main_hero2';
    }

    public function get_title()
    {
        return __('Main Hero 2', 'adforest-elementor');
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
            'bg_img', [
                'label' => esc_html__('Background Image', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => esc_html__("", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'section_title', [
                'label' => esc_html__('Section Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => esc_html__('Section Title', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'section_tagline', [
                'label' => esc_html__('Section Tagline', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => esc_html__('Section Tagline', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'badge_text_top', [
                'label' => esc_html__('Badge Text Top', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('No 1', 'adforest-elementor'),
                'title' => esc_html__('Badge Text', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'badge_text_middle', [
                'label' => esc_html__('Badge Text Middle', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('PETFOREST', 'adforest-elementor'),
                'title' => esc_html__('Badge Text', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'badge_text_btm', [
                'label' => esc_html__('Badge Text Bottom', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('WordPress', 'adforest-elementor'),
                'title' => esc_html__('Badge Text', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'bg_img_2', [
                'label' => esc_html__('Background Image 2', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => esc_html__("", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'bg_img_3', [
                'label' => esc_html__('Background Image 3', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => esc_html__("", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );
        $this->add_control(
            'bg_img_4', [
                'label' => esc_html__('Background Image 4', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => esc_html__("", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );
        $this->add_control(
            'arrow_img', [
                'label' => esc_html__('Arrow PNG', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                "description" => esc_html__("", 'adforest-elementor'),
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'cat_section', [
                'label' => esc_html__('Category Section', 'adforest-elementor'),
            ]
        );

        $this->add_control(
            'cat_section_title', [
                'label' => esc_html__('Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => esc_html__('Title', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'cat_section_tagline', [
                'label' => esc_html__('Tagline', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' => esc_html__('Tagline', 'adforest-elementor'),
            ]
        );

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
                'default' => '',
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
                'label' => esc_html__('Categories', 'adforest-elementor'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ mini_category }}}',
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
        $params['cat_section_title'] = $atts['cat_section_title'] ?? "";
        $params['section_tagline'] = $atts['section_tagline'] ?? "";
        $params['badge_text_top'] = $atts['badge_text_top'] ?? "";
        $params['badge_text_middle'] = $atts['badge_text_middle'] ?? "";
        $params['badge_text_btm'] = $atts['badge_text_btm'] ?? "";
        $params['cat_section_tagline'] = $atts['cat_section_tagline'] ?? "";
        $params['bg_img'] = $atts['bg_img'] ?? "";
        $params['bg_img_2'] = $atts['bg_img_2'] ?? "";
        $params['bg_img_3'] = $atts['bg_img_3'] ?? "";
        $params['bg_img_4'] = $atts['bg_img_4'] ?? "";
        $params['arrow_img'] = $atts['arrow_img'] ?? "";
        $params['mini_categories_repeater'] = $atts['mini_categories_repeater'] ?? "";

        if (function_exists('main_hero_2_shortcode')) {
            echo main_hero_2_shortcode($params);
        }

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.pet-category-carousel').owlCarousel({
                        loop: false,
                        rtl: is_rtl,
                        margin: 30,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: false,
                        responsive: {
                            0: {
                                items: 2,
                                margin: 10,
                            }, 420: {
                                items: 2,
                                margin: 10,
                            }, 576: {
                                items: 3,
                                margin: 10,
                            }, 992: {
                                items: 5,
                                margin: 20,
                            }, 1200: {
                                items: 6,
                                margin: 20,
                            }, 1400: {
                                items: 6
                            }
                        }
                    });
                });
            </script>
        <?php }
    }
}