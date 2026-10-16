@extends('layouts.app')
@section('title', 'Bank Lien') @section('page-title', 'M5 · Bank Lien & Encumbrance Registry')
@section('page-sub', 'Simulated CIB/CBS link — blocks registration until e-NOC issued')

@push('scripts')
<script type="module">
import { getPlots } from '/js/store.js';
import { getLiens as allLiens, addLien, issueENOC } from '/js/liens.js';

const plotSel = document.getElementById('lien-plot');
getPlots().forEach(p => plotSel.add(new Option(p.plotNo, p.id)));

function render() {
    document.getElementById('lien-table').innerHTML = allLiens().length ? allLiens().map(l => {
        const plot = getPlots().find(p => p.id === l.plotId);
        return `<tr class="border-b border-slate-100 text-sm">
        <td class="py-2">${plot?.plotNo ?? l.plotId}</td><td>${l.bank}</td><td>৳ ${Number(l.amount).toLocaleString()}</td>
        <td><span class="text-xs font-bold px-2 py-0.5 rounded ${l.status === 'ACTIVE' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}">${l.status}</span></td>
        <td class="font-mono text-[10px]">${l.eNoc ?? '—'}</td>
        <td>${l.status === 'ACTIVE' ? `<button class="btn-primary text-xs" onclick="window.__noc('${l.id}')">Issue e-NOC</button>` : ''}</td></tr>`;
    }).join('') : '<tr><td colspan="6" class="py-4 text-sm text-slate-500">No liens. Plot-9 has a seeded lien after demo reset.</td></tr>';
}
window.__noc = (id) => { const l = issueENOC(id); alert(l ? `✅ e-NOC ${l.eNoc} issued (simulated)` : 'Failed'); render(); };
render();

document.getElementById('btn-add').onclick = () => {
    const l = addLien(plotSel.value, document.getElementById('lien-bank').value || 'SIM-Bank Ltd (fictional)', document.getElementById('lien-amt').value || 1000000);
    alert(`Lien registered (simulated CIB): ${l.id}`);
    render();
};
</script>
@endpush

@section('content')
<div class="card p-5 mb-6">
    <h3 class="font-semibold mb-3">Register Lien (simulated)</h3>
    <div class="grid sm:grid-cols-4 gap-3 items-end">
        <select id="lien-plot" class="input"></select>
        <input id="lien-bank" class="input" placeholder="Bank (fictional)">
        <input id="lien-amt" class="input" type="number" placeholder="Amount (BDT)">
        <button id="btn-add" class="btn-primary">🏦 Register Lien</button>
    </div>
    <p class="text-xs text-slate-500 mt-3">In production this links to bank CIB/CBS systems and issues an <strong>e-NOC</strong>. <span class="sim-badge">Simulated — no real bank connection</span></p>
</div>
<div class="card overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
    <tr><th class="px-4 py-3">Plot</th><th class="px-4 py-3">Bank</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">e-NOC</th><th class="px-4 py-3">Action</th></tr></thead>
    <tbody id="lien-table"></tbody></table>
</div>
@endsection
