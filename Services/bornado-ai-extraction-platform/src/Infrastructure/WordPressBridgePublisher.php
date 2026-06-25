<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Infrastructure;

use RuntimeException;

final class WordPressBridgePublisher
{
    /** @var array<string,mixed> */
    private $config;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string,mixed> $record
     * @return array<string,mixed>
     */
    public function publish(array $record): array
    {
        $wordpress = isset($this->config['source']['wordpress']) && is_array($this->config['source']['wordpress'])
            ? $this->config['source']['wordpress']
            : array();
        $target = isset($this->config['target']['wordpress_bridge']) && is_array($this->config['target']['wordpress_bridge'])
            ? $this->config['target']['wordpress_bridge']
            : array();

        $endpoint = trim((string) ($target['ingest_endpoint'] ?? ''));
        if ('' === $endpoint) {
            $catalogEndpoint = trim((string) ($wordpress['catalog_endpoint'] ?? ''));
            if ('' !== $catalogEndpoint) {
                $endpoint = preg_replace('~/catalog(?:\?.*)?$~', '/ingest', $catalogEndpoint) ?: '';
            }
        }

        if ('' === $endpoint) {
            throw new RuntimeException('WordPress bridge ingest endpoint is not configured.');
        }

        $serviceKey = trim((string) ($target['service_key'] ?? ($wordpress['service_key'] ?? '')));
        if ('' !== $serviceKey) {
            $separator = false === strpos($endpoint, '?') ? '?' : '&';
            $endpoint .= $separator . http_build_query(array('key' => $serviceKey));
        }

        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
        );
        if ('' !== $serviceKey) {
            $headers[] = 'X-Bornado-Service-Key: ' . $serviceKey;
        }

        $payload = json_encode(
            array('record' => $record),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (!is_string($payload) || '' === $payload) {
            throw new RuntimeException('Failed to encode ingest payload.');
        }

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'timeout' => max(1, (int) ($target['timeout_seconds'] ?? $wordpress['timeout_seconds'] ?? 12)),
                    'content' => $payload,
                    'ignore_errors' => true,
                ),
            )
        );

        $raw = @file_get_contents($endpoint, false, $context);
        $responseHeaders = isset($http_response_header) && is_array($http_response_header)
            ? $http_response_header
            : array();
        $statusCode = $this->parseStatusCode($responseHeaders);

        if (false === $raw || $statusCode >= 400) {
            throw new RuntimeException(sprintf('Failed to publish to %s (%d)', $endpoint, $statusCode));
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Bridge ingest endpoint returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @param array<int,string> $headers
     */
    private function parseStatusCode(array $headers): int
    {
        $statusLine = isset($headers[0]) ? (string) $headers[0] : '';
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}

// cache-bust 2026-06-25-01