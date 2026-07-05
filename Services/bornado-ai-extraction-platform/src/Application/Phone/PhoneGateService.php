<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Application\Phone;

final class PhoneGateService
{
    /** @var PhoneNumberService */
    private $phoneNumberService;

    public function __construct(?PhoneNumberService $phoneNumberService = null)
    {
        $this->phoneNumberService = $phoneNumberService ?: new PhoneNumberService();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function evaluate(array $payload): array
    {
        $content = isset($payload['content']) && is_array($payload['content'])
            ? $payload['content']
            : array();

        $texts = array(
            trim((string) ($content['effective_text'] ?? $content['text'] ?? $payload['text'] ?? '')),
            trim((string) ($content['scraper_ocr_text'] ?? $payload['scraper_ocr_text'] ?? '')),
            trim((string) ($content['ocr_text'] ?? $payload['ocr_text'] ?? '')),
        );

        $detection = $this->phoneNumberService->detectPhones($texts);
        $hasImage = '' !== trim((string) ($content['first_image_url'] ?? $payload['first_image_url'] ?? ''))
            || '' !== trim((string) ($content['first_media_id'] ?? $payload['first_media_id'] ?? ''))
            || '' !== trim((string) ($content['first_attachment_token'] ?? $payload['first_attachment_token'] ?? ''));
        $hasScraperOcr = '' !== trim((string) ($content['scraper_ocr_text'] ?? $payload['scraper_ocr_text'] ?? ''));
        $hasGoogleOcr = '' !== trim((string) ($content['ocr_text'] ?? $payload['ocr_text'] ?? ''));

        $requiresGoogleOcr = !$detection['has_phone'] && $hasImage && !$hasGoogleOcr;
        $shouldContinue = (bool) $detection['has_phone'];
        $decision = $shouldContinue ? 'has_phone' : ($requiresGoogleOcr ? 'needs_ocr' : 'no_phone');

        return array(
            'status' => 'ok',
            'decision' => $decision,
            'has_phone' => $shouldContinue,
            'has_phone_text' => $shouldContinue ? 'true' : 'false',
            'should_continue' => $shouldContinue,
            'should_continue_text' => $shouldContinue ? 'true' : 'false',
            'requires_google_ocr' => $requiresGoogleOcr,
            'requires_google_ocr_text' => $requiresGoogleOcr ? 'true' : 'false',
            'primary_phone' => $detection['primary_phone'],
            'phones' => $detection['phones'],
            'matches_by_source' => $detection['matches_by_source'],
            'reason' => $shouldContinue
                ? 'Detected at least one phone number.'
                : ($requiresGoogleOcr
                    ? 'No phone found yet. Run OCR on image before rejecting.'
                    : 'No phone number detected.'),
            'sources' => array(
                'text' => '' !== $texts[0],
                'scraper_ocr' => $hasScraperOcr,
                'google_ocr' => $hasGoogleOcr,
                'has_image' => $hasImage,
            ),
        );
    }
}
