@extends('layouts.app')

@section('title', __('citizen.listings.title'))
@section('page-title', __('citizen.listings.title'))
@section('page-sub', __('citizen.listings.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.listings.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('citizen.listings.subtitle') }}</p>
        </div>
        <a href="{{ route('citizen.listings.create') }}" class="btn-primary">{{ __('citizen.listings.create') }}</a>
    </div>

    <div class="alert alert-warning">
        <x-icon name="warning" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.listings.unverified_warning') }}</p>
    </div>

    @if ($listings->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="banknotes" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.listings.empty') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.listings.empty_hint') }}</p>
            </div>
        </section>
    @else
        <section class="card">
            <div class="card-body pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="py-3 pe-3">{{ __('citizen.listings.reference') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.listings.status_label') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.listings.verification_label') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.listings.asking_price') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.listings.interest_count') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($listings as $listing)
                                <tr>
                                    <td class="py-3 pe-3">
                                        <p class="font-semibold text-slate-700">{{ $listing->title }}</p>
                                        <p class="text-xs text-slate-400">{{ $listing->reference_no }}</p>
                                    </td>
                                    <td class="py-3 pe-3"><x-status-badge :status="$listing->status" /></td>
                                    <td class="py-3 pe-3">
                                        {{-- Verification is reviewer-controlled and is always shown,
                                             so an unverified listing can never look verified. --}}
                                        <span class="badge badge-{{ $listing->isVerified() ? 'green' : 'amber' }}">
                                            {{ __('citizen.listings.verification_'.$listing->verification_status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">
                                        {{ $listing->asking_price
                                            ? number_format((float) $listing->asking_price).' '.$listing->price_unit
                                            : __('citizen.common.none') }}
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">{{ $listing->interest_requests_count }}</td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('citizen.listings.show', $listing) }}"
                                           class="btn-ghost btn-sm border-slate-200">{{ __('citizen.common.view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($listings->hasPages())
                    <div class="pt-4">{{ $listings->links() }}</div>
                @endif
            </div>
        </section>
    @endif

    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="info-circle" class="mx-auto h-8 w-8 text-slate-300" />
            <p class="text-sm leading-relaxed text-slate-500">{{ __('citizen.listings.published_only_note') }}</p>
        </div>
    </section>
</div>
@endsection
