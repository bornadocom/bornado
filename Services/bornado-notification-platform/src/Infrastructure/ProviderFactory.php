<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

final class ProviderFactory
{
    /**
     * @param array<string,mixed> $config
     * @return array<string,ProviderAdapterInterface>
     */
    public static function buildAll(array $config): array
    {
        $providersConfig = isset($config['providers']) && is_array($config['providers']) ? $config['providers'] : array();
        $providers       = array();

        $providers['dry-run'] = new DryRunProviderAdapter(
            isset($providersConfig['dry-run']) && is_array($providersConfig['dry-run'])
                ? $providersConfig['dry-run']
                : array()
        );

        $providers['whatsapp-cloud-api'] = new WhatsAppCloudApiAdapter(
            isset($providersConfig['whatsapp-cloud-api']) && is_array($providersConfig['whatsapp-cloud-api'])
                ? $providersConfig['whatsapp-cloud-api']
                : array()
        );

        return $providers;
    }
}
