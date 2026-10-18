@extends('layouts.app')
@section('title', __('pages.saga.title')) @section('page-title', __('pages.saga.page_title'))
@section('page-sub', __('pages.saga.page_sub'))

@push('scripts')
<script type="module">
import { runSaga, SAGA_STEPS } from '/js/saga.js';
import { getPlots, getDeeds } from '/js/store.js';
import { toast } from '/js/ui.js';

const plots = getPlots(), deeds = getDeeds();
const plotSel = document.getElementById('saga-plot'), deedSel = document.getElementById('saga-deed');
plots.forEach(p => plotSel.add(new Option(`${p.plotNo} — ${p.mouza} (${p.mutationStatus})`, p.id)));
deeds.forEach(d => deedSel.add(new Option(`${d.deedNo} — plot ${d.plotId}`, d.id)));

plotSel.onchange = () => {
    deedSel.innerHTML = '';
    const list = deeds.filter(d => d.plotId === plotSel.value);
    if (!list.length) {
        deedSel.add(new Option(__t('pages.saga.no_deed_for_plot'), ''));
    } else {
        list.forEach(d => deedSel.add(new Option(d.deedNo, d.id)));
    }
};
plotSel.onchange();

const failSel = document.getElementById('saga-fail');
['', ...SAGA_STEPS.map(s => s.key)].forEach(k => failSel.add(new Option(k || __t('pages.saga.no_injected_failure'), k)));

function renderSteps(states = {}) {
    document.getElementById('saga-steps').innerHTML = SAGA_STEPS.map((s, i) => {
        const st = states[s.key];
        const badge = st === 'COMPLETED' ? 'badge-green' : st === 'FAILED' ? 'badge-red'
            : st === 'COMPENSATED' ? 'badge-amber' : 'badge-slate';
        const label = __t('common.' + (st ? st.toLowerCase() : 'pending'));
        return `<li class="flex items-center gap-3 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">${i + 1}</span>
            <span class="min-w-0 flex-1 text-sm text-slate-700">${__tHtml(s.i18n)}</span>
            <span class="badge ${badge}">${__esc(label)}</span>
        </li>`;
    }).join('');
}
renderSteps();

document.getElementById('saga-run').onclick = async (e) => {
    const btn = e.currentTarget;
    const out = document.getElementById('saga-out');
    const label = btn.querySelector('span');
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    label.textContent = __t('pages.saga.running');
    out.innerHTML = `<p class="flex items-center gap-2 text-sm text-slate-500">
        <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-300 border-t-brand-600"></span>
        ${__esc(__t('pages.saga.running'))}</p>`;

    try {
        const r = await runSaga({
            plotId: plotSel.value, deedId: deedSel.value,
            buyerNid: document.getElementById('saga-nid').value || __t('pages.saga.default_buyer_nid'),
            buyerName: document.getElementById('saga-name').value || __t('pages.saga.default_buyer_name'),
            failAt: failSel.value || null,
        });

        const states = {};
        r.log.forEach(l => states[l.key] = l.status);
        renderSteps(states);

        const statusBadge = r.status === 'COMPLETED' ? 'badge-green' : 'badge-red';
        out.innerHTML = `
            <div class="mb-3 flex items-center gap-2">
                <span class="badge ${statusBadge}">${__esc(r.status)}</span>
            </div>
            <details class="group">
                <summary class="menu-summary text-sm font-semibold text-brand-700">
                    ${__esc(__t('pages.saga.log_heading'))}
                </summary>
                <pre class="code-block mt-2">${__esc(JSON.stringify(r, null, 2))}</pre>
            </details>`;

        toast(r.status === 'COMPLETED'
            ? __t('pages.saga.completed_toast')
            : __t('pages.saga.aborted_toast'), r.status === 'COMPLETED' ? 'success' : 'error');
    } catch {
        out.innerHTML = `<div class="alert alert-danger">${__esc(__t('error_generic'))}</div>`;
        toast(__t('error_generic'), 'error');
    } finally {
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        label.textContent = __t('pages.saga.execute');
    }
};
</script>
@endpush

@section('content')
<x-page-header icon="saga" :title="__('pages.saga.page_title')" :subtitle="__('pages.saga.page_sub')" />
<div class="grid gap-6 lg:grid-cols-2">
    <section class="card" aria-labelledby="saga-params-title">
        <div class="card-header">
            <h2 id="saga-params-title" class="card-title">{{ __('pages.saga.params_heading') }}</h2>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label for="saga-plot" class="label">{{ __('pages.saga.label_plot') }}</label>
                <select id="saga-plot" class="input"></select>
            </div>
            <div>
                <label for="saga-deed" class="label">{{ __('pages.saga.label_deed') }}</label>
                <select id="saga-deed" class="input"></select>
            </div>
            <div>
                <label for="saga-nid" class="label">
                    {{ __('pages.saga.label_buyer_nid') }}
                    <span class="font-normal text-slate-400">{{ __('pages.saga.label_buyer_nid_hint') }}</span>
                </label>
                <input id="saga-nid" class="input" placeholder="SIM-123456" inputmode="text" autocomplete="off">
            </div>
            <div>
                <label for="saga-name" class="label">{{ __('pages.saga.label_buyer_name') }}</label>
                <input id="saga-name" class="input" placeholder="{{ __('pages.saga.default_buyer_name') }}" autocomplete="off">
            </div>
            <div>
                <label for="saga-fail" class="label">{{ __('pages.saga.label_inject_failure') }}</label>
                <select id="saga-fail" class="input"></select>
                <p class="hint">{{ __('pages.saga.failure_hint') }}</p>
            </div>
            <button type="button" id="saga-run" class="btn-primary w-full">
                <span>{{ __('pages.saga.execute') }}</span>
            </button>
        </div>
    </section>

    <section class="card" aria-labelledby="saga-steps-title">
        <div class="card-header">
            <div>
                <h2 id="saga-steps-title" class="card-title">{{ __('pages.saga.steps_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.saga.steps_badge') }}</p>
            </div>
        </div>
        <div class="card-body">
            <ol id="saga-steps" class="divide-y divide-slate-100"></ol>
            <div id="saga-out" class="mt-4 text-sm text-slate-500">{{ __('pages.saga.idle_hint') }}</div>
        </div>
    </section>
</div>
@endsection
