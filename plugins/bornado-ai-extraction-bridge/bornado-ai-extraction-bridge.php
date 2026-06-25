<?php
/**
 * Plugin Name: Bornado AI Extraction Bridge
 * Description: Exposes a curated WordPress adapter for the independent Bornado AI Extraction Platform without modifying core theme files.
 * Version: 1.0.0
 * Author: Bornado
 * Text Domain: bornado-ai-extraction-bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BORNADO_AI_EXTRACTION_BRIDGE_FILE')) {
    define('BORNADO_AI_EXTRACTION_BRIDGE_FILE', __FILE__);
}

if (!defined('BORNADO_AI_EXTRACTION_BRIDGE_DIR')) {
    define('BORNADO_AI_EXTRACTION_BRIDGE_DIR', plugin_dir_path(__FILE__));
}

if (file_exists(BORNADO_AI_EXTRACTION_BRIDGE_DIR . 'config/bornado-ai-extraction-bridge-config.php')) {
    require_once BORNADO_AI_EXTRACTION_BRIDGE_DIR . 'config/bornado-ai-extraction-bridge-config.php';
}

require_once BORNADO_AI_EXTRACTION_BRIDGE_DIR . 'includes/class-bornado-ai-extraction-bridge.php';

Bornado_AI_Extraction_Bridge::instance();
