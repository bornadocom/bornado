<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Infrastructure;

final class FileSchemaCache
{
    /** @var string */
    private $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
    }

    /**
     * @param callable():array<string,mixed> $callback
     * @return array<string,mixed>
     */
    public function remember(string $key, int $ttlSeconds, callable $callback): array
    {
        $path = $this->getPath($key);
        $now  = time();

        if (is_file($path)) {
            $cached = json_decode((string) file_get_contents($path), true);
            if (
                is_array($cached)
                && isset($cached['createdAt'], $cached['payload'])
                && ($now - (int) $cached['createdAt']) < $ttlSeconds
                && is_array($cached['payload'])
            ) {
                return $cached['payload'];
            }
        }

        $payload = $callback();
        $this->ensureDirectory($this->cacheDir);
        file_put_contents(
            $path,
            json_encode(
                array(
                    'createdAt' => $now,
                    'payload' => $payload,
                ),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );

        return $payload;
    }

    private function getPath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . sha1($key) . '.json';
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        mkdir($directory, 0777, true);
    }
}
