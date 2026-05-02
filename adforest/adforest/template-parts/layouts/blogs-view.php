<?php
global $adforest_theme;
if ( get_query_var( 'paged' ) ) {
	$paged = get_query_var( 'paged' );
} else if ( get_query_var( 'page' ) ) {
	$paged = get_query_var( 'page' );
} else {
	$paged = 1;
}
$args = array(
	'post_type'      => 'post',
	'posts_per_page' => get_option( 'posts_per_page' ),
	'paged'          => $paged,
);

if ( isset($_GET['s']) && !empty($_GET['s']) ) {
    $args['s'] = sanitize_text_field( $_GET['s'] );
}

$query = new WP_Query( $args );

$adt_container_class = "";
if ( isset( $adforest_theme['sb_header'] ) && ( $adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar" ) ) {
	$adt_container_class = "adt-container";
}

$page_id = get_queried_object_id();

$page_header_style = get_post_meta($page_id, '_page_header_style', true);

if(isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2")) {
    $adt_container_class = "adt-container";
}

if(!is_array($adforest_theme)) {
    $adt_container_class = "adt-container";
}
?>
<div class="main-content-area">
<section class="adt-blog-section">
        <div class="container <?php echo esc_attr($adt_container_class); ?>">
            <div class="row">
				<?php
				if ( isset( $adforest_theme['blog_sidebar'] ) && $adforest_theme['blog_sidebar'] == 'left' && $adforest_theme['blog_sidebar'] != 'no-sidebar' ) {
					get_sidebar();
				} ?>
                <div class="col-lg-9">
                    <div class="row">
						<?php if ( $query->have_posts() ) {
							while ( $query->have_posts() ) {
								$query->the_post();
								$no_img   = 'no-img';
								$response = adforest_get_feature_image( get_the_ID(), 'adforest_single_product' );
								?>
                                <div class="col-sm-6 col-md-4">
                                    <div class="adt-blog-card">
                                        <div class="blog-img-box">
											<?php if ( isset( $response[0] ) && $response[0] != "" ) {
												$no_img = ''; ?>
                                                <a href="<?php the_permalink(); ?>">
                                                    <img src="<?php echo esc_url( $response[0] ); ?>"
                                                         alt="<?php the_title(); ?>">
                                                </a>
											<?php } else { ?>
                                                <a href="<?php the_permalink(); ?>">
                                                    <img src="<?php echo trailingslashit( get_template_directory_uri() ) . 'images/no-image.jpg'; ?>"
                                                         alt="<?php the_title(); ?>">
                                                </a>
											<?php } ?>
                                        </div>
                                        <div class="blog-meta-box">
                                            <div class="date"><i
                                                        class="far fa-calendar-alt"></i><?php echo adforest_get_date( get_the_ID() ); ?>
                                            </div>
                                            <a href="<?php the_permalink(); ?>">
                                                <h5><?php the_title(); ?></h5>
                                            </a>
                                        </div>
                                    </div>
                                </div>
							<?php }
						} else {
							echo '<h2>' . __( "No Posts Found", "adforest" ) . '</h2>';
						} ?>
                    </div>
                    <!-- Pagination -->
                    <div class="pagination">
                        <nav aria-label="pagination">
                            <ul class="pagination adt-custom-pagination">
								<?php
								$total_pages = $query->max_num_pages;
								if ( $total_pages > 1 ) {
									if ( $paged > 1 ) {
										echo '<li class="page-item"><a class="page-link prv" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '"><i class="fas fa-chevron-left"></i></a></li>';
									}
									for ( $i = 1; $i <= $total_pages; $i ++ ) {
										$active_class = ( $i == $paged ) ? ' active' : '';
										echo '<li class="page-item"><a class="page-link' . $active_class . '" href="' . esc_url( get_pagenum_link( $i ) ) . '">' . str_pad( $i, 2, '0', STR_PAD_LEFT ) . '</a></li>';
									}
									if ( $paged < $total_pages ) {
										echo '<li class="page-item"><a class="page-link nxt" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '"><i class="fas fa-chevron-right"></i></a></li>';
									}
								}
								?>
                            </ul>
                        </nav>
                    </div>
                </div>
				<?php
				if ( isset( $adforest_theme['blog_sidebar'] ) && $adforest_theme['blog_sidebar'] == 'right' && $adforest_theme['blog_sidebar'] != 'no-sidebar' ) {
					get_sidebar();
				} elseif(!is_array($adforest_theme)) {
                    get_sidebar();
                } ?>
            </div>
        </div>
    </section>
</div>