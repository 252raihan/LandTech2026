// ============================================================
// LandTech 2026 P3 — Mock Data Store (LocalStorage persistence)
// SIMULATED: this is NOT a database. All data is synthetic demo data.
// ============================================================
import { seedAll } from './seed.js';

const NS = 'landtech2026.';

export function load(key, fallback) {
    try {
        const raw = localStorage.getItem(NS + key);
        return raw ? JSON.parse(raw) : structuredClone(fallback);
    } catch { return structuredClone(fallback); }
}

export function save(key, value) {
    localStorage.setItem(NS + key, JSON.stringify(value));
}

export function resetAll() {
    Object.keys(localStorage)
        .filter(k => k.startsWith(NS))
        .forEach(k => localStorage.removeItem(k));
    seedAll();
}

// ---- Collections ------------------------------------------------
export const getPlots = () => load('plots', []);
export const savePlots = (p) => save('plots', p);
export const getDeeds = () => load('deeds', []);
export const saveDeeds = (d) => save('deeds', d);
export const getBuyers = () => load('buyers', []);
export const saveBuyers = (b) => save('buyers', b);
export const getLocks = () => load('locks', {});
export const saveLocks = (l) => save('locks', l);
export const getLiens = () => load('liens', []);
export const saveLiens = (l) => save('liens', l);
export const getKyc = () => load('kyc', []);
export const saveKyc = (k) => save('kyc', k);
export const getEvents = () => load('webhooks', []);
export const saveEvents = (e) => save('webhooks', e);
export const getLedger = () => load('ledger', []);
export const saveLedger = (l) => save('ledger', l);
export const getOcrDocs = () => load('ocrdocs', []);
export const saveOcrDocs = (d) => save('ocrdocs', d);
export const getMutations = () => load('mutations', []);
export const saveMutations = (m) => save('mutations', m);

// ---- Helpers ----------------------------------------------------
export const uid = (prefix) => prefix + '-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
export const nowISO = () => new Date().toISOString();

export function pushEvent(type, payload) {
    const events = getEvents();
    events.unshift({ id: uid('evt'), type, payload, ts: nowISO(), source: 'simulated-kafka' });
    saveEvents(events.slice(0, 200));
    return events[0];
}
