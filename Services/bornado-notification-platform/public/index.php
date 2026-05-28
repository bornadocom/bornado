<?php
declare(strict_types=1);

use Bornado\NotificationPlatform\Contracts\EventCatalog;
use Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog;
use Bornado\NotificationPlatform\Infrastructure\FileEventQueue;
use Bornado\NotificationPlatform\Infrastructure\ServiceOperations;
use Bornado\NotificationPlatform\Infrastructure\WhatsAppStateStore;
use Bornado\NotificationPlatform\Infrastructure\WhatsAppWebhookHandler;

require_once dirname(__DIR__) . '/bootstrap.php';

/** @var array<string,mixed> $config */
$config = require dirname(__DIR__) . '/config/notification-platform.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = '' !== $path ? rtrim($path, '/') : '/';
$path = '' === $path ? '/' : $path;

$queue          = new FileEventQueue($config['queue']);
$log            = new FileDeliveryLog($config['logging']['delivery_log'], $config['logging']['state_dir']);
$whatsAppState  = new WhatsAppStateStore(
    (string) ($config['logging']['whatsapp_state_dir'] ?? ''),
    (string) ($config['logging']['whatsapp_webhook_log'] ?? ''),
    (string) ($config['logging']['whatsapp_inbound_dir'] ?? '')
);
$serviceOps     = new ServiceOperations($config);
$webhookHandler = new WhatsAppWebhookHandler(
    isset($config['webhooks']['whatsapp']) && is_array($config['webhooks']['whatsapp'])
        ? $config['webhooks']['whatsapp']
        : array(),
    $whatsAppState
);

$respondJson = static function (int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$respondText = static function (int $statusCode, string $payload, string $contentType = 'text/plain; charset=utf-8'): void {
    http_response_code($statusCode);
    header('Content-Type: ' . $contentType);
    echo $payload;
    exit;
};

$esc = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$getOpsKey = static function (): string {
    $requestValue = $_REQUEST['key'] ?? '';
    if ('' !== trim((string) $requestValue)) {
        return trim((string) $requestValue);
    }

    return trim((string) ($_SERVER['HTTP_X_BORNADO_OPS_KEY'] ?? ''));
};

$isValidServiceSignature = static function (string $rawBody) use ($config): bool {
    $sharedSecret = trim((string) ($config['service']['shared_secret'] ?? ''));
    $signature    = trim((string) ($_SERVER['HTTP_X_BORNADO_SIGNATURE'] ?? ''));

    if ('' === $sharedSecret || '' === $signature) {
        return false;
    }

    return hash_equals(hash_hmac('sha256', $rawBody, $sharedSecret), $signature);
};

if ('POST' === $method && '/events' === $path) {
    $rawBody = file_get_contents('php://input');
    if (!is_string($rawBody) || '' === trim($rawBody)) {
        $respondJson(400, array('message' => 'Request body is required.'));
    }

    $sharedSecret = trim((string) ($config['service']['shared_secret'] ?? ''));
    $signature    = trim((string) ($_SERVER['HTTP_X_BORNADO_SIGNATURE'] ?? ''));

    if ('' !== $sharedSecret) {
        $expectedSignature = hash_hmac('sha256', $rawBody, $sharedSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            $respondJson(401, array('message' => 'Invalid signature.'));
        }
    }

    $event = json_decode($rawBody, true);
    if (!is_array($event)) {
        $respondJson(400, array('message' => 'Invalid JSON payload.'));
    }

    $errors = EventCatalog::validate($event);
    if (!empty($errors)) {
        $respondJson(422, array('message' => 'Validation failed.', 'errors' => $errors));
    }

    $queuePath = $queue->enqueue($event);
    $log->markEvent(
        $event,
        'queued',
        array(
            'queuePath' => $queuePath,
        )
    );

    $respondJson(
        202,
        array(
            'message'   => 'Event accepted.',
            'eventId'   => $event['eventId'],
            'eventType' => $event['eventType'],
            'status'    => 'queued',
        )
    );
}

if ('/webhooks/whatsapp' === $path) {
    if ('GET' === $method) {
        $result = $webhookHandler->handleVerification($_GET);
        $respondText((int) $result['status'], (string) ($result['body'] ?? ''), (string) ($result['type'] ?? 'text/plain; charset=utf-8'));
    }

    if ('POST' === $method) {
        $rawBody = (string) file_get_contents('php://input');
        $result  = $webhookHandler->handleNotification(
            $rawBody,
            trim((string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ''))
        );
        $respondJson((int) $result['status'], (array) ($result['body'] ?? array()));
    }

    $respondJson(405, array('message' => 'Method not allowed.'));
}

if ('/ops' === $path) {
    $expectedOpsKey = trim((string) ($config['service']['ops_key'] ?? $config['service']['shared_secret'] ?? ''));
    $providedOpsKey = $getOpsKey();
    $format         = strtolower(trim((string) ($_REQUEST['format'] ?? 'html')));
    $actionResult   = null;

    if ('POST' === $method) {
        $rawBody = (string) file_get_contents('php://input');
        $decodedBody = json_decode($rawBody, true);
        $action  = trim((string) ($_REQUEST['action'] ?? (is_array($decodedBody) ? ($decodedBody['action'] ?? '') : '')));
        $hasOpsKey = '' !== $expectedOpsKey && '' !== $providedOpsKey && hash_equals($expectedOpsKey, $providedOpsKey);

        if ('ping' === $action && ($hasOpsKey || $isValidServiceSignature($rawBody))) {
            $respondJson(200, $serviceOps->ping());
        }

        if (!$hasOpsKey) {
            if ('json' === $format) {
                $respondJson(403, array('message' => 'Forbidden'));
            }

            $respondText(403, 'Forbidden');
        }

        if ('run_consumer' === $action) {
            $actionResult = $serviceOps->runConsumer(
                max(1, (int) ($_POST['limit'] ?? 20)),
                !empty($_POST['debug'])
            );
        } elseif ('requeue_failed' === $action) {
            $actionResult = $serviceOps->requeueFailed(max(1, (int) ($_POST['limit'] ?? 20)));
        } elseif ('set_provider_state' === $action) {
            $providerName = trim((string) ($_POST['provider'] ?? ''));
            $serviceOps->setProviderEnabled($providerName, '1' === (string) ($_POST['enabled'] ?? '0'));
            $actionResult = array('message' => 'Provider state updated.', 'provider' => $providerName);
        } elseif ('set_event_state' === $action) {
            $eventType = trim((string) ($_POST['event_type'] ?? ''));
            $serviceOps->setEventEnabled($eventType, '1' === (string) ($_POST['enabled'] ?? '0'));
            $actionResult = array('message' => 'Event route updated.', 'eventType' => $eventType);
        }
    } elseif ('' === $expectedOpsKey || '' === $providedOpsKey || !hash_equals($expectedOpsKey, $providedOpsKey)) {
        if ('json' === $format) {
            $respondJson(403, array('message' => 'Forbidden'));
        }

        $respondText(403, 'Forbidden');
    }

    $snapshot = $serviceOps->snapshot(max(1, (int) ($_REQUEST['limit'] ?? 10)));

    if ('json' === $format) {
        $payload = $snapshot;
        if (is_array($actionResult)) {
            $payload['actionResult'] = $actionResult;
        }

        $respondJson(200, $payload);
    }

    $statusBadge = static function (string $status) use ($esc): string {
        $background = 'warning' === $status ? '#fff3cd' : '#e7f7ed';
        $color      = 'warning' === $status ? '#7a5d00' : '#14532d';

        return '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:' . $background . ';color:' . $color . ';">' . $esc(strtoupper($status)) . '</span>';
    };

    $renderRows = static function (array $rows) use ($esc): string {
        if (empty($rows)) {
            return '<p style="margin:0;">No records yet.</p>';
        }

        $html = '<table style="width:100%;border-collapse:collapse;"><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr><td style="vertical-align:top;padding:8px;border-top:1px solid #e5e7eb;"><pre style="margin:0;white-space:pre-wrap;">' . $esc(json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre></td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    };

    $queue = isset($snapshot['queue']) && is_array($snapshot['queue']) ? $snapshot['queue'] : array();
    $runtime = isset($snapshot['runtime']) && is_array($snapshot['runtime']) ? $snapshot['runtime'] : array();
    $health = isset($snapshot['health']) && is_array($snapshot['health']) ? $snapshot['health'] : array();
    $deliveries = isset($snapshot['deliveries']) && is_array($snapshot['deliveries']) ? $snapshot['deliveries'] : array();
    $whatsApp = isset($snapshot['whatsapp']) && is_array($snapshot['whatsapp']) ? $snapshot['whatsapp'] : array();

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Bornado Notification Ops</title></head><body style="font-family:Arial,sans-serif;background:#f7f7f7;color:#111;margin:0;padding:24px;">';
    echo '<div style="max-width:1100px;margin:0 auto;">';
    echo '<h1 style="margin-top:0;">Bornado Notification Ops</h1>';
    echo '<p style="margin-top:0;color:#555;">Service-side dashboard for queue health, WhatsApp tracking, and runtime toggles. WordPress remains producer-only.</p>';

    if (is_array($actionResult)) {
        echo '<div style="margin:16px 0;padding:12px 16px;background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;"><strong>Last action:</strong><pre style="margin:8px 0 0;white-space:pre-wrap;">' . $esc(json_encode($actionResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre></div>';
    }

    echo '<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;">';
    foreach (array('pending', 'processing', 'processed', 'failed') as $bucket) {
        echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><div style="font-size:13px;color:#666;">' . $esc(ucfirst($bucket)) . '</div><div style="font-size:28px;font-weight:bold;">' . $esc((string) ($queue[$bucket] ?? 0)) . '</div></div>';
    }
    echo '</div>';

    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">';
    echo '<h2 style="margin-top:0;">Queue Actions</h2>';
    echo '<form method="post" style="margin-bottom:12px;"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="run_consumer"><label>Limit <input type="number" name="limit" value="20" min="1" style="width:72px;"></label> <label><input type="checkbox" name="debug" value="1"> Debug</label> <button type="submit">Run consumer</button></form>';
    echo '<form method="post"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="requeue_failed"><label>Limit <input type="number" name="limit" value="20" min="1" style="width:72px;"></label> <button type="submit">Requeue failed</button></form>';
    echo '<p style="color:#666;margin-bottom:0;">JSON snapshot: <a href="?key=' . rawurlencode($providedOpsKey) . '&format=json">view</a></p>';
    echo '</section>';

    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">';
    echo '<h2 style="margin-top:0;">Health</h2><table style="width:100%;border-collapse:collapse;">';
    foreach ($health as $item) {
        $label = isset($item['label']) ? (string) $item['label'] : 'Check';
        $status = isset($item['status']) ? (string) $item['status'] : 'warning';
        $value = isset($item['value']) ? (string) $item['value'] : '';
        echo '<tr><td style="padding:8px 0;border-top:1px solid #e5e7eb;">' . $esc($label) . ($value ? '<div style="color:#666;font-size:12px;">' . $esc($value) . '</div>' : '') . '</td><td style="padding:8px 0;border-top:1px solid #e5e7eb;text-align:right;">' . $statusBadge($status) . '</td></tr>';
    }
    echo '</table></section></div>';

    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Provider Toggles</h2>';
    echo '<table style="width:100%;border-collapse:collapse;"><tbody>';
    foreach ((array) ($runtime['providers'] ?? array()) as $provider) {
        $name = (string) ($provider['name'] ?? '');
        $effective = !empty($provider['effectiveEnabled']);
        echo '<tr><td style="padding:8px 0;border-top:1px solid #e5e7eb;">' . $esc($name) . '</td><td style="padding:8px 0;border-top:1px solid #e5e7eb;text-align:right;">';
        echo '<form method="post" style="display:inline;"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="set_provider_state"><input type="hidden" name="provider" value="' . $esc($name) . '"><input type="hidden" name="enabled" value="' . ($effective ? '0' : '1') . '"><button type="submit">' . ($effective ? 'Disable' : 'Enable') . '</button></form>';
        echo '</td></tr>';
    }
    echo '</tbody></table></section>';

    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Event Route Toggles</h2>';
    echo '<table style="width:100%;border-collapse:collapse;"><tbody>';
    foreach ((array) ($runtime['events'] ?? array()) as $eventConfig) {
        $eventType = (string) ($eventConfig['eventType'] ?? '');
        $effective = !empty($eventConfig['effectiveEnabled']);
        $channels = implode(', ', (array) ($eventConfig['configuredChannels'] ?? array()));
        echo '<tr><td style="padding:8px 0;border-top:1px solid #e5e7eb;">' . $esc($eventType) . '<div style="font-size:12px;color:#666;">Configured channels: ' . $esc($channels ?: 'none') . '</div></td><td style="padding:8px 0;border-top:1px solid #e5e7eb;text-align:right;">';
        echo '<form method="post" style="display:inline;"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="set_event_state"><input type="hidden" name="event_type" value="' . $esc($eventType) . '"><input type="hidden" name="enabled" value="' . ($effective ? '0' : '1') . '"><button type="submit">' . ($effective ? 'Disable' : 'Enable') . '</button></form>';
        echo '</td></tr>';
    }
    echo '</tbody></table></section></div>';

    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Recent Deliveries</h2>' . $renderRows($deliveries) . '</section>';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Recent WhatsApp Statuses</h2><p style="color:#666;">Inbound mode: ' . $esc((string) ($whatsApp['inboundMode'] ?? 'log_only')) . '</p>' . $renderRows((array) ($whatsApp['recentStatuses'] ?? array())) . '</section>';
    echo '</div>';

    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-top:16px;"><h2 style="margin-top:0;">Recent Inbound Messages</h2>' . $renderRows((array) ($whatsApp['recentInbound'] ?? array())) . '</section>';
    echo '</div></body></html>';
    exit;
}

$respondJson(404, array('message' => 'Not Found'));
