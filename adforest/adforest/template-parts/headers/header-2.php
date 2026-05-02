<?php
global $adforest_theme;
$site_logo = $adforest_theme['sb_site_logo']['url'] ?? ADFOREST_IMAGE_PATH . "/adt-logo.png";
$sb_sign_in_page = $adforest_theme['sb_sign_in_page'] ?? "";
$sb_sign_up_page = $adforest_theme['sb_sign_up_page'] ?? "";
$ad_in_menu_text = $adforest_theme['ad_in_menu_text'] ?? "";
// Apply WPML translation using icl_t()
if (function_exists('icl_t')) {
    $ad_in_menu_text = icl_t('adforest_theme', 'ad_in_menu_text', $ad_in_menu_text);
}
$sb_post_ad_page = $adforest_theme['sb_post_ad_page'] ?? "";
$responsive_logo = $adforest_theme['sb_site_logo_mobile']['url'] ?? ADFOREST_IMAGE_PATH . "/adt-logo.png";
$home_page_logo = $adforest_theme['sb_home_logo']['url'] ?? ADFOREST_IMAGE_PATH . "/adt-logo.png";
$user_id = get_current_user_id();

$sb_profile_page = $adforest_theme['sb_profile_page'] ?? '';
$ad_categories = [];
if (function_exists('adforest_get_ad_taxonomy_callback')) {
    $ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
    
    // Filter out categories with zero ads if the option is enabled
    if (isset($adforest_theme['search_popup_cat_disable']) && $adforest_theme['search_popup_cat_disable'] == true) {
        $ad_categories = array_filter($ad_categories, function($category) {
            $category_details = get_taxonomy_details($category);
            return isset($category_details['ad_count']) && $category_details['ad_count'] > 0;
        });
    }
}
$adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
global $wpdb;
$current_user_id = get_current_user_id();

$is_sticky_header = isset($adforest_theme['sb_sticky_header']) ? $adforest_theme['sb_sticky_header'] : '';
$sticky_class = "";
if ($is_sticky_header == '1') {
    $sticky_class = "sticky-header";
}

$location_selector_data   = adforest_get_location_selector_data();
$location_items           = $location_selector_data['items'] ?? [];
$selected_location_data   = $location_selector_data['selected'] ?? [];
$selected_location_name   = $selected_location_data['name'] ?? esc_html__('All Locations', 'adforest');
$selected_location_image  = $selected_location_data['image'] ?? adforest_get_location_default_image_url();
$selected_location_raw    = $location_selector_data['raw_value'] ?? 'all';
$location_selector_active = !empty($location_items);
$location_default_image   = adforest_get_location_default_image_url();

if (empty($selected_location_image)) {
    $selected_location_image = $location_default_image;
}
?>

<!-- adt-top-search-bar-start -->
<?php
if (isset($adforest_theme['sb_top_bar']) && $adforest_theme['sb_top_bar']) {
    ?>
    <section class="adt-top-search-bar">
        <div class="container adt-container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="adt-searchbar-wrapper">
                        <div class="adt-searchbar-meta">
                            <p class="adt-lists-count">
                            <?php
                                if (isset($adforest_theme['adforest_top_bar_text']) && $adforest_theme['adforest_top_bar_text']) {
                                    // Apply WPML translation using icl_t()
                                    $translated_text = $adforest_theme['adforest_top_bar_text'];
                                    if (function_exists('icl_t')) {
                                        $translated_text = icl_t('adforest_theme', 'adforest_top_bar_text', $translated_text);
                                    }
                                    echo wp_kses_post($translated_text);
                                } else {
                                    $ad_post_count = wp_count_posts('ad_post')->publish;
                                    echo wp_kses(
                                        sprintf(
                                            __('More Than <span>%s</span> Ads.', 'adforest'),
                                            number_format($ad_post_count)
                                        ),
                                        array(
                                            'span' => array()
                                        )
                                    );
                                }
                                ?>
                            </p>
                            <?php if ($location_selector_active) : ?>
                                <div class="adt-location-selector" data-location-selector data-selected="<?php echo esc_attr($selected_location_raw); ?>">
                                    <button type="button" class="adt-location-toggle" aria-haspopup="true" aria-expanded="false">
                                        <span class="adt-location-flag">
                                            <img src="<?php echo esc_url($selected_location_image); ?>" alt="<?php echo esc_attr($selected_location_name); ?>">
                                        </span>
                                        <span class="adt-location-text">
                                            <span class="adt-location-label"><?php echo esc_html__('Location', 'adforest'); ?></span>
                                            <span class="adt-location-value"><?php echo esc_html($selected_location_name); ?></span>
                                        </span>
                                        <span class="adt-location-caret" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                                    </button>
                                    <div class="adt-location-dropdown" role="menu" aria-hidden="true">
                                        <div class="adt-location-search">
                                            <i class="fas fa-search" aria-hidden="true"></i>
                                            <input type="search"
                                                   class="adt-location-search-field"
                                                   placeholder="<?php echo esc_attr__('Search locations...', 'adforest'); ?>"
                                                   aria-label="<?php echo esc_attr__('Filter locations', 'adforest'); ?>">
                                        </div>
                                        <ul class="adt-location-options" role="listbox">
                                            <?php foreach ($location_items as $item) :
                                                $is_active  = !empty($item['active']);
                                                $is_all     = !empty($item['is_all']);
                                                $item_name  = is_string($item['name'] ?? '') ? $item['name'] : '';
                                                $item_image = !empty($item['image']) ? $item['image'] : $location_default_image;
                                                $count      = isset($item['count']) ? (int) $item['count'] : 0;

                                                $count_text = $is_all
                                                    ? __('Show ads from every location', 'adforest')
                                                    : sprintf(_n('%s ad available', '%s ads available', $count, 'adforest'), number_format_i18n($count));
                                                ?>
                                                <li class="<?php echo $is_active ? 'is-active' : ''; ?>">
                                                    <button type="button"
                                                            class="adt-location-option"
                                                            data-loc-id="<?php echo esc_attr($item['id']); ?>"
                                                            data-loc-name="<?php echo esc_attr($item_name); ?>"
                                                            data-loc-image="<?php echo esc_url($item_image); ?>"
                                                            data-is-all="<?php echo $is_all ? '1' : '0'; ?>"
                                                            role="option"
                                                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                                                        <span class="adt-location-option-flag">
                                                            <img src="<?php echo esc_url($item_image); ?>" alt="<?php echo esc_attr($item_name); ?>">
                                                        </span>
                                                        <span class="adt-location-option-meta">
                                                            <span class="adt-location-option-name"><?php echo esc_html($item_name); ?></span>
                                                            <span class="adt-location-option-count"><?php echo esc_html($count_text); ?></span>
                                                        </span>
                                                        <span class="adt-location-option-check" aria-hidden="true"><i class="fas fa-check"></i></span>
                                                    </button>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <p class="adt-location-hint"><?php echo esc_html__('Selecting a location filters ads across the site.', 'adforest'); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="adt-search-area">
                            <form method="GET"
                                  action="<?php echo esc_url(get_the_permalink($adforest_search_page)); ?>">
                                <input type="text" name="ad_title"
                                       placeholder="<?php echo esc_attr__("What are you looking for...", 'adforest'); ?>">
                                <select class="default-select post-type-change" name="cat_id">
                                    <option value=""><?php echo esc_html__("Select Category", "adforest"); ?></option>
                                    <?php if (!empty($ad_categories) && !is_wp_error($ad_categories)): ?>
                                        <?php foreach ($ad_categories as $category): ?>
                                            <option value="<?php echo esc_attr($category->term_id); ?>">
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="submit"><i
                                            class="fas fa-search"></i><?php echo esc_html__("Search", "adforest"); ?></button>
                            </form>
                            <div class="adt-extra-buttons">
                                <?php if (!is_user_logged_in()) { ?>
                                    <div>
                                        <a href="<?php echo esc_url(get_the_permalink($sb_sign_in_page), 'adforest') ?>"
                                           class="sign-in"><i
                                                    class="fas fa-user"></i><?php echo esc_html__("Sign in", "adforest"); ?></a>
                                        <a href="<?php echo esc_url(get_the_permalink($sb_sign_up_page), 'adforest') ?>"
                                           class="sign-up"><?php echo esc_html__("Register", "adforest"); ?></a>
                                    </div>
                                <?php } else { ?>
                                    <div>
                                        <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                           data-bs-placement="top" class="login-user">
                                            <?php
                                            $dp = '';
//                                            $unread_msgs = ADFOREST_MESSAGE_COUNT;
                                            if (function_exists('adforest_get_user_dp')) {
                                                $dp = adforest_get_user_dp($user_id);
                                            }
                                            ?>
                                            <img class="img-circle" src="<?php echo esc_url($dp); ?>"
                                                 alt="<?php esc_html__('user profile picture', 'adforest'); ?>" width="32"
                                                 height="32"></a>

                                        <ul class="dropdown-user-login">
                                            <li><a href="<?php echo get_the_permalink($sb_profile_page); ?>"><i
                                                            class="fa fa-user"></i> <?php echo esc_html__("Profile", "adforest"); ?>
                                                </a></li>
                                            <?php echo apply_filters('adforest_vendor_dashboard_profile', '', $user_id); ?>
                                            <?php
                                            if (isset($adforest_theme['communication_mode']) && ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'message')) {
                                                ?>
                                                <li>
                                                    <a href="<?php echo adforest_set_url_param(trailingslashit(get_the_permalink($sb_profile_page)), 'page_type', 'msg'); ?>"><i
                                                                class="fa fa-envelope"></i> <?php echo esc_html__('Messages', 'adforest'); ?>
                                                        <span class="badge bg-danger"><?php echo esc_html(adforest_get_message_count()); ?></span></a>
                                                </li>
                                                <?php
                                            }
                                            if (isset($adforest_theme['sb_cart_in_menu']) && $adforest_theme['sb_cart_in_menu']) {
                                                global $woocommerce;
                                                ?>
                                                <li><a href="<?php echo wc_get_cart_url(); ?>"><i
                                                                class="fa fa-shopping-cart"></i> <?php echo esc_html__('Cart', 'adforest'); ?>
                                                        <span class="badge bg-danger"><?php echo adforest_return_echo($woocommerce->cart->cart_contents_count); ?></span></a>
                                                </li> <?php } ?>
                                            <li>
                                                <a href="<?php echo wp_logout_url(get_the_permalink($sb_sign_in_page)); ?>"><i
                                                            class="fa fa-power-off"></i> <?php echo esc_html__("Logout", "adforest"); ?>
                                                </a></li>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php } ?>
<!-- adt-top-search-bar-end -->

<!-- adt-header-primary-start -->
<div class="sb-header header-shadow viewport-lg adt-header-primary <?php echo esc_attr($sticky_class); ?>">
    <div class="container adt-container">
        <!-- sb header -->
        <div class="sb-header-container">
            <!--Logo-->
            <div class="logo" data-mobile-logo="<?php echo esc_url($responsive_logo) ?>"
                 data-sticky-logo="<?php echo esc_url($responsive_logo) ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url($site_logo); ?>"
                                                            alt="<?php echo esc_attr__('logo', 'adforest') ?>"></a>
            </div>
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
                    <li class="adt-list">
                        <?php if ( isset($adforest_theme['ad_in_menu']) && $adforest_theme['ad_in_menu'] ) { ?>
                            <a href="<?php echo get_the_permalink($sb_post_ad_page); ?>"
                               class="btn-theme-secondary ad-post-btn"><i
                                        class="fas fa-plus"></i><?php echo esc_html($ad_in_menu_text) ?></a>
                            <?php
                        } ?>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>
<!-- adt-header-primary-end -->
