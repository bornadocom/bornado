<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

final class DryRunProviderAdapter implements ProviderAdapterInterface
{
    /**
     * @var array<string,mixed>
     */
    private $config;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'dry-run';
    }

    /**
     * @param array<string,mixed> $contact
     * @param array<string,string> $message
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function send(string $channel, array $contact, array $message, array $context): array
    {
        if ('' === trim((string) ($contact['address'] ?? ''))) {
            return array(
                'success' => false,
                'code'    => 'missing_destination',
                'message' => 'Destination address is required.',
            );
        }

        return array(
            'success'         => true,
            'code'            => 'dry_run_accepted',
            'providerMessage' => 'Message accepted in dry-run mode.',
            'channel'         => $channel,
            'provider'        => $this->getName(),
            'destination'     => (string) $contact['address'],
            'body'            => (string) ($message['body'] ?? ''),
            'subject'         => (string) ($message['subject'] ?? ''),
            'mode'            => (string) ($this->config['mode'] ?? 'dry-run'),
            'eventId'         => (string) ($context['eventId'] ?? ''),
        );
    }
}
