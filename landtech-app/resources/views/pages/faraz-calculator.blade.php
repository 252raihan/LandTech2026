@extends('layouts.app')
@section('title', __('pages.faraz-calculator.title')) @section('page-title', __('pages.faraz-calculator.page_title'))
@section('page-sub', __('pages.faraz-calculator.page_sub'))

@push('scripts')
<script type="module">
import { validateShares, validateTransfer, equalSplit, fractionToAnkra } from '/js/faraz.js';

let legal = [
    { holder: __t('pages.faraz-calculator.heir_prefix') + ' A', share: 0.4 },
    { holder: __t('pages.faraz-calculator.heir_prefix') + ' B', share: 0.35 },
    { holder: __t('pages.faraz-calculator.heir_prefix') + ' C', share: 0.25 },
];

function renderLegal() {
    const v = validateShares(legal);

    document.getElementById('legal-list').innerHTML = legal.map(s => `
        <tr>
            <td class="font-medium text-slate-900">${__esc(s.holder)}</td>
            <td class="whitespace-nowrap">${__esc(s.share)} <span class="text-xs text-slate-400">(${__esc((s.share * 100).toFixed(1))}%)</span></td>
            <td class="whitespace-nowrap text-xs text-slate-500">${__esc(fractionToAnkra(s.share))}</td>
        </tr>`).join('') + `
        <tr class="bg-slate-50 font-semibold">
            <td class="px-4 py-3">${__t('pages.faraz-calculator.total_label')}</td>
            <td class="px-4 py-3 tabular-nums">${__esc(v.sum)}</td>
            <td class="px-4 py-3">
                <span class="badge ${v.valid ? 'badge-green' : 'badge-red'}">${__esc(v.valid ? __t('pages.faraz-calculator.sum_valid') : __t('pages.faraz-calculator.sum_invalid'))}</span>
            </td>
        </tr>`;

    // Keep the seller dropdown in sync with the current legal holders, preserving
    // the previous selection where possible.
    const sellerSel = document.getElementById('t-seller');
    const previous = sellerSel.value;
    sellerSel.innerHTML = legal
        .map(s => `<option value="${__esc(s.holder)}">${__esc(s.holder)}</option>`)
        .join('');
    if (legal.some(s => s.holder === previous)) sellerSel.value = previous;
}
renderLegal();

document.getElementById('btn-split').onclick = () => {
    const n = Math.min(8, Math.max(1, Number(document.getElementById('heir-count').value || 3)));
    legal = equalSplit(Array.from({ length: n }, (_, i) => `${__t('pages.faraz-calculator.heir_prefix')} ${String.fromCharCode(65 + i)}`));
    renderLegal();
    document.getElementById('transfer-out').innerHTML =
        `<div class="alert alert-info">${__esc(__t('pages.faraz-calculator.split_result', { count: n }))}</div>`;
};

document.getElementById('btn-transfer').onclick = () => {
    const seller = document.getElementById('t-seller').value;
    const share = Number(document.getElementById('t-share').value);
    const r = validateTransfer(legal, { seller, share });
    const v = validateShares(legal);

    document.getElementById('transfer-out').innerHTML = `
        <div class="alert ${r.valid ? 'alert-success' : 'alert-danger'}" role="status">
            <span class="badge ${r.valid ? 'badge-green' : 'badge-red'} shrink-0">${__esc(r.valid ? __t('pages.faraz-calculator.sum_valid') : __t('pages.faraz-calculator.sum_invalid'))}</span>
            <div class="min-w-0">
                <p>${__esc(r.message)}</p>
                <p class="mt-1 text-xs">${__tHtml('pages.faraz-calculator.sum_line', { sum: v.sum, verdict: v.valid ? __t('pages.faraz-calculator.sum_valid') : __t('pages.faraz-calculator.sum_invalid') })}</p>
            </div>
        </div>`;
};
</script>
@endpush

@section('content')
<x-page-header icon="scale" :title="__('pages.faraz-calculator.page_title')" :subtitle="__('pages.faraz-calculator.page_sub')" />

<div class="grid gap-6 lg:grid-cols-2">
    <section class="card" aria-labelledby="legal-shares-title">
        <div class="card-header">
            <div>
                <h2 id="legal-shares-title" class="card-title">{{ __('pages.faraz-calculator.legal_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.faraz-calculator.help') }}</p>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="overflow-x-auto">
                <table class="data-table min-w-[420px]">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('pages.faraz-calculator.col_holder') }}</th>
                            <th scope="col">{{ __('pages.faraz-calculator.col_share') }}</th>
                            <th scope="col">{{ __('pages.faraz-calculator.col_ankra') }}</th>
                        </tr>
                    </thead>
                    <tbody id="legal-list"></tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <div>
                    <label for="heir-count" class="label">{{ __('pages.faraz-calculator.heir_count_label') }}</label>
                    <input id="heir-count" class="input w-24" type="number" value="3" min="1" max="8" inputmode="numeric">
                </div>
                <button type="button" id="btn-split" class="btn-ghost">
                    <x-icon name="scale" class="h-4 w-4" />
                    <span>{{ __('pages.faraz-calculator.equal_split') }}</span>
                </button>
            </div>
        </div>
    </section>

    <section class="card" aria-labelledby="transfer-title">
        <div class="card-header">
            <div>
                <h2 id="transfer-title" class="card-title">{{ __('pages.faraz-calculator.transfer_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.faraz-calculator.rule_note') }}</p>
            </div>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label for="t-seller" class="label">{{ __('pages.faraz-calculator.seller_label') }}</label>
                <select id="t-seller" class="input"></select>
            </div>
            <div>
                <label for="t-share" class="label">{{ __('pages.faraz-calculator.transfer_share_label') }}</label>
                <input id="t-share" class="input" type="number" step="0.01" min="0" max="1" value="0.2" inputmode="decimal">
                <p class="hint">{{ __('pages.faraz-calculator.transfer_hint') }}</p>
            </div>
            <button type="button" id="btn-transfer" class="btn-primary">
                <x-icon name="calculator" class="h-4 w-4" />
                <span>{{ __('pages.faraz-calculator.validate_transfer') }}</span>
            </button>
            <div id="transfer-out" role="status"></div>
        </div>
    </section>
</div>
@endsection
