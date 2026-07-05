<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Infrastructure;

use Bornado\AiExtractionPlatform\Domain\CanonicalKey;
use RuntimeException;

final class WordPressRestCatalogSource
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
     * @param array<string,mixed> $marketConfig
     * @return array<string,mixed>
     */
    public function fetchCatalog(array $marketConfig): array
    {
        $wordpress = isset($this->config['wordpress']) && is_array($this->config['wordpress'])
            ? $this->config['wordpress']
            : array();
        $marketKey = trim((string) ($marketConfig['country_key'] ?? $marketConfig['country_code'] ?? ''));

        $catalogEndpoint = trim((string) ($wordpress['catalog_endpoint'] ?? ''));
        if ('' !== $catalogEndpoint) {
            $query = array(
                'market' => $marketKey,
                'channel' => 'instagram',
            );

            return $this->requestCatalogEndpoint(
                $this->appendQueryArgs(
                    $catalogEndpoint,
                    array_filter($query, static function ($value): bool {
                        return '' !== trim((string) $value);
                    })
                ),
                $wordpress
            );
        }

        $categories = $this->fetchAllTerms('ad_cats', $wordpress);
        $countries  = $this->fetchAllTerms('ad_country', $wordpress);
        $adType     = $this->fetchAllTerms('ad_type', $wordpress);
        $condition  = $this->fetchAllTerms('ad_condition', $wordpress);
        $warranty   = $this->fetchAllTerms('ad_warranty', $wordpress);

        $rootCountry = $this->selectRootCountry($countries, $marketConfig, $wordpress);
        if ('' !== $marketKey && empty($rootCountry)) {
            throw new RuntimeException(
                sprintf(
                    'Root country could not be resolved for market %s.',
                    $marketKey
                )
            );
        }
        $locations   = $this->buildLocations($countries, $rootCountry, $marketConfig);

        return array(
            'source' => array(
                'mode' => 'wordpress-rest',
                'baseUrl' => (string) ($wordpress['base_url'] ?? ''),
            ),
            'categories' => $this->mapTerms($categories),
            'locations' => $locations,
            'enums' => array(
                'ad_type' => $this->mapTerms($adType),
                'ad_condition' => $this->mapTerms($condition),
                'ad_warranty' => $this->mapTerms($warranty),
            ),
            'root_country' => $rootCountry,
        );
    }

    /**
     * @param array<string,mixed> $wordpress
     * @return array<int,array<string,mixed>>
     */
    private function fetchAllTerms(string $taxonomy, array $wordpress): array
    {
        $baseUrl = rtrim((string) ($wordpress['base_url'] ?? ''), '/');
        if ('' === $baseUrl) {
            return array();
        }

        $pageSize = max(1, (int) ($wordpress['page_size'] ?? 100));
        $page     = 1;
        $pages    = 1;
        $items    = array();

        do {
            $url = sprintf(
                '%s/wp-json/wp/v2/%s?per_page=%d&page=%d&_fields=id,name,slug,parent,meta',
                $baseUrl,
                rawurlencode($taxonomy),
                $pageSize,
                $page
            );

            $response = $this->requestJson($url, $wordpress);
            if (!is_array($response['body'])) {
                throw new RuntimeException(sprintf('Unexpected response for taxonomy %s', $taxonomy));
            }

            foreach ($response['body'] as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $items[] = $item;
                }
            }

            $pages = max(1, (int) ($response['headers']['x-wp-totalpages'] ?? 1));
            $page++;
        } while ($page <= $pages);

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $terms
     * @return array<int,array<string,mixed>>
     */
    private function mapTerms(array $terms): array
    {
        $mapped = array();

        foreach ($terms as $term) {
            $slug = trim((string) ($term['slug'] ?? ''));
            $name = trim((string) ($term['name'] ?? ''));
            $key  = CanonicalKey::fromString('' !== $slug ? $slug : $name);

            if ('' === $key) {
                continue;
            }

            $mapped[] = array(
                'key' => $key,
                'label' => $name,
                'slug' => $slug,
                'term_id' => (int) ($term['id'] ?? 0),
                'parent_term_id' => (int) ($term['parent'] ?? 0),
            );
        }

        return $mapped;
    }

    /**
     * @param array<int,array<string,mixed>> $countries
     * @param array<string,mixed> $marketConfig
     * @param array<string,mixed> $wordpress
     * @return array<string,mixed>
     */
    private function selectRootCountry(array $countries, array $marketConfig, array $wordpress): array
    {
        $targetCode      = strtoupper(trim((string) ($marketConfig['country_code'] ?? '')));
        $metaKey         = (string) ($wordpress['country_code_meta_key'] ?? '_bornado_country_code');

        foreach ($countries as $term) {
            if ((int) ($term['parent'] ?? 0) !== 0) {
                continue;
            }

            $meta = isset($term['meta']) && is_array($term['meta']) ? $term['meta'] : array();
            $code = strtoupper(trim((string) ($meta[$metaKey] ?? '')));

            if ('' !== $targetCode && '' !== $code && $code === $targetCode) {
                return $term;
            }
        }

        return array();
    }

    /**
     * @param array<int,array<string,mixed>> $countries
     * @param array<string,mixed> $rootCountry
     * @param array<string,mixed> $marketConfig
     * @return array<string,mixed>
     */
    private function buildLocations(array $countries, array $rootCountry, array $marketConfig): array
    {
        $rootId       = (int) ($rootCountry['id'] ?? 0);
        $aliasesByKey = isset($marketConfig['location_aliases']) && is_array($marketConfig['location_aliases'])
            ? $marketConfig['location_aliases']
            : array();

        if ($rootId < 1) {
            return array(
                'country' => array(),
                'cities' => array(),
            );
        }

        $rootKey = CanonicalKey::fromString((string) ($rootCountry['slug'] ?? $marketConfig['country_key'] ?? ''));
        $cities  = array();

        foreach ($countries as $term) {
            if ((int) ($term['parent'] ?? 0) !== $rootId) {
                continue;
            }

            $cityKey = CanonicalKey::fromString((string) ($term['slug'] ?? $term['name'] ?? ''));
            if ('' === $cityKey) {
                continue;
            }

            $cities[] = array(
                'key' => $cityKey,
                'label' => (string) ($term['name'] ?? ''),
                'slug' => (string) ($term['slug'] ?? ''),
                'term_id' => (int) ($term['id'] ?? 0),
                'geoname_id' => (int) ($term['meta']['_bornado_geo_source_id'] ?? 0),
                'country_key' => $rootKey,
                'aliases' => array_values(array_unique(array_filter(array_map('strval', (array) ($aliasesByKey[$cityKey] ?? array()))))),
            );
        }

        return array(
            'country' => array(
                'key' => $rootKey,
                'label' => (string) ($rootCountry['name'] ?? ''),
                'slug' => (string) ($rootCountry['slug'] ?? ''),
                'term_id' => $rootId,
            ),
            'cities' => $cities,
        );
    }

    /**
     * @param array<string,mixed> $wordpress
     * @return array{body:mixed,headers:array<string,string>}
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
            throw new RuntimeException(sprintf('Failed to fetch %s (%d)', $url, $statusCode));
        }

        $decoded = json_decode($raw, true);

        return array(
            'body' => $decoded,
            'headers' => $this->normalizeHeaders($responseHeaders),
        );
    }

    /**
     * @param array<string,mixed> $wordpress
     * @return array<string,mixed>
     */
    private function requestCatalogEndpoint(string $url, array $wordpress): array
    {
        $serviceKey = trim((string) ($wordpress['service_key'] ?? ''));
        $catalogConfig = $wordpress;

        // The bridge endpoint authenticates with the service key. Sending a
        // stale or invalid WordPress Basic Auth header can cause REST auth to
        // fail before the bridge permission callback runs.
        $catalogConfig['username'] = '';
        $catalogConfig['application_password'] = '';
        $catalogConfig['service_key'] = $serviceKey;

        $response = $this->requestJson($url, $catalogConfig);
        if (!is_array($response['body'])) {
            throw new RuntimeException('Catalog endpoint returned invalid JSON.');
        }

        return $response['body'];
    }

    /**
     * @param array<string,string> $args
     */
    private function appendQueryArgs(string $url, array $args): string
    {
        if (empty($args)) {
            return $url;
        }

        $separator = false === strpos($url, '?') ? '?' : '&';

        return $url . $separator . http_build_query($args);
    }

    /**
     * @param array<int,string> $headers
     * @return array<string,string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = array();

        foreach ($headers as $header) {
            if (false === strpos($header, ':')) {
                continue;
            }

            list($name, $value) = explode(':', $header, 2);
            $normalized[strtolower(trim($name))] = trim($value);
        }

        return $normalized;
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
