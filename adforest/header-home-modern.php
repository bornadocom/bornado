<?php
/**
 * Standalone header loader for the AdForest - Home (Modern) page template.
 *
 * Loaded via get_header('home-modern'). Renders the page's
 * <!DOCTYPE> / <head> / <body> wrapper and then delegates the
 * actual header bar markup to the shared template part at
 * `template-parts/headers/header-home-modern.php` — the same part
 * `header.php` loads when admin picks the "Header Modern" option
 * in Theme Options → Header Style.
 *
 * @package Adforest
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class('adf-home-modern'); ?>>
<?php if (function_exists('wp_body_open')) { wp_body_open(); } ?>

<?php get_template_part('template-parts/headers/header', 'home-modern'); ?>
