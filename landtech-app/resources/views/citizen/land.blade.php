@extends('layouts.app')

@section('title', __('citizen.land.title'))
@section('page-title', __('citizen.land.title'))
@section('page-sub', __('citizen.land.subtitle'))

@section('content')
<div class="space-y-6">
    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="map" class="mx-auto h-10 w-10 text-slate-300" />
            <p class="font-semibold text-slate-700">{{ __('citizen.land.empty') }}</p>
            <p class="text-sm text-slate-500">{{ __('citizen.land.empty_hint') }}</p>
        </div>
    </section>
</div>
@endsection
