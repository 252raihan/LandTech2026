<?php

/*
|--------------------------------------------------------------------------
| LandTech 2026 — Welcome / Landing page (English, source of truth)
|--------------------------------------------------------------------------
|
| Every key here MUST exist in bn/welcome.php — MissingTranslationsTest
| enforces parity in both directions.
|
| Wording rules for this file:
|   - Never promise functionality that is not implemented.
|   - Never state or hint at a credential, password or token.
|   - Panels that do not exist are described as not yet available.
|
*/

return [
    'meta_title' => 'LandTech 2026 · Land Fraud & Multiple-Sale Detection',
    'meta_description' => 'LandTech 2026 is a prototype platform for detecting land fraud and multiple-sale of the same plot, built around verified ownership records, privacy-preserving identity checks and an immutable audit trail.',

    'log_in' => 'Log in',
    'register' => 'Register',
    'go_to_my_dashboard' => 'Go to my dashboard',

    'hero' => [
        'badge' => 'Prototype · Demonstration build',
        'heading' => 'Detecting land fraud and multiple-sale before it costs someone their land',
        'lead' => 'LandTech 2026 is a demonstration platform for a national land-records integrity service. It brings ownership records, identity verification and audit evidence together so that the same plot cannot be quietly sold twice.',
        'purpose' => 'The project targets the two most damaging land-record failures: forged or conflicting ownership claims, and the multiple sale of a single plot to different buyers. Every capability shown here exists to surface those conflicts early and to leave verifiable evidence behind.',
        'explore_cta' => 'Explore demo panels',
        'demo_on' => 'Demo Mode is enabled in this environment. Panel shortcuts use separate development demo accounts.',
        'demo_off' => 'Demo Mode is disabled. Use the normal login page to sign in with a real account.',
    ],

    'login_card' => [
        'heading' => 'Sign in',
        'subtitle' => 'Access your role dashboard using your registered account.',
        'button' => 'Log in to your account',
        'no_account' => 'No account yet?',
        'signed_in_as' => 'Signed in as :name',
        'data_note' => 'All records in this demonstration are synthetic. No real citizen data, national identity numbers or government systems are used anywhere in this build.',
    ],

    'purpose' => [
        'heading' => 'Why this platform exists',
        'body' => 'Land disputes in Bangladesh commonly arise from the same plot being sold to multiple buyers, from forged or conflicting deed records, and from the absence of a single trustworthy ownership history. This prototype demonstrates the controls that would make those abuses detectable.',
        'items' => [
            'fraud' => [
                'title' => 'Fraud detection',
                'body' => 'Conflicting ownership claims, suspicious deed patterns and inconsistent ownership histories are surfaced for review rather than silently accepted.',
            ],
            'multiple_sale' => [
                'title' => 'Multiple-sale alerts',
                'body' => 'A plot under active transfer is locked against a second transfer, so the same land cannot be committed to two buyers at once.',
            ],
            'privacy' => [
                'title' => 'Identity privacy',
                'body' => 'Identity numbers are stored encrypted and masked by default. Full values require an authorized role, a stated purpose and an audit record.',
            ],
            'audit' => [
                'title' => 'Audit evidence',
                'body' => 'Sensitive access and record changes are written to an append-only, hash-chained ledger so the history cannot be quietly rewritten.',
            ],
        ],
    ],

    'panels' => [
        'heading' => 'Explore Demo Panels',
        'subheading' => 'Each panel is scoped to a single role and protected on the server. Selecting a panel signs you in with that role\'s dedicated development demo account, so you see exactly what that role is permitted to see.',
        'available' => 'Available',
        'coming_soon' => 'Coming Soon',
        'enter_demo' => 'Enter demo panel',
        'sign_in' => 'Sign in to view',
        'demo_account_note' => 'Signs in with a seeded development demo account.',
        'no_demo_account' => 'No demo account is seeded. Use the login page.',
        'coming_soon_hint' => 'This panel is not implemented yet, so there is nothing to open.',
        'demo_badge_on' => 'Demo Mode: on',
        'demo_badge_off' => 'Demo Mode: off',
        'demo_disabled_note' => 'Demo Mode is currently disabled, so panel shortcuts are not available. Set LANDTECH_DEMO_MODE=true in a local or testing environment to enable them. Normal login is unaffected.',
        'missing_accounts' => 'Demo Mode is on, but no seeded demo account was found for: :roles. Run the database seeder in a local environment to create them. No account has been created or modified automatically.',

        'super_admin' => [
            'title' => 'Super Admin',
            'description' => 'System-wide oversight, user and role administration, security settings and audit review. This role is not implemented in the current build.',
        ],
        'admin' => [
            'title' => 'Admin',
            'description' => 'Operational oversight: user management, application and correction review, and fraud and conflict reports. The operational dashboard is still being built.',
        ],
        'registry_officer' => [
            'title' => 'Registry Officer',
            'description' => 'Land records, deed and ownership verification, mutation review and conflict alerts within an assigned jurisdiction.',
        ],
        'bank_officer' => [
            'title' => 'Bank Officer',
            'description' => 'Lien records, e-NOC and lien-release requests, and loan security status for their own bank only.',
        ],
        'citizen' => [
            'title' => 'Citizen / Landowner',
            'description' => 'Your own verified land records, deeds, ownership shares and applications. Records appear only once your identity is securely linked by an authorized officer.',
        ],
    ],

    'modules' => [
        'heading' => 'Public demonstration modules (M1–M10)',
        'subheading' => 'These ten modules are open to everyone without signing in. They demonstrate individual platform capabilities in isolation, using synthetic data only.',
        'm1' => 'M1 · Sync & Webhook',
        'm2' => 'M2 · Cryptographic Verification',
        'm3' => 'M3 · Real-Time Plot Lock',
        'm4' => 'M4 · e-KYC & Biometric',
        'm5' => 'M5 · Bank Lien Registry',
        'm6' => 'M6 · AI/OCR Conflict Detection',
        'm7' => 'M7 · Faraz Share Calculator',
        'm8' => 'M8 · GIS Spatial Mapping',
        'm9' => 'M9 · Public Risk Scoring',
        'm10' => 'M10 · Immutable Audit Ledger',
        'overview' => 'Dashboard overview',
        'saga' => 'Saga orchestration',
        'demo_data_note' => 'These pages are front-end demonstrations. They run entirely in the browser against synthetic data and do not read or write any real land or citizen record.',
    ],

    'footer' => [
        'demo_mode_label' => 'Demo Mode',
        'on' => 'On',
        'off' => 'Off',
    ],
];
