<?php
/**
 * Child override for AdForest sidebar search layout.
 *
 * Keep the parent template streaming. Toolbar H1 / sort-icon patches run once
 * on the existing full-page output buffer instead of copying all listing HTML
 * into a second PHP string.
 */

$parent_template = trailingslashit( get_template_directory() ) . 'template-parts/layouts/search/search-sidebar.php';

if ( file_exists( $parent_template ) ) {
	require $parent_template;
}
