<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Application\Phone;

final class PhoneNumberService
{
    /**
     * @param array<int,string> $texts
     * @return array<string,mixed>
     */
    public function detectPhones(array $texts): array
    {
        $phones = array();
        $matchesBySource = array();

        foreach ($texts as $index => $text) {
            $detectedPhones = $this->extractPhones((string) $text);
            if (empty($detectedPhones)) {
                continue;
            }

            $sourceKey = 'source_' . (string) $index;
            $matchesBySource[$sourceKey] = $detectedPhones;
            foreach ($detectedPhones as $phone) {
                $phones['phone:' . $phone] = $phone;
            }
        }

        $phoneList = array_values($phones);

        return array(
            'has_phone' => !empty($phoneList),
            'phones' => array_values($phoneList),
            'primary_phone' => isset($phoneList[0]) ? (string) $phoneList[0] : null,
            'matches_by_source' => $matchesBySource,
        );
    }

    /**
     * @return array<int,string>
     */
    public function extractPhones(string $text): array
    {
        $normalizedText = $this->normalizeUnicodeDigits($text);
        if ('' === trim($normalizedText)) {
            return array();
        }

        $pattern = '/(?:\+|00)?\d[\d\-\.\(\)\s\/]{5,}\d/u';
        preg_match_all($pattern, $normalizedText, $matches);
        $results = array();

        foreach ((array) ($matches[0] ?? array()) as $rawCandidate) {
            $phones = $this->normalizeCandidate((string) $rawCandidate, $normalizedText);
            foreach ($phones as $phone) {
                $results['phone:' . $phone] = $phone;
            }
        }

        return array_values($results);
    }

    public function containsPhone(string $text): bool
    {
        return !empty($this->extractPhones($text));
    }

    private function normalizeUnicodeDigits(string $text): string
    {
        return strtr(
            $text,
            array(
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
                '０' => '0',
                '１' => '1',
                '２' => '2',
                '３' => '3',
                '４' => '4',
                '５' => '5',
                '６' => '6',
                '７' => '7',
                '８' => '8',
                '９' => '9',
                '＋' => '+',
            )
        );
    }

    /**
     * @return array<int,string>
     */
    private function normalizeCandidate(string $candidate, string $fullText): array
    {
        $candidate = trim($candidate);
        if ('' === $candidate) {
            return array();
        }

        $hasInternationalPrefix = 0 === strpos($candidate, '+') || 0 === strpos($candidate, '00');
        $digits = preg_replace('/\D+/', '', $candidate) ?: '';
        if ('' === $digits) {
            return array();
        }

        $digitLength = strlen($digits);
        if ($digitLength < 7 || $digitLength > 16) {
            return array();
        }

        if ($this->looksLikeLikelyDateOrId($candidate, $digits, $fullText)) {
            return array();
        }

        $contextHasPhoneLabel = $this->hasPhoneContext($candidate, $fullText);

        if ($digitLength < 8 && !$contextHasPhoneLabel) {
            return array();
        }

        if ($digitLength < 10 && !$hasInternationalPrefix && !$contextHasPhoneLabel && '0' !== $digits[0]) {
            return array();
        }

        return array($digits);
    }

    private function hasPhoneContext(string $candidate, string $fullText): bool
    {
        $position = mb_stripos($fullText, $candidate, 0, 'UTF-8');
        if (false === $position) {
            return $this->containsPhoneKeyword($fullText);
        }

        $start = max(0, (int) $position - 32);
        $length = mb_strlen($candidate, 'UTF-8') + 64;
        $context = mb_substr($fullText, $start, $length, 'UTF-8');

        return $this->containsPhoneKeyword($context);
    }

    private function containsPhoneKeyword(string $text): bool
    {
        return 1 === preg_match(
            '/(?:phone|tel|call|text|mobile|cell|contact|whatsapp|واتساپ|واتساپ|واتس|تماس|تلفن|موبایل|شماره|زنگ|call us|call me)/iu',
            $text
        );
    }

    private function looksLikeLikelyDateOrId(string $candidate, string $digits, string $fullText): bool
    {
        if (1 === preg_match('/^\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}$/', trim($candidate))) {
            return true;
        }

        if (preg_match('/(?:20\d{2}|19\d{2})/', $digits) && strlen($digits) <= 8 && !$this->containsPhoneKeyword($fullText)) {
            return true;
        }

        return false;
    }
}
