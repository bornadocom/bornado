<?php
declare(strict_types=1);

use Bornado\AiExtractionPlatform\Application\PromptPackageService;
use Bornado\AiExtractionPlatform\Application\DedupService;
use Bornado\AiExtractionPlatform\Application\Phone\PhoneGateService;
use Bornado\AiExtractionPlatform\Application\ResolverService;
use Bornado\AiExtractionPlatform\Application\SchemaService;
use Bornado\AiExtractionPlatform\Infrastructure\FileSchemaCache;
use Bornado\AiExtractionPlatform\Infrastructure\WordPressBridgePublisher;
use Bornado\AiExtractionPlatform\Infrastructure\WordPressGeoCityLookupClient;
use Bornado\AiExtractionPlatform\Infrastructure\WordPressRestCatalogSource;

require_once dirname(__DIR__) . '/bootstrap.php';

/** @var array<string,mixed> $config */
$config = require dirname(__DIR__) . '/config/ai-extraction-platform.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$routeOverride = trim((string) ($_GET['route'] ?? ''));

if ('' !== $routeOverride) {
    $path = '/' . ltrim($routeOverride, '/');
} else {
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $path = str_replace('\\', '/', $path);

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir  = '' !== $scriptName ? str_replace('\\', '/', dirname($scriptName)) : '';
    $scriptDir  = '.' === $scriptDir ? '' : rtrim($scriptDir, '/');

    if ('' !== $scriptName && 0 === strpos($path, $scriptName)) {
        $path = substr($path, strlen($scriptName));
    } elseif ('' !== $scriptDir && '/' !== $scriptDir && 0 === strpos($path, $scriptDir)) {
        $path = substr($path, strlen($scriptDir));
    }

    $path = '' !== $path ? rtrim($path, '/') : '/';
    $path = '' === $path ? '/' : $path;
}

$schemaCache   = new FileSchemaCache((string) ($config['logging']['schema_cache_dir'] ?? ''));
$catalogSource = new WordPressRestCatalogSource((array) ($config['source'] ?? array()));
$geoCityLookup = new WordPressGeoCityLookupClient((array) ($config['source'] ?? array()));
$schemaService = new SchemaService($catalogSource, $schemaCache, $config);
$promptService = new PromptPackageService($config);
$dedupService  = new DedupService($config);
$phoneGateService = new PhoneGateService();
$resolver      = new ResolverService($geoCityLookup);
$publisher     = new WordPressBridgePublisher($config);

$respondJson = static function (int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$queryKeyAuthAllowed = !empty($config['security']['allow_query_key_auth']);

$requireKeyAuth = static function (bool $enabled = true) use ($config, $respondJson, $queryKeyAuthAllowed): void {
    if (!$enabled) {
        return;
    }

    $expected = trim((string) ($config['service']['ops_key'] ?? $config['service']['shared_secret'] ?? ''));
    $provided = trim((string) ($_SERVER['HTTP_X_BORNADO_SERVICE_KEY'] ?? ''));

    if ('' === $provided && $queryKeyAuthAllowed) {
        $provided = trim((string) ($_GET['key'] ?? ''));
    }

    if ('' === $expected || '' === $provided || !hash_equals($expected, $provided)) {
        $respondJson(403, array('message' => 'Forbidden'));
    }
};

$hasValidServiceKey = static function () use ($config, $queryKeyAuthAllowed): bool {
    $expected = trim((string) ($config['service']['ops_key'] ?? $config['service']['shared_secret'] ?? ''));
    $provided = trim((string) ($_SERVER['HTTP_X_BORNADO_SERVICE_KEY'] ?? ''));

    if ('' === $provided && $queryKeyAuthAllowed) {
        $provided = trim((string) ($_GET['key'] ?? ''));
    }

    return '' !== $expected && '' !== $provided && hash_equals($expected, $provided);
};

$requireSignatureAuth = static function (string $rawBody, bool $enabled = true) use ($config, $respondJson, $hasValidServiceKey): void {
    if (!$enabled) {
        return;
    }

    if ($hasValidServiceKey()) {
        return;
    }

    $sharedSecret = trim((string) ($config['service']['shared_secret'] ?? ''));
    $signature    = trim((string) ($_SERVER['HTTP_X_BORNADO_SIGNATURE'] ?? ''));

    if ('' === $sharedSecret || '' === $signature) {
        $respondJson(401, array('message' => 'Missing signature.'));
    }

    $expected = hash_hmac('sha256', $rawBody, $sharedSecret);
    if (!hash_equals($expected, $signature)) {
        $respondJson(401, array('message' => 'Invalid signature.'));
    }
};

$mergeOperationalFallbacks = static function (array $decoded, array $payload): array {
    $context = isset($decoded['context']) && is_array($decoded['context'])
        ? $decoded['context']
        : array();

    foreach (array('default_country_key') as $key) {
        if (!array_key_exists($key, $payload) && array_key_exists($key, $context)) {
            $payload[$key] = $context[$key];
        }
        if (!array_key_exists($key, $payload) && array_key_exists($key, $decoded)) {
            $payload[$key] = $decoded[$key];
        }
    }

    foreach (array('default_city_geoname_id', 'city_geoname_id') as $key) {
        if (!array_key_exists($key, $payload) && array_key_exists($key, $context)) {
            $payload[$key] = $context[$key];
        }
        if (!array_key_exists($key, $payload) && array_key_exists($key, $decoded)) {
            $payload[$key] = $decoded[$key];
        }
    }

    return $payload;
};

$market  = trim(strtolower((string) ($_GET['market'] ?? '')));
$channel = trim(strtolower((string) ($_GET['channel'] ?? 'instagram')));
$promptStage = trim(strtolower((string) ($_GET['stage'] ?? 'extract')));
$categoryHint = trim(strtolower((string) ($_GET['category_hint'] ?? '')));
$candidateCategories = array_values(
    array_filter(
        array_map(
            static function (string $value): string {
                return trim(strtolower($value));
            },
            explode(',', (string) ($_GET['candidate_categories'] ?? ''))
        ),
        static function (string $value): bool {
            return '' !== $value;
        }
    )
);

if ('GET' === $method && '/health' === $path) {
    $respondJson(
        200,
        array(
            'service' => (string) ($config['service']['name'] ?? 'bornado-ai-extraction-platform'),
            'status' => 'ok',
            'time' => gmdate('c'),
        )
    );
}

if ('GET' === $method && '/schema' === $path) {
    try {
        $requireKeyAuth(!empty($config['security']['require_auth_for_schema']));
        $respondJson(200, $schemaService->getSchema($market, $channel));
    } catch (\Throwable $exception) {
        $respondJson(
            500,
            array(
                'message' => 'Schema build failed.',
                'error' => $exception->getMessage(),
            )
        );
    }
}

if ('GET' === $method && '/prompt-package' === $path) {
    try {
        $requireKeyAuth(!empty($config['security']['require_auth_for_prompt']));
        $schema = $schemaService->getSchema($market, $channel);
        $respondJson(
            200,
            $promptService->buildPromptPackage(
                $schema,
                array(
                    'stage' => $promptStage,
                    'category_hint' => $categoryHint,
                    'candidate_categories' => $candidateCategories,
                )
            )
        );
    } catch (\Throwable $exception) {
        $respondJson(
            500,
            array(
                'message' => 'Prompt package build failed.',
                'error' => $exception->getMessage(),
            )
        );
    }
}

if ('POST' === $method && '/resolve' === $path) {
    try {
        $rawBody = (string) file_get_contents('php://input');
        if ('' === trim($rawBody)) {
            $respondJson(400, array('message' => 'Request body is required.'));
        }

        $requireSignatureAuth($rawBody, !empty($config['security']['require_auth_for_resolve']));

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            $respondJson(400, array('message' => 'Invalid JSON payload.'));
        }

        $market = trim(strtolower((string) ($decoded['market'] ?? $market)));
        $channel = trim(strtolower((string) ($decoded['channel'] ?? $channel)));
        $extraction = isset($decoded['extraction']) && is_array($decoded['extraction'])
            ? $decoded['extraction']
            : $decoded;
        $extraction = $mergeOperationalFallbacks($decoded, $extraction);

        $schema = $schemaService->getSchema($market, $channel);
        $respondJson(200, $resolver->resolve($schema, $extraction));
    } catch (\Throwable $exception) {
        $respondJson(
            500,
            array(
                'message' => 'Resolve failed.',
                'error' => $exception->getMessage(),
            )
        );
    }
}

if ('POST' === $method && '/dedup/preflight' === $path) {
    try {
        $rawBody = (string) file_get_contents('php://input');
        if ('' === trim($rawBody)) {
            $respondJson(400, array('message' => 'Request body is required.'));
        }

        $requireSignatureAuth($rawBody, !empty($config['security']['require_auth_for_resolve']));

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            $respondJson(400, array('message' => 'Invalid JSON payload.'));
        }

        $respondJson(200, $dedupService->preflight($decoded));
    } catch (\Throwable $exception) {
        $respondJson(
            500,
            array(
                'message' => 'Dedup preflight failed.',
                'error' => $exception->getMessage(),
            )
        );
    }
}

if ('POST' === $method && '/dedup/finalize' === $path) {
    try {
        $rawBody = (string) file_get_contents('php://input');
        if ('' === trim($rawBody)) {
            $respondJson(400, array('message' => 'Request body is required.'));
        }

        $requireSignatureAuth($rawBody, !empty($config['security']['require_auth_for_resolve']));

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            $respondJson(400, array('message' => 'Invalid JSON payload.'));
        }

        $respondJson(200, $dedupService->finalize($decoded));
    } catch (\Throwable $exception) {
        $respondJson(
            200,
            array(
                'status' => 'error',
                'message' => 'Dedup finalize failed.',
                'error' => $exception->getMessage(),
            )
        );
    }
}

if ('POST' === $method && '/phone-gate' === $path) {
    try {
        $rawBody = (string) file_get_contents('php://input');
        if ('' === trim($rawBody)) {
            $respondJson(400, array('message' => 'Request body is required.'));
        }

        $requireSignatureAuth($rawBody, !empty($config['security']['require_auth_for_resolve']));

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            $respondJson(400, array('message' => 'Invalid JSON payload.'));
        }

        $respondJson(200, $phoneGateService->evaluate($decoded));
    } catch (\Throwable $exception) {
        $respondJson(
            500,
            array(
                'message' => 'Phone gate failed.',
                'error' => $exception->getMessage(),
            )
        );
    }
}

if ('POST' === $method && ('/ingest' === $path || '/resolve-and-save' === $path)) {
    try {
        $rawBody = (string) file_get_contents('php://input');
        if ('' === trim($rawBody)) {
            $respondJson(400, array('message' => 'Request body is required.'));
        }

        $requireSignatureAuth($rawBody, !empty($config['security']['require_auth_for_resolve']));

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            $respondJson(400, array('message' => 'Invalid JSON payload.'));
        }

        $market = trim(strtolower((string) ($decoded['market'] ?? $market)));
        $channel = trim(strtolower((string) ($decoded['channel'] ?? $channel)));
        $extraction = isset($decoded['extraction']) && is_array($decoded['extraction'])
            ? $decoded['extraction']
            : $decoded;
        $extraction = $mergeOperationalFallbacks($decoded, $extraction);

        $rawPhoneCheck = $phoneGateService->evaluate(
            array(
                'content' => array(
                    'effective_text' => (string) ($decoded['content']['effective_text'] ?? $decoded['content']['text'] ?? ''),
                    'scraper_ocr_text' => (string) ($decoded['content']['scraper_ocr_text'] ?? ''),
                    'ocr_text' => (string) ($decoded['content']['ocr_text'] ?? ''),
                    'first_image_url' => (string) ($decoded['content']['first_image_url'] ?? ''),
                    'first_media_id' => (string) ($decoded['content']['first_media_id'] ?? ''),
                    'first_attachment_token' => (string) ($decoded['content']['first_attachment_token'] ?? ''),
                ),
            )
        );

        $rawFallbackPhone = trim((string) ($rawPhoneCheck['primary_phone'] ?? ''));
        $primaryContact = preg_replace('/\D+/', '', (string) ($extraction['primary_contact'] ?? '')) ?: '';
        if ('' === $primaryContact && '' !== $rawFallbackPhone) {
            $extraction['primary_contact'] = $rawFallbackPhone;
        }

        $schema = $schemaService->getSchema($market, $channel);
        $resolved = $resolver->resolve($schema, $extraction);
        if ('resolved' !== (string) ($resolved['resolution_status'] ?? 'invalid')) {
            $respondJson(422, $resolved);
        }

        $resolvedPhonePayload = array(
            'content' => array(
                'effective_text' => (string) ($decoded['content']['effective_text'] ?? $decoded['content']['text'] ?? ''),
                'scraper_ocr_text' => (string) ($decoded['content']['scraper_ocr_text'] ?? ''),
                'ocr_text' => (string) ($decoded['content']['ocr_text'] ?? ''),
                'first_image_url' => (string) ($decoded['content']['first_image_url'] ?? ''),
                'first_media_id' => (string) ($decoded['content']['first_media_id'] ?? ''),
                'first_attachment_token' => (string) ($decoded['content']['first_attachment_token'] ?? ''),
            ),
        );

        $resolvedContacts = isset($resolved['target_payload']['wordpress_bridge']['meta']) && is_array($resolved['target_payload']['wordpress_bridge']['meta'])
            ? $resolved['target_payload']['wordpress_bridge']['meta']
            : array();
        $resolvedPhonePayload['content']['ocr_text'] .= "\n" . (string) ($resolvedContacts['_adforest_poster_contact'] ?? '');
        foreach ((array) ($resolvedContacts['_bornado_secondary_contacts'] ?? array()) as $secondaryContact) {
            $resolvedPhonePayload['content']['ocr_text'] .= "\n" . (string) $secondaryContact;
        }

        $resolvedPhoneCheck = $phoneGateService->evaluate($resolvedPhonePayload);
        if (empty($resolvedPhoneCheck['has_phone'])) {
            $respondJson(
                422,
                array(
                    'message' => 'Approved records require at least one detected phone number before ingest.',
                    'phone_gate' => $resolvedPhoneCheck,
                )
            );
        }

        $bridgeRecord = isset($resolved['target_payload']['wordpress_bridge']) && is_array($resolved['target_payload']['wordpress_bridge'])
            ? $resolved['target_payload']['wordpress_bridge']
            : array();
        $publishResult = $publisher->publish($bridgeRecord);
        $dedupFinalizeResult = array();
        $hasDedupSource = isset($decoded['source']) && is_array($decoded['source']) && !empty($decoded['source']);
        $hasDedupContent = isset($decoded['content']) && is_array($decoded['content']) && !empty($decoded['content']);

        if ($hasDedupSource || $hasDedupContent) {
            $dedupFinalizeResult = $dedupService->finalize(
                array(
                    'market' => $market,
                    'channel' => $channel,
                    'source' => $hasDedupSource ? $decoded['source'] : array(),
                    'content' => $hasDedupContent ? $decoded['content'] : array(),
                    'ai' => array(
                        'classify' => isset($decoded['ai']['classify']) && is_array($decoded['ai']['classify'])
                            ? $decoded['ai']['classify']
                            : array(),
                        'extract' => $extraction,
                    ),
                    'publish' => $publishResult,
                )
            );
        }

        $respondJson(
            200,
            array(
                'resolution' => $resolved,
                'publish' => $publishResult,
                'dedup' => $dedupFinalizeResult,
            )
        );
    } catch (\Throwable $exception) {
        $respondJson(
            500,
            array(
                'message' => 'Ingest failed.',
                'error' => $exception->getMessage(),
            )
        );
    }
}

$respondJson(404, array('message' => 'Not Found'));
