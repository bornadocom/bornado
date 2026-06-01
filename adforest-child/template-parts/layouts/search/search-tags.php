<?php
/**
 * Child-theme override for AdForest legacy search tags.
 *
 * Search 2.0 already renders active-filter chips from the current URL.
 * When the legacy `search-tags.php` template also runs on a full refresh,
 * users see duplicated filter badges (for example ad type / condition).
 * Keep the legacy template only as a fallback when the modern AJAX search
 * layer is not active on the current request.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_script_is( 'adforest-search-ajax', 'enqueued' ) || wp_script_is( 'adforest-search-ux', 'enqueued' ) ) {
	return;
}

$parent_template = trailingslashit( get_template_directory() ) . 'template-parts/layouts/search/search-tags.php';
if ( file_exists( $parent_template ) ) {
	require $parent_template;
}
