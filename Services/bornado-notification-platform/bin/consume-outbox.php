<?php
declare(strict_types=1);

use Bornado\NotificationPlatform\Application\NotificationOrchestrator;
use Bornado\NotificationPlatform\Application\PolicyEngine;
use Bornado\NotificationPlatform\Application\TemplateEngine;
use Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog;
use Bornado\NotificationPlatform\Infrastructure\FileEventQueue;
use Bornado\NotificationPlatform\Infrastructure\ProviderFactory;

require_once dirname(__DIR__) . '/bootstrap.php';

/** @var array<string,mixed> $config */
$config = require dirname(__DIR__) . '/config/notification-platform.php';

$queue        = new FileEventQueue($config['queue']);
$deliveryLog  = new FileDeliveryLog($config['logging']['delivery_log'], $config['logging']['state_dir']);
$policyEngine = new PolicyEngine($config);
$templateEngine = new TemplateEngine($config);
$providers    = ProviderFactory::buildAll($config);

$orchestrator = new NotificationOrchestrator(
    $policyEngine,
    $templateEngine,
    $deliveryLog,
    $providers
);

$limit = 20;

if (!empty($argv[1]) && is_numeric($argv[1])) {
    $limit = max(1, (int) $argv[1]);
}

$claims = $queue->claimBatch($limit);

foreach ($claims as $claim) {
    $event  = is_array($claim['event'] ?? null) ? $claim['event'] : array();
    $result = $orchestrator->handle($event);

    if (in_array($result['status'] ?? '', array('sent', 'no_route', 'duplicate', 'invalid'), true)) {
        $queue->acknowledge((string) $claim['path'], $result);
        continue;
    }

    $queue->fail((string) $claim['path'], $result);
}

echo sprintf("Processed %d event(s).\n", count($claims));
