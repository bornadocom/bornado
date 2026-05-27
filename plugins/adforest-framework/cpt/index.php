<?php
/*Register post  type and taxonomy*/
add_action('init', 'adforest_themes_custom_types', 0);

if (!function_exists('adforest_themes_custom_types')) {
    function adforest_themes_custom_types()
    {

        global $adforest_theme;
        /*Register Post type*/
        $args = array(
            'public' => true,
            'label' => __('Countries', 'redux-framework'),
            'supports' => array('thumbnail', 'title')
        );
        register_post_type('_sb_country', $args);
        /*Register Post type*/
        $args = array(
            'public' => true,
            'label' => __('Classified Ads', 'redux-framework'),
            'supports' => array('title', 'thumbnail', 'editor', 'author'),
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'rewrite' => array('with_front' => false, 'slug' => 'ad')
        );
        register_post_type('ad_post', $args);
        /*Ads Cats*/
        register_taxonomy('ad_cats', array('ad_post'), array(
                'hierarchical' => true,
                'show_ui' => true,
                'label' => __('Categories', 'redux-framework'),
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'ad_category', 'with_front' => false,),
            )
        );
        /*Ads Country*/
        $labels = array(
            'name' => _x('Ad Locations', 'taxonomy general name', 'redux-framework'),
            'singular_name' => _x('Location', 'taxonomy singular name', 'redux-framework'),
            'search_items' => __('Search Ad Locations', 'redux-framework'),
            'all_items' => __('All Ad Locations', 'redux-framework'),
            'parent_item' => __('Parent Location', 'redux-framework'),
            'parent_item_colon' => __('Parent Location:', 'redux-framework'),
            'edit_item' => __('Edit Location', 'redux-framework'),
            'update_item' => __('Update Location', 'redux-framework'),
            'add_new_item' => __('Add New Location', 'redux-framework'),
            'new_item_name' => __('New Location Name', 'redux-framework'),
            'menu_name' => __('Ad Locations', 'redux-framework'),
        );
        /*Ads Country*/
        register_taxonomy('ad_country', array('ad_post'), array(
                'hierarchical' => true,
                'show_ui' => true,
                'labels' => $labels,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'ad_country'),
            )
        );

        /*Ads tags*/
        register_taxonomy('ad_tags', array('ad_post'), array(
                'hierarchical' => false,
                'label' => __('Tags', 'redux-framework'),
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'ad_tag'),
            )
        );

        /*Ads Currency*/
        register_taxonomy('ad_currency', array('ad_post'), array(
                'hierarchical' => true,
                'label' => __('Currency', 'redux-framework'),
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'ad_currency'),
            )
        );
        /*Ads Condition*/
        register_taxonomy('ad_condition', array('ad_post'), array(
                'hierarchical' => true,
                'label' => __('Condition', 'redux-framework'),
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'ad_condition'),
            )
        );
        /*Ads Type*/
        register_taxonomy('ad_type', array('ad_post'), array(
                'hierarchical' => true,
                'label' => __('Type', 'redux-framework'),
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'ad_type'),
            )
        );
        /*Ads warranty*/
        register_taxonomy('ad_warranty', array('ad_post'), array(
                'hierarchical' => true,
                'label' => __('Warranty', 'redux-framework'),
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'ad_warranty'),
            )
        );


        if (isset($adforest_theme['allow_claim']) && $adforest_theme['allow_claim']) {

            /*claim post type*/
            $args = array(
                'public' => true,
                'menu_icon' => 'dashicons-shield',
                'capability_type' => 'post',
                'capabilities' => array(// 'create_posts' => false, // false < WP 4.5, credit @Ewout
                ),
                'map_meta_cap' => true,
                'label' => __('Claims', 'redux-framework'),
                'supports' => array('title')
            );
            register_post_type('ad_claims', $args);
        }
    }


}

/*Register metaboxes for Products*/
add_action('add_meta_boxes', 'adforest_meta_box_ads');

if (!function_exists('adforest_meta_box_ads')) {
    function adforest_meta_box_ads()
    {
        // Start From Here on Monday
        add_meta_box('sb_thmemes_adforest_metaboxes', __('Reported', 'redux-framework'), 'sb_render_meta_ads', 'ad_post', 'normal', 'high');
        add_meta_box('sb_theme_adforest_metaboxes', __('Bids', 'redux-framework'), 'adforest_render_bids_admin', 'ad_post', 'normal', 'high');


    }
}

if (!function_exists('sb_render_meta_ads')) {
    function sb_render_meta_ads($post)
    {
        global $wpdb;
        $pid = $post->ID;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s", intval($pid), '_sb_user_id_%'));
        ?>
        <div class="margin_top">
            <h3><?php echo count($rows); ?><?php echo __('Users report to this AD.', 'redux-framework'); ?></h3>
            <ul type="square">
                <?php
                foreach ($rows as $row) {
                    $user = get_userdata($row->meta_value);
                    ?>
                    <li>
                        <p>
                            <strong>
                                <?php if (isset($user->display_name))
                                    echo esc_html($user->display_name); ?>
                            </strong> <?php echo __('mark as', 'redux-framework'); ?>
                            <strong>
                                <?php echo esc_html(get_post_meta($pid, '_sb_report_option_' . $row->meta_value, true)); ?>
                            </strong>
                        </p>
                        <p><?php echo esc_html(get_post_meta($pid, '_sb_report_comments_' . $row->meta_value, true)); ?></p>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <?php
    }
}

if (!function_exists('adforest_render_bids_admin')) {
    function adforest_render_bids_admin($post)
    {
        global $adforest_theme;
        $curreny = isset($adforest_theme['sb_currency']) ? $adforest_theme['sb_currency'] : "";
        ?>
        <div class="margin_top">
            <table class="wp-list-table widefat fixed striped users">
                <tr>
                    <th width="15%"><strong><?php echo __('Bidder', 'redux-framework'); ?></strong></th>
                    <th width="15%"><strong><?php echo __('Bid', 'redux-framework'); ?></strong></th>
                    <th width="15%"><strong><?php echo __('Time', 'redux-framework'); ?></strong></th>
                    <th width="45%"><strong><?php echo __('Comment', 'redux-framework'); ?></strong></th>
                    <th width="10%"><strong><?php echo __('Action', 'redux-framework'); ?></strong></th>
                </tr>

                <?php
                global $wpdb;
                $have_bids = true;
                $biddings = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s ORDER BY meta_id DESC", intval($post->ID), '_adforest_bid_%'), OBJECT);
                if (count($biddings) > 0) {

                    $sr = 1;
                    foreach ($biddings as $bid) {
                        /*date - comment - user - offer*/
                        $data_array = explode('_separator_', $bid->meta_value);

                        $bidder_id = $data_array[2];
                        $bid_date = $data_array[0];
                        $offer = substr($data_array[3], 0, 12);
                        $comment = $data_array[1];

                        if (get_post_meta($post->ID, '_adforest_ad_currency', true) != "") {
                            $curreny = get_post_meta($post->ID, '_adforest_ad_currency', true);
                        }

                        $user_info = get_userdata($bidder_id);
                        $bidder_name = 'demo';
                        $user_profile = 'javascript:void(0);';
                        if (isset($user_info->display_name) && $user_info->display_name != "") {
                            $bidder_name = $user_info->display_name;
                            $user_profile = get_author_posts_url($bidder_id) . '?type=ads';
                            $have_bids = false;
                        } else {
                            continue;
                        }


                        $user_html = '<a class="text-black" href="' . $user_profile . '" target="_blank">' . $bidder_name . '</a>';
                        ?>
                        <tr>
                            <td><?php echo($user_html); ?></td>
                            <td><?php echo esc_html($offer) . '<span>(' . $curreny . ')</span>'; ?></td>
                            <td><?php echo($bid_date); ?></td>
                            <td><?php echo esc_html($comment); ?></td>
                            <td><a href="javascript:void(0);" class="bids-in-admin"
                                   data-bid-meta="<?php echo esc_attr($bid->meta_id); ?>"><?php echo __('Delete', 'redux-framework'); ?></a>
                            </td>

                        </tr>

                        <?php
                    }
                }
                if ($have_bids) {
                    echo '<tr><td colspan="5">' . __('There is no bid on this ad yet.', 'redux-framework') . '</td></tr>';
                }
                ?>

            </table>
        </div>
        <?php
    }
}
/*Register metaboxes for Products*/
add_action('add_meta_boxes', 'sb_rane_meta_box_add');

if (!function_exists('sb_rane_meta_box_add')) {
    function sb_rane_meta_box_add()
    {
        add_meta_box('sb_thmemes_adforest_metaboxes', __('Package Essentials', 'redux-framework'), 'sb_render_meta_product', 'product', 'normal', 'high');
        add_meta_box('feature_expiry_meta', __('Package Essentials', 'redux-framework'), 'sb_render_meta_feature_ad_product', 'product', 'normal', 'high');

    }
}

if (!function_exists('sb_render_meta_feature_ad_product')) {
    function sb_render_meta_feature_ad_product()
    {
        global $post;
        ?>
        <div class="margin_top">
            <p><?php echo __('Featured Ad Expiry (in days)', 'redux-framework'); ?></p>
            <input type="text" name="package_ad_featured_expiry_days" class="project_meta"
                   placeholder="<?php echo esc_attr__('Like 30, 40 or 50 but must be an inter value.', 'redux-framework'); ?>"
                   size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "ppp_package_adFeatured_expiry_days", true)); ?>"
                   id="package_ad_featured_expiry_days" spellcheck="true" autocomplete="off">
            <div style="background-color: red; color: white; padding: 2px;">
                <?php echo __('Featured ads expiry in days, -1 means never experies. If featured expiry field is empty then it will use the expiry days from Theme Options.', 'redux-framework'); ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('sb_render_meta_product')) {
    function sb_render_meta_product($post)
    {
        /*We'll use this nonce field later on when saving.*/
        global $adforest_theme;
        wp_nonce_field('my_meta_box_nonce_product', 'meta_box_nonce_product');
        /*$get_all_cats = adforest_sb_get_cats('ad_cats', true);*/
        $get_all_cats = array();
        $terms_args = array(
            'taxonomy' => 'ad_cats',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => 0,
            'hierarchical' => true,
        );
        $cat_pkg_type = isset($adforest_theme['cat_pkg_type']) && $adforest_theme['cat_pkg_type'] != '' ? $adforest_theme['cat_pkg_type'] : 'parent';
        if ($cat_pkg_type == 'parent') {
            $terms_args['parent'] = 0;
        }
        if (taxonomy_exists('ad_cats')) {
            $get_all_cats = get_terms($terms_args);
        }

        $selected_categories = get_post_meta($post->ID, "package_allow_categories", true);
        $selected_categories = isset($selected_categories) && !empty($selected_categories) ? $selected_categories : '';
        $selected_categories_arr = array();
        if ($selected_categories != '' && $selected_categories != 'all') {
            $selected_categories_arr = explode(",", $selected_categories);
        }
        ?>
        <!-- <div class="margin_top">
            <p><?php echo __('Package BG Color', 'redux-framework'); ?></p>
            <select name="package_bg_color" style="width:100%; height:40px;">
                <option value="light" <?php if (get_post_meta($post->ID, "package_bg_color", true) == 'new')
            echo 'selected'; ?>>
                    <?php echo esc_html__('White', 'redux-framework'); ?></option>
                <option value="dark" <?php if (get_post_meta($post->ID, "package_bg_color", true) == 'dark')
            echo 'selected'; ?>>
                    <?php echo esc_html__('Dark', 'redux-framework'); ?></option>
            </select>
        </div> -->

        <div class="margin_top">
            <p><?php echo __('Package Expiry', 'redux-framework'); ?></p>
            <input type="text" name="package_expiry_days" class="project_meta"
                   placeholder="<?php echo esc_attr__('Like 30, 40 or 50 but must be an inter value.', 'redux-framework'); ?>"
                   size="30" value="<?php echo esc_attr(get_post_meta($post->ID, "package_expiry_days", true)); ?>"
                   id="package_expiry_days" spellcheck="true" autocomplete="off">
            <div><?php echo __('Expiry in days, -1 means never experied unless used it.', 'redux-framework'); ?></div>
        </div>
        <div>
            <p><?php echo __('Simple Ads', 'redux-framework'); ?></p>
            <input type="text" name="package_free_ads" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an inter value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "package_free_ads", true)); ?>"
                   id="package_free_ads"
                   spellcheck="true" autocomplete="off">
            <div><?php echo __('-1 means unlimited.', 'redux-framework'); ?></div>
        </div>

        <div class="margin_top">
            <p><?php echo __('Simple Ad Expiry (in days)', 'redux-framework'); ?></p>
            <input type="text" name="package_ad_expiry_days" class="project_meta"
                   placeholder="<?php echo esc_attr__('Like 30, 40 or 50 but must be an inter value.', 'redux-framework'); ?>"
                   size="30" value="<?php echo esc_attr(get_post_meta($post->ID, "package_ad_expiry_days", true)); ?>"
                   id="package_ad_expiry_days" spellcheck="true" autocomplete="off">
            <div style="background-color: red; color: white; padding: 2px;">
                <?php echo __('Simple ads expiry in days, -1 means never experies. If simple expiry field is empty then it will use the expiry days from Theme Options.', 'redux-framework'); ?>
            </div>
        </div>


        <div>
            <p><?php echo __('Featured Ads', 'redux-framework'); ?></p>
            <input type="text" name="package_featured_ads" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an inter value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "package_featured_ads", true)); ?>"
                   id="package_featured_ads"
                   spellcheck="true" autocomplete="off">
            <div><?php echo __('-1 means unlimited.', 'redux-framework'); ?></div>
        </div>

        <div class="margin_top">
            <p><?php echo __('Featured Ad Expiry (in days)', 'redux-framework'); ?></p>
            <input type="text" name="package_adFeatured_expiry_days" class="project_meta"
                   placeholder="<?php echo esc_attr__('Like 30, 40 or 50 but must be an inter value.', 'redux-framework'); ?>"
                   size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "package_adFeatured_expiry_days", true)); ?>"
                   id="package_adFeatured_expiry_days" spellcheck="true" autocomplete="off">
            <div style="background-color: red; color: white; padding: 2px;">
                <?php echo __('Featured ads expiry in days, -1 means never experies. If featured expiry field is empty then it will use the expiry days from Theme Options.', 'redux-framework'); ?>
            </div>
        </div>


        <div>
            <p><?php echo __('Bump Ads', 'redux-framework'); ?></p>
            <input type="text" name="package_bump_ads" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an inter value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "package_bump_ads", true)); ?>"
                   id="package_bump_ads"
                   spellcheck="true" autocomplete="off">
        </div>
        <div><?php echo __('-1 means unlimited.', 'redux-framework'); ?></div>
        <div class="margin_top">

            <p><?php echo __('Allow Bidding', 'redux-framework'); ?></p>
            <input type="text" name="package_allow_bidding" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an integer value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "package_allow_bidding", true)); ?>"
                   id="package_allow_bidding" spellcheck="true" autocomplete="off">
            <div><?php echo __('-1 means unlimited.', 'redux-framework'); ?></div>
        </div>


        <div class="margin_top">

            <p><?php echo __('Make Bidding paid  on Ad detail page', 'redux-framework'); ?></p>
            <input type="text" name="package_make_bidding_paid" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an integer value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "package_make_bidding_paid", true)); ?>"
                   id="package_make_bidding_paid" spellcheck="true" autocomplete="off">
            <div>
                <?php echo __('-1 means unlimited.You would not  be able to post bidding on the ad detail page unless you enable bidding, which you may do via the settings menu.', 'redux-framework'); ?>
                <b>
                    ( dashboard &gt;&gt; Theme options &gt;&gt; Bidding settings ) </b></div>
        </div>


        <div class="margin_top">
            <p><?php echo __('Allow Tags', 'redux-framework'); ?></p>
            <select name="package_allow_tags" style="width:100%; height:40px;">
                <option value=""><?php echo esc_html__('Select an option', 'redux-framework'); ?> </option>
                <option value="yes" <?php if (get_post_meta($post->ID, "package_allow_tags", true) == 'yes')
                    echo 'selected'; ?>>
                    <?php echo esc_html__('Yes', 'redux-framework'); ?>
                </option>
                <option value="no" <?php if (get_post_meta($post->ID, "package_allow_tags", true) == 'no')
                    echo 'selected'; ?>>
                    <?php echo esc_html__('No', 'redux-framework'); ?>
                </option>
            </select>
        </div>
        <div>
            <p><?php echo __('Number of Images ( while ad posting )', 'redux-framework'); ?></p>
            <input type="text" name="package_num_of_images" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an integer value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "package_num_of_images", true)); ?>"
                   id="package_num_of_images" spellcheck="true" autocomplete="off">
            <div><?php echo __('-1 means unlimited.', 'redux-framework'); ?></div>
        </div>
        <div class="margin_top">
            <p><?php echo __('Allow Video Link ( while ad posting)', 'redux-framework'); ?></p>
            <select name="package_video_links" style="width:100%; height:40px;">
                <option value=""><?php echo esc_html__('Select an option', 'redux-framework'); ?> </option>
                <option value="yes" <?php if (get_post_meta($post->ID, "package_video_links", true) == 'yes')
                    echo 'selected'; ?>>
                    <?php echo esc_html__('Yes', 'redux-framework'); ?>
                </option>
                <option value="no" <?php if (get_post_meta($post->ID, "package_video_links", true) == 'no')
                    echo 'selected'; ?>>
                    <?php echo esc_html__('No', 'redux-framework'); ?>
                </option>
            </select>
        </div>


        <?php
        if (isset($adforest_theme['is_claim_paid']) && $adforest_theme['is_claim_paid']) { ?>
            <p><?php echo __('Claim ad', 'redux-framework'); ?></p>
            <input type="text" name="_sb_claim_ads" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an inter value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "_sb_claim_ads", true)); ?>" id="_sb_claim_ads"
                   spellcheck="true"
                   autocomplete="off">
            <?php
        }
        ?>
        <div class="margin_top">
            <p><?php echo __('Allow Categories ( while ad posting )', 'redux-framework'); ?></p>
            <select name="package_allow_categories[]" style="width:100%; height:100px;" multiple="multiple">
                <option value=""><?php echo esc_html__('Select categories', 'redux-framework'); ?> </option>
                <option value="all" <?php if (get_post_meta($post->ID, "package_allow_categories", true) == 'all')
                    echo 'selected'; ?>><?php echo esc_html__('All', 'redux-framework'); ?> </option>
                <?php
                if (isset($get_all_cats) && !empty($get_all_cats) && is_array($get_all_cats)) {
                    foreach ($get_all_cats as $single_cat) {
                        $selected_opt = '';
                        if (in_array($single_cat->term_id, $selected_categories_arr)) {
                            $selected_opt = ' selected ';
                        }

                        $adforest_make_cat_paid = get_term_meta($single_cat->term_id, 'adforest_make_cat_paid', true);
                        if (isset($adforest_make_cat_paid) && $adforest_make_cat_paid != 'yes')
                            continue;
                        ?>
                    <option <?php echo esc_html($selected_opt); ?> value="<?php echo intVal($single_cat->term_id); ?>">
                        <?php echo esc_html($single_cat->name); ?> </option><?php
                    }
                }
                ?>
            </select>
            <div><b>Note :
                </b><?php echo __('Load only those categories which are "Is paid" enabled in category meta. ( dashboard >> Classified Ads >> Categories >> Is paid checkbox field. )', 'redux-framework'); ?>
            </div>
        </div>


        <?php if (class_exists('SbPro')) { ?>
        <div class="">
            <p><?php echo __('Number of events', 'redux-framework'); ?></p>
            <input type="text" name="number_of_events" class="project_meta"
                   placeholder="<?php echo esc_attr__('Must be an inter value.', 'redux-framework'); ?>" size="30"
                   value="<?php echo esc_attr(get_post_meta($post->ID, "number_of_events", true)); ?>"
                   id="number_of_events"
                   spellcheck="true" autocomplete="off">
            <div><?php echo __('-1 means unlimited.', 'redux-framework'); ?></div>
        </div>
        <?php
    }
    }
}
/*Saving Metabox data */
add_action('save_post', 'sb_themes_meta_save_product', 10, 3);

if (!function_exists('sb_themes_meta_save_product')) {

    function sb_themes_meta_save_product($post_id, $post, $update)
    {
        /*Bail if we're doing an auto save*/
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        /*if our nonce isn't there, or we can't verify it, bail*/
        if (!isset($_POST['meta_box_nonce_product']) || !wp_verify_nonce($_POST['meta_box_nonce_product'], 'my_meta_box_nonce_product')) {
//            return;
        }

        /*if our current user can't edit this post, bail*/
        if (!current_user_can('edit_posts')) {
            return;
        }

        if ('product' !== $post->post_type) {
            return;
        }

        $pkg_cat_val = '';
        if (isset($_POST['package_allow_categories']) && !empty($_POST['package_allow_categories']) && in_array('all', $_POST['package_allow_categories'])) {
            $pkg_cat_val = 'all';
        } elseif (isset($_POST['package_allow_categories']) && !empty($_POST['package_allow_categories'])) {
            $pkg_cat_val = implode(",", $_POST['package_allow_categories']);
        }
        /*Make sure your data is set before trying to save it*/
        if (isset($_POST['package_bg_color'])) {
            update_post_meta($post_id, 'package_bg_color', $_POST['package_bg_color']);
        }
        if (isset($_POST['package_expiry_days'])) {
            update_post_meta($post_id, 'package_expiry_days', $_POST['package_expiry_days']);
        }
        if (isset($_POST['package_free_ads'])) {
            update_post_meta($post_id, 'package_free_ads', $_POST['package_free_ads']);
        }
        if (isset($_POST['package_featured_ads'])) {
            update_post_meta($post_id, 'package_featured_ads', $_POST['package_featured_ads']);
        }
        if (isset($_POST['package_bump_ads'])) {
            update_post_meta($post_id, 'package_bump_ads', $_POST['package_bump_ads']);
        }
        if (isset($_POST['package_video_links'])) {
            update_post_meta($post_id, 'package_video_links', $_POST['package_video_links']);
        }

        if (isset($_POST['_sb_claim_ads'])) {
            update_post_meta($post_id, '_sb_claim_ads', $_POST['_sb_claim_ads']);
        }
        if (isset($_POST['package_num_of_images'])) {
            update_post_meta($post_id, 'package_num_of_images', $_POST['package_num_of_images']);
        }
        if (isset($_POST['package_allow_tags'])) {
            update_post_meta($post_id, 'package_allow_tags', $_POST['package_allow_tags']);
        }
        if (isset($_POST['package_allow_bidding'])) {
            update_post_meta($post_id, 'package_allow_bidding', $_POST['package_allow_bidding']);
        }

        if (isset($_POST['package_make_bidding_paid'])) {
            update_post_meta($post_id, 'package_make_bidding_paid', $_POST['package_make_bidding_paid']);
        }
        if (isset($_POST['package_allow_categories'])) {
            update_post_meta($post_id, 'package_allow_categories', $pkg_cat_val);
        }

        if (isset($_POST['package_ad_expiry_days']) && $_POST['package_ad_expiry_days'] != "") {
            update_post_meta($post_id, 'package_ad_expiry_days', $_POST['package_ad_expiry_days']);
        } else {
            update_post_meta($post_id, 'package_ad_expiry_days', '');
        }
        if (isset($_POST['package_adFeatured_expiry_days']) && $_POST['package_adFeatured_expiry_days'] != "") {
            update_post_meta($post_id, 'package_adFeatured_expiry_days', $_POST['package_adFeatured_expiry_days']);
        } else {
            update_post_meta($post_id, 'package_adFeatured_expiry_days', '');
        }
        if (isset($_POST['number_of_events']) && $_POST['number_of_events'] != "") {
            update_post_meta($post_id, 'number_of_events', $_POST['number_of_events']);
        }
    }
}

/*Register metaboxes for Country CPT*/
add_action('add_meta_boxes', 'sb_meta_box_for_country');

if (!function_exists('sb_meta_box_for_country')) {
    function sb_meta_box_for_country()
    {
        add_meta_box('sb_metabox_for_country', 'Country', 'sb_render_meta_country', '_sb_country', 'normal', 'high');
    }
}

if (!function_exists('sb_render_meta_country')) {
    function sb_render_meta_country($post)
    {
        /*We'll use this nonce field later on when saving.*/
        wp_nonce_field('my_meta_box_nonce_country', 'meta_box_nonce_country');
        ?>
        <div class="margin_top">
            <input type="text" name="country_county" class="project_meta"
                   placeholder="<?php echo esc_attr__('Country', 'redux-framework'); ?>" size="30"
                   value="<?php echo get_the_excerpt($post->ID); ?>" id="country_county" spellcheck="true"
                   autocomplete="off">
            <p><?php echo __('This should be follow ISO2 like', 'redux-framework'); ?>
                <strong><?php echo __('US', 'redux-framework'); ?></strong>
                <?php echo __('for USA and', 'redux-framework'); ?>
                <strong><?php echo __('CA', 'redux-framework'); ?></strong>
                <?php echo __('for Canada', 'redux-framework'); ?>, <a
                        href="https://public.opendatasoft.com/explore/dataset/countries-codes/table/"
                        target="_blank"><?php echo __('Read More.', 'redux-framework'); ?></a></p>
        </div>

        <?php
    }
}


/*saving feature product essentials*/
add_action('save_post', 'save_feature_expiry_meta');
if (!function_exists('save_feature_expiry_meta')) {
    function save_feature_expiry_meta($post_id)
    {
        // Check if the user has permission to edit the post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check for autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save or update the meta field
        if (isset($_POST['package_ad_featured_expiry_days'])) {
            update_post_meta($post_id, 'ppp_package_adFeatured_expiry_days', sanitize_text_field($_POST['package_ad_featured_expiry_days']));
        }
    }
}


/*Saving Metabox data */
add_action('save_post', 'sb_themes_meta_save_country', 10, 3);

if (!function_exists('sb_themes_meta_save_country')) {
    function sb_themes_meta_save_country($post_id, $post, $update)
    {
        /*Bail if we're doing an auto save*/
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        /*if our nonce isn't there, or we can't verify it, bail*/
        if (!isset($_POST['meta_box_nonce_country']) || !wp_verify_nonce($_POST['meta_box_nonce_country'], 'my_meta_box_nonce_country')) {
            return;
        }

        /*if our current user can't edit this post, bail*/
        if (!current_user_can('edit_posts')) {
            return;
        }
        if ('_sb_country' !== $post->post_type) {
            return;
        }

        /*Make sure your data is set before trying to save it*/
        if (isset($_POST['country_county'])) {
            /*update_post_meta( $post_id, '_sb_country_county', $_POST['country_county'] );*/
            $my_post = array(
                'ID' => $post_id,
                'post_excerpt' => $_POST['country_county'],
            );
            global $wpdb;
            $county = sanitize_textarea_field($_POST['country_county']);
            $wpdb->update(
                $wpdb->posts,
                array('post_excerpt' => $county),
                array('ID' => (int)$post_id),
                array('%s'),
                array('%d')
            );
        }
    }
}
/*Add the fields to the "ad_cats" taxonomy, using our callback function*/
add_action('ad_cats_edit_form_fields', 'ad_cats_taxonomy_custom_fields', 10, 2);
add_action('l_event_cat_edit_form_fields', 'ad_cats_taxonomy_custom_fields', 10, 2);

/*A callback function to add a custom field to our "ad_cats" taxonomy */
if (!function_exists('ad_cats_taxonomy_custom_fields')) {
    function ad_cats_taxonomy_custom_fields($tag)
    {
        /*Check for existing taxonomy meta for the term you're editing*/
        $t_id = $tag->term_id; /*Get the ID of the term you're editing  */
        $term_meta = get_option("taxonomy_term_$t_id"); /*Do the check*/
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="ad_cat_icon"><?php echo __('Icon Name', 'redux-framework'); ?></label>
            </th>
            <td>
                <input type="text" name="term_meta[ad_cat_icon]" id="term_meta[ad_cat_icon]" size="25"
                       style="width:60%;"
                       value="<?php echo isset($term_meta['ad_cat_icon']) && $term_meta['ad_cat_icon'] != '' ? $term_meta['ad_cat_icon'] : ''; ?>"><br/>
                <span class="description">
                    <a href="https://adforestpro.scriptsbundle.com/icons-page/"
                       target="_blank"><?php echo __('Check icons list.', 'redux-framework'); ?></a>
                </span>
            </td>
        </tr>
        <?php


    }
}

/*Save the changes made on the "ad_cats" taxonomy, using our callback function  */
add_action('edited_ad_cats', 'save_taxonomy_custom_fields', 10, 2);
add_action('edited_l_event_cat', 'save_taxonomy_custom_fields', 10, 2);
/*A callback function to save our extra taxonomy field(s)  */

if (!function_exists('save_taxonomy_custom_fields')) {
    function save_taxonomy_custom_fields($term_id)
    {
        if (isset($_POST['term_meta'])) {
            $t_id = $term_id;
            $term_meta = get_option("taxonomy_term_$t_id");
            $cat_keys = array_keys($_POST['term_meta']);
            foreach ($cat_keys as $key) {
                if (isset($_POST['term_meta'][$key])) {
                    $term_meta[$key] = $_POST['term_meta'][$key];
                }
            }
            /*save the option array*/
            update_option("taxonomy_term_$t_id", $term_meta);
        }
    }
}
/*Register metaboxes for Bumbup ads*/
if (isset($_GET['post']) && $_GET['post'] != "") {
    add_action('add_meta_boxes', 'sb_meta_box_bump');
}

if (!function_exists('sb_meta_box_bump')) {
    function sb_meta_box_bump()
    {
        add_meta_box('sb_adforest_bump_ad', __('Bump This Ad At Top', 'redux-framework'), 'sb_render_meta_bump', 'ad_post', 'normal', 'high');
    }
}

if (!function_exists('sb_render_meta_bump')) {
    function sb_render_meta_bump()
    {
        ?>
        <div class="margin_top">
            <h3 class="alignleft">
                <?php echo __("Current Date: ", "redux-framework") . '' . get_the_date() . __(' And Time: ', 'redux-framework') . get_the_date('g:i A', get_the_ID()); ?>
            </h3>
            <div class="clear"></div>
            <input class="button button-primary" id="ad-adforest-bump-btn"
                   value="<?php echo __("Bump This Ad At Top", "redux-framework"); ?>" type="buttom">
            <script type="text/javascript">
                //Car Comparison
                jQuery('#ad-adforest-bump-btn').on('click', function () {
                    var post_id = '<?php echo get_the_ID(); ?>';
                    var confrm = confirm('<?php echo __("Are Your Sure You Want To Bumb The Ad", "redux-framework"); ?>');
                    if (confrm != true)
                        return;
                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'adforest_make_ad_bumb',
                        post_id: post_id,
                    }).done(function (response) {
                        if (response == 1) {
                            location.reload();
                        }
                    });
                });
            </script>
            <div class="clear"></div>

        </div>
        <?php
    }
}

add_action('wp_ajax_adforest_make_ad_bumb', 'adforest_make_ad_bumb_admin');

if (!function_exists('adforest_make_ad_bumb_admin')) {
    function adforest_make_ad_bumb_admin()
    {
        $id = ($_POST['post_id'] != "") ? $_POST['post_id'] : '';
        if (function_exists('adforest_set_date_timezone')) {
            adforest_set_date_timezone();
        }
        $time = current_time('mysql');
        //$time = date();
        $updated = wp_update_post(
            array(
                'ID' => $id,
                'post_date' => $time,
                'post_date_gmt' => get_gmt_from_date(current_time('mysql')),
                'post_type' => 'ad_post',
            )
        );
        update_post_meta($id, '_adforest_ad_status_', 'active');
        if ($updated) {
            echo '1';
        } else {
            echo '0';
        }
        wp_die();
    }
}

/*Register metaboxes for Products*/
add_action('add_meta_boxes', 'sb_adforest_ad_meta_box');

if (!function_exists('sb_adforest_ad_meta_box')) {
    function sb_adforest_ad_meta_box()
    {
        add_meta_box('sb_thmemes_adforest_metaboxes_for_ad', __('Assign AD', 'redux-framework'), 'sb_render_meta_for_ads', 'ad_post', 'normal', 'high');
    }
}

if (!function_exists('sb_render_meta_for_ads')) {
    function sb_render_meta_for_ads($post)
    {
        /*We'll use this nonce field later on when saving.*/
        wp_nonce_field('my_meta_box_nonce_ad', 'meta_box_nonce_ad');
        ?>
        <div class="margin_top">
            <p><?php echo __('Select Author', 'redux-framework'); ?></p>
            <select name="sb_change_author" style="width:100%; height:40px;">
                <?php $users = get_users(array('fields' => array('display_name', 'ID', 'user_email')));
                foreach ($users as $user) {
                    echo '<span>' . esc_html($user->display_name) . '</span>';
                    ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php if ($post->post_author == $user->ID)
                        echo 'selected'; ?>>
                        <?php echo esc_html($user->display_name) . ' ( ' . $user->user_email . ' )'; ?></option>
                <?php } ?>
            </select>
        </div>
        <?php
    }
}

/*Saving Metabox data */
add_action('save_post', 'sb_themes_meta_save_for_ad', 10, 3);
if (!function_exists('sb_themes_meta_save_for_ad')) {
    function sb_themes_meta_save_for_ad($post_id, $post, $update)
    {
        /*Bail if we're doing an auto save*/
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        /*if our nonce isn't there, or we can't verify it, bail*/
        if (!isset($_POST['meta_box_nonce_ad']) || !wp_verify_nonce($_POST['meta_box_nonce_ad'], 'my_meta_box_nonce_ad')) {
            return;
        }
        /*if our current user can't edit this post, bail*/
        if (!current_user_can('edit_posts')) {
            return;
        }
        if ('ad_post' !== $post->post_type) {
            return;
        }
        /*Make sure your data is set before trying to save it*/
        if (isset($_POST['sb_change_author'])) {
            $my_post = array(
                'ID' => $post_id,
                'post_author' => $_POST['sb_change_author'],
                'post_type' => 'ad_post',
            );
            /*unhook this function so it doesn't loop infinitely*/
            remove_action('save_post', 'sb_themes_meta_save_for_ad', 10, 3);

            /*update the post, which calls save_post again*/
            wp_update_post($my_post);

            $user_data = get_userdata($_POST['sb_change_author']);
            if (isset($user_data->display_name)) {

                update_post_meta($post_id, '_adforest_poster_name', $user_data->display_name);
            }

            /*re-hook this function*/
            add_action('save_post', 'sb_themes_meta_save_for_ad', 10, 3);
        }
    }
}


function sb_ad_claims_admin_tables($columns)
{

    // New columns to add to table
    $new_columns = array(
        'sb_claim_status' => __('Claim Status', 'redux-framework'),
        'sb_claimner' => __('Claimer', 'redux-framework'),
        'sb_claimner_no' => __('Contact No', 'redux-framework')
    );

    // Remove unwanted publish date column
    // unset( $columns['date'] );
    // Combine existing columns with new columns
    $filtered_columns = array_merge($columns, $new_columns);

    // Return our filtered array of columns
    return $filtered_columns;
}

// Let WordPress know to use our filter
add_filter('manage_ad_claims_posts_columns', 'sb_ad_claims_admin_tables');

function sb_ad_claims_admin_tables_content($column, $dynamic_data = '', $post_type_id = '')
{
    $user_info = '';
    $claimer_name = '';
    $claimer_url = '';
    //get post
    $posts = get_post($post_type_id);
    if ($posts != "") {
        $post_type_id = $posts->ID;
        $user_info = get_userdata($posts->post_author);
        $claimer_name = $user_info->display_name;
        $claimer_url = get_edit_user_link($posts->post_author);
    } else {
        $post_type_id = $post_type_id;
    }

    // Check to see if $column matches our custom column names
    switch ($column) {
        case 'sb_claim_status':
            // Retrieve post meta
            if (get_post_meta($post_type_id, 'd_listing_claim_status', true) != "") {
                echo get_post_meta($post_type_id, 'd_listing_claim_status', true);
            } else {
                update_post_meta($post_type_id, 'd_listing_claim_status', $dynamic_data);
            }
            break;

        case 'sb_claimner':
            // Retrieve post meta
            echo('<a href="' . esc_url($claimer_url) . '">' . $claimer_name . '</a>');
            break;

        case 'sb_claimner_no':
            // Retrieve post meta
            if (get_post_meta($post_type_id, 'd_listing_claimer_contact', true) != "") {
                echo get_post_meta($post_type_id, 'd_listing_claimer_contact', true);
            } else {
                update_post_meta($post_type_id, 'd_listing_claimer_contact', $dynamic_data);
            }
            break;
    }
}

// Let WordPress know to use our action
add_action('manage_ad_claims_posts_custom_column', 'sb_ad_claims_admin_tables_content');

function sb_ad_claims_admin_tables_sort($columns)
{
    // Add our columns to $columns array
    $columns['sb_claim_status'] = 'sb_claim_status';
    $columns['sb_claimner'] = 'sb_claimner';
    $columns['sb_claimner_no'] = 'contact';

    return $columns;
}

// Let WordPress know to use our filter
add_filter('manage_edit-ad_claims_sortable_columns', 'sb_ad_claims_admin_tables_sort');


add_action('add_meta_boxes', 'sb_meta_box_claims');

if (!function_exists('sb_meta_box_claims')) {
    function sb_meta_box_claims()
    {

        add_meta_box(
            'claim_history',
            __('Detial About Claim', 'redux-framework'),
            'render_metabox',
            'ad_claims',
            'advanced',
            'default'
        );
    }
}


function render_metabox($post)
{
    /* Add nonce for security and authentication. */
    wp_nonce_field('claim_nonce_action', 'claim_nonce');
    /* Retrieve an existing value from the database. */
    $claimer_contact = get_post_meta($post->ID, 'd_listing_claimer_contact', true);
    $claimed_by = $author_id = $post->post_author;
    $claimer_author_name = get_the_author_meta('display_name', $claimed_by);
    $claim_detials = get_post_meta($post->ID, 'd_listing_claimer_msg', true);
    $claim_status = get_post_meta($post->ID, 'd_listing_claim_status', true);

    /* Set default values. */
    if (empty($claimer_contact))
        $claimer_contact = '';
    if (empty($claimed_by))
        $claimed_by = '';
    if (empty($claimer_author_name))
        $claimer_author_name = '';
    if (empty($claim_detials))
        $claim_detials = '';
    if (empty($claim_status))
        $claim_status = '';
    /* Form fields. */
    echo '<table class="form-table ">
        <tr>
                <th><label for="claimed_by" class="claimed_by_label">' . __('Claimed By', 'redux-framework') . '</label></th>
                <td>
                 <select id="claimed_by" name="claimed_by" class="claim_status_field">
                    <option value=' . esc_attr__($claimed_by) . '> ' . (esc_attr__($claimer_author_name)) . '
                </select>
                    <p class="description">' . __('Claimed Author Name.', 'redux-framework') . '</p>
                </td>
            </tr>
            <tr>
                <th><label for="claim_detials" class="claim_detials_label">' . __('Claim Detials', 'redux-framework') . '</label></th>
                <td>
                    <textarea name="claim_detials" id="claim_detials" rows="10" cols="20" placeholder="' . __('Additional proof to expedite your claim approval...', 'redux-framework') . '"  >' . esc_attr__($claim_detials) . '</textarea>
                </td>
            </tr>
        <tr>
                <th><label for="claim_status" class="claim_status_label">' . __('Claim Status', 'redux-framework') . '</label></th>
            <td>
                <select id="claim_status" name="claim_status" class="claim_status_field">
                    <option value="pending" ' . selected($claim_status, 'pending', false) . '> ' . __('Pending', 'redux-framework') . '
                    <option value="approved" ' . selected($claim_status, 'approved', false) . '> ' . __('Approved', 'redux-framework') . '
                    <option value="decline" ' . selected($claim_status, 'decline', false) . '> ' . __('Decline', 'redux-framework') . '
                </select>
                <p class="description">' . __('Status for current claim', 'redux-framework') . '</p>
                </td>
            </tr>
            <tr>
                <th><label for="claimer_contact" class="claimer_contact_label">' . __('Claimer Contact', 'redux-framework') . '</label></th>
                <td>
                    <input type="text" id="claimer_contact" name="claimer_contact" class="claimer_contact_field" placeholder="" value="' . esc_attr__($claimer_contact) . '">
                    <p class="description">' . __('Claimer contact number', 'redux-framework') . '</p>
                </td>
            </tr>

        </table>';
}

add_action('save_post', 'sb_listing_claim_hook', 10, 3);
function sb_listing_claim_hook($post_id, $post, $update)
{
    if ($post->post_type != 'ad_claims') {
        return;
    }
    global $adforest_theme;
    $original_listing_id = '';
    $claimer_id = '';
    $claim_status = '';
    $user_info = '';
    $claim_winner_name = '';
    $claim_winner_email = '';
    if (isset($_POST['claim_status']) && $_POST['claim_status'] != "") {
        //get original listing id
        if (get_post_meta($post->ID, 'd_listing_original_id', true) != "")
            ;
        {
            $original_listing_id = get_post_meta($post->ID, 'd_listing_original_id', true);
        }
        // get claimer id
        if (get_post_meta($post->ID, 'd_listing_claimer_id', true) != "")
            ;
        {
            $claimer_id = get_post_meta($post->ID, 'd_listing_claimer_id', true);
        }
        // pending post status
        if ($_POST['claim_status'] == 'pending') {
            update_post_meta($original_listing_id, 'd_listing_claim_status', 'pending');
            return;
        }
        // approved post status
        if ($_POST['claim_status'] == 'approved') {

            $get_owner_id = get_post_field('post_author', $original_listing_id);
            //get user data
            $user_info = get_userdata($get_owner_id);


            // send email to first owner before claim
            $first_owner = $user_info->display_name;
            $first_owner_email = $user_info->user_email;
            $to = $first_owner_email;
            $subject = __('Listing Ownership Changed', 'redux-framework');
            $body = '<html><body><p>' . __('The ownership of your listing has been changed, please check it. ', 'redux-framework') . '<a href="' . get_the_permalink($original_listing_id) . '">' . get_the_title($original_listing_id) . '</a></p></body></html>';
            $from = get_bloginfo('name');

            if (isset($adforest_theme['sb_claim_change_from']) && $adforest_theme['sb_claim_change_from'] != "") {
                $from = $adforest_theme['sb_claim_change_from'];
            }
            $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");
            if (isset($adforest_theme['sb_claim_change_message']) && $adforest_theme['sb_claim_change_message'] != "") {
                $subject_keywords = array('%site_name%', '%ad_title%');
                $subject_replaces = array(get_bloginfo('name'), get_the_title($original_listing_id));
                $subject = str_replace($subject_keywords, $subject_replaces, $adforest_theme['sb_claim_change']);
                $author_id = get_post_field('post_author', $original_listing_id);
                $user_info = get_userdata($author_id);
                $msg_keywords = array('%site_name%', '%ad_title%', '%ad_link%', '%ad_owner%');
                $msg_replaces = array(get_bloginfo('name'), get_the_title($original_listing_id), get_the_permalink($original_listing_id), $first_owner);
                $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_claim_change_message']);
            }
            wp_mail($to, $subject, $body, $headers);
            remove_action('save_post', 'sb_listing_claim_hook', 10, 3);
            // update the post, which calls save_post again
            $my_post = array(
                'ID' => $original_listing_id,
                'post_author' => $claimer_id,
            );
            wp_update_post($my_post);
            // Now get claim winner data
            $user_info = get_userdata($claimer_id);
            if ($user_info) {
                $claim_winner_name = $user_info->display_name;
                $claim_winner_email = $user_info->user_email;
            }
            // Now send email to claim winne
            $to = $claim_winner_email;
            $subject = __('Claim Listing Approval', 'redux-framework');
            $body = '<html><body><p>' . __('The ownership of your listing has been changed, please check it. ', 'redux-framework') . '<a href="' . get_the_permalink($original_listing_id) . '">' . get_the_title($original_listing_id) . '</a></p></body></html>';
            $from = get_bloginfo('name');
            if (isset($adforest_theme['sb_claim_change_approved_from']) && $adforest_theme['sb_claim_change_approved_from'] != "") {
                $from = $adforest_theme['sb_claim_change_approved_from'];
            }
            $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");
            if (isset($adforest_theme['sb_claim_change_approved_message']) && $adforest_theme['sb_claim_change_approved_message'] != "") {
                $subject_keywords = array('%site_name%', '%ad_title%');
                $subject_replaces = array(get_bloginfo('name'), get_the_title($original_listing_id));
                $subject = str_replace($subject_keywords, $subject_replaces, $adforest_theme['sb_claim_approved_change']);
                $author_id = get_post_field('post_author', $original_listing_id);
                $user_info = get_userdata($author_id);
                $msg_keywords = array('%site_name%', '%ad_title%', '%ad_link%', '%ad_owner%');
                $msg_replaces = array(get_bloginfo('name'), get_the_title($original_listing_id), get_the_permalink($original_listing_id), $claim_winner_name);
                $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_claim_change_approved_message']);
            }
            wp_mail($to, $subject, $body, $headers);
            // remove user meta value
            if (get_user_meta($claimer_id, 'sb_listing_claimed_listing_id' . $original_listing_id, true) == $original_listing_id) {
                //update values
                update_user_meta($claimer_id, 'sb_listing_claimed_listing_id' . $original_listing_id, '');
                update_post_meta($post_id, 'd_listing_claim_status', 'approved');
                update_post_meta($original_listing_id, 'sb_listing_is_claimed', 1);
            }
        }
        // decline post status
        if ($_POST['claim_status'] == 'decline') {
            // Now get claim winner data
            $user_infoz = get_userdata($claimer_id);
            if ($user_infoz) {
                $claim_looser_name = $user_infoz->display_name;
                $claim_looser_email = $user_infoz->user_email;
                // Now send email to claim winne
                $to = $claim_looser_email;
                $subject = __('Listing Claim Declined', 'redux-framework');
                $body = '<html><body><p>' . __('Unfortunately! your claim has been declined, please check it. ', 'redux-framework') . '<a href="' . get_the_permalink($original_listing_id) . '">' . get_the_title($original_listing_id) . '</a></p></body></html>';
                $from = get_bloginfo('name');
                if (isset($adforest_theme['sb_claim_change_decline_from']) && $adforest_theme['sb_claim_change_decline_from'] != "") {
                    $from = $adforest_theme['sb_claim_change_decline_from'];
                }
                $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");
                if (isset($adforest_theme['sb_claim_change_decline_message']) && $adforest_theme['sb_claim_change_decline_message'] != "") {
                    $subject_keywords = array('%site_name%', '%ad_title%');
                    $subject_replaces = array(get_bloginfo('name'), get_the_title($original_listing_id));
                    $subject = str_replace($subject_keywords, $subject_replaces, $adforest_theme['sb_claim_decline_change']);
                    $author_id = get_post_field('post_author', $original_listing_id);
                    $user_info = get_userdata($author_id);
                    $msg_keywords = array('%site_name%', '%ad_title%', '%ad_link%', '%claimer_name%');
                    $msg_replaces = array(get_bloginfo('name'), get_the_title($original_listing_id), get_the_permalink($original_listing_id), $claim_looser_name);
                    $body = str_replace($msg_keywords, $msg_replaces, $adforest_theme['sb_claim_change_decline_message']);
                }
                wp_mail($to, $subject, $body, $headers);
                // remove user meta value
                if (get_user_meta($claimer_id, 'sb_listing_claimed_listing_id' . $original_listing_id, true) == $original_listing_id) {
                    //update values
                    update_post_meta($post_id, 'd_listing_claim_status', 'decline');
                }
            }
        }
    }
}
