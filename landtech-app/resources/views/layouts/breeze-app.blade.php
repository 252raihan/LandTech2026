<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($header) ? trim(strip_tags($header)) : config('app.name', 'LandTech 2026') }} · {{ __('pages.layout.app_tagline') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=hind-siliguri:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.locale-bootstrap')
</head>
<body class="min-h-screen">
<a class="skip-link" href="#main-content">{{ __('pages.layout.skip_to_content') }}</a>

<div class="border-b border-amber-200 bg-amber-50 px-4 py-1.5 text-center text-[11px] font-medium leading-relaxed text-amber-900">
    {{ __('pages.layout.disclaimer', ['strong' => __('pages.layout.disclaimer_strong')]) }}
</div>

<div class="lg:flex">
    <div id="nav-backdrop" class="app-backdrop" aria-hidden="true"></div>

    @include('partials.app-sidebar')

    <div class="flex min-h-screen min-w-0 flex-1 flex-col bg-slate-100">
        @include('partials.app-header')

        <main id="main-content" class="mx-auto w-full max-w-[90rem] flex-1 px-4 py-6 lg:px-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-white px-4 py-4 text-center text-xs text-slate-400 lg:hidden">
            {{ __('pages.layout.footer_note') }}
        </footer>
    </div>
</div>

<div id="toast-region" class="toast-region" role="status" aria-live="polite" aria-atomic="false"></div>
<dialog id="ui-dialog" class="app-dialog"></dialog>

@stack('scripts')
</body>
</html>
