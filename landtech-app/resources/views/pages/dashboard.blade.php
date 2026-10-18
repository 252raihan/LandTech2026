@extends('layouts.app')
@section('title', __('pages.dashboard.title')) @section('page-title', __('pages.dashboard.page_title'))
@section('page-sub', __('pages.dashboard.page_sub'))

@push('scripts')
<script type="module">
import { getPlots, getDeeds, getEvents, getKyc, getLedger, getLiens, resetAll } from '/js/store.js';
import { seedAll } from '/js/seed.js';
import { scoreAll } from '/js/risk.js';
import { detectConflicts } from '/js/ocr.js';
import { toast, uiConfirm, fmtTime } from '/js/ui.js';

if (!getPlots().length) seedAll();

const RISK_COLORS = { red: '#dc2626', amber: '#f59e0b', green: '#15805f' };

// ---- Summary cards ------------------------------------------------
function renderStats(scores) {
    const plots = getPlots();
    const deeds = getDeeds();
    const kyc = getKyc();
    const high = scores.filter(s => s.band.color === 'red').length;
    const verified = kyc.filter(k => k.status === 'VERIFIED').length;

    document.getElementById('stat-plots').textContent = plots.length;
    document.getElementById('stat-deeds').textContent = deeds.length;
    document.getElementById('stat-alerts').textContent = high;
    document.getElementById('stat-kyc').textContent = verified;

    const sub = (id, key, count) => {
        document.getElementById(id).textContent = __t(key, { count });
    };
    sub('stat-plots-sub', 'dashboard.stat_sub_synthetic', plots.length);
    sub('stat-deeds-sub', 'dashboard.stat_sub_synthetic', deeds.length);
    document.getElementById('stat-alerts-sub').textContent = __t('dashboard.stat_sub_high');
    document.getElementById('stat-kyc-sub').textContent = __t('dashboard.stat_sub_kyc');
}

// ---- Plot risk chart + legend -------------------------------------
let riskChart = null;

function renderChart(scores) {
    const canvas = document.getElementById('ch-risk');
    if (typeof Chart === 'undefined') {
        canvas.closest('div').innerHTML =
            '<p class="p-4 text-sm text-slate-500">' + __esc(__t('dashboard.chart_unavailable')) + '</p>';
        return;
    }

    const labels = scores.map(s => s.plotNo);
    const data = scores.map(s => s.score);
    const colors = scores.map(s => RISK_COLORS[s.band.color] ?? RISK_COLORS.green);

    if (riskChart) {
        riskChart.data.labels = labels;
        riskChart.data.datasets[0].data = data;
        riskChart.data.datasets[0].backgroundColor = colors;
        riskChart.update();
    } else {
        riskChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: __t('dashboard.chart_risk_label'),
                    data,
                    backgroundColor: colors,
                    borderRadius: 4,
                    maxBarThickness: 42,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${__t('dashboard.chart_risk_label')}: ${ctx.parsed.y} / 100`,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: __t('dashboard.risk_axis_score'), color: '#64748b' },
                        grid: { color: '#e2e8f0' },
                        ticks: { color: '#64748b' },
                    },
                    x: {
                        title: { display: true, text: __t('dashboard.risk_axis_plot'), color: '#64748b' },
                        grid: { display: false },
                        ticks: { color: '#64748b', maxRotation: 60, minRotation: 0 },
                    },
                },
            },
        });
    }

    const count = band => scores.filter(s => s.band.color === band).length;
    document.getElementById('legend-low').textContent =
        `${__t('dashboard.risk_legend_low')} · ${count('green')}`;
    document.getElementById('legend-medium').textContent =
        `${__t('dashboard.risk_legend_medium')} · ${count('amber')}`;
    document.getElementById('legend-high').textContent =
        `${__t('dashboard.risk_legend_high')} · ${count('red')}`;
}

// ---- Attention-required alerts (derived from current demo data) ----
function buildAlerts() {
    const plots = getPlots();
    const deeds = getDeeds();
    const liens = getLiens().filter(l => l.status === 'ACTIVE');
    const alerts = [];
    const byPlot = {};
    deeds.forEach(d => { (byPlot[d.plotId] ??= []).push(d); });

    Object.entries(byPlot).forEach(([plotId, list]) => {
        const plot = plots.find(p => p.id === plotId);
        list.sort((a, b) => new Date(a.registeredAt) - new Date(b.registeredAt));

        if (list.length > 1) {
            alerts.push({
                severity: 'high',
                plotId,
                message: __t('dashboard.alert_multiple_sale', { count: list.length, plotNo: plot?.plotNo ?? plotId }),
            });
        }
        for (let i = 1; i < list.length; i++) {
            const gap = (new Date(list[i].registeredAt) - new Date(list[i - 1].registeredAt)) / 86400000;
            if (gap < 30) {
                alerts.push({
                    severity: 'high',
                    plotId,
                    message: __t('dashboard.alert_rapid_transfer', { plotNo: plot?.plotNo ?? plotId }),
                });
                break;
            }
        }
    });

    liens.forEach(l => {
        const plot = plots.find(p => p.id === l.plotId);
        alerts.push({
            severity: 'medium',
            plotId: l.plotId,
            href: '/bank-lien',
            message: __t('dashboard.alert_lien', { plotNo: plot?.plotNo ?? l.plotId }),
        });
    });

    plots
        .filter(p => p.mutationStatus === 'PENDING_MUTATION')
        .forEach(p => alerts.push({
            severity: 'medium',
            plotId: p.id,
            message: __t('dashboard.alert_pending_mutation', { plotNo: p.plotNo }),
        }));

    return alerts.sort((a, b) => (a.severity === b.severity ? 0 : a.severity === 'high' ? -1 : 1));
}

function renderAlerts() {
    const alerts = buildAlerts();
    const list = document.getElementById('alert-list');
    const empty = document.getElementById('alert-empty');

    if (!alerts.length) {
        list.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');
    list.innerHTML = alerts.map(a => {
        const severityClass = a.severity === 'high' ? 'badge-red' : 'badge-amber';
        const severityLabel = a.severity === 'high'
            ? __t('dashboard.severity_high')
            : __t('dashboard.severity_medium');
        const href = a.href ?? `/risk-scoring#${a.plotId}`;
        return `<li class="flex items-start gap-3 py-3">
            <span class="badge ${severityClass}">${__esc(severityLabel)}</span>
            <p class="min-w-0 flex-1 text-sm text-slate-700">${__esc(a.message)}</p>
            <a href="${href}" class="btn-ghost btn-xs shrink-0">${__esc(__t('dashboard.inspect'))}</a>
        </li>`;
    }).join('');
}

// ---- Recent activity (synthetic records + simulated events) -------
function renderActivity() {
    const items = [];
    const plots = getPlots();

    getEvents().slice(0, 10).forEach(e => items.push({
        ts: e.ts,
        badge: 'badge-blue',
        label: __t('dashboard.activity_kind_event'),
        text: __t('dashboard.activity_event', { type: e.type }),
    }));

    getLedger().slice(-10).forEach(l => items.push({
        ts: l.ts,
        badge: 'badge-slate',
        label: __t('dashboard.activity_kind_ledger'),
        text: __t('dashboard.activity_ledger', { seq: l.seq, action: l.action }),
    }));

    getKyc().slice(0, 10).forEach(k => items.push({
        ts: k.ts,
        badge: k.status === 'VERIFIED' ? 'badge-green' : 'badge-red',
        label: __t('dashboard.activity_kind_kyc'),
        text: __t('dashboard.activity_kyc', {
            nid: k.nid,
            status: k.status === 'VERIFIED' ? __t('common.verified') : __t('common.rejected'),
        }),
    }));

    getDeeds()
        .slice()
        .sort((a, b) => new Date(b.registeredAt) - new Date(a.registeredAt))
        .slice(0, 6)
        .forEach(d => {
            const plot = plots.find(p => p.id === d.plotId);
            items.push({
                ts: d.registeredAt,
                badge: 'badge-slate',
                label: __t('dashboard.activity_kind_deed'),
                text: __t('dashboard.activity_deed', { deedNo: d.deedNo, plotNo: plot?.plotNo ?? d.plotId }),
            });
        });

    items.sort((a, b) => new Date(b.ts) - new Date(a.ts));
    const list = document.getElementById('activity-list');

    list.innerHTML = items.length
        ? items.slice(0, 8).map(i => `<li class="flex items-start gap-3 py-3">
            <span class="badge ${i.badge} mt-0.5 shrink-0">${__esc(i.label)}</span>
            <p class="min-w-0 flex-1 text-sm text-slate-700">${__esc(i.text)}</p>
            <time class="shrink-0 text-xs text-slate-400" datetime="${__esc(i.ts)}">${__esc(fmtTime(i.ts))}</time>
        </li>`).join('')
        : `<li class="py-6 text-center text-sm text-slate-500">${__esc(__t('dashboard.activity_empty'))}</li>`;
}

// ---- Orchestration ------------------------------------------------
function renderAll() {
    const scores = scoreAll();
    renderStats(scores);
    renderChart(scores);
    renderAlerts();
    renderActivity();
}

renderAll();

document.getElementById('btn-rescan').onclick = async (e) => {
    const btn = e.currentTarget;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    try {
        const conflicts = await detectConflicts();
        renderAll();
        toast(__t('dashboard.scan_done', { count: conflicts.length }), 'success');
    } catch {
        toast(__t('error_generic'), 'error');
    } finally {
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
    }
};

document.getElementById('btn-reset').onclick = async () => {
    const ok = await uiConfirm({
        title: __t('dashboard.reset_heading'),
        message: __t('reset_confirm'),
        confirmLabel: __t('common.confirm'),
        cancelLabel: __t('common.cancel'),
        danger: true,
    });
    if (ok) {
        resetAll();
        location.reload();
    }
};
</script>
@endpush

@section('content')
<x-page-header icon="dashboard" :title="__('pages.dashboard.page_title')" :subtitle="__('pages.dashboard.page_sub')">
    <x-slot:actions>
        <button type="button" id="btn-rescan" class="btn-primary">
            <x-icon name="refresh" class="h-4 w-4" />
            <span>{{ __('pages.dashboard.run_scan') }}</span>
        </button>
    </x-slot:actions>
</x-page-header>

{{-- A. Summary cards --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="stat-card">
        <span class="stat-icon bg-blue-50 text-blue-700"><x-icon name="map" /></span>
        <div class="min-w-0">
            <p class="stat-label">{{ __('pages.dashboard.stat_plots') }}</p>
            <p id="stat-plots" class="stat-value">—</p>
            <p id="stat-plots-sub" class="stat-sub"></p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon bg-indigo-50 text-indigo-700"><x-icon name="document" /></span>
        <div class="min-w-0">
            <p class="stat-label">{{ __('pages.dashboard.stat_deeds') }}</p>
            <p id="stat-deeds" class="stat-value">—</p>
            <p id="stat-deeds-sub" class="stat-sub"></p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon bg-red-50 text-red-700"><x-icon name="warning" /></span>
        <div class="min-w-0">
            <p class="stat-label">{{ __('pages.dashboard.stat_alerts') }}</p>
            <p id="stat-alerts" class="stat-value">—</p>
            <p id="stat-alerts-sub" class="stat-sub"></p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon bg-brand-50 text-brand-700"><x-icon name="check-circle" /></span>
        <div class="min-w-0">
            <p class="stat-label">{{ __('pages.dashboard.stat_kyc') }}</p>
            <p id="stat-kyc" class="stat-value">—</p>
            <p id="stat-kyc-sub" class="stat-sub"></p>
        </div>
    </div>
</div>

{{-- B. Plot risk chart + D. Attention-required alerts --}}
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2" aria-labelledby="risk-chart-title">
        <div class="card-header">
            <div>
                <h2 id="risk-chart-title" class="card-title">{{ __('pages.dashboard.risk_chart_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.dashboard.risk_chart_note') }}</p>
            </div>
            <span class="sim-badge">{{ __('pages.layout.sim_badge') }}</span>
        </div>
        <div class="card-body">
            <div class="relative h-64 sm:h-80">
                <canvas id="ch-risk" role="img" aria-label="{{ __('pages.dashboard.risk_chart_aria') }}"></canvas>
            </div>
            <ul class="mt-4 flex flex-wrap items-center gap-2" aria-label="{{ __('pages.dashboard.risk_legend_label') }}">
                <li><span id="legend-low" class="badge badge-green"></span></li>
                <li><span id="legend-medium" class="badge badge-amber"></span></li>
                <li><span id="legend-high" class="badge badge-red"></span></li>
            </ul>
        </div>
    </section>

    <section class="card" aria-labelledby="attention-title">
        <div class="card-header">
            <div>
                <h2 id="attention-title" class="card-title">{{ __('pages.dashboard.attention_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.dashboard.attention_note') }}</p>
            </div>
        </div>
        <div class="card-body pt-0">
            <ul id="alert-list" class="divide-y divide-slate-100"></ul>
            <div id="alert-empty" class="hidden">
                <div class="alert alert-success mt-4">
                    <x-icon name="check-circle" class="h-5 w-5 shrink-0" />
                    <p>{{ __('pages.dashboard.no_alerts') }}</p>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- C. Recent activity + E. Quick actions --}}
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2" aria-labelledby="activity-title">
        <div class="card-header">
            <div>
                <h2 id="activity-title" class="card-title">{{ __('pages.dashboard.activity_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.dashboard.activity_note') }}</p>
            </div>
        </div>
        <div class="card-body pt-0">
            <ul id="activity-list" class="divide-y divide-slate-100"></ul>
        </div>
    </section>

    <section class="card" aria-labelledby="quick-actions-title">
        <div class="card-header">
            <h2 id="quick-actions-title" class="card-title">{{ __('pages.dashboard.quick_actions_heading') }}</h2>
        </div>
        <div class="card-body space-y-3">
            <div>
                <button type="button" id="btn-reset" class="btn-danger-outline w-full justify-start">
                    <x-icon name="trash" class="h-4 w-4" />
                    <span>{{ __('pages.dashboard.reset_demo') }}</span>
                </button>
                <p class="hint">{{ __('pages.dashboard.reset_demo_hint') }}</p>
            </div>
            <a href="{{ route('risk-scoring') }}" class="menu-item border border-slate-200">
                <x-icon name="warning" class="h-4 w-4 text-amber-600" />
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-slate-700">{{ __('pages.dashboard.open_risk_index') }}</span>
                    <span class="block text-xs text-slate-400">{{ __('pages.dashboard.open_risk_hint') }}</span>
                </span>
                <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" />
            </a>
            <a href="{{ route('ai-ocr-conflict') }}" class="menu-item border border-slate-200">
                <x-icon name="cpu" class="h-4 w-4 text-blue-600" />
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-slate-700">{{ __('pages.dashboard.open_conflicts') }}</span>
                    <span class="block text-xs text-slate-400">{{ __('pages.dashboard.open_conflicts_hint') }}</span>
                </span>
                <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" />
            </a>
            <a href="{{ route('audit-ledger') }}" class="menu-item border border-slate-200">
                <x-icon name="chain" class="h-4 w-4 text-slate-500" />
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-slate-700">{{ __('pages.dashboard.open_ledger') }}</span>
                    <span class="block text-xs text-slate-400">{{ __('pages.dashboard.open_ledger_hint') }}</span>
                </span>
                <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" />
            </a>
        </div>
    </section>
</div>
@endsection
