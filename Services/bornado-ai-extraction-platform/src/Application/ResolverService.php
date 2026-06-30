<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Application;

use Bornado\AiExtractionPlatform\Domain\CanonicalKey;

final class ResolverService
{
    /**
     * @param array<string,mixed> $schema
     * @param array<string,mixed> $extraction
     * @return array<string,mixed>
     */
    public function resolve(array $schema, array $extraction): array
    {
        $errors = array();

        $categoryIndex = $this->buildIndex((array) ($schema['categories'] ?? array()));
        $locationIndex = $this->buildIndex((array) ($schema['locations'] ?? array()));

        $marketCountryKey = (string) ($schema['market']['country']['key'] ?? '');
        $marketCountryId  = (int) ($schema['market']['country']['term_id'] ?? 0);

        $categoryKey = trim((string) ($extraction['category_key'] ?? ''));
        $countryKey  = trim((string) ($extraction['country_key'] ?? ''));
        $cityKey     = trim((string) ($extraction['city_key'] ?? ''));

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

        if ('' === $countryKey) {
            $errors[] = 'Missing country_key.';
        } elseif ($countryKey !== $marketCountryKey) {
            $errors[] = sprintf('country_key %s is outside the configured market.', $countryKey);
        }

        $resolvedCityId = 0;
        if ('' !== $cityKey) {
            if (isset($locationIndex[$cityKey])) {
                $city = $locationIndex[$cityKey];
                if ((string) ($city['country_key'] ?? '') !== $marketCountryKey) {
                    $errors[] = sprintf('city_key %s does not belong to the market country.', $cityKey);
                } else {
                    $resolvedCityId = (int) ($city['term_id'] ?? 0);
                }
            } else {
                $errors[] = sprintf('Unknown city_key: %s', $cityKey);
            }
        } else {
            $errors[] = 'Missing city_key.';
        }

        $status = trim((string) ($extraction['status'] ?? 'pending'));
        if ('' === $status) {
            $status = 'pending';
        }

        $primaryContactInput = $extraction['primary_contact'] ?? null;
        $primaryContact = $this->normalizePhoneContact($primaryContactInput);
        if ($this->hasNonEmptyValue($primaryContactInput) && null === $primaryContact) {
            $errors[] = 'primary_contact must contain only one normalized phone number.';
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

        $taxonomies = array(
            'ad_cats' => $resolvedCategoryId > 0 ? array($resolvedCategoryId) : array(),
            'ad_country' => ($marketCountryId > 0 && $resolvedCityId > 0) ? array($marketCountryId, $resolvedCityId) : array(),
        );
        $meta = array(
            '_adforest_poster_contact' => $primaryContact ?? '',
            '_adforest_ad_location' => $extraction['exact_address'] ?? '',
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

            $resolvedValue = $this->resolveFieldValue($fieldDescriptor, $fieldValue, $errors);
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
            'errors' => $errors,
            'trace' => array(
                'schema_version' => (string) ($schema['schema_version'] ?? ''),
                'category_key' => $categoryKey,
                'country_key' => $countryKey,
                'city_key' => $cityKey,
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

            $key = trim((string) ($item['key'] ?? ''));
            if ('' === $key) {
                continue;
            }

            $index[$key] = $item;

            foreach ((array) ($item['aliases'] ?? array()) as $alias) {
                $alias = trim(strtolower((string) $alias));
                if ('' !== $alias && !isset($index[$alias])) {
                    $index[$alias] = $item;
                }
            }
        }

        return $index;
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

            if (!empty($filtered)) {
                return $filtered;
            }
        }

        return $choices;
    }

    /**
     * @param array<string,mixed> $fieldDescriptor
     * @param mixed $value
     * @param array<int,string> $errors
     * @return array<string,mixed>
     */
    private function resolveFieldValue(array $fieldDescriptor, $value, array &$errors): array
    {
        $fieldKey = (string) ($fieldDescriptor['field_key'] ?? '');
        $type = (string) ($fieldDescriptor['type'] ?? 'text');
        $choices = isset($fieldDescriptor['choices']) && is_array($fieldDescriptor['choices'])
            ? $fieldDescriptor['choices']
            : array();
        $rules = isset($fieldDescriptor['rules']) && is_array($fieldDescriptor['rules'])
            ? $fieldDescriptor['rules']
            : array();

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
            }
            if (isset($rules['max']) && $numeric > (float) $rules['max']) {
                $errors[] = sprintf('Field %s is above maximum.', $fieldKey);
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
