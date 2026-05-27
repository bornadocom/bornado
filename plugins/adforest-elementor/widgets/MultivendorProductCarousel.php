<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit;

class MultivendorProductCarousel extends Widget_Base
{
    public function get_name()
    {
        return 'adforest_multivendor_product_carousel';
    }

    public function get_title()
    {
        return __("Multivendor Product Carousel", 'adforest_elementor');
    }

    public function get_icon()
    {
        return 'fa fa-audio-description';
    }

    public function get_categories()
    {
        return ['adforest_widgets'];
    }

    private function get_product_categories_options()
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        $options = [];

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->term_id] = $term->name;
            }
        }

        return $options;
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'main', [
            'label' => esc_html__('Main', 'adforest-elementor'),
        ]);

        $this->add_control(
            'section_title', [
            'label' => esc_html__('Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "Our Latest Products",
        ]);

        $this->add_control(
            'product_title_limit', [
            'label' => esc_html__('Product Title Limit', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 40,
        ]);

        $this->add_control(
            'product_categories', [
            'label' => esc_html__('Product Categories', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $this->get_product_categories_options(),
            'label_block' => true,
            'description' => esc_html__('Select one or more product categories to show products from.', 'adforest-elementor'),
        ]);

        $this->add_control(
            'btn_title', [
            'label' => esc_html__('Button Title', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "View All",
        ]);

        $this->add_control(
            'btn_link', [
            'label' => esc_html__('Button Link', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => "#",
        ]);

        $this->add_control(
            'grid_style', [
            'label' => esc_html__('Grid Style', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'modern' => __('Grid Type 1', "adforest-elementor"),
                'fancy' => __("Grid Type 2", "adforest-elementor"),
            ],
        ]);

        $this->add_control(
            'grid_cols', [
            'label' => esc_html__('Grid Columns', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '2' => __('Two', "adforest-elementor"),
                '3' => __("Three", "adforest-elementor"),
                '4' => __("Four", "adforest-elementor"),
                '5' => __("Five", "adforest-elementor"),
            ],
        ]);

        $this->add_control(
            'no_of_prd_to_show', [
            'label' => esc_html__('No of Products', 'adforest-elementor'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 10,
        ]);

        $this->add_control(
            'show_countdown',
            [
                'label' => esc_html__('Show Countdown?', 'adforest-elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '1' => esc_html__('Yes', 'adforest-elementor'),
                    '0' => esc_html__('No', 'adforest-elementor'),
                ],
                'default' => '0',
            ]
        );

        $this->add_control(
            'countdown_end',
            [
                'label' => __('Countdown End Date/Time', 'adforest-elementor'),
                'type' => Controls_Manager::DATE_TIME,
                'description' => __('When the sale ends.', 'adforest-elementor'),
                'default' => date('Y-m-d\TH:i', strtotime('+7 days')),
                'condition' => [
                    'show_countdown' => '1',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $s = $this->get_settings_for_display();

        $section_title = $s['section_title'];
        $btn_title = $s['btn_title'];
        $btn_link = $s['btn_link'];
        $title_limit = absint($s['product_title_limit']);
        $grid_style = $s['grid_style'];
        $grid_cols = absint($s['grid_cols']);
        $limit = absint($s['no_of_prd_to_show']);
        $categories = $s['product_categories'];
        $countdown_end = $s['countdown_end'];

        $args = [
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            'type' => 'simple',
        ];
        if (!empty($categories)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $categories,
                ],
            ];
        }

        $query = new \WC_Product_Query($args);
        $product_ids = $query->get_products();
        if (empty($product_ids)) {
            return;
        }

        $extra_class = '';
        if (isset($s['show_countdown']) && $s['show_countdown'] == 1) {
            $extra_class = 'adt-countdown-active';
        }

        ?>
        <section class="adt-mv-product-carousel <?php echo $extra_class; ?>">
            <div class="container adt-container">
                <div class="adt-ads-top-box">
                    <?php if (isset($s['show_countdown']) && $s['show_countdown'] == 1) { ?>
                        <div class="adt-counter-title">
                            <h2><?php echo esc_html($section_title); ?></h2>
                            <?php
                            $end_timestamp = strtotime($s['countdown_end']) * 1000;
                            ?>
                            <div class="adt-deal-countdown-desc-clock"
                                 data-countdown="<?php echo esc_attr($end_timestamp); ?>">
                                <?php foreach (['days', 'hours', 'minutes', 'seconds'] as $unit) : ?>
                                    <div class="adt-countdown-clock-div">
                                        <div class="adt-countdown-clock-inner">
                                            <span class="adt-countdown-clock-title"
                                                  data-unit="<?php echo $unit; ?>">0</span>
                                        </div>
                                        <span class="adt-countdown-clock-heading"><?php echo ucfirst($unit); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <h2><?php echo esc_html($section_title); ?></h2>
                    <?php } ?>

                    <?php if ($btn_title) { ?>
                        <a href="<?php echo esc_url($btn_link); ?>" class="adt-button-1">
                            <?php echo esc_html($btn_title); ?>
                        </a>
                    <?php } ?>
                </div>

                <div class="adt-multivendor-ads-carousel owl-carousel owl-theme"
                     data-columns="<?php echo esc_attr($grid_cols); ?>">
                    <?php foreach ($product_ids as $pid) :
                        $product = wc_get_product($pid);
                        if (!$product) continue;

                        $fav_class = '';
                        $heart_filled = 'fa-heart';
                        if (get_user_meta(get_current_user_id(), '_product_fav_id_' . $pid, true) == $pid) {
                            $fav_class = 'favourited';
                            $heart_filled = 'fa-heart';
                        }

                        if ($grid_style === 'modern' && function_exists('adforest_product_grid_1')) {
                            adforest_product_grid_1($product, $title_limit);
                        } elseif ($grid_style === 'fancy' && function_exists('adforest_product_grid_2')) {
                            adforest_product_grid_2($product, [
                                'title_limit' => $title_limit,
                                'fav_class' => $fav_class,
                                'heart_filled' => $heart_filled,
                            ]);
                        }
                    endforeach; ?>
                </div>
            </div>
        </section>

        <?php if (\Elementor\Plugin::$instance->editor->is_edit_mode()) : ?>
        <script>
            jQuery(function ($) {
                $('.adt-multivendor-ads-carousel').each(function () {
                    var $owl = $(this);
                    var cols = parseInt($owl.data('columns')) || 4;
                    $owl.owlCarousel({
                        loop: false,
                        rtl: $('html').attr('dir') === 'rtl',
                        margin: 5,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        responsive: {
                            0: {items: 1},
                            576: {items: Math.min(2, cols)},
                            768: {items: Math.min(3, cols)},
                            992: {items: Math.min(4, cols)},
                            1200: {items: cols}
                        }
                    });
                });
            });
        </script>
    <?php endif;
    }
}