<?php

/**
 * Compare two lang files for key parity (flattened dot keys).
 * Usage: php tools/lang-parity.php
 */

$pairs = [
    ['lang/en/pages.php', 'lang/bn/pages.php'],
];

$flatten = function (array $a, string $prefix = '') use (&$flatten): array {
    $out = [];
    foreach ($a as $k => $v) {
        $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
        if (is_array($v)) {
            $out += $flatten($v, $key);
        } else {
            $out[$key] = $v;
        }
    }

    return $out;
};

$failed = 0;

foreach ($pairs as [$enPath, $bnPath]) {
    $en = $flatten(require $enPath);
    $bn = $flatten(require $bnPath);

    $missingInBn = array_diff_key($en, $bn);
    $missingInEn = array_diff_key($bn, $en);

    echo "=== {$enPath} vs {$bnPath} ===" . PHP_EOL;
    echo 'en keys: ' . count($en) . PHP_EOL;
    echo 'bn keys: ' . count($bn) . PHP_EOL;

    echo 'missing in bn: ' . count($missingInBn) . PHP_EOL;
    foreach (array_keys($missingInBn) as $k) {
        echo "   - {$k}" . PHP_EOL;
    }

    echo 'missing in en: ' . count($missingInEn) . PHP_EOL;
    foreach (array_keys($missingInEn) as $k) {
        echo "   + {$k}" . PHP_EOL;
    }

    // Detect untranslated values: bn value identical to en value.
    $identical = [];
    foreach ($en as $k => $v) {
        if (isset($bn[$k]) && $bn[$k] === $v && trim((string) $v) !== '') {
            $identical[] = $k;
        }
    }
    echo 'bn identical to en: ' . count($identical) . PHP_EOL;
    foreach ($identical as $k) {
        echo "   = {$k}" . PHP_EOL;
    }

    if ($missingInBn || $missingInEn) {
        $failed++;
    }
}

echo PHP_EOL . ($failed === 0 ? 'PARITY OK' : 'PARITY FAILED') . PHP_EOL;
exit($failed === 0 ? 0 : 1);
