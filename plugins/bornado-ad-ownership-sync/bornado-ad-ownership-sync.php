<?php
/**
 * Plugin Name: Bornado Ad Ownership Sync
 * Description: Secure ad ownership transfer based on verified phone numbers.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-ad-ownership-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BORNADO_AD_OWNERSHIP_SYNC_VERSION', '1.0.0' );
define( 'BORNADO_AD_OWNERSHIP_SYNC_FILE', __FILE__ );
define( 'BORNADO_AD_OWNERSHIP_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'BORNADO_AD_OWNERSHIP_SYNC_URL', plugin_dir_url( __FILE__ ) );

require_once BORNADO_AD_OWNERSHIP_SYNC_PATH . 'includes/class-bornado-ad-ownership-phone.php';
require_once BORNADO_AD_OWNERSHIP_SYNC_PATH . 'includes/class-bornado-ad-ownership-transfer-service.php';
require_once BORNADO_AD_OWNERSHIP_SYNC_PATH . 'includes/class-bornado-ad-ownership-claim-bridge.php';
require_once BORNADO_AD_OWNERSHIP_SYNC_PATH . 'includes/class-bornado-ad-ownership-report.php';

Bornado_Ad_Ownership_Transfer_Service::init();
Bornado_Ad_Ownership_Claim_Bridge::init();
Bornado_Ad_Ownership_Report::init();

if ( ! function_exists( 'bornado_get_ad_ownership_claim_context' ) ) {
	/**
	 * Public helper for child-theme templates.
	 *
	 * @param int $ad_id Listing ID.
	 * @return array<string,mixed>
	 */
	function bornado_get_ad_ownership_claim_context( $ad_id ) {
		return Bornado_Ad_Ownership_Claim_Bridge::get_claim_context( $ad_id );
	}
}
