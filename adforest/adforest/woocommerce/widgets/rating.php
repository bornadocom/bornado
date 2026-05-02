<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Search', 'adforest');

// Get the current rating filter from the query parameter and sanitize
$current_rating = isset($_GET['rating']) ? absint($_GET['rating']) : '';

// Define possible ratings (5 to 1 stars)
$ratings_options = array(5, 4, 3, 2, 1);
?>

<div class="adt-search-rating-box adt-search-rating-box-wc">
    <h3><?php echo esc_html($title); ?></h3>
    <form action="" method="get">
        <ul class="adt-type-filter-box">
            <?php foreach ($ratings_options as $rating): ?>
                <li>
                    <label class="container">
                        <div class="rating">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star <?php echo esc_attr( $i < $rating ? 'fill' : '' ); ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="radio" name="rating" value="<?php echo esc_attr($rating); ?>"
                            <?php echo esc_attr( $current_rating == $rating ? 'checked' : '' ); ?>
                               onchange="this.form.submit();" />
                        <span class="checkmark"></span>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
        <!-- Preserve other existing query parameters -->
        <?php foreach ($_GET as $key => $value): ?>
            <?php if ($key != 'rating'): ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(sanitize_text_field($value)); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    </form>
</div>