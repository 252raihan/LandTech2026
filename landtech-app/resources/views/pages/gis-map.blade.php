@extends('layouts.app')
@section('title', __('pages.gis-map.title')) @section('page-title', __('pages.gis-map.page_title'))
@section('page-sub', __('pages.gis-map.page_sub'))

@push('scripts')
<script type="module">
import { initMap, renderPlots, focusPlot } from '/js/map.js';
import { getPlots } from '/js/store.js';

function mutationLabel(status) {
    return __t({
        MUTATED: 'common.mutated',
        PENDING_MUTATION: 'common.pending_mutation',
        NONE: 'common.no_mutation',
    }[status] ?? 'common.no_mutation');
}

initMap('gis-map');
const res = renderPlots(p => {
    document.getElementById('plot-detail').innerHTML = `
        <h4 class="text-sm font-bold text-slate-900">${__esc(p.plotNo)}</h4>
        <dl class="mt-2 space-y-1.5 text-sm">
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.district')}</dt><dd>${__esc(p.district)} → ${__esc(p.upazila)} → ${__esc(p.mouza)}</dd></div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.khatian')}</dt><dd>${__esc(p.khatian)} (${__esc(p.survey)})</dd></div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.owner')}</dt><dd>${__esc(p.owner)} <em class="text-xs not-italic text-slate-400">(${__t('common.synthetic')})</em></dd></div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.area')}</dt><dd>${__esc(p.areaDecimal)} ${__t('common.decimal')}</dd></div>
            <div class="flex gap-2"><dt class="shrink-0 text-slate-400">${__t('common.mutation')}</dt><dd><strong>${__esc(mutationLabel(p.mutationStatus))}</strong></dd></div>
        </dl>
        <p class="mono mt-2">${__esc(p.redlockKey)}</p>`;
});

document.getElementById('ov-count').textContent = res.overlaps.length;
document.getElementById('ov-list').innerHTML = res.overlaps.length
    ? res.overlaps.map(([a, b]) => {
        const A = getPlots().find(p => p.id === a), B = getPlots().find(p => p.id === b);
        if (!A || !B) return '';
        return `<li class="flex flex-wrap items-center gap-1 py-2 text-sm">
            <button type="button" class="font-medium text-blue-700 hover:underline" data-focus="${__esc(A.id)}">${__esc(A.plotNo)}</button>
            <span class="text-xs text-slate-400">${__t('pages.gis-map.overlaps_word')}</span>
            <button type="button" class="font-medium text-blue-700 hover:underline" data-focus="${__esc(B.id)}">${__esc(B.plotNo)}</button>
        </li>`;
    }).join('')
    : `<li class="py-3 text-sm text-slate-500">${__t('pages.gis-map.no_overlaps')}</li>`;

document.getElementById('ov-list').addEventListener('click', e => {
    const btn = e.target.closest('[data-focus]');
    if (btn) focusPlot(btn.dataset.focus);
});
</script>
@endpush

@section('content')
<x-page-header icon="map" :title="__('pages.gis-map.page_title')" :subtitle="__('pages.gis-map.page_sub')">
    <x-slot:actions>
        <span class="sim-badge">{{ __('pages.gis-map.overlap_badge') }}</span>
    </x-slot:actions>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="card overflow-hidden lg:col-span-2" aria-labelledby="gis-map-title">
        <div class="card-header">
            <h2 id="gis-map-title" class="card-title">{{ __('pages.gis-map.map_heading') }}</h2>
            <p class="text-xs text-slate-400">{{ __('pages.gis-map.map_note') }}</p>
        </div>
        <div id="gis-map" class="z-0 h-[420px] w-full sm:h-[520px]"></div>
        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-brand-500" aria-hidden="true"></span>
                {{ __('common.mutated') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                {{ __('common.pending_mutation') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-slate-500" aria-hidden="true"></span>
                {{ __('common.no_mutation') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-red-600" aria-hidden="true"></span>
                {{ __('pages.gis-map.legend_overlap') }}
            </span>
        </div>
    </section>

    <div class="space-y-6">
        <section class="card" aria-labelledby="overlap-title">
            <div class="card-header">
                <div>
                    <h2 id="overlap-title" class="card-title">{{ __('pages.gis-map.overlap_heading') }}</h2>
                    <p class="text-xs text-slate-400">{{ __('pages.gis-map.overlap_hint') }}</p>
                </div>
                <span class="badge badge-slate"><span id="ov-count">0</span></span>
            </div>
            <div class="card-body pt-0">
                <ul id="ov-list" class="divide-y divide-slate-100"></ul>
            </div>
        </section>

        <section class="card" aria-labelledby="plot-detail-title">
            <div class="card-header">
                <h2 id="plot-detail-title" class="card-title">{{ __('pages.gis-map.details_heading') }}</h2>
            </div>
            <div class="card-body pt-2">
                <div id="plot-detail" class="text-sm text-slate-500">{{ __('pages.gis-map.details_hint') }}</div>
            </div>
        </section>
    </div>
</div>
@endsection
