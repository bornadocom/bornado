<?php
class Adforest_Custom_Search extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'custom_search_widget',
            __('Custom Search Widget', 'adforest'),
            array('description' => __('A custom search widget for posts', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        echo wp_kses_post( $args['before_widget'] );
        $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
        ?>
        <div class="adt-search-list-box">
            <h4><?php echo esc_html($title); ?></h4>
            <form action="<?php echo esc_url(home_url('/')); ?>" method="get">
                <div class="form-field">
                    <label for="search" class="form-label"><?php _e('Search', 'adforest'); ?></label>
                    <input type="text" class="form-control" id="search" name="s"
                           placeholder="<?php _e('Search by Name', 'adforest'); ?>">
                    <button type="submit" class="search-btn-title"><i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <?php
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Search Filter', 'adforest');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

        return $instance;
    }
}

class Adforest_Recent_Ads_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'adforest_recent_ads_widget',
            __('Recent Ads Widget', 'adforest'),
            array(
                'description' => __('A widget to display recent ad_post entries (simple, featured, or both).', 'adforest'),
            )
        );
    }

    /**
     * Front‐end display of the widget.
     */
    public function widget($args, $instance) {
        echo wp_kses_post($args['before_widget']);

        // Title
        $title = ! empty($instance['title'])
            ? apply_filters('widget_title', $instance['title'])
            : '';

        // Number of posts
        $number_of_posts = ! empty($instance['number_of_posts'])
            ? absint($instance['number_of_posts'])
            : 5;

        // Ad type: 'all', 'simple', or 'featured'
        $ad_type = ! empty($instance['ad_type'])
            ? sanitize_text_field($instance['ad_type'])
            : 'all';

        ?>
        <div class="adt-recent-ads-sidebar">
            <?php if ($title) : ?>
                <h4><?php echo esc_html($title); ?></h4>
            <?php endif; ?>

            <ul>
                <?php
                // Build base args for wp_get_recent_posts
                $query_args = array(
                    'numberposts' => $number_of_posts,
                    'post_status'  => 'publish',
                    'post_type'    => 'ad_post',
                );

                // If admin selected “Featured Ads” only:
                if ($ad_type === 'featured') {
                    $query_args['meta_key']   = '_adforest_is_feature';
                    $query_args['meta_value'] = '1';
                }
                // If admin selected “Simple Ads” only:
                elseif ($ad_type === 'simple') {
                    // Assume that simple ads have _adforest_is_feature meta = '0' (or missing).
                    // You can adjust this compare if your logic differs.
                    $query_args['meta_key']   = '_adforest_is_feature';
                    $query_args['meta_value'] = '0';
                }
                // else 'all' → no meta filter, show both simple & featured

                // Fetch the recent ad_post entries
                $recent_posts = wp_get_recent_posts($query_args);

                foreach ($recent_posts as $post) {
                    $post_id        = $post['ID'];
                    $post_title     = wp_trim_words($post['post_title'], 5, '...');
                    $post_permalink = get_permalink($post_id);

                    // Determine price type and thumbnail:
                    $post_price_type = get_post_meta($post_id, '_adforest_ad_price_type', true);
                    $image_thumbnail_size      = 'adforest-single-post';
                    $widget_img      = '';
                    $media = adforest_get_ad_images($post_id);
                    $img = (is_array($media) && isset($media[0])) ? wp_get_attachment_image_src($media[0], $image_thumbnail_size) : null;

                    $widget_img = isset($img[0]) ? $img[0] : "";

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

                                <?php if ($post_price_type === 'no_price') : ?>
                                    <strong><?php echo esc_html__('Free', 'adforest'); ?></strong>
                                <?php else : ?>
                                    <strong>
                                        <?php
                                        // Assuming adforest_adPrice() returns properly escaped HTML
                                        echo adforest_adPrice($post_id, 'negotiable-single', '');
                                        ?>
                                    </strong>
                                <?php endif; ?>
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

    /**
     * Back‐end widget form.
     */
    public function form($instance) {
        // Retrieve existing values or set defaults
        $title            = ! empty($instance['title']) ? $instance['title'] : __('Recent Ads', 'adforest');
        $number_of_posts  = ! empty($instance['number_of_posts']) ? absint($instance['number_of_posts']) : 5;
        $ad_type_selected = ! empty($instance['ad_type']) ? sanitize_text_field($instance['ad_type']) : 'all';
        ?>
        <!-- Title field -->
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'adforest'); ?>
            </label>
            <input
                    class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                    type="text"
                    value="<?php echo esc_attr($title); ?>">
        </p>

        <!-- Number of posts field -->
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>">
                <?php esc_html_e('Number of posts to show:', 'adforest'); ?>
            </label>
            <input
                    class="tiny-text"
                    id="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('number_of_posts')); ?>"
                    type="number"
                    step="1"
                    min="1"
                    value="<?php echo esc_attr($number_of_posts); ?>"
                    size="3">
        </p>

        <!-- Ad Type dropdown (all / simple / featured) -->
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('ad_type')); ?>">
                <?php esc_html_e('Ad Type:', 'adforest'); ?>
            </label>
            <select
                    id="<?php echo esc_attr($this->get_field_id('ad_type')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('ad_type')); ?>"
                    class="widefat"
                    style="margin-top: 4px;">
                <?php
                // Define the three options
                $options = array(
                    'all'      => esc_html__('All Ads', 'adforest'),
                    'simple'   => esc_html__('Simple Ads', 'adforest'),
                    'featured' => esc_html__('Featured Ads', 'adforest'),
                );
                foreach ($options as $value => $label) {
                    printf(
                        '<option value="%1$s" %2$s>%3$s</option>',
                        esc_attr($value),
                        selected($ad_type_selected, $value, false),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </p>
        <?php
    }

    /**
     * Sanitize and save widget form values.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();

        $instance['title'] = (! empty($new_instance['title']))
            ? sanitize_text_field($new_instance['title'])
            : '';

        $instance['number_of_posts'] = (! empty($new_instance['number_of_posts']))
            ? absint($new_instance['number_of_posts'])
            : 5;

        $allowed_types = array('all', 'simple', 'featured');
        $instance['ad_type'] = in_array($new_instance['ad_type'], $allowed_types, true)
            ? $new_instance['ad_type']
            : 'all';

        return $instance;
    }
}

class Adforest_Recent_Post_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'adforest_recent_posts_widget',
            __('Recent Posts Widget', 'adforest'),
            array('description' => __('A widget to display recent posts', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        echo wp_kses_post( $args['before_widget'] );
        $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
        $number_of_posts = empty($instance['number_of_posts']) ? 5 : $instance['number_of_posts'];
        $title_limit = empty($instance['title_limit']) ? 18 : $instance['title_limit'];
        ?>
        <div class="adt-recent-ads-sidebar">
            <h4><?php echo esc_html($title); ?></h4>
            <ul>
                <?php
                $recent_posts = wp_get_recent_posts(
                    array(
                        'numberposts' => $number_of_posts,
                        'post_status' => 'publish',
                        'post_type' => 'post',
                    )
                );
                foreach ($recent_posts as $post) {
                    $post_id = $post['ID'];
                    $post_title = truncate_string($post['post_title'], $title_limit);
                    $post_permalink = get_permalink($post_id);
                    $post_thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');

                    if (empty($post_thumbnail)) {
                        $post_thumbnail = trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';
                    }
                    ?>
                    <li>
                        <div class="adt-recent-ad-box">
                            <a href="<?php echo esc_url($post_permalink); ?>" class="recent-img-box">
                                <img class="img-fluid" src="<?php echo esc_url($post_thumbnail); ?>"
                                     alt="<?php echo esc_attr($post_title); ?>">
                            </a>
                            <div class="recent-img-meta">
                                <a href="<?php echo esc_url($post_permalink); ?>">
                                    <h6><?php echo esc_html($post_title); ?></h6>
                                </a>
                                <strong><?php echo get_the_date('F j, Y', $post_id); ?></strong>
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
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Recent Ads', 'adforest');
        $number_of_posts = !empty($instance['number_of_posts']) ? absint($instance['number_of_posts']) : 5;
        $title_limit = !empty($instance['title_limit']) ? absint($instance['title_limit']) : 18;
        ?>
        <p>
            <label
                    for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label
                    for="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>"><?php esc_html_e('Number of posts to show:', 'adforest'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('number_of_posts')); ?>" type="number" step="1"
                   min="1"
                   value="<?php echo esc_attr($number_of_posts); ?>" size="3">
        </p>
        <p>
            <label
                    for="<?php echo esc_attr($this->get_field_id('title_limit')); ?>"><?php esc_html_e('Title Limit:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title_limit')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title_limit')); ?>" type="text"
                   value="<?php echo esc_attr($title_limit); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number_of_posts'] = (!empty($new_instance['number_of_posts'])) ? absint($new_instance['number_of_posts']) : 5;
        $instance['title_limit'] = (!empty($new_instance['title_limit'])) ? absint($new_instance['title_limit']) : 18;

        return $instance;
    }
}

class Adforest_Titles_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_title_search_filter_widget',
            __('Search Ads With Title', 'adforest'),
            array('description' => __('Search Ads with title *Only for Search*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Search Filter', 'adforest');
        echo wp_kses_post( $args['before_widget'] );
        $widget_layout = adforest_search_layout();
        if ($widget_layout == 'sidebar') {
            $expand = '';
            $collapsed = 'collapsed';
            if (isset($instance['open_widget']) && $instance['open_widget'] == '1') {
                $expand = 'show';
                $collapsed = '';
            }
            if (isset($_GET['ad_title']) && $_GET['ad_title'] != "") {
                $expand = 'show';
                $collapsed = '';
            }
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?php echo esc_attr($collapsed); ?>" type="button" data-bs-toggle="collapse"
                            data-bs-target="#panelsStayOpen-collapseZero" aria-expanded="true"
                            aria-controls="panelsStayOpen-collapseZero">
                        <?php echo esc_html($title); ?>
                    </button>
                </h2>
                <div id="panelsStayOpen-collapseZero" class="accordion-collapse collapse <?php echo esc_attr($expand); ?>">
                    <div class="accordion-body">
                        <div class="adt-search-list-box">
                            <?php
                            require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/sidebar/title.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/title.php';
        }
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Search Filter', 'adforest');
        if (isset($instance['default_value'])) {
            $default_value = $instance['default_value'];
        } else {
            $default_value = esc_html__('Default Value', 'adforest');
        }
        if (isset($instance['edit_able'])) {
            $edit_able = $instance['edit_able'];
        }
        if (isset($instance['show_hide'])) {
            $show_hide = $instance['show_hide'];
        }
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

class Adforest_Radius_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_search_filter_widget',
            __('Search Widget For Radius Search', 'adforest'),
            array('description' => __('Search Ads for Radius Search *Only for Search*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Radius Search', 'adforest');
        echo wp_kses_post( $args['before_widget'] );
        $widget_layout = adforest_search_layout();
        if ($widget_layout == 'sidebar') {
            $expand = '';
            $collapsed = 'collapsed';
            if (isset($instance['open_widget']) && $instance['open_widget'] == '1') {
                $expand = 'show';
                $collapsed = '';
            }
            if ((isset($_GET['location']) && $_GET['location'] != "") || (isset($_GET['rd']) && $_GET['rd'] != "")) {
                $expand = 'show';
                $collapsed = '';
            }
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?php echo esc_attr($collapsed); ?>" type="button" data-bs-toggle="collapse"
                            data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true"
                            aria-controls="panelsStayOpen-collapseOne">
                        <?php echo esc_html($title); ?>
                    </button>
                </h2>
                <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse <?php echo esc_attr($expand); ?>">
                    <div class="accordion-body">
                        <div class="adt-search-list-box">
                            <?php
                            require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/sidebar/radius-search.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/radius-search.php';
        }
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Search Filter', 'adforest');
        if (isset($instance['default_value'])) {
            $default_value = $instance['default_value'];
        } else {
            $default_value = esc_html__('Default Value', 'adforest');
        }
        if (isset($instance['show_hide'])) {
            $show_hide = $instance['show_hide'];
        }
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p><?php echo __("For Location and Radius", "adforest"); ?></p>
        <label for="<?php echo esc_attr($this->get_field_id('default_value')); ?>">
            <?php echo esc_html__('Default Value: Assigns a default Value for radius', 'adforest'); ?>
        </label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('default_value')); ?>"
               name="<?php echo esc_attr($this->get_field_name('default_value')); ?>" type="number"
               value="<?php echo esc_attr($default_value); ?>">
        <label for="<?php echo esc_attr($this->get_field_id('show_hide')); ?>">
            <?php echo esc_html__('Show/Hide:', 'adforest'); ?>
        </label>
        <select name="<?php echo esc_attr($this->get_field_name('show_hide')); ?>"
                id="<?php echo esc_attr($this->get_field_id('show_hide')); ?>" class="widefat">
            <option value="show" <?php if (isset($show_hide) && $show_hide == 'show') {
                echo 'selected';
            } ?> >Show
            </option>
            <option value="hide" <?php if (isset($show_hide) && $show_hide == 'hide') {
                echo 'selected';
            } ?>>Hide
            </option>
        </select>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['default_value'] = (!empty($new_instance['default_value'])) ? strip_tags($new_instance['default_value']) : '';
        $instance['show_hide'] = (!empty($new_instance['show_hide'])) ? strip_tags($new_instance['show_hide']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

class Adforest_Category_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_category_search_widget',
            __('Category Search Widget', 'adforest'),
            array('description' => __('Search Ads with Categories *Only for Search*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        echo wp_kses_post( $args['before_widget'] );
        $widget_layout = adforest_search_layout();

        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/categories.php';

        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Categories', 'adforest');
        $no_of_cats = !empty($instance['no_of_cats']) ? $instance['no_of_cats'] : 0;
        $this->adforect_widget_open($instance);
        $this->adforect_enable_showmore_on_cats($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
            <?php
            global $adforest_theme;
            if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] == 'sidebar' || $adforest_theme['search_design'] == "map") { ?>
                <label for="<?php echo esc_attr($this->get_field_id('no_of_cats')); ?>"><?php _e('No of Categories to Show before Show More:', 'adforest'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('no_of_cats')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('no_of_cats')); ?>" type="number"
                       value="<?php echo esc_attr($no_of_cats); ?>">
            <?php } ?>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';
        $instance['show_more_cate'] = (!empty($new_instance['show_more_cate'])) ? strip_tags($new_instance['show_more_cate']) : '';
        $instance['no_of_cats'] = (!empty($new_instance['no_of_cats'])) ? strip_tags($new_instance['no_of_cats']) : '';

        return $instance;
    }
}

class Adforest_Category_Select_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_category_select_search_widget',
            __('Category Select Search Widget', 'adforest'),
            array('description' => __('Search Ads with Categories (Select2) *Only for Search*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        echo wp_kses_post( $args['before_widget'] );
        $widget_layout = adforest_search_layout();

        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/categories-select.php';

        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Categories', 'adforest');
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Select a category', 'adforest');
        $show_counts = !empty($instance['show_counts']) ? $instance['show_counts'] : 0;
        $enable_cascading = !empty($instance['enable_cascading']) ? $instance['enable_cascading'] : 0;
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('placeholder')); ?>"><?php _e('Placeholder:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('placeholder')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('placeholder')); ?>" type="text"
                   value="<?php echo esc_attr($placeholder); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_counts, '1'); ?> id="<?php echo esc_attr($this->get_field_id('show_counts')); ?>" name="<?php echo esc_attr($this->get_field_name('show_counts')); ?>" value="1" />
            <label for="<?php echo esc_attr($this->get_field_id('show_counts')); ?>"><?php _e('Show ad counts', 'adforest'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($enable_cascading, '1'); ?> id="<?php echo esc_attr($this->get_field_id('enable_cascading')); ?>" name="<?php echo esc_attr($this->get_field_name('enable_cascading')); ?>" value="1" />
            <label for="<?php echo esc_attr($this->get_field_id('enable_cascading')); ?>"><?php _e('Enable cascading child selects', 'adforest'); ?></label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';
        $instance['placeholder'] = (!empty($new_instance['placeholder'])) ? sanitize_text_field($new_instance['placeholder']) : '';
        $instance['show_counts'] = (!empty($new_instance['show_counts'])) ? strip_tags($new_instance['show_counts']) : '';
        $instance['enable_cascading'] = (!empty($new_instance['enable_cascading'])) ? strip_tags($new_instance['enable_cascading']) : '';

        return $instance;
    }
}

class Adforest_Price_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_price_search_widget',
            __('Price Search Widget', 'adforest'),
            array('description' => __('Search Ads with price *Only for Search*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $widget_layout = adforest_search_layout();
        echo wp_kses_post( $args['before_widget'] );

        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/price.php';

        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Ad Price', 'adforest');

        if (isset($instance['min_price'])) {
            $min_price = $instance['min_price'];
        } else {
            $min_price = 1;
        }

        if (isset($instance['max_price'])) {
            $max_price = $instance['max_price'];
        } else {
            $max_price = esc_html__('10000000', 'adforest');
        }
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('min_price')); ?>">
                <?php echo esc_html__('Min Price:', 'adforest'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('min_price')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('min_price')); ?>" type="text"
                   value="<?php echo esc_attr($min_price); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('max_price')); ?>">
                <?php echo esc_html__('Max Price:', 'adforest'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('max_price')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('max_price')); ?>" type="text"
                   value="<?php echo esc_attr($max_price); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['min_price'] = (!empty($new_instance['min_price'])) ? strip_tags($new_instance['min_price']) : 0;
        $instance['max_price'] = (!empty($new_instance['max_price'])) ? strip_tags($new_instance['max_price']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

class Adforest_Ad_Type_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_ad_type_search_widget',
            __('Ad Type Search Widget', 'adforest'),
            array('description' => __('Search Ads with Ad Type *Only for Search*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $widget_layout = adforest_search_layout();
        echo wp_kses_post( $args['before_widget'] );
        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/ad_type.php';

        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Ad Type', 'adforest');
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

class Adforest_Warranty_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_warranty_search_widget',
            __('Warranty Search Widget', 'adforest'),
            array('description' => __('Search Ads with Warranty *Only for Search*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $widget_layout = adforest_search_layout();
        echo wp_kses_post( $args['before_widget'] );
        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/warranty.php';
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Warranty', 'adforest');
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

class Adforest_Ad_Location_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_ad_location_search_widget',
            __('Ad Location Search Widget', 'adforest'),
            array('description' => __('A widget to search Ads with Location *Only for Search Page*', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $widget_layout = adforest_search_layout();
        echo wp_kses_post( $args['before_widget'] );
        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/ad_location.php';
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Location', 'adforest');
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

class Adforest_Condition_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_condition_search_widget',
            __('Condition Search Widget', 'adforest'),
            array('description' => __('Search Ads with Condition * Only for Search *', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $widget_layout = adforest_search_layout();
        echo wp_kses_post( $args['before_widget'] );
        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/condition.php';
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Condition', 'adforest');
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

class Adforest_Currency_Search_Widget extends WP_Widget
{
    use adforest_reuse_functions;

    public function __construct()
    {
        parent::__construct(
            'adforest_currency_search_widget',
            __('Currency Search Widget', 'adforest'),
            array('description' => __('Search Ads with Currency * Only for Search *', 'adforest'))
        );
    }

    public function widget($args, $instance)
    {
        $widget_layout = adforest_search_layout();
        echo wp_kses_post( $args['before_widget'] );
        require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/ad_currency.php';
        echo wp_kses_post( $args['after_widget'] );
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Currency', 'adforest');
        $this->adforect_widget_open($instance);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

        return $instance;
    }
}

/* Ad Countries widget */
if (!class_exists('Adforest_Ad_Countries')) {
    class Adforest_Ad_Countries extends WP_Widget
    {
        use adforest_reuse_functions;

        /**
         * Register widget with WordPress.
         */
        function __construct()
        {
            $widget_ops = array(
                'classname' => 'Adforest_Ad_Countries',
                'description' => __('Only for search', 'adforest'),
            );
            // Instantiate the parent object
            parent::__construct(false, __('Country Location', 'adforest'), $widget_ops);
        }

        /**
         * Front-end display of widget.
         *
         *
         * @param array $args Widget arguments.
         * @param array $instance Saved values from database.
         *
         * @see WP_Widget::widget()
         *
         */
        public function widget($args, $instance)
        {

            $new = '';
            $used = '';
            $expand = "";
            $toggle = "collapsed";
            if (isset($_GET['country_id']) && $_GET['country_id'] != "") {
                $expand = "show";
                $toggle = "";
            }
            global $adforest_theme;
            $widget_layout = adforest_search_layout();
            require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/countries.php';
        }

        /**
         * Back-end widget form.
         *
         * @param array $instance Previously saved values from database.
         *
         * @see WP_Widget::form()
         *
         */
        public function form($instance)
        {
            if (isset($instance['title'])) {
                $title = $instance['title'];
            } else {
                $title = esc_html__('Country Location', 'adforest');
            }

            $this->adforect_widget_open($instance);
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                    <?php echo esc_html__('Title:', 'adforest'); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>">
            </p>
            <?php
        }

        /**
         * Sanitize widget form values as they are saved.
         *
         * @param array $new_instance Values just sent to be saved.
         * @param array $old_instance Previously saved values from database.
         *
         * @return array Updated safe values to be saved.
         * @see WP_Widget::update()
         *
         */
        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
            $instance['default_value'] = (!empty($new_instance['default_value'])) ? strip_tags($new_instance['default_value']) : '';
            $instance['edit_able'] = (!empty($new_instance['edit_able'])) ? strip_tags($new_instance['edit_able']) : '';

            $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

            return $instance;
        }
    }
    /* Location widget */
}

/* Advertisement  Widget */
if (!class_exists('adforest_search_advertisement')) {
    class adforest_search_advertisement extends WP_Widget
    {
        use adforest_reuse_functions;

        /**
         * Register widget with WordPress.
         */
        function __construct()
        {
            $widget_ops = array(
                'classname' => 'adforest_search_advertisement',
                'description' => __('Only for search and single ad sidebar.', 'adforest'),
            );
            // Instantiate the parent object
            parent::__construct(false, __('Adforest Advertisement', 'adforest'), $widget_ops);
        }

        /**
         * Front-end display of widget.
         *
         * @param array $args Widget arguments.
         * @param array $instance Saved values from database.
         *
         * @see WP_Widget::widget()
         *
         */
        public function widget($args, $instance)
        {
            $ad_code = isset($instance['ad_code']) ? $instance['ad_code'] : '';
            $ad_type = isset($instance['ad_type']) ? $instance['ad_type'] : 'image';
            global $adforest_theme;
            ?>
            <div class="adt-vertical-ad-box">
                <?php
                if ( function_exists( 'adforest_render_ad' ) ) {
                    adforest_render_ad( $ad_type, $ad_code );
                } else {
                    echo "" . $ad_code;
                }
                ?>
            </div>
            <?php
        }

        /**
         * Back-end widget form.
         *
         * @param array $instance Previously saved values from database.
         *
         * @see WP_Widget::form()
         *
         */
        public function form($instance)
        {
            $ad_code = '';
            if (isset($instance['ad_code'])) {
                $ad_code = $instance['ad_code'];
            }
            $ad_type = isset($instance['ad_type']) ? $instance['ad_type'] : 'image';
            $this->adforect_widget_open($instance);
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('ad_type')); ?>">
                    <?php echo esc_html__('Ad Type:', 'adforest'); ?>
                </label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('ad_type')); ?>"
                        name="<?php echo esc_attr($this->get_field_name('ad_type')); ?>">
                    <option value="image" <?php selected($ad_type, 'image'); ?>><?php echo esc_html__('Image', 'adforest'); ?></option>
                    <option value="custom_html" <?php selected($ad_type, 'custom_html'); ?>><?php echo esc_html__('Custom HTML', 'adforest'); ?></option>
                    <option value="adsense" <?php selected($ad_type, 'adsense'); ?>><?php echo esc_html__('Google AdSense', 'adforest'); ?></option>
                </select>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('ad_code')); ?>">
                    <?php echo esc_html__('Code:', 'adforest'); ?>
                </label>
                <textarea class="widefat" id="<?php echo esc_attr($this->get_field_id('ad_code')); ?>"
                          name="<?php echo esc_attr($this->get_field_name('ad_code')); ?>"
                          type="text"><?php echo esc_attr($ad_code); ?></textarea>
                <small><?php echo esc_html__('For AdSense: paste your full AdSense code (script + ins tags).', 'adforest'); ?></small>
            </p>
            <?php
        }

        /**
         * Sanitize widget form values as they are saved.
         *
         * @param array $new_instance Values just sent to be saved.
         * @param array $old_instance Previously saved values from database.
         *
         * @return array Updated safe values to be saved.
         * @see WP_Widget::update()
         *
         */
        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['ad_code'] = (!empty($new_instance['ad_code'])) ? $new_instance['ad_code'] : '';
            $allowed_types = array('image', 'custom_html', 'adsense');
            $instance['ad_type'] = (isset($new_instance['ad_type']) && in_array($new_instance['ad_type'], $allowed_types, true)) ? $new_instance['ad_type'] : 'image';
            $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

            return $instance;
        }
    }
    /* Advertisement */
}

if (!class_exists('WooCommerce_Category_Product_Widget')) {
    class WooCommerce_Category_Product_Widget extends WP_Widget
    {

        public function __construct()
        {
            parent::__construct(
                'woocommerce_category_product_widget', // Base ID
                'WooCommerce Category Product Widget', // Widget Name
                array('description' => __('Select a category and display products', 'adforest'))
            );
        }

        public function widget($args, $instance)
        {
            echo wp_kses_post( $args['before_widget'] );

            $selected_category = isset($instance['category']) ? $instance['category'] : '';
            $posts_per_page = isset($instance['posts_per_page']) ? $instance['posts_per_page'] : 5;

            // Widget HTML content
            echo '<div class="adt-recent-ads-sidebar">';
            echo '<h4>' . (!empty($instance['title']) ? $instance['title'] : __('Products', 'adforest')) . '</h4>';
            echo '<ul>';
            $this->display_products($selected_category, $posts_per_page);
            echo '</ul>';
            echo '</div>';
            echo wp_kses_post( $args['after_widget'] );
        }

        // Form in the Widget admin panel
        public function form($instance)
        {
            $title = !empty($instance['title']) ? $instance['title'] : '';
            $category = !empty($instance['category']) ? $instance['category'] : '';
            $posts_per_page = !empty($instance['posts_per_page']) ? $instance['posts_per_page'] : 5;

            // Title field
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'adforest'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>">
            </p>

            <!-- Select WooCommerce Category -->
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('category')); ?>"><?php esc_html_e('Select Category:', 'adforest'); ?></label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('category')); ?>"
                        name="<?php echo esc_attr($this->get_field_name('category')); ?>">
                    <option value=""><?php _e('Select a Category', 'adforest'); ?></option>
                    <?php
                    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
                    foreach ($categories as $cat) {
                        echo '<option value="' . esc_attr($cat->term_id) . '"' . selected($cat->term_id, $category, false) . '>' . esc_html($cat->name) . '</option>';
                    }
                    ?>
                </select>
            </p>

            <!-- Posts Per Page -->
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('posts_per_page')); ?>"><?php esc_html_e('Number of Products:', 'adforest'); ?></label>
                <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('posts_per_page')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('posts_per_page')); ?>" type="number"
                       step="1"
                       min="1" value="<?php echo esc_attr($posts_per_page); ?>">
            </p>
            <?php
        }

        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
            $instance['category'] = (!empty($new_instance['category'])) ? sanitize_text_field($new_instance['category']) : '';
            $instance['posts_per_page'] = (!empty($new_instance['posts_per_page'])) ? intval($new_instance['posts_per_page']) : 5;

            return $instance;
        }

        // Display products
        private function display_products($category_id, $posts_per_page)
        {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $posts_per_page,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category_id,
                    ),
                ),
            );
            $products = new WP_Query($args);

            if ($products->have_posts()) {
                while ($products->have_posts()) {
                    $products->the_post();
                    global $product;
                    ?>
                    <li>
                        <div class="adt-recent-ad-box">
                            <a href="<?php the_permalink(); ?>" class="recent-img-box">
                                <?php echo woocommerce_get_product_thumbnail('thumbnail'); ?>
                            </a>
                            <div class="recent-img-meta">
                                <a href="<?php the_permalink(); ?>"><h6><?php the_title(); ?></h6></a>
                                <strong><?php echo wp_kses_post($product->get_price_html()); ?></strong>
                            </div>
                        </div>
                    </li>
                    <?php
                }
                wp_reset_postdata();
            } else {
                echo '<li>' . __('No products found.', 'adforest') . '</li>';
            }
        }
    }
}

if (!class_exists('Woocommerce_Title_Search_Widget')) {
    class Woocommerce_Title_Search_Widget extends WP_Widget
    {
        use adforest_reuse_functions;
        public function __construct()
        {
            parent::__construct(
                'adforest_woocommerce_title_search_widget',
                __('Woocommerce Title Search Widget', 'adforest'),
                array('description' => __('Search Ads *Only for Woocommerce shop*', 'adforest'))
            );
        }

        public function widget($args, $instance)
        {
            echo wp_kses_post( $args['before_widget'] );
            require trailingslashit(get_template_directory()) . 'woocommerce/widgets/title.php';
            echo wp_kses_post( $args['after_widget'] );
        }

        public function form($instance)
        {
            $title = !empty($instance['title']) ? $instance['title'] : __('Search', 'adforest');
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>">
            </p>
            <?php
        }

        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
            $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';
            return $instance;
        }
    }
}

if (!class_exists('Woocommerce_Search_Widget')) {
    class Woocommerce_Search_Widget extends WP_Widget
    {
        use adforest_reuse_functions;
        public function __construct()
        {
            parent::__construct(
                'adforest_woocommerce_category_search_widget',
                __('Woocommerce Categories Search Widget', 'adforest'),
                array('description' => __('Search Ads *Only for Woocommerce shop*', 'adforest'))
            );
        }

        public function widget($args, $instance)
        {
            echo wp_kses_post( $args['before_widget'] );
            require trailingslashit(get_template_directory()) . 'woocommerce/widgets/search.php';
            echo wp_kses_post( $args['after_widget'] );
        }

        public function form($instance)
        {
            $this->adforect_enable_showmore_on_cats($instance);
            $no_of_cats = !empty($instance['no_of_cats']) ? $instance['no_of_cats'] : 0;
            $title = !empty($instance['title']) ? $instance['title'] : __('Search', 'adforest');
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>">
            </p>
            <p>
            <label for="<?php echo esc_attr($this->get_field_id('no_of_cats')); ?>"><?php _e('No of Categories to Show before Show More:', 'adforest'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('no_of_cats')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('no_of_cats')); ?>" type="number"
                   value="<?php echo esc_attr($no_of_cats); ?>">
            </p>
            <?php
        }

        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
            $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';
            $instance['show_more_cate'] = (!empty($new_instance['show_more_cate'])) ? strip_tags($new_instance['show_more_cate']) : '';
            $instance['no_of_cats'] = (!empty($new_instance['no_of_cats'])) ? strip_tags($new_instance['no_of_cats']) : '';
            return $instance;
        }
    }
}

if (!class_exists('Woocommerce_Price_Search_Widget')) {
    class Woocommerce_Price_Search_Widget extends WP_Widget
    {
        public function __construct()
        {
            parent::__construct(
                'adforest_woocommerce_price_search_widget',
                __('Woocommerce Price Search', 'adforest'),
                array('description' => __('Search Ads with price *Only for Woocommerce*', 'adforest'))
            );
        }

        public function widget($args, $instance)
        {
            $widget_layout = adforest_search_layout();
            echo wp_kses_post( $args['before_widget'] );
            require trailingslashit(get_template_directory()) . 'woocommerce/widgets/price.php';
            echo wp_kses_post( $args['after_widget'] );
        }

        public function form($instance)
        {
            $title = !empty($instance['title']) ? $instance['title'] : __('Price', 'adforest');

            if (isset($instance['min_price'])) {
                $min_price = $instance['min_price'];
            } else {
                $min_price = 1;
            }

            if (isset($instance['max_price'])) {
                $max_price = $instance['max_price'];
            } else {
                $max_price = esc_html__('10000000', 'adforest');
            }
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'adforest'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('min_price')); ?>">
                    <?php echo esc_html__('Min Price:', 'adforest'); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('min_price')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('min_price')); ?>" type="text"
                       value="<?php echo esc_attr($min_price); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('max_price')); ?>">
                    <?php echo esc_html__('Max Price:', 'adforest'); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('max_price')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('max_price')); ?>" type="text"
                       value="<?php echo esc_attr($max_price); ?>">
            </p>
            <?php
        }

        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
            $instance['min_price'] = (!empty($new_instance['min_price'])) ? strip_tags($new_instance['min_price']) : 0;
            $instance['max_price'] = (!empty($new_instance['max_price'])) ? strip_tags($new_instance['max_price']) : '';

            return $instance;
        }
    }
}

if (!class_exists('Woocommerce_Rating_Search_Widget')) {
    class Woocommerce_Rating_Search_Widget extends WP_Widget
    {
        public function __construct()
        {
            parent::__construct(
                'adforest_wc_rating_search_widget',
                __('Woocommerce Rating Search Widget', 'adforest'),
                array('description' => __('Search Ads with rating *Only for Woocommerce shop*', 'adforest'))
            );
        }

        public function widget($args, $instance)
        {
            echo wp_kses_post( $args['before_widget'] );
            require trailingslashit(get_template_directory()) . 'woocommerce/widgets/rating.php';
            echo wp_kses_post( $args['after_widget'] );
        }

        public function form($instance)
        {
            $title = !empty($instance['title']) ? $instance['title'] : __('Search Rating', 'adforest');
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo esc_html__('Title:', 'adforest'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>">
            </p>
            <?php
        }

        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
            $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

            return $instance;
        }
    }
}

/* ------------------------------------------------------------------------------------- */
/* Custom Search */
if (!class_exists('adforest_search_custom_fields')) {

    class adforest_search_custom_fields extends WP_Widget
    {

        use adforest_reuse_functions;

        /**
         * Register widget with WordPress.
         */
        function __construct()
        {
            $widget_ops = array(
                'classname' => 'adforest_search_custom_fields',
                'description' => __('Only for search and single ad sidebar.', 'adforest'),
            );
            // Instantiate the parent object
            parent::__construct(false, __('Custom Fields Search', 'adforest'), $widget_ops);
        }

        /**
         * Front-end display of widget.
         *
         * @param array $args Widget arguments.
         * @param array $instance Saved values from database.
         *
         * @see WP_Widget::widget()
         *
         */
        public function widget($args, $instance)
        {
            $ad_code = '';
            if (isset($instance['ad_code'])) {
                $ad_code = $instance['ad_code'];
            }
            global $adforest_theme;
            $term_id = '';
            $customHTML = '';
            $widget_layout = adforest_search_layout();


            require trailingslashit(get_template_directory()) . 'template-parts/layouts/widgets/' . $widget_layout . '/custom.php';
            echo "" . $customHTML;
        }

        /**
         * Back-end widget form.
         *
         * @param array $instance Previously saved values from database.
         *
         * @see WP_Widget::form()
         *
         */
        public function form($instance)
        {

            $title = (isset($instance['title'])) ? $instance['title'] : esc_html__('Search By:', 'adforest');
            $this->adforect_widget_open($instance);
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                    <?php echo esc_html__('Title:', 'adforest'); ?>
                    <small><?php echo esc_html__('You can leave it empty as well', 'adforest'); ?></small>
                </label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>">
            <p><?php echo esc_html__('You can show/hide the specific type from categories custom fields where you created it.', 'adforest'); ?> </p>
            </p>

            <?php
        }

        /**
         * Sanitize widget form values as they are saved.
         *
         * @param array $new_instance Values just sent to be saved.
         * @param array $old_instance Previously saved values from database.
         *
         * @return array Updated safe values to be saved.
         * @see WP_Widget::update()
         *
         */
        public function update($new_instance, $old_instance)
        {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
            $instance['open_widget'] = (!empty($new_instance['open_widget'])) ? strip_tags($new_instance['open_widget']) : '';

            return $instance;
        }

    }

    /* Custom Search */
}
/* Custom Search */
/* ------------------------------------------------------------------------------------- */

add_action('widgets_init', function () {
    if (function_exists('adforest_register_custom_widgets')) {
        adforest_register_custom_widgets('Adforest_Custom_Search');
        adforest_register_custom_widgets('Adforest_Recent_Ads_Widget');
        adforest_register_custom_widgets('adforest_search_advertisement');
        adforest_register_custom_widgets('Adforest_Radius_Search_Widget');
        adforest_register_custom_widgets('Adforest_Titles_Search_Widget');
        adforest_register_custom_widgets('Adforest_Category_Search_Widget');
        adforest_register_custom_widgets('Adforest_Category_Select_Search_Widget');
        adforest_register_custom_widgets('Adforest_Price_Search_Widget');
        adforest_register_custom_widgets('Adforest_Ad_Type_Search_Widget');
        adforest_register_custom_widgets('Adforest_Warranty_Search_Widget');
        adforest_register_custom_widgets('Adforest_Currency_Search_Widget');
        adforest_register_custom_widgets('Adforest_Ad_Location_Search_Widget');
        adforest_register_custom_widgets('Adforest_Condition_Search_Widget');
        adforest_register_custom_widgets('Adforest_Ad_Countries');
        adforest_register_custom_widgets('adforest_search_custom_fields');
//        adforest_register_custom_widgets('WooCommerce_Category_Product_Widget');
        adforest_register_custom_widgets('Woocommerce_Title_Search_Widget');
        adforest_register_custom_widgets('Woocommerce_Search_Widget');
        adforest_register_custom_widgets('Woocommerce_Price_Search_Widget');
        adforest_register_custom_widgets('Woocommerce_Rating_Search_Widget');
        adforest_register_custom_widgets('Adforest_Recent_Post_Widget');
    }
});