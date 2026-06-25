<?php
declare(strict_types=1);

namespace Bornado\AiExtractionPlatform\Domain;

final class CanonicalKey
{
    public static function fromString(string $value): string
    {
        $value = trim(strtolower($value));
        if ('' === $value) {
            return '';
        }

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($transliterated) && '' !== $transliterated) {
                $value = $transliterated;
            }
        }

        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = is_string($value) ? trim($value, '-') : '';

        return preg_replace('/-+/', '-', $value ?? '') ?: '';
    }
}
