@extends('layouts.app')

@section('title', $listing->title)
@section('page-title', $listing->title)
@section('page-sub', __('citizen.search.source_label'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="badge badge-{{ $listing->isVerified() ? 'green' : 'amber' }}">
                {{ __('citizen.listings.verification_'.$listing->verification_status) }}
            </span>
            <h1 class="mt-3 text-xl font-bold text-navy-900">{{ $listing->title }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ collect([$listing->mouza_name, $listing->upazila_name, $listing->district_name])->filter()->implode(', ') ?: __('citizen.common.none') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('citizen.verification.create', ['listing_id' => $listing->id]) }}"
               class="btn-ghost btn-sm border-slate-200">
                {{ __('citizen.verification.create') }}
            </a>

            @if ($isOwner)
                <a href="{{ route('citizen.listings.show', $listing->id) }}" class="btn-ghost btn-sm border-slate-200">
                    {{ __('citizen.common.edit') }}
                </a>
            @elseif (! $alreadyInterested)
                <form method="POST" action="{{ route('citizen.interest.store', $listing->id) }}">
                    @csrf
                    <button type="submit" class="btn-primary btn-sm">{{ __('citizen.interest.express') }}</button>
                </form>
            @else
                <a href="{{ route('citizen.interest.index') }}" class="btn-ghost btn-sm border-slate-200">
                    {{ __('citizen.interest.title') }}
                </a>
            @endif
        </div>
    </div>

    @unless ($listing->isVerified())
        <div class="alert alert-warning">
            <x-icon name="warning" class="h-5 w-5 shrink-0" />
            <p class="min-w-0 flex-1">{{ __('citizen.listings.unverified_warning') }}</p>
        </div>
    @endunless

    <div class="alert alert-warning">
        <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.search.not_official_note') }}</p>
    </div>

    <section class="card p-6">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            @foreach ([
                ['citizen.land.field_survey_type', $listing->survey_type],
                ['citizen.land.field_khatian_no', $listing->khatian_no],
                ['citizen.land.field_dag_no', $listing->dag_no],
                ['citizen.land.field_jl_no', $listing->jl_no],
                ['citizen.land.area', $listing->area ? $listing->area.' '.$listing->area_unit : null],
                ['citizen.search.share', $listing->shareLabel()],
                ['citizen.listings.asking_price', $listing->asking_price
                    ? number_format((float) $listing->asking_price).' '.$listing->price_unit : null],
                ['citizen.listings.field_sale_type', $listing->sale_type === 'partial'
                    ? __('citizen.listings.sale_partial') : __('citizen.listings.sale_full_plot')],
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
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $listing->public_description }}</p>
            </div>
        @endif
    </section>

    {{-- Expressing interest: a separate, explicit action with its own warning. --}}
    @unless ($isOwner)
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.interest.express') }}</h2>

            @if ($alreadyInterested)
                <div class="empty-state mt-4">
                    <x-icon name="flag" class="mx-auto h-8 w-8 text-slate-300" />
                    <p class="text-sm text-slate-500">{{ __('citizen.interest.already_raised') }}</p>
                </div>
            @else
                <form method="POST" action="{{ route('citizen.interest.store', $listing->id) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="message" :value="__('citizen.interest.field_message')" />
                        <textarea id="message" name="message" rows="3" class="input mt-1 w-full"
                                  maxlength="2000">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn-primary">{{ __('citizen.interest.submit') }}</button>
                </form>
            @endif

            <p class="mt-4 border-t border-slate-200 pt-4 text-xs leading-relaxed text-slate-400">
                {{ __('citizen.interest.not_transfer_warning') }}
            </p>
            <p class="mt-2 text-xs leading-relaxed text-slate-400">
                {{ __('citizen.interest.privacy_note') }}
            </p>
        </section>
    @endunless
</div>
@endsection
