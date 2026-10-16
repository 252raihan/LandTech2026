<?php

use Illuminate\Support\Facades\Route;

/*
| LandTech 2026 — P3: Fraud & Multiple Sale Detection (Frontend Prototype)
| View-serving only. NO backend business logic, NO database, NO real APIs.
| All modules are simulated client-side in JavaScript (see public/js/).
*/

$pages = [
    'dashboard'          => 'Dashboard Overview',
    'saga'               => 'Saga Orchestration',
    'sync-webhook'       => 'M1 · Sync & Webhook Layer',
    'crypto-verify'      => 'M2 · Cryptographic Verification',
    'plot-lock'          => 'M3 · Real-Time Plot Lock',
    'ekyc-biometric'     => 'M4 · e-KYC & Biometric Gateway',
    'bank-lien'          => 'M5 · Bank Lien Registry',
    'ai-ocr-conflict'    => 'M6 · AI/OCR Conflict Detection',
    'faraz-calculator'   => 'M7 · Faraz Share Calculator',
    'gis-map'            => 'M8 · GIS Spatial Mapping',
    'risk-scoring'       => 'M9 · Public Risk Scoring',
    'audit-ledger'       => 'M10 · Immutable Audit Ledger',
];

foreach ($pages as $slug => $title) {
    Route::view("/{$slug}", "pages.{$slug}")->name($slug);
}

Route::redirect('/', '/dashboard');
