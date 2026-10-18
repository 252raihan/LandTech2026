@extends('layouts.app')

@section('title', __('officer.no_role.title'))
@section('page-title', __('officer.no_role.title'))
@section('page-sub', __('officer.no_role.subtitle'))

@section('content')
<div class="space-y-6">
    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="warning" class="mx-auto h-10 w-10 text-amber-400" />
            <p class="font-semibold text-slate-700">{{ __('officer.no_role.heading') }}</p>
            <p class="text-sm text-slate-500">{{ __('officer.no_role.hint') }}</p>
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
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="menu-item border-slate-200 w-full text-left">
                    <x-icon name="logout" class="h-4 w-4 text-slate-600" />
                    <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">
                        {{ __('officer.no_role.logout') }}
                    </span>
                </button>
            </form>
        </div>
    </section>
</div>
@endsection
