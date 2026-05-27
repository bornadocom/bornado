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
                            <?php
                            /* Delegate to the shared user-menu helper so the
                               Theme Options → "Modern User Menu" toggle
                               (`sb_header_user_menu_style`) is respected here
                               too. Previously this header rendered a hard-coded
                               sign-in/avatar block that ignored the option, so
                               enabling the modern menu in Theme Options had no
                               effect when "Header Transparent" was selected.
                               The helper returns:
                                 - logged-out → Sign in / Register links
                                 - logged-in, classic mode → legacy avatar +
                                   classic dropdown (matches the markup this
                                   file used to emit inline)
                                 - logged-in, modern mode → avatar + Listivo-
                                   style dropdown (Add Listing / Awaiting
                                   Approval / Invoices / Messages / My Listings /
                                   Favorites / My Packages / Profile Settings /
                                   Log Out), with its own inline CSS. */
                            if (function_exists('adforest_get_header_user_menu_markup')) {
                                echo adforest_get_header_user_menu_markup(array(
                                    'sign_in_page' => $sb_sign_in_page,
                                    'sign_up_page' => $sb_sign_up_page,
                                    'profile_page' => $sb_profile_page,
                                    'user_id'      => $user_id,
                                ));
                            }
                            ?>
                            <?php if (isset($adforest_theme['ad_in_menu']) && $adforest_theme['ad_in_menu']) { ?>
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