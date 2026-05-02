<?php
/* Template Name: Seller Search */
/**
 * The template for displaying Pages.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Adforest
 */

global $adforest_theme;
get_header();
adforest_custom_breadcrumbs();
wp_enqueue_style('star-rating', trailingslashit(get_template_directory_uri()) . 'assets/css/star-rating.css');

get_template_part('template-parts/layouts/seller-search/search', 'modern');

get_footer();
