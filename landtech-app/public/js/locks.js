// ============================================================
// M3 · Real-Time Plot Lock Service — SIMULATED Redis Redlock
// (in-browser TTL locks; NOT a real Redis server)
// ============================================================
import { getLocks, saveLocks, getPlots, savePlots, pushEvent, nowISO, uid } from './store.js';

const TTL_MS = 5 * 60 * 1000; // 5 minutes lock TTL

export function activeLocks() {
    const locks = getLocks();
    const now = Date.now();
    let changed = false;
    for (const [k, v] of Object.entries(locks)) {
        if (v.expiresAt < now) { delete locks[k]; changed = true; }
    }
    if (changed) saveLocks(locks);
    return locks;
}

export function isLocked(plotId) {
    return plotId in activeLocks();
}

/** Acquire simulated Redlock. Returns {ok, reason}. */
export function acquireLock(plotId, holder) {
    const locks = activeLocks();
    if (plotId in locks) {
        return { ok: false, reason: `Plot already locked by "${locks[plotId].holder}" until ${new Date(locks[plotId].expiresAt).toLocaleTimeString()} (simulated Redlock contention)` };
    }
    const plot = getPlots().find(p => p.id === plotId);
    const key = plot ? plot.redlockKey : `LOCK:LAND:${plotId}`;
    locks[plotId] = { key, holder, expiresAt: Date.now() + TTL_MS, acquiredAt: nowISO(), token: uid('tkn') };
    saveLocks(locks);
    pushEvent('PLOT_LOCKED', { plotId, key, holder });
    return { ok: true, lock: locks[plotId] };
}

export function releaseLock(plotId, holder) {
    const locks = activeLocks();
    const lock = locks[plotId];
    if (!lock) return { ok: false, reason: 'No active lock' };
    if (lock.holder !== holder) return { ok: false, reason: 'Lock token mismatch (simulated Redlock safety)' };
    delete locks[plotId];
    saveLocks(locks);
    pushEvent('PLOT_UNLOCKED', { plotId, holder });
    return { ok: true };
}

/** Pending Mutation Flag — blocks transfer (per architecture: DEED_EXECUTED → flag → block). */
export function setPendingMutationFlag(plotId, flag = true) {
    const plots = getPlots();
    const plot = plots.find(p => p.id === plotId);
    if (!plot) return false;
    plot.mutationStatus = flag ? 'PENDING_MUTATION' : 'MUTATED';
    savePlots(plots);
    pushEvent(flag ? 'PENDING_MUTATION_FLAG_SET' : 'MUTATION_COMPLETED', { plotId });
    return true;
}

export function isTransferBlocked(plotId) {
    const plot = getPlots().find(p => p.id === plotId);
    return plot && plot.mutationStatus === 'PENDING_MUTATION';
}
