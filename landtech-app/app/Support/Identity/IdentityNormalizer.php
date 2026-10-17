<?php

namespace App\Support\Identity;

/**
 * Canonical normalizer for identity numbers.
 *
 * This is the SINGLE place where Bengali and ASCII digits are reconciled. Both
 * the validation rule and the HMAC lookup call through here, so the two can
 * never drift apart — a mismatch would silently break search for every
 * Bengali-digit entry.
 *
 * Normalization steps:
 *   1. Bengali digits ০-৯ (U+09E6..U+09EF) -> ASCII 0-9
 *   2. Strip whitespace, dashes, dots, slashes, underscores and NBSP
 *   3. Uppercase (identifiers may carry a trailing letter or the SIM- prefix)
 *
 * The ORIGINAL formatting is preserved inside the encrypted payload; only the
 * normalized form is ever hashed.
 */
class IdentityNormalizer
{
    /** Bengali digit glyphs, index 0..9. */
    private const BENGALI_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    /**
     * Normalize a raw identity number into its canonical lookup form.
     */
    public static function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // 1. Bengali -> ASCII digits.
        $value = str_replace(self::BENGALI_DIGITS, range('0', '9'), $value);

        // 2. Remove separators and whitespace (including NBSP U+00A0).
        $value = str_replace(
            ['-', '_', '.', '/', '\\', ' ', "\t", "\n", "\r", "\u{00A0}"],
            '',
            $value
        );

        // 3. Case-fold for identifiers that carry letters.
        return strtoupper(trim($value));
    }

    /**
     * Last N characters of the normalized value, for masked display.
     * Returns null when there is nothing meaningful to show.
     */
    public static function trailing(?string $value, int $length = 4): ?string
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return null;
        }

        return substr($normalized, -$length);
    }

    /**
     * True when the value is a synthetic demo identifier.
     * Phase 3 uses synthetic data only, per the LandTech 2026 rule book §8.
     */
    public static function isSynthetic(?string $value): bool
    {
        return str_starts_with(self::normalize($value), 'SIM');
    }
}
