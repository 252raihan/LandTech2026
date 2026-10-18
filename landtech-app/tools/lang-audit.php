<?php

/*
 * Translation audit for LandTech 2026.
 *
 * Scans Blade views and JS modules for translation keys, then diffs them
 * against lang/en/pages.php and lang/bn/pages.php so missing keys are found
 * before the test suite is run.
 *
 * Usage: php tools/lang-audit.php
 */

$root = dirname(__DIR__);

$en = flatten(require $root.'/lang/en/pages.php');
$bn = flatten(require $root.'/lang/bn/pages.php');

$knownGroups = ['layout', 'nav', 'common', 'js'];
foreach (array_keys(require $root.'/lang/en/pages.php') as $group) {
    $knownGroups[] = $group;
}

$used = [];
walkDir($root.'/resources/views', ['blade.php'], $used, $knownGroups, $root);
walkDir($root.'/resources/js', ['js'], $used, $knownGroups, $root);
walkDir($root.'/public/js', ['js'], $used, $knownGroups, $root);

$usage = $used;
$used = array_keys($usage);
sort($used);

$missingEn = array_values(array_diff($used, array_keys($en)));
$missingBn = array_values(array_diff($used, array_keys($bn)));
$dynamic = array_values(array_filter($used, fn ($k) => str_ends_with($k, '.*')));
$missingEn = array_values(array_filter($missingEn, fn ($k) => ! str_ends_with($k, '.*')));
$missingBn = array_values(array_filter($missingBn, fn ($k) => ! str_ends_with($k, '.*')));

$onlyEn = array_values(array_diff(array_keys($en), array_keys($bn)));
$onlyBn = array_values(array_diff(array_keys($bn), array_keys($en)));

$unusedEn = array_values(array_diff(array_keys($en), $used));
$unusedBn = array_values(array_diff(array_keys($bn), $used));

$identical = [];
foreach ($en as $key => $value) {
    if (isset($bn[$key]) && $bn[$key] === $value && preg_match('/[A-Za-z]/', $value)) {
        $identical[] = $key;
    }
}

echo "USED KEYS: ".count($used)."\n";
echo 'EN KEYS: '.count($en)."  BN KEYS: ".count($bn)."\n\n";

report('MISSING FROM en/pages.php (used but undefined)', array_map(fn ($k) => $k.'  ('.($usage[$k] ?? '?').')', $missingEn));
report('MISSING FROM bn/pages.php (used but undefined)', array_map(fn ($k) => $k.'  ('.($usage[$k] ?? '?').')', $missingBn));
report('DYNAMIC PREFIXES (resolved at runtime, informational)', $dynamic);
report('IN en BUT NOT bn', $onlyEn);
report('IN bn BUT NOT en', $onlyBn);
report('BN IDENTICAL TO EN (test violation unless exempted)', $identical);
report('DEFINED IN en BUT CURRENTLY UNUSED (informational)', $unusedEn);
report('DEFINED IN bn BUT CURRENTLY UNUSED (informational)', $unusedBn);

exit(($missingEn || $missingBn || $onlyEn || $onlyBn || $identical) ? 1 : 0);

function flatten(array $array, string $prefix = ''): array
{
    $out = [];
    foreach ($array as $key => $value) {
        $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;
        if (is_array($value)) {
            $out += flatten($value, $full);
        } else {
            $out[$full] = (string) $value;
        }
    }

    return $out;
}

function walkDir(string $dir, array $extensions, array &$used, array $knownGroups, string $root): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir.DIRECTORY_SEPARATOR.$entry;
        if (is_dir($path)) {
            walkDir($path, $extensions, $used, $knownGroups, $root);
            continue;
        }
        foreach ($extensions as $ext) {
            if (str_ends_with($entry, $ext)) {
                scanFile($path, $used, $knownGroups, $root);
                break;
            }
        }
    }
}

function scanFile(string $path, array &$used, array $knownGroups, string $root): void
{
    $contents = file_get_contents($path);
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));

    // Any quoted literal that looks like a translation key (group.subkey...).
    // Keys are handed around as plain strings too (arrays, helper arguments),
    // so matching call sites alone is not enough.
    preg_match_all(
        '/[\'"]([a-z][a-z0-9\-]*(?:\.[a-zA-Z0-9_\-]+)+)[\'"]/',
        $contents,
        $m,
        PREG_OFFSET_CAPTURE
    );

    // Template-literal dynamic keys: t(`prefix.${expr}`)
    preg_match_all(
        '/\bt\(\s*`([A-Za-z0-9][A-Za-z0-9_.\-]*)\$\{/',
        $contents,
        $m3
    );

    $hits = [];
    foreach ($m[1] as [$key, $offset]) {
        $hits[] = [$key, $offset];
    }
    foreach ($m3[1] as $key) {
        // Dynamic prefix: keep the trailing separator so it is filtered later.
        $hits[] = [$key.'.', 0];
    }

    foreach ($hits as [$key, $offset]) {
        // Normalise: view helper keys may be prefixed with "pages.".
        if (str_starts_with($key, 'pages.')) {
            $key = substr($key, 6);
        }

        $first = explode('.', $key)[0];
        if (! in_array($first, $knownGroups, true)) {
            continue; // other namespaces (auth.*, validation.*, ...)
        }

        if (! str_contains($key, '.')) {
            continue; // group-only root like __('pages.common')
        }

        // Dynamic prefixes ("common." + x, "type_" + y) are not real keys.
        if (str_ends_with($key, '.') || str_ends_with($key, '_')) {
            $used[$key.'.*'] = true;

            continue;
        }

        $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
        $used[$key] = $rel.':'.$line;
    }
}

function report(string $title, array $keys): void
{
    echo "== {$title} ==\n";
    if (! $keys) {
        echo "  (none)\n\n";

        return;
    }
    foreach ($keys as $key) {
        echo "  - {$key}\n";
    }
    echo "\n";
}
