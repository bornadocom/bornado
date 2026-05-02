<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) exit;

class DealsOfTheDay extends Widget_Base {

    public function get_name() {
        return 'adforest_deals_of_the_day';
    }

    public function get_title() {
        return __( 'Deals of the Day', 'adforest_elementor' );
    }

    public function get_icon() {
        return 'eicon-hotspot';
    }

    public function get_categories() {
        return [ 'adforest_widgets' ];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Content', 'adforest_elementor' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'background',
            [
                'label'   => __( 'Background Image', 'adforest_elementor' ),
                'type'    => Controls_Manager::MEDIA,
                'default' => [ 'url' => '' ],
            ]
        );

        $this->add_control(
            'overlay_color',
            [
                'label'     => __( 'Overlay Color', 'adforest_elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .adt-ecommerce-deals-box::before' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => __( 'Title', 'adforest_elementor' ),
                'type'    => Controls_Manager::TEXT,
                'default' => __( 'Deals Of The Day', 'adforest_elementor' ),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'description',
            [
                'label'   => __( 'Description', 'adforest_elementor' ),
                'type'    => Controls_Manager::TEXTAREA,
                'default' => __( 'Get a chance, Save up to 50% discount', 'adforest_elementor' ),
                'rows'    => 3,
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __( 'Button Text', 'adforest_elementor' ),
                'type'    => Controls_Manager::TEXT,
                'default' => __( 'Shop Now', 'adforest_elementor' ),
            ]
        );

        $this->add_control(
            'button_link',
            [
                'label' => __( 'Button Link', 'adforest_elementor' ),
                'type'  => Controls_Manager::URL,
                'default' => [ 'url' => '#' ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Style', 'adforest_elementor' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'box_background',
                'label'    => __( 'Box Background', 'adforest_elementor' ),
                'types'    => [ 'classic' ],
                'selector' => '{{WRAPPER}} .adt-ecommerce-deals-box',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __( 'Title Typography', 'adforest_elementor' ),
                'selector' => '{{WRAPPER}} .adt-ecommerce-deals-box .title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __( 'Title Color', 'adforest_elementor' ),
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
                'label'    => __( 'Description Typography', 'adforest_elementor' ),
                'selector' => '{{WRAPPER}} .adt-ecommerce-deals-box .desc',
            ]
        );

        $this->add_control(
            'desc_color',
            [
                'label'     => __( 'Description Color', 'adforest_elementor' ),
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
                'label'    => __( 'Button Typography', 'adforest_elementor' ),
                'selector' => '{{WRAPPER}} .adt-button-dark',
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => __( 'Button Text Color', 'adforest_elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .adt-button-dark' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label'     => __( 'Button Background Color', 'adforest_elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .adt-button-dark' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        ?>
        <div class="adt-ecommerce-deals-box" style="position: relative; overflow: hidden;">
            <?php if ( $s['background']['url'] ) : ?>
                <div class="deals-bg" style="
                        background-image: url('<?php echo esc_url( $s['background']['url'] ); ?>');
                        position: absolute; top:0; left:0; width:100%; height:100%; background-size:cover; background-position:center;
                        "></div>
                <div class="deals-overlay" style="
                        position:absolute; top:0; left:0; width:100%; height:100%; background: <?php echo esc_attr( $s['overlay_color'] ); ?>;
                        "></div>
            <?php endif; ?>
            <div class="deals-content" style="position:relative; padding: 30px; text-align:center;">
                <?php if ( $s['title'] ) : ?><h3 class="title"><?php echo esc_html( $s['title'] ); ?></h3><?php endif; ?>
                <?php if ( $s['description'] ) : ?><p class="desc"><?php echo esc_html( $s['description'] ); ?></p><?php endif; ?>
                <?php if ( $s['button_text'] ) : ?><a href="<?php echo esc_url( $s['button_link']['url'] ); ?>" class="adt-button-dark"><?php echo esc_html( $s['button_text'] ); ?></a><?php endif; ?>
            </div>
        </div>
        <?php
    }
}