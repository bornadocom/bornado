<?php
/**
 * Compact listing card — dense layout for high-volume marketplaces.
 *
 * Receives the same data scope as card-modern.php. Optimised for 4–5 cards
 * per row on desktop and 2 per row on mobile. No hover carousel, no author
 * block — just image, title, price, location.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id   = get_the_ID();
$ad_type   = isset( $ad_type ) ? $ad_type : '';
$first_img = isset( $first_img ) ? $first_img : ( ! empty( $all_ad_images ) ? reset( $all_ad_images ) : get_template_directory_uri() . '/images/no-image.jpg' );
$is_urgent = ( $ad_type !== '' && stripos( $ad_type, 'urgent' ) !== false );
?>
<div class="adf-card adf-card-compact" data-post-id="<?php echo (int) $post_id; ?>">
    <div class="adf-card-compact__media">
        <a href="<?php echo esc_url( $ad_permalink ); ?>" aria-label="<?php echo esc_attr( $ad_title ); ?>">
            <img class="adf-card-compact__img" src="<?php echo esc_url( $first_img ); ?>" alt="<?php echo esc_attr( $ad_title ); ?>" loading="lazy">
        </a>

        <?php if ( $is_featured || $is_urgent ) : ?>
            <div class="adf-card-compact__badges">
                <?php if ( $is_featured ) : ?>
                    <span class="adf-badge adf-badge--featured adf-badge--sm"><?php esc_html_e( 'Featured', 'adforest' ); ?></span>
                <?php endif; ?>
                <?php if ( $is_urgent ) : ?>
                    <span class="adf-badge adf-badge--urgent adf-badge--sm"><?php esc_html_e( 'Urgent', 'adforest' ); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="javascript:void(0);"
           class="adf-card-compact__fav ad_to_fav<?php echo esc_attr( $fav_extra ); ?>"
           data-adid="<?php echo (int) $post_id; ?>"
           data-toggle="tooltip" data-placement="top"
           title="<?php echo esc_attr( $fav_title ); ?>"
           aria-label="<?php echo esc_attr( $fav_title ); ?>">
            <i class="<?php echo esc_attr( $heart_class ); ?>" aria-hidden="true"></i>
        </a>
    </div>

    <div class="adf-card-compact__body">
        <a class="adf-card-compact__title-link" href="<?php echo esc_url( $ad_permalink ); ?>">
            <h4 class="adf-card-compact__title" dir="auto"><?php echo esc_html( $truncated_title ); ?></h4>
        </a>
        <div class="adf-card-compact__price"><?php echo wp_kses( $price_html, defined( 'ADFOREST_ALLOWED_FORM_HTML' ) ? ADFOREST_ALLOWED_FORM_HTML : array() ); ?></div>
        <?php if ( $truncated_location !== '' ) : ?>
            <div class="adf-card-compact__location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( $truncated_location ); ?></div>
        <?php endif; ?>
    </div>
</div>
