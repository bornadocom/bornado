<?php
/**
 * List-view card — horizontal layout for comparison-heavy browsing.
 *
 * Image left, content right on desktop; stacks on mobile. Exposes more
 * metadata than the grid cards (description excerpt, category chain).
 * Receives the same data scope as the other card templates.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id     = get_the_ID();
$posted_time = get_the_time( 'U', $post_id );
$ad_type     = isset( $ad_type ) ? $ad_type : '';
$first_img   = isset( $first_img ) ? $first_img : ( ! empty( $all_ad_images ) ? reset( $all_ad_images ) : get_template_directory_uri() . '/images/no-image.jpg' );
$is_urgent   = ( $ad_type !== '' && stripos( $ad_type, 'urgent' ) !== false );

$excerpt = '';
if ( function_exists( 'truncate_string' ) ) {
    $excerpt = truncate_string( wp_strip_all_tags( get_the_content() ), 180 );
} else {
    $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 28, '…' );
}

$category_links = array();
if ( ! empty( $ad_categories_post ) && is_array( $ad_categories_post ) ) {
    foreach ( $ad_categories_post as $category ) {
        if ( ! isset( $category->term_id ) ) {
            continue;
        }
        $url = get_term_link( (int) $category->term_id );
        if ( ! is_wp_error( $url ) ) {
            $category_links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $category->name ) . '</a>';
        }
    }
}

$is_verified = false;
if ( function_exists( 'adforest_is_verified_user' ) ) {
    $is_verified = (bool) adforest_is_verified_user( get_post_field( 'post_author', $post_id ) );
} elseif ( function_exists( 'adforest_user_is_verified' ) ) {
    $is_verified = (bool) adforest_user_is_verified( get_post_field( 'post_author', $post_id ) );
}
?>
<div class="adf-card adf-card-list<?php echo $is_featured ? ' adf-card-list--featured' : ''; ?>" data-post-id="<?php echo (int) $post_id; ?>">
    <div class="adf-card-list__media">
        <a href="<?php echo esc_url( $ad_permalink ); ?>" aria-label="<?php echo esc_attr( $ad_title ); ?>">
            <img class="adf-card-list__img" src="<?php echo esc_url( $first_img ); ?>" alt="<?php echo esc_attr( $ad_title ); ?>" loading="lazy">
        </a>
        <div class="adf-card-list__badges">
            <?php if ( $is_featured ) : ?>
                <span class="adf-badge adf-badge--featured"><?php esc_html_e( 'Featured', 'adforest' ); ?></span>
            <?php endif; ?>
            <?php if ( $is_urgent ) : ?>
                <span class="adf-badge adf-badge--urgent"><?php esc_html_e( 'Urgent', 'adforest' ); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="adf-card-list__body">
        <div class="adf-card-list__head">
            <?php if ( ! empty( $category_links ) ) : ?>
                <div class="adf-card-list__crumbs"><?php echo wp_kses( implode( ' <span>›</span> ', $category_links ), array( 'a' => array( 'href' => array() ), 'span' => array() ) ); ?></div>
            <?php endif; ?>
            <a class="adf-card-list__title-link" href="<?php echo esc_url( $ad_permalink ); ?>">
                <h3 class="adf-card-list__title" dir="auto"><?php echo esc_html( $ad_title ); ?></h3>
            </a>
        </div>

        <?php if ( $excerpt !== '' ) : ?>
            <p class="adf-card-list__excerpt"><?php echo esc_html( $excerpt ); ?></p>
        <?php endif; ?>

        <div class="adf-card-list__meta">
            <?php if ( $truncated_location !== '' ) : ?>
                <span class="adf-card-list__meta-item"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( $truncated_location ); ?></span>
            <?php endif; ?>
            <?php if ( function_exists( 'adforest_get_ad_posted_date' ) ) : ?>
                <span class="adf-card-list__meta-item"><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( adforest_get_ad_posted_date( $posted_time ) ); ?></span>
            <?php endif; ?>
            <?php if ( ! empty( $ad_poster_name ) ) : ?>
                <span class="adf-card-list__meta-item"><i class="far fa-user" aria-hidden="true"></i> <?php echo esc_html( $ad_poster_name ); ?></span>
            <?php endif; ?>
            <?php if ( $is_verified ) : ?>
                <span class="adf-card-list__meta-item adf-card-list__meta-item--verified"><i class="fas fa-check-circle" aria-hidden="true"></i> <?php esc_html_e( 'Verified', 'adforest' ); ?></span>
            <?php endif; ?>
        </div>

        <div class="adf-card-list__footer">
            <div class="adf-card-list__price"><?php echo wp_kses( $price_html, defined( 'ADFOREST_ALLOWED_FORM_HTML' ) ? ADFOREST_ALLOWED_FORM_HTML : array() ); ?></div>
            <div class="adf-card-list__actions">
                <a href="javascript:void(0);"
                   class="adf-card-list__fav ad_to_fav<?php echo esc_attr( $fav_extra ); ?>"
                   data-adid="<?php echo (int) $post_id; ?>"
                   data-toggle="tooltip" data-placement="top"
                   title="<?php echo esc_attr( $fav_title ); ?>"
                   aria-label="<?php echo esc_attr( $fav_title ); ?>">
                    <i class="<?php echo esc_attr( $heart_class ); ?>" aria-hidden="true"></i>
                </a>
                <a class="adf-card-list__cta" href="<?php echo esc_url( $ad_permalink ); ?>"><?php esc_html_e( 'View Details', 'adforest' ); ?></a>
            </div>
        </div>
    </div>
</div>
