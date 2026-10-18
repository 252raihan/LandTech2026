@extends('layouts.app')

@section('title', __('citizen.link.heading'))
@section('page-title', __('citizen.link.heading'))
@section('page-sub', __('citizen.link.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.link.heading') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('citizen.link.subtitle') }}</p>
        </div>
        <a href="{{ route('citizen.link.create') }}" class="btn-primary">{{ __('citizen.link.claim') }}</a>
    </div>

    {{-- The single most important statement on this page. --}}
    <div class="alert alert-warning">
        <x-icon name="shield-check" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.link.warning') }}</p>
    </div>

    @if ($links->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="shield-check" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.link.empty') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.link.empty_hint') }}</p>
            </div>
        </section>
    @else
        <section class="card">
            <div class="card-body pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="py-3 pe-3">{{ __('citizen.link.column_party') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.link.column_basis') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.link.column_status') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.link.column_reviewed') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($links as $link)
                                <tr>
                                    <td class="py-3 pe-3 font-semibold text-slate-700">
                                        {{ $link->party?->display_name_en ?? __('citizen.common.none') }}
                                    </td>
                                    <td class="py-3 pe-3 text-slate-600">
                                        {{ __('citizen.link.basis_'.$link->claim_basis) }}
                                    </td>
                                    <td class="py-3 pe-3">
                                        <span class="badge badge-{{ $link->status === 'verified' ? 'green' : ($link->status === 'rejected' ? 'red' : 'amber') }}">
                                            {{ __('citizen.link.status_'.$link->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pe-3 text-slate-500">
                                        {{ $link->reviewed_at?->diffForHumans() ?? __('citizen.common.none') }}
                                    </td>
                                    <td class="py-3 text-right">
                                        @can('delete', $link)
                                            <form method="POST" action="{{ route('citizen.link.destroy', $link->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-ghost btn-sm border-slate-200"
                                                        onclick="return confirm('{{ __('citizen.link.withdraw_confirm') }}')">
                                                    {{ __('citizen.link.withdraw') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    {{-- Honest statement that the reviewer interface does not exist yet. --}}
    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.review.not_implemented_title') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('citizen.review.not_implemented_body') }}</p>
        <p class="mt-2 text-xs text-slate-400">{{ __('citizen.review.separation_note') }}</p>
    </section>
</div>
@endsection
