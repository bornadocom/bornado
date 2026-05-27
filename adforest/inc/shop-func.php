<?php
/* Shop Settings */
add_action('pre_get_posts', 'adforest_shop_filter_cat');
if (!function_exists('adforest_shop_filter_cat')) {

    function adforest_shop_filter_cat($query)
    {
        if (!is_admin() && is_post_type_archive('product') && $query->is_main_query() && is_shop()) {

            $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'adforest_classified_pkgs',
                        'operator' => 'NOT IN'
                    ),
                )
            );
        }
    }

}

if (!function_exists('adforest_shopPriceDirection')) {

    function adforest_shopPriceDirection($price = '', $curreny = '')
    {

        global $adforest_theme;
        $price = (isset($price) && $price != "") ? $price : 0;

        $thousands_sep = ",";
        if (isset($adforest_theme['sb_price_separator']) && $adforest_theme['sb_price_separator'] != '') {
            $thousands_sep = $adforest_theme['sb_price_separator'];
        }
        $decimals = 0;
        if (isset($adforest_theme['sb_price_decimals']) && $adforest_theme['sb_price_decimals'] != '') {
            $decimals = $adforest_theme['sb_price_decimals'];
        }
        $decimals_separator = ".";
        if (isset($adforest_theme['sb_price_decimals_separator']) && $adforest_theme['sb_price_decimals_separator'] != '') {
            $decimals_separator = $adforest_theme['sb_price_decimals_separator'];
        }
        // Price format
        $price = number_format((float)$price, $decimals, $decimals_separator, $thousands_sep);

        if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right') {
            $price = $price . $curreny;
        } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'right_with_space') {
            $price = $price . " " . $curreny;
        } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left') {
            $price = $curreny . $price;
        } else if (isset($adforest_theme['sb_price_direction']) && $adforest_theme['sb_price_direction'] == 'left_with_space') {
            $price = $curreny . " " . $price;
        } else {
            $price = $curreny . $price;
        }
        return $price;
    }

}

/**
 * Change number of products that are displayed per page (shop page)
 */
add_filter('loop_shop_per_page', 'adforest_new_loop_shop_per_page', 20);

if (!function_exists('adforest_new_loop_shop_per_page')) {

    function adforest_new_loop_shop_per_page($cols)
    {
        global $adforest_theme;
        $cols = (isset($adforest_theme['shop-number-of-products'])) ? $adforest_theme['shop-number-of-products'] : 9;
        return $cols;
    }

}

/* AdForest Custom Package */
if (!function_exists('adforest_register_custom_packages')) {

    function adforest_register_custom_packages()
    {
        if (class_exists('WooCommerce')) {
            if (!class_exists('WC_Product_adforest_custom_packages')) {

                class WC_Product_adforest_custom_packages extends WC_Product
                {

                    public $product_type = 'adforest_classified_pkgs';

                    public function __construct($product)
                    {
                        parent::__construct($product);
                    }

                }

            }
        }


        if (class_exists('WooCommerce')) {
            if (!class_exists('WC_Product_adforest_feature_pkgs')) {

                class WC_Product_adforest_feature_pkgs extends WC_Product
                {

                    public $product_type = 'adforest_feature_pkgs';

                    public function __construct($product)
                    {
                        parent::__construct($product);
                    }

                }

            }
        }


        if (class_exists('WooCommerce')) {
            if (!class_exists('WC_Product_adforest_alert_pkgs')) {

                class WC_Product_adforest_alert_pkgs extends WC_Product
                {

                    public $product_type = 'adforest_alert_pkgs';

                    public function __construct($product)
                    {
                        parent::__construct($product);
                    }

                }

            }

            if (class_exists('WooCommerce')) {
                if (!class_exists('WC_Product_adforest_bump_up_pkgs')) {

                    class WC_Product_adforest_bump_up_pkgs extends WC_Product
                    {

                        public $product_type = 'adforest_bump_up_pkgs';

                        public function __construct($product)
                        {
                            parent::__construct($product);
                        }

                    }

                }
            }

            if (class_exists('WooCommerce')) {
                if (!class_exists('WC_Product_adforest_pay_per_post_pkgs')) {

                    class WC_Product_adforest_pay_per_post_pkgs extends WC_Product
                    {

                        public $product_type = 'adforest_pay_per_post_pkgs';

                        public function __construct($product)
                        {
                            parent::__construct($product);
                        }

                    }

                }
            }
        }
    }

}
add_action('init', 'adforest_register_custom_packages', 1);
//AdForest Custom Package Ends

if (!function_exists('adforest_add_packages_type')) {

    function adforest_add_packages_type($types)
    {

        global $adforest_theme;
        $types['adforest_classified_pkgs'] = __('AdForest Packages', 'adforest');
        $types['adforest_feature_pkgs'] = __('Feature Ad Packages', 'adforest');
        $types['adforest_bump_up_pkgs'] = __('Bump Up Ad Packages', 'adforest');
        $types['adforest_pay_per_post_pkgs'] = __('Pay Per Post Packages', 'adforest');
        if (isset($adforest_theme['sb_ad_alerts_paid']) && $adforest_theme['sb_ad_alerts_paid']) {


        }
        return $types;
    }

}
add_filter('product_type_selector', 'adforest_add_packages_type', 1);

//class for custom product type
if (!function_exists('adforest_woocommerce_product_class')) {

    function adforest_woocommerce_product_class($classname, $product_type)
    {
        if ($product_type == 'adforest_classified_pkgs') {
            $classname = 'WC_Product_adforest_custom_packages';
        } else if ($product_type == 'adforest_alert_pkgs') {

        } else if ($product_type == 'adforest_feature_pkgs') {
            $classname = 'WC_Product_adforest_feature_pkgs';
        }

        else if ($product_type == 'adforest_bump_up_pkgs') {
            $classname = 'WC_Product_adforest_bump_up_pkgs';
        } else if ($product_type == 'adforest_pay_per_post_pkgs') {
            $classname = 'WC_Product_adforest_pay_per_post_pkgs';
        }
        return $classname;
    }

}
add_filter('woocommerce_product_class', 'adforest_woocommerce_product_class', 10, 2);
/* * * Show pricing fields for simple_rental product. */
if (!function_exists('adforest_render_package_custom_js')) {

    function adforest_render_package_custom_js()
    {

        if ('product' != get_post_type()) :
            return;
        endif;
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                jQuery('#sb_thmemes_adforest_metaboxes').hide();
                jQuery('#adforest_pay_per_post_metaboxes').hide();
                jQuery('.options_group.pricing').addClass('show_if_adforest_classified_pkgs').show();
                jQuery('.options_group.pricing').addClass('show_if_adforest_alert_pkgs').show();
                jQuery('.options_group.pricing').addClass('show_if_adforest_feature_pkgs').show();
                // jQuery('.options_group.pricing').addClass('show_if_profile_badge_pkgs').show();
                jQuery('.options_group.pricing').addClass('show_if_adforest_bump_up_pkgs').show();
                jQuery('.options_group.pricing').addClass('show_if_adforest_pay_per_post_pkgs').show();


                jQuery('#product-type').on('change', function () {
                    console.log("Product Type Change");
                    console.log("Product Type Value: ", jQuery(this).val());
                    if (jQuery(this).val() == 'adforest_classified_pkgs' || jQuery(this).val() == 'subscription' || jQuery(this).val() == 'variable-subscription') {
                        jQuery('#sb_thmemes_adforest_metaboxes').show();
                        jQuery('#feature_expiry_meta').hide();
                    } else if (jQuery(this).val() == 'adforest_feature_pkgs') {
                        jQuery('#feature_expiry_meta').show();
                        jQuery('#sb_thmemes_adforest_metaboxes').hide();
                        jQuery('#adforest_pay_per_post_metaboxes').hide();

                    } else if (jQuery(this).val() == 'adforest_bump_up_pkgs') {
                        jQuery('#feature_expiry_meta').hide();
                        jQuery('#sb_thmemes_adforest_metaboxes').hide();
                        jQuery('#adforest_pay_per_post_metaboxes').hide();
                    } else if (jQuery(this).val() == 'adforest_pay_per_post_pkgs') {
                        jQuery('#adforest_pay_per_post_metaboxes').show();
                        jQuery('#sb_thmemes_adforest_metaboxes').hide();
                        jQuery('#feature_expiry_meta').hide();
                    } else {
                        jQuery('#sb_thmemes_adforest_metaboxes').hide();
                        jQuery('#adforest_pay_per_post_metaboxes').hide();
                        jQuery('#feature_expiry_meta').hide();
                    }
                });


                jQuery('#product-type').trigger('change');
            });

        </script><?php
    }

}
add_action('admin_footer', 'adforest_render_package_custom_js');

if (!function_exists('adforest_hide_attributes_data_panel')) {

    function adforest_hide_attributes_data_panel($tabs)
    {

        $tabs['attribute']['class'][] = 'hide_if_adforest_classified_pkgs';
        $tabs['shipping']['class'][] = 'hide_if_adforest_classified_pkgs';
        $tabs['linked_product']['class'][] = 'hide_if_adforest_classified_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_classified_pkgs';

        $tabs['attribute']['class'][] = 'hide_if_adforest_alert_pkgs';
        $tabs['shipping']['class'][] = 'hide_if_adforest_alert_pkgs';
        $tabs['linked_product']['class'][] = 'hide_if_adforest_alert_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_alert_pkgs';


        $tabs['attribute']['class'][] = 'hide_if_adforest_feature_pkgs';
        $tabs['shipping']['class'][] = 'hide_if_adforest_feature_pkgs';
        $tabs['linked_product']['class'][] = 'hide_if_adforest_feature_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_feature_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_feature_pkgs';

        $tabs['attribute']['class'][] = 'hide_if_adforest_bump_up_pkgs';
        $tabs['shipping']['class'][] = 'hide_if_adforest_bump_up_pkgs';
        $tabs['linked_product']['class'][] = 'hide_if_adforest_bump_up_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_bump_up_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_bump_up_pkgs';


        $tabs['attribute']['class'][] = 'hide_if_adforest_pay_per_post_pkgs';
        $tabs['shipping']['class'][] = 'hide_if_adforest_pay_per_post_pkgs';
        $tabs['linked_product']['class'][] = 'hide_if_adforest_pay_per_post_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_pay_per_post_pkgs';
        $tabs['advanced']['class'][] = 'hide_if_adforest_pay_per_post_pkgs';


        return $tabs;
    }

}
add_filter('woocommerce_product_data_tabs', 'adforest_hide_attributes_data_panel');
if (!function_exists('adforest_get_woo_categories')) {
    function adforest_get_woo_categories($post_id = 0, $product_cat = 'product_cat', $args = array())
    {
        $post_id = (int)$post_id;
        $defaults = array();
        $args = wp_parse_args($args, $defaults);
        $product_categories = wp_get_object_terms($post_id, $product_cat, $args);
        $cats = array();
        $html = '';
        foreach ($product_categories as $c) {
            $cat = get_category($c);
            $html .= '<a href="' . esc_url(get_term_link($cat->term_id)) . '">' . $cat->name . '</a>,';
        }
        $return_value = rtrim($html, ",");
        return $return_value;
    }

}
if (!function_exists('adforest_get_woo_stars')) {
    function adforest_get_woo_stars($average = 0)
    {
        $starsHTML = '';
        $ratting = round($average);
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $ratting) {
                $starsHTML .= '<i class="fa fa-star colored"></i>';
            } else {
                $starsHTML .= '<i class="fa fa-star"></i>';
            }
        }
        return $starsHTML;
    }
}

if (!function_exists('adforest_get_product_type')) {
    function adforest_get_product_type($product_id)
    {
        $cache_key = WC_Cache_Helper::get_cache_prefix('product_' . $product_id) . '_type_' . $product_id;
        $product_type = wp_cache_get($cache_key, 'products');
        if ($product_type) {
            return $product_type;
        }
        $post_type = get_post_type($product_id);
        if ('product_variation' === $post_type) {
            $product_type = 'variation';
        } elseif ('product' === $post_type) {
            $terms = get_the_terms($product_id, 'product_type');
            $product_type = !empty($terms) ? sanitize_title(current($terms)->name) : 'simple';
        } else {
            $product_type = false;
        }
        wp_cache_set($cache_key, $product_type, 'products');
        return $product_type;
    }
}


if (!function_exists('adforest_get_product_details')) {
    function adforest_get_product_details($product)
    {
        global $adforest_theme;
        $product_id = get_the_ID();
        $product_type = wc_get_product($product_id);
        $currency = get_woocommerce_currency_symbol();
        $price = $product->get_regular_price();
        $sale = $product->get_sale_price();
        $product_typee = adforest_get_product_type($product_id);
        if (isset($product_typee) && $product_typee == 'variable') {
            $available_variations = $product->get_available_variations();
            if (isset($available_variations[0]['variation_id']) && !empty($available_variations[0]['variation_id'])) {
                $variation_id = $available_variations[0]['variation_id'];
                $variable_product1 = new WC_Product_Variation($variation_id);
                $price = $variable_product1->get_regular_price();
                $sale = $variable_product1->get_sale_price();
            }
        }
        $currency = get_woocommerce_currency_symbol();
        $newness_days = isset($adforest_theme['shop_newness_product_days']) ? $adforest_theme['shop_newness_product_days'] : 30;
        $created = strtotime($product->get_date_created());
        $new_badge_html = '';
        /* here we use static badge date. */
        if ((current_time('timestamp') - (60 * 60 * 24 * $newness_days)) < $created) {
            $new_badge_html = '<div class="ribbon-container"><a href="javascript:void(0);" class="ribbon">' . __("New", "adforest") . '</a></div>';
        }
        $prod_image_src = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'adforest-vendor_page_prod_img');
        $prod_img_html = '';
        if (isset($prod_image_src) && is_array($prod_image_src)) {
            $prod_img_html = '<a href="' . get_the_permalink($product_id) . '"><img src="' . $prod_image_src[0] . '" alt="' . get_the_title($product_id) . '" class="img-fluid"/></a>';
        } else {
            $prod_img_html = '<a href="' . get_the_permalink($product_id) . '"><img class="img-fluid" alt="' . get_the_title() . '" src="' . esc_url(wc_placeholder_img_src()) . '"></a>';
        }
        $price_html = '<h5>' . esc_html(adforest_shopPriceDirection($price, $currency)) . '</h5>';
        if ($sale) {
            $price_html = '<h5>' . esc_html(adforest_shopPriceDirection($sale, $currency)) . '<span class="del">' . esc_html(adforest_shopPriceDirection($price, $currency)) . '</span></h5>';
        }
        $rating_html = "";
        if ($product->get_average_rating() > 0) {
            $rating_html = '<div class="listing-ratings">
                                                <div class="woocommerce-product-rating">' . wc_get_rating_html($product->get_average_rating()) . '  
                                                    <span class="product-review-count">' . $product->get_review_count() . '&nbsp' . esc_html__('Reviews', 'adforest') . '</span>
                                                </div>
                                            </div>';
        }
        /* check already favourite or not */
        $fav_class = '';
        if (get_user_meta(get_current_user_id(), '_product_fav_id_' . $product_id, true) == $product_id) {
            $fav_class = 'favourited';
        }
        return '<div class="wrapper-latest-product woocommerce listing-list-items-1">
                       <div class="top-product-img">
                       <a href="' . get_the_permalink() . '">
                         ' . $prod_img_html . '
                         </a>
                       </div>
                       <div class="bottom-listing-product">
                           <div class="listing-ratings">
                               ' . $rating_html . '
                           </div>
                            <h4><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h4>
                                                ' . $price_html . '
                            <div class="shop-detail-listing">
                              <a href="' . get_the_permalink() . '" class="btn btn-theme btn-listing">' . esc_html__('Shop Now', 'adforest') . '</a>                      
                            </div>
        
                         </div>
                   <div class="fav-product-container"> 
                            <a href="javascript:void(0)" class="product_to_fav   favourited" data-productid="4411">  <span class="fa fa-heart hear-btn"></span></a>
                             </div>  
                     </div>
       ';

    }
}
if (!function_exists('adforest_get_recent_products_list')) {
    function adforest_get_recent_products_list($product)
    {
        $product_id = get_the_ID();
        $prod_image_src = wp_get_attachment_image_src(get_post_thumbnail_id($product_id));
        $prod_img_html = '';
        if (isset($prod_image_src) && is_array($prod_image_src)) {
            $prod_img_html = '<a href="' . get_the_permalink($product_id) . '"><img src="' . $prod_image_src[0] . '" alt="' . get_the_title($product_id) . '" class="img-fluid"/></a>';
        } else {
            $prod_img_html = '<a href="' . get_the_permalink($product_id) . '"><img class="img-fluid" alt="' . get_the_title() . '" src="' . esc_url(wc_placeholder_img_src()) . '"></a>';
        }
        return '<div class="recent-section-content-1">
                                <div class="row">
                                    <div class="col-sm-5">
                                        <div class="img-recent-1">
                                            ' . $prod_img_html . '
                                        </div>
                                    </div>
                                    <div class="col-sm-7">
                                        <div class="prodcut-heading">
                                            
                                         <h3>' . esc_html(get_the_title()) . '</h3>
                                      
                                            <a href="' . get_the_permalink() . '"><i class="fa fa-long-arrow-right"></i>' . esc_html__('Shop Now', 'adforest') . '</a>
                                        </div>
                                    </div>
                                </div>
                            </div>';
    }
}

if (!function_exists('adforest_get_recent_products_list_1')) {
    function adforest_get_recent_products_list_1($product)
    {
        $product_id = get_the_ID();

        $prod_image_src = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'full');

        if (isset($prod_image_src[0])) {
            $prod_img_html = '<a href="' . esc_url(get_the_permalink($product_id)) . '" class="recent-img-box">
                                <img class="img-fluid" src="' . esc_url($prod_image_src[0]) . '" alt="' . esc_attr(get_the_title($product_id)) . '">
                              </a>';
        } else {
            $prod_img_html = '<a href="' . esc_url(get_the_permalink($product_id)) . '" class="recent-img-box">
                                <img class="img-fluid" src="' . esc_url(wc_placeholder_img_src()) . '" alt="' . esc_attr(get_the_title($product_id)) . '">
                              </a>';
        }

        $title = truncate_string(get_the_title(), 25);

        $price_html = '';
        if (method_exists($product, 'get_price_html')) {
            $price_html = $product->get_price_html();
        }

        // Build the markup.
        $output = '<ul><li>
                        <div class="adt-recent-ad-box">
                            ' . $prod_img_html . '
                            <div class="recent-img-meta">
                                <a href="' . esc_url(get_the_permalink($product_id)) . '">
                                    <h6>' . esc_html($title) . '</h6>
                                </a>
                                <strong>' . $price_html . '</strong>
                            </div>
                        </div>
                    </li></ul>';

        return $output;
    }
}