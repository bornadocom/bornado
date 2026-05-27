<?php
if (!function_exists('adforest_vc_forntend_edit')) {
    function adforest_vc_forntend_edit()
    {
        // return function_exists('vc_is_inline') && vc_is_inline() ? true : false;
    }
}

/*
 * Adforest Elementor Function Class
 */

class Adforest_Elementor_Functions
{

    public function __construct()
    {
        add_filter('adforest_elementor_url_field', array($this, 'adforest_elementor_url_field_callback'), 10, 2);
        add_filter('adforest_elementor_ads_styles', array($this, 'adforest_elementor_ads_styles_callback'));
        add_filter('adforest_elementor_ads_categories', array(
            $this,
            'adforest_elementor_ads_categories_callback'
        ), 10, 4);
        add_filter('adforest_elementor_get_packages', array($this, 'adforest_elementor_get_packages_callback'));
        add_filter('adforest_elementor_get_product_categories', array(
            $this,
            'adforest_product_categories_callback'
        ));
    }

    public function adforest_elementor_get_packages_callback($type = 'products')
    {
        global $adforest_theme;
        $products = array();
        if ($type != "products") {
            $args = array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => array('adforest_classified_pkgs', 'subscription', 'variable-subscription'),
                    ),
                ),
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'orderby' => 'ID',
            );
        } else {
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'order' => 'DESC',
                'orderby' => 'ID',
            );
        }

        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $packages = new WP_Query($args);
        if ($packages->have_posts()) {
            while ($packages->have_posts()) {
                $packages->the_post();
                $products[get_the_ID()] = get_the_title();
            }
        }

        return $products;
    }

    public function adforest_elementor_ads_categories_callback($cats_arr = array(), $taxonomy = 'ad_cats', $all = '', $hide_empty = 0)
    {
        if ($all == 'yes') {
            $cats_arr['all'] = __('All', 'adforest-elementor');
        } else if ($taxonomy == 'ad_country') {
            $cats_arr[''] = __('Select Location', 'adforest-elementor');
        } else if ($taxonomy == 'ad_warranty') {
            $cats_arr[''] = __('Select Warranty', 'adforest-elementor');
        } else if ($taxonomy == 'ad_condition') {
            $cats_arr[''] = __('Select Condition', 'adforest-elementor');
        } else if ($taxonomy == 'ad_type') {
            $cats_arr[''] = __('Select Ad Type', 'adforest-elementor');
        } else if ($taxonomy == 'product_cat') {
            $cats_arr[''] = __('Select Product Type', 'adforest-elementor');
        } else {
            $cats_arr[''] = __('Select ad categories', 'adforest-elementor');
        }

        $args = array('hide_empty' => $hide_empty);
        $args = apply_filters('adforest_wpml_show_all_posts', $args);
        $ad_cats = get_terms($taxonomy, $args);

        if (is_array($ad_cats) && count($ad_cats) > 0) {
            foreach ($ad_cats as $cat) {
                if (isset($cat->term_id)) {
                    $count = isset($cat->count) ? $cat->count : "";
                    $cats_arr[$cat->term_id] = wp_specialchars_decode($cat->name) . ' ( ' . urldecode_deep($cat->slug) . ' ) ' . ' ( ' . $count . ' ) ';
                }
            }
        }

        return $cats_arr;
    }

    public function adforest_elementor_ads_styles_callback()
    {
        $grid_array = [];
        $grid_array = array(
            '' => __('Select Layout Type', 'adforest-elementor'),
            'grid_1' => __('Grid 1', 'adforest-elementor'),
            'grid_2' => __('Grid 2', 'adforest-elementor'),
            'grid_3' => __('Grid 3', 'adforest-elementor'),
            'grid_4' => __('Grid 4', 'adforest-elementor'),
            'grid_5' => __('Grid 5', 'adforest-elementor'),
            'grid_6' => __('Grid 6', 'adforest-elementor'),
            'grid_7' => __('Grid 7', 'adforest-elementor'),
            'grid_8' => __('Grid 8', 'adforest-elementor'),
            'grid_9' => __('Grid 9', 'adforest-elementor'),
            'grid_10' => __('Grid 10', 'adforest-elementor'),
            'grid_11' => __('Grid 11', 'adforest-elementor'),
            'list' => __('List', 'adforest-elementor'),
        );

        return $grid_array;
    }

    public function adforest_elementor_url_field_callback($link_html, $params_data = array())
    {
        extract($params_data);
        $buttonHTML = '';
        if (isset($adforest_elementor) && $adforest_elementor) {
            if (isset($btn_key["url"]) && $btn_key["url"] != "") {
                $is_external = isset($btn_key["is_external"]) && $btn_key["is_external"] == 1 ? ' target="__blank" ' : '';
                $nofollow = isset($btn_key["nofollow"]) && $btn_key["nofollow"] == 1 ? ' rel="nofollow" ' : '';
                $custom_attr = isset($btn_key["custom_attributes"]) && $btn_key["custom_attributes"] != '' ? $btn_key["custom_attributes"] : '';
                $class = ($btn_class != "") ? 'class="' . esc_attr($btn_class) . '" ' : '';

                if (isset($onlyAttr) && $onlyAttr) {
                    $btn_html = $class . 'href="' . $btn_key["url"] . '" ' . $is_external . ' ' . $nofollow . ' ' . $custom_attr;
                } else {
                    $btn_html = '<a ' . $class . 'href="' . $btn_key["url"] . '" ' . $is_external . ' ' . $nofollow . ' ' . $custom_attr . '>' . $iconBefore . ' ' . esc_html($titleText) . ' ' . $iconAfter . '</a>';
                }

                $buttonHTML = (isset($titleText)) ? $btn_html : "";
            }
        }

        return $buttonHTML;
    }

    public function adforest_product_categories_callback($term_type = 'product_cat')
    {
        global $adforest_theme;
        if (isset($adforest_theme['shop-turn-on']) && $adforest_theme['shop-turn-on']) {
            $args = array('hide_empty' => false);
            // Apply WPML filter for product categories
            $args = apply_filters('adforest_wpml_show_all_posts', $args);
            $terms = get_terms($term_type, $args);
            $result = array();
            if (count((array)$terms) > 0 && !empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $result[$term->slug] = $term->name;
                }
            }

            return $result;
        }
    }

}

new Adforest_Elementor_Functions();

add_action('init', 'adforest_elementor_plugin_initializaion_func');
if (!function_exists('adforest_elementor_plugin_initializaion_func')) {

    function adforest_elementor_plugin_initializaion_func()
    {
        $code_verification = get_option('_sb_purchase_code_ele_verification');
        if (isset($code_verification) && $code_verification == 'done') {

        } else {
            $my_keyname = array(
                "_",
                "s",
                "b",
                "_",
                "p",
                "u",
                "r",
                "c",
                "h",
                "a",
                "s",
                "e",
                "_",
                "c",
                "o",
                "d",
                "e"
            );
            $kyname = implode($my_keyname);
            $my_keynamelink = array(
                "h",
                "t",
                "t",
                "p",
                "s",
                ":",
                "/",
                "/",
                "a",
                "u",
                "t",
                "h",
                "e",
                "n",
                "t",
                "i",
                "c",
                "a",
                "t",
                "e",
                ".",
                "s",
                "c",
                "r",
                "i",
                "p",
                "t",
                "s",
                "b",
                "u",
                "n",
                "d",
                "l",
                "e",
                ".",
                "c",
                "o",
                "m",
                "/",
                "a",
                "d",
                "f",
                "o",
                "r",
                "e",
                "s",
                "t",
                "/",
                "v",
                "e",
                "r",
                "i",
                "f",
                "y",
                "_",
                "p",
                "c",
                "o",
                "d",
                "e",
                ".",
                "p",
                "h",
                "p"
            );
            $my_keynameUrl = implode($my_keynamelink);
            $sb_theme_pcode = get_option($kyname);
            if ($sb_theme_pcode != "") {
                $theme_name = "Adforest Elementor";
                $data = "?purchase_code=" . $sb_theme_pcode . "&id=" . get_option('admin_email') . '&url=' . get_option('siteurl') . '&theme_name=' . $theme_name;
                $url = esc_url($my_keynameUrl) . $data;
                $response = @wp_remote_get($url);
                if (is_array($response) && !is_wp_error($response)) {
                    update_option('_sb_purchase_code_ele_verification', 'done');
                } else {
                    update_option('_sb_purchase_code_ele_verification', '');
                }
            }
        }
    }

}

/*
 * Get Product Categories
 */
/* Get products related to post. on vendor page */

add_action('wp_ajax_product_fav_add', 'adforest_product_fav_add');
add_action('wp_ajax_nopriv_product_fav_add', 'adforest_product_fav_add');
if (!function_exists('adforest_product_fav_add')) {
    function adforest_product_fav_add()
    {
        adforest_authenticate_check();
        $prod_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $status_code = isset($_POST['status_code']) ? sanitize_text_field($_POST['status_code']) : '';
        if ($status_code == "true") {
            update_user_meta(get_current_user_id(), '_product_fav_id_' . $prod_id, $prod_id);
            echo '1|' . __("Added to your favourites.", 'adforest-elementor');
        } else {
            if (delete_user_meta(get_current_user_id(), '_product_fav_id_' . $prod_id)) {
                echo '0|' . __("Ad removed from your favourites.", 'adforest-elementor');
            }
        }
        die();
    }
}
/*
 * Vendor grid
 * */
if (!function_exists('adforest_all_vendors_style1')) {
    function adforest_all_vendors_style1($vendors_id = '', $no_of_vendors_ = '')
    {
        $vendor_grid_html = '';
        $no_of_vendors_count = $no_of_vendors_;
        if (!empty($vendors_id) && is_array($vendors_id)) {
            foreach ($vendors_id as $vendor_id) {
                if (is_user_mvx_vendor($vendor_id)) {
                    $vendor = get_mvx_vendor($vendor_id);
                    $vendor_image = ($vendor->get_image('image') != '') ? $vendor->get_image('image', 'woocommerce_gallery_thumbnail') : get_template_directory_uri() . '/images/avatar-vendor.png';
                    $store_banner = ($vendor->get_image('banner') != '') ? $vendor->get_image('banner', 'adforest_vendor_store_front_grid') : get_template_directory_uri() . '/images/v3.png';
                    $store_name = apply_filters('wcmp_vendor_lists_single_button_text', $vendor->page_title);
                    $vendor_data = get_userdata($vendor_id);
                    $registered_date = $vendor_data->user_registered;
                    /* getting complete address */
                    /* check already favourite or not */
                    $fav_v_class = '';
                    $heart_class = "heart-vendor";
                    if (get_user_meta(get_current_user_id(), '_vendor_fav_id_' . $vendor_id, true) == $vendor_id) {
                        $fav_v_class = 'favourited_v';
                        $heart_class = "heart-vendor-fill";
                    }
                    $vendor_grid_html .= '<div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                         <div class="vendor-grid-detail">
                                           <a href="' . esc_url($vendor->get_permalink()) . '">  <img class="card-img-top" src="' . $store_banner . '" alt="Card image cap"></a>
                                                 <div class="' . $heart_class . '">
                                                   <a href="javascript:void(0)" data-vendorid ="' . $vendor_id . '" class="' . $fav_v_class . ' vendor_to_fav" >   <i class="fa fa-heart"></i></a>
                                                 </div>
                                          <div class="card-body vendor-body">
                                          <div class="vendor-name-heading">
                                          <ul class="multi-item">
                                             <li><a href="javascript:void(0)"><img src="' . $vendor_image . '" alt="small-img" class="rounded-circle"></a></li>
                                             <li><a href="' . esc_url($vendor->get_permalink()) . '" class="heading-name">' . esc_html($store_name) . '</a>
                                            <p><i class="fa fa-clock-o"></i> ' . date(get_option('date_format'), strtotime($registered_date)) . '</p></li>
                                         </ul>
                                    </div>
                                </div>
                             </div>
                        </div>';
                }
                if ($no_of_vendors_count == 1) {
                    break;
                }
                $no_of_vendors_count = $no_of_vendors_count - 1;
            }
        } else {
            $vendor_grid_html = '<div class="col-lg-12">' . esc_html__('No Vendor Found!', 'adforest-elementor') . '</div>';
        }

        return $vendor_grid_html;
    }
}

if (!function_exists('adforest_ThemeBtn')) {
    function adforest_ThemeBtn($section_btn = '', $class = '', $onlyAttr = false, $iconBefore = '', $iconAfter = '')
    {
        $buttonHTML = "";
        if (isset($section_btn) && $section_btn != "") {
            $button = []; //adforest_extarct_link($section_btn);
            $class = ($class != "") ? 'class="' . esc_attr($class) . '"' : '';
            $rel = (isset($button["rel"]) && $button["rel"] != "") ? ' rel="' . esc_attr($button["rel"]) . ' "' : "";
            $href = (isset($button["url"]) && $button["url"] != "") ? ' href="' . esc_url($button["url"]) . ' "' : "javascript:void(0);";
            $title = (isset($button["title"]) && $button["title"] != "") ? ' title="' . esc_attr($button["title"]) . '"' : "";
            $target = (isset($button["target"]) && $button["target"] != "") ? ' target="' . esc_attr(trim($button["target"])) . '"' : "";
            $titleText = (isset($button["title"]) && $button["title"] != "") ? esc_html($button["title"]) : "";

            if (isset($button["url"]) && $button["url"] != "") {
                $btn = ($onlyAttr == true) ? $href . $target . $class . $rel : '<a ' . $href . ' ' . $target . ' ' . $class . ' ' . $rel . '>' . $iconBefore . ' ' . esc_html($titleText) . ' ' . $iconAfter . '</a>';
                $buttonHTML = (isset($title)) ? $btn : "";
            }
        }

        return $buttonHTML;
    }
}

if (!function_exists("adforest_get_ad_taxonomy_callback")) {
    function adforest_get_ad_taxonomy_callback($taxonomy = '')
    {
        $args = array(
            'taxonomy' => $taxonomy,
            'parent' => 0,
            'hide_empty' => false,
        );
        
        // Apply WPML filter to ensure terms are retrieved in the current language
        $args = apply_filters('adforest_wpml_show_all_posts', $args);

        $taxonomy_terms = get_terms($args);

        return $taxonomy_terms;
    }
}

add_action('wp_ajax_get_child_categories', 'get_child_categories');
add_action('wp_ajax_nopriv_get_child_categories', 'get_child_categories');

function get_child_categories()
{
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    if ($parent_id === 0) {
        wp_send_json_error('Invalid parent ID.');
        return;
    }

    $args = array(
        'taxonomy' => 'ad_cats',
        'parent' => $parent_id,
        'hide_empty' => false,
    );
    
    // Apply WPML filter to ensure child terms are retrieved in the current language
    $args = apply_filters('adforest_wpml_show_all_posts', $args);

    $categories = get_terms($args);

    if (is_wp_error($categories)) {
        wp_send_json_error($categories->get_error_message());
    } elseif (empty($categories)) {
        wp_send_json_error('No child categories found.');
    } else {
        wp_send_json_success($categories);
    }

    wp_die();
}

add_action('wp_footer', 'adforest_forgot_pass_modal');
if (!function_exists('adforest_forgot_pass_modal')) {
    function adforest_forgot_pass_modal()
    {
        ?>
        <div class="modal fade" id="forgot_password_modal" tabindex="-1" aria-labelledby="forgot_password_modal"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fs-5" id="forgot_password_modal_title">
                            <?php echo __("Forgot Your Password?", "adforest-elementor") ?>
                        </h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="sb-forgot-form">
                        <div class="modal-body">
                            <div class="form-group">
                                <label><?php echo __('Email', 'adforest-elementor'); ?></label>
                                <input placeholder="<?php echo __('Your Email', 'adforest-elementor') ?>"
                                       class="form-control"
                                       type="email" data-parsley-type="email" data-parsley-required="true"
                                       data-parsley-error-message="<?php echo __('Please enter valid email.', 'adforest-elementor') ?>"
                                       data-parsley-trigger="change" name="sb_forgot_email" id="sb_forgot_email">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" id="sb-forgot-pass-token"
                                   value="<?php echo wp_create_nonce('sb_forgot_pass_secure') ?>"/>
                            <button type="submit" id="sb_forgot_submit"
                                    class="btn btn-theme"><?php echo __("Reset My Password", "adforest-elementor"); ?></button>
                            <button class="btn btn-dark" type="button"
                                    id="sb_forgot_msg"><?php echo __('Processing...', 'adforest-elementor') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('adforest_phone_verification_modal')) {
    function adforest_phone_verification_modal()
    {
        $nonce = wp_create_nonce('sb_otp_nonce_phone_login');
        $otp_html = '<form id="sb-ph-verification">
                        <div class="modal-body">           
                            <div class="form-group sb_ver_ph_code_div ">
                                <label>' . __('Enter code', 'adforest-elementor') . '</label>
                                <input class="form-control" type="text" name="sb_ph_number_code" id="sb_ph_number_code">                                              
                                <div id="firebase-recaptcha2"></div>  
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="adt-button-dark btn-sm" type="button" id="sb_verify_otp">' . __('Verify now ', 'adforest-elementor') . '<i id="verify-spinner-icon" class="fa fa-circle-o-notch fa-spin" style="display: none; color: #fff;"></i></button>
                            <button class="adt-button-dark btn-sm no-display" type="button" id="sb_verification_ph_back">' . __('Processing ...', 'adforest-elementor') . '</button>
                            <button class="adt-button-dark btn-sm no-display" type="button" id="sb_verification_ph_code">' . __('Verify now', 'adforest-elementor') . '</button>
                            <button class="adt-button-dark btn-sm " type="button" id="sb_verification_resend">' . __('Resend', 'adforest-elementor') . '</button>
                            <input type="hidden" id="sb_otp_nonce_phone_login" name="sb_otp_nonce_phone_login" value="' . esc_attr($nonce) . '" />
                        </div>
                    </form>';

        echo '<div class="custom-modal">
                <div id="verification_modal" class="sb-verify-modal modal fade" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                            <h2 class="modal-title">' . __('Verify phone number', 'adforest-elementor') . '</h2>
                            </div>
                            ' . $otp_html . '
                        </div>
                    </div>
                </div>
            </div>';
    }
}

if (!function_exists("get_taxonomy_details")) {
    function get_taxonomy_details($taxonomy): array
    {
        $taxonomy_name = $taxonomy->name;
        $taxonomy_slug = $taxonomy->slug;
        $ad_count = get_active_ad_count($taxonomy->term_id, $taxonomy->taxonomy);
        $taxonomy_image = get_option('adforest_taxonomy_image' . $taxonomy->term_id);
        
        // Get the icon for ad_cats taxonomy
        $taxonomy_icon = '';
        if ($taxonomy->taxonomy === 'ad_cats') {
            $term_meta = get_option("taxonomy_term_" . $taxonomy->term_id);
            $taxonomy_icon = isset($term_meta['ad_cat_icon']) ? $term_meta['ad_cat_icon'] : '';
        }

        if (!$taxonomy_image) {
            $taxonomy_image = plugin_dir_url(__FILE__) . 'assets/images/no-image.jpg';
        }

        // Determine display mode: image takes priority, then icon
        $display_mode = 'image';
        if (!empty($taxonomy_icon) && $taxonomy_image === plugin_dir_url(__FILE__) . 'assets/images/no-image.jpg') {
            $display_mode = 'icon';
        }

        $taxonomy_link = get_term_link($taxonomy);

        return array(
            'name' => $taxonomy_name,
            'ad_count' => $ad_count,
            'image' => $taxonomy_image,
            'icon' => $taxonomy_icon,
            'display_mode' => $display_mode,
            'link' => $taxonomy_link,
            'slug' => $taxonomy_slug
        );
    }
}

if (!function_exists('adforest_get_taxonomy_details_by_id')) {
    function adforest_get_taxonomy_details_by_id($term_id, $type = ""): array
    {
        global $adforest_theme;

        $taxonomy = get_term_by('slug', $term_id, $type);

        if (is_wp_error($taxonomy) || !$taxonomy) {
            return array();
        }

        $ad_count = get_active_ad_count($taxonomy->term_id, $taxonomy->taxonomy);

        if (
            ($taxonomy->taxonomy === 'ad_country' && isset($adforest_theme['search_popup_loc_disable']) && $adforest_theme['search_popup_loc_disable'] && $ad_count <= 0)
            || ($taxonomy->taxonomy === 'ad_cats' && isset($adforest_theme['search_popup_cat_disable']) && $adforest_theme['search_popup_cat_disable'] && $ad_count <= 0)
        ) {
            return array();
        }

        $taxonomy_name  = $taxonomy->name;
        $taxonomy_slug  = $taxonomy->slug;
        $taxonomy_image = get_option('adforest_taxonomy_image' . $taxonomy->term_id);

        if (!$taxonomy_image) {
            $taxonomy_image = plugin_dir_url(__FILE__) . 'assets/images/no-image.jpg';
        }

        $taxonomy_link = get_term_link($taxonomy);

        return array(
            'name'     => $taxonomy_name,
            'ad_count' => $ad_count,
            'image'    => $taxonomy_image,
            'link'     => $taxonomy_link,
            'slug'     => $taxonomy_slug,
            "id"       => $taxonomy->term_id,
        );
    }
}

if (!function_exists('get_active_ad_count')) {
    function get_active_ad_count($term_id, $taxonomy)
    {
        $args = array(
            'post_type' => 'ad_post',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
            'fields' => 'ids',
        );

        $query = new WP_Query($args);

        return $query->found_posts;
    }
}

if (!function_exists('get_ad_post_details')) {
    function get_ad_post_details($post_id = null, $truncate_title = 0): array
    {
        global $adforest_theme;

        if ($post_id instanceof WP_Post) {
            $post_id = $post_id->ID;
        }
    
        $post_id = intval($post_id);
    
        if ($post_id === 0) {
            return [];
        }
        
        $ad_selected_cats = wp_get_post_terms($post_id, 'ad_cats', array(
            'orderby' => 'parent',
            'order' => 'ASC'
        ));
        $ad_selected_countries = wp_get_post_terms($post_id, 'ad_country', array(
            'orderby' => 'parent',
            'order' => 'ASC'
        ));
        $category_names = wp_list_pluck($ad_selected_cats, 'name');
        $category_ids = wp_list_pluck($ad_selected_cats, 'term_id');

        $poster_id = get_post_field('post_author', $post_id);
        $poster_name = get_post_meta($post_id, '_adforest_poster_name', true);
        if (empty($poster_name)) {
            $user_info = get_userdata($poster_id);
            $poster_name = $user_info ? $user_info->display_name : '';
        }
        $user_pic = adforest_get_user_dp($poster_id);

        $image_thumbnail_size = 'adforest-single-post';
        $media = adforest_get_ad_images($post_id);
        $media_ids = [];

        if (is_array($media)) {
            foreach ($media as $item) {
                if ($item instanceof WP_Post) {
                    $media_ids[] = (int) $item->ID;
                } elseif (is_numeric($item)) {
                    $media_ids[] = (int) $item;
                }
            }
        } elseif ($media instanceof WP_Post) {
            $media_ids[] = (int) $media->ID;
        } elseif (!empty($media) && is_numeric($media)) {
            $media_ids[] = (int) $media;
        }

        $media_ids = array_values(array_unique(array_filter($media_ids)));

        $img = (!empty($media_ids)) ? wp_get_attachment_image_src($media_ids[0], $image_thumbnail_size) : null;

        $first_img = isset($img[0]) ? $img[0] : "";
        if (empty($first_img)) {
            $first_img = plugin_dir_url(__FILE__) . 'assets/images/no-image.jpg';
        }

        // Retrieve all images
        $all_ad_images = [];
        if (!empty($media_ids)) {
            foreach ($media_ids as $image_id) {
                $image_src = wp_get_attachment_image_src($image_id, $image_thumbnail_size);
                if (!empty($image_src)) {
                    $all_ad_images[] = $image_src[0];
                }
            }
        }

        $ad_title = get_the_title($post_id);

        $ad_location = get_post_meta($post_id, '_adforest_ad_location', true);
        $truncated_location = truncate_string($ad_location, 20);
        $location = $ad_location;
        $truncated_title = $truncate_title == 0 ? truncate_string(get_the_title($post_id), 18) : truncate_string(get_the_title($post_id), $truncate_title);

        $ad_price = get_post_meta($post_id, '_adforest_ad_price', true);
        $ad_price_from = get_post_meta($post_id, '_adforest_ad_price_from', true);
        $ad_price_to = get_post_meta($post_id, '_adforest_ad_price_to', true);
        $ad_price_type = get_post_meta($post_id, '_adforest_ad_price_type', true);
        $curreny = get_post_meta($post_id, '_adforest_ad_currency', true) ?: $adforest_theme['sb_currency'];
        $default_currency = get_woocommerce_currency();
        $currency_symbol = get_woocommerce_currency_symbol($default_currency);

        $default_currency = isset($adforest_theme['sb_currency']) ? $adforest_theme['sb_currency'] : $currency_symbol;

        if (!$curreny) {
            $curreny = $default_currency;
        }

        $ad_link = get_permalink($post_id);

        $is_fav      = ( get_user_meta( get_current_user_id(), '_sb_fav_id_' . $post_id, true ) == $post_id );
        $heart_class = $is_fav ? 'fas fa-heart text-danger' : 'far fa-heart';

        $price_html = '';

        $thousands_sep = isset($adforest_theme['sb_price_separator_remove']) && $adforest_theme['sb_price_separator_remove'] == '1' ? "" : ",";
        if (!empty($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator_remove'] != '1') {
            $thousands_sep = $adforest_theme['sb_price_separator'];
        }

        $decimals = isset($adforest_theme['sb_price_decimals']) ? (int)$adforest_theme['sb_price_decimals'] : 0;
        $decimals_separator = isset($adforest_theme['sb_price_decimals_separator']) ? $adforest_theme['sb_price_decimals_separator'] : '.';

        if (isset($ad_price) && $ad_price != "") {
            $formatted_price = is_numeric($ad_price) ? number_format($ad_price, $decimals, $decimals_separator, $thousands_sep) : $ad_price;

            $price_with_currency = '';
            if (isset($adforest_theme['sb_price_direction'])) {
                switch ($adforest_theme['sb_price_direction']) {
                    case 'right':
                        $price_with_currency = $formatted_price . $curreny;
                        break;
                    case 'right_with_space':
                        $price_with_currency = $formatted_price . " " . $curreny;
                        break;
                    case 'left':
                        $price_with_currency = $curreny . $formatted_price;
                        break;
                    case 'left_with_space':
                        $price_with_currency = $curreny . " " . $formatted_price;
                        break;
                    default:
                        $price_with_currency = $curreny . $formatted_price;
                }
            } else {
                $price_with_currency = $curreny . $formatted_price;
            }

            $price_html = '<strong>' . esc_html($price_with_currency);
            if (!empty($ad_price_type)) {
                $formatted_price_type = str_replace('_', ' ', $ad_price_type);
                $price_html .= ' <small>(' . esc_html($formatted_price_type) . ')</small>';
            }
            $price_html .= '</strong>';
            } elseif ($ad_price_type == 'free') {
                $price_html = '<strong>' . __("Free", "adforest-elementor") . '</strong>';
            } elseif ($ad_price_type == 'no_price') {
                $price_html = '<strong>' . __("No Price", "adforest-elementor") . '</strong>';
            } elseif (empty($ad_price) && empty($ad_price_type)) {
                $price_html = '<strong>' . __("No Price", "adforest-elementor") . '</strong>';
            } elseif ($ad_price_type == 'on_call') {
                $price_html = '<strong>' . __("Price On Call", "adforest-elementor") . '</strong>';
            } elseif ($ad_price_type == 'range') {
                $formatted_from = is_numeric($ad_price_from) ? number_format($ad_price_from, $decimals, $decimals_separator, $thousands_sep) : $ad_price_from;
                $formatted_to = is_numeric($ad_price_to) ? number_format($ad_price_to, $decimals, $decimals_separator, $thousands_sep) : $ad_price_to;

                // Add currency direction
                if (isset($adforest_theme['sb_price_direction'])) {
                    switch ($adforest_theme['sb_price_direction']) {
                        case 'right':
                            $formatted_from .= $curreny;
                            $formatted_to .= $curreny;
                            break;
                        case 'right_with_space':
                            $formatted_from .= " " . $curreny;
                            $formatted_to .= " " . $curreny;
                            break;
                        case 'left':
                            $formatted_from = $curreny . $formatted_from;
                            $formatted_to = $curreny . $formatted_to;
                            break;
                        case 'left_with_space':
                            $formatted_from = $curreny . " " . $formatted_from;
                            $formatted_to = $curreny . " " . $formatted_to;
                            break;
                        default:
                            $formatted_from = $curreny . $formatted_from;
                            $formatted_to = $curreny . $formatted_to;
                    }
                }

                $price_range = $formatted_from . ' - ' . $formatted_to;
                $price_html = '<strong>' . esc_html($price_range) . '</strong>';
            }

        $is_featured = get_post_meta($post_id, '_adforest_is_feature', true);

        return [
            'category_names' => $category_names,
            'img' => $first_img,
            'all_ad_images' => $all_ad_images,
            'ad_title' => $ad_title,
            'truncated_location' => $truncated_location,
            'truncated_title' => $truncated_title,
            'price_html' => $price_html,
            'ad_link' => $ad_link,
            'heart_class' => $heart_class,
            'is_fav'      => $is_fav,
            'is_featured' => $is_featured,
            'location' => $ad_location,
            'ad_poster_name' => $poster_name,
            'ad_poster_img' => $user_pic,
            'category_ids' => $category_ids,
            'price' => $ad_price,
            'categories' => $ad_selected_cats,
            'countries' => $ad_selected_countries,
        ];
    }
}

if (!function_exists('adforest_display_1_ads_sidebar_section')) {
    function adforest_display_1_ads_sidebar_section($sec_ad_type, $section_post_per_page, $ad_img, $show_ad, $ads_title, $show_ads, $ad_title_limit = 10)
    {
        if (!empty($sec_ad_type) || !isset($sec_ad_type)) {
            $args = [
                'post_type' => 'ad_post',
                'posts_per_page' => !empty(
                $section_post_per_page) ? $section_post_per_page : '5',
                'post_status' => 'publish',
            ];

            if ($sec_ad_type === 'recent') {
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
            } elseif ($sec_ad_type === 'featured') {
                $args['meta_key'] = '_adforest_is_feature';
                $args['meta_value'] = '1';
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
            }

            $ads_query = new WP_Query($args);

            if ($show_ads == 'yes' && $ads_query->have_posts()) {
                ?>
                <div class="adt-recent-ads-sidebar">
                    <h4><?php echo esc_html__($ads_title, 'adforest-elementor'); ?></h4>
                    <ul>
                        <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                            $ad_details = get_ad_post_details(get_the_ID());
                            $first_img = $ad_details['img'];
                            $price_html = $ad_details['price_html'];
                            $truncated_title = truncate_string(get_the_title(), $ad_title_limit);
                            ?>
                            <li>
                                <div class="adt-recent-ad-box">
                                    <a href="<?php the_permalink(); ?>" class="recent-img-box">
                                        <img class="img-fluid"
                                             src="<?php echo $first_img; ?>"
                                             alt="<?php echo esc_html__(get_the_title(), 'adforest-elementor'); ?>">
                                    </a>
                                    <div class="recent-img-meta">
                                        <a href="<?php the_permalink(); ?>"><h6><?php echo $truncated_title; ?></h6>
                                        </a>
                                        <?php echo $price_html; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php }
        }
        if (!empty($ad_img) && isset($show_ad) && $show_ad == 'yes') {
            ?>
            <div class="adt-vertical-ad-box">
                <?php echo wp_kses_post($ad_img); ?>
            </div>
        <?php }
    }
}

if (!function_exists('adforest_display_2_ads_sidebar_section')) {
    function adforest_display_2_ads_sidebar_section($show_ads, $ads_title, $sec_ad_type, $section_post_per_page, $ad_img, $ad_img_2, $show_ad_1, $show_ad_2)
    {
        if (!empty($sec_ad_type) || !isset($sec_ad_type)) {
            $args = [
                'post_type' => 'ad_post',
                'posts_per_page' => !empty(
                $section_post_per_page) ? $section_post_per_page : '5',
                'post_status' => 'publish',
            ];

            if ($sec_ad_type === 'recent') {
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
            } elseif ($sec_ad_type === 'featured') {
                $args['meta_key'] = '_adforest_is_feature';
                $args['meta_value'] = '1';
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
            }

            $ads_query = new WP_Query($args);

            if ($ads_query->have_posts() && isset($show_ads) && $show_ads == 'yes') { ?>
                <div class="adt-recent-ads-sidebar">
                    <h4><?php echo esc_html__($ads_title, "adforest-elementor"); ?></h4>
                    <ul>
                        <?php while ($ads_query->have_posts()) : $ads_query->the_post();
                            $ad_details = get_ad_post_details(get_the_ID());
                            $first_img = $ad_details['img'];
                            $price_html = $ad_details['price_html'];
                            $truncated_title = truncate_string(get_the_title(), 20);
                            ?>
                            <li>
                                <div class="adt-recent-ad-box">
                                    <a href="<?php the_permalink(); ?>" class="recent-img-box">
                                        <img class="img-fluid"
                                             src="<?php echo $first_img; ?>"
                                             alt="<?php echo esc_html__(get_the_title(), 'adforest-elementor'); ?>">
                                    </a>
                                    <div class="recent-img-meta">
                                        <a href="<?php the_permalink(); ?>"><h6><?php echo $truncated_title; ?></h6>
                                        </a>
                                        <?php echo $price_html; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php }
        }
        if (isset($show_ad_1) && $show_ad_1 == "yes" && $ad_img != "") {
            ?>
            <div class="adt-vertical-ad-box">
                <?php echo wp_kses_post($ad_img); ?>
            </div>
        <?php }

        if (isset($show_ad_2) && $show_ad_2 == "yes" && $ad_img_2 != "") {
            ?>
            <div class="adt-vertical-ad-box">
                <?php echo wp_kses_post($ad_img_2); ?>
            </div>
        <?php }
    }
}

if (!function_exists("adforest_elementor_img_box")) {
    function adforest_elementor_img_box($ad_link, $img, $is_featured)
    {
        $is_featured_html = '';
        if ($is_featured) {
            $is_featured_html = '<img class="featured-tag"
                                 src="' . plugin_dir_url(__FILE__) . "assets/images/featured.png" . '"
                                 alt="' . esc_html__(get_the_title(), "adforest-elementor") . '">';
        }
        $html = '
                <div class="category-img-box">
                    <a href="' . esc_url($ad_link) . '">
                        <img class="img-fluid"
                             src="' . esc_url($img) . '"
                             alt="' . esc_html__(get_the_title(), "adforest-elementor") . '">
                    </a>
                    ' . $is_featured_html . '
                </div>';

        return $html;
    }
}

function get_all_child_terms($parent_id, $taxonomy, $level = 1)
{
    $args = [
        'taxonomy' => $taxonomy,
        'parent' => $parent_id,
        'hide_empty' => false,
    ];
    
    // Apply WPML filter to ensure child terms are retrieved in the current language
    $args = apply_filters('adforest_wpml_show_all_posts', $args);
    
    $child_terms = get_terms($args);

    $options = [];

    if (!is_wp_error($child_terms) && !empty($child_terms)) {
        foreach ($child_terms as $child) {
            $prefix = str_repeat('— ', $level);
            $options[$child->term_id] = $prefix . $child->name;

            $sub_categories = get_all_child_terms($child->term_id, $taxonomy, $level + 1);

            $options += $sub_categories;
        }
    }

    return $options;
}

function get_all_child_terms_slug($parent_id, $taxonomy, $level = 1)
{
    $args = [
        'taxonomy' => $taxonomy,
        'parent' => $parent_id,
        'hide_empty' => false,
    ];
    
    // Apply WPML filter to ensure child terms are retrieved in the current language
    $args = apply_filters('adforest_wpml_show_all_posts', $args);
    
    $child_terms = get_terms($args);

    $options = [];

    if (!is_wp_error($child_terms) && !empty($child_terms)) {
        foreach ($child_terms as $child) {
            $prefix = str_repeat('— ', $level);
            $options[$child->slug] = $prefix . $child->name;

            $sub_categories = get_all_child_terms_slug($child->term_id, $taxonomy, $level + 1);

            $options += $sub_categories;
        }
    }

    return $options;
}


if (!function_exists('adforest_product_grid_1')) {
    /**
     * Outputs a single “cyber sale” product card.
     *
     * @param WC_Product|null $product
     */
    function adforest_product_grid_1($product = null, $title_limit = 35)
    {
        if (!$product instanceof WC_Product) {
            global $product;
            if (!$product instanceof WC_Product) {
                return;
            }
        }

        $id = $product->get_id();
        $permalink = get_permalink($id);
        $title = truncate_string(get_the_title($id), $title_limit);
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $rating_count = $product->get_rating_count();
        $avg_rating = (float)$product->get_average_rating();

        $img_src = get_the_post_thumbnail_url($id, 'full')
            ?: trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';

        $cart_icon = esc_url(plugin_dir_url(__FILE__) . 'assets/images/cart.svg');
        ?>
        <div class="adt-cyber-sale-product-card">
            <div class="cyber-sale-product-img-box">
                <a href="<?php echo esc_url($permalink); ?>">
                    <img src="<?php echo esc_url($img_src); ?>"
                         alt="<?php esc_attr_e('Product', 'adforest-elementor'); ?>">
                </a>
            </div>
            <div class="cyber-sale-product-content">
                <span class="price">
                    <?php if ($sale_price !== '') : ?>
                        <?php echo wp_kses_post(wc_price($sale_price)); ?>
                        <del><?php echo wp_kses_post(wc_price($regular_price)); ?></del>
                    <?php else : ?>
                        <?php echo wp_kses_post(wc_price($regular_price)); ?>
                    <?php endif; ?>
                </span>

                <a href="<?php echo esc_url($permalink); ?>">
                    <h4><?php echo esc_html__($title, 'adforest-elementor'); ?></h4>
                </a>

                <div class="rating">
                    <span>
                        <?php
                        /* translators: %s = reviews count */
                        echo sprintf(
                            esc_html__('%s Reviews', 'adforest-elementor'),
                            number_format_i18n($rating_count)
                        );
                        ?>
                    </span>
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= ceil($avg_rating)
                            ? '<i class="fas fa-star" aria-hidden="true"></i>'
                            : '<i class="far fa-star" aria-hidden="true"></i>';
                    }
                    ?>
                </div>

                <button
                        type="button"
                        class="cart-btn custom_add_to_cart_button_grid"
                        data-product-id="<?php echo esc_attr($id); ?>"
                        aria-label="<?php esc_attr_e('Add to cart', 'adforest-elementor'); ?>"
                >
                    <img src="<?php echo $cart_icon; ?>" alt="<?php esc_attr_e('Cart icon', 'adforest-elementor'); ?>">
                </button>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('adforest_product_grid_2')) {
    /**
     * Outputs a multivendor category card.
     *
     * @param WC_Product $product The product object.
     * @param array $args Optional. Overrides:
     *                                  - title_limit  (int)   Max words in title. Default 10.
     *                                  - fav_class    (string) CSS class for favorite toggle.
     *                                  - heart_filled (string) Icon class when favorited.
     */
    function adforest_product_grid_2($product, $args = [])
    {
        if (!$product instanceof WC_Product) {
            return;
        }

        $defaults = [
            'title_limit' => 10,
            'fav_class' => '',
            'heart_filled' => 'fa-heart-o',
        ];
        $args = wp_parse_args($args, $defaults);

        $id = $product->get_id();
        $permalink = get_permalink($id);
        $title = truncate_string(get_the_title($id), intval($args['title_limit']));
        $avg_rating = (float)$product->get_average_rating();
        $rating_count = $product->get_rating_count();
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $image_src = get_the_post_thumbnail_url($id, 'full')
            ?: trailingslashit(get_template_directory_uri()) . 'images/no-image.jpg';

        ?>
        <div class="adt-multivendor-category-ad-card">
            <div class="category-img-box">
                <a href="<?php echo esc_url($permalink); ?>">
                    <img class="img-fluid" src="<?php echo esc_url($image_src); ?>"
                         alt="<?php esc_attr_e('Product image', 'adforest-elementor'); ?>">
                </a>
            </div>
            <div class="category-content-box">
                <div class="rating">
                    <span><?php echo esc_html($avg_rating); ?></span>
                    <small>
                        <?php
                        echo sprintf(
                            esc_html__('%s Reviews', 'adforest-elementor'),
                            number_format_i18n($rating_count)
                        );
                        ?>
                    </small>
                </div>

                <a href="<?php echo esc_url($permalink); ?>">
                    <h5><?php echo esc_html($title); ?></h5>
                </a>

                <strong class="price">
                    <?php if ($sale_price !== '') : ?>
                        <del><?php echo wp_kses_post(wc_price($regular_price)); ?></del>
                        <ins><?php echo wp_kses_post(wc_price($sale_price)); ?></ins>
                    <?php else : ?>
                        <?php echo wp_kses_post(wc_price($regular_price)); ?>
                    <?php endif; ?>
                </strong>

                <div class="detail-btn-box">
                    <a href="<?php echo esc_url($permalink); ?>"
                       class="detail-btn"><?php esc_html_e('Detail Now', 'adforest-elementor'); ?></a>

                    <a href="javascript:void(0);"
                       class="favourite product_to_fav <?php echo esc_attr($args['fav_class']); ?>"
                       data-product-id="<?php echo esc_attr($id); ?>">
                        <i class="fa <?php echo esc_attr($args['heart_filled']); ?>"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
