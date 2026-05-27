<?php
defined( 'ABSPATH' ) || exit;
get_header( 'shop' );
/**
 * Hook: wcmp_before_main_content.
 *
 */
do_action( 'wcmp_before_main_content' );

global $MVX , $adforest_theme;



$vendor_temp_custom = isset($adforest_theme['sb_vendor_templates0']) ? $adforest_theme['sb_vendor_templates0'] : '';


if(isset($_GET['dashboard_type']) && $_GET['dashboard_type'] != ""){
    // Sanitize and validate dashboard_type - only allow numeric values 1-3
    $dashboard_type = absint($_GET['dashboard_type']);
    if ($dashboard_type >= 1 && $dashboard_type <= 3) {
        $template_class = 'template' . $dashboard_type;
        $vendor_temp_custom = 2;
    }
}


?>
<header class="woocommerce-products-header">
	<?php if ( apply_filters( 'wcmp_show_page_title', true ) && $vendor_temp_custom != 1 ) : ?>
		<div class="woocommerce-products-header__title page-title"><?php is_tax($MVX->taxonomy->taxonomy_name) ? woocommerce_page_title() : print(get_user_meta( wcmp_find_shop_page_vendor(), '_vendor_page_title', true )); ?></div>
	<?php endif; ?>

	<?php
	/**
	 * Hook: wcmp_archive_description.
	 *
	 */
	do_action( 'mvx_archive_description' );
	?>
</header>
<?php

/**
 * Hook: wcmp_store_tab_contents.
 *
 * Output wcmp store widget
 */

if($vendor_temp_custom != 1){
    do_action( 'mvx_store_tab_widget_contents' );
}

/**
 * Hook: wcmp_after_main_content.
 *
 */
do_action( 'mvx_after_main_content' );

/**
 * Hook: wcmp_sidebar.
 *
 */
// deprecated since version 3.0.0 with no alternative available
// do_action( 'wcmp_sidebar' );

get_footer( 'shop' );