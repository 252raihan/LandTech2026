<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('pages.layout.app_name') }} · {{ __('pages.layout.app_tagline') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=hind-siliguri:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.locale-bootstrap')
</head>
<body class="min-h-screen bg-slate-100">
<a class="skip-link" href="#auth-content">{{ __('pages.layout.skip_to_content') }}</a>

<div class="border-b border-amber-200 bg-amber-50 px-4 py-1.5 text-center text-[11px] font-medium leading-relaxed text-amber-900">
    {{ __('pages.layout.disclaimer', ['strong' => __('pages.layout.disclaimer_strong')]) }}
</div>

<div class="flex min-h-screen flex-col">
    <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:px-8">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-icon name="layers" class="h-5 w-5" />
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-navy-900">{{ __('pages.layout.app_name') }}</span>
                <span class="block truncate text-[11px] text-slate-400">{{ __('pages.layout.app_tagline') }}</span>
            </span>
        </a>
        @include('partials.locale-switcher')
    </header>

    <main id="auth-content" class="flex flex-1 items-start justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-md">
            {{ $slot }}
            <p class="mt-4 text-center text-xs leading-relaxed text-slate-400">
                {{ __('pages.layout.footer_note') }}
            </p>
        </div>
    </main>
</div>

<div id="toast-region" class="toast-region" role="status" aria-live="polite" aria-atomic="false"></div>

@stack('scripts')
</body>
</html>
