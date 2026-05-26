<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Application;

use Bornado\NotificationPlatform\Domain\ContactModel;

final class PolicyEngine
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
     * @return array<string,mixed>
     */
    public function buildPlan(array $event): array
    {
        $routing       = isset($this->config['routing']) && is_array($this->config['routing']) ? $this->config['routing'] : array();
        $eventType     = (string) ($event['eventType'] ?? '');
        $eventConfig   = isset($routing['events'][$eventType]) && is_array($routing['events'][$eventType]) ? $routing['events'][$eventType] : array();
        $channels      = isset($eventConfig['channels']) && is_array($eventConfig['channels']) ? $eventConfig['channels'] : array();
        $contactPool   = ContactModel::fromEvent($event);
        $plan          = array();
        $skipped       = array();

        foreach ($channels as $channel) {
            $eligibleContacts = ContactModel::eligibleContactsForChannel($contactPool, (string) $channel, $routing);

            if (empty($eligibleContacts)) {
                $skipped[] = array(
                    'channel' => (string) $channel,
                    'reason'  => 'no_eligible_contacts',
                );
                continue;
            }

            $plan[] = array(
                'channel'   => (string) $channel,
                'contacts'  => $eligibleContacts,
                'providers' => $this->getProvidersForChannel((string) $channel),
            );
        }

        return array(
            'plan'    => $plan,
            'skipped' => $skipped,
        );
    }

    /**
     * @return array<int,string>
     */
    private function getProvidersForChannel(string $channel): array
    {
        $channelProviders = isset($this->config['routing']['channel_providers']) && is_array($this->config['routing']['channel_providers'])
            ? $this->config['routing']['channel_providers']
            : array();

        $providers = $channelProviders[$channel] ?? array('dry-run');

        return array_values(
            array_filter(
                array_map(
                    static function ($provider): string {
                        return trim((string) $provider);
                    },
                    is_array($providers) ? $providers : array()
                )
            )
        );
    }
}
