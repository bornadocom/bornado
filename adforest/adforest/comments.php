<?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) { ?>
    <p class="no-comments"><?php esc_html_e('Comments are closed.', 'adforest'); ?></p>
<?php } else { ?>
    <div class="comment-box">
        <h3><?php echo esc_html__('Leave your comment', 'adforest'); ?></h3>
        <?php
        $comment_form_args = array(
	        'title_reply'        => '',
	        'title_reply_to'     => esc_html__('Leave a Reply to %s', 'adforest'),
	        'cancel_reply_link'  => esc_html__('Cancel reply', 'adforest'),
	        'label_submit'       => esc_html__('Post Comment', 'adforest'),
	        // Override the default fields with an empty array so they don't output twice.
	        'fields'             => array(),
	        // Include your custom markup for all fields in comment_field.
	        'comment_field'      => '
        <div class="row">
            <div class="col-lg-6">
                <div class="field-box">
                    <label for="author">' . esc_html__('Name', 'adforest') . '</label>
                    <input id="author" name="author" type="text" placeholder="' . esc_html__('Enter Your Name', 'adforest') . '" value="' . esc_attr($commenter['comment_author']) . '" required>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="field-box">
                    <label for="email">' . esc_html__('Email', 'adforest') . '</label>
                    <input id="email" name="email" type="email" placeholder="' . esc_html__('Enter Your Email', 'adforest') . '" value="' . esc_attr($commenter['comment_author_email']) . '" required>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="field-box">
                    <label for="url">' . esc_html__('Website', 'adforest') . '</label>
                    <input id="url" name="url" type="url" placeholder="' . esc_html__('Enter Your Website', 'adforest') . '" value="' . esc_attr($commenter['comment_author_url']) . '">
                </div>
            </div>
            <div class="col-lg-12">
                <div class="field-box">
                    <label for="comment">' . esc_html__('Comment', 'adforest') . '</label>
                    <textarea id="comment" name="comment" placeholder="' . esc_html__('Enter Your Message', 'adforest') . '" required></textarea>
                </div>
            </div>
        </div>',
	        'submit_button'      => '
        <div class="field-box submit-btn-box" style="margin-top:-70px">
            <button class="adt-theme-button-2" type="submit">' . esc_html__('Post Comment', 'adforest') . '</button>
        </div>',
	        'comment_notes_before' => '',
	        'comment_notes_after'  => '',
        );
        comment_form($comment_form_args);
        ?>
    </div>
<?php } ?>

<?php
if (post_password_required()) {
    return;
}

if (have_comments()) { ?>
    <div class="comment-box">
        <h3><?php echo esc_html__('Comments', 'adforest'); ?> (<?php echo get_comments_number(); ?>)</h3>
        <hr>
        <ol class="comment-list">
            <?php
            wp_list_comments(
                array(
                    'style' => 'ul',
                    'short_ping' => true,
                    'callback' => 'adforest_comments_list',
                )
            );
            ?>
        </ol>
    </div>
<?php }