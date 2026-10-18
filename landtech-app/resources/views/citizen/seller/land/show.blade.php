@extends('layouts.app')

@section('title', __('citizen.land.reference'))
@section('page-title', __('citizen.land.title'))
@section('page-sub', __('citizen.land.subtitle'))

@section('content')
<div class="space-y-6">

    @if (session('status'))
        <div class="alert alert-warning">
            <x-icon name="check-circle" class="h-5 w-5 shrink-0" />
            <p class="min-w-0 flex-1">
                {{ __('citizen.land.'.str_replace('-', '_', session('status'))) }}
            </p>
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <x-status-badge :status="$record->status" />
                <span class="badge badge-slate">{{ $record->intended_use === 'sale_listing'
                    ? __('citizen.land.intent_sale_listing')
                    : __('citizen.land.intent_record_keeping') }}</span>
            </div>
            <h1 class="mt-3 text-xl font-bold text-navy-900">
                {{ $record->khatian_no }} / {{ $record->dag_no }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ collect([$record->mouza_name, $record->upazila_name, $record->district_name])->filter()->implode(', ') ?: __('citizen.common.none') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $record)
                <a href="{{ route('citizen.land.edit', $record) }}" class="btn-ghost btn-sm border-slate-200">
                    {{ __('citizen.common.edit') }}
                </a>
            @endcan

            @can('submit', $record)
                <form method="POST" action="{{ route('citizen.land.submit', $record) }}">
                    @csrf
                    <button type="submit" class="btn-primary btn-sm"
                            onclick="return confirm('{{ __('citizen.land.submit_confirm') }}')">
                        {{ __('citizen.land.submit_review') }}
                    </button>
                </form>
            @endcan

            @can('delete', $record)
                <form method="POST" action="{{ route('citizen.land.destroy', $record) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-ghost btn-sm border-red-200 text-red-600"
                            onclick="return confirm('{{ __('citizen.land.delete_confirm') }}')">
                        {{ __('citizen.land.delete_draft') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Reviewer outcome, when one exists. --}}
    @if ($record->review_note)
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.verification.review_note') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $record->review_note }}</p>
            @if ($record->reviewer)
                <p class="mt-2 text-xs text-slate-400">
                    {{ __('citizen.verification.reviewed_by') }}: {{ $record->reviewer->name }}
                    @if ($record->reviewed_at)
                        · {{ $record->reviewed_at->diffForHumans() }}
                    @endif
                </p>
            @endif
        </section>
    @endif

    {{-- ============ DETAILS ============ --}}
    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.land.section_identifiers') }}</h2>

        <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            @foreach ([
                ['citizen.land.field_survey_type', $record->survey_type],
                ['citizen.land.field_khatian_no', $record->khatian_no],
                ['citizen.land.field_dag_no', $record->dag_no],
                ['citizen.land.field_area', $record->area ? $record->area.' '.$record->area_unit : null],
                ['citizen.land.field_jl_no', $record->jl_no],
                ['citizen.land.share', $record->shareLabel()],
            ] as [$labelKey, $value])
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __($labelKey) }}</dt>
                    <dd class="mt-1 text-slate-700">{{ $value ?: __('citizen.common.none') }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.land.section_ownership') }}</h2>

        <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('citizen.land.basis') }}</dt>
                <dd class="mt-1 text-slate-700">
                    {{ $record->ownership_basis ? __('citizen.land.basis_'.$record->ownership_basis) : __('citizen.common.none') }}
                    @if ($record->ownership_basis_other)
                        — {{ $record->ownership_basis_other }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('citizen.land.share') }}</dt>
                <dd class="mt-1 text-slate-700">{{ $record->shareLabel() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('citizen.land.field_deed_no') }}</dt>
                <dd class="mt-1 text-slate-700">{{ $record->deed_no ?: __('citizen.common.none') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('citizen.land.field_mutation_status') }}</dt>
                <dd class="mt-1 text-slate-700">
                    {{ $record->mutation_status ? __('citizen.land.mutation_'.$record->mutation_status) : __('citizen.common.none') }}
                </dd>
            </div>
        </dl>
    </section>

    {{-- ============ DOCUMENTS ============ --}}
    <section class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="card-title">{{ __('citizen.documents.title') }}</h2>
            <a href="{{ route('citizen.documents.create', ['land_record_id' => $record->id]) }}"
               class="btn-ghost btn-sm border-slate-200">
                {{ __('citizen.documents.upload') }}
            </a>
        </div>

        @if ($record->documents->isEmpty())
            <div class="empty-state mt-4">
                <x-icon name="document" class="mx-auto h-8 w-8 text-slate-300" />
                <p class="text-sm text-slate-500">{{ __('citizen.documents.empty') }}</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($record->documents as $document)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-700">
                                {{ __('citizen.documents.types.'.$document->document_type) }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ $document->humanSize() }} · {{ $document->created_at?->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-slate">
                                {{ __('citizen.documents.review_status_'.$document->review_status) }}
                            </span>
                            <a href="{{ route('citizen.documents.download', $document) }}"
                               class="btn-ghost btn-sm border-slate-200">
                                {{ __('citizen.documents.download') }}
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- ============ LISTINGS ============ --}}
    @if ($record->listings->isNotEmpty())
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.listings.title') }}</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($record->listings as $listing)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-700">{{ $listing->title }}</p>
                            <p class="text-xs text-slate-400">{{ $listing->reference_no }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$listing->status" />
                            <a href="{{ route('citizen.listings.show', $listing) }}"
                               class="btn-ghost btn-sm border-slate-200">{{ __('citizen.common.view') }}</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="info-circle" class="mx-auto h-8 w-8 text-slate-300" />
            <p class="text-sm leading-relaxed text-slate-500">{{ __('citizen.land.claim_warning') }}</p>
        </div>
    </section>
</div>
@endsection
