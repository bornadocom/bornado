<?php
/**
 * Child override for AdForest Search 2.0 list cards.
 *
 * Keeps parent markup for the default list layout, while moving the custom
 * "time + city" meta under the price when Bornado style 3 is active.
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
    $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 28, '...' );
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

if ( count( $category_links ) > 1 ) {
    $category_links = array( end( $category_links ) );
}

$is_verified = false;
if ( function_exists( 'adforest_is_verified_user' ) ) {
    $is_verified = (bool) adforest_is_verified_user( get_post_field( 'post_author', $post_id ) );
} elseif ( function_exists( 'adforest_user_is_verified' ) ) {
    $is_verified = (bool) adforest_user_is_verified( get_post_field( 'post_author', $post_id ) );
}

$bornado_style3_active         = function_exists( 'mcew_is_style3_enabled' ) && mcew_is_style3_enabled();
$bornado_posted_location_text  = function_exists( 'bornado_get_search_card_posted_location_text' ) ? bornado_get_search_card_posted_location_text( $post_id ) : '';
$bornado_meta_has_top_content  = ( $truncated_location !== '' || $is_verified );
$bornado_root_classes          = array( 'adf-card', 'adf-card-list' );
$bornado_first_image_id        = isset( $first_image_id ) ? (int) $first_image_id : 0;
$bornado_image_size            = isset( $image_size ) && is_string( $image_size ) && $image_size !== '' ? $image_size : 'adforest-ad-list';
$bornado_fallback_width        = isset( $img_width ) && (int) $img_width > 0 ? (int) $img_width : 350;
$bornado_fallback_height       = isset( $img_height ) && (int) $img_height > 0 ? (int) $img_height : 220;
$bornado_image_sizes           = $bornado_style3_active ? '(max-width: 767px) 108px, 220px' : '(max-width: 991px) 100vw, 280px';
$bornado_is_lcp_candidate      = false;

if ( function_exists( 'bornado_is_ad_search_view' ) && bornado_is_ad_search_view() ) {
	// Claim the LCP slot only once per request so other result cards stay lazy.
	if ( empty( $GLOBALS['bornado_search_lcp_image_claimed'] ) ) {
		$GLOBALS['bornado_search_lcp_image_claimed'] = true;
		$bornado_is_lcp_candidate                    = true;
	}
}

$bornado_image_attrs = array(
	'class'    => 'adf-card-list__img',
	'alt'      => $ad_title,
	'loading'  => $bornado_is_lcp_candidate ? 'eager' : 'lazy',
	'decoding' => 'async',
	'sizes'    => $bornado_image_sizes,
);

if ( $bornado_is_lcp_candidate ) {
	$bornado_image_attrs['fetchpriority'] = 'high';
}

$bornado_image_markup = '';
if ( $bornado_first_image_id > 0 ) {
	$bornado_image_markup = wp_get_attachment_image( $bornado_first_image_id, $bornado_image_size, false, $bornado_image_attrs );
}

if ( '' === $bornado_image_markup ) {
	$bornado_image_markup = sprintf(
		'<img class="%1$s" src="%2$s" alt="%3$s" loading="%4$s" decoding="async"%5$s sizes="%6$s" width="%7$d" height="%8$d">',
		esc_attr( $bornado_image_attrs['class'] ),
		esc_url( $first_img ),
		esc_attr( $ad_title ),
		esc_attr( $bornado_image_attrs['loading'] ),
		$bornado_is_lcp_candidate ? ' fetchpriority="high"' : '',
		esc_attr( $bornado_image_sizes ),
		(int) $bornado_fallback_width,
		(int) $bornado_fallback_height
	);
}

if ( $is_featured ) {
    $bornado_root_classes[] = 'adf-card-list--featured';
}

if ( $bornado_style3_active ) {
    $bornado_root_classes[] = 'mcew-style3-search20-card';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $bornado_root_classes ) ); ?>" data-post-id="<?php echo (int) $post_id; ?>">
    <div class="adf-card-list__media">
        <a href="<?php echo esc_url( $ad_permalink ); ?>" aria-label="<?php echo esc_attr( $ad_title ); ?>">
            <?php echo $bornado_image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </a>
        <?php if ( $is_featured || $is_urgent ) : ?>
            <div class="adf-card-list__badges">
                <?php if ( $is_featured ) : ?>
                    <span class="adf-badge adf-badge--featured"><?php esc_html_e( 'Featured', 'adforest' ); ?></span>
                <?php endif; ?>
                <?php if ( $is_urgent ) : ?>
                    <span class="adf-badge adf-badge--urgent"><?php esc_html_e( 'Urgent', 'adforest' ); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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

        <?php if ( ! $bornado_style3_active ) : ?>
            <div class="adf-card-list__meta">
                <?php if ( $truncated_location !== '' ) : ?>
                    <span class="adf-card-list__meta-item"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( $truncated_location ); ?></span>
                <?php endif; ?>
                <?php if ( function_exists( 'adforest_get_ad_posted_date' ) ) : ?>
                    <span class="adf-card-list__meta-item"><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( adforest_get_ad_posted_date( $posted_time ) ); ?></span>
                <?php endif; ?>
                <?php if ( $is_verified ) : ?>
                    <span class="adf-card-list__meta-item adf-card-list__meta-item--verified"><i class="fas fa-check-circle" aria-hidden="true"></i> <?php esc_html_e( 'Verified', 'adforest' ); ?></span>
                <?php endif; ?>
            </div>
        <?php elseif ( $bornado_meta_has_top_content ) : ?>
            <div class="adf-card-list__meta">
                <?php if ( $truncated_location !== '' ) : ?>
                    <span class="adf-card-list__meta-item"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo esc_html( $truncated_location ); ?></span>
                <?php endif; ?>
                <?php if ( $is_verified ) : ?>
                    <span class="adf-card-list__meta-item adf-card-list__meta-item--verified"><i class="fas fa-check-circle" aria-hidden="true"></i> <?php esc_html_e( 'Verified', 'adforest' ); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="adf-card-list__footer">
            <?php if ( $bornado_style3_active ) : ?>
                <div class="bornado-card-list__price-stack">
                    <div class="adf-card-list__price"><?php echo wp_kses( $price_html, defined( 'ADFOREST_ALLOWED_FORM_HTML' ) ? ADFOREST_ALLOWED_FORM_HTML : array() ); ?></div>
                    <?php if ( $bornado_posted_location_text !== '' ) : ?>
                        <div class="mcew-style3-posted bornado-card-list__posted-location">
                            <i class="far fa-clock" aria-hidden="true"></i>
                            <span class="mcew-style3-posted__text"><?php echo esc_html( $bornado_posted_location_text ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="adf-card-list__price"><?php echo wp_kses( $price_html, defined( 'ADFOREST_ALLOWED_FORM_HTML' ) ? ADFOREST_ALLOWED_FORM_HTML : array() ); ?></div>
            <?php endif; ?>
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
