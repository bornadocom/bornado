<?php
global $adforest_theme;
get_header();
adforest_custom_breadcrumbs();
$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

if ($type === '1') {
    get_template_part('template-parts/layouts/profile/user', 'rating');
} else {
    get_template_part('template-parts/layouts/profile/profile', 'modern');
}
get_footer();