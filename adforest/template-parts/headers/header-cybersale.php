<?php
global $adforest_theme;
$site_logo = isset($adforest_theme['sb_site_logo']['url']) ? $adforest_theme['sb_site_logo']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$sb_sign_in_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : "";
$sb_sign_up_page = isset($adforest_theme['sb_sign_up_page']) ? $adforest_theme['sb_sign_up_page'] : "";
$ad_in_menu_text = isset($adforest_theme['ad_in_menu_text']) ? $adforest_theme['ad_in_menu_text'] : "";
// Apply WPML translation using icl_t()
if (function_exists('icl_t')) {
    $ad_in_menu_text = icl_t('adforest_theme', 'ad_in_menu_text', $ad_in_menu_text);
}
$sb_post_ad_page = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : "";
$responsive_logo = isset($adforest_theme['sb_site_logo_mobile']['url']) ? $adforest_theme['sb_site_logo_mobile']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$home_page_logo = isset($adforest_theme['sb_home_logo']['url']) ? $adforest_theme['sb_home_logo']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$user_id = get_current_user_id();

$is_sticky_header = isset($adforest_theme['sb_sticky_header']) ? $adforest_theme['sb_sticky_header'] : '';
$sticky_class = "";
if ($is_sticky_header == '1') {
    $sticky_class = "sticky-header";
}

$sb_profile_page = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
$product_categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
));
$adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
// Get current query parameters
$current_query = $_GET;

$title = isset($_GET['title']) ? $_GET['title'] : "";
$header_bg_image = isset($adforest_theme['adforest_shop_vendor_2_header_image']['url']) ? $adforest_theme['adforest_shop_vendor_2_header_image']['url'] : "";
global $wpdb;
$fav_like = $wpdb->esc_like('_product_fav_id_') . '%';
$query = $wpdb->prepare(
    "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key like %s ",
    $user_id,
    $fav_like
);
$products = $wpdb->get_results($query);
$fav_product_list = "";
if (!empty($products) && function_exists('wc_get_product')) {
	$count = 0;
	$max_iterations = 10;

	foreach ($products as $product) {
		if ($count >= $max_iterations) {
			break;
		}

		$product_id = $product->meta_value;
		$_product = wc_get_product($product_id);

		if (!empty($_product)) {
			$fav_class = "";
			if (get_user_meta(get_current_user_id(), '_product_fav_id_' . $product_id, true) == $product_id) {
				$fav_class = 'favourited';
			}
			$product_price = $_product->get_price_html();
			$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_gallery_thumbnail'));
			$product_name = $_product->get_name();
			$truncated_title = truncate_string($product_name, 15);

			$fav_product_list .= '<li>
                                <div class="img-produt-1">
                                    <a href="' . get_the_permalink($product_id) . '">' . $thumbnail . '</a>
                                </div>
                                <div class="product-cart-head">
                                    <div>
                                        <a href="' . get_the_permalink($product_id) . '">
                                            <h3>' . $truncated_title . '</h3>
                                        </a>
                                        ' . $product_price . '
                                    </div>
                                </div>
                                <div>
                                    <a href="javascript:void(0)" class="product_to_fav ' . $fav_class . '" data-productid="' . $product_id . '">
                                        <span class="fa fa-heart hear-btn"></span>
                                    </a>
                                </div>
                             </li>';
		}

		$count++;
	}
} else {
    $fav_product_list .= '<div class="mini-cart-items">
                                  <p>' . esc_html__('Do not have favourite products', 'adforest') . '</p>
                               </div>';
}
?>

<!-- adt-multivendor-top-search-bar-start -->
<section class="adt-multivendor-top-search-bar adt-cyber-sale-topbar <?php echo esc_attr($sticky_class); ?>"
         style="background-image: url(<?php echo esc_url($header_bg_image); ?>);">
    <div class="container adt-container">
        <div class="row">
            <div class="col-lg-12">
                <div class="adt-multivendor-searchbar-wrapper">
                    <div class="logo">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url($site_logo); ?>"
                                                                    alt="<?php echo esc_attr__('logo', 'adforest') ?>"></a>
                    </div>
                    <div class="adt-search-area">
                        <?php
                        $shop_url = wc_get_page_permalink('shop');
                        ?>
                        <form method="GET"
                              action="<?php echo esc_url($shop_url); ?>">
                            <input type="text" name="title"
                                   placeholder="<?php echo esc_attr__("What are you looking for...", "adforest"); ?>"
                                   value="<?php echo esc_attr($title); ?>">
                            <button type="button"><i
                                        class="fas fa-search"></i><span><?php echo esc_html__("Search", "adforest"); ?></span>
                            </button>
                        </form>
                    </div>
                    <div class="adt-extra-buttons">
                        <div class="cart-box">
                            <div class="icon">
                                <a href="<?php echo esc_url(wc_get_cart_url()); ?>"><i class="fas fa-shopping-cart"></i></a>
                                <span class="count"><?php
                                    echo esc_html(WC()->cart->get_cart_contents_count());
                                    ?></span>
                            </div>
                        </div>
                        <div class="wishlist-box">
                            <a href="javascript:void(0)" class="favourite" id="fav_product_btn">
                                <span><?php echo esc_html__("Wishlist", "adforest"); ?></span>
                                <i class="far fa-heart"></i>
                                <span class="favourite-count"><?php echo esc_html(count($products)); ?></span>
                            </a>
                            <div class="product-favourite-sb">
                                <ul class="product-section-content">
                                    <?php echo adforest_return_echo($fav_product_list); ?>
                                    <?php if(is_array($products) && count($products) > 5) {
                                        echo '<a href="' . get_the_permalink($sb_profile_page) . '" class="view-all-fav">' . esc_html__("View All", "adforest") . '</a>';
                                    } ?>
                                </ul>
                            </div>
                        </div>
                        <?php if (!is_user_logged_in()) { ?>
                            <a href="<?php echo esc_url(get_the_permalink($sb_sign_in_page), 'adforest') ?>"><i
                                        class="fas fa-sign-in-alt"></i><?php echo esc_html__("Sign in", "adforest"); ?></a>
                            <a href="<?php echo esc_url(get_the_permalink($sb_sign_up_page), 'adforest') ?>"><i
                                        class="fas fa-sign-in-alt"></i><?php echo esc_html__("Register", "adforest"); ?></a>
                        <?php } else { ?>
                            <div>
                                <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                   data-bs-placement="top" class="login-user">
                                    <?php
                                    $dp = '';
                                    $unread_msgs = ADFOREST_MESSAGE_COUNT;
                                    if (function_exists('adforest_get_user_dp')) {
                                        $dp = adforest_get_user_dp($user_id);
                                    }
                                    ?>
                                    <img class="img-circle" src="<?php echo esc_url($dp); ?>"
                                         alt="<?php echo esc_attr__('user profile picture', 'adforest'); ?>" width="32"
                                         height="32"></a>

                                <ul class="dropdown-user-login shop-dropdown-user">
                                    <li><a href="<?php echo get_the_permalink($sb_profile_page); ?>">
                                            <?php echo esc_html__("Profile", "adforest"); ?>
                                        </a>
                                    </li>
                                    <?php echo apply_filters('adforest_vendor_dashboard_profile', '', $user_id); ?>

                                    <li>
                                        <a href="<?php echo wp_logout_url(get_the_permalink($sb_sign_in_page)); ?>">
                                            <?php echo esc_html__("Logout", "adforest"); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- adt-multivendor-top-search-bar-end -->
