<?php
declare(strict_types=1);

use Bornado\AiExtractionPlatform\Application\PromptPackageService;
use Bornado\AiExtractionPlatform\Application\ResolverService;
use Bornado\AiExtractionPlatform\Application\SchemaService;
use Bornado\AiExtractionPlatform\Infrastructure\FileSchemaCache;
use Bornado\AiExtractionPlatform\Infrastructure\WordPressBridgePublisher;
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
$schemaService = new SchemaService($catalogSource, $schemaCache, $config);
$promptService = new PromptPackageService($config);
$resolver      = new ResolverService();
$publisher     = new WordPressBridgePublisher($config);

$respondJson = static function (int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$requireKeyAuth = static function (bool $enabled = true) use ($config, $respondJson): void {
    if (!$enabled) {
        return;
    }

    $expected = trim((string) ($config['service']['ops_key'] ?? $config['service']['shared_secret'] ?? ''));
    $provided = trim((string) ($_SERVER['HTTP_X_BORNADO_SERVICE_KEY'] ?? ($_GET['key'] ?? '')));

    if ('' === $expected || '' === $provided || !hash_equals($expected, $provided)) {
        $respondJson(403, array('message' => 'Forbidden'));
    }
};

$hasValidServiceKey = static function () use ($config): bool {
    $expected = trim((string) ($config['service']['ops_key'] ?? $config['service']['shared_secret'] ?? ''));
    $provided = trim((string) ($_SERVER['HTTP_X_BORNADO_SERVICE_KEY'] ?? ($_GET['key'] ?? '')));

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

$market  = trim(strtolower((string) ($_GET['market'] ?? 'uk')));
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

        $schema = $schemaService->getSchema($market, $channel);
        $resolved = $resolver->resolve($schema, $extraction);
        if ('resolved' !== (string) ($resolved['resolution_status'] ?? 'invalid')) {
            $respondJson(422, $resolved);
        }

        $bridgeRecord = isset($resolved['target_payload']['wordpress_bridge']) && is_array($resolved['target_payload']['wordpress_bridge'])
            ? $resolved['target_payload']['wordpress_bridge']
            : array();
        $publishResult = $publisher->publish($bridgeRecord);

        $respondJson(
            200,
            array(
                'resolution' => $resolved,
                'publish' => $publishResult,
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
