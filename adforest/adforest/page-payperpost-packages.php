<?php /* Template Name: Pay Per Post Template */ ?>
<?php
get_header();
global $adforest_theme;
$category_pkg_args = array(
    'post_type' => 'product',
    'fields' => 'ids',
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => 'product_type',
            'field' => 'slug',
            'terms' => 'adforest_pay_per_post_pkgs',
        ),
    ),
    'posts_per_page' => -1,
);

$category_pkg_posts = new WP_Query($category_pkg_args);


if ($category_pkg_posts->have_posts()) {
    while ($category_pkg_posts->have_posts()) {
        $category_pkg_posts->the_post();
        $post_id = get_the_ID();
        $post_title = get_the_title();
        $Featured_expiry_days = get_post_meta($post_id, 'pay_post_ad_expiry_days', true);
        $regular_price = get_post_meta($post_id, '_regular_price', true);
        $sale_price = get_post_meta($post_id, '_sale_price', true);
        $pretty_days = ($Featured_expiry_days == '-1') ? __('Unlimited', 'adforest') : $Featured_expiry_days;
        $ads_for_dayes = $post_title . " For " . $pretty_days . "Days";
    }

    wp_reset_postdata();

}

$search_page = isset($adforest_theme['sb_search_page']) ? get_the_permalink($adforest_theme['sb_search_page']) : "#";
$adt_container_class = "";
if (isset($adforest_theme['sb_header']) && ($adforest_theme['sb_header'] == "white" || $adforest_theme['sb_header'] == "header_w_topbar")) {
    $adt_container_class = "adt-container";
}

$page_id = get_queried_object_id();

$page_header_style = get_post_meta($page_id, '_page_header_style', true);

if (isset($page_header_style) && ($page_header_style == "white" || $page_header_style == "header_w_topbar" || $page_header_style == "vendor-2")) {
    $adt_container_class = "adt-container";
}
?>
    <section class="ad-uploaded-section">
        <div class="container <?php echo esc_attr($adt_container_class); ?>">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 col-xxl-12">
                    <div class="uploaded-ad-box">
                        <div class="left-icon">
                            <img src="<?php echo esc_url(get_template_directory_uri()) . "/images/hand-shake.svg" ?>"
                                 alt="hand-shake">
                        </div>
                        <div class="right-meta">
                            <h4><?php echo esc_html__('Your Ad has been uploaded successfully!', 'adforest'); ?></h4>
                            <p><?php echo esc_html__('Your ad will soon be reachable of ', 'adforest'); ?>
                                <span><?php echo esc_html__('millions', 'adforest'); ?></span><?php echo esc_html__('of buyers', 'adforest'); ?>
                            </p>
                        </div>
                    </div>
                    <form id="pay_per_post_ad_form" class="feature_ad_form">
                        <div class="upgrade-ad-positions">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 col-xxl-8">
                                    <div class="upgrade-position-detail">
                                        <h4><?php echo esc_html__('Pay Directly to Post Your Ad!', 'adforest'); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="featured-ads-box">
                                <div class="top-bar">
                                    <h5><?php echo __('Pay Per Ad', 'adforest') ?></h5>
                                    <a class="adt-button-dark-1"
                                       href="<?php echo esc_url($search_page); ?>"> <?php echo __('See example', 'adforest'); ?> </a>
                                </div>
                                <div class="featured-duration">

                                    <ul class="featured-duration-list">
                                        <?php
                                        if ($category_pkg_posts->have_posts()) {
                                            while ($category_pkg_posts->have_posts()) {
                                                $category_pkg_posts->the_post();
                                                $post_id = get_the_ID();
                                                $post_title = get_the_title();

                                                $Featured_expiry_days = get_post_meta($post_id, 'pay_post_ad_expiry_days', true);
                                                $pretty_days = ($Featured_expiry_days == '-1') ? __('Unlimited', 'adforest') : $Featured_expiry_days;
                                                $ads_for_days = $post_title . ' ' . __('For', 'adforest') . ' ' . $pretty_days . ' ' . __(' Days', 'adforest');
                                                $regular_price = get_post_meta($post_id, '_regular_price', true);
                                                $sale_price = get_post_meta($post_id, '_sale_price', true);
                                                ?>
                                                <li>
                                                    <input class="form-check-input" type="radio" name="package"
                                                           id="package-<?php echo esc_attr($post_id) ?>"
                                                           value="<?php echo get_the_ID(); ?>" required>
                                                    <label for="check-one-ctg">
                                                        <div class="type-box">
                                                            <div class="r-meta">
                                                                <p class="txt"><?php echo esc_html($ads_for_days); ?></p>
                                                                <span><?php if ($sale_price != "") {
                                                                        echo get_woocommerce_currency_symbol() . $sale_price;
                                                                    } else {
                                                                        echo get_woocommerce_currency_symbol() . $regular_price;
                                                                    } ?></span>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="pid" id="pid" value="<?php echo esc_attr($_GET['pid']); ?>">
                        <input type="hidden" id="sb-post-ppp-token"
                               value="<?php echo wp_create_nonce('sb_post_ppp_secure'); ?>"/>
                        <?php $pid = $_GET['pid']; ?>
                        <div class="ad-botm-buttons">
                            <button type="submit"
                                    class="upgrade-btn adt-button-dark"><?php echo esc_html__('Pay for your Ad ', 'adforest'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
<?php
get_footer(); ?>