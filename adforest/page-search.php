<?php
/* Template Name: Ad Search */

/**
 * The template for displaying Pages.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Adforest
 */

get_header();

$search_layout = 'sidebar';
if ( isset( $adforest_theme['search_design'] ) && $adforest_theme['search_design'] != '' ) {
    $search_layout = $adforest_theme['search_design'];
}
if ( $search_layout !== 'map' ) {
    adforest_custom_breadcrumbs();
}

require trailingslashit( get_template_directory() ) . 'template-parts/layouts/search/search-' . $search_layout . '.php';
?>
    <div class="modal fade" id="cat_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i
                                class="fa fa-cogs"></i> <?php echo esc_html__( 'Select Any Category', 'adforest' ); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="search-block">
                        <div class="row"></div>
                        <div class="row">
                            <div class="col-12 popular-search" id="cats_response"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="ad-search-btn" class="btn btn-dark w-100">
                        <?php echo esc_html__( 'Submit', 'adforest' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="search-modal modal fade states_model" id="states_model" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">

                    <h3 class="modal-title text-center"><i
                                class="fa fa-cogs"></i> <?php echo esc_html__( 'Select Your Location', 'adforest' ); ?>
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="search-block">
                        <div class="row">
                            <div class="col-md-12 col-xs-12 col-sm-12 popular-search" id="countries_response"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="country-btn"
                            class="btn btn-theme"> <?php echo esc_html__( 'Submit', 'adforest' ); ?> </button>
                </div>
            </div>
        </div>
    </div>
<?php
get_footer();