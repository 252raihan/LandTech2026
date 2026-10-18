@extends('layouts.app')

@section('title', __('citizen.link.claim'))
@section('page-title', __('citizen.link.claim'))
@section('page-sub', __('citizen.link.claim_subtitle'))

@section('content')
<div class="space-y-6">

    <div class="alert alert-warning">
        <x-icon name="shield-check" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.link.warning') }}</p>
    </div>

    @if ($alreadyLinked)
        <div class="alert alert-warning">
            <x-icon name="check-circle" class="h-5 w-5 shrink-0" />
            <p class="min-w-0 flex-1">{{ __('citizen.link.status_verified') }}</p>
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

    {{-- NOTE: no NID field. Identity material is never accepted here; it stays
         behind IdentityAccessService, which requires an authorized role and
         writes an audited, hash-chained record. --}}
    <form method="POST" action="{{ route('citizen.link.store') }}" class="space-y-6">
        @csrf

        <section class="card p-6 space-y-4">
            <div>
                <x-input-label for="display_name" :value="__('citizen.link.field_display_name')" required />
                <x-text-input id="display_name" name="display_name" class="mt-1 block w-full"
                              :value="old('display_name')" required />
                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="date_of_birth" :value="__('citizen.link.field_dob')" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date"
                              class="mt-1 block w-full" :value="old('date_of_birth')" />
            </div>

            <div>
                <x-input-label for="org_registration_no" :value="__('citizen.link.field_org_reg')" />
                <x-text-input id="org_registration_no" name="org_registration_no"
                              class="mt-1 block w-full" :value="old('org_registration_no')" />
            </div>

            <div>
                <x-input-label for="claim_basis" :value="__('citizen.link.field_basis')" required />
                <select id="claim_basis" name="claim_basis" class="input mt-1 w-full" required>
                    @foreach (['nid', 'birth_registration', 'inheritance', 'court_order', 'other'] as $basis)
                        <option value="{{ $basis }}" @selected(old('claim_basis') === $basis)>
                            {{ __('citizen.link.basis_'.$basis) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="claim_note" :value="__('citizen.link.field_note')" required />
                <textarea id="claim_note" name="claim_note" rows="4" class="input mt-1 w-full"
                          required maxlength="2000">{{ old('claim_note') }}</textarea>
                <x-input-error :messages="$errors->get('claim_note')" class="mt-2" />
            </div>
        </section>

        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.review.not_implemented_title') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('citizen.review.not_implemented_body') }}</p>
        </section>

        <div class="flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary">{{ __('citizen.link.submit') }}</button>
            <a href="{{ route('citizen.link.index') }}" class="btn-ghost border-slate-200">
                {{ __('citizen.common.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
