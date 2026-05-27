<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class MultivendorProductsGrid extends Widget_Base
{

    public function get_name()
    {
        return 'adforest_multivendor_products_grid';
    }

    public function get_title()
    {
        return __('Multivendor Products Grid', 'adforest_elementor');
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
            'main_section',
            ['label' => __('Main', 'adforest_elementor')]
        );
        $this->add_control(
            'section_title',
            [
                'label' => __('Title', 'adforest_elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Our Products', 'adforest_elementor'),
            ]
        );
        $this->add_control(
            'btn_title',
            [
                'label' => __('Button Title', 'adforest_elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('View All', 'adforest_elementor'),
            ]
        );
        $this->add_control(
            'btn_link',
            [
                'label' => __('Link URL', 'adforest_elementor'),
                'type' => Controls_Manager::URL,
                'default' => ['url' => '#'],
            ]
        );
        $this->add_control(
            'btn_position',
            [
                'label' => __('Button Position', 'adforest_elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'top' => __('Top', 'adforest_elementor'),
                    'bottom' => __('Bottom', 'adforest_elementor'),
                ],
                'default' => 'bottom',
            ]
        );
        $this->add_control(
            'number_of_posts',
            [
                'label' => __('Number of Posts', 'adforest_elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
            ]
        );

        $this->add_control(
            'grid_type',
            [
                'label' => __('Grid Type', 'adforest_elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    1 => __('Grid Type 1', 'adforest_elementor'),
                    2 => __('Grid Type 2', 'adforest_elementor'),
                ],
                'default' => 1,
            ]
        );

        $this->add_control(
            'title_limit',
            [
                'label' => __('Title Character Limit', 'adforest_elementor'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 5,
            ]
        );

        $this->add_control(
            'grid_columns',
            [
                'label' => __('Grid Columns', 'adforest_elementor'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '2' => '2 Columns',
                    '3' => '3 Columns',
                    '4' => '4 Columns',
                    '5' => '5 Columns',
                ],
                'default' => '3',
            ]
        );
        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $section_title = $settings['section_title'];
        $btn_title = $settings['btn_title'];
        $btn_link_url = $settings['btn_link']['url'];
        $posts_per_page = absint($settings['number_of_posts']);
        $title_limit = absint($settings['title_limit']);
        $cols = max(1, min(6, intval($settings['grid_columns'])));
        $bootstrap_col = 'col-md-' . intval(12 / $cols);
        $half_gutter = intval($settings['gutter']['size']) / 2;

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $posts_per_page,
            'tax_query' => [
                [
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'simple',
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        $products = new \WP_Query($args);
        $total_posts = $products->found_posts;

        if (!$products->have_posts()) {
            return;
        }
        ?>
        <section class="adt-cyber-sale-products-section">
            <div class="container adt-container">
                <div class="row">
                    <div class="col-12">
                        <?php if ($section_title) : ?>
                            <div class="adt-ads-top-box">
                                <h2><?php echo esc_html($section_title); ?></h2>
                                <?php if ($settings['btn_position'] == 'top') { ?>
                                    <a href="<?php echo esc_url($btn_link_url); ?>">
                                        <button class="adt-button-1">
                                            <?php echo esc_html($btn_title); ?>
                                        </button>
                                    </a>
                                <?php } ?>
                            </div>
                        <?php endif; ?>

                        <div class="adt-cyberSale-product-grid-wrapper adt-cyberSale-product-grid-cols-<?php echo $settings['grid_columns'] ?>">
                            <?php
                            while ($products->have_posts()) {
                                $products->the_post();
                                global $product;
                                $heart_filled = 'fa-heart';
                                $fav_class = "";
                                if (get_user_meta(get_current_user_id(), '_product_fav_id_' . get_the_ID(), true) == get_the_ID()) {
                                    $fav_class = 'favourited';
                                    $heart_filled = 'fa-heart';
                                }

                                if (function_exists('adforest_product_grid_1') && $settings['grid_type'] == 1) {
                                    adforest_product_grid_1($product, $title_limit);
                                } else if (function_exists('adforest_product_grid_2') && $settings['grid_type'] == 2) {
                                    adforest_product_grid_2($product, [
                                        'title_limit' => $title_limit,
                                        'fav_class' => $fav_class,
                                        'heart_filled' => $heart_filled,
                                    ]);
                                }
                            }
                            wp_reset_postdata();
                            ?>
                        </div>

                        <?php
                        if ($settings['btn_position'] == 'bottom') {
                            if ($total_posts > $posts_per_page && $btn_link_url) { ?>
                                <div class="load-more-btn-box text-center mt-4">
                                    <a href="<?php echo esc_url($btn_link_url); ?>">
                                        <button class="adt-button-dark-1">
                                            <?php echo esc_html($btn_title); ?>
                                            <i class="fas fa-search" aria-hidden="true"></i>
                                        </button>
                                    </a>
                                </div>
                            <?php }
                        } ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}