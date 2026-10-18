@extends('layouts.app')
@section('title', __('pages.bank-lien.title')) @section('page-title', __('pages.bank-lien.page_title'))
@section('page-sub', __('pages.bank-lien.page_sub'))

@push('scripts')
<script type="module">
import { getPlots } from '/js/store.js';
import { getLiens as allLiens, addLien, issueENOC } from '/js/liens.js';
import { toast } from '/js/ui.js';

const plotSel = document.getElementById('lien-plot');
getPlots().forEach(p => plotSel.add(new Option(`${p.plotNo} — ${p.khatian}`, p.id)));

function money(amount) {
    return '৳ ' + Number(amount).toLocaleString();
}

function render() {
    const liens = allLiens();
    document.getElementById('lien-count').textContent = __t('pages.bank-lien.lien_count', { count: liens.length });

    document.getElementById('lien-table').innerHTML = liens.length ? liens.map(l => {
        const plot = getPlots().find(p => p.id === l.plotId);
        return `<tr>
            <td class="font-medium text-slate-900">${__esc(plot?.plotNo ?? l.plotId)}</td>
            <td>${__esc(l.bank)}</td>
            <td class="whitespace-nowrap">${__esc(money(l.amount))}</td>
            <td><span class="badge ${l.status === 'ACTIVE' ? 'badge-red' : 'badge-green'}">${__esc(__t('common.' + l.status.toLowerCase()))}</span></td>
            <td class="mono">${__esc(l.eNoc ?? '—')}</td>
            <td class="whitespace-nowrap text-end">${l.status === 'ACTIVE'
                ? `<button type="button" class="btn-primary btn-xs" onclick="window.__noc('${__esc(l.id)}')">${__t('pages.bank-lien.issue_enoc')}</button>`
                : ''}</td>
        </tr>`;
    }).join('') : `<tr><td colspan="6" class="table-empty">${__esc(__t('pages.bank-lien.empty'))}</td></tr>`;
}

window.__noc = (id) => {
    const l = issueENOC(id);
    if (l) {
        toast(__t('pages.bank-lien.enoc_issued', { enoc: l.eNoc }), 'success');
    } else {
        toast(__t('pages.bank-lien.enoc_failed'), 'error');
    }
    render();
};
render();

document.getElementById('btn-add').onclick = () => {
    const amount = Number(document.getElementById('lien-amt').value || 1000000);
    const l = addLien(plotSel.value, document.getElementById('lien-bank').value || __t('pages.bank-lien.default_bank'), amount);
    toast(__t('pages.bank-lien.lien_registered', { id: l.id }), 'success');
    render();
};
</script>
@endpush

@section('content')
<x-page-header icon="banknotes" :title="__('pages.bank-lien.page_title')" :subtitle="__('pages.bank-lien.page_sub')">
    <x-slot:actions>
        <span class="sim-badge">{{ __('pages.bank-lien.badge') }}</span>
    </x-slot:actions>
</x-page-header>

<section class="card mb-6" aria-labelledby="lien-register-title">
    <div class="card-header">
        <div>
            <h2 id="lien-register-title" class="card-title">{{ __('pages.bank-lien.register_heading') }}</h2>
            <p class="text-xs text-slate-400">{!! __('pages.bank-lien.intro', ['enoc' => '<strong>e-NOC</strong>']) !!}</p>
        </div>
    </div>
    <div class="card-body">
        <div class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="lien-plot" class="label">{{ __('pages.bank-lien.col_plot') }}</label>
                <select id="lien-plot" class="input"></select>
            </div>
            <div>
                <label for="lien-bank" class="label">{{ __('pages.bank-lien.col_bank') }}</label>
                <input id="lien-bank" class="input" placeholder="{{ __('pages.bank-lien.placeholder_bank') }}" autocomplete="off">
            </div>
            <div>
                <label for="lien-amt" class="label">{{ __('pages.bank-lien.col_amount') }}</label>
                <input id="lien-amt" class="input" type="number" min="0" step="1000" placeholder="{{ __('pages.bank-lien.placeholder_amount') }}" inputmode="numeric">
            </div>
            <button type="button" id="btn-add" class="btn-primary">
                <x-icon name="banknotes" class="h-4 w-4" />
                <span>{{ __('pages.bank-lien.register_button') }}</span>
            </button>
        </div>
    </div>
</section>

<section class="card" aria-labelledby="lien-table-title">
    <div class="card-header">
        <h2 id="lien-table-title" class="card-title">{{ __('pages.bank-lien.table_heading') }}</h2>
        <span id="lien-count" class="badge badge-slate"></span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table min-w-[820px]">
            <thead>
                <tr>
                    <th scope="col">{{ __('pages.bank-lien.col_plot') }}</th>
                    <th scope="col">{{ __('pages.bank-lien.col_bank') }}</th>
                    <th scope="col">{{ __('pages.bank-lien.col_amount') }}</th>
                    <th scope="col">{{ __('pages.bank-lien.col_status') }}</th>
                    <th scope="col">{{ __('pages.bank-lien.col_enoc') }}</th>
                    <th scope="col" class="text-end">{{ __('pages.bank-lien.col_action') }}</th>
                </tr>
            </thead>
            <tbody id="lien-table"></tbody>
        </table>
    </div>
</section>
@endsection
