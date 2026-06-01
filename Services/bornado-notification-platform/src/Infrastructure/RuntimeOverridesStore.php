<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

final class RuntimeOverridesStore
{
    /**
     * @var string
     */
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function load(): array
    {
        if (!is_file($this->filePath)) {
            return $this->defaultOverrides();
        }

        $decoded = json_decode((string) file_get_contents($this->filePath), true);

        if (!is_array($decoded)) {
            return $this->defaultOverrides();
        }

        return array_merge($this->defaultOverrides(), $decoded);
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public function apply(array $config): array
    {
        $overrides = $this->load();
        $providers = isset($overrides['providers']) && is_array($overrides['providers']) ? $overrides['providers'] : array();
        $events    = isset($overrides['events']) && is_array($overrides['events']) ? $overrides['events'] : array();
        $service   = isset($overrides['service']) && is_array($overrides['service']) ? $overrides['service'] : array();

        if (!isset($config['service']) || !is_array($config['service'])) {
            $config['service'] = array();
        }

        $config['service']['paused'] = !empty($service['paused']);

        foreach ($providers as $providerName => $isEnabled) {
            if (!isset($config['providers'][$providerName]) || !is_array($config['providers'][$providerName])) {
                continue;
            }

            $config['providers'][$providerName]['enabled'] = (bool) $isEnabled;
        }

        foreach ($events as $eventType => $isEnabled) {
            if (
                !isset($config['routing']['events'][$eventType])
                || !is_array($config['routing']['events'][$eventType])
            ) {
                continue;
            }

            if (!$isEnabled) {
                $config['routing']['events'][$eventType]['channels'] = array();
            }
        }

        return $config;
    }

    public function setProviderEnabled(string $providerName, bool $isEnabled): void
    {
        $overrides = $this->load();
        $overrides['providers'][$providerName] = $isEnabled;
        $this->persist($overrides);
    }

    public function setEventEnabled(string $eventType, bool $isEnabled): void
    {
        $overrides = $this->load();
        $overrides['events'][$eventType] = $isEnabled;
        $this->persist($overrides);
    }

    public function setServicePaused(bool $isPaused): void
    {
        $overrides = $this->load();
        $overrides['service']['paused'] = $isPaused;
        $this->persist($overrides);
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public function snapshot(array $config): array
    {
        $overrides = $this->load();
        $providers = array();
        $events    = array();

        foreach ((array) ($config['providers'] ?? array()) as $providerName => $providerConfig) {
            if (!is_array($providerConfig)) {
                continue;
            }

            $providers[] = array(
                'name'              => (string) $providerName,
                'configuredEnabled' => !empty($providerConfig['enabled']),
                'overrideEnabled'   => array_key_exists($providerName, (array) ($overrides['providers'] ?? array()))
                    ? (bool) $overrides['providers'][$providerName]
                    : null,
                'effectiveEnabled'  => !empty($providerConfig['enabled']),
            );
        }

        foreach ((array) ($config['routing']['events'] ?? array()) as $eventType => $eventConfig) {
            if (!is_array($eventConfig)) {
                continue;
            }

            $events[] = array(
                'eventType'         => (string) $eventType,
                'configuredChannels'=> array_values((array) ($eventConfig['channels'] ?? array())),
                'overrideEnabled'   => array_key_exists($eventType, (array) ($overrides['events'] ?? array()))
                    ? (bool) $overrides['events'][$eventType]
                    : null,
                'effectiveEnabled'  => !empty($eventConfig['channels']),
            );
        }

        return array(
            'updatedAt' => (string) ($overrides['updatedAt'] ?? ''),
            'service'   => array(
                'paused' => !empty($overrides['service']['paused']),
            ),
            'providers' => $providers,
            'events'    => $events,
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function defaultOverrides(): array
    {
        return array(
            'updatedAt' => '',
            'service'   => array(
                'paused' => false,
            ),
            'providers' => array(),
            'events'    => array(),
        );
    }

    /**
     * @param array<string,mixed> $overrides
     */
    private function persist(array $overrides): void
    {
        $overrides['updatedAt'] = gmdate('c');

        file_put_contents(
            $this->filePath,
            json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
