<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LandTech 2026') · P3 Fraud & Multiple Sale Detection</title>
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="/vendor/leaflet/leaflet.css" />
    <script src="/vendor/leaflet/leaflet.js"></script>
    <script src="/vendor/chartjs/chart.umd.min.js"></script>
</head>
<body class="min-h-screen">
<!-- SIMULATION DISCLAIMER BANNER -->
<div class="bg-amber-400 text-amber-950 text-xs text-center font-semibold py-1.5 px-4">
    ⚠️ FRONTEND DEMO PROTOTYPE — All government registries, Porichoy e-KYC, bank/CIB systems, Redis locks,
    AI/OCR and blockchain features are <strong>browser simulations with synthetic data</strong>. No real
    integration exists. Not connected to any government system.
</div>

<div class="flex min-h-[calc(100vh-32px)]">
    <!-- SIDEBAR -->
    <aside class="w-64 shrink-0 bg-gov-950 text-slate-300 hidden lg:flex flex-col">
        <div class="px-5 py-5 border-b border-white/10">
            <h1 class="text-white font-bold text-lg leading-tight">LandTech 2026</h1>
            <p class="text-[11px] text-slate-400 mt-1">P3 · Fraud & Multiple Sale Detection</p>
        </div>
        <nav class="flex-1 overflow-y-auto py-3 text-sm">
            @php($nav = [
                ['dashboard', '📊', 'Dashboard'],
                ['saga', '🔁', 'Saga Orchestration'],
                ['sync-webhook', '📡', 'M1 · Sync & Webhook'],
                ['crypto-verify', '🔐', 'M2 · Crypto Verification'],
                ['plot-lock', '🔒', 'M3 · Plot Lock'],
                ['ekyc-biometric', '🪪', 'M4 · e-KYC Gateway'],
                ['bank-lien', '🏦', 'M5 · Bank Lien Registry'],
                ['ai-ocr-conflict', '🤖', 'M6 · AI/OCR Conflict'],
                ['faraz-calculator', '🧮', 'M7 · Faraz Calculator'],
                ['gis-map', '🗺️', 'M8 · GIS Mapping'],
                ['risk-scoring', '⚠️', 'M9 · Risk Scoring'],
                ['audit-ledger', '⛓️', 'M10 · Audit Ledger'],
            ])
            @foreach ($nav as [$slug, $icon, $label])
                <a href="{{ route($slug) }}"
                   class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/10 {{ request()->routeIs($slug) ? 'bg-gov-700 text-white border-l-4 border-accent' : 'border-l-4 border-transparent' }}">
                    <span>{{ $icon }}</span><span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        <div class="px-5 py-4 text-[10px] text-slate-500 border-t border-white/10">
            Synthetic data only · LocalStorage persistence
        </div>
    </aside>

    <!-- MOBILE NAV -->
    <div class="lg:hidden fixed bottom-0 inset-x-0 bg-gov-950 overflow-x-auto flex z-50">
        @foreach ($nav as [$slug, $icon, $label])
            <a href="{{ route($slug) }}" class="px-4 py-2.5 text-lg {{ request()->routeIs($slug) ? 'text-accent' : 'text-slate-400' }}">{{ $icon }}</a>
        @endforeach
    </div>

    <!-- MAIN -->
    <main class="flex-1 overflow-x-hidden pb-16 lg:pb-0">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-40">
            <div>
                <h2 class="font-bold text-slate-900">@yield('page-title', 'Dashboard')</h2>
                <p class="text-xs text-slate-500">@yield('page-sub', 'Fraud & Multiple Sale Detection Platform')</p>
            </div>
            <span class="sim-badge">Simulated Module</span>
        </header>
        <div class="p-6 max-w-7xl mx-auto">
            @yield('content')
        </div>
    </main>
</div>

@stack('scripts')
</body>
</html>
