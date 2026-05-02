<?php
global $adforest_theme, $template;

$page_template = basename($template);
if ($page_template == 'page-theme-dashboard.php') {
    wp_footer();
    return;
}

$shop_request = function_exists('adforestAPI_getSpecific_headerVal') ? adforestAPI_getSpecific_headerVal('Adforest-Shop-Request') : '';
if ($shop_request === 'body') {
    wp_footer();
    ?>
    </body>
    </html>
    <?php
    return;
}

$footer_style = $adforest_theme['footer_style'] ?? "default";
$get_footer_style = isset($_GET['footer_style']) ? $_GET['footer_style'] : ""; 
$footer_style_sanitized = sanitize_text_field($get_footer_style);

if (isset($footer_style_sanitized) && $footer_style_sanitized != "") {

    $footer_style = preg_replace('/[^0-9a-zA-Z_-]/', '', $footer_style_sanitized);
}
$page_template = basename($template);

if (!function_exists('adforest_footer_content_html')) {
    function adforest_footer_content_html()
    {
        global $adforest_theme;
        $footer_style = $adforest_theme['footer_style'] ?? "default";
        $get_footer_style = isset($_GET['footer_style']) ? $_GET['footer_style'] : ""; 
        $footer_style_sanitized = sanitize_text_field($get_footer_style);
        if (isset($footer_style_sanitized) && $footer_style_sanitized != "") {
            $footer_style = preg_replace('/[^0-9a-zA-Z_-]/', '', $footer_style_sanitized);
        }
        if($footer_style == 'default') {
            $footer_style = isset($adforest_theme['footer_style']) ? $adforest_theme['footer_style'] : '1';
        }
        get_template_part('template-parts/footers/footer', $footer_style);
    }

}
if ($footer_style == "13" && in_array('elementor-pro/elementor-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    elementor_theme_do_location('footer');
} else {
    add_action('adforest_action_footer_content', 'adforest_footer_content_html');
    do_action('adforest_action_footer_content', 'adforest_footer_content_html');
}


wp_footer();

?>
</body>

</html>