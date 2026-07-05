<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Application;

use Bornado\AiExtractionPlatform\Domain\CanonicalKey;
use Bornado\AiExtractionPlatform\Infrastructure\WordPressGeoCityLookupClient;

final class ResolverService
{
    /** @var WordPressGeoCityLookupClient|null */
    private $geoCityLookup;

    public function __construct(?WordPressGeoCityLookupClient $geoCityLookup = null)
    {
        $this->geoCityLookup = $geoCityLookup;
    }

    /**
     * @param array<string,mixed> $schema
     * @param array<string,mixed> $extraction
     * @return array<string,mixed>
     */
    public function resolve(array $schema, array $extraction): array
    {
        $errors = array();

        $locations = (array) ($schema['locations'] ?? array());
        $categoryIndex = $this->buildIndex((array) ($schema['categories'] ?? array()));
        $locationIndex = $this->buildIndex($locations);
        $locationByGeonameId = $this->buildLocationGeonameIndex($locations);

        $marketCountryKey = $this->normalizeEntityKey((string) ($schema['market']['country']['key'] ?? ''));
        $marketCountryId  = (int) ($schema['market']['country']['term_id'] ?? 0);
        $marketCountryIso2 = strtoupper($marketCountryKey);

        $categoryKey = $this->normalizeEntityKey((string) ($extraction['category_key'] ?? ''));
        $countryKey  = $this->normalizeEntityKey((string) ($extraction['country_key'] ?? ''));
        $cityKey     = $this->normalizeEntityKey((string) ($extraction['city_key'] ?? ''));
        $locationSource = $this->normalizeLocationSource((string) ($extraction['location_source'] ?? ''));
        $locationEvidence = trim((string) ($extraction['location_evidence'] ?? ''));
        $defaultCountryKey = $this->normalizeEntityKey((string) ($extraction['default_country_key'] ?? ''));
        $defaultCityGeonameId = $this->extractFallbackGeonameId($extraction);
        $exactAddress = trim((string) ($extraction['exact_address'] ?? ''));

        $resolvedCategoryId = 0;
        if ('' !== $categoryKey) {
            if (isset($categoryIndex[$categoryKey])) {
                $resolvedCategoryId = (int) ($categoryIndex[$categoryKey]['term_id'] ?? 0);
            } else {
                $errors[] = sprintf('Unknown category_key: %s', $categoryKey);
            }
        } else {
            $errors[] = 'Missing category_key.';
        }

        if ('' === $countryKey && '' !== $defaultCountryKey) {
            $countryKey = $defaultCountryKey;
        }
        if ('' !== $countryKey && '' !== $marketCountryKey && $countryKey !== $marketCountryKey) {
            $errors[] = sprintf('country_key %s is outside the configured market.', $countryKey);
        }

        $cityResolution = $this->resolveCityReference(
            $locationIndex,
            $locationByGeonameId,
            '' !== $countryKey ? $countryKey : $marketCountryKey,
            $cityKey,
            $defaultCityGeonameId
        );
        $cityKey = (string) ($cityResolution['city_key'] ?? '');
        $resolvedCityId = (int) ($cityResolution['term_id'] ?? 0);
        $resolvedCityGeoNameId = (int) ($cityResolution['geoname_id'] ?? 0);
        $resolvedCityPayload = isset($cityResolution['payload']) && is_array($cityResolution['payload'])
            ? $cityResolution['payload']
            : array();

        if (
            '' !== $countryKey &&
            '' !== $exactAddress &&
            (
                '' === $cityKey ||
                !$this->resolvedCityMatchesText($resolvedCityPayload, $exactAddress) ||
                $this->isWeakLocationSource($locationSource)
            )
        ) {
            $exactAddressResolution = $this->resolveCityFromExactAddress(
                $locationIndex,
                $locationByGeonameId,
                $countryKey,
                $exactAddress
            );
            $exactAddressCityKey = (string) ($exactAddressResolution['city_key'] ?? '');
            if ('' !== $exactAddressCityKey && ('' === $cityKey || $exactAddressCityKey !== $cityKey)) {
                $cityResolution = $exactAddressResolution;
                $cityKey = $exactAddressCityKey;
                $resolvedCityId = (int) ($cityResolution['term_id'] ?? 0);
                $resolvedCityGeoNameId = (int) ($cityResolution['geoname_id'] ?? 0);
                $resolvedCityPayload = isset($cityResolution['payload']) && is_array($cityResolution['payload'])
                    ? $cityResolution['payload']
                    : array();
                $locationSource = 'ad_content';
                if ('' === $locationEvidence) {
                    $locationEvidence = $exactAddress;
                }
            }
        }

        if ('' === $countryKey) {
            $countryKey = $this->normalizeEntityKey((string) ($resolvedCityPayload['country_key'] ?? ''));
        }

        $status = $this->normalizeModerationStatus((string) ($extraction['status'] ?? 'pending'));
        if ('' === $status) {
            $status = 'pending';
        }

        if ('' === $countryKey) {
            $status = 'rejected';
            if (!$this->hasNonEmptyValue($extraction['reason'] ?? null)) {
                $extraction['reason'] = 'کشور آگهی از متن یا داده‌های ورودی قابل تشخیص نبود.';
            }
        }

        $primaryContactInput = $extraction['primary_contact'] ?? null;
        $primaryContact = $this->normalizePhoneContact($primaryContactInput);
        if ($this->hasNonEmptyValue($primaryContactInput) && null === $primaryContact) {
            $errors[] = 'primary_contact must contain only one normalized phone number.';
        }
        if ($this->requiresPrimaryContact($status) && null === $primaryContact) {
            $errors[] = 'Approved records require a valid primary_contact phone number.';
        }

        $secondaryContacts = array();
        foreach ((array) ($extraction['secondary_contacts'] ?? array()) as $index => $secondaryContactInput) {
            if (!$this->hasNonEmptyValue($secondaryContactInput)) {
                continue;
            }

            $secondaryContact = $this->normalizePhoneContact($secondaryContactInput);
            if (null === $secondaryContact) {
                $errors[] = sprintf('secondary_contacts[%d] must contain only normalized phone numbers.', (int) $index);
                continue;
            }

            if ($secondaryContact === $primaryContact || in_array($secondaryContact, $secondaryContacts, true)) {
                continue;
            }

            $secondaryContacts[] = $secondaryContact;
        }

        $dynamicFieldsInput = $this->collectDynamicFieldsInput($schema, $categoryKey, $extraction);
        $categoryFields = isset($schema['fields']['by_category'][$categoryKey]) && is_array($schema['fields']['by_category'][$categoryKey])
            ? $schema['fields']['by_category'][$categoryKey]
            : array();

        $resolvedCountryKey = '' !== $countryKey ? $countryKey : $marketCountryKey;
        $resolvedCountryIso2 = strtoupper($resolvedCountryKey);
        $resolvedCountryTermId = ('' !== $resolvedCountryKey && $resolvedCountryKey === $marketCountryKey)
            ? $marketCountryId
            : 0;

        $countryTerms = array();
        if ($resolvedCountryTermId > 0) {
            $countryTerms[] = $resolvedCountryTermId;
        }
        if ($resolvedCityId > 0) {
            $countryTerms[] = $resolvedCityId;
        }

        $taxonomies = array(
            'ad_cats' => $resolvedCategoryId > 0 ? array($resolvedCategoryId) : array(),
            'ad_country' => $countryTerms,
        );
        $meta = array(
            '_adforest_poster_contact' => $primaryContact ?? '',
            '_adforest_ad_location' => $exactAddress,
            '_bornado_secondary_contacts' => $secondaryContacts,
            '_bornado_ai_reason' => $extraction['reason'] ?? null,
            '_bornado_ai_schema_version' => (string) ($schema['schema_version'] ?? ''),
        );
        $dynamicMeta = array();
        $resolvedDynamicFields = array();

        foreach ($categoryFields as $fieldDescriptor) {
            if (!is_array($fieldDescriptor)) {
                continue;
            }

            $fieldKey = trim((string) ($fieldDescriptor['field_key'] ?? ''));
            if ('' === $fieldKey) {
                continue;
            }

            $fieldValue = array_key_exists($fieldKey, $dynamicFieldsInput)
                ? $dynamicFieldsInput[$fieldKey]
                : $this->getAutoDefaultFieldValue($fieldDescriptor, $schema);
            if (null === $fieldValue && !array_key_exists($fieldKey, $dynamicFieldsInput)) {
                continue;
            }

            $resolvedValue = $this->resolveFieldValue($fieldDescriptor, $fieldValue, $errors, $schema);
            if (!$resolvedValue['resolved']) {
                continue;
            }

            $resolvedDynamicFields[$fieldKey] = $resolvedValue['public_value'];

            $storage = isset($fieldDescriptor['storage']) && is_array($fieldDescriptor['storage'])
                ? $fieldDescriptor['storage']
                : array();
            $storageKind = (string) ($storage['kind'] ?? '');

            if ('post_meta' === $storageKind) {
                $storageKey = trim((string) ($storage['key'] ?? ''));
                if ('' !== $storageKey) {
                    $dynamicMeta[$storageKey] = $resolvedValue['storage_value'];
                }
            } elseif ('taxonomy_meta' === $storageKind) {
                $taxonomy = trim((string) ($storage['taxonomy'] ?? ''));
                $metaKey = trim((string) ($storage['meta_key'] ?? ''));
                if ('' !== $taxonomy && !empty($resolvedValue['term_ids'])) {
                    $taxonomies[$taxonomy] = array_values(array_map('intval', (array) $resolvedValue['term_ids']));
                }
                if ('' !== $metaKey) {
                    $meta[$metaKey] = $resolvedValue['storage_value'];
                }
            }
        }

        $seoTitle = trim((string) ($extraction['seo_title'] ?? ''));
        $finalText = trim((string) ($extraction['final_ad_text'] ?? ''));
        $slug = trim((string) ($extraction['slug'] ?? ''));
        $title = '' !== $seoTitle ? $seoTitle : $this->truncateText($finalText, 80);
        if ('' === $slug) {
            $slug = $title;
        }

        return array(
            'resolution_status' => empty($errors) ? 'resolved' : 'invalid',
            'ingest_ready' => empty($errors),
            'errors' => $errors,
            'trace' => array(
                'schema_version' => (string) ($schema['schema_version'] ?? ''),
                'category_key' => $categoryKey,
                'country_key' => $countryKey,
                'city_key' => $cityKey,
                'location_source' => '' !== $locationSource ? $locationSource : null,
                'location_evidence' => '' !== $locationEvidence ? $locationEvidence : null,
                'default_country_key' => '' !== $defaultCountryKey ? $defaultCountryKey : null,
                'default_city_geoname_id' => $defaultCityGeonameId > 0 ? $defaultCityGeonameId : null,
                'resolved_city_geoname_id' => $resolvedCityGeoNameId > 0 ? $resolvedCityGeoNameId : null,
                'resolved_dynamic_fields' => $resolvedDynamicFields,
            ),
            'target_payload' => array(
                'wordpress_bridge' => array(
                    'status' => $status,
                    'post' => array(
                        'title' => $title,
                        'content' => $finalText,
                        'slug' => $slug,
                    ),
                    'taxonomies' => $taxonomies,
                    'geo_location' => array(
                        'country_iso2' => $resolvedCountryIso2,
                        'country_key' => $resolvedCountryKey,
                        'country_term_id' => $resolvedCountryTermId,
                        'city_key' => '' !== $cityKey ? $cityKey : null,
                        'city_label' => '' !== trim((string) ($resolvedCityPayload['label'] ?? '')) ? (string) $resolvedCityPayload['label'] : null,
                        'city_term_id' => $resolvedCityId > 0 ? $resolvedCityId : null,
                        'city_geoname_id' => $resolvedCityGeoNameId > 0 ? $resolvedCityGeoNameId : null,
                    ),
                    'meta' => $meta,
                    'dynamic_meta' => $dynamicMeta,
                    'flags' => array(
                        'clear_dynamic_meta' => true,
                    ),
                ),
            ),
        );
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,array<string,mixed>>
     */
    private function buildIndex(array $items): array
    {
        $index = array();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = $this->normalizeEntityKey((string) ($item['key'] ?? ''));
            if ('' === $key) {
                continue;
            }

            $index[$key] = $item;

            foreach (array('slug', 'label') as $fieldKey) {
                $alias = $this->normalizeEntityKey((string) ($item[$fieldKey] ?? ''));
                if ('' !== $alias && !isset($index[$alias])) {
                    $index[$alias] = $item;
                }
            }

            foreach ((array) ($item['aliases'] ?? array()) as $aliasValue) {
                $alias = $this->normalizeEntityKey((string) $aliasValue);
                if ('' !== $alias && !isset($index[$alias])) {
                    $index[$alias] = $item;
                }
            }

            $geonameId = (int) ($item['geoname_id'] ?? 0);
            if ($geonameId > 0 && !isset($index[(string) $geonameId])) {
                $index[(string) $geonameId] = $item;
            }
        }

        return $index;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    private function buildLocationGeonameIndex(array $items): array
    {
        $index = array();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $geonameId = (int) ($item['geoname_id'] ?? 0);
            if ($geonameId > 0 && !isset($index[$geonameId])) {
                $index[$geonameId] = $item;
            }
        }

        return $index;
    }

    private function extractFallbackGeonameId(array $extraction): int
    {
        foreach (array('default_city_geoname_id', 'city_geoname_id') as $key) {
            $value = $extraction[$key] ?? null;
            if (is_numeric($value)) {
                $candidate = (int) $value;
                if ($candidate > 0) {
                    return $candidate;
                }
            }
        }

        return 0;
    }

    /**
     * @param array<string,array<string,mixed>> $locationIndex
     * @param array<int,array<string,mixed>> $locationByGeonameId
     * @return array{city_key:string,term_id:int,geoname_id:int,payload:array<string,mixed>}
     */
    private function resolveCityReference(
        array $locationIndex,
        array $locationByGeonameId,
        string $countryKey,
        string $cityKey,
        int $defaultCityGeonameId
    ): array {
        $countryKey = $this->normalizeEntityKey($countryKey);
        $cityKey = $this->normalizeEntityKey($cityKey);
        $resolved = array(
            'city_key' => '',
            'term_id' => 0,
            'geoname_id' => 0,
            'payload' => array(),
        );

        if ('' !== $cityKey && isset($locationIndex[$cityKey])) {
            $candidate = $locationIndex[$cityKey];
            $candidateCountryKey = $this->normalizeEntityKey((string) ($candidate['country_key'] ?? ''));
            if ('' === $countryKey || '' === $candidateCountryKey || $candidateCountryKey === $countryKey) {
                return $this->buildResolvedCityResult($candidate);
            }
        }

        if ($defaultCityGeonameId > 0) {
            $this->applyGeonameFallback(
                $locationByGeonameId,
                $defaultCityGeonameId,
                $countryKey,
                $resolved['term_id'],
                $resolved['geoname_id'],
                $resolved['payload'],
                $resolved['city_key']
            );
            if ($resolved['geoname_id'] > 0 && !empty($resolved['payload'])) {
                return $resolved;
            }

            if ($this->geoCityLookup instanceof WordPressGeoCityLookupClient && '' !== $countryKey) {
                $lookup = $this->geoCityLookup->resolveCity($countryKey, '', $defaultCityGeonameId);
                if (!empty($lookup['resolved']) && !empty($lookup['city']) && is_array($lookup['city'])) {
                    return $this->buildResolvedCityResult($lookup['city'], $defaultCityGeonameId);
                }
            }
        }

        if ('' !== $cityKey && $this->geoCityLookup instanceof WordPressGeoCityLookupClient && '' !== $countryKey) {
            $lookup = $this->geoCityLookup->resolveCity($countryKey, $cityKey, 0);
            if (!empty($lookup['resolved']) && !empty($lookup['city']) && is_array($lookup['city'])) {
                return $this->buildResolvedCityResult($lookup['city']);
            }
        }

        return $resolved;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{city_key:string,term_id:int,geoname_id:int,payload:array<string,mixed>}
     */
    private function buildResolvedCityResult(array $payload, int $fallbackGeonameId = 0): array
    {
        return array(
            'city_key' => $this->normalizeEntityKey((string) ($payload['key'] ?? '')),
            'term_id' => (int) ($payload['term_id'] ?? 0),
            'geoname_id' => (int) ($payload['geoname_id'] ?? $fallbackGeonameId),
            'payload' => $payload,
        );
    }

    /**
     * @param array<string,array<string,mixed>> $locationIndex
     * @param array<int,array<string,mixed>> $locationByGeonameId
     * @return array{city_key:string,term_id:int,geoname_id:int,payload:array<string,mixed>}
     */
    private function resolveCityFromExactAddress(
        array $locationIndex,
        array $locationByGeonameId,
        string $countryKey,
        string $exactAddress
    ): array {
        $countryKey = $this->normalizeEntityKey($countryKey);
        $candidates = $this->extractAddressCityCandidates($exactAddress);
        foreach ($candidates as $candidate) {
            $resolved = $this->resolveCityReference(
                $locationIndex,
                $locationByGeonameId,
                $countryKey,
                $candidate,
                0
            );
            if ('' !== (string) ($resolved['city_key'] ?? '')) {
                return $resolved;
            }
        }

        return array(
            'city_key' => '',
            'term_id' => 0,
            'geoname_id' => 0,
            'payload' => array(),
        );
    }

    /**
     * @return array<int,string>
     */
    private function extractAddressCityCandidates(string $exactAddress): array
    {
        $exactAddress = trim($exactAddress);
        if ('' === $exactAddress) {
            return array();
        }

        $segments = preg_split('/[\r\n,|;\/]+/u', $exactAddress);
        if (!is_array($segments) || empty($segments)) {
            $segments = array($exactAddress);
        }

        $candidates = array();
        foreach (array_reverse($segments) as $segment) {
            $candidate = $this->sanitizeAddressCityCandidate((string) $segment);
            if ('' !== $candidate && !in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }

        $fullCandidate = $this->sanitizeAddressCityCandidate($exactAddress);
        if ('' !== $fullCandidate && !in_array($fullCandidate, $candidates, true)) {
            $candidates[] = $fullCandidate;
        }

        return $candidates;
    }

    private function sanitizeAddressCityCandidate(string $value): string
    {
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B-._()[]{}");
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = is_string($value) ? trim($value) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ('' === $value || $length < 2 || $length > 120) {
            return '';
        }

        if (!preg_match('/[\p{L}]/u', $value)) {
            return '';
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $resolvedCityPayload
     */
    private function resolvedCityMatchesText(array $resolvedCityPayload, string $text): bool
    {
        $text = trim($text);
        if ('' === $text || empty($resolvedCityPayload)) {
            return false;
        }

        $normalizedText = $this->normalizeEntityKey($text);
        $candidates = array(
            (string) ($resolvedCityPayload['key'] ?? ''),
            (string) ($resolvedCityPayload['slug'] ?? ''),
            (string) ($resolvedCityPayload['label'] ?? ''),
        );
        foreach ((array) ($resolvedCityPayload['aliases'] ?? array()) as $alias) {
            $candidates[] = (string) $alias;
        }

        foreach (array_values(array_unique(array_filter(array_map('strval', $candidates), 'strlen'))) as $candidate) {
            if (function_exists('mb_stripos')) {
                if (false !== mb_stripos($text, $candidate, 0, 'UTF-8')) {
                    return true;
                }
            } elseif (false !== stripos($text, $candidate)) {
                return true;
            }

            $normalizedCandidate = $this->normalizeEntityKey($candidate);
            if ('' !== $normalizedText && '' !== $normalizedCandidate && false !== strpos($normalizedText, $normalizedCandidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $schema
     * @param array<string,mixed> $extraction
     * @return array<string,mixed>
     */
    private function collectDynamicFieldsInput(array $schema, string $categoryKey, array $extraction): array
    {
        $dynamic = isset($extraction['dynamic_fields']) && is_array($extraction['dynamic_fields'])
            ? $extraction['dynamic_fields']
            : array();

        $categoryFields = isset($schema['fields']['by_category'][$categoryKey]) && is_array($schema['fields']['by_category'][$categoryKey])
            ? $schema['fields']['by_category'][$categoryKey]
            : array();

        foreach ($categoryFields as $fieldDescriptor) {
            if (!is_array($fieldDescriptor)) {
                continue;
            }

            $fieldKey = trim((string) ($fieldDescriptor['field_key'] ?? ''));
            if ('' === $fieldKey || array_key_exists($fieldKey, $dynamic)) {
                continue;
            }

            if (array_key_exists($fieldKey, $extraction)) {
                $dynamic[$fieldKey] = $extraction[$fieldKey];
            }
        }

        return $dynamic;
    }

    /**
     * @param array<string,mixed> $fieldDescriptor
     * @return mixed|null
     */
    private function getAutoDefaultFieldValue(array $fieldDescriptor, array $schema)
    {
        $choices = isset($fieldDescriptor['choices']) && is_array($fieldDescriptor['choices'])
            ? $fieldDescriptor['choices']
            : array();
        $type = (string) ($fieldDescriptor['type'] ?? 'text');
        $multiple = !empty($fieldDescriptor['multiple']);
        $fieldKey = (string) ($fieldDescriptor['field_key'] ?? '');

        if ($multiple || !in_array($type, array('select', 'radio', 'color', 'taxonomy_select'), true)) {
            return null;
        }

        if ('currency' === $fieldKey) {
            $choices = $this->filterCurrencyChoicesForAutoDefault($choices, $schema);
        }

        if (count($choices) !== 1 || !is_array($choices[0])) {
            return null;
        }

        return (string) ($choices[0]['key'] ?? '');
    }

    /**
     * @param array<int,array<string,mixed>> $choices
     * @return array<int,array<string,mixed>>
     */
    private function filterCurrencyChoicesForAutoDefault(array $choices, array $schema): array
    {
        $preferredKeys = array_values(
            array_filter(
                array_map(
                    'strval',
                    (array) ($schema['market']['preferred_currency_keys'] ?? array())
                ),
                static function (string $value): bool {
                    return '' !== trim($value);
                }
            )
        );

        if (!empty($preferredKeys)) {
            $filtered = array_values(
                array_filter(
                    $choices,
                    static function (array $choice) use ($preferredKeys): bool {
                        return in_array((string) ($choice['key'] ?? ''), $preferredKeys, true);
                    }
                )
            );

            if (!empty($filtered)) {
                return $filtered;
            }
        }

        $marketCurrencyName = trim((string) ($schema['market']['country']['currency_name'] ?? ''));
        if ('' !== $marketCurrencyName) {
            $filtered = array_values(
                array_filter(
                    $choices,
                    static function (array $choice) use ($marketCurrencyName): bool {
                        return (string) ($choice['label'] ?? '') === $marketCurrencyName;
                    }
                )
            );

            if (!empty($filtered) && count($filtered) <= 5) {
                return $filtered;
            }
        }

        return $choices;
    }

    /**
     * @param array<string,mixed> $fieldDescriptor
     * @param mixed $value
     * @param array<int,string> $errors
     * @param array<string,mixed> $schema
     * @return array<string,mixed>
     */
    private function resolveFieldValue(array $fieldDescriptor, $value, array &$errors, array $schema = array()): array
    {
        $fieldKey = (string) ($fieldDescriptor['field_key'] ?? '');
        $type = (string) ($fieldDescriptor['type'] ?? 'text');
        $choices = isset($fieldDescriptor['choices']) && is_array($fieldDescriptor['choices'])
            ? $fieldDescriptor['choices']
            : array();
        $rules = isset($fieldDescriptor['rules']) && is_array($fieldDescriptor['rules'])
            ? $fieldDescriptor['rules']
            : array();

        if ('currency' === $fieldKey) {
            $choices = $this->filterCurrencyChoicesForAutoDefault($choices, $schema);
        }

        if (null === $value || '' === $value || array() === $value) {
            return array(
                'resolved' => true,
                'public_value' => null,
                'storage_value' => null,
                'term_ids' => array(),
            );
        }

        if ('checkbox' === $type) {
            $inputItems = is_array($value) ? $value : array($value);
            $publicItems = array();
            $storageItems = array();

            foreach ($inputItems as $item) {
                $choice = $this->resolveChoice($choices, $item);
                if (null === $choice) {
                    $errors[] = sprintf('Unknown choice for %s: %s', $fieldKey, (string) $item);
                    continue;
                }

                $publicItems[] = (string) ($choice['key'] ?? '');
                $storageItems[] = (string) ($choice['stored_value'] ?? '');
            }

            return array(
                'resolved' => true,
                'public_value' => array_values(array_unique(array_filter($publicItems, 'strlen'))),
                'storage_value' => array_values(array_unique(array_filter($storageItems, 'strlen'))),
                'term_ids' => array(),
            );
        }

        if (in_array($type, array('select', 'radio', 'color', 'taxonomy_select'), true)) {
            $choice = $this->resolveChoice($choices, $value);
            if (null === $choice) {
                $errors[] = sprintf('Unknown choice for %s: %s', $fieldKey, (string) $value);

                return array(
                    'resolved' => false,
                    'public_value' => null,
                    'storage_value' => null,
                    'term_ids' => array(),
                );
            }

            return array(
                'resolved' => true,
                'public_value' => (string) ($choice['key'] ?? ''),
                'storage_value' => (string) ($choice['stored_value'] ?? ''),
                'term_ids' => !empty($choice['term_id']) ? array((int) $choice['term_id']) : array(),
            );
        }

        if ('number' === $type) {
            if (!is_numeric($value)) {
                $errors[] = sprintf('Field %s must be numeric.', $fieldKey);

                return array(
                    'resolved' => false,
                    'public_value' => null,
                    'storage_value' => null,
                    'term_ids' => array(),
                );
            }

            $numeric = strpos((string) $value, '.') !== false ? (float) $value : (int) $value;
            if (isset($rules['min']) && $numeric < (float) $rules['min']) {
                $errors[] = sprintf('Field %s is below minimum.', $fieldKey);
                return array(
                    'resolved' => false,
                    'public_value' => null,
                    'storage_value' => null,
                    'term_ids' => array(),
                );
            }
            if (isset($rules['max']) && $numeric > (float) $rules['max']) {
                $errors[] = sprintf('Field %s is above maximum.', $fieldKey);
                return array(
                    'resolved' => false,
                    'public_value' => null,
                    'storage_value' => null,
                    'term_ids' => array(),
                );
            }

            return array(
                'resolved' => true,
                'public_value' => $numeric,
                'storage_value' => (string) $numeric,
                'term_ids' => array(),
            );
        }

        return array(
            'resolved' => true,
            'public_value' => (string) $value,
            'storage_value' => (string) $value,
            'term_ids' => array(),
        );
    }

    /**
     * @param array<int,array<string,mixed>> $choices
     * @param mixed $value
     * @return array<string,mixed>|null
     */
    private function resolveChoice(array $choices, $value): ?array
    {
        $needle = CanonicalKey::fromString((string) $value);
        $rawNeedle = trim((string) $value);

        foreach ($choices as $choice) {
            if (!is_array($choice)) {
                continue;
            }

            $choiceKey = CanonicalKey::fromString((string) ($choice['key'] ?? ''));
            $labelKey = CanonicalKey::fromString((string) ($choice['label'] ?? ''));
            $storedKey = CanonicalKey::fromString((string) ($choice['stored_value'] ?? ''));

            if ('' !== $needle && ($needle === $choiceKey || $needle === $labelKey || $needle === $storedKey)) {
                return $choice;
            }

            if ('' !== $rawNeedle && $rawNeedle === (string) ($choice['stored_value'] ?? '')) {
                return $choice;
            }
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private function hasNonEmptyValue($value): bool
    {
        return '' !== trim((string) $value);
    }

    private function normalizeEntityKey(string $value): string
    {
        return CanonicalKey::fromString($value);
    }

    private function normalizeLocationSource(string $source): string
    {
        $source = trim(strtolower($source));
        if ('' === $source) {
            return '';
        }

        $map = array(
            'ad_content' => 'ad_content',
            'ad-content' => 'ad_content',
            'ad' => 'ad_content',
            'ad_text' => 'ad_content',
            'ad-text' => 'ad_content',
            'caption' => 'ad_content',
            'ocr' => 'ad_content',
            'address' => 'ad_content',
            'default_metadata' => 'default_metadata',
            'default-metadata' => 'default_metadata',
            'default' => 'default_metadata',
            'fallback' => 'default_metadata',
            'publisher_metadata' => 'publisher_metadata',
            'publisher-metadata' => 'publisher_metadata',
            'publisher' => 'publisher_metadata',
            'group' => 'publisher_metadata',
            'group_title' => 'publisher_metadata',
            'group-title' => 'publisher_metadata',
            'none' => 'none',
            'null' => 'none',
            'unresolved' => 'none',
            'unknown' => 'none',
        );

        return $map[$source] ?? '';
    }

    private function isWeakLocationSource(string $source): bool
    {
        return in_array($source, array('default_metadata', 'publisher_metadata'), true);
    }

    private function requiresPrimaryContact(string $status): bool
    {
        return 'approved' === trim(strtolower($status));
    }

    private function normalizeModerationStatus(string $status): string
    {
        $status = trim(strtolower($status));
        if ('' === $status) {
            return '';
        }

        $map = array(
            'approve' => 'approved',
            'approved' => 'approved',
            'accept' => 'approved',
            'accepted' => 'approved',
            'ok' => 'approved',
            'reject' => 'rejected',
            'rejected' => 'rejected',
            'deny' => 'rejected',
            'denied' => 'rejected',
            'decline' => 'rejected',
            'declined' => 'rejected',
            'pending' => 'pending',
            'review' => 'pending',
            'uncertain' => 'pending',
        );

        return isset($map[$status]) ? $map[$status] : $status;
    }

    /**
     * @param array<int,array<string,mixed>> $locationByGeonameId
     * @param array<string,mixed> $resolvedCityPayload
     */
    private function applyGeonameFallback(
        array $locationByGeonameId,
        int $defaultCityGeonameId,
        string $marketCountryKey,
        int &$resolvedCityId,
        int &$resolvedCityGeoNameId,
        array &$resolvedCityPayload,
        string &$cityKey
    ): void {
        $resolvedCityGeoNameId = $defaultCityGeonameId > 0 ? $defaultCityGeonameId : 0;
        $cityKey = '';

        if ($defaultCityGeonameId < 1 || !isset($locationByGeonameId[$defaultCityGeonameId])) {
            return;
        }

        $candidate = $locationByGeonameId[$defaultCityGeonameId];
        $candidateCountryKey = $this->normalizeEntityKey((string) ($candidate['country_key'] ?? ''));
        if ('' !== $candidateCountryKey && $candidateCountryKey !== $marketCountryKey) {
            return;
        }

        $resolvedCityPayload = $candidate;
        $resolvedCityId = (int) ($candidate['term_id'] ?? 0);
        $resolvedCityGeoNameId = (int) ($candidate['geoname_id'] ?? $defaultCityGeonameId);
        $cityKey = $this->normalizeEntityKey((string) ($candidate['key'] ?? ''));
    }

    /**
     * @param mixed $value
     */
    private function normalizePhoneContact($value): ?string
    {
        $raw = trim((string) $value);
        if ('' === $raw) {
            return null;
        }

        $normalizedDigits = strtr(
            $raw,
            array(
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
            )
        );

        $candidate = preg_replace('/[\s\-\(\)\+]+/', '', $normalizedDigits);
        if (!is_string($candidate) || !preg_match('/^[0-9]{7,15}$/', $candidate)) {
            return null;
        }

        return $candidate;
    }

    private function truncateText(string $value, int $length): string
    {
        $value = trim($value);
        if ('' === $value) {
            return 'آگهی-بدون-عنوان';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
