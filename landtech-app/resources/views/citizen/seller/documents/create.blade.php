@extends('layouts.app')

@section('title', __('citizen.documents.upload'))
@section('page-title', __('citizen.documents.upload'))
@section('page-sub', __('citizen.documents.upload_subtitle'))

@section('content')
<div class="space-y-6">

    <div class="alert alert-warning">
        <x-icon name="lock" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.documents.privacy_note') }}</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-warning">
            <x-icon name="warning" class="h-5 w-5 shrink-0" />
            <ul class="min-w-0 flex-1 list-inside list-disc text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('citizen.documents.store') }}"
          enctype="multipart/form-data" class="space-y-6">
        @csrf

        <section class="card p-6 space-y-4">
            <div>
                <x-input-label for="document_type" :value="__('citizen.documents.field_type')" required />
                <select id="document_type" name="document_type" class="input mt-1 w-full" required>
                    <option value="">{{ __('citizen.search.any') }}</option>
                    @foreach ($documentTypes as $value => $labelKey)
                        <option value="{{ $value }}" @selected(old('document_type') === $value)>
                            {{ __($labelKey) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="land_record_id" :value="__('citizen.documents.field_attach_to')" />
                <select id="land_record_id" name="land_record_id" class="input mt-1 w-full">
                    <option value="">{{ __('citizen.documents.field_attach_none') }}</option>
                    @foreach ($records as $record)
                        <option value="{{ $record->id }}"
                            @selected((int) old('land_record_id', $landRecord?->id) === $record->id)>
                            {{ $record->khatian_no }} / {{ $record->dag_no }}
                            — {{ $record->mouza_name ?: ($record->mouza?->name_en ?? '') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="document" :value="__('citizen.documents.field_file')" required />
                <input id="document" name="document" type="file"
                       accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                       class="input mt-1 w-full" required />
                <p class="mt-1 text-xs text-slate-400">{{ __('citizen.documents.file_help') }}</p>
                <x-input-error :messages="$errors->get('document')" class="mt-2" />
            </div>
        </section>

        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="info-circle" class="mx-auto h-8 w-8 text-slate-300" />
                <p class="text-sm leading-relaxed text-slate-500">{{ __('citizen.documents.evidence_note') }}</p>
            </div>
        </section>

        <div class="flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary">{{ __('citizen.documents.upload') }}</button>
            <a href="{{ route('citizen.documents.index') }}" class="btn-ghost border-slate-200">
                {{ __('citizen.common.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
