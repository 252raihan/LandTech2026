<?php

/*
|--------------------------------------------------------------------------
| LandTech 2026 — Officer / Admin dashboard strings (English)
|--------------------------------------------------------------------------
|
| Covers the Phase 4 role landing pages. Every key here MUST exist in
| bn/officer.php — MissingTranslationsTest enforces parity.
|
| These strings describe placeholder dashboards honestly: they must not imply
| functionality that is not implemented.
|
*/

return [
    'registry' => [
        'title' => 'Registry Dashboard',
        'subtitle' => 'Land records, deed and ownership verification',
    ],

    'bank' => [
        'title' => 'Bank Dashboard',
        'subtitle' => 'Lien records, e-NOC and loan security status',
    ],

    'admin' => [
        'title' => 'Admin Dashboard',
        'subtitle' => 'Operational oversight and application review',
    ],

    'your_dashboard' => 'Your dashboard',
    'pending_badge' => 'Dashboard pending',    'pending_title' => 'This dashboard is not implemented yet.',
    'pending_hint' => 'Your role has been verified and you are signed in to the correct area. The operational dashboard for this role is still being built — no records or statistics are shown here because none are available yet.',
    'available_now' => 'Available now',
    'profile_link' => 'Profile & security settings',
    'demo_note' => 'The public M1–M10 demonstration modules remain available from the main navigation.',

    'no_role' => [
        'title' => 'Account Setup Incomplete',
        'subtitle' => 'No role has been assigned to your account',
        'heading' => 'Your account has no assigned role.',
        'hint' => 'You are signed in, but no role has been granted to your account, so no dashboard is available. An administrator must assign your role before you can use the system. You can still manage your profile and security settings.',
        'logout' => 'Log out',
    ],
];
