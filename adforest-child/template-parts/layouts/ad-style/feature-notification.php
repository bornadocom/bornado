<?php

if (function_exists('bornado_is_promotion_enabled') && !bornado_is_promotion_enabled('feature')) {
    return;
}

require trailingslashit(get_template_directory()) . 'template-parts/layouts/ad-style/feature-notification.php';
