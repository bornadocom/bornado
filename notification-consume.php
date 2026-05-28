<?php
declare(strict_types=1);

use Bornado\NotificationPlatform\Application\QueueConsumer;

require __DIR__ . '/Services/bornado-notification-platform/bootstrap.php';

/** @var array<string,mixed> $config */
$config = require __DIR__ . '/Services/bornado-notification-platform/config/notification-platform.php';

$providedKey = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
$expectedKey = trim((string) ($config['service']['ops_key'] ?? $config['service']['shared_secret'] ?? ''));

if ('' === $expectedKey || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('message' => 'Forbidden'));
    exit;
}

$limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 20;
$debug = !empty($_GET['debug']);

$consumer = new QueueConsumer($config);
$result   = $consumer->run($limit, $debug);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
