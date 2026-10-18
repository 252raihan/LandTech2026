@extends('layouts.app')

@section('title', __('citizen.listings.title'))
@section('page-title', $listing->title ?? __('citizen.listings.title'))
@section('page-sub', $listing->reference_no)

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <x-status-badge :status="$listing->status" />
                <span class="badge badge-{{ $listing->isVerified() ? 'green' : 'amber' }}">
                    {{ __('citizen.listings.verification_'.$listing->verification_status) }}
                </span>
            </div>
            <h1 class="mt-3 text-xl font-bold text-navy-900">{{ $listing->title }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $listing->reference_no }} ·
                {{ collect([$listing->mouza_name, $listing->upazila_name, $listing->district_name])->filter()->implode(', ') ?: __('citizen.common.none') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $listing)
                <a href="{{ route('citizen.listings.edit', $listing) }}" class="btn-ghost btn-sm border-slate-200">
                    {{ __('citizen.common.edit') }}
                </a>
            @endcan

            @can('submit', $listing)
                <form method="POST" action="{{ route('citizen.listings.submit', $listing) }}">
                    @csrf
                    <button type="submit" class="btn-primary btn-sm">{{ __('citizen.listings.submit_review') }}</button>
                </form>
            @endcan

            @can('withdraw', $listing)
                <form method="POST" action="{{ route('citizen.listings.withdraw', $listing) }}">
                    @csrf
                    <button type="submit" class="btn-ghost btn-sm border-amber-200 text-amber-700"
                            onclick="return confirm('{{ __('citizen.listings.withdraw_confirm') }}')">
                        {{ __('citizen.listings.withdraw') }}
                    </button>
                </form>
            @endcan

            @can('delete', $listing)
                <form method="POST" action="{{ route('citizen.listings.destroy', $listing) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-ghost btn-sm border-red-200 text-red-600">
                        {{ __('citizen.listings.delete') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    {{-- The unverified label is not conditional on status: even a published
         listing carries it until a reviewer sets verification. --}}
    @unless ($listing->isVerified())
        <div class="alert alert-warning">
            <x-icon name="warning" class="h-5 w-5 shrink-0" />
            <p class="min-w-0 flex-1">{{ __('citizen.listings.unverified_warning') }}</p>
        </div>
    @endunless

    <section class="card p-6">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            @foreach ([
                ['citizen.listings.reference', $listing->reference_no],
                ['citizen.land.khatian_dag', ($listing->khatian_no ?? '—').' / '.($listing->dag_no ?? '—')],
                ['citizen.land.field_survey_type', $listing->survey_type],
                ['citizen.listings.field_area', $listing->area ? $listing->area.' '.$listing->area_unit : null],
                ['citizen.listings.asking_price', $listing->asking_price
                    ? number_format((float) $listing->asking_price).' '.$listing->price_unit : null],
                ['citizen.listings.field_sale_type', $listing->sale_type === 'partial'
                    ? __('citizen.listings.sale_partial') : __('citizen.listings.sale_full_plot')],
                ['citizen.listings.field_share_numerator', $listing->shareLabel()],
                ['citizen.listings.field_contact', __('citizen.listings.contact_'.$listing->contact_preference)],
                ['citizen.listings.published_on', $listing->published_at?->format('Y-m-d')],
            ] as [$labelKey, $value])
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __($labelKey) }}</dt>
                    <dd class="mt-1 text-slate-700">{{ $value ?: __('citizen.common.none') }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($listing->public_description)
            <div class="mt-6 border-t border-slate-200 pt-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ __('citizen.listings.field_description') }}
                </dt>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $listing->public_description }}</p>
            </div>
        @endif
    </section>

    {{-- Buyer enquiries against this listing. --}}
    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.interest.received_title') }}</h2>

        @if ($listing->interestRequests->isEmpty())
            <div class="empty-state mt-4">
                <x-icon name="flag" class="mx-auto h-8 w-8 text-slate-300" />
                <p class="text-sm text-slate-500">{{ __('citizen.interest.received_empty') }}</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($listing->interestRequests as $interest)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-700">{{ $interest->reference_no }}</p>
                            <p class="text-xs text-slate-400">{{ $interest->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$interest->status" />
                            <a href="{{ route('citizen.interest.show', $interest) }}"
                               class="btn-ghost btn-sm border-slate-200">{{ __('citizen.common.view') }}</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
