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

/** Translation helper (no-op fallback outside the browser). */
const t = (key, replace) =>
    (typeof window !== 'undefined' && window.__t ? window.__t(key, replace) : key);

/**
 * Saga steps. `i18n` holds the translation KEY (never the literal text) so the
 * label follows the active locale; `name` is kept for any non-UI consumers.
 */
export const SAGA_STEPS = [
    { key: 'lock', i18n: 'pages.saga.step_1', compensate: 'releaseLock' },
    { key: 'kyc', i18n: 'pages.saga.step_2', compensate: 'flagRejected' },
    { key: 'lien', i18n: 'pages.saga.step_3', compensate: null },
    { key: 'boundary', i18n: 'pages.saga.step_4', compensate: null },
    { key: 'hash', i18n: 'pages.saga.step_5', compensate: 'revertMutation' },
];

export async function runSaga({ plotId, deedId, buyerNid, buyerName, failAt = null }) {
    const sagaId = uid('saga');
    const log = [];
    const step = async (key, fn) => {
        if (failAt === key) {
            log.push({ key, status: 'FAILED', detail: t('pages.saga.msg_failure_injected') });
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
            return t('pages.saga.msg_lock_acquired', { key: r.lock.key });
        });
        // 2. KYC
        await step('kyc', () => {
            const r = verifyKyc(buyerNid, buyerName);
            if (r.status !== 'VERIFIED') throw { reason: t('pages.saga.msg_kyc_failed', { status: r.status }) };
            return t('pages.saga.msg_kyc_verified', { provider: r.provider });
        });
        // 3. Lien check
        await step('lien', () => {
            const blocked = registrationBlockedByLien(plotId);
            if (blocked) throw { reason: blocked };
            return t('pages.saga.msg_no_lien');
        });
        // 4. Boundary validation
        await step('boundary', () => {
            const overlaps = findOverlaps().filter(pair => pair.includes(plotId));
            if (overlaps.length) throw { reason: t('pages.saga.msg_overlap') };
            return t('pages.saga.msg_no_overlap');
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
                return t('pages.saga.msg_hash_committed');
            });
            setPendingMutationFlag(plotId, true);
            appendLedger('SAGA_COMMITTED', { plotId, deedId });
            return t('pages.saga._flag_set');
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
                comp.push(t('pages.saga.comp_lock_released'));
            } else if (s.compensate === 'revertMutation') {
                setPendingMutationFlag(plotId, false);
                comp.push(t('pages.saga.comp_mutation_reverted'));
            } else if (s.compensate === 'flagRejected') {
                comp.push(t('pages.saga.comp_kyc_rejected'));
            }
        }
        appendLedger('SAGA_ABORTED', { plotId, deedId, failedAt: err.failedAt });
        pushEvent('SAGA_ABORTED', { plotId, failedAt: err.failedAt });
        return { sagaId, status: 'ABORTED', failedAt: err.failedAt, log, compensations: comp };
    }
}
