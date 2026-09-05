<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$search_layout = 'sidebar';
if ( isset( $adforest_theme['search_design'] ) && '' !== $adforest_theme['search_design'] ) {
	$search_layout = $adforest_theme['search_design'];
}

if ( $search_layout !== 'map' && function_exists( 'adforest_custom_breadcrumbs' ) ) {
	adforest_custom_breadcrumbs();
}
?>
<section class="bornado-seo-landing-entry">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<?php
				while ( have_posts() ) :
					the_post();
					$title   = trim( get_the_title() );
					$excerpt = trim( (string) get_the_excerpt() );
					$content = trim( (string) get_the_content() );

					if ( '' !== $title || '' !== $excerpt || '' !== $content ) :
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'bornado-seo-landing-content' ); ?>>
							<?php if ( '' !== $title ) : ?>
								<header class="bornado-seo-landing-content__header">
									<h1 class="bornado-seo-landing-content__title"><?php the_title(); ?></h1>
									<?php if ( '' !== $excerpt ) : ?>
										<div class="bornado-seo-landing-content__excerpt"><?php echo wp_kses_post( wpautop( $excerpt ) ); ?></div>
									<?php endif; ?>
								</header>
							<?php endif; ?>

							<?php if ( '' !== $content ) : ?>
								<div class="bornado-seo-landing-content__body">
									<?php the_content(); ?>
								</div>
							<?php endif; ?>
						</article>
						<?php
					endif;
				endwhile;
				rewind_posts();
				?>
			</div>
		</div>
	</div>
</section>
<?php
$search_template_relative = 'template-parts/layouts/search/search-' . $search_layout . '.php';
$search_template          = locate_template( array( $search_template_relative ), false, false );

if ( $search_template ) {
	require $search_template;
} else {
	$search_template = trailingslashit( get_template_directory() ) . $search_template_relative;
	if ( file_exists( $search_template ) ) {
		require $search_template;
	}
}
?>
<div class="modal fade" id="cat_modal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><i class="fa fa-cogs"></i> <?php echo esc_html__( 'Select Any Category', 'adforest' ); ?></h5>
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
				<button type="submit" id="ad-search-btn" class="btn btn-dark w-100"><?php echo esc_html__( 'Submit', 'adforest' ); ?></button>
			</div>
		</div>
	</div>
</div>
<div class="search-modal modal fade states_model" id="states_model" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title text-center"><i class="fa fa-cogs"></i> <?php echo esc_html__( 'Select Your Location', 'adforest' ); ?></h3>
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
				<button type="submit" id="country-btn" class="btn btn-theme"><?php echo esc_html__( 'Submit', 'adforest' ); ?></button>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
