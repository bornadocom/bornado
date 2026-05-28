<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Application;

use Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog;
use Bornado\NotificationPlatform\Infrastructure\FileEventQueue;
use Bornado\NotificationPlatform\Infrastructure\ProviderFactory;
use Bornado\NotificationPlatform\Infrastructure\RuntimeOverridesStore;
use Bornado\NotificationPlatform\Infrastructure\WhatsAppStateStore;

final class QueueConsumer
{
    /**
     * @var array<string,mixed>
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array<string,mixed>
     */
    public function run(int $limit = 20, bool $debug = false): array
    {
        $effectiveConfig = $this->loadEffectiveConfig();
        $queue           = new FileEventQueue($effectiveConfig['queue']);
        $deliveryLog     = new FileDeliveryLog($effectiveConfig['logging']['delivery_log'], $effectiveConfig['logging']['state_dir']);
        $whatsAppState   = new WhatsAppStateStore(
            (string) ($effectiveConfig['logging']['whatsapp_state_dir'] ?? ''),
            (string) ($effectiveConfig['logging']['whatsapp_webhook_log'] ?? ''),
            (string) ($effectiveConfig['logging']['whatsapp_inbound_dir'] ?? '')
        );
        $policyEngine    = new PolicyEngine($effectiveConfig);
        $templateEngine  = new TemplateEngine($effectiveConfig);
        $providers       = ProviderFactory::buildAll($effectiveConfig);
        $orchestrator    = new NotificationOrchestrator($policyEngine, $templateEngine, $deliveryLog, $providers, $whatsAppState);
        $claims          = $queue->claimBatch($limit);
        $results         = array();

        foreach ($claims as $claim) {
            $event  = is_array($claim['event'] ?? null) ? $claim['event'] : array();
            $result = $orchestrator->handle($event);
            if (!$debug && is_array($result)) {
                unset($result['attempts']);
            }

            $results[] = $result;

            if (in_array($result['status'] ?? '', array('sent', 'no_route', 'duplicate', 'invalid'), true)) {
                $queue->acknowledge((string) $claim['path'], $result);
                continue;
            }

            $queue->fail((string) $claim['path'], $result);
        }

        $payload = array(
            'processed'   => count($claims),
            'results'     => $results,
            'processedAt' => gmdate('c'),
        );

        $this->recordHeartbeat($payload);

        return $payload;
    }

    /**
     * @return array<string,mixed>
     */
    private function loadEffectiveConfig(): array
    {
        $opsDir = (string) ($this->config['logging']['ops_dir'] ?? '');
        if ('' === $opsDir) {
            return $this->config;
        }

        $runtimeOverrides = new RuntimeOverridesStore($opsDir . DIRECTORY_SEPARATOR . 'runtime-overrides.json');

        return $runtimeOverrides->apply($this->config);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function recordHeartbeat(array $payload): void
    {
        $opsDir = (string) ($this->config['logging']['ops_dir'] ?? '');
        if ('' === $opsDir) {
            return;
        }

        if (!is_dir($opsDir)) {
            mkdir($opsDir, 0777, true);
        }

        file_put_contents(
            $opsDir . DIRECTORY_SEPARATOR . 'consumer-heartbeat.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
