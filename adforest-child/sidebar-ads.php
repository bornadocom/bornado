<?php
global $adforest_theme;
$right_col       = 'col-md-4 col-xs-12';
$show_mob_filter = false;
if ( isset( $adforest_theme['search_design'] ) && $adforest_theme['search_design'] == 'sidebar' ) {
	if ( isset( $adforest_theme['search_design_sidebar_mob_filter'] ) && $adforest_theme['search_design_sidebar_mob_filter'] == true ) {
		$show_mob_filter = true;
	}
}
$ext_class = '';
$map_style = '';
if ( isset( $adforest_theme['search_design'] ) && $adforest_theme['search_design'] == 'map' ) {
	$ext_class = 'map-sidebar';
}
if ( isset( $_GET['hide-filters'] ) ) {
	$map_style = ' style="display:none;" ';
}
?>

<?php if ( $show_mob_filter ) { ?>
    <div id="adforest-ajax-sidebar" class="adforest-ajax-sidebar <?php echo esc_attr( $ext_class ); echo adforest_return_echo( $show_mob_filter ) ? 'mobile-filters' : ''; ?>">
        <div class="mobile-filter-heading"><h2><?php echo esc_html__( 'Search Filters', 'adforest' ); ?></h2></div>
        <?php if ( function_exists( 'adforest_ajax_search_toolbar' ) ) { adforest_ajax_search_toolbar(); } ?>
        <div class="accordion" id="accordionPanelsStayOpenExample">
			<?php dynamic_sidebar( 'adforest_search_sidebar' ); ?>
        </div>
        <div id="adforest-ajax-dynamic-fields"></div>
    </div>
<?php } else { ?>
    <div id="adforest-ajax-sidebar" class="adforest-ajax-sidebar">
        <?php if ( function_exists( 'adforest_ajax_search_toolbar' ) ) { adforest_ajax_search_toolbar(); } ?>
        <div class="accordion" id="accordionPanelsStayOpenExample">
		    <?php dynamic_sidebar( 'adforest_search_sidebar' ); ?>
        </div>
        <div id="adforest-ajax-dynamic-fields"></div>
    </div>
<?php } ?>

<?php if ( $show_mob_filter ) { ?>
    <div class="mobile-filters-btn">
        <a class="adt-button-dark" href="javascript:void(0);"><?php esc_html_e( 'Filters', 'adforest' ); ?><i
                    class="fa fa-filter"></i></a>
    </div>
<?php } ?>
