<?php
global $adforest_theme;
$footer_logo = isset($adforest_theme['footer_logo']['url']) ? $adforest_theme['footer_logo']['url'] : ADFOREST_IMAGE_PATH . "/footer-logo.png";
$footer_img_1 = isset($adforest_theme['footer_img_1']['url']) ? $adforest_theme['footer_img_1']['url'] : ADFOREST_IMAGE_PATH . "/footer-logo.png";
$footer_img_2 = isset($adforest_theme['footer_img_2']['url']) ? $adforest_theme['footer_img_2']['url'] : ADFOREST_IMAGE_PATH . "/footer-logo.png";
$footer_desc = isset($adforest_theme['footer_description']) ? $adforest_theme['footer_description'] : "";
$social_icons = isset($adforest_theme['footer_social_icons']) ? $adforest_theme['footer_social_icons'] : array();
$social_section_heading = $adforest_theme['footer_social_heading'] ?? esc_html__('Follow Us', 'adforest');
$footer_social_icons = $adforest_theme['footer_social_icons'] ?? "";

$footer3_menu_title_1 = isset($adforest_theme['footer3_menu_title_1']) ? $adforest_theme['footer3_menu_title_1'] : '';
$footer3_menu_title_2 = isset($adforest_theme['footer3_menu_title_2']) ? $adforest_theme['footer3_menu_title_2'] : '';
$footer3_menu_title_3 = isset($adforest_theme['footer3_menu_title_3']) ? $adforest_theme['footer3_menu_title_3'] : '';
$footer3_menu_title_4 = isset($adforest_theme['footer3_menu_title_4']) ? $adforest_theme['footer3_menu_title_4'] : '';

$footer3_title_1 = isset($adforest_theme['footer3_title_1']) ? $adforest_theme['footer3_title_1'] : "";
$footer3_title_2 = isset($adforest_theme['footer3_title_2']) ? $adforest_theme['footer3_title_2'] : "";
$footer3_title_3 = isset($adforest_theme['footer3_title_3']) ? $adforest_theme['footer3_title_3'] : "";
$footer3_title_4 = isset($adforest_theme['footer3_title_4']) ? $adforest_theme['footer3_title_4'] : "";

$footer3_desc_1 = isset($adforest_theme['footer3_desc_1']) ? $adforest_theme['footer3_desc_1'] : "";
$footer3_desc_2 = isset($adforest_theme['footer3_desc_2']) ? $adforest_theme['footer3_desc_2'] : "";

$footer_contact_details = isset($adforest_theme['footer-contact-details']) ? $adforest_theme['footer-contact-details'] : array();
$footer_style = isset($adforest_theme['footer_style']) ? $adforest_theme['footer_style'] : 3;
$footer_description = $adforest_theme['footer_description'] ?? "";

// Apply WPML translations
if (function_exists('icl_t')) {
    $footer_description = icl_t('adforest_theme', 'footer_description', $footer_description);
    $social_section_heading = icl_t('adforest_theme', 'footer_social_heading', $social_section_heading);
    $footer3_menu_title_1 = icl_t('adforest_theme', 'footer3_menu_title_1', $footer3_menu_title_1);
    $footer3_menu_title_2 = icl_t('adforest_theme', 'footer3_menu_title_2', $footer3_menu_title_2);
    $footer3_menu_title_3 = icl_t('adforest_theme', 'footer3_menu_title_3', $footer3_menu_title_3);
    $footer3_menu_title_4 = icl_t('adforest_theme', 'footer3_menu_title_4', $footer3_menu_title_4);
    $footer3_title_1 = icl_t('adforest_theme', 'footer3_title_1', $footer3_title_1);
    $footer3_title_2 = icl_t('adforest_theme', 'footer3_title_2', $footer3_title_2);
    $footer3_title_3 = icl_t('adforest_theme', 'footer3_title_3', $footer3_title_3);
    $footer3_title_4 = icl_t('adforest_theme', 'footer3_title_4', $footer3_title_4);
    $footer3_desc_1 = icl_t('adforest_theme', 'footer3_desc_1', $footer3_desc_1);
    $footer3_desc_2 = icl_t('adforest_theme', 'footer3_desc_2', $footer3_desc_2);
}

$adt_container_class = "adt-container";
if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
    $adt_container_class = "adt-container";
}
$page_id = get_queried_object_id();

$page_header_style = get_post_meta($page_id, '_page_header_style', true);

if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2")) {
    $adt_container_class = "adt-container";
}
?>

<!-- adt-cybersale-footer-start -->
<section class="adt-cybersale-footer">
    <div class="container <?php echo esc_attr($adt_container_class); ?>">
        <div class="row">
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-3">
                <h4><?php echo esc_html($footer3_menu_title_1); ?></h4>
                <ul>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer_1'
                    ));
                    ?>
                </ul>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <h4><?php echo esc_html($footer3_menu_title_2); ?></h4>
                <ul>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer_2'
                    ));
                    ?>
                </ul>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <h4><?php echo esc_html($footer3_menu_title_3); ?></h4>
                <ul>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer_3'
                    ));
                    ?>
                </ul>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <h4><?php echo esc_html($footer3_menu_title_4); ?></h4>
                <ul>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer_4'
                    ));
                    ?>
                </ul>
            </div>
            <div class="col-md-8 col-lg-12 col-xl-3">
                <div class="footer-logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><img
                                src="<?php echo esc_url($adforest_theme['footer_logo']['url']); ?>" alt="<?php echo esc_attr__('logo', 'adforest'); ?>"></a>
                </div>
                <small><?php echo esc_html($footer_description); ?></small>
                <h5><?php echo esc_html($social_section_heading); ?></h5>
                <ul class="social-links">
                    <?php
                    foreach ($footer_social_icons as $key => $values) {
                        if (!empty($values)) {
                            ?>
                            <li>
                                <a href="<?php echo esc_url($values, "adforest"); ?>">
                                    <i class="fab fa-<?php echo strtolower($key) ?>"></i>
                                </a>
                            </li>
                        <?php }
                    } ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<!-- adt-cybersale-footer-end -->

<!-- adt-cybersale-footer-start -->
<section class="adt-cybersale-footer">
    <div class="container <?php echo esc_attr($adt_container_class); ?>">
        <div class="row">
            <div class="col-lg-5">
                <?php if (!empty($footer3_title_1) && !empty($footer_img_1)) : ?>
                    <h4><?php echo esc_html($footer3_title_1); ?></h4>
                    <img class="img-fluid" src="<?php echo esc_url($footer_img_1); ?>" alt="<?php echo esc_attr__('img', 'adforest'); ?>">
                <?php endif; ?>

                <?php if (!empty($footer3_title_2) && !empty($footer_img_2)) : ?>
                    <h4><?php echo esc_html($footer3_title_2); ?></h4>
                    <img class="img-fluid" src="<?php echo esc_url($footer_img_2); ?>" alt="<?php echo esc_attr__('img', 'adforest'); ?>">
                <?php endif; ?>
            </div>
            <div class="col-lg-7">
                <h4><?php echo esc_html($footer3_title_3); ?></h4>
                <p><?php echo esc_html($footer3_desc_1); ?></p>
                <h4><?php echo esc_html($footer3_title_4); ?></h4>
                <p><?php echo esc_html($footer3_desc_2); ?></p>
            </div>
        </div>
    </div>
</section>
<!-- adt-cybersale-footer-end -->

<div class="adt-copyright-box">
    <?php
    if (isset($adforest_theme['sb_footer']) && $adforest_theme['sb_footer'] != "") {
        echo wp_kses($adforest_theme['sb_footer'], adforest_required_tags());
    } else {
        echo wp_kses(__("Copyright 2021 &copy; Theme Created By <a href='https://themeforest.net/user/scriptsbundle/portfolio'>ScriptsBundle</a> All Rights Reserved.", 'adforest'), adforest_required_tags());
    }
    ?>
</div>
