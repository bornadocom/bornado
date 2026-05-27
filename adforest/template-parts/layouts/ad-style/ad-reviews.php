<?php
global $adforest_theme;
$pid = get_the_ID();
$poster_id = get_post_field('post_author', $pid);
$section_title = __('Write a Review', 'adforest');
$sb_ad_rating_start = isset($adforest_theme['sb_ad_rating_start']) ? $adforest_theme['sb_ad_rating_start'] : true;
$review_enable_gallery = isset($adforest_theme['dwt_listing_review_enable_gallery']) ? $adforest_theme['dwt_listing_review_enable_gallery'] : true;
if (isset($adforest_theme['sb_ad_rating_title']) && $adforest_theme['sb_ad_rating_title'] != "") {
    $section_title = $adforest_theme['sb_ad_rating_title'];
}
if (get_post_meta($pid, '_adforest_ad_status_', true) == 'active' && isset($adforest_theme['sb_ad_rating']) && $adforest_theme['sb_ad_rating']) {
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
            'type__in' => array('ad_post_rating'),
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
                    <h4 class="main-title text-left">
                        <?php echo adforest_return_echo($adforest_theme['sb_ad_rating_title']); ?>
                        <?php
                        if ($sb_ad_rating_start) { ?>
                            <span class="ratings">
                                <?php
                                $get_percentage = adforest_fetch_reviews_average($pid);
                                if (isset($get_percentage) && count($get_percentage['ratings']) > 0) {
                                    echo adforest_return_echo($get_percentage['total_stars']) . ' <span class="avg_stars">(' . $get_percentage['average'] . ')</span>';;
                                }
                                ?>
                            </span>
                        <?php } ?>
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
                            $image_id = get_comment_meta($comment->comment_ID, "review_images_attachmentId", true);
                            $full_img = wp_get_attachment_image_src($image_id, 'full');
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
                                                        if ($i <= get_comment_meta($comment->comment_ID, 'review_stars', true)) {
                                                            echo '<i class="fa fa-star color" aria-hidden="true"></i>';
                                                        } else {
                                                            echo '<i class="fa fa-star-o" aria-hidden="true"></i>';
                                                        }
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
                                            <?php echo esc_html__("Dislike", 'adforest'); ?> <span
                                                    class="dislike-count">(<?php echo esc_html($dislikes_count); ?>)</span>
                                        </button>

                                        <button data-comment_id="<?php echo adforest_return_echo($comment->comment_ID); ?>"
                                                class="like-btn">
                                            <i class="<?php echo esc_attr($like_class); ?>"></i>
                                            <?php echo esc_html__("Like", 'adforest'); ?> <span
                                                    class="like-count">(<?php echo esc_html($likes_count); ?>)</span>
                                        </button>
                                        <?php
                                        if ($comment->user_id == get_current_user_id() || is_super_admin(get_current_user_id())) {
                                            if (isset($adforest_theme['ads_rewiew_delete']) && $adforest_theme['ads_rewiew_delete']) {
                                                ?>
                                                <li style="padding:10px">
                                                    <a href="javascript:void(0)" class="ads_rating_dlt"
                                                       data-comment_id="<?php echo esc_attr($comment->comment_ID); ?>"
                                                       data-ad_id="<?php echo esc_attr($pid); ?>">
                                                        <i class="fa fa-trash text-danger"></i>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>


                                </div>
                                <div class="comment-meta-box">
                                    <div class="d-flex flex-column gap-2">
                                        <?php
                                        if (isset($full_img[0])) {
                                            ?>
                                            <a href="<?php echo esc_url($full_img[0]); ?>"
                                               data-fancybox="images-preview-<?php echo '' . $comment->comment_ID; ?>">
                                                <img style="width: 200px"
                                                     src="<?php echo esc_url($full_img[0]); ?>" class="img-responsive"
                                                     alt="imagess"/><?php ?></a>
                                        <?php } ?>
                                        <p class="txt w-100"><?php echo esc_html($comment->comment_content); ?></p>
                                    </div>
                                    <a class="reply-btn reply_ad_rating adt-button-dark" href="javascript:void(0);"
                                       data-comment_id="<?php echo adforest_return_echo($comment->comment_ID); ?>"
                                       data-commenter-name="<?php echo esc_attr($commenter->display_name); ?>"
                                       data-bs-toggle="modal"
                                       data-bs-target=".reply_rating"><?php echo esc_html__("Reply", 'adforest'); ?></a>
                                </div>
                            </div>
                            <div class="review-content">
                                <div class="review-content-item">
                                    <?php
                                    $args_reply = array(
                                        'type__in' => array('ad_post_rating'),
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
                                                                <?php echo esc_html__("Dislike", 'adforest'); ?> <span
                                                                        class="dislike-count">(<?php echo esc_html($dislikes_count); ?>)</span>
                                                            </button>

                                                            <button data-comment_id="<?php echo adforest_return_echo($reply->comment_ID); ?>"
                                                                    class="like-btn">
                                                                <i class="<?php echo esc_attr($like_class); ?>"></i>
                                                                <?php echo esc_html__("Like", 'adforest'); ?> <span
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
                        <?php }
                    }
                } ?>
            </ul>
        </div>

        <?php
        if (get_post_meta($pid, '_adforest_ad_status_', true) == 'active') {
            ?>
            <h4><?php echo esc_html__('Write a Review', 'adforest'); ?> </h4>
            <form method="post" id="ad_rating_form" enctype="multipart/form-data">
                <?php
                if ($sb_ad_rating_start) {
                    ?>
                    <div class="col-md-12 col-sm-12 margin-btm-15">
                        <div class="form-group">
                            <div dir="ltr">
                                <input id="input-21b" name="rating" value="1" type="hidden"
                                       data-show-clear="false" <?php if (is_rtl()) { ?> dir="rtl"
                                       <?php } ?>class="rating" data-min="0" data-max="5" data-step="1"
                                       data-size="xs" required title="required">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                <?php } ?>
                <?php
                if ($review_enable_gallery == '1') {
                    $media_required = "";
                    if (isset($adforest_theme['adforest_review_gallery_required']) && $adforest_theme['adforest_review_gallery_required'] == 1) {
                        $media_required = "required";
                    }
                    ?>
                    <div class="col-md-12 col-sm-12 margin-btm-15">
                        <div class="form-group">
                            <label><?php echo esc_html__('Review Gallery:', 'adforest'); ?> </label>
                            <div class="upload-file-box">
                                <input type="file" class="filepond" id="files" name="files" accept=".jpeg,.png,.jpg"
                                       <?php echo esc_attr($media_required); ?>>
                                <span class="upload-btn adt-button-dark"><?php echo esc_html__("Select File", "adforest") ?></span>
                                <span class="txt"><?php echo esc_html__("Select File", "adforest") ?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="col-md-12 col-sm-12 margin-btm-15">
                    <div class="form-group">
                        <label><?php echo esc_html__('Comments', 'adforest'); ?>: <span class="required">*</span></label>
                        <textarea cols="6" name="rating_comments" rows="6"
                                  placeholder="<?php echo esc_attr__('Your comments...', 'adforest'); ?>"
                                  class="form-control re-mdg"
                                  data-parsley-required="true"
                                  data-parsley-error-message="<?php echo esc_attr__('This field is required.', 'adforest'); ?>"></textarea>
                    </div>
                </div>
                <div class="col-md-12 col-sm-12 margin-btm-15">
                    <input type="hidden" id="sb-review-token"
                           value="<?php echo wp_create_nonce('sb_review_secure'); ?>"/>
                    <button class="adt-button-dark"
                            type="submit"><?php echo esc_html__('Submit Review', 'adforest'); ?></button>
                    <input type="hidden" value="<?php echo adforest_return_echo($pid); ?>" name="ad_id"/>
                    <input type="hidden" value="<?php echo adforest_return_echo($poster_id); ?>" name="ad_owner"/>
                </div>
            </form>
            <?php
            $flip_it = 'text-left';
            if (is_rtl()) {
                $flip_it = 'text-right';
            }
            ?>
            <div class="modal fade reply_rating" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <form id="rating_reply_form">
                        <div class="modal-content <?php echo esc_attr($flip_it); ?>">
                            <div class="modal-header">
                                <button class="btn" type="button" class="close" data-bs-dismiss="modal"><span
                                            aria-hidden="true">&#10005;</span><span class="sr-only"></span></button>
                                <div class="modal-title"><?php echo esc_html__('Reply to', 'adforest'); ?> <span
                                            id="reply_to_rating"></span></div>
                            </div>
                            <div class="modal-body <?php echo esc_attr($flip_it); ?>">
                                <div class="form-group  col-md-12 col-sm-12">
                                    <textarea placeholder="<?php echo esc_attr__('Write your reply...', 'adforest'); ?>"
                                              rows="3"
                                              class="form-control" name="reply_comments" data-parsley-required="true"
                                              data-parsley-error-message="<?php echo esc_attr__('This field is required.', 'adforest'); ?>"></textarea>
                                </div>
                                <div class="clearfix"></div>
                                <div class="col-md-12 col-sm-12 margin-bottom-20 m-t-2">
                                    <input type="hidden" id="sb-review-reply-token"
                                           value="<?php echo wp_create_nonce('sb_review_reply_secure'); ?>"/>
                                    <input type="hidden" id="parent_comment_id" value="0" name="parent_comment_id"/>
                                    <input type="hidden" value="<?php echo adforest_return_echo($pid); ?>"
                                           name="ad_id"/>
                                    <input type="hidden" value="<?php echo adforest_return_echo($poster_id); ?>"
                                           name="ad_owner"/>
                                    <input type="submit" class="adt-button-dark"
                                           value="<?php echo esc_attr__('Submit', 'adforest'); ?>"/>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php } ?>
    </div>
<?php }