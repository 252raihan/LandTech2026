<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * i18n coverage: the English and Bengali page dictionaries must stay in parity,
 * Bengali must be genuinely translated (not English passthrough), and no Blade
 * template or JS module may hardcode user-facing English.
 *
 * These tests FAIL when a new string is added to one locale only, or when a
 * Bengali value is left as the English original.
 */
class MissingTranslationsTest extends TestCase
{
    /**
     * Flatten a nested lang array into dot-notation keys.
     *
     * @return array<string, string>
     */
    protected function flatten(array $array, string $prefix = ''): array
    {
        $out = [];

        foreach ($array as $key => $value) {
            $full = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $out += $this->flatten($value, $full);
            } else {
                $out[$full] = (string) $value;
            }
        }

        return $out;
    }

    protected function en(): array
    {
        return $this->flatten(require base_path('lang/en/pages.php'));
    }

    protected function bn(): array
    {
        return $this->flatten(require base_path('lang/bn/pages.php'));
    }

    public function test_english_and_bengali_page_dictionaries_have_identical_keys(): void
    {
        $en = $this->en();
        $bn = $this->bn();

        $missingInBn = array_diff_key($en, $bn);
        $missingInEn = array_diff_key($bn, $en);

        $this->assertSame(
            [],
            array_keys($missingInBn),
            'Keys present in lang/en/pages.php but missing from lang/bn/pages.php'
        );

        $this->assertSame(
            [],
            array_keys($missingInEn),
            'Keys present in lang/bn/pages.php but missing from lang/en/pages.php'
        );
    }

    public function test_no_bengali_value_is_left_as_untranslated_english(): void
    {
        $en = $this->en();
        $bn = $this->bn();

        // Values that are intentionally identical across locales:
        //  - symbols / punctuation-only strings (e.g. the em-dash placeholder)
        //  - identifiers and technical terms that must not be translated
        $allowedIdentical = [
            'common.none',            // em-dash placeholder
            'saga.default_buyer_nid', // SIM-123456 identifier
            'saga.steps_badge',       // proper nouns (Redlock, Porichoy, CIB, PostGIS)
            'crypto-verify.col_sha',  // "SHA-256" — a technical term kept in Latin script
        ];

        $untranslated = [];

        foreach ($en as $key => $englishValue) {
            if (! isset($bn[$key])) {
                continue;
            }

            if ($bn[$key] !== $englishValue) {
                continue;
            }

            // Skip strings with no letters at all (symbols, dashes, numbers).
            if (! preg_match('/[A-Za-z]/', $englishValue)) {
                continue;
            }

            if (in_array($key, $allowedIdentical, true)) {
                continue;
            }

            $untranslated[] = $key;
        }

        $this->assertSame(
            [],
            $untranslated,
            'These Bengali values are identical to English and appear untranslated: '
                . implode(', ', $untranslated)
        );
    }

    public function test_bengali_values_actually_contain_bengali_characters(): void
    {
        $bn = $this->bn();

        // Keys whose value legitimately has no Bengali script (identifiers, acronyms).
        $exempt = [
            'saga.default_buyer_nid',
            'common.none',
            'saga.steps_badge',
            'crypto-verify.col_sha',  // "SHA-256" technical term
        ];

        $notBengali = [];

        foreach ($bn as $key => $value) {
            if (in_array($key, $exempt, true)) {
                continue;
            }

            // Only consider values that contain letters in the first place.
            if (! preg_match('/[A-Za-z]/', $value)) {
                continue;
            }

            if (! preg_match('/[\x{0980}-\x{09FF}]/u', $value)) {
                $notBengali[] = $key;
            }
        }

        $this->assertSame(
            [],
            $notBengali,
            'These Bengali entries contain no Bengali script: ' . implode(', ', $notBengali)
        );
    }

    public function test_every_route_slug_has_a_translation_group(): void
    {
        $slugs = [
            'dashboard',
            'saga',
            'sync-webhook',
            'crypto-verify',
            'plot-lock',
            'ekyc-biometric',
            'bank-lien',
            'ai-ocr-conflict',
            'faraz-calculator',
            'gis-map',
            'risk-scoring',
            'audit-ledger',
        ];

        $en = require base_path('lang/en/pages.php');

        foreach ($slugs as $slug) {
            $this->assertArrayHasKey($slug, $en, "Missing translation group for page: {$slug}");
            $this->assertArrayHasKey(
                'title',
                $en[$slug],
                "Page group '{$slug}' must define a 'title' key"
            );
        }
    }

    /**
     * Phase 4 — the officer/admin dashboard dictionaries must stay in parity and
     * must be genuinely translated, exactly like the pages and citizen files.
     */
    public function test_officer_dictionaries_have_identical_keys_and_are_translated(): void
    {
        $en = $this->flatten(require base_path('lang/en/officer.php'));
        $bn = $this->flatten(require base_path('lang/bn/officer.php'));

        $this->assertSame(
            [],
            array_keys(array_diff_key($en, $bn)),
            'Keys present in lang/en/officer.php but missing from lang/bn/officer.php'
        );
        $this->assertSame(
            [],
            array_keys(array_diff_key($bn, $en)),
            'Keys present in lang/bn/officer.php but missing from lang/en/officer.php'
        );

        $untranslated = [];

        foreach ($en as $key => $english) {
            if (! preg_match('/[A-Za-z]/', $english)) {
                continue;
            }

            if (($bn[$key] ?? null) === $english) {
                $untranslated[] = $key;
            }
        }

        $this->assertSame([], $untranslated, 'Untranslated officer keys: '.implode(', ', $untranslated));
    }

    /**
     * Phase 4 — the citizen dictionaries must also stay in parity, including the
     * Help page keys added with the Low-risk fixes.
     */
    public function test_citizen_dictionaries_have_identical_keys(): void
    {
        $en = $this->flatten(require base_path('lang/en/citizen.php'));
        $bn = $this->flatten(require base_path('lang/bn/citizen.php'));

        $this->assertSame([], array_keys(array_diff_key($en, $bn)), 'missing from bn/citizen.php');
        $this->assertSame([], array_keys(array_diff_key($bn, $en)), 'missing from en/citizen.php');

        // The Help page keys must exist in both locales.
        foreach (['help.coming_soon', 'help.coming_soon_hint'] as $key) {
            $this->assertArrayHasKey($key, $en);
            $this->assertArrayHasKey($key, $bn);
        }
    }

    public function test_js_dictionary_exposes_every_js_key(): void
    {
        $pages = require base_path('lang/en/pages.php');
        $js = require base_path('lang/en/js.php');

        foreach (array_keys($pages['js']) as $key) {
            $this->assertArrayHasKey(
                $key,
                $js,
                "lang/en/js.php must expose the pages.js key '{$key}' to the browser dictionary"
            );
        }
    }

    public function test_blade_templates_contain_no_obvious_hardcoded_english_ui_text(): void
    {
        $views = glob(resource_path('views/pages/*.blade.php'));
        $views[] = resource_path('views/layouts/app.blade.php');

        // Strings that are allowed to remain literal English in markup.
        $allowed = [
            'SIM-123456',      // identifier
            'SIM-XXXXXX',      // identifier format hint
            'DEED_EXECUTED_EVENT',
            'ST_Overlaps',
            'SHA-256',
            'prevHash',
            'LOCK:LAND',
            'officer-demo',
            'MANUAL_NOTE',
        ];

        $offenders = [];

        foreach ($views as $view) {
            $contents = file_get_contents($view);

            // Find text sitting between a tag boundary and an opening brace/at-tag,
            // i.e. literal copy that was never wrapped in __().
            if (preg_match_all('/>\s*([A-Z][A-Za-z][A-Za-z ,\.\-\'&\(\)\/]{6,})\s*</', $contents, $m)) {
                foreach ($m[1] as $candidate) {
                    $candidate = trim($candidate);

                    $isAllowed = false;
                    foreach ($allowed as $a) {
                        if (str_contains($candidate, $a)) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    if (! $isAllowed) {
                        $offenders[] = basename($view) . ': ' . $candidate;
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Hardcoded English UI text found in Blade templates:\n" . implode("\n", $offenders)
        );
    }
}
