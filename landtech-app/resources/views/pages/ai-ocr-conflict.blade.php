@extends('layouts.app')
@section('title', __('pages.ai-ocr-conflict.title')) @section('page-title', __('pages.ai-ocr-conflict.page_title'))
@section('page-sub', __('pages.ai-ocr-conflict.page_sub'))

@push('scripts')
<script type="module">
import { getDeeds } from '/js/store.js';
import { simulateOcrExtract, detectConflicts } from '/js/ocr.js';
import { toast } from '/js/ui.js';

const sel = document.getElementById('ocr-deed');
getDeeds().forEach(d => sel.add(new Option(`${d.deedNo} (plot ${d.plotId})`, d.id)));

const SEV = {
    CRITICAL: 'badge-red',
    HIGH: 'badge-red',
    MEDIUM: 'badge-amber',
    LOW: 'badge-slate',
};

sel.onchange = () => {
    const x = simulateOcrExtract(sel.value);
    document.getElementById('ocr-out').innerHTML = x ? `
        <div class="space-y-3">
            <p class="text-sm text-slate-600">
                ${__t('pages.ai-ocr-conflict.confidence')}
                <strong>${(x.confidence * 100).toFixed(1)}%</strong>
            </p>
            <pre class="code-block">${__esc(JSON.stringify(x.extracted, null, 2))}</pre>
            <p><em class="text-xs not-italic text-slate-500">${__esc(x.engine)}</em></p>
        </div>` : '';
};

async function runScan() {
    const conflicts = await detectConflicts();
    document.getElementById('conflict-count').textContent =
        __t('pages.ai-ocr-conflict.conflict_count', { count: conflicts.length });

    document.getElementById('conflict-list').innerHTML = conflicts.length ? conflicts.map(c => `
        <li class="flex items-start gap-3 py-3">
            <span class="badge ${SEV[c.severity] ?? 'badge-slate'} shrink-0">${__esc(__t('pages.ai-ocr-conflict.severity_' + c.severity.toLowerCase()))}</span>
            <div class="min-w-0 flex-1">
                <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-700">
                    <span>${__esc(__t('pages.ai-ocr-conflict.type_' + c.type.toLowerCase()))}</span>
                    <span class="badge badge-slate">${__esc(c.plotNo)}</span>
                </p>
                <p class="mt-1 text-sm text-slate-600">${__esc(c.detail)}</p>
            </div>
            <a href="/risk-scoring#${encodeURIComponent(c.plotId)}" class="btn-ghost btn-xs shrink-0">${__esc(__t('dashboard.inspect'))}</a>
        </li>`).join('')
        : `<li class="py-8 text-center">
               <span class="badge badge-green">${__esc(__t('pages.ai-ocr-conflict.no_conflicts'))}</span>
           </li>`;
}

document.getElementById('btn-scan').onclick = async (e) => {
    const btn = e.currentTarget;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    try {
        await runScan();
        toast(__t('pages.ai-ocr-conflict.scan_done'), 'success');
    } finally {
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
    }
};

runScan(); // auto-run on load
</script>
@endpush

@section('content')
<x-page-header icon="cpu" :title="__('pages.ai-ocr-conflict.page_title')" :subtitle="__('pages.ai-ocr-conflict.page_sub')" />

<div class="grid gap-6 lg:grid-cols-2">
    <section class="card" aria-labelledby="ocr-title">
        <div class="card-header">
            <div>
                <h2 id="ocr-title" class="card-title">{{ __('pages.ai-ocr-conflict.ocr_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.ai-ocr-conflict.ocr_hint') }}</p>
            </div>
            <span class="sim-badge">{{ __('pages.ai-ocr-conflict.ocr_badge') }}</span>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label for="ocr-deed" class="label">{{ __('pages.ai-ocr-conflict.select_deed_label') }}</label>
                <select id="ocr-deed" class="input"></select>
            </div>
            <div id="ocr-out" class="text-sm text-slate-500" role="status">
                {{ __('pages.ai-ocr-conflict.ocr_empty') }}
            </div>
        </div>
    </section>

    <section class="card" aria-labelledby="conflict-title">
        <div class="card-header">
            <div>
                <h2 id="conflict-title" class="card-title">{{ __('pages.ai-ocr-conflict.conflict_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.ai-ocr-conflict.detects') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span id="conflict-count" class="badge badge-slate"></span>
                <button type="button" id="btn-scan" class="btn-primary btn-sm">
                    <x-icon name="search" class="h-4 w-4" />
                    <span>{{ __('pages.ai-ocr-conflict.run_scan') }}</span>
                </button>
            </div>
        </div>
        <div class="card-body pt-0">
            <ul id="conflict-list" class="divide-y divide-slate-100"></ul>
        </div>
    </section>
</div>
@endsection
