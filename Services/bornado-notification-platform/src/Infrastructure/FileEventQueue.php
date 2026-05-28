<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

final class FileEventQueue
{
    /**
     * @var array<string,string>
     */
    private $config;

    /**
     * @param array<string,string> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->ensureDirectories();
    }

    /**
     * @param array<string,mixed> $event
     */
    public function enqueue(array $event): string
    {
        $fileName = sprintf(
            '%s-%s.json',
            gmdate('YmdHis'),
            preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($event['eventId'] ?? uniqid('event_', true)))
        );

        $path = $this->config['pending_dir'] . DIRECTORY_SEPARATOR . $fileName;

        file_put_contents($path, json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function claimBatch(int $limit = 20): array
    {
        $pendingFiles = glob($this->config['pending_dir'] . DIRECTORY_SEPARATOR . '*.json') ?: array();
        sort($pendingFiles);

        $claims = array();

        foreach (array_slice($pendingFiles, 0, $limit) as $pendingFile) {
            $processingFile = $this->config['processing_dir'] . DIRECTORY_SEPARATOR . basename($pendingFile);

            if (!@rename($pendingFile, $processingFile)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($processingFile), true);
            if (!is_array($decoded)) {
                $this->fail($processingFile, array('status' => 'invalid_json'));
                continue;
            }

            $claims[] = array(
                'path'  => $processingFile,
                'event' => $decoded,
            );
        }

        return $claims;
    }

    /**
     * @param array<string,mixed> $metadata
     */
    public function acknowledge(string $processingPath, array $metadata = array()): void
    {
        $this->finalize($processingPath, $this->config['processed_dir'], $metadata);
    }

    /**
     * @param array<string,mixed> $metadata
     */
    public function fail(string $processingPath, array $metadata = array()): void
    {
        $this->finalize($processingPath, $this->config['failed_dir'], $metadata);
    }

    public function countFiles(string $bucket): int
    {
        $map = array(
            'pending'    => $this->config['pending_dir'] ?? '',
            'processing' => $this->config['processing_dir'] ?? '',
            'processed'  => $this->config['processed_dir'] ?? '',
            'failed'     => $this->config['failed_dir'] ?? '',
        );

        $directory = isset($map[$bucket]) ? (string) $map[$bucket] : '';
        if ('' === $directory) {
            return 0;
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: array();

        return count($files);
    }

    public function requeueFailed(int $limit = 20): int
    {
        $failedFiles = glob($this->config['failed_dir'] . DIRECTORY_SEPARATOR . '*.json') ?: array();
        sort($failedFiles);

        $moved = 0;

        foreach (array_slice($failedFiles, 0, $limit) as $failedFile) {
            $pendingFile = $this->config['pending_dir'] . DIRECTORY_SEPARATOR . basename($failedFile);
            if (@rename($failedFile, $pendingFile)) {
                $moved++;
            }
        }

        return $moved;
    }

    private function ensureDirectories(): void
    {
        foreach ($this->config as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function finalize(string $processingPath, string $targetDirectory, array $metadata): void
    {
        $decoded = json_decode((string) file_get_contents($processingPath), true);
        $payload = is_array($decoded) ? $decoded : array('rawFile' => basename($processingPath));

        if (!empty($metadata)) {
            $payload['_processing'] = $metadata;
        }

        $finalPath = $targetDirectory . DIRECTORY_SEPARATOR . basename($processingPath);
        file_put_contents($finalPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @unlink($processingPath);
    }
}
