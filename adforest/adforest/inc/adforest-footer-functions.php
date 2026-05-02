<?php
global $adforest_theme;
$adforest_theme = get_option('adforest_theme');

add_action('adforest_relation_follow_links', 'adforest_relation_follow_links_callback');
//add_filter('adforest_footer_sidebar_options', 'adforest_footer_sidebar_options_callback', 10, 1);
add_filter('adforest_footer_widget_col_class', 'adforest_footer_widget_col_class_callback', 10, 1);
if (!function_exists('adforest_relation_follow_links_callback')) {
    function adforest_relation_follow_links_callback()
    {
        $social_follow = '';
        if (class_exists('Redux')) {
            $social_follow = Redux::get_option('adforest_theme', 'social_follow');
        }
        if ($social_follow == 'nofollow') {
            echo adforest_return_echo(' rel="nofollow" ');
        }
    }
}
if (!function_exists('adforest_footer_sidebar_widgets')) {

    /**
     * Footer Sidebar Widgets section.
     * @return markup
     */
    function adforest_footer_sidebar_widgets()
    {

        $adforest_sidebars_col = array();
        $adforest_sidebars_switch = false;
        if (class_exists('Redux')) {
            $adforest_sidebars_col = Redux::get_option('adforest_theme', 'sb_footer_cols');
            $adforest_sidebars_switch = Redux::get_option('adforest_theme', 'sb_footer_widget_switch');
        }

        if (isset($adforest_sidebars_switch) && isset($adforest_sidebars_col) && is_array($adforest_sidebars_col) && sizeof($adforest_sidebars_col) > 0) {
            $counter = 1;
            foreach ($adforest_sidebars_col as $sidebar) {
                $sidebar_id = 'footer-' . $counter;
                if (is_active_sidebar($sidebar_id)) {
                    $sidebar_col_class = apply_filters('adforest_footer_widget_col_class', $sidebar);
                    ?>
                    <div class="<?php echo esc_attr($sidebar_col_class) ?>">
                        <?php dynamic_sidebar($sidebar_id) ?>
                    </div>
                    <?php
                }
                $counter++;
            }
        }
    }

    add_action('adforest_footer_sidebar_widgets', 'adforest_footer_sidebar_widgets', 10);
}




if (!function_exists('adforest_footer_widget_col_class_callback')) {

    function adforest_footer_widget_col_class_callback($col = '')
    {
        /* 0-5 = 2 column
          6-9 = 3 column
          10-12 = 4 column
          13-14 = 5 column
          15-16 = 6 column
          17 = 7 column
          18 = 8 colimn
          19 = 9 column
          20 = 10 column
          21 = 12 column */
        $col_class = 'col-md-12 col-sm-6 col-xs-12 col-lg-12';
        if ($col >= 0 && $col <= 5) {
            $col_class = 'col-md-2 col-sm-6 col-xs-12 col-lg-2';
        } elseif ($col >= 6 && $col <= 9) {
            $col_class = 'col-md-3 col-sm-6 col-xs-12 col-lg-3';
        } elseif ($col >= 10 && $col <= 12) {
            $col_class = 'col-md-4 col-sm-6 col-xs-12 col-lg-4';
        } elseif ($col >= 13 && $col <= 14) {
            $col_class = 'col-md-5 col-sm-6 col-xs-12 col-lg-5';
        } elseif ($col >= 15 && $col <= 16) {
            $col_class = 'col-md-6 col-sm-6 col-xs-12 col-lg-6';
        } elseif ($col == 17) {
            $col_class = 'col-md-7 col-sm-6 col-xs-12 col-lg-7';
        } elseif ($col == 18) {
            $col_class = 'col-md-8 col-sm-6 col-xs-12 col-lg-8';
        } elseif ($col == 19) {
            $col_class = 'col-md-9 col-sm-6 col-xs-12 col-lg-9';
        } elseif ($col == 20) {
            $col_class = 'col-md-10 col-sm-6 col-xs-12 col-lg-10';
        } elseif ($col == 21) {
            $col_class = 'col-md-12 col-sm-6 col-xs-12 col-lg-12';
        }
        return $col_class;
    }

}

if (!function_exists('adforest_footer_sidebar_options_callback')) {

    function adforest_footer_sidebar_options_callback($options = array())
    {

        $footer_cols_arr = array('column 2', 'column    2', 'column 2', 'column 2', 'column 2', 'column 2', 'column 3', 'column 3', 'column 3', 'column 3', 'column 4', 'column 4', 'column 4', 'column 5', 'column 5', 'column 6', 'column 6', 'column 7', 'column 8', 'column 9', 'column 10', 'column 12', );

        $options = array(
            'title' => __('Footer Widgets', 'adforest'),
            'id' => 'sb-footer-sidebar',
            'desc' => __('Enable on the footer widgets and add custom footer sidebar columns of your choice.After that you can add widgets in these custom footer sidebar <b>dashboard > Apperance > widgets</b> these widgets displays at the top of the footer.', 'adforest'),
            'subsection' => true,
            'fields' => array(
                array(
                    'id' => 'sb_footer_widget_switch',
                    'type' => 'switch',
                    'title' => __('Footer Widgets', 'adforest'),
                    'default' => false,
                    'desc' => __('<b>Note</b> : This widgetize area will only work for footer-8', 'adforest'),
                ),
                array(
                    'id' => 'sb_footer_cols',
                    'type' => 'select',
                    'multi' => true,
                    'sortable' => true,
                    'title' => __('Register Sidebar', 'adforest'),
                    'desc' => __('Set custom footer sidebar columns for the footer widgetize area.', 'adforest'),
                    'options' => $footer_cols_arr,
                    'required' => array('sb_footer_widget_switch', 'equals', true)
                ),
            )
        );

        return $options;
    }

}



$adforest_themee = get_option('adforest_theme');
$package_expiry_notification = isset($adforest_themee['package_expiry_notification']) ? $adforest_themee['package_expiry_notification'] : false;
if (isset($package_expiry_notification) && ($package_expiry_notification)) {
    if (!wp_next_scheduled('adforest_package_expiray_notification')) {
        wp_schedule_event(time(), 'daily', 'adforest_package_expiray_notification');
    }
} else {
    if (wp_next_scheduled('adforest_package_expiray_notification')) {
        $timestamp = wp_next_scheduled('adforest_package_expiray_notification');
        wp_unschedule_event($timestamp, 'adforest_package_expiray_notification');
    }
}

add_action('adforest_package_expiray_notification', 'adforest_package_expiray_notification_callback');

if (!function_exists('adforest_package_expiray_notification_callback')) {

    function adforest_package_expiray_notification_callback()
    {
        global $adforest_theme;
        $adforest_theme = get_option('adforest_theme');
        $before_days = isset($adforest_theme['package_expire_notify_before']) ? $adforest_theme['package_expire_notify_before'] : 0;
        if (isset($adforest_theme['package_expiry_notification']) && ($adforest_theme['package_expiry_notification'])) {
            $adforest_users = get_users([ 'role__in' => [ 'subscriber' ] ]);
            if (isset($adforest_users) && !empty($adforest_users) && is_array($adforest_users)) {
                foreach ($adforest_users as $key => $user) {
                    $package_expiry_data = get_user_meta($user->ID, '_sb_expire_ads', true);
                    $sb_pkg_name = get_user_meta($user->ID, '_sb_pkg_type', true);
                    $user_data = $user->data;
                    $user_display_name = $user_data->display_name;
                    if (empty($package_expiry_data) || $package_expiry_data == -1) {
                        continue;
                    }
                    $notification_date = date("Y-m-d", strtotime("-{$before_days} days", strtotime($package_expiry_data)));
                    $today_data = current_time("Y-m-d");
                    if ($today_data == $notification_date) {
                        do_action('adforest_package_expiry_notification', $before_days, $user->ID);
                    }
                }
            }
        }
    }

}


if (!function_exists('adforest_dynamic_sidebars')) {

    /**
     * Adforest Dynamic Sidebars.
     * @generate sidebars
     */
    function adforest_dynamic_sidebars()
    {

        $adforest_sidebars = array();
        if (class_exists('Redux')) {
            $adforest_sidebars = Redux::get_option('adforest_theme', 'sb_footer_cols');
        }

        $adforest_sidebars = isset($adforest_sidebars) && !empty($adforest_sidebars) ? $adforest_sidebars : array();
        if (is_array($adforest_sidebars) && sizeof($adforest_sidebars) > 0) {
            $counter = 1;
            foreach ($adforest_sidebars as $sidebar) {
                if ($sidebar != '') {
                    $sidebar_name = sprintf(esc_html__('Footer Sidebar - %d', 'adforest'), $counter);
                    $sidebar_id = 'footer-' . $counter;
                    register_sidebar(
                        array(
                            'name' => $sidebar_name,
                            'id' => $sidebar_id,
                            'description' => esc_html__('Add widgets here.', 'adforest'),
                            'before_widget' => '<div id="%1$s" class="widget %2$s">',
                            'after_widget' => '</div>',
                            'before_title' => '<div class="footer-widget-title"><h2>',
                            'after_title' => '</h2></div>',
                        )
                    );
                }
                $counter++;
            }
        }
    }

    add_action('widgets_init', 'adforest_dynamic_sidebars');
}

if (!function_exists('adforest_exclude_child_from_tax_page')) {

    function adforest_exclude_child_from_tax_page($query)
    {
        $taxonomy_slugs = [ 'ad_cats' ];
        if ($query->is_main_query() && is_tax($taxonomy_slugs)) {
            foreach ($query->tax_query->queries as &$tax_query_item) {
                if (empty($taxonomy_slugs) || in_array($tax_query_item['taxonomy'], $taxonomy_slugs)) {
                    $tax_query_item['include_children'] = 0;
                }
            }
        }
    }

}
//add_action('parse_tax_query', 'adforest_exclude_child_from_tax_page');


add_filter('adforest_category_widget_form_action', 'adforest_category_widget_form_action', 10, 2);

if (!function_exists('adforest_category_widget_form_action')) {

    function adforest_category_widget_form_action($sb_search_page, $widget_action = '')
    {
        global $template, $wp;
        $page_template = basename($template);

        if ($page_template == 'taxonomy-ad_cats.php' || $page_template == 'taxonomy-ad_country.php') {

            $category = get_queried_object();
            if (isset($category->term_id)) {

                $sb_search_page = get_category_link($category->term_id);
            } else {
                $sb_search_page = home_url($wp->request);
            }

//            if ($widget_action == 'cat_page' || $widget_action == 'location_page') {
//                $sb_search_page = 'javascript:void(0)';
//            }
        }

        return $sb_search_page;
    }

}


add_action('init', 'sb_theme_initializaion_callback');

if (!function_exists('sb_theme_initializaion_callback')) {

    function sb_theme_initializaion_callback()
    {

        $code_verification = get_option('_sb_purchase_code_verification');
        if (isset($code_verification) && $code_verification == 'done') {

        } else {
            $my_keyname = array("_", "s", "b", "_", "p", "u", "r", "c", "h", "a", "s", "e", "_", "c", "o", "d", "e");
            $kyname = implode($my_keyname);
            $my_keynamelink = array("h", "t", "t", "p", "s", ":", "/", "/", "a", "u", "t", "h", "e", "n", "t", "i", "c", "a", "t", "e", ".", "s", "c", "r", "i", "p", "t", "s", "b", "u", "n", "d", "l", "e", ".", "c", "o", "m", "/", "a", "d", "f", "o", "r", "e", "s", "t", "/", "v", "e", "r", "i", "f", "y", "_", "p", "c", "o", "d", "e", ".", "p", "h", "p");
            $my_keynameUrl = implode($my_keynamelink);
            $sb_theme_pcode = get_option($kyname);
            if ($sb_theme_pcode != "") {
                $theme_name = "Adforest";
                $data = "?purchase_code=" . $sb_theme_pcode . "&id=" . get_option('admin_email') . '&url=' . get_option('siteurl') . '&theme_name=' . $theme_name;
                $url = esc_url($my_keynameUrl) . $data;
                $response = @wp_remote_get($url);
                if (is_array($response) && !is_wp_error($response)) {
                    update_option('_sb_purchase_code_verification', 'done');
                } else {
                    update_option('_sb_purchase_code_verification', '');
                }
            }
        }
    }

}
add_filter('adforest_filter_taxonomy_popup_actions', 'adforest_filter_taxonomy_popup_actions_callback', 10, 3);

if (!function_exists('adforest_filter_taxonomy_popup_actions_callback')) {

    function adforest_filter_taxonomy_popup_actions_callback($sb_search_page, $tax_id = '', $tax_name = 'ad_cats')
    {
        global $template, $wp;
        $page_template = basename($template);

        if ($page_template == 'taxonomy-ad_cats.php' && $tax_name == 'ad_cats') {
            if (isset($tax_id) && $tax_id != '') {
                $sb_search_page = get_term_link($tax_id, $tax_name);
            }
        }
        if ($page_template == 'taxonomy-ad_country.php' && $tax_name == 'ad_country') {
            if (isset($tax_id) && $tax_id != '') {
                $sb_search_page = get_term_link($tax_id, $tax_name);
            }
        }
        return $sb_search_page;
    }
}

$sb_user_profile_avatar = isset($adforest_theme['sb_user_profile_avatar']) && $adforest_theme['sb_user_profile_avatar'] ? TRUE : FALSE;
if ($sb_user_profile_avatar) {
    add_filter('get_avatar', 'adforest_user_avatar_image', 1, 5);

    if (!function_exists('adforest_user_avatar_image')) {
        function adforest_user_avatar_image($avatar, $id_or_email, $size, $default, $alt)
        {
            global $adforest_theme;
            $user = false;
            if (is_numeric($id_or_email)) {
                $id = (int) $id_or_email;
                $user = get_user_by('id', $id);
            } elseif (is_object($id_or_email)) {
                if (!empty($id_or_email->user_id)) {
                    $id = (int) $id_or_email->user_id;
                    $user = get_user_by('id', $id);
                }
            } else {
                $user = get_user_by('email', $id_or_email);
            }

            if ($user && is_object($user)) {
                if ($user->data->ID != '') {

                    $user_id = $user->data->ID;
                    $user_pic = trailingslashit(get_template_directory_uri()) . 'images/users/9.jpg';
                    if (isset($adforest_theme['sb_user_dp']['url']) && $adforest_theme['sb_user_dp']['url'] != "") {
                        $user_pic = $adforest_theme['sb_user_dp']['url'];
                    }
                    $image_link = array();
                    if (get_user_meta($user_id, '_sb_user_pic', true) != "") {
                        $attach_id = get_user_meta($user_id, '_sb_user_pic', true);
                        $image_link = wp_get_attachment_image_src($attach_id, $size);
                    }
                    if (isset($image_link) && !empty($image_link) && is_array($image_link) && count($image_link) > 0) {
                        if ($image_link[0] != "") {
                            $headers = @get_headers($image_link[0]);
                            if (strpos($headers[0], '404') === false) {
                                $image_link = $image_link[0];
                            } else {
                                $image_link = $user_pic;
                            }
                        } else {
                            $image_link = $user_pic;
                        }
                    } else {
                        $image_link = $user_pic;
                    }
                    $avatar = $image_link;
                    $avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
                }
            }
            return $avatar;
        }
    }
}

/* * Used to refresh the enqueued style and script version * */
$sb_refersh_enqueued = isset($adforest_theme['sb_refersh_enqueued']) && $adforest_theme['sb_refersh_enqueued'] ? TRUE : FALSE;
if ($sb_refersh_enqueued) {

    if (!function_exists('adforest_refresh_enqueued_files')) {

        function adforest_refresh_enqueued_files($enq_source)
        {
            if (strpos($enq_source, 'ver=')) {
                $enq_source = remove_query_arg('ver', $enq_source);
                $enq_source = add_query_arg(array('ver' => wp_rand(99, 9999)), $enq_source);
            }
            return $enq_source;
        }

    }

    add_filter('style_loader_src', 'adforest_refresh_enqueued_files', 10, 2);
    add_filter('script_loader_src', 'adforest_refresh_enqueued_files', 10, 2);
}


add_action('wp_ajax_sb_set_site_location', 'sb_set_site_location_callback');
add_action('wp_ajax_nopriv_sb_set_site_location', 'sb_set_site_location_callback');

if(!function_exists('sb_set_site_location_callback')) {
    function sb_set_site_location_callback() {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sb_set_site_location_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'adforest')]);
        }

        $raw_location = isset($_POST['location_id']) ? sanitize_text_field(wp_unslash($_POST['location_id'])) : '';

        if ($raw_location === '') {
            wp_send_json_error(['message' => esc_html__('Missing location identifier.', 'adforest')]);
        }

        $cookie_secure   = is_ssl();
        $cookie_httponly = false;

        if ($raw_location === 'all') {
            setcookie('adforest_site_location_id', 'all', time() + 31556926, COOKIEPATH, COOKIE_DOMAIN, $cookie_secure, $cookie_httponly);

            wp_send_json_success([
                'id'      => 'all',
                'term_id' => 0,
                'slug'    => '',
                'name'    => esc_html__('All Locations', 'adforest'),
                'image'   => adforest_get_location_default_image_url(),
                'count'   => 0,
                'is_all'  => true,
            ]);
        }

        $term_id = absint($raw_location);
        if (!$term_id) {
            wp_send_json_error(['message' => esc_html__('Invalid location identifier.', 'adforest')]);
        }

        $term = get_term($term_id, 'ad_country');
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(['message' => esc_html__('Selected location no longer exists.', 'adforest')]);
        }

        setcookie('adforest_site_location_id', (string) $term->term_id, time() + 31556926, COOKIEPATH, COOKIE_DOMAIN, $cookie_secure, $cookie_httponly);

        wp_send_json_success([
            'id'      => (string) $term->term_id,
            'term_id' => (int) $term->term_id,
            'slug'    => $term->slug,
            'name'    => $term->name,
            'image'   => adforest_get_location_image_url($term->term_id),
            'count'   => (int) $term->count,
            'is_all'  => false,
        ]);
    }
}

add_action('adforest_topbar_location', 'adforest_topbar_location_callback');
if (!function_exists('adforest_topbar_location_callback')) {
    function adforest_topbar_location_callback()
    {
        global $adforest_theme;
        if (isset($adforest_theme["sb_top_location"]) && !empty($adforest_theme["sb_top_location"]) && $adforest_theme["sb_top_location"] == '1') {

            $active_class = $image_idz = $final_img = $sel_image_link = $selected_loc = $loc_name = $selected_location = $image_id = $get_valz = '';
            if (isset($adforest_theme["sb_top_location_list"]) && $adforest_theme["sb_top_location_list"] != '' && is_array($adforest_theme["sb_top_location_list"]) && count($adforest_theme["sb_top_location_list"]) > 0) {
                $adforest_site_location_id = isset($_COOKIE["adforest_site_location_id"]) && $_COOKIE["adforest_site_location_id"] != '' ? $_COOKIE["adforest_site_location_id"] : '';
                $selected_location = $adforest_site_location_id;


                if (isset($adforest_site_location_id) && $adforest_site_location_id != "" && $adforest_site_location_id != "all") {
                    $selected_loc = get_term_by('id', $selected_location, 'ad_country');
                    if (isset($selected_loc) && $selected_loc != '') {
                        $loc_name = $selected_loc->name;
                        $image_idz = get_term_meta($selected_loc->term_id, 'taxonomy_image', true);
                        $sel_image_link = wp_get_attachment_image_src($image_idz, 'adforest-single-small-50');
                        if (isset($sel_image_link[0]) && $sel_image_link[0] != "") {
                            $final_img = $sel_image_link[0];
                        } else {
                            $final_img = esc_url(trailingslashit(get_template_directory_uri()) . 'images/global.png');
                        }
                    }
                } else {
                    $loc_name = esc_html__('All Locations', 'adforest');
                    $final_img =
                        esc_url(trailingslashit(get_template_directory_uri()) . 'images/global.png');
                }
                ?>
                <ul class="list-inline">
                    <li class="dropdown sb-location-selector"><span class="loc"><?php echo esc_attr($loc_name); ?> :</span><a
                            href="javascript:void(0)" class="dropdown-toggle" data-bs-toggle="dropdown" data-close-others="true">

                            <img src="<?php echo esc_url($final_img); ?>" alt="<?php echo esc_attr($loc_name); ?>" />
                        </a>
                        <ul class="dropdown-menu pull-right sb-top-loc">
                            <li><a href="javascript:void(0)" data-loc-id="all" class="top-loc-selection"><img
                                        src="<?php echo esc_url(trailingslashit(get_template_directory_uri()) . 'images/global.png'); ?>"
                                        alt="<?php echo esc_attr__('All Locations', 'adforest'); ?>" /><span><?php echo esc_html__('All Locations', 'adforest'); ?></span></a>
                            </li><?php
                            foreach ($adforest_theme["sb_top_location_list"] as $val) {
                                $image_link = '';
                                $get_valz = get_term_by('id', $val, 'ad_country');
                                if (get_term_meta($get_valz->term_id, 'taxonomy_image', true) != "") {
                                    $image_id = get_term_meta($get_valz->term_id, 'taxonomy_image', true);
                                    $image_link = wp_get_attachment_image_src($image_id, 'adforest-single-small-50');
                                }
                                ?><li <?php
                                if ($get_valz->term_id == $selected_location) {
                                    echo 'class=active';
                                }
                                ?>><a href="javascript:void(0)" data-loc-id="<?php echo esc_attr($get_valz->term_id); ?>"
                                        class="top-loc-selection"><?php if (isset($image_link[0]) && $image_link[0] != "") { ?><img
                                                src="<?php echo esc_url($image_link[0]); ?>"
                                                alt="<?php echo esc_attr($get_valz->name); ?>" /><?php } ?><span><?php echo esc_attr($get_valz->name); ?></span></a>
                                </li><?php } ?>
                        </ul>
                    </li>
                </ul>
                <?php
            }
        }
    }
}
add_filter('adforest_site_location_ads', 'adforest_site_location_ads_callback', 10, 2);

if (!function_exists('adforest_site_location_ads_callback')) {

    function adforest_site_location_ads_callback($loc_args, $arg_type = 'search')
    {
        if (isset($_GET['country_id']) && $_GET['country_id'] != "") {
            return $loc_args;
        }

        $location_id = adforest_get_selected_site_location_id();
        if (!$location_id) {
            return $loc_args;
        }

        if ($arg_type === 'ads') {
            if (!is_array($loc_args)) {
                $loc_args = array();
            }

            $tax_query = isset($loc_args['tax_query']) && is_array($loc_args['tax_query']) ? $loc_args['tax_query'] : array();
            if (!empty($tax_query) && !isset($tax_query['relation'])) {
                $tax_query['relation'] = 'AND';
            }

            $already_applied = false;
            if (!empty($tax_query)) {
                foreach ($tax_query as $condition) {
                    if (!is_array($condition)) {
                        continue;
                    }
                    if (isset($condition['taxonomy']) && $condition['taxonomy'] === 'ad_country') {
                        $already_applied = true;
                        break;
                    }
                }
            }

            if (!$already_applied) {
                $tax_query[] = array(
                    'taxonomy' => 'ad_country',
                    'field'    => 'term_id',
                    'terms'    => array($location_id),
                );
            }

            $loc_args['tax_query'] = $tax_query;

            return $loc_args;
        } else {
            if (!is_array($loc_args)) {
                $loc_args = array();
            }

            $loc_args[] = array(
                'taxonomy' => 'ad_country',
                'field'    => 'term_id',
                'terms'    => array($location_id),
            );

            return $loc_args;
        }
    }
}

// Footer Email Subscriber //
add_action('wp_ajax_adforest_mailchimp_subcribe', 'adforest_mailchimp_subcribe');
add_action('wp_ajax_nopriv_adforest_mailchimp_subcribe', 'adforest_mailchimp_subcribe');
/* Addind Subcriber into Mailchimp */
if (!function_exists('adforest_mailchimp_subcribe')) {
    function adforest_mailchimp_subcribe() {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'adforest_mailchimp_subcribe_nonce')) {
            $response = ['status' => 0, 'msg' => esc_html__('Security check failed.', 'adforest')];
            wp_send_json_error($response);
            die();
        }

        global $adforest_theme;
        $sb_action = $_POST['adforest_action'];
        $apiKey = $adforest_theme['mailchimp_api_key'];

        if ($sb_action == 'coming_soon') {
            $listid = $adforest_theme['mailchimp_notify_list_id'];
        }
        if ($sb_action == 'footer_action') {
            $listid = $adforest_theme['mailchimp_footer_list_id'];
        }
        if ($apiKey == "" || $listid == "") {
            $response = ['status' => 0, 'msg' => "Mailchimp API Key/List ID missing."];
            wp_send_json_error($response);
            die();
        }
        $email = $_POST['sb_email'];
        $fname = '';
        $lname = '';
        /* MailChimp API URL */
        $memberID = md5(strtolower($email));
        $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listid . '/members/' . $memberID;
        /* member information */
        $json = json_encode(array(
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => array(
                'FNAME' => $fname,
                'LNAME' => $lname
            )
        ));

        /* send a HTTP POST request with curl */
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        /* store the status message based on response code */

        $mcdata = json_decode($result);
        if (!empty($mcdata->error)) {
            $response = ['status' => 0, 'msg' => $mcdata->error];
            wp_send_json_error($response);
        } else {
            $response = ['status' => 1, 'msg' => __('Subscribed Successfully', 'adforest')];
            wp_send_json_success($response);
        }
        die();
    }

}
// Footer Email Subscriber//