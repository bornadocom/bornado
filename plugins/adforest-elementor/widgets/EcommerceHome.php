<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Repeater;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class EcommerceHome extends Widget_Base
{

    public function get_name()
    {
        return 'ecommerce_home_widget';
    }

    public function get_title()
    {
        return __('Ecommerce Hero', 'adforest-elementor');
    }

    public function get_icon()
    {
        return 'eicon-countdown';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'left_panel_section',
            [
                'label' => __('Left Panel', 'adforest-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'left_bg',
            [
                'label' => __('Background Image', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'subtitle',
            [
                'label' => __('Subtitle', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Collection 2025', 'adforest-elementor'),
                'label_block' => true,
            ]
        );
        $this->add_control(
            'title',
            [
                'label' => __('Title', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Luxury Furnitures', 'adforest-elementor'),
                'label_block' => true,
            ]
        );
        $this->add_control(
            'button_text',
            [
                'label' => __('Button Text', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Shop Now', 'adforest-elementor'),
            ]
        );
        $this->add_control(
            'button_url',
            [
                'label' => __('Button URL', 'adforest-elementor'),
                'type' => Controls_Manager::URL,
                'default' => ['url' => '#'],
            ]
        );
        $this->add_control(
            'countdown_end',
            [
                'label' => __('Countdown End Date/Time', 'adforest-elementor'),
                'type' => Controls_Manager::DATE_TIME,
                'description' => __('When the sale ends.', 'adforest-elementor'),
                'default' => date('Y-m-d\TH:i', strtotime('+7 days')),
            ]
        );

        $this->end_controls_section();


        //
        // RIGHT PANEL REPEATER
        //
        $repeater = new Repeater();

        $repeater->add_control(
            'cat_id',
            [
                'label' => __('Product Category', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_product_categories(),
                'label_block' => true,
            ]
        );
        $repeater->add_control(
            'cat_image',
            [
                'label' => __('Custom Image (optional)', 'adforest-elementor'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => '',
                ],
            ]
        );
        $repeater->add_control(
            'custom_title',
            [
                'label' => __('Override Title (optional)', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );
        $repeater->add_control(
            'custom_count_text',
            [
                'label' => __('Override Count Text (e.g. “16 Products”)', 'adforest-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );
        $repeater->add_control(
            'cat_link',
            [
                'label' => __('Link URL', 'adforest-elementor'),
                'type' => Controls_Manager::URL,
                'default' => ['url' => '#'],
            ]
        );

        $this->start_controls_section(
            'right_panel_section',
            [
                'label' => __('Right Panel Categories', 'adforest-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        $this->add_control(
            'show_categories',
            [
                'label' => __('Show Categories Section', 'adforest-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'adforest-elementor'),
                'label_off' => __('Hide', 'adforest-elementor'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'categories_list',
            [
                'label' => __('Categories Items', 'adforest-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    ['cat_id' => '', 'custom_count_text' => '', 'cat_link' => ['url' => '#']],
                ],
                'title_field' => '{{{ custom_title || "Category #" + cat_id }}}',
                'condition' => [ 'show_categories' => 'yes' ],
            ]
        );
        $this->add_control(
            'right_columns',
            [
                'label' => __('Columns', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'default' => '3',
                'condition' => [ 'show_categories' => 'yes' ],
            ]
        );
        $this->end_controls_section();

        //
        // GLOBAL STYLES
        //
        $this->start_controls_section(
            'global_style_section',
            [
                'label' => __('Layout & Spacing', 'adforest-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'container_width',
            [
                'label' => __('Container Max Width (px)', 'adforest-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1170,
            ]
        );
        $this->add_control(
            'gutter',
            [
                'label'   => __( 'Gutter between items (px)', 'adforest-elementor' ),
                'type'    => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'   => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 15,
                ],
            ]
        );
        $this->end_controls_section();
    }

    /**
     * Helper to fetch product categories into a select array
     */
    protected function get_product_categories()
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        $options = [];
        foreach ($terms as $term) {
            $options[$term->term_id] = $term->name;
        }
        return $options;
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();

        $container_width = intval($s['container_width']);
        $gutter = intval($s['gutter']['size']);

        $end_timestamp = strtotime($s['countdown_end']) * 1000;
        $has_categories = false;
        if (!empty($s['categories_list']) && is_array($s['categories_list'])) {
            foreach ($s['categories_list'] as $item) {
                if (!empty($item['cat_id'])) { $has_categories = true; break; }
            }
        }
        $show_categories = isset($s['show_categories']) && $s['show_categories'] === 'yes';

        $single_layout = ! ( isset($s['show_categories']) && $s['show_categories'] === 'yes' && $has_categories );
        ?>
        <section class="adt-ecommerce-hero-section">
            <div class="container adt-container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="adt-ecommerce-advertise-container<?php echo $single_layout ? ' single-column' : ''; ?>" style="<?php echo $single_layout ? 'display:block;width:100%;' : ''; ?>">

                            <!-- LEFT PANEL -->
                            <div class="adt-ecommerce-advertise-box adv-left-panel<?php echo ($show_categories && $has_categories) ? '' : ' full-width'; ?>"
                                 style="background-image: url('<?php echo esc_url($s['left_bg']['url']); ?>'); background-repeat: no-repeat; background-position: center center; background-size: cover;<?php echo ($show_categories && $has_categories) ? '' : ' width:100%; flex:0 0 100%;'; ?>">
                                <div class="overlay"></div>
                                <a href="<?php echo esc_url($s['button_url']['url']); ?>"
                                   class="adt-ecom-advertise-inner">
                                    <div class="ecom-advertise-content-box">
                                        <span class="sub-title"><?php echo esc_html($s['subtitle']); ?></span>
                                        <h1 class="title"><?php echo esc_html($s['title']); ?></h1>
                                        <button class="adt-button-dark"><?php echo esc_html($s['button_text']); ?></button>
                                    </div>
                                </a>
                                <div class="ad-bidding-timer-box-inner"
                                     data-countdown="<?php echo esc_attr($end_timestamp); ?>">
                                    <h5><?php esc_html_e('Sale Ends In:', 'adforest-elementor'); ?></h5>
                                    <div class="adt-deal-countdown-desc-clock">
                                        <?php foreach (['days', 'hours', 'minutes', 'seconds'] as $unit) : ?>
                                            <div class="adt-countdown-clock-div">
                                                <div class="adt-countdown-clock-inner">
                                                    <span class="adt-countdown-clock-title"
                                                          id="<?php echo $unit; ?>">0</span>
                                                </div>
                                                <span class="adt-countdown-clock-heading"><?php echo ucfirst($unit); ?></span>
                                            </div>
                                            <?php if ($unit !== 'seconds') : ?>
                                                <div class="adt-coundown-svg">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                         viewBox="0 0 5 5" fill="none">
                                                        <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                    </svg>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"
                                                         viewBox="0 0 5 5" fill="none">
                                                        <circle cx="2.5" cy="2.5" r="2.5" fill="black"></circle>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT PANEL -->
                            <?php if ( $show_categories && $has_categories ) { ?>
                                <?php
                                $cols        = max(1, min(4, intval( $s['right_columns'] )));
                                $bootstrap_col = 'col-md-' . intval( 12 / $cols );
                                $half_gutter = $gutter / 2;
                                ?>
                                <div class="adv-right-panel row" style="margin: -<?php echo esc_attr( $half_gutter ); ?>px;">
                                    <?php foreach ( $s['categories_list'] as $item ) :
                                        if ( empty( $item['cat_id'] ) ) { continue; }
                                        $term = get_term( $item['cat_id'], 'product_cat' );
                                        $count = $term ? $term->count : 0;

                                        $image_url = $item['cat_image']['url'] ?? '';

                                        $title = $item['custom_title']
                                            ?: ( $term ? $term->name : __( 'Category', 'adforest-elementor' ) );
                                        $count_text = $item['custom_count_text']
                                            ?: sprintf( _n( '%s Product', '%s Products', $count, 'adforest-elementor' ), $count );
                                        ?>
                                        <div class="<?php echo esc_attr( $bootstrap_col ); ?>" style="padding: <?php echo esc_attr( $half_gutter ); ?>px;">
                                            <div class="adt-ecommerce-advertise-box"
                                                 style="background-image: url('<?php echo esc_url( $image_url ); ?>');
                                                         background-repeat: no-repeat;
                                                         background-position: center center;
                                                         background-size: cover;">
                                                <a href="<?php echo esc_url( $item['cat_link']['url'] ); ?>" class="adt-ecom-advertise-inner">
                                                    <p class="title"><?php echo esc_html( $title ); ?></p>
                                                    <p class="subtitle"><?php echo esc_html( $count_text ); ?></p>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}