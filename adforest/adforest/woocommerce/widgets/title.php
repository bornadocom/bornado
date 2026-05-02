<?php
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Categories', 'adforest');
$title_val = isset($_GET['title']) ? sanitize_text_field($_GET['title']) : "";
// Get current query parameters and sanitize them
$current_query = array_map('sanitize_text_field', $_GET);
?>
<div class="adt-search-list-box">
    <h3><?php echo esc_html($title); ?></h3>
    <form action="" method="get">
        <div class="form-field">
            <input type="text" class="form-control" name="title" id="search"
                   value="<?php echo esc_html($title_val) ?>"
                   placeholder="<?php echo esc_attr__("Search now", "adforest"); ?>">
            <button class="search-btn"><i class="fas fa-search"></i></button>
        </div>
        <?php foreach ($current_query as $key => $value): ?>
            <?php if ($key != 'title'): ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    </form>
</div>