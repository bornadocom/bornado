<?php
get_header();
adforest_custom_breadcrumbs();
global $adforest_theme;
$adt_container_class = '';
if(!is_array($adforest_theme)) {
    $adt_container_class = "adt-container";
}
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		?>
        <div class="main-content-area">
            <section class="adt-blog-detail-section">
                <div class="container <?php echo $adt_container_class; ?>">
                    <div class="row">
                        <div class="col-lg-9">
                            <div class="blog-detail-content">
                                <ul class="detail-meta">
                                    <li><i class="far fa-user-circle"></i><?php the_author(); ?></li>
                                    <li><i class="far fa-calendar-alt"></i><?php echo get_the_date( 'F j, Y' ); ?></li>
                                </ul>
                                <h3><?php echo the_title(); ?></h3>
								<?php
								$no_img   = 'no-img';
								$response = adforest_get_feature_image( get_the_ID(), 'adforest-single-post' );
								if ( isset( $response ) && ! empty( $response ) && isset( $response[0] ) && $response[0] != "" ) {
									$no_img = '';
									?>

                                    <div class="img-box">
                                        <img class="img-fluid" src="<?php echo esc_url( $response[0] ); ?>" alt="<?php the_title(); ?>">
                                    </div>
								<?php } ?>
                                <p><?php echo the_content(); ?></p>

								<?php $post_tags = get_the_tags(); ?>
                                <div class="social-tags-box">
                                    <ul class="tags-list">
                                        <li>
                                            <i class="fas fa-tag"></i>
                                        </li>
										<?php if ( is_array( $post_tags ) ) {
											foreach ( $post_tags as $tag ) { ?>
                                                <li>
                                                    <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>"
                                                       title="<?php echo esc_attr( $tag->name ); ?>">#<?php echo esc_attr( $tag->name ); ?></a>
                                                </li>
											<?php }
										} ?>
                                    </ul>
                                    <ul class="social-links">
										<?php echo adforest_social_share(); ?>
                                    </ul>
                                </div>
                            </div>
							<?php if ( comments_open() || get_comments_number() ) {
                                if(isset($adforest_theme['sb_ad_rating']) && $adforest_theme['sb_ad_rating']) {
								    comments_template();
                                }
							} ?>
                        </div>
						<?php get_sidebar(); ?>
                    </div>
                </div>
            </section>
        </div>
	<?php } ?>

<?php } else {
	get_template_part( 'template-parts/content', 'none' );
}
?>

<?php get_footer() ?>