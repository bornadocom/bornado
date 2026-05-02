<?php
/**
 * Plugin Name: Bornado Ad Permalinks
 * Description: Stable Hash-ID permalinks and canonical redirects for AdForest ad pages.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-ad-permalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BORNADO_AD_PERMALINKS_FILE' ) ) {
	define( 'BORNADO_AD_PERMALINKS_FILE', __FILE__ );
}

if ( ! defined( 'BORNADO_AD_PERMALINKS_DIR' ) ) {
	define( 'BORNADO_AD_PERMALINKS_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BORNADO_AD_PERMALINKS_URL' ) ) {
	define( 'BORNADO_AD_PERMALINKS_URL', plugin_dir_url( __FILE__ ) );
}

require_once BORNADO_AD_PERMALINKS_DIR . 'vendor/Hashids/HashidsInterface.php';
require_once BORNADO_AD_PERMALINKS_DIR . 'vendor/Hashids/MathInterface.php';
require_once BORNADO_AD_PERMALINKS_DIR . 'vendor/Hashids/BCMath.php';
require_once BORNADO_AD_PERMALINKS_DIR . 'vendor/Hashids/Gmp.php';
require_once BORNADO_AD_PERMALINKS_DIR . 'vendor/Hashids/Hashids.php';

require_once BORNADO_AD_PERMALINKS_DIR . 'includes/class-bornado-ad-hash-service.php';
require_once BORNADO_AD_PERMALINKS_DIR . 'includes/class-bornado-ad-permalinks.php';

Bornado_Ad_Permalinks::init();
register_activation_hook( __FILE__, array( 'Bornado_Ad_Permalinks', 'activate' ) );
