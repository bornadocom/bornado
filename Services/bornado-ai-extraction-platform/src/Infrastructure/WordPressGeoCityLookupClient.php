<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Infrastructure;

use RuntimeException;

final class WordPressGeoCityLookupClient
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
     * @return array<string,mixed>
     */
    public function resolveCity(string $countryKeyOrIso2, string $cityQuery = '', int $geonameId = 0): array
    {
        $countryKeyOrIso2 = strtoupper(trim($countryKeyOrIso2));
        $cityQuery = trim($cityQuery);
        $geonameId = max(0, $geonameId);

        if ('' === $countryKeyOrIso2 || ('' === $cityQuery && $geonameId < 1)) {
            return array(
                'resolved' => false,
                'ambiguous' => false,
            );
        }

        $endpoint = $this->resolveEndpoint();
        if ('' === $endpoint) {
            return array(
                'resolved' => false,
                'ambiguous' => false,
            );
        }

        $query = array(
            'country_iso2' => $countryKeyOrIso2,
        );
        if ('' !== $cityQuery) {
            $query['city_key'] = $cityQuery;
        }
        if ($geonameId > 0) {
            $query['geoname_id'] = $geonameId;
        }

        $wordpressConfig = $this->getWordPressConfig();
        $serviceKey = trim((string) ($wordpressConfig['service_key'] ?? ''));
        if ('' !== $serviceKey) {
            // Bridge routes authenticate with the service key. Sending a stale
            // Basic Auth header can cause WordPress REST auth to fail before
            // the bridge permission callback runs.
            $wordpressConfig['username'] = '';
            $wordpressConfig['application_password'] = '';
        }

        $response = $this->requestJson(
            $this->appendQueryArgs($endpoint, $query),
            $wordpressConfig
        );

        if (!is_array($response['body'])) {
            throw new RuntimeException('Geo city lookup returned invalid JSON.');
        }

        return $response['body'];
    }

    private function resolveEndpoint(): string
    {
        $wordpress = $this->getWordPressConfig();
        $endpoint = trim((string) ($wordpress['geo_city_lookup_endpoint'] ?? ''));
        if ('' !== $endpoint) {
            return $endpoint;
        }

        $catalogEndpoint = trim((string) ($wordpress['catalog_endpoint'] ?? ''));
        if ('' !== $catalogEndpoint) {
            return $this->replaceCatalogPath($catalogEndpoint, '/geo-city-lookup');
        }

        $baseUrl = rtrim((string) ($wordpress['base_url'] ?? ''), '/');
        if ('' === $baseUrl) {
            return '';
        }

        return $baseUrl . '/wp-json/bornado-ai-bridge/v1/geo-city-lookup';
    }

    private function replaceCatalogPath(string $url, string $replacementPath): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        if ('' === $path) {
            return '';
        }

        $newPath = preg_replace('~/catalog$~', $replacementPath, $path);
        if (!is_string($newPath) || '' === $newPath) {
            return '';
        }

        $rebuilt = '';
        if (isset($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $rebuilt .= $parts['user'];
            if (isset($parts['pass'])) {
                $rebuilt .= ':' . $parts['pass'];
            }
            $rebuilt .= '@';
        }
        if (isset($parts['host'])) {
            $rebuilt .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $rebuilt .= $newPath;

        if (isset($parts['query']) && '' !== (string) $parts['query']) {
            $rebuilt .= '?' . $parts['query'];
        }
        if (isset($parts['fragment']) && '' !== (string) $parts['fragment']) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    /**
     * @param array<string,mixed> $wordpress
     * @return array{body:mixed,headers:array<int,string>,status_code:int}
     */
    private function requestJson(string $url, array $wordpress): array
    {
        $headers = array('Accept: application/json');

        $username = trim((string) ($wordpress['username'] ?? ''));
        $password = trim((string) ($wordpress['application_password'] ?? ''));
        $serviceKey = trim((string) ($wordpress['service_key'] ?? ''));
        if ('' !== $username && '' !== $password) {
            $headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
        }
        if ('' !== $serviceKey) {
            $headers[] = 'X-Bornado-Service-Key: ' . $serviceKey;
        }

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'timeout' => max(1, (int) ($wordpress['timeout_seconds'] ?? 12)),
                    'ignore_errors' => true,
                ),
            )
        );

        $raw = @file_get_contents($url, false, $context);
        $responseHeaders = isset($http_response_header) && is_array($http_response_header)
            ? $http_response_header
            : array();
        $statusCode = $this->parseStatusCode($responseHeaders);

        if (false === $raw || $statusCode >= 400) {
            throw new RuntimeException(sprintf('Geo city lookup failed for %s (%d).', $url, $statusCode));
        }

        return array(
            'body' => json_decode($raw, true),
            'headers' => $responseHeaders,
            'status_code' => $statusCode,
        );
    }

    /**
     * @param array<string,mixed> $query
     */
    private function appendQueryArgs(string $url, array $query): string
    {
        $separator = false === strpos($url, '?') ? '?' : '&';

        return $url . $separator . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
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

    /**
     * @return array<string,mixed>
     */
    private function getWordPressConfig(): array
    {
        return isset($this->config['wordpress']) && is_array($this->config['wordpress'])
            ? $this->config['wordpress']
            : array();
    }
}
