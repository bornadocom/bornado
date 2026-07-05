<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Application;

use stdClass;

final class PromptPackageService
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
     * @param array<string,mixed> $schema
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function buildPromptPackage(array $schema, array $options = array()): array
    {
        $templatePath = (string) ($this->config['prompt']['core_template_path'] ?? '');
        $template     = is_file($templatePath) ? (string) file_get_contents($templatePath) : '';
        $templateHash = sha1($template);
        $stage = $this->normalizeStage((string) ($options['stage'] ?? 'extract'));
        $categoryHint = $this->normalizeKey((string) ($options['category_hint'] ?? ''));
        $candidateCategories = $this->normalizeKeyList((array) ($options['candidate_categories'] ?? array()));

        $dynamicSchema = $this->buildAiSchemaSlice($schema, $stage, $categoryHint, $candidateCategories);
        $outputContract = $this->buildOutputContract($dynamicSchema);

        $composedPrompt = str_replace(
            array('{{DYNAMIC_SCHEMA_JSON}}', '{{OUTPUT_CONTRACT_JSON}}'),
            array(
                json_encode($dynamicSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($outputContract, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ),
            $template
        );

        return array(
            'prompt_version' => $this->buildPromptVersion((string) ($schema['schema_version'] ?? ''), $templateHash),
            'prompt_template_hash' => $templateHash,
            'stage' => $stage,
            'category_hint' => $categoryHint,
            'dynamic_schema' => $dynamicSchema,
            'output_contract' => $outputContract,
            'composed_prompt' => $composedPrompt,
        );
    }

    /**
     * @param array<string,mixed> $schema
     * @param array<int,string> $candidateCategories
     * @return array<string,mixed>
     */
    private function buildAiSchemaSlice(array $schema, string $stage, string $categoryHint, array $candidateCategories): array
    {
        $categories = array_values(array_filter((array) ($schema['categories'] ?? array()), 'is_array'));
        if ('extract' === $stage && '' !== $categoryHint && empty($candidateCategories)) {
            $candidateCategories = array($categoryHint);
        }
        $allowedCategories = array();
        foreach ($categories as $category) {
            $key = $this->normalizeKey((string) ($category['key'] ?? ''));
            if ('' === $key) {
                continue;
            }

            if (!empty($candidateCategories) && !in_array($key, $candidateCategories, true)) {
                continue;
            }

            $allowedCategories[] = array(
                'key' => $key,
                'label' => (string) ($category['label'] ?? ''),
                'term_id' => (int) ($category['term_id'] ?? 0),
                'parent_key' => (string) ($category['parent_key'] ?? ''),
                'template_key' => (string) ($category['template_key'] ?? ''),
            );
        }

        $categorySchema = null;
        if ('extract' === $stage && '' !== $categoryHint) {
            foreach ($categories as $category) {
                if ($this->normalizeKey((string) ($category['key'] ?? '')) !== $categoryHint) {
                    continue;
                }

                $categorySchema = array(
                    'key' => (string) ($category['key'] ?? ''),
                    'label' => (string) ($category['label'] ?? ''),
                    'term_id' => (int) ($category['term_id'] ?? 0),
                    'template_key' => (string) ($category['template_key'] ?? ''),
                    'fields' => $this->sanitizeFieldsForAi(
                        (array) ($schema['fields']['by_category'][$categoryHint] ?? array()),
                        $schema
                    ),
                    'auto_defaults' => $this->buildAutoDefaultsForAi(
                        (array) ($schema['fields']['by_category'][$categoryHint] ?? array()),
                        $schema
                    ),
                );
                break;
            }
        }

        return array(
            'stage' => $stage,
            'schema_version' => (string) ($schema['schema_version'] ?? ''),
            'market' => (array) ($schema['market'] ?? array()),
            'channel' => (array) ($schema['channel'] ?? array()),
            'global_output_fields' => array_values(array_map('strval', (array) ($schema['fields']['global'] ?? array()))),
            'allowed_categories' => $allowedCategories,
            'allowed_locations' => array_map(
                static function ($location): array {
                    return array(
                        'key' => (string) ($location['key'] ?? ''),
                        'label' => (string) ($location['label'] ?? ''),
                        'term_id' => (int) ($location['term_id'] ?? 0),
                        'geoname_id' => (int) ($location['geoname_id'] ?? 0),
                        'aliases' => array_values(array_map('strval', (array) ($location['aliases'] ?? array()))),
                    );
                },
                (array) ($schema['locations'] ?? array())
            ),
            'category_schema' => $categorySchema,
        );
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @return array<int,array<string,mixed>>
     */
    private function sanitizeFieldsForAi(array $fields, array $schema = array()): array
    {
        $sanitized = array();
        $preferredCurrencyKeys = array_values(
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
        $marketCurrencyName = trim((string) ($schema['market']['country']['currency_name'] ?? ''));

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $choices = array();
            foreach ((array) ($field['choices'] ?? array()) as $choice) {
                if (!is_array($choice)) {
                    continue;
                }

                $choices[] = array(
                    'key' => (string) ($choice['key'] ?? ''),
                    'label' => (string) ($choice['label'] ?? ''),
                );
            }

            $fieldKey = (string) ($field['field_key'] ?? '');
            if ('currency' === $fieldKey) {
                $choices = $this->filterCurrencyChoicesForAi($choices, $preferredCurrencyKeys, $marketCurrencyName);
            }

            if ($this->shouldAutoApplySingleChoiceField($field, $choices)) {
                continue;
            }

            $sanitized[] = array(
                'field_key' => $fieldKey,
                'label_fa' => (string) ($field['label_fa'] ?? ''),
                'type' => (string) ($field['type'] ?? 'text'),
                'required' => !empty($field['required']),
                'multiple' => !empty($field['multiple']),
                'choices' => $choices,
                'rules' => is_array($field['rules'] ?? null) ? $field['rules'] : array(),
            );
        }

        return $sanitized;
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @return array<int,array<string,mixed>>
     */
    private function buildAutoDefaultsForAi(array $fields, array $schema = array()): array
    {
        $autoDefaults = array();
        $preferredCurrencyKeys = array_values(
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
        $marketCurrencyName = trim((string) ($schema['market']['country']['currency_name'] ?? ''));

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $choices = array();
            foreach ((array) ($field['choices'] ?? array()) as $choice) {
                if (!is_array($choice)) {
                    continue;
                }

                $choices[] = array(
                    'key' => (string) ($choice['key'] ?? ''),
                    'label' => (string) ($choice['label'] ?? ''),
                );
            }

            $fieldKey = (string) ($field['field_key'] ?? '');
            if ('currency' === $fieldKey) {
                $choices = $this->filterCurrencyChoicesForAi($choices, $preferredCurrencyKeys, $marketCurrencyName);
            }

            if (!$this->shouldAutoApplySingleChoiceField($field, $choices)) {
                continue;
            }

            $choice = $choices[0];
            $autoDefaults[] = array(
                'field_key' => $fieldKey,
                'key' => (string) ($choice['key'] ?? ''),
                'label' => (string) ($choice['label'] ?? ''),
            );
        }

        return $autoDefaults;
    }

    /**
     * @param array<int,array<string,mixed>> $choices
     * @param array<int,string> $preferredCurrencyKeys
     * @return array<int,array<string,mixed>>
     */
    private function filterCurrencyChoicesForAi(array $choices, array $preferredCurrencyKeys, string $marketCurrencyName): array
    {
        if (!empty($preferredCurrencyKeys)) {
            $filtered = array_values(
                array_filter(
                    $choices,
                    static function (array $choice) use ($preferredCurrencyKeys): bool {
                        return in_array((string) ($choice['key'] ?? ''), $preferredCurrencyKeys, true);
                    }
                )
            );

            if (!empty($filtered)) {
                return $filtered;
            }
        }

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
     * @param array<string,mixed> $field
     * @param array<int,array<string,mixed>> $choices
     */
    private function shouldAutoApplySingleChoiceField(array $field, array $choices): bool
    {
        $type = (string) ($field['type'] ?? 'text');
        $multiple = !empty($field['multiple']);

        if ($multiple) {
            return false;
        }

        if (!in_array($type, array('select', 'radio', 'color', 'taxonomy_select'), true)) {
            return false;
        }

        return count($choices) === 1;
    }

    /**
     * @param array<string,mixed> $dynamicSchema
     * @return array<string,mixed>
     */
    private function buildOutputContract(array $dynamicSchema): array
    {
        $stage = (string) ($dynamicSchema['stage'] ?? 'extract');
        $dynamicFields = new stdClass();
        $categorySchema = isset($dynamicSchema['category_schema']) && is_array($dynamicSchema['category_schema'])
            ? $dynamicSchema['category_schema']
            : array();

        if ('extract' === $stage && !empty($categorySchema['fields']) && is_array($categorySchema['fields'])) {
            $dynamicFields = array();
            foreach ($categorySchema['fields'] as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $dynamicFields[(string) ($field['field_key'] ?? '')] = null;
            }
        }

        return array(
            'status' => 'classify' === $stage ? 'pending' : 'approved',
            'reason' => null,
            'category_key' => ('extract' === $stage && !empty($categorySchema['key'])) ? (string) $categorySchema['key'] : null,
            'country_key' => null,
            'city_key' => null,
            'location_source' => null,
            'location_evidence' => null,
            'exact_address' => null,
            'seo_title' => null,
            'slug' => null,
            'final_ad_text' => null,
            'primary_contact' => null,
            'secondary_contacts' => array(),
            'dynamic_fields' => $dynamicFields,
        );
    }

    private function buildPromptVersion(string $schemaVersion, string $templateHash): string
    {
        $schemaVersion = trim($schemaVersion);
        $templateFingerprint = substr($templateHash, 0, 12);

        if ('' === $schemaVersion) {
            return 'template-' . $templateFingerprint;
        }

        return $schemaVersion . '-prompt-' . $templateFingerprint;
    }

    private function normalizeStage(string $stage): string
    {
        $stage = trim(strtolower($stage));

        return in_array($stage, array('classify', 'extract'), true) ? $stage : 'extract';
    }

    private function normalizeKey(string $value): string
    {
        $value = trim(strtolower($value));
        if ('' === $value) {
            return '';
        }

        $value = preg_replace('/[^a-z0-9-]+/', '-', $value);

        return trim((string) $value, '-');
    }

    /**
     * @param array<int,string> $items
     * @return array<int,string>
     */
    private function normalizeKeyList(array $items): array
    {
        $normalized = array();

        foreach ($items as $item) {
            $key = $this->normalizeKey((string) $item);
            if ('' !== $key && !in_array($key, $normalized, true)) {
                $normalized[] = $key;
            }
        }

        return $normalized;
    }
}
