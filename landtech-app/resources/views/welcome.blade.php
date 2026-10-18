<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('welcome.meta_title') }}</title>
    <meta name="description" content="{{ __('welcome.meta_description') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=hind-siliguri:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.locale-bootstrap')
</head>
<body class="min-h-screen bg-slate-100">
<a class="skip-link" href="#main-content">{{ __('pages.layout.skip_to_content') }}</a>

{{-- Simulation disclaimer, consistent with every other page in the app. --}}
<div class="border-b border-amber-200 bg-amber-50 px-4 py-1.5 text-center text-[11px] font-medium leading-relaxed text-amber-900">
    {{ __('pages.layout.disclaimer', ['strong' => __('pages.layout.disclaimer_strong')]) }}
</div>

<div class="flex min-h-screen flex-col">

    {{-- ============================ HEADER ============================ --}}
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-[90rem] items-center justify-between gap-3 px-4 py-3 lg:px-8">
            <a href="{{ route('welcome') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <x-icon name="layers" class="h-5 w-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-navy-900">{{ __('pages.layout.app_name') }}</span>
                    <span class="block truncate text-[11px] text-slate-400">{{ __('pages.layout.app_tagline') }}</span>
                </span>
            </a>

            <div class="flex shrink-0 items-center gap-2">
                @include('partials.locale-switcher')

                @auth
                    <a href="{{ route(\App\Support\Auth\LandingPage::routeNameFor(auth()->user())) }}"
                       class="btn-primary btn-sm">{{ __('welcome.go_to_my_dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary btn-sm">{{ __('welcome.log_in') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="main-content" class="flex-1">

        {{-- ============================ HERO ============================ --}}
        <section class="border-b border-slate-200 bg-white">
            <div class="mx-auto grid w-full max-w-[90rem] gap-10 px-4 py-12 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-16">

                <div class="min-w-0">
                    <span class="badge badge-blue">{{ __('welcome.hero.badge') }}</span>

                    <h1 class="mt-4 text-3xl font-bold leading-tight text-navy-900 sm:text-4xl">
                        {{ __('welcome.hero.heading') }}
                    </h1>

                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        {{ __('welcome.hero.lead') }}
                    </p>

                    <p class="mt-3 text-sm leading-relaxed text-slate-500">
                        {{ __('welcome.hero.purpose') }}
                    </p>

                    <div class="mt-6 flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ route(\App\Support\Auth\LandingPage::routeNameFor(auth()->user())) }}" class="btn-primary">
                                <x-icon name="dashboard" class="h-4 w-4" />
                                {{ __('welcome.go_to_my_dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-primary">
                                <x-icon name="user" class="h-4 w-4" />
                                {{ __('welcome.log_in') }}
                            </a>
                        @endauth

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-ghost border-slate-200">
                                {{ __('welcome.register') }}
                            </a>
                        @endif

                        <a href="#demo-panels" class="btn-ghost border-slate-200">
                            {{ __('welcome.hero.explore_cta') }}
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    </div>

                    {{-- Demo Mode status is always shown, on or off, so the state
                         of the system is never ambiguous during a presentation. --}}
                    <p class="mt-5 flex items-start gap-2 text-xs leading-relaxed text-slate-500">
                        @if ($demoMode)
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                            <span>{{ __('welcome.hero.demo_on') }}</span>
                        @else
                            <x-icon name="info-circle" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                            <span>{{ __('welcome.hero.demo_off') }}</span>
                        @endif
                    </p>
                </div>

                {{-- Login card --}}
                <div class="min-w-0">
                    <div class="card p-6 sm:p-8">
                        <h2 class="text-lg font-bold text-navy-900">{{ __('welcome.login_card.heading') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('welcome.login_card.subtitle') }}</p>

                        @guest
                            <a href="{{ route('login') }}" class="btn-primary mt-5 w-full justify-center">
                                <x-icon name="user" class="h-4 w-4" />
                                {{ __('welcome.login_card.button') }}
                            </a>

                            @if (Route::has('register'))
                                <p class="mt-3 text-center text-sm text-slate-500">
                                    {{ __('welcome.login_card.no_account') }}
                                    <a href="{{ route('register') }}" class="font-semibold text-brand-700 underline">{{ __('welcome.register') }}</a>
                                </p>
                            @endif
                        @endguest

                        @auth
                            <div class="mt-5 rounded-lg border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ __('welcome.login_card.signed_in_as', ['name' => auth()->user()->name]) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ auth()->user()->email }}</p>
                            </div>

                            <a href="{{ route(\App\Support\Auth\LandingPage::routeNameFor(auth()->user())) }}"
                               class="btn-primary mt-4 w-full justify-center">
                                <x-icon name="dashboard" class="h-4 w-4" />
                                {{ __('welcome.go_to_my_dashboard') }}
                            </a>
                        @endauth

                        {{-- Honest data note: no real records are used anywhere. --}}
                        <p class="mt-5 border-t border-slate-200 pt-4 text-xs leading-relaxed text-slate-400">
                            {{ __('welcome.login_card.data_note') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ======================= PURPOSE / FEATURES ====================== --}}
        <section class="mx-auto w-full max-w-[90rem] px-4 py-12 lg:px-8">
            <div class="max-w-3xl">
                <h2 class="text-2xl font-bold text-navy-900">{{ __('welcome.purpose.heading') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('welcome.purpose.body') }}</p>
            </div>

            <div class="mt-8 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['icon' => 'shield-exclamation', 'title' => 'welcome.purpose.items.fraud.title', 'body' => 'welcome.purpose.items.fraud.body'],
                    ['icon' => 'search', 'title' => 'welcome.purpose.items.multiple_sale.title', 'body' => 'welcome.purpose.items.multiple_sale.body'],
                    ['icon' => 'lock', 'title' => 'welcome.purpose.items.privacy.title', 'body' => 'welcome.purpose.items.privacy.body'],
                    ['icon' => 'chain', 'title' => 'welcome.purpose.items.audit.title', 'body' => 'welcome.purpose.items.audit.body'],
                ] as $item)
                    <div class="card p-5">
                        <span class="stat-icon bg-brand-50 text-brand-700">
                            <x-icon :name="$item['icon']" />
                        </span>
                        <p class="mt-3 text-sm font-bold text-navy-900">{{ __($item['title']) }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ __($item['body']) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ====================== EXPLORE DEMO PANELS ====================== --}}
        <section id="demo-panels" class="border-y border-slate-200 bg-white">
            <div class="mx-auto w-full max-w-[90rem] px-4 py-12 lg:px-8">

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl">
                        <h2 class="text-2xl font-bold text-navy-900">{{ __('welcome.panels.heading') }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('welcome.panels.subheading') }}</p>
                    </div>

                    <span class="badge {{ $demoMode ? 'badge-green' : 'badge-slate' }}">
                        {{ $demoMode ? __('welcome.panels.demo_badge_on') : __('welcome.panels.demo_badge_off') }}
                    </span>
                </div>

                @unless ($demoMode)
                    <div class="alert alert-warning mt-6">
                        <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
                        <p class="min-w-0 flex-1">{{ __('welcome.panels.demo_disabled_note') }}</p>
                    </div>
                @endunless

                <div class="mt-8 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($panels as $panel)
                        <div class="card flex-col p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="stat-icon {{ $panel['available'] ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-400' }}">
                                    <x-icon :name="$panel['icon']" />
                                </span>

                                @if ($panel['coming_soon'])
                                    <span class="badge badge-slate">{{ __('welcome.panels.coming_soon') }}</span>
                                @else
                                    <span class="badge badge-green">{{ __('welcome.panels.available') }}</span>
                                @endif
                            </div>

                            <p class="mt-3 text-base font-bold text-navy-900">{{ __($panel['title']) }}</p>
                            <p class="mt-1 flex-1 text-sm leading-relaxed text-slate-500">{{ __($panel['description']) }}</p>

                            <div class="mt-4 border-t border-slate-200 pt-4">
                                @if ($panel['can_enter'])
                                    {{-- Demo shortcut: links to the demo controller, which
                                         performs a genuine login for the seeded demo
                                         account. It grants no role. --}}
                                    <a href="{{ route('demo.panel', ['role' => $panel['key']]) }}"
                                       class="btn-primary btn-sm w-full justify-center">
                                        <x-icon name="arrow-right" class="h-4 w-4" />
                                        {{ __('welcome.panels.enter_demo') }}
                                    </a>
                                    <p class="mt-2 text-center text-[11px] leading-relaxed text-slate-400">
                                        {{ __('welcome.panels.demo_account_note') }}
                                    </p>
                                @elseif ($panel['coming_soon'])
                                    <p class="text-center text-xs leading-relaxed text-slate-400">
                                        {{ __('welcome.panels.coming_soon_hint') }}
                                    </p>
                                @else
                                    {{-- Implemented, but Demo Mode is off or no demo
                                         account is seeded: point at the real login. --}}
                                    <a href="{{ $panel['login_url'] }}"
                                       class="btn-ghost btn-sm w-full justify-center border-slate-200">
                                        <x-icon name="user" class="h-4 w-4" />
                                        {{ __('welcome.panels.sign_in') }}
                                    </a>
                                    <p class="mt-2 text-center text-[11px] leading-relaxed text-slate-400">
                                        {{ __('welcome.panels.no_demo_account') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Missing demo accounts are reported plainly, never invented. --}}
                @if ($demoMode && $missingDemoAccounts !== [])
                    <div class="alert alert-warning mt-6">
                        <x-icon name="warning" class="h-5 w-5 shrink-0" />
                        <p class="min-w-0 flex-1">
                            {{ __('welcome.panels.missing_accounts', ['roles' => implode(', ', $missingDemoAccounts)]) }}
                        </p>
                    </div>
                @endif
            </div>
        </section>

        {{-- ===================== M1–M10 PUBLIC MODULES ===================== --}}
        <section class="mx-auto w-full max-w-[90rem] px-4 py-12 lg:px-8">
            <div class="max-w-3xl">
                <h2 class="text-2xl font-bold text-navy-900">{{ __('welcome.modules.heading') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('welcome.modules.subheading') }}</p>
            </div>

            <div class="mt-6 grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    ['sync-webhook', 'welcome.modules.m1'],
                    ['crypto-verify', 'welcome.modules.m2'],
                    ['plot-lock', 'welcome.modules.m3'],
                    ['ekyc-biometric', 'welcome.modules.m4'],
                    ['bank-lien', 'welcome.modules.m5'],
                    ['ai-ocr-conflict', 'welcome.modules.m6'],
                    ['faraz-calculator', 'welcome.modules.m7'],
                    ['gis-map', 'welcome.modules.m8'],
                    ['risk-scoring', 'welcome.modules.m9'],
                    ['audit-ledger', 'welcome.modules.m10'],
                ] as [$slug, $label])
                    @if (Route::has($slug))
                        <a href="{{ route($slug) }}"
                           class="menu-item border-slate-200 bg-white">
                            <span class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-700">{{ __($label) }}</span>
                            <x-icon name="external-link" class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="mt-6 flex-wrap gap-3">
                @if (Route::has('dashboard'))
                    <a href="{{ route('dashboard') }}" class="btn-ghost border-slate-200">
                        <x-icon name="dashboard" class="h-4 w-4" />
                        {{ __('welcome.modules.overview') }}
                    </a>
                @endif
                @if (Route::has('saga'))
                    <a href="{{ route('saga') }}" class="btn-ghost border-slate-200">
                        <x-icon name="saga" class="h-4 w-4" />
                        {{ __('welcome.modules.saga') }}
                    </a>
                @endif
            </div>

            <p class="mt-6 max-w-3xl text-xs leading-relaxed text-slate-400">
                {{ __('welcome.modules.demo_data_note') }}
            </p>
        </section>
    </main>

    {{-- ============================ FOOTER ============================ --}}
    <footer class="border-t border-slate-200 bg-white px-4 py-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-[90rem] flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-400">{{ __('pages.layout.footer_note') }}</p>
            <p class="text-xs text-slate-400">{{ __('welcome.footer.demo_mode_label') }}:
                <span class="font-semibold {{ $demoMode ? 'text-emerald-600' : 'text-slate-500' }}">
                    {{ $demoMode ? __('welcome.footer.on') : __('welcome.footer.off') }}
                </span>
            </p>
        </div>
    </footer>
</div>

<div id="toast-region" class="toast-region" role="status" aria-live="polite" aria-atomic="false"></div>

@stack('scripts')
</body>
</html>
