@extends('layouts.app')

@php
    $isEdit = $listing !== null;
    $old = fn (string $key, $default = null) => old($key, $isEdit ? $listing->{$key} : $default);
@endphp

@section('title', $isEdit ? __('citizen.listings.edit') : __('citizen.listings.create'))
@section('page-title', $isEdit ? __('citizen.listings.edit') : __('citizen.listings.create'))
@section('page-sub', __('citizen.listings.create_subtitle'))

@section('content')
<div class="space-y-6">

    <div class="alert alert-warning">
        <x-icon name="warning" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.listings.unverified_warning') }}</p>
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

    @if ($records->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="map" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.listings.no_eligible_records') }}</p>
                <a href="{{ route('citizen.land.create') }}" class="btn-primary mt-3">
                    {{ __('citizen.land.add') }}
                </a>
            </div>
        </section>
    @else
        {{-- NOTE: no verification_status field, and no status field. Both are
             server-controlled: verification is reviewer-only and publication
             follows review. --}}
        <form method="POST"
              action="{{ $isEdit ? route('citizen.listings.update', $listing) : route('citizen.listings.store') }}"
              class="space-y-6">
            @csrf
            @if ($isEdit)
                @method('PATCH')
            @endif

            <section class="card p-6 space-y-4">
                <div>
                    <x-input-label for="land_record_id" :value="__('citizen.listings.field_record')" required />
                    <select id="land_record_id" name="land_record_id" class="input mt-1 w-full" required>
                        @foreach ($records as $record)
                            <option value="{{ $record->id }}"
                                @selected((int) $old('land_record_id', $listing?->land_record_id) === $record->id)>
                                {{ $record->khatian_no }} / {{ $record->dag_no }}
                                — {{ $record->mouza_name ?: ($record->mouza?->name_en ?? '') }}
                                ({{ $record->shareLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="title" :value="__('citizen.listings.field_title')" required />
                    <x-text-input id="title" name="title" class="mt-1 block w-full"
                                  :value="$old('title')" required maxlength="191" />
                </div>

                <div>
                    <x-input-label for="public_description" :value="__('citizen.listings.field_description')" />
                    <textarea id="public_description" name="public_description" rows="5"
                              class="input mt-1 w-full" maxlength="5000">{{ $old('public_description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">{{ __('citizen.listings.field_description_help') }}</p>
                </div>
            </section>

            <section class="card p-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="area" :value="__('citizen.listings.field_area')" />
                    <x-text-input id="area" name="area" type="number" step="0.0001" min="0"
                                  class="mt-1 block w-full" :value="$old('area')" />
                </div>
                <div>
                    <x-input-label for="area_unit" :value="__('citizen.land.field_area_unit')" />
                    <select id="area_unit" name="area_unit" class="input mt-1 w-full">
                        @foreach (\App\Models\LandRecord::AREA_UNITS as $unit)
                            <option value="{{ $unit }}" @selected($old('area_unit', 'decimal') === $unit)>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="asking_price" :value="__('citizen.listings.field_price')" />
                    <x-text-input id="asking_price" name="asking_price" type="number" step="0.01" min="0"
                                  class="mt-1 block w-full" :value="$old('asking_price')" />
                </div>
                <div>
                    <x-input-label for="price_unit" :value="__('citizen.listings.field_price_unit')" />
                    <select id="price_unit" name="price_unit" class="input mt-1 w-full">
                        @foreach (\App\Models\SaleListing::PRICE_UNITS as $unit)
                            <option value="{{ $unit }}" @selected($old('price_unit', 'BDT') === $unit)>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section class="card p-6 grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="sale_type" :value="__('citizen.listings.field_sale_type')" required />
                    <select id="sale_type" name="sale_type" class="input mt-1 w-full" required>
                        <option value="full_plot" @selected($old('sale_type', 'full_plot') === 'full_plot')>
                            {{ __('citizen.listings.sale_full_plot') }}
                        </option>
                        <option value="partial" @selected($old('sale_type') === 'partial')>
                            {{ __('citizen.listings.sale_partial') }}
                        </option>
                    </select>
                </div>
                <div>
                    <x-input-label for="claim_share_numerator" :value="__('citizen.listings.field_share_numerator')" required />
                    <x-text-input id="claim_share_numerator" name="claim_share_numerator" type="number"
                                  min="1" class="mt-1 block w-full"
                                  :value="$old('claim_share_numerator', 1)" required />
                </div>
                <div>
                    <x-input-label for="claim_share_denominator" :value="__('citizen.listings.field_share_denominator')" required />
                    <x-text-input id="claim_share_denominator" name="claim_share_denominator" type="number"
                                  min="1" class="mt-1 block w-full"
                                  :value="$old('claim_share_denominator', 1)" required />
                    <x-input-error :messages="$errors->get('claim_share_numerator')" class="mt-2" />
                </div>

                <p class="text-xs leading-relaxed text-slate-400 sm:col-span-3">
                    {{ __('citizen.listings.share_cap_note') }}
                </p>
            </section>

            <section class="card p-6">
                <x-input-label for="contact_preference" :value="__('citizen.listings.field_contact')" required />
                <select id="contact_preference" name="contact_preference" class="input mt-1 w-full" required>
                    @foreach ([
                        'via_platform' => 'contact_via_platform',
                        'phone' => 'contact_phone',
                        'email' => 'contact_email',
                    ] as $value => $key)
                        <option value="{{ $value }}" @selected($old('contact_preference', 'via_platform') === $value)>
                            {{ __('citizen.listings.'.$key) }}
                        </option>
                    @endforeach
                </select>
            </section>

            <div class="flex-wrap items-center gap-3">
                <button type="submit" name="action" value="draft" class="btn-ghost border-slate-200">
                    {{ __('citizen.listings.save_draft') }}
                </button>
                <button type="submit" name="action" value="submit" class="btn-primary">
                    {{ __('citizen.listings.submit_review') }}
                </button>
                <a href="{{ route('citizen.listings.index') }}" class="btn-ghost border-slate-200">
                    {{ __('citizen.common.cancel') }}
                </a>
            </div>
        </form>
    @endif
</div>
@endsection
