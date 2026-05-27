<?php

namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Repeater;

if (! defined('ABSPATH')) exit;

class EcommerceDealsBoxes extends Widget_Base
{

    public function get_name()
    {
        return 'adforest_ecom_deals_boxes';
    }

    public function get_title()
    {
        return __('Ecommerce Deals Boxes', 'adforest_elementor');
    }

    public function get_icon()
    {
        return 'eicon-gallery-grid';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'adforest_elementor'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'alignment',
            [
                'label'   => __('Content Alignment', 'adforest_elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'right' => __('Right', 'adforest_elementor'),
                    'left'  => __('Left', 'adforest_elementor'),
                ],
                'default' => 'right',
            ]
        );

        $repeater->add_control(
            'bg_image',
            [
                'label'   => __('Background Image', 'adforest_elementor'),
                'type'    => Controls_Manager::MEDIA,
                'default' => ['url' => ''],
            ]
        );

        $repeater->add_control(
            'title',
            [
                'label'       => __('Title', 'adforest_elementor'),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'default'     => __('Hand Gloves', 'adforest_elementor'),
            ]
        );

        $repeater->add_control(
            'desc',
            [
                'label'   => __('Description', 'adforest_elementor'),
                'type'    => Controls_Manager::TEXTAREA,
                'rows'    => 3,
                'default' => __('Save up to 50% discount', 'adforest_elementor'),
            ]
        );

        $repeater->add_control(
            'button_text',
            [
                'label'   => __('Button Text', 'adforest_elementor'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('Shop Now', 'adforest_elementor'),
            ]
        );

        $repeater->add_control(
            'button_link',
            [
                'label'   => __('Button Link', 'adforest_elementor'),
                'type'    => Controls_Manager::URL,
                'default' => ['url' => '#'],
            ]
        );

        $this->add_control(
            'deals',
            [
                'label'       => __('Deals Boxes', 'adforest_elementor'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    [
                        'alignment'   => 'right',
                        'title'       => __('Hand Gloves', 'adforest_elementor'),
                        'desc'        => __('Save up to 50% discount', 'adforest_elementor'),
                        'button_text' => __('Shop Now', 'adforest_elementor'),
                        'button_link' => ['url' => '#'],
                    ],
                    [
                        'alignment'   => 'left',
                        'title'       => __('Best Earrings', 'adforest_elementor'),
                        'desc'        => __('Save up to 50% discount', 'adforest_elementor'),
                        'button_text' => __('Shop Now', 'adforest_elementor'),
                        'button_link' => ['url' => '#'],
                    ],
                ],
                'title_field' => '{{{ title }}}',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'adforest_elementor'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __('Title Typography', 'adforest_elementor'),
                'selector' => '{{WRAPPER}} .adt-ecommerce-deals-box .title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __('Title Color', 'adforest_elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .adt-ecommerce-deals-box .title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'desc_typography',
                'label'    => __('Description Typography', 'adforest_elementor'),
                'selector' => '{{WRAPPER}} .adt-ecommerce-deals-box .desc',
            ]
        );

        $this->add_control(
            'desc_color',
            [
                'label'     => __('Description Color', 'adforest_elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .adt-ecommerce-deals-box .desc' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'label'    => __('Button Typography', 'adforest_elementor'),
                'selector' => '{{WRAPPER}} .adt-button-dark',
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => __('Button Text Color', 'adforest_elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .adt-button-dark' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label'     => __('Button Background Color', 'adforest_elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .adt-button-dark' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();
        if (empty($s['deals']) || ! is_array($s['deals'])) {
            return;
        }
?>
        <div class="container adt-container">
            <div class="adt-ecommerce-deals-box-wrapper">
                <?php foreach ($s['deals'] as $deal) :
                    $align = isset($deal['alignment']) && $deal['alignment'] === 'left' ? 'ecom-deal-content-left' : 'ecom-deal-content-right';
                    $bg_url = isset($deal['bg_image']['url']) ? $deal['bg_image']['url'] : '';
                    $style  = $bg_url ? 'background-image: url(' . esc_url($bg_url) . ');' : '';
                ?>
                    <div class="adt-ecommerce-deals-box <?php echo esc_attr($align); ?>" style="<?php echo esc_attr($style); ?>">
                        <?php if (! empty($deal['title'])) : ?>
                            <h3 class="title"><?php echo esc_html($deal['title']); ?></h3>
                        <?php endif; ?>
                        <?php if (! empty($deal['desc'])) : ?>
                            <p class="desc"><?php echo esc_html($deal['desc']); ?></p>
                        <?php endif; ?>
                        <?php if (! empty($deal['button_text'])) : ?>
                            <a href="<?php echo isset($deal['button_link']['url']) ? esc_url($deal['button_link']['url']) : '#'; ?>" class="adt-button-dark"><?php echo esc_html($deal['button_text']); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php
    }
}
