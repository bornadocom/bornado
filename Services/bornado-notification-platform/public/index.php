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

    if ($serviceOps->isServicePaused()) {
        $respondJson(
            503,
            array(
                'message' => 'Service is paused. Event ingestion is temporarily disabled.',
                'code'    => 'service_paused',
            )
        );
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
    $currentLimit   = max(1, (int) ($_REQUEST['limit'] ?? 10));
    $actionResult   = null;
    $opsDir         = (string) ($config['logging']['ops_dir'] ?? '');
    $flashDir       = '' !== $opsDir ? $opsDir . DIRECTORY_SEPARATOR . 'flash' : '';
    $redirectPath   = trim((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ('' === $redirectPath) {
        $redirectPath = '/notification-admin.php';
    }

    $storeFlash = static function (array $payload) use ($flashDir): string {
        if ('' === $flashDir) {
            return '';
        }

        if (!is_dir($flashDir)) {
            mkdir($flashDir, 0777, true);
        }

        $flashId = bin2hex(random_bytes(8));
        file_put_contents(
            $flashDir . DIRECTORY_SEPARATOR . $flashId . '.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $flashId;
    };

    $consumeFlash = static function (string $flashId) use ($flashDir): ?array {
        if ('' === $flashDir || '' === $flashId) {
            return null;
        }

        $path = $flashDir . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9]/', '', $flashId) . '.json';
        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        @unlink($path);

        return is_array($decoded) ? $decoded : null;
    };

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
        } elseif ('clear_queue_bucket' === $action) {
            $bucket = trim((string) ($_POST['bucket'] ?? ''));
            $actionResult = $serviceOps->clearQueueBucket($bucket, max(1, (int) ($_POST['limit'] ?? 1000)));
        } elseif ('set_service_pause' === $action) {
            $isPaused = '1' === (string) ($_POST['paused'] ?? '0');
            $serviceOps->setServicePaused($isPaused);
            $actionResult = array(
                'message' => $isPaused ? 'Service paused.' : 'Service resumed.',
                'paused'  => $isPaused,
            );
        } elseif ('reply_inbound_message' === $action) {
            $messageId = trim((string) ($_POST['message_id'] ?? ''));
            $replyBody = trim((string) ($_POST['reply_body'] ?? ''));
            $actionResult = $serviceOps->replyToInboundMessage($messageId, $replyBody);
        } elseif ('set_provider_state' === $action) {
            $providerName = trim((string) ($_POST['provider'] ?? ''));
            $serviceOps->setProviderEnabled($providerName, '1' === (string) ($_POST['enabled'] ?? '0'));
            $actionResult = array('message' => 'Provider state updated.', 'provider' => $providerName);
        } elseif ('set_event_state' === $action) {
            $eventType = trim((string) ($_POST['event_type'] ?? ''));
            $serviceOps->setEventEnabled($eventType, '1' === (string) ($_POST['enabled'] ?? '0'));
            $actionResult = array('message' => 'Event route updated.', 'eventType' => $eventType);
        }

        if ('json' !== $format && is_array($actionResult)) {
            $flashId = $storeFlash($actionResult);
            $query = array(
                'key'   => $providedOpsKey,
                'limit' => $currentLimit,
            );

            if ('' !== $flashId) {
                $query['flash'] = $flashId;
            }

            header('Location: ' . $redirectPath . '?' . http_build_query($query), true, 303);
            exit;
        }
    } elseif ('' === $expectedOpsKey || '' === $providedOpsKey || !hash_equals($expectedOpsKey, $providedOpsKey)) {
        if ('json' === $format) {
            $respondJson(403, array('message' => 'Forbidden'));
        }

        $respondText(403, 'Forbidden');
    }

    if (isset($_GET['flash'])) {
        $flashPayload = $consumeFlash(trim((string) $_GET['flash']));
        if (is_array($flashPayload)) {
            $actionResult = $flashPayload;
        }
    }

    $snapshot = $serviceOps->snapshot($currentLimit);

    if ('json' === $format) {
        $payload = $snapshot;
        if (is_array($actionResult)) {
            $payload['actionResult'] = $actionResult;
        }

        $respondJson(200, $payload);
    }

    $statusBadge = static function (string $status) use ($esc): string {
        $normalized = strtolower(trim($status));
        $palette = array(
            'ok'         => array('#e7f7ed', '#14532d'),
            'running'    => array('#e7f7ed', '#14532d'),
            'sent'       => array('#e7f7ed', '#14532d'),
            'provider_accepted' => array('#e0f2fe', '#075985'),
            'accepted'   => array('#e0f2fe', '#075985'),
            'delivered'  => array('#e7f7ed', '#14532d'),
            'read'       => array('#ede9fe', '#5b21b6'),
            'warning'    => array('#fff3cd', '#7a5d00'),
            'paused'     => array('#fff3cd', '#7a5d00'),
            'queued'     => array('#e0f2fe', '#075985'),
            'pending'    => array('#e0f2fe', '#075985'),
            'processing' => array('#ede9fe', '#5b21b6'),
            'failed'     => array('#fee2e2', '#991b1b'),
            'provider_rejected' => array('#fee2e2', '#991b1b'),
            'no_route'   => array('#f3f4f6', '#374151'),
        );

        $colors = isset($palette[$normalized]) ? $palette[$normalized] : array('#f3f4f6', '#374151');

        return '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:' . $colors[0] . ';color:' . $colors[1] . ';font-size:12px;font-weight:600;">' . $esc($normalized !== '' ? strtoupper($normalized) : 'INFO') . '</span>';
    };

    $renderDetails = static function (array $row, string $label = 'Details') use ($esc): string {
        return '<details><summary style="cursor:pointer;color:#2563eb;">' . $esc($label) . '</summary><pre style="margin:8px 0 0;white-space:pre-wrap;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;padding:8px;">' . $esc(json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre></details>';
    };

    $renderTable = static function (array $headers, array $rows): string {
        if (empty($rows)) {
            return '<p style="margin:0;color:#666;">No records yet.</p>';
        }

        $html = '<div style="overflow:auto;"><table style="width:100%;border-collapse:collapse;font-size:13px;">';
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;color:#666;font-weight:600;white-space:nowrap;">' . $header . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $cells) {
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= '<td style="vertical-align:top;padding:8px;border-top:1px solid #f1f5f9;">' . $cell . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    };

    $queue = isset($snapshot['queue']) && is_array($snapshot['queue']) ? $snapshot['queue'] : array();
    $queueItems = isset($queue['items']) && is_array($queue['items']) ? $queue['items'] : array();
    $service = isset($snapshot['service']) && is_array($snapshot['service']) ? $snapshot['service'] : array();
    $runtime = isset($snapshot['runtime']) && is_array($snapshot['runtime']) ? $snapshot['runtime'] : array();
    $health = isset($snapshot['health']) && is_array($snapshot['health']) ? $snapshot['health'] : array();
    $deliveries = isset($snapshot['deliveries']) && is_array($snapshot['deliveries']) ? $snapshot['deliveries'] : array();
    $whatsApp = isset($snapshot['whatsapp']) && is_array($snapshot['whatsapp']) ? $snapshot['whatsapp'] : array();

    $buildQueueRows = static function (array $items) use ($esc, $statusBadge, $renderDetails): array {
        $rows = array();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $rows[] = array(
                '<code>' . $esc((string) ($item['eventType'] ?? '')) . '</code>',
                '<div style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><code>' . $esc((string) ($item['eventId'] ?? '')) . '</code></div>',
                $statusBadge((string) ($item['bucket'] ?? '')),
                $esc((string) ($item['occurredAt'] ?? '')),
                $renderDetails($item),
            );
        }

        return $rows;
    };

    $deliveryRows = array();
    foreach ($deliveries as $delivery) {
        if (!is_array($delivery)) {
            continue;
        }

        $deliveryRows[] = array(
            $esc((string) ($delivery['recordedAt'] ?? '')),
            '<code>' . $esc((string) ($delivery['eventType'] ?? '')) . '</code>',
            $statusBadge((string) ($delivery['status'] ?? '')),
            '<div style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $esc((string) ($delivery['destination'] ?? ($delivery['eventId'] ?? ''))) . '</div>',
            '<code>' . $esc((string) ($delivery['provider'] ?? '-')) . '</code>',
            $renderDetails($delivery),
        );
    }

    $statusRows = array();
    foreach ((array) ($whatsApp['recentStatuses'] ?? array()) as $row) {
        if (!is_array($row)) {
            continue;
        }

        $statusRows[] = array(
            $esc((string) ($row['recordedAt'] ?? '')),
            $statusBadge((string) ($row['status'] ?? '')),
            '<code>' . $esc((string) ($row['wamid'] ?? '')) . '</code>',
            '<div style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><code>' . $esc((string) ($row['eventId'] ?? '')) . '</code></div>',
            $renderDetails($row),
        );
    }

    $trackedOutboundRows = array();
    foreach ((array) ($whatsApp['trackedOutbound'] ?? array()) as $row) {
        if (!is_array($row)) {
            continue;
        }

        $deliveryState = (string) ($row['deliveryState'] ?? '');
        $providerState = (string) ($row['providerMessageStatus'] ?? $row['providerStatus'] ?? '');
        $reason = (string) ($row['failureReason'] ?? '');
        $reasonLabel = '' !== $reason
            ? $esc($reason)
            : ('failed' === strtolower($deliveryState) ? 'No failure details yet.' : ('accepted' === strtolower($deliveryState) ? 'Accepted by Meta, waiting for delivery/read webhook.' : '-'));

        $trackedOutboundRows[] = array(
            $esc((string) ($row['updatedAt'] ?? $row['createdAt'] ?? '')),
            $statusBadge($deliveryState !== '' ? $deliveryState : $providerState),
            '<div style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><code>' . $esc((string) ($row['destination'] ?? '')) . '</code></div>',
            '<div style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><code>' . $esc((string) ($row['eventId'] ?? '')) . '</code></div>',
            '<code>' . $esc((string) ($row['wamid'] ?? '')) . '</code>',
            '<div style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $reasonLabel . '</div>',
            $renderDetails($row),
        );
    }

    $inboundRows = array();
    foreach ((array) ($whatsApp['recentInbound'] ?? array()) as $row) {
        if (!is_array($row)) {
            continue;
        }

        $textBody = trim((string) ($row['textBody'] ?? ''));
        $replyForm = '<form method="post" style="display:flex;gap:6px;align-items:flex-start;min-width:240px;">'
            . '<input type="hidden" name="key" value="' . $esc($providedOpsKey) . '">'
            . '<input type="hidden" name="action" value="reply_inbound_message">'
            . '<input type="hidden" name="message_id" value="' . $esc((string) ($row['messageId'] ?? '')) . '">'
            . '<input type="text" name="reply_body" placeholder="Reply text..." style="flex:1;min-width:120px;">'
            . '<button type="submit">Reply</button>'
            . '</form>';

        $inboundRows[] = array(
            $esc((string) ($row['recordedAt'] ?? '')),
            '<code>' . $esc((string) ($row['from'] ?? '')) . '</code>',
            $esc((string) ($row['type'] ?? '')),
            '<div style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $esc('' !== $textBody ? $textBody : '-') . '</div>',
            '<code>' . $esc((string) ($row['messageId'] ?? '')) . '</code>',
            $replyForm,
            $renderDetails($row),
        );
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Bornado Notification Ops</title></head><body style="font-family:Arial,sans-serif;background:#f7f7f7;color:#111;margin:0;padding:24px;">';
    echo '<div style="max-width:1100px;margin:0 auto;">';
    echo '<div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">';
    echo '<div><h1 style="margin-top:0;margin-bottom:8px;">Bornado Notification Ops</h1><p style="margin-top:0;color:#555;">Service-side dashboard for queue health, WhatsApp tracking, and runtime toggles. WordPress remains producer-only.</p></div>';
    echo '<form method="get" style="display:flex;gap:8px;align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><label style="font-size:13px;color:#666;">Rows <input type="number" name="limit" value="' . $esc((string) $currentLimit) . '" min="1" max="100" style="width:70px;"></label><button type="submit">Refresh</button></form>';
    echo '</div>';

    if (!empty($service['paused'])) {
        echo '<div style="margin:16px 0;padding:12px 16px;background:#fff3cd;border:1px solid #facc15;border-radius:8px;"><strong>Service is paused.</strong> New business events are rejected and consumer runs are skipped until you resume the service.</div>';
    }

    if (is_array($actionResult)) {
        echo '<div style="margin:16px 0;padding:12px 16px;background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;"><strong>Last action:</strong>' . $renderDetails($actionResult, 'View result') . '</div>';
    }

    echo '<div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;">';
    foreach (array('pending', 'processing', 'processed', 'failed') as $bucket) {
        echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><div style="font-size:13px;color:#666;">' . $esc(ucfirst($bucket)) . '</div><div style="font-size:28px;font-weight:bold;">' . $esc((string) ($queue[$bucket] ?? 0)) . '</div></div>';
    }
    echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><div style="font-size:13px;color:#666;">Service Mode</div><div style="font-size:20px;font-weight:bold;">' . $esc(!empty($service['paused']) ? 'Paused' : 'Running') . '</div></div>';
    echo '</div>';

    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">';
    echo '<h2 style="margin-top:0;">Queue Actions</h2>';
    echo '<form method="post" style="margin-bottom:12px;"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="run_consumer"><label>Limit <input type="number" name="limit" value="20" min="1" style="width:72px;"></label> <label><input type="checkbox" name="debug" value="1"> Debug</label> <button type="submit">Run consumer</button></form>';
    echo '<form method="post"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="requeue_failed"><label>Limit <input type="number" name="limit" value="20" min="1" style="width:72px;"></label> <button type="submit">Requeue failed</button></form>';
    echo '<form method="post" style="margin-top:12px;"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="clear_queue_bucket"><label>Bucket <select name="bucket"><option value="all_safe">all_safe</option><option value="pending">pending</option><option value="failed">failed</option><option value="processed">processed</option><option value="processing">processing</option></select></label> <label>Limit <input type="number" name="limit" value="1000" min="1" style="width:82px;"></label> <button type="submit" onclick="return confirm(\'Clear selected queue bucket?\')">Clear bucket</button></form>';
    echo '<p style="color:#666;margin-bottom:0;">JSON snapshot: <a href="?key=' . rawurlencode($providedOpsKey) . '&format=json&limit=' . rawurlencode((string) $currentLimit) . '">view</a></p>';
    echo '</section>';

    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">';
    echo '<h2 style="margin-top:0;">Service Control</h2>';
    echo '<form method="post" style="margin-bottom:12px;"><input type="hidden" name="key" value="' . $esc($providedOpsKey) . '"><input type="hidden" name="action" value="set_service_pause"><input type="hidden" name="paused" value="' . (!empty($service['paused']) ? '0' : '1') . '"><button type="submit">' . (!empty($service['paused']) ? 'Resume service' : 'Pause service') . '</button></form>';
    echo '<p style="color:#666;margin-top:0;">Pause blocks new event ingestion and consumer processing, but keeps the dashboard and webhook visibility online.</p>';
    echo '<h2 style="margin-top:16px;">Health</h2><table style="width:100%;border-collapse:collapse;">';
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
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Recent Deliveries</h2>' . $renderTable(
        array('Recorded', 'Event', 'Status', 'Target', 'Provider', 'Details'),
        $deliveryRows
    ) . '</section>';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">WhatsApp Delivery Tracking</h2><p style="color:#666;">`ACCEPTED` یعنی متا پیام را پذیرفته؛ برای اطمینان از رسیدن، باید `DELIVERED` یا `READ` را ببینی.</p>' . $renderTable(
        array('Updated', 'Delivery', 'Recipient', 'Event', 'WAMID', 'Reason', 'Details'),
        $trackedOutboundRows
    ) . '</section>';
    echo '</div>';

    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Pending Queue Items</h2>' . $renderTable(
        array('Event', 'Event ID', 'Bucket', 'Occurred', 'Details'),
        $buildQueueRows((array) ($queueItems['pending'] ?? array()))
    ) . '</section>';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Failed Queue Items</h2>' . $renderTable(
        array('Event', 'Event ID', 'Bucket', 'Occurred', 'Details'),
        $buildQueueRows((array) ($queueItems['failed'] ?? array()))
    ) . '</section>';
    echo '</div>';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-top:16px;"><h2 style="margin-top:0;">Processing Queue Items</h2>' . $renderTable(
        array('Event', 'Event ID', 'Bucket', 'Occurred', 'Details'),
        $buildQueueRows((array) ($queueItems['processing'] ?? array()))
    ) . '</section>';
    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Recent WhatsApp Statuses</h2>' . $renderTable(
        array('Recorded', 'Status', 'WAMID', 'Event', 'Details'),
        $statusRows
    ) . '</section>';
    echo '<section style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;"><h2 style="margin-top:0;">Recent Inbound Messages</h2>' . $renderTable(
        array('Recorded', 'From', 'Type', 'Text', 'Message ID', 'Reply', 'Details'),
        $inboundRows
    ) . '</section>';
    echo '</div>';
    echo '</div></body></html>';
    exit;
}

$respondJson(404, array('message' => 'Not Found'));
