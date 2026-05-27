<?php
/**
 * Modern listing card — primary new layout.
 *
 * Called by adforest_load_search_card() inside the search grid container.
 * All data variables are passed in via extract() and must match what the
 * dispatcher provides; do not run queries here.
 *
 * Expected scope:
 *   $ad_permalink, $first_img, $all_ad_images, $is_featured,
 *   $heart_class, $is_fav, $fav_title, $fav_extra,
 *   $truncated_title, $ad_title, $truncated_location, $price_html,
 *   $ad_categories_post, $ad_poster_img, $ad_poster_name, $ad_type,
 *   $ad_details, $top_bar_specific_style
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id        = get_the_ID();
$posted_time    = get_the_time( 'U', $post_id );
$ad_type        = isset( $ad_type ) ? $ad_type : '';
$first_img      = isset( $first_img ) ? $first_img : ( ! empty( $all_ad_images ) ? reset( $all_ad_images ) : get_template_directory_uri() . '/images/no-image.jpg' );
$is_urgent      = ( $ad_type !== '' && stripos( $ad_type, 'urgent' ) !== false );
$show_ad_type   = ( $ad_type !== '' && ! $is_urgent );
$category_name  = '';
$category_link  = '';
if ( ! empty( $ad_categories_post ) && is_array( $ad_categories_post ) && isset( $ad_categories_post[0]->name ) ) {
    $category_name = $ad_categories_post[0]->name;
    $category_link = isset( $ad_categories_post[0]->term_id ) ? get_term_link( (int) $ad_categories_post[0]->term_id ) : '';
}

$is_verified = false;
if ( function_exists( 'adforest_is_verified_user' ) ) {
    $is_verified = (bool) adforest_is_verified_user( get_post_field( 'post_author', $post_id ) );
} elseif ( function_exists( 'adforest_user_is_verified' ) ) {
    $is_verified = (bool) adforest_user_is_verified( get_post_field( 'post_author', $post_id ) );
}
?>
<div class="adf-card adf-card-modern" data-post-id="<?php echo (int) $post_id; ?>">
    <div class="adf-card-modern__media">
        <a class="adf-card-modern__media-link" href="<?php echo esc_url( $ad_permalink ); ?>" aria-label="<?php echo esc_attr( $ad_title ); ?>">
            <img class="adf-card-modern__img" src="<?php echo esc_url( $first_img ); ?>" alt="<?php echo esc_attr( $ad_title ); ?>" loading="lazy">
        </a>

        <div class="adf-card-modern__badges">
            <?php if ( $is_featured ) : ?>
                <span class="adf-badge adf-badge--featured"><?php esc_html_e( 'Featured', 'adforest' ); ?></span>
            <?php endif; ?>
            <?php if ( $is_urgent ) : ?>
                <span class="adf-badge adf-badge--urgent"><?php esc_html_e( 'Urgent', 'adforest' ); ?></span>
            <?php endif; ?>
            <?php if ( $is_verified ) : ?>
                <span class="adf-badge adf-badge--verified"><i class="fas fa-check-circle" aria-hidden="true"></i> <?php esc_html_e( 'Verified', 'adforest' ); ?></span>
            <?php endif; ?>
            <?php if ( $show_ad_type ) : ?>
                <span class="adf-badge adf-badge--type"><?php echo esc_html( $ad_type ); ?></span>
            <?php endif; ?>
        </div>

        <a href="javascript:void(0);"
           class="adf-card-modern__fav ad_to_fav<?php echo esc_attr( $fav_extra ); ?>"
           data-adid="<?php echo (int) $post_id; ?>"
           data-toggle="tooltip" data-placement="top"
           title="<?php echo esc_attr( $fav_title ); ?>"
           aria-label="<?php echo esc_attr( $fav_title ); ?>">
            <i class="<?php echo esc_attr( $heart_class ); ?>" aria-hidden="true"></i>
        </a>
    </div>

    <div class="adf-card-modern__body">
        <?php if ( $category_name !== '' ) : ?>
            <a class="adf-card-modern__category" href="<?php echo esc_url( $category_link ); ?>"><?php echo esc_html( $category_name ); ?></a>
        <?php endif; ?>

        <a class="adf-card-modern__title-link" href="<?php echo esc_url( $ad_permalink ); ?>">
            <h3 class="adf-card-modern__title" dir="auto"><?php echo esc_html( $truncated_title ); ?></h3>
        </a>

        <div class="adf-card-modern__meta">
            <?php if ( $truncated_location !== '' ) : ?>
                <span class="adf-card-modern__location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( $truncated_location ); ?></span>
            <?php endif; ?>
            <?php if ( function_exists( 'adforest_get_ad_posted_date' ) ) : ?>
                <span class="adf-card-modern__date"><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( adforest_get_ad_posted_date( $posted_time ) ); ?></span>
            <?php endif; ?>
        </div>

        <div class="adf-card-modern__footer">
            <div class="adf-card-modern__price"><?php echo wp_kses( $price_html, defined( 'ADFOREST_ALLOWED_FORM_HTML' ) ? ADFOREST_ALLOWED_FORM_HTML : array() ); ?></div>
            <a class="adf-card-modern__cta" href="<?php echo esc_url( $ad_permalink ); ?>"><?php esc_html_e( 'View', 'adforest' ); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </div>
</div>
