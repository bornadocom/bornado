<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

interface ProviderAdapterInterface
{
    public function getName(): string;

    /**
     * @param array<string,mixed> $contact
     * @param array<string,string> $message
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function send(string $channel, array $contact, array $message, array $context): array;
}
