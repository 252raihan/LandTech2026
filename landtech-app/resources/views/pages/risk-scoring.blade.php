@extends('layouts.app')
@section('title', __('pages.risk-scoring.title')) @section('page-title', __('pages.risk-scoring.page_title'))
@section('page-sub', __('pages.risk-scoring.page_sub'))

@push('scripts')
<script type="module">
import { scoreAll, publicCheck } from '/js/risk.js';

const BAND_CLASS = { red: 'badge-red', amber: 'badge-amber', green: 'badge-green' };
const BAR_CLASS = { red: 'bg-red-600', amber: 'bg-amber-500', green: 'bg-brand-500' };

function bandBadge(b) {
    return `<span class="badge ${BAND_CLASS[b.color] ?? 'badge-slate'}">${__esc(b.label)}</span>`;
}

function mutationLabel(status) {
    return __t({
        MUTATED: 'common.mutated',
        PENDING_MUTATION: 'common.pending_mutation',
        NONE: 'common.no_mutation',
    }[status] ?? 'common.no_mutation');
}

function render() {
    const scores = scoreAll();
    document.getElementById('risk-table').innerHTML = scores.map(s => `
        <tr>
            <td>
                <button type="button" class="font-medium text-blue-700 hover:underline" data-plot="${__esc(s.id)}">
                    ${__esc(s.plotNo)}
                </button>
            </td>
            <td class="whitespace-nowrap tabular-nums">
                <span class="font-bold">${__esc(s.score)}</span><span class="text-xs text-slate-400"> / 100</span>
                <span class="mt-1 block h-1.5 w-24 rounded-full bg-slate-100" aria-hidden="true">
                    <span class="block h-full rounded-full ${BAR_CLASS[s.band.color] ?? 'bg-brand-500'}" style="width:${Math.max(2, Number(s.score))}%"></span>
                </span>
            </td>
            <td>${bandBadge(s.band)}</td>
        </tr>`).join('');
}
render();

function openReport(plotId) {
    const c = publicCheck(plotId);
    if (!c) return;

    document.getElementById('public-out').innerHTML = `
        <h3 class="text-sm font-bold text-slate-900">${__tHtml('pages.risk-scoring.report_title', { plotNo: c.plot.plotNo })}</h3>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <dt class="text-slate-400">${__t('pages.risk-scoring.risk_line')}</dt>
                <dd class="flex items-center gap-2"><strong class="tabular-nums">${__esc(c.risk.score)}/100</strong>${bandBadge(c.risk.band)}</dd>
            </div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.owner')}</dt>
                <dd>${__esc(c.plot.owner)} <em class="text-xs not-italic text-slate-400">(${__t('common.synthetic')})</em></dd></div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('pages.risk-scoring.mutation_line')}</dt>
                <dd><strong>${__esc(mutationLabel(c.mutation))}</strong></dd></div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.deeds')}</dt>
                <dd>${__tHtml('pages.risk-scoring.deeds_line', { count: c.deedCount })}</dd></div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.status')}</dt>
                <dd>${__tHtml('pages.risk-scoring.status_line', { status: c.khasOrAcquisition })}</dd></div>
        </dl>
        ${c.risk.reasons.length
            ? `<div class="alert alert-danger mt-4">
                   <x-icon name="warning" class="h-5 w-5 shrink-0" />
                   <div>
                       <p class="font-semibold">${__t('pages.risk-scoring.risk_factors')}</p>
                       <ul class="mt-1 list-inside list-disc">${c.risk.reasons.map(r => `<li>${__esc(r)}</li>`).join('')}</ul>
                   </div>
               </div>`
            : `<div class="alert alert-success mt-4">${__t('pages.risk-scoring.no_risk_factors')}</div>`}`;
}

document.getElementById('risk-table').addEventListener('click', e => {
    const target = e.target.closest('[data-plot]');
    if (target) {
        openReport(target.dataset.plot);
        document.getElementById('public-out').scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
});

// Deep link support: /risk-scoring#plot-3 opens that plot's report
// (used by the dashboard "Inspect" actions and the M6 conflict list).
if (location.hash) {
    const plotId = decodeURIComponent(location.hash.slice(1));
    openReport(plotId);
}
</script>
@endpush

@section('content')
<x-page-header icon="warning" :title="__('pages.risk-scoring.page_title')" :subtitle="__('pages.risk-scoring.page_sub')">
    <x-slot:actions>
        <span class="sim-badge">{{ __('pages.risk-scoring.verify_badge') }}</span>
    </x-slot:actions>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="card" aria-labelledby="risk-index-title">
        <div class="card-header">
            <div>
                <h2 id="risk-index-title" class="card-title">{{ __('pages.risk-scoring.index_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.risk-scoring.index_note') }}</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[460px]">
                <thead>
                    <tr>
                        <th scope="col">{{ __('pages.risk-scoring.col_plot') }}</th>
                        <th scope="col">{{ __('pages.risk-scoring.col_score') }}</th>
                        <th scope="col">{{ __('pages.risk-scoring.col_band') }}</th>
                    </tr>
                </thead>
                <tbody id="risk-table"></tbody>
            </table>
        </div>
    </section>

    <section class="card" aria-labelledby="public-report-title">
        <div class="card-header">
            <div>
                <h2 id="public-report-title" class="card-title">{{ __('pages.risk-scoring.verify_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.risk-scoring.verify_hint') }}</p>
            </div>
        </div>
        <div class="card-body">
            <div id="public-out" class="text-sm text-slate-500" role="status">{{ __('pages.risk-scoring.verify_empty') }}</div>
        </div>
    </section>
</div>
@endsection
