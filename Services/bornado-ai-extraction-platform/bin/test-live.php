<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
$configPath = $baseDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-extraction-platform.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Config file not found.\n");
    exit(1);
}

/** @var array<string,mixed> $config */
$config = require $configPath;

$serviceBaseUrl = rtrim((string) ($config['service']['base_url'] ?? ''), '/');
$serviceKey     = trim((string) ($config['service']['ops_key'] ?? $config['service']['shared_secret'] ?? ''));
$sharedSecret   = trim((string) ($config['service']['shared_secret'] ?? ''));
$catalogUrl     = trim((string) ($config['source']['wordpress']['catalog_endpoint'] ?? ''));
$wpUser         = trim((string) ($config['source']['wordpress']['username'] ?? ''));
$wpPass         = trim((string) ($config['source']['wordpress']['application_password'] ?? ''));

if ('' === $serviceBaseUrl) {
    fwrite(STDERR, "Service base URL is missing.\n");
    exit(1);
}

$printSection = static function (string $title): void {
    echo "\n=== " . $title . " ===\n";
};

$isVerbose = in_array('--verbose', $argv ?? array(), true);
$withCatalog = in_array('--with-catalog', $argv ?? array(), true);
$withIngest = in_array('--with-ingest', $argv ?? array(), true);
$stringPreview = static function (string $value, int $limit = 500): string {
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit);
    }

    return substr($value, 0, $limit);
};

$buildServiceUrl = static function (string $baseUrl, string $route, array $query = array()): string {
    $baseUrl = rtrim($baseUrl, '/');
    $query['route'] = '/' . ltrim($route, '/');

    $separator = false === strpos($baseUrl, '?') ? '?' : '&';

    return $baseUrl . $separator . http_build_query($query);
};

/**
 * @param array<string,string> $headers
 * @return array{ok:bool,status:int,body:string,json:mixed,error:string,headers:array<int,string>}
 */
$request = static function (string $method, string $url, array $headers = array(), ?string $body = null): array {
    $headerLines = array();
    foreach ($headers as $name => $value) {
        $headerLines[] = $name . ': ' . $value;
    }

    $context = stream_context_create(
        array(
            'http' => array(
                'method' => $method,
                'header' => implode("\r\n", $headerLines),
                'content' => $body ?? '',
                'timeout' => 20,
                'ignore_errors' => true,
            ),
        )
    );

    $raw = @file_get_contents($url, false, $context);
    $responseHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
    $status = 0;
    if (!empty($responseHeaders[0]) && preg_match('/\s(\d{3})\s/', (string) $responseHeaders[0], $matches)) {
        $status = (int) $matches[1];
    }

    return array(
        'ok' => false !== $raw && $status >= 200 && $status < 300,
        'status' => $status,
        'body' => false === $raw ? '' : $raw,
        'json' => false === $raw ? null : json_decode($raw, true),
        'error' => false === $raw ? 'Request failed.' : '',
        'headers' => $responseHeaders,
    );
};

$summarizeJson = static function (array $json) use ($stringPreview): array {
    $summary = array();

    if (isset($json['message'])) {
        $summary['message'] = $json['message'];
    }

    if (isset($json['error'])) {
        $summary['error'] = $json['error'];
    }

    if (isset($json['status'])) {
        $summary['status'] = $json['status'];
    }

    if (isset($json['service']) && is_array($json['service'])) {
        $summary['service'] = $json['service'];
    }

    if (isset($json['schema_version'])) {
        $summary['schema_version'] = $json['schema_version'];
    }

    if (isset($json['schema_hash'])) {
        $summary['schema_hash'] = $json['schema_hash'];
    }

    if (isset($json['market']) && is_array($json['market'])) {
        $summary['market'] = $json['market'];
    }

    if (isset($json['channel']) && is_array($json['channel'])) {
        $summary['channel'] = $json['channel'];
    }

    if (isset($json['categories']) && is_array($json['categories'])) {
        $summary['categories_count'] = count($json['categories']);
        $summary['categories_sample'] = array_slice($json['categories'], 0, 5);
    }

    if (isset($json['templates']) && is_array($json['templates'])) {
        $summary['templates_count'] = count($json['templates']);
    }

    if (isset($json['locations']) && is_array($json['locations'])) {
        $summary['locations_count'] = count($json['locations']);
        $summary['locations_sample'] = array_slice($json['locations'], 0, 5);
    }

    if (isset($json['enums']) && is_array($json['enums'])) {
        $summary['enum_counts'] = array_map(
            static function ($items): int {
                return is_array($items) ? count($items) : 0;
            },
            $json['enums']
        );
    }

    if (isset($json['dynamic_schema']) && is_array($json['dynamic_schema'])) {
        $summary['dynamic_schema_summary'] = array(
            'stage' => $json['dynamic_schema']['stage'] ?? null,
            'schema_version' => $json['dynamic_schema']['schema_version'] ?? null,
            'allowed_categories_count' => isset($json['dynamic_schema']['allowed_categories']) && is_array($json['dynamic_schema']['allowed_categories']) ? count($json['dynamic_schema']['allowed_categories']) : 0,
            'allowed_locations_count' => isset($json['dynamic_schema']['allowed_locations']) && is_array($json['dynamic_schema']['allowed_locations']) ? count($json['dynamic_schema']['allowed_locations']) : 0,
            'category_schema_key' => $json['dynamic_schema']['category_schema']['key'] ?? null,
            'category_field_count' => isset($json['dynamic_schema']['category_schema']['fields']) && is_array($json['dynamic_schema']['category_schema']['fields']) ? count($json['dynamic_schema']['category_schema']['fields']) : 0,
        );
    }

    if (isset($json['composed_prompt'])) {
        $summary['composed_prompt_length'] = strlen((string) $json['composed_prompt']);
        $summary['composed_prompt_preview'] = $stringPreview((string) $json['composed_prompt'], 500) . '...';
    }

    if (isset($json['resolution_status'])) {
        $summary['resolution_status'] = $json['resolution_status'];
    }

    if (isset($json['errors'])) {
        $summary['errors'] = $json['errors'];
    }

    if (isset($json['trace'])) {
        $summary['trace'] = $json['trace'];
    }

    if (isset($json['target_payload']) && is_array($json['target_payload'])) {
        $summary['target_payload_keys'] = array_keys($json['target_payload']);
        $summary['wordpress_bridge_payload'] = $json['target_payload']['wordpress_bridge'] ?? null;
    }

    if (isset($json['publish']) && is_array($json['publish'])) {
        $summary['publish'] = $json['publish'];
    }

    if (empty($summary)) {
        $summary = array(
            'top_level_keys' => array_keys($json),
        );
    }

    return $summary;
};

/**
 * @param array<int,array<string,mixed>> $fields
 * @return array<string,mixed>
 */
$buildDynamicFieldSample = static function (array $fields): array {
    $sample = array();

    foreach ($fields as $field) {
        if (!is_array($field) || empty($field['field_key'])) {
            continue;
        }

        $fieldKey = (string) $field['field_key'];
        $type = (string) ($field['type'] ?? 'text');
        $choices = isset($field['choices']) && is_array($field['choices']) ? $field['choices'] : array();
        $rules = isset($field['rules']) && is_array($field['rules']) ? $field['rules'] : array();

        if ('checkbox' === $type) {
            $firstChoice = $choices[0]['key'] ?? null;
            $sample[$fieldKey] = null !== $firstChoice ? array($firstChoice) : array();
            continue;
        }

        if (in_array($type, array('select', 'radio', 'color', 'taxonomy_select'), true)) {
            $sample[$fieldKey] = $choices[0]['key'] ?? null;
            continue;
        }

        if ('number' === $type) {
            $min = isset($rules['min']) ? (float) $rules['min'] : 1;
            $sample[$fieldKey] = (int) $min > 0 ? (int) $min : $min;
            continue;
        }

        $sample[$fieldKey] = null;
    }

    return $sample;
};

$detectContentType = static function (array $headers): string {
    foreach ($headers as $header) {
        if (0 === stripos((string) $header, 'Content-Type:')) {
            return trim((string) substr((string) $header, strlen('Content-Type:')));
        }
    }

    return '';
};

$printResult = static function (array $result) use ($isVerbose, $summarizeJson, $stringPreview, $detectContentType): void {
    echo 'HTTP Status: ' . (int) ($result['status'] ?? 0) . "\n";
    if (!empty($result['error'])) {
        echo 'Error: ' . (string) $result['error'] . "\n";
    }

    $json = $result['json'] ?? null;
    if (is_array($json)) {
        $payload = $isVerbose ? $json : $summarizeJson($json);
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        return;
    }

    $contentType = $detectContentType((array) ($result['headers'] ?? array()));
    $body = (string) ($result['body'] ?? '');
    if ('' !== $contentType) {
        echo 'Content-Type: ' . $contentType . "\n";
    }

    if (false !== stripos($contentType, 'text/html') || preg_match('/^\s*</', $body)) {
        echo "HTML response detected. Preview only:\n";
        echo $stringPreview($body, 800) . "\n";
        return;
    }

    echo $body . "\n";
};

$printSection('Service Health');
$healthResult = $request('GET', $buildServiceUrl($serviceBaseUrl, '/health'));
$printResult($healthResult);

$printSection('Service Schema');
$schemaResult = $request(
    'GET',
    $buildServiceUrl($serviceBaseUrl, '/schema', array('market' => 'uk', 'channel' => 'instagram')),
    array(
        'X-Bornado-Service-Key' => $serviceKey,
    )
);
$printResult($schemaResult);

if ((int) ($schemaResult['status'] ?? 0) === 403) {
    echo "\nSchema access is forbidden. Check that your service key matches ops_key and, for browser testing, use ?key=YOUR_OPS_KEY.\n";
    echo "Stopping here to avoid noisy downstream output.\n";
    exit(1);
}

$schema = is_array($schemaResult['json'] ?? null) ? $schemaResult['json'] : array();
$categoryKey = '';
$countryKey  = (string) ($schema['market']['country']['key'] ?? 'gb');
$cityKey     = '';
$extractFields = array();

foreach ((array) ($schema['categories'] ?? array()) as $category) {
    if (is_array($category) && empty($category['parent_key']) && !empty($category['key'])) {
        $categoryKey = (string) $category['key'];
        break;
    }
}

foreach ((array) ($schema['locations'] ?? array()) as $location) {
    if (is_array($location) && !empty($location['key'])) {
        $cityKey = (string) $location['key'];
        break;
    }
}

$extractFields = isset($schema['fields']['by_category'][$categoryKey]) && is_array($schema['fields']['by_category'][$categoryKey])
    ? $schema['fields']['by_category'][$categoryKey]
    : array();

$printSection('Prompt Package (Classify)');
$classifyPromptResult = $request(
    'GET',
    $buildServiceUrl($serviceBaseUrl, '/prompt-package', array('market' => 'uk', 'channel' => 'instagram', 'stage' => 'classify')),
    array(
        'X-Bornado-Service-Key' => $serviceKey,
    )
);
$printResult($classifyPromptResult);

$printSection('Prompt Package (Extract)');
$promptPackageResult = $request(
    'GET',
    $buildServiceUrl(
        $serviceBaseUrl,
        '/prompt-package',
        array(
            'market' => 'uk',
            'channel' => 'instagram',
            'stage' => 'extract',
            'category_hint' => $categoryKey,
        )
    ),
    array(
        'X-Bornado-Service-Key' => $serviceKey,
    )
);
$printResult($promptPackageResult);

$printSection('Resolve Test');
$resolvePayload = array(
    'market' => 'uk',
    'channel' => 'instagram',
    'extraction' => array(
        'status' => 'approved',
        'category_key' => $categoryKey,
        'country_key' => $countryKey,
        'city_key' => $cityKey,
        'primary_contact' => '07493995660',
        'secondary_contacts' => array(),
        'seo_title' => 'تست لایو سرویس هوش مصنوعی',
        'slug' => 'تست-لایو-سرویس-هوش-مصنوعی',
        'final_ad_text' => 'این فقط یک تست برای بررسی مسیر resolve سرویس است.',
        'dynamic_fields' => $buildDynamicFieldSample($extractFields),
    ),
);
$resolveBody = json_encode($resolvePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$resolveResult = $request(
    'POST',
    $buildServiceUrl($serviceBaseUrl, '/resolve'),
    array(
        'Content-Type' => 'application/json',
        'X-Bornado-Signature' => hash_hmac('sha256', (string) $resolveBody, $sharedSecret),
    ),
    $resolveBody
);
$printResult($resolveResult);

if ($withIngest) {
    $printSection('Ingest Test');
    $ingestResult = $request(
        'POST',
        $buildServiceUrl($serviceBaseUrl, '/ingest'),
        array(
            'Content-Type' => 'application/json',
            'X-Bornado-Signature' => hash_hmac('sha256', (string) $resolveBody, $sharedSecret),
        ),
        $resolveBody
    );
    $printResult($ingestResult);
}

if ($withCatalog && '' !== $catalogUrl) {
    $printSection('WordPress Bridge Catalog');
    $catalogResult = $request(
        'GET',
        $catalogUrl . (false === strpos($catalogUrl, '?') ? '?' : '&') . http_build_query(array('market' => 'gb', 'channel' => 'instagram', 'key' => $serviceKey)),
        array(
            'X-Bornado-Service-Key' => $serviceKey,
        )
    );
    $printResult($catalogResult);
} else {
    $printSection('WordPress Bridge Catalog');
    if (!$withCatalog) {
        echo "Skipped by default. Run again with --with-catalog if you want to test the WordPress bridge endpoint too.\n";
    } else {
        echo "Skipped: catalog endpoint is missing.\n";
    }
}

echo "\nDone.\n";
