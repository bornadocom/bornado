<?php
/*
 * Adforest Elementor Function Class
 */
if(!class_exists('Adforest_Elementor_Functions')){
Class Adforest_Elementor_Functions2 {

    public function __construct() {
        add_filter('adforest_elementor_url_field', array($this, 'adforest_elementor_url_field_callback'), 10, 2);
        add_filter('adforest_elementor_ads_styles', array($this, 'adforest_elementor_ads_styles_callback'));
        add_filter('adforest_elementor_ads_categories', array($this, 'adforest_elementor_ads_categories_callback'), 10, 4);
        add_filter('adforest_elementor_get_packages', array($this, 'adforest_elementor_get_packages_callback'));
        add_filter('adforest_elementor_get_product_categories', array($this, 'adforest_product_categories_callback'));
    }

    public function adforest_elementor_get_packages_callback($type = 'products') {

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

    public function adforest_elementor_ads_categories_callback($cats_arr = array(), $taxonomy = 'ad_cats', $all = '' ,$hide_empty = 0) {

        
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
        $args = apply_filters('adforest_wpml_show_all_posts', $args); // for all lang texonomies
        $ad_cats = get_terms($taxonomy, $args);

        if (!empty($ad_cats)) {
            foreach ($ad_cats as $cat) {
                 if(!empty($cat)){
                $count = isset($cat->count)  ?  $cat->count  : "";
                $cats_arr[$cat->term_id] = wp_specialchars_decode($cat->name) . ' ( ' . urldecode_deep($cat->slug) . ' ) ' . ' ( ' . $count . ' ) ';
            }
        }}

        return $cats_arr;
    }

    public function adforest_elementor_ads_styles_callback() {

        $grid_array;
     
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
                'grid_11' => __('Grid 11', 'adforest-elementor'),  
                'list' => __('List', 'adforest-elementor'),             
            );
    

        return $grid_array;
    }

    public function adforest_elementor_url_field_callback($link_html, $params_data = array()) {
        extract($params_data);
        $buttonHTML = '';
        if (isset($adforest_elementor) && $adforest_elementor) {
            if (isset($btn_key["url"]) && $btn_key["url"] != "") {
                $is_external = isset($btn_key["is_external"]) && $btn_key["is_external"] == 1 ? ' target="__blank" ' : '';
                $nofollow = isset($btn_key["nofollow"]) && $btn_key["nofollow"] == 1 ? ' rel="nofollow" ' : '';
                $custom_attr = isset($btn_key["custom_attributes"]) && $btn_key["custom_attributes"] != '' ? $btn_key["custom_attributes"] : '';
                $class = ( $btn_class != "" ) ? 'class="' . esc_attr($btn_class) . '" ' : '';

                if (isset($onlyAttr) && $onlyAttr) {
                    $btn_html = $class . 'href="' . $btn_key["url"] . '" ' . $is_external . ' ' . $nofollow . ' ' . $custom_attr;
                } else {
                    $btn_html = '<a ' . $class . 'href="' . $btn_key["url"] . '" ' . $is_external . ' ' . $nofollow . ' ' . $custom_attr . '>' . $iconBefore . ' ' . esc_html($titleText) . ' ' . $iconAfter . '</a>';
                }

                $buttonHTML = ( isset($titleText) ) ? $btn_html : "";
            }
        }
        return $buttonHTML;
    }

    public function adforest_product_categories_callback($term_type = 'product_cat') {
        global $adforest_theme;
        if (isset($adforest_theme['shop-turn-on']) && $adforest_theme['shop-turn-on']) {
            $terms = get_terms($term_type, array('hide_empty' => false));
            $result = array();
            if (count((array) $terms) > 0 && !empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $result[$term->slug] = $term->name;
                }
            }
            return $result;
        }
    }

}
}

new Adforest_Elementor_Functions2();

add_action('init', 'adforest_elementor_plugin_initializaion_func');
if (!function_exists('adforest_elementor_plugin_initializaion_func')) {

    function adforest_elementor_plugin_initializaion_func() {

        $code_verification = get_option('_sb_purchase_code_ele_verification');
        if (isset($code_verification) && $code_verification == 'done') {
            
        } else {
            $my_keyname = array("_", "s", "b", "_", "p", "u", "r", "c", "h", "a", "s", "e", "_", "c", "o", "d", "e");
            $kyname = implode($my_keyname);
            $my_keynamelink = array("h", "t", "t", "p", "s", ":", "/", "/", "a", "u", "t", "h", "e", "n", "t", "i", "c", "a", "t", "e", ".", "s", "c", "r", "i", "p", "t", "s", "b", "u", "n", "d", "l", "e", ".", "c", "o", "m", "/", "a", "d", "f", "o", "r", "e", "s", "t", "/", "v", "e", "r", "i", "f", "y", "_", "p", "c", "o", "d", "e", ".", "p", "h", "p");
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
    function adforest_all_vendors_style1($vendors_id = '', $no_of_vendors_ = '') {
        $vendor_grid_html = '';
        $no_of_vendors_count = $no_of_vendors_;
        if (!empty($vendors_id) && is_array($vendors_id)) {
            foreach ($vendors_id as $vendor_id) {
                if (is_user_mvx_vendor($vendor_id)) {
                    $vendor = get_mvx_vendor ($vendor_id);
                    $vendor_image = ($vendor->get_image('image') != '') ? $vendor->get_image('image', 'woocommerce_gallery_thumbnail') : get_template_directory_uri() . '/images/avatar-vendor.png';
                    $store_banner = ($vendor->get_image('banner') != '') ? $vendor->get_image('banner', 'adforest_vendor_store_front_grid') : get_template_directory_uri() . '/images/v3.png';
                    $store_name   = apply_filters('wcmp_vendor_lists_single_button_text', $vendor->page_title);
                    $vendor_data  = get_userdata($vendor_id);
                    $registered_date = $vendor_data->user_registered;
                    /* getting complete address */
                      /* check already favourite or not */
                    $fav_v_class = '';
                    $heart_class   =    "heart-vendor";
                    if (get_user_meta(get_current_user_id(), '_vendor_fav_id_' . $vendor_id, true) == $vendor_id) {
                        $fav_v_class = 'favourited_v';
                        $heart_class   =  "heart-vendor-fill";
                    }
                    $vendor_grid_html .= '<div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                         <div class="vendor-grid-detail">
                                           <a href="'.esc_url($vendor->get_permalink()).'">  <img class="card-img-top" src="'.$store_banner.'" alt="Card image cap"></a>
                                                 <div class="'.$heart_class.'">
                                                   <a href="javascript:void(0)" data-vendorid ="'.$vendor_id.'" class="'.$fav_v_class.' vendor_to_fav" >   <i class="fa fa-heart"></i></a>
                                                 </div>
                                          <div class="card-body vendor-body">
                                          <div class="vendor-name-heading">
                                          <ul class="multi-item">
                                             <li><a href="javascript:void(0)"><img src="'.$vendor_image.'" alt="small-img" class="rounded-circle"></a></li>
                                             <li><a href="'.esc_url($vendor->get_permalink()).'" class="heading-name">'.esc_html($store_name).'</a>
                                            <p><i class="fa fa-clock-o"></i> '.date(get_option('date_format'), strtotime($registered_date)).'</p></li>
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
            $vendor_grid_html = '<div class="col-lg-12">' . esc_html__('No Vendor Found!', 'adforest') . '</div>';
        }
        return $vendor_grid_html;
    }
}