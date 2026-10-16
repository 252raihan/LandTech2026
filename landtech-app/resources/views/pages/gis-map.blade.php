@extends('layouts.app')
@section('title', 'GIS Map') @section('page-title', 'M8 · GIS Spatial Mapping Layer')
@section('page-sub', 'Leaflet + OpenStreetMap with simulated PostGIS overlap detection')

@push('scripts')
<script type="module">
import { initMap, renderPlots, findOverlaps, focusPlot } from '/js/map.js';
import { getPlots } from '/js/store.js';

initMap('gis-map');
const res = renderPlots(p => {
    document.getElementById('plot-detail').innerHTML = `
        <h4 class="font-bold">${p.plotNo}</h4>
        <div class="text-sm space-y-1 mt-2">
            <div>📍 ${p.district} > ${p.upazila} > ${p.mouza}</div>
            <div>🗂 Khatian: ${p.khatian} (${p.survey})</div>
            <div>👤 Owner: ${p.owner} <em class="text-xs text-slate-400">(synthetic)</em></div>
            <div>📐 Area: ${p.areaDecimal} decimal</div>
            <div>Mutation: <strong>${p.mutationStatus}</strong></div>
            <div class="font-mono text-[10px] break-all">${p.redlockKey}</div>
        </div>`;
});

document.getElementById('ov-count').textContent = res.overlaps.length;
document.getElementById('ov-list').innerHTML = res.overlaps.length
    ? res.overlaps.map(([a, b]) => {
        const A = getPlots().find(p => p.id === a), B = getPlots().find(p => p.id === b);
        if (!A || !B) return '';
        return `<li class="text-sm py-1"><button class="text-blue-600 hover:underline" data-focus="${a}">${A.plotNo}</button> ⛔ overlaps
                <button class="text-blue-600 hover:underline" data-focus="${b}">${B.plotNo}</button></li>`;
    }).join('') : '<li class="text-sm text-slate-500">No overlaps among current synthetic plots.</li>';

document.getElementById('ov-list').addEventListener('click', e => {
    const id = e.target.dataset.focus;
    if (id) focusPlot(id);
});
</script>
@endpush

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div id="gis-map" class="card h-[520px] z-0"></div>
    </div>
    <div class="space-y-6">
        <div class="card p-5">
            <h3 class="font-semibold">Overlap Report <span class="sim-badge">Simulated ST_Overlaps</span></h3>
            <p class="text-3xl font-bold mt-2" id="ov-count">0</p>
            <ul id="ov-list" class="mt-2 divide-y divide-slate-100"></ul>
        </div>
        <div class="card p-5"><h3 class="font-semibold mb-2">Plot Details</h3><div id="plot-detail" class="text-sm text-slate-500">Click a plot polygon…</div></div>
    </div>
</div>
@endsection
