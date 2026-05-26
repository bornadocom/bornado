<?php
declare(strict_types=1);

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

/**
 * @param string $value
 */
function bornado_notification_mask_secret($value) {
    $value = (string) $value;
    $length = strlen($value);

    if ($length <= 10) {
        return str_repeat('*', $length);
    }

    return substr($value, 0, 6) . str_repeat('*', max(0, $length - 12)) . substr($value, -6);
}

/**
 * @return array<string,mixed>
 */
function bornado_notification_http_get($url, $accessToken) {
    $headers = array(
        'Authorization: Bearer ' . trim((string) $accessToken),
    );

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
            )
        );

        $body       = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error      = curl_error($curl);
        curl_close($curl);

        if (false === $body) {
            return array(
                'ok'     => false,
                'status' => $statusCode,
                'body'   => array('message' => $error ?: 'cURL request failed.'),
            );
        }

        $decoded = json_decode($body, true);

        return array(
            'ok'     => $statusCode >= 200 && $statusCode < 300,
            'status' => $statusCode,
            'body'   => is_array($decoded) ? $decoded : array('raw' => $body),
        );
    }

    $context = stream_context_create(
        array(
            'http' => array(
                'method'        => 'GET',
                'header'        => implode("\r\n", $headers),
                'timeout'       => 10,
                'ignore_errors' => true,
            ),
            'ssl' => array(
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ),
        )
    );

    $body = @file_get_contents($url, false, $context);
    $statusCode = 0;

    if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $statusCode = (int) $matches[1];
    }

    $decoded = false !== $body ? json_decode($body, true) : null;

    return array(
        'ok'     => false !== $body && $statusCode >= 200 && $statusCode < 300,
        'status' => $statusCode,
        'body'   => is_array($decoded) ? $decoded : array('raw' => false !== $body ? $body : ''),
    );
}

$localOverridePath = __DIR__ . '/Services/bornado-notification-platform/config/notification-platform.local.php';
$providerConfig    = isset($config['providers']['whatsapp-cloud-api']) && is_array($config['providers']['whatsapp-cloud-api'])
    ? $config['providers']['whatsapp-cloud-api']
    : array();
$templateMap       = isset($providerConfig['template_map']['listing.published']) && is_array($providerConfig['template_map']['listing.published'])
    ? $providerConfig['template_map']['listing.published']
    : array();
$graphCheck        = null;

if (!empty($_GET['check_meta']) && !empty($providerConfig['phone_number_id']) && !empty($providerConfig['access_token'])) {
    $graphCheck = bornado_notification_http_get(
        'https://graph.facebook.com/' . rawurlencode((string) ($providerConfig['api_version'] ?? 'v22.0')) . '/' . rawurlencode((string) $providerConfig['phone_number_id']),
        (string) $providerConfig['access_token']
    );
}

$response = array(
    'service' => array(
        'base_url'      => (string) ($config['service']['base_url'] ?? ''),
        'shared_secret' => bornado_notification_mask_secret((string) ($config['service']['shared_secret'] ?? '')),
    ),
    'files' => array(
        'local_override_loaded' => is_file($localOverridePath),
        'local_override_path'   => 'Services/bornado-notification-platform/config/notification-platform.local.php',
        'local_override_sha1'   => is_file($localOverridePath) ? sha1_file($localOverridePath) : null,
        'local_override_mtime'  => is_file($localOverridePath) ? gmdate('c', (int) filemtime($localOverridePath)) : null,
    ),
    'routing' => array(
        'whatsapp_providers' => $config['routing']['channel_providers']['whatsapp'] ?? array(),
    ),
    'whatsapp' => array(
        'enabled'               => !empty($providerConfig['enabled']),
        'phone_number_id'       => (string) ($providerConfig['phone_number_id'] ?? ''),
        'access_token_masked'   => bornado_notification_mask_secret((string) ($providerConfig['access_token'] ?? '')),
        'message_mode'          => (string) ($providerConfig['message_mode'] ?? ''),
        'template_name'         => (string) ($templateMap['name'] ?? ''),
        'template_language'     => (string) ($templateMap['language_code'] ?? ''),
        'template_param_count'  => is_array($templateMap['body_parameters'] ?? null) ? count($templateMap['body_parameters']) : null,
    ),
    'meta_check' => $graphCheck,
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
