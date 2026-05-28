<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

use Bornado\NotificationPlatform\Application\QueueConsumer;

final class ServiceOperations
{
    /**
     * @var array<string,mixed>
     */
    private $config;

    /**
     * @var RuntimeOverridesStore
     */
    private $runtimeOverrides;

    /**
     * @var WhatsAppStateStore
     */
    private $whatsAppState;

    public function __construct(array $config)
    {
        $this->config = $config;
        $opsDir = (string) ($config['logging']['ops_dir'] ?? '');
        $this->runtimeOverrides = new RuntimeOverridesStore($opsDir . DIRECTORY_SEPARATOR . 'runtime-overrides.json');
        $this->whatsAppState = new WhatsAppStateStore(
            (string) ($config['logging']['whatsapp_state_dir'] ?? ''),
            (string) ($config['logging']['whatsapp_webhook_log'] ?? ''),
            (string) ($config['logging']['whatsapp_inbound_dir'] ?? '')
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function snapshot(int $limit = 10): array
    {
        $effectiveConfig = $this->runtimeOverrides->apply($this->config);
        $queue = new FileEventQueue($effectiveConfig['queue']);

        return array(
            'generatedAt' => gmdate('c'),
            'service'     => array(
                'baseUrl'     => (string) ($effectiveConfig['service']['base_url'] ?? ''),
                'sourceSystem'=> (string) ($effectiveConfig['service']['source_system'] ?? ''),
                'defaultLocale' => (string) ($effectiveConfig['service']['default_locale'] ?? ''),
            ),
            'queue' => array(
                'pending'    => $queue->countFiles('pending'),
                'processing' => $queue->countFiles('processing'),
                'processed'  => $queue->countFiles('processed'),
                'failed'     => $queue->countFiles('failed'),
            ),
            'health' => $this->buildHealth($effectiveConfig),
            'runtime' => $this->runtimeOverrides->snapshot($effectiveConfig),
            'deliveries' => $this->recentDeliveries($limit),
            'whatsapp' => array(
                'inboundMode'    => (string) ($effectiveConfig['webhooks']['whatsapp']['inbound_mode'] ?? 'log_only'),
                'recentStatuses' => $this->whatsAppState->recentLogEntries($limit, 'status'),
                'recentInbound'  => $this->whatsAppState->recentLogEntries($limit, 'inbound'),
            ),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function runConsumer(int $limit = 20, bool $debug = false): array
    {
        $consumer = new QueueConsumer($this->config);

        return $consumer->run($limit, $debug);
    }

    /**
     * @return array<string,mixed>
     */
    public function requeueFailed(int $limit = 20): array
    {
        $queue = new FileEventQueue($this->config['queue']);

        return array(
            'moved'      => $queue->requeueFailed($limit),
            'requeuedAt' => gmdate('c'),
        );
    }

    public function setProviderEnabled(string $providerName, bool $isEnabled): void
    {
        $this->runtimeOverrides->setProviderEnabled($providerName, $isEnabled);
    }

    public function setEventEnabled(string $eventType, bool $isEnabled): void
    {
        $this->runtimeOverrides->setEventEnabled($eventType, $isEnabled);
    }

    /**
     * @return array<string,mixed>
     */
    public function ping(): array
    {
        return array(
            'ok'         => true,
            'service'    => (string) ($this->config['service']['name'] ?? 'bornado-notification-platform'),
            'generatedAt'=> gmdate('c'),
        );
    }

    /**
     * @param array<string,mixed> $effectiveConfig
     * @return array<int,array<string,mixed>>
     */
    private function buildHealth(array $effectiveConfig): array
    {
        $health = array();
        $providerConfig = isset($effectiveConfig['providers']['whatsapp-cloud-api']) && is_array($effectiveConfig['providers']['whatsapp-cloud-api'])
            ? $effectiveConfig['providers']['whatsapp-cloud-api']
            : array();
        $webhookConfig = isset($effectiveConfig['webhooks']['whatsapp']) && is_array($effectiveConfig['webhooks']['whatsapp'])
            ? $effectiveConfig['webhooks']['whatsapp']
            : array();
        $heartbeatPath = (string) ($effectiveConfig['logging']['ops_dir'] ?? '') . DIRECTORY_SEPARATOR . 'consumer-heartbeat.json';
        $heartbeat     = is_file($heartbeatPath) ? json_decode((string) file_get_contents($heartbeatPath), true) : array();

        $health[] = array(
            'label'  => 'Service shared secret',
            'status' => '' !== trim((string) ($effectiveConfig['service']['shared_secret'] ?? '')) ? 'ok' : 'warning',
        );
        $health[] = array(
            'label'  => 'Ops key',
            'status' => '' !== trim((string) ($effectiveConfig['service']['ops_key'] ?? '')) ? 'ok' : 'warning',
        );
        $health[] = array(
            'label'  => 'WhatsApp provider enabled',
            'status' => !empty($providerConfig['enabled']) ? 'ok' : 'warning',
        );
        $health[] = array(
            'label'  => 'WhatsApp phone number ID',
            'status' => '' !== trim((string) ($providerConfig['phone_number_id'] ?? '')) ? 'ok' : 'warning',
        );
        $health[] = array(
            'label'  => 'WhatsApp access token',
            'status' => '' !== trim((string) ($providerConfig['access_token'] ?? '')) ? 'ok' : 'warning',
        );
        $health[] = array(
            'label'  => 'Webhook verify token',
            'status' => '' !== trim((string) ($webhookConfig['verify_token'] ?? '')) ? 'ok' : 'warning',
        );
        $health[] = array(
            'label'  => 'Webhook app secret',
            'status' => '' !== trim((string) ($webhookConfig['app_secret'] ?? '')) ? 'ok' : 'warning',
        );
        $health[] = array(
            'label'  => 'Last consumer heartbeat',
            'status' => !empty($heartbeat['processedAt']) ? 'ok' : 'warning',
            'value'  => (string) ($heartbeat['processedAt'] ?? 'never'),
        );

        return $health;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function recentDeliveries(int $limit): array
    {
        $logFile = (string) ($this->config['logging']['delivery_log'] ?? '');
        if ('' === $logFile || !is_file($logFile)) {
            return array();
        }

        $lines   = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: array();
        $lines   = array_reverse($lines);
        $entries = array();

        foreach ($lines as $line) {
            $decoded = json_decode((string) $line, true);
            if (!is_array($decoded)) {
                continue;
            }

            $entries[] = $decoded;
            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }
}
