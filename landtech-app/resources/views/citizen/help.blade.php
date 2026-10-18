@extends('layouts.app')

@section('title', __('citizen.help.title'))
@section('page-title', __('citizen.help.title'))
@section('page-sub', __('citizen.help.subtitle'))

@section('content')
<div class="space-y-6">
    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="chat" class="mx-auto h-10 w-10 text-slate-300" />
            <p class="font-semibold text-slate-700">{{ __('citizen.help.coming_soon') }}</p>
            <p class="text-sm text-slate-500">{{ __('citizen.help.coming_soon_hint') }}</p>
        </div>
    </section>
</div>
@endsection
