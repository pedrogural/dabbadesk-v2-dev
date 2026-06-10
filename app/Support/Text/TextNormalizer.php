<?php

namespace App\Support\Text;

use Illuminate\Support\Str;

class TextNormalizer
{
    /**
     * Normalise user/customer/product text before it is persisted.
     *
     * This deliberately focuses on common UTF-8 mojibake created when UTF-8 text
     * has been decoded as Windows-1252/Latin-1, e.g. â€“ instead of – and Ã— instead of ×.
     */
    public static function clean(?string $value, ?int $limit = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = (string) $value;

        if ($text === '') {
            return '';
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = self::fixMojibake($text);

        // Remove invisible control characters while preserving tabs/new lines.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

        // Tidy common non-breaking spaces and over-zealous spacing.
        $text = str_replace(["\xc2\xa0", 'Â '], ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
        $text = trim($text);

        if ($limit !== null && $limit > 0) {
            $text = Str::limit($text, $limit, '');
        }

        return $text;
    }

    public static function cleanOrNull(?string $value, ?int $limit = null): ?string
    {
        $cleaned = self::clean($value, $limit);

        return $cleaned === '' ? null : $cleaned;
    }

    public static function suspicious(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return preg_match('/(â€|â€“|â€”|â€¦|â„¢|Ã.|Â£|Â®|Â©|Â |�)/u', $value) === 1;
    }

    public static function fixMojibake(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $map = [
            'â€“' => '–',
            'â€”' => '—',
            'â€˜' => '‘',
            'â€™' => '’',
            'â€œ' => '“',
            'â€�' => '”',
            'â€' => '”',
            'â€¦' => '…',
            'â€¢' => '•',
            'â„¢' => '™',
            'â‚¬' => '€',
            'â‚¬â„¢' => '’',
            'â€' => '†',
            'Ã—' => '×',
            'Ã·' => '÷',
            'Â£' => '£',
            'Â€' => '€',
            'Â©' => '©',
            'Â®' => '®',
            'Â°' => '°',
            'Â±' => '±',
            'Â·' => '·',
            'Â ' => ' ',
            'Â' => '',
        ];

        $text = strtr($text, $map);

        // A second pass catches longer patterns after partial replacements.
        $text = strtr($text, [
            'â€˜' => '‘',
            'â€™' => '’',
            'â€œ' => '“',
            'â€�' => '”',
            'â€' => '”',
        ]);

        return $text;
    }
}
