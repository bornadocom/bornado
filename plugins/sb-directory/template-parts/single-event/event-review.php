<?php
global $adforest_theme;
$pid = get_the_ID();
$poster_id = get_post_field('post_author', $pid);
$section_title = __('Write a Review', 'sb_pro');

wp_enqueue_style('star-rating', trailingslashit(get_template_directory_uri()) . 'assets/css/star-rating.css');
?>

<div id="adt-ad-review-box" class="adt-ad-review-box">
    <?php
    if (function_exists('adforest_comments_pagination2')) {
        $page = (isset($_GET['page-number'])) ? $_GET['page-number'] : 1;
    } else {
        $page = (get_query_var('page')) ? get_query_var('page') : 1;
    }

    $limit = $adforest_theme['sb_rating_max'];
    $offset = ($page * $limit) - $limit;
    $args = array(
        'type__in' => array('event_post_rating'),
        'number' => $limit,
        'offset' => $offset,
        'parent' => 0,
        'post_id' => $pid,
    );
    $comments = get_comments($args);
    ?>
    <div class="review-reply">
        <?php
        if (count($comments) > 0) {
            ?>
            <div class="review-product">
                <h4>
                    <?php echo adforest_return_echo($adforest_theme['sb_ad_rating_title']); ?>
                    <span class="ratings">
                        <?php
                        $get_percentage = adforest_fetch_reviews_average($pid);
                        if (isset($get_percentage) && count($get_percentage['ratings']) > 0) {
                            echo adforest_return_echo($get_percentage['total_stars']) . ' <span class="avg_stars">(' . $get_percentage['average'] . ')</span>';;
                        }
                        ?>
                    </span>
                </h4>
            </div>
            <?php
        }
        ?>
        <ul>
            <?php
            if (count($comments) > 0) {
                foreach ($comments as $comment) {
                    $commenter = get_userdata($comment->user_id);
                    if ($commenter) {
                        $likes = get_comment_meta($comment->comment_ID, 'likes_count', true) ?: 0;
                        $dislikes = get_comment_meta($comment->comment_ID, 'dislikes_count', true) ?: 0;
                        ?>
                        <div class="adt-commnet-main-box">
                            <div class="prf-box">
                                <div class="left-cont">
                                    <div class="img-box">
                                        <img src="<?php echo adforest_get_user_dp($comment->user_id, 'adforest-single-small'); ?>"
                                             alt="user-img">
                                    </div>
                                    <div class="meta-box">
                                        <h6><?php echo esc_attr($commenter->display_name); ?></h6>
                                        <span><?php echo get_comment_date(get_option('date_format'), $comment->comment_ID); ?> &nbsp;|&nbsp; <span
                                                    class="ratings"><?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= get_comment_meta($comment->comment_ID, 'review_stars', true))
                                                        echo '<i class="fa fa-star color" aria-hidden="true"></i>';
                                                    else
                                                        echo '<i class="fa fa-star-o" aria-hidden="true"></i>';
                                                }
                                                ?></span></span>
                                    </div>
                                </div>
                                <div class="right-cont">
                                    <?php
                                    $user_id = get_current_user_id();
                                    $liked = get_comment_meta($comment->comment_ID, 'liked_' . $user_id, true);
                                    $disliked = get_comment_meta($comment->comment_ID, 'disliked_' . $user_id, true);
                                    $likes_count = get_comment_meta($comment->comment_ID, 'likes_count', true) ?: 0;
                                    $dislikes_count = get_comment_meta($comment->comment_ID, 'dislikes_count', true) ?: 0;
                                    $like_class = $liked ? 'fas fa-thumbs-up text-primary' : 'far fa-thumbs-up';
                                    $dislike_class = $disliked ? 'fas fa-thumbs-down text-danger' : 'far fa-thumbs-down';
                                    ?>

                                    <button data-comment_id="<?php echo adforest_return_echo($comment->comment_ID); ?>"
                                            class="dislike-btn">
                                        <i class="<?php echo esc_attr($dislike_class); ?>"></i>
                                        <?php echo esc_html__("Dislike", 'sb_pro'); ?> <span
                                                class="dislike-count">(<?php echo esc_html($dislikes_count); ?>)</span>
                                    </button>

                                    <button data-comment_id="<?php echo adforest_return_echo($comment->comment_ID); ?>"
                                            class="like-btn">
                                        <i class="<?php echo esc_attr($like_class); ?>"></i>
                                        <?php echo esc_html__("Like", 'sb_pro'); ?> <span
                                                class="like-count">(<?php echo esc_html($likes_count); ?>)</span>
                                    </button>
                                </div>


                            </div>
                            <div class="comment-meta-box">
                                <p class="txt"><?php echo esc_html($comment->comment_content); ?></p>
                                <a class="reply-btn reply_event_rating adt-button-dark" href="javascript:void(0);"
                                   data-comment_id="<?php echo adforest_return_echo($comment->comment_ID); ?>"
                                   data-commenter-name="<?php echo esc_attr($commenter->display_name); ?>"
                                   data-bs-toggle="modal"
                                   data-bs-target=".event_reply_rating"><?php echo esc_html__("Reply", 'sb_pro'); ?></a>
                            </div>
                        </div>
                        <div class="review-content">
                            <div class="review-content-item">
                                <?php
                                $args_reply = array(
                                    'type__in' => array('event_post_rating'),
                                    'number' => 1,
                                    'parent' => $comment->comment_ID,
                                    'post_id' => $pid,
                                );
                                $replies = get_comments($args_reply);
                                if (count($replies) > 0) {
                                    foreach ($replies as $reply) {
                                        $ad_author = get_userdata($poster_id);
                                        if ($ad_author) {
                                            ?>
                                            <div class="adt-commnet-main-box reply">
                                                <div class="prf-box">
                                                    <div class="left-cont">
                                                        <div class="img-box">
                                                            <img src="<?php echo adforest_get_user_dp($ad_author->ID, 'adforest-single-small'); ?>"
                                                                 alt="user-img">
                                                        </div>
                                                        <div class="meta-box">
                                                            <h6><?php echo esc_html($ad_author->display_name); ?></h6>
                                                            <span><?php echo get_comment_date(get_option('date_format'), $reply->comment_ID); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="right-cont">
                                                        <?php
                                                        $user_id = get_current_user_id();
                                                        $liked = get_comment_meta($reply->comment_ID, 'liked_' . $user_id, true);
                                                        $disliked = get_comment_meta($reply->comment_ID, 'disliked_' . $user_id, true);
                                                        $likes_count = get_comment_meta($reply->comment_ID, 'likes_count', true) ?: 0;
                                                        $dislikes_count = get_comment_meta($reply->comment_ID, 'dislikes_count', true) ?: 0;
                                                        $like_class = $liked ? 'fas fa-thumbs-up text-primary' : 'far fa-thumbs-up';
                                                        $dislike_class = $disliked ? 'fas fa-thumbs-down text-danger' : 'far fa-thumbs-down';
                                                        ?>
                                                        <button data-comment_id="<?php echo adforest_return_echo($reply->comment_ID); ?>"
                                                                class="dislike-btn">
                                                            <i class="<?php echo esc_attr($dislike_class); ?>"></i>
                                                            <?php echo esc_html__("Dislike", 'sb_pro'); ?> <span
                                                                    class="dislike-count">(<?php echo esc_html($dislikes_count); ?>)</span>
                                                        </button>

                                                        <button data-comment_id="<?php echo adforest_return_echo($reply->comment_ID); ?>"
                                                                class="like-btn">
                                                            <i class="<?php echo esc_attr($like_class); ?>"></i>
                                                            <?php echo esc_html__("Like", 'sb_pro'); ?> <span
                                                                    class="like-count">(<?php echo esc_html($likes_count); ?>)</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="comment-meta-box">
                                                    <p class="txt"><?php echo esc_html($reply->comment_content); ?></p>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                $args_c = array(
                    'type__in' => array('event_post_rating'),
                    'parent' => 0,
                    'post_id' => $pid,
                );
                $total_comments = get_comments($args_c);
                $pages = (ceil(count($total_comments)) / $limit);

                if (function_exists('adforest_comments_pagination2')) {
                    echo adforest_comments_pagination2($pages, $page);
                } else {
                    echo adforest_comments_pagination($pages, $page);
                }
            }
            ?>
        </ul>
    </div>

    <div id="adt-ad-review-box" class="adt-ad-review-box" style="padding-top: 0 !important;">
        <h4><?php echo esc_html__('Write a Review', 'sb_pro'); ?></h4>
        <form method="post" id="event_rating_form">
            <div class="rating-box event-rating-box">
                <div class="form-group">
                    <div dir="ltr">
                        <input type="hidden" id="input-21b" name="rating" value="1" type="text"
                               data-show-clear="false" <?php if (is_rtl()) { ?> dir="rtl" <?php } ?>class="rating"
                               data-min="0" data-max="5" data-step="1" data-size="xs" required title="required">
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="input-field">
                <label><?php echo __('Comments', 'sb_pro'); ?>: <span class="required">*</span></label>
                <textarea cols="6" name="rating_comments" rows="6"
                          placeholder="<?php echo __('Your comments...', 'sb_pro'); ?>" class="form-control re-mdg"
                          data-parsley-required="true"
                          data-parsley-error-message="<?php echo __('This field is required.', 'sb_pro'); ?>"></textarea>
            </div>
            <div class="submit-btn-box">
                <input type="hidden" id="sb-review-token" value="<?php echo wp_create_nonce('sb_review_secure'); ?>"/>
                <button class="adt-button-dark" type="submit"><?php echo __('Submit Review', 'sb_pro'); ?></button>
                <input type="hidden" value="<?php echo adforest_return_echo($pid); ?>" name="ad_id"/>
                <input type="hidden" value="<?php echo adforest_return_echo($poster_id); ?>" name="ad_owner"/>
            </div>
        </form>
    </div>
</div>
  