@extends('layouts.app')

@section('title', __('citizen.ownership.title'))
@section('page-title', __('citizen.ownership.title'))
@section('page-sub', __('citizen.ownership.subtitle'))

@section('content')
<div class="space-y-6">
    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="document" class="mx-auto h-10 w-10 text-slate-300" />
            <p class="font-semibold text-slate-700">{{ __('citizen.ownership.empty') }}</p>
            <p class="text-sm text-slate-500">{{ __('citizen.ownership.empty_hint') }}</p>
        </div>
    </section>
</div>
@endsection
