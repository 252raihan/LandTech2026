@extends('layouts.app')

@section('title', __('citizen.documents.title'))
@section('page-title', __('citizen.documents.types.'.$document->document_type))
@section('page-sub', __('citizen.documents.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <span class="badge badge-slate">
                {{ __('citizen.documents.review_status_'.$document->review_status) }}
            </span>
            <h1 class="mt-3 text-xl font-bold text-navy-900">
                {{ __('citizen.documents.types.'.$document->document_type) }}
            </h1>
            {{-- Only the sanitized display name is shown. The stored path is never
                 rendered, because it is internal storage detail. --}}
            <p class="mt-1 truncate text-sm text-slate-500">
                {{ $document->original_name ?: __('citizen.common.none') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('citizen.documents.download', $document) }}" class="btn-primary btn-sm">
                {{ __('citizen.documents.download') }}
            </a>

            @can('delete', $document)
                <form method="POST" action="{{ route('citizen.documents.destroy', $document) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-ghost btn-sm border-red-200 text-red-600"
                            onclick="return confirm('{{ __('citizen.documents.delete_confirm') }}')">
                        {{ __('citizen.documents.delete') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <section class="card p-6">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            @foreach ([
                ['citizen.documents.column_type', __('citizen.documents.types.'.$document->document_type)],
                ['citizen.documents.column_size', $document->humanSize()],
                ['citizen.documents.column_uploaded', $document->created_at?->format('Y-m-d H:i')],
                ['citizen.documents.column_status', __('citizen.documents.review_status_'.$document->review_status)],
                ['citizen.documents.column_record', $document->landRecord
                    ? $document->landRecord->khatian_no.' / '.$document->landRecord->dag_no
                    : __('citizen.common.none')],
                ['citizen.documents.column_file', $document->mime_type],
            ] as [$labelKey, $value])
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __($labelKey) }}</dt>
                    <dd class="mt-1 text-slate-700">{{ $value ?: __('citizen.common.none') }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    @if ($document->review_note)
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.verification.review_note') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $document->review_note }}</p>
        </section>
    @endif

    @if ($document->replacement)
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.documents.review_status_replacement_requested') }}</h2>
            <a href="{{ route('citizen.documents.show', $document->replacement) }}"
               class="mt-2 inline-block text-sm font-semibold text-brand-700 underline">
                {{ __('citizen.documents.types.'.$document->replacement->document_type) }}
            </a>
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
