<?php
/**
 * Child-theme override of AdForest's profile rating template.
 *
 * We keep a copied version here so the parent theme remains untouched while
 * hiding the "Write Review" UI on a user's own profile page.
 */
global $adforest_theme;
if (isset($adforest_theme['sb_enable_user_ratting']) && !$adforest_theme['sb_enable_user_ratting']) {
    return;
}

wp_enqueue_script('star-rating');
wp_enqueue_style('star-rating', trailingslashit(get_template_directory_uri()) . 'assets/css/star-rating.css');

$author_id = get_query_var('author');
$author = get_user_by('ID', $author_id);
$user_pic = adforest_get_user_dp($author_id, 'adforest-user-profile');
$contact_num = get_user_meta($author->ID, '_sb_contact', true);

/* rating data */
$user_type = isset($_GET['type']) ? $_GET['type'] : '';
$usmeta_id = isset($_GET['umeta_id']) ? $_GET['umeta_id'] : '';

$current_user_id = get_current_user_id();
$is_own_profile = ((int) $author_id === (int) $current_user_id);
$meta_key = "_user_" . $current_user_id;
$comments = "";
$user_rating = get_user_meta($author_id, $meta_key, true);

$rated = 1;
if (isset($_GET['umeta_id'])) {
    $data = explode('_separator_', $user_rating);
    $rated = isset($data[0]) ? $data[0] : "";
    $comments = isset($data[1]) ? $data[1] : "";
    $date = isset($data[2]) ? $data[2] : "";
}

$enable_rating = isset($adforest_theme['sb_enable_user_ratting_public_profile']) ?
    $adforest_theme['sb_enable_user_ratting_public_profile'] : true;
$ratings = adforest_get_all_ratings($author_id);
?>

<section class="profile-page">
    <div class="container">
        <div class="row">
            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
                <?php require trailingslashit(get_template_directory()) . 'template-parts/layouts/profile/profile-header.php'; ?>
            </div>
            <div class="col-xxl-8 col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
                <nav>
                    <div class="nav nav-tabs nav-user-review" id="nav-tab" role="tablist">
                        <?php if (!$is_own_profile) { ?>
                            <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home"
                                    aria-selected="true"><i class="fa fa-pencil"
                                                            aria-hidden="true"></i> <?php echo esc_html__('Write Review', 'adforest') ?>
                            </button>
                        <?php } ?>
                        <button class="nav-link<?php echo $is_own_profile ? ' active' : ''; ?>" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                                type="button" role="tab" aria-controls="nav-profile" aria-selected="<?php echo $is_own_profile ? 'true' : 'false'; ?>">

                            <i class="fa fa-comments"></i> <?php echo esc_html__('Reviews', 'adforest') ?>
                        </button>
                    </div>
                </nav>

                <div class="tab-content" id="nav-tabContent">
                    <?php if (!$is_own_profile) { ?>
                        <div class="tab-pane fade active show" id="nav-home" role="tabpanel"
                             aria-labelledby="nav-home-tab">
                            <div class="write-review">
                                <h3><?php echo esc_html__('Write a review', 'adforest'); ?></h3>
                                <form id="user_rating_form" novalidate>
                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <div dir="ltr">
                                                <input id="input-21b" name="rating" value="<?php echo esc_attr($rated) ?>" type="text"
                                                       data-show-clear="false" <?php if (is_rtl()) { ?> dir="rtl"
                                                       <?php } ?>class="rating" data-min="0" data-max="5" data-step="1"
                                                       data-size="xs" required title="required">
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                    <?php if ($enable_rating) { ?>
                                        <div class="col-md-12 col-sm-12">
                                            <div class="public-link-provider-1">
                                                <div class="form-group">
                                                    <label><?php echo esc_html__('Comments', 'adforest'); ?><span
                                                                class="required">*</span></label>
                                                    <textarea cols="6" rows="6" class="form-control"
                                                              id="sb_rate_user_comments" name="sb_rate_user_comments"
                                                              data-parsley-required="true"
                                                              data-parsley-error-message="<?php echo esc_attr__('This field is required.', 'adforest'); ?>">   <?php echo esc_html($comments); ?> </textarea>
                                                    <?php if (!isset($adforest_theme['sb_rewiew_edit'])) {
                                                        ?>
                                                        <p><?php echo esc_html__('You can not edit it later.', 'adforest'); ?></small></p>

                                                    <?php } else {
                                                        echo '<p>' . esc_html__('Place your comment.', 'adforest') . '</p>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="d-flex justify-content-end">
                                        <input type="hidden" id="sb-user-rating-token"
                                               value="<?php echo wp_create_nonce('sb_user_rating_secure'); ?>"/>
                                        <input style="width: auto" class="btn btn-theme btn-inline"
                                               value="<?php echo esc_attr__('Post Your Comment', 'adforest'); ?>" type="submit">
                                        <input type="hidden" name="author" value="<?php echo esc_attr($author_id); ?>"/>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="tab-pane fade<?php echo $is_own_profile ? ' active show' : ''; ?>" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">

                        <?php if (count($ratings) > 0) {
                            ?>
                            <?php
                            if (is_array($ratings) && count($ratings) > 0) {
                                foreach ($ratings as $rating) {
                                    $data = explode('_separator_', $rating->meta_value);
                                    $rated = $data[0];
                                    $comments = $data[1];
                                    $date = $data[2];
                                    $reply = '';
                                    $reply_date = '';
                                    $umeta_id = $rating->umeta_id;

                                    if (isset($data[3])) {
                                        $reply = $data[3];
                                    }
                                    if (isset($data[4])) {
                                        $reply_date = $data[4];
                                    }
                                    $_arr = explode('_user_', $rating->meta_key);
                                    $rator = $_arr[1];
                                    $user = get_user_by('ID', $rator);
                                    if ($user) {
                                        ?>
                                        <div class="profile-rating">
                                            <div class="pro-response">
                                                <div class="pro-response-img">
                                                    <img src="<?php echo esc_url(adforest_get_user_dp($rator, 'adforest-single-small')); ?>"
                                                         alt="<?php echo esc_attr($user->display_name); ?>">
                                                </div>
                                                <div class="pro-response-head">
                                                    <div class="profile-star-rate">
                                                        <ul class="star-listing">
                                                            (<?php echo esc_html($rated) ?>)
                                                            <?php
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                if ($i <= $rated) {
                                                                    echo '<li><i class="fa fa-star"></i></li>';
                                                                } else {
                                                                    echo '<li><i class="fa fa-star-o"></i></li>';
                                                                }
                                                            }
                                                            ?>
                                                        </ul>
                                                    </div>
                                                    <h3>
                                                        <a href="<?php echo adforest_set_url_param(get_author_posts_url($rator), 'type', 'ads'); ?>"></a><?php echo esc_attr($user->display_name); ?>
                                                    </h3>
                                                    <span><?php echo date_i18n(get_option('date_format'), strtotime($date)); ?></span>

                                                    <?php if ($enable_rating) { ?>
                                                        <p><?php echo esc_html($comments); ?></p>
                                                    <?php } ?>

                                                    <?php if ($author_id == $current_user_id && $reply == "") {
                                                        ?>
                                                        <div class="pro-reply-comment-heading">
                                                            <a href="javascript:void(0);"
                                                               data-rator-id="<?php echo esc_attr($rator); ?>"
                                                               data-rator-name="<?php echo esc_attr($user->display_name); ?>"
                                                               class="clikc_reply" data-bs-target="#rating_reply_modal"
                                                               data-bs-toggle="modal">
                                                                <i class="fa fa-commenting"></i>
                                                                <?php echo esc_html__('Reply', 'adforest'); ?>
                                                            </a>
                                                        </div>
                                                        <?php
                                                    }

                                                    ?>
                                                    <ul>
                                                        <?php

                                                        if ($rator == $current_user_id) {
                                                            global $adforest_theme;
                                                            if (isset($adforest_theme['sb_rewiew_edit']) && $adforest_theme['sb_rewiew_edit']) {
                                                                $sb_post_ad_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_post_ad_page']);
                                                                $ad_update_url = adforest_set_url_param(get_author_posts_url($author_id), 'type', ' 1&&umeta_id') . "=" . $umeta_id;
                                                                ?>
                                                                <li>
                                                                <a href="<?php echo esc_url($ad_update_url); ?>"><?php echo esc_html__('Edit', 'adforest'); ?></a>
                                                                </li><?php
                                                            }
                                                        }
                                                        if ($rator == $current_user_id || is_super_admin($current_user_id)) {
                                                            if (isset($adforest_theme['sb_rewiew_delete']) && $adforest_theme['sb_rewiew_delete']) { ?>
                                                                <li><a href="javascript:void(0)" class="user_rating_dlt"
                                                                       data-userid="<?php echo esc_attr($author_id); ?>"
                                                                       data-confirmation="<?php echo esc_attr__('Are you sure you want to delete this?', 'adforest') ?>"><?php echo esc_html__('Delete', 'adforest'); ?>
                                                                    </a>
                                                                </li>
                                                                <?php
                                                            }
                                                        }
                                                        ?>
                                                    </ul>
                                                </div>
                                            </div>
                                            <?php if ($reply != "") {
                                                $user = get_user_by('ID', $author_id);
                                                ?>
                                                <div class="pro-reply-comment">
                                                    <div class="pro-reply-comment-img">
                                                        <img src="<?php echo adforest_get_user_dp($user->ID, 'adforest-single-small'); ?>"
                                                             alt="<?php echo esc_attr($user->display_name); ?>">
                                                    </div>
                                                    <div class="pro-reply-comment-head">
                                                        <h3><?php echo esc_html($user->display_name); ?></h3>
                                                        <p><?php echo esc_html($reply); ?></p>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>

                                        <?php
                                    }
                                }
                            }
                        } else {
                            echo '<div class="write-review"><h3> ' . esc_html__('There are no reviews yet', 'adforest') . '</h3></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div id="rating_reply_modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header rte">
                <h2 class="modal-title"><?php echo esc_html__('Reply to', 'adforest'); ?>&nbsp;<span id="rator_name"></span>
                </h2>
            </div>
            <form id="sb-reply-rating-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo esc_html__('Comments', 'adforest'); ?> <span class="required">*</span>
                        </label>
                        <textarea class="form-control" rows="8" cols="6" id="sb_rate_user_comments"
                                  name="sb_rate_user_comments" data-parsley-required="true"
                                  data-parsley-error-message="<?php echo esc_attr__('This field is required.', 'adforest'); ?>"></textarea>
                        <div><small><?php echo esc_html__('You can not edit it later.', 'adforest'); ?></small></div>
                        <button class="btn btn-theme btn-sm" type="submit">
                            <?php echo esc_html__('Post Your Reply', 'adforest'); ?>
                        </button>
                        <input type="hidden" id="rator_reply" name="rator_reply" value="0"/>
                        <input type="hidden" id="sb-user-rate-reply-token"
                               value="<?php echo wp_create_nonce('sb_user_rate_reply_secure'); ?>"/>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
