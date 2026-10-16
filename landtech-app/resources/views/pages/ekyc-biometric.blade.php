@extends('layouts.app')
@section('title', 'e-KYC') @section('page-title', 'M4 · e-KYC & Biometric Gateway')
@section('page-sub', 'Simulated Porichoy verification + optional real browser camera (photos stay local)')

@push('scripts')
<script type="module">
import { verifyKyc, startCamera, stopCamera, capturePhoto, getKycHistory } from '/js/kyc.js';

const video = document.getElementById('cam'), canvas = document.getElementById('snap');
document.getElementById('btn-cam').onclick = async () => {
    try { await startCamera(video); document.getElementById('cam-note').textContent = '📷 Real camera active (local only — nothing uploaded).'; }
    catch (e) { document.getElementById('cam-note').textContent = 'Camera unavailable: ' + e.message; }
};
document.getElementById('btn-cam-off').onclick = () => stopCamera(video);
document.getElementById('btn-snap').onclick = () => {
    capturePhoto(video, canvas);
    canvas.classList.remove('hidden');
};

document.getElementById('btn-kyc').onclick = () => {
    const r = verifyKyc(document.getElementById('kyc-nid').value || 'SIM-123456', document.getElementById('kyc-name').value || 'Simulated Buyer');
    document.getElementById('kyc-out').innerHTML = `
        <div class="p-3 rounded ${r.status === 'VERIFIED' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'} text-sm">
            ${r.status === 'VERIFIED' ? '✅' : '🚫'} <strong>${r.status}</strong> · confidence ${(r.confidence * 100).toFixed(1)}%<br>
            Face match: ${r.faceMatch ?? 'n/a'} · Liveness: ${r.liveness ?? 'n/a'}<br>
            <em class="text-xs">${r.provider}</em>
        </div>`;
    renderHistory();
};

function renderHistory() {
    document.getElementById('kyc-history').innerHTML = getKycHistory().slice(0, 10).map(k => `
        <tr class="border-b border-slate-100 text-sm"><td class="py-2">${k.nid}</td><td>${k.name}</td>
        <td><span class="text-xs font-bold px-2 py-0.5 rounded ${k.status === 'VERIFIED' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">${k.status}</span></td>
        <td class="text-xs text-slate-500">${new Date(k.ts).toLocaleTimeString()}</td></tr>`).join('');
}
renderHistory();
</script>
@endpush

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-5 space-y-4">
        <h3 class="font-semibold">Simulated Porichoy e-KYC <span class="sim-badge">No real API call</span></h3>
        <p class="text-xs text-slate-500">Dual-confirmation model demo: document match + facial liveness. Use NIDs of the form <code>SIM-XXXXXX</code>. Any other format fails.</p>
        <input id="kyc-nid" class="input" placeholder="SIM-123456">
        <input id="kyc-name" class="input" placeholder="Buyer name">
        <button id="btn-kyc" class="btn-primary">🪪 Run e-KYC Verification</button>
        <div id="kyc-out"></div>
    </div>
    <div class="card p-5 space-y-3">
        <h3 class="font-semibold">Biometric Capture (real camera, local-only) <span class="sim-badge">Nothing uploaded</span></h3>
        <video id="cam" class="rounded-lg w-full bg-slate-900" autoplay playsinline muted></video>
        <canvas id="snap" class="hidden rounded-lg w-48"></canvas>
        <div class="flex gap-2">
            <button id="btn-cam" class="btn-primary text-sm">Start Camera</button>
            <button id="btn-snap" class="btn-ghost text-sm">Capture</button>
            <button id="btn-cam-off" class="btn-ghost text-sm">Stop</button>
        </div>
        <p id="cam-note" class="text-xs text-slate-500"></p>
    </div>
</div>
<div class="card p-5 mt-6">
    <h3 class="font-semibold mb-2">Verification History</h3>
    <table class="w-full"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">NID</th><th>Name</th><th>Status</th><th>Time</th></tr></thead><tbody id="kyc-history"></tbody></table>
</div>
@endsection
