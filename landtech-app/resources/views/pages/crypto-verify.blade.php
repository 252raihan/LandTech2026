@extends('layouts.app')
@section('title', __('pages.crypto-verify.title')) @section('page-title', __('pages.crypto-verify.page_title'))
@section('page-sub', __('pages.crypto-verify.page_sub'))

@push('scripts')
<script type="module">
import { getDeeds } from '/js/store.js';
import { hashAllDeeds, dynamicQrPayload, verifyDeed } from '/js/crypto.js';
import { toast } from '/js/ui.js';

const sel = document.getElementById('deed-sel');

function fillSelect() {
    const current = sel.value;
    sel.innerHTML = '';
    getDeeds().forEach(d => sel.add(new Option(`${d.deedNo} (plot ${d.plotId})`, d.id)));
    if (getDeeds().some(d => d.id === current)) sel.value = current;
}
fillSelect();

function renderDeeds() {
    const deeds = getDeeds();
    document.getElementById('deed-count').textContent = __t('pages.crypto-verify.deed_count', { count: deeds.length });
    document.getElementById('deed-table').innerHTML = deeds.length
        ? deeds.map(d => `
            <tr>
                <td class="font-medium text-slate-900">${__esc(d.deedNo)}</td>
                <td>${__esc(d.seller)} → ${__esc(d.buyer)}</td>
                <td>${d.hash
                    ? `<span class="badge badge-green">${__esc(__t('pages.crypto-verify.hashed_badge'))}</span>
                       <span class="mono ms-1" title="${__esc(d.hash)}">${__esc(d.hash.slice(0, 16))}…</span>`
                    : `<span class="badge badge-amber">${__esc(__t('pages.crypto-verify.not_hashed'))}</span>`}</td>
            </tr>`).join('')
        : `<tr><td colspan="3" class="table-empty">${__esc(__t('pages.crypto-verify.empty'))}</td></tr>`;
}
renderDeeds();

document.getElementById('btn-hash').onclick = async (e) => {
    const btn = e.currentTarget;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    try {
        const n = await hashAllDeeds();
        renderDeeds();
        document.getElementById('verify-out').innerHTML =
            `<div class="alert alert-success">${__esc(__t('crypto-verify.hashed_result', { count: n }))}</div>`;
        toast(__t('crypto-verify.hashed_toast', { count: n }), 'success');
    } finally {
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
    }
};

sel.onchange = async () => {
    const d = getDeeds().find(x => x.id === sel.value);
    if (!d) return;
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
        ? `<div class="alert alert-success">
               <span class="badge badge-green shrink-0">${__esc(__t('crypto-verify.verified_badge'))}</span>
               <div class="min-w-0">
                   <p>${__t('crypto-verify.verified_result')}</p>
                   <code class="mono mt-1 block">${__esc(r.recomputed)}</code>
               </div>
           </div>`
        : `<div class="alert alert-danger">
               <span class="badge badge-red shrink-0">${__esc(__t('crypto-verify.tamper_badge'))}</span>
               <div class="min-w-0">
                   <p>${__t('crypto-verify.tamper_result')}</p>
                   <p class="mt-1 text-xs">${__t('crypto-verify.registered_label')} <code class="mono">${__esc(r.registered ?? r.reason)}</code></p>
                   <p class="text-xs">${__t('crypto-verify.recomputed_label')} <code class="mono">${__esc(r.recomputed ?? '')}</code></p>
               </div>
           </div>`;
    renderDeeds();
};

document.getElementById('btn-tamper').onclick = () => {
    const field = document.getElementById('canonical');
    field.value = field.value.replace('"buyer"', '"buyer (FORGED)"');
    document.getElementById('verify-out').innerHTML =
        `<div class="alert alert-warning">${__esc(__t('crypto-verify.forgery_hint'))}</div>`;
    field.focus();
};
</script>
@endpush

@section('content')
<x-page-header icon="shield-check" :title="__('pages.crypto-verify.page_title')" :subtitle="__('pages.crypto-verify.page_sub')" />

<div class="grid gap-6 lg:grid-cols-2">
    <section class="card" aria-labelledby="deed-registry-title">
        <div class="card-header">
            <div>
                <h2 id="deed-registry-title" class="card-title">{{ __('pages.crypto-verify.registry_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.crypto-verify.registry_note') }}</p>
            </div>
            <button type="button" id="btn-hash" class="btn-primary btn-sm">
                <x-icon name="shield-check" class="h-4 w-4" />
                <span>{{ __('pages.crypto-verify.hash_all') }}</span>
            </button>
        </div>
        <div class="card-body pt-0">
            <p class="pt-3 text-xs text-slate-400" id="deed-count"></p>
            <div class="overflow-x-auto">
                <table class="data-table min-w-[520px]">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('pages.crypto-verify.col_deed') }}</th>
                            <th scope="col">{{ __('pages.crypto-verify.col_parties') }}</th>
                            <th scope="col">{{ __('pages.crypto-verify.col_sha') }}</th>
                        </tr>
                    </thead>
                    <tbody id="deed-table"></tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card" aria-labelledby="verify-title">
        <div class="card-header">
            <div>
                <h2 id="verify-title" class="card-title">{{ __('pages.crypto-verify.verify_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.crypto-verify.verify_hint') }}</p>
            </div>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label for="deed-sel" class="label">{{ __('pages.crypto-verify.select_deed_label') }}</label>
                <select id="deed-sel" class="input"></select>
            </div>
            <div>
                <label for="canonical" class="label">{{ __('pages.crypto-verify.canonical_label') }}</label>
                <textarea id="canonical" class="input font-mono text-[11px]" rows="8" spellcheck="false"></textarea>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" id="btn-verify" class="btn-primary">
                    <x-icon name="check-circle" class="h-4 w-4" />
                    <span>{{ __('pages.crypto-verify.verify_hash') }}</span>
                </button>
                <button type="button" id="btn-tamper" class="btn-danger-outline">
                    <x-icon name="warning" class="h-4 w-4" />
                    <span>{{ __('pages.crypto-verify.simulate_forgery') }}</span>
                </button>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                    <x-icon name="qr-code" class="h-4 w-4" />
                    <span>{{ __('pages.crypto-verify.qr_label') }}</span>
                </p>
                <p id="qr-payload" class="mono mt-1 !text-slate-600">{{ __('common.select_deed') }}</p>
            </div>
            <div id="verify-out" class="text-sm" role="status"></div>
        </div>
    </section>
</div>
@endsection
