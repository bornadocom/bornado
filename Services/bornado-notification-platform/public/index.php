<?php
declare(strict_types=1);

use Bornado\NotificationPlatform\Contracts\EventCatalog;
use Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog;
use Bornado\NotificationPlatform\Infrastructure\FileEventQueue;

require_once dirname(__DIR__) . '/bootstrap.php';

/** @var array<string,mixed> $config */
$config = require dirname(__DIR__) . '/config/notification-platform.php';

$queue = new FileEventQueue($config['queue']);
$log   = new FileDeliveryLog($config['logging']['delivery_log'], $config['logging']['state_dir']);

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path   = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

header('Content-Type: application/json; charset=utf-8');

if ('POST' !== $method || '/events' !== $path) {
    http_response_code(404);
    echo json_encode(array('message' => 'Not Found'));
    exit;
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || '' === trim($rawBody)) {
    http_response_code(400);
    echo json_encode(array('message' => 'Request body is required.'));
    exit;
}

$sharedSecret = trim((string) ($config['service']['shared_secret'] ?? ''));
$signature    = trim((string) ($_SERVER['HTTP_X_BORNADO_SIGNATURE'] ?? ''));

if ('' !== $sharedSecret) {
    $expectedSignature = hash_hmac('sha256', $rawBody, $sharedSecret);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(array('message' => 'Invalid signature.'));
        exit;
    }
}

$event = json_decode($rawBody, true);
if (!is_array($event)) {
    http_response_code(400);
    echo json_encode(array('message' => 'Invalid JSON payload.'));
    exit;
}

$errors = EventCatalog::validate($event);
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(array('message' => 'Validation failed.', 'errors' => $errors), JSON_UNESCAPED_UNICODE);
    exit;
}

$queuePath = $queue->enqueue($event);
$log->markEvent(
    $event,
    'queued',
    array(
        'queuePath' => $queuePath,
    )
);

http_response_code(202);
echo json_encode(
    array(
        'message'  => 'Event accepted.',
        'eventId'  => $event['eventId'],
        'eventType'=> $event['eventType'],
        'status'   => 'queued',
    ),
    JSON_UNESCAPED_UNICODE
);
