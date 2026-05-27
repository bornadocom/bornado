<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Categories', 'adforest');
$enable_show_more_cats = !empty($instance['show_more_cate']) ? $instance['show_more_cate'] : 0;
$no_of_cats_before_show_more = !empty($instance['no_of_cats']) ? $instance['no_of_cats'] : 0;

// Check if we should show sub-categories
$search_show_sub_cats_with_parent = isset($adforest_theme['search_show_sub_cats_with_parent']) ? $adforest_theme['search_show_sub_cats_with_parent'] : false;
$current_cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

// Get parent category for back navigation
$parent_cat_id = 0;
$parent_cat_name = '';
if ($current_cat_id > 0) {
    $current_term = get_term($current_cat_id, 'ad_cats');
    if ($current_term && !is_wp_error($current_term) && $current_term->parent > 0) {
        $parent_term = get_term($current_term->parent, 'ad_cats');
        if ($parent_term && !is_wp_error($parent_term)) {
            $parent_cat_id = $parent_term->term_id;
            $parent_cat_name = $parent_term->name;
        }
    }
}

// Show categories when hide_cat_sidebar_map is NOT enabled (i.e., when we want to show the sidebar)
if (!isset($adforest_theme['hide_cat_sidebar_map']) || $adforest_theme['hide_cat_sidebar_map'] != '1') {
    ?>
    <h3><?php echo esc_html($title); ?></h3>
    <div>
        <?php
        $adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
        $adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
        $adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page, 'cat_page');
        ?>
        <form method="get" id="search_cats_w" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
            <input type="hidden" id="sb_show_sub_with_parent" value="<?php echo $search_show_sub_cats_with_parent ? '1' : '0'; ?>">
            <?php
            // If feature is enabled and we have a selected category, show its sub-categories
            if ($search_show_sub_cats_with_parent && $current_cat_id > 0) {
                $ad_categories = adforest_get_cats('ad_cats', $current_cat_id);
                $parent_category = get_term($current_cat_id, 'ad_cats');
                if ($parent_category && !is_wp_error($parent_category)) {
                    $title = $parent_category->name . ' - ' . __('Sub Categories', 'adforest');
                }
            } else {
                $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
            }
            
            // Filter out categories with zero ads if the option is enabled
            if (isset($adforest_theme['search_popup_cat_disable']) && $adforest_theme['search_popup_cat_disable'] == true) {
                $ad_categories = array_filter($ad_categories, function($category) {
                    $category_details = get_taxonomy_details($category);
                    return isset($category_details['ad_count']) && $category_details['ad_count'] > 0;
                });
            }
            
            if (is_array($ad_categories) && count($ad_categories) > 0) {
                if ($enable_show_more_cats && count($ad_categories) > $no_of_cats_before_show_more) {
                    $first_categories = array_slice($ad_categories, 0, $no_of_cats_before_show_more);
                    $rest_categories = array_slice($ad_categories, $no_of_cats_before_show_more);
                } else {
                    $first_categories = $ad_categories;
                    $rest_categories = [];
                }
                ?>
                <div class="adt-category-list-sidebar" style="padding: 0">
                    <?php
                    if (isset($_GET['cat_id']) && $_GET['cat_id'] != "") {
                        $selected_cats = adforest_get_taxonomy_parents($_GET['cat_id'], 'ad_cats', false);
                        $find = '&raquo;';
                        $replace = '';
                        $selected_cats = preg_replace("/$find/", $replace, $selected_cats, 1);
                        echo adforest_return_echo($selected_cats);
                    }
                    ?>
                    
                    <?php if ($search_show_sub_cats_with_parent && $current_cat_id > 0): ?>
                        <!-- Back to parent categories button -->
                        <div style="margin-bottom: 15px;">
                            <?php if ($parent_cat_id > 0): ?>
                                <!-- Back to immediate parent -->
                                <a href="<?php echo esc_url(add_query_arg('cat_id', $parent_cat_id, remove_query_arg('cat_id'))); ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> <?php echo esc_html(sprintf(__('Back to %s', 'adforest'), $parent_cat_name)); ?>
                                </a>
                            <?php else: ?>
                                <!-- Back to all categories -->
                                <a href="<?php echo esc_url(remove_query_arg('cat_id')); ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> <?php esc_html_e('Back to All Categories', 'adforest'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <ul>
                        <!-- Display first set of categories -->
                        <?php
                        if (is_array($first_categories) && count($first_categories) > 0) {
                            foreach ($first_categories as $category) {
                                $category_details = get_taxonomy_details($category);
                                $name = $category_details['name'];
                                $ad_count = $category_details['ad_count'];
                                $image = $category_details['image'];
                                $icon   = $category_details['icon'];
								$display_mode = $category_details['display_mode'];
                                $link = $category_details['link'];
                                $category_search_page = apply_filters('adforest_filter_taxonomy_popup_actions', 'javascript:void(0);', $category->term_id, 'ad_cats');
                                ?>
                                <li>
                                    <div class="adt-category-box">
                                        <div class="category-meta">
                                            <a href="<?php echo esc_url($category_search_page); ?>"
                                               class="img-box category_click_link"
                                               data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                <?php if ($display_mode === 'icon' && !empty($icon)) : ?>
                                                    <div class="<?php echo esc_attr($icon); ?>"></div>
                                                <?php else : ?>
                                                    <img class="img-fluid" src="<?php echo esc_url($image); ?>"
                                                     alt="<?php echo esc_attr($name); ?>">
                                                <?php endif; ?>
                                            </a>
                                            <a href="<?php echo esc_url($category_search_page); ?>"
                                               class="category_click_link"
                                               data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                <?php echo esc_html($name); ?>
                                            </a>
                                        </div>
                                        <span class="listing-count"><?php echo esc_html($ad_count) . ' ' . esc_html__('ads', 'adforest'); ?></span>
                                    </div>
                                </li>
                            <?php }
                        } ?>

                        <!-- If there are extra categories, add Show More / Show Less links -->
                        <?php if (!empty($rest_categories)) { ?>
                            <?php foreach ($rest_categories as $category) {
                                $category_details = get_taxonomy_details($category);
                                $name = $category_details['name'];
                                $ad_count = $category_details['ad_count'];
                                $image = $category_details['image'];
                                $icon   = $category_details['icon'];
								$display_mode = $category_details['display_mode'];
                                $link = $category_details['link'];
                                $category_search_page = apply_filters('adforest_filter_taxonomy_popup_actions', 'javascript:void(0);', $category->term_id, 'ad_cats');
                                ?>
                                <li class="hidden-category" style="display: none;">
                                    <div class="adt-category-box">
                                        <div class="category-meta">
                                            <a href="<?php echo esc_url($category_search_page); ?>"
                                               class="img-box category_click_link"
                                               data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                <?php if ($display_mode === 'icon' && !empty($icon)) : ?>
                                                    <div class="<?php echo esc_attr($icon); ?>"></div>
                                                <?php else : ?>
                                                    <img class="img-fluid" src="<?php echo esc_url($image); ?>"
                                                     alt="<?php echo esc_attr($name); ?>">
                                                <?php endif; ?>
                                            </a>
                                            <a href="<?php echo esc_url($category_search_page); ?>"
                                               class="category_click_link"
                                               data-cat-id="<?php echo esc_attr($category->term_id); ?>">
                                                <?php echo esc_html($name); ?>
                                            </a>
                                        </div>
                                        <span class="listing-count"><?php echo esc_html($ad_count) . ' ' . esc_html__('ads', 'adforest'); ?></span>
                                    </div>
                                </li>
                            <?php } ?>
                            <!-- Wrapper for the toggle links -->
                            <li class="toggle-wrapper">
                                <a href="javascript:void(0);"
                                   id="showMoreCategories"><?php _e('Show More', 'adforest'); ?></a>
                                <a href="javascript:void(0);" id="showLessCategories"
                                   style="display: none;"><?php _e('Show Less', 'adforest'); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
            <input type="hidden" name="cat_id" id="cat_id" value=""/>
            <?php echo adforest_search_params('cat_id'); ?>
            <?php apply_filters('adforest_form_lang_field', true); ?>
        </form>
    </div>
    <?php
} else { ?>
    <!-- Categories are hidden when hide_cat_sidebar_map is enabled -->
    <?php if (isset($adforest_theme['hide_cat_sidebar_map']) && $adforest_theme['hide_cat_sidebar_map'] == '1'): ?>
        <!-- Categories are hidden by theme option -->
        <div style="display: none;">
            <!-- Categories widget content is hidden -->
        </div>
    <?php else: ?>
        <!-- No categories found (fallback) -->
        <h3><?php echo esc_html($title); ?></h3>
        <div>
            <?php if ($search_show_sub_cats_with_parent && $current_cat_id > 0): ?>
                <!-- Back to parent categories button -->
                <div style="margin-bottom: 15px;">
                    <?php if ($parent_cat_id > 0): ?>
                        <!-- Back to immediate parent -->
                        <a href="<?php echo esc_url(add_query_arg('cat_id', $parent_cat_id, remove_query_arg('cat_id'))); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo esc_html(sprintf(__('Back to %s', 'adforest'), $parent_cat_name)); ?>
                        </a>
                    <?php else: ?>
                        <!-- Back to all categories -->
                        <a href="<?php echo esc_url(remove_query_arg('cat_id')); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> <?php esc_html_e('Back to All Categories', 'adforest'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="alert alert-info">
                    <?php esc_html_e('No sub-categories found for this category.', 'adforest'); ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php esc_html_e('No categories found.', 'adforest'); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php }
?>