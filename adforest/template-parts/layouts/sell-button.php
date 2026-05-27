<?php
global $adforest_theme;

if (isset($adforest_theme['sell_button']) && $adforest_theme['sell_button']) {
    $sell = '';
    if (isset($adforest_theme['sticky_title']) && $adforest_theme['sticky_title']) {
        $sell = $adforest_theme['sticky_title'];
    }
    $icon = 'flaticon-transport-9';
    if (isset($adforest_theme['sticky_icon']) && $adforest_theme['sticky_icon']) {
        $icon = $adforest_theme['sticky_icon'];
    }
    $sb_post_ad_page = apply_filters('adforest_ad_post_verified_id', $adforest_theme['sb_post_ad_page']); // phone verification redirection
    $sb_post_ad_page = apply_filters('adforest_language_page_id', $sb_post_ad_page);
    if (is_page() && get_the_ID() == $sb_post_ad_page)
        return;

    $sb_ad_post_url = isset($sb_post_ad_page) && !empty($sb_post_ad_page) ? apply_filters('adforest_ad_post_verified_link', get_the_permalink($sb_post_ad_page)) : home_url('/');
    ?>
    <a href="<?php echo esc_url($sb_ad_post_url); ?>" class="sticky-post-button sticky-post-button-hidden hidden-xs">
        <span class="sell-icons"><i class="<?php echo esc_attr($icon); ?>"></i></span>
        <h4><?php echo esc_html($sell); ?></h4>
    </a>
<?php } ?>