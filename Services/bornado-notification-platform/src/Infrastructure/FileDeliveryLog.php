<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

final class FileDeliveryLog
{
    /**
     * @var string
     */
    private $deliveryLogFile;

    /**
     * @var string
     */
    private $stateDirectory;

    public function __construct(string $deliveryLogFile, string $stateDirectory)
    {
        $this->deliveryLogFile = $deliveryLogFile;
        $this->stateDirectory  = $stateDirectory;
        $this->ensureDirectories();
    }

    public function hasProcessed(string $eventId): bool
    {
        $path = $this->stateFilePath($eventId);
        if (!is_file($path)) {
            return false;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return false;
        }

        return in_array((string) ($decoded['status'] ?? ''), array('sent', 'failed', 'invalid', 'duplicate', 'no_route'), true);
    }

    /**
     * @param array<string,mixed> $event
     * @param array<string,mixed> $context
     */
    public function markEvent(array $event, string $status, array $context = array()): void
    {
        $record = array(
            'eventId'        => (string) ($event['eventId'] ?? ''),
            'eventType'      => (string) ($event['eventType'] ?? ''),
            'status'         => $status,
            'occurredAt'     => (string) ($event['occurredAt'] ?? ''),
            'recordedAt'     => gmdate('c'),
            'sourceSystem'   => (string) ($event['sourceSystem'] ?? ''),
            'idempotencyKey' => (string) ($event['idempotencyKey'] ?? ''),
            'context'        => $context,
        );

        $this->appendRecord($record);

        if ('' !== $record['eventId']) {
            file_put_contents($this->stateFilePath($record['eventId']), json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * @param array<string,mixed> $attempt
     */
    public function appendAttempt(array $attempt): void
    {
        $attempt['recordedAt'] = gmdate('c');
        $this->appendRecord($attempt);
    }

    private function ensureDirectories(): void
    {
        $logDirectory = dirname($this->deliveryLogFile);

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true);
        }

        if (!is_dir($this->stateDirectory)) {
            mkdir($this->stateDirectory, 0777, true);
        }
    }

    /**
     * @param array<string,mixed> $record
     */
    private function appendRecord(array $record): void
    {
        file_put_contents(
            $this->deliveryLogFile,
            json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }

    private function stateFilePath(string $eventId): string
    {
        return $this->stateDirectory . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9._-]/', '_', $eventId) . '.json';
    }
}
