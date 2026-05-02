<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$event_id = get_the_ID();
$poster_id = get_post_field('post_author', $event_id);
$user_info = get_userdata($poster_id);
$poster_name = $user_info->display_name;
$user_pic = adforest_get_user_dp($poster_id);
$user_address = get_user_meta($poster_id, '_sb_address', true);
$img1 = SB_DIR_URL . '/images/side-vector-1.png';
$img2 = SB_DIR_URL . '/images/side-vector-2.png';

global $adforest_theme;
?>
<div class="heading-detail">
    <div class="detail-img">
        <a href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', '1'); ?>"><img src="<?php echo esc_attr($user_pic); ?>" id="user_dp" alt="<?php echo __('Profile Picture', 'adforest'); ?>" class="img-fluid"></a>

        <?php
        if (get_user_meta($poster_id, '_sb_badge_type', true) != "" && get_user_meta($poster_id, '_sb_badge_text', true) != "" && isset($adforest_theme['sb_enable_user_badge']) && $adforest_theme['sb_enable_user_badge']) {
            ?>
            <div class="heading-detail-check <?php echo get_user_meta($poster_id, '_sb_badge_type', true); ?>">
                <i class="fa fa-check label "></i>
            </div>
            &nbsp; <?php }
        ?>

    </div>                      
    <div class="deatil-head">
        <div class="listing-ratings">
            <?php
            if (isset($adforest_theme['sb_enable_user_ratting']) && $adforest_theme['sb_enable_user_ratting']) {
              
                ?>
              
                    <div class="seller-public-profile-star-icons">
                        <?php
                        $got = get_user_meta($poster_id, "_adforest_rating_avg", true);
                        // $got = count(adforest_get_all_ratings($poster_id));
                        $total = 0;
                        if ($got == "")
                            $got = 0;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= round($got))
                                echo '<i class="fa fa-star"></i>';
                            else
                                echo '<i class="fa fa-star-o"></i>';

                            $total++;
                        }
                        ?>
                        <span class="rating-count count-clr">
                            (<?php
                    $ratings = adforest_get_all_ratings($poster_id);
                    echo $got;
                        ?>)
                        </span>
                    </div>
                
                <?php
            }
            ?>
        </div>
        <h5>
            <?php 
                $user_info = get_userdata($poster_id);
                $poster_name = $user_info->display_name;
            
            ?><?php echo esc_html($poster_name); ?></h5>
                <?php
        $user_type = 'Dealer';
        if (get_user_meta($poster_id, '_sb_user_type', true) == 'Indiviual') {
            $user_type = __('Individual', 'adforest');
        } else if (get_user_meta($poster_id, '_sb_user_type', true) == 'Dealer') {
            $user_type = __('Dealer', 'adforest');
        }
        // if ($user_type == "") {
        ?><span class="label-user label-success user-type"><?php echo adforest_return_echo($user_type); ?></span>
                <?php //}    ?>
   
        <a href="<?php echo adforest_set_url_param(get_author_posts_url($poster_id), 'type', 'ads'); ?>">
            <span class="view-pro"> <?php echo esc_html__('View Profile','sb_pro') ?><i class="fa fa-long-arrow-right" aria-hidden="true"></i></span>
        </a>
    </div>
</div>