@extends('layouts.app')

@section('title', __('citizen.interest.title'))
@section('page-title', $interest->reference_no)
@section('page-sub', $interest->listing?->title ?? __('citizen.interest.title'))

@section('content')
<div class="space-y-6">

    <div class="min-w-0">
        <x-status-badge :status="$interest->status" />
        <h1 class="mt-3 text-xl font-bold text-navy-900">{{ $interest->reference_no }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $interest->listing?->title ?? __('citizen.common.none') }}
        </p>
    </div>

    <div class="alert alert-warning">
        <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
        <p class="min-w-0 flex-1">{{ __('citizen.interest.not_transfer_warning') }}</p>
    </div>

    <section class="card p-6">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            @foreach ([
                ['citizen.interest.listing', $interest->listing?->reference_no],
                ['citizen.interest.raised_on', $interest->created_at?->format('Y-m-d H:i')],
                ['citizen.interest.status_label', __('citizen.status.'.$interest->status)],
                ['citizen.interest.contact_shared', $interest->contact_shared ? __('citizen.common.yes') : __('citizen.common.no')],
                ['citizen.interest.responded_on', $interest->responded_at?->format('Y-m-d H:i')],
            ] as [$labelKey, $value])
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __($labelKey) }}</dt>
                    <dd class="mt-1 text-slate-700">{{ $value ?: __('citizen.common.none') }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($interest->message)
            <div class="mt-6 border-t border-slate-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ __('citizen.interest.your_message') }}
                </p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $interest->message }}</p>
            </div>
        @endif

        @if ($interest->seller_response)
            <div class="mt-6 border-t border-slate-200 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ __('citizen.interest.seller_response') }}
                </p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $interest->seller_response }}</p>
            </div>
        @endif
    </section>

    {{-- Owner-only response form. Policy-authorized: only the listing owner
         passes `respond`. --}}
    @can('respond', $interest)
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.interest.respond') }}</h2>

            <form method="POST" action="{{ route('citizen.interest.respond', $interest) }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <x-input-label for="seller_response" :value="__('citizen.interest.field_response')" />
                    <textarea id="seller_response" name="seller_response" rows="4" class="input mt-1 w-full"
                              maxlength="2000">{{ old('seller_response', $interest->seller_response) }}</textarea>
                </div>

                <div>
                    <x-input-label for="status" :value="__('citizen.interest.field_status')" required />
                    <select id="status" name="status" class="input mt-1 w-full" required>
                        @foreach ([
                            'acknowledged' => 'acknowledged',
                            'contact_shared' => 'contact_shared',
                            'site_visit_proposed' => 'site_visit_proposed',
                            'declined' => 'declined',
                            'closed' => 'closed',
                        ] as $value => $key)
                            <option value="{{ $value }}" @selected($interest->status === $value)>
                                {{ __('citizen.status.'.$key) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <label for="contact_shared" class="inline-flex items-center gap-2">
                    <input id="contact_shared" name="contact_shared" type="checkbox" value="1"
                           class="h-4 w-4 rounded border-slate-300 text-brand-600"
                           @checked($interest->contact_shared)>
                    <span class="text-sm text-slate-600">{{ __('citizen.interest.field_contact_shared') }}</span>
                </label>

                <button type="submit" class="btn-primary">{{ __('citizen.interest.submit_response') }}</button>
            </form>
        </section>
    @endcan

    <section class="card p-6">
        <div class="empty-state">
            <x-icon name="lock" class="mx-auto h-8 w-8 text-slate-300" />
            <p class="text-sm leading-relaxed text-slate-500">{{ __('citizen.interest.privacy_note') }}</p>
        </div>
    </section>
</div>
@endsection
