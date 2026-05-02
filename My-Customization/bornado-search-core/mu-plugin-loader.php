<?php
/**
 * Plugin Name: Bornado Search Core Loader
 * Description: MU loader for Bornado Search Core and Bornado SEO Routing plugins.
 * Version: 1.0.0
 * Author: Bornado
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bornado_search_core_plugin   = WP_PLUGIN_DIR . '/bornado-search-core/bornado-search-core.php';
$bornado_routing_plugin       = WP_PLUGIN_DIR . '/bornado-routing/bornado-routing.php';
$bornado_ad_permalinks_plugin = WP_PLUGIN_DIR . '/bornado-ad-permalinks/bornado-ad-permalinks.php';

if ( file_exists( $bornado_search_core_plugin ) ) {
	require_once $bornado_search_core_plugin;
}

if ( file_exists( $bornado_routing_plugin ) ) {
	require_once $bornado_routing_plugin;
}

if ( file_exists( $bornado_ad_permalinks_plugin ) ) {
	require_once $bornado_ad_permalinks_plugin;
}
