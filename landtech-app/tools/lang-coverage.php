<?php

/*
 * --------------------------------------------------------------------------
 * LandTech 2026 - Translation coverage audit (all namespaces)
 * --------------------------------------------------------------------------
 *
 * The original tools/lang-audit.php only inspected the pages dictionary and
 * explicitly skipped every other namespace (see its $knownGroups filter),
 * which is why the citizen.* workflow strings were never audited.
 * This script closes that gap. It extracts translation keys that are
 * genuinely referenced by the application, resolves each one through
 * Laravel's real translation pipeline in BOTH locales, and reports any key
 * that does not resolve.
 *
 * Usage:
 *   php tools/lang-coverage.php            # summary + list of missing keys
 *   php tools/lang-coverage.php --quiet    # exit code only (for CI)
 *
 * Exit code 0 = every referenced key resolves in both locales.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$root = dirname(__DIR__);
$quiet = in_array('--quiet', $argv ?? [], true);

/*
|--------------------------------------------------------------------------
| Namespaces to audit.
|--------------------------------------------------------------------------
|
| Laravel resolves a key like "citizen.land.title" against lang/<locale>/citizen.php.
| A bare "auth.failed" resolves against lang/<locale>/auth.php. Keys without a
| dot resolve against lang/<locale>.json (JSON translations).
|
| "pages" is included even though views write "pages.foo.bar" — the audit strips
| that prefix, matching how lang-audit.php already behaves.
*/
$auditedNamespaces = ['citizen', 'pages', 'officer', 'welcome', 'land', 'app', 'js', 'auth', 'validation'];

// Keys that legitimately do not resolve, with the reason documented.
// Keep this list SHORT and justified — every entry weakens the audit.
$exempt = [
    // Dynamic prefixes: callers concatenate a runtime suffix (e.g. "review_status_" + status).
    // These are investigated separately in step 3 of the task.
    'citizen.documents.review_status_',
    'citizen.land.basis_',
    'citizen.land.mutation_',
    'citizen.link.basis_',
    'citizen.link.status_',
    'citizen.listings.verification_',
    'citizen.listings.contact_',
    'citizen.verification.check_result_',
    // M6 AI/OCR conflict badges: view builds "severity_" + c.severity and
    // "type_" + c.type at runtime. All suffixes (critical/high/medium/low,
    // multiple_sale/rapid_transfer/mutation_mismatch/survey_conflict) exist
    // as real keys in BOTH locales — verified in step 3.
    'pages.ai-ocr-conflict.severity_',
    'pages.ai-ocr-conflict.type_',
];

/*
|--------------------------------------------------------------------------
| 1. Extract referenced keys from Blade, PHP controllers, and JS modules.
|--------------------------------------------------------------------------
*/
$sources = [];
foreach ([
    $root.'/resources/views' => ['.blade.php'],
    $root.'/app' => ['.php'],
    $root.'/public/js' => ['.js'],
    $root.'/resources/js' => ['.js'],
] as $dir => $exts) {
    if (! is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $path = $file->getPathname();
        foreach ($exts as $ext) {
            if (str_ends_with($path, $ext)) {
                $sources[] = $path;
                break;
            }
        }
    }
}

$referenced = [];   // key => "relpath:line"

foreach ($sources as $path) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $contents = file_get_contents($path);
    $lines = explode("\n", $contents);

    foreach ($lines as $i => $line) {
        $lineNo = $i + 1;

        // Skip comment-only lines so documentation examples do not register as usage.
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#')) {
            continue;
        }

        // __t( 'key' ) / __t( "key" )  -- JS dictionary helper.
        // Must be matched BEFORE the __( pattern, otherwise the "__" inside
        // "__t" is captured and the double-quoted form is missed entirely.
        if (preg_match_all('/\b__t\s*\(\s*[\'"]([a-zA-Z0-9_\-]+(?:\.[a-zA-Z0-9_\-]+)+)[\'"]/', $line, $mt)) {
            foreach ($mt[1] as $k) {
                $referenced[$k] ??= $rel.':'.$lineNo;
            }
        }

        // __( 'key' ) / @lang( 'key' ) / trans( 'key' ) / trans( "key" )
        if (preg_match_all('/\b(?:__|trans|@lang)\s*\(\s*[\'"]([a-zA-Z0-9_\-]+(?:\.[a-zA-Z0-9_\-]+)*)[\'"]/', $line, $m)) {
            foreach ($m[1] as $k) {
                $referenced[$k] ??= $rel.':'.$lineNo;
            }
        }

        // Controller flash strings: ->with('status', 'some-key') — these are keys
        // only when the consumer resolves them through a namespace. Handled below.
    }
}

/*
|--------------------------------------------------------------------------
| 2. Extract controller flash "status" keys and map them to real namespaces.
|--------------------------------------------------------------------------
*/
$flash = [];
foreach ($sources as $path) {
    if (! str_contains($path, DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR)) {
        continue;
    }
    $contents = file_get_contents($path);
    if (preg_match_all("/->with\(\s*'status'\s*,\s*'([a-z0-9\-]+)'/", $contents, $m)) {
        foreach ($m[1] as $v) {
            $flash[$v] = true;
        }
    }
}

/*
 * --------------------------------------------------------------------------
 * 3. Resolve every referenced key in both locales.
 * --------------------------------------------------------------------------
 *
 * Keys are resolved exactly as written in the source. Laravel maps
 * "pages.ai-ocr-conflict.title" to lang/<locale>/pages.php -> ['ai-ocr-conflict']['title'],
 * so NO prefix stripping is performed here. (tools/lang-audit.php strips "pages."
 * only because it compares against a flattened pages.php array; that is a
 * different, file-based approach and must not be copied into runtime resolution.)
 */

$check = [];
foreach ($referenced as $key => $where) {
    $ns = explode('.', $key)[0];
    if (! in_array($ns, $auditedNamespaces, true)) {
        continue; // namespaces outside the audit scope
    }
    $check[$key] = $where;
}

// Add the flash keys as they are consumed: 'citizen.<section>.<dash-key>'.
foreach (array_keys($flash) as $v) {
    $check['citizen.land.'.str_replace('-', '_', $v)] = 'controller flash (mapped to citizen.land.*)';
}

$missing = ['en' => [], 'bn' => []];

foreach (array_keys($check) as $key) {
    $k = $key;

    if (in_array($k, $exempt, true)) {
        continue;
    }

    foreach (['en', 'bn'] as $loc) {
        app()->setLocale($loc);
        $resolved = __($k);
        // Laravel returns the key itself when nothing matches.
        if ($resolved === $k || $resolved === '') {
            $missing[$loc][$k] = $check[$key];
        }
    }
}

/*
|--------------------------------------------------------------------------
| 4. Report.
|--------------------------------------------------------------------------
*/
$total = count($check);

if (! $quiet) {
    echo "AUDITED NAMESPACES: ".implode(', ', $auditedNamespaces).PHP_EOL;
    echo "REFERENCED KEYS IN SCOPE: {$total}".PHP_EOL;
    echo 'MISSING IN EN: '.count($missing['en']).PHP_EOL;
    echo 'MISSING IN BN: '.count($missing['bn']).PHP_EOL.PHP_EOL;

    foreach (['en', 'bn'] as $loc) {
        echo "== MISSING IN {$loc} ==".PHP_EOL;
        if (! $missing[$loc]) {
            echo "  (none)".PHP_EOL.PHP_EOL;

            continue;
        }
        ksort($missing[$loc]);
        foreach ($missing[$loc] as $key => $where) {
            echo "  - {$key}   ({$where})".PHP_EOL;
        }
        echo PHP_EOL;
    }

    echo "== EXEMPTED (documented) ==".PHP_EOL;
    foreach ($exempt as $e) {
        echo "  - {$e}".PHP_EOL;
    }
    echo PHP_EOL;
}

$failed = count($missing['en']) + count($missing['bn']);
exit($failed === 0 ? 0 : 1);
