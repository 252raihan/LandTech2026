@extends('layouts.app')

@section('title', __('citizen.search.title'))
@section('page-title', __('citizen.search.title'))
@section('page-sub', __('citizen.search.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="min-w-0">
        <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.search.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('citizen.search.subtitle') }}</p>
    </div>

    {{-- Honest framing: these are user advertisements, not official records. --}}
    <div class="alert alert-warning">
        <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.search.not_official_note') }}</p>
    </div>

    {{-- ============ FILTERS ============ --}}
    <form method="GET" action="{{ route('citizen.find') }}" class="card p-6">
        <h2 class="card-title">{{ __('citizen.search.heading') }}</h2>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="district" :value="__('citizen.search.field_district')" />
                <x-text-input id="district" name="district" class="mt-1 block w-full"
                              :value="$filters['district'] ?? ''" />
            </div>
            <div>
                <x-input-label for="upazila" :value="__('citizen.search.field_upazila')" />
                <x-text-input id="upazila" name="upazila" class="mt-1 block w-full"
                              :value="$filters['upazila'] ?? ''" />
            </div>
            <div>
                <x-input-label for="mouza" :value="__('citizen.search.field_mouza')" />
                <x-text-input id="mouza" name="mouza" class="mt-1 block w-full"
                              :value="$filters['mouza'] ?? ''" />
            </div>
            <div>
                <x-input-label for="jl_no" :value="__('citizen.search.field_jl_no')" />
                <x-text-input id="jl_no" name="jl_no" class="mt-1 block w-full"
                              :value="$filters['jl_no'] ?? ''" />
            </div>
            <div>
                <x-input-label for="khatian_no" :value="__('citizen.search.field_khatian_no')" />
                <x-text-input id="khatian_no" name="khatian_no" class="mt-1 block w-full"
                              :value="$filters['khatian_no'] ?? ''" />
            </div>
            <div>
                <x-input-label for="dag_no" :value="__('citizen.search.field_dag_no')" />
                <x-text-input id="dag_no" name="dag_no" class="mt-1 block w-full"
                              :value="$filters['dag_no'] ?? ''" />
            </div>
            <div>
                <x-input-label for="sale_type" :value="__('citizen.search.field_sale_type')" />
                <select id="sale_type" name="sale_type" class="input mt-1 w-full">
                    <option value="">{{ __('citizen.search.any') }}</option>
                    <option value="full_plot" @selected(($filters['sale_type'] ?? '') === 'full_plot')>
                        {{ __('citizen.listings.sale_full_plot') }}
                    </option>
                    <option value="partial" @selected(($filters['sale_type'] ?? '') === 'partial')>
                        {{ __('citizen.listings.sale_partial') }}
                    </option>
                </select>
            </div>
            <div>
                <x-input-label for="verification_status" :value="__('citizen.search.field_verification')" />
                <select id="verification_status" name="verification_status" class="input mt-1 w-full">
                    <option value="">{{ __('citizen.search.any') }}</option>
                    @foreach (['unverified', 'pending', 'document_reviewed', 'verified'] as $vs)
                        <option value="{{ $vs }}" @selected(($filters['verification_status'] ?? '') === $vs)>
                            {{ __('citizen.listings.verification_'.$vs) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="min_area" :value="__('citizen.search.field_min_area')" />
                <x-text-input id="min_area" name="min_area" type="number" step="0.0001" min="0"
                              class="mt-1 block w-full" :value="$filters['min_area'] ?? ''" />
            </div>
            <div>
                <x-input-label for="max_area" :value="__('citizen.search.field_max_area')" />
                <x-text-input id="max_area" name="max_area" type="number" step="0.0001" min="0"
                              class="mt-1 block w-full" :value="$filters['max_area'] ?? ''" />
            </div>
            <div>
                <x-input-label for="min_price" :value="__('citizen.search.field_min_price')" />
                <x-text-input id="min_price" name="min_price" type="number" step="0.01" min="0"
                              class="mt-1 block w-full" :value="$filters['min_price'] ?? ''" />
            </div>
            <div>
                <x-input-label for="max_price" :value="__('citizen.search.field_max_price')" />
                <x-text-input id="max_price" name="max_price" type="number" step="0.01" min="0"
                              class="mt-1 block w-full" :value="$filters['max_price'] ?? ''" />
            </div>
        </div>

        <div class="mt-5 flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary">{{ __('citizen.search.submit') }}</button>
            <a href="{{ route('citizen.find') }}" class="btn-ghost border-slate-200">
                {{ __('citizen.search.reset') }}
            </a>
            <a href="{{ route('citizen.verification.create') }}" class="btn-ghost border-slate-200">
                {{ __('citizen.verification.create') }}
            </a>
        </div>
    </form>

    {{-- ============ RESULTS ============ --}}
    @if (! $hasSearched)
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="search" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.search.prompt') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.search.prompt_hint') }}</p>
            </div>
        </section>
    @elseif ($listings->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="search" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.search.empty') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.search.empty_hint') }}</p>
            </div>
        </section>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="card-title">{{ __('citizen.search.results') }}</h2>
            <p class="text-sm text-slate-500">
                {{ __('citizen.search.result_count', ['count' => $listings->total()]) }}
            </p>
        </div>

        <div class="grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($listings as $listing)
                <div class="card flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                        <span class="stat-icon bg-indigo-50 text-indigo-700"><x-icon name="map" /></span>
                        <span class="badge badge-{{ $listing->isVerified() ? 'green' : 'amber' }}">
                            {{ __('citizen.listings.verification_'.$listing->verification_status) }}
                        </span>
                    </div>

                    <p class="mt-3 text-base font-bold text-navy-900">{{ $listing->title }}</p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ collect([$listing->mouza_name, $listing->upazila_name, $listing->district_name])->filter()->implode(', ') ?: __('citizen.common.none') }}
                    </p>

                    <dl class="mt-3 flex-1 space-y-1 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-400">{{ __('citizen.land.khatian_dag') }}</dt>
                            <dd class="text-slate-700">{{ $listing->khatian_no ?? '—' }} / {{ $listing->dag_no ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-400">{{ __('citizen.land.area') }}</dt>
                            <dd class="text-slate-700">{{ $listing->area ? $listing->area.' '.$listing->area_unit : '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-400">{{ __('citizen.search.share') }}</dt>
                            <dd class="text-slate-700">{{ $listing->shareLabel() }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-400">{{ __('citizen.listings.asking_price') }}</dt>
                            <dd class="text-slate-700">
                                {{ $listing->asking_price
                                    ? number_format((float) $listing->asking_price).' '.$listing->price_unit
                                    : '—' }}
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-3 text-[11px] text-slate-400">{{ __('citizen.search.source_label') }}</p>

                    <a href="{{ route('citizen.find.show', $listing->id) }}"
                       class="btn-primary btn-sm mt-4 w-full justify-center">
                        {{ __('citizen.search.view') }}
                    </a>
                </div>
            @endforeach
        </div>

        @if ($listings->hasPages())
            <div>{{ $listings->links() }}</div>
        @endif
    @endif
</div>
@endsection
