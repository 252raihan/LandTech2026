@extends('layouts.app')
@section('title', 'Faraz Calculator') @section('page-title', 'M7 · Faraz Share Calculator')
@section('page-sub', 'Enforces Σ Shareᵢ = 1.0 and S_transfer ≤ S_legal')

@push('scripts')
<script type="module">
import { validateShares, validateTransfer, equalSplit, fractionToAnkra } from '/js/faraz.js';

let legal = [
    { holder: 'Heir A', share: 0.4 },
    { holder: 'Heir B', share: 0.35 },
    { holder: 'Heir C', share: 0.25 },
];

function renderLegal() {
    document.getElementById('legal-list').innerHTML = legal.map((s, i) => `
        <tr class="border-b border-slate-100 text-sm"><td class="py-2">${s.holder}</td>
        <td>${s.share} <span class="text-xs text-slate-400">(${fractionToAnkra(s.share)})</span></td></tr>`).join('') +
        `<tr class="font-bold"><td class="py-2">Total (must equal 1.0)</td><td id="sum-cell">${validateShares(legal).sum}</td></tr>`;
}
renderLegal();

document.getElementById('btn-split').onclick = () => {
    const n = Number(document.getElementById('heir-count').value || 3);
    legal = equalSplit(Array.from({ length: n }, (_, i) => `Heir ${String.fromCharCode(65 + i)}`));
    renderLegal();
    document.getElementById('transfer-out').textContent = `Estate equally split among ${n} heirs.`;
};

document.getElementById('btn-transfer').onclick = () => {
    const seller = document.getElementById('t-seller').value;
    const share = Number(document.getElementById('t-share').value);
    const r = validateTransfer(legal, { seller, share });
    const v = validateShares(legal);
    document.getElementById('transfer-out').innerHTML = `
        <div class="p-3 rounded text-sm ${r.valid ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}">
            ${r.valid ? '✅' : '🚫'} ${r.message}<br>
            <span class="text-xs">Σ shares = ${v.sum} → ${v.valid ? 'valid' : 'INVALID: shares do not sum to 1.0'}</span>
        </div>`;
};
</script>
@endpush

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-5">
        <h3 class="font-semibold mb-3">Legal (Inherited) Shares</h3>
        <table class="w-full"><tbody id="legal-list"></tbody></table>
        <div class="mt-4 flex gap-2 items-end">
            <div><label class="text-xs text-slate-500">Number of heirs</label><input id="heir-count" class="input w-24" type="number" value="3" min="1" max="8"></div>
            <button id="btn-split" class="btn-ghost">⚖️ Equal Faraz Split</button>
        </div>
    </div>
    <div class="card p-5 space-y-4">
        <h3 class="font-semibold">Proposed Transfer Check</h3>
        <div><label class="text-xs text-slate-500">Seller</label>
            <select id="t-seller" class="input">${''}<option>Heir A</option><option>Heir B</option><option>Heir C</option></select></div>
        <div><label class="text-xs text-slate-500">Transfer share (fraction, e.g. 0.2)</label><input id="t-share" class="input" type="number" step="0.01" value="0.2"></div>
        <button id="btn-transfer" class="btn-primary">🧮 Validate Transfer</button>
        <div id="transfer-out"></div>
        <p class="text-xs text-slate-500">Rule: a seller cannot transfer more than their legal faraz share — prevents fraudulently selling shares one does not own. Pure client-side math.</p>
    </div>
</div>
@endsection
