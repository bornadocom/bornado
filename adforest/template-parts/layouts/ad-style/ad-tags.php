<?php
global $adforest_theme;
$pid = get_the_ID();
$posttags = get_the_terms(get_the_ID(), 'ad_tags');

$flip_it = '';
if (is_rtl()) {
    $flip_it = 'flip';
}
?>

<?php
$count = 0;
$tags = '';

if ($posttags) {
    ?>
    <div class="tags-box">
        <ul>
            <li><i class="fa fa-tags"></i></li>
            <?php foreach ($posttags as $tag) { ?>
                <li><a href="<?php echo esc_url(get_term_link($tag->term_id, 'ad_tags')); ?>"
                       title="<?php echo esc_attr($tag->name); ?>">#<?php echo esc_attr($tag->name); ?></a></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

