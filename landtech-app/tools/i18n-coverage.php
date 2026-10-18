<?php

/*
|--------------------------------------------------------------------------
| LandTech 2026 — i18n coverage auditor
|--------------------------------------------------------------------------
|
| Extracts static translation keys from Blade views (__('...') and
| @lang('...')) and resolves each key against the real lang/ dictionaries
| for BOTH locales, exactly the way Laravel resolves them.
|
| Usage: php tools/i18n-coverage.php [--json] [--dir=path]
|
*/

$base = dirname(__DIR__);

$jsonOut = in_array('--json', $argv, true);
$onlyDir = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--dir=')) {
        $onlyDir = substr($a, 6);
    }
}

/* ------------------------------------------------------------------ */
/* 1. Collect all view files                                           */
/* ------------------------------------------------------------------ */

$viewRoots = [
    $base . '/resources/views',
];

$files = [];
foreach ($viewRoots as $root) {
    if (! is_dir($root)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
            $path = $f->getPathname();
            if ($onlyDir && ! str_contains($path, $onlyDir)) {
                continue;
            }
            $files[] = $path;
        }
    }
}
sort($files);

/* ------------------------------------------------------------------ */
/* 2. Extract static keys                                              */
/* ------------------------------------------------------------------ */

// Matches __('key'), __("key"), @lang('key'), trans('key')
// Captures only keys without interpolation of the KEY itself.
// A key containing "'.something.'" or "'.$var.'" is dynamic -> skip.
$keyRegex = '/(?:__|@lang|trans)\(\s*[\'"]([A-Za-z0-9_\-\.\:]+)[\'"]/';

$staticKeys = [];   // key => [files...]
$dynamicHits = [];  // raw snippets that looked dynamic

foreach ($files as $file) {
    $contents = file_get_contents($file);
    $rel = str_replace($base . '/', '', str_replace('\\', '/', $file));

    // Find dynamic key concatenations like __('citizen.documents.review_status_'.$x)
    if (preg_match_all('/__\(\s*[\'"]([A-Za-z0-9_\-\.\:]+)[\'"]\s*\./', $contents, $dm)) {
        foreach ($dm[1] as $d) {
            $dynamicHits[$d][] = $rel;
        }
    }

    if (preg_match_all($keyRegex, $contents, $m)) {
        foreach ($m[1] as $key) {
            // Skip keys that are clearly PHP/JS fragments, not translation keys
            if (str_contains($key, '..')) {
                continue;
            }
            $staticKeys[$key][] = $rel;
        }
    }
}

ksort($staticKeys);

/* ------------------------------------------------------------------ */
/* 3. Build flattened dictionaries for each locale                     */
/* ------------------------------------------------------------------ */

function flatten(array $array, string $prefix = ''): array
{
    $out = [];
    foreach ($array as $key => $value) {
        $full = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) {
            $out += flatten($value, $full);
        } else {
            $out[$full] = (string) $value;
        }
    }
    return $out;
}

function loadLocale(string $base, string $locale): array
{
    $dir = $base . '/lang/' . $locale;
    $dict = [];
    if (! is_dir($dir)) {
        return $dict;
    }
    foreach (glob($dir . '/*.php') as $file) {
        $namespace = basename($file, '.php');
        $data = require $file;
        if (! is_array($data)) {
            continue;
        }
        foreach (flatten($data) as $k => $v) {
            $dict[$namespace . '.' . $k] = $v;
        }
    }
    // JSON translation files (lang/<locale>.json) also count
    $jsonFile = $base . '/lang/' . $locale . '.json';
    if (is_file($jsonFile)) {
        $data = json_decode((string) file_get_contents($jsonFile), true);
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $dict[$k] = (string) $v;
            }
        }
    }
    return $dict;
}

$en = loadLocale($base, 'en');
$bn = loadLocale($base, 'bn');

/* ------------------------------------------------------------------ */
/* 4. Resolve every static key                                         */
/* ------------------------------------------------------------------ */

$missingEn = [];
$missingBn = [];
$missingBoth = [];
$presentBoth = [];

foreach (array_keys($staticKeys) as $key) {
    $hasEn = array_key_exists($key, $en);
    $hasBn = array_key_exists($key, $bn);

    if ($hasEn && $hasBn) {
        $presentBoth[] = $key;
    } else {
        if (! $hasEn) {
            $missingEn[] = $key;
        }
        if (! $hasBn) {
            $missingBn[] = $key;
        }
        if (! $hasEn && ! $hasBn) {
            $missingBoth[] = $key;
        }
    }
}

/* ------------------------------------------------------------------ */
/* 5. Group missing keys by namespace                                  */
/* ------------------------------------------------------------------ */

function groupByNamespace(array $keys): array
{
    $groups = [];
    foreach ($keys as $k) {
        $parts = explode('.', $k);
        $ns = count($parts) > 1 ? $parts[0] . '.' . $parts[1] : $parts[0];
        $groups[$ns][] = $k;
    }
    ksort($groups);
    return $groups;
}

$groupsBoth = groupByNamespace($missingBoth);

/* ------------------------------------------------------------------ */
/* 6. Output                                                           */
/* ------------------------------------------------------------------ */

if ($jsonOut) {
    echo json_encode([
        'total_static_keys' => count($staticKeys),
        'present_both' => count($presentBoth),
        'missing_en' => count($missingEn),
        'missing_bn' => count($missingBn),
        'missing_both' => count($missingBoth),
        'missing_both_keys' => $missingBoth,
        'missing_bn_only' => array_values(array_diff($missingBn, $missingBoth)),
        'missing_en_only' => array_values(array_diff($missingEn, $missingBoth)),
        'dynamic_prefixes' => array_keys($dynamicHits),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
    exit(0);
}

echo "=== LandTech i18n coverage audit ===\n";
echo "Views scanned        : " . count($files) . "\n";
echo "Static keys found    : " . count($staticKeys) . "\n";
echo "Resolve in BOTH      : " . count($presentBoth) . "\n";
echo "Missing in EN        : " . count($missingEn) . "\n";
echo "Missing in BN        : " . count($missingBn) . "\n";
echo "Missing in BOTH      : " . count($missingBoth) . "\n";
echo "\n";

if ($missingBoth) {
    echo "--- Missing in BOTH locales, grouped by namespace ---\n";
    foreach ($groupsBoth as $ns => $keys) {
        echo "\n[{$ns}] (" . count($keys) . ")\n";
        foreach ($keys as $k) {
            echo "  - {$k}\n";
        }
    }
    echo "\n";
}

$bnOnly = array_values(array_diff($missingBn, $missingBoth));
$enOnly = array_values(array_diff($missingEn, $missingBoth));

if ($bnOnly) {
    echo "--- Missing in BN only ---\n";
    foreach ($bnOnly as $k) {
        echo "  - {$k}\n";
    }
    echo "\n";
}

if ($enOnly) {
    echo "--- Missing in EN only ---\n";
    foreach ($enOnly as $k) {
        echo "  - {$k}\n";
    }
    echo "\n";
}

if ($dynamicHits) {
    echo "--- Dynamic key prefixes (concatenated at call site) ---\n";
    foreach ($dynamicHits as $prefix => $hits) {
        echo "  - {$prefix}  (" . implode(', ', array_unique($hits)) . ")\n";
    }
    echo "\n";
}
