<?php get_header();
adforest_custom_breadcrumbs();
?>
<?php global $adforest_theme;
$title = isset($adforest_theme['sb_404_title']) ? $adforest_theme['sb_404_title'] : esc_html__('Sorry, this page does not exist.', 'adforest');
$desc = isset($adforest_theme['sb_404_description']) ? $adforest_theme['sb_404_description'] : "";
$img = isset($adforest_theme['adforest_404_image']['url']) ? $adforest_theme['adforest_404_image']['url'] : "";

?>
    <section class="adt-404-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="content-box">
                        <img class="img-fluid" src="<?php echo esc_url($img); ?>" alt="404">
                        <h1><?php echo esc_html($title) ?></h1>
                        <?php echo wp_kses_post($desc) ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__("Back to Homepage", "adforest"); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php get_footer(); ?>