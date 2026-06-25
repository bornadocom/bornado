<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Application;

use Bornado\AiExtractionPlatform\Infrastructure\FileSchemaCache;
use Bornado\AiExtractionPlatform\Infrastructure\WordPressRestCatalogSource;

final class SchemaService
{
    /** @var WordPressRestCatalogSource */
    private $source;

    /** @var FileSchemaCache */
    private $cache;

    /** @var array<string,mixed> */
    private $config;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(WordPressRestCatalogSource $source, FileSchemaCache $cache, array $config)
    {
        $this->source = $source;
        $this->cache  = $cache;
        $this->config = $config;
    }

    /**
     * @return array<string,mixed>
     */
    public function getSchema(string $market, string $channel): array
    {
        $market = trim(strtolower($market));
        $channel = trim(strtolower($channel));

        $marketConfig = isset($this->config['markets'][$market]) && is_array($this->config['markets'][$market])
            ? $this->config['markets'][$market]
            : array();

        $cacheKey = implode(':', array('schema', $market, $channel));

        return $this->cache->remember(
            $cacheKey,
            300,
            function () use ($market, $channel, $marketConfig): array {
                $catalog = $this->source->fetchCatalog($marketConfig);

                $country = isset($catalog['locations']['country']) && is_array($catalog['locations']['country'])
                    ? $catalog['locations']['country']
                    : array();
                $cities = isset($catalog['locations']['cities']) && is_array($catalog['locations']['cities'])
                    ? array_values($catalog['locations']['cities'])
                    : array();
                $templates = $this->normalizeTemplates((array) ($catalog['templates'] ?? array()));
                $categories = $this->normalizeCategories((array) ($catalog['categories'] ?? array()), $templates);

                $schema = array(
                    'service' => array(
                        'name' => (string) ($this->config['service']['name'] ?? 'bornado-ai-extraction-platform'),
                        'source_system' => (string) ($this->config['service']['source_system'] ?? 'bornado-wordpress'),
                    ),
                    'market' => array(
                        'key' => $market,
                        'label' => (string) ($marketConfig['label'] ?? strtoupper($market)),
                        'preferred_currency_keys' => array_values(
                            array_map('strval', (array) ($marketConfig['preferred_currency_keys'] ?? array()))
                        ),
                        'country' => array(
                            'key' => (string) ($country['key'] ?? ''),
                            'label' => (string) ($country['label'] ?? ''),
                            'term_id' => (int) ($country['term_id'] ?? 0),
                            'currency_name' => (string) ($country['currency_name'] ?? ''),
                        ),
                    ),
                    'channel' => array(
                        'key' => $channel,
                        'platform_label_fa' => (string) ($marketConfig['channel_defaults'][$channel]['platform_label_fa'] ?? $channel),
                    ),
                    'categories' => $categories,
                    'locations' => $cities,
                    'enums' => (array) ($catalog['enums'] ?? array()),
                    'templates' => $templates,
                    'fields' => array(
                        'global' => $this->getGlobalFieldKeys(),
                        'by_category' => $this->buildFieldsByCategory($categories, $templates),
                    ),
                    'constraints' => array(),
                    'source' => (array) ($catalog['source'] ?? array()),
                    'generated_at' => gmdate('c'),
                );

                $schema['schema_hash'] = sha1((string) json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $schema['schema_version'] = gmdate('Y-m-d') . '-' . substr($schema['schema_hash'], 0, 12);

                return $schema;
            }
        );
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @param array<string,array<string,mixed>> $templates
     * @return array<int,array<string,mixed>>
     */
    private function normalizeCategories(array $categories, array $templates): array
    {
        $byTermId = array();
        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            $byTermId[(int) ($category['term_id'] ?? 0)] = $category;
        }

        $normalized = array();
        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            $parentTermId = (int) ($category['parent_term_id'] ?? 0);
            $parentKey = '';
            if ($parentTermId > 0 && isset($byTermId[$parentTermId]['key'])) {
                $parentKey = (string) $byTermId[$parentTermId]['key'];
            }

            $normalized[] = array(
                'key' => (string) ($category['key'] ?? ''),
                'label' => (string) ($category['label'] ?? ''),
                'slug' => (string) ($category['slug'] ?? ''),
                'term_id' => (int) ($category['term_id'] ?? 0),
                'parent_term_id' => $parentTermId,
                'parent_key' => $parentKey,
                'template_term_id' => (int) ($category['template_term_id'] ?? 0),
                'template_key' => (string) ($category['template_key'] ?? ''),
                'template_label' => (string) ($category['template_label'] ?? ''),
                'ai_fields_count' => $this->countTemplateAiFields((string) ($category['template_key'] ?? ''), $templates),
            );
        }

        return $normalized;
    }

    /**
     * @param array<int,array<string,mixed>> $templates
     * @return array<string,array<string,mixed>>
     */
    private function normalizeTemplates(array $templates): array
    {
        $normalized = array();

        foreach ($templates as $template) {
            if (!is_array($template)) {
                continue;
            }

            $key = trim((string) ($template['key'] ?? ''));
            if ('' === $key) {
                $termId = (int) ($template['term_id'] ?? 0);
                if ($termId < 1) {
                    continue;
                }

                $key = 'template-' . $termId;
            }

            $normalized[$key] = array(
                'term_id' => (int) ($template['term_id'] ?? 0),
                'key' => $key,
                'label' => (string) ($template['label'] ?? ''),
                'slug' => (string) ($template['slug'] ?? ''),
                'dynamic_fields' => $this->normalizeFieldDescriptors((array) ($template['dynamic_fields'] ?? array())),
                'static_fields' => $this->normalizeFieldDescriptors((array) ($template['static_fields'] ?? array())),
                'all_fields' => $this->normalizeFieldDescriptors((array) ($template['all_fields'] ?? array())),
                'ai_fields' => $this->normalizeFieldDescriptors((array) ($template['ai_fields'] ?? array()), true),
            );
        }

        return $normalized;
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @return array<int,array<string,mixed>>
     */
    private function normalizeFieldDescriptors(array $fields, bool $aiOnly = false): array
    {
        $normalized = array();

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldKey = trim((string) ($field['field_key'] ?? ''));
            if ('' === $fieldKey) {
                continue;
            }

            $aiExposed = !empty($field['ai_exposed']);
            if ($aiOnly && !$aiExposed) {
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
                    'stored_value' => (string) ($choice['stored_value'] ?? ''),
                    'term_id' => (int) ($choice['term_id'] ?? 0),
                );
            }

            $normalized[] = array(
                'field_key' => $fieldKey,
                'label_fa' => (string) ($field['label_fa'] ?? $fieldKey),
                'type' => (string) ($field['type'] ?? 'text'),
                'type_code' => (string) ($field['type_code'] ?? ''),
                'required' => !empty($field['required']),
                'active' => !empty($field['active']),
                'multiple' => !empty($field['multiple']),
                'choices' => $choices,
                'rules' => is_array($field['rules'] ?? null) ? $field['rules'] : array(),
                'storage' => is_array($field['storage'] ?? null) ? $field['storage'] : array(),
                'source' => (string) ($field['source'] ?? ''),
                'ai_exposed' => $aiExposed,
            );
        }

        return $normalized;
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @param array<string,array<string,mixed>> $templates
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function buildFieldsByCategory(array $categories, array $templates): array
    {
        $map = array();

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            $categoryKey = trim((string) ($category['key'] ?? ''));
            if ('' === $categoryKey) {
                continue;
            }

            $templateKey = trim((string) ($category['template_key'] ?? ''));
            $map[$categoryKey] = isset($templates[$templateKey]['ai_fields']) && is_array($templates[$templateKey]['ai_fields'])
                ? $templates[$templateKey]['ai_fields']
                : array();
        }

        return $map;
    }

    /**
     * @return array<int,string>
     */
    private function getGlobalFieldKeys(): array
    {
        return array(
            'status',
            'reason',
            'category_key',
            'country_key',
            'city_key',
            'primary_contact',
            'secondary_contacts',
            'final_ad_text',
            'seo_title',
            'slug',
            'exact_address',
            'dynamic_fields',
        );
    }

    /**
     * @param array<string,array<string,mixed>> $templates
     */
    private function countTemplateAiFields(string $templateKey, array $templates): int
    {
        return isset($templates[$templateKey]['ai_fields']) && is_array($templates[$templateKey]['ai_fields'])
            ? count($templates[$templateKey]['ai_fields'])
            : 0;
    }
}
