@extends('layouts.app')

@section('title', __('citizen.documents.title'))
@section('page-title', __('citizen.documents.title'))
@section('page-sub', __('citizen.documents.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.documents.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('citizen.documents.subtitle') }}</p>
        </div>
        <a href="{{ route('citizen.documents.create') }}" class="btn-primary">
            {{ __('citizen.documents.upload') }}
        </a>
    </div>

    <div class="alert alert-warning">
        <x-icon name="lock" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.documents.privacy_note') }}</p>
    </div>

    @if ($documents->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="document" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.documents.empty') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.documents.empty_hint') }}</p>
            </div>
        </section>
    @else
        <section class="card">
            <div class="card-body pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="py-3 pe-3">{{ __('citizen.documents.column_type') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.documents.column_size') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.documents.column_status') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.documents.column_record') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.documents.column_uploaded') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($documents as $document)
                                <tr>
                                    <td class="py-3 pe-3 font-semibold text-slate-700">
                                        {{ __('citizen.documents.types.'.$document->document_type) }}
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">{{ $document->humanSize() }}</td>
                                    <td class="py-3 pe-3">
                                        <span class="badge badge-slate">
                                            {{ __('citizen.documents.review_status_'.$document->review_status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">
                                        @if ($document->landRecord)
                                            {{ $document->landRecord->khatian_no }} / {{ $document->landRecord->dag_no }}
                                        @else
                                            {{ __('citizen.common.none') }}
                                        @endif
                                    </td>
                                    <td class="py-3 pe-3 text-slate-500">
                                        {{ $document->created_at?->diffForHumans() }}
                                    </td>
                                    <td class="py-3 text-right">
                                        {{-- No public URL: this is an authorized, auditable route. --}}
                                        <a href="{{ route('citizen.documents.download', $document) }}"
                                           class="btn-ghost btn-sm border-slate-200">
                                            {{ __('citizen.documents.download') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($documents->hasPages())
                    <div class="pt-4">{{ $documents->links() }}</div>
                @endif
            </div>
        </section>
    @endif

    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="info-circle" class="mx-auto h-8 w-8 text-slate-300" />
            <p class="text-sm leading-relaxed text-slate-500">{{ __('citizen.documents.evidence_note') }}</p>
        </div>
    </section>
</div>
@endsection
