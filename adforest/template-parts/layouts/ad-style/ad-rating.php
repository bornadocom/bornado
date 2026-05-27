<?php
global $adforest_theme;
$pid = get_the_ID();
$poster_id = get_post_field('post_author', $pid);
$section_title = esc_html__('Write a Review', 'adforest');
$sb_ad_rating_start = $adforest_theme['sb_ad_rating_start'] ?? true;
$review_enable_gallery = $adforest_theme['dwt_listing_review_enable_gallery'] ?? true;
if (isset($adforest_theme['sb_ad_rating_title']) && $adforest_theme['sb_ad_rating_title'] != "") {
    $section_title = $adforest_theme['sb_ad_rating_title'];
}
if (get_post_meta($pid, '_adforest_ad_status_', true) == 'active') {
    // grab the current page number and set to 1 if no page number is set
    if (function_exists('adforest_comments_pagination2')) {
        $page = (isset($_GET['page-number'])) ? $_GET['page-number'] : 1;
    } else {
        $page = (get_query_var('page')) ? get_query_var('page') : 1;
    }

    $limit = $adforest_theme['sb_rating_max'];
    $offset = ($page * $limit) - $limit;
    $args = array(
        'type__in' => array('ad_post_rating'),
        'number' => $limit,
        'offset' => $offset,
        'parent' => 0,
        'post_id' => $pid,
    );

    $comments = get_comments($args);
    $get_percentage = adforest_fetch_reviews_average($pid);
    ?>

    <div class="rating test">
        <span><?php echo esc_html(isset($get_percentage['average']) ? $get_percentage['average'] : 0); ?></span>
        <small>
            <?php printf( esc_html__('%d Reviews', 'adforest'), is_array($comments) ? count($comments) : 0 ); ?>
        </small>
        <?php
        if (isset($get_percentage) && count($get_percentage['ratings']) > 0) {
            echo adforest_return_echo($get_percentage['total_stars']);
        } else {
            echo '<i class="far fa-star" aria-hidden="true"></i>
                  <i class="far fa-star" aria-hidden="true"></i>
                  <i class="far fa-star" aria-hidden="true"></i>
                  <i class="far fa-star" aria-hidden="true"></i>
                  <i class="far fa-star" aria-hidden="true"></i>';
        }
        ?>
    </div>
<?php }