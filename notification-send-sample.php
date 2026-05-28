<?php
declare(strict_types=1);

use Bornado\NotificationPlatform\Contracts\EventCatalog;
use Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog;
use Bornado\NotificationPlatform\Infrastructure\FileEventQueue;

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

$samplePath = __DIR__ . '/Services/bornado-notification-platform/examples/events/listing.published.sample.json';
$event      = json_decode((string) file_get_contents($samplePath), true);

if (!is_array($event)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('message' => 'Sample event is invalid.'));
    exit;
}

$phone = isset($_GET['to']) ? preg_replace('/[^\d+]/', '', (string) $_GET['to']) : '';
if (is_string($phone) && '' !== $phone) {
    $event['payload']['user']['phone'] = $phone;

    if (!empty($event['payload']['user']['contacts']) && is_array($event['payload']['user']['contacts'])) {
        foreach ($event['payload']['user']['contacts'] as $index => $contact) {
            if (!is_array($contact)) {
                continue;
            }

            if (in_array((string) ($contact['channel'] ?? ''), array('whatsapp', 'sms'), true)) {
                $event['payload']['user']['contacts'][$index]['address'] = $phone;
            }
        }
    }
}

$event['eventId']        = sprintf('listing.published.ad_post.sample.%s', gmdate('YmdHis'));
$event['occurredAt']     = gmdate('c');
$event['idempotencyKey'] = sha1((string) $event['eventId']);

$errors = EventCatalog::validate($event);
if (!empty($errors)) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('message' => 'Validation failed.', 'errors' => $errors), JSON_UNESCAPED_UNICODE);
    exit;
}

$queue = new FileEventQueue($config['queue']);
$log   = new FileDeliveryLog($config['logging']['delivery_log'], $config['logging']['state_dir']);
$path  = $queue->enqueue($event);
$log->markEvent(
    $event,
    'queued',
    array(
        'queuePath' => $path,
        'sample'    => true,
    )
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    array(
        'message' => 'Sample event queued.',
        'eventId' => $event['eventId'],
        'to'      => $event['payload']['user']['phone'] ?? '',
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
