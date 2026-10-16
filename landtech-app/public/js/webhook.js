// ============================================================
// M1 — Sync & Webhook Layer
// SIMULATED event bus / Kafka-style topic. Pure browser events.
// ============================================================
import { getEvents, saveEvents, pushEvent } from './store.js';

export const TOPICS = {
    DEED_EXECUTED: 'deed.executed',
    PLOT_LOCKED: 'plot.locked',
    PLOT_UNLOCKED: 'plot.unlocked',
    KYC_VERIFIED: 'kyc.verified',
    LIEN_DETECTED: 'lien.detected',
    SPATIAL_CONFLICT: 'spatial.conflict',
    RISK_UPDATED: 'risk.updated',
    EMUTATION_NOTIFY: 'emutation.notify',
};

const listeners = new Map();

export function subscribe(topic, fn) {
    if (!listeners.has(topic)) listeners.set(topic, new Set());
    listeners.get(topic).add(fn);
    return () => listeners.get(topic).delete(fn);
}

export function emit(topic, payload) {
    const evt = pushEvent(topic, payload);
    (listeners.get(topic) || []).forEach(fn => fn(evt));
    (listeners.get('*') || []).forEach(fn => fn(evt));
    return evt;
}

export function recentEvents(n = 50) {
    return getEvents().slice(0, n);
}
