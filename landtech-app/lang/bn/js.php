<?php

/*
|--------------------------------------------------------------------------
| JavaScript translation dictionary (Bengali)
|--------------------------------------------------------------------------
|
| Mirrors lang/en/js.php. All page/UI strings come from bn/pages.php, so the
| browser dictionary and the server-rendered templates can never drift.
*/

$pages = require __DIR__.'/pages.php';

return array_merge(
    [
        'language' => 'ভাষা',
        'simulated_notice' => 'এই ডেমোর সব ডেটা কৃত্রিম।',
        'error_generic' => 'কিছু ভুল হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
        'reset_confirm' => 'সব ডেমো ডেটা রিসেট করবেন?',
        'loading' => 'লোড হচ্ছে…',
    ],

    $pages['js'] ?? [],

    array_diff_key($pages, ['js' => null]),
);
