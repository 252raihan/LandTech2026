@extends('layouts.app')

@section('title', __('citizen.dashboard.title'))

@section('page-title', __('citizen.dashboard.title'))
@section('page-sub', __('citizen.dashboard.subtitle'))

@section('content')
<div class="space-y-6">
    {{-- A. Welcome + verification status --}}
    <section class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-900">
                    {{ __('citizen.dashboard.welcome', ['name' => $user->name]) }}                </h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('citizen.dashboard.subtitle') }}</p>
            </div>

            <span class="badge {{ $emailVerified ? 'badge-green' : 'badge-amber' }}">
                <x-icon :name="$emailVerified ? 'check-circle' : 'warning'" class="h-4 w-4" />
                {{ $emailVerified ? __('citizen.dashboard.verified') : __('citizen.dashboard.unverified') }}
            </span>
        </div>

        @unless ($emailVerified)
            <div class="alert alert-warning mt-4">
                <x-icon name="warning" class="h-5 w-5 shrink-0" />
                <p class="min-w-0 flex-1">{{ __('citizen.dashboard.verify_hint') }}</p>
                <a href="{{ route('verification.notice') }}" class="btn-ghost btn-sm shrink-0">
                    {{ __('citizen.dashboard.verified') }}
                </a>
            </div>
        @endunless
    </section>

    {{-- B. Summary cards — every value is real or an honest zero --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card">
            <span class="stat-icon bg-blue-50 text-blue-700"><x-icon name="map" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.dashboard.card_plots') }}</p>
                <p class="stat-value">{{ $plotCount }}</p>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-indigo-50 text-indigo-700"><x-icon name="document" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.dashboard.card_ownership') }}</p>
                <p class="stat-value">{{ $ownershipCount }}</p>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-amber-50 text-amber-700"><x-icon name="document" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.dashboard.card_applications') }}</p>
                <p class="stat-value">{{ $applicationCount }}</p>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-brand-50 text-brand-700"><x-icon name="bell" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.dashboard.card_notifications') }}</p>
                <p class="stat-value">{{ $unreadNotificationCount }}</p>
            </div>
        </div>
    </div>

    {{-- C. Recent activity + Quick links --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2" aria-labelledby="activity-title">
            <div class="card-header">
                <h2 id="activity-title" class="card-title">{{ __('citizen.dashboard.recent_activity') }}</h2>
            </div>
            <div class="card-body pt-0">
                @if ($recentActivity->isEmpty())
                    <div class="empty-state">
                        <x-icon name="inbox" class="mx-auto h-8 w-8 text-slate-300" />
                        <p>{{ __('citizen.dashboard.no_activity') }}</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($recentActivity as $item)
                            <li class="py-3 text-sm text-slate-700">{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section class="card" aria-labelledby="quick-links-title">
            <div class="card-header">
                <h2 id="quick-links-title" class="card-title">{{ __('citizen.dashboard.quick_links') }}</h2>
            </div>
            <div class="card-body space-y-3">
                <a href="{{ route('citizen.land.index') }}" class="menu-item border border-slate-200">
                    <x-icon name="map" class="h-4 w-4 text-blue-600" />
                    <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">
                        {{ __('citizen.nav.my_land') }}
                    </span>
                    <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" />
                </a>
                <a href="{{ route('citizen.documents.index') }}" class="menu-item border border-slate-200">
                    <x-icon name="document" class="h-4 w-4 text-indigo-600" />
                    <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">
                        {{ __('citizen.nav.my_deeds') }}
                    </span>
                    <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" />
                </a>
                <a href="{{ route('citizen.listings.index') }}" class="menu-item border border-slate-200">
                    <x-icon name="document" class="h-4 w-4 text-amber-600" />
                    <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">
                        {{ __('citizen.nav.my_applications') }}
                    </span>
                    <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" />
                </a>
            </div>
        </section>
    </div>

    {{-- D. Honest empty state for linked records --}}
    @if ($plotCount === 0 && $ownershipCount === 0)
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="map" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.dashboard.no_plots_yet') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.dashboard.no_plots_hint') }}</p>
            </div>
        </section>
    @endif
</div>
@endsection
