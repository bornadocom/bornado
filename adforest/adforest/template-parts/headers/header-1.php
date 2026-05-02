<?php
global $adforest_theme;
$site_logo = isset($adforest_theme['sb_site_logo']['url']) ? $adforest_theme['sb_site_logo']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.svg";
$sb_sign_in_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : "";
$sb_sign_up_page = isset($adforest_theme['sb_sign_up_page']) ? $adforest_theme['sb_sign_up_page'] : "";
$ad_in_menu_text = isset($adforest_theme['ad_in_menu_text']) ? $adforest_theme['ad_in_menu_text'] : "";
// Apply WPML translation using icl_t()
if (function_exists('icl_t')) {
    $ad_in_menu_text = icl_t('adforest_theme', 'ad_in_menu_text', $ad_in_menu_text);
}
$sb_post_ad_page = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : "";
$responsive_logo = isset($adforest_theme['sb_site_logo_mobile']['url']) ? $adforest_theme['sb_site_logo_mobile']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.svg";
$home_page_logo = isset($adforest_theme['sb_home_logo']['url']) ? $adforest_theme['sb_home_logo']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.svg";
$user_id = get_current_user_id();

$sb_profile_page = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';

$is_sticky_header = isset($adforest_theme['sb_sticky_header']) ? $adforest_theme['sb_sticky_header'] : '';
$sticky_class = "";
if ($is_sticky_header == '1') {
    $sticky_class = "sticky-header";
}
?>

<div class="sb-header header-shadow viewport-lg adt-header-secondary <?php echo esc_attr($sticky_class); ?>">
    <div class="container adt-container">
        <div class="sb-header-container">
            <div class="logo" data-mobile-logo="<?php echo esc_url($responsive_logo) ?>"
                 data-sticky-logo="<?php echo esc_url($responsive_logo) ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url($site_logo); ?>"
                                                                     alt="<?php echo esc_attr__('logo', 'adforest') ?>"></a>
            </div>
            <div class="burger-menu">
                <div class="line-menu line-half first-line"></div>
                <div class="line-menu"></div>
                <div class="line-menu line-half last-line"></div>
            </div>
            <nav class="sb-menu menu-caret submenu-top-border submenu-scale">
                <ul>
                    <?php get_template_part('template-parts/layouts/main', 'nav'); ?>
                    <li class="adt-list">
                        <?php
                        if (function_exists('adforest_get_header_user_menu_markup')) {
                            echo adforest_get_header_user_menu_markup(array(
                                'sign_in_page' => $sb_sign_in_page,
                                'sign_up_page' => $sb_sign_up_page,
                                'profile_page' => $sb_profile_page,
                                'user_id'      => $user_id,
                            ));
                        }
                        ?>
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

<?php
if ($is_sticky_header == '1') {
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('.ad-content-item').on('click', function(e) {
                e.preventDefault();

                var target = $($(this).attr('href'));
                var headerHeight = $('.sb-header.sticky-header').outerHeight() || 0;

                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - headerHeight - 20
                    }, 600);
                }
            });
        });
    </script>
    <?php
}
