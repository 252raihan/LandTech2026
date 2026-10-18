@extends('layouts.app')

@section('title', __('citizen.interest.title'))
@section('page-title', __('citizen.interest.title'))
@section('page-sub', __('citizen.interest.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="min-w-0">
        <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.interest.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('citizen.interest.subtitle') }}</p>
    </div>

    @if ($interests->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="flag" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.interest.empty') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.interest.empty_hint') }}</p>
                <a href="{{ route('citizen.find') }}" class="btn-primary mt-3">{{ __('citizen.search.title') }}</a>
            </div>
        </section>
    @else
        <section class="card">
            <div class="card-body pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="py-3 pe-3">{{ __('citizen.interest.reference') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.interest.listing') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.interest.status_label') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.interest.raised_on') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($interests as $interest)
                                <tr>
                                    <td class="py-3 pe-3 font-semibold text-slate-700">{{ $interest->reference_no }}</td>
                                    <td class="py-3 pe-3 text-slate-600">
                                        {{ $interest->listing?->title ?? __('citizen.common.none') }}
                                    </td>
                                    <td class="py-3 pe-3"><x-status-badge :status="$interest->status" /></td>
                                    <td class="py-3 pe-3 text-slate-500">{{ $interest->created_at?->format('Y-m-d') }}</td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('citizen.interest.show', $interest) }}"
                                           class="btn-ghost btn-sm border-slate-200">{{ __('citizen.common.view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($interests->hasPages())
                    <div class="pt-4">{{ $interests->links() }}</div>
                @endif
            </div>
        </section>
    @endif

    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="info-circle" class="mx-auto h-8 w-8 text-slate-300" />
            <p class="text-sm leading-relaxed text-slate-500">{{ __('citizen.interest.not_transfer_warning') }}</p>
        </div>
    </section>
</div>
@endsection
