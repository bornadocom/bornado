<?php
global $adforest_theme;

$section4_title = $adforest_theme['section_4_title'] ?? esc_html__('Our Info', 'adforest');
$social_section_heading = $adforest_theme['footer_social_heading'] ?? esc_html__('Follow Us', 'adforest');
$footer_social_icons = $adforest_theme['footer_social_icons'] ?? "";
$footer_description = $adforest_theme['footer_description'] ?? "";
$footer_contact_details = $adforest_theme['footer_contact_details'] ?? "";
$section_1_title = $adforest_theme['section_1_title'] ?? "";
$adforest_footer_pages = $adforest_theme['sb_footer_pages'] ?? "";
$newsletter = $adforest_theme['section_3_mc'] ?? "";
$newsletter_title = $adforest_theme['mc_title'] ?? "";
$newsletter_description = $adforest_theme['mc_description'] ?? "";

// Apply WPML translations
if (function_exists('icl_t')) {
    $footer_description = icl_t('adforest_theme', 'footer_description', $footer_description);
    $social_section_heading = icl_t('adforest_theme', 'footer_social_heading', $social_section_heading);
    $section_1_title = icl_t('adforest_theme', 'section_1_title', $section_1_title);
    $newsletter_title = icl_t('adforest_theme', 'mc_title', $newsletter_title);
    $newsletter_description = icl_t('adforest_theme', 'mc_description', $newsletter_description);
}

$adt_container_class = "";
if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
    $adt_container_class = "adt-container";
}

$lg_cols = 3;
if (isset($newsletter) && $newsletter != '1') {
    $lg_cols = 4;
}

$page_id = get_queried_object_id();

$page_header_style = get_post_meta($page_id, '_page_header_style', true);

if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2" || $page_header_style == "transparent")) {
    $adt_container_class = "adt-container";
}
?>

<!-- adt-footer-section-start -->
<section class="adt-footer-section adt-light-footer">
    <div class="container <?php echo esc_attr($adt_container_class); ?>">
        <div id="sb_loading" style="display:none;">Loading...</div>
        <div class="row">
            <div class="col-md-6 col-xl-<?php echo esc_attr($lg_cols); ?>">
                <div class="adt-about-detail-box">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <?php if (isset($adforest_theme['footer_logo']['url']) && $adforest_theme['footer_logo']['url'] != "") { ?>
                            <img src="<?php echo esc_url($adforest_theme['footer_logo']['url']); ?>"
                                 class="img-fluid"
                                 alt="<?php echo esc_attr__('Site Logo', 'adforest'); ?>">
                        <?php } else { ?>
                            <img
                            src="<?php echo esc_url(trailingslashit(get_template_directory_uri())) . 'images/logo.png' ?>"
                            class="img-fluid" alt="<?php echo esc_attr__('Site Logo', 'adforest'); ?>" /><?php } ?></a>
                    <p><?php echo esc_html($footer_description); ?></p>
                    <?php
                    if (is_array($footer_social_icons) && count($footer_social_icons) > 0) {
                        $non_empty_icons = array_filter($footer_social_icons);

                        if (!empty($non_empty_icons)) { ?>
                            <h5><?php echo esc_html($social_section_heading); ?></h5>
                            <ul class="social-links">
                                <?php foreach ($non_empty_icons as $key => $values) { ?>
                                    <li>
                                        <a href="<?php echo esc_url($values, "adforest"); ?>">
                                            <i class="fab fa-<?php echo strtolower($key); ?>"></i>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php }
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-6 col-xl-<?php echo esc_attr($lg_cols); ?>">
                <div class="adt-contact-box">
                    <h4><?php 
                    $contact_title = $adforest_theme['adforest_footer_contact_title'] ?? __('Contact Us', 'adforest');
                    if (function_exists('icl_t')) {
                        $contact_title = icl_t('adforest_theme', 'adforest_footer_contact_title', $contact_title);
                    }
                    echo esc_html($contact_title); 
                    ?></h4>
                    <ul class="adt-contact-list">
                        <?php
                        if (!empty($footer_contact_details['title']) && is_array($footer_contact_details['title'])) {
                            foreach ($footer_contact_details['title'] as $index => $title) {
                                $content = isset($footer_contact_details['content'][$index]) ? $footer_contact_details['content'][$index] : '';
                                $icon = isset($footer_contact_details['icon'][$index]) ? $footer_contact_details['icon'][$index] : 'fas fa-info-circle';
                                ?>
                                <li>
                                    <div class="icon-box">
                                        <i class="<?php echo esc_attr($icon); ?>"></i>
                                    </div>
                                    <div class="meta-box">
                                        <small><?php echo esc_html($title); ?></small>
                                        <span><?php echo esc_html($content); ?></span>
                                    </div>
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 col-xl-<?php echo esc_attr($lg_cols); ?>">
                <div class="adt-quick-links">
                    <h4><?php echo esc_html($section_1_title, "adforest"); ?></h4>
                    <ul>
                        <?php foreach ($adforest_footer_pages as $footer_page) {
                            $footer_page = apply_filters('adforest_language_page_id', $footer_page); ?>
                            <li><a
                                        href="<?php echo esc_url(get_the_permalink($footer_page)); ?>"><?php echo esc_html(get_the_title($footer_page)); ?></a>
                            </li>
                            <?php
                        } ?>
                    </ul>
                </div>
            </div>
            <?php if (isset($newsletter) && $newsletter == '1') { ?>
                <div class="col-md-6 col-xl-<?php echo esc_attr($lg_cols); ?>">
                    <div class="adt-newsletter-box">
                        <h4><?php echo esc_html($newsletter_title) ?></h4>
                        <p><?php echo esc_html($newsletter_description) ?></p>
                        <form id="save_email_footer_form">
                            <div class="input-group">
                                <input type="email" name="footer_email" id="footer_email" class="form-control"
                                       placeholder="Your Email">
                                <button id="save_email_footer_btn" class="btn send-btn" type="submit">
                                    <i id="btn-primary-icon" class="fas fa-paper-plane"></i>
                                    <i id='btn-spinner-icon' class="fa fa-circle-o-notch fa-spin"
                                       style="display: none; color: #fff;"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
<!-- adt-footer-section-end -->

<!-- adt-copyright-box-start -->
<div class="adt-copyright-box">
    <?php
    if (isset($adforest_theme['sb_footer']) && $adforest_theme['sb_footer'] != "") {
        echo wp_kses($adforest_theme['sb_footer'], adforest_required_tags());
    } else {
        echo wp_kses("Copyright 2021 &copy; Theme Created By <a href='https://themeforest.net/user/scriptsbundle/portfolio'>ScriptsBundle</a> All Rights Reserved.", adforest_required_tags());
    }
    ?>
</div>
<!-- adt-copyright-box-end -->
