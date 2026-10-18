@extends('layouts.app')

@section('title', __('citizen.verification.create'))
@section('page-title', __('citizen.verification.create'))
@section('page-sub', __('citizen.verification.create_subtitle'))

@section('content')
<div class="space-y-6">

    <div class="alert alert-warning">
        <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.verification.official_note') }}</p>
    </div>

    @if ($listing)
        <div class="card p-4">
            <p class="text-sm text-slate-600">
                {{ __('citizen.search.source_label') }}: <span class="font-semibold">{{ $listing->title }}</span>
                ({{ $listing->reference_no }})
            </p>
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

    {{-- A buyer may request verification for land that has NO listing. The land
         identifiers are collected directly for exactly that reason. --}}
    <form method="POST" action="{{ route('citizen.verification.store') }}" class="space-y-6">
        @csrf

        @if (! empty($prefill['sale_listing_id']))
            <input type="hidden" name="sale_listing_id" value="{{ $prefill['sale_listing_id'] }}">
        @endif

        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_location') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="district_name" :value="__('citizen.verification.field_district')" />
                    <x-text-input id="district_name" name="district_name" class="mt-1 block w-full"
                                  :value="old('district_name', $prefill['district_name'] ?? '')" />
                </div>
                <div>
                    <x-input-label for="upazila_name" :value="__('citizen.verification.field_upazila')" />
                    <x-text-input id="upazila_name" name="upazila_name" class="mt-1 block w-full"
                                  :value="old('upazila_name', $prefill['upazila_name'] ?? '')" />
                </div>
                <div>
                    <x-input-label for="mouza_name" :value="__('citizen.verification.field_mouza')" />
                    <x-text-input id="mouza_name" name="mouza_name" class="mt-1 block w-full"
                                  :value="old('mouza_name', $prefill['mouza_name'] ?? '')" />
                </div>
                <div>
                    <x-input-label for="jl_no" :value="__('citizen.verification.field_jl_no')" />
                    <x-text-input id="jl_no" name="jl_no" class="mt-1 block w-full"
                                  :value="old('jl_no', $prefill['jl_no'] ?? '')" />
                </div>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_identifiers') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <x-input-label for="survey_type" :value="__('citizen.verification.field_survey_type')" />
                    <select id="survey_type" name="survey_type" class="input mt-1 w-full">
                        <option value="">{{ __('citizen.search.any') }}</option>
                        @foreach (\App\Models\LandRecord::SURVEY_TYPES as $type)
                            <option value="{{ $type }}"
                                @selected(old('survey_type', $prefill['survey_type'] ?? '') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="khatian_no" :value="__('citizen.verification.field_khatian_no')" />
                    <x-text-input id="khatian_no" name="khatian_no" class="mt-1 block w-full"
                                  :value="old('khatian_no', $prefill['khatian_no'] ?? '')" />
                </div>
                <div>
                    <x-input-label for="dag_no" :value="__('citizen.verification.field_dag_no')" />
                    <x-text-input id="dag_no" name="dag_no" class="mt-1 block w-full"
                                  :value="old('dag_no', $prefill['dag_no'] ?? '')" />
                </div>
                <div>
                    <x-input-label for="area" :value="__('citizen.verification.field_area')" />
                    <x-text-input id="area" name="area" type="number" step="0.0001" min="0"
                                  class="mt-1 block w-full" :value="old('area', $prefill['area'] ?? '')" />
                </div>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.land.section_deed') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="deed_no" :value="__('citizen.verification.field_deed_no')" />
                    <x-text-input id="deed_no" name="deed_no" class="mt-1 block w-full" :value="old('deed_no')" />
                </div>
                <div>
                    <x-input-label for="registration_office" :value="__('citizen.verification.field_registration_office')" />
                    <x-text-input id="registration_office" name="registration_office"
                                  class="mt-1 block w-full" :value="old('registration_office')" />
                </div>
            </div>
        </section>

        <section class="card p-6">
            <x-input-label for="reason" :value="__('citizen.verification.field_reason')" required />
            <textarea id="reason" name="reason" rows="4" class="input mt-1 w-full"
                      required minlength="10" maxlength="2000">{{ old('reason') }}</textarea>
            <p class="mt-1 text-xs text-slate-400">{{ __('citizen.verification.field_reason_help') }}</p>
            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
        </section>

        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.verification.limitations_heading') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                {{ __('citizen.verification.limitation.overall') }}
            </p>
        </section>

        <div class="flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary">{{ __('citizen.verification.submit') }}</button>
            <a href="{{ route('citizen.verification.index') }}" class="btn-ghost border-slate-200">
                {{ __('citizen.common.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
