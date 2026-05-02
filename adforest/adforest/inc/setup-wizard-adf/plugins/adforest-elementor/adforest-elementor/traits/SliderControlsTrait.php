<?php
namespace ElementorAdforest\Traits;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait SliderControlsTrait
{
    /**
     * Registers the reusable slider settings controls (Loop, Autoplay, Speed).
     *
     * This method must be called within the protected function register_controls() of the widget.
     * Requires use Elementor\Controls_Manager; in the consuming file.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Arguments to customise the controls.
     *
     *     @type string $section_id    Unique section ID. Default 'carousel'.
     *     @type string $section_label Section heading. Default 'Slider Settings'.
     *     @type string $prefix        Control ID prefix (e.g. 'featured'). Default ''.
     *     @type array  $condition     Elementor condition array applied to the section/controls.
     * }
     */
    protected function register_slider_controls(array $args = [])
    {
        $defaults = [
            'section_id' => 'carousel',
            'section_label' => esc_html__('Slider Settings', 'adforest-elementor'),
            'prefix' => '',
            'condition' => [],
        ];

        $args = wp_parse_args($args, $defaults);

        $section_args = [
            'label' => $args['section_label'],
        ];

        $base_condition = [];
        if (!empty($args['condition']) && is_array($args['condition'])) {
            $section_args['condition'] = $args['condition'];
            $base_condition = $args['condition'];
        }

        $prefix = trim((string) $args['prefix']);
        if ($prefix !== '') {
            $prefix = rtrim($prefix, '_') . '_';
        }

        $loop_control_id = $prefix . 'loop_slider';
        $autoplay_control_id = $prefix . 'autoplay_slider';
        $speed_control_id = $prefix . 'slider_speed';
        $pause_control_id = $prefix . 'pause_on_hover_slider';

        $this->start_controls_section(
            $args['section_id'],
            $section_args
        );

        $this->add_control(
            $loop_control_id,
            array(
                'label' => __('Loop the Slider?', 'adforest-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'adforest-elementor'),
                'label_off' => __('No', 'adforest-elementor'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => $base_condition,
            )
        );

        $this->add_control(
            $autoplay_control_id,
            array(
                'label' => __('Autoplay Slider?', 'adforest-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'adforest-elementor'),
                'label_off' => __('No', 'adforest-elementor'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => $base_condition,
            )
        );

        $this->add_control(
            $speed_control_id,
            array(
                'label' => __('Slider Autoplay Speed (ms)', 'adforest-elementor'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1000,
                'max' => 10000,
                'step' => 1000,
                'default' => 3000,
                'condition' => array_merge(
                    $base_condition,
                    [
                        $autoplay_control_id => 'yes',
                    ]
                ),
            )
        );

        $this->add_control(
            $pause_control_id,
            array(
                'label' => __('Autoplay Pause on Hover?', 'adforest-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'adforest-elementor'),
                'label_off' => __('No', 'adforest-elementor'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => array_merge(
                    $base_condition,
                    [
                        $autoplay_control_id => 'yes',
                    ]
                ),
            )
        );

        $this->end_controls_section();
    }
}
