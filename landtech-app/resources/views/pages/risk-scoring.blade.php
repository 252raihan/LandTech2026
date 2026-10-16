@extends('layouts.app')
@section('title', 'Risk Scoring') @section('page-title', 'M9 · Public Risk Scoring Engine')
@section('page-sub', 'Public "check before you buy" plot risk ratings (P5 citizen tool)')

@push('scripts')
<script type="module">
import { getPlots } from '/js/store.js';
import { scoreAll, publicCheck } from '/js/risk.js';

function bandBadge(b) {
    const cls = { red: 'bg-red-100 text-red-700', amber: 'bg-amber-100 text-amber-700', green: 'bg-green-100 text-green-700' }[b.color];
    return `<span class="text-xs font-bold px-2 py-0.5 rounded ${cls}">${b.label}</span>`;
}

function render() {
    const scores = scoreAll();
    document.getElementById('risk-table').innerHTML = scores.map(s => `
        <tr class="border-b border-slate-100 text-sm cursor-pointer hover:bg-slate-50" data-plot="${s.id}">
            <td class="py-2.5">${s.plotNo}</td>
            <td class="font-bold">${s.score}/100</td>
            <td>${bandBadge(s.band)}</td></tr>`).join('');
}
render();

document.getElementById('risk-table').addEventListener('click', e => {
    const row = e.target.closest('[data-plot]');
    if (!row) return;
    const c = publicCheck(row.dataset.plot);
    document.getElementById('public-out').innerHTML = `
        <h4 class="font-bold">Plot ${c.plot.plotNo} — Citizen Pre-Purchase Report</h4>
        <div class="text-sm space-y-1.5 mt-3">
            <div>Risk: <strong>${c.risk.score}/100</strong> ${bandBadge(c.risk.band)}</div>
            <div>Owner: ${c.plot.owner} <em class="text-xs text-slate-400">(synthetic)</em></div>
            <div>Mutation status: <strong>${c.mutation}</strong></div>
            <div>Deeds registered: ${c.deedCount}</div>
            <div>Status: ${c.khasOrAcquisition}</div>
            ${c.risk.reasons.length ? `<div class="mt-2"><strong>⚠️ Risk factors:</strong><ul class="list-disc list-inside text-red-600">${c.risk.reasons.map(r => `<li>${r}</li>`).join('')}</ul></div>` : '<div class="text-green-600 mt-2">✅ No risk factors detected.</div>'}
        </div>`;
});
</script>
@endpush

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-5">
        <h3 class="font-semibold mb-2">Plot Risk Index (click for report)</h3>
        <table class="w-full"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">Plot</th><th>Score</th><th>Band</th></tr></thead>
        <tbody id="risk-table"></tbody></table>
    </div>
    <div class="card p-5">
        <h3 class="font-semibold mb-2">Public Pre-Purchase Verification <span class="sim-badge">Synthetic data only</span></h3>
        <div id="public-out" class="text-sm text-slate-500">Click a plot on the left…</div>
    </div>
</div>
@endsection
