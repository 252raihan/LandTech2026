@extends('layouts.app')
@section('title', __('pages.plot-lock.title')) @section('page-title', __('pages.plot-lock.page_title'))
@section('page-sub', __('pages.plot-lock.page_sub'))

@push('scripts')
<script type="module">
import { getPlots } from '/js/store.js';
import { acquireLock, releaseLock, activeLocks } from '/js/locks.js';
import { toast, fmtTime } from '/js/ui.js';

function render() {
    const plots = getPlots(), locks = activeLocks();
    const lockedCount = plots.filter(p => locks[p.id]).length;

    document.getElementById('lock-summary').textContent =
        __t('pages.plot-lock.lock_summary', { locked: lockedCount, total: plots.length });

    document.getElementById('lock-table').innerHTML = plots.map(p => {
        const lock = locks[p.id];
        return `<tr>
            <td class="mono" title="${__esc(p.redlockKey)}">${__esc(p.redlockKey)}</td>
            <td class="font-medium text-slate-900">${__esc(p.plotNo)}</td>
            <td>${lock
                ? `<span class="badge badge-red">${__tHtml('pages.plot-lock.locked_by', { holder: lock.holder })}</span>`
                : `<span class="badge badge-green">${__t('common.free')}</span>`}</td>
            <td class="whitespace-nowrap text-xs text-slate-500">${lock ? __esc(fmtTime(lock.expiresAt)) : '—'}</td>
            <td class="whitespace-nowrap text-end">${lock
                ? `<button type="button" class="btn-ghost btn-xs" onclick="window.__rel('${__esc(p.id)}','${__esc(lock.holder)}')">${__t('pages.plot-lock.release')}</button>`
                : `<button type="button" class="btn-primary btn-xs" onclick="window.__acq('${__esc(p.id)}')">${__t('pages.plot-lock.acquire')}</button>`}</td>
        </tr>`;
    }).join('');
}

window.__acq = (id) => {
    const r = acquireLock(id, 'officer-demo');
    if (r.ok) {
        toast(__t('pages.plot-lock.acquired_alert'), 'success');
    } else {
        toast(r.reason, 'error');
    }
    render();
};

window.__rel = (id, holder) => {
    const r = releaseLock(id, holder);
    if (r.ok) {
        toast(__t('pages.plot-lock.released_alert'), 'success');
    } else {
        toast(r.reason, 'error');
    }
    render();
};

render();
setInterval(render, 5000); // show TTL expiry live
</script>
@endpush

@section('content')
<x-page-header icon="lock" :title="__('pages.plot-lock.page_title')" :subtitle="__('pages.plot-lock.page_sub')" />

<div class="mb-6 alert alert-info">
    <x-icon name="info-circle" class="h-5 w-5 shrink-0" />
    <p>
        {!! __('pages.plot-lock.intro', [
            'key' => '<code class="rounded bg-white/70 px-1 py-0.5 font-mono text-xs">LOCK:LAND:&lt;District&gt;:&lt;Upazila&gt;:&lt;Mouza&gt;:&lt;Khatian&gt;:&lt;Plot&gt;</code>',
        ]) !!}
        <span class="sim-badge ms-1">{{ __('pages.plot-lock.badge') }}</span>
    </p>
</div>

<section class="card" aria-labelledby="lock-table-title">
    <div class="card-header">
        <div>
            <h2 id="lock-table-title" class="card-title">{{ __('pages.plot-lock.table_heading') }}</h2>
            <p class="text-xs text-slate-400">{{ __('pages.plot-lock.expires_hint') }}</p>
        </div>
        <span id="lock-summary" class="badge badge-slate"></span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table min-w-[860px]">
            <thead>
                <tr>
                    <th scope="col">{{ __('pages.plot-lock.col_key') }}</th>
                    <th scope="col">{{ __('pages.plot-lock.col_plot') }}</th>
                    <th scope="col">{{ __('pages.plot-lock.col_status') }}</th>
                    <th scope="col">{{ __('pages.plot-lock.col_expires') }}</th>
                    <th scope="col" class="text-end">{{ __('pages.plot-lock.col_action') }}</th>
                </tr>
            </thead>
            <tbody id="lock-table"></tbody>
        </table>
    </div>
</section>
@endsection
