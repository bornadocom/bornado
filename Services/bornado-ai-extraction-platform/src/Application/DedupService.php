<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Application;

final class DedupService
{
    /** @var string */
    private $stateFile;

    /** @var string */
    private $version;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $dedupConfig = isset($config['dedup']) && is_array($config['dedup'])
            ? $config['dedup']
            : array();
        $loggingConfig = isset($config['logging']) && is_array($config['logging'])
            ? $config['logging']
            : array();

        $stateFile = trim((string) ($dedupConfig['state_file'] ?? ''));
        if ('' === $stateFile) {
            $stateDir = trim((string) ($loggingConfig['state_dir'] ?? ''));
            $stateFile = '' !== $stateDir
                ? rtrim($stateDir, '/\\') . DIRECTORY_SEPARATOR . 'dedup-state.json'
                : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bornado-dedup-state.json';
        }

        $this->stateFile = $stateFile;
        $this->version = trim((string) ($dedupConfig['version'] ?? 'v1'));
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function preflight(array $payload): array
    {
        $context = $this->buildContext($payload);
        $state = $this->loadState();

        $match = $this->findBestMatch($state, $context);
        $matchedRecord = isset($match['record']) && is_array($match['record'])
            ? $match['record']
            : array();
        $matchType = (string) ($match['type'] ?? '');
        $cachedOcrText = $this->extractCachedOcrText($matchedRecord);

        $decision = 'new';
        $reason = 'No matching record found.';
        $shouldProcess = true;

        if ('source' === $matchType || 'content' === $matchType || 'text' === $matchType) {
            $decision = 'exact_duplicate';
            $reason = 'Matched an existing source or canonical content fingerprint.';
            $shouldProcess = false;
        } elseif ('image' === $matchType && '' !== $cachedOcrText) {
            $decision = 'reuse_ocr';
            $reason = 'Matched an image fingerprint with cached OCR text.';
        } elseif ('probable' === $matchType) {
            $decision = 'probable_duplicate';
            $reason = 'Matched a soft duplicate heuristic. Processing continues conservatively.';
        }

        return array(
            'status' => 'ok',
            'decision' => $decision,
            'reason' => $reason,
            'should_process' => $shouldProcess,
            'should_run_ocr' => $shouldProcess && $context['has_image'] && '' === $context['content']['ocr_text'] && '' === $cachedOcrText,
            'cached_ocr_text' => $cachedOcrText,
            'effective_ocr_text' => '' !== $context['content']['ocr_text']
                ? $context['content']['ocr_text']
                : $cachedOcrText,
            'canonical' => array(
                'post_id' => (string) ($context['source']['post_id'] ?? ''),
                'origin_url' => (string) ($context['source']['origin_url'] ?? ''),
                'group_post_url' => (string) ($context['source']['group_post_url'] ?? ''),
                'publisher_name' => (string) ($context['source']['publisher_name'] ?? ''),
                'publisher_profile_url' => (string) ($context['source']['publisher_profile_url'] ?? ''),
                'text' => (string) ($context['content']['text'] ?? ''),
                'first_image_url' => (string) ($context['content']['first_image_url'] ?? ''),
                'first_attachment_token' => (string) ($context['content']['first_attachment_token'] ?? ''),
                'first_media_id' => (string) ($context['content']['first_media_id'] ?? ''),
            ),
            'fingerprints' => $context['fingerprints'],
            'matched_record' => $this->summarizeRecord($matchedRecord),
        );
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function finalize(array $payload): array
    {
        $context = $this->buildContext($payload);
        $result = $this->withLockedState(
            /**
             * @param array<string,mixed> $state
             * @return array<string,mixed>
             */
            function (array $state) use ($context): array {
                $match = $this->findBestMatch($state, $context);
                $record = isset($match['record']) && is_array($match['record'])
                    ? $match['record']
                    : array();
                $recordId = isset($record['id']) ? (string) $record['id'] : '';

                if ('' === $recordId) {
                    $recordId = $this->generateRecordId($context);
                    $record = array(
                        'id' => $recordId,
                        'created_at' => gmdate('c'),
                    );
                }

                $record['updated_at'] = gmdate('c');
                $record['market'] = $context['market'];
                $record['channel'] = $context['channel'];
                $record['source'] = $context['source'];
                $record['content'] = $context['content'];
                $record['fingerprints'] = $context['fingerprints'];
                $record['cache'] = array(
                    'scraper_ocr_text' => $context['content']['scraper_ocr_text'],
                    'ocr_text' => $context['content']['ocr_text'],
                    'classification' => $context['ai']['classify'],
                    'extraction' => $context['ai']['extract'],
                );
                $record['publish'] = $context['publish'];

                if (!isset($state['records']) || !is_array($state['records'])) {
                    $state['records'] = array();
                }
                $state['records'][$recordId] = $record;

                if (!isset($state['indices']) || !is_array($state['indices'])) {
                    $state['indices'] = $this->defaultState()['indices'];
                }

                foreach ((array) $context['fingerprints']['source_keys'] as $sourceKey) {
                    if ('' !== $sourceKey) {
                        $state['indices']['source'][(string) $sourceKey] = $recordId;
                    }
                }

                foreach (array('content' => 'content_key', 'image' => 'image_key', 'text' => 'text_key') as $bucket => $field) {
                    $fingerprint = (string) ($context['fingerprints'][$field] ?? '');
                    if ('' !== $fingerprint) {
                        $state['indices'][$bucket][$fingerprint] = $recordId;
                    }
                }

                $state['updated_at'] = gmdate('c');

                return array(
                    'state' => $state,
                    'response' => array(
                        'status' => 'ok',
                        'record_id' => $recordId,
                        'fingerprints' => $context['fingerprints'],
                        'cache' => array(
                            'has_ocr_text' => '' !== $context['content']['ocr_text'],
                            'classification_status' => (string) ($context['ai']['classify']['status'] ?? ''),
                            'extraction_status' => (string) ($context['ai']['extract']['status'] ?? ''),
                            'published_post_id' => (int) ($context['publish']['post_id'] ?? 0),
                        ),
                    ),
                );
            }
        );

        return isset($result['response']) && is_array($result['response'])
            ? $result['response']
            : array('status' => 'ok');
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function buildContext(array $payload): array
    {
        $market = trim(strtolower((string) ($payload['market'] ?? '')));
        $channel = trim(strtolower((string) ($payload['channel'] ?? '')));

        $source = isset($payload['source']) && is_array($payload['source'])
            ? $payload['source']
            : array();
        $content = isset($payload['content']) && is_array($payload['content'])
            ? $payload['content']
            : array();
        $ai = isset($payload['ai']) && is_array($payload['ai'])
            ? $payload['ai']
            : array();
        $publish = isset($payload['publish']) && is_array($payload['publish'])
            ? $payload['publish']
            : array();

        $postId = $this->firstNonEmptyString(
            $source['effective_post_id'] ?? null,
            $source['post_id'] ?? null,
            $source['legacy_id'] ?? null,
            $payload['post_id'] ?? null
        );
        $groupPostUrl = $this->normalizeUrl(
            $this->firstNonEmptyString(
                $source['group_post_url'] ?? null,
                $source['post_url'] ?? null,
                $payload['group_post_url'] ?? null,
                $payload['post_url'] ?? null
            )
        );
        $originUrl = $this->normalizeUrl(
            $this->firstNonEmptyString(
                $source['origin_url'] ?? null,
                $source['effective_origin_url'] ?? null,
                $source['shared_post_url'] ?? null,
                $payload['origin_url'] ?? null
            )
        );
        $publisherProfileUrl = $this->normalizeUrl(
            $this->firstNonEmptyString(
                $source['publisher_profile_url'] ?? null,
                $source['publisher_username'] ?? null,
                $payload['publisher_profile_url'] ?? null,
                $payload['publisher_username'] ?? null
            )
        );
        $sourceContext = array(
            'post_id' => $postId,
            'group_post_url' => $groupPostUrl,
            'origin_url' => $originUrl,
            'post_url' => '' !== $groupPostUrl ? $groupPostUrl : $originUrl,
            'group_url' => $this->normalizeUrl((string) ($source['group_url'] ?? $payload['group_url'] ?? '')),
            'publisher_profile_url' => $publisherProfileUrl,
            'publisher_username' => $this->normalizeLooseText($publisherProfileUrl),
            'publisher_name' => trim((string) $this->firstNonEmptyString(
                $source['publisher_name'] ?? null,
                $payload['publisher_name'] ?? null
            )),
        );

        $rawText = (string) $this->firstNonEmptyString(
            $content['effective_text'] ?? null,
            $content['text'] ?? null,
            $payload['effective_text'] ?? null,
            $payload['text'] ?? null
        );
        $normalizedText = $this->normalizeAdText($rawText);
        $textTokenCount = preg_match_all('/\S+/u', $normalizedText, $matches);
        $textIsMeaningful = '' !== $normalizedText && (mb_strlen($normalizedText) >= 24 || $textTokenCount >= 5);

        $firstImageUrl = $this->normalizeUrl((string) $this->firstNonEmptyString(
            $content['effective_first_image_url'] ?? null,
            $content['first_image_url'] ?? null,
            $payload['effective_first_image_url'] ?? null,
            $payload['first_image_url'] ?? null
        ));
        $firstAttachmentToken = $this->normalizeLooseText((string) $this->firstNonEmptyString(
            $content['effective_media_identity'] ?? null,
            $content['first_attachment_token'] ?? null,
            $payload['effective_media_identity'] ?? null,
            $payload['first_attachment_token'] ?? null
        ));
        $firstMediaId = trim((string) $this->firstNonEmptyString(
            $content['first_media_id'] ?? null,
            $content['effective_first_media_id'] ?? null,
            $payload['first_media_id'] ?? null,
            $payload['effective_first_media_id'] ?? null
        ));
        $scraperOcrText = $this->normalizeScraperOcrText((string) $this->firstNonEmptyString(
            $content['scraper_ocr_text'] ?? null,
            $payload['scraper_ocr_text'] ?? null
        ));
        $ocrText = $this->choosePreferredOcrText(
            $scraperOcrText,
            trim((string) $this->firstNonEmptyString(
                $content['ocr_text'] ?? null,
                $payload['ocr_text'] ?? null
            ))
        );

        $sourceKeys = array();
        if ('' !== $sourceContext['post_id']) {
            $sourceKeys[] = sha1('source-post|' . $market . '|' . $channel . '|' . $sourceContext['post_id']);
        }
        if ('' !== $sourceContext['group_post_url']) {
            $sourceKeys[] = sha1('source-group-url|' . $market . '|' . $channel . '|' . $sourceContext['group_post_url']);
        }
        if ('' !== $sourceContext['origin_url']) {
            $sourceKeys[] = sha1('source-origin-url|' . $market . '|' . $channel . '|' . $sourceContext['origin_url']);
        }
        if ('' !== $firstMediaId) {
            $sourceKeys[] = sha1('source-media-id|' . $market . '|' . $channel . '|' . $firstMediaId);
        }

        $imageIdentity = '';
        if ('' !== $firstAttachmentToken) {
            $imageIdentity = 'token:' . $firstAttachmentToken;
        } elseif ('' !== $firstMediaId) {
            $imageIdentity = 'media:' . $firstMediaId;
        } elseif ('' !== $firstImageUrl) {
            $imageIdentity = 'url:' . $firstImageUrl;
        }

        $imageKey = '' !== $imageIdentity
            ? sha1('image|' . $market . '|' . $imageIdentity)
            : '';
        $textKey = $textIsMeaningful
            ? sha1('text|' . $market . '|' . $normalizedText)
            : '';
        $contentKey = '';

        if ('' !== $textKey && '' !== $imageKey) {
            $contentKey = sha1('content|' . $market . '|' . $normalizedText . '|' . $imageIdentity);
        } elseif ('' !== $textKey) {
            $contentKey = sha1('content-text-only|' . $market . '|' . $normalizedText);
        }

        $classify = isset($ai['classify']) && is_array($ai['classify'])
            ? $ai['classify']
            : array();
        $extract = isset($ai['extract']) && is_array($ai['extract'])
            ? $ai['extract']
            : array();

        return array(
            'market' => $market,
            'channel' => $channel,
            'has_image' => '' !== $imageIdentity,
            'source' => $sourceContext,
            'content' => array(
                'text' => $rawText,
                'normalized_text' => $normalizedText,
                'text_is_meaningful' => $textIsMeaningful,
                'first_image_url' => $firstImageUrl,
                'first_attachment_token' => $firstAttachmentToken,
                'first_media_id' => $firstMediaId,
                'scraper_ocr_text' => $scraperOcrText,
                'ocr_text' => $ocrText,
            ),
            'ai' => array(
                'classify' => $this->summarizeAiPayload($classify),
                'extract' => $this->summarizeAiPayload($extract),
            ),
            'publish' => $this->summarizePublishPayload($publish),
            'fingerprints' => array(
                'source_keys' => array_values(array_unique(array_filter($sourceKeys, 'strlen'))),
                'text_key' => $textKey,
                'image_key' => $imageKey,
                'content_key' => $contentKey,
            ),
        );
    }

    /**
     * @param array<string,mixed> $state
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function findBestMatch(array $state, array $context): array
    {
        $indices = isset($state['indices']) && is_array($state['indices'])
            ? $state['indices']
            : array();
        $records = isset($state['records']) && is_array($state['records'])
            ? $state['records']
            : array();

        foreach ((array) ($context['fingerprints']['source_keys'] ?? array()) as $sourceKey) {
            $recordId = isset($indices['source'][(string) $sourceKey]) ? (string) $indices['source'][(string) $sourceKey] : '';
            if ('' !== $recordId && isset($records[$recordId]) && is_array($records[$recordId])) {
                return array('type' => 'source', 'record' => $records[$recordId]);
            }
        }

        $contentKey = (string) ($context['fingerprints']['content_key'] ?? '');
        if ('' !== $contentKey) {
            $recordId = isset($indices['content'][$contentKey]) ? (string) $indices['content'][$contentKey] : '';
            if ('' !== $recordId && isset($records[$recordId]) && is_array($records[$recordId])) {
                return array('type' => 'content', 'record' => $records[$recordId]);
            }
        }

        $textKey = (string) ($context['fingerprints']['text_key'] ?? '');
        if ('' !== $textKey && '' === (string) ($context['fingerprints']['image_key'] ?? '')) {
            $recordId = isset($indices['text'][$textKey]) ? (string) $indices['text'][$textKey] : '';
            if ('' !== $recordId && isset($records[$recordId]) && is_array($records[$recordId])) {
                return array('type' => 'text', 'record' => $records[$recordId]);
            }
        }

        $imageKey = (string) ($context['fingerprints']['image_key'] ?? '');
        if ('' !== $imageKey) {
            $recordId = isset($indices['image'][$imageKey]) ? (string) $indices['image'][$imageKey] : '';
            if ('' !== $recordId && isset($records[$recordId]) && is_array($records[$recordId])) {
                return array('type' => 'image', 'record' => $records[$recordId]);
            }
        }

        return array();
    }

    /**
     * @return array<string,mixed>
     */
    private function loadState(): array
    {
        $directory = dirname($this->stateFile);
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        if (!is_file($this->stateFile)) {
            return $this->defaultState();
        }

        $raw = @file_get_contents($this->stateFile);
        if (!is_string($raw) || '' === trim($raw)) {
            return $this->defaultState();
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $this->normalizeState($decoded) : $this->defaultState();
    }

    /**
     * @param callable(array<string,mixed>):array<string,mixed> $callback
     * @return array<string,mixed>
     */
    private function withLockedState(callable $callback): array
    {
        $directory = dirname($this->stateFile);
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $handle = fopen($this->stateFile, 'c+');
        if (false === $handle) {
            return array(
                'response' => array(
                    'status' => 'error',
                    'message' => 'Unable to open dedup state file.',
                ),
            );
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return array(
                    'response' => array(
                        'status' => 'error',
                        'message' => 'Unable to lock dedup state file.',
                    ),
                );
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && '' !== trim($raw)
                ? json_decode($raw, true)
                : null;
            $state = is_array($state) ? $this->normalizeState($state) : $this->defaultState();

            $result = $callback($state);
            $nextState = isset($result['state']) && is_array($result['state'])
                ? $this->normalizeState($result['state'])
                : $state;

            rewind($handle);
            ftruncate($handle, 0);
            fwrite(
                $handle,
                (string) json_encode($nextState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
            fflush($handle);
            flock($handle, LOCK_UN);

            return $result;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function defaultState(): array
    {
        return array(
            'version' => $this->version,
            'updated_at' => null,
            'indices' => array(
                'source' => array(),
                'content' => array(),
                'image' => array(),
                'text' => array(),
            ),
            'records' => array(),
        );
    }

    /**
     * @param array<string,mixed> $state
     * @return array<string,mixed>
     */
    private function normalizeState(array $state): array
    {
        $defaults = $this->defaultState();

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $state) || !is_array($value) && null === $state[$key]) {
                $state[$key] = $value;
            }
        }

        if (!isset($state['indices']) || !is_array($state['indices'])) {
            $state['indices'] = $defaults['indices'];
        }
        foreach (array('source', 'content', 'image', 'text') as $bucket) {
            if (!isset($state['indices'][$bucket]) || !is_array($state['indices'][$bucket])) {
                $state['indices'][$bucket] = array();
            }
        }
        if (!isset($state['records']) || !is_array($state['records'])) {
            $state['records'] = array();
        }

        return $state;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function summarizeAiPayload(array $payload): array
    {
        return array(
            'status' => trim((string) ($payload['status'] ?? '')),
            'reason' => trim((string) ($payload['reason'] ?? '')),
            'category_key' => trim((string) ($payload['category_key'] ?? '')),
            'country_key' => trim((string) ($payload['country_key'] ?? '')),
            'city_key' => trim((string) ($payload['city_key'] ?? '')),
            'final_ad_text' => trim((string) ($payload['final_ad_text'] ?? '')),
            'ad_phone' => trim((string) ($payload['ad_phone'] ?? '')),
            'price' => trim((string) ($payload['price'] ?? '')),
        );
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function summarizePublishPayload(array $payload): array
    {
        $post = isset($payload['post']) && is_array($payload['post'])
            ? $payload['post']
            : $payload;

        return array(
            'ingest_status' => trim((string) ($payload['ingest_status'] ?? '')),
            'post_id' => (int) ($post['id'] ?? 0),
            'post_status' => trim((string) ($post['status'] ?? '')),
            'view_link' => trim((string) ($post['view_link'] ?? '')),
        );
    }

    /**
     * @param array<string,mixed> $record
     * @return array<string,mixed>
     */
    private function summarizeRecord(array $record): array
    {
        if (empty($record)) {
            return array();
        }

        $source = isset($record['source']) && is_array($record['source']) ? $record['source'] : array();
        $publish = isset($record['publish']) && is_array($record['publish']) ? $record['publish'] : array();

        return array(
            'record_id' => (string) ($record['id'] ?? ''),
            'updated_at' => (string) ($record['updated_at'] ?? ''),
            'source_post_id' => (string) ($source['post_id'] ?? ''),
            'source_post_url' => (string) ($source['post_url'] ?? ''),
            'origin_url' => (string) ($source['origin_url'] ?? ''),
            'published_post_id' => (int) ($publish['post_id'] ?? 0),
            'published_post_status' => (string) ($publish['post_status'] ?? ''),
        );
    }

    /**
     * @param array<string,mixed> $record
     */
    private function extractCachedOcrText(array $record): string
    {
        $cache = isset($record['cache']) && is_array($record['cache']) ? $record['cache'] : array();

        return $this->choosePreferredOcrText(
            trim((string) ($cache['scraper_ocr_text'] ?? '')),
            trim((string) ($cache['ocr_text'] ?? ''))
        );
    }

    /**
     * @param array<string,mixed> $context
     */
    private function generateRecordId(array $context): string
    {
        $seed = implode(
            '|',
            array(
                $context['market'],
                $context['channel'],
                (string) ($context['fingerprints']['content_key'] ?? ''),
                (string) ($context['fingerprints']['image_key'] ?? ''),
                (string) ($context['fingerprints']['text_key'] ?? ''),
                (string) microtime(true),
            )
        );

        return gmdate('YmdHis') . '-' . substr(sha1($seed), 0, 16);
    }

    /**
     * @param mixed ...$values
     */
    private function firstNonEmptyString(...$values): string
    {
        foreach ($values as $value) {
            if (!is_scalar($value) && null !== $value) {
                continue;
            }

            $candidate = trim((string) $value);
            if ('' !== $candidate) {
                return $candidate;
            }
        }

        return '';
    }

    private function choosePreferredOcrText(string $scraperOcrText, string $fallbackOcrText): string
    {
        $normalizedScraperOcr = $this->normalizeScraperOcrText($scraperOcrText);
        if ('' !== $normalizedScraperOcr) {
            return $normalizedScraperOcr;
        }

        return trim($fallbackOcrText);
    }

    private function normalizeScraperOcrText(string $value): string
    {
        $value = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ('' === $value) {
            return '';
        }

        $lowerValue = strtolower($value);
        if ('no photo description available.' === $lowerValue) {
            return '';
        }

        if (preg_match('/^may be an? (image|graphic) of\b/i', $value) && false === stripos($value, 'that says')) {
            return '';
        }

        if (preg_match('/that says\s+[\'"“”‎]*(.+?)[\'"“”‎]*$/uis', $value, $matches)) {
            $value = (string) ($matches[1] ?? '');
        }

        $value = trim($value, " \t\n\r\0\x0B'\"“”‎");
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        if ('' === $value) {
            return '';
        }

        if (mb_strlen($value) < 18 && !preg_match('/\d{4,}/', $value)) {
            return '';
        }

        return $value;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ('' === $url) {
            return '';
        }

        $parts = parse_url($url);
        if (false === $parts || !isset($parts['host'])) {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : 'https';
        $host = strtolower((string) $parts['host']);
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';
        $normalized = $scheme . '://' . $host . $path;

        $isFacebookPageUrl = false !== strpos($host, 'facebook.com') || false !== strpos($host, 'fb.watch');
        if (
            $isFacebookPageUrl
            && isset($parts['query'])
            && is_string($parts['query'])
            && '' !== $parts['query']
        ) {
            parse_str($parts['query'], $query);
            $keptQuery = array();
            foreach (array('fbid', 'set', 'story_fbid', 'id', 'v', 'comment_id') as $key) {
                if (isset($query[$key]) && '' !== trim((string) $query[$key])) {
                    $keptQuery[$key] = trim((string) $query[$key]);
                }
            }

            if (!empty($keptQuery)) {
                $normalized .= '?' . http_build_query($keptQuery, '', '&', PHP_QUERY_RFC3986);
            }
        }

        return $normalized;
    }

    private function normalizeLooseText(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        if ('' === $value) {
            return '';
        }

        $value = strtr(
            $value,
            array(
                'ي' => 'ی',
                'ك' => 'ک',
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
            )
        );

        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return trim($value);
    }

    private function normalizeAdText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = $this->normalizeLooseText($value);
        if ('' === $value) {
            return '';
        }

        $value = preg_replace('~https?://\S+~u', ' ', $value) ?: $value;
        $value = preg_replace('/[@#][\p{L}\p{N}_]+/u', ' ', $value) ?: $value;
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?: $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return trim($value);
    }
}
