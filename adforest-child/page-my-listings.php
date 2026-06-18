<?php
/*
 * Template Name: AdForest - My Listings (Modern)
 *
 * Child wrapper around the parent template so promotion controls can be
 * centrally filtered without editing the original theme file.
 */

ob_start();
require trailingslashit(get_template_directory()) . 'page-my-listings.php';
$bornado_my_listings_output = ob_get_clean();

if (function_exists('bornado_filter_my_listings_promotion_markup')) {
    $bornado_my_listings_output = bornado_filter_my_listings_promotion_markup($bornado_my_listings_output);
}

echo $bornado_my_listings_output;
