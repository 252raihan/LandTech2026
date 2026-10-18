<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | Controls whether the Welcome page offers one-click "enter this panel"
    | shortcuts for the presentation demo.
    |
    | TWO independent conditions must BOTH hold before any demo shortcut is
    | offered or honoured:
    |
    |   1. this flag is true (`LANDTECH_DEMO_MODE=true`), and
    |   2. the application environment is `local` or `testing`.
    |
    | The default is false, so a fresh checkout — and every production
    | deployment — has demo shortcuts switched off. Even with the flag on, the
    | environment check in App\Support\Demo\DemoMode::enabled() still refuses to
    | activate outside local/testing, so a stray `LANDTECH_DEMO_MODE=true` in a
    | production .env cannot expose demo entry points.
    |
    | Demo Mode NEVER grants a role, changes the current user, or bypasses
    | authorization. It only decides whether the landing page renders clearly
    | labelled shortcuts that log in a pre-seeded DEVELOPMENT demo account
    | through the normal authentication stack. See DemoPanelController.
    |
    */

    'demo_mode' => env('LANDTECH_DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Demo Accounts
    |--------------------------------------------------------------------------
    |
    | The demo accounts used by Demo Mode, keyed by role name.
    |
    | These are DEVELOPMENT-ONLY identities created by DatabaseSeeder when the
    | environment is local/testing. Passwords are NEVER stored here: the
    | seeder derives them from the LANDTECH_DEMO_PASSWORD environment variable,
    | falling back to a random unusable value. This map only records which email
    | belongs to which role so the demo controller can look the account up.
    |
    | A role is only offered on the Welcome page when BOTH a route exists and
    | its demo account exists in the database. Missing accounts are reported as
    | unavailable rather than invented.
    |
    */

    'demo_accounts' => [
        \App\Models\Role::ADMIN => 'admin.demo@example.test',
        \App\Models\Role::REGISTRY_OFFICER => 'registry.officer@example.test',
        \App\Models\Role::BANK_OFFICER => 'bank.officer@example.test',
        \App\Models\Role::CITIZEN => 'citizen.demo@example.test',
    ],

];
