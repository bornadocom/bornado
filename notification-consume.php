<?php
declare(strict_types=1);

use Bornado\NotificationPlatform\Application\NotificationOrchestrator;
use Bornado\NotificationPlatform\Application\PolicyEngine;
use Bornado\NotificationPlatform\Application\TemplateEngine;
use Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog;
use Bornado\NotificationPlatform\Infrastructure\FileEventQueue;
use Bornado\NotificationPlatform\Infrastructure\ProviderFactory;

require __DIR__ . '/Services/bornado-notification-platform/bootstrap.php';

/** @var array<string,mixed> $config */
$config = require __DIR__ . '/Services/bornado-notification-platform/config/notification-platform.php';

$providedKey = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
$expectedKey = trim((string) ($config['service']['shared_secret'] ?? ''));

if ('' === $expectedKey || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('message' => 'Forbidden'));
    exit;
}

$limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 20;

$queue          = new FileEventQueue($config['queue']);
$deliveryLog    = new FileDeliveryLog($config['logging']['delivery_log'], $config['logging']['state_dir']);
$policyEngine   = new PolicyEngine($config);
$templateEngine = new TemplateEngine($config);
$providers      = ProviderFactory::buildAll($config);
$orchestrator   = new NotificationOrchestrator($policyEngine, $templateEngine, $deliveryLog, $providers);
$claims         = $queue->claimBatch($limit);
$results        = array();

foreach ($claims as $claim) {
    $event  = is_array($claim['event'] ?? null) ? $claim['event'] : array();
    $result = $orchestrator->handle($event);
    $results[] = $result;

    if (in_array($result['status'] ?? '', array('sent', 'no_route', 'duplicate', 'invalid'), true)) {
        $queue->acknowledge((string) $claim['path'], $result);
        continue;
    }

    $queue->fail((string) $claim['path'], $result);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    array(
        'processed' => count($claims),
        'results'   => $results,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
