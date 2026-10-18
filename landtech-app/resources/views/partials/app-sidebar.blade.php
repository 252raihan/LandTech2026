@php
    // Citizen/User Panel sidebar — only these items, per the panel spec.
    $citizenNav = [
        ['citizen.dashboard', 'dashboard', __('citizen.nav.dashboard')],
        ['citizen.land', 'map', __('citizen.nav.my_land')],
        ['citizen.deeds', 'document', __('citizen.nav.my_deeds')],
        ['citizen.ownership', 'shield-check', __('citizen.nav.ownership_shares')],
        ['citizen.applications', 'document', __('citizen.nav.my_applications')],
        ['citizen.notifications', 'bell', __('citizen.nav.notifications')],
        ['citizen.help', 'chat', __('citizen.nav.help_support')],
    ];
    // Officer/Admin landing items. Only dashboards that actually exist are
    // listed — no placeholder menu entries are invented.
    $officerNav = [];
    $user = auth()->user();

    if ($user?->hasRole(\App\Models\Role::ADMIN)) {
        $officerNav[] = ['admin.dashboard', 'dashboard', __('officer.admin.title')];
    }
    if ($user?->hasRole(\App\Models\Role::REGISTRY_OFFICER)) {
        $officerNav[] = ['registry.dashboard', 'dashboard', __('officer.registry.title')];
    }
    if ($user?->hasRole(\App\Models\Role::BANK_OFFICER)) {
        $officerNav[] = ['bank.dashboard', 'dashboard', __('officer.bank.title')];
    }

    $navOverview = [
        ['dashboard', 'dashboard', __('pages.nav.dashboard')],
        ['saga', 'saga', __('pages.nav.saga')],
    ];
    $navModules = [
        ['sync-webhook', 'sync', __('pages.nav.sync-webhook')],
        ['crypto-verify', 'shield-check', __('pages.nav.crypto-verify')],
        ['plot-lock', 'lock', __('pages.nav.plot-lock')],
        ['ekyc-biometric', 'id-card', __('pages.nav.ekyc-biometric')],
        ['bank-lien', 'banknotes', __('pages.nav.bank-lien')],
        ['ai-ocr-conflict', 'cpu', __('pages.nav.ai-ocr-conflict')],
        ['faraz-calculator', 'scale', __('pages.nav.faraz-calculator')],
        ['gis-map', 'map', __('pages.nav.gis-map')],
        ['risk-scoring', 'warning', __('pages.nav.risk-scoring')],
        ['audit-ledger', 'chain', __('pages.nav.audit-ledger')],
    ];
@endphp

<aside id="app-sidebar" class="app-sidebar" aria-label="{{ __('pages.layout.primary_navigation') }}">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3" title="{{ __('pages.layout.app_name') }}">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-icon name="layers" class="h-5 w-5" />
            </span>
            <span class="sidebar-brand-text min-w-0">
                <span class="block truncate text-sm font-bold text-white">{{ __('pages.layout.app_name') }}</span>
                <span class="block truncate text-[11px] text-slate-400">{{ __('pages.layout.app_tagline') }}</span>
            </span>
        </a>
        <button type="button" id="mobile-nav-close"
                class="ms-auto rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden"
                aria-label="{{ __('pages.layout.close_menu') }}">
            <x-icon name="close" class="h-5 w-5" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-2 pb-4">
        @if (auth()->user()?->hasAnyRole([\App\Models\Role::CITIZEN]) && ! auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::REGISTRY_OFFICER, \App\Models\Role::BANK_OFFICER]))
            {{-- Phase 5: the citizen sidebar is mode-aware. Mode is a session
                 navigation preference only — it never changes authorization. --}}
            @include('partials.citizen-mode-nav')
        @else
            @if ($officerNav !== [])
                <p class="app-nav-group-label sidebar-group-label">{{ __('officer.your_dashboard') }}</p>
                @foreach ($officerNav as [$route, $icon, $label])
                    <a href="{{ route($route) }}"
                       class="app-nav-link {{ request()->routeIs($route) ? 'app-nav-link-active' : '' }}"
                       title="{{ $label }}"
                       @if (request()->routeIs($route)) aria-current="page" @endif>
                        <x-icon :name="$icon" class="nav-icon" />
                        <span class="sidebar-label truncate">{{ $label }}</span>
                    </a>
                @endforeach
            @endif

            <p class="app-nav-group-label sidebar-group-label">{{ __('pages.layout.nav_overview') }}</p>
            @foreach ($navOverview as [$slug, $icon, $label])
                <a href="{{ route($slug) }}"
                   class="app-nav-link {{ request()->routeIs($slug) ? 'app-nav-link-active' : '' }}"
                   title="{{ $label }}"
                   @if (request()->routeIs($slug)) aria-current="page" @endif>
                    <x-icon :name="$icon" class="nav-icon" />
                    <span class="sidebar-label truncate">{{ $label }}</span>
                </a>
            @endforeach

            <p class="app-nav-group-label sidebar-group-label">{{ __('pages.layout.nav_modules') }}</p>
            @foreach ($navModules as [$slug, $icon, $label])
                <a href="{{ route($slug) }}"
                   class="app-nav-link {{ request()->routeIs($slug) ? 'app-nav-link-active' : '' }}"
                   title="{{ $label }}"
                   @if (request()->routeIs($slug)) aria-current="page" @endif>
                    <x-icon :name="$icon" class="nav-icon" />
                    <span class="sidebar-label truncate">{{ $label }}</span>
                </a>
            @endforeach
        @endif
    </nav>

    <div class="sidebar-footer">
        {{ __('pages.layout.footer_note') }}
    </div>
</aside>
