@extends('layouts.app')
@section('title', 'Plot Lock') @section('page-title', 'M3 · Real-Time Plot Lock Service')
@section('page-sub', 'Simulated Redis Redlock — prevents concurrent double-sale transactions')

@push('scripts')
<script type="module">
import { getPlots, getLocks, saveLocks } from '/js/store.js';
import { acquireLock, releaseLock, activeLocks } from '/js/locks.js';

function render() {
    const plots = getPlots(), locks = activeLocks();
    document.getElementById('lock-table').innerHTML = plots.map(p => {
        const lock = locks[p.id];
        return `<tr class="border-b border-slate-100">
            <td class="py-2 font-mono text-xs">${p.redlockKey}</td>
            <td>${p.plotNo}</td>
            <td>${lock ? `<span class="text-xs font-bold px-2 py-1 rounded bg-red-100 text-red-700">LOCKED by ${lock.holder}</span>` : '<span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700">FREE</span>'}</td>
            <td>${lock ? new Date(lock.expiresAt).toLocaleTimeString() : '—'}</td>
            <td class="space-x-2">
                ${lock ? `<button class="btn-ghost text-xs" onclick="window.__rel('${p.id}','${lock.holder}')">Release</button>`
                       : `<button class="btn-primary text-xs" onclick="window.__acq('${p.id}')">Acquire</button>`}
            </td></tr>`;
    }).join('');
}
window.__acq = (id) => {
    const r = acquireLock(id, 'officer-demo');
    alert(r.ok ? '🔒 Lock acquired (simulated Redlock)' : '⚠️ ' + r.reason);
    render();
};
window.__rel = (id, h) => {
    const r = releaseLock(id, h);
    alert(r.ok ? '🔓 Released' : '⚠️ ' + r.reason);
    render();
};
render();
setInterval(render, 5000); // show TTL expiry live
</script>
@endpush

@section('content')
<div class="card p-5 mb-6">
    <p class="text-sm text-slate-600">
        Uses a <code class="bg-slate-100 px-1 rounded">LOCK:LAND:&lt;District&gt;:&lt;Upazila&gt;:&lt;Mouza&gt;:&lt;Khatian&gt;:&lt;Plot&gt;</code> key
        pattern with a 5-minute TTL, exactly as in the architecture. <span class="sim-badge ml-2">Simulated Redlock — no real Redis server</span>
    </p>
</div>
<div class="card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr><th class="px-4 py-3">Redlock Key</th><th class="px-4 py-3">Plot</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Expires (TTL)</th><th class="px-4 py-3">Action</th></tr>
        </thead>
        <tbody id="lock-table"></tbody>
    </table>
</div>
@endsection
