@extends('layouts.app')
@section('title', 'Saga') @section('page-title', 'Saga Orchestration Workflow')
@section('page-sub', 'Simulated distributed transaction with compensating actions')

@push('scripts')
<script type="module">
import { runSaga, SAGA_STEPS } from '/js/saga.js';
import { getPlots, getDeeds } from '/js/store.js';

const plots = getPlots(), deeds = getDeeds();
const plotSel = document.getElementById('saga-plot'), deedSel = document.getElementById('saga-deed');
plots.forEach(p => plotSel.add(new Option(`${p.plotNo} — ${p.mouza} (${p.mutationStatus})`, p.id)));
deeds.forEach(d => deedSel.add(new Option(`${d.deedNo} — plot ${d.plotId}`, d.id)));

plotSel.onchange = () => {
    deedSel.innerHTML = '';
    deeds.filter(d => d.plotId === plotSel.value).forEach(d => deedSel.add(new Option(d.deedNo, d.id)));
};
plotSel.onchange();

const failSel = document.getElementById('saga-fail');
['', ...SAGA_STEPS.map(s => s.key)].forEach(k => failSel.add(new Option(k || 'No injected failure', k)));

function renderSteps(states) {
    document.getElementById('saga-steps').innerHTML = SAGA_STEPS.map(s => {
        const st = states[s.key];
        const badge = st === 'COMPLETED' ? 'bg-green-100 text-green-700' : st === 'FAILED' ? 'bg-red-100 text-red-700'
            : st === 'COMPENSATED' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500';
        return `<div class="flex items-center justify-between py-2.5 border-b border-slate-100">
            <span>${s.name}</span><span class="text-xs font-bold px-2 py-1 rounded ${badge}">${st ?? 'PENDING'}</span></div>`;
    }).join('');
}

document.getElementById('saga-run').onclick = async () => {
    const states = {}; renderSteps(states);
    const out = document.getElementById('saga-out');
    out.textContent = 'Running…';
    const r = await runSaga({
        plotId: plotSel.value, deedId: deedSel.value,
        buyerNid: document.getElementById('saga-nid').value || 'SIM-123456',
        buyerName: document.getElementById('saga-name').value || 'Simulated Buyer',
        failAt: failSel.value || null,
    });
    r.log.forEach(l => states[l.key] = l.status);
    if (r.status === 'ABORTED') r.compensations.forEach(() => {});
    renderSteps(states);
    out.innerHTML = `<pre class="text-xs bg-slate-900 text-green-300 p-4 rounded-lg overflow-auto">${JSON.stringify(r, null, 2)}</pre>`;
};
</script>
@endpush

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-5">
        <h3 class="font-semibold mb-4">Transaction Parameters</h3>
        <div class="space-y-4">
            <div><label class="text-sm font-medium">Plot</label><select id="saga-plot" class="input mt-1"></select></div>
            <div><label class="text-sm font-medium">Deed</label><select id="saga-deed" class="input mt-1"></select></div>
            <div><label class="text-sm font-medium">Buyer NID <span class="text-xs text-slate-400">(use SIM-123456)</span></label>
                <input id="saga-nid" class="input mt-1" placeholder="SIM-123456"></div>
            <div><label class="text-sm font-medium">Buyer Name</label><input id="saga-name" class="input mt-1" placeholder="Simulated Buyer"></div>
            <div><label class="text-sm font-medium">Inject simulated failure at step</label><select id="saga-fail" class="input mt-1"></select></div>
            <button id="saga-run" class="btn-primary w-full justify-center">▶ Execute Saga</button>
        </div>
    </div>
    <div class="card p-5">
        <h3 class="font-semibold mb-2">Saga Steps <span class="sim-badge ml-2">Simulated Redlock · Porichoy · CIB · PostGIS</span></h3>
        <div id="saga-steps"></div>
        <div id="saga-out" class="mt-4 text-sm text-slate-500">Run a saga to see the orchestration log, failure injection and compensating transactions.</div>
    </div>
</div>
@endsection
