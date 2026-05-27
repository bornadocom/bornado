<?php

/**

 * Customize AdForest Recent Ads sidebar widget in child theme only.

 *

 * - Show full ad titles (parent hard-codes wp_trim_words to 5 words).

 * - Show Negotiable (توافقی) for ads without a price.

 *

 * @package Bornado_Child

 */



if (!defined('ABSPATH')) {

    exit;

}



if (!function_exists('bornado_recent_ads_sidebar_preserve_title')) {

    /**

     * @param string $text

     * @param int    $num_words

     * @param string $more

     * @param string $original_text

     * @return string

     */

    function bornado_recent_ads_sidebar_preserve_title($text, $num_words, $more, $original_text)

    {

        if ((int) $num_words === 5 && '...' === (string) $more) {

            return $original_text;

        }



        return $text;

    }

}



if (!class_exists('Bornado_Recent_Ads_Widget') && class_exists('Adforest_Recent_Ads_Widget')) {

    class Bornado_Recent_Ads_Widget extends Adforest_Recent_Ads_Widget

    {

        /**

         * Front-end display with full titles and Negotiable fallback for empty prices.

         *

         * @param array $args     Widget wrapper args.

         * @param array $instance Widget instance settings.

         * @return void

         */

        public function widget($args, $instance)

        {

            echo wp_kses_post($args['before_widget']);



            $title = !empty($instance['title'])

                ? apply_filters('widget_title', $instance['title'])

                : '';



            $number_of_posts = !empty($instance['number_of_posts'])

                ? absint($instance['number_of_posts'])

                : 5;



            $ad_type = !empty($instance['ad_type'])

                ? sanitize_text_field($instance['ad_type'])

                : 'all';



            ?>

            <div class="adt-recent-ads-sidebar">

                <?php if ($title) : ?>

                    <h4><?php echo esc_html($title); ?></h4>

                <?php endif; ?>



                <ul>

                    <?php

                    $query_args = array(

                        'numberposts' => $number_of_posts,

                        'post_status' => 'publish',

                        'post_type'   => 'ad_post',

                    );



                    if ($ad_type === 'featured') {

                        $query_args['meta_key']   = '_adforest_is_feature';

                        $query_args['meta_value'] = '1';

                    } elseif ($ad_type === 'simple') {

                        $query_args['meta_key']   = '_adforest_is_feature';

                        $query_args['meta_value'] = '0';

                    }



                    add_filter('wp_trim_words', 'bornado_recent_ads_sidebar_preserve_title', 10, 4);

                    $recent_posts = wp_get_recent_posts($query_args);

                    remove_filter('wp_trim_words', 'bornado_recent_ads_sidebar_preserve_title', 10);



                    foreach ($recent_posts as $post) {

                        $post_id        = $post['ID'];

                        $post_title     = wp_trim_words($post['post_title'], 5, '...');

                        $post_permalink = get_permalink($post_id);

                        $image_thumbnail_size = 'adforest-single-post';

                        $widget_img           = '';

                        $media                = adforest_get_ad_images($post_id);

                        $img                  = (is_array($media) && isset($media[0])) ? wp_get_attachment_image_src($media[0], $image_thumbnail_size) : null;



                        $widget_img = isset($img[0]) ? $img[0] : '';



                        if (empty($widget_img)) {

                            $widget_img = trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';

                        }

                        ?>

                        <li>

                            <div class="adt-recent-ad-box">

                                <a href="<?php echo esc_url($post_permalink); ?>" class="recent-img-box">

                                    <img class="img-fluid"

                                         src="<?php echo esc_url($widget_img); ?>"

                                         alt="<?php echo esc_attr($post_title); ?>">

                                </a>

                                <div class="recent-img-meta">

                                    <a href="<?php echo esc_url($post_permalink); ?>">

                                        <h6><?php echo esc_html($post_title); ?></h6>

                                    </a>



                                    <strong>

                                        <?php echo adforest_adPrice($post_id, 'negotiable-single', ''); ?>

                                    </strong>

                                </div>

                            </div>

                        </li>

                        <?php

                    }



                    wp_reset_query();

                    ?>

                </ul>

            </div>

            <?php

            echo wp_kses_post($args['after_widget']);

        }

    }

}



if (!function_exists('bornado_register_recent_ads_widget_override')) {

    /**

     * Replace the parent Recent Ads widget with the child-theme version.

     *

     * @return void

     */

    function bornado_register_recent_ads_widget_override()

    {

        if (!class_exists('Adforest_Recent_Ads_Widget') || !class_exists('Bornado_Recent_Ads_Widget')) {

            return;

        }



        unregister_widget('Adforest_Recent_Ads_Widget');

        register_widget('Bornado_Recent_Ads_Widget');

    }



    add_action('widgets_init', 'bornado_register_recent_ads_widget_override', 20);

}


