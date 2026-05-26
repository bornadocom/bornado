<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Application;

final class TemplateEngine
{
    /**
     * @var array<string,mixed>
     */
    private $config;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string,mixed> $event
     * @return array<string,string>
     */
    public function render(array $event, string $channel): array
    {
        $templates      = isset($this->config['templates']) && is_array($this->config['templates']) ? $this->config['templates'] : array();
        $eventType      = (string) ($event['eventType'] ?? '');
        $defaultLocale  = (string) ($this->config['service']['default_locale'] ?? 'fa-IR');
        $requestedLocale= (string) ($event['locale'] ?? $defaultLocale);
        $eventTemplates = isset($templates[$eventType]) && is_array($templates[$eventType]) ? $templates[$eventType] : array();
        $localeTemplate = $eventTemplates[$requestedLocale] ?? $eventTemplates[$defaultLocale] ?? array();
        $channelTemplate= isset($localeTemplate[$channel]) && is_array($localeTemplate[$channel]) ? $localeTemplate[$channel] : array();
        $templateBody   = (string) ($channelTemplate['body'] ?? '');
        $templateSubject= (string) ($channelTemplate['subject'] ?? '');
        $replacements   = $this->buildReplacementMap($event);

        return array(
            'subject' => $this->replacePlaceholders($templateSubject, $replacements),
            'body'    => $this->replacePlaceholders($templateBody, $replacements),
        );
    }

    /**
     * @param array<string,mixed> $event
     * @return array<string,string>
     */
    private function buildReplacementMap(array $event): array
    {
        $flattened = array();
        $this->flatten($event, '', $flattened);

        $replacements = array();
        foreach ($flattened as $path => $value) {
            $replacements['{{' . $path . '}}'] = $value;
        }

        return $replacements;
    }

    /**
     * @param array<string,mixed> $source
     * @param array<string,string> $flattened
     */
    private function flatten(array $source, string $prefix, array &$flattened): void
    {
        foreach ($source as $key => $value) {
            $path = '' === $prefix ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $this->flatten($value, $path, $flattened);
                continue;
            }

            $flattened[$path] = is_scalar($value) ? (string) $value : '';
        }
    }

    /**
     * @param array<string,string> $replacements
     */
    private function replacePlaceholders(string $template, array $replacements): string
    {
        return strtr($template, $replacements);
    }
}
