<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <?php global $adforest_theme; ?>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11"/>
    <?php
    if (isset($adforest_theme['header_js_and_css']) && $adforest_theme['header_js_and_css'] != "") {
        echo adforest_return_echo($adforest_theme['header_js_and_css']);
    }
    ?>
    <style id="adforest-custom-css"></style>
    <?php wp_head();
    $custom_switcher = isset($adforest_theme['custom_theme_color_switch']) && $adforest_theme['custom_theme_color_switch'] ? $adforest_theme['custom_theme_color_switch'] : false;
    $cus_switch_class = '';
    if ($custom_switcher) {
        $cus_switch_class = 'custom-switcher';
    }
    ?>
</head>

<body <?php body_class(); ?>>
<?php
if (function_exists('wp_body_open')) {
    wp_body_open();
}
?>
<?php do_action('adforest_language_switcher'); ?>

<?php
if (class_exists('Redux')) {
    if (($adforest_theme['sb_pre_loader'] && isset($adforest_theme['loader_img_switch'])) && $adforest_theme['loader_img_switch']) {
        $loader_text = (isset($adforest_theme['loader_text']) && $adforest_theme['loader_text'] != "") ? $adforest_theme['loader_text'] : '';
        /* Profile Pic  */
        $preldr_link[0] = get_template_directory_uri() . '/images/loader.gif';
        if (isset($adforest_theme['loader_img']['url']) && $adforest_theme['loader_img']['url'] != "") {
            $preldr_link = array($adforest_theme['loader_img']['url']);
        }
        ?>
        <div id="spinner">
            <div class="spinner-img"><img alt="<?php echo esc_attr__('Preloader', 'adforest'); ?>"
                                          src="<?php echo esc_url($preldr_link[0]); ?>"/>
                <h2><?php echo '' . ($loader_text); ?></h2>
            </div>
        </div>
        <?php
    } else if (isset($adforest_theme['sb_pre_loader']) && $adforest_theme['sb_pre_loader'] && !$adforest_theme['loader_img_switch']) { ?>
        <div id="loader-wrapper">
            <div id="loader"></div>
            <div class="loader-section section-left"></div>
            <div class="loader-section section-right"></div>
        </div>
        <?php
    }
} ?>


<?php
if (isset($adforest_theme['adforest_coming_soon_mode']) && $adforest_theme['adforest_coming_soon_mode']) {
    if (!current_user_can('administrator') && !is_admin()) {
        get_template_part('template-parts/layouts/coming', 'soon');
        exit;
    }
}
?>

<script>
    window.addEventListener('load', function () {
        let spinner = document.getElementById('spinner');

        if (spinner) {
            spinner.style.display = 'none';
        }
    });
</script>
