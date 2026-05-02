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
$selected_header_cats = isset($adforest_theme['adforest_shop_header_cats_selection']) ? $adforest_theme['adforest_shop_header_cats_selection'] : "";
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
	$max_iterations = 4;

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
$shop_url = wc_get_page_permalink( 'shop' );
?>

    <!-- adt-multivendor-top-search-bar-start -->
    <section class="adt-multivendor-top-search-bar">
        <div class="adt-container container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-multivendor-searchbar-wrapper">
                        <div class="logo">
                            <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url($site_logo); ?>"
                                                                        alt="<?php echo esc_attr__('logo', 'adforest') ?>"></a>
                        </div>
                        <div class="adt-search-area">
                            <form method="GET"
                                  action="<?php echo esc_url($shop_url); ?>">
                                <input type="text" name="title"
                                       placeholder="<?php echo esc_attr__("What are you looking for...", "adforest"); ?>">
                                <select name="category_id" class="default-select post-type-change">
                                    <option value=""><?php echo __("Select Category", "adforest"); ?></option>
                                    <?php
                                    if (!empty($product_categories) && !is_wp_error($product_categories)) {
                                        foreach ($product_categories as $category) {
                                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <button type="submit"><i
                                            class="fas fa-search"></i><span><?php echo __("Search", "adforest"); ?></span>
                                </button>
                            </form>
                        </div>
                        <div class="adt-extra-buttons">
                            <div class="cart-box">
                                <div class="meta">
                                    <small><?php echo __("Shopping Cart:", "adforest"); ?></small>
                                    <strong>
                                        <?php
                                        echo wp_kses_post(WC()->cart->get_cart_total());
                                        ?>
                                    </strong>
                                </div>
                                <div class="icon">
                                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>"><i
                                                class="fas fa-shopping-cart"></i></a>
                                    <span class="count">
                                    <?php
                                    echo WC()->cart->get_cart_contents_count();
                                    ?>
                                </span>
                                </div>
                            </div>

                            <a href="javascript:void(0)" class="favourite" id="fav_product_btn"><i class="far fa-heart"></i><span class="favourite-count"><?php echo esc_html(count($products)); ?></span></a>
                            <div class="product-favourite-sb">
                                <ul class="product-section-content">
                                    <?php echo adforest_return_echo($fav_product_list); ?>
                                    <?php if(is_array($products) && count($products) > 5) {
                                        echo "<li style='display: flex; justify-content: center;'>";
	                                    echo '<a href="' . get_the_permalink($sb_profile_page) . '" class="no-margin-my-custom adt-button-dark">' . __("View All", "adforest") . '</a>';
	                                    echo "</li>";
                                    } ?>
                                </ul>
                            </div>
                            <?php if (!is_user_logged_in()) { ?>
                                <a href="<?php echo esc_url(get_the_permalink($sb_sign_in_page), 'adforest') ?>"><i
                                            class="fas fa-sign-in-alt"></i><?php echo __("Sign in", "adforest"); ?></a>
                                <a href="<?php echo esc_url(get_the_permalink($sb_sign_up_page), 'adforest') ?>"><i
                                            class="fas fa-sign-in-alt"></i><?php echo __("Register", "adforest"); ?></a>
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
                                             alt="<?php __('user profile picture', 'adforest'); ?>" width="32"
                                             height="32"></a>

                                    <ul class="dropdown-user-login shop-dropdown-user">
                                        <li><a href="<?php echo get_the_permalink($sb_profile_page); ?>">
                                                <?php echo __("Profile", "adforest"); ?>
                                            </a>
                                        </li>
                                        <?php echo apply_filters('adforest_vendor_dashboard_profile', '', $user_id); ?>

                                        <li>
                                            <a href="<?php echo wp_logout_url(get_the_permalink($sb_sign_in_page)); ?>">
                                                <?php echo __("Logout", "adforest"); ?>
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

    <!-- adt-multivendor-header-start -->
    <div class="adt-multivendor-header <?php echo esc_attr($sticky_class); ?>">
        <div class="adt-container container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="content-wrapper">
                        <div class="dropdown category-dropdown">
                            <form id="shop-categories-select" method="get" action="<?php echo esc_url($shop_url); ?>">
                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    <?php echo __("Categories", "adforest"); ?>
                                </button>
                                <ul class="dropdown-menu categories-list">
                                    <?php
                                    if (!empty($selected_header_cats) && !is_wp_error($selected_header_cats)) {
                                        foreach ($selected_header_cats as $category) {
                                            $cat_details = adforest_get_woocommerce_category_details($category);
                                            $icon = '<i class="fas fa-box-open"></i>';

                                            if (strpos(strtolower($cat_details['name']), 'electronics') !== false) {
                                                $icon = '<i class="fas fa-atom"></i>';
                                            } elseif (strpos(strtolower($cat_details['name']), 'fashion') !== false) {
                                                $icon = '<i class="fas fa-tshirt"></i>';
                                            }

                                            // Store category ID as data attribute for easier access
                                            echo '<li><a class="category-link" href="#" data-category-id="' . esc_attr($cat_details['id']) . '">' . $icon . esc_html($cat_details['name']) . '</a></li>';
                                        }
                                    }
                                    ?>
                                </ul>
                                <?php foreach ($current_query as $key => $value): ?>
                                    <?php if ($key != 'min_price' && $key != 'max_price'): ?>
                                        <input type="hidden" name="<?php echo esc_attr($key); ?>"
                                               value="<?php echo esc_attr($value); ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </form>
                        </div>
                        <!--Navigation menu-->
                        <div class="sb-header">
                            <!-- Burger menu -->
                            <div class="burger-menu">
                                <div class="line-menu line-half first-line"></div>
                                <div class="line-menu"></div>
                                <div class="line-menu line-half last-line"></div>
                            </div>
                            <!--Navigation menu-->
                            <nav class="sb-menu menu-caret submenu-top-border submenu-scale">
                                <ul>
                                    <?php get_template_part('template-parts/layouts/main', 'nav'); ?>
                                </ul>
                            </nav>
                        </div>
                        <div class="hotline-box">
                            <div class="icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="meta">
                                <small><?php echo !empty($adforest_theme['menu_contact_sub_text']) ? $adforest_theme['menu_contact_sub_text'] : "" ?></small>
                                <a href="tel:<?php echo isset($adforest_theme['menu_contact_number']) ? $adforest_theme['menu_contact_number'] : "" ?>"><?php echo isset($adforest_theme['menu_contact_number']) ? $adforest_theme['menu_contact_number'] : "" ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if (!is_user_logged_in()) : ?>
    <style>
        .adt-multivendor-searchbar-wrapper .adt-extra-buttons a::before {
            content: "";
            position: absolute;
            top: 5px;
            right: -15px;
            width: 1px;
            height: 12px;
            background-color: #000;
        }
    </style>
<?php endif; ?>
