@extends('layouts.app')
@section('title', 'Webhooks') @section('page-title', 'M1 · Sync & Webhook Layer')
@section('page-sub', 'Simulated event-driven sync (Kafka/RabbitMQ style) between Sub-Registry and AC Land')

@push('scripts')
<script type="module">
import { getEvents, pushEvent, getPlots, savePlots } from '/js/store.js';

function render() {
    const events = getEvents();
    document.getElementById('evt-list').innerHTML = events.length ? events.map(e => `
        <div class="flex items-start gap-3 py-2.5 border-b border-slate-100 text-sm">
            <span class="font-mono text-xs bg-gov-900 text-white px-2 py-0.5 rounded">${e.type}</span>
            <div class="flex-1"><div>${JSON.stringify(e.payload)}</div>
            <div class="text-xs text-slate-400">${new Date(e.ts).toLocaleString()} · ${e.source}</div></div>
        </div>`).join('') : '<p class="text-sm text-slate-500">No events yet. Emit one below or run the Saga.</p>';
}
render();

document.getElementById('btn-deed-event').onclick = () => {
    // Simulate: Sub-Registry deed execution → DEED_EXECUTED_EVENT → Pending Mutation Flag
    const plots = getPlots();
    const p = plots[Math.floor(Math.random() * plots.length)];
    p.mutationStatus = 'PENDING_MUTATION';
    savePlots(plots);
    pushEvent('DEED_EXECUTED_EVENT', { plotId: p.id, plotNo: p.plotNo, flag: 'PENDING_MUTATION' });
    render();
};
document.getElementById('btn-echo').onclick = () => {
    pushEvent('SYNC_PING', { channel: 'simulated-kafka', ok: true });
    render();
};
</script>
@endpush

@section('content')
<div class="card p-5 mb-6">
    <p class="text-sm text-slate-600">
        In production this layer uses <strong>Apache Kafka / RabbitMQ</strong> to push a
        <code class="bg-slate-100 px-1 rounded">DEED_EXECUTED_EVENT</code> from the Sub-Registry e-Registration portal to the
        e-Mutation portal, automatically setting a <strong>Pending Mutation Flag</strong> that blocks further transfer in real time.
        <span class="sim-badge ml-2">Simulated — no real message broker</span>
    </p>
    <div class="mt-4 flex gap-3">
        <button id="btn-deed-event" class="btn-primary">📨 Emit DEED_EXECUTED_EVENT (random plot)</button>
        <button id="btn-echo" class="btn-ghost">Ping Sync Channel</button>
    </div>
</div>
<div class="card p-5">
    <h3 class="font-semibold mb-2">Event Feed (latest first)</h3>
    <div id="evt-list"></div>
</div>
@endsection
