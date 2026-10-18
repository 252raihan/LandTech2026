@extends('layouts.app')

@php
    $resultColours = [
        'pass' => 'green',
        'fail' => 'red',
        'inconclusive' => 'amber',
        'not_available' => 'slate',
        'pending' => 'blue',
    ];
@endphp

@section('title', __('citizen.verification.title'))
@section('page-title', $request->reference_no)
@section('page-sub', __('citizen.verification.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <x-status-badge :status="$request->status" />
                {{-- The official-verification state is always shown, and is never
                     "verified", because no government integration exists. --}}
                <span class="badge badge-slate">{{ __('citizen.verification.official_not_available') }}</span>
            </div>
            <h1 class="mt-3 text-xl font-bold text-navy-900">{{ $request->reference_no }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ collect([$request->mouza_name, $request->upazila_name, $request->district_name])->filter()->implode(', ') ?: __('citizen.common.none') }}
            </p>
        </div>

        @if ($request->isOpen())
            <form method="POST" action="{{ route('citizen.verification.checks', $request->id) }}">
                @csrf
                <button type="submit" class="btn-ghost btn-sm border-slate-200">
                    {{ __('citizen.verification.checks_refreshed') }}
                </button>
            </form>
        @endif
    </div>

    {{-- ============ THE HONESTY BANNER ============ --}}
    <div class="alert alert-warning">
        <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
        <div class="min-w-0 flex-1 space-y-1">
            <p>{{ __('citizen.verification.official_note') }}</p>
            <p class="text-xs">{{ __('citizen.verification.limitation.overall') }}</p>
        </div>
    </div>

    {{-- ============ LAND REFERENCE ============ --}}
    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.verification.land') }}</h2>

        <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            @foreach ([
                ['citizen.verification.field_district', $request->district_name],
                ['citizen.verification.field_upazila', $request->upazila_name],
                ['citizen.verification.field_mouza', $request->mouza_name],
                ['citizen.verification.field_jl_no', $request->jl_no],
                ['citizen.verification.field_survey_type', $request->survey_type],
                ['citizen.verification.field_khatian_no', $request->khatian_no],
                ['citizen.verification.field_dag_no', $request->dag_no],
                ['citizen.verification.field_area', $request->area],
                ['citizen.verification.field_deed_no', $request->deed_no],
            ] as [$labelKey, $value])
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __($labelKey) }}</dt>
                    <dd class="mt-1 text-slate-700">{{ $value ?: __('citizen.common.none') }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-6 border-t border-slate-200 pt-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {{ __('citizen.verification.requester_note') }}
            </dt>
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $request->reason }}</p>
        </div>
    </section>

    {{-- ============ RISK INDICATOR ============ --}}
    @if ($request->risk_indicator !== null)
        <section class="card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="card-title">{{ __('citizen.verification.risk_label') }}</h2>
                <span class="stat-value">{{ $request->risk_indicator }}</span>
            </div>

            <p class="mt-2 text-sm text-slate-600">
                @if ($request->risk_indicator < 20)
                    {{ __('citizen.verification.risk_low') }}
                @elseif ($request->risk_indicator < 50)
                    {{ __('citizen.verification.risk_moderate') }}
                @else
                    {{ __('citizen.verification.risk_elevated') }}
                @endif
            </p>

            {{-- A score is a signal, never a finding. --}}
            <p class="mt-3 border-t border-slate-200 pt-3 text-xs leading-relaxed text-slate-400">
                {{ __('citizen.verification.risk_note') }}
            </p>
        </section>
    @endif

    {{-- ============ CHECKS ============ --}}
    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.verification.checks_heading') }}</h2>

        @if ($request->checks->isEmpty())
            <div class="empty-state mt-4">
                <x-icon name="shield-check" class="mx-auto h-8 w-8 text-slate-300" />
                <p class="text-sm text-slate-500">{{ __('citizen.verification.check_result_pending') }}</p>
            </div>
        @else
            <ul class="mt-4 space-y-4">
                @foreach ($request->checks as $check)
                    <li class="rounded-lg border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="text-sm font-bold text-navy-900">
                                {{ __('citizen.verification.checks.'.$check->check_code) }}
                            </p>
                            <span class="badge badge-{{ $resultColours[$check->result] ?? 'slate' }}">
                                {{ __('citizen.verification.check_result_'.$check->result) }}
                            </span>
                        </div>

                        @if ($check->detail)
                            <p class="mt-2 text-sm text-slate-600">{{ $check->detail }}</p>
                        @endif

                        {{-- The limitation is rendered ALONGSIDE every result, not
                             hidden in a footnote. --}}
                        @if ($check->limitation)
                            <p class="mt-2 text-xs leading-relaxed text-slate-400">
                                {{ $check->limitation }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- ============ OUTCOME / REVIEW ============ --}}
    @if ($request->outcome_summary || $request->reviewed_by)
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.verification.outcome_heading') }}</h2>

            @if ($request->outcome_summary)
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $request->outcome_summary }}</p>
            @endif

            @if ($request->reviewer)
                <p class="mt-3 text-xs text-slate-400">
                    {{ __('citizen.verification.reviewed_by') }}: {{ $request->reviewer->name }}
                    @if ($request->reviewed_at) · {{ $request->reviewed_at->diffForHumans() }} @endif
                </p>
            @endif

            @if ($request->review_note)
                <div class="mt-4 border-t border-slate-200 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ __('citizen.verification.review_note') }}
                    </p>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $request->review_note }}</p>
                </div>
            @endif
        </section>
    @else
        {{-- No reviewer has acted, and none can yet. State that plainly rather
             than showing an empty "outcome" box that implies progress. --}}
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.verification.reviewer_pending_title') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                {{ __('citizen.verification.reviewer_pending_body') }}
            </p>
            <p class="mt-2 text-xs text-slate-400">{{ __('citizen.review.separation_note') }}</p>
        </section>
    @endif

    {{-- ============ LIMITATIONS ============ --}}
    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.verification.limitations_heading') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('citizen.verification.limitation.overall') }}</p>

        @if ($request->outcome_limitations)
            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-500">
                {{ $request->outcome_limitations }}
            </p>
        @endif

        <div class="mt-4 border-t border-slate-200 pt-4">
            <p class="text-sm font-semibold text-slate-700">{{ __('citizen.verification.report_conflict') }}</p>
            <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ __('citizen.verification.report_note') }}</p>
        </div>
    </section>

    {{-- ============ ADDITIONAL INFORMATION ============ --}}
    @can('update', $request)
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.verification.additional_info') }}</h2>

            <form method="POST" action="{{ route('citizen.verification.update', $request->id) }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')
                <textarea name="additional_information" rows="4" class="input w-full"
                          required minlength="5" maxlength="2000"></textarea>
                <button type="submit" class="btn-primary">{{ __('citizen.verification.additional_info_submit') }}</button>
            </form>
        </section>
    @endcan
</div>
@endsection
