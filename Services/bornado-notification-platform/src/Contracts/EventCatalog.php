<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Contracts;

final class EventCatalog
{
    public const SUPPORTED_TYPES = array(
        'listing.published'    => array('payload.listing.title', 'payload.listing.manageUrl'),
        'user.registered'      => array('payload.user.profileUrl'),
        'listing.rejected'     => array('payload.listing.title', 'payload.listing.editUrl'),
        'listing.expiring_soon'=> array('payload.listing.title', 'payload.listing.manageUrl'),
        'payment.completed'    => array('payload.payment.orderId'),
    );

    /**
     * @return array<string,mixed>
     */
    public static function definitions(): array
    {
        $definitions = array();

        foreach (self::SUPPORTED_TYPES as $eventType => $requiredFields) {
            $definitions[$eventType] = array(
                'currentVersion' => 1,
                'requiredFields' => $requiredFields,
                'idempotencyScope' => 'eventType + businessObject + version',
            );
        }

        return $definitions;
    }

    /**
     * @param array<string,mixed> $event
     * @return array<int,string>
     */
    public static function validate(array $event): array
    {
        $errors = array();

        foreach (array('eventId', 'eventType', 'eventVersion', 'occurredAt', 'sourceSystem', 'idempotencyKey') as $requiredKey) {
            if (!array_key_exists($requiredKey, $event) || '' === trim((string) ($event[$requiredKey] ?? ''))) {
                $errors[] = sprintf('Missing required key: %s', $requiredKey);
            }
        }

        if (!array_key_exists('payload', $event)) {
            $errors[] = 'Missing required key: payload';
        }

        $eventType = isset($event['eventType']) ? (string) $event['eventType'] : '';
        if ('' === $eventType || !isset(self::SUPPORTED_TYPES[$eventType])) {
            $errors[] = sprintf('Unsupported event type: %s', $eventType ?: 'unknown');
            return $errors;
        }

        if (!is_array($event['payload'] ?? null)) {
            $errors[] = 'Payload must be an object.';
            return $errors;
        }

        foreach (self::SUPPORTED_TYPES[$eventType] as $requiredPath) {
            if (null === self::getByPath($event, $requiredPath)) {
                $errors[] = sprintf('Missing required payload field: %s', $requiredPath);
            }
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $source
     * @return mixed
     */
    public static function getByPath(array $source, string $path)
    {
        $segments = explode('.', $path);
        $cursor   = $source;

        foreach ($segments as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
