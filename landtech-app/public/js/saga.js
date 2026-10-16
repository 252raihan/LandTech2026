// ============================================================
// Saga Orchestration Engine — SIMULATED distributed transaction.
// Steps: Lock → e-KYC → Lien Check → Boundary Validation →
//        Hash/Mutation Notification. Compensating actions on failure.
// Frontend-only: each step calls the browser modules above.
// ============================================================
import { acquireLock, releaseLock, setPendingMutationFlag, isTransferBlocked } from './locks.js';
import { verifyKyc } from './kyc.js';
import { registrationBlockedByLien } from './liens.js';
import { findOverlaps } from './map.js';
import { computeDeedHash, appendLedger } from './crypto.js';
import { getDeeds, getPlots, pushEvent, saveDeeds, saveMutations, getMutations, uid, nowISO } from './store.js';

export const SAGA_STEPS = [
    { key: 'lock', name: '1. Plot Lock (simulated Redlock)', compensate: 'releaseLock' },
    { key: 'kyc', name: '2. Identity Verification (simulated Porichoy)', compensate: 'flagRejected' },
    { key: 'lien', name: '3. Bank Lien Check (simulated CIB)', compensate: null },
    { key: 'boundary', name: '4. Boundary Validation (simulated PostGIS)', compensate: null },
    { key: 'hash', name: '5. Hash Generation & Mutation Notification', compensate: 'revertMutation' },
];

export async function runSaga({ plotId, deedId, buyerNid, buyerName, failAt = null }) {
    const sagaId = uid('saga');
    const log = [];
    const step = async (key, fn) => {
        if (failAt === key) {
            log.push({ key, status: 'FAILED', detail: 'SIMULATED failure injected for demo' });
            throw { sagaId, failedAt: key, log };
        }
        try {
            const r = await fn();
            log.push({ key, status: 'COMPLETED', detail: r });
        } catch (e) {
            log.push({ key, status: 'FAILED', detail: String(e?.reason || e) });
            throw { sagaId, failedAt: key, log };
        }
    };

    const deeds = getDeeds();
    const deed = deeds.find(d => d.id === deedId);

    try {
        // 1. Lock
        await step('lock', () => {
            const r = acquireLock(plotId, `saga:${sagaId}`);
            if (!r.ok) throw r;
            return `Acquired ${r.lock.key}`;
        });
        // 2. KYC
        await step('kyc', () => {
            const r = verifyKyc(buyerNid, buyerName);
            if (r.status !== 'VERIFIED') throw { reason: `KYC ${r.status}` };
            return `e-KYC VERIFIED (${r.provider})`;
        });
        // 3. Lien check
        await step('lien', () => {
            const blocked = registrationBlockedByLien(plotId);
            if (blocked) throw { reason: blocked };
            return 'No active lien — e-NOC not required';
        });
        // 4. Boundary validation
        await step('boundary', () => {
            const overlaps = findOverlaps().filter(pair => pair.includes(plotId));
            if (overlaps.length) throw { reason: 'Plot boundary overlaps another khatian (simulated ST_Overlaps)' };
            return 'No boundary overlap detected';
        });
        // 5. Hash + mutation notification
        await step('hash', () => {
            if (deed && !deed.hash) return computeDeedHash(deed).then(h => {
                deed.hash = h; saveDeeds(deeds);
                setPendingMutationFlag(plotId, true);
                const mutations = getMutations();
                mutations.push({ id: uid('mut'), plotId, deedId, ts: nowISO(), status: 'PENDING_MUTATION' });
                saveMutations(mutations);
                pushEvent('DEED_EXECUTED_EVENT', { plotId, deedId });
                appendLedger('SAGA_COMMITTED', { plotId, deedId });
                return `SHA-256 committed; Pending Mutation Flag set`;
            });
            setPendingMutationFlag(plotId, true);
            appendLedger('SAGA_COMMITTED', { plotId, deedId });
            return 'Pending Mutation Flag set';
        });
        return { sagaId, status: 'COMMITTED', log };
    } catch (err) {
        // ---- COMPENSATION PHASE ----
        const comp = [];
        for (const s of SAGA_STEPS) {
            const done = log.find(l => l.key === s.key && l.status === 'COMPLETED');
            if (!done || !s.compensate) continue;
            if (s.compensate === 'releaseLock') {
                releaseLock(plotId, `saga:${sagaId}`);
                comp.push('Lock released');
            } else if (s.compensate === 'revertMutation') {
                setPendingMutationFlag(plotId, false);
                comp.push('Mutation flag reverted');
            } else if (s.compensate === 'flagRejected') {
                comp.push('KYC rejection recorded');
            }
        }
        appendLedger('SAGA_ABORTED', { plotId, deedId, failedAt: err.failedAt });
        pushEvent('SAGA_ABORTED', { plotId, failedAt: err.failedAt });
        return { sagaId, status: 'ABORTED', failedAt: err.failedAt, log, compensations: comp };
    }
}
