<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

use Bornado\NotificationPlatform\Contracts\EventCatalog;

final class WhatsAppCloudApiAdapter implements ProviderAdapterInterface
{
    /**
     * @var array<string,mixed>
     */
    private $config;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'whatsapp-cloud-api';
    }

    /**
     * @param array<string,mixed> $contact
     * @param array<string,string> $message
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function send(string $channel, array $contact, array $message, array $context): array
    {
        if ('whatsapp' !== $channel) {
            return array(
                'success' => false,
                'code'    => 'unsupported_channel',
                'message' => 'This provider only supports WhatsApp.',
            );
        }

        if (!$this->isEnabled()) {
            return array(
                'success' => false,
                'code'    => 'provider_disabled',
                'message' => 'WhatsApp Cloud API provider is disabled.',
            );
        }

        $destination = $this->normalizeDestination((string) ($contact['address'] ?? ''));
        if ('' === $destination) {
            return array(
                'success' => false,
                'code'    => 'invalid_destination',
                'message' => 'A valid WhatsApp destination is required.',
            );
        }

        $event     = isset($context['event']) && is_array($context['event']) ? $context['event'] : array();
        $eventType = (string) ($event['eventType'] ?? ($context['eventType'] ?? ''));
        $payload   = $this->buildPayload($destination, $eventType, $event, $message);

        if (isset($payload['_error'])) {
            return array(
                'success' => false,
                'code'    => (string) $payload['_error'],
                'message' => (string) ($payload['_message'] ?? 'Unable to build WhatsApp payload.'),
            );
        }

        $response = $this->postJson($this->buildEndpoint(), $payload);

        return array(
            'success'        => !empty($response['ok']),
            'code'           => !empty($response['ok']) ? 'provider_accepted' : 'provider_rejected',
            'provider'       => $this->getName(),
            'httpStatus'     => (int) ($response['status'] ?? 0),
            'response'       => $response['body'] ?? null,
            'requestPayload' => $payload,
        );
    }

    private function isEnabled(): bool
    {
        return !empty($this->config['enabled'])
            && '' !== trim((string) ($this->config['access_token'] ?? ''))
            && '' !== trim((string) ($this->config['phone_number_id'] ?? ''));
    }

    private function buildEndpoint(): string
    {
        $baseUrl       = rtrim((string) ($this->config['base_url'] ?? 'https://graph.facebook.com'), '/');
        $apiVersion    = trim((string) ($this->config['api_version'] ?? 'v22.0'));
        $phoneNumberId = trim((string) ($this->config['phone_number_id'] ?? ''));

        return $baseUrl . '/' . $apiVersion . '/' . $phoneNumberId . '/messages';
    }

    /**
     * @param array<string,mixed> $event
     * @param array<string,string> $message
     * @return array<string,mixed>
     */
    private function buildPayload(string $destination, string $eventType, array $event, array $message): array
    {
        $mode          = trim((string) ($this->config['message_mode'] ?? 'template'));
        $templateMap   = isset($this->config['template_map']) && is_array($this->config['template_map']) ? $this->config['template_map'] : array();
        $templateSpec  = isset($templateMap[$eventType]) && is_array($templateMap[$eventType]) ? $templateMap[$eventType] : array();
        $textFallback  = !empty($this->config['text_fallback_enabled']);
        $body          = trim((string) ($message['body'] ?? ''));

        if ('template' === $mode && !empty($templateSpec['name'])) {
            return $this->buildTemplatePayload($destination, $templateSpec, $event);
        }

        if ('text' === $mode && '' !== $body) {
            return $this->buildTextPayload($destination, $body);
        }

        if ($textFallback && '' !== $body) {
            return $this->buildTextPayload($destination, $body);
        }

        return array(
            '_error'   => 'missing_message_configuration',
            '_message' => 'No usable WhatsApp template or text body was available.',
        );
    }

    /**
     * @param array<string,mixed> $templateSpec
     * @param array<string,mixed> $event
     * @return array<string,mixed>
     */
    private function buildTemplatePayload(string $destination, array $templateSpec, array $event): array
    {
        $languageCode  = trim((string) ($templateSpec['language_code'] ?? $this->config['default_language_code'] ?? 'fa'));
        $bodyPaths     = isset($templateSpec['body_parameters']) && is_array($templateSpec['body_parameters']) ? $templateSpec['body_parameters'] : array();
        $bodyParams    = array();

        foreach ($bodyPaths as $path) {
            $value = EventCatalog::getByPath($event, (string) $path);
            $bodyParams[] = array(
                'type' => 'text',
                'text' => is_scalar($value) ? (string) $value : '',
            );
        }

        $components = array();
        if (!empty($bodyParams)) {
            $components[] = array(
                'type'       => 'body',
                'parameters' => $bodyParams,
            );
        }

        $template = array(
            'name'     => (string) $templateSpec['name'],
            'language' => array(
                'code' => $languageCode,
            ),
        );

        if (!empty($components)) {
            $template['components'] = $components;
        }

        return array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $destination,
            'type'              => 'template',
            'template'          => $template,
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function buildTextPayload(string $destination, string $body): array
    {
        return array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $destination,
            'type'              => 'text',
            'text'              => array(
                'preview_url' => true,
                'body'        => $body,
            ),
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function postJson(string $url, array $payload): array
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $jsonPayload) {
            return array(
                'ok'     => false,
                'status' => 0,
                'body'   => array('message' => 'Unable to encode request payload.'),
            );
        }

        if (function_exists('curl_init')) {
            return $this->postWithCurl($url, $jsonPayload);
        }

        return $this->postWithStream($url, $jsonPayload);
    }

    /**
     * @return array<string,mixed>
     */
    private function postWithCurl(string $url, string $jsonPayload): array
    {
        $timeout   = (int) ($this->config['timeout_seconds'] ?? 10);
        $curl      = curl_init($url);
        $headers   = array(
            'Authorization: Bearer ' . trim((string) ($this->config['access_token'] ?? '')),
            'Content-Type: application/json',
        );

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => $jsonPayload,
                CURLOPT_SSL_VERIFYPEER => !empty($this->config['verify_ssl']),
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

        return array(
            'ok'     => $statusCode >= 200 && $statusCode < 300,
            'status' => $statusCode,
            'body'   => $this->decodeResponseBody($body),
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function postWithStream(string $url, string $jsonPayload): array
    {
        $timeout = (int) ($this->config['timeout_seconds'] ?? 10);
        $headers = implode(
            "\r\n",
            array(
                'Authorization: Bearer ' . trim((string) ($this->config['access_token'] ?? '')),
                'Content-Type: application/json',
            )
        );

        $context = stream_context_create(
            array(
                'http' => array(
                    'method'        => 'POST',
                    'header'        => $headers,
                    'content'       => $jsonPayload,
                    'timeout'       => $timeout,
                    'ignore_errors' => true,
                ),
                'ssl' => array(
                    'verify_peer'      => !empty($this->config['verify_ssl']),
                    'verify_peer_name' => !empty($this->config['verify_ssl']),
                ),
            )
        );

        $body = @file_get_contents($url, false, $context);

        $statusCode = 0;
        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $statusCode = (int) $matches[1];
        }

        return array(
            'ok'     => false !== $body && $statusCode >= 200 && $statusCode < 300,
            'status' => $statusCode,
            'body'   => false !== $body ? $this->decodeResponseBody($body) : array('message' => 'HTTP stream request failed.'),
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeResponseBody(string $body): array
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return array(
            'raw' => $body,
        );
    }

    private function normalizeDestination(string $address): string
    {
        $address = trim($address);
        $address = preg_replace('/[^\d+]/', '', $address) ?? '';

        if ('' === $address) {
            return '';
        }

        return ltrim($address, '+');
    }
}
