<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Domain;

final class ContactModel
{
    /**
     * @param array<string,mixed> $event
     * @return array<int,array<string,mixed>>
     */
    public static function fromEvent(array $event): array
    {
        $payload      = isset($event['payload']) && is_array($event['payload']) ? $event['payload'] : array();
        $user         = isset($payload['user']) && is_array($payload['user']) ? $payload['user'] : array();
        $contactItems = isset($user['contacts']) && is_array($user['contacts']) ? $user['contacts'] : array();
        $contacts     = array();

        foreach ($contactItems as $contactItem) {
            if (!is_array($contactItem)) {
                continue;
            }

            $normalized = self::normalizeContact($contactItem);
            if (null !== $normalized) {
                $contacts[] = $normalized;
            }
        }

        $email = self::normalizeEmail((string) ($user['email'] ?? ''));
        if ('' !== $email) {
            $contacts[] = self::normalizeContact(
                array(
                    'channel'      => 'email',
                    'address'      => $email,
                    'verified'     => true,
                    'primary'      => true,
                    'priority'     => 10,
                    'capabilities' => array(
                        'email'         => true,
                        'transactional' => true,
                    ),
                )
            );
        }

        $phone = self::normalizePhone((string) ($user['phone'] ?? ''));
        if ('' !== $phone) {
            $whatsAppCapability = self::extractChannelCapability($user, 'whatsapp');
            $contacts[]         = self::normalizeContact(
                array(
                    'channel'      => 'sms',
                    'address'      => $phone,
                    'verified'     => self::normalizeBoolean($user['phoneVerified'] ?? false),
                    'primary'      => true,
                    'priority'     => 20,
                    'capabilities' => array(
                        'sms'           => true,
                        'transactional' => true,
                    ),
                )
            );
            $contacts[]         = self::normalizeContact(
                array(
                    'channel'      => 'whatsapp',
                    'address'      => $phone,
                    'verified'     => self::normalizeBoolean($user['phoneVerified'] ?? false),
                    'primary'      => true,
                    'priority'     => 30,
                    'capabilities' => array(
                        'whatsapp'      => $whatsAppCapability,
                        'transactional' => true,
                    ),
                )
            );
        }

        return self::deduplicate($contacts);
    }

    /**
     * @param array<int,array<string,mixed>> $contacts
     * @param array<string,mixed> $routingConfig
     * @return array<int,array<string,mixed>>
     */
    public static function eligibleContactsForChannel(array $contacts, string $channel, array $routingConfig = array()): array
    {
        $eligible                 = array();
        $optimisticChannelRouting = isset($routingConfig['allow_optimistic_channel_routing']) && is_array($routingConfig['allow_optimistic_channel_routing'])
            ? $routingConfig['allow_optimistic_channel_routing']
            : array();

        foreach ($contacts as $contact) {
            if (!is_array($contact)) {
                continue;
            }

            if (($contact['channel'] ?? '') !== $channel) {
                continue;
            }

            $capability = $contact['capabilities'][$channel] ?? false;
            if (true === $capability) {
                $eligible[] = $contact;
                continue;
            }

            if (
                'unknown' === $capability
                && !empty($optimisticChannelRouting[$channel])
                && !empty($contact['capabilities']['transactional'])
            ) {
                $eligible[] = $contact;
            }
        }

        usort(
            $eligible,
            static function (array $left, array $right): int {
                return (int) ($left['priority'] ?? 100) <=> (int) ($right['priority'] ?? 100);
            }
        );

        return $eligible;
    }

    /**
     * @param array<string,mixed> $contact
     * @return array<string,mixed>|null
     */
    public static function normalizeContact(array $contact): ?array
    {
        $channel = strtolower(trim((string) ($contact['channel'] ?? '')));
        $address = trim((string) ($contact['address'] ?? ''));

        if ('email' === $channel) {
            $address = self::normalizeEmail($address);
        } elseif (in_array($channel, array('sms', 'whatsapp'), true)) {
            $address = self::normalizePhone($address);
        }

        if ('' === $channel || '' === $address) {
            return null;
        }

        $capabilities = isset($contact['capabilities']) && is_array($contact['capabilities']) ? $contact['capabilities'] : array();

        return array(
            'channel'      => $channel,
            'address'      => $address,
            'verified'     => self::normalizeBoolean($contact['verified'] ?? false),
            'primary'      => self::normalizeBoolean($contact['primary'] ?? false),
            'priority'     => (int) ($contact['priority'] ?? 100),
            'capabilities' => $capabilities,
        );
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';

        if ('' === $phone) {
            return '';
        }

        if (0 === strpos($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        } elseif ('+' !== substr($phone, 0, 1)) {
            $phone = '+' . ltrim($phone, '+');
        }

        $digits = preg_replace('/[^\d]/', '', $phone) ?? '';

        return '' === $digits ? '' : '+' . $digits;
    }

    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /**
     * @param array<int,array<string,mixed>|null> $contacts
     * @return array<int,array<string,mixed>>
     */
    private static function deduplicate(array $contacts): array
    {
        $result = array();
        $seen   = array();

        foreach ($contacts as $contact) {
            if (!is_array($contact)) {
                continue;
            }

            $key = ($contact['channel'] ?? '') . '|' . ($contact['address'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[]   = $contact;
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $user
     * @return bool|string
     */
    private static function extractChannelCapability(array $user, string $channel)
    {
        $capabilities = isset($user['channelCapabilities']) && is_array($user['channelCapabilities']) ? $user['channelCapabilities'] : array();

        if (array_key_exists($channel, $capabilities)) {
            $value = $capabilities[$channel];
            if (null === $value || '' === $value) {
                return 'unknown';
            }

            return self::normalizeBoolean($value);
        }

        return 'unknown';
    }

    /**
     * @param mixed $value
     */
    private static function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, array('1', 'true', 'yes', 'on'), true);
    }
}
