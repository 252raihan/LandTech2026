@extends('layouts.app')

@section('title', __('citizen.land.title'))
@section('page-title', __('citizen.land.title'))
@section('page-sub', __('citizen.land.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.land.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('citizen.land.subtitle') }}</p>
        </div>
        <a href="{{ route('citizen.land.create') }}" class="btn-primary">
            {{ __('citizen.land.add') }}
        </a>
    </div>

    @unless ($hasVerifiedLink)
        <div class="alert alert-warning">
            <x-icon name="warning" class="h-5 w-5 shrink-0" />
            <p class="min-w-0 flex-1">{{ __('citizen.land.no_verified_link_note') }}</p>
            <a href="{{ route('citizen.link.index') }}" class="btn-ghost btn-sm shrink-0">
                {{ __('citizen.link.heading') }}
            </a>
        </div>
    @endunless

    @if ($records->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="map" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.land.empty') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.land.empty_hint') }}</p>
            </div>
        </section>
    @else
        <section class="card">
            <div class="card-body pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="py-3 pe-3">{{ __('citizen.land.location') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.land.khatian_dag') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.land.share') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.land.status_label') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.land.documents_count') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($records as $record)
                                <tr>
                                    <td class="py-3 pe-3">
                                        <p class="font-semibold text-slate-700">
                                            {{ $record->mouza_name ?: ($record->mouza?->name_en ?? '—') }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ collect([$record->district_name, $record->upazila_name])->filter()->implode(', ') ?: '—' }}
                                        </p>
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">
                                        {{ $record->khatian_no }} / {{ $record->dag_no }}
                                        @if ($record->survey_type)
                                            <span class="badge badge-slate ms-1">{{ $record->survey_type }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">{{ $record->shareLabel() }}</td>
                                    <td class="py-3 pe-3">
                                        <x-status-badge :status="$record->status" />
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">{{ $record->documents->count() }}</td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('citizen.land.show', $record) }}"
                                           class="btn-ghost btn-sm border-slate-200">
                                            {{ __('citizen.land.reference') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($records->hasPages())
                    <div class="pt-4">{{ $records->links() }}</div>
                @endif
            </div>
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
