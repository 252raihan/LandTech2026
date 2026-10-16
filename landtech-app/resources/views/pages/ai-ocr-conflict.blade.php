@extends('layouts.app')
@section('title', 'OCR Conflict') @section('page-title', 'M6 · AI/OCR Conflict Detection Engine')
@section('page-sub', 'Simulated OCR extraction + rule-based fraud detection')

@push('scripts')
<script type="module">
import { getDeeds, getPlots } from '/js/store.js';
import { simulateOcrExtract, detectConflicts, getLatestScan } from '/js/ocr.js';

const sel = document.getElementById('ocr-deed');
getDeeds().forEach(d => sel.add(new Option(`${d.deedNo} (plot ${d.plotId})`, d.id)));

const SEV = { CRITICAL: 'bg-red-100 text-red-700', HIGH: 'bg-orange-100 text-orange-700', MEDIUM: 'bg-amber-100 text-amber-700', LOW: 'bg-slate-100 text-slate-600' };

sel.onchange = () => {
    const x = simulateOcrExtract(sel.value);
    document.getElementById('ocr-out').innerHTML = x ? `
        <div class="text-sm space-y-1"><div>Confidence: <strong>${(x.confidence * 100).toFixed(1)}%</strong></div>
        <pre class="bg-slate-900 text-green-300 p-3 rounded text-xs overflow-auto">${JSON.stringify(x.extracted, null, 2)}</pre>
        <em class="text-xs text-slate-500">${x.engine}</em></div>` : '';
};

async function runScan() {
    const conflicts = await detectConflicts();
    document.getElementById('conflict-list').innerHTML = conflicts.length ? conflicts.map(c => `
        <div class="flex items-start gap-3 py-3 border-b border-slate-100 text-sm">
            <span class="text-xs font-bold px-2 py-1 rounded ${SEV[c.severity]}">${c.severity}</span>
            <div><strong class="font-mono text-xs">${c.type}</strong> — plot ${c.plotNo}<div class="text-slate-600">${c.detail}</div></div>
        </div>`).join('') : '<p class="text-sm text-green-600">✅ No conflicts detected.</p>';
}
document.getElementById('btn-scan').onclick = runScan;
runScan(); // auto-run on load
</script>
@endpush

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-5">
        <h3 class="font-semibold mb-3">Simulated OCR Extraction <span class="sim-badge">Rule-based, no real AI model</span></h3>
        <select id="ocr-deed" class="input mb-3"></select>
        <div id="ocr-out" class="text-sm text-slate-500">Select a deed to "scan".</div>
    </div>
    <div class="card p-5">
        <h3 class="font-semibold mb-3">Conflict Detection <button id="btn-scan" class="btn-primary text-xs ml-2">🔍 Run Full Scan</button></h3>
        <p class="text-xs text-slate-500 mb-2">Detects: multiple sale of same plot · rapid transfers (&lt;30 days) · registration-vs-mutation mismatch · cross-survey (CS/SA/RS/BS) inconsistency.</p>
        <div id="conflict-list"></div>
    </div>
</div>
@endsection
