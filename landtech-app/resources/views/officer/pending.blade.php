@extends('layouts.app')

@php
    $roleLabel = \App\Models\Role::query()->where('name', $role)->value(
        app()->getLocale() === 'bn' ? 'label_bn' : 'label_en'
    ) ?? $role;
@endphp

@section('title', __($titleKey))
@section('page-title', __($titleKey))
@section('page-sub', __($subtitleKey))

@section('content')
<div class="space-y-6">
    <section class="card p-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-blue">{{ $roleLabel }}</span>
            <span class="badge badge-amber">{{ __('officer.pending_badge') }}</span>
        </div>
    </section>

    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="layers" class="mx-auto h-10 w-10 text-slate-300" />
            <p class="font-semibold text-slate-700">{{ __('officer.pending_title') }}</p>
            <p class="text-sm text-slate-500">{{ __('officer.pending_hint') }}</p>
        </div>
    </section>

    <section class="card p-6">
        <div class="card-header">
            <h2 class="card-title">{{ __('officer.available_now') }}</h2>
        </div>
        <div class="card-body space-y-3">
            <a href="{{ route('profile.edit') }}" class="menu-item border-slate-200">
                <x-icon name="user" class="h-4 w-4 text-slate-600" />
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">
                    {{ __('officer.profile_link') }}
                </span>
                <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" />
            </a>
            <p class="text-sm text-slate-500">{{ __('officer.demo_note') }}</p>
        </div>
    </section>
</div>
@endsection
