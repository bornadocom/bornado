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
$sb_profile_page = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
?>

    <div id="transparent-header"
         class="sb-header header-shadow viewport-lg adt-transparent-header-1">
        <div class="container adt-container">
            <div class="sb-header-container">
                <div class="logo" data-mobile-logo="<?php echo esc_url($responsive_logo) ?>"
                     data-sticky-logo="<?php echo esc_url($responsive_logo) ?>">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <img src="<?php echo esc_url($site_logo); ?>"
                             alt="<?php echo esc_attr__('logo', 'adforest') ?>">
                    </a>
                </div>
                <div class="burger-menu">
                    <div class="line-menu line-half first-line"></div>
                    <div class="line-menu"></div>
                    <div class="line-menu line-half last-line"></div>
                </div>
                <nav class="sb-menu menu-caret submenu-top-border submenu-scale">
                    <ul>
                        <?php get_template_part('template-parts/layouts/main', 'nav'); ?>
                        <li class="adt-list d-flex justify-content-center align-items-center gap-2">
                            <?php if (isset($adforest_theme['ad_in_menu']) && $adforest_theme['ad_in_menu']) { ?>
                                <a href="<?php echo get_the_permalink($sb_post_ad_page); ?>"
                                   class="btn-theme-secondary ad-post-btn"><i
                                            class="fas fa-plus"></i><?php echo esc_html($ad_in_menu_text) ?></a>
                                <?php
                            } ?>
                            <?php if (!is_user_logged_in()) { ?>
                                <div>
                                    <a href="<?php echo esc_url(get_the_permalink($sb_sign_in_page), 'adforest') ?>"
                                       class="sign-in"><i
                                                class="fas fa-user"></i><?php echo esc_html__('Sign in', 'adforest'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(get_the_permalink($sb_sign_up_page), 'adforest') ?>"
                                       class="sign-up"><?php echo esc_html__("Register", "adforest") ?></a>
                                </div>
                            <?php } else { ?>
                                <div>
                                    <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                                       class="login-user">
                                        <?php
                                        $dp = '';
                                        $unread_msgs = ADFOREST_MESSAGE_COUNT;
                                        if (function_exists('adforest_get_user_dp')) {
                                            $dp = adforest_get_user_dp($user_id);
                                        }
                                        ?>
                                        <img class="img-circle" src="<?php echo esc_url($dp); ?>"
                                             alt="<?php __('user prfile picture', 'adforest'); ?>" width="32"
                                             height="32"></a>

                                    <ul class="dropdown-user-login">
                                        <li><a class="user_login_dropdown_text"
                                               href="<?php echo get_the_permalink($sb_profile_page); ?>"><i
                                                        class="fa fa-user"></i> <?php echo __("Profile", "adforest"); ?>
                                            </a>
                                        </li>
                                        <?php echo apply_filters('adforest_vendor_dashboard_profile', '', $user_id); ?>
                                        <?php
                                        if (isset($adforest_theme['communication_mode']) && ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'message')) {
                                            ?>
                                            <li><a class="user_login_dropdown_text  "
                                                   href="<?php echo adforest_set_url_param(trailingslashit(get_the_permalink($sb_profile_page)), 'page_type', 'msg'); ?>"><i
                                                            class="fa fa-envelope"></i> <?php echo __('Messages', 'adforest'); ?>
                                                    <span class="badge bg-danger"><?php echo esc_html(adforest_get_message_count()); ?></span></a>
                                            </li>
                                            <?php
                                        }
                                        if (isset($adforest_theme['sb_cart_in_menu']) && $adforest_theme['sb_cart_in_menu']) {
                                            global $woocommerce;
                                            ?>
                                            <li><a class="user_login_dropdown_text"
                                                   href="<?php echo wc_get_cart_url(); ?>"><i
                                                            class="fa fa-shopping-cart"></i> <?php echo __('Cart', 'adforest'); ?>
                                                    <span class="badge bg-danger"><?php echo adforest_return_echo($woocommerce->cart->cart_contents_count); ?></span></a>
                                            </li> <?php } ?>

                                        <li><a class="user_login_dropdown_text"
                                               href="<?php echo wp_logout_url(get_the_permalink($sb_sign_in_page)); ?>"><i
                                                        class="fa fa-power-off"></i> <?php echo __("Logout", "adforest"); ?>
                                            </a></li>
                                    </ul>

                                </div>

                            <?php } ?>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
<?php if ($is_sticky_header == '1') { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const header = document.getElementById('transparent-header');
            window.addEventListener('scroll', function () {
                if (window.scrollY > 50) {
                    header.classList.add('sticky-active');
                } else {
                    header.classList.remove('sticky-active');
                }
            });
        });
    </script>
<?php }