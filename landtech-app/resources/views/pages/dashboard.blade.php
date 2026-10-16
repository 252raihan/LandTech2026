@extends('layouts.app')
@section('title', 'Dashboard') @section('page-title', 'Command Dashboard')
@section('page-sub', 'Fraud & Multiple Sale Detection — all data synthetic')

@push('scripts')
<script type="module">
import { getPlots, getDeeds, getEvents, getKyc, resetAll } from '/js/store.js';
import { seedAll } from '/js/seed.js';
import { scoreAll } from '/js/risk.js';
import { detectConflicts } from '/js/ocr.js';

if (!getPlots().length) seedAll();
const plots = getPlots(), deeds = getDeeds(), events = getEvents(), kyc = getKyc();

// Stat cards
const scores = scoreAll();
const high = scores.filter(s => s.band.label === 'HIGH RISK').length;
document.getElementById('stat-plots').textContent = plots.length;
document.getElementById('stat-deeds').textContent = deeds.length;
document.getElementById('stat-alerts').textContent = high;
document.getElementById('stat-kyc').textContent = kyc.filter(k => k.status === 'VERIFIED').length;

// Charts
new Chart(document.getElementById('ch-risk'), {
    type: 'bar',
    data: { labels: scores.map(s => s.plotNo), datasets: [{ label: 'Risk score', data: scores.map(s => s.score),
        backgroundColor: scores.map(s => s.band.label === 'HIGH RISK' ? '#dc2626' : s.band.label === 'CAUTION' ? '#f59e0b' : '#16a34a') }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { max: 100 } } }
});
const types = {};
events.forEach(e => types[e.type] = (types[e.type] || 0) + 1);
new Chart(document.getElementById('ch-events'), {
    type: 'doughnut',
    data: { labels: Object.keys(types), datasets: [{ data: Object.values(types) }] },
    options: { plugins: { title: { display: true, text: 'Simulated webhook events by type' } } }
});

document.getElementById('btn-rescan').onclick = async () => {
    await detectConflicts();
    location.reload();
};
document.getElementById('btn-reset').onclick = () => { resetAll(); location.reload(); };
</script>
@endpush

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php($cards = [
        ['stat-plots', 'Plots (synthetic)', '🗺️', 'bg-blue-50 text-blue-700'],
        ['stat-deeds', 'Registered deeds', '📜', 'bg-indigo-50 text-indigo-700'],
        ['stat-alerts', 'High-risk plots', '🚨', 'bg-red-50 text-red-700'],
        ['stat-kyc', 'KYC verified', '✅', 'bg-green-50 text-green-700'],
    ])
    @foreach ($cards as [$id, $label, $icon, $cls])
        <div class="card p-5">
            <div class="text-2xl">{{ $icon }}</div>
            <div id="{{ $id }}" class="text-3xl font-bold mt-2">—</div>
            <div class="text-sm text-slate-500">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="card p-5 lg:col-span-2"><h3 class="font-semibold mb-3">Public Risk Scores (M9)</h3><canvas id="ch-risk" height="110"></canvas></div>
    <div class="card p-5"><h3 class="font-semibold mb-3">Event Feed (M1)</h3><canvas id="ch-events" height="180"></canvas></div>
</div>

<div class="mt-6 flex gap-3">
    <button id="btn-rescan" class="btn-primary">🔄 Run Fraud Scan</button>
    <button id="btn-reset" class="btn-danger">♻️ Reset Demo Data</button>
</div>
@endsection
