@extends('layouts.app')

@section('title', __('citizen.verification.title'))
@section('page-title', __('citizen.verification.title'))
@section('page-sub', __('citizen.verification.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.verification.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('citizen.verification.subtitle') }}</p>
        </div>
        <a href="{{ route('citizen.verification.create') }}" class="btn-primary">
            {{ __('citizen.verification.create') }}
        </a>
    </div>

    {{-- The honesty banner. This must be visible on every verification screen. --}}
    <div class="alert alert-warning">
        <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.verification.official_note') }}</p>
    </div>

    @if ($requests->isEmpty())
        <section class="card p-6">
            <div class="empty-state">
                <x-icon name="shield-check" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-700">{{ __('citizen.verification.empty') }}</p>
                <p class="text-sm text-slate-500">{{ __('citizen.verification.empty_hint') }}</p>
            </div>
        </section>
    @else
        <section class="card">
            <div class="card-body pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="py-3 pe-3">{{ __('citizen.verification.reference') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.verification.land') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.verification.status_label') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.verification.checks_heading') }}</th>
                                <th class="py-3 pe-3">{{ __('citizen.verification.submitted_on') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($requests as $request)
                                <tr>
                                    <td class="py-3 pe-3 font-semibold text-slate-700">{{ $request->reference_no }}</td>
                                    <td class="py-3 pe-3 text-slate-600">
                                        {{ $request->khatian_no ?? '—' }} / {{ $request->dag_no ?? '—' }}
                                        <span class="block text-xs text-slate-400">
                                            {{ collect([$request->mouza_name, $request->upazila_name])->filter()->implode(', ') }}
                                        </span>
                                    </td>
                                    <td class="py-3 pe-3"><x-status-badge :status="$request->status" /></td>
                                    <td class="py-3 pe-3 text-slate-600">{{ $request->checks_count }}</td>
                                    <td class="py-3 pe-3 text-slate-500">{{ $request->submitted_at?->format('Y-m-d') }}</td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('citizen.verification.show', $request->id) }}"
                                           class="btn-ghost btn-sm border-slate-200">{{ __('citizen.common.view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($requests->hasPages())
                    <div class="pt-4">{{ $requests->links() }}</div>
                @endif
            </div>
        </section>
    @endif

    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.review.not_implemented_title') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('citizen.review.not_implemented_body') }}</p>
    </section>
</div>
@endsection
