@extends('layouts.app')

@php
    $isEdit = $record !== null;
    $old = fn (string $key, $default = null) => old($key, $isEdit ? $record->{$key} : $default);
@endphp

@section('title', $isEdit ? __('citizen.land.edit') : __('citizen.land.add'))
@section('page-title', $isEdit ? __('citizen.land.edit') : __('citizen.land.add'))
@section('page-sub', __('citizen.land.add_subtitle'))

@section('content')
<div class="space-y-6">

    <div class="alert alert-warning">
        <x-icon name="warning" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.land.claim_warning') }}</p>
    </div>

    @if (! $hasVerifiedLink)
        <div class="alert alert-warning">
            <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
            <p class="min-w-0 flex-1">{{ __('citizen.land.no_verified_link_note') }}</p>
        </div>
    @endif

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

    {{-- The form posts to store() or update(); `action` decides draft vs submit.
         Status, party_id and reviewed_by are NOT form fields: they are set
         server-side and cannot be influenced from here. --}}
    <form method="POST"
          action="{{ $isEdit ? route('citizen.land.update', $record) : route('citizen.land.store') }}"
          class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PATCH')
        @endif

        {{-- ============ LOCATION ============ --}}
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_location') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="mouza_id" :value="__('citizen.land.field_mouza_select')" />
                    <select id="mouza_id" name="mouza_id" class="input mt-1 w-full">
                        <option value="">{{ __('citizen.search.any') }}</option>
                        @foreach ($mouzas as $mouza)
                            <option value="{{ $mouza->id }}" @selected((int) $old('mouza_id') === $mouza->id)>
                                {{ $mouza->name_en }} ({{ $mouza->name_bn }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">{{ __('citizen.land.field_mouza_help') }}</p>
                </div>

                <div>
                    <x-input-label for="district_name" :value="__('citizen.land.field_district')" />
                    <x-text-input id="district_name" name="district_name" class="mt-1 block w-full"
                                  :value="$old('district_name')" />
                </div>
                <div>
                    <x-input-label for="upazila_name" :value="__('citizen.land.field_upazila')" />
                    <x-text-input id="upazila_name" name="upazila_name" class="mt-1 block w-full"
                                  :value="$old('upazila_name')" />
                </div>
                <div>
                    <x-input-label for="mouza_name" :value="__('citizen.land.field_mouza')" />
                    <x-text-input id="mouza_name" name="mouza_name" class="mt-1 block w-full"
                                  :value="$old('mouza_name')" />
                </div>
                <div>
                    <x-input-label for="jl_no" :value="__('citizen.land.field_jl_no')" />
                    <x-text-input id="jl_no" name="jl_no" class="mt-1 block w-full" :value="$old('jl_no')" />
                </div>
            </div>
        </section>

        {{-- ============ LAND IDENTIFIERS ============ --}}
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_identifiers') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="survey_type" :value="__('citizen.land.field_survey_type')" />
                    <select id="survey_type" name="survey_type" class="input mt-1 w-full">
                        <option value="">{{ __('citizen.search.any') }}</option>
                        @foreach (\App\Models\LandRecord::SURVEY_TYPES as $type)
                            <option value="{{ $type }}" @selected($old('survey_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="area_unit" :value="__('citizen.land.field_area_unit')" />
                    <select id="area_unit" name="area_unit" class="input mt-1 w-full">
                        @foreach (\App\Models\LandRecord::AREA_UNITS as $unit)
                            <option value="{{ $unit }}" @selected($old('area_unit', 'decimal') === $unit)>
                                {{ $unit }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="khatian_no" :value="__('citizen.land.field_khatian_no')" required />
                    <x-text-input id="khatian_no" name="khatian_no" class="mt-1 block w-full"
                                  :value="$old('khatian_no')" required />
                    <x-input-error :messages="$errors->get('khatian_no')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="dag_no" :value="__('citizen.land.field_dag_no')" required />
                    <x-text-input id="dag_no" name="dag_no" class="mt-1 block w-full"
                                  :value="$old('dag_no')" required />
                    <x-input-error :messages="$errors->get('dag_no')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="area" :value="__('citizen.land.field_area')" />
                    <x-text-input id="area" name="area" type="number" step="0.0001" min="0"
                                  class="mt-1 block w-full" :value="$old('area')" />
                </div>
            </div>
        </section>

        {{-- ============ CLAIMED OWNERSHIP ============ --}}
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_ownership') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="claim_share_numerator" :value="__('citizen.land.field_share_numerator')" required />
                    <x-text-input id="claim_share_numerator" name="claim_share_numerator" type="number"
                                  min="1" class="mt-1 block w-full"
                                  :value="$old('claim_share_numerator', 1)" required />
                </div>
                <div>
                    <x-input-label for="claim_share_denominator" :value="__('citizen.land.field_share_denominator')" required />
                    <x-text-input id="claim_share_denominator" name="claim_share_denominator" type="number"
                                  min="1" class="mt-1 block w-full"
                                  :value="$old('claim_share_denominator', 1)" required />
                    <x-input-error :messages="$errors->get('claim_share_denominator')" class="mt-2" />
                </div>

                <p class="text-xs leading-relaxed text-slate-400 sm:col-span-2">
                    {{ __('citizen.land.field_share_help') }}
                </p>

                <div>
                    <x-input-label for="ownership_basis" :value="__('citizen.land.field_ownership_basis')" />
                    <select id="ownership_basis" name="ownership_basis" class="input mt-1 w-full">
                        <option value="">{{ __('citizen.search.any') }}</option>
                        @foreach ([
                            'purchase', 'inheritance', 'gift_heba', 'court_decree', 'settlement', 'other',
                        ] as $basis)
                            <option value="{{ $basis }}" @selected($old('ownership_basis') === $basis)>
                                {{ __('citizen.land.basis_'.$basis) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="ownership_basis_other" :value="__('citizen.land.field_ownership_basis_other')" />
                    <x-text-input id="ownership_basis_other" name="ownership_basis_other"
                                  class="mt-1 block w-full" :value="$old('ownership_basis_other')" />
                </div>
            </div>
        </section>

        {{-- ============ DEED ============ --}}
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_deed') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="deed_no" :value="__('citizen.land.field_deed_no')" />
                    <x-text-input id="deed_no" name="deed_no" class="mt-1 block w-full" :value="$old('deed_no')" />
                </div>
                <div>
                    <x-input-label for="deed_date" :value="__('citizen.land.field_deed_date')" />
                    <x-text-input id="deed_date" name="deed_date" type="date" class="mt-1 block w-full"
                                  :value="$isEdit && $record->deed_date ? $record->deed_date->format('Y-m-d') : old('deed_date')" />
                </div>
                <div>
                    <x-input-label for="registration_office" :value="__('citizen.land.field_registration_office')" />
                    <x-text-input id="registration_office" name="registration_office"
                                  class="mt-1 block w-full" :value="$old('registration_office')" />
                </div>
            </div>
        </section>

        {{-- ============ MUTATION ============ --}}
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_mutation') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="mutation_status" :value="__('citizen.land.field_mutation_status')" />
                    <select id="mutation_status" name="mutation_status" class="input mt-1 w-full">
                        <option value="">{{ __('citizen.search.any') }}</option>
                        @foreach (['not_done', 'in_progress', 'done', 'unknown'] as $mutation)
                            <option value="{{ $mutation }}" @selected($old('mutation_status') === $mutation)>
                                {{ __('citizen.land.mutation_'.$mutation) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="mutation_no" :value="__('citizen.land.field_mutation_no')" />
                    <x-text-input id="mutation_no" name="mutation_no" class="mt-1 block w-full"
                                  :value="$old('mutation_no')" />
                </div>
            </div>
        </section>

        {{-- ============ INTENT ============ --}}
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_intent') }}</h2>

            <div class="mt-4">
                <select id="intended_use" name="intended_use" class="input w-full" required>
                    <option value="record_keeping" @selected($old('intended_use', 'record_keeping') === 'record_keeping')>
                        {{ __('citizen.land.intent_record_keeping') }}
                    </option>
                    <option value="sale_listing" @selected($old('intended_use') === 'sale_listing')>
                        {{ __('citizen.land.intent_sale_listing') }}
                    </option>
                </select>
            </div>
        </section>

        {{-- ============ ACTIONS ============ --}}
        <div class="flex-wrap items-center gap-3">
            <button type="submit" name="action" value="draft" class="btn-ghost border-slate-200">
                {{ __('citizen.land.save_draft') }}
            </button>

            <button type="submit" name="action" value="submit" class="btn-primary"
                    onclick="return confirm('{{ __('citizen.land.submit_confirm') }}')">
                {{ __('citizen.land.submit_review') }}
            </button>

            <a href="{{ route('citizen.land.index') }}" class="btn-ghost border-slate-200">
                {{ __('citizen.common.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
