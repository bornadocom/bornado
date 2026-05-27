<?php get_header();
global $adforest_theme;
$page_class = esc_attr("adt-white-breadcrumb");
adforest_custom_breadcrumbs($page_class);
$adt_container_class = "";
if ( isset( $adforest_theme['sb_header'] ) && ( $adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar" ) ) {
    $adt_container_class = "adt-container";
}

$page_id = get_queried_object_id();

$page_header_style = get_post_meta($page_id, '_page_header_style', true);

if(isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2")) {
    $adt_container_class = "adt-container";
}
?>
<?php
if ( have_posts() ) {
	the_post();
	$post = get_post();
	if ( $post && sb_is_elementor( $post->ID ) ) {
		the_content();
	} else if ( $post && ( preg_match( '/vc_row/', $post->post_content ) || preg_match( '/post_job/', $post->post_content ) ) ) {
		the_content();
	} else {
		?>
        <section <?php post_class( 'static-page' ); ?>>
            <div class="container <?php echo esc_attr($adt_container_class); ?>">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 no-padding">
                        <div class="single-blog blog-detial">
                            <div class="blog-post">
                                <div class="post-excerpt post-desc">
									<?php the_content(); ?>
                                    <div class="col-md-12 add-pages margin-top-20">
										<?php
										$args = array(
											'before'           => '',
											'after'            => '',
											'link_before'      => '<span class="btn btn-default">',
											'link_after'       => '</span>',
											'next_or_number'   => 'number',
											'separator'        => ' ',
											'nextpagelink'     => esc_html__( 'Next >>', 'adforest' ),
											'previouspagelink' => esc_html__( '<< Prev', 'adforest' ),
											'highlight'        => 'iAmActive'
										);

										wp_link_pages( $args );
										?>
                                    </div>

                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-12 co-xs-12 xol-sm-12">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
		<?php
	}
}
?>
<?php get_footer(); ?>