@extends('layouts.app')

@section('title', __('citizen.modes.title'))
@section('page-title', __('citizen.modes.title'))
@section('page-sub', __('citizen.modes.subtitle'))

@section('content')
<div class="space-y-6">

    {{-- Welcome + honest account status --}}
    <section class="card p-6">
        <h1 class="text-xl font-bold text-navy-900">
            {{ __('citizen.modes.welcome', ['name' => $user->name]) }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('citizen.modes.subtitle') }}</p>

        {{-- Ownership-link status is stated plainly: without a verified link no
             land records can be attributed to this account. --}}
        <div class="mt-4">
            @if ($hasVerifiedLink)
                <span class="badge badge-green">
                    <x-icon name="check-circle" class="h-3.5 w-3.5" />
                    {{ __('citizen.modes.link_verified') }}
                </span>
            @elseif ($pendingLink)
                <span class="badge badge-amber">
                    <x-icon name="clock" class="h-3.5 w-3.5" />
                    {{ __('citizen.modes.link_pending') }}
                </span>
            @else
                <span class="badge badge-slate">
                    <x-icon name="warning" class="h-3.5 w-3.5" />
                    {{ __('citizen.modes.link_absent') }}
                </span>
            @endif
        </div>

        @unless ($hasVerifiedLink)
            <div class="alert alert-warning mt-4">
                <x-icon name="warning" class="h-5 w-5 shrink-0" />
                <p class="min-w-0 flex-1">{{ __('citizen.modes.link_explainer') }}</p>
                <a href="{{ route('citizen.link.index') }}" class="btn-ghost btn-sm shrink-0">
                    {{ __('citizen.link.heading') }}
                </a>
            </div>
        @endunless
    </section>

    {{-- Summary counters — every value is a real count, or an honest zero --}}
    <div class="grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card">
            <span class="stat-icon bg-blue-50 text-blue-700"><x-icon name="map" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.modes.card_land_records') }}</p>
                <p class="stat-value">{{ $landRecordCount }}</p>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon bg-amber-50 text-amber-700"><x-icon name="clock" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.modes.card_pending_verification') }}</p>
                <p class="stat-value">{{ $pendingVerificationCount }}</p>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon bg-emerald-50 text-emerald-700"><x-icon name="banknotes" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.modes.card_published_listings') }}</p>
                <p class="stat-value">{{ $publishedListingCount }}</p>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon bg-indigo-50 text-indigo-700"><x-icon name="search" /></span>
            <div class="min-w-0">
                <p class="stat-label">{{ __('citizen.modes.card_open_verifications') }}</p>
                <p class="stat-value">{{ $openVerificationCount }}</p>
            </div>
        </div>
    </div>

    {{-- ============ THE TWO MODE CARDS ============ --}}
    <div class="grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- Landowner / Seller --}}
        <section class="card p-6">
            <div class="flex items-start justify-between gap-3">
                <span class="stat-icon bg-emerald-50 text-emerald-700"><x-icon name="map" /></span>
                @if ($activeMode === 'seller')
                    <span class="badge badge-green">{{ __('citizen.modes.active') }}</span>
                @endif
            </div>

            <h2 class="mt-4 text-lg font-bold text-navy-900">{{ __('citizen.modes.seller_title') }}</h2>
            <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ __('citizen.modes.seller_desc') }}</p>

            <ul class="mt-4 space-y-2 text-sm">
                @foreach ([
                    ['citizen.land.index', __('citizen.nav.my_land')],
                    ['citizen.land.create', __('citizen.nav.add_land')],
                    ['citizen.documents.index', __('citizen.nav.my_documents')],
                    ['citizen.link.index', __('citizen.nav.ownership_info')],
                    ['citizen.listings.index', __('citizen.nav.my_listings')],
                    ['citizen.interest.received', __('citizen.nav.buyer_interest')],
                ] as [$route, $label])
                    <li>
                        <a href="{{ route($route) }}" class="flex items-center gap-2 text-slate-600 hover:text-brand-700">
                            <x-icon name="arrow-right" class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('citizen.mode.switch') }}" class="mt-5">
                @csrf
                <input type="hidden" name="mode" value="seller">
                <button type="submit" class="btn-primary w-full justify-center">
                    {{ __('citizen.modes.enter_seller') }}
                </button>
            </form>
        </section>

        {{-- Land Buyer --}}
        <section class="card p-6">
            <div class="flex items-start justify-between gap-3">
                <span class="stat-icon bg-indigo-50 text-indigo-700"><x-icon name="search" /></span>
                @if ($activeMode === 'buyer')
                    <span class="badge badge-green">{{ __('citizen.modes.active') }}</span>
                @endif
            </div>

            <h2 class="mt-4 text-lg font-bold text-navy-900">{{ __('citizen.modes.buyer_title') }}</h2>
            <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ __('citizen.modes.buyer_desc') }}</p>

            <ul class="mt-4 space-y-2 text-sm">
                @foreach ([
                    ['citizen.find', __('citizen.nav.find_land')],
                    ['citizen.find', __('citizen.nav.search_by_location')],
                    ['citizen.find', __('citizen.nav.search_by_khatian')],
                    ['citizen.verification.create', __('citizen.nav.request_verification')],
                    ['citizen.verification.index', __('citizen.nav.verification_reports')],
                    ['citizen.interest.index', __('citizen.nav.my_interest')],
                ] as [$route, $label])
                    <li>
                        <a href="{{ route($route) }}" class="flex items-center gap-2 text-slate-600 hover:text-brand-700">
                            <x-icon name="arrow-right" class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('citizen.mode.switch') }}" class="mt-5">
                @csrf
                <input type="hidden" name="mode" value="buyer">
                <button type="submit" class="btn-ghost w-full justify-center border-slate-200">
                    {{ __('citizen.modes.enter_buyer') }}
                </button>
            </form>
        </section>
    </div>

    {{-- Honest statement of what the two modes are and are not. --}}
    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="info-circle" class="mx-auto h-8 w-8 text-slate-300" />
            <p class="text-sm leading-relaxed text-slate-500">{{ __('citizen.modes.mode_notice') }}</p>
        </div>
    </section>
</div>
@endsection
