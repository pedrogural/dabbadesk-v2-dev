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

        return preg_match('/(â€|â€“|â€”|â€¦|â„¢|Ã.|Ãƒ|Ã¢|Â£|Â®|Â©|Â |�)/u', $value) === 1;
    }

    /**
     * Repair historical text conservatively.
     *
     * The save-time cleaner is intentionally lightweight. Historical data can be
     * double/triple encoded, so this method iterates a few times and then only
     * accepts the result if no suspicious mojibake markers remain. This prevents
     * PREVIEW/APPLY from replacing one flavour of gibberish with another.
     */
    public static function repairHistorical(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $original = (string) $value;
        $text = $original;

        for ($pass = 0; $pass < 6; $pass++) {
            $next = self::clean($text);

            if ($next === null || $next === $text) {
                break;
            }

            $text = $next;
        }

        if ($text === $original || $text === '') {
            return null;
        }

        if (self::suspicious($text)) {
            return null;
        }

        return $text;
    }

    public static function fixMojibake(string $text): string
    {
        if ($text === '') {
            return '';
        }

        // Longest and most corrupted patterns first.
        $map = [
            // Common double/triple encoded punctuation seen in older imported data.
            'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢' => '’',
            'Ãƒ¢Ã¢†š¬Ã¢†ž¢' => '’',
            'ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ' => '“',
            'ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â' => '”',
            'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' => '—',
            'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“' => '–',
            'ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦' => '…',

            // Common double-encoded accented characters.
            'ÃƒÆ’Ã‚Â©' => 'é',
            'ÃƒÆ’Ã‚©' => 'é',
            'ÃƒÂ©' => 'é',
            'Ã©' => 'é',
            'ÃƒÆ’Ã‚Â¨' => 'è',
            'ÃƒÂ¨' => 'è',
            'Ã¨' => 'è',
            'ÃƒÆ’Ã‚Â¡' => 'á',
            'ÃƒÂ¡' => 'á',
            'Ã¡' => 'á',
            'ÃƒÆ’Ã‚Â­' => 'í',
            'ÃƒÂ­' => 'í',
            'Ã­' => 'í',
            'ÃƒÆ’Ã‚Â³' => 'ó',
            'ÃƒÂ³' => 'ó',
            'Ã³' => 'ó',
            'ÃƒÆ’Ã‚Âº' => 'ú',
            'ÃƒÂº' => 'ú',
            'Ãº' => 'ú',
            'ÃƒÆ’Ã‚Â±' => 'ñ',
            'ÃƒÂ±' => 'ñ',
            'Ã±' => 'ñ',
            'ÃƒÆ’Ã‚Â§' => 'ç',
            'ÃƒÂ§' => 'ç',
            'Ã§' => 'ç',

            // Standard single-pass mojibake.
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

        // A second pass catches shorter patterns revealed after long replacements.
        $text = strtr($text, [
            'â€˜' => '‘',
            'â€™' => '’',
            'â€œ' => '“',
            'â€�' => '”',
            'â€' => '”',
            'Ã—' => '×',
        ]);

        return $text;
    }
}
