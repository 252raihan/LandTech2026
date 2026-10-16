@extends('layouts.app')
@section('title', 'Crypto Verify') @section('page-title', 'M2 · Cryptographic Verification Engine')
@section('page-sub', 'REAL Web Crypto SHA-256 hashing + simulated Dynamic QR')

@push('scripts')
<script type="module">
import { getDeeds, saveDeeds } from '/js/store.js';
import { hashAllDeeds, computeDeedHash, dynamicQrPayload, verifyDeed } from '/js/crypto.js';

const deeds = getDeeds();
const sel = document.getElementById('deed-sel');
deeds.forEach(d => sel.add(new Option(`${d.deedNo} (plot ${d.plotId})`, d.id)));

function renderDeeds() {
    document.getElementById('deed-table').innerHTML = getDeeds().map(d => `
    <tr class="border-b border-slate-100"><td class="py-2">${d.deedNo}</td><td>${d.seller} → ${d.buyer}</td>
    <td class="font-mono text-[10px] break-all">${d.hash ?? '<em class="text-amber-600">not hashed</em>'}</td></tr>`).join('');
}
renderDeeds();

document.getElementById('btn-hash').onclick = async () => {
    const n = await hashAllDeeds();
    renderDeeds();
    document.getElementById('verify-out').textContent = `✅ Hashed ${n} deed(s) with SHA-256 (real Web Crypto) and registered on the simulated ledger.`;
};

sel.onchange = async () => {
    const d = getDeeds().find(x => x.id === sel.value);
    document.getElementById('canonical').value = JSON.stringify({
        deedNo: d.deedNo, plotId: d.plotId, seller: d.seller, sellerNid: d.sellerNid,
        buyer: d.buyer, buyerNid: d.buyerNid, registeredAt: d.registeredAt, registryOffice: d.registryOffice,
    }, null, 1);
    document.getElementById('qr-payload').textContent = await dynamicQrPayload(d);
};

document.getElementById('btn-verify').onclick = async () => {
    const json = document.getElementById('canonical').value;
    const r = await verifyDeed(sel.value, json);
    document.getElementById('verify-out').innerHTML = r.ok
        ? `<div class="p-3 rounded bg-green-50 text-green-800 text-sm">✅ VERIFIED — recomputed SHA-256 matches registered hash.<br><code class="text-[10px]">${r.recomputed}</code></div>`
        : `<div class="p-3 rounded bg-red-50 text-red-800 text-sm">🚨 TAMPER DETECTED — hash mismatch.<br>Registered: <code class="text-[10px]">${r.registered ?? r.reason}</code><br>Recomputed: <code class="text-[10px]">${r.recomputed ?? ''}</code></div>`;
    renderDeeds();
};

document.getElementById('btn-tamper').onclick = () => {
    document.getElementById('canonical').value = document.getElementById('canonical').value.replace('"buyer"', '"buyer (FORGED)"');
    document.getElementById('verify-out').textContent = 'Document content modified — press Verify to detect the forgery.';
};
</script>
@endpush

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-5">
        <h3 class="font-semibold mb-3">Deed Registry <button id="btn-hash" class="btn-primary text-xs ml-2">🔐 Hash All Deeds (SHA-256)</button></h3>
        <table class="w-full text-xs"><thead class="text-left text-slate-500 uppercase"><tr><th class="py-2">Deed</th><th>Parties</th><th>SHA-256</th></tr></thead>
        <tbody id="deed-table"></tbody></table>
    </div>
    <div class="card p-5 space-y-4">
        <h3 class="font-semibold">Verify Document Integrity</h3>
        <select id="deed-sel" class="input"></select>
        <textarea id="canonical" class="input font-mono text-[11px]" rows="8"></textarea>
        <div class="flex gap-2">
            <button id="btn-verify" class="btn-primary">✔ Verify Hash</button>
            <button id="btn-tamper" class="btn-danger">🛠 Simulate Forgery (edit content)</button>
        </div>
        <div class="p-3 bg-slate-50 rounded text-xs"><strong>Dynamic QR payload:</strong><div id="qr-payload" class="font-mono mt-1 break-all text-slate-600">select a deed…</div></div>
        <div id="verify-out" class="text-sm"></div>
    </div>
</div>
@endsection
