@extends('layouts.app')
@section('title', __('pages.sync-webhook.title')) @section('page-title', __('pages.sync-webhook.page_title'))
@section('page-sub', __('pages.sync-webhook.page_sub'))

@push('scripts')
<script type="module">
import { getEvents, pushEvent, getPlots, savePlots } from '/js/store.js';
import { toast, fmtTime } from '/js/ui.js';

function render() {
    const events = getEvents();
    document.getElementById('evt-count').textContent =
        __t('pages.sync-webhook.event_count', { count: events.length });

    document.getElementById('evt-list').innerHTML = events.length
        ? events.map(e => `
            <li class="py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge badge-blue">${__esc(e.type)}</span>
                    <time class="text-xs text-slate-400" datetime="${__esc(e.ts)}">${__esc(fmtTime(e.ts))}</time>
                    <span class="text-xs text-slate-300" aria-hidden="true">·</span>
                    <span class="text-xs text-slate-400">${__esc(e.source)}</span>
                </div>
                <code class="mt-2 block overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3 font-mono text-[11px] leading-relaxed text-slate-600">${__esc(JSON.stringify(e.payload, null, 2))}</code>
            </li>`).join('')
        : `<li class="py-10 text-center">
                <p class="text-sm text-slate-500">${__esc(__t('pages.sync-webhook.empty'))}</p>
           </li>`;
}
render();

document.getElementById('btn-deed-event').onclick = () => {
    // Simulate: Sub-Registry deed execution → DEED_EXECUTED_EVENT → Pending Mutation Flag
    const plots = getPlots();
    if (!plots.length) return;
    const p = plots[Math.floor(Math.random() * plots.length)];
    p.mutationStatus = 'PENDING_MUTATION';
    savePlots(plots);
    pushEvent('DEED_EXECUTED_EVENT', { plotId: p.id, plotNo: p.plotNo, flag: 'PENDING_MUTATION' });
    render();
    toast(__t('pages.sync-webhook.emitted_toast', { plotNo: p.plotNo }), 'success');
};

document.getElementById('btn-echo').onclick = () => {
    pushEvent('SYNC_PING', { channel: 'simulated-kafka', ok: true });
    render();
    toast(__t('pages.sync-webhook.pinged_toast'), 'info');
};
</script>
@endpush

@section('content')
<x-page-header icon="sync" :title="__('pages.sync-webhook.page_title')" :subtitle="__('pages.sync-webhook.page_sub')" />

<section class="card mb-6" aria-labelledby="sync-intro-title">
    <div class="card-header">
        <h2 id="sync-intro-title" class="card-title">{{ __('pages.sync-webhook.intro_heading') }}</h2>
        <span class="sim-badge">{{ __('pages.sync-webhook.badge') }}</span>
    </div>
    <div class="card-body">
        <p class="text-sm leading-relaxed text-slate-600">
            {!! __('pages.sync-webhook.intro', [
                'kafka' => '<strong>'.e(__('pages.sync-webhook.intro_kafka')).'</strong>',
                'event' => '<code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-xs">DEED_EXECUTED_EVENT</code>',
                'flag' => '<strong>'.e(__('pages.sync-webhook.intro_flag')).'</strong>',
            ]) !!}
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" id="btn-deed-event" class="btn-primary">
                <x-icon name="sync" class="h-4 w-4" />
                <span>{{ __('pages.sync-webhook.emit_event') }}</span>
            </button>
            <button type="button" id="btn-echo" class="btn-ghost">
                <x-icon name="refresh" class="h-4 w-4" />
                <span>{{ __('pages.sync-webhook.ping_channel') }}</span>
            </button>
        </div>
    </div>
</section>

<section class="card" aria-labelledby="evt-feed-title">
    <div class="card-header">
        <h2 id="evt-feed-title" class="card-title">{{ __('pages.sync-webhook.feed_heading') }}</h2>
        <span id="evt-count" class="badge badge-slate"></span>
    </div>
    <div class="card-body pt-0">
        <ul id="evt-list" class="divide-y divide-slate-100"></ul>
    </div>
</section>
@endsection
