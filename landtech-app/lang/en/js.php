<?php

/*
|--------------------------------------------------------------------------
| JavaScript translation dictionary (English)
|--------------------------------------------------------------------------
|
| This file feeds window.__t(key, replacements) in the browser via
| LocaleController::script(). Keys are addressed with dot notation, so
| __t('risk-scoring.band_high') resolves through this nested array.
|
| SINGLE SOURCE OF TRUTH: all page/UI strings live in pages.php. This file
| re-exports those groups so client-side code can reuse exactly the same keys
| as the Blade templates, plus the handful of JS-only keys below. No string
| is ever hardcoded inside a public/js/*.js module.
|
| NAMESPACE: the `js` group from pages.php is flattened to the top level so
| __t('kyc_provider') works, while page groups stay nested under their slug.
*/

$pages = require __DIR__.'/pages.php';

return array_merge(
    // JS-only keys that never appear in Blade templates.
    [
        'language' => 'Language',
        'simulated_notice' => 'All data in this demo is simulated.',
        'error_generic' => 'Something went wrong. Please try again.',
        'reset_confirm' => 'Reset all demo data?',
        'loading' => 'Loading…',
    ],

    // pages.js.* promoted to the top level for convenience in JS modules.
    $pages['js'] ?? [],

    // Every page group stays nested, so JS can call the same keys as Blade.
    array_diff_key($pages, ['js' => null]),
);
