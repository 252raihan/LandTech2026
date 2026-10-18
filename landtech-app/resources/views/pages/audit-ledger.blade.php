@extends('layouts.app')
@section('title', __('pages.audit-ledger.title')) @section('page-title', __('pages.audit-ledger.page_title'))
@section('page-sub', __('pages.audit-ledger.page_sub'))

@push('scripts')
<script type="module">
import { getLedger } from '/js/store.js';
import { verifyLedgerChain, tamperLedger, appendLedger } from '/js/crypto.js';
import { toast, fmtTime } from '/js/ui.js';

function render(status = null) {
    const ledger = getLedger();

    document.getElementById('block-count').textContent = __t('pages.audit-ledger.block_count', { count: ledger.length });

    document.getElementById('chain-status').innerHTML = status
        ? (status.ok
            ? `<span class="badge badge-green">${__esc(__t('pages.audit-ledger.chain_valid'))}</span>`
            : `<span class="badge badge-red">${__tHtml('pages.audit-ledger.chain_broken', { block: status.brokenAt })}</span>`)
        : `<span class="badge badge-slate">${__esc(__t('pages.audit-ledger.chain_idle'))}</span>`;

    document.getElementById('ledger-table').innerHTML = ledger.length
        ? ledger.slice().reverse().map(e => `
            <tr>
                <td class="font-bold tabular-nums">#${__esc(e.seq)}</td>
                <td><span class="badge badge-blue">${__esc(e.action)}</span></td>
                <td class="mono max-w-[16rem] truncate" title="${__esc(JSON.stringify(e.data))}">${__esc(JSON.stringify(e.data))}</td>
                <td class="mono" title="${__esc(e.prevHash)}">${__esc(e.prevHash.slice(0, 12))}…</td>
                <td class="mono" title="${__esc(e.blockHash)}">${__esc(e.blockHash.slice(0, 12))}…</td>
                <td class="whitespace-nowrap text-xs text-slate-500">${__esc(fmtTime(e.ts))}</td>
            </tr>`).join('')
        : `<tr><td colspan="6" class="table-empty">${__esc(__t('pages.audit-ledger.empty'))}</td></tr>`;
}

document.getElementById('btn-verify').onclick = async () => {
    const st = await verifyLedgerChain();
    render(st);
    toast(st.ok
        ? __t('pages.audit-ledger.verify_valid_toast')
        : __t('pages.audit-ledger.verify_broken_toast'), st.ok ? 'success' : 'error');
};

document.getElementById('btn-tamper').onclick = async () => {
    if (tamperLedger()) {
        const st = await verifyLedgerChain();
        render(st);
        toast(__t('pages.audit-ledger.tamper_toast'), 'error');
    } else {
        toast(__t('pages.audit-ledger.ledger_empty_alert'), 'info');
    }
};

document.getElementById('btn-entry').onclick = async () => {
    await appendLedger('MANUAL_NOTE', { note: __t('pages.audit-ledger.manual_note') });
    render();
    toast(__t('pages.audit-ledger.append_toast'), 'success');
};

render();
</script>
@endpush

@section('content')
<x-page-header icon="chain" :title="__('pages.audit-ledger.page_title')" :subtitle="__('pages.audit-ledger.page_sub')">
    <x-slot:actions>
        <span class="sim-badge">{{ __('pages.audit-ledger.badge') }}</span>
    </x-slot:actions>
</x-page-header>

<section class="card mb-6" aria-labelledby="ledger-actions-title">
    <div class="card-header">
        <div>
            <h2 id="ledger-actions-title" class="card-title">{{ __('pages.audit-ledger.intro_heading') }}</h2>
            <p class="text-xs text-slate-400">{!! __('pages.audit-ledger.intro', ['prevhash' => '<code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-xs">prevHash</code>']) !!}</p>
        </div>
        <span id="block-count" class="badge badge-slate"></span>
    </div>
    <div class="card-body">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="btn-verify" class="btn-primary btn-sm">
                <x-icon name="shield-check" class="h-4 w-4" />
                <span>{{ __('pages.audit-ledger.verify_chain') }}</span>
            </button>
            <button type="button" id="btn-tamper" class="btn-danger btn-sm">
                <x-icon name="warning" class="h-4 w-4" />
                <span>{{ __('pages.audit-ledger.simulate_tamper') }}</span>
            </button>
            <button type="button" id="btn-entry" class="btn-ghost btn-sm">{{ __('pages.audit-ledger.append_note') }}</button>
            <span id="chain-status" class="ms-auto" role="status"></span>
        </div>
    </div>
</section>

<section class="card" aria-labelledby="ledger-table-title">
    <div class="card-header">
        <h2 id="ledger-table-title" class="card-title">{{ __('pages.audit-ledger.table_heading') }}</h2>
        <p class="text-xs text-slate-400">{{ __('pages.audit-ledger.tamper_hint') }}</p>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table min-w-[960px]">
            <thead>
                <tr>
                    <th scope="col">{{ __('pages.audit-ledger.col_seq') }}</th>
                    <th scope="col">{{ __('pages.audit-ledger.col_action') }}</th>
                    <th scope="col">{{ __('pages.audit-ledger.col_data') }}</th>
                    <th scope="col">{{ __('pages.audit-ledger.col_prev_hash') }}</th>
                    <th scope="col">{{ __('pages.audit-ledger.col_block_hash') }}</th>
                    <th scope="col">{{ __('pages.audit-ledger.col_time') }}</th>
                </tr>
            </thead>
            <tbody id="ledger-table"></tbody>
        </table>
    </div>
</section>
@endsection
