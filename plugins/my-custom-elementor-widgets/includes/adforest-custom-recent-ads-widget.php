<?php
/**
 * Custom Elementor widget clone for AdForest Recent Ads.
 *
 * This file creates a separate widget without editing theme core files.
 *
 * @package My_Custom_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mcew_resolve_custom_recent_ads_main_ppp' ) ) {
	/**
	 * Resolve posts_per_page for custom recent ads main query.
	 *
	 * Accepts -1 as unlimited. Empty/invalid values fallback to 5.
	 *
	 * @param mixed $raw_value Control value.
	 * @return int
	 */
	function mcew_resolve_custom_recent_ads_main_ppp( $raw_value ) {
		if ( '' === $raw_value || null === $raw_value ) {
			return 5;
		}

		$value = (int) $raw_value;
		if ( -1 === $value ) {
			return -1;
		}

		return ( $value > 0 ) ? $value : 5;
	}
}

if ( ! function_exists( 'mcew_custom_recent_ads_shortcode' ) ) {
	/**
	 * Render custom recent ads section.
	 *
	 * @param array<string,mixed> $params Widget params.
	 * @return void
	 */
	function mcew_custom_recent_ads_shortcode( $params ) {
		global $adforest_theme;

		$main_sec_ad_type       = $params['main_sec_ad_type'] ?? '';
		$main_section_ppp       = mcew_resolve_custom_recent_ads_main_ppp( $params['main_section_ppp'] ?? '' );
		$left_sec_ad_type       = $params['left_sec_ad_type'] ?? '';
		$left_section_ppp       = $params['left_section_ppp'] ?? '';
		$left_ad_img            = $params['left_ad'] ?? '';
		$show_left_sec_ad_type  = $params['show_left_sec_ad_type'] ?? '';
		$show_left_ad           = $params['show_left_ad'] ?? '';
		$left_sec_ads_title     = $params['left_sec_ads_title'] ?? '';
		$show_right_ad_1        = $params['show_right_ad_1'] ?? '';
		$show_right_ad_2        = $params['show_right_ad_2'] ?? '';
		$ad_1                   = $params['advert_1'] ?? '';
		$ad_2                   = $params['advert_2'] ?? '';
		$ad_title_limit_side    = $params['ad_title_limit_side'] ?? '';
		$ad_title_limit_main    = $params['ad_title_limit_main'] ?? '';
		$adt_container_class    = '';
		$sb_2column             = ( isset( $adforest_theme['sb_2column_mobile_layout'] ) && true === (bool) $adforest_theme['sb_2column_mobile_layout'] );
		$mobile_two_columns     = ( false === $sb_2column ) ? 'one-column-mobile-layout' : '';
		$show_left_sidebar      = ( 'yes' === $show_left_sec_ad_type );
		$show_right_sidebar     = (
			( 'yes' === $show_right_ad_1 && ! empty( $ad_1 ) ) ||
			( 'yes' === $show_right_ad_2 && ! empty( $ad_2 ) )
		);
		$middle_column_class    = 'col-xl-6';

		if ( $show_left_sidebar && $show_right_sidebar ) {
			$middle_column_class = 'col-xl-6';
		} elseif ( $show_left_sidebar || $show_right_sidebar ) {
			$middle_column_class = 'col-xl-9';
		} else {
			$middle_column_class = 'col-xl-12';
		}

		if ( isset( $adforest_theme['sb_header'] ) && ( 'white' === $adforest_theme['sb_header'] || 'header_w_topbar' === $adforest_theme['sb_header'] ) ) {
			$adt_container_class = 'adt-container';
		}
		?>
		<section class="adt-estate-ads-section mcew-custom-recent-ads-section">
			<div class="container <?php echo esc_attr( $adt_container_class ); ?>">
				<div class="row">
					<?php if ( $show_left_sidebar ) : ?>
						<div class="col-lg-6 col-xl-3">
							<?php
							if ( function_exists( 'adforest_display_1_ads_sidebar_section' ) ) {
								echo adforest_display_1_ads_sidebar_section( $left_sec_ad_type, $left_section_ppp, $left_ad_img, $show_left_ad, $left_sec_ads_title, $show_left_sec_ad_type, $ad_title_limit_side ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
					<?php endif; ?>
					<div class="<?php echo esc_attr( $middle_column_class ); ?> middle-content <?php echo esc_attr( $mobile_two_columns ); ?>">
						<?php
						$args = array(
							'post_type'      => 'ad_post',
							'posts_per_page' => $main_section_ppp,
							'post_status'    => 'publish',
						);

						if ( 'recent' === $main_sec_ad_type ) {
							$args['orderby'] = 'date';
							$args['order']   = 'DESC';
						} elseif ( 'featured' === $main_sec_ad_type ) {
							$args['meta_key']   = '_adforest_is_feature';
							$args['meta_value'] = '1';
							$args['orderby']    = 'date';
							$args['order']      = 'DESC';
						}

						$ads_query = new WP_Query( $args );
						if ( $ads_query->have_posts() ) :
							while ( $ads_query->have_posts() ) :
								$ads_query->the_post();

								$ad_details         = function_exists( 'get_ad_post_details' ) ? get_ad_post_details( get_the_ID() ) : array();
								$first_img          = $ad_details['img'] ?? '';
								$truncated_location = $ad_details['truncated_location'] ?? '';
								$title              = $ad_details['ad_title'] ?? get_the_title();
								$truncated_title    = function_exists( 'truncate_string' ) ? truncate_string( $title, $ad_title_limit_main ) : $title;
								$price_html         = $ad_details['price_html'] ?? '';
								$ad_permalink       = $ad_details['ad_link'] ?? get_the_permalink();
								$heart_class        = $ad_details['heart_class'] ?? 'far fa-heart';
								$is_featured        = ! empty( $ad_details['is_featured'] );
								$selected_categories = ( isset( $ad_details['categories'] ) && is_array( $ad_details['categories'] ) ) ? $ad_details['categories'] : array();
								$category_link      = '';
								$category_name      = '';

								if ( ! empty( $selected_categories ) && isset( $selected_categories[0]->term_id ) ) {
									$category_link = get_term_link( $selected_categories[0]->term_id );
									if ( is_wp_error( $category_link ) ) {
										$category_link = '';
									}
									$category_name = isset( $selected_categories[0]->name ) ? $selected_categories[0]->name : '';
								}
								?>
								<div class="adt-category-ad-list">
									<div class="category-img-box">
										<a href="<?php echo esc_url( $ad_permalink ); ?>">
											<img class="img-fluid" src="<?php echo esc_url( $first_img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
											<?php if ( $is_featured ) : ?>
												<span class="featured-label"><?php echo esc_html__( 'Featured', 'adforest-elementor' ); ?></span>
											<?php endif; ?>
										</a>
									</div>
									<div class="category-content-box">
										<a href="javascript:void(0);" data-adid="<?php echo esc_attr( get_the_ID() ); ?>" class="favourite ad_to_fav" data-toggle="tooltip" data-placement="top" title="Click to add to favorites">
											<i class="<?php echo esc_attr( $heart_class ); ?>"></i>
										</a>
										<div class="adt-ad-cats">
											<a class="ctg-tag" href="<?php echo esc_url( $category_link ); ?>">
												<?php echo esc_html( $category_name ); ?>
											</a>
										</div>
										<a href="<?php echo esc_url( $ad_permalink ); ?>">
											<h5><?php echo esc_html( $truncated_title ); ?></h5>
										</a>
										<p><i class="fas fa-map-marker-alt"></i><?php echo esc_html( $truncated_location ); ?></p>
										<div class="price-box">
											<?php echo wp_kses_post( $price_html ); ?>
											<a href="<?php echo esc_url( $ad_permalink ); ?>" class="detail-btn"><?php echo esc_html__( 'Detail', 'adforest-elementor' ); ?></a>
										</div>
									</div>
								</div>
								<?php
							endwhile;
							wp_reset_postdata();
						endif;
						?>
					</div>
					<?php if ( $show_right_sidebar ) : ?>
						<div class="col-lg-6 col-xl-3 right-sidebar">
							<?php if ( 'yes' === $show_right_ad_1 && ! empty( $ad_1 ) ) : ?>
								<div class="adt-vertical-ad-box">
									<?php echo wp_kses_post( $ad_1 ); ?>
								</div>
							<?php endif; ?>
							<?php if ( 'yes' === $show_right_ad_2 && ! empty( $ad_2 ) ) : ?>
								<div class="adt-vertical-ad-box">
									<?php echo wp_kses_post( $ad_2 ); ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'mcew_register_custom_recent_ads_widget' ) ) {
	/**
	 * Register custom recent ads widget into Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 * @return void
	 */
	function mcew_register_custom_recent_ads_widget( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once MY_CEW_PLUGIN_DIR . 'includes/adforest-custom-recent-ads-widget-class.php';

		if ( ! class_exists( 'MCEW_Custom_Recent_Ads_Widget' ) ) {
			return;
		}
		$widgets_manager->register( new MCEW_Custom_Recent_Ads_Widget() );
	}
}
add_action( 'elementor/widgets/register', 'mcew_register_custom_recent_ads_widget', 99 );
