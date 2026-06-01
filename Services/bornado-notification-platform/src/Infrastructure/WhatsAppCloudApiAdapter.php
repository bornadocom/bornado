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
        $payload   = $this->buildPayload($destination, $eventType, $event, $message, $context);

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
            'messageId'      => $this->extractMessageId($response['body'] ?? array()),
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
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function buildPayload(string $destination, string $eventType, array $event, array $message, array $context = array()): array
    {
        $mode          = trim((string) ($this->config['message_mode'] ?? 'template'));
        $templateMap   = isset($this->config['template_map']) && is_array($this->config['template_map']) ? $this->config['template_map'] : array();
        $templateSpec  = isset($templateMap[$eventType]) && is_array($templateMap[$eventType]) ? $templateMap[$eventType] : array();
        $textFallback  = !empty($this->config['text_fallback_enabled']);
        $body          = trim((string) ($message['body'] ?? ''));
        $replyToMessageId = trim((string) ($context['replyToMessageId'] ?? ''));

        if ('template' === $mode && !empty($templateSpec['name'])) {
            return $this->buildTemplatePayload($destination, $templateSpec, $event);
        }

        if ('text' === $mode && '' !== $body) {
            return $this->buildTextPayload($destination, $body, $replyToMessageId);
        }

        if ($textFallback && '' !== $body) {
            return $this->buildTextPayload($destination, $body, $replyToMessageId);
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
        $headerPaths   = isset($templateSpec['header_parameters']) && is_array($templateSpec['header_parameters']) ? $templateSpec['header_parameters'] : array();
        $bodyPaths     = isset($templateSpec['body_parameters']) && is_array($templateSpec['body_parameters']) ? $templateSpec['body_parameters'] : array();
        $headerParams  = $this->buildTextParameters($headerPaths, $event);
        $bodyParams    = $this->buildTextParameters($bodyPaths, $event);

        $components = array();
        $headerComponent = $this->buildHeaderComponent($templateSpec, $event, $headerParams);
        if (!empty($headerComponent)) {
            $components[] = $headerComponent;
        }

        if (!empty($bodyParams)) {
            $components[] = array(
                'type'       => 'body',
                'parameters' => $bodyParams,
            );
        }

        $buttonComponents = $this->buildButtonComponents($templateSpec, $event);
        if (!empty($buttonComponents)) {
            $components = array_merge($components, $buttonComponents);
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
     * @param array<string,mixed> $templateSpec
     * @param array<string,mixed> $event
     * @param array<int,array<string,string>> $headerParams
     * @return array<string,mixed>
     */
    private function buildHeaderComponent(array $templateSpec, array $event, array $headerParams): array
    {
        $headerMedia = isset($templateSpec['header_media']) && is_array($templateSpec['header_media'])
            ? $templateSpec['header_media']
            : array();

        $mediaType = strtolower(trim((string) ($headerMedia['type'] ?? '')));
        if ('' !== $mediaType) {
            $mediaLink = trim((string) ($headerMedia['link'] ?? ''));
            $mediaPath = trim((string) ($headerMedia['path'] ?? ''));

            if ('' === $mediaLink && '' !== $mediaPath) {
                $mediaValue = EventCatalog::getByPath($event, $mediaPath);
                $mediaLink = is_scalar($mediaValue) ? trim((string) $mediaValue) : '';
            }

            if ('' !== $mediaLink && in_array($mediaType, array('image', 'video', 'document'), true)) {
                return array(
                    'type'       => 'header',
                    'parameters' => array(
                        array(
                            'type'  => $mediaType,
                            $mediaType => array(
                                'link' => $mediaLink,
                            ),
                        ),
                    ),
                );
            }
        }

        if (!empty($headerParams)) {
            return array(
                'type'       => 'header',
                'parameters' => $headerParams,
            );
        }

        return array();
    }

    /**
     * @param array<string,mixed> $templateSpec
     * @param array<string,mixed> $event
     * @return array<int,array<string,mixed>>
     */
    private function buildButtonComponents(array $templateSpec, array $event): array
    {
        $buttonSpecs = isset($templateSpec['button_parameters']) && is_array($templateSpec['button_parameters'])
            ? $templateSpec['button_parameters']
            : array();
        $components = array();

        foreach ($buttonSpecs as $buttonSpec) {
            if (!is_array($buttonSpec)) {
                continue;
            }

            $paths = isset($buttonSpec['parameters']) && is_array($buttonSpec['parameters'])
                ? $buttonSpec['parameters']
                : array();
            $parameters = $this->buildTextParameters($paths, $event);

            if (empty($parameters)) {
                continue;
            }

            $components[] = array(
                'type'       => 'button',
                'sub_type'   => (string) ($buttonSpec['sub_type'] ?? 'url'),
                'index'      => (string) ($buttonSpec['index'] ?? '0'),
                'parameters' => $parameters,
            );
        }

        return $components;
    }

    /**
     * @param array<int,mixed> $paths
     * @param array<string,mixed> $event
     * @return array<int,array<string,string>>
     */
    private function buildTextParameters(array $paths, array $event): array
    {
        $parameters = array();

        foreach ($paths as $parameterSpec) {
            $resolvedSpec = $this->resolveParameterSpec($parameterSpec);
            if ('' === $resolvedSpec['path']) {
                continue;
            }

            $value = EventCatalog::getByPath($event, $resolvedSpec['path']);
            $parameter = array(
                'type' => 'text',
                'text' => is_scalar($value) ? (string) $value : '',
            );

            if ('' !== $resolvedSpec['name']) {
                $parameter['parameter_name'] = $resolvedSpec['name'];
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param mixed $parameterSpec
     * @return array{name:string,path:string}
     */
    private function resolveParameterSpec($parameterSpec): array
    {
        if (is_string($parameterSpec)) {
            return array(
                'name' => '',
                'path' => trim($parameterSpec),
            );
        }

        if (!is_array($parameterSpec)) {
            return array(
                'name' => '',
                'path' => '',
            );
        }

        return array(
            'name' => trim((string) ($parameterSpec['name'] ?? $parameterSpec['parameter_name'] ?? '')),
            'path' => trim((string) ($parameterSpec['path'] ?? '')),
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function buildTextPayload(string $destination, string $body, string $replyToMessageId = ''): array
    {
        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $destination,
            'type'              => 'text',
            'text'              => array(
                'preview_url' => true,
                'body'        => $body,
            ),
        );

        if ('' !== $replyToMessageId) {
            $payload['context'] = array(
                'message_id' => $replyToMessageId,
            );
        }

        return $payload;
    }

    /**
     * @return array<string,mixed>
     */
    public function sendTextMessage(string $destination, string $body, string $replyToMessageId = ''): array
    {
        $destination = $this->normalizeDestination($destination);
        if ('' === $destination) {
            return array(
                'success' => false,
                'code'    => 'invalid_destination',
                'message' => 'A valid WhatsApp destination is required.',
            );
        }

        if (!$this->isEnabled()) {
            return array(
                'success' => false,
                'code'    => 'provider_disabled',
                'message' => 'WhatsApp Cloud API provider is disabled.',
            );
        }

        $payload = $this->buildTextPayload($destination, trim($body), $replyToMessageId);
        $response = $this->postJson($this->buildEndpoint(), $payload);

        return array(
            'success'        => !empty($response['ok']),
            'code'           => !empty($response['ok']) ? 'provider_accepted' : 'provider_rejected',
            'provider'       => $this->getName(),
            'httpStatus'     => (int) ($response['status'] ?? 0),
            'response'       => $response['body'] ?? null,
            'messageId'      => $this->extractMessageId($response['body'] ?? array()),
            'requestPayload' => $payload,
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

    /**
     * @param mixed $responseBody
     */
    private function extractMessageId($responseBody): string
    {
        if (!is_array($responseBody)) {
            return '';
        }

        $messages = $responseBody['messages'] ?? null;
        if (!is_array($messages) || !isset($messages[0]) || !is_array($messages[0])) {
            return '';
        }

        return trim((string) ($messages[0]['id'] ?? ''));
    }
}
