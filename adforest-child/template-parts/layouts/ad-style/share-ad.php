<?php
global $adforest_theme;
$pid = get_the_ID();

if (!empty($adforest_theme['share_ads_on'])) {
    $flip_it = is_rtl() ? 'text-right' : 'text-left';
    $share_permalink = get_the_permalink($pid);
    $share_permalink_text = function_exists('bornado_get_readable_permalink')
        ? bornado_get_readable_permalink($pid)
        : $share_permalink;
    ?>
    <div class="modal fade share-ad" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content <?php echo esc_attr($flip_it); ?>">
                <div class="modal-header">
                    <h3 class="modal-title"><?php echo esc_html__('Share', 'adforest'); ?></h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo esc_attr__('Close', 'adforest'); ?>"></button>
                </div>
                <div class="modal-body <?php echo esc_attr($flip_it); ?>">
                    <div class="recent-ads">
                        <div class="recent-ads-list">
                            <div class="recent-ads-container">
                                <div class="recent-ads-list-image">
                                    <?php
                                    $media = adforest_get_ad_images($pid);
                                    $img = adforest_get_ad_default_image_url('adforest-ad-related');
                                    if (count($media) > 0) {
                                        foreach ($media as $m) {
                                            $mid = isset($m->ID) ? $m->ID : $m;
                                            $image = wp_get_attachment_image_src($mid, 'adforest-ad-related');
                                            $img = $image[0];
                                            break;
                                        }
                                        ?>
                                        <a href="<?php echo esc_url($share_permalink); ?>" class="recent-ads-list-image-inner"><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"></a>
                                    <?php } ?>
                                </div>
                                <div class="recent-ads-list-content">
                                    <h3 class="recent-ads-list-title"><a href="<?php echo esc_url($share_permalink); ?>"><?php the_title(); ?></a></h3>
                                    <div class="recent-ads-list-price"><?php echo adforest_adPrice($pid); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                    <p><?php echo esc_html(adforest_words_count(get_the_excerpt(get_the_ID()), 250)); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="share-link"><?php echo esc_html__('Link', 'adforest'); ?></div>
                    <p><a href="<?php echo esc_url($share_permalink); ?>"><?php echo esc_html($share_permalink_text); ?></a></p>
                </div>
                <div class="modal-footer">
                    <?php echo adforest_social_share(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
