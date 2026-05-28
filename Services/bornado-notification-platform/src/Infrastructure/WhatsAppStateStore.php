<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

final class WhatsAppStateStore
{
    /**
     * @var string
     */
    private $stateDirectory;

    /**
     * @var string
     */
    private $logFile;

    /**
     * @var string
     */
    private $inboundDirectory;

    public function __construct(string $stateDirectory, string $logFile, string $inboundDirectory)
    {
        $this->stateDirectory   = $stateDirectory;
        $this->logFile          = $logFile;
        $this->inboundDirectory = $inboundDirectory;
        $this->ensureDirectories();
    }

    /**
     * @param array<string,mixed> $event
     * @param array<string,mixed> $contact
     * @param array<string,mixed> $result
     */
    public function recordOutboundDispatch(array $event, array $contact, array $result): void
    {
        $messageId = trim((string) ($result['messageId'] ?? ''));
        if ('' === $messageId) {
            return;
        }

        $current = $this->readMessageState($messageId);
        $record  = array(
            'wamid'          => $messageId,
            'eventId'        => (string) ($event['eventId'] ?? ''),
            'eventType'      => (string) ($event['eventType'] ?? ''),
            'destination'    => (string) ($contact['address'] ?? ''),
            'providerStatus' => (string) ($result['code'] ?? ''),
            'httpStatus'     => (int) ($result['httpStatus'] ?? 0),
            'requestPayload' => $result['requestPayload'] ?? array(),
            'response'       => $result['response'] ?? array(),
            'createdAt'      => $current['createdAt'] ?? gmdate('c'),
            'updatedAt'      => gmdate('c'),
            'lastWebhookStatus' => $current['lastWebhookStatus'] ?? null,
            'webhookHistory' => isset($current['webhookHistory']) && is_array($current['webhookHistory'])
                ? $current['webhookHistory']
                : array(),
        );

        $this->writeMessageState($messageId, $record);
        $this->appendLog(
            array(
                'kind'      => 'outbound',
                'wamid'     => $messageId,
                'eventId'   => $record['eventId'],
                'eventType' => $record['eventType'],
                'status'    => $record['providerStatus'],
                'httpStatus'=> $record['httpStatus'],
                'destination' => $record['destination'],
            )
        );
    }

    /**
     * @param array<string,mixed> $status
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function applyStatusUpdate(array $status, array $context = array()): array
    {
        $messageId = trim((string) ($status['id'] ?? ''));
        if ('' === $messageId) {
            return array(
                'handled' => false,
                'reason'  => 'missing_message_id',
            );
        }

        $current = $this->readMessageState($messageId);
        $history = isset($current['webhookHistory']) && is_array($current['webhookHistory'])
            ? $current['webhookHistory']
            : array();

        $history[] = array(
            'status'       => (string) ($status['status'] ?? ''),
            'timestamp'    => (string) ($status['timestamp'] ?? ''),
            'conversation' => $status['conversation'] ?? null,
            'pricing'      => $status['pricing'] ?? null,
            'errors'       => $status['errors'] ?? null,
            'recordedAt'   => gmdate('c'),
        );

        $record = array_merge(
            $current,
            array(
                'wamid'             => $messageId,
                'lastWebhookStatus' => (string) ($status['status'] ?? ''),
                'updatedAt'         => gmdate('c'),
                'webhookHistory'    => $history,
                'webhookContext'    => $context,
            )
        );

        $this->writeMessageState($messageId, $record);
        $this->appendLog(
            array(
                'kind'      => 'status',
                'wamid'     => $messageId,
                'status'    => (string) ($status['status'] ?? ''),
                'eventId'   => (string) ($record['eventId'] ?? ''),
                'eventType' => (string) ($record['eventType'] ?? ''),
                'destination' => (string) ($status['recipient_id'] ?? ($record['destination'] ?? '')),
            )
        );

        return array(
            'handled' => true,
            'wamid'   => $messageId,
            'status'  => (string) ($status['status'] ?? ''),
            'eventId' => (string) ($record['eventId'] ?? ''),
        );
    }

    /**
     * @param array<string,mixed> $message
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function recordInboundMessage(array $message, array $context = array()): array
    {
        $messageId = trim((string) ($message['id'] ?? ''));
        if ('' === $messageId) {
            return array(
                'handled' => false,
                'reason'  => 'missing_message_id',
            );
        }

        $record = array(
            'messageId'   => $messageId,
            'from'        => (string) ($message['from'] ?? ''),
            'type'        => (string) ($message['type'] ?? ''),
            'timestamp'   => (string) ($message['timestamp'] ?? ''),
            'payload'     => $message,
            'context'     => $context,
            'recordedAt'  => gmdate('c'),
        );

        file_put_contents(
            $this->inboundPath($messageId),
            json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->appendLog(
            array(
                'kind'      => 'inbound',
                'messageId' => $messageId,
                'from'      => $record['from'],
                'type'      => $record['type'],
            )
        );

        return array(
            'handled'   => true,
            'messageId' => $messageId,
            'mode'      => (string) ($context['mode'] ?? 'log_only'),
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function recentLogEntries(int $limit = 20, ?string $kind = null): array
    {
        if (!is_file($this->logFile)) {
            return array();
        }

        $lines   = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: array();
        $lines   = array_reverse($lines);
        $entries = array();

        foreach ($lines as $line) {
            $decoded = json_decode((string) $line, true);
            if (!is_array($decoded)) {
                continue;
            }

            if (null !== $kind && (string) ($decoded['kind'] ?? '') !== $kind) {
                continue;
            }

            $entries[] = $decoded;
            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    private function ensureDirectories(): void
    {
        foreach (array($this->stateDirectory, dirname($this->logFile), $this->inboundDirectory) as $directory) {
            if ('' !== $directory && !is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function readMessageState(string $messageId): array
    {
        $path = $this->messageStatePath($messageId);
        if (!is_file($path)) {
            return array();
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : array();
    }

    /**
     * @param array<string,mixed> $record
     */
    private function writeMessageState(string $messageId, array $record): void
    {
        file_put_contents(
            $this->messageStatePath($messageId),
            json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @param array<string,mixed> $record
     */
    private function appendLog(array $record): void
    {
        $record['recordedAt'] = gmdate('c');

        file_put_contents(
            $this->logFile,
            json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }

    private function messageStatePath(string $messageId): string
    {
        return $this->stateDirectory . DIRECTORY_SEPARATOR . $this->sanitize($messageId) . '.json';
    }

    private function inboundPath(string $messageId): string
    {
        return $this->inboundDirectory . DIRECTORY_SEPARATOR . $this->sanitize($messageId) . '.json';
    }

    private function sanitize(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '_', $value) ?: 'unknown';
    }
}
