@extends('layouts.app')

@section('title', __('citizen.notifications.title'))
@section('page-title', __('citizen.notifications.title'))
@section('page-sub', __('citizen.notifications.subtitle'))

@section('content')
<div class="space-y-6">
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">{{ __('citizen.notifications.heading') }}</h2>
        </div>
        <div class="card-body pt-0">
            @if ($notifications->isEmpty())
                <div class="empty-state">
                    <x-icon name="bell" class="mx-auto h-8 w-8 text-slate-300" />
                    <p>{{ __('citizen.notifications.empty') }}</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($notifications as $n)
                        <li class="flex items-start gap-3 py-3">
                            <span class="badge {{ $n->unread() ? 'badge-blue' : 'badge-slate' }} mt-0.5 shrink-0">
                                {{ $n->unread() ? __('citizen.notifications.unread') : __('citizen.notifications.read') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-700">{{ $n->title }}</p>
                                <p class="text-sm text-slate-500">{{ $n->body }}</p>
                            </div>
                            <time class="shrink-0 text-xs text-slate-400">{{ $n->created_at?->diffForHumans() }}</time>
                        </li>
                    @endforeach
                </ul>

                @if ($notifications->hasPages())
                    <div class="pt-4">
                        {{ $notifications->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>
</div>
@endsection
