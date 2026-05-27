<?php
/**
 * Standalone footer loader for the AdForest - Home (Modern) page template.
 *
 * Loaded via get_footer('home-modern'). Delegates the visual
 * markup to the shared template part at
 * `template-parts/footers/footer-home-modern.php` — the same part
 * `footer.php` loads when admin picks the "Footer Modern" option
 * in Theme Options → Footer Style — then emits wp_footer() and
 * closes the document.
 *
 * @package Adforest
 */
?>
<?php get_template_part('template-parts/footers/footer', 'home-modern'); ?>

<?php wp_footer(); ?>
</body>
</html>
