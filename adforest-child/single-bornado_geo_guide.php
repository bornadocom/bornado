<?php
if (!defined('ABSPATH')) {
    exit;
}

$template = get_stylesheet_directory() . '/page-geo-guide.php';
if (is_readable($template)) {
    include $template;
    return;
}

get_header();
get_footer();
