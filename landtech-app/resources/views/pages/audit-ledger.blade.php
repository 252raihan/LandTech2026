@extends('layouts.app')
@section('title', 'Audit Ledger') @section('page-title', 'M10 · Immutable Audit Ledger')
@section('page-sub', 'Hash-chained append-only ledger (real Web Crypto SHA-256)')

@push('scripts')
<script type="module">
import { getLedger } from '/js/store.js';
import { verifyLedgerChain, tamperLedger, appendLedger } from '/js/crypto.js';

function render(status = null) {
    const ledger = getLedger();
    document.getElementById('chain-status').innerHTML = status
        ? (status.ok ? '<span class="text-xs font-bold px-2 py-1 rounded bg-green-100 text-green-700">CHAIN VALID ✓</span>'
                    : `<span class="text-xs font-bold px-2 py-1 rounded bg-red-100 text-red-700">CHAIN BROKEN at block ${status.brokenAt} 🚨</span>`)
        : '<span class="text-xs text-slate-500">Run verification…</span>';
    document.getElementById('ledger-table').innerHTML = ledger.length ? ledger.slice().reverse().map(e => `
        <tr class="border-b border-slate-100 text-xs">
            <td class="py-2 font-bold">#${e.seq}</td>
            <td class="font-mono">${e.action}</td>
            <td class="font-mono text-[10px] break-all">${JSON.stringify(e.data)}</td>
            <td class="font-mono text-[10px] break-all text-slate-500">${e.prevHash.slice(0, 12)}…</td>
            <td class="font-mono text-[10px] break-all">${e.blockHash.slice(0, 12)}…</td>
            <td class="text-slate-400">${new Date(e.ts).toLocaleTimeString()}</td>
        </tr>`).join('') : '<tr><td colspan="6" class="py-4 text-sm text-slate-500">Ledger empty — run the Saga, hash deeds or detect conflicts to create blocks.</td></tr>';
}

document.getElementById('btn-verify').onclick = async () => {
    const st = await verifyLedgerChain();
    render(st);
};
document.getElementById('btn-tamper').onclick = async () => {
    if (tamperLedger()) {
        const st = await verifyLedgerChain();
        render(st);
    } else alert('Ledger empty.');
};
document.getElementById('btn-entry').onclick = async () => {
    await appendLedger('MANUAL_NOTE', { note: 'Officer annotation (simulated)' });
    render();
};
render();
</script>
@endpush

@section('content')
<div class="card p-5 mb-6">
    <p class="text-sm text-slate-600">
        Each block stores <code class="bg-slate-100 px-1 rounded">prevHash</code> and a SHA-256 of its content — tampering with any block breaks the chain,
        mimicking a Hyperledger Fabric / append-only design. <span class="sim-badge">Browser simulation — no real blockchain</span>
    </p>
    <div class="mt-3 flex gap-2 items-center">
        <button id="btn-verify" class="btn-primary text-sm">⛓ Verify Chain</button>
        <button id="btn-tamper" class="btn-danger text-sm">🛠 Simulate Tamper Attack</button>
        <button id="btn-entry" class="btn-ghost text-sm">+ Append Note</button>
        <span id="chain-status" class="ml-2"></span>
    </div>
</div>
<div class="card overflow-x-auto">
    <table class="w-full text-xs"><thead class="bg-slate-50 text-left uppercase text-slate-500">
    <tr><th class="px-4 py-3">Seq</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Data</th><th class="px-4 py-3">Prev Hash</th><th class="px-4 py-3">Block Hash</th><th class="px-4 py-3">Time</th></tr></thead>
    <tbody id="ledger-table"></tbody></table>
</div>
@endsection
