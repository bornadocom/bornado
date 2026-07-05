<?php
/* Template Name: Ad Search */

/**
 * Child-theme aware Ad Search page template.
 *
 * Keeps parent behavior, but resolves the search layout template through
 * locate_template() so child overrides like `search-map.php` can be used
 * safely without editing parent theme files.
 *
 * @package Adforest
 */

get_header();

$search_layout = 'sidebar';
if (isset($adforest_theme['search_design']) && $adforest_theme['search_design'] != '') {
    $search_layout = $adforest_theme['search_design'];
}

$seo_heading_title = function_exists('bornado_get_ad_search_seo_heading_title')
    ? bornado_get_ad_search_seo_heading_title()
    : '';

if ($seo_heading_title !== '') {
    echo '<div class="bornado-ad-search-seo-heading"><div class="container adt-container"><h1 class="bornado-ad-search-seo-title">' . esc_html($seo_heading_title) . '</h1></div></div>';
}

if ($search_layout !== 'map') {
    adforest_custom_breadcrumbs();
}

$layout_relative_path = 'template-parts/layouts/search/search-' . $search_layout . '.php';
$layout_template = locate_template(array($layout_relative_path), false, false);

if ($layout_template) {
    require $layout_template;
} else {
    require trailingslashit(get_template_directory()) . $layout_relative_path;
}
?>
    <div class="modal fade" id="cat_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i
                                class="fa fa-cogs"></i> <?php echo esc_html__('Select Any Category', 'adforest'); ?></h5>
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
                        <?php echo esc_html__('Submit', 'adforest'); ?>
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
                                class="fa fa-cogs"></i> <?php echo esc_html__('Select Your Location', 'adforest'); ?>
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
                            class="btn btn-theme"> <?php echo esc_html__('Submit', 'adforest'); ?> </button>
                </div>
            </div>
        </div>
    </div>
<?php
get_footer();
