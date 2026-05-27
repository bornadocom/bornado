<?php /* 
Template Name: AdForest Dashboard
*
* The template for displaying Pages.
*
* @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
*
* @package Adforest
*/

adforest_user_not_logged_in();  
?>
<?php get_header('dashboard'); ?>
<?php 
	get_template_part('dashboard/index', '');
?>
<?php get_footer('dashboard'); 