<?php
declare(strict_types=1);

$baseDir    = dirname(__DIR__);
$storageDir = $baseDir . DIRECTORY_SEPARATOR . 'storage';

$isList = static function (array $value): bool {
    if (function_exists('array_is_list')) {
        return array_is_list($value);
    }

    return array_keys($value) === range(0, count($value) - 1);
};

$mergeConfig = static function (array $base, array $override) use (&$mergeConfig, $isList) {
    foreach ($override as $key => $value) {
        if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
            if ($isList($base[$key]) || $isList($value)) {
                $base[$key] = $value;
                continue;
            }

            $base[$key] = $mergeConfig($base[$key], $value);
            continue;
        }

        $base[$key] = $value;
    }

    return $base;
};

$sharedSecret = trim((string) (getenv('BORNADO_AI_SHARED_SECRET') ?: ''));
$opsKey       = trim((string) (getenv('BORNADO_AI_OPS_KEY') ?: ''));
$serviceUrl   = trim((string) (getenv('BORNADO_AI_SERVICE_URL') ?: 'http://localhost:8086'));
$wpBaseUrl    = trim((string) (getenv('BORNADO_AI_WORDPRESS_BASE_URL') ?: ''));
$wpUser       = trim((string) (getenv('BORNADO_AI_WORDPRESS_USERNAME') ?: ''));
$wpPassword   = trim((string) (getenv('BORNADO_AI_WORDPRESS_APP_PASSWORD') ?: ''));

$config = array(
    'service' => array(
        'name'           => 'bornado-ai-extraction-platform',
        'source_system'  => getenv('BORNADO_AI_SOURCE_SYSTEM') ?: 'bornado-wordpress',
        'base_url'       => $serviceUrl,
        'shared_secret'  => $sharedSecret,
        'ops_key'        => '' !== $opsKey ? $opsKey : $sharedSecret,
        'default_locale' => getenv('BORNADO_AI_DEFAULT_LOCALE') ?: 'fa-IR',
    ),
    'security' => array(
        'require_auth_for_schema' => true,
        'require_auth_for_prompt' => true,
        'require_auth_for_resolve' => true,
    ),
    'logging' => array(
        'state_dir'        => $storageDir . DIRECTORY_SEPARATOR . 'state',
        'schema_cache_dir' => $storageDir . DIRECTORY_SEPARATOR . 'schema-cache',
    ),
    'source' => array(
        'mode' => getenv('BORNADO_AI_SOURCE_MODE') ?: 'wordpress-rest',
        'wordpress' => array(
            'base_url'              => $wpBaseUrl,
            'username'              => $wpUser,
            'application_password'  => $wpPassword,
            'timeout_seconds'       => (int) (getenv('BORNADO_AI_WORDPRESS_TIMEOUT') ?: 12),
            'page_size'             => (int) (getenv('BORNADO_AI_WORDPRESS_PAGE_SIZE') ?: 100),
            'catalog_endpoint'      => trim((string) (getenv('BORNADO_AI_WORDPRESS_CATALOG_ENDPOINT') ?: '')),
            'service_key'           => trim((string) (getenv('BORNADO_AI_WORDPRESS_SERVICE_KEY') ?: ($opsKey ?: $sharedSecret))),
            'taxonomies'            => array('ad_cats', 'ad_country', 'ad_type', 'ad_condition', 'ad_warranty'),
            'country_code_meta_key' => '_bornado_country_code',
        ),
    ),
    'target' => array(
        'wordpress_bridge' => array(
            'ingest_endpoint' => trim((string) (getenv('BORNADO_AI_WORDPRESS_INGEST_ENDPOINT') ?: '')),
            'service_key' => trim((string) (getenv('BORNADO_AI_WORDPRESS_SERVICE_KEY') ?: ($opsKey ?: $sharedSecret))),
            'timeout_seconds' => (int) (getenv('BORNADO_AI_WORDPRESS_TIMEOUT') ?: 12),
        ),
    ),
    'prompt' => array(
        'core_template_path' => $baseDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'core-prompt.md',
    ),
    'markets' => array(
        'uk' => array(
            'label' => 'United Kingdom',
            'country_key' => 'gb',
            'country_code' => 'GB',
            'preferred_currency_keys' => array('gbp'),
            'channel_defaults' => array(
                'instagram' => array(
                    'platform_label_fa' => 'اینستاگرام',
                ),
            ),
            'location_aliases' => array(
                'london' => array('wembley', 'croydon', 'ealing', 'harrow'),
                'manchester' => array('salford', 'stockport', 'bolton'),
                'birmingham' => array('solihull'),
                'liverpool' => array('bootle'),
                'leeds' => array('bradford'),
                'newcastle' => array('gateshead'),
            ),
        ),
    ),
);

$localOverrideFile = __DIR__ . DIRECTORY_SEPARATOR . 'ai-extraction-platform.local.php';
if (is_file($localOverrideFile)) {
    $override = require $localOverrideFile;
    if (is_array($override)) {
        $config = $mergeConfig($config, $override);
    }
}

return $config;
